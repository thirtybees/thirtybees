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
 * Class AdminDashboardControllerCore
 *
 * @since 1.0.0
 */
class AdminDashboardControllerCore extends AdminController
{
    /**
     * AdminDashboardControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();

        if (Tools::isSubmit('profitability_conf') || Tools::isSubmit('submitOptionsconfiguration')) {
            $this->fields_options = $this->getOptionFields();
        }
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    protected function getOptionFields()
    {
        $forms = [];
        $currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        $carriers = Carrier::getCarriers($this->context->language->id, true, false, false, null, 'ALL_CARRIERS');
        $modules = Module::getModulesOnDisk(true);

        $forms = [
            'payment'  => ['title' => $this->l('Average bank fees per payment method'), 'id' => 'payment'],
            'carriers' => ['title' => $this->l('Average shipping fees per shipping method'), 'id' => 'carriers'],
            'other'    => ['title' => $this->l('Other settings'), 'id' => 'other'],
        ];
        foreach ($forms as &$form) {
            $form['icon'] = 'tab-preferences';
            $form['fields'] = [];
            $form['submit'] = ['title' => $this->l('Save')];
        }

        foreach ($modules as $module) {
            if (isset($module->tab) && $module->tab == 'payments_gateways' && $module->id) {
                $moduleClass = Module::getInstanceByName($module->name);
                if (!$moduleClass->isEnabledForShopContext()) {
                    continue;
                }

                $forms['payment']['fields']['CONF_'.strtoupper($module->name).'_FIXED'] = [
                    'title'        => $module->displayName,
                    'desc'         => sprintf($this->l('Choose a fixed fee for each order placed in %1$s with %2$s.'), $currency->iso_code, $module->displayName),
                    'validation'   => 'isPrice',
                    'cast'         => 'floatval',
                    'type'         => 'text',
                    'defaultValue' => '0',
                    'suffix'       => $currency->iso_code,
                ];
                $forms['payment']['fields']['CONF_'.strtoupper($module->name).'_VAR'] = [
                    'title'        => $module->displayName,
                    'desc'         => sprintf($this->l('Choose a variable fee for each order placed in %1$s with %2$s. It will be applied on the total paid with taxes.'), $currency->iso_code, $module->displayName),
                    'validation'   => 'isPercentage',
                    'cast'         => 'floatval',
                    'type'         => 'text',
                    'defaultValue' => '0',
                    'suffix'       => '%',
                ];

                if (Currency::isMultiCurrencyActivated()) {
                    $forms['payment']['fields']['CONF_'.strtoupper($module->name).'_FIXED_FOREIGN'] = [
                        'title'        => $module->displayName,
                        'desc'         => sprintf($this->l('Choose a fixed fee for each order placed with a foreign currency with %s.'), $module->displayName),
                        'validation'   => 'isPrice',
                        'cast'         => 'floatval',
                        'type'         => 'text',
                        'defaultValue' => '0',
                        'suffix'       => $currency->iso_code,
                    ];
                    $forms['payment']['fields']['CONF_'.strtoupper($module->name).'_VAR_FOREIGN'] = [
                        'title'        => $module->displayName,
                        'desc'         => sprintf($this->l('Choose a variable fee for each order placed with a foreign currency with %s. It will be applied on the total paid with taxes.'), $module->displayName),
                        'validation'   => 'isPercentage',
                        'cast'         => 'floatval',
                        'type'         => 'text',
                        'defaultValue' => '0',
                        'suffix'       => '%',
                    ];
                }
            }
        }

        foreach ($carriers as $carrier) {
            $forms['carriers']['fields']['CONF_'.strtoupper($carrier['id_reference']).'_SHIP'] = [
                'title'        => $carrier['name'],
                'desc'         => sprintf($this->l('For the carrier named %s, indicate the domestic delivery costs  in percentage of the price charged to customers.'), $carrier['name']),
                'validation'   => 'isPercentage',
                'cast'         => 'floatval',
                'type'         => 'text',
                'defaultValue' => '0',
                'suffix'       => '%',
            ];
            $forms['carriers']['fields']['CONF_'.strtoupper($carrier['id_reference']).'_SHIP_OVERSEAS'] = [
                'title'        => $carrier['name'],
                'desc'         => sprintf($this->l('For the carrier named %s, indicate the overseas delivery costs in percentage of the price charged to customers.'), $carrier['name']),
                'validation'   => 'isPercentage',
                'cast'         => 'floatval',
                'type'         => 'text',
                'defaultValue' => '0',
                'suffix'       => '%',
            ];
        }

        $forms['carriers']['description'] = $this->l('Method: Indicate the percentage of your carrier margin. For example, if you charge $10 of shipping fees to your customer for each shipment, but you really pay $4 to this carrier, then you should indicate "40" in the percentage field.');

        $forms['other']['fields']['CONF_AVERAGE_PRODUCT_MARGIN'] = [
            'title'        => $this->l('Average gross margin percentage'),
            'desc'         => $this->l('You should calculate this percentage as follows: ((total sales revenue) - (cost of goods sold)) / (total sales revenue) * 100. This value is only used to calculate the Dashboard approximate gross margin, if you do not specify the wholesale price for each product.'),
            'validation'   => 'isPercentage',
            'cast'         => 'intval',
            'type'         => 'text',
            'defaultValue' => '0',
            'suffix'       => '%',
        ];

        $forms['other']['fields']['CONF_ORDER_FIXED'] = [
            'title'        => $this->l('Other fees per order'),
            'desc'         => $this->l('You should calculate this value by making the sum of all of your additional costs per order.'),
            'validation'   => 'isPrice',
            'cast'         => 'floatval',
            'type'         => 'text',
            'defaultValue' => '0',
            'suffix'       => $currency->iso_code,
        ];

        Media::addJsDef(
            [
                'dashboard_ajax_url' => $this->context->link->getAdminLink('AdminDashboard'),
                'read_more'          => '',
            ]
        );

        return $forms;
    }

    /**
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();

        $this->addJqueryUI('ui.datepicker');
        $this->addJS(
            [
                _PS_JS_DIR_.'vendor/d3.v3.min.js',
                __PS_BASE_URI__.$this->admin_webpath.'/themes/'.$this->bo_theme.'/js/vendor/nv.d3.min.js',
                _PS_JS_DIR_.'/admin/dashboard.js',
            ]
        );
        $this->addCSS(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$this->bo_theme.'/css/vendor/nv.d3.css');
    }

    /**
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_title = $this->l('Dashboard');
        $this->page_header_toolbar_btn['switch_demo'] = [
            'desc' => $this->l('Demo mode', null, null, false),
            'icon' => 'process-icon-toggle-'.(Configuration::get('PS_DASHBOARD_SIMULATION') ? 'on' : 'off'),
            'help' => $this->l('This mode displays sample data so you can try your dashboard without real numbers.', null, null, false),
        ];

        parent::initPageHeaderToolbar();

        // Remove the last element on this controller to match the title with the rule of the others
        array_pop($this->meta_title);
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function renderView()
    {
        if (Tools::isSubmit('profitability_conf')) {
            return parent::renderOptions();
        }

        // $translations = array(
        // 	'Calendar' => $this->l('Calendar', 'AdminStatsTab'),
        // 	'Day' => $this->l('Day', 'AdminStatsTab'),
        // 	'Month' => $this->l('Month', 'AdminStatsTab'),
        // 	'Year' => $this->l('Year', 'AdminStatsTab'),
        // 	'From' => $this->l('From:', 'AdminStatsTab'),
        // 	'To' => $this->l('To:', 'AdminStatsTab'),
        // 	'Save' => $this->l('Save', 'AdminStatsTab')
        // );

        $testStatsDateUpdate = $this->context->cookie->__get('stats_date_update');
        if (!empty($testStatsDateUpdate) && $this->context->cookie->__get('stats_date_update') < strtotime(date('Y-m-d'))) {
            switch ($this->context->employee->preselect_date_range) {
                case 'day':
                    $dateFrom = date('Y-m-d');
                    $dateTo = date('Y-m-d');
                    break;
                case 'prev-day':
                    $dateFrom = date('Y-m-d', strtotime('-1 day'));
                    $dateTo = date('Y-m-d', strtotime('-1 day'));
                    break;
                case 'month':
                default:
                    $dateFrom = date('Y-m-01');
                    $dateTo = date('Y-m-d');
                    break;
                case 'prev-month':
                    $dateFrom = date('Y-m-01', strtotime('-1 month'));
                    $dateTo = date('Y-m-t', strtotime('-1 month'));
                    break;
                case 'year':
                    $dateFrom = date('Y-01-01');
                    $dateTo = date('Y-m-d');
                    break;
                case 'prev-year':
                    $dateFrom = date('Y-m-01', strtotime('-1 year'));
                    $dateTo = date('Y-12-t', strtotime('-1 year'));
                    break;
            }
            $this->context->employee->stats_date_from = $dateFrom;
            $this->context->employee->stats_date_to = $dateTo;
            $this->context->employee->update();
            $this->context->cookie->__set('stats_date_update', strtotime(date('Y-m-d')));
            $this->context->cookie->write();
        }

        $calendarHelper = new HelperCalendar();

        $calendarHelper->setDateFrom(Tools::getValue('date_from', $this->context->employee->stats_date_from));
        $calendarHelper->setDateTo(Tools::getValue('date_to', $this->context->employee->stats_date_to));

        $statsCompareFrom = $this->context->employee->stats_compare_from;
        $statsCompareTo = $this->context->employee->stats_compare_to;

        if (is_null($statsCompareFrom) || $statsCompareFrom == '0000-00-00') {
            $statsCompareFrom = null;
        }

        if (is_null($statsCompareTo) || $statsCompareTo == '0000-00-00') {
            $statsCompareTo = null;
        }

        $calendarHelper->setCompareDateFrom($statsCompareFrom);
        $calendarHelper->setCompareDateTo($statsCompareTo);
        $calendarHelper->setCompareOption(Tools::getValue('compare_date_option', $this->context->employee->stats_compare_option));

        $params = [
            'date_from' => $this->context->employee->stats_date_from,
            'date_to'   => $this->context->employee->stats_date_to,
        ];

        $this->tpl_view_vars = [
            'date_from'               => $this->context->employee->stats_date_from,
            'date_to'                 => $this->context->employee->stats_date_to,
            'hookDashboardZoneOne'    => Hook::exec('dashboardZoneOne', $params),
            'hookDashboardZoneTwo'    => Hook::exec('dashboardZoneTwo', $params),
            //'translations' => $translations,
            'action'                  => '#',
            'warning'                 => $this->getWarningDomainName(),
            'new_version_url'         => Tools::getCurrentUrlProtocolPrefix()._PS_API_DOMAIN_.'/version/check_version.php?v='._PS_VERSION_.'&lang='.$this->context->language->iso_code.'&autoupgrade='.(int) (Module::isInstalled('autoupgrade') && Module::isEnabled('autoupgrade')).'&hosted_mode='.(int) defined('_PS_HOST_MODE_'),
            'dashboard_use_push'      => Configuration::get('PS_DASHBOARD_USE_PUSH'),
            'calendar'                => $calendarHelper->generate(),
            'PS_DASHBOARD_SIMULATION' => Configuration::get('PS_DASHBOARD_SIMULATION'),
            'datepickerFrom'          => Tools::getValue('datepickerFrom', $this->context->employee->stats_date_from),
            'datepickerTo'            => Tools::getValue('datepickerTo', $this->context->employee->stats_date_to),
            'preselect_date_range'    => Tools::getValue('preselectDateRange', $this->context->employee->preselect_date_range),
        ];

        return parent::renderView();
    }

    /**
     * @return bool|null|string
     *
     * @since 1.0.0
     */
    protected function getWarningDomainName()
    {
        $warning = false;
        if (Shop::isFeatureActive()) {
            return null;
        }

        $shop = Context::getContext()->shop;
        if ($_SERVER['HTTP_HOST'] != $shop->domain && $_SERVER['HTTP_HOST'] != $shop->domain_ssl && Tools::getValue('ajax') == false && !defined('_PS_HOST_MODE_')) {
            $warning = $this->l('You are currently connected under the following domain name:').' <span style="color: #CC0000;">'.$_SERVER['HTTP_HOST'].'</span><br />';
            if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
                $warning .= sprintf($this->l('This is different from the shop domain name set in the Multistore settings: "%s".'), $shop->domain).'
				'.preg_replace('@{link}(.*){/link}@', '<a href="index.php?controller=AdminShopUrl&id_shop_url='.(int) $shop->id.'&updateshop_url&token='.Tools::getAdminTokenLite('AdminShopUrl').'">$1</a>', $this->l('If this is your main domain, please {link}change it now{/link}.'));
            } else {
                $warning .= $this->l('This is different from the domain name set in the "SEO & URLs" tab.').'
				'.preg_replace('@{link}(.*){/link}@', '<a href="index.php?controller=AdminMeta&token='.Tools::getAdminTokenLite('AdminMeta').'#meta_fieldset_shop_url">$1</a>', $this->l('If this is your main domain, please {link}change it now{/link}.'));
            }
        }

        return $warning;
    }

    /**
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitDateRealTime')) {
            if ($useRealtime = (int) Tools::getValue('submitDateRealTime')) {
                $this->context->employee->stats_date_from = date('Y-m-d');
                $this->context->employee->stats_date_to = date('Y-m-d');
                $this->context->employee->stats_compare_option = HelperCalendar::DEFAULT_COMPARE_OPTION;
                $this->context->employee->stats_compare_from = null;
                $this->context->employee->stats_compare_to = null;
                $this->context->employee->update();
            }

            Configuration::updateValue('PS_DASHBOARD_USE_PUSH', $useRealtime);
        }

        if (Tools::isSubmit('submitDateRange')) {
            if (!Validate::isDate(Tools::getValue('date_from'))
                || !Validate::isDate(Tools::getValue('date_to'))
            ) {
                $this->errors[] = Tools::displayError('The selected date range is not valid.');
            }

            if (Tools::getValue('datepicker_compare')) {
                if (!Validate::isDate(Tools::getValue('compare_date_from'))
                    || !Validate::isDate(Tools::getValue('compare_date_to'))
                ) {
                    $this->errors[] = Tools::displayError('The selected date range is not valid.');
                }
            }

            if (!count($this->errors)) {
                $this->context->employee->stats_date_from = Tools::getValue('date_from');
                $this->context->employee->stats_date_to = Tools::getValue('date_to');
                $this->context->employee->preselect_date_range = Tools::getValue('preselectDateRange');

                if (Tools::getValue('datepicker_compare')) {
                    $this->context->employee->stats_compare_from = Tools::getValue('compare_date_from');
                    $this->context->employee->stats_compare_to = Tools::getValue('compare_date_to');
                    $this->context->employee->stats_compare_option = Tools::getValue('compare_date_option');
                } else {
                    $this->context->employee->stats_compare_from = null;
                    $this->context->employee->stats_compare_to = null;
                    $this->context->employee->stats_compare_option = HelperCalendar::DEFAULT_COMPARE_OPTION;
                }

                $this->context->employee->update();
            }
        }

        parent::postProcess();
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessRefreshDashboard()
    {
        $idModule = null;
        if ($module = Tools::getValue('module')) {
            $moduleObj = Module::getInstanceByName($module);
            if (Validate::isLoadedObject($moduleObj)) {
                $idModule = $moduleObj->id;
            }
        }

        $params = [
            'date_from'          => $this->context->employee->stats_date_from,
            'date_to'            => $this->context->employee->stats_date_to,
            'compare_from'       => $this->context->employee->stats_compare_from,
            'compare_to'         => $this->context->employee->stats_compare_to,
            'dashboard_use_push' => (int) Tools::getValue('dashboard_use_push'),
            'extra'              => (int) Tools::getValue('extra'),
        ];

        die(json_encode(Hook::exec('dashboardData', $params, $idModule, true, true, (int) Tools::getValue('dashboard_use_push'))));
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessSetSimulationMode()
    {
        Configuration::updateValue('PS_DASHBOARD_SIMULATION', (int) Tools::getValue('PS_DASHBOARD_SIMULATION'));
        die('k'.Configuration::get('PS_DASHBOARD_SIMULATION').'k');
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessGetBlogRss()
    {
        $return = array('has_errors' => false, 'rss' => array());
        if (!$this->isFresh('/config/xml/blog-'.$this->context->language->iso_code.'.xml', 86400)) {
            if (!$this->refresh('/config/xml/blog-'.$this->context->language->iso_code.'.xml', 'https://thirtybees.com/feed/')) {
                $return['has_errors'] = true;
            }
        }
        if (!$return['has_errors']) {
            $rss = @simplexml_load_file(_PS_ROOT_DIR_.'/config/xml/blog-'.$this->context->language->iso_code.'.xml');
            if (!$rss) {
                $return['has_errors'] = true;
            }
            $articlesLimit = 2;
            if ($rss) {
                foreach ($rss->channel->item as $item) {
                    if ($articlesLimit > 0 && Validate::isCleanHtml((string) $item->title) && Validate::isCleanHtml((string) $item->description)
                        && isset($item->link) && isset($item->title)
                    ) {
                        if (in_array($this->context->mode, array(Context::MODE_HOST, Context::MODE_HOST_CONTRIB))) {
                            $utmContent = 'cloud';
                        } else {
                            $utmContent = 'download';
                        }
                        $shopDefaultCountryId = (int) Configuration::get('PS_COUNTRY_DEFAULT');
                        $shopDefaultIsoCountry = (string) Tools::strtoupper(Country::getIsoById($shopDefaultCountryId));
                        $analyticsParams = array(
                            'utm_source'   => 'back-office',
                            'utm_medium'   => 'rss',
                            'utm_campaign' => 'back-office-'.$shopDefaultIsoCountry,
                            'utm_content'  => $utmContent,
                        );
                        $urlQuery = parse_url($item->link, PHP_URL_QUERY);
                        parse_str($urlQuery, $linkQueryParams);
                        if ($linkQueryParams) {
                            $fullUrlParams = array_merge($linkQueryParams, $analyticsParams);
                            $baseUrl = explode('?', (string) $item->link);
                            $baseUrl = (string) $baseUrl[0];
                            $articleLink = $baseUrl.'?'.http_build_query($fullUrlParams);
                        } else {
                            $articleLink = (string) $item->link.'?'.http_build_query($analyticsParams);
                        }
                        $return['rss'][] = array(
                            'date'       => Tools::displayDate(date('Y-m-d', strtotime((string) $item->pubDate))),
                            'title'      => (string) Tools::htmlentitiesUTF8($item->title),
                            'short_desc' => Tools::truncateString(strip_tags((string) $item->description), 150),
                            'link'       => (string) $articleLink,
                        );
                    } else {
                        break;
                    }
                    $articlesLimit--;
                }
            }
        }
        die(json_encode($return));
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessSaveDashConfig()
    {
        $return = ['has_errors' => false, 'errors' => []];
        $module = Tools::getValue('module');
        $hook = Tools::getValue('hook');
        $configs = Tools::getValue('configs');

        $params = [
            'date_from' => $this->context->employee->stats_date_from,
            'date_to'   => $this->context->employee->stats_date_to,
        ];

        if (Validate::isModuleName($module) && $moduleObj = Module::getInstanceByName($module)) {
            if (Validate::isLoadedObject($moduleObj) && method_exists($moduleObj, 'validateDashConfig')) {
                $return['errors'] = $moduleObj->validateDashConfig($configs);
            }
            if (!count($return['errors'])) {
                if (Validate::isLoadedObject($moduleObj) && method_exists($moduleObj, 'saveDashConfig')) {
                    $return['has_errors'] = $moduleObj->saveDashConfig($configs);
                } elseif (is_array($configs) && count($configs)) {
                    foreach ($configs as $name => $value) {
                        if (Validate::isConfigName($name)) {
                            Configuration::updateValue($name, $value);
                        }
                    }
                }
            } else {
                $return['has_errors'] = true;
            }
        }

        if (Validate::isHookName($hook) && method_exists($moduleObj, $hook)) {
            $return['widget_html'] = $moduleObj->$hook($params);
        }

        die(json_encode($return));
    }
}
