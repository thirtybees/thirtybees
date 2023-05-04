<?php
/**
 * Copyright (C) 2021 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2021 thirty bees
 * @license   Open Software License (OSL 3.0)
 */



/**
 * Class AdminCategoriesControllerCore reworked
 *
 * @since 1.5.0
 */
class AdminCategoriesAllController extends AdminController
{
	protected $original_filter = '';


	public function __construct()
	{
		$this->bootstrap = true;
		$this->table = 'category';
		$this->className = 'Category';
		$this->lang = true;
		$this->_defaultOrderBy = 'id_category';
		$this->_use_found_rows = false;
		$this->allow_export = true;
		$this->list_no_link = true;
		
		$this->addRowAction('preview');
		$this->addRowAction('edit');


		$this->context = Context::getContext();

		$this->fields_list = array(
			'id_category' => array(
				'title' => $this->l('ID'),
				'align' => 'center',
				'class' => 'fixed-width-xs'
			),
			'name' => array(
				'title' => $this->l('Name'),
				'class' => 'fixed-width-xxl'
			),
			'description' => array(
				'title' => $this->l('Description'),
				'callback' => 'getDescriptionClean',
				'orderby' => false
			),
		);

		parent::__construct();
	}
	

	public function renderKpis()
	{
		$kpis = array();

		$helper = new HelperKpi();
		$helper->id = 'box-disabled-categories-all';
		$helper->icon = 'icon-question-circle';
		$helper->color = 'color1';
		$helper->title = $this->l('Quick find any products category in e-shop (type into blue fields for search)');
		$helper->subtitle = $this->l('Preview opens new browser window/tab. Editing functions are linked to original category editing page');
		$helper->value = $this->l('Search in all products categories');
		$kpis[] = $helper->generate();

		$helper = new HelperKpiRow();
		$helper->kpis = $kpis;

		return $helper->generate();
	}
	

	public function initPageHeaderToolbar()
	{
		parent::initPageHeaderToolbar();

		if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
			$this->page_header_toolbar_btn['new-url'] = array(
				'href' => Context::getContext()->link->getAdminLink('AdminCategories') . '&add' . $this->table . 'root',
				'desc' => $this->l('Add new root category', null, null, false),
			);
		}

		$idCategory = Tools::isSubmit('id_category') ? '&id_parent=' . (int) Tools::getValue('id_category') : '';
		$this->page_header_toolbar_btn['new_category'] = array(
			'href' => Context::getContext()->link->getAdminLink('AdminCategories') . '&addcategory',
			'desc' => $this->l('Add new category', null, null, false),
			'icon' => 'process-icon-new'
		);

		$this->page_header_toolbar_btn['categories'] = array(
			'href' => Context::getContext()->link->getAdminLink('AdminCategories'),
			'desc' => $this->l('Categories tree', null, null, false),
			'icon' => 'process-icon-edit'
		);
	}
	
	
	public function getPreviewUrl($id_category)
	{
		$previewUrl = $this->context->link->getCategoryLink($id_category);
		return $previewUrl;
	}


	public function displayPreviewLink($token, $id, $name = null)
	{
		$tpl = $this->createTemplate('helpers/list/list_action_preview.tpl');
		if (!array_key_exists('Bad SQL query', static::$cache_lang)) {
			static::$cache_lang['Preview'] = $this->l('Preview', 'Helper');
		}

		$tpl->assign(
			[
				'href'   => $this->getPreviewUrl((int)$id),
				'action' => static::$cache_lang['Preview'],
			]
		);

		return $tpl->fetch();
	}
	

	public static function getDescriptionClean($description)
	{
		return Tools::getDescriptionClean($description);
	}
	
	
	public function init()
	{
		parent::init();

		// context->shop is set in the init() function, so we move the _category instanciation after that
		if (($idCategory = Tools::getvalue('id_category')) && $this->action != 'select_delete') {
			$this->_category = new Category($idCategory);
		} else {
			if (count(Category::getCategoriesWithoutParent()) > 1) {
				$this->_category = Category::getTopCategory();
			} else {
				$this->_category = new Category($this->context->shop->id_category);
			}
		}

		$this->_select = 'sa.position AS position, sa.id_category AS categ_id, ';
		$this->original_filter = $this->_filter .= ' AND `id_parent` > 1 '; // DWL: display and search all categories at once, not in tree hierarchy
		$this->_use_found_rows = false;

		if (Shop::getContext() == Shop::CONTEXT_SHOP) {
			$this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'category_shop` sa ON (a.`id_category` = sa.`id_category` AND sa.id_shop = ' . (int) $this->context->shop->id . ') ';
		} else {
			$this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'category_shop` sa ON (a.`id_category` = sa.`id_category` AND sa.id_shop = a.id_shop_default) ';
		}

		// we add restriction for shop
		if (Shop::getContext() == Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
			$this->_where = ' AND sa.`id_shop` = ' . (int) $this->context->shop->id;
		}

		// if we are not in a shop context, we remove the position column
		if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
			unset($this->fields_list['position']);
		}
		// shop restriction : if category is not available for current shop, we redirect to the list from default category
		if (
			Validate::isLoadedObject($this->_category) &&
			!$this->_category->isAssociatedToShop() &&
			Shop::getContext() == Shop::CONTEXT_SHOP
		) {
			$this->redirect_after = static::$currentIndex . '&id_category=' . (int) $this->context->shop->getCategory() . '&token=' . $this->token;
			$this->redirect();
		}
	}


	public function initToolbar()
	{
		parent::initToolbar();
		unset($this->toolbar_btn['new']);
	}
	
	
	public function renderForm()
	{
		if ($id_category = Tools::getValue('id_category')) {
			$this->redirect_after = Context::getContext()->link->getAdminLink('AdminCategories') . '&id_category=' . $id_category . '&updatecategory';
			$this->redirect();
		}
	}
	
	
}
