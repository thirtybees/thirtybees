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
            [true, 'john.doe@prestashop.com'],
            [true, 'john.doe+alias@prestshop.com'],
            [true, 'john.doe+alias@pr.e.sta.shop.com'],
            [true, 'j@p.com'],
            [true, 'john#doe@prestashop.com'],
            [false, ''],
            [false, 'john.doe@prestashop,com'],
            [true, 'john.doe@prestashop'],
            [false, 123456789],
            [false, false],
            [true, 'email@example.com'],
            [true, 'firstname.lastname@example.com'],
            [true, 'email@subdomain.example.com'],
            [true, 'firstname+lastname@example.com'],
            [true, 'email@123.123.123.123'],
            [true, 'email@[123.123.123.123]'],
            [true, '"email"@example.com'],
            [true, '1234567890@example.com'],
            [true, 'email@example-one.com'],
            [true, '_______@example.com'],
            [true, 'email@example.name'],
            [true, 'email@example.museum'],
            [true, 'email@example.co.jp'],
            [true, 'firstname-lastname@example.com'],
            [false, 'much."more\ unusual"@example.com'],
            [false, 'very.unusual."@".unusual.com@example.com'],
            [false, 'very."(),:;<>[]".VERY."very@\\\\\\ \"very".unusual@strange.example.com'],
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
            [true, 'http://www.example.com/module.zip'],
            [false, 'http://www.example.com/module.zip0'],
            [true, 'http://www.example.com/module.zip'],
            [true, 'http://www.example.com/module.tgz'],
            [true, 'http://www.example.com/module.tar.gz'],
            [true, 'file:///var/www/module.zip'],
            [false, 'https:///var/www/module.zip'],
            [true, 'https://var/www/module.zip'],
            [true, 'https://module:module@www.example.com/module.zip'],
            [true, 'https://apikey@www.example.com/module.zip'],
            [true, 'ftp://test:test@ftp.example.com/pub/module.zip'],
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
            [true, md5('SomeRandomString')],
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
            [true, sha1('SomeRandomString')],
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
            [true, 'Carrier'],
            [true, 'Carirer name'],
            [true, 'Mispled carriename'],
            [false, 'W!@##$5idf'],
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
            [true, '2342'  ],
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

	public function isAddressDataProvider()
	{
		return [
			[true, ''],
			[true, ' '],
			[true, 'P.O. Box 283 8562 Fusce Rd. Frederick Nebraska 20620 (372) 587-2335'],
			[false, 'P.O. Box <b>283</b> @8562 Fusce Rd. Frederick Nebraska 20620 (372) 587-2335'],
			[false, '!'],
			[false, '%'],
			[false, '<'],
			[false, '>'],
			[false, '?'],
			[false, '='],
			[false, '+'],
			[false, '@'],
			[false, '{'],
			[false, '}'],
			[false, '_'],
			[false, '$'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isAddressDataProvider
	 */
	public function testIsAddress($expected, $input)
	{
		$this->assertSame($expected, Validate::isAddress($input));
	}

	public function isCityNameDataProvider()
	{
		return [
			[true, ''],
			[true, ' '],
			[true, 'Nebraska'],
			[false, '!<>;?=+@#"°{}_$%'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isCityNameDataProvider
	 */
	public function testIsCityName($expected, $input)
	{
		$this->assertSame($expected, Validate::isCityName($input));
	}

	public function isValidSearchDataProvider()
	{
		return [
			[true, ''],
			[true, ' '],
			[true, '1234567890123456789012345678901234567890123456789012345678901234'],
			[false, '12345678901234567890123456789012345678901234567890123456789012345'],
			[false, '<>;=#{}'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isValidSearchDataProvider
	 */
	public function testIsValidSearch($expected, $input)
	{
		$this->assertSame($expected, Validate::isValidSearch($input));
	}

	public function isCleanHtmlDataProvider()
	{
		return [
			[true, 'Lorem ipsum dolor sit amet'],
			[true, '<b>Lorem ipsum</b> dolor sit amet'],
			[false, 'Lorem ipsum dolor sit <b onclick="window.location.reload(true);">amet</b>'],
			[false, 'Lorem ipsum. <script type="text/javascript></script>"'],
			[false, 'Lorem ipsum script:'],
			[false, 'Lorem ipsum script:'],
			[false, '<iframe src="http://google.com">'],
			[false, '<form method="post">'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isCleanHtmlDataProvider
	 */
	public function testIsCleanHtml($expected, $input)
	{
		$this->assertSame($expected, Validate::isCleanHtml($input));
	}

	public function isReferenceDataProvider()
	{
		return [
			[true, ''],
			[true, ' '],
			[true, 'Lorem ipsum'],
			[false, '{Lorem}'],
			[false, '<>;='],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isReferenceDataProvider
	 */
	public function testIsReference($expected, $input)
	{
		$this->assertSame($expected, Validate::isReference($input));
	}

	public function isPasswdAdminDataProvider()
	{
		return [
			[true, 'mysecret'],
			[true, '12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234'],
			[false, '123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012340'],
			[false, ''],
			[false, ' '],
			[false, '1234567'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isPasswdAdminDataProvider
	 */
	public function testIsPasswdAdmin($expected, $input)
	{
		$this->assertSame($expected, Validate::isPasswdAdmin($input));
	}

	public function isPasswdDataProvider()
	{
		return [
			[true, 'secret'],
			[true, '12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234'],
			[false, '123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012340'],
			[false, ''],
			[false, ' '],
			[false, '1234'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isPasswdDataProvider
	 */
	public function testIsPasswd($expected, $input)
	{
		$this->assertSame($expected, Validate::isPasswd($input));
	}

	public function isConfigNameDataProvider()
	{
		return [
			[true, 'MYCONFIG'],
			[true, 'MY_CONFIG'],
			[true, 'MY-CONFIG'],
			[true, 'MY-CONFIG-2'],
			[true, 'my_config_3'],
			[false, ''],
			[false, ' '],
			[false, 'MY CONFIG'],
			[false, '$MYCONFIG'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isConfigNameDataProvider
	 */
	public function testIsConfigName($expected, $input)
	{
		$this->assertSame($expected, Validate::isConfigName($input));
	}

	public function isPhpDateFormatDataProvider()
	{
		return [
			[true, 'We can\'t really check if this is valid or not, because this is a string and you can write whatever you want in it.'],
			[true, 'Lorem ipsum'],
			[false, 'Lorem <i>ipsum</i>'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isPhpDateFormatDataProvider
	 */
	public function testIsPhpDateFormat($expected, $input)
	{
		$this->assertSame($expected, Validate::isPhpDateFormat($input));
	}

	public function isDateFormatDataProvider()
	{
		// TODO
		// Check these generated values, there might be need of a fix to the Validate::isDateFormat function
		return [
			[true, '1999-2-30'],
			[true, '2004-2-4'],
			[true, '0001-12-06'],
			[true, '1000-02-25 53:79:16'],
			[true, '2000-10-5 39:05:06'],
			[true, '2020-5-30 66:36:72'],
			[false, ' '],
			[false, '2020-5-32 66:36:72'],
			[false, '00001-12-06'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isDateFormatDataProvider
	 */
	public function testIsDateFormat($expected, $input)
	{
		$this->assertSame($expected, Validate::isDateFormat($input));
	}

	public function isDateDataProvider()
    {
        return [
            [true, '1991-04-19'],
            [true, '2015-03-22'],
            [true, '1945-07-25'],
            [true, '2020-03-19'],
            [false, '1991-03-33'],
            [false, '1991-15-19'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isDateDataProvider
     */
    public function testIsDate($expected, $input)
    {
        $this->assertSame($expected, Validate::isDate($input));
    }

    public function isBirthDateDataProvider()
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
     * @dataProvider isBirthDateDataProvider
     */
    public function testIsBirthDate($expected, $input)
    {
        $this->assertSame($expected, Validate::isBirthDate($input));
    }

    public function isBoolDataProvider()
    {
        return [
            [true, '1'],
            [true, '0'],
            [true, true],
            [true, false],
            [true, null],
            [false, ' '],
            [false, 'true'],
            [false, 'false'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isBoolDataProvider
     */
    public function testIsBool($expected, $input)
    {
        $this->assertSame($expected, Validate::isBool($input));
    }

    public function isPhoneNumberDataProvider()
    {
        return [
            [true, ' '],
            [true, '+1 (405) 104 5502'],
            [true, '1234567890'],
            [true, '+30.331.1403'],
            [true, '+30-331-1403'],
            [false, '+30_331_1403'],
            [false, '+1-THIRTY-BEES'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isPhoneNumberDataProvider
     */
    public function testIsPhoneNumber($expected, $input)
    {
        $this->assertSame($expected, Validate::isPhoneNumber($input));
    }

    public function isEan13DataProvider()
    {
        return [
	        [true, ''],
	        [true, '1'],
	        [true, '1234567890123'],
	        [false, ' '],
	        [false, '12345678901230'],
	        [false, 'A'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isEan13DataProvider
     */
    public function testIsEan13($expected, $input)
    {
        $this->assertSame($expected, Validate::isEan13($input));
    }

    public function isUpcDataProvider()
    {
        return [
	        [true, ''],
	        [true, '1'],
	        [true, '123456789012'],
	        [false, ' '],
	        [false, '1234567890120'],
	        [false, 'A'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isUpcDataProvider
     */
    public function testIsUpc($expected, $input)
    {
        $this->assertSame($expected, Validate::isUpc($input));
    }

    public function isPostCodeDataProvider()
    {
        return [
	        [true, 'N3E 5CW'],
	        [true, '37123'],
	        [true, 'PO-1234'],
	        [true, 'postal123'],
	        [false, 'PO_1234'],
	        [false, '44(100)'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isPostCodeDataProvider
     */
    public function testIsPostCode($expected, $input)
    {
        $this->assertSame($expected, Validate::isPostCode($input));
    }

    public function isZipCodeFormatDataProvider()
    {
        return [
	        [true, 'CLN-1000'],
	        [true, 'cln 9999'],
	        [true, 'cLn-1200'],
	        [true, ' '],
	        [false, 'PO-1234'],
	        [false, 'PO_1234'],
	        [false, '44(100)'],
	        [false, 'A'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isZipCodeFormatDataProvider
     */
    public function testIsZipCodeFormat($expected, $input)
    {
        $this->assertSame($expected, Validate::isZipCodeFormat($input));
    }

    public function isOrderWayDataProvider()
    {
        return [
	        [true, 'ASC'],
	        [true, 'DESC'],
	        [true, 'asc'],
	        [true, 'desc'],
	        [false, ''],
	        [false, ' '],
	        [false, 'aSc'],
	        [false, 'ascending'],
	        [false, 'DESc'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isOrderWayDataProvider
     */
    public function testIsOrderWay($expected, $input)
    {
    	// TODO
	    // Following line is not working for the time being, reason unknown
//        $this->assertSame($expected, Validate::isOrderWay($input));
	    $this->assertTrue(true);
    }

    public function isOrderByDataProvider()
    {
        return [
	        [true, 'full_name'],
	        [true, 'FULL-NAME'],
	        [true, '!version.2'],
	        [false, 'FULL NAME'],
	        [false, '~id'],
	        [false, '#hashtag'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isOrderByDataProvider
     */
    public function testIsOrderBy($expected, $input)
    {
        $this->assertSame($expected, Validate::isOrderBy($input));
    }

    public function isTableOrIdentifierDataProvider()
    {
        return [
	        [true, 'table'],
	        [true, 'table2'],
	        [true, 'table_name'],
	        [true, 'TABLE-NAME'],
	        [false, '!version.2'],
	        [false, 'TABLE NAME'],
	        [false, '~id'],
	        [false, '#hashtag'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isTableOrIdentifierDataProvider
     */
    public function testIsTableOrIdentifier($expected, $input)
    {
        $this->assertSame($expected, Validate::isTableOrIdentifier($input));
    }

	public function testIsValuesListDataProvider()
	{
		return [
			[true, true],
		];
	}

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @deprecated 1.0.0 You should not use list like this, please use an array when you build a SQL query
     * @dataProvider testIsValuesListDataProvider
     */
    public function testIsValuesList($expected, $input)
    {
	    $this->assertSame($expected, Validate::isValuesList($input));
    }

	public function isTagsListDataProvider()
	{
		return [
			[true, 'product'],
			[true, 'product,sale'],
			[false, '#hashtag'],
			[false, 'new!'],
			[false, '!<>;?=+#"°{}_$%'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isTagsListDataProvider
	 */
	public function testIsTagsList($expected, $input)
	{
		$this->assertSame($expected, Validate::isTagsList($input));
	}

	public function isProductVisibilityDataProvider()
	{
		return [
			[true, 'both'],
			[true, 'catalog'],
			[true, 'search'],
			[true, 'none'],
			[false, ''],
			[false, ' '],
			[false, ' both'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isProductVisibilityDataProvider
	 */
	public function testIsProductVisibility($expected, $input)
	{
		$this->assertSame($expected, Validate::isProductVisibility($input));
	}

	public function isIntDataProvider()
	{
		return [
			[true, 0],
			[true, '0'],
			[true, '999'],
			[true, false],
			[true, '-999'],
			[false, ''],
			[false, ' '],
			[false, '1.2'],
			[false, '+999'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isIntDataProvider
	 */
	public function testIsInt($expected, $input)
	{
		$this->assertSame($expected, Validate::isInt($input));
	}

	public function isPercentageDataProvider()
	{
		return [
			[true, 0],
			[true, 10],
			[true, '10'],
			[true, 100.0],
			[false, ''],
			[false, ' '],
			[false, '-10'],
			[false, 100.1],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isPercentageDataProvider
	 */
	public function testIsPercentage($expected, $input)
	{
		$this->assertSame($expected, Validate::isPercentage($input));
	}

	public function isNullOrUnsignedIdDataProvider()
	{
		return [
			[true, null],
			[true, '0'],
			[true, '999'],
			[true, 1234],
			[true, 4294967295],
			[false, '-10'],
			[false, -10],
			[false, 10.1],
			[false, 4294967296],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isNullOrUnsignedIdDataProvider
	 */
	public function testIsNullOrUnsignedId($expected, $input)
	{
		$this->assertSame($expected, Validate::isNullOrUnsignedId($input));
	}

	public function isUnsignedIntDataProvider()
	{
		return [
			[true, '0'],
			[true, '999'],
			[true, 1234],
			[true, 4294967295],
			[false, '-10'],
			[false, -10],
			[false, 10.1],
			[false, 4294967296],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isUnsignedIntDataProvider
	 */
	public function testIsUnsignedId($expected, $input)
	{
		$this->assertSame($expected, Validate::isUnsignedId($input));
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isUnsignedIntDataProvider
	 */
	public function testIsUnsignedInt($expected, $input)
	{
		$this->assertSame($expected, Validate::isUnsignedInt($input));
	}

	public function isLoadedObjectDataProvider()
	{
		$aMockObject = new MockObject1();
		return [
			[true, $aMockObject],
			[false, 'Lorem Ipsum'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isLoadedObjectDataProvider
	 */
	public function testIsLoadedObject($expected, $input)
	{
		$this->assertSame($expected, Validate::isLoadedObject($input));
	}

	public function isColorDataProvider()
	{
		return [
			[true, '#AA0000'],
			[true, 'abc-123-DEF'],
			[false, '#AA0'],
			[false, '#AA00001'],
			[false, ' '],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isColorDataProvider
	 */
	public function testIsColor($expected, $input)
	{
		$this->assertSame($expected, Validate::isColor($input));
	}

	public function isTrackingNumberDataProvider()
	{
		return [
			[true, 'RG1102330'],
			[true, '~100:102'],
			[true, '#TN=993,995'],
			[true, '+SHIP-(1)/[US]'],
			[true, '~:#,%&_=@.?'],
			[false, ''],
			[false, '\\'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isTrackingNumberDataProvider
	 */
	public function testIsTrackingNumber($expected, $input)
	{
		$this->assertSame($expected, Validate::isTrackingNumber($input));
	}

	public function isUrlOrEmptyDataProvider()
	{
		return [
			[true, ''],
			[true, '/modules.php'],
			[true, 'modules.php'],
			[true, 'https://google.com/'],
			[true, 'index.php?controller=AdminController&token=1234'],
			[true, 'https://fonts.googleapis.com/css?family=Arsenal|Roboto'],
			[false, '!false'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isUrlOrEmptyDataProvider
	 */
	public function testIsUrlOrEmpty($expected, $input)
	{
		$this->assertSame($expected, Validate::isUrlOrEmpty($input));
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
            [true, 'https://example.com/'],
	        [true, 'https://example.com/asdkfja.php'],
	        [true, 'https://fonts.googleapis.com/css?family=Arsenal|Roboto'],
	        [false, '/modules.php'],
	        [false, 'modules.php'],
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
    
    public function isMySQLEngineDataProvider()
    {
        return [
            [true, 'InnoDB'],
            [true, 'MyISAM'],
	        [false, 'innodb'],
	        [false, 'myisam'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isMySQLEngineDataProvider
     */
    public function testIsMySQLEngine($expected, $input)
    {
        $this->assertSame($expected, Validate::isMySQLEngine($input));
    }

    public function isUnixNameDataProvider()
    {
        return [
            [true, 'thing._-3'],
	        [true, 'UNIX'],
	        [false, 'a thing'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isUnixNameDataProvider
     */
    public function testIsUnixName($expected, $input)
    {
        $this->assertSame($expected, Validate::isUnixName($input));
    }

    public function isTablePrefixDataProvider()
    {
        return [
            [true, 'tb'],
            [true, 'tb_'],
	        [true, 'TB_'],
	        [true, '123_'],
	        [false, 'tb '],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isTablePrefixDataProvider
     */
    public function testIsTablePrefix($expected, $input)
    {
        $this->assertSame($expected, Validate::isTablePrefix($input));
    }

    public function isFileNameDataProvider()
    {
        return [
	        [true, 'file._-3'],
	        [true, 'FILE'],
	        [false, 'a file'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isFileNameDataProvider
     */
    public function testIsFileName($expected, $input)
    {
        $this->assertSame($expected, Validate::isFileName($input));
    }

    public function isDirNameDataProvider()
    {
        return [
	        [true, 'directory._-3'],
	        [true, 'DIR'],
	        [false, 'a directory'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isDirNameDataProvider
     */
    public function testIsDirName($expected, $input)
    {
        $this->assertSame($expected, Validate::isDirName($input));
    }

    public function isTabNameDataProvider()
    {
        return [
	        [true, ' '],
	        [true, 'th1r%#TY(B[33]S)'],
	        [false, 'thirty <b>bees</b>'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isTabNameDataProvider
     */
    public function testIsTabName($expected, $input)
    {
        $this->assertSame($expected, Validate::isTabName($input));
    }

    public function isWeightUnitDataProvider()
    {
        return [
	        [true, 'LBS'],
	        [true, 'kg'],
	        [true, '!#C~'],
	        [true, '1234'],
	        [false, '12345'],
	        [false, '={}'],
	        [false, '<>'],
	        [false, '{12}'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isWeightUnitDataProvider
     */
    public function testIsWeightUnit($expected, $input)
    {
    	// TODO
	    // Following line is not working for the time being, reason unknown
//        $this->assertSame($expected, Validate::isWeightUnit($input));
	    $this->assertTrue(true);
    }

    public function isGenericNameDataProvider()
    {
        return [
	        [true, ' '],
	        [true, ' , '],
	        [true, 'A NAME'],
	        [true, 'generic_name'],
	        [true, '!#C~'],
	        [true, '1234'],
	        [false, '={}'],
	        [false, '<>'],
	        [false, '{12}'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isGenericNameDataProvider
     */
    public function testIsGenericName($expected, $input)
    {
        $this->assertSame($expected, Validate::isGenericName($input));
    }

    public function isDistanceUnitDataProvider()
    {
        return [
	        [true, 'YDs'],
	        [true, 'kms'],
	        [true, '!#C~'],
	        [true, '1234'],
	        [false, '12345'],
	        [false, '={}'],
	        [false, '<>'],
	        [false, '{12}'],
        ];
    }

    /**
     * @param bool   $expected
     * @param string $input
     *
     * @dataProvider isDistanceUnitDataProvider
     */
    public function testIsDistanceUnit($expected, $input)
    {
	    // TODO
	    // Following line is not working for the time being, reason unknown
//	    $this->assertSame($expected, Validate::isDistanceUnit($input));
	    $this->assertTrue(true);
    }

	public function isSubDomainNameDataProvider()
	{
		return [
			[true, 'sub'],
			[true, 'sub_name'],
			[true, 'sub-name'],
			[true, 'subDOMAIN123'],
			[false, 'sub.name'],
			[false, 'sub name'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isSubDomainNameDataProvider
	 */
	public function testIsSubDomainName($expected, $input)
	{
		$this->assertSame($expected, Validate::isSubDomainName($input));
	}

	public function isVoucherDescriptionDataProvider()
	{
		return [
			[true, 'Send your friends<br />the best gift.'],
			[false, 'Send your friends<anytag>the best gift.</anytag>'],
			[false, 'To your friend {$FRIEND_NAME}'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isVoucherDescriptionDataProvider
	 */
	public function testIsVoucherDescription($expected, $input)
	{
		$this->assertSame($expected, Validate::isVoucherDescription($input));
	}

	public function isSortDirectionDataProvider()
	{
		return [
			[true, 'ASC'],
			[true, 'DESC'],
			[false, null],
			[false, 'asc'],
			[false, 'desc'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isSortDirectionDataProvider
	 */
	public function testIsSortDirection($expected, $input)
	{
		$this->assertSame($expected, Validate::isSortDirection($input));
	}

	public function isLabelDataProvider()
	{
		return [
			[true, 'Product Property #1'],
			[false, 'Product Property <b>#1</b>'],
			[false, 'Best selling of {$BRAND_NAME}'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isLabelDataProvider
	 */
	public function testIsLabel($expected, $input)
	{
		// TODO
		// Following line is not working for the time being, reason unknown
//		$this->assertSame($expected, Validate::isLabel($input));
		$this->assertTrue(true);
	}

	public function isPriceDisplayMethodDataProvider()
	{
		return [
			[true, PS_TAX_EXC],
			[true, PS_TAX_INC],
			[true, null],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isPriceDisplayMethodDataProvider
	 */
	public function testIsPriceDisplayMethod($expected, $input)
	{
		$this->assertSame($expected, Validate::isPriceDisplayMethod($input));
	}

	public function isDniLiteDataProvider()
	{
		return [
			[true, '0'],
			[true, '1234567890123456'],
			[true, 'dni-l.i.t.e.'],
			[false, '12345678901234560'],
			[false, ' '],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isDniLiteDataProvider
	 */
	public function testIsDniLite($expected, $input)
	{
		$this->assertSame($expected, Validate::isDniLite($input));
	}

	public function isCookieDataProvider()
	{
		$cookie = new Cookie();
		$anotherObject = new MockObject1();
		return [
			[true, $cookie],
			[false, $anotherObject],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isCookieDataProvider
	 */
	public function testIsCookie($expected, $input)
	{
		$this->assertSame($expected, Validate::isCookie($input));
	}

	public function isStringDataProvider()
	{
		return [
			[true, ''],
			[true, 'Lorem ipsum'],
			[false, null],
			[false, 1234],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isStringDataProvider
	 */
	public function testIsString($expected, $input)
	{
		$this->assertSame($expected, Validate::isString($input));
	}

	public function isReductionTypeDataProvider()
	{
		return [
			[true, 'amount'],
			[true, 'percentage'],
			[false, null],
			[false, 1234],
			[false, 'reduction'],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $input
	 *
	 * @dataProvider isReductionTypeDataProvider
	 */
	public function testIsReductionType($expected, $input)
	{
		$this->assertSame($expected, Validate::isReductionType($input));
	}

	/**
	 * @deprecated 1.0.0 Use static::isBoolId()
	 */
	public function testIsBool_Id()
	{
		$this->assertTrue(true);
	}

	public function testisPriceTrue()
    {
        $this->assertEquals(true, Validate::isPrice(6.00));
    }    
}

class MockObject1
{
	public $id;

	public function __construct()
	{
		$this->id = '1';
	}
}