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

    public function isNameDataProvider()
    {
    	return [
		    [true, 'John'],
		    [true, 'Anthony Jr.'],
		    [true, 'Carl\'s Jr.'],
		    [true, 'VII. Henry'],
		    [false, '5. Henry'],
		    [false, 'Henry The 5th'],
    		[false, 'Attica!'],
    		[true, '~Smith^&*¡£¢∞§¶•ª–ª'],
	    ];
    }

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isNameDataProvider
	 */
	public function testIsName($expected, $input)
	{
		$this->assertSame($expected, Validate::isName($input));
	}

    public function isHookNameDataProvider()
    {
    	return [
		    [true, 'hookDisplayHeader'],
		    [true, 'hookFooter3'],
		    [true, 'hookBottom-3'],
		    [false, 'hookDesc.'],
    		[true, '123456'],
	    ];
    }

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isHookNameDataProvider
	 */
	public function testIsHookName($expected, $input)
	{
		$this->assertSame($expected, Validate::isHookName($input));
	}

    public function isMailNameDataProvider()
    {
    	return [
		    [true, 'John Doe'],
		    [true, 'Carl\'s Jr.'],
		    [true, 'Henry the 5th'],
		    [true, '123456'],
		    [true, '    Attic@! '],
		    [true, '$%^&*()+¡™£¢∞§¶•ªº–≠'],
		    [true, 'éåáıíìæ'],
		    [false, '#hashtag'],
		    [false, '<script>'],
		    [false, 'me;you'],
		    [false, 'bridg='],
		    [false, 'func{}'],
		    [true, '[@_@]'],
	    ];
    }

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isMailNameDataProvider
	 */
	public function testIsMailName($expected, $input)
	{
		$this->assertSame($expected, Validate::isMailName($input));
	}

    public function isMailSubjectDataProvider()
    {
    	return [
		    [true, 'Full fathom five thy father lies, of his bones are coral made. Those are pearls that were his eyes. Nothing of him that doth fade, but doth suffer a sea-change into something rich and strange.'],
		    [true, '‘Life’s but a walking shadow, a poor player, that struts and frets his hour upon the stage, and then is heard no more; it is a tale told by an idiot, full of sound and fury, signifying nothing.’'],
		    [true, 'Srp5BDkcZb8n2Wqwv7nK kVEJTqMA5AJ1NeO7I5Ce lP5MWUXM3TPMpQcd0edf xUmHBLSRqpxG7Pol5JKu'],
		    [true, 'JohnDoe'],
		    [false, '‘<i>Friends</i>, <b>Romans</b>, countrymen, lend me your ears: I come to bury Caesar, not to praise him.’'],
	    ];
    }

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isMailSubjectDataProvider
	 */
	public function testIsMailSubject($expected, $input)
	{
		$this->assertSame($expected, Validate::isMailSubject($input));
	}

	public function isModuleNameDataProvider()
	{
		return [
			[true, 'MailChimp'],
			[true, 'yvcyXdjlKYxztxUUEq3E'],
			[true, 'thirtybeesv2'],
			[true, 'RICH_TEXT'],
			[true, 'checkout-master'],
			[false, 'thirtybeesv2.1'],
			[false, 'duplicate~finder'],
			[false, 'some module'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isModuleNameDataProvider
	 */
	public function testIsModuleName($expected, $input)
	{
		$this->assertSame($expected, Validate::isModuleName($input));
	}

	public function isTplNameDataProvider()
	{
		return [
			[true, 'header'],
			[true, 'header2'],
			[true, 'FOOTER'],
			[true, 'FOOTER_1'],
			[true, 'FOOTER-2'],
			[false, 'header2.tpl'],
			[false, 'header2(2)'],
			[false, 'header 2'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isTplNameDataProvider
	 */
	public function testIsTplName($expected, $input)
	{
		$this->assertSame($expected, Validate::isTplName($input));
	}

	public function isImageTypeNameDataProvider()
	{
		return [
			[true, 'type'],
			[true, 'type2'],
			[true, 'type 2'],
			[true, 'TYPE'],
			[true, 'TYPE_1'],
			[true, 'TYPE-2'],
			[false, 'type2.ext'],
			[false, 'type(2)'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isImageTypeNameDataProvider
	 */
	public function testIsImageTypeName($expected, $input)
	{
		$this->assertSame($expected, Validate::isImageTypeName($input));
	}

	public function isPriceDataProvider()
	{
		return [
			[true, '1'],
			[true, '000'],
			[true, '9999999999'],
			[true, '1.0'],
			[true, '99.999999999'],
			[true, '1000000000.999999999'],
			[false, ''],
			[false, ' '],
			[false, '10000000000'],
			[false, '1.'],
			[false, '1,'],
			[false, '1,0'],
			[false, '99.9999999999'],
			[false, '10000000000.999999999'],
			[false, 'ABC'],
			[false, '1 0'],
			[false, '-123'],
			[false, '-123.00'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isPriceDataProvider
	 */
	public function testIsPrice($expected, $input)
	{
		$this->assertSame($expected, Validate::isPrice($input));
	}

	public function isNegativePriceDataProvider()
	{
		return [
			[true, '1'],
			[true, '000'],
			[true, '9999999999'],
			[true, '1.0'],
			[true, '99.999999999'],
			[true, '-1000000000.999999999'],
			[true, '-0'],
			[true, '-123'],
			[true, '-123.00'],
			[false, ''],
			[false, ' '],
			[false, '10000000000'],
			[false, '1.'],
			[false, '1,'],
			[false, '1,0'],
			[false, '99.9999999999'],
			[false, '10000000000.999999999'],
			[false, 'ABC'],
			[false, '1 0'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isNegativePriceDataProvider
	 */
	public function testIsNegativePrice($expected, $input)
	{
		$this->assertSame($expected, Validate::isNegativePrice($input));
	}

	public function isLanguageIsoCodeDataProvider()
	{
		return [
			[true, 'US'],
			[true, 'USA'],
			[false, ''],
			[false, ' '],
			[false, 'U'],
			[false, 'U.S'],
			[false, '12'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isLanguageIsoCodeDataProvider
	 */
	public function testIsLanguageIsoCode($expected, $input)
	{
		$this->assertSame($expected, Validate::isLanguageIsoCode($input));
	}

	public function isLanguageCodeDataProvider()
	{
		return [
			[true, 'us'],
			[true, 'us-US'],
			[true, 'en-gb'],
			[false, ''],
			[false, ' '],
			[false, 'u'],
			[false, 'GBR'],
			[false, 'usUS'],
			[false, 'us US'],
			[false, 'us_US'],
			[false, '123'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isLanguageCodeDataProvider
	 */
	public function testIsLanguageCode($expected, $input)
	{
		$this->assertSame($expected, Validate::isLanguageCode($input));
	}

	public function isStateIsoCodeDataProvider()
	{
		return [
			[true, 't'],
			[true, 'T'],
			[true, 'tn'],
			[true, 'tn-TN'],
			[true, 'TN'],
			[true, 't-t'],
			[true, 'WTON'],
			[true, 'WTON-WTON'],
			[true, 'T1-9'],
			[true, '0-1'],
			[false, ''],
			[false, ' '],
			[false, 'TN-'],
			[false, 'TN TN'],
			[false, 'WTONG-WTON'],
			[false, 'WTON-WTONG'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isStateIsoCodeDataProvider
	 */
	public function testIsStateIsoCode($expected, $input)
	{
		$this->assertSame($expected, Validate::isStateIsoCode($input));
	}

	public function isNumericIsoCodeDataProvider()
	{
		return [
			[true, '00'],
			[true, '123'],
			[false, ''],
			[false, '.'],
			[false, ' '],
			[false, 'ABC'],
			[false, '0'],
			[false, '1234'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isNumericIsoCodeDataProvider
	 */
	public function testIsNumericIsoCode($expected, $input)
	{
		$this->assertSame($expected, Validate::isNumericIsoCode($input));
	}

	public function isDiscountNameDataProvider()
	{
		return [
			[true, 'dsc'],
			[true, 'summer sale'],
			[true, 'season4'],
			[true, 'great®'],
			[true, 'summer sale is here until the 13'],
			[false, ''],
			[false, ' '],
			[false, 'dn'],
			[false, 'summer sale is here until the end'],
			[false, '!<>,;?=+()@"°{}_$%:'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isDiscountNameDataProvider
	 */
	public function testIsDiscountName($expected, $input)
	{
		$this->assertSame($expected, Validate::isDiscountName($input));
	}

	public function isCatalogNameDataProvider()
	{
		return [
			[true, ' '],
			[true, 'featured'],
			[true, 'FEATURED [summer]'],
			[false, '<>;=#{}'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isCatalogNameDataProvider
	 */
	public function testIsCatalogName($expected, $input)
	{
		$this->assertSame($expected, Validate::isCatalogName($input));
	}

	public function isMessageDataProvider()
	{
		return [
			[true, ''],
			[true, ' '],
			[true, 'My name is John and I will help you through your shopping experience.'],
			[false, 'My name is <b>John</b> and I will help you through your shopping experience.'],
			[false, 'My name is {$EMPLOYEE_NAME} and I will help you through your shopping experience.'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isMessageDataProvider
	 */
	public function testIsMessage($expected, $input)
	{
		$this->assertSame($expected, Validate::isMessage($input));
	}

	public function isCountryNameDataProvider()
	{
		return [
			[true, ' '],
			[true, 'United States'],
			[true, 'Uran-Uran'],
			[false, ''],
			[false, 'U.S.'],
			[false, '3rd District'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isCountryNameDataProvider
	 */
	public function testIsCountryName($expected, $input)
	{
		$this->assertSame($expected, Validate::isCountryName($input));
	}

	public function isLinkRewriteDataProvider()
	{
		// TODO
		// Find a string that is valid for the first case and not the second
		if (Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')) {
			return [
				[true, 'home'],
				[true, 'contact-us'],
				[true, 'our_story'],
				[true, 'since-2005'],
				[false, ''],
				[false, ' '],
				[false, '#home'],
			];
		} else {
			return [
				[true, 'home'],
				[true, 'contact-us'],
				[true, 'our_story'],
				[true, 'since-2005'],
				[false, ''],
				[false, ' '],
				[false, '#home'],
			];
		}
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isLinkRewriteDataProvider
	 */
	public function testIsLinkRewrite($expected, $input)
	{
		$this->assertSame($expected, Validate::isLinkRewrite($input));
	}

	public function isRoutePatternDataProvider()
	{
		// TODO
		// Find a string that is valid for the first case and not the second
		if (Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')) {
			return [
				[true, 'home'],
				[true, 'contact-us'],
				[true, 'our_story'],
				[true, 'since-2005'],
				[true, 'google.com(sale)'],
				[true, 'node{3}'],
				[true, '/:usr/'],
				[false, ''],
				[false, ' '],
				[false, '#home'],
				[false, 'parent\\child'],
			];
		} else {
			return [
				[true, 'home'],
				[true, 'contact-us'],
				[true, 'our_story'],
				[true, 'since-2005'],
				[true, 'google.com(sale)'],
				[true, 'node{3}'],
				[true, '/:usr/'],
				[false, ''],
				[false, ' '],
				[false, '#home'],
				[false, 'parent\\child'],
			];
		}
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isRoutePatternDataProvider
	 */
	public function testIsRoutePattern($expected, $input)
	{
		$this->assertSame($expected, Validate::isRoutePattern($input));
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
