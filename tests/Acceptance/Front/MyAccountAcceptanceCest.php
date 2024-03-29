<?php

namespace Tests\Acceptance\Front;

use Tests\Support\AcceptanceTester;

class MyAccountAcceptanceCest
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
     * @param AcceptanceTester $I
     *
     * @return void
     */
    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @param AcceptanceTester $I
     *
     * @return void
     */
    public function createAccountFormIsVisible(AcceptanceTester $I)
    {
        $I->amOnPage('/index.php');
        $I->click(['css' => '.login']);
        $I->seeElement(['css' => '#create-account_form']);
        $I->withoutErrors();
    }
}
