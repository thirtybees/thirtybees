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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

use Thirtybees\Core\Package\PackageExtractor;

/**
 * Class AdminModulesControllerCore
 */
class AdminModulesControllerCore extends AdminController
{
    const CATEGORY_ALL = 'all';
    const CATEGORY_FAVORITES = 'favorites';
    const CATEGORY_PREMIUM = 'premium';
    const CATEGORY_OTHERS = 'others';

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

    /**
     * Admin Modules Controller Constructor
     * Init list modules categories
     * Load id employee
     * Load filter configuration
     * Load cache file
     * @throws PrestaShopException
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
        $this->list_modules_categories[static::CATEGORY_OTHERS]['name'] = $this->l('Other Modules');
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
                'PS_SHOW_INSTALLED_MODULES_'.(int) $this->id_employee,
                'PS_SHOW_ENABLED_MODULES_'.(int) $this->id_employee
            ]
        );
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    public function checkCategoriesNames($a, $b)
    {
        if ($a['name'] === $b['name']) {
            return 0;
        }
        if ($a['name'] === $this->l('Other Modules')) {
            return 1;
        }
        if ($b['name'] === $this->l('Other Modules')) {
            return -1;
        }
        return ($a['name'] > $b['name']) ? 1 : -1;
    }

    /**
     * Set media
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryPlugin(['autocomplete', 'fancybox', 'tablefilter']);
        $this->addCSS(__PS_BASE_URI__ . $this->admin_webpath . '/themes/default/css/tb-premium-modules/tb-premium-modules.css');
    }

    /**
     * @param bool $forceReloadCache
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessRefreshModuleList($forceReloadCache = false)
    {
        Module::checkApiModulesUpdates($forceReloadCache);
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     */
    public function displayAjaxRefreshModuleList()
    {
        $this->ajaxDie(json_encode(['status' => $this->status]));
    }

    /**
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function ajaxProcessLogOnAddonsWebservices()
    {
        $this->ajaxDie('OK');
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function ajaxProcessLogOutAddonsWebservices()
    {
        Tools::displayAsDeprecated();
        $this->ajaxDie('OK');
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function ajaxProcessReloadModulesList()
    {
        if (Tools::getValue('filterCategory')) {
            $this->setCategoryFilter(Tools::getValue('filterCategory'));
        }
        if (Tools::getValue('unfilterCategory')) {
            $this->setCategoryFilter(static::CATEGORY_ALL);
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
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        // If we are on a module configuration, no need to load all modules
        if (Tools::getValue('configure') != '') {
            $this->context->smarty->assign(['maintenance_mode' => !Configuration::Get('PS_SHOP_ENABLE')]);
            return;
        }

        $tabModule = Tools::getValue('tab_module');
        if ($tabModule && $this->isModuleCategory($tabModule)) {
            $this->setCategoryFilter($tabModule);
        }

        $this->initToolbar();
        $this->initPageHeaderToolbar();

        // Init
        $smarty = $this->context->smarty;
        $autocompleteList = 'var moduleList = [';

        foreach ($this->list_modules_categories as $k => $v) {
            $this->list_modules_categories[$k]['nb'] = 0;
        }

        // Retrieve Modules Preferences
        $modulesPreferences = [];
        $modulesPreferencesTmp = Db::readOnly()->getArray(
            (new DbQuery())
            ->select('*')
            ->from('module_preference')
            ->where('`id_employee` = '.(int) $this->id_employee)
        );

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
        $modules = Module::getModulesOnDisk(true, false, $this->id_employee);
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

        $premiumModulesCount = 0;
        // Browse modules list
        foreach ($modules as $km => $module) {
            if ($module->premium) {
                $premiumModulesCount++;
            }
            //if we are in favorites view we only display installed modules
            if (Tools::getValue('select') == static::CATEGORY_FAVORITES && !$module->id) {
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
            if ($module->active && !empty($module->warning) && !$this->ajax) {
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
            if ($this->isModuleFiltered($module) && Tools::getValue('select') != static::CATEGORY_FAVORITES) {
                unset($modules[$km]);
            } else {
                if (isset($modulesPreferences[$modules[$km]->name])) {
                    $modules[$km]->preferences = $modulesPreferences[$modules[$km]->name];
                }

                $this->fillModuleData($module);
                $module->categoryName = $this->list_modules_categories[$module->tab]['name'] ?? $this->list_modules_categories[static::CATEGORY_OTHERS]['name'];
            }
            unset($object);
            if ($module->installed && isset($module->version_addons) && $module->version_addons) {
                $upgradeAvailable[] = [
                    'anchor' => ucfirst($module->name),
                    'name' => $module->name,
                    'displayName' => $module->displayName
                ];
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

        if (count($upgradeAvailable) == 0 && Tools::getIntValue('check') === 1) {
            $this->confirmations[] = $this->l('Everything is up-to-date');
        }

        // Sort modules by display name from their config.xml instad of their `name` property.
        uasort($modules, function($a, $b) {
            return strcoll(mb_strtolower($a->displayName), mb_strtolower($b->displayName));
        });

        $connectLink = null;
        $connected = (bool)Configuration::getGlobalValue(Configuration::CONNECTED);
        if (! $connected) {
            if ($this->context->employee->hasAccess(AdminConnectController::class, Profile::PERMISSION_VIEW)) {
                $connectLink = $this->context->link->getAdminLink('AdminConnect', true, [ AdminConnectController::ACTION_CONNECT => 1 ]);
            }
        }

        $supporter = Configuration::getSupporterInfo();

        // Init tpl vars for smarty
        $tplVars = [
            'token'                     => $this->token,
            'upgrade_available'         => $upgradeAvailable,
            'currentIndex'              => static::$currentIndex,
            'dirNameCurrentIndex'       => dirname(static::$currentIndex),
            'ajaxCurrentIndex'          => str_replace('index', 'ajax-tab', static::$currentIndex),
            'autocompleteList'          => rtrim($autocompleteList, ' ,').'];',
            'showTypeModules'           => $this->filter_configuration['PS_SHOW_TYPE_MODULES_'.(int) $this->id_employee],
            'showInstalledModules'      => $this->filter_configuration['PS_SHOW_INSTALLED_MODULES_'.(int) $this->id_employee],
            'showEnabledModules'        => $this->filter_configuration['PS_SHOW_ENABLED_MODULES_'.(int) $this->id_employee],
            'nameCountryDefault'        => Country::getNameById($this->context->language->id, Configuration::get('PS_COUNTRY_DEFAULT')),
            'isoCountryDefault'         => $this->iso_default_country,
            'selectedCategory'          => $this->getCategoryFilter(),
            'modules'                   => $modules,
            'nb_modules'                => $this->nb_modules_total,
            'nb_modules_premium'        => $premiumModulesCount,
            'nb_modules_favorites'      => count($this->context->employee->favoriteModulesList()),
            'nb_modules_installed'      => $this->nb_modules_installed,
            'nb_modules_uninstalled'    => $this->nb_modules_total - $this->nb_modules_installed,
            'nb_modules_activated'      => $this->nb_modules_activated,
            'nb_modules_unactivated'    => $this->nb_modules_installed - $this->nb_modules_activated,
            'list_modules_categories'   => $cleanedList,
            'list_modules_authors'      => $this->modules_authors,
            'add_permission'            => $this->hasAddPermission(),
            'kpis'                      => $this->renderKpis(),
            'module_name'               => Tools::getValue('module_name'),
            'page_header_toolbar_title' => $this->page_header_toolbar_title,
            'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            'modules_uri'               => __PS_BASE_URI__.basename(_PS_MODULE_DIR_),
            'dont_filter'               => $dontFilter,
            'maintenance_mode'          => !Configuration::Get('PS_SHOP_ENABLE'),
            'connected'                 => $connected,
            'connectLink'               => $connectLink,
            'showBecomeSupporterButton' => !$supporter,
            'becomeSupporterUrl' => Configuration::getBecomeSupporterUrl(),
        ];

        $smarty->assign($tplVars);
    }

    /**
     * Initialize module list
     *
     * @param array $modules
     *
     * @throws PrestaShopException
     */
    public function initModulesList(&$modules)
    {
        foreach ($modules as $k => $module) {
            // Check add permissions, if add permissions not set, addons modules and uninstalled modules will not be displayed
            if (!$this->hasAddPermission()) {
                unset($modules[$k]);
            } elseif (!$this->hasAddPermission() && (!isset($module->id) || $module->id < 1)) {
                unset($modules[$k]);
            } elseif ($module->id && !Module::getPermissionStatic($module->id, 'view') && !Module::getPermissionStatic($module->id, 'configure')) {
                unset($modules[$k]);
            } else {
                // Init serial and modules author list
                $this->serial_modules .= $module->name.' '.$module->version.'-'.($module->active ? 'a' : 'i')."\n";
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
     * @param stdClass $module
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
            $this->list_modules_categories[static::CATEGORY_OTHERS]['nb']++;
        }
    }

    /**
     * Is the module filtered?
     *
     * @param stdClass $module
     *
     * @return bool
     *
     * @throws PrestaShopException
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
        } elseif ((int) Db::readOnly()->getValue(
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
        $selectedCategory = $this->getCategoryFilter();
        if ($selectedCategory === static::CATEGORY_FAVORITES) {
            if ((int) Db::readOnly()->getValue(
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
        } elseif ($selectedCategory === static::CATEGORY_PREMIUM)  {
            if (! $module->premium) {
                return true;
            }
        } elseif ($selectedCategory !== static::CATEGORY_ALL) {
            // Handle "others" category
            $moduleCategory = $this->isModuleCategory($module->tab)
                ? $module->tab
                : static::CATEGORY_OTHERS;

            if ($moduleCategory !== $selectedCategory) {
                return true;
            }
        }

        // Filter on module type and author
        $showTypeModules = $this->filter_configuration['PS_SHOW_TYPE_MODULES_'.(int) $this->id_employee];
        if (strpos($showTypeModules, 'authorModules[') !== false) {
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

        // Module has not been filtered
        return false;
    }

    /**
     * Generate html errors for a module process
     *
     * @param array $moduleErrors
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
                } else {
                    $htmlErrorDescription = $this->l('Unknown error');
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
     * @return false|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
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

        // Show how many modules can be updated from api server
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

        $helper = new HelperKpiRow();
        $helper->kpis = $kpis;

        return $helper->generate();
    }

    /**
     * Ajax process get tab modules list
     *
     * @return void
     */
    public function ajaxProcessGetTabModulesList()
    {
        Tools::displayAsDeprecated();
        exit;
    }

    /**
     * Filter Configuration Methods
     * Set and reset filter configuration
     *
     * @param string[] $tabModulesList
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
                if ($module->id) {
                    $perm = Module::getPermissionStatic($module->id, 'configure');
                } else {
                    $perm = $this->hasEditPermission();
                }

                if ($perm) {
                    $this->fillModuleData($module);
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
     * @throws PrestaShopException
     */
    public function ajaxProcessSetFilter()
    {
        $this->setFilterModules(
            Tools::getValue('module_type'),
            '',
            Tools::getValue('module_install'),
            Tools::getValue('module_status')
        );
        $this->ajaxDie('OK');
    }

    /**
     * Post Process Filter
     *
     * @param string $moduleType
     * @param string $countryModuleValue
     * @param string $moduleInstall
     * @param string $moduleStatus
     *
     * @throws PrestaShopException
     */
    protected function setFilterModules($moduleType, $countryModuleValue, $moduleInstall, $moduleStatus)
    {
        Configuration::updateValue('PS_SHOW_TYPE_MODULES_'.(int) $this->id_employee, $moduleType);
        Configuration::updateValue('PS_SHOW_INSTALLED_MODULES_'.(int) $this->id_employee, $moduleInstall);
        Configuration::updateValue('PS_SHOW_ENABLED_MODULES_'.(int) $this->id_employee, $moduleStatus);
    }

    /**
     * Ajax process save favorite preferences
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessSaveFavoritePreferences()
    {
        $action = Tools::getValue('action_pref');
        $value = Tools::getValue('value_pref');
        $module = Tools::getValue('module_pref');
        $idModulePreference = (int) Db::readOnly()->getValue(
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
        $this->ajaxDie('OK');
    }

    /**
     * Post process filter modules
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function postProcessFilterModules()
    {
        $this->setFilterModules(
            Tools::getValue('module_type'),
            '',
            Tools::getValue('module_install'),
            Tools::getValue('module_status')
        );
        Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
    }

    /**
     * Post Process Module CallBack
     *
     * @return void
     *
     * @throws PrestaShopException
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
     * @throws PrestaShopException
     */
    protected function resetFilterModules()
    {
        Configuration::updateValue('PS_SHOW_TYPE_MODULES_'.(int) $this->id_employee, 'allModules');
        Configuration::updateValue('PS_SHOW_INSTALLED_MODULES_'.(int) $this->id_employee, 'installedUninstalled');
        Configuration::updateValue('PS_SHOW_ENABLED_MODULES_'.(int) $this->id_employee, 'enabledDisabled');
        $this->setCategoryFilter(static::CATEGORY_ALL);
    }

    /**
     * Post process filter category
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function postProcessFilterCategory()
    {
        // Save configuration and redirect employee
        $this->setCategoryFilter(Tools::getValue('filterCategory'));
        Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
    }

    /**
     * Post process unfilter category
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function postProcessUnfilterCategory()
    {
        // Save configuration and redirect employee
        $this->setCategoryFilter(static::CATEGORY_ALL);
        Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
    }

    /**
     * Post process reset
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcessReset()
    {
        if ($this->hasEditPermission()) {
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
     */
    public function postProcessDownload()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return;
        }

        // Try to upload and unarchive the module
        if ($this->hasAddPermission()) {
            if (isset($_FILES['file']['error']) && $_FILES['file']['error'] != UPLOAD_ERR_OK) {
                $this->errors[] = Tools::decodeUploadError($_FILES['file']['error']);
            } elseif (empty($_FILES['file']['tmp_name'])) {
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
     * @param bool $redirect
     *
     * @return bool
     */
    protected function extractArchive($file, $redirect = true)
    {
        try {
            $packageExtractor = new PackageExtractor(_PS_MODULE_DIR_);

            // inspect package and find module directory
            $directories = $packageExtractor->getPackageTopLevelDirectories($file);

            $packageName = htmlentities(basename($file));
            if (count($directories) > 1) {
                $errorMessage = sprintf(Tools::displayError('Package %1$s contains multiple top-level entries: '), $packageName);
                $errorMessage .= '<ul>';
                foreach ($directories as $directory) {
                    $errorMessage .= '<li>' . htmlentities($directory) . '</li>';
                }
                $errorMessage .= '</ul>';
                $this->errors[] = $errorMessage;
                return false;
            }

            if (count($directories) !== 1) {
                $this->errors[] = sprintf(Tools::displayError('Package %1$s does not contain valid module directory'), $packageName);
                return false;
            }
            $moduleName = $directories[0];

            // check module directory
            if (!Validate::isModuleName($moduleName)) {
                $this->errors[] = sprintf(Tools::displayError('Package %1$s does not contain valid module'), $packageName);
                return false;
            }

            // add package validator
            $packageExtractor->setPackageValidator([ $this, 'validatePackage' ]);

            // extract package
            if (!$packageExtractor->extractPackage($file, $moduleName)) {
                $errors = $packageExtractor->getErrors();
                if ($errors) {
                    foreach ($errors as $error) {
                        $this->errors[] = $error['message'];
                    }
                } else {
                    $this->errors[] = Tools::displayError('There was an error while extracting the module (file may be corrupted).');
                }
                return false;
            }

            if ($redirect) {
                // redirect halts the script execution, finally block won't get a chance to run. We need to clean up upfront
                @unlink($file);
                Tools::redirectAdmin(static::$currentIndex . '&conf=8&anchor=' . ucfirst($moduleName) . '&token=' . $this->token);
            }

            return true;
        } finally {
            @unlink($file);
        }
    }

    /**
     * @param array $packageContent
     * @param string $moduleName
     *
     * @return string[]
     */
    public function validatePackage($packageContent, $moduleName)
    {
        $errors = [] ;
        $files = array_keys($packageContent);
        if (! $files) {
            $errors[] = Tools::displayError('Empty package');
        } else {
            $moduleMainFile = $moduleName . '/' . $moduleName . '.php';
            if (!isset($packageContent[$moduleMainFile])) {
                $errors[] = sprintf(Tools::displayError('Module main file %1$s not found in the package'), $moduleMainFile);
            }
        }

        return $errors;
    }

    /**
     * Recursive delete on disk
     *
     * @param string $dir
     *
     * @return void
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcessEnable()
    {
        if ($this->hasEditPermission()) {
            $module = Module::getInstanceByName(Tools::getValue('module_name'));
            if (Validate::isLoadedObject($module)) {
                if (!$module->getPermission('configure')) {
                    $this->errors[] = Tools::displayError('You do not have the permission to use this module.');
                } else {
                    if (Tools::getValue('enable')) {
                        $moduleInfo = static::getModuleInfo($module->name);
                        $canEnable = true;
                        if ($moduleInfo && $moduleInfo->premium) {
                            $canEnable = $moduleInfo->canInstall;
                        }
                        if ($canEnable) {
                            $module->enable();
                        } else {
                            $this->errors[] = Tools::displayError('You can\'t enable this premium module.');
                        }
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
     * @return string
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcessEnable_Device()
    {
        if ($this->hasEditPermission()) {
            $module = Module::getInstanceByName(Tools::getValue('module_name'));
            if (Validate::isLoadedObject($module)) {
                if (!$module->getPermission('configure')) {
                    $this->errors[] = Tools::displayError('You do not have the permission to use this module.');
                } else {
                    $module->enableDevice(Tools::getIntValue('enable_device'));
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcessDisable_Device()
    {
        if ($this->hasEditPermission()) {
            $module = Module::getInstanceByName(Tools::getValue('module_name'));
            if (Validate::isLoadedObject($module)) {
                if (!$module->getPermission('configure')) {
                    $this->errors[] = Tools::displayError('You do not have the permission to use this module.');
                } else {
                    $module->disableDevice(Tools::getIntValue('disable_device'));
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcessDelete()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return;
        }

        if ($this->hasDeletePermission()) {
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
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
                foreach ($modulesOnDisk as $moduleOnDisk) {
                    if (!Tools::getValue('module_name') && isset($moduleOnDisk->version_addons) && $moduleOnDisk->version_addons) {
                        $modules[] = $moduleOnDisk->name;
                    }
                }
                if (!Tools::getValue('module_name')) {
                    $modulesListSave = implode('|', $modules);
                }
            } elseif ($key == 'updateAll') {
                $allModules = Module::getModulesOnDisk(true, false, $this->context->employee->id);
                $modules = [];
                foreach ($allModules as $moduleToUpdate) {
                    if ($moduleToUpdate->installed && isset($moduleToUpdate->version_addons) && $moduleToUpdate->version_addons) {
                        $modules[] = (string) $moduleToUpdate->name;
                    }
                }
            } elseif ($modules = Tools::getValue($key)) {
                if (strpos($modules, '|')) {
                    $modulesListSave = $modules;
                    $modules = explode('|', $modules);
                }
                if (!is_array($modules)) {
                    $modules = (array) $modules;
                }
                $modules = array_filter($modules, [Validate::class, 'isModuleName']);
            }

            $moduleErrors = [];
            if (isset($modules)) {
                foreach ($modules as $name) {
                    // If Addons module, download and unzip it before installing it
                    if (!file_exists(_PS_MODULE_DIR_.$name.'/'.$name.'.php') || $key == 'update' || $key == 'updateAll') {
                        foreach (Module::getApiModulesInfo() as $moduleInfoName => $moduleInfo) {
                            if (mb_strtolower($name) == mb_strtolower($moduleInfoName)) {
                                if (!$this->downloadModuleFromApi(mb_strtolower($name), $moduleInfo)) {
                                    $this->errors[] = sprintf(Tools::displayError('Failed to download module "%s" from api server'), $name);
                                }
                            }
                        }
                        Tools::clearOpCache();
                    }

                    if (!($module = Module::getInstanceByName(urldecode($name)))) {
                        $this->errors[] = $this->l('Module not found');
                    } elseif ($key == 'install' && !$this->hasAddPermission()) {
                        $this->errors[] = Tools::displayError('You do not have permission to install this module.');
                    } elseif ($key == 'delete' && (!$this->hasDeletePermission() || !$module->getPermission('configure'))) {
                        $this->errors[] = Tools::displayError('You do not have permission to delete this module.');
                    } elseif ($key == 'configure' && (!$this->hasEditPermission() || !$module->getPermission('configure') || !Module::isInstalled(urldecode($name)))) {
                        $this->errors[] = Tools::displayError('You do not have permission to configure this module.');
                    } elseif ($key == 'install' && Module::isInstalled($module->name)) {
                        $this->errors[] = sprintf(Tools::displayError('This module is already installed: %s.'), $module->name);
                    } elseif ($key == 'uninstall' && !Module::isInstalled($module->name)) {
                        $this->errors[] = sprintf(Tools::displayError('This module has already been uninstalled: %s.'), $module->name);
                    } elseif ($key == 'update' && !Module::isInstalled($module->name)) {
                        $this->errors[] = sprintf(Tools::displayError('This module needs to be installed in order to be updated: %s.'), $module->name);
                    } else {
                        // If we install a module, force temporary global context for multishop
                        $tmpOldShop = null;
                        if (Shop::isFeatureActive() && Shop::getContext() !== Shop::CONTEXT_ALL && $method !== 'getContent') {
                            $shopId = (int) $this->context->shop->id;
                            if ($shopId) {
                                $tmpOldShop = $this->context->shop;
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
                                    'add_permission'            => $this->hasAddPermission(),
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

                        if ($tmpOldShop) {
                            $this->context->shop = $tmpOldShop;
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

        if (! $this->errors) {
            // optional redirect, but only if no errors occured
            if ($return) {
                $params = (count($installedModules)) ? '&installed_modules=' . implode('|', $installedModules) : '';
                // If redirect parameter is present and module installed with success, we redirect on configuration module page
                if (Tools::getValue('redirect') == 'config' && Tools::getValue('module_name') != '' && $return == '12' && Module::isInstalled(pSQL(Tools::getValue('module_name')))) {
                    Tools::redirectAdmin('index.php?controller=adminmodules&configure=' . Tools::getValue('module_name') . '&token=' . Tools::getValue('token') . '&module_name=' . Tools::getValue('module_name') . $params);
                }
                if (isset($module)) {
                    Tools::redirectAdmin(static::$currentIndex . '&conf=' . $return . '&token=' . $this->token . '&tab_module=' . $module->tab . '&module_name=' . $module->name . '&anchor=' . ucfirst($module->name) . (isset($modulesListSave) ? '&modules_list=' . $modulesListSave : '') . $params);
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
                    Tools::redirectAdmin(static::$currentIndex . '&token=' . $this->token . $updated . '&tab_module=' . $module->tab . '&module_name=' . $module->name . '&anchor=' . ucfirst($module->name) . (isset($modulesListSave) ? '&modules_list=' . $modulesListSave : ''));
                }
            }
        }
    }

    /**
     * Initialize modal
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
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
     * Returns selected category filter
     *
     * @return string
     * @throws PrestaShopException
     */
    public function getCategoryFilter()
    {
        $value = Configuration::get($this->getCategoryFilterConfigKey());
        if ($value && $this->isModuleCategory($value)) {
            return $value;
        }
        return static::CATEGORY_ALL;
    }

    /**
     * Updates selected category filter
     *
     * @param string $category
     * @return void
     * @throws PrestaShopException
     */
    public function setCategoryFilter($category)
    {
        if (! $this->isModuleCategory($category)) {
            $category = static::CATEGORY_ALL;
        }
        Configuration::updateValue($this->getCategoryFilterConfigKey(), $category);
    }

    /**
     * Returns configuration key for selected category filter
     *
     * @return string
     */
    protected function getCategoryFilterConfigKey()
    {
        return 'PS_SHOW_CAT_MODULES_' . (int)$this->id_employee;
    }

    /**
     * Returns true if $category is valid module category
     *
     * @param string $category
     * @return bool
     */
    protected function isModuleCategory($category)
    {
        if ($category === static::CATEGORY_ALL || $category === static::CATEGORY_FAVORITES || $category === static::CATEGORY_PREMIUM) {
            return true;
        }
        return isset($this->list_modules_categories[$category]);
    }

    /**
     * Download module from thirty bees api server
     *
     * @param string $moduleName
     * @param array $moduleInfo
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function downloadModuleFromApi($moduleName, $moduleInfo)
    {
        if (!isset($moduleInfo['binary'])) {
            return false;
        }

        $zipLocation = _PS_MODULE_DIR_.$moduleName.'.zip';
        if (!file_exists($zipLocation)) {
            $guzzle = new GuzzleHttp\Client([
                'timeout' => 30,
                'verify'  => Configuration::getSslTrustStore(),
            ]);
            try {
                $guzzle->get($moduleInfo['binary'], ['sink' => $zipLocation]);
            } catch (Throwable $e) {
                return false;
            }
        }
        if (file_exists($zipLocation)) {
            return $this->extractArchive($zipLocation, false);
        }
        return false;
    }

    /**
     * @param string $moduleName
     *
     * @return stdClass|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getModuleInfo($moduleName)
    {
        $modules = Module::getModulesOnDisk(true);
        if ($modules) {
            $moduleName = mb_strtolower($moduleName);
            foreach ($modules as $module) {
                if ($moduleName === mb_strtolower((string)$module->name)) {
                    return $module;
                }
            }
        }
        return false;
    }
}
