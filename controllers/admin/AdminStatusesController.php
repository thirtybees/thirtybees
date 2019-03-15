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
 * Class AdminStatusesControllerCore
 *
 * @since 1.0.0
 */
class AdminStatusesControllerCore extends AdminController
{
    /**
     * AdminStatusesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Retrocompatibility with < 1.1.0.
        OrderState::installationCheck();
        OrderReturnState::installationCheck();

        $this->bootstrap = true;
        $this->table = 'order_state';
        $this->className = 'OrderState';
        $this->lang = true;
        $this->deleted = false;
        $this->colorOnBackground = false;
        $this->bulk_actions = ['delete' => ['text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?')]];
        $this->multishop_context = Shop::CONTEXT_ALL;
        $this->imageType = 'gif';
        $this->fieldImageSettings = [
            'name' => 'icon',
            'dir'  => 'os',
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
        if (Tools::isSubmit('addorder_return_state')) {
            $this->display = 'add';
        }
        if (Tools::isSubmit('updateorder_return_state')) {
            $this->display = 'edit';
        }

        parent::init();
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
            $this->page_header_toolbar_btn['new_order_state'] = [
                'href' => static::$currentIndex.'&addorder_state&token='.$this->token,
                'desc' => $this->l('Add new order status', null, null, false),
                'icon' => 'process-icon-new',
            ];
            $this->page_header_toolbar_btn['new_order_return_state'] = [
                'href' => static::$currentIndex.'&addorder_return_state&token='.$this->token,
                'desc' => $this->l('Add new order return status', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Function used to render the list to display for this controller
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        //init and render the first list
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $unremovableOs = [];
        $buf = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('`id_order_state`')
            ->from('order_state')
            ->where('`unremovable` = 1')
        );
        foreach ($buf as $row) {
            $unremovableOs[] = $row['id_order_state'];
        }
        $this->addRowActionSkipList('delete', $unremovableOs);

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];
        $this->initOrderStatutsList();
        $lists = parent::renderList();

        //init and render the second list
        $this->list_skip_actions = [];
        $this->_filter = false;
        $this->addRowActionSkipList('delete', [1, 2, 3, 4, 5]);
        $this->initOrdersReturnsList();
        $this->checkFilterForOrdersReturnsList();

        // call postProcess() to take care of actions and filters
        $this->postProcess();
        $this->toolbar_title = $this->l('Return statuses');

        parent::initToolbar();
        $lists .= parent::renderList();

        return $lists;
    }

    /**
     * init all variables to render the order status list
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function initOrderStatutsList()
    {
        $this->fields_list = [
            'id_order_state' => [
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'name'           => [
                'title' => $this->l('Name'),
                'width' => 'auto',
                'color' => 'color',
            ],
            'active'       => [
                'title'   => $this->l('Active'),
                'align'   => 'text-center',
                'active'  => 'active',
                'type'    => 'bool',
                'ajax'    => true,
                'orderby' => false,
                'class'   => 'fixed-width-sm',
            ],
            'logo'           => [
                'title'   => $this->l('Icon'),
                'align'   => 'text-center',
                'image'   => 'os',
                'orderby' => false,
                'search'  => false,
                'class'   => 'fixed-width-xs',
            ],
            'send_email'     => [
                'title'   => $this->l('Send email to customer'),
                'align'   => 'text-center',
                'active'  => 'sendEmail',
                'type'    => 'bool',
                'ajax'    => true,
                'orderby' => false,
                'class'   => 'fixed-width-sm',
            ],
            'delivery'       => [
                'title'   => $this->l('Delivery'),
                'align'   => 'text-center',
                'active'  => 'delivery',
                'type'    => 'bool',
                'ajax'    => true,
                'orderby' => false,
                'class'   => 'fixed-width-sm',
            ],
            'invoice'        => [
                'title'   => $this->l('Invoice'),
                'align'   => 'text-center',
                'active'  => 'invoice',
                'type'    => 'bool',
                'ajax'    => true,
                'orderby' => false,
                'class'   => 'fixed-width-sm',
            ],
            'template'       => [
                'title' => $this->l('Email template'),
            ],
        ];
    }

    /**
     * init all variables to render the order return list
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function initOrdersReturnsList()
    {
        $this->table = 'order_return_state';
        $this->className = 'OrderReturnState';
        $this->_defaultOrderBy = $this->identifier = 'id_order_return_state';
        $this->list_id = 'order_return_state';
        $this->deleted = false;
        $this->_orderBy = null;

        $this->fields_list = [
            'id_order_return_state' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'                  => [
                'title' => $this->l('Name'),
                'align' => 'left',
                'width' => 'auto',
                'color' => 'color',
            ],
            'active'       => [
                'title'   => $this->l('Active'),
                'align'   => 'text-center',
                'active'  => 'active',
                'type'    => 'bool',
                'ajax'    => true,
                'orderby' => false,
                'class'   => 'fixed-width-sm',
            ],
        ];
    }

    /**
     * @return void
     *
     * @since 1.0.0
     */
    protected function checkFilterForOrdersReturnsList()
    {
        // test if a filter is applied for this list
        if (Tools::isSubmit('submitFilter'.$this->table) || $this->context->cookie->{'submitFilter'.$this->table} !== false) {
            $this->filter = true;
        }

        // test if a filter reset request is required for this list
        if (isset($_POST['submitReset'.$this->table])) {
            $this->action = 'reset_filters';
        } else {
            $this->action = '';
        }
    }

    /**
     * Post processing
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit($this->table.'Orderby') || Tools::isSubmit($this->table.'Orderway')) {
            $this->filter = true;
        }

        if (Tools::isSubmit('submitAddorder_return_state')) {
            $idOrderReturnState = Tools::getValue('id_order_return_state');

            // Create Object OrderReturnState
            $orderReturnState = new OrderReturnState((int) $idOrderReturnState);

            $orderReturnState->color = Tools::getValue('color');
            $orderReturnState->active = Tools::getValue('active_on');
            $orderReturnState->name = [];
            foreach (Language::getIDs(false) as $idLang) {
                $orderReturnState->name[$idLang] = Tools::getValue('name_'.$idLang);
            }

            // Update object
            if (!$orderReturnState->save()) {
                $this->errors[] = Tools::displayError('An error has occurred: Can\'t save the current order\'s return status.');
            } else {
                Tools::redirectAdmin(static::$currentIndex.'&conf=4&token='.$this->token);
            }
        }

        if (Tools::isSubmit('submitBulkdeleteorder_return_state')) {
            $this->className = 'OrderReturnState';
            $this->table = 'order_return_state';
            $this->boxes = Tools::getValue('order_return_stateBox');
            parent::processBulkDelete();
        }

        if (Tools::isSubmit('deleteorder_return_state')) {
            $idOrderReturnState = Tools::getValue('id_order_return_state');

            // Create Object OrderReturnState
            $orderReturnState = new OrderReturnState((int) $idOrderReturnState);

            if (!$orderReturnState->delete()) {
                $this->errors[] = Tools::displayError('An error has occurred: Can\'t delete the current order\'s return status.');
            } else {
                Tools::redirectAdmin(static::$currentIndex.'&conf=1&token='.$this->token);
            }
        }

        if (Tools::isSubmit('submitAdd'.$this->table)) {
            $this->deleted = false; // Disabling saving historisation
            $_POST['invoice'] = (int) Tools::getValue('invoice_on');
            $_POST['logable'] = (int) Tools::getValue('logable_on');
            $_POST['send_email'] = (int) Tools::getValue('send_email_on');
            $_POST['hidden'] = (int) Tools::getValue('hidden_on');
            $_POST['shipped'] = (int) Tools::getValue('shipped_on');
            $_POST['paid'] = (int) Tools::getValue('paid_on');
            $_POST['delivery'] = (int) Tools::getValue('delivery_on');
            $_POST['pdf_delivery'] = (int) Tools::getValue('pdf_delivery_on');
            $_POST['pdf_invoice'] = (int) Tools::getValue('pdf_invoice_on');
            $_POST['active'] = (int) Tools::getValue('active_on');
            if (!$_POST['send_email']) {
                foreach (Language::getIDs(false) as $idLang) {
                    $_POST['template_'.$idLang] = '';
                }
            }

            return parent::postProcess();
        } elseif (Tools::isSubmit('delete'.$this->table)) {
            $orderState = new OrderState(Tools::getValue('id_order_state'), $this->context->language->id);
            if (!$orderState->isRemovable()) {
                $this->errors[] = $this->l('For security reasons, you cannot delete default order statuses.');
            } else {
                return parent::postProcess();
            }
        } elseif (Tools::isSubmit('submitBulkdelete'.$this->table)) {
            foreach (Tools::getValue($this->table.'Box') as $selection) {
                $orderState = new OrderState((int) $selection, $this->context->language->id);
                if (!$orderState->isRemovable()) {
                    $this->errors[] = $this->l('For security reasons, you cannot delete default order statuses.');
                    break;
                }
            }

            if (!count($this->errors)) {
                return parent::postProcess();
            }
        } else {
            return parent::postProcess();
        }
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
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('Order status'),
                'icon'  => 'icon-time',
            ],
            'input'   => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Status name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                    'hint'     => [
                        $this->l('Order status (e.g. \'Pending\').'),
                        $this->l('Invalid characters: numbers and').' !<>,;?=+()@#"{}_$%:',
                    ],
                ],
                [
                    'type'  => 'file',
                    'label' => $this->l('Icon'),
                    'name'  => 'icon',
                    'hint'  => $this->l('Upload an icon from your computer (File type: .gif, suggested size: 16x16).'),
                ],
                [
                    'type'  => 'color',
                    'label' => $this->l('Color'),
                    'name'  => 'color',
                    'hint'  => $this->l('Status will be highlighted in this color. HTML colors only.').' "lightblue", "#CC6600")',
                ],
                [
                    'type'   => 'checkbox',
                    'name'   => 'logable',
                    'values' => [
                        'query' => [
                            ['id' => 'on', 'name' => $this->l('Consider the associated order as validated.'), 'val' => '1'],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'   => 'checkbox',
                    'name'   => 'invoice',
                    'values' => [
                        'query' => [
                            ['id' => 'on', 'name' => $this->l('Allow a customer to download and view PDF versions of his/her invoices.'), 'val' => '1'],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'   => 'checkbox',
                    'name'   => 'hidden',
                    'values' => [
                        'query' => [
                            ['id' => 'on', 'name' => $this->l('Hide this status in all customer orders.'), 'val' => '1'],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'   => 'checkbox',
                    'name'   => 'send_email',
                    'values' => [
                        'query' => [
                            ['id' => 'on', 'name' => $this->l('Send an email to the customer when his/her order status has changed.'), 'val' => '1'],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'   => 'checkbox',
                    'name'   => 'pdf_invoice',
                    'values' => [
                        'query' => [
                            ['id' => 'on', 'name' => $this->l('Attach invoice PDF to email.'), 'val' => '1'],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'   => 'checkbox',
                    'name'   => 'pdf_delivery',
                    'values' => [
                        'query' => [
                            ['id' => 'on', 'name' => $this->l('Attach delivery slip PDF to email.'), 'val' => '1'],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'   => 'checkbox',
                    'name'   => 'shipped',
                    'values' => [
                        'query' => [
                            ['id' => 'on', 'name' => $this->l('Set the order as shipped.'), 'val' => '1'],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'   => 'checkbox',
                    'name'   => 'paid',
                    'values' => [
                        'query' => [
                            ['id' => 'on', 'name' => $this->l('Set the order as paid.'), 'val' => '1'],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'   => 'checkbox',
                    'name'   => 'delivery',
                    'values' => [
                        'query' => [
                            ['id' => 'on', 'name' => $this->l('Show delivery PDF.'), 'val' => '1'],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'   => 'checkbox',
                    'name'   => 'active',
                    'values' => [
                        'query' => [
                            ['id' => 'on', 'name' => $this->l('Status is active for orders.'), 'val' => '1'],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'    => 'select_template',
                    'label'   => $this->l('Template'),
                    'name'    => 'template',
                    'lang'    => true,
                    'options' => [
                        'query'  => $this->getTemplates(),
                        'id'     => 'id',
                        'name'   => 'name',
                        'folder' => 'folder',
                    ],
                    'hint'    => [
                        $this->l('Only letters, numbers and underscores ("_") are allowed.'),
                        $this->l('Email template for both .html and .txt.'),
                    ],
                ],
            ],
            'submit'  => [
                'title' => $this->l('Save'),
            ],
        ];

        if (Tools::isSubmit('updateorder_state') || Tools::isSubmit('addorder_state')) {
            return $this->renderOrderStatusForm();
        } elseif (Tools::isSubmit('updateorder_return_state') || Tools::isSubmit('addorder_return_state')) {
            return $this->renderOrderReturnsForm();
        } else {
            return parent::renderForm();
        }
    }

    /**
     * Get templates
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function getTemplates()
    {
        $theme = new Theme($this->context->shop->id_theme);
        $defaultPath = '../mails/';
        $themePath = '../themes/'.$theme->directory.'/mails/'; // Mail templates can also be found in the theme folder

        $array = [];
        foreach (Language::getLanguages(false) as $language) {
            $isoCode = $language['iso_code'];

            // If there is no folder for the given iso_code in /mails or in /themes/[theme_name]/mails, we bypass this language
            if (!@filemtime(_PS_ADMIN_DIR_.'/'.$defaultPath.$isoCode) && !@filemtime(_PS_ADMIN_DIR_.'/'.$themePath.$isoCode)) {
                continue;
            }

            $themeTemplatesDir = _PS_ADMIN_DIR_.'/'.$themePath.$isoCode;
            $themeTemplates = is_dir($themeTemplatesDir) ? scandir($themeTemplatesDir) : [];
            // We merge all available emails in one array
            $templates = array_unique(array_merge(scandir(_PS_ADMIN_DIR_.'/'.$defaultPath.$isoCode), $themeTemplates));
            foreach ($templates as $key => $template) {
                if (!strncmp(strrev($template), 'lmth.', 5)) {
                    $searchResult = array_search($template, $themeTemplates);
                    $array[$isoCode][] = [
                        'id'     => substr($template, 0, -5),
                        'name'   => substr($template, 0, -5),
                        'folder' => ((!empty($searchResult) ? $themePath : $defaultPath)),
                    ];
                }
            }
        }

        return $array;
    }

    /**
     * Render order status form
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function renderOrderStatusForm()
    {
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $this->fields_value = [
            'logable_on'      => $this->getFieldValue($obj, 'logable'),
            'invoice_on'      => $this->getFieldValue($obj, 'invoice'),
            'hidden_on'       => $this->getFieldValue($obj, 'hidden'),
            'send_email_on'   => $this->getFieldValue($obj, 'send_email'),
            'shipped_on'      => $this->getFieldValue($obj, 'shipped'),
            'paid_on'         => $this->getFieldValue($obj, 'paid'),
            'delivery_on'     => $this->getFieldValue($obj, 'delivery'),
            'pdf_delivery_on' => $this->getFieldValue($obj, 'pdf_delivery'),
            'pdf_invoice_on'  => $this->getFieldValue($obj, 'pdf_invoice'),
            'active_on'       => $this->getFieldValue($obj, 'active'),
        ];

        if ($this->getFieldValue($obj, 'color') !== false) {
            $this->fields_value['color'] = $this->getFieldValue($obj, 'color');
        } else {
            $this->fields_value['color'] = "#ffffff";
        }

        return parent::renderForm();
    }

    /**
     * Render order returns form
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function renderOrderReturnsForm()
    {
        $helper = $this->initOrderReturnsForm();
        $helper->show_cancel_button = true;

        $back = Tools::safeOutput(Tools::getValue('back', ''));
        if (empty($back)) {
            $back = static::$currentIndex.'&token='.$this->token;
        }
        if (!Validate::isCleanHtml($back)) {
            die(Tools::displayError());
        }

        $helper->back_url = $back;

        $this->fields_form[0]['form'] = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('Return status'),
                'icon'  => 'icon-time',
            ],
            'input'   => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Status name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                    'hint'     => [
                        $this->l('Order\'s return status name.'),
                        $this->l('Invalid characters: numbers and').' !<>,;?=+()@#"�{}_$%:',
                    ],
                ],
                [
                    'type'  => 'color',
                    'label' => $this->l('Color'),
                    'name'  => 'color',
                    'hint'  => $this->l('Status will be highlighted in this color. HTML colors only.').' "lightblue", "#CC6600")',
                ],
                [
                    'type'   => 'checkbox',
                    'name'   => 'active',
                    'values' => [
                        'query' => [
                            ['id' => 'on', 'name' => $this->l('Status is active for return orders.'), 'val' => '1'],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
            ],
            'submit'  => [
                'title' => $this->l('Save'),
            ],
        ];

        return $helper->generateForm($this->fields_form);
    }

    /**
     * Initialize order returns form
     *
     * @return HelperForm
     *
     * @since 1.0.0
     */
    protected function initOrderReturnsForm()
    {
        $idOrderReturnState = (int) Tools::getValue('id_order_return_state');

        // Create Object OrderReturnState
        $orderReturnState = new OrderReturnState($idOrderReturnState);

        //init field form variable for order return form
        $this->fields_form = [];

        //$this->initToolbar();
        $this->getlanguages();
        $helper = new HelperForm();
        $helper->currentIndex = static::$currentIndex;
        $helper->token = $this->token;
        $helper->table = 'order_return_state';
        $helper->identifier = 'id_order_return_state';
        $helper->id = $orderReturnState->id;
        $helper->toolbar_scroll = false;
        $helper->languages = $this->_languages;
        $helper->default_form_language = $this->default_form_language;
        $helper->allow_employee_form_lang = $this->allow_employee_form_lang;

        if ($orderReturnState->id) {
            $helper->fields_value = [
                'name'      => $this->getFieldValue($orderReturnState, 'name'),
                'color'     => $this->getFieldValue($orderReturnState, 'color'),
                'active_on' => $this->getFieldValue($orderReturnState, 'active'),
            ];
        } else {
            $helper->fields_value = [
                'name'      => $this->getFieldValue($orderReturnState, 'name'),
                'color'     => "#ffffff",
                'active_on' => $this->getFieldValue($orderReturnState, 'active'),
            ];
        }

        $helper->toolbar_btn = $this->toolbar_btn;
        $helper->title = $this->l('Edit Return Status');

        return $helper;
    }

    /**
     * Ajax process send order email state
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessSendEmailOrderState()
    {
        $idOrderState = (int) Tools::getValue('id_order_state');

        $sql = 'UPDATE '._DB_PREFIX_.'order_state SET `send_email`= NOT `send_email` WHERE id_order_state='.$idOrderState;
        $result = Db::getInstance()->execute($sql);

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }
    }

    /**
     * Ajax process delivery order state
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessDeliveryOrderState()
    {
        $idOrderState = (int) Tools::getValue('id_order_state');

        $sql = 'UPDATE '._DB_PREFIX_.'order_state SET `delivery`= NOT `delivery` WHERE id_order_state='.$idOrderState;
        $result = Db::getInstance()->execute($sql);

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }
    }

    /**
     * Ajax process invoice order state
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessInvoiceOrderState()
    {
        $idOrderState = (int) Tools::getValue('id_order_state');

        $sql = 'UPDATE '._DB_PREFIX_.'order_state SET `invoice`= NOT `invoice` WHERE id_order_state='.$idOrderState;
        $result = Db::getInstance()->execute($sql);

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }
    }

    /**
     * Ajax process active order state
     *
     * @return void
     *
     * @since 1.1.0
     */
    public function ajaxProcessActiveOrderState()
    {
        $idOrderState = (int) Tools::getValue('id_order_state');

        $sql = 'UPDATE '._DB_PREFIX_.'order_state SET `active`= NOT `active` WHERE id_order_state='.$idOrderState;
        $result = Db::getInstance()->execute($sql);

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }
    }

    /**
     * Ajax process active order state
     *
     * @return void
     *
     * @since 1.1.0
     */
    public function ajaxProcessActiveOrderReturnState()
    {
        $idOrderState = (int) Tools::getValue('id_order_return_state');

        $sql = 'UPDATE '._DB_PREFIX_.'order_return_state SET `active`= NOT `active` WHERE id_order_return_state='.$idOrderState;
        $result = Db::getInstance()->execute($sql);

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }
    }

    /**
     * @param string $key
     * @param string $filter
     *
     * @return array|false
     *
     * @since 1.0.0
     */
    protected function filterToField($key, $filter)
    {
        if ($this->table == 'order_state') {
            $this->initOrderStatutsList();
        } elseif ($this->table == 'order_return_state') {
            $this->initOrdersReturnsList();
        }

        return parent::filterToField($key, $filter);
    }

    /**
     * @return bool
     * @since 1.0.0
     */
    protected function afterImageUpload()
    {
        parent::afterImageUpload();

        if (($idOrderState = (int) Tools::getValue('id_order_state')) &&
            isset($_FILES) && count($_FILES) && file_exists(_PS_ORDER_STATE_IMG_DIR_.$idOrderState.'.gif')
        ) {
            $currentFile = _PS_TMP_IMG_DIR_.'order_state_mini_'.$idOrderState.'_'.$this->context->shop->id.'.gif';

            if (file_exists($currentFile)) {
                unlink($currentFile);
            }
        }

        return true;
    }
}
