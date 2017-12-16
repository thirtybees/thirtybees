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
 * Class AdminCmsContentControllerCore
 *
 * @since 1.0.0
 */
class AdminCmsContentControllerCore extends AdminController
{
    // @codingStandardsIgnoreStart
    /** @var CMSCategory Cms category instance for navigation */
    protected static $category = null;
    /** @var AdminCmsCategoriesController $admin_cms_categories */
    protected $admin_cms_categories;
    /** @var object adminCMS() instance */
    protected $admin_cms;
    // @codingStandardsIgnoreEnd

    /**
     * AdminCmsContentControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        /* Get current category */
        $idCmsCategory = (int) Tools::getValue('id_cms_category', Tools::getValue('id_cms_category_parent', 1));
        static::$category = new CMSCategory($idCmsCategory);
        if (!Validate::isLoadedObject(static::$category)) {
            die('Category cannot be loaded');
        }

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
     * @since 1.0.0
     */
    public static function getCurrentCMSCategory()
    {
        return static::$category;
    }

    /**
     * Check if view access
     *
     * @param bool $disable
     *
     * @return bool
     *
     * @since 1.0.0
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
     * @since 1.0.0
     */
    public function initContent()
    {
        $this->initTabModuleList();
        $this->renderPageHeaderToolbar();

        $this->admin_cms_categories->token = $this->token;
        $this->admin_cms->token = $this->token;

        if ($this->display == 'edit_category') {
            $this->content .= $this->admin_cms_categories->renderForm();
        } elseif ($this->display == 'edit_page') {
            $this->content .= $this->admin_cms->renderForm();
        } elseif ($this->display == 'view_page') {
            $fixme = 'fixme'; // FIXME
        } else {
            $idCmsCategory = (int) Tools::getValue('id_cms_category', 1);

            // CMS categories breadcrumb
            $cmsTabs = ['cms_category', 'cms'];
            // Cleaning links
            $catBarIndex = static::$currentIndex;
            foreach ($cmsTabs as $tab) {
                if (Tools::getValue($tab.'Orderby') && Tools::getValue($tab.'Orderway')) {
                    $catBarIndex = preg_replace('/&'.$tab.'Orderby=([a-z _]*)&'.$tab.'Orderway=([a-z]*)/i', '', static::$currentIndex);
                }
            }
            $this->context->smarty->assign(
                [
                    'cms_breadcrumb'            => getPath($catBarIndex, $idCmsCategory, '', '', 'cms'),
                    'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
                    'page_header_toolbar_title' => $this->toolbar_title,
                ]
            );

            $this->content .= $this->admin_cms_categories->renderList();
            $this->admin_cms->id_cms_category = $idCmsCategory;
            $this->content .= $this->admin_cms->renderList();
        }

        $this->context->smarty->assign(
            [
                'content' => $this->content,
            ]
        );
    }

    /**
     * Render toolbar in page header
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function renderPageHeaderToolbar()
    {
        $idCmsCategory = (int) Tools::getValue('id_cms_category');
        $idCmsPage = Tools::getValue('id_cms');

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

        $this->page_header_toolbar_title = implode(' '.Configuration::get('PS_NAVIGATION_PIPE').' ', $this->toolbar_title);

        if (is_array($this->page_header_toolbar_btn)
            && $this->page_header_toolbar_btn instanceof Traversable
            || trim($this->page_header_toolbar_title) != ''
        ) {
            $this->show_page_header_toolbar = true;
        }

        // TODO: Check if we need this
//        $template = $this->context->smarty->createTemplate(
//            $this->context->smarty->getTemplateDir(0).DIRECTORY_SEPARATOR.'page_header_toolbar.tpl', $this->context->smarty
//        );

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
     * @since 1.0.0
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
            $this->id_cms_category = (int) Tools::getValue('id_cms_category');
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
     * @since 1.0.0
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
     * @since 1.0.0
     */
    public function ajaxProcessUpdateCmsPositions()
    {
        if ($this->tabAccess['edit'] === '1') {
            $idCms = (int) Tools::getValue('id_cms');
            $idCategory = (int) Tools::getValue('id_cms_category');
            $way = (int) Tools::getValue('way');
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
                    die(true);
                } else {
                    die('{"hasError" : true, "errors" : "Can not update cms position"}');
                }
            } else {
                die('{"hasError" : true, "errors" : "This cms can not be loaded"}');
            }
        }
    }

    /**
     * Ajax process update CMSCategory positions
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessUpdateCmsCategoriesPositions()
    {
        if ($this->tabAccess['edit'] === '1') {
            $idCmsCategoryToMove = (int) Tools::getValue('id_cms_category_to_move');
            $idCmsCategoryParent = (int) Tools::getValue('id_cms_category_parent');
            $way = (int) Tools::getValue('way');
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
                    die(true);
                } else {
                    die('{"hasError" : true, "errors" : "Can not update cms categories position"}');
                }
            } else {
                die('{"hasError" : true, "errors" : "This cms category can not be loaded"}');
            }
        }
    }

    /**
     * Ajax process publish CMS
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessPublishCMS()
    {
        if ($this->tabAccess['edit'] === '1') {
            if ($idCms = (int) Tools::getValue('id_cms')) {
                $boCmsUrl = $this->context->link->getAdminLink('AdminCmsContent', true).'&updatecms&id_cms='.(int) $idCms;
                if (Tools::getValue('redirect')) {
                    die($boCmsUrl);
                }

                $cms = new CMS((int) (Tools::getValue('id_cms')));
                if (!Validate::isLoadedObject($cms)) {
                    die('error: invalid id');
                }

                $cms->active = 1;
                if ($cms->save()) {
                    die($boCmsUrl);
                } else {
                    die('error: saving');
                }
            } else {
                die('error: parameters');
            }
        }
    }
}
