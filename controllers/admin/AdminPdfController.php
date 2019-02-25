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
 * Class AdminPdfControllerCore
 *
 * @since 1.0.0
 */
class AdminPdfControllerCore extends AdminController
{
    /**
     * Post processing
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        parent::postProcess();

        // We want to be sure that displaying PDF is the last thing this controller will do
        exit;
    }

    /**
     * Initialize processing
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initProcess()
    {
        parent::initProcess();
        $this->checkCacheFolder();
        $access = Profile::getProfileAccess($this->context->employee->id_profile, (int) Tab::getIdFromClassName('AdminOrders'));
        if ($access['view'] === '1' && ($action = Tools::getValue('submitAction'))) {
            $this->action = $action;
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to view this.');
        }
    }

    /**
     * Check cache folder
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function checkCacheFolder()
    {
        if (!is_dir(_PS_CACHE_DIR_.'tcpdf/')) {
            mkdir(_PS_CACHE_DIR_.'tcpdf/');
        }
    }

    /**
     * Process generate invoice PDF
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function processGenerateInvoicePdf()
    {
        if (Tools::isSubmit('id_order')) {
            $this->generateInvoicePDFByIdOrder(Tools::getValue('id_order'));
        } elseif (Tools::isSubmit('id_order_invoice')) {
            $this->generateInvoicePDFByIdOrderInvoice(Tools::getValue('id_order_invoice'));
        } else {
            die(Tools::displayError('The order ID -- or the invoice order ID -- is missing.'));
        }
    }

    /**
     * Generate PDF invoice by Order ID
     *
     * @param int $idOrder
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function generateInvoicePDFByIdOrder($idOrder)
    {
        $order = new Order((int) $idOrder);
        if (!Validate::isLoadedObject($order)) {
            die(Tools::displayError('The order cannot be found within your database.'));
        }

        $orderInvoiceList = $order->getInvoicesCollection();
        Hook::exec('actionPDFInvoiceRender', ['order_invoice_list' => $orderInvoiceList]);
        $this->generatePDF($orderInvoiceList, PDF::TEMPLATE_INVOICE);
    }

    /**
     * Generate PDF
     *
     * @param $object
     * @param $template
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function generatePDF($object, $template)
    {
        $pdf = new PDF($object, $template, $this->context->smarty);
        $pdf->render();
    }

    /**
     * Generate PDF Invoice by OrderInvoice ID
     *
     * @param int $idOrderInvoice
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function generateInvoicePDFByIdOrderInvoice($idOrderInvoice)
    {
        $orderInvoice = new OrderInvoice((int) $idOrderInvoice);
        if (!Validate::isLoadedObject($orderInvoice)) {
            die(Tools::displayError('The order invoice cannot be found within your database.'));
        }

        Hook::exec('actionPDFInvoiceRender', ['order_invoice_list' => [$orderInvoice]]);
        $this->generatePDF($orderInvoice, PDF::TEMPLATE_INVOICE);
    }

    /**
     * Generate Order Slip PDF
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function processGenerateOrderSlipPDF()
    {
        $orderSlip = new OrderSlip((int) Tools::getValue('id_order_slip'));

        if ( ! Validate::isLoadedObject($orderSlip)) {
            die(Tools::displayError('The order slip cannot be found within your database.'));
        }

        $this->generatePDF($orderSlip, PDF::TEMPLATE_ORDER_SLIP);
    }

    /**
     * Process generate Delivery Slip PDF
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function processGenerateDeliverySlipPDF()
    {
        if (Tools::isSubmit('id_order')) {
            $this->generateDeliverySlipPDFByIdOrder((int) Tools::getValue('id_order'));
        } elseif (Tools::isSubmit('id_order_invoice')) {
            $this->generateDeliverySlipPDFByIdOrderInvoice((int) Tools::getValue('id_order_invoice'));
        } elseif (Tools::isSubmit('id_delivery')) {
            $order = Order::getByDelivery((int) Tools::getValue('id_delivery'));
            $this->generateDeliverySlipPDFByIdOrder((int) $order->id);
        } else {
            die(Tools::displayError('The order ID -- or the invoice order ID -- is missing.'));
        }
    }

    /**
     * Generate Delivery Slip PDF by Order ID
     *
     * @param int $idOrder
     *
     * @throws PrestaShopException
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function generateDeliverySlipPDFByIdOrder($idOrder)
    {
        $order = new Order((int) $idOrder);
        if (!Validate::isLoadedObject($order)) {
            throw new PrestaShopException('Can\'t load Order object');
        }

        $orderInvoiceCollection = $order->getInvoicesCollection();
        $this->generatePDF($orderInvoiceCollection, PDF::TEMPLATE_DELIVERY_SLIP);
    }

    /**
     * Generate Delivery Slip PDF by OrderInvoice ID
     *
     * @param int $idOrderInvoice
     *
     * @throws PrestaShopException
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function generateDeliverySlipPDFByIdOrderInvoice($idOrderInvoice)
    {
        $orderInvoice = new OrderInvoice((int) $idOrderInvoice);
        if (!Validate::isLoadedObject($orderInvoice)) {
            throw new PrestaShopException('Can\'t load Order Invoice object');
        }

        $this->generatePDF($orderInvoice, PDF::TEMPLATE_DELIVERY_SLIP);
    }

    /**
     * Generate PDF invoices
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function processGenerateInvoicesPDF()
    {
        $orderInvoiceCollection = OrderInvoice::getByDateInterval(Tools::getValue('date_from'), Tools::getValue('date_to'));

        if (!count($orderInvoiceCollection)) {
            die(Tools::displayError('No invoice was found.'));
        }

        $this->generatePDF($orderInvoiceCollection, PDF::TEMPLATE_INVOICE);
    }

    /**
     * Generate PDF invoices 2
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function processGenerateInvoicesPDF2()
    {
        $orderInvoiceCollection = [];
        foreach (explode('-', Tools::getValue('id_order_state')) as $idOrderState) {
            if (is_array($orderInvoices = OrderInvoice::getByStatus((int) $idOrderState))) {
                $orderInvoiceCollection = array_merge($orderInvoices, $orderInvoiceCollection);
            }
        }

        if (!count($orderInvoiceCollection)) {
            die(Tools::displayError('No invoice was found.'));
        }

        $this->generatePDF($orderInvoiceCollection, PDF::TEMPLATE_INVOICE);
    }

    /**
     * Generate Order Slip PDFs
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function processGenerateOrderSlipsPDF()
    {
        $idOrderSlipsList = OrderSlip::getSlipsIdByDate(Tools::getValue('date_from'), Tools::getValue('date_to'));
        if (!count($idOrderSlipsList)) {
            die(Tools::displayError('No order slips were found.'));
        }

        $orderSlips = [];
        foreach ($idOrderSlipsList as $idOrderSlips) {
            $orderSlips[] = new OrderSlip((int) $idOrderSlips);
        }

        $this->generatePDF($orderSlips, PDF::TEMPLATE_ORDER_SLIP);
    }

    /**
     * Generate Delivery Slip PDFs
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function processGenerateDeliverySlipsPDF()
    {
        $orderInvoiceCollection = OrderInvoice::getByDeliveryDateInterval(Tools::getValue('date_from'), Tools::getValue('date_to'));

        if (!count($orderInvoiceCollection)) {
            die(Tools::displayError('No invoice was found.'));
        }

        $this->generatePDF($orderInvoiceCollection, PDF::TEMPLATE_DELIVERY_SLIP);
    }

    /**
     * Generate Supply Order Form PDFs
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function processGenerateSupplyOrderFormPDF()
    {
        if (!Tools::isSubmit('id_supply_order')) {
            die(Tools::displayError('The supply order ID is missing.'));
        }

        $idSupplyOrder = (int) Tools::getValue('id_supply_order');
        $supplyOrder = new SupplyOrder($idSupplyOrder);

        if (!Validate::isLoadedObject($supplyOrder)) {
            die(Tools::displayError('The supply order cannot be found within your database.'));
        }

        $this->generatePDF($supplyOrder, PDF::TEMPLATE_SUPPLY_ORDER_FORM);
    }
}
