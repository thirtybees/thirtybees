<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class AddressCore
 *
 * @since 1.0.0
 */
class AddressCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int Customer id which address belongs to */
    public $id_customer = null;

    /** @var int Manufacturer id which address belongs to */
    public $id_manufacturer = null;

    /** @var int Supplier id which address belongs to */
    public $id_supplier = null;

    /**
     * @since 1.5.0
     * @var int Warehouse id which address belongs to
     */
    public $id_warehouse = null;

    /** @var int Country id */
    public $id_country;

    /** @var int State id */
    public $id_state;

    /** @var string Country name */
    public $country;

    /** @var string Alias (eg. Home, Work...) */
    public $alias;

    /** @var string Company (optional) */
    public $company;

    /** @var string Lastname */
    public $lastname;

    /** @var string Firstname */
    public $firstname;

    /** @var string Address first line */
    public $address1;

    /** @var string Address second line (optional) */
    public $address2;

    /** @var string Postal code */
    public $postcode;

    /** @var string City */
    public $city;

    /** @var string Any other useful information */
    public $other;

    /** @var string Phone number */
    public $phone;

    /** @var string Mobile phone number */
    public $phone_mobile;

    /** @var string VAT number */
    public $vat_number;

    /** @var string DNI number */
    public $dni;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    /** @var bool True if address has been deleted (staying in database as deleted) */
    public $deleted = 0;

    protected static $_idZones = [];
    protected static $_idCountries = [];

    protected $_includeContainer = false;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'address',
        'primary' => 'id_address',
        'fields'  => [
            'id_customer'     => ['type' => self::TYPE_INT,    'validate' => 'isNullOrUnsignedId', 'copy_post' => false                                    ],
            'id_manufacturer' => ['type' => self::TYPE_INT,    'validate' => 'isNullOrUnsignedId', 'copy_post' => false                                    ],
            'id_supplier'     => ['type' => self::TYPE_INT,    'validate' => 'isNullOrUnsignedId', 'copy_post' => false                                    ],
            'id_warehouse'    => ['type' => self::TYPE_INT,    'validate' => 'isNullOrUnsignedId', 'copy_post' => false                                    ],
            'id_country'      => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',                              'required' => true               ],
            'id_state'        => ['type' => self::TYPE_INT,    'validate' => 'isNullOrUnsignedId'                                                          ],
            'alias'           => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName',                             'required' => true, 'size' => 32 ],
            'company'         => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName',                                                 'size' => 64 ],
            'lastname'        => ['type' => self::TYPE_STRING, 'validate' => 'isName',                                    'required' => true, 'size' => 32 ],
            'firstname'       => ['type' => self::TYPE_STRING, 'validate' => 'isName',                                    'required' => true, 'size' => 32 ],
            'vat_number'      => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'                                                               ],
            'address1'        => ['type' => self::TYPE_STRING, 'validate' => 'isAddress',                                 'required' => true, 'size' => 128],
            'address2'        => ['type' => self::TYPE_STRING, 'validate' => 'isAddress',                                                     'size' => 128],
            'postcode'        => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode',                                                    'size' => 12 ],
            'city'            => ['type' => self::TYPE_STRING, 'validate' => 'isCityName',                                'required' => true, 'size' => 64 ],
            'other'           => ['type' => self::TYPE_STRING, 'validate' => 'isMessage',                                                     'size' => 300],
            'phone'           => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber',                                                 'size' => 32 ],
            'phone_mobile'    => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber',                                                 'size' => 32 ],
            'dni'             => ['type' => self::TYPE_STRING, 'validate' => 'isDniLite',                                                     'size' => 16 ],
            'deleted'         => ['type' => self::TYPE_BOOL,   'validate' => 'isBool',             'copy_post' => false                                    ],
            'date_add'        => ['type' => self::TYPE_DATE,   'validate' => 'isDate',             'copy_post' => false                                    ],
            'date_upd'        => ['type' => self::TYPE_DATE,   'validate' => 'isDate',             'copy_post' => false                                    ],
        ],
    ];

    protected $webserviceParameters = [
        'objectsNodeName' => 'addresses',
        'fields'          => [
            'id_customer'     => ['xlink_resource' => 'customers'],
            'id_manufacturer' => ['xlink_resource' => 'manufacturers'],
            'id_supplier'     => ['xlink_resource' => 'suppliers'],
            'id_warehouse'    => ['xlink_resource' => 'warehouse'],
            'id_country'      => ['xlink_resource' => 'countries'],
            'id_state'        => ['xlink_resource' => 'states'],
        ],
    ];

    /**
     * Build an address
     *
     * @param int $idAddress Existing address id in order to load object (optional)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($idAddress = null, $idLang = null)
    {
        parent::__construct($idAddress);

        /* Get and cache address country name */
        if ($this->id) {
            $this->country = Country::getNameById($idLang ? $idLang : Configuration::get('PS_LANG_DEFAULT'), $this->id_country);
        }
    }

    /**
     * @see     ObjectModel::add()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autodate = true, $nullValues = false)
    {
        if (!parent::add($autodate, $nullValues)) {
            return false;
        }

        if (Validate::isUnsignedId($this->id_customer)) {
            Customer::resetAddressCache($this->id_customer, $this->id);
        }

        return true;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($nullValues = false)
    {
        // Empty related caches
        if (isset(static::$_idCountries[$this->id])) {
            unset(static::$_idCountries[$this->id]);
        }
        if (isset(static::$_idZones[$this->id])) {
            unset(static::$_idZones[$this->id]);
        }

        if (Validate::isUnsignedId($this->id_customer)) {
            Customer::resetAddressCache($this->id_customer, $this->id);
        }

        return parent::update($nullValues);
    }

    /**
     * @see     ObjectModel::delete()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if (Validate::isUnsignedId($this->id_customer)) {
            Customer::resetAddressCache($this->id_customer, $this->id);
        }

        if (!$this->isUsed()) {
            return parent::delete();
        } else {
            $this->deleted = true;

            return $this->update();
        }
    }

    /**
     * Returns fields required for an address in an array hash
     *
     * @return array hash values.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFieldsValidate()
    {
        $tmpAddr = new Address();
        $out = $tmpAddr->fieldsValidate;

        unset($tmpAddr);

        return $out;
    }

    /**
     * @see     ObjectModel::validateController()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function validateController($htmlentities = true)
    {
        $errors = parent::validateController($htmlentities);
        if (!Configuration::get('VATNUMBER_MANAGEMENT') || !Configuration::get('VATNUMBER_CHECKING')) {
            return $errors;
        }
        include_once(_PS_MODULE_DIR_.'vatnumber/vatnumber.php');
        if (class_exists('VatNumber', false)) {
            return array_merge($errors, VatNumber::WebServiceCheck($this->vat_number));
        }

        return $errors;
    }

    /**
     * Get zone id for a given address
     *
     * @param int $idAddress Address id for which we want to get zone id
     *
     * @return int Zone id
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getZoneById($idAddress)
    {
        if (!isset($idAddress) || empty($idAddress)) {
            return false;
        }

        if (isset(static::$_idZones[$idAddress])) {
            return static::$_idZones[$idAddress];
        }

        $idZone = Hook::exec('actionGetIDZoneByAddressID', ['id_address' => $idAddress]);

        if (is_numeric($idZone)) {
            static::$_idZones[$idAddress] = (int) $idZone;

            return static::$_idZones[$idAddress];
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            '
			SELECT s.`id_zone` AS id_zone_state, c.`id_zone`
			FROM `'._DB_PREFIX_.'address` a
			LEFT JOIN `'._DB_PREFIX_.'country` c ON c.`id_country` = a.`id_country`
			LEFT JOIN `'._DB_PREFIX_.'state` s ON s.`id_state` = a.`id_state`
			WHERE a.`id_address` = '.(int) $idAddress
        );

        static::$_idZones[$idAddress] = (int) ((int) $result['id_zone_state'] ? $result['id_zone_state'] : $result['id_zone']);

        return static::$_idZones[$idAddress];
    }

    /**
     * Check if country is active for a given address
     *
     * @param int $idAddress Address id for which we want to get country status
     *
     * @return int Country status
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isCountryActiveById($idAddress)
    {
        if (!isset($idAddress) || empty($idAddress)) {
            return false;
        }

        $cacheId = 'Address::isCountryActiveById_'.(int) $idAddress;
        if (!Cache::isStored($cacheId)) {
            $result = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getvalue(
                '
			SELECT c.`active`
			FROM `'._DB_PREFIX_.'address` a
			LEFT JOIN `'._DB_PREFIX_.'country` c ON c.`id_country` = a.`id_country`
			WHERE a.`id_address` = '.(int) $idAddress
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Check if address is used (at least one order placed)
     *
     * @return int Order count for this address
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function isUsed()
    {
        $result = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
		SELECT COUNT(`id_order`) AS used
		FROM `'._DB_PREFIX_.'orders`
		WHERE `id_address_delivery` = '.(int) $this->id.'
		OR `id_address_invoice` = '.(int) $this->id
        );

        return $result > 0 ? (int) $result : false;
    }

    /**
     * @param $idAddress
     *
     * @return array|bool|mixed|null|object
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCountryAndState($idAddress)
    {
        if (isset(static::$_idCountries[$idAddress])) {
            return static::$_idCountries[$idAddress];
        }
        if ($idAddress) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                '
			SELECT `id_country`, `id_state`, `vat_number`, `postcode` FROM `'._DB_PREFIX_.'address`
			WHERE `id_address` = '.(int) $idAddress
            );
        } else {
            $result = false;
        }
        static::$_idCountries[$idAddress] = $result;

        return $result;
    }

    /**
     * Specify if an address is already in base
     *
     * @param int $idAddress Address id
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function addressExists($idAddress)
    {
        $key = 'address_exists_'.(int) $idAddress;
        if (!Cache::isStored($key)) {
            $idAddress = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT `id_address` FROM '._DB_PREFIX_.'address a WHERE a.`id_address` = '.(int) $idAddress);
            Cache::store($key, (bool) $idAddress);

            return (bool) $idAddress;
        }

        return Cache::retrieve($key);
    }

    /**
     * @param      $idCustomer
     * @param bool $active
     *
     * @return bool|int|null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFirstCustomerAddressId($idCustomer, $active = true)
    {
        if (!$idCustomer) {
            return false;
        }
        $cacheId = 'Address::getFirstCustomerAddressId_'.(int) $idCustomer.'-'.(bool) $active;
        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                '
				SELECT `id_address`
				FROM `'._DB_PREFIX_.'address`
				WHERE `id_customer` = '.(int) $idCustomer.' AND `deleted` = 0'.($active ? ' AND `active` = 1' : '')
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Initiliaze an address corresponding to the specified id address or if empty to the
     * default shop configuration
     *
     * @param int  $idAddress
     * @param bool $withGeoLocation
     *
     * @return Address address
     *
     * @throws PrestaShopException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function initialize($idAddress = null, $withGeoLocation = false)
    {
        $context = Context::getContext();
        $exists = (int) $idAddress && (bool) Address::addressExists($idAddress);
        if ($exists) {
            $contextHash = (int) $idAddress;
        } elseif ($withGeoLocation && isset($context->customer->geoloc_id_country)) {
            $contextHash = md5(
                (int) $context->customer->geoloc_id_country.'-'.(int) $context->customer->id_state.'-'.
                $context->customer->postcode
            );
        } else {
            $contextHash = md5((int) $context->country->id);
        }

        $cacheId = 'Address::initialize_'.$contextHash;

        if (!Cache::isStored($cacheId)) {
            // if an id_address has been specified retrieve the address
            if ($exists) {
                $address = new Address((int) $idAddress);

                if (!Validate::isLoadedObject($address)) {
                    throw new PrestaShopException('Invalid address #'.(int) $idAddress);
                }
            } elseif ($withGeoLocation && isset($context->customer->geoloc_id_country)) {
                $address = new Address();
                $address->id_country = (int) $context->customer->geoloc_id_country;
                $address->id_state = (int) $context->customer->id_state;
                $address->postcode = $context->customer->postcode;
            } else {
                // set the default address
                $address = new Address();
                $address->id_country = (int) $context->country->id;
                $address->id_state = 0;
                $address->postcode = 0;
            }
            Cache::store($cacheId, $address);

            return $address;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Returns id_address for a given id_supplier
     *
     * @param int $idSupplier
     *
     * @return int $id_address
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAddressIdBySupplierId($idSupplier)
    {
        $query = new DbQuery();
        $query->select('id_address');
        $query->from('address');
        $query->where('id_supplier = '.(int) $idSupplier);
        $query->where('deleted = 0');
        $query->where('id_customer = 0');
        $query->where('id_manufacturer = 0');
        $query->where('id_warehouse = 0');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * @param $alias
     * @param $idAddress
     * @param $idCustomer
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function aliasExist($alias, $idAddress, $idCustomer)
    {
        $query = new DbQuery();
        $query->select('count(*)');
        $query->from('address');
        $query->where('alias = \''.pSQL($alias).'\'');
        $query->where('id_address != '.(int) $idAddress);
        $query->where('id_customer = '.(int) $idCustomer);
        $query->where('deleted = 0');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFieldsRequiredDB()
    {
        $this->cacheFieldsRequiredDatabase(false);
        if (isset(static::$fieldsRequiredDatabase['Address'])) {
            return static::$fieldsRequiredDatabase['Address'];
        }

        return [];
    }
}
