<?php

namespace Tests\Integration;

use Adapter_ServiceLocator;
use Cache;
use Carrier;
use Cart;
use Codeception\Test\Unit;
use Configuration;
use Db;
use PrestaShopDatabaseException;
use PrestaShopException;
use Product;
use StockAvailable;
use Tests\Support\UnitTester;
use Warehouse;
use WarehouseProductLocation;

class CartPackageListTest extends Unit
{

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * Before each test callback
     *
     * @throws PrestaShopException
     */
    protected function _before() {
        $this->reset();
        $this->associateCarrierWithZone(1, 2);
    }

    /**
     * After each test callback
     *
     * @throws PrestaShopException
     */
    protected function _after()
    {
        $this->reset();
    }


    /**
     * Cart with single product
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testSingleProduct()
    {
        $cart = $this->createCart([
            [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ]
        ]);

        $this->verify([
            0 => [
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ]
                    ],
                    'carriers' => [ 1, 2, ],
                    'warehouse' => 0,
                ],
            ]
        ], $cart->getPackageList(true));
    }

    /**
     * Cart with multiple products
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testMultipleProducts()
    {
        $cart = $this->createCart([
            [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ],
            [ 'productId' => 2, 'combinationId' => 8, 'quantity' => 2 ],
            [ 'productId' => 8, 'combinationId' => 0, 'quantity' => 1 ],
        ]);

        $this->verify([
            0 => [
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ],
                        [ 'productId' => 2, 'combinationId' => 8, 'quantity' => 2 ],
                        [ 'productId' => 8, 'combinationId' => 0, 'quantity' => 1 ],
                    ],
                    'carriers' => [ 1, 2, ],
                    'warehouse' => 0,
                ],
            ]
        ], $cart->getPackageList(true));
    }

    /**
     * Cart with multiple products, one of them having carrier restriction
     *
     * This should lead to single packages + restricted carrier list
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testProductWithCarrierRestriction()
    {
        // restrict product 8 to use carrier 1
        $product = new Product(8);
        $product->setCarriers([1]);
        Cache::clean('*');

        $cart = $this->createCart([
            [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ],
            [ 'productId' => 2, 'combinationId' => 8, 'quantity' => 2 ],
            [ 'productId' => 8, 'combinationId' => 0, 'quantity' => 1 ],
        ]);

        $this->verify([
            0 => [
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ],
                        [ 'productId' => 2, 'combinationId' => 8, 'quantity' => 2 ],
                        [ 'productId' => 8, 'combinationId' => 0, 'quantity' => 1 ],
                    ],
                    'carriers' => [ 1 ],
                    'warehouse' => 0,
                ],
            ]
        ], $cart->getPackageList(true));
    }

    /**
     * Cart with multiple products, each of them having different carrier restriction
     *
     * This should lead to multiple packages, every using different carrier
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testProductsWithDifferentCarrierRestriction()
    {
        // restrict product 8 to use carrier 1
        $product8 = new Product(8);
        $product8->setCarriers([1]);

        $product1 = new Product(1);
        $product1->setCarriers([2]);
        Cache::clean('*');

        $cart = $this->createCart([
            [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ],
            [ 'productId' => 8, 'combinationId' => 0, 'quantity' => 1 ],
        ]);

        $this->verify([
            0 => [
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ],
                    ],
                    'carriers' => [ 2 ],
                    'warehouse' => 0,
                ],
                [
                    'products' => [
                        [ 'productId' => 8, 'combinationId' => 0, 'quantity' => 1 ],
                    ],
                    'carriers' => [ 1 ],
                    'warehouse' => 0,
                ],
            ]
        ], $cart->getPackageList(true));
    }

    /**
     * Cart with product tracked in ASM, but without warehouse association
     *
     * This should lead to single package without warehouse association.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testASMSingleProductWithoutWarehouseAssociation()
    {
        $this->setASM(true);
        $product = new Product(1);
        $product->setAdvancedStockManagement(true);


        $cart = $this->createCart([
            [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ]
        ]);

        $this->verify([
            0 => [
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ]
                    ],
                    'carriers' => [ 1, 2 ],
                    'warehouse' => 0,
                ],
            ]
        ], $cart->getPackageList(true));
    }

    /**
     * Cart with product tracked in ASM, with warehouse association
     *
     * This should lead to single package, carrier list should be restricted by warehouse carriers
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testASMSingleProductWithWarehouseAssociation()
    {
        $this->setASM(true);
        $product = new Product(1);
        $product->setAdvancedStockManagement(true);
        $warehouse = $this->createWarehouse("w1", [2]);
        $this->associateWarehouse($warehouse, 1, 2);

        $cart = $this->createCart([
            [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ]
        ]);

        $this->verify([
            0 => [
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ]
                    ],
                    'carriers' => [ 2 ],
                    'warehouse' => (int)$warehouse->id,
                ],
            ]
        ], $cart->getPackageList(true));
    }

    /**
     * Cart with product tracked in ASM, with warehouse association, and product restriction
     *
     * This should lead to single package. Carrier list should be restricted by warehouse carriers AND
     * product carriers restriction
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testASMSingleProductWithWarehouseAssociationAndProductRestriction()
    {
        $this->setASM(true);
        $product = new Product(1);
        $product->setAdvancedStockManagement(true);
        $product->setCarriers([1]);
        $warehouse = $this->createWarehouse("w1", [1, 2]);
        $this->associateWarehouse($warehouse, 1, 2);

        $cart = $this->createCart([
            [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ]
        ]);

        $this->verify([
            0 => [
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ]
                    ],
                    'carriers' => [ 1 ],
                    'warehouse' => (int)$warehouse->id,
                ],
            ]
        ], $cart->getPackageList(true));
    }

    /**
     * Cart with multiple ASM products, all sharing same warehouse
     *
     * This should lead to single package. Carrier list should be restricted by warehouse carriers AND
     * products carriers restrictions
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testASMMultipleProductsSingleWarehouse()
    {
        $this->setASM(true);
        $product1 = new Product(1);
        $product1->setAdvancedStockManagement(true);

        $product8 = new Product(8);
        $product8->setAdvancedStockManagement(true);

        $warehouse = $this->createWarehouse("w1", [1, 2]);

        $this->associateWarehouse($warehouse, 1, 2);
        $this->associateWarehouse($warehouse, 8, 0);

        $cart = $this->createCart([
            [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ],
            [ 'productId' => 8, 'combinationId' => 0, 'quantity' => 1 ]
        ]);

        $this->verify([
            0 => [
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ],
                        [ 'productId' => 8, 'combinationId' => 0, 'quantity' => 1 ]
                    ],
                    'carriers' => [ 1, 2 ],
                    'warehouse' => (int)$warehouse->id,
                ],
            ]
        ], $cart->getPackageList(true));
    }

    /**
     * Cart with multiple ASM products, all stored in different warehouses
     *
     * This should lead to multiple packages, from different warehouses
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testASMMultipleProductsDifferentWarehouses()
    {
        $this->setASM(true);
        $product1 = new Product(1);
        $product1->setAdvancedStockManagement(true);

        $product8 = new Product(8);
        $product8->setAdvancedStockManagement(true);

        $w1 = $this->createWarehouse("w1", [1, 2]);
        $w2 = $this->createWarehouse("w2", [2]);

        $this->associateWarehouse($w1, 1, 2);
        $this->associateWarehouse($w2, 8, 0);

        $cart = $this->createCart([
            [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ],
            [ 'productId' => 2, 'combinationId' => 8, 'quantity' => 1 ],
            [ 'productId' => 8, 'combinationId' => 0, 'quantity' => 1 ]
        ]);

        $this->verify([
            0 => [
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 2, 'quantity' => 1 ]
                    ],
                    'carriers' => [ 1, 2 ],
                    'warehouse' => (int)$w1->id,
                ],
                [
                    'products' => [
                        [ 'productId' => 2, 'combinationId' => 8, 'quantity' => 1 ]
                    ],
                    'carriers' => [ 1, 2 ],
                    'warehouse' => 0
                ],
                [
                    'products' => [
                        [ 'productId' => 8, 'combinationId' => 0, 'quantity' => 1 ]
                    ],
                    'carriers' => [ 2 ],
                    'warehouse' => (int)$w2->id,
                ],
            ]
        ], $cart->getPackageList(true));
    }

    /**
     * Cart with single product, ordered quantity > actual store quantity (OOS ordering allowed)
     *
     * This should lead to single package
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testSinglePackageForOOS()
    {
        // allow ordering out of stock for all products
        $this->setOOS(1);

        $currentQuantity = StockAvailable::getQuantityAvailableByProduct(1,2);
        $extraQuantity = 100;
        $orderQuantity = $currentQuantity + $extraQuantity;

        $cart = $this->createCart([
            [ 'productId' => 1, 'combinationId' => 2, 'quantity' => $orderQuantity ],
        ]);

        $this->verify([
            0 => [
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 2, 'quantity' => $orderQuantity ]
                    ],
                    'carriers' => [ 1, 2 ],
                    'warehouse' => 0
                ],
            ]
        ], $cart->getPackageList(true));
    }

    /**
     * Cart with single product, ordered quantity > actual store quantity (OOS ordering allowed), and
     * separate package allowed
     *
     * This should lead to two packages, one for current quantity on stock, other for remaining quantity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testSeparatePackageForOOS()
    {
        // allow ordering out of stock for all products
        $this->setOOS(1);

        $currentQuantity = StockAvailable::getQuantityAvailableByProduct(1,2);
        $extraQuantity = 100;
        $orderQuantity = $currentQuantity + $extraQuantity;

        $cart = $this->createCart([
            [ 'productId' => 1, 'combinationId' => 2, 'quantity' => $orderQuantity ],
        ]);

        // allow separate package for in_stock/out_of_stock
        $cart->allow_seperated_package = true;

        $this->verify([
            0 => [
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 2, 'quantity' => $currentQuantity ]
                    ],
                    'carriers' => [ 1, 2 ],
                    'warehouse' => 0
                ],
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 2, 'quantity' => $extraQuantity ]
                    ],
                    'carriers' => [ 1, 2 ],
                    'warehouse' => 0
                ],
            ]
        ], $cart->getPackageList(true));
    }

    /**
     * Product #1 ha assigned carrier that does not deliver to current zone
     *
     * This should result in two packages - one package without valid carriers
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testUndeliverableProduct()
    {
        $this->removeCarrierFromZone(1, 2);

        // restrict product 8 to use carrier 1
        $product1 = new Product(1);
        $product1->setCarriers([1]);

        $product2 = new Product(2);
        $product2->setCarriers([2]);
        Cache::clean('*');

        $cart = $this->createCart([
            [ 'productId' => 1, 'combinationId' => 1, 'quantity' => 1 ],
            [ 'productId' => 2, 'combinationId' => 7, 'quantity' => 2 ],
        ]);

        $this->verify([
            0 => [
                [
                    'products' => [
                        [ 'productId' => 1, 'combinationId' => 1, 'quantity' => 1 ],
                    ],
                    'carriers' => [ 0 ],
                    'warehouse' => Cart::NO_CARRIER_FOUND_PLACEHOLDER,
                ],
                [
                    'products' => [
                        [ 'productId' => 2, 'combinationId' => 7, 'quantity' => 2 ],
                    ],
                    'carriers' => [ 2 ],
                    'warehouse' => 0,
                ],
            ]
        ], $cart->getPackageList(true));
    }


    /**
     * Verification of actual package list against expectation
     *
     * @param array $expectedAddressList
     * @param array $actualAddressList
     */
    private function verify($expectedAddressList, $actualAddressList)
    {
        $this->assertNotEmpty($actualAddressList);
        $addressCnt = count($expectedAddressList);
        $this->assertCount($addressCnt, $actualAddressList, "Cart should be split into $addressCnt addresses");

        foreach ($expectedAddressList as $addressId => $expectedPackageList) {
            $this->assertArrayHasKey($addressId, $actualAddressList, "Address list does not contain address $addressId");
            $actualPackageList = $actualAddressList[$addressId];
            $this->assertNotEmpty($actualPackageList);

            $packageCnt = count($expectedPackageList);
            $this->assertCount($packageCnt, $actualPackageList, "Cart should be split into $packageCnt packages");

            for ($i = 0; $i < $packageCnt; $i++) {
                $expectedPackage = $expectedPackageList[$i];
                $actualPackage = $actualPackageList[$i];

                // test product list
                $actualProducts = array_map(function($product) {
                    return [
                        'productId' => (int)$product['id_product'],
                        'combinationId' => (int)$product['id_product_attribute'],
                        'quantity' => (int)$product['cart_quantity'],
                    ];
                }, $actualPackage['product_list']);
                $expectedProducts = $expectedPackage['products'];

                $this->assertSame($expectedProducts, $actualProducts, "Product list in the package does not match");

                // test carrier list
                $actualCarrierList = array_values($actualPackage['carrier_list']);
                $expectedCarrierList = array_values($expectedPackage['carriers']);
                $this->assertEquals($expectedCarrierList, $actualCarrierList, "Carrier list does not match");

                // test warehouse
                $this->assertEquals($expectedPackage['warehouse'], $actualPackage['id_warehouse'], 'Package warehouse does not match');
            }
        }
    }

    /**
     * Helper method to restore settings changed by tests
     *
     * @throws PrestaShopException
     */
    private function reset() {
        $this->resetProducts();
        $this->deleteWarehouses();
        $this->setASM(false);
        $this->setOOS(2);
        $this->removeCarrierFromZone(1, 2);
        Cache::clean('*');
    }

    /**
     * Helper method to associate warehouse with product
     *
     * @param Warehouse $warehouse
     * @param int $productId
     * @param int $combinationId
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function associateWarehouse(Warehouse $warehouse, $productId, $combinationId)
    {
        $wl = new WarehouseProductLocation();
        $wl->id_product = $productId;
        $wl->id_warehouse = $warehouse->id;
        $wl->location = "loc-" . $productId . "-" . $combinationId;
        $wl->id_product_attribute = $combinationId;
        $wl->save();
    }


    /**
     * Helper method to restore products settings
     *
     * @throws PrestaShopException
     */
    private function resetProducts() {
        $repository = Adapter_ServiceLocator::get('Core_Foundation_Database_EntityManager')->getRepository('WarehouseProductLocation');
        foreach ([1, 2, 8] as $productId) {
            $product = new Product($productId);
            $product->setAdvancedStockManagement(false);
            $product->setCarriers([]);

            /** @var WarehouseProductLocation[] $warehouseProductLocations */
            $warehouseProductLocations = $repository->findByIdProduct($productId);
            if ($warehouseProductLocations) {
                foreach ($warehouseProductLocations as $warehouseProductLocation) {
                    $warehouseProductLocation->delete();
                }
            }
        }
    }

    /**
     * Enabled/Disabled Advanced Stock Management feature
     *
     * @param bool $value
     * @throws PrestaShopException
     */
    private function setASM($value) {
        Configuration::updateValue('PS_ADVANCED_STOCK_MANAGEMENT', $value ? 1 : 0);
    }

    /**
     * Sets Out of stock behaviour for all products
     *
     * @param int $value Out of stock behavior. 0=block, 1=allow, 2=use system wide settings
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function setOOS($value) {
        Db::getInstance()->update("stock_available", ['out_of_stock' => $value]);
        Cache::clean("*");
    }

    /**
     * Creates new cart with products
     * @param array $products
     * @return Cart
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function createCart($products) {
        $cart = new Cart();
        $cart->id_currency = 1;
        $cart->save();
        foreach ($products as $info) {
            $cart->updateQty($info['quantity'], $info['productId'], $info['combinationId']);
        }
        return $cart;
    }

    /**
     * Creates new warehouse, and associate it with carriers
     *
     * @param string $name
     * @param int[] $carriers
     * @return Warehouse
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function createWarehouse($name, $carriers) {
        $warehouse = new Warehouse();
        $warehouse->id_currency = 1;
        $warehouse->id_address = 1;
        $warehouse->id_employee = 1;
        $warehouse->name = $name;
        $warehouse->reference = $name;
        $warehouse->management_type = "WA";
        $warehouse->save();
        if ($carriers) {
            $warehouse->setCarriers($carriers);
        }
        return $warehouse;
    }

    /**
     * Deletes all warehouses
     *
     * @throws PrestaShopException
     */
    private function deleteWarehouses()
    {
        $repository = Adapter_ServiceLocator::get('Core_Foundation_Database_EntityManager')->getRepository('Warehouse');
        $warehouses = $repository->findAll();
        if ($warehouses) {
            /** @var Warehouse $warehouse */
            foreach ($warehouses as $warehouse) {
                $warehouse->delete();
            }
        }
    }

    /**
     * Adds carrier to zone
     *
     * @param int $carrierId
     * @param int $zoneId
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function associateCarrierWithZone($carrierId, $zoneId)
    {
        $carrier = new Carrier($carrierId);
        $carrier->addZone($zoneId);
        Cache::clean("*");
    }

    /**
     * deletes carrier from zone
     *
     * @param int $carrierId
     * @param int $zoneId
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function removeCarrierFromZone($carrierId, $zoneId)
    {
        $carrier = new Carrier($carrierId);
        $carrier->deleteZone($zoneId);
        Cache::clean("*");
    }


}
