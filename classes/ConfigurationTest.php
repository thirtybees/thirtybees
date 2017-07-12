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
     * @return string 'ok' on success, 'fail' or error message on failure.
     *
     * @since   1.0.2 Also report error message.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function run($ptr, $arg = 0)
    {
        $report = '';
        $result = call_user_func_array(['ConfigurationTest', 'test'.$ptr], [$arg, &$report]);

        if (strlen($report)) {
            return $report;
        } elseif (!$result) {
            return 'fail';
        }

        return 'ok';
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
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testConfigDir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, false, $report);
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
                    if (!ConfigurationTest::testDir($path, $recursive, $fullReport, true)) {
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
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testSitemap($dir, &$report = null)
    {
        if (!ConfigurationTest::testFile($dir)) {
            $report = 'File or directory '.$dir.' is not writable.';
            return false;
        }

        return true;
    }

    /**
     * @param string $fileRelative
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testRootDir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, false, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testLogDir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, false, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testAdminDir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, false, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testImgDir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testModuleDir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testCacheDir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testToolsV2Dir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, false, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testCacheV2Dir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, false, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testDownloadDir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, false, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testMailsDir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testTranslationsDir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testThemeLangDir($dir, &$report = null)
    {
        $absoluteDir = rtrim(_PS_ROOT_DIR_, '\\/').DIRECTORY_SEPARATOR.trim($dir, '\\/');
        if (!file_exists($absoluteDir)) {
            return false;
        }

        return ConfigurationTest::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testThemePdfLangDir($dir, &$report = null)
    {
        $absoluteDir = rtrim(_PS_ROOT_DIR_, '\\/').DIRECTORY_SEPARATOR.trim($dir, '\\/');
        if (!file_exists($absoluteDir)) {
            return true;
        }

        return ConfigurationTest::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testThemeCacheDir($dir, &$report = null)
    {
        $absoluteDir = rtrim(_PS_ROOT_DIR_, '\\/').DIRECTORY_SEPARATOR.trim($dir, '\\/');
        if (!file_exists($absoluteDir)) {
            return true;
        }

        return ConfigurationTest::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testCustomizableProductsDir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, false, $report);
    }

    /**
     * @param $dir
     *
     * @return bool
     *
     * @since   1.0.2 Add $report.
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testVirtualProductsDir($dir, &$report = null)
    {
        return ConfigurationTest::testDir($dir, false, $report);
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
     * Test the set of files defined above. Not used by the installer, but by
     * AdminInformationController.
     *
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
