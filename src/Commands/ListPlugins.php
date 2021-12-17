<?php

namespace Pheeque\CraftPluginsAnalyzer\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Pheeque\CraftPluginsAnalyzer\Analyzers\ListPlugins as AnalyzersListPlugins;
use Pheeque\CraftPluginsAnalyzer\Contracts\CacheInterface;
use Pheeque\CraftPluginsAnalyzer\FileCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * list-plugins command class
 * Displays all Craft plugins from packagist
 *
 */
class ListPlugins extends Command {
    /**
     * @var string
     */
    protected static $defaultName = 'list-plugins';

    /**
     * @var ClientInterface
     */
    private ClientInterface $httpClient;

    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * Command constructor
     *
     * @param ClientInterface|null $client
     * @param CacheInterface|null $cache
     */
    public function __construct(ClientInterface $client = null, CacheInterface $cache = null)
    {
        if (! $client) {
            $this->httpClient = new Client([
                'headers' => [
                    'User-Agent' => 'CraftPluginsAnalayzerBot/0.1 (+https://github.com/pheeque/craft-plugins-analyzer)',
                ],
            ]);
        } else {
            $this->httpClient = $client;
        }

        if (! $cache) {
            $this->cache = new FileCache(
                $this->httpClient,
                __DIR__ . '/../../cache.json'
            );
        } else {
            $this->cache = $cache;
        }

        parent::__construct();
    }

    /**
     * Set options/help for this command
     *
     * @return void
     */
    protected function configure() : void
    {
        $this
            ->setHelp('This tool allows you to analyze data about available plugins in the Craft CMS ecosystem.')
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of items to display',
                50
            )
            ->addOption(
                'output',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to the JSON file'
            )
            ->addOption(
                'orderBy',
                null,
                InputOption::VALUE_REQUIRED,
                'Field to sort results by, allowed values: downloads, favers, dependents, testLibrary, updated',
                'downloads'
            )
            ->addOption(
                'order',
                null,
                InputOption::VALUE_REQUIRED,
                'ASC or DESC',
                'DESC'
            );
    }


    /**
     * Run this command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $listPlugins = new AnalyzersListPlugins(
            $this->httpClient,
            $this->cache,
            $input->getOption('limit'),
            $input->getOption('orderBy'),
            $input->getOption('order'),
        );

        $progressBar = new ProgressBar($output, 50);

        $data = $listPlugins->run(function ($count) use ($progressBar) {
                $progressBar->setMaxSteps($count);

                $progressBar->advance();
            });

        $progressBar->finish();

        //output option
        $output->writeln('');
        $outputFile = $input->getOption('output');
        if (! $outputFile) {
            $table = new Table($output);
            $table->setHeaders([
                'Name',
                'Description',
                'Handle',
                'Repository',
                'Test library',
                'Version',
                'Downloads',
                'Dependents',
                'Favers',
                'Updated',
            ])->setRows($data);

            $table->render();
        } else {
            //save to file
            file_put_contents($outputFile, json_encode($data));

            $output->writeln('Output saved to ' . $outputFile);
        }

        return Command::SUCCESS;
    }
}
