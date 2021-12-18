<?php

namespace Pheeque\CraftPluginsAnalyzer\Traits;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Collection;
use stdClass;

trait InteractsWithPackagist {
    /**
     * Fetch package information from packagist
     *
     * @param ClientInterface $httpClient
     * @param string $packageName
     *
     * @return array
     */
    public function getPackageData(ClientInterface $httpClient, string $packageName) : array
    {
        $res = $httpClient->request('GET', 'https://packagist.org/packages/' . $packageName . '.json');
        return json_decode($res->getBody(), true);
    }

    public function getModifiedPackages(ClientInterface $httpClient, int $lastChecked) : array
    {
        $res = $httpClient->request('GET', 'https://packagist.org/metadata/changes.json?since=' . $lastChecked);
        return json_decode($res->getBody(), true);
    }

    /**
     * Retrieves test library used by the plugin
     *
     * @param array $versionData
     *
     * @return string|null
     */
    private function getTestLibrary(array $versionData) : ?string
    {
        $testLibraries = new Collection([
            'phpunit/phpunit',
            'behat/behat',
            'pestphp/pest',
            'laravel/dusk',
            'behat/mink',
            'symfony/panther',
            'phpstan/phpstan',
        ]);
        $testLibrary = '';
        if (isset($versionData['require'])) {
            $testLibrary = $testLibraries->intersect(
                array_keys($versionData['require'])
            )->first();
        }

        if (! $testLibrary) {
            if (isset($versionData['require-dev'])) {
                $testLibrary = $testLibraries->intersect(
                    array_keys($versionData['require-dev'])
                )->first();
            }
        }

        return $testLibrary;
    }

    public function getCraftPlugins(ClientInterface $httpClient) : stdClass
    {
        $res = $httpClient->request('GET', 'https://packagist.org/packages/list.json?type=craft-plugin');
        return json_decode($res->getBody());
    }
}
