<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pheeque\CraftPluginsAnalyzer\Cache;
use Pheeque\CraftPluginsAnalyzer\CraftPluginPackage;

it('hydrates plugin from cache', function () {
    $httpClient = new Client([
        'handler' => HandlerStack::create(
            new MockHandler([
                new Response(200, [],
                    file_get_contents('tests/fixtures/packages/craft-avatax.json')),
            ])
        ),
    ]);

    $cache = new Cache($httpClient, false);

    $package = new CraftPluginPackage('abryrath/craft-avatax');
    $package->hydrate($cache);

    expect($package->toArray())->toEqual([
        "abryrath/craft-avatax",
        "Calculate and add sales tax to an order's base tax using Avalara's Avatax service.",
        "avatax",
        "https://github.com/abryrath/craft-avatax",
        "",
        "dev-master",
        5,
        0,
        0,
        "2020-02-10 13:45:30",
    ]);
});

it('can retrieve testlibrary information', function () {
    $httpClient = new Client([
        'handler' => HandlerStack::create(
            new MockHandler([
                new Response(200, [],
                    file_get_contents('tests/fixtures/packages/craft-avatax.json')),
            ])
        ),
    ]);

    $cache = new Cache($httpClient, false);

    $package = new CraftPluginPackage('abyrath/craft-avatax');
    $package->hydrate($cache);

    expect($package->testLibrary)->toEqual('phpunit/phpunit');
});
