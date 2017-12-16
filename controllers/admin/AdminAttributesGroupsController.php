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
 * Class AdminAttributesGroupsControllerCore
 *
 * @since 1.0.0
 */
class AdminAttributesGroupsControllerCore extends AdminController
{
    public $bootstrap = true;
    protected $id_attribute;
    protected $position_identifier = 'id_attribute_group';
    protected $attribute_name;

    /**
     * AdminAttributesGroupsControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'attribute_group';
        $this->list_id = 'attribute_group';
        $this->identifier = 'id_attribute_group';
        $this->className = 'AttributeGroup';
        $this->lang = true;
        $this->_defaultOrderBy = 'position';

        $this->fields_list = [
            'id_attribute_group' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'               => [
                'title'      => $this->l('Name'),
                'filter_key' => 'b!name',
                'align'      => 'left',
            ],
            'count_values'       => [
                'title'   => $this->l('Values count'),
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
                'orderby' => false,
                'search'  => false,
            ],
            'position'           => [
                'title'      => $this->l('Position'),
                'filter_key' => 'a!position',
                'position'   => 'position',
                'align'      => 'center',
                'class'      => 'fixed-width-xs',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'icon'    => 'icon-trash',
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];
        $this->fieldImageSettings = ['name' => 'texture', 'dir' => 'co'];

        parent::__construct();
    }

    /**
     * @since 1.0.0
     */
    public function init()
    {
        if (Tools::isSubmit('updateattribute')) {
            $this->display = 'editAttributes';
        } elseif (Tools::isSubmit('submitAddattribute')) {
            $this->display = 'editAttributes';
        } elseif (Tools::isSubmit('submitAddattribute_group')) {
            $this->display = 'add';
        }

        parent::init();
    }

    /**
     * @since 1.0.0
     */
    public function initContent()
    {
        if (!Combination::isFeatureActive()) {
            $url = '<a href="index.php?tab=AdminPerformance&token='.Tools::getAdminTokenLite('AdminPerformance').'#featuresDetachables">'.$this->l('Performance').'</a>';
            $this->displayWarning(sprintf($this->l('This feature has been disabled. You can activate it here: %s.'), $url));

            return;
        }

        // toolbar (save, cancel, new, ..)
        $this->initTabModuleList();
        $this->initToolbar();
        $this->initPageHeaderToolbar();
        if ($this->display == 'edit' || $this->display == 'add') {
            if (!($this->object = $this->loadObject(true))) {
                return;
            }
            $this->content .= $this->renderForm();
        } elseif ($this->display == 'editAttributes') {
            if (!$this->object = new Attribute((int) Tools::getValue('id_attribute'))) {
                return;
            }

            $this->content .= $this->renderFormAttributes();
        } elseif ($this->display != 'view' && !$this->ajax) {
            $this->content .= $this->renderList();
            $this->content .= $this->renderOptions();
        } elseif ($this->display == 'view' && !$this->ajax) {
            $this->content = $this->renderView();
        }

        $this->context->smarty->assign(
            [
                'table'                     => $this->table,
                'current'                   => static::$currentIndex,
                'token'                     => $this->token,
                'content'                   => $this->content,
                'url_post'                  => static::$currentIndex.'&token='.$this->token,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * @since 1.0.0
     */
    public function initToolbar()
    {
        switch ($this->display) {
            // @todo defining default buttons
            case 'add':
            case 'edit':
            case 'editAttributes':
                // Default save button - action dynamically handled in javascript
                $this->toolbar_btn['save'] = [
                    'href' => '#',
                    'desc' => $this->l('Save'),
                ];

                if ($this->display == 'editAttributes' && !$this->id_attribute) {
                    $this->toolbar_btn['save-and-stay'] = [
                        'short'      => 'SaveAndStay',
                        'href'       => '#',
                        'desc'       => $this->l('Save then add another value', null, null, false),
                        'force_desc' => true,
                    ];
                }

                $this->toolbar_btn['back'] = [
                    'href' => static::$currentIndex.'&token='.$this->token,
                    'desc' => $this->l('Back to list', null, null, false),
                ];
                break;
            case 'view':
                $this->toolbar_btn['newAttributes'] = [
                    'href'  => static::$currentIndex.'&updateattribute&id_attribute_group='.(int) Tools::getValue('id_attribute_group').'&token='.$this->token,
                    'desc'  => $this->l('Add New Values', null, null, false),
                    'class' => 'toolbar-new',
                ];

                $this->toolbar_btn['back'] = [
                    'href' => static::$currentIndex.'&token='.$this->token,
                    'desc' => $this->l('Back to list', null, null, false),
                ];
                break;
            default: // list
                $this->toolbar_btn['new'] = [
                    'href' => static::$currentIndex.'&add'.$this->table.'&token='.$this->token,
                    'desc' => $this->l('Add New Attributes', null, null, false),
                ];
                if ($this->can_import) {
                    $this->toolbar_btn['import'] = [
                        'href' => $this->context->link->getAdminLink('AdminImport', true).'&import_type=combinations',
                        'desc' => $this->l('Import', null, null, false),
                    ];
                }
        }
    }

    /**
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_attribute_group'] = [
                'href' => static::$currentIndex.'&addattribute_group&token='.$this->token,
                'desc' => $this->l('Add new attribute', null, null, false),
                'icon' => 'process-icon-new',
            ];
            $this->page_header_toolbar_btn['new_value'] = [
                'href' => static::$currentIndex.'&updateattribute&id_attribute_group='.(int) Tools::getValue('id_attribute_group').'&token='.$this->token,
                'desc' => $this->l('Add new value', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        if ($this->display == 'view') {
            $this->page_header_toolbar_btn['new_value'] = [
                'href' => static::$currentIndex.'&updateattribute&id_attribute_group='.(int) Tools::getValue('id_attribute_group').'&token='.$this->token,
                'desc' => $this->l('Add new value', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $this->table = 'attribute_group';
        $this->identifier = 'id_attribute_group';

        $groupType = [
            [
                'id'   => 'select',
                'name' => $this->l('Drop-down list'),
            ],
            [
                'id'   => 'radio',
                'name' => $this->l('Radio buttons'),
            ],
            [
                'id'   => 'color',
                'name' => $this->l('Color or texture'),
            ],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Attributes'),
                'icon'  => 'icon-info-sign',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                    'col'      => '4',
                    'hint'     => $this->l('Your internal name for this attribute.').'&nbsp;'.$this->l('Invalid characters:').' <>;=#{}',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Public name'),
                    'name'     => 'public_name',
                    'lang'     => true,
                    'required' => true,
                    'col'      => '4',
                    'hint'     => $this->l('The public name for this attribute, displayed to the customers.').'&nbsp;'.$this->l('Invalid characters:').' <>;=#{}',
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Attribute type'),
                    'name'     => 'group_type',
                    'required' => true,
                    'options'  => [
                        'query' => $groupType,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'col'      => '2',
                    'hint'     => $this->l('The way the attribute\'s values will be presented to the customers in the product\'s page.'),
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

        if (!($obj = $this->loadObject(true))) {
            return null;
        }

        return parent::renderForm();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function renderFormAttributes()
    {
        $attributesGroups = AttributeGroup::getAttributesGroups($this->context->language->id);

        $this->table = 'attribute';
        $this->identifier = 'id_attribute';

        $this->show_form_cancel_button = true;
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Values'),
                'icon'  => 'icon-info-sign',
            ],
            'input'  => [
                [
                    'type'     => 'select',
                    'label'    => $this->l('Attribute group'),
                    'name'     => 'id_attribute_group',
                    'required' => true,
                    'options'  => [
                        'query' => $attributesGroups,
                        'id'    => 'id_attribute_group',
                        'name'  => 'name',
                    ],
                    'hint'     => $this->l('Choose the attribute group for this value.'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Value'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                    'hint'     => $this->l('Invalid characters:').' <>;=#{}',
                ],
            ],
        ];

        if (Shop::isFeatureActive()) {
            // We get all associated shops for all attribute groups, because we will disable group shops
            // for attributes that the selected attribute group don't support
            $sql = 'SELECT id_attribute_group, id_shop FROM '._DB_PREFIX_.'attribute_group_shop';
            $associations = [];
            foreach (Db::getInstance()->executeS($sql) as $row) {
                $associations[$row['id_attribute_group']][] = $row['id_shop'];
            }

            $this->fields_form['input'][] = [
                'type'   => 'shop',
                'label'  => $this->l('Shop association'),
                'name'   => 'checkBoxShopAsso',
                'values' => Shop::getTree(),
            ];
        } else {
            $associations = [];
        }

        $this->fields_form['shop_associations'] = json_encode($associations);

        $this->fields_form['input'][] = [
            'type'  => 'color',
            'label' => $this->l('Color'),
            'name'  => 'color',
            'hint'  => $this->l('Choose a color with the color picker, or enter an HTML color (e.g. "lightblue", "#CC6600").'),
        ];

        $this->fields_form['input'][] = [
            'type'  => 'file',
            'label' => $this->l('Texture'),
            'name'  => 'texture',
            'hint'  => [
                $this->l('Upload an image file containing the color texture from your computer.'),
                $this->l('This will override the HTML color!'),
            ],
        ];

        $this->fields_form['input'][] = [
            'type'  => 'current_texture',
            'label' => $this->l('Current texture'),
            'name'  => 'current_texture',
        ];

        $this->fields_form['input'][] = [
            'type' => 'closediv',
            'name' => '',
        ];

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        $this->fields_form['buttons'] = [
            'save-and-stay' => [
                'title' => $this->l('Save then add another value'),
                'name'  => 'submitAdd'.$this->table.'AndStay',
                'type'  => 'submit',
                'class' => 'btn btn-default pull-right',
                'icon'  => 'process-icon-save',
            ],
        ];

        $this->fields_value['id_attribute_group'] = (int) Tools::getValue('id_attribute_group');

        // Override var of Controller
        $this->table = 'attribute';
        $this->className = 'Attribute';
        $this->identifier = 'id_attribute';
        $this->lang = true;
        $this->tpl_folder = 'attributes/';

        // Create object Attribute
        if (!$obj = new Attribute((int) Tools::getValue($this->identifier))) {
            return '';
        }

        $strAttributesGroups = '';
        foreach ($attributesGroups as $attributeGroup) {
            $strAttributesGroups .= '"'.$attributeGroup['id_attribute_group'].'" : '.($attributeGroup['group_type'] == 'color' ? '1' : '0').', ';
        }

        $image = '../img/'.$this->fieldImageSettings['dir'].'/'.(int) $obj->id.'.jpg';

        $this->tpl_form_vars = [
            'strAttributesGroups'      => $strAttributesGroups,
            'colorAttributeProperties' => Validate::isLoadedObject($obj) && $obj->isColorAttribute(),
            'imageTextureExists'       => file_exists(_PS_IMG_DIR_.$this->fieldImageSettings['dir'].'/'.(int) $obj->id.'.jpg'),
            'imageTexture'             => $image,
            'imageTextureUrl'          => Tools::safeOutput($_SERVER['REQUEST_URI']).'&deleteImage=1',
        ];

        return parent::renderForm();
    }

    /**
     * @return false|string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function renderView()
    {
        if (($id = Tools::getValue('id_attribute_group'))) {
            $this->table = 'attribute';
            $this->className = 'Attribute';
            $this->identifier = 'id_attribute';
            $this->position_identifier = 'id_attribute';
            $this->position_group_identifier = 'id_attribute_group';
            $this->list_id = 'attribute_values';
            $this->lang = true;

            $this->context->smarty->assign(
                [
                    'current' => static::$currentIndex.'&id_attribute_group='.(int) $id.'&viewattribute_group',
                ]
            );

            if (!Validate::isLoadedObject($obj = new AttributeGroup((int) $id))) {
                $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');

                return '';
            }

            $this->attribute_name = $obj->name;
            $this->fields_list = [
                'id_attribute' => [
                    'title' => $this->l('ID'),
                    'align' => 'center',
                    'class' => 'fixed-width-xs',
                ],
                'name'         => [
                    'title'      => $this->l('Value'),
                    'width'      => 'auto',
                    'filter_key' => 'b!name',
                ],
            ];

            if ($obj->group_type == 'color') {
                $this->fields_list['color'] = [
                    'title'      => $this->l('Color'),
                    'filter_key' => 'a!color',
                ];
            }

            $this->fields_list['position'] = [
                'title'      => $this->l('Position'),
                'filter_key' => 'a!position',
                'position'   => 'position',
                'class'      => 'fixed-width-md',
            ];

            $this->addRowAction('edit');
            $this->addRowAction('delete');

            $this->_where = 'AND a.`id_attribute_group` = '.(int) $id;
            $this->_orderBy = 'position';

            static::$currentIndex = static::$currentIndex.'&id_attribute_group='.(int) $id.'&viewattribute_group';
            $this->processFilter();

            return parent::renderList();
        }

        return '';
    }

    /**
     * @since 1.0.0
     */
    public function initToolbarTitle()
    {
        $breadExtended = $this->breadcrumbs;

        switch ($this->display) {
            case 'edit':
                $breadExtended[] = $this->l('Edit New Attribute');
                break;

            case 'add':
                $breadExtended[] = $this->l('Add New Attribute');
                break;

            case 'view':
                if (Tools::getIsset('viewattribute_group')) {
                    if (($id = Tools::getValue('id_attribute_group'))) {
                        if (Validate::isLoadedObject($obj = new AttributeGroup((int) $id))) {
                            $breadExtended[] = $obj->name[$this->context->employee->id_lang];
                        }
                    }
                } else {
                    $breadExtended[] = $this->attribute_name[$this->context->employee->id_lang];
                }
                break;

            case 'editAttributes':
                if ($this->id_attribute) {
                    if (($id = Tools::getValue('id_attribute_group'))) {
                        if (Validate::isLoadedObject($obj = new AttributeGroup((int) $id))) {
                            $breadExtended[] = '<a href="'.$this->context->link->getAdminLink('AdminAttributesGroups').'&id_attribute_group='.$id.'&viewattribute_group">'.$obj->name[$this->context->employee->id_lang].'</a>';
                        }
                        if (Validate::isLoadedObject($obj = new Attribute((int) $this->id_attribute))) {
                            $breadExtended[] = sprintf($this->l('Edit: %s'), $obj->name[$this->context->employee->id_lang]);
                        }
                    } else {
                        $breadExtended[] = $this->l('Edit Value');
                    }
                } else {
                    $breadExtended[] = $this->l('Add New Value');
                }
                break;
        }

        if (count($breadExtended) > 0) {
            $this->addMetaTitle($breadExtended[count($breadExtended) - 1]);
        }

        $this->toolbar_title = $breadExtended;
    }

    /**
     * @since 1.0.0
     */
    public function initProcess()
    {
        $this->setTypeAttribute();

        if (Tools::getIsset('viewattribute_group')) {
            $this->list_id = 'attribute_values';

            if (isset($_POST['submitReset'.$this->list_id])) {
                $this->processResetFilters();
            }
        } else {
            $this->list_id = 'attribute_group';
        }

        parent::initProcess();

        if ($this->table == 'attribute') {
            $this->display = 'editAttributes';
            $this->id_attribute = (int) Tools::getValue('id_attribute');
        }
    }

    /**
     * @since 1.0.0
     */
    protected function setTypeAttribute()
    {
        if (Tools::isSubmit('updateattribute') || Tools::isSubmit('deleteattribute') || Tools::isSubmit('submitAddattribute') || Tools::isSubmit('submitBulkdeleteattribute')) {
            $this->table = 'attribute';
            $this->className = 'Attribute';
            $this->identifier = 'id_attribute';

            if ($this->display == 'edit') {
                $this->display = 'editAttributes';
            }
        }
    }

    /**
     * @return Attribute|AttributeGroup
     *
     * @since 1.0.0
     */
    public function processPosition()
    {
        if (Tools::getIsset('viewattribute_group')) {
            $object = new Attribute((int) Tools::getValue('id_attribute'));
            static::$currentIndex = static::$currentIndex.'&viewattribute_group';
        } else {
            $object = new AttributeGroup((int) Tools::getValue('id_attribute_group'));
        }

        if (!Validate::isLoadedObject($object)) {
            $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        } elseif (!$object->updatePosition((int) Tools::getValue('way'), (int) Tools::getValue('position'))) {
            $this->errors[] = Tools::displayError('Failed to update the position.');
        } else {
            $idIdentifierStr = ($idIdentifier = (int) Tools::getValue($this->identifier)) ? '&'.$this->identifier.'='.$idIdentifier : '';
            $redirect = static::$currentIndex.'&'.$this->table.'Orderby=position&'.$this->table.'Orderway=asc&conf=5'.$idIdentifierStr.'&token='.$this->token;
            $this->redirect_after = $redirect;
        }

        return $object;
    }

    public function postProcess()
    {
        if (!Combination::isFeatureActive()) {
            return;
        }

        if (!Tools::getValue($this->identifier) && Tools::getValue('id_attribute') && !Tools::getValue('attributeOrderby')) {
            // Override var of Controller
            $this->table = 'attribute';
            $this->className = 'Attribute';
            $this->identifier = 'id_attribute';
        }

        // If it's an attribute, load object Attribute()
        if (Tools::getValue('updateattribute') || Tools::isSubmit('deleteattribute') || Tools::isSubmit('submitAddattribute')) {
            if ($this->tabAccess['edit'] !== '1') {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            } elseif (!$object = new Attribute((int) Tools::getValue($this->identifier))) {
                $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
            }

            if (Tools::getValue('position') !== false && Tools::getValue('id_attribute')) {
                $_POST['id_attribute_group'] = $object->id_attribute_group;
                if (!$object->updatePosition((int) Tools::getValue('way'), (int) Tools::getValue('position'))) {
                    $this->errors[] = Tools::displayError('Failed to update the position.');
                } else {
                    Tools::redirectAdmin(static::$currentIndex.'&conf=5&token='.Tools::getAdminTokenLite('AdminAttributesGroups').'#details_details_'.$object->id_attribute_group);
                }
            } elseif (Tools::isSubmit('deleteattribute') && Tools::getValue('id_attribute')) {
                if (!$object->delete()) {
                    $this->errors[] = Tools::displayError('Failed to delete the attribute.');
                } else {
                    Tools::redirectAdmin(static::$currentIndex.'&conf=1&token='.Tools::getAdminTokenLite('AdminAttributesGroups'));
                }
            } elseif (Tools::isSubmit('submitAddattribute')) {
                Hook::exec('actionObjectAttributeAddBefore');
                $this->action = 'save';
                $idAttribute = (int) Tools::getValue('id_attribute');
                // Adding last position to the attribute if not exist
                if ($idAttribute <= 0) {
                    $sql = 'SELECT `position`+1
							FROM `'._DB_PREFIX_.'attribute`
							WHERE `id_attribute_group` = '.(int) Tools::getValue('id_attribute_group').'
							ORDER BY position DESC';
                    // set the position of the new group attribute in $_POST for postProcess() method
                    $_POST['position'] = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
                }
                $_POST['id_parent'] = 0;
                $this->processSave($this->token);
            }

            if (Tools::getValue('id_attribute') && Tools::isSubmit('submitAddattribute') && Tools::getValue('color') && !Tools::getValue('filename')) {
                if (file_exists(_PS_IMG_DIR_.$this->fieldImageSettings['dir'].'/'.(int) Tools::getValue('id_attribute').'.jpg')) {
                    unlink(_PS_IMG_DIR_.$this->fieldImageSettings['dir'].'/'.(int) Tools::getValue('id_attribute').'.jpg');
                }
            }
        } else {
            if (Tools::isSubmit('submitBulkdelete'.$this->table)) {
                if ($this->tabAccess['delete'] === '1') {
                    if (isset($_POST[$this->list_id.'Box'])) {
                        /** @var AttributeGroup $object */
                        $object = new $this->className();
                        if ($object->deleteSelection($_POST[$this->list_id.'Box'])) {
                            AttributeGroup::cleanPositions();
                            Tools::redirectAdmin(static::$currentIndex.'&conf=2'.'&token='.$this->token);
                        }
                        $this->errors[] = Tools::displayError('An error occurred while deleting this selection.');
                    } else {
                        $this->errors[] = Tools::displayError('You must select at least one element to delete.');
                    }
                } else {
                    $this->errors[] = Tools::displayError('You do not have permission to delete this.');
                }
                // clean position after delete
                AttributeGroup::cleanPositions();
            } elseif (Tools::isSubmit('submitAdd'.$this->table)) {
                Hook::exec('actionObjectAttributeGroupAddBefore');
                $idAttributeGroup = (int) Tools::getValue('id_attribute_group');
                // Adding last position to the attribute if not exist
                if ($idAttributeGroup <= 0) {
                    $sql = 'SELECT `position`+1
							FROM `'._DB_PREFIX_.'attribute_group`
							ORDER BY position DESC';
                    // set the position of the new group attribute in $_POST for postProcess() method
                    $_POST['position'] = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
                }
                // clean \n\r characters
                foreach ($_POST as $key => $value) {
                    if (preg_match('/^name_/Ui', $key)) {
                        $_POST[$key] = str_replace('\n', '', str_replace('\r', '', $value));
                    }
                }
                parent::postProcess();
            } else {
                parent::postProcess();
                if (Tools::isSubmit('delete'.$this->table)) {
                    AttributeGroup::cleanPositions();
                }
            }
        }
    }

    /**
     * Call the right method for creating or updating object
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function processSave()
    {
        if ($this->display == 'add' || $this->display == 'edit') {
            $this->identifier = 'id_attribute_group';
        }

        if (!$this->id_object) {
            return $this->processAdd();
        } else {
            return $this->processUpdate();
        }
    }

    /**
     * @return AttributeGroup
     *
     * @since 1.0.0
     */
    public function processAdd()
    {
        if ($this->table == 'attribute') {
            /** @var AttributeGroup $object */
            $object = new $this->className();
            foreach (Language::getLanguages(false) as $language) {
                if ($object->isAttribute(
                    (int) Tools::getValue('id_attribute_group'),
                    Tools::getValue('name_'.$language['id_lang']),
                    $language['id_lang']
                )
                ) {
                    $this->errors['name_'.$language['id_lang']] =
                        sprintf(
                            Tools::displayError('The attribute value "%1$s" already exist for %2$s language'),
                            Tools::getValue('name_'.$language['id_lang']),
                            $language['name']
                        );
                }
            }

            if (!empty($this->errors)) {
                return $object;
            }
        }

        $object = parent::processAdd();

        if (Tools::isSubmit('submitAdd'.$this->table.'AndStay') && !count($this->errors)) {
            if ($this->display == 'add') {
                $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'=&conf=3&update'.$this->table.'&token='.$this->token;
            } else {
                $this->redirect_after = static::$currentIndex.'&id_attribute_group='.(int) Tools::getValue('id_attribute_group').'&conf=3&update'.$this->table.'&token='.$this->token;
            }
        }

        if (count($this->errors)) {
            $this->setTypeAttribute();
        }

        return $object;
    }

    /**
     * @return false|ObjectModel
     *
     * @since 1.0.0
     */
    public function processUpdate()
    {
        $object = parent::processUpdate();

        if (Tools::isSubmit('submitAdd'.$this->table.'AndStay') && !count($this->errors)) {
            if ($this->display == 'add') {
                $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'=&conf=3&update'.$this->table.'&token='.$this->token;
            } else {
                $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'=&id_attribute_group='.(int) Tools::getValue('id_attribute_group').'&conf=3&update'.$this->table.'&token='.$this->token;
            }
        }

        if (count($this->errors)) {
            $this->setTypeAttribute();
        }

        if (Tools::isSubmit('updateattribute') || Tools::isSubmit('deleteattribute') || Tools::isSubmit('submitAddattribute') || Tools::isSubmit('submitBulkdeleteattribute')) {
            Tools::clearColorListCache();
        }

        return $object;
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
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        if ($this->display == 'view') {
            foreach ($this->_list as &$list) {
                if (file_exists(_PS_IMG_DIR_.$this->fieldImageSettings['dir'].'/'.(int) $list['id_attribute'].'.jpg')) {
                    if (!isset($list['color']) || !is_array($list['color'])) {
                        $list['color'] = [];
                    }
                    $list['color']['texture'] = '../img/'.$this->fieldImageSettings['dir'].'/'.(int) $list['id_attribute'].'.jpg';
                }
            }
        } else {
            $nbItems = count($this->_list);
            for ($i = 0; $i < $nbItems; ++$i) {
                $item = &$this->_list[$i];

                $query = new DbQuery();
                $query->select('COUNT(a.id_attribute) as count_values');
                $query->from('attribute', 'a');
                $query->join(Shop::addSqlAssociation('attribute', 'a'));
                $query->where('a.id_attribute_group ='.(int) $item['id_attribute_group']);
                $query->groupBy('attribute_shop.id_shop');
                $query->orderBy('count_values DESC');
                $item['count_values'] = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
                unset($query);
            }
        }
    }

    /**
     * Overrides parent to delete items from sublist
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function processBulkDelete()
    {
        // If we are deleting attributes instead of attribute_groups
        if (Tools::getIsset('attributeBox')) {
            $this->className = 'Attribute';
            $this->table = 'attribute';
            $this->boxes = Tools::getValue($this->table.'Box');
        }

        $result = parent::processBulkDelete();
        // Restore vars
        $this->className = 'AttributeGroup';
        $this->table = 'attribute_group';

        return $result;
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessUpdateGroupsPositions()
    {
        $way = (int) Tools::getValue('way');
        $idAttributeGroup = (int) Tools::getValue('id_attribute_group');
        $positions = Tools::getValue('attribute_group');

        $newPositions = [];
        foreach ($positions as $k => $v) {
            if (count(explode('_', $v)) == 4) {
                $newPositions[] = $v;
            }
        }

        foreach ($newPositions as $position => $value) {
            $pos = explode('_', $value);

            if (isset($pos[2]) && (int) $pos[2] === $idAttributeGroup) {
                if ($groupAttribute = new AttributeGroup((int) $pos[2])) {
                    if (isset($position) && $groupAttribute->updatePosition($way, $position)) {
                        echo 'ok position '.(int) $position.' for attribute group '.(int) $pos[2].'\r\n';
                    } else {
                        echo '{"hasError" : true, "errors" : "Can not update the '.(int) $idAttributeGroup.' attribute group to position '.(int) $position.' "}';
                    }
                } else {
                    echo '{"hasError" : true, "errors" : "The ('.(int) $idAttributeGroup.') attribute group cannot be loaded."}';
                }

                break;
            }
        }
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessUpdateAttributesPositions()
    {
        $way = (int) Tools::getValue('way');
        $idAttribute = (int) Tools::getValue('id_attribute');
        $positions = Tools::getValue('attribute');

        if (is_array($positions)) {
            foreach ($positions as $position => $value) {
                $pos = explode('_', $value);

                if ((isset($pos[1]) && isset($pos[2])) && (int) $pos[2] === $idAttribute) {
                    if ($attribute = new Attribute((int) $pos[2])) {
                        if (isset($position) && $attribute->updatePosition($way, $position)) {
                            echo 'ok position '.(int) $position.' for attribute '.(int) $pos[2].'\r\n';
                        } else {
                            echo '{"hasError" : true, "errors" : "Can not update the '.(int) $idAttribute.' attribute to position '.(int) $position.' "}';
                        }
                    } else {
                        echo '{"hasError" : true, "errors" : "The ('.(int) $idAttribute.') attribute cannot be loaded"}';
                    }

                    break;
                }
            }
        }
    }

    /**
     * @param string $url
     */
    public function setRedirectAfter($url)
    {
        $addUrl = '';
        if (Tools::isSubmit('view'.AttributeGroup::$definition['table']) && Tools::getValue(AttributeGroup::$definition['primary'])) {
            $addUrl = '&view'.AttributeGroup::$definition['table'].'&'.AttributeGroup::$definition['primary'].'='.Tools::getValue(AttributeGroup::$definition['primary']);
        }

        $this->redirect_after = $url.$addUrl;
    }
}
