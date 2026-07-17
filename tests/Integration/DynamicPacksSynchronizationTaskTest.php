<?php

namespace Tests\Integration;

use Cache;
use Cart;
use Codeception\Test\Unit;
use Context;
use Db;
use Pack;
use PrestaShopException;
use Product;
use StockAvailable;
use Tests\Support\UnitTester;
use Thirtybees\Core\Stock\Synchronization\DynamicPacksSynchronizationTask;
use Thirtybees\Core\WorkQueue\WorkQueueContext;

class DynamicPacksSynchronizationTaskTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var Db
     */
    private $connection;

    /**
     * @var int
     */
    private $shopId;

    /**
     * @var int
     */
    private $stockShopId;

    /**
     * @var int
     */
    private $stockShopGroupId;

    /**
     * @var Cart|null
     */
    private $originalCart;

    /**
     * @throws PrestaShopException
     */
    protected function _before()
    {
        $this->connection = Db::getInstance();
        $this->connection->execute('START TRANSACTION');

        $context = Context::getContext();
        $this->originalCart = $context->cart;
        $context->cart = new Cart();
        $this->shopId = (int)$context->shop->id;
        $stockShop = [];
        StockAvailable::addSqlShopParams($stockShop, $this->shopId);
        $this->stockShopId = (int)$stockShop['id_shop'];
        $this->stockShopGroupId = (int)$stockShop['id_shop_group'];

        Cache::clean('*');
    }

    protected function _after()
    {
        Context::getContext()->cart = $this->originalCart;
        $this->connection->execute('ROLLBACK');
        Cache::clean('*');
    }

    /**
     * @return array[]
     */
    public static function fastUpdateModes()
    {
        return [
            'object model update' => [false],
            'fast database update' => [true],
        ];
    }

    /**
     * @dataProvider fastUpdateModes
     *
     * @param bool $fastUpdate
     * @throws PrestaShopException
     */
    public function testSynchronizesSimplePackQuantitiesInBothUpdateModes($fastUpdate)
    {
        $firstItem = $this->createProduct();
        $secondItem = $this->createProduct();
        $pack = $this->createProduct(true);

        $this->addPackItem($pack, 0, $firstItem, 0, 2);
        $this->addPackItem($pack, 0, $secondItem, 0, 3);
        $this->addStock($firstItem, 0, 11, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->addStock($secondItem, 0, 7, StockAvailable::OUT_OF_STOCK_ALLOW);

        $result = $this->synchronize($pack, $fastUpdate);

        $this->assertSame(1, $result['created']);
        $this->assertStock($pack, 0, 2, StockAvailable::OUT_OF_STOCK_ALLOW);

        // Exercise the update path and the original zero-stock scenario.
        $this->updateStock($firstItem, 0, ['quantity' => 0]);
        $result = $this->synchronize($pack, $fastUpdate);

        $this->assertGreaterThanOrEqual(1, $result['updated']);
        $this->assertStock($pack, 0, 0, StockAvailable::OUT_OF_STOCK_ALLOW);

        $this->updateStock($firstItem, 0, ['quantity' => -1]);
        $this->synchronize($pack, $fastUpdate);
        $this->assertStock($pack, 0, -1, StockAvailable::OUT_OF_STOCK_ALLOW);
    }

    /**
     * @return array[]
     */
    public static function outOfStockPolicies()
    {
        return [
            'all items allow orders' => [
                StockAvailable::OUT_OF_STOCK_ALLOW,
                StockAvailable::OUT_OF_STOCK_ALLOW,
                StockAvailable::OUT_OF_STOCK_ALLOW,
            ],
            'allow and system default use system default' => [
                StockAvailable::OUT_OF_STOCK_ALLOW,
                StockAvailable::OUT_OF_STOCK_SYSTEM_DEFAULT,
                StockAvailable::OUT_OF_STOCK_SYSTEM_DEFAULT,
            ],
            'all items use system default' => [
                StockAvailable::OUT_OF_STOCK_SYSTEM_DEFAULT,
                StockAvailable::OUT_OF_STOCK_SYSTEM_DEFAULT,
                StockAvailable::OUT_OF_STOCK_SYSTEM_DEFAULT,
            ],
            'deny overrides allow' => [
                StockAvailable::OUT_OF_STOCK_DENY,
                StockAvailable::OUT_OF_STOCK_ALLOW,
                StockAvailable::OUT_OF_STOCK_DENY,
            ],
            'deny overrides system default' => [
                StockAvailable::OUT_OF_STOCK_DENY,
                StockAvailable::OUT_OF_STOCK_SYSTEM_DEFAULT,
                StockAvailable::OUT_OF_STOCK_DENY,
            ],
        ];
    }

    /**
     * @dataProvider outOfStockPolicies
     *
     * @param int $firstPolicy
     * @param int $secondPolicy
     * @param int $expectedPolicy
     * @throws PrestaShopException
     */
    public function testAggregatesItemOutOfStockPolicies($firstPolicy, $secondPolicy, $expectedPolicy)
    {
        $firstItem = $this->createProduct();
        $secondItem = $this->createProduct();
        $pack = $this->createProduct(true);

        $this->addPackItem($pack, 0, $firstItem, 0, 1);
        $this->addPackItem($pack, 0, $secondItem, 0, 1);
        $this->addStock($firstItem, 0, 0, $firstPolicy);
        $this->addStock($secondItem, 0, 0, $secondPolicy);
        $this->addStock($pack, 0, 99, $this->differentPolicy($expectedPolicy));

        $this->synchronize($pack, false);

        $this->assertStock($pack, 0, 0, $expectedPolicy);
    }

    /**
     * @throws PrestaShopException
     */
    public function testComponentStockAndOOSChangesResynchronizePack()
    {
        $firstItem = $this->createProduct();
        $secondItem = $this->createProduct();
        $pack = $this->createProduct(true);

        $this->addPackItem($pack, 0, $firstItem, 0, 1);
        $this->addPackItem($pack, 0, $secondItem, 0, 1);
        $this->addStock($firstItem, 0, 5, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->addStock($secondItem, 0, 8, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->synchronize($pack, false);
        $this->assertStock($pack, 0, 5, StockAvailable::OUT_OF_STOCK_ALLOW);

        StockAvailable::setProductOutOfStock(
            $firstItem,
            StockAvailable::OUT_OF_STOCK_DENY,
            $this->shopId
        );
        $this->assertStock($pack, 0, 5, StockAvailable::OUT_OF_STOCK_DENY);

        StockAvailable::setProductOutOfStock(
            $firstItem,
            StockAvailable::OUT_OF_STOCK_SYSTEM_DEFAULT,
            $this->shopId
        );
        $this->assertStock($pack, 0, 5, StockAvailable::OUT_OF_STOCK_SYSTEM_DEFAULT);

        StockAvailable::setProductOutOfStock(
            $firstItem,
            StockAvailable::OUT_OF_STOCK_ALLOW,
            $this->shopId
        );
        $this->assertStock($pack, 0, 5, StockAvailable::OUT_OF_STOCK_ALLOW);

        StockAvailable::setQuantity($firstItem, 0, 0, $this->shopId);
        $this->assertStock($pack, 0, 0, StockAvailable::OUT_OF_STOCK_ALLOW);
    }

    /**
     * @throws PrestaShopException
     */
    public function testEnablingAndSavingDynamicPackSynchronizeImmediately()
    {
        $item = $this->createProduct();
        $packProductId = $this->createProduct(false);

        $this->addPackItem($packProductId, 0, $item, 0, 2);
        $this->addStock($item, 0, 8, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->addStock($packProductId, 0, 99, StockAvailable::OUT_OF_STOCK_DENY);

        $this->assertTrue(Product::setDynamicPack($packProductId, true));
        $this->assertStock($packProductId, 0, 4, StockAvailable::OUT_OF_STOCK_ALLOW);

        // Pack::save() is another synchronization entry point. Change the
        // component row directly so only saving the pack can refresh it.
        $this->updateStock($item, 0, [
            'quantity' => 0,
            'out_of_stock' => StockAvailable::OUT_OF_STOCK_DENY,
        ]);
        $pack = Pack::getProductLevelPack($packProductId);
        $this->assertNotNull($pack);
        $this->assertTrue($pack->save());
        $this->assertStock($packProductId, 0, 0, StockAvailable::OUT_OF_STOCK_DENY);
    }

    /**
     * @throws PrestaShopException
     */
    public function testSynchronizesCombinationPacksAndNormalizesOOSAtProductLevel()
    {
        $sharedItem = $this->createProduct();
        $defaultItem = $this->createProduct();
        $allowItem = $this->createProduct();
        $pack = $this->createProduct(true);
        $firstPackCombination = $this->createCombination($pack);
        $secondPackCombination = $this->createCombination($pack);
        $stalePackCombination = $this->createCombination($pack);

        $this->addPackItem($pack, $firstPackCombination, $sharedItem, 0, 2);
        $this->addPackItem($pack, $firstPackCombination, $defaultItem, 0, 1);
        $this->addPackItem($pack, $secondPackCombination, $sharedItem, 0, 4);
        $this->addPackItem($pack, $secondPackCombination, $allowItem, 0, 3);

        $this->addStock($sharedItem, 0, 10, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->addStock($defaultItem, 0, 5, StockAvailable::OUT_OF_STOCK_SYSTEM_DEFAULT);
        $this->addStock($allowItem, 0, 12, StockAvailable::OUT_OF_STOCK_ALLOW);

        $this->addStock($pack, 0, 123, StockAvailable::OUT_OF_STOCK_DENY);
        $this->addStock($pack, $firstPackCombination, 99, StockAvailable::OUT_OF_STOCK_DENY);
        $this->addStock($pack, $secondPackCombination, 99, StockAvailable::OUT_OF_STOCK_DENY);
        $this->addStock($pack, $stalePackCombination, 99, StockAvailable::OUT_OF_STOCK_DENY);

        $this->synchronize($pack, false);

        // Combination stock is calculated independently.
        $this->assertStock($pack, $firstPackCombination, 5, StockAvailable::OUT_OF_STOCK_SYSTEM_DEFAULT);
        $this->assertStock($pack, $secondPackCombination, 2, StockAvailable::OUT_OF_STOCK_SYSTEM_DEFAULT);

        // OOS is product-level, therefore the most restrictive combination
        // policy is copied to the attribute-zero row and every combination.
        $this->assertOutOfStock($pack, 0, StockAvailable::OUT_OF_STOCK_SYSTEM_DEFAULT);
        $this->assertStockDoesNotExist($pack, $stalePackCombination);
    }

    /**
     * @throws PrestaShopException
     */
    public function testSynchronizesVirtualPackAttributesAndTheirTriggers()
    {
        $item = $this->createProduct();
        $firstItemCombination = $this->createCombination($item);
        $secondItemCombination = $this->createCombination($item);
        $pack = $this->createProduct(true);
        $firstPackCombination = $this->createCombination($pack);
        $secondPackCombination = $this->createCombination($pack);

        $this->mapVirtualCombination($item, $firstItemCombination, $firstPackCombination);
        $this->mapVirtualCombination($item, $secondItemCombination, $secondPackCombination);
        $this->addPackItem($pack, $firstPackCombination, $item, Pack::VIRTUAL_PRODUCT_ATTRIBUTE, 2);
        $this->addPackItem($pack, $secondPackCombination, $item, Pack::VIRTUAL_PRODUCT_ATTRIBUTE, 3);

        // Product-level and combination-level rows all carry the product OOS.
        $this->addStock($item, 0, 0, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->addStock($item, $firstItemCombination, 4, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->addStock($item, $secondItemCombination, 9, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->addStock($pack, 0, 123, StockAvailable::OUT_OF_STOCK_DENY);
        $this->addStock($pack, $firstPackCombination, 99, StockAvailable::OUT_OF_STOCK_DENY);
        $this->addStock($pack, $secondPackCombination, 99, StockAvailable::OUT_OF_STOCK_DENY);

        $this->synchronize($pack, false);

        $this->assertStock($pack, $firstPackCombination, 2, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->assertStock($pack, $secondPackCombination, 3, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->assertOutOfStock($pack, 0, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->assertTrue((bool)Product::isAvailableWhenOutOfStock(
            StockAvailable::outOfStock($pack, $this->shopId)
        ));

        // Changing one resolved item combination must find virtual packs too.
        StockAvailable::setQuantity($item, $firstItemCombination, 0, $this->shopId);
        $this->assertStock($pack, $firstPackCombination, 0, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->assertStock($pack, $secondPackCombination, 3, StockAvailable::OUT_OF_STOCK_ALLOW);

        // A product-level OOS change updates all component rows and then the pack.
        StockAvailable::setProductOutOfStock(
            $item,
            StockAvailable::OUT_OF_STOCK_DENY,
            $this->shopId
        );
        $this->assertOutOfStock($pack, 0, StockAvailable::OUT_OF_STOCK_DENY);
        $this->assertStock($pack, $firstPackCombination, 0, StockAvailable::OUT_OF_STOCK_DENY);
        $this->assertStock($pack, $secondPackCombination, 3, StockAvailable::OUT_OF_STOCK_DENY);
        $this->assertFalse((bool)Product::isAvailableWhenOutOfStock(
            StockAvailable::outOfStock($pack, $this->shopId)
        ));
    }

    /**
     * @throws PrestaShopException
     */
    public function testKeepsQuantitiesAndOOSSeparateForEachStockScope()
    {
        $firstItem = $this->createProduct();
        $secondItem = $this->createProduct();
        $pack = $this->createProduct(true);
        $otherShopId = $this->shopId + 100000;

        $this->addPackItem($pack, 0, $firstItem, 0, 2);
        $this->addPackItem($pack, 0, $secondItem, 0, 2);

        $this->addStock($firstItem, 0, 8, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->addStock($secondItem, 0, 6, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->addStock($firstItem, 0, 4, StockAvailable::OUT_OF_STOCK_ALLOW, $otherShopId, 0);
        $this->addStock($secondItem, 0, 10, StockAvailable::OUT_OF_STOCK_DENY, $otherShopId, 0);
        $this->addStock($pack, 0, 99, StockAvailable::OUT_OF_STOCK_DENY);
        $this->addStock($pack, 0, 99, StockAvailable::OUT_OF_STOCK_ALLOW, $otherShopId, 0);

        $this->synchronize($pack, true);

        $this->assertStock($pack, 0, 3, StockAvailable::OUT_OF_STOCK_ALLOW);
        $this->assertStock($pack, 0, 2, StockAvailable::OUT_OF_STOCK_DENY, $otherShopId, 0);
    }

    /**
     * @param bool $dynamicPack
     * @return int
     */
    private function createProduct($dynamicPack = false)
    {
        $now = date('Y-m-d H:i:s');
        $this->connection->insert('product', [
            'id_shop_default' => $this->shopId,
            'id_tax_rules_group' => 0,
            'cache_is_pack' => $dynamicPack ? 1 : 0,
            'date_add' => $now,
            'date_upd' => $now,
            'pack_dynamic' => $dynamicPack ? 1 : 0,
        ]);
        $productId = (int)$this->connection->Insert_ID();
        $this->connection->insert('product_shop', [
            'id_product' => $productId,
            'id_shop' => $this->shopId,
            'id_tax_rules_group' => 0,
            'date_add' => $now,
            'date_upd' => $now,
            'pack_dynamic' => $dynamicPack ? 1 : 0,
        ]);

        return $productId;
    }

    /**
     * @param int $productId
     * @return int
     */
    private function createCombination($productId)
    {
        $this->connection->insert('product_attribute', [
            'id_product' => $productId,
        ]);
        $combinationId = (int)$this->connection->Insert_ID();
        $this->connection->insert('product_attribute_shop', [
            'id_product' => $productId,
            'id_product_attribute' => $combinationId,
            'id_shop' => $this->shopId,
        ]);

        return $combinationId;
    }

    /**
     * @param int $packId
     * @param int $packCombinationId
     * @param int $itemId
     * @param int $itemCombinationId
     * @param int $quantity
     */
    private function addPackItem($packId, $packCombinationId, $itemId, $itemCombinationId, $quantity)
    {
        $this->connection->insert('pack', [
            'id_product_pack' => $packId,
            'id_product_attribute_pack' => $packCombinationId,
            'id_product_item' => $itemId,
            'id_product_attribute_item' => $itemCombinationId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * @param int $itemProductId
     * @param int $itemCombinationId
     * @param int $packCombinationId
     */
    private function mapVirtualCombination($itemProductId, $itemCombinationId, $packCombinationId)
    {
        $attributeGroupId = (int)$this->connection->getValue(
            'SELECT id_attribute_group FROM '._DB_PREFIX_.'attribute_group'.
            ' WHERE id_product_ref = '.(int)$itemProductId
        );
        if (!$attributeGroupId) {
            $this->connection->insert('attribute_group', [
                'id_product_ref' => $itemProductId,
            ]);
            $attributeGroupId = (int)$this->connection->Insert_ID();
        }

        $this->connection->insert('attribute', [
            'id_attribute_group' => $attributeGroupId,
            'id_product_attribute_ref' => $itemCombinationId,
        ]);
        $attributeId = (int)$this->connection->Insert_ID();
        $this->connection->insert('product_attribute_combination', [
            'id_attribute' => $attributeId,
            'id_product_attribute' => $packCombinationId,
        ]);
    }

    /**
     * @param int $productId
     * @param int $combinationId
     * @param int $quantity
     * @param int $outOfStock
     * @param int|null $shopId
     * @param int|null $shopGroupId
     */
    private function addStock($productId, $combinationId, $quantity, $outOfStock, $shopId = null, $shopGroupId = null)
    {
        $this->connection->insert('stock_available', [
            'id_product' => $productId,
            'id_product_attribute' => $combinationId,
            'id_shop' => is_null($shopId) ? $this->stockShopId : $shopId,
            'id_shop_group' => is_null($shopGroupId) ? $this->stockShopGroupId : $shopGroupId,
            'quantity' => $quantity,
            'depends_on_stock' => 0,
            'out_of_stock' => $outOfStock,
        ]);
    }

    /**
     * @param int $productId
     * @param int $combinationId
     * @param array $values
     */
    private function updateStock($productId, $combinationId, array $values)
    {
        $this->connection->update(
            'stock_available',
            $values,
            'id_product = '.(int)$productId.
            ' AND id_product_attribute = '.(int)$combinationId.
            ' AND id_shop = '.$this->stockShopId.
            ' AND id_shop_group = '.$this->stockShopGroupId
        );
    }

    /**
     * @param int $packId
     * @param bool $fastUpdate
     * @return array
     * @throws PrestaShopException
     */
    private function synchronize($packId, $fastUpdate)
    {
        $task = new DynamicPacksSynchronizationTask();
        $result = $task->execute(
            WorkQueueContext::fromContext(Context::getContext()),
            [
                DynamicPacksSynchronizationTask::PARAMETER_PRODUCT_IDS => [$packId],
                DynamicPacksSynchronizationTask::PARAMETER_FAST_UPDATE => $fastUpdate,
            ]
        );
        $result = json_decode($result, true);
        $this->assertIsArray($result);

        return $result;
    }

    /**
     * @param int $productId
     * @param int $combinationId
     * @param int $expectedQuantity
     * @param int $expectedOutOfStock
     * @param int|null $shopId
     * @param int|null $shopGroupId
     */
    private function assertStock(
        $productId,
        $combinationId,
        $expectedQuantity,
        $expectedOutOfStock,
        $shopId = null,
        $shopGroupId = null
    ) {
        $row = $this->getStock($productId, $combinationId, $shopId, $shopGroupId);
        $this->assertNotFalse($row, 'Expected stock_available row does not exist');
        $this->assertSame($expectedQuantity, (int)$row['quantity']);
        $this->assertSame($expectedOutOfStock, (int)$row['out_of_stock']);
        $this->assertSame(0, (int)$row['depends_on_stock']);
    }

    /**
     * @param int $productId
     * @param int $combinationId
     * @param int $expectedOutOfStock
     */
    private function assertOutOfStock($productId, $combinationId, $expectedOutOfStock)
    {
        $row = $this->getStock($productId, $combinationId);
        $this->assertNotFalse($row, 'Expected stock_available row does not exist');
        $this->assertSame($expectedOutOfStock, (int)$row['out_of_stock']);
    }

    /**
     * @param int $productId
     * @param int $combinationId
     * @param int|null $shopId
     * @param int|null $shopGroupId
     */
    private function assertStockDoesNotExist($productId, $combinationId, $shopId = null, $shopGroupId = null)
    {
        $this->assertFalse($this->getStock($productId, $combinationId, $shopId, $shopGroupId));
    }

    /**
     * @param int $productId
     * @param int $combinationId
     * @param int|null $shopId
     * @param int|null $shopGroupId
     * @return array|false
     */
    private function getStock($productId, $combinationId, $shopId = null, $shopGroupId = null)
    {
        $shopId = is_null($shopId) ? $this->stockShopId : $shopId;
        $shopGroupId = is_null($shopGroupId) ? $this->stockShopGroupId : $shopGroupId;

        return $this->connection->getRow(
            'SELECT quantity, depends_on_stock, out_of_stock'.
            ' FROM '._DB_PREFIX_.'stock_available'.
            ' WHERE id_product = '.(int)$productId.
            ' AND id_product_attribute = '.(int)$combinationId.
            ' AND id_shop = '.(int)$shopId.
            ' AND id_shop_group = '.(int)$shopGroupId
        );
    }

    /**
     * @param int $policy
     * @return int
     */
    private function differentPolicy($policy)
    {
        return $policy === StockAvailable::OUT_OF_STOCK_DENY
            ? StockAvailable::OUT_OF_STOCK_ALLOW
            : StockAvailable::OUT_OF_STOCK_DENY;
    }
}
