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
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectMSI\Test\Integration;

use Magento\TestFramework\ObjectManager;
use MultiSafepay\ConnectCore\Api\StockReducerInterface;
use MultiSafepay\ConnectCatalogInventory\Model\StockReducer as StockReducerCatalogInventory;
use MultiSafepay\ConnectMSI\Model\StockReducer as StockReducerMSI;
use PHPUnit\Framework\TestCase;

class PreferenceTest extends TestCase
{
    public function testIfPreferenceWorks()
    {
        $stockerReducer = ObjectManager::getInstance()->get(StockReducerInterface::class);
        $this->assertTrue(
            is_a($stockerReducer, StockReducerMSI::class) || is_a($stockerReducer, StockReducerCatalogInventory::class)
        );
    }
}
