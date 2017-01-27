<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Migrate BO tabs for multi-shop new reorganization
 */
function migrate_tabs_multi_shop()
{
    include_once(_TB_INSTALL_PATH_.'upgrade/php/add_new_tab.php');
    include_once(_TB_INSTALL_PATH_.'upgrade/php/migrate_tabs_15.php');

    $nbr_shop = Db::getInstance()->getValue('SELECT count(id_shop) FROM '._DB_PREFIX_.'shop');
    $tab_shop_group_active = false;

    //check if current configuration has more than one shop
    if ($nbr_shop > 1) {
        Db::getInstance()->update('configuration', ['value' => true], 'name = \'PS_MULTISHOP_FEATURE_ACTIVE\'');
        $tab_shop_group_active = true;
    }

    // ===== remove AdminParentShop from BO menu =====
    $admin_parent_shop_id = get_tab_id('AdminParentShop');
    $admin_shop_group_id = get_tab_id('AdminShopGroup');
    Db::getInstance()->delete('tab', 'id_tab IN ('.(int)$admin_shop_group_id.', '.(int)$admin_parent_shop_id.')');
    Db::getInstance()->delete('tab_lang', 'id_tab IN ('.(int)$admin_shop_group_id.', '.(int)$admin_parent_shop_id.')');

    // ===== add AdminShopGroup to parent AdminTools =====
    $admin_shop_group_id = add_new_tab('AdminShopGroup', 'en:Multi-shop|fr:Multiboutique|es:Multi-tienda|de:Multi-shop|it:Multi-shop', get_tab_id('AdminTools'), true);
    Db::getInstance()->update('tab', ['active' => $tab_shop_group_active], 'id_tab = '.(int)$admin_shop_group_id);

    // ===== hide AdminShopUrl and AdminShop =====
    Db::getInstance()->update('tab', ['id_parent' => '-1'], 'id_tab IN ('.get_tab_id('AdminShop').', '.get_tab_id('AdminShopUrl').')');
}
