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
 * Class ConfigurationTestCore
 *
 * @since 1.0.0
 */
class ConfigurationTestCore
{
    /**
     * @var array $testFiles
     *
     * @since 1.0.0 Renamed from $test_files
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
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getDefaultTests()
    {
        $tests = [
            'Upload'                  => false,
            'CacheDir'                => 'cache',
            'LogDir'                  => 'log',
            'ImgDir'                  => 'img',
            'ModuleDir'               => 'modules',
            'ThemeLangDir'            => 'themes/'._THEME_NAME_.'/lang/',
            'ThemePdfLangDir'         => 'themes/'._THEME_NAME_.'/pdf/lang/',
            'ThemeCacheDir'           => 'themes/'._THEME_NAME_.'/cache/',
            'TranslationsDir'         => 'translations',
            'CustomizableProductsDir' => 'upload',
            'VirtualProductsDir'      => 'download',
            'System'                  => [
                'fopen', 'fclose', 'fread', 'fwrite',
                'rename', 'file_exists', 'unlink', 'rmdir', 'mkdir',
                'getcwd', 'chdir', 'chmod',
            ],
            'PhpVersion'              => false,
            'Gd'                      => false,
            'ConfigDir'               => 'config',
            'Files'                   => false,
            'MailsDir'                => 'mails',
            'MaxExecutionTime'        => false,
            'PdoMysql'                => false,
            'Bcmath'                  => false,
            'Xml'                     => false,
            'Json'                    => false,
            'Zip'                     => false,
        ];

        return $tests;
    }

    /**
     * getDefaultTestsOp return an array of tests to executes.
     * key are method name, value are parameters (false for no parameter)
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getDefaultTestsOp()
    {
        return [
            'NewPhpVersion'   => false,
            'RegisterGlobals' => false,
            'Gz'              => false,
            'Mbstring'        => false,
            'Tlsv12'          => false,
        ];
    }

    /**
     * run all test defined in $tests
     *
     * @param array $tests
     *
     * @return array results of tests
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function check($tests)
    {
        $res = [];
        foreach ($tests as $key => $test) {
            $res[$key] = ConfigurationTest::run($key, $test);
        }

        return $res;
    }

    /**
     * @param string $ptr
     * @param int    $arg
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function run($ptr, $arg = 0)
    {
        if (call_user_func(['ConfigurationTest', 'test'.$ptr], $arg)) {
            return 'ok';
        }

        return 'fail';
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testPhpVersion()
    {
        return version_compare(PHP_VERSION, '5.5.0', '>=');
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testNewPhpVersion()
    {
        return version_compare(PHP_VERSION, '5.6.0', '>=');
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testMysqlSupport()
    {
        return extension_loaded('mysql') || extension_loaded('mysqli') || extension_loaded('pdo_mysql');
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testPdoMysql()
    {
        return extension_loaded('pdo_mysql');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public static function testBcmath()
    {
        return extension_loaded('bcmath') && function_exists('bcdiv');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public static function testXml()
    {
        return class_exists('SimpleXMLElement');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public static function testJson()
    {
        return function_exists('json_encode') && function_exists('json_decode');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public static function testZip()
    {
        return class_exists('ZipArchive');
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testMagicQuotes()
    {
        return !get_magic_quotes_gpc();
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testUpload()
    {
        return ini_get('file_uploads');
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testFopen()
    {
        return ini_get('allow_url_fopen');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public static function testTlsv12()
    {
        $guzzle = new GuzzleHttp\Client([
            'http_errors' => false,
            'verify' => _PS_TOOL_DIR_.'cacert.pem',
            'timeout' => 5,
        ]);
        try {
            $response = $guzzle->get('https://tlstest.paypal.com/');
            $success = (string) $response->getBody() === 'PayPal_Connection_OK';
        } catch (Exception $e) {
            $success = false;
        }

        return $success;
    }

    /**
     * @param array $funcs
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testSystem($funcs)
    {
        foreach ($funcs as $func) {
            if (!function_exists($func)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testGd()
    {
        return function_exists('imagecreatetruecolor');
    }

    /**
     * @return bool
     *
     * @since   1.0.1
     * @version 1.0.1 Initial version
     */
    public static function testMaxExecutionTime()
    {
        return ini_get('max_execution_time') <= 0 || ini_get('max_execution_time') >= 30;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testRegisterGlobals()
    {
        return !ini_get('register_globals');
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testConfigDir($dir)
    {
        return ConfigurationTest::testDir($dir);
    }

    /**
     * Test if directory is writable
     *
     * @param string $dir        Directory path, absolute or relative
     * @param bool   $recursive
     * @param null   $fullReport
     * @param bool   $absolute   Is absolute path to directory
     *
     * @return bool
     *
     * @since   1.0.0 Added $absolute parameter
     * @version 1.0.0 Initial version
     */
    public static function testDir($dir, $recursive = false, &$fullReport = null, $absolute = false)
    {
        if ($absolute) {
            $absoluteDir = $dir;
        } else {
            $absoluteDir = rtrim(_PS_ROOT_DIR_, '\\/').DIRECTORY_SEPARATOR.trim($dir, '\\/');
        }

        if (!file_exists($absoluteDir)) {
            $fullReport = sprintf('Directory %s does not exist or is not writable', $absoluteDir);

            return false;
        }

        if (!is_writable($absoluteDir)) {
            $fullReport = sprintf('Directory %s is not writable', $absoluteDir);

            return false;
        }

        if ($recursive) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absoluteDir)) as $file) {
                /** @var SplFileInfo $file */
                if (in_array($file->getFilename(), ['.', '..']) || $file->isLink()) {
                    continue;
                }

                if (!is_writable($file)) {
                    $fullReport = sprintf('File %s is not writable', $file);

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testSitemap($dir)
    {
        return ConfigurationTest::testFile($dir);
    }

    /**
     * @param string $fileRelative
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testFile($fileRelative)
    {
        $file = _PS_ROOT_DIR_.DIRECTORY_SEPARATOR.$fileRelative;

        return (file_exists($file) && is_writable($file));
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testRootDir($dir)
    {
        return ConfigurationTest::testDir($dir);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testLogDir($dir)
    {
        return ConfigurationTest::testDir($dir);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testAdminDir($dir)
    {
        return ConfigurationTest::testDir($dir);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testImgDir($dir)
    {
        return ConfigurationTest::testDir($dir, true);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testModuleDir($dir)
    {
        return ConfigurationTest::testDir($dir, true);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testCacheDir($dir)
    {
        return ConfigurationTest::testDir($dir, true);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testToolsV2Dir($dir)
    {
        return ConfigurationTest::testDir($dir);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testCacheV2Dir($dir)
    {
        return ConfigurationTest::testDir($dir);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testDownloadDir($dir)
    {
        return ConfigurationTest::testDir($dir);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testMailsDir($dir)
    {
        return ConfigurationTest::testDir($dir, true);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testTranslationsDir($dir)
    {
        return ConfigurationTest::testDir($dir, true);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testThemeLangDir($dir)
    {
        $absoluteDir = rtrim(_PS_ROOT_DIR_, '\\/').DIRECTORY_SEPARATOR.trim($dir, '\\/');
        if (!file_exists($absoluteDir)) {
            return false;
        }

        return ConfigurationTest::testDir($dir, true);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testThemePdfLangDir($dir)
    {
        $absoluteDir = rtrim(_PS_ROOT_DIR_, '\\/').DIRECTORY_SEPARATOR.trim($dir, '\\/');
        if (!file_exists($absoluteDir)) {
            return true;
        }

        return ConfigurationTest::testDir($dir, true);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testThemeCacheDir($dir)
    {
        $absoluteDir = rtrim(_PS_ROOT_DIR_, '\\/').DIRECTORY_SEPARATOR.trim($dir, '\\/');
        if (!file_exists($absoluteDir)) {
            return true;
        }

        return ConfigurationTest::testDir($dir, true);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testCustomizableProductsDir($dir)
    {
        return ConfigurationTest::testDir($dir);
    }

    /**
     * @param $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testVirtualProductsDir($dir)
    {
        return ConfigurationTest::testDir($dir);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testMbstring()
    {
        return function_exists('mb_strtolower');
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @deprecated since PHP 7.1
     */
    public static function testMcrypt()
    {
        return function_exists('mcrypt_encrypt');
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testSessions()
    {
        if (!$path = @ini_get('session.save_path')) {
            return true;
        }

        return is_writable($path);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testDom()
    {
        return extension_loaded('Dom');
    }

    /**
     * @param bool $full
     *
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testFiles($full = false)
    {
        $return = [];
        foreach (ConfigurationTest::$testFiles as $file) {
            if (!file_exists(rtrim(_PS_ROOT_DIR_, DIRECTORY_SEPARATOR).str_replace('/', DIRECTORY_SEPARATOR, $file))) {
                if ($full) {
                    array_push($return, $file);
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
