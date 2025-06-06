<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class AdminCartsControllerCore
 *
 * @property Cart|null $object
 */
class AdminCartsControllerCore extends AdminController
{
    /**
     * AdminCartsControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'cart';
        $this->className = 'Cart';
        $this->lang = false;
        $this->explicitSelect = true;

        $this->addRowAction('view');
        $this->addRowAction('delete');
        $this->allow_export = true;
        $this->_orderWay = 'DESC';

        $this->_select = 'CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) `customer`, a.id_cart total, ca.name carrier, o.id_order,
		IF (IFNULL(o.id_order, \''.$this->l('Non ordered').'\') = \''.$this->l('Non ordered').'\', IF(TIME_TO_SEC(TIMEDIFF(\''.pSQL(date('Y-m-d H:i:00', time())).'\', a.`date_add`)) > 86400, \''.$this->l('Abandoned cart').'\', \''.$this->l('Non ordered').'\'), o.id_order) AS status, a.`date_upd`, IF(o.id_order, 1, 0) badge_success, IF(o.id_order, 0, 1) badge_danger, IF(co.id_guest, 1, 0) id_guest';
        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'customer c ON (c.id_customer = a.id_customer)
		LEFT JOIN '._DB_PREFIX_.'currency cu ON (cu.id_currency = a.id_currency)
		LEFT JOIN '._DB_PREFIX_.'carrier ca ON (ca.id_carrier = a.id_carrier)
		LEFT JOIN '._DB_PREFIX_.'orders o ON (o.id_cart = a.id_cart)
		LEFT JOIN `'._DB_PREFIX_.'connections` co ON (a.id_guest = co.id_guest AND TIME_TO_SEC(TIMEDIFF(\''.pSQL(date('Y-m-d H:i:00', time())).'\', co.`date_add`)) < 1800)';

        if (Tools::getValue('action') && Tools::getValue('action') == 'filterOnlyAbandonedCarts') {
            $this->_having = 'status = \''.$this->l('Abandoned cart').'\'';
        } else {
            $this->_use_found_rows = false;
        }

        $this->fields_list = [
            'id_cart'  => [
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'status'   => [
                'title'        => $this->l('Order ID'),
                'align'        => 'text-center',
                'badge_danger' => true,
                'havingFilter' => true,
            ],
            'customer' => [
                'title'      => $this->l('Customer'),
                'filter_key' => 'c!lastname',
            ],
            'total'    => [
                'title'         => $this->l('Total'),
                'callback'      => 'getOrderTotalUsingTaxCalculationMethod',
                'orderby'       => false,
                'search'        => false,
                'align'         => 'text-right',
                'badge_success' => true,
            ],
            'carrier'  => [
                'title'      => $this->l('Carrier'),
                'align'      => 'text-left',
                'callback'   => 'replaceZeroByShopName',
                'filter_key' => 'ca!name',
            ],
            'date_add' => [
                'title'      => $this->l('Date created'),
                'align'      => 'text-left',
                'type'       => 'datetime',
                'class'      => 'fixed-width-lg',
                'filter_key' => 'a!date_add',
            ],
            'date_upd' => [
                'title'      => $this->l('Date modified'),
                'align'      => 'text-left',
                'type'       => 'datetime',
                'class'      => 'fixed-width-lg',
                'filter_key' => 'a!date_upd',
            ],
            'id_guest' => [
                'title'        => $this->l('Online'),
                'align'        => 'text-center',
                'type'         => 'bool',
                'havingFilter' => true,
                'class'        => 'fixed-width-xs',
                'icon'         => [
                    0 => ['class' => 'icon-'],
                    1 => ['class' => 'icon-user'],
                ],
            ],
        ];
        $this->shopLinkType = 'shop';

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        parent::__construct();
    }

    /**
     * @param int $idCart
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function getOrderTotalUsingTaxCalculationMethod($idCart)
    {
        $context = Context::getContext();
        $context->cart = new Cart($idCart);
        $context->currency = new Currency((int) $context->cart->id_currency);
        $context->customer = new Customer((int) $context->cart->id_customer);

        return Cart::getTotalCart($idCart, true, Cart::BOTH_WITHOUT_SHIPPING);
    }

    /**
     * @param string $echo
     * @param array $tr
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function replaceZeroByShopName($echo, $tr)
    {
        return ($echo == '0' ? Carrier::getCarrierNameFromShopName() : $echo);
    }

    /**
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
		// Get the cookie lifetime setting in hours
		$cookieLifetimeHours = (int) Configuration::get('PS_COOKIE_LIFETIME_FO');

		// Convert cookie lifetime to days
		$cookieLifetimeDays = floor($cookieLifetimeHours / 24);

		if (empty($this->display)) {
			$this->page_header_toolbar_btn['export_cart'] = [
			    'href' => static::$currentIndex.'&exportcart&token='.$this->token,
			    'desc' => $this->l('Export carts', null, null, false),
			    'icon' => 'process-icon-export',
			];

			$this->page_header_toolbar_btn['delete_empty_carts'] = [
			    'href' => static::$currentIndex.'&delete_empty_carts&token='.$this->token,
			    'desc' => $this->l('Delete empty carts', null, null, false),
			    'icon' => 'process-icon-delete',
			];

			$this->page_header_toolbar_btn['delete_old_carts'] = [
			    'href' => self::$currentIndex.'&deleteoldcarts&token='.$this->token,
			    'desc' => $this->l(sprintf('Delete carts older than %d days', $cookieLifetimeDays), null, null, false),
			    'icon' => 'process-icon-delete',
			];
		}

		parent::initPageHeaderToolbar();
	}

    /**
     * @return HelperKpi[]
     *
     * @throws PrestaShopException
     */
    public function getKpis(): array
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
        $kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-carts';
        $helper->icon = 'icon-shopping-cart';
        $helper->color = 'color2';
        $helper->title = $this->l('Abandoned Carts', null, null, false);
        $dateFrom = date($this->context->language->date_format_lite, strtotime('-2 day'));
        $dateTo = date($this->context->language->date_format_lite, strtotime('-1 day'));
        $helper->subtitle = sprintf($this->l('From %s to %s', null, null, false), $dateFrom, $dateTo);
        $helper->href = $this->context->link->getAdminLink('AdminCarts').'&action=filterOnlyAbandonedCarts';
        if (ConfigurationKPI::get('ABANDONED_CARTS') !== false) {
            $helper->value = ConfigurationKPI::get('ABANDONED_CARTS');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=abandoned_cart';
        $helper->refresh = (bool) (ConfigurationKPI::get('ABANDONED_CARTS_EXPIRE') < $time);
        $kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-average-order';
        $helper->icon = 'icon-money';
        $helper->color = 'color3';
        $helper->title = $this->l('Average Order Value', null, null, false);
        $helper->subtitle = $this->l('30 days', null, null, false);
        if (ConfigurationKPI::get('AVG_ORDER_VALUE', $this->context->employee->id_lang) !== false) {
            $helper->value = ConfigurationKPI::get('AVG_ORDER_VALUE', $this->context->employee->id_lang);
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=average_order_value';
        $helper->refresh = (bool) (ConfigurationKPI::get('AVG_ORDER_VALUE_EXPIRE', $this->context->employee->id_lang) < $time);
        $kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-net-profit-visitor';
        $helper->icon = 'icon-user';
        $helper->color = 'color4';
        $helper->title = $this->l('Net Profit per Visitor', null, null, false);
        $helper->subtitle = $this->l('30 days', null, null, false);
        if (ConfigurationKPI::get('NETPROFIT_VISIT') !== false) {
            $helper->value = ConfigurationKPI::get('NETPROFIT_VISIT');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=netprofit_visit';
        $helper->refresh = (bool) (ConfigurationKPI::get('NETPROFIT_VISIT_EXPIRE') < $time);
        $kpis[] = $helper;

        return $kpis;
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderView()
    {
        /** @var Cart $cart */
        if (!($cart = $this->loadObject(true))) {
            return '';
        }
        $customer = new Customer($cart->id_customer);
        $currency = new Currency($cart->id_currency);
        $this->context->cart = $cart;
        $this->context->currency = $currency;
        $this->context->customer = $customer;
        $this->toolbar_title = sprintf($this->l('Cart #%06d'), $this->context->cart->id);
        $products = $cart->getProducts();
        $customizedDatas = Product::getAllCustomizedDatas((int) $cart->id);
        Product::addCustomizationPrice($products, $customizedDatas);
        $summary = $cart->getSummaryDetails();

        /* Display order information */
        $idOrder = Order::getOrderByCartId($cart->id);
        $order = new Order($idOrder);
        if (Validate::isLoadedObject($order)) {
            $taxCalculationMethod = $order->getTaxCalculationMethod();
            $idShop = (int) $order->id_shop;
        } else {
            $idShop = (int) $cart->id_shop;
            $taxCalculationMethod = Group::getPriceDisplayMethod(Group::getCurrent()->id);
        }

        if ($taxCalculationMethod == PS_TAX_EXC) {
            $totalProducts = $summary['total_products'];
            $totalDiscounts = $summary['total_discounts_tax_exc'];
            $totalWrapping = $summary['total_wrapping_tax_exc'];
            $totalPrice = $summary['total_price_without_tax'];
            $totalShipping = $summary['total_shipping_tax_exc'];
        } else {
            $totalProducts = $summary['total_products_wt'];
            $totalDiscounts = $summary['total_discounts'];
            $totalWrapping = $summary['total_wrapping'];
            $totalPrice = $summary['total_price'];
            $totalShipping = $summary['total_shipping'];
        }
        foreach ($products as &$product) {
            if ($taxCalculationMethod == PS_TAX_EXC) {
                $product['product_price'] = $product['price'];
                $product['product_total'] = $product['total'];
            } else {
                $product['product_price'] = $product['price_wt'];
                $product['product_total'] = $product['total_wt'];
            }
            $image = 0;
            $conn = Db::readOnly();
            if (isset($product['id_product_attribute']) && (int) $product['id_product_attribute']) {
                $image = (int)$conn->getValue(
                    (new DbQuery())
                        ->select('`id_image`')
                        ->from('product_attribute_image')
                        ->where('`id_product_attribute` = '.(int) $product['id_product_attribute'])
                );
            }
            if (! $image) {
                $image = (int)$conn->getValue(
                    (new DbQuery())
                        ->select('`id_image`')
                        ->from('image')
                        ->where('`id_product` = '.(int) $product['id_product'])
                        ->where('`cover` = 1')
                );
            }

            $product['qty_in_stock'] = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute'] ?? null, (int) $idShop);

            if ($image) {
                $product['image'] = ImageManager::getProductImageThumbnailTag($image);
            } else {
                $product['image'] = '--';
            }
        }

        $helper = new HelperKpi();
        $helper->id = 'box-kpi-cart';
        $helper->icon = 'icon-shopping-cart';
        $helper->color = 'color1';
        $helper->title = $this->l('Total Cart', null, null, false);
        $helper->subtitle = sprintf($this->l('Cart #%06d', null, null, false), $cart->id);
        $helper->value = Tools::displayPrice($totalPrice, $currency);
        $kpi = $helper->generate();

        $this->tpl_view_vars = [
            'kpi'                    => $kpi,
            'products'               => $products,
            'discounts'              => $cart->getCartRules(),
            'order'                  => $order,
            'cart'                   => $cart,
            'currency'               => $currency,
            'customer'               => $customer,
            'customer_stats'         => $customer->getStats(),
            'total_products'         => $totalProducts,
            'total_discounts'        => $totalDiscounts,
            'total_wrapping'         => $totalWrapping,
            'total_price'            => $totalPrice,
            'total_shipping'         => $totalShipping,
            'customized_datas'       => $customizedDatas,
            'tax_calculation_method' => $taxCalculationMethod,
        ];

        return parent::renderView();
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxPreProcess()
    {
        if ($this->hasEditPermission()) {
            $idCustomer = Tools::getIntValue('id_customer');
            $customer = new Customer((int) $idCustomer);
            $this->context->customer = $customer;
            $idCart = Tools::getIntValue('id_cart');
            if (!$idCart) {
                $idCart = $customer->getLastCart(false);
            }
            $this->context->cart = new Cart((int) $idCart);

            if (!$this->context->cart->id) {
                $this->context->cart->recyclable = 0;
                $this->context->cart->gift = 0;
            }

            if (!$this->context->cart->id_customer) {
                $this->context->cart->id_customer = $idCustomer;
            }
            if (Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists()) {
                return;
            }
            if (!$this->context->cart->secure_key) {
                $this->context->cart->secure_key = $this->context->customer->secure_key;
            }
            if (!$this->context->cart->id_shop) {
                $this->context->cart->id_shop = (int) $this->context->shop->id;
            }
            if (!$this->context->cart->id_lang) {
                $this->context->cart->id_lang = (($idLang = Tools::getIntValue('id_lang')) ? $idLang : Configuration::get('PS_LANG_DEFAULT'));
            }
            if (!$this->context->cart->id_currency) {
                $this->context->cart->id_currency = (($idCurrency = Tools::getIntValue('id_currency')) ? $idCurrency : Configuration::get('PS_CURRENCY_DEFAULT'));
            }

            $addresses = $customer->getAddresses((int) $this->context->cart->id_lang);
            $idAddressDelivery = Tools::getIntValue('id_address_delivery');
            $idAddressInvoice = Tools::getIntValue('id_address_delivery');

            if (!$this->context->cart->id_address_invoice && isset($addresses[0])) {
                $this->context->cart->id_address_invoice = (int) $addresses[0]['id_address'];
            } elseif ($idAddressInvoice) {
                $this->context->cart->id_address_invoice = (int) $idAddressInvoice;
            }
            if (!$this->context->cart->id_address_delivery && isset($addresses[0])) {
                $this->context->cart->id_address_delivery = $addresses[0]['id_address'];
            } elseif ($idAddressDelivery) {
                $this->context->cart->id_address_delivery = (int) $idAddressDelivery;
            }
            $this->context->cart->setNoMultishipping();
            $this->context->cart->save();
            $currency = new Currency((int) $this->context->cart->id_currency);
            $this->context->currency = $currency;
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessDeleteProduct()
    {
        if ($this->hasEditPermission()) {
            $errors = [];
            if ((!$idProduct = Tools::getIntValue('id_product')) || !Validate::isInt($idProduct)) {
                $errors[] = Tools::displayError('Invalid product');
            }
            if (($idProductAttribute = Tools::getIntValue('id_product_attribute')) && !Validate::isInt($idProductAttribute)) {
                $errors[] = Tools::displayError('Invalid combination');
            }
            if (count($errors)) {
                $this->ajaxDie(json_encode($errors));
            }
            if ($this->context->cart->deleteProduct($idProduct, $idProductAttribute, Tools::getIntValue('id_customization'))) {
                $this->ajaxDie(json_encode($this->ajaxReturnVars()));
            }
        }
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxReturnVars()
    {
        $idCart = (int) $this->context->cart->id;
        $messageContent = '';
        if ($message = Message::getMessageByCartId((int) $this->context->cart->id)) {
            $messageContent = $message['message'];
        }
        $cartRules = $this->context->cart->getCartRules(CartRule::FILTER_ACTION_SHIPPING);

        $freeShipping = false;
        if (count($cartRules)) {
            foreach ($cartRules as $cart_rule) {
                if ($cart_rule['id_cart_rule'] == CartRule::getIdByCode(CartRule::BO_ORDER_CODE_PREFIX.(int) $this->context->cart->id)) {
                    $freeShipping = true;
                    break;
                }
            }
        }

        $addresses = $this->context->customer->getAddresses((int) $this->context->cart->id_lang);

        foreach ($addresses as &$data) {
            $address = new Address((int) $data['id_address']);
            $data['formated_address'] = AddressFormat::generateAddress($address, [], "<br />");
        }

        return [
            'summary'              => $this->getCartSummary(),
            'delivery_option_list' => $this->getDeliveryOptionList(),
            'cart'                 => $this->context->cart,
            'currency'             => new Currency($this->context->cart->id_currency),
            'addresses'            => $addresses,
            'id_cart'              => $idCart,
            'order_message'        => $messageContent,
            'link_order'           => $this->context->link->getPageLink(
                'order',
                false,
                (int) $this->context->cart->id_lang,
                'step=3&recover_cart='.$idCart.'&token_cart='.md5(_COOKIE_KEY_.'recover_cart_'.$idCart)
            ),
            'free_shipping'        => (int) $freeShipping,
        ];
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getCartSummary()
    {
        $summary = $this->context->cart->getSummaryDetails(null, true);
        $currency = $this->context->currency;
        if (count($summary['products'])) {
            foreach ($summary['products'] as &$product) {
                $product['numeric_price'] = $product['price'];
                $product['numeric_total'] = $product['total'];
                $product['price'] = str_replace($currency->sign, '', Tools::displayPrice($product['price'], $currency));
                $product['total'] = str_replace($currency->sign, '', Tools::displayPrice($product['total'], $currency));
                $product['image_link'] = $this->context->link->getImageLink($product['link_rewrite'], $product['id_image'], 'small_default');
                if (!isset($product['attributes_small'])) {
                    $product['attributes_small'] = '';
                }
                $product['customized_datas'] = Product::getAllCustomizedDatas((int) $this->context->cart->id, null, true);
            }
        }
        if (count($summary['discounts'])) {
            foreach ($summary['discounts'] as &$voucher) {
                $voucher['value_real'] = Tools::displayPrice($voucher['value_real'], $currency);
            }
        }

        if (isset($summary['gift_products']) && count($summary['gift_products'])) {
            foreach ($summary['gift_products'] as &$product) {
                $product['image_link'] = $this->context->link->getImageLink($product['link_rewrite'], $product['id_image'], 'small_default');
                if (!isset($product['attributes_small'])) {
                    $product['attributes_small'] = '';
                }
            }
        }

        return $summary;
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getDeliveryOptionList()
    {
        $deliveryOptionListFormatted = [];
        $deliveryOptionList = $this->context->cart->getDeliveryOptionList();

        if (!count($deliveryOptionList)) {
            return [];
        }

        $idDefaultCarrier = (int) Configuration::get('PS_CARRIER_DEFAULT');
        foreach (current($deliveryOptionList) as $key => $deliveryOption) {
            $name = '';
            $first = true;
            $idDefaultCarrierDelivery = false;
            foreach ($deliveryOption['carrier_list'] as $carrier) {
                if (!$first) {
                    $name .= ', ';
                } else {
                    $first = false;
                }

                $name .= $carrier['instance']->name;

                if ($deliveryOption['unique_carrier']) {
                    $name .= ' - '.$carrier['instance']->delay[$this->context->employee->id_lang];
                }

                if (!$idDefaultCarrierDelivery) {
                    $idDefaultCarrierDelivery = (int) $carrier['instance']->id;
                }
                if ($carrier['instance']->id == $idDefaultCarrier) {
                    $idDefaultCarrierDelivery = $idDefaultCarrier;
                }
                if (!$this->context->cart->id_carrier) {
                    $this->context->cart->setDeliveryOption([$this->context->cart->id_address_delivery => (int) $carrier['instance']->id.',']);
                    $this->context->cart->save();
                }
            }
            $deliveryOptionListFormatted[] = ['name' => $name, 'key' => $key];
        }

        return $deliveryOptionListFormatted;
    }

    /**
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function ajaxProcessUpdateCustomizationFields()
    {
        if ($this->hasEditPermission()) {
            $errors = [];
            if (Tools::getValue('only_display') != 1) {
                if (!$this->context->cart->id || (!$idProduct = Tools::getIntValue('id_product'))) {
                    return;
                }
                $product = new Product((int) $idProduct);
                if (!$customizationFields = $product->getCustomizationFieldIds()) {
                    return;
                }
                foreach ($customizationFields as $customizationField) {
                    $fieldId = 'customization_'.$idProduct.'_'.$customizationField['id_customization_field'];
                    if ($customizationField['type'] == Product::CUSTOMIZE_TEXTFIELD) {
                        if (!Tools::getValue($fieldId)) {
                            if ($customizationField['required']) {
                                $errors[] = Tools::displayError('Please fill in all the required fields.');
                            }
                            continue;
                        }
                        if (!Validate::isMessage(Tools::getValue($fieldId))) {
                            $errors[] = Tools::displayError('Invalid message');
                        }
                        $this->context->cart->addTextFieldToProduct((int) $product->id, (int) $customizationField['id_customization_field'], Product::CUSTOMIZE_TEXTFIELD, Tools::getValue($fieldId));
                    } elseif ($customizationField['type'] == Product::CUSTOMIZE_FILE) {
                        if (!isset($_FILES[$fieldId]) || empty($_FILES[$fieldId]['tmp_name'])) {
                            if ($customizationField['required']) {
                                $errors[] = Tools::displayError('Please fill in all the required fields.');
                            }
                            continue;
                        }
                        if ($error = ImageManager::validateUpload($_FILES[$fieldId], (int) Configuration::get('PS_PRODUCT_PICTURE_MAX_SIZE'))) {
                            $errors[] = $error;
                        }
                        if (!($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES[$fieldId]['tmp_name'], $tmpName)) {
                            $errors[] = Tools::displayError('An error occurred during the image upload process.');
                        }
                        $fileName = md5(uniqid(rand(), true));
                        if (!ImageManager::resize($tmpName, _PS_UPLOAD_DIR_.$fileName)) {
                            continue;
                        } elseif (!ImageManager::resize($tmpName, _PS_UPLOAD_DIR_.$fileName.'_small', (int) Configuration::get('PS_PRODUCT_PICTURE_WIDTH'), (int) Configuration::get('PS_PRODUCT_PICTURE_HEIGHT'))) {
                            $errors[] = Tools::displayError('An error occurred during the image upload process.');
                        } elseif (!chmod(_PS_UPLOAD_DIR_.$fileName, 0777) || !chmod(_PS_UPLOAD_DIR_.$fileName.'_small', 0777)) {
                            $errors[] = Tools::displayError('An error occurred during the image upload process.');
                        } else {
                            $this->context->cart->addPictureToProduct((int) $product->id, (int) $customizationField['id_customization_field'], Product::CUSTOMIZE_FILE, $fileName);
                        }
                        unlink($tmpName);
                    }
                }
            }
            $this->setMedia();
            $this->initFooter();
            $this->context->smarty->assign(
                [
                    'customization_errors' => implode('<br />', $errors),
                    'css_files'            => $this->css_files,
                ]
            );

            $this->smartyOutputContent('controllers/orders/form_customization_feedback.tpl');
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateQty()
    {
        if ($this->hasEditPermission()) {
            $errors = [];
            $cart = $this->context->cart;

            if (! Validate::isLoadedObject($cart)) {
                return;
            }

            $idProduct = Tools::getIntValue('id_product');
            $idProductAttribute = Tools::getIntValue('id_product_attribute');
            $idCustomization = Tools::getIntValue('id_customization', 0);
            $qty = Tools::getIntValue('qty');

            if ($cart->orderExists()) {
                $errors[] = Tools::displayError('An order has already been placed with this cart.');
            }
            if (! $qty) {
                $errors[] = Tools::displayError('Invalid quantity');
            }

            $product = new Product($idProduct, true, $this->context->language->id);
            if (Validate::isLoadedObject($product)) {
                if ($idProductAttribute) {
                    if (! Product::isAvailableWhenOutOfStock($product->out_of_stock) && !ProductAttribute::checkAttributeQty($idProductAttribute, $qty)) {
                        $errors[] = Tools::displayError('There is not enough product in stock.');
                    }
                } else {
                    if (! $product->checkQty($qty)) {
                        $errors[] = Tools::displayError('There is not enough product in stock.');
                    }
                }
                if (!$idCustomization && !$product->hasAllRequiredCustomizableFields()) {
                    $errors[] = Tools::displayError('Please fill in all the required fields.');
                }
            } else {
                $errors[] = Tools::displayError('This product cannot be added to the cart.');
            }

            if (! $errors) {
                if ($qty < 0) {
                    $qty = (int)abs($qty);
                    $operator = 'down';
                } else {
                    $operator = 'up';
                }

                $qtyUpd = $cart->updateQty($qty, $idProduct, $idProductAttribute, $idCustomization, $operator);
                if (! $qtyUpd) {
                    $errors[] = Tools::displayError('You already have the maximum quantity available for this product.');
                } elseif ($qtyUpd < 0) {
                    $minimalQty = $idProductAttribute ? ProductAttribute::getAttributeMinimalQty((int) $idProductAttribute) : $product->minimal_quantity;
                    $errors[] = sprintf(Tools::displayError('You must add a minimum quantity of %d', false), $minimalQty);
                }
            }

            $this->ajaxDie(json_encode(array_merge($this->ajaxReturnVars(), ['errors' => $errors])));
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateDeliveryOption()
    {
        if ($this->hasEditPermission()) {
            $deliveryOption = Tools::getValue('delivery_option');
            if ($deliveryOption !== false) {
                $this->context->cart->setDeliveryOption([$this->context->cart->id_address_delivery => $deliveryOption]);
            }
            if (Validate::isBool(($recyclable = Tools::getIntValue('recyclable')))) {
                $this->context->cart->recyclable = $recyclable;
            }
            if (Validate::isBool(($gift = Tools::getIntValue('gift')))) {
                $this->context->cart->gift = $gift;
            }
            if (Validate::isMessage(($giftMessage = pSQL(Tools::getValue('gift_message'))))) {
                $this->context->cart->gift_message = $giftMessage;
            }
            $this->context->cart->save();
            $this->ajaxDie(json_encode($this->ajaxReturnVars()));
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateOrderMessage()
    {
        if ($this->hasEditPermission()) {
            $idMessage = false;
            if ($oldMessage = Message::getMessageByCartId((int) $this->context->cart->id)) {
                $idMessage = $oldMessage['id_message'];
            }
            $message = new Message((int) $idMessage);
            if ($messageContent = Tools::getValue('message')) {
                if (Validate::isMessage($messageContent)) {
                    $message->message = $messageContent;
                    $message->id_cart = (int) $this->context->cart->id;
                    $message->id_customer = (int) $this->context->cart->id_customer;
                    $message->save();
                }
            } elseif (Validate::isLoadedObject($message)) {
                $message->delete();
            }
            $this->ajaxDie(json_encode($this->ajaxReturnVars()));
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateCurrency()
    {
        if ($this->hasEditPermission()) {
            $currency = new Currency(Tools::getIntValue('id_currency'));
            if (Validate::isLoadedObject($currency) && !$currency->deleted && $currency->active) {
                $this->context->cart->id_currency = (int) $currency->id;
                $this->context->currency = $currency;
                $this->context->cart->save();
            }
            $this->ajaxDie(json_encode($this->ajaxReturnVars()));
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateLang()
    {
        if ($this->hasEditPermission()) {
            $lang = new Language(Tools::getIntValue('id_lang'));
            if (Validate::isLoadedObject($lang) && $lang->active) {
                $this->context->cart->id_lang = (int) $lang->id;
                $this->context->cart->save();
            }
            $this->ajaxDie(json_encode($this->ajaxReturnVars()));
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessDuplicateOrder()
    {
        if ($this->hasEditPermission()) {
            if ($idOrder = Tools::getIntValue('id_order')) {
                $cart = Cart::getCartByOrderId($idOrder);
                if (Validate::isLoadedObject($cart)) {
                    $newCart = $cart->duplicate();
                    if (!$newCart || !Validate::isLoadedObject($newCart['cart'])) {
                        $error = Tools::displayError('The order cannot be renewed.');
                    } elseif (!$newCart['success']) {
                        $error = Tools::displayError('The order cannot be renewed.');
                    } else {
                        $this->context->cart = $newCart['cart'];
                        $this->ajaxDie(json_encode($this->ajaxReturnVars()));
                        exit;
                    }
                } else {
                    $error = Tools::displayError('Failed to resolve cart for order');
                }
            } else {
                $error = Tools::displayError('Invalid order');
            }
        } else {
            $error = Tools::displayError('No permissions');
        }
        $this->ajaxDie(json_encode(['error' => $error]));
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessDeleteVoucher()
    {
        if ($this->hasEditPermission()) {
            if ($this->context->cart->removeCartRule(Tools::getIntValue('id_cart_rule'))) {
                $this->ajaxDie(json_encode($this->ajaxReturnVars()));
            }
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessupdateFreeShipping()
    {
        if ($this->hasEditPermission()) {
            if (!$idCartRule = CartRule::getIdByCode(CartRule::BO_ORDER_CODE_PREFIX.(int) $this->context->cart->id)) {
                $cartRule = new CartRule();
                $cartRule->code = CartRule::BO_ORDER_CODE_PREFIX.(int) $this->context->cart->id;
                $cartRule->name = [Configuration::get('PS_LANG_DEFAULT') => $this->l('Free Shipping', 'AdminTab', false, false)];
                $cartRule->id_customer = (int) $this->context->cart->id_customer;
                $cartRule->free_shipping = true;
                $cartRule->quantity = 1;
                $cartRule->quantity_per_user = 1;
                $cartRule->minimum_amount_currency = (int) $this->context->cart->id_currency;
                $cartRule->reduction_currency = (int) $this->context->cart->id_currency;
                $cartRule->date_from = date('Y-m-d H:i:s', time());
                $cartRule->date_to = date('Y-m-d H:i:s', time() + 24 * 36000);
                $cartRule->active = 1;
                $cartRule->add();
            } else {
                $cartRule = new CartRule((int) $idCartRule);
            }

            $this->context->cart->removeCartRule((int) $cartRule->id);
            if (Tools::getValue('free_shipping')) {
                $this->context->cart->addCartRule((int) $cartRule->id);
            }

            $this->ajaxDie(json_encode($this->ajaxReturnVars()));
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessAddVoucher()
    {
        if ($this->hasEditPermission()) {
            $errors = [];
            if (!($idCartRule = Tools::getIntValue('id_cart_rule')) || !$cartRule = new CartRule($idCartRule)) {
                $errors[] = Tools::displayError('Invalid voucher.');
            } elseif ($err = $cartRule->checkValidity($this->context)) {
                $errors[] = $err;
            }
            if (!count($errors) && isset($cartRule)) {
                if (!$this->context->cart->addCartRule((int) $cartRule->id)) {
                    $errors[] = Tools::displayError('Can\'t add the voucher.');
                }
            }
            $this->ajaxDie(json_encode(array_merge($this->ajaxReturnVars(), ['errors' => $errors])));
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateAddress()
    {
        if ($this->hasEditPermission()) {
            $this->ajaxDie(json_encode(['addresses' => $this->context->customer->getAddresses((int) $this->context->cart->id_lang)]));
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateAddresses()
    {
        if ($this->hasEditPermission()) {
            if (($idAddressDelivery = Tools::getIntValue('id_address_delivery')) &&
                ($addressDelivery = new Address((int) $idAddressDelivery)) &&
                $addressDelivery->id_customer == $this->context->cart->id_customer
            ) {
                $this->context->cart->id_address_delivery = (int) $addressDelivery->id;
            }

            if (($idAddressInvoice = Tools::getIntValue('id_address_invoice')) &&
                ($addressInvoice = new Address((int) $idAddressInvoice)) &&
                $addressInvoice->id_customer = $this->context->cart->id_customer
            ) {
                $this->context->cart->id_address_invoice = (int) $addressInvoice->id;
            }
            $this->context->cart->save();

            $this->ajaxDie(json_encode($this->ajaxReturnVars()));
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function displayAjaxSearchCarts()
    {
        $idCustomer = Tools::getIntValue('id_customer');
        $carts = Cart::getCustomerCarts((int) $idCustomer);
        $orders = Order::getCustomerOrders((int) $idCustomer);

        if (count($carts)) {
            foreach ($carts as $key => &$cart) {
                $cartObj = new Cart((int) $cart['id_cart']);
                if ($cart['id_cart'] == $this->context->cart->id || !Validate::isLoadedObject($cartObj) || $cartObj->OrderExists()) {
                    unset($carts[$key]);
                }
                $currency = new Currency((int) $cart['id_currency']);
                $cart['total_price'] = Tools::displayPrice($cartObj->getOrderTotal(), $currency);
            }
        }
        if ($orders) {
            foreach ($orders as &$order) {
                $orderObj = new Order($order['id_order']);
                $order['totalPaid'] = Tools::displayPrice($orderObj->getTotalPaid(), (int)$order['id_currency']);
            }
        }
        if ($orders || $carts) {
            $toReturn = array_merge(
                $this->ajaxReturnVars(),
                [
                    'carts'  => $carts,
                    'orders' => $orders,
                    'found'  => true,
                ]
            );
        } else {
            $toReturn = array_merge($this->ajaxReturnVars(), ['found' => false]);
        }

        $this->ajaxDie(json_encode($toReturn));
    }

    /**
     * @throws PrestaShopException
     */
    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    /**
     * @throws PrestaShopException
     */
    public function displayAjaxGetSummary()
    {
        $this->ajaxDie(json_encode($this->ajaxReturnVars()));
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateProductPrice()
    {
        if ($this->hasEditPermission()) {
            SpecificPrice::deleteByIdCart((int) $this->context->cart->id, Tools::getIntValue('id_product'), Tools::getIntValue('id_product_attribute'));
            $specificPrice = new SpecificPrice();
            $specificPrice->id_cart = (int) $this->context->cart->id;
            $specificPrice->id_shop = 0;
            $specificPrice->id_shop_group = 0;
            $specificPrice->id_currency = 0;
            $specificPrice->id_country = 0;
            $specificPrice->id_group = 0;
            $specificPrice->id_customer = (int) $this->context->customer->id;
            $specificPrice->id_product = Tools::getIntValue('id_product');
            $specificPrice->id_product_attribute = Tools::getIntValue('id_product_attribute');
            $specificPrice->price = Tools::convertPrice(Tools::getValue('price'), (int)$this->context->cart->id_currency, false);
            $specificPrice->from_quantity = 1;
            $specificPrice->reduction = 0;
            $specificPrice->reduction_type = 'amount';
            $specificPrice->from = '0000-00-00 00:00:00';
            $specificPrice->to = '0000-00-00 00:00:00';
            $specificPrice->add();
            $this->ajaxDie(json_encode($this->ajaxReturnVars()));
        }
    }

    /**
     * @param string $token
     * @param int $id
     * @param string|null $name
     *
     * @return string|void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayDeleteLink($token, $id, $name = null)
    {
        // don't display ordered carts
        foreach ($this->_list as $row) {
            if ($row['id_cart'] == $id && isset($row['id_order']) && is_numeric($row['id_order'])) {
                return;
            }
        }

        return $this->helper->displayDeleteLink($token, $id, $name);
    }

    /**
     * @return bool|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        if (!($this->fields_list && is_array($this->fields_list))) {
            return false;
        }
        $this->getList($this->context->language->id);

        $helper = new HelperList();

        $this->setHelperDisplay($helper);
        $helper->tpl_vars = $this->tpl_list_vars;
        $helper->tpl_delete_link_vars = $this->tpl_delete_link_vars;

        // For compatibility reasons, we have to check standard actions in class attributes
        foreach ($this->actions_available as $action) {
            if (!in_array($action, $this->actions) && isset($this->$action) && $this->$action) {
                $this->actions[] = $action;
            }
        }
        $helper->is_cms = $this->is_cms;
        $skipList = [];

        foreach ($this->_list as $row) {
            if ((int)$row['id_order']) {
                $skipList[] = (int)$row['id_cart'];
            }
        }

        if (array_key_exists('delete', $helper->list_skip_actions)) {
            $helper->list_skip_actions['delete'] = array_merge($helper->list_skip_actions['delete'], $skipList);
        } else {
            $helper->list_skip_actions['delete'] = $skipList;
        }

        $list = $helper->generateList($this->_list, $this->fields_list);

        return $list;
    }

    /**
     * Delete empty carts
     *
     * @throws PrestaShopException
     */
    public function processDeleteEmptyCarts()
    {
		$sql = new DbQuery();
		$sql->select('id_cart');
		$sql->from('cart');
		$sql->where('id_cart NOT IN (SELECT id_cart FROM '._DB_PREFIX_.'cart_product)');

		$emptyCarts = Db::getInstance(_PS_USE_SQL_SLAVE_)->getArray($sql);

		$deletedCount = 0;

		foreach ($emptyCarts as $cart) {
			$cartObj = new Cart((int)$cart['id_cart']);
			if (Validate::isLoadedObject($cartObj)) {
				if ($cartObj->delete()) {
					$deletedCount++;
				}
			}
		}

		if ($deletedCount > 0) {
			$this->confirmations[] = sprintf($this->l('Successfully deleted %d empty carts.'), $deletedCount);
		} else {
			$this->errors[] = $this->l('No empty carts were found to delete.');
		}
	}

	/**
	 * Delete carts older than configured FO cookie in PS_COOKIE_LIFETIME_FO
	 *
	 * @throws PrestaShopException
	 */
	protected function processDeleteOldCarts()
	{
		// Get the cookie lifetime setting in hours
		$cookieLifetimeHours = (int) Configuration::get('PS_COOKIE_LIFETIME_FO');

		// Convert cookie lifetime to seconds
		$cookieLifetimeSeconds = $cookieLifetimeHours * 3600;

		// Calculate the cutoff date for old carts
		$cutoffDate = date('Y-m-d H:i:s', time() - $cookieLifetimeSeconds);

		// Retrieve carts older than the cutoff date that are not ordered
		$sql = new DbQuery();
		$sql->select('id_cart');
		$sql->from('cart');
		$sql->where('date_add < "'.pSQL($cutoffDate).'" AND id_cart NOT IN (SELECT id_cart FROM '._DB_PREFIX_.'orders)');

		$oldCarts = Db::getInstance(_PS_USE_SQL_SLAVE_)->getArray($sql);

		$deletedCount = 0;

		foreach ($oldCarts as $cart) {
			$cartObj = new Cart((int)$cart['id_cart']);
			if (Validate::isLoadedObject($cartObj)) {
				if ($cartObj->delete()) {
					$deletedCount++;
				}
			}
		}

		if ($deletedCount > 0) {
			$this->confirmations[] = sprintf($this->l('Successfully deleted %d old carts.'), $deletedCount);
		} else {
			$this->errors[] = $this->l('No old carts were found to delete.');
		}
	}

    /**
     * @return void
     *
     * @throws PrestaShopException
     */
    public function postProcess()
    {
		if (Tools::isSubmit('delete_empty_carts')) {
			$this->processDeleteEmptyCarts();
            $this->redirect_after = Context::getContext()->link->getAdminLink('AdminCarts');
		}

		if (Tools::isSubmit('deleteoldcarts')) {
			$this->processDeleteOldCarts();
            $this->redirect_after = Context::getContext()->link->getAdminLink('AdminCarts');
		}

		parent::postProcess();
	}
}
