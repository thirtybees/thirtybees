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
 * Class AdminAddressesControllerCore
 *
 * @since 1.0.0
 */
class AdminAddressesControllerCore extends AdminController
{
    /** @var array countries list */
    protected $countries_array = [];

    /**
     * AdminAddressesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->required_database = true;
        $this->required_fields = ['company', 'address2', 'postcode', 'other', 'phone', 'phone_mobile', 'vat_number', 'dni'];
        $this->table = 'address';
        $this->className = 'Address';
        $this->lang = false;
        $this->addressType = 'customer';
        $this->explicitSelect = true;
        $this->context = Context::getContext();

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->allow_export = true;

        if (!Tools::getValue('realedit')) {
            $this->deleted = true;
        }

        $countries = Country::getCountries($this->context->language->id);
        foreach ($countries as $country) {
            $this->countries_array[$country['id_country']] = $country['name'];
        }

        $this->fields_list = [
            'id_address' => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'firstname'  => ['title' => $this->l('First Name'), 'filter_key' => 'a!firstname'],
            'lastname'   => ['title' => $this->l('Last Name'), 'filter_key' => 'a!lastname'],
            'address1'   => ['title' => $this->l('Address')],
            'postcode'   => ['title' => $this->l('Zip/Postal Code'), 'align' => 'right'],
            'city'       => ['title' => $this->l('City')],
            'country'    => ['title' => $this->l('Country'), 'type' => 'select', 'list' => $this->countries_array, 'filter_key' => 'cl!id_country'],
        ];

        parent::__construct();

        $this->_select = 'cl.`name` as country';
        $this->_join = '
			LEFT JOIN `'._DB_PREFIX_.'country_lang` cl ON (cl.`id_country` = a.`id_country` AND cl.`id_lang` = '.(int) $this->context->language->id.')
			LEFT JOIN `'._DB_PREFIX_.'customer` c ON a.id_customer = c.id_customer
		';
        $this->_where = 'AND a.id_customer != 0 '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER, 'c');
        $this->_use_found_rows = false;
    }

    /**
     * @since 1.0.0
     */
    public function initToolbar()
    {
        parent::initToolbar();

        if (!$this->display && $this->can_import) {
            $this->toolbar_btn['import'] = [
                'href' => $this->context->link->getAdminLink('AdminImport', true).'&import_type=addresses',
                'desc' => $this->l('Import'),
            ];
        }
    }

    /**
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_address'] = [
                'href' => self::$currentIndex.'&addaddress&token='.$this->token,
                'desc' => $this->l('Add new address', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Addresses'),
                'icon'  => 'icon-envelope-alt',
            ],
            'input'  => [
                [
                    'type'     => 'text_customer',
                    'label'    => $this->l('Customer'),
                    'name'     => 'id_customer',
                    'required' => false,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Identification Number'),
                    'name'     => 'dni',
                    'required' => false,
                    'col'      => '4',
                    'hint'     => $this->l('DNI / NIF / NIE'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Address alias'),
                    'name'     => 'alias',
                    'required' => true,
                    'col'      => '4',
                    'hint'     => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'     => 'textarea',
                    'label'    => $this->l('Other'),
                    'name'     => 'other',
                    'required' => false,
                    'cols'     => 15,
                    'rows'     => 3,
                    'hint'     => $this->l('Forbidden characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'id_order',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'address_type',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'back',
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['address_type'] = (int) Tools::getValue('address_type', 1);

        $idCustomer = (int) Tools::getValue('id_customer');
        if (!$idCustomer && Validate::isLoadedObject($this->object)) {
            $idCustomer = $this->object->id_customer;
        }
        if ($idCustomer) {
            $customer = new Customer((int) $idCustomer);
            $token_customer = Tools::getAdminToken('AdminCustomers'.(int) (Tab::getIdFromClassName('AdminCustomers')).(int) $this->context->employee->id);
        }

        $this->tpl_form_vars = [
            'customer'      => isset($customer) ? $customer : null,
            'tokenCustomer' => isset($token_customer) ? $token_customer : null,
            'back_url'      => urldecode(Tools::getValue('back')),
        ];

        // Order address fields depending on country format
        $addressesFields = $this->processAddressFormat();
        // we use  delivery address
        $addressesFields = $addressesFields['dlv_all_fields'];

        // get required field
        $requiredFields = AddressFormat::getFieldsRequired();

        // Merge with field required
        $addressesFields = array_unique(array_merge($addressesFields, $requiredFields));

        $tempFields = [];

        foreach ($addressesFields as $addrFieldItem) {
            if ($addrFieldItem == 'company') {
                $tempFields[] = [
                    'type'     => 'text',
                    'label'    => $this->l('Company'),
                    'name'     => 'company',
                    'required' => in_array('company', $requiredFields),
                    'col'      => '4',
                    'hint'     => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                ];
                $tempFields[] = [
                    'type'     => 'text',
                    'label'    => $this->l('VAT number'),
                    'col'      => '2',
                    'name'     => 'vat_number',
                    'required' => in_array('vat_number', $requiredFields),
                ];
            } elseif ($addrFieldItem == 'lastname') {
                if (isset($customer) &&
                    !Tools::isSubmit('submit'.strtoupper($this->table)) &&
                    Validate::isLoadedObject($customer) &&
                    !Validate::isLoadedObject($this->object)
                ) {
                    $defaultValue = $customer->lastname;
                } else {
                    $defaultValue = '';
                }

                $tempFields[] = [
                    'type'          => 'text',
                    'label'         => $this->l('Last Name'),
                    'name'          => 'lastname',
                    'required'      => true,
                    'col'           => '4',
                    'hint'          => $this->l('Invalid characters:').' 0-9!&amp;lt;&amp;gt;,;?=+()@#"�{}_$%:',
                    'default_value' => $defaultValue,
                ];
            } elseif ($addrFieldItem == 'firstname') {
                if (isset($customer) &&
                    !Tools::isSubmit('submit'.strtoupper($this->table)) &&
                    Validate::isLoadedObject($customer) &&
                    !Validate::isLoadedObject($this->object)
                ) {
                    $defaultValue = $customer->firstname;
                } else {
                    $defaultValue = '';
                }

                $tempFields[] = [
                    'type'          => 'text',
                    'label'         => $this->l('First Name'),
                    'name'          => 'firstname',
                    'required'      => true,
                    'col'           => '4',
                    'hint'          => $this->l('Invalid characters:').' 0-9!&amp;lt;&amp;gt;,;?=+()@#"�{}_$%:',
                    'default_value' => $defaultValue,
                ];
            } elseif ($addrFieldItem == 'address1') {
                $tempFields[] = [
                    'type'     => 'text',
                    'label'    => $this->l('Address'),
                    'name'     => 'address1',
                    'col'      => '6',
                    'required' => true,
                ];
            } elseif ($addrFieldItem == 'address2') {
                $tempFields[] = [
                    'type'     => 'text',
                    'label'    => $this->l('Address').' (2)',
                    'name'     => 'address2',
                    'col'      => '6',
                    'required' => in_array('address2', $requiredFields),
                ];
            } elseif ($addrFieldItem == 'postcode') {
                $tempFields[] = [
                    'type'     => 'text',
                    'label'    => $this->l('Zip/Postal Code'),
                    'name'     => 'postcode',
                    'col'      => '2',
                    'required' => true,
                ];
            } elseif ($addrFieldItem == 'city') {
                $tempFields[] = [
                    'type'     => 'text',
                    'label'    => $this->l('City'),
                    'name'     => 'city',
                    'col'      => '4',
                    'required' => true,
                ];
            } elseif ($addrFieldItem == 'country' || $addrFieldItem == 'Country:name') {
                $tempFields[] = [
                    'type'          => 'select',
                    'label'         => $this->l('Country'),
                    'name'          => 'id_country',
                    'required'      => in_array('Country:name', $requiredFields) || in_array('country', $requiredFields),
                    'col'           => '4',
                    'default_value' => (int) $this->context->country->id,
                    'options'       => [
                        'query' => Country::getCountries($this->context->language->id),
                        'id'    => 'id_country',
                        'name'  => 'name',
                    ],
                ];
                $tempFields[] = [
                    'type'     => 'select',
                    'label'    => $this->l('State'),
                    'name'     => 'id_state',
                    'required' => false,
                    'col'      => '4',
                    'options'  => [
                        'query' => [],
                        'id'    => 'id_state',
                        'name'  => 'name',
                    ],
                ];
            } elseif ($addrFieldItem == 'phone') {
                $tempFields[] = [
                    'type'     => 'text',
                    'label'    => $this->l('Home phone'),
                    'name'     => 'phone',
                    'required' => in_array('phone', $requiredFields) || Configuration::get('PS_ONE_PHONE_AT_LEAST'),
                    'col'      => '4',
                    'hint'     => Configuration::get('PS_ONE_PHONE_AT_LEAST') ? sprintf($this->l('You must register at least one phone number.')) : '',
                ];
            } elseif ($addrFieldItem == 'phone_mobile') {
                $tempFields[] = [
                    'type'     => 'text',
                    'label'    => $this->l('Mobile phone'),
                    'name'     => 'phone_mobile',
                    'required' => in_array('phone_mobile', $requiredFields) || Configuration::get('PS_ONE_PHONE_AT_LEAST'),
                    'col'      => '4',
                    'hint'     => Configuration::get('PS_ONE_PHONE_AT_LEAST') ? sprintf($this->l('You must register at least one phone number.')) : '',
                ];
            }
        }

        // merge address format with the rest of the form
        array_splice($this->fields_form['input'], 3, 0, $tempFields);

        return parent::renderForm();
    }

    /**
     * Get Address formats used by the country where the address id retrieved from POST/GET is.
     *
     * @return array address formats
     */
    protected function processAddressFormat()
    {
        $tmpAddr = new Address((int) Tools::getValue('id_address'));

        $selectedCountry = ($tmpAddr && $tmpAddr->id_country) ? $tmpAddr->id_country : (int) Configuration::get('PS_COUNTRY_DEFAULT');

        $invAdrFields = AddressFormat::getOrderedAddressFields($selectedCountry, false, true);
        $dlvAdrFields = AddressFormat::getOrderedAddressFields($selectedCountry, false, true);

        $invAllFields = [];
        $dlvAllFields = [];

        $out = [];

        foreach (['inv', 'dlv'] as $adrType) {
            foreach (${$adrType.'AdrFields'} as $fieldsLine) {
                foreach (explode(' ', $fieldsLine) as $fieldItem) {
                    ${$adrType.'AllFields'}[] = trim($fieldItem);
                }
            }

            $out[$adrType.'_adr_fields'] = ${$adrType.'AdrFields'};
            $out[$adrType.'_all_fields'] = ${$adrType.'AllFields'};
        }

        return $out;
    }

    /**
     * @return bool|false|ObjectModel|null
     *
     * @since 1.0.0
     */
    public function processSave()
    {
        if (Tools::getValue('submitFormAjax')) {
            $this->redirect_after = false;
        }

        // Transform e-mail in id_customer for parent processing
        if (Validate::isEmail(Tools::getValue('email'))) {
            $customer = new Customer();
            $customer->getByEmail(Tools::getValue('email'), null, false);
            if (Validate::isLoadedObject($customer)) {
                $_POST['id_customer'] = $customer->id;
            } else {
                $this->errors[] = Tools::displayError('This email address is not registered.');
            }
        } elseif ($idCustomer = Tools::getValue('id_customer')) {
            $customer = new Customer((int) $idCustomer);
            if (Validate::isLoadedObject($customer)) {
                $_POST['id_customer'] = $customer->id;
            } else {
                $this->errors[] = Tools::displayError('This customer ID is not recognized.');
            }
        } else {
            $this->errors[] = Tools::displayError('This email address is not valid. Please use an address like bob@example.com.');
        }
        if (Country::isNeedDniByCountryId(Tools::getValue('id_country')) && !Tools::getValue('dni')) {
            $this->errors[] = Tools::displayError('The identification number is incorrect or has already been used.');
        }

        /* If the selected country does not contain states */
        $idState = (int) Tools::getValue('id_state');
        $idCountry = (int) Tools::getValue('id_country');
        $country = new Country((int) $idCountry);
        if ($country && !(int) $country->contains_states && $idState) {
            $this->errors[] = Tools::displayError('You have selected a state for a country that does not contain states.');
        }

        /* If the selected country contains states, then a state have to be selected */
        if ((int) $country->contains_states && !$idState) {
            $this->errors[] = Tools::displayError('An address located in a country containing states must have a state selected.');
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

        if (Configuration::get('PS_ONE_PHONE_AT_LEAST') && !Tools::getValue('phone') && !Tools::getValue('phone_mobile')) {
            $this->errors[] = Tools::displayError('You must register at least one phone number.');
        }

        /* If this address come from order's edition and is the same as the other one (invoice or delivery one)
        ** we delete its id_address to force the creation of a new one */
        if ((int) Tools::getValue('id_order')) {
            $this->_redirect = false;
            if (isset($_POST['address_type'])) {
                $_POST['id_address'] = '';
                $this->id_object = null;
            }
        }

        // Check the requires fields which are settings in the BO
        $address = new Address();
        $this->errors = array_merge($this->errors, $address->validateFieldsRequiredDatabase());

        $return = false;
        if (empty($this->errors)) {
            $return = parent::processSave();
        } else {
            // if we have errors, we stay on the form instead of going back to the list
            $this->display = 'edit';
        }

        /* Reassignation of the order's new (invoice or delivery) address */
        $addressType = (int) Tools::getValue('address_type') == 2 ? 'invoice' : 'delivery';

        if ($this->action == 'save' && ($idOrder = (int) Tools::getValue('id_order')) && !count($this->errors) && !empty($addressType)) {
            if (!Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'orders SET `id_address_'.bqSQL($addressType).'` = '.(int) $this->object->id.' WHERE `id_order` = '.(int) $idOrder)) {
                $this->errors[] = Tools::displayError('An error occurred while linking this address to its order.');
            } else {
                Tools::redirectAdmin(urldecode(Tools::getValue('back')).'&conf=4');
            }
        }

        return $return;
    }

    /**
     * @return false|ObjectModel
     *
     * @since 1.0.0
     */
    public function processAdd()
    {
        if (Tools::getValue('submitFormAjax')) {
            $this->redirect_after = false;
        }

        return parent::processAdd();
    }

    /**
     * Method called when an ajax request is made
     *
     * @see AdminController::postProcess()
     *
     * @since 1.0.0
     */
    public function ajaxProcess()
    {
        if (Tools::isSubmit('email')) {
            $email = pSQL(Tools::getValue('email'));
            $customer = Customer::searchByName($email);
            if (!empty($customer)) {
                $customer = $customer['0'];
                $this->ajaxDie(json_encode(['infos' => pSQL($customer['firstname']).'_'.pSQL($customer['lastname']).'_'.pSQL($customer['company'])]));
            }
        }
        die;
    }

    /**
     * @return false|ObjectModel
     *
     * @since 1.0.0
     */
    public function processDelete()
    {
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            /** @var Address $object */
            if (!$object->isUsed()) {
                $this->deleted = false;
            }
        }

        $res = parent::processDelete();

        if ($back = Tools::getValue('back')) {
            $this->redirect_after = urldecode($back).'&conf=1';
        }

        return $res;
    }

    /**
     * Delete multiple items
     *
     * @return bool true if succcess
     */
    protected function processBulkDelete()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $deleted = false;
            foreach ($this->boxes as $id) {
                $toDelete = new Address((int) $id);
                if ($toDelete->isUsed()) {
                    $deleted = true;
                    break;
                }
            }
            $this->deleted = $deleted;
        }

        return parent::processBulkDelete();
    }
}
