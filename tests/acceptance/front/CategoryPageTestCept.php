<?php
$I = new AcceptanceTester($scenario);
$I->am('Visitor');
$I->wantTo('Make sure the category page works');
$I->resizeWindow(1920, 1080);
$I->amOnPage('/index.php?id_category=3&controller=category&id_lang=1');
$I->see('Coffee');
