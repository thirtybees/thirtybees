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

use Thirtybees\Core\View\Model\ProductViewModel;

/**
 * Class CompareControllerCore
 */
class CompareControllerCore extends FrontController
{
    /**
     * @var string $php_self
     */
    public $php_self = 'products-comparison';

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
        $this->addCSS(_THEME_CSS_DIR_.'comparator.css');
    }

    /**
     * Display ajax content (this function is called instead of classic display, in ajax mode)
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function displayAjax()
    {
        // Add or remove product with Ajax
        if (Tools::getValue('ajax') && Tools::getIntValue('id_product') && Tools::getValue('action')) {
            $productId = Tools::getIntValue('id_product');
            if (Tools::getValue('action') == 'add') {
                $idCompare = $this->context->cookie->id_compare ?? false;
                if (CompareProduct::getNumberProducts($idCompare) < Configuration::get('PS_COMPARATOR_MAX_ITEM')) {
                    CompareProduct::addCompareProduct($idCompare, $productId);
                } else {
                    $this->ajaxDie('0');
                }
            } elseif (Tools::getValue('action') == 'remove') {
                if (isset($this->context->cookie->id_compare)) {
                    CompareProduct::removeCompareProduct((int) $this->context->cookie->id_compare, $productId);
                } else {
                    $this->ajaxDie('0');
                }
            } else {
                $this->ajaxDie('0');
            }
            $this->ajaxDie('1');
        }
        $this->ajaxDie('0');
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
        if (Tools::getValue('ajax')) {
            return;
        }
        parent::initContent();

        //Clean compare product table
        CompareProduct::cleanCompareProducts();

        $hasProduct = false;

        if (!Configuration::get('PS_COMPARATOR_MAX_ITEM')) {
            Tools::redirect('index.php?controller=404');

            return;
        }

        $ids = null;
        if (($productList = urldecode(Tools::getValue('compare_product_list'))) && ($postProducts = rtrim($productList, '|'))) {
            $ids = array_unique(explode('|', $postProducts));
        } elseif (isset($this->context->cookie->id_compare)) {
            $ids = CompareProduct::getCompareProducts($this->context->cookie->id_compare);
            if (count($ids)) {
                Tools::redirect($this->context->link->getPageLink('products-comparison', null, $this->context->language->id, ['compare_product_list' => implode('|', $ids)]));
            }
        }

        if ($ids) {
            if (count($ids) > 0) {
                if (count($ids) > Configuration::get('PS_COMPARATOR_MAX_ITEM')) {
                    $ids = array_slice($ids, 0, Configuration::get('PS_COMPARATOR_MAX_ITEM'));
                }

                $listProducts = [];
                $listFeatures = [];

                foreach ($ids as $k => &$id) {
                    $curProduct = new ProductViewModel((int) $id, 0, $this->context->language->id, $this->context->shop->id);
                    if (!Validate::isLoadedObject($curProduct) || !$curProduct->active || !$curProduct->isAssociatedToShop()) {
                        if (isset($this->context->cookie->id_compare)) {
                            CompareProduct::removeCompareProduct($this->context->cookie->id_compare, $id);
                        }
                        unset($ids[$k]);
                        continue;
                    }

                    foreach ($curProduct->getFrontFeatures($this->context->language->id) as $feature) {
                        $listFeatures[$curProduct->id][$feature['id_feature']] = $feature['value'];
                    }

                    $listProducts[] = $curProduct;
                }

                if (count($listProducts) > 0) {
                    $width = 80 / count($listProducts);

                    $hasProduct = true;
                    $orderedFeatures = Feature::getFeaturesForComparison($ids, $this->context->language->id);
                    $this->context->smarty->assign(
                        [
                            'ordered_features'               => $orderedFeatures,
                            'product_features'               => $listFeatures,
                            'products'                       => $listProducts,
                            'width'                          => $width,
                            'HOOK_COMPARE_EXTRA_INFORMATION' => Hook::displayHook('displayCompareExtraInformation', ['list_ids_product' => $ids]),
                            'HOOK_EXTRA_PRODUCT_COMPARISON'  => Hook::displayHook('displayProductComparison', ['list_ids_product' => $ids]),
                            'homeSize'                       => Image::getSize(ImageType::getFormatedName('home')),
                        ]
                    );
                } elseif (isset($this->context->cookie->id_compare)) {
                    $object = new CompareProduct((int) $this->context->cookie->id_compare);
                    if (Validate::isLoadedObject($object)) {
                        $object->delete();
                    }
                }
            }
        }
        $this->context->smarty->assign('hasProduct', $hasProduct);

        $this->setTemplate(_PS_THEME_DIR_.'products-comparison.tpl');
    }
}
