<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Stock\Synchronization;

use Pack;
use Db;
use DbQuery;
use PrestaShopDatabaseException;
use PrestaShopException;
use StockAvailable;
use Context;
use Thirtybees\Core\InitializationCallback;
use Thirtybees\Core\WorkQueue\ScheduledTask;
use Thirtybees\Core\WorkQueue\WorkQueueContext;
use Thirtybees\Core\WorkQueue\WorkQueueTask;
use Thirtybees\Core\WorkQueue\WorkQueueTaskCallable;

/**
 * Class DynamicPacksSynchronizationTaskCore
 *
 * Work queue task to synchronize dynamic packs quantities
 */
class DynamicPacksSynchronizationTaskCore implements WorkQueueTaskCallable, InitializationCallback
{
    /**
     * Creates work queue task to synchronize packs
     *
     * @param int[] $productIds
     * @return WorkQueueTask
     */
    public static function createTask($productIds = null)
    {
        $parameters = [];
        if (! is_null($productIds)) {
            $parameters['productIds'] = array_filter(array_map('intval', $productIds));
        }
        return WorkQueueTask::createTask(
            static::getTaskName(),
            $parameters,
            WorkQueueContext::fromContext(Context::getContext())
        );
    }

    /**
     * Task execution method
     *
     * Synchronizes all dynamic packs
     *
     * @param WorkQueueContext $context
     * @param array $parameters
     *
     * @return int
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function execute(WorkQueueContext $context, array $parameters)
    {
        $conn = Db::getInstance();

        $products = [];
        $hasCombinationPacks = (new DbQuery())
            ->select('1')
            ->from('pack', 'pack')
            ->where('pack.id_product_pack = ps.id_product')
            ->where('pack.id_product_attribute_pack != 0');
        if (isset($parameters['productIds'])) {
            $productIds = array_filter(array_map('intval', $parameters['productIds']));
            $sql = (new DbQuery())
                ->select("ps.id_product, EXISTS($hasCombinationPacks) as combination_packs")
                ->from('product_shop', 'ps')
                ->where('ps.pack_dynamic')
                ->where('ps.id_product IN (' .implode(',', $productIds). ')');
            foreach ($conn->getArray($sql) as $row) {
                $productId = (int)$row['id_product'];
                $combinationPacks = (bool)$row['combination_packs'];
                $products[$productId] = $combinationPacks;
            }
        } else {
            $sql = (new DbQuery())
                ->select("ps.id_product, EXISTS($hasCombinationPacks) as combination_packs")
                ->from('product_shop', 'ps')
                ->where('ps.pack_dynamic');
            foreach ($conn->getArray($sql) as $row) {
                $productId = (int)$row['id_product'];
                $combinationPacks = (bool)$row['combination_packs'];
                $products[$productId] = $combinationPacks;
            }
        }

        if (! $products) {
            return 0;
        }

        $productIds = implode(',', array_keys($products));
        $productPacks = [];
        $combinationPacks = [];
        foreach ($products as $productId => $isCombinationPack) {
            if ($isCombinationPack) {
                $combinationPacks[] = $productId;
            } else {
                $productPacks[] = $productId;
            }
        }

        // figure out current stocks
        $currentStockSql = (new DbQuery())
            ->select('s.*')
            ->from('stock_available', 's')
            ->where("s.id_product IN ($productIds)");

        $currentQuantities = [];
        foreach ($conn->getArray($currentStockSql) as $row) {
            $productId = (int)$row['id_product'];
            $productAttributeId = (int)$row['id_product_attribute'];
            $shopId = (int)$row['id_shop'];
            $shopGroupId = (int)$row['id_shop_group'];
            $key = "$shopId|$shopGroupId|$productId|$productAttributeId";
            $currentQuantities[$key] = [
                'id' => (int)$row['id_stock_available'],
                'quantity' => (int)$row['quantity'],
            ];
        }

        // calculate dynamic stocks
        $expectedQuantities = [];
        if ($combinationPacks) {
            $dynamicStockSql = (new DbQuery())
                ->select('sa.id_shop')
                ->select('sa.id_shop_group')
                ->select('p.id_product_pack AS id_product')
                ->select('pa.id_product_attribute AS id_product_attribute')
                ->select('MIN(FLOOR(sa.quantity / p.quantity)) AS quantity')
                ->from('pack', 'p')
                ->leftJoin('product_attribute', 'pa', '(pa.id_product = p.id_product_pack AND pa.id_product_attribute = p.id_product_attribute_pack)')
                ->innerJoin('stock_available', 'sa', '(sa.id_product = p.id_product_item AND sa.id_product_attribute = p.id_product_attribute_item)')
                ->where("p.id_product_pack IN ($productIds)")
                ->groupBy('sa.id_shop')
                ->groupBy('sa.id_shop_group')
                ->groupBy('p.id_product_pack')
                ->groupBy('pa.id_product_attribute');
            $expectedQuantities = $conn->getArray($dynamicStockSql);
        }
        if ($productPacks) {
            $dynamicStockSql = (new DbQuery())
                ->select('sa.id_shop')
                ->select('sa.id_shop_group')
                ->select('p.id_product_pack AS id_product')
                ->select('COALESCE(pa.id_product_attribute, 0) AS id_product_attribute')
                ->select('MIN(FLOOR(sa.quantity / p.quantity)) AS quantity')
                ->from('pack', 'p')
                ->leftJoin('product_attribute', 'pa', '(pa.id_product = p.id_product_pack)')
                ->innerJoin('stock_available', 'sa', '(sa.id_product = p.id_product_item AND sa.id_product_attribute = p.id_product_attribute_item)')
                ->where("p.id_product_pack IN ($productIds)")
                ->groupBy('sa.id_shop')
                ->groupBy('sa.id_shop_group')
                ->groupBy('p.id_product_pack')
                ->groupBy('COALESCE(pa.id_product_attribute, 0)');
            $expectedQuantities = array_merge($expectedQuantities, $conn->getArray($dynamicStockSql));
        }

        $cnt = 0;
        // update stock
        foreach ($expectedQuantities as $row) {
            $productId = (int)$row['id_product'];
            $productAttributeId = (int)$row['id_product_attribute'];
            $shopId = (int)$row['id_shop'];
            $shopGroupId = (int)$row['id_shop_group'];
            $key = "$shopId|$shopGroupId|$productId|$productAttributeId";
            $quantity = (int)$row['quantity'];

            if (isset($currentQuantities[$key])) {
                if ($currentQuantities[$key]['quantity'] !== $quantity) {
                    $stockAvailable = new StockAvailable($currentQuantities[$key]['id']);
                    $stockAvailable->quantity = $quantity;
                    $stockAvailable->depends_on_stock = false;
                    $stockAvailable->update();
                    $cnt++;
                }
                unset($currentQuantities[$key]);
            } else {
                $stockAvailable = new StockAvailable();
                $stockAvailable->out_of_stock = StockAvailable::outOfStock($productId, $shopId);
                $stockAvailable->depends_on_stock = false;
                $stockAvailable->id_product = $productId;
                $stockAvailable->id_product_attribute = $productAttributeId;
                $stockAvailable->quantity = $quantity;
                $stockAvailable->id_shop = $shopId;
                $stockAvailable->id_shop_group = $shopGroupId;
                $stockAvailable->add();
                $cnt++;
            }
        }

        // delete all residual stock
        if ($currentQuantities) {
            $ids = implode(',', array_column($currentQuantities, 'id'));
            $conn->delete('stock_available', "id_stock_available IN ($ids)");
        }

        return $cnt;
    }

    /**
     * Callback method to initialize class
     *
     * @param Db $conn
     * @return void
     * @throws PrestaShopException
     */
    public static function initializationCallback(Db $conn)
    {
        $task = static::getTaskName();
        $trackingTasks = ScheduledTask::getTasksForCallable($task);
        if (! $trackingTasks) {
            $scheduledTask = new ScheduledTask();
            $scheduledTask->frequency = '0 */8 * * *';
            $scheduledTask->name = 'Dynamic packs synchronization task';
            $scheduledTask->description = 'Synchronizes dynamic packs quantities';
            $scheduledTask->task = $task;
            $scheduledTask->active = true;
            $scheduledTask->add();
        }
    }

    /**
     * @return string
     */
    public static function getTaskName()
    {
        return preg_replace("/Core$/", "", static::class);
    }
}
