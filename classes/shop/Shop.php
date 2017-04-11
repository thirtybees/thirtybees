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
 * Class ShopCore
 *
 * @since 1.0.0
 */
class ShopCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int ID of shop group */
    public $id_shop_group;

    /** @var int ID of shop category */
    public $id_category;

    /** @var int ID of shop theme */
    public $id_theme;

    /** @var string Shop name */
    public $name;

    public $active = true;
    public $deleted;

    /** @var string Shop theme name (read only) */
    public $theme_name;

    /** @var string Shop theme directory (read only) */
    public $theme_directory;

    /** @var string Physical uri of main url (read only) */
    public $physical_uri;

    /** @var string Virtual uri of main url (read only) */
    public $virtual_uri;

    /** @var string Domain of main url (read only) */
    public $domain;

    /** @var string Domain SSL of main url (read only) */
    public $domain_ssl;

    /** @var ShopGroup Shop group object */
    protected $group;

    /** @var array List of shops cached */
    protected static $shops;

    protected static $asso_tables = [];
    protected static $id_shop_default_tables = [];
    protected static $initialized = false;

    /**
     * Store the current context of shop (CONTEXT_ALL, CONTEXT_GROUP, CONTEXT_SHOP)
     *
     * @var int $context ;
     */
    protected static $context;

    /**
     * ID shop in the current context (will be empty if context is not CONTEXT_SHOP)
     *
     * @var int $context_id_shop
     */
    protected static $context_id_shop;

    /**
     * ID shop group in the current context (will be empty if context is CONTEXT_ALL)
     *
     * @var int $context_id_shop_group
     */
    protected static $context_id_shop_group;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'shop',
        'primary' => 'id_shop',
        'fields'  => [
            'active'        => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'deleted'       => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'name'          => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'id_theme'      => ['type' => self::TYPE_INT,                                   'required' => true              ],
            'id_category'   => ['type' => self::TYPE_INT,                                   'required' => true              ],
            'id_shop_group' => ['type' => self::TYPE_INT,                                   'required' => true              ],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_shop_group' => ['xlink_resource' => 'shop_groups'],
            'id_category'   => [],
            'id_theme'      => [],
        ],
    ];

    /**
     * There are 3 kinds of shop context : shop, group shop and general
     */
    const CONTEXT_SHOP = 1;
    const CONTEXT_GROUP = 2;
    const CONTEXT_ALL = 4;

    /**
     * Some data can be shared between shops, like customers or orders
     */
    const SHARE_CUSTOMER = 'share_customer';
    const SHARE_ORDER = 'share_order';
    const SHARE_STOCK = 'share_stock';

    /**
     * On shop instance, get its theme and URL data too
     *
     * @param int $id
     * @param int $idLang
     * @param int $idShop
     *
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
        if ($this->id) {
            $this->setUrl();
        }
    }

    /**
     * Initialize an array with all the multistore associations in the database
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function init()
    {
        Shop::$id_shop_default_tables = ['product', 'category'];

        $assoTables = [
            'carrier'                      => ['type' => 'shop'],
            'carrier_lang'                 => ['type' => 'fk_shop'],
            'category'                     => ['type' => 'shop'],
            'category_lang'                => ['type' => 'fk_shop'],
            'cms'                          => ['type' => 'shop'],
            'cms_lang'                     => ['type' => 'fk_shop'],
            'cms_category'                 => ['type' => 'shop'],
            'cms_category_lang'            => ['type' => 'fk_shop'],
            'contact'                      => ['type' => 'shop'],
            'country'                      => ['type' => 'shop'],
            'currency'                     => ['type' => 'shop'],
            'employee'                     => ['type' => 'shop'],
            'hook_module'                  => ['type' => 'fk_shop'],
            'hook_module_exceptions'       => ['type' => 'fk_shop', 'primary' => 'id_hook_module_exceptions'],
            'image'                        => ['type' => 'shop'],
            'lang'                         => ['type' => 'shop'],
            'meta_lang'                    => ['type' => 'fk_shop'],
            'module'                       => ['type' => 'shop'],
            'module_currency'              => ['type' => 'fk_shop'],
            'module_country'               => ['type' => 'fk_shop'],
            'module_group'                 => ['type' => 'fk_shop'],
            'product'                      => ['type' => 'shop'],
            'product_attribute'            => ['type' => 'shop'],
            'product_lang'                 => ['type' => 'fk_shop'],
            'referrer'                     => ['type' => 'shop'],
            'scene'                        => ['type' => 'shop'],
            'store'                        => ['type' => 'shop'],
            'webservice_account'           => ['type' => 'shop'],
            'warehouse'                    => ['type' => 'shop'],
            'stock_available'              => ['type' => 'fk_shop', 'primary' => 'id_stock_available'],
            'carrier_tax_rules_group_shop' => ['type' => 'fk_shop'],
            'attribute'                    => ['type' => 'shop'],
            'feature'                      => ['type' => 'shop'],
            'group'                        => ['type' => 'shop'],
            'attribute_group'              => ['type' => 'shop'],
            'tax_rules_group'              => ['type' => 'shop'],
            'zone'                         => ['type' => 'shop'],
            'manufacturer'                 => ['type' => 'shop'],
            'supplier'                     => ['type' => 'shop'],
        ];

        foreach ($assoTables as $tableName => $tableDetails) {
            Shop::addTableAssociation($tableName, $tableDetails);
        }

        Shop::$initialized = true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setUrl()
    {
        $cacheId = 'Shop::setUrl_'.(int) $this->id;
        if (!Cache::isStored($cacheId)) {
            $row = Db::getInstance()->getRow(
                '
			SELECT su.physical_uri, su.virtual_uri, su.domain, su.domain_ssl, t.id_theme, t.name, t.directory
			FROM '._DB_PREFIX_.'shop s
			LEFT JOIN '._DB_PREFIX_.'shop_url su ON (s.id_shop = su.id_shop)
			LEFT JOIN '._DB_PREFIX_.'theme t ON (t.id_theme = s.id_theme)
			WHERE s.id_shop = '.(int) $this->id.'
			AND s.active = 1 AND s.deleted = 0 AND su.main = 1'
            );
            Cache::store($cacheId, $row);
        } else {
            $row = Cache::retrieve($cacheId);
        }
        if (!$row) {
            return false;
        }

        $this->theme_id = $row['id_theme'];
        $this->theme_name = $row['name'];
        $this->theme_directory = $row['directory'];
        $this->physical_uri = $row['physical_uri'];
        $this->virtual_uri = $row['virtual_uri'];
        $this->domain = $row['domain'];
        $this->domain_ssl = $row['domain_ssl'];

        return true;
    }

    /**
     * Add a shop, and clear the cache
     *
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     */
    public function add($autodate = true, $nullValues = false)
    {
        $res = parent::add($autodate, $nullValues);
        // Set default language routes
        $langs = Language::getLanguages(false, $this->id, true);
        // @codingStandardsIgnoreStart
        Configuration::updateValue('PS_ROUTE_product_rule', array_map(function() {return '{categories:/}{rewrite}';}, $langs));
        Configuration::updateValue('PS_ROUTE_category_rule', array_map(function() {return '{rewrite}';}, $langs));
        Configuration::updateValue('PS_ROUTE_supplier_rule', array_map(function() {return '{rewrite}';}, $langs));
        Configuration::updateValue('PS_ROUTE_manufacturer_rule', array_map(function() {return '{rewrite}';}, $langs));
        Configuration::updateValue('PS_ROUTE_cms_rule', array_map(function() {return '{categories:/}{rewrite}';}, $langs));
        Configuration::updateValue('PS_ROUTE_cms_category_rule', array_map(function() {return '{categories:/}{rewrite}';}, $langs));
        // @codingStandardsIgnoreEnd

        Shop::cacheShops(true);

        return $res;
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function associateSuperAdmins()
    {
        $superAdmins = Employee::getEmployeesByProfile(_PS_ADMIN_PROFILE_);
        foreach ($superAdmins as $superAdmin) {
            $employee = new Employee((int) $superAdmin['id_employee']);
            $employee->associateTo((int) $this->id);
        }
    }

    /**
     * Remove a shop only if it has no dependencies, and remove its associations
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if (Shop::hasDependency($this->id) || !$res = parent::delete()) {
            return false;
        }

        foreach (Shop::getAssoTables() as $tableName => $row) {
            $id = 'id_'.$row['type'];
            if ($row['type'] == 'fk_shop') {
                $id = 'id_shop';
            } else {
                $tableName .= '_'.$row['type'];
            }
            $res &= Db::getInstance()->execute(
                '
				DELETE FROM `'.bqSQL(_DB_PREFIX_.$tableName).'`
				WHERE `'.bqSQL($id).'`='.(int) $this->id
            );
        }

        // removes stock available
        $res &= Db::getInstance()->delete('stock_available', '`id_shop` = '.(int) $this->id);

        // Remove urls
        $res &= Db::getInstance()->delete('shop_url', '`id_shop` = '.(int) $this->id);

        // Remove currency restrictions
        $res &= Db::getInstance()->delete('module_currency', '`id_shop` = '.(int) $this->id);

        // Remove group restrictions
        $res &= Db::getInstance()->delete('module_group', '`id_shop` = '.(int) $this->id);

        // Remove country restrictions
        $res &= Db::getInstance()->delete('module_country', '`id_shop` = '.(int) $this->id);

        // Remove carrier restrictions
        $res &= Db::getInstance()->delete('module_carrier', '`id_shop` = '.(int) $this->id);

        Shop::cacheShops(true);

        return $res;
    }

    /**
     * Detect dependency with customer or orders
     *
     * @param int $idShop
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function hasDependency($idShop)
    {
        $hasDependency = false;
        $nbrCustomer = (int) Db::getInstance()->getValue(
            '
			SELECT count(*)
			FROM `'._DB_PREFIX_.'customer`
			WHERE `id_shop`='.(int) $idShop
        );
        if ($nbrCustomer) {
            $hasDependency = true;
        } else {
            $nbrOrder = (int) Db::getInstance()->getValue(
                '
				SELECT count(*)
				FROM `'._DB_PREFIX_.'orders`
				WHERE `id_shop`='.(int) $idShop
            );
            if ($nbrOrder) {
                $hasDependency = true;
            }
        }

        return $hasDependency;
    }

    /**
     * Find the shop from current domain / uri and get an instance of this shop
     *
     * @return Shop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function initialize()
    {
        // Find current shop from URL
        if (!($idShop = Tools::getValue('id_shop')) || defined('_PS_ADMIN_DIR_')) {
            $foundUri = '';
            $isMainUri = false;
            $host = Tools::getHttpHost();
            $requestUri = rawurldecode($_SERVER['REQUEST_URI']);

            $sql = 'SELECT s.id_shop, CONCAT(su.physical_uri, su.virtual_uri) AS uri, su.domain, su.main
					FROM '._DB_PREFIX_.'shop_url su
					LEFT JOIN '._DB_PREFIX_.'shop s ON (s.id_shop = su.id_shop)
					WHERE (su.domain = \''.pSQL($host).'\' OR su.domain_ssl = \''.pSQL($host).'\')
						AND s.active = 1
						AND s.deleted = 0
					ORDER BY LENGTH(CONCAT(su.physical_uri, su.virtual_uri)) DESC';

            $result = Db::getInstance()->executeS($sql);

            $through = false;
            foreach ($result as $row) {
                // An URL matching current shop was found
                if (preg_match('#^'.preg_quote($row['uri'], '#').'#i', $requestUri)) {
                    $through = true;
                    $idShop = $row['id_shop'];
                    $foundUri = $row['uri'];
                    if ($row['main']) {
                        $isMainUri = true;
                    }
                    break;
                }
            }

            // If an URL was found but is not the main URL, redirect to main URL
            if ($through && $idShop && !$isMainUri) {
                foreach ($result as $row) {
                    if ($row['id_shop'] == $idShop && $row['main']) {
                        $requestUri = substr($requestUri, strlen($foundUri));
                        $url = str_replace('//', '/', $row['domain'].$row['uri'].$requestUri);
                        $redirectType = Configuration::get('PS_CANONICAL_REDIRECT');
                        $redirectCode = ($redirectType == 1 ? '302' : '301');
                        $redirectHeader = ($redirectType == 1 ? 'Found' : 'Moved Permanently');
                        header('HTTP/1.0 '.$redirectCode.' '.$redirectHeader);
                        header('Cache-Control: no-cache');
                        header('Location: http://'.$url);
                        exit;
                    }
                }
            }
        }

        $httpHost = Tools::getHttpHost();
        $allMedia = array_merge(Configuration::getMultiShopValues('PS_MEDIA_SERVER_1'), Configuration::getMultiShopValues('PS_MEDIA_SERVER_2'), Configuration::getMultiShopValues('PS_MEDIA_SERVER_3'));

        if ((!$idShop && defined('_PS_ADMIN_DIR_')) || Tools::isPHPCLI() || in_array($httpHost, $allMedia)) {
            // If in admin, we can access to the shop without right URL
            if ((!$idShop && Tools::isPHPCLI()) || defined('_PS_ADMIN_DIR_')) {
                $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
            }

            $shop = new Shop((int) $idShop);
            if (!Validate::isLoadedObject($shop)) {
                $shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
            }

            $shop->virtual_uri = '';

            // Define some $_SERVER variables like HTTP_HOST if PHP is launched with php-cli
            if (Tools::isPHPCLI()) {
                if (!isset($_SERVER['HTTP_HOST']) || empty($_SERVER['HTTP_HOST'])) {
                    $_SERVER['HTTP_HOST'] = $shop->domain;
                }
                if (!isset($_SERVER['SERVER_NAME']) || empty($_SERVER['SERVER_NAME'])) {
                    $_SERVER['SERVER_NAME'] = $shop->domain;
                }
                if (!isset($_SERVER['REMOTE_ADDR']) || empty($_SERVER['REMOTE_ADDR'])) {
                    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
                }
            }
        } else {
            $shop = new Shop($idShop);
            if (!Validate::isLoadedObject($shop) || !$shop->active) {
                // No shop found ... too bad, let's redirect to default shop
                $defaultShop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));

                // Hmm there is something really bad in your Prestashop !
                if (!Validate::isLoadedObject($defaultShop)) {
                    throw new PrestaShopException('Shop not found');
                }

                $params = $_GET;
                unset($params['id_shop']);
                $url = $defaultShop->domain;
                if (!Configuration::get('PS_REWRITING_SETTINGS')) {
                    $url .= $defaultShop->getBaseURI().'index.php?'.http_build_query($params);
                } else {
                    // Catch url with subdomain "www"
                    if (strpos($url, 'www.') === 0 && 'www.'.$_SERVER['HTTP_HOST'] === $url || $_SERVER['HTTP_HOST'] === 'www.'.$url) {
                        $url .= $_SERVER['REQUEST_URI'];
                    } else {
                        $url .= $defaultShop->getBaseURI();
                    }

                    if (count($params)) {
                        $url .= '?'.http_build_query($params);
                    }
                }

                $redirectType = Configuration::get('PS_CANONICAL_REDIRECT');
                $redirectCode = ($redirectType == 1 ? '302' : '301');
                $redirectHeader = ($redirectType == 1 ? 'Found' : 'Moved Permanently');
                header('HTTP/1.0 '.$redirectCode.' '.$redirectHeader);
                header('Location: http://'.$url);
                exit;
            } elseif (defined('_PS_ADMIN_DIR_') && empty($shop->physical_uri)) {
                $shopDefault = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
                $shop->physical_uri = $shopDefault->physical_uri;
                $shop->virtual_uri = $shopDefault->virtual_uri;
            }
        }

        static::$context_id_shop = $shop->id;
        static::$context_id_shop_group = $shop->id_shop_group;
        static::$context = static::CONTEXT_SHOP;

        return $shop;
    }

    /**
     * @return Address the current shop address
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getAddress()
    {
        if (!isset($this->address)) {
            $address = new Address();
            $address->company = Configuration::get('PS_SHOP_NAME');
            $address->id_country = Configuration::get('PS_SHOP_COUNTRY_ID') ? Configuration::get('PS_SHOP_COUNTRY_ID') : Configuration::get('PS_COUNTRY_DEFAULT');
            $address->id_state = Configuration::get('PS_SHOP_STATE_ID');
            $address->address1 = Configuration::get('PS_SHOP_ADDR1');
            $address->address2 = Configuration::get('PS_SHOP_ADDR2');
            $address->postcode = Configuration::get('PS_SHOP_CODE');
            $address->city = Configuration::get('PS_SHOP_CITY');

            $this->address = $address;
        }

        return $this->address;
    }

    /**
     * Get shop theme name
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTheme()
    {
        return $this->theme_directory;
    }

    /**
     * Get shop URI
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getBaseURI()
    {
        return $this->physical_uri.$this->virtual_uri;
    }

    /**
     * Get shop URL
     *
     * @param string $autoSecureMode if set to true, secure mode will be checked
     * @param string $addBaseUri     if set to true, shop base uri will be added
     *
     * @return string complete base url of current shop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getBaseURL($autoSecureMode = false, $addBaseUri = true)
    {
        if (($autoSecureMode && Tools::usingSecureMode() && !$this->domain_ssl) || !$this->domain) {
            return false;
        }

        $url = [];
        $url['protocol'] = $autoSecureMode && Tools::usingSecureMode() ? 'https://' : 'http://';
        $url['domain'] = $autoSecureMode && Tools::usingSecureMode() ? $this->domain_ssl : $this->domain;

        if ($addBaseUri) {
            $url['base_uri'] = $this->getBaseURI();
        }

        return implode('', $url);
    }

    /**
     * Get group of current shop
     *
     * @return ShopGroup
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getGroup()
    {
        if (!$this->group) {
            $this->group = new ShopGroup($this->id_shop_group);
        }

        return $this->group;
    }

    /**
     * Get root category of current shop
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getCategory()
    {
        return (int) ($this->id_category ? $this->id_category : Configuration::get('PS_ROOT_CATEGORY'));
    }

    /**
     * Get list of shop's urls
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getUrls()
    {
        $sql = 'SELECT *
				FROM '._DB_PREFIX_.'shop_url
				WHERE active = 1
					AND id_shop = '.(int) $this->id;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Check if current shop ID is the same as default shop in configuration
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function isDefaultShop()
    {
        return $this->id == Configuration::get('PS_SHOP_DEFAULT');
    }

    /**
     * Get the associated table if available
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAssoTable($table)
    {
        if (!Shop::$initialized) {
            Shop::init();
        }

        return (isset(Shop::$asso_tables[$table]) ? Shop::$asso_tables[$table] : false);
    }

    /**
     * check if the table has an id_shop_default
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function checkIdShopDefault($table)
    {
        if (!Shop::$initialized) {
            Shop::init();
        }

        return in_array($table, static::$id_shop_default_tables);
    }

    /**
     * Get list of associated tables to shop
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAssoTables()
    {
        if (!Shop::$initialized) {
            Shop::init();
        }

        return Shop::$asso_tables;
    }

    /**
     * Add table associated to shop
     *
     * @param string $table_name
     * @param array  $table_details
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function addTableAssociation($table_name, $table_details)
    {
        if (!isset(Shop::$asso_tables[$table_name])) {
            Shop::$asso_tables[$table_name] = $table_details;
        } else {
            return false;
        }

        return true;
    }

    /**
     * Check if given table is associated to shop
     *
     * @param string $table
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isTableAssociated($table)
    {
        if (!Shop::$initialized) {
            Shop::init();
        }

        return isset(Shop::$asso_tables[$table]) && Shop::$asso_tables[$table]['type'] == 'shop';
    }

    /**
     * Load list of groups and shops, and cache it
     *
     * @param bool $refresh
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cacheShops($refresh = false)
    {
        if (!is_null(static::$shops) && !$refresh) {
            return;
        }

        static::$shops = [];

        $from = '';
        $where = '';

        $employee = Context::getContext()->employee;

        // If the profile isn't a superAdmin
        if (Validate::isLoadedObject($employee) && $employee->id_profile != _PS_ADMIN_PROFILE_) {
            $from .= 'LEFT JOIN '._DB_PREFIX_.'employee_shop es ON es.id_shop = s.id_shop';
            $where .= 'AND es.id_employee = '.(int) $employee->id;
        }

        $sql = 'SELECT gs.*, s.*, gs.name AS group_name, s.name AS shop_name, s.active, su.domain, su.domain_ssl, su.physical_uri, su.virtual_uri
				FROM '._DB_PREFIX_.'shop_group gs
				LEFT JOIN '._DB_PREFIX_.'shop s
					ON s.id_shop_group = gs.id_shop_group
				LEFT JOIN '._DB_PREFIX_.'shop_url su
					ON s.id_shop = su.id_shop AND su.main = 1
				'.$from.'
				WHERE s.deleted = 0
					AND gs.deleted = 0
					'.$where.'
				ORDER BY gs.name, s.name';

        if ($results = Db::getInstance()->executeS($sql)) {
            foreach ($results as $row) {
                if (!isset(static::$shops[$row['id_shop_group']])) {
                    static::$shops[$row['id_shop_group']] = [
                        'id'             => $row['id_shop_group'],
                        'name'           => $row['group_name'],
                        'share_customer' => $row['share_customer'],
                        'share_order'    => $row['share_order'],
                        'share_stock'    => $row['share_stock'],
                        'shops'          => [],
                    ];
                }

                static::$shops[$row['id_shop_group']]['shops'][$row['id_shop']] = [
                    'id_shop'       => $row['id_shop'],
                    'id_shop_group' => $row['id_shop_group'],
                    'name'          => $row['shop_name'],
                    'id_theme'      => $row['id_theme'],
                    'id_category'   => $row['id_category'],
                    'domain'        => $row['domain'],
                    'domain_ssl'    => $row['domain_ssl'],
                    'uri'           => $row['physical_uri'].$row['virtual_uri'],
                    'active'        => $row['active'],
                ];
            }
        }
    }

    /**
     * @return array|null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCompleteListOfShopsID()
    {
        $cacheId = 'Shop::getCompleteListOfShopsID';
        if (!Cache::isStored($cacheId)) {
            $list = [];
            $sql = 'SELECT id_shop FROM '._DB_PREFIX_.'shop';
            foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql) as $row) {
                $list[] = $row['id_shop'];
            }

            Cache::store($cacheId, $list);

            return $list;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Get shops list
     *
     * @param bool $active
     * @param int  $idShopGroup
     * @param bool $getAsListId
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getShops($active = true, $idShopGroup = null, $getAsListId = false)
    {
        Shop::cacheShops();

        $results = [];
        foreach (static::$shops as $group_id => $group_data) {
            foreach ($group_data['shops'] as $id => $shop_data) {
                if ((!$active || $shop_data['active']) && (!$idShopGroup || $idShopGroup == $group_id)) {
                    if ($getAsListId) {
                        $results[$id] = $id;
                    } else {
                        $results[$id] = $shop_data;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getUrlsSharedCart()
    {
        if (!$this->getGroup()->share_order) {
            return false;
        }

        $query = new DbQuery();
        $query->select('domain');
        $query->from('shop_url');
        $query->where('main = 1');
        $query->where('active = 1');
        $query .= $this->addSqlRestriction(Shop::SHARE_ORDER);
        $domains = [];
        foreach (Db::getInstance()->executeS($query) as $row) {
            $domains[] = $row['domain'];
        }

        return $domains;
    }

    /**
     * Get a collection of shops
     *
     * @param bool $active
     * @param int  $idShopGroup
     *
     * @return PrestaShopCollection Collection of Shop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getShopsCollection($active = true, $idShopGroup = null)
    {
        $shops = new PrestaShopCollection('Shop');
        if ($active) {
            $shops->where('active', '=', 1);
        }

        if ($idShopGroup) {
            $shops->where('id_shop_group', '=', (int) $idShopGroup);
        }

        return $shops;
    }

    /**
     * Return some informations cached for one shop
     *
     * @param int $shopId
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getShop($shopId)
    {
        Shop::cacheShops();
        foreach (static::$shops as $group_id => $groupData) {
            if (array_key_exists($shopId, $groupData['shops'])) {
                return $groupData['shops'][$shopId];
            }
        }

        return false;
    }

    /**
     * Return a shop ID from shop name
     *
     * @param string $name
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIdByName($name)
    {
        Shop::cacheShops();
        foreach (static::$shops as $groupData) {
            foreach ($groupData['shops'] as $shop_id => $shopData) {
                if (Tools::strtolower($shopData['name']) == Tools::strtolower($name)) {
                    return $shop_id;
                }
            }
        }

        return false;
    }

    /**
     * @param bool $active
     * @param int  $idShopGroup
     *
     * @return int Total of shops
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTotalShops($active = true, $idShopGroup = null)
    {
        return count(Shop::getShops($active, $idShopGroup));
    }

    /**
     * Retrieve group ID of a shop
     *
     * @param int $shopId Shop ID
     *
     * @return int Group ID
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGroupFromShop($shopId, $asId = true)
    {
        Shop::cacheShops();
        foreach (static::$shops as $groupId => $groupData) {
            if (array_key_exists($shopId, $groupData['shops'])) {
                return ($asId) ? $groupId : $groupData;
            }
        }

        return false;
    }

    /**
     * If the shop group has the option $type activated, get all shops ID of this group, else get current shop ID
     *
     * @param int $shopId
     * @param int $type Shop::SHARE_CUSTOMER | Shop::SHARE_ORDER
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getSharedShops($shopId, $type)
    {
        if (!in_array($type, [Shop::SHARE_CUSTOMER, Shop::SHARE_ORDER, SHOP::SHARE_STOCK])) {
            die('Wrong argument ($type) in Shop::getSharedShops() method');
        }

        Shop::cacheShops();
        foreach (static::$shops as $groupData) {
            if (array_key_exists($shopId, $groupData['shops']) && $groupData[$type]) {
                return array_keys($groupData['shops']);
            }
        }

        return [$shopId];
    }

    /**
     * Get a list of ID concerned by the shop context (E.g. if context is shop group, get list of children shop ID)
     *
     * @param string $share If false, dont check share datas from group. Else can take a Shop::SHARE_* constant value
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getContextListShopID($share = false)
    {
        if (Shop::getContext() == Shop::CONTEXT_SHOP) {
            $list = ($share) ? Shop::getSharedShops(Shop::getContextShopID(), $share) : [Shop::getContextShopID()];
        } elseif (Shop::getContext() == Shop::CONTEXT_GROUP) {
            $list = Shop::getShops(true, Shop::getContextShopGroupID(), true);
        } else {
            $list = Shop::getShops(true, null, true);
        }

        return $list;
    }

    /**
     * Return the list of shop by id
     *
     * @param int    $id
     * @param string $identifier
     * @param string $table
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getShopById($id, $identifier, $table)
    {
        return Db::getInstance()->executeS(
            '
			SELECT `id_shop`, `'.bqSQL($identifier).'`
			FROM `'._DB_PREFIX_.bqSQL($table).'_shop`
			WHERE `'.bqSQL($identifier).'` = '.(int) $id
        );
    }

    /**
     * Change the current shop context
     *
     * @param int $type Shop::CONTEXT_ALL | Shop::CONTEXT_GROUP | Shop::CONTEXT_SHOP
     * @param int $id   ID shop if CONTEXT_SHOP or id shop group if CONTEXT_GROUP
     *
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function setContext($type, $id = null)
    {
        // @codingStandardsIgnoreStart
        switch ($type) {
            case static::CONTEXT_ALL :
                static::$context_id_shop = null;
                static::$context_id_shop_group = null;
                break;

            case static::CONTEXT_GROUP :
                static::$context_id_shop = null;
                static::$context_id_shop_group = (int) $id;
                break;

            case static::CONTEXT_SHOP :
                static::$context_id_shop = (int) $id;
                static::$context_id_shop_group = Shop::getGroupFromShop($id);
                break;

            default :
                throw new PrestaShopException('Unknown context for shop');
        }
        // @codingStandardsIgnoreEnd

        static::$context = $type;
    }

    /**
     * Get current context of shop
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getContext()
    {
        return static::$context;
    }

    /**
     * Get current ID of shop if context is CONTEXT_SHOP
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getContextShopID($nullValueWithoutMultishop = false)
    {
        if ($nullValueWithoutMultishop && !Shop::isFeatureActive()) {
            return null;
        }

        // @codingStandardsIgnoreStart
        return static::$context_id_shop;
        // @codingStandardsIgnoreEnd
    }

    /**
     * Get current ID of shop group if context is CONTEXT_SHOP or CONTEXT_GROUP
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getContextShopGroupID($nullValueWithoutMultishop = false)
    {
        if ($nullValueWithoutMultishop && !Shop::isFeatureActive()) {
            return null;
        }

        // @codingStandardsIgnoreStart
        return static::$context_id_shop_group;
        // @codingStandardsIgnoreEnd
    }

    public static function getContextShopGroup()
    {
        static $contextShopGroup = null;
        if ($contextShopGroup === null) {
            $contextShopGroup = new ShopGroup((int) static::$context_id_shop_group);
        }

        return $contextShopGroup;
    }

    /**
     * Add an sql restriction for shops fields
     *
     * @param int    $share If false, dont check share datas from group. Else can take a Shop::SHARE_* constant value
     * @param string $alias
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function addSqlRestriction($share = false, $alias = null)
    {
        if ($alias) {
            $alias .= '.';
        }

        $group = Shop::getGroupFromShop(Shop::getContextShopID(), false);
        if ($share == Shop::SHARE_CUSTOMER && Shop::getContext() == Shop::CONTEXT_SHOP && $group['share_customer']) {
            $restriction = ' AND '.$alias.'id_shop_group = '.(int) Shop::getContextShopGroupID().' ';
        } else {
            $restriction = ' AND '.$alias.'id_shop IN ('.implode(', ', Shop::getContextListShopID($share)).') ';
        }

        return $restriction;
    }

    /**
     * Add an SQL JOIN in query between a table and its associated table in multishop
     *
     * @param string $table     Table name (E.g. product, module, etc.)
     * @param string $alias     Alias of table
     * @param bool   $innerJoin Use or not INNER JOIN
     * @param string $on
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function addSqlAssociation($table, $alias, $innerJoin = true, $on = null, $forceNotDefault = false)
    {
        $table_alias = $table.'_shop';
        if (strpos($table, '.') !== false) {
            list($table_alias, $table) = explode('.', $table);
        }

        $asso_table = Shop::getAssoTable($table);
        if ($asso_table === false || $asso_table['type'] != 'shop') {
            return;
        }
        $sql = (($innerJoin) ? ' INNER' : ' LEFT').' JOIN '._DB_PREFIX_.$table.'_shop '.$table_alias.'
		ON ('.$table_alias.'.id_'.$table.' = '.$alias.'.id_'.$table;
        if ((int) static::$context_id_shop) {
            $sql .= ' AND '.$table_alias.'.id_shop = '.(int) static::$context_id_shop;
        } elseif (Shop::checkIdShopDefault($table) && !$forceNotDefault) {
            $sql .= ' AND '.$table_alias.'.id_shop = '.$alias.'.id_shop_default';
        } else {
            $sql .= ' AND '.$table_alias.'.id_shop IN ('.implode(', ', Shop::getContextListShopID()).')';
        }
        $sql .= (($on) ? ' AND '.$on : '').')';

        return $sql;
    }

    /**
     * Add a restriction on id_shop for multishop lang table
     *
     * @param string  $alias
     * @param Context $context
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function addSqlRestrictionOnLang($alias = null, $idShop = null)
    {
        if (isset(Context::getContext()->shop) && is_null($idShop)) {
            $idShop = (int) Context::getContext()->shop->id;
        }
        if (!$idShop) {
            $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
        }

        return ' AND '.(($alias) ? $alias.'.' : '').'id_shop = '.$idShop.' ';
    }

    /**
     * Get all groups and associated shops as subarrays
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTree()
    {
        Shop::cacheShops();

        return static::$shops;
    }

    /**
     * @return bool Return true if multishop feature is active and at last 2 shops have been created
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isFeatureActive()
    {
        static $featureActive = null;

        if ($featureActive === null) {
            $featureActive = (bool) Db::getInstance()->getValue('SELECT value FROM `'._DB_PREFIX_.'configuration` WHERE `name` = "PS_MULTISHOP_FEATURE_ACTIVE"')
                && (Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'shop') > 1);
        }

        return $featureActive;
    }

    /**
     * @param      $oldId
     * @param bool $tablesImport
     * @param bool $deleted
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function copyShopData($oldId, $tablesImport = false, $deleted = false)
    {
        // If we duplicate some specific data, automatically duplicate other data linked to the first
        // E.g. if carriers are duplicated for the shop, duplicate carriers langs too

        if (!$oldId) {
            $oldId = Configuration::get('PS_SHOP_DEFAULT');
        }

        if (isset($tablesImport['carrier'])) {
            $tablesImport['carrier_tax_rules_group_shop'] = true;
            $tablesImport['carrier_lang'] = true;
        }

        if (isset($tablesImport['cms'])) {
            $tablesImport['cms_lang'] = true;
            $tablesImport['cms_category'] = true;
            $tablesImport['cms_category_lang'] = true;
        }

        $tablesImport['category_lang'] = true;
        if (isset($tablesImport['product'])) {
            $tablesImport['product_lang'] = true;
        }

        if (isset($tablesImport['module'])) {
            $tablesImport['module_currency'] = true;
            $tablesImport['module_country'] = true;
            $tablesImport['module_group'] = true;
        }

        if (isset($tablesImport['hook_module'])) {
            $tablesImport['hook_module_exceptions'] = true;
        }

        if (isset($tablesImport['attribute_group'])) {
            $tablesImport['attribute'] = true;
        }

        // Browse and duplicate data
        foreach (Shop::getAssoTables() as $tableName => $row) {
            if ($tablesImport && !isset($tablesImport[$tableName])) {
                continue;
            }

            // Special case for stock_available if current shop is in a share stock group
            if ($tableName == 'stock_available') {
                $group = new ShopGroup($this->id_shop_group);
                if ($group->share_stock && $group->haveShops()) {
                    continue;
                }
            }

            $id = 'id_'.$row['type'];
            if ($row['type'] == 'fk_shop') {
                $id = 'id_shop';
            } else {
                $tableName .= '_'.$row['type'];
            }

            if (!$deleted) {
                $res = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.$tableName.'` WHERE `'.$id.'` = '.(int) $oldId);
                if ($res) {
                    unset($res[$id]);
                    if (isset($row['primary'])) {
                        unset($res[$row['primary']]);
                    }

                    $categories = Tools::getValue('categoryBox');
                    if ($tableName == 'product_shop' && count($categories) == 1) {
                        unset($res['id_category_default']);
                        $keys = implode('`, `', array_keys($res));
                        $sql = 'INSERT IGNORE INTO `'._DB_PREFIX_.$tableName.'` (`'.$keys.'`, `id_category_default`, '.$id.')
								(SELECT `'.$keys.'`, '.(int) $categories[0].', '.(int) $this->id.' FROM '._DB_PREFIX_.$tableName.'
								WHERE `'.$id.'` = '.(int) $oldId.')';
                    } else {
                        $keys = implode('`, `', array_keys($res));
                        $sql = 'INSERT IGNORE INTO `'._DB_PREFIX_.$tableName.'` (`'.$keys.'`, '.$id.')
								(SELECT `'.$keys.'`, '.(int) $this->id.' FROM '._DB_PREFIX_.$tableName.'
								WHERE `'.$id.'` = '.(int) $oldId.')';
                    }
                    Db::getInstance()->execute($sql);
                }
            }
        }

        // Hook for duplication of shop data
        $modulesList = Hook::getHookModuleExecList('actionShopDataDuplication');
        if (is_array($modulesList) && count($modulesList) > 0) {
            foreach ($modulesList as $m) {
                if (!$tablesImport || isset($tablesImport['Module'.ucfirst($m['module'])])) {
                    Hook::exec(
                        'actionShopDataDuplication',
                        [
                            'old_id_shop' => (int) $oldId,
                            'new_id_shop' => (int) $this->id,
                        ],
                        $m['id_module']
                    );
                }
            }
        }
    }

    /**
     * @param int $id
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCategories($id = 0, $onlyId = true)
    {
        // build query
        $query = new DbQuery();
        if ($onlyId) {
            $query->select('cs.`id_category`');
        } else {
            $query->select('DISTINCT cs.`id_category`, cl.`name`, cl.`link_rewrite`');
        }
        $query->from('category_shop', 'cs');
        $query->leftJoin('category_lang', 'cl', 'cl.`id_category` = cs.`id_category` AND cl.`id_lang` = '.(int) Context::getContext()->language->id);
        $query->where('cs.`id_shop` = '.(int) $id);
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        if ($onlyId) {
            $array = [];
            foreach ($result as $row) {
                $array[] = $row['id_category'];
            }
            $array = array_unique($array);
        } else {
            return $result;
        }

        return $array;
    }

    /**
     * @deprecated 2.0.0 Use shop->id
     */
    public static function getCurrentShop()
    {
        Tools::displayAsDeprecated();

        return Context::getContext()->shop->id;
    }

    /**
     * @param string $entity
     * @param int    $idShop
     *
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getEntityIds($entity, $idShop, $active = false, $delete = false)
    {
        if (!Shop::isTableAssociated($entity)) {
            return false;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT entity.`id_'.pSQL($entity).'`
			FROM `'._DB_PREFIX_.pSQL($entity).'_shop`es
			LEFT JOIN '._DB_PREFIX_.pSQL($entity).' entity
				ON (entity.`id_'.pSQL($entity).'` = es.`id_'.pSQL($entity).'`)
			WHERE es.`id_shop` = '.(int) $idShop.
            ($active ? ' AND entity.`active` = 1' : '').
            ($delete ? ' AND entity.deleted = 0' : '')
        );
    }
}
