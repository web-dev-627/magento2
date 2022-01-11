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

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Mirasvit\OptimizeImage\Api\Data\FileInterface;
use Mirasvit\OptimizeImage\Model\Config;
use Mirasvit\OptimizeImage\Repository\FileRepository;

class FileListSynchronizationService
{
    private $config;

    private $fileRepository;

    private $fs;

    public function __construct(
        Config $config,
        FileRepository $fileRepository,
        Filesystem $fs
    ) {
        $this->config         = $config;
        $this->fileRepository = $fileRepository;
        $this->fs             = $fs;
    }

    /**
     * @param int $limit
     */
    public function synchronize($limit)
    {
        foreach ($this->getSyncPaths() as $syncPath) {
            $files = $this->scanDir($syncPath, $limit);
            shuffle($files);

            foreach ($files as $file) {
                 $this->ensureFile($file);
            }
        }
    }

    /**
     * @param string $file
     *
     * @return false|FileInterface
     */
    public function ensureFile($file)
    {
        $extension = $this->config->getFileExtension($file);

        if(!$this->config->isAllowedFileExtension($extension)) {
            return false;
        }

        $pathInfo     = pathinfo($file);
        $relativePath = $this->config->getRelativePath($file);
        $size         = filesize($file);

        $model = $this->fileRepository->getByRelativePath($relativePath);

        if (!$model) {
            $model = $this->fileRepository->create();
            $model->setBasename($pathInfo['basename'])
                ->setRelativePath($relativePath)
                ->setFileExtension($extension)
                ->setOriginalSize($size);

            $this->fileRepository->save($model);
        } else {
            if ($model->getActualSize() && $model->getActualSize() != $size) {
                $model->setActualSize(null)
                    ->setOriginalSize($size);

                $this->fileRepository->save($model);
            }
        }

        return $model;
    }

    /**
     * @return array
     */
    public function getSyncPaths()
    {
        return [
            $this->fs->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath(),
        ];
    }

    /**
     * @param string $target
     * @param int    $limit
     *
     * @return array
     */
    public function scanDir($target, $limit)
    {
        $files = [];
        if (is_dir($target)) {
            $items = glob($target . '*', GLOB_MARK);
            shuffle($items);

            foreach ($items as $item) {
                if (is_file($item)) {
                    $files[] = $item;
                } else {
                    $files = array_merge($files, $this->scanDir($item, $limit - count($files)));
                }

                if (count($files) >= $limit) {
                    break;
                }
            }
        }

        return $files;
    }
}
