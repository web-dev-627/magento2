<?php

namespace CappasityTech\Magento3D\Controller\Adminhtml\Index;

use \Magento\Framework\Controller\ResultFactory;

class SyncJobParamsSave extends \Magento\Backend\App\Action
{
    private $cappasityHelper;
    private $syncJobParamsFactory;

    public function __construct(
        \CappasityTech\Magento3D\Model\SyncJobParamsFactory $cappasityTechSyncJobParamsFactory,
        \CappasityTech\Magento3D\Helper\Data $cappasityHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->cappasityHelper = $cappasityHelper;
        $this->syncJobParamsFactory = $cappasityTechSyncJobParamsFactory;
    }

    public function execute()
    {
        try {
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
 
            $postData = $this->getRequest()->getPostValue();
            if (!$postData) {
                throw new \CappasityTech\Magento3D\Model\Exceptions\DataNotFoundException(__('Saving failed. Data is empty'));
            }
            $postData['set_preview_base'] = $postData['add_preview_to_gallery'] ? $postData['set_preview_base'] : 0;

            $modelJobParams = $this->syncJobParamsFactory->create();
            $activeUser = $this->cappasityHelper->getActiveUser();
            $params = $modelJobParams->loadByUserId($activeUser->getEntityId());

            if (!$params->getEntityId()) {
                $params = $this->syncJobParamsFactory->create();
                $params->setUserId($activeUser->getEntityId());
                $params->setEntityId($activeUser->getEntityId());
            }

            $notFree = $this->cappasityHelper->getPaidSettingCodes();
            if ($this->cappasityHelper->getCurrentPlanLevel() < '30') {
                $params->addData(array_fill_keys($notFree, 0));

                foreach ($notFree as $name) {
                    if (isset($postData[$name]) && $postData[$name]) {
                        $params->save();
                        throw new \Exception(__('Your payment plan doesn\'t allow to access the settings for images and thumbnails . Upgrade it now!'), 5);
                    }
                }
            }

            foreach ($postData as $key => $value) {
                $params->setData(trim($key), (int)$value);
            }
            $params->save();

            $responseData = ['status' => 'success', 'message' => __('Data was saved successfully')];
        } catch (\Exception $e) {
            $responseData = ['status' => 'error', 'message' => __('Error: ') . $e->getMessage(), 'code' => $e->getCode()];
        }
        return $resultJson->setData($responseData);
    }
}
