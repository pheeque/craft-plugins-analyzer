<?php

namespace Pheeque\CraftPluginsAnalyzer\Traits;

use GuzzleHttp\ClientInterface;
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
        $testLibraries = [
            'phpunit/phpunit',
            'behat/behat',
            'pestphp/pest',
            'laravel/dusk',
            'behat/mink',
            'symfony/panther',
            'phpstan/phpstan',
        ];
        $testLibrary = '';
        if (isset($versionData['require'])) {
            $found = array_intersect($testLibraries, array_keys($versionData['require']));
            $found = reset($found);
            if ($found) {
                $testLibrary = $found;
            }
        }

        if (! $testLibrary) {
            if (isset($versionData['require-dev'])) {
                $found = array_intersect($testLibraries, array_keys($versionData['require-dev']));
                $found = reset($found);
                if ($found) {
                    $testLibrary = $found;
                }
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
