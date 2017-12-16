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
 * Class WarehouseCore
 *
 * @since 1.0.0
 */
class WarehouseCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int identifier of the warehouse */
    public $id;

    /** @var int Id of the address associated to the warehouse */
    public $id_address;

    /** @var string Reference of the warehouse */
    public $reference;

    /** @var string Name of the warehouse */
    public $name;

    /** @var int Id of the employee who manages the warehouse */
    public $id_employee;

    /** @var int Id of the valuation currency of the warehouse */
    public $id_currency;

    /** @var bool True if warehouse has been deleted (hence, no deletion in DB) */
    public $deleted = 0;

    /**
     * Describes the way a Warehouse is managed
     *
     * @var string enum WA|LIFO|FIFO
     */
    public $management_type;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'warehouse',
        'primary' => 'id_warehouse',
        'fields'  => [
            'id_address'      => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',      'required' => true              ],
            'reference'       => ['type' => self::TYPE_STRING, 'validate' => 'isString',          'required' => true, 'size' => 45],
            'name'            => ['type' => self::TYPE_STRING, 'validate' => 'isString',          'required' => true, 'size' => 45],
            'id_employee'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',      'required' => true              ],
            'management_type' => ['type' => self::TYPE_STRING, 'validate' => 'isStockManagement', 'required' => true              ],
            'id_currency'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',      'required' => true              ],
            'deleted'         => ['type' => self::TYPE_BOOL],
        ],
    ];

    /**
     * @see ObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'fields'       => [
            'id_address'  => ['xlink_resource' => 'addresses'],
            'id_employee' => ['xlink_resource' => 'employees'],
            'id_currency' => ['xlink_resource' => 'currencies'],
            'valuation'   => ['getter' => 'getWsStockValue', 'setter' => false],
            'deleted'     => [],
        ],
        'associations' => [
            'stocks'   => [
                'resource' => 'stock',
                'fields'   => [
                    'id' => [],
                ],
            ],
            'carriers' => [
                'resource' => 'carrier',
                'fields'   => [
                    'id' => [],
                ],
            ],
            'shops'    => [
                'resource' => 'shop',
                'fields'   => [
                    'id'   => [],
                    'name' => [],
                ],
            ],
        ],
    ];

    /**
     * Gets the shops associated to the current warehouse
     *
     * @return array Shops (id, name)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getShops()
    {
        $query = new DbQuery();
        $query->select('ws.id_shop, s.name');
        $query->from('warehouse_shop', 'ws');
        $query->leftJoin('shop', 's', 's.id_shop = ws.id_shop');
        $query->where($this->def['primary'].' = '.(int) $this->id);

        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        return $res;
    }

    /**
     * Gets the carriers associated to the current warehouse
     *
     * @return array Ids of the associated carriers
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getCarriers($returnReference = false)
    {
        $idsCarrier = [];

        $query = new DbQuery();
        if ($returnReference) {
            $query->select('wc.id_carrier');
        } else {
            $query->select('c.id_carrier');
        }
        $query->from('warehouse_carrier', 'wc');
        $query->innerJoin('carrier', 'c', 'c.id_reference = wc.id_carrier');
        $query->where($this->def['primary'].' = '.(int) $this->id);
        $query->where('c.deleted = 0');
        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        if (!is_array($res)) {
            return $idsCarrier;
        }

        foreach ($res as $carriers) {
            foreach ($carriers as $carrier) {
                $idsCarrier[$carrier] = $carrier;
            }
        }

        return $idsCarrier;
    }

    /**
     * Sets the carriers associated to the current warehouse
     *
     * @param array $idsCarriers
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setCarriers($idsCarriers)
    {
        if (!is_array($idsCarriers)) {
            $idsCarriers = [];
        }

        $rowToInsert = [];
        foreach ($idsCarriers as $idCarrier) {
            $rowToInsert[] = [$this->def['primary'] => $this->id, 'id_carrier' => (int) $idCarrier];
        }

        Db::getInstance()->execute(
            '
			DELETE FROM '._DB_PREFIX_.'warehouse_carrier
			WHERE '.$this->def['primary'].' = '.(int) $this->id
        );

        if ($rowToInsert) {
            Db::getInstance()->insert('warehouse_carrier', $rowToInsert);
        }
    }

    /**
     * For a given carrier, removes it from the warehouse/carrier association
     * If $id_warehouse is set, it only removes the carrier for this warehouse
     *
     * @param int $idCarrier   Id of the carrier to remove
     * @param int $idWarehouse optional Id of the warehouse to filter
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function removeCarrier($idCarrier, $idWarehouse = null)
    {
        Db::getInstance()->execute(
            '
			DELETE FROM '._DB_PREFIX_.'warehouse_carrier
			WHERE id_carrier = '.(int) $idCarrier.
            ($idWarehouse ? ' AND id_warehouse = '.(int) $idWarehouse : '')
        );
    }

    /**
     * Checks if a warehouse is empty - i.e. has no stock
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function isEmpty()
    {
        $query = new DbQuery();
        $query->select('SUM(s.physical_quantity)');
        $query->from('stock', 's');
        $query->where($this->def['primary'].' = '.(int) $this->id);

        return (Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query) == 0);
    }

    /**
     * Checks if the given warehouse exists
     *
     * @param int $idWarehouse
     *
     * @return bool Exists/Does not exist
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function exists($idWarehouse)
    {
        $query = new DbQuery();
        $query->select('id_warehouse');
        $query->from('warehouse');
        $query->where('id_warehouse = '.(int) $idWarehouse);
        $query->where('deleted = 0');

        return (Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query));
    }

    /**
     * For a given {product, product attribute} sets its location in the given warehouse
     * First, for the given parameters, it cleans the database before updating
     *
     * @param int    $idProduct          ID of the product
     * @param int    $idProductAttribute Use 0 if this product does not have attributes
     * @param int    $idWarehouse        ID of the warehouse
     * @param string $location           Describes the location (no lang id required)
     *
     * @return bool Success/Failure
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function setProductLocation($idProduct, $idProductAttribute, $idWarehouse, $location)
    {
        Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'warehouse_product_location`
			WHERE `id_product` = '.(int) $idProduct.'
			AND `id_product_attribute` = '.(int) $idProductAttribute.'
			AND `id_warehouse` = '.(int) $idWarehouse
        );

        $rowToInsert = [
            'id_product'           => (int) $idProduct,
            'id_product_attribute' => (int) $idProductAttribute,
            'id_warehouse'         => (int) $idWarehouse,
            'location'             => pSQL($location),
        ];

        return Db::getInstance()->insert('warehouse_product_location', $rowToInsert);
    }

    /**
     * Resets all product locations for this warehouse
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function resetProductsLocations()
    {
        Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'warehouse_product_location`
			WHERE `id_warehouse` = '.(int) $this->id
        );
    }

    /**
     * For a given {product, product attribute} gets its location in the given warehouse
     *
     * @param int $idProduct          ID of the product
     * @param int $idProductAttribute Use 0 if this product does not have attributes
     * @param int $idWarehouse        ID of the warehouse
     *
     * @return string Location of the product
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getProductLocation($idProduct, $idProductAttribute, $idWarehouse)
    {
        $query = new DbQuery();
        $query->select('location');
        $query->from('warehouse_product_location');
        $query->where('id_warehouse = '.(int) $idWarehouse);
        $query->where('id_product = '.(int) $idProduct);
        $query->where('id_product_attribute = '.(int) $idProductAttribute);

        return (Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query));
    }

    /**
     * For a given {product, product attribute} gets warehouse list
     *
     * @param int $idProduct          ID of the product
     * @param int $idProductAttribute Optional, uses 0 if this product does not have attributes
     * @param int $idShop             Optional, ID of the shop. Uses the context shop id (@see Context::shop)
     *
     * @return array Warehouses (ID, reference/name concatenated)
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProductWarehouseList($idProduct, $idProductAttribute = 0, $idShop = null)
    {
        // if it's a pack, returns warehouses if and only if some products use the advanced stock management
        $shareStock = false;
        if ($idShop === null) {
            if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                $shopGroup = Shop::getContextShopGroup();
            } else {
                $shopGroup = Context::getContext()->shop->getGroup();
                $idShop = (int) Context::getContext()->shop->id;
            }
            $shareStock = $shopGroup->share_stock;
        } else {
            $shopGroup = Shop::getGroupFromShop($idShop);
            $shareStock = $shopGroup['share_stock'];
        }

        if ($shareStock) {
            $idsShop = Shop::getShops(true, (int) $shopGroup->id, true);
        } else {
            $idsShop = [(int) $idShop];
        }

        $query = new DbQuery();
        $query->select('wpl.id_warehouse, CONCAT(w.reference, " - ", w.name) as name');
        $query->from('warehouse_product_location', 'wpl');
        $query->innerJoin('warehouse_shop', 'ws', 'ws.id_warehouse = wpl.id_warehouse AND id_shop IN ('.implode(',', array_map('intval', $idsShop)).')');
        $query->innerJoin('warehouse', 'w', 'ws.id_warehouse = w.id_warehouse');
        $query->where('id_product = '.(int) $idProduct);
        $query->where('id_product_attribute = '.(int) $idProductAttribute);
        $query->where('w.deleted = 0');
        $query->groupBy('wpl.id_warehouse');

        return (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query));
    }

    /**
     * Gets available warehouses
     * It is possible via ignore_shop and id_shop to filter the list with shop id
     *
     * @param bool $ignoreShop Optional, false by default - Allows to get only the warehouses that are associated to one/some shops (@see $id_shop)
     * @param int  $idShop     Optional, Context::shop::Id by default - Allows to define a specific shop to filter.
     *
     * @return array Warehouses (ID, reference/name concatenated)
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getWarehouses($ignoreShop = false, $idShop = null)
    {
        if (!$ignoreShop) {
            if (is_null($idShop)) {
                $idShop = Context::getContext()->shop->id;
            }
        }

        $query = new DbQuery();
        $query->select('w.id_warehouse, CONCAT(reference, \' - \', name) as name');
        $query->from('warehouse', 'w');
        $query->where('deleted = 0');
        $query->orderBy('reference ASC');
        if (!$ignoreShop) {
            $query->innerJoin('warehouse_shop', 'ws', 'ws.id_warehouse = w.id_warehouse AND ws.id_shop = '.(int) $idShop);
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * Gets warehouses grouped by shops
     *
     * @return array (of array) Warehouses ID are grouped by shops ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getWarehousesGroupedByShops()
    {
        $idsWarehouse = [];
        $query = new DbQuery();
        $query->select('id_warehouse, id_shop');
        $query->from('warehouse_shop');
        $query->orderBy('id_shop');

        // queries to get warehouse ids grouped by shops
        foreach (Db::getInstance()->executeS($query) as $row) {
            $idsWarehouse[$row['id_shop']][] = $row['id_warehouse'];
        }

        return $idsWarehouse;
    }

    /**
     * Gets the number of products in the current warehouse
     *
     * @return int Number of different id_stock
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getNumberOfProducts()
    {
        $query = '
			SELECT COUNT(t.id_stock)
			FROM
				(
					SELECT s.id_stock
				 	FROM '._DB_PREFIX_.'stock s
				 	WHERE s.id_warehouse = '.(int) $this->id.'
				 	GROUP BY s.id_product, s.id_product_attribute
				 ) as t';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * Gets the number of quantities - for all products - in the current warehouse
     *
     * @return int Total Quantity
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getQuantitiesOfProducts()
    {
        $query = '
			SELECT SUM(s.physical_quantity)
			FROM '._DB_PREFIX_.'stock s
			WHERE s.id_warehouse = '.(int) $this->id;

        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        return ($res ? $res : 0);
    }

    /**
     * Gets the value of the stock in the current warehouse
     *
     * @return int Value of the stock
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getStockValue()
    {
        $query = new DbQuery();
        $query->select('SUM(s.`price_te` * s.`physical_quantity`)');
        $query->from('stock', 's');
        $query->where('s.`id_warehouse` = '.(int) $this->id);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * For a given employee, gets the warehouse(s) he/she manages
     *
     * @param int $idEmployee Manager ID
     *
     * @return array ids_warehouse Ids of the warehouses
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getWarehousesByEmployee($idEmployee)
    {
        $query = new DbQuery();
        $query->select('w.id_warehouse');
        $query->from('warehouse', 'w');
        $query->where('w.id_employee = '.(int) $idEmployee);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * For a given product, returns the warehouses it is stored in
     *
     * @param int $idProduct          Product Id
     * @param int $idProductAttribute Optional, Product Attribute Id - 0 by default (no attribues)
     *
     * @return array Warehouses Ids and names
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getWarehousesByProductId($idProduct, $idProductAttribute = 0)
    {
        if (!$idProduct && !$idProductAttribute) {
            return [];
        }

        $query = new DbQuery();
        $query->select('DISTINCT w.id_warehouse, CONCAT(w.reference, " - ", w.name) as name');
        $query->from('warehouse', 'w');
        $query->leftJoin('warehouse_product_location', 'wpl', 'wpl.id_warehouse = w.id_warehouse');
        if ($idProduct) {
            $query->where('wpl.id_product = '.(int) $idProduct);
        }
        if ($idProductAttribute) {
            $query->where('wpl.id_product_attribute = '.(int) $idProductAttribute);
        }
        $query->orderBy('w.reference ASC');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * For a given $id_warehouse, returns its name
     *
     * @param int $idWarehouse Warehouse Id
     *
     * @return string Name
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getWarehouseNameById($idWarehouse)
    {
        $query = new DbQuery();
        $query->select('name');
        $query->from('warehouse');
        $query->where('id_warehouse = '.(int) $idWarehouse);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * For a given pack, returns the warehouse it can be shipped from
     *
     * @param int  $idProduct
     *
     * @param null $idShop
     *
     * @return array|bool id_warehouse or false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getPackWarehouses($idProduct, $idShop = null)
    {
        if (!Pack::isPack($idProduct)) {
            return false;
        }

        if (is_null($idShop)) {
            $idShop = Context::getContext()->shop->id;
        }

        // warehouses of the pack
        $packWarehouses = WarehouseProductLocation::getCollection((int) $idProduct);
        // products in the pack
        $products = Pack::getItems((int) $idProduct, Configuration::get('PS_LANG_DEFAULT'));

        // array with all warehouses id to check
        $list = [];

        // fills $list
        foreach ($packWarehouses as $pack_warehouse) {
            /** @var WarehouseProductLocation $pack_warehouse */
            $list['pack_warehouses'][] = (int) $pack_warehouse->id_warehouse;
        }

        // for each products in the pack
        foreach ($products as $product) {
            if ($product->advanced_stock_management) {
                // gets the warehouses of one product
                $productWarehouses = Warehouse::getProductWarehouseList((int) $product->id, (int) $product->cache_default_attribute, (int) $idShop);
                $list[(int) $product->id] = [];
                // fills array with warehouses for this product
                foreach ($productWarehouses as $productWarehouse) {
                    $list[(int) $product->id][] = $productWarehouse['id_warehouse'];
                }
            }
        }

        $res = false;
        // returns final list
        if (count($list) > 1) {
            $res = call_user_func_array('array_intersect', $list);
        }

        return $res;
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function resetStockAvailable()
    {
        $products = WarehouseProductLocation::getProducts((int) $this->id);
        foreach ($products as $product) {
            StockAvailable::synchronize((int) $product['id_product']);
        }
    }

    /*********************************\
     *
     * Webservices Specific Methods
     *
     *********************************/

    /**
     * Webservice : gets the value of the warehouse
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getWsStockValue()
    {
        return $this->getStockValue();
    }

    /**
     * Webservice : gets the ids stock associated to this warehouse
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsStocks()
    {
        $query = new DbQuery();
        $query->select('s.id_stock as id');
        $query->from('stock', 's');
        $query->where('s.id_warehouse ='.(int) $this->id);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * Webservice : gets the ids shops associated to this warehouse
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsShops()
    {
        $query = new DbQuery();
        $query->select('ws.id_shop as id, s.name');
        $query->from('warehouse_shop', 'ws');
        $query->leftJoin('shop', 's', 's.id_shop = ws.id_shop');
        $query->where($this->def['primary'].' = '.(int) $this->id);

        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        return $res;
    }

    /**
     * Webservice : gets the ids carriers associated to this warehouse
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsCarriers()
    {
        $idsCarrier = [];

        $query = new DbQuery();
        $query->select('wc.id_carrier as id');
        $query->from('warehouse_carrier', 'wc');
        $query->where($this->def['primary'].' = '.(int) $this->id);

        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        if (!is_array($res)) {
            return $idsCarrier;
        }

        foreach ($res as $carriers) {
            foreach ($carriers as $carrier) {
                $idsCarrier[] = $carrier;
            }
        }

        return $idsCarrier;
    }
}
