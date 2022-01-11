<?php

namespace CappasityTech\Magento3D\Controller\Adminhtml\Index;

use CappasitySDK\ClientFactory;
use CappasitySDK\Client\Model\Request;
use CappasitySDK\Client\Model\Response;

class SyncData extends \Magento\Backend\App\Action
{
    private $syncFactory;
    private $dataHelper;
    private $json;

    /**
     * SyncJobData constructor.
     * @param \CappasityTech\Magento3D\Model\SyncFactory $syncFactory
     * @param \Magento\Framework\Controller\Result\Json $json
     * @param \CappasityTech\Magento3D\Helper\Data $helper
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \CappasityTech\Magento3D\Model\SyncFactory $syncFactory,
        \Magento\Framework\Controller\Result\Json $json,
        \CappasityTech\Magento3D\Helper\Data $dataHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->syncFactory = $syncFactory;
        $this->dataHelper = $dataHelper;
        $this->json = $json;
    }

    public function execute()
    {
        session_write_close();
        try {
            $status = $this->getRequest()->getParam('status', false);
            if (!$status) {
                $this->syncFactory->create()->createJobs();
                $this->syncFactory->create()->getJobResults();
                $inProgress = true;
            } else {
                $inProgress = $this->syncFactory->create()->inProgress();
            }
        } catch (\CappasitySDK\Client\Exception\RequestException $e) {
            $message = \CappasityTech\Magento3D\Model\Exceptions\CappasityRequestException::getMessage($e);
            return $this->json->setData(['type' => 'error', 'text' => $message, 'is_last' => true]);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->json->setData(['type' => 'error', 'text' => $message, 'is_last' => true]);
        }
        return $this->json->setData(['type' => 'success', 'in_progress' => $inProgress]);
    }
}
