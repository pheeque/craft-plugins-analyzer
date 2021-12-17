<?php

namespace Pheeque\CraftPluginsAnalyzer\Traits;

use GuzzleHttp\ClientInterface;

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

}
