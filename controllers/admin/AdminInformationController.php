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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class AdminInformationControllerCore
 *
 * @since 1.0.0
 */
class AdminInformationControllerCore extends AdminController
{
    /**
     * @var array $fileList
     *
     * @since 1.0.0
     */
    public $fileList = [];

    /**
     * @var string $excludeRegexp
     *
     * @since 1.0.0
     */
    protected $excludeRegexp = '^/(install(-dev|-new)?|vendor|themes|tools|cache|docs|download|img|localization|log|mails|translations|upload|modules|override/(.*|index\.php)$)';

    /**
     * AdminInformationControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * @since 1.0.0
     */
    public function initContent()
    {
        $this->show_toolbar = false;
        $this->display = 'view';
        parent::initContent();
    }

    /**
     * @since 1.0.0
     */
    public function initToolbarTitle()
    {
        $this->toolbar_title = array_unique($this->breadcrumbs);
    }

    /**
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        unset($this->page_header_toolbar_btn['back']);
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function renderView()
    {
        $this->initPageHeaderToolbar();
        $vars = [
            'version'         => [
                'php'                => phpversion(),
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
                'driver'  => Db::getClass(),
            ],
            'uname'           => function_exists('php_uname') ? php_uname('s').' '.php_uname('v').' '.php_uname('m') : '',
            'apache_instaweb' => Tools::apacheModExists('mod_instaweb'),
            'shop'            => [
                'ps'    => _TB_VERSION_,
                'url'   => $this->context->shop->getBaseURL(),
                'theme' => $this->context->shop->theme_name,
            ],
            'mail'            => Configuration::get('PS_MAIL_METHOD') == 1,
            'smtp'            => [
                'server'     => Configuration::get('PS_MAIL_SERVER'),
                'user'       => Configuration::get('PS_MAIL_USER'),
                'password'   => Configuration::get('PS_MAIL_PASSWD'),
                'encryption' => Configuration::get('PS_MAIL_SMTP_ENCRYPTION'),
                'port'       => Configuration::get('PS_MAIL_SMTP_PORT'),
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
            'PhpVersion'              => $this->l('Update your PHP version.'),
            'Upload'                  => $this->l('Configure your server to allow file uploads.'),
            'System'                  => $this->l('Configure your server to allow the creation of directories and files with write permissions.'),
            'Gd'                      => $this->l('Enable the GD library on your server.'),
            'MysqlSupport'            => $this->l('Enable the MySQL support on your server.'),
            'ConfigDir'               => $this->l('Set write permissions for the "config" folder.'),
            'CacheDir'                => $this->l('Set write permissions for the "cache" folder.'),
            'itemap'                  => $this->l('Set write permissions for the "sitemap.xml" file.'),
            'ImgDir'                  => $this->l('Set write permissions for the "img" folder and subfolders.'),
            'LogDir'                  => $this->l('Set write permissions for the "log" folder and subfolders.'),
            'MailsDir'                => $this->l('Set write permissions for the "mails" folder and subfolders.'),
            'ModuleDir'               => $this->l('Set write permissions for the "modules" folder and subfolders.'),
            'ThemeLangDir'            => sprintf($this->l('Set the write permissions for the "themes%s/lang/" folder and subfolders, recursively.'), _THEME_NAME_),
            'TranslationsDir'         => $this->l('Set write permissions for the "translations" folder and subfolders.'),
            'CustomizableProductsDir' => $this->l('Set write permissions for the "upload" folder and subfolders.'),
            'VirtualProductsDir'      => $this->l('Set write permissions for the "download" folder and subfolders.'),
            'Fopen'                   => $this->l('Allow the PHP fopen() function on your server.'),
            'Gz'                      => $this->l('Enable GZIP compression on your server.'),
            'Files'                   => $this->l('Some thirty bees files are missing from your server.'),
            'NewPhpVersion'           => sprintf($this->l('You are using PHP %s version. Soon, the oldest PHP version supported by thirty bees will be PHP 5.6. To make sure you’re ready for the future, we recommend you to upgrade to PHP 5.6 now!'), phpversion()),
        ];

        // Functions list to test with 'test_system'
        // Test to execute (function/args): lets uses the default test
        $paramsRequiredResults = ConfigurationTest::check(ConfigurationTest::getDefaultTests());
        $paramsOptionalResults = ConfigurationTest::check(ConfigurationTest::getDefaultTestsOp());

        $failRequired = in_array('fail', $paramsRequiredResults);

        if ($failRequired && $paramsRequiredResults['Files'] != 'ok') {
            $tmp = ConfigurationTest::testFiles(true);
            if (is_array($tmp) && count($tmp)) {
                $testsErrors['Files'] = $testsErrors['Files'].'<br/>('.implode(', ', $tmp).')';
            }
        }

        $results = [
            'failRequired'  => $failRequired,
            'testsErrors'   => $testsErrors,
            'testsRequired' => $paramsRequiredResults,
        ];

        $results = array_merge(
            $results,
            [
                'failOptional'  => in_array('fail', $paramsOptionalResults),
                'testsOptional' => $paramsOptionalResults,
            ]
        );


        return $results;
    }

    /**
     * @since 1.0.0
     *
     * @fixme: remove API call
     */
    public function displayAjaxCheckFiles()
    {
        $this->fileList = ['listMissing'   => false,
                           'isDevelopment' => false,
                           'missing'       => [],
                           'updated'       => []];
        $filesFile = _PS_CONFIG_DIR_.'json/files.json';
        if (file_exists($filesFile)) {
            $files = json_decode(file_get_contents($filesFile), true);
            $this->getListOfUpdatedFiles($files);
        } else {
          $this->fileList['listMissing'] = $filesFile;
          if (file_exists(_PS_ROOT_DIR_.'/admin-dev/')) {
            $this->fileList['isDevelopment'] = true;
          }
        }

        die(json_encode($this->fileList));
    }

    /**
     * Generate the list of files to be checked and also save it in
     * config/json/files.json. This can't be done from back office, but
     * is done automatically when building a distribution package.
     *
     * @return array md5 list
     */
    public function generateMd5List()
    {
        $md5List = [];
        $adminDir = str_replace(_PS_ROOT_DIR_, '', _PS_ADMIN_DIR_);
        $iterator = new AppendIterator();
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_PS_CLASS_DIR_)));
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_PS_CONTROLLER_DIR_)));
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_PS_ROOT_DIR_.'/Core')));
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_PS_ROOT_DIR_.'/Adapter')));
        $iterator->append(new DirectoryIterator(_PS_ADMIN_DIR_));

        foreach ($iterator as $file) {
            /** @var DirectoryIterator $file */
            $filePath = $file->getPathname();
            $filePath = str_replace(_PS_ROOT_DIR_, '', $filePath);
            if (in_array($file->getFilename(), ['.', '..', 'index.php'])) {
                continue;
            }

            if (strpos($filePath, $adminDir) !== false) {
                $filePath = str_replace($adminDir, '/admin', $filePath);
            }
            $md5List[$filePath] = md5_file($file->getPathname());
        }

        file_put_contents(_PS_CONFIG_DIR_.'json/files.json',
                          json_encode($md5List, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return $md5List;
    }

    /**
     * @param array       $md5List
     * @param string|null $basePath
     *
     * @since 1.0.0
     */
    public function getListOfUpdatedFiles(array $md5List, $basePath = null)
    {
        $adminDir = str_replace(_PS_ROOT_DIR_, '', _PS_ADMIN_DIR_);
        if (is_null($basePath)) {
            $basePath = rtrim(_PS_ROOT_DIR_, DIRECTORY_SEPARATOR);
        }

        foreach ($md5List as $file => $md5) {
            if (strpos($file, '/admin/') === 0) {
                $file = str_replace('/admin/', $adminDir.'/', $file);
            }

            if (!file_exists($basePath.$file)) {
                $this->fileList['missing'][] = $basePath.$file;
                continue;
            }

            if (md5_file($basePath.$file) != $md5) {
                $this->fileList['updated'][] = $basePath.$file;
                continue;
            }
        }
    }
}
