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
 * Class PdfInvoiceControllerCore
 */
class PdfInvoiceControllerCore extends FrontController
{
    /** @var string $php_self */
    public $php_self = 'pdf-invoice';
    /** @var bool $content_only */
    public $content_only = true;
    /** @var string $filename */
    public $filename;
    /** @var bool $display_header */
    protected $display_header = false;
    /** @var bool $display_footer */
    protected $display_footer = false;
    /** @var string $template */
    protected $template;

    /**
     * Post processing
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (!(int) Configuration::get('PS_INVOICE')) {
            throw new PrestaShopException(Tools::displayError('Invoices are disabled in this shop.'));
        }

        $idOrder = (int) Tools::getValue('id_order');
        if (!$this->context->customer->isLogged() && !Tools::getValue('secure_key')) {
            $backLink = $this->context->link->getPageLink('pdf-invoice', null, $this->context->language->id, 'id_order=' . $idOrder);
            Tools::redirect('index.php?controller=authentication&back='.urlencode($backLink));
        }

        if (Validate::isUnsignedId($idOrder)) {
            $order = new Order((int) $idOrder);
        }

        if (!isset($order) || !Validate::isLoadedObject($order)) {
            throw new PrestaShopException(Tools::displayError('The invoice was not found.'));
        }

        if ((isset($this->context->customer->id) && $order->id_customer != $this->context->customer->id) || (Tools::isSubmit('secure_key') && $order->secure_key != Tools::getValue('secure_key'))) {
            throw new PrestaShopException(Tools::displayError('The invoice was not found.'));
        }

        if (!OrderState::invoiceAvailable($order->getCurrentState()) && !$order->invoice_number) {
            throw new PrestaShopException(Tools::displayError('No invoice is available.'));
        }

        $this->order = $order;
    }

    /**
     * Display
     *
     * @return void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function display()
    {
        $orderInvoiceList = $this->order->getInvoicesCollection();
        Hook::exec('actionPDFInvoiceRender', ['order_invoice_list' => $orderInvoiceList]);

        $pdf = new PDF($orderInvoiceList, PDF::TEMPLATE_INVOICE, $this->context->smarty);
        $pdf->render();
    }
}
