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
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
/**
 * This class require Redis server to be installed
 *
 * @since 1.0.0
 */
class CacheRedisCore extends CacheCore
{
    /**
     * @var bool Connection status
     *
     * @since 1.0.0
     */
    public $is_connected = false;
    /**
     * @var RedisClient $redis
     *
     * @since 1.0.0
     */
    protected $redis;
    /**
     * @var array RedisParams
     *
     * @since 1.0.0
     */
    protected $_params = array();

    /**
     * CacheRedisCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->connect();

        if ($this->is_connected) {
            $this->keys = @$this->redis->get(_COOKIE_IV_);
            if (!is_array($this->keys)) {
                $this->keys = array();
            }
        }
    }

    /**
     * Connect to redis server
     *
     * @since 1.0.0
     */
    public function connect()
    {
        $this->is_connected = false;
        $server = self::getRedisServer();

        if (!$server) {
            return;
        } else {
            $this->redis = new Redis();

            if ($this->redis->pconnect($server['TB_REDIS_SERVER'], $server['TB_REDIS_PORT'])) {
                $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
                if ($server['TB_REDIS_AUTH']) {
                    if (!($this->redis->auth((string) $server['TB_REDIS_AUTH']))) {
                        return;
                    }
                }
                $this->redis->select((int) $server['TB_REDIS_DB']);
                $this->is_connected = true;
            }
        }
    }

    /**
     * Get list of redis server information
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function getRedisServer()
    {
        $server = array();
        // bypass the memory fatal error caused functions nesting on PS 1.5
        $sql = new DbQuery();
        $sql->select('`name`, `value`');
        $sql->from('configuration');
        $sql->where('`name` = \'TB_REDIS_SERVER\' OR `name` = \'TB_REDIS_PORT\' OR name = \'TB_REDIS_AUTH\' OR name = \'TB_REDIS_DB\'');
        $params = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);
        foreach ($params as $key => $val) {
            $server[$val['name']] = $val['value'];
        }

        return $server;
    }

    /**
     * +     * Add a redis server
     * +     *
     * +     * @param string $ip IP address or hostname
     * +     * @param int $port Port number
     * +     * @param string $auth Authentication key
     * +     * @param int $db Redis database ID
     * +     * @return bool Whether the server was successfully added
     * +     * @throws PrestaShopDatabaseException
     * +     */
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
            $context = Context::getContext();
            $context->controller->errors[] =
                Tools::displayError('Redis server has already been added');

            return false;
        }

        return Db::getInstance()->insert(
            'redis_servers',
            array(
                'ip'   => pSQL($ip),
                'port' => (int) $port,
                'auth' => pSQL($auth),
                'db'   => (int) $db,
            ),
            false,
            false
        );
    }

    /**
     * Get list of redis server information
     *
     * @return array
     */
    public static function getRedisServers()
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('redis_servers');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);
    }

    /**
     * Delete a redis server
     *
     * @param int $id_server Server ID
     *
     * @return bool Whether the server was successfully deleted
     */
    public static function deleteServer($id_server)
    {
        return Db::getInstance()->delete(
            'redis_servers',
            '`id_redis_server` = '.(int) $id_server,
            0,
            false
        );
    }

    /**
     * @since 1.0.0
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Close connection to redis server
     *
     * @return bool
     *
     * @since 1.0.0
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
     * @see   Cache::flush()
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function flush()
    {
        if (!$this->is_connected) {
            return false;
        }

        return (bool) $this->redis->flushDB();
    }

    /**
     * @see   Cache::_set()
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function _set($key, $value, $ttl = 0)
    {

        if (!$this->is_connected) {
            return false;
        }

        return $this->redis->set($key, $value);
    }

    /**
     * @see   Cache::_exists()
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function _exists($key)
    {
        if (!$this->is_connected) {
            return false;
        }

        return (bool) $this->_get($key);
    }

    /**
     * @see   Cache::_get()
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function _get($key)
    {
        if (!$this->is_connected) {
            return false;
        }

        return $this->redis->get($key);
    }

    /**
     * @see   Cache::_delete()
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function _delete($key)
    {
        if (!$this->is_connected) {
            return false;
        }

        return $this->redis->del($key);
    }

    /**
     * @see   Cache::_writeKeys()
     *
     * @return bool
     *
     * @since 1.0.0
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
