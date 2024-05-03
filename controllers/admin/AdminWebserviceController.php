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
 * Class AdminWebserviceControllerCore
 *
 * @property WebserviceKey|null $object
 */
class AdminWebserviceControllerCore extends AdminController
{
    /**
     * AdminWebserviceControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'webservice_account';
        $this->className = 'WebserviceKey';
        $this->lang = false;
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'employee` b ON (b.`id_employee` = a.`context_employee_id`)';
        $this->_select = 'concat(concat(b.`firstname`, " "), b.`lastname`) as employee';

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fields_list = [
            'key'         => [
                'title' => $this->l('Key'),
                'class' => 'fixed-width-md',
            ],
            'description' => [
                'title'   => $this->l('Key description'),
                'align'   => 'left',
                'orderby' => false,
            ],
            'employee'    => [
                'title'   => $this->l('Employee context'),
                'align'   => 'left',
            ],
            'active'      => [
                'title'   => $this->l('Enabled'),
                'align'   => 'center',
                'active'  => 'status',
                'type'    => 'bool',
                'orderby' => false,
                'class'   => 'fixed-width-xs',
            ],
        ];

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Configuration'),
                'fields' => [
                    'PS_WEBSERVICE' => [
                        'title' => $this->l('Enable thirty bees\' webservice'),
                        'cast'  => 'intval',
                        'type'  => 'bool',
                    ],
                    'WEBSERVICE_LOG_ENABLED' => [
                        'title' => $this->l('Enable logging'),
                        'desc'  => Translate::ppTags(sprintf($this->l('All webservice requests and responses will be saved in directory [1]%s[/1]'), WebserviceLogger::getDirectory()), ['<code>']),
                        'cast'  => 'intval',
                        'type'  => 'bool',
                    ]
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        parent::__construct();
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_webservice'] = [
                'href' => static::$currentIndex.'&addwebservice_account&token='.$this->token,
                'desc' => $this->l('Add new webservice key', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        /** @var WebserviceKey|false $obj */
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        // retrieve list of employees
        $employees = array_map(function($row) {
            return [
                'id_employee' => $row['id_employee'],
                'name' => $row['firstname'] . ' ' . $row['lastname']
            ];
        }, Employee::getEmployees(false));

        // generate form
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Webservice Accounts'),
                'icon'  => 'icon-lock',
            ],
            'input'  => [
                [
                    'type'     => 'textbutton',
                    'label'    => $this->l('Key'),
                    'name'     => 'key',
                    'id'       => 'code',
                    'required' => true,
                    'hint'     => $this->l('Webservice account key.'),
                    'button'   => [
                        'label'      => $this->l('Generate!'),
                        'attributes' => [
                            'onclick' => 'gencode(32)',
                        ],
                    ],
                ],
                [
                    'type'  => 'textarea',
                    'label' => $this->l('Key description'),
                    'name'  => 'description',
                    'rows'  => 3,
                    'cols'  => 110,
                    'hint'  => $this->l('Quick description of the key: who it is for, what permissions it has, etc.'),
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Employee context'),
                    'name'    => 'context_employee_id',
                    'options' => [
                        'query' => $employees,
                        'id'    => 'id_employee',
                        'name'  => 'name',
                    ],
                    'desc'    => $this->l('Select employee in which context API request will be executed'),
                    'hint'    => $this->l('This is useful for audit trail, as changes created by API calls can be associated with dedicated user')
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
                ],
                [
                    'type'  => 'resources',
                    'label' => $this->l('Permissions'),
                    'name'  => 'resources',
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

        $resources = WebserviceRequest::getResources();
        $permissions = WebserviceKey::getPermissionForAccount($obj->key);

        $this->tpl_form_vars = [
            'resources'  => $resources,
            'permissions' => $permissions,
        ];

        $this->fields_value = [
            'resources' => $resources
        ];
        return parent::renderForm();
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
        if ($this->display != 'add' && $this->display != 'edit') {
            $this->checkForWarning();
        }

        parent::initContent();
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function checkForWarning()
    {
        if (!extension_loaded('SimpleXML')) {
            $this->warnings[] = $this->l('Please activate the \'SimpleXML\' PHP extension.');
        }

        foreach ($this->_list as $k => $item) {
            if ($item['is_module'] && $item['class_name'] && $item['module_name'] &&
                ($instance = Module::getInstanceByName($item['module_name'])) &&
                (method_exists($instance, 'useNormalPermissionBehaviour') && !$instance->useNormalPermissionBehaviour())
            ) {
                unset($this->_list[$k]);
            }
        }

        $this->renderList();
    }

    /**
     * Function used to render the options for this controller
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderOptions()
    {
        if ($this->fields_options && is_array($this->fields_options)) {
            $helper = new HelperOptions();
            $this->setHelperDisplay($helper);
            $helper->toolbar_scroll = true;
            $helper->toolbar_btn = [
                'save' => [
                    'href' => '#',
                    'desc' => $this->l('Save'),
                ],
            ];
            $helper->id = $this->id;
            $helper->tpl_vars = $this->tpl_option_vars;
            $options = $helper->generateOptions($this->fields_options);

            return $options;
        }

        return '';
    }

    /**
     * Initialize processing
     *
     * @return void
     */
    public function initProcess()
    {
        parent::initProcess();
        // This is a composite page, we don't want the "options" display mode
        if ($this->display == 'options') {
            $this->display = '';
        }
    }

    /**
     * Post processing
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $key = Tools::getValue('key');
        if ($key) {
            if (strlen($key) < 32) {
                $this->errors[] = Tools::displayError('Key length must be 32 character long.');
            }
            if (WebserviceKey::keyExists($key) && !Tools::getIntValue('id_webservice_account')) {
                $this->errors[] = Tools::displayError('This key already exists.');
            }
        }
        return parent::postProcess();
    }

    /**
     * Process update options
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    protected function processUpdateOptions()
    {
        parent::processUpdateOptions();
        Tools::generateHtaccess();
    }

    /**
     * @param ObjectModel $object
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function afterAdd($object)
    {
        Tools::generateHtaccess();
        WebserviceKey::setPermissionForAccount($object->id, Tools::getValue('resources', []));
    }

    /**
     * @param ObjectModel $object
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function afterUpdate($object)
    {
        Tools::generateHtaccess();
        WebserviceKey::setPermissionForAccount($object->id, Tools::getValue('resources', []));
    }
}
