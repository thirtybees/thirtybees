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
 * Class OrderReturnControllerCore
 *
 * @since 1.0.0
 */
class OrderReturnControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'order-return';
    /** @var string $authRedirection */
    public $authRedirection = 'order-follow';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    /**
     * Initialize order return controller
     *
     * @see   FrontController::init()
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function init()
    {
        parent::init();

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        $idOrderReturn = (int) Tools::getValue('id_order_return');

        if (!isset($idOrderReturn) || !Validate::isUnsignedId($idOrderReturn)) {
            $this->errors[] = Tools::displayError('Order ID required');
        } else {
            $orderReturn = new OrderReturn((int) $idOrderReturn);
            if (Validate::isLoadedObject($orderReturn) && $orderReturn->id_customer == $this->context->cookie->id_customer) {
                $order = new Order((int) ($orderReturn->id_order));
                if (Validate::isLoadedObject($order)) {
                    $state = new OrderReturnState((int) $orderReturn->state);
                    $this->context->smarty->assign(
                        [
                            'PS_RETURN_PREFIX' => Configuration::get('PS_RETURN_PREFIX', $this->context->language->id),
                            'orderRet'               => $orderReturn,
                            'order'                  => $order,
                            'state_name'             => $state->name[(int) $this->context->language->id],
                            'return_allowed'         => false,
                            'products'               => OrderReturn::getOrdersReturnProducts((int) $orderReturn->id, $order),
                            'returnedCustomizations' => OrderReturn::getReturnedCustomizedProducts((int) $orderReturn->id_order),
                            'customizedDatas'        => Product::getAllCustomizedDatas((int) $order->id_cart),
                        ]
                    );
                } else {
                    $this->errors[] = Tools::displayError('Cannot find the order return.');
                }
            } else {
                $this->errors[] = Tools::displayError('Cannot find the order return.');
            }
        }
    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign(
            [
                'errors'       => $this->errors,
                'nbdaysreturn' => (int) Configuration::get('PS_ORDER_RETURN_NB_DAYS'),
            ]
        );
        $this->setTemplate(_PS_THEME_DIR_.'order-return.tpl');
    }

    /**
     * Process ajax call
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function displayAjax()
    {
        $this->smartyOutputContent($this->template);
    }
}
