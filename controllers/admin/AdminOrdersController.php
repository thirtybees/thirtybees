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
 * Class AdminOrdersControllerCore
 *
 * @since 1.0.0
 */
class AdminOrdersControllerCore extends AdminController
{
    // @codingStandardsIgnoreStart
    /** @var string $toolbar_title */
    public $toolbar_title;
    /** @var array $statuses_array */
    protected $statuses_array = [];
    // @codingStandardsIgnoreEnd

    /**
     * AdminOrdersControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'order';
        $this->className = 'Order';
        $this->lang = false;
        $this->addRowAction('view');
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();

        $this->_select = '
		a.id_currency,
		a.id_order AS id_pdf,
		CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
		osl.`name` AS `osname`,
		os.`color`,
		IF((SELECT so.id_order FROM `'._DB_PREFIX_.'orders` so WHERE so.id_customer = a.id_customer AND so.id_order < a.id_order LIMIT 1) > 0, 0, 1) as new,
		country_lang.name as cname,
		IF(a.valid, 1, 0) badge_success';

        $this->_join = '
		LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`)
		LEFT JOIN `'._DB_PREFIX_.'address` address ON address.id_address = a.id_address_delivery
		LEFT JOIN `'._DB_PREFIX_.'country` country ON address.id_country = country.id_country
		LEFT JOIN `'._DB_PREFIX_.'country_lang` country_lang ON (country.`id_country` = country_lang.`id_country` AND country_lang.`id_lang` = '.(int) $this->context->language->id.')
		LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = a.`current_state`)
		LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int) $this->context->language->id.')';
        $this->_orderBy = 'id_order';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;

        $statuses = OrderState::getOrderStates((int) $this->context->language->id);
        foreach ($statuses as $status) {
            $this->statuses_array[$status['id_order_state']] = $status['name'];
        }

        $this->fields_list = [
            'id_order'  => [
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'reference' => [
                'title' => $this->l('Reference'),
            ],
            'new'       => [
                'title'          => $this->l('New client'),
                'align'          => 'text-center',
                'type'           => 'bool',
                'tmpTableFilter' => true,
                'orderby'        => false,
                'callback'       => 'printNewCustomer',
            ],
            'customer'  => [
                'title'        => $this->l('Customer'),
                'havingFilter' => true,
            ],
        ];

        if (Configuration::get('PS_B2B_ENABLE')) {
            $this->fields_list = array_merge(
                $this->fields_list,
                [
                    'company' => [
                        'title'      => $this->l('Company'),
                        'filter_key' => 'c!company',
                    ],
                ]
            );
        }

        $this->fields_list = array_merge(
            $this->fields_list,
            [
                'total_paid_tax_incl' => [
                    'title'         => $this->l('Total'),
                    'align'         => 'text-right',
                    'type'          => 'price',
                    'currency'      => true,
                    'callback'      => 'setOrderCurrency',
                    'badge_success' => true,
                ],
                'payment'             => [
                    'title' => $this->l('Payment'),
                ],
                'osname'              => [
                    'title'       => $this->l('Status'),
                    'type'        => 'select',
                    'color'       => 'color',
                    'list'        => $this->statuses_array,
                    'filter_key'  => 'os!id_order_state',
                    'filter_type' => 'int',
                    'order_key'   => 'osname',
                ],
                'date_add'            => [
                    'title'      => $this->l('Date'),
                    'align'      => 'text-right',
                    'type'       => 'datetime',
                    'filter_key' => 'a!date_add',
                ],
                'id_pdf'              => [
                    'title'          => $this->l('PDF'),
                    'align'          => 'text-center',
                    'callback'       => 'printPDFIcons',
                    'orderby'        => false,
                    'search'         => false,
                    'remove_onclick' => true,
                ],
            ]
        );

        if (Country::isCurrentlyUsed('country', true)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS(
                '
			SELECT DISTINCT c.id_country, cl.`name`
			FROM `'._DB_PREFIX_.'orders` o
			'.Shop::addSqlAssociation('orders', 'o').'
			INNER JOIN `'._DB_PREFIX_.'address` a ON a.id_address = o.id_address_delivery
			INNER JOIN `'._DB_PREFIX_.'country` c ON a.id_country = c.id_country
			INNER JOIN `'._DB_PREFIX_.'country_lang` cl ON (c.`id_country` = cl.`id_country` AND cl.`id_lang` = '.(int) $this->context->language->id.')
			ORDER BY cl.name ASC'
            );

            $countryArray = [];
            foreach ($result as $row) {
                $countryArray[$row['id_country']] = $row['name'];
            }

            $part1 = array_slice($this->fields_list, 0, 3);
            $part2 = array_slice($this->fields_list, 3);
            $part1['cname'] = [
                'title'       => $this->l('Delivery'),
                'type'        => 'select',
                'list'        => $countryArray,
                'filter_key'  => 'country!id_country',
                'filter_type' => 'int',
                'order_key'   => 'cname',
            ];
            $this->fields_list = array_merge($part1, $part2);
        }

        $this->shopLinkType = 'shop';
        $this->shopShareDatas = Shop::SHARE_ORDER;

        if (Tools::isSubmit('id_order')) {
            // Save context (in order to apply cart rule)
            $order = new Order((int) Tools::getValue('id_order'));
            $this->context->cart = new Cart($order->id_cart);
            $this->context->customer = new Customer($order->id_customer);
        }

        $this->bulk_actions = [
            'updateOrderStatus' => ['text' => $this->l('Change Order Status'), 'icon' => 'icon-refresh'],
        ];

        parent::__construct();
    }

    /**
     * Set Order currency
     *
     * @param $echo
     * @param $tr
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function setOrderCurrency($echo, $tr)
    {
        $order = new Order($tr['id_order']);

        return Tools::displayPrice($echo, (int) $order->id_currency);
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
        parent::initPageHeaderToolbar();

        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_order'] = [
                'href' => static::$currentIndex.'&addorder&token='.$this->token,
                'desc' => $this->l('Add new order', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        if ($this->display == 'add') {
            unset($this->page_header_toolbar_btn['save']);
        }

        if ($this->context->shop->getContext() != Shop::CONTEXT_SHOP && isset($this->page_header_toolbar_btn['new_order'])
            && Shop::isFeatureActive()
        ) {
            unset($this->page_header_toolbar_btn['new_order']);
        }
    }

    /**
     * Render form
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        if ($this->context->shop->getContext() != Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
            $this->errors[] = $this->l('You have to select a shop before creating new orders.');
        }

        $idCart = (int) Tools::getValue('id_cart');
        $cart = new Cart((int) $idCart);
        if ($idCart && !Validate::isLoadedObject($cart)) {
            $this->errors[] = $this->l('This cart does not exists');
        }
        if ($idCart && Validate::isLoadedObject($cart) && !$cart->id_customer) {
            $this->errors[] = $this->l('The cart must have a customer');
        }
        if (count($this->errors)) {
            return;
        }

        parent::renderForm();
        unset($this->toolbar_btn['save']);
        $this->addJqueryPlugin(['autocomplete', 'fancybox', 'typewatch']);

        $defaultsOrderState = [
            'bankwire'       => (int) Configuration::get('PS_OS_BANKWIRE'),
            'cashondelivery' => Configuration::get('PS_OS_COD_VALIDATION') ? (int) Configuration::get('PS_OS_COD_VALIDATION') : (int) Configuration::get('PS_OS_PREPARATION'),
            'other'          => (int) Configuration::get('PS_OS_PAYMENT'),
        ];
        $paymentModules = [];
        foreach (PaymentModule::getInstalledPaymentModules() as $pModule) {
            $paymentModule = Module::getInstanceById((int) $pModule['id_module']);
            if ($paymentModule && Module::isEnabled($paymentModule->name)) {
                $paymentModules[] = $paymentModule;
            }
        }

        $this->context->smarty->assign(
            [
                'recyclable_pack'      => (int) Configuration::get('PS_RECYCLABLE_PACK'),
                'gift_wrapping'        => (int) Configuration::get('PS_GIFT_WRAPPING'),
                'cart'                 => $cart,
                'currencies'           => Currency::getCurrenciesByIdShop($this->context->shop->id),
                'langs'                => Language::getLanguages(true, $this->context->shop->id),
                'payment_modules'      => $paymentModules,
                'order_states'         => OrderState::getOrderStates((int) $this->context->language->id),
                'defaults_order_state' => $defaultsOrderState,
                'show_toolbar'         => $this->show_toolbar,
                'toolbar_btn'          => $this->toolbar_btn,
                'toolbar_scroll'       => $this->toolbar_scroll,
                'PS_CATALOG_MODE'      => Configuration::get('PS_CATALOG_MODE'),
                'title'                => [$this->l('Orders'), $this->l('Create order')],

            ]
        );
        $this->content .= $this->createTemplate('form.tpl')->fetch();
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
        if ($this->display == 'view') {
            /** @var Order $order */
            $order = $this->loadObject();
            $customer = $this->context->customer;

            if (!Validate::isLoadedObject($order)) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders'));
            }

            $this->toolbar_title[] = sprintf($this->l('Order %1$s from %2$s %3$s'), $order->reference, $customer->firstname, $customer->lastname);
            $this->addMetaTitle($this->toolbar_title[count($this->toolbar_title) - 1]);

            if ($order->hasBeenShipped()) {
                $type = $this->l('Return products');
            } elseif ($order->hasBeenPaid()) {
                $type = $this->l('Standard refund');
            } else {
                $type = $this->l('Cancel products');
            }

            if (!$order->hasBeenShipped() && !$this->lite_display) {
                $this->toolbar_btn['new'] = [
                    'short' => 'Create',
                    'href'  => '#',
                    'desc'  => $this->l('Add a product'),
                    'class' => 'add_product',
                ];
            }

            if (Configuration::get('PS_ORDER_RETURN') && !$this->lite_display) {
                $this->toolbar_btn['standard_refund'] = [
                    'short' => 'Create',
                    'href'  => '',
                    'desc'  => $type,
                    'class' => 'process-icon-standardRefund',
                ];
            }

            if ($order->hasInvoice() && !$this->lite_display) {
                $this->toolbar_btn['partial_refund'] = [
                    'short' => 'Create',
                    'href'  => '',
                    'desc'  => $this->l('Partial refund'),
                    'class' => 'process-icon-partialRefund',
                ];
            }
        }
        $res = parent::initToolbar();
        if ($this->context->shop->getContext() != Shop::CONTEXT_SHOP && isset($this->toolbar_btn['new']) && Shop::isFeatureActive()) {
            unset($this->toolbar_btn['new']);
        }

        return $res;
    }

    /**
     * Set Media
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();

        $this->addJqueryUI('ui.datepicker');
        $this->addJS(_PS_JS_DIR_.'vendor/d3.v3.min.js');

        if ($this->tabAccess['edit'] == 1 && $this->display == 'view') {
	        $apiKey = (Configuration::get('TB_GOOGLE_MAPS_API_KEY')) ? 'key='.Configuration::get('TB_GOOGLE_MAPS_API_KEY').'&' : '';
	        $protocol = Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http';
	        $this->addJS($protocol.'://maps.google.com/maps/api/js?'.$apiKey);
            $this->addJS(_PS_JS_DIR_.'admin/orders.js');
            $this->addJS(_PS_JS_DIR_.'tools.js');
            $this->addJqueryPlugin('autocomplete');
        }
    }

    /**
     * Print PDF icons
     *
     * @param int   $idOrder
     * @param array $tr
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function printPDFIcons($idOrder, $tr)
    {
        static $validOrderState = [];

        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        if (!isset($validOrderState[$order->current_state])) {
            $validOrderState[$order->current_state] = Validate::isLoadedObject($order->getCurrentOrderState());
        }

        if (!$validOrderState[$order->current_state]) {
            return '';
        }

        $this->context->smarty->assign(
            [
                'order' => $order,
                'tr'    => $tr,
            ]
        );

        return $this->createTemplate('_print_pdf_icon.tpl')->fetch();
    }

    /**
     * Print new customer
     *
     * @param int   $idOrder
     * @param array $tr
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function printNewCustomer($idOrder, $tr)
    {
        return ($tr['new'] ? $this->l('Yes') : $this->l('No'));
    }

    /**
     * Bulk process update order statuses
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function processBulkUpdateOrderStatus()
    {
        if (Tools::isSubmit('submitUpdateOrderStatus') && ($idOrderState = (int) Tools::getValue('id_order_state'))) {
            if ($this->tabAccess['edit'] !== '1') {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            } else {
                $orderState = new OrderState($idOrderState);

                if (!Validate::isLoadedObject($orderState)) {
                    $this->errors[] = sprintf(Tools::displayError('Order status #%d cannot be loaded'), $idOrderState);
                } else {
                    foreach (Tools::getValue('orderBox') as $idOrder) {
                        $order = new Order((int) $idOrder);
                        if (!Validate::isLoadedObject($order)) {
                            $this->errors[] = sprintf(Tools::displayError('Order #%d cannot be loaded'), $idOrder);
                        } else {
                            $currentOrderState = $order->getCurrentOrderState();
                            if ($currentOrderState->id == $orderState->id) {
                                $this->errors[] = $this->displayWarning(sprintf('Order #%d has already been assigned this status.', $idOrder));
                            } else {
                                $history = new OrderHistory();
                                $history->id_order = $order->id;
                                $history->id_employee = (int) $this->context->employee->id;

                                // Since we have an order there should already be a payment
                                // If there is no payment and the order status is `logable`
                                // then the order payment will be generated automatically
                                $history->changeIdOrderState((int) $orderState->id, $order, !$order->hasInvoice());

                                $carrier = new Carrier($order->id_carrier, $order->id_lang);
                                $customer = new Customer($order->id_customer);
                                if (Validate::isLoadedObject($customer)) {
                                    $firstname = $customer->firstname;
                                    $lastname = $customer->lastname;
                                } else {
                                    $firstname = '';
                                    $lastname = '';
                                }
                                $templateVars = [
                                    '{firstname}'        => $firstname,
                                    '{lastname}'         => $lastname,
                                    '{id_order}'         => $order->id,
                                    '{order_name}'       => $order->getUniqReference(),
                                    '{bankwire_owner}'   => (string) Configuration::get('BANK_WIRE_OWNER'),
                                    '{bankwire_details}' => (string) nl2br(Configuration::get('BANK_WIRE_DETAILS')),
                                    '{bankwire_address}' => (string) nl2br(Configuration::get('BANK_WIRE_ADDRESS')),
                                ];
                                if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                                    $templateVars = [
                                        '{followup}'         => str_replace('@', $order->shipping_number, $carrier->url),
                                        '{shipping_number}'  => $order->shipping_number,
                                    ];
                                }

                                if ($history->addWithemail(true, $templateVars)) {
                                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                                        foreach ($order->getProducts() as $product) {
                                            if (StockAvailable::dependsOnStock($product['product_id'])) {
                                                StockAvailable::synchronize($product['product_id'], (int) $product['id_shop']);
                                            }
                                        }
                                    }
                                } else {
                                    $this->errors[] = sprintf(Tools::displayError('Cannot change status for order #%d.'), $idOrder);
                                }
                            }
                        }
                    }
                }
            }
            if (!count($this->errors)) {
                Tools::redirectAdmin(static::$currentIndex.'&conf=4&token='.$this->token);
            }
        }
    }

    /**
     * Render list
     *
     * @return false|string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        if (Tools::isSubmit('submitBulkupdateOrderStatus'.$this->table)) {
            if (Tools::getIsset('cancel')) {
                Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
            }

            $this->tpl_list_vars['updateOrderStatus_mode'] = true;
            $this->tpl_list_vars['order_statuses'] = $this->statuses_array;
            $this->tpl_list_vars['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
            $this->tpl_list_vars['POST'] = $_POST;
        }

        return parent::renderList();
    }

    /**
     * Post processing
     *
     * @throws PrestaShopException
     * @throws Adapter_Exception
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        // If id_order is sent, we instanciate a new Order object
        if (Tools::isSubmit('id_order') && Tools::getValue('id_order') > 0) {
            $order = new Order(Tools::getValue('id_order'));
            if (!Validate::isLoadedObject($order)) {
                $this->errors[] = Tools::displayError('The order cannot be found within your database.');
            }
            ShopUrl::cacheMainDomainForShop((int) $order->id_shop);
        }

        /* Update shipping number */
        if (Tools::isSubmit('submitShippingNumber') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                $orderCarrier = new OrderCarrier(Tools::getValue('id_order_carrier'));
                if (!Validate::isLoadedObject($orderCarrier)) {
                    $this->errors[] = Tools::displayError('The order carrier ID is invalid.');
                } elseif (!Validate::isTrackingNumber(Tools::getValue('tracking_number'))) {
                    $this->errors[] = Tools::displayError('The tracking number is incorrect.');
                } else {
                    // update shipping number
                    // Keep these two following lines for backward compatibility, remove on 1.6 version
                    $order->shipping_number = Tools::getValue('tracking_number');
                    $order->update();

                    // Update order_carrier
                    $orderCarrier->tracking_number = pSQL(Tools::getValue('tracking_number'));
                    if ($orderCarrier->update()) {
                        // Send mail to customer
                        $customer = new Customer((int) $order->id_customer);
                        $carrier = new Carrier((int) $order->id_carrier, $order->id_lang);
                        if (!Validate::isLoadedObject($customer)) {
                            throw new PrestaShopException('Can\'t load Customer object');
                        }
                        if (!Validate::isLoadedObject($carrier)) {
                            throw new PrestaShopException('Can\'t load Carrier object');
                        }
                        $templateVars = [
                            '{followup}'         => str_replace('@', $order->shipping_number, $carrier->url),
                            '{firstname}'        => $customer->firstname,
                            '{lastname}'         => $customer->lastname,
                            '{id_order}'         => $order->id,
                            '{shipping_number}'  => $order->shipping_number,
                            '{order_name}'       => $order->getUniqReference(),
                            '{bankwire_owner}'   => (string) Configuration::get('BANK_WIRE_OWNER'),
                            '{bankwire_details}' => (string) nl2br(Configuration::get('BANK_WIRE_DETAILS')),
                            '{bankwire_address}' => (string) nl2br(Configuration::get('BANK_WIRE_ADDRESS')),
                        ];
                        if (@Mail::Send(
                            (int) $order->id_lang,
                            'in_transit',
                            Mail::l('Package in transit', (int) $order->id_lang),
                            $templateVars,
                            $customer->email,
                            $customer->firstname.' '.$customer->lastname,
                            null,
                            null,
                            null,
                            null,
                            _PS_MAIL_DIR_,
                            true,
                            (int) $order->id_shop
                        )) {
                            Hook::exec('actionAdminOrdersTrackingNumberUpdate', ['order' => $order, 'customer' => $customer, 'carrier' => $carrier], null, false, true, false, $order->id_shop);
                            Tools::redirectAdmin(static::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                        } else {
                            $this->errors[] = Tools::displayError('An error occurred while sending an email to the customer.');
                        }
                    } else {
                        $this->errors[] = Tools::displayError('The order carrier cannot be updated.');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } /* Change order status, add a new entry in order history and send an e-mail to the customer if needed */
        elseif (Tools::isSubmit('submitState') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                $orderState = new OrderState(Tools::getValue('id_order_state'));

                if (!Validate::isLoadedObject($orderState)) {
                    $this->errors[] = Tools::displayError('The new order status is invalid.');
                } else {
                    $currentOrderState = $order->getCurrentOrderState();
                    if ($currentOrderState->id != $orderState->id) {
                        // Create new OrderHistory
                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->id_employee = (int) $this->context->employee->id;

                        // Since we have an order there should already be a payment
                        // If there is no payment and the order status is `logable`
                        // then the order payment will be generated automatically
                        $useExistingPayment = !$order->hasInvoice();
                        $history->changeIdOrderState((int) $orderState->id, $order, $useExistingPayment);

                        $carrier = new Carrier($order->id_carrier, $order->id_lang);
                        $customer = new Customer($order->id_customer);
                        if (Validate::isLoadedObject($customer)) {
                            $firstname = $customer->firstname;
                            $lastname = $customer->lastname;
                        } else {
                            $firstname = '';
                            $lastname = '';
                        }
                        $templateVars = [
                            '{firstname}'        => $firstname,
                            '{lastname}'         => $lastname,
                            '{id_order}'         => $order->id,
                            '{order_name}'       => $order->getUniqReference(),
                            '{bankwire_owner}'   => (string) Configuration::get('BANK_WIRE_OWNER'),
                            '{bankwire_details}' => (string) nl2br(Configuration::get('BANK_WIRE_DETAILS')),
                            '{bankwire_address}' => (string) nl2br(Configuration::get('BANK_WIRE_ADDRESS')),
                        ];
                        if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                            $templateVars = [
                                '{followup}'         => str_replace('@', $order->shipping_number, $carrier->url),
                                '{shipping_number}'  => $order->shipping_number,
                            ];
                        }

                        // Save all changes
                        if ($history->addWithemail(true, $templateVars)) {
                            // synchronizes quantities if needed..
                            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                                foreach ($order->getProducts() as $product) {
                                    if (StockAvailable::dependsOnStock($product['product_id'])) {
                                        StockAvailable::synchronize($product['product_id'], (int) $product['id_shop']);
                                    }
                                }
                            }

                            Tools::redirectAdmin(static::$currentIndex.'&id_order='.(int) $order->id.'&vieworder&token='.$this->token);
                        }
                        $this->errors[] = Tools::displayError('An error occurred while changing order status, or we were unable to send an email to the customer.');
                    } else {
                        $this->errors[] = Tools::displayError('The order has already been assigned this status.');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } /* Add a new message for the current order and send an e-mail to the customer if needed */
        elseif (Tools::isSubmit('submitMessage') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                $customer = new Customer(Tools::getValue('id_customer'));
                if (!Validate::isLoadedObject($customer)) {
                    $this->errors[] = Tools::displayError('The customer is invalid.');
                } elseif (!Tools::getValue('message')) {
                    $this->errors[] = Tools::displayError('The message cannot be blank.');
                } else {
                    /* Get message rules and and check fields validity */
                    $rules = call_user_func(['Message', 'getValidationRules'], 'Message');
                    foreach ($rules['required'] as $field) {
                        if (($value = Tools::getValue($field)) == false && (string) $value != '0') {
                            if (!Tools::getValue('id_'.$this->table) || $field != 'passwd') {
                                $this->errors[] = sprintf(Tools::displayError('field %s is required.'), $field);
                            }
                        }
                    }
                    foreach ($rules['size'] as $field => $maxLength) {
                        if (Tools::getValue($field) && mb_strlen(Tools::getValue($field)) > $maxLength) {
                            $this->errors[] = sprintf(Tools::displayError('field %1$s is too long (%2$d chars max).'), $field, $maxLength);
                        }
                    }
                    foreach ($rules['validate'] as $field => $function) {
                        if (Tools::getValue($field)) {
                            if (!Validate::$function(htmlentities(Tools::getValue($field), ENT_COMPAT, 'UTF-8'))) {
                                $this->errors[] = sprintf(Tools::displayError('field %s is invalid.'), $field);
                            }
                        }
                    }

                    if (!count($this->errors)) {
                        //check if a thread already exist
                        $idCustomerThread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($customer->email, $order->id);
                        $threadStatus = Tools::getValue('status_msg', 'open');
                        if (!$idCustomerThread) {
                            $customerThread = new CustomerThread();
                            $customerThread->id_contact = 0;
                            $customerThread->id_customer = (int) $order->id_customer;
                            $customerThread->id_shop = (int) $order->id_shop;
                            $customerThread->id_order = (int) $order->id;
                            $customerThread->id_lang = (int) $this->context->language->id;
                            $customerThread->email = $customer->email;
                            $customerThread->status = $threadStatus;
                            $customerThread->token = Tools::passwdGen(12);
                            $customerThread->add();
                        } else {
                            $customerThread = new CustomerThread((int) $idCustomerThread);
                            if ($customerThread->status !== $threadStatus) {
                                $customerThread->status = $threadStatus;
                                $customerThread->update();
                            }
                        }

                        $customerMessage = new CustomerMessage();
                        $customerMessage->id_customer_thread = $customerThread->id;
                        $customerMessage->id_employee = (int) $this->context->employee->id;
                        $customerMessage->message = Tools::getValue('message');
                        $customerMessage->private = Tools::getValue('visibility');

                        if (!$customerMessage->add()) {
                            $this->errors[] = Tools::displayError('An error occurred while saving the message.');
                        } elseif ($customerMessage->private) {
                            Tools::redirectAdmin(static::$currentIndex.'&id_order='.(int) $order->id.'&vieworder&conf=11&token='.$this->token);
                        } else {
                            $message = $customerMessage->message;
                            if (Configuration::get('PS_MAIL_TYPE', null, null, $order->id_shop) != Mail::TYPE_TEXT) {
                                $message = Tools::nl2br($customerMessage->message);
                            }

                            $varsTpl = [
                                '{lastname}'   => $customer->lastname,
                                '{firstname}'  => $customer->firstname,
                                '{id_order}'   => $order->id,
                                '{order_name}' => $order->getUniqReference(),
                                '{message}'    => $message,
                            ];
                            if (@Mail::Send(
                                (int) $order->id_lang,
                                'order_merchant_comment',
                                Mail::l('New message regarding your order', (int) $order->id_lang),
                                $varsTpl,
                                $customer->email,
                                $customer->firstname.' '.$customer->lastname,
                                null,
                                null,
                                null,
                                null,
                                _PS_MAIL_DIR_,
                                true,
                                (int) $order->id_shop
                            )) {
                                Tools::redirectAdmin(static::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=11'.'&token='.$this->token);
                            }
                        }
                        $this->errors[] = Tools::displayError('An error occurred while sending an email to the customer.');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        } /* Partial refund from order */
        elseif (Tools::isSubmit('partialRefund') && isset($order)) {
            if ($this->tabAccess['edit'] == '1') {
                if (Tools::isSubmit('partialRefundProduct') && ($refunds = Tools::getValue('partialRefundProduct')) && is_array($refunds)) {
                    $amount = 0;
                    $orderDetailList = [];
                    $fullQuantityList = [];
                    foreach ($refunds as $idOrderDetail => $amountDetail) {
                        $quantity = Tools::getValue('partialRefundProductQuantity');
                        if (!$quantity[$idOrderDetail]) {
                            continue;
                        }

                        $fullQuantityList[$idOrderDetail] = (int) $quantity[$idOrderDetail];

                        $orderDetailList[$idOrderDetail] = [
                            'quantity'        => (int) $quantity[$idOrderDetail],
                            'id_order_detail' => (int) $idOrderDetail,
                        ];

                        $orderDetail = new OrderDetail((int) $idOrderDetail);
                        if (empty($amountDetail)) {
                            $orderDetailList[$idOrderDetail]['unit_price'] = (!Tools::getValue('TaxMethod') ? $orderDetail->unit_price_tax_excl : $orderDetail->unit_price_tax_incl);
                            $orderDetailList[$idOrderDetail]['amount'] = $orderDetail->unit_price_tax_incl * $orderDetailList[$idOrderDetail]['quantity'];
                        } else {
                            $orderDetailList[$idOrderDetail]['amount']
                                = (float) $amountDetail;
                            $orderDetailList[$idOrderDetail]['unit_price'] = round(
                                $orderDetailList[$idOrderDetail]['amount']
                                / $orderDetailList[$idOrderDetail]['quantity'],
                                _TB_PRICE_DATABASE_PRECISION_
                            );
                        }
                        $amount += $orderDetailList[$idOrderDetail]['amount'];
                        if (!$order->hasBeenDelivered() || ($order->hasBeenDelivered() && Tools::isSubmit('reinjectQuantities')) && $orderDetailList[$idOrderDetail]['quantity'] > 0) {
                            $this->reinjectQuantity($orderDetail, $orderDetailList[$idOrderDetail]['quantity']);
                        }
                    }

                    $shippingCostAmount
                        = (float) Tools::getValue('partialRefundShippingCost') ?
                            (float) Tools::getValue('partialRefundShippingCost') :
                            false;

                    if ($amount == 0 && $shippingCostAmount == 0) {
                        if (!empty($refunds)) {
                            $this->errors[] = Tools::displayError('Please enter a quantity to proceed with your refund.');
                        } else {
                            $this->errors[] = Tools::displayError('Please enter an amount to proceed with your refund.');
                        }

                        return false;
                    }

                    $chosen = false;
                    $voucher = 0;

                    if ((int) Tools::getValue('refund_voucher_off') == 1) {
                        $amount -= $voucher = (float) Tools::getValue('order_discount_price');
                    } elseif ((int) Tools::getValue('refund_voucher_off') == 2) {
                        $chosen = true;
                        $amount = $voucher = (float) Tools::getValue('refund_voucher_choose');
                    }

                    if ($shippingCostAmount > 0) {
                        if (!Tools::getValue('TaxMethod')) {
                            $tax = new Tax();
                            $tax->rate = $order->carrier_tax_rate;
                            $taxCalculator = new TaxCalculator([$tax]);
                            $amount += $taxCalculator->addTaxes($shippingCostAmount);
                        } else {
                            $amount += $shippingCostAmount;
                        }
                    }

                    $orderCarrier = new OrderCarrier((int) $order->getIdOrderCarrier());
                    if (Validate::isLoadedObject($orderCarrier)) {
                        $orderCarrier->weight = (float) $order->getTotalWeight();
                        if ($orderCarrier->update()) {
                            $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $orderCarrier->weight);
                        }
                    }

                    if ($amount >= 0) {
                        if (!OrderSlip::create(
                            $order,
                            $orderDetailList,
                            $shippingCostAmount,
                            $voucher,
                            $chosen,
                            (Tools::getValue('TaxMethod') ? false : true)
                        )) {
                            $this->errors[] = Tools::displayError('You cannot generate a partial credit slip.');
                        } else {
                            Hook::exec('actionOrderSlipAdd', ['order' => $order, 'productList' => $orderDetailList, 'qtyList' => $fullQuantityList], null, false, true, false, $order->id_shop);
                            $customer = new Customer((int) ($order->id_customer));
                            $params['{lastname}'] = $customer->lastname;
                            $params['{firstname}'] = $customer->firstname;
                            $params['{id_order}'] = $order->id;
                            $params['{order_name}'] = $order->getUniqReference();
                            @Mail::Send(
                                (int) $order->id_lang,
                                'credit_slip',
                                Mail::l('New credit slip regarding your order', (int) $order->id_lang),
                                $params,
                                $customer->email,
                                $customer->firstname.' '.$customer->lastname,
                                null,
                                null,
                                null,
                                null,
                                _PS_MAIL_DIR_,
                                true,
                                (int) $order->id_shop
                            );
                        }

                        foreach ($orderDetailList as &$product) {
                            $orderDetail = new OrderDetail((int) $product['id_order_detail']);
                            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                                StockAvailable::synchronize($orderDetail->product_id);
                            }
                        }

                        // Generate voucher
                        if (Tools::isSubmit('generateDiscountRefund') && !count($this->errors) && $amount > 0) {
                            $cartRule = new CartRule();
                            $cartRule->description = sprintf($this->l('Credit slip for order #%d'), $order->id);
                            $languageIds = Language::getIDs(false);
                            foreach ($languageIds as $idLang) {
                                // Define a temporary name
                                $cartRule->name[$idLang] = sprintf('V0C%1$dO%2$d', $order->id_customer, $order->id);
                            }

                            // Define a temporary code
                            $cartRule->code = sprintf('V0C%1$dO%2$d', $order->id_customer, $order->id);
                            $cartRule->quantity = 1;
                            $cartRule->quantity_per_user = 1;

                            // Specific to the customer
                            $cartRule->id_customer = $order->id_customer;
                            $now = time();
                            $cartRule->date_from = date('Y-m-d H:i:s', $now);
                            $cartRule->date_to = date('Y-m-d H:i:s', strtotime('+1 year'));
                            $cartRule->partial_use = 1;
                            $cartRule->active = 1;

                            $cartRule->reduction_amount = $amount;
                            $cartRule->reduction_tax = $order->getTaxCalculationMethod() != PS_TAX_EXC;
                            $cartRule->minimum_amount_currency = $order->id_currency;
                            $cartRule->reduction_currency = $order->id_currency;

                            if (!$cartRule->add()) {
                                $this->errors[] = Tools::displayError('You cannot generate a voucher.');
                            } else {
                                // Update the voucher code and name
                                foreach ($languageIds as $idLang) {
                                    $cartRule->name[$idLang] = sprintf('V%1$dC%2$dO%3$d', $cartRule->id, $order->id_customer, $order->id);
                                }
                                $cartRule->code = sprintf('V%1$dC%2$dO%3$d', $cartRule->id, $order->id_customer, $order->id);

                                if (!$cartRule->update()) {
                                    $this->errors[] = Tools::displayError('You cannot generate a voucher.');
                                } else {
                                    $currency = $this->context->currency;
                                    $customer = new Customer((int) ($order->id_customer));
                                    $params['{lastname}'] = $customer->lastname;
                                    $params['{firstname}'] = $customer->firstname;
                                    $params['{id_order}'] = $order->id;
                                    $params['{order_name}'] = $order->getUniqReference();
                                    $params['{voucher_amount}'] = Tools::displayPrice($cartRule->reduction_amount, $currency, false);
                                    $params['{voucher_num}'] = $cartRule->code;
                                    @Mail::Send(
                                        (int) $order->id_lang,
                                        'voucher',
                                        sprintf(Mail::l('New voucher for your order #%s', (int) $order->id_lang), $order->reference),
                                        $params,
                                        $customer->email,
                                        $customer->firstname.' '.$customer->lastname,
                                        null,
                                        null,
                                        null,
                                        null,
                                        _PS_MAIL_DIR_,
                                        true,
                                        (int) $order->id_shop
                                    );
                                }
                            }
                        }
                    } else {
                        if (!empty($refunds)) {
                            $this->errors[] = Tools::displayError('Please enter a quantity to proceed with your refund.');
                        } else {
                            $this->errors[] = Tools::displayError('Please enter an amount to proceed with your refund.');
                        }
                    }

                    // Redirect if no errors
                    if (!count($this->errors)) {
                        Tools::redirectAdmin(static::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=30&token='.$this->token);
                    }
                } else {
                    $this->errors[] = Tools::displayError('The partial refund data is incorrect.');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        } /* Cancel product from order */
        elseif (Tools::isSubmit('cancelProduct') && isset($order)) {
            if ($this->tabAccess['delete'] === '1') {
                if (!Tools::isSubmit('id_order_detail') && !Tools::isSubmit('id_customization')) {
                    $this->errors[] = Tools::displayError('You must select a product.');
                } elseif (!Tools::isSubmit('cancelQuantity') && !Tools::isSubmit('cancelCustomizationQuantity')) {
                    $this->errors[] = Tools::displayError('You must enter a quantity.');
                } else {
                    $productList = Tools::getValue('id_order_detail');
                    if ($productList) {
                        $productList = array_map('intval', $productList);
                    }

                    $customizationList = Tools::getValue('id_customization');
                    if ($customizationList) {
                        $customizationList = array_map('intval', $customizationList);
                    }

                    $qtyList = Tools::getValue('cancelQuantity');
                    if ($qtyList) {
                        $qtyList = array_map('intval', $qtyList);
                    }

                    $customizationQtyList = Tools::getValue('cancelCustomizationQuantity');
                    if ($customizationQtyList) {
                        $customizationQtyList = array_map('intval', $customizationQtyList);
                    }

                    $fullProductList = $productList;
                    $fullQuantityList = $qtyList;

                    if ($customizationList) {
                        foreach ($customizationList as $key => $idOrderDetail) {
                            $fullProductList[(int) $idOrderDetail] = $idOrderDetail;
                            if (isset($customizationQtyList[$key])) {
                                $fullQuantityList[(int) $idOrderDetail] += $customizationQtyList[$key];
                            }
                        }
                    }

                    if ($productList || $customizationList) {
                        if ($productList) {
                            $idCart = Cart::getCartIdByOrderId($order->id);
                            $customizationQuantities = Customization::countQuantityByCart($idCart);

                            foreach ($productList as $key => $idOrderDetail) {
                                $qtyCancelProduct = abs($qtyList[$key]);
                                if (!$qtyCancelProduct) {
                                    $this->errors[] = Tools::displayError('No quantity has been selected for this product.');
                                }

                                $orderDetail = new OrderDetail($idOrderDetail);
                                $customizationQuantity = 0;
                                if (array_key_exists($orderDetail->product_id, $customizationQuantities) && array_key_exists($orderDetail->product_attribute_id, $customizationQuantities[$orderDetail->product_id])) {
                                    $customizationQuantity = (int) $customizationQuantities[$orderDetail->product_id][$orderDetail->product_attribute_id];
                                }

                                if (($orderDetail->product_quantity - $customizationQuantity - $orderDetail->product_quantity_refunded - $orderDetail->product_quantity_return) < $qtyCancelProduct) {
                                    $this->errors[] = Tools::displayError('An invalid quantity was selected for this product.');
                                }
                            }
                        }
                        if ($customizationList) {
                            $customizationQuantities = Customization::retrieveQuantitiesFromIds(array_keys($customizationList));

                            foreach ($customizationList as $idCustomization => $idOrderDetail) {
                                $qtyCancelProduct = abs($customizationQtyList[$idCustomization]);
                                $customizationQuantity = $customizationQuantities[$idCustomization];

                                if (!$qtyCancelProduct) {
                                    $this->errors[] = Tools::displayError('No quantity has been selected for this product.');
                                }

                                if ($qtyCancelProduct > ($customizationQuantity['quantity'] - ($customizationQuantity['quantity_refunded'] + $customizationQuantity['quantity_returned']))) {
                                    $this->errors[] = Tools::displayError('An invalid quantity was selected for this product.');
                                }
                            }
                        }

                        if (!count($this->errors) && $productList) {
                            foreach ($productList as $key => $idOrderDetail) {
                                $qtyCancelProduct = abs($qtyList[$key]);
                                $orderDetail = new OrderDetail((int) ($idOrderDetail));

                                if (!$order->hasBeenDelivered() || ($order->hasBeenDelivered() && Tools::isSubmit('reinjectQuantities')) && $qtyCancelProduct > 0) {
                                    $this->reinjectQuantity($orderDetail, $qtyCancelProduct);
                                }

                                // Delete product
                                $orderDetail = new OrderDetail((int) $idOrderDetail);
                                if (!$order->deleteProduct($order, $orderDetail, $qtyCancelProduct)) {
                                    $this->errors[] = Tools::displayError('An error occurred while attempting to delete the product.').' <span class="bold">'.$orderDetail->product_name.'</span>';
                                }
                                // Update weight SUM
                                $orderCarrier = new OrderCarrier((int) $order->getIdOrderCarrier());
                                if (Validate::isLoadedObject($orderCarrier)) {
                                    $orderCarrier->weight = (float) $order->getTotalWeight();
                                    if ($orderCarrier->update()) {
                                        $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $orderCarrier->weight);
                                    }
                                }

                                if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && StockAvailable::dependsOnStock($orderDetail->product_id)) {
                                    StockAvailable::synchronize($orderDetail->product_id);
                                }
                                Hook::exec('actionProductCancel', ['order' => $order, 'id_order_detail' => (int) $idOrderDetail], null, false, true, false, $order->id_shop);
                            }
                        }
                        if (!count($this->errors) && $customizationList) {
                            foreach ($customizationList as $idCustomization => $idOrderDetail) {
                                $orderDetail = new OrderDetail((int) ($idOrderDetail));
                                $qtyCancelProduct = abs($customizationQtyList[$idCustomization]);
                                if (!$order->deleteCustomization($idCustomization, $qtyCancelProduct, $orderDetail)) {
                                    $this->errors[] = Tools::displayError('An error occurred while attempting to delete product customization.').' '.$idCustomization;
                                }
                            }
                        }
                        // E-mail params
                        if ((Tools::isSubmit('generateCreditSlip') || Tools::isSubmit('generateDiscount')) && !count($this->errors)) {
                            $customer = new Customer((int) ($order->id_customer));
                            $params['{lastname}'] = $customer->lastname;
                            $params['{firstname}'] = $customer->firstname;
                            $params['{id_order}'] = $order->id;
                            $params['{order_name}'] = $order->getUniqReference();
                        }

                        // Generate credit slip
                        if (Tools::isSubmit('generateCreditSlip') && !count($this->errors)) {
                            $productList = [];
                            $amount = $orderDetail->unit_price_tax_incl * $fullQuantityList[$idOrderDetail];

                            $chosen = false;
                            if ((int) Tools::getValue('refund_total_voucher_off') == 1) {
                                $amount -= $voucher = (float) Tools::getValue('order_discount_price');
                            } elseif ((int) Tools::getValue('refund_total_voucher_off') == 2) {
                                $chosen = true;
                                $amount = $voucher = (float) Tools::getValue('refund_total_voucher_choose');
                            }
                            foreach ($fullProductList as $idOrderDetail) {
                                $orderDetail = new OrderDetail((int) $idOrderDetail);
                                $productList[$idOrderDetail] = [
                                    'id_order_detail' => $idOrderDetail,
                                    'quantity'        => $fullQuantityList[$idOrderDetail],
                                    'unit_price'      => $orderDetail->unit_price_tax_excl,
                                    'amount'          => isset($amount) ? $amount : $orderDetail->unit_price_tax_incl * $fullQuantityList[$idOrderDetail],
                                ];
                            }

                            $shipping = Tools::isSubmit('shippingBack') ? null : false;

                            if (!OrderSlip::create($order, $productList, $shipping, $amount, $chosen)) {
                                $this->errors[] = Tools::displayError('A credit slip cannot be generated. ');
                            } else {
                                Hook::exec('actionOrderSlipAdd', ['order' => $order, 'productList' => $fullProductList, 'qtyList' => $fullQuantityList], null, false, true, false, $order->id_shop);
                                @Mail::Send(
                                    (int) $order->id_lang,
                                    'credit_slip',
                                    Mail::l('New credit slip regarding your order', (int) $order->id_lang),
                                    $params,
                                    $customer->email,
                                    $customer->firstname.' '.$customer->lastname,
                                    null,
                                    null,
                                    null,
                                    null,
                                    _PS_MAIL_DIR_,
                                    true,
                                    (int) $order->id_shop
                                );
                            }
                        }

                        // Generate voucher
                        if (Tools::isSubmit('generateDiscount') && !count($this->errors)) {
                            $cartRule = new CartRule();
                            $languageIds = Language::getIDs((bool) $order);
                            $cartRule->description = sprintf($this->l('Credit card slip for order #%d'), $order->id);
                            foreach ($languageIds as $idLang) {
                                // Define a temporary name
                                $cartRule->name[$idLang] = 'V0C'.(int) $order->id_customer.'O'.(int) $order->id;
                            }
                            // Define a temporary code
                            $cartRule->code = 'V0C'.(int) $order->id_customer.'O'.(int) $order->id;

                            $cartRule->quantity = 1;
                            $cartRule->quantity_per_user = 1;
                            // Specific to the customer
                            $cartRule->id_customer = $order->id_customer;
                            $now = time();
                            $cartRule->date_from = date('Y-m-d H:i:s', $now);
                            $cartRule->date_to = date('Y-m-d H:i:s', $now + (3600 * 24 * 365.25)); /* 1 year */
                            $cartRule->active = 1;

                            $products = $order->getProducts(false, $fullProductList, $fullQuantityList);

                            $total = 0;
                            foreach ($products as $product) {
                                $total += $product['unit_price_tax_incl'] * $product['product_quantity'];
                            }

                            if (Tools::isSubmit('shippingBack')) {
                                $total += $order->total_shipping;
                            }

                            if ((int) Tools::getValue('refund_total_voucher_off') == 1) {
                                $total -= (float) Tools::getValue('order_discount_price');
                            } elseif ((int) Tools::getValue('refund_total_voucher_off') == 2) {
                                $total = (float) Tools::getValue('refund_total_voucher_choose');
                            }

                            $cartRule->reduction_amount = $total;
                            $cartRule->reduction_tax = true;
                            $cartRule->minimum_amount_currency = $order->id_currency;
                            $cartRule->reduction_currency = $order->id_currency;

                            if (!$cartRule->add()) {
                                $this->errors[] = Tools::displayError('You cannot generate a voucher.');
                            } else {
                                // Update the voucher code and name
                                foreach ($languageIds as $idLang) {
                                    $cartRule->name[$idLang] = 'V'.(int) ($cartRule->id).'C'.(int) ($order->id_customer).'O'.$order->id;
                                }
                                $cartRule->code = 'V'.(int) ($cartRule->id).'C'.(int) ($order->id_customer).'O'.$order->id;
                                if (!$cartRule->update()) {
                                    $this->errors[] = Tools::displayError('You cannot generate a voucher.');
                                } else {
                                    $currency = $this->context->currency;
                                    $params['{voucher_amount}'] = Tools::displayPrice($cartRule->reduction_amount, $currency, false);
                                    $params['{voucher_num}'] = $cartRule->code;
                                    @Mail::Send(
                                        (int) $order->id_lang,
                                        'voucher',
                                        sprintf(Mail::l('New voucher for your order #%s', (int) $order->id_lang), $order->reference),
                                        $params,
                                        $customer->email,
                                        $customer->firstname.' '.$customer->lastname,
                                        null,
                                        null,
                                        null,
                                        null,
                                        _PS_MAIL_DIR_,
                                        true,
                                        (int) $order->id_shop
                                    );
                                }
                            }
                        }
                    } else {
                        $this->errors[] = Tools::displayError('No product or quantity has been selected.');
                    }

                    // Redirect if no errors
                    if (!count($this->errors)) {
                        Tools::redirectAdmin(static::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=31&token='.$this->token);
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        } elseif (Tools::isSubmit('messageReaded')) {
            Message::markAsReaded(Tools::getValue('messageReaded'), $this->context->employee->id);
        } elseif (Tools::isSubmit('submitAddPayment') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                $amount = Tools::getValue('payment_amount');
                $currency = new Currency(Tools::getValue('payment_currency'));
                $orderHasInvoice = $order->hasInvoice();
                if ($orderHasInvoice) {
                    $orderInvoice = new OrderInvoice(Tools::getValue('payment_invoice'));
                } else {
                    $orderInvoice = null;
                }

                if (!Validate::isLoadedObject($order)) {
                    $this->errors[] = Tools::displayError('The order cannot be found');
                } elseif (!Validate::isNegativePrice($amount) || !(float) $amount) {
                    $this->errors[] = Tools::displayError('The amount is invalid.');
                } elseif (!Validate::isGenericName(Tools::getValue('payment_method'))) {
                    $this->errors[] = Tools::displayError('The selected payment method is invalid.');
                } elseif (!Validate::isString(Tools::getValue('payment_transaction_id'))) {
                    $this->errors[] = Tools::displayError('The transaction ID is invalid.');
                } elseif (!Validate::isLoadedObject($currency)) {
                    $this->errors[] = Tools::displayError('The selected currency is invalid.');
                } elseif ($orderHasInvoice && !Validate::isLoadedObject($orderInvoice)) {
                    $this->errors[] = Tools::displayError('The invoice is invalid.');
                } elseif (!Validate::isDate(Tools::getValue('payment_date'))) {
                    $this->errors[] = Tools::displayError('The date is invalid');
                } else {
                    if (!$order->addOrderPayment($amount, Tools::getValue('payment_method'), Tools::getValue('payment_transaction_id'), $currency, Tools::getValue('payment_date'), $orderInvoice)) {
                        $this->errors[] = Tools::displayError('An error occurred during payment.');
                    } else {
                        Tools::redirectAdmin(static::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitEditNote')) {
            $note = Tools::getValue('note');
            $orderInvoice = new OrderInvoice((int) Tools::getValue('id_order_invoice'));
            if (Validate::isLoadedObject($orderInvoice) && Validate::isCleanHtml($note)) {
                if ($this->tabAccess['edit'] === '1') {
                    $orderInvoice->note = $note;
                    if ($orderInvoice->save()) {
                        Tools::redirectAdmin(static::$currentIndex.'&id_order='.$orderInvoice->id_order.'&vieworder&conf=4&token='.$this->token);
                    } else {
                        $this->errors[] = Tools::displayError('The invoice note was not saved.');
                    }
                } else {
                    $this->errors[] = Tools::displayError('You do not have permission to edit this.');
                }
            } else {
                $this->errors[] = Tools::displayError('The invoice for edit note was unable to load. ');
            }
        } elseif (Tools::isSubmit('submitAddOrder') && ($idCart = Tools::getValue('id_cart')) &&
            ($moduleName = Tools::getValue('payment_module_name')) &&
            ($idOrderState = Tools::getValue('id_order_state')) && Validate::isModuleName($moduleName)
        ) {
            if ($this->tabAccess['edit'] === '1') {
                if (!Configuration::get('PS_CATALOG_MODE')) {
                    $paymentModule = Module::getInstanceByName($moduleName);
                } else {
                    $paymentModule = new BoOrder();
                }

                $cart = new Cart((int) $idCart);
                $this->context->currency = new Currency((int) $cart->id_currency);
                $this->context->customer = new Customer((int) $cart->id_customer);

                if (($badDelivery = !Address::isCountryActiveById((int) $cart->id_address_delivery))
                    || !Address::isCountryActiveById((int) $cart->id_address_invoice)
                ) {
                    if ($badDelivery) {
                        $this->errors[] = Tools::displayError('This delivery address country is not active.');
                    } else {
                        $this->errors[] = Tools::displayError('This invoice address country is not active.');
                    }
                } else {
                    $employee = new Employee((int) $this->context->cookie->id_employee);
                    $paymentModule->validateOrder(
                        (int) $cart->id,
                        (int) $idOrderState,
                        $cart->getOrderTotal(true, Cart::BOTH),
                        $paymentModule->displayName,
                        $this->l('Manual order -- Employee:').' '.substr($employee->firstname, 0, 1).'. '.$employee->lastname,
                        [],
                        null,
                        false,
                        $cart->secure_key
                    );
                    if ($paymentModule->currentOrder) {
                        Tools::redirectAdmin(static::$currentIndex.'&id_order='.$paymentModule->currentOrder.'&vieworder'.'&token='.$this->token);
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        } elseif ((Tools::isSubmit('submitAddressShipping') || Tools::isSubmit('submitAddressInvoice')) && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                $address = new Address(Tools::getValue('id_address'));
                if (Validate::isLoadedObject($address)) {
                    // Update the address on order
                    if (Tools::isSubmit('submitAddressShipping')) {
                        $order->id_address_delivery = $address->id;
                    } elseif (Tools::isSubmit('submitAddressInvoice')) {
                        $order->id_address_invoice = $address->id;
                    }
                    $order->update();
                    Tools::redirectAdmin(static::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                } else {
                    $this->errors[] = Tools::displayError('This address can\'t be loaded');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitChangeCurrency') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                if (Tools::getValue('new_currency') != $order->id_currency && !$order->valid) {
                    $oldCurrency = new Currency($order->id_currency);
                    $currency = new Currency(Tools::getValue('new_currency'));
                    if (!Validate::isLoadedObject($currency)) {
                        throw new PrestaShopException('Can\'t load Currency object');
                    }

                    // Update order detail amount
                    foreach ($order->getOrderDetailList() as $row) {
                        $orderDetail = new OrderDetail($row['id_order_detail']);
                        $fields = [
                            'ecotax',
                            'product_price',
                            'reduction_amount',
                            'total_shipping_price_tax_excl',
                            'total_shipping_price_tax_incl',
                            'total_price_tax_incl',
                            'total_price_tax_excl',
                            'product_quantity_discount',
                            'purchase_supplier_price',
                            'reduction_amount',
                            'reduction_amount_tax_incl',
                            'reduction_amount_tax_excl',
                            'unit_price_tax_incl',
                            'unit_price_tax_excl',
                            'original_product_price',

                        ];
                        foreach ($fields as $field) {
                            $orderDetail->{$field} = Tools::convertPriceFull($orderDetail->{$field}, $oldCurrency, $currency);
                        }

                        $orderDetail->update();
                        $orderDetail->updateTaxAmount($order);
                    }

                    $idOrderCarrier = (int) $order->getIdOrderCarrier();
                    if ($idOrderCarrier) {
                        $orderCarrier = $orderCarrier = new OrderCarrier((int) $order->getIdOrderCarrier());
                        $orderCarrier->shipping_cost_tax_excl = Tools::convertPriceFull($orderCarrier->shipping_cost_tax_excl, $oldCurrency, $currency);
                        $orderCarrier->shipping_cost_tax_incl = Tools::convertPriceFull($orderCarrier->shipping_cost_tax_incl, $oldCurrency, $currency);
                        $orderCarrier->update();
                    }

                    // Update order && order_invoice amount
                    $fields = [
                        'total_discounts',
                        'total_discounts_tax_incl',
                        'total_discounts_tax_excl',
                        'total_discount_tax_excl',
                        'total_discount_tax_incl',
                        'total_paid',
                        'total_paid_tax_incl',
                        'total_paid_tax_excl',
                        'total_paid_real',
                        'total_products',
                        'total_products_wt',
                        'total_shipping',
                        'total_shipping_tax_incl',
                        'total_shipping_tax_excl',
                        'total_wrapping',
                        'total_wrapping_tax_incl',
                        'total_wrapping_tax_excl',
                    ];

                    $invoices = $order->getInvoicesCollection();
                    if ($invoices) {
                        foreach ($invoices as $invoice) {
                            foreach ($fields as $field) {
                                if (isset($invoice->$field)) {
                                    $invoice->{$field} = Tools::convertPriceFull($invoice->{$field}, $oldCurrency, $currency);
                                }
                            }
                            $invoice->save();
                        }
                    }

                    foreach ($fields as $field) {
                        if (isset($order->$field)) {
                            $order->{$field} = Tools::convertPriceFull($order->{$field}, $oldCurrency, $currency);
                        }
                    }

                    // Update currency in order
                    $order->id_currency = $currency->id;
                    // Update exchange rate
                    $order->conversion_rate = (float) $currency->conversion_rate;
                    $order->update();
                } else {
                    $this->errors[] = Tools::displayError('You cannot change the currency.');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitGenerateInvoice') && isset($order)) {
            if (!Configuration::get('PS_INVOICE', null, null, $order->id_shop)) {
                $this->errors[] = Tools::displayError('Invoice management has been disabled.');
            } elseif ($order->hasInvoice()) {
                $this->errors[] = Tools::displayError('This order already has an invoice.');
            } else {
                $order->setInvoice(true);
                Tools::redirectAdmin(static::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
            }
        } elseif (Tools::isSubmit('submitDeleteVoucher') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                $orderCartRule = new OrderCartRule(Tools::getValue('id_order_cart_rule'));
                if (Validate::isLoadedObject($orderCartRule) && $orderCartRule->id_order == $order->id) {
                    if ($orderCartRule->id_order_invoice) {
                        $orderInvoice = new OrderInvoice($orderCartRule->id_order_invoice);
                        if (!Validate::isLoadedObject($orderInvoice)) {
                            throw new PrestaShopException('Can\'t load Order Invoice object');
                        }

                        // Update amounts of Order Invoice
                        $orderInvoice->total_discount_tax_excl -= $orderCartRule->value_tax_excl;
                        $orderInvoice->total_discount_tax_incl -= $orderCartRule->value;

                        $orderInvoice->total_paid_tax_excl += $orderCartRule->value_tax_excl;
                        $orderInvoice->total_paid_tax_incl += $orderCartRule->value;

                        // Update Order Invoice
                        $orderInvoice->update();
                    }

                    // Update amounts of order
                    $order->total_discounts -= $orderCartRule->value;
                    $order->total_discounts_tax_incl -= $orderCartRule->value;
                    $order->total_discounts_tax_excl -= $orderCartRule->value_tax_excl;

                    $order->total_paid += $orderCartRule->value;
                    $order->total_paid_tax_incl += $orderCartRule->value;
                    $order->total_paid_tax_excl += $orderCartRule->value_tax_excl;

                    // Delete Order Cart Rule and update Order
                    $orderCartRule->delete();
                    $order->update();
                    Tools::redirectAdmin(static::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                } else {
                    $this->errors[] = Tools::displayError('You cannot edit this cart rule.');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitNewVoucher') && isset($order)) {
            if ($this->tabAccess['edit'] === '1') {
                if (!Tools::getValue('discount_name')) {
                    $this->errors[] = Tools::displayError('You must specify a name in order to create a new discount.');
                } else {
                    if ($order->hasInvoice()) {
                        // If the discount is for only one invoice
                        if (!Tools::isSubmit('discount_all_invoices')) {
                            $orderInvoice = new OrderInvoice(Tools::getValue('discount_invoice'));
                            if (!Validate::isLoadedObject($orderInvoice)) {
                                throw new PrestaShopException('Can\'t load Order Invoice object');
                            }
                        }
                    }

                    $cartRules = [];
                    $discountValue = (float) Tools::getValue('discount_value');
                    switch (Tools::getValue('discount_type')) {
                        // Percent type
                        case 1:
                            if ($discountValue < 100) {
                                if (isset($orderInvoice)) {
                                    $cartRules[$orderInvoice->id]['value_tax_incl'] = round(
                                        $orderInvoice->total_paid_tax_incl * $discountValue / 100,
                                        _TB_PRICE_DATABASE_PRECISION_
                                    );
                                    $cartRules[$orderInvoice->id]['value_tax_excl'] = round(
                                        $orderInvoice->total_paid_tax_excl * $discountValue / 100,
                                        _TB_PRICE_DATABASE_PRECISION_
                                    );

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($orderInvoice, $cartRules[$orderInvoice->id]['value_tax_incl'], $cartRules[$orderInvoice->id]['value_tax_excl']);
                                } elseif ($order->hasInvoice()) {
                                    $orderInvoicesCollection = $order->getInvoicesCollection();
                                    foreach ($orderInvoicesCollection as $orderInvoice) {
                                        /** @var OrderInvoice $orderInvoice */
                                        $cartRules[$orderInvoice->id]['value_tax_incl'] = round(
                                            $orderInvoice->total_paid_tax_incl * $discountValue / 100,
                                            _TB_PRICE_DATABASE_PRECISION_
                                        );
                                        $cartRules[$orderInvoice->id]['value_tax_excl'] = round(
                                            $orderInvoice->total_paid_tax_excl * $discountValue / 100,
                                            _TB_PRICE_DATABASE_PRECISION_
                                        );

                                        // Update OrderInvoice
                                        $this->applyDiscountOnInvoice($orderInvoice, $cartRules[$orderInvoice->id]['value_tax_incl'], $cartRules[$orderInvoice->id]['value_tax_excl']);
                                    }
                                } else {
                                    $cartRules[0]['value_tax_incl'] = round(
                                        $order->total_paid_tax_incl * $discountValue / 100,
                                        _TB_PRICE_DATABASE_PRECISION_
                                    );
                                    $cartRules[0]['value_tax_excl'] = round(
                                        $order->total_paid_tax_excl * $discountValue / 100,
                                        _TB_PRICE_DATABASE_PRECISION_
                                    );
                                }
                            } else {
                                $this->errors[] = Tools::displayError('The discount value is invalid.');
                            }
                            break;
                        // Amount type
                        case 2:
                            if (isset($orderInvoice)) {
                                if ($discountValue > $orderInvoice->total_paid_tax_incl) {
                                    $this->errors[] = Tools::displayError('The discount value is greater than the order invoice total.');
                                } else {
                                    $cartRules[$orderInvoice->id]['value_tax_incl'] = round(
                                        $discountValue,
                                        _TB_PRICE_DATABASE_PRECISION_
                                    );
                                    $cartRules[$orderInvoice->id]['value_tax_excl'] = round(
                                        $discountValue / (1 + $order->getTaxesAverageUsed() / 100),
                                        _TB_PRICE_DATABASE_PRECISION_
                                    );

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($orderInvoice, $cartRules[$orderInvoice->id]['value_tax_incl'], $cartRules[$orderInvoice->id]['value_tax_excl']);
                                }
                            } elseif ($order->hasInvoice()) {
                                $orderInvoicesCollection = $order->getInvoicesCollection();
                                foreach ($orderInvoicesCollection as $orderInvoice) {
                                    /** @var OrderInvoice $orderInvoice */
                                    if ($discountValue > $orderInvoice->total_paid_tax_incl) {
                                        $this->errors[] = Tools::displayError('The discount value is greater than the order invoice total.').$orderInvoice->getInvoiceNumberFormatted($this->context->language->id, (int) $order->id_shop).')';
                                    } else {
                                        $cartRules[$orderInvoice->id]['value_tax_incl'] = round(
                                            $discountValue,
                                            _TB_PRICE_DATABASE_PRECISION_
                                        );
                                        $cartRules[$orderInvoice->id]['value_tax_excl'] = round(
                                            $discountValue / (1 + $order->getTaxesAverageUsed() / 100),
                                            _TB_PRICE_DATABASE_PRECISION_
                                        );

                                        // Update OrderInvoice
                                        $this->applyDiscountOnInvoice($orderInvoice, $cartRules[$orderInvoice->id]['value_tax_incl'], $cartRules[$orderInvoice->id]['value_tax_excl']);
                                    }
                                }
                            } else {
                                if ($discountValue > $order->total_paid_tax_incl) {
                                    $this->errors[] = Tools::displayError('The discount value is greater than the order total.');
                                } else {
                                    $cartRules[0]['value_tax_incl'] = round(
                                        $discountValue,
                                        _TB_PRICE_DATABASE_PRECISION_
                                    );
                                    $cartRules[0]['value_tax_excl'] = round(
                                        $discountValue / (1 + $order->getTaxesAverageUsed() / 100),
                                        _TB_PRICE_DATABASE_PRECISION_
                                    );
                                }
                            }
                            break;
                        // Free shipping type
                        case 3:
                            if (isset($orderInvoice)) {
                                if ($orderInvoice->total_shipping_tax_incl > 0) {
                                    $cartRules[$orderInvoice->id]['value_tax_incl'] = $orderInvoice->total_shipping_tax_incl;
                                    $cartRules[$orderInvoice->id]['value_tax_excl'] = $orderInvoice->total_shipping_tax_excl;

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($orderInvoice, $cartRules[$orderInvoice->id]['value_tax_incl'], $cartRules[$orderInvoice->id]['value_tax_excl']);
                                }
                            } elseif ($order->hasInvoice()) {
                                $orderInvoicesCollection = $order->getInvoicesCollection();
                                foreach ($orderInvoicesCollection as $orderInvoice) {
                                    /** @var OrderInvoice $orderInvoice */
                                    if ($orderInvoice->total_shipping_tax_incl <= 0) {
                                        continue;
                                    }
                                    $cartRules[$orderInvoice->id]['value_tax_incl'] = $orderInvoice->total_shipping_tax_incl;
                                    $cartRules[$orderInvoice->id]['value_tax_excl'] = $orderInvoice->total_shipping_tax_excl;

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($orderInvoice, $cartRules[$orderInvoice->id]['value_tax_incl'], $cartRules[$orderInvoice->id]['value_tax_excl']);
                                }
                            } else {
                                $cartRules[0]['value_tax_incl'] = $order->total_shipping_tax_incl;
                                $cartRules[0]['value_tax_excl'] = $order->total_shipping_tax_excl;
                            }
                            break;
                        default:
                            $this->errors[] = Tools::displayError('The discount type is invalid.');
                    }

                    $res = true;
                    foreach ($cartRules as &$cartRule) {
                        $cartRuleObj = new CartRule();
                        $cartRuleObj->date_from = date('Y-m-d H:i:s', strtotime('-1 hour', strtotime($order->date_add)));
                        $cartRuleObj->date_to = date('Y-m-d H:i:s', strtotime('+1 hour'));
                        $cartRuleObj->name[Configuration::get('PS_LANG_DEFAULT')] = Tools::getValue('discount_name');
                        $cartRuleObj->quantity = 0;
                        $cartRuleObj->quantity_per_user = 1;
                        if (Tools::getValue('discount_type') == 1) {
                            $cartRuleObj->reduction_percent = $discountValue;
                        } elseif (Tools::getValue('discount_type') == 2) {
                            $cartRuleObj->reduction_amount = $cartRule['value_tax_excl'];
                        } elseif (Tools::getValue('discount_type') == 3) {
                            $cartRuleObj->free_shipping = 1;
                        }
                        $cartRuleObj->active = 0;
                        if ($res = $cartRuleObj->add()) {
                            $cartRule['id'] = $cartRuleObj->id;
                        } else {
                            break;
                        }
                    }

                    if ($res) {
                        foreach ($cartRules as $idOrderInvoice => $cartRule) {
                            // Create OrderCartRule
                            $orderCartRule = new OrderCartRule();
                            $orderCartRule->id_order = $order->id;
                            $orderCartRule->id_cart_rule = $cartRule['id'];
                            $orderCartRule->id_order_invoice = $idOrderInvoice;
                            $orderCartRule->name = Tools::getValue('discount_name');
                            $orderCartRule->value = $cartRule['value_tax_incl'];
                            $orderCartRule->value_tax_excl = $cartRule['value_tax_excl'];
                            $res &= $orderCartRule->add();

                            $order->total_discounts += $orderCartRule->value;
                            $order->total_discounts_tax_incl += $orderCartRule->value;
                            $order->total_discounts_tax_excl += $orderCartRule->value_tax_excl;
                            $order->total_paid -= $orderCartRule->value;
                            $order->total_paid_tax_incl -= $orderCartRule->value;
                            $order->total_paid_tax_excl -= $orderCartRule->value_tax_excl;
                        }

                        // Update Order
                        $res &= $order->update();
                    }

                    if ($res) {
                        Tools::redirectAdmin(static::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                    } else {
                        $this->errors[] = Tools::displayError('An error occurred during the OrderCartRule creation');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('sendStateEmail') && Tools::getValue('sendStateEmail') > 0 && Tools::getValue('id_order') > 0) {
            if ($this->tabAccess['edit'] === '1') {
                $orderState = new OrderState((int) Tools::getValue('sendStateEmail'));

                if (!Validate::isLoadedObject($orderState)) {
                    $this->errors[] = Tools::displayError('An error occurred while loading order status.');
                } else {
                    $history = new OrderHistory((int) Tools::getValue('id_order_history'));

                    $carrier = new Carrier($order->id_carrier, $order->id_lang);
                    $customer = new Customer($order->id_customer);
                    if (Validate::isLoadedObject($customer)) {
                        $firstname = $customer->firstname;
                        $lastname = $customer->lastname;
                    } else {
                        $firstname = '';
                        $lastname = '';
                    }
                    $templateVars = [
                        '{firstname}'        => $firstname,
                        '{lastname}'         => $lastname,
                        '{id_order}'         => $order->id,
                        '{order_name}'       => $order->getUniqReference(),
                        '{bankwire_owner}'   => (string) Configuration::get('BANK_WIRE_OWNER'),
                        '{bankwire_details}' => (string) nl2br(Configuration::get('BANK_WIRE_DETAILS')),
                        '{bankwire_address}' => (string) nl2br(Configuration::get('BANK_WIRE_ADDRESS')),
                    ];
                    if ($orderState->id == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                        $templateVars = array_merge($templateVars, [
                            '{followup}'         => str_replace('@', $order->shipping_number, $carrier->url),
                            '{shipping_number}'  => $order->shipping_number,
                        ]);
                    }

                    if ($history->sendEmail($order, $templateVars)) {
                        Tools::redirectAdmin(static::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=10&token='.$this->token);
                    } else {
                        $this->errors[] = Tools::displayError('An error occurred while sending the e-mail to the customer.');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }

        parent::postProcess();
    }

    /**
     * Render KPIs
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function renderKpis()
    {
        $time = time();
        $kpis = [];

        /* The data generation is located in AdminStatsControllerCore */

        $helper = new HelperKpi();
        $helper->id = 'box-conversion-rate';
        $helper->icon = 'icon-sort-by-attributes-alt';
        //$helper->chart = true;
        $helper->color = 'color1';
        $helper->title = $this->l('Conversion Rate', null, null, false);
        $helper->subtitle = $this->l('30 days', null, null, false);
        if (ConfigurationKPI::get('CONVERSION_RATE') !== false) {
            $helper->value = ConfigurationKPI::get('CONVERSION_RATE');
        }
        if (ConfigurationKPI::get('CONVERSION_RATE_CHART') !== false) {
            $helper->data = ConfigurationKPI::get('CONVERSION_RATE_CHART');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=conversion_rate';
        $helper->refresh = (bool) (ConfigurationKPI::get('CONVERSION_RATE_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-carts';
        $helper->icon = 'icon-shopping-cart';
        $helper->color = 'color2';
        $helper->title = $this->l('Abandoned Carts', null, null, false);
        $helper->subtitle = $this->l('Today', null, null, false);
        $helper->href = $this->context->link->getAdminLink('AdminCarts').'&action=filterOnlyAbandonedCarts';
        if (ConfigurationKPI::get('ABANDONED_CARTS') !== false) {
            $helper->value = ConfigurationKPI::get('ABANDONED_CARTS');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=abandoned_cart';
        $helper->refresh = (bool) (ConfigurationKPI::get('ABANDONED_CARTS_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-average-order';
        $helper->icon = 'icon-money';
        $helper->color = 'color3';
        $helper->title = $this->l('Average Order Value', null, null, false);
        $helper->subtitle = $this->l('30 days', null, null, false);
        if (ConfigurationKPI::get('AVG_ORDER_VALUE') !== false) {
            $helper->value = sprintf($this->l('%s tax excl.'), ConfigurationKPI::get('AVG_ORDER_VALUE'));
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=average_order_value';
        $helper->refresh = (bool) (ConfigurationKPI::get('AVG_ORDER_VALUE_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-net-profit-visit';
        $helper->icon = 'icon-user';
        $helper->color = 'color4';
        $helper->title = $this->l('Net Profit per Visit', null, null, false);
        $helper->subtitle = $this->l('30 days', null, null, false);
        if (ConfigurationKPI::get('NETPROFIT_VISIT') !== false) {
            $helper->value = ConfigurationKPI::get('NETPROFIT_VISIT');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=netprofit_visit';
        $helper->refresh = (bool) (ConfigurationKPI::get('NETPROFIT_VISIT_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpiRow();
        $helper->kpis = $kpis;

        return $helper->generate();
    }

    /**
     * Render view
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderView()
    {
        $order = new Order(Tools::getValue('id_order'));
        if (!Validate::isLoadedObject($order)) {
            $this->errors[] = Tools::displayError('The order cannot be found within your database.');
        }

        $customer = new Customer($order->id_customer);
        $carrier = new Carrier($order->id_carrier);
        $products = $this->getProducts($order);
        $currency = new Currency((int) $order->id_currency);
        // Carrier module call
        $carrierModuleCall = null;
        if ($carrier->is_module) {
            $module = Module::getInstanceByName($carrier->external_module_name);
            if (!Validate::isLoadedObject($module)) {
                $carrier->shipping_external = false;
                $carrier->external_module_name = '';
                $carrier->is_module = false;
                try {
                    $carrier->save();
                } catch (PrestaShopException $e) {
                    $this->context->controller->errors[] = $e->getMessage();
                }
            }

            if (method_exists($module, 'displayInfoByCart')) {
                $carrierModuleCall = call_user_func([$module, 'displayInfoByCart'], $order->id_cart);
            }
        }

        // Retrieve addresses information
        $addressInvoice = new Address($order->id_address_invoice, $this->context->language->id);
        if (Validate::isLoadedObject($addressInvoice) && $addressInvoice->id_state) {
            $invoiceState = new State((int) $addressInvoice->id_state);
        }

        if ($order->id_address_invoice == $order->id_address_delivery) {
            $addressDelivery = $addressInvoice;
            if (isset($invoiceState)) {
                $deliveryState = $invoiceState;
            }
        } else {
            $addressDelivery = new Address($order->id_address_delivery, $this->context->language->id);
            if (Validate::isLoadedObject($addressDelivery) && $addressDelivery->id_state) {
                $deliveryState = new State((int) ($addressDelivery->id_state));
            }
        }

        $this->toolbar_title = sprintf($this->l('Order #%1$d (%2$s) - %3$s %4$s'), $order->id, $order->reference, $customer->firstname, $customer->lastname);
        if (Shop::isFeatureActive()) {
            $shop = new Shop((int) $order->id_shop);
            $this->toolbar_title .= ' - '.sprintf($this->l('Shop: %s'), $shop->name);
        }

        // gets warehouses to ship products, if and only if advanced stock management is activated
        $warehouseList = null;

        $orderDetails = $order->getOrderDetailList();
        foreach ($orderDetails as $orderDetail) {
            $product = new Product($orderDetail['product_id']);

            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
                && $product->advanced_stock_management
            ) {
                $warehouses = Warehouse::getWarehousesByProductId($orderDetail['product_id'], $orderDetail['product_attribute_id']);
                foreach ($warehouses as $warehouse) {
                    if (!isset($warehouseList[$warehouse['id_warehouse']])) {
                        $warehouseList[$warehouse['id_warehouse']] = $warehouse;
                    }
                }
            }
        }

        $paymentMethods = [];
        foreach (PaymentModule::getInstalledPaymentModules() as $payment) {
            $module = Module::getInstanceByName($payment['name']);
            if (Validate::isLoadedObject($module) && $module->active) {
                $paymentMethods[] = $module->displayName;
            }
        }

        // display warning if there are products out of stock
        $displayOutOfStockWarning = false;
        $currentOrderState = $order->getCurrentOrderState();
        if (Configuration::get('PS_STOCK_MANAGEMENT') && (!Validate::isLoadedObject($currentOrderState) || ($currentOrderState->delivery != 1 && $currentOrderState->shipped != 1))) {
            $displayOutOfStockWarning = true;
        }

        // products current stock (from stock_available)
        foreach ($products as &$product) {
            // Get total customized quantity for current product
            $customizedProductQuantity = 0;

            if (is_array($product['customizedDatas'])) {
                foreach ($product['customizedDatas'] as $customizationPerAddress) {
                    foreach ($customizationPerAddress as $customizationId => $customization) {
                        $customizedProductQuantity += (int) $customization['quantity'];
                    }
                }
            }

            $product['customized_product_quantity'] = $customizedProductQuantity;
            $product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], $product['id_shop']);
            $resume = OrderSlip::getProductSlipResume($product['id_order_detail']);
            $product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
            $product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
            $product['amount_refundable_tax_incl'] = $product['total_price_tax_incl'] - $resume['amount_tax_incl'];
            $product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl'], $currency);
            $product['refund_history'] = OrderSlip::getProductSlipDetail($product['id_order_detail']);
            $product['return_history'] = OrderReturn::getProductReturnDetail($product['id_order_detail']);

            // if the current stock requires a warning
            if ($product['current_stock'] <= 0 && $displayOutOfStockWarning) {
                $this->displayWarning($this->l('This product is out of stock: ').' '.$product['product_name']);
            }
            if ($product['id_warehouse'] != 0) {
                $warehouse = new Warehouse((int) $product['id_warehouse']);
                $product['warehouse_name'] = $warehouse->name;
                $warehouseLocation = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse']);
                if (!empty($warehouseLocation)) {
                    $product['warehouse_location'] = $warehouseLocation;
                } else {
                    $product['warehouse_location'] = false;
                }
            } else {
                $product['warehouse_name'] = '--';
                $product['warehouse_location'] = false;
            }
        }

        $gender = new Gender((int) $customer->id_gender, $this->context->language->id);

        $history = $order->getHistory($this->context->language->id);

        foreach ($history as &$orderState) {
            $orderState['text-color'] = Tools::getBrightness($orderState['color']) < 128 ? 'white' : 'black';
        }

        // Smarty assign
        $this->tpl_view_vars = [
            'order'                        => $order,
            'cart'                         => new Cart($order->id_cart),
            'customer'                     => $customer,
            'gender'                       => $gender,
            'customer_addresses'           => $customer->getAddresses($this->context->language->id),
            'addresses'                    => [
                'delivery'      => $addressDelivery,
                'deliveryState' => isset($deliveryState) ? $deliveryState : null,
                'invoice'       => $addressInvoice,
                'invoiceState'  => isset($invoiceState) ? $invoiceState : null,
            ],
            'customerStats'                => $customer->getStats(),
            'products'                     => $products,
            'discounts'                    => $order->getCartRules(),
            'orders_total_paid_tax_incl'   => $order->getOrdersTotalPaid(), // Get the sum of total_paid_tax_incl of the order with similar reference
            'total_paid'                   => $order->getTotalPaid(),
            'returns'                      => OrderReturn::getOrdersReturn($order->id_customer, $order->id),
            'customer_thread_message'      => CustomerThread::getCustomerMessages($order->id_customer, null, $order->id),
            'orderMessages'                => OrderMessage::getOrderMessages($order->id_lang),
            'messages'                     => CustomerMessage::getMessagesByOrderId($order->id, false),
            'carrier'                      => new Carrier($order->id_carrier),
            'history'                      => $history,
            'states'                       => OrderState::getOrderStates($this->context->language->id),
            'warehouse_list'               => $warehouseList,
            'sources'                      => ConnectionsSource::getOrderSources($order->id),
            'currentState'                 => $order->getCurrentOrderState(),
            'currency'                     => new Currency($order->id_currency),
            'currencies'                   => Currency::getCurrenciesByIdShop($order->id_shop),
            'previousOrder'                => $order->getPreviousOrderId(),
            'nextOrder'                    => $order->getNextOrderId(),
            'current_index'                => static::$currentIndex,
            'carrierModuleCall'            => $carrierModuleCall,
            'iso_code_lang'                => $this->context->language->iso_code,
            'id_lang'                      => $this->context->language->id,
            'can_edit'                     => ($this->tabAccess['edit'] == 1),
            'current_id_lang'              => $this->context->language->id,
            'invoices_collection'          => $order->getInvoicesCollection(),
            'not_paid_invoices_collection' => $order->getNotPaidInvoicesCollection(),
            'payment_methods'              => $paymentMethods,
            'invoice_management_active'    => Configuration::get('PS_INVOICE', null, null, $order->id_shop),
            'display_warehouse'            => (int) Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'),
            'HOOK_CONTENT_ORDER'           => Hook::exec(
                'displayAdminOrderContentOrder',
                [
                    'order'    => $order,
                    'products' => $products,
                    'customer' => $customer,
                ]
            ),
            'HOOK_CONTENT_SHIP'            => Hook::exec(
                'displayAdminOrderContentShip',
                [
                    'order'    => $order,
                    'products' => $products,
                    'customer' => $customer,
                ]
            ),
            'HOOK_TAB_ORDER'               => Hook::exec(
                'displayAdminOrderTabOrder',
                [
                    'order'    => $order,
                    'products' => $products,
                    'customer' => $customer,
                ]
            ),
            'HOOK_TAB_SHIP'                => Hook::exec(
                'displayAdminOrderTabShip', [
                    'order'    => $order,
                    'products' => $products,
                    'customer' => $customer,
                ]
            ),
        ];

        return parent::renderView();
    }

    /**
     * Ajax process search products
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessSearchProducts()
    {
        $this->context->customer = new Customer((int) Tools::getValue('id_customer'));
        $currency = new Currency((int) Tools::getValue('id_currency'));
        if ($products = Product::searchByName((int) $this->context->language->id, pSQL(Tools::getValue('product_search')))) {
            $decimals = 0;
            if ($currency->decimals) {
                $decimals = Configuration::get('PS_PRICE_DISPLAY_PRECISION');
            }
            foreach ($products as &$product) {
                // Formatted price
                $product['formatted_price'] = Tools::displayPrice(Tools::convertPrice($product['price_tax_incl'], $currency), $currency);
                // Concret price
                $product['price_tax_incl'] = Tools::ps_round(
                    Tools::convertPrice($product['price_tax_incl'], $currency),
                    $decimals
                );
                $product['price_tax_excl'] = Tools::ps_round(
                    Tools::convertPrice($product['price_tax_excl'], $currency),
                    $decimals
                );
                $productObj = new Product((int) $product['id_product'], false, (int) $this->context->language->id);
                $combinations = [];
                $attributes = $productObj->getAttributesGroups((int) $this->context->language->id);

                // Tax rate for this customer
                if (Tools::isSubmit('id_address')) {
                    $product['tax_rate'] = $productObj->getTaxesRate(new Address(Tools::getValue('id_address')));
                }

                $product['warehouse_list'] = [];

                foreach ($attributes as $attribute) {
                    if (!isset($combinations[$attribute['id_product_attribute']]['attributes'])) {
                        $combinations[$attribute['id_product_attribute']]['attributes'] = '';
                    }
                    $combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'].' - ';
                    $combinations[$attribute['id_product_attribute']]['id_product_attribute'] = (int)$attribute['id_product_attribute'];
                    $combinations[$attribute['id_product_attribute']]['default_on'] = (int)$attribute['default_on'];
                    if (!isset($combinations[$attribute['id_product_attribute']]['price'])) {
                        $priceTaxIncl = Product::getPriceStatic((int) $product['id_product'], true, $attribute['id_product_attribute']);
                        $priceTaxExcl = Product::getPriceStatic((int) $product['id_product'], false, $attribute['id_product_attribute']);
                        $combinations[$attribute['id_product_attribute']]['price_tax_incl'] = Tools::ps_round(
                            Tools::convertPrice($priceTaxIncl, $currency),
                            $decimals
                        );
                        $combinations[$attribute['id_product_attribute']]['price_tax_excl'] = Tools::ps_round(
                            Tools::convertPrice($priceTaxExcl, $currency),
                            $decimals
                        );
                        $combinations[$attribute['id_product_attribute']]['formatted_price'] = Tools::displayPrice(Tools::convertPrice($priceTaxExcl, $currency), $currency);
                    }
                    if (!isset($combinations[$attribute['id_product_attribute']]['qty_in_stock'])) {
                        $combinations[$attribute['id_product_attribute']]['qty_in_stock'] = StockAvailable::getQuantityAvailableByProduct((int) $product['id_product'], $attribute['id_product_attribute'], (int) $this->context->shop->id);
                    }

                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int) $product['advanced_stock_management'] == 1) {
                        $product['warehouse_list'][$attribute['id_product_attribute']] = Warehouse::getProductWarehouseList($product['id_product'], $attribute['id_product_attribute']);
                    } else {
                        $product['warehouse_list'][$attribute['id_product_attribute']] = [];
                    }

                    $product['stock'][$attribute['id_product_attribute']] = Product::getRealQuantity($product['id_product'], $attribute['id_product_attribute']);
                }

                if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int) $product['advanced_stock_management'] == 1) {
                    $product['warehouse_list'][0] = Warehouse::getProductWarehouseList($product['id_product']);
                } else {
                    $product['warehouse_list'][0] = [];
                }

                $product['stock'][0] = StockAvailable::getQuantityAvailableByProduct((int) $product['id_product'], 0, (int) $this->context->shop->id);

                foreach ($combinations as &$combination) {
                    $combination['attributes'] = rtrim($combination['attributes'], ' - ');
                }
                $product['combinations'] = $combinations;

                if ($product['customizable']) {
                    $productInstance = new Product((int) $product['id_product']);
                    $product['customization_fields'] = $productInstance->getCustomizationFields($this->context->language->id);
                }
            }

            $toReturn = [
                'products' => $products,
                'found'    => true,
            ];
        } else {
            $toReturn = ['found' => false];
        }

        $this->content = json_encode($toReturn);
    }

    /**
     * Ajax process send mail validate order
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessSendMailValidateOrder()
    {
        if ($this->tabAccess['edit'] === '1') {
            $cart = new Cart((int) Tools::getValue('id_cart'));
            if (Validate::isLoadedObject($cart)) {
                $customer = new Customer((int) $cart->id_customer);
                if (Validate::isLoadedObject($customer)) {
                    $mailVars = [
                        '{order_link}' => $this->context->link->getPageLink('order', false, (int) $cart->id_lang, 'step=3&recover_cart='.(int) $cart->id.'&token_cart='.md5(_COOKIE_KEY_.'recover_cart_'.(int) $cart->id)),
                        '{firstname}'  => $customer->firstname,
                        '{lastname}'   => $customer->lastname,
                    ];
                    if (Mail::Send(
                        (int) $cart->id_lang,
                        'backoffice_order',
                        Mail::l('Process the payment of your order', (int) $cart->id_lang),
                        $mailVars,
                        $customer->email,
                        $customer->firstname.' '.$customer->lastname,
                        null,
                        null,
                        null,
                        null,
                        _PS_MAIL_DIR_,
                        true,
                        $cart->id_shop
                    )) {
                        $this->ajaxDie(json_encode(['errors' => false, 'result' => $this->l('The email was sent to your customer.')]));
                    }
                }
            }
            $this->content = json_encode(['errors' => true, 'result' => $this->l('Error in sending the email to your customer.')]);
        }
    }

    /**
     * Ajax process add product on order
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessAddProductOnOrder()
    {
        // Load object
        $order = new Order((int) Tools::getValue('id_order'));
        if (!Validate::isLoadedObject($order)) {
            $this->ajaxDie(
                json_encode(
                    [
                        'result' => false,
                        'error'  => Tools::displayError('The order object cannot be loaded.'),
                    ]
                )
            );
        }

        $oldCartRules = $this->context->cart->getCartRules();

        if ($order->hasBeenShipped()) {
            $this->ajaxDie(
                json_encode(
                    [
                        'result' => false,
                        'error'  => Tools::displayError('You cannot add products to delivered orders. '),
                    ]
                )
            );
        }

        $productInformations = $_POST['add_product'];
        if (isset($_POST['add_invoice'])) {
            $invoiceInformations = $_POST['add_invoice'];
        } else {
            $invoiceInformations = [];
        }
        $product = new Product($productInformations['product_id'], false, $order->id_lang);
        if (!Validate::isLoadedObject($product)) {
            $this->ajaxDie(
                json_encode(
                    [
                        'result' => false,
                        'error'  => Tools::displayError('The product object cannot be loaded.'),
                    ]
                )
            );
        }

        if (isset($productInformations['product_attribute_id']) && $productInformations['product_attribute_id']) {
            $combination = new Combination($productInformations['product_attribute_id']);
            if (!Validate::isLoadedObject($combination)) {
                $this->ajaxDie(
                    json_encode(
                        [
                            'result' => false,
                            'error'  => Tools::displayError('The combination object cannot be loaded.'),
                        ]
                    )
                );
            }
        }

        // Total method
        $totalMethod = Cart::BOTH_WITHOUT_SHIPPING;

        // Create new cart
        $cart = new Cart();
        $cart->id_shop_group = $order->id_shop_group;
        $cart->id_shop = $order->id_shop;
        $cart->id_customer = $order->id_customer;
        $cart->id_carrier = $order->id_carrier;
        $cart->id_address_delivery = $order->id_address_delivery;
        $cart->id_address_invoice = $order->id_address_invoice;
        $cart->id_currency = $order->id_currency;
        $cart->id_lang = $order->id_lang;
        $cart->secure_key = $order->secure_key;

        // Save new cart
        $cart->add();

        // Save context (in order to apply cart rule)
        $this->context->cart = $cart;
        $this->context->customer = new Customer($order->id_customer);

        // always add taxes even if there are not displayed to the customer
        $useTaxes = true;

        $initialProductPriceTaxIncl = Product::getPriceStatic(
            $product->id,
            $useTaxes,
            isset($combination) ? $combination->id : null,
            _TB_PRICE_DATABASE_PRECISION_,
            null,
            false,
            true,
            1,
            false,
            $order->id_customer,
            $cart->id,
            $order->{Configuration::get('PS_TAX_ADDRESS_TYPE', null, null, $order->id_shop)}
        );

        // Creating specific price if needed
        if ((string) $productInformations['product_price_tax_incl']
            !== (string) $initialProductPriceTaxIncl) {
            $specificPrice = new SpecificPrice();
            $specificPrice->id_shop = 0;
            $specificPrice->id_shop_group = 0;
            $specificPrice->id_currency = 0;
            $specificPrice->id_country = 0;
            $specificPrice->id_group = 0;
            $specificPrice->id_customer = $order->id_customer;
            $specificPrice->id_product = $product->id;
            if (isset($combination)) {
                $specificPrice->id_product_attribute = $combination->id;
            } else {
                $specificPrice->id_product_attribute = 0;
            }
            $specificPrice->price = $productInformations['product_price_tax_excl'];
            $specificPrice->from_quantity = 1;
            $specificPrice->reduction = 0;
            $specificPrice->reduction_type = 'amount';
            $specificPrice->reduction_tax = 0;
            $specificPrice->from = '0000-00-00 00:00:00';
            $specificPrice->to = '0000-00-00 00:00:00';
            $specificPrice->add();
        }

        // Add product to cart
        $updateQuantity = $cart->updateQty(
            $productInformations['product_quantity'],
            $product->id,
            isset($productInformations['product_attribute_id']) ? $productInformations['product_attribute_id'] : null,
            isset($combination) ? $combination->id : null,
            'up',
            0,
            new Shop($cart->id_shop)
        );

        if ($updateQuantity < 0) {
            // If product has attribute, minimal quantity is set with minimal quantity of attribute
            $minimalQuantity = ($productInformations['product_attribute_id']) ? Attribute::getAttributeMinimalQty($productInformations['product_attribute_id']) : $product->minimal_quantity;
            $this->ajaxDie(json_encode(['error' => sprintf(Tools::displayError('You must add %d minimum quantity', false), $minimalQuantity)]));
        } elseif (!$updateQuantity) {
            $this->ajaxDie(json_encode(['error' => Tools::displayError('You already have the maximum quantity available for this product.', false)]));
        }

        // If order is valid, we can create a new invoice or edit an existing invoice
        if ($order->hasInvoice()) {
            $orderInvoice = new OrderInvoice($productInformations['invoice']);
            // Create new invoice
            if ($orderInvoice->id == 0) {
                // If we create a new invoice, we calculate shipping cost
                $totalMethod = Cart::BOTH;
                // Create Cart rule in order to make free shipping
                if (isset($invoiceInformations['free_shipping']) && $invoiceInformations['free_shipping']) {
                    $cartRule = new CartRule();
                    $cartRule->id_customer = $order->id_customer;
                    $cartRule->name = [
                        Configuration::get('PS_LANG_DEFAULT') => $this->l('[Generated] CartRule for Free Shipping'),
                    ];
                    $cartRule->date_from = date('Y-m-d H:i:s', time());
                    $cartRule->date_to = date('Y-m-d H:i:s', time() + 24 * 3600);
                    $cartRule->quantity = 1;
                    $cartRule->quantity_per_user = 1;
                    $cartRule->minimum_amount_currency = $order->id_currency;
                    $cartRule->reduction_currency = $order->id_currency;
                    $cartRule->free_shipping = true;
                    $cartRule->active = 1;
                    $cartRule->add();

                    // Add cart rule to cart and in order
                    $cart->addCartRule($cartRule->id);
                    $values = [
                        'tax_incl' => $cartRule->getContextualValue(true),
                        'tax_excl' => $cartRule->getContextualValue(false),
                    ];
                    $order->addCartRule($cartRule->id, $cartRule->name[Configuration::get('PS_LANG_DEFAULT')], $values);
                }

                $orderInvoice->id_order = $order->id;
                if ($orderInvoice->number) {
                    Configuration::updateValue('PS_INVOICE_START_NUMBER', false, false, null, $order->id_shop);
                } else {
                    $orderInvoice->number = Order::getLastInvoiceNumber() + 1;
                }

                $invoiceAddress = new Address((int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE', null, null, $order->id_shop)});
                $carrier = new Carrier((int) $order->id_carrier);
                $taxCalculator = $carrier->getTaxCalculator($invoiceAddress);

                $orderInvoice->total_paid_tax_excl
                    = $cart->getOrderTotal(false, $totalMethod);
                $orderInvoice->total_paid_tax_incl
                    = $cart->getOrderTotal($useTaxes, $totalMethod);
                $orderInvoice->total_products
                    = $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
                $orderInvoice->total_products_wt
                    = $cart->getOrderTotal($useTaxes, Cart::ONLY_PRODUCTS);
                $orderInvoice->total_shipping_tax_excl
                    = $cart->getTotalShippingCost(null, false);
                $orderInvoice->total_shipping_tax_incl
                    = $cart->getTotalShippingCost();

                $orderInvoice->total_wrapping_tax_excl
                  = $cart->getOrderTotal(false, Cart::ONLY_WRAPPING);
                $orderInvoice->total_wrapping_tax_incl
                    = $cart->getOrderTotal($useTaxes, Cart::ONLY_WRAPPING);
                $orderInvoice->shipping_tax_computation_method = (int) $taxCalculator->computation_method;

                // Update current order field, only shipping because other field is updated later
                $order->total_shipping += $orderInvoice->total_shipping_tax_incl;
                $order->total_shipping_tax_excl += $orderInvoice->total_shipping_tax_excl;
                $order->total_shipping_tax_incl += ($useTaxes) ? $orderInvoice->total_shipping_tax_incl : $orderInvoice->total_shipping_tax_excl;

                $order->total_wrapping
                    += $cart->getOrderTotal($useTaxes, Cart::ONLY_WRAPPING);
                $order->total_wrapping_tax_excl
                    += $cart->getOrderTotal(false, Cart::ONLY_WRAPPING);
                $order->total_wrapping_tax_incl
                    += $cart->getOrderTotal($useTaxes, Cart::ONLY_WRAPPING);
                $orderInvoice->add();

                $orderInvoice->saveCarrierTaxCalculator($taxCalculator->getTaxesAmount($orderInvoice->total_shipping_tax_excl));

                $orderCarrier = new OrderCarrier();
                $orderCarrier->id_order = (int) $order->id;
                $orderCarrier->id_carrier = (int) $order->id_carrier;
                $orderCarrier->id_order_invoice = (int) $orderInvoice->id;
                $orderCarrier->weight = (float) $cart->getTotalWeight();
                $orderCarrier->shipping_cost_tax_excl = (float) $orderInvoice->total_shipping_tax_excl;
                $orderCarrier->shipping_cost_tax_incl = ($useTaxes) ? (float) $orderInvoice->total_shipping_tax_incl : (float) $orderInvoice->total_shipping_tax_excl;
                $orderCarrier->add();
            } // Update current invoice
            else {
                $orderInvoice->total_paid_tax_excl += round(
                    $cart->getOrderTotal(false, $totalMethod),
                    _TB_PRICE_DATABASE_PRECISION_
                );
                $orderInvoice->total_paid_tax_incl += round(
                    $cart->getOrderTotal($useTaxes, $totalMethod),
                    _TB_PRICE_DATABASE_PRECISION_
                );
                $orderInvoice->total_products
                    += $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
                $orderInvoice->total_products_wt
                    += $cart->getOrderTotal($useTaxes, Cart::ONLY_PRODUCTS);
                $orderInvoice->update();
            }
        }

        // Create Order detail information
        $orderDetail = new OrderDetail();
        $orderDetail->createList($order, $cart, $order->getCurrentOrderState(), $cart->getProducts(), (isset($orderInvoice) ? $orderInvoice->id : 0), $useTaxes, (int) Tools::getValue('add_product_warehouse'));

        // update totals amount of order
        $order->total_products += (float) $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
        $order->total_products_wt += (float) $cart->getOrderTotal($useTaxes, Cart::ONLY_PRODUCTS);

        $order->total_paid += round(
            $cart->getOrderTotal(true, $totalMethod),
            _TB_PRICE_DATABASE_PRECISION_
        );
        $order->total_paid_tax_excl += round(
            $cart->getOrderTotal(false, $totalMethod),
            _TB_PRICE_DATABASE_PRECISION_
        );
        $order->total_paid_tax_incl += round(
            $cart->getOrderTotal($useTaxes, $totalMethod),
            _TB_PRICE_DATABASE_PRECISION_
        );

        if (isset($orderInvoice) && Validate::isLoadedObject($orderInvoice)) {
            $order->total_shipping = $orderInvoice->total_shipping_tax_incl;
            $order->total_shipping_tax_incl = $orderInvoice->total_shipping_tax_incl;
            $order->total_shipping_tax_excl = $orderInvoice->total_shipping_tax_excl;
        }

        // discount
        $order->total_discounts
            += $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        $order->total_discounts_tax_excl
            += $cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS);
        $order->total_discounts_tax_incl
            += $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);

        // Save changes of order
        $order->update();

        // Update weight SUM
        $orderCarrier = new OrderCarrier((int) $order->getIdOrderCarrier());
        if (Validate::isLoadedObject($orderCarrier)) {
            $orderCarrier->weight = (float) $order->getTotalWeight();
            if ($orderCarrier->update()) {
                $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $orderCarrier->weight);
            }
        }

        // Update Tax lines
        $orderDetail->updateTaxAmount($order);

        // Delete specific price if exists
        if (isset($specificPrice)) {
            $specificPrice->delete();
        }

        $products = $this->getProducts($order);

        // Get the last product
        $product = end($products);
        $product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], $product['id_shop']);
        $resume = OrderSlip::getProductSlipResume((int) $product['id_order_detail']);
        $product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
        $product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
        $product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl']);
        $product['return_history'] = OrderReturn::getProductReturnDetail((int) $product['id_order_detail']);
        $product['refund_history'] = OrderSlip::getProductSlipDetail((int) $product['id_order_detail']);
        if ($product['id_warehouse'] != 0) {
            $warehouse = new Warehouse((int) $product['id_warehouse']);
            $product['warehouse_name'] = $warehouse->name;
            $warehouseLocation = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse']);
            if (!empty($warehouseLocation)) {
                $product['warehouse_location'] = $warehouseLocation;
            } else {
                $product['warehouse_location'] = false;
            }
        } else {
            $product['warehouse_name'] = '--';
            $product['warehouse_location'] = false;
        }

        // Get invoices collection
        $invoiceCollection = $order->getInvoicesCollection();

        $invoiceArray = [];
        foreach ($invoiceCollection as $invoice) {
            /** @var OrderInvoice $invoice */
            $invoice->name = $invoice->getInvoiceNumberFormatted($this->context->language->id, (int) $order->id_shop);
            $invoiceArray[] = $invoice;
        }

        // Assign to smarty informations in order to show the new product line
        $this->context->smarty->assign(
            [
                'product'             => $product,
                'order'               => $order,
                'currency'            => new Currency($order->id_currency),
                'can_edit'            => $this->tabAccess['edit'],
                'invoices_collection' => $invoiceCollection,
                'current_id_lang'     => $this->context->language->id,
                'link'                => $this->context->link,
                'current_index'       => static::$currentIndex,
                'display_warehouse'   => (int) Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'),
            ]
        );

        $this->sendChangedNotification($order);
        $newCartRules = $this->context->cart->getCartRules();
        sort($oldCartRules);
        sort($newCartRules);
        $result = array_diff($newCartRules, $oldCartRules);
        $refresh = false;

        $res = true;
        foreach ($result as $cartRule) {
            $refresh = true;
            // Create OrderCartRule
            $rule = new CartRule($cartRule['id_cart_rule']);
            $values = [
                'tax_incl' => $rule->getContextualValue(true),
                'tax_excl' => $rule->getContextualValue(false),
            ];
            $orderCartRule = new OrderCartRule();
            $orderCartRule->id_order = $order->id;
            $orderCartRule->id_cart_rule = $cartRule['id_cart_rule'];
            $orderCartRule->id_order_invoice = $orderInvoice->id;
            $orderCartRule->name = $cartRule['name'];
            $orderCartRule->value = $values['tax_incl'];
            $orderCartRule->value_tax_excl = $values['tax_excl'];
            $res &= $orderCartRule->add();

            $order->total_discounts += $orderCartRule->value;
            $order->total_discounts_tax_incl += $orderCartRule->value;
            $order->total_discounts_tax_excl += $orderCartRule->value_tax_excl;
            $order->total_paid -= $orderCartRule->value;
            $order->total_paid_tax_incl -= $orderCartRule->value;
            $order->total_paid_tax_excl -= $orderCartRule->value_tax_excl;
        }

        // Update Order
        $order->update();

        $this->ajaxDie(
            json_encode(
                [
                    'result'             => true,
                    'view'               => $this->createTemplate('_product_line.tpl')->fetch(),
                    'can_edit'           => $this->tabAccess['add'],
                    'order'              => $order,
                    'invoices'           => $invoiceArray,
                    'documents_html'     => $this->createTemplate('_documents.tpl')->fetch(),
                    'shipping_html'      => $this->createTemplate('_shipping.tpl')->fetch(),
                    'discount_form_html' => $this->createTemplate('_discount_form.tpl')->fetch(),
                    'refresh'            => $refresh,
                ]
            )
        );
    }

    /**
     * Send changed notification
     *
     * @param Order|null $order
     *
     * @since 1.0.0
     */
    public function sendChangedNotification(Order $order = null)
    {
        if (is_null($order)) {
            $order = new Order(Tools::getValue('id_order'));
        }

        Hook::exec('actionOrderEdited', ['order' => $order]);
    }

    /**
     * Ajax proces load product information
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessLoadProductInformation()
    {
        $orderDetail = new OrderDetail(Tools::getValue('id_order_detail'));
        if (!Validate::isLoadedObject($orderDetail)) {
            $this->ajaxDie(
                json_encode(
                    [
                        'result' => false,
                        'error'  => Tools::displayError('The OrderDetail object cannot be loaded.'),
                    ]
                )
            );
        }

        $product = new Product($orderDetail->product_id);
        if (!Validate::isLoadedObject($product)) {
            $this->ajaxDie(
                json_encode(
                    [
                        'result' => false,
                        'error'  => Tools::displayError('The product object cannot be loaded.'),
                    ]
                )
            );
        }

        $address = new Address(Tools::getValue('id_address'));
        if (!Validate::isLoadedObject($address)) {
            $this->ajaxDie(
                json_encode(
                    [
                        'result' => false,
                        'error'  => Tools::displayError('The address object cannot be loaded.'),
                    ]
                )
            );
        }

        $decimals = 0;
        if ($this->context->currency->decimals) {
            $decimals = Configuration::get('PS_PRICE_DISPLAY_PRECISION');
        }
        $this->ajaxDie(json_encode([
            'result'            => true,
            'product'           => $product,
            'tax_rate'          => $product->getTaxesRate($address),
            'price_tax_incl'    => Product::getPriceStatic(
                $product->id,
                true,
                $orderDetail->product_attribute_id,
                $decimals
            ),
            'price_tax_excl'    => Product::getPriceStatic(
                $product->id,
                false,
                $orderDetail->product_attribute_id,
                $decimals
            ),
            'reduction_percent' => $orderDetail->reduction_percent,
        ]));
    }

    /**
     * Ajax process edit product on order
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessEditProductOnOrder()
    {
        // Return value
        $res = true;

        $order = new Order((int) Tools::getValue('id_order'));
        $orderDetail = new OrderDetail((int) Tools::getValue('product_id_order_detail'));
        if (Tools::isSubmit('product_invoice')) {
            $orderInvoice = new OrderInvoice((int) Tools::getValue('product_invoice'));
        }

        // If multiple product_quantity, the order details concern a product customized
        $productQuantity = 0;
        if (is_array(Tools::getValue('product_quantity'))) {
            foreach (Tools::getValue('product_quantity') as $idCustomization => $qty) {
                // Update quantity of each customization
                Db::getInstance()->update('customization', ['quantity' => (int) $qty], 'id_customization = '.(int) $idCustomization);
                // Calculate the real quantity of the product
                $productQuantity += $qty;
            }
        } else {
            $productQuantity = Tools::getValue('product_quantity');
        }

        $this->checkStockAvailable($orderDetail, ($productQuantity - $orderDetail->product_quantity));


        // Check fields validity
        $this->doEditProductValidation($orderDetail, $order, isset($orderInvoice) ? $orderInvoice : null);

        // If multiple product_quantity, the order details concern a product customized
        $productQuantity = 0;
        if (is_array(Tools::getValue('product_quantity'))) {
            foreach (Tools::getValue('product_quantity') as $idCustomization => $qty) {
                // Update quantity of each customization
                Db::getInstance()->update(
                    'customization',
                    [
                        'quantity' => (int) $qty,
                    ],
                    'id_customization = '.(int) $idCustomization,
                    1
                );
                // Calculate the real quantity of the product
                $productQuantity += $qty;
            }
        } else {
            $productQuantity = Tools::getValue('product_quantity');
        }

        $productPriceTaxIncl = round(
            Tools::getValue('product_price_tax_incl'),
            _TB_PRICE_DATABASE_PRECISION_
        );
        $productPriceTaxExcl = round(
            Tools::getValue('product_price_tax_excl'),
            _TB_PRICE_DATABASE_PRECISION_
        );
        $totalProductsTaxIncl = $productPriceTaxIncl * $productQuantity;
        $totalProductsTaxExcl = $productPriceTaxExcl * $productQuantity;

        // Calculate differences of price (Before / After)
        $diffPriceTaxIncl = $totalProductsTaxIncl - $orderDetail->total_price_tax_incl;
        $diffPriceTaxExcl = $totalProductsTaxExcl - $orderDetail->total_price_tax_excl;

        // Apply change on OrderInvoice
        if (isset($orderInvoice)) {
            // If OrderInvoice to use is different, we update the old invoice and new invoice
            if ($orderDetail->id_order_invoice != $orderInvoice->id) {
                $oldOrderInvoice = new OrderInvoice($orderDetail->id_order_invoice);
                // We remove cost of products
                $oldOrderInvoice->total_products -= $orderDetail->total_price_tax_excl;
                $oldOrderInvoice->total_products_wt -= $orderDetail->total_price_tax_incl;

                $oldOrderInvoice->total_paid_tax_excl -= $orderDetail->total_price_tax_excl;
                $oldOrderInvoice->total_paid_tax_incl -= $orderDetail->total_price_tax_incl;

                $res &= $oldOrderInvoice->update();

                $orderInvoice->total_products += $orderDetail->total_price_tax_excl;
                $orderInvoice->total_products_wt += $orderDetail->total_price_tax_incl;

                $orderInvoice->total_paid_tax_excl += $orderDetail->total_price_tax_excl;
                $orderInvoice->total_paid_tax_incl += $orderDetail->total_price_tax_incl;

                $orderDetail->id_order_invoice = $orderInvoice->id;
            }
        }

        if ($diffPriceTaxIncl != 0 && $diffPriceTaxExcl != 0) {
            $orderDetail->unit_price_tax_excl = $productPriceTaxExcl;
            $orderDetail->unit_price_tax_incl = $productPriceTaxIncl;

            $orderDetail->total_price_tax_incl += $diffPriceTaxIncl;
            $orderDetail->total_price_tax_excl += $diffPriceTaxExcl;

            if (isset($orderInvoice)) {
                // Apply changes on OrderInvoice
                $orderInvoice->total_products += $diffPriceTaxExcl;
                $orderInvoice->total_products_wt += $diffPriceTaxIncl;

                $orderInvoice->total_paid_tax_excl += $diffPriceTaxExcl;
                $orderInvoice->total_paid_tax_incl += $diffPriceTaxIncl;
            }

            // Apply changes on Order
            $order = new Order($orderDetail->id_order);
            $order->total_products += $diffPriceTaxExcl;
            $order->total_products_wt += $diffPriceTaxIncl;

            $order->total_paid += $diffPriceTaxIncl;
            $order->total_paid_tax_excl += $diffPriceTaxExcl;
            $order->total_paid_tax_incl += $diffPriceTaxIncl;

            $res &= $order->update();
        }

        $oldQuantity = $orderDetail->product_quantity;

        $orderDetail->product_quantity = $productQuantity;
        $orderDetail->reduction_percent = 0;

        // update taxes
        $res &= $orderDetail->updateTaxAmount($order);

        // Save order detail
        $res &= $orderDetail->update();

        // Update weight SUM
        $orderCarrier = new OrderCarrier((int) $order->getIdOrderCarrier());
        if (Validate::isLoadedObject($orderCarrier)) {
            $orderCarrier->weight = (float) $order->getTotalWeight();
            $res &= $orderCarrier->update();
            if ($res) {
                $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $orderCarrier->weight);
            }
        }

        // Save order invoice
        if (isset($orderInvoice)) {
            $res &= $orderInvoice->update();
        }

        // Update product available quantity
        StockAvailable::updateQuantity($orderDetail->product_id, $orderDetail->product_attribute_id, ($oldQuantity - $orderDetail->product_quantity), $order->id_shop);

        $products = $this->getProducts($order);
        // Get the last product
        $product = $products[$orderDetail->id];
        $product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], $product['id_shop']);
        $resume = OrderSlip::getProductSlipResume($orderDetail->id);
        $product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
        $product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
        $product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl']);
        $product['refund_history'] = OrderSlip::getProductSlipDetail($orderDetail->id);
        if ($product['id_warehouse'] != 0) {
            $warehouse = new Warehouse((int) $product['id_warehouse']);
            $product['warehouse_name'] = $warehouse->name;
            $warehouseLocation = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse']);
            if (!empty($warehouseLocation)) {
                $product['warehouse_location'] = $warehouseLocation;
            } else {
                $product['warehouse_location'] = false;
            }
        } else {
            $product['warehouse_name'] = '--';
            $product['warehouse_location'] = false;
        }

        // Get invoices collection
        $invoiceCollection = $order->getInvoicesCollection();

        $invoiceArray = [];
        foreach ($invoiceCollection as $invoice) {
            /** @var OrderInvoice $invoice */
            $invoice->name = $invoice->getInvoiceNumberFormatted($this->context->language->id, (int) $order->id_shop);
            $invoiceArray[] = $invoice;
        }

        // Assign to smarty informations in order to show the new product line
        $this->context->smarty->assign(
            [
                'product'             => $product,
                'order'               => $order,
                'currency'            => new Currency($order->id_currency),
                'can_edit'            => $this->tabAccess['edit'],
                'invoices_collection' => $invoiceCollection,
                'current_id_lang'     => $this->context->language->id,
                'link'                => $this->context->link,
                'current_index'       => static::$currentIndex,
                'display_warehouse'   => (int) Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'),
            ]
        );

        if (!$res) {
            $this->ajaxDie(
                json_encode(
                    [
                        'result' => $res,
                        'error'  => Tools::displayError('An error occurred while editing the product line.'),
                    ]
                )
            );
        }

        if (is_array(Tools::getValue('product_quantity'))) {
            $view = $this->createTemplate('_customized_data.tpl')->fetch();
        } else {
            $view = $this->createTemplate('_product_line.tpl')->fetch();
        }

        $this->sendChangedNotification($order);

        $this->ajaxDie(json_encode([
            'result'              => $res,
            'view'                => $view,
            'can_edit'            => $this->tabAccess['add'],
            'invoices_collection' => $invoiceCollection,
            'order'               => $order,
            'invoices'            => $invoiceArray,
            'documents_html'      => $this->createTemplate('_documents.tpl')->fetch(),
            'shipping_html'       => $this->createTemplate('_shipping.tpl')->fetch(),
            'customized_product'  => is_array(Tools::getValue('product_quantity')),
        ]));
    }

    /**
     * @param OrderDetail $orderDetail
     * @param int         $addQuantity
     */
    protected function checkStockAvailable($orderDetail, $addQuantity)
    {
        if ($addQuantity > 0) {
            $stockAvailable = StockAvailable::getQuantityAvailableByProduct($orderDetail->product_id, $orderDetail->product_attribute_id, $orderDetail->id_shop);
            $product = new Product($orderDetail->product_id, true, null, $orderDetail->id_shop);
            if (!Validate::isLoadedObject($product)) {
                $this->ajaxDie(json_encode([
                    'result' => false,
                    'error'  => Tools::displayError('The Product object could not be loaded.'),
                ]));
            } else {
                if (($stockAvailable < $addQuantity) && (!$product->isAvailableWhenOutOfStock((int) $product->out_of_stock))) {
                    $this->ajaxDie(json_encode([
                        'result' => false,
                        'error'  => Tools::displayError('This product is no longer in stock with those attributes '),
                    ]));

                }
            }
        }
    }

    /**
     * Ajax proces delete product line
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessDeleteProductLine()
    {
        $res = true;

        $orderDetail = new OrderDetail((int) Tools::getValue('id_order_detail'));
        $order = new Order((int) Tools::getValue('id_order'));

        $this->doDeleteProductLineValidation($orderDetail, $order);

        // Update OrderInvoice of this OrderDetail
        if ($orderDetail->id_order_invoice != 0) {
            $orderInvoice = new OrderInvoice($orderDetail->id_order_invoice);
            $orderInvoice->total_paid_tax_excl -= $orderDetail->total_price_tax_excl;
            $orderInvoice->total_paid_tax_incl -= $orderDetail->total_price_tax_incl;
            $orderInvoice->total_products -= $orderDetail->total_price_tax_excl;
            $orderInvoice->total_products_wt -= $orderDetail->total_price_tax_incl;
            $res &= $orderInvoice->update();
        }

        // Update Order
        $order->total_paid -= $orderDetail->total_price_tax_incl;
        $order->total_paid_tax_incl -= $orderDetail->total_price_tax_incl;
        $order->total_paid_tax_excl -= $orderDetail->total_price_tax_excl;
        $order->total_products -= $orderDetail->total_price_tax_excl;
        $order->total_products_wt -= $orderDetail->total_price_tax_incl;

        $res &= $order->update();

        // Reinject quantity in stock
        $this->reinjectQuantity($orderDetail, $orderDetail->product_quantity, true);

        // Update weight SUM
        $orderCarrier = new OrderCarrier((int) $order->getIdOrderCarrier());
        if (Validate::isLoadedObject($orderCarrier)) {
            $orderCarrier->weight = (float) $order->getTotalWeight();
            $res &= $orderCarrier->update();
            if ($res) {
                $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $orderCarrier->weight);
            }
        }

        if (!$res) {
            $this->ajaxDie(json_encode([
                'result' => $res,
                'error'  => Tools::displayError('An error occurred while attempting to delete the product line.'),
            ]));
        }

        // Get invoices collection
        $invoiceCollection = $order->getInvoicesCollection();

        $invoiceArray = [];
        foreach ($invoiceCollection as $invoice) {
            /** @var OrderInvoice $invoice */
            $invoice->name = $invoice->getInvoiceNumberFormatted($this->context->language->id, (int) $order->id_shop);
            $invoiceArray[] = $invoice;
        }

        // Assign to smarty informations in order to show the new product line
        $this->context->smarty->assign([
            'order'               => $order,
            'currency'            => new Currency($order->id_currency),
            'invoices_collection' => $invoiceCollection,
            'current_id_lang'     => $this->context->language->id,
            'link'                => $this->context->link,
            'current_index'       => static::$currentIndex,
        ]);

        $this->sendChangedNotification($order);

        $this->ajaxDie(json_encode([
            'result'         => $res,
            'order'          => $order,
            'invoices'       => $invoiceArray,
            'documents_html' => $this->createTemplate('_documents.tpl')->fetch(),
            'shipping_html'  => $this->createTemplate('_shipping.tpl')->fetch(),
        ]));
    }

    /**
     * Ajax process change payment method
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessChangePaymentMethod()
    {
        $customer = new Customer(Tools::getValue('id_customer'));
        $modules = Module::getAuthorizedModules($customer->id_default_group);
        $authorizedModules = [];

        if (!Validate::isLoadedObject($customer) || !is_array($modules)) {
            $this->ajaxDie(json_encode(['result' => false]));
        }

        foreach ($modules as $module) {
            $authorizedModules[] = (int) $module['id_module'];
        }

        $paymentModules = [];

        foreach (PaymentModule::getInstalledPaymentModules() as $pModule) {
            if (in_array((int) $pModule['id_module'], $authorizedModules)) {
                $paymentModules[] = Module::getInstanceById((int) $pModule['id_module']);
            }
        }

        $this->context->smarty->assign([
            'payment_modules' => $paymentModules,
        ]);

        $this->ajaxDie(json_encode([
            'result' => true,
            'view'   => $this->createTemplate('_select_payment.tpl')->fetch(),
        ]));
    }

    /**
     * Apply discount on invoice
     *
     * @param OrderInvoice $orderInvoice
     * @param float        $valueTaxIncl
     * @param float        $valueTaxExcl
     *
     * @return bool Indicates whether the invoice was successfully updated
     *
     * @since 1.0.0
     * @since 1.0.1 Return update status bool
     */
    protected function applyDiscountOnInvoice($orderInvoice, $valueTaxIncl, $valueTaxExcl)
    {
        // Update OrderInvoice
        $orderInvoice->total_discount_tax_incl += $valueTaxIncl;
        $orderInvoice->total_discount_tax_excl += $valueTaxExcl;
        $orderInvoice->total_paid_tax_incl -= $valueTaxIncl;
        $orderInvoice->total_paid_tax_excl -= $valueTaxExcl;
        $orderInvoice->update();
    }

    /**
     * Edit production validation
     *
     * @param OrderDetail       $orderDetail
     * @param Order             $order
     * @param OrderInvoice|null $orderInvoice
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function doEditProductValidation(OrderDetail $orderDetail, Order $order, OrderInvoice $orderInvoice = null)
    {
        if (!Validate::isLoadedObject($orderDetail)) {
            $this->ajaxDie(json_encode([
                'result' => false,
                'error'  => Tools::displayError('The Order Detail object could not be loaded.'),
            ]));
        }

        if (!empty($orderInvoice) && !Validate::isLoadedObject($orderInvoice)) {
            $this->ajaxDie(json_encode([
                'result' => false,
                'error'  => Tools::displayError('The invoice object cannot be loaded.'),
            ]));
        }

        if (!Validate::isLoadedObject($order)) {
            $this->ajaxDie(json_encode([
                'result' => false,
                'error'  => Tools::displayError('The order object cannot be loaded.'),
            ]));
        }

        if ($orderDetail->id_order != $order->id) {
            $this->ajaxDie(json_encode([
                'result' => false,
                'error'  => Tools::displayError('You cannot edit the order detail for this order.'),
            ]));
        }

        // We can't edit a delivered order
        if ($order->hasBeenDelivered()) {
            $this->ajaxDie(json_encode([
                'result' => false,
                'error'  => Tools::displayError('You cannot edit a delivered order.'),
            ]));
        }

        if (!empty($orderInvoice) && $orderInvoice->id_order != Tools::getValue('id_order')) {
            $this->ajaxDie(json_encode([
                'result' => false,
                'error'  => Tools::displayError('You cannot use this invoice for the order'),
            ]));
        }

        if ( ! Validate::isPrice(Tools::getValue('product_price_tax_incl'))
            || ! Validate::isPrice(Tools::getValue('product_price_tax_excl'))) {
            $this->ajaxDie(json_encode([
                'result' => false,
                'error'  => Tools::displayError('Invalid price'),
            ]));
        }

        if (!is_array(Tools::getValue('product_quantity')) && !Validate::isUnsignedInt(Tools::getValue('product_quantity'))) {
            $this->ajaxDie(json_encode([
                'result' => false,
                'error'  => Tools::displayError('Invalid quantity'),
            ]));
        } elseif (is_array(Tools::getValue('product_quantity'))) {
            foreach (Tools::getValue('product_quantity') as $qty) {
                if (!Validate::isUnsignedInt($qty)) {
                    $this->ajaxDie(json_encode([
                        'result' => false,
                        'error'  => Tools::displayError('Invalid quantity'),
                    ]));
                }
            }
        }
    }

    /**
     * Delete product line validation
     *
     * @param OrderDetail $orderDetail
     * @param Order       $order
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function doDeleteProductLineValidation(OrderDetail $orderDetail, Order $order)
    {
        if (!Validate::isLoadedObject($orderDetail)) {
            $this->ajaxDie(json_encode([
                'result' => false,
                'error'  => Tools::displayError('The Order Detail object could not be loaded.'),
            ]));
        }

        if (!Validate::isLoadedObject($order)) {
            $this->ajaxDie(json_encode([
                'result' => false,
                'error'  => Tools::displayError('The order object cannot be loaded.'),
            ]));
        }

        if ($orderDetail->id_order != $order->id) {
            $this->ajaxDie(json_encode([
                'result' => false,
                'error'  => Tools::displayError('You cannot delete the order detail.'),
            ]));
        }

        // We can't edit a delivered order
        if ($order->hasBeenDelivered()) {
            $this->ajaxDie(json_encode([
                'result' => false,
                'error'  => Tools::displayError('You cannot edit a delivered order.'),
            ]));
        }
    }

    /**
     * @param OrderDetail $orderDetail
     * @param int         $qtyCancelProduct
     * @param bool        $delete
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function reinjectQuantity($orderDetail, $qtyCancelProduct, $delete = false)
    {
        // Reinject product
        $reinjectableQuantity = (int) $orderDetail->product_quantity - (int) $orderDetail->product_quantity_reinjected;
        $quantityToReinject = $qtyCancelProduct > $reinjectableQuantity ? $reinjectableQuantity : $qtyCancelProduct;
        // @since 1.5.0 : Advanced Stock Management
        // FIXME: this should do something
        // $product_to_inject = new Product($orderDetail->product_id, false, (int) $this->context->language->id, (int) $orderDetail->id_shop);

        $product = new Product($orderDetail->product_id, false, (int) $this->context->language->id, (int) $orderDetail->id_shop);

        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $product->advanced_stock_management && $orderDetail->id_warehouse != 0) {
            $manager = StockManagerFactory::getManager();
            $movements = StockMvt::getNegativeStockMvts(
                $orderDetail->id_order,
                $orderDetail->product_id,
                $orderDetail->product_attribute_id,
                $quantityToReinject
            );
            $leftToReinject = $quantityToReinject;
            foreach ($movements as $movement) {
                if ($leftToReinject > $movement['physical_quantity']) {
                    $quantityToReinject = $movement['physical_quantity'];
                }

                $leftToReinject -= $quantityToReinject;
                if (Pack::isPack((int) $product->id)) {
                    // Gets items
                    if ($product->pack_stock_type == 1 || $product->pack_stock_type == 2 || ($product->pack_stock_type == 3 && Configuration::get('PS_PACK_STOCK_TYPE') > 0)) {
                        $productsPack = Pack::getItems((int) $product->id, (int) Configuration::get('PS_LANG_DEFAULT'));
                        // Foreach item
                        foreach ($productsPack as $productPack) {
                            if ($productPack->advanced_stock_management == 1) {
                                $manager->addProduct(
                                    $productPack->id,
                                    $productPack->id_pack_product_attribute,
                                    new Warehouse($movement['id_warehouse']),
                                    $productPack->pack_quantity * $quantityToReinject,
                                    null,
                                    $movement['price_te'],
                                    true
                                );
                            }
                        }
                    }
                    if ($product->pack_stock_type == 0 || $product->pack_stock_type == 2 ||
                        ($product->pack_stock_type == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 0 || Configuration::get('PS_PACK_STOCK_TYPE') == 2))
                    ) {
                        $manager->addProduct(
                            $orderDetail->product_id,
                            $orderDetail->product_attribute_id,
                            new Warehouse($movement['id_warehouse']),
                            $quantityToReinject,
                            null,
                            $movement['price_te'],
                            true
                        );
                    }
                } else {
                    $manager->addProduct(
                        $orderDetail->product_id,
                        $orderDetail->product_attribute_id,
                        new Warehouse($movement['id_warehouse']),
                        $quantityToReinject,
                        null,
                        $movement['price_te'],
                        true
                    );
                }
            }

            $idProduct = $orderDetail->product_id;
            if ($delete) {
                $orderDetail->delete();
            }
            StockAvailable::synchronize($idProduct);
        } elseif ($orderDetail->id_warehouse == 0) {
            StockAvailable::updateQuantity(
                $orderDetail->product_id,
                $orderDetail->product_attribute_id,
                $quantityToReinject,
                $orderDetail->id_shop
            );

            if ($delete) {
                $orderDetail->delete();
            }
        } else {
            $this->errors[] = Tools::displayError('This product cannot be re-stocked.');
        }
    }

    /**
     * @param Order $order
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function getProducts($order)
    {
        $products = $order->getProducts();

        foreach ($products as &$product) {
            if ($product['image'] != null) {
                $name = 'product_mini_'.(int) $product['product_id'].(isset($product['product_attribute_id']) ? '_'.(int) $product['product_attribute_id'] : '').'.jpg';
                // generate image cache, only for back office
                $product['image_tag'] = ImageManager::thumbnail(_PS_IMG_DIR_.'p/'.$product['image']->getExistingImgPath().'.jpg', $name, 45, 'jpg');
                if (file_exists(_PS_TMP_IMG_DIR_.$name)) {
                    $product['image_size'] = getimagesize(_PS_TMP_IMG_DIR_.$name);
                } else {
                    $product['image_size'] = false;
                }
            }
        }

        ksort($products);

        return $products;
    }
}
