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
 * Class TranslateCore
 *
 * @since 1.0.0
 */
class TranslateCore
{
    /**
     * Get a translation for an admin controller
     *
     * @param string $string
     * @param string $class
     * @param bool   $addslashes
     * @param bool   $htmlentities
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAdminTranslation($string, $class = 'AdminTab', $addslashes = false, $htmlentities = true, $sprintf = null)
    {
        static $modulesTabs = null;

        global $_LANGADM;

        if ($modulesTabs === null) {
            try {
                $modulesTabs = Tab::getModuleTabList();
            } catch (PrestaShopException $e) {
                $modulesTabs = [];
            }
        }

        if ($_LANGADM == null) {
            $iso = Context::getContext()->language->iso_code;
            if (empty($iso)) {
                try {
                    $iso = Language::getIsoById((int) Configuration::get('PS_LANG_DEFAULT'));
                } catch (PrestaShopException $e) {
                    $iso = 'en';
                }
            }
            if (file_exists(_PS_TRANSLATIONS_DIR_.$iso.'/admin.php')) {
                include_once(_PS_TRANSLATIONS_DIR_.$iso.'/admin.php');
            }
        }

        if (isset($modulesTabs[strtolower($class)])) {
            $classNameController = $class.'controller';
            // if the class is extended by a module, use modules/[module_name]/xx.php lang file
            if (class_exists($classNameController) && Module::getModuleNameFromClass($classNameController)) {
                return Translate::getModuleTranslation(Module::$classInModule[$classNameController], $string, $classNameController, $sprintf, $addslashes);
            }
        }

        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = md5($string);
        if (isset($_LANGADM[$class.$key]) && $_LANGADM[$class.$key] !== '') {
            $str = $_LANGADM[$class.$key];
        } else {
            $str = Translate::getGenericAdminTranslation($string, $key, $_LANGADM);
        }

        if ($htmlentities) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }
        $str = str_replace('"', '&quot;', $str);

        if ($sprintf !== null) {
            $str = Translate::checkAndReplaceArgs($str, $sprintf);
        }

        return ($addslashes ? addslashes($str) : stripslashes($str));
    }

    /**
     * Get a translation for a module
     *
     * @param string|Module $module
     * @param string $string
     * @param string $source
     * @param array $sprintf
     * @param bool $js
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getModuleTranslation($module, $string, $source, $sprintf = null, $js = false)
    {
        global $_MODULES, $_MODULE, $_LANGADM;

        static $langCache = [];
        // $_MODULES is a cache of translations for all module.
        // $translations_merged is a cache of wether a specific module's translations have already been added to $_MODULES
        static $translationsMerged = [];

        $name = $module instanceof Module ? $module->name : $module;

        $language = Context::getContext()->language;

        if (!isset($translationsMerged[$name]) && isset(Context::getContext()->language)) {
            $filesByPriority = [
                // Translations in theme
                _PS_THEME_DIR_.'modules/'.$name.'/translations/'.$language->iso_code.'.php',
                _PS_THEME_DIR_.'modules/'.$name.'/'.$language->iso_code.'.php',
                // PrestaShop 1.5 translations
                _PS_MODULE_DIR_.$name.'/translations/'.$language->iso_code.'.php',
                // PrestaShop 1.4 translations
                _PS_MODULE_DIR_.$name.'/'.$language->iso_code.'.php',
            ];
            foreach ($filesByPriority as $file) {
                if (file_exists($file)) {
                    include_once($file);
                    $_MODULES = !empty($_MODULES) ? $_MODULES + $_MODULE : $_MODULE; //we use "+" instead of array_merge() because array merge erase existing values.
                    $translationsMerged[$name] = true;
                }
            }
        }
        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = md5($string);

        $cacheKey = $name.'|'.$string.'|'.$source.'|'.(int) $js;

        if (!isset($langCache[$cacheKey])) {
            if ($_MODULES == null) {
                return static::escapeModuleTranslation($string, $sprintf, $js);
            }

            $currentKey = strtolower('<{'.$name.'}'._THEME_NAME_.'>'.$source).'_'.$key;
            $defaultKey = strtolower('<{'.$name.'}thirtybees>'.$source).'_'.$key;
            $prestaShopKey = strtolower('<{'.$name.'}prestashop>'.$source).'_'.$key;

            if ('controller' == substr($source, -10, 10)) {
                $file = substr($source, 0, -10);
                $currentKeyFile = strtolower('<{'.$name.'}'._THEME_NAME_.'>'.$file).'_'.$key;
                $defaultKeyFile = strtolower('<{'.$name.'}thirtybees>'.$file).'_'.$key;
                $prestaShopKeyFile = strtolower('<{'.$name.'}prestashop>'.$file).'_'.$key;
            }

            if (isset($currentKeyFile) && !empty($_MODULES[$currentKeyFile])) {
                $ret = $_MODULES[$currentKeyFile];
            } elseif (isset($defaultKeyFile) && !empty($_MODULES[$defaultKeyFile])) {
                $ret = $_MODULES[$defaultKeyFile];
            } elseif (isset($prestaShopKeyFile) && !empty($_MODULES[$prestaShopKeyFile])) {
                $ret = $_MODULES[$prestaShopKeyFile];
            } elseif (!empty($_MODULES[$currentKey])) {
                $ret = $_MODULES[$currentKey];
            } elseif (!empty($_MODULES[$defaultKey])) {
                $ret = $_MODULES[$defaultKey];
            } elseif (!empty($_MODULES[$prestaShopKey])) {
                $ret = $_MODULES[$prestaShopKey];
            } elseif (!empty($_LANGADM)) {
                $ret = Translate::getGenericAdminTranslation($string, $key, $_LANGADM);
            } else {
                $ret = $string;
            }

            $ret = static::escapeModuleTranslation($ret, $sprintf, $js);

            if ($sprintf === null) {
                $langCache[$cacheKey] = $ret;
            } else {
                return $ret;
            }
        }

        return $langCache[$cacheKey];
    }

    /**
     * Helper method to escape return value for getModuleTranslation
     *
     * @param string $input
     * @param array $sprintf
     * @param bool $js
     *
     * @return string
     */
    protected static function escapeModuleTranslation($input, $sprintf, $js)
    {
        if (! $input) {
            return '';
        }

        $ret = stripslashes($input);

        if ($sprintf !== null) {
            $ret = Translate::checkAndReplaceArgs($ret, $sprintf);
        }

        return $js ? addslashes($ret) : htmlspecialchars($ret, ENT_COMPAT, 'UTF-8');
    }

    /**
     * Check if string use a specif syntax for sprintf and replace arguments if use it
     *
     * @param string $string
     * @param array  $args
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function checkAndReplaceArgs($string, $args)
    {
        if (preg_match_all('#(?:%%|%(?:[0-9]+\$)?[+-]?(?:[ 0]|\'.)?-?[0-9]*(?:\.[0-9]+)?[bcdeufFosxX])#', $string, $matches) && !is_null($args)) {
            if (!is_array($args)) {
                $args = [$args];
            }

            return vsprintf($string, $args);
        }

        return $string;
    }

    /**
     * Return the translation for a string if it exists for the base AdminController or for helpers
     *
     * @param string      $string     string to translate
     * @param string|null $key        md5 key if already calculated (optional)
     * @param array       $langArray  Global array of admin translations
     *
     * @return string translation
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGenericAdminTranslation($string, $key = null, &$langArray)
    {
        $string = preg_replace("/\\\*'/", "\'", $string);
        if (is_null($key)) {
            $key = md5($string);
        }

        if (isset($langArray['AdminController'.$key])) {
            $str = $langArray['AdminController'.$key];
        } elseif (isset($langArray['Helper'.$key])) {
            $str = $langArray['Helper'.$key];
        } elseif (isset($langArray['AdminTab'.$key])) {
            $str = $langArray['AdminTab'.$key];
        } else {
            // note in 1.5, some translations has moved from AdminXX to helper/*.tpl
            $str = $string;
        }

        return $str !== '' ? $str : $string;
    }

    /**
     * Get a translation for a PDF
     *
     * @param string $string
     *
     * @param null   $sprintf
     *
     * @return string
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getPdfTranslation($string, $sprintf = null)
    {
        global $_LANGPDF;

        $iso = Context::getContext()->language->iso_code;

        if (!Validate::isLangIsoCode($iso)) {
            Tools::displayError(sprintf('Invalid iso lang (%s)', Tools::safeOutput($iso)));
        }

        $overrideI18NFile = _PS_THEME_DIR_.'pdf/lang/'.$iso.'.php';
        $i18NFile = _PS_TRANSLATIONS_DIR_.$iso.'/pdf.php';
        if (file_exists($overrideI18NFile)) {
            $i18NFile = $overrideI18NFile;
        }

        if (!include($i18NFile)) {
            Tools::displayError(sprintf('Cannot include PDF translation language file : %s', $i18NFile));
        }

        if (!isset($_LANGPDF) || !is_array($_LANGPDF)) {
            return str_replace('"', '&quot;', $string);
        }

        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = 'PDF' . md5($string);

        $str = array_key_exists($key, $_LANGPDF) && $_LANGPDF[$key] !== '' ? $_LANGPDF[$key] : $string;

        if ($sprintf !== null) {
            $str = Translate::checkAndReplaceArgs($str, $sprintf);
        }

        return $str;
    }

    /**
     * Compatibility method that just calls postProcessTranslation.
     *
     * @deprecated 1.0.0 renamed this to postProcessTranslation, since it is not only used in relation to smarty.
     */
    public static function smartyPostProcessTranslation($string, $params)
    {
        return Translate::postProcessTranslation($string, $params);
    }

    /**
     * Perform operations on translations after everything is escaped and before displaying it
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param string $string
     * @param array  $params
     *
     * @return mixed
     */
    public static function postProcessTranslation($string, $params)
    {
        // If tags were explicitely provided, we want to use them *after* the translation string is escaped.
        if (!empty($params['tags'])) {
            foreach ($params['tags'] as $index => $tag) {
                // Make positions start at 1 so that it behaves similar to the %1$d etc. sprintf positional params
                $position = $index + 1;
                // extract tag name
                $match = [];
                if (preg_match('/^\s*<\s*(\w+)/', $tag, $match)) {
                    $opener = $tag;
                    $closer = '</'.$match[1].'>';

                    $string = str_replace('['.$position.']', $opener, $string);
                    $string = str_replace('[/'.$position.']', $closer, $string);
                    $string = str_replace('['.$position.'/]', $opener.$closer, $string);
                }
            }
        }

        return $string;
    }

    /**
     * Helper function to make calls to postProcessTranslation more readable.
     *
     * @param string $string
     * @param array  $tags
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function ppTags($string, $tags)
    {
        return Translate::postProcessTranslation($string, ['tags' => $tags]);
    }
}
