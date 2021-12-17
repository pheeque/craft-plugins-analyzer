<?php

namespace Pheeque\CraftPluginsAnalyzer\Analyzers;

use GuzzleHttp\ClientInterface;
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

    public function run(callable $onProgressUpdate) : array
    {
        $data = $this->getCraftPlugins($this->httpClient);

        $packageNames = $data->packageNames;

        $packages = [];
        foreach ($packageNames as $name) {
            $package = new CraftPluginPackage($name);
            $package->hydrate($this->cache);

            //skip packages without a handle or abandoned
            if ($package->handle || ! $package->isAbandoned()) {
                $packages[] = $package;
            }

            $onProgressUpdate(count($packageNames));
        }

        //order
        usort($packages, function ($a, $b) {
            $orderValues = match($this->orderBy) {
                'downloads' => [
                    $a->downloads,
                    $b->downloads,
                ],
                'favers' => [
                    $a->favers,
                    $b->favers,
                ],
                'dependents' => [
                    $a->dependents,
                    $b->dependents,
                ],
                'testLibrary' => [
                    $a->testLibrary,
                    $b->testLibrary,
                ],
            };

            if ($this->order == 'DESC') {
                return $orderValues[0] < $orderValues[1] ? 1 : -1;
            } else {
                return $orderValues[0] > $orderValues[1] ? 1 : -1;
            }
        });

        //limit option
        $packages = array_slice($packages, 0, $this->limit);

        return array_map(fn (CraftPluginPackage $package) => $package->toArray(), $packages);
    }
}
