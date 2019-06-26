<?php
$I = new AcceptanceTester($scenario);
$I->am('Guest');
$I->wantTo('Buy a product');
$I->resizeWindow(1920, 1080);
$I->amOnPage('/index.php?id_product=1&controller=product');
$I->see('Add to cart');
$I->withoutErrors();

$I->click('Add to cart');
$I->waitForElementVisible(['css' => '.layer_cart_product']);
$I->see('Product successfully added to your shopping cart');
$I->withoutErrors();

$I->click('Proceed to checkout');
$I->withoutErrors();

$I->click('Proceed to checkout');
$I->see('Authentication');
$I->withoutErrors();

$I->fillField('#email_create', 'testbuyer@test.test');
$I->click('#SubmitCreate');

$I->waitForElementVisible('#account-creation_form');
$I->fillField(['css' => '#customer_firstname'], 'test');
$I->fillField(['css' => '#customer_lastname'], 'test');
$I->fillField(['css' => '#passwd'], 'testtest');
$I->click('Register');
$I->withoutErrors();

$I->click('Proceed to checkout');
$I->withoutErrors();

$I->fillField(['css' => '#address1'], 'Address 1');
$I->fillField(['css' => '#city'], 'City');
$I->fillField(['css' => '#phone'], '1234567890');
$I->selectOption(['css' => '#id_state'], 'Alabama');
$I->fillField(['css' => '#postcode'], '12345');
$I->click('Save');
$I->withoutErrors();

$I->click('Proceed to checkout');
$I->withoutErrors();

$I->click('label[for="cgv"]');
$I->click('Proceed to checkout');
$I->withoutErrors();

$I->click('Pay by bank wire');
$I->withoutErrors();

$I->click('I confirm my order');
$I->see('Please send us a bank wire');
$I->withoutErrors();
