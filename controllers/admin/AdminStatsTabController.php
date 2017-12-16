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
 * Class AdminStatsTabControllerCore
 *
 * @since 1.0.0
 */
abstract class AdminStatsTabControllerCore extends AdminPreferencesControllerCore
{
    /**
     * Initialize
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function init()
    {
        parent::init();

        $this->action = 'view';
        $this->display = 'view';
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
        if ($this->ajax) {
            return;
        }

        $this->initTabModuleList();
        $this->addToolBarModulesListButton();
        $this->toolbar_title = $this->l('Stats', 'AdminStatsTab');
        $this->initPageHeaderToolbar();
        if ($this->display == 'view') {
            // Some controllers use the view action without an object
            if ($this->className) {
                $this->loadObject(true);
            }
            $this->content .= $this->renderView();
        }

        $this->content .= $this->displayMenu();
        $this->content .= $this->displayCalendar();
        $this->content .= $this->displayStats();

        $this->context->smarty->assign(
            [
                'content'                   => $this->content,
                'url_post'                  => static::$currentIndex.'&token='.$this->token,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        unset($this->page_header_toolbar_btn['back']);
    }

    /**
     * Display menu
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function displayMenu()
    {
        $tpl = $this->createTemplate('menu.tpl');

        $modules = $this->getModules();
        $moduleInstance = [];
        /** @var StatsModule $statsModuleInstance */
        $statsModuleInstance = Module::getInstanceByName('statsmodule');
        foreach ($modules as $index => $module) {
            if ($module['name'] === 'statsmodule'  ||  Validate::isLoadedObject($statsModuleInstance) && isset($module['hook']) && in_array($module['name'], $statsModuleInstance->modules)) {
                unset($modules[$index]);
                continue;
            }

            if (Validate::isLoadedObject($statsModuleInstance) && $statsModuleInstance->active && in_array($module['name'], $statsModuleInstance->modules)) {
                $moduleInstance[$module['name']] = $statsModuleInstance->executeStatsInstance($module['name']);
                $modules[$index]['displayName'] = $moduleInstance[$module['name']]->displayName;
            } elseif ($moduleInstance[$module['name']] = Module::getInstanceByName($module['name'])) {
                $modules[$index]['displayName'] = $moduleInstance[$module['name']]->displayName;
            } else {
                unset($moduleInstance[$module['name']]);
                unset($modules[$index]);
            }
        }
        uasort($modules, [$this, 'checkModulesNames']);

        $tpl->assign(
            [
                'current'             => static::$currentIndex,
                'current_module_name' => Tools::getValue('module', 'statsforecast'),
                'token'               => $this->token,
                'modules'             => $modules,
                'module_instance'     => $moduleInstance,
            ]
        );

        try {
            return $tpl->fetch();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Get modules
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since 1.0.0
     */
    protected function getModules()
    {
        try {
            $modules = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('h.`name` AS hook, m.`name`')
                    ->from('module', 'm')
                    ->leftJoin('hook_module', 'hm', 'hm.`id_module` = m.`id_module`')
                    ->leftJoin('hook', 'h', 'hm.`id_hook` = h.`id_hook`')
                    ->where('h.`name` = \'displayAdminStatsModules\'')
                    ->where('m.`active` = 1')
                    ->groupBy('hm.`id_module`')
                    ->orderBy('hm.`position`')
            );
        } catch (PrestaShopException $e) {
            $modules = [];
        }
        if ($modules) {
            foreach ($modules as $module) {
                if ($module['name'] == 'statsmodule') {
                    /** @var StatsModule $statsModule */
                    $statsModule = Module::getInstanceByName('statsmodule');
                    $statsModulesList = $statsModule->getStatsModulesList();
                    if ($statsModulesList) {
                        $modules = array_merge($modules, $statsModulesList);
                    }
                }
            }
        }

        return $modules;
    }

    /**
     * @return string
     *
     * @deprecated 1.0.0
     */
    public function displayCalendar()
    {
        return AdminStatsTabController::displayCalendarForm(
            [
                'Calendar' => $this->l('Calendar', 'AdminStatsTab'),
                'Day'      => $this->l('Day', 'AdminStatsTab'),
                'Month'    => $this->l('Month', 'AdminStatsTab'),
                'Year'     => $this->l('Year', 'AdminStatsTab'),
                'From'     => $this->l('From:', 'AdminStatsTab'),
                'To'       => $this->l('To:', 'AdminStatsTab'),
                'Save'     => $this->l('Save', 'AdminStatsTab'),
            ],
            $this->token
        );
    }

    /**
     * Display calendar form
     *
     * @param array       $translations
     * @param string      $token
     * @param string|null $action
     * @param null        $table
     * @param null        $identifier
     * @param null        $id
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function displayCalendarForm($translations, $token, $action = null, $table = null, $identifier = null, $id = null)
    {
        $context = Context::getContext();

        $tpl = $context->controller->createTemplate('calendar.tpl');

        $context->controller->addJqueryUI('ui.datepicker');

        if ($identifier === null && Tools::getValue('module')) {
            $identifier = 'module';
            $id = Tools::getValue('module');
        }

        $action = Context::getContext()->link->getAdminLink('AdminStats');
        $action .= ($action && $table ? '&'.Tools::safeOutput($action) : '');
        $action .= ($identifier && $id ? '&'.Tools::safeOutput($identifier).'='.(int) $id : '');
        $module = Tools::getValue('module');
        $action .= ($module ? '&module='.Tools::safeOutput($module) : '');
        $action .= (($idProduct = Tools::getValue('id_product')) ? '&id_product='.Tools::safeOutput($idProduct) : '');
        $tpl->assign(
            [
                'current'        => static::$currentIndex,
                'token'          => $token,
                'action'         => $action,
                'table'          => $table,
                'identifier'     => $identifier,
                'id'             => $id,
                'translations'   => $translations,
                'datepickerFrom' => Tools::getValue('datepickerFrom', $context->employee->stats_date_from),
                'datepickerTo'   => Tools::getValue('datepickerTo', $context->employee->stats_date_to),
            ]
        );

        try {
            return $tpl->fetch();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Display stats
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function displayStats()
    {
        $tpl = $this->createTemplate('stats.tpl');

        if ((!($moduleName = Tools::getValue('module', 'statsforecast')) || !Validate::isModuleName($moduleName)) && ($moduleInstance = Module::getInstanceByName('statsforecast')) && $moduleInstance->active) {
            return '';
        }

        if ($moduleName) {
            $_GET['module'] = $moduleName;

            if (!isset($moduleInstance)) {
                $moduleInstance = Module::getInstanceByName($moduleName);
            }

            if (Validate::isLoadedObject($moduleInstance) && $moduleInstance && $moduleInstance->active) {
                $hook = Hook::exec('displayAdminStatsModules', null, $moduleInstance->id);
            } else {
                /** @var StatsModule $moduleInstance */
                $moduleInstance = Module::getInstanceByName('statsmodule');
                if (Validate::isLoadedObject($moduleInstance) && $moduleInstance->active && in_array($moduleName, $moduleInstance->modules)) {
                    $hook = $moduleInstance->executeStatsInstance($moduleName, true);
                }
            }

        }

        $tpl->assign(
            [
                'module_name'     => $moduleName,
                'module_instance' => isset($moduleInstance) ? $moduleInstance : null,
                'hook'            => isset($hook) ? $hook : null,
            ]
        );

        try {
            return $tpl->fetch();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Compare module names
     *
     * @param $a
     * @param $b
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function checkModulesNames($a, $b)
    {
        return (bool) ($a['displayName'] > $b['displayName']);
    }

    /**
     * Post processing
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        $this->context = Context::getContext();

        $this->processDateRange();

        if (Tools::getValue('submitSettings')) {
            if ($this->tabAccess['edit'] === '1') {
                static::$currentIndex .= '&module='.Tools::getValue('module');
                Configuration::updateValue('PS_STATS_RENDER', Tools::getValue('PS_STATS_RENDER', Configuration::get('PS_STATS_RENDER')));
                Configuration::updateValue('PS_STATS_GRID_RENDER', Tools::getValue('PS_STATS_GRID_RENDER', Configuration::get('PS_STATS_GRID_RENDER')));
                Configuration::updateValue('PS_STATS_OLD_CONNECT_AUTO_CLEAN', Tools::getValue('PS_STATS_OLD_CONNECT_AUTO_CLEAN', Configuration::get('PS_STATS_OLD_CONNECT_AUTO_CLEAN')));
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }
    }

    /**
     * Process date range
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function processDateRange()
    {
        if (Tools::isSubmit('submitDatePicker')) {
            if ((!Validate::isDate($from = Tools::getValue('datepickerFrom')) || !Validate::isDate($to = Tools::getValue('datepickerTo'))) || (strtotime($from) > strtotime($to))) {
                $this->errors[] = Tools::displayError('The specified date is invalid.');
            }
        }
        if (Tools::isSubmit('submitDateDay')) {
            $from = date('Y-m-d');
            $to = date('Y-m-d');
        }
        if (Tools::isSubmit('submitDateDayPrev')) {
            $yesterday = time() - 60 * 60 * 24;
            $from = date('Y-m-d', $yesterday);
            $to = date('Y-m-d', $yesterday);
        }
        if (Tools::isSubmit('submitDateMonth')) {
            $from = date('Y-m-01');
            $to = date('Y-m-t');
        }
        if (Tools::isSubmit('submitDateMonthPrev')) {
            $m = (date('m') == 1 ? 12 : date('m') - 1);
            $y = ($m == 12 ? date('Y') - 1 : date('Y'));
            $from = $y.'-'.$m.'-01';
            $to = $y.'-'.$m.date('-t', mktime(12, 0, 0, $m, 15, $y));
        }
        if (Tools::isSubmit('submitDateYear')) {
            $from = date('Y-01-01');
            $to = date('Y-12-31');
        }
        if (Tools::isSubmit('submitDateYearPrev')) {
            $from = (date('Y') - 1).date('-01-01');
            $to = (date('Y') - 1).date('-12-31');
        }
        if (isset($from) && isset($to) && !count($this->errors)) {
            $this->context->employee->stats_date_from = $from;
            $this->context->employee->stats_date_to = $to;
            $this->context->employee->update();
            if (!$this->isXmlHttpRequest()) {
                Tools::redirectAdmin($_SERVER['REQUEST_URI']);
            }
        }
    }

    /**
     * Ajax process set dashboard date range
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessSetDashboardDateRange()
    {
        $this->processDateRange();

        if ($this->isXmlHttpRequest()) {
            if (is_array($this->errors) && count($this->errors)) {
                $this->ajaxDie(
                    json_encode(
                        [
                            'has_errors' => true,
                            'errors'     => [$this->errors],
                            'date_from'  => $this->context->employee->stats_date_from,
                            'date_to'    => $this->context->employee->stats_date_to,
                        ]
                    )
                );
            } else {
                $this->ajaxDie(
                    json_encode(
                        [
                            'has_errors' => false,
                            'date_from'  => $this->context->employee->stats_date_from,
                            'date_to'    => $this->context->employee->stats_date_to,
                        ]
                    )
                );
            }
        }
    }

    /**
     * Display engines
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function displayEngines()
    {
        $tpl = $this->createTemplate('engines.tpl');

        $autocleanPeriod = [
            'never' => $this->l('Never', 'AdminStatsTab'),
            'week'  => $this->l('Week', 'AdminStatsTab'),
            'month' => $this->l('Month', 'AdminStatsTab'),
            'year'  => $this->l('Year', 'AdminStatsTab'),
        ];

        $tpl->assign(
            [
                'current'             => static::$currentIndex,
                'token'               => $this->token,
                'graph_engine'        => Configuration::get('PS_STATS_RENDER'),
                'grid_engine'         => Configuration::get('PS_STATS_GRID_RENDER'),
                'auto_clean'          => Configuration::get('PS_STATS_OLD_CONNECT_AUTO_CLEAN'),
                'array_graph_engines' => ModuleGraphEngine::getGraphEngines(),
                'array_grid_engines'  => ModuleGridEngine::getGridEngines(),
                'array_auto_clean'    => $autocleanPeriod,
            ]
        );

        try {
            return $tpl->fetch();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Get date
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function getDate()
    {
        $year = isset($this->context->cookie->stats_year) ? $this->context->cookie->stats_year : date('Y');
        $month = isset($this->context->cookie->stats_month) ? sprintf('%02d', $this->context->cookie->stats_month) : '%';
        $day = isset($this->context->cookie->stats_day) ? sprintf('%02d', $this->context->cookie->stats_day) : '%';

        return $year.'-'.$month.'-'.$day;
    }
}
