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
 * Class AdminTabsControllerCore
 *
 * @since 1.0.0
 */
class AdminTabsControllerCore extends AdminController
{
    // @codingStandardsIgnoreStart
    /** @var string $position_identifier */
    protected $position_identifier = 'id_tab';
    // @codingStandardsIgnoreEnd

    /**
     * AdminTabsControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->multishop_context = Shop::CONTEXT_ALL;
        $this->table = 'tab';
        $this->list_id = 'tab';
        $this->className = 'Tab';
        $this->lang = true;
        $this->fieldImageSettings = [
            'name' => 'icon',
            'dir'  => 't',
        ];
        $this->imageType = 'gif';
        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];
        $this->fields_list = [
            'id_tab'     => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'       => [
                'title' => $this->l('Name'),
            ],
            'class_name' => [
                'title' => $this->l('Class'),
            ],
            'module'     => [
                'title' => $this->l('Module'),
            ],
            'active'     => [
                'title'   => $this->l('Enabled'),
                'align'   => 'center',
                'active'  => 'status',
                'type'    => 'bool',
                'orderby' => false,
            ],
            'position'   => [
                'title'      => $this->l('Position'),
                'filter_key' => 'a!position',
                'position'   => 'position',
                'align'      => 'center',
                'class'      => 'fixed-width-md',
            ],
        ];

        parent::__construct();
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
        $this->page_header_toolbar_title = $this->l('Menus');

        if ($this->display == 'details') {
            $this->page_header_toolbar_btn['back_to_list'] = [
                'href' => $this->context->link->getAdminLink('AdminTabs'),
                'desc' => $this->l('Back to list', null, null, false),
                'icon' => 'process-icon-back',
            ];
        } elseif (empty($this->display)) {
            $this->page_header_toolbar_btn['new_menu'] = [
                'href' => static::$currentIndex.'&addtab&token='.$this->token,
                'desc' => $this->l('Add new menu', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * AdminController::renderForm() override
     *
     * @see AdminController::renderForm()
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $tabs = Tab::getTabs($this->context->language->id, 0);
        // If editing, we clean itself
        if (Tools::isSubmit('id_tab')) {
            foreach ($tabs as $key => $tab) {
                if ($tab['id_tab'] == Tools::getValue('id_tab')) {
                    unset($tabs[$key]);
                }
            }
        }

        // added category "Home" in var $tabs
        $tabZero = [
            'id_tab' => 0,
            'name'   => $this->l('Home'),
        ];
        array_unshift($tabs, $tabZero);

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Menus'),
                'icon'  => 'icon-list-ul',
            ],
            'input'  => [
                [
                    'type'     => 'hidden',
                    'name'     => 'position',
                    'required' => false,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'hint'     => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Class'),
                    'name'     => 'class_name',
                    'required' => true,
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Module'),
                    'name'  => 'module',
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Status'),
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
                    'hint'     => $this->l('Show or hide menu.'),
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_form['input'][] = [
            'type'    => 'select',
            'label'   => $this->l('Parent'),
            'name'    => 'id_parent',
            'options' => [
                'query' => $tabs,
                'id'    => 'id_tab',
                'name'  => 'name',
            ],
        ];

        return parent::renderForm();
    }

    /**
     * AdminController::renderList() override
     *
     * @see AdminController::renderList()
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('details');
        $this->addRowAction('delete');

        $this->_where = 'AND a.`id_parent` = 0';
        $this->_orderBy = 'position';

        return parent::renderList();
    }

    /**
     * Initialize processing
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initProcess()
    {
        if (Tools::getIsset('details'.$this->table)) {
            $this->list_id = 'details';

            if (isset($_POST['submitReset'.$this->list_id])) {
                $this->processResetFilters();
            }
        } else {
            $this->list_id = 'tab';
        }

        parent::initProcess();
    }

    /**
     * Render details
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderDetails()
    {
        if (($id = Tools::getValue('id_tab'))) {
            $this->lang = false;
            $this->list_id = 'details';
            $this->addRowAction('edit');
            $this->addRowAction('delete');
            $this->toolbar_btn = [];

            /** @var Tab $tab */
            $tab = $this->loadObject($id);
            $this->toolbar_title = $tab->name[$this->context->employee->id_lang];

            $this->_select = 'b.`name`';
            $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'tab_lang` b ON (b.`id_tab` = a.`id_tab` AND b.`id_lang` = '.$this->context->language->id.')';
            $this->_where = 'AND a.`id_parent` = '.(int) $id;
            $this->_orderBy = 'position';
            $this->_use_found_rows = false;

            static::$currentIndex = static::$currentIndex.'&details'.$this->table;
            $this->processFilter();

            return parent::renderList();
        }

        return '';
    }

    /**
     * Post processing
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return;
        }
        /* PrestaShop demo mode*/

        if (($idTab = (int) Tools::getValue('id_tab')) && ($direction = Tools::getValue('move')) && Validate::isLoadedObject($tab = new Tab($idTab))) {
            if ($tab->move($direction)) {
                Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
            }
        } elseif (Tools::getValue('position') && !Tools::isSubmit('submitAdd'.$this->table)) {
            if ($this->tabAccess['edit'] !== '1') {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            } elseif (!Validate::isLoadedObject($object = new Tab((int) Tools::getValue($this->identifier)))) {
                $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').
                    ' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
            }
            if (!$object->updatePosition((int) Tools::getValue('way'), (int) Tools::getValue('position'))) {
                $this->errors[] = Tools::displayError('Failed to update the position.');
            } else {
                Tools::redirectAdmin(static::$currentIndex.'&conf=5&token='.Tools::getAdminTokenLite('AdminTabs'));
            }
        } elseif (Tools::isSubmit('submitAdd'.$this->table) && Tools::getValue('id_tab') === Tools::getValue('id_parent')) {
            $this->errors[] = Tools::displayError('You can\'t put this menu inside itself. ');
        } elseif (Tools::isSubmit('submitAdd'.$this->table) && $idParent = (int) Tools::getValue('id_parent')) {
            $this->redirect_after = static::$currentIndex.'&id_'.$this->table.'='.$idParent.'&details'.$this->table.'&conf=4&token='.$this->token;
        } elseif (isset($_GET['details'.$this->table]) && is_array($this->bulk_actions)) {
            $submitBulkActions = array_merge(
                [
                    'enableSelection'  => [
                        'text' => $this->l('Enable selection'),
                        'icon' => 'icon-power-off text-success',
                    ],
                    'disableSelection' => [
                        'text' => $this->l('Disable selection'),
                        'icon' => 'icon-power-off text-danger',
                    ],
                ],
                $this->bulk_actions
            );
            foreach ($submitBulkActions as $bulkAction => $params) {
                if (Tools::isSubmit('submitBulk'.$bulkAction.$this->table) || Tools::isSubmit('submitBulk'.$bulkAction)) {
                    if ($this->tabAccess['edit'] === '1') {
                        $this->action = 'bulk'.$bulkAction;
                        $this->boxes = Tools::getValue($this->list_id.'Box');
                    } else {
                        $this->errors[] = Tools::displayError('You do not have permission to edit this.');
                    }
                    break;
                } elseif (Tools::isSubmit('submitBulk')) {
                    if ($this->tabAccess['edit'] === '1') {
                        $this->action = 'bulk'.Tools::getValue('select_submitBulk');
                        $this->boxes = Tools::getValue($this->list_id.'Box');
                    } else {
                        $this->errors[] = Tools::displayError('You do not have permission to edit this.');
                    }
                    break;
                }
            }
        } else {
            // Temporary add the position depend of the selection of the parent category
            if (!Tools::isSubmit('id_tab')) { // @todo Review
                $_POST['position'] = Tab::getNbTabs(Tools::getValue('id_parent'));
            }
        }

        if (!count($this->errors)) {
            parent::postProcess();
        }
    }

    /**
     * Ajax process update positions
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessUpdatePositions()
    {
        $way = (int) (Tools::getValue('way'));
        $idTab = (int) (Tools::getValue('id'));
        $positions = Tools::getValue('tab');

        // when changing positions in a tab sub-list, the first array value is empty and needs to be removed
        if (!$positions[0]) {
            unset($positions[0]);
            // reset indexation from 0
            $positions = array_merge($positions);
        }

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);

            if (isset($pos[2]) && (int) $pos[2] === $idTab) {
                if ($tab = new Tab((int) $pos[2])) {
                    if (isset($position) && $tab->updatePosition($way, $position)) {
                        echo 'ok position '.(int) $position.' for tab '.(int) $pos[1].'\r\n';
                    } else {
                        echo '{"hasError" : true, "errors" : "Can not update tab '.(int) $idTab.' to position '.(int) $position.' "}';
                    }
                } else {
                    echo '{"hasError" : true, "errors" : "This tab ('.(int) $idTab.') can t be loaded"}';
                }

                break;
            }
        }
    }

    /**
     * After image upload
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function afterImageUpload()
    {
        /** @var Tab $obj */
        if (!($obj = $this->loadObject(true))) {
            return;
        }
        @rename(_PS_IMG_DIR_.'t/'.$obj->id.'.gif', _PS_IMG_DIR_.'t/'.$obj->class_name.'.gif');
    }
}
