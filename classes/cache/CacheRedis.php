<?php
/**
 * 2015-2016 Michael Dekker
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@michaeldekker.com so we can send you a copy immediately.
 *
 * @author    Michael Dekker <prestashop@michaeldekker.com>
 * @copyright 2015-2016 Michael Dekker
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * This class require Redis server to be installed
 *
 */
class CacheRedisCore extends CacheCore
{
    /**
     * @var RedisClient
     */
    protected $redis;

    /**
     * @var RedisParams
     */
    protected $_params = array();

    /**
     * @var bool Connection status
     */
    public $is_connected = false;

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

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Connect to redis server
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
     * @see Cache::_set()
     *
     * @return bool
     */
    protected function _set($key, $value, $ttl = 0)
    {

        if (!$this->is_connected) {
            return false;
        }

        return $this->redis->set($key, $value);
    }

    /**
     * @see Cache::_get()
     *
     * @return bool
     */
    protected function _get($key)
    {
        if (!$this->is_connected) {
            return false;
        }

        return $this->redis->get($key);
    }

    /**
     * @see Cache::_exists()
     *
     * @return bool
     */
    protected function _exists($key)
    {
        if (!$this->is_connected) {
            return false;
        }

        return (bool) $this->_get($key);
    }

    /**
     * @see Cache::_delete()
     *
     * @return bool
     */
    protected function _delete($key)
    {
        if (!$this->is_connected) {
            return false;
        }

        return $this->redis->del($key);
    }

    /**
     * @see Cache::_writeKeys()
     *
     * @return bool
     */
    protected function _writeKeys()
    {
        if (!$this->is_connected) {
            return false;
        }
        $this->redis->set(_COOKIE_IV_, $this->keys);

        return true;
    }

    /**
     * @see Cache::flush()
     *
     * @return bool
     */
    public function flush()
    {
        if (!$this->is_connected) {
            return false;
        }

        return (bool) $this->redis->flushDB();
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
     * Get list of redis server information
     *
     * @return array
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
}
