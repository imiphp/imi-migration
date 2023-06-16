<?php

declare(strict_types=1);

namespace Imi\Migration\Handler;

use Imi\App;
use Imi\AppContexts;
use Imi\Util\File;

class FileMigrationHandler implements IMigrationHandler
{
    public const DEFAULT_VERSION_LSIT = ['0'];

    protected ?string $path = null;

    private ?string $parsedPath = null;

    private ?array $data = null;

    public function __init(): void
    {
        $this->data = $this->loadData();
    }

    public function saveUpSql(string $id, string $table, string $sql, string $Sqltype): void
    {
        $this->saveSql('up', $id, $table, $sql, $Sqltype);
    }

    public function saveDownSql(string $id, string $table, string $sql, string $Sqltype): void
    {
        $this->saveSql('down', $id, $table, $sql, $Sqltype);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function saveData(): void
    {
        File::putContents($this->getDataFile(), json_encode($this->data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));
    }

    public function getCurrentVersion(): string
    {
        return $this->data['version'] ??= '';
    }

    public function setCurrentVersion(string $version): self
    {
        $this->data['version'] = $version;

        return $this;
    }

    public function getVersionList(): array
    {
        return $this->data['versionList'] ??= self::DEFAULT_VERSION_LSIT;
    }

    public function addVersion(string $version): self
    {
        if (!isset($this->data['versionList']))
        {
            $this->data['versionList'] = self::DEFAULT_VERSION_LSIT;
        }
        $this->data['versionList'][] = $version;
        rsort($this->data['versionList']);

        return $this;
    }

    /**
     * @param string|string[] $version
     */
    public function removeVersion($version): self
    {
        if (!isset($this->data['versionList']))
        {
            $this->data['versionList'] = self::DEFAULT_VERSION_LSIT;
        }
        $this->data['versionList'] = array_diff($this->data['versionList'], (array) $version);
        rsort($this->data['versionList']);

        return $this;
    }

    public function migrate(?string $version, callable $callback): void
    {
        $currentVersion = $this->getCurrentVersion();
        $versionList = $this->getVersionList();
        if (null === $version)
        {
            $version = $versionList[0] ?? '';
        }
        if (version_compare($currentVersion, $version, '>='))
        {
            throw new \InvalidArgumentException(sprintf('Current version "%s" is greater than or equal to the target version "%s"', $currentVersion, $version));

            return;
        }
        sort($versionList);
        $currentIndex = array_search($currentVersion, $versionList);
        if (false === $currentIndex)
        {
            throw new \InvalidArgumentException(sprintf('Current version "%s" does not exist', $currentVersion));

            return;
        }
        $targetIndex = array_search($version, $versionList);
        if (false === $targetIndex)
        {
            throw new \InvalidArgumentException(sprintf('Target version "%s" does not exist', $version));

            return;
        }
        try
        {
            for ($i = $currentIndex + 1; $i <= $targetIndex; ++$i)
            {
                $migrationVersion = $versionList[$i];
                $path = $this->path($migrationVersion, 'up');
                foreach (File::enumFile($path, null, ['sql']) as $file)
                {
                    $fileName = $file->getFullPath();
                    $sql = file_get_contents($fileName);
                    $callback($sql);
                }
                $this->setCurrentVersion($versionList[$i] ?? '');
            }
        }
        finally
        {
            $this->saveData();
        }
    }

    public function rollback(?string $version, ?int $step, callable $callback): void
    {
        $currentVersion = $this->getCurrentVersion();
        $versionList = $this->getVersionList();
        if (null === $version)
        {
            $version = $versionList[0] ?? '';
        }
        if (version_compare($currentVersion, $version, '<='))
        {
            throw new \InvalidArgumentException(sprintf('Current version "%s" is less than or equal to the target version "%s"', $currentVersion, $version));

            return;
        }
        $currentIndex = array_search($currentVersion, $versionList);
        if (false === $currentIndex)
        {
            throw new \InvalidArgumentException(sprintf('Current version "%s" does not exist', $currentVersion));

            return;
        }
        $targetIndex = array_search($version, $versionList);
        if (false === $targetIndex)
        {
            throw new \InvalidArgumentException(sprintf('Target version "%s" does not exist', $version));

            return;
        }
        try
        {
            if (null === $step)
            {
                $max = $targetIndex;
            }
            else
            {
                $max = min($targetIndex, $currentIndex + $step);
            }
            for ($i = $currentIndex; $i <= $max; ++$i)
            {
                $migrationVersion = $versionList[$i];
                $path = $this->path($migrationVersion, 'down');
                foreach (File::enumFile($path, null, ['sql']) as $file)
                {
                    $fileName = $file->getFullPath();
                    $sql = file_get_contents($fileName);
                    $callback($sql);
                }
                $this->setCurrentVersion($versionList[$i] ?? '');
            }
        }
        finally
        {
            $this->saveData();
        }
    }

    protected function saveSql(string $type, string $id, string $table, string $sql, string $Sqltype): void
    {
        $fileName = ((int) (microtime(true) * 1000)) . '_' . $table;
        if ('' !== $Sqltype)
        {
            $fileName .= '.' . $Sqltype;
        }
        $fileName .= '.sql';
        File::putContents($this->path($id, $type, $fileName), $sql);
    }

    protected function path(string ...$args): string
    {
        $this->parsedPath ??= ($this->path ?? File::path(App::get(AppContexts::APP_PATH_PHYSICS), '.migration'));

        return File::path($this->parsedPath, ...$args);
    }

    protected function getDataFile(): string
    {
        return $this->path('data.json');
    }

    protected function loadData(): array
    {
        $dataFile = $this->getDataFile();
        if (is_file($dataFile))
        {
            return json_decode(file_get_contents($dataFile), true);
        }
        else
        {
            return [];
        }
    }
}
