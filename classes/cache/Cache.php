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
     * @var array Store list of tables and their associated keys for SQL cache (warning: this var must not be initialized here !)
     */
    protected $sql_tables_cached;

    /**
     * @var array List of blacklisted tables for SQL cache, these tables won't be indexed
     */
    protected $blacklist = [
        'cart',
        'cart_cart_rule',
        'cart_product',
        'connections',
        'connections_source',
        'connections_page',
        'customer',
        'customer_group',
        'customized_data',
        'guest',
        'pagenotfound',
        'page_viewed',
    ];

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
     * @return mixed
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
     * @return Cache
     *
     * @throws PrestaShopException
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            $sql = new DbQuery();
            $sql->select('`value`');
            $sql->from('configuration');
            $sql->where('`name` = \'TB_CACHE_SYSTEM\'');
            $cachingSystem = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql, false);
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
     */
    public static function setInstanceForTesting($testInstance)
    {
        static::$instance = $testInstance;
    }

    /**
     * Unit testing purpose only
     *
     * @return void
     */
    public static function deleteTestingInstance()
    {
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
     * @return array List of deleted keys
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
     */
    public function setQuery($query, $result)
    {
        if ($this->isBlacklist($query)) {
            return true;
        }

        if (empty($result) || $result === false) {
            $result = [];
        }

        if (is_null($this->sql_tables_cached)) {
            $this->sql_tables_cached = $this->get(Tools::encryptIV(static::SQL_TABLES_NAME));
            if (!is_array($this->sql_tables_cached)) {
                $this->sql_tables_cached = [];
            }
        }

        // Store query results in cache
        $key = Tools::encryptIV($query);
        // no need to check the key existence before the set : if the query is already
        // in the cache, setQuery is not invoked
        $this->set($key, $result);

        // Get all table from the query and save them in cache
        if ($tables = $this->getTables($query)) {
            foreach ($tables as $table) {
                if (!isset($this->sql_tables_cached[$table][$key])) {
                    $this->adjustTableCacheSize($table);
                    $this->sql_tables_cached[$table][$key] = true;
                }
            }
        }
        $this->set(Tools::encryptIV(static::SQL_TABLES_NAME), $this->sql_tables_cached);
    }

    /**
     * Autoadjust the table cache size to avoid storing too big elements in the cache
     *
     * @param string $table
     */
    protected function adjustTableCacheSize($table)
    {
        if (isset($this->sql_tables_cached[$table])
            && count($this->sql_tables_cached[$table]) > 5000
        ) {
            // make sure the cache doesn't contains too many elements : delete the first 1000
            $tableBuffer = array_slice($this->sql_tables_cached[$table], 0, 1000, true);
            foreach ($tableBuffer as $fsKey => $value) {
                $this->delete($fsKey);
                $this->delete($fsKey.'_nrows');
                unset($this->sql_tables_cached[$table][$fsKey]);
            }
        }
    }

    /**
     * @param string $string
     * @return array|false
     */
    protected function getTables($string)
    {
        if (preg_match_all('/(?:from|join|update|into)\s+`?('._DB_PREFIX_.'[0-9a-z_-]+)(?:`?\s{0,},\s{0,}`?('._DB_PREFIX_.'[0-9a-z_-]+)`?)?(?:`|\s+|\Z)(?!\s*,)/Umsi', $string, $res)) {
            foreach ($res[2] as $table) {
                if ($table != '') {
                    $res[1][] = $table;
                }
            }

            return array_unique($res[1]);
        } else {
            return false;
        }
    }

    /**
     * Delete a query from cache
     *
     * @param string $query
     */
    public function deleteQuery($query)
    {
        if (is_null($this->sql_tables_cached)) {
            $this->sql_tables_cached = $this->get(Tools::encryptIV(static::SQL_TABLES_NAME));
            if (!is_array($this->sql_tables_cached)) {
                $this->sql_tables_cached = [];
            }
        }

        if ($tables = $this->getTables($query)) {
            foreach ($tables as $table) {
                if (isset($this->sql_tables_cached[$table])) {
                    foreach (array_keys($this->sql_tables_cached[$table]) as $fsKey) {
                        $this->delete($fsKey);
                        $this->delete($fsKey.'_nrows');
                    }
                    unset($this->sql_tables_cached[$table]);
                }
            }
        }
        $this->set(Tools::encryptIV(static::SQL_TABLES_NAME), $this->sql_tables_cached);
    }

    /**
     * Check if a query contain blacklisted tables
     *
     * @param string $query
     *
     * @return bool
     */
    protected function isBlacklist($query)
    {
        foreach ($this->blacklist as $find) {
            if (false !== strpos($query, _DB_PREFIX_.$find)) {
                return true;
            }
        }

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
        } else if (strpos($key, '*') !== false) {
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
