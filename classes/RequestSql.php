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
 * Class RequestSqlCore
 */
class RequestSqlCore extends ObjectModel
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $sql;

    /**
     * @var array : List of params to tested
     */
    public $tested = [
        'required'     => ['SELECT', 'FROM'],
        'option'       => ['WHERE', 'ORDER', 'LIMIT', 'HAVING', 'GROUP', 'UNION'],
        'operator'     => [
            'AND', '&&', 'BETWEEN', 'AND', 'BINARY', '&', '~', '|', '^', 'CASE', 'WHEN', 'END', 'DIV', '/', '<=>', '=', '>=',
            '>', 'IS', 'NOT', 'NULL', '<<', '<=', '<', 'LIKE', '-', '%', '!=', '<>', 'REGEXP', '!', '||', 'OR', '+', '>>', 'RLIKE', 'SOUNDS', '*',
            '-', 'XOR', 'IN',
        ],
        'function'     => [
            'AVG', 'SUM', 'COUNT', 'MIN', 'MAX', 'STDDEV', 'STDDEV_SAMP', 'STDDEV_POP', 'VARIANCE', 'VAR_SAMP', 'VAR_POP',
            'GROUP_CONCAT', 'BIT_AND', 'BIT_OR', 'BIT_XOR',
        ],
        'unauthorized' => [
            'DELETE', 'ALTER', 'INSERT', 'REPLACE', 'CREATE', 'TRUNCATE', 'OPTIMIZE', 'GRANT', 'REVOKE', 'SHOW', 'HANDLER',
            'LOAD', 'ROLLBACK', 'SAVEPOINT', 'UNLOCK', 'INSTALL', 'UNINSTALL', 'ANALZYE', 'BACKUP', 'CHECK', 'CHECKSUM', 'REPAIR', 'RESTORE', 'CACHE',
            'DESCRIBE', 'EXPLAIN', 'USE', 'HELP', 'SET', 'DUPLICATE', 'VALUES', 'INTO', 'RENAME', 'CALL', 'PROCEDURE', 'FUNCTION', 'DATABASE', 'SERVER',
            'LOGFILE', 'DEFINER', 'RETURNS', 'EVENT', 'TABLESPACE', 'VIEW', 'TRIGGER', 'DATA', 'DO', 'PASSWORD', 'USER', 'PLUGIN', 'FLUSH', 'KILL',
            'RESET', 'START', 'STOP', 'PURGE', 'EXECUTE', 'PREPARE', 'DEALLOCATE', 'LOCK', 'USING', 'DROP', 'FOR', 'UPDATE', 'BEGIN', 'BY', 'ALL', 'SHARE',
            'MODE', 'TO', 'KEY', 'DISTINCTROW', 'DISTINCT', 'HIGH_PRIORITY', 'LOW_PRIORITY', 'DELAYED', 'IGNORE', 'FORCE', 'STRAIGHT_JOIN',
            'SQL_SMALL_RESULT', 'SQL_BIG_RESULT', 'QUICK', 'SQL_BUFFER_RESULT', 'SQL_CACHE', 'SQL_NO_CACHE', 'SQL_CALC_FOUND_ROWS', 'WITH',
        ],
    ];

    /**
     * @var string[]
     */
    public $attributes = [
        'passwd'     => '*******************',
        'secure_key' => '*******************',
    ];

    /** @var array : list of errors */
    public $error_sql = [];

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'request_sql',
        'primary' => 'id_request_sql',
        'primaryKeyDbType' => 'int(11)',
        'fields'  => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 200],
            'sql'  => ['type' => self::TYPE_SQL,    'validate' => 'isString', 'required' => true, 'dbType' => 'text', 'charset' => ['utf8mb4' , 'utf8mb4_unicode_ci']],
        ],
    ];

    /**
     * Get list of request SQL
     *
     * @return array|false
     */
    public static function getRequestSql()
    {
        try {
            if (!$result = Db::readOnly()->getArray(
                (new DbQuery())
                    ->select('*')
                    ->from(bqSQL(static::$definition['table']))
                    ->orderBy('`'.bqSQL(static::$definition['primary']).'`')
            )) {
                return false;
            }
        } catch (PrestaShopException $e) {
            return false;
        }

        $requestSql = [];
        foreach ($result as $row) {
            $requestSql[] = $row['sql'];
        }

        return $requestSql;
    }

    /**
     * Get request SQL by id request
     *
     * @param int $id
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function getRequestSqlById($id)
    {
        return (string)Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`sql`')
                ->from(bqSQL(static::$definition['table']))
                ->where('`'.bqSQL(static::$definition['primary']).'` = '.(int) $id)
        );
    }

    /**
     * Call the parserSQL() method in Tools class
     * Cut the request in table for check it
     *
     * @param string $sql
     *
     * @return false|array
     */
    public function parsingSql($sql)
    {
        return Tools::parserSQL($sql);
    }

    /**
     * Check if the parsing of the SQL request is good or not
     *
     * @param false|array $tab
     * @param bool $in
     * @param string $sql
     *
     * @return bool
     */
    public function validateParser($tab, $in, $sql)
    {
        if (!$tab) {
            return false;
        } elseif (isset($tab['UNION'])) {
            $union = $tab['UNION'];
            foreach ($union as $tab) {
                if (!$this->validateSql($tab, $in, $sql)) {
                    return false;
                }
            }

            return true;
        } else {
            return $this->validateSql($tab, $in, $sql);
        }
    }

    /**
     * Cut the request for check each cutting
     *
     * @param array $tab
     * @param bool $in
     * @param string $sql
     *
     * @return bool
     */
    public function validateSql($tab, $in, $sql)
    {
        if (!$this->testedRequired($tab)) {
            return false;
        } elseif (!$this->testedUnauthorized($tab)) {
            return false;
        } elseif (!$this->checkedFrom($tab['FROM'])) {
            return false;
        } elseif (!$this->checkedSelect($tab['SELECT'], $tab['FROM'], $in)) {
            return false;
        } elseif (isset($tab['WHERE'])) {
            if (!$this->checkedWhere($tab['WHERE'], $tab['FROM'], $sql)) {
                return false;
            }
        } elseif (isset($tab['HAVING'])) {
            if (!$this->checkedHaving($tab['HAVING'], $tab['FROM'])) {
                return false;
            }
        } elseif (isset($tab['ORDER'])) {
            if (!$this->checkedOrder($tab['ORDER'], $tab['FROM'])) {
                return false;
            }
        } elseif (isset($tab['GROUP'])) {
            if (!$this->checkedGroupBy($tab['GROUP'], $tab['FROM'])) {
                return false;
            }
        } elseif (isset($tab['LIMIT'])) {
            if (!$this->checkedLimit($tab['LIMIT'])) {
                return false;
            }
        }

        try {
            if (empty($this->_errors) && !Db::readOnly()->getArray($sql)) {
                return false;
            }
        } catch (PrestaShopException $e) {
            return false;
        }

        return true;
    }

    /**
     * Check if all required sentence existing
     *
     * @param array $tab
     *
     * @return bool
     */
    public function testedRequired($tab)
    {
        foreach ($this->tested['required'] as $key) {
            if (!array_key_exists($key, $tab)) {
                $this->error_sql['testedRequired'] = $key;

                return false;
            }
        }

        return true;
    }

    /**
     * Check if an unauthorized existing in an array
     *
     * @param array $tab
     *
     * @return bool
     */
    public function testedUnauthorized($tab)
    {
        foreach ($this->tested['unauthorized'] as $key) {
            if (array_key_exists($key, $tab)) {
                $this->error_sql['testedUnauthorized'] = $key;

                return false;
            }
        }

        return true;
    }

    /**
     * Check a "FROM" sentence
     *
     * @param array $from
     *
     * @return bool
     */
    public function checkedFrom($from)
    {
        if (!is_array($from)) {
            return false;
        }

        $nb = count($from);
        for ($i = 0; $i < $nb; $i++) {
            $table = $from[$i];

            if (isset($table['table']) && !in_array(str_replace('`', '', $table['table']), $this->getTables())) {
                $this->error_sql['checkedFrom']['table'] = $table['table'];

                return false;
            }
            if ($table['ref_type'] == 'ON' && (trim($table['join_type']) == 'LEFT' || trim($table['join_type']) == 'JOIN')) {
                if ($attrs = $this->cutJoin($table['ref_clause'], $from)) {
                    foreach ($attrs as $attr) {
                        if (!$this->attributExistInTable($attr['attribut'], $attr['table'])) {
                            $this->error_sql['checkedFrom']['attribut'] = [$attr['attribut'], implode(', ', $attr['table'])];

                            return false;
                        }
                    }
                } else {
                    if (isset($this->error_sql['returnNameTable'])) {
                        $this->error_sql['checkedFrom'] = $this->error_sql['returnNameTable'];

                        return false;
                    } else {
                        $this->error_sql['checkedFrom'] = false;

                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get list of all tables
     *
     * @return array
     */
    public function getTables()
    {
        $tables = [];
        try {
            $results = Db::readOnly()->getArray('SHOW TABLES');
        } catch (PrestaShopException $e) {
            return $tables;
        }
        foreach ($results as $result) {
            $key = array_keys($result);
            $tables[] = $result[$key[0]];
        }

        return $tables;
    }

    /**
     * Cut an join sentence
     *
     * @param array $attrs
     * @param array $from
     *
     * @return array
     */
    public function cutJoin($attrs, $from)
    {
        $tab = [];

        foreach ($attrs as $attr) {
            if (in_array($attr['expr_type'], ['operator', 'const'])) {
                continue;
            }
            if ($attr['expr_type'] === 'bracket_expression') {
                $tab = array_merge($tab, $this->cutJoin($attr['sub_tree'], $from));
            } elseif ($attribut = $this->cutAttribute($attr['base_expr'], $from)) {
                $tab[] = $attribut;
            }
        }

        return $tab;
    }

    /**
     * Cut an attribute with or without the alias
     *
     * @param string $attr
     * @param array $from
     *
     * @return array|false
     */
    public function cutAttribute($attr, $from)
    {
        $matches = [];
        if (preg_match('/((`(\()?([a-z0-9_])+`(\))?)|((\()?([a-z0-9_])+(\))?))\.((`(\()?([a-z0-9_])+`(\))?)|((\()?([a-z0-9_])+(\))?))$/i', $attr, $matches, PREG_OFFSET_CAPTURE)) {
            $tab = explode('.', str_replace(['`', '(', ')'], '', $matches[0][0]));
            if ($table = $this->returnNameTable($tab[0], $from)) {
                return [
                    'table'    => $table,
                    'alias'    => $tab[0],
                    'attribut' => $tab[1],
                    'string'   => $attr,
                ];
            }
        } elseif (preg_match('/((`(\()?([a-z0-9_])+`(\))?)|((\()?([a-z0-9_])+(\))?))$/i', $attr, $matches, PREG_OFFSET_CAPTURE)) {
            $attribut = str_replace(['`', '(', ')'], '', $matches[0][0]);
            if ($table = $this->returnNameTable(false, $from, $attr)) {
                return [
                    'table'    => $table,
                    'attribut' => $attribut,
                    'string'   => $attr,
                ];
            }
        }

        return false;
    }

    /**
     * Get name of table by alias
     *
     * @param bool $alias
     * @param array $tables
     * @param string $attr
     *
     * @return array|false
     */
    public function returnNameTable($alias, $tables, $attr = null)
    {
        if ($alias) {
            foreach ($tables as $table) {
                if (isset($table['alias']) && isset($table['table']) && $table['alias']['no_quotes']['parts'][0] == $alias) {
                    return [$table['table']];
                }
            }
        } elseif (count($tables) > 1) {
            if ($attr !== null) {
                $tab = [];
                foreach ($tables as $table) {
                    if ($this->attributExistInTable($attr, $table['table'])) {
                        $tab = $table['table'];
                    }
                }
                if (count($tab) == 1) {
                    return $tab;
                }
            }

            $this->error_sql['returnNameTable'] = false;

            return false;
        } else {
            $tab = [];
            foreach ($tables as $table) {
                $tab[] = $table['table'];
            }

            return $tab;
        }

        return false;
    }

    /**
     * Check if an attributes existe in an table
     *
     * @param string $attr
     * @param string $table
     *
     * @return bool
     */
    public function attributExistInTable($attr, $table)
    {
        if (!$attr) {
            return true;
        }
        if (is_array($table) && (count($table) == 1)) {
            $table = $table[0];
        }
        $attributs = $this->getAttributesByTable($table);
        foreach ($attributs as $attribut) {
            if ($attribut['Field'] == trim($attr, ' `')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of all attributes by an table
     *
     * @param string $table
     *
     * @return array
     */
    public function getAttributesByTable($table)
    {
        try {
            return Db::readOnly()->getArray('DESCRIBE '.pSQL($table));
        } catch (PrestaShopException $e) {
            return [];
        }
    }

    /**
     * Check a "SELECT" sentence
     *
     * @param string[] $select
     * @param string[] $from
     * @param bool $in
     *
     * @return bool
     */
    public function checkedSelect($select, $from, $in = false)
    {
        if (!is_array($select)) {
            return false;
        }

        $nb = count($select);
        for ($i = 0; $i < $nb; $i++) {
            /** @var string[] $attribut */
            $attribut = $select[$i];
            if ($attribut['base_expr'] != '*' && !preg_match('/\.*$/', $attribut['base_expr'])) {
                if ($attribut['expr_type'] == 'colref') {
                    if ($attr = $this->cutAttribute(trim($attribut['base_expr']), $from)) {
                        if (!$this->attributExistInTable($attr['attribut'], $attr['table'])) {
                            $this->error_sql['checkedSelect']['attribut'] = [$attr['attribut'], implode(', ', $attr['table'])];

                            return false;
                        }
                    } else {
                        if (isset($this->error_sql['returnNameTable'])) {
                            $this->error_sql['checkedSelect'] = $this->error_sql['returnNameTable'];

                            return false;
                        } else {
                            $this->error_sql['checkedSelect'] = false;

                            return false;
                        }
                    }
                }
            } elseif ($in) {
                $this->error_sql['checkedSelect']['*'] = false;

                return false;
            }
        }

        return true;
    }

    /**
     * Check a "WHERE" sentence
     *
     * @param array $where
     * @param array $from
     * @param string $sql
     *
     * @return bool
     */
    public function checkedWhere($where, $from, $sql)
    {
        if (!is_array($where)) {
            return false;
        }

        $nb = count($where);
        for ($i = 0; $i < $nb; $i++) {
            $attribut = $where[$i];
            if ($attribut['expr_type'] == 'colref' || $attribut['expr_type'] == 'reserved') {
                if ($attr = $this->cutAttribute(trim($attribut['base_expr']), $from)) {
                    if (!$this->attributExistInTable($attr['attribut'], $attr['table'])) {
                        $this->error_sql['checkedWhere']['attribut'] = [$attr['attribut'], implode(', ', $attr['table'])];

                        return false;
                    }
                } else {
                    if (isset($this->error_sql['returnNameTable'])) {
                        $this->error_sql['checkedWhere'] = $this->error_sql['returnNameTable'];

                        return false;
                    } else {
                        $this->error_sql['checkedWhere'] = false;

                        return false;
                    }
                }
            } elseif ($attribut['expr_type'] == 'operator') {
                if (!in_array(strtoupper($attribut['base_expr']), $this->tested['operator'])) {
                    $this->error_sql['checkedWhere']['operator'] = [$attribut['base_expr']];

                    return false;
                }
            } elseif ($attribut['expr_type'] == 'subquery') {
                $tab = $attribut['sub_tree'];

                return $this->validateParser($tab, true, $sql);
            }
        }

        return true;
    }

    /**
     * Check a "HAVING" sentence
     *
     * @param array $having
     * @param array $from
     *
     * @return bool
     */
    public function checkedHaving($having, $from)
    {
        $nb = count($having);
        for ($i = 0; $i < $nb; $i++) {
            $attribut = $having[$i];
            if ($attribut['expr_type'] == 'colref') {
                if ($attr = $this->cutAttribute(trim($attribut['base_expr']), $from)) {
                    if (!$this->attributExistInTable($attr['attribut'], $attr['table'])) {
                        $this->error_sql['checkedHaving']['attribut'] = [$attr['attribut'], implode(', ', $attr['table'])];

                        return false;
                    }
                } else {
                    if (isset($this->error_sql['returnNameTable'])) {
                        $this->error_sql['checkedHaving'] = $this->error_sql['returnNameTable'];

                        return false;
                    } else {
                        $this->error_sql['checkedHaving'] = false;

                        return false;
                    }
                }
            }

            if ($attribut['expr_type'] == 'operator') {
                if (!in_array(strtoupper($attribut['base_expr']), $this->tested['operator'])) {
                    $this->error_sql['checkedHaving']['operator'] = [$attribut['base_expr']];

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check a "ORDER" sentence
     *
     * @param array $order
     * @param array $from
     *
     * @return bool
     */
    public function checkedOrder($order, $from)
    {
        $order = $order[0];
        if ($order['type'] == 'expression') {
            if ($attr = $this->cutAttribute(trim($order['base_expr']), $from)) {
                if (!$this->attributExistInTable($attr['attribut'], $attr['table'])) {
                    $this->error_sql['checkedOrder']['attribut'] = [$attr['attribut'], implode(', ', $attr['table'])];

                    return false;
                }
            } else {
                if (isset($this->error_sql['returnNameTable'])) {
                    $this->error_sql['checkedOrder'] = $this->error_sql['returnNameTable'];

                    return false;
                } else {
                    $this->error_sql['checkedOrder'] = false;

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check a "GROUP BY" sentence
     *
     * @param array $group
     * @param array $from
     *
     * @return bool
     */
    public function checkedGroupBy($group, $from)
    {
        $group = $group[0];
        if ($group['type'] == 'expression') {
            if ($attr = $this->cutAttribute(trim($group['base_expr']), $from)) {
                if (!$this->attributExistInTable($attr['attribut'], $attr['table'])) {
                    $this->error_sql['checkedGroupBy']['attribut'] = [$attr['attribut'], implode(', ', $attr['table'])];

                    return false;
                }
            } else {
                if (isset($this->error_sql['returnNameTable'])) {
                    $this->error_sql['checkedGroupBy'] = $this->error_sql['returnNameTable'];

                    return false;
                } else {
                    $this->error_sql['checkedGroupBy'] = false;

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check a "LIMIT" sentence
     *
     * @param string[] $limit
     *
     * @return bool
     */
    public function checkedLimit($limit)
    {
        if (!preg_match('#^[0-9]+$#', trim($limit['start'])) || !preg_match('#^[0-9]+$#', trim($limit['end']))) {
            $this->error_sql['checkedLimit'] = false;

            return false;
        }

        return true;
    }
}
