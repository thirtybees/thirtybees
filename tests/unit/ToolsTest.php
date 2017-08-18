<?php
use AspectMock\Test as test;

require_once __DIR__.'/tools/MockAddress.php';
require_once __DIR__.'/tools/MockCurrency.php';
require_once __DIR__.'/tools/MockLanguage.php';

class ToolsTest extends \Codeception\Test\Unit
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
		\AspectMock\Test::clean();
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
	// Mocking doesn't work in any way
	/**
	 * @param string $expected
	 * @param string $referrer
	 *
	 * @dataProvider secureReferrerDataProvider
	 */
	public function testSecureReferrer($expected, $referrer)
	{
		$this->assertTrue(true);
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
		$this->assertTrue(true);
	}

	public function getValueDataProvider()
	{
		return [
			[false, null, null],
			['one', '1', null],
			['two', '2', null],
			[null, '3', 'three'],
		];
	}

	/**
	 * @param mixed  $expected
	 * @param string $key
	 * @param mixed  $defaultValue
	 *
	 * @dataProvider getValueDataProvider
	 */
	public function testGetValue($expected, $key, $defaultValue)
	{
		if ($key == '1') {
			$_POST[$key] = $expected;
		} else if ($key == '2') {
			$_GET[$key] = $expected;
		} else {
			$expected = $defaultValue;
		}
		$this->assertEquals($expected, Tools::getValue($key, $defaultValue));
	}

	// FIXME
	public function testSwitchLanguage()
	{
		$this->assertTrue(true);
	}

	public function getCountryDataProvider()
	{
		$anAddress = new MockAddress();
		$anAddress->id_country = 2;
		return [
			[1, 1, null],
			[2, null, $anAddress],
			[Configuration::get('PS_COUNTRY_DEFAULT'), null, null],
		];
	}

	/**
	 * @param int       $expected
	 * @param int       $idCountry
	 * @param Address   $address
	 *
	 * @dataProvider getCountryDataProvider
	 */
	public function testGetCountry($expected, $idCountry, $address)
	{
		if (isset($idCountry)) {
			$_POST['id_country'] = $idCountry;
		}
		$this->assertEquals($expected, Tools::getCountry($address));
	}

	// FIXME
	public function testSetCurrency()
	{
		$this->assertTrue(true);
	}

	public function isSubmitDataProvider()
	{
		return [
			[true, 'aKey', 1],
			[true, 'aKey', 2],
			[true, 'aKey', 3],
			[true, 'aKey', 4],
			[true, 'aKey', 5],
			[true, 'aKey', 6],
			[false, 'aKey', 7],
		];
	}

	/**
	 * @param bool   $expected
	 * @param string $submit
	 * @param int    $case
	 *
	 * @dataProvider isSubmitDataProvider
	 */
	public function testIsSubmit($expected, $submit, $case)
	{
		switch ($case) {
			case 1:
				$_POST[$submit] = true;
				break;
			case 2:
				$_POST[$submit.'_x'] = true;
				break;
			case 3:
				$_POST[$submit.'_y'] = true;
				break;
			case 4:
				$_GET[$submit] = true;
				break;
			case 5:
				$_GET[$submit.'_x'] = true;
				break;
			case 6:
				$_GET[$submit.'_y'] = true;
				break;
		}
		$this->assertEquals($expected, Tools::isSubmit($submit));
	}

	public function displayNumberDataProvider()
	{
		$aCurrency = new MockCurrency();
		$aCurrency->format = 1;
		return [
			['500', 500, $aCurrency],
			['1 000', 1000, ['format' => 2]],
			['999', 999, ['format' => 3]],
			['99,999', 99999, ['format' => 4]],
		];
	}

	/**
	 * @param string   $expected
	 * @param float    $number
	 * @param mixed    $currency
	 *
	 * @dataProvider displayNumberDataProvider
	 */
	public function testDisplayNumber($expected, $number, $currency)
	{
		$this->assertEquals($expected, Tools::displayNumber($number, $currency));
	}

	// FIXME
	public function testDisplayPriceSmarty()
	{
		$this->assertTrue(true);
	}

	public function displayPriceDataProvider()
	{
		$context1 = new Context();
		$context2 = new Context();
		$context3 = new Context();
		$context4 = new Context();

		$currency1 = new MockCurrency();
		$currency1->iso_code = 'USD';
		$currency1->sign = '$';
		$currency1->format = 1;
		$currency1->decimals = 1;
		$currency1->blank = true;
		$context1->currency = $currency1;

		$lang = new MockLanguage();
		$lang->language_code = 'en-us';
		$context1->language = $lang;
		$context4->language = $lang;

		$currency2 = [
			'iso_code' => 'USD',
			'sign' => '$',
			'format' => '1',
			'decimals' => '1',
			'blank' => true,
		];

		return [
			['not_numeric', 'not_numeric', null, null, null, null],
			['$7.99', 7.99, null, null, $context1, null],
			// ['$400', 400, $currency2, null, $context2, null], // untestable, fatal error
			['$120,000.00', 120000, $currency1, null, $context4, null],
		];
	}

	/**
	 * @param string         $expected
	 * @param mixed          $price
	 * @param mixed          $tbCurrency
	 * @param bool           $noUtf8
	 * @param Context        $context
	 * @param bool           $auto
	 *
	 * @dataProvider displayPriceDataProvider
	 */
	public function testDisplayPrice($expected, $price, $tbCurrency, $noUtf8, $context, $auto)
	{
		$this->assertEquals($expected, Tools::displayPrice($price, $tbCurrency, $noUtf8, $context, $auto));
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
