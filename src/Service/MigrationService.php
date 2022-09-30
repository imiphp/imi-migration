<?php

declare(strict_types=1);

namespace Imi\Migration\Service;

use Imi\App;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\Cli\ImiCommand;
use Imi\Db\Db;
use Imi\Db\Exception\DbException;
use Imi\Db\Interfaces\IDb;
use Imi\Model\Annotation\DDL;
use Imi\Util\File;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Yurun\SqlDiff\SqlDiff;

class MigrationService
{
    public function patch(string $poolName, string $driver, string $options, bool $force, QuestionHelper $questionHelper): void
    {
        $db = $this->getDbDriver($poolName, $driver, $options);
        $modelSql = $this->getModelSql();
        $patchSqls = $this->getPatchSqls($db, $modelSql);
        $output = ImiCommand::getOutput();
        if ($patchSqls)
        {
            if (!$force)
            {
                $output->writeln('<info>Patch sql:</info>');
                $output->writeln(implode(';' . \PHP_EOL, $patchSqls) . \PHP_EOL);
                if (!$questionHelper->ask(ImiCommand::getInput(), $output, new ConfirmationQuestion('Continue to path?(y/n)', true)))
                {
                    return;
                }
            }
            $db->exec('SET FOREIGN_KEY_CHECKS=0;');
            foreach ($patchSqls as $sql)
            {
                $output->writeln('<info>Query: </info>' . $sql);
                $time = microtime(true);
                $db->exec($sql);
                $time = microtime(true) - $time;
                $output->writeln('<info>result: </info>OK, time: ' . round($time, 3) . 's');
                $output->writeln('');
            }
            $db->exec('SET FOREIGN_KEY_CHECKS=1;');
        }
        else
        {
            $output->writeln('<info>No sql</info>');
        }
    }

    public function dump(string $poolName, string $driver, string $options, string $file): void
    {
        $db = $this->getDbDriver($poolName, $driver, $options);
        $modelSql = $this->getModelSql();
        $patchSqls = $this->getPatchSqls($db, $modelSql);
        if ('' === $file)
        {
            $output = ImiCommand::getOutput();
            $output->writeln('<info>Patch sql:</info>');
            $output->writeln(implode(';' . \PHP_EOL, $patchSqls) . ';');
        }
        else
        {
            File::putContents($file, implode(';' . \PHP_EOL, $patchSqls) . ';');
        }
    }

    public function getDbDriver(string $poolName, string $driver, string $options): IDb
    {
        if ('' !== $poolName)
        {
            return Db::getInstance($poolName);
        }
        if ('' !== $driver)
        {
            parse_str($options, $arrayOptions);
            $db = App::newInstance($driver, $arrayOptions);
            if (!$db->open())
            {
                throw new DbException('Db connect error: [' . $db->errorCode() . '] ' . $db->errorInfo());
            }

            return $db;
        }

        return Db::getInstance();
    }

    public function getModelSql(): string
    {
        $sqls = [];
        foreach (AnnotationManager::getAnnotationPoints(DDL::class, 'class') as $point)
        {
            /** @var \Imi\Model\Annotation\DDL $ddlAnnotation */
            $ddlAnnotation = $point->getAnnotation();
            $sqls[] = $ddlAnnotation->getRawSql();
        }

        return implode(';' . \PHP_EOL, $sqls) . ';';
    }

    public function getPatchSqls(IDb $db, string $modelSql): array
    {
        $database = $db->query('select database()')->fetch(\PDO::FETCH_BOTH)[0];
        $stmt = $db->prepare('select TABLE_NAME, TABLE_TYPE, TABLE_COMMENT from information_schema.TABLES where TABLE_SCHEMA = ? and TABLE_TYPE in (\'BASE TABLE\', \'VIEW\')');
        $stmt->execute([$database]);
        $list = $stmt->fetchAll();
        $dbSqls = [];
        foreach ($list as $item)
        {
            $stmt = $db->query('show create table ' . $item['TABLE_NAME']);
            $row = $stmt->fetch();
            $sql = $row['Create Table'] ?? $row['Create View'] ?? '';

            $dbSqls[] = preg_replace('/ AUTO_INCREMENT=\d+ /', ' ', $sql, 1);
        }
        $dbSql = implode(';' . \PHP_EOL, $dbSqls) . ';';

        return SqlDiff::diff($dbSql, $modelSql);
    }
}
