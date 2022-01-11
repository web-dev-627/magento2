<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCatalogInventory\Model;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\ConnectCore\Api\StockReducerInterface;

class StockReducer implements StockReducerInterface
{

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * StockReducer constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StockRegistryInterface $stockRegistry
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @param OrderInterface $order
     */
    public function reduce(OrderInterface $order): void
    {
        if (!$this->scopeConfig->getValue('cataloginventory/options/can_subtract')) {
            return;
        }

        $products = $order->getAllItems();
        foreach ($products as $itemId => $product) {
            $stockItem = $this->stockRegistry->getStockItem($product->getProductId());
            $new = $stockItem->getQty() - $product->getQtyOrdered();
            $stockItem->setQty($new);
            $stockItem->save();
        }
    }
}
