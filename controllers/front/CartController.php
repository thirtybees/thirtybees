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
 * Class CartControllerCore
 *
 * @since 1.0.0
 */
class CartControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'cart';
    /** @var bool $ssl */
    public $ssl = true;
    /** @var int $id_product */
    protected $id_product;
    /** @var int $id_product_attribute */
    protected $id_product_attribute;
    /** @var int $id_address_delivery */
    protected $id_address_delivery;
    /** @var int $customization_id */
    protected $customization_id;
    /** @var int $qty */
    protected $qty;
    /** @var bool $ajax_refresh */
    protected $ajax_refresh = false;
    // @codingStandardsIgnoreEnd

    /**
     * This is not a public page, so the canonical redirection is disabled
     *
     * @param string $canonicalUrl
     *
     * @since 1.0.0
     */
    public function canonicalRedirection($canonicalUrl = '')
    {
    }

    /**
     * Initialize cart controller
     *
     * @see   FrontController::init()
     *
     * @since 1.0.0
     */
    public function init()
    {
        parent::init();

        // Send noindex to avoid ghost carts by bots
        header('X-Robots-Tag: noindex, nofollow', true);

        // Get page main parameters
        $this->id_product = (int) Tools::getValue('id_product', null);
        $this->id_product_attribute = (int) Tools::getValue('id_product_attribute', Tools::getValue('ipa'));
        $this->customization_id = (int) Tools::getValue('id_customization');
        $this->qty = abs(Tools::getValue('qty', 1));
        $this->id_address_delivery = (int) Tools::getValue('id_address_delivery');
    }

    /**
     * Post process
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        // Update the cart ONLY if $this->cookies are available, in order to avoid ghost carts created by bots
        if ($this->context->cookie->exists() && !$this->errors && !($this->context->customer->isLogged() && !$this->isTokenValid())) {
            if (Tools::getIsset('add') || Tools::getIsset('update')) {
                $this->processChangeProductInCart();
            } elseif (Tools::getIsset('delete')) {
                $this->processDeleteProductInCart();
            } elseif (Tools::getIsset('changeAddressDelivery')) {
                $this->processChangeProductAddressDelivery();
            } elseif (Tools::getIsset('allowSeperatedPackage')) {
                $this->processAllowSeperatedPackage();
            } elseif (Tools::getIsset('duplicate')) {
                $this->processDuplicateProduct();
            }
            // Make redirection
            if (!$this->errors && !$this->ajax) {
                $queryString = Tools::safeOutput(Tools::getValue('query', null));
                if ($queryString && !Configuration::get('PS_CART_REDIRECT')) {
                    Tools::redirect('index.php?controller=search&search='.$queryString);
                }

                // Redirect to previous page
                if (isset($_SERVER['HTTP_REFERER'])) {
                    preg_match('!http(s?)://(.*)/(.*)!', $_SERVER['HTTP_REFERER'], $regs);
                    if (isset($regs[3]) && !Configuration::get('PS_CART_REDIRECT')) {
                        $url = preg_replace('/(\?)+content_only=1/', '', $_SERVER['HTTP_REFERER']);
                        Tools::redirect($url);
                    }
                }

                Tools::redirect('index.php?controller=order&'.(isset($this->id_product) ? 'ipa='.$this->id_product : ''));
            }
        } elseif (!$this->isTokenValid()) {
            if (Tools::getValue('ajax')) {
                $this->ajaxDie(
                    json_encode(
                        [
                            'hasError' => true,
                            'errors'   => [Tools::displayError('Impossible to add the product to the cart. Please refresh page.')],
                        ]
                    )
                );
            } else {
                Tools::redirect('index.php');
            }
        }
    }

    /**
     * This process add or update a product in the cart
     */
    protected function processChangeProductInCart()
    {
        $mode = (Tools::getIsset('update') && $this->id_product) ? 'update' : 'add';

        if ($this->qty == 0) {
            $this->errors[] = Tools::displayError('Null quantity.', !Tools::getValue('ajax'));
        } elseif (!$this->id_product) {
            $this->errors[] = Tools::displayError('Product not found', !Tools::getValue('ajax'));
        }

        $product = new Product($this->id_product, true, $this->context->language->id);
        if (!$product->id || !$product->active || !$product->checkAccess($this->context->cart->id_customer)) {
            $this->errors[] = Tools::displayError('This product is no longer available.', !Tools::getValue('ajax'));

            return;
        }

        $qtyToCheck = $this->qty;
        $cartProducts = $this->context->cart->getProducts();

        if (is_array($cartProducts)) {
            foreach ($cartProducts as $cartProduct) {
                if ((!isset($this->id_product_attribute) || $cartProduct['id_product_attribute'] == $this->id_product_attribute) &&
                    (isset($this->id_product) && $cartProduct['id_product'] == $this->id_product)
                ) {
                    $qtyToCheck = $cartProduct['cart_quantity'];

                    if (Tools::getValue('op', 'up') == 'down') {
                        $qtyToCheck -= $this->qty;
                    } else {
                        $qtyToCheck += $this->qty;
                    }

                    break;
                }
            }
        }

        // Check product quantity availability
        if ($this->id_product_attribute) {
            if (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attribute::checkAttributeQty($this->id_product_attribute, $qtyToCheck)) {
                $this->errors[] = Tools::displayError('There aren\'t enough products in stock.', !Tools::getValue('ajax'));
            }
        } elseif ($product->hasAttributes()) {
            $minimumQuantity = ($product->out_of_stock == 2) ? !Configuration::get('PS_ORDER_OUT_OF_STOCK') : !$product->out_of_stock;
            $this->id_product_attribute = Product::getDefaultAttribute($product->id, $minimumQuantity);
            if (!$this->id_product_attribute) {
                Tools::redirectAdmin($this->context->link->getProductLink($product));
            } elseif (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attribute::checkAttributeQty($this->id_product_attribute, $qtyToCheck)) {
                $this->errors[] = Tools::displayError('There aren\'t enough products in stock.', !Tools::getValue('ajax'));
            }
        } elseif (!$product->checkQty($qtyToCheck)) {
            $this->errors[] = Tools::displayError('There aren\'t enough products in stock.', !Tools::getValue('ajax'));
        }

        // If no errors, process product addition
        if (!$this->errors && $mode == 'add') {
            // Add cart if no cart found
            if (!$this->context->cart->id) {
                if ($this->context->cookie->id_guest) {
                    $guest = new Guest($this->context->cookie->id_guest);
                    $this->context->cart->mobile_theme = $guest->mobile_theme;
                }
                $this->context->cart->add();
                if ($this->context->cart->id) {
                    $this->context->cookie->id_cart = (int) $this->context->cart->id;
                }
            }

            // Check customizable fields
            if (!$product->hasAllRequiredCustomizableFields() && !$this->customization_id) {
                $this->errors[] = Tools::displayError('Please fill in all of the required fields, and then save your customizations.', !Tools::getValue('ajax'));
            }

            if (!$this->errors) {
                $cartRules = $this->context->cart->getCartRules();
                $availableCartRules = CartRule::getCustomerCartRules($this->context->language->id, (isset($this->context->customer->id) ? $this->context->customer->id : 0), true, true, true, $this->context->cart, false, true);
                $updateQuantity = $this->context->cart->updateQty($this->qty, $this->id_product, $this->id_product_attribute, $this->customization_id, Tools::getValue('op', 'up'), $this->id_address_delivery);
                if ($updateQuantity < 0) {
                    // If product has attribute, minimal quantity is set with minimal quantity of attribute
                    $minimalQuantity = ($this->id_product_attribute) ? Attribute::getAttributeMinimalQty($this->id_product_attribute) : $product->minimal_quantity;
                    $this->errors[] = sprintf(Tools::displayError('You must add %d minimum quantity', !Tools::getValue('ajax')), $minimalQuantity);
                } elseif (!$updateQuantity) {
                    $this->errors[] = Tools::displayError('You already have the maximum quantity available for this product.', !Tools::getValue('ajax'));
                } elseif ((int) Tools::getValue('allow_refresh')) {
                    // If the cart rules has changed, we need to refresh the whole cart
                    $cartRules2 = $this->context->cart->getCartRules();
                    if (count($cartRules2) != count($cartRules)) {
                        $this->ajax_refresh = true;
                    } elseif (count($cartRules2)) {
                        $ruleList = [];
                        foreach ($cartRules2 as $rule) {
                            $ruleList[] = $rule['id_cart_rule'];
                        }
                        foreach ($cartRules as $rule) {
                            if (!in_array($rule['id_cart_rule'], $ruleList)) {
                                $this->ajax_refresh = true;
                                break;
                            }
                        }
                    } else {
                        $availableCartRules2 = CartRule::getCustomerCartRules($this->context->language->id, (isset($this->context->customer->id) ? $this->context->customer->id : 0), true, true, true, $this->context->cart, false, true);
                        if (count($availableCartRules2) != count($availableCartRules)) {
                            $this->ajax_refresh = true;
                        } elseif (count($availableCartRules2)) {
                            $ruleList = [];
                            foreach ($availableCartRules2 as $rule) {
                                $ruleList[] = $rule['id_cart_rule'];
                            }
                            foreach ($cartRules2 as $rule) {
                                if (!in_array($rule['id_cart_rule'], $ruleList)) {
                                    $this->ajax_refresh = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        $removed = CartRule::autoRemoveFromCart();
        CartRule::autoAddToCart();
        if (count($removed) && (int) Tools::getValue('allow_refresh')) {
            $this->ajax_refresh = true;
        }
    }

    /**
     * This process delete a product from the cart
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function processDeleteProductInCart()
    {
        $customizationProduct = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('customization')
                ->where('`id_cart` = '.(int) $this->context->cart->id)
                ->where('`id_product` = '.(int) $this->id_product)
                ->where('`id_customization` = '.(int) $this->customization_id)
        );

        if (count($customizationProduct)) {
            $product = new Product((int) $this->id_product);
            if ($this->id_product_attribute > 0) {
                $minimalQuantity = (int) Attribute::getAttributeMinimalQty($this->id_product_attribute);
            } else {
                $minimalQuantity = (int) $product->minimal_quantity;
            }

            $totalQuantity = 0;
            foreach ($customizationProduct as $custom) {
                $totalQuantity += $custom['quantity'];
            }

            if ($totalQuantity < $minimalQuantity) {
                $this->ajaxDie(
                    json_encode(
                        [
                            'hasError' => true,
                            'errors'   => [sprintf(Tools::displayError('You must add %d minimum quantity', !Tools::getValue('ajax')), $minimalQuantity)],
                        ]
                    )
                );
            }
        }

        if ($this->context->cart->deleteProduct($this->id_product, $this->id_product_attribute, $this->customization_id, $this->id_address_delivery)) {
            Hook::exec(
                'actionAfterDeleteProductInCart',
                [
                    'id_cart'              => (int) $this->context->cart->id,
                    'id_product'           => (int) $this->id_product,
                    'id_product_attribute' => (int) $this->id_product_attribute,
                    'customization_id'     => (int) $this->customization_id,
                    'id_address_delivery'  => (int) $this->id_address_delivery,
                ]
            );

            if (!Cart::getNbProducts((int) $this->context->cart->id)) {
                $this->context->cart->setDeliveryOption(null);
                $this->context->cart->gift = 0;
                $this->context->cart->gift_message = '';
                $this->context->cart->update();
            }
        }
        $removed = CartRule::autoRemoveFromCart();
        CartRule::autoAddToCart();
        if (count($removed) && (int) Tools::getValue('allow_refresh')) {
            $this->ajax_refresh = true;
        }
    }

    /**
     * Process change product delivery address
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function processChangeProductAddressDelivery()
    {
        if (!Configuration::get('PS_ALLOW_MULTISHIPPING')) {
            return;
        }

        $oldIdAddressDelivery = (int) Tools::getValue('old_id_address_delivery');
        $newIdAddressDelivery = (int) Tools::getValue('new_id_address_delivery');

        if (!count(Carrier::getAvailableCarrierList(new Product($this->id_product), null, $newIdAddressDelivery))) {
            $this->ajaxDie(
                json_encode(
                    [
                        'hasErrors' => true,
                        'error'     => Tools::displayError('It is not possible to deliver this product to the selected address.', false),
                    ]
                )
            );
        }

        $this->context->cart->setProductAddressDelivery(
            $this->id_product,
            $this->id_product_attribute,
            $oldIdAddressDelivery,
            $newIdAddressDelivery
        );
    }

    /**
     * Process allow separated package
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function processAllowSeperatedPackage()
    {
        if (!Configuration::get('PS_SHIP_WHEN_AVAILABLE')) {
            return;
        }

        if (Tools::getValue('value') === false) {
            $this->ajaxDie('{"error":true, "error_message": "No value setted"}');
        }

        $this->context->cart->allow_seperated_package = (bool) Tools::getValue('value');
        $this->context->cart->update();
        $this->ajaxDie('{"error":false}');
    }

    /**
     * Process product duplication
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function processDuplicateProduct()
    {
        if (!Configuration::get('PS_ALLOW_MULTISHIPPING')) {
            return;
        }

        $this->context->cart->duplicateProduct(
            $this->id_product,
            $this->id_product_attribute,
            $this->id_address_delivery,
            (int) Tools::getValue('new_id_address_delivery')
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
        $this->setTemplate(_PS_THEME_DIR_.'errors.tpl');
        if (!$this->ajax) {
            parent::initContent();
        }
    }

    /**
     * Display ajax content (this function is called instead of classic display, in ajax mode)
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function displayAjax()
    {
        if ($this->errors) {
            $this->ajaxDie(json_encode(['hasError' => true, 'errors' => $this->errors]));
        }
        if ($this->ajax_refresh) {
            $this->ajaxDie(json_encode(['refresh' => true]));
        }

        // write cookie if can't on destruct
        $this->context->cookie->write();

        if (Tools::getIsset('summary')) {
            $result = [];
            $result['summary'] = $this->context->cart->getSummaryDetails(null, true);
            $result['customizedDatas'] = Product::getAllCustomizedDatas($this->context->cart->id, null, true);
            $result['HOOK_SHOPPING_CART'] = Hook::exec('displayShoppingCartFooter', $result['summary']);
            $result['HOOK_SHOPPING_CART_EXTRA'] = Hook::exec('displayShoppingCart', $result['summary']);

            foreach ($result['summary']['products'] as $key => &$product) {
                $product['quantity_without_customization'] = $product['quantity'];
                if ($result['customizedDatas'] && isset($result['customizedDatas'][(int) $product['id_product']][(int) $product['id_product_attribute']])) {
                    foreach ($result['customizedDatas'][(int) $product['id_product']][(int) $product['id_product_attribute']] as $addresses) {
                        foreach ($addresses as $customization) {
                            $product['quantity_without_customization'] -= (int) $customization['quantity'];
                        }
                    }
                }
            }
            if ($result['customizedDatas']) {
                Product::addCustomizationPrice($result['summary']['products'], $result['customizedDatas']);
            }

            $json = '';
            Hook::exec('actionCartListOverride', ['summary' => $result, 'json' => &$json]);
            $this->ajaxDie(json_encode(array_merge($result, (array) json_decode($json, true))));
        } // @todo create a hook
        elseif (file_exists(_PS_MODULE_DIR_.'/blockcart/blockcart-ajax.php')) {
            require_once(_PS_MODULE_DIR_.'/blockcart/blockcart-ajax.php');
        }
    }

    /**
     * Remove discounts from cart
     *
     * @deprecated 1.0.0
     */
    protected function processRemoveDiscounts()
    {
        Tools::displayAsDeprecated();
        $this->errors = array_merge($this->errors, CartRule::autoRemoveFromCart());
    }
}
