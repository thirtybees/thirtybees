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
 * Class AdminStatesControllerCore
 *
 * @since 1.0.0
 */
class AdminStatesControllerCore extends AdminController
{
    /**
     * AdminStatesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'state';
        $this->className = 'State';
        $this->lang = false;
        $this->requiredDatabase = true;

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->context = Context::getContext();

        if (!Tools::getValue('realedit')) {
            $this->deleted = false;
        }

        $this->bulk_actions = [
            'delete'     => ['text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?')],
            'affectzone' => ['text' => $this->l('Assign a new zone')],
        ];

        $this->_select = 'z.`name` AS zone, cl.`name` AS country';
        $this->_join = '
		LEFT JOIN `'._DB_PREFIX_.'zone` z ON (z.`id_zone` = a.`id_zone`)
		LEFT JOIN `'._DB_PREFIX_.'country_lang` cl ON (cl.`id_country` = a.`id_country` AND cl.id_lang = '.(int) $this->context->language->id.')';
        $this->_use_found_rows = false;

        $countriesArray = $zonesArray = [];
        $this->zones = Zone::getZones();
        $this->countries = Country::getCountries($this->context->language->id, false, true, false);
        foreach ($this->zones as $zone) {
            $zonesArray[$zone['id_zone']] = $zone['name'];
        }
        foreach ($this->countries as $country) {
            $countriesArray[$country['id_country']] = $country['name'];
        }

        $this->fields_list = [
            'id_state' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'     => [
                'title'      => $this->l('Name'),
                'filter_key' => 'a!name',
            ],
            'iso_code' => [
                'title' => $this->l('ISO code'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'zone'     => [
                'title'       => $this->l('Zone'),
                'type'        => 'select',
                'list'        => $zonesArray,
                'filter_key'  => 'z!id_zone',
                'filter_type' => 'int',
                'order_key'   => 'zone',
            ],
            'country'  => [
                'title'       => $this->l('Country'),
                'type'        => 'select',
                'list'        => $countriesArray,
                'filter_key'  => 'cl!id_country',
                'filter_type' => 'int',
                'order_key'   => 'country',
            ],
            'active'   => [
                'title'      => $this->l('Enabled'),
                'active'     => 'status',
                'filter_key' => 'a!active',
                'align'      => 'center',
                'type'       => 'bool',
                'orderby'    => false,
                'class'      => 'fixed-width-sm',
            ],
        ];

        parent::__construct();
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
            $this->page_header_toolbar_btn['new_state'] = [
                'href' => static::$currentIndex.'&addstate&token='.$this->token,
                'desc' => $this->l('Add new state', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
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
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('States'),
                'icon'  => 'icon-globe',
            ],
            'input'  => [
                [
                    'type'      => 'text',
                    'label'     => $this->l('Name'),
                    'name'      => 'name',
                    'maxlength' => 32,
                    'required'  => true,
                    'hint'      => $this->l('Provide the State name to be display in addresses and on invoices.'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('ISO code'),
                    'name'      => 'iso_code',
                    'maxlength' => 7,
                    'required'  => true,
                    'class'     => 'uppercase',
                    'hint'      => $this->l('1 to 4 letter ISO code.').' '.$this->l('You can prefix it with the country ISO code if needed.'),
                ],
                [
                    'type'          => 'select',
                    'label'         => $this->l('Country'),
                    'name'          => 'id_country',
                    'required'      => true,
                    'default_value' => (int) $this->context->country->id,
                    'options'       => [
                        'query' => Country::getCountries($this->context->language->id, false, true),
                        'id'    => 'id_country',
                        'name'  => 'name',
                    ],
                    'hint'          => $this->l('Country where the state is located.').' '.$this->l('Only the countries with the option "contains states" enabled are displayed.'),
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Zone'),
                    'name'     => 'id_zone',
                    'required' => true,
                    'options'  => [
                        'query' => Zone::getZones(),
                        'id'    => 'id_zone',
                        'name'  => 'name',
                    ],
                    'hint'     => [
                        $this->l('Geographical region where this state is located.'),
                        $this->l('Used for shipping'),
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Status'),
                    'name'     => 'active',
                    'required' => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Enabled').'" />',
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('Disabled').'" />',
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        return parent::renderForm();
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
        if (Tools::isSubmit($this->table.'Orderby') || Tools::isSubmit($this->table.'Orderway')) {
            $this->filter = true;
        }

        // Idiot-proof controls
        if (!Tools::getValue('id_'.$this->table)) {
            if (Validate::isStateIsoCode(Tools::getValue('iso_code')) && State::getIdByIso(Tools::getValue('iso_code'), Tools::getValue('id_country'))) {
                $this->errors[] = Tools::displayError('This ISO code already exists. You cannot create two states with the same ISO code.');
            }
        } elseif (Validate::isStateIsoCode(Tools::getValue('iso_code'))) {
            $idState = State::getIdByIso(Tools::getValue('iso_code'), Tools::getValue('id_country'));
            if ($idState && $idState != Tools::getValue('id_'.$this->table)) {
                $this->errors[] = Tools::displayError('This ISO code already exists. You cannot create two states with the same ISO code.');
            }
        }

        /* Delete state */
        if (Tools::isSubmit('delete'.$this->table)) {
            if ($this->tabAccess['delete'] === '1') {
                if (Validate::isLoadedObject($object = $this->loadObject())) {
                    /** @var State $object */
                    if (!$object->isUsed()) {
                        if ($object->delete()) {
                            Tools::redirectAdmin(static::$currentIndex.'&conf=1&token='.(Tools::getValue('token') ? Tools::getValue('token') : $this->token));
                        }
                        $this->errors[] = Tools::displayError('An error occurred during deletion.');
                    } else {
                        $this->errors[] = Tools::displayError('This state was used in at least one address. It cannot be removed.');
                    }
                } else {
                    $this->errors[] = Tools::displayError('An error occurred while deleting the object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        }

        if (!count($this->errors)) {
            parent::postProcess();
        }
    }

    /**
     * Display ajax states
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function displayAjaxStates()
    {
        $states = Db::getInstance()->executeS(
            '
		SELECT s.id_state, s.name
		FROM '._DB_PREFIX_.'state s
		LEFT JOIN '._DB_PREFIX_.'country c ON (s.`id_country` = c.`id_country`)
		WHERE s.id_country = '.(int) (Tools::getValue('id_country')).' AND s.active = 1 AND c.`contains_states` = 1
		ORDER BY s.`name` ASC'
        );

        if (is_array($states) and !empty($states)) {
            $list = '';
            if ((bool) Tools::getValue('no_empty') != true) {
                $emptyValue = (Tools::isSubmit('empty_value')) ? Tools::getValue('empty_value') : '-';
                $list = '<option value="0">'.Tools::htmlentitiesUTF8($emptyValue).'</option>'."\n";
            }

            foreach ($states as $state) {
                $list .= '<option value="'.(int) ($state['id_state']).'"'.((isset($_GET['id_state']) and $_GET['id_state'] == $state['id_state']) ? ' selected="selected"' : '').'>'.$state['name'].'</option>'."\n";
            }
        } else {
            $list = 'false';
        }

        die($list);
    }
}
