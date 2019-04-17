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
 * @deprecated 1.0.0
 */
abstract class AdminTabCore
{
    // @codingStandardsIgnoreStart
    public static $currentIndex;
    public static $tabParenting = [
        'AdminCms'                => 'AdminCmsContent',
        'AdminCmsCategories'      => 'AdminCmsContent',
        'AdminOrdersStates'       => 'AdminStatuses',
        'AdminAttributeGenerator' => 'AdminProducts',
        'AdminAttributes'         => 'AdminAttributesGroups',
        'AdminFeaturesValues'     => 'AdminFeatures',
        'AdminReturnStates'       => 'AdminStatuses',
        'AdminStatsTab'           => 'AdminStats',
    ];
    /** @var int Tab id */
    public $id = -1;
    /** @var string Associated table name */
    public $table;
    /** @var string Tab name */
    public $className;
    /** @var string Security token */
    public $token;
    /** @var bool Automatically join language table if true */
    public $lang = false;
    /** @var bool Tab Automatically displays edit/delete icons if true */
    public $edit = false;
    /** @var bool Tab Automatically displays view icon if true */
    public $view = false;
    /** @var bool Tab Automatically displays delete icon if true */
    public $delete = false;
    /** @var bool Table records are not deleted but marked as deleted */
    public $deleted = false;
    /** @var bool Tab Automatically displays duplicate icon if true */
    public $duplicate = false;
    /** @var bool Content line is clickable if true */
    public $noLink = false;
    /** @var bool select other required fields */
    public $requiredDatabase = false;
    /** @var bool Tab Automatically displays '$color' as background color on listing if true */
    public $colorOnBackground = false;
    /** @var array Name and directory where class image are located */
    public $fieldImageSettings = [];
    /** @var string Image type */
    public $imageType = 'jpg';
    /** @var array Fields to display in list */
    public $fieldsDisplay = [];
    public $optionTitle = null;
    /** @var string shop */
    public $shopLinkType;
    /** @var bool */
    public $shopShareDatas = false;
    /** @var array Errors displayed after post processing */
    public $_errors = [];
    /** @var array tabAccess */
    public $tabAccess;
    /** @var string specificConfirmDelete */
    public $specificConfirmDelete = null;
    public $smarty;
    public $_fieldsOptions = [];
    /**
     * @since 1.5.0
     * @var array
     */
    public $optionsList = [];
    /**
     * @since 1.5.0
     * @var Context
     */
    public $context;
    public $ajax = false;
    /**
     * if true, ajax-tab will not wait 1 sec
     *
     * @var bool
     */
    public $ignore_sleep = false;
    /** @var string Object identifier inside the associated table */
    protected $identifier = false;
    /** @var string Add fields into data query to display list */
    protected $_select;
    /** @var string Join tables into data query to display list */
    protected $_join;
    /** @var string Add conditions into data query to display list */
    protected $_where;
    /** @var string Group rows into data query to display list */
    protected $_group;
    /** @var string Having rows into data query to display list */
    protected $_having;
    /** @var array Cache for query results */
    protected $_list = [];
    /** @var int Number of results in list */
    protected $_listTotal = 0;
    /** @var array WHERE clause determined by filter fields */
    protected $_filter;
    /** @var array Temporary SQL table WHERE clause determinated by filter fields */
    protected $_tmpTableFilter = '';
    /** @var array Number of results in list per page (used in select field) */
    protected $_pagination = [20, 50, 100, 300, 1000];
    /** @var string ORDER BY clause determined by field/arrows in list header */
    protected $_orderBy;
    /** @var string Default ORDER BY clause when $_orderBy is not defined */
    protected $_defaultOrderBy = false;
    /** @var string Order way (ASC, DESC) determined by arrows in list header */
    protected $_orderWay;
    /** @var int Max image size for upload
     * As of 1.5 it is recommended to not set a limit to max image size
     **/
    protected $maxImageSize;
    /** @var array Confirmations displayed after post processing */
    protected $_conf;
    /** @var object Object corresponding to the tab */
    protected $_object = false;
    protected $identifiersDnd = ['id_product' => 'id_product', 'id_category' => 'id_category_to_move', 'id_cms_category' => 'id_cms_category_to_move', 'id_cms' => 'id_cms', 'id_attribute' => 'id_attribute', 'id_attribute_group' => 'id_attribute_group', 'id_feature' => 'id_feature', 'id_carrier' => 'id_carrier'];
    /** @var bool Redirect or not ater a creation */
    protected $_redirect = true;
    /** @var bool If false, don't add form tags in options forms */
    protected $formOptions = true;
    protected $_languages = null;
    protected $_defaultFormLanguage = null;
    protected $_includeObj = [];
    protected $_includeVars = false;
    protected $_includeContainer = true;
    // @codingStandardsIgnoreEnd

    /**
     * AdminTabCore constructor.
     *
     * @deprecated 1.0.0
     */
    public function __construct()
    {
        $this->context = Context::getContext();

        $this->id = Tab::getIdFromClassName(get_class($this));
        $this->_conf = [
            1  => $this->l('Deletion successful'), 2 => $this->l('Selection successfully deleted'),
            3  => $this->l('Creation successful'), 4 => $this->l('Update successful'),
            5  => $this->l('Status update successful'), 6 => $this->l('Settings update successful'),
            7  => $this->l('Image successfully deleted'), 8 => $this->l('Module downloaded successfully'),
            9  => $this->l('Thumbnails successfully regenerated'), 10 => $this->l('Message sent to the customer'),
            11 => $this->l('Comment added'), 12 => $this->l('Module installed successfully'),
            13 => $this->l('Module uninstalled successfully'), 14 => $this->l('Language successfully copied'),
            15 => $this->l('Translations successfully added'), 16 => $this->l('Module transplanted successfully to hook'),
            17 => $this->l('Module removed successfully from hook'), 18 => $this->l('Upload successful'),
            19 => $this->l('Duplication completed successfully'), 20 => $this->l('Translation added successfully but the language has not been created'),
            21 => $this->l('Module reset successfully'), 22 => $this->l('Module deleted successfully'),
            23 => $this->l('Localization pack imported successfully'), 24 => $this->l('Refund Successful'),
            25 => $this->l('Images successfully moved'),
        ];
        if (!$this->identifier) {
            $this->identifier = 'id_'.$this->table;
        }
        if (!$this->_defaultOrderBy) {
            $this->_defaultOrderBy = $this->identifier;
        }
        $className = get_class($this);
        $this->token = Tools::getAdminToken($className.(int) $this->id.(int) $this->context->employee->id);
        if (!Shop::isFeatureActive()) {
            $this->shopLinkType = '';
        }
    }

    /**
     * Uses translations files to find a translation for a given string (string should be in english).
     *
     * @param string $string       term or expression in english
     * @param string $class
     * @param bool   $addslashes   if set to true, the return value will pass through addslashes(). Otherwise, stripslashes().
     * @param bool   $htmlentities if set to true(default), the return value will pass through htmlentities($string, ENT_QUOTES, 'utf-8')
     *
     * @return string The translation if available, or the english default text.
     *
     * @deprecated 1.0.0
     */
    protected function l($string, $class = 'AdminTab', $addslashes = false, $htmlentities = true)
    {
        // if the class is extended by a module, use modules/[module_name]/xx.php lang file
        $currentClass = get_class($this);
        if (Module::getModuleNameFromClass($currentClass)) {
            $string = str_replace('\'', '\\\'', $string);

            return Translate::getModuleTranslation(Module::$classInModule[$currentClass], $string, $currentClass);
        }
        global $_LANGADM;

        if ($class == __CLASS__) {
            $class = 'AdminTab';
        }

        $md5Key = md5(str_replace('\'', '\\\'', $string));

        $thisKey = get_class($this) . $md5Key;
        $classKey = $class . $md5Key;
        $str = $string;
        if (array_key_exists($thisKey, $_LANGADM) && $_LANGADM[$thisKey] !== '') {
            $str = $_LANGADM[$thisKey];
        } else if (array_key_exists($classKey, $_LANGADM) && $_LANGADM[$classKey] !== '') {
            $str = $_LANGADM[$classKey];
        }
        $str = $htmlentities ? htmlentities($str, ENT_QUOTES, 'utf-8') : $str;

        return str_replace('"', '&quot;', ($addslashes ? addslashes($str) : stripslashes($str)));
    }

    /**
     *
     *
     * @param      $table
     * @param bool $idObject
     *
     * @return array|void
     *
     * @deprecated 1.0.0
     */
    protected static function getAssoShop($table, $idObject = false)
    {
        if (Shop::isTableAssociated($table)) {
            $type = 'shop';
        } else {
            return;
        }

        $assos = [];
        foreach ($_POST as $k => $row) {
            if (!preg_match('/^checkBox'.Tools::toCamelCase($type, true).'Asso_'.$table.'_([0-9]+)?_([0-9]+)$/Ui', $k, $res)) {
                continue;
            }
            $idAssoObject = (!empty($res[1]) ? $res[1] : $idObject);
            $assos[] = ['id_object' => (int) $idAssoObject, 'id_'.$type => (int) $res[2]];
        }

        return [$assos, $type];
    }

    /**
     * ajaxDisplay is the default ajax return sytem
     *
     * @return void
     *
     * @deprecated 1.0.0
     */
    public function displayAjax()
    {
    }

    /**
     * Manage page display (form, list...)
     *
     * @deprecated 1.0.0
     */
    public function display()
    {
        // Include other tab in current tab
        if ($this->includeSubTab('display', ['submitAdd2', 'add', 'update', 'view'])) {
        } // Include current tab
        elseif ((Tools::getValue('submitAdd'.$this->table) && count($this->_errors)) || isset($_GET['add'.$this->table])) {
            if ($this->tabAccess['add'] === '1') {
                $this->displayForm();
                if ($this->tabAccess['view']) {
                    echo '<br /><br /><a href="'.((Tools::getValue('back')) ? Tools::getValue('back') : static::$currentIndex.'&token='.$this->token).'"><img src="../img/admin/arrow2.gif" /> '.((Tools::getValue('back')) ? $this->l('Back') : $this->l('Back to list')).'</a><br />';
                }
            } else {
                echo $this->l('You do not have permission to add here');
            }
        } elseif (isset($_GET['update'.$this->table])) {
            if ($this->tabAccess['edit'] === '1' || ($this->table == 'employee' && $this->context->employee->id == Tools::getValue('id_employee'))) {
                $this->displayForm();
                if ($this->tabAccess['view']) {
                    echo '<br /><br /><a href="'.((Tools::getValue('back')) ? Tools::getValue('back') : static::$currentIndex.'&token='.$this->token).'"><img src="../img/admin/arrow2.gif" /> '.((Tools::getValue('back')) ? $this->l('Back') : $this->l('Back to list')).'</a><br />';
                }
            } else {
                echo $this->l('You do not have permission to edit here');
            }
        } elseif (isset($_GET['view'.$this->table])) {
            $this->{'view'.$this->table}();
        } else {
            $this->getList($this->context->language->id);
            $this->displayList();
            echo '<br />';
            $this->displayOptionsList();
            $this->displayRequiredFields();
            $this->includeSubTab('display');
        }
    }

    /**
     *
     *
     * @param       $methodname
     * @param array $actions
     *
     * @deprecated 1.0.0
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function includeSubTab($methodname, $actions = [])
    {
        if (!isset($this->_includeTab) || !is_array($this->_includeTab)) {
            return false;
        }
        $key = 0;
        $inc = false;
        foreach ($this->_includeTab as $subtab => $extraVars) {
            /* New tab loading */
            $classname = 'Admin'.$subtab;
            if (($module = Db::getInstance()->getValue('SELECT `module` FROM `'._DB_PREFIX_.'tab` WHERE `class_name` = \''.pSQL($classname).'\'')) && file_exists(_PS_MODULE_DIR_.'/'.$module.'/'.$classname.'.php')) {
                include_once(_PS_MODULE_DIR_.'/'.$module.'/'.$classname.'.php');
            } elseif (file_exists(_PS_ADMIN_DIR_.'/tabs/'.$classname.'.php')) {
                include_once('tabs/'.$classname.'.php');
            }
            if (!isset($this->_includeObj[$key])) {
                $this->_includeObj[$key] = new $classname;
            }

            /** @var static $adminTab */
            $adminTab = $this->_includeObj[$key];
            $adminTab->token = $this->token;

            /* Extra variables addition */
            if (!empty($extraVars) && is_array($extraVars)) {
                foreach ($extraVars as $varKey => $varValue) {
                    $adminTab->$varKey = $varValue;
                }
            }

            /* Actions management */
            foreach ($actions as $action) {
                switch ($action) {

                    case 'submitAdd1':
                        if (Tools::getValue('submitAdd'.$adminTab->table)) {
                            $okInc = true;
                        }
                        break;
                    case 'submitAdd2':
                        if (Tools::getValue('submitAdd'.$adminTab->table) && count($adminTab->_errors)) {
                            $okInc = true;
                        }
                        break;
                    case 'submitDel':
                        if (Tools::getValue('submitDel'.$adminTab->table)) {
                            $okInc = true;
                        }
                        break;
                    case 'submitFilter':
                        if (Tools::isSubmit('submitFilter'.$adminTab->table)) {
                            $okInc = true;
                        }
                    case 'submitReset':
                        if (Tools::isSubmit('submitReset'.$adminTab->table)) {
                            $okInc = true;
                        }
                    default:
                        if (isset($_GET[$action.$adminTab->table])) {
                            $okInc = true;
                        }
                }
            }
            $inc = false;
            if ((isset($okInc) && $okInc) || !count($actions)) {
                if (!$adminTab->viewAccess()) {
                    echo Tools::displayError('Access denied.');

                    return false;
                }
                if (!count($actions)) {
                    if (($methodname == 'displayErrors' && count($adminTab->_errors)) || $methodname != 'displayErrors') {
                        echo(isset($this->_includeTabTitle[$key]) ? '<h2>'.$this->_includeTabTitle[$key].'</h2>' : '');
                    }
                }
                if ($adminTab->_includeVars) {
                    foreach ($adminTab->_includeVars as $var => $value) {
                        $adminTab->$var = $this->$value;
                    }
                }
                $adminTab->$methodname();
                $inc = true;
            }
            $key++;
        }

        return $inc;
    }

    /**
     * Display form
     *
     * @param bool $firstCall
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    public function displayForm($firstCall = true)
    {
        $allowEmployeeFormLang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        if ($allowEmployeeFormLang && !$this->context->cookie->employee_form_lang) {
            $this->context->cookie->employee_form_lang = (int) (Configuration::get('PS_LANG_DEFAULT'));
        }
        $useLangFromCookie = false;
        $this->_languages = Language::getLanguages(false);
        if ($allowEmployeeFormLang) {
            foreach ($this->_languages as $lang) {
                if ($this->context->cookie->employee_form_lang == $lang['id_lang']) {
                    $useLangFromCookie = true;
                }
            }
        }
        if (!$useLangFromCookie) {
            $this->_defaultFormLanguage = (int) (Configuration::get('PS_LANG_DEFAULT'));
        } else {
            $this->_defaultFormLanguage = (int) ($this->context->cookie->employee_form_lang);
        }

        // Only if it is the first call to displayForm, otherwise it has already been defined
        if ($firstCall) {
            echo '
			<script type="text/javascript">
				$(document).ready(function() {
					id_language = '.$this->_defaultFormLanguage.';
					languages = new Array();';
            foreach ($this->_languages as $k => $language) {
                echo '
					languages['.$k.'] = {
						id_lang: '.(int) $language['id_lang'].',
						iso_code: \''.$language['iso_code'].'\',
						name: \''.htmlentities($language['name'], ENT_COMPAT, 'UTF-8').'\'
					};';
            }
            echo '
					displayFlags(languages, id_language, '.$allowEmployeeFormLang.');
				});
			</script>';
        }
    }

    /**
     * Get the current objects' list form the database
     *
     * @param int    $idLang    Language used for display
     * @param string $orderBy   ORDER BY clause
     * @param string $_orderWay Order way (ASC, DESC)
     * @param int    $start     Offset in LIMIT clause
     * @param int    $limit     Row count in LIMIT clause
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        /* Manage default params values */
        if (empty($limit)) {
            $limit = ((!isset($this->context->cookie->{$this->table.'_pagination'})) ? $this->_pagination[1] : $limit = $this->context->cookie->{$this->table.'_pagination'});
        }

        if (!Validate::isTableOrIdentifier($this->table)) {
            $this->_errors[] = Tools::displayError('Table name is invalid:').' "'.$this->table.'"';
        }

        if (empty($orderBy)) {
            $orderBy = $this->context->cookie->__get($this->table.'Orderby') ? $this->context->cookie->__get($this->table.'Orderby') : $this->_defaultOrderBy;
        }
        if (empty($orderWay)) {
            $orderWay = $this->context->cookie->__get($this->table.'Orderway') ? $this->context->cookie->__get($this->table.'Orderway') : 'ASC';
        }

        $limit = (int) (Tools::getValue('pagination', $limit));
        $this->context->cookie->{$this->table.'_pagination'} = $limit;

        /* Check params validity */
        if (!Validate::isOrderBy($orderBy) || !Validate::isOrderWay($orderWay)
            || !is_numeric($start) || !is_numeric($limit)
            || !Validate::isUnsignedId($idLang)
        ) {
            die(Tools::displayError('get list params is not valid'));
        }

        /* Determine offset from current page */
        if ((isset($_POST['submitFilter'.$this->table]) ||
                isset($_POST['submitFilter'.$this->table.'_x']) ||
                isset($_POST['submitFilter'.$this->table.'_y'])) &&
            !empty($_POST['submitFilter'.$this->table]) &&
            is_numeric($_POST['submitFilter'.$this->table])
        ) {
            $start = (int) ($_POST['submitFilter'.$this->table] - 1) * $limit;
        }

        /* Cache */
        $this->_lang = (int) ($idLang);
        $this->_orderBy = $orderBy;
        $this->_orderWay = mb_strtoupper($orderWay);

        /* SQL table : orders, but class name is Order */
        $sqlTable = $this->table == 'order' ? 'orders' : $this->table;

        // Add SQL shop restriction
        $selectShop = $joinShop = $whereShop = '';
        if ($this->shopLinkType) {
            $selectShop = ', shop.name as shop_name ';
            $joinShop = ' LEFT JOIN '._DB_PREFIX_.$this->shopLinkType.' shop
							ON a.id_'.$this->shopLinkType.' = shop.id_'.$this->shopLinkType;
            $whereShop = Shop::addSqlRestriction($this->shopShareDatas, 'a', $this->shopLinkType);
        }

        $asso = Shop::getAssoTable($this->table);
        if ($asso !== false && $asso['type'] == 'shop') {
            $filterKey = $asso['type'];
            $idenfierShop = Shop::getContextListShopID();
        }

        $filterShop = '';
        if (isset($filterKey)) {
            if (!$this->_group) {
                $this->_group = 'GROUP BY a.'.pSQL($this->identifier);
            } elseif (!preg_match('#(\s|,)\s*a\.`?'.pSQL($this->identifier).'`?(\s|,|$)#', $this->_group)) {
                $this->_group .= ', a.'.pSQL($this->identifier);
            }

            if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_ALL && !preg_match('#`?'.preg_quote(_DB_PREFIX_.$this->table.'_'.$filterKey).'`? *sa#', $this->_join)) {
                $filterShop = 'JOIN `'._DB_PREFIX_.$this->table.'_'.$filterKey.'` sa ON (sa.'.$this->identifier.' = a.'.$this->identifier.' AND sa.id_'.$filterKey.' IN ('.implode(', ', $idenfierShop).'))';
            }
        }
        ///////////////////////
        /* Query in order to get results with all fields */
        $sql = 'SELECT SQL_CALC_FOUND_ROWS
			'.($this->_tmpTableFilter ? ' * FROM (SELECT ' : '').'
			'.($this->lang ? 'b.*, ' : '').'a.*'.(isset($this->_select) ? ', '.$this->_select.' ' : '').$selectShop.'
			FROM `'._DB_PREFIX_.$sqlTable.'` a
			'.$filterShop.'
			'.($this->lang ? 'LEFT JOIN `'._DB_PREFIX_.$this->table.'_lang` b ON (b.`'.$this->identifier.'` = a.`'.$this->identifier.'` AND b.`id_lang` = '.(int) $idLang.($idLangShop ? ' AND b.`id_shop`='.(int) $idLangShop : '').')' : '').'
			'.(isset($this->_join) ? $this->_join.' ' : '').'
			'.$joinShop.'
			WHERE 1 '.(isset($this->_where) ? $this->_where.' ' : '').($this->deleted ? 'AND a.`deleted` = 0 ' : '').(isset($this->_filter) ? $this->_filter : '').$whereShop.'
			'.(isset($this->_group) ? $this->_group.' ' : '').'
			'.((isset($this->_filterHaving) || isset($this->_having)) ? 'HAVING ' : '').(isset($this->_filterHaving) ? ltrim($this->_filterHaving, ' AND ') : '').(isset($this->_having) ? $this->_having.' ' : '').'
			ORDER BY '.(($orderBy == $this->identifier) ? 'a.' : '').'`'.pSQL($orderBy).'` '.pSQL($orderWay).
            ($this->_tmpTableFilter ? ') tmpTable WHERE 1'.$this->_tmpTableFilter : '').'
			LIMIT '.(int) $start.','.(int) $limit;
        $this->_list = Db::getInstance()->executeS($sql);
        $this->_listTotal = Db::getInstance()->getValue('SELECT FOUND_ROWS() as `'._DB_PREFIX_.$this->table.'`');
    }

    /**
     * Display list
     *
     * @deprecated 1.0.0
     */
    public function displayList()
    {
        $this->displayTop();

        if ($this->edit && (!isset($this->noAdd) || !$this->noAdd)) {
            $this->displayAddButton();
        }

        /* Append when we get a syntax error in SQL query */
        if ($this->_list === false) {
            $this->displayWarning($this->l('Bad SQL query'));

            return false;
        }

        /* Display list header (filtering, pagination and column names) */
        $this->displayListHeader();
        if (!count($this->_list)) {
            echo '<tr><td class="center" colspan="'.(count($this->fieldsDisplay) + 2).'">'.$this->l('No items found').'</td></tr>';
        }

        /* Show the content of the table */
        $this->displayListContent();

        /* Close list table and submit button */
        $this->displayListFooter();
    }

    public function displayTop()
    {
    }

    protected function displayAddButton()
    {
        echo '<br /><a href="'.static::$currentIndex.'&add'.$this->table.'&token='.$this->token.'"><img src="../img/admin/add.gif" border="0" /> '.$this->l('Add new').'</a><br /><br />';
    }

    /**
     * Display a warning message
     *
     * @param string $warn Warning message to display
     *
     * @deprecated 1.0.0
     */
    public function displayWarning($warn)
    {
        $strOutput = '';
        if (!empty($warn)) {
            $strOutput .= '<script type="text/javascript">
					$(document).ready(function() {
						$(\'#linkSeeMore\').unbind(\'click\').click(function(){
							$(\'#seeMore\').show(\'slow\');
							$(this).hide();
							$(\'#linkHide\').show();
							return false;
						});
						$(\'#linkHide\').unbind(\'click\').click(function(){
							$(\'#seeMore\').hide(\'slow\');
							$(this).hide();
							$(\'#linkSeeMore\').show();
							return false;
						});
						$(\'#hideWarn\').unbind(\'click\').click(function(){
							$(\'.warn\').hide(\'slow\', function (){
								$(\'.warn\').remove();
							});
							return false;
						});
					});
				  </script>
			<div class="warn">';
            if (!is_array($warn)) {
                $strOutput .= '<img src="../img/admin/warn2.png" />'.$warn;
            } else {
                $strOutput .= '<span style="float:right"><a id="hideWarn" href=""><img alt="X" src="../img/admin/close.png" /></a></span><img src="../img/admin/warn2.png" />'.
                    (count($warn) > 1 ? sprintf($this->l('There are %s warnings'), count($warn)) : $this->l('There is 1 warning'))
                    .'<span style="margin-left:20px;" id="labelSeeMore">
				<a id="linkSeeMore" href="#" style="text-decoration:underline">'.$this->l('Click here to see more').'</a>
				<a id="linkHide" href="#" style="text-decoration:underline;display:none">'.$this->l('Hide warning').'</a></span><ul style="display:none;" id="seeMore">';
                foreach ($warn as $val) {
                    $strOutput .= '<li>'.$val.'</li>';
                }
                $strOutput .= '</ul>';
            }
            $strOutput .= '</div>';
        }
        echo $strOutput;
    }

    /**
     * Display list header (filtering, pagination and column names)
     *
     * @deprecated 1.0.0
     */
    public function displayListHeader($token = null)
    {
        $isCms = false;
        if (preg_match('/cms/Ui', $this->identifier)) {
            $isCms = true;
        }
        $idCat = Tools::getValue('id_'.($isCms ? 'cms_' : '').'category');

        if (!isset($token) || empty($token)) {
            $token = $this->token;
        }

        /* Determine total page number */
        $totalPages = ceil($this->_listTotal / Tools::getValue('pagination', (isset($this->context->cookie->{$this->table.'_pagination'}) ? $this->context->cookie->{$this->table.'_pagination'} : $this->_pagination[0])));
        if (!$totalPages) {
            $totalPages = 1;
        }

        echo '<a name="'.$this->table.'">&nbsp;</a>';
        echo '<form method="post" action="'.static::$currentIndex;
        if (Tools::getIsset($this->identifier)) {
            echo '&'.$this->identifier.'='.(int) (Tools::getValue($this->identifier));
        }
        echo '&token='.$token;
        if (Tools::getIsset($this->table.'Orderby')) {
            echo '&'.$this->table.'Orderby='.urlencode($this->_orderBy).'&'.$this->table.'Orderway='.urlencode(strtolower($this->_orderWay));
        }
        echo '#'.$this->table.'" class="form">
		<input type="hidden" id="submitFilter'.$this->table.'" name="submitFilter'.$this->table.'" value="0">
		<table>
			<tr>
				<td style="vertical-align: bottom;">
					<span style="float: left;">';

        /* Determine current page number */
        $page = (int) (Tools::getValue('submitFilter'.$this->table));
        if (!$page) {
            $page = 1;
        }
        if ($page > 1) {
            echo '
						<input type="image" src="../img/admin/list-prev2.gif" onclick="getE(\'submitFilter'.$this->table.'\').value=1"/>
						&nbsp; <input type="image" src="../img/admin/list-prev.gif" onclick="getE(\'submitFilter'.$this->table.'\').value='.($page - 1).'"/> ';
        }
        echo $this->l('Page').' <b>'.$page.'</b> / '.$totalPages;
        if ($page < $totalPages) {
            echo '
						<input type="image" src="../img/admin/list-next.gif" onclick="getE(\'submitFilter'.$this->table.'\').value='.($page + 1).'"/>
						 &nbsp;<input type="image" src="../img/admin/list-next2.gif" onclick="getE(\'submitFilter'.$this->table.'\').value='.$totalPages.'"/>';
        }
        echo '			| '.$this->l('Display').'
						<select name="pagination">';
        /* Choose number of results per page */
        $selectedPagination = Tools::getValue('pagination', (isset($this->context->cookie->{$this->table.'_pagination'}) ? $this->context->cookie->{$this->table.'_pagination'} : null));
        foreach ($this->_pagination as $value) {
            echo '<option value="'.(int) ($value).'"'.($selectedPagination == $value ? ' selected="selected"' : (($selectedPagination == null && $value == $this->_pagination[1]) ? ' selected="selected2"' : '')).'>'.(int) ($value).'</option>';
        }
        echo '
						</select>
						/ '.(int) ($this->_listTotal).' '.$this->l('result(s)').'
					</span>
					<span style="float: right;">
						<input type="submit" name="submitReset'.$this->table.'" value="'.$this->l('Reset').'" class="button" />
						<input type="submit" id="submitFilterButton_'.$this->table.'" name="submitFilter" value="'.$this->l('Filter').'" class="button" />
					</span>
					<span class="clear"></span>
				</td>
			</tr>
			<tr>
				<td>';

        /* Display column names and arrows for ordering (ASC, DESC) */
        if (array_key_exists($this->identifier, $this->identifiersDnd) && $this->_orderBy == 'position') {
            echo '
			<script type="text/javascript" src="../js/jquery/jquery.tablednd_0_5.js"></script>
			<script type="text/javascript">
				var token = \''.($token != null ? $token : $this->token).'\';
				var come_from = \''.$this->table.'\';
				var alternate = \''.($this->_orderWay == 'DESC' ? '1' : '0').'\';
			</script>
			<script type="text/javascript" src="../js/admin/dnd.js"></script>
			';
        }
        echo '<table'.(array_key_exists($this->identifier, $this->identifiersDnd) ? ' id="'.(((int) (Tools::getValue($this->identifiersDnd[$this->identifier], 1))) ? mb_substr($this->identifier, 3, mb_strlen($this->identifier)) : '').'"' : '').' class="table'.((array_key_exists($this->identifier, $this->identifiersDnd) && ($this->_orderBy != 'position' && $this->_orderWay != 'DESC')) ? ' tableDnD' : '').'" cellpadding="0" cellspacing="0">
			<thead>
				<tr class="nodrag nodrop">
					<th>';
        if ($this->delete) {
            echo '		<input type="checkbox" name="checkme" class="noborder" onclick="checkDelBoxes(this.form, \''.$this->table.'Box[]\', this.checked)" />';
        }
        echo '		</th>';
        foreach ($this->fieldsDisplay as $key => $params) {
            echo '	<th '.(isset($params['widthColumn']) ? 'style="width: '.$params['widthColumn'].'px"' : '').'>'.$params['title'];
            if (!isset($params['orderby']) || $params['orderby']) {
                // Cleaning links
                if (Tools::getValue($this->table.'Orderby') && Tools::getValue($this->table.'Orderway')) {
                    static::$currentIndex = preg_replace('/&'.$this->table.'Orderby=([a-z _]*)&'.$this->table.'Orderway=([a-z]*)/i', '', static::$currentIndex);
                }
                echo '	<br />
						<a href="'.static::$currentIndex.'&'.$this->identifier.'='.$idCat.'&'.$this->table.'Orderby='.urlencode($key).'&'.$this->table.'Orderway=desc&token='.$token.'"><img border="0" src="../img/admin/down'.((isset($this->_orderBy) && ($key == $this->_orderBy) && ($this->_orderWay == 'DESC')) ? '_d' : '').'.gif" /></a>
						<a href="'.static::$currentIndex.'&'.$this->identifier.'='.$idCat.'&'.$this->table.'Orderby='.urlencode($key).'&'.$this->table.'Orderway=asc&token='.$token.'"><img border="0" src="../img/admin/up'.((isset($this->_orderBy) && ($key == $this->_orderBy) && ($this->_orderWay == 'ASC')) ? '_d' : '').'.gif" /></a>';
            }
            echo '	</th>';
        }

        if ($this->shopLinkType) {
            echo '<th style="width: 80px">'.$this->l(($this->shopLinkType == 'shop') ? 'Shop' : 'Shop group').'</th>';
        }

        /* Check if object can be modified, deleted or detailed */
        if ($this->edit || $this->delete || ($this->view && $this->view !== 'noActionColumn')) {
            echo '	<th style="width: 52px">'.$this->l('Actions').'</th>';
        }
        echo '	</tr>
				<tr class="nodrag nodrop" style="height: 35px;">
					<td class="center">';
        if ($this->delete) {
            echo '		--';
        }
        echo '		</td>';

        /* Javascript hack in order to catch ENTER keypress event */
        $keyPress = 'onkeypress="formSubmit(event, \'submitFilterButton_'.$this->table.'\');"';

        /* Filters (input, select, date or bool) */
        foreach ($this->fieldsDisplay as $key => $params) {
            $width = (isset($params['width']) ? ' style="width: '.(int) ($params['width']).'px;"' : '');
            echo '<td'.(isset($params['align']) ? ' class="'.$params['align'].'"' : '').'>';
            if (!isset($params['type'])) {
                $params['type'] = 'text';
            }

            $value = Tools::getValue($this->table.'Filter_'.(array_key_exists('filter_key', $params) ? $params['filter_key'] : $key));
            if (isset($params['search']) && !$params['search']) {
                echo '--</td>';
                continue;
            }
            switch ($params['type']) {
                case 'bool':
                    echo '
					<select name="'.$this->table.'Filter_'.$key.'">
						<option value="">-</option>
						<option value="1"'.($value == 1 ? ' selected="selected"' : '').'>'.$this->l('Yes').'</option>
						<option value="0"'.(($value == 0 && $value != '') ? ' selected="selected"' : '').'>'.$this->l('No').'</option>
					</select>';
                    break;

                case 'date':
                case 'datetime':
                    if (!Validate::isCleanHtml($value[0]) || !Validate::isCleanHtml($value[1])) {
                        $value = '';
                    }
                    $name = $this->table.'Filter_'.(isset($params['filter_key']) ? $params['filter_key'] : $key);
                    $nameId = str_replace('!', '__', $name);
                    includeDatepicker([$nameId.'_0', $nameId.'_1']);
                    echo $this->l('From').' <input type="text" id="'.$nameId.'_0" name="'.$name.'[0]" value="'.(isset($value[0]) ? $value[0] : '').'"'.$width.' '.$keyPress.' /><br />
					'.$this->l('To').' <input type="text" id="'.$nameId.'_1" name="'.$name.'[1]" value="'.(isset($value[1]) ? $value[1] : '').'"'.$width.' '.$keyPress.' />';
                    break;

                case 'select':

                    if (isset($params['filter_key'])) {
                        echo '<select onchange="$(\'#submitFilter'.$this->table.'\').focus();$(\'#submitFilter'.$this->table.'\').click();" name="'.$this->table.'Filter_'.$params['filter_key'].'" '.(isset($params['width']) ? 'style="width: '.$params['width'].'px"' : '').'>
								<option value=""'.(($value == 0 && $value != '') ? ' selected="selected"' : '').'>-</option>';
                        if (isset($params['select']) && is_array($params['select'])) {
                            foreach ($params['select'] as $optionValue => $optionDisplay) {
                                echo '<option value="'.$optionValue.'"'.((isset($_POST[$this->table.'Filter_'.$params['filter_key']]) && Tools::getValue($this->table.'Filter_'.$params['filter_key']) == $optionValue && Tools::getValue($this->table.'Filter_'.$params['filter_key']) != '') ? ' selected="selected"' : '').'>'.$optionDisplay.'</option>';
                            }
                        }
                        echo '</select>';
                        break;
                    }

                case 'text':
                default:
                    if (!Validate::isCleanHtml($value)) {
                        $value = '';
                    }
                    echo '<input type="text" name="'.$this->table.'Filter_'.(isset($params['filter_key']) ? $params['filter_key'] : $key).'" value="'.htmlentities($value, ENT_COMPAT, 'UTF-8').'"'.$width.' '.$keyPress.' />';
            }
            echo '</td>';
        }

        if ($this->shopLinkType) {
            echo '<td>--</td>';
        }

        if ($this->edit || $this->delete || ($this->view && $this->view !== 'noActionColumn')) {
            echo '<td class="center">--</td>';
        }

        echo '</tr>
			</thead>';
    }

    /**
     * @param string|null $token
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    public function displayListContent($token = null)
    {
        /* Display results in a table
         *
         * align  : determine value alignment
         * prefix : displayed before value
         * suffix : displayed after value
         * image  : object image
         * icon   : icon determined by values
         * active : allow to toggle status
         */
        $idCategory = 1; // default categ

        $irow = 0;
        if ($this->_list && isset($this->fieldsDisplay['position'])) {
            $positions = array_map(function ($elem) {
                return (int) $elem['position'];
            }, $this->_list);
            sort($positions);
        }
        if ($this->_list) {
            $isCms = false;
            if (preg_match('/cms/Ui', $this->identifier)) {
                $isCms = true;
            }
            $keyToGet = 'id_'.($isCms ? 'cms_' : '').'category'.(in_array($this->identifier, ['id_category', 'id_cms_category']) ? '_parent' : '');
            foreach ($this->_list as $tr) {
                $id = $tr[$this->identifier];
                echo '<tr'.(array_key_exists($this->identifier, $this->identifiersDnd) ? ' id="tr_'.(($idCategory = (int) (Tools::getValue('id_'.($isCms ? 'cms_' : '').'category', '1'))) ? $idCategory : '').'_'.$id.'_'.$tr['position'].'"' : '').($irow++ % 2 ? ' class="alt_row"' : '').' '.((isset($tr['color']) && $this->colorOnBackground) ? 'style="background-color: '.$tr['color'].'"' : '').'>
							<td class="center">';
                if ($this->delete && (!isset($this->_listSkipDelete) || !in_array($id, $this->_listSkipDelete))) {
                    echo '<input type="checkbox" name="'.$this->table.'Box[]" value="'.$id.'" class="noborder" />';
                }
                echo '</td>';
                foreach ($this->fieldsDisplay as $key => $params) {
                    $tmp = explode('!', $key);
                    $key = isset($tmp[1]) ? $tmp[1] : $tmp[0];
                    echo '
					<td '.(isset($params['position']) ? ' id="td_'.(isset($idCategory) && $idCategory ? $idCategory : 0).'_'.$id.'"' : '').' class="'.((!isset($this->noLink) || !$this->noLink) ? 'pointer' : '').((isset($params['position']) && $this->_orderBy == 'position') ? ' dragHandle' : '').(isset($params['align']) ? ' '.$params['align'] : '').'" ';
                    if (!isset($params['position']) && (!isset($this->noLink) || !$this->noLink)) {
                        echo ' onclick="document.location = \''.static::$currentIndex.'&'.$this->identifier.'='.$id.($this->view ? '&view' : '&update').$this->table.'&token='.($token != null ? $token : $this->token).'\'">'.(isset($params['prefix']) ? $params['prefix'] : '');
                    } else {
                        echo '>';
                    }
                    if (isset($params['active']) && isset($tr[$key])) {
                        $this->_displayEnableLink($token, $id, $tr[$key], $params['active'], Tools::getValue('id_category'), Tools::getValue('id_product'));
                    } elseif (isset($params['activeVisu']) && isset($tr[$key])) {
                        echo '<img src="../img/admin/'.($tr[$key] ? 'enabled.gif' : 'disabled.gif').'"
						alt="'.($tr[$key] ? $this->l('Enabled') : $this->l('Disabled')).'" title="'.($tr[$key] ? $this->l('Enabled') : $this->l('Disabled')).'" />';
                    } elseif (isset($params['position'])) {
                        if ($this->_orderBy == 'position' && $this->_orderWay != 'DESC') {
                            echo '<a'.(!($tr[$key] != $positions[count($positions) - 1]) ? ' style="display: none;"' : '').' href="'.static::$currentIndex.
                                '&'.$keyToGet.'='.(int) ($idCategory).'&'.$this->identifiersDnd[$this->identifier].'='.$id.'
									&way=1&position='.(int) ($tr['position'] + 1).'&token='.($token != null ? $token : $this->token).'">
									<img src="../img/admin/'.($this->_orderWay == 'ASC' ? 'down' : 'up').'.gif"
									alt="'.$this->l('Down').'" title="'.$this->l('Down').'" /></a>';

                            echo '<a'.(!($tr[$key] != $positions[0]) ? ' style="display: none;"' : '').' href="'.static::$currentIndex.
                                '&'.$keyToGet.'='.(int) ($idCategory).'&'.$this->identifiersDnd[$this->identifier].'='.$id.'
									&way=0&position='.(int) ($tr['position'] - 1).'&token='.($token != null ? $token : $this->token).'">
									<img src="../img/admin/'.($this->_orderWay == 'ASC' ? 'up' : 'down').'.gif"
									alt="'.$this->l('Up').'" title="'.$this->l('Up').'" /></a>';
                        } else {
                            echo (int) ($tr[$key] + 1);
                        }
                    } elseif (isset($params['image'])) {
                        // item_id is the product id in a product image context, else it is the image id.
                        $itemId = isset($params['image_id']) ? $tr[$params['image_id']] : $id;
                        // If it's a product image
                        if (isset($tr['id_image'])) {
                            $image = new Image((int) $tr['id_image']);
                            $pathToImage = _PS_IMG_DIR_.$params['image'].'/'.$image->getExistingImgPath().'.'.$this->imageType;
                        } else {
                            $pathToImage = _PS_IMG_DIR_.$params['image'].'/'.$itemId.(isset($tr['id_image']) ? '-'.(int) ($tr['id_image']) : '').'.'.$this->imageType;
                        }

                        echo ImageManager::thumbnail($pathToImage, $this->table.'_mini_'.$itemId.'.'.$this->imageType, 45, $this->imageType);
                    } elseif (isset($params['icon']) && (isset($params['icon'][$tr[$key]]) || isset($params['icon']['default']))) {
                        echo '<img src="../img/admin/'.(isset($params['icon'][$tr[$key]]) ? $params['icon'][$tr[$key]] : $params['icon']['default'].'" alt="'.$tr[$key]).'" title="'.$tr[$key].'" />';
                    } elseif (isset($params['price'])) {
                        echo Tools::displayPrice($tr[$key], (isset($params['currency']) ? Currency::getCurrencyInstance($tr['id_currency']) : $this->context->currency), false);
                    } elseif (isset($params['float'])) {
                        echo rtrim(rtrim($tr[$key], '0'), '.');
                    } elseif (isset($params['type']) && $params['type'] == 'date') {
                        echo Tools::displayDate($tr[$key]);
                    } elseif (isset($params['type']) && $params['type'] == 'datetime') {
                        echo Tools::displayDate($tr[$key], null, true);
                    } elseif (isset($tr[$key])) {
                        if ($key == 'price') {
                            $currency = $this->context->currency;
                            if (isset($params['currency'])) {
                                $currency = Currency::getCurrencyInstance(
                                    $tr['id_currency']
                                );
                            }
                            if ($currency->decimals) {
                                $decimals = Configuration::get('PS_PRICE_DISPLAY_PRECISION');
                            } else {
                                $decimals = 0;
                            }
                            $echo = Tools::ps_round($tr[$key], $decimals);
                        } elseif (isset($params['maxlength']) && mb_strlen($tr[$key]) > $params['maxlength']) {
                            $echo = '<span title="'.$tr[$key].'">'.mb_substr($tr[$key], 0, $params['maxlength']).'...</span>';
                        } else {
                            $echo = $tr[$key];
                        }

                        echo isset($params['callback']) ? call_user_func_array([(isset($params['callback_object'])) ? $params['callback_object'] : $this->className, $params['callback']], [$echo, $tr]) : $echo;
                    } else {
                        echo '--';
                    }

                    echo (isset($params['suffix']) ? $params['suffix'] : '').
                        '</td>';
                }

                if ($this->shopLinkType) {
                    $name = (mb_strlen($tr['shop_name']) > 15) ? mb_substr($tr['shop_name'], 0, 15).'...' : $tr['shop_name'];
                    echo '<td class="center" '.(($name != $tr['shop_name']) ? 'title="'.$tr['shop_name'].'"' : '').'>'.$name.'</td>';
                }

                if ($this->edit || $this->delete || ($this->view && $this->view !== 'noActionColumn')) {
                    echo '<td class="center" style="white-space: nowrap;">';
                    if ($this->view) {
                        $this->_displayViewLink($token, $id);
                    }
                    if ($this->edit) {
                        $this->_displayEditLink($token, $id);
                    }
                    if ($this->delete && (!isset($this->_listSkipDelete) || !in_array($id, $this->_listSkipDelete))) {
                        $this->_displayDeleteLink($token, $id);
                    }
                    if ($this->duplicate) {
                        $this->_displayDuplicate($token, $id);
                    }
                    echo '</td>';
                }
                echo '</tr>';
            }
        }
    }

    /**
     * @param      $token
     * @param      $id
     * @param      $value
     * @param      $active
     * @param null $idCategory
     * @param null $idProduct
     *
     * @deprecated 1.0.0
     */
    protected function _displayEnableLink($token, $id, $value, $active, $idCategory = null, $idProduct = null)
    {
        $href = Tools::safeOutput(
            static::$currentIndex.'&'.$this->identifier.'='.(int) $id.'&'.$active.$this->table.
            ((int) $idCategory && (int) $idProduct ? '&id_category='.(int) $idCategory : '').'&token='.($token != null ? $token : $this->token)
        );

        echo '<a href="'.$href.'">
	        <img src="../img/admin/'.((bool) $value ? 'enabled.gif' : 'disabled.gif').'"
	        alt="'.((bool) $value ? $this->l('Enabled') : $this->l('Disabled')).'" title="'.((bool) $value ? $this->l('Enabled') : $this->l('Disabled')).'" /></a>';
    }

    /**
     * @param null $token
     * @param      $id
     *
     * @deprecated 1.0.0
     */
    protected function _displayViewLink($token = null, $id)
    {
        $_cacheLang['View'] = $this->l('View');
        $href = Tools::safeOutput(static::$currentIndex.'&'.$this->identifier.'='.(int) $id.'&view'.$this->table.'&token='.($token != null ? $token : $this->token));

        echo '<a href="'.$href.'">
			<img src="../img/admin/details.gif" alt="'.$_cacheLang['View'].'" title="'.$_cacheLang['View'].'" /></a>';
    }

    /**
     * @param null $token
     * @param      $id
     *
     * @deprecated 1.0.0
     */
    protected function _displayEditLink($token = null, $id)
    {
        $_cacheLang['Edit'] = $this->l('Edit');
        $href = Tools::safeOutput(static::$currentIndex.'&'.$this->identifier.'='.(int) $id.'&update'.$this->table.'&token='.($token != null ? $token : $this->token));

        echo '<a href="'.$href.'">
    		<img src="../img/admin/edit.gif" alt="" title="'.$_cacheLang['Edit'].'" /></a>';
    }

    /**
     * @param null $token
     * @param      $id
     *
     * @deprecated 1.0.0
     */
    protected function _displayDeleteLink($token = null, $id)
    {
        $_cacheLang['Delete'] = $this->l('Delete');
        $_cacheLang['DeleteItem'] = $this->l('Delete item #', __CLASS__, true, false);
        $href = Tools::safeOutput(static::$currentIndex.'&'.$this->identifier.'='.(int) $id.'&delete'.$this->table.'&token='.($token != null ? $token : $this->token));

        echo '<a href="'.$href.'" onclick="return confirm(\''.$_cacheLang['DeleteItem'].(int) $id.' ?'.
            (!is_null($this->specificConfirmDelete) ? '\r'.$this->specificConfirmDelete : '').'\');">
			<img src="../img/admin/delete.gif" alt="'.$_cacheLang['Delete'].'" title="'.$_cacheLang['Delete'].'" /></a>';
    }

    /**
     * @param null $token
     * @param      $id
     *
     * @deprecated 1.0.0
     */
    protected function _displayDuplicate($token = null, $id)
    {
        $_cacheLang['Duplicate'] = $this->l('Duplicate');
        $_cacheLang['Copy images too?'] = $this->l('This will copy the images too. If you wish to proceed, click "OK". If not, click "Cancel".', __CLASS__, true, false);
        $duplicate = Tools::safeOutput(static::$currentIndex.'&'.$this->identifier.'='.$id.'&duplicate'.$this->table.'&token='.($token != null ? $token : $this->token));

        echo '<a class="pointer" onclick="if (confirm(\''.$_cacheLang['Copy images too?'].'\')) document.location = \''.$duplicate.'\'; else document.location = \''.$duplicate.'&noimage=1\';">
    		<img src="../img/admin/duplicate.png" alt="'.$_cacheLang['Duplicate'].'" title="'.$_cacheLang['Duplicate'].'" /></a>';
    }

    /**
     * Close list table and submit button
     *
     * @deprecated 1.0.0
     */
    public function displayListFooter($token = null)
    {
        echo '</table>';
        if ($this->delete) {
            echo '<p><input type="submit" class="button" name="submitDel'.$this->table.'" value="'.$this->l('Delete selection').'" onclick="return confirm(\''.$this->l('Delete selected items?', __CLASS__, true, false).'\');" /></p>';
        }
        echo '
				</td>
			</tr>
		</table>
		<input type="hidden" name="token" value="'.($token ? $token : $this->token).'" />
		</form>';
        if (isset($this->_includeTab) && count($this->_includeTab)) {
            echo '<br /><br />';
        }
    }

    /**
     * Options lists
     *
     * @deprecated 1.0.0
     */
    public function displayOptionsList()
    {
        $tab = Tab::getTab($this->context->language->id, $this->id);

        // Retrocompatibility < 1.5.0
        if (!$this->optionsList && $this->_fieldsOptions) {
            $this->optionsList = [
                'options' => [
                    'title'  => ($this->optionTitle) ? $this->optionTitle : $this->l('Options'),
                    'fields' => $this->_fieldsOptions,
                ],
            ];
        }

        if (!$this->optionsList) {
            return;
        }

        echo '<br />';
        echo '<script type="text/javascript">
			id_language = Number('.$this->context->language->id.');
		</script>';

        $action = Tools::safeOutput(static::$currentIndex.'&submitOptions'.$this->table.'=1&token='.$this->token);

        echo '<form action="'.$action.'" method="post" enctype="multipart/form-data">';
        foreach ($this->optionsList as $category => $categoryData) {
            $required = false;
            $this->displayTopOptionCategory($category, $categoryData);
            echo '<fieldset>';

            // Options category title
            $legend = '<img src="'.(!empty($tab['module']) && file_exists($_SERVER['DOCUMENT_ROOT']._MODULE_DIR_.$tab['module'].'/'.$tab['class_name'].'.gif') ? _MODULE_DIR_.$tab['module'].'/' : '../img/t/').$tab['class_name'].'.gif" /> ';
            $legend .= ((isset($categoryData['title'])) ? $categoryData['title'] : $this->l('Options'));
            echo '<legend>'.$legend.'</legend>';

            // Category fields
            if (!isset($categoryData['fields'])) {
                continue;
            }

            // Category description
            if (isset($categoryData['description']) && $categoryData['description']) {
                echo '<p class="optionsDescription">'.$categoryData['description'].'</p>';
            }

            foreach ($categoryData['fields'] as $key => $field) {
                // Field value
                $value = Tools::getValue($key, Configuration::get($key));
                if (!Validate::isCleanHtml($value)) {
                    $value = Configuration::get($key);
                }

                if (isset($field['defaultValue']) && !$value) {
                    $value = $field['defaultValue'];
                }

                // Check if var is invisible (can't edit it in current shop context), or disable (use default value for multishop)
                $isDisabled = $isInvisible = false;
                if (Shop::isFeatureActive()) {
                    if (isset($field['visibility']) && $field['visibility'] > Shop::getContext()) {
                        $isDisabled = true;
                        $isInvisible = true;
                    } elseif (Shop::getContext() != Shop::CONTEXT_ALL && !Configuration::isOverridenByCurrentContext($key)) {
                        $isDisabled = true;
                    }
                }

                // Display title
                echo '<div style="clear: both; padding-top:15px;" id="conf_id_'.$key.'" '.(($isInvisible) ? 'class="isInvisible"' : '').'>';
                if ($field['title']) {
                    echo '<label class="conf_title">';

                    // Is this field required ?
                    if (isset($field['required']) && $field['required']) {
                        $required = true;
                        echo '<sup>*</sup> ';
                    }
                    echo $field['title'].'</label>';
                }

                echo '<div class="margin-form" style="padding-top:5px;">';

                // Display option inputs
                $method = 'displayOptionType'.Tools::toCamelCase($field['type'], true);
                if (!method_exists($this, $method)) {
                    $this->displayOptionTypeText($key, $field, $value);
                }//default behavior
                else {
                    $this->$method($key, $field, $value);
                }

                // Multishop default value
                if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_ALL && !$isInvisible) {
                    echo '<div class="preference_default_multishop">
							<label>
								<input type="checkbox" name="multishopOverrideOption['.$key.']" value="1" '.(($isDisabled) ? 'checked="checked"' : '').' onclick="checkMultishopDefaultValue(this, \''.$key.'\')" /> '.$this->l('Use default value').'
							</label>
						</div>';
                }

                // Field description
                //echo (isset($field['desc']) ? '<p class="preference_description">'.((isset($field['thumb']) AND $field['thumb'] AND $field['thumb']['pos'] == 'after') ? '<img src="'.$field['thumb']['file'].'" alt="'.$field['title'].'" title="'.$field['title'].'" style="float:left;" />' : '' ).$field['desc'].'</p>' : '');
                echo(isset($field['desc']) ? '<p class="preference_description">'.$field['desc'].'</p>' : '');

                // Is this field invisible in current shop context ?
                echo ($isInvisible) ? '<p class="multishop_warning">'.$this->l('You cannot change the value of this configuration field in this shop context').'</p>' : '';

                echo '</div></div>';
            }

            echo '<div align="center" style="margin-top: 20px;">';
            echo '<input type="submit" value="'.$this->l('   Save   ').'" name="submit'.ucfirst($category).$this->table.'" class="button" />';
            echo '</div>';
            if ($required) {
                echo '<div class="small"><sup>*</sup> '.$this->l('Required field').'</div>';
            }

            echo '</fieldset><br />';
            $this->displayBottomOptionCategory($category, $categoryData);
        }
        echo '</form>';
    }

    /**
     * Can be overriden
     *
     * @deprecated 1.0.0
     */
    public function displayTopOptionCategory($category, $data)
    {
    }

    /**
     * Type = text
     *
     * @deprecated 1.0.0
     */
    public function displayOptionTypeText($key, $field, $value)
    {
        echo '<input type="'.$field['type'].'"'.(isset($field['id']) ? ' id="'.$field['id'].'"' : '').' size="'.(isset($field['size']) ? (int) $field['size'] : 5).'" name="'.$key.'" value="'.htmlentities($value, ENT_COMPAT, 'UTF-8').'" />'.(isset($field['next']) ? '&nbsp;'.(string) $field['next'] : '');
    }

    /**
     * Can be overriden
     *
     * @deprecated 1.0.0
     */
    public function displayBottomOptionCategory($category, $data)
    {
    }

    /**
     * @deprecated 1.0.0
     * @throws PrestaShopDatabaseException
     */
    public function displayRequiredFields()
    {
        if (!$this->tabAccess['add'] || !$this->tabAccess['delete'] === '1' || !$this->requiredDatabase) {
            return;
        }
        $rules = call_user_func_array([$this->className, 'getValidationRules'], [$this->className]);
        $requiredClassFields = [$this->identifier];
        foreach ($rules['required'] as $required) {
            $requiredClassFields[] = $required;
        }

        echo '<br />
		<p><a href="#" onclick="if ($(\'.requiredFieldsParameters:visible\').length == 0) $(\'.requiredFieldsParameters\').slideDown(\'slow\'); else $(\'.requiredFieldsParameters\').slideUp(\'slow\'); return false;"><img src="../img/admin/duplicate.gif" alt="" /> '.$this->l('Set required fields for this section').'</a></p>
		<fieldset style="display:none" class="width1 requiredFieldsParameters">
		<legend>'.$this->l('Required Fields').'</legend>
		<form name="updateFields" action="'.static::$currentIndex.'&submitFields'.$this->table.'=1&token='.$this->token.'" method="post">
		<p><b>'.$this->l('Select the fields you would like to be required for this section.').'<br />
		<table cellspacing="0" cellpadding="0" class="table width1 clear">
		<tr>
			<th><input type="checkbox" onclick="checkDelBoxes(this.form, \'fieldsBox[]\', this.checked)" class="noborder" name="checkme"></th>
			<th>'.$this->l('Field Name').'</th>
		</tr>';

        /** @var ObjectModel $object */
        $object = new $this->className();
        $res = $object->getFieldsRequiredDatabase();

        $requiredFields = [];
        foreach ($res as $row) {
            $requiredFields[(int) $row['id_required_field']] = $row['field_name'];
        }

        $tableFields = Db::getInstance()->executeS('SHOW COLUMNS FROM '.pSQL(_DB_PREFIX_.$this->table));
        $irow = 0;
        foreach ($tableFields as $field) {
            if (in_array($field['Field'], $requiredClassFields)) {
                continue;
            }
            echo '<tr class="'.($irow++ % 2 ? 'alt_row' : '').'">
						<td class="noborder"><input type="checkbox" name="fieldsBox[]" value="'.$field['Field'].'" '.(in_array($field['Field'], $requiredFields) ? 'checked="checked"' : '').' /></td>
						<td>'.$field['Field'].'</td>
					</tr>';
        }
        echo '</table><br />
				<center><input style="margin-left:15px;" class="button" type="submit" value="'.$this->l('   Save   ').'" name="submitFields" /></center>
		</fieldset>';
    }

    /**
     * Overload this method for custom checking
     *
     * @param int $id Object id used for deleting images
     *
     * @deprecated 1.0.0
     * @throws PrestaShopDatabaseException
     */
    public function deleteImage($id)
    {
        Tools::displayAsDeprecated();
        $dir = null;
        /* Deleting object images and thumbnails (cache) */
        if (array_key_exists('dir', $this->fieldImageSettings)) {
            $dir = $this->fieldImageSettings['dir'].'/';
            if (file_exists(_PS_IMG_DIR_.$dir.$id.'.'.$this->imageType) && !unlink(_PS_IMG_DIR_.$dir.$id.'.'.$this->imageType)) {
                return false;
            }
        }
        if (file_exists(_PS_TMP_IMG_DIR_.$this->table.'_'.$id.'.'.$this->imageType) && !unlink(_PS_TMP_IMG_DIR_.$this->table.'_'.$id.'.'.$this->imageType)) {
            return false;
        }
        if (file_exists(_PS_TMP_IMG_DIR_.$this->table.'_mini_'.$id.'.'.$this->imageType) && !unlink(_PS_TMP_IMG_DIR_.$this->table.'_mini_'.$id.'.'.$this->imageType)) {
            return false;
        }
        $types = ImageType::getImagesTypes();
        foreach ($types as $imageType) {
            if (file_exists(_PS_IMG_DIR_.$dir.$id.'-'.stripslashes($imageType['name']).'.'.$this->imageType) && !unlink(_PS_IMG_DIR_.$dir.$id.'-'.stripslashes($imageType['name']).'.'.$this->imageType)) {
                return false;
            }
        }

        return true;
    }

    /**
     * ajaxPreProcess is a method called in ajax-tab.php before displayConf().
     *
     * @return void
     *
     * @deprecated 1.0.0
     */
    public function ajaxPreProcess()
    {
    }

    /**
     * ajaxProcess is the default handle method for request with ajax-tab.php
     *
     * @return void
     *
     * @deprecated 1.0.0
     */
    public function ajaxProcess()
    {
    }

    /**
     * Manage page processing
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function postProcess()
    {
        if (!isset($this->table)) {
            return false;
        }

        // set token
        $token = Tools::getValue('token') ? Tools::getValue('token') : $this->token;

        // Sub included tab postProcessing
        $this->includeSubTab('postProcess', ['status', 'submitAdd1', 'submitDel', 'delete', 'submitFilter', 'submitReset']);

        /* Delete object image */
        if (isset($_GET['deleteImage'])) {
            if (Validate::isLoadedObject($object = $this->loadObject())) {
                /** @var ObjectModel $object */
                if (($object->deleteImage())) {
                    Tools::redirectAdmin(static::$currentIndex.'&add'.$this->table.'&'.$this->identifier.'='.Tools::getValue($this->identifier).'&conf=7&token='.$token);
                }
            }
            $this->_errors[] = Tools::displayError('An error occurred during image deletion (cannot load object).');
        } /* Delete object */
        elseif (isset($_GET['delete'.$this->table])) {
            if ($this->tabAccess['delete'] === '1') {
                if (Validate::isLoadedObject($object = $this->loadObject()) && isset($this->fieldImageSettings)) {
                    /** @var ObjectModel $object */
                    // check if request at least one object with noZeroObject
                    if (isset($object->noZeroObject) && count(call_user_func([$this->className, $object->noZeroObject])) <= 1) {
                        $this->_errors[] = Tools::displayError('You need at least one object.').' <b>'.$this->table.'</b><br />'.Tools::displayError('You cannot delete all of the items.');
                    } else {
                        if ($this->deleted) {
                            $object->deleteImage();
                            $object->deleted = 1;
                            if (method_exists($object, 'cleanPositions')) {
                                $object->cleanPositions();
                            }
                            if ($object->update()) {
                                Tools::redirectAdmin(static::$currentIndex.'&conf=1&token='.$token);
                            }
                        } elseif ($object->delete()) {
                            if (method_exists($object, 'cleanPositions')) {
                                $object->cleanPositions();
                            }
                            Tools::redirectAdmin(static::$currentIndex.'&conf=1&token='.$token);
                        }
                        $this->_errors[] = Tools::displayError('An error occurred during deletion.');
                    }
                } else {
                    $this->_errors[] = Tools::displayError('An error occurred while deleting object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
                }
            } else {
                $this->_errors[] = Tools::displayError('You do not have permission to delete here.');
            }
        } /* Change object statuts (active, inactive) */
        elseif ((isset($_GET['status'.$this->table]) || isset($_GET['status'])) && Tools::getValue($this->identifier)) {
            if ($this->tabAccess['edit'] === '1') {
                if (Validate::isLoadedObject($object = $this->loadObject())) {
                    /** @var ObjectModel $object */
                    if ($object->toggleStatus()) {
                        Tools::redirectAdmin(static::$currentIndex.'&conf=5'.((($idCategory = (int) (Tools::getValue('id_category'))) && Tools::getValue('id_product')) ? '&id_category='.$idCategory : '').'&token='.$token);
                    } else {
                        $this->_errors[] = Tools::displayError('An error occurred while updating status.');
                    }
                } else {
                    $this->_errors[] = Tools::displayError('An error occurred while updating status for object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
                }
            } else {
                $this->_errors[] = Tools::displayError('You do not have permission to edit here.');
            }
        } /* Move an object */
        elseif (isset($_GET['position'])) {
            /** @var ObjectModel $object */
            if ($this->tabAccess['edit'] !== '1') {
                $this->_errors[] = Tools::displayError('You do not have permission to edit here.');
            } elseif (!Validate::isLoadedObject($object = $this->loadObject())) {
                $this->_errors[] = Tools::displayError('An error occurred while updating status for object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
            } elseif (!$object->updatePosition((int) (Tools::getValue('way')), (int) (Tools::getValue('position')))) {
                $this->_errors[] = Tools::displayError('Failed to update the position.');
            } else {
                Tools::redirectAdmin(static::$currentIndex.'&'.$this->table.'Orderby=position&'.$this->table.'Orderway=asc&conf=5'.(($id_identifier = (int) (Tools::getValue($this->identifier))) ? ('&'.$this->identifier.'='.$id_identifier) : '').'&token='.$token);
            }
        } /* Delete multiple objects */
        elseif (Tools::getValue('submitDel'.$this->table)) {
            if ($this->tabAccess['delete'] === '1') {
                if (isset($_POST[$this->table.'Box'])) {
                    /** @var ObjectModel $object */
                    $object = new $this->className();
                    if (isset($object->noZeroObject) &&
                        // Check if all object will be deleted
                        (count(call_user_func([$this->className, $object->noZeroObject])) <= 1 || count($_POST[$this->table.'Box']) == count(call_user_func([$this->className, $object->noZeroObject])))
                    ) {
                        $this->_errors[] = Tools::displayError('You need at least one object.').' <b>'.$this->table.'</b><br />'.Tools::displayError('You cannot delete all of the items.');
                    } else {
                        $result = true;
                        if ($this->deleted) {
                            foreach (Tools::getValue($this->table.'Box') as $id) {
                                /** @var ObjectModel $toDelete */
                                $toDelete = new $this->className($id);
                                $toDelete->deleted = 1;
                                $result = $result && $toDelete->update();
                            }
                        } else {
                            $result = $object->deleteSelection(Tools::getValue($this->table.'Box'));
                        }

                        if ($result) {
                            Tools::redirectAdmin(static::$currentIndex.'&conf=2&token='.$token);
                        }
                        $this->_errors[] = Tools::displayError('An error occurred while deleting selection.');
                    }
                    // clean carriers positions
                    Carrier::cleanPositions();
                } else {
                    $this->_errors[] = Tools::displayError('You must select at least one element to delete.');
                }
            } else {
                $this->_errors[] = Tools::displayError('You do not have permission to delete here.');
            }
        } /* Create or update an object */
        elseif (Tools::getValue('submitAdd'.$this->table)) {
            /* Checking fields validity */
            $this->validateRules();
            if (!count($this->_errors)) {
                $id = (int) (Tools::getValue($this->identifier));

                /* Object update */
                if (isset($id) && !empty($id)) {
                    if ($this->tabAccess['edit'] === '1' || ($this->table == 'employee' && $this->context->employee->id == Tools::getValue('id_employee') && Tools::isSubmit('updateemployee'))) {
                        /** @var ObjectModel $object */
                        $object = new $this->className($id);
                        if (Validate::isLoadedObject($object)) {
                            /* Specific to objects which must not be deleted */
                            if ($this->deleted && $this->beforeDelete($object)) {
                                /** @var ObjectModel $objectNew */
                                // Create new one with old objet values
                                $objectNew = new $this->className($object->id);
                                $objectNew->id = null;
                                $objectNew->date_add = '';
                                $objectNew->date_upd = '';

                                // Update old object to deleted
                                $object->deleted = 1;
                                $object->update();

                                // Update new object with post values
                                $this->copyFromPost($objectNew, $this->table);
                                $result = $objectNew->add();
                                if (Validate::isLoadedObject($objectNew)) {
                                    $this->afterDelete($objectNew, $object->id);
                                }
                            } else {
                                $this->copyFromPost($object, $this->table);
                                $result = $object->update();
                                $this->afterUpdate($object);
                            }

                            if ($object->id) {
                                $this->updateAssoShop($object->id);
                            }

                            if (!$result) {
                                $this->_errors[] = Tools::displayError('An error occurred while updating object.').' <b>'.$this->table.'</b> ('.Db::getInstance()->getMsgError().')';
                            } elseif ($this->postImage($object->id) && !count($this->_errors)) {
                                if ($this->table == 'group') {
                                    $this->updateRestrictions($object->id);
                                }
                                $parentId = (int) (Tools::getValue('id_parent', 1));
                                // Specific back redirect
                                if ($back = Tools::getValue('back')) {
                                    Tools::redirectAdmin(urldecode($back).'&conf=4');
                                }
                                // Specific scene feature
                                if (Tools::getValue('stay_here') == 'on' || Tools::getValue('stay_here') == 'true' || Tools::getValue('stay_here') == '1') {
                                    Tools::redirectAdmin(static::$currentIndex.'&'.$this->identifier.'='.$object->id.'&conf=4&updatescene&token='.$token);
                                }
                                // Save and stay on same form
                                if (Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
                                    Tools::redirectAdmin(static::$currentIndex.'&'.$this->identifier.'='.$object->id.'&conf=4&update'.$this->table.'&token='.$token);
                                }
                                // Save and back to parent
                                if (Tools::isSubmit('submitAdd'.$this->table.'AndBackToParent')) {
                                    Tools::redirectAdmin(static::$currentIndex.'&'.$this->identifier.'='.$parentId.'&conf=4&token='.$token);
                                }
                                // Default behavior (save and back)
                                Tools::redirectAdmin(static::$currentIndex.($parentId ? '&'.$this->identifier.'='.$object->id : '').'&conf=4&token='.$token);
                            }
                        } else {
                            $this->_errors[] = Tools::displayError('An error occurred while updating object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
                        }
                    } else {
                        $this->_errors[] = Tools::displayError('You do not have permission to edit here.');
                    }
                } /* Object creation */
                else {
                    if ($this->tabAccess['add'] === '1') {
                        /** @var ObjectModel $object */
                        $object = new $this->className();
                        $this->copyFromPost($object, $this->table);
                        if (!$object->add()) {
                            $this->_errors[] = Tools::displayError('An error occurred while creating object.').' <b>'.$this->table.' ('.Db::getInstance()->getMsgError().')</b>';
                        } elseif (($_POST[$this->identifier] = $object->id /* voluntary */) && $this->postImage($object->id) && !count($this->_errors) && $this->_redirect) {
                            $parentId = (int) (Tools::getValue('id_parent', 1));
                            $this->afterAdd($object);
                            $this->updateAssoShop($object->id);
                            if ($this->table == 'group') {
                                $this->updateRestrictions($object->id);
                                // assign group access to every categories
                                $categories = Category::getCategories($this->context->language->id, true);
                                $rowList = [];
                                $a = 0;
                                foreach ($categories as $category) {
                                    foreach ($category as $categId => $categ) {
                                        if ($categId != 1) {
                                            $rowList[] = ['id_category' => $categId, 'id_group' => $object->id];
                                        }
                                    }
                                }
                                Db::getInstance()->insert('category_group', $rowList);
                            }
                            // Save and stay on same form
                            if (Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
                                Tools::redirectAdmin(static::$currentIndex.'&'.$this->identifier.'='.$object->id.'&conf=3&update'.$this->table.'&token='.$token);
                            }
                            // Save and back to parent
                            if (Tools::isSubmit('submitAdd'.$this->table.'AndBackToParent')) {
                                Tools::redirectAdmin(static::$currentIndex.'&'.$this->identifier.'='.$parentId.'&conf=3&token='.$token);
                            }
                            // Default behavior (save and back)
                            Tools::redirectAdmin(static::$currentIndex.($parentId ? '&'.$this->identifier.'='.$object->id : '').'&conf=3&token='.$token);
                        }
                    } else {
                        $this->_errors[] = Tools::displayError('You do not have permission to add here.');
                    }
                }
            }
            $this->_errors = array_unique($this->_errors);
        } /* Cancel all filters for this tab */
        elseif (isset($_POST['submitReset'.$this->table])) {
            $filters = $this->context->cookie->getFamily($this->table.'Filter_');
            foreach ($filters as $cookieKey => $filter) {
                if (strncmp($cookieKey, $this->table.'Filter_', 7 + mb_strlen($this->table)) == 0) {
                    $key = mb_substr($cookieKey, 7 + mb_strlen($this->table));
                    /* Table alias could be specified using a ! eg. alias!field */
                    $tmpTab = explode('!', $key);
                    $key = (count($tmpTab) > 1 ? $tmpTab[1] : $tmpTab[0]);
                    if (array_key_exists($key, $this->fieldsDisplay)) {
                        unset($this->context->cookie->$cookieKey);
                    }
                }
            }
            if (isset($this->context->cookie->{'submitFilter'.$this->table})) {
                unset($this->context->cookie->{'submitFilter'.$this->table});
            }
            if (isset($this->context->cookie->{$this->table.'Orderby'})) {
                unset($this->context->cookie->{$this->table.'Orderby'});
            }
            if (isset($this->context->cookie->{$this->table.'Orderway'})) {
                unset($this->context->cookie->{$this->table.'Orderway'});
            }
            unset($_POST);
        } /* Submit options list */
        elseif (Tools::getValue('submitOptions'.$this->table)) {
            $this->updateOptions($token);
        } /* Manage list filtering */
        elseif (Tools::isSubmit('submitFilter'.$this->table) || $this->context->cookie->{'submitFilter'.$this->table} !== false) {
            $_POST = array_merge($this->context->cookie->getFamily($this->table.'Filter_'), (isset($_POST) ? $_POST : []));
            foreach ($_POST as $key => $value) {
                /* Extracting filters from $_POST on key filter_ */
                if ($value != null && !strncmp($key, $this->table.'Filter_', 7 + mb_strlen($this->table))) {
                    $key = mb_substr($key, 7 + mb_strlen($this->table));
                    /* Table alias could be specified using a ! eg. alias!field */
                    $tmpTab = explode('!', $key);
                    $filter = count($tmpTab) > 1 ? $tmpTab[1] : $tmpTab[0];
                    if ($field = $this->filterToField($key, $filter)) {
                        $type = (array_key_exists('filter_type', $field) ? $field['filter_type'] : (array_key_exists('type', $field) ? $field['type'] : false));
                        $key = isset($tmpTab[1]) ? $tmpTab[0].'.`'.bqSQL($tmpTab[1]).'`' : '`'.bqSQL($tmpTab[0]).'`';
                        if (array_key_exists('tmpTableFilter', $field)) {
                            $sqlFilter = &$this->_tmpTableFilter;
                        } elseif (array_key_exists('havingFilter', $field)) {
                            $sqlFilter = &$this->_filterHaving;
                        } else {
                            $sqlFilter = &$this->_filter;
                        }

                        /* Only for date filtering (from, to) */
                        if (is_array($value)) {
                            if (isset($value[0]) && !empty($value[0])) {
                                if (!Validate::isDate($value[0])) {
                                    $this->_errors[] = Tools::displayError('\'From:\' date format is invalid (YYYY-MM-DD)');
                                } else {
                                    $sqlFilter .= ' AND '.$key.' >= \''.pSQL(Tools::dateFrom($value[0])).'\'';
                                }
                            }

                            if (isset($value[1]) && !empty($value[1])) {
                                if (!Validate::isDate($value[1])) {
                                    $this->_errors[] = Tools::displayError('\'To:\' date format is invalid (YYYY-MM-DD)');
                                } else {
                                    $sqlFilter .= ' AND '.$key.' <= \''.pSQL(Tools::dateTo($value[1])).'\'';
                                }
                            }
                        } else {
                            $sqlFilter .= ' AND ';
                            if ($type == 'int' || $type == 'bool') {
                                $sqlFilter .= (($key == $this->identifier || $key == '`'.$this->identifier.'`' || $key == '`active`') ? 'a.' : '').pSQL($key).' = '.(int) ($value).' ';
                            } elseif ($type == 'decimal') {
                                $sqlFilter .= (($key == $this->identifier || $key == '`'.$this->identifier.'`') ? 'a.' : '').pSQL($key).' = '.(float) ($value).' ';
                            } elseif ($type == 'select') {
                                $sqlFilter .= (($key == $this->identifier || $key == '`'.$this->identifier.'`') ? 'a.' : '').pSQL($key).' = \''.pSQL($value).'\' ';
                            } else {
                                $sqlFilter .= (($key == $this->identifier || $key == '`'.$this->identifier.'`') ? 'a.' : '').pSQL($key).' LIKE \'%'.pSQL($value).'%\' ';
                            }
                        }
                    }
                }
            }
        } elseif (Tools::isSubmit('submitFields') && $this->requiredDatabase && $this->tabAccess['add'] === '1' && $this->tabAccess['delete'] === '1') {
            if (!is_array($fields = Tools::getValue('fieldsBox'))) {
                $fields = [];
            }

            /** @var ObjectModel $object */
            $object = new $this->className();
            if (!$object->addFieldsRequiredDatabase($fields)) {
                $this->_errors[] = Tools::displayError('Error in updating required fields');
            } else {
                Tools::redirectAdmin(static::$currentIndex.'&conf=4&token='.$token);
            }
        }
    }

    /**
     * Load class object using identifier in $_GET (if possible)
     * otherwise return an empty object, or die
     *
     * @param bool $opt Return an empty object if load fail
     *
     * @return object
     */
    protected function loadObject($opt = false)
    {
        $id = (int) Tools::getValue($this->identifier);
        if ($id && Validate::isUnsignedId($id)) {
            if (!$this->_object) {
                $this->_object = new $this->className($id);
            }
            if (Validate::isLoadedObject($this->_object)) {
                return $this->_object;
            }
            $this->_errors[] = Tools::displayError('Object cannot be loaded (not found)');
        } elseif ($opt) {
            $this->_object = new $this->className();

            return $this->_object;
        } else {
            $this->_errors[] = Tools::displayError('Object cannot be loaded (identifier missing or invalid)');
        }

        $this->displayErrors();
    }

    /**
     * Display errors
     */
    public function displayErrors()
    {
        if ($nbErrors = count($this->_errors) && $this->_includeContainer) {
            echo '<script type="text/javascript">
				$(document).ready(function() {
					$(\'#hideError\').unbind(\'click\').click(function(){
						$(\'.error\').hide(\'slow\', function (){
							$(\'.error\').remove();
						});
						return false;
					});
				});
			  </script>
			<div class="error"><span style="float:right"><a id="hideError" href=""><img alt="X" src="../img/admin/close.png" /></a></span><img src="../img/admin/error2.png" />';
            if (count($this->_errors) == 1) {
                echo $this->_errors[0];
            } else {
                echo sprintf($this->l('%d errors'), $nbErrors).'<br /><ol>';
                foreach ($this->_errors as $error) {
                    echo '<li>'.$error.'</li>';
                }
                echo '</ol>';
            }
            echo '</div>';
        }
        $this->includeSubTab('displayErrors');
    }

    /**
     * Manage page display (form, list...)
     *
     * @param string $className Allow to validate a different class than the current one
     *
     * @throws PrestaShopException
     */
    public function validateRules($className = false)
    {
        if (!$className) {
            $className = $this->className;
        }

        /* Class specific validation rules */
        $rules = call_user_func([$className, 'getValidationRules'], $className);

        if ((count($rules['requiredLang']) || count($rules['sizeLang']) || count($rules['validateLang']))) {
            /* Language() instance determined by default language */
            $defaultLanguage = new Language((int) (Configuration::get('PS_LANG_DEFAULT')));

            /* All availables languages */
            $languages = Language::getLanguages(false);
        }

        /* Checking for required fields */
        foreach ($rules['required'] as $field) {
            if (($value = Tools::getValue($field)) == false && (string) $value != '0') {
                if (!Tools::getValue($this->identifier) || ($field != 'passwd' && $field != 'no-picture')) {
                    $this->_errors[] = sprintf(Tools::displayError('The field %s is required.'), call_user_func([$className, 'displayFieldName'], $field, $className));
                }
            }
        }

        /* Checking for multilingual required fields */
        foreach ($rules['requiredLang'] as $fieldLang) {
            if (($empty = Tools::getValue($fieldLang.'_'.$defaultLanguage->id)) === false || $empty !== '0' && empty($empty)) {
                $this->_errors[] = sprintf(Tools::displayError('The field %1$s is required at least in %2$s.'), call_user_func([$className, 'displayFieldName'], $fieldLang, $className), $defaultLanguage->name);
            }
        }

        /* Checking for maximum fields sizes */
        foreach ($rules['size'] as $field => $maxLength) {
            if (Tools::getValue($field) !== false && mb_strlen(Tools::getValue($field)) > $maxLength) {
                $this->_errors[] = sprintf(Tools::displayError('field %1$s is too long. (%2$d chars max)'), call_user_func([$className, 'displayFieldName'], $field, $className), $maxLength);
            }
        }

        /* Checking for maximum multilingual fields size */
        foreach ($rules['sizeLang'] as $fieldLang => $maxLength) {
            foreach ($languages as $language) {
                if (Tools::getValue($fieldLang.'_'.$language['id_lang']) !== false && mb_strlen(Tools::getValue($fieldLang.'_'.$language['id_lang'])) > $maxLength) {
                    $this->_errors[] = sprintf(Tools::displayError('field %1$s is too long. (%2$d chars max, html chars including)'), call_user_func([$className, 'displayFieldName'], $fieldLang, $className), $maxLength);
                }
            }
        }

        /* Overload this method for custom checking */
        $this->_childValidation();

        /* Checking for fields validity */
        foreach ($rules['validate'] as $field => $function) {
            if (($value = Tools::getValue($field)) !== false && !empty($value) && ($field != 'passwd')) {
                if (!Validate::$function($value)) {
                    $this->_errors[] = sprintf(Tools::displayError('The field %1$s (%2$s) is invalid.'), call_user_func([$className, 'displayFieldName'], $field, $className));
                }
            }
        }

        /* Checking for passwd_old validity */
        if (($value = Tools::getValue('passwd')) != false) {
            if ($className == 'Employee' && !Validate::isPasswdAdmin($value)) {
                $this->_errors[] = sprintf(Tools::displayError('The field %1$s (%2$s) is invalid.'), call_user_func([$className, 'displayFieldName'], 'passwd', $className));
            } elseif ($className == 'Customer' && !Validate::isPasswd($value)) {
                $this->_errors[] = sprintf(Tools::displayError('The field %1$s (%2$s) is invalid.'), call_user_func([$className, 'displayFieldName'], 'passwd', $className));
            }
        }

        /* Checking for multilingual fields validity */
        foreach ($rules['validateLang'] as $fieldLang => $function) {
            foreach ($languages as $language) {
                if (($value = Tools::getValue($fieldLang.'_'.$language['id_lang'])) !== false && !empty($value)) {
                    if (!Validate::$function($value)) {
                        $this->_errors[] = sprintf(Tools::displayError('The field %1$s (%2$s) is invalid.'), call_user_func([$className, 'displayFieldName'], $fieldLang, $className), $language['name']);
                    }
                }
            }
        }
    }

    /**
     * Overload this method for custom checking
     *
     * @deprecated 1.0.0
     */
    protected function _childValidation()
    {
    }

    /**
     * Called before deletion
     *
     * @param object $object Object
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */
    protected function beforeDelete($object)
    {
        return true;
    }

    /**
     * Copy datas from $_POST to object
     *
     * @param object &$object Object
     * @param string $table   Object table
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    protected function copyFromPost(&$object, $table)
    {
        /* Classical fields */
        foreach ($_POST as $key => $value) {
            if (array_key_exists($key, $object) && $key != 'id_'.$table) {
                /* Do not take care of password field if empty */
                if ($key == 'passwd' && Tools::getValue('id_'.$table) && empty($value)) {
                    continue;
                }
                /* Automatically encrypt password in MD5 */
                if ($key == 'passwd' && !empty($value)) {
                    $value = Tools::encrypt($value);
                }
                $object->{$key} = $value;
            }
        }

        /* Multilingual fields */
        $rules = call_user_func([get_class($object), 'getValidationRules'], get_class($object));
        if (count($rules['validateLang'])) {
            $languageIds = Language::getIDs(false);
            foreach ($languageIds as $idLang) {
                foreach (array_keys($rules['validateLang']) as $field) {
                    if (Tools::isSubmit($field.'_'.(int) $idLang)) {
                        $object->{$field}[(int) $idLang] = Tools::getValue($field.'_'.(int) $idLang);
                    }
                }
            }
        }
    }

    /**
     * Called before deletion
     *
     * @param object $object Object
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */
    protected function afterDelete($object, $oldId)
    {
        return true;
    }

    /**
     * @param $object
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */
    protected function afterUpdate($object)
    {
        return true;
    }

    /**
     * @param bool $idObject
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    protected function updateAssoShop($idObject = false)
    {
        if (!Shop::isFeatureActive()) {
            return;
        }

        if (!$assos = static::getAssoShop($this->table, $idObject)) {
            return;
        }

        Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.$this->table.'_'.$assos[1].($idObject ? ' WHERE `'.$this->identifier.'`='.(int) $idObject : ''));
        foreach ($assos[0] as $asso) {
            Db::getInstance()->execute(
                'INSERT INTO '._DB_PREFIX_.$this->table.'_'.$assos[1].' (`'.pSQL($this->identifier).'`, id_'.$assos[1].')
											VALUES('.(int) $asso['id_object'].', '.(int) $asso['id_'.$assos[1]].')'
            );
        }
    }

    /**
     * Overload this method for custom checking
     *
     * @param int $id Object id used for deleting images
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */
    protected function postImage($id)
    {
        if (isset($this->fieldImageSettings['name']) && isset($this->fieldImageSettings['dir'])) {
            return $this->uploadImage($id, $this->fieldImageSettings['name'], $this->fieldImageSettings['dir'].'/');
        } elseif (!empty($this->fieldImageSettings)) {
            foreach ($this->fieldImageSettings as $image) {
                if (isset($image['name']) && isset($image['dir'])) {
                    $this->uploadImage($id, $image['name'], $image['dir'].'/');
                }
            }
        }

        return !count($this->_errors) ? true : false;
    }

    protected function uploadImage($id, $name, $dir, $ext = false, $width = null, $height = null)
    {
        if (isset($_FILES[$name]['tmp_name']) && !empty($_FILES[$name]['tmp_name'])) {
            // Delete old image
            if (Validate::isLoadedObject($object = $this->loadObject())) {
                $object->deleteImage();
            } else {
                return false;
            }

            // Check image validity
            $maxSize = isset($this->maxImageSize) ? $this->maxImageSize : 0;
            if ($error = ImageManager::validateUpload($_FILES[$name], Tools::getMaxUploadSize($maxSize))) {
                $this->_errors[] = $error;
            } elseif (!($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES[$name]['tmp_name'], $tmpName)) {
                return false;
            } else {
                $_FILES[$name]['tmp_name'] = $tmpName;
                // Copy new image
                if (!ImageManager::resize($tmpName, _PS_IMG_DIR_.$dir.$id.'.'.$this->imageType, (int) $width, (int) $height, ($ext ? $ext : $this->imageType))) {
                    $this->_errors[] = Tools::displayError('An error occurred while uploading image.');
                }
                if (count($this->_errors)) {
                    return false;
                }
                if ($this->afterImageUpload()) {
                    unlink($tmpName);

                    return true;
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Check rights to view the current tab
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */

    protected function afterImageUpload()
    {
        return true;
    }

    /**
     * @param $object
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */
    protected function afterAdd($object)
    {
        return true;
    }

    /**
     * Update options and preferences
     *
     * @param string $token
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    protected function updateOptions($token)
    {
        if ($this->tabAccess['edit'] === '1') {
            $this->beforeUpdateOptions();

            $languageIds = Language::getIDs(false);
            foreach ($this->optionsList as $category => $categoryData) {
                $fields = $categoryData['fields'];

                /* Check required fields */
                foreach ($fields as $field => $values) {
                    if (isset($values['required']) && $values['required'] && !empty($_POST['multishopOverrideOption'][$field])) {
                        if (isset($values['type']) && $values['type'] == 'textLang') {
                            foreach ($languageIds as $idLang) {
                                if (($value = Tools::getValue($field.'_'.$idLang)) == false && (string) $value != '0') {
                                    $this->_errors[] = sprintf(Tools::displayError('field %s is required.'), $values['title']);
                                }
                            }
                        } elseif (($value = Tools::getValue($field)) == false && (string) $value != '0') {
                            $this->_errors[] = sprintf(Tools::displayError('field %s is required.'), $values['title']);
                        }
                    }
                }

                /* Check fields validity */
                foreach ($fields as $field => $values) {
                    if (isset($values['type']) && $values['type'] == 'textLang') {
                        foreach ($languageIds as $idLang) {
                            if (Tools::getValue($field.'_'.$idLang) && isset($values['validation'])) {
                                $valuesValidation = $values['validation'];
                                if (!Validate::$valuesValidation(Tools::getValue($field.'_'.$idLang))) {
                                    $this->_errors[] = sprintf(Tools::displayError('field %s is invalid.'), $values['title']);
                                }
                            }
                        }
                    } elseif (Tools::getValue($field) && isset($values['validation'])) {
                        $valuesValidation = $values['validation'];
                        if (!Validate::$valuesValidation(Tools::getValue($field))) {
                            $this->_errors[] = sprintf(Tools::displayError('field %s is invalid.'), $values['title']);
                        }
                    }
                }

                /* Default value if null */
                foreach ($fields as $field => $values) {
                    if (!Tools::getValue($field) && isset($values['default'])) {
                        $_POST[$field] = $values['default'];
                    }
                }

                if (!count($this->_errors)) {
                    foreach ($fields as $key => $options) {
                        if (isset($options['visibility']) && $options['visibility'] > Shop::getContext()) {
                            continue;
                        }

                        if (Shop::isFeatureActive() && empty($_POST['multishopOverrideOption'][$key])) {
                            Configuration::deleteFromContext($key);
                            continue;
                        }

                        // check if a method updateOptionFieldName is available
                        $methodName = 'updateOption'.Tools::toCamelCase($key, true);
                        if (method_exists($this, $methodName)) {
                            $this->$methodName(Tools::getValue($key));
                        } elseif (isset($options['type']) && in_array($options['type'], ['textLang', 'textareaLang'])) {
                            $list = [];
                            foreach ($languageIds as $idLang) {
                                $val = (isset($options['cast']) ? $options['cast'](Tools::getValue($key.'_'.$idLang)) : Tools::getValue($key.'_'.$idLang));
                                if ($this->validateField($val, $options)) {
                                    if (Validate::isCleanHtml($val)) {
                                        $list[$idLang] = $val;
                                    } else {
                                        $this->_errors[] = Tools::displayError('Can not add configuration '.$key.' for lang '.Language::getIsoById((int) $idLang));
                                    }
                                }
                            }
                            Configuration::updateValue($key, $list);
                        } else {
                            $val = (isset($options['cast']) ? $options['cast'](Tools::getValue($key)) : Tools::getValue($key));
                            if ($this->validateField($val, $options)) {
                                if (Validate::isCleanHtml($val)) {
                                    Configuration::updateValue($key, $val);
                                } else {
                                    $this->_errors[] = Tools::displayError('Can not add configuration '.$key);
                                }
                            }
                        }
                    }
                }
            }

            if (count($this->_errors) <= 0) {
                Tools::redirectAdmin(static::$currentIndex.'&conf=6&token='.$token);
            }
        } else {
            $this->_errors[] = Tools::displayError('You do not have permission to edit here.');
        }
    }

    /**
     * Can be overriden
     *
     * @deprecated 1.0.0
     */
    public function beforeUpdateOptions()
    {
    }

    /**
     * @param $value
     * @param $field
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */
    protected function validateField($value, $field)
    {
        if (isset($field['validation'])) {
            $fieldValidation = $field['validation'];
            if ((!isset($field['empty']) || !$field['empty'] || (isset($field['empty']) && $field['empty'] && $value)) && method_exists('Validate', $field['validation'])) {
                if (!Validate::$fieldValidation($value)) {
                    $this->_errors[] = Tools::displayError($field['title'].' : Incorrect value');

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param $key
     * @param $filter
     *
     * @return mixed
     *
     * @deprecated 1.0.0
     */
    protected function filterToField($key, $filter)
    {
        foreach ($this->fieldsDisplay as $field) {
            if (array_key_exists('filter_key', $field) && $field['filter_key'] == $key) {
                return $field;
            }
        }
        if (array_key_exists($filter, $this->fieldsDisplay)) {
            return $this->fieldsDisplay[$filter];
        }

        return false;
    }

    /**
     * Display confirmations
     *
     * @deprecated 1.0.0
     */
    public function displayConf()
    {
        if ($conf = Tools::getValue('conf')) {
            echo '
			<div class="conf">
				'.$this->_conf[(int) ($conf)].'
			</div>';
        }
    }

    /**
     * Display image aside object form
     *
     * @param int    $id           Object id
     * @param string $image        Local image filepath
     * @param int    $size         Image width
     * @param int    $idImage      Image id (for products with several images)
     * @param string $token        Employee token used in the image deletion link
     * @param bool   $disableCache When turned on a timestamp will be added to the image URI to disable the HTTP cache
     *
     * @deprecated 1.0.0
     */
    public function displayImage($id, $image, $size, $idImage = null, $token = null, $disableCache = false)
    {
        if (!isset($token) || empty($token)) {
            $token = $this->token;
        }
        if ($id && file_exists($image)) {
            echo '
			<div id="image" >
				'.ImageManager::thumbnail($image, $this->table.'_'.(int) ($id).'.'.$this->imageType, $size, $this->imageType, $disableCache).'
				<p align="center">'.$this->l('File size').' '.(filesize($image) / 1000).'kb</p>
				<a href="'.static::$currentIndex.'&'.$this->identifier.'='.(int) ($id).'&token='.$token.($idImage ? '&id_image='.(int) ($idImage) : '').'&deleteImage=1">
				<img src="../img/admin/delete.gif" alt="'.$this->l('Delete').'" /> '.$this->l('Delete').'</a>
			</div>';
        }
    }

    /**
     * Type = select
     *
     * @deprecated 1.0.0
     *
     * @param $key
     * @param $field
     * @param $value
     */
    public function displayOptionTypeSelect($key, $field, $value)
    {
        echo '<select name="'.$key.'"'.(isset($field['js']) === true ? ' onchange="'.$field['js'].'"' : '').' id="'.$key.'">';
        foreach ($field['list'] as $k => $option) {
            echo '<option value="'.(isset($option['cast']) ? $option['cast']($option[$field['identifier']]) : $option[$field['identifier']]).'"'.(($value == $option[$field['identifier']]) ? ' selected="selected"' : '').'>'.$option['name'].'</option>';
        }
        echo '</select>';
    }

    /**
     * Type = bool
     *
     * @deprecated 1.0.0
     *
     * @param $key
     * @param $field
     * @param $value
     */
    public function displayOptionTypeBool($key, $field, $value)
    {
        echo '<label class="t" for="'.$key.'_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>';
        echo '<input type="radio" name="'.$key.'" id="'.$key.'_on" value="1" '.($value ? ' checked="checked" ' : '').(isset($field['js']['on']) ? $field['js']['on'] : '').' />';
        echo '<label class="t" for="'.$key.'_on"> '.$this->l('Yes').'</label>';

        echo '<label class="t" for="'.$key.'_off"><img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" style="margin-left: 10px;" /></label>';
        echo '<input type="radio" name="'.$key.'" id="'.$key.'_off" value="0" '.(!$value ? ' checked="checked" ' : '').(isset($field['js']['off']) ? $field['js']['off'] : '').' />';
        echo '<label class="t" for="'.$key.'_off"> '.$this->l('No').'</label>';
    }

    /**
     * Type = radio
     *
     * @deprecated 1.0.0
     *
     * @param $key
     * @param $field
     * @param $value
     */
    public function displayOptionTypeRadio($key, $field, $value)
    {
        foreach ($field['choices'] as $k => $v) {
            echo '<input type="radio" name="'.$key.'" id="'.$key.$k.'_on" value="'.(int) $k.'"'.(($k == $value) ? ' checked="checked"' : '').(isset($field['js'][$k]) ? ' '.$field['js'][$k] : '').' /><label class="t" for="'.$key.$k.'_on"> '.$v.'</label><br />';
        }
        echo '<br />';
    }

    /**
     * Type = password
     *
     * @deprecated 1.0.0
     *
     * @param $key
     * @param $field
     * @param $value
     */
    public function displayOptionTypePassword($key, $field, $value)
    {
        $this->displayOptionTypeText($key, $field, '');
    }

    /**
     * Type = textarea
     *
     * @param $key
     * @param $field
     * @param $value
     *
     * @deprecated 1.0.0
     */
    public function displayOptionTypeTextarea($key, $field, $value)
    {
        echo '<textarea name='.$key.' cols="'.$field['cols'].'" rows="'.$field['rows'].'">'.htmlentities($value, ENT_COMPAT, 'UTF-8').'</textarea>';
    }

    /**
     * Type = file
     *
     * @param $key
     * @param $field
     * @param $value
     *
     * @deprecated 1.0.0
     */
    public function displayOptionTypeFile($key, $field, $value)
    {
        if (isset($field['thumb']) && $field['thumb'] && $field['thumb']['pos'] == 'before') {
            echo '<img src="'.$field['thumb']['file'].'" alt="'.$field['title'].'" title="'.$field['title'].'" /><br />';
        }
        echo '<input type="file" name="'.$key.'" />';
    }

    /**
     * Type = image
     *
     * @param $key
     * @param $field
     * @param $value
     *
     * @deprecated 1.0.0
     */
    public function displayOptionTypeImage($key, $field, $value)
    {
        echo '<table cellspacing="0" cellpadding="0">';
        echo '<tr>';

        /*if ($name == 'themes')
            echo '
            <td colspan="'.sizeof($field['list']).'">
                <b>'.$this->l('In order to use a new theme, please follow these steps:', get_class()).'</b>
                <ul>
                    <li>'.$this->l('Import your theme using this module:', get_class()).' <a href="index.php?tab=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'&filtername=themeinstallator" style="text-decoration: underline;">'.$this->l('Theme installer', get_class()).'</a></li>
                    <li>'.$this->l('When your theme is imported, please select the theme in this page', get_class()).'</li>
                </ul>
            </td>
            </tr>
            <tr>
            ';*/

        $i = 0;
        foreach ($field['list'] as $theme) {
            echo '<td class="center" style="width: 180px; padding:0px 20px 20px 0px;">';
            echo '<input type="radio" name="'.$key.'" id="'.$key.'_'.$theme['name'].'_on" style="vertical-align: text-bottom;" value="'.$theme['name'].'"'.(_THEME_NAME_ == $theme['name'] ? 'checked="checked"' : '').' />';
            echo '<label class="t" for="'.$key.'_'.$theme['name'].'_on"> '.mb_strtolower($theme['name']).'</label>';
            echo '<br />';
            echo '<label class="t" for="'.$key.'_'.$theme['name'].'_on">';
            echo '<img src="../themes/'.$theme['name'].'/preview.jpg" alt="'.mb_strtolower($theme['name']).'">';
            echo '</label>';
            echo '</td>';
            if (isset($field['max']) && ($i + 1) % $field['max'] == 0) {
                echo '</tr><tr>';
            }
            $i++;
        }
        echo '</tr>';
        echo '</table>';
    }

    /**
     * Type = textLang
     *
     * @param $key
     * @param $field
     * @param $value
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    public function displayOptionTypeTextLang($key, $field, $value)
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            $value = Tools::getValue($key.'_'.$language['id_lang'], Configuration::get($key, $language['id_lang']));
            echo '<div id="'.$key.'_'.$language['id_lang'].'" style="margin-bottom:8px; display: '.($language['id_lang'] == $this->context->language->id ? 'block' : 'none').'; float: left; vertical-align: top;">';
            echo '<input type="text" size="'.(isset($field['size']) ? (int) $field['size'] : 5).'" name="'.$key.'_'.$language['id_lang'].'" value="'.htmlentities($value, ENT_COMPAT, 'UTF-8').'" />';
            echo '</div>';
        }
        $this->displayFlags($languages, $this->context->language->id, $key, $key);
    }

    /**
     * Display flags in forms for translations
     *
     * @param array  $languages               All languages available
     * @param int    $default_language        Default language id
     * @param string $ids                     Multilingual div ids in form
     * @param string $id                      Current div id]
     * @param bool   $return                  define the return way : false for a display, true for a return
     * @param bool   $use_vars_instead_of_ids use an js vars instead of ids seperate by "¤"
     *
     *                                        @deprecated 1.0.0
     */
    public function displayFlags($languages, $default_language, $ids, $id, $return = false, $use_vars_instead_of_ids = false)
    {
        if (count($languages) == 1) {
            return false;
        }
        $output = '
		<div class="displayed_flag">
			<img src="../img/l/'.$default_language.'.jpg" class="pointer" id="language_current_'.$id.'" onclick="toggleLanguageFlags(this);" alt="" />
		</div>
		<div id="languages_'.$id.'" class="language_flags">
			'.$this->l('Choose language:').'<br /><br />';
        foreach ($languages as $language) {
            if ($use_vars_instead_of_ids) {
                $output .= '<img src="../img/l/'.(int) ($language['id_lang']).'.jpg" class="pointer" alt="'.$language['name'].'" title="'.$language['name'].'" onclick="changeLanguage(\''.$id.'\', '.$ids.', '.$language['id_lang'].', \''.$language['iso_code'].'\');" /> ';
            } else {
                $output .= '<img src="../img/l/'.(int) ($language['id_lang']).'.jpg" class="pointer" alt="'.$language['name'].'" title="'.$language['name'].'" onclick="changeLanguage(\''.$id.'\', \''.$ids.'\', '.$language['id_lang'].', \''.$language['iso_code'].'\');" /> ';
            }
        }
        $output .= '</div>';

        if ($return) {
            return $output;
        }
        echo $output;
    }

    /**
     * Type = TextareaLang
     *
     * @param $key
     * @param $field
     * @param $value
     *
     * @throws PrestaShopException
     */
    public function displayOptionTypeTextareaLang($key, $field, $value)
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            $value = Configuration::get($key, $language['id_lang']);
            echo '<div id="'.$key.'_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $this->context->language->id ? 'block' : 'none').'; float: left;">';
            echo '<textarea rows="'.(int) ($field['rows']).'" cols="'.(int) ($field['cols']).'"  name="'.$key.'_'.$language['id_lang'].'">'.str_replace('\r\n', "\n", $value).'</textarea>';
            echo '</div>';
        }
        $this->displayFlags($languages, $this->context->language->id, $key, $key);
        echo '<br style="clear:both">';
    }

    /**
     * Type = selectLang
     *
     * @param $key
     * @param $field
     * @param $value
     *
     * @throws PrestaShopException
     */
    public function displayOptionTypeSelectLang($key, $field, $value)
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            echo '<div id="'.$key.'_'.$language['id_lang'].'" style="margin-bottom:8px; display: '.($language['id_lang'] == $this->context->language->id ? 'block' : 'none').'; float: left; vertical-align: top;">';
            echo '<select name="'.$key.'_'.mb_strtoupper($language['iso_code']).'">';
            foreach ($field['list'] as $k => $v) {
                echo '<option value="'.(isset($v['cast']) ? $v['cast']($v[$field['identifier']]) : $v[$field['identifier']]).'"'.((htmlentities(Tools::getValue($key.'_'.mb_strtoupper($language['iso_code']), (Configuration::get($key.'_'.mb_strtoupper($language['iso_code'])) ? Configuration::get($key.'_'.mb_strtoupper($language['iso_code'])) : '')), ENT_COMPAT, 'UTF-8') == $v[$field['identifier']]) ? ' selected="selected"' : '').'>'.$v['name'].'</option>';
            }
            echo '</select>';
            echo '</div>';
        }
        $this->displayFlags($languages, $this->context->language->id, $key, $key);
    }

    /**
     * Type = price
     *
     * @param $key
     * @param $field
     * @param $value
     */
    public function displayOptionTypePrice($key, $field, $value)
    {
        echo $this->context->currency->getSign('left');
        $this->displayOptionTypeText($key, $field, $value);
        echo $this->context->currency->getSign('right').' '.$this->l('(tax excl.)');
    }

    /**
     * Type = disabled
     *
     * @param $key
     * @param $field
     * @param $value
     */
    public function displayOptionTypeDisabled($key, $field, $value)
    {
        echo $field['disabled'];
    }

    /**
     * Return field value if possible (both classical and multilingual fields)
     *
     * Case 1 : Return value if present in $_POST / $_GET
     * Case 2 : Return object value
     *
     * @param object $obj     Object
     * @param string $key     Field name
     * @param int    $id_lang Language id (optional)
     *
     * @param null   $idShop
     *
     * @return string
     */
    public function getFieldValue($obj, $key, $id_lang = null, $idShop = null)
    {
        if (!$idShop && $obj->isLangMultishop()) {
            $idShop = Context::getContext()->shop->id;
        }

        if ($id_lang) {
            $defaultValue = ($obj->id && isset($obj->{$key}[$id_lang])) ? $obj->{$key}[$id_lang] : '';
        } else {
            $defaultValue = isset($obj->{$key}) ? $obj->{$key} : '';
        }

        return Tools::getValue($key.($id_lang ? '_'.$idShop.'_'.$id_lang : ''), $defaultValue);
    }

    /**
     * Display object details
     *
     * @deprecated 1.0.0
     */
    public function viewDetails()
    {
    }

    /**
     * Check rights to view the current tab
     *
     * @param bool $disable
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */

    public function viewAccess($disable = false)
    {
        if ($disable) {
            return true;
        }

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, $this->id);

        if ($this->tabAccess['view'] === '1') {
            return true;
        }

        return false;
    }

    /**
     * Check for security token
     *
     * @deprecated 1.0.0
     */
    public function checkToken()
    {
        $token = Tools::getValue('token');

        return (!empty($token) && $token === $this->token);
    }

    /**
     * @deprecated 1.0.0
     */
    protected function warnDomainName()
    {
        if ($_SERVER['HTTP_HOST'] != Configuration::get('PS_SHOP_DOMAIN') && $_SERVER['HTTP_HOST'] != Configuration::get('PS_SHOP_DOMAIN_SSL')) {
            $this->displayWarning(
                $this->l('You are currently connected with the following domain name:').' <span style="color: #CC0000;">'.$_SERVER['HTTP_HOST'].'</span><br />'.
                $this->l('This one is different from the main shop\'s domain name set in "Preferences > SEO & URLs":').' <span style="color: #CC0000;">'.Configuration::get('PS_SHOP_DOMAIN').'</span><br />
			<a href="index.php?tab=AdminMeta&token='.Tools::getAdminTokenLite('AdminMeta').'#SEO%20%26%20URLs">'.
                $this->l('Click here if you want to modify the main shop\'s domain name').'</a>'
            );
        }
    }

    /**
     * @deprecated 1.0.0
     */
    protected function displayAssoShop()
    {
        if (!Shop::isFeatureActive() || (!$this->_object && Shop::getContext() != Shop::CONTEXT_ALL)) {
            return;
        }

        $assos = [];
        $sql = 'SELECT id_shop, `'.bqSQL($this->identifier).'`
				FROM `'._DB_PREFIX_.bqSQL($this->table).'_shop`';
        foreach (Db::getInstance()->executeS($sql) as $row) {
            $assos[$row['id_shop']][] = $row[$this->identifier];
        }

        $html = <<<EOF
			<script type="text/javascript">
			$().ready(function()
			{
				// Click on "all shop"
				$('.input_all_shop').click(function()
				{
					var checked = $(this).prop('checked');
					$('.input_shop_group').attr('checked', checked);
					$('.input_shop').attr('checked', checked);
				});

				// Click on a group shop
				$('.input_shop_group').click(function()
				{
					$('.input_shop[value='+$(this).val()+']').attr('checked', $(this).prop('checked'));
					check_all_shop();
				});

				// Click on a shop
				$('.input_shop').click(function()
				{
					check_shop_group_status($(this).val());
					check_all_shop();
				});

				// Initialize checkbox
				$('.input_shop').each(function(k, v)
				{
					check_shop_group_status($(v).val());
					check_all_shop();
				});
			});

			function check_shop_group_status(id_group)
			{
				var groupChecked = true;
				$('.input_shop[value='+id_group+']').each(function(k, v)
				{
					if (!$(v).prop('checked'))
						groupChecked = false;
				});
				$('.input_shop_group[value='+id_group+']').attr('checked', groupChecked);
			}

			function check_all_shop()
			{
				var allChecked = true;
				$('.input_shop_group').each(function(k, v)
				{
					if (!$(v).prop('checked'))
						allChecked = false;
				});
				$('.input_all_shop').attr('checked', allChecked);
			}
			</script>
EOF;

        $html .= '<div class="assoShop">';
        $html .= '<table class="table" cellpadding="0" cellspacing="0" width="100%">
					<tr><th>'.$this->l('Shop').'</th></tr>';
        $html .= '<tr><td><label class="t"><input class="input_all_shop" type="checkbox" /> '.$this->l('All shops').'</label></td></tr>';
        foreach (Shop::getTree() as $groupID => $groupData) {
            $html .= '<tr class="alt_row">';
            $html .= '<td><img style="vertical-align: middle;" alt="" src="../img/admin/lv2_b.gif" /><label class="t"><input class="input_shop_group" type="checkbox" name="checkBoxShopGroupAsso_'.$this->table.'_'.$this->_object->id.'_'.$groupID.'" value="'.$groupID.'" '.($groupChecked ? 'checked="checked"' : '').' /> '.$groupData['name'].'</label></td>';
            $html .= '</tr>';

            $total = count($groupData['shops']);
            $j = 0;
            foreach ($groupData['shops'] as $shopID => $shopData) {
                $checked = ((isset($assos[$shopID]) && in_array($this->_object->id, $assos[$shopID])) || !$this->_object->id);
                $html .= '<tr>';
                $html .= '<td><img style="vertical-align: middle;" alt="" src="../img/admin/lv3_'.(($j < $total - 1) ? 'b' : 'f').'.png" /><label class="child">';
                $html .= '<input class="input_shop" type="checkbox" value="'.$groupID.'" name="checkBoxShopAsso_'.$this->table.'_'.$this->_object->id.'_'.$shopID.'" id="checkedBox_'.$shopID.'" '.($checked ? 'checked="checked"' : '').' /> ';
                $html .= $shopData['name'].'</label></td>';
                $html .= '</tr>';
                $j++;
            }
        }
        $html .= '</table></div>';
        echo $html;
    }

    /**
     * Get current URL
     *
     * @param array $remove List of keys to remove from URL
     *
     * @return string
     *
     * @deprecated 1.0.0
     */
    protected function getCurrentUrl($remove = [])
    {
        $url = $_SERVER['REQUEST_URI'];
        if (!$remove) {
            return $url;
        }

        if (!is_array($remove)) {
            $remove = [$remove];
        }

        $url = preg_replace('#(?<=&|\?)('.implode('|', $remove).')=.*?(&|$)#i', '', $url);
        $len = mb_strlen($url);
        if ($url[$len - 1] == '&') {
            $url = mb_substr($url, 0, $len - 1);
        }

        return $url;
    }
}
