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

use Thirtybees\Core\DependencyInjection\ServiceLocator;

/**
 * Class DispatcherCore
 */
class DispatcherCore
{
    /**
     * List of available front controllers types
     */
    const FC_FRONT = 1;
    const FC_ADMIN = 2;
    const FC_MODULE = 3;

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
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
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
                'any' => [
                    'regexp' => '.*'
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

    /**
     * @var bool
     */
    protected $multilang_activated = false;

    /**
     * @var array List of loaded routes
     */
    public $routes = [];

    /**
     * @var array Map of additional route matchers
     */
    public $matchers = [];

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

    /**
     * @var bool
     */
    protected $use_default_controller = false;

    /**
     * @var string Controller to use if found controller doesn't exist
     */
    protected $controller_not_found = 'pagenotfound';

    /**
     * @var string Front controller to use
     */
    protected $front_controller = self::FC_FRONT;

    /**
     * Get current instance of dispatcher (singleton)
     *
     * @return Dispatcher
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @param string $routeId Name of the route (need to be unique, a second route with same name will override the first)
     * @param string $rule Url rule
     * @param string $controller Controller to call if request uri match the rule
     * @param int $idLang
     * @param array $keywords
     * @param array $params
     * @param int $idShop
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
        $aliases = [];
        if ($keywords) {
            $transformKeywords = [];
            preg_match_all('#\\\{(([^{}]*)\\\:)?('.implode('|', array_keys($keywords)).')(\\\:([^{}]*))?\\\}#', $regexp, $m);
            for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
                $prepend = $m[2][$i];
                $keyword = $m[3][$i];
                $append = $m[5][$i];
                $hasParam = isset($keywords[$keyword]['param']);
                $keywordParam = $hasParam ? $keywords[$keyword]['param'] : null;
                $keywordRegexp = $keywords[$keyword]['regexp'];

                $transformKeywords[$keyword] = [
                    'required' => isset($keywords[$keyword]['param']),
                    'prepend'  => stripslashes($prepend),
                    'append'   => stripslashes($append),
                ];

                // if keyword 'id' and 'param' are different, let's register param as keyword id alias
                if ($keywordParam && $keywordParam !== $keyword) {
                    $aliases[$keywordParam] = $keyword;
                }

                $prependRegexp = $appendRegexp = '';
                if ($prepend || $append) {
                    $prependRegexp = '('.$prepend;
                    $appendRegexp = $append.')?';
                }

                if ($keywordParam) {
                    $regexp = str_replace($m[0][$i], $prependRegexp.'(?P<'.$keywordParam.'>'.$keywordRegexp.')'.$appendRegexp, $regexp);
                } elseif ($keyword === 'id') {
                    $regexp = str_replace($m[0][$i], $prependRegexp.'(?P<id>'.$keywordRegexp.')'.$appendRegexp, $regexp);
                } else {
                    $regexp = str_replace($m[0][$i], $prependRegexp.'('.$keywordRegexp.')'.$appendRegexp, $regexp);
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
            'aliases'    => $aliases,
        ];
    }

    /**
     * Get list of all available Module Front controllers
     *
     * @param string $type
     * @param string|string[]|null $module
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @throws PrestaShopException
     */
    protected function setRequestUri()
    {
        $requestUri = static::extractRequestUri();

        // remove shop base uri from requestUri
        if (isset(Context::getContext()->shop) && is_object(Context::getContext()->shop)) {
            $requestUri = preg_replace('#^'.preg_quote(Context::getContext()->shop->getBaseURI(), '#').'#i', '/', $requestUri);
        }

        // remove language from uri
        if ($this->use_routes && Language::isMultiLanguageActivated()) {
            if (preg_match('#^/([a-z]{2})(?:/.*)?$#', $requestUri, $m)) {
                $_GET['isolang'] = $m[1];
                $requestUri = substr($requestUri, 3);
            }
        }

        // fix uri, if it starts with two or more / characters
        if (strpos($requestUri, '//') === 0) {
            $requestUri = '/' . ltrim($requestUri, '/');
        }

        $this->request_uri = $requestUri;
    }

    /**
     * Load default routes group by languages
     *
     * @param int|null $idShop
     *
     * @throws PrestaShopException
     */
    protected function loadRoutes($idShop = null)
    {
        // Load custom routes from modules
        $modulesRoutes = Hook::getResponses('moduleRoutes', ['id_shop' => $idShop]);
        foreach ($modulesRoutes as $moduleRoute) {
            if (is_array($moduleRoute)) {
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

        $this->setRouteMatcher('product_rule', [ $this, 'matchRewritableRoute' ]);
        $this->setRouteMatcher('category_rule', [ $this, 'matchRewritableRoute' ]);
        $this->setRouteMatcher('supplier_rule', [ $this, 'matchRewritableRoute' ]);
        $this->setRouteMatcher('manufacturer_rule', [ $this, 'matchRewritableRoute' ]);
        $this->setRouteMatcher('layered_rule', [ $this, 'matchRewritableRoute' ]);
        $this->setRouteMatcher('cms_rule', [ $this, 'matchRewritableRoute' ]);
        $this->setRouteMatcher('cms_category_rule', [ $this, 'matchRewritableRoute' ]);

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
            if ($results = Db::readOnly()->getArray(
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
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
                $controllerClass = 'PageNotFoundController';
                $moduleName = Tools::getValue('module');
                if (Validate::isModuleName($moduleName)) {
                    $module = Module::getInstanceByName($moduleName);
                    if (Validate::isLoadedObject($module) && $module->active) {
                        $controllers = Dispatcher::getControllers(_PS_MODULE_DIR_ . $moduleName . '/controllers/front/');
                        if (isset($controllers[strtolower($this->controller)])) {
                            include_once(_PS_MODULE_DIR_ . $moduleName . '/controllers/front/' . $this->controller . '.php');
                            $controllerClass = $moduleName . $this->controller . 'ModuleFrontController';
                        }

                        $ajaxControllers = Dispatcher::getControllers(_PS_MODULE_DIR_ . $moduleName . '/controllers/ajax/');
                        if (isset($ajaxControllers[strtolower($this->controller)])) {
                            include_once(_PS_MODULE_DIR_ . $moduleName . '/controllers/ajax/' . $this->controller . '.php');
                            $controllerClass = $moduleName . $this->controller . 'ModuleAjaxController';
                        }
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
        $controller = ServiceLocator::getInstance()->getController($controllerClass);

        // Execute hook dispatcher
        Hook::triggerEvent('actionDispatcher', $paramsHookActionDispatcher);

        // Running controller
        $controller->run();
    }

    /**
     * Retrieve the controller from url or request uri if routes are activated
     *
     * @param int|null $idShop
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getController($idShop = null)
    {
        $context = Context::getContext();

        // Get the controller directly on admin pages
        if (isset($context->employee->id) && $context->employee->id) {
            $_GET['controllerUri'] = Tools::getValue('controller');
        }
        if ($this->controller) {
            $_GET['controller'] = $this->controller;

            return $this->controller;
        }

        if (isset(Context::getContext()->shop) && $idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }
        return $this->resolveController($idShop, $this->request_uri);
    }


    /**
     * @param int $idShop
     * @param string $requestUri
     *
     * @return string
     * @throws PrestaShopException
     */
    public function resolveController(int $idShop, string $requestUri)
    {
        list($uri) = explode('?', $requestUri);

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
            if (!$requestUri) {
                return mb_strtolower($this->controller_not_found);
            }

            // Check basic controllers & params
            $controller = $this->controller_not_found;
            $testRequestUri = preg_replace('/(=http:\/\/)/', '=', $requestUri);
            $urlPath = parse_url($testRequestUri, PHP_URL_PATH);
            if ($urlPath && !preg_match('/\.(css|js)$/i', $urlPath)) {
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
                list($uri) = explode('?', $requestUri);
                if (isset($this->routes[$idShop][Context::getContext()->language->id])) {
                    $routes = $this->routes[$idShop][Context::getContext()->language->id];

                    foreach ($routes as $routeId => $route) {
                        if (preg_match($route['regexp'], $uri, $m)) {

                            // if route has dedicated matcher, use it to determine whether route was found or not
                            if (isset($this->matchers[$routeId])) {
                                $matcher = $this->matchers[$routeId];
                                $params = $matcher($m, $uri, $route);
                                if ($params === false) {
                                    // even though uri matches this route regexp, it does not matches associated mather
                                    continue;
                                }
                                if (is_array($params)) {
                                    foreach ($params as $key => $value) {
                                        $_GET[$key] = $value;
                                    }
                                } else {
                                    $params = [];
                                }
                            }

                            $isModule = isset($route['params']['fc']) && $route['params']['fc'] === 'module';
                            foreach ($m as $k => $v) {
                                // We might have us an external module page here, in that case we set whatever we can
                                if (!is_numeric($k) &&
                                    !isset($params[$k]) &&
                                    ($isModule
                                        || $k !== 'id'
                                        && $k !== 'ipa'
                                        && $k !== 'rewrite'
                                        && $k !== 'cms_rewrite'
                                        && $k !== 'cms_cat_rewrite'
                                    )) {
                                    $_GET[$k] = $v;
                                    if (isset($route['aliases'][$k])) {
                                        $_GET[$route['aliases'][$k]] = $v;
                                    }
                                }
                            }

                            if (isset($route['controller']) && $route['controller']) {
                                $controller = (string)$route['controller'];
                            } elseif (isset($_GET['controller']) && $_GET['controller']) {
                                $controller = (string)$_GET['controller'];
                            }
                            if (!empty($route['params'])) {
                                foreach ($route['params'] as $k => $v) {
                                    $_GET[$k] = $v;
                                }
                            }

                            // A patch for module friendly urls
                            if ($info = $this->isModuleControllerRoute($controller)) {
                                $_GET['fc'] = 'module';
                                $_GET['module'] = $info['module'];
                                $controller = $info['controller'];
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
            if ($controller == 'index' || preg_match('/^\/index.php(?:\?.*)?$/', $requestUri)
                || $uri == ''
            ) {
                $controller = $this->useDefaultController();
            }
        }

        $this->controller = str_replace('-', '', (string)$controller);
        $_GET['controller'] = $this->controller;

        return $this->controller;
    }

    /**
     * @return false|null|string
     *
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
     * @param string|string[] $dirs
     *
     * @return array
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
     * @param int $idLang
     * @param int $idShop
     *
     * @return bool
     */
    public function hasRoute($routeId, $idLang = null, $idShop = null)
    {
        return !!$this->getRoute($routeId, $idLang, $idShop);
    }

    /**
     * Returns route by its routeId
     *
     * @param string $routeId
     * @param int $idLang
     * @param int $idShop
     *
     * @return array | null
     */
    public function getRoute($routeId, $idLang = null, $idShop = null)
    {
        if (isset(Context::getContext()->language) && $idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }
        if (isset(Context::getContext()->shop) && $idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        return isset($this->routes[$idShop][$idLang][$routeId])
            ? $this->routes[$idShop][$idLang][$routeId]
            : null;
    }

    /**
     * Check if a keyword is written in a route rule
     *
     * @param string $routeId
     * @param int $idLang
     * @param string $keyword
     * @param int $idShop
     *
     * @return bool
     *
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
     * @param string $rule Rule to verify
     * @param array $errors List of missing keywords
     *
     * @return bool
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
     * @param string $routeId Name of the route
     * @param int $idLang
     * @param array $params
     * @param bool $forceRoutes
     * @param string $anchor Optional anchor to add at the end of this url
     * @param int|null $idShop
     *
     * @return string
     *
     * @throws PrestaShopException
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
            $routeId = trim($routeId ?? '');
            switch ($routeId) {
                case '':
                    return '';
                case 'index':
                    $query = http_build_query($params, '', '&');
                    $indexLink = $this->use_routes ? '' : 'index.php';
                    return $indexLink.($query ? '?'.$query : '') ;
                default:
                    $query = http_build_query($params, '', '&');
                    return 'index.php?controller=' . $routeId . ($query ? '&' . $query : '') . $anchor;
            }
        }
        $route = $this->routes[$idShop][$idLang][$routeId];
        // Check required fields
        $queryParams = isset($route['params']) ? $route['params'] : [];
        // Skip if we are not using routes
        // Build an url which match a route
        if ($this->use_routes || $forceRoutes) {
            $aliases = array_flip($route['aliases']);
            foreach ($route['keywords'] as $key => $data) {
                if (!$data['required']) {
                    continue;
                }

                $alias = isset($aliases[$key]) ? $aliases[$key] : null;
                if ($alias && array_key_exists($alias, $params)) {
                    $params[$key] = $params[$alias];
                    unset($params[$alias]);
                }

                if (! array_key_exists($key, $params)) {
                    if ($alias) {
                        throw new PrestaShopException('Dispatcher::createUrl() miss required parameter "'.$alias.'" or it\'s alias "'.$key.'"for route "'.$routeId.'"');
                    } else {
                        throw new PrestaShopException('Dispatcher::createUrl() miss required parameter "'.$key.'" for route "'.$routeId.'"');
                    }
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
                    if ($value) {
                        $replace = $route['keywords'][$key]['prepend'].$value.$route['keywords'][$key]['append'];
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
     * This method tries to match core rewritable controllers
     *
     * @param array $parts parsed uri according to $route rule definition
     * @param string $uri
     * @param array $route route definition
     *
     * @return array | false
     */
    protected function matchRewritableRoute($parts, $uri, $route)
    {
        $type = $route['controller'];
        if ($type === 'cms') {
            if (isset($parts['cms_rewrite'])) {
                $key = 'id_cms';
                $func = 'cmsID';
                $rewrite = 'cms_rewrite';
            } else {
                $key = 'id_cms_category';
                $func = 'cmsCategoryID';
                $rewrite = 'cms_cat_rewrite';
            }
        } else {
            $key = 'id_' . $type;
            $func = $type . 'ID';
            $rewrite = 'rewrite';
        }

        // Look if record ID is part of the parsed uri. If exists, use it directly
        if (isset($parts['id']) && $parts['id']) {
            $id = (int)$parts['id'];
            if ($id) {
                return [ $key => $id ];
            }
            return false;
        }

        // try to resolve by rewrite / full uri
        if (isset($parts[$rewrite])) {
            $id = $this->{$func}($parts[$rewrite], $uri);
            if ($id) {
                return [ $key => (int)$id ];
            }
            return false;
        }

        return false;
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

        $results = Db::readOnly()->getArray($sql);
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
            }

            return (int) $results[0]['id_product'];
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

        $results = Db::readOnly()->getArray($sql);
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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

        $results = Db::readOnly()->getArray($sql);
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

        $results = Db::readOnly()->getArray($sql);

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
     * @param array $keywords
     *
     * @return string
     */
    protected function createRegExp($rule, $keywords)
    {
        $regexp = preg_quote($rule, '#');
        if ($keywords) {
            preg_match_all('#\\\{(([^{}]*)\\\:)?('.implode('|', array_keys($keywords)).')(\\\:([^{}]*))?\\\}#', $regexp, $m);
            for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
                $prepend = $m[2][$i];
                $keyword = $m[3][$i];
                $append = $m[5][$i];
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

    /**
     * Registers matcher function for given route
     *
     * Matcher function is called with following parameters:
     *    - $parts - array parsed when $route['rule'] regexp is matched against $uri
     *    - $uri - matched uri
     *    - $route - current route object
     * and it must return either
     *    - false - this means that $uri does NOT represent current route, even though it matched regexp
     *    - array - associative array of parameters that will be passed to route Controller
     *              This array usually contains resolved primary key, but it can contain additional
     *              information as well
     *
     * @param string $routeId unique route ID
     * @param callable $matcher matche function
     * @throws PrestaShopException
     */
    public function setRouteMatcher($routeId, $matcher)
    {
        if (is_callable($matcher)) {
            $this->matchers[$routeId] = $matcher;
        } else {
            throw new PrestaShopException("Can't register matcher for route $routeId");
        }
    }

    /**
     * Returns matcher function associated with given route
     *
     * @param string $routeId
     * @return callable | null
     */
    public function getRouteMatcher($routeId)
    {
        if (isset($this->matchers[$routeId])) {
            return $this->matchers[$routeId];
        }
        return null;
    }

    /**
     * Returns array with information about module/controller, if $routeId matches module controller route
     *
     * @param string $routeId
     * @return false| array
     */
    public function isModuleControllerRoute($routeId)
    {
        if (preg_match('#module-([a-z0-9_-]+)-([a-z0-9_]+)$#i', (string)$routeId, $m)) {
            return [
                'module' => $m[1],
                'controller' => $m[2]
            ];
        }
        return false;
    }

    /**
     * Returns parameters names required by route with id $routeId
     *
     * @return array
     */
    public function getRouteRequiredParams($routeId)
    {
        $params = [];
        $route = $this->getRoute($routeId);
        if ($route) {
            $aliases = array_flip($route['aliases']);
            foreach ($route['keywords'] as $keyword => $info) {
                if ($info['required']) {
                    if (isset($aliases[$keyword]) && $aliases[$keyword]) {
                        $alias = $aliases[$keyword];
                        $params[$alias] = $alias;
                    }
                    $params[$keyword] = $keyword;
                }
            }
        }
        return $params;
    }

    /**
     * Extracts request_uri from request
     *
     * @return string
     */
    protected static function extractRequestUri(): string
    {
        // Get request uri (HTTP_X_REWRITE_URL is used by IIS)
        if (isset($_SERVER['REQUEST_URI'])) {
            return rawurldecode((string)$_SERVER['REQUEST_URI']);
        }
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            return rawurldecode((string)$_SERVER['HTTP_X_REWRITE_URL']);
        }
        return '';
    }
}
