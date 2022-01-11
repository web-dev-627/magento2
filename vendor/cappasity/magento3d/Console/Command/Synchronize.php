<?php

namespace CappasityTech\Magento3D\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Synchronize extends Command
{
    private $sync;
    private $state;
    private $logger;

    public function __construct(
        \CappasityTech\Magento3D\Model\Sync $sync,
        \Magento\Framework\App\State $state,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->sync = $sync;
        $this->state = $state;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('cappasity:synchronize');
        $this->setDescription('Demo command line');
 
        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        $this->sync->cronCreateJobs();
    }
}
