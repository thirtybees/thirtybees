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
 * Class CountryCore
 */
class CountryCore extends ObjectModel
{
    /** @var int|null Object ID */
    public $id;
    /** @var int Zone id which country belongs */
    public $id_zone;
    /** @var int Currency id which country belongs */
    public $id_currency;
    /** @var string 2 letters iso code */
    public $iso_code;
    /** @var int international call prefix */
    public $call_prefix;
    /** @var string Name */
    public $name;
    /** @var bool Contain states */
    public $contains_states;
    /** @var bool Need identification number dni/nif/nie */
    public $need_identification_number;
    /** @var bool Need Zip Code */
    public $need_zip_code;
    /** @var string Zip Code Format */
    public $zip_code_format;
    /** @var bool Display or not the tax incl./tax excl. mention in the front office */
    public $display_tax_label = true;
    /** @var bool Status for delivery */
    public $active = true;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'        => 'country',
        'primary'      => 'id_country',
        'multilang'    => true,
        'fields'       => [
            'id_zone'                    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_currency'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '0'],
            'iso_code'                   => ['type' => self::TYPE_STRING, 'validate' => 'isLanguageIsoCode', 'required' => true, 'size' => 3],
            'call_prefix'                => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'int(10)', 'dbDefault' => '0'],
            'active'                     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'contains_states'            => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
            'need_identification_number' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
            'need_zip_code'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'zip_code_format'            => ['type' => self::TYPE_STRING, 'validate' => 'isZipCodeFormat', 'size' => 12, 'dbDefault' => ''],
            'display_tax_label'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbType' => 'tinyint(1)'],

            /* Lang fields */
            'name'                       => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
        ],
        'associations' => [
            'zone'     => ['type' => self::HAS_ONE],
            'currency' => ['type' => self::HAS_ONE],
        ],
        'keys' => [
            'country' => [
                'country_'         => ['type' => ObjectModel::KEY, 'columns' => ['id_zone']],
                'country_iso_code' => ['type' => ObjectModel::KEY, 'columns' => ['iso_code']],
            ],
            'country_shop' => [
                'id_shop' => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'objectsNodeName' => 'countries',
        'fields'          => [
            'id_zone'     => ['xlink_resource' => 'zones'],
            'id_currency' => ['xlink_resource' => 'currencies'],
        ],
    ];

    /**
     * @param int $idShop
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCountriesByIdShop($idShop, $idLang)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('country', 'c')
                ->leftJoin('country_shop', 'cs', 'cs.`id_country` = c.`id_country` AND cs.`id_shop` = '.(int) $idShop)
                ->leftJoin('country_lang', 'cl', 'cl.`id_country` = c.`id_country` AND cl.`id_lang` = '.(int) $idLang)
        );

        return is_array($result) ? $result : [];
    }

    /**
     * Get a country ID by its iso code
     *
     * @param string $isoCode Country iso code
     * @param bool $active return only active countries
     *
     * @return false|int Country ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getByIso($isoCode, $active = false)
    {
        if (Validate::isLanguageIsoCode($isoCode)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('`id_country`')
                    ->from('country')
                    ->where('`iso_code` = \'' . pSQL(strtoupper($isoCode)) . '\'')
                    ->where($active ? '`active` = 1' : '')
            );

            if (isset($result['id_country'])) {
                return (int)$result['id_country'];
            }
        }
        return false;
    }

    /**
     * @param int $idCountry
     *
     * @return int | false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getIdZone($idCountry)
    {
        $idCountry = (int)$idCountry;

        if (! $idCountry) {
            return false;
        }

        $key = 'country_getIdZone_' . $idCountry;
        if (!Cache::isStored($key)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('`id_zone`')
                    ->from('country')
                    ->where('`id_country` = ' . $idCountry)
            );

            $zoneId = (isset($result['id_zone']) && $result['id_zone'])
                ? (int)$result['id_zone']
                : false;

            Cache::store($key, $zoneId);

            return $zoneId;
        }

        return Cache::retrieve($key);
    }

    /**
     * Get a country name with its ID
     *
     * @param int $idLang Language ID
     * @param int $idCountry Country ID
     *
     * @return string | false Country name
     *
     * @throws PrestaShopException
     */
    public static function getNameById($idLang, $idCountry)
    {
        $idLang = (int)$idLang;
        $idCountry = (int)$idCountry;

        $key = 'country_getNameById_'.$idCountry.'_'.$idLang;
        if (!Cache::isStored($key)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`name`')
                    ->from('country_lang')
                    ->where('`id_lang` = '.$idLang)
                    ->where('`id_country` = '.$idCountry)
            );
            Cache::store($key, $result);

            return $result;
        }

        return Cache::retrieve($key);
    }

    /**
     * Get a country iso with its ID
     *
     * @param int $idCountry Country ID
     *
     * @return string | false Country iso
     *
     * @throws PrestaShopException
     */
    public static function getIsoById($idCountry)
    {
        $idCountry = (int)$idCountry;

        $key = 'country_getIsoById_' . $idCountry;
        if (!Cache::isStored($key)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`iso_code`')
                    ->from('country')
                    ->where('`id_country` = '.(int) $idCountry)
            );
            Cache::store($key, $result);

            return $result;
        }

        return Cache::retrieve($key);
    }

    /**
     * Get a country id with its name
     *
     * @param int|null $idLang Language ID
     * @param string $countryName Country Name
     *
     * @return int | false Country ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getIdByName($idLang, $countryName)
    {
        $idLang = (int) $idLang;

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_country`')
                ->from('country_lang')
                ->where('`name` = \''.pSQL($countryName).'\'')
                ->where($idLang ? '`id_lang` = '. $idLang : '')
        );

        if (isset($result['id_country'])) {
            return (int) $result['id_country'];
        }

        return false;
    }

    /**
     * @param int $idCountry
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function getNeedZipCode($idCountry)
    {
        if (!(int) $idCountry) {
            return false;
        }

        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`need_zip_code`')
                ->from('country')
                ->where('`id_country` = '.(int) $idCountry)
        );
    }

    /**
     * @param int $idCountry
     *
     * @return string | false
     *
     * @throws PrestaShopException
     */
    public static function getZipCodeFormat($idCountry)
    {
        $idCountry = (int)$idCountry;

        if (! $idCountry) {
            return false;
        }

        $zipCodeFormat = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`zip_code_format`')
                ->from('country')
                ->where('`id_country` = '.$idCountry)
        );

        if (isset($zipCodeFormat) && $zipCodeFormat) {
            return $zipCodeFormat;
        }

        return false;
    }

    /**
     * Returns the default country ID
     *
     * @deprecated 1.0.0 use $context->country->id instead
     * @return int default country id
     */
    public static function getDefaultCountryId()
    {
        Tools::displayAsDeprecated();

        return (int)Context::getContext()->country->id;
    }

    /**
     * @param int $idZone
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCountriesByZoneId($idZone, $idLang)
    {
        $idZone = (int)$idZone;
        $idLang = (int)$idLang;

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('c.*, cl.*')
                ->from('country', 'c')
                ->join(Shop::addSqlAssociation('country', 'c', false))
                ->leftJoin('state', 's', 's.`id_country` = c.`id_country`')
                ->leftJoin('country_lang', 'cl', 'c.`id_country` = cl.`id_country` AND cl.`id_lang` = '.$idLang)
                ->where('c.`id_zone` = '.$idZone.' OR s.`id_zone` = '.$idZone)
        );

        return is_array($result) ? $result : [];
    }

    /**
     * @param int $idCountry
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function containsStates($idCountry)
    {
        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`contains_states`')
                ->from('country')
                ->where('`id_country` = '.(int) $idCountry)
        );
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }

        return Db::getInstance()->delete('cart_rule_country', '`id_country` = '.(int) $this->id);
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function isNeedDni()
    {
        return Country::isNeedDniByCountryId($this->id);
    }

    /**
     * @param int $idCountry
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function isNeedDniByCountryId($idCountry)
    {
        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`need_identification_number`')
                ->from('country')
                ->where('`id_country` = '.(int) $idCountry)
        );
    }

    /**
     * @param array $idsCountries
     * @param int $idZone
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function affectZoneToSelection($idsCountries, $idZone)
    {
        // cast every array values to int (security)
        $idsCountries = array_map('intval', $idsCountries);

        return Db::getInstance()->update(
            'country',
            [
                'id_zone' => (int) $idZone,
            ],
            '`id_country` IN ('.implode(',', $idsCountries).')'
        );
    }

    /**
     * Replace letters of zip code format And check this format on the zip code
     *
     * @param string $zipCode
     *
     * @return bool
     */
    public function checkZipCode($zipCode)
    {
        $zipRegexp = '/^'.$this->zip_code_format.'$/ui';
        $zipRegexp = str_replace(' ', '( |)', $zipRegexp);
        $zipRegexp = str_replace('-', '(-|)', $zipRegexp);
        $zipRegexp = str_replace('N', '[0-9]', $zipRegexp);
        $zipRegexp = str_replace('L', '[a-zA-Z]', $zipRegexp);
        $zipRegexp = str_replace('C', $this->iso_code, $zipRegexp);

        return (bool) preg_match($zipRegexp, $zipCode);
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        return (
            parent::add($autoDate, $nullValues) &&
            static::addModuleRestrictions([], [['id_country' => $this->id]], [])
        );
    }

    /**
     * @param array $shops
     * @param array $countries
     * @param array $modules
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function addModuleRestrictions(array $shops = [], array $countries = [], array $modules = [])
    {
        if (!count($shops)) {
            $shops = Shop::getShops(true, null, true);
        }

        if (!count($countries)) {
            $countries = Country::getCountries((int) Context::getContext()->cookie->id_lang);
        }

        if (!count($modules)) {
            $modules = Module::getPaymentModules();
        }

        $insert = [];
        foreach ($shops as $idShop) {
            foreach ($countries as $country) {
                foreach ($modules as $module) {
                    $insert[] = [
                        'id_module'  => (int) $module['id_module'],
                        'id_shop'    => (int) $idShop,
                        'id_country' => (int) $country['id_country'],
                    ];
                }
            }
        }

        if (!empty($insert)) {
            return Db::getInstance()->insert('module_country', $insert, false, true, Db::INSERT_IGNORE);
        } else {
            return true;
        }
    }

    /**
     * Return available countries
     *
     * @param int $idLang Language ID
     * @param bool $active return only active countries
     * @param bool $containStates return only country with states
     * @param bool $listStates Include the states list with the returned list
     *
     * @return array Countries and corresponding zones
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCountries($idLang, $active = false, $containStates = false, $listStates = true)
    {
        $countries = [];
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cl.*, c.*, cl.`name` AS `country`, z.`name` AS `zone`')
                ->from('country', 'c')
                ->join(Shop::addSqlAssociation('country', 'c'))
                ->leftJoin('country_lang', 'cl', 'c.`id_country` = cl.`id_country` AND cl.`id_lang` = '.(int) $idLang)
                ->leftJoin('zone', 'z', 'z.`id_zone` = c.`id_zone`')
                ->where($active ? 'c.`active` = 1' : '')
                ->where($containStates ? 'c.`contains_states` = '.(int) $containStates : '')
                ->orderBy('cl.`name` ASC')
        );
        foreach ($result as $row) {
            $countries[$row['id_country']] = $row;
        }

        if ($listStates) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('*')
                    ->from('state')
                    ->orderBy('`name` ASC')
            );
            foreach ($result as $row) {
                if (isset($countries[$row['id_country']]) && $row['active'] == 1) { /* Does not keep the state if its country has been disabled and not selected */
                    $countries[$row['id_country']]['states'][] = $row;
                }
            }
        }

        return $countries;
    }
}
