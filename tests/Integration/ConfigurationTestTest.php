<?php

namespace Tests\Integration;

use Codeception\Test\Unit;
use ConfigurationTest;
use Tests\Support\UnitTester;

class ConfigurationTestTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @return void
     */
    public function testCheck()
    {
        $this->assertEquals(['PdoMysql' => 'ok'], ConfigurationTest::check(['PdoMysql' => false]));
    }

    /**
     * @return void
     */
    public function testRun()
    {
        $this->assertEquals('ok', ConfigurationTest::run('PdoMysql'));
    }

    /**
     * @return array
     */
    public function checkProvider()
    {
        $tests = [];
        foreach (ConfigurationTest::getDefaultTests() as $test => $argument) {
            $tests["Test " . $test] = [$test, $argument];
        }
        foreach (ConfigurationTest::getDefaultTestsOp() as $test => $argument) {
            $tests["Test " . $test] = [$test, $argument];
        }
        return $tests;
    }

    /**
     * @dataProvider checkProvider
     *
     * @param string $test
     * @param mixed $arg
     */
    public function testTestsShouldBeOk($test, $arg)
    {
        $this->assertEquals("ok", ConfigurationTest::run($test, $arg));
    }
}
