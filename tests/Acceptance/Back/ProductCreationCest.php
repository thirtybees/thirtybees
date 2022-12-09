<?php
namespace Tests\Acceptance\Back;
use Tests\Support\AcceptanceTester;

class ProductCreationCest
{
    /**
     * @param AcceptanceTester $I
     *
     * @return void
     */
    public function _before(AcceptanceTester $I)
    {
        $I->resizeWindow(1920, 1080);
    }

    /**
     * Simple test that creates product from back office
     *
     * @param AcceptanceTester $I
     *
     * @throws Exception
     */
    public function testCreateNewProduct(AcceptanceTester $I)
    {
        // login
        $I->amOnPage('/admin-dev/index.php');
        $I->waitForElementVisible('#email');

        $I->fillField('#email', 'test@thirty.bees');
        $I->fillField('#passwd', 'thirtybees');
        $I->click('Log in');
        $I->withoutErrors();

        // go to product list
        $catalogMenuElement = "#maintab-AdminCatalog a";

        $I->waitForElementVisible($catalogMenuElement, 10);
        $I->click($catalogMenuElement);

        // click on Add new product button
        $I->see('Add new product');
        $I->withoutErrors();
        $I->click('Add new product');

        // enter product name
        $I->see('Add new');
        $I->fillField('#name_1', 'Test product');

        // verify that friendly url was correctly generated from product name
        $I->click('#link-Seo');
        $I->see('Friendly URL');
        $I->see('test-product');

        // enter product price
        $I->click('#link-Prices');
        $I->fillField('#priceTE', "100.00");

        // save product
        $I->click('#link-Informations');
        $I->click('Save');

        // verify that product was created
        $I->see('Successful creation');
        $I->withoutErrors();
    }
}
