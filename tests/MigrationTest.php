<?php

declare(strict_types=1);

namespace Imi\Migration\Test;

use function Imi\env;
use function Imi\ttyExec;
use Imi\Util\File;
use PHPUnit\Framework\TestCase;

class MigrationTest extends TestCase
{
    public const SQL_DIFF = <<<SQL
    DROP TABLE `tb_diff1_2`;
    CREATE TABLE `tb_test1` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `b` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
      PRIMARY KEY (`id`) USING BTREE,
      KEY `b` (`b`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
    ALTER TABLE `tb_diff1` COMMENT='123';
    ALTER TABLE `tb_diff1` DROP COLUMN `drop`;
    ALTER TABLE `tb_diff1` MODIFY COLUMN `modify` text COLLATE utf8mb4_unicode_ci NOT NULL FIRST;
    ALTER TABLE `tb_diff1` ADD COLUMN `add` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `index2`;
    ALTER TABLE `tb_diff1` PARTITION BY HASH (`id`)
    (
    PARTITION p0 ENGINE=InnoDB,
    PARTITION p1 ENGINE=InnoDB,
    PARTITION p2 ENGINE=InnoDB,
    PARTITION p3 ENGINE=InnoDB
    );
    CREATE OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `v1`  AS SELECT 1 AS `a`, 2 AS `b` ;
    SQL;

    public function testPatch(): void
    {
        $sql = <<<SQL
        Query: DROP TABLE `tb_diff1_2`
        result: OK, time: %ss

        Query: CREATE TABLE `tb_test1` (
          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `b` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          PRIMARY KEY (`id`) USING BTREE,
          KEY `b` (`b`) USING BTREE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC
        result: OK, time: %ss

        Query: ALTER TABLE `tb_diff1` COMMENT='123'
        result: OK, time: %ss

        Query: ALTER TABLE `tb_diff1` DROP COLUMN `drop`
        result: OK, time: %ss

        Query: ALTER TABLE `tb_diff1` MODIFY COLUMN `modify` text COLLATE utf8mb4_unicode_ci NOT NULL FIRST
        result: OK, time: %ss

        Query: ALTER TABLE `tb_diff1` ADD COLUMN `add` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `index2`
        result: OK, time: %ss

        Query: ALTER TABLE `tb_diff1` PARTITION BY HASH (`id`)
        (
        PARTITION p0 ENGINE=InnoDB,
        PARTITION p1 ENGINE=InnoDB,
        PARTITION p2 ENGINE=InnoDB,
        PARTITION p3 ENGINE=InnoDB
        )
        result: OK, time: %ss

        Query: CREATE OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `v1`  AS SELECT 1 AS `a`, 2 AS `b` 
        result: OK, time: %ss
        SQL;
        $pattern = '/' . preg_quote($sql) . '/';
        $pattern = str_replace('%s', '.+', $pattern);

        $this->initSql();
        ob_start();
        passthru($this->getCmd('migration/patch', [], ['f']), $resultCode);
        $output = ob_get_clean();
        $this->assertNotFalse((bool) preg_match($pattern, $output), 'output:' . $output);

        $this->initSql();
        ob_start();
        passthru($this->getCmd('migration/patch', [], ['f', 'driver' => 'PdoMysqlDriver', 'options' => $this->getOptions()]), $resultCode);
        $output = ob_get_clean();
        $this->assertNotFalse((bool) preg_match($pattern, $output), 'output:' . $output);
    }

    public function testDump(): void
    {
        $this->initSql();

        ob_start();
        passthru($this->getCmd('migration/dump'), $resultCode);
        $output = ob_get_clean();
        $this->assertEquals(0, $resultCode);
        $this->assertNotFalse(strpos($output, self::SQL_DIFF), 'output:' . $output);

        $fileName = __DIR__ . '/1.log';
        if (is_file($fileName))
        {
            unlink($fileName);
        }
        passthru($this->getCmd('migration/dump', [], ['file' => $fileName]), $resultCode);
        $this->assertEquals(0, $resultCode);
        $this->assertTrue(is_file($fileName));
        $this->assertEquals(self::SQL_DIFF, file_get_contents($fileName));

        ob_start();
        passthru($this->getCmd('migration/dump', [], ['driver' => 'PdoMysqlDriver', 'options' => $this->getOptions()]), $resultCode);
        $output = ob_get_clean();
        $this->assertEquals(0, $resultCode);
        $this->assertNotFalse(strpos($output, self::SQL_DIFF), 'output:' . $output);

        $fileName = __DIR__ . '/1.log';
        if (is_file($fileName))
        {
            unlink($fileName);
        }
        passthru($this->getCmd('migration/dump', [], ['file' => $fileName, 'driver' => 'PdoMysqlDriver', 'options' => $this->getOptions()]), $resultCode);
        $this->assertEquals(0, $resultCode);
        $this->assertTrue(is_file($fileName));
        $this->assertEquals(self::SQL_DIFF, file_get_contents($fileName));
    }

    public function testMigrateGenerate(): void
    {
        $this->initSql();

        $path = \dirname(__DIR__) . '/example/.migration';
        if (is_dir($path))
        {
            File::deleteDir($path);
        }
        $this->assertFileDoesNotExist($path);

        $generateCommand = \dirname(__DIR__) . '/example/bin/imi-cli generate/model "app\Model" --app-namespace "app" --prefix=tb_ --override=base --lengthCheck --sqlSingleLine';
        $generateCommandNoMigration = $generateCommand . ' --no-migration';
        $dataFile = \dirname(__DIR__) . '/example/.migration/data.json';

        $modelPath = \dirname(__DIR__) . '/example/Model';
        shell_exec('rm -rf ' . \dirname(__DIR__) . '/Model');
        shell_exec('cp -r -f ' . $modelPath . ' ' . \dirname(__DIR__));

        try
        {
            // 生成模型
            passthru($generateCommand, $resultCode);
            $this->assertEquals(0, $resultCode);
            $this->assertTrue(is_file($dataFile));
            $this->assertNotEquals('[]', $content = file_get_contents($dataFile));
            $data = json_decode($content, true, \JSON_THROW_ON_ERROR);
            $version = $data['version'] ?? null;
            $this->assertIsString($version);
            $versionPath = $path . '/' . $version;

            // 迁移文件内容校验
            $content = shell_exec('cat ' . $versionPath . '/down/*_tb_diff1.update.sql');
            $this->assertStringMatchesFormat(<<<SQL
            ALTER TABLE `tb_diff1` COMMENT='123';
            ALTER TABLE `tb_diff1` DROP COLUMN `drop`;
            ALTER TABLE `tb_diff1` MODIFY COLUMN `modify` text %s NOT NULL FIRST;
            ALTER TABLE `tb_diff1` ADD COLUMN `add` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `index2`;
            ALTER TABLE `tb_diff1` PARTITION BY HASH (`id`)
            (
            PARTITION p0 ENGINE=InnoDB,
            PARTITION p1 ENGINE=InnoDB,
            PARTITION p2 ENGINE=InnoDB,
            PARTITION p3 ENGINE=InnoDB
            );
            SQL, $content);

            $content = shell_exec('cat ' . $versionPath . '/down/*_tb_diff1_2.sql');
            $this->assertEquals(<<<SQL
            DROP TABLE `tb_diff1_2`;
            SQL, $content);

            $content = shell_exec('cat ' . $versionPath . '/down/*_v1.create.sql');
            $this->assertEquals(<<<SQL
            CREATE OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `v1`  AS SELECT 1 AS `a`, 2 AS `b` ;
            SQL, $content);

            $content = shell_exec('cat ' . $versionPath . '/up/*_tb_diff1.update.sql');
            $this->assertStringMatchesFormat(<<<SQL
            ALTER TABLE `tb_diff1` DROP COLUMN `add`;
            ALTER TABLE `tb_diff1` MODIFY COLUMN `modify` varchar(255) %s NOT NULL AFTER `id`;
            ALTER TABLE `tb_diff1` ADD COLUMN `drop` varchar(255) %s NOT NULL AFTER `modify`;
            SQL, $content);

            $content = shell_exec('cat ' . $versionPath . '/up/*_tb_diff1_2.create.sql');
            $this->assertStringMatchesFormat(<<<SQL
            CREATE TABLE `tb_diff1_2` (   `modify` text %ACOLLATE utf8mb4_unicode_ci NOT NULL,   `id` int%A unsigned NOT NULL AUTO_INCREMENT,   `index1` varchar(255) %ACOLLATE utf8mb4_unicode_ci DEFAULT NULL,   `index2` varchar(255) %wCOLLATE utf8mb4_unicode_ci DEFAULT NULL,   `add` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,   PRIMARY KEY (`id`) USING BTREE,   KEY `index_modify` (`index1`,`index2`) USING BTREE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='test diff';
            SQL, $content);

            $content = shell_exec('cat ' . $versionPath . '/up/*_v1.create.sql');
            $this->assertEquals(<<<SQL
            CREATE OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `v1`  AS SELECT 1 AS `a` ;
            SQL, $content);

            // 回滚失败
            ob_start();
            passthru($this->getCmd('migration/rollback', [], ['f']), $resultCode);
            $content = ob_get_clean();
            $this->assertNotEquals(0, $resultCode);
            $this->assertStringContainsString(sprintf('Current version "%s" is less than or equal to the target version "%s"', $version, $version), $content);
            // 生成模型
            passthru($generateCommandNoMigration, $resultCode);
            $this->assertEquals(0, $resultCode);
            $this->assertTrue(is_file($dataFile));
            $this->assertNotEquals('[]', $content = file_get_contents($dataFile));
            $data2 = json_decode($content, true, \JSON_THROW_ON_ERROR);
            $tmpVersion = $data2['version'] ?? null;
            $this->assertEquals($version, $tmpVersion); // 版本无变化

            // 回滚成功
            passthru($this->getCmd('migration/rollback', ['0'], ['f']), $resultCode);
            $this->assertEquals(0, $resultCode);
            shell_exec('rm -f ' . \dirname(__DIR__) . '/example/Model/Diff12.php ' . \dirname(__DIR__) . '/example/Model/Base/Diff12Base.php');

            // 生成模型
            passthru($generateCommandNoMigration, $resultCode);
            $this->assertEquals(0, $resultCode);
            $this->assertTrue(is_file($dataFile));
            $this->assertNotEquals('[]', $content = file_get_contents($dataFile));
            $data2 = json_decode($content, true, \JSON_THROW_ON_ERROR);
            $tmpVersion = $data2['version'] ?? null;
            $this->assertEquals('0', $tmpVersion);

            // 迁移
            passthru($this->getCmd('migration/migrate', [$version], ['f']), $resultCode);
            $this->assertEquals(0, $resultCode);
            // 生成模型
            passthru($generateCommandNoMigration, $resultCode);
            $this->assertEquals(0, $resultCode);
            $this->assertTrue(is_file($dataFile));
            $this->assertNotEquals('[]', $content = file_get_contents($dataFile));
            $data2 = json_decode($content, true, \JSON_THROW_ON_ERROR);
            $tmpVersion = $data2['version'] ?? null;
            $this->assertEquals($version, $tmpVersion); // 版本无变化
        }
        finally
        {
            $this->initSql();
            // 生成模型
            passthru($generateCommandNoMigration, $resultCode);
            shell_exec('rm -rf ' . $modelPath);
            shell_exec('mv -f ' . \dirname(__DIR__) . '/Model ' . $modelPath);
        }
    }

    private function initSql(): void
    {
        ttyExec([\dirname(__DIR__) . '/example/bin/imi-cli', 'sql/import']);
    }

    private function getCmd(string $commandName, array $arguments = [], array $options = []): string
    {
        $cmd = '"' . \PHP_BINARY . '" ' . escapeshellarg(\dirname(__DIR__) . '/example/bin/imi-cli') . ' ' . escapeshellarg($commandName);
        if ($arguments)
        {
            foreach ($arguments as $v)
            {
                $cmd .= ' ' . escapeshellarg((string) $v);
            }
        }
        foreach ($options as $k => $v)
        {
            if (is_numeric($k))
            {
                $cmd .= ' -' . (isset($v[1]) ? '-' : '') . $v;
            }
            else
            {
                $cmd .= ' -' . (isset($k[1]) ? '-' : '') . $k . ' ' . escapeshellarg((string) $v);
            }
        }

        return $cmd;
    }

    private function getOptions(): string
    {
        return sprintf('host=%s&port=%d&username=%s&password=%s&database=db_imi_migration_test', env('MYSQL_SERVER_HOST', '127.0.0.1'), env('MYSQL_SERVER_PORT', 3306), env('MYSQL_SERVER_USERNAME', 'root'), env('MYSQL_SERVER_PASSWORD', 'root'));
    }
}
