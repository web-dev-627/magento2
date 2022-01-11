<?php

namespace CappasityTech\Magento3D\Model\Data;

use Magento\Framework\App\Request\DataPersistorInterface;
use CappasityTech\Magento3D\Model\ResourceModel\Setting\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class SettingProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    private $loadedData;
    private $dataPersistor;
    protected $collection;
    private $storeManager;
    private $helper;

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
        \CappasityTech\Magento3D\Helper\Data $helper,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->helper = $helper;
        $this->collection = $collectionFactory->create();
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
        $planLabel = $this->helper->getCurrentPlanAlias();

        foreach ($items as $model) {
            $this->loadedData[$model->getId()] = $model->getData();
            if ($planLabel) {
                $m['plan_label'] = strtoupper($planLabel);
                $fullData = $this->loadedData;
//                $this->loadedData[$model->getId()] = array_merge($fullData[$model->getId()], $m);
                $this->loadedData[$model->getId()] = $fullData[$model->getId()] += $m;
            }
        }

        return $this->loadedData;
    }

    public function getId($model)
    {
        return $model->load($model->getId());
    }
}
