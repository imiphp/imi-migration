<?php

declare(strict_types=1);

namespace Imi\Migration\Listener;

use Imi\Aop\Annotation\Inject;
use Imi\Bean\Annotation\Listener;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;
use Imi\Migration\Service\MigrationService;
use Imi\Model\Cli\Model\Event\Param\AfterGenerateModel;

/**
 * @Listener(AfterGenerateModel::class)
 */
class AfterGenerateModelListener implements IEventListener
{
    /**
     * @Inject
     */
    protected MigrationService $migrationService;

    /**
     * 事件处理方法.
     *
     * @param AfterGenerateModel $e
     */
    public function handle(EventParam $e): void
    {
        if ($this->migrationService->isModelMigrationBegined())
        {
            $this->migrationService->generateModelMigrationSql($e->fullClassName, $e->tableName, $e->rawDDL);
        }
    }
}
