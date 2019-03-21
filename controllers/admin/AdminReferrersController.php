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
 * Class AdminReferrersControllerCore
 *
 * @since 1.0.0
 */
class AdminReferrersControllerCore extends AdminController
{
    /**
     * AdminReferrersControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        if (!defined('_PS_ADMIN_DIR_')) {
            define('_PS_ADMIN_DIR_', getcwd().'/..');
        }

        $this->bootstrap = true;
        $this->table = 'referrer';
        $this->className = 'Referrer';
        $this->fields_list = [
            'id_referrer'         => [
                'title' => $this->l('ID'),
                'width' => 25,
                'align' => 'center',
            ],
            'name'                => [
                'title' => $this->l('Name'),
                'width' => 80,
            ],
            'cache_visitors'      => [
                'title' => $this->l('Visitors'),
                'width' => 30,
                'align' => 'center',
            ],
            'cache_visits'        => [
                'title' => $this->l('Visits'),
                'width' => 30,
                'align' => 'center',
            ],
            'cache_pages'         => [
                'title' => $this->l('Pages'),
                'width' => 30,
                'align' => 'center',
            ],
            'cache_registrations' => [
                'title' => $this->l('Reg.'),
                'width' => 30,
                'align' => 'center',
            ],
            'cache_orders'        => [
                'title' => $this->l('Orders'),
                'width' => 30,
                'align' => 'center',
            ],
            'cache_sales'         => [
                'title'  => $this->l('Sales'),
                'width'  => 80,
                'align'  => 'right',
                'prefix' => '<b>',
                'suffix' => '</b>',
                'price'  => true,
            ],
            'cart'                => [
                'title'        => $this->l('Avg. cart'),
                'width'        => 50,
                'align'        => 'right',
                'price'        => true,
                'havingFilter' => true,
            ],
            'cache_reg_rate'      => [
                'title' => $this->l('Reg. rate'),
                'width' => 30,
                'align' => 'center',
            ],
            'cache_order_rate'    => [
                'title' => $this->l('Order rate'),
                'width' => 30,
                'align' => 'center',
            ],
            'fee0'                => [
                'title'        => $this->l('Click'),
                'width'        => 30,
                'align'        => 'right',
                'price'        => true,
                'havingFilter' => true,
            ],
            'fee1'                => [
                'title'        => $this->l('Base'),
                'width'        => 30,
                'align'        => 'right',
                'price'        => true,
                'havingFilter' => true,
            ],
            'fee2'                => [
                'title'        => $this->l('Percent'),
                'width'        => 30,
                'align'        => 'right',
                'price'        => true,
                'havingFilter' => true,
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        parent::__construct();
    }

    /**
     * Display calendar form
     *
     * @param      $translations
     * @param      $token
     * @param null $action
     * @param null $table
     * @param null $identifier
     * @param null $id
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

        return $tpl->fetch();
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
        $this->context->controller->addJqueryUI('ui.datepicker');
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
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_referrer'] = [
                'href' => static::$currentIndex.'&addreferrer&token='.$this->token,
                'desc' => $this->l('Add new referrer', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Render list
     *
     * @return false|string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        // Display list Referrers:
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_select = 'SUM(sa.cache_visitors) AS cache_visitors, SUM(sa.cache_visits) AS cache_visits, SUM(sa.cache_pages) AS cache_pages,
							SUM(sa.cache_registrations) AS cache_registrations, SUM(sa.cache_orders) AS cache_orders, SUM(sa.cache_sales) AS cache_sales,
							IF(sa.cache_orders > 0, ROUND(sa.cache_sales/sa.cache_orders, 2), 0) as cart, (sa.cache_visits*click_fee) as fee0,
							(sa.cache_orders*base_fee) as fee1, (sa.cache_sales*percent_fee/100) as fee2';
        $this->_join = '
			LEFT JOIN `'._DB_PREFIX_.'referrer_shop` sa
				ON (sa.'.$this->identifier.' = a.'.$this->identifier.' AND sa.id_shop IN ('.implode(', ', Shop::getContextListShopID()).'))';

        $this->_group = 'GROUP BY sa.id_referrer';

        $this->tpl_list_vars = [
            'enable_calendar' => $this->enableCalendar(),
            'calendar_form'   => $this->displayCalendar(),
            'settings_form'   => $this->displaySettings(),
        ];

        return parent::renderList();
    }

    /**
     * Enable calendar
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function enableCalendar()
    {
        return (!Tools::isSubmit('add'.$this->table) && !Tools::isSubmit('submitAdd'.$this->table) && !Tools::isSubmit('update'.$this->table));
    }

    /**
     * @param null $action
     * @param null $table
     * @param null $identifier
     * @param null $id
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function displayCalendar($action = null, $table = null, $identifier = null, $id = null)
    {
        return static::displayCalendarForm(
            [
                'Calendar' => $this->l('Calendar'),
                'Day'      => $this->l('Today'),
                'Month'    => $this->l('Month'),
                'Year'     => $this->l('Year'),
            ],
            $this->token,
            $action,
            $table,
            $identifier,
            $id
        );
    }

    /**
     * Display settings
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function displaySettings()
    {
        if (!Tools::isSubmit('viewreferrer')) {
            $tpl = $this->createTemplate('form_settings.tpl');

            $statsdata = Module::getInstanceByName('statsdata');

            $statsdataName = false;
            if (Validate::isLoadedObject($statsdata)) {
                $statsdataName = $statsdata->displayName;
            }
            $tpl->assign(
                [
                    'statsdata_name' => $statsdataName,
                    'current'        => static::$currentIndex,
                    'token'          => $this->token,
                    'tracking_dt'    => (int) Tools::getValue('tracking_dt', Configuration::get('TRACKING_DIRECT_TRAFFIC')),
                ]
            );

            return $tpl->fetch();
        }
    }

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $uri = Tools::getHttpHost(true, true).__PS_BASE_URI__;

        $this->fields_form[0] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Affiliate'),
                    'icon'  => 'icon-group',
                ],
                'input'  => [
                    [
                        'type'         => 'text',
                        'label'        => $this->l('Name'),
                        'name'         => 'name',
                        'required'     => true,
                        'autocomplete' => false,
                    ],
                    [
                        'type'         => 'password',
                        'label'        => $this->l('Password'),
                        'name'         => 'passwd',
                        'desc'         => $this->l('Leave blank if no change.'),
                        'autocomplete' => false,
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        if (Module::isInstalled('trackingfront')) {
            $this->fields_form[0]['form']['desc'] = [
                $this->l('Affiliates can access their data with this name and password.'),
                $this->l('Front access:').' <a class="btn btn-link" href="'.$uri.'modules/trackingfront/stats.php" onclick="return !window.open(this.href);"><i class="icon-external-link-sign"></i> '.$uri.'modules/trackingfront/stats.php</a>',
            ];
        } else {
            $this->fields_form[0]['form']['desc'] = [
                sprintf($this->l('Please install the "%s" module in order to give your affiliates access their own statistics.'), Module::getModuleName('trackingfront')),
            ];
        }

        $this->fields_form[1] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Commission plan'),
                    'icon'  => 'icon-dollar',
                ],
                'input'  => [
                    [
                        'type'  => 'price',
                        'label' => $this->l('Click fee'),
                        'name'  => 'click_fee',
                        'desc'  => $this->l('Fee given for each visit.'),
                    ],
                    [
                        'type'  => 'price',
                        'label' => $this->l('Base fee'),
                        'name'  => 'base_fee',
                        'desc'  => $this->l('Fee given for each order placed.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Percent fee'),
                        'name'  => 'percent_fee',
                        'desc'  => $this->l('Percent of the sales.'),
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form[1]['form']['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        $this->fields_form[2] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Technical information -- Simple mode'),
                    'icon'  => 'icon-cogs',
                ],
                'help'   => true,
                'input'  => [
                    [
                        'type'   => 'textarea',
                        'label'  => $this->l('Include'),
                        'name'   => 'http_referer_like',
                        'cols'   => 40,
                        'rows'   => 1,
                        'legend' => $this->l('HTTP referrer'),
                    ],
                    [
                        'type'  => 'textarea',
                        'label' => $this->l('Exclude'),
                        'name'  => 'http_referer_like_not',
                        'cols'  => 40,
                        'rows'  => 1,
                    ],
                    [
                        'type'   => 'textarea',
                        'label'  => $this->l('Include'),
                        'name'   => 'request_uri_like',
                        'cols'   => 40,
                        'rows'   => 1,
                        'legend' => $this->l('Request URI'),
                    ],
                    [
                        'type'  => 'textarea',
                        'label' => $this->l('Exclude'),
                        'name'  => 'request_uri_like_not',
                        'cols'  => 40,
                        'rows'  => 1,
                    ],
                ],
                'desc'   => $this->l('If you know how to use MySQL regular expressions, you can use the').'
					<a style="cursor: pointer; font-weight: bold;" onclick="$(\'#tracking_expert\').slideToggle();">'.$this->l('expert mode').'.</a>',
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $this->fields_form[3] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Technical information -- Expert mode'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'   => 'textarea',
                        'label'  => $this->l('Include'),
                        'name'   => 'http_referer_regexp',
                        'cols'   => 40,
                        'rows'   => 1,
                        'legend' => $this->l('HTTP referrer'),
                    ],
                    [
                        'type'  => 'textarea',
                        'label' => $this->l('Exclude'),
                        'name'  => 'http_referer_regexp_not',
                        'cols'  => 40,
                        'rows'  => 1,
                    ],
                    [
                        'type'   => 'textarea',
                        'label'  => $this->l('Include'),
                        'name'   => 'request_uri_regexp',
                        'cols'   => 40,
                        'rows'   => 1,
                        'legend' => $this->l('Request URI'),
                    ],
                    [
                        'type'  => 'textarea',
                        'label' => $this->l('Exclude'),
                        'name'  => 'request_uri_regexp_not',
                        'cols'  => 40,
                        'rows'  => 1,
                    ],
                ],
            ],
        ];

        $this->multiple_fieldsets = true;

        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $this->fields_value = [
            'click_fee'             =>
                (float) $this->getFieldValue($obj, 'click_fee'),
            'base_fee'              =>
                (float) $this->getFieldValue($obj, 'base_fee'),
            'percent_fee'           =>
                (float) $this->getFieldValue($obj, 'percent_fee'),
            'http_referer_like'     => str_replace('\\', '\\\\', htmlentities($this->getFieldValue($obj, 'http_referer_like'), ENT_COMPAT, 'UTF-8')),
            'http_referer_like_not' => str_replace('\\', '\\\\', htmlentities($this->getFieldValue($obj, 'http_referer_like_not'), ENT_COMPAT, 'UTF-8')),
            'request_uri_like'      => str_replace('\\', '\\\\', htmlentities($this->getFieldValue($obj, 'request_uri_like'), ENT_COMPAT, 'UTF-8')),
            'request_uri_like_not'  => str_replace('\\', '\\\\', htmlentities($this->getFieldValue($obj, 'request_uri_like_not'), ENT_COMPAT, 'UTF-8')),
        ];

        $this->tpl_form_vars = ['uri' => $uri];

        return parent::renderForm();
    }

    /**
     * Post processing
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if ($this->enableCalendar()) {
            // Warning, instantiating a controller here changes the controller in the Context...
            $calendarTab = new AdminStatsController();
            $calendarTab->postProcess();
            // ...so we set it back to the correct one here
            $this->context->controller = $this;
        }

        if (Tools::isSubmit('submitSettings')) {
            if ($this->tabAccess['edit'] === '1') {
                if (Configuration::updateValue('TRACKING_DIRECT_TRAFFIC', (int) Tools::getValue('tracking_dt'))) {
                    Tools::redirectAdmin(static::$currentIndex.'&conf=4&token='.Tools::getValue('token'));
                }
            }
        }

        if (ModuleGraph::getDateBetween() != Configuration::get('PS_REFERRERS_CACHE_LIKE') || Tools::isSubmit('submitRefreshCache')) {
            Referrer::refreshCache();
        }
        if (Tools::isSubmit('submitRefreshIndex')) {
            Referrer::refreshIndex();
        }

        return parent::postProcess();
    }

    /**
     * Render view
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderView()
    {
        $referrer = new Referrer((int) Tools::getValue('id_referrer'));

        $displayTab = [
            'uniqs'         => $this->l('Unique visitors'),
            'visitors'      => $this->l('Visitors'),
            'visits'        => $this->l('Visits'),
            'pages'         => $this->l('Pages viewed'),
            'registrations' => $this->l('Registrations'),
            'orders'        => $this->l('Orders'),
            'sales'         => $this->l('Sales'),
            'reg_rate'      => $this->l('Registration rate'),
            'order_rate'    => $this->l('Order rate'),
            'click_fee'     => $this->l('Click fee'),
            'base_fee'      => $this->l('Base fee'),
            'percent_fee'   => $this->l('Percent fee'),
        ];

        $this->tpl_view_vars = [
            'enable_calendar' => $this->enableCalendar(),
            'calendar_form'   => $this->displayCalendar($this->action, $this->table, $this->identifier, (int) Tools::getValue($this->identifier)),
            'referrer'        => $referrer,
            'display_tab'     => $displayTab,
            'id_employee'     => (int) $this->context->employee->id,
            'id_lang'         => (int) $this->context->language->id,
        ];

        return parent::renderView();
    }
}
