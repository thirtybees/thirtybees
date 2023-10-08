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

use Thirtybees\Core\Error\ErrorUtils;
use Thirtybees\Core\Error\Response\JSendErrorResponse;

/**
 * Class AdminControllerCore
 */
class AdminControllerCore extends Controller
{
    const LEVEL_VIEW = 1;
    const LEVEL_EDIT = 2;
    const LEVEL_ADD = 3;
    const LEVEL_DELETE = 4;
    const DEFAULT_VIEW_TEMPLATE = 'content.tpl';

    // Cache file to make errors/warnings/informations/confirmations
    // survive redirects.
    const MESSAGE_CACHE_PATH = 'AdminControllerMessages.php';

    /** @var string */
    public static $currentIndex;
    /** @var array Cache for translations */
    public static $cache_lang = [];
    /** @var string */
    public $path;
    /** @var string|string[] */
    public $content;
    /** @var array */
    public $warnings = [];
    /** @var array */
    public $informations = [];
    /** @var array */
    public $confirmations = [];
    /** @var string|false */
    public $shopShareDatas = false;
    /** @var array */
    public $_languages = [];
    /** @var int */
    public $default_form_language;
    /** @var int */
    public $allow_employee_form_lang;
    /** @var string */
    public $layout = 'layout.tpl';
    /** @var bool */
    public $bootstrap = false;
    /** @var string */
    public $template = 'content.tpl';
    /** @var string Associated table name */
    public $table = 'configuration';
    /** @var string */
    public $list_id;
    /** @var string Associated object class name */
    public $className;
    /** @var array */
    public $tabAccess;
    /** @var int Tab id */
    public $id = -1;
    /** @var bool */
    public $required_database = false;
    /** @var string Security token */
    public $token;
    /** @var string "shop" or "group_shop" */
    public $shopLinkType;
    /** @var array */
    public $tpl_form_vars = [];
    /** @var array */
    public $tpl_list_vars = [];
    /** @var array */
    public $tpl_delete_link_vars = [];
    /** @var array */
    public $tpl_option_vars = [];
    /** @var array */
    public $tpl_view_vars = [];
    /** @var array */
    public $tpl_required_fields_vars = [];
    /** @var string|null */
    public $base_tpl_view = null;
    /** @var string|null */
    public $base_tpl_form = null;
    /** @var bool If you want more fieldsets in the form */
    public $multiple_fieldsets = false;
    /** @var array */
    public $fields_value = [];
    /** @var bool Automatically join language table if true */
    public $lang = false;
    /** @var array Required_fields to display in the Required Fields form */
    public $required_fields = [];
    /** @var string */
    public $tpl_folder;
    /** @var string  */
    public $override_folder;
    /** @var array Name and directory where class image are located */
    public $fieldImageSettings = [];
    /** @var string Image type */
    public $imageType = 'jpg';
    /** @var string Current controller name without suffix */
    public $controller_name;
    /** @var int */
    public $multishop_context = -1;
    /** @var false */
    public $multishop_context_group = true;
    /** @var bool Bootstrap variable */
    public $show_page_header_toolbar = false;
    /** @var string Bootstrap variable */
    public $page_header_toolbar_title;
    /** @var array|Traversable Bootstrap variable */
    public $page_header_toolbar_btn = [];
    /** @var bool Bootstrap variable */
    public $show_form_cancel_button;
    /** @var string */
    public $admin_webpath;
    /** @var array */
    public $modals = [];
    /** @var array */
    public $ajax_params = [];
    /** @var string|array */
    protected $meta_title = [];
    /** @var string|false Object identifier inside the associated table */
    protected $identifier = false;
    /** @var string */
    protected $identifier_name = 'name';
    /** @var string Default ORDER BY clause when $_orderBy is not defined */
    protected $_defaultOrderBy = false;
    /** @var string */
    protected $_defaultOrderWay = 'ASC';
    /** @var bool Define if the header of the list contains filter and sorting links or not */
    protected $list_simple_header;
    /** @var array List to be generated */
    protected $fields_list;
    /** @var array Modules list filters */
    protected $filter_modules_list = null;
    /** @var array Modules list filters */
    protected $modules_list = [];
    /** @var array Edit form to be generated */
    protected $fields_form;
    /** @var array Override of $fields_form */
    protected $fields_form_override;
    /** @var string Override form action */
    protected $submit_action;
    /** @var array List of option forms to be generated */
    protected $fields_options = [];
    /** @var string */
    protected $shopLink;
    /** @var string SQL query */
    protected $_listsql = '';
    /** @var array Cache for query results */
    protected $_list = [];
    /** @var string|null */
    protected $_list_error;
    /** @var string|array Toolbar title */
    protected $toolbar_title;
    /** @var array List of toolbar buttons */
    protected $toolbar_btn = null;
    /** @var bool Scrolling toolbar */
    protected $toolbar_scroll = true;
    /** @var bool Set to false to hide toolbar and page title */
    protected $show_toolbar = true;
    /** @var bool Set to true to show toolbar and page title for options */
    protected $show_toolbar_options = false;
    /** @var int Number of results in list */
    protected $_listTotal = 0;
    /** @var string|false WHERE clause determined by filter fields */
    protected $_filter;
    /** @var string */
    protected $_filterHaving;
    /** @var array Temporary SQL table WHERE clause determined by filter fields */
    protected $_tmpTableFilter = '';
    /** @var array Number of results in list per page (used in select field) */
    protected $_pagination = [20, 50, 100, 300, 1000];
    /** @var int Default number of results in list per page */
    protected $_default_pagination = 50;
    /** @var string ORDER BY clause determined by field/arrows in list header */
    protected $_orderBy;
    /** @var string Order way (ASC, DESC) determined by arrows in list header */
    protected $_orderWay;
    /** @var array List of available actions for each list row - default actions are view, edit, delete, duplicate */
    protected $actions_available = ['view', 'edit', 'duplicate', 'delete'];
    /** @var array List of required actions for each list row */
    protected $actions = [];
    /** @var array List of row ids associated with a given action for witch this action have to not be available */
    protected $list_skip_actions = [];
    /* @var bool Don't show header & footer */
    protected $lite_display = false;
    /** @var bool List content lines are clickable if true */
    protected $list_no_link = false;
    /** @var bool */
    protected $allow_export = false;
    /** @var HelperList */
    protected $helper;
    /**
     * Actions to execute on multiple selections.
     *
     * Usage:
     *
     * [
     *      'actionName'    => [
     *      'text'          => $this->l('Message displayed on the submit button (mandatory)'),
     *      'confirm'       => $this->l('If set, this confirmation message will pop-up (optional)')),
     *      'anotherAction' => [...]
     * ];
     *
     * If your action is named 'actionName', you need to have a method named bulkactionName() that will be executed when the button is clicked.
     *
     * @var array
     */
    protected $bulk_actions;
    /* @var array Ids of the rows selected */
    protected $boxes;
    /** @var string Do not automatically select * anymore but select only what is necessary */
    protected $explicitSelect = false;
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
    /** @var string Use SQL_CALC_FOUND_ROWS / FOUND_ROWS to count the number of records */
    protected $_use_found_rows = true;
    /** @var bool */
    protected $is_cms = false;
    /** @var string Identifier to use for changing positions in lists (can be omitted if positions cannot be changed) */
    protected $position_identifier;
    /** @var string|int */
    protected $position_group_identifier;
    /** @var bool Table records are not deleted but marked as deleted if set to true */
    protected $deleted = false;
    /**  @var bool Is a list filter set */
    protected $filter;
    /** @var bool */
    protected $noLink;
    /** @var bool|string|null */
    protected $specificConfirmDelete = null;
    /** @var bool */
    protected $colorOnBackground;
    /** @var bool If true, activates color on hover */
    protected $row_hover = true;
    /** @var string Action to perform : 'edit', 'view', 'add', ... */
    protected $action;
    /** @var string */
    protected $display;
    /** @var bool */
    protected $_includeContainer = true;
    /** @var array */
    protected $tab_modules_list = ['default_list' => [], 'slider_list' => []];
    /** @var string */
    protected $bo_theme;
    /** @var bool Redirect or not after a creation */
    protected $_redirect = true;
    /** @var ObjectModel|null Instantiation of the class associated with the AdminController */
    protected $object;
    /** @var int Current object ID */
    protected $id_object;
    /** @var array Current breadcrumb position as an array of tab names */
    protected $breadcrumbs;
    /** @var array */
    protected $list_natives_modules = [];
    /** @var array */
    protected $list_partners_modules = [];
    /** @var bool if logged employee has access to AdminImport */
    protected $can_import = false;
    /** @var array */
    protected $translationsTab = [];
    /** @var bool $isThirtybeesUp */
    public static $isThirtybeesUp = true;
    /** @var float */
    protected $timer_start;
    /** @var string */
    protected $bo_css;
    /** @var array */
    protected array $_conf = [];

    /**
     * If set to true, any exception throws in postProcess() phase will be converted to error message. Otherwise,
     * exceptions will cause error page response
     *
     * @var bool
     */
    protected $postProcessHandleExceptions = true;

    /**
     * AdminControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        global $timer_start;
        global $token;

        $this->timer_start = $timer_start;

        $messageCachePath = _PS_CACHE_DIR_.'/'.static::MESSAGE_CACHE_PATH
                            .'-'.Tools::getValue('token');
        if (is_readable($messageCachePath)) {
            include $messageCachePath;
            unlink($messageCachePath);
        }

        $this->controller_type = 'admin';
        $this->controller_name = get_class($this);
        if (strpos($this->controller_name, 'Controller')) {
            $this->controller_name = substr($this->controller_name, 0, -10);
        }
        parent::__construct();

        if ($this->multishop_context == -1) {
            $this->multishop_context = Shop::CONTEXT_ALL | Shop::CONTEXT_GROUP | Shop::CONTEXT_SHOP;
        }

        $defaultThemeName = 'default';

        if (defined('_PS_BO_DEFAULT_THEME_') && _PS_BO_DEFAULT_THEME_
            && @filemtime(_PS_BO_ALL_THEMES_DIR_._PS_BO_DEFAULT_THEME_.DIRECTORY_SEPARATOR.'template')
        ) {
            $defaultThemeName = _PS_BO_DEFAULT_THEME_;
        }

        $this->bo_theme = ((Validate::isLoadedObject($this->context->employee)
            && $this->context->employee->bo_theme) ? $this->context->employee->bo_theme : $defaultThemeName);

        if (!@filemtime(_PS_BO_ALL_THEMES_DIR_.$this->bo_theme.DIRECTORY_SEPARATOR.'template')) {
            $this->bo_theme = $defaultThemeName;
        }

        $this->bo_css = ((Validate::isLoadedObject($this->context->employee)
            && $this->context->employee->bo_css) ? $this->context->employee->bo_css : 'admin-theme.css');

        if (!@filemtime(_PS_BO_ALL_THEMES_DIR_.$this->bo_theme.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$this->bo_css)) {
            $this->bo_css = 'admin-theme.css';
        }

        $this->context->smarty->setTemplateDir(
            [
                _PS_BO_ALL_THEMES_DIR_.$this->bo_theme.DIRECTORY_SEPARATOR.'template',
                _PS_OVERRIDE_DIR_.'controllers'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'templates',
            ]
        );

        $this->id = Tab::getIdFromClassName($this->controller_name);
        $this->token = Tools::getAdminToken($this->controller_name.(int) $this->id.(int) $this->context->employee->id);

        $token = $this->token;

        $this->_conf = [
            1  => $this->l('Successful deletion'),
            2  => $this->l('The selection has been successfully deleted.'),
            3  => $this->l('Successful creation'),
            4  => $this->l('Successful update'),
            5  => $this->l('The status has been successfully updated.'),
            6  => $this->l('The settings have been successfully updated.'),
            7  => $this->l('The image was successfully deleted.'),
            8  => $this->l('The module was successfully downloaded.'),
            9  => $this->l('The thumbnails were successfully regenerated.'),
            10 => $this->l('The message was successfully sent to the customer.'),
            11 => $this->l('Comment successfully added'),
            12 => $this->l('Module(s) installed successfully.'),
            13 => $this->l('Module(s) uninstalled successfully.'),
            14 => $this->l('The translation was successfully copied.'),
            15 => $this->l('The translations have been successfully added.'),
            16 => $this->l('The module transplanted successfully to the hook.'),
            17 => $this->l('The module was successfully removed from the hook.'),
            18 => $this->l('Successful upload'),
            19 => $this->l('Duplication was completed successfully.'),
            20 => $this->l('The translation was added successfully, but the language has not been created.'),
            21 => $this->l('Module reset successfully.'),
            22 => $this->l('Module deleted successfully.'),
            23 => $this->l('Localization pack imported successfully.'),
            24 => $this->l('Localization pack imported successfully.'),
            25 => $this->l('The selected images have successfully been moved.'),
            26 => $this->l('Your cover image selection has been saved.'),
            27 => $this->l('The image\'s shop association has been modified.'),
            28 => $this->l('A zone has been assigned to the selection successfully.'),
            29 => $this->l('Successful upgrade'),
            30 => $this->l('A partial refund was successfully created.'),
            31 => $this->l('The discount was successfully generated.'),
            32 => $this->l('Successfully signed in'),
        ];

        if (!$this->identifier) {
            $this->identifier = 'id_'.$this->table;
        }
        if (!$this->_defaultOrderBy) {
            $this->_defaultOrderBy = $this->identifier;
        }
        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, $this->id);

        if (!Shop::isFeatureActive()) {
            $this->shopLinkType = '';
        }

        //$this->base_template_folder = _PS_BO_ALL_THEMES_DIR_.$this->bo_theme.'/template';
        $this->override_folder = Tools::toUnderscoreCase(substr($this->controller_name, 5)).'/';
        // Get the name of the folder containing the custom tpl files
        $this->tpl_folder = Tools::toUnderscoreCase(substr($this->controller_name, 5)).'/';

        $this->initShopContext();

        $this->context->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));

        $this->admin_webpath = str_ireplace(_PS_CORE_DIR_, '', _PS_ADMIN_DIR_);
        $this->admin_webpath = preg_replace('/^'.preg_quote(DIRECTORY_SEPARATOR, '/').'/', '', $this->admin_webpath);

        $this->can_import = $this->context->employee->hasAccess(AdminImportController::class, Profile::PERMISSION_VIEW);
        $this->context->smarty->assign('can_import', $this->can_import);
    }

    /**
     * Non-static method which uses AdminController::translate()
     *
     * @param string $string Term or expression in english
     * @param string|null $class Name of the class
     * @param bool $addslashes If set to true, the return value will pass through addslashes(). Otherwise, stripslashes().
     * @param bool $htmlentities If set to true(default), the return value will pass through htmlentities($string, ENT_QUOTES, 'utf-8')
     *
     * @return string The translation if available, or the english default text.
     */
    protected function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        if ($class === null || $class == 'AdminTab') {
            $class = substr(get_class($this), 0, -10);
        } elseif (strtolower(substr($class, -10)) == 'controller') {
            /* classname has changed, from AdminXXX to AdminXXXController, so we remove 10 characters and we keep same keys */
            $class = substr($class, 0, -10);
        }

        return Translate::getAdminTranslation($string, $class, $addslashes, $htmlentities);
    }

    /**
     * @throws PrestaShopException
     */
    public function initShopContext()
    {
        if (!$this->context->employee->isLoggedBack()) {
            return;
        }

        // Change shop context ?
        if (Shop::isFeatureActive() && Tools::getValue('setShopContext') !== false) {
            $this->context->cookie->shopContext = Tools::getValue('setShopContext');
            $url = parse_url($_SERVER['REQUEST_URI']);
            $query = (isset($url['query'])) ? $url['query'] : '';
            parse_str($query, $parseQuery);
            unset($parseQuery['setShopContext'], $parseQuery['conf']);
            $this->redirect_after = $url['path'].'?'.http_build_query($parseQuery, '', '&');
        } elseif (!Shop::isFeatureActive()) {
            $this->context->cookie->shopContext = 's-'.(int) Configuration::get('PS_SHOP_DEFAULT');
        } elseif (Shop::getTotalShops(false, null) < 2) {
            $this->context->cookie->shopContext = 's-'.(int) $this->context->employee->getDefaultShopID();
        }

        $idShop = '';
        Shop::setContext(Shop::CONTEXT_ALL);
        if ($this->context->cookie->shopContext) {
            $split = explode('-', $this->context->cookie->shopContext);
            if (count($split) == 2) {
                if ($split[0] == 'g') {
                    if ($this->context->employee->hasAuthOnShopGroup((int) $split[1])) {
                        Shop::setContext(Shop::CONTEXT_GROUP, (int) $split[1]);
                    } else {
                        $idShop = (int) $this->context->employee->getDefaultShopID();
                        Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
                    }
                } elseif (Shop::getShop($split[1]) && $this->context->employee->hasAuthOnShop($split[1])) {
                    $idShop = (int) $split[1];
                    Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
                } else {
                    $idShop = (int) $this->context->employee->getDefaultShopID();
                    Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
                }
            }
        }

        // Check multishop context and set right context if need
        if (!($this->multishop_context & Shop::getContext())) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP && !($this->multishop_context & Shop::CONTEXT_SHOP)) {
                Shop::setContext(Shop::CONTEXT_GROUP, Shop::getContextShopGroupID());
            }
            if (Shop::getContext() == Shop::CONTEXT_GROUP && !($this->multishop_context & Shop::CONTEXT_GROUP)) {
                Shop::setContext(Shop::CONTEXT_ALL);
            }
        }

        // Replace existing shop if necessary
        if (!$idShop) {
            $this->context->shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
        } elseif ($this->context->shop->id != $idShop) {
            $this->context->shop = new Shop((int) $idShop);
        }

        if ($this->context->shop->id_theme != $this->context->theme->id) {
            $this->context->theme = new Theme((int) $this->context->shop->id_theme);
        }

        // Replace current default country
        $this->context->country = new Country((int) Configuration::get('PS_COUNTRY_DEFAULT'));
    }

    /**
     * @return false|mixed
     *
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        try {
            if ($this->ajax) {
                // from ajax-tab.php
                $action = Tools::getValue('action');
                // no need to use displayConf() here
                if (!empty($action) && method_exists($this, 'ajaxProcess'.Tools::toCamelCase($action))) {
                    Hook::triggerEvent('actionAdmin'.ucfirst($action).'Before', ['controller' => $this]);
                    Hook::triggerEvent('action'.get_class($this).ucfirst($action).'Before', ['controller' => $this]);

                    $return = $this->{'ajaxProcess'.Tools::toCamelCase($action)}();

                    Hook::triggerEvent('actionAdmin'.ucfirst($action).'After', ['controller' => $this, 'return' => $return]);
                    Hook::triggerEvent('action'.get_class($this).ucfirst($action).'After', ['controller' => $this, 'return' => $return]);

                    return $return;
                } elseif (!empty($action) && $this->controller_name == 'AdminModules' && Tools::getIsset('configure')) {
                    $moduleObj = Module::getInstanceByName(Tools::getValue('configure'));
                    if (Validate::isLoadedObject($moduleObj) && method_exists($moduleObj, 'ajaxProcess'.$action)) {
                        return $moduleObj->{'ajaxProcess'.$action}();
                    }
                } elseif (method_exists($this, 'ajaxProcess')) {
                    return $this->ajaxProcess();
                }
            } else {
                // Process list filtering
                if ($this->filter && $this->action != 'reset_filters') {
                    $this->processFilter();
                }

                if (isset($_POST) && count($_POST) && Tools::getIntValue('submitFilter'.$this->list_id) || Tools::isSubmit('submitReset'.$this->list_id)) {
                    $this->setRedirectAfter(static::$currentIndex.'&token='.$this->token.(Tools::isSubmit('submitFilter'.$this->list_id) ? '&submitFilter'.$this->list_id.'='.Tools::getIntValue('submitFilter'.$this->list_id) : '').(isset($_GET['id_'.$this->list_id]) ? '&id_'.$this->list_id.'='.(int) $_GET['id_'.$this->list_id] : ''));

                    if (!empty(Tools::getValue('id_'.$this->list_id.'_category'))) {
                        $this->setRedirectAfter($this->redirect_after.'&id_'.$this->list_id.'_category='.Tools::getValue('id_'.$this->list_id.'_category'));
                    }
                }

                // If the method named after the action exists, call "before" hooks, then call action method, then call "after" hooks
                if (!empty($this->action) && method_exists($this, 'process'.ucfirst(Tools::toCamelCase($this->action)))) {
                    // Hook before action
                    Hook::triggerEvent('actionAdmin'.ucfirst($this->action).'Before', ['controller' => $this]);
                    Hook::triggerEvent('action'.get_class($this).ucfirst($this->action).'Before', ['controller' => $this]);
                    // Call process
                    $return = $this->{'process'.Tools::toCamelCase($this->action)}();
                    // Hook After Action
                    Hook::triggerEvent('actionAdmin'.ucfirst($this->action).'After', ['controller' => $this, 'return' => $return]);
                    Hook::triggerEvent('action'.get_class($this).ucfirst($this->action).'After', ['controller' => $this, 'return' => $return]);

                    return $return;
                }
            }
        } catch (Throwable $e) {
            if ($this->postProcessHandleExceptions) {
                static::getErrorHandler()->logFatalError(ErrorUtils::describeException($e));
                $this->errors[] = $e->getMessage();
            } else {
                if ($e instanceof PrestaShopException) {
                    throw $e;
                } else {
                    throw new PrestaShopException($e->getMessage(), 0, $e);
                }
            }
        }

        return false;
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function processFilter()
    {
        Hook::triggerEvent('action'.$this->controller_name.'ListingFieldsModifier', ['fields' => &$this->fields_list]);

        $this->ensureListIdDefinition();

        $prefix = $this->getCookieFilterPrefix();

        // Reset current filter, if forced filter was applied
        if (Tools::isSubmit('submitFilterForced')) {
            $this->processResetFilters();
            $_POST['submitFilter'.$this->list_id] = true;
        }

        if (isset($this->list_id)) {
            foreach ($_POST as $key => $value) {
                if ($value === '') {
                    unset($this->context->cookie->{$prefix.$key});
                } elseif (stripos($key, $this->list_id.'Filter_') === 0) {
                    $this->context->cookie->{$prefix.$key} = !is_array($value) ? $value : json_encode($value);
                } elseif (stripos($key, 'submitFilter') === 0) {
                    $this->context->cookie->$key = !is_array($value) ? $value : json_encode($value);
                }
            }

            foreach ($_GET as $key => $value) {

                // Handle forced filtering parameter by url
                if (stripos($key, 'list_idFilter_') === 0) {
                    $key = preg_replace('/list_id/', $this->list_id, $key, 1);
                }

                if (stripos($key, $this->list_id.'Filter_') === 0) {
                    $this->context->cookie->{$prefix.$key} = !is_array($value) ? $value : json_encode($value);
                } elseif (stripos($key, 'submitFilter') === 0) {
                    $this->context->cookie->$key = !is_array($value) ? $value : json_encode($value);
                }
                if (stripos($key, $this->list_id.'Orderby') === 0 && Validate::isOrderBy($value)) {
                    if ($value === '' || $value == $this->_defaultOrderBy) {
                        unset($this->context->cookie->{$prefix.$key});
                    } else {
                        $this->context->cookie->{$prefix.$key} = $value;
                    }
                } elseif (stripos($key, $this->list_id.'Orderway') === 0 && Validate::isOrderWay($value)) {
                    if ($value === '' || $value == $this->_defaultOrderWay) {
                        unset($this->context->cookie->{$prefix.$key});
                    } else {
                        $this->context->cookie->{$prefix.$key} = $value;
                    }
                }
            }
        }

        $filters = $this->context->cookie->getFamily($prefix.$this->list_id.'Filter_');
        $definition = false;
        if (isset($this->className) && $this->className) {
            $definition = ObjectModel::getDefinition($this->className);
        }

        foreach ($filters as $key => $value) {
            /* Extracting filters from $_POST on key filter_ */
            if ($value != null && !strncmp($key, $prefix.$this->list_id.'Filter_', 7 + mb_strlen($prefix.$this->list_id))) {
                $key = mb_substr($key, 7 + mb_strlen($prefix.$this->list_id));
                /* Table alias could be specified using a ! eg. alias!field */
                $tmpTab = explode('!', $key);
                $filter = count($tmpTab) > 1 ? $tmpTab[1] : $tmpTab[0];

                if ($field = $this->filterToField($key, $filter)) {
                    $type = (array_key_exists('filter_type', $field) ? $field['filter_type'] : (array_key_exists('type', $field) ? $field['type'] : false));
                    if (($type == 'date' || $type == 'datetime') && is_string($value)) {
                        $value = json_decode($value, true);
                    }
                    $key = isset($tmpTab[1]) ? $tmpTab[0].'.`'.$tmpTab[1].'`' : '`'.$tmpTab[0].'`';

                    // Assignment by reference
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
                                $this->errors[] = Tools::displayError('The \'From\' date format is invalid (YYYY-MM-DD)');
                            } else {
                                $sqlFilter .= ' AND '.pSQL($key).' >= \''.pSQL(Tools::dateFrom($value[0])).'\'';
                            }
                        }

                        if (isset($value[1]) && !empty($value[1])) {
                            if (!Validate::isDate($value[1])) {
                                $this->errors[] = Tools::displayError('The \'To\' date format is invalid (YYYY-MM-DD)');
                            } else {
                                $sqlFilter .= ' AND '.pSQL($key).' <= \''.pSQL(Tools::dateTo($value[1])).'\'';
                            }
                        }
                    } else {
                        $sqlFilter .= ' AND ';
                        $checkKey = ($key == $this->identifier || $key == '`'.$this->identifier.'`');
                        $alias = ($definition && !empty($definition['fields'][$filter]['shop'])) ? 'sa' : 'a';

                        if ($type == 'int' || $type == 'bool') {
                            $sqlFilter .= (($checkKey || $key == '`active`') ? $alias.'.' : '').pSQL($key).' = '.(int) $value.' ';
                        } elseif ($type == 'decimal' || $type == 'price') {
                            $value = Tools::parseNumber($value);
                            $sqlFilter .= ($checkKey ? $alias.'.' : '').pSQL($key).' = '. $value.' ';
                        } elseif ($type == 'select') {
                            $sqlFilter .= ($checkKey ? $alias.'.' : '').pSQL($key).' = \''.pSQL($value).'\' ';
                        } else {
                            $sqlFilter .= ($checkKey ? $alias.'.' : '').pSQL($key).' LIKE \'%'.pSQL(trim($value)).'%\' ';
                        }
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    protected function ensureListIdDefinition()
    {
        if (!isset($this->list_id)) {
            $this->list_id = $this->table;
        }
    }

    /**
     * Return the type of authorization on permissions page and option.
     *
     * @return int(integer)
     */
    public function authorizationLevel()
    {
        if ($this->hasDeletePermission()) {
            return AdminController::LEVEL_DELETE;
        } elseif ($this->hasAddPermission()) {
            return AdminController::LEVEL_ADD;
        } elseif ($this->hasEditPermission()) {
            return AdminController::LEVEL_EDIT;
        } elseif ($this->hasViewPermission()) {
            return AdminController::LEVEL_VIEW;
        } else {
            return 0;
        }
    }

    /**
     * Set the filters used for the list display
     *
     * @return string
     */
    protected function getCookieFilterPrefix()
    {
        return str_replace(['admin', 'controller'], '', mb_strtolower(get_class($this)));
    }

    /**
     * @param string $key
     * @param string $filter
     *
     * @return array|false
     */
    protected function filterToField($key, $filter)
    {
        if (!isset($this->fields_list)) {
            return false;
        }

        foreach ($this->fields_list as $field) {
            if (array_key_exists('filter_key', $field) && $field['filter_key'] == $key) {
                return $field;
            }
        }
        if (array_key_exists($filter, $this->fields_list)) {
            return $this->fields_list[$filter];
        }

        return false;
    }

    /**
     * Object Delete images
     *
     * @return ObjectModel|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processDeleteImage()
    {
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            if (($object->deleteImage())) {
                $redirect = static::$currentIndex.'&update'.$this->table.'&'.$this->identifier.'='.Tools::getValue($this->identifier).'&conf=7&token='.$this->token;
                if (!$this->ajax) {
                    $this->redirect_after = $redirect;
                } else {
                    $this->content = 'ok';
                }
            }
        }
        $this->errors[] = Tools::displayError('An error occurred while attempting to delete the image. (cannot load object).');

        return $object;
    }

    /**
     * Load class object using identifier in $_GET (if possible)
     * otherwise return an empty object, or die
     *
     * @param bool $opt Return an empty object if load fail
     *
     * @return ObjectModel|bool
     */
    protected function loadObject($opt = false)
    {
        // return object that was already instantiated
        if ($this->object) {
            return $this->object;
        }

        if (empty($this->className)) {
            return true;
        }

        $id = Tools::getIntValue($this->identifier);
        if ($id && Validate::isUnsignedId($id)) {
            $this->object = new $this->className($id);
            if (Validate::isLoadedObject($this->object)) {
                return $this->object;
            }
            // throw exception
            $this->errors[] = Tools::displayError('The object cannot be loaded (or found)');

            return false;
        } elseif ($opt) {
            $this->object = new $this->className();
            return $this->object;
        } else {
            $this->errors[] = Tools::displayError('The object cannot be loaded (the identifier is missing or invalid)');

            return false;
        }
    }

    /**
     * @param string $textDelimiter
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function processExport($textDelimiter = '"')
    {
        // clean buffer
        if (ob_get_level() && ob_get_length() > 0) {
            ob_clean();
        }
        $this->getList($this->context->language->id, null, null, 0, false);
        if (!count($this->_list)) {
            return;
        }

        header('Content-type: text/csv');
        header('Content-Type: application/force-download; charset=UTF-8');
        header('Cache-Control: no-store, no-cache');
        header('Content-disposition: attachment; filename="'.$this->table.'_'.date('Y-m-d_His').'.csv"');

        $headers = [];
        foreach ($this->fields_list as $key => $datas) {
            if ($datas['title'] === 'PDF') {
                unset($this->fields_list[$key]);
            } else {
                if ($datas['title'] === 'ID') {
                    $headers[] = strtolower(Tools::htmlentitiesDecodeUTF8($datas['title']));
                } else {
                    $headers[] = Tools::htmlentitiesDecodeUTF8($datas['title']);
                }
            }
        }
        $content = [];
        foreach ($this->_list as $i => $row) {
            $content[$i] = [];
//            $pathToImage = false;
            foreach ($this->fields_list as $key => $params) {
                $fieldValue = isset($row[$key]) ? Tools::htmlentitiesDecodeUTF8(Tools::nl2br($row[$key])) : '';
                if ($key == 'image') {
                    if ($params['image'] != 'p') {
                        $pathToImage = Tools::getShopDomain(true)._PS_IMG_.$params['image'].'/'.$row['id_'.$this->table].(isset($row['id_image']) ? '-'.(int) $row['id_image'] : '').'.'.$this->imageType;
                    } else {
                        $pathToImage = Tools::getShopDomain(true)._PS_IMG_.$params['image'].'/'.Image::getImgFolderStatic($row['id_image']).(int) $row['id_image'].'.'.$this->imageType;
                    }
                    if ($pathToImage) {
                        $fieldValue = $pathToImage;
                    }
                }
                if (isset($params['callback'])) {
                    $callbackObj = (isset($params['callback_object'])) ? $params['callback_object'] : $this->context->controller;
                    if (!preg_match('/<([a-z]+)([^<]+)*(?:>(.*)<\/\1>|\s+\/>)/ism', call_user_func_array([$callbackObj, $params['callback']], [$fieldValue, $row]))) {
                        $fieldValue = call_user_func_array([$callbackObj, $params['callback']], [$fieldValue, $row]);
                    }
                }
                $content[$i][] = $fieldValue;
            }
        }

        $fieldDelimiter = (Configuration::get('TB_EXPORT_FIELD_DELIMITER') ? Configuration::get('TB_EXPORT_FIELD_DELIMITER') : ',');
        $this->context->smarty->assign(
            [
                'export_precontent' => "",
                'export_headers'    => $headers,
                'export_content'    => $content,
                'text_delimiter'    => $textDelimiter,
                'field_delimiter'   => $fieldDelimiter,
            ]
        );

        $this->layout = 'layout-export.tpl';
    }

    /**
     * Get the current objects' list form the database
     *
     * @param int $idLang Language used for display
     * @param string|null $orderBy ORDER BY clause
     * @param string|null $orderWay Order way (ASC, DESC)
     * @param int $start Offset in LIMIT clause
     * @param int|false|null $limit Row count in LIMIT clause
     * @param int|bool $idLangShop
     *
     * @throws PrestaShopException
     */
    public function getList(
        $idLang,
        $orderBy = null,
        $orderWay = null,
        $start = 0,
        $limit = null,
        $idLangShop = false
    ) {
        $this->dispatchFieldsListingModifierEvent();

        $this->ensureListIdDefinition();

        /* Manage default params values */
        if ($limit === false) {
            $useLimit = false;
        } else {
            $useLimit = true;
            $limit = HelperList::resolvePagination($this->list_id, $this->context->cookie, $this->_pagination, $this->_default_pagination);
            if ($limit !== $this->_default_pagination) {
                $this->context->cookie->{$this->list_id.'_pagination'} = $limit;
            } else {
                unset($this->context->cookie->{$this->list_id.'_pagination'});
            }
        }

        if (!Validate::isTableOrIdentifier($this->table)) {
            throw new PrestaShopException(sprintf('Table name %s is invalid:', $this->table));
        }

        $orderBy = $this->resolveOrderBy($orderBy);
        $orderWay = $this->resolveOrderWay($orderWay);



        /* Check params validity */
        if (!Validate::isOrderBy($orderBy) || !Validate::isOrderWay($orderWay)) {
            throw new PrestaShopException(sprintf(Tools::displayError('Invalid ordering parameters: orderBy=[%s] orderWay=[%s]'), $orderBy, $orderWay));
        }
        if (!is_numeric($start) || !is_numeric($limit) || !Validate::isUnsignedId($idLang)) {
            throw new PrestaShopException(sprintf(Tools::displayError('getList params is not valid: start=[%s] limit=[%s] idLang=[%s]'), $start, $limit, $idLang));
        }

        if (!isset($this->fields_list[$orderBy]['order_key']) && isset($this->fields_list[$orderBy]['filter_key'])) {
            $this->fields_list[$orderBy]['order_key'] = $this->fields_list[$orderBy]['filter_key'];
        }

        if (isset($this->fields_list[$orderBy]['order_key'])) {
            $orderBy = $this->fields_list[$orderBy]['order_key'];
        }

        /* Determine offset from current page */
        $start = 0;
        if (Tools::getIntValue('submitFilter'.$this->list_id)) {
            $start = (Tools::getIntValue('submitFilter'.$this->list_id) - 1) * $limit;
        } elseif (empty($start) && isset($this->context->cookie->{$this->list_id.'_start'}) && Tools::isSubmit('export'.$this->table)) {
            $start = $this->context->cookie->{$this->list_id.'_start'};
        }

        // Either save or reset the offset in the cookie
        if ($start) {
            $this->context->cookie->{$this->list_id.'_start'} = $start;
        } elseif (isset($this->context->cookie->{$this->list_id.'_start'})) {
            unset($this->context->cookie->{$this->list_id.'_start'});
        }

        $this->_orderBy = $orderBy;

        if (preg_match('/[.!]/', $orderBy)) {
            $orderBySplit = preg_split('/[.!]/', $orderBy);
            $orderBy = bqSQL($orderBySplit[0]).'.`'.bqSQL($orderBySplit[1]).'`';
        } elseif ($orderBy) {
            $orderBy = '`'.bqSQL($orderBy).'`';
        }

        $this->_orderWay = mb_strtoupper($orderWay);

        /* SQL table : orders, but class name is Order */
        $sqlTable = $this->table == 'order' ? 'orders' : $this->table;

        // Add SQL shop restriction
        $selectShop = $joinShop = $whereShop = '';
        if ($this->shopLinkType) {
            $selectShop = ', shop.name as shop_name ';
            $joinShop = ' LEFT JOIN '._DB_PREFIX_.$this->shopLinkType.' shop
							ON a.id_'.$this->shopLinkType.' = shop.id_'.$this->shopLinkType;
            $whereShop = Shop::addSqlRestriction($this->shopShareDatas, 'a');
        }

        if ($this->multishop_context && Shop::isTableAssociated($this->table) && !empty($this->className)) {
            if (Shop::getContext() != Shop::CONTEXT_ALL || !$this->context->employee->isSuperAdmin()) {
                $testJoin = !preg_match('#`?'.preg_quote(_DB_PREFIX_.$this->table.'_shop').'`? *sa#', $this->_join ?? '');
                if (Shop::isFeatureActive() && $testJoin && Shop::isTableAssociated($this->table)) {
                    $this->_where .= ' AND EXISTS (
						SELECT 1
						FROM `'._DB_PREFIX_.$this->table.'_shop` sa
						WHERE a.'.$this->identifier.' = sa.'.$this->identifier.' AND sa.id_shop IN ('.implode(', ', Shop::getContextListShopID()).')
					)';
                }
            }
        }

        /* Query in order to get results with all fields */
        $langJoin = '';
        if ($this->lang) {
            $langJoin = 'LEFT JOIN `'._DB_PREFIX_.$this->table.'_lang` b ON (b.`'.$this->identifier.'` = a.`'.$this->identifier.'` AND b.`id_lang` = '.(int) $idLang;
            if ($idLangShop) {
                if (!Shop::isFeatureActive()) {
                    $langJoin .= ' AND b.`id_shop` = '.(int) Configuration::get('PS_SHOP_DEFAULT');
                } elseif (Shop::getContext() == Shop::CONTEXT_SHOP) {
                    $langJoin .= ' AND b.`id_shop` = '.(int) $idLangShop;
                } else {
                    $langJoin .= ' AND b.`id_shop` = a.id_shop_default';
                }
            }
            $langJoin .= ')';
        }

        $havingClause = '';
        if (isset($this->_filterHaving) || isset($this->_having)) {
            $havingClause = ' HAVING ';
            if (isset($this->_filterHaving)) {
                $havingClause .= ltrim($this->_filterHaving, ' AND ');
            }
            if (isset($this->_having)) {
                $havingClause .= $this->_having.' ';
            }
        }

        do {
            $this->_listsql = '';

            if ($this->explicitSelect) {
                foreach ($this->fields_list as $key => $arrayValue) {
                    // Add it only if it is not already in $this->_select
                    if (isset($this->_select) && preg_match('/[\s]`?'.preg_quote($key, '/').'`?\s*,/', $this->_select)) {
                        continue;
                    }

                    if (isset($arrayValue['filter_key'])) {
                        $this->_listsql .= str_replace('!', '.`', $arrayValue['filter_key']).'` AS `'.$key.'`, ';
                    } elseif ($key == 'id_'.$this->table) {
                        $this->_listsql .= 'a.`'.bqSQL($key).'`, ';
                    } elseif ($key != 'image' && !preg_match('/'.preg_quote($key, '/').'/i', $this->_select ?? '')) {
                        $this->_listsql .= '`'.bqSQL($key).'`, ';
                    }
                }
                $this->_listsql = rtrim(trim($this->_listsql), ',');
            } else {
                $this->_listsql .= ($this->lang ? 'b.*,' : '').' a.*';
            }

            $this->_listsql .= '
			'.(isset($this->_select) ? ', '.rtrim($this->_select, ', ') : '').$selectShop;

            $sqlFrom = '
			FROM `'._DB_PREFIX_.$sqlTable.'` a ';
            $sqlJoin = '
			'.$langJoin.'
			'.(isset($this->_join) ? $this->_join.' ' : '').'
			'.$joinShop;
            $sqlWhere = ' '.(isset($this->_where) ? $this->_where.' ' : '').($this->deleted ? 'AND a.`deleted` = 0 ' : '').
                (isset($this->_filter) ? $this->_filter : '').$whereShop.'
			'.(isset($this->_group) ? $this->_group.' ' : '').'
			'.$havingClause;
            $sqlOrderBy = ' ORDER BY '.((str_replace('`', '', $orderBy) == $this->identifier) ? 'a.' : '').$orderBy.' '.pSQL($orderWay).
                ($this->_tmpTableFilter ? ') tmpTable WHERE 1'.$this->_tmpTableFilter : '');
            $sqlLimit = ' '.(($useLimit === true) ? ' LIMIT '.(int) $start.', '.(int) $limit : '');

            if ($this->_use_found_rows || isset($this->_filterHaving) || isset($this->_having)) {
                $this->_listsql = 'SELECT SQL_CALC_FOUND_ROWS
								'.($this->_tmpTableFilter ? ' * FROM (SELECT ' : '').$this->_listsql.$sqlFrom.$sqlJoin.' WHERE 1 '.$sqlWhere.
                    $sqlOrderBy.$sqlLimit;
                $listCount = 'SELECT FOUND_ROWS() AS `'._DB_PREFIX_.$this->table.'`';
            } else {
                $this->_listsql = 'SELECT
								'.($this->_tmpTableFilter ? ' * FROM (SELECT ' : '').$this->_listsql.$sqlFrom.$sqlJoin.' WHERE 1 '.$sqlWhere.
                    $sqlOrderBy.$sqlLimit;
                if ($this->_group) {
                    $listCount = 'SELECT COUNT(*) AS `'._DB_PREFIX_.$this->table.'` FROM (SELECT 1 '.$sqlFrom.$sqlJoin.' WHERE 1 '.$sqlWhere.') AS `inner`';
                } else {
                    $listCount = 'SELECT COUNT(*) AS `'._DB_PREFIX_.$this->table.'` '.$sqlFrom.$sqlJoin.' WHERE 1 '.$sqlWhere;
                }
            }

            $conn = Db::readOnly();
            $this->_list = $conn->getArray($this->_listsql);
            $this->_listTotal = $conn->getValue($listCount);

            if ($useLimit === true) {
                $start = (int) $start - (int) $limit;
                if ($start < 0) {
                    break;
                }
            } else {
                break;
            }
        } while (empty($this->_list));

        Hook::triggerEvent(
            'action'.$this->controller_name.'ListingResultsModifier', [
                'list'       => &$this->_list,
                'list_total' => &$this->_listTotal,
            ]
        );
    }

    /**
     * @throws PrestaShopException
     */
    protected function dispatchFieldsListingModifierEvent()
    {
        Hook::triggerEvent(
            'action'.$this->controller_name.'ListingFieldsModifier', [
                'select'    => &$this->_select,
                'join'      => &$this->_join,
                'where'     => &$this->_where,
                'group_by'  => &$this->_group,
                'order_by'  => &$this->_orderBy,
                'order_way' => &$this->_orderWay,
                'fields'    => &$this->fields_list,
            ]
        );
    }

    /**
     * Object Delete
     *
     * @return ObjectModel|false
     * @throws PrestaShopException
     */
    public function processDelete()
    {
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            $res = true;
            //check if some ids are in list_skip_actions and forbid deletion
            if (array_key_exists('delete', $this->list_skip_actions) && in_array($object->id, $this->list_skip_actions['delete'])) {
                $this->errors[] = Tools::displayError('You cannot delete this item.');
            } else {
                if ($this->deleted) {
                    if (!empty($this->fieldImageSettings)) {
                        $res = $object->deleteImage();
                    }

                    if (!$res) {
                        $this->errors[] = Tools::displayError('Unable to delete associated images.');
                    }

                    $object->deleted = 1;
                    if ($res = $object->update()) {
                        $this->redirect_after = static::$currentIndex.'&conf=1&token='.$this->token;
                    }
                } elseif ($res = $object->delete()) {
                    $this->redirect_after = static::$currentIndex.'&conf=1&token='.$this->token;
                } else {
                    $this->errors[] = Tools::displayError('An error occurred during deletion.');
                }
                if ($res) {
                    Logger::addLog(sprintf($this->l('%s deletion', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int) $this->object->id, true, (int) $this->context->employee->id);
                }
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while deleting the object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }

        return $object;
    }

    /**
     * Call the right method for creating or updating object
     *
     * @return ObjectModel|false
     *
     * @throws PrestaShopException
     */
    public function processSave()
    {
        if ($this->id_object) {
            $this->loadObject();
            return $this->processUpdate();
        } else {
            return $this->processAdd();
        }
    }

    /**
     * Object update
     *
     * @return ObjectModel|false
     * @throws PrestaShopException
     */
    public function processUpdate()
    {
        /* Checking fields validity */
        $this->validateRules();
        if (empty($this->errors)) {
            $id = Tools::getIntValue($this->identifier);

            /* Object update */
            if ($id) {
                /** @var ObjectModel $object */
                $object = new $this->className($id);
                if (Validate::isLoadedObject($object)) {
                    /* Specific to objects which must not be deleted */
                    if ($this->deleted && $this->beforeDelete($object)) {
                        // Create new one with old objet values
                        /** @var ObjectModel $objectNew */
                        $objectNew = $object->duplicateObject();
                        if (Validate::isLoadedObject($objectNew)) {
                            // Update old object to deleted
                            $object->deleted = 1;
                            $object->update();

                            // Update new object with post values
                            $this->copyFromPost($objectNew, $this->table);
                            $result = $objectNew->update();
                            if (Validate::isLoadedObject($objectNew)) {
                                $this->afterDelete($objectNew, $object->id);
                            }
                        }
                    } else {
                        $this->copyFromPost($object, $this->table);
                        $result = $object->update();
                        $this->afterUpdate($object);
                    }

                    if ($object->id) {
                        $this->updateAssoShop($object->id);
                    }

                    if (!isset($result) || !$result) {
                        $this->errors[] = Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.'</b> ('.Db::getInstance()->getMsgError().')';
                    } elseif ($this->postImage($object->id) && !count($this->errors) && $this->_redirect) {
                        $parentId = Tools::getIntValue('id_parent', 1);
                        // Specific back redirect
                        if ($back = Tools::getValue('back')) {
                            $this->redirect_after = urldecode($back).'&conf=4';
                        }
                        // Specific scene feature
                        // @todo change stay_here submit name (not clear for redirect to scene ... )
                        if (Tools::getValue('stay_here') == 'on' || Tools::getValue('stay_here') == 'true' || Tools::getValue('stay_here') == '1') {
                            $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'='.$object->id.'&conf=4&updatescene&token='.$this->token;
                        }
                        // Save and stay on same form
                        // @todo on the to following if, we may prefer to avoid override redirect_after previous value
                        if (Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
                            $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'='.$object->id.'&conf=4&update'.$this->table.'&token='.$this->token;
                        }
                        // Save and back to parent
                        if (Tools::isSubmit('submitAdd'.$this->table.'AndBackToParent')) {
                            $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'='.$parentId.'&conf=4&token='.$this->token;
                        }

                        // Default behavior (save and back)
                        if (empty($this->redirect_after) && $this->redirect_after !== false) {
                            $this->redirect_after = static::$currentIndex.($parentId ? '&'.$this->identifier.'='.$object->id : '').'&conf=4&token='.$this->token;
                        }
                    }
                    Logger::addLog(sprintf($this->l('%s modification', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int) $object->id, true, (int) $this->context->employee->id);
                } else {
                    $this->errors[] = Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
                }
            }
        }
        $this->errors = array_unique($this->errors);
        if (!empty($this->errors)) {
            // if we have errors, we stay on the form instead of going back to the list
            $this->display = 'edit';

            return false;
        }

        if (isset($object)) {
            return $object;
        }

        return false;
    }

    /**
     * Manage page display (form, list...)
     *
     * @param string|bool $className Allow to validate a different class than the current one
     *
     * @throws PrestaShopException
     */
    public function validateRules($className = false)
    {
        if (!$className) {
            $className = $this->className;
        }

        /** @var ObjectModel $object */
        $object = new $className();

        if (method_exists($this, 'getValidationRules')) {
            $definition = $this->getValidationRules();
        } else {
            $definition = ObjectModel::getDefinition($className);
        }

        $defaultLanguage = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $languages = Language::getLanguages(false);

        foreach ($definition['fields'] as $field => $def) {
            $skip = [];
            if (in_array($field, ['passwd', 'no-picture'])) {
                $skip = ['required'];
            }

            if (isset($def['lang']) && $def['lang']) {
                if (isset($def['required']) && $def['required']) {
                    $value = Tools::getValue($field.'_'.$defaultLanguage->id);
                    if ($value === false || $value === '') {
                        $this->errors[$field.'_'.$defaultLanguage->id] = sprintf(
                            Tools::displayError('The field %1$s is required at least in %2$s.'),
                            $object->displayFieldName($field, $className),
                            $defaultLanguage->name
                        );
                    }
                }

                foreach ($languages as $language) {
                    $value = Tools::getValue($field.'_'.$language['id_lang']);
                    if (!empty($value)) {
                        if (($error = $object->validateField($field, $value, $language['id_lang'], $skip, true)) !== true) {
                            $this->errors[$field.'_'.$language['id_lang']] = $error;
                        }
                    }
                }
            } elseif (($error = $object->validateField($field, Tools::getValue($field), null, $skip, true)) !== true) {
                $this->errors[$field] = $error;
            }
        }

        /* Overload this method for custom checking */
        $this->_childValidation();
    }

    /**
     * Overload this method for custom checking
     *
     * @return void
     */
    protected function _childValidation()
    {
    }

    /**
     * Called before deletion
     *
     * @param ObjectModel $object Object
     *
     * @return bool
     */
    protected function beforeDelete($object)
    {
        return false;
    }

    /**
     * Copy data values from $_POST to object
     *
     * @param ObjectModel &$object Object
     * @param string $table Object table
     *
     * @throws PrestaShopException
     */
    protected function copyFromPost(&$object, $table)
    {
        /* Classical fields */
        foreach ($_POST as $key => $value) {
            if (property_exists($object, $key) && $key != 'id_'.$table) {
                /* Do not take care of password field if empty */
                if ($key == 'passwd' && Tools::getValue('id_'.$table) && empty($value)) {
                    continue;
                }
                /* Automatically hash password */
                if ($key == 'passwd' && !empty($value)) {
                    $value = Tools::hash($value);
                }
                if ($key === 'email') {
                    if (mb_detect_encoding($value, 'UTF-8', true) && mb_strpos($value, '@') > -1) {
                        // Convert to IDN
                        list ($local, $domain) = explode('@', $value, 2);
                        $domain = Tools::utf8ToIdn($domain);
                        $value = "$local@$domain";
                    }
                }
                $object->{$key} = $value;
            }
        }

        /* Multilingual fields */
        $classVars = get_class_vars(get_class($object));
        $fields = [];
        if (isset($classVars['definition']['fields'])) {
            $fields = $classVars['definition']['fields'];
        }

        foreach ($fields as $field => $params) {
            if (array_key_exists('lang', $params) && $params['lang']) {
                foreach (Language::getIDs(false) as $idLang) {
                    if (Tools::isSubmit($field.'_'.(int) $idLang)) {
                        if (!isset($object->{$field}) || !is_array($object->{$field})) {
                            $object->{$field} = [];
                        }
                        $object->{$field}[(int) $idLang] = Tools::getValue($field.'_'.(int) $idLang);
                    }
                }
            }
        }
    }

    /**
     * Called before deletion
     *
     * @param ObjectModel $object Object
     * @param int $oldId
     */
    protected function afterDelete($object, $oldId)
    {
    }

    /**
     * @param ObjectModel $object
     */
    protected function afterUpdate($object)
    {
    }

    /**
     * Update the associations of shops
     *
     * @param int $idObject
     *
     * @return bool|void
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    protected function updateAssoShop($idObject)
    {
        if (!Shop::isFeatureActive()) {
            return;
        }

        if (!Shop::isTableAssociated($this->table)) {
            return;
        }

        $assosData = $this->getSelectedAssoShop($this->table);

        // Get list of shop id we want to exclude from asso deletion
        $excludeIds = $assosData;
        $conn = Db::getInstance();
        foreach ($conn->getArray('SELECT id_shop FROM '._DB_PREFIX_.'shop') as $row) {
            if (!$this->context->employee->hasAuthOnShop($row['id_shop'])) {
                $excludeIds[] = $row['id_shop'];
            }
        }
        $conn->delete($this->table.'_shop', '`'.bqSQL($this->identifier).'` = '.(int) $idObject.($excludeIds ? ' AND id_shop NOT IN ('.implode(', ', array_map('intval', $excludeIds)).')' : ''));

        $insert = [];
        foreach ($assosData as $idShop) {
            $insert[] = [
                $this->identifier => (int) $idObject,
                'id_shop'         => (int) $idShop,
            ];
        }

        return $conn->insert($this->table.'_shop', $insert, false, true, Db::INSERT_IGNORE);
    }

    /**
     * Returns an array with selected shops and type (group or boutique shop)
     *
     * @param string $table
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getSelectedAssoShop($table)
    {
        if (!Shop::isFeatureActive() || !Shop::isTableAssociated($table)) {
            return [];
        }

        $shops = Shop::getShops(true, null, true);
        if (count($shops) == 1 && isset($shops[0])) {
            return [$shops[0], 'shop'];
        }

        $assos = [];
        if (Tools::isSubmit('checkBoxShopAsso_'.$table)) {
            foreach (Tools::getArrayValue('checkBoxShopAsso_'.$table) as $idShop => $value) {
                $assos[] = (int) $idShop;
            }
        } elseif (Shop::getTotalShops(false) == 1) {
            // if we do not have the checkBox multishop, we can have an admin with only one shop and being in multishop
            $assos[] = (int) Shop::getContextShopID();
        }

        return $assos;
    }

    /**
     * Overload this method for custom checking
     *
     * @param int $id Object id used for deleting images
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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

        return !count($this->errors) ? true : false;
    }

    /**
     * @param int $id
     * @param string $name
     * @param string $dir
     * @param string|bool $ext
     * @param int|null $width
     * @param int|null $height
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
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
            $maxSize = isset($this->max_image_size) ? $this->max_image_size : 0;
            if ($error = ImageManager::validateUpload($_FILES[$name], Tools::getMaxUploadSize($maxSize))) {
                $this->errors[] = $error;
            }

            $tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS');
            if (!$tmpName) {
                return false;
            }

            if (!move_uploaded_file($_FILES[$name]['tmp_name'], $tmpName)) {
                return false;
            }

            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!ImageManager::checkImageMemoryLimit($tmpName)) {
                $this->errors[] = Tools::displayError('Due to memory limit restrictions, this image cannot be loaded. Please increase your memory_limit value via your server\'s configuration settings. ');
            }

            // Copy new image
            if (empty($this->errors) && !ImageManager::resize($tmpName, _PS_IMG_DIR_.$dir.$id.'.'.$this->imageType, (int) $width, (int) $height, ($ext ? $ext : $this->imageType))) {
                $this->errors[] = Tools::displayError('An error occurred while uploading the image.');
            }

            if (count($this->errors)) {
                return false;
            }
            if ($this->afterImageUpload()) {
                unlink($tmpName);

                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Check rights to view the current tab
     *
     * @return bool
     */
    protected function afterImageUpload()
    {
        return true;
    }

    /**
     * Object creation
     *
     * @return ObjectModel|false
     * @throws PrestaShopException
     */
    public function processAdd()
    {
        if (empty($this->className)) {
            return false;
        }

        $this->validateRules();
        if (count($this->errors) <= 0) {
            $this->object = new $this->className();

            $this->copyFromPost($this->object, $this->table);
            $this->beforeAdd($this->object);
            if (method_exists($this->object, 'add') && !$this->object->add()) {
                $this->errors[] = Tools::displayError('An error occurred while creating an object.').' <strong>'.$this->table.' ('.Db::getInstance()->getMsgError().')</strong>';
            } elseif (($_POST[$this->identifier] = $this->object->id /* voluntary do affectation here */) && $this->postImage($this->object->id) && !count($this->errors) && $this->_redirect) {
                Logger::addLog(sprintf($this->l('%s addition', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int) $this->object->id, true, (int) $this->context->employee->id);
                $parentId = Tools::getIntValue('id_parent', 1);
                $this->afterAdd($this->object);
                $this->updateAssoShop($this->object->id);
                // Save and stay on same form
                if (empty($this->redirect_after) && $this->redirect_after !== false && Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
                    $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'='.$this->object->id.'&conf=3&update'.$this->table.'&token='.$this->token;
                }
                // Save and back to parent
                if (empty($this->redirect_after) && $this->redirect_after !== false && Tools::isSubmit('submitAdd'.$this->table.'AndBackToParent')) {
                    $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'='.$parentId.'&conf=3&token='.$this->token;
                }
                // Default behavior (save and back)
                if (empty($this->redirect_after) && $this->redirect_after !== false) {
                    $this->redirect_after = static::$currentIndex.($parentId ? '&'.$this->identifier.'='.$this->object->id : '').'&conf=3&token='.$this->token;
                }
            }
        }

        $this->errors = array_unique($this->errors);
        if (!empty($this->errors)) {
            // if we have errors, we stay on the form instead of going back to the list
            $this->display = 'edit';

            return false;
        }

        return $this->object;
    }

    /**
     * Called before Add
     *
     * @param ObjectModel $object Object
     *
     * @return void
     */
    protected function beforeAdd($object)
    {
    }

    /**
     * @param ObjectModel $object
     *
     * @return void
     */
    protected function afterAdd($object)
    {
    }

    /**
     * Change object required fields
     *
     * @return ObjectModel
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processUpdateFields()
    {
        $fields = Tools::getArrayValue('fieldsBox');

        /** @var ObjectModel $object */
        $object = new $this->className();

        if (!$object->addFieldsRequiredDatabase($fields)) {
            $this->errors[] = Tools::displayError('An error occurred when attempting to update the required fields.');
        } else {
            $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
        }

        return $object;
    }

    /**
     * Change object status (active, inactive)
     *
     * @return ObjectModel|false
     * @throws PrestaShopException
     */
    public function processStatus()
    {
        /** @var ObjectModel $object */
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            if (property_exists($object, 'active') && $object->toggleStatus()) {
                Logger::addLog(
                    sprintf($this->l('%s status switched to %s', 'AdminTab', false, false), $this->className, $object->active ? 'enable' : 'disable'),
                    1,
                    null,
                    $this->className,
                    (int) $object->id,
                    true,
                    (int) $this->context->employee->id
                );
                $matches = [];
                $referer = Tools::getHttpReferer();
                if (preg_match('/[\?|&]controller=([^&]*)/', $referer, $matches) !== false &&
                    strtolower($matches[1]) != strtolower(preg_replace('/controller/i', '', get_class($this)))
                ) {
                    $this->redirect_after = preg_replace('/[\?|&]conf=([^&]*)/i', '', $referer);
                } else {
                    $this->redirect_after = static::$currentIndex.'&token='.$this->token;
                }

                $idCategory = (($idCategory = Tools::getIntValue('id_category')) && Tools::getIntValue('id_product')) ? '&id_category='.$idCategory : '';

                $page = Tools::getIntValue('page');
                $page = $page > 1 ? '&submitFilter'.$this->table.'='.(int) $page : '';
                $this->redirect_after .= '&conf=5'.$idCategory.$page;
            } else {
                $this->errors[] = Tools::displayError('An error occurred while updating the status.');
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').
                ' <b>'.$this->table.'</b> '.
                Tools::displayError('(cannot load object)');
        }

        return $object;
    }

    /**
     * Change object position
     *
     * @return ObjectModel|false
     */
    public function processPosition()
    {
        if (!Validate::isLoadedObject($object = $this->loadObject())) {
            $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').
                ' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        } elseif (!$object->updatePosition(Tools::getIntValue('way'), Tools::getIntValue('position'))) {
            $this->errors[] = Tools::displayError('Failed to update the position.');
        } else {
            $idIdentifierStr = ($idIdentifier = Tools::getIntValue($this->identifier)) ? '&'.$this->identifier.'='.$idIdentifier : '';
            $redirect = static::$currentIndex.'&'.$this->table.'Orderby=position&'.$this->table.'Orderway=asc&conf=5'.$idIdentifierStr.'&token='.$this->token;
            $this->redirect_after = $redirect;
        }

        return $object;
    }

    /**
     * Cancel all filters for this tab
     *
     * @param int|null $listId
     */
    public function processResetFilters($listId = null)
    {
        if ($listId === null) {
            $listId = isset($this->list_id) ? $this->list_id : $this->table;
        }

        $prefix = $this->getCookieFilterPrefix();
        $filters = $this->context->cookie->getFamily($prefix.$listId.'Filter_');
        foreach ($filters as $cookieKey => $filter) {
            if (strncmp($cookieKey, $prefix.$listId.'Filter_', 7 + mb_strlen($prefix.$listId)) == 0) {
                $key = substr($cookieKey, 7 + mb_strlen($prefix.$listId));
                if (is_array($this->fields_list) && array_key_exists($key, $this->fields_list)) {
                    $this->context->cookie->$cookieKey = null;
                }
                unset($this->context->cookie->$cookieKey);
            }
        }

        if (isset($this->context->cookie->{'submitFilter'.$listId})) {
            unset($this->context->cookie->{'submitFilter'.$listId});
        }
        if (isset($this->context->cookie->{$prefix.$listId.'Orderby'})) {
            unset($this->context->cookie->{$prefix.$listId.'Orderby'});
        }
        if (isset($this->context->cookie->{$prefix.$listId.'Orderway'})) {
            unset($this->context->cookie->{$prefix.$listId.'Orderway'});
        }

        $_POST = [];
        $this->_filter = false;
        unset($this->_filterHaving);
        unset($this->_having);
    }

    /**
     * Check if the token is valid, else display a warning page
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function checkAccess()
    {
        if (!$this->checkToken()) {
            // If this is an XSS attempt, then we should only display a simple, secure page
            // ${1} in the replacement string of the regexp is required,
            // because the token may begin with a number and mix up with it (e.g. $17)
            $url = preg_replace('/([&?]token=)[^&]*(&.*)?$/', '${1}'.$this->token.'$2', $_SERVER['REQUEST_URI']);
            if (false === strpos($url, '?token=') && false === strpos($url, '&token=')) {
                $url .= '&token='.$this->token;
            }
            if (strpos($url, '?') === false) {
                $url = str_replace('&token', '?controller=AdminDashboard&token', $url);
            }

            $this->context->smarty->assign('url', htmlentities($url));

            return false;
        }

        return true;
    }

    /**
     * Check for security token
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function checkToken()
    {
        // if token is provided it must match the expected token
        $token = Tools::getValue('token');
        if ($token) {
            return $token === $this->token;
        }

        // token was not provided. It is required, if security was explicitly strengthened
        $forceToken = (bool)Configuration::getGlobalValue(Configuration::BO_FORCE_TOKEN);
        if ($forceToken) {
            return false;
        }

        // if there are any POST parameters, token is required
        if (count($_POST)) {
            return false;
        }

        // if there are any GET parameters, token is required as well
        foreach ($_GET as $key => $value) {
            if (! in_array($key, ['controller', 'controllerUri'])) {
                return false;
            }
            if ($key === 'controller' && !Validate::isControllerName($value)) {
                return false;
            }
        }

        // for backwards compatibility reasons
        return true;
    }

    /**
     * @return void
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function displayAjax()
    {
        if ($this->json) {
            $this->context->smarty->assign(
                [
                    'json'   => true,
                    'status' => $this->status,
                ]
            );
        }
        $this->layout = 'layout-ajax.tpl';
        $this->display_header = false;
        $this->display_header_javascript = false;
        $this->display_footer = false;

        $this->display();
    }

    /**
     * @return void
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function display()
    {
        $this->context->smarty->assign(
            [
                'display_header'            => $this->display_header,
                'display_header_javascript' => $this->display_header_javascript,
                'display_footer'            => $this->display_footer,
                'js_def'                    => Media::getJsDef(),
            ]
        );

        // Use page title from meta_title if it has been set else from the breadcrumbs array
        if (!$this->meta_title) {
            $this->meta_title = $this->toolbar_title;
        }
        if (is_array($this->meta_title)) {
            $this->meta_title = strip_tags(implode(' '.Configuration::get('PS_NAVIGATION_PIPE').' ', $this->meta_title));
        }
        $this->context->smarty->assign('meta_title', $this->meta_title);

        $templateDirs = $this->context->smarty->getTemplateDir();

        // Check if header/footer have been overriden
        $dir = $this->context->smarty->getTemplateDir(0).'controllers'.DIRECTORY_SEPARATOR.trim($this->override_folder, '\\/').DIRECTORY_SEPARATOR;

        $headerTpl = file_exists($dir.'header.tpl') ? $dir.'header.tpl' : 'header.tpl';
        $pageHeaderToolbar = file_exists($dir.'page_header_toolbar.tpl') ? $dir.'page_header_toolbar.tpl' : 'page_header_toolbar.tpl';
        $footerTpl = file_exists($dir.'footer.tpl') ? $dir.'footer.tpl' : 'footer.tpl';
        $tplAction = $this->tpl_folder.$this->display.'.tpl';

        // Check if action template has been overriden
        foreach ($templateDirs as $template_dir) {
            if (file_exists($template_dir.DIRECTORY_SEPARATOR.$tplAction) && $this->display != 'view' && $this->display != 'options') {
                if (method_exists($this, $this->display.Tools::toCamelCase($this->className))) {
                    $this->{$this->display.Tools::toCamelCase($this->className)}();
                }
                $this->context->smarty->assign('content', $this->context->smarty->fetch($tplAction));
                break;
            }
        }

        if (!$this->ajax) {
            $template = $this->createTemplate($this->template);
            $page = $template->fetch();
        } else {
            $page = $this->content;
        }

        if ($conf = Tools::getValue('conf')) {
            $this->context->smarty->assign('conf', $this->json ? json_encode($this->_conf[(int) $conf]) : $this->_conf[(int) $conf]);
        }

        foreach (['errors', 'warnings', 'informations', 'confirmations'] as $type) {
            if (!is_array($this->$type)) {
                $this->$type = (array) $this->$type;
            }
            $this->context->smarty->assign($type, $this->json ? json_encode(array_unique($this->$type)) : array_unique($this->$type));
        }

        if ($this->show_page_header_toolbar && !$this->lite_display) {
            $this->context->smarty->assign(
                [
                    'page_header_toolbar' => $this->context->smarty->fetch($pageHeaderToolbar),
                ]
            );
        }

        $messages = static::getErrorMessages();
        if ($messages) {
            $this->context->smarty->assign('php_errors', $messages);
        }

        $this->context->smarty->assign(
            [
                'page'   => $this->json ? json_encode($page) : $page,
                'header' => $this->context->smarty->fetch($headerTpl),
                'footer' => $this->context->smarty->fetch($footerTpl),
            ]
        );

        $this->smartyOutputContent($this->layout);
    }

    /**
     * Create a template from the override file, else from the base file.
     *
     * @param string $tplName filename
     *
     * @return Smarty_Internal_Template
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function createTemplate($tplName)
    {
        $smarty = $this->context->smarty;
        $templateDir = $smarty->getTemplateDir(0);
        if ($this->viewAccess()) {
            if ($this->override_folder) {
                // Use override tpl if it exists
                $overrideTemplateDir = $smarty->getTemplateDir(1);
                if (!Configuration::get('PS_DISABLE_OVERRIDES') && file_exists($overrideTemplateDir. DIRECTORY_SEPARATOR . $this->override_folder . $tplName)) {
                    return $smarty->createTemplate($this->override_folder . $tplName, $smarty);
                }
                if (file_exists($templateDir . 'controllers' . DIRECTORY_SEPARATOR . $this->override_folder . $tplName)) {
                    return $smarty->createTemplate('controllers' . DIRECTORY_SEPARATOR . $this->override_folder . $tplName, $smarty);
                }
            }
            return $smarty->createTemplate($templateDir.$tplName, $smarty);
        } else {
            // If view access is denied, we want to use the default template that will be used to display an error
            return $smarty->createTemplate($templateDir . static::DEFAULT_VIEW_TEMPLATE, $smarty);
        }
    }

    /**
     * Check rights to view the current tab
     *
     * @param bool $disable
     *
     * @return bool
     */
    public function viewAccess($disable = false)
    {
        if ($disable) {
            return true;
        }

        return $this->hasViewPermission();
    }

    /**
     * Assign smarty variables for the header
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initHeader()
    {
        header('Cache-Control: no-store, no-cache');

        // Multishop
        $isMultishop = Shop::isFeatureActive();

        // Quick access
        if ((int) $this->context->employee->id) {
            $quickAccess = QuickAccess::getQuickAccesses($this->context->language->id);
            foreach ($quickAccess as $index => $quick) {
                if ($quick['link'] == '../' && Shop::getContext() == Shop::CONTEXT_SHOP) {
                    $url = $this->context->shop->getBaseURL();
                    if (!$url) {
                        unset($quickAccess[$index]);
                        continue;
                    }
                    $quickAccess[$index]['link'] = $url;
                } else {
                    preg_match('/controller=(.+)(&.+)?$/', $quick['link'], $adminTab);
                    if (isset($adminTab[1])) {
                        if (strpos($adminTab[1], '&')) {
                            $adminTab[1] = substr($adminTab[1], 0, strpos($adminTab[1], '&'));
                        }

                        $token = Tools::getAdminToken($adminTab[1].(int) Tab::getIdFromClassName($adminTab[1]).(int) $this->context->employee->id);
                        $quickAccess[$index]['target'] = $adminTab[1];
                        $quickAccess[$index]['link'] .= '&token='.$token;
                    }
                }
            }
        }

        // Tab list
        $tabs = Tab::getTabs($this->context->language->id, 0);
        $currentId = Tab::getCurrentParentId();
        foreach ($tabs as $index => $tab) {
            if (!Tab::checkTabRights($tab['id_tab'])
                || ($tab['class_name'] == 'AdminStock' && Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') == 0)
                || $tab['class_name'] == 'AdminCarrierWizard'
            ) {
                unset($tabs[$index]);
                continue;
            }

            $tabs[$index]['current'] = ($tab['class_name'].'Controller' == get_class($this)) || ($currentId == $tab['id_tab']);
            $tabs[$index]['href'] = $this->context->link->getAdminLink($tab['class_name']);

            $subTabs = Tab::getTabs($this->context->language->id, $tab['id_tab']);
            foreach ($subTabs as $index2 => $subTab) {
                //check if module is enable and
                if (isset($subTab['module']) && !empty($subTab['module'])) {

                    $moduleId = Module::getModuleIdByName($subTab['module']) ;
                    if (!$moduleId || !Module::isEnabledForShops($moduleId, Shop::getContextListShopID())) {
                        unset($subTabs[$index2]);
                        continue;
                    }
                }

                // class_name is the name of the class controller
                if (Tab::checkTabRights($subTab['id_tab']) === true && $subTab['active'] && $subTab['class_name'] != 'AdminCarrierWizard') {
                    $subTabs[$index2]['href'] = $this->context->link->getAdminLink($subTab['class_name']);
                    $subTabs[$index2]['current'] = ($subTab['class_name'].'Controller' == get_class($this) || $subTab['class_name'] == Tools::getValue('controller'));
                } elseif ($subTab['class_name'] == 'AdminCarrierWizard' && $subTab['class_name'].'Controller' == get_class($this)) {
                    foreach ($subTabs as $i => $tab) {
                        if ($tab['class_name'] == 'AdminCarriers') {
                            break;
                        }
                    }

                    $subTabs[$i]['current'] = true;
                    unset($subTabs[$index2]);
                } else {
                    unset($subTabs[$index2]);
                }
            }
            $tabs[$index]['sub_tabs'] = array_values($subTabs);
        }

        if (Validate::isLoadedObject($this->context->employee)) {
            $notification = $this->context->employee->getNotification();
            $helperShop = new HelperShop();
            /* Hooks are voluntary out the initialize array (need those variables already assigned) */
            $boColor = empty($this->context->employee->bo_color) ? '#FFFFFF' : $this->context->employee->bo_color;
            $this->context->smarty->assign(
                [
                    'autorefresh_notifications' => false, Configuration::get('PS_ADMINREFRESH_NOTIFICATION'),
                    'notificationTypes'         => $notification->getTypes(),
                    'help_box'                  => Configuration::get('PS_HELPBOX'),
                    'round_mode'                => Configuration::get('PS_PRICE_ROUND_MODE'),
                    'brightness'                => Tools::getBrightness($boColor) < 128 ? 'white' : '#383838',
                    'bo_width'                  => (int) $this->context->employee->bo_width,
                    'bo_color'                  => isset($this->context->employee->bo_color) ? Tools::htmlentitiesUTF8($this->context->employee->bo_color) : null,
                    'employee'                  => $this->context->employee,
                    'search_type'               => Tools::getValue('bo_search_type'),
                    'bo_query'                  => Tools::safeOutput(Tools::getValue('bo_query')),
                    'quick_access'              => $quickAccess,
                    'multi_shop'                => Shop::isFeatureActive(),
                    'shop_list'                 => $helperShop->getRenderedShopList(),
                    'shop'                      => $this->context->shop,
                    'shop_group'                => new ShopGroup((int) Shop::getContextShopGroupID()),
                    'is_multishop'              => $isMultishop,
                    'multishop_context'         => $this->multishop_context,
                    'default_tab_link'          => $this->context->link->getAdminLink(Tab::getClassNameById((int) $this->context->employee->default_tab)),
                    'login_link'                => $this->context->link->getAdminLink('AdminLogin'),
                    'collapse_menu'             => isset($this->context->cookie->collapse_menu) ? (int) $this->context->cookie->collapse_menu : 0,
                ]
            );
        } else {
            $this->context->smarty->assign('default_tab_link', $this->context->link->getAdminLink('AdminDashboard'));
        }

        // Shop::initialize() in config.php may empty $this->context->shop->virtual_uri so using a new shop instance for getBaseUrl()
        $this->context->shop = new Shop((int) $this->context->shop->id);

        switch (Shop::getContext()) {
            case Shop::CONTEXT_ALL:
                $shopContext = 'all';
                break;
            case Shop::CONTEXT_GROUP:
                $shopContext = 'group-' . Shop::getContextShopGroupID(false);
                break;
            case Shop::CONTEXT_SHOP:
            default:
                $shopContext = 'shop-' . Shop::getContextShopID(false);
        }

        $this->context->smarty->assign(
            [
                'img_dir'                   => _PS_IMG_,
                'iso'                       => $this->context->language->iso_code,
                'class_name'                => $this->className,
                'iso_user'                  => $this->context->language->iso_code,
                'country_iso_code'          => $this->context->country->iso_code,
                'version'                   => _TB_VERSION_,
                'lang_iso'                  => $this->context->language->iso_code,
                'full_language_code'        => $this->context->language->language_code,
                'link'                      => $this->context->link,
                'shop_name'                 => Configuration::get('PS_SHOP_NAME'),
                'base_url'                  => $this->context->shop->getBaseURL(),
                'tab'                       => isset($tab) ? $tab : null, // Deprecated, this tab is declared in the foreach, so it's the last tab in the foreach
                'current_parent_id'         => (int) Tab::getCurrentParentId(),
                'tabs'                      => $tabs,
                'install_dir_exists'        => file_exists(_PS_ADMIN_DIR_.'/../install'),
                'pic_dir'                   => _THEME_PROD_PIC_DIR_,
                'controller_name'           => htmlentities(Tools::getValue('controller')),
                'currentIndex'              => static::$currentIndex,
                'maintenance_mode'          => !Configuration::get('PS_SHOP_ENABLE'),
                'bootstrap'                 => $this->bootstrap,
                'default_language'          => (int) Configuration::get('PS_LANG_DEFAULT'),
                'shopContext'               => $shopContext,
            ]
        );

        /** @var ThemeConfigurator|false $module */
        $module = Module::getInstanceByName('themeconfigurator');
        if (is_object($module) && $module->active && (int) Configuration::get('PS_TC_ACTIVE') == 1 && $this->context->shop->getBaseURL()) {
            $request =
                'live_configurator_token='.$module->getLiveConfiguratorToken()
                .'&id_employee='.(int) $this->context->employee->id
                .'&id_shop='.(int) $this->context->shop->id
                .(Configuration::get('PS_TC_THEME') != '' ? '&theme='.Configuration::get('PS_TC_THEME') : '')
                .(Configuration::get('PS_TC_FONT') != '' ? '&theme_font='.Configuration::get('PS_TC_FONT') : '');
            $this->context->smarty->assign('base_url_tc', $this->context->link->getPageLink('index', null, null, $request));
        }
    }

    /**
     * Declare an action to use for each row in the list
     *
     * @param string $action
     */
    public function addRowAction($action)
    {
        $action = strtolower($action);
        $this->actions[] = $action;
    }

    /**
     * Add an action to use for each row in the list
     *
     * @param string $action
     * @param array $list
     */
    public function addRowActionSkipList($action, $list)
    {
        $action = strtolower($action);
        $list = (array) $list;

        if (array_key_exists($action, $this->list_skip_actions)) {
            $this->list_skip_actions[$action] = array_merge($this->list_skip_actions[$action], $list);
        } else {
            $this->list_skip_actions[$action] = $list;
        }
    }

    /**
     * Assign smarty variables for all default views, list and form, then call other init functions
     *
     * @return void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function initContent()
    {
        if (!$this->viewAccess()) {
            $this->errors[] = Tools::displayError('You do not have permission to view this.');

            return;
        }

        $this->getLanguages();
        $this->initToolbar();
        $this->initPageHeaderToolbar();

        if ($this->display == 'edit' || $this->display == 'add') {
            if ($this->className) {
                if (!$this->loadObject(true)) {
                    return;
                }
            }

            $this->content .= $this->renderForm();
        } elseif ($this->display == 'view') {
            // Some controllers use the view action without an object
            if ($this->className) {
                $this->loadObject(true);
            }
            $this->content .= $this->renderView();
        } elseif ($this->display == 'details') {
            $this->content .= $this->renderDetails();
        } elseif (!$this->ajax) {
            $this->content .= $this->renderKpis();
            $this->content .= $this->renderList();
            $this->content .= $this->renderOptions();

            // if we have to display the required fields form
            if ($this->required_database) {
                $this->content .= $this->displayRequiredFields();
            }
        }

        $this->context->smarty->assign(
            [
                'maintenance_mode'          => !Configuration::get('PS_SHOP_ENABLE'),
                'content'                   => $this->content,
                'lite_display'              => $this->lite_display,
                'url_post'                  => static::$currentIndex.'&token='.$this->token,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'title'                     => $this->page_header_toolbar_title,
                'toolbar_btn'               => $this->page_header_toolbar_btn,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * @param int $defaultFormLanguage
     *
     * @return array
     * @throws PrestaShopException
     */
    protected static function resolveFormLanguages(int $defaultFormLanguage): array
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as &$language) {
            $isDefault = $defaultFormLanguage === (int)$language['id_lang'];
            $language['is_default'] = $isDefault ? 1 : 0;
        }
        return $languages;
    }

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function getLanguages()
    {
        if (! $this->_languages) {
            $this->_languages = static::resolveFormLanguages($this->getDefaultFormLanguage());
        }
        return $this->_languages;
    }

    /**
     * @param Cookie $cookie
     * @param int $allowEmployeeLang
     *
     * @return int
     * @throws PrestaShopException
     */
    protected static function resolveDefaultFormLanguage(Cookie $cookie, int $allowEmployeeLang): int
    {
        $languageIds = Language::getLanguages(false, false, true);
        if ($languageIds) {

            // first check last used employee language
            if ($allowEmployeeLang) {
                if (isset($cookie->employee_form_lang)) {
                    $employeeLang = (int)$cookie->employee_form_lang;
                    if (in_array($employeeLang, $languageIds)) {
                        return $employeeLang;
                    }
                }
            }
            if (isset($cookie->employee_form_lang)) {
                unset($cookie->employee_form_lang);
            }

            // try default language
            $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');
            if (in_array($defaultLang, $languageIds)) {
                return $defaultLang;
            }

            // fallback to first language in the list
            return $languageIds[0];
        }
        return 0;
    }

    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    protected function getDefaultFormLanguage(): int
    {
        if (is_null($this->default_form_language)) {
            $this->default_form_language = static::resolveDefaultFormLanguage($this->context->cookie, $this->getAllowEmployeeFormLanguage());
        }
        return (int)$this->default_form_language;
    }

    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    protected function getAllowEmployeeFormLanguage(): int
    {
        if (is_null($this->allow_employee_form_lang)) {
            $this->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        }
        return (int)$this->allow_employee_form_lang;
    }

    /**
     * assign default action in toolbar_btn smarty var, if they are not set.
     * uses override to specifically add, modify or remove items
     *
     * @throws PrestaShopException
     */
    public function initToolbar()
    {
        switch ($this->display) {
            case 'add':
            case 'edit':
                // Default save button - action dynamically handled in javascript
                $this->toolbar_btn['save'] = [
                    'href' => '#',
                    'desc' => $this->l('Save'),
                ];
                if (!$this->lite_display) {
                    $this->toolbar_btn['cancel'] = [
                        'href' => $this->getBackUrlParameter(),
                        'desc' => $this->l('Cancel'),
                    ];
                }
                break;
            case 'view':
                if (!$this->lite_display) {
                    $this->toolbar_btn['back'] = [
                        'href' => $this->getBackUrlParameter(),
                        'desc' => $this->l('Back to list'),
                    ];
                }
                break;
            case 'options':
                $this->toolbar_btn['save'] = [
                    'href' => '#',
                    'desc' => $this->l('Save'),
                ];
                break;
            default: // list
                $this->toolbar_btn['new'] = [
                    'href' => static::$currentIndex.'&add'.$this->table.'&token='.$this->token,
                    'desc' => $this->l('Add new'),
                ];
                if ($this->allow_export) {
                    $this->toolbar_btn['export'] = [
                        'href' => static::$currentIndex.'&export'.$this->table.'&token='.$this->token,
                        'desc' => $this->l('Export'),
                    ];
                }
        }
    }

    /**
     * @return void
     */
    protected function addToolBarModulesListButton()
    {
    }

    /**
     * @return void
     */
    protected function filterTabModuleList()
    {
    }

    /**
     * Init tab modules list and add button in toolbar
     *
     * @deprecated 1.5.0
     */
    protected function initTabModuleList()
    {
    }

    /**
     * @param string $file
     * @param int $timeout
     *
     * @return bool
     */
    public function isFresh($file, $timeout = 604800)
    {
        $path = _PS_ROOT_DIR_.$file;
        if (file_exists($path) && filesize($path) > 0) {
            return ((time() - filemtime($path)) < $timeout);
        }

        return false;
    }

    /**
     * @return void
     */
    protected function addPageHeaderToolBarModulesListButton()
    {
    }

    /**
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->toolbar_title)) {
            $this->initToolbarTitle();
        }

        if (!is_array($this->toolbar_title)) {
            $this->toolbar_title = [$this->toolbar_title];
        }

        switch ($this->display) {
            case 'view':
                // Default cancel button - like old back link
                if (!$this->lite_display) {
                    $this->page_header_toolbar_btn['back'] = [
                        'href' => $this->getBackUrlParameter(),
                        'desc' => $this->l('Back to list'),
                    ];
                }
                $obj = $this->loadObject(true);
                if (Validate::isLoadedObject($obj) && isset($obj->{$this->identifier_name}) && !empty($obj->{$this->identifier_name})) {
                    array_pop($this->toolbar_title);
                    array_pop($this->meta_title);
                    $this->toolbar_title[] = is_array($obj->{$this->identifier_name}) ? $obj->{$this->identifier_name}[$this->context->employee->id_lang] : $obj->{$this->identifier_name};
                    $this->addMetaTitle($this->toolbar_title[count($this->toolbar_title) - 1]);
                }
                break;
            case 'edit':
                $obj = $this->loadObject(true);
                if (Validate::isLoadedObject($obj) && isset($obj->{$this->identifier_name}) && !empty($obj->{$this->identifier_name})) {
                    array_pop($this->toolbar_title);
                    array_pop($this->meta_title);
                    $this->toolbar_title[] = sprintf($this->l('Edit: %s'), (is_array($obj->{$this->identifier_name}) && isset($obj->{$this->identifier_name}[$this->context->employee->id_lang])) ? $obj->{$this->identifier_name}[$this->context->employee->id_lang] : $obj->{$this->identifier_name});
                    $this->addMetaTitle($this->toolbar_title[count($this->toolbar_title) - 1]);
                }
                break;
        }

        if (empty($this->page_header_toolbar_title) && $this->toolbar_title) {
            if (is_array($this->toolbar_title)) {
                $size = count($this->toolbar_title);
                $this->page_header_toolbar_title = $this->toolbar_title[$size - 1];
            } else {
                $this->page_header_toolbar_title = $this->toolbar_title;
            }
        }

        if (is_array($this->page_header_toolbar_btn)
            || ($this->page_header_toolbar_btn instanceof Traversable)
            || $this->page_header_toolbar_title
        ) {
            $this->show_page_header_toolbar = true;
        }

        $this->context->smarty->assign('help_link', '');
    }

    /**
     * Set default toolbar_title to admin breadcrumb
     *
     * @return void
     */
    public function initToolbarTitle()
    {
        $this->toolbar_title = is_array($this->breadcrumbs) ? array_unique($this->breadcrumbs) : [$this->breadcrumbs];

        switch ($this->display) {
            case 'edit':
                $this->toolbar_title[] = $this->l('Edit', null, null, false);
                $this->addMetaTitle($this->l('Edit', null, null, false));
                break;

            case 'add':
                $this->toolbar_title[] = $this->l('Add new', null, null, false);
                $this->addMetaTitle($this->l('Add new', null, null, false));
                break;

            case 'view':
                $this->toolbar_title[] = $this->l('View', null, null, false);
                $this->addMetaTitle($this->l('View', null, null, false));
                break;
        }

        if ($filter = $this->addFiltersToBreadcrumbs()) {
            $this->toolbar_title[] = $filter;
        }
    }

    /**
     * Add an entry to the meta title.
     *
     * @param string $entry New entry.
     */
    public function addMetaTitle($entry)
    {
        // Only add entry if the meta title was not forced.
        if (is_array($this->meta_title)) {
            $this->meta_title[] = $entry;
        }
    }

    /**
     * @return string
     */
    public function addFiltersToBreadcrumbs()
    {
        if ($this->filter && is_array($this->fields_list)) {
            $filters = [];

            foreach ($this->fields_list as $field => $t) {
                if (isset($t['filter_key'])) {
                    $field = $t['filter_key'];
                }

                $val = $this->getListFieldFilterValue($field);

                if (! is_null($val)) {
                    $filterValue = '';
                    if (!is_array($val)) {
                        if (isset($t['type']) && $t['type'] == 'bool') {
                            $filterValue = ($val)
                                ? $this->l('yes')
                                : $this->l('no');
                        } elseif (isset($t['type']) && $t['type'] == 'date' || isset($t['type']) && $t['type'] == 'datetime') {
                            $date = json_decode($val, true);
                            if (isset($date[0]) && $ts=strtotime($date[0])) {
                                $filterValue = (string)date('Y-m-d', $ts);
                                if (isset($date[1]) && !empty($date[1]) && $ts=strtotime($date[1])) {
                                    $filterValue .= ' - '. date('Y-m-d', $ts);
                                }
                            }
                        } elseif (is_string($val)) {
                            $filterValue = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
                        }
                    } else {
                        foreach ($val as $v) {
                            if (is_string($v)) {
                                $v = trim($v);
                                if ($v !== '') {
                                    $filterValue .= ' - '.htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
                                }
                            }
                        }
                        $filterValue = ltrim($filterValue, ' -');
                    }
                    if ($filterValue !== '') {
                        $filters[] = sprintf($this->l('%s: %s'), $t['title'], $filterValue);
                    }
                }
            }

            if (count($filters)) {
                return sprintf($this->l('filter by %s'), implode(', ', $filters));
            }
        }

        return null;
    }

    /**
     * Function used to render the form for this controller
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $this->getLanguages();

        if (Tools::getValue('submitFormAjax')) {
            $this->content .= $this->context->smarty->fetch('form_submit_ajax.tpl');
        }

        if ($this->fields_form && is_array($this->fields_form)) {
            if (!$this->multiple_fieldsets) {
                $this->fields_form = [['form' => $this->fields_form]];
            }

            // For add a fields via an override of $fields_form, use $fields_form_override
            if (is_array($this->fields_form_override) && !empty($this->fields_form_override)) {
                $this->fields_form[0]['form']['input'] = array_merge($this->fields_form[0]['form']['input'], $this->fields_form_override);
            }

            $fieldsValue = $this->getFieldsValue($this->object);

            Hook::triggerEvent(
                'action'.$this->controller_name.'FormModifier', [
                    'fields'       => &$this->fields_form,
                    'fields_value' => &$fieldsValue,
                    'form_vars'    => &$this->tpl_form_vars,
                ]
            );

            $helper = new HelperForm();
            $this->setHelperDisplay($helper);
            $helper->fields_value = $fieldsValue;
            $helper->submit_action = $this->submit_action;
            $helper->tpl_vars = $this->getTemplateFormVars();
            $helper->show_cancel_button = (isset($this->show_form_cancel_button)) ? $this->show_form_cancel_button : ($this->display == 'add' || $this->display == 'edit');

            $helper->back_url = $this->getBackUrlParameter();
            if ($this->base_tpl_form) {
                $helper->base_tpl = $this->base_tpl_form;
            }
            if ($this->hasViewPermission()) {
                if (Tools::getValue('back')) {
                    $helper->tpl_vars['back'] = Tools::safeOutput(Tools::getValue('back'));
                } else {
                    $helper->tpl_vars['back'] = Tools::safeOutput(Tools::getValue(static::$currentIndex.'&token='.$this->token));
                }
            }
            $form = $helper->generateForm($this->fields_form);

            return $form;
        }
    }

    /**
     * Return the list of fields value
     *
     * @param ObjectModel $obj Object
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getFieldsValue($obj)
    {
        foreach ($this->fields_form as $fieldset) {
            if (isset($fieldset['form']['input'])) {
                foreach ($fieldset['form']['input'] as $input) {
                    if (!isset($this->fields_value[$input['name']])) {
                        if (isset($input['type']) && $input['type'] == 'shop') {
                            if ($obj->id) {
                                $result = Shop::getShopById((int) $obj->id, $this->identifier, $this->table);
                                foreach ($result as $row) {
                                    $this->fields_value['shop'][$row['id_'.$input['type']]][] = $row['id_shop'];
                                }
                            }
                        } elseif (isset($input['lang']) && $input['lang']) {
                            foreach ($this->getLanguages() as $language) {
                                $fieldValue = $this->getFieldValue($obj, $input['name'], $language['id_lang']);
                                if (empty($fieldValue)) {
                                    if (isset($input['default_value'][$language['id_lang']]) && is_array($input['default_value'])) {
                                        $fieldValue = $input['default_value'][$language['id_lang']];
                                    } elseif (isset($input['default_value'])) {
                                        $fieldValue = $input['default_value'];
                                    }
                                }
                                $this->fields_value[$input['name']][$language['id_lang']] = $fieldValue;
                            }
                        } else {
                            $fieldValue = $this->getFieldValue($obj, $input['name']);
                            if ($fieldValue === false && isset($input['default_value'])) {
                                $fieldValue = $input['default_value'];
                            }
                            $this->fields_value[$input['name']] = $fieldValue;
                        }
                    }
                }
            }
        }

        return $this->fields_value;
    }

    /**
     * Return field value if possible (both classical and multilingual fields)
     *
     * Case 1 : Return value if present in $_POST / $_GET
     * Case 2 : Return object value
     *
     * @param ObjectModel|null $obj Object
     * @param string $key Field name
     * @param int|null $idLang Language id (optional)
     *
     * @return array|bool|float|int|string|null
     */
    public function getFieldValue($obj, $key, $idLang = null)
    {
        if (is_object($obj) && property_exists($obj, $key)) {
            if ($idLang) {
                $defaultValue = (isset($obj->id) && $obj->id && isset($obj->{$key}[$idLang])) ? $obj->{$key}[$idLang] : false;
            } else {
                $defaultValue = $obj->{$key} ?? false;
            }
        } else {
            $defaultValue = false;
        }

        return Tools::getValue($key.($idLang ? '_'.$idLang : ''), $defaultValue);
    }

    /**
     * This function sets various display options for helper list
     *
     * @param Helper $helper
     *
     * @throws PrestaShopException
     */
    public function setHelperDisplay(Helper $helper)
    {
        if (empty($this->toolbar_title)) {
            $this->initToolbarTitle();
        }

        if ($helper instanceof HelperList) {
            $this->setHelperListDisplay($helper);
        } elseif ($helper instanceof HelperView) {
            $this->setHelperViewDisplay($helper);
        } elseif ($helper instanceof HelperForm) {
            $this->setHelperFormDisplay($helper);
        } elseif ($helper instanceof HelperOptions) {
            $this->setHelperOptionsDisplay($helper);
        } elseif ($helper instanceof HelperKpi) {
            $this->setHelperKpiDisplay($helper);
        } elseif ($helper instanceof HelperKpiRow) {
            $this->setHelperKpiRowDisplay($helper);
        } elseif ($helper instanceof HelperShop) {
            $this->setHelperShopDisplay($helper);
        } elseif ($helper instanceof HelperCalendar) {
            $this->setHelperCalendarDisplay($helper);
        } else {
            $this->setHelperCommonDisplay($helper);
        }
        $this->helper = $helper;
    }

    /**
     * @param Helper $helper
     * @throws PrestaShopException
     */
    public function setHelperCommonDisplay(Helper $helper)
    {
        $helper->title = is_array($this->toolbar_title) ? implode(' '.Configuration::get('PS_NAVIGATION_PIPE').' ', $this->toolbar_title) : $this->toolbar_title;
        $helper->toolbar_btn = $this->toolbar_btn;
        $helper->show_toolbar = $this->show_toolbar;
        $helper->toolbar_scroll = $this->toolbar_scroll;
        $helper->override_folder = $this->tpl_folder;
        $helper->currentIndex = static::$currentIndex;
        $helper->table = $this->table;
        $helper->identifier = $this->identifier;
        $helper->token = $this->token;
        $helper->bootstrap = $this->bootstrap;
    }

    /**
     * @param HelperList $helper
     * @return void
     * @throws PrestaShopException
     */
    public function setHelperListDisplay(HelperList $helper)
    {
        $this->setHelperCommonDisplay($helper);
        $helper->actions = $this->actions;
        $helper->simple_header = $this->list_simple_header;
        $helper->bulk_actions = $this->bulk_actions;
        $helper->orderBy = $this->_orderBy;
        $helper->orderWay = $this->_orderWay;
        $helper->listTotal = $this->_listTotal;
        $helper->specificConfirmDelete = $this->specificConfirmDelete;
        $helper->no_link = $this->list_no_link;
        $helper->colorOnBackground = $this->colorOnBackground;
        $helper->shopLinkType = $this->shopLinkType;
        $helper->imageType = $this->imageType;
        $helper->ajax_params = $this->ajax_params;
        $helper->row_hover = $this->row_hover;
        $helper->position_identifier = $this->position_identifier;
        $helper->position_group_identifier = $this->position_group_identifier;
        $helper->controller_name = $this->controller_name;
        $helper->list_id = $this->list_id ?? $this->table;
        $helper->list_skip_actions = $this->list_skip_actions;
    }

    /**
     * @param HelperForm $helper
     * @return void
     * @throws PrestaShopException
     */
    public function setHelperFormDisplay(HelperForm $helper)
    {
        $this->setHelperCommonDisplay($helper);
        if ($this->object && $this->object->id) {
            $helper->id = $this->object->id;
        }
        $helper->name_controller = Tools::getValue('controller');
        $helper->languages = $this->getLanguages();
        $helper->default_form_language = $this->getDefaultFormLanguage();
        $helper->allow_employee_form_lang = $this->getAllowEmployeeFormLanguage();
    }

    /**
     * @param HelperView $helper
     * @return void
     * @throws PrestaShopException
     */
    public function setHelperViewDisplay(HelperView $helper)
    {
        $this->setHelperCommonDisplay($helper);
        if ($this->object && $this->object->id) {
            $helper->id = $this->object->id;
        }
    }

    /**
     * @param HelperOptions $helper
     * @return void
     * @throws PrestaShopException
     */
    public function setHelperOptionsDisplay(HelperOptions $helper)
    {
        $this->setHelperCommonDisplay($helper);
        if ($this->object && $this->object->id) {
            $helper->id = $this->object->id;
        }
    }

    /**
     * @param HelperKpi $helper
     * @return void
     * @throws PrestaShopException
     */
    public function setHelperKpiDisplay(HelperKpi $helper)
    {
        $this->setHelperCommonDisplay($helper);
        if ($this->object && $this->object->id) {
            $helper->id = $this->object->id;
        }
    }

    /**
     * @param HelperKpiRow $helper
     * @return void
     * @throws PrestaShopException
     */
    public function setHelperKpiRowDisplay(HelperKpiRow $helper)
    {
        $this->setHelperCommonDisplay($helper);
    }

    /**
     * @param HelperCalendar $helper
     * @return void
     * @throws PrestaShopException
     */
    public function setHelperCalendarDisplay(HelperCalendar $helper)
    {
        $this->setHelperCommonDisplay($helper);
    }

    /**
     * @param HelperShop $helper
     * @return void
     * @throws PrestaShopException
     */
    public function setHelperShopDisplay(HelperShop $helper)
    {
        $this->setHelperCommonDisplay($helper);
    }


    /**
     * @return array
     */
    public function getTemplateFormVars()
    {
        return $this->tpl_form_vars;
    }

    /**
     * Override to render the view page
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderView()
    {
        $helper = new HelperView();
        $this->setHelperDisplay($helper);
        $helper->tpl_vars = $this->getTemplateViewVars();
        if (!is_null($this->base_tpl_view)) {
            $helper->base_tpl = $this->base_tpl_view;
        }
        $view = $helper->generateView();

        return $view;
    }

    /**
     * @return array
     */
    public function getTemplateViewVars()
    {
        return $this->tpl_view_vars;
    }

    /**
     * Override to render the view page
     *
     * @return string|false
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderDetails()
    {
        return $this->renderList();
    }

    /**
     * Function used to render the list to display for this controller
     *
     * @return string|false
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        if (!($this->fields_list && is_array($this->fields_list))) {
            return false;
        }
        $this->getList($this->context->language->id);

        // If list has 'active' field, we automatically create bulk action
        if (isset($this->fields_list) && is_array($this->fields_list) && array_key_exists('active', $this->fields_list)
            && !empty($this->fields_list['active'])
        ) {
            if (!is_array($this->bulk_actions)) {
                $this->bulk_actions = [];
            }

            $this->bulk_actions = array_merge(
                [
                    'enableSelection'  => [
                        'text' => $this->l('Enable selection'),
                        'icon' => 'icon-power-off text-success',
                    ],
                    'disableSelection' => [
                        'text' => $this->l('Disable selection'),
                        'icon' => 'icon-power-off text-danger',
                    ],
                    'divider'          => [
                        'text' => 'divider',
                    ],
                ],
                $this->bulk_actions
            );
        }

        $helper = new HelperList();

        // Empty list is ok
        if (!is_array($this->_list)) {
            $this->displayWarning($this->l('Bad SQL query', 'Helper').'<br />'.htmlspecialchars($this->_list_error));

            return false;
        }

        $this->setHelperDisplay($helper);
        $helper->_default_pagination = $this->_default_pagination;
        $helper->_pagination = $this->_pagination;
        $helper->tpl_vars = $this->getTemplateListVars();
        $helper->tpl_delete_link_vars = $this->tpl_delete_link_vars;

        // For compatibility reasons, we have to check standard actions in class attributes
        foreach ($this->actions_available as $action) {
            if (!in_array($action, $this->actions) && isset($this->$action) && $this->$action) {
                $this->actions[] = $action;
            }
        }

        $helper->is_cms = $this->is_cms;
        $helper->sql = $this->_listsql;
        $list = $helper->generateList($this->_list, $this->fields_list);

        return $list;
    }

    /**
     * Add a warning message to display at the top of the page
     *
     * @param string $msg
     */
    protected function displayWarning($msg)
    {
        $this->warnings[] = $msg;
    }

    /**
     * @return array
     */
    public function getTemplateListVars()
    {
        return $this->tpl_list_vars;
    }

    /**
     * @return void
     */
    public function renderModulesList()
    {
    }

    /**
     * @param array|string $filterModulesList
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function getModulesList($filterModulesList)
    {
        if (!is_array($filterModulesList) && !is_null($filterModulesList)) {
            $filterModulesList = [$filterModulesList];
        }

        if (is_null($filterModulesList) || !count($filterModulesList)) {
            return false;
        } //if there is no modules to display just return false;

        $allModules = Module::getModulesOnDisk(true);
        $this->modules_list = [];
        foreach ($allModules as $module) {
            if ($module->id) {
                $perm = Module::getPermissionStatic($module->id, 'configure');
            } else {
                $perm = $this->context->employee->hasAccess(AdminModulesController::class, Profile::PERMISSION_EDIT);
            }

            if (in_array($module->name, $filterModulesList) && $perm) {
                $this->fillModuleData($module);
                $this->modules_list[array_search($module->name, $filterModulesList)] = $module;
            }
        }
        ksort($this->modules_list);

        if (count($this->modules_list)) {
            return true;
        }

        return false; //no module found on disk just return false;
    }

    /**
     * @param string $fileToRefresh
     * @param string $externalFile
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function refresh($fileToRefresh, $externalFile)
    {
        $guzzle = new GuzzleHttp\Client([
            'timeout' => 5,
            'verify' => Configuration::getSslTrustStore(),
        ]);

        if (static::$isThirtybeesUp) {
            try {
                $content = (string) $guzzle->get($externalFile)->getBody();

                return (bool) file_put_contents(_PS_ROOT_DIR_.$fileToRefresh, $content);
            } catch (Throwable $e) {
                static::$isThirtybeesUp = false;

                return false;
            }
        }

        return false;
    }

    /**
     * @param stdClass $module
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function fillModuleData(&$module)
    {

        // Fill module data
        $module->logo = '../../img/questionmark.png';

        if (file_exists(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.basename(_PS_MODULE_DIR_).DIRECTORY_SEPARATOR.$module->name.DIRECTORY_SEPARATOR.'logo.gif')) {
            $module->logo = 'logo.gif';
        }
        if (file_exists(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.basename(_PS_MODULE_DIR_).DIRECTORY_SEPARATOR.$module->name.DIRECTORY_SEPARATOR.'logo.png')) {
            $module->logo = 'logo.png';
        }

        $linkAdminModules = $this->context->link->getAdminLink('AdminModules', true);

        $module->options['install_url'] = $linkAdminModules.'&install='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name);
        $module->options['update_url'] = $linkAdminModules.'&update='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name);
        $module->options['uninstall_url'] = $linkAdminModules.'&uninstall='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name);

        $module->optionsHtml = $this->displayModuleOptions($module);

        if ((Tools::getValue('module_name') == $module->name || in_array($module->name, explode('|', Tools::getValue('modules_list')))) && Tools::getIntValue('conf') > 0) {
            $module->message = $this->_conf[Tools::getIntValue('conf')];
        }
    }

    /**
     * Display modules list
     *
     * @param stdClass $module
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function displayModuleOptions($module)
    {
        if (!isset($module->enable_device)) {
            $module->enable_device = Context::DEVICE_COMPUTER | Context::DEVICE_TABLET | Context::DEVICE_MOBILE;
        }

        $this->translationsTab['confirm_uninstall_popup'] = isset($module->confirmUninstall) && $module->confirmUninstall
            ? $module->confirmUninstall
            : $this->l('Do you really want to uninstall this module? All its data will be lost!');

        if (!isset($this->translationsTab['Disable this module'])) {
            $this->translationsTab['Disable this module'] = $this->l('Disable this module');
            $this->translationsTab['Enable this module for all shops'] = $this->l('Enable this module for all shops');
            $this->translationsTab['Disable'] = $this->l('Disable');
            $this->translationsTab['Enable'] = $this->l('Enable');
            $this->translationsTab['Disable on mobiles'] = $this->l('Disable on mobiles');
            $this->translationsTab['Disable on tablets'] = $this->l('Disable on tablets');
            $this->translationsTab['Disable on computers'] = $this->l('Disable on computers');
            $this->translationsTab['Display on mobiles'] = $this->l('Display on mobiles');
            $this->translationsTab['Display on tablets'] = $this->l('Display on tablets');
            $this->translationsTab['Display on computers'] = $this->l('Display on computers');
            $this->translationsTab['Reset'] = $this->l('Reset');
            $this->translationsTab['Configure'] = $this->l('Configure');
            $this->translationsTab['Delete'] = $this->l('Delete');
            $this->translationsTab['Install'] = $this->l('Install');
            $this->translationsTab['Uninstall'] = $this->l('Uninstall');
            $this->translationsTab['Would you like to delete the content related to this module ?'] = $this->l('Would you like to delete the content related to this module ?');
            $this->translationsTab['This action will permanently remove the module from the server. Are you sure you want to do this?'] = $this->l('This action will permanently remove the module from the server. Are you sure you want to do this?');
            $this->translationsTab['Remove from Favorites'] = $this->l('Remove from Favorites');
            $this->translationsTab['Mark as Favorite'] = $this->l('Mark as Favorite');
        }

        $linkAdminModules = $this->context->link->getAdminLink('AdminModules', true);
        $modulesOptions = [];

        $hasReset = false;
        $onclickOptions = [
            'desactive' => '',
            'reset' => '',
            'configure' => '',
            'delete' => 'return confirm(\''.$this->translationsTab['This action will permanently remove the module from the server. Are you sure you want to do this?'].'\');',
            'uninstall' =>  'return confirm(\''.$this->translationsTab['confirm_uninstall_popup'].'\');',
        ];

        if (Validate::isModuleName($module->name) && Module::isEnabled($module->name)) {
            $instance = Module::getInstanceByName($module->name);
            if ($instance) {
                // check if module has reset capability
                if (method_exists($instance, 'reset')) {
                    $hasReset = true;
                }

                // check if module provides custom onclick handlers
                if (method_exists($instance, 'onclickOption')) {
                    $href = Context::getContext()->link->getAdminLink('Module', true) . '&module_name=' . $instance->name . '&tab_module=' . $instance->tab;
                    foreach (array_keys($onclickOptions) as $opt) {
                        $onClick = $instance->onclickOption($opt, $href);
                        if ($onClick) {
                            $onclickOptions[$opt] = $onClick;
                        }
                    }
                }
            }
        }

        $configureModule = [
            'href'    => $linkAdminModules.'&configure='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.urlencode($module->name),
            'onclick' => $onclickOptions['configure'],
            'title'   => '',
            'text'    => $this->translationsTab['Configure'],
            'cond'    => $module->id && $module->is_configurable,
            'icon'    => 'wrench',
        ];

        $deactivateModule = [
            'href'    => $linkAdminModules.'&module_name='.urlencode($module->name).'&'.($module->active ? 'enable=0' : 'enable=1').'&tab_module='.$module->tab,
            'onclick' => $module->active ? $onclickOptions['desactive'] : '',
            'title'   => Shop::isFeatureActive() ? htmlspecialchars($module->active ? $this->translationsTab['Disable this module'] : $this->translationsTab['Enable this module for all shops']) : '',
            'text'    => $module->active ? $this->translationsTab['Disable'] : $this->translationsTab['Enable'],
            'cond'    => $module->id,
            'icon'    => 'off',
        ];
        $linkResetModule = $linkAdminModules.'&module_name='.urlencode($module->name).'&reset&tab_module='.$module->tab;



        $resetModule = [
            'href'    => $linkResetModule,
            'onclick' => $onclickOptions['reset'],
            'title'   => '',
            'text'    => $this->translationsTab['Reset'],
            'cond'    => $module->id && $module->active,
            'icon'    => 'undo',
            'class'   => ($hasReset ? 'reset_ready' : ''),
        ];

        $deleteModule = [
            'href'    => $linkAdminModules.'&delete='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.urlencode($module->name),
            'onclick' => $onclickOptions['delete'],
            'title'   => '',
            'text'    => $this->translationsTab['Delete'],
            'cond'    => file_exists(_PS_MODULE_DIR_ . $module->name) && is_dir(_PS_MODULE_DIR_ . $module->name),
            'icon'    => 'trash',
            'class'   => 'text-danger',
        ];

        $displayMobile = [
            'href'    => $linkAdminModules.'&module_name='.urlencode($module->name).'&'.($module->enable_device & Context::DEVICE_MOBILE ? 'disable_device' : 'enable_device').'='.Context::DEVICE_MOBILE.'&tab_module='.$module->tab,
            'onclick' => '',
            'title'   => htmlspecialchars($module->enable_device & Context::DEVICE_MOBILE ? $this->translationsTab['Disable on mobiles'] : $this->translationsTab['Display on mobiles']),
            'text'    => $module->enable_device & Context::DEVICE_MOBILE ? $this->translationsTab['Disable on mobiles'] : $this->translationsTab['Display on mobiles'],
            'cond'    => $module->id,
            'icon'    => 'mobile',
        ];

        $displayTablet = [
            'href'    => $linkAdminModules.'&module_name='.urlencode($module->name).'&'.($module->enable_device & Context::DEVICE_TABLET ? 'disable_device' : 'enable_device').'='.Context::DEVICE_TABLET.'&tab_module='.$module->tab,
            'onclick' => '',
            'title'   => htmlspecialchars($module->enable_device & Context::DEVICE_TABLET ? $this->translationsTab['Disable on tablets'] : $this->translationsTab['Display on tablets']),
            'text'    => $module->enable_device & Context::DEVICE_TABLET ? $this->translationsTab['Disable on tablets'] : $this->translationsTab['Display on tablets'],
            'cond'    => $module->id,
            'icon'    => 'tablet',
        ];

        $displayComputer = [
            'href'    => $linkAdminModules.'&module_name='.urlencode($module->name).'&'.($module->enable_device & Context::DEVICE_COMPUTER ? 'disable_device' : 'enable_device').'='.Context::DEVICE_COMPUTER.'&tab_module='.$module->tab,
            'onclick' => '',
            'title'   => htmlspecialchars($module->enable_device & Context::DEVICE_COMPUTER ? $this->translationsTab['Disable on computers'] : $this->translationsTab['Display on computers']),
            'text'    => $module->enable_device & Context::DEVICE_COMPUTER ? $this->translationsTab['Disable on computers'] : $this->translationsTab['Display on computers'],
            'cond'    => $module->id,
            'icon'    => 'desktop',
        ];

        $install = [
            'href'    => $linkAdminModules.'&install='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name),
            'onclick' => '',
            'title'   => $this->translationsTab['Install'],
            'text'    => $this->translationsTab['Install'],
            'cond'    => $module->id,
            'icon'    => 'plus-sign-alt',
        ];

        $uninstall = [
            'href'    => $linkAdminModules.'&uninstall='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name),
            'onclick' => $onclickOptions['uninstall'],
            'title'   => $this->translationsTab['Uninstall'],
            'text'    => $this->translationsTab['Uninstall'],
            'cond'    => $module->id,
            'icon'    => 'minus-sign-alt',
        ];

        $removeFromFavorite = [
            'href'        => '#',
            'class'       => 'action_unfavorite toggle_favorite',
            'onclick'     => '',
            'title'       => $this->translationsTab['Remove from Favorites'],
            'text'        => $this->translationsTab['Remove from Favorites'],
            'cond'        => $module->id,
            'icon'        => 'star',
            'data-value'  => '0',
            'data-module' => $module->name,
        ];

        $markAsFavorite = [
            'href'        => '#',
            'class'       => 'action_favorite toggle_favorite',
            'onclick'     => '',
            'title'       => $this->translationsTab['Mark as Favorite'],
            'text'        => $this->translationsTab['Mark as Favorite'],
            'cond'        => $module->id,
            'icon'        => 'star',
            'data-value'  => '1',
            'data-module' => $module->name,
        ];

        $update = [
            'href'    => $module->options['update_url'],
            'onclick' => '',
            'title'   => 'Update it!',
            'text'    => 'Update it!',
            'icon'    => 'refresh',
            'cond'    => $module->id,
        ];

        $url = [
            'href' => $module->url ?? '',
            'onclick' => '',
            'target'  => '_blank',
            'title'   => $this->l('Visit module page'),
            'text'    => $this->l('Visit module page'),
            'cond'    => isset($module->url) && $module->url,
            'icon'    => 'link',
        ];

        $divider = [
            'href'    => '#',
            'onclick' => '',
            'title'   => 'divider',
            'text'    => 'divider',
            'cond'    => $module->id,
        ];

        if (isset($module->version_addons) && $module->version_addons) {
            $modulesOptions[] = $update;
        }

        if ($module->active) {
            $modulesOptions[] = $configureModule;
            $modulesOptions[] = $deactivateModule;
            $modulesOptions[] = $displayMobile;
            $modulesOptions[] = $displayTablet;
            $modulesOptions[] = $displayComputer;
        } else {
            $modulesOptions[] = $deactivateModule;
            $modulesOptions[] = $configureModule;
        }

        $modulesOptions[] = $resetModule;


        if ($module->id) {
            $modulesOptions[] = $uninstall;
        }

        if (isset($module->preferences['favorite']) && $module->preferences['favorite'] == 1) {
            $removeFromFavorite['style'] = '';
            $markAsFavorite['style'] = 'display:none;';
            $modulesOptions[] = $removeFromFavorite;
            $modulesOptions[] = $markAsFavorite;
        } else {
            $markAsFavorite['style'] = '';
            $removeFromFavorite['style'] = 'display:none;';
            $modulesOptions[] = $removeFromFavorite;
            $modulesOptions[] = $markAsFavorite;
        }

        if ($module->id == 0) {
            $install['cond'] = 1;
            $install['flag_install'] = 1;
            $modulesOptions[] = $install;
        }
        $modulesOptions[] = $url;
        $modulesOptions[] = $divider;
        $modulesOptions[] = $deleteModule;

        $return = [];
        foreach ($modulesOptions as $option) {
            if ($option['cond']) {

                $html = '<a class="';

                $isInstall = isset($option['flag_install']);

                if (isset($option['class'])) {
                    $html .= $option['class'];
                }
                if ($isInstall) {
                    $html .= ' btn btn-success';
                }
                if (!$isInstall && count($return) == 0) {
                    $html .= ' btn btn-default';
                }

                $html .= '"';

                if (isset($option['data-value'])) {
                    $html .= ' data-value="'.$option['data-value'].'"';
                }

                if (isset($option['data-module'])) {
                    $html .= ' data-module="'.$option['data-module'].'"';
                }

                if (isset($option['style'])) {
                    $html .= ' style="'.$option['style'].'"';
                }

                if (isset($option['target'])) {
                    $html .= ' target="'.$option['target'].'"';
                }

                $html .= ' href="'.htmlentities($option['href']).'" onclick="'.$option['onclick'].'"  title="'.$option['title'].'"><i class="icon-'.(isset($option['icon']) && $option['icon'] ? $option['icon'] : 'cog').'"></i> '.$option['text'].'</a>';
                $return[] = $html;
            }
        }

        return $return;
    }

    /**
     * @return void
     */
    public function renderKpis()
    {
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
        Hook::triggerEvent(
            'action'.$this->controller_name.'OptionsModifier', [
                'options'     => &$this->fields_options,
                'option_vars' => &$this->tpl_option_vars,
            ]
        );

        if ($this->fields_options && is_array($this->fields_options)) {
            if (isset($this->display) && $this->display != 'options' && $this->display != 'list') {
                $this->show_toolbar = false;
            } else {
                $this->display = 'options';
            }

            unset($this->toolbar_btn);
            $this->initToolbar();
            $helper = new HelperOptions();
            $this->setHelperDisplay($helper);
            $helper->id = $this->id;
            $helper->tpl_vars = $this->tpl_option_vars;
            $options = $helper->generateOptions($this->fields_options);

            return $options;
        }
    }

    /**
     * Prepare the view to display the required fields form
     *
     * @return string|void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayRequiredFields()
    {
        if (!$this->hasAddPermission() || !$this->hasDeletePermission() || !$this->required_database) {
            return;
        }

        $helper = new Helper();
        $helper->currentIndex = static::$currentIndex;
        $helper->token = $this->token;
        $helper->override_folder = $this->override_folder;

        return $helper->renderRequiredFields($this->className, $this->identifier, $this->required_fields);
    }

    /**
     * Initialize the invalid doom page of death
     *
     * @return void
     */
    public function initCursedPage()
    {
        $this->layout = 'invalid_token.tpl';
    }

    /**
     * Assign smarty variables for the footer
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function initFooter()
    {
        //RTL Support
        //rtl.js overrides inline styles
        //iso_code.css overrides default fonts for every language (optional)
        if ($this->context->language->is_rtl) {
            $this->addJS(_PS_JS_DIR_.'rtl.js');
            $this->addCSS(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$this->bo_theme.'/css/'.$this->context->language->iso_code.'.css', 'all', false);
        }

        // We assign js and css files on the last step before display template, because controller can add many js and css files
        $this->context->smarty->assign('css_files', $this->css_files);
        $this->context->smarty->assign('js_files', array_unique($this->js_files));

        $supporter = Configuration::getSupporterInfo();
        $this->context->smarty->assign(
            [
                'ps_version'   => _TB_VERSION_,
                'timer_start'  => $this->timer_start,
                'iso_is_fr'    => strtoupper($this->context->language->iso_code) == 'FR',
                'modals'       => $this->renderModal(),
                'showBecomeSupporterButton' => !$supporter,
                'becomeSupporterUrl' => Configuration::getBecomeSupporterUrl(),
            ]
        );
    }

    /**
     * @return string
     * @throws SmartyException
     */
    public function renderModal()
    {
        $modal_render = '';
        if (is_array($this->modals) && count($this->modals)) {
            foreach ($this->modals as $modal) {
                $this->context->smarty->assign($modal);
                $modal_render .= $this->context->smarty->fetch('modal.tpl');
            }
        }

        return $modal_render;
    }

    /**
     * @deprecated
     */
    public function setDeprecatedMedia()
    {
    }

    /**
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        //Bootstrap
        $this->addCSS(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$this->bo_theme.'/css/'.$this->bo_css, 'all', 0);
        $this->addCSS(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$this->bo_theme.'/css/overrides.css', 'all', PHP_INT_MAX);

        $this->addJquery();
        $this->addjQueryPlugin(['scrollTo', 'alerts', 'chosen', 'autosize', 'fancybox']);
        $this->addjQueryPlugin('growl', null, false);
        $this->addJqueryUI(['ui.slider', 'ui.datepicker']);

        Media::addJsDef(['currencyFormatters' => Currency::getJavascriptFormatters()]);

        $this->addJS(
            [
                _PS_JS_DIR_.'admin.js',
                _PS_JS_DIR_.'tools.js',
                _PS_JS_DIR_.'jquery/plugins/timepicker/jquery-ui-timepicker-addon.js',
            ]
        );

        //loads specific javascripts for the admin theme
        $this->addJS(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$this->bo_theme.'/js/vendor/bootstrap.min.js');
        $this->addJS(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$this->bo_theme.'/js/vendor/modernizr.min.js');
        $this->addJS(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$this->bo_theme.'/js/vendor/enquire.min.js');
        $this->addJS(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$this->bo_theme.'/js/vendor/moment-with-langs.min.js');
        $this->addJS(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$this->bo_theme.'/js/admin-theme.js');

        if (!$this->lite_display) {
            $this->addJS(__PS_BASE_URI__.$this->admin_webpath.'/themes/'.$this->bo_theme.'/js/help.js');
        }

        if (!Tools::getValue('submitFormAjax')) {
            $this->addJS(_PS_JS_DIR_.'admin/notifications.js');
        }

        $this->addSyntheticSchedulerJs();

        // Execute Hook AdminController SetMedia
        Hook::triggerEvent('actionAdminControllerSetMedia');
    }

    /**
     * Init context and dependencies, handles POST and GET
     *
     * @throws PrestaShopException
     */
    public function init()
    {
        // Has to be removed for the next Prestashop version
        global $currentIndex;

        parent::init();

        if (Tools::getValue('ajax')) {
            $this->ajax = '1';
        }

        /* Server Params */
        $protocol_link = (Tools::usingSecureMode() && Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
        $protocol_content = (Tools::usingSecureMode() && Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';

        $this->context->link = new Link($protocol_link, $protocol_content);

        if (isset($_GET['logout'])) {
            $this->context->employee->logout();
        }

        if (isset($this->context->cookie->last_activity)) {
            $shortExpire = defined('_TB_COOKIE_SHORT_EXPIRE_') ? _TB_COOKIE_SHORT_EXPIRE_ : 900;
            if ((int) $this->context->cookie->last_activity + (int) $shortExpire < time()) {
                $this->context->employee->logout();
            } else {
                $this->context->cookie->last_activity = time();
            }
        }

        if ($this->controller_name != 'AdminLogin' && (!isset($this->context->employee) || !$this->context->employee->isLoggedBack())) {
            if (isset($this->context->employee)) {
                $this->context->employee->logout();
            }

            $email = false;
            if (Tools::getValue('email') && Validate::isEmail(Tools::getValue('email'))) {
                $email = Tools::getValue('email');
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminLogin').((!isset($_GET['logout']) && $this->controller_name != 'AdminNotFound' && Tools::getValue('controller')) ? '&redirect='.$this->controller_name : '').($email ? '&email='.$email : ''));
        }

        // Set current index
        $current_index = 'index.php'.(($controller = Tools::getValue('controller')) ? '?controller='.$controller : '');
        if ($back = Tools::getValue('back')) {
            $current_index .= '&back='.urlencode($back);
        }
        static::$currentIndex = $current_index;
        $currentIndex = $current_index;

        if (Tools::getIntValue('liteDisplaying')) {
            $this->display_header = false;
            $this->display_header_javascript = true;
            $this->display_footer = false;
            $this->content_only = false;
            $this->lite_display = true;
        }

        if ($this->ajax && method_exists($this, 'ajaxPreprocess')) {
            $this->ajaxPreProcess();
        }

        $this->context->smarty->assign(
            [
                'table'            => $this->table,
                'current'          => static::$currentIndex,
                'token'            => $this->token,
                'stock_management' => (int) Configuration::get('PS_STOCK_MANAGEMENT'),
            ]
        );

        if ($this->display_header) {
            $this->context->smarty->assign('displayBackOfficeHeader', Hook::displayHook('displayBackOfficeHeader'));
        }

        $this->context->smarty->assign(
            [
                'displayBackOfficeTop' => Hook::displayHook('displayBackOfficeTop'),
                'submit_form_ajax'     => Tools::getIntValue('submitFormAjax'),
            ]
        );

        Employee::setLastConnectionDate($this->context->employee->id);

        $this->initProcess();
        $this->initBreadcrumbs();
        $this->initModal();
    }

    /**
     * Retrieve GET and POST value and translate them to actions
     *
     * @return void
     */
    public function initProcess()
    {
        $this->ensureListIdDefinition();

        // Manage list filtering
        if (Tools::isSubmit('submitFilter'.$this->list_id)
            || $this->context->cookie->{'submitFilter'.$this->list_id} !== false
            || Tools::getValue($this->list_id.'Orderby')
            || Tools::getValue($this->list_id.'Orderway')
            || Tools::isSubmit('submitFilterForced')
        ) {
            $this->filter = true;
        }

        $this->id_object = Tools::getIntValue($this->identifier);

        /* Delete object image */
        if (isset($_GET['deleteImage'])) {
            if ($this->hasDeletePermission()) {
                $this->action = 'delete_image';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        } elseif (isset($_GET['delete'.$this->table])) {
            /* Delete object */
            if ($this->hasDeletePermission()) {
                $this->action = 'delete';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        } elseif ((isset($_GET['status'.$this->table]) || isset($_GET['status'])) && Tools::getValue($this->identifier)) {
            /* Change object statuts (active, inactive) */
            if ($this->hasEditPermission()) {
                $this->action = 'status';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (isset($_GET['position'])) {
            /* Move an object */
            if ($this->hasEditPermission()) {
                $this->action = 'position';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitAdd'.$this->table)
            || Tools::isSubmit('submitAdd'.$this->table.'AndStay')
            || Tools::isSubmit('submitAdd'.$this->table.'AndPreview')
            || Tools::isSubmit('submitAdd'.$this->table.'AndBackToParent')
        ) {
            // case 1: updating existing entry
            if ($this->id_object) {
                if ($this->hasEditPermission()) {
                    $this->action = 'save';
                    if (Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
                        $this->display = 'edit';
                    } else {
                        $this->display = 'list';
                    }
                } else {
                    $this->errors[] = Tools::displayError('You do not have permission to edit this.');
                }
            } else {
                // case 2: creating new entry
                if ($this->hasAddPermission()) {
                    $this->action = 'save';
                    if (Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
                        $this->display = 'edit';
                    } else {
                        $this->display = 'list';
                    }
                } else {
                    $this->errors[] = Tools::displayError('You do not have permission to add this.');
                }
            }
        } elseif (isset($_GET['add'.$this->table])) {
            if ($this->hasAddPermission()) {
                $this->action = 'new';
                $this->display = 'add';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        } elseif (isset($_GET['update'.$this->table]) && isset($_GET[$this->identifier])) {
            $this->display = 'edit';
            if (! $this->hasEditPermission()) {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (isset($_GET['view'.$this->table])) {
            if ($this->hasViewPermission()) {
                $this->display = 'view';
                $this->action = 'view';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to view this.');
            }
        } elseif (isset($_GET['details'.$this->table])) {
            if ($this->hasViewPermission()) {
                $this->display = 'details';
                $this->action = 'details';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to view this.');
            }
        } elseif (isset($_GET['export'.$this->table])) {
            if ($this->hasViewPermission()) {
                $this->action = 'export';
            }
        } elseif (isset($_POST['submitReset'.$this->list_id])) {
            /* Cancel all filters for this tab */
            $this->action = 'reset_filters';
        } elseif (Tools::isSubmit('submitOptions'.$this->table) || Tools::isSubmit('submitOptions')) {
            /* Submit options list */
            $this->display = 'options';
            if ($this->hasEditPermission()) {
                $this->action = 'update_options';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::getValue('action') && method_exists($this, 'process'.ucfirst(Tools::toCamelCase(Tools::getValue('action'))))) {
            $this->action = Tools::getValue('action');
        } elseif (Tools::isSubmit('submitFields') && $this->required_database && $this->hasAddPermission() && $this->hasDeletePermission()) {
            $this->action = 'update_fields';
        } elseif (is_array($this->bulk_actions)) {
            $submit_bulk_actions = array_merge(
                [
                    'enableSelection'  => [
                        'text' => $this->l('Enable selection'),
                        'icon' => 'icon-power-off text-success',
                    ],
                    'disableSelection' => [
                        'text' => $this->l('Disable selection'),
                        'icon' => 'icon-power-off text-danger',
                    ],
                ], $this->bulk_actions
            );
            foreach ($submit_bulk_actions as $bulk_action => $params) {
                if (Tools::isSubmit('submitBulk'.$bulk_action.$this->table) || Tools::isSubmit('submitBulk'.$bulk_action)) {
                    if ($bulk_action === 'delete') {
                        if ($this->hasDeletePermission()) {
                            $this->action = 'bulk'.$bulk_action;
                            $this->boxes = Tools::getArrayValue($this->table.'Box');
                            if (empty($this->boxes) && $this->table == 'attribute') {
                                $this->boxes = Tools::getArrayValue($this->table.'_valuesBox');
                            }
                        } else {
                            $this->errors[] = Tools::displayError('You do not have permission to delete this.');
                        }
                        break;
                    } elseif ($this->hasEditPermission()) {
                        $this->action = 'bulk'.$bulk_action;
                        $this->boxes = Tools::getArrayValue($this->table.'Box');
                    } else {
                        $this->errors[] = Tools::displayError('You do not have permission to edit this.');
                    }
                    break;
                } elseif (Tools::isSubmit('submitBulk')) {
                    if ($bulk_action === 'delete') {
                        if ($this->hasDeletePermission()) {
                            $this->action = 'bulk'.$bulk_action;
                            $this->boxes = Tools::getArrayValue($this->table.'Box');
                        } else {
                            $this->errors[] = Tools::displayError('You do not have permission to delete this.');
                        }
                        break;
                    } elseif ($this->hasEditPermission()) {
                        $this->action = 'bulk'.Tools::getValue('select_submitBulk');
                        $this->boxes = Tools::getArrayValue($this->table.'Box');
                    } else {
                        $this->errors[] = Tools::displayError('You do not have permission to edit this.');
                    }
                    break;
                }
            }
        } elseif (!empty($this->fields_options) && empty($this->fields_list)) {
            $this->display = 'options';
        }
    }

    /**
     * Set breadcrumbs array for the controller page
     *
     * @param int|null $tabId
     * @param array|null $tabs
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initBreadcrumbs($tabId = null, $tabs = null)
    {
        if (is_null($tabId)) {
            $tabId = $this->id;
        }

        $tabs = Tab::recursiveTab($tabId);

        $dummy = ['name' => '', 'href' => '', 'icon' => ''];
        $breadcrumbs2 = [
            'container' => $dummy,
            'tab'       => $dummy,
            'action'    => $dummy,
        ];
        if (isset($tabs[0])) {
            $this->addMetaTitle($tabs[0]['name']);
            $breadcrumbs2['tab']['name'] = $tabs[0]['name'];
            $breadcrumbs2['tab']['href'] = __PS_BASE_URI__.basename(_PS_ADMIN_DIR_).'/'.$this->context->link->getAdminLink($tabs[0]['class_name']);
            if (!isset($tabs[1])) {
                $breadcrumbs2['tab']['icon'] = 'icon-'.$tabs[0]['class_name'];
            }
        }
        if (isset($tabs[1])) {
            $breadcrumbs2['container']['name'] = $tabs[1]['name'];
            $breadcrumbs2['container']['href'] = __PS_BASE_URI__.basename(_PS_ADMIN_DIR_).'/'.$this->context->link->getAdminLink($tabs[1]['class_name']);
            $breadcrumbs2['container']['icon'] = 'icon-'.$tabs[1]['class_name'];
        }

        /* content, edit, list, add, details, options, view */
        switch ($this->display) {
            case 'add':
                $breadcrumbs2['action']['name'] = $this->l('Add', null, null, false);
                $breadcrumbs2['action']['icon'] = 'icon-plus';
                break;
            case 'edit':
                $breadcrumbs2['action']['name'] = $this->l('Edit', null, null, false);
                $breadcrumbs2['action']['icon'] = 'icon-pencil';
                break;
            case '':
            case 'list':
                $breadcrumbs2['action']['name'] = $this->l('List', null, null, false);
                $breadcrumbs2['action']['icon'] = 'icon-th-list';
                break;
            case 'details':
            case 'view':
                $breadcrumbs2['action']['name'] = $this->l('View details', null, null, false);
                $breadcrumbs2['action']['icon'] = 'icon-zoom-in';
                break;
            case 'options':
                $breadcrumbs2['action']['name'] = $this->l('Options', null, null, false);
                $breadcrumbs2['action']['icon'] = 'icon-cogs';
                break;
            case 'generator':
                $breadcrumbs2['action']['name'] = $this->l('Generator', null, null, false);
                $breadcrumbs2['action']['icon'] = 'icon-flask';
                break;
        }

        $this->context->smarty->assign(
            [
                'breadcrumbs2'                   => $breadcrumbs2,
                'quick_access_current_link_name' => $breadcrumbs2['tab']['name'].(isset($breadcrumbs2['action']) ? ' - '.$breadcrumbs2['action']['name'] : ''),
                'quick_access_current_link_icon' => $breadcrumbs2['container']['icon'],
            ]
        );

        /* BEGIN - Backward compatibility < 1.6.0.3 */
        if (isset($tabs[0])) {
            $this->breadcrumbs[] = $tabs[0]['name'];
        }
        $navigationPipe = (Configuration::get('PS_NAVIGATION_PIPE') ? Configuration::get('PS_NAVIGATION_PIPE') : '>');
        $this->context->smarty->assign('navigationPipe', $navigationPipe);
        /* END - Backward compatibility < 1.6.0.3 */
    }

    /**
     * @return void
     */
    public function initModal()
    {
        $this->context->smarty->assign(
            [
                'check_url_fopen'             => (ini_get('allow_url_fopen') ? 'ok' : 'ko'),
                'check_openssl'               => (extension_loaded('openssl') ? 'ok' : 'ko'),
                'add_permission'              => 1,
            ]
        );
    }

    /**
     * Display object details
     *
     * @return void
     */
    public function viewDetails()
    {
    }

    /**
     * Shortcut to set up a json success payload
     *
     * @param string $message Success message
     */
    public function jsonConfirmation($message)
    {
        $this->json = true;
        $this->confirmations[] = $message;
        if ($this->status === '') {
            $this->status = 'ok';
        }
    }

    /**
     * Shortcut to set up a json error payload
     *
     * @param string $message Error message
     */
    public function jsonError($message)
    {
        $this->json = true;
        $this->errors[] = $message;
        if ($this->status === '') {
            $this->status = 'error';
        }
    }

    /**
     * @deprecated 1.5.0
     */
    public function ajaxProcessGetModuleQuickView()
    {
        Tools::displayAsDeprecated();
    }

    /**
     * Update options and preferences
     *
     * @throws PrestaShopException
     */
    protected function processUpdateOptions()
    {
        $this->beforeUpdateOptions();

        $languages = Language::getLanguages(false);

        $hideMultishopCheckbox = (Shop::getTotalShops(false, null) < 2) ? true : false;
        foreach ($this->fields_options as $categoryData) {
            if (!isset($categoryData['fields'])) {
                continue;
            }

            $fields = $categoryData['fields'];

            foreach ($fields as $field => $values) {
                if (isset($values['type']) && $values['type'] == 'selectLang') {
                    foreach ($languages as $lang) {
                        if (Tools::getValue($field.'_'.strtoupper($lang['iso_code']))) {
                            $fields[$field.'_'.strtoupper($lang['iso_code'])] = [
                                'type'       => 'select',
                                'cast'       => 'strval',
                                'identifier' => 'mode',
                                'list'       => $values['list'],
                            ];
                        }
                    }
                }
            }

            // Cast and validate fields.
            foreach ($fields as $field => $values) {

                // ignore doNotProcess fields
                if (isset($options['doNotProcess']) && $options['doNotProcess']) {
                    continue;
                }

                // We don't validate fields with no visibility
                if (!$hideMultishopCheckbox && Shop::isFeatureActive() && isset($values['visibility']) && $values['visibility'] > Shop::getContext()) {
                    continue;
                }

                // Apply cast before validating.
                if (array_key_exists('cast', $values)) {
                    $cast = $values['cast'];
                    if (array_key_exists('type', $values)
                        && in_array($values['type'], [
                            'textLang',
                            'textareaLang',
                        ])) {
                        foreach ($languages as $language) {
                            $langField = $field . '_' . $language['id_lang'];
                            $_POST[$langField] = Tools::castInput($cast, Tools::getValue($langField));
                        }
                    } else {
                        $_POST[$field] = Tools::castInput($cast, Tools::getValue($field));
                    }
                }

                // Check if field is required
                if ((!Shop::isFeatureActive() && isset($values['required']) && $values['required'])
                    || (Shop::isFeatureActive() && isset($_POST['multishopOverrideOption'][$field]) && isset($values['required']) && $values['required'])
                ) {
                    if (isset($values['type']) && $values['type'] == 'textLang') {
                        foreach ($languages as $language) {
                            if (($value = Tools::getValue($field.'_'.$language['id_lang'])) == false && (string) $value != '0') {
                                $this->errors[] = sprintf(Tools::displayError('field %s is required.'), $values['title']);
                            }
                        }
                    } elseif (($value = Tools::getValue($field)) == false && (string) $value != '0') {
                        $this->errors[] = sprintf(Tools::displayError('field %s is required.'), $values['title']);
                    }
                }

                // Check field validator
                if (isset($values['type']) && $values['type'] == 'textLang') {
                    foreach ($languages as $language) {
                        if (Tools::getValue($field.'_'.$language['id_lang']) && isset($values['validation'])) {
                            $valuesValidation = $values['validation'];
                            if (!Validate::$valuesValidation(Tools::getValue($field.'_'.$language['id_lang']))) {
                                $this->errors[] = sprintf(Tools::displayError('field %s is invalid.'), $values['title']);
                            }
                        }
                    }
                } elseif (Tools::getValue($field) && isset($values['validation'])) {
                    $valuesValidation = $values['validation'];
                    if (!Validate::$valuesValidation(Tools::getValue($field))) {
                        $this->errors[] = sprintf(Tools::displayError('field %s is invalid.'), $values['title']);
                    }
                }

                // Set default value
                if (Tools::getValue($field) === false && isset($values['default'])) {
                    $_POST[$field] = $values['default'];
                }
            }

            if (!count($this->errors)) {
                foreach ($fields as $key => $options) {

                    // ignore doNotProcess fields
                    if (isset($options['doNotProcess']) && $options['doNotProcess']) {
                        continue;
                    }

                    if (Shop::isFeatureActive() && isset($options['visibility']) && $options['visibility'] > Shop::getContext()) {
                        continue;
                    }

                    if (!$hideMultishopCheckbox && Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_ALL && empty($options['no_multishop_checkbox']) && empty($_POST['multishopOverrideOption'][$key])) {
                        Configuration::deleteFromContext($key);
                        continue;
                    }

                    // check if a method updateOptionFieldName is available
                    $methodName = 'updateOption'.Tools::toCamelCase($key, true);
                    if (method_exists($this, $methodName)) {
                        $this->$methodName(Tools::getValue($key));
                    } elseif (isset($options['type']) && in_array($options['type'], ['textLang', 'textareaLang'])) {
                        $list = [];
                        foreach ($languages as $language) {
                            $val = Tools::getValue($key.'_'.$language['id_lang']);
                            if ($this->validateField($val, $options)) {
                                if (Validate::isCleanHtml($val)) {
                                    $list[$language['id_lang']] = $val;
                                } else {
                                    $this->errors[] = Tools::displayError('Can not add configuration '.$key.' for lang '.Language::getIsoById((int) $language['id_lang']));
                                }
                            }
                        }
                        Configuration::updateValue($key, $list, isset($values['validation']) && isset($options['validation']) && $options['validation'] == 'isCleanHtml' ? true : false);
                    } else {
                        $isCodeField = $options['type'] === 'code';
                        $val = $isCodeField ? Tools::getValueRaw($key) : Tools::getValue($key);
                        if ($this->validateField($val, $options)) {
                            if ($isCodeField) {
                                Configuration::updateValueRaw($key, $val);
                            } elseif (Validate::isCleanHtml($val)) {
                                Configuration::updateValue($key, $val);
                            } else {
                                $this->errors[] = Tools::displayError('Can not add configuration '.$key);
                            }
                        }
                    }
                }
            }
        }

        $this->display = 'list';
        if (empty($this->errors)) {
            $this->confirmations[] = $this->_conf[6];
        }
    }

    /**
     * Can be overridden
     *
     * @return void
     */
    public function beforeUpdateOptions()
    {
    }

    /**
     * @param mixed $value
     * @param array $field
     *
     * @return bool
     */
    protected function validateField($value, $field)
    {
        if (isset($field['validation'])) {
            $valid_method_exists = method_exists('Validate', $field['validation']);
            if ((!isset($field['empty']) || !$field['empty'] || $value) && $valid_method_exists) {
                $field_validation = $field['validation'];
                if (!Validate::$field_validation($value)) {
                    $this->errors[] = Tools::displayError($field['title'].' : Incorrect value');

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return void
     */
    protected function redirect()
    {
        if ($this->errors || $this->warnings
            || $this->informations || $this->confirmations) {
            $token = Tools::getValue('token');
            $messageCachePath = _PS_CACHE_DIR_.'/'.static::MESSAGE_CACHE_PATH
                                .'-'.$token;

            file_put_contents($messageCachePath, '<?php
                $this->errors = '.var_export($this->errors, true).';
                $this->warnings = '.var_export($this->warnings, true).';
                $this->informations = '.var_export($this->informations, true).';
                $this->confirmations = '.var_export($this->confirmations, true).';
            ');
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($messageCachePath);
            }
        }

        Tools::redirectAdmin($this->redirect_after);
    }

    /**
     * Add a info message to display at the top of the page
     *
     * @param string $msg
     */
    protected function displayInformation($msg)
    {
        $this->informations[] = $msg;
    }

    /**
     * Delete multiple items
     *
     * @return bool true if success
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processBulkDelete()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $result = true;
            foreach ($this->boxes as $id) {
                $id = (int)$id;
                /** @var ObjectModel $objectToDelete */
                $objectToDelete = new $this->className($id);
                if (Validate::isLoadedObject($objectToDelete)) {
                    if ($this->deleted && property_exists($objectToDelete, 'deleted')) {
                        $objectToDelete->deleted = 1;
                        $deleted = $objectToDelete->update();
                    } else {
                        $deleted = $objectToDelete->delete();
                    }
                } else {
                    $deleted = false;
                }

                if ($deleted) {
                    Logger::addLog(sprintf($this->l('%s deletion', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int)$objectToDelete->id, true, (int)$this->context->employee->id);
                } else {
                    $result = false;
                    $this->errors[] = sprintf(Tools::displayError('Can\'t delete #%d'), $id);
                }
            }
            if ($result) {
                $this->redirect_after = static::$currentIndex . '&conf=2&token=' . $this->token;
            } else {
                $this->errors[] = Tools::displayError('An error occurred while deleting this selection.');
            }
        } else {
            $this->errors[] = Tools::displayError('You must select at least one element to delete.');
        }

        if (isset($result)) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * @throws PrestaShopException
     */
    protected function ajaxProcessOpenHelp()
    {
        $help_class_name = $_GET['controller'];
        $popup_content = "<!doctype html>
		<html>
			<head>
				<meta charset='UTF-8'>
				<title>thirty bees Help</title>
				<link href='//help.thirtybees.com/css/help.css' rel='stylesheet'>
				<link href='//fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet'>
				<script src='"._PS_JS_DIR_."jquery/jquery-1.11.0.min.js'></script>
				<script src='"._PS_JS_DIR_."admin.js'></script>
				<script src='"._PS_JS_DIR_."tools.js'></script>
				<script>
					help_class_name='".addslashes($help_class_name)."';
					iso_user = '".addslashes($this->context->language->iso_code)."'
				</script>
				<script src='themes/default/js/help.js'></script>
				<script>
					$(function(){
						initHelp();
					});
				</script>
			</head>
			<body><div id='help-container' class='help-popup'></div></body>
		</html>";
        $this->ajaxDie($popup_content);
    }

    /**
     * Enable multiple items
     *
     * @return bool true if success
     *
     * @throws PrestaShopException
     */
    protected function processBulkEnableSelection()
    {
        return $this->processBulkStatusSelection(1);
    }

    /**
     * Toggle status of multiple items
     *
     * @param bool $status
     *
     * @return bool true if success
     *
     * @throws PrestaShopException
     */
    protected function processBulkStatusSelection($status)
    {
        $result = true;
        if (is_array($this->boxes) && !empty($this->boxes)) {
            foreach ($this->boxes as $id) {
                /** @var ObjectModel $object */
                $object = new $this->className((int) $id);
                if (property_exists($object, 'active')) {
                    $object->setFieldsToUpdate(['active' => true]);
                    $object->active = (int)$status;
                    $result = $object->update() && $result;
                } else {
                    throw new PrestaShopException('property "active" is missing in object '.$this->className);
                }
            }
        }

        return $result;
    }

    /**
     * Disable multiple items
     *
     * @return bool true if success
     *
     * @throws PrestaShopException
     */
    protected function processBulkDisableSelection()
    {
        return $this->processBulkStatusSelection(0);
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processBulkAffectZone()
    {
        $result = false;
        if (is_array($this->boxes) && !empty($this->boxes)) {
            /** @var Country|State $object */
            $object = new $this->className();
            $result = $object->affectZoneToSelection(Tools::getArrayValue($this->table.'Box'), Tools::getIntValue('zone_to_affect'));

            if ($result) {
                $this->redirect_after = static::$currentIndex.'&conf=28&token='.$this->token;
            }
            $this->errors[] = Tools::displayError('An error occurred while assigning a zone to the selection.');
        } else {
            $this->errors[] = Tools::displayError('You must select at least one element to assign a new zone.');
        }

        return $result;
    }

    /**
     * Adds javascript URI to list of javascript files included in page header
     *
     * @param string $uri uri to javascript file
     * @param boolean $checkPath if true, system will check if the javascript file exits on filesystem
     */
    public function addJavascriptUri($uri, $checkPath)
    {
        parent::addJavascriptUri(Media::getUriWithVersion($uri), $checkPath);
    }

    /**
     * Adds a new stylesheet(s) to the page header.
     *
     * @param string|array $cssUri Path to CSS file, or list of css files like this : array(array(uri => media_type), ...)
     * @param string $cssMediaType
     * @param int|null $offset
     * @param bool $checkPath
     *
     * @return bool
     */
    public function addCSS($cssUri, $cssMediaType = 'all', $offset = null, $checkPath = true)
    {
        if (!is_array($cssUri)) {
            $cssUri = [$cssUri => $cssMediaType];
        }

        $converted = [];
        foreach ($cssUri as $cssFile => $media) {
            if (is_string($cssFile) && strlen($cssFile) > 1) {
                $converted[Media::getUriWithVersion($cssFile)] = $media;
            } else {
                $converted[Media::getUriWithVersion($media)] = $cssMediaType;
            }
        }
        return parent::addCSS($converted, $cssMediaType, $offset, $checkPath);
    }

    /**
     * Method that allows controllers to define their own custom permissions. To be overridden by subclasses

     * Returns array of permission definitions. Example entry:
     *
     *  [
     *       ...
     *      [
     *          "permission" => 'action-buttons",
     *          "name" => "Buttons available to employee"
     *          "description" => "Here you can choose what action buttons can employee use"
     *          "levels" => [
     *              ...
     *              'none' => 'No buttons available',
     *              'invoice' => 'Employee can generate invoice',
     *              'send_email' => 'Employee can send email'
     *              'all' => 'Employee can use all buttons'
     *              ...
     *          ],
     *          "defaultLevel" => 'all'
     *      ]
     *      ...
     *  ]
     *
     * Controllers are responsible for enforcing selected permissions -- permission levels for current employee
     * can be retrieved by calling method getPermLevels
     *
     * @return array
     */
    public function getPermDefinitions()
    {
        return [];
    }

    /**
     * Returns permission levels for current employee. Returns map: permission -> level
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getPermLevels()
    {
        $perms = $this->getPermDefinitions();
        $levels = [];
        if ($perms) {
            $profileId = $this->context->employee->id_profile;
            $group = preg_replace("#Controller$#", "", preg_replace("#Core$#", "", get_class($this)));
            foreach ($perms as $def) {
                $permission = $def['permission'];
                $level = Profile::getProfilePermission($profileId, $group, $permission);
                if ($level === false) {
                    $levels[$permission] = $def['defaultLevel'];
                } else {
                    $levels[$permission] = $level;
                }
            }
        }
        return $levels;
    }

    /**
     * Extracts information about custom permissions from all admin controllers
     *
     * This method iterates over all php files in /controllers/admin directory, and use reflection to checks
     * if controller overrides method AdminControllerCore::getPermissions()
     *
     * For every controller that overrides permission, new instance is created and this method is called to retrieve
     * list of additional permissions
     *
     * @throws PrestaShopException
     */
    public static function getControllersPermissions()
    {
        $permissions = [];
        $iterator = new FilesystemIterator(_PS_ADMIN_CONTROLLER_DIR_);
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile() && preg_match('#(.*)Controller\.php$#', $file->getFilename(), $matches)) {
                $controllerName = $matches[1];
                $className = $controllerName . 'Controller';
                try {
                    $reflection = new ReflectionMethod($className, 'getPermDefinitions');
                    if ($reflection->getDeclaringClass()->getName() != AdminControllerCore::class) {
                        /** @var AdminControllerCore $instance - subclass of admin controller */
                        $instance = new $className();
                        $permissions[$controllerName] = $instance->getPermDefinitions();
                    }
                } catch (ReflectionException $e) {
                    throw new PrestaShopException("Failed to resolve permissions for admin controller " . $controllerName, 0, $e);
                }
            }
        }
        return $permissions;
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    protected function getBackUrlParameter(): string
    {
        $back = Tools::safeOutput(Tools::getValue('back', ''));
        if (empty($back)) {
            $back = static::$currentIndex . '&token=' . $this->token;
        }
        if (!Validate::isCleanHtml($back)) {
            throw new PrestaShopException(Tools::displayError('Parameter $back is invalid'));
        }
        return $back;
    }

    /**
     * @return bool
     */
    protected function hasDeletePermission()
    {
        return $this->hasPermission(Profile::PERMISSION_DELETE);
    }

    /**
     * Returns true, if current employee can create new records
     *
     * @return bool
     */
    protected function hasAddPermission()
    {
        return $this->hasPermission(Profile::PERMISSION_ADD);
    }

    /**
     * Returns true, if current employee has view permissions
     *
     * @return bool
     */
    protected function hasViewPermission()
    {
        return $this->hasPermission(Profile::PERMISSION_VIEW);
    }

    /**
     * Returns true, if current employee can edit existing records
     *
     * @return bool
     */
    protected function hasEditPermission()
    {
        return $this->hasPermission(Profile::PERMISSION_EDIT);
    }

    /**
     * Returns true, if current employee has permission level
     *
     * @param string $permission
     *
     * @return bool
     */
    protected function hasPermission($permission)
    {
        if (! Profile::isValidPermission($permission)) {
            return false;
        }
        if (! isset($this->tabAccess[$permission])) {
            return false;
        }
        return (bool)$this->tabAccess[$permission];
    }

    /**
     * @param string|null $orderBy
     *
     * @return string|null
     */
    protected function resolveOrderBy(?string $orderBy)
    {
        if (! empty($orderBy)) {
            return $orderBy;
        }
        $prefix = $this->getCookieFilterPrefix();
        if ($this->context->cookie->{$prefix . $this->list_id . 'Orderby'}) {
            return $this->context->cookie->{$prefix . $this->list_id . 'Orderby'};
        }
        if ($this->_orderBy) {
            return $this->_orderBy;
        }
        return $this->_defaultOrderBy;
    }

    /**
     * @param string|null $orderWay
     *
     * @return string|null
     */
    protected function resolveOrderWay(?string $orderWay)
    {
        if (! empty($orderWay)) {
            return $orderWay;
        }
        $prefix = $this->getCookieFilterPrefix();
        if ($this->context->cookie->{$prefix.$this->list_id.'Orderway'}) {
            return $this->context->cookie->{$prefix.$this->list_id.'Orderway'};
        }
        if ($this->_orderWay) {
            return $this->_orderWay;
        }
        return $this->_defaultOrderWay;
    }

    /**
     * @param string $field
     *
     * @return array|bool|float|int|string|null
     */
    protected function getListFieldFilterValue($field)
    {
        $filterName = $this->table . 'Filter_' . $field;
        if (Tools::getIsset($filterName)) {
            return Tools::getValue($filterName);
        }
        $cookieFilterName = $this->getCookieFilterPrefix() . $filterName;
        if (isset($this->context->cookie->$cookieFilterName)) {
            return $this->context->cookie->$cookieFilterName;
        }
        return null;
    }

    /**
     * @return void
     */
    protected function setJSendErrorHandling()
    {
        $this->postProcessHandleExceptions = false;
        static::getErrorHandler()->setErrorResponseHandler(new JSendErrorResponse(_PS_MODE_DEV_));
    }
}
