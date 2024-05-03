<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Tests\Unit;

use Codeception\Test\Unit;
use Exception;
use Module;
use Tests\Support\UnitTester;

/**
 * Mock class
 */
class TestModule extends Module
{
}

class ModuleUnitTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @return void
     * @throws Exception
     */
    public function testCheckCompliancyPS16()
    {
        $mod = new TestModule();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => '1.6.1.999'];
        $this->assertEquals(true, $mod->checkCompliancy());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCheckCompliancyPS16OpenMax()
    {
        $mod = new TestModule();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => _PS_VERSION_];
        $this->assertEquals(true, $mod->checkCompliancy());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCheckCompliancyPS1_6_1_19()
    {
        $mod = new TestModule();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => '1.6.1.19'];
        $this->assertEquals(false, $mod->checkCompliancy());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCheckCompliancyPS1_6_1_20()
    {
        $mod = new TestModule();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => '1.6.1.20'];
        $this->assertEquals(true, $mod->checkCompliancy());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCheckCompliancyPS1_6_1_99()
    {
        $mod = new TestModule();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => '1.6.1.99'];
        $this->assertEquals(true, $mod->checkCompliancy());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCheckCompliancyPS17()
    {
        $mod = new TestModule();
        $mod->ps_versions_compliancy = [ 'min' => '1.7', 'max' => '1.7.1.999'];
        $this->assertEquals(false, $mod->checkCompliancy());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCheckCompliancyPS17OpenMax()
    {
        $mod = new TestModule();
        $mod->ps_versions_compliancy = [ 'min' => '1.7', 'max' => _PS_VERSION_];
        $this->assertEquals(false, $mod->checkCompliancy());
    }

}
