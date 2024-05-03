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
 * Class AdminCmsCategoriesControllerCore
 *
 * @property CMSCategory|null $object
 */
class AdminCmsCategoriesControllerCore extends AdminController
{
    /**
     * @var string
     */
    protected $position_identifier = 'id_cms_category_to_move';

    /**
     * AdminCmsCategoriesControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->is_cms = true;
        $this->table = 'cms_category';
        $this->list_id = 'cms_category';
        $this->className = 'CMSCategory';
        $this->lang = true;
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->_orderBy = 'position';

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];
        $this->tpl_list_vars['icon'] = 'icon-folder-close';
        $this->tpl_list_vars['title'] = $this->l('Categories');
        $this->fields_list = [
            'id_cms_category' => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name'            => ['title' => $this->l('Name'), 'width' => 'auto', 'callback' => 'hideCMSCategoryPosition', 'callback_object' => 'CMSCategory'],
            'description'     => ['title' => $this->l('Description'), 'maxlength' => 90, 'orderby' => false],
            'position'        => ['title' => $this->l('Position'), 'filter_key' => 'position', 'align' => 'center', 'class' => 'fixed-width-sm', 'position' => 'position'],
            'active'          => [
                'title' => $this->l('Displayed'), 'class' => 'fixed-width-sm', 'active' => 'status',
                'align' => 'center', 'type' => 'bool', 'orderby' => false,
            ],
        ];

        $category = AdminCmsContentController::getCurrentCMSCategory();
        if (! Validate::isLoadedObject($category)) {
            $this->redirect_after = '?controller=AdminCmsContent&token='.Tools::getAdminTokenLite('AdminCmsContent');
            $this->redirect();
        }

        $this->_where = ' AND `id_parent` = '.(int) $category->id;
        $this->_select = 'position ';

        parent::__construct();
    }

    /**
     * @return false|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        $this->initToolbar();
        $this->_group = 'GROUP BY a.`id_cms_category`';
        if (isset($this->toolbar_btn['new'])) {
            $this->toolbar_btn['new']['href'] .= '&id_parent='.Tools::getIntValue('id_cms_category');
        }

        return parent::renderList();
    }

    /**
     * @return bool|CMSCategory|false|ObjectModel
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd'.$this->table)) {
            $this->action = 'save';
            if ($idCmsCategory = Tools::getIntValue('id_cms_category')) {
                $this->id_object = $idCmsCategory;
                if (!CMSCategory::checkBeforeMove($idCmsCategory, Tools::getIntValue('id_parent'))) {
                    $this->errors[] = Tools::displayError('The CMS Category cannot be moved here.');

                    return false;
                }
            }
            $object = parent::postProcess();
            $this->updateAssoShop(Tools::getIntValue('id_cms_category'));
            if ($object !== false) {
                Tools::redirectAdmin(static::$currentIndex.'&conf=3&id_cms_category='.(int) $object->id.'&token='.Tools::getValue('token'));
            }

            return $object;
        } /* Change object statuts (active, inactive) */
        elseif (Tools::isSubmit('statuscms_category') && Tools::getValue($this->identifier)) {
            if ($this->hasEditPermission()) {
                if (Validate::isLoadedObject($object = $this->loadObject())) {
                    /** @var CMSCategory $object */
                    if ($object->toggleStatus()) {
                        $identifier = ((int) $object->id_parent ? '&id_cms_category='.(int) $object->id_parent : '');
                        Tools::redirectAdmin(static::$currentIndex.'&conf=5'.$identifier.'&token='.Tools::getValue('token'));
                    } else {
                        $this->errors[] = Tools::displayError('An error occurred while updating the status.');
                    }
                } else {
                    $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } /* Delete object */
        elseif (Tools::isSubmit('delete'.$this->table)) {
            if ($this->hasDeletePermission()) {
                if (Validate::isLoadedObject($object = $this->loadObject()) && isset($this->fieldImageSettings)) {
                    /** @var CMSCategory $object */
                    $identifier = ((int) $object->id_parent ? '&'.$this->identifier.'='.(int) $object->id_parent : '');
                    if ($this->deleted) {
                        $object->deleted = 1;
                        if ($object->update()) {
                            Tools::redirectAdmin(static::$currentIndex.'&conf=1&token='.Tools::getValue('token').$identifier);
                        }
                    } elseif ($object->delete()) {
                        Tools::redirectAdmin(static::$currentIndex.'&conf=1&token='.Tools::getValue('token').$identifier);
                    }
                    $this->errors[] = Tools::displayError('An error occurred during deletion.');
                } else {
                    $this->errors[] = Tools::displayError('An error occurred while deleting the object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        } elseif (Tools::isSubmit('position')) {
            $object = new CMSCategory(Tools::getIntValue($this->identifier, Tools::getIntValue('id_cms_category_to_move', 1)));
            if (!$this->hasEditPermission()) {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            } elseif (!Validate::isLoadedObject($object)) {
                $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
            } elseif (!$object->updatePosition(Tools::getIntValue('way'), Tools::getIntValue('position'))) {
                $this->errors[] = Tools::displayError('Failed to update the position.');
            } else {
                $identifier = ((int) $object->id_parent ? '&'.$this->identifier.'='.(int) $object->id_parent : '');
                $token = Tools::getAdminTokenLite('AdminCmsContent');
                Tools::redirectAdmin(
                    static::$currentIndex.'&'.$this->table.'Orderby=position&'.$this->table.'Orderway=asc&conf=5'.$identifier.'&token='.$token
                );
            }
        } /* Delete multiple objects */
        elseif (Tools::getValue('submitDel'.$this->table) || Tools::isSubmit('submitBulkdelete'.$this->table)) {
            if ($this->hasDeletePermission()) {
                if (Tools::isSubmit($this->table.'Box')) {
                    $cmsCategory = new CMSCategory();
                    $result = $cmsCategory->deleteSelection(Tools::getArrayValue($this->table.'Box'));
                    if ($result) {
                        $cmsCategory->cleanPositions(Tools::getIntValue('id_cms_category'));
                        $token = Tools::getAdminTokenLite('AdminCmsContent');
                        Tools::redirectAdmin(static::$currentIndex.'&conf=2&token='.$token.'&id_cms_category='.Tools::getIntValue('id_cms_category'));
                    }
                    $this->errors[] = Tools::displayError('An error occurred while deleting this selection.');
                } else {
                    $this->errors[] = Tools::displayError('You must select at least one element to delete.');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        }
        parent::postProcess();
    }

    /**
     * @return string|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $this->display = 'edit';
        $this->initToolbar();
        if (!$this->loadObject(true)) {
            return null;
        }

        $categories = CMSCategory::getCategories($this->context->language->id, false);
        $htmlCategories = CMSCategory::recurseCMSCategory($categories, $categories[0][1], 1, $this->getFieldValue($this->object, 'id_parent'), 1);

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('CMS Category'),
                'icon'  => 'icon-folder-close',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'class'    => 'copyMeta2friendlyURL',
                    'required' => true,
                    'lang'     => true,
                    'hint'     => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
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
                // custom template
                [
                    'type'    => 'select_category',
                    'label'   => $this->l('Parent CMS Category'),
                    'name'    => 'id_parent',
                    'options' => [
                        'html' => $htmlCategories,
                    ],
                ],
                [
                    'type'  => 'textarea',
                    'label' => $this->l('Description'),
                    'name'  => 'description',
                    'lang'  => true,
                    'rows'  => 5,
                    'cols'  => 40,
                    'autoload_rte' => true,
                    'hint'  => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Meta title'),
                    'name'  => 'meta_title',
                    'lang'  => true,
                    'hint'  => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Meta description'),
                    'name'  => 'meta_description',
                    'lang'  => true,
                    'hint'  => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Meta keywords'),
                    'name'  => 'meta_keywords',
                    'lang'  => true,
                    'hint'  => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Friendly URL'),
                    'name'     => 'link_rewrite',
                    'required' => true,
                    'lang'     => true,
                    'hint'     => $this->l('Only letters and the minus (-) character are allowed.'),
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        $this->tpl_form_vars['PS_ALLOW_ACCENTED_CHARS_URL'] = (int) Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');

        return parent::renderForm();
    }
}
