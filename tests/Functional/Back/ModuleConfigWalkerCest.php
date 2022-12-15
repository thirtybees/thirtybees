<?php

namespace Tests\Functional\Back;

use Codeception\Example;
use Module;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tests\Support\FunctionalTester;

class ModuleConfigWalkerCest
{
    /**
     * This tests goes through all installed modules configuration pages
     * and ensures that no error is thrown
     *
     * @param FunctionalTester $I
     * @param Example $example
     *
     * @return void
     *
     * @dataProvider getModuleConfigData
     */
    public function testModuleConfigPage(FunctionalTester $I, Example $example)
    {
        $moduleName = $example['module'];
        $token = $I->getAdminToken('AdminModules');
        $I->amLoggedInToBackOffice();
        $I->see('Dashboard');
        $I->amOnPage("/admin-dev/index.php?controller=AdminModules&token=$token&configure=$moduleName");
        $I->see('Dashboard');
        $I->withoutErrors();
    }

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getModuleConfigData()
    {
        $modules = [];
        foreach (Module::getModulesInstalled() as $module) {
            $moduleName = $module['name'];
            $instance = Module::getInstanceByName($moduleName);
            if (method_exists($instance, 'getContent')) {
                $modules[] = [
                    'module' => $moduleName
                ];
            }
        }
        return $modules;
    }


}
