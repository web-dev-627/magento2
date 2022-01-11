<?php
/**
 * ||GEISSWEB| EU VAT Enhanced
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GEISSWEB End User License Agreement
 * that is available through the world-wide-web at this URL: https://www.geissweb.de/legal-information/eula
 *
 * DISCLAIMER
 *
 * Do not edit this file if you wish to update the extension in the future. If you wish to customize the extension
 * for your needs please refer to our support for more information.
 *
 * @copyright   Copyright (c) 2015 GEISS Weblösungen (https://www.geissweb.de)
 * @license     https://www.geissweb.de/legal-information/eula GEISSWEB End User License Agreement
 */

namespace Geissweb\Euvat\Api;

/**
 * Interface ValidationRepositoryInterface
 */
interface ValidationRepositoryInterface
{
    /**
     * Save Validation
     * @param \Geissweb\Euvat\Api\Data\ValidationInterface $validation
     * @return \Geissweb\Euvat\Api\Data\ValidationInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Geissweb\Euvat\Api\Data\ValidationInterface $validation
    );

    /**
     * Retrieve Validation
     * @param string $validationId
     * @return \Geissweb\Euvat\Api\Data\ValidationInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($validationId);

    /**
     * Retrieve Validation matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Geissweb\Euvat\Api\Data\ValidationSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Validation
     * @param \Geissweb\Euvat\Api\Data\ValidationInterface $validation
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Geissweb\Euvat\Api\Data\ValidationInterface $validation
    );

    /**
     * Delete Validation by ID
     * @param string $validationId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($validationId);

    /**
     * Get entry by VAT number
     * @param $vatId
     *
     * @return \Geissweb\Euvat\Api\Data\ValidationInterface|\Magento\Framework\Model\AbstractModel
     */
    public function getByVatId($vatId);
}