<?php

namespace CappasityTech\Magento3D\Controller\Adminhtml\Index;

use \Magento\Framework\Controller\ResultFactory;

class SavePlayerSettings extends \Magento\Backend\App\Action
{
    private $cappasityParamsRuleFactory;
    private $helper;
    private $cacheManager;

    public function __construct(
        \CappasityTech\Magento3D\Model\ParamsRuleFactory $cappasityParamsRuleFactory,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Backend\App\Action\Context $context,
        \CappasityTech\Magento3D\Helper\Data $helper
    ) {
        $this->cappasityParamsRuleFactory = $cappasityParamsRuleFactory;
        $this->helper = $helper;
        $this->cacheManager = $cacheManager;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

            $postData = $this->getRequest()->getPostValue();

            $timeFields = ['autoRotateTime' => [10, 120], 'autoRotateDelay' => [2, 30]];
            foreach ($timeFields as $field => $between) {
                if (!(is_numeric($postData[$field])
                    && $postData[$field] >= $between[0]
                    && $postData[$field] <= $between[1])) {
                    throw new \Exception(__('Wrong time settings'));
                }
            }
            if (!$postData) {
                throw new \CappasityTech\Magento3D\Model\Exceptions\DataNotFoundException(__('Saving failed. Data is empty'));
            }

            $statuses = $this->helper->getStatusesByPlanLevel();

            $this->setValueItemsByStatus($postData, $statuses);

            $this->cacheManager->clean(['full_page']);

            $responseData = ['status' => 'success', 'message' => __('Data was saved successfully')];
        } catch (\Exception $e) {
            $responseData = ['status' => 'error', 'message' => __('Unable to save player setting') . $e->getMessage()];
        }
        return $resultJson->setData($responseData);
    }

    private function setValueItemsByStatus($postData, $statuses)
    {
        $settings = $this->cappasityParamsRuleFactory->create()->getCollection();
        foreach ($postData as $name => $value) {
            if (array_key_exists($name, $statuses)) {
                foreach ($settings as $item) {
                    if ($item->getName() == $name) {
                        if ($statuses[$name] == 'disabled') {
                            $value = $item->getDefaultValue();
                        }
                        $item->setValue($value)->save();
                    }
                }
            }
        }
    }
}
