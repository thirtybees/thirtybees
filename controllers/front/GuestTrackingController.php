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
 * Class GuestTrackingControllerCore
 *
 * @since 1.0.0
 */
class GuestTrackingControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var bool $ssl */
    public $ssl = true;
    /** @var string $php_self */
    public $php_self = 'guest-tracking';
    // @codingStandardsIgnoreEnd

    /**
     * Initialize
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function init()
    {
        parent::init();
        if ($this->context->customer->isLogged()) {
            Tools::redirect('history.php');
        }
    }

    /**
     * Start forms process
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitGuestTracking') || Tools::isSubmit('submitTransformGuestToCustomer')) {
            // These lines are here for retrocompatibility with old theme
            $idOrder = Tools::getValue('id_order');
            $orderCollection = [];
            if ($idOrder) {
                if (is_numeric($idOrder)) {
                    $order = new Order((int) $idOrder);
                    if (Validate::isLoadedObject($order)) {
                        $orderCollection = Order::getByReference($order->reference);
                    }
                } else {
                    $orderCollection = Order::getByReference($idOrder);
                }
            }

            // Get order reference, ignore package reference (after the #, on the order reference)
            $orderReference = current(explode('#', Tools::getValue('order_reference')));
            // Ignore $result_number
            if (!empty($orderReference)) {
                $orderCollection = Order::getByReference($orderReference);
            }

            $email = Tools::getValue('email');

            if (empty($orderReference) && empty($idOrder)) {
                $this->errors[] = Tools::displayError('Please provide your order\'s reference number.');
            } elseif (empty($email)) {
                $this->errors[] = Tools::displayError('Please provide a valid email address.');
            } elseif (!Validate::isEmail($email)) {
                $this->errors[] = Tools::displayError('Please provide a valid email address.');
            } elseif (!Customer::customerExists($email, false, false)) {
                $this->errors[] = Tools::displayError('There is no account associated with this email address.');
            } elseif (Customer::customerExists($email, false, true)) {
                $this->errors[] = Tools::displayError('This page is for guest accounts only. Since your guest account has already been transformed into a customer account, you can no longer view your order here. Please log in to your customer account to view this order');
                $this->context->smarty->assign('show_login_link', true);
            } elseif (empty($orderCollection->getResults())) {
                $this->errors[] = Tools::displayError('Invalid order reference');
            } elseif (!$orderCollection->getFirst()->isAssociatedAtGuest($email)) {
                $this->errors[] = Tools::displayError('Invalid order reference');
            } else {
                $this->assignOrderTracking($orderCollection);
                if (isset($order) && Tools::isSubmit('submitTransformGuestToCustomer')) {
                    $customer = new Customer((int) $order->id_customer);
                    if (!Validate::isLoadedObject($customer)) {
                        $this->errors[] = Tools::displayError('Invalid customer');
                    } elseif (!Tools::getValue('password')) {
                        $this->errors[] = Tools::displayError('Invalid password.');
                    } elseif (!$customer->transformToCustomer($this->context->language->id, Tools::getValue('password'))) {
                        $this->errors[] = Tools::displayError('An error occurred while transforming a guest into a registered customer.');
                    } else {
                        $this->context->smarty->assign('transformSuccess', true);
                    }
                }
            }
        }
    }

    /**
     * Assigns template vars related to order tracking information
     *
     * @param PrestaShopCollection $orderCollection
     *
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    protected function assignOrderTracking($orderCollection)
    {
        $customer = new Customer((int) $orderCollection->getFirst()->id_customer);

        $orderCollection = ($orderCollection->getAll());

        $orderList = [];
        foreach ($orderCollection as $order) {
            $orderList[] = $order;
        }

        foreach ($orderList as &$order) {
            /** @var Order $order */
            $order->id_order_state = (int) $order->getCurrentState();
            $order->invoice = (OrderState::invoiceAvailable((int) $order->id_order_state) && $order->invoice_number);
            $order->order_history = $order->getHistory((int) $this->context->language->id, false, true);
            $order->carrier = new Carrier((int) $order->id_carrier, (int) $order->id_lang);
            $order->address_invoice = new Address((int) $order->id_address_invoice);
            $order->address_delivery = new Address((int) $order->id_address_delivery);
            $order->inv_adr_fields = AddressFormat::getOrderedAddressFields($order->address_invoice->id_country);
            $order->dlv_adr_fields = AddressFormat::getOrderedAddressFields($order->address_delivery->id_country);
            $order->invoiceAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($order->address_invoice, $order->inv_adr_fields);
            $order->deliveryAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($order->address_delivery, $order->dlv_adr_fields);
            $order->currency = new Currency($order->id_currency);
            $order->discounts = $order->getCartRules();
            $order->invoiceState = (Validate::isLoadedObject($order->address_invoice) && $order->address_invoice->id_state) ? new State((int) $order->address_invoice->id_state) : false;
            $order->deliveryState = (Validate::isLoadedObject($order->address_delivery) && $order->address_delivery->id_state) ? new State((int) $order->address_delivery->id_state) : false;
            $order->products = $order->getProducts();
            $order->customizedDatas = Product::getAllCustomizedDatas((int) $order->id_cart);
            Product::addCustomizationPrice($order->products, $order->customizedDatas);
            $order->total_old = $order->total_discounts > 0 ? (float) $order->total_paid - (float) $order->total_discounts : false;

            if ($order->carrier->url && $order->shipping_number) {
                $order->followup = str_replace('@', $order->shipping_number, $order->carrier->url);
            }
            $order->hook_orderdetaildisplayed = Hook::exec('displayOrderDetail', ['order' => $order]);

            Hook::exec('actionOrderDetail', ['carrier' => $order->carrier, 'order' => $order]);
        }

        $this->context->smarty->assign(
            [
                'shop_name'           => Configuration::get('PS_SHOP_NAME'),
                'order_collection'    => $orderList,
                'return_allowed'      => false,
                'invoiceAllowed'      => (int) Configuration::get('PS_INVOICE'),
                'is_guest'            => true,
                'group_use_tax'       => (Group::getPriceDisplayMethod($customer->id_default_group) == PS_TAX_INC),
                'CUSTOMIZE_FILE'      => Product::CUSTOMIZE_FILE,
                'CUSTOMIZE_TEXTFIELD' => Product::CUSTOMIZE_TEXTFIELD,
                'use_tax'             => Configuration::get('PS_TAX'),
            ]
        );
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
        parent::initContent();

        /* Handle brute force attacks */
        if (count($this->errors)) {
            sleep(1);
        }

        $this->context->smarty->assign(
            [
                'action' => $this->context->link->getPageLink('guest-tracking.php', true),
                'errors' => $this->errors,
            ]
        );
        $this->setTemplate(_PS_THEME_DIR_.'guest-tracking.tpl');
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
        parent::setMedia();

        $this->addCSS(_THEME_CSS_DIR_.'history.css');
        $this->addCSS(_THEME_CSS_DIR_.'addresses.css');
    }

    protected function processAddressFormat(Address $delivery, Address $invoice)
    {
        $invAdrFields = AddressFormat::getOrderedAddressFields($invoice->id_country, false, true);
        $dlvAdrFields = AddressFormat::getOrderedAddressFields($delivery->id_country, false, true);

        $this->context->smarty->assign([
            'inv_adr_fields' => $invAdrFields,
            'dlv_adr_fields' => $dlvAdrFields,
        ]);
    }
}
