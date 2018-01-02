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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * APCu CacheResource for Smarty 3.1.x
 *
 * CacheResource Implementation based on the KeyValueStore API to use
 * apc as the storage resource for Smarty's output caching.
 *
 */
class Smarty_CacheResource_Apcu extends Smarty_CacheResource_KeyValueStore
{
    /**
     * Smarty_CacheResource_Apcu constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        if (!function_exists('apcu_cache_info')) {
            throw new PrestaShopException('APCu Template Caching Error: APCu is not installed');
        }
    }

    /**
     * Read values for a set of keys from cache
     *
     * @param array $keys list of keys to fetch
     *
     * @return array list of values with the given keys used as indexes
     * @return boolean true on success, false on failure
     */
    protected function read(array $keys)
    {
        return apcu_fetch($keys);
    }

    /**
     * Save values for a set of keys to cache
     *
     * @param array $keys   list of values to save
     * @param int   $expire expiration time
     *
     * @return bool true on success, false on failure
     */
    protected function write(array $keys, $expire = null)
    {
        return apcu_store($keys, null, $expire);
    }

    /**
     * Remove values from cache
     *
     * @param array $keys list of keys to delete
     *
     * @return bool true on success, false on failure
     */
    protected function delete(array $keys)
    {
        foreach ($keys as $k) {
            apcu_delete($k);
        }

        return true;
    }

    /**
     * Remove *all* values from cache
     *
     * @return bool true on success, false on failure
     */
    protected function purge()
    {
        return apcu_clear_cache();
    }
}
