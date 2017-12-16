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
 * Class AdminShopControllerCore
 *
 * @since 1.0.0
 */
class AdminShopControllerCore extends AdminController
{
    /**
     * AdminShopControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'shop';
        $this->className = 'Shop';
        $this->multishop_context = Shop::CONTEXT_ALL;

        $this->id_shop_group = (int) Tools::getValue('id_shop_group');

        /* if $_GET['id_shop'] is transmitted, virtual url can be loaded in config.php, so we wether transmit shop_id in herfs */
        if ($this->id_shop = (int) Tools::getValue('shop_id')) {
            $_GET['id_shop'] = $this->id_shop;
        }

        $this->list_skip_actions['delete'] = [(int) Configuration::get('PS_SHOP_DEFAULT')];
        $this->fields_list = [
            'id_shop'         => [
                'title' => $this->l('Shop ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'            => [
                'title'      => $this->l('Shop name'),
                'filter_key' => 'a!name',
                'width'      => 200,
            ],
            'shop_group_name' => [
                'title'      => $this->l('Shop group'),
                'width'      => 150,
                'filter_key' => 'gs!name',
            ],
            'category_name'   => [
                'title'      => $this->l('Root category'),
                'width'      => 150,
                'filter_key' => 'cl!name',
            ],
            'url'             => [
                'title'        => $this->l('Main URL for this shop'),
                'havingFilter' => 'url',
            ],
        ];

        parent::__construct();
    }

    /**
     * @param bool $disable
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function viewAccess($disable = false)
    {
        return Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        if (!$this->display && $this->id_shop_group) {
            if ($this->id_object) {
                $this->loadObject();
            }

            if (!$this->id_shop_group && $this->object && $this->object->id_shop_group) {
                $this->id_shop_group = $this->object->id_shop_group;
            }

            $this->page_header_toolbar_btn['edit'] = [
                'desc' => $this->l('Edit this shop group'),
                'href' => $this->context->link->getAdminLink('AdminShopGroup').'&updateshop_group&id_shop_group='.$this->id_shop_group,
            ];

            $this->page_header_toolbar_btn['new'] = [
                'desc' => $this->l('Add new shop'),
                'href' => $this->context->link->getAdminLink('AdminShop').'&add'.$this->table.'&id_shop_group='.$this->id_shop_group,
            ];
        }
    }

    /**
     * Initialize toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initToolbar()
    {
        parent::initToolbar();

        if ($this->display != 'edit' && $this->display != 'add') {
            if ($this->id_object) {
                $this->loadObject();
            }

            if (!$this->id_shop_group && $this->object && $this->object->id_shop_group) {
                $this->id_shop_group = $this->object->id_shop_group;
            }

            $this->toolbar_btn['new'] = [
                'desc' => $this->l('Add new shop'),
                'href' => $this->context->link->getAdminLink('AdminShop').'&add'.$this->table.'&id_shop_group='
                    .$this->id_shop_group,
            ];
        }
    }

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

        $this->addJqueryPlugin('cooki-plugin');
        $data = Shop::getTree();

        foreach ($data as $keyGroup => &$group) {
            foreach ($group['shops'] as $keyShop => &$shop) {
                $currentShop = new Shop($shop['id_shop']);
                $urls = $currentShop->getUrls();

                foreach ($urls as $keyUrl => &$url) {
                    $title = $url['domain'].$url['physical_uri'].$url['virtual_uri'];
                    if (strlen($title) > 23) {
                        $title = substr($title, 0, 23).'...';
                    }

                    $url['name'] = $title;
                    $shop['urls'][$url['id_shop_url']] = $url;
                }
            }
        }

        $shopsTree = new HelperTreeShops('shops-tree', $this->l('Multistore tree'));
        $shopsTree->setNodeFolderTemplate('shop_tree_node_folder.tpl')->setNodeItemTemplate('shop_tree_node_item.tpl')
            ->setHeaderTemplate('shop_tree_header.tpl')->setActions(
                [
                    new TreeToolbarLink(
                        'Collapse All',
                        '#',
                        '$(\'#'.$shopsTree->getId().'\').tree(\'collapseAll\'); return false;',
                        'icon-collapse-alt'
                    ),
                    new TreeToolbarLink(
                        'Expand All',
                        '#',
                        '$(\'#'.$shopsTree->getId().'\').tree(\'expandAll\'); return false;',
                        'icon-expand-alt'
                    ),
                ]
            )
            ->setAttribute('url_shop_group', $this->context->link->getAdminLink('AdminShopGroup'))
            ->setAttribute('url_shop', $this->context->link->getAdminLink('AdminShop'))
            ->setAttribute('url_shop_url', $this->context->link->getAdminLink('AdminShopUrl'))
            ->setData($data);
        $shopsTree = $shopsTree->render(null, false, false);

        if ($this->display == 'edit') {
            $this->toolbar_title[] = $this->object->name;
        } elseif (!$this->display && $this->id_shop_group) {
            $group = new ShopGroup($this->id_shop_group);
            $this->toolbar_title[] = $group->name;
        }

        $this->context->smarty->assign(
            [
                'toolbar_scroll' => 1,
                'toolbar_btn'    => $this->toolbar_btn,
                'title'          => $this->toolbar_title,
                'shops_tree'     => $shopsTree,
            ]
        );
    }

    /**
     * Render list
     *
     * @return false|string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_select = 'gs.name shop_group_name, cl.name category_name, CONCAT(\'http://\', su.domain, su.physical_uri, su.virtual_uri) AS url';
        $this->_join = '
			LEFT JOIN `'._DB_PREFIX_.'shop_group` gs
				ON (a.id_shop_group = gs.id_shop_group)
			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
				ON (a.id_category = cl.id_category AND cl.id_lang='.(int) $this->context->language->id.')
			LEFT JOIN '._DB_PREFIX_.'shop_url su
				ON a.id_shop = su.id_shop AND su.main = 1
		';
        $this->_group = 'GROUP BY a.id_shop';

        if ($idShopGroup = (int) Tools::getValue('id_shop_group')) {
            $this->_where = 'AND a.id_shop_group = '.$idShopGroup;
        }

        return parent::renderList();
    }

    /**
     * @return void
     *
     * @since 1.0.0
     */
    public function displayAjaxGetCategoriesFromRootCategory()
    {
        if (Tools::isSubmit('id_category')) {
            $selectedCat = [(int) Tools::getValue('id_category')];
            $children = Category::getChildren((int) Tools::getValue('id_category'), $this->context->language->id);
            foreach ($children as $child) {
                $selectedCat[] = $child['id_category'];
            }

            $helper = new HelperTreeCategories('categories-tree', null, (int) Tools::getValue('id_category'), null, false);
            $this->content = $helper->setSelectedCategories($selectedCat)->setUseSearch(true)->setUseCheckBox(true)
                ->render();
        }
        parent::displayAjax();
    }

    /**
     * Post processing
     *
     * @return bool|Shop
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('id_category_default')) {
            $_POST['id_category'] = Tools::getValue('id_category_default');
        }

        if (Tools::isSubmit('submitAddshopAndStay') || Tools::isSubmit('submitAddshop')) {
            $shopGroup = new ShopGroup((int) Tools::getValue('id_shop_group'));
            if ($shopGroup->shopNameExists(Tools::getValue('name'), (int) Tools::getValue('id_shop'))) {
                $this->errors[] = Tools::displayError('You cannot have two shops with the same name in the same group.');
            }
        }

        if (count($this->errors)) {
            return false;
        }

        /** @var Shop|bool $result */
        $result = parent::postProcess();

        if ($result != false && (Tools::isSubmit('submitAddshopAndStay') || Tools::isSubmit('submitAddshop')) && (int) $result->id_category != (int) Configuration::get('PS_HOME_CATEGORY', null, null, (int) $result->id)) {
            Configuration::updateValue('PS_HOME_CATEGORY', (int) $result->id_category, false, null, (int) $result->id);
        }

        if ($this->redirect_after) {
            $this->redirect_after .= '&id_shop_group='.$this->id_shop_group;
        }

        return $result;
    }

    /**
     * Process delete
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function processDelete()
    {
        if (!Validate::isLoadedObject($object = $this->loadObject())) {
            $this->errors[] = Tools::displayError('Unable to load this shop.');
        } elseif (!Shop::hasDependency($object->id)) {
            $result = Category::deleteCategoriesFromShop($object->id) && parent::processDelete();
            Tools::generateHtaccess();

            return $result;
        } else {
            $this->errors[] = Tools::displayError('You can\'t delete this shop (customer and/or order dependency).');
        }

        return false;
    }

    /**
     * Get list
     *
     * @param int         $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int         $start
     * @param int|null    $limit
     * @param int|bool    $idLangShop
     *
     * @since 1.0.0
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        if (Shop::getContext() == Shop::CONTEXT_GROUP) {
            $this->_where .= ' AND a.id_shop_group = '.(int) Shop::getContextShopGroupID();
        }

        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);
        $shopDeleteList = [];

        // don't allow to remove shop which have dependencies (customers / orders / ... )
        foreach ($this->_list as &$shop) {
            if (Shop::hasDependency($shop['id_shop'])) {
                $shopDeleteList[] = $shop['id_shop'];
            }
        }
        $this->context->smarty->assign('shops_having_dependencies', $shopDeleteList);
    }

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        /** @var Shop $obj */
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $this->fields_form = [
            'legend'     => [
                'title' => $this->l('Shop'),
                'icon'  => 'icon-shopping-cart',
            ],
            'identifier' => 'shop_id',
            'input'      => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Shop name'),
                    'desc'     => [
                        $this->l('This field does not refer to the shop name visible in the front office.'),
                        sprintf($this->l('Follow %sthis link%s to edit the shop name used on the front office.'), '<a href="'.$this->context->link->getAdminLink('AdminStores').'#store_fieldset_general">', '</a>'),
                    ],
                    'name'     => 'name',
                    'required' => true,
                ],
            ],
        ];

        $displayGroupList = true;
        if ($this->display == 'edit') {
            $group = new ShopGroup($obj->id_shop_group);
            if ($group->share_customer || $group->share_order || $group->share_stock) {
                $displayGroupList = false;
            }
        }

        if ($displayGroupList) {
            $options = [];
            foreach (ShopGroup::getShopGroups() as $group) {
                /** @var ShopGroup $group */
                if ($this->display == 'edit' && ($group->share_customer || $group->share_order || $group->share_stock) && ShopGroup::hasDependency($group->id)) {
                    continue;
                }

                $options[] = [
                    'id_shop_group' => $group->id,
                    'name'          => $group->name,
                ];
            }

            if ($this->display == 'add') {
                $groupDesc = $this->l('Warning: You won\'t be able to change the group of this shop if this shop belongs to a group with one of these options activated: Share Customers, Share Quantities or Share Orders.');
            } else {
                $groupDesc = $this->l('You can only move your shop to a shop group with all "share" options disabled -- or to a shop group with no customers/orders.');
            }

            $this->fields_form['input'][] = [
                'type'    => 'select',
                'label'   => $this->l('Shop group'),
                'desc'    => $groupDesc,
                'name'    => 'id_shop_group',
                'options' => [
                    'query' => $options,
                    'id'    => 'id_shop_group',
                    'name'  => 'name',
                ],
            ];
        } else {
            $this->fields_form['input'][] = [
                'type'    => 'hidden',
                'name'    => 'id_shop_group',
                'default' => $group->name,
            ];
            $this->fields_form['input'][] = [
                'type'  => 'textShopGroup',
                'label' => $this->l('Shop group'),
                'desc'  => $this->l('You can\'t edit the shop group because the current shop belongs to a group with the "share" option enabled.'),
                'name'  => 'id_shop_group',
                'value' => $group->name,
            ];
        }

        $categories = Category::getRootCategories($this->context->language->id);
        $this->fields_form['input'][] = [
            'type'    => 'select',
            'label'   => $this->l('Category root'),
            'desc'    => sprintf($this->l('This is the root category of the store that you\'ve created. To define a new root category for your store, %splease click here%s.'), '<a href="'.$this->context->link->getAdminLink('AdminCategories').'&addcategoryroot" target="_blank">', '</a>'),
            'name'    => 'id_category',
            'options' => [
                'query' => $categories,
                'id'    => 'id_category',
                'name'  => 'name',
            ],
        ];

        if (Tools::isSubmit('id_shop')) {
            $shop = new Shop((int) Tools::getValue('id_shop'));
            $idRoot = $shop->id_category;
        } else {
            $idRoot = $categories[0]['id_category'];
        }

        $idShop = (int) Tools::getValue('id_shop');
        static::$currentIndex = static::$currentIndex.'&id_shop_group='.(int) (Tools::getValue('id_shop_group') ?
                Tools::getValue('id_shop_group') : (isset($obj->id_shop_group) ? $obj->id_shop_group : Shop::getContextShopGroupID()));
        $shop = new Shop($idShop);
        $selectedCat = Shop::getCategories($idShop);

        if (empty($selectedCat)) {
            // get first category root and preselect all these children
            $rootCategories = Category::getRootCategories();
            $rootCategory = new Category($rootCategories[0]['id_category']);
            $children = $rootCategory->getAllChildren($this->context->language->id);
            $selectedCat[] = $rootCategories[0]['id_category'];

            foreach ($children as $child) {
                $selectedCat[] = $child->id;
            }
        }

        if (Shop::getContext() == Shop::CONTEXT_SHOP && Tools::isSubmit('id_shop')) {
            $rootCategory = new Category($shop->id_category);
        } else {
            $rootCategory = new Category($idRoot);
        }

        $this->fields_form['input'][] = [
            'type'  => 'categories',
            'name'  => 'categoryBox',
            'label' => $this->l('Associated categories'),
            'tree'  => [
                'id'                  => 'categories-tree',
                'selected_categories' => $selectedCat,
                'root_category'       => $rootCategory->id,
                'use_search'          => true,
                'use_checkbox'        => true,
            ],
            'desc'  => $this->l('By selecting associated categories, you are choosing to share the categories between shops. Once associated between shops, any alteration of this category will impact every shop.'),
        ];

        $themes = Theme::getThemes();
        if (!isset($obj->id_theme)) {
            foreach ($themes as $theme) {
                if (isset($theme->id)) {
                    $idTheme = $theme->id;
                    break;
                }
            }
        }

        $this->fields_form['input'][] = [
            'type'   => 'theme',
            'label'  => $this->l('Theme'),
            'name'   => 'theme',
            'values' => $themes,
        ];

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        if (Shop::getTotalShops() > 1 && $obj->id) {
            $disabled = ['active' => false];
        } else {
            $disabled = false;
        }

        $importData = [
            'carrier'            => $this->l('Carriers'),
            'cms'                => $this->l('CMS pages'),
            'contact'            => $this->l('Contact information'),
            'country'            => $this->l('Countries'),
            'currency'           => $this->l('Currencies'),
            'discount'           => $this->l('Discount prices'),
            'employee'           => $this->l('Employees'),
            'image'              => $this->l('Images'),
            'lang'               => $this->l('Languages'),
            'manufacturer'       => $this->l('Manufacturers'),
            'module'             => $this->l('Modules'),
            'hook_module'        => $this->l('Module hooks'),
            'meta_lang'          => $this->l('Meta information'),
            'product'            => $this->l('Products'),
            'product_attribute'  => $this->l('Product combinations'),
            'scene'              => $this->l('Scenes'),
            'stock_available'    => $this->l('Available quantities for sale'),
            'store'              => $this->l('Stores'),
            'warehouse'          => $this->l('Warehouses'),
            'webservice_account' => $this->l('Webservice accounts'),
            'attribute_group'    => $this->l('Attribute groups'),
            'feature'            => $this->l('Features'),
            'group'              => $this->l('Customer groups'),
            'tax_rules_group'    => $this->l('Tax rules groups'),
            'supplier'           => $this->l('Suppliers'),
            'referrer'           => $this->l('Referrers/affiliates'),
            'zone'               => $this->l('Zones'),
            'cart_rule'          => $this->l('Cart rules'),
        ];

        // Hook for duplication of shop data
        $modulesList = Hook::getHookModuleExecList('actionShopDataDuplication');
        if (is_array($modulesList) && count($modulesList) > 0) {
            foreach ($modulesList as $m) {
                $importData['Module'.ucfirst($m['module'])] = Module::getModuleName($m['module']);
            }
        }

        asort($importData);

        if (!$this->object->id) {
            $this->fields_import_form = [
                'radio'       => [
                    'type'  => 'radio',
                    'label' => $this->l('Import data'),
                    'name'  => 'useImportData',
                    'value' => 1,
                ],
                'select'      => [
                    'type'    => 'select',
                    'name'    => 'importFromShop',
                    'label'   => $this->l('Choose the source shop'),
                    'options' => [
                        'query' => Shop::getShops(false),
                        'name'  => 'name',
                    ],
                ],
                'allcheckbox' => [
                    'type'   => 'checkbox',
                    'label'  => $this->l('Choose data to import'),
                    'values' => $importData,
                ],
                'desc'        => $this->l('Use this option to associate data (products, modules, etc.) the same way for each selected shop.'),
            ];
        }

        $this->fields_value = [
            'id_shop_group'    => (Tools::getValue('id_shop_group') ? Tools::getValue('id_shop_group') :
                (isset($obj->id_shop_group)) ? $obj->id_shop_group : Shop::getContextShopGroupID()),
            'id_category'      => (Tools::getValue('id_category') ? Tools::getValue('id_category') :
                (isset($obj->id_category)) ? $obj->id_category : (int) Configuration::get('PS_HOME_CATEGORY')),
            'id_theme_checked' => (isset($obj->id_theme) ? $obj->id_theme : $idTheme),
        ];

        $idsCategory = [];
        $shops = Shop::getShops(false);
        foreach ($shops as $shop) {
            $idsCategory[$shop['id_shop']] = $shop['id_category'];
        }

        $this->tpl_form_vars = [
            'disabled'     => $disabled,
            'checked'      => (Tools::getValue('addshop') !== false) ? true : false,
            'defaultShop'  => (int) Configuration::get('PS_SHOP_DEFAULT'),
            'ids_category' => $idsCategory,
        ];
        if (isset($this->fields_import_form)) {
            $this->tpl_form_vars = array_merge($this->tpl_form_vars, ['form_import' => $this->fields_import_form]);
        }

        return parent::renderForm();
    }

    /**
     * Object creation
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function processAdd()
    {
        if (!Tools::getValue('categoryBox') || !in_array(Tools::getValue('id_category'), Tools::getValue('categoryBox'))) {
            $this->errors[] = $this->l('You need to select at least the root category.');
        }

        if (Tools::isSubmit('id_category_default')) {
            $_POST['id_category'] = (int) Tools::getValue('id_category_default');
        }

        /* Checking fields validity */
        $this->validateRules();

        if (!count($this->errors)) {
            /** @var Shop $object */
            $object = new $this->className();
            $this->copyFromPost($object, $this->table);
            $this->beforeAdd($object);
            if (!$object->add()) {
                $this->errors[] = Tools::displayError('An error occurred while creating an object.').
                    ' <b>'.$this->table.' ('.Db::getInstance()->getMsgError().')</b>';
            } /* voluntary do affectation here */
            elseif (($_POST[$this->identifier] = $object->id) && $this->postImage($object->id) && !count($this->errors) && $this->_redirect) {
                $parentId = (int) Tools::getValue('id_parent', 1);
                $this->afterAdd($object);
                $this->updateAssoShop($object->id);
                // Save and stay on same form
                if (Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
                    $this->redirect_after = static::$currentIndex.'&shop_id='.(int) $object->id.'&conf=3&update'.$this->table.'&token='.$this->token;
                }
                // Save and back to parent
                if (Tools::isSubmit('submitAdd'.$this->table.'AndBackToParent')) {
                    $this->redirect_after = static::$currentIndex.'&shop_id='.(int) $parentId.'&conf=3&token='.$this->token;
                }
                // Default behavior (save and back)
                if (empty($this->redirect_after)) {
                    $this->redirect_after = static::$currentIndex.($parentId ? '&shop_id='.$object->id : '').'&conf=3&token='.$this->token;
                }
            }
        }

        $this->errors = array_unique($this->errors);
        if (count($this->errors) > 0) {
            $this->display = 'add';

            return;
        }

        $object->associateSuperAdmins();

        $categories = Tools::getValue('categoryBox');
        array_unshift($categories, Configuration::get('PS_ROOT_CATEGORY'));
        Category::updateFromShop($categories, $object->id);
        if (Tools::getValue('useImportData') && ($importData = Tools::getValue('importData')) && is_array($importData) && isset($importData['product'])) {
            ini_set('max_execution_time', 7200); // like searchcron.php
            Search::indexation(true);
        }

        return $object;
    }

    /**
     * @param Shop $newShop
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function afterAdd($newShop)
    {
        $importData = Tools::getValue('importData', []);

        // The root category should be at least imported
        $newShop->copyShopData((int) Tools::getValue('importFromShop'), $importData);

        // copy default data
        if (!Tools::getValue('useImportData') || (is_array($importData) && !isset($importData['group']))) {
            $sql = 'INSERT INTO `'._DB_PREFIX_.'group_shop` (`id_shop`, `id_group`)
					VALUES
					('.(int) $newShop->id.', '.(int) Configuration::get('PS_UNIDENTIFIED_GROUP').'),
					('.(int) $newShop->id.', '.(int) Configuration::get('PS_GUEST_GROUP').'),
					('.(int) $newShop->id.', '.(int) Configuration::get('PS_CUSTOMER_GROUP').')
				';
            Db::getInstance()->execute($sql);
        }

        return parent::afterAdd($newShop);
    }

    /**
     * Display edit link
     *
     * @param null $token
     * @param int  $id
     * @param null $name
     *
     * @return string|void
     *
     * @since 1.0.0
     */
    public function displayEditLink($token = null, $id, $name = null)
    {
        if ($this->tabAccess['edit'] == 1) {
            $tpl = $this->createTemplate('helpers/list/list_action_edit.tpl');
            if (!array_key_exists('Edit', static::$cache_lang)) {
                static::$cache_lang['Edit'] = $this->l('Edit', 'Helper');
            }

            $tpl->assign(
                [
                    'href'   => $this->context->link->getAdminLink('AdminShop').'&shop_id='.(int) $id.'&update'.$this->table,
                    'action' => static::$cache_lang['Edit'],
                    'id'     => $id,
                ]
            );

            return $tpl->fetch();
        } else {
            return;
        }
    }

    /**
     * Initialize categories association
     *
     * @param null $idRoot
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function initCategoriesAssociation($idRoot = null)
    {
        if (is_null($idRoot)) {
            $idRoot = Configuration::get('PS_ROOT_CATEGORY');
        }
        $idShop = (int) Tools::getValue('id_shop');
        $shop = new Shop($idShop);
        $selectedCat = Shop::getCategories($idShop);
        if (empty($selectedCat)) {
            // get first category root and preselect all these children
            $rootCategories = Category::getRootCategories();
            $rootCategory = new Category($rootCategories[0]['id_category']);
            $children = $rootCategory->getAllChildren($this->context->language->id);
            $selectedCat[] = $rootCategories[0]['id_category'];

            foreach ($children as $child) {
                $selectedCat[] = $child->id;
            }
        }
        if (Shop::getContext() == Shop::CONTEXT_SHOP && Tools::isSubmit('id_shop')) {
            $rootCategory = new Category($shop->id_category);
        } else {
            $rootCategory = new Category($idRoot);
        }
        $rootCategory = ['id_category' => $rootCategory->id, 'name' => $rootCategory->name[$this->context->language->id]];

        $helper = new Helper();

        return $helper->renderCategoryTree($rootCategory, $selectedCat, 'categoryBox', false, true);
    }

    /**
     * Ajax process tree
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessTree()
    {
        $tree = [];
        $sql = 'SELECT g.id_shop_group, g.name as group_name, s.id_shop, s.name as shop_name, u.id_shop_url, u.domain, u.physical_uri, u.virtual_uri
				FROM '._DB_PREFIX_.'shop_group g
				LEFT JOIN  '._DB_PREFIX_.'shop s ON g.id_shop_group = s.id_shop_group
				LEFT JOIN  '._DB_PREFIX_.'shop_url u ON u.id_shop = s.id_shop
				ORDER BY g.name, s.name, u.domain';
        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        foreach ($results as $row) {
            $idShopGroup = $row['id_shop_group'];
            $idShop = $row['id_shop'];
            $idShopUrl = $row['id_shop_url'];

            // Group list
            if (!isset($tree[$idShopGroup])) {
                $tree[$idShopGroup] = [
                    'data'     => [
                        'title' => '<b>'.$this->l('Group').'</b> '.$row['group_name'],
                        'icon'  => 'themes/'.$this->context->employee->bo_theme.'/img/tree-multishop-groups.png',
                        'attr'  => [
                            'href'  => $this->context->link->getAdminLink('AdminShop').'&id_shop_group='.$idShopGroup,
                            'title' => sprintf($this->l('Click here to display the shops in the %s shop group', 'AdminShop', false, false), $row['group_name']),
                        ],
                    ],
                    'attr'     => [
                        'id' => 'tree-group-'.$idShopGroup,
                    ],
                    'children' => [],
                ];
            }

            // Shop list
            if (!$idShop) {
                continue;
            }

            if (!isset($tree[$idShopGroup]['children'][$idShop])) {
                $tree[$idShopGroup]['children'][$idShop] = [
                    'data'     => [
                        'title' => $row['shop_name'],
                        'icon'  => 'themes/'.$this->context->employee->bo_theme.'/img/tree-multishop-shop.png',
                        'attr'  => [
                            'href'  => $this->context->link->getAdminLink('AdminShopUrl').'&shop_id='.(int) $idShop,
                            'title' => sprintf($this->l('Click here to display the URLs of the %s shop', 'AdminShop', false, false), $row['shop_name']),
                        ],
                    ],
                    'attr'     => [
                        'id' => 'tree-shop-'.$idShop,
                    ],
                    'children' => [],
                ];
            }
            // Url list
            if (!$idShopUrl) {
                continue;
            }

            if (!isset($tree[$idShopGroup]['children'][$idShop]['children'][$idShopUrl])) {
                $url = $row['domain'].$row['physical_uri'].$row['virtual_uri'];
                if (strlen($url) > 23) {
                    $url = substr($url, 0, 23).'...';
                }

                $tree[$idShopGroup]['children'][$idShop]['children'][$idShopUrl] = [
                    'data' => [
                        'title' => $url,
                        'icon'  => 'themes/'.$this->context->employee->bo_theme.'/img/tree-multishop-url.png',
                        'attr'  => [
                            'href'  => $this->context->link->getAdminLink('AdminShopUrl').'&updateshop_url&id_shop_url='.$idShopUrl,
                            'title' => $row['domain'].$row['physical_uri'].$row['virtual_uri'],
                        ],
                    ],
                    'attr' => [
                        'id' => 'tree-url-'.$idShopUrl,
                    ],
                ];
            }
        }

        // jstree need to have children as array and not object, so we use sort to get clean keys
        // DO NOT REMOVE this code, even if it seems really strange ;)
        sort($tree);
        foreach ($tree as &$groups) {
            sort($groups['children']);
            foreach ($groups['children'] as &$shops) {
                sort($shops['children']);
            }
        }

        $tree = [
            [
                'data'     => [
                    'title' => '<b>'.$this->l('Shop groups list').'</b>',
                    'icon'  => 'themes/'.$this->context->employee->bo_theme.'/img/tree-multishop-root.png',
                    'attr'  => [
                        'href'  => $this->context->link->getAdminLink('AdminShopGroup'),
                        'title' => $this->l('Click here to display the list of shop groups', 'AdminShop', false, false),
                    ],
                ],
                'attr'     => [
                    'id' => 'tree-root',
                ],
                'state'    => 'open',
                'children' => $tree,
            ],
        ];

        $this->ajaxDie(json_encode($tree));
    }

    /**
     * @param Shop $newShop
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function afterUpdate($newShop)
    {
        $categories = Tools::getValue('categoryBox');

        if (!is_array($categories)) {
            $this->errors[] = $this->l('Please create some sub-categories for this root category.');

            return false;
        }

        array_unshift($categories, Configuration::get('PS_ROOT_CATEGORY'));

        if (!Category::updateFromShop($categories, $newShop->id)) {
            $this->errors[] = $this->l('You need to select at least the root category.');
        }
        if (Tools::getValue('useImportData') && ($importData = Tools::getValue('importData')) && is_array($importData)) {
            $newShop->copyShopData((int) Tools::getValue('importFromShop'), $importData);
        }

        if (Tools::isSubmit('submitAddshopAndStay') || Tools::isSubmit('submitAddshop')) {
            $this->redirect_after = static::$currentIndex.'&shop_id='.(int) $newShop->id.'&conf=4&token='.$this->token;
        }

        return parent::afterUpdate($newShop);
    }
}
