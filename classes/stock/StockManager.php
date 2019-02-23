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
 * Class StockManagerCore
 */
class StockManagerCore implements StockManagerInterface
{
    /**
     * @see     StockManagerInterface::isAvailable()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isAvailable()
    {
        // Default Manager : always available
        return true;
    }

    /**
     * @see     StockManagerInterface::addProduct()
     *
     * @param int           $idProduct
     * @param int           $idProductAttribute
     * @param Warehouse     $warehouse
     * @param int           $quantity
     * @param int           $idStockMvtReason
     * @param float         $priceTe
     * @param bool          $isUsable
     * @param int|null      $idSupplyOrder
     * @param Employee|null $employee
     *
     * @return bool
     * @throws PrestaShopException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addProduct(
        $idProduct,
        $idProductAttribute = 0,
        Warehouse $warehouse,
        $quantity,
        $idStockMvtReason,
        $priceTe,
        $isUsable = true,
        $idSupplyOrder = null,
        $employee = null
    ) {
        if ($this->shouldPreventStockOperation($warehouse, $idProduct, $quantity)) {
            return false;
        }

        $priceTe = round($priceTe, _TB_PRICE_DATABASE_PRECISION_);
        if ($priceTe < 0.0) { // why <= ?
            return false;
        }

        if (!StockMvtReason::exists($idStockMvtReason)) {
            $idStockMvtReason = Configuration::get('PS_STOCK_MVT_INC_REASON_DEFAULT');
        }

        $context = Context::getContext();

        $mvtParams = [
            'id_stock'            => null,
            'physical_quantity'   => $quantity,
            'id_stock_mvt_reason' => $idStockMvtReason,
            'id_supply_order'     => $idSupplyOrder,
            'price_te'            => $priceTe,
            'last_wa'             => null,
            'current_wa'          => null,
            'id_employee'         => (int) $context->employee->id ? (int) $context->employee->id : $employee->id,
            'employee_firstname'  => $context->employee->firstname ? $context->employee->firstname : $employee->firstname,
            'employee_lastname'   => $context->employee->lastname ? $context->employee->lastname : $employee->lastname,
            'sign'                => 1,
        ];

        $stockExists = false;

        // switch on MANAGEMENT_TYPE
        switch ($warehouse->management_type) {
            // case CUMP mode
            case 'WA':
                $stockCollection = $this->getStockCollection($idProduct, $idProductAttribute, $warehouse->id);

                // if this product is already in stock
                if (count($stockCollection) > 0) {
                    $stockExists = true;

                    /** @var Stock $stock */
                    // for a warehouse using WA, there is one and only one stock for a given product
                    $stock = $stockCollection->current();

                    // calculates WA price
                    $lastWa = $stock->price_te;
                    $currentWa = $this->calculateWA($stock, $quantity, $priceTe);

                    $mvtParams['id_stock'] = $stock->id;
                    $mvtParams['last_wa'] = $lastWa;
                    $mvtParams['current_wa'] = $currentWa;

                    $stockParams = [
                        'physical_quantity' => ($stock->physical_quantity + $quantity),
                        'price_te'          => $currentWa,
                        'usable_quantity'   => ($isUsable ? ($stock->usable_quantity + $quantity) : $stock->usable_quantity),
                        'id_warehouse'      => $warehouse->id,
                    ];

                    // saves stock in warehouse
                    $stock->hydrate($stockParams);
                    $stock->update();
                } else {
                    // else, the product is not in sock

                    $mvtParams['last_wa'] = 0;
                    $mvtParams['current_wa'] = $priceTe;
                }
                break;

            // case FIFO / LIFO mode
            case 'FIFO':
            case 'LIFO':
                $stockCollection = $this->getStockCollection($idProduct, $idProductAttribute, $warehouse->id, $priceTe);

                // if this product is already in stock
                if (count($stockCollection) > 0) {
                    $stockExists = true;

                    /** @var Stock $stock */
                    // there is one and only one stock for a given product in a warehouse and at the current unit price
                    $stock = $stockCollection->current();

                    $stockParams = [
                        'physical_quantity' => ($stock->physical_quantity + $quantity),
                        'usable_quantity'   => ($isUsable ? ($stock->usable_quantity + $quantity) : $stock->usable_quantity),
                    ];

                    // updates stock in warehouse
                    $stock->hydrate($stockParams);
                    $stock->update();

                    // sets mvt_params
                    $mvtParams['id_stock'] = $stock->id;
                }

                break;

            default:
                return false;
                break;
        }

        if (!$stockExists) {
            $stock = new Stock();

            $stockParams = [
                'id_product_attribute' => $idProductAttribute,
                'id_product'           => $idProduct,
                'physical_quantity'    => $quantity,
                'price_te'             => $priceTe,
                'usable_quantity'      => ($isUsable ? $quantity : 0),
                'id_warehouse'         => $warehouse->id,
            ];

            // saves stock in warehouse
            $stock->hydrate($stockParams);
            $stock->add();
            $mvtParams['id_stock'] = $stock->id;
        }

        // saves stock mvt
        $stockMvt = new StockMvt();
        $stockMvt->hydrate($mvtParams);
        $stockMvt->add();

        return true;
    }

    /**
     * @see StockManagerInterface::removeProduct()
     *
     * @param int           $idProduct
     * @param int|null      $idProductAttribute
     * @param Warehouse     $warehouse
     * @param int           $quantity
     * @param int           $idStockMvtReason
     * @param bool          $isUsable
     * @param int|null      $idOrder
     * @param int           $ignorePack
     * @param Employee|null $employee
     * @param Stock|null    $stock
     *
     * @return array
     * @throws PrestaShopException
     */
    public function removeProduct(
        $idProduct,
        $idProductAttribute = null,
        Warehouse $warehouse,
        $quantity,
        $idStockMvtReason,
        $isUsable = true,
        $idOrder = null,
        $ignorePack = 0,
        $employee = null,
        Stock $stock = null
    ) {
        $removedProducts = [];

        if ($this->shouldPreventStockOperation($warehouse, $idProduct, $quantity)) {
            return $removedProducts;
        }

        $idStockMvtReason = $this->ensureStockMovementReasonIsValid($idStockMvtReason);

        if ($this->shouldHandleStockOperationForProductsPack($idProduct, $ignorePack)) {
            if (Validate::isLoadedObject($product = new Product((int) $idProduct))) {
                // Gets items
                if (
                    $product->pack_stock_type == 1 ||
                    $product->pack_stock_type == 2 || (
                        $product->pack_stock_type == 3 &&
                        Configuration::get('PS_PACK_STOCK_TYPE') > 0
                    )
                ) {
                    $productsPack = Pack::getItems((int) $idProduct, (int) Configuration::get('PS_LANG_DEFAULT'));
                    // Foreach item
                    foreach ($productsPack as $productPack) {
                        if ($productPack->advanced_stock_management == 1) {
                            $productWarehouses = Warehouse::getProductWarehouseList($productPack->id, $productPack->id_pack_product_attribute);
                            $warehouseStockFound = false;
                            foreach ($productWarehouses as $productWarehouse) {
                                if (!$warehouseStockFound) {
                                    if (Warehouse::exists($productWarehouse['id_warehouse'])) {
                                        $currentWarehouse = new Warehouse($productWarehouse['id_warehouse']);
                                        $removedProducts[] = $this->removeProduct(
                                            $productPack->id,
                                            $productPack->id_pack_product_attribute,
                                            $currentWarehouse,
                                            $productPack->pack_quantity * $quantity,
                                            $idStockMvtReason,
                                            $isUsable,
                                            $idOrder
                                        );

                                        // The product was found on this warehouse. Stop the stock searching.
                                        $warehouseStockFound = !empty($removedProducts[count($removedProducts) - 1]);
                                    }
                                }
                            }
                        }
                    }
                }

                if ($product->pack_stock_type == 0 ||
                    $product->pack_stock_type == 2 || (
                        $product->pack_stock_type == 3 && (
                            Configuration::get('PS_PACK_STOCK_TYPE') == 0 ||
                            Configuration::get('PS_PACK_STOCK_TYPE') == 2
                        )
                    )
                ) {
                    $removedProducts = array_merge(
                        $removedProducts,
                        $this->removeProduct(
                            $idProduct,
                            $idProductAttribute,
                            $warehouse,
                            $quantity,
                            $idStockMvtReason,
                            $isUsable,
                            $idOrder,
                            1
                        )
                    );
                }
            } else {
                return false;
            }
        } else {
            $quantityInStock = $this->computeProductQuantityInStock(
                $warehouse,
                $idProduct,
                $idProductAttribute,
                $isUsable,
                $stock
            );

            if ($this->ensureProductQuantityRequestedForRemovalIsValid($quantity, $quantityInStock)) {
                return $removedProducts;
            }

            $stockCollection = $this->getProductStockLinesInWarehouse(
                $idProduct,
                $idProductAttribute,
                $warehouse,
                $stock
            );

            /** @var \Countable $stockCollection */
            if (count($stockCollection) <= 0) {
                return $removedProducts;
            }

            // switch on MANAGEMENT_TYPE
            switch ($warehouse->management_type) {
                // case CUMP mode
                case 'WA':
                    /** @var Stock $stock */
                    // There is one and only one stock for a given product in a warehouse in this mode
                    $stock = $stockCollection->current();

                    $this->removeProductQuantityApplyingCump(
                        $quantity,
                        $idStockMvtReason,
                        $isUsable,
                        $idOrder,
                        $employee,
                        $stock
                    );

                    $removedProducts[$stock->id]['quantity'] = $quantity;
                    $removedProducts[$stock->id]['price_te'] = $stock->price_te;

                    break;

                case 'LIFO':
                case 'FIFO':

                    $stockHistoryQtyAvailable = [];
                    $quantityToDecrementByStock = [];
                    $globalQuantityToDecrement = $quantity;

                    // for each stock, parse its mvts history to calculate the quantities left for each positive mvt,
                    // according to the instant available quantities for this stock
                    foreach ($stockCollection as $stock) {
                        /** @var Stock $stock */
                        $leftQuantityToCheck = $stock->physical_quantity;
                        if ($leftQuantityToCheck <= 0) {
                            continue;
                        }

                        $resource = Db::getInstance(_PS_USE_SQL_SLAVE_)->query(
                            '
							SELECT sm.`id_stock_mvt`, sm.`date_add`, sm.`physical_quantity`,
								IF ((sm2.`physical_quantity` is null), sm.`physical_quantity`, (sm.`physical_quantity` - SUM(sm2.`physical_quantity`))) as qty
							FROM `'._DB_PREFIX_.'stock_mvt` sm
							LEFT JOIN `'._DB_PREFIX_.'stock_mvt` sm2 ON sm2.`referer` = sm.`id_stock_mvt`
							WHERE sm.`sign` = 1
							AND sm.`id_stock` = '.(int) $stock->id.'
							GROUP BY sm.`id_stock_mvt`
							ORDER BY sm.`date_add` DESC'
                        );

                        while ($row = Db::getInstance()->nextRow($resource)) {
                            // continue - in FIFO mode, we have to retreive the oldest positive mvts for which there are left quantities
                            if ($warehouse->management_type == 'FIFO') {
                                if ($row['qty'] == 0) {
                                    continue;
                                }
                            }

                            // converts date to timestamp
                            $date = new DateTime($row['date_add']);
                            $timestamp = $date->format('U');

                            // history of the mvt
                            $stockHistoryQtyAvailable[$timestamp] = [
                                'id_stock'     => $stock->id,
                                'id_stock_mvt' => (int) $row['id_stock_mvt'],
                                'qty'          => (int) $row['qty'],
                            ];

                            // break - in LIFO mode, checks only the necessary history to handle the global quantity for the current stock
                            if ($warehouse->management_type == 'LIFO') {
                                $leftQuantityToCheck -= (int) $row['qty'];
                                if ($leftQuantityToCheck <= 0) {
                                    break;
                                }
                            }
                        }
                    }

                    if ($warehouse->management_type == 'LIFO') {
                        // orders stock history by timestamp to get newest history first
                        krsort($stockHistoryQtyAvailable);
                    } else {
                        // orders stock history by timestamp to get oldest history first
                        ksort($stockHistoryQtyAvailable);
                    }

                    // checks each stock to manage the real quantity to decrement for each of them
                    foreach ($stockHistoryQtyAvailable as $entry) {
                        if ($entry['qty'] >= $globalQuantityToDecrement) {
                            $quantityToDecrementByStock[$entry['id_stock']][$entry['id_stock_mvt']] = $globalQuantityToDecrement;
                            $globalQuantityToDecrement = 0;
                        } else {
                            $quantityToDecrementByStock[$entry['id_stock']][$entry['id_stock_mvt']] = $entry['qty'];
                            $globalQuantityToDecrement -= $entry['qty'];
                        }

                        if ($globalQuantityToDecrement <= 0) {
                            break;
                        }
                    }

                    $employeeAttributes = $this->getAttributesOfEmployeeRequestingStockMovement($employee);

                    // for each stock, decrements it and logs the mvts
                    foreach ($stockCollection as $stock) {
                        if (array_key_exists($stock->id, $quantityToDecrementByStock) &&
                            is_array($quantityToDecrementByStock[$stock->id])
                        ) {
                            $totalQuantityForCurrentStock = 0;

                            foreach ($quantityToDecrementByStock[$stock->id] as $idMvtReferrer => $qte) {
                                $mvt_params = [
                                    'id_stock'            => $stock->id,
                                    'physical_quantity'   => $qte,
                                    'id_stock_mvt_reason' => $idStockMvtReason,
                                    'id_order'            => $idOrder,
                                    'price_te'            => $stock->price_te,
                                    'sign'                => -1,
                                    'referer'             => $idMvtReferrer,
                                    'id_employee'         => $employeeAttributes['employee_id'],
                                ];

                                // saves stock mvt
                                $stockMvt = new StockMvt();
                                $stockMvt->hydrate($mvt_params);
                                $stockMvt->save();

                                $totalQuantityForCurrentStock += $qte;
                            }

                            if ($isUsable) {
                                $usableProductQuantity = $stock->usable_quantity - $totalQuantityForCurrentStock;
                            } else {
                                $usableProductQuantity = $stock->usable_quantity;
                            }

                            $stockParams = [
                                'physical_quantity' => ($stock->physical_quantity - $totalQuantityForCurrentStock),
                                'usable_quantity'   => $usableProductQuantity,
                            ];

                            $removedProducts[$stock->id]['quantity'] = $totalQuantityForCurrentStock;
                            $removedProducts[$stock->id]['price_te'] = $stock->price_te;

                            // saves stock in warehouse
                            $stock->hydrate($stockParams);
                            $stock->update();
                        }
                    }
                    break;
            }

            if (Pack::isPacked($idProduct, $idProductAttribute)) {
                $packs = Pack::getPacksContainingItem(
                    $idProduct,
                    $idProductAttribute,
                    (int) Configuration::get('PS_LANG_DEFAULT')
                );

                foreach ($packs as $pack) {
                    // Decrease stocks of the pack only if pack is in linked stock mode (option called 'Decrement both')
                    if (!((int) $pack->pack_stock_type == 2) &&
                        !(
                            (int) $pack->pack_stock_type == 3 &&
                            (int) Configuration::get('PS_PACK_STOCK_TYPE') == 2
                        )
                    ) {
                        continue;
                    }

                    // Decrease stocks of the pack only if there is not enough items to constitute the actual pack stocks.
                    // How many packs can be constituted with the remaining product stocks
                    $quantityByPack = $pack->pack_item_quantity;
                    $stockAvailableQuantity = $quantityInStock - $quantity;
                    $maxPackQuantity = max([0, floor($stockAvailableQuantity / $quantityByPack)]);
                    $quantityDelta = Pack::getQuantity($pack->id) - $maxPackQuantity;

                    if ($pack->advanced_stock_management == 1 && $quantityDelta > 0) {
                        $productWarehouses = Warehouse::getPackWarehouses($pack->id);
                        $warehouseStockFound = false;
                        foreach ($productWarehouses as $productWarehouse) {
                            if (!$warehouseStockFound) {
                                if (Warehouse::exists($productWarehouse)) {
                                    $currentWarehouse = new Warehouse($productWarehouse);
                                    $removedProducts[] = $this->removeProduct(
                                        $pack->id,
                                        null,
                                        $currentWarehouse,
                                        $quantityDelta,
                                        $idStockMvtReason,
                                        $isUsable,
                                        $idOrder,
                                        1
                                    );

                                    // The product was found on this warehouse. Stop the stock searching.
                                    $warehouseStockFound = !empty($removedProducts[count($removedProducts) - 1]);
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->hookCoverageOnProductRemoval(
            $warehouse,
            $idProduct,
            $idProductAttribute,
            $isUsable
        );

        return $removedProducts;
    }

    /**
     * @deprecated
     * @see getPhysicalProductQuantities
     *
     * @param int  $idProduct
     * @param int  $idProductAttribute
     * @param null $idsWarehouse
     * @param bool $usable
     *
     * @return int
     * @throws PrestaShopException
     */
    public function getProductPhysicalQuantities(
        $idProduct,
        $idProductAttribute,
        $idsWarehouse = null,
        $usable = false
    ) {
        $idsWarehouse = $this->normalizeWarehouseIds($idsWarehouse);

        $query = new DbQuery();
        $query->select('SUM('.($usable ? 's.usable_quantity' : 's.physical_quantity').')');
        $query->from('stock', 's');
        $query->where('s.id_product = '.(int) $idProduct);

        if (0 != $idProductAttribute) {
            $query->where('s.id_product_attribute = '.(int) $idProductAttribute);
        }

        if (count($idsWarehouse)) {
            $query->where('s.id_warehouse IN('.implode(', ', $idsWarehouse).')');
        }

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * @param $productStockCriteria
     *
     * @return int
     *
     * @throws Exception
     * @throws PrestaShopException
     * @since 1.0.0
     * @since 1.0.1 Add `usable` to `$productStockCriteria`
     */
    public function getPhysicalProductQuantities($productStockCriteria)
    {
        $productStockCriteria = $this->validateProductStockCriteria($productStockCriteria);

        return (int) $this->getProductPhysicalQuantities(
            $productStockCriteria['product_id'],
            $productStockCriteria['product_attribute_id'],
            $productStockCriteria['warehouse_id'],
            isset($productStockCriteria['usable']) ? $productStockCriteria['usable'] : false
        );
    }

    /**
     * @param $productStockCriteria
     *
     * @return int
     *
     * @throws Exception
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getUsableProductQuantities($productStockCriteria)
    {
        $productStockCriteria = $this->validateProductStockCriteria($productStockCriteria);

        return (int) $this->getProductPhysicalQuantities(
            $productStockCriteria['product_id'],
            $productStockCriteria['product_attribute_id'],
            $productStockCriteria['warehouse_id'],
            $usable = true
        );
    }

    /**
     * @param array $criteria
     *
     * @return array
     * @throws Exception
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function validateProductStockCriteria(array $criteria)
    {
        if (!array_key_exists('product_id', $criteria)) {
            throw new \Exception('Missing product id');
        }

        if (!array_key_exists('product_attribute_id', $criteria)) {
            throw new \Exception('Missing product combination id');
        }

        if (!array_key_exists('warehouse_id', $criteria)) {
            throw new \Exception('Missing warehouse id');
        }

        return $criteria;
    }

    /**
     * @param $idsWarehouse
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function normalizeWarehouseIds($idsWarehouse)
    {
        $normalizedWarehouseIds = [];

        if (!is_null($idsWarehouse)) {
            if (!is_array($idsWarehouse)) {
                $idsWarehouse = [$idsWarehouse];
            }

            $normalizedWarehouseIds = array_map('intval', $idsWarehouse);
        }

        return $normalizedWarehouseIds;
    }

    /**
     * @see StockManagerInterface::getProductRealQuantities()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductRealQuantities($idProduct, $idProductAttribute, $idsWarehouse = null, $usable = false)
    {
        if (!is_null($idsWarehouse)) {
            // in case $ids_warehouse is not an array
            if (!is_array($idsWarehouse)) {
                $idsWarehouse = [$idsWarehouse];
            }

            // casts for security reason
            $idsWarehouse = array_map('intval', $idsWarehouse);
        }

        $clientOrdersQty = 0;

        // check if product is present in a pack
        if (!Pack::isPack($idProduct) && $inPack = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT id_product_pack, quantity FROM '._DB_PREFIX_.'pack
			WHERE id_product_item = '.(int) $idProduct.'
			AND id_product_attribute_item = '.($idProductAttribute ? (int) $idProductAttribute : '0')
            )
        ) {
            foreach ($inPack as $value) {
                if (Validate::isLoadedObject($product = new Product((int) $value['id_product_pack'])) &&
                    ($product->pack_stock_type == 1 || $product->pack_stock_type == 2 || ($product->pack_stock_type == 3 && Configuration::get('PS_PACK_STOCK_TYPE') > 0))
                ) {
                    $query = new DbQuery();
                    $query->select('od.product_quantity, od.product_quantity_refunded, pk.quantity');
                    $query->from('order_detail', 'od');
                    $query->leftjoin('orders', 'o', 'o.id_order = od.id_order');
                    $query->where('od.product_id = '.(int) $value['id_product_pack']);
                    $query->leftJoin('order_history', 'oh', 'oh.id_order = o.id_order AND oh.id_order_state = o.current_state');
                    $query->leftJoin('order_state', 'os', 'os.id_order_state = oh.id_order_state');
                    $query->leftJoin('pack', 'pk', 'pk.id_product_item = '.(int) $idProduct.' AND pk.id_product_attribute_item = '.($idProductAttribute ? (int) $idProductAttribute : '0').' AND id_product_pack = od.product_id');
                    $query->where('os.shipped != 1');
                    $query->where(
                        'o.valid = 1 OR (os.id_order_state != '.(int) Configuration::get('PS_OS_ERROR').'
								   AND os.id_order_state != '.(int) Configuration::get('PS_OS_CANCELED').')'
                    );
                    $query->groupBy('od.id_order_detail');
                    if (count($idsWarehouse)) {
                        $query->where('od.id_warehouse IN('.implode(', ', $idsWarehouse).')');
                    }
                    $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
                    if (count($res)) {
                        foreach ($res as $row) {
                            $clientOrdersQty += ($row['product_quantity'] - $row['product_quantity_refunded']) * $row['quantity'];
                        }
                    }
                }
            }
        }

        // skip if product is a pack without
        if (!Pack::isPack($idProduct) || (Pack::isPack($idProduct) && Validate::isLoadedObject($product = new Product((int) $idProduct))
                && $product->pack_stock_type == 0 || $product->pack_stock_type == 2 ||
                ($product->pack_stock_type == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 0 || Configuration::get('PS_PACK_STOCK_TYPE') == 2)))
        ) {
            // Gets client_orders_qty
            $query = new DbQuery();
            $query->select('od.product_quantity, od.product_quantity_refunded');
            $query->from('order_detail', 'od');
            $query->leftjoin('orders', 'o', 'o.id_order = od.id_order');
            $query->where('od.product_id = '.(int) $idProduct);
            if (0 != $idProductAttribute) {
                $query->where('od.product_attribute_id = '.(int) $idProductAttribute);
            }
            $query->leftJoin('order_history', 'oh', 'oh.id_order = o.id_order AND oh.id_order_state = o.current_state');
            $query->leftJoin('order_state', 'os', 'os.id_order_state = oh.id_order_state');
            $query->where('os.shipped != 1');
            $query->where(
                'o.valid = 1 OR (os.id_order_state != '.(int) Configuration::get('PS_OS_ERROR').'
						   AND os.id_order_state != '.(int) Configuration::get('PS_OS_CANCELED').')'
            );
            $query->groupBy('od.id_order_detail');
            if (count($idsWarehouse)) {
                $query->where('od.id_warehouse IN('.implode(', ', $idsWarehouse).')');
            }
            $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
            if (count($res)) {
                foreach ($res as $row) {
                    $clientOrdersQty += ($row['product_quantity'] - $row['product_quantity_refunded']);
                }
            }
        }
        // Gets supply_orders_qty
        $query = new DbQuery();

        $query->select('sod.quantity_expected, sod.quantity_received');
        $query->from('supply_order', 'so');
        $query->leftjoin('supply_order_detail', 'sod', 'sod.id_supply_order = so.id_supply_order');
        $query->leftjoin('supply_order_state', 'sos', 'sos.id_supply_order_state = so.id_supply_order_state');
        $query->where('sos.pending_receipt = 1');
        $query->where('sod.id_product = '.(int) $idProduct.' AND sod.id_product_attribute = '.(int) $idProductAttribute);
        if (!is_null($idsWarehouse) && count($idsWarehouse)) {
            $query->where('so.id_warehouse IN('.implode(', ', $idsWarehouse).')');
        }

        $supplyOrdersQties = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        $supplyOrdersQty = 0;
        foreach ($supplyOrdersQties as $qty) {
            if ($qty['quantity_expected'] > $qty['quantity_received']) {
                $supplyOrdersQty += ($qty['quantity_expected'] - $qty['quantity_received']);
            }
        }

        // Gets {physical OR usable}_qty
        $qty = $this->getPhysicalProductQuantities(['product_id' => $idProduct, 'product_attribute_id' => $idProductAttribute, 'warehouse_id' => $idsWarehouse, 'usable' => $usable]);

        //real qty = actual qty in stock - current client orders + current supply orders
        return ($qty - $clientOrdersQty + $supplyOrdersQty);
    }

    /**
     * @see StockManagerInterface::transferBetweenWarehouses()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function transferBetweenWarehouses(
        $idProduct,
        $idProductAttribute,
        $quantity,
        $idWarehouseFrom,
        $idWarehouseTo,
        $usableFrom = true,
        $usableTo = true
    ) {
        // Checks if this transfer is possible
        if ($this->getPhysicalProductQuantities(['product_id' => $idProduct, 'product_attribute_id' => $idProductAttribute, 'warehouse_id' => [$idWarehouseFrom], 'usable' => $usableFrom]) < $quantity) {
            return false;
        }

        if ($idWarehouseFrom == $idWarehouseTo && $usableFrom == $usableTo) {
            return false;
        }

        // Checks if the given warehouses are available
        $warehouseFrom = new Warehouse($idWarehouseFrom);
        $warehouseTo = new Warehouse($idWarehouseTo);
        if (!Validate::isLoadedObject($warehouseFrom) ||
            !Validate::isLoadedObject($warehouseTo)
        ) {
            return false;
        }

        // Removes from warehouse_from
        $stocks = $this->removeProduct(
            $idProduct,
            $idProductAttribute,
            $warehouseFrom,
            $quantity,
            Configuration::get('PS_STOCK_MVT_TRANSFER_FROM'),
            $usableFrom
        );
        if (!count($stocks)) {
            return false;
        }

        // Adds in warehouse_to
        foreach ($stocks as $stock) {
            $price = $stock['price_te'];

            // convert product price to destination warehouse currency if needed
            if ($warehouseFrom->id_currency != $warehouseTo->id_currency) {
                // First convert price to the default currency
                $priceConvertedToDefaultCurrency = Tools::convertPrice($price, $warehouseFrom->id_currency, false);

                // Convert the new price from default currency to needed currency
                $price = Tools::convertPrice($priceConvertedToDefaultCurrency, $warehouseTo->id_currency, true);
            }

            if (!$this->addProduct(
                $idProduct,
                $idProductAttribute,
                $warehouseTo,
                $stock['quantity'],
                Configuration::get('PS_STOCK_MVT_TRANSFER_TO'),
                $price,
                $usableTo
            )
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @see     StockManagerInterface::getProductCoverage()
     * Here, $coverage is a number of days
     *
     * @return int number of days left (-1 if infinite)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getProductCoverage($idProduct, $idProductAttribute, $coverage, $idWarehouse = null)
    {
        if (!$idProductAttribute) {
            $idProductAttribute = 0;
        }

        if ($coverage == 0 || !$coverage) {
            $coverage = 7;
        } // Week by default

        // gets all stock_mvt for the given coverage period
        $query = '
			SELECT SUM(view.quantity) as quantity_out
			FROM
			(	SELECT sm.`physical_quantity` as quantity
				FROM `'._DB_PREFIX_.'stock_mvt` sm
				LEFT JOIN `'._DB_PREFIX_.'stock` s ON (sm.`id_stock` = s.`id_stock`)
				LEFT JOIN `'._DB_PREFIX_.'product` p ON (p.`id_product` = s.`id_product`)
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (p.`id_product` = pa.`id_product`)
				'.Shop::addSqlAssociation('product_attribute', 'pa', false).'
				WHERE sm.`sign` = -1
				AND sm.`id_stock_mvt_reason` != '.Configuration::get('PS_STOCK_MVT_TRANSFER_FROM').'
				AND TO_DAYS("'.date('Y-m-d').' 00:00:00") - TO_DAYS(sm.`date_add`) <= '.(int) $coverage.'
				AND s.`id_product` = '.(int) $idProduct.'
				AND s.`id_product_attribute` = '.(int) $idProductAttribute.
            ($idWarehouse ? ' AND s.`id_warehouse` = '.(int) $idWarehouse : '').'
				GROUP BY sm.`id_stock_mvt`
			) as view';

        $quantityOut = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
        if (!$quantityOut) {
            return -1;
        }

        $quantityPerDay = Tools::ps_round($quantityOut / $coverage);
        $physicalQuantity = $this->getProductPhysicalQuantities(
            $idProduct,
            $idProductAttribute,
            ($idWarehouse ? [$idWarehouse] : null),
            true
        );

        $timeLeft = ($quantityPerDay == 0) ? (-1) : Tools::ps_round($physicalQuantity / $quantityPerDay);

        return $timeLeft;
    }

    /**
     * For a given stock, calculates its new WA(Weighted Average) price based on the new quantities and price
     * Formula : (physicalStock * lastCump + quantityToAdd * unitPrice) / (physicalStock + quantityToAdd)
     *
     * @param Stock|PrestaShopCollection $stock
     * @param int                        $quantity
     * @param float                      $priceTe
     *
     * @return float Weight Average, rounded to _TB_PRICE_DATABASE_PRECISION_.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function calculateWA(Stock $stock, $quantity, $priceTe)
    {
        return round(
            ($stock->physical_quantity * $stock->price_te + $quantity * $priceTe)
            / ($stock->physical_quantity + $quantity),
            _TB_PRICE_DATABASE_PRECISION_
        );
    }

    /**
     * For a given product, retrieves the stock collection
     *
     * @param int   $idProduct
     * @param int   $idProductAttribute
     * @param int   $idWarehouse      Optional
     * @param int   $priceTaxExcluded Optional
     * @param Stock $stock            Optional
     *
     * @return PrestaShopCollection Collection of Stock
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function getStockCollection(
        $idProduct,
        $idProductAttribute,
        $idWarehouse = null,
        $priceTaxExcluded = null,
        Stock $stock = null
    ) {
        $stocks = new PrestaShopCollection('Stock');
        $stocks->where('id_product', '=', $idProduct);
        $stocks->where('id_product_attribute', '=', $idProductAttribute);
        if ($stock) {
            $stocks->where('id_stock', '=', $stock->id);
        }
        if ($idWarehouse) {
            $stocks->where('id_warehouse', '=', $idWarehouse);
        }
        if ($priceTaxExcluded) {
            $stocks->where('price_te', '=', $priceTaxExcluded);
        }

        return $stocks;
    }

    /**
     * For a given product, retrieves the stock in function of the delivery option
     *
     * @param int   $idProduct
     * @param int   $idProductAttribute optional
     * @param array $deliveryOption
     *
     * @return int quantity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getStockByCarrier($idProduct = 0, $idProductAttribute = 0, $deliveryOption = null)
    {
        if (!(int) $idProduct || !is_array($deliveryOption) || !is_int($idProductAttribute)) {
            return false;
        }

        $results = Warehouse::getWarehousesByProductId($idProduct, $idProductAttribute);
        $stockQuantity = 0;

        foreach ($results as $result) {
            if (isset($result['id_warehouse']) && (int) $result['id_warehouse']) {
                $ws = new Warehouse((int) $result['id_warehouse']);
                $carriers = $ws->getWsCarriers();

                if (is_array($carriers) && !empty($carriers)) {
                    $stockQuantity += Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        'SELECT SUM(s.`usable_quantity`) as quantity
						FROM '._DB_PREFIX_.'stock s
						LEFT JOIN '._DB_PREFIX_.'warehouse_carrier wc ON wc.`id_warehouse` = s.`id_warehouse`
						LEFT JOIN '._DB_PREFIX_.'carrier c ON wc.`id_carrier` = c.`id_reference`
						WHERE s.`id_product` = '.(int) $idProduct.' AND s.`id_product_attribute` = '.(int) $idProductAttribute.' AND s.`id_warehouse` = '.$result['id_warehouse'].' AND c.`id_carrier` IN ('.rtrim($deliveryOption[(int) Context::getContext()->cart->id_address_delivery], ',').') GROUP BY s.`id_product`'
                    );
                } else {
                    $stockQuantity += Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        'SELECT SUM(s.`usable_quantity`) as quantity
						FROM '._DB_PREFIX_.'stock s
						WHERE s.`id_product` = '.(int) $idProduct.' AND s.`id_product_attribute` = '.(int) $idProductAttribute.' AND s.`id_warehouse` = '.$result['id_warehouse'].' GROUP BY s.`id_product`'
                    );
                }
            }
        }

        return $stockQuantity;
    }

    /**
     * Prevent stock operation whenever product, quantity or warehouse are invalid
     *
     * @param Warehouse $warehouse
     * @param           $productId
     * @param           $quantity
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function shouldPreventStockOperation(Warehouse $warehouse, $productId, $quantity)
    {
        return !Validate::isLoadedObject($warehouse) || !$quantity || !$productId;
    }

    /**
     * @param $stockMovementReasonId
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function ensureStockMovementReasonIsValid($stockMovementReasonId)
    {
        if (!StockMvtReason::exists($stockMovementReasonId)) {
            $stockMovementReasonId = Configuration::get('PS_STOCK_MVT_DEC_REASON_DEFAULT');
        }

        return $stockMovementReasonId;
    }

    /**
     * @param $productId
     * @param $shouldIgnorePack
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function shouldHandleStockOperationForProductsPack($productId, $shouldIgnorePack)
    {
        return Pack::isPack((int) $productId) && !$shouldIgnorePack;
    }

    /**
     * @param Warehouse $warehouse
     * @param           $productId
     * @param           $productAttributeId
     * @param           $isUsable
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function hookCoverageOnProductRemoval(
        Warehouse $warehouse,
        $productId,
        $productAttributeId,
        $isUsable
    ) {
        if ($isUsable) {
            Hook::exec(
                'actionProductCoverage',
                [
                    'id_product'           => $productId,
                    'id_product_attribute' => $productAttributeId,
                    'warehouse'            => $warehouse,
                ]
            );
        }
    }

    /**
     * @param Warehouse $warehouse
     * @param int       $productId
     * @param int       $productAttributeId
     * @param bool      $shouldHandleUsableQuantity
     * @param Stock     $stock
     *
     * @return int
     *
     * @throws Exception
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function computeProductQuantityInStock(
        Warehouse $warehouse,
        $productId,
        $productAttributeId,
        $shouldHandleUsableQuantity,
        Stock $stock = null
    ) {
        $productStockCriteria = [
            'product_id'           => $productId,
            'product_attribute_id' => $productAttributeId,
            'warehouse_id'         => $warehouse->id,
        ];
        $physicalProductQuantityInStock = $this->getPhysicalProductQuantities($productStockCriteria);
        $usableProductQuantityInStock = $this->getUsableProductQuantities($productStockCriteria);

        if ($stock) {
            $physicalProductQuantityInStock = $stock->physical_quantity;
            $usableProductQuantityInStock = $stock->usable_quantity;
        }

        $productQuantityInStock = $physicalProductQuantityInStock;

        if ($shouldHandleUsableQuantity) {
            $productQuantityInStock = $usableProductQuantityInStock;
        }

        return (int) $productQuantityInStock;
    }

    /**
     * @param $quantity
     * @param $quantityInStock
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function ensureProductQuantityRequestedForRemovalIsValid($quantity, $quantityInStock)
    {
        return $quantityInStock < $quantity;
    }

    /**
     * @param int       $idProduct
     * @param int       $idProductAttribute
     * @param Warehouse $warehouse
     * @param Stock     $stock
     *
     * @return PrestaShopCollection
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function getProductStockLinesInWarehouse(
        $idProduct,
        $idProductAttribute,
        Warehouse $warehouse,
        Stock $stock = null
    ) {
        $stockLines = $this->getStockCollection($idProduct, $idProductAttribute, $warehouse->id, null, $stock);
        $stockLines->getAll();

        return $stockLines;
    }

    /**
     * @param $employee
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getAttributesOfEmployeeRequestingStockMovement($employee)
    {
        $context = Context::getContext();

        if ((int) $context->employee->id) {
            $employeeId = (int) $context->employee->id;
        } else {
            $employeeId = $employee->id;
        }

        if ($context->employee->firstname) {
            $employeeFirstName = $context->employee->firstname;
        } else {
            $employeeFirstName = $employee->firstname;
        }

        if ($context->employee->lastname) {
            $employeeLastName = $context->employee->lastname;
        } else {
            $employeeLastName = $employee->lastname;
        }

        return [
            'employee_id' => $employeeId,
            'first_name'  => $employeeFirstName,
            'last_name'   => $employeeLastName,
        ];
    }

    /**
     * @param $quantity
     * @param $idStockMvtReason
     * @param $isUsable
     * @param $idOrder
     * @param $employee
     * @param $stock
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function removeProductQuantityApplyingCump(
        $quantity,
        $idStockMvtReason,
        $isUsable,
        $idOrder,
        $employee,
        $stock
    ) {
        $employeeAttributes = $this->getAttributesOfEmployeeRequestingStockMovement($employee);

        $movementParams = [
            'id_stock'            => $stock->id,
            'physical_quantity'   => $quantity,
            'id_stock_mvt_reason' => $idStockMvtReason,
            'id_order'            => $idOrder,
            'price_te'            => $stock->price_te,
            'last_wa'             => $stock->price_te,
            'current_wa'          => $stock->price_te,
            'id_employee'         => $employeeAttributes['employee_id'],
            'employee_firstname'  => $employeeAttributes['first_name'],
            'employee_lastname'   => $employeeAttributes['last_name'],
            'sign'                => -1,
        ];

        if ($isUsable) {
            $usableProductQuantity = $stock->usable_quantity - $quantity;
        } else {
            $usableProductQuantity = $stock->usable_quantity;
        }

        $physicalProductQuantity = $stock->physical_quantity - $quantity;

        $stockParams = [
            'physical_quantity' => $physicalProductQuantity,
            'usable_quantity'   => $usableProductQuantity,
        ];

        /** @var \StockCore $stock */
        $stock->hydrate($stockParams);
        $stock->update();

        /** @var \StockMvtCore $stockMovement */
        $stockMovement = new StockMvt();
        $stockMovement->hydrate($movementParams);
        $stockMovement->save();
    }
}
