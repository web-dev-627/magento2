<?php

namespace Wws\Multistore\Console\Command;

use Wws\Multistore\Helper\Export;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportStoresCommand extends Command
{

    /** @var Export */
    private $export;

    public function __construct(Export $export)
    {
        $this->export = $export;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('wws:multistore:export')
            ->setDescription('Exports all store view base urls to a JSON file that can be parsed by the multistore autoloader.')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Output file. Leave empty to generate at the default location.', BP . '/var/stores.json')
            ->addOption('store', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Export base urls of the specified store(s).')
            ->addOption('website', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Export base urls of stores in the specified website(s).');
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('filename');

        $websiteCodes = (array)$input->getOption('website');
        $storeCodes = (array)$input->getOption('store');

        // Merge in store codes extracted from the specified websites (if any).
        empty($websiteCodes) or $storeCodes = array_unique(array_merge($storeCodes, $this->export->getStoreCodesFromWebsites($websiteCodes)));

        // Preload all registered stores if no stores were specified as option.
        empty($storeCodes) and $storeCodes = $this->export->getAllStoreCodes();

        $this->export->exportStores($filename, $storeCodes);

        $output->writeln("<info>Store view configuration was written to <comment>$filename</comment>.</info>");
        $output->writeln('');

        $data = @json_decode(@file_get_contents($filename), true);

        if (is_array($data)) {
            $table = new Table($output);
            $table->setHeaders(['Store code', 'Base url']);
            $table->setRows((function ($data) {
                $rows = [];
                foreach ($data as $url => $code)
                    $rows[] = [$code, $url];
                return $rows;
            })($data));
            $table->render();
        } else {
            $output->writeln('<error>Unable to decode contents of the store view configuration file.</error>');
        }

        return null;
    }

}