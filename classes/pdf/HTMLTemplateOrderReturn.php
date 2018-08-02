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
 * Class HTMLTemplateOrderReturnCore
 *
 * @since 1.0.0
 */
class HTMLTemplateOrderReturnCore extends HTMLTemplate
{
    // @codingStandardsIgnoreStart
    /** @var OrderReturn $order_return */
    public $order_return;
    /** @var Order $order */
    public $order;
    // @codingStandardsIgnoreEnd

    /**
     * @param OrderReturnCore $orderReturn
     * @param Smarty          $smarty
     *
     * @throws PrestaShopException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct(OrderReturnCore $orderReturn, Smarty $smarty)
    {
        $this->order_return = $orderReturn;
        $this->smarty = $smarty;
        $this->order = new Order($orderReturn->id_order);

        // header informations
        $this->date = Tools::displayDate($this->order->invoice_date);
        $prefix = Configuration::get('PS_RETURN_PREFIX', Context::getContext()->language->id);
        $this->title = sprintf(HTMLTemplateOrderReturn::l('%1$s%2$06d'), $prefix, $this->order_return->id);

        $this->shop = new Shop((int) $this->order->id_shop);
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
        $deliveryAddress = new Address((int) $this->order->id_address_delivery);
        $formattedDeliveryAddress = AddressFormat::generateAddress($deliveryAddress, [], '<br />', ' ');
        $formattedInvoiceAddress = '';

        if ($this->order->id_address_delivery != $this->order->id_address_invoice) {
            $invoiceAddress = new Address((int) $this->order->id_address_invoice);
            $formattedInvoiceAddress = AddressFormat::generateAddress($invoiceAddress, [], '<br />', ' ');
        }

        $this->smarty->assign(
            [
                'order_return'     => $this->order_return,
                'return_nb_days'   => (int) Configuration::get('PS_ORDER_RETURN_NB_DAYS'),
                'products'         => OrderReturn::getOrdersReturnProducts((int) $this->order_return->id, $this->order),
                'delivery_address' => $formattedDeliveryAddress,
                'invoice_address'  => $formattedInvoiceAddress,
                'shop_address'     => AddressFormat::generateAddress($this->shop->getAddress(), [], '<br />', ' '),
            ]
        );

        $tpls = [
            'style_tab'      => $this->smarty->fetch($this->getTemplate('invoice.style-tab')),
            'addresses_tab'  => $this->smarty->fetch($this->getTemplate('order-return.addresses-tab')),
            'summary_tab'    => $this->smarty->fetch($this->getTemplate('order-return.summary-tab')),
            'product_tab'    => $this->smarty->fetch($this->getTemplate('order-return.product-tab')),
            'conditions_tab' => $this->smarty->fetch($this->getTemplate('order-return.conditions-tab')),
        ];
        $this->smarty->assign($tpls);

        return $this->smarty->fetch($this->getTemplate('order-return'));
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
        return Configuration::get('PS_RETURN_PREFIX', Context::getContext()->language->id).sprintf('%06d', $this->order_return->id).'.pdf';
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
            'header' => HTMLTemplateOrderReturn::l('Order return'),
            ]
        );

        return $this->smarty->fetch($this->getTemplate('header'));
    }
}
