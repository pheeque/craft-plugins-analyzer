<?php

namespace Pheeque\CraftPluginsAnalyzer;

use GuzzleHttp\ClientInterface;

class Cache {

    private int $lastChecked;

    private array $items;

    private string $cacheFilename;

    public function __construct(
        private ClientInterface $client,
        private bool $persist = true
    ) {
        $this->httpClient = $client;

        $this->items = [];

        $this->lastChecked = 10000 * time();
    }

    public function load(string $filename) : void
    {
        $this->cacheFilename = $filename;

        if (file_exists($this->cacheFilename)) {
            $data = json_decode(file_get_contents($this->cacheFilename), true);

            if (isset($data['items'])) {
                $this->items = $data['items'];
            }
            if (isset($data['lastChecked'])) {
                $this->lastChecked = $data['lastChecked'];
            }

            $this->validateCacheData();
        }
    }

    public function save()
    {
        if ($this->persist) {
            file_put_contents($this->cacheFilename, json_encode([
                'items' => $this->items,
                'lastChecked' => $this->lastChecked,
            ]));
        }
    }

    public function get(string $packageName) : array
    {
        if (! isset($this->items[$packageName])) {
            //fetch data remotely
            $res = $this->httpClient->request('GET', 'https://packagist.org/packages/' . $packageName . '.json');
            $data = json_decode($res->getBody(), true);
            $composerData = $data['package'];

            $firstVersion = reset($composerData['versions']);
            $time = $firstVersion['time'];

            $handle = '';
            if (isset($firstVersion['extra']['handle'])) {
                $handle = $firstVersion['extra']['handle'];
            }

            //TODO: confirm if time supersedes order in versions array
            // $versions = array_map(fn ($version) => [
            //     'version' => $version['version'],
            //     'time' => $version['time'],
            // ], array_values($composerData['versions']));

            $this->items[$packageName] = [
                'name' => $composerData['name'],
                'description' => $composerData['description'],
                'handle' => $handle,
                'repository' => $composerData['repository'],
                // 'testLibrary' => $composerData['testLibrary'],
                'testLibrary' => '',
                'version' => $firstVersion['version'],
                'downloads' => $composerData['downloads']['total'],
                'dependents' => $composerData['dependents'],
                'favers' => $composerData['favers'],
                'time' => $time,
                'abandoned' => isset($composerData['abandoned']) && $composerData['abandoned'] != 'false' ? true : false,
            ];
        }

        return $this->items[$packageName];
    }

    /**
     * Confirms from packagist what cached packages have updates.
     */
    public function validateCacheData()
    {
        $res = $this->httpClient->request('GET', 'https://packagist.org/metadata/changes.json?since=' . $this->lastChecked);
        $data = json_decode($res->getBody(), true);

        foreach($data['actions'] as $action) {
            if (isset($this->items[$action['package']])) {
                unset($this->items[$action['package']]);
            }
        }

        $this->lastChecked = 10000 * time();
    }

    public function __destruct()
    {
        $this->save();
    }
}
