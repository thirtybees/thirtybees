<?php
$I = new AcceptanceTester($scenario);
$I->am('Guest');
$I->wantTo('Buy a product');
$I->amOnPage('/index.php?controller=product&id_product=1');
$I->see('Add to cart');
$I->click('Add to cart');
$I->waitForElementVisible('.layer_cart_product');
$I->see('Product successfully added to your shopping cart');
$I->click('Proceed to checkout');

$I->click('Proceed to checkout');
$I->see('Authentication');
$I->fillField('#email_create', 'testbuyer@test.test');
$I->click('#SubmitCreate');

$I->waitForElementVisible('#account-creation_form');
$I->fillField('#customer_firstname', 'test');
$I->fillField('#customer_lastname', 'test');
$I->fillField('#passwd', 'testtest');
$I->click('Register');

$I->click('Proceed to checkout');
$I->fillField('#address1', 'Address 1');
$I->fillField('#city', 'City');
$I->fillField('#phone', '1234567890');
$I->selectOption('#id_state', 'Alabama');
$I->fillField('#postcode', '12345');
$I->click('Save');

$I->click('Proceed to checkout');

$I->checkOption('#cgv');
$I->click('Proceed to checkout');

$I->click('Pay by bank wire');

$I->click('I confirm my order');

$I->see('Please send us a bank wire');
