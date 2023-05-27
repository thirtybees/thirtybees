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
 * Class PdfOrderReturnControllerCore
 */
class PdfOrderReturnControllerCore extends FrontController
{
    /**
     * @var string $php_self
     */
    public $php_self = 'pdf-order-return';

    /**
     * @var bool $display_header
     */
    protected $display_header = false;

    /**
     * @var bool $display_footer
     */
    protected $display_footer = false;

    /**
     * @var OrderReturn
     */
    protected $orderReturn;

    /**
     * Post processing
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $fromAdmin = (Tools::getValue('adtoken') == Tools::getAdminToken('AdminReturn'.(int) Tab::getIdFromClassName('AdminReturn').Tools::getIntValue('id_employee')));

        if (!$fromAdmin && !$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication&back=order-follow');
        }

        if (Tools::getIntValue('id_order_return') && Validate::isUnsignedId(Tools::getIntValue('id_order_return'))) {
            $this->orderReturn = new OrderReturn(Tools::getIntValue('id_order_return'));
        }

        if (!isset($this->orderReturn) || !Validate::isLoadedObject($this->orderReturn)) {
            throw new PrestaShopException(Tools::displayError('Order return not found.'));
        } elseif (!$fromAdmin && $this->orderReturn->id_customer != $this->context->customer->id) {
            throw new PrestaShopException(Tools::displayError('Order return not found.'));
        } elseif ($this->orderReturn->state < 2) {
            throw new PrestaShopException(Tools::displayError('Order return not confirmed.'));
        }
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
        $pdf = new PDF($this->orderReturn, PDF::TEMPLATE_ORDER_RETURN, $this->context->smarty);
        $pdf->render();
    }
}
