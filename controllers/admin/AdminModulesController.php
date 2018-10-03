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
 * Class AdminModulesControllerCore
 *
 * @since 1.0.0
 */
class AdminModulesControllerCore extends AdminController
{
    // @codingStandardsIgnoreStart
    /** @var array map with $_GET keywords and their callback */
    protected $map = [
        'check'          => 'check',
        'install'        => 'install',
        'uninstall'      => 'uninstall',
        'configure'      => 'getContent',
        'update'         => 'update',
        'delete'         => 'delete',
        'checkAndUpdate' => 'checkAndUpdate',
        'updateAll'      => 'updateAll',
    ];
    /** @var array $list_modules_categories */
    protected $list_modules_categories = [];
    /** @var array $list_partners_modules */
    protected $list_partners_modules = [];
    /** @var array $list_natives_modules */
    protected $list_natives_modules = [];
    /** @var int $nb_modules_total */
    protected $nb_modules_total = 0;
    /** @var int $nb_modules_installed */
    protected $nb_modules_installed = 0;
    /** @var int $nb_modules_activated */
    protected $nb_modules_activated = 0;
    /** @var string $serial_modules */
    protected $serial_modules = '';
    /** @var array $modules_authors */
    protected $modules_authors = [];
    /** @var int $id_employee */
    protected $id_employee;
    /** @var string $iso_default_country */
    protected $iso_default_country;
    /** @var array $filter_configuration */
    protected $filter_configuration = [];
    /**
     * @var string $xml_modules_list
     *
     * @deprecated 1.0.1 DO NOT USE THIS!
     */
    protected $xml_modules_list = '';
    // @codingStandardsIgnoreEnd

    /**
     * Admin Modules Controller Constructor
     * Init list modules categories
     * Load id employee
     * Load filter configuration
     * Load cache file
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        register_shutdown_function('displayFatalError');

        // Set the modules categories
        $this->list_modules_categories['administration']['name'] = $this->l('Administration');
        $this->list_modules_categories['advertising_marketing']['name'] = $this->l('Advertising and Marketing');
        $this->list_modules_categories['analytics_stats']['name'] = $this->l('Analytics and Stats');
        $this->list_modules_categories['billing_invoicing']['name'] = $this->l('Taxes & Invoicing');
        $this->list_modules_categories['checkout']['name'] = $this->l('Checkout');
        $this->list_modules_categories['content_management']['name'] = $this->l('Content Management');
        $this->list_modules_categories['customer_reviews']['name'] = $this->l('Customer Reviews');
        $this->list_modules_categories['export']['name'] = $this->l('Export');
        $this->list_modules_categories['emailing']['name'] = $this->l('Emailing');
        $this->list_modules_categories['front_office_features']['name'] = $this->l('Front office Features');
        $this->list_modules_categories['i18n_localization']['name'] = $this->l('Internationalization and Localization');
        $this->list_modules_categories['merchandizing']['name'] = $this->l('Merchandising');
        $this->list_modules_categories['migration_tools']['name'] = $this->l('Migration Tools');
        $this->list_modules_categories['payments_gateways']['name'] = $this->l('Payments and Gateways');
        $this->list_modules_categories['payment_security']['name'] = $this->l('Site certification & Fraud prevention');
        $this->list_modules_categories['pricing_promotion']['name'] = $this->l('Pricing and Promotion');
        $this->list_modules_categories['quick_bulk_update']['name'] = $this->l('Quick / Bulk update');
        $this->list_modules_categories['search_filter']['name'] = $this->l('Search and Filter');
        $this->list_modules_categories['seo']['name'] = $this->l('SEO');
        $this->list_modules_categories['shipping_logistics']['name'] = $this->l('Shipping and Logistics');
        $this->list_modules_categories['slideshows']['name'] = $this->l('Slideshows');
        $this->list_modules_categories['smart_shopping']['name'] = $this->l('Comparison site & Feed management');
        $this->list_modules_categories['market_place']['name'] = $this->l('Marketplace');
        $this->list_modules_categories['others']['name'] = $this->l('Other Modules');
        $this->list_modules_categories['mobile']['name'] = $this->l('Mobile');
        $this->list_modules_categories['dashboard']['name'] = $this->l('Dashboard');
        $this->list_modules_categories['i18n_localization']['name'] = $this->l('Internationalization & Localization');
        $this->list_modules_categories['emailing']['name'] = $this->l('Emailing & SMS');
        $this->list_modules_categories['social_networks']['name'] = $this->l('Social Networks');
        $this->list_modules_categories['social_community']['name'] = $this->l('Social & Community');

        uasort($this->list_modules_categories, [$this, 'checkCategoriesNames']);

        // Set Id Employee, Iso Default Country and Filter Configuration
        $this->id_employee = (int) $this->context->employee->id;
        $this->iso_default_country = $this->context->country->iso_code;
        $this->filter_configuration = Configuration::getMultiple(
            [
                'PS_SHOW_TYPE_MODULES_'.(int) $this->id_employee,
                'PS_SHOW_COUNTRY_MODULES_'.(int) $this->id_employee,
                'PS_SHOW_INSTALLED_MODULES_'.(int) $this->id_employee,
                'PS_SHOW_ENABLED_MODULES_'.(int) $this->id_employee,
                'PS_SHOW_CAT_MODULES_'.(int) $this->id_employee,
            ]
        );

        if (!Module::isEnabled('tbupdater')) {
            // Linking directly to the install/enable action would be frivolous.
            $linkOpen = '<a href="'
                        .$this->context->link->getAdminLink('AdminModules')
                        .'&anchor=tbupdater">';
            $linkClose = '</a>';

            if (Module::isInstalled('tbupdater')) {
                $this->warnings[] = sprintf($this->l('The thirty bees updater module has not been enabled. %sGet it enabled%s to keep thirty bees updated.'), $linkOpen, $linkClose);
            } else {
                $this->warnings[] = sprintf($this->l('The thirty bees updater module is not installed. %sGet it installed%s to keep thirty bees updated.'), $linkOpen, $linkClose);
            }
        }
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function checkCategoriesNames($a, $b)
    {
        if ($a['name'] === $this->l('Other Modules')) {
            return true;
        }

        return (bool) ($a['name'] > $b['name']);
    }

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryPlugin(['autocomplete', 'fancybox', 'tablefilter']);
    }

    /**
     * @param bool $forceReloadCache
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessRefreshModuleList($forceReloadCache = false)
    {
        $module = Module::getInstanceByName('tbupdater');

        if (Validate::isLoadedObject($module)) {
            /** @var TbUpdater $module */
            $module->checkForUpdates($forceReloadCache);
        }
    }

    /**
     * @return void
     *
     * @since 1.0.0
     */
    public function displayAjaxRefreshModuleList()
    {
        $this->ajaxDie(json_encode(['status' => $this->status]));
    }

    /**
     * @deprecated 1.0.0
     */
    public function ajaxProcessLogOnAddonsWebservices()
    {
        die('OK');
    }

    /**
     * @return void
     *
     * @deprecated 1.0.0
     */
    public function ajaxProcessLogOutAddonsWebservices()
    {
        $this->context->cookie->username_addons = '';
        $this->context->cookie->password_addons = '';
        $this->context->cookie->is_contributor = 0;
        $this->context->cookie->write();
        die('OK');
    }

    /**
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessReloadModulesList()
    {
        if (Tools::getValue('filterCategory')) {
            Configuration::updateValue('PS_SHOW_CAT_MODULES_'.(int) $this->id_employee, Tools::getValue('filterCategory'));
        }
        if (Tools::getValue('unfilterCategory')) {
            Configuration::updateValue('PS_SHOW_CAT_MODULES_'.(int) $this->id_employee, '');
        }

        $this->initContent();
        $this->smartyOutputContent('controllers/modules/list.tpl');
        exit;
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        if (Tools::isSubmit('addnewmodule') && $this->context->mode == Context::MODE_HOST) {
            $this->display = 'add';
            $this->context->smarty->assign(['iso_code' => $this->context->language->iso_code]);
            parent::initContent();

            return;
        }

        // If we are on a module configuration, no need to load all modules
        if (Tools::getValue('configure') != '') {
            $this->context->smarty->assign(['maintenance_mode' => !(bool) Configuration::Get('PS_SHOP_ENABLE')]);

            return;
        }

        $this->initToolbar();
        $this->initPageHeaderToolbar();

        // Init
        $smarty = $this->context->smarty;
        $autocompleteList = 'var moduleList = [';
        $categoryFiltered = [];
        $filterCategories = explode('|', Configuration::get('PS_SHOW_CAT_MODULES_'.(int) $this->id_employee));
        if (count($filterCategories) > 0) {
            foreach ($filterCategories as $fc) {
                if (!empty($fc)) {
                    $categoryFiltered[$fc] = 1;
                }
            }
        }

        if (empty($categoryFiltered) && Tools::getValue('tab_module')) {
            $categoryFiltered[Tools::getValue('tab_module')] = 1;
        }

        foreach ($this->list_modules_categories as $k => $v) {
            $this->list_modules_categories[$k]['nb'] = 0;
        }

        // Retrieve Modules Preferences
        $modulesPreferences = [];
        $tabModulesPreferences = [];
        $modulesPreferencesTmp = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('*')
            ->from('module_preference')
            ->where('`id_employee` = '.(int) $this->id_employee)
        );
        $tabModulesPreferencesTmp = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('*')
            ->from('tab_module_preference')
            ->where('`id_employee` = '.(int) $this->employee)
        );

        foreach ($tabModulesPreferencesTmp as $i => $j) {
            $tabModulesPreferences[$j['module']][] = $j['id_tab'];
        }

        foreach ($modulesPreferencesTmp as $k => $v) {
            if ($v['interest'] == null) {
                unset($v['interest']);
            }
            if ($v['favorite'] == null) {
                unset($v['favorite']);
            }
            $modulesPreferences[$v['module']] = $v;
        }

        // Retrieve Modules List
        $modules = Module::getModulesOnDisk(true, $this->logged_on_addons, $this->id_employee);
        $this->initModulesList($modules);
        $this->nb_modules_total = count($modules);
        $moduleErrors = [];
        $moduleSuccess = [];
        $upgradeAvailable = [];
        $dontFilter = false;

        //Add success message for one module update
        if (Tools::getValue('updated') && Tools::getValue('module_name')) {
            $moduleNames = (string) Tools::getValue('module_name');
            if (strpos($moduleNames, '|')) {
                $moduleNames = explode('|', $moduleNames);
                $dontFilter = true;
            }

            if (!is_array($moduleNames)) {
                $moduleNames = (array) $moduleNames;
            }

            foreach ($modules as $km => $module) {
                if (in_array($module->name, $moduleNames)) {
                    $moduleSuccess[] = [
                        'name' => $module->displayName, 'message' => [
                            0 => sprintf($this->l('Current version: %s'), $module->version),
                        ],
                    ];
                }
            }
        }

        if (Tools::getValue('allUpdated')) {
            $this->confirmations[] = $this->l('All modules updated successfully.');
        }

        // Browse modules list
        foreach ($modules as $km => $module) {
            //if we are in favorites view we only display installed modules
            if (Tools::getValue('select') == 'favorites' && !$module->id) {
                unset($modules[$km]);
                continue;
            }

            // Upgrade Module process, init check if a module could be upgraded
            if (Module::initUpgradeModule($module)) {
                // When the XML cache file is up-to-date, the module may not be loaded yet
                if (!class_exists($module->name)) {
                    if (!file_exists(_PS_MODULE_DIR_.$module->name.'/'.$module->name.'.php')) {
                        continue;
                    }
                    require_once(_PS_MODULE_DIR_.$module->name.'/'.$module->name.'.php');
                }

                if ($object = Adapter_ServiceLocator::get($module->name)) {
                    /** @var Module $object */
                    $object->runUpgradeModule();
                    if ((count($errorsModuleList = $object->getErrors()))) {
                        $moduleErrors[] = ['name' => $module->displayName, 'message' => $errorsModuleList];
                    } elseif ((count($confModuleList = $object->getConfirmations()))) {
                        $moduleSuccess[] = ['name' => $module->displayName, 'message' => $confModuleList];
                    }
                    unset($object);
                }
            } elseif (Module::getUpgradeStatus($module->name)) {
                // Module can't be upgraded if not file exist but can change the database version...
                // User has to be prevented
                // When the XML cache file is up-to-date, the module may not be loaded yet
                if (!class_exists($module->name)) {
                    if (file_exists(_PS_MODULE_DIR_.$module->name.'/'.$module->name.'.php')) {
                        require_once(_PS_MODULE_DIR_.$module->name.'/'.$module->name.'.php');
                        $object = Adapter_ServiceLocator::get($module->name);
                        $moduleSuccess[] = [
                            'name' => $module->name, 'message' => [
                                0 => sprintf($this->l('Current version: %s'), $object->version),
                                1 => $this->l('No file upgrades applied (none exist).'),
                            ],
                        ];
                    } else {
                        continue;
                    }
                }
                unset($object);
            }

            // Make modules stats
            $this->makeModulesStats($module);

            // Assign warnings
            if ($module->active && isset($module->warning) && !empty($module->warning) && !$this->ajax) {
                $href = $this->context->link->getAdminLink('AdminModules', true).'&module_name='.$module->name.'&tab_module='.$module->tab.'&configure='.$module->name;
                $this->context->smarty->assign('text', sprintf($this->l('%1$s: %2$s'), $module->displayName, $module->warning));
                $this->context->smarty->assign('module_link', $href);
                $this->displayWarning($this->context->smarty->fetch('controllers/modules/warning_module.tpl'));
            }

            // AutoComplete array
            $autocompleteList .= json_encode(
                [
                    'displayName' => (string) $module->displayName,
                    'desc'        => (string) $module->description,
                    'name'        => (string) $module->name,
                    'author'      => (string) $module->author,
                    'image'       => (isset($module->image) ? (string) $module->image : ''),
                    'option'      => '',
                ]
            ).', ';

            // Apply filter
            if ($this->isModuleFiltered($module) && Tools::getValue('select') != 'favorites') {
                unset($modules[$km]);
            } else {
                if (isset($modulesPreferences[$modules[$km]->name])) {
                    $modules[$km]->preferences = $modulesPreferences[$modules[$km]->name];
                }

                $this->fillModuleData($module, 'array');
                $module->categoryName = (isset($this->list_modules_categories[$module->tab]['name']) ? $this->list_modules_categories[$module->tab]['name'] : $this->list_modules_categories['others']['name']);
            }
            unset($object);
            if ($module->installed && isset($module->version_addons) && $module->version_addons) {
                $upgradeAvailable[] = ['anchor' => ucfirst($module->name), 'name' => $module->name, 'displayName' => $module->displayName];
            }

            if (in_array($module->name, $this->list_partners_modules)) {
                $module->type = 'addonsPartner';
            }

            if (isset($module->description_full) && trim($module->description_full) != '') {
                $module->show_quick_view = true;
            }
        }

        // Don't display categories without modules
        $cleanedList = [];
        foreach ($this->list_modules_categories as $k => $list) {
            if ($list['nb'] > 0) {
                $cleanedList[$k] = $list;
            }
        }

        // Actually used for the report of the upgraded errors
        if (count($moduleErrors)) {
            $html = $this->generateHtmlMessage($moduleErrors);
            $this->errors[] = sprintf(Tools::displayError('The following module(s) were not upgraded successfully: %s.'), $html);
        }
        if (count($moduleSuccess)) {
            $html = $this->generateHtmlMessage($moduleSuccess);
            $this->confirmations[] = sprintf($this->l('The following module(s) were upgraded successfully: %s.'), $html);
        }

        ConfigurationKPI::updateValue('UPDATE_MODULES', count($upgradeAvailable));

        if (count($upgradeAvailable) == 0 && (int) Tools::getValue('check') == 1) {
            $this->confirmations[] = $this->l('Everything is up-to-date');
        }

        // Init tpl vars for smarty
        $tplVars = [
            'token'                     => $this->token,
            'upgrade_available'         => $upgradeAvailable,
            'currentIndex'              => static::$currentIndex,
            'dirNameCurrentIndex'       => dirname(static::$currentIndex),
            'ajaxCurrentIndex'          => str_replace('index', 'ajax-tab', static::$currentIndex),
            'autocompleteList'          => rtrim($autocompleteList, ' ,').'];',
            'showTypeModules'           => $this->filter_configuration['PS_SHOW_TYPE_MODULES_'.(int) $this->id_employee],
            'showCountryModules'        => $this->filter_configuration['PS_SHOW_COUNTRY_MODULES_'.(int) $this->id_employee],
            'showInstalledModules'      => $this->filter_configuration['PS_SHOW_INSTALLED_MODULES_'.(int) $this->id_employee],
            'showEnabledModules'        => $this->filter_configuration['PS_SHOW_ENABLED_MODULES_'.(int) $this->id_employee],
            'nameCountryDefault'        => Country::getNameById($this->context->language->id, Configuration::get('PS_COUNTRY_DEFAULT')),
            'isoCountryDefault'         => $this->iso_default_country,
            'categoryFiltered'          => $categoryFiltered,
            'modules'                   => $modules,
            'nb_modules'                => $this->nb_modules_total,
            'nb_modules_favorites'      => count($this->context->employee->favoriteModulesList()),
            'nb_modules_installed'      => $this->nb_modules_installed,
            'nb_modules_uninstalled'    => $this->nb_modules_total - $this->nb_modules_installed,
            'nb_modules_activated'      => $this->nb_modules_activated,
            'nb_modules_unactivated'    => $this->nb_modules_installed - $this->nb_modules_activated,
            'list_modules_categories'   => $cleanedList,
            'list_modules_authors'      => $this->modules_authors,
            'add_permission'            => $this->tabAccess['add'],
            'tab_modules_preferences'   => $tabModulesPreferences,
            'kpis'                      => $this->renderKpis(),
            'module_name'               => Tools::getValue('module_name'),
            'page_header_toolbar_title' => $this->page_header_toolbar_title,
            'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            'modules_uri'               => __PS_BASE_URI__.basename(_PS_MODULE_DIR_),
            'dont_filter'               => $dontFilter,
            'is_contributor'            => (int) $this->context->cookie->is_contributor,
            'maintenance_mode'          => !(bool) Configuration::Get('PS_SHOP_ENABLE'),
        ];

        $smarty->assign($tplVars);
    }

    /**
     * Initialize module list
     *
     * @param array $modules
     *
     * @since 1.0.0
     */
    public function initModulesList(&$modules)
    {
        foreach ($modules as $k => $module) {
            // Check add permissions, if add permissions not set, addons modules and uninstalled modules will not be displayed
            if ($this->tabAccess['add'] !== '1' && isset($module->type) && ($module->type != 'addonsNative' || $module->type != 'addonsBought')) {
                unset($modules[$k]);
            } elseif ($this->tabAccess['add'] !== '1' && (!isset($module->id) || $module->id < 1)) {
                unset($modules[$k]);
            } elseif ($module->id && !Module::getPermissionStatic($module->id, 'view') && !Module::getPermissionStatic($module->id, 'configure')) {
                unset($modules[$k]);
            } else {
                // Init serial and modules author list
                if (!in_array($module->name, $this->list_natives_modules)) {
                    $this->serial_modules .= $module->name.' '.$module->version.'-'.($module->active ? 'a' : 'i')."\n";
                }
                $moduleAuthor = $module->author;
                if (!empty($moduleAuthor) && ($moduleAuthor != '')) {
                    $this->modules_authors[strtolower($moduleAuthor)] = 'notselected';
                }
            }
        }
        $this->serial_modules = urlencode($this->serial_modules);
    }

    /**
     * Make module stats
     *
     * @param Module $module
     *
     * @since 1.0.0
     */
    public function makeModulesStats($module)
    {
        // Count Installed Modules
        if (isset($module->id) && $module->id > 0) {
            $this->nb_modules_installed++;
        }

        // Count Activated Modules
        if (isset($module->id) && $module->id > 0 && $module->active > 0) {
            $this->nb_modules_activated++;
        }

        // Count Modules By Category
        if (isset($this->list_modules_categories[$module->tab]['nb'])) {
            $this->list_modules_categories[$module->tab]['nb']++;
        } else {
            $this->list_modules_categories['others']['nb']++;
        }
    }

    /**
     * Is the module filtered?
     *
     * @param Module $module
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isModuleFiltered($module)
    {
        // If action on module, we display it
        if (Tools::getValue('module_name') != '' && Tools::getValue('module_name') == $module->name) {
            return false;
        }

        // Filter on module name
        $filterName = Tools::getValue('filtername');
        if (!empty($filterName)) {
            if (stristr($module->name, $filterName) === false && stristr($module->displayName, $filterName) === false && stristr($module->description, $filterName) === false) {
                return true;
            }

            return false;
        }

        // Filter on interest
        if ($module->interest !== '') {
            if ($module->interest === '0') {
                return true;
            }
        } elseif ((int) Db::getInstance()->getValue(
            (new DbQuery())
            ->select('`id_module_preference`')
            ->from('module_preference')
            ->where('`module` = \''.pSQL($module->name).'\'')
            ->where('`id_employee` = '.(int) $this->id_employee)
            ->where('`interest` = 0')
        ) > 0) {
            return true;
        }

        // Filter on favorites
        if (Configuration::get('PS_SHOW_CAT_MODULES_'.(int) $this->id_employee) == 'favorites') {
            if ((int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                ->select('`id_module_preference`')
                ->from('module_preference')
                ->where('`module` = \''.pSQL($module->name).'\'')
                ->where('`id_employee` = '.(int) $this->id_employee)
                ->where('`favorite` = 1')
                ->where('`interest` = 1 OR `interest` IS NULL')
            ) < 1) {
                return true;
            }
        } else {
            // Handle "others" category
            if (!isset($this->list_modules_categories[$module->tab])) {
                $module->tab = 'others';
            }

            // Filter on module category
            $categoryFiltered = [];
            $filterCategories = explode('|', Configuration::get('PS_SHOW_CAT_MODULES_'.(int) $this->id_employee));
            if (count($filterCategories) > 0) {
                foreach ($filterCategories as $fc) {
                    if (!empty($fc)) {
                        $categoryFiltered[$fc] = 1;
                    }
                }
            }
            if (count($categoryFiltered) > 0 && !isset($categoryFiltered[$module->tab])) {
                return true;
            }
        }

        // Filter on module type and author
        $showTypeModules = $this->filter_configuration['PS_SHOW_TYPE_MODULES_'.(int) $this->id_employee];
        if ($showTypeModules == 'nativeModules' && !in_array($module->name, $this->list_natives_modules)) {
            return true;
        } elseif ($showTypeModules == 'partnerModules' && !in_array($module->name, $this->list_partners_modules)) {
            return true;
        } elseif ($showTypeModules == 'addonsModules' && (!isset($module->type) || $module->type != 'addonsBought')) {
            return true;
        } elseif ($showTypeModules == 'mustHaveModules' && (!isset($module->type) || $module->type != 'addonsMustHave')) {
            return true;
        } elseif ($showTypeModules == 'otherModules' && (in_array($module->name, $this->list_partners_modules) || in_array($module->name, $this->list_natives_modules))) {
            return true;
        } elseif (strpos($showTypeModules, 'authorModules[') !== false) {
            // setting selected author in authors set
            $authorSelected = substr(str_replace(['authorModules[', "\'"], ['', "'"], $showTypeModules), 0, -1);
            $this->modules_authors[$authorSelected] = 'selected';
            if (empty($module->author) || strtolower($module->author) != $authorSelected) {
                return true;
            }
        }

        // Filter on install status
        $showInstalledModules = $this->filter_configuration['PS_SHOW_INSTALLED_MODULES_'.(int) $this->id_employee];
        if ($showInstalledModules == 'installed' && !$module->id) {
            return true;
        }
        if ($showInstalledModules == 'uninstalled' && $module->id) {
            return true;
        }

        // Filter on active status
        $showEnabledModules = $this->filter_configuration['PS_SHOW_ENABLED_MODULES_'.(int) $this->id_employee];
        if ($showEnabledModules == 'enabled' && !$module->active) {
            return true;
        }
        if ($showEnabledModules == 'disabled' && $module->active) {
            return true;
        }

        // Filter on country
        $showCountryModules = $this->filter_configuration['PS_SHOW_COUNTRY_MODULES_'.(int) $this->id_employee];
        if ($showCountryModules && (isset($module->limited_countries) && !empty($module->limited_countries)
                && ((is_array($module->limited_countries) && count($module->limited_countries)
                        && !in_array(strtolower($this->iso_default_country), $module->limited_countries))
                    || (!is_array($module->limited_countries) && strtolower($this->iso_default_country) != strval($module->limited_countries))))
        ) {
            return true;
        }

        // Module has not been filtered
        return false;
    }

    /**
     * Generate html errors for a module process
     *
     * @param $moduleErrors
     *
     * @return string
     */
    protected function generateHtmlMessage($moduleErrors)
    {
        $htmlError = '';

        if (count($moduleErrors)) {
            $htmlError = '<ul>';
            foreach ($moduleErrors as $moduleError) {
                $htmlErrorDescription = '';
                if (count($moduleError['message']) > 0) {
                    foreach ($moduleError['message'] as $e) {
                        $htmlErrorDescription .= '<br />&nbsp;&nbsp;&nbsp;&nbsp;'.$e;
                    }
                }
                $htmlError .= '<li><b>'.$moduleError['name'].'</b> : '.$htmlErrorDescription.'</li>';
            }
            $htmlError .= '</ul>';
        }

        return $htmlError;
    }

    /**
     * Render KPIs
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function renderKpis()
    {
        $time = time();
        $kpis = [];

        /* The data generation is located in AdminStatsControllerCore */
        $helper = new HelperKpi();
        $helper->id = 'box-installed-modules';
        $helper->icon = 'icon-puzzle-piece';
        $helper->color = 'color1';
        $helper->title = $this->l('Installed Modules', null, null, false);
        if (ConfigurationKPI::get('INSTALLED_MODULES') !== false && ConfigurationKPI::get('INSTALLED_MODULES') != '') {
            $helper->value = ConfigurationKPI::get('INSTALLED_MODULES');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=installed_modules';
        $helper->refresh = (bool) (ConfigurationKPI::get('INSTALLED_MODULES_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-disabled-modules';
        $helper->icon = 'icon-off';
        $helper->color = 'color2';
        $helper->title = $this->l('Disabled Modules', null, null, false);
        if (ConfigurationKPI::get('DISABLED_MODULES') !== false && ConfigurationKPI::get('DISABLED_MODULES') != '') {
            $helper->value = ConfigurationKPI::get('DISABLED_MODULES');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=disabled_modules';
        $helper->refresh = (bool) (ConfigurationKPI::get('DISABLED_MODULES_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        if (Module::isEnabled('tbupdater')) {
            /* Show how many modules can be updated (patch, minor or major)
             * Value should be calculated via the `modules.json` cache file
             * that is generated by the `tbupdater` module
             */
            $helper = new HelperKpi();
            $helper->id = 'box-update-modules';
            $helper->icon = 'icon-refresh';
            $helper->color = 'color3';
            $helper->title = $this->l('Modules to update', null, null, false);
            if (ConfigurationKPI::get('UPDATE_MODULES') !== false && ConfigurationKPI::get('UPDATE_MODULES') != '') {
                $helper->value = (int) ConfigurationKPI::get('UPDATE_MODULES');
            }
            $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=update_modules';
            $helper->refresh = (bool) (ConfigurationKPI::get('UPDATE_MODULES_EXPIRE') < $time);
            $kpis[] = $helper->generate();
        }

        $helper = new HelperKpiRow();
        $helper->kpis = $kpis;

        return $helper->generate();
    }

    /**
     * Ajax process get tab modules list
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessGetTabModulesList()
    {
        $tabModulesList = Tools::getValue('tab_modules_list');
        if ($tabModulesList) {
            $tabModulesList = explode(',', $tabModulesList);
            $modulesListUnsort = $this->getModulesByInstallation($tabModulesList);
        }

        $installed = $uninstalled = [];
        if (isset($modulesListUnsort)) {
            foreach ($tabModulesList as $key => $value) {
                $continue = 0;
                foreach ($modulesListUnsort['installed'] as $modIn) {
                    if ($modIn->name == $value) {
                        $continue = 1;
                        $installed[] = $modIn;
                    }
                }
                if ($continue) {
                    continue;
                }
                foreach ($modulesListUnsort['not_installed'] as $modIn) {
                    if ($modIn->name == $value) {
                        $uninstalled[] = $modIn;
                    }
                }
            }
        }

        $modulesListSort = [
            'installed'     => $installed,
            'not_installed' => $uninstalled,
        ];

        $this->context->smarty->assign(
            [
                'tab_modules_list'            => $modulesListSort,
                'admin_module_favorites_view' => $this->context->link->getAdminLink('AdminModules').'&select=favorites',
                'lang_iso'                    => $this->context->language->iso_code,
            ]
        );

        $this->smartyOutputContent('controllers/modules/tab_modules_list.tpl');
        exit;
    }

    /**
     * Filter Configuration Methods
     * Set and reset filter configuration
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function getModulesByInstallation($tabModulesList = null)
    {
        $allModules = Module::getModulesOnDisk(true, false, $this->id_employee);
        $allUniqueModules = [];
        $modulesList = ['installed' => [], 'not_installed' => []];

        foreach ($allModules as $mod) {
            if (!isset($allUniqueModules[$mod->name])) {
                $allUniqueModules[$mod->name] = $mod;
            }
        }

        $allModules = $allUniqueModules;

        foreach ($allModules as $module) {
            if (!isset($tabModulesList) || in_array($module->name, $tabModulesList)) {
                $perm = true;
                if ($module->id) {
                    $perm &= Module::getPermissionStatic($module->id, 'configure');
                } else {
                    $idAdminModule = Tab::getIdFromClassName('AdminModules');
                    $access = Profile::getProfileAccess($this->context->employee->id_profile, $idAdminModule);
                    if (!$access['edit']) {
                        $perm &= false;
                    }
                }

                if (in_array($module->name, $this->list_partners_modules)) {
                    $module->type = 'addonsPartner';
                }

                if ($perm) {
                    $this->fillModuleData($module, 'array');
                    if ($module->id) {
                        $modulesList['installed'][] = $module;
                    } else {
                        $modulesList['not_installed'][] = $module;
                    }
                }
            }
        }

        return $modulesList;
    }

    /**
     * Ajax process set filter
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessSetFilter()
    {
        $this->setFilterModules(Tools::getValue('module_type'), Tools::getValue('country_module_value'), Tools::getValue('module_install'), Tools::getValue('module_status'));
        die('OK');
    }

    /**
     * Post Process Filter
     *
     * @param mixed $moduleType
     * @param mixed $countryModuleValue
     * @param mixed $moduleInstall
     * @param mixed $moduleStatus
     *
     * @return void
     * @since 1.0.0
     */
    protected function setFilterModules($moduleType, $countryModuleValue, $moduleInstall, $moduleStatus)
    {
        Configuration::updateValue('PS_SHOW_TYPE_MODULES_'.(int) $this->id_employee, $moduleType);
        Configuration::updateValue('PS_SHOW_COUNTRY_MODULES_'.(int) $this->id_employee, $countryModuleValue);
        Configuration::updateValue('PS_SHOW_INSTALLED_MODULES_'.(int) $this->id_employee, $moduleInstall);
        Configuration::updateValue('PS_SHOW_ENABLED_MODULES_'.(int) $this->id_employee, $moduleStatus);
    }

    /**
     * Ajax process save favorite preferences
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessSaveFavoritePreferences()
    {
        $action = Tools::getValue('action_pref');
        $value = Tools::getValue('value_pref');
        $module = Tools::getValue('module_pref');
        $idModulePreference = (int) Db::getInstance()->getValue(
            (new DbQuery())
            ->select('`id_module_preference`')
            ->from('module_preference')
            ->where('`id_employee` = '.(int) $this->id_employee)
            ->where('`module` = \''.pSQL($module).'\'')
        );
        if ($idModulePreference > 0) {
            if ($action == 'i') {
                $update = ['interest' => ($value == '' ? null : (int) $value)];
            }
            if ($action == 'f') {
                $update = ['favorite' => ($value == '' ? null : (int) $value)];
            }
            Db::getInstance()->update('module_preference', $update, '`id_employee` = '.(int) $this->id_employee.' AND `module` = \''.pSQL($module).'\'', 0, true);
        } else {
            $insert = ['id_employee' => (int) $this->id_employee, 'module' => pSQL($module), 'interest' => null, 'favorite' => null];
            if ($action == 'i') {
                $insert['interest'] = ($value == '' ? null : (int) $value);
            }
            if ($action == 'f') {
                $insert['favorite'] = ($value == '' ? null : (int) $value);
            }
            Db::getInstance()->insert('module_preference', $insert, true);
        }
        die('OK');
    }

    /**
     * Ajax process save tab module preferences
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessSaveTabModulePreferences()
    {
        $values = Tools::getValue('value_pref');
        $module = Tools::getValue('module_pref');
        if (Validate::isModuleName($module)) {
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'tab_module_preference` WHERE `id_employee` = '.(int) $this->id_employee.' AND `module` = \''.pSQL($module).'\'');
            if (is_array($values) && count($values)) {
                foreach ($values as $value) {
                    Db::getInstance()->execute(
                        '
                        INSERT INTO `'._DB_PREFIX_.'tab_module_preference` (`id_tab_module_preference`, `id_employee`, `id_tab`, `module`)
                        VALUES (NULL, '.(int) $this->id_employee.', '.(int) $value.', \''.pSQL($module).'\');'
                    );
                }
            }
        }
        die('OK');
    }

    /**
     * Post process filter modules
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcessFilterModules()
    {
        $this->setFilterModules(Tools::getValue('module_type'), Tools::getValue('country_module_value'), Tools::getValue('module_install'), Tools::getValue('module_status'));
        Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
    }

    /**
     * Post Process Module CallBack
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcessResetFilterModules()
    {
        $this->resetFilterModules();
        Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
    }

    /**
     * Reset filter modules
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function resetFilterModules()
    {
        Configuration::updateValue('PS_SHOW_TYPE_MODULES_'.(int) $this->id_employee, 'allModules');
        Configuration::updateValue('PS_SHOW_COUNTRY_MODULES_'.(int) $this->id_employee, 0);
        Configuration::updateValue('PS_SHOW_INSTALLED_MODULES_'.(int) $this->id_employee, 'installedUninstalled');
        Configuration::updateValue('PS_SHOW_ENABLED_MODULES_'.(int) $this->id_employee, 'enabledDisabled');
        Configuration::updateValue('PS_SHOW_CAT_MODULES_'.(int) $this->id_employee, '');
    }

    /**
     * Post process filter category
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcessFilterCategory()
    {
        // Save configuration and redirect employee
        Configuration::updateValue('PS_SHOW_CAT_MODULES_'.(int) $this->id_employee, Tools::getValue('filterCategory'));
        Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
    }

    /**
     * Post process unfilter category
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcessUnfilterCategory()
    {
        // Save configuration and redirect employee
        Configuration::updateValue('PS_SHOW_CAT_MODULES_'.(int) $this->id_employee, '');
        Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
    }

    /**
     * Post process reset
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcessReset()
    {
        if ($this->tabAccess['edit'] === '1') {
            $module = Module::getInstanceByName(Tools::getValue('module_name'));
            if (Validate::isLoadedObject($module)) {
                if (!$module->getPermission('configure')) {
                    $this->errors[] = Tools::displayError('You do not have the permission to use this module.');
                } else {
                    if (Tools::getValue('keep_data') == '1' && method_exists($module, 'reset')) {
                        if ($module->reset()) {
                            Tools::redirectAdmin(static::$currentIndex.'&conf=21&token='.$this->token.'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name));
                        } else {
                            $this->errors[] = Tools::displayError('Cannot reset this module.');
                        }
                    } else {
                        if ($module->uninstall()) {
                            if ($module->install()) {
                                Tools::redirectAdmin(static::$currentIndex.'&conf=21&token='.$this->token.'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name));
                            } else {
                                $this->errors[] = Tools::displayError('Cannot install this module.');
                            }
                        } else {
                            $this->errors[] = Tools::displayError('Cannot uninstall this module.');
                        }
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('Cannot load the module\'s object.');
            }

            if (($errors = $module->getErrors()) && is_array($errors)) {
                $this->errors = array_merge($this->errors, $errors);
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to add this.');
        }
    }

    /**
     * Post process download
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcessDownload()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_ || ($this->context->mode == Context::MODE_HOST)) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return;
        }

        // Try to upload and unarchive the module
        if ($this->tabAccess['add'] === '1') {
            // UPLOAD_ERR_OK: 0
            // UPLOAD_ERR_INI_SIZE: 1
            // UPLOAD_ERR_FORM_SIZE: 2
            // UPLOAD_ERR_NO_TMP_DIR: 6
            // UPLOAD_ERR_CANT_WRITE: 7
            // UPLOAD_ERR_EXTENSION: 8
            // UPLOAD_ERR_PARTIAL: 3

            if (isset($_FILES['file']['error']) && $_FILES['file']['error'] != UPLOAD_ERR_OK) {
                switch ($_FILES['file']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $this->errors[] = sprintf($this->l('File too large (limit of %s bytes).'), Tools::getMaxUploadSize());
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $this->errors[] = $this->l('File upload was not completed.');
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $this->errors[] = $this->l('No file was uploaded.');
                        break;
                    default:
                        $this->errors[] = sprintf($this->l('Internal error #%s'), $_FILES['newfile']['error']);
                        break;
                }
            } elseif (!isset($_FILES['file']['tmp_name']) || empty($_FILES['file']['tmp_name'])) {
                $this->errors[] = $this->l('No file has been selected');
            } elseif (substr($_FILES['file']['name'], -4) != '.tar' && substr($_FILES['file']['name'], -4) != '.zip'
                && substr($_FILES['file']['name'], -4) != '.tgz' && substr($_FILES['file']['name'], -7) != '.tar.gz'
            ) {
                $this->errors[] = Tools::displayError('Unknown archive type.');
            } elseif (!move_uploaded_file($_FILES['file']['tmp_name'], _PS_MODULE_DIR_.$_FILES['file']['name'])) {
                $this->errors[] = Tools::displayError('An error occurred while copying the archive to the module directory.');
            } else {
                $this->extractArchive(_PS_MODULE_DIR_.$_FILES['file']['name']);
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to add this.');
        }
    }

    /**
     * Extract archive
     *
     * @param string $file
     * @param bool  $redirect
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function extractArchive($file, $redirect = true)
    {
        $oldUmask = @umask(0000);

        $tmpFolder = _PS_MODULE_DIR_.md5(time());

        $success = false;
        if (substr($file, -4) == '.zip') {
            $zip = new ZipArchive();
            $zip->open($file);
            $dirs = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                // Zip = *NIX style
                $filePath = explode('/', $zip->getNameIndex($i));

                if (!empty($filePath)) {
                    $dirs[] = $filePath[0];
                }
            }
            $zipFolders = array_unique($dirs);
            foreach ($zipFolders as $zipFolder) {
                if (!in_array($zipFolder, ['.', '..', '.svn', '.git', '__MACOSX'])) {
                    if (file_exists(_PS_MODULE_DIR_.$zipFolder) && !ConfigurationTest::testDir(_PS_MODULE_DIR_.$zipFolder, true, $report, true)) {
                        $this->errors[] = $this->l('There was an error while extracting the module.').' '.$report;

                        return false;
                    }
                }
            }

            // Set permissions to the default 0777
            if (Tools::ZipExtract($file, _PS_MODULE_DIR_)) {
                $success = true;

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    @chmod(_PS_MODULE_DIR_.$zip->getNameIndex($i), 0777);
                }
                foreach ($zipFolders as $zipFolder) {
                    @chmod(_PS_MODULE_DIR_.$zipFolder, 0777);
                }
            }
        } else {
            $archive = new Archive_Tar($file);
            $dirs = $archive->listContent();
            $zipFolders = [];
            for ($i = 0; $i < count($dirs); $i++) {
                $filePath = explode(DIRECTORY_SEPARATOR, $dirs[$i]);
                if (!empty($filePath)) {
                    $zipFolders[] = $filePath[0];
                }
            }
            $zipFolders = array_unique($dirs);
            foreach ($zipFolders as $zipFolder) {
                if (!in_array($zipFolder, ['.', '..', '.svn', '.git', '__MACOSX'])) {
                    if (file_exists(_PS_MODULE_DIR_.$zipFolder) && !ConfigurationTest::testDir(_PS_MODULE_DIR_.$zipFolder, true, $report, true)) {
                        $this->errors[] = $this->l('There was an error while extracting the module.').' '.$report;

                        return false;
                    }
                }
            }
            if ($archive->extract(_PS_MODULE_DIR_)) {
                $success = true;

                for ($i = 0; $i < count($dirs); $i++) {
                    @chmod(_PS_MODULE_DIR_.$dirs[$i], 0777);
                }
                foreach ($zipFolders as $zipFolder) {
                    @chmod(_PS_MODULE_DIR_.$zipFolder, 0777);
                }
            }
        }

        if (!$success) {
            $this->errors[] = Tools::displayError('There was an error while extracting the module (file may be corrupted).');
        } else {
            //check if it's a real module
            foreach ($zipFolders as $folder) {
                if (!in_array($folder, ['.', '..', '.svn', '.git', '__MACOSX']) && !Module::getInstanceByName($folder)) {
                    $this->errors[] = sprintf(Tools::displayError('The module %1$s that you uploaded is not a valid module.'), $folder);
                    $this->recursiveDeleteOnDisk(_PS_MODULE_DIR_.$folder);
                }
            }
        }

        @unlink($file);
        $this->recursiveDeleteOnDisk($tmpFolder);

        @umask($oldUmask);

        if ($success && $redirect && isset($folder)) {
            Tools::redirectAdmin(static::$currentIndex.'&conf=8&anchor='.ucfirst($folder).'&token='.$this->token);
        }

        return $success;
    }

    /**
     * Recursive delete on disk
     *
     * @param string $dir
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function recursiveDeleteOnDisk($dir)
    {
        if (strpos(realpath($dir), realpath(_PS_MODULE_DIR_)) === false) {
            return;
        }
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir.'/'.$object) == 'dir') {
                        $this->recursiveDeleteOnDisk($dir.'/'.$object);
                    } else {
                        unlink($dir.'/'.$object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    /**
     * Post process enable
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcessEnable()
    {
        if ($this->tabAccess['edit'] === '1') {
            $module = Module::getInstanceByName(Tools::getValue('module_name'));
            if (Validate::isLoadedObject($module)) {
                if (!$module->getPermission('configure')) {
                    $this->errors[] = Tools::displayError('You do not have the permission to use this module.');
                } else {
                    if (Tools::getValue('enable')) {
                        $module->enable();
                    } else {
                        $module->disable();
                    }
                    Tools::redirectAdmin($this->getCurrentUrl('enable'));
                }
            } else {
                $this->errors[] = Tools::displayError('Cannot load the module\'s object.');
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to add this.');
        }
    }

    /**
     * Get current URL
     *
     * @param array $remove
     *
     * @return mixed|string
     *
     * @since 1.0.0
     */
    protected function getCurrentUrl($remove = [])
    {
        $url = $_SERVER['REQUEST_URI'];
        if (!$remove) {
            return $url;
        }

        if (!is_array($remove)) {
            $remove = [$remove];
        }

        $url = preg_replace('#(?<=&|\?)('.implode('|', $remove).')=.*?(&|$)#i', '', $url);
        $len = strlen($url);
        if ($url[$len - 1] == '&') {
            $url = substr($url, 0, $len - 1);
        }

        return $url;
    }

    /**
     * Post process enable device
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcessEnable_Device()
    {
        if ($this->tabAccess['edit'] === '1') {
            $module = Module::getInstanceByName(Tools::getValue('module_name'));
            if (Validate::isLoadedObject($module)) {
                if (!$module->getPermission('configure')) {
                    $this->errors[] = Tools::displayError('You do not have the permission to use this module.');
                } else {
                    $module->enableDevice((int) Tools::getValue('enable_device'));
                    Tools::redirectAdmin($this->getCurrentUrl('enable_device'));
                }
            } else {
                $this->errors[] = Tools::displayError('Cannot load the module\'s object.');
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to add this.');
        }
    }

    /**
     * Post proces disable device
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcessDisable_Device()
    {
        if ($this->tabAccess['edit'] === '1') {
            $module = Module::getInstanceByName(Tools::getValue('module_name'));
            if (Validate::isLoadedObject($module)) {
                if (!$module->getPermission('configure')) {
                    $this->errors[] = Tools::displayError('You do not have the permission to use this module.');
                } else {
                    $module->disableDevice((int) Tools::getValue('disable_device'));
                    Tools::redirectAdmin($this->getCurrentUrl('disable_device'));
                }
            } else {
                $this->errors[] = Tools::displayError('Cannot load the module\'s object.');
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to add this.');
        }
    }

    /**
     * Post process delete
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcessDelete()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return;
        }

        if ($this->tabAccess['delete'] === '1') {
            if (Tools::getValue('module_name') != '') {
                $module = Module::getInstanceByName(Tools::getValue('module_name'));
                if (Validate::isLoadedObject($module) && !$module->getPermission('configure')) {
                    $this->errors[] = Tools::displayError('You do not have the permission to use this module.');
                } else {
                    // Uninstall the module before deleting the files, but do not block the process if uninstall returns false
                    if (Module::isInstalled($module->name)) {
                        $module->uninstall();
                    }
                    $moduleDir = _PS_MODULE_DIR_.str_replace(['.', '/', '\\'], ['', '', ''], Tools::getValue('module_name'));
                    if (!ConfigurationTest::testDir($moduleDir, true, $report, true)) {
                        $this->errors[] = Tools::displayError('Sorry, the module cannot be deleted:').' '.$report;
                    } else {
                        $this->recursiveDeleteOnDisk($moduleDir);
                        if (!file_exists($moduleDir)) {
                            Tools::redirectAdmin(static::$currentIndex.'&conf=22&token='.$this->token.'&tab_module='.Tools::getValue('tab_module').'&module_name='.Tools::getValue('module_name'));
                        } else {
                            $this->errors[] = Tools::displayError('Sorry, the module cannot be deleted. Please check if you have the right permissions on this folder.');
                        }
                    }
                }
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to delete this.');
        }
    }

    /**
     * Post process
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        // Parent Post Process
        parent::postProcess();

        // Get the list of installed module ans prepare it for ajax call.
        if (($list = Tools::getValue('installed_modules'))) {
            $this->context->smarty->assign('installed_modules', json_encode(explode('|', $list)));
        }

        // If redirect parameter is present and module already installed, we redirect on configuration module page
        if (Tools::getValue('redirect') == 'config' && Tools::getValue('module_name') != '' && Module::isInstalled(pSQL(Tools::getValue('module_name')))) {
            Tools::redirectAdmin('index.php?controller=adminmodules&configure='.Tools::getValue('module_name').'&token='.Tools::getValue('token').'&module_name='.Tools::getValue('module_name'));
        }

        // Execute filter or callback methods
        $filterMethods = ['filterModules', 'resetFilterModules', 'filterCategory', 'unfilterCategory'];
        $callbackMethods = ['reset', 'download', 'enable', 'delete', 'enable_device', 'disable_device'];
        $postProcessMethodsList = array_merge((array) $filterMethods, (array) $callbackMethods);
        foreach ($postProcessMethodsList as $ppm) {
            if (Tools::isSubmit($ppm)) {
                $ppm = 'postProcess'.ucfirst($ppm);
                if (method_exists($this, $ppm)) {
                    $ppmReturn = $this->$ppm();
                }
            }
        }

        // Call appropriate module callback
        if (!isset($ppmReturn)) {
            $this->postProcessCallback();
        }

        if ($back = Tools::getValue('back')) {
            Tools::redirectAdmin($back);
        }
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function postProcessCallback()
    {
        $return = false;
        $installedModules = [];
        foreach ($this->map as $key => $method) {
            if (!Tools::getValue($key)) {
                continue;
            }
            /* PrestaShop demo mode */
            if (_PS_MODE_DEMO_) {
                $this->errors[] = Tools::displayError('This functionality has been disabled.');

                return;
            }

            if ($key == 'check') {
                $this->ajaxProcessRefreshModuleList(true);
            } elseif ($key == 'checkAndUpdate') {
                $modules = [];
                $this->ajaxProcessRefreshModuleList(true);
                $modulesOnDisk = Module::getModulesOnDisk(true, false, $this->id_employee);
                // Browse modules list
                foreach ($modulesOnDisk as $km => $moduleOnDisk) {
                    if (!Tools::getValue('module_name') && isset($moduleOnDisk->version_addons) && $moduleOnDisk->version_addons) {
                        $modules[] = $moduleOnDisk->name;
                    }
                }
                if (!Tools::getValue('module_name')) {
                    $modulesListSave = implode('|', $modules);
                }
            } elseif (($modules = Tools::getValue($key)) && $key != 'checkAndUpdate' && $key != 'updateAll') {
                if (strpos($modules, '|')) {
                    $modulesListSave = $modules;
                    $modules = explode('|', $modules);
                }
                if (!is_array($modules)) {
                    $modules = (array) $modules;
                }
            } elseif ($key == 'updateAll') {
                $allModules = Module::getModulesOnDisk(true, false, $this->context->employee->id);
                $modules = [];
                foreach ($allModules as $km => $moduleToUpdate) {
                    if ($moduleToUpdate->installed && isset($moduleToUpdate->version_addons) && $moduleToUpdate->version_addons) {
                        $modules[] = (string) $moduleToUpdate->name;
                    }
                }
            }

            /** @var TbUpdater $tbupdater */
            $tbupdater = Module::getInstanceByName('tbupdater');
            $moduleUpgraded = [];
            $moduleErrors = [];
            if (isset($modules)) {
                foreach ($modules as $name) {
                    $moduleToUpdate = [];
                    $moduleToUpdate[$name] = null;
                    $fullReport = null;

                    // If Addons module, download and unzip it before installing it
                    if (Validate::isLoadedObject($tbupdater) && !file_exists(_PS_MODULE_DIR_.$name.'/'.$name.'.php') || $key == 'update' || $key == 'updateAll') {
                        foreach ($tbupdater->getCachedModulesInfo($this->context->language->language_code) as $moduleInfoName => $moduleInfo) {
                            if (mb_strtolower($name) == mb_strtolower($moduleInfoName)) {
                                $moduleToUpdate[$name]['id'] = 0;
                                $moduleToUpdate[$name]['displayName'] = $moduleInfo['displayName'];
                            }
                        }

                        foreach ($moduleToUpdate as $name => $attr) {
                            if (!$tbupdater->updateModule($name)) {
                                $this->errors[] = sprintf(Tools::displayError('Module %s cannot be upgraded: Error while extracting the latest version.'), '<strong>'.$attr['displayName'].'</strong>');
                            } else {
                                $moduleUpgraded[] = $name;
                            }
                        }
                    }

                    // Check potential error
                    if (!($module = Module::getInstanceByName(urldecode($name)))) {
                        // Try the thirty bees updater
                        /** @var TbUpdater $updater */
                        $updater = Module::getInstanceByName('tbupdater');
                        if (!($key === 'install' && Validate::isLoadedObject($updater) && $updater->installModule(urldecode($name)))) {
                            $this->errors[] = $this->l('Module not found');
                        }
                    } elseif ($key == 'install' && $this->tabAccess['add'] !== '1') {
                        $this->errors[] = Tools::displayError('You do not have permission to install this module.');
                    } elseif ($key == 'delete' && ($this->tabAccess['delete'] !== '1' || !$module->getPermission('configure'))) {
                        $this->errors[] = Tools::displayError('You do not have permission to delete this module.');
                    } elseif ($key == 'configure' && ($this->tabAccess['edit'] !== '1' || !$module->getPermission('configure') || !Module::isInstalled(urldecode($name)))) {
                        $this->errors[] = Tools::displayError('You do not have permission to configure this module.');
                    } elseif ($key == 'install' && Module::isInstalled($module->name)) {
                        $this->errors[] = sprintf(Tools::displayError('This module is already installed: %s.'), $module->name);
                    } elseif ($key == 'uninstall' && !Module::isInstalled($module->name)) {
                        $this->errors[] = sprintf(Tools::displayError('This module has already been uninstalled: %s.'), $module->name);
                    } elseif ($key == 'update' && !Module::isInstalled($module->name)) {
                        $this->errors[] = sprintf(Tools::displayError('This module needs to be installed in order to be updated: %s.'), $module->name);
                    } else {
                        // If we install a module, force temporary global context for multishop
                        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_ALL && $method != 'getContent') {
                            $shopId = (int) $this->context->shop->id;
                            $this->context->tmpOldShop = clone($this->context->shop);
                            if ($shopId) {
                                $this->context->shop = new Shop($shopId);
                            }
                        }
                        //retrocompatibility
                        if (Tools::getValue('controller') != '') {
                            $_POST['tab'] = Tools::safeOutput(Tools::getValue('controller'));
                        }
                        $echo = '';
                        if ($key != 'update' && $key != 'updateAll' && $key != 'checkAndUpdate' && $key != 'delete') {
                            // We check if method of module exists
                            if (!method_exists($module, $method)) {
                                throw new PrestaShopException(sprintf('Method %s of module cannot be found', $method));
                            }
                            if ($key == 'uninstall' && !Module::getPermissionStatic($module->id, 'uninstall')) {
                                $this->errors[] = Tools::displayError('You do not have permission to uninstall this module.');
                            }
                            if (count($this->errors)) {
                                continue;
                            }
                            // Get the return value of current method
                            $echo = $module->{$method}();
                            // After a successful install of a single module that has a configuration method, to the configuration page
                            if ($key == 'install' && $echo === true && strpos(Tools::getValue('install'), '|') === false && method_exists($module, 'getContent')) {
                                Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token.'&configure='.$module->name.'&conf=12');
                            }
                        }
                        // If the method called is "configure" (getContent method), we show the html code of configure page
                        if ($key == 'configure' && Module::isInstalled($module->name)) {
                            $this->bootstrap = (isset($module->bootstrap) && $module->bootstrap);
                            if (isset($module->multishop_context)) {
                                $this->multishop_context = $module->multishop_context;
                            }
                            $backLink = static::$currentIndex.'&token='.$this->token.'&tab_module='.$module->tab.'&module_name='.$module->name;
                            $hookLink = 'index.php?tab=AdminModulesPositions&token='.Tools::getAdminTokenLite('AdminModulesPositions').'&show_modules='.(int) $module->id;
                            $tradLink = 'index.php?tab=AdminTranslations&token='.Tools::getAdminTokenLite('AdminTranslations').'&type=modules&lang=';
                            $disableLink = $this->context->link->getAdminLink('AdminModules').'&module_name='.$module->name.'&enable=0&tab_module='.$module->tab;
                            $uninstallLink = $this->context->link->getAdminLink('AdminModules').'&module_name='.$module->name.'&uninstall='.$module->name.'&tab_module='.$module->tab;
                            $resetLink = $this->context->link->getAdminLink('AdminModules').'&module_name='.$module->name.'&reset&tab_module='.$module->tab;
                            $updateLink = $this->context->link->getAdminLink('AdminModules').'&checkAndUpdate=1&module_name='.$module->name;
                            $isResetReady = false;
                            if (method_exists($module, 'reset')) {
                                $isResetReady = true;
                            }
                            $this->context->smarty->assign(
                                [
                                    'module_name'               => $module->name,
                                    'module_display_name'       => $module->displayName,
                                    'back_link'                 => $backLink,
                                    'module_hook_link'          => $hookLink,
                                    'module_disable_link'       => $disableLink,
                                    'module_uninstall_link'     => $uninstallLink,
                                    'module_reset_link'         => $resetLink,
                                    'module_update_link'        => $updateLink,
                                    'trad_link'                 => $tradLink,
                                    'module_languages'          => Language::getLanguages(false),
                                    'theme_language_dir'        => _THEME_LANG_DIR_,
                                    'page_header_toolbar_title' => $this->page_header_toolbar_title,
                                    'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
                                    'add_permission'            => $this->tabAccess['add'],
                                    'is_reset_ready'            => $isResetReady,
                                ]
                            );
                            // Display checkbox in toolbar if multishop
                            if (Shop::isFeatureActive()) {
                                if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                                    $shopContext = 'shop <strong>'.$this->context->shop->name.'</strong>';
                                } elseif (Shop::getContext() == Shop::CONTEXT_GROUP) {
                                    $shopGroup = new ShopGroup((int) Shop::getContextShopGroupID());
                                    $shopContext = 'all shops of group shop <strong>'.$shopGroup->name.'</strong>';
                                } else {
                                    $shopContext = 'all shops';
                                }
                                $this->context->smarty->assign(
                                    [
                                        'module'                     => $module,
                                        'display_multishop_checkbox' => true,
                                        'current_url'                => $this->getCurrentUrl('enable'),
                                        'shop_context'               => $shopContext,
                                    ]
                                );
                            }
                            $this->context->smarty->assign(
                                [
                                    'is_multishop'      => Shop::isFeatureActive(),
                                    'multishop_context' => Shop::CONTEXT_ALL | Shop::CONTEXT_GROUP | Shop::CONTEXT_SHOP,
                                ]
                            );
                            if (Shop::isFeatureActive() && isset($this->context->tmpOldShop)) {
                                $this->context->shop = clone($this->context->tmpOldShop);
                                unset($this->context->tmpOldShop);
                            }
                            // Display module configuration
                            $header = $this->context->smarty->fetch('controllers/modules/configure.tpl');
                            $configurationBar = $this->context->smarty->fetch('controllers/modules/configuration_bar.tpl');
                            $output = $header.$echo;
                            $this->context->smarty->assign('module_content', $output.$configurationBar);
                        } elseif ($echo === true) {
                            $return = 13;
                            if ($method == 'install') {
                                $return = 12;
                                $installedModules[] = $module->id;
                            }
                        } elseif ($echo === false) {
                            $moduleErrors[] = ['name' => $name, 'message' => $module->getErrors()];
                        }
                        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_ALL && isset($this->context->tmpOldShop)) {
                            $this->context->shop = clone($this->context->tmpOldShop);
                            unset($this->context->tmpOldShop);
                        }
                    }
                    if ($key != 'configure' && Tools::getIsset('bpay')) {
                        Tools::redirectAdmin('index.php?tab=AdminPayment&token='.Tools::getAdminToken('AdminPayment'.(int) Tab::getIdFromClassName('AdminPayment').(int) $this->id_employee));
                    }
                }
            }
            if (isset($moduleErrors) && count($moduleErrors)) {
                // If error during module installation, no redirection
                $htmlError = $this->generateHtmlMessage($moduleErrors);
                if ($key == 'uninstall') {
                    $this->errors[] = sprintf(Tools::displayError('The following module(s) could not be uninstalled properly: %s.'), $htmlError);
                } else {
                    $this->errors[] = sprintf(Tools::displayError('The following module(s) could not be installed properly: %s.'), $htmlError);
                }
                $this->context->smarty->assign('error_module', 'true');
            }
        }
        if ($return) {
            $params = (count($installedModules)) ? '&installed_modules='.implode('|', $installedModules) : '';
            // If redirect parameter is present and module installed with success, we redirect on configuration module page
            if (Tools::getValue('redirect') == 'config' && Tools::getValue('module_name') != '' && $return == '12' && Module::isInstalled(pSQL(Tools::getValue('module_name')))) {
                Tools::redirectAdmin('index.php?controller=adminmodules&configure='.Tools::getValue('module_name').'&token='.Tools::getValue('token').'&module_name='.Tools::getValue('module_name').$params);
            }
            if (isset($module)) {
                Tools::redirectAdmin(static::$currentIndex.'&conf='.$return.'&token='.$this->token.'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name).(isset($modulesListSave) ? '&modules_list='.$modulesListSave : '').$params);
            }
        }
        if (Tools::getValue('update') || Tools::getValue('updateAll') || Tools::getValue('checkAndUpdate')) {
            $updated = '&updated=1';
            if (Tools::getValue('checkAndUpdate')) {
                $updated = '&check=1';
                if (Tools::getValue('module_name')) {
                    $module = Module::getInstanceByName(Tools::getValue('module_name'));
                    if (!Validate::isLoadedObject($module)) {
                        unset($module);
                    }
                }
            }

            if (isset($module)) {
                Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token.$updated.'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name).(isset($modulesListSave) ? '&modules_list='.$modulesListSave : ''));
            }
        }
    }

    /**
     * Initialize modal
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initModal()
    {
        parent::initModal();

        $this->context->smarty->assign(
            [
                'trad_link'        => 'index.php?tab=AdminTranslations&token='.Tools::getAdminTokenLite('AdminTranslations').'&type=modules&lang=',
                'module_languages' => Language::getLanguages(false),
                'module_name'      => Tools::getValue('module_name'),
            ]
        );

        $modalContent = $this->context->smarty->fetch('controllers/modules/modal_translation.tpl');
        $this->modals[] = [
            'modal_id'      => 'moduleTradLangSelect',
            'modal_class'   => 'modal-sm',
            'modal_title'   => $this->l('Translate this module'),
            'modal_content' => $modalContent,
        ];
    }

    /**
     * Ajax process get module quick view
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessGetModuleQuickView()
    {
        $modules = Module::getModulesOnDisk();

        foreach ($modules as $module) {
            if ($module->name == Tools::getValue('module')) {
                break;
            }
        }

        $url = $module->url;

        if (isset($module->type) && ($module->type == 'addonsPartner' || $module->type == 'addonsNative')) {
            $url = $this->context->link->getAdminLink('AdminModules').'&install='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name);
        }

        $this->context->smarty->assign(
            [
                'displayName'            => $module->displayName,
                'image'                  => $module->image,
                'nb_rates'               => (int) $module->nb_rates[0],
                'avg_rate'               => (int) $module->avg_rate[0],
                'badges'                 => $module->badges,
                'compatibility'          => $module->compatibility,
                'description_full'       => $module->description_full,
                'additional_description' => $module->additional_description,
                'is_addons_partner'      => (isset($module->type) && ($module->type == 'addonsPartner' || $module->type == 'addonsNative')),
                'url'                    => $url,
                'price'                  => $module->price,
            ]
        );
        $this->smartyOutputContent('controllers/modules/quickview.tpl');
    }
}
