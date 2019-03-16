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
 * Class StockMvtCore
 *
 * @since 1.0.0
 */
class StockMvtCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
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
     * @since 1.5.0
     * @var string The first name of the employee responsible of the movement
     */
    public $employee_firstname;

    /**
     * @since 1.5.0
     * @var string The last name of the employee responsible of the movement
     */
    public $employee_lastname;

    /**
     * @since 1.5.0
     * @var int The stock id on wtich the movement is applied
     */
    public $id_stock;

    /**
     * @since 1.5.0
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
     * @since 1.5.0
     * @var int detrmine if the movement is a positive or negative operation
     */
    public $sign;

    /**
     * @since 1.5.0
     * @var int Used when the movement is due to a supplier order
     */
    public $id_supply_order = null;

    /**
     * @since 1.5.0
     * @var float Last value of the weighted-average method
     */
    public $last_wa = null;

    /**
     * @since 1.5.0
     * @var float Current value of the weighted-average method
     */
    public $current_wa = null;

    /**
     * @since 1.5.0
     * @var float The unit price without tax of the product associated to the movement
     */
    public $price_te;

    /**
     * @since 1.5.0
     * @var int Refers to an other id_stock_mvt : used for LIFO/FIFO implementation in StockManager
     */
    public $referer;

    /**
     * @deprecated since 1.5.0
     * @deprecated stock movement will not be updated anymore
     */
    public $date_upd;

    /**
     * @deprecated since 1.5.0
     * @see        physical_quantity
     * @var int
     */
    public $quantity;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'stock_mvt',
        'primary' => 'id_stock_mvt',
        'fields'  => [
            'id_employee'         => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'employee_firstname'  => ['type' => self::TYPE_STRING, 'validate' => 'isName'                           ],
            'employee_lastname'   => ['type' => self::TYPE_STRING, 'validate' => 'isName'                           ],
            'id_stock'            => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'physical_quantity'   => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'required' => true],
            'id_stock_mvt_reason' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'id_order'            => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                     ],
            'id_supply_order'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                     ],
            'sign'                => ['type' => self::TYPE_INT,    'validate' => 'isInt',         'required' => true],
            'last_wa'             => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'current_wa'          => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'price_te'            => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',       'required' => true],
            'referer'             => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                     ],
            'date_add'            => ['type' => self::TYPE_DATE,   'validate' => 'isDate',        'required' => true],
        ],
    ];

    protected $webserviceParameters = [
        'objectsNodeName' => 'stock_movements',
        'objectNodeName'  => 'stock_movement',
        'fields'          => [
            'id_employee'         => ['xlink_resource' => 'employees'],
            'id_stock'            => ['xlink_resource' => 'stock'],
            'id_stock_mvt_reason' => ['xlink_resource' => 'stock_movement_reasons'],
            'id_order'            => ['xlink_resource' => 'orders'],
            'id_supply_order'     => ['xlink_resource' => 'supply_order'],
        ],
    ];

    /**
     * @deprecated 1.0.0
     *
     * This method no longer exists.
     * There is no equivalent or replacement, considering that this should be handled by inventories.
     */
    public static function addMissingMvt($id_employee)
    {
        // display that this method is deprecated
        Tools::displayAsDeprecated();
    }

    /**
     * Gets the negative (decrements the stock) stock mvts that correspond to the given order, for :
     * the given product, in the given quantity.
     *
     * @param int $idOrder
     * @param int $idProduct
     * @param int $idProductAttribute Use 0 if the product does not have attributes
     * @param int $quantity
     * @param int $idWarehouse        Optional
     *
     * @return array mvts
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNegativeStockMvts($idOrder, $idProduct, $idProductAttribute, $quantity, $idWarehouse = null)
    {
        $movements = [];
        $quantityTotal = 0;

        // preps query
        $query = new DbQuery();
        $query->select('sm.*, s.id_warehouse');
        $query->from('stock_mvt', 'sm');
        $query->innerJoin('stock', 's', 's.id_stock = sm.id_stock');
        $query->where('sm.sign = -1');
        $query->where('sm.id_order = '.(int) $idOrder);
        $query->where('s.id_product = '.(int) $idProduct.' AND s.id_product_attribute = '.(int) $idProductAttribute);

        // if filer by warehouse
        if (!is_null($idWarehouse)) {
            $query->where('s.id_warehouse = '.(int) $idWarehouse);
        }

        // orders the movements by date
        $query->orderBy('date_add DESC');

        // gets the result
        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->query($query);

        // fills the movements array
        while ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->nextRow($res)) {
            if ($quantityTotal >= $quantity) {
                break;
            }
            $quantityTotal += (int) $row['physical_quantity'];
            $movements[] = $row;
        }

        return $movements;
    }

    /**
     * For a given product, gets the last positive stock mvt
     *
     * @param int $idProduct
     * @param int $idProductAttribute Use 0 if the product does not have attributes
     *
     * @return bool|array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getLastPositiveStockMvt($idProduct, $idProductAttribute)
    {
        $query = new DbQuery();
        $query->select('sm.*, w.id_currency, (s.usable_quantity = sm.physical_quantity) as is_usable');
        $query->from('stock_mvt', 'sm');
        $query->innerJoin('stock', 's', 's.id_stock = sm.id_stock');
        $query->innerJoin('warehouse', 'w', 'w.id_warehouse = s.id_warehouse');
        $query->where('sm.sign = 1');
        if ($idProductAttribute) {
            $query->where('s.id_product = '.(int) $idProduct.' AND s.id_product_attribute = '.(int) $idProductAttribute);
        } else {
            $query->where('s.id_product = '.(int) $idProduct);
        }
        $query->orderBy('date_add DESC');

        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        if ($res != false) {
            return $res['0'];
        }

        return false;
    }
}
