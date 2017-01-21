<?php


class MyAccountCest
{
    public function _before(FunctionalTester $I)
    {
    }

    public function _after(FunctionalTester $I)
    {
    }

    public function createAccountFormIsVisible(FunctionalTester $I)
    {
        $I->amOnPage('/index.php');
        $I->click('a.login');
        $I->seeElement('#create-account_form');
    }
}
