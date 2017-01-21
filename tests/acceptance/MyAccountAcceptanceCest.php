<?php


class MyAccountAcceptanceCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    public function createAccountFormIsVisible(AcceptanceTester $I)
    {
        $I->amOnPage('/index.php');
        $I->click('a.login');
        $I->seeElement('#create-account_form');
    }
}
