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
 * Class PrestaShopCollectionCore
 *
 * @since 1.0.0
 */
class PrestaShopCollectionCore implements Iterator, ArrayAccess, Countable
{
    const LEFT_JOIN = 1;
    const INNER_JOIN = 2;
    const LEFT_OUTER_JOIN = 3;
    const LANG_ALIAS = 'l';

    // @codingStandardsIgnoreStart
    /**
     * @var string Object class name
     */
    protected $classname;
    /**
     * @var int
     */
    protected $id_lang;
    /**
     * @var array Object definition
     */
    protected $definition = [];
    /**
     * @var DbQuery
     */
    protected $query;
    /**
     * @var array Collection of objects in an array
     */
    protected $results = [];
    /**
     * @var bool Is current collection already hydrated
     */
    protected $is_hydrated = false;
    /**
     * @var int Collection iterator
     */
    protected $iterator = 0;
    /**
     * @var int Total of elements for iteration
     */
    protected $total;
    /**
     * @var int Page number
     */
    protected $page_number = 0;
    /**
     * @var int Size of a page
     */
    protected $page_size = 0;
    protected $fields = [];
    protected $alias = [];
    protected $alias_iterator = 0;
    protected $join_list = [];
    protected $association_definition = [];
    // @codingStandardsIgnoreEnd

    /**
     * @param string $classname
     * @param int    $idLang
     *
     * @throws PrestaShopException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($classname, $idLang = null)
    {
        $this->classname = $classname;
        $this->id_lang = $idLang;

        $this->definition = ObjectModel::getDefinition($this->classname);
        if (!isset($this->definition['table'])) {
            throw new PrestaShopException('Miss table in definition for class '.$this->classname);
        } elseif (!isset($this->definition['primary'])) {
            throw new PrestaShopException('Miss primary in definition for class '.$this->classname);
        }

        $this->query = new DbQuery();
    }

    /**
     * Add WHERE restriction on query using real SQL syntax
     *
     * @param string $sql
     *
     * @return static
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function sqlWhere($sql)
    {
        $this->query->where($this->parseFields($sql));

        return $this;
    }

    /**
     * Parse all fields with {field} syntax in a string
     *
     * @param string $str
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function parseFields($str)
    {
        preg_match_all('#\{(([a-z0-9_]+\.)*[a-z0-9_]+)\}#i', $str, $m);
        for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
            $str = str_replace($m[0][$i], $this->parseField($m[1][$i]), $str);
        }

        return $str;
    }

    /**
     * Replace a field with its SQL version (E.g. manufacturer.name with a2.name)
     *
     * @param string $field Field name
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function parseField($field)
    {
        $info = $this->getFieldInfo($field);

        return $info['alias'].'.`'.$info['name'].'`';
    }

    /**
     * Obtain some information on a field (alias, name, type, etc.)
     *
     * @param string $field Field name
     *
     * @return array
     * @throws PrestaShopException
     * @throws PrestaShopException
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    protected function getFieldInfo($field)
    {
        if (!isset($this->fields[$field])) {
            $split = explode('.', $field);
            $total = count($split);
            if ($total > 1) {
                $fieldname = $split[$total - 1];
                unset($split[$total - 1]);
                $association = implode('.', $split);
            } else {
                $fieldname = $field;
                $association = '';
            }

            $definition = $this->getDefinition($association);
            if ($association && !isset($this->join_list[$association])) {
                $this->join($association);
            }

            if ($fieldname == $definition['primary'] || (!empty($definition['is_lang']) && $fieldname == 'id_lang')) {
                $type = ObjectModel::TYPE_INT;
            } else {
                // Test if field exists
                if (!isset($definition['fields'][$fieldname])) {
                    throw new PrestaShopException('Field '.$fieldname.' not found in class '.$definition['classname']);
                }

                // Test field validity for language fields
                if (empty($definition['is_lang']) && !empty($definition['fields'][$fieldname]['lang'])) {
                    throw new PrestaShopException('Field '.$fieldname.' is declared as lang field but is used in non multilang context');
                } elseif (!empty($definition['is_lang']) && empty($definition['fields'][$fieldname]['lang'])) {
                    throw new PrestaShopException('Field '.$fieldname.' is not declared as lang field but is used in multilang context');
                }

                $type = $definition['fields'][$fieldname]['type'];
            }

            $this->fields[$field] = [
                'name'        => $fieldname,
                'association' => $association,
                'alias'       => $this->generateAlias($association),
                'type'        => $type,
            ];
        }

        return $this->fields[$field];
    }

    /**
     * Get definition of an association
     *
     * @param string $association
     *
     * @return array
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getDefinition($association)
    {
        if (!$association) {
            return $this->definition;
        }

        if (!isset($this->association_definition[$association])) {
            $definition = $this->definition;
            $split = explode('.', $association);
            $isLang = false;
            for ($i = 0, $totalAssociation = count($split); $i < $totalAssociation; $i++) {
                $asso = $split[$i];

                // Check is current association exists in current definition
                if (!isset($definition['associations'][$asso])) {
                    throw new PrestaShopException('Association '.$asso.' not found for class '.$this->definition['classname']);
                }
                $currentDef = $definition['associations'][$asso];

                // Special case for lang alias
                if ($asso == static::LANG_ALIAS) {
                    $isLang = true;
                    break;
                }

                $classname = (isset($currentDef['object'])) ? $currentDef['object'] : Tools::toCamelCase($asso, true);
                $definition = ObjectModel::getDefinition($classname);
            }

            $type = $currentDef['type'];

            // Get definition of associated entity and add information on current association
            $currentDef['name'] = $asso;
            if (!isset($currentDef['object'])) {
                $currentDef['object'] = Tools::toCamelCase($asso, true);
            }
            if (!isset($currentDef['field'])) {
                $currentDef['field'] = ($type === ObjectModel::BELONGS_TO_MANY)
                    ? $this->definition['primary']
                    : 'id_'.$asso;
            }
            if (!isset($currentDef['foreign_field'])) {
                $currentDef['foreign_field'] = $definition['primary'];
            }

            if ($type === ObjectModel::BELONGS_TO_MANY) {
                if (!isset($currentDef['joinTable'])) {
                    throw new PrestaShopException('Association ' . $this->definition['classname'] . ':' . $asso . ' is missing joinTable');
                }
                if (!isset($currentDef['joinSourceField'])) {
                    $currentDef['joinSourceField'] = $this->definition['primary'];
                }
                if (!isset($currentDef['joinTargetField'])) {
                    $currentDef['joinTargetField'] = $definition['primary'];
                }
            }

            if ($totalAssociation > 1) {
                unset($split[$totalAssociation - 1]);
                $currentDef['complete_field'] = implode('.', $split).'.'.$currentDef['field'];
            } else {
                $currentDef['complete_field'] = $currentDef['field'];
            }
            $currentDef['complete_foreign_field'] = $association.'.'.$currentDef['foreign_field'];

            $definition['is_lang'] = $isLang;
            $definition['asso'] = $currentDef;
            $this->association_definition[$association] = $definition;
        } else {
            $definition = $this->association_definition[$association];
        }

        return $definition;
    }

    /**
     * Join current entity to an associated entity
     *
     * @param string $association Association name
     * @param string $on
     * @param int    $type
     *
     * @return false|static
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function join($association, $on = '', $type = null)
    {
        if (!$association) {
            return false;
        }

        if (!isset($this->join_list[$association])) {
            $type = static::LEFT_JOIN;
            $definition = $this->getDefinition($association);
            $assocDefinition = $definition['asso'];
            if (isset($assocDefinition['joinTable'])) {
                $joinAlias = $this->generateAlias($association . '_' . $assocDefinition['joinTable']);
                $targetAlias = $this->generateAlias($association);
                $on = $joinAlias . '.`' . $assocDefinition['joinTargetField'] . '` = {' . $assocDefinition['complete_foreign_field'] . '}';
                $this->join_list[$association] = [
                    'joinTable' => $assocDefinition['joinTable'],
                    'joinAlias' => $joinAlias,
                    'joinTableJoin' => $this->parseFields('{' . $assocDefinition['complete_field'] . '} = ' . $joinAlias . '.`' . $assocDefinition['joinSourceField'].'`'),
                    'table' => $definition['table'],
                    'alias' => $targetAlias,
                    'on' => [],
                    'type' => $type
                ];
            } else {
                $on = '{' . $assocDefinition['complete_field'] . '} = {' . $assocDefinition['complete_foreign_field'] . '}';
                $this->join_list[$association] = [
                    'table' => ($definition['is_lang']) ? $definition['table'] . '_lang' : $definition['table'],
                    'alias' => $this->generateAlias($association),
                    'on' => [],
                    'type' => $type,
                ];
            }
        }

        if ($on) {
            $this->join_list[$association]['on'][] = $this->parseFields($on);
        }

        if ($type) {
            $this->join_list[$association]['type'] = $type;
        }

        return $this;
    }

    /**
     * Generate uniq alias from association name
     *
     * @param string $association Use empty association for alias on current table
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function generateAlias($association = '')
    {
        if (!isset($this->alias[$association])) {
            $this->alias[$association] = 'a'.$this->alias_iterator++;
        }

        return $this->alias[$association];
    }

    /**
     * Add HAVING restriction on query
     *
     * @param string $field    Field name
     * @param string $operator List of operators : =, !=, <>, <, <=, >, >=, like, notlike, regexp, notregexp
     * @param mixed  $value
     *
     * @return static
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function having($field, $operator, $value)
    {
        return $this->where($field, $operator, $value, 'having');
    }

    /**
     * Add WHERE restriction on query
     *
     * @param string $field    Field name
     * @param string $operator List of operators : =, !=, <>, <, <=, >, >=, like, notlike, regexp, notregexp
     * @param mixed  $value
     * @param string $method
     *
     * @return static
     * @throws PrestaShopException
     * @internal param string $type where|having
     *
     * @since    1.0.0
     * @version  1.0.0 Initial version
     */
    public function where($field, $operator, $value, $method = 'where')
    {
        if ($method != 'where' && $method != 'having') {
            throw new PrestaShopException('Bad method argument for where() method (should be "where" or "having")');
        }

        // Create WHERE clause with an array value (IN, NOT IN)
        if (is_array($value)) {
            switch (strtolower($operator)) {
                case '=':
                case 'in':
                    $this->query->$method($this->parseField($field).' IN('.implode(', ', $this->formatValue($value, $field)).')');
                    break;

                case '!=':
                case '<>':
                case 'notin':
                    $this->query->$method($this->parseField($field).' NOT IN('.implode(', ', $this->formatValue($value, $field)).')');
                    break;

                default:
                    throw new PrestaShopException('Operator not supported for array value');
            }
        } // Create WHERE clause
        else {
            switch (strtolower($operator)) {
                case '=':
                case '!=':
                case '<>':
                case '>':
                case '>=':
                case '<':
                case '<=':
                case 'like':
                case 'regexp':
                    $this->query->$method($this->parseField($field).' '.$operator.' '.$this->formatValue($value, $field));
                    break;

                case 'notlike':
                    $this->query->$method($this->parseField($field).' NOT LIKE '.$this->formatValue($value, $field));
                    break;

                case 'notregexp':
                    $this->query->$method($this->parseField($field).' NOT REGEXP '.$this->formatValue($value, $field));
                    break;
                default:
                    throw new PrestaShopException('Operator not supported');
            }
        }

        return $this;
    }

    /**
     * Format a value with the type of the given field
     *
     * @param mixed  $value
     * @param string $field Field name
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function formatValue($value, $field)
    {
        $info = $this->getFieldInfo($field);
        if (is_array($value)) {
            $results = [];
            foreach ($value as $item) {
                $results[] = ObjectModel::formatValue($item, $info['type'], true);
            }

            return $results;
        }

        return ObjectModel::formatValue($value, $info['type'], true);
    }

    /**
     * Add HAVING restriction on query using real SQL syntax
     *
     * @param string $sql
     *
     * @return static
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function sqlHaving($sql)
    {
        $this->query->having($this->parseFields($sql));

        return $this;
    }

    /**
     * Add ORDER BY restriction on query
     *
     * @param string $field Field name
     * @param string $order asc|desc
     *
     * @return static
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function orderBy($field, $order = 'asc')
    {
        $order = strtolower($order);
        if ($order != 'asc' && $order != 'desc') {
            throw new PrestaShopException('Order must be asc or desc');
        }
        $this->query->orderBy($this->parseField($field).' '.$order);

        return $this;
    }

    /**
     * Add ORDER BY restriction on query using real SQL syntax
     *
     * @param string $sql
     *
     * @return static
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function sqlOrderBy($sql)
    {
        $this->query->orderBy($this->parseFields($sql));

        return $this;
    }

    /**
     * Add GROUP BY restriction on query
     *
     * @param string $field Field name
     *
     * @return static
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function groupBy($field)
    {
        $this->query->groupBy($this->parseField($field));

        return $this;
    }

    /**
     * Add GROUP BY restriction on query using real SQL syntax
     *
     * @param string $sql
     *
     * @return static
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function sqlGroupBy($sql)
    {
        $this->query->groupBy($this->parseFields($sql));

        return $this;
    }

    /**
     * Retrieve the first result
     *
     * @return false|ObjectModel
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getFirst()
    {
        $this->getAll();
        if (!count($this)) {
            return false;
        }

        return $this[0];
    }

    /**
     * Launch sql query to create collection of objects
     *
     * @param bool $displayQuery If true, query will be displayed (for debug purpose)
     *
     * @return static
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getAll($displayQuery = false)
    {
        if ($this->is_hydrated) {
            return $this;
        }
        $this->is_hydrated = true;

        $alias = $this->generateAlias();
        //$this->query->select($alias.'.*');
        $this->query->from($this->definition['table'], $alias);

        // If multilang, create association to lang table
        if (!empty($this->definition['multilang'])) {
            $this->join(static::LANG_ALIAS);
            if ($this->id_lang) {
                $this->where(static::LANG_ALIAS.'.id_lang', '=', $this->id_lang);
            }
        }

        // Add join clause
        foreach ($this->join_list as $data) {
            if (isset($data['joinTable'])) {
                $this->joinTable($data['joinTable'], $data['joinAlias'], $data['joinTableJoin'], $data['type']);
            }
            $on = '(' . implode(') AND (', $data['on']) . ')';
            $this->joinTable($data['table'], $data['alias'], $on, $data['type']);
        }

        // All limit clause
        if ($this->page_size) {
            $this->query->limit($this->page_size, $this->page_number * $this->page_size);
        }

        // Shall we display query for debug ?
        if ($displayQuery) {
            echo $this->query.'<br />';
        }

        $this->results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($this->query);
        if ($this->results && is_array($this->results)) {
            $this->results = ObjectModel::hydrateCollection($this->classname, $this->results, $this->id_lang);
        }

        return $this;
    }

    /**
     * @param $table
     * @param $alias
     * @param $on
     * @param $joinType
     */
    private function joinTable($table, $alias, $on, $joinType)
    {
        switch ($joinType) {
            case static::LEFT_JOIN:
                $this->query->leftJoin($table, $alias, $on);
                break;
            case static::INNER_JOIN:
                $this->query->innerJoin($table, $alias, $on);
                break;
            case static::LEFT_OUTER_JOIN:
                $this->query->leftOuterJoin($table, $alias, $on);
                break;
        }
    }

    /**
     * Get results array
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getResults()
    {
        $this->getAll();

        return $this->results;
    }

    /**
     * This method is called when a foreach begin
     *
     * @see     Iterator::rewind()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function rewind()
    {
        $this->getAll();
        $this->results = array_merge($this->results);
        $this->iterator = 0;
        $this->total = count($this->results);
    }

    /**
     * Get current result
     *
     * @see     Iterator::current()
     * @return ObjectModel
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function current()
    {
        return isset($this->results[$this->iterator]) ? $this->results[$this->iterator] : null;
    }

    /**
     * Check if there is a current result
     *
     * @see     Iterator::valid()
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function valid()
    {
        return $this->iterator < $this->total;
    }

    /**
     * Get current result index
     *
     * @see     Iterator::key()
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function key()
    {
        return $this->iterator;
    }

    /**
     * Go to next result
     *
     * @see     Iterator::next()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function next()
    {
        $this->iterator++;
    }

    /**
     * Get total of results
     *
     * @see     Countable::count()
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function count()
    {
        $this->getAll();

        return count($this->results);
    }

    /**
     * Check if a result exist
     *
     * @param int $offset
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function offsetExists($offset)
    {
        $this->getAll();

        return isset($this->results[$offset]);
    }

    /**
     * Get a result by offset
     *
     * @param mixed $offset
     *
     * @return ObjectModel
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function offsetGet($offset)
    {
        $this->getAll();
        if (!isset($this->results[$offset])) {
            throw new PrestaShopException('Unknown offset '.$offset.' for collection '.$this->classname);
        }

        return $this->results[$offset];
    }

    /**
     * Add an element in the collection
     *
     * @param int   $offset
     * @param mixed $value
     *
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function offsetSet($offset, $value)
    {
        if (!$value instanceof $this->classname) {
            throw new PrestaShopException('You cannot add an element which is not an instance of '.$this->classname);
        }

        $this->getAll();
        if (is_null($offset)) {
            $this->results[] = $value;
        } else {
            $this->results[$offset] = $value;
        }
    }

    /**
     * Delete an element from the collection
     *
     * @see     ArrayAccess::offsetUnset()
     *
     * @param $offset
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function offsetUnset($offset)
    {
        $this->getAll();
        unset($this->results[$offset]);
    }

    /**
     * Set the page number
     *
     * @param int $pageNumber
     *
     * @return static
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setPageNumber($pageNumber)
    {
        $pageNumber = (int) $pageNumber;
        if ($pageNumber > 0) {
            $pageNumber--;
        }

        $this->page_number = $pageNumber;

        return $this;
    }

    /**
     * Set the nuber of item per page
     *
     * @param int $pageSize
     *
     * @return static
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setPageSize($pageSize)
    {
        $this->page_size = (int) $pageSize;

        return $this;
    }
}
