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
    /**
     * @var bool Connection status
     */
    public $is_connected = false;

    /**
     * @var Redis|RedisArray $redis
     */
    protected $redis;


    /**
     * CacheRedisCore constructor.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws RedisException
     */
    public function __construct()
    {
        $this->is_connected = $this->connect();

        if ($this->is_connected) {
            $this->keys = @$this->redis->get(_COOKIE_IV_);
            if (!is_array($this->keys)) {
                $this->keys = [];
            }
        }
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
            throw new PrestaShopException("Failed to connect to redis", 0, $e);
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
     * Close connection to redis server
     *
     * @return bool
     */
    protected function close()
    {
        if (!$this->is_connected) {
            return false;
        }

        // Don't close the connection, needs to be persistent across PHP-sessions
        return true;
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

        return $this->redis->set($key, $value);
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

        return $this->redis->get($key);
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

        return $this->redis->del($key);
    }

    /**
     * @return bool
     *
     * @throws RedisException
     */
    protected function _writeKeys()
    {
        if (!$this->is_connected) {
            return false;
        }
        $this->redis->set(_COOKIE_IV_, $this->keys);

        return true;
    }
}
