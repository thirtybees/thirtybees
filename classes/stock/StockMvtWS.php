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
 * Class StockMvtWSCore
 *
 * @since 1.0.0
 */
class StockMvtWSCore extends ObjectModelCore
{
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'stock_mvt',
        'primary' => 'id_stock_mvt',
        'fields'  => [
            'id_employee'         => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'employee_firstname'  => ['type' => self::TYPE_STRING, 'validate' => 'isName'],
            'employee_lastname'   => ['type' => self::TYPE_STRING, 'validate' => 'isName'],
            'id_stock'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'physical_quantity'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'id_stock_mvt_reason' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_supply_order'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'sign'                => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'last_wa'             => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'current_wa'          => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'price_te'            => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true],
            'referer'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'date_add'            => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];
    /** @var int $id */
    public $id;
    /**
     * @var string The creation date of the movement
     */
    public $date_add;
    /**
     * @var int The employee id, responsible of the movement
     */
    public $id_employee;
    /**
     * @var string The first name of the employee responsible of the movement
     */
    public $employee_firstname;
    /**
     * @var string The last name of the employee responsible of the movement
     */
    public $employee_lastname;
    /**
     * @var int The stock id on wtich the movement is applied
     */
    public $id_stock;
    /**
     * @var int the quantity of product with is moved
     */
    public $physical_quantity;
    /**
     * @var int id of the movement reason assoiated to the movement
     */
    public $id_stock_mvt_reason;
    /**
     * @var int Used when the movement is due to a customer order
     */
    public $id_order = null;
    /**
     * @var int detrmine if the movement is a positive or negative operation
     */
    public $sign;
    /**
     * @var int Used when the movement is due to a supplier order
     */
    public $id_supply_order = null;
    /**
     * @var float Last value of the weighted-average method
     */
    public $last_wa = null;
    /**
     * @var float Current value of the weighted-average method
     */
    public $current_wa = null;
    /**
     * @var float The unit price without tax of the product associated to the movement
     */
    public $price_te;
    /**
     * @var int Refers to an other id_stock_mvt : used for LIFO/FIFO implementation in StockManager
     */
    public $referer;
    /**
     * @var int id_product (@see Stock::id_product)
     */
    public $id_product;
    /**
     * @var int id_product_attribute (@see Stock::id_product_attribute)
     */
    public $id_product_attribute;
    /**
     * @var int id_warehouse (@see Stock::id_warehouse)
     */
    public $id_warehouse;
    /**
     * @var int id_currency (@see Warehouse::id_currency)
     */
    public $id_currency;
    /**
     * @var string management_type (@see Warehouse::management_type)
     */
    public $management_type;
    /**
     * @var string : Name of the product (@see Product::getProductName)
     */
    public $product_name;
    /**
     * @var string EAN13 of the product (@see Stock::product_ean13)
     */
    public $ean13;
    /**
     * @var string UPC of the product (@see Stock::product_upc)
     */
    public $upc;
    /**
     * @var string Reference of the product (@see Stock::product_reference)
     */
    public $reference;
    /**
     * @see ObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'fields'        => [
            'id_product'           => ['xlink_resource' => 'products'],
            'id_product_attribute' => ['xlink_resource' => 'combinations'],
            'id_warehouse'         => ['xlink_resource' => 'warehouses'],
            'id_currency'          => ['xlink_resource' => 'currencies'],
            'management_type'      => [],
            'id_employee'          => ['xlink_resource' => 'employees'],
            'id_stock'             => ['xlink_resource' => 'stocks'],
            'id_stock_mvt_reason'  => ['xlink_resource' => 'stock_movement_reasons'],
            'id_order'             => ['xlink_resource' => 'orders'],
            'id_supply_order'      => ['xlink_resource' => 'supply_orders'],
            'product_name'         => ['getter' => 'getWSProductName', 'i18n' => true],
            'ean13'                => [],
            'upc'                  => [],
            'reference'            => [],
        ],
        'hidden_fields' => [
            'referer',
            'employee_firstname',
            'employee_lastname',
        ],
    ];

    /**
     * Associations tables for attributes that require different tables than stated in ObjectModel::definition
     *
     * @var array
     */
    protected $tables_assoc = [
        'id_product'           => ['table' => 's'],
        'id_product_attribute' => ['table' => 's'],
        'id_warehouse'         => ['table' => 's'],
        'id_currency'          => ['table' => 's'],
        'management_type'      => ['table' => 'w'],
        'ean13'                => ['table' => 's'],
        'upc'                  => ['table' => 's'],
        'reference'            => ['table' => 's'],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        // calls parent
        parent::__construct($id, $idLang, $idShop);

        if ((int) $this->id != 0) {
            $res = $this->getWebserviceObjectList(null, (' AND '.$this->def['primary'].' = '.(int) $this->id), null, null, true);
            if (isset($res[0])) {
                foreach ($this->tables_assoc as $key => $param) {
                    $this->{$key} = $res[0][$key];
                }
            }
        }
    }

    /**
     * @param string $join
     * @param string $filter
     * @param string $sort
     * @param string $limit
     * @param bool   $full
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWebserviceObjectList($join, $filter, $sort, $limit, $full = false)
    {
        $query = 'SELECT DISTINCT main.'.$this->def['primary'].' ';

        if ($full) {
            $query .= ', s.id_product, s.id_product_attribute, s.id_warehouse, w.id_currency, w.management_type,s.ean13, s.upc, s.reference ';
        }

        $oldFilter = $filter;
        if ($filter) {
            foreach ($this->tables_assoc as $key => $value) {
                $filter = str_replace('main.`'.$key.'`', $value['table'].'.`'.$key.'`', $filter);
            }
        }

        $query .= 'FROM '._DB_PREFIX_.$this->def['table'].' as main ';

        if ($filter !== $oldFilter || $full) {
            $query .= 'LEFT JOIN '._DB_PREFIX_.'stock s ON (s.id_stock = main.id_stock) ';
            $query .= 'LEFT JOIN '._DB_PREFIX_.'warehouse w ON (w.id_warehouse = s.id_warehouse) ';
            $query .= 'LEFT JOIN '._DB_PREFIX_.'currency c ON (c.id_currency = w.id_currency) ';
        }

        if ($join) {
            $query .= $join;
        }

        $query .= 'WHERE 1 ';

        if ($filter) {
            $query .= $filter.' ';
        }

        if ($sort) {
            $query .= $sort.' ';
        }

        if ($limit) {
            $query .= $limit.' ';
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * Webservice : getter for the product name
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getWSProductName()
    {
        $res = [];
        foreach (Language::getIDs(true) as $idLang) {
            $res[$idLang] = Product::getProductName($this->id_product, $this->id_product_attribute, $idLang);
        }

        return $res;
    }
}
