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

namespace MultiSafepay\ConnectCore\Util;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Config as TaxConfig;

class TaxUtil
{
    /**
     * @var Calculation
     */
    private $calculation;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * GrandTotalUtil constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param Calculation $calculation
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        Calculation $calculation,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->calculation = $calculation;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param OrderInterface $order
     * @return float
     * @throws NoSuchEntityException
     */
    public function getShippingTaxRate(OrderInterface $order): float
    {
        $store = $order->getStore();
        $quote = $this->quoteRepository->get($order->getQuoteId());

        $request = $this->calculation->getRateRequest(
            $order->getShippingAddress(),
            $order->getBillingAddress(),
            $quote->getCustomerTaxClassId(),
            $store
        );

        $taxRateId = $this->scopeConfig->getValue(
            TaxConfig::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
            ScopeInterface::SCOPE_STORES,
            $order->getStoreId()
        );

        return $this->calculation->getRate($request->setProductClassId($taxRateId));
    }
}
