<?php

namespace Tests\Functional\Front;

use Codeception\Example;
use PrestaShopDatabaseException;
use PrestaShopException;
use Product;
use Tests\Support\FunctionalTester;

class ProductWalkerCest
{
    /**
     * This tests walks through front office product pages and ensures that no error is thrown
     *
     * @param FunctionalTester $I
     * @param Example $example
     *
     * @return void
     *
     * @dataProvider getProductsToTest
     */
    public function testProduct(FunctionalTester $I, Example $example)
    {
        $productId = $example['productId'];
        $name = $example['name'];
        $I->amOnPage("/index.php?id_product=$productId&controller=product&id_lang=1");
        $I->see($name);
        $I->see('Add to cart');
        $I->withoutErrors();
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getProductsToTest()
    {
        $products = Product::getProducts(1, 0, 0, 'id_product', 'asc', false, true);
        $toTest = [];
        foreach ($products as $product) {
            $toTest[] = [
                'name' => $product['name'],
                'productId' => $product['id_product']
            ];
        }
        return $toTest;
    }


}
