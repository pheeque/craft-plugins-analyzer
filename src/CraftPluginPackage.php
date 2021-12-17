<?php

namespace Pheeque\CraftPluginsAnalyzer;

use DateTime;

class CraftPluginPackage implements \JsonSerializable
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var string|null
     */
    public ?string $description;

    /**
     * @var string
     */
    public string $handle; // versions[0].extra.handle attribute

    /**
     * @var string
     */
    public string $repository;

    /**
     * @var string|null
     */
    public ?string $testLibrary;

    /**
     * @var string
     */
    public string $version; // most recent branch

    /**
     * @var int
     */
    public int $downloads; // downloads.monthly

    /**
     * @var int
     */
    public int $dependents;

    /**
     * @var int
     */
    public int $favers;

    /**
     * @var DateTime
     */
    public DateTime $updated;

    /**
     * @var bool
     */
    private bool $abandoned;

    /**
     * Package constructor
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->abandoned = false;

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

    /**
     * Fetch package data from the cache
     *
     * @param Cache $cache
     *
     * @return void
     */
    public function hydrate(Cache $cache) : void
    {
        $data = $cache->get($this->name);

        $this->abandoned = $data['abandoned'];

        $this->description = $data['description'];
        $this->handle = $data['handle'];
        $this->repository = $data['repository'];
        $this->testLibrary = $data['testLibrary'];
        $this->version = $data['version'];
        $this->downloads = (int) $data['downloads'];
        $this->dependents = (int) $data['dependents'];
        $this->favers = (int) $data['favers'];
        $this->updated = new DateTime($data['time']);
    }

    /**
     * Determines if the package is abandoned or moved on packagist
     *
     * @return bool
     */
    public function isAbandoned() : bool
    {
        return $this->abandoned;
    }

    /**
     * Make package json serializable
     *
     * @return array
     */
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }

    /**
     * Returns plugin object as array
     *
     * @return array
     */
    public function toArray() : array
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
}
