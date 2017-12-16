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
 * Class AdminCountriesControllerCore
 *
 * @since 1.0.0
 */
class AdminCountriesControllerCore extends AdminController
{
    /**
     * AdminCountriesControllerCore constructor.
     *
     * @since 1.0.0
     */
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
            'delete'     => ['text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?')],
            'affectzone' => ['text' => $this->l('Assign to a new zone')],
        ];

        $this->fieldImageSettings = [
            'name' => 'logo',
            'dir'  => 'st',
        ];

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Country options'),
                'fields' => [
                    'PS_RESTRICT_DELIVERED_COUNTRIES' => [
                        'title'   => $this->l('Restrict country selections in front office to those covered by active carriers'),
                        'cast'    => 'intval',
                        'type'    => 'bool',
                        'default' => '0',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        $zonesArray = [];
        $this->zones = Zone::getZones();
        foreach ($this->zones as $zone) {
            $zonesArray[$zone['id_zone']] = $zone['name'];
        }

        $this->fields_list = [
            'id_country'  => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'        => [
                'title'      => $this->l('Country'),
                'filter_key' => 'b!name',
            ],
            'iso_code'    => [
                'title' => $this->l('ISO code'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'call_prefix' => [
                'title'    => $this->l('Call prefix'),
                'align'    => 'center',
                'callback' => 'displayCallPrefix',
                'class'    => 'fixed-width-sm',
            ],
            'zone'        => [
                'title'       => $this->l('Zone'),
                'type'        => 'select',
                'list'        => $zonesArray,
                'filter_key'  => 'z!id_zone',
                'filter_type' => 'int',
                'order_key'   => 'z!name',
            ],
            'active'      => [
                'title'      => $this->l('Enabled'),
                'align'      => 'center',
                'active'     => 'status',
                'type'       => 'bool',
                'orderby'    => false,
                'filter_key' => 'a!active',
                'class'      => 'fixed-width-sm',
            ],
        ];

        parent::__construct();
    }

    /**
     * Display call prefix
     *
     * @param string $prefix
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function displayCallPrefix($prefix)
    {
        return ((int) $prefix ? '+'.$prefix : '-');
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_country'] = [
                'href' => static::$currentIndex.'&addcountry&token='.$this->token,
                'desc' => $this->l('Add new country', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * AdminController::setMedia() override
     *
     * @see AdminController::setMedia()
     */
    public function setMedia()
    {
        parent::setMedia();

        $this->addJqueryPlugin('fieldselection');
    }

    /**
     * Render list
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        $this->_select = 'z.`name` AS zone';
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'zone` z ON (z.`id_zone` = a.`id_zone`)';
        $this->_use_found_rows = false;

        $this->tpl_list_vars['zones'] = Zone::getZones();

        return parent::renderList();
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
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $addressLayout = AddressFormat::getAddressCountryFormat($obj->id);
        if ($value = Tools::getValue('address_layout')) {
            $addressLayout = $value;
        }

        $defaultLayout = '';

        $defaultLayoutTab = [
            ['firstname', 'lastname'],
            ['company'],
            ['vat_number'],
            ['address1'],
            ['address2'],
            ['postcode', 'city'],
            ['Country:name'],
            ['phone'],
            ['phone_mobile'],
        ];

        foreach ($defaultLayoutTab as $line) {
            $defaultLayout .= implode(' ', $line)."\r\n";
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Countries'),
                'icon'  => 'icon-globe',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Country'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                    'hint'     => $this->l('Country name').' - '.$this->l('Invalid characters:').' &lt;&gt;;=#{} ',
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('ISO code'),
                    'name'      => 'iso_code',
                    'maxlength' => 3,
                    'class'     => 'uppercase',
                    'required'  => true,
                    'hint'      => $this->l('Two -- or three -- letter ISO code (e.g. "us for United States).'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Call prefix'),
                    'name'      => 'call_prefix',
                    'maxlength' => 3,
                    'class'     => 'uppercase',
                    'required'  => true,
                    'hint'      => $this->l('International call prefix, (e.g. 1 for United States).'),
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Default currency'),
                    'name'    => 'id_currency',
                    'options' => [
                        'query'   => Currency::getCurrencies(false, true, true),
                        'id'      => 'id_currency',
                        'name'    => 'name',
                        'default' => [
                            'label' => $this->l('Default store currency'),
                            'value' => 0,
                        ],
                    ],
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Zone'),
                    'name'    => 'id_zone',
                    'options' => [
                        'query' => Zone::getZones(),
                        'id'    => 'id_zone',
                        'name'  => 'name',
                    ],
                    'hint'    => $this->l('Geographical region.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Does it need Zip/postal code?'),
                    'name'     => 'need_zip_code',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'need_zip_code_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'need_zip_code_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Zip/postal code format'),
                    'name'     => 'zip_code_format',
                    'required' => true,
                    'desc'     => $this->l('Indicate the format of the postal code: use L for a letter, N for a number, and C for the country\'s ISO 3166-1 alpha-2 code. For example, NNNNN for the United States, France, Poland and many other; LNNNNLLL for Argentina, etc. If you do not want thirty bees to verify the postal code for this country, leave it blank.'),
                ],
                [
                    'type'                    => 'address_layout',
                    'label'                   => $this->l('Address format'),
                    'name'                    => 'address_layout',
                    'address_layout'          => $addressLayout,
                    'encoding_address_layout' => urlencode($addressLayout),
                    'encoding_default_layout' => urlencode($defaultLayout),
                    'display_valid_fields'    => $this->displayValidFields(),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Active'),
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
                    'hint'     => $this->l('Display this country to your customers (the selected country will always be displayed in the Back Office).'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Contains states'),
                    'name'     => 'contains_states',
                    'required' => false,
                    'values'   => [
                        [
                            'id'    => 'contains_states_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" />'.$this->l('Yes'),
                        ],
                        [
                            'id'    => 'contains_states_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" />'.$this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Do you need a tax identification number?'),
                    'name'     => 'need_identification_number',
                    'required' => false,
                    'values'   => [
                        [
                            'id'    => 'need_identification_number_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" />'.$this->l('Yes'),
                        ],
                        [
                            'id'    => 'need_identification_number_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" />'.$this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Display tax label (e.g. "Tax incl.")'),
                    'name'     => 'display_tax_label',
                    'required' => false,
                    'values'   => [
                        [
                            'id'    => 'display_tax_label_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" />'.$this->l('Yes'),
                        ],
                        [
                            'id'    => 'display_tax_label_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" />'.$this->l('No'),
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
     * Display valid fields
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function displayValidFields()
    {
        /* The following translations are needed later - don't remove the comments!
        $this->l('Customer');
        $this->l('Warehouse');
        $this->l('Country');
        $this->l('State');
        $this->l('Address');
        */

        $htmlTabnav = '<ul class="nav nav-tabs" id="custom-address-fields">';
        $htmlTabcontent = '<div class="tab-content" >';

        $objectList = AddressFormat::getLiableClass('Address');
        $objectList['Address'] = null;

        // Get the available properties for each class
        $i = 0;
        $classTabActive = 'active';
        foreach ($objectList as $className => &$object) {
            if ($i != 0) {
                $classTabActive = '';
            }
            $fields = [];
            $htmlTabnav .= '<li'.($classTabActive ? ' class="'.$classTabActive.'"' : '').'>
				<a href="#availableListFieldsFor_'.$className.'"><i class="icon-caret-down"></i>&nbsp;'.Translate::getAdminTranslation($className, 'AdminCountries').'</a></li>';

            foreach (AddressFormat::getValidateFields($className) as $name) {
                $fields[] = '<a href="javascript:void(0);" class="addPattern btn btn-default btn-xs" id="'.($className == 'Address' ? $name : $className.':'.$name).'">
					<i class="icon-plus-sign"></i>&nbsp;'.ObjectModel::displayFieldName($name, $className).'</a>';
            }
            $htmlTabcontent .= '
				<div class="tab-pane availableFieldsList panel '.$classTabActive.'" id="availableListFieldsFor_'.$className.'">
				'.implode(' ', $fields).'</div>';
            unset($object);
            $i++;
        }
        $htmlTabnav .= '</ul>';
        $htmlTabcontent .= '</div>';

        return $html = $htmlTabnav.$htmlTabcontent;
    }

    /**
     * Process update
     *
     * @return false|ObjectModel
     *
     * @since 1.0.0
     */
    public function processUpdate()
    {
        /** @var Country $country */
        $country = $this->loadObject();
        if (Validate::isLoadedObject($country) && Tools::getValue('id_zone')) {
            $oldIdZone = $country->id_zone;
            $sql = new DbQuery();
            $sql->select('id_state');
            $sql->from('state');
            $sql->where('`id_country` = '.(int) $country->id.' AND `id_zone` = '.(int) $oldIdZone);
            $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

            if ($results && count($results)) {
                $ids = [];
                foreach ($results as $res) {
                    $ids[] = (int) $res['id_state'];
                }

                if (count($ids)) {
                    Db::getInstance()->update(
                        'state',
                        [
                            'id_zone' => (int) Tools::getValue('id_zone'),
                        ],
                        '`id_state` IN ('.implode(',', $ids).')'
                    );
                }
            }
        }

        return parent::processUpdate();
    }

    /**
     * Post process
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (!Tools::getValue('id_'.$this->table)) {
            if (Validate::isLanguageIsoCode(Tools::getValue('iso_code')) && (int) Country::getByIso(Tools::getValue('iso_code'))) {
                $this->errors[] = Tools::displayError('This ISO code already exists.You cannot create two countries with the same ISO code.');
            }
        } elseif (Validate::isLanguageIsoCode(Tools::getValue('iso_code'))) {
            $idCountry = (int) Country::getByIso(Tools::getValue('iso_code'));
            if (!is_null($idCountry) && $idCountry != Tools::getValue('id_'.$this->table)) {
                $this->errors[] = Tools::displayError('This ISO code already exists.You cannot create two countries with the same ISO code.');
            }
        }

        return parent::postProcess();
    }

    /**
     * Process save
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function processSave()
    {
        if (!$this->id_object) {
            $tmpAddrFormat = new AddressFormat();
        } else {
            $tmpAddrFormat = new AddressFormat($this->id_object);
        }

        $tmpAddrFormat->format = Tools::getValue('address_layout');

        if (!$tmpAddrFormat->checkFormatFields()) {
            $errorList = $tmpAddrFormat->getErrorList();
            foreach ($errorList as $numError => $error) {
                $this->errors[] = $error;
            }
        }
        if (strlen($tmpAddrFormat->format) <= 0) {
            $this->errors[] = $this->l('Address format invalid');
        }

        $country = parent::processSave();

        if (!count($this->errors) && $country instanceof Country) {
            if (is_null($tmpAddrFormat->id_country)) {
                $tmpAddrFormat->id_country = $country->id;
            }

            if (!$tmpAddrFormat->save()) {
                $this->errors[] = Tools::displayError('Invalid address layout '.Db::getInstance()->getMsgError());
            }
        }

        return $country;
    }

    /**
     * Process status
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function processStatus()
    {
        parent::processStatus();

        /** @var Country $object */
        if (Validate::isLoadedObject($object = $this->loadObject()) && $object->active == 1) {
            return Country::addModuleRestrictions([], [['id_country' => $object->id]], []);
        }

        return false;
    }

    /**
     * Process bulk status selection
     *
     * @param bool $way
     *
     * @return bool|void
     *
     * @since 1.0.0
     */
    public function processBulkStatusSelection($way)
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $countriesIds = [];
            foreach ($this->boxes as $id) {
                $countriesIds[] = ['id_country' => $id];
            }

            if (count($countriesIds)) {
                Country::addModuleRestrictions([], $countriesIds, []);
            }
        }

        parent::processBulkStatusSelection($way);
    }
}
