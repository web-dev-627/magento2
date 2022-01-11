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

class WebpService
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
     * @param FileInterface $file
     *
     * @return FileInterface
     * @throws NotFoundException
     */
    public function process(FileInterface $file)
    {
        if(!$this->validationService->canConvertWebp()) {
            return $file;
        }

        switch ($file->getFileExtension()) {
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                $this->generateWebp($file);
                $file->setWebpPath($file->getRelativePath() . Config::WEBP_SUFFIX);

                break;
        }

        return $file;
    }

    /**
     * @param FileInterface $file
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function generateWebp(FileInterface $file)
    {
        $path = $this->config->getAbsolutePath($file->getRelativePath());

        if (!file_exists($path)) {
            throw new NotFoundException(__('The file was removed: %1', $path));
        }

        $command = $this->config->getFileExtension($path) == 'gif'
            ? Config::CMD_PROCESS_GIF2WEBP
            : Config::CMD_PROCESS_WEBP;

        $newPath = $path . Config::WEBP_SUFFIX;

        $configCompression = $this->config->getCompressionLevel();

        if (file_exists($newPath) && $file->getCompression() == $configCompression) {
            return;
        }

        $isCompressed = file_exists($path . Config::BACKUP_SUFFIX);

        if ($isCompressed) {
            $this->compressedToTmp($path);
        }

        try {
            $this->shell->execute(sprintf($command, $configCompression, $path, $newPath));
        } catch (\Exception $e) {
            if ($convertedPath = $this->normalize($path)) {
                $this->shell->execute(sprintf($command, $configCompression, $convertedPath, $newPath));
                unlink($convertedPath);
            }
        }

        if ($isCompressed) {
            $this->tmpToCompressed($path);
        }
    }

    /**
     * Normilize image when error appears during webp convertion
     *
     * @param string $path
     *
     * @return bool|string
     */
    private function normalize($path)
    {
        $convertedPath = $path . Config::CONVERT_SUFFIX;

        try {
            $this->shell->execute(sprintf(Config::CMD_CONVERT_RGB, $path, $convertedPath));

            return $convertedPath;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Temporary replace compressed file with original file
     *
     * @param string $path
     */
    private function compressedToTmp($path)
    {
        rename($path, $path . Config::TMP_SUFFIX);
        rename($path . Config::BACKUP_SUFFIX, $path);
    }

    /**
     * Replace original file with compressed file after webp conversion
     *
     * @param string $path
     */
    private function tmpToCompressed($path)
    {
        rename($path, $path . Config::BACKUP_SUFFIX);
        rename($path . Config::TMP_SUFFIX, $path);
    }
}
