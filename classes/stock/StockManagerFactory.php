<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class StockManagerFactoryCore
 *
 * @since 1.0.0
 */
class StockManagerFactoryCore
{
    // @codingStandardsIgnoreStart
    /** @var $stock_manager : instance of the current StockManager. */
    protected static $stock_manager;
    // @codingStandardsIgnoreEnd

    /**
     * Returns a StockManager
     *
     * @return StockManagerInterface
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getManager()
    {
        if (!isset(StockManagerFactory::$stock_manager)) {
            $stockManager = StockManagerFactory::execHookStockManagerFactory();
            if (!($stockManager instanceof StockManagerInterface)) {
                $stockManager = new StockManager();
            }
            StockManagerFactory::$stock_manager = $stockManager;
        }
        return StockManagerFactory::$stock_manager;
    }

    /**
     * Looks for a StockManager in the modules list.
     *
     * @return StockManagerInterface
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function execHookStockManagerFactory()
    {
        $modulesInfos = Hook::getModulesFromHook(Hook::getIdByName('stockManager'));
        $stockManager = false;

        foreach ($modulesInfos as $moduleInfos) {
            $moduleInstance = Module::getInstanceByName($moduleInfos['name']);

            if (is_callable([$moduleInstance, 'hookStockManager'])) {
                $stockManager = $moduleInstance->hookStockManager();
            }

            if ($stockManager) {
                break;
            }
        }

        return $stockManager;
    }
}
