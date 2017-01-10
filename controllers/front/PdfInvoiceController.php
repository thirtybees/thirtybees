<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

class PdfInvoiceControllerCore extends FrontController
{
    public $php_self = 'pdf-invoice';
    protected $display_header = false;
    protected $display_footer = false;

    public $content_only = true;

    protected $template;
    public $filename;

    public function postProcess()
    {
        if (!$this->context->customer->isLogged() && !Tools::getValue('secure_key')) {
            Tools::redirect('index.php?controller=authentication&back=pdf-invoice');
        }

        if (!(int)Configuration::get('PS_INVOICE')) {
            die(Tools::displayError('Invoices are disabled in this shop.'));
        }

        $id_order = (int)Tools::getValue('id_order');
        if (Validate::isUnsignedId($id_order)) {
            $order = new Order((int)$id_order);
        }

        if (!isset($order) || !Validate::isLoadedObject($order)) {
            die(Tools::displayError('The invoice was not found.'));
        }

        if ((isset($this->context->customer->id) && $order->id_customer != $this->context->customer->id) || (Tools::isSubmit('secure_key') && $order->secure_key != Tools::getValue('secure_key'))) {
            die(Tools::displayError('The invoice was not found.'));
        }

        if (!OrderState::invoiceAvailable($order->getCurrentState()) && !$order->invoice_number) {
            die(Tools::displayError('No invoice is available.'));
        }

        $this->order = $order;
    }

    public function display()
    {
        $order_invoice_list = $this->order->getInvoicesCollection();
        Hook::exec('actionPDFInvoiceRender', ['order_invoice_list' => $order_invoice_list]);

        $pdf = new PDF($order_invoice_list, PDF::TEMPLATE_INVOICE, $this->context->smarty);
        $pdf->render();
    }
}
