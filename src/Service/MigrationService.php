<?php

declare(strict_types=1);

namespace Imi\Migration\Service;

use Imi\App;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\Cli\ImiCommand;
use Imi\Db\Db;
use Imi\Db\Exception\DbException;
use Imi\Db\Interfaces\IDb;
use Imi\Migration\Handler\FileMigrationHandler;
use Imi\Migration\Handler\IMigrationHandler;
use Imi\Model\Annotation\DDL;
use Imi\Util\File;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Yurun\SqlDiff\SqlDiff;

class MigrationService
{
    protected string $handler = FileMigrationHandler::class;

    protected bool $onGenerateModel = true;

    private IMigrationHandler $handlerInstance;

    private ?string $modelMigrationId = null;

    private bool $sqlSaved = false;

    private array $tableModels = [];

    public function __init(): void
    {
        $this->handlerInstance = App::getBean($this->handler);
    }

    public function patch(string $poolName, string $driver, string $options, bool $force, QuestionHelper $questionHelper): void
    {
        $db = $this->getDbDriver($poolName, $driver, $options);
        $modelSql = $this->getModelSqls();
        $patchSqls = $this->getPatchSqls($db, $modelSql);
        $output = ImiCommand::getOutput();
        if ($patchSqls)
        {
            if (!$force)
            {
                $output->writeln('<info>Patch sql:</info>');
                $output->writeln(implode(';' . \PHP_EOL, $patchSqls) . \PHP_EOL);
                if (!$questionHelper->ask(ImiCommand::getInput(), $output, new ConfirmationQuestion('Continue to patch?(y/n)', true)))
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
        $modelSql = $this->getModelSqls();
        $patchSqls = $this->getPatchSqls($db, $modelSql);
        if ('' === $file)
        {
            $output = ImiCommand::getOutput();
            $output->writeln('<info>Patch sql:</info>');
            $output->writeln(implode(';' . \PHP_EOL, $patchSqls) . ($patchSqls ? ';' : ''));
        }
        else
        {
            File::putContents($file, implode(';' . \PHP_EOL, $patchSqls) . ($patchSqls ? ';' : ''));
        }
    }

    public function migrate(string $poolName, string $driver, string $options, bool $force, ?string $version, QuestionHelper $questionHelper): void
    {
        $output = ImiCommand::getOutput();
        if (!$force)
        {
            if (!$questionHelper->ask(ImiCommand::getInput(), $output, new ConfirmationQuestion('Continue to patch?(y/n)', true)))
            {
                return;
            }
        }
        $db = $this->getDbDriver($poolName, $driver, $options);
        $db->exec('SET FOREIGN_KEY_CHECKS=0;');
        $this->handlerInstance->migrate($version, function (string $sql) use ($db, $output) {
            $output->writeln('<info>Query: </info>' . $sql);
            $time = microtime(true);
            $db->batchExec($sql);
            $time = microtime(true) - $time;
            $output->writeln('<info>result: </info>OK, time: ' . round($time, 3) . 's');
            $output->writeln('');
        });
        $db->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function rollback(string $poolName, string $driver, string $options, bool $force, ?string $version, ?int $step, QuestionHelper $questionHelper): void
    {
        $output = ImiCommand::getOutput();
        if (!$force)
        {
            if (!$questionHelper->ask(ImiCommand::getInput(), $output, new ConfirmationQuestion('Continue to rollback?(y/n)', true)))
            {
                return;
            }
        }
        $db = $this->getDbDriver($poolName, $driver, $options);
        $db->exec('SET FOREIGN_KEY_CHECKS=0;');
        $this->handlerInstance->rollback($version, $step, function (string $sql) use ($db, $output) {
            $output->writeln('<info>Query: </info>' . $sql);
            $time = microtime(true);
            $db->batchExec($sql);
            $time = microtime(true) - $time;
            $output->writeln('<info>result: </info>OK, time: ' . round($time, 3) . 's');
            $output->writeln('');
        });
        $db->exec('SET FOREIGN_KEY_CHECKS=1;');
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

    public function getModelSqls(): string
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

    public function beginModelMigration(): void
    {
        if ($this->modelMigrationId)
        {
            throw new \RuntimeException('Model migration is running');
        }
        $microTime = microtime(true);
        $sec = (int) $microTime;
        $usec = $microTime - $sec; // 获取小数部分

        $this->modelMigrationId = date('YmdHis', $sec) . str_pad((string) (int) ($usec * 1000), 3, '0', \STR_PAD_LEFT);
        $this->sqlSaved = false;
        $this->tableModels = [];
    }

    public function endModelMigration(): void
    {
        if (!$this->modelMigrationId)
        {
            throw new \RuntimeException('Model migration is not running');
        }
        if ($this->sqlSaved)
        {
            $this->handlerInstance->setCurrentVersion($this->modelMigrationId);
            $this->handlerInstance->addVersion($this->modelMigrationId);
            $data = $this->handlerInstance->getData();
            $oldTableModels = $data['tableModels'] ?? [];
            $removeTableModels = array_diff($oldTableModels, $this->tableModels);
            foreach ($removeTableModels as $table => $modelClass)
            {
                $this->handlerInstance->saveUpSql($this->modelMigrationId, $table, 'DROP TABLE ' . $table, '');
                if ($ddlAnnotation = AnnotationManager::getClassAnnotations($modelClass, DDL::class, true, true))
                {
                    /** @var DDL $ddlAnnotation */
                    $sql = $ddlAnnotation->getRawSql();
                    $this->handlerInstance->saveDownSql($this->modelMigrationId, $table, $sql . ';', 'create');
                }
            }
            $data['tableModels'] = $this->tableModels;
            $this->handlerInstance->setData($data);
        }
        $this->handlerInstance->saveData();
        $this->modelMigrationId = null;
    }

    public function isModelMigrationBegined(): bool
    {
        return null !== $this->modelMigrationId;
    }

    public function generateModelMigrationSql(string $modelClass, string $table, string $newDDL): void
    {
        if (!$this->modelMigrationId)
        {
            throw new \RuntimeException('Model migration is not running');
        }
        if (class_exists($modelClass) && ($ddlAnnotation = AnnotationManager::getClassAnnotations($modelClass, DDL::class, false, true)))
        {
            /** @var DDL $ddlAnnotation */
            $upDiff = implode(';' . \PHP_EOL, SqlDiff::diff($ddlAnnotation->getRawSql(), $newDDL));
            $downDiff = implode(';' . \PHP_EOL, SqlDiff::diff($newDDL, $ddlAnnotation->getRawSql()));
        }
        else
        {
            $upDiff = $newDDL;
            $downDiff = implode(';' . \PHP_EOL, SqlDiff::diff($newDDL, ''));
        }
        if ('' !== $upDiff)
        {
            $this->handlerInstance->saveUpSql($this->modelMigrationId, $table, $upDiff . ';', $this->getSqlType($upDiff));
            $this->sqlSaved = true;
        }
        if ('' !== $downDiff)
        {
            $this->handlerInstance->saveDownSql($this->modelMigrationId, $table, $downDiff . ';', $this->getSqlType($downDiff));
            $this->sqlSaved = true;
        }
        $this->tableModels[$table] = $modelClass;
    }

    public function getSqlType(string $sql): string
    {
        $parser = new Parser($sql);
        $statement = reset($parser->statements);
        if (!$statement)
        {
            return '';
        }
        if ($statement instanceof CreateStatement)
        {
            return 'create';
        }
        if ($statement instanceof AlterStatement)
        {
            return 'update';
        }

        return '';
    }

    public function isOnGenerateModel(): bool
    {
        $noMigration = ImiCommand::getInput()->getParameterOption('--no-migration', false);

        return $this->onGenerateModel && (null !== $noMigration && !$noMigration);
    }
}
