<?php

namespace CappasityTech\Magento3D\Model\Data;

use Magento\Framework\App\Request\DataPersistorInterface;
use CappasityTech\Magento3D\Model\ResourceModel\SyncJobParams\CollectionFactory;
//use CappasityTech\Magento3D\Model\ResourceModel\ParamsRule\CollectionParamsRuleFactory;
use Magento\Store\Model\StoreManagerInterface;

class SyncJobProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    private $loadedData;
    private $dataPersistor;
    protected $collection;
    private $storeManager;
    private $cappasityParamsParamsFactory;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $blockCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        \CappasityTech\Magento3D\Model\ParamsRuleFactory $cappasityParamsRuleFactory,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->cappasityParamsParamsFactory = $cappasityParamsRuleFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->storeManager = $storeManager;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        $collectionParamsRule = $this->cappasityParamsParamsFactory->getCollection();
        if (count($collectionParamsRule)) {
            foreach ($collectionParamsRule as $item) {
                $rules[$item->getName()] = $item->getValue();
            }
        }

        foreach ($items as $model) {
            $data = $model->getData();
            if ($rules) {
//                $data = array_merge($data, $rules);
                $data += $rules;
            }
            $this->loadedData[$model->getId()] = $data;
        }

        return $this->loadedData;
    }

    public function getId($model)
    {
        return $model->load($model->getId());
    }
}
