<?php

use AspectMock\Test as test;

class ModuleUnitTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testCheckCompliancyPS16()
    {
        $mod = test::double('Module')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => '1.6.1.999'];
        $this->assertEquals(true, $mod->checkCompliancy());
    }

    public function testCheckCompliancyPS16OpenMax()
    {
        $mod = test::double('Module')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => _PS_VERSION_];
        $this->assertEquals(true, $mod->checkCompliancy());
    }

    public function testCheckCompliancyPS1_6_1_19()
    {
        $mod = test::double('Module')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => '1.6.1.19'];
        $this->assertEquals(false, $mod->checkCompliancy());
    }

    public function testCheckCompliancyPS1_6_1_20()
    {
        $mod = test::double('Module')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => '1.6.1.20'];
        $this->assertEquals(true, $mod->checkCompliancy());
    }

    public function testCheckCompliancyPS1_6_1_99()
    {
        $mod = test::double('Module')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.6', 'max' => '1.6.1.99'];
        $this->assertEquals(true, $mod->checkCompliancy());
    }

    public function testCheckCompliancyPS17()
    {
        $mod = test::double('Module')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.7', 'max' => '1.7.1.999'];
        $this->assertEquals(false, $mod->checkCompliancy());
    }

    public function testCheckCompliancyPS17OpenMax()
    {
        $mod = test::double('Module')->construct();
        $mod->ps_versions_compliancy = [ 'min' => '1.7', 'max' => _PS_VERSION_];
        $this->assertEquals(false, $mod->checkCompliancy());
    }

}
