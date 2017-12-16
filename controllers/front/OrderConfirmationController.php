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
 * Class OrderConfirmationControllerCore
 *
 * @since 1.0.0
 */
class OrderConfirmationControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var bool $ssl */
    public $ssl = true;
    /** @var string $php_self */
    public $php_self = 'order-confirmation';
    /** @var int $id_cart */
    public $id_cart;
    /** @var int $id_module */
    public $id_module;
    /** @var int $id_order */
    public $id_order;
    /** @var string $reference */
    public $reference;
    /** @var string $secure_key */
    public $secure_key;
    // @codingStandardsIgnoreEnd

    /**
     * Initialize order confirmation controller
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

        $this->id_cart = (int) Tools::getValue('id_cart', 0);
        $isGuest = false;

        /* check if the cart has been made by a Guest customer, for redirect link */
        if (Cart::isGuestCartByCartId($this->id_cart)) {
            $isGuest = true;
            $redirectLink = 'index.php?controller=guest-tracking';
        } else {
            $redirectLink = 'index.php?controller=history';
        }

        $this->id_module = (int) (Tools::getValue('id_module', 0));
        $this->id_order = Order::getOrderByCartId((int) ($this->id_cart));
        $this->secure_key = Tools::getValue('key', false);
        $order = new Order((int) ($this->id_order));
        if ($isGuest) {
            $customer = new Customer((int) $order->id_customer);
            $redirectLink .= '&id_order='.$order->reference.'&email='.urlencode($customer->email);
        }
        if (!$this->id_order || !$this->id_module || !$this->secure_key || empty($this->secure_key)) {
            Tools::redirect($redirectLink.(Tools::isSubmit('slowvalidation') ? '&slowvalidation' : ''));
        }
        $this->reference = $order->reference;
        if (!Validate::isLoadedObject($order) || $order->id_customer != $this->context->customer->id || $this->secure_key != $order->secure_key) {
            Tools::redirect($redirectLink);
        }
        $module = Module::getInstanceById((int) ($this->id_module));
        if ($order->module != $module->name) {
            Tools::redirect($redirectLink);
        }
    }

    /**
     * Assign template vars related to page content
     *
     * @see   FrontController::initContent()
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        parent::initContent();

        /* variables available in the custom scripts:
            - list of products with few info
            - products total
            - shipping total
            - total amount
        */

        $idCart = (int) Tools::getValue('id_cart');
        $idOrder = Order::getOrderByCartId($idCart);
        $order = new Order($idOrder);
        $varProducts = [];

        if (Validate::isLoadedObject($order)) {
            $products = $order->getProducts();
            if ($products) {
                foreach ($products as $product) {
                    $varProducts[] = [
                        'id_product' => $product['id_product'],
                        'name'       => $product['product_name'],
                        'price'      => $product['product_price'],
                        'quantity'   => $product['product_quantity'],
                    ];
                }
            }
        }

        Media::AddJsDef(
            [
                'bought_products'          => $varProducts,
                'total_products_tax_incl'  => $order->total_products_wt,
                'total_products_tax_excl'  => $order->total_products,
                'total_shipping_tax_incl'  => $order->total_shipping_tax_incl,
                'total_shipping_tax_excl'  => $order->total_shipping_tax_excl,
                'total_discounts_tax_incl' => $order->total_discounts_tax_incl,
                'total_discounts_tax_excl' => $order->total_discounts_tax_excl,
                'total_paid_tax_incl'      => $order->total_paid_tax_incl,
                'total_paid_tax_excl'      => $order->total_paid_tax_excl,
                'id_customer'              => $this->context->customer->id,
            ]
        );

        $this->context->smarty->assign(
            [
                'is_guest'                => $this->context->customer->is_guest,
                'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation(),
                'HOOK_PAYMENT_RETURN'     => $this->displayPaymentReturn(),
            ]
        );

        if ($this->context->customer->is_guest) {
            $this->context->smarty->assign(
                [
                    'id_order'           => $this->id_order,
                    'reference_order'    => $this->reference,
                    'id_order_formatted' => sprintf('#%06d', $this->id_order),
                    'email'              => $this->context->customer->email,
                ]
            );
            /* If guest we clear the cookie for security reason */
            $this->context->customer->mylogout();
        }

        $this->setTemplate(_PS_THEME_DIR_.'order-confirmation.tpl');
    }

    /**
     * Execute the hook displayOrderConfirmation
     *
     * @return string|array|false
     *
     * @since 1.0.0
     */
    public function displayOrderConfirmation()
    {
        if (Validate::isUnsignedId($this->id_order)) {
            $params = [];
            $order = new Order($this->id_order);
            $currency = new Currency($order->id_currency);

            if (Validate::isLoadedObject($order)) {
                $params['total_to_pay'] = $order->getOrdersTotalPaid();
                $params['currency'] = $currency->sign;
                $params['objOrder'] = $order;
                $params['currencyObj'] = $currency;

                return Hook::exec('displayOrderConfirmation', $params);
            }
        }

        return false;
    }

    /**
     * Execute the hook displayPaymentReturn
     *
     * @return string|array|false
     *
     * @since 1.0.0
     */
    public function displayPaymentReturn()
    {
        if (Validate::isUnsignedId($this->id_order) && Validate::isUnsignedId($this->id_module)) {
            $params = [];
            $order = new Order($this->id_order);
            $currency = new Currency($order->id_currency);

            if (Validate::isLoadedObject($order)) {
                $params['total_to_pay'] = $order->getOrdersTotalPaid();
                $params['currency'] = $currency->sign;
                $params['objOrder'] = $order;
                $params['currencyObj'] = $currency;

                return Hook::exec('displayPaymentReturn', $params, $this->id_module);
            }
        }

        return false;
    }
}
