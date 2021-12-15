<?php

namespace Pheeque\CraftPluginsAnalyzer;

use DateTime;

class CraftPluginPackage implements \JsonSerializable
{
    public string $name;
    public ?string $description;
    public string $handle; // versions[0].extra.handle attribute
    public string $repository;
    public ?string $testLibrary;
    public string $version; // most recent branch
    public int $downloads; // downloads.monthly
    public int $dependents;
    public int $favers;
    public DateTime $updated;

    public function __construct(string $name)
    {
        $this->name = $name;

        $this->description = '';
        $this->handle = '';
        $this->repository = '';
        $this->testLibrary = '';
        $this->version = '';
        $this->downloads = 0;
        $this->dependents = 0;
        $this->favers = 0;
        $this->updated = new DateTime();
    }

    public function hydrate(Cache $cache)
    {
        $data = $cache->get($this->name);

        $this->description = $data['description'];
        $this->handle = $data['handle'];
        $this->repository = $data['repository'];
        $this->testLibrary = $data['testLibrary'];
        $this->version = $data['version'];
        $this->downloads = (int) $data['downloads'];
        $this->dependents = (int) $data['dependents'];
        $this->favers = (int) $data['favers'];
        $this->updated = new DateTime();
    }

    public function jsonSerialize()
    {
        return [
            $this->name,
            $this->description,
            $this->handle,
            $this->repository,
            $this->testLibrary,
            $this->version,
            $this->downloads,
            $this->dependents,
            $this->favers,
            $this->updated->format('Y-m-d H:i:s'),
        ];
    }

    public function toArray()
    {
        return $this->jsonSerialize();
    }
}
