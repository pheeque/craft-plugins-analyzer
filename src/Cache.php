<?php

namespace Pheeque\CraftPluginsAnalyzer;

use GuzzleHttp\ClientInterface;

class Cache {

    private ClientInterface $httpClient;

    private array $items;

    private string $cacheFilename;


    public function __construct(ClientInterface $client)
    {
        $this->httpClient = $client;

        $this->items = [];
    }

    public function load(string $filename) : void
    {
        $this->cacheFilename = $filename;

        if (file_exists($this->cacheFilename)) {
            $this->items = json_decode(file_get_contents($this->cacheFilename), true);
        }
    }

    public function save()
    {
        file_put_contents($this->cacheFilename, json_encode($this->items));
    }

    public function get(string $packageName) : array
    {
        if (! isset($this->items[$packageName])) {
            //fetch data remotely
            $res = $this->httpClient->request('GET', 'https://packagist.org/packages/' . $packageName . '.json');
            $data = json_decode($res->getBody(), true);
            $composerData = $data['package'];

            $this->items[$packageName] = [
                'name' => $composerData['name'],
                'description' => $composerData['description'],
                // 'handle' => $composerData['handle'],
                'handle' => '',
                'repository' => $composerData['repository'],
                // 'testLibrary' => $composerData['testLibrary'],
                'testLibrary' => '',
                // 'version' => $composerData['version'],
                'version' => '',
                'downloads' => $composerData['downloads']['total'],
                // 'dependents' => $composerData['dependents'],
                'dependents' => '',
                'favers' => $composerData['favers'],
                'updated' => $composerData['time'],
            ];
        }

        return $this->items[$packageName];
    }

    public function __destruct()
    {
        $this->save();
    }
}
