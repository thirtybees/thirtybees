<?php
$I = new AcceptanceTester($scenario);
$I->am('Visitor');
$I->wantTo('Add a product to my cart');
$I->resizeWindow(1920, 1080);
$I->amOnPage('/index.php');
$I->see('Women');
$I->click('Women');

$I->see('Women Subcategories');
$I->click('Add to cart');
$I->waitForElementVisible(['css' => '.layer_cart_product']);
$I->see('Product successfully added to your shopping cart');

$I->click('Tops');

