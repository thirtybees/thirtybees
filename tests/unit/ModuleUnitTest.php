<?php
/**
 * Copyright (C) 2017-2018 thirty bees
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
 * @copyright 2017-2018 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

require_once __DIR__.'/support/TestModule.php';

use AspectMock\Test as test;

class ModuleUnitTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testCheckCompliancyPS16()
    {
        $mod = test::double('TestModule')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => '1.6.1.999'];
        $this->assertEquals(true, $mod->checkCompliancy());
    }

    public function testCheckCompliancyPS16OpenMax()
    {
        $mod = test::double('TestModule')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => _PS_VERSION_];
        $this->assertEquals(true, $mod->checkCompliancy());
    }

    public function testCheckCompliancyPS1_6_1_19()
    {
        $mod = test::double('TestModule')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => '1.6.1.19'];
        $this->assertEquals(false, $mod->checkCompliancy());
    }

    public function testCheckCompliancyPS1_6_1_20()
    {
        $mod = test::double('TestModule')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => '1.6.1.20'];
        $this->assertEquals(true, $mod->checkCompliancy());
    }

    public function testCheckCompliancyPS1_6_1_99()
    {
        $mod = test::double('TestModule')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => '1.6.1.99'];
        $this->assertEquals(true, $mod->checkCompliancy());
    }

    public function testCheckCompliancyPS17()
    {
        $mod = test::double('TestModule')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.7', 'max' => '1.7.1.999'];
        $this->assertEquals(false, $mod->checkCompliancy());
    }

    public function testCheckCompliancyPS17OpenMax()
    {
        $mod = test::double('TestModule')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.7', 'max' => _PS_VERSION_];
        $this->assertEquals(false, $mod->checkCompliancy());
    }

}
