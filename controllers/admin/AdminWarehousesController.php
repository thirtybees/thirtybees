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
 * Class AdminWarehousesControllerCore
 *
 * @since 1.0.0
 */
class AdminWarehousesControllerCore extends AdminController
{
    /**
     * AdminWarehousesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'warehouse';
        $this->className = 'Warehouse';
        $this->deleted = true;
        $this->lang = false;
        $this->multishop_context = Shop::CONTEXT_ALL;

        $this->fields_list = [
            'id_warehouse'    => [
                'title' => $this->l('ID'),
                'width' => 50,
            ],
            'reference'       => [
                'title' => $this->l('Reference'),
            ],
            'name'            => [
                'title' => $this->l('Name'),
            ],
            'management_type' => [
                'title' => $this->l('Management type'),
            ],
            'employee'        => [
                'title'        => $this->l('Manager'),
                'filter_key'   => 'employee',
                'havingFilter' => true,
            ],
            'location'        => [
                'title'   => $this->l('Location'),
                'orderby' => false,
                'filter'  => false,
                'search'  => false,
            ],
            'contact'         => [
                'title'   => $this->l('Phone Number'),
                'orderby' => false,
                'filter'  => false,
                'search'  => false,
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'icon'    => 'icon-trash',
                'confirm' => $this->l('Delete selected items?'),
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
            $this->page_header_toolbar_btn['new_warehouse'] = [
                'href' => static::$currentIndex.'&addwarehouse&token='.$this->token,
                'desc' => $this->l('Add new warehouse', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * AdminController::renderList() override
     *
     * @see AdminController::renderList()
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        // removes links on rows
        $this->list_no_link = true;

        // adds actions on rows
        $this->addRowAction('edit');
        $this->addRowAction('view');
        $this->addRowAction('delete');

        // query: select
        $this->_select = '
			reference,
			name,
			management_type,
			CONCAT(e.lastname, \' \', e.firstname) as employee,
			ad.phone as contact,
			CONCAT(ad.city, \' - \', c.iso_code) as location';

        // query: join
        $this->_join = '
			LEFT JOIN `'._DB_PREFIX_.'employee` e ON (e.id_employee = a.id_employee)
			LEFT JOIN `'._DB_PREFIX_.'address` ad ON (ad.id_address = a.id_address)
			LEFT JOIN `'._DB_PREFIX_.'country` c ON (c.id_country = ad.id_country)';
        $this->_use_found_rows = false;
        // display help informations
        $this->displayInformation($this->l('This interface allows you to manage your warehouses.').'<br />');
        $this->displayInformation($this->l('Before adding stock in your warehouses, you should check the default currency used.').'<br />');
        $this->displayInformation($this->l('You should also check the management type (according to the law in your country), the valuation currency and its associated carriers and shops.').'<br />');
        $this->displayInformation($this->l('You can also see detailed information about your stock, such as its overall value, the number of products and quantities stored, etc.'));
        $this->displayInformation($this->l('Be careful! Products from different warehouses will need to be shipped in different packages.'));

        return parent::renderList();
    }

    /**
     * AdminController::renderForm() override
     *
     * @see AdminController::renderForm()
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        /** @var Warehouse $obj */
        // loads current warehouse
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        // gets the manager of the warehouse
        $query = new DbQuery();
        $query->select('id_employee, CONCAT(lastname," ",firstname) as name');
        $query->from('employee');
        $query->where('active = 1');
        $employeesArray = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        // sets the title of the toolbar
        if (Tools::isSubmit('add'.$this->table)) {
            $this->toolbar_title = $this->l('Stock: Create a warehouse');
        } else {
            $this->toolbar_title = $this->l('Stock: Warehouse management');
        }

        $tmpAddr = new Address();
        $res = $tmpAddr->getFieldsRequiredDatabase();
        $requiredFields = [];
        foreach ($res as $row) {
            $requiredFields[(int) $row['id_required_field']] = $row['field_name'];
        }

        // sets the fields of the form
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Warehouse information'),
                'icon'  => 'icon-pencil',
            ],
            'input'  => [
                [
                    'type' => 'hidden',
                    'name' => 'id_address',
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Reference'),
                    'name'      => 'reference',
                    'maxlength' => 32,
                    'required'  => true,
                    'hint'      => $this->l('Reference for this warehouse.'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Name'),
                    'name'      => 'name',
                    'maxlength' => 45,
                    'required'  => true,
                    'hint'      => [
                        $this->l('Name of this warehouse.'),
                        $this->l('Invalid characters:').' !&lt;&gt;,;?=+()@#"�{}_$%:',
                    ],
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Phone'),
                    'name'      => 'phone',
                    'maxlength' => 16,
                    'hint'      => $this->l('Phone number for this warehouse.'),
                    'required'  => in_array('phone', $requiredFields),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Mobile phone'),
                    'name'      => 'phone_mobile',
                    'required'  => in_array('phone_mobile', $requiredFields),
                    'maxlength' => 16,
                    'hint'      => $this->l('Mobile phone number for this supplier.'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Address'),
                    'name'      => 'address',
                    'maxlength' => 128,
                    'required'  => true,
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Address').' (2)',
                    'name'      => 'address2',
                    'maxlength' => 128,
                    'hint'      => $this->l('Complementary address (optional).'),
                    'required'  => in_array('address2', $requiredFields),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Zip/postal code'),
                    'name'      => 'postcode',
                    'maxlength' => 12,
                    'required'  => in_array('postcode', $requiredFields),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('City'),
                    'name'      => 'city',
                    'maxlength' => 32,
                    'required'  => true,
                ],
                [
                    'type'          => 'select',
                    'label'         => $this->l('Country'),
                    'name'          => 'id_country',
                    'required'      => true,
                    'default_value' => (int) $this->context->country->id,
                    'options'       => [
                        'query' => Country::getCountries($this->context->language->id, false),
                        'id'    => 'id_country',
                        'name'  => 'name',
                    ],
                    'hint'          => $this->l('Country of location of the warehouse.'),
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('State'),
                    'name'     => 'id_state',
                    'required' => true,
                    'options'  => [
                        'query' => [],
                        'id'    => 'id_state',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Manager'),
                    'name'     => 'id_employee',
                    'required' => true,
                    'options'  => [
                        'query' => $employeesArray,
                        'id'    => 'id_employee',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'     => 'swap',
                    'label'    => $this->l('Carriers'),
                    'name'     => 'ids_carriers',
                    'required' => false,
                    'multiple' => true,
                    'options'  => [
                        'query' => Carrier::getCarriers($this->context->language->id, false, false, false, null, Carrier::ALL_CARRIERS),
                        'id'    => 'id_reference',
                        'name'  => 'name',
                    ],
                    'hint'     => [
                        $this->l('Associated carriers.'),
                        $this->l('You can choose which carriers can ship orders from particular warehouses.'),
                        $this->l('If you do not select any carrier, all the carriers will be able to ship from this warehouse.'),
                    ],
                    'desc'     => $this->l('If no carrier is selected, all the carriers will be allowed to ship from this warehouse. Use CTRL+Click to select more than one carrier.'),
                ],
            ],

        ];

        // Shop Association
        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'           => 'shop',
                'label'          => $this->l('Shop association'),
                'name'           => 'checkBoxShopAsso',
                'disable_shared' => Shop::SHARE_STOCK,
            ];
        }

        // if it is still possible to change currency valuation and management type
        if (Tools::isSubmit('addwarehouse') || Tools::isSubmit('submitAddwarehouse')) {
            // adds input management type
            $this->fields_form['input'][] = [
                'type'     => 'select',
                'label'    => $this->l('Management type'),
                'hint'     => $this->l('Inventory valuation method. Be careful! You won\'t be able to change this value later!'),
                'name'     => 'management_type',
                'required' => true,
                'options'  => [
                    'query' => [
                        [
                            'id'   => 'WA',
                            'name' => $this->l('Weighted Average'),
                        ],
                        [
                            'id'   => 'FIFO',
                            'name' => $this->l('First In, First Out'),
                        ],
                        [
                            'id'   => 'LIFO',
                            'name' => $this->l('Last In, First Out'),
                        ],
                    ],
                    'id'    => 'id',
                    'name'  => 'name',
                ],
            ];

            // adds input valuation currency
            $this->fields_form['input'][] = [
                'type'     => 'select',
                'label'    => $this->l('Stock valuation currency'),
                'hint'     => $this->l('Be careful! You won\'t be able to change this value later!'),
                'name'     => 'id_currency',
                'required' => true,
                'options'  => [
                    'query' => Currency::getCurrencies(false, true, true),
                    'id'    => 'id_currency',
                    'name'  => 'name',
                ],
            ];
        } else {
            // else hide input

            $this->fields_form['input'][] = [
                'type' => 'hidden',
                'name' => 'management_type',
            ];

            $this->fields_form['input'][] = [
                'type' => 'hidden',
                'name' => 'id_currency',
            ];
        }

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        $address = null;
        // loads current address for this warehouse - if possible
        if ($obj->id_address > 0) {
            $address = new Address($obj->id_address);
        }

        // loads current shops associated with this warehouse
        $shops = $obj->getShops();
        $idsShop = [];
        foreach ($shops as $shop) {
            $idsShop[] = $shop['id_shop'];
        }

        // loads current carriers associated with this warehouse
        $carriers = $obj->getCarriers(true);

        // if an address is available : force specific fields values
        if ($address != null) {
            $this->fields_value = [
                'id_address' => $address->id,
                'phone'      => $address->phone,
                'address'    => $address->address1,
                'address2'   => $address->address2,
                'postcode'   => $address->postcode,
                'city'       => $address->city,
                'id_country' => $address->id_country,
                'id_state'   => $address->id_state,
            ];
        } else { // loads default country
            $this->fields_value = [
                'id_address' => 0,
                'id_country' => Configuration::get('PS_COUNTRY_DEFAULT'),
            ];
        }

        // loads shops and carriers
        $this->fields_value['ids_shops[]'] = $idsShop;
        $this->fields_value['ids_carriers'] = $carriers;

        if (!Validate::isLoadedObject($obj)) {
            $this->fields_value['id_currency'] = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        }

        return parent::renderForm();
    }

    /**
     * @see AdminController::renderView()
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderView()
    {
        // gets necessary objects
        $idWarehouse = (int) Tools::getValue('id_warehouse');
        $warehouse = new Warehouse($idWarehouse);
        $employee = new Employee($warehouse->id_employee);
        $currency = new Currency($warehouse->id_currency);
        $address = new Address($warehouse->id_address);
        $shops = $warehouse->getShops();

        $this->toolbar_title = $warehouse->name;

        // checks objects
        if (!Validate::isLoadedObject($warehouse) ||
            !Validate::isLoadedObject($employee) ||
            !Validate::isLoadedObject($currency) ||
            !Validate::isLoadedObject($address)
        ) {
            return parent::renderView();
        }

        // assigns to our view
        $this->tpl_view_vars = [
            'warehouse'              => $warehouse,
            'employee'               => $employee,
            'currency'               => $currency,
            'address'                => $address,
            'shops'                  => $shops,
            'warehouse_num_products' => $warehouse->getNumberOfProducts(),
            'warehouse_value'        => Tools::displayPrice($warehouse->getStockValue(), $currency),
            'warehouse_quantities'   => $warehouse->getQuantitiesofProducts(),
        ];

        return parent::renderView();
    }

    /**
     * AdminController::getList() override
     *
     * @see AdminController::getList()
     *
     * @param int         $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int         $start
     * @param int|null    $limit
     * @param int|bool    $idLangShop
     *
     * @throws PrestaShopException
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function getList(
        $idLang,
        $orderBy = null,
        $orderWay = null,
        $start = 0,
        $limit = null,
        $idLangShop = false
    ) {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        // foreach item in the list to render
        $nbItems = count($this->_list);
        for ($i = 0; $i < $nbItems; ++$i) {
            // depending on the management type, translates the management type
            $item = &$this->_list[$i];
            switch ($item['management_type']) {// management type can be either WA/FIFO/LIFO

                case 'WA':
                    $item['management_type'] = $this->l('WA: Weighted Average');
                    break;

                case 'FIFO':
                    $item['management_type'] = $this->l('FIFO: First In, First Out');
                    break;

                case 'LIFO':
                    $item['management_type'] = $this->l('LIFO: Last In, First Out');
                    break;
            }
        }
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        if ($this->isAdvancedStockManagementActive()) {
            parent::initContent();

            return true;
        }

        return false;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    protected function isAdvancedStockManagementActive()
    {
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            return true;
        }

        $this->warnings[md5('PS_ADVANCED_STOCK_MANAGEMENT')] = $this->l('You need to activate advanced stock management before using this feature.');

        return false;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function initProcess()
    {
        if ($this->isAdvancedStockManagementActive()) {
            return parent::initProcess();
        }

        return false;
    }

    /**
     * @see AdminController::processAdd();
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function processAdd()
    {
        if (Tools::isSubmit('submitAdd'.$this->table)) {
            if (!($obj = $this->loadObject(true))) {
                return;
            }

            $this->updateAddress();

            // hack for enable the possibility to update a warehouse without recreate new id
            $this->deleted = false;

            parent::processAdd();
        }
    }

    /**
     * @return void
     *
     * @since 1.0.0
     */
    protected function updateAddress()
    {
        /** @var AddressCore $address */
        $address = new Address();

        if (Tools::isSubmit('id_address') && (int) Tools::getValue('id_address') > 0) {
            $address = new Address((int) Tools::getValue('id_address'));
        }

        $address->alias = Tools::getValue('reference', null);
        $address->lastname = 'warehouse';   // skip problem with numeric characters
        $address->firstname = 'warehouse';  // in warehouse name
        $address->address1 = Tools::getValue('address', null);
        $address->address2 = Tools::getValue('address2', null);
        $address->postcode = Tools::getValue('postcode', null);
        $address->phone = Tools::getValue('phone', null);
        $address->id_country = Tools::getValue('id_country', null);
        $address->id_state = Tools::getValue('id_state', null);
        $address->city = Tools::getValue('city', null);

        if (!($country = new Country($address->id_country, Configuration::get('PS_LANG_DEFAULT'))) || !Validate::isLoadedObject($country)) {
            $this->errors[] = Tools::displayError('Country is invalid');
        }

        $containsState = isset($country) && is_object($country) ? (int) $country->contains_states : 0;
        $idState = isset($address) && is_object($address) ? (int) $address->id_state : 0;
        if ($containsState && !$idState) {
            $this->errors[] = Tools::displayError('This country requires you to choose a State.');
        }

        // validates the address
        $validation = $address->validateController();

        // checks address validity
        if (count($validation) > 0) {
            // if not valid

            foreach ($validation as $item) {
                $this->errors[] = $item;
            }

            $this->errors[] = Tools::displayError(
                'The address is not correct. Please make sure all of the required fields are completed.'
            );
        } else {
            // valid

            if (Tools::isSubmit('id_address') && Tools::getValue('id_address') > 0) {
                $address->update();
            } else {
                $address->save();
                $_POST['id_address'] = $address->id;
            }
        }
    }

    /**
     * When submitting a warehouse deletion request,
     * make an attempt to load a warehouse instance from an identifier,
     * ensure the warehouse to be deleted,
     *  - does not contain any products,
     *  - nor it has some pending supply orders
     * before actual deletion
     *
     * @return bool|mixed
     *
     * @since 1.0.0
     */
    public function processDelete()
    {
        if (!Tools::isSubmit('delete'.$this->table)) {
            return false;
        }

        /** @var Warehouse $warehouse */
        $warehouse = $this->loadObject(true);

        if ($this->shouldForbidWarehouseDeletion($warehouse)) {
            return false;
        }

        return $this->deleteWarehouse($warehouse);
    }

    /**
     * @param $warehouse
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function shouldForbidWarehouseDeletion($warehouse)
    {
        if (!$warehouse) {
            return true;
        }

        if ($warehouse->getQuantitiesOfProducts() > 0) {
            $this->errors[] = $this->l('It is not possible to delete a warehouse when there are products in it.');

            return true;
        }

        if (SupplyOrder::warehouseHasPendingOrders($warehouse->id)) {
            $this->errors[] = $this->l('It is not possible to delete a Warehouse if it has pending supply orders.');

            return true;
        }

        return false;
    }

    /**
     * @param Warehouse $warehouse
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    protected function deleteWarehouse(Warehouse $warehouse)
    {
        $address = new Address($warehouse->id_address);
        $this->markAddressAsDeleted($address);

        /** @var WarehouseCore $warehouse */
        $warehouse->setCarriers([]);
        $warehouse->resetProductsLocations();

        return parent::processDelete();
    }

    /**
     * @param Address $address
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function markAddressAsDeleted(Address $address)
    {
        /** @var AddressCore $address */
        $address->deleted = 1;
        $address->save();
    }

    /**
     * @see AdminController::processUpdate();
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function processUpdate()
    {
        /** @var WarehouseCore $warehouse */
        if (!($warehouse = $this->loadObject(true))) {
            return false;
        }

        $this->updateAddress();
        // handles carriers associations
        $idsCarriersSelected = Tools::getValue('ids_carriers_selected');
        if (Tools::isSubmit('ids_carriers_selected') && !empty($idsCarriersSelected)) {
            $warehouse->setCarriers($idsCarriersSelected);
        } else {
            $warehouse->setCarriers(Tools::getValue('ids_carriers_available'));
        }

        return parent::processUpdate();
    }

    /**
     * Called once $object is set.
     * Used to process the associations with address/shops/carriers
     *
     * @see AdminController::afterAdd()
     *
     * @param Warehouse $object
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function afterAdd($object)
    {
        // handles address association
        $address = new Address($object->id_address);
        if (Validate::isLoadedObject($address)) {
            $address->id_warehouse = (int) $object->id;
            $address->save();
        }

        // handles carriers associations
        $idsCarriersSelected = Tools::getValue('ids_carriers_selected');
        if (Tools::isSubmit('ids_carriers_selected') && !empty($idsCarriersSelected)) {
            $object->setCarriers($idsCarriersSelected);
        } else {
            $object->setCarriers(Tools::getValue('ids_carriers_available'));
        }

        return true;
    }

    /**
     * @param int $idObject
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function updateAssoShop($idObject)
    {
        parent::updateAssoShop($idObject);
        if (!($obj = $this->loadObject(true))) {
            return;
        }

        /** @var Warehouse $obj */
        $obj->resetStockAvailable();
    }
}
