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

    // FIXME
	public function testPasswdGen()
	{
		$this->assertTrue(true);
	}

	public function getBytesDataProvider()
	{
		return [
			[0],
		];
	}

	// FIXME
	/**
	 * @param int $length
	 *
	 * @dataProvider getBytesDataProvider()
	 */
	public function testGetBytes($length)
	{
		$this->assertTrue(true);
	}

	public function redirectDataProvider()
	{
		return [
			[''],
		];
	}

	// FIXME
	/**
	 * @param string $url
	 *
	 * @dataProvider redirectDataProvider()
	 */
	public function testRedirect($url)
	{
		$this->assertTrue(true);
	}

	public function strReplaceFirstDataProvider()
	{
		return [
			['google.com', '.net', '.com', 'google.net', null],
			['google.com', '.net', '.com', 'google.net', 0],
			['google.com', '.net', '.com', 'google.net', 1],
			['google.net', 'bing', '', 'google.net', 1],
		];
	}

	/**
	 * @param string  $expected
	 * @param string  $search
	 * @param string  $replace
	 * @param string  $string
	 * @param int     $cursor
	 *
	 * @dataProvider strReplaceFirstDataProvider
	 */
	public function testStrReplaceFirst($expected, $search, $replace, $string, $cursor = 0)
	{
		$this->assertEquals($expected, Tools::strReplaceFirst($search, $replace, $string, $cursor));
	}

	// FIXME
	public function testRedirectLink()
	{
		$this->assertTrue(true);
	}

	// FIXME
	public function testRedirectAdmin()
	{
		$this->assertTrue(true);
	}

	public function getShopProtocolDataProvider()
	{
		return [
			[(Configuration::get('PS_SSL_ENABLED') || (!empty($_SERVER['HTTPS'])
					&& Tools::strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://']
		];
	}

	/**
	 * @param string $expected
	 *
	 * @dataProvider getShopProtocolDataProvider
	 */
	public function testGetShopProtocol($expected)
	{
		$this->assertEquals($expected, Tools::getShopProtocol());
	}

	public function strtolowerDataProvider()
	{
		return [
			['thirty bees', 'THIRTY BEES'],
			['thirty bees', 'Thirty Bees'],
			['thirty bees', 'thirty bees'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $str
	 *
	 * @dataProvider strtolowerDataProvider
	 */
	public function testStrtolower($expected, $str)
	{
		$this->assertEquals($expected, Tools::strtolower($str));
	}

	public function getProtocolDataProvider()
	{
		return [
			['https://', true],
			['http://', false],
			['http://', null],
		];
	}

	/**
	 * @param string $expected
	 * @param bool   $useSsl
	 *
	 * @dataProvider getProtocolDataProvider
	 */
	public function testGetProtocol($expected, $useSsl)
	{
		$this->assertEquals($expected, Tools::getProtocol($useSsl));
	}

	// FIXME
	public function testGetRemoteAddr()
	{
		$this->assertTrue(true);
	}

	public function getCurrentUrlProtocolPrefixDataProvider()
	{
		if (Tools::usingSecureMode()) {
			return [
				['https://'],
			];
		} else {
			return [
				['http://'],
			];
		}
	}

	/**
	 * @param string $expected
	 *
	 * @dataProvider getCurrentUrlProtocolPrefixDataProvider
	 */
	public function testGetCurrentUrlProtocolPrefix($expected)
	{
		$this->assertEquals($expected, Tools::getCurrentUrlProtocolPrefix());
	}

	public function usingSecureModeDataProvider()
	{
		$server1_1 = [
			'HTTPS' => 'On',
		];
		$server1_2 = [
			'HTTPS' => 1,
		];
		$server1_3 = [
			'HTTPS' => '',
		];
		$server2_1 = [
			'SSL' => 'On',
		];
		$server2_2 = [
			'SSL' => 1,
		];
		$server2_3 = [
			'SSL' => '',
		];
		$server3_1 = [
			'REDIRECT_HTTPS' => 'On',
		];
		$server3_2 = [
			'REDIRECT_HTTPS' => 1,
		];
		$server3_3 = [
			'REDIRECT_HTTPS' => '',
		];
		$server4_1 = [
			'HTTP_SSL' => 'On',
		];
		$server4_2 = [
			'HTTP_SSL' => 1,
		];
		$server4_3 = [
			'HTTP_SSL' => '',
		];
		$server5_1 = [
			'HTTP_X_FORWARDED_PROTO' => 'HTTPS',
		];
		$server5_2 = [
			'HTTP_X_FORWARDED_PROTO' => 'https',
		];
		$server5_3 = [
			'HTTP_X_FORWARDED_PROTO' => 'http',
		];
		return [
			[true, $server1_1],
			[true, $server1_2],
			[false, $server1_3],
			[true, $server2_1],
			[true, $server2_2],
			[false, $server2_3],
			[true, $server3_1],
			[true, $server3_2],
			[false, $server3_3],
			[true, $server4_1],
			[true, $server4_2],
			[false, $server4_3],
			[true, $server5_1],
			[true, $server5_2],
			[false, $server5_3],
		];
	}

	/**
	 * @param bool    $expected
	 * @param array   $server
	 *
	 * @dataProvider usingSecureModeDataProvider
	 */
	public function testUsingSecureMode($expected, $server)
	{
		$_SERVER = $server;
		$this->assertEquals($expected, Tools::usingSecureMode());
	}

	public function secureReferrerDataProvider()
	{
		return [
			['http://server.domain/', 'http://server.domain/'],
			['https://server.domain:443/', 'https://server.domain:443/'],
			['https://server.domain:443/contact-us/', 'https://server.domain:443/contact-us/'],
			['server.domain', 'loremipsum'],
		];
	}

	// FIXME
	/**
	 * @param string $expected
	 * @param string $referrer
	 *
	 * @dataProvider secureReferrerDataProvider
	 */
	public function testSecureReferrer($expected, $referrer)
	{
		$this->assertTrue(true);
		/*
		$_SERVER = [
			'SERVER_NAME' => 'server.domain',
		];
		if (!defined('_PS_SSL_PORT_') && !isset($_SESSION['_PS_SSL_PORT_'])) {
			define('_PS_SSL_PORT_', 443);
			$_SESSION['_PS_SSL_PORT_'] = true;
		}
		if (!defined('__PS_BASE_URI__') && !isset($_SESSION['__PS_BASE_URI__'])) {
			define('__PS_BASE_URI__', 'server.domain');
			$_SESSION['__PS_BASE_URI__'] = true;
		}
		$this->assertEquals($expected, Tools::secureReferrer($referrer));
		*/
	}

	public function getServerNameDataProvider()
	{
		$server1 = [
			'HTTP_X_FORWARDED_SERVER' => 'http://server.domain',
		];
		$server2 = [
			'SERVER_NAME' => 'https://server.domain',
		];
		return [
			['http://server.domain', $server1],
			['https://server.domain', $server2],
		];
	}

	/**
	 * @param string $expected
	 * @param array  $server
	 *
	 * @dataProvider getServerNameDataProvider
	 */
	public function testGetServerName($expected, $server)
	{
		$_SERVER = $server;
		$this->assertEquals($expected, Tools::getServerName());
	}

	public function getAllValuesDataProvider()
	{
		return [
			[
				[
					'key1' => 'value1',
					'key2' => 'value2',
					'key3' => 'value3',
					'key4' => 'value4',
				]
			],
		];
	}

	/**
	 * @param array $expected
	 *
	 * @dataProvider getAllValuesDataProvider
	 */
	public function testGetAllValues($expected)
	{
		$_POST = array(
			'key1' => 'value1',
			'key2' => 'value2',
		);
		$_GET = array(
			'key3' => 'value3',
			'key4' => 'value4',
		);
		$this->assertEquals($expected, Tools::getAllValues());
	}

	public function getIssetDataProvider()
	{
		return [
			[false, null, []],
			[true, 'key', ['key' => 'value']],
		];
	}

	/**
	 * @param bool       $expected
	 * @param string     $key
	 * @param array      $post
	 *
	 * @dataProvider getIssetDataProvider
	 */
	public function testGetIsset($expected, $key, $post)
	{
		if ($key === null) { // in order to test both get and post arrays
			$_POST = $post;
		} else {
			$_GET = $post;
		}
		$this->assertEquals($expected, Tools::getIsset($key));
	}

	// FIXME
	public function testSetCookieLanguage()
	{
//		$cookie = new Cookie('mockCookie', null, null, null, true);
//		$cookie->id_lang = Configuration::get('PS_LANG_DEFAULT');
//		$_SERVER['HTTP_HOST'] = 'server.domain';
//		$this->assertEquals('', Tools::setCookieLanguage($cookie));
		$this->assertTrue(true);
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
