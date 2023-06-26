<?php

declare(strict_types=1);

namespace Imi\Migration\Handler;

interface IMigrationHandler
{
    public function saveUpSql(string $id, string $table, string $sql, string $Sqltype): void;

    public function saveDownSql(string $id, string $table, string $sql, string $Sqltype): void;

    public function getData(): array;

    public function setData(array $data): void;

    public function saveData(): void;

    public function getCurrentVersion(): string;

    public function setCurrentVersion(string $version): self;

    public function getVersionList(): array;

    public function addVersion(string $version): self;

    /**
     * @param string|string[] $version
     */
    public function removeVersion($version): self;

    public function migrate(?string $version, callable $callback): void;

    public function rollback(?string $version, ?int $step, callable $callback): void;
}
