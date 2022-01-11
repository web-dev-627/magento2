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



namespace Mirasvit\OptimizeImage\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Core\Service\SerializeService;
use Mirasvit\OptimizeImage\Repository\FileRepository;
use Mirasvit\OptimizeImage\Service\ResponsiveImageService;

class Config
{
    const CMD_CONVERT_RGB      = 'convert -colorspace RGB "%s" "%s"';
    const CMD_PROCESS_WEBP     = 'cwebp -q %s \'%s\' -o \'%s\'';
    const CMD_PROCESS_GIF2WEBP = 'gif2webp -q %s \'%s\' -o \'%s\'';
    const CMD_PROCESS_PNG      = 'optipng \'%s\'';
    const CMD_PROCESS_GIF      = 'gifsicle \'%s\' -o \'%s\'';
    const CMD_PROCESS_JPG      = 'jpegoptim --all-progressive --strip-xmp --strip-com --strip-exif --strip-iptc \'%s\'';

    const WEBP_SUFFIX    = '.mst.webp';
    const CONVERT_SUFFIX = '.mst.conv';
    const BACKUP_SUFFIX  = "_mst_ORIG";
    const TMP_SUFFIX     = "_mst_TMP";

    const STRATEGY_FILESYSTEM = 'file';
    const STRATEGY_WEBPAGES   = 'web';

    private $fs;

    private $scopeConfig;

    private $request;

    private $storeManager;

    private $fileRepository;

    public function __construct(
        Filesystem $fs,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        FileRepository $fileRepository
    ) {
        $this->fs             = $fs;
        $this->storeManager   = $storeManager;
        $this->scopeConfig    = $scopeConfig;
        $this->request        = $request;
        $this->fileRepository = $fileRepository;
    }

    /**
     * @return bool
     */
    public function isWebpEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            'mst_optimize/optimize_image/is_webp',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param string $extension
     *
     * @return bool
     */
    public function isAllowedFileExtension($extension)
    {
        return in_array($extension, ['png', 'gif', 'jpg', 'jpeg']);
    }

    /**
     * @return bool
     */
    public function isLazyEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            'mst_optimize/optimize_image/image_lazy_load/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param string $relativePath
     *
     * @return string
     */
    public function getAbsolutePath($relativePath)
    {
        $abs = $this->fs->getDirectoryRead(DirectoryList::ROOT)->getAbsolutePath();
        $abs .= $relativePath;

        if (!file_exists($abs)) {
            $this->deleteRemovedFile($abs);
        }

        return $abs;
    }

    /**
     * @param string $absolutePath
     *
     * @return string
     */
    public function getRelativePath($absolutePath)
    {
        $abs = $this->fs->getDirectoryRead(DirectoryList::ROOT)->getAbsolutePath();

        return str_replace($abs, '', $absolutePath);
    }

    /**
     * @param string $url
     *
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function retrieveImageAbsPath($url)
    {
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $mediaDir = $this->fs->getDirectoryread(DirectoryList::MEDIA);

        if (strpos($url, $mediaUrl) === false) {
            return false;
        }

        $path = str_replace($mediaUrl, '', $url);

        if (!$mediaDir->isExist($path)) {
            $this->deleteRemovedFile($mediaDir->getAbsolutePath($path));
            return false;
        }

        return $mediaDir->getAbsolutePath($path);
    }

    /**
     * @param string $absolutePath
     */
    private function deleteRemovedFile($absolutePath)
    {
        $relativePath = $this->getRelativePath($absolutePath);
        $file = $this->fileRepository->getByRelativePath($relativePath);

        if (!$file) {
            return;
        }

        $this->fileRepository->delete($file);
    }

    /**
     * @param string $absolutePath
     *
     * @return string
     */
    public function getFileExtension($absolutePath)
    {
        $pathInfo = pathinfo($absolutePath);

        return isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : '';
    }

    /**
     * @param string $img
     *
     * @return bool
     */
    public function isWebpException($img)
    {
        if (strpos($img, 'lazyOwl') !== false
            || strpos($img, 'owl-lazy') !== false
            || strpos($img, 'swiper-lazy') !== false
            || strpos($img, 'mst-no-webp') !== false
            || strpos($img, 'loader.gif') !== false
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function getCompressionLevel()
    {
        $compression = $this->scopeConfig->getValue('mst_optimize/optimize_image/compression');

        return $compression ?: 100;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->request->getParam('debug') == 'optimize-image';
    }

    /**
     * @return string
     */
    public function getStrategy()
    {
        return $this->scopeConfig->getValue('mst_optimize/optimize_image/strategy') ?: self::STRATEGY_FILESYSTEM;
    }

    /**
     * @return bool
     */
    public function isFilesystemStrategy()
    {
        return $this->getStrategy() == self::STRATEGY_FILESYSTEM;
    }

    /**
     * @return bool
     */
    public function isWebpFotoramaEnabled()
    {
        return $this->isWebpEnabled() && (bool)$this->scopeConfig->getValue(
            'mst_optimize/optimize_image/is_webp_fotorama',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isUseWebpForFotorama()
    {
        $httpAcceptHeader = $this->request->getHeader('Accept');

        $isBrowserSupportWebp = $httpAcceptHeader && strpos($httpAcceptHeader, 'image/webp') !== false;

        $isSupportWebp = $isBrowserSupportWebp || $this->isUserAgentSupportWebp($this->request->getHeader('User-Agent'));

        return $isSupportWebp && $this->isWebpFotoramaEnabled();
    }

    /**
     * For properly set mst_webp vary data when page visited not in the browser (for example - CURL)
     *
     * @param string $userAgent
     *
     * @return bool
     */
    private function isUserAgentSupportWebp($userAgent)
    {
        return strpos($userAgent, ' Chrome/') !== false
            || strpos($userAgent, ' Opera/') !== false
            || strpos($userAgent, ' OPR/') !== false
            || strpos($userAgent, ' QQBrowser/') !== false
            || preg_match('/ Firefox\/(6[5-9]|[7-9]\d|1\d{2})/', $userAgent)
            || preg_match('/Macintosh.+Mac OS X (1[1-9]|2\d).+Version\/1[4-9]|2\d/', $userAgent)
            || preg_match('/ Edg\/(1[8-9]|[2-9]\d|\d{3})/', $userAgent)
            || preg_match('/ Android.* Version\/(4\.[2-9]|[5-9]\.|\d{2})/', $userAgent)
            || preg_match('/ Android.*(UC|Samsung)Browser/', $userAgent);
    }

    /**
     * @return array|false
     */
    public function getResponsiveImages()
    {
        $conf = $this->scopeConfig->getValue('mst_optimize/optimize_image/responsive/image');
        $conf = SerializeService::decode($conf);

        if (!is_array($conf) && is_object($conf)) {
            $conf = (array)$conf;
            foreach ($conf as $key => $value) {
                if (is_object($value)) {
                    $conf[$key] = (array)$value;
                }
            }
        }

        if (is_array($conf)) {
            foreach ($conf as $confKey => $confData) {
                if (
                    !$confData['file']
                    || !(int)$confData[ResponsiveImageService::MOBILE_IDENTIFIER]
                ) {
                    unset($conf[$confKey]);
                }
            }
        }

        return $conf;
    }

    /**
     * @param string $file
     * @return array|false
     */
    public function getResponsiveImageConfigByFileName($file)
    {
        $resposive = $this->getResponsiveImages();

        if(!is_array($resposive)) {
            return false;
        }

        foreach ($resposive as $image) {
            if(strpos($file, $image['file']) !== false) {
                return $image;
            }
        }

        return false;
    }
}
