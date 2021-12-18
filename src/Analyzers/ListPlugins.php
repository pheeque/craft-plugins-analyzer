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

        $packageNames = $data->packageNames;

        $packages = new Collection();
        foreach ($packageNames as $name) {
            $package = new CraftPluginPackage($name);
            $package->hydrate($this->cache);

            //skip packages without a handle or abandoned
            if ($package->handle || ! $package->isAbandoned()) {
                $packages->push($package);
            }

            $onProgressUpdate(count($packageNames));
        }

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
