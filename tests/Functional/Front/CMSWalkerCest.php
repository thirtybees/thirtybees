<?php

namespace Tests\Functional\Front;

use CMS;
use CMSCategory;
use Codeception\Example;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tests\Support\FunctionalTester;

class CMSWalkerCest
{
    /**
     * This tests walks through front office CMS category pages and ensures that no error is thrown
     *
     * @param FunctionalTester $I
     * @param Example $example
     *
     * @return void
     *
     * @dataProvider getCMSCategoriesToTest
     */
    public function testCmsCategory(FunctionalTester $I, Example $example)
    {
        $categoryId = (int)$example['categoryId'];
        $name = $example['name'];
        $I->amOnPage("/index.php?controller=cms&id_lang=1&id_cms_category=$categoryId");
        $I->see($name);
        $I->withoutErrors();
    }

    /**
     * This tests walks through front office CMS pages and ensures that no error is thrown
     *
     * @param FunctionalTester $I
     * @param Example $example
     *
     * @return void
     *
     * @dataProvider getCMSPagesToTest
     */
    public function testCmsPage(FunctionalTester $I, Example $example)
    {
        $pageId = (int)$example['pageId'];
        $name = $example['name'];
        $I->amOnPage("/index.php?controller=cms&id_lang=1&id_cms=$pageId");
        $I->see($name);
        $I->withoutErrors();
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getCMSCategoriesToTest()
    {
        $categories = CMSCategory::getCategories(1, true, false);
        $ret = [];
        foreach ($categories as $cat) {
            $ret[] = ['name' => $cat['name'], 'categoryId' => $cat['id_cms_category']];
        }
        return $ret;
    }


    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getCMSPagesToTest()
    {
        $pages = CMS::getCMSPages(1, true, true);
        $ret = [];
        foreach ($pages as $page) {
            $ret[] = ['name' => $page['meta_title'], 'pageId' => $page['id_cms']];
        }
        return $ret;
    }

}
