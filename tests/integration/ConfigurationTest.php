<?php


class ConfigurationTest extends \Codeception\Test\Unit
{
    public function testUpdateEscapingTest()
    {
        $key = 'TEST__ConfigurationTest_testUpdateValue';
        $value = '{ "json": true }';
        Configuration::deleteByName($key);
        static::assertEquals(Configuration::get($key), false, "Key does not exists yet");
        Configuration::updateValue($key, $value);
        static::assertEquals(Configuration::get($key), $value, "Value matches immediately after update");
        Configuration::clearConfigurationCacheForTesting();
        static::assertEquals(Configuration::get($key), $value, "Value matches when re-loaded from db");
    }
}
