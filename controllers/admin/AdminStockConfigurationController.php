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
 * Class AdminStockConfigurationControllerCore
 *
 * @since 1.0.0
 */
class AdminStockConfigurationControllerCore extends AdminController
{
    /**
     * AdminStockConfigurationControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'stock_mvt_reason';
        $this->className = 'StockMvtReason';
        $this->lang = true;
        $this->multishop_context = Shop::CONTEXT_ALL;

        // defines fields
        $this->fields_list = [
            'id_stock_mvt_reason' => [
                'title'  => $this->l('ID'),
                'align'  => 'center',
                'class'  => 'fixed-width-xs',
                'search' => false,
            ],
            'sign'                => [
                'title'      => $this->l('Action'),
                'align'      => 'center',
                'type'       => 'select',
                'filter_key' => 'a!sign',
                'list'       => [
                    '1'  => $this->l('Increase'),
                    '-1' => $this->l('Decrease'),
                ],
                'icon'       => [
                    -1 => 'remove_stock.png',
                    1  => 'add_stock.png',
                ],
                'orderby'    => false,
                'class'      => 'fixed-width-sm',
                'search'     => false,
            ],
            'name'                => [
                'title'      => $this->l('Name'),
                'filter_key' => 'b!name',
                'search'     => false,
            ],
        ];

        // loads labels (incremenation)
        $reasonsInc = StockMvtReason::getStockMvtReasonsWithFilter(
            $this->context->language->id,
            [Configuration::get('PS_STOCK_MVT_TRANSFER_TO')],
            1
        );
        // loads labaels (decremenation)
        $reasonsDec = StockMvtReason::getStockMvtReasonsWithFilter(
            $this->context->language->id,
            [Configuration::get('PS_STOCK_MVT_TRANSFER_FROM')],
            -1
        );

        // defines options for StockMvt
        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Options'),
                'fields' => [
                    'PS_STOCK_MVT_INC_REASON_DEFAULT' => [
                        'title'      => $this->l('Default label for increasing stock'),
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => $reasonsInc,
                        'identifier' => 'id_stock_mvt_reason',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_STOCK_MVT_DEC_REASON_DEFAULT' => [
                        'title'      => $this->l('Default label for decreasing stock'),
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => $reasonsDec,
                        'identifier' => 'id_stock_mvt_reason',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_STOCK_CUSTOMER_ORDER_REASON'  => [
                        'title'      => $this->l('Default label for decreasing stock when a customer order is shipped'),
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => $reasonsDec,
                        'identifier' => 'id_stock_mvt_reason',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_STOCK_MVT_SUPPLY_ORDER'       => [
                        'title'      => $this->l('Default label for increasing stock when a supply order is received'),
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => $reasonsInc,
                        'identifier' => 'id_stock_mvt_reason',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        parent::__construct();
    }

    /**
     * Initialize
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function init()
    {
        // if we are managing the second list (i.e. supply order status)
        if (Tools::isSubmit('submitAddsupply_order_state') ||
            Tools::isSubmit('addsupply_order_state') ||
            Tools::isSubmit('updatesupply_order_state') ||
            Tools::isSubmit('deletesupply_order_state')
        ) {
            $this->table = 'supply_order_state';
            $this->className = 'SupplyOrderState';
            $this->identifier = 'id_supply_order_state';
            $this->display = 'edit';
        }
        parent::init();
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
        // if we are managing StockMvtReason
        if (Tools::isSubmit('addstock_mvt_reason') ||
            Tools::isSubmit('updatestock_mvt_reason') ||
            Tools::isSubmit('submitAddstock_mvt_reason') ||
            Tools::isSubmit('submitUpdatestock_mvt_reason')
        ) {
            $this->toolbar_title = $this->l('Stock: Add stock movement label');

            $this->fields_form = [
                'legend' => [
                    'title' => $this->l('Stock Movement label'),
                    'icon'  => 'icon-pencil',
                ],
                'input'  => [
                    [
                        'type'     => 'text',
                        'lang'     => true,
                        'label'    => $this->l('Name'),
                        'name'     => 'name',
                        'required' => true,
                    ],
                    [
                        'type'     => 'select',
                        'label'    => $this->l('Action'),
                        'name'     => 'sign',
                        'required' => true,
                        'options'  => [
                            'query' => [
                                [
                                    'id'   => '1',
                                    'name' => $this->l('Increase stock'),
                                ],
                                [
                                    'id'   => '-1',
                                    'name' => $this->l('Decrease stock'),
                                ],
                            ],
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                        'desc'     => $this->l('Does this label indicate a stock increase or a stock decrease?'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ];
        } // else, if we are managing Supply Order Status
        elseif (Tools::isSubmit('addsupply_order_state') ||
            Tools::isSubmit('updatesupply_order_state') ||
            Tools::isSubmit('submitAddsupply_order_state') ||
            Tools::isSubmit('submitUpdatesupply_order_state')
        ) {
            $this->fields_form = [
                'legend' => [
                    'title' => $this->l('Supply Order Status'),
                    'icon'  => 'icon-pencil',
                ],
                'input'  => [
                    [
                        'type'     => 'text',
                        'lang'     => true,
                        'label'    => $this->l('Status'),
                        'name'     => 'name',
                        'required' => true,
                    ],
                    [
                        'type'  => 'color',
                        'label' => $this->l('Color'),
                        'name'  => 'color',
                        'hint'  => $this->l('Status will be highlighted in this color. HTML colors only.'),
                    ],
                    [
                        'type'     => 'switch',
                        'label'    => $this->l('Editable'),
                        'name'     => 'editable',
                        'required' => true,
                        'is_bool'  => true,
                        'values'   => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ],
                        ],
                        'hint'     => $this->l('Is it is possible to edit the order? Keep in mind that an editable order cannot be sent to the supplier.'),
                    ],
                    [
                        'type'     => 'switch',
                        'label'    => $this->l('Delivery note'),
                        'name'     => 'delivery_note',
                        'required' => true,
                        'is_bool'  => true,
                        'values'   => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ],
                        ],
                        'hint'     => $this->l('Is it possible to generate a delivery note for the order?'),
                    ],
                    [
                        'type'     => 'switch',
                        'label'    => $this->l('Delivery status'),
                        'name'     => 'receipt_state',
                        'required' => true,
                        'is_bool'  => true,
                        'values'   => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ],
                        ],
                        'hint'     => $this->l('Indicates whether the supplies have been either partially or completely received. This will allow you to know if ordered products have to be added to the corresponding warehouse.'),
                    ],
                    [
                        'type'     => 'switch',
                        'label'    => $this->l('Awaiting delivery'),
                        'name'     => 'pending_receipt',
                        'required' => true,
                        'is_bool'  => true,
                        'values'   => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ],
                        ],
                        'hint'     => $this->l('Indicates that you are awaiting delivery of supplies.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ];

            if (Tools::isSubmit('addsupply_order_state')) {
                $this->toolbar_title = $this->l('Stock: Add supply order status');
            } else {
                $this->toolbar_title = $this->l('Stock: Update supply order status');

                $idSupplyOrderState = Tools::getValue('id_supply_order_state', 0);

                // only some fields are editable for initial states
                if (in_array($idSupplyOrderState, [1, 2, 3, 4, 5, 6])) {
                    $this->fields_form = [
                        'legend' => [
                            'title' => $this->l('Supply order status'),
                            'icon'  => 'icon-pencil',
                        ],
                        'input'  => [
                            [
                                'type'     => 'text',
                                'lang'     => true,
                                'label'    => $this->l('Status'),
                                'name'     => 'name',
                                'required' => true,
                            ],
                            [
                                'type'  => 'color',
                                'label' => $this->l('Color'),
                                'name'  => 'color',
                                'desc'  => $this->l('Status will be highlighted in this color. HTML colors only.'),
                            ],
                        ],
                        'submit' => [
                            'title' => $this->l('Save'),
                        ],
                    ];
                }

                if (!($obj = new SupplyOrderState((int) $idSupplyOrderState))) {
                    return '';
                }

                $this->fields_value = [
                    'color'           => $obj->color,
                    'editable'        => $obj->editable,
                    'delivery_note'   => $obj->delivery_note,
                    'receipt_state'   => $obj->receipt_state,
                    'pending_receipt' => $obj->pending_receipt,
                ];
                foreach ($this->getLanguages() as $language) {
                    $this->fields_value['name'][$language['id_lang']] = $this->getFieldValue($obj, 'name', $language['id_lang']);
                }
            }
        }

        return parent::renderForm();
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
        /**
         * General messages displayed for all lists
         */
        $this->displayInformation($this->l('This interface allows you to configure your supply order status and stock movement labels.').'<br />');

        // Checks access
        if (!($this->tabAccess['add'] === '1')) {
            unset($this->toolbar_btn['new']);
        }

        /**
         * First list
         * Stock Mvt Labels/Reasons
         */
        $firstList = null;
        $this->list_no_link = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->addRowActionSkipList('delete', [1, 2, 3, 4, 5, 6, 7, 8]);
        $this->_where = ' AND a.deleted = 0';
        $this->_use_found_rows = false;

        $this->toolbar_title = $this->l('Stock: Stock movement labels');
        $firstList = parent::renderList();

        /**
         * Second list
         * Supply Order Status/State
         */
        $secondList = null;
        unset($this->_select, $this->_where, $this->_join, $this->_group, $this->_filterHaving, $this->_filter, $this->list_skip_actions['delete'], $this->list_skip_actions['edit'], $this->list_id);

        // generates the actual second list
        $secondList = $this->initSupplyOrderStatusList();

        // resets default table and className for options list management
        $this->table = 'stock_mvt_reason';
        $this->className = 'StockMvtReason';

        // returns the final list
        return $secondList.$firstList;
    }

    /**
     * Help function for AdminStockConfigurationController::renderList()
     * @see AdminStockConfigurationController::renderList()
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function initSupplyOrderStatusList()
    {
        $this->table = 'supply_order_state';
        $this->className = 'SupplyOrderState';
        $this->identifier = 'id_supply_order_state';
        $this->_defaultOrderBy = 'id_supply_order_state';
        $this->lang = true;
        $this->list_no_link = true;
        $this->_orderBy = null;
        $this->addRowActionSkipList('delete', [1, 2, 3, 4, 5, 6]);
        $this->toolbar_title = $this->l('Stock: Supply order status');
        $this->initToolbar();

        $this->fields_list = [
            'name'            => [
                'title'  => $this->l('Name'),
                'color'  => 'color',
                'search' => false,
            ],
            'editable'        => [
                'title'   => $this->l('Supply order can be edited?'),
                'align'   => 'center',
                'active'  => 'editable',
                'type'    => 'bool',
                'orderby' => false,
                'class'   => 'fixed-width-sm',
                'ajax'    => true,
                'search'  => false,
            ],
            'delivery_note'   => [
                'title'   => $this->l('Delivery note is available?'),
                'align'   => 'center',
                'active'  => 'deliveryNote',
                'type'    => 'bool',
                'orderby' => false,
                'class'   => 'fixed-width-sm',
                'ajax'    => true,
                'search'  => false,
            ],
            'pending_receipt' => [
                'title'   => $this->l('Delivery is expected?'),
                'align'   => 'center',
                'active'  => 'pendingReceipt',
                'type'    => 'bool',
                'orderby' => false,
                'class'   => 'fixed-width-sm',
                'ajax'    => true,
                'search'  => false,
            ],
            'receipt_state'   => [
                'title'   => $this->l('Stock has been delivered?'),
                'align'   => 'center',
                'active'  => 'receiptState',
                'type'    => 'bool',
                'orderby' => false,
                'class'   => 'fixed-width-sm',
                'ajax'    => true,
                'search'  => false,
            ],
            'enclosed'        => [
                'title'   => $this->l('Order is closed?'),
                'align'   => 'center',
                'active'  => 'enclosed',
                'type'    => 'bool',
                'orderby' => false,
                'class'   => 'fixed-width-sm',
                'ajax'    => true,
                'search'  => false,
            ],
        ];

        return parent::renderList();
    }

    /**
     * AdminController::postProcess() override
     *
     * @see AdminController::postProcess()
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        // SupplyOrderState
        if (Tools::isSubmit('submitAddsupply_order_state') ||
            Tools::isSubmit('deletesupply_order_state') ||
            Tools::isSubmit('submitUpdatesupply_order_state')
        ) {
            if (Tools::isSubmit('deletesupply_order_state')) {
                $this->action = 'delete';
            } else {
                $this->action = 'save';
            }
            $this->table = 'supply_order_state';
            $this->className = 'SupplyOrderState';
            $this->identifier = 'id_supply_order_state';
            $this->_defaultOrderBy = 'id_supply_order_state';
        } // StockMvtReason
        elseif (Tools::isSubmit('delete'.$this->table)) {
            $this->deleted = true;
        }

        return parent::postProcess();
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
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        //If there is a field product_name in the list, check if this field is null and display standard message
        foreach ($this->fields_list as $key => $value) {
            if ($key == 'product_name') {
                $nbItems = count($this->_list);

                for ($i = 0; $i < $nbItems; ++$i) {
                    $item = &$this->_list[$i];

                    if (empty($item['product_name'])) {
                        $item['product_name'] = $this->l('The name of this product is not available. It may have been deleted from the system.');
                    }
                }
            }
        }
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            $this->warnings[md5('PS_ADVANCED_STOCK_MANAGEMENT')] = $this->l('You need to activate the Advanced Stock Management feature before you can use this feature.');

            return;
        }

        parent::initContent();
    }

    /**
     * Initialize processing
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initProcess()
    {
        if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            $this->warnings[md5('PS_ADVANCED_STOCK_MANAGEMENT')] = $this->l('You need to activate the Advanced Stock Management feature before you can use this feature.');

            false;
        }
        parent::initProcess();
    }

    /**
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessEditableSupplyOrderState()
    {
        $idSupplyOrderState = (int) Tools::getValue('id_supply_order_state');

        $sql = 'UPDATE '._DB_PREFIX_.'supply_order_state SET `editable` = NOT `editable` WHERE id_supply_order_state='.$idSupplyOrderState;
        $result = Db::getInstance()->execute($sql);

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }
    }

    /**
     * Ajax process delivery note supply order state
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessDeliveryNoteSupplyOrderState()
    {
        $idSupplyOrderState = (int) Tools::getValue('id_supply_order_state');

        $sql = 'UPDATE '._DB_PREFIX_.'supply_order_state SET `delivery_note` = NOT `delivery_note` WHERE id_supply_order_state='.$idSupplyOrderState;
        $result = Db::getInstance()->execute($sql);

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }
    }

    /**
     * Ajax process pending receipt supply order state
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessPendingReceiptSupplyOrderState()
    {
        $idSupplyOrderState = (int) Tools::getValue('id_supply_order_state');

        $sql = 'UPDATE '._DB_PREFIX_.'supply_order_state SET `pending_receipt` = NOT `pending_receipt` WHERE id_supply_order_state='.$idSupplyOrderState;
        $result = Db::getInstance()->execute($sql);

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }
    }

    /**
     * Ajax process receipt state supply order state
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessReceiptStateSupplyOrderState()
    {
        $idSupplyOrderState = (int) Tools::getValue('id_supply_order_state');

        $sql = 'UPDATE '._DB_PREFIX_.'supply_order_state SET `receipt_state` = NOT `receipt_state` WHERE id_supply_order_state='.$idSupplyOrderState;
        $result = Db::getInstance()->execute($sql);

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }
    }

    /**
     * Ajax process enclosed supply order state
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessEnclosedSupplyOrderState()
    {
        $idSupplyOrderState = (int) Tools::getValue('id_supply_order_state');

        $sql = 'UPDATE '._DB_PREFIX_.'supply_order_state SET `enclosed`= NOT `enclosed` WHERE id_supply_order_state='.$idSupplyOrderState;
        $result = Db::getInstance()->execute($sql);

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }
    }
}
