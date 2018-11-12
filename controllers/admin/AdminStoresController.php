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
 * Class AdminStoresControllerCore
 *
 * @since 1.0.0
 */
class AdminStoresControllerCore extends AdminController
{
    /**
     * AdminStoresControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'store';
        $this->className = 'Store';
        $this->lang = false;
        $this->toolbar_scroll = false;

        $this->context = Context::getContext();

        if (!Tools::getValue('realedit')) {
            $this->deleted = false;
        }

        $this->fieldImageSettings = [
            'name' => 'image',
            'dir'  => 'st',
        ];

        $this->fields_list = [
            'id_store' => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name'     => ['title' => $this->l('Name'), 'filter_key' => 'a!name'],
            'address1' => ['title' => $this->l('Address'), 'filter_key' => 'a!address1'],
            'city'     => ['title' => $this->l('City')],
            'postcode' => ['title' => $this->l('Zip/postal code')],
            'state'    => ['title' => $this->l('State'), 'filter_key' => 'st!name'],
            'country'  => ['title' => $this->l('Country'), 'filter_key' => 'cl!name'],
            'phone'    => ['title' => $this->l('Phone')],
            'fax'      => ['title' => $this->l('Fax')],
            'active'   => ['title' => $this->l('Enabled'), 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'orderby' => false],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Parameters'),
                'fields' => [
                    'PS_STORES_DISPLAY_FOOTER'  => [
                        'title' => $this->l('Display in the footer'),
                        'hint'  => $this->l('Display a link to the store locator in the footer.'),
                        'cast'  => 'intval',
                        'type'  => 'bool',
                    ],
                    'PS_STORES_DISPLAY_SITEMAP' => [
                        'title' => $this->l('Display in the sitemap page'),
                        'hint'  => $this->l('Display a link to the store locator in the sitemap page.'),
                        'cast'  => 'intval',
                        'type'  => 'bool',
                    ],
                    'PS_STORES_SIMPLIFIED'      => [
                        'title' => $this->l('Show a simplified store locator'),
                        'hint'  => $this->l('No map, no search, only a store directory.'),
                        'cast'  => 'intval',
                        'type'  => 'bool',
                    ],
                    'PS_STORES_CENTER_LAT'      => [
                        'title' => $this->l('Default latitude'),
                        'hint'  => $this->l('Used for the initial position of the map.'),
                        'cast'  => 'floatval',
                        'type'  => 'text',
                        'size'  => '10',
                    ],
                    'PS_STORES_CENTER_LONG'     => [
                        'title' => $this->l('Default longitude'),
                        'hint'  => $this->l('Used for the initial position of the map.'),
                        'cast'  => 'floatval',
                        'type'  => 'text',
                        'size'  => '10',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        parent::__construct();

        $this->_buildOrderedFieldsShop($this->_getDefaultFieldsContent());
    }

    /**
     * @param $formFields
     *
     * @since 1.0.0
     */
    protected function _buildOrderedFieldsShop($formFields)
    {
        // You cannot do that, because the fields must be sorted for the country you've selected.
        // Simple example: the current country is France, where we don't display the state. You choose "US" as a country in the form. The state is not dsplayed at the right place...

        // $associatedOrderKey = array(
        // 'PS_SHOP_NAME' => 'company',
        // 'PS_SHOP_ADDR1' => 'address1',
        // 'PS_SHOP_ADDR2' => 'address2',
        // 'PS_SHOP_CITY' => 'city',
        // 'PS_SHOP_STATE_ID' => 'State:name',
        // 'PS_SHOP_CODE' => 'postcode',
        // 'PS_SHOP_COUNTRY_ID' => 'Country:name',
        // 'PS_SHOP_PHONE' => 'phone');
        // $fields = array();
        // $orderedFields = AddressFormat::getOrderedAddressFields(Configuration::get('PS_SHOP_COUNTRY_ID'), false, true);
        // foreach ($orderedFields as $lineFields)
        // if (($patterns = explode(' ', $lineFields)))
        // foreach ($patterns as $pattern)
        // if (($key = array_search($pattern, $associatedOrderKey)))
        // $fields[$key] = $formFields[$key];
        // foreach ($formFields as $key => $value)
        // if (!isset($fields[$key]))
        // $fields[$key] = $formFields[$key];

        $fields = $formFields;
        $this->fields_options['contact'] = [
            'title'  => $this->l('Contact details'),
            'icon'   => 'icon-user',
            'fields' => $fields,
            'submit' => ['title' => $this->l('Save')],
        ];
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    protected function _getDefaultFieldsContent()
    {
        $countryList = [];
        $countryList[] = ['id' => '0', 'name' => $this->l('Choose your country')];
        foreach (Country::getCountries($this->context->language->id) as $country) {
            $countryList[] = ['id' => $country['id_country'], 'name' => $country['name']];
        }
        $stateList = [];
        $stateList[] = ['id' => '0', 'name' => $this->l('Choose your state (if applicable)')];
        foreach (State::getStates($this->context->language->id) as $state) {
            $stateList[] = ['id' => $state['id_state'], 'name' => $state['name']];
        }

        $formFields = [
            'PS_SHOP_NAME'       => [
                'title'      => $this->l('Shop name'),
                'hint'       => $this->l('Displayed in emails and page titles.'),
                'validation' => 'isGenericName',
                'required'   => true,
                'type'       => 'text',
                'no_escape'  => true,
            ],
            'PS_SHOP_EMAIL'      => [
                'title'      => $this->l('Shop email'),
                'hint'       => $this->l('Displayed in emails sent to customers.'),
                'validation' => 'isEmail',
                'required'   => true,
                'type'       => 'text',
            ],
            'PS_SHOP_DETAILS'    => [
                'title'      => $this->l('Registration number'),
                'hint'       => $this->l('Shop registration information (e.g. SIRET or RCS).'),
                'validation' => 'isGenericName',
                'type'       => 'textarea',
                'cols'       => 30,
                'rows'       => 5,
            ],
            'PS_SHOP_ADDR1'      => [
                'title'      => $this->l('Shop address line 1'),
                'validation' => 'isAddress',
                'type'       => 'text',
            ],
            'PS_SHOP_ADDR2'      => [
                'title'      => $this->l('Shop address line 2'),
                'validation' => 'isAddress',
                'type'       => 'text',
            ],
            'PS_SHOP_CODE'       => [
                'title'      => $this->l('Zip/postal code'),
                'validation' => 'isGenericName',
                'type'       => 'text',
            ],
            'PS_SHOP_CITY'       => [
                'title'      => $this->l('City'),
                'validation' => 'isGenericName',
                'type'       => 'text',
            ],
            'PS_SHOP_COUNTRY_ID' => [
                'title'        => $this->l('Country'),
                'validation'   => 'isInt',
                'type'         => 'select',
                'list'         => $countryList,
                'identifier'   => 'id',
                'cast'         => 'intval',
                'defaultValue' => (int) $this->context->country->id,
            ],
            'PS_SHOP_STATE_ID'   => [
                'title'      => $this->l('State'),
                'validation' => 'isInt',
                'type'       => 'select',
                'list'       => $stateList,
                'identifier' => 'id',
                'cast'       => 'intval',
            ],
            'PS_SHOP_PHONE'      => [
                'title'      => $this->l('Phone'),
                'validation' => 'isGenericName',
                'type'       => 'text',
            ],
            'PS_SHOP_FAX'        => [
                'title'      => $this->l('Fax'),
                'validation' => 'isGenericName',
                'type'       => 'text',
            ],
        ];

        return $formFields;
    }

    /**
     * Render options
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderOptions()
    {
        // Set toolbar options
        $this->display = 'options';
        $this->show_toolbar = true;
        $this->toolbar_scroll = true;
        $this->initToolbar();

        return parent::renderOptions();
    }

    /**
     * Initialize toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initToolbar()
    {
        parent::initToolbar();

        if ($this->display == 'options') {
            unset($this->toolbar_btn['new']);
        } elseif ($this->display != 'add' && $this->display != 'edit') {
            unset($this->toolbar_btn['save']);
        }
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
            $this->page_header_toolbar_btn['new_store'] = [
                'href' => static::$currentIndex.'&addstore&token='.$this->token,
                'desc' => $this->l('Add new store', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
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
        // Set toolbar options
        $this->display = null;
        $this->initToolbar();

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_select = 'cl.`name` country, st.`name` state';
        $this->_join = '
			LEFT JOIN `'._DB_PREFIX_.'country_lang` cl
				ON (cl.`id_country` = a.`id_country`
				AND cl.`id_lang` = '.(int) $this->context->language->id.')
			LEFT JOIN `'._DB_PREFIX_.'state` st
				ON (st.`id_state` = a.`id_state`)';

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

        $image = _PS_STORE_IMG_DIR_.$obj->id.'.jpg';
        $imageUrl = ImageManager::thumbnail(
            $image,
            $this->table.'_'.(int) $obj->id.'.'.$this->imageType,
            350,
            $this->imageType,
            true,
            true
        );
        $imageSize = file_exists($image) ? filesize($image) / 1000 : false;

        $tmpAddr = new Address();
        $res = $tmpAddr->getFieldsRequiredDatabase();
        $requiredFields = [];
        foreach ($res as $row) {
            $requiredFields[(int) $row['id_required_field']] = $row['field_name'];
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Stores'),
                'icon'  => 'icon-home',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'required' => false,
                    'hint'     => [
                        $this->l('Store name (e.g. City Center Mall Store).'),
                        $this->l('Allowed characters: letters, spaces and %s'),
                    ],
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Address'),
                    'name'     => 'address1',
                    'required' => true,
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Address (2)'),
                    'name'  => 'address2',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Zip/postal Code'),
                    'name'     => 'postcode',
                    'required' => in_array('postcode', $requiredFields),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('City'),
                    'name'     => 'city',
                    'required' => true,
                ],
                [
                    'type'          => 'select',
                    'label'         => $this->l('Country'),
                    'name'          => 'id_country',
                    'required'      => true,
                    'default_value' => (int) $this->context->country->id,
                    'options'       => [
                        'query' => Country::getCountries($this->context->language->id),
                        'id'    => 'id_country',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('State'),
                    'name'     => 'id_state',
                    'required' => true,
                    'options'  => [
                        'id'    => 'id_state',
                        'name'  => 'name',
                        'query' => null,
                    ],
                ],
                [
                    'type'      => 'latitude',
                    'label'     => $this->l('Latitude / Longitude'),
                    'name'      => 'latitude',
                    'required'  => true,
                    'maxlength' => 12,
                    'hint'      => $this->l('Store coordinates (e.g. 45.265469/-47.226478).'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Phone'),
                    'name'  => 'phone',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Fax'),
                    'name'  => 'fax',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Email address'),
                    'name'  => 'email',
                ],
                [
                    'type'  => 'textarea',
                    'label' => $this->l('Note'),
                    'name'  => 'note',
                    'cols'  => 42,
                    'rows'  => 4,
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Status'),
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
                    'hint'     => $this->l('Whether or not to display this store.'),
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Picture'),
                    'name'          => 'image',
                    'display_image' => true,
                    'image'         => $imageUrl ? $imageUrl : false,
                    'size'          => $imageSize,
                    'hint'          => $this->l('Storefront picture.'),
                ],
            ],
            'hours'  => [],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        $days = [];
        $days[1] = $this->l('Monday');
        $days[2] = $this->l('Tuesday');
        $days[3] = $this->l('Wednesday');
        $days[4] = $this->l('Thursday');
        $days[5] = $this->l('Friday');
        $days[6] = $this->l('Saturday');
        $days[7] = $this->l('Sunday');

        $hours = json_decode($this->getFieldValue($obj, 'hours'), true);

        // Retrocompatibility for thirty bees <= 1.0.4.
        //
        // To get rid of this, introduce a data converter executed by the
        // upgrader over a couple of releases, making this obsolete.
        if (!$hours) {
            $hours = Tools::unSerialize($this->getFieldValue($obj, 'hours'));
        }

        $this->fields_value = [
            'latitude'  => $this->getFieldValue($obj, 'latitude') ? $this->getFieldValue($obj, 'latitude') : Configuration::get('PS_STORES_CENTER_LAT'),
            'longitude' => $this->getFieldValue($obj, 'longitude') ? $this->getFieldValue($obj, 'longitude') : Configuration::get('PS_STORES_CENTER_LONG'),
            'days'      => $days,
            'hours'     => isset($hours) ? $hours : false,
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
        if (isset($_POST['submitAdd'.$this->table])) {
            /* Cleaning fields */
            foreach ($_POST as $kp => $vp) {
                if (!in_array($kp, ['checkBoxShopGroupAsso_store', 'checkBoxShopAsso_store']) && !is_array($_POST[$kp])) {
                    $_POST[$kp] = trim($vp);
                }
            }

            /* Rewrite latitude and longitude to 8 digits */
            $_POST['latitude'] = number_format((float) $_POST['latitude'], 8);
            $_POST['longitude'] = number_format((float) $_POST['longitude'], 8);

            /* If the selected country does not contain states */
            $idState = (int) Tools::getValue('id_state');
            $idCountry = (int) Tools::getValue('id_country');
            $country = new Country((int) $idCountry);

            if ($idCountry && $country && !(int) $country->contains_states && $idState) {
                $this->errors[] = Tools::displayError('You\'ve selected a state for a country that does not contain states.');
            }

            /* If the selected country contains states, then a state have to be selected */
            if ((int) $country->contains_states && !$idState) {
                $this->errors[] = Tools::displayError('An address located in a country containing states must have a state selected.');
            }

            $latitude = (float) Tools::getValue('latitude');
            $longitude = (float) Tools::getValue('longitude');

            if (empty($latitude) || empty($longitude)) {
                $this->errors[] = Tools::displayError('Latitude and longitude are required.');
            }

            $postcode = Tools::getValue('postcode');
            /* Check zip code format */
            if ($country->zip_code_format && !$country->checkZipCode($postcode)) {
                $this->errors[] = Tools::displayError('Your Zip/postal code is incorrect.').'<br />'.Tools::displayError('It must be entered as follows:').' '.str_replace('C', $country->iso_code, str_replace('N', '0', str_replace('L', 'A', $country->zip_code_format)));
            } elseif (empty($postcode) && $country->need_zip_code) {
                $this->errors[] = Tools::displayError('A Zip/postal code is required.');
            } elseif ($postcode && !Validate::isPostCode($postcode)) {
                $this->errors[] = Tools::displayError('The Zip/postal code is invalid.');
            }

            /* Store hours */
            $_POST['hours'] = [];
            for ($i = 1; $i < 8; $i++) {
                $_POST['hours'][] .= Tools::getValue('hours_'.(int) $i);
            }
            $_POST['hours'] = json_encode($_POST['hours']);
        }

        if (!count($this->errors)) {
            parent::postProcess();
        } else {
            $this->display = 'add';
        }
    }

    /**
     * Before updating options
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function beforeUpdateOptions()
    {
        if (isset($_POST['PS_SHOP_STATE_ID']) && $_POST['PS_SHOP_STATE_ID'] != '0') {
            $sql = 'SELECT `active` FROM `'._DB_PREFIX_.'state`
					WHERE `id_country` = '.(int) Tools::getValue('PS_SHOP_COUNTRY_ID').'
						AND `id_state` = '.(int) Tools::getValue('PS_SHOP_STATE_ID');
            $isStateOk = Db::getInstance()->getValue($sql);
            if ($isStateOk != 1) {
                $this->errors[] = Tools::displayError('The specified state is not located in this country.');
            }
        }
    }

    /**
     * @param mixed $value
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function updateOptionPsShopCountryId($value)
    {
        if (!$this->errors && $value) {
            $country = new Country($value, $this->context->language->id);
            if ($country->id) {
                Configuration::updateValue('PS_SHOP_COUNTRY_ID', $value);
                Configuration::updateValue('PS_SHOP_COUNTRY', $country->name);
            }
        }
    }

    /**
     * @param mixed $value
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function updateOptionPsShopStateId($value)
    {
        if (!$this->errors && $value) {
            $state = new State($value);
            if ($state->id) {
                Configuration::updateValue('PS_SHOP_STATE_ID', $value);
                Configuration::updateValue('PS_SHOP_STATE', $state->name);
            }
        }
    }

    /**
     * @param int $id
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function postImage($id)
    {
        $ret = parent::postImage($id);

        if (($idStore = (int) Tools::getValue('id_store')) && isset($_FILES) && count($_FILES) && file_exists(_PS_STORE_IMG_DIR_.$idStore.'.jpg')) {
            $imageTypes = ImageType::getImagesTypes('stores');
            foreach ($imageTypes as $k => $imageType) {
                ImageManager::resize(
                    _PS_STORE_IMG_DIR_.$idStore.'.jpg',
                    _PS_STORE_IMG_DIR_.$idStore.'-'.stripslashes($imageType['name']).'.jpg',
                    (int) $imageType['width'],
                    (int) $imageType['height']
                );
                if (ImageManager::retinaSupport()) {
                    ImageManager::resize(
                        _PS_STORE_IMG_DIR_.$idStore.'.jpg',
                        _PS_STORE_IMG_DIR_.$idStore.'-'.stripslashes($imageType['name']).'2x.jpg',
                        (int) $imageType['width'] * 2,
                        (int) $imageType['height'] * 2
                    );
                }
                if (ImageManager::webpSupport()) {
                    ImageManager::resize(
                        _PS_STORE_IMG_DIR_.$idStore.'.jpg',
                        _PS_STORE_IMG_DIR_.$idStore.'-'.stripslashes($imageType['name']).'.webp',
                        (int) $imageType['width'],
                        (int) $imageType['height'],
                        'webp'
                    );
                    if (ImageManager::retinaSupport()) {
                        ImageManager::resize(
                            _PS_STORE_IMG_DIR_.$idStore.'.jpg',
                            _PS_STORE_IMG_DIR_.$idStore.'-'.stripslashes($imageType['name']).'2x.webp',
                            (int) $imageType['width'] * 2,
                            (int) $imageType['height'] * 2,
                            'webp'
                        );
                    }
                }

                if (Configuration::get('TB_IMAGE_LAST_UPD_STORES') < $idStore) {
                    Configuration::updateValue('TB_IMAGE_LAST_UPD_STORES', $idStore);
                }
            }
        }

        return $ret;
    }
}
