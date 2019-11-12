<?php


class ConfigurationCoreTest extends \Codeception\Test\Unit
{

    public function updateEscapingData()
    {
        return [
            [ false, 'simple string' , 'simple string' ],
            [ false, "string with ' apostrophe", "string with ' apostrophe"],
            [ false, 'string with <a>html</a> tags', 'string with html tags'],
            [ false, 'html &gt; entities > test', 'html &gt; entities > test'],
            [ false, '{ "json": true }', '{ "json": true }' ],
            [ false, "multi\nline\ntext", "multi\nline\ntext" ],
            //
            [ true, 'simple string' , 'simple string' ],
            [ true, "string with ' apostrophe", "string with ' apostrophe"],
            [ true, 'string with <a>html</a> tags', 'string with <a>html</a> tags'],
            [ true, 'html &gt; entities > test', 'html &gt; entities &gt; test'],
            [ true, '{ "json": true }', '{ "json": true }' ],
            [ true, "multi\nline\ntext", "multi\nline\ntext" ],
        ];
    }

    /**
     * This tests verifies that values in configuration cache are properly updated
     * during Configuration::updateValue method
     *
     * @dataProvider updateEscapingData
     */
    public function testUpdateValueCachedData($allowHtml, $input, $expected)
    {
        $key = 'TEST__ConfigurationTest_testUpdateValue';

        // delete data
        Configuration::deleteByName($key);
        static::assertEquals(Configuration::get($key), false, "Key does not exists yet");

        // update value
        Configuration::updateValue($key, $input, $allowHtml);

        // test that values in cache matches expectation
        static::assertEquals($expected, Configuration::get($key), "Value matches immediately after update");
    }


    /**
     * This tests verifies that data stored into database during Configuration::updateValue contains
     * expected value
     *
     * @dataProvider updateEscapingData
     */
    public function testUpdateValueStoredData($allowHtml, $input, $expected)
    {
        $key = 'TEST__ConfigurationTest_testUpdateValue';

        // delete data
        Configuration::deleteByName($key);
        static::assertEquals(Configuration::get($key), false, "Key does not exists yet");

        // update value
        Configuration::updateValue($key, $input, $allowHtml);

        // flush configuration cache --> force reload from database
        Configuration::clearConfigurationCacheForTesting();

        // test that value read from db matches expectation
        static::assertEquals($expected, Configuration::get($key), "Value matches when re-loaded from db");
    }
}
