<?php

namespace CappasityTech\Magento3D\Model;

class SyncJob extends \Magento\Framework\Model\AbstractModel implements SyncJobInterface
{
    private $assetRepository;
    private $сappasityHelper;

    protected function _construct()
    {
        $this->_init(\CappasityTech\Magento3D\Model\ResourceModel\SyncJob::class);
    }

    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \CappasityTech\Magento3D\Helper\Data $сappasityHelper,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->assetRepository = $assetRepository;
        $this->сappasityHelper = $сappasityHelper;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_construct();
    }

    public function loadActiveSyncJob($activeUser)
    {
        if (!$activeUser->getEntityId()) {
            return false;
        }
        $collection = $this->getCollection();
        $collection->addFieldToFilter(SyncJobInterface::DATA_USER_ID, $activeUser->getEntityId());
        $collection->addFieldToFilter(SyncJobInterface::DATA_STATUS, 1);
        if ($collection->getSize() > 0) {
            return $collection->getItems();
        }
        return false;
    }

    public function createJob($products)
    {
        $user = $this->сappasityHelper->getActiveUser();
        $token = $user->getToken();
        if (!$user || !$token) {
            throw new \CappasityTech\Magento3D\Model\Exceptions\ValidationTokenException(
                __('Please input a correct token.')
            );
        }
        $client = \CappasitySDK\ClientFactory::getClientInstance(['apiToken' => $token]);

        $items = [];
        foreach ($products as $item) {
            $tmp = [
                'id' => $item->getId(),
                'aliases' => [$item->getSku()],
            ];
            $capp = $item->getCapp();
            if ($capp) {
                $tmp['capp'] = $capp;
            }

            $items[] = $tmp;
        }

        $response = $client
            ->registerSyncJob(\CappasitySDK\Client\Model\Request\Process\JobsRegisterSyncPost::fromData(
                $items,
                'pull'
            ))
            ->getBodyData();
        $jobId = $response->getData()->getId();
        $this->saveData($jobId, $user->getEntityId(), $items);

        return $jobId;
    }

    protected function saveData($jobId, $userId, $items)
    {
        $currentDate = $this->сappasityHelper->getCurrentDate();
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->create(self::class)
            ->setJobId($jobId)
            ->setUserId($userId)
            ->setStatus(1)
            ->setItems(json_encode($items))
            ->setCreatedAt($currentDate)
            ->save();
    }
}
