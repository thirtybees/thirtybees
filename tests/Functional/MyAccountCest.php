<?php

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class MyAccountCest
{
    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function _before(FunctionalTester $I)
    {
    }

    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function _after(FunctionalTester $I)
    {
    }

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
    }
}
