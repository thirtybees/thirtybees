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
 * Class AdminPaymentControllerCore
 */
class AdminPaymentControllerCore extends AdminController
{
    /** @var array $payment_modules */
    public $payment_modules = [];

    /**
     * AdminPaymentControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        $idShop = $this->context->shop->id;

        /* Get all modules then select only payment ones */
        $modules = Module::getModulesOnDisk(true);

        foreach ($modules as $module) {
            if ($module->tab == 'payments_gateways' && Module::isEnabled($module->name)) {
                $instance = Module::getInstanceByName($module->name);

                // sync settings
                $module->limited_countries = $instance->limited_countries;
                $module->currencies_mode = $instance->currencies_mode ?? 'checkbox';
                $module->currencies = $instance->currencies ?? true;

                $module->country = [];
                $sql = new DbQuery();
                $sql->select('`id_country`');
                $sql->from('module_country');
                $sql->where('`id_module` = '.(int) $module->id);
                $sql->where('`id_shop` = '.(int) $idShop);

                $conn = Db::readOnly();
                $countries = $conn->getArray($sql);
                foreach ($countries as $country) {
                    $module->country[] = $country['id_country'];
                }

                $module->currency = [];
                $sql = new DbQuery();
                $sql->select('`id_currency`');
                $sql->from('module_currency');
                $sql->where('`id_module` = '.(int) $module->id);
                $sql->where('`id_shop` = '.(int) $idShop);

                $currencies = $conn->getArray($sql);
                foreach ($currencies as $currency) {
                    $module->currency[] = $currency['id_currency'];
                }

                $module->group = [];
                $sql = new DbQuery();
                $sql->select('`id_group`');
                $sql->from('module_group');
                $sql->where('`id_module` = '.(int) $module->id);
                $sql->where('`id_shop` = '.(int) $idShop);

                $groups = $conn->getArray($sql);
                foreach ($groups as $group) {
                    $module->group[] = $group['id_group'];
                }

                $module->reference = [];
                $sql = new DbQuery();
                $sql->select('`id_reference`');
                $sql->from('module_carrier');
                $sql->where('`id_module` = '.(int) $module->id);
                $sql->where('`id_shop` = '.(int) $idShop);

                $carriers = $conn->getArray($sql);
                foreach ($carriers as $carrier) {
                    $module->reference[] = $carrier['id_reference'];
                }

                $this->payment_modules[] = $module;
            }
        }
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
        $this->page_header_toolbar_btn = [];
    }

    /**
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if ($this->action) {
            $this->saveRestrictions($this->action);
        }
    }

    /**
     * @return void
     */
    public function initProcess()
    {
        if ($this->hasEditPermission()) {
            if (Tools::isSubmit('submitModulecountry')) {
                $this->action = 'country';
            } elseif (Tools::isSubmit('submitModulecurrency')) {
                $this->action = 'currency';
            } elseif (Tools::isSubmit('submitModulegroup')) {
                $this->action = 'group';
            } elseif (Tools::isSubmit('submitModulereference')) {
                $this->action = 'carrier';
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }
    }

    /**
     * @param string $type
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function saveRestrictions($type)
    {
        // Delete type restrictions for active module.
        $modules = [];
        foreach ($this->payment_modules as $module) {
            if ($module->active) {
                $modules[] = (int) $module->id;
            }
        }

        $conn = Db::getInstance();
        $conn->execute('
			DELETE FROM `'._DB_PREFIX_.'module_'.bqSQL($type).'`
			WHERE id_shop = '.$this->context->shop->id.'
			AND `id_module` IN ('.implode(', ', $modules).')'
        );

        if ($type === 'carrier') {
            // Fill the new restriction selection for active module.
            $values = [];
            foreach ($this->payment_modules as $module) {
                if ($module->active && isset($_POST[$module->name.'_reference'])) {
                    foreach ($_POST[$module->name.'_reference'] as $selected) {
                        $values[] = '('.(int) $module->id.', '.(int) $this->context->shop->id.', '.(int) $selected.')';
                    }
                }
            }
            if (count($values)) {
                $conn->execute('
				INSERT INTO `'._DB_PREFIX_.'module_carrier`
				(`id_module`, `id_shop`, `id_reference`)
				VALUES '.implode(',', $values));
            }
        } else {
            // Fill the new restriction selection for active module.
            $values = [];
            foreach ($this->payment_modules as $module) {
                if ($module->active && isset($_POST[$module->name.'_'.$type])) {
                    foreach ($_POST[$module->name.'_'.$type] as $selected) {
                        $values[] = '('.(int) $module->id.', '.(int) $this->context->shop->id.', '.(int) $selected.')';
                    }
                }
            }
            if (count($values)) {
                $conn->execute('
				INSERT INTO `'._DB_PREFIX_.'module_'.bqSQL($type).'`
				(`id_module`, `id_shop`, `id_'.bqSQL($type).'`)
				VALUES '.implode(',', $values));
            }
        }

        Tools::redirectAdmin(static::$currentIndex.'&conf=4'.'&token='.$this->token);
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        $this->display = 'view';
        parent::initContent();
    }

    /**
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryPlugin('fancybox');
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
        $this->toolbar_title = $this->l('Payment');
        unset($this->toolbar_btn['back']);

        $shopContext = (!Shop::isFeatureActive() || Shop::getContext() == Shop::CONTEXT_SHOP);
        if (!$shopContext) {
            $this->tpl_view_vars = ['shop_context' => false];

            return parent::renderView();
        }

        $displayRestrictions = false;
        foreach ($this->payment_modules as $module) {
            if ($module->active) {
                $displayRestrictions = true;
                break;
            }
        }

        $lists = [
            [
                'items'      => Currency::getCurrencies(),
                'title'      => $this->l('Currency restrictions'),
                'desc'       => $this->l('Please mark each checkbox for the currency, or currencies, for which you want the payment module(s) to be available.'),
                'name_id'    => 'currency',
                'identifier' => 'id_currency',
                'icon'       => 'icon-money',
            ],
            [
                'items'      => Group::getGroups($this->context->language->id, true),
                'title'      => $this->l('Group restrictions'),
                'desc'       => $this->l('Please mark each checkbox for the customer group(s), for which you want the payment module(s) to be available.'),
                'name_id'    => 'group',
                'identifier' => 'id_group',
                'icon'       => 'icon-group',
            ],
            [
                'items'      => Country::getCountries($this->context->language->id),
                'title'      => $this->l('Country restrictions'),
                'desc'       => $this->l('Please mark each checkbox for the country, or countries, for which you want the payment module(s) to be available.'),
                'name_id'    => 'country',
                'identifier' => 'id_country',
                'icon'       => 'icon-globe',
            ],
            [
                'items'      => Carrier::getCarriers($this->context->language->id, false, false, false, null, Carrier::ALL_CARRIERS),
                'title'      => $this->l('Carrier restrictions'),
                'desc'       => $this->l('Please mark each checkbox for the carrier, or carrier, for which you want the payment module(s) to be available.'),
                'name_id'    => 'reference',
                'identifier' => 'id_reference',
                'icon'       => 'icon-truck',
            ],
        ];

        foreach ($lists as $keyList => $list) {
            $list['check_list'] = [];
            foreach ($list['items'] as $keyItem => $item) {
                $nameId = $list['name_id'];

                if ($nameId === 'currency'
                    && mb_strpos($list['items'][$keyItem]['name'], '('.$list['items'][$keyItem]['iso_code'].')') === false) {
                    $list['items'][$keyItem]['name'] = sprintf($this->l('%1$s (%2$s)'), $list['items'][$keyItem]['name'], $list['items'][$keyItem]['iso_code']);
                }

                foreach ($this->payment_modules as $keyModule => $module) {
                    if (isset($module->$nameId) && in_array($item['id_'.$nameId], $module->$nameId)) {
                        $list['items'][$keyItem]['check_list'][$keyModule] = 'checked';
                    } else {
                        $list['items'][$keyItem]['check_list'][$keyModule] = 'unchecked';
                    }

                    // If is a country list and the country is limited, remove it from list
                    if ($nameId === 'country'
                        && !empty($module->limited_countries)
                        && is_array($module->limited_countries)
                        && !(in_array(strtoupper($item['iso_code']), array_map('strtoupper', $module->limited_countries)))
                    ) {
                        $list['items'][$keyItem]['check_list'][$keyModule] = null;
                    }
                }
            }
            // update list
            $lists[$keyList] = $list;
        }

        $this->tpl_view_vars = [
            'display_restrictions' => $displayRestrictions,
            'lists'                => $lists,
            'ps_base_uri'          => __PS_BASE_URI__,
            'payment_modules'      => $this->payment_modules,
            'url_submit'           => static::$currentIndex.'&token='.$this->token,
            'shop_context'         => true,
        ];

        return parent::renderView();
    }
}
