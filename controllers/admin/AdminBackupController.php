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

@ini_set('max_execution_time', 0);

/**
 * Class AdminBackupControllerCore
 *
 * @since 1.0.0
 */
class AdminBackupControllerCore extends AdminController
{
    /** @var string The field we are sorting on */
    protected $sort_by = 'date';

    /**
     * AdminBackupControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'backup';
        $this->className = 'PrestaShopBackup';
        $this->identifier = 'filename';
        parent::__construct();

        $this->fields_list = [
            'date'     => ['title' => $this->l('Date'), 'type' => 'datetime', 'class' => 'fixed-width-lg', 'orderby' => false, 'search' => false],
            'age'      => ['title' => $this->l('Age'), 'orderby' => false, 'search' => false],
            'filename' => ['title' => $this->l('File name'), 'orderby' => false, 'search' => false],
            'filesize' => ['title' => $this->l('File size'), 'class' => 'fixed-width-sm', 'orderby' => false, 'search' => false],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'), 'icon' => 'icon-trash',
            ],
        ];

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Backup options'),
                'fields' => [
                    'PS_BACKUP_ALL'        => [
                        'title' => $this->l('Ignore statistics tables'),
                        'desc'  => $this->l('Drop existing tables during import.').'<br />'._DB_PREFIX_.'connections, '._DB_PREFIX_.'connections_page, '._DB_PREFIX_.'connections_source, '._DB_PREFIX_.'guest, '._DB_PREFIX_.'statssearch',
                        'cast'  => 'intval',
                        'type'  => 'bool',
                    ],
                    'PS_BACKUP_DROP_TABLE' => [
                        'title' => $this->l('Drop existing tables during import'),
                        'hint'  => [
                            $this->l('If enabled, the backup script will drop your tables prior to restoring data.'),
                            $this->l('(ie. "DROP TABLE IF EXISTS")'),
                        ],
                        'cast'  => 'intval',
                        'type'  => 'bool',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];
    }

    /**
     * @return false|string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        $this->addRowAction('view');
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
        if (!($object = $this->loadObject())) {
            $this->errors[] = Tools::displayError('The object could not be loaded.');
        }

        if ($object->id) {
            $this->tpl_view_vars = ['url_backup' => $object->getBackupURL()];
        } elseif ($object->error) {
            $this->errors[] = $object->error;
            $this->tpl_view_vars = ['errors' => $this->errors];
        }

        return parent::renderView();
    }

    /**
     * Load class object using identifier in $_GET (if possible)
     * otherwise return an empty object
     * This method overrides the one in AdminTab because AdminTab assumes the id is a UnsignedInt
     * "Backups" Directory in admin directory must be writeable (CHMOD 777)
     *
     * @param bool $opt Return an empty object if load fail
     *
     * @return object
     *
     * @since 1.0.0
     */
    protected function loadObject($opt = false)
    {
        if (($id = Tools::getValue($this->identifier)) && PrestaShopBackup::backupExist($id)) {
            return new $this->className($id);
        }

        $obj = new $this->className();
        $obj->error = Tools::displayError('The backup file does not exist');

        return $obj;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function initViewDownload()
    {
        $this->tpl_folder = $this->tpl_folder.'download/';

        return parent::renderView();
    }

    /**
     * @since 1.0.0
     */
    public function initToolbar()
    {
        switch ($this->display) {
            case 'add':
            case 'edit':
            case 'view':
                $this->toolbar_btn['cancel'] = [
                    'href' => static::$currentIndex.'&token='.$this->token,
                    'desc' => $this->l('Cancel'),
                ];
                break;
            case 'options':
                $this->toolbar_btn['save'] = [
                    'href' => '#',
                    'desc' => $this->l('Save'),
                ];
                break;
        }
    }

    /**
     * @since 1.0.0
     */
    public function initContent()
    {
        if ($this->display == 'add') {
            $this->display = 'list';
        }

        return parent::initContent();
    }

    /**
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

        // Test if the backup dir is writable
        if (!is_writable(PrestaShopBackup::getBackupPath())) {
            $this->warnings[] = $this->l('The "Backups" directory located in the admin directory must be writable (CHMOD 755 / 777).');
        } elseif ($this->display == 'add') {
            if (($object = $this->loadObject())) {
                if (!$object->add()) {
                    $this->errors[] = $object->error;
                } else {
                    $this->context->smarty->assign(
                        [
                            'conf'          => $this->l('It appears the backup was successful, however you must download and carefully verify the backup file before proceeding.'),
                            'backup_url'    => $object->getBackupURL(),
                            'backup_weight' => number_format((filesize($object->id) * 0.000001), 2, '.', ''),
                        ]
                    );
                }
            }
        }

        parent::postProcess();
    }

    /**
     * @param int         $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int         $start
     * @param int|null    $limit
     * @param int|null    $idLangShop
     *
     * @since 1.0.0
     */
    public function getList(
        $idLang,
        $orderBy = null,
        $orderWay = null,
        $start = 0,
        $limit = null,
        $idLangShop = null
    ) {
        if (!Validate::isTableOrIdentifier($this->table)) {
            die('filter is corrupted');
        }

        if (empty($orderBy)) {
            $orderBy = Tools::getValue($this->table.'Orderby', $this->_defaultOrderBy);
        }

        if (empty($orderWay)) {
            $orderWay = Tools::getValue($this->table.'Orderway', 'ASC');
        }

        // Try and obtain getList arguments from $_GET
        $orderBy = Tools::getValue($this->table.'Orderby');
        $orderWay = Tools::getValue($this->table.'Orderway');

        // Validate the orderBy and orderWay fields
        switch ($orderBy) {
            case 'filename':
            case 'filesize':
            case 'date':
            case 'age':
                break;
            default:
                $orderBy = 'date';
        }
        switch ($orderWay) {
            case 'asc':
            case 'desc':
                break;
            default:
                $orderWay = 'desc';
        }
        if (empty($limit)) {
            $limit = ((!isset($this->context->cookie->{$this->table.'_pagination'})) ? $this->_pagination[0] : $limit =
                $this->context->cookie->{$this->table.'_pagination'});
        }
        $limit = (int) Tools::getValue('pagination', $limit);
        $this->context->cookie->{$this->table.'_pagination'} = $limit;

        /* Determine offset from current page */
        if (!empty($_POST['submitFilter'.$this->list_id]) && is_numeric($_POST['submitFilter'.$this->list_id])) {
            $start = (int) $_POST['submitFilter'.$this->list_id] - 1 * $limit;
        }

        $this->_lang = (int) $idLang;
        $this->_orderBy = $orderBy;
        $this->_orderWay = strtoupper($orderWay);
        $this->_list = [];

        // Find all the backups
        $dh = @opendir(PrestaShopBackup::getBackupPath());

        if ($dh === false) {
            $this->errors[] = Tools::displayError('Unable to open your backup directory');

            return;
        }
        while (($file = readdir($dh)) !== false) {
            if (preg_match('/^([_a-zA-Z0-9\-]*[\d]+-[a-z\d]+)\.sql(\.gz|\.bz2)?$/', $file, $matches) == 0) {
                continue;
            }
            $timestamp = (int) $matches[1];
            $date = date('Y-m-d H:i:s', $timestamp);
            $age = time() - $timestamp;
            if ($age < 3600) {
                $age = '< 1 '.$this->l('Hour', 'AdminTab', false, false);
            } elseif ($age < 86400) {
                $age = floor($age / 3600);
                $age = $age.' '.(($age == 1) ? $this->l('Hour', 'AdminTab', false, false) :
                        $this->l('Hours', 'AdminTab', false, false));
            } else {
                $age = floor($age / 86400);
                $age = $age.' '.(($age == 1) ? $this->l('Day') : $this->l('Days', 'AdminTab', false, false));
            }
            $size = filesize(PrestaShopBackup::getBackupPath($file));
            $this->_list[] = [
                'filename'      => $file,
                'age'           => $age,
                'date'          => $date,
                'filesize'      => number_format($size / 1000, 2).' Kb',
                'timestamp'     => $timestamp,
                'filesize_sort' => $size,
            ];
        }
        closedir($dh);
        $this->_listTotal = count($this->_list);

        // Sort the _list based on the order requirements
        switch ($this->_orderBy) {
            case 'filename':
                $this->sort_by = 'filename';
                $sorter = 'strSort';
                break;
            case 'filesize':
                $this->sort_by = 'filesize_sort';
                $sorter = 'intSort';
                break;
            case 'age':
            case 'date':
                $this->sort_by = 'timestamp';
                $sorter = 'intSort';
                break;
        }
        usort($this->_list, [$this, $sorter]);
        $this->_list = array_slice($this->_list, $start, $limit);
    }

    /**
     * @param int $a
     * @param int $b
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function intSort($a, $b)
    {
        return $this->_orderWay == 'ASC' ? $a[$this->sort_by] - $b[$this->sort_by] :
            $b[$this->sort_by] - $a[$this->sort_by];
    }

    /**
     * @param string $a
     * @param string $b
     *
     * @return int
     *
     * @since 1.0.0
     */
    public function strSort($a, $b)
    {
        return $this->_orderWay == 'ASC' ? strcmp($a[$this->sort_by], $b[$this->sort_by]) :
            strcmp($b[$this->sort_by], $a[$this->sort_by]);
    }
}
