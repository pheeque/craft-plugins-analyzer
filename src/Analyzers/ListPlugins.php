<?php

namespace Pheeque\CraftPluginsAnalyzer\Analyzers;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Collection;
use Pheeque\CraftPluginsAnalyzer\Analyzer;
use Pheeque\CraftPluginsAnalyzer\Contracts\CacheInterface;
use Pheeque\CraftPluginsAnalyzer\CraftPluginPackage;
use Pheeque\CraftPluginsAnalyzer\Traits\InteractsWithPackagist;

class ListPlugins extends Analyzer {

    use InteractsWithPackagist;

    public function __construct(
        private ClientInterface $httpClient,
        private CacheInterface $cache,
        private int $limit = 50,
        private string $orderBy = 'downloads',
        private string $order = 'DESC',
    ) {}

    /**
     * Run this analyzer
     * A progress update callable is passed in to update the caller of progress
     * during long-running operations.
     *
     * @param callable $onProgressUpdate
     *
     * @return array
     */
    public function run(callable $onProgressUpdate) : array
    {
        $data = $this->getCraftPlugins($this->httpClient);

        $packageNames = new Collection($data->packageNames);
        $count = $packageNames->count();
        $packages = $packageNames->map(function ($name) use ($onProgressUpdate, $count) {
            $onProgressUpdate($count);

            $package = new CraftPluginPackage($name);
            $package->hydrate($this->cache);

            return $package;
        })->filter(fn ($package) => $package->handle || ! $package->isAbandoned());

        if ($this->order == 'DESC') {
            $sorted = $packages->sortByDesc($this->orderBy);
        } else {
            $sorted = $packages->sortBy($this->orderBy);
        }

        return $sorted
                    ->slice(0, $this->limit)
                    ->map(fn (CraftPluginPackage $package) => $package->toArray())
                    ->toArray();
    }
}
