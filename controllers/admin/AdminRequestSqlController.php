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
 * Class AdminRequestSqlControllerCore
 *
 * @property RequestSql|null $object
 */
class AdminRequestSqlControllerCore extends AdminController
{

    /**
     * AdminRequestSqlControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'request_sql';
        $this->className = 'RequestSql';
        $this->lang = false;

        $this->context = Context::getContext();

        $this->fields_list = [
            'id_request_sql' => ['title' => $this->l('ID'), 'class' => 'fixed-width-xs'],
            'name'           => ['title' => $this->l('SQL query Name')],
            'sql'            => ['title' => $this->l('SQL query')],
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
     * @throws PrestaShopException
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
     * @return void
     *
     * @throws PrestaShopException
     * @see AdminController::postProcess()
     */
    public function ajaxProcess()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_) {
            $this->ajaxDie(Tools::displayError('This functionality has been disabled.'));
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
     * @param int $id
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
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
     * @throws PrestaShopException
     */
    public function initToolbar()
    {
        if ($this->display == 'view' && $idRequest = Tools::getIntValue('id_request_sql')) {
            $this->toolbar_btn['edit'] = [
                'href' => static::$currentIndex.'&amp;updaterequest_sql&amp;token='.$this->token.'&amp;id_request_sql='.$idRequest,
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
     * @throws PrestaShopException
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
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
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderView()
    {
        /** @var RequestSql $obj */
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        try {
            if ($results = Db::readOnly()->getArray($obj->sql)) {
                $view['name'] = $obj->name;
                $view['key'] = array_keys($results[0]);
                $view['results'] = $results;

                $this->toolbar_title = $obj->name;

                $requestSql = new RequestSql();
                $view['attributes'] = $requestSql->attributes;
            } else {
                $view['error'] = true;
            }
        } catch (PrestaShopException $e) {
            $this->errors[] = Tools::safeOutput($e->getMessage());
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
     * @param string $textDelimiter
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processExport($textDelimiter = '"')
    {
        $id = Tools::getIntValue($this->identifier);
        $sql = RequestSql::getRequestSqlById($id);
        if (! $sql) {
            $this->errors[] =Tools::displayError("SQL with not found");
            $this->redirect_after = Context::getContext()->link->getAdminLink('AdminRequestSql');
            return;
        }

        // export settings
        $exportDir = _PS_ADMIN_DIR_.'/export/';
        $file = 'request_sql_'.$id.'.csv';
        $separator = Configuration::get('TB_EXPORT_FIELD_DELIMITER') ? Configuration::get('TB_EXPORT_FIELD_DELIMITER') : ',';
        $enclosure = '"';
        $escape = "";

        $conn = Db::readOnly();
        try {
            $results = $conn->getArray($sql);
        } catch (PrestaShopDatabaseException $e) {
            $this->errors[] = $e->getMessage();
            $this->redirect_after = Context::getContext()->link->getAdminLink('AdminRequestSql');
            return;
        }

        if (! $results) {
            $this->errors[] =Tools::displayError('This SQL query has no result.');
            $this->redirect_after = Context::getContext()->link->getAdminLink('AdminRequestSql');
            return;
        }

        $filepath = $exportDir . $file;
        $csv = fopen($filepath, 'w');
        if (! $csv) {
            $this->errors[] = sprintf(Tools::displayError('Failed to create export file: %s'), $filepath);
            $this->redirect_after = Context::getContext()->link->getAdminLink('AdminRequestSql');
            return;
        }

        // export csv
        $keys = array_keys($results[0]);
        fputcsv($csv, $keys, $separator, $enclosure, $escape);
        foreach ($results as $result) {
            fputcsv($csv, $result, $separator, $enclosure, $escape);
        }
        fclose($csv);
        $filesize = filesize($filepath);

        header('Content-Type: text/csv; charset=utf-8');
        header('Cache-Control: no-store, no-cache');
        header('Content-Disposition: attachment; filename="'.$file.'"');
        header('Content-Length: '.$filesize);
        readfile($filepath);
        exit;
    }

    /**
     * Render list
     *
     * @return false|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
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
			<li>'.$this->l('Click "Add new SQL query".').'</li>
			<li>'.$this->l('Fill in the fields and click "Save".').'</li>
			<li>'.$this->l('You can then view the query results by clicking on the query in the list or the View action in the dropdown menu: ').' <i class="icon-search-plus"></i></li>
			<li>'.$this->l('You can edit the query by clicking on the Edit action in the dropdown menu: ').' <i class="icon-pencil"></i></li>
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
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
