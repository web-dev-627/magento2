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

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Optimize\Api\Processor\OutputProcessorInterface;
use Mirasvit\OptimizeImage\Model\Config;
use Mirasvit\OptimizeImage\Repository\FileRepository;
use Mirasvit\OptimizeImage\Service\FileListSynchronizationService;
use Mirasvit\OptimizeImage\Service\ResponsiveImageService;

class WebpProcessor implements OutputProcessorInterface
{
    private $config;

    private $mediaUrl;

    private $mediaDir;

    private $fileRepository;

    private $syncService;

    public function __construct(
        Config $config,
        FileRepository $fileRepository,
        FileListSynchronizationService $syncService,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        $this->config         = $config;
        $this->fileRepository = $fileRepository;
        $this->syncService    = $syncService;
        $this->mediaUrl       = $storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $this->mediaDir       = $filesystem->getDirectoryread(DirectoryList::MEDIA);
    }

    /**
     * {@inheritdoc}
     */
    public function process($content)
    {
        if (!$this->config->isWebpEnabled()) {
            return $content;
        }

        $content = preg_replace_callback(
            '/(<\s*img[^>]+)src\s*=\s*["\']([^"\'\?]+)(\?[^"\']*)?[\'"]([^>]{0,}>(\s*<\/picture>)?)/is',
            [$this, 'replaceCallback'],
            $content
        );

        $content = $this->appendSwatcherFixScript($content);

        return $content;
    }

    /**
     * @param array $match
     *
     * @return string
     */
    private function replaceCallback(array $match)
    {
        if ($this->config->isWebpException($match[0]) || isset($match[5])) {
            return $match[0];
        }

        $url = $match[2];

        if (strpos($url, $this->mediaUrl) === false) {
            return $match[0];
        }

        $path = str_replace($this->mediaUrl, '', $url);

        $webpPath = $path . Config::WEBP_SUFFIX;

        if (!$this->mediaDir->isExist($webpPath)) {
            $absolutePath = $this->config->retrieveImageAbsPath($url);

            if ($absolutePath) {
                $relativePath = $this->config->getRelativePath($absolutePath);

                if ($image = $this->fileRepository->getByRelativePath($relativePath)) {
                    $image->setWebpPath(null);

                    $this->fileRepository->save($image);
                }
            }

            return $match[0];
        }

        $classes = '';

        if (preg_match('/class\s*=\s*"[^"]*"/i', $match[0], $found)) {
            $classes = ' data-mst-' . $found[0];
        }

        $defaultSource    = $this->getDefaultSource($path, $url, $classes, $webpPath, $match[3]);
        $responsiveSource = $this->getResponsiveSource($path, $url, $classes);

        return '<picture>' . $responsiveSource . $defaultSource . $match[0] . '</picture>';
    }

    /**
     * @param string $path
     * @param string $imageUrl
     * @param string $classes
     *
     * @return string
     */
    private function getResponsiveSource($path, $imageUrl, $classes)
    {
        $source = '';

        $ext           = $this->config->getFileExtension($path);
        $resizedSuffix = '.' . ResponsiveImageService::MOBILE_IDENTIFIER . '-mst.' . $ext;
        $webpPath      = $path . $resizedSuffix . Config::WEBP_SUFFIX;

        if ($this->mediaDir->isExist($webpPath)) {
            $webpUrl = str_replace($path, $webpPath, $imageUrl);
            $source  = '<source media="(max-width: 480px)" srcset="' . $webpUrl . '" type="image/webp"' . $classes . '/>';
        }

        return $source;
    }

    /**
     * @param string $path
     * @param string $imageUrl
     * @param string $classes
     * @param string $webpPath
     * @param string $query
     *
     * @return string
     */
    private function getDefaultSource($path, $imageUrl, $classes, $webpPath, $query)
    {
        $ext             = $this->config->getFileExtension($path);
        $resizedSuffix   = '.' . ResponsiveImageService::DESKTOP_IDENTIFIER . '-mst.' . $ext;
        $resizedWebpPath = $path . $resizedSuffix . Config::WEBP_SUFFIX;

        if ($this->mediaDir->isExist($resizedWebpPath)) {
            $webpUrl = str_replace($path, $resizedWebpPath, $imageUrl);
        } else {
            $webpUrl = str_replace($path, $webpPath, $imageUrl);
        }

        return '<source srcset="' . $webpUrl . $query . '" type="image/webp"' . $classes . '/>';
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function appendSwatcherFixScript($content)
    {
        $script = '
            <script>
                document.addEventListener("click", function(e) {
                    if (!e.target.classList.contains("swatch-option")) {
                        return;
                    }

                    productElement = e.target.closest(".product-item");

                    if (productElement !== null) {
                        imgSource = productElement.querySelector("source");

                        if (imgSource !== null) {
                            imgSource.srcset = "";
                        }
                    }
                })
            </script>';

        return str_replace('</body>', $script . '</body>', $content);
    }
}
