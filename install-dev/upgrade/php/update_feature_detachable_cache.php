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

function update_feature_detachable_cache()
{
    $array_features = [
        'PS_SPECIFIC_PRICE_FEATURE_ACTIVE' => 'specific_price',
        'PS_SCENE_FEATURE_ACTIVE' => 'scene',
        'PS_PRODUCT_DOWNLOAD_FEATURE_ACTIVE' => 'product_download',
        'PS_CUSTOMIZATION_FEATURE_ACTIVE' => 'customization_field',
        'PS_CART_RULE_FEATURE_ACTIVE' => 'discount',
        'PS_GROUP_FEATURE_ACTIVE' => 'group',
        'PS_PACK_FEATURE_ACTIVE' => 'pack',
        'PS_ALIAS_FEATURE_ACTIVE' => 'alias',
    ];
    $res = true;
    foreach ($array_features as $config_key => $feature) {
        // array_features is an array defined above, so please don't add bqSql !
        $count = (int)Db::getInstance()->getValue('SELECT count(*) FROM `'._DB_PREFIX_.$feature.'`');

        $exist = Db::getInstance()->getValue('SELECT `id_configuration` FROM `'._DB_PREFIX_.'configuration` WHERE `name` = \''.pSQL($config_key).'\'');
        if ($exist) {
            $res &= Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'configuration` SET value = "'.(int)$count.'" WHERE `name` = \''.pSQL($config_key).'\'');
        } else {
            $res &= Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'configuration` (name, value) values ("'.pSQL($config_key).'", "'.(int)$count.'")');
        }
    }
    return $res;
}
