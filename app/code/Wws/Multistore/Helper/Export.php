<?php

namespace Wws\Multistore\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use RuntimeException;

class Export
{

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var StoreFactory */
    private $storeFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * Export constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreFactory $storeFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(ScopeConfigInterface $scopeConfig, StoreFactory $storeFactory, StoreManagerInterface $storeManager)
    {
        $this->scopeConfig = $scopeConfig;
        $this->storeFactory = $storeFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Export the specified store codes to the specified file.
     *
     * @param string $filename
     * @param array $storeCodes
     */
    public function exportStores(string $filename, array $storeCodes)
    {
        $stores = [];

        foreach ($storeCodes as $storeCode) {
            try {
                $store = $this->storeManager->getStore($storeCode);
            } catch (NoSuchEntityException $e) {
                throw new RuntimeException("Requested store \"$storeCode\" does not exist.");
            }

            // Always ensure a / at the end of the url.
            $http = rtrim($this->scopeConfig->getValue('web/unsecure/base_url', ScopeInterface::SCOPE_STORES, $store->getCode()), '/') . '/';
            $https = rtrim($this->scopeConfig->getValue('web/secure/base_url', ScopeInterface::SCOPE_STORES, $store->getCode()), '/') . '/';
            $stores[$http] = $store->getCode();
            $stores[$https] = $store->getCode();
        }

        if (@file_put_contents($filename, json_encode($stores)) === false)
            throw new RuntimeException("Unable to write the store view configuration file at $filename.");

    }

    /**
     * Returns a list of all store codes registered in Magento.
     *
     * @return string[]
     */
    public function getAllStoreCodes()
    {
        return array_map(function (StoreInterface $store) {
            return $store->getCode();
        }, $this->storeManager->getStores());
    }

    /**
     * Returns a list of all store codes in the specified websites.
     *
     * @param string[] $websiteCodes
     * @return string[]
     */
    public function getStoreCodesFromWebsites(array $websiteCodes)
    {
        return array_unique(array_merge(...array_map(function (string $websiteCode) {
            try {
                $website = $this->storeManager->getWebsite($websiteCode);
                $storeCollection = $this->storeFactory->create()->getCollection();
                $storeCollection->addFieldToFilter('website_id', $website->getId());
                $storeCollection->addFieldToSelect('code');
                return array_map(function ($data) {
                    return $data['code'];
                }, $storeCollection->getData());
            } catch (NoSuchEntityException $e) {
                throw new RuntimeException("Requested website \"$websiteCode\" does not exist.");
            }
        }, $websiteCodes)));
    }

}