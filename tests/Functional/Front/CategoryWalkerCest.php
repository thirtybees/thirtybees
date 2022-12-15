<?php

namespace Tests\Functional\Front;

use Codeception\Example;
use PrestaShopDatabaseException;
use PrestaShopException;
use Category;
use Tests\Support\FunctionalTester;

class CategoryWalkerCest
{
    /**
     * This tests walks through front office category pages and ensures that no error is thrown
     *
     * @param FunctionalTester $I
     * @param Example $example
     *
     * @return void
     *
     * @dataProvider getCategoriesToTest
     */
    public function testCategory(FunctionalTester $I, Example $example)
    {
        $categoryId = $example['categoryId'];
        $name = $example['name'];
        $I->amOnPage("/index.php?id_category=$categoryId&controller=category&id_lang=1");
        $I->see($name);
        $I->withoutErrors();
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getCategoriesToTest()
    {
        $categories = Category::getCategories(1, true, false);
        $toTest = [];
        foreach ($categories as $category) {
            if ($category['id_parent']) {
                $toTest[] = [
                    'name' => $category['name'],
                    'categoryId' => $category['id_category']
                ];
            }
        }
        return $toTest;
    }


}
