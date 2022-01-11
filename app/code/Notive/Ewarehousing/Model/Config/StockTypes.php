<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 29/11/2016
 * Time: 13:26
 */

namespace Notive\Ewarehousing\Model\Config;

/**
 * Class StockTypes
 * @package Notive\Ewarehousing\Model\Config
 */
class StockTypes
{
    /**
     * Create option array
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'salable_stock',
                'label' => 'Salable',
            ],
            [
                'value' => 'available_stock',
                'label' => 'Available',
            ],
            [
                'value' => 'fysical_stock',
                'label' => 'Physical',
            ]
        ];
    }
}