<?php

declare(strict_types=1);

namespace Imi\Migration\Listener;

use Imi\Aop\Annotation\Inject;
use Imi\Bean\Annotation\Listener;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;
use Imi\Migration\Service\MigrationService;
use Imi\Model\Cli\Model\Event\Param\BeforeGenerateModels;

/**
 * @Listener(BeforeGenerateModels::class)
 */
class BeforeGenerateModelsListener implements IEventListener
{
    /**
     * @Inject
     */
    protected MigrationService $migrationService;

    /**
     * 事件处理方法.
     *
     * @param BeforeGenerateModels $e
     */
    public function handle(EventParam $e): void
    {
        if ($this->migrationService->isOnGenerateModel())
        {
            var_dump('begin');
            $this->migrationService->beginModelMigration();
        }
        else
        {
            var_dump('not begin');
        }
    }
}
