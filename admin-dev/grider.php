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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/** @noinspection PhpUnhandledExceptionInspection */

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
include_once(_PS_ADMIN_DIR_.'/../config/config.inc.php');

$module = Tools::getValue('module');
$type = Tools::getValue('type');
$option = Tools::getValue('option');
$width = Tools::getIntValue('width', 600);
$height = Tools::getIntValue('height', 920);
$start = Tools::getIntValue('start', 0);
$limit = Tools::getIntValue('limit', 40);
$sort = Tools::getValue('sort', 0); // Should be a String. Default value is an Integer because we don't know what can be the name of the column to sort.
$dir = Tools::getValue('dir', 0); // Should be a String : Either ASC or DESC
$id_employee = Tools::getIntValue('id_employee');
$id_lang = Tools::getIntValue('id_lang');


if (!isset($cookie->id_employee) || !$cookie->id_employee || $cookie->id_employee != $id_employee) {
    throw new PrestaShopException(Tools::displayError("Employee not validated"));
}

if (!Validate::isModuleName($module)) {
    throw new PrestaShopException(sprintf(Tools::displayError("Invalid module name [%s]"), Tools::safeOutput($module)));
}

/** @var StatsModule $statsModuleInstance */
$statsModuleInstance = Module::getInstanceByName('statsmodule');

if ($statsModuleInstance->active && in_array($module, $statsModuleInstance->modules)) {
    $module_path = _PS_ROOT_DIR_.'/modules/statsmodule/stats/'.$module.'.php';
} else {
    if (!file_exists($module_path = _PS_ROOT_DIR_.'/modules/'.$module.'/'.$module.'.php')) {
        throw new PrestaShopException(sprintf(Tools::displayError("Module [%s] not found"), Tools::safeOutput($module)));
    }
}


$shop_id = '';
Shop::setContext(Shop::CONTEXT_ALL);
if (Context::getContext()->cookie->shopContext) {
    $split = explode('-', Context::getContext()->cookie->shopContext);
    if (count($split) == 2) {
        if ($split[0] == 'g') {
            if (Context::getContext()->employee->hasAuthOnShopGroup($split[1])) {
                Shop::setContext(Shop::CONTEXT_GROUP, $split[1]);
            } else {
                $shop_id = Context::getContext()->employee->getDefaultShopID();
                Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
            }
        } elseif (Shop::getShop($split[1]) && Context::getContext()->employee->hasAuthOnShop($split[1])) {
            $shop_id = $split[1];
            Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
        } else {
            $shop_id = Context::getContext()->employee->getDefaultShopID();
            Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
        }
    }
}

// Check multishop context and set right context if need
if (Shop::getContext()) {
    if (Shop::getContext() == Shop::CONTEXT_SHOP && !Shop::CONTEXT_SHOP) {
        Shop::setContext(Shop::CONTEXT_GROUP, Shop::getContextShopGroupID());
    }
    if (Shop::getContext() == Shop::CONTEXT_GROUP && !Shop::CONTEXT_GROUP) {
        Shop::setContext(Shop::CONTEXT_ALL);
    }
}

// Replace existing shop if necessary
if (!$shop_id) {
    Context::getContext()->shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
} elseif (Context::getContext()->shop->id != $shop_id) {
    Context::getContext()->shop = new Shop($shop_id);
}


require_once($module_path);

/** @var StatsModule $grid */
$grid = new $module();
$grid->setEmployee($id_employee);
$grid->setLang($id_lang);
if ($option) {
    $grid->setOption($option);
}
$grid->createGrid(null, $type, $width, $height, $start, $limit, $sort, $dir);
$grid->render();
