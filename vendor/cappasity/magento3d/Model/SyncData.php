<?php

namespace CappasityTech\Magento3D\Model;

use CappasityTech\Magento3D\Helper\Data as CappasityHelper;

class SyncData extends \Magento\Framework\Model\AbstractModel implements SyncDataInterface
{
    const RESOURCE_SYNCHRONIZATION = 'sync';
    const BASE_IFRAME_URL = 'https://api.cappasity.com/api/player/';
    const DEFAULT_THUMBNAIL = 'images/thumbnail-3D.png';
    const BASE_THUMBNAIL_URL = 'https://api.cappasity.com/api/files/preview/';

    private $productFactory;
    private $productAction;
    private $productRepository;
    private $productProcessor;
    private $productGallery;
    private $directoryList;
    private $assetRepository;
    private $cappasityHelper;
    private $cappasityTechSyncJob;
    private $file;
    private $cacheManager;

    protected function _construct()
    {
        $this->_init(\CappasityTech\Magento3D\Model\ResourceModel\SyncData::class);
    }

    public function __construct(
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Product\Action $productAction,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\Product\Gallery\Processor $productProcessor,
        \Magento\Catalog\Model\ResourceModel\Product\Gallery $productGallery,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \CappasityTech\Magento3D\Model\SyncJobFactory $cappasityTechSyncJob,
        \CappasityTech\Magento3D\Helper\Data $cappasityHelper,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->file = $file;
        $this->productFactory = $productFactory;
        $this->productAction = $productAction;
        $this->productRepository = $productRepository;
        $this->productProcessor = $productProcessor;
        $this->productGallery = $productGallery;
        $this->directoryList = $directoryList;
        $this->assetRepository = $assetRepository;
        $this->cappasityHelper = $cappasityHelper;
        $this->cappasityTechSyncJob = $cappasityTechSyncJob;
        $this->cacheManager = $cacheManager;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_construct();
    }

    public function getLast3dModels($count = 1)
    {
        return $this->getCollection()->setOrder('updated_at', 'DESC')->setPageSize($count)->setPage(1);
    }

    protected function replaceDataImage($data)
    {
        //TODO: after change
        return $data;
    }

    public function getProductImage3D($productId)
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->create(self::class)->load($productId, SyncDataInterface::DATA_PRODUCT_ID);
    }

    public function saveResultData($data, $jobId)
    {
        $items = $this->replaceDataImage($data);
        $cappasityHelper = CappasityHelper::getInstance();
        $currentDate = $cappasityHelper->getCurrentDate();

        foreach ($items as $item) {
//            try {
                $image3D = $item['uploadId'];
                $productId = (int)$item['id'];

                if (!$image3D) {
                    if (isset($item['capp']) && $item['capp']) {
                        $imageObject = $this->getProductImage3D($productId);
                        $imageObject->delete();
                    }
                    continue;
                }

                $imageObject = $this->getProductImage3D($productId);

                if (!$imageObject->getId()) {
                    $imageObject->setCreatedAt(strtotime($currentDate));
                    $imageObject->setProductId($productId);
                }
                $imageObject->setUpdatedAt(strtotime($currentDate));
                $imageObject->setSku($item['sku']);
                $imageObject->setImage3d($image3D);

                if (strpos($jobId, 'custom') === false) {
                    $imageObject->setOrigImage3d($image3D);
                }

                $image3DUrl = $imageObject->getImage3dUrl();
                $imageObject->setUrl3d($image3DUrl);
                $imageObject->setJobId($jobId);
                $imageObject->setSyncResource(self::RESOURCE_SYNCHRONIZATION);

                $imageObject->setData('thumbnail_3d', $cappasityHelper->generatePreviewImageSrc($image3D));
                $imageObject->setData(
                    'small_thumbnail_3d',
                    $cappasityHelper->generatePreviewImageSrc($image3D, 'small')
                );
                $imageObject->setData(
                    'medium_thumbnail_3d',
                    $cappasityHelper->generatePreviewImageSrc($image3D, 'medium')
                );

                $useThumbnail3d = 'global';
                if (isset($item['use_thumbnail_3d'])) {
                    $useThumbnail3d = $item['use_thumbnail_3d'];
                }
                $imageObject->setData('use_thumbnail_3d', $useThumbnail3d);

                //Add Image To Product
                if ($cappasityHelper->getSyncConfigValue('add_preview_to_gallery')) {
                    $this->addImageToProduct(
                        $productId,
                        $imageObject,
                        $cappasityHelper->getSyncConfigValue('set_preview_base')
                    );
                }

                $imageObject->save();
//            } catch (\Exception $e) {
//                return false;
//            }
        }
        return true;
    }

    private function addImageToProduct($productId, $imageObject, $setPreviewImageAsBase)
    {

        $prefix = 'cappasity_tech_image_';
        $imageName = $prefix . $imageObject->getImage3d();
        $url = $imageObject->getData('thumbnail_3d');
        /**
         * Download and save image
         */
        $root = $this->directoryList->getPath('media');
        $bufferPath = $root . "/tmp/catalog/product/";
        if (!is_dir($bufferPath)) {
            mkdir($bufferPath, 0777, true);
        }

        $absoluteMediaPath = $root . "/catalog/product";
        $newImage = $bufferPath . $imageName . ".jpg";
        $this->file->write($newImage, $this->file->read($url));

        $imageExists = false;
        $imageKey = null;
        $oldImageExists = false;
        $oldImageId = 0;
        $oldImageKey = null;

        $product = $this->productFactory->create()->load($productId);
        $existingMediaGalleryEntries = $product->getMediaGalleryEntries();
        foreach ($existingMediaGalleryEntries as $key => $img) {
            if (strpos($img->getFile(), $prefix) !== false) {
                if (strpos($img->getFile(), $imageName) !== false) {
                    $imageKey = $key;
                    $imageExists = true;
                    continue;
                }

                $oldImageId = $img->getId();
                $oldImageKey = $key;
                $oldImageExists = true;
            }
        }

        $types = ["small_image", "thumbnail", 'image'];

        if (!$setPreviewImageAsBase) {
            $types = [];
        }
        if (!$imageExists) {
            $product->addImageToMediaGallery($newImage, $types, false, false);
            $product->save();
            $imageExists = true;
            $existingMediaGalleryEntries = $product->getMediaGalleryEntries();
            $imageKey = count($existingMediaGalleryEntries) - 1;
        }

        $storeIds = $product->getStoreIds();
        array_push($storeIds, 0);

        if ($imageExists || $oldImageExists) {
            foreach ($storeIds as $storeId) {
                if ($imageExists && $setPreviewImageAsBase) {
                    $this->productAction->updateAttributes(
                        [$productId],
                        array_fill_keys($types, $existingMediaGalleryEntries[$imageKey]->getFile()),
                        $storeId
                    );
                }

                /**
                 * Remove old images
                 */
                if ($oldImageExists) {
                    $oldImagePath = $existingMediaGalleryEntries[$oldImageKey]->getFile();
                    $oldBufferImage = $bufferPath . stristr($oldImagePath, $prefix);
                    // From Pub
                    if ($this->file->fileExists($oldBufferImage)) {
                        unlink($oldBufferImage);
                    }
                    $oldPubImage = $absoluteMediaPath . $oldImagePath;
                    if ($this->file->fileExists($oldPubImage)) {
                        unlink($oldPubImage);
                    }
                    // From Product
                    $this->productProcessor->removeImage($product, $oldImagePath);
                    $this->productGallery->deleteGallery($oldImageId);
                }
            }
        }

        if ($oldImageExists) {
            unset($existingMediaGalleryEntries[$oldImageKey]);
            $product->setMediaGalleryEntries($existingMediaGalleryEntries);
            $this->productRepository->save($product);
        }

        return true;
    }

    public function getImage3d()
    {
        $image3d = $this->getData('image_3d');
        if (!$image3d) {
            $image3d = $this->getData('orig_image_3d');
        }
        return $image3d;
    }

    public function getImage3dUrl()
    {
        $image3DUrl = '';
        if ($this->getImage3d()) {
            preg_match_all('/<iframe[^>]+src="([^"]+)"/', $this->getImage3dIframe(), $match);
            if (isset($match[1][0])) {
                $image3DUrl = $match[1][0];
            };
        }
        return $image3DUrl;
    }

    public function getImage3dIframe()
    {
        if ($this->getImage3d()) {
            return CappasityHelper::getInstance()->renderImage3d($this->getImage3d());
        }
        return '';
    }

    public function getThumbnailUrl()
    {
        if ($this->getData('use_thumbnail_3d') == 'global') {
            if ($this->cappasityHelper->getSyncConfigValue('use_thumbnail_of_button')) {
                return $this->getMediumThumbnail3d();
            } else {
                return $this->assetRepository->createAsset(
                    'CappasityTech_Magento3D::' . $this->cappasityHelper->getBaseThumbnail()
                )->getUrl();
            }
        } elseif ($this->getData('use_thumbnail_3d') == 'button3d') {
            return $this->assetRepository->createAsset(
                'CappasityTech_Magento3D::' . $this->cappasityHelper->getBaseThumbnail()
            )->getUrl();
        } else {
            return $this->getMediumThumbnail3d();
        }
    }

    public function getOrigMediumThumbnailUrl()
    {
        if ($image3d = $this->getData('orig_image_3d')) {
            return CappasityHelper::getInstance()->generatePreviewImageSrc($image3d, 'medium');
        }
        return '';
    }

    public function getOrigThumbnailUrl()
    {
        if ($image3d = $this->getData('orig_image_3d')) {
            return CappasityHelper::getInstance()->generatePreviewImageSrc($image3d);
        }
        return '';
    }

    private function setStatusJobSync($job, $data)
    {
        $model = $job->setUpdateAt($this->cappasityHelper->getCurrentDate());
        if ($this->saveJobResult($data, $job->getJobId())) {
            $model->setStatus(2)->save();
        } else {
            $model->setStatus(3)->save();
            return false;
        }
        return true;
    }

    public function getJobResults($jobId = '')
    {
        $user = $this->cappasityHelper->getActiveUser();
        $token = $user->getToken();

        if (!$user || !$token) {
            throw new \CappasityTech\Magento3D\Model\Exceptions\ValidationTokenException(__('Please input a correct token.'));
        }

        $client = \CappasitySDK\ClientFactory::getClientInstance(['apiToken' => $token]);

        $jobs = $this->cappasityTechSyncJob->create()->getCollection()->addFieldToFilter('status', '1');
        if ($jobId) {
            $jobs->addFieldToFilter('job_id', $jobId);
        }
        if (count($jobs)) {
            foreach ($jobs as $job) {
                $jobResult = [];
                $jobId = $job->getJobId();
                $items = json_decode($job->getItems(), true);

                foreach ($items as $item) {
                    if (!isset($item['capp']) || !$item['capp']) {
                        continue;
                    }
                    $tmp = $item;
                    $tmp['uploadId'] = $tmp['capp'];
                    $tmp['capp'] = '';
                    $tmp['sku'] = $tmp['aliases'][0];
                    $jobResult[$item['id']] = $tmp;
                }

                $response = $client
                    ->getPullJobResult(\CappasitySDK\Client\Model\Request\Process\JobsPullResultGet::fromData($jobId))
                    ->getBodyData();

                if ($response->getData()) {
                    foreach ($response->getData() as $jobItemResult) {
                        $jobResult[$jobItemResult->getId()] = [
                            'id' => $jobItemResult->getId(),
                            'uploadId' => $jobItemResult->getUploadId(),
                            'sku' => $jobItemResult->getSku(),
                            'capp' => $jobItemResult->getCapp()
                        ];
                    }
                }

                $this->setStatusJobSync($job, $jobResult);
            }
            $this->cacheManager->clean(['full_page']);
        }

        return true;
    }

    private function saveJobResult($data, $jobId)
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->create(self::class)->saveResultData($data, $jobId);
    }

    public function inProgress()
    {
        $jobs = $this->cappasityTechSyncJob->create()->getCollection()->addFieldToFilter('status', '1');
        if (count($jobs)) {
            return true;
        }
        return false;
    }
}
