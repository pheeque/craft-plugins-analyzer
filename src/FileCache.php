<?php

namespace Pheeque\CraftPluginsAnalyzer;

use GuzzleHttp\ClientInterface;
use Pheeque\CraftPluginsAnalyzer\Contracts\CacheInterface;
use Pheeque\CraftPluginsAnalyzer\Traits\InteractsWithPackagist;

class FileCache implements CacheInterface {

    use InteractsWithPackagist;

    /**
     * @var int
     */
    private int $lastChecked;

    /**
     * @var array
     */
    private array $items;

    /**
     * @param GuzzleHttp\ClientInterface $client
     * @param string|null $cacheFilename filename of the cache in storage
     *
     * @return void
     */
    public function __construct(
        private ClientInterface $client,
        private ?string $cacheFilename = NULL
    ) {
        $this->httpClient = $client;

        $this->items = [];

        $this->lastChecked = 10000 * time();

        if ($this->cacheFilename) {
            $this->load();
        }
    }

    /**
     * Retrieves package data from the cache
     * Fetches from remote server if package not present in cache or invalidated
     *
     * @param string $packageName
     *
     * @return array
     */
    public function get(string $packageName) : array
    {
        if (! isset($this->items[$packageName])) {
            $data = $this->getPackageData($this->httpClient, $packageName);

            $composerData = $data['package'];

            $firstVersion = reset($composerData['versions']);

            $handle = '';
            if (isset($firstVersion['extra']['handle'])) {
                $handle = $firstVersion['extra']['handle'];
            }

            $this->items[$packageName] = [
                'name' => $composerData['name'],
                'description' => $composerData['description'],
                'handle' => $handle,
                'repository' => $composerData['repository'],
                'testLibrary' => $this->getTestLibrary($firstVersion),
                'version' => $firstVersion['version'],
                'downloads' => $composerData['downloads']['total'],
                'dependents' => $composerData['dependents'],
                'favers' => $composerData['favers'],
                'time' => $firstVersion['time'],
                'abandoned' => isset($composerData['abandoned']) && $composerData['abandoned'] != 'false' ? true : false,
                'require' => isset($firstVersion['require']) ?
                    $firstVersion['require'] : [],
                'require-dev' => isset($firstVersion['require-dev']) ?
                    $firstVersion['require-dev'] : [],
            ];
        }

        return $this->items[$packageName];
    }

    /**
     * Load the cache data from a provided filename
     *
     * @return void
     */
    private function load() : void
    {
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

    /**
     * Save the cache to file
     *
     * @return void
     */
    private function save() : void
    {
        if ($this->cacheFilename) {
            file_put_contents($this->cacheFilename, json_encode([
                'items' => $this->items,
                'lastChecked' => $this->lastChecked,
            ]));
        }
    }

    /**
     * Confirms from packagist what cached packages to invalidate
     *
     * @return void
     */
    private function validateCacheData() : void
    {
        $data = $this->getModifiedPackages($this->httpClient, $this->lastChecked);

        foreach($data['actions'] as $action) {
            if (isset($this->items[$action['package']])) {
                unset($this->items[$action['package']]);
            }
        }

        $this->lastChecked = 10000 * time();
    }

    /**
     * Automatically saves the cache to file regardless of how the program is stopped
     */
    public function __destruct()
    {
        $this->save();
    }
}
