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

/**
 * SQL query builder
 *
 * @since 1.0.0
 */
class DbQueryCore
{
    /**
     * List of data to build the query
     *
     * @var array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected $query = [
        'type'   => 'SELECT',
        'select' => [],
        'from'   => [],
        'join'   => [],
        'where'  => [],
        'group'  => [],
        'having' => [],
        'order'  => [],
        'limit'  => ['offset' => 0, 'limit' => 0],
    ];

    /**
     * Sets type of the query
     *
     * @param string $type SELECT|DELETE
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function type($type)
    {
        $types = ['SELECT', 'DELETE'];

        if (!empty($type) && in_array($type, $types)) {
            $this->query['type'] = $type;
        }

        return $this;
    }

    /**
     * Adds fields to SELECT clause
     *
     * @param string $fields List of fields to concat to other fields
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function select($fields)
    {
        if (!empty($fields)) {
            $this->query['select'][] = $fields;
        }

        return $this;
    }

    /**
     * Sets table for FROM clause
     *
     * @param string      $table Table name
     * @param string|null $alias Table alias
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function from($table, $alias = null)
    {
        if (!empty($table)) {
            if (strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
                $table = _DB_PREFIX_.$table;
            }

            if (empty($this->query['from'])) {
                $this->query['from'] = [];
            }
            $this->query['from'][] = '`'.bqSQL($table).'`'.($alias ? ' '.$alias : '');
        }

        return $this;
    }

    /**
     * Adds JOIN clause
     * E.g. $this->join('RIGHT JOIN '._DB_PREFIX_.'product p ON ...');
     *
     * @param string $join Complete string
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function join($join)
    {
        if (!empty($join)) {
            $this->query['join'][] = $join;
        }

        return $this;
    }

    /**
     * Adds a LEFT JOIN clause
     *
     * @param string      $table Table name (without prefix)
     * @param string|null $alias Table alias
     * @param string|null $on    ON clause
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function leftJoin($table, $alias = null, $on = null)
    {
        if (strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_.$table;
        }

        return $this->join('LEFT JOIN `'.bqSQL($table).'`'.($alias ? ' `'.pSQL($alias).'`' : '').($on ? ' ON '.$on : ''));
    }

    /**
     * Adds an INNER JOIN clause
     * E.g. $this->innerJoin('product p ON ...')
     *
     * @param string      $table Table name (without prefix)
     * @param string|null $alias Table alias
     * @param string|null $on    ON clause
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function innerJoin($table, $alias = null, $on = null)
    {
        if (strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_.$table;
        }

        return $this->join('INNER JOIN `'.bqSQL($table).'`'.($alias ? ' '.pSQL($alias) : '').($on ? ' ON '.$on : ''));
    }

    /**
     * Include primary and shop tables into the query using INNER JOIN
     *
     * Two tables will be added to the sql query
     *   - primary table:  <DB_PREFIX>_table AS alias
     *   - shop table:     <DB_PREFIX>_table_shop AS aliasShop
     * Shop table will be joined using object model metadata
     *
     * For more information, see Shop::addSqlAssociation
     *
     * @param string $table primary table name (without prefix)
     * @param string $alias primary table alias
     * @param string $aliasShop shop table alias
     * @param string $on primary table ON clause
     * @param string|null $shopOnExtra additional conditions to be included within shop ON clause
     *
     * @return $this
     *
     * @throws PrestaShopException
     */
    public function innerJoinMultishop($table, $alias, $aliasShop, $on, $shopOnExtra = null)
    {
        if (! Shop::isTableAssociated($table)) {
            throw new PrestaShopException("Table `$table` is not multistore enabled`");
        }

        // expose primary table
        $this->innerJoin($table, $alias, $on);

        // expose shop table
        $shopOn = '`' . pSQL($aliasShop) . '`.`id_' . $table . '` = `' . pSQL($alias). '`.`id_' . $table . '`';
        if ((int) Shop::getContextShopID()) {
            $shopOn .= ' AND `'.$aliasShop.'`.`id_shop` = ' . (int) Shop::getContextShopID();
        } elseif (Shop::checkIdShopDefault($table)) {
            $shopOn .= ' AND `'.$aliasShop.'`.`id_shop` = `'.$alias.'`.`id_shop_default`';
        } else {
            $shopOn .= ' AND `'.$aliasShop.'`.`id_shop` IN ('.implode(', ', Shop::getContextListShopID()).')';
        }

        if ($shopOnExtra) {
            $shopOn .= ' ' . trim($shopOnExtra);
        }

        return $this->innerJoin($table . "_shop", $aliasShop, $shopOn);
    }


    /**
     * Adds a LEFT OUTER JOIN clause
     *
     * @param string      $table Table name (without prefix)
     * @param string|null $alias Table alias
     * @param string|null $on    ON clause
     *
     * @return $this
     *
     *@since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function leftOuterJoin($table, $alias = null, $on = null)
    {
        if (strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_.$table;
        }

        return $this->join('LEFT OUTER JOIN `'.bqSQL($table).'`'.($alias ? ' '.pSQL($alias) : '').($on ? ' ON '.$on : ''));
    }

    /**
     * Adds a NATURAL JOIN clause
     *
     * @param string      $table Table name (without prefix)
     * @param string|null $alias Table alias
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function naturalJoin($table, $alias = null)
    {
        if (strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_.$table;
        }

        return $this->join('NATURAL JOIN `'.bqSQL($table).'`'.($alias ? ' '.pSQL($alias) : ''));
    }

    /**
     * Adds a RIGHT JOIN clause
     *
     * @param string      $table Table name (without prefix)
     * @param string|null $alias Table alias
     * @param string|null $on    ON clause
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function rightJoin($table, $alias = null, $on = null)
    {
        if (strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_.$table;
        }

        return $this->join('RIGHT JOIN `'.bqSQL($table).'`'.($alias ? ' `'.pSQL($alias).'`' : '').($on ? ' ON '.$on : ''));
    }

    /**
     * Adds a restriction in WHERE clause (each restriction will be separated by AND statement)
     *
     * @param string $restriction
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function where($restriction)
    {
        if (!empty($restriction)) {
            $this->query['where'][] = $restriction;
        }

        return $this;
    }

    /**
     * Adds shop restriction for a specific table alias.
     *
     * @param string $tableAlias
     * @param mixed $share If false, dont check share datas from group. Else can take a Shop::SHARE_* constant value

     * @return $this
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @since 1.1.1
     */
    public function addCurrentShopRestriction($tableAlias, $share = false)
    {
        return $this->where(Shop::getSqlRestriction($share, '`' . $tableAlias. '`'));
    }

    /**
     * Adds a restriction in HAVING clause (each restriction will be separated by AND statement)
     *
     * @param string $restriction
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function having($restriction)
    {
        if (!empty($restriction)) {
            $this->query['having'][] = $restriction;
        }

        return $this;
    }

    /**
     * Adds an ORDER BY restriction
     *
     * @param string $fields List of fields to sort. E.g. $this->order('myField, b.mySecondField DESC')
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function orderBy($fields)
    {
        if (!empty($fields)) {
            $this->query['order'][] = $fields;
        }

        return $this;
    }

    /**
     * Adds a GROUP BY restriction
     *
     * @param string $fields List of fields to group. E.g. $this->group('myField1, myField2')
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function groupBy($fields)
    {
        if (!empty($fields)) {
            $this->query['group'][] = $fields;
        }

        return $this;
    }

    /**
     * Sets query offset and limit
     *
     * @param int $limit
     * @param int $offset
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function limit($limit, $offset = 0)
    {
        $offset = (int)$offset;
        if ($offset < 0) {
            $offset = 0;
        }

        $this->query['limit'] = [
            'offset' => $offset,
            'limit'  => (int)$limit,
        ];

        return $this;
    }

    /**
     * Generates query and return SQL string
     *
     * @return string
     * @throws PrestaShopException
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function build()
    {
        $this->validate();
        return $this->buildSql();
    }

    /**
     * Validates current DbQuery object, throws exception if it's not valid
     *
     * @throws PrestaShopException
     */
    public function validate()
    {
        if (!$this->query['from']) {
            throw new PrestaShopException('Table name not set in DbQuery object. Cannot build a valid SQL query.');
        }
    }

    /**
     * Generates query and return SQL
     *
     * @return string
     */
    public function buildSql()
    {
        if ($this->query['type'] == 'SELECT') {
            $sql = 'SELECT '.((($this->query['select'])) ? implode(",\n", $this->query['select']) : '*')."\n";
        } else {
            $sql = $this->query['type'].' ';
        }

        if ($this->query['from']) {
            $sql .= 'FROM ' . implode(', ', $this->query['from']) . "\n";
        }

        if ($this->query['join']) {
            $sql .= implode("\n", $this->query['join'])."\n";
        }

        if ($this->query['where']) {
            $sql .= 'WHERE ('.implode(') AND (', $this->query['where']).")\n";
        }

        if ($this->query['group']) {
            $sql .= 'GROUP BY '.implode(', ', $this->query['group'])."\n";
        }

        if ($this->query['having']) {
            $sql .= 'HAVING ('.implode(') AND (', $this->query['having']).")\n";
        }

        if ($this->query['order']) {
            $sql .= 'ORDER BY '.implode(', ', $this->query['order'])."\n";
        }

        if ($this->query['limit']['limit']) {
            $limit = $this->query['limit'];
            $sql .= 'LIMIT '.($limit['offset'] ? $limit['offset'].', ' : '').$limit['limit'];
        }

        return $sql;
    }

    /**
     * Converts object to string
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function __toString()
    {
        return $this->buildSql();
    }
}
