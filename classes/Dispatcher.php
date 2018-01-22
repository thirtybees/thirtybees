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
 * Class DispatcherCore
 *
 * @since 1.0.0
 */
class DispatcherCore
{
    /**
     * List of available front controllers types
     */
    const FC_FRONT = 1;
    const FC_ADMIN = 2;
    const FC_MODULE = 3;

    // @codingStandardsIgnoreStart
    /**
     * @var Dispatcher
     */
    public static $instance = null;

    /**
     * @var array List of default routes
     */
    public $default_routes = [
        'category_rule' => [
            'controller' => 'category',
            'rule' => '{categories:/}{rewrite}',
            'keywords' => [
                'id' => [
                    'regexp' => '[0-9]+',
                    'alias'  => 'id_category',
                ],
                'rewrite' => [
                    'regexp' => '[_a-zA-Z0-9\pL\pS-]*',
                    'param' => 'rewrite',
                ],
                'categories' => [
                    'regexp' => '[/_a-zA-Z0-9-\pL]*',
                ],
                'meta_keywords' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
                'meta_title' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
            ],
        ],
        'supplier_rule' => [
            'controller' => 'supplier',
            'rule' => '{rewrite}',
            'keywords' => [
                'id' => [
                    'regexp' => '[0-9]+',
                    'alias'  => 'id_supplier',
                ],
                'rewrite' => [
                    'regexp' => '[_a-zA-Z0-9\pL\pS-]*',
                    'param' => 'rewrite',
                ],
                'meta_keywords' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
                'meta_title' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
            ],
        ],
        'manufacturer_rule' => [
            'controller' => 'manufacturer',
            'rule' => 'manufacturer/{rewrite}',
            'keywords' => [
                'id' => [
                    'regexp' => '[0-9]+',
                    'alias'  => 'id_manufacturer',
                ],
                'rewrite' => [
                    'regexp' => '[_a-zA-Z0-9\pL\pS-]*',
                    'param' => 'rewrite',
                ],
                'meta_keywords' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
                'meta_title' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
            ],
        ],
        'cms_rule' => [
            'controller' => 'cms',
            'rule' => 'info/{categories:/}{rewrite}',
            'keywords' => [
                'id' => [
                    'regexp' => '[0-9]+',
                    'alias'  => 'id_cms',
                ],
                'rewrite' => [
                    'regexp' => '[_a-zA-Z0-9\pL\pS-]*',
                    'param' => 'cms_rewrite',
                ],
                'categories' => [
                    'regexp' => '[/_a-zA-Z0-9-\pL]*',
                ],
                'meta_keywords' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
                'meta_title' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
            ],
        ],
        'cms_category_rule' => [
            'controller' => 'cms',
            'rule' => 'info/{categories:/}{rewrite}',
            'keywords' => [
                'id' => [
                    'regexp' => '[0-9]+',
                    'alias'  => 'id_cms_category'
                ],
                'rewrite' => [
                    'regexp' => '[_a-zA-Z0-9\pL\pS-]*',
                    'param' => 'cms_cat_rewrite',
                ],
                'categories' => [
                    'regexp' => '[/_a-zA-Z0-9-\pL]*',
                ],
                'meta_keywords' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
                'meta_title' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
            ],
        ],
        'module' => [
            'controller' => null,
            'rule' => 'module/{module}{/:controller}',
            'keywords' => [
                'module' => [
                    'regexp' => '[_a-zA-Z0-9_-]+',
                    'param' => 'module',
                ],
                'controller' => [
                    'regexp' => '[_a-zA-Z0-9_-]+',
                    'param' => 'controller',
                ],
            ],
            'params' => [
                'fc' => 'module',
            ],
        ],
        'product_rule' => [
            'controller' => 'product',
            'rule' => '{categories:/}{rewrite}',
            'keywords' => [
                'id' => [
                    'regexp' => '[0-9]+',
                    'alias'  => 'id_product',
                ],
                'rewrite' => [
                    'regexp' => '[_a-zA-Z0-9\pL\pS-]*',
                    'param' => 'rewrite',
                ],
                'ean13' => [
                    'regexp' => '[0-9\pL]*',
                ],
                'category' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
                'categories' => [
                    'regexp' => '[/_a-zA-Z0-9-\pL]*',
                ],
                'reference' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
                'meta_keywords' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
                'meta_title' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
                'manufacturer' => [
                    'regexp' => '[_a-zA-Z09-\pL]*',
                ],
                'supplier' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
                'price' => [
                    'regexp' => '[0-9\.,]*',
                ],
                'tags' => [
                    'regexp' => '[a-zA-Z0-9-\pL]*',
                ],
            ],
        ],
        'layered_rule' => [
            'controller' => 'category',
            'rule' => '{rewrite}{/:selected_filters}',
            'keywords' => [
                'id' => [
                    'regexp' => '[0-9]+',
                    'alias'  => 'id_category',
                ],
                'selected_filters' => [
                    'regexp' => '.*',
                    'param' => 'selected_filters',
                ],
                'rewrite' => [
                    'regexp' => '[_a-zA-Z0-9\pL\pS-]*',
                    'param' => 'rewrite',
                ],
                'categories' => [
                    'regexp' => '[/_a-zA-Z0-9-\pL]*',
                ],
                'meta_keywords' => [
                    'regexp' => '[_a-zA-0-9-\pL]*',
                ],
                'meta_title' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
            ],
        ],
    ];

    /**
     * @var bool If true, use routes to build URL (mod rewrite must be activated)
     */
    protected $use_routes = false;

    protected $multilang_activated = false;

    /**
     * @var array List of loaded routes
     */
    public $routes = [];

    /**
     * @var string Current controller name
     */
    protected $controller;

    /**
     * @var string Current request uri
     */
    protected $request_uri;

    /**
     * @var array Store empty route (a route with an empty rule)
     */
    protected $empty_route;

    /**
     * @var string Set default controller, which will be used if http parameter 'controller' is empty
     */
    protected $default_controller;
    protected $use_default_controller = false;

    /**
     * @var string Controller to use if found controller doesn't exist
     */
    protected $controller_not_found = 'pagenotfound';

    /**
     * @var string Front controller to use
     */
    protected $front_controller = self::FC_FRONT;
    // @codingStandardsIgnoreEnd

    /**
     * Get current instance of dispatcher (singleton)
     *
     * @return Dispatcher
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @param string $routeId    Name of the route (need to be unique, a second route with same name will override the first)
     * @param string $rule       Url rule
     * @param string $controller Controller to call if request uri match the rule
     * @param int    $idLang
     * @param array  $keywords
     * @param array  $params
     * @param int    $idShop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addRoute($routeId, $rule, $controller, $idLang = null, array $keywords = [], array $params = [], $idShop = null)
    {
        if (isset(Context::getContext()->language) && $idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }

        if (isset(Context::getContext()->shop) && $idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        if (!$rule && in_array($routeId, array_keys($this->default_routes))) {
            $rule = $this->default_routes[$routeId]['rule'];
        }

        $regexp = preg_quote($rule, '#');
        if ($keywords) {
            $transformKeywords = [];
            preg_match_all('#\\\{(([^{}]*)\\\:)?('.implode('|', array_keys($keywords)).')(\\\:([^{}]*))?\\\}#', $regexp, $m);
            for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
                $prepend = $m[2][$i];
                $keyword = $m[3][$i];
                $append = $m[5][$i];
                $transformKeywords[$keyword] = [
                    'required' => isset($keywords[$keyword]['param']),
                    'prepend'  => stripslashes($prepend),
                    'append'   => stripslashes($append),
                ];

                $prependRegexp = $appendRegexp = '';
                if ($prepend || $append) {
                    $prependRegexp = '('.$prepend;
                    $appendRegexp = $append.')?';
                }

                if (isset($keywords[$keyword]['param']) && $keywords[$keyword]['param']) {
                    $regexp = str_replace($m[0][$i], $prependRegexp.'(?P<'.$keywords[$keyword]['param'].'>'.$keywords[$keyword]['regexp'].')'.$appendRegexp, $regexp);
                } elseif ($keyword === 'id') {
                    $regexp = str_replace($m[0][$i], $prependRegexp.'(?P<id>'.$keywords[$keyword]['regexp'].')'.$appendRegexp, $regexp);
                } else {
                    $regexp = str_replace($m[0][$i], $prependRegexp.'('.$keywords[$keyword]['regexp'].')'.$appendRegexp, $regexp);
                }
            }
            $keywords = $transformKeywords;
        }

        $regexp = '#^/'.$regexp.'$#u';
        if (!isset($this->routes[$idShop])) {
            $this->routes[$idShop] = [];
        }
        if (!isset($this->routes[$idShop][$idLang])) {
            $this->routes[$idShop][$idLang] = [];
        }

        $this->routes[$idShop][$idLang][$routeId] = [
            'rule'       => $rule,
            'regexp'     => $regexp,
            'controller' => $controller,
            'keywords'   => $keywords,
            'params'     => $params,
        ];
    }

    /**
     * Get list of all available Module Front controllers
     *
     * @param string $type
     * @param null   $module
     *
     * @return array
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getModuleControllers($type = 'all', $module = null)
    {
        $modulesControllers = [];
        if (is_null($module)) {
            $modules = Module::getModulesOnDisk(true);
        } elseif (!is_array($module)) {
            $modules = [Module::getInstanceByName($module)];
        } else {
            $modules = [];
            foreach ($module as $_mod) {
                $modules[] = Module::getInstanceByName($_mod);
            }
        }

        foreach ($modules as $mod) {
            foreach (Dispatcher::getControllersInDirectory(_PS_MODULE_DIR_.$mod->name.'/controllers/') as $controller) {
                if ($type == 'admin') {
                    if (strpos($controller, 'Admin') !== false) {
                        $modulesControllers[$mod->name][] = $controller;
                    }
                } elseif ($type == 'front') {
                    if (strpos($controller, 'Admin') === false) {
                        $modulesControllers[$mod->name][] = $controller;
                    }
                } else {
                    $modulesControllers[$mod->name][] = $controller;
                }
            }
        }

        return $modulesControllers;
    }

    /**
     * Needs to be instantiated from getInstance() method
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function __construct()
    {
        $this->use_routes = (bool) Configuration::get('PS_REWRITING_SETTINGS');

        // Select right front controller
        if (defined('_PS_ADMIN_DIR_')) {
            $this->front_controller = static::FC_ADMIN;
            $this->controller_not_found = 'adminnotfound';
        } elseif (Tools::getValue('fc') == 'module') {
            $this->front_controller = static::FC_MODULE;
            $this->controller_not_found = 'pagenotfound';
        } else {
            $this->front_controller = static::FC_FRONT;
            $this->controller_not_found = 'pagenotfound';
        }

        $this->setRequestUri();

        // Switch language if needed (only on front)
        if (in_array($this->front_controller, [static::FC_FRONT, static::FC_MODULE])) {
            Tools::switchLanguage();
        }

        if (Language::isMultiLanguageActivated()) {
            $this->multilang_activated = true;
        }

        $this->loadRoutes();
    }

    /**
     * Set request uri and iso lang
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function setRequestUri()
    {
        // Get request uri (HTTP_X_REWRITE_URL is used by IIS)
        if (isset($_SERVER['REQUEST_URI'])) {
            $this->request_uri = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $this->request_uri = $_SERVER['HTTP_X_REWRITE_URL'];
        }
        $this->request_uri = rawurldecode($this->request_uri);

        if (isset(Context::getContext()->shop) && is_object(Context::getContext()->shop)) {
            $this->request_uri = preg_replace('#^'.preg_quote(Context::getContext()->shop->getBaseURI(), '#').'#i', '/', $this->request_uri);
        }

        // If there are several languages, get language from uri
        if ($this->use_routes && Language::isMultiLanguageActivated()) {
            if (preg_match('#^/([a-z]{2})(?:/.*)?$#', $this->request_uri, $m)) {
                $_GET['isolang'] = $m[1];
                $this->request_uri = substr($this->request_uri, 3);
            }
        }
    }

    /**
     * Load default routes group by languages
     *
     * @param int|null $idShop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function loadRoutes($idShop = null)
    {
        // Load custom routes from modules
        $modulesRoutes = Hook::exec('moduleRoutes', ['id_shop' => $idShop], null, true, false);
        if (is_array($modulesRoutes) && count($modulesRoutes)) {
            foreach ($modulesRoutes as $moduleRoute) {
                foreach ($moduleRoute as $route => $routeDetails) {
                    if (array_key_exists('controller', $routeDetails) && array_key_exists('rule', $routeDetails)
                        && array_key_exists('keywords', $routeDetails) && array_key_exists('params', $routeDetails)
                    ) {
                        if (!isset($this->default_routes[$route])) {
                            $this->default_routes[$route] = [];
                        }
                        $this->default_routes[$route] = array_merge($this->default_routes[$route], $routeDetails);
                    }
                }
            }
        }

        // Set rules and old keywords
        $prodroutes = 'PS_ROUTE_product_rule';
        $catroutes = 'PS_ROUTE_category_rule';
        $supproutes = 'PS_ROUTE_supplier_rule';
        $manuroutes = 'PS_ROUTE_manufacturer_rule';
        $layeredroutes = 'PS_ROUTE_layered_rule';
        $cmsroutes = 'PS_ROUTE_cms_rule';
        $cmscatroutes = 'PS_ROUTE_cms_category_rule';
        $moduleroutes = 'PS_ROUTE_module';

        // Set new routes
        foreach (Language::getLanguages() as $lang) {
            foreach ($this->default_routes as $id => $route) {
                switch ($id) {
                    case 'product_rule':
                        $rule = Configuration::get($prodroutes, (int) $lang['id_lang']);
                        break;
                    case 'category_rule':
                        $rule = Configuration::get($catroutes, (int) $lang['id_lang']);
                        break;
                    case 'supplier_rule':
                        $rule = Configuration::get($supproutes, (int) $lang['id_lang']);
                        break;
                    case 'manufacturer_rule':
                        $rule = Configuration::get($manuroutes, (int) $lang['id_lang']);
                        break;
                    case 'layered_rule':
                        $rule = Configuration::get($layeredroutes, (int) $lang['id_lang']);
                        break;
                    case 'cms_rule':
                        $rule = Configuration::get($cmsroutes, (int) $lang['id_lang']);
                        break;
                    case 'cms_category_rule':
                        $rule = Configuration::get($cmscatroutes, (int) $lang['id_lang']);
                        break;
                    case 'module':
                        $rule = Configuration::get($moduleroutes, (int) $lang['id_lang']);
                        break;
                    default:
                        $rule = $route['rule'];
                        break;
                }

                $this->addRoute(
                    $id,
                    $rule,
                    $route['controller'],
                    $lang['id_lang'],
                    $route['keywords'],
                    isset($route['params']) ? $route['params'] : [],
                    $idShop
                );
            }
        }

        // Load the custom routes prior the defaults to avoid infinite loops
        if ($this->use_routes) {
            /* Load routes from meta table */
            if ($results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('m.`page`, ml.`url_rewrite`, ml.`id_lang`')
                    ->from('meta', 'm')
                    ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta` '.Shop::addSqlRestrictionOnLang('ml', $idShop))
                    ->orderBy('LENGTH(ml.`url_rewrite`) DESC')
            )) {
                foreach ($results as $row) {
                    if ($row['url_rewrite']) {
                        $this->addRoute(
                            $row['page'],
                            $row['url_rewrite'],
                            $row['page'],
                            $row['id_lang'],
                            [],
                            [],
                            $idShop
                        );
                    }
                }
            }

            foreach (Language::getLanguages(false, false, true) as $idLang) {
                // Set favicon.ico route
                $this->addRoute(
                    'favicon',
                    'favicon.ico',
                    'favicon',
                    $idLang,
                    [],
                    [],
                    $idShop
                );

                // Set apple-touch-icon.png route
                $this->addRoute(
                    'apple-touch-icon',
                    'apple-touch-icon.png',
                    'favicon',
                    $idLang,
                    [],
                    [
                        'icon'        => 'apple-touch-icon',
                        'precomposed' => false,
                    ],
                    $idShop
                );

                // Set apple-touch-icon.png route
                $this->addRoute(
                    'apple-touch-icon-precomposed',
                    'apple-touch-icon-precomposed.png',
                    'favicon',
                    $idLang,
                    [],
                    [
                        'icon'        => 'apple-touch-icon',
                        'precomposed' => true,
                    ],
                    $idShop
                );

                // Set apple-touch-icon-width-height.png route
                $this->addRoute(
                    'apple-touch-icon-size',
                    'apple-touch-icon-{width}x{height}.png',
                    'favicon',
                    $idLang,
                    [
                        'width'  => [
                            'regexp' => '[0-9]+',
                            'param'  => 'width',
                        ],
                        'height' => [
                            'regexp' => '[0-9]+',
                            'param'  => 'height',
                        ],
                    ],
                    [
                        'icon'        => 'apple-touch-icon',
                        'precomposed' => false,
                    ],
                    $idShop
                );

                // Set apple-touch-icon-width-height-precomposed.png route
                $this->addRoute(
                    'apple-touch-icon-size-precomposed',
                    'apple-touch-icon-{width}x{height}-precomposed.png',
                    'favicon',
                    $idLang,
                    [
                        'width'  => [
                            'regexp' => '[0-9]+',
                            'param'  => 'width',
                        ],
                        'height' => [
                            'regexp' => '[0-9]+',
                            'param'  => 'height',
                        ],
                    ],
                    [
                        'icon'        => 'apple-touch-icon',
                        'precomposed' => true,
                    ],
                    $idShop
                );
            }

            // Set default empty route if no empty route (that's weird I know)
            if (!$this->empty_route) {
                $this->empty_route = [
                    'routeID' => 'index',
                    'rule' => '',
                    'controller' => 'index',
                ];
            }
        }
    }

    /**
     * Find the controller and instantiate it
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function dispatch()
    {
        $controllerClass = '';

        // Get current controller
        $this->getController();
        if (!$this->controller) {
            $this->controller = $this->useDefaultController();
        }
        // Dispatch with right front controller
        switch ($this->front_controller) {
            // Dispatch front office controller
            case static::FC_FRONT:
                $controllers = Dispatcher::getControllers([_PS_FRONT_CONTROLLER_DIR_, _PS_OVERRIDE_DIR_.'controllers/front/']);
                $controllers['index'] = 'IndexController';
                if (isset($controllers['auth'])) {
                    $controllers['authentication'] = $controllers['auth'];
                }
                if (isset($controllers['compare'])) {
                    $controllers['productscomparison'] = $controllers['compare'];
                }
                if (isset($controllers['contact'])) {
                    $controllers['contactform'] = $controllers['contact'];
                }

                if (!isset($controllers[strtolower($this->controller)])) {
                    $this->controller = $this->controller_not_found;
                }
                $controllerClass = $controllers[strtolower($this->controller)];
                $paramsHookActionDispatcher = ['controller_type' => static::FC_FRONT, 'controller_class' => $controllerClass, 'is_module' => 0];
                break;

            // Dispatch module controller for front office and ajax
            case static::FC_MODULE:
                $moduleName = Validate::isModuleName(Tools::getValue('module')) ? Tools::getValue('module') : '';
                $module = Module::getInstanceByName($moduleName);
                $controllerClass = 'PageNotFoundController';
                if (Validate::isLoadedObject($module) && $module->active) {
                    $controllers = Dispatcher::getControllers(_PS_MODULE_DIR_.$moduleName.'/controllers/front/');
                    if (isset($controllers[strtolower($this->controller)])) {
                        include_once(_PS_MODULE_DIR_.$moduleName.'/controllers/front/'.$this->controller.'.php');
                        $controllerClass = $moduleName.$this->controller.'ModuleFrontController';
                    }

                    $ajaxControllers = Dispatcher::getControllers(_PS_MODULE_DIR_.$moduleName.'/controllers/ajax/');
                    if (isset($ajaxControllers[strtolower($this->controller)])) {
                        include_once(_PS_MODULE_DIR_.$moduleName.'/controllers/ajax/'.$this->controller.'.php');
                        $controllerClass = $moduleName.$this->controller.'ModuleAjaxController';
                    }
                }
                $paramsHookActionDispatcher = ['controller_type' => static::FC_FRONT, 'controller_class' => $controllerClass, 'is_module' => 1];
                break;

            // Dispatch back office controller + module back office controller
            case static::FC_ADMIN:
                if ($this->use_default_controller && !Tools::getValue('token') && Validate::isLoadedObject(Context::getContext()->employee) && Context::getContext()->employee->isLoggedBack()) {
                    Tools::redirectAdmin('index.php?controller='.$this->controller.'&token='.Tools::getAdminTokenLite($this->controller));
                }

                $tab = Tab::getInstanceFromClassName($this->controller, Configuration::get('PS_LANG_DEFAULT'));
                $retrocompatibilityAdminTab = null;

                if ($tab->module) {
                    if (file_exists(_PS_MODULE_DIR_.$tab->module.'/'.$tab->class_name.'.php')) {
                        $retrocompatibilityAdminTab = _PS_MODULE_DIR_.$tab->module.'/'.$tab->class_name.'.php';
                    } else {
                        $controllers = Dispatcher::getControllers(_PS_MODULE_DIR_.$tab->module.'/controllers/admin/');
                        if (!isset($controllers[strtolower($this->controller)])) {
                            $this->controller = $this->controller_not_found;
                            $controllerClass = 'AdminNotFoundController';
                        } else {
                            // Controllers in modules can be named AdminXXX.php or AdminXXXController.php
                            include_once(_PS_MODULE_DIR_.$tab->module.'/controllers/admin/'.$controllers[strtolower($this->controller)].'.php');
                            $controllerClass = $controllers[strtolower($this->controller)].(strpos($controllers[strtolower($this->controller)], 'Controller') ? '' : 'Controller');
                        }
                    }
                    $paramsHookActionDispatcher = ['controller_type' => static::FC_ADMIN, 'controller_class' => $controllerClass, 'is_module' => 1];
                } else {
                    $controllers = Dispatcher::getControllers([_PS_ADMIN_DIR_.'/tabs/', _PS_ADMIN_CONTROLLER_DIR_, _PS_OVERRIDE_DIR_.'controllers/admin/']);
                    if (!isset($controllers[strtolower($this->controller)])) {
                        // If this is a parent tab, load the first child
                        if (Validate::isLoadedObject($tab) && $tab->id_parent == 0 && ($tabs = Tab::getTabs(Context::getContext()->language->id, $tab->id)) && isset($tabs[0])) {
                            Tools::redirectAdmin(Context::getContext()->link->getAdminLink($tabs[0]['class_name']));
                        }
                        $this->controller = $this->controller_not_found;
                    }

                    $controllerClass = $controllers[strtolower($this->controller)];
                    $paramsHookActionDispatcher = ['controller_type' => static::FC_ADMIN, 'controller_class' => $controllerClass, 'is_module' => 0];

                    if (file_exists(_PS_ADMIN_DIR_.'/tabs/'.$controllerClass.'.php')) {
                        $retrocompatibilityAdminTab = _PS_ADMIN_DIR_.'/tabs/'.$controllerClass.'.php';
                    }
                }

                // @retrocompatibility with admin/tabs/ old system
                if ($retrocompatibilityAdminTab) {
                    include_once($retrocompatibilityAdminTab);
                    include_once(_PS_ADMIN_DIR_.'/functions.php');
                    runAdminTab($this->controller, !empty($_REQUEST['ajaxMode']));

                    return;
                }
                break;

            default:
                throw new PrestaShopException('Bad front controller chosen');
        }

        // Instantiate controller
        try {
            // Loading controller
            $controller = Controller::getController($controllerClass);

            // Execute hook dispatcher
            if (isset($paramsHookActionDispatcher)) {
                Hook::exec('actionDispatcher', $paramsHookActionDispatcher);
            }

            // Running controller
            $controller->run();
        } catch (PrestaShopException $e) {
            $e->displayMessage();
        }
    }

    /**
     * Retrieve the controller from url or request uri if routes are activated
     *
     * @param int|null $idShop
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getController($idShop = null)
    {
        $context = Context::getContext();

        // Get the controller directly on admin pages
        if (isset($context->employee->id) && $context->employee->id) {
            $_GET['controllerUri'] = Tools::getvalue('controller');
        }
        if ($this->controller) {
            $_GET['controller'] = $this->controller;

            return $this->controller;
        }


        list($uri) = explode('?', $this->request_uri);
        if (isset(Context::getContext()->shop) && $idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }
        $controller = Tools::getValue('controller');
        if (isset($controller) && is_string($controller)) {
            if (preg_match('/^([0-9a-z_-]+)\?(.*)=(.*)$/Ui', $controller, $m)) {
                $controller = $m[1];
                if (isset($_GET['controller'])) {
                    $_GET[$m[2]] = $m[3];
                } else {
                    if (isset($_POST['controller'])) {
                        $_POST[$m[2]] = $m[3];
                    }
                }
            } elseif (!$this->use_routes && Validate::isControllerName($controller) && Tools::isSubmit('id_'.$controller)) {
                $id = Tools::getValue('id_'.$controller);
                $_GET['id_'.$controller] = $id;
                $this->controller = $controller;

                return $this->controller;
            }
        }
        if (!Validate::isControllerName($controller)) {
            $controller = false;
        }
        if ($this->use_routes && !$controller && !defined('_PS_ADMIN_DIR_')) {
            if (!$this->request_uri) {
                return mb_strtolower($this->controller_not_found);
            }

            // Check basic controllers & params
            $controller = $this->controller_not_found;
            $testRequestUri = preg_replace('/(=http:\/\/)/', '=', $this->request_uri);
            if (!preg_match('/\.(css|js)$/i', parse_url($testRequestUri, PHP_URL_PATH))) {
                // Add empty route as last route to prevent this greedy regexp to match request uri before right time
                if ($this->empty_route) {
                    $this->addRoute(
                        $this->empty_route['routeID'],
                        $this->empty_route['rule'],
                        $this->empty_route['controller'],
                        Context::getContext()->language->id,
                        [],
                        [],
                        $idShop
                    );
                }
                list($uri) = explode('?', $this->request_uri);
                if (isset($this->routes[$idShop][Context::getContext()->language->id])) {
                    $routes = $this->routes[$idShop][Context::getContext()->language->id];

                    foreach ($routes as $route) {
                        if (preg_match($route['regexp'], $uri, $m)) {
                            // Try to recover the IDs which were previously unique
                            // It will also tell us if the route leads to something
                            // Skip this whole part if the url isn't rewritten
                            if (array_key_exists('rewrite', $m)) {
                                if ($route['controller'] === 'category') {
                                    if (isset($m['id']) && $m['id']) {
                                        $idCategory = (int) $m['id'];
                                    } else {
                                        $idCategory = $this->categoryID($m['rewrite'], $uri);
                                        if (!$idCategory) {
                                            $idCategory = in_array('id_category', $m) ? (int) $m['id_category'] : 0;
                                            if (!$idCategory) {
                                                continue;
                                            }
                                        }
                                    }
                                    $_GET['id_category'] = $idCategory;
                                }
                                if ($route['controller'] === 'product') {
                                    if (isset($m['id']) && $m['id']) {
                                        $idProduct = (int) $m['id'];
                                    } else {
                                        $idProduct = $this->productID($m['rewrite'], $uri);
                                        if (!$idProduct) {
                                            $idProduct = in_array('id_product', $m) ? (int) $m['id_product'] : 0;
                                            if (!$idProduct) {
                                                continue;
                                            }
                                        }
                                    }
                                    $_GET['id_product'] = $idProduct;
                                }
                                if ($route['controller'] === 'supplier') {
                                    if (isset($m['id']) && $m['id']) {
                                        $idSupplier = (int) $m['id'];
                                    } else {
                                        $idSupplier = $this->supplierID($m['rewrite']);
                                        if (!$idSupplier) {
                                            $idSupplier = in_array('id_supplier', $m) ? (int) $m['id_supplier'] : 0;
                                            if (!$idSupplier) {
                                                continue;
                                            }
                                        }
                                    }
                                    $_GET['id_supplier'] = $idSupplier;
                                }
                                if ($route['controller'] === 'manufacturer') {
                                    if (isset($m['id']) && $m['id']) {
                                        $idManufacturer = (int) $m['id'];
                                    } else {
                                        $idManufacturer = $this->manufacturerID($m['rewrite']);
                                        if (!$idManufacturer) {
                                            $idManufacturer = in_array('id_manufacturer', $m) ? (int) $m['id_manufacturer'] : 0;
                                            if (!$idManufacturer) {
                                                continue;
                                            }
                                        }
                                    }
                                    $_GET['id_manufacturer'] = $idManufacturer;
                                }
                            }
                            if (array_key_exists('cms_cat_rewrite', $m)) {
                                if ($route['controller'] === 'cms') {
                                    if (isset($m['id']) && $m['id']) {
                                        $idCmsCat = (int) $m['id'];
                                    } else {
                                        $idCmsCat = $this->cmsCategoryID($m['cms_cat_rewrite'], $uri);
                                        if (!$idCmsCat) {
                                            $idCmsCat = in_array('id_cms_category', $m) ? (int) $m['id_cms_category'] : 0;
                                            if (!$idCmsCat) {
                                                continue;
                                            }
                                        }
                                    }
                                    $_GET['id_cms_category'] = $idCmsCat;
                                }
                            }
                            if (array_key_exists('cms_rewrite', $m)) {
                                if ($route['controller'] === 'cms') {
                                    if (isset($m['id']) && $m['id']) {
                                        $idCms = (int) $m['id'];
                                    } else {
                                        $idCms = $this->cmsID($m['cms_rewrite'], $uri);
                                        if (!$idCms) {
                                            $idCms = in_array('id_cms', $m) ? (int) $m['id_cms'] : 0;
                                            if (!$idCms) {
                                                continue;
                                            }
                                        }
                                    }
                                    $_GET['id_cms'] = $idCms;
                                }
                            }
                            $isModule = isset($route['params']['fc']) && $route['params']['fc'] === 'module';
                            foreach ($m as $k => $v) {
                                // We might have us an external module page here, in that case we set whatever we can
                                if (!is_numeric($k) &&
                                    ($isModule
                                        || $k !== 'id'
                                        && $k !== 'ipa'
                                        && $k !== 'rewrite'
                                        && $k !== 'cms_rewrite'
                                        && $k !== 'cms_cat_rewrite'
                                    )) {
                                        $_GET[$k] = $v;
                                }
                            }
                            $controller = $route['controller'] ? $route['controller'] : $_GET['controller'];
                            if (!empty($route['params'])) {
                                foreach ($route['params'] as $k => $v) {
                                    $_GET[$k] = $v;
                                }
                            }

                            // A patch for module friendly urls
                            if (preg_match('#module-([a-z0-9_-]+)-([a-z0-9_]+)$#i', $controller, $m)) {
                                $_GET['module'] = $m[1];
                                $_GET['fc'] = 'module';
                                $controller = $m[2];
                            }
                            if (isset($_GET['fc']) && $_GET['fc'] == 'module') {
                                $this->front_controller = self::FC_MODULE;
                            }
                            break;
                        }
                    }
                }
            }

            // Check if index
            if ($controller == 'index' || preg_match('/^\/index.php(?:\?.*)?$/', $this->request_uri)
                || $uri == ''
            ) {
                $controller = $this->useDefaultController();
            }
        }
        $this->controller = str_replace('-', '', $controller);
        $_GET['controller'] = $this->controller;

        return $this->controller;
    }

    /**
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function useDefaultController()
    {
        $this->use_default_controller = true;
        if ($this->default_controller === null) {
            if (defined('_PS_ADMIN_DIR_')) {
                if (isset(Context::getContext()->employee) && Validate::isLoadedObject(Context::getContext()->employee) && isset(Context::getContext()->employee->default_tab)) {
                    $this->default_controller = Tab::getClassNameById((int) Context::getContext()->employee->default_tab);
                }
                if (empty($this->default_controller)) {
                    $this->default_controller = 'AdminDashboard';
                }
            } elseif (Tools::getValue('fc') == 'module') {
                $this->default_controller = 'default';
            } else {
                $this->default_controller = 'index';
            }
        }

        return $this->default_controller;
    }

    /**
     * Get list of all available FO controllers
     *
     * @param mixed $dirs
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getControllers($dirs)
    {
        if (!is_array($dirs)) {
            $dirs = [$dirs];
        }

        $controllers = [];
        foreach ($dirs as $dir) {
            $controllers = array_merge($controllers, Dispatcher::getControllersInDirectory($dir));
        }

        return $controllers;
    }

    /**
     * Get list of available controllers from the specified dir
     *
     * @param string $dir Directory to scan (recursively)
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getControllersInDirectory($dir)
    {
        if (!is_dir($dir)) {
            return [];
        }

        $controllers = [];
        $controllerFiles = scandir($dir);
        foreach ($controllerFiles as $controllerFilename) {
            if ($controllerFilename[0] != '.') {
                if (!strpos($controllerFilename, '.php') && is_dir($dir.$controllerFilename)) {
                    $controllers += Dispatcher::getControllersInDirectory($dir.$controllerFilename.DIRECTORY_SEPARATOR);
                } elseif ($controllerFilename != 'index.php') {
                    $key = str_replace(['controller.php', '.php'], '', strtolower($controllerFilename));
                    $controllers[$key] = basename($controllerFilename, '.php');
                }
            }
        }

        return $controllers;
    }

    /**
     * Check if a route exists
     *
     * @param string $routeId
     * @param int    $idLang
     * @param int    $idShop
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function hasRoute($routeId, $idLang = null, $idShop = null)
    {
        if (isset(Context::getContext()->language) && $idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }
        if (isset(Context::getContext()->shop) && $idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        return isset($this->routes[$idShop]) && isset($this->routes[$idShop][$idLang]) && isset($this->routes[$idShop][$idLang][$routeId]);
    }

    /**
     * Check if a keyword is written in a route rule
     *
     * @param string $routeId
     * @param int    $idLang
     * @param string $keyword
     * @param int    $idShop
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function hasKeyword($routeId, $idLang, $keyword, $idShop = null)
    {
        if ($idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        if (!isset($this->routes[$idShop])) {
            $this->loadRoutes($idShop);
        }

        if (!isset($this->routes[$idShop]) || !isset($this->routes[$idShop][$idLang]) || !isset($this->routes[$idShop][$idLang][$routeId])) {
            return false;
        }

        return preg_match('#\{([^{}]*:)?'.preg_quote($keyword, '#').'(:[^{}]*)?\}#', $this->routes[$idShop][$idLang][$routeId]['rule']);
    }

    /**
     * Check if a route rule contain all required keywords of default route definition
     *
     * @param string $routeId
     * @param string $rule    Rule to verify
     * @param array  $errors  List of missing keywords
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     */
    public function validateRoute($routeId, $rule, &$errors = [])
    {
        $errors = [];
        if (!isset($this->default_routes[$routeId])) {
            return false;
        }

        foreach ($this->default_routes[$routeId]['keywords'] as $keyword => $data) {
            if ($this->use_routes && $keyword === 'id') {
                continue;
            }
            if ($this->use_routes && $keyword === 'rewrite') {
                $data['param'] = true;
            }

            if (isset($data['param']) && !preg_match('#\{([^{}]*:)?'.$keyword.'(:[^{}]*)?\}#', $rule)) {
                $errors[] = $keyword;
            }
        }

        return (count($errors)) ? false : true;
    }

    /**
     * Create an url from
     *
     * @param string $routeId     Name of the route
     * @param int    $idLang
     * @param array  $params
     * @param bool   $forceRoutes
     * @param string $anchor      Optional anchor to add at the end of this url
     * @param null   $idShop
     *
     * @return string
     *
     * @throws PrestaShopException
     * @internal param bool $use_routes If false, don't use to create this url
     *
     * @since    1.0.0
     * @version  1.0.0 Initial version
     */
    public function createUrl($routeId, $idLang = null, array $params = [], $forceRoutes = false, $anchor = '', $idShop = null)
    {
        if ($idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }
        if ($idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        if (!isset($this->routes[$idShop])) {
            $this->loadRoutes($idShop);
        }

        if (!isset($this->routes[$idShop][$idLang][$routeId])) {
            $query = http_build_query($params, '', '&');
            $indexLink = $this->use_routes ? '' : 'index.php';

            return ($routeId == 'index') ? $indexLink.(($query) ? '?'.$query : '') : ((trim($routeId) == '') ? '' : 'index.php?controller='.$routeId).(($query) ? '&'.$query : '').$anchor;
        }
        $route = $this->routes[$idShop][$idLang][$routeId];
        // Check required fields
        $queryParams = isset($route['params']) ? $route['params'] : [];
        // Skip if we are not using routes
        // Build an url which match a route
        if ($this->use_routes || $forceRoutes) {
            foreach ($route['keywords'] as $key => $data) {
                if (!$data['required']) {
                    continue;
                }

                if (!array_key_exists($key, $params)) {
                    throw new PrestaShopException('Dispatcher::createUrl() miss required parameter "'.$key.'" for route "'.$routeId.'"');
                }
                if (isset($this->default_routes[$routeId])) {
                    $queryParams[$this->default_routes[$routeId]['keywords'][$key]['param']] = $params[$key];
                }
            }

            $url = $route['rule'];
            $addParam = [];

            foreach ($params as $key => $value) {
                if (!isset($route['keywords'][$key])) {
                    if (!isset($this->default_routes[$routeId]['keywords'][$key])) {
                        $addParam[$key] = $value;
                    }
                } else {
                    if ($params[$key]) {
                        $replace = $route['keywords'][$key]['prepend'].$params[$key].$route['keywords'][$key]['append'];
                    } else {
                        $replace = '';
                    }
                    $url = preg_replace('#\{([^{}]*:)?'.$key.'(:[^{}]*)?\}#', $replace, $url);
                }
            }
            $url = preg_replace('#\{([^{}]*:)?[a-z0-9_]+?(:[^{}]*)?\}#', '', $url);
            if (count($addParam)) {
                $url .= '?'.http_build_query($addParam, '', '&');
            }
        } else {
            $addParams = [];
            foreach ($route['keywords'] as $key => $data) {
                if (!$data['required'] || !array_key_exists($key, $params) || ($key === 'rewrite' && in_array($route['controller'], ['product', 'category', 'supplier', 'manufacturer', 'cms', 'cms_category']))) {
                    continue;
                }
                if (isset($this->default_routes[$routeId])) {
                    $queryParams[$this->default_routes[$routeId]['keywords'][$key]['param']] = $params[$key];
                }
            }
            foreach ($params as $key => $value) {
                if (!isset($route['keywords'][$key]) && !isset($this->default_routes[$routeId]['keywords'][$key])) {
                    $addParams[$key] = $value;
                }
            }
            if (isset($this->default_routes[$routeId])) {
                foreach ($this->default_routes[$routeId]['keywords'] as $key => $keyword) {
                    if (isset($keyword['alias']) && $keyword['alias']) {
                        $addParams[$keyword['alias']] = $params[$key];
                    }
                }
            }

            if (!empty($route['controller'])) {
                $queryParams['controller'] = $route['controller'];
            }
            $query = http_build_query(array_merge($addParams, $queryParams), '', '&');
            if ($this->multilang_activated) {
                $query .= (!empty($query) ? '&' : '').'id_lang='.(int) $idLang;
            }
            $url = 'index.php?'.$query;
        }

        return $url.$anchor;
    }

    /**
     * @param string $rewrite
     * @param string $url
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function productID($rewrite, $url = '')
    {
        // Rewrite and url cannot both be empty
        if (empty($rewrite)) {
            return 0;
        }
        // Remove leading slash from URL
        $url = ltrim($url, '/');

        $context = Context::getContext();
        $link = $context->link;
        $idLang = $context->language->id;
        $idShop = $context->shop->id;

        // Context sometimes contains no link in older versions of PS
        if (empty($link)) {
            $link = new Link();
        }

        $sql = new DbQuery();
        $sql->select('`id_product`');
        $sql->from('product_lang');
        $sql->where('`link_rewrite` = \''.pSQL($rewrite).'\'');
        $sql->where('`id_lang` = '.(int) $idLang);
        $sql->where('`id_shop` = '.(int) $idShop);

        $results = Db::getInstance()->executeS($sql);
        if (!empty($results)) {
            $baseLink = $link->getBaseLink().$link->getLangLink();
            if (count($results) > 1 && !empty($url)) {
                // Multiple rewrites available, full URL needs to be checked
                foreach ($results as $result) {
                    $productLink = $link->getProductLink($result['id_product']);
                    if ($url === str_replace($baseLink, '', $productLink)) {
                        return (int) $result['id_product'];
                    }
                }
            } else {
                $productLink = $link->getProductLink((int) $results[0]['id_product']);
                if ($url === str_replace($baseLink, '', $productLink)) {
                    return (int) $results[0]['id_product'];
                }
            }
        }

        return 0;
    }

    /**
     * @param string $rewrite
     * @param string $url
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function categoryID($rewrite, $url = '')
    {
        // Rewrite cannot be empty
        if (empty($rewrite)) {
            return 0;
        }
        // Remove leading slash from URL
        $url = ltrim($url, '/');

        $context = Context::getContext();
        $link = $context->link;
        $idLang = $context->language->id;
        $idShop = $context->shop->id;

        // Context sometimes contains no link in older versions of PS
        if (empty($link)) {
            $link = new Link();
        }

        $sql = new DbQuery();
        $sql->select('`id_category`');
        $sql->from('category_lang');
        $sql->where('`link_rewrite` = \''.pSQL($rewrite).'\'');
        $sql->where('`id_lang` = '.(int) $idLang);
        $sql->where('`id_shop` = '.(int) $idShop);

        $results = Db::getInstance()->executeS($sql);
        if (!empty($results)) {
            $baseLink = $link->getBaseLink().$link->getLangLink();
            if (count($results) > 1 && !empty($url)) {
                // Multiple rewrites available, full URL needs to be checked
                foreach ($results as $result) {
                    $categoryLink = $link->getCategoryLink($result['id_category']);
                    if ($url === str_replace($baseLink, '', $categoryLink)) {
                        return (int) $result['id_category'];
                    }
                }
            } else {
                $categoryLink = $link->getCategoryLink((int) $results[0]['id_category']);
                if ($url === str_replace($baseLink, '', $categoryLink)) {
                    return (int) $results[0]['id_category'];
                }
            }
        }

        return 0;
    }

    /**
     * @param string $rewrite
     *
     * @return int
     */
    protected function supplierID($rewrite)
    {
        // Rewrite cannot be empty
        if (empty($rewrite)) {
            return 0;
        }

        $context = Context::getContext();

        $suppliers = Supplier::getSuppliers(false, $context->language->id, true);
        foreach ($suppliers as $supplier) {
            if (Tools::link_rewrite($supplier['name']) === $rewrite) {
                return (int) $supplier['id_supplier'];
            }
        }

        return 0;
    }

    /**
     * @param string $rewrite
     *
     * @return int
     */
    protected function manufacturerID($rewrite)
    {
        // Rewrite cannot be empty
        if (empty($rewrite)) {
            return 0;
        }

        $context = Context::getContext();

        $manufacturers = Manufacturer::getManufacturers(false, $context->language->id, true);
        foreach ($manufacturers as $manufacturer) {
            if (Tools::link_rewrite($manufacturer['name']) === $rewrite) {
                return (int) $manufacturer['id_manufacturer'];
            }
        }

        return 0;
    }

    /**
     * @param string $rewrite
     * @param string $url
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function cmsID($rewrite, $url = '')
    {
        // Rewrite cannot be empty
        if (empty($rewrite)) {
            return 0;
        }
        // Remove leading slash from URL
        $url = ltrim($url, '/');

        $context = Context::getContext();
        $link = $context->link;
        $idLang = $context->language->id;
        $idShop = $context->shop->id;

        // Context sometimes contains no link in older versions of PS
        if (empty($link)) {
            $link = new Link();
        }

        $sql = new DbQuery();
        $sql->select('`cl`.`id_cms`');
        $sql->from('cms_lang', 'cl');
        $sql->innerJoin('cms_shop', 'cs', '`cl`.`id_cms` = `cs`.`id_cms`');
        $sql->where('`link_rewrite` = \''.pSQL($rewrite).'\'');
        $sql->where('`cl`.`id_lang` = '.(int) $idLang);
        $sql->where('`cs`.`id_shop` = '.(int) $idShop);

        $results = Db::getInstance()->executeS($sql);
        if (!empty($results)) {
            $baseLink = $link->getBaseLink().$link->getLangLink();
            if (count($results) > 1 && !empty($url)) {
                // Multiple rewrites available, full URL needs to be checked
                foreach ($results as $result) {
                    $cmsLink = $link->getCMSLink($result['id_cms']);
                    if ($url === str_replace($baseLink, '', $cmsLink)) {
                        return (int) $result['id_cms'];
                    }
                }
            } else {
                $cmsLink = $link->getCMSLink((int) $results[0]['id_cms']);
                if ($url === str_replace($baseLink, '', $cmsLink)) {
                    return (int) $results[0]['id_cms'];
                }
            }
        }

        return 0;
    }

    /**
     * @param string $rewrite
     * @param string $url
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function cmsCategoryID($rewrite, $url = '')
    {
        // Rewrite cannot be empty
        if (empty($rewrite)) {
            return 0;
        }
        // Remove leading slash from URL
        $url = ltrim($url, '/');

        $context = Context::getContext();
        $link = $context->link;
        $idLang = $context->language->id;
        $idShop = $context->shop->id;

        // Context sometimes contains no link in older versions of PS
        if (empty($link)) {
            $link = new Link();
        }

        $sql = new DbQuery();
        $sql->select('`cl`.`id_cms_category`');
        $sql->from('cms_category_lang', 'cl');
        $sql->innerJoin('cms_category_shop', 'cs', '`cl`.`id_cms_category` = `cs`.`id_cms_category`');
        $sql->where('`link_rewrite` = \''.pSQL($rewrite).'\'');
        $sql->where('`cl`.`id_lang` = '.(int) $idLang);
        $sql->where('`cs`.`id_shop` = '.(int) $idShop);

        $results = Db::getInstance()->executeS($sql);

        if (!empty($results)) {
            $baseLink = $link->getBaseLink().$link->getLangLink();
            if (count($results) > 1 && !empty($url)) {
                // Multiple rewrites available, full URL needs to be checked
                foreach ($results as $result) {
                    $cmsLink = $link->getCMSCategoryLink($result['id_cms_category']);
                    if ($url === str_replace($baseLink, '', $cmsLink)) {
                        return (int) $result['id_cms_category'];
                    }
                }
            } else {
                $cmsLink = $link->getCMSCategoryLink((int) $results[0]['id_cms_category']);
                if ($url === str_replace($baseLink, '', $cmsLink)) {
                    return (int) $results[0]['id_cms_category'];
                }
            }
        }

        return 0;
    }

    /**
     * @param string $rule
     * @param array  $keywords
     *
     * @return string
     */
    protected function createRegExp($rule, $keywords)
    {
        $regexp = preg_quote($rule, '#');
        if ($keywords) {
            $transformKeywords = [];
            preg_match_all('#\\\{(([^{}]*)\\\:)?('.implode('|', array_keys($keywords)).')(\\\:([^{}]*))?\\\}#', $regexp, $m);
            for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
                $prepend = $m[2][$i];
                $keyword = $m[3][$i];
                $append = $m[5][$i];
                $transformKeywords[$keyword] = [
                    'required' => isset($keywords[$keyword]['param']),
                    'prepend' => Tools::stripslashes($prepend),
                    'append' => Tools::stripslashes($append),
                ];
                $prependRegexp = $appendRegexp = '';
                if ($prepend || $append) {
                    $prependRegexp = '('.preg_quote($prepend);
                    $appendRegexp = preg_quote($append).')?';
                }
                if (isset($keywords[$keyword]['param'])) {
                    $regexp = str_replace($m[0][$i], $prependRegexp.'(?P<'.$keywords[$keyword]['param'].'>'.$keywords[$keyword]['regexp'].')'.$appendRegexp, $regexp);
                } else {
                    $regexp = str_replace($m[0][$i], $prependRegexp.'('.$keywords[$keyword]['regexp'].')'.$appendRegexp, $regexp);
                }
            }
        }

        return '#^/'.$regexp.'$#u';
    }
}
