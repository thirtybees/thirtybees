<?php

namespace Integration;

use AdminDashboardController;
use AdminDashboardControllerCore;
use Codeception\Test\Unit;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tab;
use Tests\Support\UnitTester;

class TabTest extends Unit
{

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @return array[]
     */
    protected function tabIdsDataProvider()
    {
        return [
            [1, 'AdminDashboard'],
            [1, 'ADMINDASHBOARD'],
            [1, 'admindashboard'],
            [1, 'admindashboardController'],
            [1, 'AdminDashboardControllerCore'],
            [1, AdminDashboardControllerCore::class],
            [1, AdminDashboardController::class],
            [false, 'nadmindashboardController'],
            [false, null],
            [false, ''],
            [false, '1'],
        ];
    }

    /**
     *
     * @param $expected
     * @param $classname
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @dataProvider tabIdsDataProvider
     */
    public function testGetIdFromClassName($expected, $classname)
    {
        $actual = Tab::getIdFromClassName($classname);
        static::assertEquals($expected, $actual, "Failed to resolve tab id from classname '$classname'");
    }
}
