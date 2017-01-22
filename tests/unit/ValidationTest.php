<?php

class ValidateTest extends \Codeception\TestCase\Test
{
    public $tester;

    public function isIp2LongDataProvider()
    {
        return [
            [false, 'toto'],
            [true, '123']
        ];
    }

    /**
     * @dataProvider isIp2LongDataProvider
     */
    public function testIsIp2Long($expected, $input)
    {
        $this->assertEquals($expected, Validate::isIp2Long($input));
    }

    public function testIsAnything()
    {
        $this->assertTrue(Validate::isAnything());
    }

    public function isEmailDataProvider()
    {
        return [
            [true, 'john.doe@prestashop.com'],
            [true, 'john.doe+alias@prestshop.com'],
            [true, 'john.doe+alias@pr.e.sta.shop.com'],
            [true, 'j@p.com'],
            [true, 'john#doe@prestashop.com'],
            [false, ''],
            [false, 'john.doe@prestashop,com'],
            [false, 'john.doe@prestashop'],
            [false, 123456789],
            [false, false],
        ];
    }

    /**
     * @dataProvider isEmailDataProvider
     */
    public function testIsEmail($expected, $input)
    {
        $this->assertSame($expected, Validate::isEmail($input));
    }

    public function isMd5DataProvider()
    {
        return [
            [1, md5('SomeRandomString')],
            [0, ''],
            [0, sha1('AnotherRandomString')],
            [0, substr(md5('AnotherRandomString'), 0, 31)],
            [0, 123],
            [0, false],
        ];
    }

    /**
     * @dataProvider isMd5DataProvider
     */
    public function testIsMd5($expected, $input)
    {
        $this->assertSame($expected, Validate::isMd5($input));
    }

    public function isSha1DataProvider()
    {
        return [
            [1, sha1('SomeRandomString')],
            [0, ''],
            [0, md5('AnotherRandomString')],
            [0, substr(sha1('AnotherRandomString'), 0, 39)],
            [0, 123],
            [0, false],
        ];
    }


    /**
     * @dataProvider isSha1DataProvider
     */
    public function testIsSha1($expected, $input)
    {
        $this->assertSame($expected, Validate::isSha1($input));
    }

    public function isFloatDataProvider()
    {
        return array_merge(
            $this->trueFloatDataProvider(),
            [
                [true, -12.2151],
                [true, -12, 2151],
                [true, '-12.2151'],
                [false, ''],
                [false, 'A'],
                [false, null],
            ]
        );
    }

    /**
     * @dataProvider isFloatDataProvider
     */
    public function testIsFloat($expected, $input)
    {
        $this->assertSame($expected, Validate::isFloat($input));
    }

    public function isUnsignedFloatDataProvider()
    {
        return array_merge(
            $this->trueFloatDataProvider(),
            [
                [false, -12.2151],
                [false, -12, 2151],
                [false, '-12.2151'],
                [false, ''],
                [false, 'A'],
                [false, null],
            ]
        );
    }

    /**
     * @dataProvider isUnsignedFloatDataProvider
     */
    public function testIsUnsignedFloat($expected, $input)
    {
        $this->assertSame($expected, Validate::isUnsignedFloat($input));
    }

    public function trueFloatDataProvider()
    {
        return [
            [true, 12],
            [true, 12.2151],
            [true, 12, 2151],
            [true, '12.2151'],
        ];
    }

    public function isOptFloatDataProvider()
    {
        return array_merge(
            $this->trueFloatDataProvider(),
            [
                [true, -12.2151],
                [true, null],
                [true, ''],
            ]
        );
    }

    /**
     * @depends testIsFloat
     * @dataProvider isOptFloatDataProvider
     */
    public function testIsOptFloat($expected, $input)
    {
        $this->assertSame($expected, Validate::isOptFloat($input));
    }

    public function isBirthDateProvider()
    {
        return [
            [true, '1991-04-19'],
            [true, '2015-03-22'],
            [true, '1945-07-25'],
            [false, '2020-03-19'],
            [false, '1991-03-33'],
            [false, '1991-15-19'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isBirthDateProvider
     */
    public function testIsBirthdate($expected, $input)
    {
        $this->assertSame($expected, Validate::isBirthDate($input));
    }
}
