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
 * Class AdminProductsControllerCore
 *
 * @property Product|null $object
 */
class AdminProductsControllerCore extends AdminController
{

    /**
     * Tab permissions level NONE = tab is hidden / employee can't see it
     */
    const TAB_PERMISSION_NONE = 'none';

    /**
     * Tab permission level FULL = employee can work with tab without any restrictions
     */
    const TAB_PERMISSION_FULL = 'full';

    const QUANTITY_METHOD_MANUAL = 0;
    const QUANTITY_METHOD_ASM = 1;
    const QUANTITY_METHOD_DYNAMIC_PACK = 2;


    /** @var int Max image size for upload
     * As of 1.5 it is recommended to not set a limit to max image size
     */
    protected $max_file_size = null;

    /**
     * @var int|null
     */
    protected $max_image_size = null;

    /**
     * @var Category
     */
    protected $_category;
    /**
     * @var string name of the tab to display
     */
    protected $tab_display;

    /**
     * @var string
     */
    protected $tab_display_module;

    /**
     * The order in the array decides the order in the list of tab. If an element's value is a number, it will be preloaded.
     * The tabs are preloaded from the smallest to the highest number.
     *
     * @var array Product tabs.
     */
    protected $available_tabs = [];

    /** @var string $default_tab */
    protected $default_tab = 'Informations';

    /** @var array $available_tabs_lang */
    protected $available_tabs_lang = [];

    /** @var string $position_identifier */
    protected $position_identifier = 'id_product';

    /** @var array $submitted_tabs */
    protected $submitted_tabs;

    /** @var int $id_current_category */
    protected $id_current_category;

    /** @var string */
    protected $tpl_form;

    /** @var bool */
    protected $product_exists_in_shop = false;

    /**
     * AdminProductsControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'product';
        $this->className = 'Product';
        $this->lang = true;
        $this->explicitSelect = true;
        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'icon'    => 'icon-trash',
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];
        $productId = Tools::getIntValue('id_product');

        if (! $productId) {
            $this->multishop_context_group = false;
        }

        if (! $this->allowEditPerStore() && !Tools::isSubmit('statusproduct')) {
            if ($productId || Tools::isSubmit('addproduct')) {
                $this->multishop_context = false;
                $defaultShopId = (int)Configuration::get('PS_SHOP_DEFAULT');
                $context = Context::getContext();
                if ((int)$context->shop->id !== $defaultShopId) {
                    $context->shop = new Shop($defaultShopId);
                }
            }
        }

        parent::__construct();

        $this->imageType = 'jpg';
        $this->_defaultOrderBy = 'position';
        $this->max_file_size = (int) (Configuration::get('PS_LIMIT_UPLOAD_FILE_VALUE') * 1000000);
        $this->max_image_size = (int) Configuration::get('PS_PRODUCT_PICTURE_MAX_SIZE');
        $this->allow_export = true;

        // @since 1.5 : translations for tabs
        $this->available_tabs_lang = [
            'Informations'   => $this->l('Information'),
            'Pack'           => $this->l('Pack'),
            'VirtualProduct' => $this->l('Virtual Product'),
            'Prices'         => $this->l('Prices'),
            'Seo'            => $this->l('SEO'),
            'Images'         => $this->l('Images'),
            'Associations'   => $this->l('Associations'),
            'Shipping'       => $this->l('Shipping'),
            'Combinations'   => $this->l('Combinations'),
            'Features'       => $this->l('Features'),
            'Customization'  => $this->l('Customization'),
            'Attachments'    => $this->l('Attachments'),
            'Quantities'     => $this->l('Quantities'),
            'Suppliers'      => $this->l('Suppliers'),
            'Warehouses'     => $this->l('Warehouses'),
        ];

        $this->available_tabs = ['Quantities' => 6, 'Warehouses' => 14];
        if ($this->context->shop->getContext() != Shop::CONTEXT_GROUP) {
            $this->available_tabs = array_merge(
                $this->available_tabs,
                [
                    'Informations'   => 0,
                    'Pack'           => 7,
                    'VirtualProduct' => 8,
                    'Prices'         => 1,
                    'Seo'            => 2,
                    'Associations'   => 3,
                    'Images'         => 9,
                    'Shipping'       => 4,
                    'Combinations'   => 5,
                    'Features'       => 10,
                    'Customization'  => 11,
                    'Attachments'    => 12,
                    'Suppliers'      => 13,
                ]
            );
        }

        // Sort the tabs that need to be preloaded by their priority number
        asort($this->available_tabs, SORT_NUMERIC);

        /* Adding tab if modules are hooked */
        $modulesList = Hook::getHookModuleExecList('displayAdminProductsExtra');
        if (is_array($modulesList) && count($modulesList) > 0) {
            foreach ($modulesList as $m) {
                $this->available_tabs['Module'.ucfirst($m['module'])] = 23;
                $this->available_tabs_lang['Module'.ucfirst($m['module'])] = Module::getModuleName($m['module']);
            }
        }

        if (Tools::getValue('reset_filter_category')) {
            $this->context->cookie->id_category_products_filter = false;
        }
        if (Shop::isFeatureActive() && $this->context->cookie->id_category_products_filter) {
            $category = new Category((int) $this->context->cookie->id_category_products_filter);
            if (!$category->inShop()) {
                $this->context->cookie->id_category_products_filter = false;
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminProducts'));
            }
        }
        /* Join categories table */
        if ($idCategory = Tools::getIntValue('productFilter_cl!name')) {
            $this->_category = new Category((int) $idCategory);
            $_POST['productFilter_cl!name'] = $this->_category->name[$this->context->language->id];
        } else {
            if ($idCategory = Tools::getIntValue('id_category')) {
                $this->id_current_category = $idCategory;
                $this->context->cookie->id_category_products_filter = $idCategory;
            } elseif ($idCategory = $this->context->cookie->id_category_products_filter) {
                $this->id_current_category = $idCategory;
            }
            if ($this->id_current_category) {
                $this->_category = new Category((int) $this->id_current_category);
            } else {
                $this->_category = new Category();
            }
        }

        $joinCategory = false;
        if (Validate::isLoadedObject($this->_category) && empty($this->_filter)) {
            $joinCategory = true;
        }

        $this->_join .= '
		LEFT JOIN `'._DB_PREFIX_.'stock_available` sav ON (sav.`id_product` = a.`id_product` AND sav.`id_product_attribute` = 0
		'.StockAvailable::addSqlShopRestriction(null, null, 'sav').') ';

        $alias = 'sa';
        $aliasImage = 'image_shop';

        $idShop = Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP ? (int) $this->context->shop->id : 'a.id_shop_default';
        $this->_join .= ' JOIN `'._DB_PREFIX_.'product_shop` sa ON (a.`id_product` = sa.`id_product` AND sa.id_shop = '.$idShop.')
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON ('.$alias.'.`id_category_default` = cl.`id_category` AND b.`id_lang` = cl.`id_lang` AND cl.id_shop = '.$idShop.')
				LEFT JOIN `'._DB_PREFIX_.'shop` shop ON (shop.id_shop = '.$idShop.')
				LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop ON (image_shop.`id_product` = a.`id_product` AND image_shop.`cover` = 1 AND image_shop.id_shop = '.$idShop.')
				LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_image` = image_shop.`id_image`)
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = a.`id_manufacturer`)
				LEFT JOIN `'._DB_PREFIX_.'supplier` s ON (s.`id_supplier` = a.`id_supplier`)
				LEFT JOIN `'._DB_PREFIX_.'product_download` pd ON (pd.`id_product` = a.`id_product` AND pd.`active` = 1)';

        $this->_select .= 'shop.`name` AS `shopname`, a.`id_shop_default`, ';
        $this->_select .= 'm.`name` AS `name_manufacturer`, ';
        $this->_select .= 's.`name` AS `name_supplier`, ';
        $this->_select .= $aliasImage.'.`id_image` AS `id_image`, cl.`name` AS `name_category`, '.$alias.'.`price`, 0 AS `price_final`, a.`is_virtual`, pd.`nb_downloadable`, sav.`quantity` AS `sav_quantity`, '.$alias.'.`active`, IF(sav.`quantity`<=0, 1, 0) AS `badge_danger`';

        if ($joinCategory) {
            $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_product` = a.`id_product` AND cp.`id_category` = '.(int) $this->_category->id.') ';
            $this->_select .= ' , cp.`position`, ';
        }

        // filter by feature or feature value
        $featureId = Tools::getIntValue('id_feature');
        $featureValueId = Tools::getIntValue('id_feature_value');
        if ($featureId) {
            $this->_where .= ' AND EXISTS(SELECT 1 FROM `'._DB_PREFIX_.'feature_product` fp WHERE fp.id_product = a.id_product AND fp.id_feature = ' . $featureId;
            if ($featureValueId) {
                $this->_where.= ' AND fp.id_feature_value = ' . $featureValueId;
            }
            $this->_where .= ')';
        }

        // filter by attribute group or specific attribute
        $attributeGroupId = Tools::getIntValue('id_attribute_group');
        $attributeId = Tools::getIntValue('id_attribute');
        if ($attributeGroupId) {
            $subQuery = (new DbQuery())
                ->select('1')
                ->from('product_attribute_combination', 'pac')
                ->innerJoin('product_attribute', 'pa', '(pac.id_product_attribute = pa.id_product_attribute)')
                ->innerJoin('attribute', 'attr', '(pac.id_attribute = attr.id_attribute)')
                ->where('pa.id_product = a.id_product')
                ->where('attr.id_attribute_group = ' . $attributeGroupId);
            if ($attributeId) {
                $subQuery->where('attr.id_attribute = ' . $attributeId);
            }
            $this->_where .= " AND EXISTS($subQuery)";
        }


        $this->_use_found_rows = false;
        $this->_group = '';

        $this->fields_list = [];
        $this->fields_list['id_product'] = [
            'title' => $this->l('ID'),
            'align' => 'center',
            'class' => 'fixed-width-xs',
            'type'  => 'int',
        ];
        $this->fields_list['image'] = [
            'title'   => $this->l('Image'),
            'align'   => 'center',
            'image'   => 'p',
            'orderby' => false,
            'filter'  => false,
            'search'  => false,
        ];
        $this->fields_list['name'] = [
            'title'      => $this->l('Name'),
            'filter_key' => 'b!name',
        ];
        $this->fields_list['reference'] = [
            'title' => $this->l('Reference'),
            'align' => 'left',
        ];

        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            $this->fields_list['shopname'] = [
                'title'      => $this->l('Default shop'),
                'filter_key' => 'shop!name',
            ];
        } else {
            $this->fields_list['name_category'] = [
                'title'      => $this->l('Category'),
                'filter_key' => 'cl!name',
            ];
        }
        $this->fields_list['name_manufacturer'] = [
            'title'      => $this->l('Manufacturer'),
            'filter_key' => 'm!name',
            'orderby'    => true,
        ];
        $this->fields_list['name_supplier'] = [
            'title'      => $this->l('Supplier'),
            'filter_key' => 's!name',
            'orderby'    => true,
        ];
        $this->fields_list['price'] = [
            'title'      => $this->l('Base price'),
            'type'       => 'price',
            'align'      => 'text-right',
            'filter_key' => 'a!price',
        ];
        $this->fields_list['price_final'] = [
            'title'        => $this->l('Final price'),
            'type'         => 'price',
            'align'        => 'text-right',
            'havingFilter' => true,
            'orderby'      => false,
            'search'       => false,
        ];

        if (Configuration::get('PS_STOCK_MANAGEMENT')) {
            $this->fields_list['sav_quantity'] = [
                'title'        => $this->l('Quantity'),
                'type'         => 'int',
                'align'        => 'text-right',
                'filter_key'   => 'sav!quantity',
                'orderby'      => true,
                'badge_danger' => true,
                'callback' => 'callbackRenderStockLink'
                //'hint' => $this->l('This is the quantity available in the current shop/group.'),
            ];
        }

        $this->fields_list['active'] = [
            'title'      => $this->l('Status'),
            'active'     => 'status',
            'filter_key' => $alias.'!active',
            'align'      => 'text-center',
            'type'       => 'bool',
            'class'      => 'fixed-width-sm',
            'orderby'    => false,
        ];

        if ($joinCategory && (int) $this->id_current_category) {
            $this->fields_list['position'] = [
                'title'      => $this->l('Position'),
                'filter_key' => 'cp!position',
                'align'      => 'center',
                'position'   => 'position',
            ];
        }
    }

    /**
     * @param string $echo
     * @param array $tr
     *
     * @return string
     */
    public static function getQuantities($echo, $tr)
    {
        if ((int) $tr['is_virtual'] == 1 && $tr['nb_downloadable'] == 0) {
            return '&infin;';
        } else {
            return $echo;
        }
    }

    /**
     * Build a categories tree
     *
     * @param int $idObj
     * @param array $indexedCategories Array with categories where product is indexed (in order to check checkbox)
     * @param array $categories Categories to list
     * @param array $current
     * @param int|null $idCategory Current category ID
     * @param int|null $idCategoryDefault
     * @param array $hasSuite
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function recurseCategoryForInclude($idObj, $indexedCategories, $categories, $current, $idCategory = null, $idCategoryDefault = null, $hasSuite = [])
    {
        global $done;
        static $irow;
        $content = '';

        if (!$idCategory) {
            $idCategory = (int) Configuration::get('PS_ROOT_CATEGORY');
        }

        if (!isset($done[$current['infos']['id_parent']])) {
            $done[$current['infos']['id_parent']] = 0;
        }
        $done[$current['infos']['id_parent']] += 1;

        $todo = count($categories[$current['infos']['id_parent']]);
        $doneC = $done[$current['infos']['id_parent']];

        $level = $current['infos']['level_depth'] + 1;

        $content .= '
		<tr class="'.($irow++ % 2 ? 'alt_row' : '').'">
			<td>
				<input type="checkbox" name="categoryBox[]" class="categoryBox'.($idCategoryDefault == $idCategory ? ' id_category_default' : '').'" id="categoryBox_'.$idCategory.'" value="'.$idCategory.'"'.((in_array($idCategory, $indexedCategories) || (Tools::getIntValue('id_category') == $idCategory && !(int) ($idObj))) ? ' checked="checked"' : '').' />
			</td>
			<td>
				'.$idCategory.'
			</td>
			<td>';
        for ($i = 2; $i < $level; $i++) {
            $content .= '<img src="../img/admin/lvl_'.$hasSuite[$i - 2].'.gif" alt="" />';
        }
        $content .= '<img src="../img/admin/'.($level == 1 ? 'lv1.gif' : 'lv2_'.($todo == $doneC ? 'f' : 'b').'.gif').'" alt="" /> &nbsp;
			<label for="categoryBox_'.$idCategory.'" class="t">'.stripslashes($current['infos']['name']).'</label></td>
		</tr>';

        if ($level > 1) {
            $hasSuite[] = ($todo == $doneC ? 0 : 1);
        }
        if (isset($categories[$idCategory])) {
            foreach ($categories[$idCategory] as $key => $row) {
                if ($key != 'infos') {
                    $content .= AdminProductsController::recurseCategoryForInclude($idObj, $indexedCategories, $categories, $categories[$idCategory][$key], $key, $idCategoryDefault, $hasSuite);
                }
            }
        }

        return $content;
    }


    /**
     * @return void
     *
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();

        $boTheme = ((Validate::isLoadedObject($this->context->employee)
            && $this->context->employee->bo_theme) ? $this->context->employee->bo_theme : 'default');

        if (!file_exists(_PS_BO_ALL_THEMES_DIR_.$boTheme.DIRECTORY_SEPARATOR.'template')) {
            $boTheme = 'default';
        }

        $this->addJs(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$boTheme.'/js/jquery.iframe-transport.js');
        $this->addJs(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$boTheme.'/js/jquery.fileupload.js');
        $this->addJs(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$boTheme.'/js/jquery.fileupload-process.js');
        $this->addJs(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$boTheme.'/js/jquery.fileupload-validate.js');
        $this->addJs(__PS_BASE_URI__.'js/vendor/spin.js');
        $this->addJs(__PS_BASE_URI__.'js/vendor/ladda.js');
    }

    /**
     * @param int $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int $start
     * @param int|null $limit
     * @param int|null $idLangShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = null)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $this->context->shop->id);

        /* update product quantity with attributes ...*/
        $nb = count($this->_list);
        if ($this->_list) {
            $context = $this->context->cloneContext();
            $context->shop = clone($context->shop);
            /* update product final price */
            for ($i = 0; $i < $nb; $i++) {
                if ($this->context->shop->getContext() != Shop::CONTEXT_SHOP) {
                    $context->shop = new Shop((int) $this->_list[$i]['id_shop_default']);
                }

                $decimals = $this->context->currency->getDisplayPrecision();
                // convert price with the currency from context
                $this->_list[$i]['price'] = Tools::convertPrice($this->_list[$i]['price'], $this->context->currency, true, $this->context);
                $this->_list[$i]['price_final'] = Product::getPriceStatic(
                    $this->_list[$i]['id_product'],
                    true,
                    null,
                    $decimals,
                    null,
                    false,
                    true,
                    1,
                    true,
                    null,
                    null,
                    null,
                    $nothing,
                    true,
                    true,
                    $context
                );
            }
        }
    }

    /**
     * Ajax process get category tree
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function ajaxProcessGetCategoryTree()
    {
        $category = Tools::getValue('category', Category::getRootCategory()->id);
        $fullTree = Tools::getValue('fullTree', 0);
        $useCheckBox = Tools::getValue('useCheckBox', 1);
        $selected = Tools::getValue('selected', []);
        $idTree = Tools::getValue('type');
        $inputName = str_replace(['[', ']'], '', Tools::getValue('inputName', null));

        $tree = new HelperTreeCategories('subtree_associated_categories');
        $tree->setTemplate('subtree_associated_categories.tpl')
            ->setHeaderTemplate('tree_associated_header.tpl')
            ->setUseCheckBox($useCheckBox)
            ->setUseSearch(true)
            ->setIdTree($idTree)
            ->setSelectedCategories($selected)
            ->setFullTree($fullTree)
            ->setChildrenOnly(true)
            ->setNoJS(true)
            ->setRootCategory($category);

        if ($inputName) {
            $tree->setInputName($inputName);
        }

        $this->ajaxDie($tree->render());
    }

    /**
     * Ajax process get countries options
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function ajaxProcessGetCountriesOptions()
    {
        if (!$res = Country::getCountriesByIdShop(Tools::getIntValue('id_shop'), (int) $this->context->language->id)) {
            return;
        }

        $tpl = $this->createTemplate('specific_prices_shop_update.tpl');
        $tpl->assign(
            [
                'option_list' => $res,
                'key_id'      => 'id_country',
                'key_value'   => 'name',
            ]
        );

        $this->content = $tpl->fetch();
    }

    /**
     * Ajax process get currency options
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function ajaxProcessGetCurrenciesOptions()
    {
        if (!$res = Currency::getCurrenciesByIdShop(Tools::getIntValue('id_shop'))) {
            return;
        }

        $tpl = $this->createTemplate('specific_prices_shop_update.tpl');
        $tpl->assign(
            [
                'option_list' => $res,
                'key_id'      => 'id_currency',
                'key_value'   => 'name',
            ]
        );

        $this->content = $tpl->fetch();
    }

    /**
     * Ajax process get group options
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function ajaxProcessGetGroupsOptions()
    {
        if (!$res = Group::getGroups((int) $this->context->language->id, Tools::getIntValue('id_shop'))) {
            return;
        }

        $tpl = $this->createTemplate('specific_prices_shop_update.tpl');
        $tpl->assign(
            [
                'option_list' => $res,
                'key_id'      => 'id_group',
                'key_value'   => 'name',
            ]
        );

        $this->content = $tpl->fetch();
    }

    /**
     * Process delete virtual product
     *
     * @throws PrestaShopException
     */
    public function processDeleteVirtualProduct()
    {
        $idProduct = Tools::getIntValue('id_product');
        $idProductDownload = ProductDownload::getIdFromIdProduct($idProduct);

        if ($idProductDownload) {
            $productDownload = new ProductDownload($idProductDownload);

            if (!$productDownload->deleteFile()) {
                $this->errors[] = Tools::displayError('Cannot delete file.');
            } else {
                $productDownload->active = false;
                $productDownload->update();
                $this->redirect_after = static::$currentIndex.'&id_product='.$idProduct.'&updateproduct&key_tab=VirtualProduct&conf=1&token='.$this->token;
            }
        }

        $this->display = 'edit';
        $this->tab_display = 'VirtualProduct';
    }

    /**
     * Ajax process add attachment
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessAddAttachment()
    {
        if (!$this->hasEditPermission()) {
            $this->ajaxDie(json_encode(['error' => $this->l('You do not have the right permission')]));
        }
        if (isset($_FILES['attachment_file'])) {
            $errorCode = (int) $_FILES['attachment_file']['error'];
            if ($errorCode) {
                $_FILES['attachment_file']['error'] = [ Tools::decodeUploadError($errorCode) ];
            } else {
                $_FILES['attachment_file']['error'] = [];
            }

            $isAttachmentNameValid = false;
            $attachmentNames = Tools::getValue('attachment_name');
            $attachmentDescriptions = Tools::getValue('attachment_description');

            if (!isset($attachmentNames) || !$attachmentNames) {
                $attachmentNames = [];
            }

            if (!isset($attachmentDescriptions) || !$attachmentDescriptions) {
                $attachmentDescriptions = [];
            }

            foreach ($attachmentNames as $lang => $name) {
                $language = Language::getLanguage((int) $lang);

                if (mb_strlen($name) > 0) {
                    $isAttachmentNameValid = true;
                }

                if (!Validate::isGenericName($name)) {
                    $_FILES['attachment_file']['error'][] = sprintf(Tools::displayError('Invalid name for %s language'), $language['name']);
                } elseif (mb_strlen($name) > 32) {
                    $_FILES['attachment_file']['error'][] = sprintf(Tools::displayError('The name for %1s language is too long (%2d chars max).'), $language['name'], 32);
                }
            }

            foreach ($attachmentDescriptions as $lang => $description) {
                $language = Language::getLanguage((int) $lang);

                if (!Validate::isCleanHtml($description)) {
                    $_FILES['attachment_file']['error'][] = sprintf(Tools::displayError('Invalid description for %s language'), $language['name']);
                }
            }

            if (!$isAttachmentNameValid) {
                $_FILES['attachment_file']['error'][] = Tools::displayError('An attachment name is required.');
            }

            if (empty($_FILES['attachment_file']['error'])) {
                if (is_uploaded_file($_FILES['attachment_file']['tmp_name'])) {
                    if ($_FILES['attachment_file']['size'] > (Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE') * 1024 * 1024)) {
                        $_FILES['attachment_file']['error'][] = sprintf(
                            $this->l('The file is too large. Maximum size allowed is: %1$d kB. The file you are trying to upload is %2$d kB.'),
                            (Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE') * 1024),
                            number_format(($_FILES['attachment_file']['size'] / 1024), 2, '.', '')
                        );
                    } else {
                        $uniqid = Attachment::getNewFilename();
                        if (!copy($_FILES['attachment_file']['tmp_name'], _PS_DOWNLOAD_DIR_.$uniqid)) {
                            $_FILES['attachment_file']['error'][] = $this->l('File copy failed');
                        }
                        @unlink($_FILES['attachment_file']['tmp_name']);
                    }
                } else {
                    $_FILES['attachment_file']['error'][] = Tools::displayError('The file is missing.');
                }

                if (empty($_FILES['attachment_file']['error']) && isset($uniqid)) {
                    $attachment = new Attachment();

                    foreach ($attachmentNames as $lang => $name) {
                        $attachment->name[(int) $lang] = $name;
                    }

                    foreach ($attachmentDescriptions as $lang => $description) {
                        $attachment->description[(int) $lang] = $description;
                    }

                    $attachment->file = $uniqid;
                    $attachment->mime = $_FILES['attachment_file']['type'];
                    $attachment->file_name = $_FILES['attachment_file']['name'];

                    if (empty($attachment->mime) || mb_strlen($attachment->mime) > 128) {
                        $_FILES['attachment_file']['error'][] = Tools::displayError('Invalid file extension');
                    }
                    if (!Validate::isGenericName($attachment->file_name)) {
                        $_FILES['attachment_file']['error'][] = Tools::displayError('Invalid file name');
                    }
                    if (mb_strlen($attachment->file_name) > 128) {
                        $_FILES['attachment_file']['error'][] = Tools::displayError('The file name is too long.');
                    }
                    if (empty($this->errors)) {
                        $res = $attachment->add();
                        if (!$res) {
                            $_FILES['attachment_file']['error'][] = Tools::displayError('This attachment was unable to be loaded into the database.');
                        } else {
                            $_FILES['attachment_file']['id_attachment'] = $attachment->id;
                            $_FILES['attachment_file']['filename'] = $attachment->name[$this->context->employee->id_lang];
                            $idProduct = Tools::getIntValue($this->identifier);
                            $res = $attachment->attachProduct($idProduct);
                            if (!$res) {
                                $_FILES['attachment_file']['error'][] = Tools::displayError('We were unable to associate this attachment to a product.');
                            }
                        }
                    } else {
                        foreach ($this->errors as $error) {
                            $_FILES['attachment_file']['error'][] = $error;
                        }
                    }
                }
            }

            $this->ajaxDie(json_encode($_FILES));
        }
    }

    /**
     * Process duplicate
     *
     * @throws PrestaShopException
     */
    public function processDuplicate()
    {
        $product = new Product(Tools::getIntValue('id_product'));
        if (Validate::isLoadedObject($product)) {
            $idProductOld = $product->id;
            if (empty($product->price) && Shop::getContext() == Shop::CONTEXT_GROUP) {
                $shops = ShopGroup::getShopsFromGroup(Shop::getContextShopGroupID());
                foreach ($shops as $shop) {
                    if ($product->isAssociatedToShop($shop['id_shop'])) {
                        $productPrice = new Product($idProductOld, false, null, $shop['id_shop']);
                        $product->price = $productPrice->price;
                    }
                }
            }
            unset($product->id);
            unset($product->id_product);
            $product->indexed = 0;
            $product->active = 0;
            $product->link_rewrite = static::getUniqueLinkRewrites($product->link_rewrite);
            $product->reference = Tools::nextAvailableReference(static::getBaseIdentifier($product->reference));
            if ($product->add()
                && Category::duplicateProductCategories($idProductOld, $product->id)
                && Product::duplicateSuppliers($idProductOld, $product->id)
                && ($combinationImages = Product::duplicateAttributes($idProductOld, $product->id)) !== false
                && GroupReduction::duplicateReduction($idProductOld, $product->id)
                && Product::duplicateAccessories($idProductOld, $product->id)
                && Product::duplicateFeatures($idProductOld, $product->id)
                && Product::duplicateSpecificPrices($idProductOld, $product->id)
                && Pack::duplicate($idProductOld, $product->id)
                && Product::duplicateCustomizationFields($idProductOld, $product->id)
                && Product::duplicateTags($idProductOld, $product->id)
                && Product::duplicateDownload($idProductOld, $product->id)
                && Product::duplicateAttachments($idProductOld, $product->id)
            ) {
                if ($product->hasAttributes()) {
                    Product::updateDefaultAttribute($product->id);
                } else {
	                // Set stock quantity
	                $quantityAttributeOld = Db::readOnly()->getValue(
		                (new DbQuery())
			                ->select('`quantity`')
			                ->from('stock_available')
			                ->where('`id_product` = '.(int) $idProductOld)
			                ->where('`id_product_attribute` = 0')
	                );
	                StockAvailable::setQuantity((int) $product->id, 0, (int) $quantityAttributeOld);
                }

                if (!Tools::getValue('noimage') && !Image::duplicateProductImages($idProductOld, $product->id, $combinationImages)) {
                    $this->errors[] = Tools::displayError('An error occurred while copying images.');
                } else {
                    Hook::triggerEvent('actionProductAdd', ['id_product' => (int) $product->id, 'product' => $product]);
                    if (in_array($product->visibility, ['both', 'search']) && Configuration::get('PS_SEARCH_INDEXATION')) {
                        Search::indexation(false, $product->id);
                    }
                    $this->redirect_after = static::$currentIndex.(Tools::getIsset('id_category') ? '&id_category='.Tools::getIntValue('id_category') : '').'&conf=19&token='.$this->token;
                }
            } else {
                $this->errors[] = Tools::displayError('An error occurred while creating an object.');
            }
        }
    }

    /**
     * Process delete
     *
     * @throws PrestaShopException
     */
    public function processDelete()
    {
        if (Validate::isLoadedObject($object = $this->loadObject()) && isset($this->fieldImageSettings)) {
            /*
             * It is NOT possible to delete a product if there is/are currently:
             * - a physical stock for this product
             * - supply order(s) for this product
             */
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $object->advanced_stock_management) {
                $stockManager = StockManagerFactory::getManager();
                $physicalQuantity = $stockManager->getProductPhysicalQuantities($object->id, 0);
                $realQuantity = $stockManager->getProductRealQuantities($object->id, 0);
                if ($physicalQuantity > 0 || $realQuantity > $physicalQuantity) {
                    $this->errors[] = Tools::displayError('You cannot delete this product because there is physical stock left.');
                }
            }

            if (!count($this->errors)) {
                if ($object->delete()) {
                    $idCategory = Tools::getIntValue('id_category');
                    $categoryUrl = empty($idCategory) ? '' : '&id_category='.(int) $idCategory;
                    Logger::addLog(sprintf($this->l('%s deletion', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int) $object->id, true, (int) $this->context->employee->id);
                    $this->redirect_after = static::$currentIndex.'&conf=1&token='.$this->token.$categoryUrl;
                } else {
                    $this->errors[] = Tools::displayError('An error occurred during deletion.');
                }
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while deleting the object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }
    }

    /**
     * Load object
     *
     * @param bool $opt
     *
     * @return Product|bool
     *
     * @throws PrestaShopException
     */
    protected function loadObject($opt = false)
    {
        /** @var Product|bool $result */
        $result = parent::loadObject($opt);
        if ($result && Validate::isLoadedObject($this->object)) {
            if (Shop::isFeatureActive()) {
                if (! $this->object->isAssociatedToShop()) {
                    $defaultProduct = new Product((int)$this->object->id, false, null, (int)$this->object->getDefaultShopId());
                    $this->object->id_shop = $defaultProduct->id_shop;
                    $def = ObjectModel::getDefinition(get_class($this->object));
                    foreach ($def['fields'] as $field_name => $row) {
                        if (is_array($defaultProduct->$field_name)) {
                            foreach ($defaultProduct->$field_name as $key => $value) {
                                $this->object->{$field_name}[$key] = $value;
                            }
                        } else {
                            $this->object->$field_name = $defaultProduct->$field_name;
                        }
                    }
                }
            }
            $this->object->loadStockData();
        }

        return $result;
    }

    /**
     * Process image
     *
     * @throws PrestaShopException
     */
    public function processImage()
    {
        $idImage = Tools::getIntValue('id_image');
        $image = new Image((int) $idImage);
        if (Validate::isLoadedObject($image)) {
            /* Update product image/legend */
            // @todo : move in processEditProductImage
            if (Tools::getIsset('editImage')) {
                if ($image->cover) {
                    $_POST['cover'] = 1;
                }

                $_POST['id_image'] = $image->id;
            } elseif (Tools::getIsset('coverImage')) {
                /* Choose product cover image */
                Image::deleteCover($image->id_product);
                $image->cover = 1;
                if (!$image->update()) {
                    $this->errors[] = Tools::displayError('You cannot change the product\'s cover image.');
                } else {
                    $this->redirect_after = static::$currentIndex.'&id_product='.$image->id_product.'&id_category='.(Tools::getIsset('id_category') ? '&id_category='.Tools::getIntValue('id_category') : '').'&action=Images&addproduct'.'&token='.$this->token;
                }
            } elseif (Tools::getIsset('imgPosition') && Tools::getIsset('imgDirection')) {
                /* Choose product image position */
                $image->updatePosition(Tools::getValue('imgDirection'), Tools::getValue('imgPosition'));
                $this->redirect_after = static::$currentIndex.'&id_product='.$image->id_product.'&id_category='.(Tools::getIsset('id_category') ? '&id_category='.Tools::getIntValue('id_category') : '').'&add'.$this->table.'&action=Images&token='.$this->token;
            }
        } else {
            $this->errors[] = Tools::displayError('The image could not be found. ');
        }
    }

    /**
     * Method to retrieve information about specific price
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessEditSpecificPrice() {
        $idSpecificPrice = Tools::getIntValue('id_specific_price');
        $error = null;
        $specificPriceData = [];
        if (!$idSpecificPrice || !Validate::isUnsignedId($idSpecificPrice)) {
            $error = Tools::displayError('Invalid specific price ID');
        } else {
            $specificPrice = new SpecificPrice((int) $idSpecificPrice);
            if (! Validate::isLoadedObject($specificPrice)) {
                $error = Tools::displayError('Specific price not found');
            }
            $specificPriceData = [
                'id' => $idSpecificPrice,
                'id_shop' => (int)$specificPrice->id_shop,
                'id_currency' => (int)$specificPrice->id_currency,
                'id_country' => (int)$specificPrice->id_country,
                'id_group' => (int)$specificPrice->id_group,
                'id_customer' => (int)$specificPrice->id_customer,
                'id_product_attribute' => (int)$specificPrice->id_product_attribute,
                'from' => $specificPrice->from == '0000-00-00 00:00:00' ? null : $specificPrice->from,
                'to' => $specificPrice->to == '0000-00-00 00:00:00' ? null : $specificPrice->to,
                'from_quantity' => (int)$specificPrice->from_quantity,
                'price' => Tools::roundPrice((float)$specificPrice->price),
                'reduction' => Tools::roundPrice((float)$specificPrice->reduction),
                'reduction_tax' => (int)$specificPrice->reduction_tax,
                'reduction_type' => $specificPrice->reduction_type,
                'customer_name' => '',
            ];
            if ($specificPrice->id_customer) {
                $customer = new Customer($specificPrice->id_customer);
                if (Validate::isLoadedObject($customer)) {
                    $specificPriceData['customer_name'] = $customer->firstname.' '.$customer->lastname;
                }
            }
        }

        if ($error) {
            $json = [
                'status' => 'error',
                'message' => $error
            ];
        } else {
            $json = [
                'status' => 'ok',
                'specificPrice' => $specificPriceData
            ];
        }
        $this->ajaxDie(json_encode($json));
    }

    /**
     * Validate a SpecificPrice
     *
     * @param int $idShop
     * @param int $idCurrency
     * @param int $idCountry
     * @param int $idGroup
     * @param int $idCustomer
     * @param float $price
     * @param int $fromQuantity
     * @param float $reduction
     * @param string $reductionType
     * @param string $from
     * @param string $to
     * @param int $idCombination
     * @param int $idSpecificPrice
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function _validateSpecificPrice($idShop, $idCurrency, $idCountry, $idGroup, $idCustomer, $price, $fromQuantity, $reduction, $reductionType, $from, $to, $idCombination = 0, $idSpecificPrice = 0)
    {
        if (!Validate::isUnsignedId($idShop) || !Validate::isUnsignedId($idCurrency) || !Validate::isUnsignedId($idCountry) || !Validate::isUnsignedId($idGroup) || !Validate::isUnsignedId($idCustomer)) {
            $this->errors[] = Tools::displayError('Wrong IDs');
        } elseif ((!isset($price) && !isset($reduction)) || (isset($price) && !Validate::isNegativePrice($price)) || (isset($reduction) && !Validate::isPrice($reduction))) {
            $this->errors[] = Tools::displayError('Invalid price/discount amount');
        } elseif (!Validate::isUnsignedInt($fromQuantity)) {
            $this->errors[] = Tools::displayError('Invalid quantity');
        } elseif ($reduction && !Validate::isReductionType($reductionType)) {
            $this->errors[] = Tools::displayError('Please select a discount type (amount or percentage).');
        } elseif ($from && $to && (!Validate::isDateFormat($from) || !Validate::isDateFormat($to))) {
            $this->errors[] = Tools::displayError('The from/to date is invalid.');
        } elseif (!$idSpecificPrice && SpecificPrice::exists((int) $this->object->id, $idCombination, $idShop, $idGroup, $idCountry, $idCurrency, $idCustomer, $fromQuantity, $from, $to, false)) {
            $this->errors[] = Tools::displayError('A specific price already exists for these parameters.');
        } else {
            return true;
        }

        return false;
    }

    /**
     * Ajax process delete SpecificPrice
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessDeleteSpecificPrice()
    {
        if ($this->hasDeletePermission()) {
            $idSpecificPrice = Tools::getIntValue('id_specific_price');
            if (!$idSpecificPrice || !Validate::isUnsignedId($idSpecificPrice)) {
                $error = Tools::displayError('The specific price ID is invalid.');
            } else {
                $specificPrice = new SpecificPrice((int) $idSpecificPrice);
                if (!$specificPrice->delete()) {
                    $error = Tools::displayError('An error occurred while attempting to delete the specific price.');
                }
            }
        } else {
            $error = Tools::displayError('You do not have permission to delete this.');
        }

        if (isset($error)) {
            $json = [
                'status'  => 'error',
                'message' => $error,
            ];
        } else {
            $json = [
                'status'  => 'ok',
                'message' => $this->_conf[1],
            ];
        }

        $this->ajaxDie(json_encode($json));
    }

    /**
     * Process product customization
     *
     * @throws PrestaShopException
     */
    public function processProductCustomization()
    {
        if (Validate::isLoadedObject($product = new Product(Tools::getIntValue('id_product')))) {
            foreach ($_POST as $field => $value) {
                if (strncmp($field, 'label_', 6) == 0 && !Validate::isLabel($value)) {
                    $this->errors[] = Tools::displayError('The label fields defined are invalid.');
                }
            }
            if (empty($this->errors) && !$product->updateLabels()) {
                $this->errors[] = Tools::displayError('An error occurred while updating customization fields.');
            }
            if (empty($this->errors)) {
                $this->confirmations[] = $this->l('Update successful');
            }
        } else {
            $this->errors[] = Tools::displayError('A product must be created before adding customization.');
        }
    }

    /**
     * Overrides parent for custom redirect link
     *
     * @throws PrestaShopException
     */
    public function processPosition()
    {
        /** @var Product $object */
        if (!Validate::isLoadedObject($object = $this->loadObject())) {
            $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        } elseif (!$object->updatePosition(Tools::getIntValue('way'), Tools::getIntValue('position'))) {
            $this->errors[] = Tools::displayError('Failed to update the position.');
        } else {
            $category = new Category(Tools::getIntValue('id_category'));
            if (Validate::isLoadedObject($category)) {
                Hook::triggerEvent('actionCategoryUpdate', ['category' => $category]);
            }
            $this->redirect_after = static::$currentIndex.'&'.$this->table.'Orderby=position&'.$this->table.'Orderway=asc&action=Customization&conf=5'.(($idCategory = (Tools::getIsset('id_category') ? Tools::getIntValue('id_category') : '')) ? ('&id_category='.$idCategory) : '').'&token='.Tools::getAdminTokenLite('AdminProducts');
        }
    }

    /**
     * Initialize processing
     *
     * @throws PrestaShopException
     */
    public function initProcess()
    {
        if (
            Tools::isSubmit('submitAddproductAndStay') ||
            Tools::isSubmit('submitAddproduct') ||
            Tools::isSubmit('submitAddProductAndPreview')
        ) {
            // Clean up possible product type changes.
            $typeProduct = Tools::getIntValue('type_product');
            $idProduct = Tools::getIntValue('id_product');

            $this->id_object = $idProduct;
            $this->object = new Product($idProduct);

            if ($typeProduct !== Product::PTYPE_PACK) {
                if (!Pack::deleteItems($idProduct)) {
                    $this->errors[] = Tools::displayError('Cannot delete product pack items.');
                }
            }
            if ($typeProduct !== Product::PTYPE_VIRTUAL) {
                $idProductDownload = ProductDownload::getIdFromIdProduct($idProduct, false);

                if ($idProductDownload) {
                    $productDownload = new ProductDownload($idProductDownload);
                    if (!$productDownload->delete()) {
                        $this->errors[] = Tools::displayError('Cannot delete product download.');
                    }
                }
            }
        }

        // Delete a product in the download folder
        if (Tools::getValue('deleteVirtualProduct')) {
            if ($this->hasDeletePermission()) {
                $this->action = 'deleteVirtualProduct';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        } elseif (Tools::isSubmit('submitAddProductAndPreview')) {
            // Product preview
            $this->display = 'edit';
            $this->action = 'save';
        } elseif (Tools::isSubmit('submitAttachments')) {
            if ($this->hasEditPermission()) {
                $this->action = 'attachments';
                $this->tab_display = 'attachments';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::getIsset('duplicate'.$this->table)) {
            // Product duplication
            if ($this->hasAddPermission()) {
                $this->action = 'duplicate';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        } elseif (Tools::getIntValue('id_image') && Tools::getValue('ajax')) {
            // Product images management
            if ($this->hasEditPermission()) {
                $this->action = 'image';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitProductAttribute')) {
            // Product attributes management
            if ($this->hasEditPermission()) {
                $this->action = 'productAttribute';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitFeatures') || Tools::isSubmit('submitFeaturesAndStay')) {
            // Product features management
            if ($this->hasEditPermission()) {
                $this->action = 'features';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitPricesModification')) {
            // Product specific prices management
            if ($this->hasEditPermission()) {
                $this->action = 'pricesModification';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        } elseif (Tools::isSubmit('deleteSpecificPrice')) {
            if ($this->hasDeletePermission()) {
                $this->action = 'deleteSpecificPrice';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        } elseif (Tools::isSubmit('submitSpecificPricePriorities')) {
            if ($this->hasEditPermission()) {
                $this->action = 'specificPricePriorities';
                $this->tab_display = 'prices';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitCustomizationConfiguration')) {
            // Customization management
            if ($this->hasEditPermission()) {
                $this->action = 'customizationConfiguration';
                $this->tab_display = 'customization';
                $this->display = 'edit';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitProductCustomization')) {
            if ($this->hasEditPermission()) {
                $this->action = 'productCustomization';
                $this->tab_display = 'customization';
                $this->display = 'edit';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('id_product') && Tools::getValue('action') !== 'AddAttachment') {
            $postMaxSize = Tools::getMaxUploadSize(Configuration::get('PS_LIMIT_UPLOAD_FILE_VALUE') * 1024 * 1024);
            if ($postMaxSize && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] && $_SERVER['CONTENT_LENGTH'] > $postMaxSize) {
                $this->errors[] = sprintf(Tools::displayError('The uploaded file exceeds the "Maximum size for a downloadable product" set in preferences (%1$dMB) or the post_max_size/ directive in php.ini (%2$dMB).'), number_format((Configuration::get('PS_LIMIT_UPLOAD_FILE_VALUE'))), ($postMaxSize / 1024 / 1024));
            }
        }

        if (!$this->action) {
            parent::initProcess();
        } else {
            $this->id_object = Tools::getIntValue($this->identifier);
        }

        if (isset($this->available_tabs[Tools::getValue('key_tab')])) {
            $this->tab_display = Tools::getValue('key_tab');
        }

        // Set tab to display if not decided already
        if (!$this->tab_display && $this->action) {
            if (in_array($this->action, array_keys($this->available_tabs))) {
                $this->tab_display = $this->action;
            }
        }

        // And if still not set, use default
        if (!$this->tab_display) {
            $allowedTabs = $this->getVisibleTabs();
            if (isset($allowedTabs[$this->default_tab])) {
                $this->tab_display = $this->default_tab;
            } else {
                $this->tab_display = key($allowedTabs);
                $this->default_tab = $this->tab_display;
            }
        }
    }

    /**
     * Has the given tab been submitted?
     *
     * @param string $tabName
     *
     * @return bool
     */
    protected function isTabSubmitted($tabName)
    {
        if (!is_array($this->submitted_tabs)) {
            $this->submitted_tabs = Tools::getValue('submitted_tabs');
        }

        if (is_array($this->submitted_tabs) && in_array($tabName, $this->submitted_tabs)) {
            return true;
        }

        return false;
    }

    /**
     * postProcess handle every checks before saving products information
     *
     * @return void
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (!$this->redirect_after) {
            parent::postProcess();
        }

        if ($this->display == 'edit' || $this->display == 'add') {
            $this->addJqueryUI(
                [
                    'ui.core',
                    'ui.widget',
                ]
            );

            $this->addjQueryPlugin(
                [
                    'autocomplete',
                    'tablednd',
                    'thickbox',
                    'ajaxfileupload',
                    'date',
                    'tagify',
                    'select2',
                    'validate',
                ]
            );

            $this->addJS(
                [
                    _PS_JS_DIR_.'admin/products.js',
                    _PS_JS_DIR_.'admin/attributes.js',
                    _PS_JS_DIR_.'admin/price.js',
                    _PS_JS_DIR_.'tiny_mce/tiny_mce.js',
                    _PS_JS_DIR_.'admin/tinymce.inc.js',
                    _PS_JS_DIR_.'admin/dnd.js',
                    _PS_JS_DIR_.'jquery/ui/jquery.ui.progressbar.min.js',
                    _PS_JS_DIR_.'vendor/spin.js',
                    _PS_JS_DIR_.'vendor/ladda.js',
                ]
            );

            $this->addJS(_PS_JS_DIR_.'jquery/plugins/select2/select2_locale_'.$this->context->language->iso_code.'.js');
            $this->addJS(_PS_JS_DIR_.'jquery/plugins/validate/localization/messages_'.$this->context->language->iso_code.'.js');

            $this->addCSS(
                [
                    _PS_JS_DIR_.'jquery/plugins/timepicker/jquery-ui-timepicker-addon.css',
                ]
            );
        }
    }

    /**
     * Ajax process delete ProductAttribute
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessDeleteProductAttribute()
    {
        if (!Combination::isFeatureActive()) {
            return;
        }

        if ($this->hasDeletePermission()) {
            $idProduct = Tools::getIntValue('id_product');
            $idProductAttribute = Tools::getIntValue('id_product_attribute');

            if ($idProduct && Validate::isUnsignedId($idProduct) && Validate::isLoadedObject($product = new Product($idProduct))) {
                if (($dependsOnStock = StockAvailable::dependsOnStock($idProduct)) && StockAvailable::getQuantityAvailableByProduct($idProduct, $idProductAttribute)) {
                    $json = [
                        'status'  => 'error',
                        'message' => $this->l('It is not possible to delete a combination while it still has some quantities in the Advanced Stock Management. You must delete its stock first.'),
                    ];
                } else {
                    $product->deleteAttributeCombination((int) $idProductAttribute);
                    $product->checkDefaultAttributes();
                    Tools::clearColorListCache((int) $product->id);
                    if (!$product->hasAttributes()) {
                        $product->cache_default_attribute = 0;
                        $product->update();
                    } else {
                        Product::updateDefaultAttribute($idProduct);
                    }

                    if ($dependsOnStock && !Stock::deleteStockByIds($idProduct, $idProductAttribute)) {
                        $json = [
                            'status'  => 'error',
                            'message' => $this->l('Error while deleting the stock'),
                        ];
                    } else {
                        $json = [
                            'status'               => 'ok',
                            'message'              => $this->_conf[1],
                            'id_product_attribute' => (int) $idProductAttribute,
                        ];
                    }
                }
            } else {
                $json = [
                    'status'  => 'error',
                    'message' => $this->l('You cannot delete this attribute.'),
                ];
            }
        } else {
            $json = [
                'status'  => 'error',
                'message' => $this->l('You do not have permission to delete this.'),
            ];
        }

        $this->ajaxDie(json_encode($json));
    }

    /**
     * Ajax process default ProductAttribute
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessDefaultProductAttribute()
    {
        if ($this->hasEditPermission()) {
            if (!Combination::isFeatureActive()) {
                return;
            }

            if (Validate::isLoadedObject($product = new Product(Tools::getIntValue('id_product')))) {
                $product->deleteDefaultAttributes();
                $product->setDefaultAttribute(Tools::getIntValue('id_product_attribute'));
                $product->checkDefaultAttributes();
                $json = [
                    'status'  => 'ok',
                    'message' => $this->_conf[4],
                ];
            } else {
                $json = [
                    'status'  => 'error',
                    'message' => $this->l('You cannot make this the default attribute.'),
                ];
            }

            $this->ajaxDie(json_encode($json));
        }
    }

    /**
     * Ajax process edit ProductAttribute
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessEditProductAttribute()
    {
        if ($this->hasEditPermission()) {
            $idProduct = Tools::getIntValue('id_product');
            $idProductAttribute = Tools::getIntValue('id_product_attribute');
            if ($idProduct && Validate::isUnsignedId($idProduct) && Validate::isLoadedObject($product = new Product((int) $idProduct))) {
                $combinations = $product->getAttributeCombinationsById($idProductAttribute, $this->context->language->id);
                foreach ($combinations as $key => $combination) {
                    $combinations[$key]['attributes'][] = [$combination['group_name'], $combination['attribute_name'], $combination['id_attribute']];
                }

                $this->ajaxDie(json_encode($combinations));
            }
        }
    }

    /**
     * Ajax pre process
     *
     * @return void
     */
    public function ajaxPreProcess()
    {
        if (Tools::getIsset('update'.$this->table) && Tools::getIsset('id_'.$this->table)) {
            $this->display = 'edit';
            $this->action = Tools::getValue('action');
        }
    }

    /**
     * Ajax process update Product image shop association
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateProductImageShopAsso()
    {
        $idProduct = Tools::getIntValue('id_product');
        $conn = Db::getInstance();
        if (($idImage = Tools::getIntValue('id_image')) && ($idShop = Tools::getIntValue('id_shop'))) {
            if (Tools::getValue('active') == 'true') {
                $res = $conn->execute('INSERT INTO '._DB_PREFIX_.'image_shop (`id_product`, `id_image`, `id_shop`, `cover`) VALUES('.(int) $idProduct.', '.(int) $idImage.', '.(int) $idShop.', NULL)');
            } else {
                $res = $conn->execute('DELETE FROM '._DB_PREFIX_.'image_shop WHERE `id_image` = '.(int) $idImage.' AND `id_shop` = '.(int) $idShop);
            }
        }

        // Clean covers in image table
        if (isset($idShop)) {
            $countCoverImage = $conn->getValue(
                '
			SELECT COUNT(*) FROM '._DB_PREFIX_.'image i
			INNER JOIN '._DB_PREFIX_.'image_shop ish ON (i.id_image = ish.id_image AND ish.id_shop = '.(int) $idShop.')
			WHERE i.cover = 1 AND i.`id_product` = '.(int) $idProduct
            );

            if (!$idImage) {
                $idImage = $conn->getValue(
                    '
                    SELECT i.`id_image` FROM '._DB_PREFIX_.'image i
                    INNER JOIN '._DB_PREFIX_.'image_shop ish ON (i.id_image = ish.id_image AND ish.id_shop = '.(int) $idShop.')
                    WHERE i.`id_product` = '.(int) $idProduct
                );
            }

            if ($countCoverImage < 1) {
                $conn->execute('UPDATE '._DB_PREFIX_.'image i SET i.cover = 1 WHERE i.id_image = '.(int) $idImage.' AND i.`id_product` = '.(int) $idProduct.' LIMIT 1');
            }

            // Clean covers in image_shop table
            $countCoverImageShop = $conn->getValue(
                '
                SELECT COUNT(*)
                FROM '._DB_PREFIX_.'image_shop ish
                WHERE ish.`id_product` = '.(int) $idProduct.' AND ish.id_shop = '.(int) $idShop.' AND ish.cover = 1'
            );
            if ($countCoverImageShop < 1) {
                $conn->execute('UPDATE '._DB_PREFIX_.'image_shop ish SET ish.cover = 1 WHERE ish.id_image = '.(int) $idImage.' AND ish.`id_product` = '.(int) $idProduct.' AND ish.id_shop =  '.(int) $idShop.' LIMIT 1');
            }
        }

        if (isset($res) && $res) {
            $this->jsonConfirmation($this->_conf[27]);
        } else {
            $this->jsonError(Tools::displayError('An error occurred while attempting to associate this image with your shop. '));
        }
    }

    /**
     * Ajax process update image position
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateImagePosition()
    {
        if (!$this->hasEditPermission()) {
            $this->ajaxDie(json_encode(['error' => $this->l('You do not have the right permission')]));
        }
        $res = false;
        if ($json = Tools::getValue('json')) {
            // If there is an exception, at least the response is in JSON format.
            $this->json = true;

            $res = true;
            $json = stripslashes($json);
            $images = json_decode($json, true);
            $conn = Db::readOnly();
            foreach ($images as $id => $position) {
                /*
                 * If the image is not associated with the currently
                 * selected shop, the fields that are also in the image_shop
                 * table (like id_product and cover) cannot be loaded properly,
                 * so we have to load them separately.
                 */
                $img = new Image((int) $id);
                $def = $img::$definition;
                $sql = 'SELECT * FROM `' . _DB_PREFIX_ . $def['table'] . '` WHERE `' . $def['primary'] . '` = ' . (int) $id;
                $fields_from_table = $conn->getRow($sql);
                foreach ($def['fields'] as $key => $value) {
                    if (!isset($value['lang']) || !$value['lang']) {
                        $img->{$key} = $fields_from_table[$key];
                    }
                }
                $img->position = (int) $position;
                $res = $img->update() && $res;
            }
        }
        if ($res) {
            $this->jsonConfirmation($this->_conf[25]);
        } else {
            $this->jsonError(Tools::displayError('An error occurred while attempting to move this picture.'));
        }
    }

    /**
     * Ajax process update cover
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateCover()
    {
        if (!$this->hasEditPermission()) {
            $this->ajaxDie(json_encode(['error' => $this->l('You do not have the right permission')]));
        }
        Image::deleteCover(Tools::getIntValue('id_product'));
        $id_image = Tools::getIntValue('id_image');

        /*
         * If the image is not associated with the currently selected shop,
         * the fields that are also in the image_shop table (like id_product and
         * cover) cannot be loaded properly, so we have to load them separately.
         */
        $img = new Image($id_image);
        $def = $img::$definition;
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . $def['table'] . '` WHERE `' . $def['primary'] . '` = ' . $id_image;
        $fields_from_table = Db::readOnly()->getRow($sql);
        foreach ($def['fields'] as $key => $value) {
            if (!isset($value['lang']) || !$value['lang']) {
                $img->{$key} = $fields_from_table[$key];
            }
        }
        $img->cover = 1;

        if ($img->update()) {
            $this->jsonConfirmation($this->_conf[26]);
        } else {
            $this->jsonError(Tools::displayError('An error occurred while attempting to update the cover picture.'));
        }
    }

    /**
     * Ajax process delete product image
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessDeleteProductImage()
    {
        $this->display = 'content';
        /* Delete product image */
        $image = new Image(Tools::getIntValue('id_image'));
        $this->content['id'] = $image->id;
        $res = $image->delete();
        // if deleted image was the cover, change it to the first one
        $conn = Db::getInstance();
        if (!Image::getCover($image->id_product)) {
            $res = $conn->execute(
                '
			UPDATE `'._DB_PREFIX_.'image_shop` image_shop
			SET image_shop.`cover` = 1
			WHERE image_shop.`id_product` = '.(int) $image->id_product.'
			AND id_shop='.(int) $this->context->shop->id.' LIMIT 1'
            ) && $res;
        }

        if (!Image::getGlobalCover($image->id_product)) {
            $res = $conn->execute(
                '
			UPDATE `'._DB_PREFIX_.'image` i
			SET i.`cover` = 1
			WHERE i.`id_product` = '.(int) $image->id_product.' LIMIT 1'
            ) && $res;
        }

        if ($res) {
            $this->jsonConfirmation($this->_conf[7]);
        } else {
            $this->jsonError(Tools::displayError('An error occurred while attempting to delete the product image.'));
        }
    }

    /**
     * Add or update a product image
     *
     * @param Product $product Product object to add image
     * @param string $method
     *
     * @return int|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addProductImage($product, $method = 'auto')
    {
        /* Updating an existing product image */
        if ($idImage = Tools::getIntValue('id_image')) {
            $image = new Image((int) $idImage);
            if (!Validate::isLoadedObject($image)) {
                $this->errors[] = Tools::displayError('An error occurred while loading the object image.');
            } else {
                if (($cover = Tools::getValue('cover')) == 1) {
                    Image::deleteCover($product->id);
                }
                $image->cover = $cover;
                $this->validateRules('Image');
                $this->copyFromPost($image, 'image');
                if (count($this->errors) || !$image->update()) {
                    $this->errors[] = Tools::displayError('An error occurred while updating the image.');
                } elseif (isset($_FILES['image_product']['tmp_name']) && $_FILES['image_product']['tmp_name'] != null) {
                    $this->copyImage($product->id, $image->id, $method);
                }
            }
        }
        if (isset($image) && Validate::isLoadedObject($image) && !file_exists(_PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.'.$image->image_format)) {
            $image->delete();
        }
        if (count($this->errors)) {
            return false;
        }
        return $idImage ? $idImage : false;
    }

    /**
     * @param Product|ObjectModel $object
     * @param string $table
     *
     * @throws PrestaShopException
     */
    protected function copyFromPost(&$object, $table)
    {
        parent::copyFromPost($object, $table);
        if (get_class($object) != 'Product') {
            return;
        }

        /* Additional fields */
        foreach (Language::getIDs(false) as $idLang) {
            if (isset($_POST['meta_keywords_'.$idLang])) {
                $_POST['meta_keywords_'.$idLang] = $this->_cleanMetaKeywords(mb_strtolower($_POST['meta_keywords_'.$idLang]));
                // preg_replace('/ *,? +,* /', ',', strtolower($_POST['meta_keywords_'.$id_lang]));
                $object->meta_keywords[$idLang] = $_POST['meta_keywords_'.$idLang];
            }
        }
        $_POST['width'] = Tools::getNumberValue('width');
        $_POST['height'] = Tools::getNumberValue('height');
        $_POST['depth'] = Tools::getNumberValue('depth');
        $_POST['weight'] = Tools::getNumberValue('weight');

        if (Tools::getIsset('unit_price') != null) {
            $object->unit_price = Tools::getNumberValue('unit_price');
        }
        if (Tools::getIsset('ecotax') != null) {
            $object->ecotax = Tools::getNumberValue('ecotax');
        }

        if ($this->isTabSubmitted('Informations')) {
            if ($this->checkMultishopBox('available_for_order', $this->context)) {
                $object->available_for_order = Tools::getIntValue('available_for_order');
            }

            if ($this->checkMultishopBox('show_price', $this->context)) {
                $object->show_price = $object->available_for_order ? 1 : Tools::getIntValue('show_price');
            }

            if ($this->checkMultishopBox('online_only', $this->context)) {
                $object->online_only = Tools::getIntValue('online_only');
            }
        }
        if ($this->isTabSubmitted('Prices')) {
            $object->on_sale = Tools::getIntValue('on_sale');
        }
    }

    /**
     * Clean meta keywords
     *
     * @param string $keywords
     *
     * @return string
     */
    protected function _cleanMetaKeywords($keywords)
    {
        if (!empty($keywords) && $keywords != '') {
            $out = [];
            $words = explode(',', $keywords);
            foreach ($words as $wordItem) {
                $wordItem = trim($wordItem);
                if (!empty($wordItem) && $wordItem != '') {
                    $out[] = $wordItem;
                }
            }

            return ((count($out) > 0) ? implode(',', $out) : '');
        } else {
            return '';
        }
    }

    /**
     * @param string $field
     * @param Context|null $context
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function checkMultishopBox($field, $context = null)
    {
        static $checkbox = null;
        static $shopContext = null;

        if ($context == null && $shopContext == null) {
            $context = $this->context;
        }

        if ($shopContext == null) {
            $shopContext = $context->shop->getContext();
        }

        if ($checkbox == null) {
            $checkbox = Tools::getValue('multishop_check', []);
        }

        if (! $this->allowEditPerStore()) {
            return true;
        }

        if ($shopContext == Shop::CONTEXT_SHOP) {
            return true;
        }

        if (isset($checkbox[$field]) && $checkbox[$field] == 1) {
            return true;
        }

        return false;
    }

    /**
     * Copy a product image
     *
     * @param int $idProduct Product Id for product image filename
     * @param int $idImage Image Id for product image filename
     * @param string $method
     *
     * @return void|false
     * @throws PrestaShopException
     */
    public function copyImage($idProduct, $idImage, $method = 'auto')
    {
        if (!isset($_FILES['image_product']['tmp_name'])) {
            return false;
        }
        if ($error = ImageManager::validateUpload($_FILES['image_product'])) {
            $this->errors[] = $error;
        } else {
            $highDpi = (bool) Configuration::get('PS_HIGHT_DPI');

            $image = new Image($idImage);

            if (!$newPath = $image->getPathForCreation()) {
                $this->errors[] = Tools::displayError('An error occurred while attempting to create a new folder.');
            }
            if (!($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES['image_product']['tmp_name'], $tmpName)) {
                $this->errors[] = Tools::displayError('An error occurred during the image upload process.');
            } elseif (!ImageManager::resize($tmpName, $newPath.'.'.$image->image_format)) {
                $this->errors[] = Tools::displayError('An error occurred while copying the image.');
            } elseif ($method == 'auto') {
                $imagesTypes = ImageType::getImagesTypes('products');
                foreach ($imagesTypes as $imageType) {
                    if (!ImageManager::resize(
                        $tmpName,
                        $newPath.'-'.stripslashes($imageType['name']).'.'.$image->image_format,
                        (int) $imageType['width'],
                        (int) $imageType['height'],
                        $image->image_format
                    )) {
                        $this->errors[] = Tools::displayError('An error occurred while copying this image: '.stripslashes($imageType['name']));
                    } else {
                        if ($highDpi) {
                            ImageManager::resize(
                                $tmpName,
                                $newPath.'-'.stripslashes($imageType['name']).'2x.'.$image->image_format,
                                (int) $imageType['width'] * 2,
                                (int) $imageType['height'] * 2,
                                $image->image_format
                            );
                        }

                        if (ImageManager::generateWebpImages()) {
                            ImageManager::resize(
                                $tmpName,
                                $newPath.'-'.stripslashes($imageType['name']).'.webp',
                                (int) $imageType['width'],
                                (int) $imageType['height'],
                                'webp'
                            );

                            if ($highDpi) {
                                ImageManager::resize(
                                    $tmpName,
                                    $newPath.'-'.stripslashes($imageType['name']).'2x.webp',
                                    (int) $imageType['width'] * 2,
                                    (int) $imageType['height'] * 2,
                                    'webp'
                                );
                            }
                        }

                        if ((int) Configuration::get('TB_IMAGES_LAST_UPD_PRODUCTS') < $idProduct) {
                            Configuration::updateValue('TB_IMAGES_LAST_UPD_PRODUCTS', $idProduct);
                        }
                    }
                }
            }

            @unlink($tmpName);
            Hook::triggerEvent('actionWatermark', ['id_image' => $idImage, 'id_product' => $idProduct]);
        }
    }

    /**
     * Process add
     *
     * @return Product|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processAdd()
    {
        $this->checkProduct();

        if (!empty($this->errors)) {
            $this->display = 'add';

            return false;
        }

        $this->object = new $this->className();
        $this->_removeTaxFromEcotax();
        $this->copyFromPost($this->object, $this->table);
        if ($this->object->add()) {
            Logger::addLog(sprintf($this->l('%s addition', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int) $this->object->id, true, (int) $this->context->employee->id);
            $this->updateAssoShop($this->object->id);
            $this->updatePerStoreFields($this->object->id);
            $this->addCarriers($this->object);
            $this->updateAccessories($this->object);
            $this->updatePackItems($this->object);
            $this->updateDownloadProduct($this->object);

            if (Configuration::get('PS_FORCE_ASM_NEW_PRODUCT') && Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $this->object->getType() != Product::PTYPE_VIRTUAL) {
                $this->object->advanced_stock_management = 1;
                $this->object->save();
                foreach ($this->getSaveShopIDs() as $idShop) {
                    StockAvailable::setProductDependsOnStock($this->object->id, true, (int) $idShop, 0);
                }
            }

            if (empty($this->errors)) {
                $languages = Language::getLanguages(false);
                if ($this->isProductFieldUpdated('category_box') && !$this->object->updateCategories(Tools::getArrayValue('categoryBox'))) {
                    $this->errors[] = Tools::displayError('An error occurred while linking the object.').' <b>'.$this->table.'</b> '.Tools::displayError('To categories');
                } elseif (!$this->updateTags($languages, $this->object)) {
                    $this->errors[] = Tools::displayError('An error occurred while adding tags.');
                } else {
                    Hook::triggerEvent('actionProductAdd', ['id_product' => (int) $this->object->id, 'product' => $this->object]);
                    if (in_array($this->object->visibility, ['both', 'search']) && Configuration::get('PS_SEARCH_INDEXATION')) {
                        Search::indexation(false, $this->object->id);
                    }
                }

                if (Configuration::get('PS_DEFAULT_WAREHOUSE_NEW_PRODUCT') != 0 && Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                    $warehouseLocationEntity = new WarehouseProductLocation();
                    $warehouseLocationEntity->id_product = $this->object->id;
                    $warehouseLocationEntity->id_product_attribute = 0;
                    $warehouseLocationEntity->id_warehouse = Configuration::get('PS_DEFAULT_WAREHOUSE_NEW_PRODUCT');
                    $warehouseLocationEntity->location = pSQL('');
                    $warehouseLocationEntity->save();
                }

                // Apply groups reductions
                $this->object->setGroupReduction();

                // Save and preview
                if (Tools::isSubmit('submitAddProductAndPreview')) {
                    $this->redirect_after = $this->getPreviewUrl($this->object);
                }

                // Save and stay on same form
                if ($this->display == 'edit') {
                    $this->redirect_after = static::$currentIndex.'&id_product='.(int) $this->object->id.(Tools::getIsset('id_category') ? '&id_category='.Tools::getIntValue('id_category') : '').'&updateproduct&conf=3&key_tab='.Tools::safeOutput(Tools::getValue('key_tab')).'&token='.$this->token;
                } else {
                    // Default behavior (save and back)
                    $this->redirect_after = static::$currentIndex.(Tools::getIsset('id_category') ? '&id_category='.Tools::getIntValue('id_category') : '').'&conf=3&token='.$this->token;
                }
            } else {
                $this->object->delete();
                // if errors : stay on edit page
                $this->display = 'edit';
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while creating an object.').' <b>'.$this->table.'</b>';
        }

        return $this->object;
    }

    /**
     * Check that a saved product is valid
     * @throws PrestaShopException
     */
    public function checkProduct()
    {
        $className = 'Product';
        // @todo : the call_user_func seems to contains only statics values (className = 'Product')
        $rules = call_user_func([$this->className, 'getValidationRules'], $this->className);
        $defaultLanguage = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $languages = Language::getLanguages(false);

        // Check required fields
        foreach ($rules['required'] as $field) {
            if (!$this->isProductFieldUpdated($field)) {
                continue;
            }

            if (($value = Tools::getValue($field)) == false && $value != '0') {
                if (Tools::getValue('id_'.$this->table) && $field == 'passwd') {
                    continue;
                }
                $this->errors[] = sprintf(
                    Tools::displayError('The %s field is required.'),
                    call_user_func([$className, 'displayFieldName'], $field, $className)
                );
            }
        }

        // Check multilingual required fields
        foreach ($rules['requiredLang'] as $fieldLang) {
            if ($this->isProductFieldUpdated($fieldLang, $defaultLanguage->id) && !Tools::getValue($fieldLang.'_'.$defaultLanguage->id)) {
                $this->errors[] = sprintf(
                    Tools::displayError('This %1$s field is required at least in %2$s'),
                    call_user_func([$className, 'displayFieldName'], $fieldLang, $className),
                    $defaultLanguage->name
                );
            }
        }

        // Check fields sizes
        foreach ($rules['size'] as $field => $maxLength) {
            if ($this->isProductFieldUpdated($field) && ($value = Tools::getValue($field)) && mb_strlen($value) > $maxLength) {
                $this->errors[] = sprintf(
                    Tools::displayError('The %1$s field is too long (%2$d chars max).'),
                    call_user_func([$className, 'displayFieldName'], $field, $className),
                    $maxLength
                );
            }
        }

        if (Tools::getIsset('description_short') && $this->isProductFieldUpdated('description_short')) {
            $saveShort = Tools::getValue('description_short');
            $_POST['description_short'] = strip_tags(Tools::getValue('description_short'));
        }

        // Check description short size without html
        $limit = (int) Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT');
        if ($limit <= 0) {
            $limit = 400;
        }
        foreach ($languages as $language) {
            if ($this->isProductFieldUpdated('description_short', $language['id_lang']) && ($value = Tools::getValue('description_short_'.$language['id_lang']))) {
                if (mb_strlen(strip_tags($value)) > $limit) {
                    $this->errors[] = sprintf(
                        Tools::displayError('This %1$s field (%2$s) is too long: %3$d chars max (current count %4$d).'),
                        call_user_func([$className, 'displayFieldName'], 'description_short'),
                        $language['name'],
                        $limit,
                        mb_strlen(strip_tags($value))
                    );
                }
            }
        }

        // Check multilingual fields sizes
        foreach ($rules['sizeLang'] as $fieldLang => $maxLength) {
            foreach ($languages as $language) {
                $value = Tools::getValue($fieldLang.'_'.$language['id_lang']);
                if ($value && mb_strlen($value) > $maxLength) {
                    $this->errors[] = sprintf(
                        Tools::displayError('The %1$s field is too long (%2$d chars max).'),
                        call_user_func([$className, 'displayFieldName'], $fieldLang, $className),
                        $maxLength
                    );
                }
            }
        }

        if ($this->isProductFieldUpdated('description_short') && isset($_POST['description_short'])) {
            $_POST['description_short'] = $saveShort;
        }

        // Check fields validity
        foreach ($rules['validate'] as $field => $function) {
            if ($this->isProductFieldUpdated($field) && ($value = Tools::getValue($field))) {
                $res = true;
                if (mb_strtolower($function) == 'iscleanhtml') {
                    if (!Validate::$function($value, (int) Configuration::get('PS_ALLOW_HTML_IFRAME'))) {
                        $res = false;
                    }
                } elseif (!Validate::$function($value)) {
                    $res = false;
                }

                if (!$res) {
                    $this->errors[] = sprintf(
                        Tools::displayError('The %s field is invalid.'),
                        call_user_func([$className, 'displayFieldName'], $field, $className)
                    );
                }
            }
        }
        // Check multilingual fields validity
        foreach ($rules['validateLang'] as $fieldLang => $function) {
            foreach ($languages as $language) {
                if ($this->isProductFieldUpdated($fieldLang, $language['id_lang']) && ($value = Tools::getValue($fieldLang.'_'.$language['id_lang']))) {
                    if (!Validate::$function($value, (int) Configuration::get('PS_ALLOW_HTML_IFRAME'))) {
                        $this->errors[] = sprintf(
                            Tools::displayError('The %1$s field (%2$s) is invalid.'),
                            call_user_func([$className, 'displayFieldName'], $fieldLang, $className),
                            $language['name']
                        );
                    }
                }
            }
        }

        // Categories
        if ($this->isProductFieldUpdated('id_category_default') && !Tools::getArrayValue('categoryBox')) {
            $this->errors[] = $this->l('Products must be in at least one category.');
        }

        if ($this->isProductFieldUpdated('id_category_default') && !in_array(Tools::getIntValue('id_category_default'), Tools::getArrayValue('categoryBox'))) {
            $this->errors[] = $this->l('This product must be in the default category.');
        }

        // Tags
        foreach ($languages as $language) {
            if ($value = Tools::getValue('tags_'.$language['id_lang'])) {
                if (!Validate::isTagsList($value)) {
                    $this->errors[] = sprintf(
                        Tools::displayError('The tags list (%s) is invalid.'),
                        $language['name']
                    );
                }
            }
        }
    }

    /**
     * Check if a field is edited (if the checkbox is checked)
     * This method will do something only for multishop with a context all / group
     *
     * @param string $field Name of field
     * @param int|null $idLang
     *
     * @return bool
     * @throws PrestaShopException
     */
    protected function isProductFieldUpdated($field, $idLang = null)
    {
        // Cache this condition to improve performances
        static $isActivated = null;
        if (is_null($isActivated)) {
            $isActivated = Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP && $this->id_object;
            if ($isActivated) {
                $isActivated = $this->allowEditPerStore();
            }
        }

        if (!$isActivated) {
            return true;
        }

        $def = ObjectModel::getDefinition($this->object);
        if (!$this->object->isMultiShopField($field) && is_null($idLang) && isset($def['fields'][$field])) {
            return true;
        }

        if (is_null($idLang)) {
            return !empty($_POST['multishop_check'][$field]);
        } else {
            return !empty($_POST['multishop_check'][$field][$idLang]);
        }
    }

    /**
     * Checking customs feature
     *
     * @throws PrestaShopException
     */
    protected function _removeTaxFromEcotax()
    {
        if ($ecotax = Tools::getValue('ecotax')) {
            $_POST['ecotax'] = Tools::roundPrice($ecotax / (1 + Tax::getProductEcotaxRate() / 100));
        }
    }

    /**
     * @param Product|null $product
     *
     * @throws PrestaShopException
     */
    protected function addCarriers($product = null)
    {
        if ($this->isProductFieldUpdated('carrierSelection')) {
            $productId = Validate::isLoadedObject($product)
                ? (int)$product->id
                : Tools::getIntValue('id_product');

            if ($productId) {
                $carriers = [];
                if (Tools::getValue('selectedCarriers')) {
                    $carriers = array_map('intval', Tools::getValue('selectedCarriers'));
                }

                if (! $this->allowEditPerStore()) {
                    Db::getInstance()->delete('product_carrier', "id_product = $productId");
                }

                Product::associateProductWithCarriers(
                    $productId,
                    $carriers,
                    $this->getSaveShopIDs()
                );
            }
        }
    }

    /**
     * Update product accessories
     *
     * @param object $product Product
     */
    public function updateAccessories($product)
    {
        $product->deleteAccessories();
        if ($accessories = Tools::getValue('inputAccessories')) {
            $accessoriesId = array_unique(explode('-', $accessories));
            if (count($accessoriesId)) {
                array_pop($accessoriesId);
                $product->changeAccessories($accessoriesId);
            }
        }
    }

    /**
     * delete all items in pack, then check if type_product value is PTYPE_PACK.
     * if yes, add the pack items from input "inputPackItems"
     *
     * @param Product $product
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updatePackItems($product)
    {
        Pack::deleteItems($product->id);
        // lines format: QTY x ID-QTY x ID
        if (Tools::getValue('type_product') == Product::PTYPE_PACK) {
            $product->setDefaultAttribute(0);//reset cache_default_attribute
            $items = Tools::getValue('inputPackItems');
            $lines = array_unique(explode('-', $items));

            // lines is an array of string with format : QTYxIDxID_PRODUCT_ATTRIBUTE
            if (count($lines)) {
                foreach ($lines as $line) {
                    if (!empty($line)) {
                        $itemIdAttribute = 0;
                        count($array = explode('x', $line)) == 3 ? list($qty, $itemId, $itemIdAttribute) = $array : list($qty, $itemId) = $array;
                        if ($qty > 0 && isset($itemId)) {
                            if (Pack::isPack((int) $itemId || $product->id == (int) $itemId)) {
                                $this->errors[] = Tools::displayError('You can\'t add product packs into a pack');
                            } elseif (!Pack::addItem((int) $product->id, (int) $itemId, (int) $qty, (int) $itemIdAttribute)) {
                                $this->errors[] = Tools::displayError('An error occurred while attempting to add products to the pack.');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Update product download
     *
     * @param Product $product
     * @param int $edit Deprecated in favor of autodetection.
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updateDownloadProduct($product, $edit = 999)
    {
        if ($edit !== 999) {
            Tools::displayParameterAsDeprecated('edit');
        }

        $idProductDownload = ProductDownload::getIdFromIdProduct($product->id, false);
        if (!$idProductDownload) {
            $idProductDownload = Tools::getIntValue('virtual_product_id');
        }

        if (Tools::getValue('type_product') == Product::PTYPE_VIRTUAL
            && Tools::getValue('is_virtual_file') == 1) {
            if (isset($_FILES['virtual_product_file_uploader']) && $_FILES['virtual_product_file_uploader']['size'] > 0) {
                $filename = ProductDownload::getNewFilename();
                $helper = new HelperUploader('virtual_product_file_uploader');
                $helper
                    ->setPostMaxSize(Tools::getOctets(ini_get('upload_max_filesize')))
                    ->setSavePath(_PS_DOWNLOAD_DIR_)
                    ->upload($_FILES['virtual_product_file_uploader'], $filename);
            } else {
                $filename = Tools::getValue('virtual_product_filename', ProductDownload::getNewFilename());
            }

            $product->setDefaultAttribute(0); //reset cache_default_attribute

            $active = Tools::getValue('virtual_product_active');
            $isShareable = Tools::getValue('virtual_product_is_shareable');
            $name = Tools::getValue('virtual_product_name');
            $nbDays = Tools::getValue('virtual_product_nb_days');
            $nbDownloable = Tools::getValue('virtual_product_nb_downloable');
            $expirationDate = Tools::getValue('virtual_product_expiration_date');
            // This whould allow precision up to the second, not supported by
            // the datepicker in the GUI, yet.
            //if ($expirationDate
            //    && !preg_match('/\d{1,2}\:\d{1,2}/', $expirationDate)) {
            //    // No time given should mean the end of the day.
            //    $dateExpiration .= ' 23:59:59';
            //}
            if ($expirationDate) {
                // We want the end of the given day.
                $expirationDate .= ' 23:59:59';
            }

            $download = new ProductDownload($idProductDownload);
            $download->id_product = (int) $product->id;
            $download->display_filename = $name;
            $download->filename = $filename;
            $download->date_expiration = $expirationDate;
            $download->nb_days_accessible = (int) $nbDays;
            $download->nb_downloadable = (int) $nbDownloable;
            $download->active = (int) $active;
            $download->is_shareable = (int) $isShareable;
            if ($download->save()) {
                return true;
            }
        } else {
            // Delete the download and its file.
            if ($idProductDownload) {
                $productDownload = new ProductDownload($idProductDownload);

                return $productDownload->delete();
            }
        }

        return false;
    }

    /**
     * Update product tags
     *
     * @param array $languages Array languages
     * @param object $product Product
     *
     * @return bool Update result
     *
     * @throws PrestaShopException
     */
    public function updateTags($languages, $product)
    {
        $tagSuccess = true;
        /* Reset all tags for THIS product */
        if (!Tag::deleteTagsForProduct((int) $product->id)) {
            $this->errors[] = Tools::displayError('An error occurred while attempting to delete previous tags.');
        }
        /* Assign tags to this product */
        foreach ($languages as $language) {
            if ($value = Tools::getValue('tags_'.$language['id_lang'])) {
                $tagSuccess = Tag::addTags($language['id_lang'], (int) $product->id, $value) && $tagSuccess;
            }
        }

        if (!$tagSuccess) {
            $this->errors[] = Tools::displayError('An error occurred while adding tags.');
        }

        return $tagSuccess;
    }

    /**
     * @param Product $product
     *
     * @return bool|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getPreviewUrl(Product $product)
    {
        if ($this->context->language->active) {
            // use the same language that is used in back office
            $idLang = $this->context->language->id;
        } else {
            // language used in back office is not allowed in front office, fall back to store default language
            $idLang = Configuration::get('PS_LANG_DEFAULT', null, null, $this->context->shop->id);
        }


        if (!Validate::isLoadedObject($product) || !$product->id_category_default) {
            return $this->l('Unable to determine the preview URL. This product has not been linked with a category, yet.');
        }

        if (!ShopUrl::getMainShopDomain()) {
            return false;
        }

        $isRewriteActive = (bool) Configuration::get('PS_REWRITING_SETTINGS');
        $previewUrl = $this->context->link->getProductLink(
            $product,
            $this->getFieldValue($product, 'link_rewrite', $idLang),
            Category::getLinkRewrite($this->getFieldValue($product, 'id_category_default'), $idLang),
            null,
            $idLang,
            (int) $this->context->shop->id,
            0,
            $isRewriteActive
        );

        if (!$product->active) {
            $adminDir = dirname($_SERVER['PHP_SELF']);
            $adminDir = substr($adminDir, strrpos($adminDir, '/') + 1);
            $previewUrl .= ((strpos($previewUrl, '?') === false) ? '?' : '&').'adtoken='.$this->token.'&ad='.$adminDir.'&id_employee='.(int) $this->context->employee->id;
        }

        return $previewUrl;
    }

    /**
     * @return bool|false|ObjectModel
     *
     * @throws PrestaShopException
     */
    public function processStatus()
    {
        $this->loadObject(true);
        if (!Validate::isLoadedObject($this->object)) {
            return false;
        }
        if (($error = $this->object->validateFields(false, true)) !== true) {
            $this->errors[] = $error;
        }
        if (($error = $this->object->validateFieldsLang(false, true)) !== true) {
            $this->errors[] = $error;
        }

        if (count($this->errors)) {
            return false;
        }

        $res = parent::processStatus();

        $query = trim(Tools::getValue('bo_query'));
        $searchType = Tools::getIntValue('bo_search_type');

        if ($query) {
            $this->redirect_after = preg_replace('/[\?|&](bo_query|bo_search_type)=([^&]*)/i', '', $this->redirect_after);
            $this->redirect_after .= '&bo_query='.urlencode($query).'&bo_search_type='.$searchType;
        }

        return $res;
    }

    /**
     * Process update
     *
     * @return Product|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processUpdate()
    {
        $existingProduct = $this->object;

        $this->checkProduct();

        if (!empty($this->errors)) {
            $this->display = 'edit';

            return false;
        }

        $id = Tools::getIntValue('id_'.$this->table);
        /* Update an existing product */
        if ($id) {
            /** @var Product $object */
            $object = new $this->className((int) $id);
            $this->object = $object;

            if (Validate::isLoadedObject($object)) {
                $this->_removeTaxFromEcotax();
                $productTypeBefore = $object->getType();
                $this->copyFromPost($object, $this->table);
                $object->indexed = 0;

                if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
                    if ($this->allowEditPerStore()) {
                        $object->setFieldsToUpdate(Tools::getArrayValue('multishop_check', []));
                    }
                    $this->updateAssoShop($object->id);
                }

                // Duplicate combinations if not associated to shop
                if ($this->context->shop->getContext() == Shop::CONTEXT_SHOP && !$object->isAssociatedToShop()) {
                    $isAssociatedToShop = false;
                    $combinations = Product::getProductAttributesIds($object->id);
                    if ($combinations) {
                        foreach ($combinations as $idCombination) {
                            $combination = new Combination((int) $idCombination['id_product_attribute']);
                            $defaultCombination = new Combination((int) $idCombination['id_product_attribute'], null, (int) $this->object->id_shop_default);

                            $def = ObjectModel::getDefinition($defaultCombination);
                            foreach ($def['fields'] as $fieldName => $row) {
                                $combination->$fieldName = ObjectModel::formatValue($defaultCombination->$fieldName, $def['fields'][$fieldName]['type']);
                            }

                            $combination->save();
                        }
                    }
                } else {
                    $isAssociatedToShop = true;
                }

                if ($object->update()) {
                    // If the product doesn't exist in the current shop but exists in another shop
                    if (Shop::getContext() == Shop::CONTEXT_SHOP && !$existingProduct->isAssociatedToShop($this->context->shop->id)) {
                        $outOfStock = StockAvailable::outOfStock($existingProduct->id, $existingProduct->id_shop_default);
                        $dependsOnStock = StockAvailable::dependsOnStock($existingProduct->id, $existingProduct->id_shop_default);
                        StockAvailable::setProductOutOfStock((int) $this->object->id, $outOfStock, $this->context->shop->id);
                        StockAvailable::setProductDependsOnStock((int) $this->object->id, $dependsOnStock, $this->context->shop->id);
                    }

                    Logger::addLog(sprintf($this->l('%s modification', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int) $this->object->id, true, (int) $this->context->employee->id);
                    $this->updatePerStoreFields($object->id);
                    if (in_array($this->context->shop->getContext(), [Shop::CONTEXT_SHOP, Shop::CONTEXT_ALL])) {
                        if ($this->isTabSubmitted('Shipping')) {
                            $this->addCarriers();
                        }
                        if ($this->isTabSubmitted('Associations')) {
                            $this->updateAccessories($object);
                        }
                        if ($this->isTabSubmitted('Suppliers')) {
                            $this->processSuppliers();
                        }
                        if ($this->isTabSubmitted('Features')) {
                            $this->processFeatures();
                        }
                        if ($this->isTabSubmitted('Combinations')) {
                            $this->processProductAttribute();
                        }
                        if ($this->isTabSubmitted('Prices')) {
                            $this->processSpecificPrice();
                            $this->processSpecificPricePriorities();
                        }
                        if ($this->isTabSubmitted('Customization')) {
                            $this->processCustomizationConfiguration();
                        }
                        if ($this->isTabSubmitted('Attachments')) {
                            $this->processAttachments();
                        }
                        if ($this->isTabSubmitted('Images')) {
                            $this->processImageLegends();
                        }

                        $this->updatePackItems($object);
                        // Disallow avanced stock management if the product become a pack
                        if ($productTypeBefore == Product::PTYPE_SIMPLE && $object->getType() == Product::PTYPE_PACK) {
                            StockAvailable::setProductDependsOnStock((int) $object->id, false);
                        }
                        $this->updateDownloadProduct($object);
                        $this->updateTags(Language::getLanguages(false), $object);

                        if ($this->isProductFieldUpdated('category_box') && !$object->updateCategories(Tools::getArrayValue('categoryBox'))) {
                            $this->errors[] = Tools::displayError('An error occurred while linking the object.').' <b>'.$this->table.'</b> '.Tools::displayError('To categories');
                        }
                    }

                    if ($this->isTabSubmitted('Warehouses')) {
                        $this->processWarehouses();
                    }
                    if (empty($this->errors)) {
                        if (in_array($object->visibility, ['both', 'search']) && Configuration::get('PS_SEARCH_INDEXATION')) {
                            Search::indexation(false, $object->id);
                        }

                        // Save and preview
                        if (Tools::isSubmit('submitAddProductAndPreview')) {
                            $this->redirect_after = $this->getPreviewUrl($object);
                        } else {
                            $page = Tools::getIntValue('page');
                            // Save and stay on same form
                            if ($this->display == 'edit') {
                                $this->confirmations[] = $this->l('Update successful');
                                $this->redirect_after = static::$currentIndex.'&id_product='.(int) $this->object->id
                                    .(Tools::getIsset('id_category') ? '&id_category='.Tools::getIntValue('id_category') : '')
                                    .'&updateproduct&conf=4&key_tab='.Tools::safeOutput(Tools::getValue('key_tab')).($page > 1 ? '&page='.(int) $page : '').'&token='.$this->token;
                            } else {
                                // Default behavior (save and back)
                                $this->redirect_after = static::$currentIndex.(Tools::getIsset('id_category') ? '&id_category='.Tools::getIntValue('id_category') : '').'&conf=4'.($page > 1 ? '&submitFilterproduct='.(int) $page : '').'&token='.$this->token;
                            }
                        }
                    } // if errors : stay on edit page
                    else {
                        $this->display = 'edit';
                    }
                } else {
                    if (!$isAssociatedToShop && isset($combinations) && $combinations) {
                        foreach ($combinations as $idCombination) {
                            $combination = new Combination((int) $idCombination['id_product_attribute']);
                            $combination->delete();
                        }
                    }
                    $this->errors[] = Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.'</b> ('.Db::getInstance()->getMsgError().')';
                }
            } else {
                $this->errors[] = Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.'</b> ('.Tools::displayError('The object cannot be loaded. ').')';
            }

            return $object;
        }
    }

    /**
     * Post treatment for suppliers
     *
     * @throws PrestaShopException
     */
    public function processSuppliers()
    {
        $productId = Tools::getIntValue('id_product');
        if (Tools::getBoolValue('supplier_loaded') && Validate::isLoadedObject($product = new Product($productId))) {

            // Get all id_product_attribute
            $attributes = $product->getAttributesResume($this->context->language->id);
            if (empty($attributes)) {
                $attributes[] = [
                    'id_product_attribute'  => 0,
                    'attribute_designation' => '',
                ];
            }

            // Get all available suppliers
            $suppliers = Supplier::getSuppliers();

            // Get already associated suppliers
            $associatedSuppliers = ProductSupplier::getSupplierCollection($productId);

            $suppliersToAssociate = [];

            // Get new associations
            foreach ($suppliers as $supplier) {
                if (Tools::isSubmit('check_supplier_'.$supplier['id_supplier'])) {
                    $suppliersToAssociate[] = $supplier['id_supplier'];
                }
            }

            // Delete already associated suppliers if needed
            foreach ($associatedSuppliers as $key => $associatedSupplier) {
                /** @var ProductSupplier $associatedSupplier */
                if (!in_array($associatedSupplier->id_supplier, $suppliersToAssociate)) {
                    $associatedSupplier->delete();
                    unset($associatedSuppliers[$key]);
                }
            }

            // Associate suppliers
            foreach ($suppliersToAssociate as $id) {
                $toAdd = true;
                foreach ($associatedSuppliers as $as) {
                    /** @var ProductSupplier $as */
                    if ($id == $as->id_supplier) {
                        $toAdd = false;
                    }
                }

                if ($toAdd) {
                    $productSupplier = new ProductSupplier();
                    $productSupplier->id_product = $productId;
                    $productSupplier->id_product_attribute = 0;
                    $productSupplier->id_supplier = $id;
                    if ($this->context->currency->id) {
                        $productSupplier->id_currency = (int) $this->context->currency->id;
                    } else {
                        $productSupplier->id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
                    }
                    $productSupplier->save();

                    $associatedSuppliers[] = $productSupplier;
                    foreach ($attributes as $attribute) {
                        if ((int) $attribute['id_product_attribute'] > 0) {
                            $productSupplier = new ProductSupplier();
                            $productSupplier->id_product = $productId;
                            $productSupplier->id_product_attribute = (int) $attribute['id_product_attribute'];
                            $productSupplier->id_supplier = $id;
                            $productSupplier->save();
                        }
                    }
                }
            }

            // Manage references and prices
            foreach ($attributes as $attribute) {
                $combinationId = (int)$attribute['id_product_attribute'];
                /** @var ProductSupplier $supplier */
                foreach ($associatedSuppliers as $supplier) {
                    $supplierId = (int)$supplier->id_supplier;

                    $postfix = '_' . $productId . '_' . $combinationId . '_' . $supplierId;
                    $paramReference = 'supplier_reference' . $postfix;
                    $paramPrice = 'product_price' . $postfix;
                    $paramCurrency = 'product_price_currency' . $postfix;
                    $paramName = 'supplier_product_name' . $postfix;
                    $paramComment = 'supplier_comment' . $postfix;

                    $referenceSubmitted = Tools::isSubmit($paramReference);
                    $priceSubmitted = Tools::isSubmit($paramPrice) && Tools::isSubmit($paramCurrency);
                    $nameSubmitted = Tools::isSubmit($paramName);
                    $commentSubmitted = Tools::isSubmit($paramComment);

                    if ($referenceSubmitted || $priceSubmitted || $nameSubmitted || $commentSubmitted) {
                        $reference = Tools::getValue($paramReference);
                        $name = Tools::getValue($paramName);
                        $comment = Tools::getValue($paramComment);
                        $price = Tools::getNumberValue($paramPrice);
                        $idCurrency = Tools::getIntValue($paramCurrency);

                        if ($idCurrency <= 0 || (!($result = Currency::getCurrency($idCurrency)) || empty($result))) {
                            $this->errors[] = Tools::displayError('The selected currency is not valid');
                        }

                        // Save product-supplier data
                        $productSupplierId = (int)ProductSupplier::getIdByProductAndSupplier($productId, $combinationId, $supplierId);

                        if (! $productSupplierId) {
                            $product->addSupplierReference(
                                $supplierId,
                                $combinationId,
                                $reference,
                                $price,
                                $idCurrency,
                                $name,
                                $comment
                            );
                        } else {
                            $productSupplier = new ProductSupplier($productSupplierId);
                            $productSupplier->id_currency = $idCurrency;
                            $productSupplier->product_supplier_price_te = $price;
                            $productSupplier->product_supplier_reference = $reference;
                            $productSupplier->product_supplier_name = $name;
                            $productSupplier->product_supplier_comment = $comment;
                            $productSupplier->update();
                        }

                        // update supplier_reference column in product/combination record
                        if ((int)$product->id_supplier === $supplierId) {
                            $conn = Db::getInstance();
                            $data = ['supplier_reference' => pSQL($reference)];
                            if ($combinationId > 0) {
                                $where = 'id_product = '.$productId.' AND id_product_attribute = '.$combinationId;
                                $conn->update('product_attribute', $data, $where);
                            } else {
                                $conn->update('product', $data, 'id_product = ' . $productId);
                            }
                        }
                    }
                }
            }

            // Manage defaut supplier for product
            $newDefaultSupplier = Tools::getIntValue('default_supplier');
            if ($newDefaultSupplier !== (int)$product->id_supplier) {
                $this->object->setFieldsToUpdate([
                    'id_supplier' => true
                ]);
                $this->object->id_supplier = $newDefaultSupplier;
                $this->object->update();
            }
        }
    }

    /**
     * Process features
     *
     * @throws PrestaShopException
     */
    public function processFeatures()
    {
        if (!Feature::isFeatureActive()) {
            return;
        }

        // load product
        $product = new Product(Tools::getIntValue('id_product'));
        if (!Validate::isLoadedObject($product)) {
            $this->errors[] = Tools::displayError('A product must be created before adding features.');
            return;
        }

        $saveData = $this->getFeatureSaveData();

        // delete all product features
        $product->deleteFeatures();

        // save information
        foreach ($saveData as $featureId => $saveInfo) {

            // associate existing features
            foreach ($saveInfo['associate'] as $featureValueId => $customDisplayable) {
                $product->addFeaturesToDB($featureId, $featureValueId);
                foreach ($customDisplayable as $idLang => $displayable) {
                    $product->addFeaturesDisplayableToDb($featureValueId, $idLang, $displayable);
                }
            }

            // create new features
            foreach ($saveInfo['create'] as $langValues) {
                // Create a new feature value
                $featureValue = new FeatureValue();
                $featureValue->id_feature = $featureId;
                $featureValue->value = $langValues;
                $featureValue->add();

                // Link feature value
                $product->addFeaturesToDB($featureId, $featureValue->id);
            }
        }
    }

    /**
     * Returns information about features saveing
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function getFeatureSaveData()
    {
        $data = [];
        $languages = array_map('intval', Language::getIDs(false));
        $defaultLangId = (int)Configuration::get('PS_LANG_DEFAULT');

        // resolve all new values
        $newValues = [];
        foreach ($_POST as $key => $val) {
            if ($val && preg_match('/^new_feature_value_([0-9]+)_([0-9]+)_([0-9]+)$/i', $key, $match)) {
                $featureId = (int)$match[1];
                $index = (int)$match[2];
                $idLang = (int)$match[3];
                $newValues[$featureId][$index][$idLang] = pSQL($val);
            }
        }

        // prepare list of save instructions
        foreach (Feature::getAll() as $feature) {
            $featureId = (int)$feature->id;

            $associate = [];
            $create = [];

            $featureValues = Tools::getValue('feature_' . $featureId . '_value');
            if (is_array($featureValues)) {
                foreach ($featureValues as $featureValueId) {
                    $featureValueId = (int)$featureValueId;
                    // Important: as the default value has value=0
                    if ($featureValueId > 0) {
                        $custDisplayable = [];
                        foreach ($languages as $langId) {
                            $displayable = Tools::getValue('displayable_' . $featureValueId . '_' . $langId);
                            if ($displayable) {
                                $custDisplayable[$langId] = $displayable;
                            }
                        }
                        $associate[$featureValueId] = $custDisplayable;
                    }
                }
            }

            if (isset($newValues[$featureId]) && $newValues[$featureId]) {
                foreach ($newValues[$featureId] as $newValueEntry) {
                    $defaultValue = reset($newValueEntry);
                    $createEntry = [];
                    foreach ($languages as $langId) {
                        if (isset($newValueEntry[$langId])) {
                            $value = $newValueEntry[$langId];
                        } elseif (isset($newValueEntry[$defaultLangId])) {
                            $value = $newValueEntry[$defaultLangId];
                        } else {
                            $value = $defaultValue;
                        }
                        $createEntry[$langId] = $value;
                    }
                    $create[] = $createEntry;
                }
            }

            if ($associate || $create) {
                if (! $feature->allows_multiple_values) {
                    if ($create) {
                        $associate = [];
                    }
                    if (count($create) > 1) {
                        throw new PrestaShopException("Attempt to create multiple values for single-value feature");
                    }
                    if (count($associate) > 1) {
                        throw new PrestaShopException("Attempt to associate multiple values for single-value feature");
                    }
                }

                $data[$featureId] = [
                    'associate' => $associate,
                    'create' => $create
                ];
            }
        }

        return $data;
    }

    /**
     * Check features
     *
     * @param array $languages
     * @param int $featureId
     *
     * @return int
     *
     * @deprecated 1.3.0
     */
    protected function checkFeatures($languages, $featureId)
    {
        Tools::displayAsDeprecated();
        return 0;
    }

    /**
     * Returns submitted custom feature values
     *
     * @param int $featureId
     * @return array
     * @deprecated 1.4.0
     */
    protected function getCustomFeatureValues($featureId)
    {
        Tools::displayAsDeprecated();
        return [];
    }

    /**
     * Process product attribute
     *
     * @throws PrestaShopException
     */
    public function processProductAttribute()
    {
        // Don't process if the combination fields have not been submitted
        if (!Combination::isFeatureActive() || !Tools::getValue('attribute_combination_list')) {
            return;
        }

        if (Validate::isLoadedObject($product = $this->object)) {
            if ($this->isProductFieldUpdated('attribute_price') && (!Tools::getIsset('attribute_price') || Tools::getIsset('attribute_price') == null)) {
                $this->errors[] = Tools::displayError('The price attribute is required.');
            }
            if (!Tools::getIsset('attribute_combination_list') || Tools::isEmpty(Tools::getValue('attribute_combination_list'))) {
                $this->errors[] = Tools::displayError('You must add at least one attribute.');
            }

            $arrayChecks = [
                'reference'          => 'isReference',
                'supplier_reference' => 'isReference',
                'location'           => 'isReference',
                'ean13'              => 'isEan13',
                'upc'                => 'isUpc',
                'wholesale_price'    => 'isPrice',
                'price'              => 'isPrice',
                'ecotax'             => 'isPrice',
                'quantity'           => 'isInt',
                'weight'             => 'isUnsignedFloat',
                'unit_price_impact'  => 'isPrice',
                'default_on'         => 'isBool',
                'minimal_quantity'   => 'isUnsignedInt',
                'available_date'     => 'isDateFormat',
            ];
            foreach ($arrayChecks as $property => $check) {
                $key = 'attribute_'.$property;
                $value = Tools::getValue($key);

                if ($check === 'isPrice') {
                    $value = Tools::parseNumber($value);
                }
                if ($value !== false
                    && ! call_user_func(['Validate', $check], $value)) {
                    $this->errors[] = sprintf(Tools::displayError('Field %s is not valid'), $property);
                }
            }

            if (!count($this->errors)) {
                if (!isset($_POST['attribute_wholesale_price'])) {
                    $_POST['attribute_wholesale_price'] = 0;
                }
                if (!isset($_POST['attribute_price_impact'])) {
                    $_POST['attribute_price_impact'] = 0;
                }
                if (!isset($_POST['attribute_weight_impact'])) {
                    $_POST['attribute_weight_impact'] = 0;
                }
                if (!isset($_POST['attribute_ecotax'])) {
                    $_POST['attribute_ecotax'] = 0;
                }
                if (Tools::getValue('attribute_default')) {
                    $product->deleteDefaultAttributes();
                }

                // Change existing one
                if (($idProductAttribute = Tools::getIntValue('id_product_attribute')) || ($idProductAttribute = $product->productAttributeExists(Tools::getValue('attribute_combination_list'), false, null, true, true))) {
                    if ($this->hasEditPermission()) {
                        if ($this->isProductFieldUpdated('available_date_attribute') && (Tools::getValue('available_date_attribute') != '' && !Validate::isDateFormat(Tools::getValue('available_date_attribute')))) {
                            $this->errors[] = Tools::displayError('Invalid date format.');
                        } else {
                            $product->updateAttribute(
                                (int) $idProductAttribute,
                                $this->isProductFieldUpdated('attribute_wholesale_price') ? Tools::getNumberValue('attribute_wholesale_price') : null,
                                $this->isProductFieldUpdated('attribute_price_impact') ? Tools::getNumberValue('attribute_price') * Tools::getNumberValue('attribute_price_impact') : null,
                                $this->isProductFieldUpdated('attribute_weight_impact') ? Tools::getNumberValue('attribute_weight') * Tools::getNumberValue('attribute_weight_impact') : null,
                                $this->isProductFieldUpdated('attribute_unit_impact') ? Tools::getNumberValue('attribute_unity') * Tools::getNumberValue('attribute_unit_impact') : null,
                                $this->isProductFieldUpdated('attribute_ecotax') ? Tools::getNumberValue('attribute_ecotax') : null,
                                Tools::getValue('id_image_attr'),
                                Tools::getValue('attribute_reference'),
                                Tools::getValue('attribute_ean13'),
                                $this->isProductFieldUpdated('attribute_default') ? Tools::getValue('attribute_default') : null,
                                Tools::getValue('attribute_location'),
                                Tools::getValue('attribute_upc'),
                                $this->isProductFieldUpdated('attribute_minimal_quantity') ? Tools::getValue('attribute_minimal_quantity') : null,
                                $this->isProductFieldUpdated('available_date_attribute') ? Tools::getValue('available_date_attribute') : null,
                                false
                            );
                            StockAvailable::setProductDependsOnStock((int) $product->id, $product->depends_on_stock, null, (int) $idProductAttribute);
                            StockAvailable::setProductOutOfStock((int) $product->id, $product->out_of_stock, null, (int) $idProductAttribute);
                        }
                    } else {
                        $this->errors[] = Tools::displayError('You do not have permission to add this.');
                    }
                } // Add new
                else {
                    if ($this->hasAddPermission()) {
                        if ($product->productAttributeExists(Tools::getValue('attribute_combination_list'))) {
                            $this->errors[] = Tools::displayError('This combination already exists.');
                        } else {
                            $idProductAttribute = $product->addCombinationEntity(
                                Tools::getNumberValue('attribute_wholesale_price'),
                                Tools::getNumberValue('attribute_price') * Tools::getNumberValue('attribute_price_impact'),
                                Tools::getNumberValue('attribute_weight') * Tools::getNumberValue('attribute_weight_impact'),
                                Tools::getNumberValue('attribute_unity') * Tools::getNumberValue('attribute_unit_impact'),
                                Tools::getNumberValue('attribute_ecotax'),
                                0,
                                Tools::getValue('id_image_attr'),
                                Tools::getValue('attribute_reference'),
                                null,
                                Tools::getValue('attribute_ean13'),
                                Tools::getValue('attribute_default'),
                                Tools::getValue('attribute_location'),
                                Tools::getValue('attribute_upc'),
                                Tools::getValue('attribute_minimal_quantity'),
                                [],
                                Tools::getValue('available_date_attribute')
                            );
                            StockAvailable::setProductDependsOnStock((int) $product->id, $product->depends_on_stock, null, (int) $idProductAttribute);
                            StockAvailable::setProductOutOfStock((int) $product->id, $product->out_of_stock, null, (int) $idProductAttribute);
                        }
                    } else {
                        $this->errors[] = Tools::displayError('You do not have permission to').'<hr>'.Tools::displayError('edit here.');
                    }
                }
                if (!count($this->errors)) {
                    $combination = new Combination((int) $idProductAttribute);
                    $combination->setAttributes(Tools::getValue('attribute_combination_list'));

                    // images could be deleted before
                    $idImages = Tools::getValue('id_image_attr');
                    if (!empty($idImages)) {
                        $combination->setImages($idImages);
                    }

                    $product->checkDefaultAttributes();
                    if (Tools::getValue('attribute_default')) {
                        Product::updateDefaultAttribute((int) $product->id);
                        if (isset($idProductAttribute)) {
                            $product->cache_default_attribute = (int) $idProductAttribute;
                        }

                        if ($availableDate = Tools::getValue('available_date_attribute')) {
                            $product->setAvailableDate($availableDate);
                        } else {
                            $product->setAvailableDate();
                        }
                    }
                }
            }
        }
    }

    /**
     * Process price addition
     *
     * @throws PrestaShopException
     */
    public function processSpecificPrice()
    {
        if (! Tools::getIsset('submitSpecificPriceForm')) {
            return;
        }

        $idSpecificPrice = Tools::getIntValue('sp_id_specific_price');

        $idProduct = Tools::getIntValue('id_product');
        $idProductAttribute = Tools::getValue('sp_id_product_attribute');
        $idShop = Tools::getValue('sp_id_shop');
        $idCurrency = Tools::getValue('sp_id_currency');
        $idCountry = Tools::getValue('sp_id_country');
        $idGroup = Tools::getValue('sp_id_group');
        $idCustomer = Tools::getValue('sp_id_customer');
        $price = Tools::getValue('leave_bprice') ? -1 : Tools::getNumberValue('sp_price');
        $fromQuantity = Tools::getValue('sp_from_quantity');
        $reduction = Tools::getNumberValue('sp_reduction');
        $reductionTax = Tools::getNumberValue('sp_reduction_tax');
        $reductionType = !$reduction ? 'amount' : Tools::getValue('sp_reduction_type');
        $reductionType = $reductionType == '-' ? 'amount' : $reductionType;
        $from = Tools::getValue('sp_from');
        if (!$from) {
            $from = '0000-00-00 00:00:00';
        }
        $to = Tools::getValue('sp_to');
        if (!$to) {
            $to = '0000-00-00 00:00:00';
        }

        if ($idSpecificPrice) {
            $specificPrice = new SpecificPrice($idSpecificPrice);
            if (! Validate::isLoadedObject($specificPrice)) {
                $this->errors = Tools::displayError('Specific price not found');
                return;
            }
        } else {
            $specificPrice = new SpecificPrice();
        }

        if (($price == -1) && ($reduction == 0)) {
            $this->errors[] = Tools::displayError('No reduction value has been submitted');
        } elseif ($to != '0000-00-00 00:00:00' && strtotime($to) < strtotime($from)) {
            $this->errors[] = Tools::displayError('Invalid date range');
        } elseif ($reductionType == 'percentage' && ($reduction <= 0 || $reduction > 100)) {
            $this->errors[] = Tools::displayError('Submitted reduction value (0-100) is out-of-range');
        } elseif ($this->_validateSpecificPrice($idShop, $idCurrency, $idCountry, $idGroup, $idCustomer, $price, $fromQuantity, $reduction, $reductionType, $from, $to, $idProductAttribute, $idSpecificPrice)) {
            $specificPrice->id_product = (int) $idProduct;
            $specificPrice->id_product_attribute = (int) $idProductAttribute;
            $specificPrice->id_shop = (int) $idShop;
            $specificPrice->id_currency = (int) ($idCurrency);
            $specificPrice->id_country = (int) ($idCountry);
            $specificPrice->id_group = (int) ($idGroup);
            $specificPrice->id_customer = (int) $idCustomer;
            $specificPrice->price = (float) ($price);
            $specificPrice->from_quantity = (int) ($fromQuantity);
            $specificPrice->reduction = (float) ($reductionType == 'percentage' ? $reduction / 100 : $reduction);
            $specificPrice->reduction_tax = $reductionTax;
            $specificPrice->reduction_type = $reductionType;
            $specificPrice->from = $from;
            $specificPrice->to = $to;
            if (!$specificPrice->save()) {
                $this->errors[] = Tools::displayError('An error occurred while updating the specific price.');
            }
        }
    }

    /**
     * Process specific price priorities
     *
     * @throws PrestaShopException
     */
    public function processSpecificPricePriorities()
    {
        if (!($obj = $this->loadObject())) {
            return;
        }
        if (!$priorities = Tools::getValue('specificPricePriority')) {
            $this->errors[] = Tools::displayError('Please specify priorities.');
        } elseif (Tools::isSubmit('specificPricePriorityToAll')) {
            if (!SpecificPrice::setPriorities($priorities)) {
                $this->errors[] = Tools::displayError('An error occurred while updating priorities.');
            } else {
                $this->confirmations[] = $this->l('The price rule has successfully updated');
            }
        } elseif (!SpecificPrice::setSpecificPriority((int) $obj->id, $priorities)) {
            $this->errors[] = Tools::displayError('An error occurred while setting priorities.');
        }
    }

    /**
     * Process customization configuration
     *
     * @throws PrestaShopException
     */
    public function processCustomizationConfiguration()
    {
        $product = $this->object;
        // Get the number of existing customization fields ($product->text_fields is the updated value, not the existing value)
        $currentCustomization = $product->getCustomizationFieldIds();
        $filesCount = 0;
        $textCount = 0;
        if (is_array($currentCustomization)) {
            foreach ($currentCustomization as $field) {
                if ($field['type'] == 1) {
                    $textCount++;
                } else {
                    $filesCount++;
                }
            }
        }

        if (!$product->createLabels((int) $product->uploadable_files - $filesCount, (int) $product->text_fields - $textCount)) {
            $this->errors[] = Tools::displayError('An error occurred while creating customization fields.');
        }
        if (!count($this->errors) && !$product->updateLabels()) {
            $this->errors[] = Tools::displayError('An error occurred while updating customization fields.');
        }
        $product->customizable = ($product->uploadable_files > 0 || $product->text_fields > 0) ? 1 : 0;
        if (($product->uploadable_files != $filesCount || $product->text_fields != $textCount) && !count($this->errors) && !$product->update()) {
            $this->errors[] = Tools::displayError('An error occurred while updating the custom configuration.');
        }
    }

    /**
     * Attach an existing attachment to the product
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processAttachments()
    {
        if ($id = Tools::getIntValue($this->identifier)) {
            $attachments = trim(Tools::getValue('arrayAttachments'), ',');
            $attachments = explode(',', $attachments);
            if (!Attachment::attachToProduct($id, $attachments)) {
                $this->errors[] = Tools::displayError('An error occurred while saving product attachments.');
            }
        }
    }

    /**
     * Process image legends
     *
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function processImageLegends()
    {
        if (Tools::getValue('key_tab') == 'Images' && Tools::getValue('submitAddproductAndStay') == 'update_legends' && Validate::isLoadedObject($product = new Product(Tools::getIntValue('id_product')))) {
            $conn = Db::getInstance();
            $idImage = Tools::getIntValue('id_caption');
            $idImages = [];
            if ($idImage) {
                // Caption is for one image only.
                $idImages[] = $idImage;
            } else {
                // Same caption for all images.
                $images = Image::getImages(null, $product->id);
                foreach ($images as $image) {
                    $idImages[] = $image['id_image'];
                }
            }
            $languageIds = Language::getIDs(false);
            foreach ($_POST as $key => $val) {
                if (preg_match('/^legend_([0-9]+)/', $key, $match)) {
                    foreach ($languageIds as $idLang) {
                        if ($idLang == $match[1]) {
                            foreach ($idImages as $idImage) {
                                $conn->insert('image_lang', [
                                    'id_image'  => (int)$idImage,
                                    'id_lang'   => (int)$idLang,
                                    'legend'    => pSQL($val),
                                ], false, true, Db::ON_DUPLICATE_KEY);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Post treatment for warehouses
     *
     * @throws PrestaShopException
     */
    public function processWarehouses()
    {
        if (Tools::getIntValue('warehouse_loaded') === 1 && Validate::isLoadedObject($product = new Product((int) $idProduct = Tools::getIntValue('id_product')))) {
            // Get all id_product_attribute
            $attributes = $product->getAttributesResume($this->context->language->id);
            if (empty($attributes)) {
                $attributes[] = [
                    'id_product_attribute'  => 0,
                    'attribute_designation' => '',
                ];
            }

            // Get all available warehouses
            $warehouses = Warehouse::getWarehouses(true);

            // Get already associated warehouses
            $associatedWarehousesCollection = WarehouseProductLocation::getCollection($product->id);

            $elementsToManage = [];

            // get form inforamtion
            foreach ($attributes as $attribute) {
                foreach ($warehouses as $warehouse) {
                    $key = $warehouse['id_warehouse'].'_'.$product->id.'_'.$attribute['id_product_attribute'];

                    // get elements to manage
                    if (Tools::isSubmit('check_warehouse_'.$key)) {
                        $location = Tools::getValue('location_warehouse_'.$key, '');
                        $elementsToManage[$key] = $location;
                    }
                }
            }

            // Delete entry if necessary
            foreach ($associatedWarehousesCollection as $awc) {
                /** @var WarehouseProductLocation $awc */
                if (!array_key_exists($awc->id_warehouse.'_'.$awc->id_product.'_'.$awc->id_product_attribute, $elementsToManage)) {
                    $awc->delete();
                }
            }

            // Manage locations
            foreach ($elementsToManage as $key => $location) {
                $params = explode('_', $key);

                $wplId = (int) WarehouseProductLocation::getIdByProductAndWarehouse((int) $params[1], (int) $params[2], (int) $params[0]);

                if (empty($wplId)) {
                    //create new record
                    $warehouseLocationEntity = new WarehouseProductLocation();
                    $warehouseLocationEntity->id_product = (int) $params[1];
                    $warehouseLocationEntity->id_product_attribute = (int) $params[2];
                    $warehouseLocationEntity->id_warehouse = (int) $params[0];
                    $warehouseLocationEntity->location = pSQL($location);
                    $warehouseLocationEntity->save();
                } else {
                    $warehouseLocationEntity = new WarehouseProductLocation((int) $wplId);

                    $location = pSQL($location);

                    if ($location != $warehouseLocationEntity->location) {
                        $warehouseLocationEntity->location = pSQL($location);
                        $warehouseLocationEntity->update();
                    }
                }
            }
            StockAvailable::synchronize((int) $idProduct);
        }
    }

    /**
     * Initialize content
     *
     * @param string|null $token
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent($token = null)
    {
        if ($this->display == 'edit' || $this->display == 'add') {
            $this->fields_form = [];

            // Check if Module
            if (substr($this->tab_display, 0, 6) == 'Module') {
                $this->tab_display_module = strtolower(substr($this->tab_display, 6, mb_strlen($this->tab_display) - 6));
                $this->tab_display = 'Modules';
            }
            if (method_exists($this, 'initForm'.$this->tab_display)) {
                $this->tpl_form = strtolower($this->tab_display).'.tpl';
            }

            if ($this->ajax) {
                $this->content_only = true;
            } else {
                $productTabs = [];

                // tab_display defines which tab to display first
                if (!method_exists($this, 'initForm'.$this->tab_display)) {
                    $this->tab_display = $this->default_tab;
                }

                // get permissions levels for current employee
                $permissions = $this->getPermLevels();

                $advancedStockManagementActive = Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT');
                foreach ($this->available_tabs as $productTab => $value) {
                    // if it's the warehouses tab and advanced stock management is disabled, continue
                    if ($advancedStockManagementActive == 0 && $productTab == 'Warehouses') {
                        continue;
                    }

                    $productTabs[$productTab] = [
                        'id'       => $productTab,
                        'hidden'   => $permissions[$productTab] == static::TAB_PERMISSION_NONE,
                        'selected' => (strtolower($productTab) == strtolower($this->tab_display) || (isset($this->tab_display_module) && 'module'.$this->tab_display_module == mb_strtolower($productTab))),
                        'name'     => $this->available_tabs_lang[$productTab],
                        'href'     => $this->context->link->getAdminLink('AdminProducts').'&id_product='.Tools::getIntValue('id_product').'&action='.$productTab,
                    ];
                }
                $this->tpl_form_vars['product_tabs'] = $productTabs;
            }
        } else {
            if ($idCategory = (int) $this->id_current_category) {
                static::$currentIndex .= '&id_category='.(int) $this->id_current_category;
            }

            // If products from all categories are displayed, we don't want to use sorting by position
            if (!$idCategory) {
                $this->_defaultOrderBy = $this->identifier;
                if ($this->context->cookie->{$this->table.'Orderby'} == 'position') {
                    unset($this->context->cookie->{$this->table.'Orderby'});
                    unset($this->context->cookie->{$this->table.'Orderway'});
                }
            }
            if (!$idCategory) {
                $idCategory = Configuration::get('PS_ROOT_CATEGORY');
            }
            $this->tpl_list_vars['is_category_filter'] = (bool) $this->id_current_category;

            // Generate category selection tree
            $tree = new HelperTreeCategories('categories-tree', $this->l('Filter by category'));
            $tree->setAttribute('is_category_filter', (bool) $this->id_current_category)
                ->setAttribute('base_url', preg_replace('#&id_category=[0-9]*#', '', static::$currentIndex).'&token='.$this->token)
                ->setInputName('id-category')
                ->setRootCategory(Category::getRootCategory()->id)
                ->setSelectedCategories([(int) $idCategory]);
            $this->tpl_list_vars['category_tree'] = $tree->render();

            // used to build the new url when changing category
            $this->tpl_list_vars['base_url'] = preg_replace('#&id_category=[0-9]*#', '', static::$currentIndex).'&token='.$this->token;
        }
        // @todo module free
        $this->tpl_form_vars['vat_number'] = file_exists(_PS_MODULE_DIR_.'vatnumber/ajax.php');

        parent::initContent();
    }

    /**
     * Render KPIs
     *
     * @return false|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderKpis()
    {
        $time = time();
        $kpis = [];

        /* The data generation is located in AdminStatsControllerCore */

        if (Configuration::get('PS_STOCK_MANAGEMENT')) {
            $helper = new HelperKpi();
            $helper->id = 'box-products-stock';
            $helper->icon = 'icon-archive';
            $helper->color = 'color1';
            $helper->title = $this->l('Out of stock items', null, null, false);
            if (ConfigurationKPI::get('PERCENT_PRODUCT_OUT_OF_STOCK') !== false) {
                $helper->value = ConfigurationKPI::get('PERCENT_PRODUCT_OUT_OF_STOCK');
            }
            $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=percent_product_out_of_stock';
            $helper->tooltip = $this->l('X% of your products for sale are out of stock.', null, null, false);
            $helper->refresh = (bool) (ConfigurationKPI::get('PERCENT_PRODUCT_OUT_OF_STOCK_EXPIRE') < $time);
            $helper->href = $this->context->link->getAdminLink('AdminProducts').'&productFilter_sav!quantity=0&productFilter_active=1&submitFilterproduct=1';
            $kpis[] = $helper->generate();
        }

        $helper = new HelperKpi();
        $helper->id = 'box-avg-gross-margin';
        $helper->icon = 'icon-tags';
        $helper->color = 'color2';
        $helper->title = $this->l('Average Gross Margin %', null, null, false);
        if (ConfigurationKPI::get('PRODUCT_AVG_GROSS_MARGIN') !== false) {
            $helper->value = ConfigurationKPI::get('PRODUCT_AVG_GROSS_MARGIN');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=product_avg_gross_margin';
        $helper->tooltip = $this->l('Gross margin expressed in percentage assesses how cost-effectively you sell your goods. Out of $100, you will retain $X to cover profit and expenses.', null, null, false);
        $helper->refresh = (bool) (ConfigurationKPI::get('PRODUCT_AVG_GROSS_MARGIN_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-8020-sales-catalog';
        $helper->icon = 'icon-beaker';
        $helper->color = 'color3';
        $helper->title = $this->l('Purchased references', null, null, false);
        $helper->subtitle = $this->l('30 days', null, null, false);
        if (ConfigurationKPI::get('8020_SALES_CATALOG') !== false) {
            $helper->value = ConfigurationKPI::get('8020_SALES_CATALOG');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=8020_sales_catalog';
        $helper->tooltip = $this->l('X% of your references have been purchased for the past 30 days', null, null, false);
        $helper->refresh = (bool) (ConfigurationKPI::get('8020_SALES_CATALOG_EXPIRE') < $time);
        if (Module::isInstalled('statsbestproducts')) {
            $helper->href = $this->context->link->getAdminLink('AdminStats').'&module=statsbestproducts&datepickerFrom='.date('Y-m-d', strtotime('-30 days')).'&datepickerTo='.date('Y-m-d');
        }
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-disabled-products';
        $helper->icon = 'icon-off';
        $helper->color = 'color4';
        $helper->href = $this->context->link->getAdminLink('AdminProducts');
        $helper->title = $this->l('Disabled Products', null, null, false);
        if (ConfigurationKPI::get('DISABLED_PRODUCTS') !== false) {
            $helper->value = ConfigurationKPI::get('DISABLED_PRODUCTS');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=disabled_products';
        $helper->refresh = (bool) (ConfigurationKPI::get('DISABLED_PRODUCTS_EXPIRE') < $time);
        $helper->tooltip = $this->l('X% of your products are disabled and not visible to your customers', null, null, false);
        $helper->href = $this->context->link->getAdminLink('AdminProducts').'&productFilter_active=0&submitFilterproduct=1';
        $kpis[] = $helper->generate();

        $helper = new HelperKpiRow();
        $helper->kpis = $kpis;

        return $helper->generate();
    }

    /**
     * Function used to render the list to display for this controller
     *
     * @return string|false
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('preview');
        $this->addRowAction('duplicate');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessProductManufacturers()
    {
        $manufacturers = Manufacturer::getManufacturers();
        $jsonArray = [];

        if ($manufacturers) {
            foreach ($manufacturers as $manufacturer) {
                $tmp = ["optionValue" => $manufacturer['id_manufacturer'], "optionDisplay" => htmlspecialchars(trim($manufacturer['name']))];
                $jsonArray[] = json_encode($tmp);
            }
        }

        $this->ajaxDie('['.implode(',', $jsonArray).']');
    }

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_product'] = [
                'href' => static::$currentIndex.'&addproduct&token='.$this->token,
                'desc' => $this->l('Add new product', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }
        if ($this->display == 'edit') {
            if (($product = $this->loadObject(true)) && $product->isAssociatedToShop()) {
                // adding button for preview this product
                if ($url_preview = $this->getPreviewUrl($product)) {
                    $this->page_header_toolbar_btn['preview'] = [
                        'short'  => $this->l('Preview', null, null, false),
                        'href'   => $url_preview,
                        'desc'   => $this->l('Preview', null, null, false),
                        'target' => true,
                        'class'  => 'previewUrl',
                    ];
                }

                $js = Image::getImages($this->context->language->id, (int) $product->id) ?
                    'confirm_link(\'\', \''.$this->l('This will copy the images too. If you wish to proceed, click "Yes". If not, click "No".', null, true, false).'\', \''.$this->l('Yes', null, true, false).'\', \''.$this->l('No', null, true, false).'\', \''.$this->context->link->getAdminLink('AdminProducts', true).'&id_product='.(int) $product->id.'&duplicateproduct'.'\', \''.$this->context->link->getAdminLink('AdminProducts', true).'&id_product='.(int) $product->id.'&duplicateproduct&noimage=1'.'\')'
                    :
                    'document.location = \''.$this->context->link->getAdminLink('AdminProducts', true).'&id_product='.(int) $product->id.'&duplicateproduct&noimage=1'.'\'';

                // adding button for duplicate this product
                if ($this->hasAddPermission()) {
                    $this->page_header_toolbar_btn['duplicate'] = [
                        'short'   => $this->l('Duplicate', null, null, false),
                        'desc'    => $this->l('Duplicate', null, null, false),
                        'confirm' => 1,
                        'js'      => $js,
                    ];
                }

                // adding button for preview this product statistics
                if (Module::isEnabled('statsmodule')) {
                    $this->page_header_toolbar_btn['stats'] = [
                        'short' => $this->l('Statistics', null, null, false),
                        'href'  => $this->context->link->getAdminLink('AdminStats').'&module=statsproduct&id_product='.(int) $product->id,
                        'desc'  => $this->l('Product sales', null, null, false),
                    ];
                }

                // adding button for delete this product
                if ($this->hasDeletePermission()) {
                    $this->page_header_toolbar_btn['delete'] = [
                        'short'   => $this->l('Delete', null, null, false),
                        'href'    => $this->context->link->getAdminLink('AdminProducts').'&id_product='.(int) $product->id.'&deleteproduct',
                        'desc'    => $this->l('Delete this product', null, null, false),
                        'confirm' => 1,
                        'js'      => 'if (confirm(\''.$this->l('Delete product?', null, true, false).'\')){return true;}else{event.preventDefault();}',
                    ];
                }
            }
        }
        parent::initPageHeaderToolbar();
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function initToolbar()
    {
        parent::initToolbar();
        if ($this->display == 'edit' || $this->display == 'add') {
            $this->toolbar_btn['save'] = [
                'short' => 'Save',
                'href'  => '#',
                'desc'  => $this->l('Save'),
            ];

            $this->toolbar_btn['save-and-stay'] = [
                'short' => 'SaveAndStay',
                'href'  => '#',
                'desc'  => $this->l('Save and stay'),
            ];

            // adding button for adding a new combination in Combination tab
            $this->toolbar_btn['newCombination'] = [
                'short' => 'New combination',
                'desc'  => $this->l('New combination'),
                'class' => 'toolbar-new',
            ];
        } elseif ($this->can_import) {
            $this->toolbar_btn['import'] = [
                'href' => $this->context->link->getAdminLink('AdminImport', true, ['import_type' => AdminImportController::ENTITY_TYPE_PRODUCTS]),
                'desc' => $this->l('Import'),
            ];
        }

        $this->context->smarty->assign('toolbar_scroll', 1);
        $this->context->smarty->assign('show_toolbar', 1);
        $this->context->smarty->assign('toolbar_btn', $this->toolbar_btn);
    }

    /**
     * renderForm contains all necessary initialization needed for all tabs
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        if (!method_exists($this, 'initForm'.$this->tab_display)) {
            return '';
        }

        $product = $this->object;

        // Product for multishop
        $this->context->smarty->assign('bullet_common_field', '');
        if (Shop::isFeatureActive() && $this->display == 'edit') {
            if (Shop::getContext() != Shop::CONTEXT_SHOP) {
                $this->context->smarty->assign(
                    [
                        'display_multishop_checkboxes' => $this->allowEditPerStore(),
                        'multishop_check'              => Tools::getValue('multishop_check'),
                    ]
                );
            }

            if (Shop::getContext() != Shop::CONTEXT_ALL) {
                $this->context->smarty->assign('bullet_common_field', '<i class="icon-circle text-orange"></i>');
                $this->context->smarty->assign('display_common_field', true);
            }
        }

        $this->tpl_form_vars['tabs_preloaded'] = $this->available_tabs;

        $this->tpl_form_vars['product_type'] = Tools::getIntValue('type_product', $product->getType());

        $this->getLanguages();

        $this->tpl_form_vars['id_lang_default'] = Configuration::get('PS_LANG_DEFAULT');

        $this->tpl_form_vars['currentIndex'] = static::$currentIndex;
        $this->tpl_form_vars['display_multishop_checkboxes'] = ($this->allowEditPerStore() && Shop::getContext() != Shop::CONTEXT_SHOP && $this->display == 'edit');
        $this->fields_form = [''];

        $this->tpl_form_vars['token'] = $this->token;
        $this->tpl_form_vars['combinationImagesJs'] = $this->getCombinationImagesJs();
        $this->tpl_form_vars['PS_ALLOW_ACCENTED_CHARS_URL'] = (int) Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
        $this->tpl_form_vars['post_data'] = json_encode($_POST);
        $this->tpl_form_vars['save_error'] = !empty($this->errors);
        $this->tpl_form_vars['mod_evasive'] = Tools::apacheModExists('evasive');
        $this->tpl_form_vars['mod_security'] = Tools::apacheModExists('security');
        $this->tpl_form_vars['ps_force_friendly_product'] = Configuration::get('PS_FORCE_FRIENDLY_PRODUCT');

        // autoload rich text editor (tiny mce)
        $this->tpl_form_vars['tinymce'] = true;
        $iso = $this->context->language->iso_code;
        $this->tpl_form_vars['iso'] = file_exists(_PS_ROOT_DIR_.'/js/tiny_mce/langs/'.$iso.'.js') ? $iso : 'en';
        $this->tpl_form_vars['path_css'] = _THEME_CSS_DIR_;
        $this->tpl_form_vars['ad'] = __PS_BASE_URI__.basename(_PS_ADMIN_DIR_);

        if (Validate::isLoadedObject(($this->object))) {
            $idProduct = (int) $this->object->id;
        } else {
            $idProduct = Tools::getIntValue('id_product');
        }

        $page = Tools::getIntValue('page');

        $this->tpl_form_vars['form_action'] = $this->context->link->getAdminLink('AdminProducts').'&'.($idProduct ? 'updateproduct&id_product='.(int) $idProduct : 'addproduct').($page > 1 ? '&page='.(int) $page : '');
        $this->tpl_form_vars['id_product'] = $idProduct;

        // Transform configuration option 'upload_max_filesize' in octets
        $uploadMaxFilesize = Tools::getOctets(ini_get('upload_max_filesize'));

        // Transform configuration option 'upload_max_filesize' in MegaOctets
        $uploadMaxFilesize = ($uploadMaxFilesize / 1024) / 1024;

        $this->tpl_form_vars['upload_max_filesize'] = $uploadMaxFilesize;
        $this->tpl_form_vars['country_display_tax_label'] = $this->context->country->display_tax_label;
        $this->tpl_form_vars['has_combinations'] = $this->object->hasAttributes();
        $this->product_exists_in_shop = true;

        if ($this->display == 'edit' && Validate::isLoadedObject($product) && Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP && !$product->isAssociatedToShop($this->context->shop->id)) {
            $this->product_exists_in_shop = false;
            if ($this->tab_display == 'Informations') {
                $this->displayWarning($this->l('Warning: The product does not exist in this shop'));
            }

            $defaultProduct = new Product();
            $definition = ObjectModel::getDefinition($product);
            foreach ($definition['fields'] as $fieldName => $field) {
                if (isset($field['shop']) && $field['shop']) {
                    $product->$fieldName = ObjectModel::formatValue($defaultProduct->$fieldName, $field['type']);
                }
            }
        }

        // let's calculate this once for all
        if (!Validate::isLoadedObject($this->object) && Tools::getIntValue('id_product')) {
            $this->errors[] = 'Unable to load object';
        } else {
            $this->_displayDraftWarning($this->object->active);

            // if there was an error while saving, we don't want to lose posted data
            if (!empty($this->errors)) {
                $this->copyFromPost($this->object, $this->table);
            }

            $this->initPack($this->object);
            $this->{'initForm'.$this->tab_display}($this->object);
            $this->tpl_form_vars['product'] = $this->object;

            if ($this->ajax) {
                if (!isset($this->tpl_form_vars['custom_form'])) {
                    throw new PrestaShopException('custom_form empty for action '.$this->tab_display);
                } else {
                    return $this->tpl_form_vars['custom_form'];
                }
            }
        }

        $parent = parent::renderForm();
        $this->addJqueryPlugin(['autocomplete', 'fancybox', 'typewatch']);

        return $parent;
    }

    /**
     * Get combination images JS
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getCombinationImagesJS()
    {
        /** @var Product $obj */
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $content = 'var combination_images = new Array();';
        if (!$allCombinationImages = $obj->getCombinationImages($this->context->language->id)) {
            return $content;
        }
        foreach ($allCombinationImages as $idProductAttribute => $combinationImages) {
            $i = 0;
            $content .= 'combination_images['.(int) $idProductAttribute.'] = new Array();';
            foreach ($combinationImages as $combinationImage) {
                $content .= 'combination_images['.(int) $idProductAttribute.']['.$i++.'] = '.(int) $combinationImage['id_image'].';';
            }
        }

        return $content;
    }

    /**
     * Display draft warning
     *
     * @param bool $active
     */
    protected function _displayDraftWarning($active)
    {
        $content = '<div class="warn draft" style="'.($active ? 'display:none' : '').'">
				<span>'.$this->l('Your product will be saved as a draft.').'</span>
				<a href="#" class="btn btn-default pull-right" onclick="submitAddProductAndPreview()" ><i class="icon-external-link-sign"></i> '.$this->l('Save and preview').'</a>
				<input type="hidden" name="fakeSubmitAddProductAndPreview" id="fakeSubmitAddProductAndPreview" />
	 		</div>';
        $this->tpl_form_vars['draft_warning'] = $content;
    }

    /**
     * @param Product $product
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function initPack(Product $product)
    {
        $this->tpl_form_vars['is_pack'] = ($product->id && Pack::isPack($product->id)) || Tools::getValue('type_product') == Product::PTYPE_PACK;
        $product->packItems = Pack::getItems($product->id, $this->context->language->id);

        $inputPackItems = '';
        if (Tools::getValue('inputPackItems')) {
            $inputPackItems = Tools::getValue('inputPackItems');
        } else {
            if (is_array($product->packItems)) {
                foreach ($product->packItems as $packItem) {
                    $inputPackItems .= $packItem->pack_quantity.'x'.$packItem->id.'-';
                }
            }
        }
        $this->tpl_form_vars['input_pack_items'] = $inputPackItems;

        $inputNamepackItems = '';
        if (Tools::getValue('namePackItems')) {
            $inputNamepackItems = Tools::getValue('namePackItems');
        } else {
            if (is_array($product->packItems)) {
                foreach ($product->packItems as $packItem) {
                    $inputNamepackItems .= $packItem->pack_quantity.' x '.$packItem->name.'¤';
                }
            }
        }
        $this->tpl_form_vars['input_namepack_items'] = $inputNamepackItems;
    }

    /**
     * @param Product $obj
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormAssociations($obj)
    {
        $product = $obj;
        $data = $this->createTemplate($this->tpl_form);
        // Prepare Categories tree for display in Associations tab
        $root = Category::getRootCategory();
        $defaultCategory = $this->context->cookie->id_category_products_filter ? $this->context->cookie->id_category_products_filter : $this->context->shop->id_category;
        if (!$product->id || !$product->isAssociatedToShop()) {
            $selectedCat = Category::getCategoryInformations(Tools::getArrayValue('categoryBox', [$defaultCategory]));
        } else {
            if (Tools::isSubmit('categoryBox')) {
                $selectedCat = Category::getCategoryInformations(Tools::getArrayValue('categoryBox', [$defaultCategory]));
            } else {
                $selectedCat = Product::getProductCategoriesFull($product->id);
            }
        }

        // Multishop block
        $data->assign('feature_shop_active', Shop::isFeatureActive());

        // Accessories block
        $accessories = Product::getAccessoriesLight($this->context->language->id, $product->id);

        if ($postAccessories = Tools::getValue('inputAccessories')) {
            $postAccessoriesTab = explode('-', $postAccessories);
            foreach ($postAccessoriesTab as $accessoryId) {
                if (!$this->haveThisAccessory($accessoryId, $accessories) && $accessory = Product::getAccessoryById($accessoryId)) {
                    $accessories[] = $accessory;
                }
            }
        }
        $data->assign('accessories', $accessories);

        $product->manufacturer_name = Manufacturer::getNameById($product->id_manufacturer);

        $categories = [];
        foreach ($selectedCat as $key => $category) {
            $categories[] = $key;
        }

        $tree = new HelperTreeCategories('associated-categories-tree', 'Associated categories');
        $tree->setTemplate('tree_associated_categories.tpl')
            ->setHeaderTemplate('tree_associated_header.tpl')
            ->setRootCategory((int) $root->id)
            ->setUseCheckBox(true)
            ->setUseSearch(true)
            ->setSelectedCategories($categories);

        $data->assign(
            [
                'default_category'    => $defaultCategory,
                'selected_cat_ids'    => implode(',', array_keys($selectedCat)),
                'selected_cat'        => $selectedCat,
                'id_category_default' => $product->getDefaultCategory(),
                'category_tree'       => $tree->render(),
                'product'             => $product,
                'link'                => $this->context->link,
                'is_shop_context'     => Shop::getContext() == Shop::CONTEXT_SHOP,
            ]
        );

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * @param int $accessoryId
     * @param array[] $accessories
     *
     * @return bool
     */
    public function haveThisAccessory($accessoryId, $accessories)
    {
        foreach ($accessories as $accessory) {
            if ((int) $accessory['id_product'] == (int) $accessoryId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Product $obj
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormPrices($obj)
    {
        $data = $this->createTemplate($this->tpl_form);
        $product = $obj;
        if ($obj->id) {
            $shops = Shop::getShops();
            $countries = Country::getCountries($this->context->language->id);
            $groups = Group::getGroups($this->context->language->id, true);
            $currencies = Currency::getCurrencies();
            $attributes = $obj->getAttributesGroups((int) $this->context->language->id);
            $combinations = [];
            foreach ($attributes as $attribute) {
                $combinations[$attribute['id_product_attribute']]['id_product_attribute'] = $attribute['id_product_attribute'];
                if (!isset($combinations[$attribute['id_product_attribute']]['attributes'])) {
                    $combinations[$attribute['id_product_attribute']]['attributes'] = '';
                }
                $combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'].' - ';

                $combinations[$attribute['id_product_attribute']]['price'] = Tools::displayPrice(
                    Tools::convertPrice(
                        Product::getPriceStatic((int) $obj->id, false, $attribute['id_product_attribute']),
                        $this->context->currency
                    ),
                    $this->context->currency
                );
            }
            foreach ($combinations as &$combination) {
                $combination['attributes'] = rtrim($combination['attributes'], ' - ');
            }
            $data->assign(
                'specificPriceModificationForm',
                $this->_displaySpecificPriceModificationForm($this->context->currency, $shops, $currencies, $countries, $groups)
            );

            $data->assign('ecotax_tax_excl', (float) $obj->ecotax);
            $this->_applyTaxToEcotax($obj);

            $data->assign(
                [
                    'shops'          => $shops,
                    'admin_one_shop' => count($this->context->employee->getAssociatedShops()) == 1,
                    'currencies'     => $currencies,
                    'countries'      => $countries,
                    'groups'         => $groups,
                    'combinations'   => $combinations,
                    'multi_shop'     => Shop::isFeatureActive(),
                    'link'           => new Link(),
                    'pack'           => new Pack(),
                ]
            );
        } else {
            $this->displayWarning($this->l('You must save this product before adding specific pricing'));
            $product->id_tax_rules_group = (int) Product::getIdTaxRulesGroupMostUsed();
            $data->assign('ecotax_tax_excl', 0);
        }

        $address = new Address();
        $address->id_country = (int) $this->context->country->id;
        $taxRulesGroups = TaxRulesGroup::getTaxRulesGroups(true);
        $taxRates = [
            0 => [
                'id_tax_rules_group' => 0,
                'rates'              => [0],
                'computation_method' => 0,
            ],
        ];

        foreach ($taxRulesGroups as $taxRulesGroup) {
            $idTaxRulesGroup = (int) $taxRulesGroup['id_tax_rules_group'];
            $taxCalculator = TaxManagerFactory::getManager($address, $idTaxRulesGroup)->getTaxCalculator();
            $taxRates[$idTaxRulesGroup] = [
                'id_tax_rules_group' => $idTaxRulesGroup,
                'rates'              => [],
                'computation_method' => (int) $taxCalculator->computation_method,
            ];

            if (isset($taxCalculator->taxes) && count($taxCalculator->taxes)) {
                foreach ($taxCalculator->taxes as $tax) {
                    $taxRates[$idTaxRulesGroup]['rates'][] = (float) $tax->rate;
                }
            } else {
                $taxRates[$idTaxRulesGroup]['rates'][] = 0;
            }
        }

        // prices part
        $data->assign(
            [
                'link'                    => $this->context->link,
                'currency'                => $this->context->currency,
                'tax_rules_groups'        => $taxRulesGroups,
                'taxesRatesByGroup'       => $taxRates,
                'ecotaxTaxRate'           => Tax::getProductEcotaxRate(),
                'tax_exclude_taxe_option' => Tax::excludeTaxeOption(),
                'ps_use_ecotax'           => Configuration::get('PS_USE_ECOTAX'),
            ]
        );

        $product->price = Tools::convertPrice($product->price, $this->context->currency, true, $this->context);
        if ($product->unit_price_ratio != 0) {
            $data->assign('unit_price', Tools::roundPrice($product->price / $product->unit_price_ratio));
        } else {
            $data->assign('unit_price', 0);
        }
        $data->assign('ps_tax', Configuration::get('PS_TAX'));

        $data->assign('country_display_tax_label', $this->context->country->display_tax_label);
        $data->assign(
            [
                'currency', $this->context->currency,
                'product' => $product,
                'token'   => $this->token,
            ]
        );

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * @param Currency $defaultCurrency
     * @param array[] $shops
     * @param array[] $currencies
     * @param array[] $countries
     * @param array[] $groups
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function _displaySpecificPriceModificationForm($defaultCurrency, $shops, $currencies, $countries, $groups)
    {
        /** @var Product $obj */
        if (!($obj = $this->loadObject())) {
            return '';
        }

        $page = Tools::getIntValue('page');
        $content = '';
        $specificPrices = SpecificPrice::getByProductId((int) $obj->id);
        $specificPricePriorities = SpecificPrice::getPriority((int) $obj->id);

        $tmp = [];
        foreach ($shops as $shop) {
            $tmp[$shop['id_shop']] = $shop;
        }
        $shops = $tmp;
        $tmp = [];
        foreach ($currencies as $currency) {
            $tmp[$currency['id_currency']] = $currency;
        }
        $currencies = $tmp;

        $tmp = [];
        foreach ($countries as $country) {
            $tmp[$country['id_country']] = $country;
        }
        $countries = $tmp;

        $tmp = [];
        foreach ($groups as $group) {
            $tmp[$group['id_group']] = $group;
        }
        $groups = $tmp;

        $lengthBefore = strlen($content);
        if (is_array($specificPrices) && count($specificPrices)) {
            $i = 0;
            foreach ($specificPrices as $specificPrice) {
                $idCurrency = $specificPrice['id_currency'] ? $specificPrice['id_currency'] : $defaultCurrency->id;
                if (!isset($currencies[$idCurrency])) {
                    continue;
                }

                $currentSpecificCurrency = $currencies[$idCurrency];
                if ($specificPrice['reduction_type'] == 'percentage') {
                    $impact = '- '.($specificPrice['reduction'] * 100).' %';
                } elseif ($specificPrice['reduction'] > 0) {
                    $impact = '- '.Tools::displayPrice(
                        $specificPrice['reduction'],
                        $currentSpecificCurrency
                    ).' ';
                    if ($specificPrice['reduction_tax']) {
                        $impact .= '('.$this->l('Tax incl.').')';
                    } else {
                        $impact .= '('.$this->l('Tax excl.').')';
                    }
                } else {
                    $impact = '--';
                }

                if ($specificPrice['from'] == '0000-00-00 00:00:00' && $specificPrice['to'] == '0000-00-00 00:00:00') {
                    $period = $this->l('Unlimited');
                } else {
                    $period = $this->l('From').' '.($specificPrice['from'] != '0000-00-00 00:00:00' ? $specificPrice['from'] : '0000-00-00 00:00:00').'<br />'.$this->l('To').' '.($specificPrice['to'] != '0000-00-00 00:00:00' ? $specificPrice['to'] : '0000-00-00 00:00:00');
                }
                if ($specificPrice['id_product_attribute']) {
                    $combination = new Combination((int) $specificPrice['id_product_attribute']);
                    $attributes = $combination->getAttributesName((int) $this->context->language->id);
                    $attributesName = implode(' - ', array_column($attributes, 'name'));
                } else {
                    $attributesName = $this->l('All combinations');
                }

                $rule = new SpecificPriceRule((int) $specificPrice['id_specific_price_rule']);
                $ruleName = ($rule->id ? $rule->name : '--');

                if ($specificPrice['id_customer']) {
                    $customer = new Customer((int) $specificPrice['id_customer']);
                    if (Validate::isLoadedObject($customer)) {
                        $customerFullName = $customer->firstname.' '.$customer->lastname;
                    }
                    unset($customer);
                }

                if (!$specificPrice['id_shop'] || in_array($specificPrice['id_shop'], Shop::getContextListShopID())) {
                    $content .= '
					<tr '.($i % 2 ? 'class="alt_row"' : '').'>
						<td>'.$ruleName.'</td>
						<td>'.$attributesName.'</td>';

                    $canDeleteSpecificPrices = true;
                    if (Shop::isFeatureActive()) {
                        $idShopSp = $specificPrice['id_shop'];
                        $canDeleteSpecificPrices = (count($this->context->employee->getAssociatedShops()) > 1 && !$idShopSp) || $idShopSp;
                        $content .= '
						<td>'.($idShopSp ? $shops[$idShopSp]['name'] : $this->l('All shops')).'</td>';
                    }
                    $price = $specificPrice['price'];
                    $fixedPrice = '--';
                    if ((string) $price !== (string) $obj->price && $price != -1) {
                        $fixedPrice = Tools::displayPrice($price, $currentSpecificCurrency);
                    }
                    $content .= '
						<td>'.($specificPrice['id_currency'] ? $currencies[$specificPrice['id_currency']]['name'] : $this->l('All currencies')).'</td>
						<td>'.($specificPrice['id_country'] ? $countries[$specificPrice['id_country']]['name'] : $this->l('All countries')).'</td>
						<td>'.($specificPrice['id_group'] ? $groups[$specificPrice['id_group']]['name'] : $this->l('All groups')).'</td>
						<td title="'.$this->l('ID:').' '.$specificPrice['id_customer'].'">'.(isset($customerFullName) ? $customerFullName : $this->l('All customers')).'</td>
						<td>'.$fixedPrice.'</td>
						<td>'.$impact.'</td>
						<td>'.$period.'</td>
						<td>'.$specificPrice['from_quantity'].'</td>
                        <td>' .
                            ((!$rule->id) ? '<a name="edit_link" class="edit btn btn-default" href="' . self::$currentIndex . '&id_product=' . Tools::getIntValue('id_product') . '&action=pricesModification&id_specific_price=' . (int)($specificPrice['id_specific_price']) . '&token=' . Tools::getValue('token') . '"><i class="icon-pencil"></i></a>' : '') . '&nbsp;' .
                            ((!$rule->id && $canDeleteSpecificPrices) ? '<a class="btn btn-default" name="delete_link" href="' . static::$currentIndex . '&id_product=' . Tools::getIntValue('id_product') . '&action=deleteSpecificPrice&id_specific_price=' . (int)($specificPrice['id_specific_price']) . '&token=' . Tools::getValue('token') . '"><i class="icon-trash"></i></a>' : '') .
                        '</td>
					</tr>';
                    $i++;
                    unset($customerFullName);
                }
            }
        }
        if ($lengthBefore === strlen($content)) {
            $content .= '
				<tr>
					<td class="text-center" colspan="13"><i class="icon-warning-sign"></i>&nbsp;'.$this->l('No specific prices.').'</td>
				</tr>';
        }

        $content .= '
				</tbody>
			</table>
			</div>
			<div class="panel-footer">
				<a href="'.$this->context->link->getAdminLink('AdminProducts').($page > 1 ? '&submitFilter'.$this->table.'='.(int) $page : '').'" class="btn btn-default"><i class="process-icon-cancel"></i> '.$this->l('Cancel').'</a>
				<button id="product_form_submit_btn"  type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> '.$this->l('Save').'</button>
				<button id="product_form_submit_btn"  type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> '.$this->l('Save and stay').'</button>
			</div>
		</div>';

        $content .= '
		<script type="text/javascript">
			var currencies = [];
			currencies[0] = [];
			currencies[0]["sign"] = "'.$defaultCurrency->sign.'";
			currencies[0]["format"] = '.intval($defaultCurrency->format).';
			';
        foreach ($currencies as $currency) {
            $content .= '
				currencies['.$currency['id_currency'].'] = new Array();
				currencies['.$currency['id_currency'].']["sign"] = "'.$currency['sign'].'";
				currencies['.$currency['id_currency'].']["format"] = '.intval($currency['format']).';
				';
        }
        $content .= '
		</script>';

        // Not use id_customer
        if ($specificPricePriorities[0] == 'id_customer') {
            unset($specificPricePriorities[0]);
        }
        // Reindex array starting from 0
        $specificPricePriorities = array_values($specificPricePriorities);

        $content .= '<div class="panel">
		<h3>'.$this->l('Priority management').'</h3>
		<div class="alert alert-info">
				'.$this->l('Sometimes one customer can fit into multiple price rules. Priorities allow you to define which rule applies to the customer.').'
		</div>';

        $content .= '
		<div class="form-group">
			<label class="control-label col-lg-3" for="specificPricePriority1">'.$this->l('Priorities').'</label>
			<div class="input-group col-lg-9">
				<select id="specificPricePriority1" name="specificPricePriority[]">
					<option value="id_shop"'.($specificPricePriorities[0] == 'id_shop' ? ' selected="selected"' : '').'>'.$this->l('Shop').'</option>
					<option value="id_currency"'.($specificPricePriorities[0] == 'id_currency' ? ' selected="selected"' : '').'>'.$this->l('Currency').'</option>
					<option value="id_country"'.($specificPricePriorities[0] == 'id_country' ? ' selected="selected"' : '').'>'.$this->l('Country').'</option>
					<option value="id_group"'.($specificPricePriorities[0] == 'id_group' ? ' selected="selected"' : '').'>'.$this->l('Group').'</option>
				</select>
				<span class="input-group-addon"><i class="icon-chevron-right"></i></span>
				<select name="specificPricePriority[]">
					<option value="id_shop"'.($specificPricePriorities[1] == 'id_shop' ? ' selected="selected"' : '').'>'.$this->l('Shop').'</option>
					<option value="id_currency"'.($specificPricePriorities[1] == 'id_currency' ? ' selected="selected"' : '').'>'.$this->l('Currency').'</option>
					<option value="id_country"'.($specificPricePriorities[1] == 'id_country' ? ' selected="selected"' : '').'>'.$this->l('Country').'</option>
					<option value="id_group"'.($specificPricePriorities[1] == 'id_group' ? ' selected="selected"' : '').'>'.$this->l('Group').'</option>
				</select>
				<span class="input-group-addon"><i class="icon-chevron-right"></i></span>
				<select name="specificPricePriority[]">
					<option value="id_shop"'.($specificPricePriorities[2] == 'id_shop' ? ' selected="selected"' : '').'>'.$this->l('Shop').'</option>
					<option value="id_currency"'.($specificPricePriorities[2] == 'id_currency' ? ' selected="selected"' : '').'>'.$this->l('Currency').'</option>
					<option value="id_country"'.($specificPricePriorities[2] == 'id_country' ? ' selected="selected"' : '').'>'.$this->l('Country').'</option>
					<option value="id_group"'.($specificPricePriorities[2] == 'id_group' ? ' selected="selected"' : '').'>'.$this->l('Group').'</option>
				</select>
				<span class="input-group-addon"><i class="icon-chevron-right"></i></span>
				<select name="specificPricePriority[]">
					<option value="id_shop"'.($specificPricePriorities[3] == 'id_shop' ? ' selected="selected"' : '').'>'.$this->l('Shop').'</option>
					<option value="id_currency"'.($specificPricePriorities[3] == 'id_currency' ? ' selected="selected"' : '').'>'.$this->l('Currency').'</option>
					<option value="id_country"'.($specificPricePriorities[3] == 'id_country' ? ' selected="selected"' : '').'>'.$this->l('Country').'</option>
					<option value="id_group"'.($specificPricePriorities[3] == 'id_group' ? ' selected="selected"' : '').'>'.$this->l('Group').'</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-9 col-lg-offset-3">
				<p class="checkbox">
					<label for="specificPricePriorityToAll"><input type="checkbox" name="specificPricePriorityToAll" id="specificPricePriorityToAll" />'.$this->l('Apply to all products').'</label>
				</p>
			</div>
		</div>
		<div class="panel-footer">
				<a href="'.$this->context->link->getAdminLink('AdminProducts').($page > 1 ? '&submitFilter'.$this->table.'='.(int) $page : '').'" class="btn btn-default"><i class="process-icon-cancel"></i> '.$this->l('Cancel').'</a>
				<button id="product_form_submit_btn"  type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> '.$this->l('Save').'</button>
				<button id="product_form_submit_btn"  type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> '.$this->l('Save and stay').'</button>
			</div>
		</div>
		';

        return $content;
    }

    /**
     * Apply tax to eco tax
     *
     * @param Product $product
     *
     * @throws PrestaShopException
     */
    protected function _applyTaxToEcotax($product)
    {
        if ($product->ecotax) {
            $product->ecotax = Tools::roundPrice(
                $product->ecotax * (1 + Tax::getProductEcotaxRate() / 100)
            );
        }
    }

    /**
     * Initialize SEO form
     *
     * @param Product $product
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormSeo($product)
    {
        $data = $this->createTemplate($this->tpl_form);

        $context = $this->context;
        $rewrittenLinks = [];
        if (!Validate::isLoadedObject($product) || !$product->id_category_default) {
            foreach ($this->getLanguages() as $language) {
                $rewrittenLinks[(int) $language['id_lang']] = [$this->l('Unable to determine the preview URL. This product has not been linked with a category, yet.')];
            }
        } else {
            foreach ($this->getLanguages() as $language) {
                $langId = (int)$language['id_lang'];
                $rewrittenLinks[$langId] = explode(
                    '[REWRITE]',
                    $context->link->getProductLink($product->id, '[REWRITE]', (int) $product->id_category_default, $product->ean13, $langId)
                );
            }
        }

        $data->assign(
            [
                'product'               => $product,
                'languages'             => $this->getLanguages(),
                'id_lang'               => $this->context->language->id,
                'ps_ssl_enabled'        => Configuration::get('PS_SSL_ENABLED'),
                'curent_shop_url'       => $this->context->shop->getBaseURL(),
                'default_form_language' => $this->getDefaultFormLanguage(),
                'useRoutes'             => (bool)Configuration::get('PS_REWRITING_SETTINGS'),
                'rewritten_links'       => $rewrittenLinks,
            ]
        );

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * @param Product $product
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormPack($product)
    {
        $data = $this->createTemplate($this->tpl_form);

        // If pack items have been submitted, we want to display them instead of the actuel content of the pack
        // in database. In case of a submit error, the posted data is not lost and can be sent again.
        if (Tools::getValue('namePackItems')) {
            $inputPackItems = Tools::getValue('inputPackItems');
            $inputNamepackItems = Tools::getValue('namePackItems');
            $packItems = $this->getPackItems();
        } else {
            $product->packItems = Pack::getItems($product->id, $this->context->language->id);
            $packItems = $this->getPackItems($product);
            $inputNamepackItems = '';
            $inputPackItems = '';
            foreach ($packItems as $packItem) {
                $inputPackItems .= $packItem['pack_quantity'].'x'.$packItem['id'].'x'.$packItem['id_product_attribute'].'-';
                $inputNamepackItems .= $packItem['pack_quantity'].' x '.$packItem['name'].'¤';
            }
        }

        $data->assign(
            [
                'input_pack_items'     => $inputPackItems,
                'input_namepack_items' => $inputNamepackItems,
                'pack_items'           => $packItems,
                'product_type'         => Tools::getIntValue('type_product', $product->getType()),
            ]
        );

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * Get an array of pack items for display from the product object if specified, else from POST/GET values
     *
     * @param Product|null $product
     *
     * @return array of pack items
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getPackItems($product = null)
    {
        $packItems = [];

        if (!$product) {
            $namesInput = Tools::getValue('namePackItems');
            $idsInput = Tools::getValue('inputPackItems');
            if (!$namesInput || !$idsInput) {
                return [];
            }
            // ids is an array of string with format : QTYxID
            $ids = array_unique(explode('-', $idsInput));
            $names = array_unique(explode('¤', $namesInput));

            if (!empty($ids)) {
                $length = count($ids);
                for ($i = 0; $i < $length; $i++) {
                    if (!empty($ids[$i]) && !empty($names[$i])) {
                        list($packItems[$i]['pack_quantity'], $packItems[$i]['id']) = explode('x', $ids[$i]);
                        $explodedName = explode('x', $names[$i]);
                        $packItems[$i]['name'] = $explodedName[1];
                    }
                }
            }
        } else {
            if (is_array($product->packItems)) {
                $i = 0;
                foreach ($product->packItems as $packItem) {
                    $packItems[$i]['id'] = $packItem->id;
                    $packItems[$i]['pack_quantity'] = $packItem->pack_quantity;
                    $packItems[$i]['name'] = $packItem->name;
                    $packItems[$i]['reference'] = $packItem->reference;
                    $productAttributeId = (int)($packItem->id_pack_product_attribute ?? 0);
                    $packItems[$i]['id_product_attribute'] = $productAttributeId;
                    $cover = $productAttributeId
                        ? Product::getCombinationImageById($productAttributeId, $this->context->language->id)
                        : Product::getCover($packItem->id);
                    $packItems[$i]['image'] = $this->context->link->getImageLink($packItem->link_rewrite, $cover['id_image'] ?? 0, 'home');
                    $i++;
                }
            }
        }

        return $packItems;
    }

    /**
     * Initialize virtual product form
     *
     * @param Product $product
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormVirtualProduct($product)
    {
        $data = $this->createTemplate($this->tpl_form);

        $currency = $this->context->currency;

        $idProductDownload = ProductDownload::getIdFromIdProduct($product->id, false);
        // This might give an empty record, which is fine.
        $productDownload = new ProductDownload($idProductDownload);

        if (!ProductDownload::checkWritableDir()) {
            $this->errors[] = Tools::displayError('Download repository is not writable.');
            $this->tab_display = 'VirtualProduct';
        }

        if ($productDownload->id) {
            // Check the downloadable file.
            $fileNotRight = false;
            if (!$productDownload->filename) {
                $this->errors[] = Tools::displayError('A downloadable file is missing.');
                $fileNotRight = true;
            } else {
                if (!$productDownload->display_filename) {
                    $this->errors[] = Tools::displayError('A file name is required.');
                    $fileNotRight = true;
                }
                if (!$productDownload->checkFile()) {
                    $this->errors[] = Tools::displayError('File on the server is missing, should be').' '._PS_DOWNLOAD_DIR_.$productDownload->filename.'.';
                    $fileNotRight = true;
                }
            }
            if ($fileNotRight) {
                if ($productDownload->active) {
                    $productDownload->active = false;
                    $productDownload->update();
                }

                $this->tab_display = 'VirtualProduct';
            }
        }

        $uploadUrl = $this->context->link->getAdminLink('AdminProducts', true, [
            'ajax' => 1,
            'action' => 'AddVirtualProductFile',
            'id_product' => (int)$product->id,
        ]);
        $virtualProductFileUploader = (new HelperUploader('virtual_product_file_uploader'))
            ->setMultiple(false)
            ->setUrl($uploadUrl)
            ->setPostMaxSize(Tools::getOctets(ini_get('upload_max_filesize')))
            ->setTemplate('virtual_product.tpl');

        if ($productDownload->date_expiration !== '0000-00-00 00:00:00') {
            $productDownload->date_expiration = substr($productDownload->date_expiration, 0, 10);
        } else {
            $productDownload->date_expiration = '';
        }

        $data->assign(
            [
                'product'                       => $product,
                'token'                         => $this->token,
                'currency'                      => $currency,
                'link'                          => $this->context->link,
                'is_file'                       => $productDownload->checkFile(),
                'productDownload'               => $productDownload,
                'virtual_product_file_uploader' => $virtualProductFileUploader->render(),
            ]
        );
        $data->assign($this->tpl_form_vars);
        $this->tpl_form_vars['product'] = $product;
        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * @param Product $obj
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormCustomization($obj)
    {
        $data = $this->createTemplate($this->tpl_form);

        if ($obj->id) {
            if ($this->product_exists_in_shop) {
                $labels = $obj->getCustomizationFields();

                $hasFileLabels = (int) $this->getFieldValue($obj, 'uploadable_files');
                $hasTextLabels = (int) $this->getFieldValue($obj, 'text_fields');

                $languages = $this->getLanguages();
                $data->assign(
                    [
                        'obj'                 => $obj,
                        'table'               => $this->table,
                        'languages'           => $languages,
                        'has_file_labels'     => $hasFileLabels,
                        'display_file_labels' => $this->_displayLabelFields($obj, $labels, $languages, Configuration::get('PS_LANG_DEFAULT'), Product::CUSTOMIZE_FILE),
                        'has_text_labels'     => $hasTextLabels,
                        'display_text_labels' => $this->_displayLabelFields($obj, $labels, $languages, Configuration::get('PS_LANG_DEFAULT'), Product::CUSTOMIZE_TEXTFIELD),
                        'uploadable_files'    => (int) ($this->getFieldValue($obj, 'uploadable_files') ? (int) $this->getFieldValue($obj, 'uploadable_files') : '0'),
                        'text_fields'         => (int) ($this->getFieldValue($obj, 'text_fields') ? (int) $this->getFieldValue($obj, 'text_fields') : '0'),
                    ]
                );
            } else {
                $this->displayWarning($this->l('You must save the product in this shop before adding customization.'));
            }
        } else {
            $this->displayWarning($this->l('You must save this product before adding customization.'));
        }

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * @param ObjectModel $obj
     * @param array $labels
     * @param array $languages
     * @param int $defaultLanguage
     * @param string $type
     *
     * @return string
     *
     * @throws SmartyException
     */
    protected function _displayLabelFields(&$obj, &$labels, $languages, $defaultLanguage, $type)
    {
        $content = '';
        $type = (int) ($type);
        $labelGenerated = [Product::CUSTOMIZE_FILE => (isset($labels[Product::CUSTOMIZE_FILE]) ? count($labels[Product::CUSTOMIZE_FILE]) : 0), Product::CUSTOMIZE_TEXTFIELD => (isset($labels[Product::CUSTOMIZE_TEXTFIELD]) ? count($labels[Product::CUSTOMIZE_TEXTFIELD]) : 0)];

        $fieldIds = $this->_getCustomizationFieldIds($labels, $labelGenerated, $obj);
        if (isset($labels[$type])) {
            foreach ($labels[$type] as $idCustomizationField => $label) {
                $content .= $this->_displayLabelField($label, $languages, $defaultLanguage, $type, $fieldIds, (int) ($idCustomizationField));
            }
        }

        return $content;
    }

    /**
     * @param array $labels
     * @param array $alreadyGenerated
     * @param ObjectModel $obj
     *
     * @return string
     */
    protected function _getCustomizationFieldIds($labels, $alreadyGenerated, $obj)
    {
        $customizableFieldIds = [];
        if (isset($labels[Product::CUSTOMIZE_FILE])) {
            foreach ($labels[Product::CUSTOMIZE_FILE] as $idCustomizationField => $label) {
                $customizableFieldIds[] = 'label_'.Product::CUSTOMIZE_FILE.'_'.(int) ($idCustomizationField);
            }
        }
        if (isset($labels[Product::CUSTOMIZE_TEXTFIELD])) {
            foreach ($labels[Product::CUSTOMIZE_TEXTFIELD] as $idCustomizationField => $label) {
                $customizableFieldIds[] = 'label_'.Product::CUSTOMIZE_TEXTFIELD.'_'.(int) ($idCustomizationField);
            }
        }
        $j = 0;
        for ($i = $alreadyGenerated[Product::CUSTOMIZE_FILE]; $i < (int) ($this->getFieldValue($obj, 'uploadable_files')); $i++) {
            $customizableFieldIds[] = 'newLabel_'.Product::CUSTOMIZE_FILE.'_'.$j++;
        }
        $j = 0;
        for ($i = $alreadyGenerated[Product::CUSTOMIZE_TEXTFIELD]; $i < (int) ($this->getFieldValue($obj, 'text_fields')); $i++) {
            $customizableFieldIds[] = 'newLabel_'.Product::CUSTOMIZE_TEXTFIELD.'_'.$j++;
        }

        return implode('¤', $customizableFieldIds);
    }

    /**
     * @param array $label
     * @param array $languages
     * @param int $defaultLanguage
     * @param string $type
     * @param string $fieldIds
     * @param int $idCustomizationField
     *
     * @return string
     *
     * @throws SmartyException
     */
    protected function _displayLabelField(&$label, $languages, $defaultLanguage, $type, $fieldIds, $idCustomizationField)
    {
        foreach ($languages as $language) {
            $inputValue[$language['id_lang']] = (isset($label[(int) ($language['id_lang'])])) ? $label[(int) ($language['id_lang'])]['name'] : '';
        }

        $required = (isset($label[(int) ($language['id_lang'])])) ? $label[(int) ($language['id_lang'])]['required'] : false;

        $template = $this->context->smarty->createTemplate(
            'controllers/products/input_text_lang.tpl',
            $this->context->smarty
        );

        return '<div class="form-group">'
            .'<div class="col-lg-6">'
            .$template->assign(
                [
                    'languages'   => $languages,
                    'input_name'  => 'label_'.$type.'_'.(int) ($idCustomizationField),
                    'input_value' => $inputValue,
                ]
            )->fetch()
            .'</div>'
            .'<div class="col-lg-6">'
            .'<div class="checkbox">'
            .'<label for="require_'.$type.'_'.(int) ($idCustomizationField).'">'
            .'<input type="checkbox" name="require_'.$type.'_'.(int) ($idCustomizationField).'" id="require_'.$type.'_'.(int) ($idCustomizationField).'" value="1" '.($required ? 'checked="checked"' : '').'/>'
            .$this->l('Required')
            .'</label>'
            .'</div>'
            .'</div>'
            .'</div>';
    }

    /**
     * @param Product $obj
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormAttachments($obj)
    {
        $data = $this->createTemplate($this->tpl_form);
        $data->assign('default_form_language', $this->getDefaultFormLanguage());

        if ($obj->id) {
            if ($this->product_exists_in_shop) {
                $attachmentName = [];
                $attachmentDescription = [];
                foreach ($this->getLanguages() as $language) {
                    $attachmentName[$language['id_lang']] = '';
                    $attachmentDescription[$language['id_lang']] = '';
                }

                $isoTinyMce = $this->context->language->iso_code;
                $isoTinyMce = (file_exists(_PS_ROOT_DIR_.'/js/tiny_mce/langs/'.$isoTinyMce.'.js') ? $isoTinyMce : 'en');

                $attachmentUploader = new HelperUploader('attachment_file');
                $attachmentUploader
                    ->setMultiple(false)
                    ->setUseAjax(true)
                    ->setUrl($this->context->link->getAdminLink('AdminProducts').'&ajax=1&id_product='.(int) $obj->id.'&action=AddAttachment')
                    ->setPostMaxSize((Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE') * 1024 * 1024))
                    ->setTemplate('attachment_ajax.tpl');

                $data->assign(
                    [
                        'obj'                    => $obj,
                        'table'                  => $this->table,
                        'ad'                     => __PS_BASE_URI__.basename(_PS_ADMIN_DIR_),
                        'iso_tiny_mce'           => $isoTinyMce,
                        'languages'              => $this->getLanguages(),
                        'id_lang'                => $this->context->language->id,
                        'attach1'                => Attachment::getAttachments($this->context->language->id, $obj->id, true),
                        'attach2'                => Attachment::getAttachments($this->context->language->id, $obj->id, false),
                        'attachment_name'        => $attachmentName,
                        'attachment_description' => $attachmentDescription,
                        'attachment_uploader'    => $attachmentUploader->render(),
                    ]
                );
            } else {
                $this->displayWarning($this->l('You must save the product in this shop before adding attachements.'));
            }
        } else {
            $this->displayWarning($this->l('You must save this product before adding attachements.'));
        }

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * @param Product $product
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormInformations($product)
    {
        $data = $this->createTemplate($this->tpl_form);

        $currency = $this->context->currency;

        $data->assign(
            [
                'languages'             => $this->getLanguages(),
                'default_form_language' => $this->getDefaultFormLanguage(),
                'currency'              => $currency,
            ]
        );
        $this->object = $product;
        //$this->display = 'edit';
        $data->assign('product_name_redirected', Product::getProductName((int) $product->id_product_redirected, null, (int) $this->context->language->id));

        $productProps = [];
        // global informations
        array_push($productProps, 'reference', 'ean13', 'upc', 'available_for_order', 'show_price', 'online_only', 'id_manufacturer');

        // specific / detailled information
        array_push(
            $productProps,
            // physical product
            'width', 'height', 'weight', 'active',
            // virtual product
            'is_virtual', 'cache_default_attribute',
            // customization
            'uploadable_files', 'text_fields'
        );
        // prices
        array_push(
            $productProps,
            'price',
            'wholesale_price',
            'id_tax_rules_group',
            'unit_price_ratio',
            'on_sale',
            'unity',
            'additional_shipping_cost',
            'available_now',
            'available_later',
            'available_date'
        );

        if (Configuration::get('PS_USE_ECOTAX')) {
            $productProps[] = 'ecotax';
        }

        foreach ($productProps as $prop) {
            $product->$prop = $this->getFieldValue($product, $prop);
        }

        $images = Image::getImages($this->context->language->id, $product->id);

        if (is_array($images)) {
            foreach ($images as $k => $image) {
                $images[$k]['src'] = $this->context->link->getImageLink($product->link_rewrite[$this->context->language->id], (int)$image['id_image'], 'small');
            }
            $data->assign('images', $images);
        }
        $data->assign('imagesTypes', ImageType::getImagesTypes('products'));

        $product->tags = Tag::getProductTags($product->id);

        $data->assign('product_type', Tools::getIntValue('type_product', $product->getType()));
        $data->assign('is_in_pack', (int) Pack::isPacked($product->id));

        $checkProductAssociationAjax = false;
        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_ALL) {
            $checkProductAssociationAjax = true;
        }

        $shops = Shop::getShops(true);

        if (Shop::isFeatureActive() &&
            Shop::getContext() != Shop::CONTEXT_SHOP &&
            count($shops) > 1 &&
            $this->allowEditPerStore()
        ) {
            $helper = new HelperForm();
            $helper->id = $product->id;
            $helper->table = 'product';
            $helper->identifier = 'id_product';
            $this->context->smarty->assign('product_asso_shops', $helper->renderAssoShop());
        }

        $productId = (int)$product->id;
        if (Shop::isFeatureActive() &&
            Shop::getContext() != Shop::CONTEXT_SHOP &&
            count($shops) > 1 &&
            !$this->allowEditPerStore()
        ) {
            $activePerStore = [];
            foreach ($shops as $shop) {
                $shopId = (int)$shop['id_shop'];
                $activePerStore[$shopId] = [
                    'name' => $shop['name'],
                    'active' => $productId === 0,
                ];
            }
            if ($productId) {
                $active = Db::readOnly()->getArray((new DbQuery())
                    ->select('id_shop, active')
                    ->from('product_shop')
                    ->where('id_product = ' . $productId)
                );
                foreach ($active as $row) {
                    $activePerStore[$row['id_shop']]['active'] = (bool)$row['active'];
                }
            }
            $this->context->smarty->assign('active_per_store', $activePerStore);
        }

        // TinyMCE
        $isoTinyMce = $this->context->language->iso_code;
        $isoTinyMce = (file_exists(_PS_ROOT_DIR_.'/js/tiny_mce/langs/'.$isoTinyMce.'.js') ? $isoTinyMce : 'en');
        $data->assign(
            [
                'ad'                             => dirname($_SERVER['PHP_SELF']),
                'iso_tiny_mce'                   => $isoTinyMce,
                'check_product_association_ajax' => $checkProductAssociationAjax,
                'id_lang'                        => $this->context->language->id,
                'product'                        => $product,
                'token'                          => $this->token,
                'currency'                       => $currency,
                'link'                           => $this->context->link,
            ]
        );
        $data->assign($this->tpl_form_vars);

        $this->tpl_form_vars['product'] = $product;
        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * Initialize shipping form
     *
     * @param Product $obj
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormShipping($obj)
    {
        $data = $this->createTemplate($this->tpl_form);
        $data->assign(
            [
                'product'                   => $obj,
                'ps_dimension_unit'         => Configuration::get('PS_DIMENSION_UNIT'),
                'ps_weight_unit'            => Configuration::get('PS_WEIGHT_UNIT'),
                'carrier_list'              => $this->getCarrierList(),
                'currency'                  => $this->context->currency,
                'country_display_tax_label' => $this->context->country->display_tax_label,
                'shops' => Shop::getShops(),
                'multi_shop'     => Shop::isFeatureActive(),
            ]
        );
        $productId = (int)$obj->id;
        if ($productId && Pack::isPack($productId)) {
            $packWeight = 0.0;
            foreach (Pack::getPackContent($productId) as $packItem) {
                $item = new Product((int)$packItem['id_product']);
                $quantity = (int)$packItem['quantity'];
                $weight = (float)$item->weight;
                $combinationId = (int)$packItem['id_product_attribute'];
                if ($combinationId) {
                    $combination = new Combination($combinationId);
                    $weight += (float)$combination->weight;
                }
                $packWeight += ($weight * $quantity);
            }
            $data->assign('packWeight', sprintf("%.2f", $packWeight));
        }
        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * Get carrier list
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getCarrierList()
    {
        $carrierList = Carrier::getCarriers($this->context->language->id, false, false, false, null, Carrier::ALL_CARRIERS);

        if ($product = $this->loadObject(true)) {
            /** @var Product $product */
            $carrierSelectedList = $product->getCarriers();
            foreach ($carrierList as &$carrier) {
                foreach ($carrierSelectedList as $carrierSelected) {
                    if ($carrierSelected['id_reference'] == $carrier['id_reference']) {
                        $carrier['selected'] = true;
                        continue;
                    }
                }
            }
        }

        return $carrierList;
    }

    /**
     * Ajax process add product image
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessAddProductImage()
    {
        static::$currentIndex = 'index.php?tab=AdminProducts';
        $product = new Product(Tools::getIntValue('id_product'));
        $legends = Tools::getValue('legend');

        if (!is_array($legends)) {
            $legends = (array) $legends;
        }

        if (!Validate::isLoadedObject($product)) {
            $files = [];
            $files[0]['error'] = Tools::displayError('Cannot add image because product creation failed.');
        }

        $imageUploader = new HelperImageUploader('file');
        $imageUploader->setAcceptTypes(['jpeg', 'gif', 'png', 'jpg'])->setMaxSize($this->max_image_size);
        $files = $imageUploader->process();

        foreach ($files as &$file) {
            $image = new Image();
            $image->id_product = (int) ($product->id);
            $image->position = Image::getHighestPosition($product->id) + 1;

            foreach ($legends as $key => $legend) {
                if (!empty($legend)) {
                    $image->legend[(int) $key] = $legend;
                }
            }

            if (!Image::getCover($image->id_product)) {
                $image->cover = 1;
            } else {
                $image->cover = 0;
            }

            if (($validate = $image->validateFieldsLang(false, true)) !== true) {
                $file['error'] = Tools::displayError($validate);
            }

            if (isset($file['error']) && (!is_numeric($file['error']) || $file['error'] != 0)) {
                continue;
            }

            if (!$image->add()) {
                $file['error'] = Tools::displayError('Error while creating additional image');
            } else {
                if (!$newPath = $image->getPathForCreation()) {
                    $file['error'] = Tools::displayError('An error occurred during new folder creation');
                    continue;
                }

                $error = 0;

                if (!ImageManager::resize($file['save_path'], $newPath.'.'.$image->image_format, null, null, 'jpg', false, $error)) {
                    switch ($error) {
                        case ImageManager::ERROR_FILE_NOT_EXIST:
                            $file['error'] = Tools::displayError('An error occurred while copying image, the file does not exist anymore.');
                            break;

                        case ImageManager::ERROR_FILE_WIDTH:
                            $file['error'] = Tools::displayError('An error occurred while copying image, the file width is 0px.');
                            break;

                        case ImageManager::ERROR_MEMORY_LIMIT:
                            $file['error'] = Tools::displayError('An error occurred while copying image, check your memory limit.');
                            break;

                        default:
                            $file['error'] = Tools::displayError('An error occurred while copying image.');
                            break;
                    }
                    continue;
                } else {
                    $imagesTypes = ImageType::getImagesTypes('products');
                    $highDpi = (bool) Configuration::get('PS_HIGHT_DPI');
                    $webpSupport = (bool) ImageManager::generateWebpImages();
                    $tmpName = $file['save_path'];

                    $results = [];
                    foreach ($imagesTypes as $imageType) {
                        # Default format
                        if (!ImageManager::resize(
                            $tmpName,
                            $newPath.'-'.stripslashes($imageType['name']).'.'.$image->image_format,
                            (int) $imageType['width'],
                            (int) $imageType['height'],
                            $image->image_format
                        )) {
                            $results[$image->image_format] = '';
                        } else {
                            if ($highDpi) {
                                $results['2x'.$image->image_format] = ImageManager::resize(
                                    $tmpName,
                                    $newPath.'-'.stripslashes($imageType['name']).'2x.'.$image->image_format,
                                    (int) $imageType['width'] * 2,
                                    (int) $imageType['height'] * 2,
                                    $image->image_format
                                );
                            }

                            if ($webpSupport) {
                                $results['webp'] = ImageManager::resize(
                                    $tmpName,
                                    $newPath.'-'.stripslashes($imageType['name']).'.webp',
                                    (int) $imageType['width'],
                                    (int) $imageType['height'],
                                    'webp'
                                );
                            }

                            if ($webpSupport && $highDpi){
                                $results['2x.webp'] = ImageManager::resize(
                                    $tmpName,
                                    $newPath.'-'.stripslashes($imageType['name']).'2x.webp',
                                    (int) $imageType['width'] * 2,
                                    (int) $imageType['height'] * 2,
                                    'webp'
                                );
                            }
                        }

                        if ((int) Configuration::get('TB_IMAGES_LAST_UPD_PRODUCTS') < $product->id) {
                            Configuration::updateValue('TB_IMAGES_LAST_UPD_PRODUCTS', $product->id);
                        }

                        # Some errors?
                        if ($results) {
                            foreach ($results as $format => $r){
                                if ( ! $r) {
                                    $file['error'] .= '<br/>'.sprintf(
                                        $this->l('An error occurred while generate: %s.%s'),
                                        stripslashes($imageType['name']),
                                        $format
                                    );
                                }
                            }
                        }
                    }
                }

                unlink($file['save_path']);
                //Necesary to prevent hacking
                unset($file['save_path']);
                Hook::triggerEvent('actionWatermark', ['id_image' => $image->id, 'id_product' => $product->id]);

                if (!$image->update()) {
                    $file['error'] .= Tools::displayError('Error while updating image status');
                    continue;
                }

                // Associate image to shop from context
                $shops = Shop::getContextListShopID();
                $image->associateTo($shops);
                $jsonShops = [];

                foreach ($shops as $id_shop) {
                    $jsonShops[$id_shop] = true;
                }

                $file['status'] = 'ok';
                $file['id'] = $image->id;
                $file['position'] = $image->position;
                $file['cover'] = $image->cover;
                $file['legend'] = $image->legend;
                $file['path'] = $image->getExistingImgPath();
                $file['shops'] = $jsonShops;
            }
        }

        $this->ajaxDie(json_encode([$imageUploader->getName() => $files]));
    }

    /**
     * @param Product $obj
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormImages($obj)
    {
        $data = $this->createTemplate($this->tpl_form);

        $data->assign('default_form_language', $this->getDefaultFormLanguage());

        if ($obj->id) {
            if ($this->product_exists_in_shop) {
                $data->assign('product', $this->loadObject());

                $shops = false;
                if (Shop::isFeatureActive()) {
                    $shops = Shop::getShops();
                }

                if ($shops) {
                    foreach ($shops as $key => $shop) {
                        if (!$obj->isAssociatedToShop($shop['id_shop'])) {
                            unset($shops[$key]);
                        }
                    }
                }

                $data->assign('shops', $shops);

                $images = Image::getImages(null, $obj->id);
                foreach ($images as $k => $image) {
                    $images[$k] = new Image($image['id_image']);
                }

                if ($this->context->shop->getContext() == Shop::CONTEXT_SHOP) {
                    $currentShopId = (int) $this->context->shop->id;
                } else {
                    $currentShopId = 0;
                }

                $imageUploader = new HelperImageUploader('file');
                $imageUploader->setMultiple(!(Tools::getUserBrowser() == 'Apple Safari' && Tools::getUserPlatform() == 'Windows'))
                    ->setUseAjax(true)->setUrl($this->context->link->getAdminLink('AdminProducts').'&ajax=1&id_product='.(int) $obj->id.'&action=addProductImage');

                $languages = $this->getLanguages();
                $data->assign(
                    [
                        'countImages'         => count($images),
                        'id_product'          => Tools::getIntValue('id_product'),
                        'id_category_default' => (int) $this->_category->id,
                        'images'              => $images,
                        'iso_lang'            => $languages[0]['iso_code'],
                        'token'               => $this->token,
                        'table'               => $this->table,
                        'max_image_size'      => $this->max_image_size / 1024 / 1024,
                        'currency'            => $this->context->currency,
                        'current_shop_id'     => $currentShopId,
                        'languages'           => $languages,
                        'default_language'    => $this->getDefaultFormLanguage(),
                        'image_uploader'      => $imageUploader->render(),
                        'imageType'           => ImageType::getFormatedName('small'),
                    ]
                );
            } else {
                $this->displayWarning($this->l('You must save the product in this shop before adding images.'));
            }
        } else {
            $this->displayWarning($this->l('You must save this product before adding images.'));
        }

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * Initialize combinations form
     *
     * @param Product $obj
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormCombinations($obj)
    {
        return $this->initFormAttributes($obj);
    }

    /**
     * @param Product $product
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormAttributes($product)
    {
        $data = $this->createTemplate($this->tpl_form);
        if (!Combination::isFeatureActive()) {
            $this->displayWarning(
                $this->l('This feature has been disabled. ').
                ' <a href="index.php?tab=AdminPerformance&token='.Tools::getAdminTokenLite('AdminPerformance').'#featuresDetachables">'.$this->l('Performances').'</a>'
            );
        } elseif (Validate::isLoadedObject($product)) {
            if ($this->product_exists_in_shop) {
                $attributeJs = [];
                $attributes = ProductAttribute::getAttributes($this->context->language->id, true);
                foreach ($attributes as $attribute) {
                    $attributeJs[$attribute['id_attribute_group']][$attribute['id_attribute']] = $attribute['name'];
                }
                foreach ($attributeJs as $k => $ajs) {
                    natsort($attributeJs[$k]);
                }
                $currency = $this->context->currency;
                $data->assign('attributeJs', $attributeJs);
                $data->assign('attributes_groups', AttributeGroup::getAttributesGroups($this->context->language->id));
                $data->assign('currency', $currency);
                $images = Image::getImages($this->context->language->id, $product->id);
                $data->assign('tax_exclude_option', Tax::excludeTaxeOption());
                $data->assign('ps_weight_unit', Configuration::get('PS_WEIGHT_UNIT'));
                $data->assign('ps_use_ecotax', Configuration::get('PS_USE_ECOTAX'));
                $data->assign('field_value_unity', $this->getFieldValue($product, 'unity'));
                $data->assign('reasons', StockMvtReason::getStockMvtReasons($this->context->language->id));
                $data->assign('ps_stock_mvt_reason_default', Configuration::get('PS_STOCK_MVT_REASON_DEFAULT'));
                $data->assign('minimal_quantity', $this->getFieldValue($product, 'minimal_quantity') ? $this->getFieldValue($product, 'minimal_quantity') : 1);
                $data->assign('available_date', ($this->getFieldValue($product, 'available_date') != 0) ? stripslashes(htmlentities($this->getFieldValue($product, 'available_date'), $this->context->language->id)) : '0000-00-00');
                $data->assign('imageType', ImageType::getFormatedName('small'));
                $i = 0;
                foreach ($images as $k => $image) {
                    $images[$k]['obj'] = new Image($image['id_image']);
                    ++$i;
                }
                $data->assign('images', $images);
                $data->assign($this->tpl_form_vars);
                $data->assign(
                    [
                        'list'               => $this->renderListAttributes($product, $currency),
                        'product'            => $product,
                        'defaultReference'   => Tools::nextAvailableReference($product->reference),
                        'id_category'        => $product->getDefaultCategory(),
                        'token_generator'    => Tools::getAdminTokenLite('AdminAttributeGenerator'),
                        'combination_exists' => (Shop::isFeatureActive() && (Shop::getContextShopGroup()->share_stock) && count(AttributeGroup::getAttributesGroups($this->context->language->id)) > 0 && $product->hasAttributes()),
                    ]
                );
            } else {
                $this->displayWarning($this->l('You must save the product in this shop before adding combinations.'));
            }
        } else {
            $data->assign('product', $product);
            $this->displayWarning($this->l('You must save this product before adding combinations.'));
        }

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * @param Product $product
     * @param Currency|array|int $currency
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderListAttributes($product, $currency)
    {
        $this->bulk_actions = ['delete' => ['text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?')]];
        $this->addRowAction('edit');
        $this->addRowAction('default');
        $this->addRowAction('delete');

        $defaultClass = 'highlighted';

        $this->fields_list = [
            'attributes' => ['title' => $this->l('Attribute - value pair'), 'align' => 'left'],
            'price'      => ['title' => $this->l('Impact on price'), 'type' => 'price', 'align' => 'left'],
            'weight'     => ['title' => $this->l('Impact on weight'), 'align' => 'left'],
            'reference'  => ['title' => $this->l('Reference'), 'align' => 'left'],
            'ean13'      => ['title' => $this->l('EAN-13'), 'align' => 'left'],
            'upc'        => ['title' => $this->l('UPC'), 'align' => 'left'],
        ];

        $combArray = [];

        if ($product->id) {
            /* Build attributes combinations */
            $combinations = $product->getAttributeCombinations($this->context->language->id);
            if (is_array($combinations)) {
                $combinationImages = $product->getCombinationImages($this->context->language->id);
                foreach ($combinations as $combination) {
                    $priceToConvert = Tools::convertPrice($combination['price'], $currency);
                    $price = $priceToConvert != 0.0
                        ? Tools::displayPrice($priceToConvert, $currency)
                        : '';

                    $weight = round((float)$combination['weight'], _TB_PRICE_DATABASE_PRECISION_);
                    $weightImpact = $weight != 0.00
                        ? $weight . Configuration::get('PS_WEIGHT_UNIT')
                        : '';

                    $combArray[$combination['id_product_attribute']]['id_product_attribute'] = $combination['id_product_attribute'];
                    $combArray[$combination['id_product_attribute']]['attributes'][] = [$combination['group_name'], $combination['attribute_name'], $combination['id_attribute']];
                    $combArray[$combination['id_product_attribute']]['wholesale_price'] = $combination['wholesale_price'];
                    $combArray[$combination['id_product_attribute']]['price'] = $price;
                    $combArray[$combination['id_product_attribute']]['weight'] = $weightImpact;
                    $combArray[$combination['id_product_attribute']]['unit_impact'] = $combination['unit_price_impact'];
                    $combArray[$combination['id_product_attribute']]['reference'] = $combination['reference'];
                    $combArray[$combination['id_product_attribute']]['ean13'] = $combination['ean13'];
                    $combArray[$combination['id_product_attribute']]['upc'] = $combination['upc'];
                    $combArray[$combination['id_product_attribute']]['id_image'] = isset($combinationImages[$combination['id_product_attribute']][0]['id_image']) ? $combinationImages[$combination['id_product_attribute']][0]['id_image'] : 0;
                    $combArray[$combination['id_product_attribute']]['available_date'] = strtotime($combination['available_date']);
                    $combArray[$combination['id_product_attribute']]['default_on'] = $combination['default_on'];
                }
            }

            foreach ($combArray as $id_product_attribute => $product_attribute) {
                $list = '';

                /* In order to keep the same attributes order */
                asort($product_attribute['attributes']);

                foreach ($product_attribute['attributes'] as $attribute) {
                    $list .= $attribute[0].' - '.$attribute[1].', ';
                }

                $list = rtrim($list, ', ');
                $combArray[$id_product_attribute]['image'] = $product_attribute['id_image'] ? new Image($product_attribute['id_image']) : false;
                $combArray[$id_product_attribute]['available_date'] = $product_attribute['available_date'] ? date('Y-m-d', $product_attribute['available_date']) : '0000-00-00';
                $combArray[$id_product_attribute]['attributes'] = $list;
                $combArray[$id_product_attribute]['name'] = $list;

                if ($product_attribute['default_on']) {
                    $combArray[$id_product_attribute]['class'] = $defaultClass;
                }
            }
        }

        foreach ($this->actions_available as $action) {
            if (!in_array($action, $this->actions) && isset($this->$action) && $this->$action) {
                $this->actions[] = $action;
            }
        }

        $helper = new HelperList();
        $helper->identifier = 'id_product_attribute';
        $helper->table_id = 'combinations-list';
        $helper->token = $this->token;
        $helper->currentIndex = static::$currentIndex;
        $helper->no_link = true;
        $helper->simple_header = true;
        $helper->show_toolbar = false;
        $helper->shopLinkType = $this->shopLinkType;
        $helper->actions = $this->actions;
        $helper->list_skip_actions = $this->list_skip_actions;
        $helper->colorOnBackground = true;
        $helper->override_folder = $this->tpl_folder.'combination/';

        return $helper->generateList($combArray, $this->fields_list);
    }

    /**
     * @param Product $obj
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormQuantities($obj)
    {
        $data = $this->createTemplate($this->tpl_form);
        $data->assign('languages', $this->getLanguages());
        $data->assign('default_form_language', $this->getDefaultFormLanguage());

        if ($obj->id) {
            if ($this->product_exists_in_shop) {
                // Get all id_product_attribute
                $attributes = $obj->getAttributesResume($this->context->language->id);
                if (empty($attributes)) {
                    $attributes[] = [
                        'id_product_attribute'  => 0,
                        'attribute_designation' => '',
                    ];
                }

                // Get available quantities
                $available_quantity = [];
                $product_designation = [];

                foreach ($attributes as $attribute) {
                    // Get available quantity for the current product attribute in the current shop
                    $available_quantity[$attribute['id_product_attribute']] = isset($attribute['id_product_attribute']) && $attribute['id_product_attribute'] ? (int) $attribute['quantity'] : (int) $obj->quantity;
                    // Get all product designation
                    $product_designation[$attribute['id_product_attribute']] = rtrim(
                        $obj->name[$this->context->language->id].' - '.$attribute['attribute_designation'],
                        ' - '
                    );
                }

                $show_quantities = $this->showQuantities();

                $data->assign('ps_stock_management', Configuration::get('PS_STOCK_MANAGEMENT'));
                $data->assign('has_attribute', $obj->hasAttributes());
                // Check if product has combination, to display the available date only for the product or for each combination
                if (Combination::isFeatureActive()) {
                    $data->assign('countAttributes', (int) Db::readOnly()->getValue(
                        (new DbQuery())
                        ->select('COUNT(`id_product`)')
                        ->from('product_attribute')
                        ->where('`id_product` = '.(int) $obj->id)
                    ));
                } else {
                    $data->assign('countAttributes', false);
                }
                // if advanced stock management is active, checks associations
                $advanced_stock_management_warning = false;
                if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $obj->advanced_stock_management) {
                    $p_attributes = Product::getProductAttributesIds($obj->id);
                    $warehouses = [];

                    if (!$p_attributes) {
                        $warehouses[] = Warehouse::getProductWarehouseList($obj->id, 0);
                    }

                    foreach ($p_attributes as $p_attribute) {
                        $ws = Warehouse::getProductWarehouseList($obj->id, $p_attribute['id_product_attribute']);
                        if ($ws) {
                            $warehouses[] = $ws;
                        }
                    }
                    $warehouses = array_unique($warehouses);

                    if (empty($warehouses)) {
                        $advanced_stock_management_warning = true;
                    }
                }
                if ($advanced_stock_management_warning) {
                    $this->displayWarning($this->l('If you wish to use the advanced stock management, you must:'));
                    $this->displayWarning('- '.$this->l('associate your products with warehouses.'));
                    $this->displayWarning('- '.$this->l('associate your warehouses with carriers.'));
                    $this->displayWarning('- '.$this->l('associate your warehouses with the appropriate shops.'));
                }

                $pack_quantity = null;
                // if product is a pack
                if (Pack::isPack($obj->id)) {
                    $items = Pack::getItems((int) $obj->id, Configuration::get('PS_LANG_DEFAULT'));
                    $pack_quantity = PHP_INT_MAX;
                    foreach ($items as $item) {
                        if ($item->pack_quantity > 0) {
                            $itemQuantity = StockAvailable::getQuantityAvailableByProduct($item->id, $item->id_pack_product_attribute);
                            $pack_quantity = min($pack_quantity, floor($itemQuantity / $item->pack_quantity));
                        }
                    }

                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && !Warehouse::getPackWarehouses((int) $obj->id)) {
                        $this->displayWarning($this->l('You must have a common warehouse between this pack and its product.'));
                    }
                }

                $data->assign(
                    [
                        'attributes'              => $attributes,
                        'available_quantity'      => $available_quantity,
                        'pack_quantity'           => $pack_quantity,
                        'stock_management_active' => Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'),
                        'product_designation'     => $product_designation,
                        'product'                 => $obj,
                        'isPack'                  => Pack::isPack($obj->id),
                        'show_quantities'         => $show_quantities,
                        'order_out_of_stock'      => Configuration::get('PS_ORDER_OUT_OF_STOCK'),
                        'pack_stock_type'         => Pack::getGlobalStockTypeSettings(),
                        'token_preferences'       => Tools::getAdminTokenLite('AdminPPreferences'),
                        'token'                   => $this->token,
                        'id_lang'                 => $this->context->language->id,
                    ]
                );
            } else {
                $this->displayWarning($this->l('You must save the product in this shop before managing quantities.'));
            }
        } else {
            $this->displayWarning($this->l('You must save this product before managing quantities.'));
        }

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * @param Product $obj
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormSuppliers($obj)
    {
        $data = $this->createTemplate($this->tpl_form);

        if ($obj->id) {
            if ($this->product_exists_in_shop) {
                // Get all id_product_attribute
                $attributes = $obj->getAttributesResume($this->context->language->id);
                if (empty($attributes)) {
                    $attributes[] = [
                        'id_product'            => $obj->id,
                        'id_product_attribute'  => 0,
                        'attribute_designation' => '',
                    ];
                }

                $product_designation = [];

                foreach ($attributes as $attribute) {
                    $product_designation[$attribute['id_product_attribute']] = rtrim(
                        $obj->name[$this->context->language->id].' - '.$attribute['attribute_designation'],
                        ' - '
                    );
                }

                // Get all available suppliers
                $suppliers = Supplier::getSuppliers();
                $supplierIds = array_map('intval', array_column($suppliers, 'id_supplier'));

                // Get already associated suppliers
                $associated_suppliers = $this->filterSuppliers($supplierIds, ProductSupplier::getSupplierCollection($obj->id));

                // Get already associated suppliers and force to retreive product declinaisons
                $product_supplier_collection = $this->filterSuppliers($supplierIds, ProductSupplier::getSupplierCollection($obj->id, false));

                $default_supplier = 0;
                $supplierNames = [];

                foreach ($suppliers as &$supplier) {
                    $supplierId = (int)$supplier['id_supplier'];
                    $supplierNames[$supplierId] = $supplier['name'];
                    $supplier['is_selected'] = false;
                    $supplier['is_default'] = false;

                    foreach ($associated_suppliers as $associated_supplier) {
                        if ($associated_supplier->id_supplier == $supplierId) {
                            $supplier['is_selected'] = true;

                            if ($obj->id_supplier == $supplierId) {
                                $supplier['is_default'] = true;
                                $default_supplier = $supplierId;
                            }
                        }
                    }
                }

                $data->assign(
                    [
                        'attributes'                      => $attributes,
                        'suppliers'                       => $suppliers,
                        'default_supplier'                => $default_supplier,
                        'supplierNames'                   => $supplierNames,
                        'associated_suppliers'            => $associated_suppliers,
                        'associated_suppliers_collection' => $product_supplier_collection,
                        'product_designation'             => $product_designation,
                        'currencies'                      => Currency::getCurrencies(false, true, true),
                        'product'                         => $obj,
                        'link'                            => $this->context->link,
                        'token'                           => $this->token,
                        'id_default_currency'             => Configuration::get('PS_CURRENCY_DEFAULT'),
                    ]
                );
            } else {
                $this->displayWarning($this->l('You must save the product in this shop before managing suppliers.'));
            }
        } else {
            $this->displayWarning($this->l('You must save this product before managing suppliers.'));
        }

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * @param Product $obj
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormWarehouses($obj)
    {
        $data = $this->createTemplate($this->tpl_form);

        if ($obj->id) {
            if ($this->product_exists_in_shop) {
                // Get all id_product_attribute
                $attributes = $obj->getAttributesResume($this->context->language->id);
                if (empty($attributes)) {
                    $attributes[] = [
                        'id_product'            => $obj->id,
                        'id_product_attribute'  => 0,
                        'attribute_designation' => '',
                    ];
                }

                $product_designation = [];

                foreach ($attributes as $attribute) {
                    $product_designation[$attribute['id_product_attribute']] = rtrim(
                        $obj->name[$this->context->language->id].' - '.$attribute['attribute_designation'],
                        ' - '
                    );
                }

                // Get all available warehouses
                $warehouses = Warehouse::getWarehouses(true);

                // Get already associated warehouses
                $associated_warehouses_collection = WarehouseProductLocation::getCollection($obj->id);

                $data->assign(
                    [
                        'attributes'            => $attributes,
                        'warehouses'            => $warehouses,
                        'associated_warehouses' => $associated_warehouses_collection,
                        'product_designation'   => $product_designation,
                        'product'               => $obj,
                        'link'                  => $this->context->link,
                        'token'                 => $this->token,
                    ]
                );
            } else {
                $this->displayWarning($this->l('You must save the product in this shop before managing warehouses.'));
            }
        } else {
            $this->displayWarning($this->l('You must save this product before managing warehouses.'));
        }

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * @param Product $obj
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initFormFeatures($obj)
    {
        $data = $this->createTemplate($this->tpl_form);
        $data->assign('languages', $this->getLanguages());
        $data->assign('defaultFormLanguage', $this->getDefaultFormLanguage());

        if (!Feature::isFeatureActive()) {
            $this->displayWarning($this->l('This feature has been disabled. ').' <a href="index.php?tab=AdminPerformance&token='.Tools::getAdminTokenLite('AdminPerformance').'#featuresDetachables">'.$this->l('Performances').'</a>');
        } else {
            if ($obj->id) {
                if ($this->product_exists_in_shop) {

                    // Get all selected values
                    $selectedFeatureValues = [];

                    foreach ($obj->getFeatures() as $selectedFeature) {
                        $selectedFeatureValues[(int)$selectedFeature['id_feature']][] = (int)$selectedFeature['id_feature_value'];
                    }

                    // Loop through all available features in this store
                    $features = Feature::getFeatures($this->context->language->id, (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP));

                    foreach ($features as &$feature) {

                        $idFeature = (int)$feature['id_feature'];
                        $feature['selected'] = $selectedFeatureValues[$idFeature] ?? [];
                        $feature['displayable_values'] = [];
                        $feature['featureValues'] = FeatureValue::getFeatureValuesWithLang($this->context->language->id, $idFeature);

                        if (count($feature['featureValues'])) {
                            foreach ($feature['featureValues'] as $value) {
                                $idFeatureValue = (int)$value['id_feature_value'];
                                if (array_key_exists($idFeature, $selectedFeatureValues) && in_array($idFeatureValue, $selectedFeatureValues[$idFeature])) {
                                    foreach (FeatureValue::getFeatureValueLang($idFeatureValue, $obj->id) as $featureLang) {
                                        $id_lang = $featureLang['id_lang'];
                                        $feature['displayable_values'][$idFeatureValue][$id_lang] = $featureLang['displayable'];
                                    }
                                }
                            }
                        }
                    }
                    $data->assign('available_features', $features);
                    $data->assign('product', $obj);
                    $data->assign('link', $this->context->link);
                } else {
                    $this->displayWarning($this->l('You must save the product in this shop before adding features.'));
                }
            } else {
                $this->displayWarning($this->l('You must save this product before adding features.'));
            }
        }
        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    /**
     * Ajax process product quantity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessProductQuantity()
    {
        if (!$this->hasEditPermission()) {
            $this->ajaxDie(json_encode(['error' => $this->l('You do not have the right permission')]));
        }
        if (!Tools::getValue('actionQty')) {
            $this->ajaxDie(json_encode(['error' => $this->l('Undefined action')]));
        }

        $product = new Product(Tools::getIntValue('id_product'), true);
        if (! Validate::isLoadedObject($product)) {
            $this->ajaxDie(json_encode(['error' => $this->l('Product not found')]));
        }

        switch (Tools::getValue('actionQty')) {
            case 'depends_on_stock':
                $this->setQuantitiesMethod(Tools::getIntValue('value'), $product);
                break;

            case 'pack_stock_type':
                $value = Tools::getIntValue('value');
                if (! in_array($value, [
                    Pack::STOCK_TYPE_DECREMENT_PACK,
                    Pack::STOCK_TYPE_DECREMENT_PRODUCTS,
                    Pack::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS,
                    Pack::STOCK_TYPE_DECREMENT_GLOBAL_SETTINGS
                ])) {
                    $this->ajaxDie(json_encode(['error' => $this->l('Incorrect value')]));
                }
                $product->pack_stock_type = $value;
                if ($product->depends_on_stock &&
                    !Pack::allUsesAdvancedStockManagement($product->id) &&
                    $product->shouldAdjustPackItemsQuantities()
                ) {
                    $this->ajaxDie(
                        json_encode(
                            [
                                'error' => $this->l('You cannot use this stock management option because:').'<br />'.
                                    $this->l('- advanced stock management is not enabled for these products').'<br />'.
                                    $this->l('- advanced stock management is enabled for the pack'),
                            ]
                        )
                    );
                }

                Product::setPackStockType($product->id, $value);
                break;

            case 'out_of_stock':
                if (Tools::getValue('value') === false) {
                    $this->ajaxDie(json_encode(['error' => $this->l('Undefined value')]));
                }
                if (!in_array(Tools::getIntValue('value'), [0, 1, 2])) {
                    $this->ajaxDie(json_encode(['error' => $this->l('Incorrect value')]));
                }

                StockAvailable::setProductOutOfStock($product->id, Tools::getIntValue('value'));
                break;

            case 'set_qty':
                if (Tools::getValue('value') === false || (!is_numeric(trim(Tools::getValue('value'))))) {
                    $this->ajaxDie(json_encode(['error' => $this->l('Undefined value')]));
                }
                if (! Tools::getIsset('id_product_attribute')) {
                    $this->ajaxDie(json_encode(['error' => $this->l('Undefined id product attribute')]));
                }

                StockAvailable::setQuantity($product->id, Tools::getIntValue('id_product_attribute'), Tools::getIntValue('value'));
                Hook::triggerEvent('actionProductUpdate', ['id_product' => (int) $product->id, 'product' => $product]);
                break;
            case 'advanced_stock_management' :
                if (Tools::getValue('value') === false) {
                    $this->ajaxDie(json_encode(['error' => $this->l('Undefined value')]));
                }
                if (Tools::getIntValue('value') !== 1 && Tools::getIntValue('value') !== 0) {
                    $this->ajaxDie(json_encode(['error' => $this->l('Incorrect value')]));
                }
                if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && Tools::getIntValue('value') === 1) {
                    $this->ajaxDie(json_encode(['error' => $this->l('Not possible if advanced stock management is disabled. ')]));
                }

                $useAdvancedStockManagement = Tools::getBoolValue('value');
                $product->setAdvancedStockManagement($useAdvancedStockManagement);
                if (StockAvailable::dependsOnStock($product->id) && !$useAdvancedStockManagement) {
                    StockAvailable::setProductDependsOnStock($product->id, 0);
                }
                break;

        }
        $this->ajaxDie(json_encode(['error' => false]));
    }

    /**
     * @param int $method
     * @param Product $product
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function setQuantitiesMethod($method, Product $product)
    {
        // validate input
        switch ($method) {
            case static::QUANTITY_METHOD_MANUAL:
                StockAvailable::setProductDependsOnStock($product->id, 0);
                Product::setDynamicPack($product->id, false);
                break;

            case static::QUANTITY_METHOD_ASM:

                // check that advanced stock management feature is enabled
                if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                    $this->ajaxDie(json_encode(['error' => $this->l('Not possible if advanced stock management is disabled.')]));
                }

                // check that advanced stock management is enabled for this product
                if (!$product->advanced_stock_management) {
                    $this->ajaxDie(json_encode(['error' => $this->l('Not possible if advanced stock management is disabled.')]));
                }

                // if the product is pack, and pack quantities settings is set to decrement pack items, then verify that
                // all items use advances stock management feature as well
                if (Pack::isPack($product->id) &&
                    $product->shouldAdjustPackItemsQuantities() &&
                    !Pack::allUsesAdvancedStockManagement($product->id)
                ) {
                    $this->ajaxDie(
                        json_encode(
                            [
                                'error' => (
                                    $this->l('You cannot use advanced stock management for this pack because').'<br />'.
                                    $this->l('- advanced stock management is not enabled for these products').'<br />'.
                                    $this->l('- you have chosen to decrement products quantities.')
                                )
                            ]
                        )
                    );
                }

                // set asm for this product
                StockAvailable::setProductDependsOnStock($product->id, 1);
                Product::setDynamicPack($product->id, false);
                break;

            case static::QUANTITY_METHOD_DYNAMIC_PACK:
                if (!Pack::isPack($product->id)) {
                    $this->ajaxDie(json_encode(['error' => $this->l('Product is not a pack')]));
                }

                StockAvailable::setProductDependsOnStock($product->id, 0);
                Product::setPackStockType($product->id, Pack::STOCK_TYPE_DECREMENT_PRODUCTS);
                Product::setDynamicPack($product->id, true);

                $this->ajaxDie(json_encode([
                    'error' => false,
                    'quantities' => $this->getProductQuantities($product)
                ]));
                break;
            default:
                $this->ajaxDie(json_encode(['error' => $this->l('Incorrect value')]));
        }
    }

    /**
     * AdminProducts display hook
     *
     * @param Product $obj
     *
     * @throws PrestaShopException
     */
    public function initFormModules($obj)
    {
        $idModule = (int)Db::readOnly()->getValue(
            (new DbQuery())
            ->select('`id_module`')
            ->from('module')
            ->where('`name` = \''.pSQL($this->tab_display_module).'\'')
        );
        $this->tpl_form_vars['custom_form'] = Hook::displayHook('displayAdminProductsExtra', [], $idModule);
    }

    /**
     * @param string $key
     * @return string
     */
    public function getL($key)
    {
        $trad = [
            'Default category:'                                                 => $this->l('Default category'),
            'Catalog:'                                                          => $this->l('Catalog'),
            'Consider changing the default category.'                           => $this->l('Consider changing the default category.'),
            'ID'                                                                => $this->l('ID'),
            'Name'                                                              => $this->l('Name'),
            'Mark all checkbox(es) of categories in which product is to appear' => $this->l('Mark the checkbox of each categories in which this product will appear.'),
        ];

        return $trad[$key];
    }

    /**
     * Ajax process product name check
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessCheckProductName()
    {
        if ($this->hasViewPermission()) {
            $search = Tools::getValue('q');
            $id_lang = Tools::getIntValue('id_lang');
            $limit = Tools::getValue('limit');
            if ($this->context->shop->getContext() != Shop::CONTEXT_SHOP) {
                $result = false;
            } else {
                $result = Db::readOnly()->getArray(
                    '
					SELECT DISTINCT pl.`name`, p.`id_product`, pl.`id_shop`
					FROM `'._DB_PREFIX_.'product` p
					LEFT JOIN `'._DB_PREFIX_.'product_shop` ps ON (ps.id_product = p.id_product AND ps.id_shop ='.(int) $this->context->shop->id.')
					LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
						ON (pl.`id_product` = p.`id_product` AND pl.`id_lang` = '.(int) $id_lang.')
					WHERE pl.`name` LIKE "%'.pSQL($search).'%" AND ps.id_product IS NULL
					GROUP BY pl.`id_product`
					LIMIT '.(int) $limit
                );
            }
            $this->ajaxDie(json_encode($result));
        }
    }

    /**
     * Ajax process update positions
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdatePositions()
    {
        $this->setJSendErrorHandling();

        if (! $this->hasEditPermission()) {
            throw new PrestaShopException(Tools::displayError('You do not have permission to edit this.'));
        }

        $way = Tools::getIntValue('way');
        $productId = Tools::getIntValue('id_product');
        $categoryId = Tools::getIntValue('id_category');
        $positions = Tools::getValue('product');
        $page = Tools::getIntValue('page');
        $selected_pagination = Tools::getIntValue('selected_pagination');

        if (is_array($positions)) {
            foreach ($positions as $position => $value) {
                $pos = explode('_', $value);
                $categoryIdPos = (int)$pos[1] ?? 0;
                $productIdPos = (int)$pos[2] ?? 0;

                if ($categoryIdPos === $categoryId && $productIdPos === $productId) {
                    $position = (int)$position;
                    if ($page > 1) {
                        $position = $position + (($page - 1) * $selected_pagination);
                    }
                    $product = new Product($productId, false, $this->context->language->id);

                    if (Validate::isLoadedObject($product)) {
                        if ($product->updatePosition($way, $position)) {
                            $category = new Category($categoryId);
                            if (Validate::isLoadedObject($category)) {
                                Hook::triggerEvent('categoryUpdate', ['category' => $category]);
                            }
                            $this->ajaxDie(json_encode([
                                'status' => 'success',
                                'data' => sprintf($this->l('Product "%1$s" moved to position %2$d'), $product->name, ($position + 1)),
                            ]));
                        } else {
                            throw new PrestaShopException(sprintf(Tools::displayError('Can not update product "%1$s" to position %2$d'), $product->name, ($position + 1)));
                        }
                    } else {
                        throw new PrestaShopException(sprintf(Tools::displayError('Product with ID %d not found'), $productId));
                    }
                    return;
                }
            }
        }
        throw new PrestaShopException(Tools::displayError('New position not provided'));
    }

    /**
     * Ajax process publish product
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessPublishProduct()
    {
        if ($this->hasEditPermission()) {
            if ($id_product = Tools::getIntValue('id_product')) {
                $bo_product_url = dirname($_SERVER['PHP_SELF']).'/index.php?tab=AdminProducts&id_product='.$id_product.'&updateproduct&token='.$this->token;

                if (Tools::getValue('redirect')) {
                    $this->ajaxDie($bo_product_url);
                }

                $product = new Product((int) $id_product);
                if (!Validate::isLoadedObject($product)) {
                    $this->ajaxDie('error: invalid id');
                }

                $product->active = 1;

                if ($product->save()) {
                    $this->ajaxDie($bo_product_url);
                } else {
                    $this->ajaxDie('error: saving');
                }
            }
        }
    }

    /**
     * Display preview link
     *
     * @param string $token
     * @param int $id
     * @param string|null $name
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayPreviewLink($token, $id, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_preview.tpl');
        if (!array_key_exists('Bad SQL query', static::$cache_lang)) {
            static::$cache_lang['Preview'] = $this->l('Preview', 'Helper');
        }

        $tpl->assign(
            [
                'href'   => $this->getPreviewUrl(new Product((int) $id)),
                'action' => static::$cache_lang['Preview'],
            ]
        );

        return $tpl->fetch();
    }

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processBulkDelete()
    {
        if ($this->hasDeletePermission()) {
            if (is_array($this->boxes) && !empty($this->boxes)) {
                $success = 1;
                $products = Tools::getArrayValue($this->table.'Box');
                if ($products) {
                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                        $stockManager = StockManagerFactory::getManager();
                    }

                    foreach ($products as $id_product) {
                        $product = new Product((int) $id_product);
                        /*
                         * It is NOT possible to delete a product if there are currently:
                         * - physical stock for this product
                         * - supply order(s) for this product
                         */
                        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $product->advanced_stock_management) {
                            $physical_quantity = $stockManager->getProductPhysicalQuantities($product->id, 0);
                            $real_quantity = $stockManager->getProductRealQuantities($product->id, 0);
                            if ($physical_quantity > 0 || $real_quantity > $physical_quantity) {
                                $this->errors[] = sprintf(Tools::displayError('You cannot delete the product #%d because there is physical stock left.'), $product->id);
                            }
                        }
                        if (!count($this->errors)) {
                            if ($product->delete()) {
                                Logger::addLog(sprintf($this->l('%s deletion', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int) $product->id, true, (int) $this->context->employee->id);
                            } else {
                                $success = false;
                            }
                        } else {
                            $success = 0;
                        }
                    }
                }

                if ($success) {
                    $id_category = Tools::getIntValue('id_category');
                    $category_url = empty($id_category) ? '' : '&id_category='.(int) $id_category;
                    $this->redirect_after = static::$currentIndex.'&conf=2&token='.$this->token.$category_url;
                } else {
                    $this->errors[] = Tools::displayError('An error occurred while deleting this selection.');
                }
            } else {
                $this->errors[] = Tools::displayError('You must select at least one element to delete.');
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to delete this.');
        }
    }

    /**
     * @param int $productId
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function updatePerStoreFields($productId)
    {
        $productId = (int)$productId;
        if ($productId && !$this->allowEditPerStore()) {
            $active = array_map('intval', array_keys(Tools::getValue('active_per_store', [])));
            $conn = Db::getInstance();
            if ($active) {
                $list = implode(',', $active);
                $conn->update('product_shop', [
                    'active' => [
                        'type' => 'sql',
                        'value' => "(CASE WHEN id_shop IN ($list) THEN 1 ELSE 0 END)"
                    ]
                ], "id_product = $productId");
            } else {
                $conn->update('product_shop', ['active' => 0], "id_product = $productId");
            }
        }
    }

    /**
     * Update shop association
     *
     * @param int $idObject
     *
     * @return void | bool
     * @throws PrestaShopException
     */
    protected function updateAssoShop($idObject)
    {
        if (Tools::isSubmit('submitShopAssociation') || !$this->allowEditPerStore()) {
            return parent::updateAssoShop($idObject);
        }
    }

    /**
     * @param string $table
     * @return array
     * @throws PrestaShopException
     */
    protected function getSelectedAssoShop($table)
    {
        if ($this->allowEditPerStore()) {
            return parent::getSelectedAssoShop($table);
        } else {
            return $this->getSaveShopIDs();
        }
    }


    /**
     * Get final price
     *
     * @param array $specificPrice
     * @param float $productPrice
     * @param float $taxRate
     *
     * @return float
     *
     * @throws PrestaShopException
     * @deprecated 1.1.0 Nowhere in use. Remove entirely for 1.2.0.
     */
    protected function _getFinalPrice($specificPrice, $productPrice, $taxRate)
    {
        Tools::displayAsDeprecated('Use Product->getPrice() directly.');

        return $this->object->getPrice(
            false,
            $specificPrice['id_product_attribute'],
            $this->context->currency->getDisplayPrecision()
        );
    }

    /**
     * Display product unavailable warning
     *
     * @deprecated 1.1.0 Nowhere in use. Remove entirely for 1.2.0.
     */
    protected function _displayUnavailableProductWarning()
    {
        Tools::displayAsDeprecated();

        $content = '<div class="alert">
			<span>'.$this->l('Your product will be saved as a draft.').'</span>
				<a href="#" class="btn btn-default pull-right" onclick="submitAddProductAndPreview()" ><i class="icon-external-link-sign"></i> '.$this->l('Save and preview').'</a>
				<input type="hidden" name="fakeSubmitAddProductAndPreview" id="fakeSubmitAddProductAndPreview" />
			</div>';
        $this->tpl_form_vars['warning_unavailable_product'] = $content;
    }

    /**
     * Products controllers has per-tab permissions that allows store administrator to hide some tabs
     * for some roles.
     *
     * This is useful, for example, if you want to ensure that translator can't change pricing information
     *
     * @return array Permissions definitions
     */
    public function getPermDefinitions()
    {
        $permissions = [];
        foreach (array_keys($this->available_tabs) as $tab) {
            $permissions[] = [
                'permission' => $tab,
                'name' => $this->available_tabs_lang[$tab],
                'description' => sprintf($this->l('Permission for tab %s'), $this->available_tabs_lang[$tab]),
                'levels' => [
                    static::TAB_PERMISSION_NONE => $this->l('Tab is hidden'),
                    static::TAB_PERMISSION_FULL => $this->l('Read and write access'),
                ],
                'defaultLevel' => static::TAB_PERMISSION_FULL
            ];
        }
        return $permissions;
    }

    /**
     * Returns list of tabs that current employee can see
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getVisibleTabs()
    {
        $tabs = [];
        foreach ($this->getPermLevels() as $key => $level) {
            if ($level === static::TAB_PERMISSION_FULL) {
                $tabs[$key] = true;
            }
        }
        return $tabs;
    }

    /**
     * Returns base identifier from $full, for example 'demo_1' will return 'demo'
     * @param string $full
     * @return array|string|string[]|null
     */
    protected static function getBaseIdentifier($full)
    {
        if ($full) {
            return preg_replace('/_[0-9]+$/', '', $full);
        } else {
            return '';
        }
    }

    /**
     * Returns unique link rewrites
     *
     * @param array $rewrites link rewrites for all languages
     * @return array unique link rewrites
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function getUniqueLinkRewrites($rewrites)
    {
        $conn = Db::readOnly();
        foreach ($rewrites as $langId => &$rewrite) {
            $base = static::getBaseIdentifier($rewrite);
            $langId = (int)$langId;
            $candidates = array_column($conn->getArray((new DbQuery())
                ->select('DISTINCT link_rewrite')
                ->from('product_lang')
                ->where('id_lang = ' . $langId)
                ->where('link_rewrite LIKE "'.pSQL($base).'%"')
            ), 'link_rewrite');
            $i = 1;
            $candidate = $base;
            while (in_array($candidate, $candidates)) {
                $candidate = $base  . '_' . $i;
                $i++;
            }
            $rewrite = $candidate;
        }
        return $rewrites;
    }

    /**
     * @param Product $product
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getProductQuantities(Product $product)
    {
        $quantities = [];
        $attributes = $product->getAttributesResume($this->context->language->id);
        if (!$attributes) {
            $attributes = [[
                'id_product_attribute' => 0,
                'quantity' => StockAvailable::getQuantityAvailableByProduct($product->id)
            ]];
        }
        foreach ($attributes as $attribute) {
            $quantities[] = [
                'id' => (int)$attribute['id_product_attribute'],
                'value' => (int)$attribute['quantity'],
            ];
        }
        return $quantities;
    }

    /**
     * Returns true, if editation per store is allowed
     *
     * @return bool
     * @throws PrestaShopException
     */
    protected function allowEditPerStore()
    {
        if (Shop::isFeatureActive()) {
            return !Configuration::getGlobalValue('TB_SIMPLIFIED_PRODUCT_EDITATION');
        }
        return true;
    }

    /**
     * Returns list of shop IDs to be used for saving. It is an intersection of current shop context and
     * shops associated with products
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function getSaveShopIDs()
    {
        $shopIds = array_map('intval', Shop::getContextListShopID());
        if (Tools::isSubmit('submitShopAssociation')) {
            $associatedShopIds = array_map('intval', $this->getSelectedAssoShop($this->table));
            $shopIds = array_intersect($shopIds, $associatedShopIds);
        }
        return $shopIds;
    }

    /**
     * Helper methods to decide if stock quantities should be displayed or not, depending on store
     * settings
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function showQuantities(): bool
    {
        // always show quantities when multistore is disabled
        if (! Shop::isFeatureActive()) {
            return true;
        }

        $shopContext = (int)Shop::getContext();

        // if we are in all shops context, it may or may not be possible to manage quantities.
        // We have to check if all stores share the same group id. If they do, we can fallback to
        // group settings. If shops do not share group, it is not possible to manage quantities
        if ($shopContext === Shop::CONTEXT_ALL) {
            $groupId = 0;
            foreach (Shop::getContextListShopID() as $shopId) {
                $shopGroupId = (int)Shop::getShop($shopId)['id_shop_group'];
                if ($groupId && $shopGroupId !== $groupId) {
                    return false;
                }
                $groupId = $shopGroupId;
            }
            if (! $groupId) {
                return false;
            }
        } else {
            $groupId = (int)Shop::getContextShopGroupId();
        }

        $group = new ShopGroup($groupId);
        $shareStock = (bool)$group->share_stock;

        // if we are in a single shop context, and quantities are shared between shops of the group
        // it's not possible to manage quantities for a single shop
        if ($shopContext === Shop::CONTEXT_SHOP) {
            return !$shareStock;
        }

        // use shop group settings
        return $shareStock;
    }

    /**
     * @param int $quantity
     * @param array $row
     *
     * @return string
     * @throws PrestaShopException
     */
    public function callbackRenderStockLink($quantity, $row) {
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            $reference = $row['reference'] ?? '';
            if ($reference) {
                $stockLink = $this->context->link->getAdminLink('AdminStockInstantState', true, [], ['reference' => $reference]);
                $style = $quantity <= 0 ? 'color: #FFF' : 'color: #666';
                return "<a style='{$style}' href={$stockLink}>{$quantity}</a>";
            }
        }
        return $quantity;
    }

    /**
     * @param array $supplierIds
     * @param PrestaShopCollection $collection
     *
     * @return ProductSupplier[]
     */
    protected function filterSuppliers(array $supplierIds, PrestaShopCollection $collection)
    {
        $productSuppliers = [];
        /** @var ProductSupplier $productSupplier */
        foreach ($collection as $productSupplier) {
            $supplierId = (int)$productSupplier->id_supplier;
            if (in_array($supplierId, $supplierIds)) {
                $productSuppliers[] = $productSupplier;
            }
        }
        return $productSuppliers;
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     */
    public function initShopContext()
    {
        parent::initShopContext();
        if (!$this->allowEditPerStore()) {
            $defaultShopId = (int)Configuration::get('PS_SHOP_DEFAULT');
            if ((int)$this->context->shop->id !== $defaultShopId) {
                $this->context->shop = new Shop($defaultShopId);
            }
        }
    }


}
