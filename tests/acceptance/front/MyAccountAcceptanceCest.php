<?php


class MyAccountAcceptanceCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->resizeWindow(1920, 1080);
    }

    public function _after(AcceptanceTester $I)
    {
    }

    public function createAccountFormIsVisible(AcceptanceTester $I)
    {
        $I->amOnPage('/index.php');
        $I->click(['css' => '.login']);
        $I->seeElement(['css' => '#create-account_form']);
    }
}
