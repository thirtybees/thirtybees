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
 * Class AdminQuickAccessesControllerCore
 *
 * @since 1.0.0
 */
class AdminQuickAccessesControllerCore extends AdminController
{
    /**
     * AdminQuickAccessesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'quick_access';
        $this->className = 'QuickAccess';
        $this->lang = true;

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->context = Context::getContext();

        if (!Tools::getValue('realedit')) {
            $this->deleted = false;
        }

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fields_list = [
            'id_quick_access' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'            => [
                'title' => $this->l('Name'),
            ],
            'link'            => [
                'title' => $this->l('Link'),
            ],
            'new_window'      => [
                'title'  => $this->l('New window'),
                'align'  => 'center',
                'type'   => 'bool',
                'active' => 'new_window',
                'class'  => 'fixed-width-sm',
            ],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Quick Access menu'),
                'icon'  => 'icon-align-justify',
            ],
            'input'  => [
                [
                    'type'      => 'text',
                    'label'     => $this->l('Name'),
                    'name'      => 'name',
                    'lang'      => true,
                    'maxlength' => 32,
                    'required'  => true,
                    'hint'      => $this->l('Forbidden characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('URL'),
                    'name'      => 'link',
                    'maxlength' => 128,
                    'required'  => true,
                    'hint'      => $this->l('If it\'s a URL that comes from your Back Office, you MUST remove the security token.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Open in new window'),
                    'name'     => 'new_window',
                    'required' => false,
                    'values'   => [
                        [
                            'id'    => 'new_window_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Enabled').'" />',
                        ],
                        [
                            'id'    => 'new_window_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('Disabled').'" />',
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
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
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_quick_access'] = [
                'href' => static::$currentIndex.'&addquick_access&token='.$this->token,
                'desc' => $this->l('Add new quick access', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
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
        if ((isset($_GET['new_window'.$this->table]) || isset($_GET['new_window'])) && Tools::getValue($this->identifier)) {
            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'newWindow';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }

        parent::initProcess();
    }

    /**
     * Get link token
     *
     * @param array $item
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function getLinkToken($item)
    {
        $url = parse_url($item['link']);
        parse_str($url['query'], $query);
        $controller = $query['controller'];
        $item['token'] = Tools::getAdminTokenLite($controller);

        return $item;
    }

    /**
     * Ajax process get URL
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessGetUrl()
    {
        if (mb_strtolower(Tools::getValue('method')) === 'add') {
            $params['new_window'] = 0;
            $params['name_'.(int) Configuration::get('PS_LANG_DEFAULT')] = Tools::getValue('name');
            $params['link'] = 'index.php?'.Tools::getValue('url');
            $params['submitAddquick_access'] = 1;
            unset($_POST['name']);
            $_POST = array_merge($_POST, $params);
            die($this->addQuickLink());
        } elseif (mb_strtolower(Tools::getValue('method')) === 'remove') {
            $params['deletequick_access'] = 1;
            $_POST = array_merge($_POST, $params);
            die($this->processDelete());
        }
    }

    /**
     * Add quick link
     *
     * @return bool|string
     *
     * @since 1.0.0
     */
    public function addQuickLink()
    {
        if (!isset($this->className) || empty($this->className)) {
            return false;
        }
        $this->validateRules();

        if (count($this->errors) <= 0) {
            $this->object = new $this->className();
            $this->copyFromPost($this->object, $this->table);
            $exists = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                ->select('`id_quick_access`')
                ->from('quick_access')
                ->where('`link` = \''.pSQL($this->object->link).'\'')
            );
            if ($exists) {
                return true;
            }
            $this->beforeAdd($this->object);

            if (method_exists($this->object, 'add') && !$this->object->add()) {
                $this->errors[] = Tools::displayError('An error occurred while creating an object.').
                    ' <b>'.$this->table.' ('.Db::getInstance()->getMsgError().')</b>';
            } /* voluntary do affectation here */
            elseif (($_POST[$this->identifier] = $this->object->id) && $this->postImage($this->object->id) && !count($this->errors) && $this->_redirect) {
                Logger::addLog(sprintf($this->l('%s addition', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int) $this->object->id, true, (int) $this->context->employee->id);
                $this->afterAdd($this->object);
            }
        }

        $this->errors = array_unique($this->errors);
        if (!empty($this->errors)) {
            $this->errors['has_errors'] = true;
            $this->ajaxDie(json_encode($this->errors));

            return false;
        }

        return $this->getQuickAccessesList();
    }

    /**
     * Get quick access list
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getQuickAccessesList()
    {
        $links = QuickAccess::getQuickAccesses($this->context->language->id);

        return json_encode(array_map([$this, 'getLinkToken'], $links));
    }

    /**
     * Process delete
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function processDelete()
    {
        parent::processDelete();

        return $this->getQuickAccessesList();
    }

    /**
     * Process new window
     *
     * @return false|ObjectModel|QuickAccess
     *
     * @since 1.0.0
     */
    public function processNewWindow()
    {
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            /** @var QuickAccess $object */
            if ($object->toggleNewWindow()) {
                $this->redirect_after = static::$currentIndex.'&conf=5&token='.$this->token;
            } else {
                $this->errors[] = Tools::displayError('An error occurred while updating new window property.');
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating the new window property for this object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }

        return $object;
    }
}
