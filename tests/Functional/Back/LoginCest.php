<?php

namespace Tests\Functional\Front;

use Tests\Support\FunctionalTester;

class LoginCest
{
    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function testLogin(FunctionalTester $I)
    {
        $I->amLoggedInToBackOffice();
        $I->see('Dashboard');
    }

    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function testInvalidPassword(FunctionalTester $I)
    {
        $I->amOnPage('/admin-dev/index.php?controller=AdminLogin');
        $I->fillField('#email', 'test@thirty.bees');
        $I->fillField('#passwd', 'invalid');
        $I->click('submitLogin');
        $I->see('Invalid password.');
    }
}
