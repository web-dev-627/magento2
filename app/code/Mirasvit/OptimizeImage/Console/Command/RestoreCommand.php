<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-optimize
 * @version   1.3.14
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */




namespace Mirasvit\OptimizeImage\Console\Command;

use Mirasvit\OptimizeImage\Model\Config;
use Mirasvit\OptimizeImage\Repository\FileRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestoreCommand extends Command
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var FileRepository
     */
    private $fileRepository;

    public function __construct(
        Config $config,
        FileRepository $fileRepository
    )
    {
        $this->config         = $config;
        $this->fileRepository = $fileRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('mirasvit:optimize-image:restore')
            ->setDescription('Restore compressed images');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Start restoring compressed images');

        foreach ($this->fileRepository->getCollection() as $file) {
            $absPath = $this->config->getAbsolutePath($file->getRelativePath());

            if (file_exists($absPath . Config::BACKUP_SUFFIX)) {
                rename($absPath . Config::BACKUP_SUFFIX, $absPath);

                $file->setCompression(100)
                    ->setOriginalSize(filesize($absPath))
                    ->setActualSize(null)
                    ->setProcessedAt(null);

                $this->fileRepository->save($file);
            }
        }

        $output->writeln("Done!");
    }
}
