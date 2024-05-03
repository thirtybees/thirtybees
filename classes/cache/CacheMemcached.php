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
 * Class CacheMemcachedCore
 */
class CacheMemcachedCore extends Cache
{
    /**
     * @var Memcached
     */
    protected $memcached;

    /**
     * @var bool Connection status
     */
    protected $is_connected = false;

    /**
     * CacheMemcachedCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->is_connected = $this->connect();
        if ($this->is_connected) {
            $this->memcached->setOption(Memcached::OPT_PREFIX_KEY, _DB_PREFIX_);
            if ($this->memcached->getOption(Memcached::HAVE_IGBINARY)) {
                $this->memcached->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_IGBINARY);
            }
        } else {
            trigger_error("Failed to connect to memcache", E_USER_WARNING);
        }
    }

    /**
     * CacheMemcachedCore destructor.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Connect to memcached server
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function connect()
    {
        if (! static::checkEnvironment()) {
            return false;
        }

        $servers = static::getMemcachedServers();
        if (! $servers) {
            return false;
        }

        try {
            $this->memcached = new Memcached();
            foreach ($servers as $server) {
                $this->memcached->addServer($server['ip'], $server['port'], (int) $server['weight']);
            }

            return (bool)$this->memcached->getVersion();
        } catch (Throwable $e) {
            return false;
        }
    }

    /***
     * Returns true, if we are connected to memcache server
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->is_connected;
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
        if (!$this->is_connected) {
            return false;
        }

        $expires = $ttl ? time() + $ttl : 0;
        return $this->memcached->set(static::mapKey($key), $value, $expires);
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
        if (!$this->is_connected) {
            return false;
        }

        return $this->memcached->get(static::mapKey($key));
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
        if (!$this->is_connected) {
            return false;
        }

        return ($this->memcached->get(static::mapKey($key)) !== false);
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
        if (!$this->is_connected) {
            return false;
        }

        return $this->memcached->delete(static::mapKey($key));
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
        if (!$this->is_connected) {
            return false;
        }

        return $this->memcached->flush();
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
        return $this->_set($key, $value, $ttl);
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
        return $this->_exists($key);
    }

    /**
     * Delete one or several data from cache (* joker can be used, but avoid it !)
     *    E.g.: delete('*'); delete('my_prefix_*'); delete('my_key_name');
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete($key)
    {
        if (! $this->is_connected) {
            return false;
        }
        if ($key == '*') {
            $this->flush();
        } elseif (strpos($key, '*') === false) {
            $this->_delete($key);
        } else {
            $pattern = str_replace('\\*', '.*', preg_quote($key));
            $keys = $this->memcached->getAllKeys();
            foreach ($keys as $key => $data) {
                if (preg_match('#^'.$pattern.'$#', $key)) {
                    $this->_delete($key);
                }
            }
        }

        return true;
    }

    /**
     * Close connection to memcache server
     *
     * @return bool
     */
    protected function close()
    {
        if (!$this->is_connected) {
            return false;
        }

        return $this->memcached->quit();
    }

    /**
     * Add a memcached server
     *
     * @param string $ip
     * @param int $port
     * @param int $weight
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function addServer($ip, $port, $weight)
    {
        return Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'memcached_servers (ip, port, weight) VALUES(\''.pSQL($ip).'\', '.(int) $port.', '.(int) $weight.')', false);
    }

    /**
     * Get list of memcached servers
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getMemcachedServers()
    {
        return Db::readOnly()->getArray('SELECT * FROM '._DB_PREFIX_.'memcached_servers');
    }

    /**
     * Delete a memcached server
     *
     * @param int $id_server
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function deleteServer($id_server)
    {
        return Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'memcached_servers WHERE id_memcached_server='.(int) $id_server);
    }

    /**
     * @return bool
     */
    public static function checkEnvironment()
    {
        return (
            class_exists('Memcached') &&
            extension_loaded('memcached')
        );
    }

    /**
     * @return string
     */
    protected static function mapKey($key)
    {
        if (strlen($key) > 250)  {
            return Tools::encrypt($key);
        }
        return $key;
    }
}
