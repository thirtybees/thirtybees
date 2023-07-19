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
 * Class ConfigurationTestCore
 */
class ConfigurationTestCore
{
    const NO_ARGUMENTS = false;
    const TEST_UPLOAD = 'Upload';
    const TEST_IMG_DIR = 'ImgDir';
    const TEST_LOG_DIR = 'LogDir';
    const TEST_CACHE_DIR = 'CacheDir';
    const TEST_MODULES_DIR = 'ModuleDir';
    const TEST_THEME_LANG_DIR = 'ThemeLangDir';
    const TEST_THEME_PDF_LANG_DIR = 'ThemePdfLangDir';
    const TEST_THEME_CACHE_DIR = 'ThemeCacheDir';
    const TEST_TRANSLATIONS_DIR = 'TranslationsDir';
    const TEST_CUSTOMIZABLE_PRODUCTS_DIR = 'CustomizableProductsDir';
    const TEST_VIRTUAL_PRODUCTS_DIR = 'VirtualProductsDir';
    const TEST_SYSTEM = 'System';
    const TEST_FOPEN = 'Fopen';
    const TEST_CONFIG_DIR = 'ConfigDir';
    const TEST_FILES = 'Files';
    const TEST_MAILS_DIR = 'MailsDir';
    const TEST_MAX_EXECUTION_TIME = 'MaxExecutionTime';
    const TEST_BCMATH = 'Bcmath';
    const TEST_GD = 'Gd';
    const TEST_JSON = 'Json';
    const TEST_MBSTRING = 'Mbstring';
    const TEST_OPENSSL = 'OpenSSL';
    const TEST_PDO_MYSQL = 'PdoMysql';
    const TEST_XML = 'Xml';
    const TEST_ZIP = 'Zip';
    const TEST_GZ = 'Gz';
    const TEST_INTL = 'Intl';
    const TEST_SOAP = 'Soap';

    /**
     * @var array $testFiles
     */
    public static $testFiles = [
        '/cache/smarty/compile',
        '/classes/log',
        '/classes/cache',
        '/config',
        '/controllers/admin/AdminLoginController.php',
        '/vendor/autoload.php',
        '/css',
        '/download',
        '/img/404.gif',
        '/js/tools.js',
        '/js/jquery/plugins/fancybox/jquery.fancybox.js',
        '/localization/fr.xml',
        '/mails',
        '/modules',
        '/pdf/order-return.tpl',
        '/themes/community-theme-default/css/global.css',
        '/translations/export',
        '/webservice/dispatcher.php',
    ];

    /**
     * getDefaultTests return an array of tests to executes.
     * key are method name, value are parameters (false for no parameter)
     * all path are _PS_ROOT_DIR_ related
     *
     * @return array
     */
    public static function getDefaultTests()
    {
        return[
            static::TEST_UPLOAD => static::NO_ARGUMENTS,
            static::TEST_CACHE_DIR => 'cache',
            static::TEST_LOG_DIR => 'log',
            static::TEST_IMG_DIR => 'img',
            static::TEST_MODULES_DIR => 'modules',
            static::TEST_THEME_LANG_DIR => 'themes/' . _THEME_NAME_ . '/lang/',
            static::TEST_THEME_PDF_LANG_DIR => 'themes/' . _THEME_NAME_ . '/pdf/lang/',
            static::TEST_THEME_CACHE_DIR => 'themes/' . _THEME_NAME_ . '/cache/',
            static::TEST_TRANSLATIONS_DIR => 'translations',
            static::TEST_CUSTOMIZABLE_PRODUCTS_DIR => 'upload',
            static::TEST_VIRTUAL_PRODUCTS_DIR => 'download',
            static::TEST_SYSTEM => [
                'fopen', 'fclose', 'fread', 'fwrite',
                'rename', 'file_exists', 'unlink', 'rmdir', 'mkdir',
                'getcwd', 'chdir', 'chmod',
            ],
            static::TEST_FOPEN => static::NO_ARGUMENTS,
            static::TEST_CONFIG_DIR => 'config',
            static::TEST_FILES => static::NO_ARGUMENTS,
            static::TEST_MAILS_DIR => 'mails',
            static::TEST_MAX_EXECUTION_TIME => static::NO_ARGUMENTS,
            static::TEST_BCMATH => static::NO_ARGUMENTS,
            static::TEST_GD => static::NO_ARGUMENTS,
            static::TEST_JSON => static::NO_ARGUMENTS,
            static::TEST_MBSTRING => static::NO_ARGUMENTS,
            static::TEST_OPENSSL => static::NO_ARGUMENTS,
            static::TEST_PDO_MYSQL => static::NO_ARGUMENTS,
            static::TEST_XML => static::NO_ARGUMENTS,
            static::TEST_ZIP => static::NO_ARGUMENTS,
        ];
    }

    /**
     * getDefaultTestsOp return an array of tests to executes.
     * key are method name, value are parameters (static::NO_ARGUMENTS for no parameter)
     *
     * @return array
     */
    public static function getDefaultTestsOp()
    {
        return [
            static::TEST_GZ => static::NO_ARGUMENTS,
            static::TEST_INTL => static::NO_ARGUMENTS,
            static::TEST_SOAP => static::NO_ARGUMENTS,
        ];
    }

    /**
     * run all test defined in $tests
     *
     * @param array $tests
     *
     * @return array results of tests
     */
    public static function check($tests)
    {
        $res = [];
        foreach ($tests as $key => $test) {
            $res[$key] = static::run($key, $test);
        }

        return $res;
    }

    /**
     * @param string $ptr
     * @param int $arg
     *
     * @return string 'ok' on success, 'fail' or error message on failure.
     */
    public static function run($ptr, $arg = 0)
    {
        $report = '';
        if ($arg) {
            $result = call_user_func_array([static::class, 'test'.$ptr], [$arg, &$report]);
        } else {
            $result = call_user_func_array([static::class, 'test'.$ptr], [&$report]);
        }

        if ( ! $result) {
            if (strlen($report)) {
                return $report;
            } else {
                return 'fail';
            }
        }

        return 'ok';
    }

    /**
     * @return bool
     */
    public static function testPdoMysql()
    {
        return extension_loaded('pdo_mysql');
    }

    /**
     * @return bool
     */
    public static function testBcmath()
    {
        return extension_loaded('bcmath') && function_exists('bcdiv');
    }

    /**
     * @return bool
     */
    public static function testXml()
    {
        return class_exists('SimpleXMLElement');
    }

    /**
     * @return bool
     */
    public static function testJson()
    {
        return function_exists('json_encode') && function_exists('json_decode');
    }

    /**
     * @return bool
     */
    public static function testZip()
    {
        return class_exists('ZipArchive');
    }

    /**
     * @return string
     */
    public static function testUpload()
    {
        return ini_get('file_uploads');
    }

    /**
     * @return string
     */
    public static function testFopen()
    {
        return ini_get('allow_url_fopen');
    }

    /**
     * @param array $funcs
     *
     * @return bool
     */
    public static function testSystem($funcs, &$report = null)
    {
        foreach ($funcs as $func) {
            if (!function_exists($func)) {
                $report = 'Function '.$func.'() does not exist.';
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public static function testIntl()
    {
        return extension_loaded('intl');
    }

    /**
     * @return bool
     */
    public static function testSoap()
    {
        return extension_loaded('soap');
    }

    /**
     * @return bool
     */
    public static function testGd()
    {
        return function_exists('imagecreatetruecolor');
    }

    /**
     * @return bool
     */
    public static function testMaxExecutionTime()
    {
        return ini_get('max_execution_time') <= 0
               || ini_get('max_execution_time') >= 30;
    }

    /**
     * @return bool
     */
    public static function testGz()
    {
        if (function_exists('gzencode')) {
            return @gzencode('dd') !== false;
        }

        return false;
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function testConfigDir($dir, &$report = null)
    {
        return static::testDir($dir, false, $report);
    }

    /**
     * Test if directory is writable
     *
     * @param string $dir Directory path, absolute or relative
     * @param bool $recursive
     * @param string|null $fullReport
     * @param bool $absolute Is absolute path to directory
     *
     * @return bool
     */
    public static function testDir($dir, $recursive = false, &$fullReport = null, $absolute = false)
    {
        if ($absolute) {
            $absoluteDir = $dir;
        } else {
            $absoluteDir = rtrim(_PS_ROOT_DIR_, '\\/').DIRECTORY_SEPARATOR.trim($dir, '\\/');
        }

        if (!file_exists($absoluteDir)) {
            $fullReport = sprintf('Directory %s does not exist.', $absoluteDir);

            return false;
        }

        if (!is_writable($absoluteDir)) {
            $fullReport = sprintf('Directory %s is not writable.', $absoluteDir);

            return false;
        }

        if ($recursive) {
            foreach (scandir($absoluteDir, SCANDIR_SORT_NONE) as $item) {
                $path = $absoluteDir.DIRECTORY_SEPARATOR.$item;

                if (in_array($item, ['.', '..', '.git'])
                    || is_link($path)) {
                    continue;
                }

                if (is_dir($path)) {
                    if (!static::testDir($path, $recursive, $fullReport, true)) {
                        return false;
                    }
                }

                if (!is_writable($path)) {
                    $fullReport = sprintf('File %s is not writable.', $path);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param string $fileRelative
     *
     * @return bool
     */
    public static function testFile($fileRelative, &$report = null)
    {
        $file = _PS_ROOT_DIR_.DIRECTORY_SEPARATOR.$fileRelative;

        if (!file_exists($file)) {
            $report = 'File or directory '.$file.' does not exist.';
            return false;
        }

        if (!is_writable($file)) {
            $report = 'File or directory '.$file.' is not writable.';
            return false;
        }

        return true;
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function testLogDir($dir, &$report = null)
    {
        return static::testDir($dir, false, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function testImgDir($dir, &$report = null)
    {
        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function testModuleDir($dir, &$report = null)
    {
        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function testCacheDir($dir, &$report = null)
    {
        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function testMailsDir($dir, &$report = null)
    {
        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function testTranslationsDir($dir, &$report = null)
    {
        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function testThemeLangDir($dir, &$report = null)
    {
        $absoluteDir = rtrim(_PS_ROOT_DIR_, '\\/').DIRECTORY_SEPARATOR.trim($dir, '\\/');
        if (!file_exists($absoluteDir)) {
            return false;
        }

        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function testThemePdfLangDir($dir, &$report = null)
    {
        $absoluteDir = rtrim(_PS_ROOT_DIR_, '\\/').DIRECTORY_SEPARATOR.trim($dir, '\\/');
        if (!file_exists($absoluteDir)) {
            return true;
        }

        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function testThemeCacheDir($dir, &$report = null)
    {
        $absoluteDir = rtrim(_PS_ROOT_DIR_, '\\/').DIRECTORY_SEPARATOR.trim($dir, '\\/');
        if (!file_exists($absoluteDir)) {
            return true;
        }

        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function testCustomizableProductsDir($dir, &$report = null)
    {
        return static::testDir($dir, false, $report);
    }

    /**
     * @param string $dir
     * @param string|null $report
     * @return bool
     */
    public static function testVirtualProductsDir($dir, &$report = null)
    {
        return static::testDir($dir, false, $report);
    }

    /**
     * @return bool
     */
    public static function testMbstring()
    {
        return extension_loaded('mbstring');
    }

    /**
     * @return bool
     */
    public static function testOpenSSL()
    {
        return extension_loaded('openssl')
               && function_exists('openssl_encrypt');
    }

    /**
     * Test the set of files defined above. Not used by the installer, but by
     * AdminInformationController.
     *
     * @param bool $full
     *
     * @return array|bool
     */
    public static function testFiles($full = false)
    {
        $return = [];
        foreach (static::$testFiles as $file) {
            if (!file_exists(rtrim(_PS_ROOT_DIR_, DIRECTORY_SEPARATOR).str_replace('/', DIRECTORY_SEPARATOR, $file))) {
                if ($full) {
                    $return[] = $file;
                } else {
                    return false;
                }
            }
        }

        if ($full) {
            return $return;
        }

        return true;
    }
}
