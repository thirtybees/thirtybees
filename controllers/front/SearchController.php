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
 * Class SearchControllerCore
 *
 * @since 1.0.0
 */
class SearchControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'search';
    /** @var string $instant_search */
    public $instant_search;
    /** @var string $ajax_search */
    public $ajax_search;
    // @codingStandardsIgnoreEnd

    /**
     * Initialize search controller
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

        $this->instant_search = Tools::getValue('instantSearch');

        $this->ajax_search = Tools::getValue('ajaxSearch');

        if ($this->instant_search || $this->ajax_search) {
            $this->display_header = false;
            $this->display_footer = false;
        }
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
        $originalQuery = Tools::getValue('q');
        $query = Tools::replaceAccentedChars(urldecode($originalQuery));
        if ($this->ajax_search) {
            $searchResults = Search::find((int) (Tools::getValue('id_lang')), $query, 1, 10, 'position', 'desc', true);
            if (is_array($searchResults)) {
                foreach ($searchResults as &$product) {
                    $product['product_link'] = $this->context->link->getProductLink($product['id_product'], $product['prewrite'], $product['crewrite']);
                }
                Hook::exec('actionSearch', ['expr' => $query, 'total' => count($searchResults)]);
            }
            $this->ajaxDie(json_encode($searchResults));
        }

        //Only controller content initialization when the user use the normal search
        parent::initContent();

        $productPerPage = isset($this->context->cookie->nb_item_per_page) ? (int) $this->context->cookie->nb_item_per_page : Configuration::get('PS_PRODUCTS_PER_PAGE');

        if ($this->instant_search && !is_array($query)) {
            $this->productSort();
            $this->n = abs((int) (Tools::getValue('n', $productPerPage)));
            $this->p = abs((int) (Tools::getValue('p', 1)));
            $search = Search::find($this->context->language->id, $query, 1, 10, 'position', 'desc');
            Hook::exec('actionSearch', ['expr' => $query, 'total' => $search['total']]);
            $nbProducts = $search['total'];
            $this->pagination($nbProducts);

            $this->addColorsToProductList($search['result']);

            $this->context->smarty->assign(
                [
                    'products'        => $search['result'], // DEPRECATED (since to 1.4), not use this: conflict with block_cart module
                    'search_products' => $search['result'],
                    'nbProducts'      => $search['total'],
                    'search_query'    => $originalQuery,
                    'instant_search'  => $this->instant_search,
                    'homeSize'        => Image::getSize(ImageType::getFormatedName('home')),
                ]
            );
        } elseif (($query = Tools::getValue('search_query', Tools::getValue('ref'))) && !is_array($query)) {
            $this->productSort();
            $this->n = abs((int) (Tools::getValue('n', $productPerPage)));
            $this->p = abs((int) (Tools::getValue('p', 1)));
            $originalQuery = $query;
            $query = Tools::replaceAccentedChars(urldecode($query));
            $search = Search::find($this->context->language->id, $query, $this->p, $this->n, $this->orderBy, $this->orderWay);
            if (is_array($search['result'])) {
                foreach ($search['result'] as &$product) {
                    $product['link'] .= (strpos($product['link'], '?') === false ? '?' : '&').'search_query='.urlencode($query).'&results='.(int) $search['total'];
                }
            }

            Hook::exec('actionSearch', ['expr' => $query, 'total' => $search['total']]);
            $nbProducts = $search['total'];
            $this->pagination($nbProducts);

            $this->addColorsToProductList($search['result']);

            $this->context->smarty->assign(
                [
                    'products'        => $search['result'], // DEPRECATED (since to 1.4), not use this: conflict with block_cart module
                    'search_products' => $search['result'],
                    'nbProducts'      => $search['total'],
                    'search_query'    => $originalQuery,
                    'homeSize'        => Image::getSize(ImageType::getFormatedName('home')),
                ]
            );
        } elseif (($tag = urldecode(Tools::getValue('tag'))) && !is_array($tag)) {
            $nbProducts = (int) (Search::searchTag($this->context->language->id, $tag, true));
            $this->pagination($nbProducts);
            $result = Search::searchTag($this->context->language->id, $tag, false, $this->p, $this->n, $this->orderBy, $this->orderWay);
            Hook::exec('actionSearch', ['expr' => $tag, 'total' => count($result)]);

            $this->addColorsToProductList($result);

            $this->context->smarty->assign(
                [
                    'search_tag'      => $tag,
                    'products'        => $result, // DEPRECATED (since to 1.4), not use this: conflict with block_cart module
                    'search_products' => $result,
                    'nbProducts'      => $nbProducts,
                    'homeSize'        => Image::getSize(ImageType::getFormatedName('home')),
                ]
            );
        } else {
            $this->context->smarty->assign(
                [
                    'products'        => [],
                    'search_products' => [],
                    'pages_nb'        => 1,
                    'nbProducts'      => 0,
                ]
            );
        }
        $this->context->smarty->assign(['add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'), 'comparator_max_item' => Configuration::get('PS_COMPARATOR_MAX_ITEM')]);

        $this->setTemplate(_PS_THEME_DIR_.'search.tpl');
    }

    /**
     * Display header
     *
     * @param bool $display
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function displayHeader($display = true)
    {
        if (!$this->instant_search && !$this->ajax_search) {
            parent::displayHeader();
        } else {
            $this->context->smarty->assign('static_token', Tools::getToken(false));
        }
    }

    /**
     * Display footer
     *
     * @param bool $display
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function displayFooter($display = true)
    {
        if (!$this->instant_search && !$this->ajax_search) {
            parent::displayFooter();
        }
    }

    /**
     * Set Media
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();

        if (!$this->instant_search && !$this->ajax_search) {
            $this->addCSS(_THEME_CSS_DIR_.'product_list.css');
        }
    }
}
