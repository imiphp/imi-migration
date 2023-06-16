<?php

declare(strict_types=1);

namespace app\Command;

use Imi\Cli\Annotation\Argument;
use Imi\Cli\Annotation\Command;
use Imi\Cli\Annotation\CommandAction;
use Imi\Cli\Contract\BaseCommand;
use Imi\Db\Db;

/**
 * @Command("sql")
 */
class SqlCommand extends BaseCommand
{
    /**
     * @CommandAction("import")
     *
     * @return void
     */
    public function import()
    {
        var_dump(\count(Db::getInstance()->batchExec(file_get_contents(\dirname(__DIR__) . '/mysql.sql'))));
    }

    /**
     * @CommandAction("exec")
     *
     * @Argument(name="sql", required=true, comments="SQL语句")
     *
     * @return void
     */
    public function exec(string $sql)
    {
        var_dump(\count(Db::getInstance()->batchExec($sql)));
    }
}
