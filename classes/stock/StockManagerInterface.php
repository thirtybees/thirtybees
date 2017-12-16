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
 * StockManagerInterface : defines a way to manage stock
 *
 * @since 1.0.0
 */
interface StockManagerInterface
{
    /**
     * Checks if the StockManager is available
     *
     * @return StockManagerInterface
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isAvailable();

    /**
     * For a given product, adds a given quantity
     *
     * @param int       $idProduct
     * @param int       $idProductAttribute
     * @param Warehouse $warehouse
     * @param int       $quantity
     * @param int       $idStockMovementReason
     * @param float     $priceTe
     * @param bool      $isUsable
     * @param int       $idSupplyOrder optionnal
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addProduct($idProduct, $idProductAttribute, Warehouse $warehouse, $quantity, $idStockMovementReason, $priceTe, $isUsable = true, $idSupplyOrder = null);

    /**
     * For a given product, removes a given quantity
     *
     * @param int           $idProduct
     * @param int|null      $idProductAttribute
     * @param Warehouse     $warehouse
     * @param int           $quantity
     * @param int           $id_stock_mvt_reason
     * @param bool          $isUsable
     * @param int|null      $idOrder
     * @param int           $ignorePack
     * @param Employee|null $employee
     * @param Stock|null    $stock
     *
     * @return array - empty if an error occurred | details of removed products quantities with corresponding prices otherwise
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function removeProduct(
        $idProduct,
        $idProductAttribute,
        Warehouse $warehouse,
        $quantity,
        $idStockMovementReason,
        $isUsable = true,
        $idOrder = null,
        $ignorePack = 0,
        $employee = null,
        Stock $stock = null
    );

    /**
     * For a given product, returns its physical quantity
     * If the given product has combinations and $id_product_attribute is null, returns the sum for all combinations
     *
     * @param int       $idProduct
     * @param int       $idProductAttribute
     * @param array|int $idsWarehouse optional
     * @param bool      $usable       false default - in this case we retrieve all physical quantities, otherwise we retrieve physical quantities flagged as usable
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductPhysicalQuantities($idProduct, $idProductAttribute, $idsWarehouse = null, $usable = false);

    /**
     * For a given product, returns its real quantity
     * If the given product has combinations and $id_product_attribute is null, returns the sum for all combinations
     * Real quantity : (physical_qty + supply_orders_qty - client_orders_qty)
     * If $usable is defined, real quantity: usable_qty + supply_orders_qty - client_orders_qty
     *
     * @param int       $idProduct
     * @param int       $idProductAttribute
     * @param array|int $idsWarehouse optional
     * @param bool      $usable       false by default
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductRealQuantities($idProduct, $idProductAttribute, $idsWarehouse = null, $usable = false);

    /**
     * For a given product, transfers quantities between two warehouses
     * By default, it manages usable quantities
     * It is also possible to transfer a usable quantity from warehouse 1 in an unusable quantity to warehouse 2
     * It is also possible to transfer a usable quantity from warehouse 1 in an unusable quantity to warehouse 1
     *
     * @param int  $idProduct
     * @param int  $idProductAttribute
     * @param int  $quantity
     * @param int  $warehouseFrom
     * @param int  $warehouseTo
     * @param bool $usableFrom Optional, true by default
     * @param bool $usableTo   Optional, true by default
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function transferBetweenWarehouses($idProduct, $idProductAttribute, $quantity, $warehouseFrom, $warehouseTo, $usableFrom = true, $usableTo = true);

    /**
     * For a given product, returns the time left before being out of stock.
     * By default, for the given product, it will use sum(quantities removed in all warehouses)
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $coverage
     * @param int $idWarehouse Optional
     *
     * @return int time
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductCoverage($idProduct, $idProductAttribute, $coverage, $idWarehouse = null);
}
