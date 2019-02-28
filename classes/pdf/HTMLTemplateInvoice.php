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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * @since 1.5
 */
class HTMLTemplateInvoiceCore extends HTMLTemplate
{
    // @codingStandardsIgnoreStart
    /** @var Order $order */
    public $order;
    /** @var OrderInvoice $order_invoice */
    public $order_invoice;
    /** @var bool $available_in_your_account */
    public $available_in_your_account = false;
    // @codingStandardsIgnoreEnd

    /**
     * @param OrderInvoice $orderInvoice
     * @param Smarty       $smarty
     * @param bool         $bulkMode
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function __construct(OrderInvoiceCore $orderInvoice, Smarty $smarty, $bulkMode = false)
    {
        $this->order_invoice = $orderInvoice;
        $this->order = new Order((int) $this->order_invoice->id_order);
        $this->smarty = $smarty;

        // If shop_address is null, then update it with current one.
        // But no DB save required here to avoid massive updates for bulk PDF generation case.
        // (DB: bug fixed in 1.6.1.1 with upgrade SQL script to avoid null shop_address in old orderInvoices)
        if (!isset($this->order_invoice->shop_address) || !$this->order_invoice->shop_address) {
            $this->order_invoice->shop_address = OrderInvoice::getCurrentFormattedShopAddress((int) $this->order->id_shop);
            if (!$bulkMode) {
                OrderInvoice::fixAllShopAddresses();
            }
        }

        // header informations
        $this->date = Tools::displayDate($orderInvoice->date_add);

        $idLang = Context::getContext()->language->id;
        $this->title = $orderInvoice->getInvoiceNumberFormatted($idLang, (int) $this->order->id_shop);

        $this->shop = new Shop((int) $this->order->id_shop);
    }

    /**
     * Returns the template's HTML header
     *
     * @return string HTML header
     *
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getHeader()
    {
        $this->assignCommonHeaderData();
        $this->smarty->assign(['header' => static::l('Invoice')]);

        return $this->smarty->fetch($this->getTemplate('header'));
    }

    /**
     * Returns the template's HTML content
     *
     * @return string HTML content
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getContent()
    {
        $invoiceAddressPatternRules = json_decode(Configuration::get('PS_INVCE_INVOICE_ADDR_RULES'), true);
        $deliveryAddressPatternRules = json_decode(Configuration::get('PS_INVCE_DELIVERY_ADDR_RULES'), true);

        $invoiceAddress = new Address((int) $this->order->id_address_invoice);
        $country = new Country((int) $invoiceAddress->id_country);
        $formattedInvoiceAddress = AddressFormat::generateAddress($invoiceAddress, $invoiceAddressPatternRules, '<br />', ' ');

        $deliveryAddress = null;
        $formattedDeliveryAddress = '';
        if (isset($this->order->id_address_delivery) && $this->order->id_address_delivery) {
            $deliveryAddress = new Address((int) $this->order->id_address_delivery);
            $formattedDeliveryAddress = AddressFormat::generateAddress($deliveryAddress, $deliveryAddressPatternRules, '<br />', ' ');
        }

        $customer = new Customer((int) $this->order->id_customer);
        $carrier = new Carrier((int) $this->order->id_carrier);

        $orderDetails = $this->order_invoice->getProducts();
        $order = new Order($this->order_invoice->id_order);

        $hasDiscount = false;
        foreach ($orderDetails as $id => &$orderDetail) {
            // Find out if column 'price before discount' is required
            if ($orderDetail['reduction_amount_tax_excl'] > 0) {
                $hasDiscount = true;
                $orderDetail['unit_price_tax_excl_before_specific_price'] = $orderDetail['unit_price_tax_excl_including_ecotax'] + $orderDetail['reduction_amount_tax_excl'];
            } elseif ($orderDetail['reduction_percent'] > 0) {
                $hasDiscount = true;
                $orderDetail['unit_price_tax_excl_before_specific_price'] = (100 * $orderDetail['unit_price_tax_excl_including_ecotax']) / (100 - $orderDetail['reduction_percent']);
            }

            // Set tax_code
            $taxes = OrderDetail::getTaxListStatic($id);
            $taxTemp = [];
            foreach ($taxes as $tax) {
                $obj = new Tax($tax['id_tax']);
                $taxTemp[] = sprintf($this->l('%1$s%2$s%%'), (float) round($obj->rate, 3), '&nbsp;');
            }

            $orderDetail['order_detail_tax'] = $taxes;
            $orderDetail['order_detail_tax_label'] = implode(', ', $taxTemp);
        }
        unset($taxTemp);
        unset($orderDetail);

        if (Configuration::get('PS_PDF_IMG_INVOICE')) {
            foreach ($orderDetails as &$orderDetail) {
                if ($orderDetail['image'] instanceof Image) {
                    $name = 'product_mini_'.(int) $orderDetail['product_id'].(isset($orderDetail['product_attribute_id']) ? '_'.(int) $orderDetail['product_attribute_id'] : '').'.jpg';
                    $path = _PS_PROD_IMG_DIR_.$orderDetail['image']->getExistingImgPath().'.jpg';

                    $orderDetail['image_tag'] = preg_replace(
                        '/\.*'.preg_quote(__PS_BASE_URI__, '/').'/',
                        _PS_ROOT_DIR_.DIRECTORY_SEPARATOR,
                        ImageManager::thumbnail($path, $name, 45, 'jpg', false),
                        1
                    );

                    if (file_exists(_PS_TMP_IMG_DIR_.$name)) {
                        $orderDetail['image_size'] = getimagesize(_PS_TMP_IMG_DIR_.$name);
                    } else {
                        $orderDetail['image_size'] = false;
                    }
                }
            }
            unset($orderDetail); // don't overwrite the last order_detail later
        }

        $cartRules = $this->order->getCartRules();
        $freeShipping = false;
        foreach ($cartRules as $key => $cartRule) {
            if ($cartRule['free_shipping']) {
                $freeShipping = true;
                /**
                 * Adjust cart rule value to remove the amount of the shipping.
                 * We're not interested in displaying the shipping discount as it is already shown as "Free Shipping".
                 */
                $cartRules[$key]['value_tax_excl'] -= $this->order_invoice->total_shipping_tax_excl;
                $cartRules[$key]['value'] -= $this->order_invoice->total_shipping_tax_incl;

                /**
                 * Don't display cart rules that are only about free shipping and don't create
                 * a discount on products.
                 */
                if ($cartRules[$key]['value'] == 0) {
                    unset($cartRules[$key]);
                }
            }
        }

        $productTaxes = 0;
        foreach ($this->order_invoice->getProductTaxesBreakdown($this->order) as $details) {
            $productTaxes += $details['total_amount'];
        }

        $productDiscountsTaxExcl = $this->order_invoice->total_discount_tax_excl;
        $productDiscountsTaxIncl = $this->order_invoice->total_discount_tax_incl;
        if ($freeShipping) {
            $productDiscountsTaxExcl -= $this->order_invoice->total_shipping_tax_excl;
            $productDiscountsTaxIncl -= $this->order_invoice->total_shipping_tax_incl;
        }

        $productsAfterDiscountsTaxExcl = $this->order_invoice->total_products - $productDiscountsTaxExcl;
        $productsAfterDiscountsTaxIncl = $this->order_invoice->total_products_wt - $productDiscountsTaxIncl;

        $shippingTaxExcl = $freeShipping ? 0 : $this->order_invoice->total_shipping_tax_excl;
        $shippingTaxIncl = $freeShipping ? 0 : $this->order_invoice->total_shipping_tax_incl;
        $shippingTaxes = $shippingTaxIncl - $shippingTaxExcl;

        $wrappingTaxes = $this->order_invoice->total_wrapping_tax_incl - $this->order_invoice->total_wrapping_tax_excl;

        $totalTaxes = $this->order_invoice->total_paid_tax_incl - $this->order_invoice->total_paid_tax_excl;

        $footer = [
            'products_before_discounts_tax_excl' => $this->order_invoice->total_products,
            'product_discounts_tax_excl'         => $productDiscountsTaxExcl,
            'products_after_discounts_tax_excl'  => $productsAfterDiscountsTaxExcl,
            'products_before_discounts_tax_incl' => $this->order_invoice->total_products_wt,
            'product_discounts_tax_incl'         => $productDiscountsTaxIncl,
            'products_after_discounts_tax_incl'  => $productsAfterDiscountsTaxIncl,
            'product_taxes'                      => $productTaxes,
            'shipping_tax_excl'                  => $shippingTaxExcl,
            'shipping_taxes'                     => $shippingTaxes,
            'shipping_tax_incl'                  => $shippingTaxIncl,
            'wrapping_tax_excl'                  => $this->order_invoice->total_wrapping_tax_excl,
            'wrapping_taxes'                     => $wrappingTaxes,
            'wrapping_tax_incl'                  => $this->order_invoice->total_wrapping_tax_incl,
            'ecotax_taxes'                       => $totalTaxes - $productTaxes - $wrappingTaxes - $shippingTaxes,
            'total_taxes'                        => $totalTaxes,
            'total_paid_tax_excl'                => $this->order_invoice->total_paid_tax_excl,
            'total_paid_tax_incl'                => $this->order_invoice->total_paid_tax_incl,
        ];

        $decimals = 0;
        if ((new Currency($this->order->id_currency))->decimals) {
            $decimals = Configuration::get('PS_PRICE_DISPLAY_PRECISION');
        }
        foreach ($footer as $key => $value) {
            $footer[$key] = Tools::ps_round(
                $value,
                $decimals,
                $this->order->round_mode
            );
        }

        $displayProductImages = Configuration::get('PS_PDF_IMG_INVOICE');
        $taxExcludedDisplay = Group::getPriceDisplayMethod($customer->id_default_group);

        $layout = $this->computeLayout(['has_discount' => $hasDiscount]);

        $legalFreeText = Hook::exec('displayInvoiceLegalFreeText', ['order' => $this->order]);
        if (!$legalFreeText) {
            $legalFreeText = Configuration::get('PS_INVOICE_LEGAL_FREE_TEXT', (int) Context::getContext()->language->id, null, (int) $this->order->id_shop);
        }

        $data = [
            'order'                      => $this->order,
            'order_invoice'              => $this->order_invoice,
            'order_details'              => $orderDetails,
            'carrier'                    => $carrier,
            'cart_rules'                 => $cartRules,
            'delivery_address'           => $formattedDeliveryAddress,
            'invoice_address'            => $formattedInvoiceAddress,
            'addresses'                  => ['invoice' => $invoiceAddress, 'delivery' => $deliveryAddress],
            'tax_excluded_display'       => $taxExcludedDisplay,
            'display_product_images'     => $displayProductImages,
            'layout'                     => $layout,
            'tax_tab'                    => $this->getTaxTabContent(),
            'customer'                   => $customer,
            'footer'                     => $footer,
            'legal_free_text'            => $legalFreeText,
        ];
        $this->smarty->assign($data);

        $tpls = [
            'style_tab'     => $this->smarty->fetch($this->getTemplate('invoice.style-tab')),
            'addresses_tab' => $this->smarty->fetch($this->getTemplate('invoice.addresses-tab')),
            'summary_tab'   => $this->smarty->fetch($this->getTemplate('invoice.summary-tab')),
            'product_tab'   => $this->smarty->fetch($this->getTemplate('invoice.product-tab')),
            'tax_tab'       => $this->getTaxTabContent(),
            'payment_tab'   => $this->smarty->fetch($this->getTemplate('invoice.payment-tab')),
            'note_tab'      => $this->smarty->fetch($this->getTemplate('invoice.note-tab')),
            'total_tab'     => $this->smarty->fetch($this->getTemplate('invoice.total-tab')),
            'shipping_tab'  => $this->smarty->fetch($this->getTemplate('invoice.shipping-tab')),
        ];
        $this->smarty->assign($tpls);

        return $this->smarty->fetch($this->getTemplateByCountry($country->iso_code));
    }

    /**
     * Returns the tax tab content
     *
     * @return string|string[] Tax tab html content
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTaxTabContent()
    {
        $address = new Address((int) $this->order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});

        $taxExempt = false;
        // @TODO: Use a hook for this. Like:
        //        Hook::exec('isVatExemption', ['address' => &$address]);
        if (Module::isEnabled('vatnumber')) {
            require_once _PS_MODULE_DIR_.'/vatnumber/VATNumberTaxManager.php';

            $taxExempt = VATNumberTaxManager::isAvailableForThisAddress($address);
        }

        $taxBreakdowns = $this->getTaxBreakdown();
        $shippingTaxBreakdowns = $this->order_invoice->getShippingTaxesBreakdown($this->order);
        $ecoTaxBreakdowns = $this->order_invoice->getEcoTaxTaxesBreakdown();
        $wrappingTaxBreakdowns = $this->order_invoice->getWrappingTaxesBreakdown();
        foreach (array_merge($shippingTaxBreakdowns, $ecoTaxBreakdowns, $wrappingTaxBreakdowns) as &$breakdown) {
            $breakdown['rate'] = (float) round($breakdown['rate'], 3);
        }

        $data = [
            'tax_exempt'                      => $taxExempt,
            'use_one_after_another_method'    => $this->order_invoice->useOneAfterAnotherTaxComputationMethod(),
            'display_tax_bases_in_breakdowns' => $this->order_invoice->displayTaxBasesInProductTaxesBreakdown(),
            'product_tax_breakdown'           => $this->order_invoice->getProductTaxesBreakdown($this->order),
            'shipping_tax_breakdown'          => $shippingTaxBreakdowns,
            'ecotax_tax_breakdown'            => $ecoTaxBreakdowns,
            'wrapping_tax_breakdown'          => $wrappingTaxBreakdowns,
            'tax_breakdowns'                  => $taxBreakdowns,
        ];
        $this->smarty->assign($data);

        return $this->smarty->fetch($this->getTemplate('invoice.tax-tab'));
    }

    /**
     * Returns the template filename when using bulk rendering
     *
     * @return string filename
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getBulkFilename()
    {
        return 'invoices.pdf';
    }

    /**
     * Returns the template filename
     *
     * @return string filename
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getFilename()
    {
        $idLang = Context::getContext()->language->id;
        $idShop = (int) $this->order->id_shop;
        $format = '%1$s%2$06d';

        if (Configuration::get('PS_INVOICE_USE_YEAR')) {
            $format = Configuration::get('PS_INVOICE_YEAR_POS') ? '%1$s%3$s-%2$06d' : '%1$s%2$06d-%3$s';
        }

        return sprintf(
            $format,
            Configuration::get('PS_INVOICE_PREFIX', $idLang, null, $idShop),
            $this->order_invoice->number,
            date('Y', strtotime($this->order_invoice->date_add))
        ).'.pdf';
    }

    /**
     * Compute layout elements size
     *
     * @param $params array Layout elements
     *
     * @return array Layout elements columns size
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function computeLayout($params)
    {
        $layout = [
            'reference'           => ['width' => 15],
            'product'             => ['width' => 40],
            'quantity'            => ['width' => 8],
            'tax_code'            => ['width' => 12],
            'unit_price_tax_excl' => ['width' => 0],
            'total_tax_excl'      => ['width' => 0],
        ];

        if (isset($params['has_discount']) && $params['has_discount']) {
            $layout['before_discount'] = ['width' => 0];
            $layout['product']['width'] -= 7;
            $layout['reference']['width'] -= 3;
        }

        $totalWidth = 0;
        $freeColumnsCount = 0;
        foreach ($layout as $data) {
            if ($data['width'] === 0) {
                ++$freeColumnsCount;
            }

            $totalWidth += $data['width'];
        }

        $delta = 100 - $totalWidth;

        foreach ($layout as $row => $data) {
            if ($data['width'] === 0) {
                $layout[$row]['width'] = $delta / $freeColumnsCount;
            }
        }

        $layout['_colCount'] = count($layout);

        return $layout;
    }

    /**
     * Returns the invoice template associated to the country iso_code
     *
     * @param string $isoCountry
     *
     * @return string
     * @throws PrestaShopException
     */
    protected function getTemplateByCountry($isoCountry)
    {
        $file = Configuration::get('PS_INVOICE_MODEL');

        // try to fetch the iso template
        $template = $this->getTemplate($file.'.'.$isoCountry);

        // else use the default one
        if (!$template) {
            $template = $this->getTemplate($file);
        }

        return $template;
    }

    /**
     * Returns different tax breakdown elements
     *
     * @return array Different tax breakdown elements
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getTaxBreakdown()
    {
        $breakdowns = [
            'product_tax'  => $this->order_invoice->getProductTaxesBreakdown($this->order),
            'shipping_tax' => $this->order_invoice->getShippingTaxesBreakdown($this->order),
            'ecotax_tax'   => $this->order_invoice->getEcoTaxTaxesBreakdown(),
            'wrapping_tax' => $this->order_invoice->getWrappingTaxesBreakdown(),
        ];

        foreach ($breakdowns as $type => $bd) {
            if (empty($bd)) {
                unset($breakdowns[$type]);
            }
        }

        if (empty($breakdowns)) {
            $breakdowns = false;
        }

        if (isset($breakdowns['product_tax'])) {
            foreach ($breakdowns['product_tax'] as &$bd) {
                $bd['total_tax_excl'] = $bd['total_price_tax_excl'];
            }
        }

        if (isset($breakdowns['ecotax_tax'])) {
            foreach ($breakdowns['ecotax_tax'] as &$bd) {
                $bd['total_tax_excl'] = $bd['ecotax_tax_excl'];
                $bd['total_amount'] = $bd['ecotax_tax_incl'] - $bd['ecotax_tax_excl'];
            }
        }

        return $breakdowns;
    }
}
