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
use Mirasvit\Core\Service\SerializeService;
use Mirasvit\Optimize\Api\Processor\OutputProcessorInterface;
use Mirasvit\OptimizeImage\Model\Config;
use Mirasvit\OptimizeImage\Repository\FileRepository;
use Mirasvit\OptimizeImage\Service\FileListSynchronizationService;

class FotoramaProcessor implements OutputProcessorInterface
{
    private $config;

    private $fileRepository;

    private $syncService;

    private $mediaUrl;

    private $mediaDir;

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
        if(!$this->config->isUseWebpForFotorama()) {
            return $content;
        }

        $content = preg_replace_callback(
            '/<script type="text\/x-magento-init">([^<]*?magnifier\/magnify.*?)<\/script>/ims',
            [$this, 'replaceCallback'],
            $content
        );

        $content = preg_replace_callback(
            '/<div[^>]*class=["\']notorama[^"\']*["\'][^>]*data-mage-init=[\']([^\']*)[\'][^>]*>/ims',
            [$this, 'replaceNotoramaCallback'],
            $content
        );

        return $content;
    }

    /**
     * @param array $match
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     */
    private function replaceCallback(array $match)
    {
        $imgKeys = ['thumb', 'img', 'full'];
        $config  = SerializeService::decode($match[1]);
        $widgetConfig = $config["[data-gallery-role=gallery-placeholder]"];

        $dataKey = array_keys($widgetConfig)[0];

        if (!isset($widgetConfig[$dataKey]["data"])) {
            return $match[0];
        }

        $data = $widgetConfig[$dataKey]["data"];

        foreach ($data as $idx => $imageConfig) {
            if ($imageConfig["type"] !== 'image') {
                continue;
            }

            foreach ($imageConfig as $key => $value) {
                if(!in_array($key, $imgKeys) || strpos($value, '.webp') !== false) {
                    continue;
                }

                preg_match('/\?.*/is', $value, $query);

                $query = count($query) ? $query[0] : '';
                $value = str_replace($query, '', $value);

                $absolutePath = $this->config->retrieveImageAbsPath($value);

                if (!$absolutePath) {
                    continue;
                }

                $image = $this->syncService->ensureFile($absolutePath);

                if($image && $image->getWebpPath()) {
                    $path     = str_replace($this->mediaUrl, '', $value);
                    $webpPath = $path . Config::WEBP_SUFFIX;

                    if (!$this->mediaDir->isExist($webpPath)) {
                        $image->setWebpPath(null);
                        $this->fileRepository->save($image);
                        continue;
                    }

                    $webpUrl = str_replace($path, $webpPath, $value);

                    $imageConfig[$key] = $webpUrl . $query;
                }
            }

            $data[$idx] = $imageConfig;
        }

        $config["[data-gallery-role=gallery-placeholder]"][$dataKey]["data"] = $data;

        $serializedKey = str_replace('/', '\/', $dataKey);

        $script = SerializeService::encode($config);
        $script = str_replace($serializedKey, $dataKey, $script);
        $script = str_replace('magnifier\/magnify', 'magnifier/magnify', $script);

        return '<script type="text/x-magento-init">' . $script . '</script>';
    }

    /**
     * @param array $match
     * 
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     */
    private function replaceNotoramaCallback(array $match)
    {
        $imgKeys = ['thumb', 'img', 'full'];
        $config  = SerializeService::decode($match[1]);
        $imagesConfig = SerializeService::decode($config['notorama']['initialImages']);

        foreach ($imagesConfig as $idx => $imgConfig) {
            if (!isset($imgConfig['type']) || $imgConfig['type'] !== 'image') {
                continue;
            }

            foreach ($imgConfig as $key => $value) {
                if(!in_array($key, $imgKeys) || strpos($value, '.webp') !== false) {
                    continue;
                }

                preg_match('/\?.*/is', $value, $query);

                $query = count($query) ? $query[0] : '';
                $value = str_replace($query, '', $value);

                $absolutePath = $this->config->retrieveImageAbsPath($value);

                if (!$absolutePath) {
                    continue;
                }

                $image = $this->syncService->ensureFile($absolutePath);

                if($image && $image->getWebpPath()) {
                    $path     = str_replace($this->mediaUrl, '', $value);
                    $webpPath = $path . Config::WEBP_SUFFIX;

                    if (!$this->mediaDir->isExist($webpPath)) {
                        $image->setWebpPath(null);
                        $this->fileRepository->save($image);
                        continue;
                    }

                    $webpUrl = str_replace($path, $webpPath, $value);

                    $imgConfig[$key] = $webpUrl . $query;
                }
            }

            $imagesConfig[$idx] = $imgConfig;
        }

        $config['notorama']['initialImages'] = SerializeService::encode($imagesConfig);

        $config = SerializeService::encode($config);

        $content = str_replace($match[1], $config, $match[0]);

        return $content;
    }
}
