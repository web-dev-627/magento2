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




namespace Mirasvit\OptimizeImage\Processor;


use Mirasvit\Optimize\Api\Processor\OutputProcessorInterface;
use Mirasvit\OptimizeImage\Model\Config;
use Mirasvit\OptimizeImage\Repository\FileRepository;
use Mirasvit\OptimizeImage\Service\FileListSynchronizationService;
use Mirasvit\OptimizeImage\Service\FormatService;
use Mirasvit\OptimizeImage\Service\ResponsiveImageService;

class ImageProcessor implements OutputProcessorInterface
{
    private $config;

    private $fileRepository;

    private $formatService;

    private $syncService;

    public function __construct(
        Config $config,
        FileRepository $fileRepository,
        FormatService $formatService,
        FileListSynchronizationService $syncService
    ) {
        $this->config         = $config;
        $this->fileRepository = $fileRepository;
        $this->formatService  = $formatService;
        $this->syncService    = $syncService;
    }

    /**
     * {@inheritdoc}
     */
    public function process($content) {
        $content = preg_replace_callback(
            '/(<\s*img[^>]+)src\s*=\s*["\']([^"\'\?]+)(\?[^"\']*)?[\'"]([^>]{0,}>)/is',
            [$this, 'replaceCallback'],
            $content
        );

        return $content;
    }

    /**
     * @param array $match
     * @return string
     */
    private function replaceCallback(array $match)
    {
        $absolutePath = $this->config->retrieveImageAbsPath($match[2]);

        if (!$this->config->isFilesystemStrategy()) {
            $this->syncService->ensureFile($absolutePath);
        }

        $relativePath = $this->config->getRelativePath($absolutePath);
        $replacement  = $match[0];

        $file = $this->fileRepository->getByRelativePath($relativePath);
        $ext  = $this->config->getFileExtension($absolutePath);

        if($responsiveImage = $this->config->getResponsiveImageConfigByFileName($match[2])) {
            $mobileSuffix  = '.' . ResponsiveImageService::MOBILE_IDENTIFIER . '-mst.' . $ext;

            if(!file_exists($absolutePath . $mobileSuffix)) {
                return $replacement; // mobile image not generated
            }

            $desktopSuffix = (int)$responsiveImage[ResponsiveImageService::DESKTOP_IDENTIFIER]
                ? '.' . ResponsiveImageService::DESKTOP_IDENTIFIER . '-mst.' . $ext
                : '';

            if($desktopSuffix && !file_exists($absolutePath . $desktopSuffix)) {
                $desktopSuffix = ''; // desktop image removed or not generated
            }

            $srcset = $match[2] . $mobileSuffix . ' 480w, ' . $match[2] . $desktopSuffix . ' 800w';
            $sizes  = "(max-width: 480px) 480px, 800px";

            $additional  = 'srcset="' . $srcset . '" sizes="' . $sizes . '"';
            $replacement = str_replace('<img', '<img ' . $additional, $replacement);
        }

        if (!$this->config->isDebug()) {
            return $replacement;
        }

        if ($file && $file->getProcessedAt()) {
            $saved   = $file->getOriginalSize() - $file->getActualSize();
            $saved   = $this->formatService->formatBytes($saved);
            $hasWebp = $file->getWebpPath() ? "Yes" : "No";

            $info = "<span>Optimized. Saved $saved</span>
                     <span>Webp generated - $hasWebp</span>";
        } elseif (!$this->config->isAllowedFileExtension($ext)) {
            $info = "<span>Not allowed file extension $ext</span>";
        } else {
            $info = "<span>Not processed yet</span>";
        }

        return $replacement .= "<span class='mst-optwebp-debug'>$info</span>";
    }
}
