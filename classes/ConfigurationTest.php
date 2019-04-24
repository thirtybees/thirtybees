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
            'Fopen'                   => false,
            'ConfigDir'               => 'config',
            'Files'                   => false,
            'MailsDir'                => 'mails',
            'MaxExecutionTime'        => false,
            'MysqlVersion'            => false,
            // PHP extensions.
            'Bcmath'                  => false,
            'Gd'                      => false,
            'Json'                    => false,
            'Mbstring'                => false,
            'OpenSSL'                 => false,
            'PdoMysql'                => false,
            'Xml'                     => false,
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
            'Gz'              => false,
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
            $res[$key] = static::run($key, $test);
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
        if ($arg) {
            $result = call_user_func_array(['static', 'test'.$ptr], [$arg, &$report]);
        } else {
            $result = call_user_func_array(['static', 'test'.$ptr], [&$report]);
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
     *
     * @since   1.0.0
     * @since   1.0.8 Fill error report.
     * @version 1.0.0 Initial version
     */
    public static function testPhpVersion(&$report = null)
    {
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            $report = sprintf('PHP version is %s, should be at least version 5.6.', PHP_VERSION);

            return false;
        }

        return true;
    }

    /**
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testMysqlSupport()
    {
        Tools::displayAsDeprecated();

        return true;
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
     * @since   1.0.8
     */
    public static function testMysqlVersion(&$report = null)
    {
        if (defined('_DB_SERVER_') && defined('_DB_USER_')
            && defined('_DB_PASSWD_') && defined('_DB_NAME_')) {
            $version = Db::getInstance()->getVersion();

            if (version_compare($version, '5.5', '<')) {
                $report = sprintf('DB server is v%s, should be at least MySQL v5.5.3 or MariaDB v5.5.', $version);

                return false;
            }
        }
        // Else probably installation time.

        return true;
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
     * @deprecated 1.0.8
     */
    public static function testMagicQuotes()
    {
        Tools::displayAsDeprecated();

        return true;
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
            'verify'  => _PS_TOOL_DIR_.'cacert.pem',
            'timeout' => 20,
        ]);

        $success = false;
        try {
            $response = $guzzle->get('https://tlstest.paypal.com/');
            $success = (string) $response->getBody() === 'PayPal_Connection_OK';
        } catch (Exception $e) {
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
        return ini_get('max_execution_time') <= 0
               || ini_get('max_execution_time') >= 30;
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
        return static::testDir($dir, false, $report);
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
     * @param string $dir
     *
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testSitemap($dir, &$report = null)
    {
        Tools::displayAsDeprecated();

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
     * @deprecated 1.0.8
     */
    public static function testRootDir($dir, &$report = null)
    {
        Tools::displayAsDeprecated();

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
    public static function testLogDir($dir, &$report = null)
    {
        return static::testDir($dir, false, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testAdminDir($dir, &$report = null)
    {
        Tools::displayAsDeprecated();

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
    public static function testImgDir($dir, &$report = null)
    {
        return static::testDir($dir, true, $report);
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
        return static::testDir($dir, true, $report);
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
        return static::testDir($dir, true, $report);
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testToolsV2Dir($dir, &$report = null)
    {
        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testCacheV2Dir($dir, &$report = null)
    {
        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @param string $dir
     *
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testDownloadDir($dir, &$report = null)
    {
        Tools::displayAsDeprecated();

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
    public static function testMailsDir($dir, &$report = null)
    {
        return static::testDir($dir, true, $report);
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
        return static::testDir($dir, true, $report);
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

        return static::testDir($dir, true, $report);
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

        return static::testDir($dir, true, $report);
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

        return static::testDir($dir, true, $report);
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
        return static::testDir($dir, false, $report);
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
        return static::testDir($dir, false, $report);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function testMbstring()
    {
        return extension_loaded('mbstring');
    }

    /**
     * @return bool
     *
     * @since 1.1.0
     */
    public static function testOpenSSL()
    {
        return extension_loaded('openssl')
               && function_exists('openssl_encrypt');
    }

    /**
     * @return bool
     *
     * @deprecated 1.0.8
     * @deprecated since PHP 7.1
     */
    public static function testMcrypt()
    {
        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testSessions()
    {
        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @return bool
     *
     * @deprecated 1.0.8
     */
    public static function testDom()
    {
        Tools::displayAsDeprecated();

        return true;
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
        foreach (static::$testFiles as $file) {
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
