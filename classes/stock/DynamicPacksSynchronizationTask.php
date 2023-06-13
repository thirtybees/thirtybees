<?php
/**
 * Copyright (C) 2022-2022 thirty bees
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
 * @copyright 2021-2021 thirty bees
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

        if (isset($parameters['productIds'])) {
            $productIds = array_filter(array_map('intval', $parameters['productIds']));
            $productIdsSql = (new DbQuery())
                ->select('DISTINCT id_product')
                ->from('product_shop')
                ->where('pack_dynamic')
                ->where('id_product IN (' .implode(',', $productIds). ')');
            $productIds = array_map('intval', array_column($conn->getArray($productIdsSql), 'id_product'));
        } else {
            $productIds = Pack::getDynamicPacks();
        }

        if (! $productIds) {
            return 0;
        }

        $productIds = implode(',', $productIds);

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
        $dynamicStockSql = (new DbQuery())
            ->select('sa.id_shop')
            ->select('sa.id_shop_group')
            ->select('p.id_product_pack AS id_product')
            ->select('0 AS id_product_attribute')
            ->select('MIN(FLOOR(sa.quantity / p.quantity)) AS quantity')
            ->from('pack', 'p')
            ->innerJoin('stock_available', 'sa', '(sa.id_product = p.id_product_item AND sa.id_product_attribute = p.id_product_attribute_item)')
            ->where("p.id_product_pack IN ($productIds)")
            ->groupBy('sa.id_shop')
            ->groupBy('sa.id_shop_group')
            ->groupBy('p.id_product_pack');

        $cnt = 0;
        // update stock
        foreach ($conn->getArray($dynamicStockSql) as $row) {
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
                    $stockAvailable->update();
                    $cnt++;
                }
                unset($currentQuantities[$key]);
            } else {
                $stockAvailable = new StockAvailable();
                $stockAvailable->out_of_stock = StockAvailable::outOfStock($productId, $shopId);
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
