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
 * Class AdminSupplyOrdersControllerCore
 *
 * @since 1.0.0
 */
class AdminSupplyOrdersControllerCore extends AdminController
{
    /**
     * @var array List of warehouses
     */
    protected $warehouses;

    /**
     * AdminSupplyOrdersControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'supply_order';

        $this->className = 'SupplyOrder';
        $this->identifier = 'id_supply_order';
        $this->lang = false;
        $this->is_template_list = false;
        $this->multishop_context = Shop::CONTEXT_ALL;

        $this->addRowAction('updatereceipt');
        $this->addRowAction('changestate');
        $this->addRowAction('edit');
        $this->addRowAction('view');
        $this->addRowAction('details');
        $this->list_no_link = true;

        $this->fields_list = [
            'reference'              => [
                'title'        => $this->l('Reference'),
                'havingFilter' => true,
            ],
            'supplier'               => [
                'title'      => $this->l('Supplier'),
                'filter_key' => 's!name',
            ],
            'warehouse'              => [
                'title'      => $this->l('Warehouse'),
                'filter_key' => 'w!name',
            ],
            'state'                  => [
                'title'      => $this->l('Status'),
                'filter_key' => 'stl!name',
                'color'      => 'color',
            ],
            'date_add'               => [
                'title'        => $this->l('Creation'),
                'align'        => 'left',
                'type'         => 'date',
                'havingFilter' => true,
                'filter_key'   => 'a!date_add',
            ],
            'date_upd'               => [
                'title'        => $this->l('Last modification'),
                'align'        => 'left',
                'type'         => 'date',
                'havingFilter' => true,
                'filter_key'   => 'a!date_upd',
            ],
            'date_delivery_expected' => [
                'title'        => $this->l('Delivery (expected)'),
                'align'        => 'left',
                'type'         => 'date',
                'havingFilter' => true,
                'filter_key'   => 'a!date_delivery_expected',
            ],
            'id_export'              => [
                'title'    => $this->l('Export'),
                'callback' => 'printExportIcons',
                'orderby'  => false,
                'search'   => false,
            ],
        ];

        // gets the list of warehouses available
        $this->warehouses = Warehouse::getWarehouses(true);
        // gets the final list of warehouses
        array_unshift($this->warehouses, ['id_warehouse' => -1, 'name' => $this->l('All Warehouses')]);

        parent::__construct();
    }

    /**
     * AdminController::init() override
     *
     * @see AdminController::init()
     *
     * @since 1.0.0
     */
    public function init()
    {
        if (Tools::isSubmit('submitFilterorders')) {
            $this->list_id = 'orders';
        } elseif (Tools::isSubmit('submitFiltertemplates')) {
            $this->list_id = 'templates';
        }

        parent::init();

        if (Tools::isSubmit('addsupply_order') ||
            Tools::isSubmit('submitAddsupply_order') ||
            (Tools::isSubmit('updatesupply_order') && Tools::isSubmit('id_supply_order'))
        ) {
            // override table, lang, className and identifier for the current controller
            $this->table = 'supply_order';
            $this->className = 'SupplyOrder';
            $this->identifier = 'id_supply_order';
            $this->lang = false;

            $this->action = 'new';
            $this->display = 'add';

            if (Tools::isSubmit('updatesupply_order')) {
                if ($this->tabAccess['edit'] === '1') {
                    $this->display = 'edit';
                } else {
                    $this->errors[] = Tools::displayError('You do not have permission to edit this.');
                }
            }
        }

        if (Tools::isSubmit('update_receipt') && Tools::isSubmit('id_supply_order')) {
            // change the display type in order to add specific actions to
            $this->display = 'update_receipt';

            // display correct toolBar
            $this->initToolbar();
        }
    }

    /**
     * Assigns default actions in toolbar_btn smarty var, if they are not set.
     * uses override to specifically add, modify or remove items
     *
     * @see AdminSupplier::initToolbar()
     *
     * @since 1.0.0
     */
    public function initToolbar()
    {
        switch ($this->display) {
            case 'update_order_state':
                $this->toolbar_btn['save'] = [
                    'href' => '#',
                    'desc' => $this->l('Save'),
                ];

            case 'update_receipt':
                // Default cancel button - like old back link
                if (!isset($this->no_back) || $this->no_back == false) {
                    $back = Tools::safeOutput(Tools::getValue('back', ''));
                    if (empty($back)) {
                        $back = static::$currentIndex.'&token='.$this->token;
                    }

                    $this->toolbar_btn['cancel'] = [
                        'href' => $back,
                        'desc' => $this->l('Cancel'),
                    ];
                }
                break;

            case 'add':
            case 'edit':
                $this->toolbar_btn['save-and-stay'] = [
                    'href' => '#',
                    'desc' => $this->l('Save and stay'),
                ];
            default:
                parent::initToolbar();
        }
    }

    /**
     * AdminController::renderForm() override
     *
     * @see AdminController::renderForm()
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        if (Tools::isSubmit('addsupply_order') ||
            Tools::isSubmit('updatesupply_order') ||
            Tools::isSubmit('submitAddsupply_order') ||
            Tools::isSubmit('submitUpdatesupply_order')
        ) {
            if (Tools::isSubmit('addsupply_order') || Tools::isSubmit('submitAddsupply_order')) {
                $this->toolbar_title = $this->l('Stock: Create a new supply order');
            }

            $update = false;
            if (Tools::isSubmit('updatesupply_order') || Tools::isSubmit('submitUpdatesupply_order')) {
                $this->toolbar_title = $this->l('Stock: Manage supply orders');
                $update = true;
            }

            if (Tools::isSubmit('mod') && Tools::getValue('mod') === 'template' || $this->object->is_template) {
                $this->toolbar_title .= ' ('.$this->l('template').')';
            }

            $this->addJqueryUI('ui.datepicker');

            //get warehouses list
            $warehouses = Warehouse::getWarehouses(true);

            // displays warning if there are no warehouses
            if (!$warehouses) {
                $this->displayWarning($this->l('You must have at least one warehouse. See Stock/Warehouses'));
            }

            //get currencies list
            $currencies = Currency::getCurrencies(false, true, true);

            //get suppliers list
            $suppliers = array_unique(Supplier::getSuppliers(), SORT_REGULAR);

            //get languages list
            $languages = Language::getLanguages(true);

            $this->fields_form = [
                'legend'  => [
                    'title' => $this->l('Order information'),
                    'icon'  => 'icon-pencil',
                ],
                'input'   => [
                    [
                        'type'     => 'text',
                        'label'    => $this->l('Reference'),
                        'name'     => 'reference',
                        'required' => true,
                        'hint'     => $this->l('The reference number for your order.'),
                    ],
                    [
                        'type'     => 'select',
                        'label'    => $this->l('Supplier'),
                        'name'     => 'id_supplier',
                        'required' => true,
                        'options'  => [
                            'query' => $suppliers,
                            'id'    => 'id_supplier',
                            'name'  => 'name',
                        ],
                        'hint'     => [
                            $this->l('Select the supplier you\'ll be purchasing from.'),
                            $this->l('Warning: All products already added to the order will be removed.'),
                        ],
                    ],
                    [
                        'type'     => 'select',
                        'label'    => $this->l('Warehouse'),
                        'name'     => 'id_warehouse',
                        'required' => true,
                        'options'  => [
                            'query' => $warehouses,
                            'id'    => 'id_warehouse',
                            'name'  => 'name',
                        ],
                        'hint'     => $this->l('Which warehouse will the order be sent to?'),
                    ],
                    [
                        'type'     => 'select',
                        'label'    => $this->l('Currency'),
                        'name'     => 'id_currency',
                        'required' => true,
                        'options'  => [
                            'query' => $currencies,
                            'id'    => 'id_currency',
                            'name'  => 'name',
                        ],
                        'hint'     => [
                            $this->l('The currency of the order.'),
                            $this->l('Warning: All products already added to the order will be removed.'),
                        ],
                    ],
                    [
                        'type'     => 'select',
                        'label'    => $this->l('Order Language'),
                        'name'     => 'id_lang',
                        'required' => true,
                        'options'  => [
                            'query' => $languages,
                            'id'    => 'id_lang',
                            'name'  => 'name',
                        ],
                        'hint'     => $this->l('The language of the order.'),
                    ],
                    [
                        'type'     => 'text',
                        'label'    => $this->l('Global discount percentage'),
                        'name'     => 'discount_rate',
                        'required' => false,
                        'hint'     => $this->l('This is the global discount percentage for the order.'),
                    ],
                    [
                        'type'     => 'text',
                        'label'    => $this->l('Automatically load products'),
                        'name'     => 'load_products',
                        'required' => false,
                        'hint'     => [
                            $this->l('This will reset the order.'),
                            $this->l('If a value specified, each of your current product (from the selected supplier and warehouse) with a quantity lower than or equal to this value will be loaded. This means that thirty bees will pre-fill this order with the products that are low on quantity.'),
                        ],
                    ],
                ],
                'submit'  => (!$update ? ['title' => $this->l('Save order')] : []),
                'buttons' => (!$update ?
                    [
                        'save-and-stay' => [
                            'title' => $this->l('Save order and stay'),
                            'name'  => 'submitAddsupply_orderAndStay',
                            'type'  => 'submit',
                            'class' => 'btn btn-default pull-right',
                            'icon'  => 'process-icon-save',
                        ],
                    ] : []),
            ];

            if (Tools::isSubmit('mod') && Tools::getValue('mod') === 'template' || $this->object->is_template) {
                $this->fields_form['input'][] = [
                    'type' => 'hidden',
                    'name' => 'is_template',
                ];

                $this->fields_form['input'][] = [
                    'type' => 'hidden',
                    'name' => 'date_delivery_expected',
                ];
            } else {
                $this->fields_form['input'][] = [
                    'type'     => 'date',
                    'label'    => $this->l('Expected delivery date'),
                    'name'     => 'date_delivery_expected',
                    'required' => true,
                    'desc'     => $this->l('The expected delivery date for this order is...'),
                ];
            }

            //specific discount display
            if (isset($this->object->discount_rate)) {
                $this->object->discount_rate = round($this->object->discount_rate, 4);
            }

            //specific date display

            if (isset($this->object->date_delivery_expected)) {
                $date = explode(' ', $this->object->date_delivery_expected);
                if ($date) {
                    $this->object->date_delivery_expected = $date[0];
                }
            }

            $this->displayInformation(
                $this->l('If you wish to order products, they have to be available for the specified supplier/warehouse.')
                .' '.
                $this->l('See Catalog/Products/[Your Product]/Suppliers & Warehouses.')
                .'<br />'.
                $this->l('Changing the currency or the supplier will reset the order.')
                .'<br />'
                .'<br />'.
                $this->l('Please note that you can only order from one supplier at a time.')
            );

            return parent::renderForm();
        }
    }

    /**
     * AdminController::renderList() override
     *
     * @see AdminController::renderList()
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        $this->displayInformation($this->l('This interface allows you to manage supply orders.').'<br />');
        $this->displayInformation($this->l('You can create pre-filled order templates, from which you can build actual orders much quicker.').'<br />');

        if (count($this->warehouses) <= 1) {
            $this->displayWarning($this->l('You must choose at least one warehouse before creating supply orders. For more information, see Stock/Warehouses.'));
        }

        // assigns warehouses
        $this->tpl_list_vars['warehouses'] = $this->warehouses;
        $this->tpl_list_vars['current_warehouse'] = $this->getCurrentWarehouse();
        $this->tpl_list_vars['filter_status'] = $this->getFilterStatus();

        // overrides query
        $this->_select = '
			s.name AS supplier,
			w.name AS warehouse,
			stl.name AS state,
			st.delivery_note,
			st.editable,
			st.enclosed,
			st.receipt_state,
			st.pending_receipt,
			st.color AS color,
			a.id_supply_order as id_export';

        $this->_join = '
			LEFT JOIN `'._DB_PREFIX_.'supply_order_state_lang` stl ON
			(
				a.id_supply_order_state = stl.id_supply_order_state
				AND stl.id_lang = '.(int) $this->context->language->id.'
			)
			LEFT JOIN `'._DB_PREFIX_.'supply_order_state` st ON a.id_supply_order_state = st.id_supply_order_state
			LEFT JOIN `'._DB_PREFIX_.'supplier` s ON a.id_supplier = s.id_supplier
			LEFT JOIN `'._DB_PREFIX_.'warehouse` w ON (w.id_warehouse = a.id_warehouse)';

        $this->_where = ' AND a.is_template = 0';

        if ($this->getCurrentWarehouse() != -1) {
            $this->_where .= ' AND a.id_warehouse = '.$this->getCurrentWarehouse();
            static::$currentIndex .= '&id_warehouse='.(int) $this->getCurrentWarehouse();
        }

        if ($this->getFilterStatus() != 0) {
            $this->_where .= ' AND st.enclosed != 1';
            static::$currentIndex .= '&filter_status=on';
        }

        $this->list_id = 'orders';
        $this->_filterHaving = null;

        if (Tools::isSubmit('submitFilter'.$this->list_id)
            || $this->context->cookie->{'submitFilter'.$this->list_id} !== false
            || Tools::getValue($this->list_id.'Orderby')
            || Tools::getValue($this->list_id.'Orderway')
        ) {
            $this->filter = true;
            parent::processFilter();
        }

        $firstList = parent::renderList();

        if (Tools::isSubmit('csv_orders') || Tools::isSubmit('csv_orders_details') || Tools::isSubmit('csv_order_details')) {
            if (count($this->_list) > 0) {
                $this->renderCSV();
                die;
            } else {
                $this->displayWarning($this->l('There is nothing to export as a CSV file.'));
            }
        }

        // second list : templates
        $secondList = null;
        $this->is_template_list = true;
        unset($this->tpl_list_vars['warehouses']);
        unset($this->tpl_list_vars['current_warehouse']);
        unset($this->tpl_list_vars['filter_status']);

        // unsets actions
        $this->actions = [];
        unset($this->toolbar_btn['export-csv-orders']);
        unset($this->toolbar_btn['export-csv-details']);
        // adds actions
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('createsupplyorder');
        $this->addRowAction('delete');
        // unsets some fields
        unset(
            $this->fields_list['state'],
            $this->fields_list['date_upd'],
            $this->fields_list['id_pdf'],
            $this->fields_list['date_delivery_expected'],
            $this->fields_list['id_export']
        );

        // $this->fields_list['date_add']['align'] = 'left';

        // adds filter, to gets only templates
        unset($this->_where);
        $this->_where = ' AND a.is_template = 1';

        if ($this->getCurrentWarehouse() != -1) {
            $this->_where .= ' AND a.id_warehouse = '.$this->getCurrentWarehouse();
        }

        // re-defines toolbar & buttons
        $this->toolbar_title = $this->l('Stock: Supply order templates');
        $this->initToolbar();
        unset($this->toolbar_btn['new']);
        $this->toolbar_btn['new'] = [
            'href'     => static::$currentIndex.'&add'.$this->table.'&mod=template&token='.$this->token,
            'desc'     => $this->l('Add new template'),
            'imgclass' => 'new_1',
            'class'    => 'process-icon-new',
        ];

        $this->list_id = 'templates';
        $this->_filterHaving = null;

        if (Tools::isSubmit('submitFilter'.$this->list_id)
            || $this->context->cookie->{'submitFilter'.$this->list_id} !== false
            || Tools::getValue($this->list_id.'Orderby')
            || Tools::getValue($this->list_id.'Orderway')
        ) {
            $this->filter = true;
            parent::processFilter();
        }
        // inits list
        $secondList = parent::renderList();

        return $firstList.$secondList;
    }

    /**
     * Gets the current warehouse used
     *
     * @return int
     *
     * @since 1.0.0
     */
    protected function getCurrentWarehouse()
    {
        static $warehouse = 0;

        if ($warehouse == 0) {
            $warehouse = -1; // all warehouses
            if ((int) Tools::getValue('id_warehouse')) {
                $warehouse = (int) Tools::getValue('id_warehouse');
            }
        }

        return $warehouse;
    }

    /**
     * Gets the current filter used
     *
     * @return int status
     *
     * @since 1.0.0
     */
    protected function getFilterStatus()
    {
        static $status = 0;

        if (Tools::getValue('filter_status') === 'on') {
            $status = 1;
        }

        return $status;
    }

    /**
     * Exports CSV
     */
    protected function renderCSV()
    {
        // exports orders
        if (Tools::isSubmit('csv_orders')) {
            $ids = [];
            foreach ($this->_list as $entry) {
                $ids[] = $entry['id_supply_order'];
            }

            if (count($ids) <= 0) {
                return;
            }

            $idLang = $this->context->language->id;
            $orders = new PrestaShopCollection('SupplyOrder', $idLang);
            $orders->where('is_template', '=', false);
            $orders->where('id_supply_order', 'in', $ids);
            $idWarehouse = $this->getCurrentWarehouse();
            if ($idWarehouse != -1) {
                $orders->where('id_warehouse', '=', $idWarehouse);
            }
            $orders->getAll();
            $csv = new CSV($orders, $this->l('supply_orders'));
            $csv->export();
        } elseif (Tools::isSubmit('csv_orders_details')) {
            // header
            header('Content-type: text/csv');
            header('Content-Type: application/force-download; charset=UTF-8');
            header('Cache-Control: no-store, no-cache');
            header('Content-disposition: attachment; filename="'.$this->l('supply_orders_details').'.csv"');

            // echoes details
            $ids = [];
            foreach ($this->_list as $entry) {
                $ids[] = $entry['id_supply_order'];
            }

            if (count($ids) <= 0) {
                return;
            }

            // for each supply order
            $keys = [
                'id_product', 'id_product_attribute', 'reference', 'supplier_reference', 'ean13', 'upc', 'name',
                'unit_price_te', 'quantity_expected', 'quantity_received', 'price_te', 'discount_rate', 'discount_value_te',
                'price_with_discount_te', 'tax_rate', 'tax_value', 'price_ti', 'tax_value_with_order_discount',
                'price_with_order_discount_te', 'id_supply_order',
            ];
            echo sprintf("%s\n", implode(';', array_map(['CSVCore', 'wrap'], $keys)));

            // overrides keys (in order to add FORMAT calls)
            $keys = [
                'sod.id_product', 'sod.id_product_attribute', 'sod.reference', 'sod.supplier_reference', 'sod.ean13', 'sod.upc', 'sod.name',
                'sod.unit_price_te', 'sod.quantity_expected',
                'sod.quantity_received', 'sod.price_te', 'sod.discount_rate', 'sod.discount_value_te', 'sod.price_with_discount_te',
                'sod.tax_rate', 'sod.tax_value', 'sod.price_ti',
                'sod.tax_value_with_order_discount',
                'sod.price_with_order_discount_te', 'sod.id_supply_order',
            ];
            foreach ($ids as $id) {
                $query = new DbQuery();
                $query->select(implode(', ', $keys));
                $query->from('supply_order_detail', 'sod');
                $query->leftJoin('supply_order', 'so', 'so.id_supply_order = sod.id_supply_order');
                $idWarehouse = $this->getCurrentWarehouse();
                if ($idWarehouse != -1) {
                    $query->where('so.id_warehouse = '.(int) $idWarehouse);
                }
                $query->where('sod.id_supply_order = '.(int) $id);
                $query->orderBy('sod.id_supply_order_detail DESC');
                $resource = Db::getInstance()->query($query);
                // gets details
                while ($row = Db::getInstance()->nextRow($resource)) {
                    echo sprintf("%s\n", implode(';', array_map(['CSVCore', 'wrap'], $row)));
                }
            }
        } elseif (Tools::isSubmit('csv_order_details') && Tools::getValue('id_supply_order')) {
            // exports details for the given order
            $supplyOrder = new SupplyOrder((int) Tools::getValue('id_supply_order'));
            if (Validate::isLoadedObject($supplyOrder)) {
                $details = $supplyOrder->getEntriesCollection();
                $details->getAll();
                $csv = new CSV($details, $this->l('supply_order').'_'.$supplyOrder->reference.'_details');
                $csv->export();
            }
        }
    }

    /**
     * AdminController::initContent() override
     *
     * @see AdminController::initContent()
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            $this->warnings[md5('PS_ADVANCED_STOCK_MANAGEMENT')] =
                $this->l('You need to activate the Advanced Stock Management feature prior to using this feature.');

            return false;
        }
        // Manage the add stock form
        if (Tools::isSubmit('changestate')) {
            $this->initChangeStateContent();
        } elseif (Tools::isSubmit('update_receipt') && Tools::isSubmit('id_supply_order') && !Tools::isSubmit('detailssupply_order_detail')) {
            $this->initUpdateReceiptContent();
        } elseif (Tools::isSubmit('viewsupply_order') && Tools::isSubmit('id_supply_order')) {
            $this->action = 'view';
            $this->display = 'view';
            parent::initContent();
        } elseif (Tools::isSubmit('updatesupply_order')) {
            $this->initUpdateSupplyOrderContent();
        } else {
            if (Tools::isSubmit('detailssupply_order_detail')) {
                $this->action = 'details';
                $this->display = 'details';
            }
            parent::initContent();
        }
    }

    /**
     * Init the content of change state action
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initChangeStateContent()
    {
        $idSupplyOrder = (int) Tools::getValue('id_supply_order', 0);

        if ($idSupplyOrder <= 0) {
            $this->errors[] = Tools::displayError('The specified supply order is not valid');

            parent::initContent();

            return;
        }

        $supplyOrder = new SupplyOrder($idSupplyOrder);
        $supplyOrderState = new SupplyOrderState($supplyOrder->id_supply_order_state);

        if (!Validate::isLoadedObject($supplyOrder) || !Validate::isLoadedObject($supplyOrderState)) {
            $this->errors[] = Tools::displayError('The specified supply order is not valid');

            parent::initContent();

            return;
        }

        // change the display type in order to add specific actions to
        $this->display = 'update_order_state';
        // overrides parent::initContent();
        $this->initToolbar();
        $this->initPageHeaderToolbar();

        // given the current state, loads available states
        $states = SupplyOrderState::getSupplyOrderStates($supplyOrder->id_supply_order_state);

        // gets the state that are not allowed
        $allowedStates = [];
        foreach ($states as &$state) {
            $allowedStates[] = $state['id_supply_order_state'];
            $state['allowed'] = 1;
        }
        $notAllowedStates = SupplyOrderState::getStates($allowedStates);

        // generates the final list of states
        $index = count($allowedStates);
        foreach ($notAllowedStates as &$notAllowedState) {
            $notAllowedState['allowed'] = 0;
            $states[$index] = $notAllowedState;
            ++$index;
        }

        // loads languages
        $this->getlanguages();

        // defines the fields of the form to display
        $this->fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Supply order status'),
                'icon'  => 'icon-pencil',
            ],
            'input'  => [],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->displayInformation($this->l('Be careful when changing status. Some of those changes cannot be canceled. '));

        // sets up the helper
        $helper = new HelperForm();
        $helper->submit_action = 'submitChangestate';
        $helper->currentIndex = static::$currentIndex;
        $helper->toolbar_btn = $this->toolbar_btn;
        $helper->toolbar_scroll = false;
        $helper->token = $this->token;
        $helper->id = null; // no display standard hidden field in the form
        $helper->languages = $this->_languages;
        $helper->default_form_language = $this->default_form_language;
        $helper->allow_employee_form_lang = $this->allow_employee_form_lang;
        $helper->title = sprintf($this->l('Stock: Change supply order status #%s'), $supplyOrder->reference);
        $helper->show_cancel_button = true;
        $helper->override_folder = 'supply_orders_change_state/';

        // assigns our content
        $helper->tpl_vars['show_change_state_form'] = true;
        $helper->tpl_vars['supply_order_state'] = $supplyOrderState;
        $helper->tpl_vars['supply_order'] = $supplyOrder;
        $helper->tpl_vars['supply_order_states'] = $states;

        // generates the form to display
        $content = $helper->generateForm($this->fields_form);

        $this->context->smarty->assign(
            [
                'content'                   => $content,
                'url_post'                  => static::$currentIndex.'&token='.$this->token,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * @return void
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if ($this->display == 'details') {
            $this->page_header_toolbar_btn['back'] = [
                'href' => $this->context->link->getAdminLink('AdminSupplyOrders'),
                'desc' => $this->l('Back to list', null, null, false),
                'icon' => 'process-icon-back',
            ];
        } elseif (empty($this->display)) {
            $this->page_header_toolbar_btn['new_supply_order'] = [
                'href' => static::$currentIndex.'&addsupply_order&token='.$this->token,
                'desc' => $this->l('Add new supply order', null, null, false),
                'icon' => 'process-icon-new',
            ];
            $this->page_header_toolbar_btn['new_supply_order_template'] = [
                'href' => static::$currentIndex.'&addsupply_order&mod=template&token='.$this->token,
                'desc' => $this->l('Add new supply order template', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Inits the content of 'update_receipt' action
     * Called in initContent()
     *
     * @since 1.0.0
     */
    public function initUpdateReceiptContent()
    {
        $idSupplyOrder = (int) Tools::getValue('id_supply_order', null);

        // if there is no order to fetch
        if (null == $idSupplyOrder) {
            parent::initContent();

            return;
        }

        $supplyOrder = new SupplyOrder($idSupplyOrder);

        // if it's not a valid order
        if (!Validate::isLoadedObject($supplyOrder)) {
            parent::initContent();

            return;
        }

        $this->initPageHeaderToolbar();

        // re-defines fields_list
        $this->fields_list = [
            'supplier_reference'      => [
                'title'   => $this->l('Supplier reference'),
                'orderby' => false,
                'filter'  => false,
                'search'  => false,
            ],
            'reference'               => [
                'title'   => $this->l('Reference'),
                'orderby' => false,
                'filter'  => false,
                'search'  => false,
            ],
            'ean13'                   => [
                'title'   => $this->l('EAN-13 or JAN barcode'),
                'orderby' => false,
                'filter'  => false,
                'search'  => false,
            ],
            'upc'                     => [
                'title'   => $this->l('UPC barcode'),
                'orderby' => false,
                'filter'  => false,
                'search'  => false,
            ],
            'name'                    => [
                'title'   => $this->l('Name'),
                'orderby' => false,
                'filter'  => false,
                'search'  => false,
            ],
            'quantity_received_today' => [
                'title'   => $this->l('Quantity received today?'),
                'type'    => 'editable',
                'orderby' => false,
                'filter'  => false,
                'search'  => false,
                'hint'    => $this->l('The quantity of supplies that you received today.'),
            ],
            'quantity_received'       => [
                'title'         => $this->l('Quantity received'),
                'orderby'       => false,
                'filter'        => false,
                'search'        => false,
                'badge_danger'  => true,
                'badge_success' => true,
                'hint'          => $this->l('The quantity of supplies that you received so far (today and the days before, if it applies).'),
            ],
            'quantity_expected'       => [
                'title'   => $this->l('Quantity expected'),
                'orderby' => false,
                'filter'  => false,
                'search'  => false,
            ],
            'quantity_left'           => [
                'title'   => $this->l('Quantity left'),
                'orderby' => false,
                'filter'  => false,
                'search'  => false,
                'hint'    => $this->l('The quantity of supplies left to receive for this order.'),
            ],
        ];

        // attributes override
        unset($this->_select, $this->_join, $this->_where, $this->_orderBy, $this->_orderWay, $this->_group, $this->_filterHaving, $this->_filter);
        $this->table = 'supply_order_detail';
        $this->identifier = 'id_supply_order_detail';
        $this->className = 'SupplyOrderDetail';
        $this->list_simple_header = false;
        $this->list_no_link = true;
        $this->colorOnBackground = true;
        $this->row_hover = false;
        $this->bulk_actions = ['Update' => ['text' => $this->l('Update selected'), 'confirm' => $this->l('Update selected items?')]];
        $this->addRowAction('details');

        // sets toolbar title with order reference
        $this->toolbar_title = sprintf($this->l('Receipt of products for supply order #%s'), $supplyOrder->reference);

        $this->lang = false;
        $idLang = (int) $this->context->language->id; //employee lang

        // gets values corresponding to fields_list
        $this->_select = '
			a.id_supply_order_detail as id,
			a.quantity_received as quantity_received,
			a.quantity_expected as quantity_expected,
			IF (a.quantity_expected < a.quantity_received, 0, a.quantity_expected - a.quantity_received) as quantity_left,
			IF (a.quantity_expected < a.quantity_received, 0, a.quantity_expected - a.quantity_received) as quantity_received_today,
			IF (a.quantity_expected = a.quantity_received, 1, 0) badge_success,
			IF (a.quantity_expected > a.quantity_received, 1, 0) badge_danger';

        $this->_where = 'AND a.`id_supply_order` = '.(int) $idSupplyOrder;

        $this->_group = 'GROUP BY a.id_supply_order_detail';

        // gets the list ordered by price desc, without limit
        $this->getList($idLang, 'quantity_expected', 'DESC', 0, Tools::getValue('supply_order_pagination'), false);

        // defines action for POST
        $action = '&id_supply_order='.$idSupplyOrder.'&update_receipt=1';

        // unsets some buttons
        unset($this->toolbar_btn['export-csv-orders']);
        unset($this->toolbar_btn['export-csv-details']);
        unset($this->toolbar_btn['new']);

        $this->toolbar_btn['back'] = [
            'desc' => $this->l('Back'),
            'href' => $this->context->link->getAdminLink('AdminSupplyOrders'),
        ];

        // renders list
        $helper = new HelperList();
        $this->setHelperDisplay($helper);
        $helper->actions = ['details'];
        $helper->force_show_bulk_actions = true;
        $helper->override_folder = 'supply_orders_receipt_history/';
        $helper->toolbar_btn = $this->toolbar_btn;
        $helper->list_id = 'supply_order_detail';

        $helper->ajax_params = [
            'display_product_history' => 1,
        ];

        $helper->currentIndex = static::$currentIndex.$action;

        // display these global order informations
        $this->displayInformation($this->l('This interface allows you to update the quantities of this ongoing order.').'<br />');
        $this->displayInformation($this->l('Be careful! Once you update, you cannot go back unless you add new negative stock movements.').'<br />');
        $this->displayInformation($this->l('A green line means that you\'ve received exactly the quantity you expected. A red line means that you\'ve received more than expected.').'<br />');

        // generates content
        $content = $helper->generateList($this->_list, $this->fields_list);

        // assigns var
        $this->context->smarty->assign(
            [
                'content'                   => $content,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]
        );
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
     * @since 1.0.0
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        if (Tools::isSubmit('csv_orders') || Tools::isSubmit('csv_orders_details') || Tools::isSubmit('csv_order_details')) {
            $limit = false;
        }

        // defines button specific for non-template supply orders
        if (!$this->is_template_list && $this->display != 'details') {
            // adds export csv buttons
            $this->toolbar_btn['export-csv-orders'] = [
                'short' => 'Export Orders',
                'href'  => $this->context->link->getAdminLink('AdminSupplyOrders').'&csv_orders&id_warehouse='.$this->getCurrentWarehouse(),
                'desc'  => $this->l('Export Orders (CSV)'),
                'class' => 'process-icon-export',
            ];

            $this->toolbar_btn['export-csv-details'] = [
                'short' => 'Export Orders Details',
                'href'  => $this->context->link->getAdminLink('AdminSupplyOrders').'&csv_orders_details&id_warehouse='.$this->getCurrentWarehouse(),
                'desc'  => $this->l('Export Orders Details (CSV)'),
                'class' => 'process-icon-export',
            ];

            unset($this->toolbar_btn['new']);
            if ($this->tabAccess['add'] === '1') {
                $this->toolbar_btn['new'] = [
                    'href' => static::$currentIndex.'&add'.$this->table.'&token='.$this->token,
                    'desc' => $this->l('Add New'),
                ];
            }
        }

        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        // adds colors depending on the receipt state
        if ($orderBy == 'quantity_expected') {
            $nbItems = count($this->_list);
            for ($i = 0; $i < $nbItems; ++$i) {
                $item = &$this->_list[$i];
                if ($item['quantity_received'] == $item['quantity_expected']) {
                    $item['color'] = '#00bb35';
                } elseif ($item['quantity_received'] > $item['quantity_expected']) {
                    $item['color'] = '#fb0008';
                }
            }
        }

        // actions filters on supply orders list
        if ($this->table == 'supply_order') {
            $nbItems = count($this->_list);

            for ($i = 0; $i < $nbItems; $i++) {
                // if the current state doesn't allow order edit, skip the edit action
                if ($this->_list[$i]['editable'] == 0) {
                    $this->addRowActionSkipList('edit', $this->_list[$i]['id_supply_order']);
                }
                if ($this->_list[$i]['enclosed'] == 1 && $this->_list[$i]['receipt_state'] == 0) {
                    $this->addRowActionSkipList('changestate', $this->_list[$i]['id_supply_order']);
                }
                if (1 != $this->_list[$i]['pending_receipt']) {
                    $this->addRowActionSkipList('updatereceipt', $this->_list[$i]['id_supply_order']);
                }
            }
        }
    }

    /**
     * Init the content of change state action
     *
     * @since 1.0.0
     */
    public function initUpdateSupplyOrderContent()
    {
        $this->addJqueryPlugin('autocomplete');

        // load supply order
        $idSupplyOrder = (int) Tools::getValue('id_supply_order', null);

        if ($idSupplyOrder != null) {
            $supplyOrder = new SupplyOrder($idSupplyOrder);

            $currency = new Currency($supplyOrder->id_currency);

            if (Validate::isLoadedObject($supplyOrder)) {
                // load products of this order
                $products = $supplyOrder->getEntries();
                $productIds = [];

                if (isset($this->order_products_errors) && is_array($this->order_products_errors)) {
                    //for each product in error array, check if it is in products array, and remove it to conserve last user values
                    foreach ($this->order_products_errors as $pe) {
                        foreach ($products as $indexP => $p) {
                            if (($p['id_product'] == $pe['id_product']) && ($p['id_product_attribute'] == $pe['id_product_attribute'])) {
                                unset($products[$indexP]);
                            }
                        }
                    }

                    // then merge arrays
                    $products = array_merge($this->order_products_errors, $products);
                }

                foreach ($products as &$item) {
                    // calculate md5 checksum on each product for use in tpl
                    $item['checksum'] = md5(_COOKIE_KEY_.$item['id_product'].'_'.$item['id_product_attribute']);
                    $item['unit_price_te'] = priceval($item['unit_price_te']);

                    // add id to ids list
                    $productIds[] = $item['id_product'].'_'.$item['id_product_attribute'];
                }

                $this->tpl_form_vars['products_list'] = $products;
                $this->tpl_form_vars['product_ids'] = implode($productIds, '|');
                $this->tpl_form_vars['product_ids_to_delete'] = '';
                $this->tpl_form_vars['supplier_id'] = $supplyOrder->id_supplier;
                $this->tpl_form_vars['currency'] = $currency;
            }
        }

        $this->tpl_form_vars['content'] = $this->content;
        $this->tpl_form_vars['token'] = $this->token;
        $this->tpl_form_vars['show_product_management_form'] = true;

        // call parent initcontent to render standard form content
        parent::initContent();
    }

    /**
     * AdminController::postProcess() override
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        $this->is_editing_order = false;

        // Checks access
        if (Tools::isSubmit('submitAddsupply_order') && !($this->tabAccess['add'] === '1')) {
            $this->errors[] = Tools::displayError('You do not have permission to add a supply order.');
        }
        if (Tools::isSubmit('submitBulkUpdatesupply_order_detail') && !($this->tabAccess['edit'] === '1')) {
            $this->errors[] = Tools::displayError('You do not have permission to edit an order.');
        }

        // Trick to use both Supply Order as template and actual orders
        if (Tools::isSubmit('is_template')) {
            $_GET['mod'] = 'template';
        }

        // checks if supply order reference is unique
        if (Tools::isSubmit('reference')) {
            // gets the reference
            $ref = pSQL(Tools::getValue('reference'));

            if (Tools::getValue('id_supply_order') != 0 && SupplyOrder::getReferenceById((int) Tools::getValue('id_supply_order')) != $ref) {
                if ((int) SupplyOrder::exists($ref) != 0) {
                    $this->errors[] = Tools::displayError('The reference has to be unique.');
                }
            } elseif (Tools::getValue('id_supply_order') == 0 && (int) SupplyOrder::exists($ref) != 0) {
                $this->errors[] = Tools::displayError('The reference has to be unique.');
            }
        }

        if ($this->errors) {
            return;
        }

        // Global checks when add / update a supply order
        if (Tools::isSubmit('submitAddsupply_order') || Tools::isSubmit('submitAddsupply_orderAndStay')) {
            $this->action = 'save';
            $this->is_editing_order = true;

            // get supplier ID
            $idSupplier = (int) Tools::getValue('id_supplier', 0);
            if ($idSupplier <= 0 || !Supplier::supplierExists($idSupplier)) {
                $this->errors[] = Tools::displayError('The selected supplier is not valid.');
            }

            // get warehouse id
            $idWarehouse = (int) Tools::getValue('id_warehouse', 0);
            if ($idWarehouse <= 0 || !Warehouse::exists($idWarehouse)) {
                $this->errors[] = Tools::displayError('The selected warehouse is not valid.');
            }

            // get currency id
            $idCurrency = (int) Tools::getValue('id_currency', 0);
            if ($idCurrency <= 0 || (!($result = Currency::getCurrency($idCurrency)) || empty($result))) {
                $this->errors[] = Tools::displayError('The selected currency is not valid.');
            }

            // get delivery date
            if (Tools::getValue('mod') != 'template' && strtotime(Tools::getValue('date_delivery_expected')) <= strtotime('-1 day')) {
                $this->errors[] = Tools::displayError('The specified date cannot be in the past.');
            }

            // gets threshold
            $quantityThreshold = Tools::getValue('load_products');

            if (is_numeric($quantityThreshold)) {
                $quantityThreshold = (int) $quantityThreshold;
            } else {
                $quantityThreshold = null;
            }

            if (!count($this->errors)) {
                // forces date for templates
                if (Tools::isSubmit('is_template') && !Tools::getValue('date_delivery_expected')) {
                    $_POST['date_delivery_expected'] = date('Y-m-d h:i:s');
                }

                // specify initial state
                $_POST['id_supply_order_state'] = 1; //defaut creation state

                // specify global reference currency
                $_POST['id_ref_currency'] = Currency::getDefaultCurrency()->id;

                // specify supplier name
                $_POST['supplier_name'] = Supplier::getNameById($idSupplier);

                //specific discount check
                $_POST['discount_rate'] = priceval(
                    Tools::getValue('discount_rate', 0)
                );
            }

            // manage each associated product
            $this->manageOrderProducts();

            // if the threshold is defined and we are saving the order
            if (Tools::isSubmit('submitAddsupply_order') && Validate::isInt($quantityThreshold)) {
                $this->loadProducts((int) $quantityThreshold);
            }
        }

        // Manage state change
        if (Tools::isSubmit('submitChangestate')
            && Tools::isSubmit('id_supply_order')
            && Tools::isSubmit('id_supply_order_state')
        ) {
            if ($this->tabAccess['edit'] != '1') {
                $this->errors[] = Tools::displayError('You do not have permission to change the order status.');
            }

            // get state ID
            $idState = (int) Tools::getValue('id_supply_order_state', 0);
            if ($idState <= 0) {
                $this->errors[] = Tools::displayError('The selected supply order status is not valid.');
            }

            // get supply order ID
            $idSupplyOrder = (int) Tools::getValue('id_supply_order', 0);
            if ($idSupplyOrder <= 0) {
                $this->errors[] = Tools::displayError('The supply order ID is not valid.');
            }

            if (!count($this->errors)) {
                // try to load supply order
                $supplyOrder = new SupplyOrder($idSupplyOrder);

                if (Validate::isLoadedObject($supplyOrder)) {
                    // get valid available possible states for this order
                    $states = SupplyOrderState::getSupplyOrderStates($supplyOrder->id_supply_order_state);

                    foreach ($states as $state) {
                        // if state is valid, change it in the order
                        if ($idState == $state['id_supply_order_state']) {
                            $newState = new SupplyOrderState($idState);
                            $oldState = new SupplyOrderState($supplyOrder->id_supply_order_state);

                            // special case of validate state - check if there are products in the order and the required state is not an enclosed state
                            if ($supplyOrder->isEditable() && !$supplyOrder->hasEntries() && !$newState->enclosed) {
                                $this->errors[] = Tools::displayError('It is not possible to change the status of this order because you did not order any products.');
                            }

                            if (!count($this->errors)) {
                                $supplyOrder->id_supply_order_state = $state['id_supply_order_state'];
                                if ($supplyOrder->save()) {
                                    // Create stock entry if not exists when order is in pending_receipt
                                    if ($newState->pending_receipt) {
                                        $supplyOrderDetails = $supplyOrder->getEntries();
                                        foreach ($supplyOrderDetails as $supplyOrderDetail) {
                                            $isPresent = Stock::productIsPresentInStock($supplyOrderDetail['id_product'], $supplyOrderDetail['id_product_attribute'], $supplyOrder->id_warehouse);
                                            if (!$isPresent) {
                                                $stock = new Stock();

                                                $stockParams = [
                                                    'id_product_attribute' => $supplyOrderDetail['id_product_attribute'],
                                                    'id_product'           => $supplyOrderDetail['id_product'],
                                                    'physical_quantity'    => 0,
                                                    'price_te'             => $supplyOrderDetail['price_te'],
                                                    'usable_quantity'      => 0,
                                                    'id_warehouse'         => $supplyOrder->id_warehouse,
                                                ];

                                                // saves stock in warehouse
                                                $stock->hydrate($stockParams);
                                                $stock->add();
                                            }
                                        }
                                    }

                                    // add stock when is received completely
                                    if ($newState->receipt_state && $newState->enclosed) {
                                        $supplyOrderDetails = $supplyOrder->getEntries();

                                        $warehouse = new Warehouse($supplyOrder->id_warehouse);
                                        $idWarehouse = $warehouse->id;

                                        $stockManager = StockManagerFactory::getManager();

                                        foreach ($supplyOrderDetails as $detail) {
                                            $idProduct = $detail['id_product'];
                                            $idProductAttribute = $detail['id_product_attribute'];

                                            if ($stockManager->addProduct(
                                                $detail['id_product'],
                                                $detail['id_product_attribute'],
                                                $warehouse,
                                                (int) ($detail['quantity_expected'] - $detail['quantity_received']),
                                                Configuration::get('PS_STOCK_MVT_SUPPLY_ORDER'),
                                                $detail['unit_price_te'],
                                                true,
                                                $supplyOrder->id
                                            )
                                            ) {
                                                // Create warehouse_product_location entry if we add stock to a new warehouse
                                                $idWpl = (int) WarehouseProductLocation::getIdByProductAndWarehouse($idProduct, $idProductAttribute, $idWarehouse);
                                                if (!$idWpl) {
                                                    $wpl = new WarehouseProductLocation();
                                                    $wpl->id_product = (int) $idProduct;
                                                    $wpl->id_product_attribute = (int) $idProductAttribute;
                                                    $wpl->id_warehouse = (int) $idWarehouse;
                                                    $wpl->save();
                                                }

                                                $supplyOrderDetailMvt = new SupplyOrderDetail($detail['id_supply_order_detail']);
                                                $supplyOrderDetailMvtParams = [
                                                    'quantity_received' => (int) $detail['quantity_expected'],
                                                ];
                                                // saves supply order detail
                                                $supplyOrderDetailMvt->hydrate($supplyOrderDetailMvtParams);
                                                $supplyOrderDetailMvt->update();

                                            } else {
                                                $this->errors[] = $this->l('An error occurred. No stock was added.');
                                            }
                                        }
                                    }

                                    // if pending_receipt,
                                    // or if the order is being canceled,
                                    // or if the order is received completely
                                    // synchronizes StockAvailable
                                    if (($newState->pending_receipt && !$newState->receipt_state) ||
                                        (($oldState->receipt_state || $oldState->pending_receipt) && $newState->enclosed && !$newState->receipt_state) ||
                                        ($newState->receipt_state && $newState->enclosed)
                                    ) {
                                        $supplyOrderDetails = $supplyOrder->getEntries();
                                        $productsDone = [];
                                        foreach ($supplyOrderDetails as $supplyOrderDetail) {
                                            if (!in_array($supplyOrderDetail['id_product'], $productsDone)) {
                                                StockAvailable::synchronize($supplyOrderDetail['id_product']);
                                                $productsDone[] = $supplyOrderDetail['id_product'];
                                            }
                                        }
                                    }

                                    $token = Tools::getValue('token') ? Tools::getValue('token') : $this->token;
                                    $redirect = static::$currentIndex.'&token='.$token;
                                    $this->redirect_after = $redirect.'&conf=5';
                                }
                            }
                        }
                    }
                } else {
                    $this->errors[] = Tools::displayError('The selected supplier is not valid.');
                }
            }
        }

        // updates receipt
        if (Tools::isSubmit('submitBulkUpdatesupply_order_detail') && Tools::isSubmit('id_supply_order')) {
            $this->postProcessUpdateReceipt();
        }

        // use template to create a supply order
        if (Tools::isSubmit('create_supply_order') && Tools::isSubmit('id_supply_order')) {
            $this->postProcessCopyFromTemplate();
        }

        if ((!count($this->errors) && $this->is_editing_order) || !$this->is_editing_order) {
            parent::postProcess();
        }
    }

    /**
     * Ths method manage associated products to the order when updating it
     *
     * @since 1.0.0
     */
    public function manageOrderProducts()
    {
        // load supply order
        $idSupplyOrder = (int) Tools::getValue('id_supply_order', null);

        if ($idSupplyOrder != null) {
            $supplyOrder = new SupplyOrder($idSupplyOrder);

            if (Validate::isLoadedObject($supplyOrder)) {
                // tests if the supplier or currency have changed in the supply order
                $newSupplierId = (int) Tools::getValue('id_supplier');
                $newCurrencyId = (int) Tools::getValue('id_currency');

                if (($newSupplierId != $supplyOrder->id_supplier) ||
                    ($newCurrencyId != $supplyOrder->id_currency)
                ) {
                    // resets all products in this order
                    $supplyOrder->resetProducts();
                } else {
                    $productsAlreadyInOrder = $supplyOrder->getEntries();
                    $currency = new Currency($supplyOrder->id_ref_currency);

                    // gets all product ids to manage
                    $productIdsStr = Tools::getValue('product_ids', null);
                    $productIds = explode('|', $productIdsStr);
                    $productIdsToDeleteStr = Tools::getValue('product_ids_to_delete', null);
                    $productIdsToDelete = array_unique(explode('|', $productIdsToDeleteStr));

                    //delete products that are not managed anymore
                    foreach ($productsAlreadyInOrder as $paio) {
                        $productOk = false;

                        foreach ($productIdsToDelete as $id) {
                            $idCheck = $paio['id_product'].'_'.$paio['id_product_attribute'];
                            if ($idCheck == $id) {
                                $productOk = true;
                            }
                        }

                        if ($productOk === true) {
                            $entry = new SupplyOrderDetail($paio['id_supply_order_detail']);
                            $entry->delete();
                        }
                    }

                    // manage each product
                    foreach ($productIds as $id) {
                        // check if a checksum is available for this product and test it
                        $check = Tools::getValue('input_check_'.$id, '');
                        $checkValid = md5(_COOKIE_KEY_.$id);

                        if ($checkValid != $check) {
                            continue;
                        }

                        $pos = strpos($id, '_');
                        if ($pos === false) {
                            continue;
                        }

                        // Load / Create supply order detail
                        $entry = new SupplyOrderDetail();
                        $idSupplyOrderDetail = (int) Tools::getValue('input_id_'.$id, 0);
                        if ($idSupplyOrderDetail > 0) {
                            $existingEntry = new SupplyOrderDetail($idSupplyOrderDetail);
                            if (Validate::isLoadedObject($supplyOrder)) {
                                $entry = &$existingEntry;
                            }
                        }

                        // get product informations
                        $entry->id_product = substr($id, 0, $pos);
                        $entry->id_product_attribute = substr($id, $pos + 1);
                        $entry->unit_price_te = priceval(
                            Tools::getValue('input_unit_price_te_'.$id, 0)
                        );
                        $entry->quantity_expected = (int) Tools::getValue('input_quantity_expected_'.$id, 0);
                        $entry->discount_rate = Tools::getValue('input_discount_rate_'.$id, 0);
                        $entry->tax_rate = (float) Tools::getValue('input_tax_rate_'.$id, 0);
                        $entry->reference = Tools::getValue('input_reference_'.$id, '');
                        $entry->supplier_reference = Tools::getValue('input_supplier_reference_'.$id, '');
                        $entry->ean13 = Tools::getValue('input_ean13_'.$id, '');
                        $entry->upc = Tools::getValue('input_upc_'.$id, '');

                        //get the product name in the order language
                        $entry->name = Product::getProductName($entry->id_product, $entry->id_product_attribute, $supplyOrder->id_lang);

                        if (empty($entry->name)) {
                            $entry->name = '';
                        }

                        if ($entry->supplier_reference == null) {
                            $entry->supplier_reference = '';
                        }

                        $entry->exchange_rate = $currency->conversion_rate;
                        $entry->id_currency = $currency->id;
                        $entry->id_supply_order = $supplyOrder->id;

                        $errors = $entry->validateController();

                        //get the product name displayed in the backoffice according to the employee language
                        $entry->name_displayed = Tools::getValue('input_name_displayed_'.$id, '');

                        // if there is a problem, handle error for the current product
                        if (count($errors) > 0) {
                            // add the product to error array => display again product line
                            $this->order_products_errors[] = [
                                'id_product'           => $entry->id_product,
                                'id_product_attribute' => $entry->id_product_attribute,
                                'unit_price_te'        => $entry->unit_price_te,
                                'quantity_expected'    => $entry->quantity_expected,
                                'discount_rate'        => $entry->discount_rate,
                                'tax_rate'             => $entry->tax_rate,
                                'name'                 => $entry->name,
                                'name_displayed'       => $entry->name_displayed,
                                'reference'            => $entry->reference,
                                'supplier_reference'   => $entry->supplier_reference,
                                'ean13'                => $entry->ean13,
                                'upc'                  => $entry->upc,
                            ];

                            $errorStr = '<ul>';
                            foreach ($errors as $e) {
                                $errorStr .= '<li>'.sprintf($this->l('Field: %s'), $e).'</li>';
                            }
                            $errorStr .= '</ul>';

                            $this->errors[] = sprintf(Tools::displayError('Please verify the product information for "%s":'), $entry->name).' '.$errorStr;
                        } else {
                            $entry->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * Loads products which quantity (hysical quantity) is equal or less than $threshold
     *
     * @param int $threshold
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function loadProducts($threshold)
    {
        // if there is already an order
        if (Tools::getValue('id_supply_order')) {
            $supplyOrder = new SupplyOrder((int) Tools::getValue('id_supply_order'));
        } else { // else, we just created a new order
            $supplyOrder = $this->object;
        }

        // if order is not valid, return;
        if (!Validate::isLoadedObject($supplyOrder)) {
            return;
        }

        // resets products if needed
        if (Tools::getValue('id_supply_order')) {
            $supplyOrder->resetProducts();
        }

        // gets products
        $query = new DbQuery();
        $query->select(
            'ps.`id_product`,
			 ps.`id_product_attribute`,
			 ps.`product_supplier_reference` as `supplier_reference`,
			 ps.`product_supplier_price_te` as `unit_price_te`,
			 ps.`id_currency`,
			 IFNULL(pa.`reference`, IFNULL(p.`reference`, \'\')) as `reference`,
			 IFNULL(pa.`ean13`, IFNULL(p.`ean13`, \'\')) as `ean13`,
			 IFNULL(pa.`upc`, IFNULL(p.`upc`, \'\')) as `upc`'
        );
        $query->from('product_supplier', 'ps');
        $query->innerJoin('warehouse_product_location', 'wpl', 'wpl.`id_product` = ps.`id_product` AND wpl.`id_product_attribute` = ps.`id_product_attribute` AND wpl.`id_warehouse` = '.(int) $supplyOrder->id_warehouse.'');
        $query->leftJoin('product', 'p', 'p.`id_product` = ps.`id_product`');
        $query->leftJoin('product_attribute', 'pa', 'pa.`id_product_attribute` = ps.`id_product_attribute` AND p.`id_product` = ps.`id_product`');
        $query->where('ps.`id_supplier` = '.(int) $supplyOrder->id_supplier);

        // gets items
        $items = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        // loads order currency
        $orderCurrency = new Currency($supplyOrder->id_currency);
        if (!Validate::isLoadedObject($orderCurrency)) {
            return;
        }

        $manager = StockManagerFactory::getManager();
        foreach ($items as $item) {
            $diff = (int) $threshold;

            if ($supplyOrder->is_template != 1) {
                $realQuantity = (int) $manager->getProductRealQuantities(
                    $item['id_product'],
                    $item['id_product_attribute'],
                    $supplyOrder->id_warehouse,
                    true
                );
                $diff = (int) $threshold - (int) $realQuantity;
            }

            if ($diff >= 0) {
                // sets supply_order_detail
                $supplyOrderDetail = new SupplyOrderDetail();
                $supplyOrderDetail->id_supply_order = $supplyOrder->id;
                $supplyOrderDetail->id_currency = $orderCurrency->id;
                $supplyOrderDetail->id_product = $item['id_product'];
                $supplyOrderDetail->id_product_attribute = $item['id_product_attribute'];
                $supplyOrderDetail->reference = $item['reference'];
                $supplyOrderDetail->supplier_reference = $item['supplier_reference'];
                $supplyOrderDetail->name = Product::getProductName($item['id_product'], $item['id_product_attribute'], $supplyOrder->id_lang);
                $supplyOrderDetail->ean13 = $item['ean13'];
                $supplyOrderDetail->upc = $item['upc'];
                $supplyOrderDetail->quantity_expected = ((int) $diff == 0) ? 1 : (int) $diff;
                $supplyOrderDetail->exchange_rate = $orderCurrency->conversion_rate;

                $productCurrency = new Currency($item['id_currency']);
                if (Validate::isLoadedObject($productCurrency)) {
                    $supplyOrderDetail->unit_price_te = Tools::convertPriceFull($item['unit_price_te'], $productCurrency, $orderCurrency);
                } else {
                    $supplyOrderDetail->unit_price_te = 0;
                }
                $supplyOrderDetail->save();
                unset($productCurrency);
            }
        }

        // updates supply order
        $supplyOrder->update();
    }

    /**
     * Helper function for AdminSupplyOrdersController::postProcess()
     *
     * @since 1.0.0
     */
    protected function postProcessUpdateReceipt()
    {
        // gets all box selected
        $rows = Tools::getValue('supply_order_detailBox');
        if (!$rows) {
            $this->errors[] = Tools::displayError('You did not select any products to update.');

            return;
        }

        // final array with id_supply_order_detail and value to update
        $toUpdate = [];
        // gets quantity for each id_order_detail
        foreach ($rows as $row) {
            if (Tools::getValue('quantity_received_today_'.$row)) {
                $toUpdate[$row] = (int) Tools::getValue('quantity_received_today_'.$row);
            }
        }

        // checks if there is something to update
        if (!count($toUpdate)) {
            $this->errors[] = Tools::displayError('You did not select any products to update.');

            return;
        }

        $supplyOrder = new SupplyOrder((int) Tools::getValue('id_supply_order'));

        foreach ($toUpdate as $idSupplyOrderDetail => $quantity) {
            $supplyOrderDetail = new SupplyOrderDetail($idSupplyOrderDetail);

            if (Validate::isLoadedObject($supplyOrderDetail) && Validate::isLoadedObject($supplyOrder)) {
                // checks if quantity is valid
                // It's possible to receive more quantity than expected in case of a shipping error from the supplier
                if (!Validate::isInt($quantity) || $quantity <= 0) {
                    $this->errors[] = sprintf(
                        Tools::displayError('Quantity (%d) for product #%d is not valid'),
                        (int) $quantity,
                        (int) $idSupplyOrderDetail
                    );
                } else {
                    // everything is valid: update
                    // creates the history
                    $supplierReceiptHistory = new SupplyOrderReceiptHistory();
                    $supplierReceiptHistory->id_supply_order_detail = (int) $idSupplyOrderDetail;
                    $supplierReceiptHistory->id_employee = (int) $this->context->employee->id;
                    $supplierReceiptHistory->employee_firstname = pSQL($this->context->employee->firstname);
                    $supplierReceiptHistory->employee_lastname = pSQL($this->context->employee->lastname);
                    $supplierReceiptHistory->id_supply_order_state = (int) $supplyOrder->id_supply_order_state;
                    $supplierReceiptHistory->quantity = (int) $quantity;

                    // updates quantity received
                    $supplyOrderDetail->quantity_received += (int) $quantity;

                    // if current state is "Pending receipt", then we sets it to "Order received in part"
                    if (3 == $supplyOrder->id_supply_order_state) {
                        $supplyOrder->id_supply_order_state = 4;
                    }

                    // Adds to stock
                    $warehouse = new Warehouse($supplyOrder->id_warehouse);
                    if (!Validate::isLoadedObject($warehouse)) {
                        $this->errors[] = Tools::displayError('The warehouse could not be loaded.');

                        return;
                    }

                    $price = $supplyOrderDetail->unit_price_te;
                    // converts the unit price to the warehouse currency if needed
                    if ($supplyOrder->id_currency != $warehouse->id_currency) {
                        // first, converts the price to the default currency
                        $priceConvertedToDefaultCurrency = Tools::convertPrice(
                            $supplyOrderDetail->unit_price_te,
                            $supplyOrder->id_currency,
                            false
                        );

                        // then, converts the newly calculated pri-ce from the default currency to the needed currency
                        $price = Tools::convertPrice(
                            $priceConvertedToDefaultCurrency,
                            $warehouse->id_currency,
                            true
                        );
                    }

                    $manager = StockManagerFactory::getManager();
                    $manager->addProduct(
                        $supplyOrderDetail->id_product,
                        $supplyOrderDetail->id_product_attribute,
                        $warehouse,
                        (int) $quantity,
                        Configuration::get('PS_STOCK_MVT_SUPPLY_ORDER'),
                        $price,
                        true,
                        $supplyOrder->id
                    );

                    $location = Warehouse::getProductLocation(
                        $supplyOrderDetail->id_product,
                        $supplyOrderDetail->id_product_attribute,
                        $warehouse->id
                    );

                    $res = Warehouse::setProductlocation(
                        $supplyOrderDetail->id_product,
                        $supplyOrderDetail->id_product_attribute,
                        $warehouse->id,
                        $location ? $location : ''
                    );

                    if ($res) {
                        $supplierReceiptHistory->add();
                        $supplyOrderDetail->save();
                        StockAvailable::synchronize($supplyOrderDetail->id_product);
                    } else {
                        $this->errors[] = Tools::displayError('Something went wrong when setting warehouse on product record');
                    }
                }
            }
        }

        $supplyOrder->id_supply_order_state = ($supplyOrder->id_supply_order_state == 4 && $supplyOrder->getAllPendingQuantity() > 0) ? 4 : 5;
        $supplyOrder->save();

        if (!count($this->errors)) {
            // display confirm message
            $token = Tools::getValue('token') ? Tools::getValue('token') : $this->token;
            $redirect = static::$currentIndex.'&token='.$token;
            $this->redirect_after = $redirect.'&conf=4';
        }
    }

    /**
     * Helper function for AdminSupplyOrdersController::postProcess()
     *
     * @since 1.0.0
     */
    protected function postProcessCopyFromTemplate()
    {
        // gets SupplyOrder and checks if it is valid
        $idSupplyOrder = (int) Tools::getValue('id_supply_order');
        $supplyOrder = new SupplyOrder($idSupplyOrder);
        if (!Validate::isLoadedObject($supplyOrder)) {
            $this->errors[] = Tools::displayError('This template could not be copied.');
        }

        // gets SupplyOrderDetail
        $entries = $supplyOrder->getEntriesCollection();

        // updates SupplyOrder so that it is not a template anymore
        $language = new Language($supplyOrder->id_lang);
        $ref = $supplyOrder->reference;
        $ref .= ' ('.date($language->date_format_full).')';
        $supplyOrder->reference = $ref;
        $supplyOrder->is_template = 0;
        $supplyOrder->id = (int) 0;
        $supplyOrder->save();

        // copies SupplyOrderDetail
        foreach ($entries as $entry) {
            $entry->id_supply_order = $supplyOrder->id;
            $entry->id = (int) 0;
            $entry->save();
        }

        // redirect when done
        $token = Tools::getValue('token') ? Tools::getValue('token') : $this->token;
        $redirect = static::$currentIndex.'&token='.$token;
        $this->redirect_after = $redirect.'&conf=19';
    }

    /**
     * Display state action link
     *
     * @param string $token the token to add to the link
     * @param int    $id    the identifier to add to the link
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function displayUpdateReceiptLink($token = null, $id)
    {
        if (!array_key_exists('Receipt', static::$cache_lang)) {
            static::$cache_lang['Receipt'] = $this->l('Update ongoing receipt of products');
        }

        $this->context->smarty->assign(
            [
                'href'   => static::$currentIndex.'&'.$this->identifier.'='.$id.'&update_receipt&token='.($token != null ? $token : $this->token),
                'action' => static::$cache_lang['Receipt'],
            ]
        );

        return $this->context->smarty->fetch('helpers/list/list_action_supply_order_receipt.tpl');
    }

    /**
     * Display receipt action link
     *
     * @param string $token the token to add to the link
     * @param int    $id    the identifier to add to the link
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function displayChangestateLink($token = null, $id)
    {
        if (!array_key_exists('State', static::$cache_lang)) {
            static::$cache_lang['State'] = $this->l('Change status');
        }

        $this->context->smarty->assign(
            [
                'href'   => static::$currentIndex.'&'.$this->identifier.'='.$id.'&changestate&token='.($token != null ? $token : $this->token),
                'action' => static::$cache_lang['State'],
            ]
        );

        return $this->context->smarty->fetch('helpers/list/list_action_supply_order_change_state.tpl');
    }

    /**
     * Display state action link
     *
     * @param string $token the token to add to the link
     * @param int    $id    the identifier to add to the link
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function displayCreateSupplyOrderLink($token = null, $id)
    {
        if (!array_key_exists('CreateSupplyOrder', static::$cache_lang)) {
            static::$cache_lang['CreateSupplyOrder'] = $this->l('Use this template to create a supply order');
        }

        if (!array_key_exists('CreateSupplyOrderConfirm', static::$cache_lang)) {
            static::$cache_lang['CreateSupplyOrderConfirm'] = $this->l('Are you sure you want to use this template?');
        }

        $this->context->smarty->assign(
            [
                'href'    => static::$currentIndex.'&'.$this->identifier.'='.$id.'&create_supply_order&token='.($token != null ? $token : $this->token),
                'confirm' => static::$cache_lang['CreateSupplyOrderConfirm'],
                'action'  => static::$cache_lang['CreateSupplyOrder'],
            ]
        );

        return $this->context->smarty->fetch('helpers/list/list_action_supply_order_create_from_template.tpl');
    }

    /**
     * Render details
     *
     * @return false|string
     *
     * @since 1.0.0
     */
    public function renderDetails()
    {
        // tests if an id is submit
        if (Tools::isSubmit('id_supply_order') && !Tools::isSubmit('display_product_history')) {
            // overrides attributes
            $this->identifier = 'id_supply_order_history';
            $this->table = 'supply_order_history';
            $this->lang = false;
            $this->actions = [];
            $this->toolbar_btn = [];
            $this->list_simple_header = true;
            // gets current lang id
            $idLang = (int) $this->context->language->id;
            // gets supply order id
            $idSupplyOrder = (int) Tools::getValue('id_supply_order');

            // creates new fields_list
            $this->fields_list = [
                'history_date'       => [
                    'title'        => $this->l('Last update'),
                    'align'        => 'left',
                    'type'         => 'datetime',
                    'havingFilter' => true,
                ],
                'history_employee'   => [
                    'title'        => $this->l('Employee'),
                    'align'        => 'left',
                    'havingFilter' => true,
                ],
                'history_state_name' => [
                    'title'        => $this->l('Status'),
                    'align'        => 'left',
                    'color'        => 'color',
                    'havingFilter' => true,
                ],
            ];
            // loads history of the given order
            unset($this->_select, $this->_join, $this->_where, $this->_orderBy, $this->_orderWay, $this->_group, $this->_filterHaving, $this->_filter);
            $this->_select = '
				a.`date_add` as history_date,
				CONCAT(a.`employee_lastname`, \' \', a.`employee_firstname`) as history_employee,
				sosl.`name` as history_state_name,
				sos.`color` as color';

            $this->_join = '
				LEFT JOIN `'._DB_PREFIX_.'supply_order_state` sos ON (a.`id_state` = sos.`id_supply_order_state`)
				LEFT JOIN `'._DB_PREFIX_.'supply_order_state_lang` sosl ON
				(
					a.`id_state` = sosl.`id_supply_order_state`
					AND sosl.`id_lang` = '.(int) $idLang.'
				)';

            $this->_where = 'AND a.`id_supply_order` = '.(int) $idSupplyOrder;
            $this->_orderBy = 'a.date_add';
            $this->_orderWay = 'DESC';

            return parent::renderList();
        } elseif (Tools::isSubmit('id_supply_order') && Tools::isSubmit('display_product_history')) {
            $this->identifier = 'id_supply_order_receipt_history';
            $this->table = 'supply_order_receipt_history';
            $this->actions = [];
            $this->toolbar_btn = [];
            $this->list_simple_header = true;
            $this->lang = false;
            $idSupplyOrderDetail = (int) Tools::getValue('id_supply_order');

            unset($this->fields_list);
            $this->fields_list = [
                'date_add' => [
                    'title'        => $this->l('Last update'),
                    'align'        => 'left',
                    'type'         => 'datetime',
                    'havingFilter' => true,
                ],
                'employee' => [
                    'title'        => $this->l('Employee'),
                    'align'        => 'left',
                    'havingFilter' => true,
                ],
                'quantity' => [
                    'title'        => $this->l('Quantity received'),
                    'align'        => 'left',
                    'havingFilter' => true,
                ],
            ];

            // loads history of the given order
            unset($this->_select, $this->_join, $this->_where, $this->_orderBy, $this->_orderWay, $this->_group, $this->_filterHaving, $this->_filter);
            $this->_select = 'CONCAT(a.`employee_lastname`, \' \', a.`employee_firstname`) as employee';
            $this->_where = 'AND a.`id_supply_order_detail` = '.(int) $idSupplyOrderDetail;
            $this->_orderBy = 'a.date_add';
            $this->_orderWay = 'DESC';

            return parent::renderList();
        }
    }

    /**
     * method call when ajax request is made for search product to add to the order
     *
     * @TODO  - Update this method to retreive the reference, ean13, upc corresponding to a product attribute
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function ajaxProcessSearchProduct()
    {
        // Get the search pattern
        $pattern = pSQL(Tools::getValue('q', false));

        if (!$pattern || $pattern == '' || strlen($pattern) < 1) {
            die('1');
        }

        // get supplier id
        $idSupplier = (int) Tools::getValue('id_supplier', false);

        // gets the currency
        $idCurrency = (int) Tools::getValue('id_currency', false);

        // get lang from context
        $idLang = (int) $this->context->language->id;

        $query = new DbQuery();
        $query->select(
            '
			CONCAT(p.`id_product`, \'_\', IFNULL(pa.`id_product_attribute`, \'0\')) as id,
			ps.`product_supplier_reference` as supplier_reference,
			IFNULL(pa.`reference`, IFNULL(p.`reference`, \'\')) as reference,
			IFNULL(pa.`ean13`, IFNULL(p.`ean13`, \'\')) as ean13,
			IFNULL(pa.`upc`, IFNULL(p.`upc`, \'\')) as upc,
			md5(CONCAT(\''._COOKIE_KEY_.'\', p.`id_product`, \'_\', IFNULL(pa.`id_product_attribute`, \'0\'))) as checksum,
			IFNULL(CONCAT(pl.`name`, \' : \', GROUP_CONCAT(DISTINCT agl.`name`, \' - \', al.`name` ORDER BY agl.`name` SEPARATOR \', \')), pl.`name`) as name,
			t.rate as rate
		'
        );
        $query->from('product', 'p');
        $query->innerJoin('product_lang', 'pl', 'pl.`id_product` = p.`id_product` AND pl.`id_lang` = '.$idLang);
        $query->leftJoin('tax_rule', 'tr', 'p.id_tax_rules_group = tr.id_tax_rules_group');
        $query->leftJoin('tax', 't', 'tr.id_tax = t.id_tax');
        $query->leftJoin('product_attribute', 'pa', 'pa.`id_product` = p.`id_product`');
        $query->leftJoin('product_attribute_combination', 'pac', 'pac.`id_product_attribute` = pa.`id_product_attribute`');
        $query->leftJoin('attribute', 'atr', 'atr.`id_attribute` = pac.`id_attribute`');
        $query->leftJoin('attribute_lang', 'al', 'al.`id_attribute` = atr.`id_attribute` AND al.`id_lang` = '.$idLang);
        $query->leftJoin('attribute_group_lang', 'agl', 'agl.`id_attribute_group` = atr.`id_attribute_group` AND agl.`id_lang` = '.$idLang);
        $query->leftJoin('product_supplier', 'ps', 'ps.`id_product` = p.`id_product` AND ps.`id_product_attribute` = IFNULL(pa.`id_product_attribute`, 0)');
        $query->where('(pl.`name` LIKE \'%'.$pattern.'%\' OR p.`reference` LIKE \'%'.$pattern.'%\' OR ps.`product_supplier_reference` LIKE \'%'.$pattern.'%\')');
        $query->where('NOT EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'product_download` pd WHERE (pd.`id_product` = p.`id_product`))');
        $query->where('p.`is_virtual` = 0 AND p.`cache_is_pack` = 0');

        if ($idSupplier) {
            $query->where('ps.`id_supplier` = '.$idSupplier.' OR p.`id_supplier` = '.$idSupplier);
        }

        $query->groupBy('p.`id_product`, pa.`id_product_attribute`');
        $items = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        foreach ($items as &$item) {
            $ids = explode('_', $item['id']);
            $prices = ProductSupplier::getProductSupplierPrice($ids[0], $ids[1], $idSupplier, true);
            if (count($prices)) {
                $item['unit_price_te'] = Tools::convertPriceFull(
                    $prices['product_supplier_price_te'],
                    new Currency((int) $prices['id_currency']),
                    new Currency($idCurrency)
                );
            }
        }
        if ($items) {
            $this->ajaxDie(json_encode($items));
        }

        $this->ajaxDie('1');
    }

    /**
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function renderView()
    {
        $this->show_toolbar = true;
        $this->toolbar_scroll = false;
        $this->table = 'supply_order_detail';
        $this->identifier = 'id_supply_order_detail';
        $this->className = 'SupplyOrderDetail';
        $this->colorOnBackground = false;
        $this->lang = false;
        $this->list_simple_header = true;
        $this->list_no_link = true;

        // gets the id supplier to view
        $idSupplyOrder = (int) Tools::getValue('id_supply_order');

        // gets global order information
        $supplyOrder = new SupplyOrder((int) $idSupplyOrder);

        if (Validate::isLoadedObject($supplyOrder)) {
            if (!$supplyOrder->is_template) {
                $this->displayInformation($this->l('This interface allows you to display detailed information about your order.').'<br />');
            } else {
                $this->displayInformation($this->l('This interface allows you to display detailed information about your order template.').'<br />');
            }

            $idLang = (int) $supplyOrder->id_lang;

            // just in case..
            unset($this->_select, $this->_join, $this->_where, $this->_orderBy, $this->_orderWay, $this->_group, $this->_filterHaving, $this->_filter);

            // gets all information on the products ordered
            $this->_where = 'AND a.`id_supply_order` = '.(int) $idSupplyOrder;

            // gets the list ordered by price desc, without limit
            $this->getList($idLang, 'price_te', 'DESC', 0, false, false);

            // gets the currency used in this order
            $currency = new Currency($supplyOrder->id_currency);

            // gets the warehouse where products will be received
            $warehouse = new Warehouse($supplyOrder->id_warehouse);

            // sets toolbar title with order reference
            if (!$supplyOrder->is_template) {
                $this->toolbar_title = sprintf($this->l('Details on supply order #%s'), $supplyOrder->reference);
            } else {
                $this->toolbar_title = sprintf($this->l('Details on supply order template #%s'), $supplyOrder->reference);
            }
            // re-defines fields_list
            $this->fields_list = [
                'supplier_reference'     => [
                    'title'   => $this->l('Supplier Reference'),
                    'align'   => 'center',
                    'orderby' => false,
                    'filter'  => false,
                    'search'  => false,
                ],
                'reference'              => [
                    'title'   => $this->l('Reference'),
                    'align'   => 'center',
                    'orderby' => false,
                    'filter'  => false,
                    'search'  => false,
                ],
                'ean13'                  => [
                    'title'   => $this->l('EAN-13 or JAN barcode'),
                    'align'   => 'center',
                    'orderby' => false,
                    'filter'  => false,
                    'search'  => false,
                ],
                'upc'                    => [
                    'title'   => $this->l('UPC barcode'),
                    'align'   => 'center',
                    'orderby' => false,
                    'filter'  => false,
                    'search'  => false,
                ],
                'name'                   => [
                    'title'   => $this->l('Name'),
                    'orderby' => false,
                    'filter'  => false,
                    'search'  => false,
                ],
                'unit_price_te'          => [
                    'title'    => $this->l('Unit price (tax excl.)'),
                    'align'    => 'right',
                    'orderby'  => false,
                    'filter'   => false,
                    'search'   => false,
                    'type'     => 'price',
                    'currency' => true,
                ],
                'quantity_expected'      => [
                    'title'   => $this->l('Quantity'),
                    'align'   => 'right',
                    'orderby' => false,
                    'filter'  => false,
                    'search'  => false,
                ],
                'price_te'               => [
                    'title'    => $this->l('Price (tax excl.)'),
                    'align'    => 'right',
                    'orderby'  => false,
                    'filter'   => false,
                    'search'   => false,
                    'type'     => 'price',
                    'currency' => true,
                ],
                'discount_rate'          => [
                    'title'   => $this->l('Discount percentage'),
                    'align'   => 'right',
                    'orderby' => false,
                    'filter'  => false,
                    'search'  => false,
                    'suffix'  => '%',
                ],
                'discount_value_te'      => [
                    'title'    => $this->l('Discount value (tax excl.)'),
                    'align'    => 'right',
                    'orderby'  => false,
                    'filter'   => false,
                    'search'   => false,
                    'type'     => 'price',
                    'currency' => true,
                ],
                'price_with_discount_te' => [
                    'title'    => $this->l('Price with product discount (tax excl.)'),
                    'align'    => 'right',
                    'orderby'  => false,
                    'filter'   => false,
                    'search'   => false,
                    'type'     => 'price',
                    'currency' => true,
                ],
                'tax_rate'               => [
                    'title'   => $this->l('Tax rate'),
                    'align'   => 'right',
                    'orderby' => false,
                    'filter'  => false,
                    'search'  => false,
                    'suffix'  => '%',
                ],
                'tax_value'              => [
                    'title'    => $this->l('Tax value'),
                    'align'    => 'right',
                    'orderby'  => false,
                    'filter'   => false,
                    'search'   => false,
                    'type'     => 'price',
                    'currency' => true,
                ],
                'price_ti'               => [
                    'title'    => $this->l('Price (tax incl.)'),
                    'align'    => 'right',
                    'orderby'  => false,
                    'filter'   => false,
                    'search'   => false,
                    'type'     => 'price',
                    'currency' => true,
                ],
            ];

            //some staff before render list
            foreach ($this->_list as &$item) {
                $item['discount_rate'] = Tools::ps_round($item['discount_rate'], 4);
                $item['tax_rate'] = Tools::ps_round($item['tax_rate'], 4);
                $item['id_currency'] = $currency->id;
            }

            // unsets some buttons
            unset($this->toolbar_btn['export-csv-orders']);
            unset($this->toolbar_btn['export-csv-details']);
            unset($this->toolbar_btn['new']);

            // renders list
            $helper = new HelperList();
            $this->setHelperDisplay($helper);
            $helper->actions = [];
            $helper->show_toolbar = false;
            $helper->toolbar_btn = $this->toolbar_btn;

            $content = $helper->generateList($this->_list, $this->fields_list);

            // display these global order informations
            $this->tpl_view_vars = [
                'supply_order_detail_content'         => $content,
                'supply_order_warehouse'              => (Validate::isLoadedObject($warehouse) ? $warehouse->name : ''),
                'supply_order_reference'              => $supplyOrder->reference,
                'supply_order_supplier_name'          => $supplyOrder->supplier_name,
                'supply_order_creation_date'          => Tools::displayDate($supplyOrder->date_add, null, false),
                'supply_order_last_update'            => Tools::displayDate($supplyOrder->date_upd, null, false),
                'supply_order_expected'               => Tools::displayDate($supplyOrder->date_delivery_expected, null, false),
                'supply_order_discount_rate'          => Tools::ps_round($supplyOrder->discount_rate, 2),
                'supply_order_total_te'               => Tools::displayPrice($supplyOrder->total_te, $currency),
                'supply_order_discount_value_te'      => Tools::displayPrice($supplyOrder->discount_value_te, $currency),
                'supply_order_total_with_discount_te' => Tools::displayPrice($supplyOrder->total_with_discount_te, $currency),
                'supply_order_total_tax'              => Tools::displayPrice($supplyOrder->total_tax, $currency),
                'supply_order_total_ti'               => Tools::displayPrice($supplyOrder->total_ti, $currency),
                'supply_order_currency'               => $currency,
                'is_template'                         => $supplyOrder->is_template,
            ];
        }

        return parent::renderView();
    }

    /**
     * Callback used to display custom content for a given field
     *
     * @param int    $idSupplyOrder
     * @param string $tr
     *
     * @return string
     * @throws PrestaShopException
     */
    public function printExportIcons($idSupplyOrder, $tr)
    {
        $supplyOrder = new SupplyOrder((int) $idSupplyOrder);

        if (!Validate::isLoadedObject($supplyOrder)) {
            return '';
        }

        $supplyOrderState = new SupplyOrderState($supplyOrder->id_supply_order_state);
        if (!Validate::isLoadedObject($supplyOrderState)) {
            return '';
        }

        $content = '';
        if ($supplyOrderState->editable == false) {
            $content .= '<a class="btn btn-default" href="'.$this->context->link->getAdminLink('AdminPdf')
                .'&submitAction=generateSupplyOrderFormPDF&id_supply_order='.(int) $supplyOrder->id.'" title="'.$this->l('Export as PDF')
                .'"><i class="icon-print"></i></a>';
        }
        if ($supplyOrderState->enclosed == true && $supplyOrderState->receipt_state == true) {
            $content .= '&nbsp;<a href="'.$this->context->link->getAdminLink('AdminSupplyOrders').'&id_supply_order='.(int) $supplyOrder->id.'
						 &csv_order_details" class="btn btn-default" title='.$this->l('Export as CSV').'">
						 <i class="icon-table"></i></a>';
        }

        return $content;
    }

    /**
     * Overrides AdminController::beforeAdd()
     *
     * @see AdminController::beforeAdd()
     *
     * @param SupplyOrder $object
     *
     * @return true
     */
    public function beforeAdd($object)
    {
        if (Tools::isSubmit('is_template')) {
            $object->is_template = 1;
        }

        return true;
    }

    /**
     * Initialize processing
     *
     * @return bool
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function initProcess()
    {
        if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            $this->warnings[md5('PS_ADVANCED_STOCK_MANAGEMENT')] =
                $this->l('You need to activate advanced stock management prior to using this feature.');

            return false;
        }
        parent::initProcess();
    }

    /**
     * Overrides AdminController::afterAdd()
     *
     * @see   AdminController::afterAdd()
     *
     * @param ObjectModel $object
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function afterAdd($object)
    {
        if (is_numeric(Tools::getValue('load_products'))) {
            $this->loadProducts((int) Tools::getValue('load_products'));
        }

        $this->object = $object;

        return true;
    }
}
