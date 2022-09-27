<?php

declare(strict_types=1);

namespace Imi\Migration\Test;

use function Imi\env;
use function Imi\ttyExec;
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
    ALTER TABLE `tb_diff1` COMMENT='123' ;
    ALTER TABLE `tb_diff1` DROP COLUMN `drop` ;
    ALTER TABLE `tb_diff1` MODIFY COLUMN `modify` text COLLATE utf8mb4_unicode_ci NOT NULL FIRST;
    ALTER TABLE `tb_diff1` ADD COLUMN `add` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `index2`;
    ALTER TABLE `tb_diff1` 
    PARTITION BY HASH (`id`)
    (
    PARTITION p0 ENGINE=InnoDB,
    PARTITION p1 ENGINE=InnoDB,
    PARTITION p2 ENGINE=InnoDB,
    PARTITION p3 ENGINE=InnoDB
    ) ;
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

        Query: ALTER TABLE `tb_diff1` 
        PARTITION BY HASH (`id`)
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
