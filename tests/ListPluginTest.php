<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pheeque\CraftPluginsAnalyzer\Commands\ListPlugins;
use Pheeque\CraftPluginsAnalyzer\FileCache;
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
                new Response(200, [],
                    file_get_contents('tests/fixtures/packages/craft-avatax.json')),
            ])
        ),
    ]);
    $cache = new FileCache($httpClient);

    $command = new ListPlugins($httpClient, $cache);
    $application->add($command);
    $commandTester = new CommandTester($command);
    $commandTester->execute([
        'command' => $command->getName(),
        '--limit' => 1,
    ]);

    $commandTester->assertCommandIsSuccessful();
});
