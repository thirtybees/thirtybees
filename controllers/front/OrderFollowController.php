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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class OrderFollowControllerCore
 */
class OrderFollowControllerCore extends FrontController
{
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'order-follow';
    /** @var string $authRedirection */
    public $authRedirection = 'order-follow';
    /** @var bool $ssl */
    public $ssl = true;

    /**
     * Start forms process
     *
     * @throws PrestaShopException
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitReturnMerchandise')) {
            $customizationQtyInput = Tools::getValue('customization_qty_input');
            $orderQteInput = Tools::getValue('order_qte_input');
            $customizationIds = Tools::getValue('customization_ids');

            if (!$idOrder = Tools::getIntValue('id_order')) {
                Tools::redirect('index.php?controller=history');
            }
            if (!$orderQteInput && !$customizationQtyInput && !$customizationIds) {
                Tools::redirect('index.php?controller=order-follow&errorDetail1');
            }
            if (!$customizationIds && !$idsOrderDetail = Tools::getValue('ids_order_detail')) {
                Tools::redirect('index.php?controller=order-follow&errorDetail2');
            }
            if (!isset($idsOrderDetail)) {
                Tools::redirect('index.php?controller=order-follow&errorDetail2');

                return;
            }

            $order = new Order((int) $idOrder);
            if (!$order->isReturnable()) {
                Tools::redirect('index.php?controller=order-follow&errorNotReturnable');
            }
            if ($order->id_customer != $this->context->customer->id) {
                throw new PrestaShopException(Tools::displayError("Order was not placed by this customer"));
            }
            $orderReturn = new OrderReturn();
            $orderReturn->id_customer = (int) $this->context->customer->id;
            $orderReturn->id_order = $idOrder;
            $orderReturn->question = htmlspecialchars(Tools::getValue('returnText'));
            if (empty($orderReturn->question)) {
                Tools::redirect(
                    'index.php?controller=order-follow&errorMsg&'.http_build_query(
                        [
                            'ids_order_detail' => $idsOrderDetail,
                            'order_qte_input'  => $orderQteInput,
                            'id_order'         => $idOrder,
                        ]
                    )
                );
            }

            if (!$orderReturn->checkEnoughProduct($idsOrderDetail, $orderQteInput, $customizationIds, $customizationQtyInput)) {
                Tools::redirect('index.php?controller=order-follow&errorQuantity');
            }

            $orderReturn->state = 1;
            $orderReturn->add();
            $orderReturn->addReturnDetail($idsOrderDetail, $orderQteInput, $customizationIds, $customizationQtyInput);
            Hook::triggerEvent('actionOrderReturn', ['orderReturn' => $orderReturn]);
            Tools::redirect('index.php?controller=order-follow');
        }
    }

    /**
     * Assign template vars related to page content
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $ordersReturn = OrderReturn::getOrdersReturn($this->context->customer->id);
        if (Tools::isSubmit('errorQuantity')) {
            $this->context->smarty->assign('errorQuantity', true);
        } elseif (Tools::isSubmit('errorMsg')) {
            $this->context->smarty->assign(
                [
                    'errorMsg'         => true,
                    'ids_order_detail' => Tools::getValue('ids_order_detail', []),
                    'order_qte_input'  => Tools::getValue('order_qte_input', []),
                    'id_order'         => Tools::getIntValue('id_order'),
                ]
            );
        } elseif (Tools::isSubmit('errorDetail1')) {
            $this->context->smarty->assign('errorDetail1', true);
        } elseif (Tools::isSubmit('errorDetail2')) {
            $this->context->smarty->assign('errorDetail2', true);
        } elseif (Tools::isSubmit('errorNotReturnable')) {
            $this->context->smarty->assign('errorNotReturnable', true);
        }

        $this->context->smarty->assign([
            'PS_RETURN_PREFIX' => Configuration::get('PS_RETURN_PREFIX', $this->context->language->id),
            'ordersReturn' => $ordersReturn
        ]);
        

        $this->setTemplate(_PS_THEME_DIR_.'order-follow.tpl');
    }

    /**
     * Set media
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS([_THEME_CSS_DIR_.'history.css', _THEME_CSS_DIR_.'addresses.css']);
        $this->addJqueryPlugin('scrollTo');
        $this->addJS(
            [
                _THEME_JS_DIR_.'history.js',
                _THEME_JS_DIR_.'tools.js',
            ] // retro compat themes 1.5
        );
        $this->addjqueryPlugin('footable');
        $this->addJqueryPlugin('footable-sort');
    }
}
