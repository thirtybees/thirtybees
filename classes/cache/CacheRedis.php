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
 * This class require Redis server to be installed
 */
class CacheRedisCore extends Cache
{
    const KEYS_PREFIX_CONFIG_KEY = 'TB_REDIS_KEYS_PREFIX';

    /**
     * @var bool Connection status
     */
    public $is_connected = false;

    /**
     * @var Redis|RedisArray $redis
     */
    protected $redis;

    /**
     * @var string
     */
    protected $keysPrefix;

    /**
     * CacheRedisCore constructor.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($keysPrefix = null)
    {
        $this->is_connected = $this->connect();
        $this->keysPrefix = $keysPrefix ?? static::resolveKeysPrefix();
        if (! $this->is_connected) {
            trigger_error("Failed to connect to redis", E_USER_WARNING);
        }
    }

    /**
     * @return bool
     */
    public static function checkEnvironment()
    {
       return extension_loaded('redis');
    }

    /**
     * Connect to redis server or cluster
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function connect()
    {
        if (! static::checkEnvironment()) {
            return false;
        }
        try {
            $servers = static::getRedisServers();

            // no servers defined
            if (!$servers) {
                return false;
            }

            return (count($servers) === 1)
                ? $this->connectSingleServer($servers[0])
                : $this->connectCluster($servers);

        } catch (RedisException $e) {
            return false;
        }
    }

    /**
     * Connect to single redis server
     *
     * @param array $serverConfig
     *
     * @return bool
     * @throws RedisException
     */
    protected function connectSingleServer($serverConfig)
    {
        $this->redis = new Redis();
        if ($this->redis->pconnect($serverConfig['ip'], $serverConfig['port'])) {
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
            return $this->authConnection($serverConfig);
        }
        return false;
    }

    /**
     * Connects to redis cluster
     *
     * @param array[] $servers
     *
     * @return bool
     * @throws RedisException
     */
    protected function connectCluster($servers)
    {
        $hosts = [];
        foreach ($servers as $server) {
            $hosts[] = $server['ip'] . ':' . $server['port'];
        }
        $this->redis = new RedisArray($hosts, ['pconnect' => true]);
        $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

        $connected = true;
        foreach ($servers as $serverConfig) {
            $connected= $connected && $this->authConnection($serverConfig);
        }
        return $connected;
    }

    /**
     * Authenticate redis connection. Returns true, if connection to redis server(s) is established
     *
     * @param array $serverConfig
     *
     * @return bool
     * @throws RedisException
     */
    protected function authConnection($serverConfig)
    {
        if ($serverConfig['auth']) {
            return $this->redis->auth($serverConfig['auth']) === true;
        } else {
            $this->redis->select($serverConfig['db']);
            return (bool)$this->redis->ping();
        }
    }


    /***
     * Returns true, if we are connected to redis cluster
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->is_connected;
    }

    /**
     *Add a redis server
     *
     * @param string $ip IP address or hostname
     * @param int $port Port number
     * @param string $auth Authentication key
     * @param int $db Redis database ID
     *
     * @return bool Whether the server was successfully added
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function addServer($ip, $port, $auth, $db)
    {
        $sql = new DbQuery();
        $sql->select('count(*)');
        $sql->from('redis_servers');
        $sql->where('`ip` = \''.pSQL($ip).'\'');
        $sql->where('`port` = '.(int) $port);
        $sql->where('`auth` = \''.pSQL($auth).'\'');
        $sql->where('`db` = '.(int) $db);
        if (Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql, false)) {
            return false;
        }

        return Db::getInstance()->insert(
            'redis_servers',
            [
                'ip'   => pSQL($ip),
                'port' => (int) $port,
                'auth' => pSQL($auth),
                'db'   => (int) $db,
            ],
            false,
            false
        );
    }

    /**
     * Get list of redis server information
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getRedisServers()
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('redis_servers');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getArray($sql);
    }

    /**
     * Delete a redis server
     *
     * @param int $idServer Server ID
     *
     * @return bool Whether the server was successfully deleted
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function deleteServer($idServer)
    {
        return Db::getInstance()->delete(
            'redis_servers',
            '`id_redis_server` = '.(int) $idServer,
            0,
            false
        );
    }

    /**
     * Returns redis key to store all existing keys
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected static function resolveKeysPrefix()
    {
        $value = Configuration::getGlobalValue(static::KEYS_PREFIX_CONFIG_KEY);
        if (! $value) {
            $value = Tools::passwdGen(6);
            Configuration::updateGlobalValue(static::KEYS_PREFIX_CONFIG_KEY, $value);
        }
        return $value;
    }


    /**
     * Clean all cached data
     *
     * @return bool
     *
     * @throws RedisException
     */
    public function flush()
    {
        if (!$this->is_connected) {
            return false;
        }

        return (bool) $this->redis->flushDB();
    }

    /**
     * Store a data in cache
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return bool
     * @throws RedisException
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
     * @throws RedisException
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
     * @throws RedisException
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
     * @throws RedisException
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
            $keys = $this->redis->keys($this->mapKey($key));
            if (is_array($keys) && $keys) {
                $this->redis->del($keys);
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
     *
     * @throws RedisException
     */
    protected function _set($key, $value, $ttl = 0)
    {
        if (!$this->is_connected) {
            return false;
        }

        $timeout = ($ttl > 0) ? $ttl : null;
        return $this->redis->set($this->mapKey($key), $value, $timeout);
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws RedisException
     */
    protected function _exists($key)
    {
        if (!$this->is_connected) {
            return false;
        }

        return (bool) $this->_get($key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     *
     * @throws RedisException
     */
    protected function _get($key)
    {
        if (!$this->is_connected) {
            return false;
        }

        return $this->redis->get($this->mapKey($key));
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws RedisException
     */
    protected function _delete($key)
    {
        if (!$this->is_connected) {
            return false;
        }

        return $this->redis->del($this->mapKey($key));
    }

    /**
     * Write keys index
     */
    protected function _writeKeys()
    {
        // this implementation do not use keys
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function mapKey($key)
    {
        return $this->keysPrefix . ':' . $key;
    }
}
