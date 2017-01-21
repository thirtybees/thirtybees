<?php


class ToolsTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_GET = [];
        $_POST = [];
    }

    protected function _after()
    {
    }

    public function testGetValueBaseCase()
    {
        $_GET = [
            'hello' => 'world',
        ];

        $this->assertEquals('world', Tools::getValue('hello'));
    }

    public function testGetValueDefaultValueIsFalse()
    {
        $this->assertEquals(false, Tools::getValue('hello'));
    }

    public function testGetValueUsesDefaultValue()
    {
        $this->assertEquals('I AM DEFAULT', Tools::getValue('hello', 'I AM DEFAULT'));
    }

    public function testGetValuePrefersPost()
    {
        $_GET = [
            'hello' => 'world',
        ];
        $_POST = [
            'hello' => 'cruel world',
        ];

        $this->assertEquals('cruel world', Tools::getValue('hello'));
    }

    public function testGetValueAcceptsOnlyTruthyStringsAsKeys()
    {
        $_GET = [
            '' => true,
            ' ' => true,
            null => true,
        ];

        $this->assertEquals(false, Tools::getValue('', true));
        $this->assertEquals(true, Tools::getValue(' '));
        $this->assertEquals(false, Tools::getValue(null, true));
    }

    public function testGetValueStripsNullCharsFromReturnedStringsExamples()
    {
        return [
            ["\0", ''],
            ["haxx\0r", 'haxxr'],
            ["haxx\0\0\0r", 'haxxr'],
        ];
    }

    /**
     * @dataProvider testGetValueStripsNullCharsFromReturnedStringsExamples
     */
    public function testGetValueStripsNullCharsFromReturnedStrings($rawString, $cleanedString)
    {
        /**
         * Check it cleans values stored in POST
         */
        $_GET = [
            'rawString' => $rawString
        ];
        $this->assertEquals($cleanedString, Tools::getValue('rawString'));

        /**
         * Check it cleans values stored in GET
         */
        $_GET = [];
        $_POST = [
            'rawString' => $rawString,
        ];
        $this->assertEquals($cleanedString, Tools::getValue('rawString'));

        /**
         * Check it cleans default values too
         */
        $_GET = [];
        $_POST = [];
        $this->assertEquals($cleanedString, Tools::getValue('NON EXISTING KEY', $rawString));
    }

    public function testSpreadAmountExamples()
    {
        return [
            [
                // base case
                [['a' => 2], ['a' => 1]], // expected result
                1, 0,                                     // amount and precision
                [['a' => 1], ['a' => 1]], // source rows
                'a'                                         // sort column
            ],
            [
                // check with 1 decimal
                [['a' => 1.5], ['a' => 1.5]],
                1, 1,
                [['a' => 1], ['a' => 1]],
                'a',
            ],
            [
                // 2 decimals, but only one really needed
                [['a' => 1.5], ['a' => 1.5]],
                1, 2,
                [['a' => 1], ['a' => 1]],
                'a',
            ],
            [
                // check that the biggest "a" gets the adjustment
                [['a' => 3], ['a' => 1]],
                1, 0,
                [['a' => 1], ['a' => 2]],
                'a',
            ],
            [
                // check it works with amount > count($rows)
                [['a' => 4], ['a' => 2]],
                3, 0,
                [['a' => 1], ['a' => 2]],
                'a',
            ],
            [
                // 2 decimals
                [['a' => 2.01], ['a' => 1]],
                0.01, 2,
                [['a' => 1], ['a' => 2]],
                'a',
            ],
            [
                // 2 decimals, equal level of adjustment
                [['a' => 2.01], ['a' => 1.01]],
                0.02, 2,
                [['a' => 1], ['a' => 2]],
                'a',
            ],
            [
                // 2 decimals, different levels of adjustmnt
                [['a' => 2.02], ['a' => 1.01]],
                0.03, 2,
                [['a' => 1], ['a' => 2]],
                'a',
            ],
            [
                // check associative arrays are OK too
                [['a' => 2.01], ['a' => 1.01]],
                0.02, 2,
                ['z' => ['a' => 1], 'x' => ['a' => 2]],
                'a',
            ],
            [
                // check amount is rounded if it needs more precision than asked for
                [['a' => 2.02], ['a' => 1.01]],
                0.025, 2,
                [['a' => 1], ['a' => 2]],
                'a',
            ],
            [
                [['a' => 7.69], ['a' => 4.09], ['a' => 1.8]],
                -0.32, 2,
                [['a' => 7.8], ['a' => 4.2], ['a' => 1.9]],
                'a',
            ],
        ];
    }

    /**
     * @dataProvider testSpreadAmountExamples
     */
    public function testSpreadAmount($expectedRows, $amount, $precision, $rows, $column)
    {
        Tools::spreadAmount($amount, $precision, $rows, $column);
        $this->assertEquals(array_values($expectedRows), array_values($rows));
    }
}
