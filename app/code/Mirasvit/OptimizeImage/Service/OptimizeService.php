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



namespace Mirasvit\OptimizeImage\Service;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Shell;
use Mirasvit\OptimizeImage\Api\Data\FileInterface;
use Mirasvit\OptimizeImage\Model\Config;

class OptimizeService
{
    /**
     * @var Shell
     */
    private $shell;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ValidationService
     */
    private $validationService;

    /**
     * @var bool
     */
    private $shouldCompress = false;

    public function __construct(
        Shell $shell,
        Config $config,
        ValidationService $validationService
    ) {
        $this->shell             = $shell;
        $this->config            = $config;
        $this->validationService = $validationService;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * 
     * @param FileInterface $file
     *
     * @return FileInterface
     * @throws NotFoundException
     */
    public function optimize(FileInterface $file)
    {
        $absPath = $this->config->getAbsolutePath($file->getRelativePath());

        if (!file_exists($absPath)) {
            throw new NotFoundException(__('The file was removed: %1', $absPath));
        }

        $this->checkShouldCompress($file, $absPath);

        switch ($file->getFileExtension()) {
            case 'jpg':
            case 'jpeg':
                $this->processJpg($absPath);
                break;
            case 'png':
                $this->processPng($absPath);
                break;
            case 'gif':
                $this->processGif($absPath);
                break;
        }

        if($this->shouldCompress && $file->getFileExtension() !== 'png'
            && file_exists($absPath . Config::BACKUP_SUFFIX)
            && filesize($absPath) == filesize($absPath . Config::BACKUP_SUFFIX)) {
            usleep(50); // need this for correct update of actual image size;
        }
        $file->setActualSize(filesize($absPath));
        $file->setProcessedAt(date('Y-m-d H:i:s'));
        $file->setCompression($this->config->getCompressionLevel());

        return $file;
    }

    /**
     * @param string $path
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processJpg($path)
    {
        if(!$this->validationService->canRunOptimizationFor('jpg')) {
            return;
        }

        $command = Config::CMD_PROCESS_JPG;

        if($this->shouldCompress) {
            copy($path, $path . Config::BACKUP_SUFFIX);
            $command .= ' --max=' . $this->config->getCompressionLevel();
        }

        $this->shell->execute(sprintf($command, $path));
    }

    /**
     * @param string $path
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processPng($path)
    {
        if(!$this->validationService->canRunOptimizationFor('png')) {
            return;
        }

        $command = Config::CMD_PROCESS_PNG;

        $this->shell->execute(sprintf($command, $path));
    }

    /**
     * @param string $path
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processGif($path)
    {
        if(!$this->validationService->canRunOptimizationFor('gif')) {
            return;
        }

        $command = Config::CMD_PROCESS_GIF;

        if($this->shouldCompress) {
            copy($path, $path . Config::BACKUP_SUFFIX);
            $command .= ' --lossy=' . $this->getGifCompressionLevel();
        }

        $this->shell->execute(sprintf($command, $path, $path));
    }

    /**
     * @param FileInterface $file
     * @param string $absPath
     *
     * @return void
     */
    private function checkShouldCompress($file, $absPath)
    {
        $compression = $this->config->getCompressionLevel();

        if(!file_exists($absPath . Config::BACKUP_SUFFIX) && $compression !== 100) {
            // no backup file - compress
            $this->shouldCompress = true;
        } elseif (file_exists($absPath . Config::BACKUP_SUFFIX) && $compression !== $file->getCompression()) {
            // backup file present but compression level was changed
            copy($absPath . Config::BACKUP_SUFFIX, $absPath);
            $this->shouldCompress = true;
        } else {
            $this->shouldCompress = false;
        }
    }

    /**
     * Convert image quality level to gif compression level
     *
     * @return int
     */
    private function getGifCompressionLevel()
    {
        // default gif compression is 20
        // higher value gives higher compression

        return 120 - $this->config->getCompressionLevel();
    }
}
