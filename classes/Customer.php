<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class CustomerCore
 */
class CustomerCore extends ObjectModel
{
    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'customer',
        'primary' => 'id_customer',
        'fields'  => [
            'id_shop_group'              => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false, 'dbDefault' => '1'],
            'id_shop'                    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false, 'dbDefault' => '1'],
            'id_gender'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false],
            'id_default_group'           => ['type' => self::TYPE_INT, 'copy_post' => false, 'dbDefault' => '1'],
            'id_lang'                    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
            'id_risk'                    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'copy_post' => false, 'dbDefault' => '1'],
            'company'                    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64],
            'siret'                      => ['type' => self::TYPE_STRING, 'validate' => 'isSiret', 'size' => 14],
            'ape'                        => ['type' => self::TYPE_STRING, 'validate' => 'isApe', 'size' => 5],
            'firstname'                  => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32],
            'lastname'                   => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32],
            'email'                      => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 128],
            'passwd'                     => ['type' => self::TYPE_STRING, 'validate' => 'isPasswd', 'required' => true, 'size' => 60],
            'last_passwd_gen'            => ['type' => self::TYPE_DATE, 'copy_post' => false, 'dbType' => 'timestamp', 'dbDefault' => ObjectModel::DEFAULT_CURRENT_TIMESTAMP],
            'birthday'                   => ['type' => self::TYPE_DATE, 'validate' => 'isBirthDate', 'dbType' => 'date'],
            'newsletter'                 => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'ip_registration_newsletter' => ['type' => self::TYPE_STRING, 'copy_post' => false, 'size' => 45],
            'newsletter_date_add'        => ['type' => self::TYPE_DATE, 'copy_post' => false],
            'optin'                      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'website'                    => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'size' => 128],
            'outstanding_allow_amount'   => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'copy_post' => false, 'dbDefault' => '0.000000'],
            'show_public_prices'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false, 'dbDefault' => '0'],
            'max_payment_days'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'copy_post' => false, 'dbDefault' => '60'],
            'secure_key'                 => ['type' => self::TYPE_STRING, 'validate' => 'isMd5', 'copy_post' => false, 'size' => 32, 'dbDefault' => '-1'],
            'note'                       => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'copy_post' => false, 'size' => ObjectModel::SIZE_TEXT],
            'active'                     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false, 'dbDefault' => '0'],
            'is_guest'                   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false, 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
            'deleted'                    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false, 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
            'date_add'                   => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false, 'dbNullable' => false],
            'date_upd'                   => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false, 'dbNullable' => false],
        ],
        'keys' => [
            'customer' => [
                'customer_email'     => ['type' => ObjectModel::KEY, 'columns' => ['email']],
                'customer_login'     => ['type' => ObjectModel::KEY, 'columns' => ['email', 'passwd']],
                'id_customer_passwd' => ['type' => ObjectModel::KEY, 'columns' => ['id_customer', 'passwd']],
                'id_gender'          => ['type' => ObjectModel::KEY, 'columns' => ['id_gender']],
                'id_shop'            => ['type' => ObjectModel::KEY, 'columns' => ['id_shop', 'date_add']],
                'id_shop_group'      => ['type' => ObjectModel::KEY, 'columns' => ['id_shop_group']],
            ],
        ],
    ];

    const DEFAULT_MERGE_OPERATIONS = [
        'customer' => 'delete',
        'address' => 'update',
        'cart' => 'update',
        'customer_thread' => 'update',
        'guest' => 'update',
        'message' => 'update',
        'order_return' => 'update',
        'order_slip' => 'update',
        'orders' => 'update',
        'specific_price' => 'update',
        'cart_rule' => 'delete',
        'compare' => 'delete',
        'customer_group' => 'delete'
    ];

    /**
     * @var array
     */
    protected static $_defaultGroupId = [];

    /**
     * @var array
     */
    protected static $_customerHasAddress = [];

    /**
     * @var array
     */
    protected static $_customer_groups = [];

    /**
     * @var int
     */
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
    /** @var string WebSite */
    public $website;
    /** @var string Company */
    public $company;
    /** @var string SIRET */
    public $siret;
    /** @var string APE */
    public $ape;
    /** @var float Outstanding allow amount (B2B opt) */
    public $outstanding_allow_amount = 0;
    /** @var bool Show public prices (B2B opt) */
    public $show_public_prices = 0;
    /** @var int Risk ID (B2B opt) */
    public $id_risk;
    /** @var int Max payment day */
    public $max_payment_days = 0;
    /** @var string Password */
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

    /**
     * @var int
     */
    public $years;

    /**
     * @var int
     */
    public $days;

    /**
     * @var int
     */
    public $months;

    /**
     * @var bool is the customer logged in
     */
    public $logged = 0;

    /**
     * @var int id_guest meaning the guest table, not the guest customer
     */
    public $id_guest;

    /**
     * @var array
     */
    public $groupBox;

    /**
     * @var array Webservice parameters
     */
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
     * @throws PrestaShopException
     */
    public function __construct($id = null)
    {
        $this->id_default_group = (int) Configuration::get('PS_CUSTOMER_GROUP');
        parent::__construct($id);
    }

    /**
     * Return customers list
     *
     * @param bool|null $onlyActive Returns only active customers when true
     *
     * @return array Customers
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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

        return Db::readOnly()->getArray($sql);
    }

    /**
     * Retrieve customers by email address
     *
     * @param string $email
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCustomersByEmail($email)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`email` = \''.pSQL($email).'\' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER));

        return Db::readOnly()->getArray($sql);
    }

    /**
     * Check id the customer is active or not
     *
     * @param int $idCustomer
     *
     * @return bool customer validity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
            $result = (bool) !Db::readOnly()->getRow($sql);
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Check if e-mail is already registered in database
     *
     * @param string $email e-mail
     * @param bool $returnId boolean
     * @param bool $ignoreGuest boolean, to exclude guest customer
     *
     * @return int|bool if found, false otherwise
     *
     * @throws PrestaShopException
     */
    public static function customerExists($email, $returnId = false, $ignoreGuest = true)
    {
        if (!Validate::isEmail($email)) {
            return false;
        }

        $sql = new DbQuery();
        $sql->select('`id_customer`');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`email` = \''.pSQL($email).'\' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER));
        if ($ignoreGuest) {
            $sql->where('`is_guest` = 0');
        }
        $result = Db::readOnly()->getValue($sql);

        return ($returnId ? (int) $result : (bool) $result);
    }

    /**
     * Check if an address is owned by a customer
     *
     * @param int $idCustomer Customer ID
     * @param int $idAddress Address ID
     *
     * @return bool result
     * @throws PrestaShopException
     */
    public static function customerHasAddress($idCustomer, $idAddress)
    {
        $key = (int) $idCustomer.'-'.(int) $idAddress;
        if (!array_key_exists($key, static::$_customerHasAddress)) {
            static::$_customerHasAddress[$key] = (bool) Db::readOnly()->getValue(
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
     * @throws PrestaShopException
     */
    public static function getAddressesTotalById($idCustomer)
    {
        return Db::readOnly()->getValue(
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
     * @param string $query Searched string
     * @param int|null $limit Limit query results
     *
     * @return array Corresponding customers
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
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

        return Db::readOnly()->getArray($sql);
    }

    /**
     * Search for customers by ip address
     *
     * @param string $ip Searched string
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function searchByIp($ip)
    {
        return Db::readOnly()->getArray(
            '
		SELECT DISTINCT c.*
		FROM `'._DB_PREFIX_.'customer` c
		LEFT JOIN `'._DB_PREFIX_.'guest` g ON g.id_customer = c.id_customer
		LEFT JOIN `'._DB_PREFIX_.'connections` co ON g.id_guest = co.id_guest
		WHERE co.`ip_address` = \''.(int) ip2long(trim($ip)).'\''
        );
    }

    /**
     * @param int $idCustomer
     *
     * @return int
     *
     * @throws PrestaShopException
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
            static::$_defaultGroupId[(int) $idCustomer] = Db::readOnly()->getValue(
                '
				SELECT `id_default_group`
				FROM `'._DB_PREFIX_.'customer`
				WHERE `id_customer` = '.(int) $idCustomer
            );
        }

        return static::$_defaultGroupId[(int) $idCustomer];
    }

    /**
     * @param int $idCustomer
     * @param Cart|null $cart
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCurrentCountry($idCustomer, Cart $cart = null)
    {
        if (!$cart) {
            $cart = Context::getContext()->cart;
        }
        if (!$cart || !$cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}) {
            $idAddress = (int) Db::readOnly()->getValue(
                '
				SELECT `id_address`
				FROM `'._DB_PREFIX_.'address`
				WHERE `id_customer` = '.(int) $idCustomer.'
				AND `deleted` = 0 ORDER BY `id_address`'
            );
        } else {
            $idAddress = $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
        }

        if ($idAddress) {
            $ids = Address::getCountryAndState($idAddress);
            if (is_array($ids) && (int)$ids['id_country']) {
                return (int)$ids['id_country'];
            }
        }

        return (int) Configuration::get('PS_COUNTRY_DEFAULT');
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updateGroup($list)
    {
        if (!empty($list)) {
            $this->cleanGroups();
            $this->addGroups($list);
        } else {
            $this->addGroups([$this->id_default_group]);
        }
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function cleanGroups()
    {
        return Db::getInstance()->delete('customer_group', 'id_customer = '.(int) $this->id);
    }

    /**
     * @param int[] $groups
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (Validate::isLoadedObject($this)) {
            $customerId = (int)$this->id;

            if (! Order::getCustomerOrders($customerId)) {
                $addresses = $this->getAddresses((int)Configuration::get('PS_LANG_DEFAULT'));
                foreach ($addresses as $address) {
                    $obj = new Address((int)$address['id_address']);
                    $obj->delete();
                }
            }

            $conn = Db::getInstance();
            $conn->delete('customer_group', 'id_customer = ' . $customerId);
            $conn->delete('message', 'id_customer = ' . $customerId);
            $conn->delete('specific_price', 'id_customer = ' . $customerId);
            $conn->delete('compare', 'id_customer = ' . $customerId);

            $carts = $conn->getArray('SELECT id_cart FROM ' . _DB_PREFIX_ . 'cart WHERE id_customer=' . $customerId);
            foreach ($carts as $cart) {
                $cartId = (int)$cart['id_cart'];
                $conn->delete('cart', 'id_cart = ' . $cartId);
                $conn->delete('cart_product', 'id_cart = ' . $cartId);
            }

            $cts = $conn->getArray('SELECT id_customer_thread FROM ' . _DB_PREFIX_ . 'customer_thread WHERE id_customer=' . $customerId);
            foreach ($cts as $ct) {
                $customerThreadId = (int)$ct['id_customer_thread'];
                $conn->delete('customer_thread', 'id_customer_thread = ' . $customerThreadId);
                $conn->delete('customer_message', 'id_customer_thread = ' . $customerThreadId);
            }

            CartRule::deleteByIdCustomer($customerId);
        }

        return parent::delete();
    }

    /**
     * Return customer addresses
     *
     * @param int $idLang Language ID
     *
     * @return array Addresses
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getAddresses($idLang)
    {
        $shareOrder = (bool) Context::getContext()->shop->getGroup()->share_order;
        $cacheId = 'Customer::getAddresses'.(int) $this->id.'-'.(int) $idLang.'-'.$shareOrder;
        if (!Cache::isStored($cacheId)) {
            $result = Db::readOnly()->getArray(
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
     * Return customer instance from its e-mail (optionally check password)
     *
     * @param string $email E-mail
     * @param string $plainTextPassword Password is also checked if specified
     * @param bool $ignoreGuest
     *
     * @return self | false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getByEmail($email, $plainTextPassword = null, $ignoreGuest = true)
    {
        if (! Validate::isEmail($email)) {
            throw new PrestaShopException(Tools::displayError("Invalid email address"));
        }

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`email` = \''.pSQL($email).'\' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER));
        $sql->where('`deleted` = 0');
        if ($ignoreGuest) {
            $sql->where('`is_guest` = 0');
        }
        $result = Db::readOnly()->getRow($sql);

        if (!$result) {
            return false;
        }

        // If password is provided but doesn't match.
        if ($plainTextPassword && !password_verify($plainTextPassword, $result['passwd'])) {
            // Check if it matches the legacy md5 hashing and, if it does, rehash it.
            if (Validate::isMd5($result['passwd']) && $result['passwd'] === md5(_COOKIE_KEY_.$plainTextPassword)) {
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getStats()
    {
        $id = (int)$this->id;
        $result = [
            'nb_orders' => 0,
            'total_orders' => 0,
            'last_visit' => null,
            'age' => '--'
        ];

        if ($id) {
            $conn = Db::readOnly();
            $res = $conn->getRow(
                (new DbQuery())
                    ->select('COUNT(`id_order`) AS `nb_orders`, SUM(`total_paid` / o.`conversion_rate`) AS `total_orders`')
                    ->from('orders', 'o')
                    ->where('o.`id_customer` = ' . $id)
                    ->where('o.`valid` = 1')
            );
            if (is_array($res)) {
                $result['nb_orders'] = (int) $res['nb_orders'];
                $result['total_orders'] = (int) $res['total_orders'];
            }

            $result['last_visit'] = $conn->getValue(
                (new DbQuery())
                    ->select('MAX(c.`date_add`) AS `last_visit`')
                    ->from('guest', 'g')
                    ->leftJoin('connections', 'c', 'c.`id_guest` = g.`id_guest`')
                    ->where('g.`id_customer` = ' . $id)
            );

            $age = $conn->getValue(
                (new DbQuery())
                    ->select('(YEAR(CURRENT_DATE)-YEAR(c.`birthday`)) - (RIGHT(CURRENT_DATE, 5) < RIGHT(c.`birthday`, 5)) AS `age`')
                    ->from('customer', 'c')
                    ->where('c.`id_customer` = ' . $id)
            );

            $result['age'] = ($age != date('Y')) ? $age : '--';
        }

        return $result;
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getLastEmails()
    {
        if (!$this->id) {
            return [];
        }

        return Db::readOnly()->getArray(
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
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getLastConnections()
    {
        if (!$this->id) {
            return [];
        }

        return Db::readOnly()->getArray(
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
     * @throws PrestaShopException
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
     * @throws PrestaShopException
     */
    public static function customerIdExistsStatic($idCustomer)
    {
        $cacheId = 'Customer::customerIdExistsStatic'.(int) $idCustomer;
        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::readOnly()->getValue(
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
     * @return int[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getGroups()
    {
        return Customer::getGroupsStatic((int) $this->id);
    }

    /**
     * @param int $idCustomer
     *
     * @return int[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getGroupsStatic($idCustomer)
    {
        if (!Group::isFeatureActive()) {
            return [Configuration::get('PS_CUSTOMER_GROUP')];
        }

        if ($idCustomer == 0) {
            static::$_customer_groups[$idCustomer] = [(int) Configuration::get('PS_UNIDENTIFIED_GROUP')];
        }

        if (!isset(static::$_customer_groups[$idCustomer])) {
            static::$_customer_groups[$idCustomer] = [];
            $result = Db::readOnly()->getArray(
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
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getBoughtProducts()
    {
        return Db::readOnly()->getArray(
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @param int $idLang
     * @param string|null $password
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @param bool $withGuest
     *
     * @return bool customer validity
     *
     * @throws PrestaShopException
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
     * @param int $idCustomer
     * @param string $plaintextOrHashedPassword Password
     *
     * @return bool result
     *
     * @todo    : adapt validation for hashed password
     * @todo    : find out why both hashed and plaintext password are passed
     * @throws PrestaShopException
     */
    public static function checkPassword($idCustomer, $plaintextOrHashedPassword)
    {
        if (!Validate::isUnsignedId($idCustomer)) {
            return false;
        }

        if (Validate::isMd5($plaintextOrHashedPassword) || mb_substr($plaintextOrHashedPassword, 0, 4) === '$2y$') {
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

            $hashedPassword = Db::readOnly()->getValue($sql);

            return password_verify($plaintextOrHashedPassword, $hashedPassword);
        }
    }

    /**
     * Check password validity via DB
     *
     * @param int $idCustomer
     * @param string $hashedPassword
     *
     * @return bool
     *
     * @throws PrestaShopException
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
            $result = (bool) Db::readOnly()->getValue($sql);
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Logout
     *
     * @throws PrestaShopException
     */
    public function logout()
    {
        Hook::triggerEvent('actionCustomerLogoutBefore', ['customer' => $this]);

        if (isset(Context::getContext()->cookie)) {
            Context::getContext()->cookie->delete();
        }

        $this->logged = 0;

        Hook::triggerEvent('actionCustomerLogoutAfter', ['customer' => $this]);
    }

    /**
     * Soft logout, delete everything links to the customer
     * but leave there affiliate's informations
     *
     * @throws PrestaShopException
     */
    public function mylogout()
    {
        Hook::triggerEvent('actionCustomerLogoutBefore', ['customer' => $this]);

        if (isset(Context::getContext()->cookie)) {
            Context::getContext()->cookie->mylogout();
        }

        $this->logged = 0;

        Hook::triggerEvent('actionCustomerLogoutAfter', ['customer' => $this]);
    }

    /**
     * @param bool $withOrder
     *
     * @return bool|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @throws PrestaShopException
     */
    public function getOutstanding()
    {
        $conn = Db::readOnly();
        $totalPaid = (float) $conn->getValue(
            (new DbQuery())
                ->select('SUM(oi.`total_paid_tax_incl`)')
                ->from('order_invoice', 'oi')
                ->leftJoin('orders', 'o', 'oi.`id_order` = o.`id_order`')
                ->groupBy('o.`id_customer`')
                ->where('o.`id_customer` = '.(int) $this->id)
        );

        $totalRest = (float) $conn->getValue(
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
     * Return customer rank
     *
     * Return rank of customer among customers with at least one valid order.
     * If customer haven't place any order yet, this method returns null.
     *
     * @return int|null
     * @throws PrestaShopException
     */
    public function getBestCustomerRank()
    {
        $conn = Db::readOnly();
        $totalPaid = $conn->getValue(
            (new DbQuery())
                ->select('SUM(`total_paid` / `conversion_rate`)')
                ->from('orders')
                ->where('`id_customer` = ' . (int)$this->id)
                ->where('`valid` = 1')
        );

        if ($totalPaid) {
            $conn->getValue(
                (new DbQuery())
                    ->select('SQL_CALC_FOUND_ROWS COUNT(*)')
                    ->from('orders')
                    ->where('`valid` = 1')
                    ->where('`id_customer` != ' . (int)$this->id)
                    ->groupBy('id_customer')
                    ->having('SUM(`total_paid` / `conversion_rate`) > ' . $totalPaid)
            );
            return (int)$conn->getValue('SELECT FOUND_ROWS()') + 1;
        }

        return null;
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @todo    Double-check the query, doesn't look right ^MD
     */
    public function getWsGroups()
    {
        return Db::readOnly()->getArray(
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getWebserviceObjectList($sqlJoin, $sqlFilter, $sqlSort, $sqlLimit)
    {
        $sqlFilter .= Shop::addSqlRestriction(Shop::SHARE_CUSTOMER, 'main');

        return parent::getWebserviceObjectList($sqlJoin, $sqlFilter, $sqlSort, $sqlLimit);
    }

    /**
     * Methods merges two customer accounts, and deletes the $other account from the database
     *
     * @param Customer $target
     * @param Customer $source
     * @param array $tablesOperations
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function mergeAccounts(Customer $target, Customer $source, array $tablesOperations)
    {
        $targetId = (int)$target->id;
        $sourceId = (int)$source->id;
        if (! $targetId) {
            throw new PrestaShopException('Merge failed: invalid target customer id');
        }
        if (! $sourceId) {
            throw new PrestaShopException('Merge failed: invalid source customer id');
        }

        $tables = array_merge(static::DEFAULT_MERGE_OPERATIONS, $tablesOperations);

        $conn = Db::getInstance();


        // re-associate data
        unset($tables['customer']);
        foreach ($tables as $table => $operation) {
            if ($operation === 'update') {
                $conn->update($table, ['id_customer' => $targetId], 'id_customer = ' . $sourceId);
            } elseif ($operation === 'delete') {
                $conn->delete($table, 'id_customer = ' . $sourceId);
            } elseif (is_callable($operation)) {
                call_user_func($operation, $sourceId, $targetId);
            }
        }

        // delete source customer
        $source->delete();

    }
}
