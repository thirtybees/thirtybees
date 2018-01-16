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
 * @property Feature $object
 */
class AdminFeaturesControllerCore extends AdminController
{
    public $bootstrap = true;
    protected $position_identifier = 'id_feature';
    protected $feature_name;

    /**
     * AdminFeaturesControllerCore constructor.
     */
    public function __construct()
    {
        $this->table = 'feature';
        $this->className = 'Feature';
        $this->list_id = 'feature';
        $this->identifier = 'id_feature';
        $this->lang = true;

        $this->fields_list = [
            'id_feature' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'       => [
                'title'      => $this->l('Name'),
                'width'      => 'auto',
                'filter_key' => 'b!name',
            ],
            'value'      => [
                'title'   => $this->l('Values'),
                'orderby' => false,
                'search'  => false,
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
            ],
            'position'   => [
                'title'      => $this->l('Position'),
                'filter_key' => 'a!position',
                'align'      => 'center',
                'class'      => 'fixed-width-xs',
                'position'   => 'position',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'icon'    => 'icon-trash',
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];
        parent::__construct();
    }

    public function initToolbarTitle()
    {
        $bread_extended = $this->breadcrumbs;

        switch ($this->display) {
            case 'edit':
                $bread_extended[] = $this->l('Edit New Feature');
                $this->addMetaTitle($bread_extended[count($bread_extended) - 1]);
                break;

            case 'add':
                $bread_extended[] = $this->l('Add New Feature');
                $this->addMetaTitle($bread_extended[count($bread_extended) - 1]);
                break;

            case 'view':
                $bread_extended[] = $this->feature_name[$this->context->employee->id_lang];
                $this->addMetaTitle($bread_extended[count($bread_extended) - 1]);
                break;

            case 'editFeatureValue':
                if (($id_feature_value = Tools::getValue('id_feature_value'))) {
                    if (($id = Tools::getValue('id_feature'))) {
                        if (Validate::isLoadedObject($obj = new Feature((int) $id))) {
                            $bread_extended[] = '<a href="'.$this->context->link->getAdminLink('AdminFeatures').'&id_feature='.$id.'&viewfeature">'.$obj->name[$this->context->employee->id_lang].'</a>';
                        }

                        if (Validate::isLoadedObject($obj = new FeatureValue((int) Tools::getValue('id_feature_value')))) {
                            $bread_extended[] = sprintf($this->l('Edit: %s'), $obj->value[$this->context->employee->id_lang]);
                        }
                    } else {
                        $bread_extended[] = $this->l('Edit Value');
                    }
                } else {
                    $bread_extended[] = $this->l('Add New Value');
                }

                if (count($bread_extended) > 0) {
                    $this->addMetaTitle($bread_extended[count($bread_extended) - 1]);
                }
                break;
        }

        $this->toolbar_title = $bread_extended;
    }

    /**
     * AdminController::initContent() override
     *
     * @see AdminController::initContent()
     */
    public function initContent()
    {
        if (Feature::isFeatureActive()) {
            // toolbar (save, cancel, new, ..)
            $this->initTabModuleList();
            $this->initToolbar();
            $this->initPageHeaderToolbar();
            if ($this->display == 'edit' || $this->display == 'add') {
                if (!$this->loadObject(true)) {
                    return;
                }
                $this->content .= $this->renderForm();
            } elseif ($this->display == 'view') {
                // Some controllers use the view action without an object
                if ($this->className) {
                    $this->loadObject(true);
                }
                $this->content .= $this->renderView();
            } elseif ($this->display == 'editFeatureValue') {
                if (!$this->object = new FeatureValue((int) Tools::getValue('id_feature_value'))) {
                    return;
                }

                $this->content .= $this->initFormFeatureValue();
            } elseif (!$this->ajax) {
                // If a feature value was saved, we need to reset the values to display the list
                $this->setTypeFeature();
                $this->content .= $this->renderList();
            }
        } else {
            $url = '<a href="index.php?tab=AdminPerformance&token='.Tools::getAdminTokenLite('AdminPerformance').'#featuresDetachables">'.$this->l('Performance').'</a>';
            $this->displayWarning(sprintf($this->l('This feature has been disabled. You can activate it here: %s.'), $url));
        }

        /** Reset the old search */
        if (Tools::getIsset('viewfeature')
            && !Tools::getIsset('submitReset' . $this->list_id)
            && !Tools::getIsset('submitFilter')) {
            $this->processResetFilters();
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
     * AdminController::initToolbar() override
     *
     * @see AdminController::initToolbar()
     */
    public function initToolbar()
    {
        switch ($this->display) {
            case 'editFeatureValue':
            case 'add':
            case 'edit':
                $this->toolbar_btn['save'] = [
                    'href' => '#',
                    'desc' => $this->l('Save'),
                ];

                if ($this->display == 'editFeatureValue') {
                    $this->toolbar_btn['save-and-stay'] = [
                        'short'      => 'SaveAndStay',
                        'href'       => '#',
                        'desc'       => $this->l('Save and add another value'),
                        'force_desc' => true,
                    ];
                }

                // Default cancel button - like old back link
                $back = Tools::safeOutput(Tools::getValue('back', ''));
                if (empty($back)) {
                    $back = static::$currentIndex.'&token='.$this->token;
                }

                $this->toolbar_btn['back'] = [
                    'href' => $back,
                    'desc' => $this->l('Back to the list'),
                ];
                break;
            case 'view':
                $this->toolbar_btn['newAttributes'] = [
                    'href' => static::$currentIndex.'&addfeature_value&id_feature='.(int) Tools::getValue('id_feature').'&token='.$this->token,
                    'desc' => $this->l('Add new feature values'),
                ];
                $this->toolbar_btn['back'] = [
                    'href' => static::$currentIndex.'&token='.$this->token,
                    'desc' => $this->l('Back to the list'),
                ];
                break;
            default:
                parent::initToolbar();
        }
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_feature'] = [
                'href' => static::$currentIndex.'&addfeature&token='.$this->token,
                'desc' => $this->l('Add new feature', null, null, false),
                'icon' => 'process-icon-new',
            ];

            $this->page_header_toolbar_btn['new_feature_value'] = [
                'href' => static::$currentIndex.'&addfeature_value&id_feature='.(int) Tools::getValue('id_feature').'&token='.$this->token,
                'desc' => $this->l('Add new feature value', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        if ($this->display == 'view') {
            $this->page_header_toolbar_btn['new_feature_value'] = [
                'href' => static::$currentIndex.'&addfeature_value&id_feature='.(int) Tools::getValue('id_feature').'&token='.$this->token,
                'desc' => $this->l('Add new feature value', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * AdminController::renderForm() override
     *
     * @see AdminController::renderForm()
     */
    public function renderForm()
    {
        $this->toolbar_title = $this->l('Add a new feature');
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Feature'),
                'icon'  => 'icon-info-sign',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'size'     => 33,
                    'hint'     => $this->l('Invalid characters:').' <>;=#{}',
                    'required' => true,
                ],
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        return parent::renderForm();
    }

    public function renderView()
    {
        if (($id = Tools::getValue('id_feature'))) {
            $this->setTypeValue();
            $this->list_id = 'feature_value';
            $this->lang = true;

            // Action for list
            $this->addRowAction('edit');
            $this->addRowAction('delete');

            if (!Validate::isLoadedObject($obj = new Feature((int) $id))) {
                $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');

                return;
            }

            $this->feature_name = $obj->name;
            $this->toolbar_title = $this->feature_name[$this->context->employee->id_lang];
            $this->fields_list = [
                'id_feature_value' => [
                    'title' => $this->l('ID'),
                    'align' => 'center',
                    'class' => 'fixed-width-xs',
                ],
                'value'            => [
                    'title' => $this->l('Value'),
                ],
            ];

            $this->_where = sprintf('AND `id_feature` = %d', (int) $id);
            static::$currentIndex = static::$currentIndex.'&id_feature='.(int) $id.'&viewfeature';
            $this->processFilter();

            return parent::renderList();
        }
    }

    /**
     * Change object type to feature value (use when processing a feature value)
     */
    protected function setTypeValue()
    {
        $this->table = 'feature_value';
        $this->className = 'FeatureValue';
        $this->identifier = 'id_feature_value';
    }

    /**
     * AdminController::renderForm() override
     *
     * @see AdminController::renderForm()
     */
    public function initFormFeatureValue()
    {
        $this->setTypeValue();

        $this->fields_form[0]['form'] = [
            'legend'  => [
                'title' => $this->l('Feature value'),
                'icon'  => 'icon-info-sign',
            ],
            'input'   => [
                [
                    'type'     => 'select',
                    'label'    => $this->l('Feature'),
                    'name'     => 'id_feature',
                    'options'  => [
                        'query' => Feature::getFeatures($this->context->language->id),
                        'id'    => 'id_feature',
                        'name'  => 'name',
                    ],
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Value'),
                    'name'     => 'value',
                    'lang'     => true,
                    'size'     => 33,
                    'hint'     => $this->l('Invalid characters:').' <>;=#{}',
                    'required' => true,
                ],
            ],
            'submit'  => [
                'title' => $this->l('Save'),
            ],
            'buttons' => [
                'save-and-stay' => [
                    'title' => $this->l('Save then add another value'),
                    'name'  => 'submitAdd'.$this->table.'AndStay',
                    'type'  => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon'  => 'process-icon-save',
                ],
            ],
        ];

        $this->fields_value['id_feature'] = (int) Tools::getValue('id_feature');

        // Create Object FeatureValue
        $feature_value = new FeatureValue(Tools::getValue('id_feature_value'));

        $this->tpl_vars = [
            'feature_value' => $feature_value,
        ];

        $this->getlanguages();
        $helper = new HelperForm();
        $helper->show_cancel_button = true;

        $back = Tools::safeOutput(Tools::getValue('back', ''));
        if (empty($back)) {
            $back = static::$currentIndex.'&token='.$this->token;
        }
        if (!Validate::isCleanHtml($back)) {
            die(Tools::displayError());
        }

        $helper->back_url = $back;
        $helper->currentIndex = static::$currentIndex;
        $helper->token = $this->token;
        $helper->table = $this->table;
        $helper->identifier = $this->identifier;
        $helper->override_folder = 'feature_value/';
        $helper->id = $feature_value->id;
        $helper->toolbar_scroll = false;
        $helper->tpl_vars = $this->tpl_vars;
        $helper->languages = $this->_languages;
        $helper->default_form_language = $this->default_form_language;
        $helper->allow_employee_form_lang = $this->allow_employee_form_lang;
        $helper->fields_value = $this->getFieldsValue($feature_value);
        $helper->toolbar_btn = $this->toolbar_btn;
        $helper->title = $this->l('Add a new feature value');
        $this->content .= $helper->generateForm($this->fields_form);
    }

    /**
     * Change object type to feature (use when processing a feature)
     */
    protected function setTypeFeature()
    {
        $this->table = 'feature';
        $this->className = 'Feature';
        $this->identifier = 'id_feature';
    }

    /**
     * AdminController::renderList() override
     *
     * @see AdminController::renderList()
     */
    public function renderList()
    {
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function initProcess()
    {
        // Are we working on feature values?
        if (Tools::getValue('id_feature_value')
            || Tools::isSubmit('deletefeature_value')
            || Tools::isSubmit('submitAddfeature_value')
            || Tools::isSubmit('addfeature_value')
            || Tools::isSubmit('updatefeature_value')
            || Tools::isSubmit('submitBulkdeletefeature_value')
        ) {
            $this->setTypeValue();
        }

        if (Tools::getIsset('viewfeature')) {
            $this->list_id = 'feature_value';

            if (Tools::getIsset('submitReset' . $this->list_id)) {
                $this->processResetFilters();
            }

            if (Tools::getIsset('submitFilter')) {
                static::$currentIndex = static::$currentIndex . '&id_feature=' . (int)Tools::getValue('id_feature') . '&viewfeature';
            }
        } else {
            $this->list_id = 'feature';
            $this->_defaultOrderBy = 'position';
            $this->_defaultOrderWay = 'ASC';
        }

        parent::initProcess();
    }

    public function postProcess()
    {
        if (!Feature::isFeatureActive()) {
            return;
        }

        if ($this->table == 'feature_value' && ($this->action == 'save' || $this->action == 'delete' || $this->action == 'bulkDelete')) {
            Hook::exec(
                'displayFeatureValuePostProcess',
                ['errors' => &$this->errors]
            );
        } // send errors as reference to allow displayFeatureValuePostProcess to stop saving process
        else {
            Hook::exec(
                'displayFeaturePostProcess',
                ['errors' => &$this->errors]
            );
        } // send errors as reference to allow displayFeaturePostProcess to stop saving process

        parent::postProcess();

        if ($this->table == 'feature_value' && ($this->display == 'edit' || $this->display == 'add')) {
            $this->display = 'editFeatureValue';
        }
    }

    /**
     * Override processAdd to change SaveAndStay button action
     *
     * @see classes/AdminControllerCore::processAdd()
     */
    public function processAdd()
    {
        $object = parent::processAdd();

        if (Tools::isSubmit('submitAdd'.$this->table.'AndStay') && !count($this->errors)) {
            if ($this->table == 'feature_value' && ($this->display == 'edit' || $this->display == 'add')) {
                $this->redirect_after = static::$currentIndex.'&addfeature_value&id_feature='.(int) Tools::getValue('id_feature').'&token='.$this->token;
            } else {
                $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'=&conf=3&update'.$this->table.'&token='.$this->token;
            }
        } elseif (Tools::isSubmit('submitAdd'.$this->table.'AndStay') && count($this->errors)) {
            $this->display = 'editFeatureValue';
        }

        return $object;
    }

    /**
     * Override processUpdate to change SaveAndStay button action
     *
     * @see classes/AdminControllerCore::processUpdate()
     */
    public function processUpdate()
    {
        $object = parent::processUpdate();

        if (Tools::isSubmit('submitAdd'.$this->table.'AndStay') && !count($this->errors)) {
            $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'=&conf=3&update'.$this->table.'&token='.$this->token;
        }

        return $object;
    }

    /**
     * Call the right method for creating or updating object
     *
     * @return mixed
     */
    public function processSave()
    {
        if ($this->table == 'feature') {
            $id_feature = (int) Tools::getValue('id_feature');
            // Adding last position to the feature if not exist
            if ($id_feature <= 0) {
                $sql = 'SELECT `position`+1
						FROM `'._DB_PREFIX_.'feature`
						ORDER BY position DESC';
                // set the position of the new feature in $_POST for postProcess() method
                $_POST['position'] = DB::getInstance()->getValue($sql);
            }
            // clean \n\r characters
            foreach ($_POST as $key => $value) {
                if (preg_match('/^name_/Ui', $key)) {
                    $_POST[$key] = str_replace('\n', '', str_replace('\r', '', $value));
                }
            }
        }

        return parent::processSave();
    }

    /**
     * AdminController::getList() override
     *
     * @see AdminController::getList()
     *
     * @param int         $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int         $start
     * @param int|null    $limit
     * @param int|bool    $idLangShop
     *
     * @throws PrestaShopException
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        if ($this->table == 'feature_value') {
            $this->_where .= ' AND (a.custom = 0 OR a.custom IS NULL)';
        }

        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        if ($this->table == 'feature') {
            $nb_items = count($this->_list);
            for ($i = 0; $i < $nb_items; ++$i) {
                $item = &$this->_list[$i];

                $query = new DbQuery();
                $query->select('COUNT(fv.id_feature_value) as count_values');
                $query->from('feature_value', 'fv');
                $query->where('fv.id_feature ='.(int) $item['id_feature']);
                $query->where('(fv.custom=0 OR fv.custom IS NULL)');
                $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
                $item['value'] = (int) $res;
                unset($query);
            }
        }
    }

    public function ajaxProcessUpdatePositions()
    {
        if ($this->tabAccess['edit'] === '1') {
            $way = (int) Tools::getValue('way');
            $id_feature = (int) Tools::getValue('id');
            $positions = Tools::getValue('feature');

            $new_positions = [];
            foreach ($positions as $k => $v) {
                if (!empty($v)) {
                    $new_positions[] = $v;
                }
            }

            foreach ($new_positions as $position => $value) {
                $pos = explode('_', $value);

                if (isset($pos[2]) && (int) $pos[2] === $id_feature) {
                    if ($feature = new Feature((int) $pos[2])) {
                        if (isset($position) && $feature->updatePosition($way, $position, $id_feature)) {
                            echo 'ok position '.(int) $position.' for feature '.(int) $pos[1].'\r\n';
                        } else {
                            echo '{"hasError" : true, "errors" : "Can not update feature '.(int) $id_feature.' to position '.(int) $position.' "}';
                        }
                    } else {
                        echo '{"hasError" : true, "errors" : "This feature ('.(int) $id_feature.') can t be loaded"}';
                    }

                    break;
                }
            }
        }
    }
}
