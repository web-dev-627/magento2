<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 09/12/2016
 * Time: 16:56
 */

namespace Notive\Ewarehousing\Cron;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Model\StockRegistry;
use Notive\Ewarehousing\Helper\Webservice;
use Psr\Log\LoggerInterface;

/**
 * Class Stock
 * @package Notive\Ewarehousing\Cron
 */
class Stock
{
    /** @var  Webservice */
    private $webservice;

    /** @var LoggerInterface */
    private $logger;

    /** @var Product */
    private $product;

    /** @var StockRegistry */
    private $stockRegistry;

    /**
     * Stock constructor.
     * @param Webservice $webservice
     * @param LoggerInterface $logger
     * @param Product $product
     * @param StockRegistry $stockRegistry
     */
    public function __construct(Webservice $webservice, LoggerInterface $logger, Product $product, StockRegistry $stockRegistry)
    {
        $this->webservice = $webservice;
        $this->logger = $logger;
        $this->product = $product;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Update
     * @return $this
     */
    public function update()
    {
        $this->logger->info(__METHOD__);
        if (!$this->webservice->getConfig('stock_sync', 'enabled')) {
            return $this;
        }

        $stockType = $this->webservice->getConfig('stock_sync', 'stock_type');

        if (!$stockType) {
            $stockType = 'salable_stock';
        }
        $stockArray = $this->webservice->getStock();
        foreach ($stockArray as $stock) {
            $sku = $stock['article_code'];
            $productId = $this->product->getIdBySku($sku);
            if ($productId) {
                $stockItem = $this->stockRegistry->getStockItemBySku($sku);
                $stockItem->setQty($stock[$stockType]);
                $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
            }
        }
        $this->logger->info(__METHOD__.': Successful');

        return $this;
    }
}