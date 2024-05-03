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
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;

/**
 * Class AdminPerformanceControllerCore
 *
 * @property Configuration|null $object
 */
class AdminPerformanceControllerCore extends AdminController
{

    const CUSTOM_DEFINES_FILE = _PS_ROOT_DIR_ . '/config/defines_custom.inc.php';

    const CACHE_FS = 'CacheFs';
    const CACHE_MEMCACHE = 'CacheMemcache';
    const CACHE_MEMCACHED = 'CacheMemcached';
    const CACHE_APCU = 'CacheApcu';
    const CACHE_REDIS = 'CacheRedis';

    /**
     * AdminPerformanceControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        parent::__construct();
    }

    /**
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        $this->initToolbar();
        $this->initPageHeaderToolbar();
        $this->display = '';
        $this->content .= $this->renderForm();

        $this->context->smarty->assign(
            [
                'content'                   => $this->content,
                'url_post'                  => static::$currentIndex.'&token='.$this->token,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
                'title'                     => $this->page_header_toolbar_title,
                'toolbar_btn'               => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        $this->page_header_toolbar_btn['clear_cache'] = [
            'href' => static::$currentIndex.'&token='.$this->token.'&empty_smarty_cache=1',
            'desc' => $this->l('Clear cache'),
            'icon' => 'process-icon-eraser',
        ];
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {


        // Reindex fields
        $this->fields_form = [
            $this->initFieldsetSmarty(),
            $this->initFieldsetDebugMode(),
            $this->initFieldsetDatabase(),
            $this->initFieldsetFeaturesDetachables(),
            $this->initFieldsetCCC(),
            $this->initFieldsetMediaServer(),
            $this->initFieldsetCiphering(),
            $this->initFieldsetCaching(),
            $this->initFieldsetFullPageCache(),
            $this->initFieldsetExperimental(),
        ];

        // Activate multiple fieldset
        $this->multiple_fieldsets = true;

        return parent::renderForm();
    }

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function initFieldsetSmarty()
    {
        $form = [
            'legend' => [
                'title' => $this->l('Smarty - Application Cache'),
                'icon'  => 'icon-briefcase',
            ],
            'input'  => [
                [
                    'type' => 'hidden',
                    'name' => 'smarty_up',
                ],
                [
                    'type'   => 'radio',
                    'label'  => $this->l('Template compilation'),
                    'name'   => 'smarty_force_compile',
                    'values' => [
                        [
                            'id'    => 'smarty_force_compile_'._PS_SMARTY_NO_COMPILE_,
                            'value' => _PS_SMARTY_NO_COMPILE_,
                            'label' => $this->l('Never recompile template files'),
                            'hint'  => $this->l('This option should be used in a production environment.'),
                        ],
                        [
                            'id'    => 'smarty_force_compile_'._PS_SMARTY_CHECK_COMPILE_,
                            'value' => _PS_SMARTY_CHECK_COMPILE_,
                            'label' => $this->l('Recompile templates if the files have been updated'),
                            'hint'  => $this->l('Templates are recompiled when they are updated. If you experience compilation troubles when you update your template files, you should use Force Compile instead of this option. It should never be used in a production environment.'),
                        ],
                        [
                            'id'    => 'smarty_force_compile_'._PS_SMARTY_FORCE_COMPILE_,
                            'value' => _PS_SMARTY_FORCE_COMPILE_,
                            'label' => $this->l('Force compilation'),
                            'hint'  => $this->l('This forces Smarty to (re)compile templates on every invocation. This is handy for development and debugging. Note: This should never be used in a production environment.'),
                        ],
                    ],
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Cache'),
                    'name'    => 'smarty_cache',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'smarty_cache_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'smarty_cache_0',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                    'hint'    => $this->l('Should be enabled except for debugging.'),
                ],
                [
                    'type'   => 'radio',
                    'label'  => $this->l('Caching type'),
                    'name'   => 'smarty_caching_type',
                    'values' => [
                        [
                            'id'    => 'smarty_caching_type_filesystem',
                            'value' => SmartyCustom::CACHING_TYPE_FILESYSTEM,
                            'label' => $this->l('File System').(is_writable(_PS_CACHE_DIR_.'smarty/cache') ? '' : ' '.sprintf($this->l('(the directory %s must be writable)'), realpath(_PS_CACHE_DIR_.'smarty/cache'))),
                        ],
                        [
                            'id'    => 'smarty_caching_type_mysql',
                            'value' => SmartyCustom::CACHING_TYPE_MYSQL,
                            'label' => $this->l('MySQL'),
                        ],
                        [
                            'id'    => 'smarty_caching_type_ssc',
                            'value' => SmartyCustom::CACHING_TYPE_SSC,
                            'label' => $this->l('Server Side Cache'),
                        ],
                    ],
                ],
                [
                    'type'   => 'radio',
                    'label'  => $this->l('Clear cache'),
                    'name'   => 'smarty_clear_cache',
                    'values' => [
                        [
                            'id'    => 'smarty_clear_cache_never',
                            'value' => 'never',
                            'label' => $this->l('Never clear cache files'),
                        ],
                        [
                            'id'    => 'smarty_clear_cache_everytime',
                            'value' => 'everytime',
                            'label' => $this->l('Clear cache everytime something has been modified'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['smarty_force_compile'] = Configuration::get('PS_SMARTY_FORCE_COMPILE');
        $this->fields_value['smarty_cache'] = Configuration::get('PS_SMARTY_CACHE');
        $this->fields_value['smarty_caching_type'] = Configuration::get('PS_SMARTY_CACHING_TYPE');
        $this->fields_value['smarty_clear_cache'] = Configuration::get('PS_SMARTY_CLEAR_CACHE');
        $this->fields_value['smarty_console'] = Configuration::get('PS_SMARTY_CONSOLE');
        $this->fields_value['smarty_console_key'] = Configuration::get('PS_SMARTY_CONSOLE_KEY');

        return ['form' => $form ];
    }

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function initFieldsetDebugMode()
    {
        $form = [
            'legend' => [
                'title' => $this->l('Debug mode'),
                'icon'  => 'icon-bug',
            ],
            'input'  => [
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Disable non thirty bees modules'),
                    'name'    => 'native_module',
                    'class'   => 't',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'native_module_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'native_module_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'    => $this->l('Enable or disable non thirty bees modules.'),
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Disable all overrides'),
                    'name'    => 'overrides',
                    'class'   => 't',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'overrides_module_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'overrides_module_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'    => $this->l('Enable or disable all classes and controllers overrides.'),
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Debug mode'),
                    'name'    => 'debug_mode',
                    'class'   => 't',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'debug_mode_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'debug_mode_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'    => $this->l('Enable or disable debug mode.'),
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Profiling'),
                    'name'    => 'profiling',
                    'class'   => 't',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'profiling_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'profiling_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'    => $this->l('Enable or disable profiling.'),
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Display deprecation warnings'),
                    'name'    => 'display_deprecation_warnings',
                    'class'   => 't',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'display_deprecation_warnings_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'display_deprecation_warnings_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'    => $this->l('When enabled, you will see deprecation notices. Otherwise, only errors and warnings will be displayed'),
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['native_module'] = Configuration::get('PS_DISABLE_NON_NATIVE_MODULE');
        $this->fields_value['overrides'] = Configuration::get('PS_DISABLE_OVERRIDES');
        $this->fields_value['debug_mode'] = $this->isDebugModeEnabled();
        $this->fields_value['profiling'] = $this->isProfilingEnabled();
        $this->fields_value['display_deprecation_warnings'] = $this->shouldDisplayDeprecationWarnings();

        return ['form' => $form];
    }


    /**
     * @return array
     */
    public function initFieldsetDatabase()
    {
        $form = [
            'legend' => [
                'title' => $this->l('Database settings'),
                'icon'  => 'icon-database',
            ],
            'input'  => [
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Ignore SQL errors'),
                    'name'    => 'ignore_sql_errors',
                    'class'   => 't',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'ignore_sql_errors_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'ignore_sql_errors_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'    => $this->l('Silently ignore errors raised by database when executing SQL queries. Not recommended.'),
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Allow multi statements queries'),
                    'hint'    => $this->l('When enabled, multiple sql queries can be executed in one request. This significantly increases the risk of SQL injection vulnerabilities being exploited. Unfortunately, some modules can depend on this functionality.'),
                    'name'    => 'allow_multi_queries',
                    'class'   => 't',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'allow_multi_queries_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'allow_multi_queries_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['ignore_sql_errors'] = !$this->isDebugSqlEnabled();
        $this->fields_value['allow_multi_queries'] = $this->areMultiQueriesEnabled();

        return ['form' => $form];
    }

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function initFieldsetFeaturesDetachables()
    {
        $form = [
            'legend'      => [
                'title' => $this->l('Optional features'),
                'icon'  => 'icon-puzzle-piece',
            ],
            'description' => $this->l('Some features can be disabled in order to improve performance.'),
            'input'       => [
                [
                    'type' => 'hidden',
                    'name' => 'features_detachables_up',
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Combinations'),
                    'name'     => 'combination',
                    'is_bool'  => true,
                    'disabled' => Combination::isCurrentlyUsed(),
                    'values'   => [
                        [
                            'id'    => 'combination_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'combination_0',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                    'hint'     => $this->l('Choose "No" to disable Product Combinations.'),
                    'desc'     => Combination::isCurrentlyUsed() ? $this->l('You cannot set this parameter to No when combinations are already used by some of your products') : null,
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Features'),
                    'name'    => 'feature',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'feature_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'feature_0',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                    'hint'    => $this->l('Choose "No" to disable Product Features.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Customer Groups'),
                    'name'     => 'customer_group',
                    'is_bool'  => true,
                    'disabled' => Group::isCurrentlyUsed(),
                    'values'   => [
                        [
                            'id'    => 'group_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'group_0',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                    'hint'     => $this->l('Choose "No" to disable Customer Groups.'),
                ],
            ],
            'submit'      => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['combination'] = Combination::isFeatureActive();
        $this->fields_value['feature'] = Feature::isFeatureActive();
        $this->fields_value['customer_group'] = Group::isFeatureActive();

        return ['form' => $form];
    }

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function initFieldsetCCC()
    {
        $cssMinifierWarning = '';
        if (! Hook::getHookModuleExecList('actionMinifyCss')) {
            $cssMinifierWarning = $this->l('You don\'t have any CSS Minification module installed. Combined CSS bundle will not be minified!');
        }
        $inlineJsMinifierWarning = '';
        $jsMinifierWarning = '';
        if (! Hook::getHookModuleExecList('actionMinifyJs')) {
            $jsMinifierWarning = $this->l('You don\'t have any JS Minification module installed. Combined JS bundle will not be minified!');
            $inlineJsMinifierWarning = $this->l('You don\'t have any JS Minification module installed!');
        }

        $form = [
            'legend'      => [
                'title' => $this->l('CCC (Combine, Compress and Cache)'),
                'icon'  => 'icon-fullscreen',
            ],
            'description' => $this->l('CCC allows you to reduce the loading time of your page. With these settings you will gain performance without even touching the code of your theme.'),
            'input'       => [
                [
                    'type' => 'hidden',
                    'name' => 'ccc_up',
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Smart cache for CSS'),
                    'hint'   => $this->l('All css files will be combined into single css bundle.'),
                    'desc'   => $cssMinifierWarning,
                    'name'   => 'PS_CSS_THEME_CACHE',
                    'values' => [
                        [
                            'id'    => 'PS_CSS_THEME_CACHE_1',
                            'value' => 1,
                            'label' => $this->l('Use CCC for CSS'),
                        ],
                        [
                            'id'    => 'PS_CSS_THEME_CACHE_0',
                            'value' => 0,
                            'label' => $this->l('Keep CSS as original'),
                        ],
                    ],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Smart cache for JavaScript'),
                    'name'   => 'PS_JS_THEME_CACHE',
                    'hint'   => $this->l('All javascript files will be combined into single javascript bundle.'),
                    'desc'   => $jsMinifierWarning,
                    'values' => [
                        [
                            'id'    => 'PS_JS_THEME_CACHE_1',
                            'value' => 1,
                            'label' => $this->l('Use CCC for JavaScript'),
                        ],
                        [
                            'id'    => 'PS_JS_THEME_CACHE_0',
                            'value' => 0,
                            'label' => $this->l('Keep JavaScript as original'),
                        ],
                    ],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Compress inline JavaScript in HTML'),
                    'name'   => 'PS_JS_HTML_THEME_COMPRESSION',
                    'hint'   => $this->l('Javascript blocks in page body will be compressed'),
                    'desc'   => $inlineJsMinifierWarning,
                    'disabled' => !!$inlineJsMinifierWarning,
                    'values' => [
                        [
                            'id'    => 'PS_JS_HTML_THEME_COMPRESSION_1',
                            'value' => 1,
                            'label' => $this->l('Compress inline JavaScript in HTML after "Smarty compile" execution'),
                        ],
                        [
                            'id'    => 'PS_JS_HTML_THEME_COMPRESSION_0',
                            'value' => 0,
                            'label' => $this->l('Keep inline JavaScript in HTML as original'),
                        ],
                    ],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Move JavaScript to the end'),
                    'name'   => 'PS_JS_DEFER',
                    'values' => [
                        [
                            'id'    => 'PS_JS_DEFER_1',
                            'value' => 1,
                            'label' => $this->l('Move JavaScript to the end of the HTML document'),
                        ],
                        [
                            'id'    => 'PS_JS_DEFER_0',
                            'value' => 0,
                            'label' => $this->l('Keep JavaScript in HTML at its original position'),
                        ],
                    ],
                ],
                [

                    'type'   => 'switch',
                    'label'  => $this->l('Apache optimization'),
                    'name'   => 'PS_HTACCESS_CACHE_CONTROL',
                    'hint'   => $this->l('This will add directives to your .htaccess file, which should improve caching and compression.'),
                    'values' => [
                        [
                            'id'    => 'PS_HTACCESS_CACHE_CONTROL_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'PS_HTACCESS_CACHE_CONTROL_0',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Keep JS and CSS files'),
                    'desc'  => $this->l('Keep old JS and CSS files on the server, to make sure e.g. Google\'s cache still renders correctly (improves SEO).'),
                    'name'   => 'TB_KEEP_CCC_FILES',
                    'values' => [
                        [
                            'id'    => 'TB_KEEP_CCC_FILES_1',
                            'value' => 1,
                        ],
                        [
                            'id'    => 'TB_KEEP_CCC_FILES_0',
                            'value' => 0,
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['PS_CSS_THEME_CACHE'] = Configuration::get('PS_CSS_THEME_CACHE');
        $this->fields_value['PS_JS_THEME_CACHE'] = Configuration::get('PS_JS_THEME_CACHE');
        $this->fields_value['PS_JS_HTML_THEME_COMPRESSION'] = Configuration::get('PS_JS_HTML_THEME_COMPRESSION');
        $this->fields_value['PS_HTACCESS_CACHE_CONTROL'] = Configuration::get('PS_HTACCESS_CACHE_CONTROL');
        $this->fields_value['PS_JS_DEFER'] = Configuration::get('PS_JS_DEFER');
        $this->fields_value['TB_KEEP_CCC_FILES'] = Configuration::get('TB_KEEP_CCC_FILES');
        $this->fields_value['ccc_up'] = 1;

        return ['form' => $form];
    }

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function initFieldsetMediaServer()
    {
        $form = [
            'legend'      => [
                'title' => $this->l('Media servers (use only with CCC)'),
                'icon'  => 'icon-link',
            ],
            'description' => $this->l('You must enter another domain, or subdomain, in order to use cookieless static content.'),
            'input'       => [
                [
                    'type' => 'hidden',
                    'name' => 'media_server_up',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Media server #1'),
                    'name'  => '_MEDIA_SERVER_1_',
                    'hint'  => $this->l('Name of the second domain of your shop, (e.g. myshop-media-server-1.com). If you do not have another domain, leave this field blank.'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Media server #2'),
                    'name'  => '_MEDIA_SERVER_2_',
                    'hint'  => $this->l('Name of the third domain of your shop, (e.g. myshop-media-server-2.com). If you do not have another domain, leave this field blank.'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Media server #3'),
                    'name'  => '_MEDIA_SERVER_3_',
                    'hint'  => $this->l('Name of the fourth domain of your shop, (e.g. myshop-media-server-3.com). If you do not have another domain, leave this field blank.'),
                ],
            ],
            'submit'      => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['_MEDIA_SERVER_1_'] = Configuration::get('PS_MEDIA_SERVER_1');
        $this->fields_value['_MEDIA_SERVER_2_'] = Configuration::get('PS_MEDIA_SERVER_2');
        $this->fields_value['_MEDIA_SERVER_3_'] = Configuration::get('PS_MEDIA_SERVER_3');

        return ['form' => $form];
    }

    /**
     * @return array
     */
    public function initFieldsetCiphering()
    {
        $form = [

            'legend'      => [
                'title' => $this->l('Ciphering'),
                'icon'  => 'icon-desktop',
            ],
            'description' => $this->l('Keep in mind that changing this setting will log everyone out!'),
            'input'       => [
                [
                    'type' => 'hidden',
                    'name' => 'ciphering_up',
                ],
                [
                    'type'   => 'radio',
                    'label'  => $this->l('Algorithm'),
                    'name'   => 'PS_CIPHER_ALGORITHM',
                    'values' => [
                        [
                            'id'    => 'PS_CIPHER_ALGORITHM_2',
                            'value' => Encryptor::ALGO_PHP_ENCRYPTION,
                            'label' => $this->l('Use the PHP Encryption library (fastest and highest security)'),
                        ],
                        [
                            'id'    => 'PS_CIPHER_ALGORITHM_0',
                            'value' => Encryptor::ALGO_BLOWFISH,
                            'label' => $this->l('Use the custom BlowFish class.'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['PS_CIPHER_ALGORITHM'] = Encryptor::getAlgorithm();

        return ['form' => $form];
    }

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function initFieldsetCaching()
    {
        $form = [
            'legend'           => [
                'title' => $this->l('Server Side Caching'),
                'icon'  => 'icon-desktop',
            ],
            'input'            => [
                [
                    'type' => 'hidden',
                    'name' => 'cache_up',
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Use cache'),
                    'name'    => 'TB_CACHE_ENABLED',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'TB_CACHE_ENABLED_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'TB_CACHE_ENABLED_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'   => 'radio',
                    'label'  => $this->l('Caching system'),
                    'name'   => 'TB_CACHE_SYSTEM',
                    'hint'   => $this->l('The CacheFS system should be used only when the infrastructure contains one front-end server. If you are not sure, ask your hosting company.'),
                    'values' => [
                        [
                            'id'    => static::CACHE_FS,
                            'value' => static::CACHE_FS,
                            'label' => $this->getCacheFsLabel(),
                        ],
                        [
                            'id'    => static::CACHE_MEMCACHE,
                            'value' => static::CACHE_MEMCACHE,
                            'label' => $this->getCacheMemcacheLabel(),
                        ],
                        [
                            'id'    => static::CACHE_MEMCACHED,
                            'value' => static::CACHE_MEMCACHED,
                            'label' => $this->getCacheMemcachedLabel(),
                        ],
                        [
                            'id'    => static::CACHE_APCU,
                            'value' => static::CACHE_APCU,
                            'label' => $this->getCacheAPCuLabel(),
                        ],
                        [
                            'id'    => static::CACHE_REDIS,
                            'value' => static::CACHE_REDIS,
                            'label' => $this->getCacheRedisLabel(),
                        ],
                    ],
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Directory depth'),
                    'name'  => 'ps_cache_fs_directory_depth',
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
            'memcachedServers' => true,
            'redisServers' => true,
        ];
        $depth = Configuration::get('PS_CACHEFS_DIRECTORY_DEPTH');
        $this->fields_value['TB_CACHE_ENABLED'] = Cache::isEnabled();
        $this->fields_value['TB_CACHE_SYSTEM'] = Configuration::get('TB_CACHE_SYSTEM') ?: static::CACHE_FS;
        $this->fields_value['ps_cache_fs_directory_depth'] = $depth ? $depth : 1;
        $this->tpl_form_vars['memcached_servers'] = CacheMemcache::getMemcachedServers();
        $this->tpl_form_vars['redis_servers'] = CacheRedis::getRedisServers();
        $this->tpl_form_vars['cacheDisabled'] = !Cache::isEnabled();
        return ['form' => $form];
    }

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function initFieldsetFullPageCache()
    {
        Cache::clean('hook_module_list');
        $hooks = Hook::getHookModuleList();
        $hookSettings = PageCache::getCachedHooks();
        $moduleSettings = [];
        foreach ($hooks as $hook) {
            foreach ($hook as $hookInfo) {
                $idModule = (int)$hookInfo['id_module'];
                $idHook = (int)$hookInfo['id_hook'];
                $moduleName = $hookInfo['name'];
                $moduleDisplayName = Module::getModuleName($moduleName);
                $hookName = Hook::getNameById($idHook);
                // We only want display hooks
                if (strpos($hookName, 'action') === 0
                    || strpos($hookName, 'displayAdmin') === 0
                    || strpos($hookName, 'dashboard') === 0
                    || strpos($hookName, 'BackOffice') !== false
                ) {
                    continue;
                }

                if (!isset($moduleSettings[$idModule])) {
                    $moduleSettings[$idModule] = [
                        'name' => $moduleName,
                        'displayName' => $moduleDisplayName,
                        'hooks' => [],
                    ];
                }
                $moduleSettings[$hookInfo['id_module']]['hooks'][$hookName] = isset($hookSettings[$idModule][$idHook]);
            }
        }

        $form = [
            'legend'       => [
                'title' => $this->l('Full page cache'),
                'icon'  => 'icon-rocket',
            ],
            'description' => $this->l('Before enabling the full page cache, make sure you have chosen a caching system in the panel above.'),
            'input' => [
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Use full page cache'),
                    'name'    => 'TB_PAGE_CACHE_ENABLED',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'TB_PAGE_CACHE_ENABLED_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'TB_PAGE_CACHE_ENABLED_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'disabled' => !Cache::isEnabled()
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Debug mode'),
                    'hint'    => $this->l('Enable this option to see the "X-thirtybees-PageCache" debug header'),
                    'name'    => 'TB_PAGE_CACHE_DEBUG',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'TB_PAGE_CACHE_DEBUG_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'TB_PAGE_CACHE_DEBUG_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'  => 'tags',
                    'label' => $this->l('Ignore query parameters'),
                    'name'  => 'TB_PAGE_CACHE_IGNOREPARAMS',
                    'hint'  => [
                        $this->l('To add parameters click in the field, write something, and then press "Enter."'),
                        $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                    ],
                    'tagPrompt' => $this->l('Add param'),
                    'delimiters' => '13,44,32,59',
                ],
            ],
            'submit'       => [
                'title' => $this->l('Save'),
            ],
            'controllerList' => true,
            'dynamicHooks' => true,
        ];

        $controllerList = $this->displayControllerList(json_decode(Configuration::get('TB_PAGE_CACHE_CONTROLLERS'), true), $this->context->shop->id);

        $this->tpl_form_vars['controllerList'] = $controllerList;
        $this->tpl_form_vars['moduleSettings'] = $moduleSettings;
        $this->fields_value['TB_PAGE_CACHE_ENABLED'] = PageCache::isEnabled();
        $this->fields_value['TB_PAGE_CACHE_DEBUG'] = (bool) Configuration::get('TB_PAGE_CACHE_DEBUG');
        $this->fields_value['TB_PAGE_CACHE_IGNOREPARAMS'] = Configuration::get('TB_PAGE_CACHE_IGNOREPARAMS');

        return ['form' => $form];
    }



    /**
     * @return array[]
     */
    public function initFieldsetExperimental()
    {
        $form = [
            'legend' => [
                'title' => $this->l('Experimental features'),
                'icon'  => 'icon-database',
            ],
            'error' => Translate::ppTags($this->l('[1]Danger zone![/1]'), ['<b>']),
            'warning' => $this->l('These are experimental features. Do not change anything unless you know what you are doing!'),
            'input'  => [
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Use native data types for fetches'),
                    'hint'    => $this->l('When enabled, database will return numeric data types instead of strings. When disabled, PDO driwer will convert numeric values to strings when fetching.'),
                    'desc'    => $this->l('We suggest to keep this option disabled for compatibility reasons.'),
                    'name'    => 'db_use_native_types',
                    'class'   => 't',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'db_use_native_types_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'db_use_native_types_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['db_use_native_types'] = !$this->isStringifyFetchesEnabled();

        return ['form' => $form];
    }

    /**
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return;
        }

        $action = $this->action ?? Tools::getValue('action');
        if ($action) {
            Hook::triggerEvent('actionAdmin'.ucfirst($action).'Before', ['controller' => $this]);
            Hook::triggerEvent('action' . get_class($this) . ucfirst($action) . 'Before', ['controller' => $this]);
        }

        if (Tools::isSubmit('submitAddMemcachedServer')) {
            if ($this->hasAddPermission()) {
                if (!Tools::getValue('memcachedIp')) {
                    $this->errors[] = Tools::displayError('The Memcached IP is missing.');
                }
                if (!Tools::getValue('memcachedPort')) {
                    $this->errors[] = Tools::displayError('The Memcached port is missing.');
                }
                if (!Tools::getValue('memcachedWeight')) {
                    $this->errors[] = Tools::displayError('The Memcached weight is missing.');
                }
                if (!count($this->errors)) {
                    if (CacheMemcache::addServer(
                        pSQL(Tools::getValue('memcachedIp')),
                        Tools::getIntValue('memcachedPort'),
                        Tools::getIntValue('memcachedWeight')
                    )) {
                        Tools::redirectAdmin(static::$currentIndex.'&token='.Tools::getValue('token').'&conf=4');
                    } else {
                        $this->errors[] = Tools::displayError('The Memcached server cannot be added.');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        }
        if (Tools::getValue('deleteMemcachedServer')) {
            if ($this->hasAddPermission()) {
                if (CacheMemcache::deleteServer(Tools::getIntValue('deleteMemcachedServer'))) {
                    Tools::redirectAdmin(static::$currentIndex.'&token='.Tools::getValue('token').'&conf=4');
                } else {
                    $this->errors[] = Tools::displayError('There was an error when attempting to delete the Memcached server.');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        }
        if (Tools::isSubmit('submitAddRedisServer')) {
            if ($this->hasAddPermission()) {
                if (!Tools::getValue('redisIp')) {
                    $this->errors[] = Tools::displayError('The Redis IP is missing.');
                }
                if (!Tools::getValue('redisPort')) {
                    $this->errors[] = Tools::displayError('The Redis port is missing.');
                }
                if (!Tools::isSubmit('redisAuth')) {
                    $this->errors[] = Tools::displayError('The Redis auth is missing.');
                }
                if (!Tools::isSubmit('redisDb')) {
                    $this->errors[] = Tools::displayError('The Redis database is missing.');
                }
                if (!count($this->errors)) {
                    if (CacheRedis::addServer(
                        pSQL(Tools::getValue('redisIp')),
                        Tools::getIntValue('redisPort'),
                        pSQL(Tools::getValue('redisAuth')),
                        Tools::getIntValue('redisDb')
                    )) {
                        Tools::redirectAdmin(static::$currentIndex.'&token='.Tools::getValue('token').'&conf=4');
                    } else {
                        $this->errors[] = Tools::displayError('The Redis server cannot be added.');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        }
        if (Tools::isSubmit('deleteRedisServer')) {
            if ($this->hasAddPermission()) {
                if (CacheRedis::deleteServer(Tools::getIntValue('deleteRedisServer'))) {
                    Tools::redirectAdmin(static::$currentIndex.'&token='.Tools::getValue('token').'&conf=4');
                } else {
                    $this->errors[] = Tools::displayError('There was an error when attempting to delete the Redis server.');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        }

        $redirectAdmin = false;
        if (Tools::getValue('smarty_up')) {
            if ($this->hasEditPermission()) {
                Configuration::updateValue('PS_SMARTY_FORCE_COMPILE', Tools::getValue('smarty_force_compile', _PS_SMARTY_NO_COMPILE_));

                if (Configuration::get('PS_SMARTY_CACHE') != Tools::getValue('smarty_cache') || Configuration::get('PS_SMARTY_CACHING_TYPE') != Tools::getValue('smarty_caching_type')) {
                    Tools::clearSmartyCache();
                }

                Configuration::updateValue('PS_SMARTY_CACHE', Tools::getValue('smarty_cache', 0));
                Configuration::updateValue('PS_SMARTY_CACHING_TYPE', Tools::getValue('smarty_caching_type'));
                Configuration::updateValue('PS_SMARTY_CLEAR_CACHE', Tools::getValue('smarty_clear_cache'));
                $redirectAdmin = true;
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }

        if (Tools::isSubmit('TB_PAGE_CACHE_IGNOREPARAMS')) {
            if ($this->hasEditPermission()) {
                Configuration::updateValue('TB_PAGE_CACHE_ENABLED', (bool) Tools::getValue('TB_PAGE_CACHE_ENABLED'));
                Configuration::updateValue('TB_PAGE_CACHE_DEBUG', (bool) Tools::getValue('TB_PAGE_CACHE_DEBUG'));
                Configuration::updateValue('TB_PAGE_CACHE_IGNOREPARAMS', Tools::getValue('TB_PAGE_CACHE_IGNOREPARAMS'));
                Configuration::updateValue('TB_PAGE_CACHE_CONTROLLERS', json_encode(array_map('trim', explode(',', Tools::getValue('TB_PAGE_CACHE_CONTROLLERS')))));
                $redirectAdmin = true;
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }

        if (Tools::getValue('features_detachables_up')) {
            if ($this->hasEditPermission()) {
                if (Tools::isSubmit('combination')) {
                    if ((!Tools::getValue('combination') && Combination::isCurrentlyUsed()) === false) {
                        Configuration::updateValue('PS_COMBINATION_FEATURE_ACTIVE', (bool) Tools::getValue('combination'));
                    }
                }

                if (Tools::isSubmit('customer_group')) {
                    if ((!Tools::getValue('customer_group') && Group::isCurrentlyUsed()) === false) {
                        Configuration::updateValue('PS_GROUP_FEATURE_ACTIVE', (bool) Tools::getValue('customer_group'));
                    }
                }

                Configuration::updateValue('PS_FEATURE_FEATURE_ACTIVE', (bool) Tools::getValue('feature'));
                $redirectAdmin = true;
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }

        if (Tools::getValue('ccc_up')) {
            if ($this->hasEditPermission()) {
                $themeCacheDirectory = _PS_ALL_THEMES_DIR_.$this->context->shop->theme_directory.'/cache/';
                if ((Tools::getValue('PS_CSS_THEME_CACHE') || Tools::getValue('PS_JS_THEME_CACHE')) && !is_writable($themeCacheDirectory)) {
                    if (@file_exists($themeCacheDirectory) || !@mkdir($themeCacheDirectory, 0777, true)) {
                        $this->errors[] = sprintf(Tools::displayError('To use Smart Cache directory %s must be writable.'), realpath($themeCacheDirectory));
                    }
                }

                if ($tmp = Tools::getIntValue('PS_CSS_THEME_CACHE')) {
                    $version = (int) Configuration::get('PS_CCCCSS_VERSION');
                    if (Configuration::get('PS_CSS_THEME_CACHE') != $tmp) {
                        Configuration::updateValue('PS_CCCCSS_VERSION', ++$version);
                    }
                }

                if ($tmp = Tools::getIntValue('PS_JS_THEME_CACHE')) {
                    $version = (int) Configuration::get('PS_CCCJS_VERSION');
                    if (Configuration::get('PS_JS_THEME_CACHE') != $tmp) {
                        Configuration::updateValue('PS_CCCJS_VERSION', ++$version);
                    }
                }

                if (!Configuration::updateValue('PS_CSS_THEME_CACHE', Tools::getIntValue('PS_CSS_THEME_CACHE')) ||
                    !Configuration::updateValue('PS_JS_THEME_CACHE', Tools::getIntValue('PS_JS_THEME_CACHE')) ||
                    !Configuration::updateValue('PS_JS_HTML_THEME_COMPRESSION', Tools::getIntValue('PS_JS_HTML_THEME_COMPRESSION')) ||
                    !Configuration::updateValue('PS_JS_DEFER', Tools::getIntValue('PS_JS_DEFER')) ||
                    !Configuration::updateValue('TB_KEEP_CCC_FILES', Tools::getIntValue('TB_KEEP_CCC_FILES')) ||
                    !Configuration::updateValue('PS_HTACCESS_CACHE_CONTROL', Tools::getIntValue('PS_HTACCESS_CACHE_CONTROL'))
                ) {
                    $this->errors[] = Tools::displayError('Unknown error.');
                } else {
                    $redirectAdmin = true;
                    if (Configuration::get('PS_HTACCESS_CACHE_CONTROL')) {
                        if (is_writable(_PS_ROOT_DIR_.'/.htaccess')) {
                            Tools::generateHtaccess();
                        } else {
                            $message = $this->l('Before being able to use this tool, you need to:');
                            $message .= '<br />- '.$this->l('Create a blank .htaccess in your root directory.');
                            $message .= '<br />- '.$this->l('Give it write permissions (CHMOD 666 on Unix system).');
                            $this->errors[] = Tools::displayError($message, false);
                            Configuration::updateValue('PS_HTACCESS_CACHE_CONTROL', false);
                        }
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }

        if (Tools::getValue('media_server_up')) {
            if ($this->hasEditPermission()) {
                if (Tools::getValue('_MEDIA_SERVER_1_') != null && !Validate::isFileName(Tools::getValue('_MEDIA_SERVER_1_'))) {
                    $this->errors[] = Tools::displayError('Media server #1 is invalid');
                }
                if (Tools::getValue('_MEDIA_SERVER_2_') != null && !Validate::isFileName(Tools::getValue('_MEDIA_SERVER_2_'))) {
                    $this->errors[] = Tools::displayError('Media server #2 is invalid');
                }
                if (Tools::getValue('_MEDIA_SERVER_3_') != null && !Validate::isFileName(Tools::getValue('_MEDIA_SERVER_3_'))) {
                    $this->errors[] = Tools::displayError('Media server #3 is invalid');
                }
                if (!count($this->errors)) {
                    $mediaServer1 = Tools::getValue('_MEDIA_SERVER_1_');
                    $mediaServer2 = Tools::getValue('_MEDIA_SERVER_2_');
                    $mediaServer3 = Tools::getValue('_MEDIA_SERVER_3_');
                    if ($mediaServer1 || $mediaServer2 || $mediaServer3) {
                        Configuration::updateValue('PS_MEDIA_SERVERS', 1);
                    } else {
                        Configuration::updateValue('PS_MEDIA_SERVERS', 0);
                    }
                    Configuration::updateValue('PS_MEDIA_SERVER_1', $mediaServer1);
                    Configuration::updateValue('PS_MEDIA_SERVER_2', $mediaServer2);
                    Configuration::updateValue('PS_MEDIA_SERVER_3', $mediaServer3);
                    Tools::clearSmartyCache();
                    Media::clearCache();

                    if (is_writable(_PS_ROOT_DIR_.'/.htaccess')) {
                        Tools::generateHtaccess();
                        $redirectAdmin = true;
                    } else {
                        $message = $this->l('Before being able to use this tool, you need to:');
                        $message .= '<br />- '.$this->l('Create a blank .htaccess in your root directory.');
                        $message .= '<br />- '.$this->l('Give it write permissions (CHMOD 666 on Unix system).');
                        $this->errors[] = Tools::displayError($message, false);
                        Configuration::updateValue('PS_HTACCESS_CACHE_CONTROL', false);
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }

        if (Tools::getValue('ciphering_up')) {
            if ($this->hasEditPermission()) {
                $algo = Tools::getIntValue('PS_CIPHER_ALGORITHM');
                $prevSettings = file_get_contents(_PS_ROOT_DIR_.'/config/settings.inc.php');
                $newSettings = $prevSettings;
                if ($algo === Encryptor::ALGO_PHP_ENCRYPTION) {
                    if (!extension_loaded('openssl')) {
                        $this->errors[] = Tools::displayError('The "openssl" PHP extension is not activated on this server.');
                    } else {
                        $success = false;
                        if (!strstr($newSettings, '_PHP_ENCRYPTION_KEY_')) {
                            try {
                                $secureKey = Key::createNewRandomKey();
                                $key = $secureKey->saveToAsciiSafeString();

                                $success = true;
                            } catch (EnvironmentIsBrokenException $e) {
                                $this->errors[] = sprintf(Tools::displayError('It looks like your system isn\'t configured for the higest security possible. The error was: %s'), $e->getMessage());
                            } catch (Exception $e) {
                                sprintf(Tools::displayError('An error occurred while enabling the PHP Encryption library. The error was: %s'), $e->getMessage());
                            }

                            if ($success && isset($key)) {
                                $newSettings .= "define('_PHP_ENCRYPTION_KEY_', '{$key}');\n";
                            }
                        }
                    }
                }
                if (!count($this->errors)) {
                    // If there is not settings file modification or if the backup and replacement of the settings file worked
                    if ($newSettings == $prevSettings || (
                            copy(_PS_ROOT_DIR_.'/config/settings.inc.php', _PS_ROOT_DIR_.'/config/settings.old.php') &&
                            file_put_contents(_PS_ROOT_DIR_.'/config/settings.inc.php', $newSettings)
                        )
                    ) {
                        Configuration::updateValue('PS_CIPHER_ALGORITHM', $algo);
                        $redirectAdmin = true;
                    } else {
                        $this->errors[] = Tools::displayError('The settings file cannot be overwritten.');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }

        if (Tools::getValue('cache_up')) {
            if ($this->hasEditPermission()) {
                $cacheActive = (bool) Tools::getValue('TB_CACHE_ENABLED');
                if ($cachingSystem = preg_replace('[^a-zA-Z0-9]', '', Tools::getValue('TB_CACHE_SYSTEM'))) {
                    Configuration::updateGlobalValue('TB_CACHE_SYSTEM', $cachingSystem);
                }
                Configuration::updateGlobalValue('TB_CACHE_ENABLED', $cacheActive);
                if ($cacheActive) {
                    if ($cachingSystem == static::CACHE_FS) {
                        if (!($depth = Tools::getValue('ps_cache_fs_directory_depth'))) {
                            $this->errors[] = Tools::displayError('Please set a directory depth.');
                        }
                        if (!count($this->errors)) {
                            CacheFs::deleteCacheDirectory();
                            CacheFs::createCacheDirectories((int)$depth);
                            Configuration::updateValue('PS_CACHEFS_DIRECTORY_DEPTH', (int)$depth);
                        }
                    } else {
                        Cache::getInstance(true)->flush();
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }

        if (Tools::getValue('empty_smarty_cache')) {
            $redirectAdmin = true;
            Tools::clearSmartyCache();
            Tools::clearXMLCache();
            Media::clearCache();
            Tools::generateIndex();
            PageCache::flush();
            Tools::clearOpCache();
            Cache::getInstance()->flush();
        }

        if (Tools::isSubmit('submitAddconfiguration')) {
            Configuration::updateGlobalValue('PS_DISABLE_NON_NATIVE_MODULE', Tools::getIntValue('native_module'));
            Configuration::updateGlobalValue('PS_DISABLE_OVERRIDES', Tools::getIntValue('overrides'));

            $this->updateCustomDefines([
                '_PS_MODE_DEV_' => Tools::getBoolValue('debug_mode'),
                '_PS_DEBUG_PROFILING_' => Tools::getBoolValue('profiling'),
                '_PS_DEBUG_SQL_' => !Tools::getBoolValue('ignore_sql_errors'),
                '_TB_DB_ALLOW_MULTI_STATEMENTS_QUERIES_' => Tools::getBoolValue('allow_multi_queries'),
                '_TB_DB_STRINGIFY_FETCHES_' => !Tools::getBoolValue('db_use_native_types'),
                '_PS_DISPLAY_COMPATIBILITY_WARNING_' => Tools::getBoolValue('display_deprecation_warnings'),
            ]);

            Tools::generateIndex();
        }

        if ($action) {
            Hook::triggerEvent('actionAdmin'.ucfirst($action).'After', ['controller' => $this, 'return' => '']);
            Hook::triggerEvent('action' . get_class($this) . ucfirst($action) . 'After', ['controller' => $this, 'return' => '']);
        }

        if ($redirectAdmin && (!isset($this->errors) || !count($this->errors))) {
            Tools::redirectAdmin(static::$currentIndex.'&token='.Tools::getValue('token').'&conf=4');
        }
    }

    /**
     * @param string[] $fileList
     * @param int $idShop
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function displayControllerList($fileList, $idShop)
    {
        if (!is_array($fileList)) {
            $fileList = ($fileList) ? [$fileList] : [];
        }

        $content = '<p><input type="text" name="TB_PAGE_CACHE_CONTROLLERS" value="'.implode(', ', $fileList).'" id="em_text_'.$idShop.'" placeholder="'.$this->l('E.g. address, addresses, attachment').'"/></p>';

        if ($idShop) {
            $shop = new Shop($idShop);
            $content .= ' ('.$shop->name.')';
        }

        $content .= '<p>
					<select size="25" id="em_list_'.$idShop.'" multiple="multiple">
					<option disabled="disabled">'.$this->l('___________ CUSTOM ___________').'</option>';

        // @todo do something better with controllers
        $controllers = Dispatcher::getControllers(_PS_FRONT_CONTROLLER_DIR_);
        ksort($controllers);

        foreach ($fileList as $v) {
            if (! array_key_exists($v, $controllers)) {
                $content .= '<option value="'.$v.'">'.$v.'</option>';
            }
        }

        $content .= '<option disabled="disabled">'.$this->l('____________ CORE ____________').'</option>';

        foreach ($controllers as $k => $v) {
            $content .= '<option value="'.$k.'">'.$k.'</option>';
        }

        $modulesControllersType = ['admin' => $this->l('Admin modules controller'), 'front' => $this->l('Front modules controller')];
        foreach ($modulesControllersType as $type => $label) {
            $content .= '<option disabled="disabled">____________ '.$label.' ____________</option>';
            $allModulesControllers = Dispatcher::getModuleControllers($type);
            foreach ($allModulesControllers as $module => $modulesControllers) {
                foreach ($modulesControllers as $cont) {
                    $content .= '<option value="module-'.$module.'-'.$cont.'">module-'.$module.'-'.$cont.'</option>';
                }
            }
        }

        $content .= '</select>
					</p>';

        return $content;
    }

    /**
     * Is Debug Mode enabled?
     *
     * @return bool Whether debug mode is enabled
     */
    public function isDebugModeEnabled()
    {
        return _PS_MODE_DEV_;
    }

    /**
     * Is profiling enabled?
     *
     * @return bool Whether profiling is enabled
     */
    public function isProfilingEnabled()
    {
        return defined('_PS_DEBUG_PROFILING_') && _PS_DEBUG_PROFILING_;
    }

    /**
     * Is SQL debugging enabled?
     *
     * @return bool Whether debug mode is enabled
     */
    public function isDebugSqlEnabled()
    {
        return defined('_PS_DEBUG_SQL_') && _PS_DEBUG_SQL_;
    }

    /**
     * Are multi-queries enabled
     *
     * @return bool Whether debug mode is enabled
     */
    public function areMultiQueriesEnabled()
    {
        return defined('_TB_DB_ALLOW_MULTI_STATEMENTS_QUERIES_') && _TB_DB_ALLOW_MULTI_STATEMENTS_QUERIES_;
    }

    /**
     * Returns true, if compatibility warnings should be displayed
     *
     * @return bool
     */
    public function shouldDisplayDeprecationWarnings()
    {
        return defined('_PS_DISPLAY_COMPATIBILITY_WARNING_') && _PS_DISPLAY_COMPATIBILITY_WARNING_;
    }

    /**
     * Are multi-queries enabled
     *
     * @return bool Whether debug mode is enabled
     */
    public function isStringifyFetchesEnabled()
    {
        return defined('_TB_DB_STRINGIFY_FETCHES_') && _TB_DB_STRINGIFY_FETCHES_;
    }

    /**
     * Helper method to rewrite custom defined file with new configuration option
     *
     * @param array $constants
     * @throws PrestaShopException
     */
    protected function updateCustomDefines($constants)
    {
        $content = $this->loadCustomDefines();
        if ($content === false) {
            return;
        }

        foreach ($constants as $constantName => $value) {
            if (!Validate::isConfigName($constantName)) {
                throw new PrestaShopException("Invalid define constant name: '" . $constantName . "'");
            }
            if (gettype($value) !== 'boolean') {
                throw new PrestaShopException("Invalid define constant value type: " . gettype($value));
            }

            $escapedValue = $value ? 'true' : 'false';
            $regexp = '/define\(\'' . $constantName . '\',\s*(true|false)\s*\);/Ui';

            if (preg_match($regexp, $content)) {
                $content = preg_replace($regexp, "define('$constantName', $escapedValue);", $content);
            } else {
                if ($content) {
                    $content = trim($content);
                    $content .= "\n\n";
                }
                $content .= "if (! defined('$constantName')) {\n";
                $content .= "    define('$constantName', $escapedValue);\n";
                $content .= "}";
            }

        }

        $content = trim($content) . "\n";
        if (! @file_put_contents(static::CUSTOM_DEFINES_FILE, $content)) {
            $this->errors[] = Translate::ppTags(sprintf(Tools::displayError('Custom defines file [1]%s[/1] is not writable'), static::CUSTOM_DEFINES_FILE), ['<b>']);
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(static::CUSTOM_DEFINES_FILE);
        }
    }

    /**
     * Returns contents of custom defines file
     *
     * @return string | false
     */
    public function loadCustomDefines()
    {
        if (file_exists(static::CUSTOM_DEFINES_FILE)) {
            if (! is_readable(static::CUSTOM_DEFINES_FILE)) {
                $this->errors[] = Translate::ppTags(sprintf(Tools::displayError('Custom defines file [1]%s[/1] is not readable'), static::CUSTOM_DEFINES_FILE), ['<b>']);
                return false;
            }
            $content = file_get_contents(static::CUSTOM_DEFINES_FILE);
            if ($content === false) {
                $this->errors[] = Translate::ppTags(sprintf(Tools::displayError('Failed to read defines file: %s'), static::CUSTOM_DEFINES_FILE), ['<b>']);
                return false;
            }
            return $content;
        }
        return '<?php' . "\n";
    }

    /**
     * @throws PrestaShopException
     */
    public function displayAjaxTestMemcachedServer()
    {
        if (_PS_MODE_DEMO_) {
            $this->ajaxDie(Tools::displayError('This functionality has been disabled.'));
        }

        if (Tools::isSubmit('action') && Tools::getValue('action') == 'test_memcached_server') {
            $host = pSQL(Tools::getValue('sHost', ''));
            $port = Tools::getIntValue('sPort', 0);
            $type = Tools::getValue('type', '');
            if ($host != '' && $port != 0) {
                try {
                    $version = false;
                    if ($type == 'memcached') {
                        if (! CacheMemcached::checkEnvironment()) {
                            throw new PrestaShopException(Tools::displayError("Memcached extension not loaded"));
                        }
                        $memcache = new Memcached();
                        $memcache->addServer($host, $port);
                        $versions = $memcache->getVersion();
                        if ($versions) {
                            $version = array_values($versions)[0];
                        }
                    } else {
                        if (! CacheMemcache::checkEnvironment()) {
                            throw new PrestaShopException(Tools::displayError("Memcache extension not loaded"));
                        }
                        $memcache = new Memcache();
                        $memcache->addServer($host, $port);
                        $version = $memcache->getVersion();
                    }
                    if (! $version) {
                        throw new PrestaShopException("Failed to connect to memcache server");
                    }
                    $this->ajaxDie(json_encode([
                        'success' => true,
                        'message' => sprintf($this->l('Connected to memcache server version %s'), $version),
                    ]));
                    $this->ajaxDie(json_encode([]));
                } catch (Exception $e) {
                    $this->ajaxDie(json_encode([
                        'success' => false,
                        'error' => $e->getMessage()
                    ]));
                }
            }
        }
        exit;
    }

    /**
     * Perform a short test to see if Redis is enabled
     * and return the result through ajax
     *
     * @throws PrestaShopException
     */
    public function displayAjaxTestRedisServer()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_) {
            $this->ajaxDie(Tools::displayError('This functionality has been disabled.'));
        }
        if (Tools::isSubmit('action') && Tools::getValue('action') == 'test_redis_server') {
            $host = pSQL(Tools::getValue('sHost', ''));
            $port = Tools::getIntValue('sPort', 0);
            $auth = pSQL(Tools::getValue('sAuth', ''));
            $db = Tools::getIntValue('sDb', 0);
            if ($host != '' && $port != 0) {
                try {
                    if (! extension_loaded('redis')) {
                        throw new PrestaShopException(Tools::displayError("Redis extension not loaded"));
                    }

                    $redis = new Redis();
                    if ($redis->connect($host, $port)) {
                        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
                        if (!empty($auth)) {
                            if (!($redis->auth($auth))) {
                                $this->ajaxDie(json_encode([0]));
                            }
                        }
                        $redis->select($db);
                        if (! $redis->ping()) {
                            throw new PrestaShopException("Redis server ping failed");
                        }
                        $this->ajaxDie(json_encode([
                            'success' => true,
                            'message' => $this->l('Connected to Redis server'),
                        ]));
                    }
                } catch (Exception $e) {
                    $this->ajaxDie(json_encode([
                        'success' => false,
                        'error' => $e->getMessage()
                    ]));
                }

            }
        }
        exit;
    }

    /**
     * Process dynamic hook setting
     * @throws PrestaShopException
     */
    public function displayAjaxUpdateDynamicHooks()
    {
        $idModule = Tools::getIntValue('idModule');
        $status = Tools::getValue('status') === 'true';
        $hookName = Tools::getValue('hookName');
        $idHook = Hook::getIdByName($hookName);
        $this->ajaxDie(json_encode([
            'success' => PageCache::setHookCacheStatus($idModule, $idHook, $status)
        ]));
    }

    /**
     * @return string
     */
    protected function getCacheFsLabel(): string
    {
        return $this->getLabel(
            $this->l('File System'),
            CacheFs::checkEnvironment(),
            sprintf($this->l('(the directory %s must be writable)'), realpath(_PS_CACHEFS_DIRECTORY_))
        );
    }

    /**
     * @return string
     */
    protected function getCacheMemcacheLabel(): string
    {
        return $this->getLabel(
            $this->l('Memcache via PHP::Memcache'),
            CacheMemcache::checkEnvironment(),
            $this->l('(you must install [1]memcache[/1] extension)'),
            "https://www.php.net/manual/en/memcache.installation.php",
        );
    }

    /**
     * @return string
     */
    protected function getCacheMemcachedLabel(): string
    {
        return $this->getLabel(
            $this->l('Memcache via PHP::Memcached'),
            CacheMemcached::checkEnvironment(),
            $this->l('(you must install [1]memcached[/1] extension)'),
            "https://www.php.net/manual/en/memcached.installation.php",
        );
    }

    /**
     * @return string
     */
    protected function getCacheAPCuLabel(): string
    {
        return $this->getLabel(
            $this->l('APCu'),
            CacheApcu::checkEnvironment(),
            $this->l('(you must install [1]apcu[/1] extension)'),
            "https://www.php.net/manual/en/apcu.installation.php",
        );
    }

    /**
     * @return string
     */
    protected function getCacheRedisLabel(): string
    {
        return $this->getLabel(
            $this->l('Redis'),
            CacheRedis::checkEnvironment(),
            $this->l('(you must install [1]redis[/1] extension)'),
            "https://pecl.php.net/package/redis",
        );
    }


    /**
     * @param string $label
     * @param bool $checkEnv
     * @param string $errorMsg
     * @param string|null $helpUrl
     *
     * @return string
     */
    protected function getLabel($label, $checkEnv, $errorMsg, $helpUrl=null)
    {
        if (! $checkEnv) {
            if ($helpUrl) {
                $errorMsg = Translate::ppTags($errorMsg, ['<a href="'.$helpUrl.'" />']);
            }
            return $label. '&nbsp;' . $errorMsg;
        }
        return $label;
    }
}
