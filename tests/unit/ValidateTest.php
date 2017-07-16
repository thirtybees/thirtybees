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
        $this->assertSame($expected, Validate::isIp2Long($input));
    }

    public function testIsAnything()
    {
        $this->assertTrue(Validate::isAnything());
    }

    public function isEmailDataProvider()
    {
        return [
            [true,  'john.doe@prestashop.com'],
            [true,  'john.doe+alias@prestshop.com'],
            [true,  'john.doe+alias@pr.e.sta.shop.com'],
            [true,  'j@p.com'],
            [true,  'john#doe@prestashop.com'],
            [false, ''],
            [false, 'john.doe@prestashop,com'],
            [true,  'john.doe@prestashop'],
            [false, 123456789],
            [false, false],
            [true,  'email@example.com'],
            [true,  'firstname.lastname@example.com'],
            [true,  'email@subdomain.example.com'],
            [true,  'firstname+lastname@example.com'],
            [true,  'email@123.123.123.123'],
            [true,  'email@[123.123.123.123]'],
            [true,  '"email"@example.com'],
            [true,  '1234567890@example.com'],
            [true,  'email@example-one.com'],
            [true,  '_______@example.com'],
            [true,  'email@example.name'],
            [true,  'email@example.museum'],
            [true,  'email@example.co.jp'],
            [true,  'firstname-lastname@example.com'],
            [false,  'much."more\ unusual"@example.com'],
            [false,  'very.unusual."@".unusual.com@example.com'],
            [false,  'very."(),:;<>[]".VERY."very@\\\\\\ \"very".unusual@strange.example.com'],
            [false, 'plainaddress'],
            [false, '#@%^%#$@#$@#.com'],
            [false, '@example.com'],
            [false, 'Joe Smith <email@example.com>'],
            [false, 'email.example.com'],
            [false, 'email@example@example.com'],
            [false, '.email@example.com'],
            [false, 'email.@example.com'],
            [false, 'email..email@example.com'],
            [true, 'email+email@example.com'],
            [false, 'あいうえお@example.com'],
            [true, 'email@example.com (Joe Smith)'],
            [true, 'email@example'],
            [true, 'email@-example.com'],
            [true, 'email@example.web'],
            [true, 'email@111.222.333.44444'],
            [false, 'email@example..com'],
            [false, 'Abc..123@example.com'],
            [false, '"(),:;<>[\]@example.com'],
            [false, 'just"not"right@example.com'],
            [false, 'this\ is\"really\"not\\\\allowed@example.com'],
        ];
    }

    /**
     * @dataProvider isEmailDataProvider
     */
    public function testIsEmail($expected, $input)
    {
        $this->assertSame($expected, Validate::isEmail($input));
    }

    public function isModuleUrlDataProvider()
    {
        return [
            [false, ''],
            [false, 'http://'],
            [false, 'https://'],
            [true,  'http://www.example.com/module.zip'],
            [false, 'http://www.example.com/module.zip0'],
            [true,  'http://www.example.com/module.zip'],
            [true,  'http://www.example.com/module.tgz'],
            [true,  'http://www.example.com/module.tar.gz'],
            [true,  'file:///var/www/module.zip'],
            [false, 'https:///var/www/module.zip'],
            [true,  'https://var/www/module.zip'],
            [true,  'https://module:module@www.example.com/module.zip'],
            [true,  'https://apikey@www.example.com/module.zip'],
            [true,  'ftp://test:test@ftp.example.com/pub/module.zip'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider  isModuleUrlDataProvider
     */
    public function testIsModuleUrl($expected, $input)
    {
        $this->assertSame($expected, Validate::isModuleUrl($input, $errors));
    }

    public function isMd5DataProvider()
    {
        return [
            [true,  md5('SomeRandomString')],
            [false, ''],
            [false, sha1('AnotherRandomString')],
            [false, substr(md5('AnotherRandomString'), 0, 31)],
            [false, 123],
            [false, false],
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
            [true,  sha1('SomeRandomString')],
            [false, ''],
            [false, md5('AnotherRandomString')],
            [false, substr(sha1('AnotherRandomString'), 0, 39)],
            [false, 123],
            [false, false],
        ];
    }


    /**
     * @dataProvider isSha1DataProvider
     */
    public function testIsSha1($expected, $input)
    {
        $this->assertSame($expected, Validate::isSha1($input));
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

    /**
     * @dataProvider isFloatDataProvider
     */
    public function testIsFloat($expected, $input)
    {
        $this->assertSame($expected, Validate::isFloat($input));
    }

    public function isCarrierNameDataProvider()
    {
        return [
            [true,  'Carrier'],
            [true,  'Carirer name'],
            [true,  'Mispled carriename'],
            [false,  'W!@##$5idf'],
            [true, '_carrier/_nam,e'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isCarrierNameDataProvider
     */
    public function testIsCarrierName($expected, $input)
    {
        $this->assertSame($expected, Validate::isCarrierName($input));
    }

    public function isImageSizeDataProvider()
    {
        return [
            [false, '-123'  ],
            [false, '234234'],
            [true,  '2342'  ],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isImageSizeDataProvider
     */
    public function testIsImageSize($expected, $input)
    {
        $this->assertSame($expected, Validate::isImageSize($input));
    }

    public function isDateProvider()
    {
        return [
            [true,  '1991-04-19'],
            [true,  '2015-03-22'],
            [true,  '1945-07-25'],
            [true,  '2020-03-19'],
            [false, '1991-03-33'],
            [false, '1991-15-19'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isDateProvider
     */
    public function testIsDate($expected, $input)
    {
        $this->assertSame($expected, Validate::isDate($input));
    }

    public function isBirthDateProvider()
    {
        return [
            [true,  '1991-04-19'],
            [true,  '2015-03-22'],
            [true,  '1945-07-25'],
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
    public function testIsBirthDate($expected, $input)
    {
        $this->assertSame($expected, Validate::isBirthDate($input));
    }

    public function isUrlDataProvider()
    {
        return [
            [true, '/modules.php'],
            [true, 'modules.php'],
            [true, 'https://google.com/'],
            [true, 'index.php?controller=AdminController&token=1234'],
            [true, 'https://fonts.googleapis.com/css?family=Arsenal|Roboto'],
        ];
    }

    /**
     * @param $expected
     * @param $input
     *
     * @dataProvider isUrlDataProvider
     */
    public function testIsUrl($expected, $input)
    {
        $this->assertSame($expected, Validate::isUrl($input));
    }

    public function isAbsoluteUrlDataProvider()
    {
        return [
            [false, '/modules.php'],
            [false, 'modules.php'],
            [true, 'https://example.com/'],
            [true, 'https://example.com/asdkfja.php'],
            [true, 'https://fonts.googleapis.com/css?family=Arsenal|Roboto'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isAbsoluteUrlDataProvider
     */
    public function testIsAbsoluteUrl($expected, $input)
    {
        $this->assertSame($expected, Validate::isAbsoluteUrl($input));
    }
    
    public function testisPriceTrue()
    {
        $this->assertEquals(true, Validate::isPrice(6.00));
    }    
}
