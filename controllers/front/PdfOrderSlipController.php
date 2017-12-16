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
 * Class PdfOrderSlipControllerCore
 *
 * @since 1.0.0
 */
class PdfOrderSlipControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'pdf-order-slip';
    /** @var bool $display_header */
    protected $display_header = false;
    /** @var bool $display_footer */
    protected $display_footer = false;
    /** @var OrderSlip $order_slip */
    protected $order_slip;
    // @codingStandardsIgnoreEnd

    /**
     * Post processing
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication&back=order-follow');
        }

        if (isset($_GET['id_order_slip']) && Validate::isUnsignedId($_GET['id_order_slip'])) {
            $this->order_slip = new OrderSlip($_GET['id_order_slip']);
        }

        if (!isset($this->order_slip) || !Validate::isLoadedObject($this->order_slip)) {
            die(Tools::displayError('Order return not found.'));
        } elseif ($this->order_slip->id_customer != $this->context->customer->id) {
            die(Tools::displayError('Order return not found.'));
        }
    }

    /**
     * Display
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function display()
    {
        $pdf = new PDF($this->order_slip, PDF::TEMPLATE_ORDER_SLIP, $this->context->smarty);
        $pdf->render();
    }
}
