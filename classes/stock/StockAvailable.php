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
 * Represents quantities available
 * It is either synchronized with Stock or manualy set by the seller
 *
 * @since 1.0.0
 */
class StockAvailableCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int identifier of the current product */
    public $id_product;
    /** @var int identifier of product attribute if necessary */
    public $id_product_attribute;
    /** @var int the shop associated to the current product and corresponding quantity */
    public $id_shop;
    /** @var int the group shop associated to the current product and corresponding quantity */
    public $id_shop_group;
    /** @var int the quantity available for sale */
    public $quantity = 0;
    /** @var bool determine if the available stock value depends on physical stock */
    public $depends_on_stock = false;
    /** @var bool determine if a product is out of stock - it was previously in Product class */
    public $out_of_stock = false;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'stock_available',
        'primary' => 'id_stock_available',
        'fields'  => [
            'id_product'           => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedId', 'required' => true],
            'id_product_attribute' => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedId', 'required' => true],
            'id_shop'              => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedId'                    ],
            'id_shop_group'        => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedId'                    ],
            'quantity'             => ['type' => self::TYPE_INT,  'validate' => 'isInt',        'required' => true],
            'depends_on_stock'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool',       'required' => true],
            'out_of_stock'         => ['type' => self::TYPE_INT,  'validate' => 'isInt',        'required' => true],
        ],
    ];

    /**
     * @see ObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'fields'        => [
            'id_product'           => ['xlink_resource' => 'products'],
            'id_product_attribute' => ['xlink_resource' => 'combinations'],
            'id_shop'              => ['xlink_resource' => 'shops'],
            'id_shop_group'        => ['xlink_resource' => 'shop_groups'],
        ],
        'hidden_fields' => [],
        'objectMethods' => [
            'add'    => 'addWs',
            'update' => 'updateWs',
        ],
    ];

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @return bool
     */
    public function updateWs()
    {
        if ($this->depends_on_stock) {
            return WebserviceRequest::getInstance()->setError(500, Tools::displayError('You cannot update the available stock when it depends on stock.'), 133);
        }

        return $this->update();
    }

    /**
     * @param int      $idProduct
     * @param int|null $idProductAttribute
     * @param int|null $idShop
     *
     * @return bool|int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getStockAvailableIdByProductId($idProduct, $idProductAttribute = null, $idShop = null)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        $query = new DbQuery();
        $query->select('id_stock_available');
        $query->from('stock_available');
        $query->where('id_product = '.(int) $idProduct);

        if ($idProductAttribute !== null) {
            $query->where('id_product_attribute = '.(int) $idProductAttribute);
        }

        $query = static::addSqlShopRestriction($query, $idShop);

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * For a given id_product, synchronizes StockAvailable::quantity with Stock::usable_quantity
     *
     * @param int      $idProduct
     * @param int|null $orderIdShop
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function synchronize($idProduct, $orderIdShop = null)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        //if product is pack sync recursivly product in pack
        if (Pack::isPack($idProduct)) {
            if (Validate::isLoadedObject($product = new Product((int) $idProduct))) {
                if ($product->pack_stock_type == 1 || $product->pack_stock_type == 2 || ($product->pack_stock_type == 3 && Configuration::get('PS_PACK_STOCK_TYPE') > 0)) {
                    $productsPack = Pack::getItems($idProduct, (int) Configuration::get('PS_LANG_DEFAULT'));
                    foreach ($productsPack as $productPack) {
                        static::synchronize($productPack->id, $orderIdShop);
                    }
                }
            } else {
                return false;
            }
        }

        // gets warehouse ids grouped by shops
        $idsWarehouse = Warehouse::getWarehousesGroupedByShops();
        if ($orderIdShop !== null) {
            $orderWarehouses = [];
            $wh = Warehouse::getWarehouses(false, (int) $orderIdShop);
            foreach ($wh as $warehouse) {
                $orderWarehouses[] = $warehouse['id_warehouse'];
            }
        }

        // gets all product attributes ids
        $idsProductAttribute = [];
        foreach (Product::getProductAttributesIds($idProduct) as $idProductAttribute) {
            $idsProductAttribute[] = $idProductAttribute['id_product_attribute'];
        }

        // Allow to order the product when out of stock?
        $outOfStock = static::outOfStock($idProduct);

        $manager = StockManagerFactory::getManager();
        // loops on $ids_warehouse to synchronize quantities
        foreach ($idsWarehouse as $idShop => $warehouses) {
            // first, checks if the product depends on stock for the given shop $id_shop
            if (static::dependsOnStock($idProduct, $idShop)) {
                // init quantity
                $productQuantity = 0;

                // if it's a simple product
                if (empty($idsProductAttribute)) {
                    $allowedWarehouseForProduct = WareHouse::getProductWarehouseList((int) $idProduct, 0, (int) $idShop);
                    $allowedWarehouseForProductClean = [];
                    foreach ($allowedWarehouseForProduct as $warehouse) {
                        $allowedWarehouseForProductClean[] = (int) $warehouse['id_warehouse'];
                    }
                    $allowedWarehouseForProductClean = array_intersect($allowedWarehouseForProductClean, $warehouses);
                    if ($orderIdShop != null && !count(array_intersect($allowedWarehouseForProductClean, $orderWarehouses))) {
                        continue;
                    }

                    $productQuantity = $manager->getProductRealQuantities($idProduct, null, $allowedWarehouseForProductClean, true);

                    Hook::exec(
                        'actionUpdateQuantity',
                        [
                            'id_product'           => $idProduct,
                            'id_product_attribute' => 0,
                            'quantity'             => $productQuantity,
                            'id_shop'              => $idShop,
                        ]
                    );
                } // else this product has attributes, hence loops on $ids_product_attribute
                else {
                    foreach ($idsProductAttribute as $idProductAttribute) {
                        $allowedWarehouseForCombination = WareHouse::getProductWarehouseList((int) $idProduct, (int) $idProductAttribute, (int) $idShop);
                        $allowedWarehouseForCombinationClean = [];
                        foreach ($allowedWarehouseForCombination as $warehouse) {
                            $allowedWarehouseForCombinationClean[] = (int) $warehouse['id_warehouse'];
                        }
                        $allowedWarehouseForCombinationClean = array_intersect($allowedWarehouseForCombinationClean, $warehouses);
                        if ($orderIdShop != null && !count(array_intersect($allowedWarehouseForCombinationClean, $orderWarehouses))) {
                            continue;
                        }

                        $quantity = $manager->getProductRealQuantities($idProduct, $idProductAttribute, $allowedWarehouseForCombinationClean, true);

                        $query = new DbQuery();
                        $query->select('COUNT(*)');
                        $query->from('stock_available');
                        $query->where('id_product = '.(int) $idProduct.' AND id_product_attribute = '.(int) $idProductAttribute.static::addSqlShopRestriction(null, $idShop));

                        if ((int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query)) {
                            $query = [
                                'table' => 'stock_available',
                                'data'  => ['quantity' => $quantity],
                                'where' => 'id_product = '.(int) $idProduct.' AND id_product_attribute = '.(int) $idProductAttribute.static::addSqlShopRestriction(null, $idShop),
                            ];
                            Db::getInstance()->update($query['table'], $query['data'], $query['where']);
                        } else {
                            $query = [
                                'table' => 'stock_available',
                                'data'  => [
                                    'quantity'             => $quantity,
                                    'depends_on_stock'     => 1,
                                    'out_of_stock'         => $outOfStock,
                                    'id_product'           => (int) $idProduct,
                                    'id_product_attribute' => (int) $idProductAttribute,
                                ],
                            ];
                            static::addSqlShopParams($query['data'], $idShop);
                            Db::getInstance()->insert($query['table'], $query['data']);
                        }

                        $productQuantity += $quantity;

                        Hook::exec(
                            'actionUpdateQuantity',
                            [
                                'id_product'           => $idProduct,
                                'id_product_attribute' => $idProductAttribute,
                                'quantity'             => $quantity,
                                'id_shop'              => $idShop,
                            ]
                        );
                    }
                }
                // updates
                // if $id_product has attributes, it also updates the sum for all attributes
                if (($orderIdShop != null && array_intersect($warehouses, $orderWarehouses)) || $orderIdShop == null) {
                    $query = [
                        'table' => 'stock_available',
                        'data'  => ['quantity' => $productQuantity],
                        'where' => 'id_product = '.(int) $idProduct.' AND id_product_attribute = 0'.static::addSqlShopRestriction(null, $idShop),
                    ];
                    Db::getInstance()->update($query['table'], $query['data'], $query['where']);
                }
            }
        }
        // In case there are no warehouses, removes product from StockAvailable
        if (count($idsWarehouse) == 0 && static::dependsOnStock((int) $idProduct)) {
            Db::getInstance()->update('stock_available', ['quantity' => 0], 'id_product = '.(int) $idProduct);
        }

        Cache::clean('StockAvailable::getQuantityAvailableByProduct_'.(int) $idProduct.'*');
    }

    /**
     * For a given id_product, sets if stock available depends on stock
     *
     * @param int      $idProduct
     * @param int|bool $dependsOnStock true by default
     * @param int|null $idShop         gets context by default
     * @param int      $idProductAttribute
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function setProductDependsOnStock($idProduct, $dependsOnStock = true, $idShop = null, $idProductAttribute = 0)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        $existingId = static::getStockAvailableIdByProductId((int) $idProduct, (int) $idProductAttribute, $idShop);
        if ($existingId > 0) {
            Db::getInstance()->update(
                'stock_available',
                [
                    'depends_on_stock' => (int) $dependsOnStock,
                ],
                'id_stock_available = '.(int) $existingId
            );
        } else {
            $params = [
                'depends_on_stock'     => (int) $dependsOnStock,
                'id_product'           => (int) $idProduct,
                'id_product_attribute' => (int) $idProductAttribute,
            ];

            static::addSqlShopParams($params, $idShop);

            Db::getInstance()->insert('stock_available', $params);
        }

        // depends on stock.. hence synchronizes
        if ($dependsOnStock) {
            static::synchronize($idProduct);
        }
    }

    /**
     * For a given id_product, sets if product is available out of stocks
     *
     * @param int      $idProduct
     * @param bool|int $outOfStock Optional false by default
     * @param int      $idShop     Optional gets context by default
     * @param int      $idProductAttribute
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function setProductOutOfStock($idProduct, $outOfStock = false, $idShop = null, $idProductAttribute = 0)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        $existingId = (int) static::getStockAvailableIdByProductId((int) $idProduct, (int) $idProductAttribute, $idShop);

        if ($existingId > 0) {
            Db::getInstance()->update(
                'stock_available',
                ['out_of_stock' => (int) $outOfStock],
                'id_product = '.(int) $idProduct.(($idProductAttribute) ? ' AND id_product_attribute = '.(int) $idProductAttribute : '').static::addSqlShopRestriction(null, $idShop)
            );
        } else {
            $params = [
                'out_of_stock'         => (int) $outOfStock,
                'id_product'           => (int) $idProduct,
                'id_product_attribute' => (int) $idProductAttribute,
            ];

            static::addSqlShopParams($params, $idShop);
            Db::getInstance()->insert('stock_available', $params, false, true, Db::ON_DUPLICATE_KEY);
        }
    }

    /**
     * For a given id_product and id_product_attribute, gets its stock available
     *
     * @param int $idProduct
     * @param int $idProductAttribute Optional
     * @param int $idShop             Optional : gets context by default
     *
     * @return int Quantity
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getQuantityAvailableByProduct($idProduct = null, $idProductAttribute = null, $idShop = null)
    {
        // if null, it's a product without attributes
        if ($idProductAttribute === null) {
            $idProductAttribute = 0;
        }

        $key = 'StockAvailable::getQuantityAvailableByProduct_'.(int) $idProduct.'-'.(int) $idProductAttribute.'-'.(int) $idShop;
        if (!Cache::isStored($key)) {
            $query = new DbQuery();
            $query->select('SUM(quantity)');
            $query->from('stock_available');

            // if null, it's a product without attributes
            if ($idProduct !== null) {
                $query->where('id_product = '.(int) $idProduct);
            }

            $query->where('id_product_attribute = '.(int) $idProductAttribute);
            $query = static::addSqlShopRestriction($query, $idShop);
            $result = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
            Cache::store($key, $result);

            return $result;
        }

        return Cache::retrieve($key);
    }

    /**
     * Upgrades total_quantity_available after having saved
     *
     * @see     ObjectModel::add()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (!$result = parent::add($autoDate, $nullValues)) {
            return false;
        }

        $result &= $this->postSave();

        return $result;
    }

    /**
     * Upgrades total_quantity_available after having update
     *
     * @see     ObjectModel::update()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param bool $nullValues
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        if (!$result = parent::update($nullValues)) {
            return false;
        }

        $result &= $this->postSave();

        return $result;
    }

    /**
     * Upgrades total_quantity_available after having saved
     *
     * @see StockAvailableCore::update()
     * @see StockAvailableCore::add()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function postSave()
    {
        if ($this->id_product_attribute == 0) {
            return true;
        }

        $idShop = (Shop::getContext() != Shop::CONTEXT_GROUP && $this->id_shop ? $this->id_shop : null);

        if (!Configuration::get('PS_DISP_UNAVAILABLE_ATTR')) {
            $combination = new Combination((int) $this->id_product_attribute);
            if ($colors = $combination->getColorsAttributes()) {
                $product = new Product((int) $this->id_product);
                foreach ($colors as $color) {
                    if ($product->isColorUnavailable((int) $color['id_attribute'], (int) $this->id_shop)) {
                        Tools::clearColorListCache($product->id);
                        break;
                    }
                }
            }
        }

        $totalQuantity = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('SUM(`quantity`) AS `quantity`')
                ->from(bqSQL(static::$definition['table']))
                ->where('`id_product` = '.(int) $this->id_product)
                ->where('`id_product_attribute` <> 0 '.static::addSqlShopRestriction(null, $idShop))
        );
        $this->setQuantity($this->id_product, 0, $totalQuantity, $idShop);

        return true;
    }

    /**
     * For a given id_product and id_product_attribute updates the quantity available
     * If $avoid_parent_pack_update is true, then packs containing the given product won't be updated
     *
     * @param int $idProduct
     * @param int $idProductAttribute Optional
     * @param int $deltaQuantity      The delta quantity to update
     * @param int $idShop             Optional
     *
     * @return bool
     * @throws Adapter_Exception
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function updateQuantity($idProduct, $idProductAttribute, $deltaQuantity, $idShop = null)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }
        $product = new Product((int) $idProduct);
        if (!Validate::isLoadedObject($product)) {
            return false;
        }

        $stockManager = Adapter_ServiceLocator::get('Core_Business_Stock_StockManager');
        $stockManager->updateQuantity($product, $idProductAttribute, $deltaQuantity, $idShop = null);

        return true;
    }

    /**
     * For a given id_product and id_product_attribute sets the quantity available
     *
     * @param int $idProduct
     * @param int $idProductAttribute Optional
     * @param     $quantity
     * @param int $idShop             Optional
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function setQuantity($idProduct, $idProductAttribute, $quantity, $idShop = null)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        $context = Context::getContext();

        // if there is no $id_shop, gets the context one
        if ($idShop === null && Shop::getContext() != Shop::CONTEXT_GROUP) {
            $idShop = (int) $context->shop->id;
        }

        $dependsOnStock = static::dependsOnStock($idProduct);

        //Try to set available quantity if product does not depend on physical stock
        if (!$dependsOnStock) {
            $idStockAvailable = (int) static::getStockAvailableIdByProductId($idProduct, $idProductAttribute, $idShop);
            if ($idStockAvailable) {
                $stockAvailable = new StockAvailable($idStockAvailable);
                $stockAvailable->quantity = (int) $quantity;
                $stockAvailable->update();
            } else {
                $outOfStock = static::outOfStock($idProduct, $idShop);
                $stockAvailable = new StockAvailable();
                $stockAvailable->out_of_stock = (int) $outOfStock;
                $stockAvailable->id_product = (int) $idProduct;
                $stockAvailable->id_product_attribute = (int) $idProductAttribute;
                $stockAvailable->quantity = (int) $quantity;

                if ($idShop === null) {
                    $shopGroup = Shop::getContextShopGroup();
                } else {
                    $shopGroup = new ShopGroup((int) Shop::getGroupFromShop((int) $idShop));
                }

                // if quantities are shared between shops of the group
                if ($shopGroup->share_stock) {
                    $stockAvailable->id_shop = 0;
                    $stockAvailable->id_shop_group = (int) $shopGroup->id;
                } else {
                    $stockAvailable->id_shop = (int) $idShop;
                    $stockAvailable->id_shop_group = 0;
                }
                $stockAvailable->add();
            }

            Hook::exec(
                'actionUpdateQuantity',
                [
                    'id_product'           => $idProduct,
                    'id_product_attribute' => $idProductAttribute,
                    'quantity'             => $stockAvailable->quantity,
                ]
            );
        }

        Cache::clean('StockAvailable::getQuantityAvailableByProduct_'.(int) $idProduct.'*');
    }

    /**
     * Removes a given product from the stock available
     *
     * @param int       $idProduct
     * @param int|null  $idProductAttribute Optional
     * @param Shop|null $shop               Shop id or shop object Optional
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function removeProductFromStockAvailable($idProduct, $idProductAttribute = null, $shop = null)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        if (Shop::getContext() == SHOP::CONTEXT_SHOP) {
            if (Shop::getContextShopGroup()->share_stock == 1) {
                $paSql = '';
                if ($idProductAttribute !== null) {
                    $paSql = '_attribute';
                    $idProductAttributeSql = $idProductAttribute;
                } else {
                    $idProductAttributeSql = $idProduct;
                }

                if ((int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    (new DbQuery())
                        ->select('COUNT(*)')
                        ->from('product'.bqSQL($paSql).'_shop')
                        ->where('`id_product'.bqSQL($paSql).'` = '.(int) $idProductAttributeSql)
                        ->where('`id_shop` IN ('.implode(',', array_map('intval', Shop::getContextListShopID(SHOP::SHARE_STOCK))).')')
                )) {
                    return true;
                }
            }
        }

        $res = Db::getInstance()->delete(
            'stock_available',
            '`id_product` = '.(int) $idProduct.($idProductAttribute ? ' AND `id_product_attribute` = '.(int) $idProductAttribute : '').static::addSqlShopRestriction(null, $shop)
        );

        if ($idProductAttribute) {
            if ($shop === null || !Validate::isLoadedObject($shop)) {
                $shopDatas = [];
                static::addSqlShopParams($shopDatas);
                $idShop = (int) $shopDatas['id_shop'];
            } else {
                $idShop = (int) $shop->id;
            }

            $stockAvailable = new StockAvailable();
            $stockAvailable->id_product = (int) $idProduct;
            $stockAvailable->id_product_attribute = (int) $idProductAttribute;
            $stockAvailable->id_shop = (int) $idShop;
            $stockAvailable->postSave();
        }

        Cache::clean('StockAvailable::getQuantityAvailableByProduct_'.(int) $idProduct.'*');

        return $res;
    }

    /**
     * Removes all product quantities from all a group of shops
     * If stocks are shared, remoe all old available quantities for all shops of the group
     * Else remove all available quantities for the current group
     *
     * @param ShopGroup $shopGroup the ShopGroup object
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function resetProductFromStockAvailableByShopGroup(ShopGroup $shopGroup)
    {
        if ($shopGroup->share_stock) {
            $shopList = Shop::getShops(false, $shopGroup->id, true);
        }

        if (isset($shopList) && count($shopList) > 0) {
            $idShopsList = implode(', ', $shopList);

            return Db::getInstance()->update('stock_available', ['quantity' => 0], 'id_shop IN ('.$idShopsList.')');
        } else {
            return Db::getInstance()->update('stock_available', ['quantity' => 0], 'id_shop_group = '.$shopGroup->id);
        }
    }

    /**
     * For a given product, tells if it depends on the physical (usable) stock
     *
     * @param int $idProduct
     * @param int $idShop    Optional : gets context if null @see Context::getContext()
     *
     * @return bool : depends on stock @see $depends_on_stock
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function dependsOnStock($idProduct, $idShop = null)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        $query = new DbQuery();
        $query->select('depends_on_stock');
        $query->from('stock_available');
        $query->where('id_product = '.(int) $idProduct);
        $query->where('id_product_attribute = 0');

        $query = static::addSqlShopRestriction($query, $idShop);

        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * For a given product, get its "out of stock" flag
     *
     * @param int $idProduct
     * @param int $idShop    Optional : gets context if null @see Context::getContext()
     *
     * @return bool : depends on stock @see $depends_on_stock
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function outOfStock($idProduct, $idShop = null)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        $query = new DbQuery();
        $query->select('out_of_stock');
        $query->from('stock_available');
        $query->where('id_product = '.(int) $idProduct);
        $query->where('id_product_attribute = 0');

        $query = static::addSqlShopRestriction($query, $idShop);

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * Add an sql restriction for shops fields - specific to StockAvailable
     *
     * @param DbQuery|string|null $sql   Reference to the query object
     * @param Shop|int|null       $shop  Optional : The shop ID
     * @param string|null         $alias Optional : The current table alias
     *
     * @return string|DbQuery DbQuery object or the sql restriction string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function addSqlShopRestriction($sql = null, $shop = null, $alias = null)
    {
        $context = Context::getContext();

        if (!empty($alias)) {
            $alias .= '.';
        }

        // if there is no $id_shop, gets the context one
        // get shop group too
        if ($shop === null || $shop === $context->shop->id) {
            if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                $shopGroup = Shop::getContextShopGroup();
            } else {
                $shopGroup = $context->shop->getGroup();
            }
            $shop = $context->shop;
        } elseif (is_object($shop)) {
            /** @var Shop $shop */
            $shopGroup = $shop->getGroup();
        } else {
            $shop = new Shop($shop);
            $shopGroup = $shop->getGroup();
        }

        // if quantities are shared between shops of the group
        if ($shopGroup->share_stock) {
            if (is_object($sql)) {
                $sql->where(pSQL($alias).'id_shop_group = '.(int) $shopGroup->id);
                $sql->where(pSQL($alias).'id_shop = 0');
            } else {
                $sql = ' AND '.pSQL($alias).'id_shop_group = '.(int) $shopGroup->id.' ';
                $sql .= ' AND '.pSQL($alias).'id_shop = 0 ';
            }
        } else {
            if (is_object($sql)) {
                $sql->where(pSQL($alias).'id_shop = '.(int) $shop->id);
                $sql->where(pSQL($alias).'id_shop_group = 0');
            } else {
                $sql = ' AND '.pSQL($alias).'id_shop = '.(int) $shop->id.' ';
                $sql .= ' AND '.pSQL($alias).'id_shop_group = 0 ';
            }
        }

        return $sql;
    }

    /**
     * Add sql params for shops fields - specific to StockAvailable
     *
     * @param array $params Reference to the params array
     * @param int   $idShop Optional : The shop ID
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function addSqlShopParams(&$params, $idShop = null)
    {
        $context = Context::getContext();
        $groupOk = false;

        // if there is no $id_shop, gets the context one
        // get shop group too
        if ($idShop === null) {
            if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                $shopGroup = Shop::getContextShopGroup();
            } else {
                $shopGroup = $context->shop->getGroup();
                $idShop = $context->shop->id;
            }
        } else {
            $shop = new Shop($idShop);
            $shopGroup = $shop->getGroup();
        }

        // if quantities are shared between shops of the group
        if ($shopGroup->share_stock) {
            $params['id_shop_group'] = (int) $shopGroup->id;
            $params['id_shop'] = 0;

            $groupOk = true;
        } else {
            $params['id_shop_group'] = 0;
        }

        // if no group specific restriction, set simple shop restriction
        if (!$groupOk) {
            $params['id_shop'] = (int) $idShop;
        }
    }

    /**
     * Copies stock available content table
     *
     * @param int $srcShopId
     * @param int $dstShopId
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function copyStockAvailableFromShopToShop($srcShopId, $dstShopId)
    {
        if (!$srcShopId || !$dstShopId) {
            return false;
        }

        $query = '
			INSERT INTO '._DB_PREFIX_.'stock_available
			(
				id_product,
				id_product_attribute,
				id_shop,
				id_shop_group,
				quantity,
				depends_on_stock,
				out_of_stock
			)
			(
				SELECT id_product, id_product_attribute, '.(int) $dstShopId.', 0, quantity, depends_on_stock, out_of_stock
				FROM '._DB_PREFIX_.'stock_available
				WHERE id_shop = '.(int) $srcShopId.
            ')';

        return Db::getInstance()->execute($query);
    }
}
