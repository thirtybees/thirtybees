<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class CurrencyCore
 *
 * @since 1.0.0
 */
class CurrencyCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var array Currency cache */
    protected static $currencies = [];
    protected static $countActiveCurrencies = [];
    public $id;
    /** @var string Name */
    public $name;
    /** @var string Iso code */
    public $iso_code;
    /** @var string Iso code numeric */
    public $iso_code_num;
    /** @var string Symbol for short display */
    public $sign;
    /** @var int bool used for displaying blank between sign and price */
    public $blank;
    /** @var string exchange rate from euros */
    public $conversion_rate;
    /** @var bool True if currency has been deleted (staying in database as deleted) */
    public $deleted = 0;
    /** @var int ID used for displaying prices */
    public $format;
    /** @var int bool Display decimals on prices */
    public $decimals;
    /** @var int bool active */
    public $active;
    /** @var bool $auto_format */
    public $auto_format;
    /**
     * contains the sign to display before price, according to its format
     *
     * @var string
     */
    public $prefix = null;
    /**
     * contains the sign to display after price, according to its format
     *
     * @var string
     */
    public $suffix = null;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'          => 'currency',
        'primary'        => 'id_currency',
        'multilang_shop' => true,
        'fields'         => [
            'name'            => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'iso_code'        => ['type' => self::TYPE_STRING, 'validate' => 'isLanguageIsoCode', 'required' => true, 'size' => 3],
            'iso_code_num'    => ['type' => self::TYPE_STRING, 'validate' => 'isNumericIsoCode', 'size' => 3],
            'blank'           => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'sign'            => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 8],
            'format'          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'decimals'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'conversion_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => true, 'shop' => true],
            'deleted'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];

    protected $webserviceParameters = [
        'objectsNodeName' => 'currencies',
    ];

    /**
     * CurrencyCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
        // prefix and suffix are convenient shortcut for displaying
        // price sign before or after the price number
        $this->prefix = $this->format % 2 != 0 ? $this->sign.' ' : '';
        $this->suffix = $this->format % 2 == 0 ? ' '.$this->sign : '';
        if (!$this->conversion_rate) {
            $this->conversion_rate = 1;
        }

        $this->auto_format = $this->getMode();
    }

    /**
     * @param int $idShop
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCurrenciesByIdShop($idShop = 0)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('currency', 'c')
                ->leftJoin('currency_shop', 'cs', 'cs.`id_currency` = c.`id_currency`')
                ->where($idShop ? 'cs.`id_shop` = '.(int) $idShop : '')
                ->orderBy('`name` ASC')
        );
    }

    /**
     * @param int      $idModule
     * @param int|null $idShop
     *
     * @return array|bool|null|object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getPaymentCurrenciesSpecial($idModule, $idShop = null)
    {
        if (is_null($idShop)) {
            $idShop = Context::getContext()->shop->id;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('module_currency')
                ->where('`id_module` = '.(int) $idModule)
                ->where('`id_shop` = '.(int) $idShop)
        );
    }

    /**
     * @param int      $idModule
     * @param int|null $idShop
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getPaymentCurrencies($idModule, $idShop = null)
    {
        if (is_null($idShop)) {
            $idShop = Context::getContext()->shop->id;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('c.*')
                ->from('module_currency', 'mc')
                ->leftJoin('currency', 'c', 'c.`id_currency` = mc.`id_currency`')
                ->where('c.`deleted` = 0')
                ->where('mc.`id_module` = '.(int) $idModule)
                ->where('c.`active` = 1')
                ->where('mc.`id_shop` = '.(int) $idShop)
                ->orderBy('c.`name` ASC')
        );
    }

    /**
     * @param int      $idModule
     * @param int|null $idShop
     *
     * @return array|bool|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function checkPaymentCurrencies($idModule, $idShop = null)
    {
        if (empty($idModule)) {
            return false;
        }

        if (is_null($idShop)) {
            $idShop = Context::getContext()->shop->id;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('module_currency')
                ->where('`id_module` = '.(int) $idModule)
                ->where('`id_shop` = '.(int) $idShop)
        );
    }

    /**
     * @param int $idCurrency
     *
     * @return array|bool|null|object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCurrency($idCurrency)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('currency')
                ->where('`deleted` = 0')
                ->where('`id_currency` = '.(int) $idCurrency)
        );
    }

    /**
     * @return string|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function refreshCurrencies()
    {
        if (!$defaultCurrency = Currency::getDefaultCurrency()) {
            return Tools::displayError('No default currency');
        }

        $currencyRates = CurrencyRateModule::getCurrencyRateInfo();
        if (!is_array($currencyRates)) {
            return null;
        }
        $currencyRates = array_filter($currencyRates);
        $moduleRates = [];
        foreach ($currencyRates as $currency => $module) {
            if (mb_strtoupper($currency) === mb_strtoupper($defaultCurrency->iso_code)) {
                continue;
            }
            if (!isset($moduleRates[$module->id])) {
                $moduleRates[$module->id] = [mb_strtoupper($currency)];
            } else {
                $moduleRates[$module->id][] = mb_strtoupper($currency);
            }
        }

        foreach ($moduleRates as $idModule => $currencies) {
            $response = Hook::exec('actionRetrieveCurrencyRates', ['currencies' => $currencies, 'baseCurrency' => mb_strtoupper($defaultCurrency->iso_code)], $idModule, true);
            if (!is_array($response)) {
                continue;
            }
            foreach ($response as $rates) {
                foreach ($rates as $isoCode => $rate) {
                    $currency = Currency::getCurrencyInstance(Currency::getIdByIsoCode($isoCode));
                    $currency->conversion_rate = $rate;

                    $currency->save();
                }
            }
        }

        return null;
    }

    /**
     * @return bool|Currency
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getDefaultCurrency()
    {
        $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        if ($idCurrency == 0) {
            return false;
        }

        return new Currency($idCurrency);
    }

    /**
     * Return available currencies
     *
     * @param bool $object
     * @param bool $active
     * @param bool $groupBy
     *
     * @return array Currencies
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCurrencies($object = false, $active = true, $groupBy = false)
    {
        $tab = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('currency', 'c')
                ->join(Shop::addSqlAssociation('currency', 'c'))
                ->where('`deleted` = 0')
                ->where($active ? 'c.`active` = 1' : '')
                ->groupBy($groupBy ? 'c.`id_currency`' : '')
                ->orderBy('`name` ASC')
        );
        if ($object) {
            foreach ($tab as $key => $currency) {
                $tab[$key] = Currency::getCurrencyInstance($currency['id_currency']);
            }
        }

        return $tab;
    }

    /**
     * @param int $id
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCurrencyInstance($id)
    {
        if (!isset(static::$currencies[$id])) {
            static::$currencies[(int) ($id)] = new Currency($id);
        }

        return static::$currencies[(int) ($id)];
    }

    /**
     * Refresh the currency exchange rate
     * The XML file define exchange rate for each from a default currency ($isoCodeSource).
     *
     * @param SimpleXMLElement $data            XML content which contains all the exchange rates
     * @param string           $isoCodeSource   The default currency used in the XML file
     * @param Currency         $defaultCurrency The default currency object
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @deprecated 1.0.0
     */
    public function refreshCurrency($data, $isoCodeSource, $defaultCurrency)
    {
        // fetch the exchange rate of the default currency
        $exchangeRate = 1;
        $tmp = $this->conversion_rate;
        if ($defaultCurrency->iso_code != $isoCodeSource) {
            foreach ($data->currency as $currency) {
                if ($currency['iso_code'] == $defaultCurrency->iso_code) {
                    $exchangeRate = round((float) $currency['rate'], 6);
                    break;
                }
            }
        }

        if ($defaultCurrency->iso_code == $this->iso_code) {
            $this->conversion_rate = 1;
        } else {
            if ($this->iso_code == $isoCodeSource) {
                $rate = 1;
            } else {
                foreach ($data->currency as $obj) {
                    if ($this->iso_code == strval($obj['iso_code'])) {
                        $rate = (float) $obj['rate'];
                        break;
                    }
                }
            }

            if (isset($rate)) {
                $this->conversion_rate = round($rate / $exchangeRate, 6);
            }
        }

        if ($tmp != $this->conversion_rate) {
            $this->update();
        }
    }

    /**
     * Get current currency
     *
     * @deprecated 1.0.0 use $context->currency instead
     * @return Currency
     */
    public static function getCurrent()
    {
        Tools::displayAsDeprecated();

        return Context::getContext()->currency;
    }

    /**
     * @param int|null $idShop
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isMultiCurrencyActivated($idShop = null)
    {
        return (Currency::countActiveCurrencies($idShop) > 1);
    }

    /**
     * @param null $idShop
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function countActiveCurrencies($idShop = null)
    {
        if ($idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        if (!isset(static::$countActiveCurrencies[$idShop])) {
            static::$countActiveCurrencies[$idShop] = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                ->select('COUNT(DISTINCT c.`id_currency`)')
                ->from('currency', 'c')
                ->leftJoin('currency_shop', 'cs', 'cs.`id_currency` = c.`id_currency`')
                ->where('cs.`id_shop` = '.(int) $idShop)
                ->where('c.`active` = 1')
            );
        }

        return static::$countActiveCurrencies[$idShop];
    }

    /**
     * Overriding check if currency rate is not empty and if currency with the same iso code already exists.
     * If it's true, currency is not added.
     *
     * @see ObjectModelCore::add()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if ((float) $this->conversion_rate <= 0) {
            return false;
        }

        if (static::exists($this->iso_code, $this->iso_code_num)) {
            return false;
        }

        parent::add($autoDate, $nullValues);

        CurrencyRateModule::scanMissingCurrencyRateModules($this->iso_code);

        return true;
    }

    /**
     * Check if a curency already exists.
     *
     * @param int|string $isoCode int for iso code number string for iso code
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function exists($isoCode, $isoCodeNum, $idShop = 0)
    {
        if (is_int($isoCode)) {
            $idCurrencyExists = Currency::getIdByIsoCodeNum((int) $isoCodeNum, (int) $idShop);
        } else {
            $idCurrencyExists = Currency::getIdByIsoCode($isoCode, (int) $idShop);
        }

        if ($idCurrencyExists) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param     $isoCodeNum
     * @param int $idShop
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getIdByIsoCodeNum($isoCodeNum, $idShop = 0)
    {
        $query = Currency::getIdByQuery($idShop);
        $query->where('iso_code_num = \''.pSQL($isoCodeNum).'\'');

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query->build());
    }

    /**
     * @param int $idShop
     *
     * @return DbQuery
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIdByQuery($idShop = 0)
    {
        $query = new DbQuery();
        $query->select('c.id_currency');
        $query->from('currency', 'c');
        $query->where('deleted = 0');

        if (Shop::isFeatureActive() && $idShop > 0) {
            $query->leftJoin('currency_shop', 'cs', 'cs.id_currency = c.id_currency');
            $query->where('id_shop = '.(int) $idShop);
        }

        return $query;
    }

    /**
     * @param     $isoCode
     * @param int $idShop
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getIdByIsoCode($isoCode, $idShop = 0)
    {
        $cacheId = 'Currency::getIdByIsoCode_'.pSQL($isoCode).'-'.(int) $idShop;
        if (!Cache::isStored($cacheId)) {
            $query = Currency::getIdByQuery($idShop);
            $query->where('iso_code = \''.pSQL($isoCode).'\'');

            $result = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query->build());
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @param array $selection
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public function deleteSelection($selection)
    {
        if (!is_array($selection)) {
            return false;
        }

        $res = [];
        foreach ($selection as $id) {
            $obj = new Currency((int) $id);
            $res[$id] = $obj->delete();
        }

        foreach ($res as $value) {
            if (!$value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if ($this->id == Configuration::get('PS_CURRENCY_DEFAULT')) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('`id_currency`')
                    ->from('currency')
                    ->where('`id_currency` != '.(int) $this->id)
                    ->where('`deleted` = 0')
            );
            if (!$result['id_currency']) {
                return false;
            }
            Configuration::updateValue('PS_CURRENCY_DEFAULT', $result['id_currency']);
        }
        $this->deleted = 1;

        $res = (bool) Db::getInstance()->delete('module_currency', '`id_currency` = '.(int) $this->id);

        Db::getInstance()->delete('currency_module', '`id_currency` = '.(int) $this->id);

        return $res && $this->update();
    }

    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($autodate = true, $nullValues = false)
    {
        if ((float) $this->conversion_rate <= 0) {
            return false;
        }

        return parent::update($nullValues);
    }

    /**
     * Return formated sign
     *
     * @param string $side left or right
     *
     * @return string formated sign
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getSign($side = null)
    {
        if (!$side) {
            return $this->sign;
        }
        $formattedStrings = [
            'left'  => $this->sign.' ',
            'right' => ' '.$this->sign,
        ];

        $formats = [
            1 => ['left' => &$formattedStrings['left'], 'right' => ''],
            2 => ['left' => '', 'right' => &$formattedStrings['right']],
            3 => ['left' => &$formattedStrings['left'], 'right' => ''],
            4 => ['left' => '', 'right' => &$formattedStrings['right']],
            5 => ['left' => '', 'right' => &$formattedStrings['right']],
        ];
        if (isset($formats[$this->format][$side])) {
            return ($formats[$this->format][$side]);
        }

        return $this->sign;
    }

    /**
     * @return int|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getConversationRate()
    {
        return $this->id != (int) Configuration::get('PS_CURRENCY_DEFAULT') ? $this->conversion_rate : 1;
    }

    /**
     * Should the currency be automatically formatted?
     *
     * @return bool
     *
     * @since 1.0.2
     * @throws PrestaShopException
     */
    public function getMode()
    {
        return !Configuration::get('TB_NO_AUTO_FORMAT_'.(int) $this->id);
    }

    /**
     * Get the modes for all currencies
     * NOTE: the keys in this array are the uppercased ISO codes
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.2
     */
    public static function getModes()
    {
        $modes = [];
        foreach (static::getCurrencies(false, false) as $currency) {
            $currencyInstance = Currency::getCurrencyInstance((int) $currency['id_currency']);

            $modes[strtoupper($currency['iso_code'])] = $currencyInstance->getMode();
        }

        return $modes;
    }
}
