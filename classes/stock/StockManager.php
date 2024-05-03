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
 * Class StockManagerCore
 */
class StockManagerCore implements StockManagerInterface
{
    /**
     * @return bool
     */
    public static function isAvailable()
    {
        // Default Manager : always available
        return true;
    }

    /**
     * @param string|null $date
     * @return int
     */
    protected static function convertsDateToTimestamp($date): int
    {
        if ($date) {
            try {
                $date = new DateTime($date);
                return $date->getTimestamp();
            } catch (Exception $ignored) {
            }
        }
        return 0;
    }

    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param Warehouse $warehouse
     * @param int $quantity
     * @param int|null $idStockMvtReason
     * @param float $priceTe
     * @param bool $isUsable
     * @param int|null $idSupplyOrder
     * @param Employee|null $employee
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function addProduct(
        $idProduct,
        $idProductAttribute,
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
     * @param int $idProduct
     * @param int|null $idProductAttribute
     * @param Warehouse $warehouse
     * @param int $quantity
     * @param int $idStockMvtReason
     * @param bool $isUsable
     * @param int|null $idOrder
     * @param int $ignorePack
     * @param Employee|null $employee
     * @param Stock|null $stock
     *
     * @return array|false
     * @throws PrestaShopException
     */
    public function removeProduct(
        $idProduct,
        $idProductAttribute,
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
                if ($product->shouldAdjustPackItemsQuantities()) {
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

                if ($product->shouldAdjustPackQuantity()) {
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

            /** @var Countable $stockCollection */
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

                        $conn = Db::getInstance();
                        $resource = $conn->query(
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

                        while ($row = $conn->nextRow($resource)) {
                            // continue - in FIFO mode, we have to retreive the oldest positive mvts for which there are left quantities
                            if ($warehouse->management_type == 'FIFO') {
                                if ($row['qty'] == 0) {
                                    continue;
                                }
                            }
                            $timestamp = static::convertsDateToTimestamp($row['date_add']);

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
                    if ($pack->getPackStockType() !== Pack::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS) {
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
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int|null $idsWarehouse
     * @param bool $usable
     *
     * @return int
     * @throws PrestaShopException
     *
     * @deprecated
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

        return (int) Db::readOnly()->getValue($query);
    }

    /**
     * @param array $productStockCriteria
     *
     * @return int
     *
     * @throws PrestaShopException
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
     * @param array $productStockCriteria
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function getUsableProductQuantities($productStockCriteria)
    {
        $productStockCriteria = $this->validateProductStockCriteria($productStockCriteria);

        return (int) $this->getProductPhysicalQuantities(
            $productStockCriteria['product_id'],
            $productStockCriteria['product_attribute_id'],
            $productStockCriteria['warehouse_id'],
            true
        );
    }

    /**
     * @param array $criteria
     *
     * @return array
     */
    protected function validateProductStockCriteria(array $criteria)
    {
        if (!array_key_exists('product_id', $criteria)) {
            throw new InvalidArgumentException('Missing product id');
        }

        if (!array_key_exists('product_attribute_id', $criteria)) {
            throw new InvalidArgumentException('Missing product combination id');
        }

        if (!array_key_exists('warehouse_id', $criteria)) {
            throw new InvalidArgumentException('Missing warehouse id');
        }

        return $criteria;
    }

    /**
     * @param int|int[]|null $idsWarehouse
     *
     * @return int[]
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
     * @param int $idProduct
     * @param int|null $idProductAttribute
     * @param array|int $idsWarehouse
     * @param bool $usable
     *
     * @return float|int|mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getProductRealQuantities($idProduct, $idProductAttribute, $idsWarehouse = null, $usable = false)
    {
        $idsWarehouse = $this->normalizeWarehouseIds($idsWarehouse);

        $clientOrdersQty = 0;

        // check if product is present in a pack
        $conn = Db::readOnly();
        if (!Pack::isPack($idProduct) && $inPack = $conn->getArray(
                'SELECT id_product_pack, quantity FROM '._DB_PREFIX_.'pack
			WHERE id_product_item = '.(int) $idProduct.'
			AND id_product_attribute_item = '.($idProductAttribute ? (int) $idProductAttribute : '0')
            )
        ) {
            foreach ($inPack as $value) {
                $product = new Product((int) $value['id_product_pack']);
                if (Validate::isLoadedObject($product) && $product->shouldAdjustPackItemsQuantities()) {
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
                    if ($idsWarehouse) {
                        $query->where('od.id_warehouse IN('.implode(', ', $idsWarehouse).')');
                    }
                    $res = $conn->getArray($query);
                    if (count($res)) {
                        foreach ($res as $row) {
                            $clientOrdersQty += ($row['product_quantity'] - $row['product_quantity_refunded']) * $row['quantity'];
                        }
                    }
                }
            }
        }

        $trackingProductQuantity = true;
        if (Pack::isPack($idProduct)) {
            $product = new Product((int) $idProduct);
            $trackingProductQuantity = $product->shouldAdjustPackQuantity();
        }

        // skip if product is a pack without
        if ($trackingProductQuantity) {
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
            if ($idsWarehouse) {
                $query->where('od.id_warehouse IN('.implode(', ', $idsWarehouse).')');
            }
            $res = $conn->getArray($query);
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
        if ($idsWarehouse) {
            $query->where('so.id_warehouse IN('.implode(', ', $idsWarehouse).')');
        }

        $supplyOrdersQties = $conn->getArray($query);

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
     * @throws PrestaShopException
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
     * Here, $coverage is a number of days
     *
     * @return int number of days left (-1 if infinite)
     *
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
			SELECT SUM(sm.`physical_quantity`) as quantity
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
            ($idWarehouse ? ' AND s.`id_warehouse` = '.(int) $idWarehouse : '');

        $quantityOut = (int)Db::readOnly()->getValue($query);

        if (!$quantityOut) {
            return -1;
        }

        $quantityPerDay = $quantityOut / $coverage;
        $physicalQuantity = $this->getProductPhysicalQuantities(
            $idProduct,
            $idProductAttribute,
            ($idWarehouse ? [$idWarehouse] : null),
            true
        );

        $timeLeft = Tools::ps_round($physicalQuantity / $quantityPerDay);

        return $timeLeft;
    }

    /**
     * For a given stock, calculates its new WA(Weighted Average) price based on the new quantities and price
     * Formula : (physicalStock * lastCump + quantityToAdd * unitPrice) / (physicalStock + quantityToAdd)
     *
     * @param Stock|PrestaShopCollection $stock
     * @param int $quantity
     * @param float $priceTe
     *
     * @return float Weight Average, rounded to _TB_PRICE_DATABASE_PRECISION_.
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
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int|null $idWarehouse Optional
     * @param float|null $priceTaxExcluded Optional
     * @param Stock|null $stock Optional
     *
     * @return PrestaShopCollection Collection of Stock
     *
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
     * @param int $idProduct
     * @param int $idProductAttribute optional
     * @param array $deliveryOption
     *
     * @return int quantity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getStockByCarrier($idProduct = 0, $idProductAttribute = 0, $deliveryOption = null)
    {
        if (!(int) $idProduct || !is_array($deliveryOption) || !is_int($idProductAttribute)) {
            return false;
        }

        $deliveryAddressId = (int)Context::getContext()->cart->id_address_delivery;
        $carrierList = array_filter(array_map('intval', explode(',', $deliveryOption[$deliveryAddressId])));
        $results = Warehouse::getWarehousesByProductId($idProduct, $idProductAttribute);
        $stockQuantity = 0;

        $connection = Db::readOnly();
        foreach ($results as $result) {
            if (isset($result['id_warehouse']) && (int) $result['id_warehouse']) {
                $warehouseId = (int)$result['id_warehouse'];
                $ws = new Warehouse($warehouseId);
                $carriers = $ws->getWsCarriers();

                if (is_array($carriers) && !empty($carriers)) {
                    if ($carrierList) {
                        $stockQuantity += $connection->getValue((new DbQuery())
                            ->select('SUM(s.`usable_quantity`) as quantity')
                            ->from('stock', 's')
                            ->leftJoin('warehouse_carrier', 'wc', '(wc.`id_warehouse` = s.`id_warehouse`)')
                            ->leftJoin('carrier', 'c', '(wc.`id_carrier` = c.`id_reference`)')
                            ->where('s.`id_product` = ' . (int)$idProduct)
                            ->where('s.`id_product_attribute` = ' . (int)$idProductAttribute)
                            ->where('s.`id_warehouse` = ' . $warehouseId)
                            ->where('c.`id_carrier` IN (' . implode(',', $carrierList) . ')')
                            ->groupBy('s.`id_product`')
                        );
                    }
                } else {
                    $stockQuantity += $connection->getValue((new DbQuery())
                        ->select('SUM(s.`usable_quantity`) as quantity')
                        ->from('stock', 's')
                        ->where('s.`id_product` = '.(int) $idProduct)
                        ->where('s.`id_product_attribute` = '.(int) $idProductAttribute)
                        ->where('s.`id_warehouse` = ' . $warehouseId)
                        ->groupBy('s.`id_product`')
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
     * @param int $productId
     * @param int $quantity
     *
     * @return bool
     */
    protected function shouldPreventStockOperation(Warehouse $warehouse, $productId, $quantity)
    {
        return !Validate::isLoadedObject($warehouse) || !$quantity || !$productId;
    }

    /**
     * @param int $stockMovementReasonId
     *
     * @return int
     *
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
     * @param int $productId
     * @param bool $shouldIgnorePack
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function shouldHandleStockOperationForProductsPack($productId, $shouldIgnorePack)
    {
        return Pack::isPack((int) $productId) && !$shouldIgnorePack;
    }

    /**
     * @param Warehouse $warehouse
     * @param int $productId
     * @param int $productAttributeId
     * @param bool $isUsable
     *
     * @throws PrestaShopException
     */
    protected function hookCoverageOnProductRemoval(
        Warehouse $warehouse,
        $productId,
        $productAttributeId,
        $isUsable
    ) {
        if ($isUsable) {
            Hook::triggerEvent(
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
     * @param int $productId
     * @param int $productAttributeId
     * @param bool $shouldHandleUsableQuantity
     * @param Stock|null $stock
     *
     * @return int
     *
     * @throws PrestaShopException
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
     * @param int $quantity
     * @param int $quantityInStock
     *
     * @return bool
     */
    protected function ensureProductQuantityRequestedForRemovalIsValid($quantity, $quantityInStock)
    {
        return $quantityInStock < $quantity;
    }

    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param Warehouse $warehouse
     * @param Stock|null $stock
     *
     * @return PrestaShopCollection
     *
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
     * @param Employee|null $employee
     *
     * @return array
     */
    protected function getAttributesOfEmployeeRequestingStockMovement($employee)
    {
        $context = Context::getContext();
        if (Validate::isLoadedObject($context->employee)) {
            return [
                'employee_id' => (int)$context->employee->id,
                'first_name'  => $context->employee->firstname,
                'last_name'   => $context->employee->lastname,
            ];
        }

        if (Validate::isLoadedObject($employee)) {
            return [
                'employee_id' => (int)$employee->id,
                'first_name'  => $employee->firstname,
                'last_name'   => $employee->lastname,
            ];
        }

        // fallback - we are in front-office context, no employee available
        return [
            'employee_id' => 0,
            'first_name'  => '',
            'last_name'   => ''
        ];
    }

    /**
     * @param int $quantity
     * @param int $idStockMvtReason
     * @param bool $isUsable
     * @param int $idOrder
     * @param Employee|null $employee
     * @param Stock $stock
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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

        $stock->hydrate($stockParams);
        $stock->update();

        $stockMovement = new StockMvt();
        $stockMovement->hydrate($movementParams);
        $stockMovement->save();
    }
}
