<?php

namespace CappasityTech\Magento3D\Model;

class Setting extends \Magento\Framework\Model\AbstractModel implements DataInterface
{
    const CACHE_TAG = 'cappasity_setting_cache';

    protected $_cacheTag = 'cappasity_setting_cache_tag';
    protected $_eventPrefix = 'cappasity_setting_event';

    private $_token = null;
    private $_user = null;

    protected function _construct()
    {
        $this->_init(\CappasityTech\Magento3D\Model\ResourceModel\Setting::class);
    }

    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }

    public function loadActiveUser()
    {
        if ($this->_user === null) {
            $collection = $this->getCollection()->addFieldToFilter(DataInterface::DATA_STATUS, 1);
            $collection->getSelect()->limit(1);
            if ($collection->getSize() > 0) {
                $this->_user = $collection->getFirstItem();
            }
        }
        return $this->_user;
    }

    public function loadCurrentUser($userAlias)
    {
        $collection = $this->getCollection()->addFieldToFilter(DataInterface::DATA_ALIASES, $userAlias);
        $collection->getSelect()->limit(1);
        if ($collection->getSize() > 0) {
            return $collection->getFirstItem()->getEntityId();
        }
        return false;
    }

    public function setToken($token)
    {
        return parent::setData(DataInterface::DATA_TOKEN, $token);
    }

    public function getToken()
    {
        if ($this->_token === null) {
            $this->_token = parent::getToken();
        }
        return $this->_token;
    }

    public function isTokenValid($token = null)
    {
        if ($token === null) {
            $token = parent::getToken();
            if (!$token) {
                return true;
            }
        }

        try {
            \CappasitySDK\ClientFactory::getClientInstance(['apiToken' => $token])
                ->getUser(new \CappasitySDK\Client\Model\Request\Users\MeGet())
                ->getBodyData();
        } catch (\CappasitySDK\Client\Exception\RequestException $e) {
            $message = \CappasityTech\Magento3D\Model\Exceptions\CappasityRequestException::getMessage($e);
            throw new \CappasityTech\Magento3D\Model\Exceptions\ValidationTokenException(__($message));
        } catch (\Exception $e) {
            throw new \CappasityTech\Magento3D\Model\Exceptions\ValidationTokenException(__('Invalid token'));
        }
        return true;
    }
}
