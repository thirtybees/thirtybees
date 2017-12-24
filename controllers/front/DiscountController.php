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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function initContent()
    {
        parent::initContent();

        $cartRules = CartRule::getCustomerCartRules(
            $this->context->language->id,
            $this->context->customer->id,
            true,
            false,
            true,
            null,
            false,
            true
        );

        foreach ($cartRules as $key => $discount) {
            if ($discount['quantity_for_user'] === 0) {
                unset($cartRules[$key]);

                continue;
            }

            if (!empty($discount['value'])) {
                $cartRules[$key]['value'] = Tools::convertPriceFull(
                    $discount['value'],
                    new Currency((int) $discount['reduction_currency']),
                    new Currency((int) $this->context->cart->id_currency)
                );
            }
            if ((int) $discount['gift_product'] !== 0) {
                $product = new Product((int) $discount['gift_product'], false, (int) $this->context->language->id);
                if (!Validate::isLoadedObject($product) || !$product->isAssociatedToShop() || !$product->active) {
                    unset($cartRules[$key]);
                }
                if (Combination::isFeatureActive() && (int) $discount['gift_product_attribute'] !== 0) {
                    $attributes = $product->getAttributeCombinationsById(
                        (int) $discount['gift_product_attribute'],
                        (int) $this->context->language->id
                    );
                    $giftAttributes = array();
                    foreach ($attributes as $attribute) {
                        $giftAttributes[] = $attribute['group_name'].' : '.$attribute['attribute_name'];
                    }
                    $cartRules[$key]['gift_product_attributes'] = implode(', ', $giftAttributes);
                }
                $cartRules[$key]['gift_product_name'] = $product->name;
                $cartRules[$key]['gift_product_link'] = $this->context->link->getProductLink(
                    $product,
                    $product->link_rewrite,
                    $product->category,
                    $product->ean13,
                    $this->context->language->id,
                    $this->context->shop->id,
                    $discount['gift_product_attribute'],
                    false,
                    false,
                    true
                );
            }
        }

        $nbCartRules = count($cartRules);

        $this->context->smarty->assign(
            [
                'nb_cart_rules' => (int) $nbCartRules,
                'cart_rules'    => $cartRules,
                'discount'      => $cartRules, // retro compat
                'nbDiscounts'   => (int) $nbCartRules, // retro compat
            ]
        );
        $this->setTemplate(_PS_THEME_DIR_.'discount.tpl');
    }
}
