<?php

namespace Tests\Functional\Front;

use Codeception\Example;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tab;
use Tests\Support\FunctionalTester;
use Tools;

class AdminControllerWalkerCest
{
    /**
     * @param FunctionalTester $I
     * @param Example $example
     *
     * @return void
     *
     * @dataProvider getControllerUrls
     */
    public function testController(FunctionalTester $I, Example $example)
    {
        $I->amLoggedInToBackOffice();
        $I->see('Dashboard');
        $I->amOnPage('/admin-dev/index.php?controller='.$example['controller'].'&token=' . $example['token']);
        $I->see('Dashboard');
        $I->withoutErrors();
    }

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getControllerUrls()
    {
        $tabs = array_column(Tab::getTabs(1), 'class_name');
        $urls = [];
        foreach ($tabs as $tab) {
            if ($tab != 'AdminLogin') {
                $token = Tools::getAdminToken($tab . (int)Tab::getIdFromClassName($tab) . '1');
                $urls[] = [
                    'controller' => $tab,
                    'token' => $token,
                ];
            }
        }
        return $urls;
    }


}
