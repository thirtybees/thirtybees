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
 * Class SitemapControllerCore
 *
 * @since 1.0.0
 */
class SitemapControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'sitemap';
    // @codingStandardsIgnoreEnd

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_.'sitemap.css');
        $this->addJS(_THEME_JS_DIR_.'tools/treeManagement.js');
    }

    /**
     * Assign template vars related to page content
     *
     * @see   FrontController::initContent()
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign('categoriesTree', Category::getRootCategory()->recurseLiteCategTree(0));
        $this->context->smarty->assign('categoriescmsTree', CMSCategory::getRecurseCategory($this->context->language->id, 1, 1, 1));
        $this->context->smarty->assign('voucherAllowed', (int) CartRule::isFeatureActive());

        if (Module::isInstalled('blockmanufacturer') && Module::isEnabled('blockmanufacturer')) {
            $blockmanufacturer = Module::getInstanceByName('blockmanufacturer');
            $this->context->smarty->assign('display_manufacturer_link', isset($blockmanufacturer->active) ? (bool) $blockmanufacturer->active : false);
        } else {
            $this->context->smarty->assign('display_manufacturer_link', 0);
        }

        if (Module::isInstalled('blocksupplier') && Module::isEnabled('blocksupplier')) {
            $blocksupplier = Module::getInstanceByName('blocksupplier');
            $this->context->smarty->assign('display_supplier_link', isset($blocksupplier->active) ? (bool) $blocksupplier->active : false);
        } else {
            $this->context->smarty->assign('display_supplier_link', 0);
        }

        $this->context->smarty->assign('PS_DISPLAY_SUPPLIERS', Configuration::get('PS_DISPLAY_SUPPLIERS'));
        $this->context->smarty->assign('PS_DISPLAY_BEST_SELLERS', Configuration::get('PS_DISPLAY_BEST_SELLERS'));
        $this->context->smarty->assign('display_store', Configuration::get('PS_STORES_DISPLAY_SITEMAP'));

        $this->setTemplate(_PS_THEME_DIR_.'sitemap.tpl');
    }
}
