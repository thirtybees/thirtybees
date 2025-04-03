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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

class HTMLTemplateDeliverySlipCore extends HTMLTemplate
{
    /**
     * @var Order $order
     */
    public $order;

    /**
     * @var OrderInvoice $order_invoice
     */
    public $order_invoice;

    /**
     * @param OrderInvoice $orderInvoice
     * @param Smarty $smarty
     * @param bool $bulkMode
     *
     * @throws PrestaShopException
     */
    public function __construct(OrderInvoice $orderInvoice, Smarty $smarty, $bulkMode = false)
    {
        $this->order_invoice = $orderInvoice;
        $this->order = new Order($this->order_invoice->id_order);
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
        $prefix = Configuration::get('PS_DELIVERY_PREFIX', Context::getContext()->language->id);
        $this->title = sprintf(static::l('%1$s%2$06d'), $prefix, $this->order_invoice->delivery_number);

        // footer informations
        $this->shop = new Shop((int) $this->order->id_shop);
    }

    /**
     * Returns the template's HTML header
     *
     * @return string HTML header
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getHeader()
    {
        $this->assignCommonHeaderData();
        $this->smarty->assign(['header' => static::l('Delivery')]);

        return $this->smarty->fetch($this->getTemplate('header'));
    }

    /**
     * Returns the template's HTML content
     *
     * @return string HTML content
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
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

        $carrier = new Carrier($this->order->id_carrier);

        $orderDetails = $this->order_invoice->getProducts();
        foreach ($orderDetails as &$orderDetail) {
            if (OrderDetailPack::isPack((int) $orderDetail['id_order_detail'])) {
                $packItems = OrderDetailPack::getItems((int) $orderDetail['id_order_detail'], Context::getContext()->language->id);
                $namePackItems = '';
                foreach ($packItems as $packItem) {
                    $namePackItems .= $packItem->pack_quantity.' x <b>'.$packItem->reference.'</b> '.$packItem->name.', ';
                }
                $orderDetail['pack_items'] = $namePackItems;
            }
        }
        if (Configuration::get('PS_PDF_IMG_DELIVERY')) {
            foreach ($orderDetails as &$orderDetail) {
                if ($orderDetail['image'] instanceof Image) {
                    $imageId = (int)$orderDetail['image']->id;
                    $orderDetail['image_tag'] = preg_replace(
                        '/\.*'.preg_quote(__PS_BASE_URI__, '/').'/',
                        _PS_ROOT_DIR_.DIRECTORY_SEPARATOR,
                        ImageManager::getProductImageThumbnailTag($imageId, false),
                        1
                    );

                    $imagePath = ImageManager::getProductImageThumbnailFilePath($imageId);
                    if (file_exists($imagePath)) {
                        $orderDetail['image_size'] = getimagesize($imagePath);
                    } else {
                        $orderDetail['image_size'] = false;
                    }
                }
            }
            unset($orderDetail); // don't overwrite the last order_detail later
        }

        $this->smarty->assign(
            [
                'order'                  => $this->order,
                'order_details'          => $orderDetails,
                'delivery_address'       => $formattedDeliveryAddress,
                'invoice_address'        => $formattedInvoiceAddress,
                'order_invoice'          => $this->order_invoice,
                'carrier'                => $carrier,
                'display_product_images' => Configuration::get('PS_PDF_IMG_DELIVERY'),
            ]
        );

        $tpls = [
            'style_tab'     => $this->smarty->fetch($this->getTemplate('delivery-slip.style-tab')),
            'addresses_tab' => $this->smarty->fetch($this->getTemplate('delivery-slip.addresses-tab')),
            'summary_tab'   => $this->smarty->fetch($this->getTemplate('delivery-slip.summary-tab')),
            'product_tab'   => $this->smarty->fetch($this->getTemplate('delivery-slip.product-tab')),
            'payment_tab'   => $this->smarty->fetch($this->getTemplate('delivery-slip.payment-tab')),
        ];
        $this->smarty->assign($tpls);

        return $this->smarty->fetch($this->getTemplate('delivery-slip'));
    }

    /**
     * Returns the template filename when using bulk rendering
     *
     * @return string filename
     */
    public function getBulkFilename()
    {
        return 'deliveries.pdf';
    }

    /**
     * Returns the template filename
     *
     * @return string filename
     *
     * @throws PrestaShopException
     */
    public function getFilename()
    {
        return Configuration::get('PS_DELIVERY_PREFIX', Context::getContext()->language->id, null, $this->order->id_shop).sprintf('%06d', $this->order->delivery_number).'.pdf';
    }
}
