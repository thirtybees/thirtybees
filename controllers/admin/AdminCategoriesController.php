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
 * Class AdminCategoriesControllerCore
 *
 * @property Category|null $object
 */
class AdminCategoriesControllerCore extends AdminController
{
    const DELETE_MODE_DELETE = 'delete';
    const DELETE_MODE_LINK = 'link';
    const DELETE_MODE_LINK_AND_DISABLE = 'linkanddisable';

    /**
     * @var bool does the product have to be removed during the delete process
     */
    public $remove_products = true;

    /**
     * @var bool does the product have to be disabled during the delete process
     */
    public $disable_products = false;

    /**
     * @var Category instance for navigation
     */
    protected $_category = null;

    /**
     * @var string
     */
    protected $position_identifier = 'id_category_to_move';

    /**
     * @var string
     */
    protected $original_filter = '';

    /**
     * @var string
     */
    protected $delete_mode;

    /**
     * AdminCategoriesControllerCore constructor.
     *
     * @throws PrestaShopException
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
                'filter_key' => 'sa!active',
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
     * @param string $description
     *
     * @return string
     */
    public static function getDescriptionClean($description)
    {
        return substr(Tools::getDescriptionClean($description), 0, 100);
    }

    /**
     * @throws PrestaShopException
     */
    public function init()
    {
        parent::init();

        // context->shop is set in the init() function, so we move the _category instanciation after that
        if (($idCategory = Tools::getIntValue('id_category')) && $this->action != 'select_delete') {
            $this->_category = new Category($idCategory);
        } else {
            if (count(Category::getCategoriesWithoutParent()) > 1) {
                $this->_category = Category::getTopCategory();
            } else {
                $this->_category = new Category($this->context->shop->id_category);
            }
        }

        $this->_select = 'sa.position position';
        $this->original_filter = $this->_filter .= ' AND `id_parent` = '.(int) $this->_category->id .' ';
        $this->_use_found_rows = false;

        if (Shop::getContext() == Shop::CONTEXT_SHOP) {
            $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'category_shop` sa ON (a.`id_category` = sa.`id_category` AND sa.id_shop = '.(int) $this->context->shop->id.') ';
        } else {
            $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'category_shop` sa ON (a.`id_category` = sa.`id_category` AND sa.id_shop = a.id_shop_default) ';
        }

        // we add restriction for shop
        if (Shop::getContext() == Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
            $this->_where = ' AND sa.`id_shop` = '.(int) $this->context->shop->id;
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
     * @throws PrestaShopException
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

            $idCategory = (Tools::isSubmit('id_category')) ? '&id_parent='.Tools::getIntValue('id_category') : '';
            $this->page_header_toolbar_btn['new_category'] = [
                'href' => static::$currentIndex.'&addcategory&token='.$this->token.$idCategory,
                'desc' => $this->l('Add new category', null, null, false),
                'icon' => 'process-icon-new',
            ];
        } else {
            if ($this->display == 'edit') {
                // adding button for preview this category
                $this->page_header_toolbar_btn['preview'] = [
                    'short' => $this->l('Preview', null, null, false),
                    'href' => $this->context->link->getCategoryLink($this->_category->id),
                    'desc' => $this->l('Preview', null, null, false),
                    'target' => true,
                    'class' => 'previewUrl',
                ];
            }
        }
    }

    /**
     * @throws PrestaShopException
     * @throws SmartyException
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
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryUi('ui.widget');
        $this->addJqueryPlugin('tagify');
    }

    /**
     * @param int $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int $start
     * @param int|null $limit
     * @param bool $idLangShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $this->context->shop->id);
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderView()
    {
        $this->initToolbar();

        return $this->renderList();
    }

    /**
     * @throws PrestaShopException
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
                    'href' => $this->context->link->getAdminLink('AdminImport', true, ['import_type' => AdminImportController::ENTITY_TYPE_CATEGORIES]),
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
        if (Tools::getIntValue('id_category') && !Tools::isSubmit('updatecategory')) {
            $this->toolbar_btn['edit'] = [
                'href' => static::$currentIndex.'&update'.$this->table.'&id_category='.Tools::getIntValue('id_category').'&token='.$this->token,
                'desc' => $this->l('Edit'),
            ];
        }

        if ($this->display == 'view') {
            $this->toolbar_btn['new'] = [
                'href' => static::$currentIndex.'&add'.$this->table.'&id_parent='.Tools::getIntValue('id_category').'&token='.$this->token,
                'desc' => $this->l('Add New'),
            ];
        }
        parent::initToolbar();
        if ($this->_category->id == (int) Configuration::get('PS_ROOT_CATEGORY') && isset($this->toolbar_btn['new'])) {
            unset($this->toolbar_btn['new']);
        }
        // after adding a category
        if (empty($this->display)) {
            $idCategory = (Tools::isSubmit('id_category')) ? '&id_parent='.Tools::getIntValue('id_category') : '';
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
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
            $category = new Category(Tools::getIntValue('id_category'));
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
     * @throws PrestaShopException
     */
    public function initProcess()
    {
        if (Tools::isSubmit('add'.$this->table.'root')) {
            if ($this->hasAddPermission()) {
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
            $deleteMode = Tools::getValue('deleteMode');
            if (Tools::getIsset('cancel')) {
                Tools::redirectAdmin(static::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminCategories'));
            } elseif ($deleteMode === static::DELETE_MODE_LINK || $deleteMode === static::DELETE_MODE_LINK_AND_DISABLE || $deleteMode === static::DELETE_MODE_DELETE) {
                $this->delete_mode = $deleteMode;
            } else {
                $this->action = 'select_delete';
            }
        }
    }

    /**
     * @return HelperKpi[]
     *
     * @throws PrestaShopException
     */
    public function getKpis(): array
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
        $kpis[] = $helper;

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
        $kpis[] = $helper;

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
        $kpis[] = $helper;

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
        $kpis[] = $helper;

        return $kpis;
    }

    /**
     * @return string|void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $this->initToolbar();

        /** @var Category $obj */
        $obj = $this->loadObject(true);
        if (! $obj) {
            return;
        }

        $context = $this->context;
        $idShop = $context->shop->id;
        $selectedCategories = [(isset($obj->id_parent) && $obj->isParentCategoryAvailable($idShop)) ? (int) $obj->id_parent : Tools::getIntValue('id_parent', Category::getRootCategory()->id)];
        $unidentified = new Group(Configuration::get('PS_UNIDENTIFIED_GROUP'));
        $guest = new Group(Configuration::get('PS_GUEST_GROUP'));
        $default = new Group(Configuration::get('PS_CUSTOMER_GROUP'));

        $unidentifiedGroupInformation = sprintf($this->l('%s - All people without a valid customer account.'), '<b>'.$unidentified->name[$this->context->language->id].'</b>');
        $guestGroupInformation = sprintf($this->l('%s - Customer who placed an order with the guest checkout.'), '<b>'.$guest->name[$this->context->language->id].'</b>');
        $defaultGroupInformation = sprintf($this->l('%s - All people who have created an account on this site.'), '<b>'.$default->name[$this->context->language->id].'</b>');

        $image = _PS_CAT_IMG_DIR_.$obj->id.'.'.$this->imageType;
        $imageUrl = ImageManager::thumbnail($image, $this->table.'_'.(int) $obj->id.'.'.$this->imageType, 350, $this->imageType, true, true);

        $thumb = _PS_CAT_IMG_DIR_.'thumb/'.$obj->id.'.'.$this->imageType;
        $thumbUrl = ImageManager::thumbnail($thumb, $this->table.'_'.(int) $obj->id.'_thumb.'.$this->imageType, 125, $this->imageType, true, true);

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
                    'class'    => Validate::isLoadedObject($obj) ? '' : 'copy2friendlyUrl',
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
                    'type'         => 'textarea',
                    'label'        => $this->l('Additional description'),
                    'name'         => 'additional_description',
                    'autoload_rte' => true,
                    'lang'         => true,
                    'hint'         => $this->l('Invalid characters:').' <>;=#{}',
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Category Cover Image'),
                    'name'          => 'image',
                    'display_image' => true,
                    'image'         => $imageUrl ?: false,
                    'size'          => 350,
                    'delete_url'    => true,
                    'hint'          => $this->l('This is the main image for your category, displayed in the category page. The category description will overlap this image and appear in its top-left corner.'),
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Category thumbnail'),
                    'name'          => 'thumb',
                    'display_image' => true,
                    'image'         => $thumbUrl ?: false,
                    'size'          => 150,
                    'delete_url'    => true,
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
                    'values'            => Group::getGroups($this->context->language->id, true),
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
            'buttons' => [
                'save-and-stay' => [
                    'title' => $this->l('Save and Stay'),
                    'name' => 'submitAdd'.$this->table.($this->_category->is_root_category && !Tools::isSubmit('add'.$this->table) && !Tools::isSubmit('add'.$this->table.'root') ? '' : 'AndStay'),
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save',
                ],
            ],
        ];

        $this->tpl_form_vars['shared_category'] = Validate::isLoadedObject($obj) && $obj->hasMultishopEntries();
        $this->tpl_form_vars['PS_ALLOW_ACCENTED_CHARS_URL'] = (int) Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
        $this->tpl_form_vars['displayBackOfficeCategory'] = Hook::displayHook('displayBackOfficeCategory');

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

        /* $image = ImageManager::thumbnail(_PS_CAT_IMG_DIR_.'/'.$obj->id.'.'.$this->imageType, $this->table.'_'.(int) $obj->id.'.'.$this->imageType, 350, $this->imageType, true);

        $this->fields_value = [
            'image' => $image ? $image : false,
            'thumb' => $thumb ? $thumb : false,
            'size'  => $image ? filesize(_PS_CAT_IMG_DIR_.'/'.$obj->id.'.'.$this->imageType) / 1000 : false,
        ];*/

        // get selected groups
        $categoryGroupsIds = Validate::isLoadedObject($obj)
            ? $obj->getGroups()
            : [
                Configuration::get('PS_UNIDENTIFIED_GROUP'),
                Configuration::get('PS_GUEST_GROUP'),
                Configuration::get('PS_CUSTOMER_GROUP')
            ];

        $groups = Group::getGroups($this->context->language->id);
        foreach ($groups as $group) {
            $this->fields_value['groupBox_'.$group['id_group']] = Tools::getValue('groupBox_'.$group['id_group'], (in_array($group['id_group'], $categoryGroupsIds)));
        }

        $this->fields_value['is_root_category'] = (bool) Tools::isSubmit('add'.$this->table.'root');

        return parent::renderForm();
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (!in_array($this->display, ['edit', 'add'])) {
            $this->multishop_context_group = false;
        }

        return parent::postProcess();
    }

    /**
     * @return false|ObjectModel
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processAdd()
    {
        $idCategory = Tools::getIntValue('id_category');
        $idParent = Tools::getIntValue('id_parent');

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
     * @throws PrestaShopException
     */
    public function processDelete()
    {
        if ($this->hasDeletePermission()) {
            /** @var Category $category */
            $category = $this->loadObject();
            if (Validate::isLoadedObject($category)) {
                if ($category->isRootCategoryForAShop()) {
                    $this->errors[] = Tools::displayError('You cannot remove this category because one of your shops uses it as a root category.');
                } else {
                    $categoryProducts = $category->getAssociatedProducts();
                    if (parent::processDelete()) {
                        $this->setDeleteMode();
                        $this->processFatherlessProducts((int)$category->id_parent, $categoryProducts);
                        return true;
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('Category not found');
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to delete this.');
        }

        return false;
    }

    /**
     * @throws PrestaShopException
     */
    public function processPosition()
    {
        if (!$this->hasEditPermission()) {
            $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        } elseif (!Validate::isLoadedObject($object = new Category(Tools::getIntValue($this->identifier, Tools::getIntValue('id_category_to_move', 1))))) {
            $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }
        if (!$object->updatePosition(Tools::getIntValue('way'), Tools::getIntValue('position'))) {
            $this->errors[] = Tools::displayError('Failed to update the position.');
        } else {
            $object->regenerateEntireNtree();
            Tools::redirectAdmin(static::$currentIndex.'&'.$this->table.'Orderby=position&'.$this->table.'Orderway=asc&conf=5'.(($id_category = Tools::getIntValue($this->identifier, Tools::getIntValue('id_category_parent', 1))) ? ('&'.$this->identifier.'='.$id_category) : '').'&token='.Tools::getAdminTokenLite('AdminCategories'));
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdatePositions()
    {
        $idCategoryToMove = Tools::getIntValue('id_category_to_move');
        $idCategoryParent = Tools::getIntValue('id_category_parent');
        $way = Tools::getIntValue('way');
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

                $this->ajaxDie(true);
            } else {
                $this->ajaxDie('{"hasError" : true, errors : "Cannot update categories position"}');
            }
        } else {
            $this->ajaxDie('{"hasError" : true, "errors" : "This category cannot be loaded"}');
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessStatusCategory()
    {
        if (!$idCategory = Tools::getIntValue('id_category')) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => true, 'text' => $this->l('Failed to update the status')]));
        } else {
            $category = new Category((int) $idCategory);
            if (Validate::isLoadedObject($category)) {
                $category->active = $category->active == 1 ? 0 : 1;
                $category->save() ?
                    $this->ajaxDie(json_encode(['success' => true, 'text' => $this->l('The status has been updated successfully')])) :
                    $this->ajaxDie(json_encode(['success' => false, 'error' => true, 'text' => $this->l('Failed to update the status')]));
            }
        }
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processBulkDelete()
    {
        if ($this->hasDeletePermission()) {
            $categories = [];
            foreach (Tools::getArrayValue($this->table.'Box') as $idCategory) {
                $category = new Category((int) $idCategory);
                if (!$category->isRootCategoryForAShop()) {
                    $categories[$category->id] = [
                        'parentCategoryId' => (int)$category->id_parent,
                        'products' => $category->getAssociatedProducts()
                    ];
                }
            }

            if (parent::processBulkDelete()) {
                $this->setDeleteMode();
                foreach ($categories as $info) {
                    $parentCategoryId = (int)$info['parentCategoryId'];
                    $products = $info['products'];
                    $this->processFatherlessProducts($parentCategoryId, $products);
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
     * @return void
     */
    protected function setDeleteMode()
    {
        if ($this->delete_mode === static::DELETE_MODE_LINK || $this->delete_mode === static::DELETE_MODE_LINK_AND_DISABLE) {
            $this->remove_products = false;
            if ($this->delete_mode === static::DELETE_MODE_LINK_AND_DISABLE) {
                $this->disable_products = true;
            }
        } elseif ($this->delete_mode !== static::DELETE_MODE_DELETE) {
            $this->errors[] = sprintf(Tools::displayError('Unknown delete mode: %s'), $this->delete_mode);
        }
    }

    /**
     * Process products that were left category-less after category deletion.
     *
     * Depending on delete option, products can be either removed, or associated to different category
     *
     * Since 1.4.0 this method accepts new parameter $products. This is an array of product IDs that were
     * directly affected by category deletion. Only these products will be processed. Products that were
     * already without category before this category was deleted will not be processed.
     *
     * @param int $idParent id of new parent category (if re-assigning)
     * @param array $products list of products affected by category deletion
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processFatherlessProducts($idParent, array $products)
    {
        if (! $products) {
            return;
        }

        $idParent = (int)$idParent;
        $products = array_map('intval', $products);

        /* Delete or link products which were not in others categories */
        $sql = (
            'SELECT p.`id_product` FROM `'._DB_PREFIX_.'product` p'.Shop::addSqlAssociation('product', 'p').' '.
            'WHERE NOT EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'category_product` cp WHERE cp.`id_product` = p.`id_product`)'
        );

        // small optimization - if we have only a few products, include them all in sql query
        if (count($products) < 50) {
            $sql .= ' AND p.id_product in ('.implode(', ', $products).')';
            $skipCheck = true;
        } else {
            $skipCheck = false;
        }

        $fatherlessProducts = Db::readOnly()->getArray($sql);
        foreach ($fatherlessProducts as $product) {
            $productId = (int)$product['id_product'];
            if ($skipCheck || in_array($productId, $products)) {
                $poorProduct = new Product($productId);
                if (Validate::isLoadedObject($poorProduct)) {
                    if ($this->remove_products || $idParent == 0) {
                        $poorProduct->delete();
                    } else {
                        if ($this->disable_products) {
                            $poorProduct->active = 0;
                        }
                        $poorProduct->id_category_default = $idParent;
                        $poorProduct->addToCategories($idParent);
                        $poorProduct->save();
                    }
                }
            }
        }
    }

    /**
     * Copy data values from $_POST to object
     *
     * @param Category $object
     * @param string $table
     * @throws PrestaShopException
     */
    protected function copyFromPost(&$object, $table)
    {
        parent::copyFromPost($object, $table);

        // assign groups to category objects
        if (is_null($object->groupBox)) {
            $object->groupBox = [];
        } else {
            $object->groupBox = array_filter(array_unique(array_map('intval', $object->groupBox)));
        }
    }


}
