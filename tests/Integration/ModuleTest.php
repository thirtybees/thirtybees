<?php

namespace Tests\Integration;

use Codeception\Test\Unit;
use Context;
use Employee;
use Module;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tests\Support\UnitTester;

class ModuleTest extends Unit
{

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function _before()
    {
        parent::setUpBeforeClass();
        Module::updateTranslationsAfterInstall(false);

        // Some modules create a back office menu item (tab), which needs an
        // employee to be defined. Employee with ID 1 is the one created at
        // installation time.
        $employee = new Employee(1);
        Context::getContext()->employee = $employee;
    }

    /**
     * @return array
     */
    public function listModulesOnDisk()
    {
        $modules = array();
        foreach (scandir(_PS_MODULE_DIR_) as $entry) {
            if ($entry[0] !== '.') {
                if (file_exists(_PS_MODULE_DIR_.$entry.DIRECTORY_SEPARATOR.$entry.'.php')) {
                    $modules[$entry] = [ $entry ];
                }
            }
        }

        return $modules;
    }

    /**
     * @dataProvider listModulesOnDisk
     * @group slow
     *
     * @param string $moduleName
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testInstallationAndUninstallation($moduleName)
    {
        $module = Module::getInstanceByName($moduleName);

        if (Module::isInstalled($moduleName)) {
            $this->assertTrue((bool)$module->uninstall(), 'Module uninstall failed : '.$moduleName);
            $this->assertTrue((bool)$module->install(), 'Module install failed : '.$moduleName);
        } else {
            $this->assertTrue((bool)$module->install(), 'Module install failed : '.$moduleName);
            $this->assertTrue((bool)$module->uninstall(), 'Module uninstall failed : '.$moduleName);
        }
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function testValidModuleNameIsEnabled()
    {
        $this->assertTrue(Module::isEnabled("coreupdater"));
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function testValidModuleNameGetModuleId() {
        $this->assertTrue(!!Module::getModuleIdByName("coreupdater"));
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function testBackwardCompatibilityModuleNameIsEnabled()
    {
        $this->assertTrue(Module::isEnabled("CoreUpdater"));
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function testBackwardCompatibilityModuleNameGetModuleId() {
        $this->assertTrue(!!Module::getModuleIdByName("CoreUpdater"));
    }

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function testMultipleInstantiation() {
        $this->assertTrue(!!Module::getInstanceByName("coreupdater"));
        $this->assertTrue(!!Module::getInstanceByName("CoreUpdater"));
    }

}
