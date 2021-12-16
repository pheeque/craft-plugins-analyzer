<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pheeque\CraftPluginsAnalyzer\Cache;
use Pheeque\CraftPluginsAnalyzer\Commands\ListPlugins;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

test('executes command', function () {
    $application = new Application();

    $httpClient = new Client([
        'handler' => HandlerStack::create(
            new MockHandler([
                new Response(200, [],
                    file_get_contents('tests/fixtures/package-names.json')),
                new Response(200, [],
                    file_get_contents('tests/fixtures/package-data-with-stats.json')),
            ])
        ),
    ]);
    $cache = new Cache($httpClient, false);
    $cache->load('tests/fixtures/test-cache.json');

    $command = new ListPlugins($httpClient, $cache);
    $application->add($command);
    $commandTester = new CommandTester($command);
    $commandTester->execute([
        'command' => $command->getName(),
        '--limit' => 1,
    ]);

    $commandTester->assertCommandIsSuccessful();
});
