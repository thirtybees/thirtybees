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
 * @property Country $object
 */
class AdminCountriesControllerCore extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'country';
        $this->className = 'Country';
        $this->lang = true;
        $this->deleted = false;
        $this->_defaultOrderBy = 'name';
        $this->_defaultOrderWay = 'ASC';

        $this->explicitSelect = true;
        $this->addRowAction('edit');

        $this->context = Context::getContext();

        $this->bulk_actions = [
            'delete' => ['text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?')],
            'affectzone' => ['text' => $this->l('Assign to a new zone')]
        ];

        $this->fieldImageSettings = [
            'name' => 'logo',
            'dir' => 'st'
        ];

        $this->fields_options = [
            'general' => [
                'title' =>    $this->l('Country options'),
                'fields' =>    [
                    'PS_RESTRICT_DELIVERED_COUNTRIES' => [
                        'title' => $this->l('Restrict country selections in front office to those covered by active carriers'),
                        'cast' => 'intval',
                        'type' => 'bool',
                        'default' => '0'
                    ]
                ],
                'submit' => ['title' => $this->l('Save')]
            ]
        ];

        $zones_array = [];
        $this->zones = Zone::getZones();
        foreach ($this->zones as $zone) {
            $zones_array[$zone['id_zone']] = $zone['name'];
        }

        $this->fields_list = [
            'id_country' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->l('Country'),
                'filter_key' => 'b!name'
            ],
            'iso_code' => [
                'title' => $this->l('ISO code'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'call_prefix' => [
                'title' => $this->l('Call prefix'),
                'align' => 'center',
                'callback' => 'displayCallPrefix',
                'class' => 'fixed-width-sm'
            ],
            'zone' => [
                'title' => $this->l('Zone'),
                'type' => 'select',
                'list' => $zones_array,
                'filter_key' => 'z!id_zone',
                'filter_type' => 'int',
                'order_key' => 'z!name'
            ],
            'active' => [
                'title' => $this->l('Enabled'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'filter_key' => 'a!active',
                'class' => 'fixed-width-sm'
            ]
        ];

        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_country'] = [
                'href' => self::$currentIndex.'&addcountry&token='.$this->token,
                'desc' => $this->l('Add new country', null, null, false),
                'icon' => 'process-icon-new'
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * AdminController::setMedia() override
     * @see AdminController::setMedia()
     */
    public function setMedia()
    {
        parent::setMedia();

        $this->addJqueryPlugin('fieldselection');
    }

    public function renderList()
    {
        $this->_select = 'z.`name` AS zone';
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'zone` z ON (z.`id_zone` = a.`id_zone`)';
        $this->_use_found_rows = false;

        $this->tpl_list_vars['zones'] = Zone::getZones();
        return parent::renderList();
    }

    public function renderForm()
    {
        if (!($obj = $this->loadObject(true))) {
            return;
        }

        $address_layout = AddressFormat::getAddressCountryFormat($obj->id);
        if ($value = Tools::getValue('address_layout')) {
            $address_layout = $value;
        }

        $default_layout = '';

        $default_layout_tab = [
            ['firstname', 'lastname'],
            ['company'],
            ['vat_number'],
            ['address1'],
            ['address2'],
            ['postcode', 'city'],
            ['Country:name'],
            ['phone'],
            ['phone_mobile']
        ];

        foreach ($default_layout_tab as $line) {
            $default_layout .= implode(' ', $line)."\r\n";
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Countries'),
                'icon' => 'icon-globe'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Country'),
                    'name' => 'name',
                    'lang' => true,
                    'required' => true,
                    'hint' => $this->l('Country name').' - '.$this->l('Invalid characters:').' &lt;&gt;;=#{} '
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('ISO code'),
                    'name' => 'iso_code',
                    'maxlength' => 3,
                    'class' => 'uppercase',
                    'required' => true,
                    'hint' => $this->l('Two -- or three -- letter ISO code (e.g. "us for United States).')
                    /* @TODO - ajouter les liens dans le hint ? */
                    /*'desc' => $this->l('Two -- or three -- letter ISO code (e.g. U.S. for United States)').'.
                            <a href="http://www.iso.org/iso/country_codes/iso_3166_code_lists/country_names_and_code_elements.htm" target="_blank">'.
                                $this->l('Official list here').'
                            </a>.'*/
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Call prefix'),
                    'name' => 'call_prefix',
                    'maxlength' => 3,
                    'class' => 'uppercase',
                    'required' => true,
                    'hint' => $this->l('International call prefix, (e.g. 1 for United States).')
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Default currency'),
                    'name' => 'id_currency',
                    'options' => [
                        'query' => Currency::getCurrencies(false, true, true),
                        'id' => 'id_currency',
                        'name' => 'name',
                        'default' => [
                            'label' => $this->l('Default store currency'),
                            'value' => 0
                        ]
                    ]
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Zone'),
                    'name' => 'id_zone',
                    'options' => [
                        'query' => Zone::getZones(),
                        'id' => 'id_zone',
                        'name' => 'name'
                    ],
                    'hint' => $this->l('Geographical region.')
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Does it need Zip/postal code?'),
                    'name' => 'need_zip_code',
                    'required' => false,
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'need_zip_code_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'need_zip_code_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ]
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Zip/postal code format'),
                    'name' => 'zip_code_format',
                    'required' => true,
                    'desc' => $this->l('Indicate the format of the postal code: use L for a letter, N for a number, and C for the country\'s ISO 3166-1 alpha-2 code. For example, NNNNN for the United States, France, Poland and many other; LNNNNLLL for Argentina, etc. If you do not want thirty bees to verify the postal code for this country, leave it blank.')
                ],
                [
                    'type' => 'address_layout',
                    'label' => $this->l('Address format'),
                    'name' => 'address_layout',
                    'address_layout' => $address_layout,
                    'encoding_address_layout' => urlencode($address_layout),
                    'encoding_default_layout' => urlencode($default_layout),
                    'display_valid_fields' => $this->displayValidFields()
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'required' => false,
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        ]
                    ],
                    'hint' => $this->l('Display this country to your customers (the selected country will always be displayed in the Back Office).')
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Contains states'),
                    'name' => 'contains_states',
                    'required' => false,
                    'values' => [
                        [
                            'id' => 'contains_states_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" />'.$this->l('Yes')
                        ],
                        [
                            'id' => 'contains_states_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" />'.$this->l('No')
                        ]
                    ]
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Do you need a tax identification number?'),
                    'name' => 'need_identification_number',
                    'required' => false,
                    'values' => [
                        [
                            'id' => 'need_identification_number_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" />'.$this->l('Yes')
                        ],
                        [
                            'id' => 'need_identification_number_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" />'.$this->l('No')
                        ]
                    ]
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Display tax label (e.g. "Tax incl.")'),
                    'name' => 'display_tax_label',
                    'required' => false,
                    'values' => [
                        [
                            'id' => 'display_tax_label_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" />'.$this->l('Yes')
                        ],
                        [
                            'id' => 'display_tax_label_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" />'.$this->l('No')
                        ]
                    ]
                ]
            ]

        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type' => 'shop',
                'label' => $this->l('Shop association'),
                'name' => 'checkBoxShopAsso',
            ];
        }

        $this->fields_form['submit'] = [
            'title' => $this->l('Save')
        ];

        return parent::renderForm();
    }

    public function processUpdate()
    {
        /** @var Country $country */
        $country = $this->loadObject();
        if (Validate::isLoadedObject($country) && Tools::getValue('id_zone')) {
            $old_id_zone = $country->id_zone;
            $results = Db::getInstance()->executeS('SELECT `id_state` FROM `'._DB_PREFIX_.'state` WHERE `id_country` = '.(int)$country->id.' AND `id_zone` = '.(int)$old_id_zone);

            if ($results && count($results)) {
                $ids = [];
                foreach ($results as $res) {
                    $ids[] = (int)$res['id_state'];
                }

                if (count($ids)) {
                    $res = Db::getInstance()->execute(
                            'UPDATE `'._DB_PREFIX_.'state`
							SET `id_zone` = '.(int)Tools::getValue('id_zone').'
							WHERE `id_state` IN ('.implode(',', $ids).')');
                }
            }
        }
        return parent::processUpdate();
    }

    public function postProcess()
    {
        if (!Tools::getValue('id_'.$this->table)) {
            if (Validate::isLanguageIsoCode(Tools::getValue('iso_code')) && (int)Country::getByIso(Tools::getValue('iso_code'))) {
                $this->errors[] = Tools::displayError('This ISO code already exists.You cannot create two countries with the same ISO code.');
            }
        } elseif (Validate::isLanguageIsoCode(Tools::getValue('iso_code'))) {
            $id_country = (int)Country::getByIso(Tools::getValue('iso_code'));
            if (!is_null($id_country) && $id_country != Tools::getValue('id_'.$this->table)) {
                $this->errors[] = Tools::displayError('This ISO code already exists.You cannot create two countries with the same ISO code.');
            }
        }

        return parent::postProcess();
    }

    public function processSave()
    {
        if (!$this->id_object) {
            $tmp_addr_format = new AddressFormat();
        } else {
            $tmp_addr_format = new AddressFormat($this->id_object);
        }

        $tmp_addr_format->format = Tools::getValue('address_layout');

        if (!$tmp_addr_format->checkFormatFields()) {
            $error_list = $tmp_addr_format->getErrorList();
            foreach ($error_list as $num_error => $error) {
                $this->errors[] = $error;
            }
        }
        if (strlen($tmp_addr_format->format) <= 0) {
            $this->errors[] = $this->l('Address format invalid');
        }

        $country = parent::processSave();

        if (!count($this->errors)) {
            if (is_null($tmp_addr_format->id_country)) {
                $tmp_addr_format->id_country = $country->id;
            }

            if (!$tmp_addr_format->save()) {
                $this->errors[] = Tools::displayError('Invalid address layout '.Db::getInstance()->getMsgError());
            }
        }

        return $country;
    }

    public function processStatus()
    {
        parent::processStatus();

        /** @var Country $object */
        if (Validate::isLoadedObject($object = $this->loadObject()) && $object->active == 1) {
            return Country::addModuleRestrictions([], [['id_country' => $object->id]], []);
        }

        return false;
    }

    public function processBulkStatusSelection($way)
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $countries_ids = [];
            foreach ($this->boxes as $id) {
                $countries_ids[] = ['id_country' => $id];
            }

            if (count($countries_ids)) {
                Country::addModuleRestrictions([], $countries_ids, []);
            }
        }
        parent::processBulkStatusSelection($way);
    }


    protected function displayValidFields()
    {
        /* The following translations are needed later - don't remove the comments!
        $this->l('Customer');
        $this->l('Warehouse');
        $this->l('Country');
        $this->l('State');
        $this->l('Address');
        */

        $html_tabnav = '<ul class="nav nav-tabs" id="custom-address-fields">';
        $html_tabcontent = '<div class="tab-content" >';

        $object_list = AddressFormat::getLiableClass('Address');
        $object_list['Address'] = null;

        // Get the available properties for each class
        $i = 0;
        $class_tab_active = 'active';
        foreach ($object_list as $class_name => &$object) {
            if ($i != 0) {
                $class_tab_active = '';
            }
            $fields = [];
            $html_tabnav .= '<li'.($class_tab_active ? ' class="'.$class_tab_active.'"' : '').'>
				<a href="#availableListFieldsFor_'.$class_name.'"><i class="icon-caret-down"></i>&nbsp;'.Translate::getAdminTranslation($class_name, 'AdminCountries').'</a></li>';

            foreach (AddressFormat::getValidateFields($class_name) as $name) {
                $fields[] = '<a href="javascript:void(0);" class="addPattern btn btn-default btn-xs" id="'.($class_name == 'Address' ? $name : $class_name.':'.$name).'">
					<i class="icon-plus-sign"></i>&nbsp;'.ObjectModel::displayFieldName($name, $class_name).'</a>';
            }
            $html_tabcontent .= '
				<div class="tab-pane availableFieldsList panel '.$class_tab_active.'" id="availableListFieldsFor_'.$class_name.'">
				'.implode(' ', $fields).'</div>';
            unset($object);
            $i ++;
        }
        $html_tabnav .= '</ul>';
        $html_tabcontent .= '</div>';
        return $html = $html_tabnav.$html_tabcontent;
    }

    public static function displayCallPrefix($prefix)
    {
        return ((int)$prefix ? '+'.$prefix : '-');
    }
}
