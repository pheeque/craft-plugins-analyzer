<?php

namespace Pheeque\CraftPluginsAnalyzer\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Pheeque\CraftPluginsAnalyzer\Cache;
use Pheeque\CraftPluginsAnalyzer\CraftPluginPackage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListPlugins extends Command {
    protected static $defaultName = 'list-plugins';

    private ClientInterface $httpClient;

    private Cache $cache;

    public function __construct(ClientInterface $client = null)
    {
        if (! $client) {
            $this->httpClient = new Client();
        }

        $this->cache = new Cache($this->httpClient);
        $this->cache->load(__DIR__ . '/../cache.json');

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
        $res = $this->httpClient->request('GET', 'https://packagist.org/packages/list.json?type=craft-plugin');

        $data = json_decode($res->getBody());

        $packageNames = array_slice($data->packageNames, 0, $input->getOption('limit'));
        $packages = [];
        foreach ($packageNames as $name) {
            $package = new CraftPluginPackage($name);
            $package->hydrate($this->cache);

            $packages[] = $package->toArray();
        }

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
            ])->setRows($packages);
            $table->render();
        } else {
            //save to file
            file_put_contents($outputFile, json_encode($packages));

            $output->writeln('Output saved to ' . $outputFile);
        }

        return Command::SUCCESS;
    }
}
