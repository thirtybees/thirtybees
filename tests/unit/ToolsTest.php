<?php


class ToolsTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * Preparation
     *
     * @return void
     */
    protected function _before()
    {
        $_GET = [];
        $_POST = [];
    }

    /**
     * Cleanup
     *
     * @return void
     */
    protected function _after()
    {
    }

    /**
     * Default getValue test
     *
     * @return void
     */
    public function testGetValueBaseCase()
    {
        $_GET = [
            'hello' => 'world',
        ];

        $this->assertEquals('world', Tools::getValue('hello'));
    }

    /**
     * Test non existing value
     *
     * @return void
     */
    public function testGetValueDefaultValueIsFalse()
    {
        $this->assertEquals(false, Tools::getValue('hello'));
    }

    /**
     * test vetValue with default
     *
     * @return void
     */
    public function testGetValueUsesDefaultValue()
    {
        $this->assertEquals('I AM DEFAULT', Tools::getValue('hello', 'I AM DEFAULT'));
    }

    /**
     * Test priority
     *
     * @return void
     */
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

    /**
     * Test keys
     *
     * @return void
     */
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

    /**
     * Data provider
     *
     * @return array[]
     */
    public function testGetValueStripsNullCharsFromReturnedStringsExamples()
    {
        return [
            ["\0", ''],
            ["haxx\0r", 'haxxr'],
            ["haxx\0\0\0r", 'haxxr'],
        ];
    }

    /**
     * Test sanitation
     *
     * @param string $rawString
     * @param string $cleanedString
     *
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

    /**
     * Data provider for testSpreadAmount
     *
     * @return array[]
     */
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
     * Test spreadAmount
     *
     * @param array $expectedRows
     * @param float $amount
     * @param int $precision
     * @param array $rows
     * @param string $column
     *
     * @dataProvider testSpreadAmountExamples
     */
    public function testSpreadAmount($expectedRows, $amount, $precision, $rows, $column)
    {
        Tools::spreadAmount($amount, $precision, $rows, $column);
        $this->assertEquals(array_values($expectedRows), array_values($rows));
    }

    /**
     * Data provider for testParseNumber test
     *
     * @return array
     */
    public function parseNumberData()
    {
        return [
            // valid cases - float input
            [1.5, 1.5],
            [5, 5.0],
            ["1", 1.0],
            ["1.1", 1.1],
            ["92233720368547758011", 92233720368547758011.0],

            // rounding
            ["1.111111111", 1.111111],
            ["1.555555555", 1.555556],
            [1.555555555, 1.555556],
            ["9223.3720368547758011", 9223.372037],

            // invalid inputs
            [[], 0.0],
            [ [0.1, "1.2"], 0.0],
            [null, 0.0],
            [false, 0.0],
            ['', 0.0],
            [' ', 0.0],
            ['invalid input', 0.0],
            ['$', 0.0],
            ['.', 0.0],
            [',', 0.0],

            // invalid separator combinations
            ["22'33,44.123", 0.0],
            ["99.33,44.123", 0.0],

            // corrected inputs
            ["9,", 9.0],
            ["8.", 8.0],
            [".7", 0.7],
            [",6", 0.6],
            ["1,2", 1.2],
            ["1,2", 1.2],
            ["$1,3", 1.3],
            ["1,4 USD", 1.4],
            ["USD 1,41", 1.41],
            ["USD1,42", 1.42],
            ["USD 1.51", 1.51],
            ["1.52USD", 1.52],
            ["1.53 USD", 1.53],
            ["1,666666666 €", 1.666667],
            ["€ 1.111111111123", 1.111111],
            ["1'000,001 EUR", 1000.001],
            ["1 000.002$", 1000.002],
            ["1,000.003$", 1000.003],
            ["1.000,004 EURO", 1000.004],
            ["1.000.555,005 EURO", 1000555.005],
            ["2,000,555.005 EURO", 2000555.005],
            ["3,000,000", 3000000.0],
            ["3,000,001.0", 3000001.0],
            ["4.000.000", 4000000.0],
            ["4.000.001,0", 4000001.0],
            ["1.234.567'89", 1234567.89],
            ["1'234'567'89.888888888", 123456789.888889]
        ];
    }

    /**
     * Tests parsePrice method
     *
     * @param string $input
     * @param float $expectedValue
     *
     * @dataProvider parseNumberData
     */
    public function testParseNumber($input, $expectedValue)
    {
        $actualValue = Tools::parseNumber($input);
        $this->assertTrue(is_float($actualValue));
        $this->assertEquals($expectedValue, $actualValue, "Failed to parse input string: ".print_r($input, true));
    }

    /**
     * Data provider for testParseNumber test
     *
     * @return array
     */
    public function linkRewriteData()
    {
        return [
            [false, "Product Title", "product-title"],
            [true, "Product Title", "product-title"],
            [false, "Příliš Žlutoučký Kůň", "prilis-zlutoucky-kun"],
            [true, "Příliš Žlutoučký Kůň", "příliš-žlutoučký-kůň"],
            [false, "מיטת נוער משולשת + 2 מגירות דגם פרובנס", "-2-"],
            [true, "מיטת נוער משולשת + 2 מגירות דגם פרובנס", "מיטת-נוער-משולשת-2-מגירות-דגם-פרובנס"],
        ];
    }

    /**
     * @param bool $allowAccentedCharacters
     * @param string $productTitle
     * @param string $expectedLinkRewrite
     * @return void
     *
     * @dataProvider linkRewriteData
     */
    public function testGenerateLinkRewrite($allowAccentedCharacters, $productTitle, $expectedLinkRewrite)
    {
        $actualLinkRewrite = Tools::generateLinkRewrite($productTitle, $allowAccentedCharacters);
        $this->assertEquals($expectedLinkRewrite, $actualLinkRewrite);
    }
}
