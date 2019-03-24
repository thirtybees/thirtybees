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
 * Class OrderOpcControllerCore
 *
 * @since 1.0.0
 */
class OrderOpcControllerCore extends ParentOrderController
{
    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'order-opc';
    /** @var bool $isLogged */
    public $isLogged;
    /** @var bool $ajax_refresh */
    protected $ajax_refresh = false;
    // @codingStandardsIgnoreEnd

    /**
     * Initialize order opc controller
     *
     * @see FrontController::init()
     */
    public function init()
    {
        parent::init();

        if ($this->nbProducts) {
            $this->context->smarty->assign('virtual_cart', $this->context->cart->isVirtualCart());
        }

        $this->context->smarty->assign('is_multi_address_delivery', $this->context->cart->isMultiAddressDelivery() || ((int) Tools::getValue('multi-shipping') == 1));
        $this->context->smarty->assign('open_multishipping_fancybox', (int) Tools::getValue('multi-shipping') == 1);

        if ($this->context->cart->nbProducts()) {
            if (Tools::isSubmit('ajax')) {
                if (Tools::isSubmit('method')) {
                    switch (Tools::getValue('method')) {
                        case 'updateMessage':
                            if (Tools::isSubmit('message')) {
                                $txtMessage = urldecode(Tools::getValue('message'));
                                $this->_updateMessage($txtMessage);
                                if (count($this->errors)) {
                                    $this->ajaxDie('{"hasError" : true, "errors" : ["'.implode('\',\'', $this->errors).'"]}');
                                }
                                $this->ajaxDie(true);
                            }
                            break;

                        case 'updateCarrierAndGetPayments':
                            if ((Tools::isSubmit('delivery_option') || Tools::isSubmit('id_carrier')) && Tools::isSubmit('recyclable') && Tools::isSubmit('gift') && Tools::isSubmit('gift_message')) {
                                $this->_assignWrappingAndTOS();
                                if ($this->_processCarrier()) {
                                    $carriers = $this->context->cart->simulateCarriersOutput();
                                    $return = array_merge(
                                        [
                                            'HOOK_TOP_PAYMENT'   => Hook::exec('displayPaymentTop'),
                                            'HOOK_PAYMENT'       => $this->_getPaymentMethods(),
                                            'carrier_data'       => $this->_getCarrierList(),
                                            'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', ['carriers' => $carriers]),
                                        ],
                                        $this->getFormatedSummaryDetail()
                                    );
                                    Cart::addExtraCarriers($return);
                                    $this->ajaxDie(json_encode($return));
                                } else {
                                    $this->errors[] = Tools::displayError('An error occurred while updating the cart.');
                                }
                                if (count($this->errors)) {
                                    $this->ajaxDie('{"hasError" : true, "errors" : ["'.implode('\',\'', $this->errors).'"]}');
                                }
                                exit;
                            }
                            break;

                        case 'updateTOSStatusAndGetPayments':
                            if (Tools::isSubmit('checked')) {
                                $this->context->cookie->checkedTOS = (int) Tools::getValue('checked');
                                $this->ajaxDie(
                                    json_encode(
                                        [
                                            'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
                                            'HOOK_PAYMENT'     => $this->_getPaymentMethods(),
                                        ]
                                    )
                                );
                            }
                            break;

                        case 'getCarrierList':
                            $this->ajaxDie(json_encode($this->_getCarrierList()));
                            break;

                        case 'editCustomer':
                            if (!$this->isLogged || !$this->context->customer->is_guest) {
                                exit;
                            }

                            if (Validate::isEmail($email = Tools::getValue('email')) && !empty($email)) {
                                if (Customer::customerExists($email)) {
                                    $this->errors[] = Tools::displayError('An account using this email address has already been registered.', false);
                                }
                            }

                            if (Tools::getValue('years')) {
                                $this->context->customer->birthday = (int) Tools::getValue('years').'-'.(int) Tools::getValue('months').'-'.(int) Tools::getValue('days');
                            }

                            $_POST['lastname'] = $_POST['customer_lastname'];
                            $_POST['firstname'] = $_POST['customer_firstname'];
                            $this->errors = array_merge($this->errors, $this->context->customer->validateController());
                            $this->context->customer->newsletter = (int) Tools::isSubmit('newsletter');
                            $this->context->customer->optin = (int) Tools::isSubmit('optin');
                            $this->context->customer->is_guest = (Tools::isSubmit('is_new_customer') ? !Tools::getValue('is_new_customer', 1) : 0);
                            $return = [
                                'hasError'    => !empty($this->errors),
                                'errors'      => $this->errors,
                                'id_customer' => (int) $this->context->customer->id,
                                'token'       => Tools::getToken(false),
                            ];
                            if (!count($this->errors)) {
                                $return['isSaved'] = (bool) $this->context->customer->update();
                            } else {
                                $return['isSaved'] = false;
                            }
                            $this->ajaxDie(json_encode($return));
                            break;

                        case 'getAddressBlockAndCarriersAndPayments':
                            if ($this->context->customer->isLogged() || $this->context->customer->isGuest()) {
                                // check if customer have addresses
                                if (!Customer::getAddressesTotalById($this->context->customer->id)) {
                                    $this->ajaxDie(json_encode(['no_address' => 1]));
                                }
                                if (file_exists(_PS_MODULE_DIR_.'blockuserinfo/blockuserinfo.php')) {
                                    include_once(_PS_MODULE_DIR_.'blockuserinfo/blockuserinfo.php');
                                    $blockUserInfo = new BlockUserInfo();
                                }
                                $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());
                                $this->_processAddressFormat();
                                $this->_assignAddress();

                                if (!($formatedAddressFieldsValuesList = $this->context->smarty->getTemplateVars('formatedAddressFieldsValuesList'))) {
                                    $formatedAddressFieldsValuesList = [];
                                }

                                // Wrapping fees
                                $wrappingFees = $this->context->cart->getGiftWrappingPrice(false);
                                $wrappingFeesTaxInc = $this->context->cart->getGiftWrappingPrice();
                                $isAdvApi = Tools::getValue('isAdvApi');

                                if ($isAdvApi) {
                                    $tpl = 'order-address-advanced.tpl';
                                    $this->context->smarty->assign(
                                        ['products' => $this->context->cart->getProducts()]
                                    );
                                } else {
                                    $tpl = 'order-address.tpl';
                                }

                                $return = array_merge(
                                    [
                                        'order_opc_adress'                => $this->context->smarty->fetch(_PS_THEME_DIR_.$tpl),
                                        'block_user_info'                 => (isset($blockUserInfo) ? $blockUserInfo->hookDisplayTop([]) : ''),
                                        'block_user_info_nav'             => (isset($blockUserInfo) ? $blockUserInfo->hookDisplayNav([]) : ''),
                                        'formatedAddressFieldsValuesList' => $formatedAddressFieldsValuesList,
                                        'carrier_data'                    => ($isAdvApi ? '' : $this->_getCarrierList()),
                                        'HOOK_TOP_PAYMENT'                => ($isAdvApi ? '' : Hook::exec('displayPaymentTop')),
                                        'HOOK_PAYMENT'                    => ($isAdvApi ? '' : $this->_getPaymentMethods()),
                                        'no_address'                      => 0,
                                        'gift_price'                      => Tools::displayPrice(
                                            Tools::convertPrice(
                                                Product::getTaxCalculationMethod() == 1 ? $wrappingFees : $wrappingFeesTaxInc,
                                                new Currency((int) $this->context->cookie->id_currency)
                                            )
                                        ),
                                    ],
                                    $this->getFormatedSummaryDetail()
                                );
                                $this->ajaxDie(json_encode($return));
                            }
                            die(Tools::displayError());
                            break;

                        case 'makeFreeOrder':
                            /* Bypass payment step if total is 0 */
                            if (($idOrder = $this->_checkFreeOrder()) && $idOrder) {
                                $order = new Order((int) $idOrder);
                                $email = $this->context->customer->email;
                                if ($this->context->customer->is_guest) {
                                    $this->context->customer->logout();
                                } // If guest we clear the cookie for security reason
                                $this->ajaxDie('freeorder:'.$order->reference.':'.$email);
                            }
                            exit;
                            break;

                        case 'updateAddressesSelected':
                            if ($this->context->customer->isLogged(true)) {
                                $addressDelivery = new Address((int) Tools::getValue('id_address_delivery'));
                                $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());
                                $addressInvoice = ((int) Tools::getValue('id_address_delivery') == (int) Tools::getValue('id_address_invoice') ? $addressDelivery : new Address((int) Tools::getValue('id_address_invoice')));
                                if ($addressDelivery->id_customer != $this->context->customer->id || $addressInvoice->id_customer != $this->context->customer->id) {
                                    $this->errors[] = Tools::displayError('This address is not yours.');
                                } elseif (!Address::isCountryActiveById((int) Tools::getValue('id_address_delivery'))) {
                                    $this->errors[] = Tools::displayError('This address is not in a valid area.');
                                } elseif (!Validate::isLoadedObject($addressDelivery) || !Validate::isLoadedObject($addressInvoice) || $addressInvoice->deleted || $addressDelivery->deleted) {
                                    $this->errors[] = Tools::displayError('This address is invalid.');
                                } else {
                                    $this->context->cart->id_address_delivery = (int) Tools::getValue('id_address_delivery');
                                    $this->context->cart->id_address_invoice = Tools::isSubmit('same') ? $this->context->cart->id_address_delivery : (int) Tools::getValue('id_address_invoice');
                                    if (!$this->context->cart->update()) {
                                        $this->errors[] = Tools::displayError('An error occurred while updating your cart.');
                                    }

                                    $infos = Address::getCountryAndState((int) $this->context->cart->id_address_delivery);
                                    if (isset($infos['id_country']) && $infos['id_country']) {
                                        $country = new Country((int) $infos['id_country']);
                                        $this->context->country = $country;
                                    }

                                    // Address has changed, so we check if the cart rules still apply
                                    $cartRules = $this->context->cart->getCartRules();
                                    CartRule::autoRemoveFromCart($this->context);
                                    CartRule::autoAddToCart($this->context);
                                    if ((int) Tools::getValue('allow_refresh')) {
                                        // If the cart rules has changed, we need to refresh the whole cart
                                        $cartRules2 = $this->context->cart->getCartRules();
                                        if (count($cartRules2) != count($cartRules)) {
                                            $this->ajax_refresh = true;
                                        } else {
                                            $rule_list = [];
                                            foreach ($cartRules2 as $rule) {
                                                $rule_list[] = $rule['id_cart_rule'];
                                            }
                                            foreach ($cartRules as $rule) {
                                                if (!in_array($rule['id_cart_rule'], $rule_list)) {
                                                    $this->ajax_refresh = true;
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                    if (!$this->context->cart->isMultiAddressDelivery()) {
                                        $this->context->cart->setNoMultishipping();
                                    } // As the cart is no multishipping, set each delivery address lines with the main delivery address

                                    if (!count($this->errors)) {
                                        $result = $this->_getCarrierList();
                                        // Wrapping fees
                                        $wrappingFees = $this->context->cart->getGiftWrappingPrice(false);
                                        $wrappingFeesTaxInc = $this->context->cart->getGiftWrappingPrice();
                                        $result = array_merge(
                                            $result,
                                            [
                                                'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
                                                'HOOK_PAYMENT'     => $this->_getPaymentMethods(),
                                                'gift_price'       => Tools::displayPrice(Tools::convertPrice(Product::getTaxCalculationMethod() == 1 ? $wrappingFees : $wrappingFeesTaxInc, new Currency((int) $this->context->cookie->id_currency))),
                                                'carrier_data'     => $this->_getCarrierList(),
                                                'refresh'          => (bool) $this->ajax_refresh,
                                            ],
                                            $this->getFormatedSummaryDetail()
                                        );
                                        $this->ajaxDie(json_encode($result));
                                    }
                                }
                                if (count($this->errors)) {
                                    $this->ajaxDie(
                                        json_encode(
                                            [
                                                'hasError' => true,
                                                'errors'   => $this->errors,
                                            ]
                                        )
                                    );
                                }
                            }
                            die(Tools::displayError());
                            break;

                        case 'multishipping':
                            $this->_assignSummaryInformations();
                            $this->context->smarty->assign('product_list', $this->context->cart->getProducts());

                            if ($this->context->customer->id) {
                                $this->context->smarty->assign('address_list', $this->context->customer->getAddresses($this->context->language->id));
                            } else {
                                $this->context->smarty->assign('address_list', []);
                            }
                            $this->setTemplate(_PS_THEME_DIR_.'order-address-multishipping-products.tpl');
                            $this->display();
                            $this->ajaxDie();
                            break;

                        case 'cartReload':
                            $this->_assignSummaryInformations();
                            if ($this->context->customer->id) {
                                $this->context->smarty->assign('address_list', $this->context->customer->getAddresses($this->context->language->id));
                            } else {
                                $this->context->smarty->assign('address_list', []);
                            }
                            $this->context->smarty->assign('opc', true);
                            $this->setTemplate(_PS_THEME_DIR_.'shopping-cart.tpl');
                            $this->display();
                            $this->ajaxDie();
                            break;

                        case 'noMultiAddressDelivery':
                            $this->context->cart->setNoMultishipping();
                            $this->ajaxDie();
                            break;

                        default:
                            throw new PrestaShopException('Unknown method "'.Tools::getValue('method').'"');
                    }
                } else {
                    throw new PrestaShopException('Method is not defined');
                }
            }
        } elseif (Tools::isSubmit('ajax')) {
            $this->errors[] = Tools::displayError('There is no product in your cart.');
            $this->ajaxDie('{"hasError" : true, "errors" : ["'.implode('\',\'', $this->errors).'"]}');
        }
    }

    /**
     * Get payment methods
     *
     * @return array|string
     *
     * @since 1.0.0
     */
    protected function _getPaymentMethods()
    {
        if (!$this->isLogged) {
            return '<p class="warning">'.Tools::displayError('Please sign in to see payment methods.').'</p>';
        }
        if ($this->context->cart->OrderExists()) {
            return '<p class="warning">'.Tools::displayError('Error: This order has already been validated.').'</p>';
        }
        if (!$this->context->cart->id_customer || !Customer::customerIdExistsStatic($this->context->cart->id_customer) || Customer::isBanned($this->context->cart->id_customer)) {
            return '<p class="warning">'.Tools::displayError('Error: No customer.').'</p>';
        }
        $addressDelivery = new Address($this->context->cart->id_address_delivery);
        $addressInvoice = ($this->context->cart->id_address_delivery == $this->context->cart->id_address_invoice ? $addressDelivery : new Address($this->context->cart->id_address_invoice));
        if (!$this->context->cart->id_address_delivery || !$this->context->cart->id_address_invoice || !Validate::isLoadedObject($addressDelivery) || !Validate::isLoadedObject($addressInvoice) || $addressInvoice->deleted || $addressDelivery->deleted) {
            return '<p class="warning">'.Tools::displayError('Error: Please select an address.').'</p>';
        }
        if (count($this->context->cart->getDeliveryOptionList()) == 0 && !$this->context->cart->isVirtualCart()) {
            if ($this->context->cart->isMultiAddressDelivery()) {
                return '<p class="warning">'.Tools::displayError('Error: None of your chosen carriers deliver to some of the addresses you have selected.').'</p>';
            } else {
                return '<p class="warning">'.Tools::displayError('Error: None of your chosen carriers deliver to the address you have selected.').'</p>';
            }
        }
        if (!$this->context->cart->getDeliveryOption(null, false) && !$this->context->cart->isVirtualCart()) {
            return '<p class="warning">'.Tools::displayError('Error: Please choose a carrier.').'</p>';
        }
        if (!$this->context->cart->id_currency) {
            return '<p class="warning">'.Tools::displayError('Error: No currency has been selected.').'</p>';
        }
        if (!$this->context->cookie->checkedTOS && Configuration::get('PS_CONDITIONS')) {
            return '<p class="warning">'.Tools::displayError('Please accept the Terms of Service.').'</p>';
        }

        /* If some products have disappear */
        if (is_array($product = $this->context->cart->checkQuantities(true))) {
            return '<p class="warning">'.sprintf(Tools::displayError('An item (%s) in your cart is no longer available in this quantity. You cannot proceed with your order until the quantity is adjusted.'), $product['name']).'</p>';
        }

        if ((int) $idProduct = $this->context->cart->checkProductsAccess()) {
            return '<p class="warning">'.sprintf(Tools::displayError('An item in your cart is no longer available (%s). You cannot proceed with your order.'), Product::getProductName((int) $idProduct)).'</p>';
        }

        /* Check minimal amount */
        $currency = Currency::getCurrency((int) $this->context->cart->id_currency);

        $minimalPurchase = Tools::convertPrice((float) Configuration::get('PS_PURCHASE_MINIMUM'), $currency);
        if ($this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS) < $minimalPurchase) {
            return '<p class="warning">'.sprintf(
                    Tools::displayError('A minimum purchase total of %1s (tax excl.) is required to validate your order, current purchase total is %2s (tax excl.).'),
                    Tools::displayPrice($minimalPurchase, $currency), Tools::displayPrice($this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS), $currency)
                ).'</p>';
        }

        /* Bypass payment step if total is 0 */
        if ($this->context->cart->getOrderTotal() <= 0) {
            return '<p class="center"><button class="button btn btn-default button-medium" name="confirmOrder" id="confirmOrder" onclick="confirmFreeOrder();" type="submit"> <span>'.Tools::displayError('I confirm my order.').'</span></button></p>';
        }

        $return = Hook::exec('displayPayment');
        if (!$return) {
            return '<p class="warning">'.Tools::displayError('No payment method is available for use at this time. ').'</p>';
        }

        return $return;
    }

    /**
     * Get carrier list
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function _getCarrierList()
    {
        $addressDelivery = new Address($this->context->cart->id_address_delivery);

        $cms = new CMS(Configuration::get('PS_CONDITIONS_CMS_ID'), $this->context->language->id);
        $linkConditions = $this->context->link->getCMSLink($cms, $cms->link_rewrite, Configuration::get('PS_SSL_ENABLED'));
        if (!strpos($linkConditions, '?')) {
            $linkConditions .= '?content_only=1';
        } else {
            $linkConditions .= '&content_only=1';
        }

        $carriers = $this->context->cart->simulateCarriersOutput();
        $deliveryOption = $this->context->cart->getDeliveryOption(null, false, false);

        $wrappingFees = $this->context->cart->getGiftWrappingPrice(false);
        $wrappingFeesTaxInc = $this->context->cart->getGiftWrappingPrice();
        $oldMessage = Message::getMessageByCartId((int) $this->context->cart->id);

        $freeShipping = false;
        foreach ($this->context->cart->getCartRules() as $rule) {
            if ($rule['free_shipping'] && !$rule['carrier_restriction']) {
                $freeShipping = true;
                break;
            }
        }

        $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());

        $vars = [
            'advanced_payment_api'        => (bool) Configuration::get('PS_ADVANCED_PAYMENT_API'),
            'free_shipping'               => $freeShipping,
            'checkedTOS'                  => (int) $this->context->cookie->checkedTOS,
            'recyclablePackAllowed'       => (int) Configuration::get('PS_RECYCLABLE_PACK'),
            'giftAllowed'                 => (int) Configuration::get('PS_GIFT_WRAPPING'),
            'cms_id'                      => (int) Configuration::get('PS_CONDITIONS_CMS_ID'),
            'conditions'                  => (int) Configuration::get('PS_CONDITIONS'),
            'link_conditions'             => $linkConditions,
            'recyclable'                  => (int) $this->context->cart->recyclable,
            'gift_wrapping_price'         => (float) $wrappingFees,
            'total_wrapping_cost'         => Tools::convertPrice($wrappingFeesTaxInc, $this->context->currency),
            'total_wrapping_tax_exc_cost' => Tools::convertPrice($wrappingFees, $this->context->currency),
            'delivery_option_list'        => $this->context->cart->getDeliveryOptionList(),
            'carriers'                    => $carriers,
            'checked'                     => $this->context->cart->simulateCarrierSelectedOutput(),
            'delivery_option'             => $deliveryOption,
            'address_collection'          => $this->context->cart->getAddressCollection(),
            'opc'                         => true,
            'oldMessage'                  => isset($oldMessage['message']) ? $oldMessage['message'] : '',
            'HOOK_BEFORECARRIER'          => Hook::exec(
                'displayBeforeCarrier',
                [
                    'carriers'             => $carriers,
                    'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
                    'delivery_option'      => $deliveryOption,
                ]
            ),
        ];

        Cart::addExtraCarriers($vars);

        $this->context->smarty->assign($vars);

        if (!Address::isCountryActiveById((int) $this->context->cart->id_address_delivery) && $this->context->cart->id_address_delivery != 0) {
            $this->errors[] = Tools::displayError('This address is not in a valid area.');
        } elseif ((!Validate::isLoadedObject($addressDelivery) || $addressDelivery->deleted) && $this->context->cart->id_address_delivery != 0) {
            $this->errors[] = Tools::displayError('This address is invalid.');
        } else {
            $result = [
                'HOOK_BEFORECARRIER' => Hook::exec(
                    'displayBeforeCarrier',
                    [
                        'carriers'             => $carriers,
                        'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
                        'delivery_option'      => $this->context->cart->getDeliveryOption(null, true),
                    ]
                ),
                'carrier_block'      => $this->context->smarty->fetch(_PS_THEME_DIR_.'order-carrier.tpl'),
            ];

            Cart::addExtraCarriers($result);

            return $result;
        }
        if (count($this->errors)) {
            return [
                'hasError'      => true,
                'errors'        => $this->errors,
                'carrier_block' => $this->context->smarty->fetch(_PS_THEME_DIR_.'order-carrier.tpl'),
            ];
        }
    }

    protected function getFormatedSummaryDetail()
    {
        $result = [
            'summary'         => $this->context->cart->getSummaryDetails(),
            'customizedDatas' => Product::getAllCustomizedDatas($this->context->cart->id, null, true),
        ];

        foreach ($result['summary']['products'] as $key => &$product) {
            $product['quantity_without_customization'] = $product['quantity'];
            if ($result['customizedDatas']) {
                if (isset($result['customizedDatas'][(int) $product['id_product']][(int) $product['id_product_attribute']])) {
                    foreach ($result['customizedDatas'][(int) $product['id_product']][(int) $product['id_product_attribute']] as $addresses) {
                        foreach ($addresses as $customization) {
                            $product['quantity_without_customization'] -= (int) $customization['quantity'];
                        }
                    }
                }
            }
        }

        if ($result['customizedDatas']) {
            Product::addCustomizationPrice($result['summary']['products'], $result['customizedDatas']);
        }

        return $result;
    }

    /**
     * Process address format
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function _processAddressFormat()
    {
        $addressDelivery = new Address((int) $this->context->cart->id_address_delivery);
        $addressInvoice = new Address((int) $this->context->cart->id_address_invoice);

        $invAdrFields = AddressFormat::getOrderedAddressFields((int) $addressDelivery->id_country, false, true);
        $dlvAdrFields = AddressFormat::getOrderedAddressFields((int) $addressInvoice->id_country, false, true);
        $requireFormFieldsList = AddressFormat::getFieldsRequired();

        // Add missing require fields for a new user susbscription form
        foreach ($requireFormFieldsList as $fieldName) {
            if (!in_array($fieldName, $dlvAdrFields)) {
                $dlvAdrFields[] = trim($fieldName);
            }
        }

        foreach ($requireFormFieldsList as $fieldName) {
            if (!in_array($fieldName, $invAdrFields)) {
                $invAdrFields[] = trim($fieldName);
            }
        }

        $invAllFields = [];
        foreach ($invAdrFields as $fieldsLine) {
            foreach (explode(' ', $fieldsLine) as $fieldItem) {
                $invAllFields[] = trim($fieldItem);
            }
        }
        $invAdrFields = array_unique($invAdrFields);
        $invAllFields = array_unique($invAllFields);

        $dlvAllFields = [];
        foreach ($dlvAdrFields as $fieldsLine) {
            foreach (explode(' ', $fieldsLine) as $fieldItem) {
                $dlvAllFields[] = trim($fieldItem);
            }
        }
        $dlvAdrFields = array_unique($dlvAdrFields);
        $dlvAllFields = array_unique($dlvAllFields);

        $this->context->smarty->assign(
            [
                'inv_adr_fields'  => $invAdrFields,
                'inv_all_fields'  => $invAllFields,
                'dlv_adr_fields'  => $dlvAdrFields,
                'dlv_all_fields'  => $dlvAllFields,
                'required_fields' => $requireFormFieldsList,
            ]
        );
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

        if (!$this->useMobileTheme()) {
            // Adding CSS style sheet
            $this->addCSS(_THEME_CSS_DIR_.'order-opc.css');
            // Adding JS files
            $this->addJS(_THEME_JS_DIR_.'order-opc.js');
            $this->addJqueryPlugin('scrollTo');
        } else {
            $this->addJS(_THEME_MOBILE_JS_DIR_.'opc.js');
        }

        $this->addJS(
            [
                _THEME_JS_DIR_.'tools/vatManagement.js',
                _THEME_JS_DIR_.'tools/statesManagement.js',
                _THEME_JS_DIR_.'order-carrier.js',
                _PS_JS_DIR_.'validate.js',
            ]
        );
    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @return void
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version.
     * @version 1.0.6 Use VatNumber::assignTemplateVars().
     */
    public function initContent()
    {
        parent::initContent();

        /* id_carrier is not defined in database before choosing a carrier, set it to a default one to match a potential cart _rule */
        if (empty($this->context->cart->id_carrier)) {
            $checked = $this->context->cart->simulateCarrierSelectedOutput();
            $checked = ((int) Cart::desintifier($checked));
            $this->context->cart->id_carrier = $checked;
            $this->context->cart->update();
            CartRule::autoRemoveFromCart($this->context);
            CartRule::autoAddToCart($this->context);
        }

        // SHOPPING CART
        $this->_assignSummaryInformations();
        // WRAPPING AND TOS
        $this->_assignWrappingAndTOS();

        if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')) {
            $countries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        } else {
            $countries = Country::getCountries($this->context->language->id, true);
        }

        // If a rule offer free-shipping, force hidding shipping prices
        $freeShipping = false;
        foreach ($this->context->cart->getCartRules() as $rule) {
            if ($rule['free_shipping'] && !$rule['carrier_restriction']) {
                $freeShipping = true;
                break;
            }
        }

        if (Module::isInstalled('vatnumber')
            && Module::isEnabled('vatnumber')
            && file_exists(_PS_MODULE_DIR_.'vatnumber/vatnumber.php')) {
            include_once _PS_MODULE_DIR_.'vatnumber/vatnumber.php';

            if (method_exists('VatNumber', 'assignTemplateVars')) {
                VatNumber::assignTemplateVars($this->context);
            }
        }

        $this->context->smarty->assign(
            [
                'free_shipping'             => $freeShipping,
                'isGuest'                   => isset($this->context->cookie->is_guest) ? $this->context->cookie->is_guest : 0,
                'countries'                 => $countries,
                'sl_country'                => (int) Tools::getCountry(),
                'PS_GUEST_CHECKOUT_ENABLED' => Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
                'errorCarrier'              => Tools::displayError('You must choose a carrier.', false),
                'errorTOS'                  => Tools::displayError('You must accept the Terms of Service.', false),
                'isPaymentStep'             => isset($_GET['isPaymentStep']) && $_GET['isPaymentStep'],
                'genders'                   => Gender::getGenders(),
                'one_phone_at_least'        => (int) Configuration::get('PS_ONE_PHONE_AT_LEAST'),
                'HOOK_CREATE_ACCOUNT_FORM'  => Hook::exec('displayCustomerAccountForm'),
                'HOOK_CREATE_ACCOUNT_TOP'   => Hook::exec('displayCustomerAccountFormTop'),
            ]
        );
        $years = Tools::dateYears();
        $months = Tools::dateMonths();
        $days = Tools::dateDays();
        $this->context->smarty->assign(
            [
                'years'  => $years,
                'months' => $months,
                'days'   => $days,
            ]
        );

        /* Load guest informations */
        if ($this->isLogged && $this->context->cookie->is_guest) {
            $this->context->smarty->assign('guestInformations', $this->_getGuestInformations());
        }
        // ADDRESS
        if ($this->isLogged) {
            $this->_assignAddress();
        }
        // CARRIER
        $this->_assignCarrier();
        // PAYMENT
        $this->_assignPayment();
        Tools::safePostVars();

        $newsletter = Configuration::get('PS_CUSTOMER_NWSL');
        $this->context->smarty->assign('newsletter', $newsletter);
        $this->context->smarty->assign('optin', (bool) Configuration::get('PS_CUSTOMER_OPTIN'));
        $this->context->smarty->assign('field_required', $this->context->customer->validateFieldsRequiredDatabase());

        $this->_processAddressFormat();

        if ((bool) Configuration::get('PS_ADVANCED_PAYMENT_API')) {
            $this->addJS(_THEME_JS_DIR_.'advanced-payment-api.js');
            $this->setTemplate(_PS_THEME_DIR_.'order-opc-advanced.tpl');
        } else {
            $this->setTemplate(_PS_THEME_DIR_.'order-opc.tpl');
        }
    }

    /**
     * Get Guest information
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function _getGuestInformations()
    {
        $customer = $this->context->customer;
        $addressDelivery = new Address($this->context->cart->id_address_delivery);

        $idAddressInvoice = $this->context->cart->id_address_invoice != $this->context->cart->id_address_delivery ? (int) $this->context->cart->id_address_invoice : 0;
        $addressInvoice = new Address($idAddressInvoice);

        if ($customer->birthday) {
            $birthday = explode('-', $customer->birthday);
        } else {
            $birthday = ['0', '0', '0'];
        }

        return [
            'id_customer'          => (int) $customer->id,
            'email'                => Tools::htmlentitiesUTF8($customer->email),
            'customer_lastname'    => Tools::htmlentitiesUTF8($customer->lastname),
            'customer_firstname'   => Tools::htmlentitiesUTF8($customer->firstname),
            'newsletter'           => (int) $customer->newsletter,
            'optin'                => (int) $customer->optin,
            'id_address_delivery'  => (int) $this->context->cart->id_address_delivery,
            'company'              => Tools::htmlentitiesUTF8($addressDelivery->company),
            'lastname'             => Tools::htmlentitiesUTF8($addressDelivery->lastname),
            'firstname'            => Tools::htmlentitiesUTF8($addressDelivery->firstname),
            'vat_number'           => Tools::htmlentitiesUTF8($addressDelivery->vat_number),
            'dni'                  => Tools::htmlentitiesUTF8($addressDelivery->dni),
            'address1'             => Tools::htmlentitiesUTF8($addressDelivery->address1),
            'postcode'             => Tools::htmlentitiesUTF8($addressDelivery->postcode),
            'city'                 => Tools::htmlentitiesUTF8($addressDelivery->city),
            'phone'                => Tools::htmlentitiesUTF8($addressDelivery->phone),
            'phone_mobile'         => Tools::htmlentitiesUTF8($addressDelivery->phone_mobile),
            'id_country'           => (int) $addressDelivery->id_country,
            'id_state'             => (int) $addressDelivery->id_state,
            'id_gender'            => (int) $customer->id_gender,
            'sl_year'              => $birthday[0],
            'sl_month'             => $birthday[1],
            'sl_day'               => $birthday[2],
            'company_invoice'      => Tools::htmlentitiesUTF8($addressInvoice->company),
            'lastname_invoice'     => Tools::htmlentitiesUTF8($addressInvoice->lastname),
            'firstname_invoice'    => Tools::htmlentitiesUTF8($addressInvoice->firstname),
            'vat_number_invoice'   => Tools::htmlentitiesUTF8($addressInvoice->vat_number),
            'dni_invoice'          => Tools::htmlentitiesUTF8($addressInvoice->dni),
            'address1_invoice'     => Tools::htmlentitiesUTF8($addressInvoice->address1),
            'address2_invoice'     => Tools::htmlentitiesUTF8($addressInvoice->address2),
            'postcode_invoice'     => Tools::htmlentitiesUTF8($addressInvoice->postcode),
            'city_invoice'         => Tools::htmlentitiesUTF8($addressInvoice->city),
            'phone_invoice'        => Tools::htmlentitiesUTF8($addressInvoice->phone),
            'phone_mobile_invoice' => Tools::htmlentitiesUTF8($addressInvoice->phone_mobile),
            'id_country_invoice'   => (int) $addressInvoice->id_country,
            'id_state_invoice'     => (int) $addressInvoice->id_state,
            'id_address_invoice'   => $idAddressInvoice,
            'invoice_company'      => Tools::htmlentitiesUTF8($addressInvoice->company),
            'invoice_lastname'     => Tools::htmlentitiesUTF8($addressInvoice->lastname),
            'invoice_firstname'    => Tools::htmlentitiesUTF8($addressInvoice->firstname),
            'invoice_vat_number'   => Tools::htmlentitiesUTF8($addressInvoice->vat_number),
            'invoice_dni'          => Tools::htmlentitiesUTF8($addressInvoice->dni),
            'invoice_address'      => $this->context->cart->id_address_invoice !== $this->context->cart->id_address_delivery,
            'invoice_address1'     => Tools::htmlentitiesUTF8($addressInvoice->address1),
            'invoice_address2'     => Tools::htmlentitiesUTF8($addressInvoice->address2),
            'invoice_postcode'     => Tools::htmlentitiesUTF8($addressInvoice->postcode),
            'invoice_city'         => Tools::htmlentitiesUTF8($addressInvoice->city),
            'invoice_phone'        => Tools::htmlentitiesUTF8($addressInvoice->phone),
            'invoice_phone_mobile' => Tools::htmlentitiesUTF8($addressInvoice->phone_mobile),
            'invoice_id_country'   => (int) $addressInvoice->id_country,
            'invoice_id_state'     => (int) $addressInvoice->id_state,
        ];
    }

    /**
     * Assign carrier
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function _assignCarrier()
    {
        if (!$this->isLogged) {
            $carriers = $this->context->cart->simulateCarriersOutput();
            $oldMessage = Message::getMessageByCartId((int) $this->context->cart->id);
            $this->context->smarty->assign(
                [
                    'HOOK_EXTRACARRIER'      => null,
                    'HOOK_EXTRACARRIER_ADDR' => null,
                    'oldMessage'             => isset($oldMessage['message']) ? $oldMessage['message'] : '',
                    'HOOK_BEFORECARRIER'     => Hook::exec(
                        'displayBeforeCarrier', [
                            'carriers'             => $carriers,
                            'checked'              => $this->context->cart->simulateCarrierSelectedOutput(),
                            'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
                            'delivery_option'      => $this->context->cart->getDeliveryOption(null, true),
                        ]
                    ),
                ]
            );
        } else {
            parent::_assignCarrier();
        }
    }

    /**
     * Assign payment
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function _assignPayment()
    {
        if ((bool) Configuration::get('PS_ADVANCED_PAYMENT_API')) {
            $this->context->smarty->assign(
                [
                    'HOOK_TOP_PAYMENT'      => ($this->isLogged ? Hook::exec('displayPaymentTop') : ''),
                    'HOOK_PAYMENT'          => $this->_getPaymentMethods(),
                    'HOOK_ADVANCED_PAYMENT' => Hook::exec('advancedPaymentOptions', [], null, true),
                    'link_conditions'       => $this->link_conditions,
                ]
            );
        } else {
            $this->context->smarty->assign(
                [
                    'HOOK_TOP_PAYMENT' => ($this->isLogged ? Hook::exec('displayPaymentTop') : ''),
                    'HOOK_PAYMENT'     => $this->_getPaymentMethods(),
                ]
            );
        }
    }
}
