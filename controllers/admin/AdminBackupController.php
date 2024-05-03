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

@ini_set('max_execution_time', 0);

/**
 * Class AdminBackupControllerCore
 */
class AdminBackupControllerCore extends AdminController
{
    const ORDER_BY_FILENAME = 'filename';
    const ORDER_BY_FILESIZE = 'filesize';
    const ORDER_BY_DATE = 'date';
    const ORDER_BY_AGE = 'age';

    /**
     * @var string The field we are sorting on
     */
    protected $sort_by = self::ORDER_BY_DATE;

    /**
     * AdminBackupControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'backup';
        $this->className = 'PrestaShopBackup';
        $this->identifier = 'filename';
        parent::__construct();

        $this->fields_list = [
            'date'     => ['title' => $this->l('Date'), 'type' => 'datetime', 'class' => 'fixed-width-lg', 'orderby' => self::ORDER_BY_DATE, 'search' => false],
            'age'      => ['title' => $this->l('Age'), 'orderby' => self::ORDER_BY_AGE, 'search' => false],
            'filename' => ['title' => $this->l('File name'), 'orderby' => self::ORDER_BY_FILENAME, 'search' => false],
            'filesize' => ['title' => $this->l('File size'), 'class' => 'fixed-width-sm', 'orderby' => self::ORDER_BY_FILESIZE, 'search' => false],
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
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        $this->addRowAction('view');
        $this->addRowAction('delete');
        $this->tpl_list_vars = ['show_filters' => true];

        return parent::renderList();
    }

    /**
     * @return void
     */
    public function processFilter()
    {
        // no-op, because the list is not SQL based
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
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
     * @return PrestaShopBackup
     *
     * @throws PrestaShopException
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
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initViewDownload()
    {
        $this->tpl_folder = $this->tpl_folder.'download/';

        return parent::renderView();
    }

    /**
     * @return void
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
     * @return void
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        if ($this->display == 'add') {
            $this->display = 'list';
        }

        parent::initContent();
    }

    /**
     * @return void
     * @throws PrestaShopException
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
     * @param int $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int $start
     * @param int|null $limit
     * @param int|null $idLangShop
     *
     * @throws PrestaShopException
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
            throw new PrestaShopException(Tools::displayError('Filter is corrupted'));
        }

        // Try and obtain getList arguments from $_GET
        $orderBy = strtolower((string)Tools::getValue($this->table.'Orderby'));
        $orderWay = strtolower((string)Tools::getValue($this->table.'Orderway'));

        // Validate the orderBy and orderWay fields
        if (! in_array($orderBy, [
            static::ORDER_BY_FILENAME,
            static::ORDER_BY_FILESIZE,
            static::ORDER_BY_DATE,
            static::ORDER_BY_AGE,
        ])) {
            $orderBy = static::ORDER_BY_DATE;
        }

        if (! in_array($orderWay, ['asc', 'desc'])) {
            $orderWay = 'desc';
        }

        if (empty($limit)) {
            $limit = isset($this->context->cookie->{$this->table.'_pagination'})
                ? (int)$this->context->cookie->{$this->table.'_pagination'}
                : $this->_pagination[0];
        }
        $limit = Tools::getIntValue('pagination', $limit);
        $this->context->cookie->{$this->table.'_pagination'} = $limit;

        /* Determine offset from current page */
        if (!empty($_POST['submitFilter'.$this->list_id]) && is_numeric($_POST['submitFilter'.$this->list_id])) {
            $start = (int) $_POST['submitFilter'.$this->list_id] - $limit;
        }

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
            case static::ORDER_BY_FILENAME:
                $this->sort_by = 'filename';
                $sorter = 'strSort';
                break;
            case static::ORDER_BY_FILESIZE:
                $this->sort_by = 'filesize_sort';
                $sorter = 'intSort';
                break;
            case static::ORDER_BY_AGE:
            case static::ORDER_BY_DATE:
            default:
                $this->sort_by = 'timestamp';
                $sorter = 'intSort';
                break;
        }
        usort($this->_list, [$this, $sorter]);
        $this->_list = array_slice($this->_list, $start, $limit);
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    public function intSort($a, $b)
    {
        return $this->_orderWay == 'ASC'
            ? $a[$this->sort_by] - $b[$this->sort_by]
            : $b[$this->sort_by] - $a[$this->sort_by];
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    public function strSort($a, $b)
    {
        return $this->_orderWay == 'ASC'
            ? strcmp($a[$this->sort_by], $b[$this->sort_by])
            : strcmp($b[$this->sort_by], $a[$this->sort_by]);
    }
}
