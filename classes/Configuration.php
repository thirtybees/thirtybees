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

use Thirtybees\Core\Database\ReadOnlyConnection;

/**
 * Class ConfigurationCore
 */
class ConfigurationCore extends ObjectModel
{
    // Default configuration consts
    // @since 1.0.1
    // Benefit of these constants unclear. --Traumflug 2018-12-18
    const SEARCH_INDEXATION = 'PS_SEARCH_INDEXATION';
    const ONE_PHONE_AT_LEAST = 'PS_ONE_PHONE_AT_LEAST';
    const GROUP_FEATURE_ACTIVE = 'PS_GROUP_FEATURE_ACTIVE';
    const CARRIER_DEFAULT = 'PS_CARRIER_DEFAULT';
    const CURRENCY_DEFAULT = 'PS_CURRENCY_DEFAULT';
    const COUNTRY_DEFAULT = 'PS_COUNTRY_DEFAULT';
    const REWRITING_SETTINGS = 'PS_REWRITING_SETTINGS';
    const ORDER_OUT_OF_STOCK = 'PS_ORDER_OUT_OF_STOCK';
    const LAST_QTIES = 'PS_LAST_QTIES';
    const CART_REDIRECT = 'PS_CART_REDIRECT';
    const CONDITIONS = 'PS_CONDITIONS';
    const RECYCLABLE_PACK = 'PS_RECYCLABLE_PACK';
    const GIFT_WRAPPING = 'PS_GIFT_WRAPPING';
    const GIFT_WRAPPING_PRICE = 'PS_GIFT_WRAPPING_PRICE';
    const STOCK_MANAGEMENT = 'PS_STOCK_MANAGEMENT';
    const NAVIGATION_PIPE = 'PS_NAVIGATION_PIPE';
    const PRODUCTS_PER_PAGE = 'PS_PRODUCTS_PER_PAGE';
    const PURCHASE_MINIMUM = 'PS_PURCHASE_MINIMUM';
    const PRODUCTS_ORDER_WAY = 'PS_PRODUCTS_ORDER_WAY';
    const PRODUCTS_ORDER_BY = 'PS_PRODUCTS_ORDER_BY';
    const SHIPPING_HANDLING = 'PS_SHIPPING_HANDLING';
    const SHIPPING_FREE_PRICE = 'PS_SHIPPING_FREE_PRICE';
    const SHIPPING_FREE_WEIGHT = 'PS_SHIPPING_FREE_WEIGHT';
    const SHIPPING_METHOD = 'PS_SHIPPING_METHOD';
    const TAX = 'PS_TAX';
    const SHOP_ENABLE = 'PS_SHOP_ENABLE';
    const NB_DAYS_NEW_PRODUCT = 'PS_NB_DAYS_NEW_PRODUCT';
    const SSL_ENABLED = 'PS_SSL_ENABLED';
    const WEIGHT_UNIT = 'PS_WEIGHT_UNIT';
    const BLOCK_CART_AJAX = 'PS_BLOCK_CART_AJAX';
    const ORDER_RETURN = 'PS_ORDER_RETURN';
    const ORDER_RETURN_NB_DAYS = 'PS_ORDER_RETURN_NB_DAYS';
    const MAIL_TYPE = 'PS_MAIL_TYPE';
    const PRODUCT_PICTURE_MAX_SIZE = 'PS_PRODUCT_PICTURE_MAX_SIZE';
    const PRODUCT_PICTURE_WIDTH = 'PS_PRODUCT_PICTURE_WIDTH';
    const PRODUCT_PICTURE_HEIGHT = 'PS_PRODUCT_PICTURE_HEIGHT';
    const INVOICE_PREFIX = 'PS_INVOICE_PREFIX';
    const INVCE_INVOICE_ADDR_RULES = 'PS_INVCE_INVOICE_ADDR_RULES';
    const INVCE_DELIVERY_ADDR_RULES = 'PS_INVCE_DELIVERY_ADDR_RULES';
    const DELIVERY_PREFIX = 'PS_DELIVERY_PREFIX';
    const DELIVERY_NUMBER = 'PS_DELIVERY_NUMBER';
    const RETURN_PREFIX = 'PS_RETURN_PREFIX';
    const INVOICE = 'PS_INVOICE';
    const PASSWD_TIME_BACK = 'PS_PASSWD_TIME_BACK';
    const PASSWD_TIME_FRONT = 'PS_PASSWD_TIME_FRONT';
    const DISP_UNAVAILABLE_ATTR = 'PS_DISP_UNAVAILABLE_ATTR';
    const SEARCH_MINWORDLEN = 'PS_SEARCH_MINWORDLEN';
    const SEARCH_BLACKLIST = 'PS_SEARCH_BLACKLIST';
    const SEARCH_WEIGHT_PNAME = 'PS_SEARCH_WEIGHT_PNAME';
    const SEARCH_WEIGHT_REF = 'PS_SEARCH_WEIGHT_REF';
    const SEARCH_WEIGHT_SHORTDESC = 'PS_SEARCH_WEIGHT_SHORTDESC';
    const SEARCH_WEIGHT_DESC = 'PS_SEARCH_WEIGHT_DESC';
    const SEARCH_WEIGHT_CNAME = 'PS_SEARCH_WEIGHT_CNAME';
    const SEARCH_WEIGHT_MNAME = 'PS_SEARCH_WEIGHT_MNAME';
    const SEARCH_WEIGHT_TAG = 'PS_SEARCH_WEIGHT_TAG';
    const SEARCH_WEIGHT_ATTRIBUTE = 'PS_SEARCH_WEIGHT_ATTRIBUTE';
    const SEARCH_WEIGHT_FEATURE = 'PS_SEARCH_WEIGHT_FEATURE';
    const SEARCH_AJAX = 'PS_SEARCH_AJAX';
    const TIMEZONE = 'PS_TIMEZONE';
    const THEME_V11 = 'PS_THEME_V11';
    const TIN_ACTIVE = 'PS_TIN_ACTIVE';
    const SHOW_ALL_MODULES = 'PS_SHOW_ALL_MODULES';
    const BACKUP_ALL = 'PS_BACKUP_ALL';
    const PRICE_ROUND_MODE = 'PS_PRICE_ROUND_MODE';
    const CONDITIONS_CMS_ID = 'PS_CONDITIONS_CMS_ID';
    const TRACKING_DIRECT_TRAFFIC = 'TRACKING_DIRECT_TRAFFIC';
    const META_KEYWORDS = 'PS_META_KEYWORDS';
    const DISPLAY_JQZOOM = 'PS_DISPLAY_JQZOOM';
    const VOLUME_UNIT = 'PS_VOLUME_UNIT';
    const CIPHER_ALGORITHM = 'PS_CIPHER_ALGORITHM';
    const ATTRIBUTE_CATEGORY_DISPLAY = 'PS_ATTRIBUTE_CATEGORY_DISPLAY';
    const CUSTOMER_SERVICE_FILE_UPLOAD = 'PS_CUSTOMER_SERVICE_FILE_UPLOAD';
    const CUSTOMER_SERVICE_SIGNATURE = 'PS_CUSTOMER_SERVICE_SIGNATURE';
    const BLOCK_BESTSELLERS_DISPLAY = 'PS_BLOCK_BESTSELLERS_DISPLAY';
    const BLOCK_NEWPRODUCTS_DISPLAY = 'PS_BLOCK_NEWPRODUCTS_DISPLAY';
    const BLOCK_SPECIALS_DISPLAY = 'PS_BLOCK_SPECIALS_DISPLAY';
    const STOCK_MVT_REASON_DEFAULT = 'PS_STOCK_MVT_REASON_DEFAULT';
    const COMPARATOR_MAX_ITEM = 'PS_COMPARATOR_MAX_ITEM';
    const ORDER_PROCESS_TYPE = 'PS_ORDER_PROCESS_TYPE';
    const SPECIFIC_PRICE_PRIORITIES = 'PS_SPECIFIC_PRICE_PRIORITIES';
    const TAX_DISPLAY = 'PS_TAX_DISPLAY';
    const SMARTY_FORCE_COMPILE = 'PS_SMARTY_FORCE_COMPILE';
    const DISTANCE_UNIT = 'PS_DISTANCE_UNIT';
    const STORES_DISPLAY_CMS = 'PS_STORES_DISPLAY_CMS';
    const STORES_DISPLAY_FOOTER = 'PS_STORES_DISPLAY_FOOTER';
    const STORES_SIMPLIFIED = 'PS_STORES_SIMPLIFIED';
    const SHOP_LOGO_WIDTH = 'SHOP_LOGO_WIDTH';
    const SHOP_LOGO_HEIGHT = 'SHOP_LOGO_HEIGHT';
    const EDITORIAL_IMAGE_WIDTH = 'EDITORIAL_IMAGE_WIDTH';
    const EDITORIAL_IMAGE_HEIGHT = 'EDITORIAL_IMAGE_HEIGHT';
    const STATSDATA_CUSTOMER_PAGESVIEWS = 'PS_STATSDATA_CUSTOMER_PAGESVIEWS';
    const STATSDATA_PAGESVIEWS = 'PS_STATSDATA_PAGESVIEWS';
    const STATSDATA_PLUGINS = 'PS_STATSDATA_PLUGINS';
    const GEOLOCATION_ENABLED = 'PS_GEOLOCATION_ENABLED';
    const ALLOWED_COUNTRIES = 'PS_ALLOWED_COUNTRIES';
    const GEOLOCATION_BEHAVIOR = 'PS_GEOLOCATION_BEHAVIOR';
    const LOCALE_LANGUAGE = 'PS_LOCALE_LANGUAGE';
    const LOCALE_COUNTRY = 'PS_LOCALE_COUNTRY';
    const ATTACHMENT_MAXIMUM_SIZE = 'PS_ATTACHMENT_MAXIMUM_SIZE';
    const SMARTY_CACHE = 'PS_SMARTY_CACHE';
    const DIMENSION_UNIT = 'PS_DIMENSION_UNIT';
    const GUEST_CHECKOUT_ENABLED = 'PS_GUEST_CHECKOUT_ENABLED';
    const DISPLAY_SUPPLIERS = 'PS_DISPLAY_SUPPLIERS';
    const DISPLAY_BEST_SELLERS = 'PS_DISPLAY_BEST_SELLERS';
    const CATALOG_MODE = 'PS_CATALOG_MODE';
    const GEOLOCATION_WHITELIST = 'PS_GEOLOCATION_WHITELIST';
    const LOGS_BY_EMAIL = 'PS_LOGS_BY_EMAIL';
    const COOKIE_CHECKIP = 'PS_COOKIE_CHECKIP';
    const STORES_CENTER_LAT = 'PS_STORES_CENTER_LAT';
    const STORES_CENTER_LONG = 'PS_STORES_CENTER_LONG';
    const USE_ECOTAX = 'PS_USE_ECOTAX';
    const CANONICAL_REDIRECT = 'PS_CANONICAL_REDIRECT';
    const IMG_UPDATE_TIME = 'PS_IMG_UPDATE_TIME';
    const BACKUP_DROP_TABLE = 'PS_BACKUP_DROP_TABLE';
    const OS_PAYMENT = 'PS_OS_PAYMENT';
    const OS_PREPARATION = 'PS_OS_PREPARATION';
    const OS_SHIPPING = 'PS_OS_SHIPPING';
    const OS_DELIVERED = 'PS_OS_DELIVERED';
    const OS_CANCELED = 'PS_OS_CANCELED';
    const OS_REFUND = 'PS_OS_REFUND';
    const OS_ERROR = 'PS_OS_ERROR';
    const OS_OUTOFSTOCK = 'PS_OS_OUTOFSTOCK';
    const OS_BANKWIRE = 'PS_OS_BANKWIRE';
    const OS_PAYPAL = 'PS_OS_PAYPAL';
    const OS_WS_PAYMENT = 'PS_OS_WS_PAYMENT';
    const OS_OUTOFSTOCK_PAID = 'PS_OS_OUTOFSTOCK_PAID';
    const OS_OUTOFSTOCK_UNPAID = 'PS_OS_OUTOFSTOCK_UNPAID';
    const OS_COD_VALIDATION = 'PS_OS_COD_VALIDATION';
    const IMAGE_QUALITY = 'PS_IMAGE_QUALITY';
    const PNG_QUALITY = 'PS_PNG_QUALITY';
    const JPEG_QUALITY = 'PS_JPEG_QUALITY';
    const COOKIE_LIFETIME_FO = 'PS_COOKIE_LIFETIME_FO';
    const COOKIE_LIFETIME_BO = 'PS_COOKIE_LIFETIME_BO';
    const RESTRICT_DELIVERED_COUNTRIES = 'PS_RESTRICT_DELIVERED_COUNTRIES';
    const SHOW_NEW_ORDERS = 'PS_SHOW_NEW_ORDERS';
    const SHOW_NEW_CUSTOMERS = 'PS_SHOW_NEW_CUSTOMERS';
    const SHOW_NEW_MESSAGES = 'PS_SHOW_NEW_MESSAGES';
    const SHOW_NEW_SYSTEM_NOTIFICATIONS = 'TB_SHOW_NEW_SYSTEM_NOTIFICATIONS';
    const FEATURE_FEATURE_ACTIVE = 'PS_FEATURE_FEATURE_ACTIVE';
    const COMBINATION_FEATURE_ACTIVE = 'PS_COMBINATION_FEATURE_ACTIVE';
    const SPECIFIC_PRICE_FEATURE_ACTIVE = 'PS_SPECIFIC_PRICE_FEATURE_ACTIVE';
    const SCENE_FEATURE_ACTIVE = 'PS_SCENE_FEATURE_ACTIVE';
    const VIRTUAL_PROD_FEATURE_ACTIVE = 'PS_VIRTUAL_PROD_FEATURE_ACTIVE';
    const CUSTOMIZATION_FEATURE_ACTIVE = 'PS_CUSTOMIZATION_FEATURE_ACTIVE';
    const CART_RULE_FEATURE_ACTIVE = 'PS_CART_RULE_FEATURE_ACTIVE';
    const PACK_FEATURE_ACTIVE = 'PS_PACK_FEATURE_ACTIVE';
    const ALIAS_FEATURE_ACTIVE = 'PS_ALIAS_FEATURE_ACTIVE';
    const TAX_ADDRESS_TYPE = 'PS_TAX_ADDRESS_TYPE';
    const SHOP_DEFAULT = 'PS_SHOP_DEFAULT';
    const CARRIER_DEFAULT_SORT = 'PS_CARRIER_DEFAULT_SORT';
    const STOCK_MVT_INC_REASON_DEFAULT = 'PS_STOCK_MVT_INC_REASON_DEFAULT';
    const STOCK_MVT_DEC_REASON_DEFAULT = 'PS_STOCK_MVT_DEC_REASON_DEFAULT';
    const ADVANCED_STOCK_MANAGEMENT = 'PS_ADVANCED_STOCK_MANAGEMENT';
    const ADMINREFRESH_NOTIFICATION = 'PS_ADMINREFRESH_NOTIFICATION';
    const STOCK_MVT_TRANSFER_TO = 'PS_STOCK_MVT_TRANSFER_TO';
    const STOCK_MVT_TRANSFER_FROM = 'PS_STOCK_MVT_TRANSFER_FROM';
    const CARRIER_DEFAULT_ORDER = 'PS_CARRIER_DEFAULT_ORDER';
    const STOCK_MVT_SUPPLY_ORDER = 'PS_STOCK_MVT_SUPPLY_ORDER';
    const STOCK_CUSTOMER_ORDER_REASON = 'PS_STOCK_CUSTOMER_ORDER_REASON';
    const UNIDENTIFIED_GROUP = 'PS_UNIDENTIFIED_GROUP';
    const GUEST_GROUP = 'PS_GUEST_GROUP';
    const CUSTOMER_GROUP = 'PS_CUSTOMER_GROUP';
    const SMARTY_CONSOLE = 'PS_SMARTY_CONSOLE';
    const INVOICE_MODEL = 'PS_INVOICE_MODEL';
    const LIMIT_UPLOAD_IMAGE_VALUE = 'PS_LIMIT_UPLOAD_IMAGE_VALUE';
    const LIMIT_UPLOAD_FILE_VALUE = 'PS_LIMIT_UPLOAD_FILE_VALUE';
    const TOKEN_ENABLE = 'PS_TOKEN_ENABLE';
    CONST BO_FORCE_TOKEN = 'TB_BO_FORCE_TOKEN';
    const STATS_RENDER = 'PS_STATS_RENDER';
    const STATS_OLD_CONNECT_AUTO_CLEAN = 'PS_STATS_OLD_CONNECT_AUTO_CLEAN';
    const STATS_GRID_RENDER = 'PS_STATS_GRID_RENDER';
    const BASE_DISTANCE_UNIT = 'PS_BASE_DISTANCE_UNIT';
    const SHOP_DOMAIN = 'PS_SHOP_DOMAIN';
    const SHOP_DOMAIN_SSL = 'PS_SHOP_DOMAIN_SSL';
    const SHOP_NAME = 'PS_SHOP_NAME';
    const SHOP_EMAIL = 'PS_SHOP_EMAIL';
    const MAIL_METHOD = 'PS_MAIL_METHOD';
    const SHOP_ACTIVITY = 'PS_SHOP_ACTIVITY';
    const LOGO = 'PS_LOGO';
    const FAVICON = 'PS_FAVICON';
    const STORES_ICON = 'PS_STORES_ICON';
    const ROOT_CATEGORY = 'PS_ROOT_CATEGORY';
    const HOME_CATEGORY = 'PS_HOME_CATEGORY';
    const CONFIGURATION_AGREMENT = 'PS_CONFIGURATION_AGREMENT';
    const MAIL_SERVER = 'PS_MAIL_SERVER';
    const MAIL_USER = 'PS_MAIL_USER';
    const MAIL_PASSWD = 'PS_MAIL_PASSWD';
    const MAIL_SMTP_ENCRYPTION = 'PS_MAIL_SMTP_ENCRYPTION';
    const MAIL_SMTP_PORT = 'PS_MAIL_SMTP_PORT';
    const MAIL_COLOR = 'PS_MAIL_COLOR';
    const PAYMENT_LOGO_CMS_ID = 'PS_PAYMENT_LOGO_CMS_ID';
    const ALLOW_MOBILE_DEVICE = 'PS_ALLOW_MOBILE_DEVICE';
    const CUSTOMER_CREATION_EMAIL = 'PS_CUSTOMER_CREATION_EMAIL';
    const SMARTY_CONSOLE_KEY = 'PS_SMARTY_CONSOLE_KEY';
    const ATTRIBUTE_ANCHOR_SEPARATOR = 'PS_ATTRIBUTE_ANCHOR_SEPARATOR';
    const DASHBOARD_SIMULATION = 'PS_DASHBOARD_SIMULATION';
    const QUICK_VIEW = 'PS_QUICK_VIEW';
    const USE_HTMLPURIFIER = 'PS_USE_HTMLPURIFIER';
    const SMARTY_CACHING_TYPE = 'PS_SMARTY_CACHING_TYPE';
    const SMARTY_CLEAR_CACHE = 'PS_SMARTY_CLEAR_CACHE';
    const DETECT_LANG = 'PS_DETECT_LANG';
    const DETECT_COUNTRY = 'PS_DETECT_COUNTRY';
    const ROUND_TYPE = 'PS_ROUND_TYPE';
    const PRICE_DISPLAY_PRECISION = 'PS_PRICE_DISPLAY_PRECISION';
    const LOG_EMAILS = 'PS_LOG_EMAILS';
    const CUSTOMER_NWSL = 'PS_CUSTOMER_NWSL';
    const CUSTOMER_OPTIN = 'PS_CUSTOMER_OPTIN';
    const PACK_STOCK_TYPE = 'PS_PACK_STOCK_TYPE';
    const LOG_MODULE_PERFS_MODULO = 'PS_LOG_MODULE_PERFS_MODULO';
    const DISALLOW_HISTORY_REORDERING = 'PS_DISALLOW_HISTORY_REORDERING';
    const DISPLAY_PRODUCT_WEIGHT = 'PS_DISPLAY_PRODUCT_WEIGHT';
    const PRODUCT_WEIGHT_PRECISION = 'PS_PRODUCT_WEIGHT_PRECISION';
    const ADVANCED_PAYMENT_API = 'PS_ADVANCED_PAYMENT_API';
    const PAGE_CACHE_CONTROLLERS = 'TB_PAGE_CACHE_CONTROLLERS';
    const PAGE_CACHE_IGNOREPARAMS = 'PS_ADVANCED_PAYMENT_API';
    const ROUTE_PRODUCT_RULE = 'PS_ROUTE_product_rule';
    const ROUTE_CATEGORY_RULE = 'PS_ROUTE_category_rule';
    const ROUTE_SUPPLIER_RULE = 'PS_ROUTE_supplier_rule';
    const ROUTE_MANUFACTURER_RULE = 'PS_ROUTE_manufacturer_rule';
    const ROUTE_CMS_RULE = 'PS_ROUTE_cms_rule';
    const ROUTE_CMS_CATEGORY_RULE = 'PS_ROUTE_cms_category_rule';
    const DISABLE_OVERRIDES = 'PS_DISABLE_OVERRIDES';
    const DISABLE_NON_NATIVE_MODULE = 'PS_DISABLE_NON_NATIVE_MODULE';
    const CUSTOMCODE_METAS = 'TB_CUSTOMCODE_METAS';
    const CUSTOMCODE_CSS = 'TB_CUSTOMCODE_CSS';
    const CUSTOMCODE_JS = 'TB_CUSTOMCODE_JS';
    const CUSTOMCODE_ORDERCONF_JS = 'TB_CUSTOMCODE_ORDERCONF_JS';
    const STORE_REGISTERED = 'TB_STORE_REGISTERED';
    const MAIL_SUBJECT_TEMPLATE = 'TB_MAIL_SUBJECT_TEMPLATE';
    const API_SERVER_OVERRIDE = 'TB_API_SERVER_OVERRIDE';
    const SSL_TRUST_STORE_TYPE = 'TB_SSL_TRUST_STORE_TYPE';
    const SSL_TRUST_STORE = 'TB_SSL_TRUST_STORE';
    const TRACKING_ID = 'TB_TRACKING_UID';
    const MAIL_TRANSPORT = 'TB_MAIL_TRANSPORT';
    const BECOME_SUPPORTER_URL = 'TB_SUPPORTER_URL';
    const SUPPORTER_TYPE = 'TB_SUPPORTER_TYPE';
    const SUPPORTER_TYPE_NAME = 'TB_SUPPORTER_TYPE_NAME';
    const CONNECTED = 'TB_CONNECTED';
    const CONNECT_CODE = 'TB_CONNECT_CODE';

    /**
     * List of configuration keys that will raise warnings
     */
    const DEPRECATED_CONFIG_KEYS = [
        self::PRICE_DISPLAY_PRECISION => 'Use Currency::getDisplayPrecision() method instead'
    ];

    const LAST_SEEN_NOTIFICATION_UUID = 'TB_LAST_SEEN_NOTIFICATION_UUID';

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'     => 'configuration',
        'primary'   => 'id_configuration',
        'multilang' => true,
        'fields'    => [
            'id_shop_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'int(11) unsigned'],
            'id_shop'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'int(11) unsigned'],
            'name'          => ['type' => self::TYPE_STRING, 'validate' => 'isConfigName', 'required' => true, 'size' => 254],
            'value'         => ['type' => self::TYPE_STRING, 'size' => ObjectModel::SIZE_TEXT],
            'date_add'      => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
            'date_upd'      => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        ],
        'keys' => [
            'configuration' => [
                'id_shop'       => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
                'id_shop_group' => ['type' => ObjectModel::KEY, 'columns' => ['id_shop_group']],
            ],
            'configuration_kpi' => [
                'id_shop'       => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
                'id_shop_group' => ['type' => ObjectModel::KEY, 'columns' => ['id_shop_group']],
                'name'          => ['type' => ObjectModel::KEY, 'columns' => ['name']],
            ],
            'configuration_kpi_lang' => [
                'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_configuration_kpi', 'id_lang']],
            ],
        ],
    ];

    /**
     * @var array Configuration cache
     */
    protected static $_cache = [];

    /**
     * @var array Vars types
     */
    protected static $types = [];

    /**
     * @var mixed
     */
    protected static $checkDeprecatedKeys = true;

    /**
     * @var string Key
     */
    public $name;

    /**
     * @var int
     */
    public $id_shop_group;

    /**
     * @var int
     */
    public $id_shop;

    /**
     * @var string Value
     */
    public $value;

    /**
     * @var string Object creation date
     */
    public $date_add;

    /**
     * @var string Object last modification date
     */
    public $date_upd;

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'fields' => [
            'value' => [],
        ],
    ];

    /**
     * @return bool|null
     */
    public static function configurationIsLoaded()
    {
        return isset(static::$_cache[static::$definition['table']])
               && is_array(static::$_cache[static::$definition['table']])
               && count(static::$_cache[static::$definition['table']]);
    }

    /**
     * WARNING: For testing only. Do NOT rely on this method, it may be removed at any time.
     *
     * @todo    Delegate static calls from Configuration to an instance of a class to be created.
     */
    public static function clearConfigurationCacheForTesting()
    {
        static::$_cache = [];
    }

    /**
     * @param string $key
     * @param int|null $idLang
     *
     * @return string|null|false
     *
     * @throws PrestaShopException
     */
    public static function getGlobalValue($key, $idLang = null)
    {
        return Configuration::get($key, $idLang, 0, 0);
    }

    /**
     * Get a single configuration value (in one language only)
     *
     * @param string $key Key wanted
     * @param int $idLang Language ID
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return string|null|false Value
     *
     * @throws PrestaShopException
     */
    public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        if (defined('_PS_DO_NOT_LOAD_CONFIGURATION_') && _PS_DO_NOT_LOAD_CONFIGURATION_) {
            return false;
        }
        static::validateKey($key);

        if ( ! static::configurationIsLoaded()) {
            Configuration::loadConfiguration();
        }

        $idLang = (int) $idLang;
        if ($idShop === null || !Shop::isFeatureActive()) {
            $idShop = Shop::getContextShopID(true);
        }
        if ($idShopGroup === null || !Shop::isFeatureActive()) {
            $idShopGroup = Shop::getContextShopGroupID(true);
        }

        if (!isset(static::$_cache[static::$definition['table']][$idLang])) {
            $idLang = 0;
        }

        if ($idShop && Configuration::hasKey($key, $idLang, null, $idShop)) {
            return static::$_cache[static::$definition['table']][$idLang]['shop'][$idShop][$key];
        } elseif ($idShopGroup && Configuration::hasKey($key, $idLang, $idShopGroup)) {
            return static::$_cache[static::$definition['table']][$idLang]['group'][$idShopGroup][$key];
        } elseif (Configuration::hasKey($key, $idLang)) {
            return static::$_cache[static::$definition['table']][$idLang]['global'][$key];
        }

        return false;
    }

    /**
     * Get a single configuration value for a get that has been deprecated.
     *
     * @param string $key Key wanted
     * @param int $idLang Language ID
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return string|null|false Value
     *
     * @throws PrestaShopException
     */
    public static function getDeprecatedKey($key, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        $save = static::$checkDeprecatedKeys;
        static::$checkDeprecatedKeys = false;
        try {
            return static::get($key);
        } finally {
            static::$checkDeprecatedKeys = $save;
        }
    }

    /**
     * Update deprecated configuration key and value into database
     *
     * @param string $key Key
     * @param mixed $values $values is an array if the configuration is multilingual, a single string else.
     * @param bool $html Specify if html is authorized in value
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function updateDeprecatedKey($key, $values, $html = false, $idShopGroup = null, $idShop = null)
    {
        $save = static::$checkDeprecatedKeys;
        static::$checkDeprecatedKeys = false;
        try {
            return static::updateValue($key, $values, $html, $idShopGroup, $idShop);
        } finally {
            static::$checkDeprecatedKeys = $save;
        }
    }

    /**
     * Load all configuration data
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function loadConfiguration()
    {
        static::loadConfigurationFromDB(Db::readOnly());
    }

    /**
     * Load all configuration data, using an existing database connection.
     *
     * @param ReadOnlyConnection $connection Database connection to be used for data retrieval.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function loadConfigurationFromDB($connection)
    {
        static::$_cache[static::$definition['table']] = [];

        $rows = $connection->getArray(
            (new DbQuery())
                ->select('c.`name`, cl.`id_lang`, IFNULL(cl.`value`, c.`value`) AS `value`, c.`id_shop_group`, c.`id_shop`')
                ->from(static::$definition['table'], 'c')
                ->leftJoin(static::$definition['table'].'_lang', 'cl', 'c.`'.static::$definition['primary'].'` = cl.`'.static::$definition['primary'].'`')
        );

        foreach ($rows as $row) {
            $lang = ($row['id_lang']) ? $row['id_lang'] : 0;
            static::$types[$row['name']] = ($lang) ? 'lang' : 'normal';
            if (!isset(static::$_cache[static::$definition['table']][$lang])) {
                static::$_cache[static::$definition['table']][$lang] = [
                    'global' => [],
                    'group'  => [],
                    'shop'   => [],
                ];
            }

            if ($row['id_shop']) {
                static::$_cache[static::$definition['table']][$lang]['shop'][$row['id_shop']][$row['name']] = $row['value'];
            } elseif ($row['id_shop_group']) {
                static::$_cache[static::$definition['table']][$lang]['group'][$row['id_shop_group']][$row['name']] = $row['value'];
            } else {
                static::$_cache[static::$definition['table']][$lang]['global'][$row['name']] = $row['value'];
            }
        }
    }

    /**
     * Check if key exists in configuration
     *
     * @param string $key
     * @param int|null $idLang
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function hasKey($key, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        static::validateKey($key);

        if ( ! static::configurationIsLoaded()) {
            Configuration::loadConfiguration();
        }

        $idLang = (int) $idLang;

        if ($idShop) {
            return isset(static::$_cache[static::$definition['table']][$idLang]['shop'][$idShop])
                && (isset(static::$_cache[static::$definition['table']][$idLang]['shop'][$idShop][$key])
                    || array_key_exists($key, static::$_cache[static::$definition['table']][$idLang]['shop'][$idShop]));
        } elseif ($idShopGroup) {
            return isset(static::$_cache[static::$definition['table']][$idLang]['group'][$idShopGroup])
                && (isset(static::$_cache[static::$definition['table']][$idLang]['group'][$idShopGroup][$key])
                    || array_key_exists($key, static::$_cache[static::$definition['table']][$idLang]['group'][$idShopGroup]));
        }

        return isset(static::$_cache[static::$definition['table']][$idLang]['global'])
            && (isset(static::$_cache[static::$definition['table']][$idLang]['global'][$key])
                || array_key_exists($key, static::$_cache[static::$definition['table']][$idLang]['global']));
    }

    /**
     * Get a single configuration value (in multiple languages)
     *
     * @param string $key Key wanted
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return array Values in multiple languages
     *
     * @throws PrestaShopException
     */
    public static function getInt($key, $idShopGroup = null, $idShop = null)
    {
        $resultsArray = [];
        foreach (Language::getIDs() as $idLang) {
            $resultsArray[$idLang] = Configuration::get($key, $idLang, $idShopGroup, $idShop);
        }

        return $resultsArray;
    }

    /**
     * Get a single configuration value for all shops
     *
     * @param string $key Key wanted
     * @param int $idLang
     *
     * @return array Values for all shops
     *
     * @throws PrestaShopException
     */
    public static function getMultiShopValues($key, $idLang = null)
    {
        $shops = Shop::getShops(false, null, true);
        $resultsArray = [];
        foreach ($shops as $idShop) {
            $resultsArray[$idShop] = Configuration::get($key, $idLang, null, $idShop);
        }

        return $resultsArray;
    }

    /**
     * Get several configuration values (in one language only)
     *
     * @throws PrestaShopException
     *
     * @param array $keys Keys wanted
     * @param int $idLang Language ID
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return array Values
     */
    public static function getMultiple($keys, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        if (!is_array($keys)) {
            throw new PrestaShopException('keys var is not an array');
        }

        $idLang = (int) $idLang;
        if ($idShop === null) {
            $idShop = Shop::getContextShopID(true);
        }
        if ($idShopGroup === null) {
            $idShopGroup = Shop::getContextShopGroupID(true);
        }

        $results = [];
        foreach ($keys as $key) {
            $results[$key] = Configuration::get($key, $idLang, $idShopGroup, $idShop);
        }

        return $results;
    }

    /**
     * Update configuration key for global context only
     *
     * This method escapes $values with pSQL().
     *
     * @param string $key
     * @param mixed $values
     * @param bool $html
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function updateGlobalValue($key, $values, $html = false)
    {
        return Configuration::updateValue($key, $values, $html, 0, 0);
    }

    /**
     * Update configuration key and value into database (automatically insert if key does not exist)
     *
     * @param string $key Key
     * @param mixed $values $values is an array if the configuration is multilingual, a single string else.
     * @param bool $html Specify if html is authorized in value
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return bool Update result
     *
     * @throws PrestaShopException
     */
    public static function updateValue($key, $values, $html = false, $idShopGroup = null, $idShop = null)
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        // sanitize values
        foreach ($values as &$value) {
            if (!is_null($value) && !is_numeric($value)) {
                if ($html) {
                    // if html values are allowed, just purify html code
                    $value = Tools::purifyHTML($value);
                } else {
                    // if html values are not allowed, strip tags
                    $value = strip_tags($value);
                }
            }
        }

        return self::updateValueRaw($key, $values, $idShopGroup, $idShop);
    }

    /**
     * Update configuration key and value into database and cache
     *
     * Values are inserted/updated directly using SQL, because using (Configuration) ObjectModel
     * may not insert values correctly (for example, HTML is escaped, when it should not be).
     *
     * @param string $key Key
     * @param mixed $values $values is an array if the configuration is multilingual, a single string else.
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return bool Update result
     *
     * @throws PrestaShopException
     */
    public static function updateValueRaw($key, $values, $idShopGroup = null, $idShop = null)
    {
        static::validateKey($key);

        if ($idShop === null || !Shop::isFeatureActive()) {
            $idShop = Shop::getContextShopID(true);
        }

        if ($idShopGroup === null || !Shop::isFeatureActive()) {
            $idShopGroup = Shop::getContextShopGroupID(true);
        }

        if (!is_array($values)) {
            $values = [$values];
        }

        $conn = Db::getInstance();
        $result = true;
        foreach ($values as $lang => $rawValue) {
            $lang = (int)$lang;
            $value = pSQL($rawValue, true);
            if (Configuration::hasKey($key, $lang, $idShopGroup, $idShop)) {
                // If key exists already, update value.
                if (!$lang) {
                    // Update config not linked to lang
                    $result = $conn->update(
                        static::$definition['table'],
                        [
                            'value'    => $value,
                            'date_upd' => date('Y-m-d H:i:s'),
                        ],
                        '`name` = \''.$key.'\''.Configuration::sqlRestriction($idShopGroup, $idShop),
                        1,
                        true
                    ) && $result;
                } else {
                    // Update multi lang
                    $sql = 'UPDATE `'._DB_PREFIX_.static::$definition['table'].'_lang` cl
                            SET cl.value = \''.$value.'\',
                                cl.date_upd = NOW()
                            WHERE cl.id_lang = '.(int) $lang.'
                                AND cl.`'.static::$definition['primary'].'` = (
                                    SELECT c.`'.static::$definition['primary'].'`
                                    FROM `'._DB_PREFIX_.static::$definition['table'].'` c
                                    WHERE c.name = \''.$key.'\''
                        .Configuration::sqlRestriction($idShopGroup, $idShop)
                        .')';
                    $result = $conn->execute($sql) && $result;
                }
            } else {
                // If key doesn't exist, create it.
                if (!$configID = Configuration::getIdByName($key, $idShopGroup, $idShop)) {
                    $data = [
                        'id_shop_group' => $idShopGroup ? (int) $idShopGroup : null,
                        'id_shop'       => $idShop ? (int) $idShop : null,
                        'name'          => $key,
                        'value'         => $lang ? null : $value,
                        'date_add'      => ['type' => 'sql', 'value' => 'NOW()'],
                        'date_upd'      => ['type' => 'sql', 'value' => 'NOW()'],
                    ];
                    $result = $conn->insert(static::$definition['table'], $data, true) && $result;
                    $configID = $conn->Insert_ID();
                }

                if ($lang) {
                    $result = $conn->insert(
                        static::$definition['table'].'_lang',
                        [
                            static::$definition['primary'] => $configID,
                            'id_lang'                    => (int) $lang,
                            'value'                      => $value,
                            'date_upd'                   => date('Y-m-d H:i:s'),
                        ]
                    ) && $result;
                }
            }
        }

        Configuration::set($key, $values, $idShopGroup, $idShop);

        return $result;
    }

    /**
     * Add SQL restriction on shops for configuration table
     *
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return string
     */
    protected static function sqlRestriction($idShopGroup, $idShop)
    {
        if ($idShop) {
            return ' AND id_shop = '.(int) $idShop;
        } elseif ($idShopGroup) {
            return ' AND id_shop_group = '.(int) $idShopGroup.' AND (id_shop IS NULL OR id_shop = 0)';
        } else {
            return ' AND (id_shop_group IS NULL OR id_shop_group = 0) AND (id_shop IS NULL OR id_shop = 0)';
        }
    }

    /**
     * Return ID a configuration key
     *
     * @param string $key
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return int Configuration key ID
     *
     * @throws PrestaShopException
     */
    public static function getIdByName($key, $idShopGroup = null, $idShop = null)
    {
        static::validateKey($key);

        if ($idShop === null) {
            $idShop = Shop::getContextShopID(true);
        }
        if ($idShopGroup === null) {
            $idShopGroup = Shop::getContextShopGroupID(true);
        }

        $sql = 'SELECT `'.static::$definition['primary'].'`
                FROM `'._DB_PREFIX_.static::$definition['table'].'`
                WHERE name = \''.$key.'\'
                '.Configuration::sqlRestriction($idShopGroup, $idShop);

        return (int) Db::readOnly()->getValue($sql);
    }

    /**
     * Set TEMPORARY a single configuration value (in one language only)
     *
     * This method expects $values to be escaped with pSQL() already (to change
     * this, we'd need $html in the signature).
     *
     * Note: a need for calling this method directly should be rare.
     *       updateValue() and updateGlobalValue() do this on their own already.
     *
     * @param string $key Key wanted
     * @param mixed $values $values is an array if the configuration is multilingual, a single string else.
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @throws PrestaShopException
     */
    public static function set($key, $values, $idShopGroup = null, $idShop = null)
    {
        static::validateKey($key);

        if ($idShop === null) {
            $idShop = Shop::getContextShopID(true);
        }
        if ($idShopGroup === null) {
            $idShopGroup = Shop::getContextShopGroupID(true);
        }

        if (!is_array($values)) {
            $values = [$values];
        }

        foreach ($values as $lang => $value) {
            if ($idShop) {
                static::$_cache[static::$definition['table']][$lang]['shop'][$idShop][$key] = $value;
            } elseif ($idShopGroup) {
                static::$_cache[static::$definition['table']][$lang]['group'][$idShopGroup][$key] = $value;
            } else {
                static::$_cache[static::$definition['table']][$lang]['global'][$key] = $value;
            }
        }
    }

    /**
     * Delete a configuration key in database (with or without language management)
     *
     * @param string $key Key to delete
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public static function deleteByName($key)
    {
        static::validateKey($key);

        $conn = Db::getInstance();
        $result = $conn->execute(
            '
        DELETE FROM `'._DB_PREFIX_.static::$definition['table'].'_lang`
        WHERE `'.static::$definition['primary'].'` IN (
            SELECT `'.static::$definition['primary'].'`
            FROM `'._DB_PREFIX_.static::$definition['table'].'`
            WHERE `name` = "'.$key.'"
        )'
        );

        $result2 = $conn->delete(static::$definition['table'], '`name` = "'.$key.'"');

        static::$_cache[static::$definition['table']] = null;

        return ($result && $result2);
    }

    /**
     * Delete configuration key from current context.
     *
     * @param string $key
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function deleteFromContext($key)
    {
        if (Shop::getContext() == Shop::CONTEXT_ALL) {
            return;
        }

        $idShop = null;
        $idShopGroup = Shop::getContextShopGroupID(true);
        if (Shop::getContext() == Shop::CONTEXT_SHOP) {
            $idShop = Shop::getContextShopID(true);
        }

        $id = Configuration::getIdByName($key, $idShopGroup, $idShop);
        $conn = Db::getInstance();
        $conn->delete(
            static::$definition['table'],
            '`'.static::$definition['primary'].'` = '.(int) $id
        );
        $conn->delete(
            static::$definition['table'].'_lang',
            '`'.static::$definition['primary'].'` = '.(int) $id
        );

        static::$_cache[static::$definition['table']] = null;
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function isOverridenByCurrentContext($key)
    {
        if (! Shop::isFeatureActive()) {
            return false;
        }
        if (Shop::getContext() == Shop::CONTEXT_ALL) {
            return false;
        }

        if (static::isLangKey($key)) {
            foreach (Language::getIDs(false) as $idLang) {
                if (static::hasContext($key, $idLang, Shop::getContext())) {
                    return true;
                }
            }
            return false;
        } else {
            return static::hasContext($key, null, Shop::getContext());
        }
    }

    /**
     * Check if a key was loaded as multi lang
     *
     * @param string $key
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function isLangKey($key)
    {
        static::validateKey($key);

        return isset(static::$types[$key]) && static::$types[$key] == 'lang';
    }

    /**
     * Check if configuration var is defined in given context
     *
     * @param string $key
     * @param int|null $idLang
     * @param int $context
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @return bool
     */
    public static function hasContext($key, $idLang, $context)
    {
        if (Shop::getContext() == Shop::CONTEXT_ALL) {
            $idShop = $idShopGroup = null;
        } elseif (Shop::getContext() == Shop::CONTEXT_GROUP) {
            $idShopGroup = Shop::getContextShopGroupID(true);
            $idShop = null;
        } else {
            $idShopGroup = Shop::getContextShopGroupID(true);
            $idShop = Shop::getContextShopID(true);
        }

        if ($context == Shop::CONTEXT_SHOP && Configuration::hasKey($key, $idLang, null, $idShop)) {
            return true;
        } elseif ($context == Shop::CONTEXT_GROUP && Configuration::hasKey($key, $idLang, $idShopGroup)) {
            return true;
        } elseif ($context == Shop::CONTEXT_ALL && Configuration::hasKey($key, $idLang)) {
            return true;
        }

        return false;
    }

    /**
     * @return bool|array Multilingual fields
     *
     * @throws PrestaShopException
     */
    public function getFieldsLang()
    {
        if (!is_array($this->value)) {
            return true;
        }

        return parent::getFieldsLang();
    }

    /**
     * This method is override to allow TranslatedConfiguration entity
     *
     * @param string $sqlJoin
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getWebserviceObjectList($sqlJoin, $sqlFilter, $sqlSort, $sqlLimit)
    {
        $query = '
        SELECT DISTINCT main.`'.static::$definition['primary'].'`
        FROM `'._DB_PREFIX_.static::$definition['table'].'` main
        '.$sqlJoin.'
        WHERE `'.static::$definition['primary'].'` NOT IN (
            SELECT `'.static::$definition['primary'].'`
            FROM '._DB_PREFIX_.static::$definition['table'].'_lang
        ) '.$sqlFilter.'
        '.($sqlSort != '' ? $sqlSort : '').'
        '.($sqlLimit != '' ? $sqlLimit : '');

        return Db::readOnly()->getArray($query);
    }

    /**
     * Validate a configuration key. Throws an exception for invalid keys.
     *
     * @param string $key
     *
     * @throws PrestaShopException
     */
    protected static function validateKey($key)
    {
        if ( ! Validate::isConfigName($key)) {
            $message= sprintf(
                Tools::displayError('[%s] is not a valid configuration key'),
                Tools::htmlentitiesUTF8($key)
            );
            trigger_error($message, E_USER_WARNING);
            throw new PrestaShopException($message);
        }

        if (static::$checkDeprecatedKeys && array_key_exists($key, static::DEPRECATED_CONFIG_KEYS)) {
            $callPoint = Tools::getCallPoint([Configuration::class]);
            $message = sprintf(Tools::displayError("Configuration key [%s] is deprecated."), $key) . ' ';
            $message .= trim(static::DEPRECATED_CONFIG_KEYS[$key]) . '. ';
            $message .= 'Called from: ' . $callPoint['description'];
            trigger_error($message, E_USER_DEPRECATED);
        }
    }

    /**
     * Returns url to thirty bees api server
     *
     * Default api url can be overridden using configuration key TB_API_SERVER_OVERRIDE. This should be used
     * by thirty bees developers only
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function getApiServer()
    {
        $baseUriOverride = static::getGlobalValue(static::API_SERVER_OVERRIDE);
        if ($baseUriOverride) {
            $baseUriOverride = rtrim($baseUriOverride, '/');
            if (Validate::isAbsoluteUrl($baseUriOverride)) {
                return $baseUriOverride;
            }
        }
        return 'https://api.thirtybees.com';
    }

    /**
     * Returns path to trust store that should be used to verify SSL connections.
     *
     * If this method returns true, then operation-system trust store will be used
     * If this method returns false, then SSL certificates will not be used
     *
     * @return string | boolean
     * @throws PrestaShopException
     */
    public static function getSslTrustStore()
    {
        $type = static::getGlobalValue(static::SSL_TRUST_STORE_TYPE);
        switch (strtolower($type)) {
            case 'system':
                return true;
            case 'disable':
                return false;
            case 'custom':
            default:
                $path = static::getGlobalValue(static::SSL_TRUST_STORE);
                if (! $path) {
                    $path = _PS_TOOL_DIR_.'cacert.pem';
                }
                return $path;
        }
    }

    /**
     * Returns unique identifier of this installation, for tracking purposes
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function getServerTrackingId()
    {
        $trackingId = static::getGlobalValue(Configuration::TRACKING_ID);
        if (! $trackingId) {
            $trackingId = Tools::passwdGen(40);
            static::updateGlobalValue(Configuration::TRACKING_ID, $trackingId);
        }
        return $trackingId;
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function getBecomeSupporterUrl()
    {
        $url = static::getGlobalValue(static::BECOME_SUPPORTER_URL);
        if (! $url) {
            $url = "https://forum.thirtybees.com/support-thirty-bees/?sid=@SID@";
        }
        return str_replace("@SID@", static::getServerTrackingId(), $url);
    }

    /**
     * @return array|null
     *
     * @throws PrestaShopException
     */
    public static function getSupporterInfo()
    {
        $type = static::getGlobalValue(static::SUPPORTER_TYPE);
        if ($type) {
            return [
                'type' => $type,
                'name' => static::getGlobalValue(static::SUPPORTER_TYPE_NAME)
            ];
        }
        return null;
    }
}
