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
 * Class HTMLTemplateOrderSlipCore
 *
 * @since   1.0.0
 */
class HTMLTemplateOrderSlipCore extends HTMLTemplateInvoice
{
    // @codingStandardsIgnoreStart
    /** @var Order $order */
    public $order;
    /** @var OrderSlipCore $order_slip */
    public $order_slip;
    // @codingStandardsIgnoreEnd

    /**
     * @param OrderSlip $orderSlip
     * @param Smarty    $smarty
     *
     * @throws PrestaShopException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct(OrderSlipCore $orderSlip, Smarty $smarty)
    {
        $this->order_slip = $orderSlip;
        $this->order = new Order((int) $orderSlip->id_order);

        $products = OrderSlip::getOrdersSlipProducts($this->order_slip->id, $this->order);
        $customizedDatas = Product::getAllCustomizedDatas((int) $this->order->id_cart);
        Product::addCustomizationPrice($products, $customizedDatas);

        $this->order->products = $products;
        $this->smarty = $smarty;

        // header informations
        $this->date = Tools::displayDate($this->order_slip->date_add);
        $prefix = Configuration::get('PS_CREDIT_SLIP_PREFIX', Context::getContext()->language->id);
        $this->title = sprintf(static::l('%1$s%2$06d'), $prefix, (int) $this->order_slip->id);

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
        $this->smarty->assign(
            [
                'header' => static::l('Credit slip'),
            ]
        );

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
        $deliveryAddress = $invoiceAddress = new Address((int) $this->order->id_address_invoice);
        $formattedInvoiceAddress = AddressFormat::generateAddress($invoiceAddress, [], '<br />', ' ');
        $formattedDeliveryAddress = '';

        if ($this->order->id_address_delivery != $this->order->id_address_invoice) {
            $deliveryAddress = new Address((int) $this->order->id_address_delivery);
            $formattedDeliveryAddress = AddressFormat::generateAddress($deliveryAddress, [], '<br />', ' ');
        }

        $customer = new Customer((int) $this->order->id_customer);
        $taxExcludedDisplay = Group::getPriceDisplayMethod((int) $customer->id_default_group);

        $totalCartRule = 0;
        if ($this->order_slip->order_slip_type == 1 && is_array($cartRules = $this->order->getCartRules())) {
            foreach ($cartRules as $cartRule) {
                if ($taxExcludedDisplay) {
                    $totalCartRule += $cartRule['value_tax_excl'];
                } else {
                    $totalCartRule += $cartRule['value'];
                }
            }
        }

        $this->smarty->assign(
            [
                'order'                => $this->order,
                'order_slip'           => $this->order_slip,
                'order_details'        => $this->order->products,
                'cart_rules'           => $this->order_slip->order_slip_type == 1 ? $this->order->getCartRules() : false,
                'amount_choosen'       => $this->order_slip->order_slip_type == 2 ? true : false,
                'delivery_address'     => $formattedDeliveryAddress,
                'invoice_address'      => $formattedInvoiceAddress,
                'addresses'            => ['invoice' => $invoiceAddress, 'delivery' => $deliveryAddress],
                'tax_excluded_display' => $taxExcludedDisplay,
                'total_cart_rule'      => $totalCartRule,
            ]
        );

        $tpls = [
            'style_tab'     => $this->smarty->fetch($this->getTemplate('invoice.style-tab')),
            'addresses_tab' => $this->smarty->fetch($this->getTemplate('invoice.addresses-tab')),
            'summary_tab'   => $this->smarty->fetch($this->getTemplate('order-slip.summary-tab')),
            'product_tab'   => $this->smarty->fetch($this->getTemplate('order-slip.product-tab')),
            'total_tab'     => $this->smarty->fetch($this->getTemplate('order-slip.total-tab')),
            'payment_tab'   => $this->smarty->fetch($this->getTemplate('order-slip.payment-tab')),
            'tax_tab'       => $this->getTaxTabContent(),
        ];
        $this->smarty->assign($tpls);

        return $this->smarty->fetch($this->getTemplate('order-slip'));
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
        return 'order-slips.pdf';
    }

    /**
     * Returns the template filename
     *
     * @return string filename
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFilename()
    {
        return 'order-slip-'.sprintf('%06d', $this->order_slip->id).'.pdf';
    }

    /**
     * Returns the tax tab content
     *
     * @return String Tax tab html content
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

        $this->smarty->assign(
            [
                'tax_exempt'                      => $taxExempt,
                'product_tax_breakdown'           => $this->getProductTaxesBreakdown(),
                'shipping_tax_breakdown'          => $this->getShippingTaxesBreakdown(),
                'order'                           => $this->order,
                'ecotax_tax_breakdown'            => $this->order_slip->getEcoTaxTaxesBreakdown(),
                'is_order_slip'                   => true,
                'tax_breakdowns'                  => $this->getTaxBreakdown(),
                'display_tax_bases_in_breakdowns' => false,
            ]
        );

        return $this->smarty->fetch($this->getTemplate('invoice.tax-tab'));
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductTaxesBreakdown()
    {
        // $breakdown will be an array with tax rates as keys and at least the columns:
        // 	- 'total_price_tax_excl'
        // 	- 'total_amount'
        $breakdown = [];

        $details = $this->order->getProductTaxesDetails($this->order->products);

        foreach ($details as $row) {
            $rate = sprintf('%.3f', $row['tax_rate']);
            if (!isset($breakdown[$rate])) {
                $breakdown[$rate] = [
                    'total_price_tax_excl' => 0,
                    'total_amount'         => 0,
                    'id_tax'               => $row['id_tax'],
                    'rate'                 => $rate,
                ];
            }

            $breakdown[$rate]['total_price_tax_excl'] += $row['total_tax_base'];
            $breakdown[$rate]['total_amount'] += $row['total_amount'];
        }

        $decimals = 0;
        if ((new Currency($this->order->id_currency))->decimals) {
            $decimals = Configuration::get('PS_PRICE_DISPLAY_PRECISION');
        }
        foreach ($breakdown as $rate => $data) {
            $breakdown[$rate]['total_price_tax_excl'] = Tools::ps_round(
                $data['total_price_tax_excl'],
                $decimals,
                $this->order->round_mode
            );
            $breakdown[$rate]['total_amount'] = Tools::ps_round(
                $data['total_amount'],
                $decimals,
                $this->order->round_mode
            );
        }

        ksort($breakdown);

        return $breakdown;
    }

    /**
     * Returns Shipping tax breakdown elements
     *
     * @return array Shipping tax breakdown elements
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getShippingTaxesBreakdown()
    {
        $taxesBreakdown = [];
        $tax = new Tax();
        $tax->rate = $this->order->carrier_tax_rate;
        $taxCalculator = new TaxCalculator([$tax]);
        $customer = new Customer((int) $this->order->id_customer);
        $taxExcludedDisplay = Group::getPriceDisplayMethod((int) $customer->id_default_group);

        if ($taxExcludedDisplay) {
            $totalTaxExcl = $this->order_slip->shipping_cost_amount;
            $shippingTaxAmount = $taxCalculator->addTaxes($this->order_slip->shipping_cost_amount) - $totalTaxExcl;
        } else {
            $totalTaxExcl = $taxCalculator->removeTaxes($this->order_slip->shipping_cost_amount);
            $shippingTaxAmount = $this->order_slip->shipping_cost_amount - $totalTaxExcl;
        }

        if ($shippingTaxAmount > 0) {
            $taxesBreakdown[] = [
                'rate'           => $this->order->carrier_tax_rate,
                'total_amount'   => $shippingTaxAmount,
                'total_tax_excl' => $totalTaxExcl,
            ];
        }

        return $taxesBreakdown;
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
            'product_tax'  => $this->getProductTaxesBreakdown(),
            'shipping_tax' => $this->getShippingTaxesBreakdown(),
            'ecotax_tax'   => $this->order_slip->getEcoTaxTaxesBreakdown(),
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
