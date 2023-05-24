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
 * Class AdminTrackingControllerCore
 *
 * @property Product|Category|null $object
 */
class AdminTrackingControllerCore extends AdminController
{
    /**
     * @var HelperList
     */
    protected $_helper_list;

    /**
     * @var string
     */
    protected $_list_index;

    /**
     * @var string
     */
    protected $_list_token;

    /**
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }


    /**
     * @param string $description
     *
     * @return string
     */
    public static function getDescriptionClean($description)
    {
        return Tools::getDescriptionClean($description);
    }

    /**
     * Post processing
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function postprocess()
    {
        if (Tools::getValue('id_product') && Tools::isSubmit('statusproduct')) {
            $this->table = 'product';
            $this->identifier = 'id_product';
            $this->action = 'status';
            $this->className = 'Product';
        } elseif (Tools::getValue('id_category') && Tools::isSubmit('statuscategory')) {
            $this->table = 'category';
            $this->identifier = 'id_category';
            $this->action = 'status';
            $this->className = 'Category';
        }

        $this->list_no_link = true;

        return parent::postprocess();
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function initContent()
    {
        $this->initPageHeaderToolbar();

        if (($idCategory = (int)Tools::getValue('id_category')) && Tools::getIsset('viewcategory')) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminProducts').'&id_category='.$idCategory.'&viewcategory');
        }

        $this->_helper_list = new HelperList();

        if (!Configuration::get('PS_STOCK_MANAGEMENT')) {
            $this->warnings[] = $this->l('List of products without available quantities for sale are not displayed because stock management is disabled.');
        }

        $methods = get_class_methods($this);
        foreach ($methods as $methodName) {
            if (preg_match('#getCustomList(.+)#', $methodName, $matches)) {
                $this->clearListOptions();
                $this->content .= call_user_func([$this, $matches[0]]);
            }
        }
        $this->context->smarty->assign(
            [
                'content'                   => $this->content,
                'url_post'                  => static::$currentIndex.'&token='.$this->token,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * @return void
     */
    public function clearListOptions()
    {
        $this->table = '';
        $this->actions = [];
        $this->list_skip_actions = [];
        $this->lang = false;
        $this->identifier = '';
        $this->_orderBy = '';
        $this->_orderWay = '';
        $this->_filter = '';
        $this->_group = '';
        $this->_where = '';
    }

    /**
     * @return bool|false|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getCustomListCategoriesEmpty()
    {
        $this->table = 'category';
        $this->list_id = 'empty_categories';
        $this->lang = true;
        $this->className = 'Category';
        $this->identifier = 'id_category';
        $this->_orderBy = 'id_category';
        $this->_orderWay = 'DESC';
        $this->_list_index = 'index.php?controller=AdminCategories';
        $this->_list_token = Tools::getAdminTokenLite('AdminCategories');

        $this->addRowAction('edit');
        $this->addRowAction('view');
        $this->addRowAction('delete');
        $this->addRowActionSkipList('delete', [(int) Configuration::get('PS_ROOT_CATEGORY')]);
        $this->addRowActionSkipList('edit', [(int) Configuration::get('PS_ROOT_CATEGORY')]);

        $this->fields_list = ([
            'id_category' => ['title' => $this->l('ID'), 'class' => 'fixed-width-xs', 'align' => 'center'],
            'name'        => ['title' => $this->l('Name'), 'filter_key' => 'b!name'],
            'description' => ['title' => $this->l('Description'), 'callback' => 'getDescriptionClean'],
            'active'      => ['title' => $this->l('Status'), 'type' => 'bool', 'active' => 'status', 'align' => 'center', 'class' => 'fixed-width-xs'],
        ]);
        $this->clearFilters();

        $this->_join = Shop::addSqlAssociation('category', 'a');
        $this->_filter = ' AND NOT EXISTS (
			SELECT 1
			FROM `'._DB_PREFIX_.'category_product` cp
			WHERE a.`id_category` = cp.id_category
		)
		AND a.`id_category` != '.(int) Configuration::get('PS_ROOT_CATEGORY');
        $this->toolbar_title = $this->l('List of empty categories:');

        return $this->renderList();
    }

    /**
     * @return void
     */
    protected function clearFilters()
    {
        if (Tools::isSubmit('submitResetempty_categories')) {
            $this->processResetFilters('empty_categories');
        }

        if (Tools::isSubmit('submitResetno_stock_products_attributes')) {
            $this->processResetFilters('no_stock_products_attributes');
        }

        if (Tools::isSubmit('submitResetno_stock_products')) {
            $this->processResetFilters('no_stock_products');
        }

        if (Tools::isSubmit('submitResetdisabled_products')) {
            $this->processResetFilters('disabled_products');
        }
    }

    /**
     * @return bool|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        $this->processFilter();

        if (!($this->fields_list && is_array($this->fields_list))) {
            return false;
        }
        $this->getList($this->context->language->id);

        $helper = new HelperList();

        // Empty list is ok
        if (!is_array($this->_list)) {
            $this->displayWarning($this->l('Bad SQL query', 'Helper').'<br />'.htmlspecialchars($this->_list_error));

            return false;
        }

        $this->setHelperDisplay($helper);
        $helper->tpl_vars = $this->tpl_list_vars;
        $helper->tpl_delete_link_vars = $this->tpl_delete_link_vars;

        // For compatibility reasons, we have to check standard actions in class attributes
        foreach ($this->actions_available as $action) {
            if (!in_array($action, $this->actions) && isset($this->$action) && $this->$action) {
                $this->actions[] = $action;
            }
        }
        $helper->is_cms = $this->is_cms;
        $list = $helper->generateList($this->_list, $this->fields_list);

        return $list;
    }

    /**
     * @param int $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int $start
     * @param int|null $limit
     * @param int|bool $idLangShop
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $this->context->shop->id);
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getCustomListProductsAttributesNoStock()
    {
        if (!Configuration::get('PS_STOCK_MANAGEMENT')) {
            return '';
        }

        $this->table = 'product';
        $this->list_id = 'no_stock_products_attributes';
        $this->lang = true;
        $this->identifier = 'id_product';
        $this->_orderBy = 'id_product';
        $this->_orderWay = 'DESC';
        $this->className = 'Product';
        $this->_list_index = 'index.php?controller=AdminProducts';
        $this->_list_token = Tools::getAdminTokenLite('AdminProducts');
        $this->show_toolbar = false;

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->fields_list = [
            'id_product' => ['title' => $this->l('ID'), 'class' => 'fixed-width-xs', 'align' => 'center'],
            'reference'  => ['title' => $this->l('Reference')],
            'name'       => ['title' => $this->l('Name'), 'filter_key' => 'b!name'],
            'active'     => ['title' => $this->l('Status'), 'type' => 'bool', 'active' => 'status', 'align' => 'center', 'class' => 'fixed-width-xs', 'filter_key' => 'a!active'],
        ];

        $this->clearFilters();

        $this->_join = Shop::addSqlAssociation('product', 'a');
        $this->_filter = 'AND EXISTS (
			SELECT 1
			FROM `'._DB_PREFIX_.'product` p
			'.Product::sqlStock('p').'
			WHERE a.id_product = p.id_product AND EXISTS (
				SELECT 1
				FROM `'._DB_PREFIX_.'product_attribute` WHERE `'._DB_PREFIX_.'product_attribute`.id_product = p.id_product
			)
			AND IFNULL(stock.quantity, 0) <= 0
		)';
        $this->toolbar_title = $this->l('List of products with attributes but without available quantities for sale:');

        return $this->renderList();
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getCustomListProductsNoStock()
    {
        if (!Configuration::get('PS_STOCK_MANAGEMENT')) {
            return '';
        }

        $this->table = 'product';
        $this->list_id = 'no_stock_products';
        $this->className = 'Product';
        $this->lang = true;
        $this->identifier = 'id_product';
        $this->_orderBy = 'id_product';
        $this->_orderWay = 'DESC';
        $this->show_toolbar = false;
        $this->_list_index = 'index.php?controller=AdminProducts';
        $this->_list_token = Tools::getAdminTokenLite('AdminProducts');

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->fields_list = [
            'id_product' => ['title' => $this->l('ID'), 'class' => 'fixed-width-xs', 'align' => 'center'],
            'reference'  => ['title' => $this->l('Reference')],
            'name'       => ['title' => $this->l('Name')],
            'active'     => ['title' => $this->l('Status'), 'type' => 'bool', 'active' => 'status', 'align' => 'center', 'class' => 'fixed-width-xs', 'filter_key' => 'a!active'],
        ];
        $this->clearFilters();

        $this->_join = Shop::addSqlAssociation('product', 'a');
        $this->_filter = 'AND EXISTS (
			SELECT 1
			FROM `'._DB_PREFIX_.'product` p
			'.Product::sqlStock('p').'
			WHERE a.id_product = p.id_product AND NOT EXISTS (
				SELECT 1
				FROM `'._DB_PREFIX_.'product_attribute` pa WHERE pa.id_product = p.id_product
			)
			AND IFNULL(stock.quantity, 0) <= 0
		)';

        $this->toolbar_title = $this->l('List of products without attributes and without available quantities for sale:');

        return $this->renderList();
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getCustomListProductsDisabled()
    {
        $this->table = 'product';
        $this->list_id = 'disabled_products';
        $this->className = 'Product';
        $this->lang = true;
        $this->identifier = 'id_product';
        $this->_orderBy = 'id_product';
        $this->_orderWay = 'DESC';
        $this->_filter = 'AND product_shop.`active` = 0';
        $this->show_toolbar = false;
        $this->_list_index = 'index.php?controller=AdminProducts';
        $this->_list_token = Tools::getAdminTokenLite('AdminProducts');

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->fields_list = [
            'id_product' => ['title' => $this->l('ID'), 'class' => 'fixed-width-xs', 'align' => 'center'],
            'reference'  => ['title' => $this->l('Reference')],
            'name'       => ['title' => $this->l('Name'), 'filter_key' => 'b!name'],
        ];

        $this->clearFilters();

        $this->_join = Shop::addSqlAssociation('product', 'a');
        $this->toolbar_title = $this->l('List of disabled products:');

        return (string) $this->renderList();
    }

    /**
     * @param string $token
     * @param int $id
     * @param bool $value
     * @param bool $active
     * @param int|null $idCategory
     * @param int|null $idProduct
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayEnableLink($token, $id, $value, $active, $idCategory = null, $idProduct = null)
    {
        $this->_helper_list->currentIndex = $this->_list_index;
        $this->_helper_list->identifier = $this->identifier;
        $this->_helper_list->table = $this->table;

        return $this->_helper_list->displayEnableLink($this->_list_token, $id, $value, $active, $idCategory, $idProduct);
    }

    /**
     * @param string $token
     * @param int $id
     * @param string|null $name
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayDeleteLink($token, $id, $name = null)
    {
        $this->_helper_list->currentIndex = $this->_list_index;
        $this->_helper_list->identifier = $this->identifier;
        $this->_helper_list->table = $this->table;

        return $this->_helper_list->displayDeleteLink($this->_list_token, $id, $name);
    }

    /**
     * @param string $token
     * @param int $id
     * @param string|null $name
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayEditLink($token, $id, $name = null)
    {
        $this->_helper_list->currentIndex = $this->_list_index;
        $this->_helper_list->identifier = $this->identifier;
        $this->_helper_list->table = $this->table;

        return $this->_helper_list->displayEditLink($this->_list_token, $id, $name);
    }
}
