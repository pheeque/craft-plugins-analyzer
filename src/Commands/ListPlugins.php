<?php

namespace Pheeque\CraftPluginsAnalyzer\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Pheeque\CraftPluginsAnalyzer\Cache;
use Pheeque\CraftPluginsAnalyzer\CraftPluginPackage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListPlugins extends Command {
    protected static $defaultName = 'list-plugins';

    private ClientInterface $httpClient;

    private Cache $cache;

    public function __construct(ClientInterface $client = null, Cache $cache = null)
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
            $this->cache = new Cache($this->httpClient);
            $this->cache->load(__DIR__ . '/../../cache.json');
        } else {
            $this->cache = $cache;
        }

        parent::__construct();
    }

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
                'Field to sort results by, allowed values: downloads, favers, dependents, updated ',
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

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $progressBar = new ProgressBar($output, 50);

        $res = $this->httpClient->request('GET', 'https://packagist.org/packages/list.json?type=craft-plugin');

        $data = json_decode($res->getBody());

        $packageNames = $data->packageNames;

        $progressBar->setMaxSteps(count($packageNames));

        $packages = [];
        foreach ($packageNames as $name) {
            $package = new CraftPluginPackage($name);
            $package->hydrate($this->cache);

            //skip packages without a handle or abandoned
            if ($package->handle || ! $package->isAbandoned()) {
                $packages[] = $package;
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        //order
        $order = $input->getOption('order');
        $orderBy = $input->getOption('orderBy');
        usort($packages, function ($a, $b) use ($orderBy, $order) {
            $orderValues = match($orderBy) {
                'downloads' => [
                    $a->downloads,
                    $b->downloads,
                ],
                'favers' => [
                    $a->favers,
                    $b->favers,
                ],
                'dependents' => [
                    $a->dependents,
                    $b->dependents,
                ],
            };

            if ($order == 'DESC') {
                return $orderValues[0] < $orderValues[1] ? 1 : -1;
            } else {
                return $orderValues[0] > $orderValues[1] ? 1 : -1;
            }
        });

        //limit option
        $packages = array_slice($packages, 0, $input->getOption('limit'));

        //output option
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
            ])->setRows(array_map(fn (CraftPluginPackage $package) => $package->toArray(), $packages));

            $output->writeln('');
            $table->render();
        } else {
            //save to file
            file_put_contents($outputFile, json_encode($packages));

            $output->writeln('Output saved to ' . $outputFile);
        }

        return Command::SUCCESS;
    }
}
