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

use Configuration;
use Db;
use DbQuery;
use Pack;
use PrestaShopDatabaseException;
use PrestaShopException;
use StockAvailable;
use Context;
use Thirtybees\Core\DependencyInjection\ServiceLocator;
use Thirtybees\Core\Error\ErrorUtils;
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
    const PARAMETER_FAST_UPDATE = 'fastUpdate';
    const PARAMETER_PRODUCT_IDS = 'productIds';

    /**
     * Creates work queue task to synchronize packs
     *
     * @param int[] $productIds
     * @return WorkQueueTask
     * @throws PrestaShopException
     */
    public static function createTask($productIds = null)
    {
        $parameters = [
            static::PARAMETER_FAST_UPDATE => (bool)Configuration::getGlobalValue('TB_DYNAMIC_PACKS_SYNC_TASK_FAST_UPDATE')
        ];
        if (! is_null($productIds)) {
            $parameters[static::PARAMETER_PRODUCT_IDS] = array_filter(array_map('intval', $productIds));
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
        $fastUpdate = (bool)($parameters[static::PARAMETER_FAST_UPDATE] ?? false);

        $products = [];
        $hasCombinationPacks = (new DbQuery())
            ->select('1')
            ->from('pack', 'pack')
            ->where('pack.id_product_pack = ps.id_product')
            ->where('pack.id_product_attribute_pack != 0');
        if (isset($parameters[static::PARAMETER_PRODUCT_IDS])) {
            $productIds = array_filter(array_map('intval', $parameters[static::PARAMETER_PRODUCT_IDS]));
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
        $virtualProductAttribute = (int)Pack::VIRTUAL_PRODUCT_ATTRIBUTE;
        $resolvedItemCombination = "IF(
            p.id_product_attribute_item = $virtualProductAttribute AND ag.id_attribute_group IS NOT NULL,
            a.id_product_attribute_ref,
            p.id_product_attribute_item
        )";
        if ($combinationPacks) {
            $combinationPackIds = implode(',', $combinationPacks);
            $dynamicStockSql = (new DbQuery())
                ->select('sa.id_shop')
                ->select('sa.id_shop_group')
                ->select('p.id_product_pack AS id_product')
                ->select('pa.id_product_attribute AS id_product_attribute')
                ->select('MIN(FLOOR(sa.quantity / p.quantity)) AS quantity')
                ->from('pack', 'p')
                ->innerJoin('product_attribute', 'pa', '(pa.id_product = p.id_product_pack AND pa.id_product_attribute = p.id_product_attribute_pack)')
                ->leftJoin('product_attribute_combination', 'pac', "(pac.id_product_attribute = pa.id_product_attribute AND p.id_product_attribute_item = $virtualProductAttribute)")
                ->leftJoin('attribute', 'a', '(a.id_attribute = pac.id_attribute AND a.id_product_attribute_ref IS NOT NULL)')
                ->leftJoin('attribute_group', 'ag', '(ag.id_attribute_group = a.id_attribute_group AND ag.id_product_ref = p.id_product_item)')
                ->innerJoin('stock_available', 'sa', "(sa.id_product = p.id_product_item AND sa.id_product_attribute = $resolvedItemCombination)")
                ->where("p.id_product_pack IN ($combinationPackIds)")
                ->groupBy('sa.id_shop')
                ->groupBy('sa.id_shop_group')
                ->groupBy('p.id_product_pack')
                ->groupBy('pa.id_product_attribute');
            $this->mergeExpectedQuantities($expectedQuantities, $conn->getArray($dynamicStockSql));
        }
        if ($productPacks) {
            $productPackIds = implode(',', $productPacks);
            $dynamicStockSql = (new DbQuery())
                ->select('sa.id_shop')
                ->select('sa.id_shop_group')
                ->select('p.id_product_pack AS id_product')
                ->select('COALESCE(pa.id_product_attribute, 0) AS id_product_attribute')
                ->select('MIN(FLOOR(sa.quantity / p.quantity)) AS quantity')
                ->from('pack', 'p')
                ->leftJoin('product_attribute', 'pa', '(pa.id_product = p.id_product_pack)')
                ->leftJoin('product_attribute_combination', 'pac', "(pac.id_product_attribute = pa.id_product_attribute AND p.id_product_attribute_item = $virtualProductAttribute)")
                ->leftJoin('attribute', 'a', '(a.id_attribute = pac.id_attribute AND a.id_product_attribute_ref IS NOT NULL)')
                ->leftJoin('attribute_group', 'ag', '(ag.id_attribute_group = a.id_attribute_group AND ag.id_product_ref = p.id_product_item)')
                ->innerJoin('stock_available', 'sa', "(sa.id_product = p.id_product_item AND sa.id_product_attribute = $resolvedItemCombination)")
                ->where("p.id_product_pack IN ($productPackIds)")
                ->groupBy('sa.id_shop')
                ->groupBy('sa.id_shop_group')
                ->groupBy('p.id_product_pack')
                ->groupBy('COALESCE(pa.id_product_attribute, 0)');
            $this->mergeExpectedQuantities($expectedQuantities, $conn->getArray($dynamicStockSql));
        }

        $updated = 0;
        $created = 0;
        $ignored = 0;
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
                    $stockAvailableId = (int)$currentQuantities[$key]['id'];
                    $this->updateStockAvailableQuantity($stockAvailableId, $quantity, $fastUpdate);
                    $updated++;
                } else {
                    $ignored++;
                }
                unset($currentQuantities[$key]);
            } else {
                $this->createStockAvailableRecord($productId, $productAttributeId, $shopId, $shopGroupId, $quantity);
                $created++;
            }
        }

        // delete all residual stock
        if ($currentQuantities) {
            $ids = implode(',', array_column($currentQuantities, 'id'));
            $conn->delete('stock_available', "id_stock_available IN ($ids) AND id_product_attribute != 0");
        }

        return json_encode([
            'created' => $created,
            'updated' => $updated,
            'ignored' => $ignored,
        ]);
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

    /**
     * @param int $productId
     * @param int $productAttributeId
     * @param int $shopId
     * @param int $shopGroupId
     * @param int $quantity
     */
    public function createStockAvailableRecord(int $productId, int $productAttributeId, int $shopId, int $shopGroupId, int $quantity)
    {
        try {
            $stockAvailable = new StockAvailable();
            $stockAvailable->out_of_stock = StockAvailable::outOfStock($productId, $shopId);
            $stockAvailable->depends_on_stock = false;
            $stockAvailable->id_product = $productId;
            $stockAvailable->id_product_attribute = $productAttributeId;
            $stockAvailable->quantity = $quantity;
            $stockAvailable->id_shop = $shopId;
            $stockAvailable->id_shop_group = $shopGroupId;
            $stockAvailable->add();
        } catch (PrestaShopException $e) {
            $errorHandler = ServiceLocator::getInstance()->getErrorHandler();
            $errorDescription = ErrorUtils::describeException($e);
            $errorHandler->logFatalError($errorDescription);
        }
    }

    /**
     * @param int $stockAvailableId
     * @param int $quantity
     * @param bool $fastUpdate
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updateStockAvailableQuantity(int $stockAvailableId, int $quantity, bool $fastUpdate): void
    {
        if ($fastUpdate) {
            $conn = Db::getInstance();
            $conn->update('stock_available', [ 'quantity' => $quantity ], 'id_stock_available = ' . $stockAvailableId);
        } else {
            $stockAvailable = new StockAvailable($stockAvailableId);
            $stockAvailable->quantity = $quantity;
            $stockAvailable->depends_on_stock = false;
            $stockAvailable->update();
        }
    }

    /**
     * @param array $expectedQuantities
     * @param array $data
     */
    private function mergeExpectedQuantities(array &$expectedQuantities, array $data)
    {
        foreach ($data as $row) {
            $productId = (int)$row['id_product'];
            $productAttributeId = (int)$row['id_product_attribute'];
            $shopId = (int)$row['id_shop'];
            $shopGroupId = (int)$row['id_shop_group'];
            $key = "$shopId|$shopGroupId|$productId|$productAttributeId";
            if (! isset($expectedQuantities[$key])) {
                $expectedQuantities[$key] = $row;
            }
        }
    }


}
