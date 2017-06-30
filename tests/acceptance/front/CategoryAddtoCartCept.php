<?php
$I = new AcceptanceTester($scenario);
$I->am('Visitor');
$I->wantTo('Add a product to my cart');
$I->resizeWindow(1920, 1080);
$I->amOnPage('/index.php');
$I->see('Women');
$I->click('Women');
$I->waitForElementVisible(['css' => '.category-banner']);

