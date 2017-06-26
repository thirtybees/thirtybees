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
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class DiscountControllerCore
 *
 * @since 1.0.0
 */
class DiscountControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'discount';
    /** @var string $authRedirection */
    public $authRedirection = 'discount';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        parent::initContent();

        $cartRules = CartRule::getCustomerCartRules($this->context->language->id, $this->context->customer->id, true, false, true);
        $nbCartRules = count($cartRules);

        foreach ($cartRules as $key => &$discount) {
            if ($discount['quantity_for_user'] === 0) {
                unset($cartRules[$key]);

                continue;
            }

            $discount['value'] = Tools::convertPriceFull(
                $discount['value'],
                new Currency((int) $discount['reduction_currency']),
                new Currency((int) $this->context->cart->id_currency)
            );
            if ($discount['gift_product'] !== 0) {
                $product = new Product((int) $discount['gift_product']);
                if (isset($product->name)) {
                    $discount['gift_product_name'] = $product->name;
                }
            }
        }

        $this->context->smarty->assign(
            [
                'nb_cart_rules' => (int) $nbCartRules,
                'cart_rules'    => $cartRules,
                'discount'      => $cartRules,
                'nbDiscounts'   => (int) $nbCartRules,
            ]
        );
        $this->setTemplate(_PS_THEME_DIR_.'discount.tpl');
    }
}
