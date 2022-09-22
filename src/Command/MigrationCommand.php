<?php

declare(strict_types=1);

namespace Imi\Migration\Command;

use Imi\Aop\Annotation\Inject;
use Imi\Cli\Annotation\Command;
use Imi\Cli\Annotation\CommandAction;
use Imi\Cli\Annotation\Option;
use Imi\Cli\ArgType;
use Imi\Cli\Contract\BaseCommand;
use Imi\Migration\Service\MigrationService;

/**
 * @Command("migration")
 */
class MigrationCommand extends BaseCommand
{
    /**
     * @Inject
     */
    protected MigrationService $migrationService;

    /**
     * @CommandAction(name="patch", description="将数据库中的数据表结构升级为模型中定义的结构")
     * @Option(name="poolName", type=ArgType::STRING, default="", comments="连接池名，也可留空使用手动指定 --driver、--options 参数")
     * @Option(name="driver", default="", comments="数据库驱动类名", type=ArgType::STRING)
     * @Option(name="options", default="", comments="数据库连接参数，格式：host=127.0.0.1&port=3306&username=root&password=root", type=ArgType::STRING)
     * @Option(name="force", shortcut="f", default=false, type=ArgType::BOOL_NEGATABLE)
     */
    public function patch(string $poolName, string $driver, string $options, bool $force): void
    {
        $this->migrationService->patch($poolName, $driver, $options, $force, $this->command->getHelper('question'));
    }

    /**
     * @CommandAction(name="dump", description="生成从模型升级到数据库的 SQL 语句")
     * @Option(name="poolName", type=ArgType::STRING, default="", comments="连接池名，也可留空使用手动指定 --driver、--options 参数")
     * @Option(name="driver", default="", comments="数据库驱动类名", type=ArgType::STRING)
     * @Option(name="options", default="", comments="数据库连接参数，格式：host=127.0.0.1&port=3306&username=root&password=root", type=ArgType::STRING)
     * @Option(name="file", shortcut="f", default="", type=ArgType::STRING)
     */
    public function dump(string $poolName, string $driver, string $options, string $file): void
    {
        $this->migrationService->dump($poolName, $driver, $options, $file);
    }
}
