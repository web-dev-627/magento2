<?php

namespace CappasityTech\Magento3D\Controller\Adminhtml\Index;

use CappasityTech\Magento3D\Model\DataInterface;

class SettingSave extends \Magento\Backend\App\Action
{
    private $coreRegistry;
    private $settingFactory;
    private $paymentPlanFactory;
    private $cappasityHelper;
    protected $messageManager;
    private $store;
    private $jobParamsFactory;
    private $imageParamsFactory;
    private $paramsRuleFactory;
    private $resultPageFactory;

    public function __construct(
        \CappasityTech\Magento3D\Helper\Data $cappasityHelper,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \CappasityTech\Magento3D\Model\ParamsRuleFactory $cappasityTechParamsRuleFactory,
        \CappasityTech\Magento3D\Model\SyncJobParamsFactory $cappasityTechSyncJobParamsFactory,
        \CappasityTech\Magento3D\Model\ImageParamsFactory $cappasityTechImageParamsFactory,
        \CappasityTech\Magento3D\Model\PaymentPlanFactory $paymentPlanFactory,
        \CappasityTech\Magento3D\Model\SettingFactory $settingFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->settingFactory = $settingFactory;
        $this->paramsRuleFactory = $cappasityTechParamsRuleFactory;
        $this->jobParamsFactory = $cappasityTechSyncJobParamsFactory;
        $this->imageParamsFactory = $cappasityTechImageParamsFactory;
        $this->paymentPlanFactory = $paymentPlanFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->messageManager = $messageManager;
        $this->cappasityHelper = $cappasityHelper;
        $this->store = $storeManager;
    }

    public function execute()
    {
        try {
            $token = $this->getRequest()->getParam('token', false);

            $currentUser = false;
            $currentDate = $this->cappasityHelper->getCurrentDate();

            $response = \CappasitySDK\ClientFactory::getClientInstance(['apiToken' => $token])
                ->getUser(new \CappasitySDK\Client\Model\Request\Users\MeGet())
                ->getBodyData();
            $user = $response
                ->getData()
                ->getAttributes();

            $userAlias = $user->getAlias();
            $userPlan = $user->getPlan();

            $model = $this->settingFactory->create();
            $activeUser = $model->loadActiveUser();
            if ($activeUser) {
                $activeUser = $model->load($activeUser->getEntityId());
                if (trim($activeUser->getAliases()) !== $userAlias) {
                    //deactivate old user
                    $activeUser->setData('status', 0);
                    $activeUser->save();
                } else {
                    $currentUser = $activeUser;
                    $currentUser->setData('token', $token);
                    $currentUser->setData('update_at', $currentDate);
                }
            }

            if (!$currentUser) {
                $currentUserId = $model->loadCurrentUser($userAlias);
                if ($currentUserId) {
                    $currentUser = $model->load($currentUserId);
                    $oldPlan = $currentUser->getPlan();
                    if ($oldPlan !== $userPlan) {
                        $this->messageManager->addSuccess(__('Your subscription plan was changed. Current plan:')
                            . __(strtoupper($this->cappasityHelper->getCurrentPlanAlias())));
                    }
                    $currentUser->setData(DataInterface::DATA_UPDATE_AT, $currentDate);
                } else {
                    $currentUser = $this->settingFactory->create();
                    $currentUser->setData(DataInterface::DATA_CREATE_AT, $currentDate);
                    $currentUser->setData(DataInterface::DATA_ALIASES, $userAlias);
                }
                $currentUser->setData(DataInterface::DATA_TOKEN, $token);
                $currentUser->setData(DataInterface::DATA_STATUS, 1);
            }

            $currentUser->setData(DataInterface::DATA_PLAN, $userPlan);
            $currentUser->save();

            $this->cappasityHelper->resetData();

            $this->setUserParamsJob($currentUser);
            $this->setUserParamsImage($currentUser);

            $this->coreRegistry->register('CappasityTechUserId', $currentUser->getEntityId());
            if ($this->cappasityHelper->getCurrentPlanLevel() < '30') {
                $this->messageManager->addNotice(__('Please upgrade your subscription plan'));
            }
        } catch (\CappasitySDK\Client\Exception\RequestException $e) {
            $message = \CappasityTech\Magento3D\Model\Exceptions\CappasityRequestException::getMessage($e);
            $this->messageManager->addError(__($message));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect('cappasity/index/action');
    }

    private function setUserParamsJob($user)
    {
        $userId = $user->getEntityId();
        $params = $this->jobParamsFactory->create()->load((int)$userId, "user_id");

        if (!$params->getEntityId()) {
            $params = $this->jobParamsFactory->create();
            $params->setEntityId($userId);
        }
        $defaultParams = $this->cappasityHelper->getDefaultSaveJobParams();
        $params->setUserId((int)$userId);

        foreach ($defaultParams as $key => $value) {
            $params->setData(trim($key), (int)$value);
        }
        $params->save();
    }

    private function setUserParamsImage($user)
    {
        $plan = $this->cappasityHelper->getCurrentPlanLevel();
        $userId = $user->getEntityId();
        $defaultParams = $this->getImageParamsByPlan($plan);

        $params = $this->imageParamsFactory->create()->load((int)$userId);
        if (!$params->getEntityId()) {
            $params = $this->imageParamsFactory->create();
            $params->setEntityId($userId);
        }

        foreach ($defaultParams as $key => $value) {
            $params->setData(trim($key), (int)$value);
        }

        $params->setEntityId($userId);

        $params->save();
    }

    private function getImageParamsByPlan($currentPlan)
    {
        $result = [];
        $rules = $this->paramsRuleFactory->create()->getCollection();
        foreach ($rules as $rule) {
            $access = false;
            if ($rule->getPaid()) {
                if ($currentPlan >= $rule->getRegPlanLevel()) {
                    $access = true;
                }
            } else {
                $access = true;
            }
            if ($access) {
                $v = $rule->getDefaultValue();

                if ($v == "true") {
                    $v = 1;
                }
                if ($v == "false") {
                    $v = 0;
                }

                $result[trim($rule->getName())] = $v;
            }
        }
        return $result;
    }
}
