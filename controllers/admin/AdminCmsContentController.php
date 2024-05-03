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
 * Class AdminCmsContentControllerCore
 *
 * @property CMS|null $object
 */
class AdminCmsContentControllerCore extends AdminController
{
    /**
     * @var AdminCmsCategoriesController $admin_cms_categories
     */
    protected $admin_cms_categories;

    /**
     * @var object adminCMS() instance
     */
    protected $admin_cms;

    /**
     * AdminCmsContentControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;


        $this->table = 'cms';
        $this->className = 'CMS';
        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];
        $this->admin_cms_categories = new AdminCmsCategoriesController();
        $this->admin_cms_categories->init();
        $this->admin_cms = new AdminCmsController();
        $this->admin_cms->init();

        parent::__construct();
    }

    /**
     * Return current category
     *
     * @return CMSCategory
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCurrentCMSCategory()
    {
        $idCmsCategory = Tools::getIntValue('id_cms_category', Tools::getIntValue('id_cms_category_parent', 1));
        return new CMSCategory($idCmsCategory);
    }

    /**
     * Check if view access
     *
     * @param bool $disable
     *
     * @return bool
     */
    public function viewAccess($disable = false)
    {
        $result = parent::viewAccess($disable);
        $this->admin_cms_categories->tabAccess = $this->tabAccess;
        $this->admin_cms->tabAccess = $this->tabAccess;

        return $result;
    }

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
        $this->renderPageHeaderToolbar();

        $this->admin_cms_categories->token = $this->token;
        $this->admin_cms->token = $this->token;

        if ($this->display == 'edit_category') {
            $this->content .= $this->admin_cms_categories->renderForm();
        } elseif ($this->display == 'edit_page') {
            $this->content .= $this->admin_cms->renderForm();
        } else {
            $idCmsCategory = Tools::getIntValue('id_cms_category', 1);
            $category = new CMSCategory($idCmsCategory, $this->context->language->id);
            if (Validate::isLoadedObject($category)) {
                $toolbarTitle = $this->toolbar_title;
                $toolbarTitle[] = static::isRootCategory($category)
                    ? $this->l('Categories')
                    : CMSCategory::hideCMSCategoryPosition($category->name);
                $this->context->smarty->assign([
                    'cms_breadcrumb'            => $this->getBreadcrumbs($idCmsCategory),
                    'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
                    'page_header_toolbar_title' => $toolbarTitle,
                    'title'                     => $toolbarTitle
                ]);

                $this->content .= $this->admin_cms_categories->renderList();
                $this->admin_cms->id_cms_category = $idCmsCategory;
                $this->content .= $this->admin_cms->renderList();
            }
        }

        $this->context->smarty->assign('content', $this->content);
    }

    /**
     * Render toolbar in page header
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function renderPageHeaderToolbar()
    {
        $idCmsCategory = Tools::getIntValue('id_cms_category');
        $idCmsPage = Tools::getIntValue('id_cms');

        if (!$idCmsCategory) {
            $idCmsCategory = 1;
        }

        $cmsCategory = new CMSCategory($idCmsCategory);

        if ($this->display == 'edit_category') {
            if (Tools::getValue('addcms_category') !== false) {
                $this->toolbar_title[] = $this->l('Add new');
            } else {
                $this->toolbar_title[] = sprintf($this->l('Edit: %s'), $cmsCategory->name[$this->context->employee->id_lang]);
            }
        } elseif ($this->display == 'edit_page') {
            $this->toolbar_title[] = $cmsCategory->name[$this->context->employee->id_lang];

            if (Tools::getValue('addcms') !== false) {
                $this->toolbar_title[] = $this->l('Add new');
            } elseif ($idCmsPage) {
                $cmsPage = new CMS($idCmsPage);
                $this->toolbar_title[] = sprintf($this->l('Edit: %s'), $cmsPage->meta_title[$this->context->employee->id_lang]);
            }
        } else {
            $this->toolbar_title[] = $this->l('CMS');
        }

        if ($this->display == 'list') {
            $this->page_header_toolbar_btn['new_cms_category'] = [
                'href' => static::$currentIndex.'&addcms_category&token='.$this->token,
                'desc' => $this->l('Add new CMS category', null, null, false),
                'icon' => 'process-icon-new',
            ];
            $this->page_header_toolbar_btn['new_cms_page'] = [
                'href' => static::$currentIndex.'&addcms&id_cms_category='.(int) $idCmsCategory.'&token='.$this->token,
                'desc' => $this->l('Add new CMS page', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        $this->page_header_toolbar_title = trim(implode(' '.Configuration::get('PS_NAVIGATION_PIPE').' ', $this->toolbar_title));

        if (is_array($this->page_header_toolbar_btn)
            || ($this->page_header_toolbar_btn instanceof Traversable)
            || $this->page_header_toolbar_title
        ) {
            $this->show_page_header_toolbar = true;
        }

        $this->context->smarty->assign(
            [
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'title'                     => $this->page_header_toolbar_title,
                'toolbar_btn'               => $this->page_header_toolbar_btn,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
                'page_header_toolbar_title' => $this->toolbar_title,
            ]
        );
    }

    /**
     * Post process
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $this->admin_cms->postProcess();
        $this->admin_cms_categories->postProcess();

        parent::postProcess();

        if (((Tools::isSubmit('submitAddcms_category') || Tools::isSubmit('submitAddcms_categoryAndStay')) && count($this->admin_cms_categories->errors))
            || Tools::isSubmit('updatecms_category')
            || Tools::isSubmit('addcms_category')
        ) {
            $this->display = 'edit_category';
        } elseif (((Tools::isSubmit('submitAddcms') || Tools::isSubmit('submitAddcmsAndStay')) && count($this->admin_cms->errors))
            || Tools::isSubmit('updatecms')
            || Tools::isSubmit('addcms')
        ) {
            $this->display = 'edit_page';
        } else {
            $this->display = 'list';
        }

        if (isset($this->admin_cms->errors)) {
            $this->errors = array_merge($this->errors, $this->admin_cms->errors);
        }

        if (isset($this->admin_cms_categories->errors)) {
            $this->errors = array_merge($this->errors, $this->admin_cms_categories->errors);
        }
    }

    /**
     * Set media
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryUi('ui.widget');
        $this->addJqueryPlugin('tagify');
    }

    /**
     * Ajax process update cms positions
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateCmsPositions()
    {
        if ($this->hasEditPermission()) {
            $idCms = Tools::getIntValue('id_cms');
            $idCategory = Tools::getIntValue('id_cms_category');
            $way = Tools::getIntValue('way');
            $positions = Tools::getValue('cms');
            if (is_array($positions)) {
                foreach ($positions as $key => $value) {
                    $pos = explode('_', $value);
                    if ((isset($pos[1]) && isset($pos[2])) && ($pos[1] == $idCategory && $pos[2] == $idCms)) {
                        $position = $key;
                        break;
                    }
                }
            }
            $cms = new CMS($idCms);
            if (Validate::isLoadedObject($cms)) {
                if (isset($position) && $cms->updatePosition($way, $position)) {
                    $this->ajaxDie(true);
                } else {
                    $this->ajaxDie('{"hasError" : true, "errors" : "Can not update cms position"}');
                }
            } else {
                $this->ajaxDie('{"hasError" : true, "errors" : "This cms can not be loaded"}');
            }
        }
    }

    /**
     * Ajax process update CMSCategory positions
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateCmsCategoriesPositions()
    {
        if ($this->hasEditPermission()) {
            $idCmsCategoryToMove = Tools::getIntValue('id_cms_category_to_move');
            $idCmsCategoryParent = Tools::getIntValue('id_cms_category_parent');
            $way = Tools::getIntValue('way');
            $positions = Tools::getValue('cms_category');
            if (is_array($positions)) {
                foreach ($positions as $key => $value) {
                    $pos = explode('_', $value);
                    if ((isset($pos[1]) && isset($pos[2])) && ($pos[1] == $idCmsCategoryParent && $pos[2] == $idCmsCategoryToMove)) {
                        $position = $key;
                        break;
                    }
                }
            }
            $cmsCategory = new CMSCategory($idCmsCategoryToMove);
            if (Validate::isLoadedObject($cmsCategory)) {
                if (isset($position) && $cmsCategory->updatePosition($way, $position)) {
                    $this->ajaxDie(true);
                } else {
                    $this->ajaxDie('{"hasError" : true, "errors" : "Can not update cms categories position"}');
                }
            } else {
                $this->ajaxDie('{"hasError" : true, "errors" : "This cms category can not be loaded"}');
            }
        }
    }

    /**
     * Ajax process publish CMS
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessPublishCMS()
    {
        if ($this->hasEditPermission()) {
            if ($idCms = Tools::getIntValue('id_cms')) {
                $boCmsUrl = $this->context->link->getAdminLink('AdminCmsContent', true).'&updatecms&id_cms='.(int) $idCms;
                if (Tools::getValue('redirect')) {
                    $this->ajaxDie($boCmsUrl);
                }

                $cms = new CMS(Tools::getIntValue('id_cms'));
                if (!Validate::isLoadedObject($cms)) {
                    $this->ajaxDie('error: invalid id');
                }

                $cms->active = 1;
                if ($cms->save()) {
                    $this->ajaxDie($boCmsUrl);
                } else {
                    $this->ajaxDie('error: saving');
                }
            } else {
                $this->ajaxDie('error: parameters');
            }
        }
    }

    /**
     * @param int $idCmsCategory
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getBreadcrumbs(int $idCmsCategory)
    {
        return implode(static::constructBreadcrumbs($idCmsCategory, (int)$this->context->language->id));
    }

    /**
     * @param int $idCmsCategory
     * @param int $langId
     * @param array $path
     *
     * @return string[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function constructBreadcrumbs(int $idCmsCategory, int $langId, array $path = [])
    {
        $category = new CMSCategory($idCmsCategory, $langId);
        if (Validate::isLoadedObject($category)) {

            $name = CMSCategory::hideCMSCategoryPosition($category->name);
            $link = Context::getContext()->link->getAdminLink('AdminCmsContent', true, [
                'id_cms_category' => $idCmsCategory
            ]);
            $item = '<li><a href="'.$link.'">' . Tools::safeOutput($name) . '</a></li>';
            array_unshift($path, $item);
            if (static::isRootCategory($category)) {
                return $path;
            }
            return static::constructBreadcrumbs((int)$category->id_parent, $langId, $path);
        }
        return $path;
    }

    /**
     * @param CMSCategory $category
     *
     * @return bool
     */
    protected static function isRootCategory(CMSCategory $category)
    {
        $parentId = (int)$category->id_parent;
        return !$parentId || $parentId === (int)$category->id;
    }
}
