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

/**
 * Class AdminCurrenciesControllerCore
 *
 * @property Currency|null $object
 */
class AdminCurrenciesControllerCore extends AdminController
{
    /**
     * AdminCurrenciesControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'currency';
        $this->className = 'Currency';
        $this->lang = false;
        $this->list_no_link = true;

        $this->fields_list = [
            'id_currency'     => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name'            => ['title' => $this->l('Currency')],
            'iso_code'        => ['title' => $this->l('ISO code'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'iso_code_num'    => ['title' => $this->l('ISO code number'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'sign'            => ['title' => $this->l('Symbol'), 'width' => 20, 'align' => 'center', 'orderby' => false, 'search' => false, 'class' => 'fixed-width-xs'],
            'conversion_rate' => ['title' => $this->l('Exchange rate'), 'type' => 'float', 'align' => 'center', 'width' => 130, 'search' => false, 'filter_key' => 'currency_shop!conversion_rate'],
            'active'          => ['title' => $this->l('Enabled'), 'width' => 25, 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'orderby' => false, 'class' => 'fixed-width-sm'],
            'module_name'     => ['title' => $this->l('Exchange rate service'), 'type' => 'fx_service', 'align' => 'center', 'class' => 'fixed-width-md', 'orderby' => false, 'search' => false],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fields_options = [
            'change' => [
                'title'       => $this->l('Currency rates'),
                'image'       => '../img/admin/exchangesrate.gif',
                'description' => $this->l('Use currency rate modules to update your currency\'s exchange rates. However, please use caution: rates are provided as-is.'),
                'submit'      => [
                    'title' => $this->l('Update currency rates'),
                    'name'  => 'SubmitExchangesRates',
                ],
            ],
            'cron'   => [
                'title' => $this->l('Automatically update currency rates'),
                'image' => '../img/admin/tab-tools.gif',
                'info'  => '<div class="alert alert-block"><p>'.$this->l('Use the exchange rate modules to update the rates.').'<br/>'.$this->l('You can place the following URL in your crontab file, or you can click it yourself regularly:').'</p>
					<p><strong><a href="'.Tools::getShopDomain(true, true).__PS_BASE_URI__.basename(_PS_ADMIN_DIR_).'/cron_currency_rates.php?secure_key='.md5(_COOKIE_KEY_.Configuration::get('PS_SHOP_NAME')).'" onclick="return !window.open($(this).attr(\'href\'));">'.Tools::getShopDomain(true, true).__PS_BASE_URI__.basename(_PS_ADMIN_DIR_).'/cron_currency_rates.php?secure_key='.md5(_COOKIE_KEY_.Configuration::get('PS_SHOP_NAME')).'</a></strong></p></div>',
            ],
        ];

        parent::__construct();

        try {
            CurrencyRateModule::scanMissingCurrencyRateModules();
        } catch (Exception $ignored) {
        }

        $this->_select .= 'currency_shop.conversion_rate conversion_rate, m.`name` AS `module_name`';
        $this->_join .= Shop::addSqlAssociation('currency', 'a');
        $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'currency_module` cm ON a.`id_currency` = cm.`id_currency`';
        $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'module` m ON m.`id_module` = cm.`id_module`';
        $this->_group .= 'GROUP BY a.id_currency';
    }

    /**
     * @return false|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_where = 'AND a.`deleted` = 0';

        return parent::renderList();
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Currencies'),
                'icon'  => 'icon-money',
            ],
            'input'  => [
                [
                    'type'      => 'text',
                    'label'     => $this->l('Currency name'),
                    'name'      => 'name',
                    'size'      => 30,
                    'maxlength' => 32,
                    'required'  => true,
                    'hint'      => $this->l('Only letters and the minus character are allowed.'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('ISO code'),
                    'name'      => 'iso_code',
                    'maxlength' => 32,
                    'required'  => true,
                    'hint'      => $this->l('ISO code (e.g. USD for Dollars, EUR for Euros, etc.).'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Numeric ISO code'),
                    'name'      => 'iso_code_num',
                    'maxlength' => 32,
                    'required'  => true,
                    'hint'      => $this->l('Numeric ISO code (e.g. 840 for Dollars, 978 for Euros, etc.).'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Symbol'),
                    'name'      => 'sign',
                    'maxlength' => 8,
                    'required'  => true,
                    'hint'      => $this->l('Will appear in front office (e.g. $, &euro;, etc.)'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Exchange rate'),
                    'name'      => 'conversion_rate',
                    'maxlength' => 11,
                    'required'  => true,
                    'desc'      => $this->l('Exchange rates are calculated from one unit of your shop\'s default currency. For example, if the default currency is euros and your chosen currency is dollars, type "1.20" (€1 = $1.20).'),
                ],
                [
                    'type'      => 'select',
                    'label'     => $this->l('Currency format'),
                    'name'      => 'format',
                    'maxlength' => 11,
                    'required'  => true,
                    'options'   => [
                        'query' => [
                            ['key' => 1, 'name' => 'X0,000.00 ('.$this->l('Such as with Dollars').')'],
                            ['key' => 2, 'name' => '0 000,00X ('.$this->l('Such as with Euros').')'],
                            ['key' => 3, 'name' => 'X0.000,00'],
                            ['key' => 4, 'name' => '0,000.00X'],
                            ['key' => 5, 'name' => '0\'000.00X'],
                            ['key' => 6, 'name' => '0.000,00X'],
                        ],
                        'name'  => 'name',
                        'id'    => 'key',
                    ],
                    'desc'      => $this->l('Applies to all prices (e.g. $1,240.15).'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Decimals'),
                    'name'     => 'decimals',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'decimals_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'decimals_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'desc'     => $this->l('Display decimals in prices.'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Decimal places'),
                    'name'      => 'decimal_places',
                    'maxlength' => 8,
                    'disabled'  => !$this->object->decimals,
                    'desc'      => $this->l('Number of decimal places to be displayed.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Spacing'),
                    'name'     => 'blank',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'blank_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'blank_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'desc'     => $this->l('Include a space between symbol and price (e.g. $1,240.15 -> $ 1,240.15).'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Enable'),
                    'name'     => 'active',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        return parent::renderForm();
    }

    /**
     * @return bool|false|ObjectModel
     *
     * @throws PrestaShopException
     */
    public function processDelete()
    {
        $object = $this->loadObject();
        if (!$this->checkDeletion($object)) {
            return false;
        }

        return parent::processDelete();
    }

    /**
     * @param ObjectModel $object
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function checkDeletion($object)
    {
        if (Validate::isLoadedObject($object)) {
            if ($object->id == Configuration::get('PS_CURRENCY_DEFAULT')) {
                $this->errors[] = $this->l('You cannot delete the default currency');
            } else {
                return true;
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while deleting the object.').'
				<b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }

        return false;
    }

    /**
     * @return bool|false|ObjectModel
     *
     * @throws PrestaShopException
     */
    public function processStatus()
    {
        /** @var Currency $object */
        $object = $this->loadObject();
        if (!$this->checkDisableStatus($object)) {
            return false;
        }

        return parent::processStatus();
    }

    /**
     * @param Currency $object
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function checkDisableStatus($object)
    {
        if (Validate::isLoadedObject($object)) {
            if ($object->active && $object->id == Configuration::get('PS_CURRENCY_DEFAULT')) {
                $this->errors[] = $this->l('You cannot disable the default currency');
            } else {
                return true;
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').'
				<b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }

        return false;
    }

    /**
     * Update currency exchange rates
     *
     * @throws PrestaShopException
     */
    public function processExchangeRates()
    {
        if (!$this->errors = Currency::refreshCurrencies()) {
            Tools::redirectAdmin(static::$currentIndex.'&conf=6&token='.$this->token);
        }
    }

    /**
     * @see AdminController::initProcess()
     *
     * @throws PrestaShopException
     */
    public function initProcess()
    {
        if (Tools::isSubmit('SubmitExchangesRates')) {
            if ($this->hasEditPermission()) {
                $this->action = 'exchangeRates';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }
        if (Tools::isSubmit('submitAddcurrency') &&
            !Tools::getIntValue('id_currency') &&
            Currency::exists(Tools::getValue('iso_code'), Tools::getValue('iso_code_num'))
        ) {
            $this->errors[] = Tools::displayError('This currency already exists.');
        }
        if (Tools::isSubmit('submitAddcurrency') && Tools::getNumberValue('conversion_rate') <= 0) {
            $this->errors[] = Tools::displayError('The currency conversion rate can not be equal to 0.');
        }
        parent::initProcess();
    }

    /**
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_currency'] = [
                'href' => static::$currentIndex.'&addcurrency&token='.$this->token,
                'desc' => $this->l('Add new currency', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function processBulkDelete()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            foreach ($this->boxes as $idCurrency) {
                $object = new Currency((int) $idCurrency);
                if (!$this->checkDeletion($object)) {
                    return false;
                }
            }
        }

        return parent::processBulkDelete();
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function processBulkDisableSelection()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            foreach ($this->boxes as $idCurrency) {
                $object = new Currency((int) $idCurrency);
                if (!$this->checkDisableStatus($object)) {
                    return false;
                }
            }
        }

        return parent::processBulkDisableSelection();
    }

    /**
     * Process update fx service ajax call
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateFxService()
    {
        $idModule = Tools::getIntValue('idModule');
        $idCurrency = Tools::getIntValue('idCurrency');

        if ($idModule && $idCurrency) {
            CurrencyRateModule::setModule($idCurrency, $idModule);

            $this->ajaxDie(json_encode([
                    'success' => true,
            ]));
        } else {
            $this->ajaxDie(json_encode([
                    'success' => false,
            ]));
        }
    }
}
