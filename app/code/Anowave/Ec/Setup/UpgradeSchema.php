<?php
/**
 * Anowave Magento 2 Google Tag Manager Enhanced Ecommerce (UA) Tracking
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Ec
 * @copyright 	Copyright (c) 2021 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

namespace Anowave\Ec\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Updates DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        
        if (version_compare($context->getVersion(), '101.0.0') < 0)
        {
            $sql = [];
            
            $sql[] = "SET foreign_key_checks = 0";
            
            $sql[] = "CREATE TABLE IF NOT EXISTS " . $setup->getTable('ae_ec') . " (ec_id bigint(21) NOT NULL AUTO_INCREMENT,ec_order_id bigint(21) DEFAULT NULL,ec_cookie_ga varchar(255) DEFAULT NULL,PRIMARY KEY (ec_id)) ENGINE=InnoDB DEFAULT CHARSET=latin1";
            
            $sql[] = "SET foreign_key_checks = 1";
            
            foreach ($sql as $query)
            {
                $setup->run($query);
            }
        }
        
        $setup->endSetup();
    }
}