<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class AdminPerformanceControllerCore
 *
 * @since 1.0.0
 */
class AdminPerformanceControllerCore extends AdminController
{
    /**
     * AdminPerformanceControllerCore constructor.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        parent::__construct();
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initContent()
    {
        $this->initTabModuleList();
        $this->initToolbar();
        $this->initPageHeaderToolbar();
        $this->display = '';
        $this->content .= $this->renderForm();

        $this->context->smarty->assign(
            [
                'content'                   => $this->content,
                'url_post'                  => self::$currentIndex.'&token='.$this->token,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
                'title'                     => $this->page_header_toolbar_title,
                'toolbar_btn'               => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        $this->page_header_toolbar_btn['clear_cache'] = [
            'href' => self::$currentIndex.'&token='.$this->token.'&empty_smarty_cache=1',
            'desc' => $this->l('Clear cache'),
            'icon' => 'process-icon-eraser',
        ];
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderForm()
    {
        $this->initFieldsetSmarty();
        $this->initFieldsetDebugMode();
        $this->initFieldsetFeaturesDetachables();
        $this->initFieldsetCCC();

        if (!defined('_PS_HOST_MODE_')) {
            $this->initFieldsetMediaServer();
            $this->initFieldsetCiphering();
        }

        // Reindex fields
        $this->fields_form = array_values($this->fields_form);

        // Activate multiple fieldset
        $this->multiple_fieldsets = true;

        return parent::renderForm();
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initFieldsetSmarty()
    {
        $this->fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Smarty'),
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
                            'value' => 'filesystem',
                            'label' => $this->l('File System').(is_writable(_PS_CACHE_DIR_.'smarty/cache') ? '' : ' '.sprintf($this->l('(the directory %s must be writable)'), realpath(_PS_CACHE_DIR_.'smarty/cache'))),
                        ],
                        [
                            'id'    => 'smarty_caching_type_mysql',
                            'value' => 'mysql',
                            'label' => $this->l('MySQL'),
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
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initFieldsetDebugMode()
    {
        $this->fields_form[1]['form'] = [
            'legend' => [
                'title' => $this->l('Debug mode'),
                'icon'  => 'icon-bug',
            ],
            'input'  => [
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Disable non PrestaShop modules'),
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
                    'hint'    => $this->l('Enable or disable non PrestaShop Modules.'),
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
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['native_module'] = Configuration::get('PS_DISABLE_NON_NATIVE_MODULE');
        $this->fields_value['overrides'] = Configuration::get('PS_DISABLE_OVERRIDES');
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initFieldsetFeaturesDetachables()
    {
        $this->fields_form[2]['form'] = [
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
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initFieldsetCCC()
    {
        $this->fields_form[3]['form'] = [
            'legend'      => [
                'title' => $this->l('CCC (Combine, Compress and Cache)'),
                'icon'  => 'icon-fullscreen',
            ],
            'description' => $this->l('CCC allows you to reduce the loading time of your page. With these settings you will gain performance without even touching the code of your theme. Make sure, however, that your theme is compatible with PrestaShop 1.4+. Otherwise, CCC will cause problems.'),
            'input'       => [
                [
                    'type' => 'hidden',
                    'name' => 'ccc_up',
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Smart cache for CSS'),
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

            ],
            'submit'      => [
                'title' => $this->l('Save'),
            ],
        ];

        if (!defined('_PS_HOST_MODE_')) {
            $this->fields_form[3]['form']['input'][] = [
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
            ];
        }

        $this->fields_value['PS_CSS_THEME_CACHE'] = Configuration::get('PS_CSS_THEME_CACHE');
        $this->fields_value['PS_JS_THEME_CACHE'] = Configuration::get('PS_JS_THEME_CACHE');
        $this->fields_value['PS_JS_HTML_THEME_COMPRESSION'] = Configuration::get('PS_JS_HTML_THEME_COMPRESSION');
        $this->fields_value['PS_HTACCESS_CACHE_CONTROL'] = Configuration::get('PS_HTACCESS_CACHE_CONTROL');
        $this->fields_value['PS_JS_DEFER'] = Configuration::get('PS_JS_DEFER');
        $this->fields_value['ccc_up'] = 1;
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initFieldsetMediaServer()
    {
        $this->fields_form[4]['form'] = [
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
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initFieldsetCiphering()
    {
        $phpdocLangs = ['en', 'zh', 'fr', 'de', 'ja', 'pl', 'ro', 'ru', 'fa', 'es', 'tr'];
        $phpLang = in_array($this->context->language->iso_code, $phpdocLangs) ? $this->context->language->iso_code : 'en';

        $warningMcrypt = ' '.$this->l('(you must install the [a]Mcrypt extension[/a])');
        $warningMcrypt = str_replace('[a]', '<a href="http://www.php.net/manual/'.substr($phpLang, 0, 2).'/book.mcrypt.php" target="_blank">', $warningMcrypt);
        $warningMcrypt = str_replace('[/a]', '</a>', $warningMcrypt);

        if (defined('_RIJNDAEL_KEY_') && defined('_RIJNDAEL_IV_')) {
            $this->fields_form[5]['form'] = [

                'legend' => [
                    'title' => $this->l('Ciphering'),
                    'icon'  => 'icon-desktop',
                ],
                'input'  => [
                    [
                        'type' => 'hidden',
                        'name' => 'ciphering_up',
                    ],
                    [
                        'type'   => 'radio',
                        'label'  => $this->l('Algorithm'),
                        'name'   => 'PS_CIPHER_ALGORITHM',
                        'hint'   => $this->l('Mcrypt is faster than our custom BlowFish class, but requires the "mcrypt" PHP extension. If you change this configuration, all cookies will be reset.'),
                        'values' => [
                            [
                                'id'    => 'PS_CIPHER_ALGORITHM_1',
                                'value' => 1,
                                'label' => $this->l('Use Rijndael with mcrypt lib.').(function_exists('mcrypt_encrypt') ? '' : $warningMcrypt),
                            ],
                            [
                                'id'    => 'PS_CIPHER_ALGORITHM_0',
                                'value' => 0,
                                'label' => $this->l('Use the custom BlowFish class.'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ];
        }

        $this->fields_value['PS_CIPHER_ALGORITHM'] = Configuration::get('PS_CIPHER_ALGORITHM');
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function postProcess()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return;
        }

        Hook::exec('action'.get_class($this).ucfirst($this->action).'Before', ['controller' => $this]);

        $redirectAdmin = false;
        if ((bool) Tools::getValue('smarty_up')) {
            if ($this->tabAccess['edit'] === '1') {
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

        if ((bool) Tools::getValue('features_detachables_up')) {
            if ($this->tabAccess['edit'] === '1') {
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

        if ((bool) Tools::getValue('ccc_up')) {
            if ($this->tabAccess['edit'] === '1') {
                $themeCacheDirectory = _PS_ALL_THEMES_DIR_.$this->context->shop->theme_directory.'/cache/';
                if (((bool) Tools::getValue('PS_CSS_THEME_CACHE') || (bool) Tools::getValue('PS_JS_THEME_CACHE')) && !is_writable($themeCacheDirectory)) {
                    $this->errors[] = sprintf(Tools::displayError('To use Smart Cache directory %s must be writable.'), realpath($themeCacheDirectory));
                }

                if ($tmp = (int) Tools::getValue('PS_CSS_THEME_CACHE')) {
                    $version = (int) Configuration::get('PS_CCCCSS_VERSION');
                    if (Configuration::get('PS_CSS_THEME_CACHE') != $tmp) {
                        Configuration::updateValue('PS_CCCCSS_VERSION', ++$version);
                    }
                }

                if ($tmp = (int) Tools::getValue('PS_JS_THEME_CACHE')) {
                    $version = (int) Configuration::get('PS_CCCJS_VERSION');
                    if (Configuration::get('PS_JS_THEME_CACHE') != $tmp) {
                        Configuration::updateValue('PS_CCCJS_VERSION', ++$version);
                    }
                }

                if (!Configuration::updateValue('PS_CSS_THEME_CACHE', (int) Tools::getValue('PS_CSS_THEME_CACHE')) ||
                    !Configuration::updateValue('PS_JS_THEME_CACHE', (int) Tools::getValue('PS_JS_THEME_CACHE')) ||
                    !Configuration::updateValue('PS_JS_HTML_THEME_COMPRESSION', (int) Tools::getValue('PS_JS_HTML_THEME_COMPRESSION')) ||
                    !Configuration::updateValue('PS_JS_DEFER', (int) Tools::getValue('PS_JS_DEFER')) ||
                    !Configuration::updateValue('PS_HTACCESS_CACHE_CONTROL', (int) Tools::getValue('PS_HTACCESS_CACHE_CONTROL'))
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

        if ((bool) Tools::getValue('media_server_up') && !defined('_PS_HOST_MODE_')) {
            if ($this->tabAccess['edit'] === '1') {
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
                    $baseUrls = [];
                    $baseUrls['_MEDIA_SERVER_1_'] = Tools::getValue('_MEDIA_SERVER_1_');
                    $baseUrls['_MEDIA_SERVER_2_'] = Tools::getValue('_MEDIA_SERVER_2_');
                    $baseUrls['_MEDIA_SERVER_3_'] = Tools::getValue('_MEDIA_SERVER_3_');
                    if ($baseUrls['_MEDIA_SERVER_1_'] || $baseUrls['_MEDIA_SERVER_2_'] || $baseUrls['_MEDIA_SERVER_3_']) {
                        Configuration::updateValue('PS_MEDIA_SERVERS', 1);
                    } else {
                        Configuration::updateValue('PS_MEDIA_SERVERS', 0);
                    }
                    rewriteSettingsFile($baseUrls, null, null);
                    Configuration::updateValue('PS_MEDIA_SERVER_1', Tools::getValue('_MEDIA_SERVER_1_'));
                    Configuration::updateValue('PS_MEDIA_SERVER_2', Tools::getValue('_MEDIA_SERVER_2_'));
                    Configuration::updateValue('PS_MEDIA_SERVER_3', Tools::getValue('_MEDIA_SERVER_3_'));
                    Tools::clearSmartyCache();
                    Media::clearCache();

                    if (is_writable(_PS_ROOT_DIR_.'/.htaccess')) {
                        Tools::generateHtaccess(null, null, null, '', null, [$baseUrls['_MEDIA_SERVER_1_'], $baseUrls['_MEDIA_SERVER_2_'], $baseUrls['_MEDIA_SERVER_3_']]);
                        unset($this->_fieldsGeneral['_MEDIA_SERVER_1_']);
                        unset($this->_fieldsGeneral['_MEDIA_SERVER_2_']);
                        unset($this->_fieldsGeneral['_MEDIA_SERVER_3_']);
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

        if ((bool) Tools::getValue('ciphering_up') && Configuration::get('PS_CIPHER_ALGORITHM') != (int) Tools::getValue('PS_CIPHER_ALGORITHM')) {
            if ($this->tabAccess['edit'] === '1') {
                $algo = (int) Tools::getValue('PS_CIPHER_ALGORITHM');
                $prevSettings = file_get_contents(_PS_ROOT_DIR_.'/config/settings.inc.php');
                $newSettings = $prevSettings;
                if ($algo) {
                    if (!function_exists('mcrypt_encrypt')) {
                        $this->errors[] = Tools::displayError('The "Mcrypt" PHP extension is not activated on this server.');
                    } else {
                        if (!strstr($newSettings, '_RIJNDAEL_KEY_')) {
                            $key_size = mcrypt_get_key_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
                            $key = Tools::passwdGen($key_size);
                            $newSettings = preg_replace(
                                '/define\(\'_COOKIE_KEY_\', \'([a-z0-9=\/+-_]+)\'\);/i',
                                'define(\'_COOKIE_KEY_\', \'\1\');'."\n".'define(\'_RIJNDAEL_KEY_\', \''.$key.'\');',
                                $newSettings
                            );
                        }
                        if (!strstr($newSettings, '_RIJNDAEL_IV_')) {
                            $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
                            $iv = base64_encode(mcrypt_create_iv($ivSize, MCRYPT_RAND));
                            $newSettings = preg_replace(
                                '/define\(\'_COOKIE_IV_\', \'([a-z0-9=\/+-_]+)\'\);/i',
                                'define(\'_COOKIE_IV_\', \'\1\');'."\n".'define(\'_RIJNDAEL_IV_\', \''.$iv.'\');',
                                $newSettings
                            );
                        }
                    }
                }
                if (!count($this->errors)) {
                    // If there is not settings file modification or if the backup and replacement of the settings file worked
                    if ($newSettings == $prevSettings || (
                            copy(_PS_ROOT_DIR_.'/config/settings.inc.php', _PS_ROOT_DIR_.'/config/settings.old.php')
                            && (bool) file_put_contents(_PS_ROOT_DIR_.'/config/settings.inc.php', $newSettings)
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

        if ((bool) Tools::getValue('empty_smarty_cache')) {
            $redirectAdmin = true;
            Tools::clearSmartyCache();
            Tools::clearXMLCache();
            Media::clearCache();
            Tools::generateIndex();
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
        }

        if (Tools::isSubmit('submitAddconfiguration')) {
            Configuration::updateGlobalValue('PS_DISABLE_NON_NATIVE_MODULE', (int) Tools::getValue('native_module'));
            Configuration::updateGlobalValue('PS_DISABLE_OVERRIDES', (int) Tools::getValue('overrides'));
            Tools::generateIndex();
        }

        if ($redirectAdmin && (!isset($this->errors) || !count($this->errors))) {
            Hook::exec('action'.get_class($this).ucfirst($this->action).'After', ['controller' => $this, 'return' => '']);
            Tools::redirectAdmin(self::$currentIndex.'&token='.Tools::getValue('token').'&conf=4');
        }
    }
}
