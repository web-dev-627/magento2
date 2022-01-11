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


use Magento\Framework\Filesystem;
use Magento\Framework\Shell;
use Mirasvit\OptimizeImage\Model\Config;
use Mirasvit\OptimizeImage\Repository\FileRepository;

class ResponsiveImageService
{
    const RESIZE_COMMAND     = 'convert "%s" -resize %sx "%s"';
    const MOBILE_IDENTIFIER  = '480';
    const DESKTOP_IDENTIFIER = '800';

    /**
     * @var FileListSynchronizationService
     */
    private $fileSyncService;

    private $fileRepository;

    /**
     * @var Shell
     */
    private $shell;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        FileListSynchronizationService $fileSyncService,
        FileRepository $fileRepository,
        Shell $shell,
        Config $config
    ) {
        $this->fileSyncService = $fileSyncService;
        $this->fileRepository  = $fileRepository;
        $this->shell           = $shell;
        $this->config          = $config;
    }

    public function generate()
    {
        $responsiveImages = $this->config->getResponsiveImages();

        if(!$responsiveImages) {
            return false;
        }

        foreach ($responsiveImages as $imageConfig) {

            $fileCollection = $this->fileRepository
                ->getCollection()
                ->addFieldToFilter('relative_path', ['like' => '%' . $imageConfig['file'] . '%']);

            foreach ($fileCollection as $file) {
                $absPath = $this->config->getAbsolutePath($file->getRelativePath());

                if (!file_exists($absPath)) {
                    continue;
                }

                if (
                    strpos($absPath, self::MOBILE_IDENTIFIER . '-mst.' . $file->getFileExtension()) !== false
                    || strpos($absPath, self::DESKTOP_IDENTIFIER . '-mst.' . $file->getFileExtension()) !== false
                ) {
                    continue;
                }

                $this->resize($absPath, $imageConfig, self::MOBILE_IDENTIFIER);
                $this->resize($absPath, $imageConfig, self::DESKTOP_IDENTIFIER);
            }

        }

        return true;
    }

    public function cleanup()
    {
        $responsiveImages = $this->config->getResponsiveImages();

        if(!$responsiveImages) {
            return true;
        }

        foreach ($responsiveImages as $imageConfig) {
            $fileCollection = $this->fileRepository
                ->getCollection()
                ->addFieldToFilter('relative_path', ['like' => '%' . $imageConfig['file'] . '%' . '0-mst.' . '%']);

            foreach ($fileCollection as $file) {
                $imageAbsPath = $this->config->getAbsolutePath($file->getRelativePath());
                $webpAbsPath  = $file->getWebpPath() ? $this->config->getAbsolutePath($file->getWebpPath()) : null;

                if (file_exists($imageAbsPath)) {
                    unlink($imageAbsPath);
                }

                if ($webpAbsPath && file_exists($webpAbsPath)) {
                    unlink($webpAbsPath);
                }

                $this->fileRepository->delete($file);
            }
        }

        return true;
    }

    /**
     * @param string $path
     * @param array  $imageConfig
     * @param string $identifier
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function resize($path, array $imageConfig, $identifier)
    {
        $ext           = $this->config->getFileExtension($path);
        $resizedSuffix = '.' . $identifier . '-mst.' . $ext;

        if (file_exists($path . $resizedSuffix) || !(int)$imageConfig[$identifier]) {
            return;
        }

        $this->shell->execute(sprintf(
            self::RESIZE_COMMAND,
            $path,
            $imageConfig[$identifier],
            $path . $resizedSuffix
        ));

        $file = $this->fileSyncService->ensureFile($path . $resizedSuffix);
    }
}
