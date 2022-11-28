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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

define('_PS_SMARTY_DIR_', _PS_TOOL_DIR_.'smarty/');

// smarty is declared as global for backwards compatibility reasons
global $smarty;

$smarty = new SmartyCustom();
$smarty->setCompileDir(_PS_CACHE_DIR_.'smarty/compile');
$smarty->setCacheDir(_PS_CACHE_DIR_.'smarty/cache');
$smarty->use_sub_dirs = true; // Unused in community-theme-default.
$smarty->setConfigDir(_PS_SMARTY_DIR_.'configs');
$smarty->caching = false;
if (Configuration::get('PS_SMARTY_CACHING_TYPE') == 'mysql') {
    include(_PS_CLASS_DIR_.'/SmartyCacheResourceMysql.php');
    $smarty->caching_type = 'mysql';
}
$smarty->force_compile = Configuration::get('PS_SMARTY_FORCE_COMPILE') == _PS_SMARTY_FORCE_COMPILE_;
$smarty->compile_check = Configuration::get('PS_SMARTY_FORCE_COMPILE') >= _PS_SMARTY_CHECK_COMPILE_;
$smarty->debug_tpl = _PS_ALL_THEMES_DIR_.'debug.tpl';

// custom blocks
smartyRegisterFunction($smarty, 'block', 'addJsDefL', ['Media', 'addJsDefL']);

// custom functions
smartyRegisterFunction($smarty, 'function', 'addJsDef', ['Media', 'addJsDef']);
smartyRegisterFunction($smarty, 'function', 'convertPrice', ['Product', 'convertPrice']);
smartyRegisterFunction($smarty, 'function', 'convertPriceWithCurrency', ['Product', 'convertPriceWithCurrency']);
smartyRegisterFunction($smarty, 'function', 'd', 'smartyDieObject'); // Debug only
smartyRegisterFunction($smarty, 'function', 'dateFormat', ['Tools', 'dateFormat']);
smartyRegisterFunction($smarty, 'function', 'displayAddressDetail', ['AddressFormat', 'generateAddressSmarty']);
smartyRegisterFunction($smarty, 'function', 'displayPrice', ['Tools', 'displayPriceSmarty']);
smartyRegisterFunction($smarty, 'function', 'displayPriceValue', 'displayPriceValue');
smartyRegisterFunction($smarty, 'function', 'displayWtPrice', ['Product', 'displayWtPrice']);
smartyRegisterFunction($smarty, 'function', 'displayWtPriceWithCurrency', ['Product', 'displayWtPriceWithCurrency']);
smartyRegisterFunction($smarty, 'function', 'getAdminToken', ['Tools', 'getAdminTokenLiteSmarty']);
smartyRegisterFunction($smarty, 'function', 'getHeightSize', ['Image', 'getHeight']);
smartyRegisterFunction($smarty, 'function', 'getWidthSize', ['Image', 'getWidth']);
smartyRegisterFunction($smarty, 'function', 'hook', 'smartyHook');
smartyRegisterFunction($smarty, 'function', 'implode', ['Tools', 'smartyImplode']);
smartyRegisterFunction($smarty, 'function', 'm', 'smartyMaxWords'); // unused
smartyRegisterFunction($smarty, 'function', 'p', 'smartyShowObject'); // Debug only
smartyRegisterFunction($smarty, 'function', 't', 'smartyTruncate'); // unused
smartyRegisterFunction($smarty, 'function', 'toolsConvertPrice', 'toolsConvertPrice');

// custom modifiers
smartyRegisterFunction($smarty, 'modifier', 'addcslashes', 'addcslashes');
smartyRegisterFunction($smarty, 'modifier', 'addslashes', 'addslashes');
smartyRegisterFunction($smarty, 'modifier', 'boolval', ['Tools', 'boolval']);
smartyRegisterFunction($smarty, 'modifier', 'cleanHtml', 'smartyCleanHtml');
smartyRegisterFunction($smarty, 'modifier', 'constant', 'constant');
smartyRegisterFunction($smarty, 'modifier', 'convertAndFormatPrice', ['Product', 'convertAndFormatPrice']); // used twice
smartyRegisterFunction($smarty, 'modifier', 'date_format', 'smarty_modifier_date_format');
smartyRegisterFunction($smarty, 'modifier', 'end', 'smartyEndModifier');
smartyRegisterFunction($smarty, 'modifier', 'explode', 'explode');
smartyRegisterFunction($smarty, 'modifier', 'floatval', 'floatval');
smartyRegisterFunction($smarty, 'modifier', 'html_entity_decode', 'html_entity_decode');
smartyRegisterFunction($smarty, 'modifier', 'htmlentities', 'htmlentities');
smartyRegisterFunction($smarty, 'modifier', 'htmlspecialchars', 'htmlspecialchars');
smartyRegisterFunction($smarty, 'modifier', 'idnToUtf8', ['Tools', 'convertEmailFromIdn']);
smartyRegisterFunction($smarty, 'modifier', 'implode', 'implode');
smartyRegisterFunction($smarty, 'modifier', 'intval', 'intval');
smartyRegisterFunction($smarty, 'modifier', 'json_decode', ['Tools', 'jsonDecode']);
smartyRegisterFunction($smarty, 'modifier', 'json_encode', ['Tools', 'jsonEncode']);
smartyRegisterFunction($smarty, 'modifier', 'lcfirst', 'lcfirst');
smartyRegisterFunction($smarty, 'modifier', 'md5', 'md5');
smartyRegisterFunction($smarty, 'modifier', 'rand', 'rand');
smartyRegisterFunction($smarty, 'modifier', 'secureReferrer', ['Tools', 'secureReferrer']);
smartyRegisterFunction($smarty, 'modifier', 'sha1', 'sha1');
smartyRegisterFunction($smarty, 'modifier', 'sprintf', 'sprintf');
smartyRegisterFunction($smarty, 'modifier', 'str_replace', 'str_replace');
smartyRegisterFunction($smarty, 'modifier', 'stripslashes', 'stripslashes');
smartyRegisterFunction($smarty, 'modifier', 'strtolower', 'strtolower');
smartyRegisterFunction($smarty, 'modifier', 'strtoupper', 'strtoupper');
smartyRegisterFunction($smarty, 'modifier', 'strval', 'strval');
smartyRegisterFunction($smarty, 'modifier', 'substr', 'substr');
smartyRegisterFunction($smarty, 'modifier', 'trim', 'trim');
smartyRegisterFunction($smarty, 'modifier', 'truncate', 'smarty_modifier_truncate');
smartyRegisterFunction($smarty, 'modifier', 'ucfirst', 'ucfirst');
smartyRegisterFunction($smarty, 'modifier', 'urlencode', 'urlencode');
smartyRegisterFunction($smarty, 'modifier', 'utf8ToIdn', ['Tools', 'convertEmailToIdn']);
smartyRegisterFunction($smarty, 'modifier', 'var_export', 'var_export');

if (defined('_PS_ADMIN_DIR_')) {
    smartyRegisterFunction($smarty, 'function', 'l', ['Translate', 'smartyAdminTranslate'], false);
} else {
    smartyRegisterFunction($smarty, 'function', 'l', ['Translate', 'smartyFrontTranslate'], false);
    $smarty->setTemplateDir(_PS_THEME_DIR_.'tpl');
    if (Configuration::get('PS_JS_HTML_THEME_COMPRESSION')) {
        $smarty->registerFilter('output', 'smartyPackJSinHTML');
    }
}

/**
 * @param array $params
 * @param Smarty $smarty
 * @return mixed
 */
function smartyDieObject($params, $smarty)
{
    return Tools::d($params['var']);
}

/**
 * @param array $params
 * @param Smarty $smarty
 * @return mixed|void
 */
function smartyShowObject($params, $smarty)
{
    return Tools::p($params['var']);
}

/**
 * @param array $params
 * @param Smarty $smarty
 * @return string
 */
function smartyMaxWords($params, $smarty)
{
    Tools::displayAsDeprecated();
    $params['s'] = str_replace('...', ' ...', html_entity_decode($params['s'], ENT_QUOTES, 'UTF-8'));
    $words = explode(' ', $params['s']);

    foreach ($words as &$word) {
        if (mb_strlen($word) > $params['n']) {
            $word = mb_substr(trim(chunk_split($word, $params['n']-1, '- ')), 0, -1);
        }
    }

    return implode(' ',  Tools::htmlentitiesUTF8($words));
}

/**
 * @param array $array
 * @return false|mixed
 */
function smartyEndModifier($array)
{
    if (is_array($array)) {
        return end($array);
    }
    return false;
}

/**
 * @param array $params
 * @param Smarty $smarty
 * @return string
 */
function smartyTruncate($params, $smarty)
{
    Tools::displayAsDeprecated();

    $text = isset($params['strip'])
        ? strip_tags($params['text'])
        : $params['text'];
    $length = $params['length'];
    $sep = isset($params['sep']) ? $params['sep'] : '...';

    if (mb_strlen($text) > $length + mb_strlen($sep)) {
        $text = mb_substr($text, 0, $length).$sep;
    }

    return isset($params['encode'])
        ? Tools::htmlentitiesUTF8($text, ENT_NOQUOTES)
        : $text;
}

/**
 * @param string $string
 * @param int $length
 * @param string $etc
 * @param false $break_words
 * @param false $middle
 * @param string $charset
 * @return string
 */
function smarty_modifier_truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false, $charset = 'UTF-8')
{
    if (!$length) {
        return '';
    }

    $string = trim($string);

    if (mb_strlen($string) > $length) {
        $length -= min($length, mb_strlen($etc));
        if (!$break_words && !$middle) {
            $string = preg_replace('/\s+?(\S+)?$/u', '', mb_substr($string, 0, $length+1, $charset));
        }
        return !$middle
            ? mb_substr($string, 0, $length, $charset).$etc
            : mb_substr($string, 0, $length/2, $charset).$etc.mb_substr($string, -$length/2, $length, $charset);
    } else {
        return $string;
    }
}

/**
 * @param string $string
 * @return string
 */
function smarty_modifier_htmlentitiesUTF8($string)
{
    return Tools::htmlentitiesUTF8($string);
}

/**
 * @param string $tplOutput
 * @param Smarty $smarty
 * @return string
 */
function smartyMinifyHTML($tplOutput, $smarty)
{
    $context = Context::getContext();
    if (isset($context->controller) && in_array($context->controller->php_self, ['pdf-invoice', 'pdf-order-return', 'pdf-order-slip'])) {
        return $tplOutput;
    }
    return Media::minifyHTML($tplOutput);
}

/**
 * @param string $tplOutput
 * @param Smarty $smarty
 * @return string
 */
function smartyPackJSinHTML($tplOutput, $smarty)
{
    $context = Context::getContext();
    if (isset($context->controller) && in_array($context->controller->php_self, ['pdf-invoice', 'pdf-order-return', 'pdf-order-slip'])) {
        return $tplOutput;
    }
    return Media::packJSinHTML($tplOutput);
}

/**
 * @param Smarty $smarty
 * @param string $type
 * @param string $function
 * @param mixed $params
 * @param bool $lazy
 * @throws PrestaShopException
 * @throws SmartyException
 */
function smartyRegisterFunction($smarty, $type, $function, $params, $lazy = true)
{
    if (!in_array($type, ['function', 'modifier', 'block'])) {
        return;
    }

    // lazy is better if the function is not called on every page
    if ($lazy && is_array($params)) {
        $name = $params[1];
        $lazy_register = SmartyLazyRegister::getInstance();
        $lazy_register->register($name, $type, $params);

        // SmartyLazyRegister allows to only load external class when they are needed
        $smarty->registerPlugin($type, $function, [$lazy_register, $name]);
    } else {
        $smarty->registerPlugin($type, $function, $params);
    }
}

/**
 * @param mixed $params
 * @param Smarty $smarty
 * @return string|null
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function smartyHook($params, $smarty)
{
    if (!empty($params['h'])) {
        $id_module = null;
        $hook_params = $params;
        $hook_params['smarty'] = $smarty;
        if (!empty($params['mod'])) {
            $module = Module::getInstanceByName($params['mod']);
            if ($module && $module->id) {
                $id_module = $module->id;
            } else {
                return '';
            }
            unset($hook_params['mod']);
        }
        unset($hook_params['h']);
        return Hook::exec($params['h'], $hook_params, $id_module);
    }
    return null;
}

/**
 * @param mixed $data
 * @return mixed|null
 */
function smartyCleanHtml($data)
{
    // Prevent xss injection.
    if (Validate::isCleanHtml($data)) {
        return $data;
    }
    return null;
}

/**
 * Helper method
 * @param array $params
 * @param Smarty $smarty
 * @return float
 */
function toolsConvertPrice($params, $smarty)
{
    return Tools::convertPrice($params['price'], Context::getContext()->currency);
}

/**
 * Convert a price for display in an input field in back office. This means,
 * allow full precision of _TB_PRICE_DATABASE_PRECISION_, but reduce the number
 * of trailing zeros beyond PS_PRICE_DISPLAY_PRECISION. This should give the
 * nicest display possible.
 *
 * Formatting should match JavaScript function displayPriceValue (in admin.js).
 * Which means: don't forget to transport any changes made here to there.
 *
 * @param float|string $params['price'] Raw price in context currency.
 * @param float|string $smarty          Unused.
 *
 * @return string Price prettified, without currency sign.
 *
 * @throws PrestaShopException
 * @since 1.1.0
 */
function displayPriceValue($params, $smarty)
{
    $displayDecimals = 0;
    if (Context::getContext()->currency->decimals) {
        $displayDecimals = Configuration::get('PS_PRICE_DISPLAY_PRECISION');
    }

    // String -> float -> string gets rid of trailing zeros.
    $price = (float) $params['price'];
    // No need for the more expensive Tools::ps_round() here.
    if ((string) $price === (string) round($price, $displayDecimals)) {
        // Price more rounded than display precision.
        $formatted = number_format($price, $displayDecimals, '.', '');
    } else {
        // Show full precision.
        $formatted = (string) $price;
    }

    return $formatted;
}

/**
 * Method to perform smarty translate. This method should not be called directly,
 * it exists for backwards compatibility reasons only
 *
 * @param array $params
 * @param Smarty $smarty
 */
function smartyTranslate($params, $smarty)
{
    Tools::displayAsDeprecated();
    if (defined('_PS_ADMIN_DIR_')) {
        return Translate::smartyAdminTranslate($params, $smarty);
    } else {
        return Translate::smartyFrontTranslate($params, $smarty);
    }
}

/**
 * Custom modifier for 'date_format'
 *
 * @param string $string input date string
 * @param string $format strftime format for output
 * @param string $defaultDate default date if $string is empty
 * @param string $formatter either 'strftime' or 'auto'

 * @return string|void
 * @throws PrestaShopException
 */
function smarty_modifier_date_format($string, $format = null, $defaultDate = '', $formatter = 'auto')
{
    if ($format === null) {
        $format = Smarty::$_DATE_FORMAT;
    }
    static $isLoaded = false;
    if (!$isLoaded) {
        if (!is_callable('smarty_make_timestamp')) {
            include_once SMARTY_PLUGINS_DIR . 'shared.make_timestamp.php';
        }
        $isLoaded = true;
    }
    if (!empty($string) && $string !== '0000-00-00' && $string !== '0000-00-00 00:00:00') {
        $timestamp = smarty_make_timestamp($string);
    } elseif (!empty($defaultDate)) {
        $timestamp = smarty_make_timestamp($defaultDate);
    } else {
        return;
    }
    if ($formatter === 'strftime' || ($formatter === 'auto' && strpos($format, '%') !== false)) {
        if (Smarty::$_IS_WINDOWS) {
            $_win_from = [
                '%D',
                '%h',
                '%n',
                '%r',
                '%R',
                '%t',
                '%T'
            ];
            $_win_to = [
                '%m/%d/%y',
                '%b',
                "\n",
                '%I:%M:%S %p',
                '%H:%M',
                "\t",
                '%H:%M:%S'
            ];
            if (strpos($format, '%e') !== false) {
                $_win_from[] = '%e';
                $_win_to[] = sprintf('%\' 2d', date('j', $timestamp));
            }
            if (strpos($format, '%l') !== false) {
                $_win_from[] = '%l';
                $_win_to[] = sprintf('%\' 2d', date('h', $timestamp));
            }
            $format = str_replace($_win_from, $_win_to, $format);
        }
        return Tools::strftime($format, $timestamp);
    } else {
        return date($format, $timestamp);
    }
}

/**
 * Used to delay loading of external classes with smarty->register_plugin
 */
class SmartyLazyRegister
{
    protected $registry = [];
    protected static $instance;

    /**
     * Register a function or method to be dynamically called later
     *
     * @param string|array $name function name or array(object name, method name)
     * @param string $type
     * @param callable|null $callable
     * @throws PrestaShopException
     */
    public function register($name, $type = 'function', $callable = null)
    {
        if (is_null($callable)) {
            if (is_array($name) && count($name) === 2) {
                $callable = $name;
                $name = $name[1];
            } else {
                throw new PrestaShopException('Invalid usage of SmartyLazyRegister::register');
            }
        }

        $this->registry[$name] = [
            'callable' => $callable,
            'type' => $type
        ];
    }

    /**
     * Dynamically call static function or method
     *
     * @param string $name function name
     * @param mixed $arguments function argument
     * @return mixed function return
     */
    public function __call($name, $arguments)
    {
        $item = $this->registry[$name];
        $callable = $item['callable'];
        $type = $item['type'];
        if ($type === 'block') {
            // signature of smarty block plugin is: function($params, $content, $template, &$repeat)
            // we need to pass 4th parameter as reference
            return call_user_func_array($callable, [$arguments[0], $arguments[1], $arguments[2], &$arguments[3]]);
        } else {
            // signature of smarty function plugin is: function($params, $smarty)
            // signature of smarty function plugin is: function modifier($value, [$param1, $param2, $param3])
            // there are no references, we can simply forward the call with input arguments
            return call_user_func_array($callable, $arguments);
        }
    }

    /**
     * @return SmartyLazyRegister
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new SmartyLazyRegister();
        }
        return self::$instance;
    }
}

return $smarty;