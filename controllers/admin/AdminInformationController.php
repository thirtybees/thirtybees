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
        $this->tpl_view_vars = [
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
                'rootDir'  => _PS_ROOT_DIR_,
            ],
            'user_agent'      => $_SERVER['HTTP_USER_AGENT'],
        ];

        return parent::renderView();
    }

    /**
     * get all tests
     *
     * @return array of test results
     */
    public function getTestResult()
    {
        return [
            'required' => $this->runTests(ConfigurationTest::getDefaultTests()),
            'optional' =>  $this->runTests(ConfigurationTest::getDefaultTestsOp()),
        ];
    }

    /**
     * @param array $tests
     * @return array
     */
    protected function runTests(array $tests)
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
            ConfigurationTest::TEST_YAML => $this->l('Install the \'yaml\' PHP extension on your server.'),
            ConfigurationTest::TEST_JSON => $this->l('Install the `json` PHP extension on your server.'),
            ConfigurationTest::TEST_MBSTRING => $this->l('The `mbstring` extension has not been installed/enabled. This has a severe impact on the store\'s performance.'),
            ConfigurationTest::TEST_OPENSSL => $this->l('The `openssl` extension has not been installed/enabled.'),
            ConfigurationTest::TEST_PDO_MYSQL => $this->l('Install the PHP extension for MySQL with PDO support on your server.'),
            ConfigurationTest::TEST_XML => $this->l('Install the `xml` PHP extension on your server.'),
            ConfigurationTest::TEST_ZIP => $this->l('Install the `zip` PHP extension on your server.'),
            ConfigurationTest::TEST_GZ => $this->l('Enable GZIP compression on your server.'),
        ];

        $results = ConfigurationTest::check($tests);
        $output = [];
        foreach ($results as $key => $result) {
            if ($result !== 'ok') {
                $entry = [
                    'test' => $key,
                    'message' => $testsErrors[$key] ?? 'Unknown test',
                    'result' => $result === 'fail' ? $this->l('Test failed') : $result,
                ];
                if ($key === ConfigurationTest::TEST_FILES) {
                    $entry['extra'] = ConfigurationTest::testFiles(true);
                }
                $output[] = $entry;
            }
        }
        return $output;
    }

    /**
     * @throws PrestaShopException
     */
    public function displayAjaxCheckFiles()
    {
        $this->setJSendErrorHandling();
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
     * @return void
     *
     * @throws PrestaShopException
     */
    public function displayAjaxRunTests()
    {
        $this->setJSendErrorHandling();
        $this->ajaxDie(json_encode([
            'status' => 'success',
            'data' => $this->getTestResult()
        ]));
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
    
    /**
     * AJAX: scan a batch of directories for directory-listing.
     * params: offset, limit
     */
    public function displayAjaxScanOpenDirs()
    {
        $this->setJSendErrorHandling();

        // Small safety on inputs
        $offset = max(0, (int) Tools::getValue('offset', 0));
        $limit  = min(300, max(50, (int) Tools::getValue('limit', 150)));

        // Build the full dir list (fast), then slice and probe
        $all = $this->listDirsRecursively(_PS_ROOT_DIR_);
        $slice = array_slice($all, $offset, $limit);

        $base = rtrim(Tools::getShopDomainSsl(true) . __PS_BASE_URI__, '/') . '/';

        $vulnerable = [];
        foreach ($slice as $abs) {
            // Quick FS shortcut
            if (file_exists($abs.'/index.php') || file_exists($abs.'/index.html') || file_exists($abs.'/index.htm')) {
                continue;
            }

            $rel = ltrim(str_replace(_PS_ROOT_DIR_, '', $abs), DIRECTORY_SEPARATOR);
            $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
            if ($rel === '') {
                continue;
            }
            $url = $base . rtrim($rel, '/') . '/';

            $probe = $this->probeDirectory($url);
            if ($probe['status'] === 'listing') {
                $vulnerable[] = rtrim($rel, '/') . '/';
            }
        }

        $next = $offset + count($slice);
        $done = $next >= count($all);

        $this->ajaxDie(json_encode([
            'status'     => 'success',
            'offset'     => $next,
            'total'      => count($all),
            'done'       => $done,
            'vulnerable' => $vulnerable,
        ]));
    }

    /**
     * Recursively enumerate ALL directories under $root, but:
     * - DO NOT descend into cache/smarty/cache/** and cache/smarty/compile/**
     * - Still include cache/smarty/cache and cache/smarty/compile themselves in the result
     * Returns absolute paths without trailing slash.
     */
    protected function listDirsRecursively($root)
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR);

        // Normalize absolute prefixes for the two subtrees to skip
        $smartyBase   = $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'smarty';
        $skipCache    = $smartyBase . DIRECTORY_SEPARATOR . 'cache'   . DIRECTORY_SEPARATOR;
        $skipCompile  = $smartyBase . DIRECTORY_SEPARATOR . 'compile' . DIRECTORY_SEPARATOR;

        $dirs = [];

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $root,
                FilesystemIterator::SKIP_DOTS // do NOT follow symlinks to avoid loops
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $info) {
            /** @var SplFileInfo $info */
            if (!$info->isDir()) {
                continue;
            }

            // Absolute path + with trailing slash for prefix comparisons
            $absNoTrail = rtrim($info->getPathname(), DIRECTORY_SEPARATOR);
            $abs        = $absNoTrail . DIRECTORY_SEPARATOR;

            if ($abs === rtrim($skipCache, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
             || $abs === rtrim($skipCompile, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) {
                $dirs[] = $absNoTrail;
                // Do NOT "continue 2"; we simply don't need a special action here. Children will be skipped by the checks below.
                continue;
            }

            // Skip ANY directory that is under the two subtrees
            // (i.e., has one of the skip prefixes)
            if (strpos($abs, $skipCache) === 0 || strpos($abs, $skipCompile) === 0) {
                continue;
            }

            // Otherwise, include this directory
            $dirs[] = $absNoTrail;
        }

        sort($dirs);
        return $dirs;
    }

    /** Quiet HTTP probe + listing detection. */
    protected function probeDirectory($url)
    {
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => 2,
                'ignore_errors' => true, // do not emit warnings on 4xx/5xx
                'header'        =>
                    "User-Agent: TB-DirProbe/1.0\r\n" .
                    "Accept: text/html\r\n" .
                    "Range: bytes=0-16383\r\n",
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);

        $code = 0;
        if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0], $m)) {
            $code = (int) $m[1];
        }

        $sig = $this->detectListingSignature((string) $body);

        if ($code >= 200 && $code < 300 && $sig !== null) {
            return ['status' => 'listing', 'code' => $code, 'sig' => $sig];
        }
        return ['status' => 'ok', 'code' => $code, 'sig' => $sig];
    }

    /** Heuristic for Apache/nginx/lighttpd directory indexes. */
    protected function detectListingSignature($html)
    {
        if ($html === '') {
            return null;
        }
        $tests = [
            '~<title>\s*Index of /~i'        => 'apache-title',
            '~<h1>\s*Index of /~i'           => 'apache-h1',
            '~Parent Directory</a>~i'        => 'apache-parent',
            '~<pre>\s*<a href="/?">../</a>~i'=> 'nginx-pre',
            '~<address>lighttpd/.*</address>~i' => 'lighttpd',
        ];
        foreach ($tests as $rx => $label) {
            if (preg_match($rx, $html)) {
                return $label;
            }
        }
        return null;
    }

}
