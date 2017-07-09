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
 * Class CustomerCore
 *
 * @since 1.0.0
 */
class CustomerCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'customer',
        'primary' => 'id_customer',
        'fields'  => [
            'secure_key'                 => ['type' => self::TYPE_STRING, 'validate' => 'isMd5', 'copy_post' => false],
            'lastname'                   => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32],
            'firstname'                  => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32],
            'email'                      => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 128],
            'passwd'                     => ['type' => self::TYPE_STRING, 'validate' => 'isPasswd', 'required' => true, 'size' => 60],
            'last_passwd_gen'            => ['type' => self::TYPE_STRING, 'copy_post' => false],
            'id_gender'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'birthday'                   => ['type' => self::TYPE_DATE, 'validate' => 'isBirthDate'],
            'newsletter'                 => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'newsletter_date_add'        => ['type' => self::TYPE_DATE, 'copy_post' => false],
            'ip_registration_newsletter' => ['type' => self::TYPE_STRING, 'copy_post' => false],
            'optin'                      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'website'                    => ['type' => self::TYPE_STRING, 'validate' => 'isUrl'],
            'company'                    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'siret'                      => ['type' => self::TYPE_STRING, 'validate' => 'isSiret'],
            'ape'                        => ['type' => self::TYPE_STRING, 'validate' => 'isApe'],
            'outstanding_allow_amount'   => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'copy_post' => false],
            'show_public_prices'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
            'id_risk'                    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'copy_post' => false],
            'max_payment_days'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'copy_post' => false],
            'active'                     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
            'deleted'                    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
            'note'                       => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'copy_post' => false, 'size' => 65000],
            'is_guest'                   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
            'id_shop'                    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
            'id_shop_group'              => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
            'id_default_group'           => ['type' => self::TYPE_INT, 'copy_post' => false],
            'id_lang'                    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
            'date_add'                   => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'date_upd'                   => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
        ],
    ];
    protected static $_defaultGroupId = [];
    protected static $_customerHasAddress = [];
    protected static $_customer_groups = [];
    public $id_shop_group;
    /** @var string Secure key */
    public $secure_key;
    /** @var string protected note */
    public $note;
    /** @var int Gender ID */
    public $id_gender = 0;
    /** @var int Default group ID */
    public $id_default_group;
    /** @var int Current language used by the customer */
    public $id_lang;
    /** @var string Lastname */
    public $lastname;
    /** @var string Firstname */
    public $firstname;
    /** @var string Birthday (yyyy-mm-dd) */
    public $birthday = null;
    /** @var string e-mail */
    public $email;
    /** @var bool Newsletter subscription */
    public $newsletter;
    /** @var string Newsletter ip registration */
    public $ip_registration_newsletter;
    /** @var string Newsletter ip registration */
    public $newsletter_date_add;
    /** @var bool Opt-in subscription */
    public $optin;
    /** @var string WebSite * */
    public $website;
    /** @var string Company */
    public $company;
    /** @var string SIRET */
    public $siret;
    /** @var string APE */
    public $ape;
    /** @var float Outstanding allow amount (B2B opt) */
    public $outstanding_allow_amount = 0;
    /** @var int Show public prices (B2B opt) */
    public $show_public_prices = 0;
    /** @var int Risk ID (B2B opt) */
    public $id_risk;
    /** @var int Max payment day */
    public $max_payment_days = 0;
    /** @var int Password */
    public $passwd;
    /** @var string Datetime Password */
    public $last_passwd_gen;
    /** @var bool Status */
    public $active = true;
    /** @var bool Status */
    public $is_guest = 0;
    /** @var bool True if carrier has been deleted (staying in database as deleted) */
    public $deleted = 0;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    public $years;
    public $days;
    public $months;
    /** @var int customer id_country as determined by geolocation */
    public $geoloc_id_country;
    /** @var int customer id_state as determined by geolocation */
    public $geoloc_id_state;
    /** @var string customer postcode as determined by geolocation */
    public $geoloc_postcode;
    /** @var bool is the customer logged in */
    public $logged = 0;
    /** @var int id_guest meaning the guest table, not the guest customer */
    public $id_guest;
    // @codingStandardsIgnoreEnd
    public $groupBox;
    protected $webserviceParameters = [
        'fields'       => [
            'id_default_group'           => ['xlink_resource' => 'groups'],
            'id_lang'                    => ['xlink_resource' => 'languages'],
            'newsletter_date_add'        => [],
            'ip_registration_newsletter' => [],
            'last_passwd_gen'            => ['setter' => null],
            'secure_key'                 => ['setter' => null],
            'deleted'                    => [],
            'passwd'                     => ['setter' => 'setWsPasswd'],
        ],
        'associations' => [
            'groups' => ['resource' => 'group'],
        ],
    ];

    /**
     * CustomerCore constructor.
     *
     * @param int|null $id
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($id = null)
    {
        $this->id_default_group = (int) Configuration::get('PS_CUSTOMER_GROUP');
        parent::__construct($id);
    }

    /**
     * Return customers list
     *
     * @param null|bool $onlyActive Returns only active customers when true
     *
     * @return array Customers
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCustomers($onlyActive = null)
    {
        $sql = new DbQuery();
        $sql->select('`id_customer`, `email`, `firstname`, `lastname`');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('1 '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER));
        if ($onlyActive) {
            $sql->where('`active` = 1');
        }
        $sql->orderBy('`id_customer` ASC');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Retrieve customers by email address
     *
     * @param string $email
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCustomersByEmail($email)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`email` = \''.pSQL($email).'\' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER));

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Check id the customer is active or not
     *
     * @return bool customer validity
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isBanned($idCustomer)
    {
        if (!Validate::isUnsignedId($idCustomer)) {
            return true;
        }
        $cacheId = 'Customer::isBanned_'.(int) $idCustomer;
        if (!Cache::isStored($cacheId)) {
            $sql = new DbQuery();
            $sql->select('`id_customer`');
            $sql->from(bqSQL(static::$definition['table']));
            $sql->where('`id_customer` = '.(int) $idCustomer);
            $sql->where('`active` = 1');
            $sql->where('`deleted` = 0');
            $result = (bool) !Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Check if e-mail is already registered in database
     *
     * @param string $email       e-mail
     * @param bool   $returnId    boolean
     * @param bool   $ignoreGuest boolean, to exclude guest customer
     *
     * @return int|bool if found, false otherwise
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function customerExists($email, $returnId = false, $ignoreGuest = true)
    {
        if (!Validate::isEmail($email)) {
            if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
                die(Tools::displayError('Invalid email'));
            }

            return false;
        }

        $sql = new DbQuery();
        $sql->select('`id_customer`');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`email` = \''.pSQL($email).'\' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER));
        if ($ignoreGuest) {
            $sql->where('`is_guest` = 0');
        }
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

        return ($returnId ? (int) $result : (bool) $result);
    }

    /**
     * Check if an address is owned by a customer
     *
     * @param int $idCustomer Customer ID
     * @param int $idAddress  Address ID
     *
     * @return bool result
     */
    public static function customerHasAddress($idCustomer, $idAddress)
    {
        $key = (int) $idCustomer.'-'.(int) $idAddress;
        if (!array_key_exists($key, static::$_customerHasAddress)) {
            static::$_customerHasAddress[$key] = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                '
			SELECT `id_address`
			FROM `'._DB_PREFIX_.'address`
			WHERE `id_customer` = '.(int) $idCustomer.'
			AND `id_address` = '.(int) $idAddress.'
			AND `deleted` = 0'
            );
        }

        return static::$_customerHasAddress[$key];
    }

    /**
     * @param int $idCustomer
     * @param int $idAddress
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function resetAddressCache($idCustomer, $idAddress)
    {
        $key = (int) $idCustomer.'-'.(int) $idAddress;
        if (array_key_exists($key, static::$_customerHasAddress)) {
            unset(static::$_customerHasAddress[$key]);
        }
    }

    /**
     * Count the number of addresses for a customer
     *
     * @param int $idCustomer Customer ID
     *
     * @return int Number of addresses
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAddressesTotalById($idCustomer)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
			SELECT COUNT(`id_address`)
			FROM `'._DB_PREFIX_.'address`
			WHERE `id_customer` = '.(int) $idCustomer.'
			AND `deleted` = 0'
        );
    }

    /**
     * Light back office search for customers
     *
     * @param string   $query Searched string
     * @param null|int $limit Limit query results
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource Corresponding customers
     * @throws PrestaShopDatabaseException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function searchByName($query, $limit = null)
    {
        $sqlBase = 'SELECT *
				FROM `'._DB_PREFIX_.'customer`';
        $sql = '('.$sqlBase.' WHERE `email` LIKE \'%'.pSQL($query).'%\' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).')';
        $sql .= ' UNION ('.$sqlBase.' WHERE `id_customer` = '.(int) $query.' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).')';
        $sql .= ' UNION ('.$sqlBase.' WHERE `lastname` LIKE \'%'.pSQL($query).'%\' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).')';
        $sql .= ' UNION ('.$sqlBase.' WHERE `firstname` LIKE \'%'.pSQL($query).'%\' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).')';

        if ($limit) {
            $sql .= ' LIMIT 0, '.(int) $limit;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Search for customers by ip address
     *
     * @param string $ip Searched string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function searchByIp($ip)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT DISTINCT c.*
		FROM `'._DB_PREFIX_.'customer` c
		LEFT JOIN `'._DB_PREFIX_.'guest` g ON g.id_customer = c.id_customer
		LEFT JOIN `'._DB_PREFIX_.'connections` co ON g.id_guest = co.id_guest
		WHERE co.`ip_address` = \''.(int) ip2long(trim($ip)).'\''
        );
    }

    /**
     * @param $idCustomer
     *
     * @return mixed|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getDefaultGroupId($idCustomer)
    {
        if (!Group::isFeatureActive()) {
            static $psCustomerGroup = null;
            if ($psCustomerGroup === null) {
                $psCustomerGroup = Configuration::get('PS_CUSTOMER_GROUP');
            }

            return $psCustomerGroup;
        }

        if (!isset(static::$_defaultGroupId[(int) $idCustomer])) {
            static::$_defaultGroupId[(int) $idCustomer] = Db::getInstance()->getValue(
                '
				SELECT `id_default_group`
				FROM `'._DB_PREFIX_.'customer`
				WHERE `id_customer` = '.(int) $idCustomer
            );
        }

        return static::$_defaultGroupId[(int) $idCustomer];
    }

    /**
     * @param int       $idCustomer
     * @param Cart|null $cart
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCurrentCountry($idCustomer, Cart $cart = null)
    {
        if (!$cart) {
            $cart = Context::getContext()->cart;
        }
        if (!$cart || !$cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}) {
            $idAddress = (int) Db::getInstance()->getValue(
                '
				SELECT `id_address`
				FROM `'._DB_PREFIX_.'address`
				WHERE `id_customer` = '.(int) $idCustomer.'
				AND `deleted` = 0 ORDER BY `id_address`'
            );
        } else {
            $idAddress = $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
        }
        $ids = Address::getCountryAndState($idAddress);

        return (int) $ids['id_country'] ? $ids['id_country'] : Configuration::get('PS_COUNTRY_DEFAULT');
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = true)
    {
        $this->id_shop = ($this->id_shop) ? $this->id_shop : Context::getContext()->shop->id;
        $this->id_shop_group = ($this->id_shop_group) ? $this->id_shop_group : Context::getContext()->shop->id_shop_group;
        $this->id_lang = ($this->id_lang) ? $this->id_lang : Context::getContext()->language->id;
        $this->birthday = (empty($this->years) ? $this->birthday : (int) $this->years.'-'.(int) $this->months.'-'.(int) $this->days);
        $this->secure_key = md5(uniqid(rand(), true));
        $this->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-'.Configuration::get('PS_PASSWD_TIME_FRONT').'minutes'));

        if ($this->newsletter && !Validate::isDate($this->newsletter_date_add)) {
            $this->newsletter_date_add = date('Y-m-d H:i:s');
        }

        if ($this->id_default_group == Configuration::get('PS_CUSTOMER_GROUP')) {
            if ($this->is_guest) {
                $this->id_default_group = (int) Configuration::get('PS_GUEST_GROUP');
            } else {
                $this->id_default_group = (int) Configuration::get('PS_CUSTOMER_GROUP');
            }
        }

        /* Can't create a guest customer, if this feature is disabled */
        if ($this->is_guest && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED')) {
            return false;
        }
        $success = parent::add($autoDate, $nullValues);
        $this->updateGroup($this->groupBox);

        return $success;
    }

    /**
     * Update customer groups associated to the object
     *
     * @param array $list groups
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updateGroup($list)
    {
        if ($list && !empty($list)) {
            $this->cleanGroups();
            $this->addGroups($list);
        } else {
            $this->addGroups([$this->id_default_group]);
        }
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function cleanGroups()
    {
        return Db::getInstance()->delete('customer_group', 'id_customer = '.(int) $this->id);
    }

    /**
     * @param $groups
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addGroups($groups)
    {
        foreach ($groups as $group) {
            $row = ['id_customer' => (int) $this->id, 'id_group' => (int) $group];
            Db::getInstance()->insert('customer_group', $row, false, true, Db::INSERT_IGNORE);
        }
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if (!count(Order::getCustomerOrders((int) $this->id))) {
            $addresses = $this->getAddresses((int) Configuration::get('PS_LANG_DEFAULT'));
            foreach ($addresses as $address) {
                $obj = new Address((int) $address['id_address']);
                $obj->delete();
            }
        }
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'customer_group` WHERE `id_customer` = '.(int) $this->id);
        Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'message WHERE id_customer='.(int) $this->id);
        Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'specific_price WHERE id_customer='.(int) $this->id);
        Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'compare WHERE id_customer='.(int) $this->id);

        $carts = Db::getInstance()->executes(
            'SELECT id_cart
															FROM '._DB_PREFIX_.'cart
															WHERE id_customer='.(int) $this->id
        );
        if ($carts) {
            foreach ($carts as $cart) {
                Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'cart WHERE id_cart='.(int) $cart['id_cart']);
                Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'cart_product WHERE id_cart='.(int) $cart['id_cart']);
            }
        }

        $cts = Db::getInstance()->executes(
            'SELECT id_customer_thread
															FROM '._DB_PREFIX_.'customer_thread
															WHERE id_customer='.(int) $this->id
        );
        if ($cts) {
            foreach ($cts as $ct) {
                Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'customer_thread WHERE id_customer_thread='.(int) $ct['id_customer_thread']);
                Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'customer_message WHERE id_customer_thread='.(int) $ct['id_customer_thread']);
            }
        }

        CartRule::deleteByIdCustomer((int) $this->id);

        return parent::delete();
    }

    /**
     * Return customer addresses
     *
     * @param int $idLang Language ID
     *
     * @return array Addresses
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getAddresses($idLang)
    {
        $shareOrder = (bool) Context::getContext()->shop->getGroup()->share_order;
        $cacheId = 'Customer::getAddresses'.(int) $this->id.'-'.(int) $idLang.'-'.$shareOrder;
        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('DISTINCT a.*, cl.`name` AS `country`, s.`name` AS `state`, s.`iso_code` AS `state_iso`')
                    ->from('address', 'a')
                    ->leftJoin('country', 'c', 'a.`id_country` = c.`id_country`')
                    ->leftJoin('country_lang', 'cl', 'c.`id_country` = cl.`id_country` AND cl.`id_lang` = '.(int) $idLang)
                    ->leftJoin('state', 's', 's.`id_state` = a.`id_state`')
                    ->join($shareOrder ? '' : Shop::addSqlAssociation('country', 'c'))
                    ->where('a.`id_customer` = '.(int) $this->id)
                    ->where('a.`deleted` = 0')
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Return customer instance from its e-mail (optionnaly check password)
     *
     * @param string $email             e-mail
     * @param string $plainTextPassword Password is also checked if specified
     * @param bool   $ignoreGuest
     *
     * @return Customer|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getByEmail($email, $plainTextPassword = null, $ignoreGuest = true)
    {
        if (!Validate::isEmail($email) || ($plainTextPassword && !Validate::isPasswd($plainTextPassword))) {
            die(Tools::displayError());
        }

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`email` = \''.pSQL($email).'\' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER));
        $sql->where('`deleted` = 0');
        if ($ignoreGuest) {
            $sql->where('`is_guest` = 0');
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        if ($plainTextPassword && !password_verify($plainTextPassword, $result['passwd'])) {
            if (!$plainTextPassword) {
                return false;
            }

            $sql = new DbQuery();
            $sql->select('*');
            $sql->from(bqSQL(static::$definition['table']));
            $sql->where('`email` = \''.pSQL($email).'\' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER));
            if ($plainTextPassword) {
                $sql->where('`passwd` = \''.md5(_COOKIE_KEY_.$plainTextPassword).'\'');
            }
            $sql->where('`deleted` = 0');
            if ($ignoreGuest) {
                $sql->where('`is_guest` = 0');
            }
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
            if ($result) {
                $newHash = Tools::hash($plainTextPassword);
                Db::getInstance()->update(
                    bqSQL(static::$definition['table']),
                    [
                        'passwd' => pSQL($newHash),
                    ],
                    '`id_customer` = '.(int) $result['id_customer']
                );
                $result['passwd'] = $newHash;
            } else {
                return false;
            }
        }

        if (!$result) {
            return false;
        }

        $this->id = $result['id_customer'];
        foreach ($result as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Return several useful statistics about customer
     *
     * @return array Stats
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getStats()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('COUNT(`id_order`) AS `nb_orders`, SUM(`total_paid` / o.`conversion_rate`) AS `total_orders`')
                ->from('orders', 'o')
                ->where('o.`id_customer` = '.(int) $this->id)
                ->where('o.`valid` = 1')
        );

        $result2 = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('MAX(c.`date_add`) AS `last_visit`')
                ->from('guest', 'g')
                ->leftJoin('connections', 'c', 'c.`id_guest` = g.`id_guest`')
                ->where('g.`id_customer` = '.(int) $this->id)
        );

        $result3 = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('(YEAR(CURRENT_DATE)-YEAR(c.`birthday`)) - (RIGHT(CURRENT_DATE, 5) < RIGHT(c.`birthday`, 5)) AS `age`')
                ->from('customer', 'c')
                ->where('c.`id_customer` = '.(int) $this->id)
        );

        $result['last_visit'] = $result2['last_visit'];
        $result['age'] = ($result3['age'] != date('Y') ? $result3['age'] : '--');

        return $result;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLastEmails()
    {
        if (!$this->id) {
            return [];
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('m.*, l.`name` as `language`')
                ->from('mail', 'm')
                ->leftJoin('lang', 'l', 'm.`id_lang` = l.`id_lang`')
                ->where('`recipient` = \''.pSQL($this->email).'\'')
                ->orderBy('m.`date_add` DESC')
                ->limit(10)
        );
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLastConnections()
    {
        if (!$this->id) {
            return [];
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('c.`id_connections`, c.`date_add`, COUNT(cp.`id_page`) AS `pages`')
                ->select('TIMEDIFF(MAX(cp.time_end), c.date_add) AS time, http_referer,INET_NTOA(ip_address) AS ipaddress')
                ->from('guest', 'g')
                ->leftJoin('connections', 'c', 'c.`id_guest` = g.`id_guest`')
                ->leftJoin('connections_page', 'cp', 'c.`id_connections` = cp.`id_connections`')
                ->where('g.`id_customer` = '.(int) $this->id)
                ->groupBy('c.`id_connections`')
                ->orderBy('c.`date_add` DESC')
                ->limit(10)
        );
    }

    /**
     * @param int $idCustomer
     *
     * @return int|null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function customerIdExists($idCustomer)
    {
        return Customer::customerIdExistsStatic((int) $idCustomer);
    }

    /**
     * @param int $idCustomer
     *
     * @return int|null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function customerIdExistsStatic($idCustomer)
    {
        $cacheId = 'Customer::customerIdExistsStatic'.(int) $idCustomer;
        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_customer`')
                    ->from('customer', 'c')
                    ->where('c.`id_customer` = '.(int) $idCustomer)
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @return array|mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getGroups()
    {
        return Customer::getGroupsStatic((int) $this->id);
    }

    /**
     * @param int $idCustomer
     *
     * @return array|mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGroupsStatic($idCustomer)
    {
        if (!Group::isFeatureActive()) {
            return [Configuration::get('PS_CUSTOMER_GROUP')];
        }

        // @codingStandardsIgnoreStart
        if ($idCustomer == 0) {
            static::$_customer_groups[$idCustomer] = [(int) Configuration::get('PS_UNIDENTIFIED_GROUP')];
        }

        if (!isset(static::$_customer_groups[$idCustomer])) {
            static::$_customer_groups[$idCustomer] = [];
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('cg.`id_group`')
                    ->from('customer_group', 'cg')
                    ->where('cg.`id_customer` = '.(int) $idCustomer)
            );
            foreach ($result as $group) {
                static::$_customer_groups[$idCustomer][] = (int) $group['id_group'];
            }
        }

        return static::$_customer_groups[$idCustomer];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @deprecated since 1.0.0
     *
     * @return false
     */
    public function isUsed()
    {
        Tools::displayAsDeprecated();

        return false;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getBoughtProducts()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('orders', 'o')
                ->leftJoin('order_detail', 'od', 'o.`id_order` = od.`id_order`')
                ->where('o.`valid` = 1')
                ->where('o.`id_customer` = '.(int) $this->id)
        );
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function toggleStatus()
    {
        parent::toggleStatus();

        /* Change status to active/inactive */

        return Db::getInstance()->update(
            bqSQL(static::$definition['table']),
            [
                'date_upd' => ['type' => 'sql', 'value' => 'NOW()'],
            ],
            '`'.bqSQL(static::$definition['primary']).'` = '.(int) $this->id
        );
    }

    /**
     * @param int  $idLang
     * @param null $password
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function transformToCustomer($idLang, $password = null)
    {
        if (!$this->isGuest()) {
            return false;
        }
        if (empty($password)) {
            $password = Tools::passwdGen(8, 'RANDOM');
        }
        if (!Validate::isPasswd($password)) {
            return false;
        }

        $this->is_guest = 0;
        $this->passwd = Tools::hash($password);
        $this->cleanGroups();
        $this->addGroups([Configuration::get('PS_CUSTOMER_GROUP')]); // add default customer group
        if ($this->update()) {
            $vars = [
                '{firstname}' => $this->firstname,
                '{lastname}'  => $this->lastname,
                '{email}'     => $this->email,
                '{passwd}'    => '*******',
            ];

            Mail::Send(
                (int) $idLang,
                'guest_to_customer',
                Mail::l('Your guest account has been transformed into a customer account', (int) $idLang),
                $vars,
                $this->email,
                $this->firstname.' '.$this->lastname,
                null,
                null,
                null,
                null,
                _PS_MAIL_DIR_,
                false,
                (int) $this->id_shop
            );

            return true;
        }

        return false;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function isGuest()
    {
        return (bool) $this->is_guest;
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
        $this->birthday = (empty($this->years) ? $this->birthday : (int) $this->years.'-'.(int) $this->months.'-'.(int) $this->days);

        if ($this->newsletter && !Validate::isDate($this->newsletter_date_add)) {
            $this->newsletter_date_add = date('Y-m-d H:i:s');
        }
        if (isset(Context::getContext()->controller) && Context::getContext()->controller->controller_type == 'admin') {
            $this->updateGroup($this->groupBox);
        }

        if ($this->deleted) {
            $addresses = $this->getAddresses((int) Configuration::get('PS_LANG_DEFAULT'));
            foreach ($addresses as $address) {
                $obj = new Address((int) $address['id_address']);
                $obj->delete();
            }
        }

        return parent::update(true);
    }

    /**
     * @param string $passwd
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setWsPasswd($passwd)
    {
        if ($this->id == 0 || $this->passwd != $passwd) {
            $this->passwd = Tools::hash($passwd);
        }

        return true;
    }

    /**
     * Check customer informations and return customer validity
     *
     * @since   1.5.0
     *
     * @param bool $withGuest
     *
     * @return bool customer validity
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function isLogged($withGuest = false)
    {
        if (!$withGuest && $this->is_guest == 1) {
            return false;
        }

        /* Customer is valid only if it can be load and if object password is the same as database one */

        return ($this->logged == 1 && $this->id && Validate::isUnsignedId($this->id) && Customer::checkPassword($this->id, $this->passwd));
    }

    /**
     * Check if customer password is the right one
     *
     * @param int    $idCustomer
     * @param string $plaintextOrHashedPassword Password
     *
     * @return bool result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @todo    : adapt validation for hashed password
     * @todo    : find out why both hashed and plaintext password are passed
     */
    public static function checkPassword($idCustomer, $plaintextOrHashedPassword)
    {
        if (!Validate::isUnsignedId($idCustomer)) {
            die(Tools::displayError());
        }

        if (Validate::isMd5($plaintextOrHashedPassword) || Tools::substr($plaintextOrHashedPassword, 0, 4) === '$2y$') {
            $hashedPassword = $plaintextOrHashedPassword;

            return static::checkPasswordInDatabase($idCustomer, $hashedPassword);
        } else {
            $hashedPassword = Tools::encrypt($plaintextOrHashedPassword);

            if (static::checkPasswordInDatabase($idCustomer, $hashedPassword)) {
                return true;
            }

            $sql = new DbQuery();
            $sql->select('`passwd`');
            $sql->from(bqSQL(static::$definition['table']));
            $sql->where('`id_customer` = '.(int) $idCustomer);

            $hashedPassword = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

            return password_verify($plaintextOrHashedPassword, $hashedPassword);
        }
    }

    /**
     * Check password validity via DB
     *
     * @param $idCustomer
     * @param $hashedPassword
     *
     * @return bool
     *
     * @since 1.0.1
     */
    protected static function checkPasswordInDatabase($idCustomer, $hashedPassword)
    {
        $cacheId = 'Customer::checkPassword'.(int) $idCustomer.'-'.$hashedPassword;
        if (!Cache::isStored($cacheId)) {
            $sql = new DbQuery();
            $sql->select('`id_customer`');
            $sql->from(bqSQL(static::$definition['table']));
            $sql->where('`id_customer` = '.(int) $idCustomer);
            $sql->where('`passwd` = \''.pSQL($hashedPassword).'\'');
            $result = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Logout
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function logout()
    {
        Hook::exec('actionCustomerLogoutBefore', ['customer' => $this]);

        if (isset(Context::getContext()->cookie)) {
            Context::getContext()->cookie->logout();
        }

        $this->logged = 0;

        Hook::exec('actionCustomerLogoutAfter', ['customer' => $this]);
    }

    /**
     * Soft logout, delete everything links to the customer
     * but leave there affiliate's informations
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function mylogout()
    {
        Hook::exec('actionCustomerLogoutBefore', ['customer' => $this]);

        if (isset(Context::getContext()->cookie)) {
            Context::getContext()->cookie->mylogout();
        }

        $this->logged = 0;

        Hook::exec('actionCustomerLogoutAfter', ['customer' => $this]);
    }

    /**
     * @param bool $withOrder
     *
     * @return bool|int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLastCart($withOrder = true)
    {
        $carts = Cart::getCustomerCarts((int) $this->id, $withOrder);
        if (!count($carts)) {
            return false;
        }
        $cart = array_shift($carts);
        $cart = new Cart((int) $cart['id_cart']);

        return ($cart->nbProducts() === 0 ? (int) $cart->id : false);
    }

    /**
     * @return float
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getOutstanding()
    {
        $totalPaid = (float) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('SUM(oi.`total_paid_tax_incl`)')
                ->from('order_invoice', 'oi')
                ->leftJoin('orders', 'o', 'oi.`id_order` = o.`id_order`')
                ->groupBy('o.`id_customer`')
                ->where('o.`id_customer` = '.(int) $this->id)
        );

        $totalRest = (float) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('SUM(op.`amount`)')
                ->from('order_payment', 'op')
                ->leftJoin('order_invoice_payment', 'oip', 'op.`id_order_payment` = oip.`id_order_payment`')
                ->leftJoin('orders', 'o', 'oip.`id_order` = o.`id_order`')
                ->groupBy('o.`id_customer`')
                ->where('o.`id_customer` = '.(int) $this->id)
        );

        return $totalPaid - $totalRest;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @todo Double-check the query, doesn't look right ^MD
     */
    public function getWsGroups()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cg.`id_group` AS `id`')
                ->from('customer_group', 'cg')
                ->join(Shop::addSqlAssociation('group', 'cg'))
                ->where('cg.`id_customer` = '.(int) $this->id)
        );
    }

    /**
     * @param array $result
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setWsGroups($result)
    {
        $groups = [];
        foreach ($result as $row) {
            $groups[] = $row['id'];
        }
        $this->cleanGroups();
        $this->addGroups($groups);

        return true;
    }

    /**
     * @param string $sqlJoin
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array|null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWebserviceObjectList($sqlJoin, $sqlFilter, $sqlSort, $sqlLimit)
    {
        $sqlFilter .= Shop::addSqlRestriction(Shop::SHARE_CUSTOMER, 'main');

        return parent::getWebserviceObjectList($sqlJoin, $sqlFilter, $sqlSort, $sqlLimit);
    }
}
