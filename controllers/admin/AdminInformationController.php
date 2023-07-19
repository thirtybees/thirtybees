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

/**
 * Class AdminInformationControllerCore
 */
class AdminInformationControllerCore extends AdminController
{
    /**
     * @var array $fileList
     */
    public $fileList = [];

    /**
     * @var string $excludeRegexp
     */
    protected $excludeRegexp = '^/(install(-dev|-new)?|vendor|themes|tools|cache|docs|download|img|localization|log|mails|translations|upload|modules|override/(.*|index\.php)$)';

    /**
     * AdminInformationControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        $this->show_toolbar = false;
        if ( ! $this->ajax) {
            $this->display = 'view';
        }

        if (Tools::getValue('display') === 'phpinfo') {
            die(phpinfo());
        }

        parent::initContent();
    }

    /**
     * @return void
     */
    public function initToolbarTitle()
    {
        $this->toolbar_title = array_unique($this->breadcrumbs);
    }

    /**
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        unset($this->page_header_toolbar_btn['back']);
    }

    /**
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderView()
    {
        $this->initPageHeaderToolbar();
        $buildPhp = defined('_TB_BUILD_PHP_') ? _TB_BUILD_PHP_ : '';
        $vars = [
            'version'         => [
                'php'                => phpversion(),
                'phpinfoUrl'         => $this->context->link->getAdminLink('AdminInformation', true, [ 'display' => 'phpinfo' ]),
                'server'             => $_SERVER['SERVER_SOFTWARE'],
                'memory_limit'       => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'database'        => [
                'version' => Db::getInstance()->getVersion(),
                'server'  => _DB_SERVER_,
                'name'    => _DB_NAME_,
                'user'    => _DB_USER_,
                'prefix'  => _DB_PREFIX_,
                'engine'  => _MYSQL_ENGINE_,
                'driver'  => 'PDO',
            ],
            'uname'           => function_exists('php_uname') ? php_uname('s').' '.php_uname('v').' '.php_uname('m') : '',
            'apache_instaweb' => Tools::apacheModExists('mod_instaweb'),
            'shop'            => [
                'version'  => _TB_VERSION_,
                'revision' => _TB_REVISION_,
                'build_php'=> $buildPhp,
                'wrong_php'=> $buildPhp != PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
                'url'      => $this->context->shop->getBaseURL(),
                'theme'    => $this->context->shop->theme_name,
            ],
            'user_agent'      => $_SERVER['HTTP_USER_AGENT'],
        ];

        $this->tpl_view_vars = array_merge($this->getTestResult(), array_merge($vars));

        return parent::renderView();
    }

    /**
     * get all tests
     *
     * @return array of test results
     */
    public function getTestResult()
    {
        $testsErrors = [
            ConfigurationTest::TEST_UPLOAD => $this->l('Configure your server to allow file uploads.'),
            ConfigurationTest::TEST_SYSTEM => $this->l('At least one of these PHP functions is missing: fopen(), fclose(), fread(), fwrite(), rename(), file_exists(), unlink(), rmdir(), mkdir(), getcwd(), chdir() and/or chmod().'),
            ConfigurationTest::TEST_CONFIG_DIR => $this->l('Set write permissions for the "config" folder.'),
            ConfigurationTest::TEST_CACHE_DIR => $this->l('Set write permissions for the "cache" folder.'),
            ConfigurationTest::TEST_IMG_DIR => $this->l('Set write permissions for the "img" folder and subfolders.'),
            ConfigurationTest::TEST_LOG_DIR => $this->l('Set write permissions for the "log" folder and subfolders.'),
            ConfigurationTest::TEST_MAILS_DIR => $this->l('Set write permissions for the "mails" folder and subfolders.'),
            ConfigurationTest::TEST_MODULES_DIR => $this->l('Set write permissions for the "modules" folder and subfolders.'),
            ConfigurationTest::TEST_THEME_LANG_DIR => sprintf($this->l('Set the write permissions for the "themes%s/lang/" folder and subfolders, recursively.'), _THEME_NAME_),
            ConfigurationTest::TEST_THEME_PDF_LANG_DIR => sprintf($this->l('Set the write permissions for the "themes%s/pdf/lang/" folder and subfolders, recursively.'), _THEME_NAME_),
            ConfigurationTest::TEST_THEME_CACHE_DIR => sprintf($this->l('Set the write permissions for the "themes%s/cache/" folder and subfolders, recursively.'), _THEME_NAME_),
            ConfigurationTest::TEST_TRANSLATIONS_DIR => $this->l('Set write permissions for the "translations" folder and subfolders.'),
            ConfigurationTest::TEST_CUSTOMIZABLE_PRODUCTS_DIR => $this->l('Set write permissions for the "upload" folder and subfolders.'),
            ConfigurationTest::TEST_VIRTUAL_PRODUCTS_DIR => $this->l('Set write permissions for the "download" folder and subfolders.'),
            ConfigurationTest::TEST_FOPEN => $this->l('Allow PHP fopen() on your server to open remote files/URLs.'),
            ConfigurationTest::TEST_FILES => $this->l('Some thirty bees files are missing from your server.'),
            ConfigurationTest::TEST_MAX_EXECUTION_TIME => $this->l('Set PHP `max_execution_time` to at least 30 seconds.'),
            ConfigurationTest::TEST_BCMATH => $this->l('Install the `bcmath` PHP extension on your server.'),
            ConfigurationTest::TEST_GD => $this->l('Enable the GD library on your server.'),
            ConfigurationTest::TEST_INTL => $this->l('Install the \'intl\' PHP extension on your server.'),
            ConfigurationTest::TEST_SOAP => $this->l('Install the \'soap\' PHP extension on your server.'),
            ConfigurationTest::TEST_JSON => $this->l('Install the `json` PHP extension on your server.'),
            ConfigurationTest::TEST_MBSTRING => $this->l('The `mbstring` extension has not been installed/enabled. This has a severe impact on the store\'s performance.'),
            ConfigurationTest::TEST_OPENSSL => $this->l('The `openssl` extension has not been installed/enabled.'),
            ConfigurationTest::TEST_PDO_MYSQL => $this->l('Install the PHP extension for MySQL with PDO support on your server.'),
            ConfigurationTest::TEST_XML => $this->l('Install the `xml` PHP extension on your server.'),
            ConfigurationTest::TEST_ZIP => $this->l('Install the `zip` PHP extension on your server.'),
            ConfigurationTest::TEST_GZ => $this->l('Enable GZIP compression on your server.'),
        ];

        // Functions list to test with 'test_system'
        // Test to execute (function/args): lets uses the default test
        $paramsRequiredResults = ConfigurationTest::check(ConfigurationTest::getDefaultTests());
        $paramsOptionalResults = ConfigurationTest::check(ConfigurationTest::getDefaultTestsOp());

        $failRequired = false;
        foreach ($paramsRequiredResults as $key => $result) {
            if ($result !== 'ok') {
                $failRequired = true;
                $testsErrors[$key] .= '<br/>'.sprintf($this->l('Test result: %s'), $result);
                // Establish retrocompatibility with templates.
                $paramsRequiredResults[$key] = 'fail';
            }
        }
        $failOptional = false;
        foreach ($paramsOptionalResults as $key => $result) {
            if ($result !== 'ok') {
                $failOptional = true;
                $testsErrors[$key] .= '<br/>'.sprintf($this->l('Test result: %s'), $result);
                // Establish retrocompatibility with templates.
                $paramsOptionalResults[$key] = 'fail';
            }
        }

        if ($failRequired && $paramsRequiredResults['Files'] !== 'ok') {
            $tmp = ConfigurationTest::testFiles(true);
            if (is_array($tmp) && count($tmp)) {
                $testsErrors['Files'] = $testsErrors['Files'].'<br/>('.implode(', ', $tmp).')';
            }
        }

        $results = [
            'failRequired'  => $failRequired,
            'testsRequired' => $paramsRequiredResults,
            'failOptional'  => $failOptional,
            'testsOptional' => $paramsOptionalResults,
            'testsErrors'   => $testsErrors,
        ];

        return $results;
    }

    /**
     * @throws PrestaShopException
     */
    public function displayAjaxCheckFiles()
    {
        $this->fileList = [
            'listMissing'   => false,
            'isDevelopment' => false,
            'missing'       => [],
            'updated'       => [],
            'obsolete'      => [],
        ];
        $filesFile = _PS_CONFIG_DIR_.'json/files.json';
        if (file_exists($filesFile)) {
            $files = json_decode(file_get_contents($filesFile), true);
            $this->getListOfUpdatedFiles($files);
        } else {
            $this->fileList['listMissing'] = $filesFile;
        }

        if (file_exists(_PS_ROOT_DIR_.'/admin-dev/')) {
            $this->fileList['isDevelopment'] = true;
        }

        $this->ajaxDie(json_encode($this->fileList));
    }

    /**
     * Get the list of files to be checked and save it in
     * config/json/files.json. This can't be done from back office, but
     * is done automatically when building a distribution package.
     *
     * @return array md5 list
     */
    public static function generateMd5List()
    {
        $md5List = [];
        $adminDir = str_replace(_PS_ROOT_DIR_, '', _PS_ADMIN_DIR_);
        $adminDir = str_replace(DIRECTORY_SEPARATOR, '/', $adminDir);

        $iterator = static::getCheckFileIterator();
        foreach ($iterator as $file) {
            /** @var DirectoryIterator $file */
            $filePath = $file->getRealPath();
            $filePath = str_replace(_PS_ROOT_DIR_, '', $filePath);
            $filePath = str_replace(DIRECTORY_SEPARATOR, '/', $filePath);
            if (in_array($file->getFilename(), ['.', '..', 'index.php'])) {
                continue;
            }

            if (strpos($filePath, $adminDir) !== false) {
                $filePath = str_replace($adminDir, '/admin', $filePath);
            }
            $path = $file->getRealPath();
            if ($path !== false && is_file($path) && !is_dir($path)) {
                $md5List[$filePath] = md5_file($path);
            }
        }

        file_put_contents(
            _PS_CONFIG_DIR_.'json/files.json',
            json_encode($md5List, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)
        );

        return $md5List;
    }

    /**
     * Generate a list of files to be checked.
     *
     * @return AppendIterator Iterator of all files to be checked.
     */
    protected static function getCheckFileIterator()
    {
        $iterator = new AppendIterator();
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_PS_CLASS_DIR_)));
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_PS_CONTROLLER_DIR_)));
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_PS_ROOT_DIR_.'/Core')));
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_PS_ROOT_DIR_.'/Adapter')));
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_PS_ROOT_DIR_.'/vendor')));
        $iterator->append(new DirectoryIterator(_PS_ADMIN_DIR_));

        return $iterator;
    }

    /**
     * @param array $md5List
     * @param string|null $basePath
     */
    public function getListOfUpdatedFiles(array $md5List, $basePath = null)
    {
        $adminDir = str_replace(_PS_ROOT_DIR_, '', _PS_ADMIN_DIR_);
        $adminDir = str_replace(DIRECTORY_SEPARATOR, '/', $adminDir);
        if (is_null($basePath)) {
            $basePath = rtrim(_PS_ROOT_DIR_, DIRECTORY_SEPARATOR);
        }

        foreach ($md5List as $file => $md5) {
            if (strpos($file, '/admin/') === 0) {
                $file = str_replace('/admin/', $adminDir.'/', $file);
            }

            if (!file_exists($basePath.$file)) {
                $this->fileList['missing'][] = ltrim($file, '/');
                continue;
            }

            if (is_file($basePath.$file) && md5_file($basePath.$file) != $md5) {
                $this->fileList['updated'][] = ltrim($file, '/');
                continue;
            }
        }

        $fileList = array_keys($md5List);

        $iterator = static::getCheckFileIterator();
        foreach ($iterator as $file) {
            if (in_array($file->getFilename(), ['.', '..', 'index.php'])) {
                continue;
            }
            $realPath = $file->getRealPath();
            if ($realPath === false || is_dir($realPath)) {
                continue;
            }

            $path = str_replace($basePath, '', $file->getPathname());
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
            $path = str_replace($adminDir, '/admin', $path);
            if ( ! in_array($path, $fileList)) {
                $this->fileList['obsolete'][] = ltrim($path, '/');
            }
        }
    }
}
