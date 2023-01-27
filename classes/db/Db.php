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

if (file_exists(_PS_ROOT_DIR_ . '/config/settings.inc.php')) {
    include_once(_PS_ROOT_DIR_ . '/config/settings.inc.php');
}

/**
 * Class DbCore
 */
class DbCore
{
    /**
     * @var int Constant used by insert() method
     */
    const INSERT = 1;

    /**
     * @var int Constant used by insert() method
     */
    const INSERT_IGNORE = 2;

    /**
     * @var int Constant used by insert() method
     */
    const REPLACE = 3;

    /**
     * @var int Constant used by insert() method
     */
    const ON_DUPLICATE_KEY = 4;

    /**
     * @var array List of DB instances
     */
    public static $instance = [];

    /**
     * @var array List of server settings
     */
    public static $_servers = [];

    /**
     * @var bool|null Flag used to load slave servers only once
     */
    public static $_slave_servers_loaded = null;

    /**
     * @var string Server (eg. localhost)
     */
    protected $server;

    /**
     * @var string Database user (eg. root)
     */
    protected $user;

    /**
     * @var string Database password (eg. can be empty !)
     */
    protected $password;

    /**
     * @var string Database name
     */
    protected $database;

    /**
     * @var boolean
     */
    protected $throwOnError;

    /**
     * @var PDO Resource link
     */
    protected $link;

    /**
     * @var PDOStatement|false SQL cached result
     */
    protected $result;

    /**
     * Store last executed query
     *
     * @var string
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
    protected $last_cached = false;

    /**
     * @var bool
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    protected $is_cache_enabled = false;

    /**
     * Instantiates a database connection
     *
     * @param string $server Server address
     * @param string $user User login
     * @param string $password User password
     * @param string $database Database name
     * @param bool $connect If false, don't connect in constructor (since 1.5.0.1)
     */
    public function __construct($server, $user, $password, $database, $connect = true)
    {
        $this->server = $server;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->throwOnError = defined('_PS_DEBUG_SQL_') && _PS_DEBUG_SQL_;

        if ($connect) {
            $this->connect();
        }
    }

    /**
     * Opens a database connection
     *
     * @return PDO
     */
    public function connect()
    {
        try {
            $this->link = $this->_getPDO($this->server, $this->user, $this->password, $this->database, 5);
        } catch (PDOException $e) {
            die(sprintf(Tools::displayError('Link to database cannot be established: %s'), $e->getMessage()));
        }

        // UTF-8 support
        if ($this->link->exec('SET NAMES \'utf8mb4\'') === false) {
            die(Tools::displayError('thirty bees Fatal error: no UTF-8 support. Please check your server configuration.'));
        }

        $this->link->exec('SET SESSION sql_mode = \'\'');

        return $this->link;
    }

    /**
     * Returns a new PDO object (database link)
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dbname
     * @param int $timeout
     *
     * @return PDO
     */
    protected static function _getPDO($host, $user, $password, $dbname, $timeout = 5)
    {
        $dsn = 'mysql:';
        if ($dbname) {
            $dsn .= 'dbname=' . $dbname . ';';
        }
        if (preg_match('/^(.*):([0-9]+)$/', $host, $matches)) {
            $dsn .= 'host=' . $matches[1] . ';port=' . $matches[2];
        } elseif (preg_match('#^.*:(/.*)$#', $host, $matches)) {
            $dsn .= 'unix_socket=' . $matches[1];
        } else {
            $dsn .= 'host=' . $host;
        }

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_TIMEOUT => $timeout,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_STRINGIFY_FETCHES => true,
        ]);
    }

    /**
     * Displays last SQL error
     *
     * @param string|bool $sql
     *
     * @throws PrestaShopDatabaseException
     */
    public function displayError($sql = false)
    {
        $errno = $this->getNumberError();
        if ($errno) {
            throw new PrestaShopDatabaseException($this->getMsgError(), $sql);
        }
    }

    /**
     * Returns the number of the error from previous database operation
     *
     * @return int
     */
    public function getNumberError()
    {
        $error = $this->link->errorInfo();

        return isset($error[1]) ? $error[1] : 0;
    }

    /**
     * Returns the text of the error message from previous database operation
     *
     * @param bool $query
     *
     * @return string
     */
    public function getMsgError($query = false)
    {
        $error = $this->link->errorInfo();

        return ($error[0] == '00000') ? '' : $error[2];
    }

    /**
     * Try a connection to the database
     *
     * @param string $server Server address
     * @param string $user Login for database connection
     * @param string $pwd Password for database connection
     * @param string $db Database name
     * @param bool $newDbLink
     * @param string|bool $engine
     * @param int $timeout
     *
     * @return int Error code or 0 if connection was successful
     *
     */
    public static function tryToConnect($server, $user, $pwd, $db, $newDbLink = true, $engine = null, $timeout = 5)
    {
        try {
            $link = static::_getPDO($server, $user, $pwd, $db, $timeout);
        } catch (PDOException $e) {
            // hhvm wrongly reports error status 42000 when the database does not exist - might change in the future
            return ($e->getCode() == 1049 || (defined('HHVM_VERSION') && $e->getCode() == 42000)) ? 2 : 1;
        }
        unset($link);

        return 0;
    }

    /**
     * Tries to connect and create a new database
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dbname
     * @param bool $dropAfter If true, drops the created database.
     *
     * @return bool
     */
    public static function createDatabase($host, $user, $password, $dbname, $dropAfter = false)
    {
        try {
            $link = static::_getPDO($host, $user, $password, false);
            $escapedName = str_replace('`', '\\`', $dbname);
            $createDbDDL = 'CREATE DATABASE `' . $escapedName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
            $success = $link->exec($createDbDDL);
            if ($dropAfter && ($link->exec('DROP DATABASE `' . $escapedName . '`') !== false)) {
                return true;
            }
        } catch (PDOException $e) {
            return false;
        }

        return $success;
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
    public static function tryUTF8($server, $user, $pwd)
    {
        try {
            $link = static::_getPDO($server, $user, $pwd, false, 5);
        } catch (PDOException $e) {
            return false;
        }
        $result = $link->exec('SET NAMES \'utf8mb4\'');
        unset($link);

        return $result !== false;
    }

    /**
     * @param Db $testDb
     * Unit testing purpose only
     */
    public static function setInstanceForTesting($testDb)
    {
        static::$instance[0] = $testDb;
    }

    /**
     * Unit testing purpose only
     *
     * @return void
     */
    public static function deleteTestingInstance()
    {
        static::$instance = [];
    }

    /**
     * Try a connection to the database
     *
     * @param string $server Server address
     * @param string $user Login for database connection
     * @param string $pwd Password for database connection
     * @param string $db Database name
     * @param bool $newDbLink
     * @param string|bool $engine
     * @param int $timeout
     *
     * @return int Error code or 0 if connection was successful
     */
    public static function checkConnection($server, $user, $pwd, $db, $newDbLink = true, $engine = null, $timeout = 5)
    {
        return static::tryToConnect($server, $user, $pwd, $db, $newDbLink, $engine, $timeout);
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
        return static::tryUTF8($server, $user, $pwd);
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
        try {
            $link = static::_getPDO($server, $user, $pwd, $db, 5);
        } catch (PDOException $e) {
            return false;
        }

        $sql = 'SHOW TABLES LIKE \'' . $prefix . '%\'';
        $result = $link->query($sql);

        return (bool)$result->fetch();
    }

    /**
     * Execute a query and get result resource
     *
     * @param string|DbQuery $sql
     *
     * @return PDOStatement|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function query($sql)
    {
        if ($sql instanceof DbQuery) {
            $sql = $sql->build();
        }

        $this->result = $this->link->query($sql);

        if ($this->result === false && $this->getNumberError() == 2006) {
            if ($this->connect()) {
                $this->result = $this->link->query($sql);
            }
        }

        if ($this->result === false && $this->throwOnError) {
            $this->displayError($sql);
        }

        return $this->result;
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
        try {
            $link = static::_getPDO($server, $user, $pwd, $db, 5);
        } catch (PDOException $e) {
            return false;
        }

        if ($engine === null) {
            $engine = 'InnoDB';
        }

        $result = $link->query('
		CREATE TABLE `' . $prefix . 'test` (
			`test` tinyint(1) unsigned NOT NULL
		) ENGINE=' . $engine);
        if (!$result) {
            $error = $link->errorInfo();

            return $error[2];
        }
        $link->query('DROP TABLE `' . $prefix . 'test`');

        return true;
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
        try {
            $link = static::_getPDO($server, $user, $pwd, false, 5);
        } catch (PDOException $e) {
            return false;
        }
        $ret = (bool)(($result = $link->query('SELECT @@auto_increment_increment as aii')) && ($row = $result->fetch()) && $row['aii'] == 1);
        $ret = ($result = $link->query('SELECT @@auto_increment_offset as aio')) && ($row = $result->fetch()) && $row['aio'] == 1 && $ret;
        unset($link);

        return $ret;
    }

    /**
     * Executes return the result of $sql as array
     *
     * @param string|DbQuery $sql Query to execute
     * @param bool $array Return an array instead of a result object (deprecated since 1.5.0.1, use query method instead)
     * @param bool $useCache Deprecated, the internal query cache is no longer used
     *
     * @return array|bool|PDOStatement
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
            if ($this->throwOnError) {
                throw new PrestaShopDatabaseException("Db::executeS method should be used for SELECT queries only.", $sql);
            } else {
                $callPoint = Tools::getCallPoint([Db::class]);
                $error = 'Db::executeS method should be used for SELECT queries only. ';
                $error .= 'Calling this method with other SQL statements will raise exception in the future. ';
                $error .= 'Called from: ' . $callPoint['class'] . '::' . $callPoint['function'] . '() in ' . $callPoint['file'] . ':' . $callPoint['line'] . '. ';
                $error .= 'Illegal SQL: [' . $sql . ']';
                trigger_error($error, E_USER_DEPRECATED);
                return $this->execute($sql, $useCache);
            }
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

        return $result;
    }

    /**
     * Executes a query
     *
     * @param string|DbQuery $sql
     * @param bool $useCache
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

        return (bool)$this->result;
    }

    /**
     * Returns all rows from the result set.
     *
     * @param bool $result
     *
     * @return array|false|null
     */
    protected function getAll($result = false)
    {
        if (!$result) {
            $result = $this->result;
        }

        if (!is_object($result)) {
            return false;
        }

        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns database object instance.
     *
     * @param bool $master Decides whether the connection to be returned by the master server or the slave server
     *
     * @return Db Singleton instance of Db object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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

        if (!isset(static::$instance[$idServer]) || !static::$instance[$idServer]->link) {
            static::$instance[$idServer] = static::createInstance(
                static::$_servers[$idServer]['server'],
                static::$_servers[$idServer]['user'],
                static::$_servers[$idServer]['password'],
                static::$_servers[$idServer]['database']
            );
            $connection = static::$instance[$idServer];
            if (!Configuration::configurationIsLoaded()) {
                Configuration::loadConfigurationFromDB($connection);
            }
            $connection->setTimeZone(Tools::getTimeZone());
        }

        return static::$instance[$idServer];
    }

    /**
     * Loads configuration settings for slave servers if needed.
     *
     * @return void
     */
    protected static function loadSlaveServers()
    {
        if (static::$_slave_servers_loaded !== null) {
            return;
        }

        // Add here your slave(s) server(s) in this file
        if (file_exists(_PS_ROOT_DIR_ . '/config/db_slave_server.inc.php')) {
            static::$_servers = array_merge(static::$_servers, require(_PS_ROOT_DIR_ . '/config/db_slave_server.inc.php'));
        }

        static::$_slave_servers_loaded = true;
    }

    /**
     * Creates new database object instance.
     *
     * @param string $server
     * @param string $user
     * @param string $password
     * @param string $database
     *
     * @return Db
     */
    public static function createInstance($server, $user, $password, $database)
    {
        return new Db($server, $user, $password, $database);
    }

    /**
     * Set timezone on current connection.
     *
     * @param string $timezone
     *
     * @return void
     */
    public function setTimeZone($timezone)
    {
        try {
            $now = new DateTime('now', new DateTimeZone($timezone));
            $minutes = $now->getOffset() / 60;
            $sign = ($minutes < 0 ? -1 : 1);
            $minutes = abs($minutes);
            $hours = floor($minutes / 60);
            $minutes -= $hours * 60;
            $offset = sprintf('%+d:%02d', $hours * $sign, $minutes);
            $this->link->exec("SET time_zone='$offset'");
        } catch (Exception $e) {
            throw new RuntimeException("Failed to set timezone", 0, $e);
        }
    }

    /**
     * Returns ID of the last inserted row.
     *
     * @return string|false
     */
    public function Insert_ID()
    {
        return $this->link->lastInsertId();
    }

    /**
     * Return the number of rows affected by the last SQL query.
     *
     * @return int
     */
    public function Affected_Rows()
    {
        return $this->result->rowCount();
    }

    /**
     * Returns database server version.
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getVersion()
    {
        return $this->getValue('SELECT VERSION()');
    }

    /**
     * Returns a value from the first row, first column of a SELECT query
     *
     * @param string|DbQuery $sql
     * @param bool $useCache Deprecated, the internal query cache is no longer used
     *
     * @return mixed|false
     * @throws PrestaShopException
     */
    public function getValue($sql, $useCache = true)
    {
        if (!$result = $this->getRow($sql, $useCache)) {
            return false;
        }

        return array_shift($result);
    }

    /**
     * Returns an associative array containing the first row of the query
     * This function automatically adds "LIMIT 1" to the query
     *
     * @param string|DbQuery $sql the select query (without "LIMIT 1")
     * @param bool $useCache Deprecated, the internal query cache is no longer used
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getRow($sql, $useCache = true)
    {
        if ($sql instanceof DbQuery) {
            $sql = $sql->build();
        }

        $sql = rtrim($sql, " \t\n\r\0\x0B;") . ' LIMIT 1';
        $this->result = false;
        $this->last_query = $sql;

        $this->result = $this->query($sql);
        if (!$this->result) {
            $result = false;
        } else {
            $result = $this->nextRow($this->result);
        }

        return is_array($result)
            ? $result
            : false;
    }

    /**
     * Returns the next row from the result set.
     *
     * @param PDOStatement|false $result
     *
     * @return array|false|null
     */
    public function nextRow($result = false)
    {
        if (!$result) {
            $result = $this->result;
        }

        if (!is_object($result)) {
            return false;
        }

        return $result->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Sets the current active database on the server that's associated with the specified link identifier.
     * Do not remove, useful for some modules.
     *
     * @param string $dbName
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_db($dbName)
    {
        return $this->link->exec('USE ' . pSQL($dbName));
    }

    /**
     * Selects best table engine.
     *
     * @return string
     */
    public function getBestEngine()
    {
        return 'InnoDB';
    }

    /**
     * Closes connection to database
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->link) {
            $this->disconnect();
        }
    }

    /**
     * Destroys the database connection link
     */
    public function disconnect()
    {
        unset($this->link);
    }

    /**
     * Filter SQL query within a blacklist
     *
     * @param string $table Table where insert/update data
     * @param array $values Data to insert/update
     * @param string $type INSERT or UPDATE
     * @param string $where WHERE clause, only for UPDATE (optional)
     * @param int $limit LIMIT clause (optional)
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function autoExecuteWithNullValues($table, $values, $type, $where = '', $limit = 0)
    {
        return $this->autoExecute($table, $values, $type, $where, $limit, 0, true);
    }

    /**
     * Executes SQL query based on selected type
     *
     * @param string $table
     * @param array $data
     * @param string $type (INSERT, INSERT IGNORE, REPLACE, UPDATE).
     * @param string $where
     * @param int $limit
     * @param bool $useCache
     * @param bool $useNull
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * Executes an INSERT query
     *
     * @param string $table Table name without prefix
     * @param array $data Data to insert as associative array. If $data is a list of arrays, multiple insert will be done
     * @param bool $nullValues If we want to use NULL values instead of empty quotes
     * @param bool $useCache
     * @param int $type Must be static::INSERT or static::INSERT_IGNORE or static::REPLACE
     * @param bool $addPrefix Add or not _DB_PREFIX_ before table name
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function insert($table, $data, $nullValues = false, $useCache = true, $type = self::INSERT, $addPrefix = true)
    {
        if (!$data && !$nullValues) {
            return true;
        }

        if ($addPrefix && _DB_PREFIX_ && strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_ . $table;
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
                    $keys[] = '`' . bqSQL($key) . '`';
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
                    $duplicateKeyStringified .= '`' . bqSQL($key) . '` = ' . $stringValue . ',';
                }
            }
            $firstLoop = false;
            $valuesStringified[] = '(' . implode(', ', $values) . ')';
        }
        $keysStringified = implode(', ', $keys);

        $sql = $insertKeyword . ' INTO `' . $table . '` (' . $keysStringified . ') VALUES ' . implode(', ', $valuesStringified);
        if ($type == static::ON_DUPLICATE_KEY) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . substr($duplicateKeyStringified, 0, -1);
        }

        return (bool)$this->query($sql);
    }

    /**
     * Executes an UPDATE query
     *
     * @param string $table Table name without prefix
     * @param array $data Data to insert as associative array. If $data is a list of arrays, multiple insert will be done
     * @param string|array $where WHERE condition
     * @param int $limit
     * @param bool $nullValues If we want to use NULL values instead of empty quotes
     * @param bool $useCache
     * @param bool $addPrefix Add or not _DB_PREFIX_ before table name
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
            $table = _DB_PREFIX_ . $table;
        }

        if (is_array($where)) {
            $where = implode(' AND ', array_filter($where));
        }

        $sql = 'UPDATE `' . bqSQL($table) . '` SET ';
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $value = ['type' => 'text', 'value' => $value];
            }
            if ($value['type'] == 'sql') {
                $sql .= '`' . bqSQL($key) . "` = {$value['value']},";
            } else {
                $sql .= ($nullValues && ($value['value'] === '' || is_null($value['value']))) ? '`' . bqSQL($key) . '` = NULL,' : '`' . bqSQL($key) . "` = '{$value['value']}',";
            }
        }

        $sql = rtrim($sql, ',');
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }
        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        return (bool)$this->query($sql);
    }

    /**
     * Executes a DELETE query
     *
     * @param string $table Name of the table to delete
     * @param string|array $where WHERE clause on query
     * @param int $limit Number max of rows to delete
     * @param bool $useCache Use cache or not
     * @param bool $addPrefix Add or not _DB_PREFIX_ before table name
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete($table, $where = '', $limit = 0, $useCache = true, $addPrefix = true)
    {
        if ($addPrefix && strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_ . $table;
        }

        if (is_array($where)) {
            $where = implode(' AND ', array_filter($where));
        }

        $this->result = false;
        $sql = 'DELETE FROM `' . bqSQL($table) . '`' . ($where ? ' WHERE ' . $where : '') . ($limit ? ' LIMIT ' . (int)$limit : '');
        $res = $this->query($sql);

        return (bool)$res;
    }

    /**
     * Executes sql and returns the result of $sql as an array
     *
     * @param string|DbQuery $sql the select query
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getArray($sql)
    {
        $result = $this->executeS($sql);
        return is_array($result)
            ? $result
            : [];
    }

    /**
     * Get number of rows for last result
     *
     * @return int
     */
    public function numRows()
    {
        if ($this->result) {
            return $this->result->rowCount();
        }

        return 0;
    }

    /**
     * Sanitize data which will be injected into SQL query
     *
     * @param string $string SQL data which will be injected into SQL query
     * @param bool $htmlOk Does data contain HTML code ? (optional)
     * @param bool $bqSql Escape backquotes
     *
     * @return string Sanitized data
     */
    public function escape($string, $htmlOk = false, $bqSql = false)
    {
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
     * Escapes illegal characters in a string. Protect string against SQL injections
     *
     * @param string $str
     *
     * @return string
     */
    public function _escape($str)
    {
        if (is_null($str)) {
            return '';
        }

        $search = ["\\", "\0", "\n", "\r", "\x1a", "'", '"'];
        $replace = ["\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"'];

        return str_replace($search, $replace, $str);
    }

    /**
     * Get used link instance
     *
     * @return PDO Resource
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Disable the use of the cache
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    public function disableCache()
    {
    }

    /**
     * Enable & flush the cache
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    public function enableCache()
    {
    }

    /**
     * Returns database class
     *
     * @return string
     *
     * @deprecated 1.5.0
     */
    public static function getClass()
    {
        return 'Db';
    }

    /**
     * Get number of rows in a result
     *
     * @param PDOStatement $result
     *
     * @return int
     *
     * @deprecated 1.5.0
     */
    protected function _numRows($result)
    {
        return $result->rowCount();
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object or true/false.
     *
     * @param string $sql
     *
     * @return PDOStatement|false
     *
     * @deprecated 1.5.0
     */
    protected function _query($sql)
    {
        return $this->link->query($sql);
    }

    /**
     * Executes a query
     *
     * @param string|DbQuery $sql
     * @param bool $useCache Deprecated, the internal query cache is no longer used
     *
     * @return bool|PDOStatement
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @deprecated 1.1.1
     */
    protected function q($sql, $useCache = true)
    {
        Tools::displayAsDeprecated();
        return $this->query($sql);
    }

    /**
     * Executes a query
     *
     * @param string $sql
     * @param int $useCache
     *
     * @return array|bool|PDOStatement
     *
     * @throws PrestaShopException
     * @deprecated 2.0.0
     */
    public static function ps($sql, $useCache = 1)
    {
        Tools::displayAsDeprecated();
        return static::getInstance()->executeS($sql, true, $useCache);
    }

    /**
     * Executes a query
     *
     * @param string|DbQuery $sql
     * @param bool $useCache
     *
     * @return array|bool|PDOStatement
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     * @deprecated 2.0.0
     */
    public static function s($sql, $useCache = true)
    {
        Tools::displayAsDeprecated();
        return static::getInstance()->executeS($sql, true, $useCache);
    }

    /**
     * Executes a query and kills process (dies)
     *
     * @param string $sql
     * @param int $useCache
     *
     * @throws PrestaShopException
     * @deprecated 2.0.0
     *
     */
    public static function ds($sql, $useCache = 1)
    {
        Tools::displayAsDeprecated();
        static::getInstance()->executeS($sql, true, $useCache);
        exit;
    }


}
