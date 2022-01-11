<?php

namespace CappasityTech\Magento3D\Observer\Product;

use Magento\Framework\Event\ObserverInterface;

class SaveAfter implements ObserverInterface
{
    private $sync;
    private $syncJobParams;
    private $dataHelper;
    private $request;
    private $registry;

    public function __construct(
        \CappasityTech\Magento3D\Model\Sync $sync,
        \CappasityTech\Magento3D\Model\SyncJobParams $syncJobParams,
        \CappasityTech\Magento3D\Helper\Data $dataHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $registry
    ) {
        $this->sync = $sync;
        $this->syncJobParams = $syncJobParams;
        $this->dataHelper = $dataHelper;
        $this->request = $request;
        $this->registry = $registry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $params = $this->request->getParams();
        $productId = $observer->getProduct()->getId();
        if (isset($params['uploadId'])
            || (($regCappProduct = $this->registry->registry('capp_update_product'))
                && $regCappProduct == $productId)) {
            return $this;
        }
        $this->registry->register('capp_update_product', $productId);

        $user = $this->dataHelper->getActiveUser();
        if ($user && $this->syncJobParams->loadByUserId($user->getId())->isAutoSyncProduct()) {
            $jobId = $this->sync->createOneJob([$productId]);
            $this->sync->getJobResults($jobId);
        }
        return $this;
    }
}
