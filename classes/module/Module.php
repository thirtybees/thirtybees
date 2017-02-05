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
 * Class ModuleCore
 *
 * @since 1.0.0
 */
abstract class ModuleCore
{
    // @codingStandardsIgnoreStart
    /** @var int Module ID */
    public $id = null;

    /** @var float Version */
    public $version;
    public $database_version;

    /**
     * @since 1.5.0.1
     * @var string Registered Version in database
     */
    public $registered_version;

    /** @var array filled with known compliant PS versions */
    public $ps_versions_compliancy = [];

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

    public $description_full;

    public $additional_description;

    public $compatibility;

    public $nb_rates;

    public $avg_rate;

    public $badges;

    /** @var int need_instance */
    public $need_instance = 1;

    /** @var string Admin tab corresponding to the module */
    public $tab = null;

    /** @var bool Status */
    public $active = false;

    /** @var bool Is the module certified */
    public $trusted = false;

    /** @var string Fill it if the module is installed but not yet set up */
    public $warning;

    public $enable_device = 7;

    /** @var array to store the limited country */
    public $limited_countries = [];

    /** @var array names of the controllers */
    public $controllers = [];

    /** @var array used by AdminTab to determine which lang file to use (admin.php or module lang file) */
    public static $classInModule = [];

    /** @var array current language translations */
    protected $_lang = [];

    /** @var string Module web path (eg. '/shop/modules/modulename/')  */
    protected $_path = null;
    /**
     * @since 1.5.0.1
     * @var string Module local path (eg. '/home/prestashop/modules/modulename/')
     */
    protected $local_path = null;

    /** @var array Array filled with module errors */
    protected $_errors = [];

    /** @var array Array  array filled with module success */
    protected $_confirmations = [];

    /** @var string Main table used for modules installed */
    protected $table = 'module';

    /** @var string Identifier of the main table */
    protected $identifier = 'id_module';

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

    /** @var Context */
    protected $context;

    /** @var Smarty_Data */
    protected $smarty;

    /** @var Smarty_Internal_Template|null */
    protected $current_subtemplate = null;

    protected static $update_translations_after_install = true;

    protected static $_batch_mode = false;
    protected static $_defered_clearCache = [];
    protected static $_defered_func_call = [];

    /** @var bool If true, allow push */
    public $allow_push;

    public $push_time_limit = 180;

    /** @var bool Define if we will log modules performances for this session */
    public static $_log_modules_perfs = null;
    /** @var bool Random session for modules perfs logs*/
    public static $_log_modules_perfs_session = null;

    const CACHE_FILE_MODULES_LIST = '/config/xml/modules_list.xml';

    const CACHE_FILE_TAB_MODULES_LIST = '/config/xml/tab_modules_list.xml';

    public static $hosted_modules_blacklist = ['autoupgrade'];

    /**
     * @var bool $bootstrap
     *
     * Indicates whether the module's configuration page supports bootstrap
     */
    public $bootstrap = false;
    // @codingStandardsIgnoreEnd

    /**
     * Set the flag to indicate we are doing an import
     *
     * @param bool $value
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function setBatchMode($value)
    {
        self::$_batch_mode = (bool) $value;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getBatchMode()
    {
        return self::$_batch_mode;
    }

    /**
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function processDeferedFuncCall()
    {
        self::setBatchMode(false);
        foreach (self::$_defered_func_call as $funcCall) {
            call_user_func_array($funcCall[0], $funcCall[1]);
        }

        self::$_defered_func_call = [];
    }

    /**
     * Clear the caches stored in $_defered_clearCache
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function processDeferedClearCache()
    {
        self::setBatchMode(false);

        foreach (self::$_defered_clearCache as $clearCacheArray) {
            self::_deferedClearCache($clearCacheArray[0], $clearCacheArray[1], $clearCacheArray[2]);
        }

        self::$_defered_clearCache = [];
    }

    /**
     * Constructor
     *
     * @param string $name Module unique name
     * @param Context $context
     *
     * @since 1.0.0
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
        if ($this->name != null) {
            // If cache is not generated, we generate it
            if (self::$modules_cache == null && !is_array(self::$modules_cache)) {
                $idShop = (Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : Configuration::get('PS_SHOP_DEFAULT'));

                self::$modules_cache = [];
                // Join clause is done to check if the module is activated in current shop context
                $result = Db::getInstance()->executeS('
				SELECT m.`id_module`, m.`name`, (
					SELECT id_module
					FROM `'._DB_PREFIX_.'module_shop` ms
					WHERE m.`id_module` = ms.`id_module`
					AND ms.`id_shop` = '.(int) $idShop.'
					LIMIT 1
				) as mshop
				FROM `'._DB_PREFIX_.'module` m');
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
                    if (array_key_exists($key, $this)) {
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
    }

    /**
     * Insert module into datable
     *
     * @since 1.0.0
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
        Db::getInstance()->execute('
			INSERT INTO `'._DB_PREFIX_.'module_access` (`id_profile`, `id_module`, `view`, `configure`, `uninstall`) (
				SELECT id_profile, '.(int)$this->id.', 1, 1, 1
				FROM '._DB_PREFIX_.'access a
				WHERE id_tab = (
					SELECT `id_tab` FROM '._DB_PREFIX_.'tab
					WHERE class_name = \'AdminModules\' LIMIT 1)
				AND a.`view` = 1)');

        Db::getInstance()->execute('
			INSERT INTO `'._DB_PREFIX_.'module_access` (`id_profile`, `id_module`, `view`, `configure`, `uninstall`) (
				SELECT id_profile, '.(int)$this->id.', 1, 0, 0
				FROM '._DB_PREFIX_.'access a
				WHERE id_tab = (
					SELECT `id_tab` FROM '._DB_PREFIX_.'tab
					WHERE class_name = \'AdminModules\' LIMIT 1)
				AND a.`view` = 0)');

        // Adding Restrictions for client groups
        Group::addRestrictionsForModule($this->id, Shop::getShops(true, null, true));
        Hook::exec('actionModuleInstallAfter', ['object' => $this]);

        if (Module::$update_translations_after_install) {
            $this->updateModuleTranslations();
        }

        return true;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
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
     * @param bool $update
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function updateTranslationsAfterInstall($update = true)
    {
        Module::$update_translations_after_install = (bool) $update;
    }

    /**
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function updateModuleTranslations()
    {
        return Language::updateModulesTranslations([$this->name]);
    }

    /**
     * Set errors, warning or success message of a module upgrade
     *
     * @param $upgradeDetail
     *
     * @since 1.0.0
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
     * Init the upgrade module
     *
     * @param $module
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function initUpgradeModule($module)
    {
        if (((int) $module->installed == 1) & (empty($module->database_version) === true)) {
            Module::upgradeModuleVersion($module->name, $module->version);
            $module->database_version = $module->version;
        }

        // Init cache upgrade details
        self::$modules_cache[$module->name]['upgrade'] = [
            'success' => false, // bool to know if upgrade succeed or not
            'available_upgrade' => 0, // Number of available module before any upgrade
            'number_upgraded' => 0, // Number of upgrade done
            'number_upgrade_left' => 0,
            'upgrade_file_left' => [], // List of the upgrade file left
            'version_fail' => 0, // Version of the upgrade failure
            'upgraded_from' => 0, // Version number before upgrading anything
            'upgraded_to' => 0, // Last upgrade applied
        ];

        // Need Upgrade will check and load upgrade file to the moduleCache upgrade case detail
        $ret = $module->installed && Module::needUpgrade($module);

        return $ret;
    }

    /**
     * Run the upgrade for a given module name and version
     *
     * @return array
     */
    public function runUpgradeModule()
    {
        $upgrade = &self::$modules_cache[$this->name]['upgrade'];
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
     * Upgrade the registered version to a new one
     *
     * @param $name
     * @param $version
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function upgradeModuleVersion($name, $version)
    {
        return Db::getInstance()->execute('
			UPDATE `'._DB_PREFIX_.'module` m
			SET m.version = \''.pSQL($version).'\'
			WHERE m.name = \''.pSQL($name).'\'');
    }

    /**
     * Check if a module need to be upgraded.
     * This method modify the module_cache adding an upgrade list file
     *
     * @param $module
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function needUpgrade($module)
    {
        self::$modules_cache[$module->name]['upgrade']['upgraded_from'] = $module->database_version;
        // Check the version of the module with the registered one and look if any upgrade file exist
        if (Tools::version_compare($module->version, $module->database_version, '>')) {
            $old_version = $module->database_version;
            $module = Module::getInstanceByName($module->name);
            if ($module instanceof Module) {
                return $module->loadUpgradeVersionList($module->name, $module->version, $old_version);
            }
        }
        return null;
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
     * @since 1.0.0
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
                            Tools::version_compare($fileVersion, $registeredVersion, '>'))) {
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
            self::$modules_cache[$moduleName]['upgrade']['success'] = true;
            Module::upgradeModuleVersion($moduleName, $moduleVersion);
        }

        usort($list, 'ps_module_version_sort');

        // Set the list to module cache
        self::$modules_cache[$moduleName]['upgrade']['upgrade_file_left'] = $list;
        self::$modules_cache[$moduleName]['upgrade']['available_upgrade'] = count($list);
        return (bool) count($list);
    }

    /**
     * Return the status of the upgraded module
     *
     * @param $moduleName
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getUpgradeStatus($moduleName)
    {
        return (isset(self::$modules_cache[$moduleName]) &&
            self::$modules_cache[$moduleName]['upgrade']['success']);
    }

    /**
     * Delete module from datable
     *
     * @return bool result
     *
     *              @since 1.0.0
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
            $this->unregisterHook((int)$row['id_hook']);
            $this->unregisterExceptions((int)$row['id_hook']);
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

            return true;
        }

        return false;
    }

    /**
     * This function enable module $name. If an $name is an array,
     * this will enable all of them
     *
     * @param array|string $name
     * @return true if succeed

     * @since 1.0.0
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
     * Activate current module.
     *
     * @param bool $forceAll If true, enable module for all shop
     *
     * @since 1.0.0
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
                Db::getInstance()->insert('module_shop',
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
     * @param $device
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function enableDevice($device)
    {
        Db::getInstance()->execute('
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
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function disableDevice($device)
    {
        Db::getInstance()->execute('
			UPDATE '._DB_PREFIX_.'module_shop
			SET enable_device = enable_device - '.(int) $device.'
			WHERE enable_device & '.(int) $device.' AND id_module='.(int) $this->id.
            Shop::addSqlRestriction()
        );

        return true;
    }

    /**
     * This function disable module $name. If an $name is an array,
     * this will disable all of them
     *
     * @param array|string $name
     *
     * @return true if succeed
     *
     * @since 1.0.0
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
                $res &= Module::getInstanceByName($n)->disable();
            }
        }

        return $res;
    }

    /**
     * Desactivate current module.
     *
     * @param bool $force_all If true, disable module for all shop
     *
     *                        @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function disable($force_all = false)
    {
        // Disable module for all shops
        $sql = 'DELETE FROM `'._DB_PREFIX_.'module_shop` WHERE `id_module` = '.(int)$this->id.' '.((!$force_all) ? ' AND `id_shop` IN('.implode(', ', Shop::getContextListShopID()).')' : '');
        Db::getInstance()->execute($sql);
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
     * Connect module to a hook
     *
     * @param string $hookName Hook name
     * @param array  $shopList List of shop linked to the hook (if null, link hook to all shops)
     *
     * @return bool result
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
                $new_hook = new Hook();
                $new_hook->name = pSQL($hookName);
                $new_hook->title = pSQL($hookName);
                $new_hook->live_edit = (bool)preg_match('/^display/i', $new_hook->name);
                $new_hook->position = (bool)$new_hook->live_edit;
                $new_hook->add();
                $idHook = $new_hook->id;
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
					WHERE hm.`id_module` = '.(int)$this->id.' AND h.`id_hook` = '.$idHook.'
					AND h.`id_hook` = hm.`id_hook` AND `id_shop` = '.(int) $shopId;
                if (Db::getInstance()->getRow($sql)) {
                    continue;
                }

                // Get module position in hook
                $sql = 'SELECT MAX(`position`) AS position
					FROM `'._DB_PREFIX_.'hook_module`
					WHERE `id_hook` = '.(int)$idHook.' AND `id_shop` = '.(int) $shopId;
                if (!$position = Db::getInstance()->getValue($sql)) {
                    $position = 0;
                }

                // Register module in hook
                $return &= Db::getInstance()->insert('hook_module',
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
     * Unregister module from hook
     *
     * @param mixed $id_hook  Hook id (can be a hook name since 1.5.0)
     * @param array $shopList List of shop
     *
     * @return bool result
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function unregisterHook($hookId, $shopList = null)
    {
        // Get hook id if a name is given as argument
        if (!is_numeric($hookId)) {
            $hookName = (string)$hookId;
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
     * Unregister exceptions linked to module
     *
     * @param int   $id_hook  Hook id
     * @param array $shopList List of shop
     *
*@return bool result
     */
    public function unregisterExceptions($hookId, $shopList = null)
    {
        $sql = 'DELETE FROM `'._DB_PREFIX_.'hook_module_exceptions`
			WHERE `id_module` = '.(int) $this->id.' AND `id_hook` = '.(int) $hookId
            .(($shopList) ? ' AND `id_shop` IN('.implode(', ', array_map('intval', $shopList)).')' : '');

        return Db::getInstance()->execute($sql);
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
     * Edit exceptions for module->Hook
     *
     * @param int $hookID Hook id
     * @param array $excepts List of shopID and file name
     * @return bool result
     *
     * @since 1.0.0
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
     * This function is used to determine the module name
     * of an AdminTab which belongs to a module, in order to keep translation
     * related to a module in its directory (instead of $_LANGADM)
     *
     * @param mixed $currentClass the
     *
     * @return bool|string if the class belongs to a module, will return the module name. Otherwise, return false.
     *
     * @since 1.0.0
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
     * @param string $moduleName Module name
     *
     * @return Module|bool
     *
     * @since 1.0.0
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
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function coreLoadModule($moduleName)
    {
        // Define if we will log modules performances for this session
        if (Module::$_log_modules_perfs === null) {
            $modulo = _PS_DEBUG_PROFILING_ ? 1 : Configuration::get('PS_log_modules_perfs_MODULO');
            Module::$_log_modules_perfs = ($modulo && mt_rand(0, $modulo - 1) == 0);
            if (Module::$_log_modules_perfs) {
                Module::$_log_modules_perfs_session = mt_rand();
            }
        }

        // Store time and memory before and after hook call and save the result in the database
        if (Module::$_log_modules_perfs) {
            $time_start = microtime(true);
            $memory_start = memory_get_usage(true);
        }

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

        if (Module::$_log_modules_perfs) {
            $time_end = microtime(true);
            $memory_end = memory_get_usage(true);

            Db::getInstance()->execute('
			INSERT INTO '._DB_PREFIX_.'modules_perfs (session, module, method, time_start, time_end, memory_start, memory_end)
			VALUES ('.(int)Module::$_log_modules_perfs_session.', "'.pSQL($moduleName).'", "__construct", "'.pSQL($time_start).'", "'.pSQL($time_end).'", '.(int)$memory_start.', '.(int)$memory_end.')');
        }

        return $r;
    }

    /**
     * Return an instance of the specified module
     *
     * @param int $id_module Module ID
     * @return Module instance
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getInstanceById($id_module)
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

        if (isset($id2name[$id_module])) {
            return Module::getInstanceByName($id2name[$id_module]);
        }

        return false;
    }

    /**
     * @param $string
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function configXmlStringFormat($string)
    {
        return Tools::htmlentitiesDecodeUTF8($string);
    }

    /**
     * @param $module
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getModuleName($module)
    {
        $iso = substr(Context::getContext()->language->iso_code, 0, 2);

        // Config file
        $config_file = _PS_MODULE_DIR_.$module.'/config_'.$iso.'.xml';
        // For "en" iso code, we keep the default config.xml name
        if ($iso == 'en' || !file_exists($config_file)) {
            $config_file = _PS_MODULE_DIR_.$module.'/config.xml';
            if (!file_exists($config_file)) {
                return 'Module '.ucfirst($module);
            }
        }

        // Load config.xml
        libxml_use_internal_errors(true);
        $xml_module = @simplexml_load_file($config_file);
        if (!$xml_module) {
            return 'Module '.ucfirst($module);
        }
        foreach (libxml_get_errors() as $error) {
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
        return Translate::getModuleTranslation((string)$xml_module->name, Module::configXmlStringFormat($xml_module->displayName), (string)$xml_module->name);
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function useTooMuchMemory()
    {
        $memory_limit = Tools::getMemoryLimit();
        if (function_exists('memory_get_usage') && $memory_limit != '-1') {
            $current_memory = memory_get_usage(true);
            $memory_threshold = (int)max($memory_limit * 0.15, Tools::isX86_64arch() ? 4194304 : 2097152);
            $memory_left = $memory_limit - $current_memory;

            if ($memory_left <= $memory_threshold) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return available modules
     *
     * @param bool $use_config in order to use config.xml file in module dir
     * @return array Modules
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getModulesOnDisk($use_config = false, $loggedOnAddons = false, $idEmployee = false)
    {
        global $_MODULES;

        // Init var
        $module_list = [];
        $module_name_list = [];
        $modules_name_to_cursor = [];
        $errors = [];

        // Get modules directory list and memory limit
        $modules_dir = Module::getModulesDirOnDisk();

        $modules_installed = [];
        $result = Db::getInstance()->executeS('
		SELECT m.name, m.version, mp.interest, module_shop.enable_device
		FROM `'._DB_PREFIX_.'module` m
		'.Shop::addSqlAssociation('module', 'm').'
		LEFT JOIN `'._DB_PREFIX_.'module_preference` mp ON (mp.`module` = m.`name` AND mp.`id_employee` = '.(int)$idEmployee.')');
        foreach ($result as $row) {
            $modules_installed[$row['name']] = $row;
        }

        foreach ($modules_dir as $module) {
            if (Module::useTooMuchMemory()) {
                $errors[] = Tools::displayError('All modules cannot be loaded due to memory limit restrictions, please increase your memory_limit value on your server configuration');
                break;
            }

            $iso = substr(Context::getContext()->language->iso_code, 0, 2);

            // Check if config.xml module file exists and if it's not outdated

            if ($iso == 'en') {
                $config_file = _PS_MODULE_DIR_.$module.'/config.xml';
            } else {
                $config_file = _PS_MODULE_DIR_.$module.'/config_'.$iso.'.xml';
            }

            $xml_exist = (file_exists($config_file));
            $need_new_config_file = $xml_exist ? (@filemtime($config_file) < @filemtime(_PS_MODULE_DIR_.$module.'/'.$module.'.php')) : true;

            // If config.xml exists and that the use config flag is at true
            if ($use_config && $xml_exist && !$need_new_config_file) {
                // Load config.xml
                libxml_use_internal_errors(true);
                $xml_module = @simplexml_load_file($config_file);
                if (!$xml_module) {
                    $errors[] = Tools::displayError(sprintf('%1s could not be loaded.', $config_file));
                    break;
                }
                foreach (libxml_get_errors() as $error) {
                    $errors[] = '['.$module.'] '.Tools::displayError('Error found in config file:').' '.htmlentities($error->message);
                }
                libxml_clear_errors();

                // If no errors in Xml, no need instand and no need new config.xml file, we load only translations
                if (!count($errors) && (int)$xml_module->need_instance == 0) {
                    $file = _PS_MODULE_DIR_.$module.'/'.Context::getContext()->language->iso_code.'.php';
                    if (file_exists($file) && include_once($file)) {
                        if (isset($_MODULE) && is_array($_MODULE)) {
                            $_MODULES = !empty($_MODULES) ? array_merge($_MODULES, $_MODULE) : $_MODULE;
                        }
                    }

                    $item = new stdClass();
                    $item->id = 0;
                    $item->warning = '';

                    foreach ($xml_module as $k => $v) {
                        $item->$k = (string)$v;
                    }

                    $item->displayName = stripslashes(Translate::getModuleTranslation((string)$xml_module->name, Module::configXmlStringFormat($xml_module->displayName), (string)$xml_module->name));
                    $item->description = stripslashes(Translate::getModuleTranslation((string)$xml_module->name, Module::configXmlStringFormat($xml_module->description), (string)$xml_module->name));
                    $item->author = stripslashes(Translate::getModuleTranslation((string)$xml_module->name, Module::configXmlStringFormat($xml_module->author), (string)$xml_module->name));
                    $item->author_uri = (isset($xml_module->author_uri) && $xml_module->author_uri) ? stripslashes($xml_module->author_uri) : false;

                    if (isset($xml_module->confirmUninstall)) {
                        $item->confirmUninstall = Translate::getModuleTranslation((string)$xml_module->name, html_entity_decode(Module::configXmlStringFormat($xml_module->confirmUninstall)), (string)$xml_module->name);
                    }

                    $item->active = 0;
                    $item->onclick_option = false;
                    $item->trusted = Module::isModuleTrusted($item->name);

                    $module_list[] = $item;

                    $module_name_list[] = '\''.pSQL($item->name).'\'';
                    $modules_name_to_cursor[Tools::strtolower(strval($item->name))] = $item;
                }
            }

            // If use config flag is at false or config.xml does not exist OR need instance OR need a new config.xml file
            if (!$use_config || !$xml_exist || (isset($xml_module->need_instance) && (int)$xml_module->need_instance == 1) || $need_new_config_file) {
                // If class does not exists, we include the file
                if (!class_exists($module, false)) {
                    // Get content from php file
                    $file_path = _PS_MODULE_DIR_.$module.'/'.$module.'.php';
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
                        $errors[] = sprintf(Tools::displayError('%1$s (parse error in %2$s)'), $module, substr($file_path, strlen(_PS_ROOT_DIR_)));
                    }
                }

                // If class exists, we just instanciate it
                if (class_exists($module, false)) {
                    $tmp_module = Adapter_ServiceLocator::get($module);

                    $item = new stdClass();
                    $item->id = $tmp_module->id;
                    $item->warning = $tmp_module->warning;
                    $item->name = $tmp_module->name;
                    $item->version = $tmp_module->version;
                    $item->tab = $tmp_module->tab;
                    $item->displayName = $tmp_module->displayName;
                    $item->description = stripslashes($tmp_module->description);
                    $item->author = $tmp_module->author;
                    $item->author_uri = (isset($tmp_module->author_uri) && $tmp_module->author_uri) ? $tmp_module->author_uri : false;
                    $item->limited_countries = $tmp_module->limited_countries;
                    $item->parent_class = get_parent_class($module);
                    $item->is_configurable = $tmp_module->is_configurable = method_exists($tmp_module, 'getContent') ? 1 : 0;
                    $item->need_instance = isset($tmp_module->need_instance) ? $tmp_module->need_instance : 0;
                    $item->active = $tmp_module->active;
                    $item->trusted = Module::isModuleTrusted($tmp_module->name);
                    $item->currencies = isset($tmp_module->currencies) ? $tmp_module->currencies : null;
                    $item->currencies_mode = isset($tmp_module->currencies_mode) ? $tmp_module->currencies_mode : null;
                    $item->confirmUninstall = isset($tmp_module->confirmUninstall) ? html_entity_decode($tmp_module->confirmUninstall) : null;
                    $item->description_full = stripslashes($tmp_module->description_full);
                    $item->additional_description = isset($tmp_module->additional_description) ? stripslashes($tmp_module->additional_description) : null;
                    $item->compatibility = isset($tmp_module->compatibility) ? (array)$tmp_module->compatibility : null;
                    $item->nb_rates = isset($tmp_module->nb_rates) ? (array)$tmp_module->nb_rates : null;
                    $item->avg_rate = isset($tmp_module->avg_rate) ? (array)$tmp_module->avg_rate : null;
                    $item->badges = isset($tmp_module->badges) ? (array)$tmp_module->badges : null;
                    $item->url = isset($tmp_module->url) ? $tmp_module->url : null;
                    $item->onclick_option  = method_exists($module, 'onclickOption') ? true : false;

                    if ($item->onclick_option) {
                        $href = Context::getContext()->link->getAdminLink('Module', true).'&module_name='.$tmp_module->name.'&tab_module='.$tmp_module->tab;
                        $item->onclick_option_content = [];
                        $option_tab = ['desactive', 'reset', 'configure', 'delete'];

                        foreach ($option_tab as $opt) {
                            $item->onclick_option_content[$opt] = $tmp_module->onclickOption($opt, $href);
                        }
                    }

                    $module_list[] = $item;

                    if (!$xml_exist || $need_new_config_file) {
                        self::$_generate_config_xml_mode = true;
                        $tmp_module->_generateConfigXml();
                        self::$_generate_config_xml_mode = false;
                    }

                    unset($tmp_module);
                } else {
                    $errors[] = sprintf(Tools::displayError('%1$s (class missing in %2$s)'), $module, substr($file_path, strlen(_PS_ROOT_DIR_)));
                }
            }
        }

        // Get modules information from database
        if (!empty($module_name_list)) {
            $list = Shop::getContextListShopID();
            $sql = 'SELECT m.id_module, m.name, (
						SELECT COUNT(*) FROM '._DB_PREFIX_.'module_shop ms WHERE m.id_module = ms.id_module AND ms.id_shop IN ('.implode(',', $list).')
					) as total
					FROM '._DB_PREFIX_.'module m
					WHERE LOWER(m.name) IN ('.Tools::strtolower(implode(',', $module_name_list)).')';
            $results = Db::getInstance()->executeS($sql);

            foreach ($results as $result) {
                if (isset($modules_name_to_cursor[Tools::strtolower($result['name'])])) {
                    $module_cursor = $modules_name_to_cursor[Tools::strtolower($result['name'])];
                    $module_cursor->id = (int)$result['id_module'];
                    $module_cursor->active = ($result['total'] == count($list)) ? 1 : 0;
                }
            }
        }

        // Get Default Country Modules and customer module
        $files_list = [];
        foreach ($files_list as $f) {
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
                    $content= '';
                }
                $xml = @simplexml_load_string($content, null, LIBXML_NOCDATA);

                if ($xml && isset($xml->module)) {
                    foreach ($xml->module as $modaddons) {
                        $flag_found = 0;

                        foreach ($module_list as $k => &$m) {
                            if (Tools::strtolower($m->name) == Tools::strtolower($modaddons->name) && !isset($m->available_on_addons)) {
                                $flag_found = 1;
                                if ($m->version != $modaddons->version && version_compare($m->version, $modaddons->version) === -1) {
                                    $module_list[$k]->version_addons = $modaddons->version;
                                }
                            }
                        }

                        if ($flag_found == 0) {
                            $item = new stdClass();
                            $item->id = 0;
                            $item->warning = '';
                            $item->type = strip_tags((string)$f['type']);
                            $item->name = strip_tags((string)$modaddons->name);
                            $item->version = strip_tags((string)$modaddons->version);
                            $item->tab = strip_tags((string)$modaddons->tab);
                            $item->displayName = strip_tags((string)$modaddons->displayName);
                            $item->description = stripslashes(strip_tags((string)$modaddons->description));
                            $item->description_full = stripslashes(strip_tags((string)$modaddons->description_full));
                            $item->author = strip_tags((string)$modaddons->author);
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
                            $item->compatibility = isset($modaddons->compatibility) ? (array)$modaddons->compatibility : null;
                            $item->nb_rates = isset($modaddons->nb_rates) ? (array)$modaddons->nb_rates : null;
                            $item->avg_rate = isset($modaddons->avg_rate) ? (array)$modaddons->avg_rate : null;
                            $item->badges = isset($modaddons->badges) ? (array)$modaddons->badges : null;
                            $item->url = isset($modaddons->url) ? $modaddons->url : null;

                            if (isset($modaddons->img)) {
                                if (!file_exists(_PS_TMP_IMG_DIR_.md5((int)$modaddons->id.'-'.$modaddons->name).'.jpg')) {
                                    $guzzle = new \GuzzleHttp\Client(['http_errors' => false]);
                                    try {
                                        $contents = (string) $guzzle->get($modaddons->img)->getBody();
                                    } catch (Exception $e) {
                                        $contents = null;
                                    }
                                    if (!file_put_contents(_PS_TMP_IMG_DIR_.md5((int)$modaddons->id.'-'.$modaddons->name).'.jpg', $contents)) {
                                        copy(_PS_IMG_DIR_.'404.gif', _PS_TMP_IMG_DIR_.md5((int)$modaddons->id.'-'.$modaddons->name).'.jpg');
                                    }
                                }

                                if (file_exists(_PS_TMP_IMG_DIR_.md5((int)$modaddons->id.'-'.$modaddons->name).'.jpg')) {
                                    $item->image = '../img/tmp/'.md5((int)$modaddons->id.'-'.$modaddons->name).'.jpg';
                                }
                            }

                            if ($item->type == 'addonsMustHave') {
                                $item->addons_buy_url = strip_tags((string)$modaddons->url);
                                $prices = (array)$modaddons->price;
                                $id_default_currency = Configuration::get('PS_CURRENCY_DEFAULT');

                                foreach ($prices as $currency => $price) {
                                    if ($id_currency = Currency::getIdByIsoCode($currency)) {
                                        $item->price = (float)$price;
                                        $item->id_currency = (int)$id_currency;

                                        if ($id_default_currency == $id_currency) {
                                            break;
                                        }
                                    }
                                }
                            }

                            $module_list[$modaddons->id.'-'.$item->name] = $item;
                        }
                    }
                }
            }
        }

        foreach ($module_list as $key => &$module) {
            if (defined('_PS_HOST_MODE_') && in_array($module->name, self::$hosted_modules_blacklist)) {
                unset($module_list[$key]);
            } elseif (isset($modules_installed[$module->name])) {
                $module->installed = true;
                $module->database_version = $modules_installed[$module->name]['version'];
                $module->interest = $modules_installed[$module->name]['interest'];
                $module->enable_device = $modules_installed[$module->name]['enable_device'];
            } else {
                $module->installed = false;
                $module->database_version = 0;
                $module->interest = 0;
            }
        }

        usort($module_list, create_function('$a,$b', 'return strnatcasecmp($a->displayName, $b->displayName);'));
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

        return $module_list;
    }

    /**
     * Return modules directory list
     *
     * @return array Modules Directory List
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getModulesDirOnDisk()
    {
        $module_list = [];
        $modules = scandir(_PS_MODULE_DIR_);
        foreach ($modules as $name) {
            if (is_file(_PS_MODULE_DIR_.$name)) {
                continue;
            } elseif (is_dir(_PS_MODULE_DIR_.$name.DIRECTORY_SEPARATOR) && file_exists(_PS_MODULE_DIR_.$name.'/'.$name.'.php')) {
                if (!Validate::isModuleName($name)) {
                    throw new PrestaShopException(sprintf('Module %s is not a valid module name', $name));
                }
                $module_list[] = $name;
            }
        }

        return $module_list;
    }


    /**
     * Return non native module
     *
     * @param int $position Take only positionnables modules
     * @return array Modules
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNonNativeModuleList()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM `'._DB_PREFIX_.'module`');
    }

    /**
     * @return array|bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNativeModuleList()
    {
        return self::getNonNativeModuleList();
    }

    /**
     * Return installed modules
     *
     * @param int $position Take only positionnables modules
     * @return array Modules
     *
     * @since 1.0.0
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
     * Return if the module is provided by addons.prestashop.com or not
     *
     * @param string $name The module name (the folder name)
     * @param string $key The key provided by addons
     *
     * @return int
     *
     * @deprecated 1.0.0
     */
    final public static function isModuleTrusted($moduleName)
    {
        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * Generate XML files for trusted and untrusted modules
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
     * @param string $hook_name Hook Name
     * @param array $hook_args Parameters for the functions
     *
     * @return string modules output
     *
     * @deprecated 2.0.0
     */
    public static function hookExec($hook_name, $hook_args = [], $idModule = null)
    {
        Tools::displayAsDeprecated();
        return Hook::exec($hook_name, $hook_args, $idModule);
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
     * @see PaymentModule::getInstalledPaymentModules() if you don't care about the context
     *
     * @return array module informations
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getPaymentModules()
    {
        $context = Context::getContext();
        if (isset($context->cart)) {
            $billing = new Address((int)$context->cart->id_address_invoice);
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

        $hook_payment = 'Payment';
        if (Db::getInstance()->getValue('SELECT `id_hook` FROM `'._DB_PREFIX_.'hook` WHERE `name` = \'displayPayment\'')) {
            $hook_payment = 'displayPayment';
        }

        $list = Shop::getContextListShopID();

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT DISTINCT m.`id_module`, h.`id_hook`, m.`name`, hm.`position`
		FROM `'._DB_PREFIX_.'module` m
		'.($frontend ? 'LEFT JOIN `'._DB_PREFIX_.'module_country` mc ON (m.`id_module` = mc.`id_module` AND mc.id_shop = '.(int)$context->shop->id.')' : '').'
		'.($frontend && $useGroups ? 'INNER JOIN `'._DB_PREFIX_.'module_group` mg ON (m.`id_module` = mg.`id_module` AND mg.id_shop = '.(int)$context->shop->id.')' : '').'
		'.($frontend && isset($context->customer) && $useGroups ? 'INNER JOIN `'._DB_PREFIX_.'customer_group` cg on (cg.`id_group` = mg.`id_group`AND cg.`id_customer` = '.(int)$context->customer->id.')' : '').'
		LEFT JOIN `'._DB_PREFIX_.'hook_module` hm ON hm.`id_module` = m.`id_module`
		LEFT JOIN `'._DB_PREFIX_.'hook` h ON hm.`id_hook` = h.`id_hook`
		WHERE h.`name` = \''.pSQL($hook_payment).'\'
		'.(isset($billing) && $frontend ? 'AND mc.id_country = '.(int)$billing->id_country : '').'
		AND (SELECT COUNT(*) FROM '._DB_PREFIX_.'module_shop ms WHERE ms.id_module = m.id_module AND ms.id_shop IN('.implode(', ', $list).')) = '.count($list).'
		AND hm.id_shop IN('.implode(', ', $list).')
		'.((count($groups) && $frontend && $useGroups) ? 'AND (mg.`id_group` IN ('.implode(', ', $groups).'))' : '').'
		GROUP BY hm.id_hook, hm.id_module
		ORDER BY hm.`position`, m.`name` DESC');
    }

    /**
     * @deprecated 2.0.0 Use Translate::getModuleTranslation()
     */
    public static function findTranslation($name, $string, $source)
    {
        return Translate::getModuleTranslation($name, $string, $source);
    }

    /**
     * Get translation for a given module text
     *
     * Note: $specific parameter is mandatory for library files.
     * Otherwise, translation key will not match for Module library
     * when module is loaded with eval() Module::getModulesOnDisk()
     *
     * @param string $string String to translate
     * @param bool|string $specific filename to use in translation key
     * @return string Translation
     *
     * @since 1.0.0
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
     * Reposition module
     *
     * @param bool $idHook Hook ID
     * @param bool $way    Up (0) or Down (1)
     * @param int  $position
     *
     * @since 1.0.0
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
                if ((int)$values[$this->identifier] == (int)$this->id) {
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
                $to['position'] = (int)$position;
            }

            $sql = 'UPDATE `'._DB_PREFIX_.'hook_module`
				SET `position`= position '.($way ? '-1' : '+1').'
				WHERE position between '.(int)(min([$from['position'], $to['position']])).' AND '.max([$from['position'], $to['position']]).'
				AND `id_hook` = '.(int)$from['id_hook'].' AND `id_shop` = '.$idShop;
            if (!Db::getInstance()->execute($sql)) {
                return false;
            }

            $sql = 'UPDATE `'._DB_PREFIX_.'hook_module`
				SET `position`='.(int)$to['position'].'
				WHERE `'.pSQL($this->identifier).'` = '.(int)$from[$this->identifier].'
				AND `id_hook` = '.(int)$to['id_hook'].' AND `id_shop` = '.$idShop;
            if (!Db::getInstance()->execute($sql)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Reorder modules position
     *
     * @param bool  $idHook   Hook ID
     * @param array $shopList List of shop
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function cleanPositions($idHook, $shopList = null)
    {
        $sql = 'SELECT `id_module`, `id_shop`
			FROM `'._DB_PREFIX_.'hook_module`
			WHERE `id_hook` = '.(int)$idHook.'
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
				WHERE `id_hook` = '.(int)$idHook.'
				AND `id_module` = '.$row['id_module'].' AND `id_shop` = '.$row['id_shop'];
            Db::getInstance()->execute($sql);
            $position[$row['id_shop']]++;
        }

        return true;
    }

    /**
     * Helper displaying error message(s)
     * @param string|array $error
     * @return string
     *
     * @since 1.0.0
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
    * @param string|array $error
    * @return string
     *
     * @since 1.0.0
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
     * @since 1.0.0
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
     * @param int $id_module Module ID
     * @param int $id_hook Hook ID
     * @return array Exceptions
     *
     *               @since 1.0.0
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
     * Return exceptions for module in hook
     *
     * @param int $idHook Hook ID
     *
     * @return array Exceptions
     *
     *               @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getExceptions($idHook, $dispatch = false)
    {
        return Module::getExceptionsStatic($this->id, $idHook, $dispatch);
    }

    /**
     * @param $module_name
     *
     * @return bool|null
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isInstalled($module_name)
    {
        if (!Cache::isStored('Module::isInstalled'.$module_name)) {
            $id_module = Module::getModuleIdByName($module_name);
            Cache::store('Module::isInstalled'.$module_name, (bool)$id_module);
            return (bool)$id_module;
        }
        return Cache::retrieve('Module::isInstalled'.$module_name);
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function isEnabledForShopContext()
    {
        return (bool) Db::getInstance()->getValue('
			SELECT COUNT(*) n
			FROM `'._DB_PREFIX_.'module_shop`
			WHERE id_module='.(int)$this->id.' AND id_shop IN ('.implode(',', array_map('intval', Shop::getContextListShopID())).')
			GROUP BY id_module
			HAVING n='.(int)count(Shop::getContextListShopID())
        );
    }

    /**
     * @param $module_name
     *
     * @return bool|null
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isEnabled($module_name)
    {
        if (!Cache::isStored('Module::isEnabled'.$module_name)) {
            $active = false;
            $id_module = Module::getModuleIdByName($module_name);
            if (Db::getInstance()->getValue('SELECT `id_module` FROM `'._DB_PREFIX_.'module_shop` WHERE `id_module` = '.(int)$id_module.' AND `id_shop` = '.(int)Context::getContext()->shop->id)) {
                $active = true;
            }
            Cache::store('Module::isEnabled'.$module_name, (bool)$active);
            return (bool)$active;
        }
        return Cache::retrieve('Module::isEnabled'.$module_name);
    }

    /**
     * @param $hook
     *
     * @return bool|false|null|string
     *
     * @since 1.0.0
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
			WHERE h.`name` = \''.pSQL($hook).'\' AND hm.`id_module` = '.(int)$this->id;
        return Db::getInstance()->getValue($sql);
    }

    /**
     * @param $module_name
     * @param $template
     *
     * @return bool|null|string
     *
     * @since 1.0.0
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
     * @param $template
     *
     * @return bool|null|string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _isTemplateOverloaded($template)
    {
        return Module::_isTemplateOverloadedStatic($this->name, $template);
    }

    /**
     * @param null $name
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getCacheId($name = null)
    {
        $cache_array = [];
        $cache_array[] = $name !== null ? $name : $this->name;
        if (Configuration::get('PS_SSL_ENABLED')) {
            $cache_array[] = (int)Tools::usingSecureMode();
        }
        if (Shop::isFeatureActive()) {
            $cache_array[] = (int)$this->context->shop->id;
        }
        if (Group::isFeatureActive() && isset($this->context->customer)) {
            $cache_array[] = (int)Group::getCurrent()->id;
            $cache_array[] = implode('_', Customer::getGroupsStatic($this->context->customer->id));
        }
        if (Language::isMultiLanguageActivated()) {
            $cache_array[] = (int)$this->context->language->id;
        }
        if (Currency::isMultiCurrencyActivated()) {
            $cache_array[] = (int)$this->context->currency->id;
        }
        $cache_array[] = (int)$this->context->country->id;
        return implode('|', $cache_array);
    }

    /**
     * @param      $file
     * @param      $template
     * @param null $cache_id
     * @param null $compile_id
     *
     * @return string
     *
     * @since 1.0.0
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
     * @param string $template
     * @param string|null $cache_id
     * @param string|null $compile_id
     * @return Smarty_Internal_Template
     *
     * @since 1.0.0
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

    protected function resetCurrentSubTemplate($template, $cache_id, $compile_id)
    {
        $this->current_subtemplate[$template.'_'.$cache_id.'_'.$compile_id] = null;
    }

    /**
     * Get realpath of a template of current module (check if template is overriden too)
     *
     * @param string $template
     * @return string
     *
     * @since 1.0.0
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
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _getApplicableTemplateDir($template)
    {
        return $this->_isTemplateOverloaded($template) ? _PS_THEME_DIR_ : _PS_MODULE_DIR_.$this->name.'/';
    }

    /**
     * @param      $template
     * @param null $cacheId
     * @param null $compileId
     *
     * @return bool
     *
     * @since 1.0.0
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
     * Clear template cache
     *
     * @param string $template Template name
     * @param int null $cacheId
     * @param int null $compileId
     *
     * @return int Number of template cleared
     *
     * @since 1.0.0
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
    }

    /**
     * Clear deferred template cache
     *
     * @param string $templatePath Template path
     * @param int|null $cacheId
     * @param int|null $compileId
     *
     * @return int Number of template cleared
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function _deferedClearCache($templatePath, $cacheId, $compileId)
    {
        Tools::enableCache();
        $number_of_template_cleared = Tools::clearCache(Context::getContext()->smarty, $templatePath, $cacheId, $compileId);
        Tools::restoreCacheSettings();

        return $number_of_template_cleared;
    }

    /**
     * @since 1.0.0
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
	<is_configurable>'.(isset($this->is_configurable) ? (int)$this->is_configurable : 0).'</is_configurable>
	<need_instance>'.(int)$this->need_instance.'</need_instance>'.(isset($this->limited_countries) ? "\n\t".'<limited_countries>'.(count($this->limited_countries) == 1 ? $this->limited_countries[0] : '').'</limited_countries>' : '').'
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
     * Check if the module is transplantable on the hook in parameter
     *
     * @param string $hook_name
     * @return bool if module can be transplanted on hook
     *
     * @since 1.0.0
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
     * @param array $variable (action)
     * @param object $employee
     *
     * @return bool if module can be transplanted on hook
     *
     * @since 1.0.0
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
     * @param array  $variable (action)
     * @param object $employee
     *
     * @return bool if module can be transplanted on hook
     *
     * @since 1.0.0
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
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT `id_module`, `view`, `configure`, `uninstall` FROM `'._DB_PREFIX_.'module_access` WHERE `id_profile` = '.(int)$employee->id_profile);
            foreach ($result as $row) {
                self::$cache_permissions[$employee->id_profile][$row['id_module']]['view'] = $row['view'];
                self::$cache_permissions[$employee->id_profile][$row['id_module']]['configure'] = $row['configure'];
                self::$cache_permissions[$employee->id_profile][$row['id_module']]['uninstall'] = $row['uninstall'];
            }
        }

        if (!isset(self::$cache_permissions[$employee->id_profile][$idModule])) {
            throw new PrestaShopException('No access reference in table module_access for id_module '.$idModule.'.');
        }

        return (bool)self::$cache_permissions[$employee->id_profile][$idModule][$variable];
    }

    /**
     * Get Unauthorized modules for a client group
     *
     * @param int $groupId
     *
     * @return array|null
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAuthorizedModules($groupId)
    {
        return Db::getInstance()->executeS('
		SELECT m.`id_module`, m.`name` FROM `'._DB_PREFIX_.'module_group` mg
		LEFT JOIN `'._DB_PREFIX_.'module` m ON (m.`id_module` = mg.`id_module`)
		WHERE mg.`id_group` = '.(int) $groupId);
    }

    /**
     * Get ID module by name
     *
     * @param string $name
     * @return int Module ID
     *
     * @since 1.0.0
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
     * @return int position
     *
     * @since 1.0.0
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
        $result = Db::getInstance()->getRow('
			SELECT `position`
			FROM `'._DB_PREFIX_.'hook_module`
			WHERE `id_hook` = '.(int) $id_hook.'
			AND `id_module` = '.(int) $this->id.'
			AND `id_shop` = '.(int) Context::getContext()->shop->id);

        return $result['position'];
    }

    /**
     * add a warning message to display at the top of the admin page
     *
     * @param string $msg
     */
    public function adminDisplayWarning($msg)
    {
        if (!($this->context->controller instanceof AdminController)) {
            return false;
        }
        $this->context->controller->warnings[] = $msg;
    }

    /**
     * add a info message to display at the top of the admin page
     *
     * @param string $msg
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function adminDisplayInformation($msg)
    {
        if (!($this->context->controller instanceof AdminController)) {
            return false;
        }
        $this->context->controller->informations[] = $msg;
    }

    /**
     * Install module's controllers using public property $controllers
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function installControllers()
    {
        $themes = Theme::getThemes();
        $theme_meta_value = [];
        foreach ($this->controllers as $controller) {
            $page = 'module-'.$this->name.'-'.$controller;
            $result = Db::getInstance()->getValue('SELECT * FROM '._DB_PREFIX_.'meta WHERE page="'.pSQL($page).'"');
            if ((int)$result > 0) {
                continue;
            }

            $meta = new Meta();
            $meta->page = $page;
            $meta->configurable = 1;
            $meta->save();
            if ((int)$meta->id > 0) {
                foreach ($themes as $theme) {
                    /** @var Theme $theme */
                    $theme_meta_value[] = [
                        'id_theme' => $theme->id,
                        'id_meta' => $meta->id,
                        'left_column' => (int)$theme->default_left_column,
                        'right_column' => (int)$theme->default_right_column
                    ];
                }
            } else {
                $this->_errors[] = sprintf(Tools::displayError('Unable to install controller: %s'), $controller);
            }
        }
        if (count($theme_meta_value) > 0) {
            return Db::getInstance()->insert('theme_meta', $theme_meta_value);
        }

        return true;
    }

    /**
     * Install overrides files for the module
     *
     * @return bool
     *
     * @since 1.0.0
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
     * Uninstall overrides files for the module
     *
     * @return bool
     *
     * @since 1.0.0
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
     * Add all methods in a module override to the override class
     *
     * @param string $classname
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function addOverride($classname)
    {
        $orig_path = $path = PrestaShopAutoload::getInstance()->getClassPath($classname.'Core');
        if (!$path) {
            $path = 'modules'.DIRECTORY_SEPARATOR.$classname.DIRECTORY_SEPARATOR.$classname.'.php';
        }
        $path_override = $this->getLocalPath().'override'.DIRECTORY_SEPARATOR.$path;

        if (!file_exists($path_override)) {
            return false;
        } else {
            file_put_contents($path_override, preg_replace('#(\r\n|\r)#ism', "\n", file_get_contents($path_override)));
        }

        $pattern_escape_com = '#(^\s*?\/\/.*?\n|\/\*(?!\n\s+\* module:.*?\* date:.*?\* version:.*?\*\/).*?\*\/)#ism';
        // Check if there is already an override file, if not, we just need to copy the file
        if ($file = PrestaShopAutoload::getInstance()->getClassPath($classname)) {
            // Check if override file is writable
            $override_path = _PS_ROOT_DIR_.'/'.$file;

            if ((!file_exists($override_path) && !is_writable(dirname($override_path))) || (file_exists($override_path) && !is_writable($override_path))) {
                throw new Exception(sprintf(Tools::displayError('file (%s) not writable'), $override_path));
            }

            // Get a uniq id for the class, because you can override a class (or remove the override) twice in the same session and we need to avoid redeclaration
            do {
                $uniq = uniqid();
            } while (class_exists($classname.'OverrideOriginal_remove', false));

            // Make a reflection of the override class and the module override class
            $override_file = file($override_path);
            $override_file = array_diff($override_file, ["\n"]);
            eval(preg_replace(['#^\s*<\?(?:php)?#', '#class\s+'.$classname.'\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?#i'], [' ', 'class '.$classname.'OverrideOriginal'.$uniq], implode('', $override_file)));
            $override_class = new ReflectionClass($classname.'OverrideOriginal'.$uniq);

            $module_file = file($path_override);
            $module_file = array_diff($module_file, ["\n"]);
            eval(preg_replace(['#^\s*<\?(?:php)?#', '#class\s+'.$classname.'(\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?)?#i'], [' ', 'class '.$classname.'Override'.$uniq], implode('', $module_file)));
            $module_class = new ReflectionClass($classname.'Override'.$uniq);

            // Check if none of the methods already exists in the override class
            foreach ($module_class->getMethods() as $method) {
                if ($override_class->hasMethod($method->getName())) {
                    $method_override = $override_class->getMethod($method->getName());
                    if (preg_match('/module: (.*)/ism', $override_file[$method_override->getStartLine() - 5], $name) && preg_match('/date: (.*)/ism', $override_file[$method_override->getStartLine() - 4], $date) && preg_match('/version: ([0-9.]+)/ism', $override_file[$method_override->getStartLine() - 3], $version)) {
                        throw new Exception(sprintf(Tools::displayError('The method %1$s in the class %2$s is already overridden by the module %3$s version %4$s at %5$s.'), $method->getName(), $classname, $name[1], $version[1], $date[1]));
                    }
                    throw new Exception(sprintf(Tools::displayError('The method %1$s in the class %2$s is already overridden.'), $method->getName(), $classname));
                }

                $module_file = preg_replace('/((:?public|private|protected)\s+(static\s+)?function\s+(?:\b'.$method->getName().'\b))/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1", $module_file);
                if ($module_file === null) {
                    throw new Exception(sprintf(Tools::displayError('Failed to override method %1$s in class %2$s.'), $method->getName(), $classname));
                }
            }

            // Check if none of the properties already exists in the override class
            foreach ($module_class->getProperties() as $property) {
                if ($override_class->hasProperty($property->getName())) {
                    throw new Exception(sprintf(Tools::displayError('The property %1$s in the class %2$s is already defined.'), $property->getName(), $classname));
                }

                $module_file = preg_replace('/((?:public|private|protected)\s)\s*(static\s)?\s*(\$\b'.$property->getName().'\b)/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1$2$3", $module_file);
                if ($module_file === null) {
                    throw new Exception(sprintf(Tools::displayError('Failed to override property %1$s in class %2$s.'), $property->getName(), $classname));
                }
            }

            foreach ($module_class->getConstants() as $constant => $value) {
                if ($override_class->hasConstant($constant)) {
                    throw new Exception(sprintf(Tools::displayError('The constant %1$s in the class %2$s is already defined.'), $constant, $classname));
                }

                $module_file = preg_replace('/(const\s)\s*(\b'.$constant.'\b)/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1$2", $module_file);
                if ($module_file === null) {
                    throw new Exception(sprintf(Tools::displayError('Failed to override constant %1$s in class %2$s.'), $constant, $classname));
                }
            }

            // Insert the methods from module override in override
            $copy_from = array_slice($module_file, $module_class->getStartLine() + 1, $module_class->getEndLine() - $module_class->getStartLine() - 2);
            array_splice($override_file, $override_class->getEndLine() - 1, 0, $copy_from);
            $code = implode('', $override_file);

            file_put_contents($override_path, preg_replace($pattern_escape_com, '', $code));
        } else {
            $override_src = $path_override;

            $override_dest = _PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'override'.DIRECTORY_SEPARATOR.$path;
            $dir_name = dirname($override_dest);

            if (!$orig_path && !is_dir($dir_name)) {
                $oldumask = umask(0000);
                @mkdir($dir_name, 0777);
                umask($oldumask);
            }

            if (!is_writable($dir_name)) {
                throw new Exception(sprintf(Tools::displayError('directory (%s) not writable'), $dir_name));
            }
            $module_file = file($override_src);
            $module_file = array_diff($module_file, ["\n"]);

            if ($orig_path) {
                do {
                    $uniq = uniqid();
                } while (class_exists($classname.'OverrideOriginal_remove', false));
                eval(preg_replace(['#^\s*<\?(?:php)?#', '#class\s+'.$classname.'(\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?)?#i'], [' ', 'class '.$classname.'Override'.$uniq], implode('', $module_file)));
                $module_class = new ReflectionClass($classname.'Override'.$uniq);

                // For each method found in the override, prepend a comment with the module name and version
                foreach ($module_class->getMethods() as $method) {
                    $module_file = preg_replace('/((:?public|private|protected)\s+(static\s+)?function\s+(?:\b'.$method->getName().'\b))/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1", $module_file);
                    if ($module_file === null) {
                        throw new Exception(sprintf(Tools::displayError('Failed to override method %1$s in class %2$s.'), $method->getName(), $classname));
                    }
                }

                // Same loop for properties
                foreach ($module_class->getProperties() as $property) {
                    $module_file = preg_replace('/((?:public|private|protected)\s)\s*(static\s)?\s*(\$\b'.$property->getName().'\b)/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1$2$3", $module_file);
                    if ($module_file === null) {
                        throw new Exception(sprintf(Tools::displayError('Failed to override property %1$s in class %2$s.'), $property->getName(), $classname));
                    }
                }

                // Same loop for constants
                foreach ($module_class->getConstants() as $constant => $value) {
                    $module_file = preg_replace('/(const\s)\s*(\b'.$constant.'\b)/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1$2", $module_file);
                    if ($module_file === null) {
                        throw new Exception(sprintf(Tools::displayError('Failed to override constant %1$s in class %2$s.'), $constant, $classname));
                    }
                }
            }

            file_put_contents($override_dest, preg_replace($pattern_escape_com, '', $module_file));

            // Re-generate the class index
            Tools::generateIndex();
        }
        return true;
    }

    /**
     * Remove all methods in a module override from the override class
     *
     * @param string $classname
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function removeOverride($classname)
    {
        $orig_path = $path = PrestaShopAutoload::getInstance()->getClassPath($classname.'Core');

        if ($orig_path && !$file = PrestaShopAutoload::getInstance()->getClassPath($classname)) {
            return true;
        } elseif (!$orig_path && Module::getModuleIdByName($classname)) {
            $path = 'modules'.DIRECTORY_SEPARATOR.$classname.DIRECTORY_SEPARATOR.$classname.'.php';
        }

        // Check if override file is writable
        if ($orig_path) {
            $override_path = _PS_ROOT_DIR_.'/'.$file;
        } else {
            $override_path = _PS_OVERRIDE_DIR_.$path;
        }

        if (!is_file($override_path) || !is_writable($override_path)) {
            return false;
        }

        file_put_contents($override_path, preg_replace('#(\r\n|\r)#ism', "\n", file_get_contents($override_path)));

        if ($orig_path) {
            // Get a uniq id for the class, because you can override a class (or remove the override) twice in the same session and we need to avoid redeclaration
            do {
                $uniq = uniqid();
            } while (class_exists($classname.'OverrideOriginal_remove', false));

            // Make a reflection of the override class and the module override class
            $override_file = file($override_path);

            eval(preg_replace(['#^\s*<\?(?:php)?#', '#class\s+'.$classname.'\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?#i'], [' ', 'class '.$classname.'OverrideOriginal_remove'.$uniq], implode('', $override_file)));
            $override_class = new ReflectionClass($classname.'OverrideOriginal_remove'.$uniq);

            $module_file = file($this->getLocalPath().'override/'.$path);
            eval(preg_replace(['#^\s*<\?(?:php)?#', '#class\s+'.$classname.'(\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?)?#i'], [' ', 'class '.$classname.'Override_remove'.$uniq], implode('', $module_file)));
            $module_class = new ReflectionClass($classname.'Override_remove'.$uniq);

            // Remove methods from override file
            foreach ($module_class->getMethods() as $method) {
                if (!$override_class->hasMethod($method->getName())) {
                    continue;
                }

                $method = $override_class->getMethod($method->getName());
                $length = $method->getEndLine() - $method->getStartLine() + 1;

                $module_method = $module_class->getMethod($method->getName());
                $module_length = $module_method->getEndLine() - $module_method->getStartLine() + 1;

                $override_file_orig = $override_file;

                $orig_content = preg_replace('/\s/', '', implode('', array_splice($override_file, $method->getStartLine() - 1, $length, array_pad([], $length, '#--remove--#'))));
                $module_content = preg_replace('/\s/', '', implode('', array_splice($module_file, $module_method->getStartLine() - 1, $length, array_pad([], $length, '#--remove--#'))));

                $replace = true;
                if (preg_match('/\* module: ('.$this->name.')/ism', $override_file[$method->getStartLine() - 5])) {
                    $override_file[$method->getStartLine() - 6] = $override_file[$method->getStartLine() - 5] = $override_file[$method->getStartLine() - 4] = $override_file[$method->getStartLine() - 3] = $override_file[$method->getStartLine() - 2] = '#--remove--#';
                    $replace = false;
                }

                if (md5($module_content) != md5($orig_content) && $replace) {
                    $override_file = $override_file_orig;
                }
            }

            // Remove properties from override file
            foreach ($module_class->getProperties() as $property) {
                if (!$override_class->hasProperty($property->getName())) {
                    continue;
                }

                // Replace the declaration line by #--remove--#
                foreach ($override_file as $line_number => &$line_content) {
                    if (preg_match('/(public|private|protected)\s+(static\s+)?(\$)?'.$property->getName().'/i', $line_content)) {
                        if (preg_match('/\* module: ('.$this->name.')/ism', $override_file[$line_number - 4])) {
                            $override_file[$line_number - 5] = $override_file[$line_number - 4] = $override_file[$line_number - 3] = $override_file[$line_number - 2] = $override_file[$line_number - 1] = '#--remove--#';
                        }
                        $line_content = '#--remove--#';
                        break;
                    }
                }
            }

            // Remove properties from override file
            foreach ($module_class->getConstants() as $constant => $value) {
                if (!$override_class->hasConstant($constant)) {
                    continue;
                }

                // Replace the declaration line by #--remove--#
                foreach ($override_file as $line_number => &$line_content) {
                    if (preg_match('/(const)\s+(static\s+)?(\$)?'.$constant.'/i', $line_content)) {
                        if (preg_match('/\* module: ('.$this->name.')/ism', $override_file[$line_number - 4])) {
                            $override_file[$line_number - 5] = $override_file[$line_number - 4] = $override_file[$line_number - 3] = $override_file[$line_number - 2] = $override_file[$line_number - 1] = '#--remove--#';
                        }
                        $line_content = '#--remove--#';
                        break;
                    }
                }
            }

            $count = count($override_file);
            for ($i = 0; $i < $count; ++$i) {
                if (preg_match('/(^\s*\/\/.*)/i', $override_file[$i])) {
                    $override_file[$i] = '#--remove--#';
                } elseif (preg_match('/(^\s*\/\*)/i', $override_file[$i])) {
                    if (!preg_match('/(^\s*\* module:)/i', $override_file[$i + 1])
                        && !preg_match('/(^\s*\* date:)/i', $override_file[$i + 2])
                        && !preg_match('/(^\s*\* version:)/i', $override_file[$i + 3])
                        && !preg_match('/(^\s*\*\/)/i', $override_file[$i + 4])) {
                        for (; $override_file[$i] && !preg_match('/(.*?\*\/)/i', $override_file[$i]); ++$i) {
                            $override_file[$i] = '#--remove--#';
                        }
                        $override_file[$i] = '#--remove--#';
                    }
                }
            }

            // Rewrite nice code
            $code = '';
            foreach ($override_file as $line) {
                if ($line == '#--remove--#') {
                    continue;
                }

                $code .= $line;
            }

            $to_delete = preg_match('/<\?(?:php)?\s+(?:abstract|interface)?\s*?class\s+'.$classname.'\s+extends\s+'.$classname.'Core\s*?[{]\s*?[}]/ism', $code);
        }

        if (!isset($to_delete) || $to_delete) {
            unlink($override_path);
        } else {
            file_put_contents($override_path, $code);
        }

        // Re-generate the class index
        Tools::generateIndex();

        return true;
    }

    /**
     * Return the hooks list where this module can be hooked.
     *
     * @return array Hooks list.
     *
     * @since 1.0.0
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
                    'name' => $hook_name,
                    'title' => $current_hook['title'],
                ];
            }
        }

        return $possible_hooks_list;
    }
}

function ps_module_version_sort($a, $b)
{
    return version_compare($a['version'], $b['version']);
}
