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
 * Class AdminCategoriesControllerCore
 *
 * @since 1.0.0
 */
class AdminCategoriesControllerCore extends AdminController
{
    // @codingStandardsIgnoreStart
    /** @var bool does the product have to be removed during the delete process */
    public $remove_products = true;
    /** @var bool does the product have to be disable during the delete process */
    public $disable_products = false;
    /**
     * @var object Category() instance for navigation
     */
    protected $_category = null;
    protected $position_identifier = 'id_category_to_move';
    protected $original_filter = '';
    // @codingStandardsIgnoreEnd

    /**
     * AdminCategoriesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'category';
        $this->className = 'Category';
        $this->lang = true;
        $this->deleted = false;
        $this->explicitSelect = true;
        $this->_defaultOrderBy = 'position';
        $this->allow_export = true;

        $this->context = Context::getContext();

        $this->fieldImageSettings = [
            'name' => 'image',
            'dir'  => 'c',
        ];

        $this->fields_list = [
            'id_category' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'        => [
                'title' => $this->l('Name'),
            ],
            'description' => [
                'title'    => $this->l('Description'),
                'callback' => 'getDescriptionClean',
                'orderby'  => false,
            ],
            'position'    => [
                'title'      => $this->l('Position'),
                'filter_key' => 'sa!position',
                'position'   => 'position',
                'align'      => 'center',
            ],
            'active'      => [
                'title'   => $this->l('Displayed'),
                'active'  => 'status',
                'type'    => 'bool',
                'class'   => 'fixed-width-xs',
                'align'   => 'center',
                'ajax'    => true,
                'orderby' => false,
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'icon'    => 'icon-trash',
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];
        $this->specificConfirmDelete = false;

        parent::__construct();
    }

    /**
     * @param $description
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function getDescriptionClean($description)
    {
        return Tools::getDescriptionClean($description);
    }

    /**
     * @since 1.0.0
     */
    public function init()
    {
        parent::init();

        // context->shop is set in the init() function, so we move the _category instanciation after that
        if (($idCategory = Tools::getvalue('id_category')) && $this->action != 'select_delete') {
            $this->_category = new Category($idCategory);
        } else {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $this->_category = new Category($this->context->shop->id_category);
            } elseif (count(Category::getCategoriesWithoutParent()) > 1 && Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && count(Shop::getShops(true, null, true)) != 1) {
                $this->_category = Category::getTopCategory();
            } else {
                $this->_category = new Category(Configuration::get('PS_HOME_CATEGORY'));
            }
        }

        $countCategoriesWithoutParent = count(Category::getCategoriesWithoutParent());

        if (Tools::isSubmit('id_category')) {
            $idParent = $this->_category->id;
        } elseif (!Shop::isFeatureActive() && $countCategoriesWithoutParent > 1) {
            $idParent = (int) Configuration::get('PS_ROOT_CATEGORY');
        } elseif (Shop::isFeatureActive() && $countCategoriesWithoutParent == 1) {
            $idParent = (int) Configuration::get('PS_HOME_CATEGORY');
        } elseif (Shop::isFeatureActive() && $countCategoriesWithoutParent > 1 && Shop::getContext() != Shop::CONTEXT_SHOP) {
            if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && count(Shop::getShops(true, null, true)) == 1) {
                $idParent = $this->context->shop->id_category;
            } else {
                $idParent = (int) Configuration::get('PS_ROOT_CATEGORY');
            }
        } else {
            $idParent = $this->context->shop->id_category;
        }
        $this->_select = 'sa.position position';
        $this->original_filter = $this->_filter .= ' AND `id_parent` = '.(int) $idParent.' ';
        $this->_use_found_rows = false;

        if (Shop::getContext() == Shop::CONTEXT_SHOP) {
            $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'category_shop` sa ON (a.`id_category` = sa.`id_category` AND sa.id_shop = '.(int) $this->context->shop->id.') ';
        } else {
            $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'category_shop` sa ON (a.`id_category` = sa.`id_category` AND sa.id_shop = a.id_shop_default) ';
        }

        // we add restriction for shop
        if (Shop::getContext() == Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
            $this->_where = ' AND sa.`id_shop` = '.(int) Context::getContext()->shop->id;
        }

        // if we are not in a shop context, we remove the position column
        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            unset($this->fields_list['position']);
        }
        // shop restriction : if category is not available for current shop, we redirect to the list from default category
        if (Validate::isLoadedObject($this->_category) && !$this->_category->isAssociatedToShop() && Shop::getContext() == Shop::CONTEXT_SHOP) {
            $this->redirect_after = static::$currentIndex.'&id_category='.(int) $this->context->shop->getCategory().'&token='.$this->token;
            $this->redirect();
        }
    }

    /**
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        if ($this->display != 'edit' && $this->display != 'add') {
            if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
                $this->page_header_toolbar_btn['new-url'] = [
                    'href' => static::$currentIndex.'&add'.$this->table.'root&token='.$this->token,
                    'desc' => $this->l('Add new root category', null, null, false),
                ];
            }

            $idCategory = (Tools::isSubmit('id_category')) ? '&id_parent='.(int) Tools::getValue('id_category') : '';
            $this->page_header_toolbar_btn['new_category'] = [
                'href' => static::$currentIndex.'&addcategory&token='.$this->token.$idCategory,
                'desc' => $this->l('Add new category', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }
    }

    /**
     * @since 1.0.0
     */
    public function initContent()
    {
        if ($this->action == 'select_delete') {
            $this->context->smarty->assign(
                [
                    'delete_form' => true,
                    'url_delete'  => htmlentities($_SERVER['REQUEST_URI']),
                    'boxes'       => $this->boxes,
                ]
            );
        }

        parent::initContent();
    }

    /**
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryUi('ui.widget');
        $this->addJqueryPlugin('tagify');
    }

    /**
     * @param int  $idLang
     * @param null $orderBy
     * @param null $orderWay
     * @param int  $start
     * @param null $limit
     * @param bool $idLangShop
     *
     * @since 1.0.0
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, Context::getContext()->shop->id);
        // Check each row to see if there are combinations and get the correct action in consequence

        $nbItems = count($this->_list);
        for ($i = 0; $i < $nbItems; $i++) {
            $item = &$this->_list[$i];
            $categoryTree = Category::getChildren((int) $item['id_category'], $this->context->language->id, false);
            if (!count($categoryTree)) {
                $this->addRowActionSkipList('view', [$item['id_category']]);
            }
        }
    }

    /**
     * @return false|string
     *
     * @since 1.0.0
     */
    public function renderView()
    {
        $this->initToolbar();

        return $this->renderList();
    }

    /**
     * @since 1.0.0
     */
    public function initToolbar()
    {
        if (empty($this->display)) {
            $this->toolbar_btn['new'] = [
                'href' => static::$currentIndex.'&add'.$this->table.'&token='.$this->token,
                'desc' => $this->l('Add New'),
            ];

            if ($this->can_import) {
                $this->toolbar_btn['import'] = [
                    'href' => $this->context->link->getAdminLink('AdminImport', true).'&import_type=categories',
                    'desc' => $this->l('Import'),
                ];
            }
        }
        // be able to edit the Home category
        if (count(Category::getCategoriesWithoutParent()) == 1 && !Tools::isSubmit('id_category')
            && ($this->display == 'view' || empty($this->display))
        ) {
            $this->toolbar_btn['edit'] = [
                'href' => static::$currentIndex.'&update'.$this->table.'&id_category='.(int) $this->_category->id.'&token='.$this->token,
                'desc' => $this->l('Edit'),
            ];
        }
        if (Tools::getValue('id_category') && !Tools::isSubmit('updatecategory')) {
            $this->toolbar_btn['edit'] = [
                'href' => static::$currentIndex.'&update'.$this->table.'&id_category='.(int) Tools::getValue('id_category').'&token='.$this->token,
                'desc' => $this->l('Edit'),
            ];
        }

        if ($this->display == 'view') {
            $this->toolbar_btn['new'] = [
                'href' => static::$currentIndex.'&add'.$this->table.'&id_parent='.(int) Tools::getValue('id_category').'&token='.$this->token,
                'desc' => $this->l('Add New'),
            ];
        }
        parent::initToolbar();
        if ($this->_category->id == (int) Configuration::get('PS_ROOT_CATEGORY') && isset($this->toolbar_btn['new'])) {
            unset($this->toolbar_btn['new']);
        }
        // after adding a category
        if (empty($this->display)) {
            $idCategory = (Tools::isSubmit('id_category')) ? '&id_parent='.(int) Tools::getValue('id_category') : '';
            $this->toolbar_btn['new'] = [
                'href' => static::$currentIndex.'&add'.$this->table.'&token='.$this->token.$idCategory,
                'desc' => $this->l('Add New'),
            ];

            if (Tools::isSubmit('id_category')) {
                $back = Tools::safeOutput(Tools::getValue('back', ''));
                if (empty($back)) {
                    $back = static::$currentIndex.'&token='.$this->token;
                }
                $this->toolbar_btn['back'] = [
                    'href' => $back,
                    'desc' => $this->l('Back to list'),
                ];
            }
        }
        if (!$this->lite_display && isset($this->toolbar_btn['back']['href']) && $this->_category->level_depth > 1
            && $this->_category->id_parent && $this->_category->id_parent != (int) Configuration::get('PS_ROOT_CATEGORY')
        ) {
            $this->toolbar_btn['back']['href'] .= '&id_category='.(int) $this->_category->id_parent;
        }
    }

    /**
     * @return false|string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        if (isset($this->_filter) && trim($this->_filter) == '') {
            $this->_filter = $this->original_filter;
        }

        $this->addRowAction('view');
        $this->addRowAction('add');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $countCategoriesWithoutParent = count(Category::getCategoriesWithoutParent());
        $categoriesTree = $this->_category->getParentsCategories();

        if (empty($categoriesTree)
            && ($this->_category->id != (int) Configuration::get('PS_ROOT_CATEGORY') || Tools::isSubmit('id_category'))
            && (Shop::getContext() == Shop::CONTEXT_SHOP && !Shop::isFeatureActive() && $countCategoriesWithoutParent > 1)
        ) {
            $categoriesTree = [['name' => $this->_category->name[$this->context->language->id]]];
        }

        $categoriesTree = array_reverse($categoriesTree);

        $this->tpl_list_vars['categories_tree'] = $categoriesTree;
        $this->tpl_list_vars['categories_tree_current_id'] = $this->_category->id;

        if (Tools::isSubmit('submitBulkdelete'.$this->table) || Tools::isSubmit('delete'.$this->table)) {
            $category = new Category(Tools::getValue('id_category'));
            if ($category->is_root_category) {
                $this->tpl_list_vars['need_delete_mode'] = false;
            } else {
                $this->tpl_list_vars['need_delete_mode'] = true;
            }
            $this->tpl_list_vars['delete_category'] = true;
            $this->tpl_list_vars['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
            $this->tpl_list_vars['POST'] = $_POST;
        }

        return parent::renderList();
    }

    /**
     * @since 1.0.0
     */
    public function initProcess()
    {
        if (Tools::isSubmit('add'.$this->table.'root')) {
            if ($this->tabAccess['add']) {
                $this->action = 'add'.$this->table.'root';
                $obj = $this->loadObject(true);
                if (Validate::isLoadedObject($obj)) {
                    $this->display = 'edit';
                } else {
                    $this->display = 'add';
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }

        parent::initProcess();

        if ($this->action == 'delete' || $this->action == 'bulkdelete') {
            if (Tools::getIsset('cancel')) {
                Tools::redirectAdmin(static::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminCategories'));
            } elseif (Tools::getValue('deleteMode') == 'link' || Tools::getValue('deleteMode') == 'linkanddisable' || Tools::getValue('deleteMode') == 'delete') {
                $this->delete_mode = Tools::getValue('deleteMode');
            } else {
                $this->action = 'select_delete';
            }
        }
    }

    /**
     * @return mixed
     *
     * @since 1.0.0
     */
    public function renderKpis()
    {
        $time = time();
        $kpis = [];

        /* The data generation is located in AdminStatsControllerCore */

        $helper = new HelperKpi();
        $helper->id = 'box-disabled-categories';
        $helper->icon = 'icon-off';
        $helper->color = 'color1';
        $helper->title = $this->l('Disabled Categories', null, null, false);
        if (ConfigurationKPI::get('DISABLED_CATEGORIES') !== false) {
            $helper->value = ConfigurationKPI::get('DISABLED_CATEGORIES');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=disabled_categories';
        $helper->refresh = (bool) (ConfigurationKPI::get('DISABLED_CATEGORIES_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-empty-categories';
        $helper->icon = 'icon-bookmark-empty';
        $helper->color = 'color2';
        $helper->href = $this->context->link->getAdminLink('AdminTracking');
        $helper->title = $this->l('Empty Categories', null, null, false);
        if (ConfigurationKPI::get('EMPTY_CATEGORIES') !== false) {
            $helper->value = ConfigurationKPI::get('EMPTY_CATEGORIES');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=empty_categories';
        $helper->refresh = (bool) (ConfigurationKPI::get('EMPTY_CATEGORIES_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-top-category';
        $helper->icon = 'icon-money';
        $helper->color = 'color3';
        $helper->title = $this->l('Top Category', null, null, false);
        $helper->subtitle = $this->l('30 days', null, null, false);
        if (ConfigurationKPI::get('TOP_CATEGORY', $this->context->employee->id_lang) !== false) {
            $helper->value = ConfigurationKPI::get('TOP_CATEGORY', $this->context->employee->id_lang);
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=top_category';
        $helper->refresh = (bool) (ConfigurationKPI::get('TOP_CATEGORY_EXPIRE', $this->context->employee->id_lang) < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-products-per-category';
        $helper->icon = 'icon-search';
        $helper->color = 'color4';
        $helper->title = $this->l('Average number of products per category', null, null, false);
        if (ConfigurationKPI::get('PRODUCTS_PER_CATEGORY') !== false) {
            $helper->value = ConfigurationKPI::get('PRODUCTS_PER_CATEGORY');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=products_per_category';
        $helper->refresh = (bool) (ConfigurationKPI::get('PRODUCTS_PER_CATEGORY_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpiRow();
        $helper->kpis = $kpis;

        return $helper->generate();
    }

    /**
     * @return string|void
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $this->initToolbar();

        /** @var Category $obj */
        $obj = $this->loadObject(true);
        $context = Context::getContext();
        $idShop = $context->shop->id;
        $selectedCategories = [(isset($obj->id_parent) && $obj->isParentCategoryAvailable($idShop)) ? (int) $obj->id_parent : (int) Tools::getValue('id_parent', Category::getRootCategory()->id)];
        $unidentified = new Group(Configuration::get('PS_UNIDENTIFIED_GROUP'));
        $guest = new Group(Configuration::get('PS_GUEST_GROUP'));
        $default = new Group(Configuration::get('PS_CUSTOMER_GROUP'));

        $unidentifiedGroupInformation = sprintf($this->l('%s - All people without a valid customer account.'), '<b>'.$unidentified->name[$this->context->language->id].'</b>');
        $guestGroupInformation = sprintf($this->l('%s - Customer who placed an order with the guest checkout.'), '<b>'.$guest->name[$this->context->language->id].'</b>');
        $defaultGroupInformation = sprintf($this->l('%s - All people who have created an account on this site.'), '<b>'.$default->name[$this->context->language->id].'</b>');

        if (!($obj = $this->loadObject(true))) {
            return;
        }

        $image = _PS_CAT_IMG_DIR_.$obj->id.'.'.$this->imageType;
        $imageUrl = ImageManager::thumbnail($image, $this->table.'_'.(int) $obj->id.'.'.$this->imageType, 350, $this->imageType, true, true);

        $imageSize = file_exists($image) ? filesize($image) / 1000 : false;
        $imagesTypes = ImageType::getImagesTypes('categories');
        $format = [];
        $thumb = $thumbUrl = '';
        $formattedCategory = ImageType::getFormatedName('category');
        $formattedMedium = ImageType::getFormatedName('medium');
        foreach ($imagesTypes as $k => $imageType) {
            if ($formattedCategory == $imageType['name']) {
                $format['category'] = $imageType;
            } elseif ($formattedMedium == $imageType['name']) {
                $format['medium'] = $imageType;
                $thumb = _PS_CAT_IMG_DIR_.$obj->id.'-'.$imageType['name'].'.'.$this->imageType;
                if (is_file($thumb)) {
                    $thumbUrl = ImageManager::thumbnail($thumb, $this->table.'_'.(int) $obj->id.'-thumb.'.$this->imageType, (int) $imageType['width'], $this->imageType, true, true);
                }
            }
        }

        if (!is_file($thumb)) {
            $thumb = $image;
            $thumbUrl = ImageManager::thumbnail($image, $this->table.'_'.(int) $obj->id.'-thumb.'.$this->imageType, 125, $this->imageType, true, true);
            ImageManager::resize(_PS_TMP_IMG_DIR_.$this->table.'_'.(int) $obj->id.'-thumb.'.$this->imageType, _PS_TMP_IMG_DIR_.$this->table.'_'.(int) $obj->id.'-thumb.'.$this->imageType, (int) $imageType['width'], (int) $imageType['height']);
        }

        $thumbSize = file_exists($thumb) ? filesize($thumb) / 1000 : false;

        $this->fields_form = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('Category'),
                'icon'  => 'icon-tags',
            ],
            'input'   => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                    'class'    => 'copy2friendlyUrl',
                    'hint'     => $this->l('Invalid characters:').' <>;=#{}',
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Displayed'),
                    'name'     => 'active',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Display products from subcategories'),
                    'name'     => 'display_from_sub',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'display_from_sub_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'display_from_sub_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'  => 'categories',
                    'label' => $this->l('Parent category'),
                    'name'  => 'id_parent',
                    'tree'  => [
                        'id'                  => 'categories-tree',
                        'selected_categories' => $selectedCategories,
                        'disabled_categories' => (!Tools::isSubmit('add'.$this->table) && !Tools::isSubmit('submitAdd'.$this->table)) ? [$this->_category->id] : null,
                        'root_category'       => $context->shop->getCategory(),
                    ],
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Description'),
                    'name'         => 'description',
                    'autoload_rte' => true,
                    'lang'         => true,
                    'hint'         => $this->l('Invalid characters:').' <>;=#{}',
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Category Cover Image'),
                    'name'          => 'image',
                    'display_image' => true,
                    'image'         => $imageUrl ? $imageUrl : false,
                    'size'          => $imageSize,
                    'delete_url'    => static::$currentIndex.'&'.$this->identifier.'='.$this->_category->id.'&token='.$this->token.'&deleteImage=1',
                    'hint'          => $this->l('This is the main image for your category, displayed in the category page. The category description will overlap this image and appear in its top-left corner.'),
                    'format'        => $format['category'],
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Category thumbnail'),
                    'name'          => 'thumb',
                    'display_image' => true,
                    'image'         => $thumbUrl ? $thumbUrl : false,
                    'size'          => $thumbSize,
                    'format'        => $format['medium'],
                ],
                [
                    'type'    => 'text',
                    'label'   => $this->l('Meta title'),
                    'name'    => 'meta_title',
                    'maxchar' => 70,
                    'lang'    => true,
                    'rows'    => 5,
                    'cols'    => 100,
                    'hint'    => $this->l('Forbidden characters:').' <>;=#{}',
                ],
                [
                    'type'    => 'textarea',
                    'label'   => $this->l('Meta description'),
                    'name'    => 'meta_description',
                    'maxchar' => 160,
                    'lang'    => true,
                    'rows'    => 5,
                    'cols'    => 100,
                    'hint'    => $this->l('Forbidden characters:').' <>;=#{}',
                ],
                [
                    'type'  => 'tags',
                    'label' => $this->l('Meta keywords'),
                    'name'  => 'meta_keywords',
                    'lang'  => true,
                    'hint'  => $this->l('To add "tags," click in the field, write something, and then press "Enter."').'&nbsp;'.$this->l('Forbidden characters:').' <>;=#{}',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Friendly URL'),
                    'name'     => 'link_rewrite',
                    'lang'     => true,
                    'required' => true,
                    'hint'     => $this->l('Only letters, numbers, underscore (_) and the minus (-) character are allowed.'),
                ],
                [
                    'type'              => 'group',
                    'label'             => $this->l('Group access'),
                    'name'              => 'groupBox',
                    'values'            => Group::getGroups(Context::getContext()->language->id),
                    'info_introduction' => $this->l('You now have three default customer groups.'),
                    'unidentified'      => $unidentifiedGroupInformation,
                    'guest'             => $guestGroupInformation,
                    'customer'          => $defaultGroupInformation,
                    'hint'              => $this->l('Mark all of the customer groups which you would like to have access to this category.'),
                ],
            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'name'  => 'submitAdd'.$this->table.($this->_category->is_root_category && !Tools::isSubmit('add'.$this->table) && !Tools::isSubmit('add'.$this->table.'root') ? '' : 'AndBackToParent'),
            ],
        ];

        $this->tpl_form_vars['shared_category'] = Validate::isLoadedObject($obj) && $obj->hasMultishopEntries();
        $this->tpl_form_vars['PS_ALLOW_ACCENTED_CHARS_URL'] = (int) Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
        $this->tpl_form_vars['displayBackOfficeCategory'] = Hook::exec('displayBackOfficeCategory');

        // Display this field only if multistore option is enabled
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && Tools::isSubmit('add'.$this->table.'root')) {
            $this->fields_form['input'][] = [
                'type'     => 'switch',
                'label'    => $this->l('Root Category'),
                'name'     => 'is_root_category',
                'required' => false,
                'is_bool'  => true,
                'values'   => [
                    [
                        'id'    => 'is_root_on',
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ],
                    [
                        'id'    => 'is_root_off',
                        'value' => 0,
                        'label' => $this->l('No'),
                    ],
                ],
            ];
            unset($this->fields_form['input'][2], $this->fields_form['input'][3]);
        }
        // Display this field only if multistore option is enabled AND there are several stores configured
        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        // remove category tree and radio button "is_root_category" if this category has the root category as parent category to avoid any conflict
        if ($this->_category->id_parent == (int) Configuration::get('PS_ROOT_CATEGORY') && Tools::isSubmit('updatecategory')) {
            foreach ($this->fields_form['input'] as $k => $input) {
                if (in_array($input['name'], ['id_parent', 'is_root_category'])) {
                    unset($this->fields_form['input'][$k]);
                }
            }
        }

        if (!($obj = $this->loadObject(true))) {
            return;
        }

        $image = ImageManager::thumbnail(_PS_CAT_IMG_DIR_.'/'.$obj->id.'.'.$this->imageType, $this->table.'_'.(int) $obj->id.'.'.$this->imageType, 350, $this->imageType, true);

        $this->fields_value = [
            'image' => $image ? $image : false,
            'size'  => $image ? filesize(_PS_CAT_IMG_DIR_.'/'.$obj->id.'.'.$this->imageType) / 1000 : false,
        ];

        // Added values of object Group
        $categoryGroupsIds = $obj->getGroups();

        $groups = Group::getGroups($this->context->language->id);
        // if empty $carrier_groups_ids : object creation : we set the default groups
        if (empty($categoryGroupsIds)) {
            $preselected = [Configuration::get('PS_UNIDENTIFIED_GROUP'), Configuration::get('PS_GUEST_GROUP'), Configuration::get('PS_CUSTOMER_GROUP')];
            $categoryGroupsIds = array_merge($categoryGroupsIds, $preselected);
        }
        foreach ($groups as $group) {
            $this->fields_value['groupBox_'.$group['id_group']] = Tools::getValue('groupBox_'.$group['id_group'], (in_array($group['id_group'], $categoryGroupsIds)));
        }

        $this->fields_value['is_root_category'] = (bool) Tools::isSubmit('add'.$this->table.'root');

        return parent::renderForm();
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (!in_array($this->display, ['edit', 'add'])) {
            $this->multishop_context_group = false;
        }
        if (Tools::isSubmit('forcedeleteImage') || (isset($_FILES['image']) && $_FILES['image']['size'] > 0) || Tools::getValue('deleteImage')) {
            $this->processForceDeleteImage();
            $this->processForceDeleteThumb();
            if (Tools::isSubmit('forcedeleteImage')) {
                Tools::redirectAdmin(static::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminCategories').'&conf=7');
            }
        }

        return parent::postProcess();
    }

    /**
     * @since 1.0.0
     */
    public function processForceDeleteImage()
    {
        $category = $this->loadObject(true);
        if (Validate::isLoadedObject($category)) {
            $category->deleteImage(true);
        }
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function processForceDeleteThumb()
    {
        $category = $this->loadObject(true);

        if (Validate::isLoadedObject($category)) {
            if (file_exists(_PS_TMP_IMG_DIR_.$this->table.'_'.$category->id.'-thumb.'.$this->imageType)
                && !unlink(_PS_TMP_IMG_DIR_.$this->table.'_'.$category->id.'-thumb.'.$this->imageType)
            ) {
                return false;
            }
            if (file_exists(_PS_CAT_IMG_DIR_.$category->id.'_thumb.'.$this->imageType)
                && !unlink(_PS_CAT_IMG_DIR_.$category->id.'_thumb.'.$this->imageType)
            ) {
                return false;
            }

            $imagesTypes = ImageType::getImagesTypes('categories');
            $formattedMedium = ImageType::getFormatedName('medium');
            foreach ($imagesTypes as $k => $imageType) {
                if ($formattedMedium == $imageType['name'] &&
                    file_exists(_PS_CAT_IMG_DIR_.$category->id.'-'.$imageType['name'].'.'.$this->imageType) &&
                    !unlink(_PS_CAT_IMG_DIR_.$category->id.'-'.$imageType['name'].'.'.$this->imageType)
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return false|ObjectModel
     *
     * @since 1.0.0
     */
    public function processAdd()
    {
        $idCategory = (int) Tools::getValue('id_category');
        $idParent = (int) Tools::getValue('id_parent');

        // if true, we are in a root category creation
        if (!$idParent) {
            $_POST['is_root_category'] = $_POST['level_depth'] = 1;
            $_POST['id_parent'] = $idParent = (int) Configuration::get('PS_ROOT_CATEGORY');
        }

        if ($idCategory) {
            if ($idCategory != $idParent) {
                if (!Category::checkBeforeMove($idCategory, $idParent)) {
                    $this->errors[] = Tools::displayError('The category cannot be moved here.');
                }
            } else {
                $this->errors[] = Tools::displayError('The category cannot be a parent of itself.');
            }
        }
        $object = parent::processAdd();

        //if we create a you root category you have to associate to a shop before to add sub categories in. So we redirect to AdminCategories listing
        if ($object && Tools::getValue('is_root_category')) {
            Tools::redirectAdmin(static::$currentIndex.'&id_category='.(int) Configuration::get('PS_ROOT_CATEGORY').'&token='.Tools::getAdminTokenLite('AdminCategories').'&conf=3');
        }

        return $object;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function processDelete()
    {
        if ($this->tabAccess['delete'] === '1') {
            /** @var Category $category */
            $category = $this->loadObject();
            if ($category->isRootCategoryForAShop()) {
                $this->errors[] = Tools::displayError('You cannot remove this category because one of your shops uses it as a root category.');
            } elseif (parent::processDelete()) {
                $this->setDeleteMode();
                $this->processFatherlessProducts((int) $category->id_parent);

                return true;
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to delete this.');
        }

        return false;
    }

    /**
     * @since 1.0.0
     */
    public function processPosition()
    {
        if ($this->tabAccess['edit'] !== '1') {
            $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        } elseif (!Validate::isLoadedObject($object = new Category((int) Tools::getValue($this->identifier, Tools::getValue('id_category_to_move', 1))))) {
            $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }
        if (!$object->updatePosition((int) Tools::getValue('way'), (int) Tools::getValue('position'))) {
            $this->errors[] = Tools::displayError('Failed to update the position.');
        } else {
            $object->regenerateEntireNtree();
            Tools::redirectAdmin(static::$currentIndex.'&'.$this->table.'Orderby=position&'.$this->table.'Orderway=asc&conf=5'.(($id_category = (int) Tools::getValue($this->identifier, Tools::getValue('id_category_parent', 1))) ? ('&'.$this->identifier.'='.$id_category) : '').'&token='.Tools::getAdminTokenLite('AdminCategories'));
        }
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessUpdatePositions()
    {
        $idCategoryToMove = (int) Tools::getValue('id_category_to_move');
        $idCategoryParent = (int) Tools::getValue('id_category_parent');
        $way = (int) Tools::getValue('way');
        $positions = Tools::getValue('category');
        $foundFirst = (bool) Tools::getValue('found_first');
        if (is_array($positions)) {
            foreach ($positions as $key => $value) {
                $pos = explode('_', $value);
                if ((isset($pos[1]) && isset($pos[2])) && ($pos[1] == $idCategoryParent && $pos[2] == $idCategoryToMove)) {
                    $position = $key;
                    break;
                }
            }
        }

        $category = new Category($idCategoryToMove);
        if (Validate::isLoadedObject($category)) {
            if (isset($position) && $category->updatePosition($way, $position)) {
                /* Position '0' was not found in given positions so try to reorder parent category*/
                if (!$foundFirst) {
                    $category->cleanPositions((int) $category->id_parent);
                }

                die(true);
            } else {
                die('{"hasError" : true, errors : "Cannot update categories position"}');
            }
        } else {
            die('{"hasError" : true, "errors" : "This category cannot be loaded"}');
        }
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessStatusCategory()
    {
        if (!$idCategory = (int) Tools::getValue('id_category')) {
            die(json_encode(['success' => false, 'error' => true, 'text' => $this->l('Failed to update the status')]));
        } else {
            $category = new Category((int) $idCategory);
            if (Validate::isLoadedObject($category)) {
                $category->active = $category->active == 1 ? 0 : 1;
                $category->save() ?
                    die(json_encode(['success' => true, 'text' => $this->l('The status has been updated successfully')])) :
                    die(json_encode(['success' => false, 'error' => true, 'text' => $this->l('Failed to update the status')]));
            }
        }
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    protected function processBulkDelete()
    {
        if ($this->tabAccess['delete'] === '1') {
            $catsIds = [];
            foreach (Tools::getValue($this->table.'Box') as $idCategory) {
                $category = new Category((int) $idCategory);
                if (!$category->isRootCategoryForAShop()) {
                    $catsIds[$category->id] = $category->id_parent;
                }
            }

            if (parent::processBulkDelete()) {
                $this->setDeleteMode();
                foreach ($catsIds as $id => $idParent) {
                    $this->processFatherlessProducts((int) $idParent);
                }

                return true;
            } else {
                return false;
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to delete this.');
        }
    }

    /**
     * @since 1.0.0
     */
    protected function setDeleteMode()
    {
        if ($this->delete_mode == 'link' || $this->delete_mode == 'linkanddisable') {
            $this->remove_products = false;
            if ($this->delete_mode == 'linkanddisable') {
                $this->disable_products = true;
            }
        } elseif ($this->delete_mode != 'delete') {
            $this->errors[] = Tools::displayError('Unknown delete mode:'.' '.$this->deleted);
        }
    }

    /**
     * @param int $idParent
     *
     * @since 1.0.0
     */
    public function processFatherlessProducts($idParent)
    {
        /* Delete or link products which were not in others categories */
        $fatherlessProducts = Db::getInstance()->executeS(
            '
			SELECT p.`id_product` FROM `'._DB_PREFIX_.'product` p
			'.Shop::addSqlAssociation('product', 'p').'
			WHERE NOT EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'category_product` cp WHERE cp.`id_product` = p.`id_product`)'
        );

        foreach ($fatherlessProducts as $idPoorProduct) {
            $poorProduct = new Product((int) $idPoorProduct['id_product']);
            if (Validate::isLoadedObject($poorProduct)) {
                if ($this->remove_products || $idParent == 0) {
                    $poorProduct->delete();
                } else {
                    if ($this->disable_products) {
                        $poorProduct->active = 0;
                    }
                    $poorProduct->id_category_default = (int) $idParent;
                    $poorProduct->addToCategories((int) $idParent);
                    $poorProduct->save();
                }
            }
        }
    }

    /**
     * @param int $id
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function postImage($id)
    {
        $ret = parent::postImage($id);
        if (($idCategory = (int) Tools::getValue('id_category')) && isset($_FILES) && count($_FILES)) {
            $name = 'image';
            if ($_FILES[$name]['name'] != null && file_exists(_PS_CAT_IMG_DIR_.$idCategory.'.'.$this->imageType)) {
                $imagesTypes = ImageType::getImagesTypes('categories');
                foreach ($imagesTypes as $k => $imageType) {
                    if (!ImageManager::resize(
                        _PS_CAT_IMG_DIR_.$idCategory.'.'.$this->imageType,
                        _PS_CAT_IMG_DIR_.$idCategory.'-'.stripslashes($imageType['name']).'.'.$this->imageType,
                        (int) $imageType['width'],
                        (int) $imageType['height']
                    )
                    ) {
                        $this->errors = Tools::displayError('An error occurred while uploading category image.');
                    }
                }
            }

            $name = 'thumb';
            if ($_FILES[$name]['name'] != null) {
                if (!isset($imagesTypes)) {
                    $imagesTypes = ImageType::getImagesTypes('categories');
                }
                $formattedMedium = ImageType::getFormatedName('medium');
                foreach ($imagesTypes as $k => $imageType) {
                    if ($formattedMedium == $imageType['name']) {
                        if ($error = ImageManager::validateUpload($_FILES[$name], Tools::getMaxUploadSize())) {
                            $this->errors[] = $error;
                        } elseif (!($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES[$name]['tmp_name'], $tmpName)) {
                            $ret = false;
                        } else {
                            if (!ImageManager::resize(
                                $tmpName,
                                _PS_CAT_IMG_DIR_.$idCategory.'-'.stripslashes($imageType['name']).'.'.$this->imageType,
                                (int) $imageType['width'],
                                (int) $imageType['height']
                            )
                            ) {
                                $this->errors = Tools::displayError('An error occurred while uploading thumbnail image.');
                            } elseif (($infos = getimagesize($tmpName)) && is_array($infos)) {
                                ImageManager::resize(
                                    $tmpName,
                                    _PS_CAT_IMG_DIR_.$idCategory.'_'.$name.'.'.$this->imageType,
                                    (int) $infos[0],
                                    (int) $infos[1]
                                );
                            }
                            if (count($this->errors)) {
                                $ret = false;
                            }
                            unlink($tmpName);
                            $ret = true;
                        }
                    }
                }
            }
        }

        return $ret;
    }
}
