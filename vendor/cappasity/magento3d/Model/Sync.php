<?php

namespace CappasityTech\Magento3D\Model;

class Sync
{
    private $dataHelper;
    private $syncJob;
    private $syncData;
    private $configInterface;
    private $resource;

    public function __construct(
        \CappasityTech\Magento3D\Helper\Data $dataHelper,
        \CappasityTech\Magento3D\Model\SyncJob $syncJob,
        \CappasityTech\Magento3D\Model\SyncData $syncData,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->dataHelper = $dataHelper;
        $this->syncJob = $syncJob;
        $this->syncData = $syncData;
        $this->configInterface = $configInterface;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
    }

    public function cronCreateJobs()
    {
        $select = $this->connection->select()
            ->from(
                ['ccd' => $this->resource->getTableName('core_config_data')],
                ['value']
            )->where('path = "cappasitytech/general/last_sent_product" AND scope = "default" AND scope_id = "0"');
        $lastProductId = $this->connection->fetchOne($select);

        if ($lastProductId) {
            $this->createJobs([], $lastProductId);
        }
    }

    public function createOneJob($produсtId)
    {
        $products = $this->dataHelper->getProductsCollection([$produсtId]);
        if (count($products)) {
            $sendProducts = [$products->getFirstItem()];
            return $this->createJob($products, $sendProducts);
        }
        return false;
    }

    public function createJobs($produсtIds = [], $fromProductId = false)
    {
        $products = $this->dataHelper->getProductsCollection($produсtIds, $fromProductId);
        if (count($products)) {
            $sendProducts = [];
            foreach ($products as $product) {
                $sendProducts[] = $product;
                if (count($sendProducts) >= 499) {
                    $this->createJob($products, $sendProducts);
                    $sendProducts = [];
                }
            }
        }
        if (isset($sendProducts) && count($sendProducts) > 0) {
            $this->createJob($products, $sendProducts);
        }
        return true;
    }

    private function createJob($products, $sendProducts)
    {
        $lastProduct = $sendProducts[count($sendProducts) - 1];

        if ($products->getLastItem()->getId() == $sendProducts[count($sendProducts) - 1]->getId()) {
            $this->configInterface->saveConfig('cappasitytech/general/last_sent_product', '', 'default', 0);
        } else {
            $this->configInterface->saveConfig(
                'cappasitytech/general/last_sent_product',
                $lastProduct->getId(),
                'default',
                0
            );
        }

        return $this->syncJob->createJob($sendProducts);
    }

    public function getJobResults($jobId = '')
    {
        $this->syncData->getJobResults($jobId);
    }

    public function inProgress()
    {
        return $this->syncData->inProgress();
    }
}
