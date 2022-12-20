<?php

namespace Tests\Functional\Front;

use Tests\Support\FunctionalTester;

class MyAccountCest
{
    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function createAccountFormIsVisible(FunctionalTester $I)
    {
        $I->amOnPage('/index.php');
        $I->click('a.login');
        $I->seeElement('#create-account_form');
        $I->withoutErrors();
    }

    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function testLogin(FunctionalTester $I)
    {
        $I->amLoggedInToFrontOffice();
        $I->see("Sign out");
        $I->withoutErrors();
    }

    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function testRedirectAfterLogin(FunctionalTester $I)
    {
        $I->amOnPage('index.php?controller=history');
        $I->seeElement('#create-account_form');
        $I->fillField(['css' => '#email'], 'pub@thirtybees.com');
        $I->fillField(['css' => '#passwd'], '123456789');
        $I->click('#SubmitLogin');
        $I->see('Order history');
        $I->withoutErrors();
    }

    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function testMyAccountPage(FunctionalTester $I)
    {
        $I->amLoggedInToFrontOffice();
        $I->amOnPage('index.php?controller=my-account');
        $I->see('My Account');
        $I->see('Order history and details');
        $I->see('My credit slips');
        $I->see('My Addresses');
        $I->see('My Personal Information');
        $I->withoutErrors();
    }

    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function testOrderHistoryPage(FunctionalTester $I)
    {
        $I->amLoggedInToFrontOffice();
        $I->amOnPage('index.php?controller=history');
        $I->see('Order history');
        $I->see("Awaiting bank wire payment");
        $I->see("KHWLILZLL");
        $I->see("Reorder");
        $I->withoutErrors();
    }

    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function testMyOrderSlipPage(FunctionalTester $I)
    {
        $I->amLoggedInToFrontOffice();
        $I->amOnPage('index.php?controller=order-slip');
        $I->see("Credit Slips");
        $I->withoutErrors();
    }

    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function testMyAddressesPage(FunctionalTester $I)
    {
        $I->amLoggedInToFrontOffice();
        $I->amOnPage('index.php?controller=addresses');
        $I->see("My Addresses");
        $I->see("Main street 2nd floor");
        $I->withoutErrors();
    }

    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function testIdentityPage(FunctionalTester $I)
    {
        $I->amLoggedInToFrontOffice();
        $I->amOnPage('index.php?controller=identity');
        $I->see("Your personal information");
        $I->see("First name");
        $I->withoutErrors();
    }
}
