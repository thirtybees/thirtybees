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
 * Class BestSalesControllerCore
 */
class BestSalesControllerCore extends FrontController
{
    /** @var string $php_self */
    public $php_self = 'best-sales';

    /**
     * Initialize content
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        if (Configuration::get('PS_DISPLAY_BEST_SELLERS')) {
            parent::initContent();

            $this->productSort();
            $nbProducts = (int) ProductSale::getNbSales();
            $this->pagination($nbProducts);

            if (!Tools::getValue('orderby')) {
                $this->orderBy = 'sales';
            }

            $products = ProductSale::getBestSales($this->context->language->id, $this->p - 1, $this->n, $this->orderBy, $this->orderWay);
            $this->addColorsToProductList($products);

            $this->context->smarty->assign(
                [
                    'products'            => $products,
                    'add_prod_display'    => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
                    'nbProducts'          => $nbProducts,
                    'homeSize'            => Image::getSize(ImageType::getFormatedName('home')),
                    'comparator_max_item' => Configuration::get('PS_COMPARATOR_MAX_ITEM'),
                ]
            );

            $this->setTemplate(_PS_THEME_DIR_.'best-sales.tpl');
        } else {
            Tools::redirect('index.php?controller=404');
        }
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
        $this->addCSS(_THEME_CSS_DIR_.'product_list.css');
    }
}
