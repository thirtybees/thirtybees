<?php
$I = new AcceptanceTester($scenario);
$I->am('Guest');
$I->wantTo('Buy a product');
$I->resizeWindow(1920, 1080);
$I->amOnPage('/index.php?controller=product&id_product=1');
$I->see('Add to cart');
$I->click('Add to cart');
$I->waitForElementVisible(['css' => '.layer_cart_product']);
$I->see('Product successfully added to your shopping cart');
$I->click('Proceed to checkout');

$I->click('Proceed to checkout');
$I->see('Authentication');
$I->fillField('#email_create', 'testbuyer@test.test');
$I->click('#SubmitCreate');

$I->waitForElementVisible('#account-creation_form');
$I->fillField(['css' => '#customer_firstname'], 'test');
$I->fillField(['css' => '#customer_lastname'], 'test');
$I->fillField(['css' => '#passwd'], 'testtest');
$I->click('Register');

$I->click('Proceed to checkout');
$I->fillField(['css' => '#address1'], 'Address 1');
$I->fillField(['css' => '#city'], 'City');
$I->fillField(['css' => '#phone'], '1234567890');
$I->selectOption(['css' => '#id_state'], 'Alabama');
$I->fillField(['css' => '#postcode'], '12345');
$I->click('Save');

$I->click('Proceed to checkout');

$I->checkOption(['css' => '#cgv']);
$I->click('Proceed to checkout');

$I->click('Pay by bank wire');

$I->click('I confirm my order');

$I->see('Please send us a bank wire');
