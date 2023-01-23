<?php

namespace Tests\Functional\Back;

use Codeception\Example;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tab;
use Tests\Support\FunctionalTester;

class AdminControllerWalkerCest
{
    /**
     * This tests walks through all back office controller default pages
     * and ensures that no error is thrown
     *
     * @param FunctionalTester $I
     * @param Example $example
     *
     * @return void
     *
     * @dataProvider getControllerUrls
     */
    public function testController(FunctionalTester $I, Example $example)
    {
        $controller = $example['controller'];
        $token = $I->getAdminToken($controller);
        $I->amLoggedInToBackOffice();
        $I->see('Dashboard');
        $I->amOnPage("/admin-dev/index.php?controller=$controller&token=$token");
        $I->see('Dashboard');
        $I->withoutErrors();

        // check subpages
        foreach ($I->grabMultiple('.panel-heading a', 'href') as $href) {
            if (strpos($href, 'index.php') === 0) {
                if (strpos($href, '&export')) {
                    $I->amOnPage($href);
                    $I->seeResponseCodeIs(200);
                } else {
                    $I->amOnPage($href);
                    $I->see('Dashboard');
                    $I->withoutErrors();
                }
            }
        }
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
                $urls[] = [ 'controller' => $tab ];
            }
        }
        return $urls;
    }


}
