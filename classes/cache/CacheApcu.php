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

/**
 * This class requires the PECL APC extension or PECL APCu extension to be installed
 */
class CacheApcuCore extends Cache
{
    /**
     * @var bool
     */
    protected $enabled;

    /**
     * CacheApcCore constructor.
     */
    public function __construct()
    {
        $this->enabled = static::checkEnvironment();

        if (! $this->enabled) {
            trigger_error('APCu cache has been enabled, but the APCu extension is not available', E_USER_WARNING);
        }
    }

    /**
     * @return bool returns true, if server supports APCu
     */
    public static function checkEnvironment()
    {
        return (
            extension_loaded('apcu') &&
            apcu_enabled()
        );
    }

    /***
     * Returns true, if apcu extension is loaded and enabled
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->enabled;
    }


    /**
     * Delete one or several data from cache (* joker can be used, but avoid it !)
     *    E.g.: delete('*'); delete('my_prefix_*'); delete('my_key_name');
     *
     * @param string $key Cache key
     *
     * @return bool Whether the key was deleted
     */
    public function delete($key)
    {
        if (! $this->enabled) {
            return false;
        }

        if ($key == '*') {
            $this->flush();
        } elseif (strpos($key, '*') === false) {
            $this->_delete($key);
        } else {
            $pattern = str_replace('\\*', '.*', preg_quote($key));

            $cacheInfo = apcu_cache_info(false);
            foreach ($cacheInfo['cache_list'] as $entry) {
                if (isset($entry['key'])) {
                    $key = $entry['key'];
                } else {
                    $key = $entry['info'];
                }
                if (preg_match('#^'.$pattern.'$#', $key)) {
                    $this->_delete($key);
                }
            }
        }

        return true;
    }

    /**
     * Cache a data
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return bool
     */
    protected function _set($key, $value, $ttl = 0)
    {
        if (! $this->enabled) {
            return false;
        }
        return apcu_store($key, $value, $ttl) === true;
    }

    /**
     * Retrieve a cached data by key
     *
     * @param string $key
     *
     * @return mixed|false
     */
    protected function _get($key)
    {
        if (! $this->enabled) {
            return false;
        }
        return apcu_fetch($key);
    }

    /**
     * Check if a data is cached by key
     *
     * @param string $key
     *
     * @return bool
     */
    protected function _exists($key)
    {
        if (! $this->enabled) {
            return false;
        }
        return apcu_exists($key);
    }

    /**
     * Delete a data from the cache by key
     *
     * @param string $key
     *
     * @return bool
     */
    protected function _delete($key)
    {
        if (! $this->enabled) {
            return false;
        }
        return apcu_delete($key);
    }

    /**
     * Write keys index
     */
    protected function _writeKeys()
    {
        // this implementation do not use keys
    }

    /**
     * Clean all cached data
     *
     * @return bool
     */
    public function flush()
    {
        if (! $this->enabled) {
            return false;
        }
        return apcu_clear_cache();
    }

    /**
     * Store data in the cache
     *
     * @param string $key Cache Key
     * @param mixed $value Value
     * @param int $ttl Time to live in the cache, 0 = unlimited
     *
     * @return bool Whether the data was successfully stored.
     */
    public function set($key, $value, $ttl = 0)
    {
        if (! $this->enabled) {
            return false;
        }
        return $this->_set($key, $value, $ttl);
    }

    /**
     * Retrieve data from the cache
     *
     * @param string $key Cache key
     *
     * @return mixed Data
     */
    public function get($key)
    {
        if (! $this->enabled) {
            return false;
        }
        return $this->_get($key);
    }

    /**
     * Check if data has been cached
     *
     * @param string $key Cache key
     *
     * @return bool Whether the data has been cached
     */
    public function exists($key)
    {
        if (! $this->enabled) {
            return false;
        }
        return $this->_exists($key);
    }
}
