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
 * Class OrderDetailControllerCore
 *
 * @since 1.0.0
 */
class OrderDetailControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'order-detail';
    /** @var bool $auth */
    public $auth = true;
    /** @var string $authRedirection */
    public $authRedirection = 'history';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    /**
     * Initialize order detail controller
     *
     * @see   FrontController::init()
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function init()
    {
        parent::init();
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    }

    /**
     * Start forms process
     *
     * @see   FrontController::postProcess()
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('msgText') && Tools::isSubmit('id_order') && Tools::isSubmit('id_product')) {
            $idOrder = (int) Tools::getValue('id_order');
            $msgText = Tools::getValue('msgText');

            if (!$idOrder || !Validate::isUnsignedId($idOrder)) {
                $this->errors[] = Tools::displayError('The order is no longer valid.');
            } elseif (empty($msgText)) {
                $this->errors[] = Tools::displayError('The message cannot be blank.');
            } elseif (!Validate::isMessage($msgText)) {
                $this->errors[] = Tools::displayError('This message is invalid (HTML is not allowed).');
            }
            if (!count($this->errors)) {
                $order = new Order($idOrder);
                if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id) {
                    //check if a thread already exist
                    $idCustomerThread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($this->context->customer->email, $order->id);
                    $idProduct = (int) Tools::getValue('id_product');
                    $cm = new CustomerMessage();
                    if (!$idCustomerThread) {
                        $ct = new CustomerThread();
                        $ct->id_contact = 0;
                        $ct->id_customer = (int) $order->id_customer;
                        $ct->id_shop = (int) $this->context->shop->id;
                        if ($idProduct && $order->orderContainProduct($idProduct)) {
                            $ct->id_product = $idProduct;
                        }
                        $ct->id_order = (int) $order->id;
                        $ct->id_lang = (int) $this->context->language->id;
                        $ct->email = $this->context->customer->email;
                        $ct->status = 'open';
                        $ct->token = Tools::passwdGen(12);
                        $ct->add();
                    } else {
                        $ct = new CustomerThread((int) $idCustomerThread);
                        $ct->status = 'open';
                        $ct->update();
                    }

                    $cm->id_customer_thread = $ct->id;
                    $cm->message = $msgText;
                    $cm->ip_address = (int) ip2long($_SERVER['REMOTE_ADDR']);
                    $cm->add();

                    if (!Configuration::get('PS_MAIL_EMAIL_MESSAGE')) {
                        $to = strval(Configuration::get('PS_SHOP_EMAIL'));
                    } else {
                        $to = new Contact((int) Configuration::get('PS_MAIL_EMAIL_MESSAGE'));
                        $to = strval($to->email);
                    }
                    $toName = strval(Configuration::get('PS_SHOP_NAME'));
                    $customer = $this->context->customer;

                    $product = new Product($idProduct);
                    $productName = '';
                    if (Validate::isLoadedObject($product) && isset($product->name[(int) $this->context->language->id])) {
                        $productName = $product->name[(int) $this->context->language->id];
                    }

                    if (Validate::isLoadedObject($customer)) {
                        Mail::Send(
                            $this->context->language->id,
                            'order_customer_comment',
                            Mail::l('Message from a customer'),
                            [
                                '{lastname}'     => $customer->lastname,
                                '{firstname}'    => $customer->firstname,
                                '{email}'        => $customer->email,
                                '{id_order}'     => (int) $order->id,
                                '{order_name}'   => $order->getUniqReference(),
                                '{message}'      => Tools::nl2br($msgText),
                                '{product_name}' => $productName,
                            ],
                            $to,
                            $toName,
                            strval(Configuration::get('PS_SHOP_EMAIL')),
                            $customer->firstname.' '.$customer->lastname,
                            null,
                            null,
                            _PS_MAIL_DIR_,
                            false,
                            null,
                            null,
                            $customer->email
                        );
                    }


                    if (Tools::getValue('ajax') != 'true') {
                        Tools::redirect('index.php?controller=order-detail&id_order='.(int) $idOrder);
                    }

                    $this->context->smarty->assign('message_confirmation', true);
                } else {
                    $this->errors[] = Tools::displayError('Order not found');
                }
            }
        }
    }

    /**
     * Handle ajax call
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function displayAjax()
    {
        $this->display();
    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        parent::initContent();

        if (!($idOrder = (int) Tools::getValue('id_order')) || !Validate::isUnsignedId($idOrder)) {
            $this->errors[] = Tools::displayError('Order ID required');
        } else {
            $order = new Order($idOrder);
            if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id) {
                $idOrderState = (int) $order->getCurrentState();
                $carrier = new Carrier((int) $order->id_carrier, (int) $order->id_lang);
                $addressInvoice = new Address((int) $order->id_address_invoice);
                $addressDelivery = new Address((int) $order->id_address_delivery);

                $invAdrFields = AddressFormat::getOrderedAddressFields($addressInvoice->id_country);
                $dlvAdrFields = AddressFormat::getOrderedAddressFields($addressDelivery->id_country);

                $invoiceAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($addressInvoice, $invAdrFields);
                $deliveryAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($addressDelivery, $dlvAdrFields);

                if ($order->total_discounts > 0) {
                    $this->context->smarty->assign('total_old', (float) $order->total_paid - $order->total_discounts);
                }
                $products = $order->getProducts();

                /* DEPRECATED: customizedDatas @since 1.5 */
                $customizedDatas = Product::getAllCustomizedDatas((int) $order->id_cart);
                Product::addCustomizationPrice($products, $customizedDatas);

                OrderReturn::addReturnedQuantity($products, $order->id);
                $orderStatus = new OrderState((int) $idOrderState, (int) $order->id_lang);

                $customer = new Customer($order->id_customer);
                $this->context->smarty->assign(
                    [
                        'shop_name'                     => strval(Configuration::get('PS_SHOP_NAME')),
                        'order'                         => $order,
                        'return_allowed'                => (int) $order->isReturnable(),
                        'currency'                      => new Currency($order->id_currency),
                        'order_state'                   => (int) $idOrderState,
                        'invoiceAllowed'                => (int) Configuration::get('PS_INVOICE'),
                        'invoice'                       => (OrderState::invoiceAvailable($idOrderState) && count($order->getInvoicesCollection())),
                        'logable'                       => (bool) $orderStatus->logable,
                        'order_history'                 => $order->getHistory($this->context->language->id, false, true),
                        'products'                      => $products,
                        'discounts'                     => $order->getCartRules(),
                        'carrier'                       => $carrier,
                        'address_invoice'               => $addressInvoice,
                        'invoiceState'                  => (Validate::isLoadedObject($addressInvoice) && $addressInvoice->id_state) ? new State($addressInvoice->id_state) : false,
                        'address_delivery'              => $addressDelivery,
                        'inv_adr_fields'                => $invAdrFields,
                        'dlv_adr_fields'                => $dlvAdrFields,
                        'invoiceAddressFormatedValues'  => $invoiceAddressFormatedValues,
                        'deliveryAddressFormatedValues' => $deliveryAddressFormatedValues,
                        'deliveryState'                 => (Validate::isLoadedObject($addressDelivery) && $addressDelivery->id_state) ? new State($addressDelivery->id_state) : false,
                        'is_guest'                      => false,
                        'messages'                      => CustomerMessage::getMessagesByOrderId((int) $order->id, true),
                        'CUSTOMIZE_FILE'                => Product::CUSTOMIZE_FILE,
                        'CUSTOMIZE_TEXTFIELD'           => Product::CUSTOMIZE_TEXTFIELD,
                        'isRecyclable'                  => Configuration::get('PS_RECYCLABLE_PACK'),
                        'use_tax'                       => Configuration::get('PS_TAX'),
                        'group_use_tax'                 => (Group::getPriceDisplayMethod($customer->id_default_group) == PS_TAX_INC),
                        /* DEPRECATED: customizedDatas @since 1.5 */
                        'customizedDatas'               => $customizedDatas,
                        /* DEPRECATED: customizedDatas @since 1.5 */
                        'reorderingAllowed'             => !(bool) Configuration::get('PS_DISALLOW_HISTORY_REORDERING'),
                    ]
                );

                if ($carrier->url && $order->shipping_number) {
                    $this->context->smarty->assign('followup', str_replace('@', $order->shipping_number, $carrier->url));
                }
                $this->context->smarty->assign('HOOK_ORDERDETAILDISPLAYED', Hook::exec('displayOrderDetail', ['order' => $order]));
                Hook::exec('actionOrderDetail', ['carrier' => $carrier, 'order' => $order]);

                unset($carrier, $addressInvoice, $addressDelivery);
            } else {
                $this->errors[] = Tools::displayError('This order cannot be found.');
            }
            unset($order);
        }

        $this->setTemplate(_PS_THEME_DIR_.'order-detail.tpl');
    }

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setMedia()
    {
        if (Tools::getValue('ajax') != 'true') {
            parent::setMedia();
            $this->addCSS(_THEME_CSS_DIR_.'history.css');
            $this->addCSS(_THEME_CSS_DIR_.'addresses.css');
        }
    }
}
