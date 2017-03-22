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
 * Class ModuleCore
 *
 * @since 1.0.0
 */
abstract class ModuleCore
{
    const CACHE_FILE_MODULES_LIST = '/config/xml/modules_list.xml';
    const CACHE_FILE_TAB_MODULES_LIST = '/config/xml/tab_modules_list.xml';
    // @codingStandardsIgnoreStart
    /** @var array used by AdminTab to determine which lang file to use (admin.php or module lang file) */
    public static $classInModule = [];
    /** @var bool Define if we will log modules performances for this session */
    public static $_log_modules_perfs = null;
    /** @var array $hosted_modules_blacklist */
    public static $hosted_modules_blacklist = ['autoupgrade'];
    /** @var bool Random session for modules perfs logs */
    public static $_log_modules_perfs_session = null;
    /** @var array Array cache filled with modules informations */
    protected static $modules_cache;
    /** @var array Array cache filled with modules instances */
    protected static $_INSTANCE = [];
    /** @var bool Config xml generation mode */
    protected static $_generate_config_xml_mode = false;
    /** @var array Array filled with cache translations */
    protected static $l_cache = [];
    /** @var array Array filled with cache permissions (modules / employee profiles) */
    protected static $cache_permissions = [];
    /** @var bool $update_translations_after_install */
    protected static $update_translations_after_install = true;
    /** @var bool $_batch_mode */
    protected static $_batch_mode = false;
    /** @var array $_defered_clearCache */
    protected static $_defered_clearCache = [];
    /** @var array $_defered_func_call */
    protected static $_defered_func_call = [];
    /** @var int Module ID */
    public $id = null;
    /** @var string $version Version */
    public $version;
    /** @var string $database_version */
    public $database_version;
    /** @var string Registered Version in database */
    public $registered_version;
    /** @var array filled with known compliant PrestaShop versions */
    public $ps_versions_compliancy = [];
    /**
     * @var string Filled with known compliant thirty bees versions
     *             This string contains a SemVer 1.0.0 range
     */
    public $tb_versions_compliancy = '*';
    /** @var array filled with modules needed for install */
    public $dependencies = [];
    /** @var string Unique name */
    public $name;
    /** @var string Human name */
    public $displayName;
    /** @var string A little description of the module */
    public $description;
    /** @var string author of the module */
    public $author;
    /** @var string URI author of the module */
    public $author_uri = '';
    /** @var string Module key */
    public $module_key = '';
    /** @var string $description_full */
    public $description_full;
    /** @var string $additional_description */
    public $additional_description;
    /** @var string $compatibility */
    public $compatibility;
    /** @var int $nb_rates */
    public $nb_rates;
    /** @var float $avg_rate */
    public $avg_rate;
    /** @var array $badges */
    public $badges;
    /** @var int need_instance */
    public $need_instance = 1;
    /** @var string Admin tab corresponding to the module */
    public $tab = null;
    /** @var bool Status */
    public $active = false;
    /** @var bool Is the module certified */
    public $trusted = true;
    /** @var string Fill it if the module is installed but not yet set up */
    public $warning;
    /** @var int $enable_device */
    public $enable_device = 7;
    /** @var array to store the limited country */
    public $limited_countries = [];
    /** @var array names of the controllers */
    public $controllers = [];
    /** @var bool If true, allow push */
    public $allow_push;
    /** @var int $push_time_limit */
    public $push_time_limit = 180;
    /**
     * @var bool $bootstrap
     *
     * Indicates whether the module's configuration page supports bootstrap
     */
    public $bootstrap = false;
    /** @var array current language translations */
    protected $_lang = [];
    /** @var string Module web path (eg. '/shop/modules/modulename/') */
    protected $_path = null;
    /** @var string Module local path (eg. '/home/prestashop/modules/modulename/') */
    protected $local_path = null;
    /** @var array Array filled with module errors */
    protected $_errors = [];
    /** @var array Array  array filled with module success */
    protected $_confirmations = [];
    /** @var string Main table used for modules installed */
    protected $table = 'module';
    /** @var string Identifier of the main table */
    protected $identifier = 'id_module';
    /** @var Context */
    protected $context;
    /** @var Smarty_Data */
    protected $smarty;
    /** @var Smarty_Internal_Template|null */
    protected $current_subtemplate = null;
    /** @var bool $installed */
    public $installed;
    // @codingStandardsIgnoreEnd

    /**
     * Constructor
     *
     * @param string  $name    Module unique name
     * @param Context $context
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($name = null, Context $context = null)
    {
        if (isset($this->ps_versions_compliancy) && !isset($this->ps_versions_compliancy['min'])) {
            $this->ps_versions_compliancy['min'] = '1.4.0.0';
        }

        if (isset($this->ps_versions_compliancy) && !isset($this->ps_versions_compliancy['max'])) {
            $this->ps_versions_compliancy['max'] = _PS_VERSION_;
        }

        if (strlen($this->ps_versions_compliancy['min']) == 3) {
            $this->ps_versions_compliancy['min'] .= '.0.0';
        }

        if (strlen($this->ps_versions_compliancy['max']) == 3) {
            $this->ps_versions_compliancy['max'] .= '.999.999';
        }

        // Load context and smarty
        $this->context = $context ? $context : Context::getContext();
        if (is_object($this->context->smarty)) {
            $this->smarty = $this->context->smarty->createData($this->context->smarty);
        }

        // If the module has no name we gave him its id as name
        if ($this->name === null) {
            $this->name = $this->id;
        }

        // If the module has the name we load the corresponding data from the cache
        // @codingStandardsIgnoreStart
        if ($this->name != null) {
            // If cache is not generated, we generate it
            if (self::$modules_cache == null && !is_array(self::$modules_cache)) {
                $idShop = (Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : Configuration::get('PS_SHOP_DEFAULT'));

                self::$modules_cache = [];
                // Join clause is done to check if the module is activated in current shop context
                $result = Db::getInstance()->executeS(
                    '
				SELECT m.`id_module`, m.`name`, (
					SELECT id_module
					FROM `'._DB_PREFIX_.'module_shop` ms
					WHERE m.`id_module` = ms.`id_module`
					AND ms.`id_shop` = '.(int) $idShop.'
					LIMIT 1
				) as mshop
				FROM `'._DB_PREFIX_.'module` m'
                );
                foreach ($result as $row) {
                    self::$modules_cache[$row['name']] = $row;
                    self::$modules_cache[$row['name']]['active'] = ($row['mshop'] > 0) ? 1 : 0;
                }
            }

            // We load configuration from the cache
            if (isset(self::$modules_cache[$this->name])) {
                if (isset(self::$modules_cache[$this->name]['id_module'])) {
                    $this->id = self::$modules_cache[$this->name]['id_module'];
                }
                foreach (self::$modules_cache[$this->name] as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->{$key} = $value;
                    }
                }
                $this->_path = __PS_BASE_URI__.'modules/'.$this->name.'/';
            }
            if (!$this->context->controller instanceof Controller) {
                self::$modules_cache = null;
            }
            $this->local_path = _PS_MODULE_DIR_.$this->name.'/';
        }
        // @codingStandardsIgnoreEnd
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getBatchMode()
    {
        // @codingStandardsIgnoreStart
        return self::$_batch_mode;
        // @codingStandardsIgnoreEnd
    }

    /**
     * Set the flag to indicate we are doing an import
     *
     * @param bool $value
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function setBatchMode($value)
    {
        // @codingStandardsIgnoreStart
        self::$_batch_mode = (bool) $value;
        // @codingStandardsIgnoreEnd
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function processDeferedFuncCall()
    {
        self::setBatchMode(false);
        // @codingStandardsIgnoreStart
        foreach (self::$_defered_func_call as $funcCall) {
            call_user_func_array($funcCall[0], $funcCall[1]);
        }
        self::$_defered_func_call = [];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Clear the caches stored in $_defered_clearCache
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function processDeferedClearCache()
    {
        self::setBatchMode(false);

        // @codingStandardsIgnoreStart
        foreach (self::$_defered_clearCache as $clearCacheArray) {
            self::_deferedClearCache($clearCacheArray[0], $clearCacheArray[1], $clearCacheArray[2]);
        }

        self::$_defered_clearCache = [];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Clear deferred template cache
     *
     * @param string   $templatePath Template path
     * @param int|null $cacheId
     * @param int|null $compileId
     *
     * @return int Number of template cleared
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function _deferedClearCache($templatePath, $cacheId, $compileId)
    {
        Tools::enableCache();
        $numberOfTemplateCleared = Tools::clearCache(Context::getContext()->smarty, $templatePath, $cacheId, $compileId);
        Tools::restoreCacheSettings();

        return $numberOfTemplateCleared;
    }

    /**
     * @param bool $update
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function updateTranslationsAfterInstall($update = true)
    {
        // @codingStandardsIgnoreStart
        Module::$update_translations_after_install = (bool) $update;
        // @codingStandardsIgnoreEnd
    }

    /**
     * Init the upgrade module
     *
     * @param Module $module
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function initUpgradeModule($module)
    {
        if (((int) $module->installed == 1) & (empty($module->database_version) === true)) {
            Module::upgradeModuleVersion($module->name, $module->version);
            $module->database_version = $module->version;
        }

        // Init cache upgrade details
        // @codingStandardsIgnoreStart
        self::$modules_cache[$module->name]['upgrade'] = [
            'success'             => false, // bool to know if upgrade succeed or not
            'available_upgrade'   => 0, // Number of available module before any upgrade
            'number_upgraded'     => 0, // Number of upgrade done
            'number_upgrade_left' => 0,
            'upgrade_file_left'   => [], // List of the upgrade file left
            'version_fail'        => 0, // Version of the upgrade failure
            'upgraded_from'       => 0, // Version number before upgrading anything
            'upgraded_to'         => 0, // Last upgrade applied
        ];
        // @codingStandardsIgnoreEnd

        // Need Upgrade will check and load upgrade file to the moduleCache upgrade case detail
        $ret = $module->installed && Module::needUpgrade($module);

        return $ret;
    }

    /**
     * Upgrade the registered version to a new one
     *
     * @param string $name
     * @param string $version
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function upgradeModuleVersion($name, $version)
    {
        return Db::getInstance()->execute(
            '
			UPDATE `'._DB_PREFIX_.'module` m
			SET m.version = \''.pSQL($version).'\'
			WHERE m.name = \''.pSQL($name).'\''
        );
    }

    /**
     * Check if a module need to be upgraded.
     * This method modify the module_cache adding an upgrade list file
     *
     * @param $module
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function needUpgrade($module)
    {
        self::$modules_cache[$module->name]['upgrade']['upgraded_from'] = $module->database_version;
        // Check the version of the module with the registered one and look if any upgrade file exist
        if (Tools::version_compare($module->version, $module->database_version, '>')) {
            $oldVersion = $module->database_version;
            $module = Module::getInstanceByName($module->name);
            if ($module instanceof Module) {
                return $module->loadUpgradeVersionList($module->name, $module->version, $oldVersion);
            }
        }

        return null;
    }

    /**
     * Return an instance of the specified module
     *
     * @param string $moduleName Module name
     *
     * @return Module|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getInstanceByName($moduleName)
    {
        if (!Validate::isModuleName($moduleName)) {
            if (_PS_MODE_DEV_) {
                die(Tools::displayError(Tools::safeOutput($moduleName).' is not a valid module name.'));
            }

            return false;
        }

        if (!isset(self::$_INSTANCE[$moduleName])) {
            if (!Tools::file_exists_no_cache(_PS_MODULE_DIR_.$moduleName.'/'.$moduleName.'.php')) {
                return false;
            }

            return Module::coreLoadModule($moduleName);
        }

        return self::$_INSTANCE[$moduleName];
    }

    /**
     * @param $moduleName
     *
     * @return bool|mixed|object
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function coreLoadModule($moduleName)
    {
        // Define if we will log modules performances for this session
        // @codingStandardsIgnoreStart
        if (Module::$_log_modules_perfs === null) {
            $modulo = _PS_DEBUG_PROFILING_ ? 1 : Configuration::get('PS_log_modules_perfs_MODULO');
            Module::$_log_modules_perfs = ($modulo && mt_rand(0, $modulo - 1) == 0);
            if (Module::$_log_modules_perfs) {
                Module::$_log_modules_perfs_session = mt_rand();
            }
        }

        // Store time and memory before and after hook call and save the result in the database
        if (Module::$_log_modules_perfs) {
            $timeStart = microtime(true);
            $memoryStart = memory_get_usage(true);
        }
        // @codingStandardsIgnoreEnd

        include_once(_PS_MODULE_DIR_.$moduleName.'/'.$moduleName.'.php');

        $r = false;
        if (Tools::file_exists_no_cache(_PS_OVERRIDE_DIR_.'modules/'.$moduleName.'/'.$moduleName.'.php')) {
            include_once(_PS_OVERRIDE_DIR_.'modules/'.$moduleName.'/'.$moduleName.'.php');
            $override = $moduleName.'Override';

            if (class_exists($override, false)) {
                $r = self::$_INSTANCE[$moduleName] = Adapter_ServiceLocator::get($override);
            }
        }

        if (!$r && class_exists($moduleName, false)) {
            $r = self::$_INSTANCE[$moduleName] = Adapter_ServiceLocator::get($moduleName);
        }

        // @codingStandardsIgnoreStart
        if (Module::$_log_modules_perfs) {
            // @codingStandardsIgnoreEnd
            $timeEnd = microtime(true);
            $memoryEnd = memory_get_usage(true);

            Db::getInstance()->execute(
                '
			INSERT INTO '._DB_PREFIX_.'modules_perfs (session, module, method, time_start, time_end, memory_start, memory_end)
			VALUES ('.(int) Module::$_log_modules_perfs_session.', "'.pSQL($moduleName).'", "__construct", "'.pSQL($timeStart).'", "'.pSQL($timeEnd).'", '.(int) $memoryStart.', '.(int) $memoryEnd.')'
            );
        }

        return $r;
    }

    /**
     * Load the available list of upgrade of a specified module
     * with an associated version
     *
     * @param $moduleName
     * @param $moduleVersion
     * @param $registeredVersion
     *
     * @return bool to know directly if any files have been found
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function loadUpgradeVersionList($moduleName, $moduleVersion, $registeredVersion)
    {
        $list = [];

        $upgradePath = _PS_MODULE_DIR_.$moduleName.'/upgrade/';

        // Check if folder exist and it could be read
        if (file_exists($upgradePath) && ($files = scandir($upgradePath))) {
            // Read each file name
            foreach ($files as $file) {
                if (!in_array($file, ['.', '..', '.svn', 'index.php']) && preg_match('/\.php$/', $file)) {
                    $tab = explode('-', $file);

                    if (!isset($tab[1])) {
                        continue;
                    }

                    $fileVersion = basename($tab[1], '.php');
                    // Compare version, if minor than actual, we need to upgrade the module
                    if (count($tab) == 2 &&
                        (Tools::version_compare($fileVersion, $moduleVersion, '<=') &&
                            Tools::version_compare($fileVersion, $registeredVersion, '>'))
                    ) {
                        $list[] = [
                            'file'             => $upgradePath.$file,
                            'version'          => $fileVersion,
                            'upgrade_function' => [
                                'upgrade_module_'.str_replace('.', '_', $fileVersion),
                                'upgradeModule'.str_replace('.', '', $fileVersion),
                            ],
                        ];
                    }
                }
            }
        }

        // No files upgrade, then upgrade succeed
        if (count($list) == 0) {
            // @codingStandardsIgnoreStart
            self::$modules_cache[$moduleName]['upgrade']['success'] = true;
            // @codingStandardsIgnoreEnd
            Module::upgradeModuleVersion($moduleName, $moduleVersion);
        }

        usort($list, 'ps_module_version_sort');

        // Set the list to module cache
        // @codingStandardsIgnoreStart
        self::$modules_cache[$moduleName]['upgrade']['upgrade_file_left'] = $list;
        self::$modules_cache[$moduleName]['upgrade']['available_upgrade'] = count($list);
        // @codingStandardsIgnoreEnd

        return (bool) count($list);
    }

    /**
     * Return the status of the upgraded module
     *
     * @param string $moduleName
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getUpgradeStatus($moduleName)
    {
        // @codingStandardsIgnoreStart
        return (isset(self::$modules_cache[$moduleName]) &&
            self::$modules_cache[$moduleName]['upgrade']['success']);
        // @codingStandardsIgnoreEnd
    }

    /**
     * This function enable module $name. If an $name is an array,
     * this will enable all of them
     *
     * @param array|string $name
     *
     * @return true if succeed
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function enableByName($name)
    {
        // If $name is not an array, we set it as an array
        if (!is_array($name)) {
            $name = [$name];
        }
        $res = true;
        // Enable each module
        foreach ($name as $n) {
            if (Validate::isModuleName($n)) {
                $res &= Module::getInstanceByName($n)->enable();
            }
        }

        return $res;
    }

    /**
     * This function disable module $name. If an $name is an array,
     * this will disable all of them
     *
     * @param array|string $name
     *
     * @return true if succeed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function disableByName($name)
    {
        // If $name is not an array, we set it as an array
        if (!is_array($name)) {
            $name = [$name];
        }
        $res = true;
        // Disable each module
        foreach ($name as $n) {
            if (Validate::isModuleName($n)) {
                Module::getInstanceByName($n)->disable();
            }
        }

        return $res;
    }

    /**
     * This function is used to determine the module name
     * of an AdminTab which belongs to a module, in order to keep translation
     * related to a module in its directory (instead of $_LANGADM)
     *
     * @param mixed $currentClass the
     *
     * @return bool|string if the class belongs to a module, will return the module name. Otherwise, return false.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getModuleNameFromClass($currentClass)
    {
        // Module can now define AdminTab keeping the module translations method,
        // i.e. in modules/[module name]/[iso_code].php
        if (!isset(self::$classInModule[$currentClass]) && class_exists($currentClass)) {
            global $_MODULES;
            $_MODULE = [];
            $reflectionClass = new ReflectionClass($currentClass);
            $filePath = realpath($reflectionClass->getFileName());
            $realpathModuleDir = realpath(_PS_MODULE_DIR_);
            if (substr(realpath($filePath), 0, strlen($realpathModuleDir)) == $realpathModuleDir) {
                // For controllers in module/controllers path
                if (basename(dirname(dirname($filePath))) == 'controllers') {
                    self::$classInModule[$currentClass] = basename(dirname(dirname(dirname($filePath))));
                } else {
                    // For old AdminTab controllers
                    self::$classInModule[$currentClass] = substr(dirname($filePath), strlen($realpathModuleDir) + 1);
                }

                $file = _PS_MODULE_DIR_.self::$classInModule[$currentClass].'/'.Context::getContext()->language->iso_code.'.php';
                if (file_exists($file) && include_once($file)) {
                    $_MODULES = !empty($_MODULES) ? array_merge($_MODULES, $_MODULE) : $_MODULE;
                }
            } else {
                self::$classInModule[$currentClass] = false;
            }
        }

        // return name of the module, or false
        return self::$classInModule[$currentClass];
    }

    /**
     * Return an instance of the specified module
     *
     * @param int $idModule Module ID
     *
     * @return false|Module instance
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getInstanceById($idModule)
    {
        static $id2name = null;

        if (is_null($id2name)) {
            $id2name = [];
            $sql = 'SELECT `id_module`, `name` FROM `'._DB_PREFIX_.'module`';
            if ($results = Db::getInstance()->executeS($sql)) {
                foreach ($results as $row) {
                    $id2name[$row['id_module']] = $row['name'];
                }
            }
        }

        if (isset($id2name[$idModule])) {
            return Module::getInstanceByName($id2name[$idModule]);
        }

        return false;
    }

    /**
     * @param string $module
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getModuleName($module)
    {
        $iso = substr(Context::getContext()->language->iso_code, 0, 2);

        // Config file
        $configFile = _PS_MODULE_DIR_.$module.'/config_'.$iso.'.xml';
        // For "en" iso code, we keep the default config.xml name
        if ($iso == 'en' || !file_exists($configFile)) {
            $configFile = _PS_MODULE_DIR_.$module.'/config.xml';
            if (!file_exists($configFile)) {
                return 'Module '.ucfirst($module);
            }
        }

        // Load config.xml
        libxml_use_internal_errors(true);
        $xmlModule = @simplexml_load_file($configFile);
        if (!$xmlModule) {
            return 'Module '.ucfirst($module);
        }
        if (!empty(libxml_get_errors())) {
            libxml_clear_errors();

            return 'Module '.ucfirst($module);
        }
        libxml_clear_errors();

        // Find translations
        global $_MODULES;
        $file = _PS_MODULE_DIR_.$module.'/'.Context::getContext()->language->iso_code.'.php';
        if (file_exists($file) && include_once($file)) {
            if (isset($_MODULE) && is_array($_MODULE)) {
                $_MODULES = !empty($_MODULES) ? array_merge($_MODULES, $_MODULE) : $_MODULE;
            }
        }

        // Return Name
        return Translate::getModuleTranslation((string) $xmlModule->name, Module::configXmlStringFormat($xmlModule->displayName), (string) $xmlModule->name);
    }

    /**
     * @param string $string
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function configXmlStringFormat($string)
    {
        return Tools::htmlentitiesDecodeUTF8($string);
    }

    /**
     * Return available modules
     *
     * @param bool $useConfig in order to use config.xml file in module dir
     *
     * @return array Modules
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getModulesOnDisk($useConfig = false, $loggedOnAddons = false, $idEmployee = false)
    {
        global $_MODULES;

        // Init var
        $moduleList = [];
        $moduleNameList = [];
        $modulesNameToCursor = [];
        $errors = [];

        // Get modules directory list and memory limit
        $modulesDir = Module::getModulesDirOnDisk();

        $modulesInstalled = [];
        $result = Db::getInstance()->executeS(
            '
		SELECT m.name, m.version, mp.interest, module_shop.enable_device
		FROM `'._DB_PREFIX_.'module` m
		'.Shop::addSqlAssociation('module', 'm').'
		LEFT JOIN `'._DB_PREFIX_.'module_preference` mp ON (mp.`module` = m.`name` AND mp.`id_employee` = '.(int) $idEmployee.')'
        );
        foreach ($result as $row) {
            $modulesInstalled[$row['name']] = $row;
        }

        foreach ($modulesDir as $module) {
            if (Module::useTooMuchMemory()) {
                $errors[] = Tools::displayError('All modules cannot be loaded due to memory limit restrictions, please increase your memory_limit value on your server configuration');
                break;
            }

            $iso = substr(Context::getContext()->language->iso_code, 0, 2);

            // Check if config.xml module file exists and if it's not outdated

            if ($iso == 'en') {
                $configFile = _PS_MODULE_DIR_.$module.'/config.xml';
            } else {
                $configFile = _PS_MODULE_DIR_.$module.'/config_'.$iso.'.xml';
            }

            $xmlExist = (file_exists($configFile));
            $needNewConfigFile = $xmlExist ? (@filemtime($configFile) < @filemtime(_PS_MODULE_DIR_.$module.'/'.$module.'.php')) : true;

            // If config.xml exists and that the use config flag is at true
            if ($useConfig && $xmlExist && !$needNewConfigFile) {
                // Load config.xml
                libxml_use_internal_errors(true);
                $xmlModule = @simplexml_load_file($configFile);
                if (!$xmlModule) {
                    $errors[] = Tools::displayError(sprintf('%1s could not be loaded.', $configFile));
                    break;
                }
                foreach (libxml_get_errors() as $error) {
                    $errors[] = '['.$module.'] '.Tools::displayError('Error found in config file:').' '.htmlentities($error->message);
                }
                libxml_clear_errors();

                // If no errors in Xml, no need instand and no need new config.xml file, we load only translations
                if (!count($errors) && (int) $xmlModule->need_instance == 0) {
                    $file = _PS_MODULE_DIR_.$module.'/'.Context::getContext()->language->iso_code.'.php';
                    if (file_exists($file) && include_once($file)) {
                        if (isset($_MODULE) && is_array($_MODULE)) {
                            $_MODULES = !empty($_MODULES) ? array_merge($_MODULES, $_MODULE) : $_MODULE;
                        }
                    }

                    $item = new stdClass();
                    $item->id = 0;
                    $item->warning = '';

                    foreach ($xmlModule as $k => $v) {
                        $item->$k = (string) $v;
                    }

                    $item->displayName = stripslashes(Translate::getModuleTranslation((string) $xmlModule->name, Module::configXmlStringFormat($xmlModule->displayName), (string) $xmlModule->name));
                    $item->description = stripslashes(Translate::getModuleTranslation((string) $xmlModule->name, Module::configXmlStringFormat($xmlModule->description), (string) $xmlModule->name));
                    $item->author = stripslashes(Translate::getModuleTranslation((string) $xmlModule->name, Module::configXmlStringFormat($xmlModule->author), (string) $xmlModule->name));
                    $item->author_uri = (isset($xmlModule->author_uri) && $xmlModule->author_uri) ? stripslashes($xmlModule->author_uri) : false;

                    if (isset($xmlModule->confirmUninstall)) {
                        $item->confirmUninstall = Translate::getModuleTranslation((string) $xmlModule->name, html_entity_decode(Module::configXmlStringFormat($xmlModule->confirmUninstall)), (string) $xmlModule->name);
                    }

                    $item->active = 0;
                    $item->onclick_option = false;
                    $item->trusted = Module::isModuleTrusted($item->name);

                    $moduleList[] = $item;

                    $moduleNameList[] = '\''.pSQL($item->name).'\'';
                    $modulesNameToCursor[Tools::strtolower(strval($item->name))] = $item;
                }
            }

            // If use config flag is at false or config.xml does not exist OR need instance OR need a new config.xml file
            if (!$useConfig || !$xmlExist || (isset($xmlModule->need_instance) && (int) $xmlModule->need_instance == 1) || $needNewConfigFile) {
                // If class does not exists, we include the file
                if (!class_exists($module, false)) {
                    // Get content from php file
                    $filePath = _PS_MODULE_DIR_.$module.'/'.$module.'.php';
                    $file = trim(file_get_contents(_PS_MODULE_DIR_.$module.'/'.$module.'.php'));

                    if (substr($file, 0, 5) == '<?php') {
                        $file = substr($file, 5);
                    }

                    if (substr($file, -2) == '?>') {
                        $file = substr($file, 0, -2);
                    }

                    $file = preg_replace('/\n[\s\t]*?use\s.*?;/', '', $file);

                    // If (false) is a trick to not load the class with "eval".
                    // This way require_once will works correctly
                    if (eval('if (false){	'.$file."\n".' }') !== false) {
                        require_once(_PS_MODULE_DIR_.$module.'/'.$module.'.php');
                    } else {
                        $errors[] = sprintf(Tools::displayError('%1$s (parse error in %2$s)'), $module, substr($filePath, strlen(_PS_ROOT_DIR_)));
                    }
                }

                // If class exists, we just instanciate it
                if (class_exists($module, false)) {
                    $tmpModule = Adapter_ServiceLocator::get($module);

                    $item = new stdClass();
                    $item->id = $tmpModule->id;
                    $item->warning = $tmpModule->warning;
                    $item->name = $tmpModule->name;
                    $item->version = $tmpModule->version;
                    $item->tab = $tmpModule->tab;
                    $item->displayName = $tmpModule->displayName;
                    $item->description = stripslashes($tmpModule->description);
                    $item->author = $tmpModule->author;
                    $item->author_uri = (isset($tmpModule->author_uri) && $tmpModule->author_uri) ? $tmpModule->author_uri : false;
                    $item->limited_countries = $tmpModule->limited_countries;
                    $item->parent_class = get_parent_class($module);
                    $item->is_configurable = $tmpModule->is_configurable = method_exists($tmpModule, 'getContent') ? 1 : 0;
                    $item->need_instance = isset($tmpModule->need_instance) ? $tmpModule->need_instance : 0;
                    $item->active = $tmpModule->active;
                    $item->trusted = Module::isModuleTrusted($tmpModule->name);
                    $item->currencies = isset($tmpModule->currencies) ? $tmpModule->currencies : null;
                    $item->currencies_mode = isset($tmpModule->currencies_mode) ? $tmpModule->currencies_mode : null;
                    $item->confirmUninstall = isset($tmpModule->confirmUninstall) ? html_entity_decode($tmpModule->confirmUninstall) : null;
                    $item->description_full = stripslashes($tmpModule->description_full);
                    $item->additional_description = isset($tmpModule->additional_description) ? stripslashes($tmpModule->additional_description) : null;
                    $item->compatibility = isset($tmpModule->compatibility) ? (array) $tmpModule->compatibility : null;
                    $item->nb_rates = isset($tmpModule->nb_rates) ? (array) $tmpModule->nb_rates : null;
                    $item->avg_rate = isset($tmpModule->avg_rate) ? (array) $tmpModule->avg_rate : null;
                    $item->badges = isset($tmpModule->badges) ? (array) $tmpModule->badges : null;
                    $item->url = isset($tmpModule->url) ? $tmpModule->url : null;
                    $item->onclick_option = method_exists($module, 'onclickOption') ? true : false;

                    if ($item->onclick_option) {
                        $href = Context::getContext()->link->getAdminLink('Module', true).'&module_name='.$tmpModule->name.'&tab_module='.$tmpModule->tab;
                        $item->onclick_option_content = [];
                        $optionTab = ['desactive', 'reset', 'configure', 'delete'];

                        foreach ($optionTab as $opt) {
                            $item->onclick_option_content[$opt] = $tmpModule->onclickOption($opt, $href);
                        }
                    }

                    $moduleList[] = $item;

                    if (!$xmlExist || $needNewConfigFile) {
                        // @codingStandardsIgnoreStart
                        self::$_generate_config_xml_mode = true;
                        $tmpModule->_generateConfigXml();
                        self::$_generate_config_xml_mode = false;
                        // @codingStandardsIgnoreEnd
                    }

                    unset($tmpModule);
                } else {
                    $errors[] = sprintf(Tools::displayError('%1$s (class missing in %2$s)'), $module, substr($filePath, strlen(_PS_ROOT_DIR_)));
                }
            }
        }

        // Get modules information from database
        if (!empty($moduleNameList)) {
            $list = Shop::getContextListShopID();
            $sql = 'SELECT m.id_module, m.name, (
						SELECT COUNT(*) FROM '._DB_PREFIX_.'module_shop ms WHERE m.id_module = ms.id_module AND ms.id_shop IN ('.implode(',', $list).')
					) as total
					FROM '._DB_PREFIX_.'module m
					WHERE LOWER(m.name) IN ('.Tools::strtolower(implode(',', $moduleNameList)).')';
            $results = Db::getInstance()->executeS($sql);

            foreach ($results as $result) {
                if (isset($modulesNameToCursor[Tools::strtolower($result['name'])])) {
                    $moduleCursor = $modulesNameToCursor[Tools::strtolower($result['name'])];
                    $moduleCursor->id = (int) $result['id_module'];
                    $moduleCursor->active = ($result['total'] == count($list)) ? 1 : 0;
                }
            }
        }

        // Get Default Country Modules and customer module
        $filesList = [];
        foreach ($filesList as $f) {
            if (file_exists($f['file']) && ($f['loggedOnAddons'] == 0 || $loggedOnAddons)) {
                if (Module::useTooMuchMemory()) {
                    $errors[] = Tools::displayError('All modules cannot be loaded due to memory limit restrictions, please increase your memory_limit value on your server configuration');
                    break;
                }

                $guzzle = new \GuzzleHttp\Client(['http_errors' => false]);
                $file = $f['file'];
                try {
                    $content = (string) $guzzle->get($file)->getBody();
                } catch (Exception $e) {
                    $content = '';
                }
                $xml = @simplexml_load_string($content, null, LIBXML_NOCDATA);

                if ($xml && isset($xml->module)) {
                    foreach ($xml->module as $modaddons) {
                        $flagFound = 0;

                        foreach ($moduleList as $k => &$m) {
                            if (Tools::strtolower($m->name) == Tools::strtolower($modaddons->name) && !isset($m->available_on_addons)) {
                                $flagFound = 1;
                                if ($m->version != $modaddons->version && version_compare($m->version, $modaddons->version) === -1) {
                                    $moduleList[$k]->version_addons = $modaddons->version;
                                }
                            }
                        }

                        if ($flagFound == 0) {
                            $item = new stdClass();
                            $item->id = 0;
                            $item->warning = '';
                            $item->type = strip_tags((string) $f['type']);
                            $item->name = strip_tags((string) $modaddons->name);
                            $item->version = strip_tags((string) $modaddons->version);
                            $item->tab = strip_tags((string) $modaddons->tab);
                            $item->displayName = strip_tags((string) $modaddons->displayName);
                            $item->description = stripslashes(strip_tags((string) $modaddons->description));
                            $item->description_full = stripslashes(strip_tags((string) $modaddons->description_full));
                            $item->author = strip_tags((string) $modaddons->author);
                            $item->limited_countries = [];
                            $item->parent_class = '';
                            $item->onclick_option = false;
                            $item->is_configurable = 0;
                            $item->need_instance = 0;
                            $item->not_on_disk = 1;
                            $item->available_on_addons = 1;
                            $item->trusted = true;
                            $item->active = 0;
                            $item->description_full = stripslashes($modaddons->description_full);
                            $item->additional_description = isset($modaddons->additional_description) ? stripslashes($modaddons->additional_description) : null;
                            $item->compatibility = isset($modaddons->compatibility) ? (array) $modaddons->compatibility : null;
                            $item->nb_rates = isset($modaddons->nb_rates) ? (array) $modaddons->nb_rates : null;
                            $item->avg_rate = isset($modaddons->avg_rate) ? (array) $modaddons->avg_rate : null;
                            $item->badges = isset($modaddons->badges) ? (array) $modaddons->badges : null;
                            $item->url = isset($modaddons->url) ? $modaddons->url : null;

                            if (isset($modaddons->img)) {
                                if (!file_exists(_PS_TMP_IMG_DIR_.md5((int) $modaddons->id.'-'.$modaddons->name).'.jpg')) {
                                    $guzzle = new \GuzzleHttp\Client(['http_errors' => false]);
                                    try {
                                        $contents = (string) $guzzle->get($modaddons->img)->getBody();
                                    } catch (Exception $e) {
                                        $contents = null;
                                    }
                                    if (!file_put_contents(_PS_TMP_IMG_DIR_.md5((int) $modaddons->id.'-'.$modaddons->name).'.jpg', $contents)) {
                                        copy(_PS_IMG_DIR_.'404.gif', _PS_TMP_IMG_DIR_.md5((int) $modaddons->id.'-'.$modaddons->name).'.jpg');
                                    }
                                }

                                if (file_exists(_PS_TMP_IMG_DIR_.md5((int) $modaddons->id.'-'.$modaddons->name).'.jpg')) {
                                    $item->image = '../img/tmp/'.md5((int) $modaddons->id.'-'.$modaddons->name).'.jpg';
                                }
                            }

                            if ($item->type == 'addonsMustHave') {
                                $item->addons_buy_url = strip_tags((string) $modaddons->url);
                                $prices = (array) $modaddons->price;
                                $idDefaultCurrency = Configuration::get('PS_CURRENCY_DEFAULT');

                                foreach ($prices as $currency => $price) {
                                    if ($idCurrency = Currency::getIdByIsoCode($currency)) {
                                        $item->price = (float) $price;
                                        $item->id_currency = (int) $idCurrency;

                                        if ($idDefaultCurrency == $idCurrency) {
                                            break;
                                        }
                                    }
                                }
                            }

                            $moduleList[$modaddons->id.'-'.$item->name] = $item;
                        }
                    }
                }
            }
        }

        foreach ($moduleList as $key => &$module) {
            if (isset($modulesInstalled[$module->name])) {
                $module->installed = true;
                $module->database_version = $modulesInstalled[$module->name]['version'];
                $module->interest = $modulesInstalled[$module->name]['interest'];
                $module->enable_device = $modulesInstalled[$module->name]['enable_device'];
            } else {
                $module->installed = false;
                $module->database_version = 0;
                $module->interest = 0;
            }
        }

        usort($moduleList, create_function('$a,$b', 'return strnatcasecmp($a->displayName, $b->displayName);'));
        if ($errors) {
            if (!isset(Context::getContext()->controller) && !Context::getContext()->controller->controller_name) {
                echo '<div class="alert error"><h3>'.Tools::displayError('The following module(s) could not be loaded').':</h3><ol>';
                foreach ($errors as $error) {
                    echo '<li>'.$error.'</li>';
                }
                echo '</ol></div>';
            } else {
                foreach ($errors as $error) {
                    Context::getContext()->controller->errors[] = $error;
                }
            }
        }

        return $moduleList;
    }

    /**
     * Return modules directory list
     *
     * @return array Modules Directory List
     *
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getModulesDirOnDisk()
    {
        $moduleList = [];
        $modules = scandir(_PS_MODULE_DIR_);
        foreach ($modules as $name) {
            if (is_file(_PS_MODULE_DIR_.$name)) {
                continue;
            } elseif (is_dir(_PS_MODULE_DIR_.$name.DIRECTORY_SEPARATOR) && file_exists(_PS_MODULE_DIR_.$name.'/'.$name.'.php')) {
                if (!Validate::isModuleName($name)) {
                    throw new PrestaShopException(sprintf('Module %s is not a valid module name', $name));
                }
                $moduleList[] = $name;
            }
        }

        return $moduleList;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function useTooMuchMemory()
    {
        $memoryLimit = Tools::getMemoryLimit();
        if (function_exists('memory_get_usage') && $memoryLimit != '-1') {
            $currentMemory = memory_get_usage(true);
            $memoryThreshold = (int) max($memoryLimit * 0.15, Tools::isX86_64arch() ? 4194304 : 2097152);
            $memoryLeft = $memoryLimit - $currentMemory;

            if ($memoryLeft <= $memoryThreshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return if the module is provided by addons.prestashop.com or not
     *
     * @param string $moduleName
     *
     * @return int
     * @internal   param string $name The module name (the folder name)
     * @internal   param string $key The key provided by addons
     *
     * @deprecated 1.0.0
     */
    final public static function isModuleTrusted($moduleName)
    {
        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNativeModuleList()
    {
        return self::getNonNativeModuleList();
    }

    /**
     * Return non native module
     *
     * @return array Modules
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNonNativeModuleList()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM `'._DB_PREFIX_.'module`');
    }

    /**
     * Return installed modules
     *
     * @param int $position Take only positionnables modules
     *
     * @return array Modules
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getModulesInstalled($position = 0)
    {
        $sql = 'SELECT m.* FROM `'._DB_PREFIX_.'module` m ';
        if ($position) {
            $sql .= 'LEFT JOIN `'._DB_PREFIX_.'hook_module` hm ON m.`id_module` = hm.`id_module`
				 LEFT JOIN `'._DB_PREFIX_.'hook` k ON hm.`id_hook` = k.`id_hook`
				 WHERE k.`position` = 1
				 GROUP BY m.id_module';
        }

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Generate XML files for trusted and untrusted modules
     *
     * @return true
     *
     * @deprecated 1.0.0
     */
    final public static function generateTrustedXml()
    {
        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * Create the Addons API call from the module name only
     *
     * @param string $moduleName
     *
     * @return bool Returns if the module is trusted by addons.prestashop.com
     *
     * @deprecated 1.0.0
     */
    final public static function checkModuleFromAddonsApi($moduleName)
    {
        return false;
    }

    /**
     * Execute modules for specified hook
     *
     * @param string   $hookName Hook Name
     * @param array    $hookArgs Parameters for the functions
     * @param int|null $idModule
     *
     * @return string modules output
     *
     * @deprecated 2.0.0
     */
    public static function hookExec($hookName, $hookArgs = [], $idModule = null)
    {
        Tools::displayAsDeprecated();

        return Hook::exec($hookName, $hookArgs, $idModule);
    }

    /**
     * @deprecated 2.0.0
     * @return string
     * @throws PrestaShopException
     */
    public static function hookExecPayment()
    {
        Tools::displayAsDeprecated();

        return Hook::exec('displayPayment');
    }

    /**
     * Pre call
     *
     * @param string $moduleName
     *
     * @return bool
     */
    public static function preCall($moduleName)
    {
        return true;
    }

    /**
     * @deprecated 2.0.0
     */
    public static function getPaypalIgnore()
    {
        Tools::displayAsDeprecated();
    }

    /**
     * Returns the list of the payment module associated to the current customer
     *
     * @see     PaymentModule::getInstalledPaymentModules() if you don't care about the context
     *
     * @return array module informations
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getPaymentModules()
    {
        $context = Context::getContext();
        if (isset($context->cart)) {
            $billing = new Address((int) $context->cart->id_address_invoice);
        }

        $useGroups = Group::isFeatureActive();

        $frontend = true;
        $groups = [];
        if (isset($context->employee)) {
            $frontend = false;
        } elseif (isset($context->customer) && $useGroups) {
            $groups = $context->customer->getGroups();
            if (!count($groups)) {
                $groups = [Configuration::get('PS_UNIDENTIFIED_GROUP')];
            }
        }

        $hookPayment = 'Payment';
        if (Db::getInstance()->getValue('SELECT `id_hook` FROM `'._DB_PREFIX_.'hook` WHERE `name` = \'displayPayment\'')) {
            $hookPayment = 'displayPayment';
        }

        $list = Shop::getContextListShopID();

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT DISTINCT m.`id_module`, h.`id_hook`, m.`name`, hm.`position`
		FROM `'._DB_PREFIX_.'module` m
		'.($frontend ? 'LEFT JOIN `'._DB_PREFIX_.'module_country` mc ON (m.`id_module` = mc.`id_module` AND mc.id_shop = '.(int) $context->shop->id.')' : '').'
		'.($frontend && $useGroups ? 'INNER JOIN `'._DB_PREFIX_.'module_group` mg ON (m.`id_module` = mg.`id_module` AND mg.id_shop = '.(int) $context->shop->id.')' : '').'
		'.($frontend && isset($context->customer) && $useGroups ? 'INNER JOIN `'._DB_PREFIX_.'customer_group` cg on (cg.`id_group` = mg.`id_group`AND cg.`id_customer` = '.(int) $context->customer->id.')' : '').'
		LEFT JOIN `'._DB_PREFIX_.'hook_module` hm ON hm.`id_module` = m.`id_module`
		LEFT JOIN `'._DB_PREFIX_.'hook` h ON hm.`id_hook` = h.`id_hook`
		WHERE h.`name` = \''.pSQL($hookPayment).'\'
		'.(isset($billing) && $frontend ? 'AND mc.id_country = '.(int) $billing->id_country : '').'
		AND (SELECT COUNT(*) FROM '._DB_PREFIX_.'module_shop ms WHERE ms.id_module = m.id_module AND ms.id_shop IN('.implode(', ', $list).')) = '.count($list).'
		AND hm.id_shop IN('.implode(', ', $list).')
		'.((count($groups) && $frontend && $useGroups) ? 'AND (mg.`id_group` IN ('.implode(', ', $groups).'))' : '').'
		GROUP BY hm.id_hook, hm.id_module
		ORDER BY hm.`position`, m.`name` DESC'
        );
    }

    /**
     * @param string $name
     * @param string $string
     * @param string $source
     *
     * @return string
     *
     * @deprecated 2.0.0 Use Translate::getModuleTranslation()
     */
    public static function findTranslation($name, $string, $source)
    {
        return Translate::getModuleTranslation($name, $string, $source);
    }

    /**
     * @param $moduleName
     *
     * @return bool|null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isEnabled($moduleName)
    {
        if (!Cache::isStored('Module::isEnabled'.$moduleName)) {
            $active = false;
            $idModule = Module::getModuleIdByName($moduleName);
            if (Db::getInstance()->getValue('SELECT `id_module` FROM `'._DB_PREFIX_.'module_shop` WHERE `id_module` = '.(int) $idModule.' AND `id_shop` = '.(int) Context::getContext()->shop->id)) {
                $active = true;
            }
            Cache::store('Module::isEnabled'.$moduleName, (bool) $active);

            return (bool) $active;
        }

        return Cache::retrieve('Module::isEnabled'.$moduleName);
    }

    /**
     * Get Unauthorized modules for a client group
     *
     * @param int $groupId
     *
     * @return array|null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAuthorizedModules($groupId)
    {
        return Db::getInstance()->executeS(
            '
		SELECT m.`id_module`, m.`name` FROM `'._DB_PREFIX_.'module_group` mg
		LEFT JOIN `'._DB_PREFIX_.'module` m ON (m.`id_module` = mg.`id_module`)
		WHERE mg.`id_group` = '.(int) $groupId
        );
    }

    /**
     * Insert module into datable
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function install()
    {
        Hook::exec('actionModuleInstallBefore', ['object' => $this]);
        // Check module name validation
        if (!Validate::isModuleName($this->name)) {
            $this->_errors[] = Tools::displayError('Unable to install the module (Module name is not valid).');

            return false;
        }

        // Check PS version compliancy
        if (!$this->checkCompliancy()) {
            $this->_errors[] = Tools::displayError('The version of your module is not compliant with your thirty bees version.');

            return false;
        }

        // Check module dependencies
        if (count($this->dependencies) > 0) {
            foreach ($this->dependencies as $dependency) {
                if (!Db::getInstance()->getRow('SELECT `id_module` FROM `'._DB_PREFIX_.'module` WHERE LOWER(`name`) = \''.pSQL(Tools::strtolower($dependency)).'\'')) {
                    $error = Tools::displayError('Before installing this module, you have to install this/these module(s) first:').'<br />';
                    foreach ($this->dependencies as $d) {
                        $error .= '- '.$d.'<br />';
                    }
                    $this->_errors[] = $error;

                    return false;
                }
            }
        }

        // Check if module is installed
        $result = Module::isInstalled($this->name);
        if ($result) {
            $this->_errors[] = Tools::displayError('This module has already been installed.');

            return false;
        }

        // Invalidate opcache
        if (function_exists('opcache_invalidate')) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_PS_MODULE_DIR_.$this->name)) as $file) {
                /** @var SplFileInfo $file */
                if (substr($file->getFilename(), -4) !== '.php' || $file->isLink()) {
                    continue;
                }

                opcache_invalidate($file->getPathname());
            }
        }

        // Install overrides
        try {
            $this->installOverrides();
        } catch (Exception $e) {
            $this->_errors[] = sprintf(Tools::displayError('Unable to install override: %s'), $e->getMessage());
            $this->uninstallOverrides();

            return false;
        }

        if (!$this->installControllers()) {
            return false;
        }

        // Install module and retrieve the installation id
        $result = Db::getInstance()->insert($this->table, ['name' => $this->name, 'active' => 1, 'version' => $this->version]);
        if (!$result) {
            $this->_errors[] = Tools::displayError('Technical error: PrestaShop could not install this module.');

            return false;
        }
        $this->id = Db::getInstance()->Insert_ID();

        Cache::clean('Module::isInstalled'.$this->name);

        // Enable the module for current shops in context
        $this->enable();

        // Permissions management
        Db::getInstance()->execute(
            '
			INSERT INTO `'._DB_PREFIX_.'module_access` (`id_profile`, `id_module`, `view`, `configure`, `uninstall`) (
				SELECT id_profile, '.(int) $this->id.', 1, 1, 1
				FROM '._DB_PREFIX_.'access a
				WHERE id_tab = (
					SELECT `id_tab` FROM '._DB_PREFIX_.'tab
					WHERE class_name = \'AdminModules\' LIMIT 1)
				AND a.`view` = 1)'
        );

        Db::getInstance()->execute(
            '
			INSERT INTO `'._DB_PREFIX_.'module_access` (`id_profile`, `id_module`, `view`, `configure`, `uninstall`) (
				SELECT id_profile, '.(int) $this->id.', 1, 0, 0
				FROM '._DB_PREFIX_.'access a
				WHERE id_tab = (
					SELECT `id_tab` FROM '._DB_PREFIX_.'tab
					WHERE class_name = \'AdminModules\' LIMIT 1)
				AND a.`view` = 0)'
        );

        // Adding Restrictions for client groups
        Group::addRestrictionsForModule($this->id, Shop::getShops(true, null, true));
        Hook::exec('actionModuleInstallAfter', ['object' => $this]);

        // @codingStandardsIgnoreStart
        if (Module::$update_translations_after_install) {
            $this->updateModuleTranslations();
        }
        // @codingStandardsIgnoreEnd

        UrlRewrite::regenerateUrlRewrite(UrlRewrite::ENTITY_PAGE);

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function checkCompliancy()
    {
        if (version_compare(_PS_VERSION_, $this->ps_versions_compliancy['min'], '<') || version_compare(_PS_VERSION_, $this->ps_versions_compliancy['max'], '>')) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $moduleName
     *
     * @return bool|null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isInstalled($moduleName)
    {
        if (!Cache::isStored('Module::isInstalled'.$moduleName)) {
            $idModule = Module::getModuleIdByName($moduleName);
            Cache::store('Module::isInstalled'.$moduleName, (bool) $idModule);

            return (bool) $idModule;
        }

        return Cache::retrieve('Module::isInstalled'.$moduleName);
    }

    /**
     * Get ID module by name
     *
     * @param string $name
     *
     * @return int Module ID
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getModuleIdByName($name)
    {
        $cacheId = 'Module::getModuleIdByName_'.pSQL($name);
        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::getInstance()->getValue('SELECT `id_module` FROM `'._DB_PREFIX_.'module` WHERE `name` = "'.pSQL($name).'"');
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Install overrides files for the module
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function installOverrides()
    {
        if (!is_dir($this->getLocalPath().'override')) {
            return true;
        }

        $result = true;
        foreach (Tools::scandir($this->getLocalPath().'override', 'php', '', true) as $file) {
            $class = basename($file, '.php');
            if (PrestaShopAutoload::getInstance()->getClassPath($class.'Core') || Module::getModuleIdByName($class)) {
                $result &= $this->addOverride($class);
            }
        }

        return $result;
    }

    /**
     * Get local path for module
     *
     * @since 1.0.0
     * @return string
     */
    public function getLocalPath()
    {
        return $this->local_path;
    }

    /**
     * Add all methods in a module override to the override class
     *
     * @param string $classname
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addOverride($classname)
    {
        $origPath = $path = PrestaShopAutoload::getInstance()->getClassPath($classname.'Core');
        if (!$path) {
            $path = 'modules'.DIRECTORY_SEPARATOR.$classname.DIRECTORY_SEPARATOR.$classname.'.php';
        }
        $pathOverride = $this->getLocalPath().'override'.DIRECTORY_SEPARATOR.$path;

        if (!file_exists($pathOverride)) {
            return false;
        } else {
            file_put_contents($pathOverride, preg_replace('#(\r\n|\r)#ism', "\n", file_get_contents($pathOverride)));
        }

        $patternEscapeCom = '#(^\s*?\/\/.*?\n|\/\*(?!\n\s+\* module:.*?\* date:.*?\* version:.*?\*\/).*?\*\/)#ism';
        // Check if there is already an override file, if not, we just need to copy the file
        if ($file = PrestaShopAutoload::getInstance()->getClassPath($classname)) {
            // Check if override file is writable
            $overridePath = _PS_ROOT_DIR_.'/'.$file;

            if ((!file_exists($overridePath) && !is_writable(dirname($overridePath))) || (file_exists($overridePath) && !is_writable($overridePath))) {
                throw new Exception(sprintf(Tools::displayError('file (%s) not writable'), $overridePath));
            }

            // Get a uniq id for the class, because you can override a class (or remove the override) twice in the same session and we need to avoid redeclaration
            do {
                $uniq = uniqid();
            } while (class_exists($classname.'OverrideOriginal_remove', false));

            // Make a reflection of the override class and the module override class
            $overrideFile = file($overridePath);
            if (empty($overrideFile)) {
                // class_index was out of sync, so we just create a new override on the fly
                $overrideFile = array(
                    "<?php\n",
                    "class {$classname} extends {$classname}Core\n",
                    "{\n",
                    "}\n",
                );
            }
            $overrideFile = array_diff($overrideFile, ["\n"]);
            eval(preg_replace(['#^\s*<\?(?:php)?#', '#class\s+'.$classname.'\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?#i'], [' ', 'class '.$classname.'OverrideOriginal'.$uniq], implode('', $overrideFile)));
            $overrideClass = new ReflectionClass($classname.'OverrideOriginal'.$uniq);

            $moduleFile = file($pathOverride);
            $moduleFile = array_diff($moduleFile, ["\n"]);
            eval(preg_replace(['#^\s*<\?(?:php)?#', '#class\s+'.$classname.'(\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?)?#i'], [' ', 'class '.$classname.'Override'.$uniq], implode('', $moduleFile)));
            $moduleClass = new ReflectionClass($classname.'Override'.$uniq);

            // Check if none of the methods already exists in the override class
            foreach ($moduleClass->getMethods() as $method) {
                if ($overrideClass->hasMethod($method->getName())) {
                    $method_override = $overrideClass->getMethod($method->getName());
                    if (preg_match('/module: (.*)/ism', $overrideFile[$method_override->getStartLine() - 5], $name) && preg_match('/date: (.*)/ism', $overrideFile[$method_override->getStartLine() - 4], $date) && preg_match('/version: ([0-9.]+)/ism', $overrideFile[$method_override->getStartLine() - 3], $version)) {
                        throw new Exception(sprintf(Tools::displayError('The method %1$s in the class %2$s is already overridden by the module %3$s version %4$s at %5$s.'), $method->getName(), $classname, $name[1], $version[1], $date[1]));
                    }
                    throw new Exception(sprintf(Tools::displayError('The method %1$s in the class %2$s is already overridden.'), $method->getName(), $classname));
                }

                $moduleFile = preg_replace('/((:?public|private|protected)\s+(static\s+)?function\s+(?:\b'.$method->getName().'\b))/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1", $moduleFile);
                if ($moduleFile === null) {
                    throw new Exception(sprintf(Tools::displayError('Failed to override method %1$s in class %2$s.'), $method->getName(), $classname));
                }
            }

            // Check if none of the properties already exists in the override class
            foreach ($moduleClass->getProperties() as $property) {
                if ($overrideClass->hasProperty($property->getName())) {
                    throw new Exception(sprintf(Tools::displayError('The property %1$s in the class %2$s is already defined.'), $property->getName(), $classname));
                }

                $moduleFile = preg_replace('/((?:public|private|protected)\s)\s*(static\s)?\s*(\$\b'.$property->getName().'\b)/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1$2$3", $moduleFile);
                if ($moduleFile === null) {
                    throw new Exception(sprintf(Tools::displayError('Failed to override property %1$s in class %2$s.'), $property->getName(), $classname));
                }
            }

            foreach ($moduleClass->getConstants() as $constant => $value) {
                if ($overrideClass->hasConstant($constant)) {
                    throw new Exception(sprintf(Tools::displayError('The constant %1$s in the class %2$s is already defined.'), $constant, $classname));
                }

                $moduleFile = preg_replace('/(const\s)\s*(\b'.$constant.'\b)/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1$2", $moduleFile);
                if ($moduleFile === null) {
                    throw new Exception(sprintf(Tools::displayError('Failed to override constant %1$s in class %2$s.'), $constant, $classname));
                }
            }

            // Insert the methods from module override in override
            $copyFrom = array_slice($moduleFile, $moduleClass->getStartLine() + 1, $moduleClass->getEndLine() - $moduleClass->getStartLine() - 2);
            array_splice($overrideFile, $overrideClass->getEndLine() - 1, 0, $copyFrom);
            $code = implode('', $overrideFile);

            file_put_contents($overridePath, preg_replace($patternEscapeCom, '', $code));
        } else {
            $overrideSrc = $pathOverride;

            $overrideDest = _PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'override'.DIRECTORY_SEPARATOR.$path;
            $dirName = dirname($overrideDest);

            if (!$origPath && !is_dir($dirName)) {
                $oldumask = umask(0000);
                @mkdir($dirName, 0777);
                umask($oldumask);
            }

            if (!is_writable($dirName)) {
                throw new Exception(sprintf(Tools::displayError('directory (%s) not writable'), $dirName));
            }
            $moduleFile = file($overrideSrc);
            $moduleFile = array_diff($moduleFile, ["\n"]);

            if ($origPath) {
                do {
                    $uniq = uniqid();
                } while (class_exists($classname.'OverrideOriginal_remove', false));
                eval(preg_replace(['#^\s*<\?(?:php)?#', '#class\s+'.$classname.'(\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?)?#i'], [' ', 'class '.$classname.'Override'.$uniq], implode('', $moduleFile)));
                $moduleClass = new ReflectionClass($classname.'Override'.$uniq);

                // For each method found in the override, prepend a comment with the module name and version
                foreach ($moduleClass->getMethods() as $method) {
                    $moduleFile = preg_replace('/((:?public|private|protected)\s+(static\s+)?function\s+(?:\b'.$method->getName().'\b))/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1", $moduleFile);
                    if ($moduleFile === null) {
                        throw new Exception(sprintf(Tools::displayError('Failed to override method %1$s in class %2$s.'), $method->getName(), $classname));
                    }
                }

                // Same loop for properties
                foreach ($moduleClass->getProperties() as $property) {
                    $moduleFile = preg_replace('/((?:public|private|protected)\s)\s*(static\s)?\s*(\$\b'.$property->getName().'\b)/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1$2$3", $moduleFile);
                    if ($moduleFile === null) {
                        throw new Exception(sprintf(Tools::displayError('Failed to override property %1$s in class %2$s.'), $property->getName(), $classname));
                    }
                }

                // Same loop for constants
                foreach ($moduleClass->getConstants() as $constant => $value) {
                    $moduleFile = preg_replace('/(const\s)\s*(\b'.$constant.'\b)/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1$2", $moduleFile);
                    if ($moduleFile === null) {
                        throw new Exception(sprintf(Tools::displayError('Failed to override constant %1$s in class %2$s.'), $constant, $classname));
                    }
                }
            }

            file_put_contents($overrideDest, preg_replace($patternEscapeCom, '', $moduleFile));

            // Invalidate opcache
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($overrideDest);
            }

            // Re-generate the class index
            Tools::generateIndex();
        }

        return true;
    }

    /**
     * Uninstall overrides files for the module
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function uninstallOverrides()
    {
        if (!is_dir($this->getLocalPath().'override')) {
            return true;
        }

        $result = true;
        foreach (Tools::scandir($this->getLocalPath().'override', 'php', '', true) as $file) {
            $class = basename($file, '.php');
            if (PrestaShopAutoload::getInstance()->getClassPath($class.'Core') || Module::getModuleIdByName($class)) {
                $result &= $this->removeOverride($class);
            }
        }

        return $result;
    }

    /**
     * Remove all methods in a module override from the override class
     *
     * @param string $classname
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function removeOverride($classname)
    {
        $origPath = $path = PrestaShopAutoload::getInstance()->getClassPath($classname.'Core');

        if ($origPath && !$file = PrestaShopAutoload::getInstance()->getClassPath($classname)) {
            return true;
        } elseif (!$origPath && Module::getModuleIdByName($classname)) {
            $path = 'modules'.DIRECTORY_SEPARATOR.$classname.DIRECTORY_SEPARATOR.$classname.'.php';
        }

        // Check if override file is writable
        if ($origPath) {
            $overridePath = _PS_ROOT_DIR_.'/'.$file;
        } else {
            $overridePath = _PS_OVERRIDE_DIR_.$path;
        }

        if (!is_file($overridePath) || !is_writable($overridePath)) {
            return false;
        }

        file_put_contents($overridePath, preg_replace('#(\r\n|\r)#ism', "\n", file_get_contents($overridePath)));

        if ($origPath) {
            // Get a uniq id for the class, because you can override a class (or remove the override) twice in the same session and we need to avoid redeclaration
            do {
                $uniq = uniqid();
            } while (class_exists($classname.'OverrideOriginal_remove', false));

            // Make a reflection of the override class and the module override class
            $overrideFile = file($overridePath);

            eval(preg_replace(['#^\s*<\?(?:php)?#', '#class\s+'.$classname.'\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?#i'], [' ', 'class '.$classname.'OverrideOriginal_remove'.$uniq], implode('', $overrideFile)));
            $overrideClass = new ReflectionClass($classname.'OverrideOriginal_remove'.$uniq);

            $moduleFile = file($this->getLocalPath().'override/'.$path);
            eval(preg_replace(['#^\s*<\?(?:php)?#', '#class\s+'.$classname.'(\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?)?#i'], [' ', 'class '.$classname.'Override_remove'.$uniq], implode('', $moduleFile)));
            $moduleClass = new ReflectionClass($classname.'Override_remove'.$uniq);

            // Remove methods from override file
            foreach ($moduleClass->getMethods() as $method) {
                if (!$overrideClass->hasMethod($method->getName())) {
                    continue;
                }

                $method = $overrideClass->getMethod($method->getName());
                $length = $method->getEndLine() - $method->getStartLine() + 1;

                $moduleMethod = $moduleClass->getMethod($method->getName());

                $overrideFileOrig = $overrideFile;

                $origContent = preg_replace('/\s/', '', implode('', array_splice($overrideFile, $method->getStartLine() - 1, $length, array_pad([], $length, '#--remove--#'))));
                $moduleContent = preg_replace('/\s/', '', implode('', array_splice($moduleFile, $moduleMethod->getStartLine() - 1, $length, array_pad([], $length, '#--remove--#'))));

                $replace = true;
                if (preg_match('/\* module: ('.$this->name.')/ism', $overrideFile[$method->getStartLine() - 5])) {
                    $overrideFile[$method->getStartLine() - 6] = $overrideFile[$method->getStartLine() - 5] = $overrideFile[$method->getStartLine() - 4] = $overrideFile[$method->getStartLine() - 3] = $overrideFile[$method->getStartLine() - 2] = '#--remove--#';
                    $replace = false;
                }

                if (md5($moduleContent) != md5($origContent) && $replace) {
                    $overrideFile = $overrideFileOrig;
                }
            }

            // Remove properties from override file
            foreach ($moduleClass->getProperties() as $property) {
                if (!$overrideClass->hasProperty($property->getName())) {
                    continue;
                }

                // Replace the declaration line by #--remove--#
                foreach ($overrideFile as $lineNumber => &$lineContent) {
                    if (preg_match('/(public|private|protected)\s+(static\s+)?(\$)?'.$property->getName().'/i', $lineContent)) {
                        if (preg_match('/\* module: ('.$this->name.')/ism', $overrideFile[$lineNumber - 4])) {
                            $overrideFile[$lineNumber - 5] = $overrideFile[$lineNumber - 4] = $overrideFile[$lineNumber - 3] = $overrideFile[$lineNumber - 2] = $overrideFile[$lineNumber - 1] = '#--remove--#';
                        }
                        $lineContent = '#--remove--#';
                        break;
                    }
                }
            }

            // Remove properties from override file
            foreach ($moduleClass->getConstants() as $constant => $value) {
                if (!$overrideClass->hasConstant($constant)) {
                    continue;
                }

                // Replace the declaration line by #--remove--#
                foreach ($overrideFile as $lineNumber => &$lineContent) {
                    if (preg_match('/(const)\s+(static\s+)?(\$)?'.$constant.'/i', $lineContent)) {
                        if (preg_match('/\* module: ('.$this->name.')/ism', $overrideFile[$lineNumber - 4])) {
                            $overrideFile[$lineNumber - 5] = $overrideFile[$lineNumber - 4] = $overrideFile[$lineNumber - 3] = $overrideFile[$lineNumber - 2] = $overrideFile[$lineNumber - 1] = '#--remove--#';
                        }
                        $lineContent = '#--remove--#';
                        break;
                    }
                }
            }

            $count = count($overrideFile);
            for ($i = 0; $i < $count; ++$i) {
                if (preg_match('/(^\s*\/\/.*)/i', $overrideFile[$i])) {
                    $overrideFile[$i] = '#--remove--#';
                } elseif (preg_match('/(^\s*\/\*)/i', $overrideFile[$i])) {
                    if (!preg_match('/(^\s*\* module:)/i', $overrideFile[$i + 1])
                        && !preg_match('/(^\s*\* date:)/i', $overrideFile[$i + 2])
                        && !preg_match('/(^\s*\* version:)/i', $overrideFile[$i + 3])
                        && !preg_match('/(^\s*\*\/)/i', $overrideFile[$i + 4])
                    ) {
                        for (; $overrideFile[$i] && !preg_match('/(.*?\*\/)/i', $overrideFile[$i]); ++$i) {
                            $overrideFile[$i] = '#--remove--#';
                        }
                        $overrideFile[$i] = '#--remove--#';
                    }
                }
            }

            // Rewrite nice code
            $code = '';
            foreach ($overrideFile as $line) {
                if ($line == '#--remove--#') {
                    continue;
                }

                $code .= $line;
            }

            $toDelete = preg_match('/<\?(?:php)?\s+(?:abstract|interface)?\s*?class\s+'.$classname.'\s+extends\s+'.$classname.'Core\s*?[{]\s*?[}]/ism', $code);
        }

        if (!isset($toDelete) || $toDelete) {
            unlink($overridePath);
        } else {
            file_put_contents($overridePath, $code);

            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($overridePath);
            }
        }

        // Re-generate the class index
        Tools::generateIndex();

        return true;
    }

    /**
     * Install module's controllers using public property $controllers
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function installControllers()
    {
        $themes = Theme::getThemes();
        $themeMetaValue = [];
        foreach ($this->controllers as $controller) {
            $page = 'module-'.$this->name.'-'.$controller;
            $result = Db::getInstance()->getValue('SELECT * FROM '._DB_PREFIX_.'meta WHERE page="'.pSQL($page).'"');
            if ((int) $result > 0) {
                continue;
            }

            $meta = new Meta();
            $meta->page = $page;
            $meta->configurable = 1;
            $meta->save();
            if ((int) $meta->id > 0) {
                foreach ($themes as $theme) {
                    /** @var Theme $theme */
                    $themeMetaValue[] = [
                        'id_theme'     => $theme->id,
                        'id_meta'      => $meta->id,
                        'left_column'  => (int) $theme->default_left_column,
                        'right_column' => (int) $theme->default_right_column,
                    ];
                }
            } else {
                $this->_errors[] = sprintf(Tools::displayError('Unable to install controller: %s'), $controller);
            }
        }
        if (count($themeMetaValue) > 0) {
            return Db::getInstance()->insert('theme_meta', $themeMetaValue);
        }

        return true;
    }

    /**
     * Activate current module.
     *
     * @param bool $forceAll If true, enable module for all shop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function enable($forceAll = false)
    {
        // Retrieve all shops where the module is enabled
        $list = Shop::getContextListShopID();
        if (!$this->id || !is_array($list)) {
            return false;
        }
        $sql = 'SELECT `id_shop` FROM `'._DB_PREFIX_.'module_shop`
				WHERE `id_module` = '.(int) $this->id.
            ((!$forceAll) ? ' AND `id_shop` IN('.implode(', ', $list).')' : '');

        // Store the results in an array
        $items = [];
        if ($results = Db::getInstance($sql)->executeS($sql)) {
            foreach ($results as $row) {
                $items[] = $row['id_shop'];
            }
        }

        // Enable module in the shop where it is not enabled yet
        foreach ($list as $id) {
            if (!in_array($id, $items)) {
                Db::getInstance()->insert(
                    'module_shop',
                    [
                        'id_module' => $this->id,
                        'id_shop'   => $id,
                    ]
                );
            }
        }

        return true;
    }

    /**
     * @return void
     *
     * @since   1.0.0
     *
     * @version 1.0.0 Initial version
     */
    public function updateModuleTranslations()
    {
        Language::updateModulesTranslations([$this->name]);
    }

    /**
     * Run the upgrade for a given module name and version
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function runUpgradeModule()
    {
        // @codingStandardsIgnoreStart
        $upgrade = &self::$modules_cache[$this->name]['upgrade'];
        // @codingStandardsIgnore
        foreach ($upgrade['upgrade_file_left'] as $num => $fileDetail) {
            foreach ($fileDetail['upgrade_function'] as $item) {
                if (function_exists($item)) {
                    $upgrade['success'] = false;
                    $upgrade['duplicate'] = true;
                    break 2;
                }
            }

            include($fileDetail['file']);

            // Call the upgrade function if defined
            $upgrade['success'] = false;
            foreach ($fileDetail['upgrade_function'] as $item) {
                if (function_exists($item)) {
                    $upgrade['success'] = $item($this);
                }
            }

            // Set detail when an upgrade succeed or failed
            if ($upgrade['success']) {
                $upgrade['number_upgraded'] += 1;
                $upgrade['upgraded_to'] = $fileDetail['version'];

                unset($upgrade['upgrade_file_left'][$num]);
            } else {
                $upgrade['version_fail'] = $fileDetail['version'];

                // If any errors, the module is disabled
                $this->disable();
                break;
            }
        }

        $upgrade['number_upgrade_left'] = count($upgrade['upgrade_file_left']);

        // Update module version in DB with the last succeed upgrade
        if ($upgrade['upgraded_to']) {
            Module::upgradeModuleVersion($this->name, $upgrade['upgraded_to']);
        }
        $this->setUpgradeMessage($upgrade);

        return $upgrade;
    }

    /**
     * Desactivate current module.
     *
     * @param bool $force_all If true, disable module for all shop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function disable($force_all = false)
    {
        // Disable module for all shops
        $sql = 'DELETE FROM `'._DB_PREFIX_.'module_shop` WHERE `id_module` = '.(int) $this->id.' '.((!$force_all) ? ' AND `id_shop` IN('.implode(', ', Shop::getContextListShopID()).')' : '');
        Db::getInstance()->execute($sql);
    }

    /**
     * Set errors, warning or success message of a module upgrade
     *
     * @param $upgradeDetail
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setUpgradeMessage($upgradeDetail)
    {
        // Store information if a module has been upgraded (memory optimization)
        if ($upgradeDetail['available_upgrade']) {
            if ($upgradeDetail['success']) {
                $this->_confirmations[] = sprintf(Tools::displayError('Current version: %s'), $this->version);
                $this->_confirmations[] = sprintf(Tools::displayError('%d file upgrade applied'), $upgradeDetail['number_upgraded']);
            } else {
                if (!$upgradeDetail['number_upgraded']) {
                    $this->_errors[] = Tools::displayError('No upgrade has been applied');
                } else {
                    $this->_errors[] = sprintf(Tools::displayError('Upgraded from: %s to %s'), $upgradeDetail['upgraded_from'], $upgradeDetail['upgraded_to']);
                    $this->_errors[] = sprintf(Tools::displayError('%d upgrade left'), $upgradeDetail['number_upgrade_left']);
                }

                if (isset($upgradeDetail['duplicate']) && $upgradeDetail['duplicate']) {
                    $this->_errors[] = sprintf(Tools::displayError('Module %s cannot be upgraded this time: please refresh this page to update it.'), $this->name);
                } else {
                    $this->_errors[] = Tools::displayError('To prevent any problem, this module has been turned off');
                }
            }
        }
    }

    /**
     * Delete module from datable
     *
     * @return bool result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function uninstall()
    {
        // Check module installation id validation
        if (!Validate::isUnsignedId($this->id)) {
            $this->_errors[] = Tools::displayError('The module is not installed.');

            return false;
        }

        // Uninstall overrides
        if (!$this->uninstallOverrides()) {
            return false;
        }

        // Retrieve hooks used by the module
        $sql = 'SELECT `id_hook` FROM `'._DB_PREFIX_.'hook_module` WHERE `id_module` = '.(int) $this->id;
        $result = Db::getInstance()->executeS($sql);
        foreach ($result as $row) {
            $this->unregisterHook((int) $row['id_hook']);
            $this->unregisterExceptions((int) $row['id_hook']);
        }

        foreach ($this->controllers as $controller) {
            $pageName = 'module-'.$this->name.'-'.$controller;
            $meta = Db::getInstance()->getValue('SELECT id_meta FROM `'._DB_PREFIX_.'meta` WHERE page="'.pSQL($pageName).'"');
            if ((int) $meta > 0) {
                Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'theme_meta` WHERE id_meta='.(int) $meta);
                Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'meta_lang` WHERE id_meta='.(int) $meta);
                Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'meta` WHERE id_meta='.(int) $meta);
            }
        }

        // Disable the module for all shops
        $this->disable(true);

        // Delete permissions module access
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'module_access` WHERE `id_module` = '.(int) $this->id);

        // Remove restrictions for client groups
        Group::truncateRestrictionsByModule($this->id);

        // Uninstall the module
        if (Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'module` WHERE `id_module` = '.(int) $this->id)) {
            Cache::clean('Module::isInstalled'.$this->name);
            Cache::clean('Module::getModuleIdByName_'.pSQL($this->name));
            UrlRewrite::regenerateUrlRewrite(UrlRewrite::ENTITY_PAGE);

            return true;
        }

        return false;
    }

    /**
     * Unregister module from hook
     *
     * @param int   $hookId   Hook id (can be a hook name since 1.5.0)
     * @param array $shopList List of shop
     *
     * @return bool result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function unregisterHook($hookId, $shopList = null)
    {
        // Get hook id if a name is given as argument
        if (!is_numeric($hookId)) {
            $hookName = (string) $hookId;
            // Retrocompatibility
            $hookId = Hook::getIdByName($hookName);
            if (!$hookId) {
                return false;
            }
        } else {
            $hookName = Hook::getNameById((int) $hookId);
        }

        Hook::exec('actionModuleUnRegisterHookBefore', ['object' => $this, 'hook_name' => $hookName]);

        // Unregister module on hook by id
        $sql = 'DELETE FROM `'._DB_PREFIX_.'hook_module`
			WHERE `id_module` = '.(int) $this->id.' AND `id_hook` = '.(int) $hookId
            .(($shopList) ? ' AND `id_shop` IN('.implode(', ', array_map('intval', $shopList)).')' : '');
        $result = Db::getInstance()->execute($sql);

        // Clean modules position
        $this->cleanPositions($hookId, $shopList);

        Hook::exec('actionModuleUnRegisterHookAfter', ['object' => $this, 'hook_name' => $hookName]);

        return $result;
    }

    /**
     * Reorder modules position
     *
     * @param bool  $idHook   Hook ID
     * @param array $shopList List of shop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @return bool
     */
    public function cleanPositions($idHook, $shopList = null)
    {
        $sql = 'SELECT `id_module`, `id_shop`
			FROM `'._DB_PREFIX_.'hook_module`
			WHERE `id_hook` = '.(int) $idHook.'
			'.((!is_null($shopList) && $shopList) ? ' AND `id_shop` IN('.implode(', ', array_map('intval', $shopList)).')' : '').'
			ORDER BY `position`';
        $results = Db::getInstance()->executeS($sql);
        $position = [];
        foreach ($results as $row) {
            if (!isset($position[$row['id_shop']])) {
                $position[$row['id_shop']] = 1;
            }

            $sql = 'UPDATE `'._DB_PREFIX_.'hook_module`
				SET `position` = '.$position[$row['id_shop']].'
				WHERE `id_hook` = '.(int) $idHook.'
				AND `id_module` = '.$row['id_module'].' AND `id_shop` = '.$row['id_shop'];
            Db::getInstance()->execute($sql);
            $position[$row['id_shop']]++;
        }

        return true;
    }

    /**
     * Unregister exceptions linked to module
     *
     * @param int   $hookId   Hook id
     * @param array $shopList List of shop
     *
     * @return bool result
     */
    public function unregisterExceptions($hookId, $shopList = null)
    {
        $sql = 'DELETE FROM `'._DB_PREFIX_.'hook_module_exceptions`
			WHERE `id_module` = '.(int) $this->id.' AND `id_hook` = '.(int) $hookId
            .(($shopList) ? ' AND `id_shop` IN('.implode(', ', array_map('intval', $shopList)).')' : '');

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param $device
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function enableDevice($device)
    {
        Db::getInstance()->execute(
            '
			UPDATE '._DB_PREFIX_.'module_shop
			SET enable_device = enable_device + '.(int) $device.'
			WHERE (enable_device &~ '.(int) $device.' OR enable_device = 0) AND id_module='.(int) $this->id.
            Shop::addSqlRestriction()
        );

        return true;
    }

    /**
     * @param $device
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function disableDevice($device)
    {
        Db::getInstance()->execute(
            '
			UPDATE '._DB_PREFIX_.'module_shop
			SET enable_device = enable_device - '.(int) $device.'
			WHERE enable_device & '.(int) $device.' AND id_module='.(int) $this->id.
            Shop::addSqlRestriction()
        );

        return true;
    }

    /**
     * Display flags in forms for translations
     *
     *
     * @param array  $languages           All languages available
     * @param int    $defaultLanguage     Default language id
     * @param string $ids                 Multilingual div ids in form
     * @param string $id                  Current div id]
     * @param bool   $return              define the return way : false for a display, true for a return
     * @param bool   $useVarsInsteadOfIds use an js vars instead of ids seperate by "¤"
     *
     * @deprecated 2.0.0
     *
     * @return bool|string
     */
    public function displayFlags($languages, $defaultLanguage, $ids, $id, $return = false, $useVarsInsteadOfIds = false)
    {
        if (count($languages) == 1) {
            return false;
        }

        $output = '
		<div class="displayed_flag">
			<img src="../img/l/'.$defaultLanguage.'.jpg" class="pointer" id="language_current_'.$id.'" onclick="toggleLanguageFlags(this);" alt="" />
		</div>
		<div id="languages_'.$id.'" class="language_flags">
			'.$this->l('Choose language:').'<br /><br />';
        foreach ($languages as $language) {
            if ($useVarsInsteadOfIds) {
                $output .= '<img src="../img/l/'.(int) $language['id_lang'].'.jpg" class="pointer" alt="'.$language['name'].'" title="'.$language['name'].'" onclick="changeLanguage(\''.$id.'\', '.$ids.', '.$language['id_lang'].', \''.$language['iso_code'].'\');" /> ';
            } else {
                $output .= '<img src="../img/l/'.(int) $language['id_lang'].'.jpg" class="pointer" alt="'.$language['name'].'" title="'.$language['name'].'" onclick="changeLanguage(\''.$id.'\', \''.$ids.'\', '.$language['id_lang'].', \''.$language['iso_code'].'\');" /> ';
            }
        }
        $output .= '</div>';

        if ($return) {
            return $output;
        }
        echo $output;
    }

    /**
     * Get translation for a given module text
     *
     * Note: $specific parameter is mandatory for library files.
     * Otherwise, translation key will not match for Module library
     * when module is loaded with eval() Module::getModulesOnDisk()
     *
     * @param string      $string   String to translate
     * @param bool|string $specific filename to use in translation key
     *
     * @return string Translation
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function l($string, $specific = false)
    {
        if (self::$_generate_config_xml_mode) {
            return $string;
        }

        return Translate::getModuleTranslation($this, $string, ($specific) ? $specific : $this->name);
    }

    /**
     * Connect module to a hook
     *
     * @param string $hookName Hook name
     * @param array  $shopList List of shop linked to the hook (if null, link hook to all shops)
     *
     * @return bool result
     * @throws PrestaShopException
     */
    public function registerHook($hookName, $shopList = null)
    {
        $return = true;
        if (is_array($hookName)) {
            $hookNames = $hookName;
        } else {
            $hookNames = [$hookName];
        }

        foreach ($hookNames as $hookName) {
            // Check hook name validation and if module is installed
            if (!Validate::isHookName($hookName)) {
                throw new PrestaShopException('Invalid hook name');
            }
            if (!isset($this->id) || !is_numeric($this->id)) {
                return false;
            }

            // Retrocompatibility
            if ($alias = Hook::getRetroHookName($hookName)) {
                $hookName = $alias;
            }

            Hook::exec('actionModuleRegisterHookBefore', ['object' => $this, 'hook_name' => $hookName]);
            // Get hook id
            $idHook = Hook::getIdByName($hookName);

            // If hook does not exist, we create it
            if (!$idHook) {
                $newHook = new Hook();
                $newHook->name = pSQL($hookName);
                $newHook->title = pSQL($hookName);
                $newHook->live_edit = (bool) preg_match('/^display/i', $newHook->name);
                $newHook->position = (bool) $newHook->live_edit;
                $newHook->add();
                $idHook = $newHook->id;
                if (!$idHook) {
                    return false;
                }
            }

            // If shop lists is null, we fill it with all shops
            if (is_null($shopList)) {
                $shopList = Shop::getCompleteListOfShopsID();
            }

            $shopListEmployee = Shop::getShops(true, null, true);

            foreach ($shopList as $shopId) {
                // Check if already register
                $sql = 'SELECT hm.`id_module`
					FROM `'._DB_PREFIX_.'hook_module` hm, `'._DB_PREFIX_.'hook` h
					WHERE hm.`id_module` = '.(int) $this->id.' AND h.`id_hook` = '.$idHook.'
					AND h.`id_hook` = hm.`id_hook` AND `id_shop` = '.(int) $shopId;
                if (Db::getInstance()->getRow($sql)) {
                    continue;
                }

                // Get module position in hook
                $sql = 'SELECT MAX(`position`) AS position
					FROM `'._DB_PREFIX_.'hook_module`
					WHERE `id_hook` = '.(int) $idHook.' AND `id_shop` = '.(int) $shopId;
                if (!$position = Db::getInstance()->getValue($sql)) {
                    $position = 0;
                }

                // Register module in hook
                $return &= Db::getInstance()->insert(
                    'hook_module',
                    [
                        'id_module' => (int) $this->id,
                        'id_hook'   => (int) $idHook,
                        'id_shop'   => (int) $shopId,
                        'position'  => (int) ($position + 1),
                    ]
                );

                if (!in_array($shopId, $shopListEmployee)) {
                    $where = '`id_module` = '.(int) $this->id.' AND `id_shop` = '.(int) $shopId;
                    $return &= Db::getInstance()->delete('module_shop', $where);
                }
            }

            Hook::exec('actionModuleRegisterHookAfter', ['object' => $this, 'hook_name' => $hookName]);
        }

        return $return;
    }

    /**
     * Edit exceptions for module->Hook
     *
     * @param int   $hookID  Hook id
     * @param array $excepts List of shopID and file name
     *
     * @return bool result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function editExceptions($idHook, $excepts)
    {
        $result = true;
        foreach ($excepts as $shopId => $except) {
            $shopList = ($shopId == 0) ? Shop::getContextListShopID() : [$shopId];
            $this->unregisterExceptions($idHook, $shopList);
            $result &= $this->registerExceptions($idHook, $except, $shopList);

        }

        return $result;
    }

    /**
     * Add exceptions for module->Hook
     *
     * @param int   $idHook   Hook id
     * @param array $excepts  List of file name
     * @param array $shopList List of shop
     *
     * @return bool result
     */
    public function registerExceptions($idHook, $excepts, $shopList = null)
    {
        // If shop lists is null, we fill it with all shops
        if (is_null($shopList)) {
            $shopList = Shop::getContextListShopID();
        }

        // Save modules exception for each shop
        foreach ($shopList as $shopId) {
            foreach ($excepts as $except) {
                if (!$except) {
                    continue;
                }
                $insertException = [
                    'id_module' => (int) $this->id,
                    'id_hook'   => (int) $idHook,
                    'id_shop'   => (int) $shopId,
                    'file_name' => pSQL($except),
                ];
                $result = Db::getInstance()->insert('hook_module_exceptions', $insertException);
                if (!$result) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Reposition module
     *
     * @param bool $idHook Hook ID
     * @param bool $way    Up (0) or Down (1)
     * @param int  $position
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updatePosition($idHook, $way, $position = null)
    {
        foreach (Shop::getContextListShopID() as $idShop) {
            $sql = 'SELECT hm.`id_module`, hm.`position`, hm.`id_hook`
					FROM `'._DB_PREFIX_.'hook_module` hm
					WHERE hm.`id_hook` = '.(int) $idHook.' AND hm.`id_shop` = '.$idShop.'
					ORDER BY hm.`position` '.($way ? 'ASC' : 'DESC');
            if (!$res = Db::getInstance()->executeS($sql)) {
                continue;
            }

            foreach ($res as $key => $values) {
                if ((int) $values[$this->identifier] == (int) $this->id) {
                    $k = $key;
                    break;
                }
            }
            if (!isset($k) || !isset($res[$k]) || !isset($res[$k + 1])) {
                return false;
            }

            $from = $res[$k];
            $to = $res[$k + 1];

            if (isset($position) && !empty($position)) {
                $to['position'] = (int) $position;
            }

            $sql = 'UPDATE `'._DB_PREFIX_.'hook_module`
				SET `position`= position '.($way ? '-1' : '+1').'
				WHERE position between '.(int) (min([$from['position'], $to['position']])).' AND '.max([$from['position'], $to['position']]).'
				AND `id_hook` = '.(int) $from['id_hook'].' AND `id_shop` = '.$idShop;
            if (!Db::getInstance()->execute($sql)) {
                return false;
            }

            $sql = 'UPDATE `'._DB_PREFIX_.'hook_module`
				SET `position`='.(int) $to['position'].'
				WHERE `'.pSQL($this->identifier).'` = '.(int) $from[$this->identifier].'
				AND `id_hook` = '.(int) $to['id_hook'].' AND `id_shop` = '.$idShop;
            if (!Db::getInstance()->execute($sql)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Helper displaying error message(s)
     *
     * @param string|array $error
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function displayError($error)
    {
        $output = '
		<div class="bootstrap">
		<div class="module_error alert alert-danger" >
			<button type="button" class="close" data-dismiss="alert">&times;</button>';

        if (is_array($error)) {
            $output .= '<ul>';
            foreach ($error as $msg) {
                $output .= '<li>'.$msg.'</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= $error;
        }

        // Close div openned previously
        $output .= '</div></div>';

        $this->error = true;

        return $output;
    }

    /**
     * Helper displaying warning message(s)
     *
     * @param string|array $error
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function displayWarning($warning)
    {
        $output = '
		<div class="bootstrap">
		<div class="module_warning alert alert-warning" >
			<button type="button" class="close" data-dismiss="alert">&times;</button>';

        if (is_array($warning)) {
            $output .= '<ul>';
            foreach ($warning as $msg) {
                $output .= '<li>'.$msg.'</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= $warning;
        }

        // Close div openned previously
        $output .= '</div></div>';

        return $output;
    }

    /**
     * @param $string
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function displayConfirmation($string)
    {
        $output = '
		<div class="bootstrap">
		<div class="module_confirmation conf confirm alert alert-success">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			'.$string.'
		</div>
		</div>';

        return $output;
    }

    /**
     * Return exceptions for module in hook
     *
     * @param int $idHook Hook ID
     *
     * @return array Exceptions
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getExceptions($idHook, $dispatch = false)
    {
        return Module::getExceptionsStatic($this->id, $idHook, $dispatch);
    }

    /**
     * Return exceptions for module in hook
     *
     * @param int $id_module Module ID
     * @param int $id_hook   Hook ID
     *
     * @return array Exceptions
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getExceptionsStatic($id_module, $id_hook, $dispatch = false)
    {
        $cache_id = 'exceptionsCache';
        if (!Cache::isStored($cache_id)) {
            $exceptions_cache = [];
            $sql = 'SELECT * FROM `'._DB_PREFIX_.'hook_module_exceptions`
				WHERE `id_shop` IN ('.implode(', ', Shop::getContextListShopID()).')';
            $db = Db::getInstance();
            $result = $db->executeS($sql, false);
            while ($row = $db->nextRow($result)) {
                if (!$row['file_name']) {
                    continue;
                }
                $key = $row['id_hook'].'-'.$row['id_module'];
                if (!isset($exceptions_cache[$key])) {
                    $exceptions_cache[$key] = [];
                }
                if (!isset($exceptions_cache[$key][$row['id_shop']])) {
                    $exceptions_cache[$key][$row['id_shop']] = [];
                }
                $exceptions_cache[$key][$row['id_shop']][] = $row['file_name'];
            }
            Cache::store($cache_id, $exceptions_cache);
        } else {
            $exceptions_cache = Cache::retrieve($cache_id);
        }

        $key = $id_hook.'-'.$id_module;
        $array_return = [];
        if ($dispatch) {
            foreach (Shop::getContextListShopID() as $shop_id) {
                if (isset($exceptions_cache[$key], $exceptions_cache[$key][$shop_id])) {
                    $array_return[$shop_id] = $exceptions_cache[$key][$shop_id];
                }
            }
        } else {
            foreach (Shop::getContextListShopID() as $shop_id) {
                if (isset($exceptions_cache[$key], $exceptions_cache[$key][$shop_id])) {
                    foreach ($exceptions_cache[$key][$shop_id] as $file) {
                        if (!in_array($file, $array_return)) {
                            $array_return[] = $file;
                        }
                    }
                }
            }
        }

        return $array_return;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function isEnabledForShopContext()
    {
        return (bool) Db::getInstance()->getValue(
            '
			SELECT COUNT(*) n
			FROM `'._DB_PREFIX_.'module_shop`
			WHERE id_module='.(int) $this->id.' AND id_shop IN ('.implode(',', array_map('intval', Shop::getContextListShopID())).')
			GROUP BY id_module
			HAVING n='.(int) count(Shop::getContextListShopID())
        );
    }

    /**
     * @param $hook
     *
     * @return bool|false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function isRegisteredInHook($hook)
    {
        if (!$this->id) {
            return false;
        }

        $sql = 'SELECT COUNT(*)
			FROM `'._DB_PREFIX_.'hook_module` hm
			LEFT JOIN `'._DB_PREFIX_.'hook` h ON (h.`id_hook` = hm.`id_hook`)
			WHERE h.`name` = \''.pSQL($hook).'\' AND hm.`id_module` = '.(int) $this->id;

        return Db::getInstance()->getValue($sql);
    }

    /**
     * @param      $file
     * @param      $template
     * @param null $cache_id
     * @param null $compile_id
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function display($file, $template, $cache_id = null, $compile_id = null)
    {
        if (($overloaded = Module::_isTemplateOverloadedStatic(basename($file, '.php'), $template)) === null) {
            return Tools::displayError('No template found for module').' '.basename($file, '.php');
        } else {
            if (Tools::getIsset('live_edit') || Tools::getIsset('live_configurator_token')) {
                $cache_id = null;
            }

            $this->smarty->assign(
                [
                    'module_dir'          => __PS_BASE_URI__.'modules/'.basename($file, '.php').'/',
                    'module_template_dir' => ($overloaded ? _THEME_DIR_ : __PS_BASE_URI__).'modules/'.basename($file, '.php').'/',
                    'allow_push'          => $this->allow_push,
                ]
            );

            if ($cache_id !== null) {
                Tools::enableCache();
            }

            $result = $this->getCurrentSubTemplate($template, $cache_id, $compile_id)->fetch();

            if ($cache_id !== null) {
                Tools::restoreCacheSettings();
            }

            $this->resetCurrentSubTemplate($template, $cache_id, $compile_id);

            return $result;
        }
    }

    /**
     * @param $module_name
     * @param $template
     *
     * @return bool|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function _isTemplateOverloadedStatic($module_name, $template)
    {
        if (file_exists(_PS_THEME_DIR_.'modules/'.$module_name.'/'.$template)) {
            return _PS_THEME_DIR_.'modules/'.$module_name.'/'.$template;
        } elseif (file_exists(_PS_THEME_DIR_.'modules/'.$module_name.'/views/templates/hook/'.$template)) {
            return _PS_THEME_DIR_.'modules/'.$module_name.'/views/templates/hook/'.$template;
        } elseif (file_exists(_PS_THEME_DIR_.'modules/'.$module_name.'/views/templates/front/'.$template)) {
            return _PS_THEME_DIR_.'modules/'.$module_name.'/views/templates/front/'.$template;
        } elseif (file_exists(_PS_MODULE_DIR_.$module_name.'/views/templates/hook/'.$template)) {
            return false;
        } elseif (file_exists(_PS_MODULE_DIR_.$module_name.'/views/templates/front/'.$template)) {
            return false;
        } elseif (file_exists(_PS_MODULE_DIR_.$module_name.'/'.$template)) {
            return false;
        }

        return null;
    }

    /**
     * @param string      $template
     * @param string|null $cache_id
     * @param string|null $compile_id
     *
     * @return Smarty_Internal_Template
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getCurrentSubTemplate($template, $cache_id = null, $compile_id = null)
    {
        if (!isset($this->current_subtemplate[$template.'_'.$cache_id.'_'.$compile_id])) {
            $this->current_subtemplate[$template.'_'.$cache_id.'_'.$compile_id] = $this->context->smarty->createTemplate(
                $this->getTemplatePath($template),
                $cache_id,
                $compile_id,
                $this->smarty
            );
        }

        return $this->current_subtemplate[$template.'_'.$cache_id.'_'.$compile_id];
    }

    /**
     * Get realpath of a template of current module (check if template is overriden too)
     *
     * @param string $template
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTemplatePath($template)
    {
        $overloaded = $this->_isTemplateOverloaded($template);
        if ($overloaded === null) {
            return null;
        }

        if ($overloaded) {
            return $overloaded;
        } elseif (file_exists(_PS_MODULE_DIR_.$this->name.'/views/templates/hook/'.$template)) {
            return _PS_MODULE_DIR_.$this->name.'/views/templates/hook/'.$template;
        } elseif (file_exists(_PS_MODULE_DIR_.$this->name.'/views/templates/front/'.$template)) {
            return _PS_MODULE_DIR_.$this->name.'/views/templates/front/'.$template;
        } elseif (file_exists(_PS_MODULE_DIR_.$this->name.'/'.$template)) {
            return _PS_MODULE_DIR_.$this->name.'/'.$template;
        } else {
            return null;
        }
    }

    /**
     * @param $template
     *
     * @return bool|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _isTemplateOverloaded($template)
    {
        return Module::_isTemplateOverloadedStatic($this->name, $template);
    }

    protected function resetCurrentSubTemplate($template, $cache_id, $compile_id)
    {
        $this->current_subtemplate[$template.'_'.$cache_id.'_'.$compile_id] = null;
    }

    /**
     * @param      $template
     * @param null $cacheId
     * @param null $compileId
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function isCached($template, $cacheId = null, $compileId = null)
    {
        if (Tools::getIsset('live_edit') || Tools::getIsset('live_configurator_token')) {
            return false;
        }
        Tools::enableCache();
        $new_tpl = $this->getTemplatePath($template);
        $is_cached = $this->getCurrentSubTemplate($template, $cacheId, $compileId)->isCached($new_tpl, $cacheId, $compileId);
        Tools::restoreCacheSettings();

        return $is_cached;
    }

    /**
     * Check if the module is transplantable on the hook in parameter
     *
     * @param string $hook_name
     *
     * @return bool if module can be transplanted on hook
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function isHookableOn($hook_name)
    {
        $retro_hook_name = Hook::getRetroHookName($hook_name);

        return (is_callable([$this, 'hook'.ucfirst($hook_name)]) || is_callable([$this, 'hook'.ucfirst($retro_hook_name)]));
    }

    /**
     * Check employee permission for module
     *
     * @param string $variable (action)
     * @param object $employee
     *
     * @return bool if module can be transplanted on hook
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getPermission($variable, $employee = null)
    {
        return Module::getPermissionStatic($this->id, $variable, $employee);
    }

    /**
     * Check employee permission for module (static method)
     *
     * @param int    $idModule
     * @param string $variable (action)
     * @param object $employee
     *
     * @return bool if module can be transplanted on hook
     *
     * @throws PrestaShopException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getPermissionStatic($idModule, $variable, $employee = null)
    {
        if (!in_array($variable, ['view', 'configure', 'uninstall'])) {
            return false;
        }

        if (!$employee) {
            $employee = Context::getContext()->employee;
        }

        if ($employee->id_profile == _PS_ADMIN_PROFILE_) {
            return true;
        }

        if (!isset(self::$cache_permissions[$employee->id_profile])) {
            self::$cache_permissions[$employee->id_profile] = [];
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT `id_module`, `view`, `configure`, `uninstall` FROM `'._DB_PREFIX_.'module_access` WHERE `id_profile` = '.(int) $employee->id_profile);
            foreach ($result as $row) {
                self::$cache_permissions[$employee->id_profile][$row['id_module']]['view'] = $row['view'];
                self::$cache_permissions[$employee->id_profile][$row['id_module']]['configure'] = $row['configure'];
                self::$cache_permissions[$employee->id_profile][$row['id_module']]['uninstall'] = $row['uninstall'];
            }
        }

        if (!isset(self::$cache_permissions[$employee->id_profile][$idModule])) {
            throw new PrestaShopException('No access reference in table module_access for id_module '.$idModule.'.');
        }

        return (bool) self::$cache_permissions[$employee->id_profile][$idModule][$variable];
    }

    /**
     * Get module errors
     *
     * @since 1.0.0
     * @return array errors
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Get module messages confirmation
     *
     * @since 1.0.0
     * @return array conf
     */
    public function getConfirmations()
    {
        return $this->_confirmations;
    }

    /**
     * Get uri path for module
     *
     * @since 1.0.0
     * @return string
     */
    public function getPathUri()
    {
        return $this->_path;
    }

    /**
     * Return module position for a given hook
     *
     * @param bool $id_hook Hook ID
     *
     * @return int position
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getPosition($id_hook)
    {
        if (isset(Hook::$preloadModulesFromHooks)) {
            if (isset(Hook::$preloadModulesFromHooks[$id_hook])) {
                if (isset(Hook::$preloadModulesFromHooks[$id_hook]['module_position'][$this->id])) {
                    return Hook::$preloadModulesFromHooks[$id_hook]['module_position'][$this->id];
                } else {
                    return 0;
                }
            }
        }
        $result = Db::getInstance()->getRow(
            '
			SELECT `position`
			FROM `'._DB_PREFIX_.'hook_module`
			WHERE `id_hook` = '.(int) $id_hook.'
			AND `id_module` = '.(int) $this->id.'
			AND `id_shop` = '.(int) Context::getContext()->shop->id
        );

        return $result['position'];
    }

    /**
     * add a warning message to display at the top of the admin page
     *
     * @param string $msg
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function adminDisplayWarning($msg)
    {
        if (!($this->context->controller instanceof AdminController)) {
            return;
        }
        $this->context->controller->warnings[] = $msg;
    }

    /**
     * Return the hooks list where this module can be hooked.
     *
     * @return array Hooks list.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getPossibleHooksList()
    {
        $hooks_list = Hook::getHooks();
        $possible_hooks_list = [];
        foreach ($hooks_list as &$current_hook) {
            $hook_name = $current_hook['name'];
            $retro_hook_name = Hook::getRetroHookName($hook_name);

            if (is_callable([$this, 'hook'.ucfirst($hook_name)]) || is_callable([$this, 'hook'.ucfirst($retro_hook_name)])) {
                $possible_hooks_list[] = [
                    'id_hook' => $current_hook['id_hook'],
                    'name'    => $hook_name,
                    'title'   => $current_hook['title'],
                ];
            }
        }

        return $possible_hooks_list;
    }

    /**
     * @param null $name
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getCacheId($name = null)
    {
        $cache_array = [];
        $cache_array[] = $name !== null ? $name : $this->name;
        if (Configuration::get('PS_SSL_ENABLED')) {
            $cache_array[] = (int) Tools::usingSecureMode();
        }
        if (Shop::isFeatureActive()) {
            $cache_array[] = (int) $this->context->shop->id;
        }
        if (Group::isFeatureActive() && isset($this->context->customer)) {
            $cache_array[] = (int) Group::getCurrent()->id;
            $cache_array[] = implode('_', Customer::getGroupsStatic($this->context->customer->id));
        }
        if (Language::isMultiLanguageActivated()) {
            $cache_array[] = (int) $this->context->language->id;
        }
        if (Currency::isMultiCurrencyActivated()) {
            $cache_array[] = (int) $this->context->currency->id;
        }
        $cache_array[] = (int) $this->context->country->id;

        return implode('|', $cache_array);
    }

    /**
     * @param $template
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _getApplicableTemplateDir($template)
    {
        return $this->_isTemplateOverloaded($template) ? _PS_THEME_DIR_ : _PS_MODULE_DIR_.$this->name.'/';
    }

    /**
     * Clear template cache
     *
     * @param string $template Template name
     * @param        int       null $cacheId
     * @param        int       null $compileId
     *
     * @return false|int Number of template cleared
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _clearCache($template, $cacheId = null, $compileId = null)
    {
        static $ps_smarty_clear_cache = null;
        if ($ps_smarty_clear_cache === null) {
            $ps_smarty_clear_cache = Configuration::get('PS_SMARTY_CLEAR_CACHE');
        }

        if (self::$_batch_mode) {
            if ($ps_smarty_clear_cache == 'never') {
                return 0;
            }

            if ($cacheId === null) {
                $cacheId = $this->name;
            }

            $key = $template.'-'.$cacheId.'-'.$compileId;
            if (!isset(self::$_defered_clearCache[$key])) {
                self::$_defered_clearCache[$key] = [$this->getTemplatePath($template), $cacheId, $compileId];
            }
        } else {
            if ($ps_smarty_clear_cache == 'never') {
                return 0;
            }

            if ($cacheId === null) {
                $cacheId = $this->name;
            }

            Tools::enableCache();
            $number_of_template_cleared = Tools::clearCache(Context::getContext()->smarty, $this->getTemplatePath($template), $cacheId, $compileId);
            Tools::restoreCacheSettings();

            return $number_of_template_cleared;
        }

        return false;
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _generateConfigXml()
    {
        $author_uri = '';
        if (isset($this->author_uri) && $this->author_uri) {
            $author_uri = '<author_uri><![CDATA['.Tools::htmlentitiesUTF8($this->author_uri).']]></author_uri>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
<module>
	<name>'.$this->name.'</name>
	<displayName><![CDATA['.str_replace('&amp;', '&', Tools::htmlentitiesUTF8($this->displayName)).']]></displayName>
	<version><![CDATA['.$this->version.']]></version>
	<description><![CDATA['.str_replace('&amp;', '&', Tools::htmlentitiesUTF8($this->description)).']]></description>
	<author><![CDATA['.str_replace('&amp;', '&', Tools::htmlentitiesUTF8($this->author)).']]></author>'
            .$author_uri.'
	<tab><![CDATA['.Tools::htmlentitiesUTF8($this->tab).']]></tab>'.(isset($this->confirmUninstall) ? "\n\t".'<confirmUninstall><![CDATA['.$this->confirmUninstall.']]></confirmUninstall>' : '').'
	<is_configurable>'.(isset($this->is_configurable) ? (int) $this->is_configurable : 0).'</is_configurable>
	<need_instance>'.(int) $this->need_instance.'</need_instance>'.(isset($this->limited_countries) ? "\n\t".'<limited_countries>'.(count($this->limited_countries) == 1 ? $this->limited_countries[0] : '').'</limited_countries>' : '').'
</module>';
        if (is_writable(_PS_MODULE_DIR_.$this->name.'/')) {
            $iso = substr(Context::getContext()->language->iso_code, 0, 2);
            $file = _PS_MODULE_DIR_.$this->name.'/'.($iso == 'en' ? 'config.xml' : 'config_'.$iso.'.xml');
            if (!@file_put_contents($file, $xml)) {
                if (!is_writable($file)) {
                    @unlink($file);
                    @file_put_contents($file, $xml);
                }
            }
            @chmod($file, 0664);
        }
    }

    /**
     * add a info message to display at the top of the admin page
     *
     * @param string $msg
     *
     * @return void
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function adminDisplayInformation($msg)
    {
        if (!($this->context->controller instanceof AdminController)) {
            return;
        }
        $this->context->controller->informations[] = $msg;
    }
}

function ps_module_version_sort($a, $b)
{
    return version_compare($a['version'], $b['version']);
}
