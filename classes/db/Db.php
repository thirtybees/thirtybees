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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

if (file_exists(_PS_ROOT_DIR_.'/config/settings.inc.php')) {
    include_once(_PS_ROOT_DIR_.'/config/settings.inc.php');
}

/**
 * Class DbCore
 *
 * @since 1.0.0
 */
abstract class DbCore
{
    /** @var int Constant used by insert() method */
    const INSERT = 1;

    /** @var int Constant used by insert() method */
    const INSERT_IGNORE = 2;

    /** @var int Constant used by insert() method */
    const REPLACE = 3;

    /** @var int Constant used by insert() method */
    const ON_DUPLICATE_KEY = 4;

    /** @var string Server (eg. localhost) */
    protected $server;

    /**  @var string Database user (eg. root) */
    protected $user;

    /** @var string Database password (eg. can be empty !) */
    protected $password;

    /** @var string Database name */
    protected $database;

    /**
     * @var bool
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    protected $is_cache_enabled = false;

    /** @var PDO Resource link */
    protected $link;

    /** @var PDOStatement|bool SQL cached result */
    protected $result;

    /** @var array List of DB instances */
    public static $instance = [];

    /** @var array List of server settings */
    public static $_servers = [];

    /** @var null Flag used to load slave servers only once.
     * See loadSlaveServers() method.
     */
    public static $_slave_servers_loaded = null;

    /**
     * Store last executed query
     *
     * @var string
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    protected $last_query;

    /**
     * Store hash of the last executed query
     *
     * @var string
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    protected $last_query_hash;

    /**
     * Last cached query
     *
     * @var string
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    protected $last_cached;

    /**
     * Opens a database connection
     *
     * @return PDO
     */
    abstract public function connect();

    /**
     * Closes database connection
     */
    abstract public function disconnect();

    /**
     * Execute a query and get result resource
     *
     * @param string $sql
     * @return PDOStatement|bool
     */
    abstract protected function _query($sql);

    /**
     * Get number of rows in a result
     *
     * @param mixed $result
     * @return int
     */
    abstract protected function _numRows($result);

    /**
     * Get the ID generated from the previous INSERT operation
     *
     * @return int|string
     */
    abstract public function Insert_ID();

    /**
     * Get number of affected rows in previous database operation
     *
     * @return int
     */
    abstract public function Affected_Rows();

    /**
     * Get next row for a query which does not return an array
     *
     * @param PDOStatement|bool $result
     * @return array|object|false|null
     */
    abstract public function nextRow($result = false);

    /**
     * Get all rows for a query which return an array
     *
     * @param PDOStatement|bool|null $result
     * @return array
     */
    abstract protected function getAll($result = false);

    /**
     * Get database version
     *
     * @return string
     */
    abstract public function getVersion();

    /**
     * Protect string against SQL injections
     *
     * @param string $str
     * @return string
     */
    abstract public function _escape($str);

    /**
     * Returns the text of the error message from previous database operation
     *
     * @return string
     */
    abstract public function getMsgError();

    /**
     * Returns the number of the error from previous database operation
     *
     * @return int
     */
    abstract public function getNumberError();

    /**
     * Sets the current active database on the server that's associated with the specified link identifier.
     * Do not remove, useful for some modules.
     *
     * @param string $dbName
     *
     * @return bool|int
     */
    abstract public function set_db($dbName);

    /**
     * Selects best table engine.
     *
     * @return string
     */
    abstract public function getBestEngine();

    /**
     * Sets time zone for database connection.
     *
     * @return string
     */
    abstract public function setTimeZone($timezone);

    /**
     * Returns database object instance.
     *
     * @param bool $master Decides whether the connection to be returned by the master server or the slave server
     * @return Db Singleton instance of Db object
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getInstance($master = true)
    {
        static $id = 0;

        // This MUST not be declared with the class members because some defines (like _DB_SERVER_) may not exist yet (the constructor can be called directly with params)
        if (!static::$_servers) {
            static::$_servers = [
                ['server' => _DB_SERVER_, 'user' => _DB_USER_, 'password' => _DB_PASSWD_, 'database' => _DB_NAME_], /* MySQL Master server */
            ];
        }

        if (!$master) {
            static::loadSlaveServers();
        }

        $totalServers = count(static::$_servers);
        if ($master || $totalServers == 1) {
            $idServer = 0;
        } else {
            $id++;
            $idServer = ($totalServers > 2 && ($id % $totalServers) != 0) ? $id % $totalServers : 1;
        }

        if (!isset(static::$instance[$idServer])) {
            $class = static::getClass();
            static::$instance[$idServer] = new $class(
                static::$_servers[$idServer]['server'],
                static::$_servers[$idServer]['user'],
                static::$_servers[$idServer]['password'],
                static::$_servers[$idServer]['database']
            );
            $connection = static::$instance[$idServer];
            if (! Configuration::configurationIsLoaded()) {
                Configuration::loadConfigurationFromDB($connection);
            }
            $connection->setTimeZone(Tools::getTimeZone());
        }

        return static::$instance[$idServer];
    }

    /**
     * @param $testDb Db
     * Unit testing purpose only
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function setInstanceForTesting($testDb)
    {
        static::$instance[0] = $testDb;
    }

    /**
     * Unit testing purpose only
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function deleteTestingInstance()
    {
        static::$instance = [];
    }

    /**
     * Loads configuration settings for slave servers if needed.
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function loadSlaveServers()
    {
        if (static::$_slave_servers_loaded !== null) {
            return;
        }

        // Add here your slave(s) server(s) in this file
        if (file_exists(_PS_ROOT_DIR_.'/config/db_slave_server.inc.php')) {
            static::$_servers = array_merge(static::$_servers, require(_PS_ROOT_DIR_.'/config/db_slave_server.inc.php'));
        }

        static::$_slave_servers_loaded = true;
    }

    /**
     * Returns the best child layer database class.
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getClass()
    {
        return 'DbPDO';
    }

    /**
     * Instantiates a database connection
     *
     * @param string $server   Server address
     * @param string $user     User login
     * @param string $password User password
     * @param string $database Database name
     * @param bool   $connect  If false, don't connect in constructor (since 1.5.0.1)
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($server, $user, $password, $database, $connect = true)
    {
        $this->server = $server;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;

        if (!defined('_PS_DEBUG_SQL_')) {
            define('_PS_DEBUG_SQL_', false);
        }

        if ($connect) {
            $this->connect();
        }
    }

    /**
     * Disable the use of the cache
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    public function disableCache()
    {
        $this->is_cache_enabled = false;
    }

    /**
     * Enable & flush the cache
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    public function enableCache()
    {
        $this->is_cache_enabled = false;
    }

    /**
     * Closes connection to database
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function __destruct()
    {
        if ($this->link) {
            $this->disconnect();
        }
    }

    /**
     * Executes SQL query based on selected type
     *
     * @deprecated 1.5.0.1 Use insert() or update() method instead.
     *
     * @param string $table
     * @param array  $data
     * @param string $type     (INSERT, INSERT IGNORE, REPLACE, UPDATE).
     * @param string $where
     * @param int    $limit
     * @param bool   $useCache
     * @param bool   $useNull
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function autoExecute($table, $data, $type, $where = '', $limit = 0, $useCache = true, $useNull = false)
    {
        $type = strtoupper($type);
        switch ($type) {
            case 'INSERT':
                return $this->insert($table, $data, $useNull, $useCache, static::INSERT, false);

            case 'INSERT IGNORE':
                return $this->insert($table, $data, $useNull, $useCache, static::INSERT_IGNORE, false);

            case 'REPLACE':
                return $this->insert($table, $data, $useNull, $useCache, static::REPLACE, false);

            case 'UPDATE':
                return $this->update($table, $data, $where, $limit, $useNull, $useCache, false);

            default:
                throw new PrestaShopDatabaseException('Wrong argument (miss type) in static::autoExecute()');
        }
    }

    /**
     * Filter SQL query within a blacklist
     *
     * @param string $table  Table where insert/update data
     * @param array  $values Data to insert/update
     * @param string $type   INSERT or UPDATE
     * @param string $where  WHERE clause, only for UPDATE (optional)
     * @param int    $limit  LIMIT clause (optional)
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function autoExecuteWithNullValues($table, $values, $type, $where = '', $limit = 0)
    {
        return $this->autoExecute($table, $values, $type, $where, $limit, 0, true);
    }

    /**
     * Execute a query and get result resource
     *
     * @param string|DbQuery $sql
     *
     * @return bool|PDOStatement
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function query($sql)
    {
        if ($sql instanceof DbQuery) {
            $sql = $sql->build();
        }

        $this->result = $this->_query($sql);

        if (!$this->result && $this->getNumberError() == 2006) {
            if ($this->connect()) {
                $this->result = $this->_query($sql);
            }
        }

        if (_PS_DEBUG_SQL_) {
            $this->displayError($sql);
        }

        return $this->result;
    }

    /**
     * Executes an INSERT query
     *
     * @param string $table      Table name without prefix
     * @param array  $data       Data to insert as associative array. If $data is a list of arrays, multiple insert will be done
     * @param bool   $nullValues If we want to use NULL values instead of empty quotes
     * @param bool   $useCache
     * @param int    $type       Must be static::INSERT or static::INSERT_IGNORE or static::REPLACE
     * @param bool   $addPrefix  Add or not _DB_PREFIX_ before table name
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function insert($table, $data, $nullValues = false, $useCache = true, $type = self::INSERT, $addPrefix = true)
    {
        if (!$data && !$nullValues) {
            return true;
        }

        if ($addPrefix && _DB_PREFIX_ && strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_.$table;
        }

        if ($type == static::INSERT) {
            $insertKeyword = 'INSERT';
        } elseif ($type == static::INSERT_IGNORE) {
            $insertKeyword = 'INSERT IGNORE';
        } elseif ($type == static::REPLACE) {
            $insertKeyword = 'REPLACE';
        } elseif ($type == static::ON_DUPLICATE_KEY) {
            $insertKeyword = 'INSERT';
        } else {
            throw new PrestaShopDatabaseException('Bad keyword, must be static::INSERT or static::INSERT_IGNORE or static::REPLACE');
        }

        // Check if $data is a list of row
        $current = current($data);
        if (!is_array($current) || isset($current['type'])) {
            $data = [$data];
        }

        $keys = [];
        $valuesStringified = [];
        $firstLoop = true;
        $duplicateKeyStringified = '';
        foreach ($data as $rowData) {
            $values = [];
            foreach ($rowData as $key => $value) {
                if (!$firstLoop) {
                    // Check if row array mapping are the same
                    if (!in_array("`$key`", $keys)) {
                        throw new PrestaShopDatabaseException('Keys form $data subarray don\'t match');
                    }

                    if ($duplicateKeyStringified != '') {
                        throw new PrestaShopDatabaseException('On duplicate key cannot be used on insert with more than 1 VALUE group');
                    }
                } else {
                    $keys[] = '`'.bqSQL($key).'`';
                }

                if (!is_array($value)) {
                    $value = ['type' => 'text', 'value' => $value];
                }
                if ($value['type'] == 'sql') {
                    $values[] = $stringValue = $value['value'];
                } else {
                    $values[] = $stringValue = $nullValues && ($value['value'] === '' || is_null($value['value'])) ? 'NULL' : "'{$value['value']}'";
                }

                if ($type == static::ON_DUPLICATE_KEY) {
                    $duplicateKeyStringified .= '`'.bqSQL($key).'` = '.$stringValue.',';
                }
            }
            $firstLoop = false;
            $valuesStringified[] = '('.implode(', ', $values).')';
        }
        $keysStringified = implode(', ', $keys);

        $sql = $insertKeyword.' INTO `'.$table.'` ('.$keysStringified.') VALUES '.implode(', ', $valuesStringified);
        if ($type == static::ON_DUPLICATE_KEY) {
            $sql .= ' ON DUPLICATE KEY UPDATE '.substr($duplicateKeyStringified, 0, -1);
        }

        return (bool) $this->q($sql, $useCache);
    }

    /**
     * Executes an UPDATE query
     *
     * @param string $table      Table name without prefix
     * @param array  $data       Data to insert as associative array. If $data is a list of arrays, multiple insert will be done
     * @param string $where      WHERE condition
     * @param int    $limit
     * @param bool   $nullValues If we want to use NULL values instead of empty quotes
     * @param bool   $useCache
     * @param bool   $addPrefix  Add or not _DB_PREFIX_ before table name
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($table, $data, $where = '', $limit = 0, $nullValues = false, $useCache = true, $addPrefix = true)
    {
        if (!$data) {
            return true;
        }

        if ($addPrefix && strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_.$table;
        }

        $sql = 'UPDATE `'.bqSQL($table).'` SET ';
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $value = ['type' => 'text', 'value' => $value];
            }
            if ($value['type'] == 'sql') {
                $sql .= '`'.bqSQL($key)."` = {$value['value']},";
            } else {
                $sql .= ($nullValues && ($value['value'] === '' || is_null($value['value']))) ? '`'.bqSQL($key).'` = NULL,' : '`'.bqSQL($key)."` = '{$value['value']}',";
            }
        }

        $sql = rtrim($sql, ',');
        if ($where) {
            $sql .= ' WHERE '.$where;
        }
        if ($limit) {
            $sql .= ' LIMIT '.(int) $limit;
        }

        return (bool) $this->q($sql, $useCache);
    }

    /**
     * Executes a DELETE query
     *
     * @param string $table     Name of the table to delete
     * @param string $where     WHERE clause on query
     * @param int    $limit     Number max of rows to delete
     * @param bool   $useCache  Use cache or not
     * @param bool   $addPrefix Add or not _DB_PREFIX_ before table name
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete($table, $where = '', $limit = 0, $useCache = true, $addPrefix = true)
    {
        if ($addPrefix && strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_.$table;
        }

        $this->result = false;
        $sql = 'DELETE FROM `'.bqSQL($table).'`'.($where ? ' WHERE '.$where : '').($limit ? ' LIMIT '.(int) $limit : '');
        $res = $this->query($sql);

        return (bool) $res;
    }

    /**
     * Executes a query
     *
     * @param string|DbQuery $sql
     * @param bool           $useCache
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function execute($sql, $useCache = true)
    {
        if ($sql instanceof DbQuery) {
            $sql = $sql->build();
        }

        $this->result = $this->query($sql);

        return (bool) $this->result;
    }

    /**
     * Executes return the result of $sql as array
     *
     * @param string|DbQuery $sql      Query to execute
     * @param bool           $array    Return an array instead of a result object (deprecated since 1.5.0.1, use query method instead)
     * @param bool           $useCache Deprecated, the internal query cache is no longer used
     *
     * @return array|false|null|PDOStatement
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function executeS($sql, $array = true, $useCache = true)
    {
        if ($sql instanceof DbQuery) {
            $sql = $sql->build();
        }

        $this->result = false;
        $this->last_query = $sql;

        // This method must be used only with queries which display results
        if (!preg_match('#^\s*\(?\s*(select|show|explain|describe|desc)\s#i', $sql)) {
            if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
                throw new PrestaShopDatabaseException('Db->executeS() must be used only with select, show, explain or describe queries', $sql);
            }

            return $this->execute($sql, $useCache);
        }

        $this->result = $this->query($sql);

        if (!$this->result) {
            $result = false;
        } else {
            if (!$array) {
                $result = $this->result;
            } else {
                $result = $this->getAll($this->result);
            }
        }

        $this->last_cached = false;

        return $result;
    }

    /**
     * Returns an associative array containing the first row of the query
     * This function automatically adds "LIMIT 1" to the query
     *
     * @param string|DbQuery $sql      the select query (without "LIMIT 1")
     * @param bool           $useCache Deprecated, the internal query cache is no longer used
     *
     * @return array|bool|object|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getRow($sql, $useCache = true)
    {
        if ($sql instanceof DbQuery) {
            $sql = $sql->build();
        }

        $sql = rtrim($sql, " \t\n\r\0\x0B;").' LIMIT 1';
        $this->result = false;
        $this->last_query = $sql;

        $this->result = $this->query($sql);
        if (!$this->result) {
            $result = false;
        } else {
            $result = $this->nextRow($this->result);
        }

        $this->last_cached = false;

        if (is_null($result)) {
            $result = false;
        }

        return $result;
    }

    /**
     * Returns a value from the first row, first column of a SELECT query
     *
     * @param string|DbQuery $sql
     * @param bool           $useCache Deprecated, the internal query cache is no longer used
     *
     * @return string|false|null
     * @throws PrestaShopException
     */
    public function getValue($sql, $useCache = true)
    {
        if ($sql instanceof DbQuery) {
            $sql = $sql->build();
        }

        if (!$result = $this->getRow($sql, $useCache)) {
            return false;
        }

        return array_shift($result);
    }

    /**
     * Get number of rows for last result
     *
     * @return int
     * @throws PrestaShopException
     */
    public function numRows()
    {
        if (!$this->last_cached && $this->result) {
            $nrows = $this->_numRows($this->result);

            return $nrows;
        }

        return 0;
    }

    /**
     * Executes a query
     *
     * @param string|DbQuery $sql
     * @param bool           $useCache Deprecated, the internal query cache is no longer used
     *
     * @return bool|PDOStatement
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function q($sql, $useCache = true)
    {
        if ($sql instanceof DbQuery) {
            $sql = $sql->build();
        }

        $this->result = false;
        $result = $this->query($sql);

        if (_PS_DEBUG_SQL_) {
            $this->displayError($sql);
        }

        return $result;
    }

    /**
     * Displays last SQL error
     *
     * @param string|bool $sql
     * @throws PrestaShopDatabaseException
     */
    public function displayError($sql = false)
    {
        global $webserviceCall;

        $errno = $this->getNumberError();
        if ($webserviceCall && $errno) {
            $dbg = debug_backtrace();
            WebserviceRequest::getInstance()->setError(500, '[SQL Error] '.$this->getMsgError().'. From '.(isset($dbg[3]['class']) ? $dbg[3]['class'] : '').'->'.$dbg[3]['function'].'() Query was : '.$sql, 97);
        } elseif (_PS_DEBUG_SQL_ && $errno && !defined('TB_INSTALLATION_IN_PROGRESS')) {
            if ($sql) {
                throw new PrestaShopDatabaseException($this->getMsgError(), $sql);
            }

            throw new PrestaShopDatabaseException($this->getMsgError());
        }
    }

    /**
     * Sanitize data which will be injected into SQL query
     *
     * @param string $string SQL data which will be injected into SQL query
     * @param bool   $htmlOk Does data contain HTML code ? (optional)
     * @param bool   $bqSql  Escape backquotes
     *
     * @return string Sanitized data
     */
    public function escape($string, $htmlOk = false, $bqSql = false)
    {
        if (_PS_MAGIC_QUOTES_GPC_) {
            $string = stripslashes($string);
        }

        if (!is_numeric($string)) {
            $string = $this->_escape($string);

            if (!$htmlOk) {
                $string = strip_tags(Tools::nl2br($string));
            }

            if ($bqSql === true) {
                $string = str_replace('`', '\`', $string);
            }
        }

        return $string;
    }

    /**
     * Try a connection to the database
     *
     * @param string      $server Server address
     * @param string      $user Login for database connection
     * @param string      $pwd Password for database connection
     * @param string      $db Database name
     * @param bool        $newDbLink
     * @param string|bool $engine
     * @param int         $timeout
     *
     * @return int Error code or 0 if connection was successful
     */
    public static function checkConnection($server, $user, $pwd, $db, $newDbLink = true, $engine = null, $timeout = 5)
    {
        return call_user_func_array([static::getClass(), 'tryToConnect'], [$server, $user, $pwd, $db, $newDbLink, $engine, $timeout]);
    }

    /**
     * Try a connection to the database and set names to UTF-8
     *
     * @param string $server Server address
     * @param string $user Login for database connection
     * @param string $pwd Password for database connection
     *
     * @return bool
     */
    public static function checkEncoding($server, $user, $pwd)
    {
        return call_user_func_array([static::getClass(), 'tryUTF8'], [$server, $user, $pwd]);
    }

    /**
     * Try a connection to the database and check if at least one table with same prefix exists
     *
     * @param string $server Server address
     * @param string $user Login for database connection
     * @param string $pwd Password for database connection
     * @param string $db Database name
     * @param string $prefix Tables prefix
     *
     * @return bool
     */
    public static function hasTableWithSamePrefix($server, $user, $pwd, $db, $prefix)
    {
        return call_user_func_array([static::getClass(), 'hasTableWithSamePrefix'], [$server, $user, $pwd, $db, $prefix]);
    }

    /**
     * Tries to connect to the database and create a table (checking creation privileges)
     *
     * @param string $server
     * @param string $user
     * @param string $pwd
     * @param string $db
     * @param string $prefix
     * @param string|null $engine Table engine
     *
     * @return bool|string True, false or error
     */
    public static function checkCreatePrivilege($server, $user, $pwd, $db, $prefix, $engine = null)
    {
        return call_user_func_array([static::getClass(), 'checkCreatePrivilege'], [$server, $user, $pwd, $db, $prefix, $engine]);
    }

    /**
     * Checks if auto increment value and offset is 1
     *
     * @param string $server
     * @param string $user
     * @param string $pwd
     *
     * @return bool
     */
    public static function checkAutoIncrement($server, $user, $pwd)
    {
        return call_user_func_array([static::getClass(), 'checkAutoIncrement'], [$server, $user, $pwd]);
    }

    /**
     * Executes a query
     *
     * @param string|DbQuery $sql
     * @param bool           $useCache
     *
     * @return array|bool|PDOStatement
     * @throws PrestaShopDatabaseException
     *
     * @deprecated 2.0.0
     * @throws PrestaShopException
     */
    public static function s($sql, $useCache = true)
    {
        Tools::displayAsDeprecated();

        return static::getInstance()->executeS($sql, true, $useCache);
    }

    /**
     * Executes a query
     *
     * @param string $sql
     * @param int $useCache
     * @return array|bool|PDOStatement
     *
     * @deprecated 2.0.0
     *
     * @throws PrestaShopException
     */
    public static function ps($sql, $useCache = 1)
    {
        Tools::displayAsDeprecated();
        $ret = static::s($sql, $useCache);

        return $ret;
    }

    /**
     * Executes a query and kills process (dies)
     *
     * @param string $sql
     * @param int $useCache
     *
     * @deprecated 2.0.0
     *
     * @throws PrestaShopException
     */
    public static function ds($sql, $useCache = 1)
    {
        Tools::displayAsDeprecated();
        static::s($sql, $useCache);
        die();
    }

    /**
     * Get used link instance
     *
     * @return PDO Resource
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLink()
    {
        return $this->link;
    }
}
