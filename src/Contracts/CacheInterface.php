<?php

namespace Pheeque\CraftPluginsAnalyzer\Contracts;

interface CacheInterface {
    /**
     * Retrieve package data from the cache by name
     *
     * @param string $packageName
     *
     * @return array
     */
    public function get(string $packageName) : array;
}
