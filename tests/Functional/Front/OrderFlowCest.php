<?php

namespace Functional\Front;

use Tests\Support\FunctionalTester;

class OrderFlowCest
{
    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    public function testOrderFlow(FunctionalTester $I)
    {
        $I->amOnPage('/index.php');
        $I->withoutErrors();;
        $I->click('Candle');
        $I->see('19,80 €');
        $I->see('Add to cart');
        $I->withoutErrors();;
        $I->click('Add to cart');
        $I->see(' Product successfully added to your shopping cart');
        $I->withoutErrors();;
        $I->click('Proceed to checkout');
        $I->see('Shopping-cart summary');
        $I->withoutErrors();;
        $I->click(['css' => 'a.standard-checkout']);
        $I->seeElement(['css' => '#SubmitLogin']);
        $I->withoutErrors();;
        $I->fillField(['css' => '#email'], 'pub@thirtybees.com');
        $I->fillField(['css' => '#passwd'], '123456789');
        $I->click('#SubmitLogin');
        $I->see('John DOE');
        $I->withoutErrors();;
        $I->click(['css' => 'button[name="processAddress"]']);
        $I->see('Choose a shipping option for this address');
        $I->withoutErrors();;
        $I->checkOption(['css' => '#cgv']);
        $I->click(['css' => 'button[name="processCarrier"]']);
        $I->see('Please choose your payment method');
        $I->withoutErrors();;
        $I->click(['css' => 'a.bankwire']);
        $I->see('You have chosen to pay by bank wire. Here is a short summary of your order:');
        $I->withoutErrors();;
        $I->click('I confirm my order');
        $I->see('Your order on thirtybees is complete.');
        $I->withoutErrors();
    }
}
