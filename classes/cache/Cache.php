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
 * Class CacheCore
 */
abstract class CacheCore
{
    /**
     * Name of keys index
     */
    const KEYS_NAME = '__keys__';

    /**
     * Name of SQL cache index
     *
     * @deprecated 1.5.0 We no longer cache query results
     */
    const SQL_TABLES_NAME = 'tablesCached';

    /**
     * @var Cache
     */
    protected static $instance;

    /**
     * @var array List all keys of cached data and their associated ttl
     */
    protected $keys = [];

    /**
     * @var array
     *
     * @deprecated 1.5.0 We no longer cache query results
     */
    protected $sql_tables_cached;

    /**
     * @var array List of blacklisted tables for SQL cache, these tables won't be indexed
     *
     * @deprecated 1.5.0 We no longer cache query results
     */
    protected $blacklist = [];

    /**
     * @var array Store local cache
     */
    protected static $local = [];

    /**
     * Cache a data
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return bool
     */
    abstract protected function _set($key, $value, $ttl = 0);

    /**
     * Retrieve a cached data by key
     *
     * @param string $key
     *
     * @return mixed|false
     */
    abstract protected function _get($key);

    /**
     * Check if a data is cached by key
     *
     * @param string $key
     *
     * @return bool
     */
    abstract protected function _exists($key);

    /**
     * Delete a data from the cache by key
     *
     * @param string $key
     *
     * @return bool
     */
    abstract protected function _delete($key);

    /**
     * Write keys index
     */
    abstract protected function _writeKeys();

    /**
     * Clean all cached data
     *
     * @return bool
     */
    abstract public function flush();

    /***
     * Returns true, if cache is available for use.
     *
     * @return bool
     */
    public function isAvailable()
    {
        return true;
    }

    /**
     * Returns true if server-side caching is
     *
     * @throws PrestaShopException
     */
    public static function isEnabled()
    {
        return (bool)Configuration::get('TB_CACHE_ENABLED');
    }

    /**
     * @param bool $force
     *
     * @return Cache
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getInstance($force = false)
    {
        if (!static::$instance || $force) {
            $sql = new DbQuery();
            $sql->select('`value`');
            $sql->from('configuration');
            $sql->where('`name` = \'TB_CACHE_SYSTEM\'');
            $cachingSystem = Db::readOnly()->getValue($sql);
            if ($cachingSystem) {
                static::$instance = new $cachingSystem();
            } else {
                static::$instance = new CacheNoop();
            }
        }

        return static::$instance;
    }

    /**
     * Unit testing purpose only
     *
     * @param Cache $testInstance
     *
     * @deprecated 1.5.0
     */
    public static function setInstanceForTesting($testInstance)
    {
        Tools::displayAsDeprecated();
        static::$instance = $testInstance;
    }

    /**
     * Unit testing purpose only
     *
     * @deprecated 1.5.0
     *
     * @return void
     */
    public static function deleteTestingInstance()
    {
        Tools::displayAsDeprecated();
        static::$instance = null;
    }

    /**
     * Store a data in cache
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return bool
     */
    public function set($key, $value, $ttl = 0)
    {
        if ($this->_set($key, $value, $ttl)) {
            if ($ttl < 0) {
                $ttl = 0;
            }

            $this->keys[$key] = ($ttl == 0) ? 0 : time() + $ttl;
            $this->_writeKeys();

            return true;
        }

        return false;
    }

    /**
     * Retrieve a data from cache
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (!isset($this->keys[$key])) {
            return false;
        }

        return $this->_get($key);
    }

    /**
     * Check if a data is cached
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        if (!isset($this->keys[$key])) {
            return false;
        }

        return $this->_exists($key);
    }

    /**
     * Delete one or several data from cache (* joker can be used)
     *    E.g.: delete('*'); delete('my_prefix_*'); delete('my_key_name');
     *
     * @param string $key
     *
     * @return array|bool List of deleted keys
     */
    public function delete($key)
    {
        // Get list of keys to delete
        $keys = [];
        if ($key == '*') {
            $keys = array_keys($this->keys);
        } elseif (strpos($key, '*') === false) {
            $keys = [$key];
        } else {
            $pattern = str_replace('\\*', '.*', preg_quote($key));
            foreach ($this->keys as $k => $ttl) {
                if (preg_match('#^'.$pattern.'$#', $k)) {
                    $keys[] = $k;
                }
            }
        }

        // Delete keys
        foreach ($keys as $key) {
            if (!isset($this->keys[$key])) {
                continue;
            }

            if ($this->_delete($key)) {
                unset($this->keys[$key]);
            }
        }

        $this->_writeKeys();

        return $keys;
    }

    /**
     * Store a query in cache
     *
     * @param string $query
     * @param array $result
     *
     * @return bool
     *
     * @deprecated 1.5.0 We no longer cache query results
     */
    public function setQuery($query, $result)
    {
        Tools::displayAsDeprecated();
        return false;
    }

    /**
     * Autoadjust the table cache size to avoid storing too big elements in the cache
     *
     * @param string $table
     *
     * @deprecated 1.5.0 We no longer cache query results
     */
    protected function adjustTableCacheSize($table)
    {
        Tools::displayAsDeprecated();
    }

    /**
     * @param string $string
     *
     * @return false
     *
     * @deprecated 1.5.0 We no longer cache query results
     */
    protected function getTables($string)
    {
        Tools::displayAsDeprecated();
        return false;
    }

    /**
     * Delete a query from cache
     *
     * @param string $query
     *
     * @deprecated 1.5.0 We no longer cache query results
     */
    public function deleteQuery($query)
    {
        Tools::displayAsDeprecated();
    }

    /**
     * Check if a query contain blacklisted tables
     *
     * @param string $query
     *
     * @return bool
     *
     * @deprecated 1.5.0 We no longer cache query results
     */
    protected function isBlacklist($query)
    {
        Tools::displayAsDeprecated();
        return false;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public static function store($key, $value)
    {
        static::$local[$key] = $value;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public static function retrieve($key)
    {
        return static::$local[$key] ?? null;
    }

    /**
     * @return array
     */
    public static function retrieveAll()
    {
        return static::$local;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public static function isStored($key)
    {
        return isset(static::$local[$key]);
    }

    /**
     * @param string $key
     */
    public static function clean($key)
    {
        if ($key === '*') {
            static::$local = [];
        } elseif (strpos($key, '*') !== false) {
            $regexp = str_replace('\\*', '.*', preg_quote($key, '#'));
            foreach (array_keys(static::$local) as $key) {
                if (preg_match('#^'.$regexp.'$#', $key)) {
                    unset(static::$local[$key]);
                }
            }
        } else {
            unset(static::$local[$key]);
        }
    }

}
