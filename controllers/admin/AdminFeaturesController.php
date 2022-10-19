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

class AdminFeaturesControllerCore extends AdminController
{
    /**
     * @var bool
     */
    public $bootstrap = true;

    /**
     * @var string
     */
    protected $position_identifier = 'id_feature';

    /**
     * AdminFeaturesControllerCore constructor.
     *
     * @throws PrestaShopException
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
            'products'      => [
                'title'   => $this->l('Products'),
                'orderby' => false,
                'search'  => false,
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
                'callback'=> 'getProductsLink',
            ],
            'allows_multiple_values' => [
                'title' => $this->l("Allows multiple values"),
                'active'     => 'set_allow_multiple_values',
                'filter_key' => 'a!allows_multiple_values',
                'align'      => 'text-center',
                'type'       => 'bool',
                'class'      => 'fixed-width-xs',
                'orderby'    => false,
                'ajax'       => true,
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

    /**
     * @throws PrestaShopException
     */
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
                $bread_extended[] = $this->l('View Feature');
                $this->addMetaTitle($bread_extended[count($bread_extended) - 1]);
                break;

            case 'editFeatureValue':
                if ((Tools::getValue('id_feature_value'))) {
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
     * @throws PrestaShopException
     * @throws SmartyException
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

                $this->initFormFeatureValue();
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
     * @throws PrestaShopException
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

    /**
     * @return void
     * @throws PrestaShopException
     */
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
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        if (Validate::isLoadedObject($this->object)) {
            $multipleValuesDisabled = static::existsProductWithMultipleValues($this->object->id);
        } else {
            $multipleValuesDisabled = false;
        }

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
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Allows multiple values'),
                    'name'     => 'allows_multiple_values',
                    'hint'     => $multipleValuesDisabled
                                    ? $this->l('Some products contains multiple values for this feature. It is not possible to disable this functionality now')
                                    : $this->l('Choose if product can have multiple values for this feature'),
                    'required' => false,
                    'is_bool'  => true,
                    'disabled' => $multipleValuesDisabled,
                    'values'   => [
                        [
                            'id'    => 'allows_multiple_values_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'allows_multiple_values_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Sorting'),
                    'name'     => 'sorting',
                    'options'  => [
                        'query' => [
                            ['id' => Feature::SORT_VALUE_ASC, 'name' => $this->l('Value ASC')],
                            ['id' => Feature::SORT_VALUE_DESC, 'name' => $this->l('Value DESC')],
                            ['id' => Feature::SORT_CUSTOM, 'name' => $this->l('Custom')],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'col'      => '2',
                    'hint'     => $this->l('The way multiple values will be sorted'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Separator'),
                    'name'     => 'multiple_separator',
                    'lang'     => true,
                    'size'     => 33,
                    'hint'     => $this->l('How should multiple values be concatenated?'),
                    'required' => false,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Schema'),
                    'name'     => 'multiple_schema',
                    'lang'     => true,
                    'size'     => 33,
                    'hint'     => $this->l('How should multiple values be displayed?'),
                    'desc'     => $this->l('Keywords: {values}, {count_values}, {first_value}, {last_value}, {min_value}, {max_value}, {min_displayable}, {max_displayable}'),
                    'required' => false,
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

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderView()
    {
        if (!Validate::isLoadedObject($this->object)) {
            return '';
        }

        /** @var Feature $feature */
        $feature = $this->object;
        $featureId = (int)$feature->id;

        $this->setTypeValue();
        $this->list_id = 'feature_value';
        $this->lang = true;

        // Action for list
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->toolbar_title = $feature->name;
        $this->fields_list = [
            'id_feature_value' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'value'            => [
                'title' => $this->l('Value'),
            ],
            'displayable'            => [
                'title' => $this->l('Displayable'),
            ],
            'products'      => [
                'title'   => $this->l('Products'),
                'orderby' => false,
                'search'  => false,
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
                'callback'=> 'getProductsLink',
            ],

        ];

        switch ((int)$feature->sorting) {
            case Feature::SORT_CUSTOM:
                $this->fields_list['position'] = [
                    'title'      => $this->l('Position'),
                    'filter_key' => 'a!position',
                    'align'      => 'center',
                    'class'      => 'fixed-width-xs',
                    'position'   => 'position',
                ];
                $this->_defaultOrderBy = 'position';
                $this->_defaultOrderWay = 'ASC';
                break;
            case Feature::SORT_VALUE_DESC:
                $this->_defaultOrderBy = 'value';
                $this->_defaultOrderWay = 'DESC';
                break;
            case Feature::SORT_VALUE_ASC:
            default:
                $this->_defaultOrderBy = 'value';
                $this->_defaultOrderWay = 'ASC';
                break;
        }

        $this->_where = 'AND `id_feature` = ' . $featureId;
        static::$currentIndex = static::$currentIndex.'&id_feature='.$featureId.'&viewfeature';
        $this->_filter = '';
        $this->processFilter();

        return parent::renderList();
    }

    /**
     * Change object type to feature value (use when processing a feature value)
     *
     * @return void
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
     * @throws PrestaShopException
     * @throws SmartyException
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
                [
                    'type'     => 'text',
                    'label'    => $this->l('Displayable'),
                    'name'     => 'displayable',
                    'lang'     => true,
                    'size'     => 33,
                    'hint'     => $this->l('If this value is set, it overrides the original value. Note: it does only override for displaying not for filtering.'),
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


        $this->getlanguages();
        $helper = new HelperForm();
        $helper->show_cancel_button = true;

        $helper->back_url = $this->getBackUrlParameter();
        $helper->currentIndex = static::$currentIndex;
        $helper->token = $this->token;
        $helper->table = $this->table;
        $helper->identifier = $this->identifier;
        $helper->override_folder = 'feature_value/';
        $helper->id = $feature_value->id;
        $helper->toolbar_scroll = false;
        $helper->tpl_vars = [
            'feature_value' => $feature_value,
        ];
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
     *
     * @return void
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
     * @return false|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    /**
     * @return void
     */
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

            if (Tools::getIsset('submitFilter') . $this->list_id) {
                static::$currentIndex = static::$currentIndex . '&id_feature=' . (int)Tools::getValue('id_feature') . '&viewfeature';
            }
        } else {
            $this->list_id = 'feature';
        }

        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';

        parent::initProcess();
    }

    /**
     * @throws PrestaShopException
     */
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
     * @throws PrestaShopException
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
     * @throws PrestaShopException
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
     * @return bool|ObjectModel
     * @throws PrestaShopException
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
     * @param int $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int $start
     * @param int|null $limit
     * @param int|bool $idLangShop
     *
     * @throws PrestaShopException
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        if ($this->table == 'feature_value') {
            $this->_select .= ' COALESCE((SELECT COUNT(DISTINCT fp.id_product) FROM '. _DB_PREFIX_ . 'feature_product fp WHERE fp.id_feature = a.id_feature AND fp.id_feature_value = a.id_feature_value), 0) as products';
        }

        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        if ($this->table == 'feature' && $this->_list) {

            $ids = array_map('intval', array_column($this->_list, 'id_feature'));
            $extra = [];
            foreach ($ids as $id) {
                $extra[$id] = [
                    'value' => 0,
                    'products' => 0,
                ];
            }

            $conn = Db::getInstance(_PS_USE_SQL_SLAVE_);

            // count feature values
            $valuesQuery = new DbQuery();
            $valuesQuery->select('id_feature, COUNT(fv.id_feature_value) as count_values');
            $valuesQuery->from('feature_value', 'fv');
            $valuesQuery->where('fv.id_feature IN ('. implode(',', $ids).')');
            $valuesQuery->groupBy('fv.id_feature');
            $res = $conn->executeS($valuesQuery);
            if (is_array($res)) {
                foreach ($res as $row) {
                    $id = (int)$row['id_feature'];
                    $extra[$id]['value'] = (int)$row['count_values'];
                }
            }
            // count products using this feature
            $productsQuery = new DbQuery();
            $productsQuery->select('fp.id_feature, COUNT(DISTINCT fp.id_product) as count_products');
            $productsQuery->from('feature_product', 'fp');
            $productsQuery->where('fp.id_feature IN ('. implode(',', $ids).')');
            $productsQuery->groupBy('fp.id_feature');
            $res = $conn->executeS($productsQuery);
            if (is_array($res)) {
                foreach ($res as $row) {
                    $id = (int)$row['id_feature'];
                    $extra[$id]['products'] = (int)$row['count_products'];
                }
            }

            foreach ($this->_list as &$item) {
                $id = (int) $item['id_feature'];
                $item = array_merge($item, $extra[$id]);
            }
        }
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdatePositions()
    {
        if ($this->hasEditPermission()) {
            $isFeatureValue = Tools::isSubmit('viewfeature');
            $way = (int)Tools::getValue('way');
            $id = (int)Tools::getValue('id');
            $positions = $isFeatureValue
                ? Tools::getValue('feature_value')
                : Tools::getValue('feature');

            $newPositions = [];
            foreach ($positions as $v) {
                if (!empty($v)) {
                    $newPositions[] = $v;
                }
            }

            foreach ($newPositions as $position => $value) {
                $pos = explode('_', $value);
                if (isset($pos[2]) && (int)$pos[2] === $id) {

                    $object = $isFeatureValue
                        ? new FeatureValue((int)$pos[2])
                        : new Feature((int)$pos[2]);

                    if (Validate::isLoadedObject($object)) {
                        if ($object->updatePosition($way, $position)) {
                            $this->ajaxDie('ok position '.(int) $position.' for feature '.(int) $pos[1]);
                        } else {
                            $this->ajaxDie('{"hasError" : true, "errors" : "Can not update feature '.(int) $id.' to position '.(int) $position.' "}');
                        }
                    } else {
                        $this->ajaxDie('{"hasError" : true, "errors" : "This feature ('.(int) $id.') can t be loaded"}');
                    }
                }
            }
        }
    }

    /**
     * Handler for changing multiple values checkbox from list
     *
     * @throws PrestaShopException
     */
    protected function ajaxProcessSetAllowMultipleValuesFeature()
    {
        try {
            $feature = new Feature(Tools::getValue('id_feature'));
            if (! Validate::isLoadedObject($feature)) {
                throw new PrestaShopException($this->l('Feature not found'));
            }
            if ($feature->allows_multiple_values) {
                if (static::existsProductWithMultipleValues($feature->id)) {
                    throw new PrestaShopException($this->l('Not possible to deactivate this functionality because some product has associated multiple values'));
                }
                $text = $this->l('Multiple feature values were disabled for this feature');
                $feature->allows_multiple_values = false;
            } else {
                $text = $this->l('Multiple feature values were enabled for this feature');
                $feature->allows_multiple_values = true;
            }
            $feature->update();
            $this->ajaxDie(json_encode([
                'success' => true,
                'text' => $text
            ]));
        } catch (Exception $e) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'text' => $e->getMessage()
            ]));
        }
    }

    /**
     * Return true, if some product has multiple values for feature $featureId
     *
     * @param int $featureId
     * @return bool
     * @throws PrestaShopException
     */
    protected static function existsProductWithMultipleValues($featureId)
    {
        $sql = (new DbQuery())
            ->select('count(1)')
            ->from('feature_product')
            ->where('id_feature = ' . (int)$featureId)
            ->groupBy('id_product')
            ->having('count(1) > 1');

        return !!Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Return true, if some product has custom values for feature $featureId
     *
     * @param int $featureId
     * @return bool
     * @deprecated 1.4.0
     */
    protected static function featureHasCustomValues($featureId)
    {
        return false;
    }

    /**
     * @param int $value
     * @param array $row
     * @return string
     * @throws PrestaShopException
     */
    public static function getProductsLink($value, $row)
    {
        $params = [ 'id_feature' => (int)$row['id_feature'] ];
        if (isset($row['id_feature_value'])) {
            $params['id_feature_value'] = (int)$row['id_feature_value'];
        }
        $link = Context::getContext()->link->getAdminLink('AdminProducts', true, $params);
        return "<a href=\"$link\">$value</a>";
    }
}
