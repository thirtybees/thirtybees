<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class StockManagerModuleCore
 */
abstract class StockManagerModuleCore extends Module
{
    /**
     * @var string
     */
    public $stock_manager_class;

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        return (parent::install() && $this->registerHook('stockManager'));
    }

    /**
     * @return false | StockManagerInterface
     *
     * @throws PrestaShopException
     */
    public function hookStockManager()
    {
        $classFile = _PS_MODULE_DIR_.'/'.$this->name.'/'.$this->stock_manager_class.'.php';

        if (!isset($this->stock_manager_class) || !file_exists($classFile)) {
            throw new PrestaShopException(sprintf(Tools::displayError('Incorrect Stock Manager class [%s]'), $this->stock_manager_class));
        }

        require_once($classFile);

        if (!class_exists($this->stock_manager_class)) {
            throw new PrestaShopException(sprintf(Tools::displayError('Stock Manager class not found [%s]'), $this->stock_manager_class));
        }

        $class = $this->stock_manager_class;
        if (call_user_func([$class, 'isAvailable'])) {
            return new $class();
        }

        return false;
    }
}
