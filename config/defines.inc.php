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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/* Debug only */
if (!defined('_PS_MODE_DEV_')) {
    define('_PS_MODE_DEV_', false);
}
/* Compatibility warning */
if (!defined('_PS_DISPLAY_COMPATIBILITY_WARNING_')) {
    define('_PS_DISPLAY_COMPATIBILITY_WARNING_', true);
}

/* Check SQL errors by default */
if (!defined('_PS_DEBUG_SQL_')) {
    define('_PS_DEBUG_SQL_', true);
}

if (!defined('_TB_DB_STRINGIFY_FETCHES_')) {
    define('_TB_DB_STRINGIFY_FETCHES_', true);
}

if (!defined('_TB_DB_ALLOW_MULTI_STATEMENTS_QUERIES_')) {
    define('_TB_DB_ALLOW_MULTI_STATEMENTS_QUERIES_', true);
}

if (!defined('_PS_DEBUG_PROFILING_')) {
    define('_PS_DEBUG_PROFILING_', false);
}
if (!defined('_PS_MODE_DEMO_')) {
    define('_PS_MODE_DEMO_', false);
}

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

if (!defined('_PS_VERSION_') && (getenv('_PS_VERSION_') || getenv('REDIRECT__PS_VERSION_'))) {
    define('_PS_VERSION_', getenv('_PS_VERSION_') ? getenv('_PS_VERSION_') : getenv('REDIRECT__PS_VERSION_'));
}

if (!defined('_PS_ROOT_DIR_') && (getenv('_PS_ROOT_DIR_') || getenv('REDIRECT__PS_ROOT_DIR_'))) {
    define('_PS_ROOT_DIR_', getenv('_PS_ROOT_DIR_') ? getenv('_PS_ROOT_DIR_') : getenv('REDIRECT__PS_ROOT_DIR_'));
}

/* Directories */
if (!defined('_PS_ROOT_DIR_')) {
    define('_PS_ROOT_DIR_', dirname(__DIR__));
}

if (!defined('_PS_CORE_DIR_')) {
    define('_PS_CORE_DIR_', _PS_ROOT_DIR_);
}

define('_PS_ALL_THEMES_DIR_',        _PS_ROOT_DIR_.'/themes/');
/* BO THEMES */
if (defined('_PS_ADMIN_DIR_')) {
    define('_PS_BO_ALL_THEMES_DIR_', _PS_ADMIN_DIR_.'/themes/');
}
if (!defined('_PS_CACHE_DIR_')) {
    define('_PS_CACHE_DIR_',             _PS_ROOT_DIR_.'/cache/');
}
define('_PS_CONFIG_DIR_',             _PS_CORE_DIR_.'/config/');
define('_PS_CUSTOM_CONFIG_FILE_',     _PS_CONFIG_DIR_.'settings_custom.inc.php');
define('_PS_CLASS_DIR_',             _PS_CORE_DIR_.'/classes/');
if (!defined('_PS_DOWNLOAD_DIR_')) {
    define('_PS_DOWNLOAD_DIR_',          _PS_ROOT_DIR_.'/download/');
}
define('_PS_MAIL_DIR_',              _PS_CORE_DIR_.'/mails/');
if (!defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_',        _PS_ROOT_DIR_.'/modules/');
}
if (!defined('_PS_OVERRIDE_DIR_')) {
    define('_PS_OVERRIDE_DIR_',          _PS_ROOT_DIR_.'/override/');
}
define('_PS_PDF_DIR_',               _PS_CORE_DIR_.'/pdf/');
define('_PS_TRANSLATIONS_DIR_',      _PS_ROOT_DIR_.'/translations/');
if (!defined('_PS_UPLOAD_DIR_')) {
    define('_PS_UPLOAD_DIR_',             _PS_ROOT_DIR_.'/upload/');
}
define('_PS_CONTROLLER_DIR_',        _PS_CORE_DIR_.'/controllers/');
define('_PS_ADMIN_CONTROLLER_DIR_',  _PS_CORE_DIR_.'/controllers/admin/');
define('_PS_FRONT_CONTROLLER_DIR_',  _PS_CORE_DIR_.'/controllers/front/');
define('_PS_AJAX_CONTROLLER_DIR_',  _PS_CORE_DIR_.'/controllers/ajax/');

define('_PS_TOOL_DIR_',              _PS_CORE_DIR_.'/tools/');
if (!defined('_PS_GEOIP_DIR_')) {
    define('_PS_GEOIP_DIR_',             _PS_TOOL_DIR_.'geoip/');
}
if (!defined('_PS_GEOIP_CITY_FILE_')) {
    define('_PS_GEOIP_CITY_FILE_',       'GeoLiteCity.dat');
}

define('_PS_PEAR_XML_PARSER_PATH_',  _PS_TOOL_DIR_.'pear_xml_parser/');
define('_PS_TAASC_PATH_',            _PS_TOOL_DIR_.'taasc/');
define('_PS_TCPDF_PATH_',            _PS_TOOL_DIR_.'tcpdf/');

if (!defined('_PS_IMG_DIR_')) {
    define('_PS_IMG_DIR_',               _PS_ROOT_DIR_.'/img/');
}
define('_PS_CORE_IMG_DIR_',      _PS_CORE_DIR_.'/img/');

/**
 * A list of all front office related image classes with mapping to their
 * storage directories. It's safe to assume one can find these image prefixed
 * with _PS_IMG_DIR_ for local storage and prefixed with _PS_IMG_ for URLs.
 */
// Should be
//define('_TB_IMAGE_MAP_', [
// Retrocompatibility for PHP 5.6:
const _TB_IMAGE_MAP_ = [
    'carriers'          => 's/',
    'categories'        => 'c/',
    'colors'            => 'co/',
    'employees'         => 'e/',
    'genders'           => 'genders/',
    'languages'         => 'l/',
    'manufacturers'     => 'm/',
    'order_states'      => 'os/',
    'products'          => 'p/',
    'scenes'            => 'scenes/',
    'scenes_thumbs'     => 'scenes/thumbs/',
    'stores'            => 'st/',
    'suppliers'         => 'su/',
];
define('_PS_CAT_IMG_DIR_',          _PS_IMG_DIR_._TB_IMAGE_MAP_['categories']);
define('_PS_COL_IMG_DIR_',          _PS_IMG_DIR_._TB_IMAGE_MAP_['colors']);
define('_PS_EMPLOYEE_IMG_DIR_',     _PS_IMG_DIR_._TB_IMAGE_MAP_['employees']);
define('_PS_GENDERS_DIR_',          _PS_IMG_DIR_._TB_IMAGE_MAP_['genders']);
define('_PS_LANG_IMG_DIR_',         _PS_IMG_DIR_._TB_IMAGE_MAP_['languages']);
define('_PS_MANU_IMG_DIR_',         _PS_IMG_DIR_._TB_IMAGE_MAP_['manufacturers']);
define('_PS_ORDER_STATE_IMG_DIR_',  _PS_IMG_DIR_._TB_IMAGE_MAP_['order_states']);
define('_PS_PROD_IMG_DIR_',         _PS_IMG_DIR_._TB_IMAGE_MAP_['products']);
define('_PS_SCENE_IMG_DIR_',        _PS_IMG_DIR_._TB_IMAGE_MAP_['scenes']);
define('_PS_SCENE_THUMB_IMG_DIR_',  _PS_IMG_DIR_._TB_IMAGE_MAP_['scenes_thumbs']);
define('_PS_SHIP_IMG_DIR_',         _PS_IMG_DIR_._TB_IMAGE_MAP_['carriers']);
define('_PS_STORE_IMG_DIR_',        _PS_IMG_DIR_._TB_IMAGE_MAP_['stores']);
define('_PS_SUPP_IMG_DIR_',         _PS_IMG_DIR_._TB_IMAGE_MAP_['suppliers']);
define('_PS_TMP_IMG_DIR_',          _PS_IMG_DIR_.'tmp/');


/* settings php */
define('_PS_TRANS_PATTERN_',            '(.*[^\\\\])');
define('_PS_MIN_TIME_GENERATE_PASSWD_', '360');
if ( ! defined('_TB_PRICE_DATABASE_PRECISION_')) {
    define('_TB_PRICE_DATABASE_PRECISION_', 6);
}

/**
 * This constant exists for backwards compatibility only
 *
 * magic_quotes_gpc were removed in php 5.4, and all references to this constant was removed from
 * thirty bees codebase in 1.1.1
 */
if (!defined('_PS_MAGIC_QUOTES_GPC_')) {
    define('_PS_MAGIC_QUOTES_GPC_', false);
}

define('_CAN_LOAD_FILES_', 1);

/* Order statuses
Order statuses have been moved into config.inc.php file for backward compatibility reasons */

/* Tax behavior */
define('PS_PRODUCT_TAX', 0);
define('PS_STATE_TAX', 1);
define('PS_BOTH_TAX', 2);

define('PS_TAX_EXC', 1);
define('PS_TAX_INC', 0);

define('PS_ORDER_PROCESS_STANDARD', 0);
define('PS_ORDER_PROCESS_OPC', 1);

define('PS_ROUND_UP', 0);
define('PS_ROUND_DOWN', 1);
define('PS_ROUND_HALF_UP', 2);
define('PS_ROUND_HALF_DOWN', 3);
define('PS_ROUND_HALF_EVEN', 4);
define('PS_ROUND_HALF_ODD', 5);

/* Backward compatibility */
define('PS_ROUND_HALF', PS_ROUND_HALF_UP);

/* Registration behavior */
define('PS_REGISTRATION_PROCESS_STANDARD', 0);
define('PS_REGISTRATION_PROCESS_AIO', 1);

/* Carrier::getCarriers() filter */
// these defines are DEPRECATED since 1.4.5 version
define('PS_CARRIERS_ONLY', 1);
define('CARRIERS_MODULE', 2);
define('CARRIERS_MODULE_NEED_RANGE', 3);
define('PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE', 4);
define('ALL_CARRIERS', 5);

/* SQL Replication management */
define('_PS_USE_SQL_SLAVE_', 0);

/* PS Technical configuration */
define('_PS_ADMIN_PROFILE_', 1);

/* Stock Movement */
define('_STOCK_MOVEMENT_ORDER_REASON_', 3);
define('_STOCK_MOVEMENT_MISSING_REASON_', 4);

/**
 * @deprecated 1.5.0.1
 * @see Configuration::get('PS_CUSTOMER_GROUP')
 */
define('_PS_DEFAULT_CUSTOMER_GROUP_', 3);

define('_PS_CACHEFS_DIRECTORY_', _PS_ROOT_DIR_.'/cache/cachefs/');

/* Geolocation */
define('_PS_GEOLOCATION_NO_CATALOG_', 0);
define('_PS_GEOLOCATION_NO_ORDER_', 1);

define('MIN_PASSWD_LENGTH', 8);

define('_PS_SMARTY_NO_COMPILE_', 0);
define('_PS_SMARTY_CHECK_COMPILE_', 1);
define('_PS_SMARTY_FORCE_COMPILE_', 2);

define('_PS_SMARTY_CONSOLE_CLOSE_', 0);
define('_PS_SMARTY_CONSOLE_OPEN_BY_URL_', 1);
define('_PS_SMARTY_CONSOLE_OPEN_', 2);

if (!defined('_PS_JQUERY_VERSION_')) {
    define('_PS_JQUERY_VERSION_', '1.11.0');
}

if (! defined('K_TCPDF_EXTERNAL_CONFIG')) {
    define('K_TCPDF_EXTERNAL_CONFIG', true);
}

if (! defined('K_PATH_IMAGES')) {
    define('K_PATH_IMAGES', _PS_ROOT_DIR_.'/img/');
}
