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
 * Class AdminRequestSqlControllerCore
 *
 * @since 1.0.0
 */
class AdminRequestSqlControllerCore extends AdminController
{
    /**
     * @var array : List of encoding type for a file
     */
    public static $encoding_file = [
        ['value' => 1, 'name' => 'utf-8'],
        ['value' => 2, 'name' => 'iso-8859-1'],
    ];

    /**
     * AdminRequestSqlControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'request_sql';
        $this->className = 'RequestSql';
        $this->lang = false;
        $this->export = true;

        $this->context = Context::getContext();

        $this->fields_list = [
            'id_request_sql' => ['title' => $this->l('ID'), 'class' => 'fixed-width-xs'],
            'name'           => ['title' => $this->l('SQL query Name')],
            'sql'            => ['title' => $this->l('SQL query')],
        ];

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Settings'),
                'fields' => [
                    'PS_ENCODING_FILE_MANAGER_SQL' => [
                        'title'      => $this->l('Select your default file encoding'),
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'identifier' => 'value',
                        'list'       => static::$encoding_file,
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        parent::__construct();
    }

    /**
     * Post processing
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return false;
        }

        return parent::postProcess();
    }

    /**
     * method call when ajax request is made with the details row action
     *
     * @see   AdminController::postProcess()
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcess()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_) {
            die(Tools::displayError('This functionality has been disabled.'));
        }
        if ($table = Tools::GetValue('table')) {
            $requestSql = new RequestSql();
            $attributes = $requestSql->getAttributesByTable($table);
            foreach ($attributes as $key => $attribute) {
                unset($attributes[$key]['Null']);
                unset($attributes[$key]['Key']);
                unset($attributes[$key]['Default']);
                unset($attributes[$key]['Extra']);
            }
            $this->ajaxDie(json_encode($attributes));
        }
    }

    /**
     * Child validation
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function _childValidation()
    {
        if (Tools::getValue('submitAdd'.$this->table) && $sql = Tools::getValue('sql')) {
            $requestSql = new RequestSql();
            $parser = $requestSql->parsingSql($sql);
            $validate = $requestSql->validateParser($parser, false, $sql);

            if (!$validate || count($requestSql->error_sql)) {
                $this->displayError($requestSql->error_sql);
            }
        }
    }

    /**
     * Display all errors
     *
     * @param array $e errors
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function displayError($e)
    {
        foreach (array_keys($e) as $key) {
            switch ($key) {
                case 'checkedFrom':
                    if (isset($e[$key]['table'])) {
                        $this->errors[] = sprintf(Tools::displayError('The "%s" table does not exist.'), $e[$key]['table']);
                    } elseif (isset($e[$key]['attribut'])) {
                        $this->errors[] = sprintf(
                            Tools::displayError('The "%1$s" attribute does not exist in the "%2$s" table.'),
                            $e[$key]['attribut'][0],
                            $e[$key]['attribut'][1]
                        );
                    } else {
                        $this->errors[] = Tools::displayError('Undefined "checkedFrom" error');
                    }
                    break;

                case 'checkedSelect':
                    if (isset($e[$key]['table'])) {
                        $this->errors[] = sprintf(Tools::displayError('The "%s" table does not exist.'), $e[$key]['table']);
                    } elseif (isset($e[$key]['attribut'])) {
                        $this->errors[] = sprintf(
                            Tools::displayError('The "%1$s" attribute does not exist in the "%2$s" table.'),
                            $e[$key]['attribut'][0],
                            $e[$key]['attribut'][1]
                        );
                    } elseif (isset($e[$key]['*'])) {
                        $this->errors[] = Tools::displayError('The "*" operator cannot be used in a nested query.');
                    } else {
                        $this->errors[] = Tools::displayError('Undefined "checkedSelect" error');
                    }
                    break;

                case 'checkedWhere':
                    if (isset($e[$key]['operator'])) {
                        $this->errors[] = sprintf(Tools::displayError('The operator "%s" is incorrect.'), $e[$key]['operator']);
                    } elseif (isset($e[$key]['attribut'])) {
                        $this->errors[] = sprintf(
                            Tools::displayError('The "%1$s" attribute does not exist in the "%2$s" table.'),
                            $e[$key]['attribut'][0],
                            $e[$key]['attribut'][1]
                        );
                    } else {
                        $this->errors[] = Tools::displayError('Undefined "checkedWhere" error');
                    }
                    break;

                case 'checkedHaving':
                    if (isset($e[$key]['operator'])) {
                        $this->errors[] = sprintf(Tools::displayError('The "%s" operator is incorrect.'), $e[$key]['operator']);
                    } elseif (isset($e[$key]['attribut'])) {
                        $this->errors[] = sprintf(
                            Tools::displayError('The "%1$s" attribute does not exist in the "%2$s" table.'),
                            $e[$key]['attribut'][0],
                            $e[$key]['attribut'][1]
                        );
                    } else {
                        $this->errors[] = Tools::displayError('Undefined "checkedHaving" error');
                    }
                    break;

                case 'checkedOrder':
                    if (isset($e[$key]['attribut'])) {
                        $this->errors[] = sprintf(
                            Tools::displayError('The "%1$s" attribute does not exist in the "%2$s" table.'),
                            $e[$key]['attribut'][0],
                            $e[$key]['attribut'][1]
                        );
                    } else {
                        $this->errors[] = Tools::displayError('Undefined "checkedOrder" error');
                    }
                    break;

                case 'checkedGroupBy':
                    if (isset($e[$key]['attribut'])) {
                        $this->errors[] = sprintf(
                            Tools::displayError('The "%1$s" attribute does not exist in the "%2$s" table.'),
                            $e[$key]['attribut'][0],
                            $e[$key]['attribut'][1]
                        );
                    } else {
                        $this->errors[] = Tools::displayError('Undefined "checkedGroupBy" error');
                    }
                    break;

                case 'checkedLimit':
                    $this->errors[] = Tools::displayError('The LIMIT clause must contain numeric arguments.');
                    break;

                case 'returnNameTable':
                    if (isset($e[$key]['reference'])) {
                        $this->errors[] = sprintf(
                            Tools::displayError('The "%1$s" reference does not exist in the "%2$s" table.'),
                            $e[$key]['reference'][0],
                            $e[$key]['attribut'][1]
                        );
                    } else {
                        $this->errors[] = Tools::displayError('When multiple tables are used, each attribute must refer back to a table.');
                    }
                    break;

                case 'testedRequired':
                    $this->errors[] = sprintf(Tools::displayError('%s does not exist.'), $e[$key]);
                    break;

                case 'testedUnauthorized':
                    $this->errors[] = sprintf(Tools::displayError('Is an unauthorized keyword.'), $e[$key]);
                    break;
            }
        }
    }

    /**
     * Display export action link
     *
     * @param string $token
     * @param int    $id
     *
     * @return string
     * @throws Exception
     * @throws SmartyException
     *
     * @since 1.0.0
     */
    public function displayExportLink($token, $id)
    {
        $tpl = $this->createTemplate('list_action_export.tpl');

        $tpl->assign(
            [
                'href'   => static::$currentIndex.'&token='.$this->token.'&'.$this->identifier.'='.$id.'&export'.$this->table.'=1',
                'action' => $this->l('Export'),
            ]
        );

        return $tpl->fetch();
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
        parent::initProcess();
        if (Tools::getValue('export'.$this->table)) {
            $this->display = 'export';
            $this->action = 'export';
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
        $this->initTabModuleList();
        // toolbar (save, cancel, new, ..)
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
        } elseif ($this->display == 'export') {
            $this->generateExport();
        } elseif (!$this->ajax) {
            $this->content .= $this->renderList();
            $this->content .= $this->renderOptions();
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
     * Initialize toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initToolbar()
    {
        if ($this->display == 'view' && $idRequest = Tools::getValue('id_request_sql')) {
            $this->toolbar_btn['edit'] = [
                'href' => static::$currentIndex.'&amp;updaterequest_sql&amp;token='.$this->token.'&amp;id_request_sql='.(int) $idRequest,
                'desc' => $this->l('Edit this SQL query'),
            ];
        }

        parent::initToolbar();

        if ($this->display == 'options') {
            unset($this->toolbar_btn['new']);
        }
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
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_request'] = [
                'href' => static::$currentIndex.'&addrequest_sql&token='.$this->token,
                'desc' => $this->l('Add new SQL query', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
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
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('SQL query'),
                'icon'  => 'icon-cog',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('SQL query name'),
                    'name'     => 'name',
                    'size'     => 103,
                    'required' => true,
                ],
                [
                    'type'     => 'textarea',
                    'label'    => $this->l('SQL query'),
                    'name'     => 'sql',
                    'cols'     => 100,
                    'rows'     => 10,
                    'required' => true,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $request = new RequestSql();
        $this->tpl_form_vars = ['tables' => $request->getTables()];

        return parent::renderForm();
    }

    /**
     * Render view
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderView()
    {
        /** @var RequestSql $obj */
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        try {
            if ($results = Db::getInstance()->executeS($obj->sql)) {
                foreach (array_keys($results[0]) as $key) {
                    $tabKey[] = $key;
                }

                $view['name'] = $obj->name;
                $view['key'] = $tabKey;
                $view['results'] = $results;

                $this->toolbar_title = $obj->name;

                $requestSql = new RequestSql();
                $view['attributes'] = $requestSql->attributes;
            } else {
                $view['error'] = true;
            }
        } catch (PrestaShopException $e) {
            $this->errors[] = $e->getMessage();
            $view = [
                'name'    => '',
                'key'     => '',
                'results' => [],
            ];
        }

        $this->tpl_view_vars = [
            'view' => $view,
        ];

        return parent::renderView();
    }

    /**
     * Generating a export file
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function generateExport()
    {
        $id = Tools::getValue($this->identifier);
        $exportDir = defined('_PS_HOST_MODE_') ? _PS_ROOT_DIR_.'/export/' : _PS_ADMIN_DIR_.'/export/';
        if (!Validate::isFileName($id)) {
            die(Tools::displayError());
        }
        $file = 'request_sql_'.$id.'.csv';
        if ($csv = fopen($exportDir.$file, 'w')) {
            $sql = RequestSql::getRequestSqlById($id);

            if ($sql) {
                $results = Db::getInstance()->executeS($sql[0]['sql']);
                foreach (array_keys($results[0]) as $key) {
                    $tabKey[] = $key;
                    fputs($csv, $key.';');
                }
                foreach ($results as $result) {
                    fputs($csv, "\n");
                    foreach ($tabKey as $name) {
                        fputs($csv, '"'.strip_tags($result[$name]).'";');
                    }
                }
                if (file_exists($exportDir.$file)) {
                    $filesize = filesize($exportDir.$file);
                    $uploadMaxFilesize = Tools::convertBytes(ini_get('upload_max_filesize'));
                    if ($filesize < $uploadMaxFilesize) {
                        if (Configuration::get('PS_ENCODING_FILE_MANAGER_SQL')) {
                            $charset = Configuration::get('PS_ENCODING_FILE_MANAGER_SQL');
                        } else {
                            $charset = static::$encoding_file[0]['name'];
                        }

                        header('Content-Type: text/csv; charset='.$charset);
                        header('Cache-Control: no-store, no-cache');
                        header('Content-Disposition: attachment; filename="'.$file.'"');
                        header('Content-Length: '.$filesize);
                        readfile($exportDir.$file);
                        die();
                    } else {
                        $this->errors[] = Tools::DisplayError('The file is too large and can not be downloaded. Please use the LIMIT clause in this query.');
                    }
                }
            }
        }
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
        // Set toolbar options
        $this->display = null;
        $this->initToolbar();

        $this->displayWarning($this->l('When saving the query, only the "SELECT" SQL statement is allowed.'));
        $this->displayInformation(
            '
		<strong>'.$this->l('How do I create a new SQL query?').'</strong><br />
		<ul>
			<li>'.$this->l('Click "Add New".').'</li>
			<li>'.$this->l('Fill in the fields and click "Save".').'</li>
			<li>'.$this->l('You can then view the query results by clicking on the Edit action in the dropdown menu: ').' <i class="icon-pencil"></i></li>
			<li>'.$this->l('You can also export the query results as a CSV file by clicking on the Export button: ').' <i class="icon-cloud-upload"></i></li>
		</ul>'
        );

        $this->addRowAction('export');
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    /**
     * Render options
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderOptions()
    {
        // Set toolbar options
        $this->display = 'options';
        $this->show_toolbar = true;
        $this->toolbar_scroll = true;
        $this->initToolbar();

        return parent::renderOptions();
    }
}
