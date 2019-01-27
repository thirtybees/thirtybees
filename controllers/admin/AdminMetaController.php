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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class AdminMetaControllerCore
 *
 * @since 1.0.0
 */
class AdminMetaControllerCore extends AdminController
{
    // @codingStandardsIgnoreStart
    public $table = 'meta';
    public $className = 'Meta';
    public $lang = true;

    /** @var ShopUrl */
    protected $url = false;
    protected $toolbar_scroll = false;
    protected $ht_file = '';
    protected $rb_file = '';
    protected $rb_data = [];
    protected $sm_file = '';
    /** @var Meta $object */
    protected $object;
    // @codingStandardsIgnoreEnd

    /**
     * AdminMetaControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->table = 'meta';
        $this->className = 'Meta';

        $this->bootstrap = true;
        $this->identifier_name = 'page';
        $this->ht_file = _PS_ROOT_DIR_.'/.htaccess';
        $this->rb_file = _PS_ROOT_DIR_.'/robots.txt';
        $this->rb_data = $this->getRobotsContent();

        $this->explicitSelect = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fields_list = [
            'id_meta'     => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'page'        => ['title' => $this->l('Page')],
            'title'       => ['title' => $this->l('Page title')],
            'url_rewrite' => ['title' => $this->l('Friendly URL')],
        ];
        $this->_where = ' AND a.configurable = 1';
        $this->_group = 'GROUP BY a.id_meta';

        parent::__construct();

        $this->sm_file = _PS_ROOT_DIR_.DIRECTORY_SEPARATOR.$this->context->shop->id.'_index_sitemap.xml';
        // Options to generate friendly urls
        $modRewrite = Tools::modRewriteActive();

        // Retrocompatibility with <= 1.0.8. Remove ::hasKey() when Core
        // Updater has learned to add configuration keys.
        $emitSeoFields = Tools::isSubmit('TB_EMIT_SEO_FIELDS') ?
            (bool) Tools::getValue('TB_EMIT_SEO_FIELDS') :
            (
                ! Configuration::hasKey('TB_EMIT_SEO_FIELDS')
                || Configuration::get('TB_EMIT_SEO_FIELDS')
            );

        $generalFields = [
            'PS_REWRITING_SETTINGS'       => [
                'title'      => $this->l('Friendly URL'),
                'hint'       => ($this->l('This option gives your shop SEO friendly, human readable URLs, e.g. http://example.com/blouse instead of http://example.com/index.php?id_product=1&controller=product (recommended).')),
                'validation' => 'isBool',
                'cast'       => 'intval',
                'type'       => 'bool',
                'desc'       => (!$modRewrite ? $this->l('URL rewriting (mod_rewrite) is not active on your server, or it is not possible to check your server configuration. If you want to use Friendly URLs, you must activate this mod.') : ''),
                'disabled'   => !$modRewrite,
            ],
            'PS_ALLOW_ACCENTED_CHARS_URL' => [
                'title'      => $this->l('Accented URL'),
                'hint'       => $this->l('Enable this option if you want to allow accented characters in your friendly URLs.').' '.$this->l('You should only activate this option if you are using non-latin characters. For all the latin charsets, your SEO will be better without this option.'),
                'validation' => 'isBool',
                'cast'       => 'intval',
                'type'       => 'bool',
                'desc'       => (!$modRewrite ? $this->l('Not available because URL rewriting (mod_rewrite) isn\'t available.') : ''),
                'disabled'   => !$modRewrite,
            ],
            'PS_CANONICAL_REDIRECT'       => [
                'title'      => $this->l('Redirect to the canonical URL'),
                'validation' => 'isUnsignedInt',
                'cast'       => 'intval',
                'type'       => 'select',
                'list'       => [
                    ['value' => 0, 'name' => $this->l('No redirection (you may have duplicate content issues)')],
                    ['value' => 1, 'name' => $this->l('302 Moved Temporarily (recommended while setting up your store)')],
                    ['value' => 2, 'name' => $this->l('301 Moved Permanently (recommended once you have gone live)')],
                ],
                'identifier' => 'value',
            ],
            'TB_EMIT_SEO_FIELDS' => [
                'title'      => $this->l('Emit SEO fields') ,
                'hint'       => $this->l('Enable this option to include metadata for canonical url, hreflang, and next/prev page'),
                'validation' => 'isBool',
                'cast'       => 'intval',
                'type'       => 'bool',
                'value'      => $emitSeoFields,
                'auto_value' => false,
            ]
        ];

        $urlDescription = '';
        if ($this->checkConfiguration($this->ht_file)) {
            $generalFields['PS_HTACCESS_DISABLE_MULTIVIEWS'] = [
                'title' => $this->l('Disable Apache\'s MultiViews option'),
                'hint' => $this->l('Enable this option only if you have problems with URL rewriting.'),
                'validation' => 'isBool',
                'cast' => 'intval',
                'type' => 'bool',
            ];

            $generalFields['PS_HTACCESS_DISABLE_MODSEC'] = [
                'title' => $this->l('Disable Apache\'s mod_security module'),
                'hint' => $this->l('Some of thirty bees\' features might not work correctly with a specific configuration of Apache\'s mod_security module. We recommend to turn it off.'),
                'validation' => 'isBool',
                'cast' => 'intval',
                'type' => 'bool',
            ];
        } else {
            $urlDescription = $this->l('Before you can use this tool, you need to:');
            $urlDescription .= $this->l('1) Create a blank .htaccess file in your root directory.');
            $urlDescription .= $this->l('2) Give it write permissions (CHMOD 666 on Unix system).');
        }

        // Options for shop URL if multishop is disabled
        $shopUrlOptions = [
            'title' => $this->l('Set shop URL'),
            'fields' => [],
        ];

        if (!Shop::isFeatureActive()) {
            $this->url = ShopUrl::getShopUrls($this->context->shop->id)->where('main', '=', 1)->getFirst();
            if ($this->url) {
                $shopUrlOptions['description'] = $this->l('Here you can set the URL for your shop. If you migrate your shop to a new URL, remember to change the values below.');
                $shopUrlOptions['fields'] = [
                    'domain'     => [
                        'title'        => $this->l('Shop domain'),
                        'validation'   => 'isString',
                        'type'         => 'text',
                        'defaultValue' => $this->url->domain,
                    ],
                    'domain_ssl' => [
                        'title'        => $this->l('SSL domain'),
                        'validation'   => 'isString',
                        'type'         => 'text',
                        'defaultValue' => $this->url->domain_ssl,
                    ],
                    'uri'        => [
                        'title'        => $this->l('Base URI'),
                        'validation'   => 'isString',
                        'type'         => 'text',
                        'defaultValue' => $this->url->physical_uri,
                    ],
                ];
                $shopUrlOptions['submit'] = ['title' => $this->l('Save')];
            }
        } else {
            $shopUrlOptions['description'] = $this->l('The multistore option is enabled. If you want to change the URL of your shop, you must go to the "Multistore" page under the "Advanced Parameters" menu.');
        }

        // List of options
        $this->fields_options = [
            'general' => [
                'title'       => $this->l('Set up URLs'),
                'description' => $urlDescription,
                'fields'      => $generalFields,
                'submit'      => ['title' => $this->l('Save')],
            ],
        ];

        $this->fields_options['shop_url'] = $shopUrlOptions;

        // Add display route options to options form
        if (Configuration::get('PS_REWRITING_SETTINGS') || Tools::getValue('PS_REWRITING_SETTINGS')) {
            if (Configuration::get('PS_REWRITING_SETTINGS')) {
                $this->addAllRouteFields();
            }
            $this->fields_options['routes']['title'] = $this->l('Schema of URLs');
            $this->fields_options['routes']['description'] = $this->l('This section enables you to change the default pattern of your links. In order to use this functionality, thirty bees\' "Friendly URL" option must be enabled, and Apache\'s URL rewriting module (mod_rewrite) must be activated on your web server.').'<br />'.$this->l('There are several available keywords for each route listed below; note that keywords with * are required!').'<br />'.$this->l('To add a keyword in your URL, use the {keyword} syntax. If the keyword is not empty, you can add text before or after the keyword with syntax {prepend:keyword:append}. For example {-hey-:meta_title} will add "-hey-my-title" in the URL if the meta title is set.');
            $this->fields_options['routes']['submit'] = ['title' => $this->l('Save')];
        }

        // Options to generate robot.txt
        $robotsDescription = $this->l('Your robots.txt file MUST be in your website\'s root directory and nowhere else (e.g. http://www.example.com/robots.txt).').' ';
        if ($this->checkConfiguration($this->rb_file)) {
            $robotsDescription .= $this->l('Generate your "robots.txt" file by clicking on the following button (this will erase the old robots.txt file)');
            $robotsSubmit = [];
        } else {
            $robotsDescription .= $this->l('Before you can use this tool, you need to:');
            $robotsDescription .= $this->l('1) Create a blank robots.txt file in your root directory.');
            $robotsDescription .= $this->l('2) Give it write permissions (CHMOD 666 on Unix system).');
        }

        $this->fields_options['robots'] = [
            'title'  => $this->l('General'),
            'description' => $robotsDescription,
            'icon'   => 'icon-cogs',
            'fields' => [
                'robots' => [
                    'title'                     => $this->l('robots.txt'),
                    'type'                      => 'code',
                    'mode'                      => 'text',
                    'enableBasicAutocompletion' => true,
                    'enableSnippets'            => true,
                    'enableLiveAutocompletion'  => true,
                    'maxLines'                  => 400,
                    'visibility'                => Shop::CONTEXT_ALL,
                    'value'                     => Tools::isSubmit('robots') ? Tools::getValue('robots') : @file_get_contents(_PS_ROOT_DIR_.'/robots.txt'),
                    'auto_value'                => false,
                ],
            ],
            'submit' => isset($robotsSubmit) ? ['title' => $this->l('Save')] : null,
            'buttons' => [
                'generateRobots' => [
                    'class' => 'btn btn-default pull-left',
                    'title' => $this->l('Generate robots.txt file'),
                    'icon' => 'process-icon-cogs',
                    'href' => $this->context->link->getAdminLink('AdminMeta').'&submitGenerateRobots',
                ],
            ],
        ];

        $this->fields_options['htaccess'] = [
            'title'  => $this->l('.htaccess file'),
            'icon'   => 'icon-cogs',
            'fields' => [
                'htaccess' => [
                    'title'                     => $this->l('.htaccess'),
                    'type'                      => 'code',
                    'mode'                      => 'apache_conf',
                    'enableBasicAutocompletion' => true,
                    'enableSnippets'            => true,
                    'enableLiveAutocompletion'  => true,
                    'maxLines'                  => 400,
                    'visibility'                => Shop::CONTEXT_ALL,
                    'value'                     => Tools::isSubmit('htaccess') ? $_POST['htaccess'] : @file_get_contents(_PS_ROOT_DIR_.'/.htaccess'),
                    'auto_value'                => false,
                ],
            ],
            'submit' => ['title' => $this->l('Save')],
            'buttons' => [
                'generateHtaccess' => [
                    'class' => 'btn btn-default pull-left',
                    'title' => $this->l('Generate .htaccess file'),
                    'icon' => 'process-icon-cogs',
                    'href' => $this->context->link->getAdminLink('AdminMeta').'&submitGenerateHtaccess',
                ],
            ],
        ];
    }

    /**
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_meta'] = [
                'href' => static::$currentIndex.'&addmeta&token='.$this->token,
                'desc' => $this->l('Add a new page', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @since 1.0.0
     */
    public function initProcess()
    {
        parent::initProcess();
        // This is a composite page, we don't want the "options" display mode
        if ($this->display == 'options') {
            $this->display = '';
        }
    }

    /**
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryUi('ui.widget');
        $this->addJqueryPlugin('tagify');
    }

    /**
     * @param string $routeId
     * @param string $title
     *
     * @since 1.0.0
     */
    public function addFieldRoute($routeId, $title)
    {
        $keywords = [];
        foreach (Dispatcher::getInstance()->default_routes[$routeId]['keywords'] as $keyword => $data) {
            $keywords[] = ((isset($data['param'])) ? '<span class="red">'.$keyword.'*</span>' : $keyword);
        }
        $this->fields_options['routes']['fields']['PS_ROUTE_'.$routeId] = [
            'title'         => $title,
            'desc'          => sprintf($this->l('Keywords: %s'), implode(', ', $keywords)),
            'validation'    => 'isString',
            'type'          => 'textLang',
            'size'          => 70,
            'defaultValue'  => Dispatcher::getInstance()->default_routes[$routeId]['rule'],
        ];
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $files = Meta::getPages(true, ($this->object->page ? $this->object->page : false));

        $isIndex = false;
        if (is_object($this->object) && is_array($this->object->url_rewrite) && count($this->object->url_rewrite)) {
            foreach ($this->object->url_rewrite as $rewrite) {
                if ($isIndex != true) {
                    $isIndex = ($this->object->page == 'index' && empty($rewrite)) ? true : false;
                }
            }
        }

        $pages = [
            'common' => [
                'name'  => $this->l('Default pages'),
                'query' => [],
            ],
            'module' => [
                'name'  => $this->l('Modules pages'),
                'query' => [],
            ],
        ];

        foreach ($files as $name => $file) {
            $k = (preg_match('#^module-#', $file)) ? 'module' : 'common';
            $pages[$k]['query'][] = [
                'id'   => $file,
                'page' => $name,
            ];
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Meta tags'),
                'icon'  => 'icon-tags',
            ],
            'input'  => [
                [
                    'type' => 'hidden',
                    'name' => 'id_meta',
                ],
                [
                    'type'  => 'select',
                    'label' => $this->l('Page'),
                    'name'  => 'page',

                    'options'       => [
                        'optiongroup' => [
                            'label' => 'name',
                            'query' => $pages,
                        ],
                        'options'     => [
                            'id'    => 'id',
                            'name'  => 'page',
                            'query' => 'query',
                        ],
                    ],
                    'hint'          => $this->l('Name of the related page.'),
                    'required'      => true,
                    'empty_message' => '<p>'.$this->l('There is no page available!').'</p>',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Page title'),
                    'name'  => 'title',
                    'lang'  => true,
                    'hint'  => [
                        $this->l('Title of this page.'),
                        $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                    ],
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Meta description'),
                    'name'  => 'description',
                    'lang'  => true,
                    'hint'  => [
                        $this->l('A short description of your shop.'),
                        $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                    ],
                ],
                [
                    'type'  => 'tags',
                    'label' => $this->l('Meta keywords'),
                    'name'  => 'keywords',
                    'lang'  => true,
                    'hint'  => [
                        $this->l('List of keywords for search engines.'),
                        $this->l('To add tags, click in the field, write something, and then press the "Enter" key.'),
                        $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                    ],
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Rewritten URL'),
                    'name'     => 'url_rewrite',
                    'lang'     => true,
                    'required' => true,
                    'disabled' => (bool) $isIndex,
                    'hint'     => [
                        $this->l('For instance, "contacts" for http://example.com/shop/contacts to redirect to http://example.com/shop/contact-form.php'),
                        $this->l('Only letters and hyphens are allowed.'),
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        return parent::renderForm();
    }

    /**
     * @return bool|Theme|null
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        /* PrestaShop demo mode */
        if (_PS_MODE_DEMO_ && Tools::isSubmit('submitOptionsmeta')
            && (Tools::getValue('domain') != Configuration::get('PS_SHOP_DOMAIN') || Tools::getValue('domain_ssl') != Configuration::get('PS_SHOP_DOMAIN_SSL'))) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return null;
        }

        if (Tools::isSubmit('submitAddmeta')) {
            $defaultLanguage = Configuration::get('PS_LANG_DEFAULT');
            if (Tools::getValue('page') != 'index') {
                $defaultLangIsValidated = Validate::isLinkRewrite(Tools::getValue('url_rewrite_'.$defaultLanguage));
                $englishLangIsValidated = Validate::isLinkRewrite(Tools::getValue('url_rewrite_1'));
            } else {    // index.php can have empty rewrite rule
                $defaultLangIsValidated = !Tools::getValue('url_rewrite_'.$defaultLanguage) || Validate::isLinkRewrite(Tools::getValue('url_rewrite_'.$defaultLanguage));
                $englishLangIsValidated = !Tools::getValue('url_rewrite_1') || Validate::isLinkRewrite(Tools::getValue('url_rewrite_1'));
            }

            if (!$defaultLangIsValidated && !$englishLangIsValidated) {
                $this->errors[] = Tools::displayError('The URL rewrite field must be filled in either the default or English language.');

                return false;
            }

            foreach (Language::getIDs(false) as $idLang) {
                $current = Tools::getValue('url_rewrite_'.$idLang);
                if (strlen($current) == 0) {
                    // Prioritize default language first
                    if ($defaultLangIsValidated) {
                        $_POST['url_rewrite_'.$idLang] = Tools::getValue('url_rewrite_'.$defaultLanguage);
                    } else {
                        $_POST['url_rewrite_'.$idLang] = Tools::getValue('url_rewrite_1');
                    }
                }
            }

            Hook::exec('actionAdminMetaSave');
        } elseif (Tools::isSubmit('submitGenerateRobots')) {
            $this->generateRobotsFile();
        } elseif (Tools::isSubmit('submitGenerateHtaccess')) {
            Tools::generateHtaccess();
        }

        if (Tools::isSubmit('robots')) {
            $this->saveRobotsFile();
            unset($_POST['robots']);
        }

        if (Tools::isSubmit('htaccess')) {
            $this->saveHtaccessFile();
            unset($_POST['htaccess']);
        }

        if (Tools::isSubmit('PS_ROUTE_product_rule')) {
            Tools::clearCache($this->context->smarty);
        }

        if (Tools::isSubmit('deletemeta') && (int) Tools::getValue('id_meta') > 0) {
            Db::getInstance()->delete('theme_meta', 'id_meta='.(int) Tools::getValue('id_meta'));
        }

        /** @var Theme $ret */
        $ret = parent::postProcess();

        if (Tools::isSubmit('submitAddmeta') && Validate::isLoadedObject($ret)) {
            $themes = Theme::getThemes();
            $themeMetaValue = [];
            foreach ($themes as $theme) {
                /** @var Theme $theme */
                $themeMetaValue[] = [
                    'id_theme'     => (int) $theme->id,
                    'id_meta'      => (int) $ret->id,
                    'left_column'  => (int) $theme->default_left_column,
                    'right_column' => (int) $theme->default_right_column,
                ];
            }
            if (count($themeMetaValue) > 0) {
                Db::getInstance()->insert('theme_meta', $themeMetaValue, false, true, Db::INSERT_IGNORE);
            }
        }

        return $ret;
    }

    /**
     * @since 1.0.0
     */
    public function generateRobotsFile()
    {
        if (!$writeFd = @fopen($this->rb_file, 'w')) {
            $this->errors[] = sprintf(Tools::displayError('Cannot write into file: %s. Please check write permissions.'), $this->rb_file);
        } else {
            Hook::exec(
                'actionAdminMetaBeforeWriteRobotsFile',
                [
                    'rb_data' => &$this->rb_data,
                ]
            );

            // PS Comments
            fwrite($writeFd, "# robots.txt automatically generated by thirty bees e-commerce open-source solution\n");
            fwrite($writeFd, "# http://www.thirtybees.com - http://www.thirtybees.com/forums\n");
            fwrite($writeFd, "# This file is to prevent the crawling and indexing of certain parts\n");
            fwrite($writeFd, "# of your site by web crawlers and spiders run by sites like Yahoo!\n");
            fwrite($writeFd, "# and Google. By telling these \"robots\" where not to go on your site,\n");
            fwrite($writeFd, "# you save bandwidth and server resources.\n");
            fwrite($writeFd, "# For more information about the robots.txt standard, see:\n");
            fwrite($writeFd, "# http://www.robotstxt.org/robotstxt.html\n");

            // User-Agent
            fwrite($writeFd, "User-agent: *\n");

            // Allow Directives
            if (count($this->rb_data['Allow'])) {
                fwrite($writeFd, "# Allow Directives\n");
                foreach ($this->rb_data['Allow'] as $allow) {
                    fwrite($writeFd, 'Allow: '.$allow."\n");
                }
            }

            // Private pages
            if (count($this->rb_data['GB'])) {
                fwrite($writeFd, "# Private pages\n");
                foreach ($this->rb_data['GB'] as $gb) {
                    fwrite($writeFd, 'Disallow: /*'.$gb."\n");
                }
            }

            // Directories
            if (count($this->rb_data['Directories'])) {
                fwrite($writeFd, "# Directories\n");
                foreach ($this->rb_data['Directories'] as $dir) {
                    fwrite($writeFd, 'Disallow: */'.$dir."\n");
                }
            }

            // Files
            if (count($this->rb_data['Files'])) {
                $activeLanguageCount = count(Language::getIDs());
                fwrite($writeFd, "# Files\n");
                foreach ($this->rb_data['Files'] as $isoCode => $files) {
                    foreach ($files as $file) {
                        if ($activeLanguageCount > 1) {
                            // Friendly URLs have language ISO code when multiple languages are active
                            fwrite($writeFd, 'Disallow: /'.$isoCode.'/'.$file."\n");
                        } elseif ($activeLanguageCount == 1) {
                            // Friendly URL does not have language ISO when only one language is active
                            fwrite($writeFd, 'Disallow: /'.$file."\n");
                        } else {
                            fwrite($writeFd, 'Disallow: /'.$file."\n");
                        }
                    }
                }
            }

            // Sitemap
            if (file_exists($this->sm_file) && filesize($this->sm_file)) {
                fwrite($writeFd, "# Sitemap\n");
                $sitemapFilename = basename($this->sm_file);
                fwrite($writeFd, 'Sitemap: '.(Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].__PS_BASE_URI__.$sitemapFilename."\n");
            }

            Hook::exec(
                'actionAdminMetaAfterWriteRobotsFile',
                [
                    'rb_data'  => $this->rb_data,
                    'write_fd' => &$writeFd,
                ]
            );

            fclose($writeFd);

            $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
        }
    }

    /**
     * @param int         $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int         $start
     * @param int|null    $limit
     * @param int|bool    $idLangShop
     *
     * @since 1.0.0
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $this->context->shop->id);
    }

    /**
     * @return false|string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            $this->displayInformation($this->l('You can only display the page list in a shop context.'));

            return false;
        } else {
            return parent::renderList();
        }
    }

    /**
     * Validate route syntax and save it in configuration
     *
     * @param string $route
     *
     * @since 1.0.0 Added optional $idLang parameter
     */
    public function checkAndUpdateRoute($route)
    {
        $defaultRoutes = Dispatcher::getInstance()->default_routes;
        if (!isset($defaultRoutes[$route])) {
            return;
        }

        $multiLang = !Tools::getValue('PS_ROUTE_'.$route);

        $errors = [];
        $rule = Tools::getValue('PS_ROUTE_'.$route);
        foreach (Language::getIDs(false) as $idLang) {
            if ($multiLang) {
                $rule = Tools::getValue('PS_ROUTE_'.$route.'_'.$idLang);
            }
            if (!Dispatcher::getInstance()->validateRoute($route, $rule, $errors)) {
                foreach ($errors as $error) {
                    $this->errors[] = sprintf('Keyword "{%1$s}" required for route "%2$s" (rule: "%3$s")', $error, $route, htmlspecialchars($rule));
                }
            } elseif (!$this->checkRedundantRewriteKeywords($rule)) {
                $this->errors[] = sprintf('Rule "%1$s" is invalid. It has duplicate keywords.', htmlspecialchars($rule));
            } else {
                if (preg_match('/}[a-zA-Z0-9-_]*{/', $rule)) {
                    // Two regexes can't be tied together with delimiters that can also occur in the regex itself
                    // The only exception is the ID keyword
                    if (!preg_match('/:\/}[a-zA-Z0-9-_]*{/', $rule) && !preg_match('/}[a-zA-Z0-9-_]*{\/:/', $rule) && !preg_match('#\{([^{}]*:)?id(:[^{}]*)?\}#', $rule)) {
                        $this->errors[] = sprintf('Route "%1$s" with rule: "%2$s" needs a correct delimiter', $route, htmlspecialchars($rule));
                    } else {
                        Configuration::updateValue('PS_ROUTE_'.$route, [(int) $idLang => $rule]);
                    }
                } else {
                    Configuration::updateValue('PS_ROUTE_'.$route, [(int) $idLang => $rule]);
                }
            }
        }
    }

    /**
     * Called when PS_REWRITING_SETTINGS option is saved
     *
     * @since 1.0.0
     */
    public function updateOptionPsRewritingSettings()
    {
        Configuration::updateValue('PS_REWRITING_SETTINGS', (int) Tools::getValue('PS_REWRITING_SETTINGS'));

        $this->updateOptionDomain(Tools::getValue('domain'));
        $this->updateOptionDomainSsl(Tools::getValue('domain_ssl'));

        if (Tools::getIsset('uri')) {
            $this->updateOptionUri(Tools::getValue('uri'));
        }

        if (Tools::generateHtaccess($this->ht_file, null, null, '', Tools::getValue('PS_HTACCESS_DISABLE_MULTIVIEWS'), false, Tools::getValue('PS_HTACCESS_DISABLE_MODSEC'))) {
            Tools::enableCache();
            Tools::clearCache($this->context->smarty);
            Tools::restoreCacheSettings();
        } else {
            Configuration::updateValue('PS_REWRITING_SETTINGS', 0);
            // Message copied/pasted from the information tip
            $message = $this->l('Before being able to use this tool, you need to:');
            $message .= '<br />- '.$this->l('Create a blank .htaccess in your root directory.');
            $message .= '<br />- '.$this->l('Give it write permissions (CHMOD 666 on Unix system).');
            $this->errors[] = $message;
        }
    }

    /**
     * @since 1.0.0
     */
    public function updateOptionPsRouteProductRule()
    {
        $this->checkAndUpdateRoute('product_rule');
    }

    /**
     * @since 1.0.0
     */
    public function updateOptionPsRouteCategoryRule()
    {
        $this->checkAndUpdateRoute('category_rule');
    }

    /**
     * @since 1.0.0
     */
    public function updateOptionPsRouteLayeredRule()
    {
        $this->checkAndUpdateRoute('layered_rule');
    }

    /**
     * @since 1.0.0
     */
    public function updateOptionPsRouteSupplierRule()
    {
        $this->checkAndUpdateRoute('supplier_rule');
    }

    /**
     * @since 1.0.0
     */
    public function updateOptionPsRouteManufacturerRule()
    {
        $this->checkAndUpdateRoute('manufacturer_rule');
    }

    /**
     * @since 1.0.0
     */
    public function updateOptionPsRouteCmsRule()
    {
        $this->checkAndUpdateRoute('cms_rule');
    }

    /**
     * @since 1.0.0
     */
    public function updateOptionPsRouteCmsCategoryRule()
    {
        $this->checkAndUpdateRoute('cms_category_rule');
    }

    /**
     * Update shop domain (for mono shop)
     *
     * @param string $value
     *
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    public function updateOptionDomain($value)
    {
        if (!Shop::isFeatureActive() && $this->url && $this->url->domain != $value) {
            if (Validate::isCleanHtml($value)) {
                $this->url->domain = $value;
                $this->url->update();
                Configuration::updateGlobalValue('PS_SHOP_DOMAIN', $value);
            } else {
                $this->errors[] = Tools::displayError('This domain is not valid.');
            }
        }
    }

    /**
     * Update shop SSL domain (for mono shop)
     *
     * @param string $value
     *
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    public function updateOptionDomainSsl($value)
    {
        if (!Shop::isFeatureActive() && $this->url && $this->url->domain_ssl != $value) {
            if (Validate::isCleanHtml($value)) {
                $this->url->domain_ssl = $value;
                $this->url->update();
                Configuration::updateGlobalValue('PS_SHOP_DOMAIN_SSL', $value);
            } else {
                $this->errors[] = Tools::displayError('The SSL domain is not valid.');
            }
        }
    }

    /**
     * Update shop physical uri for mono shop)
     *
     * @param string $value
     *
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    public function updateOptionUri($value)
    {
        if (!Shop::isFeatureActive() && $this->url && $this->url->physical_uri != $value) {
            $this->url->physical_uri = $value;
            $this->url->update();
        }
    }

    /**
     * Save robots.txt file
     *
     * @since 1.0.0
     */
    public function saveRobotsFile()
    {
        @file_put_contents(_PS_ROOT_DIR_.'/robots.txt', Tools::getValue('robots'));
    }

    /**
     * Save .htaccess file
     *
     * @since 1.0.0
     */
    public function saveHtaccessFile()
    {
        @file_put_contents(_PS_ROOT_DIR_.'/.htaccess', $_POST['htaccess']);
    }

    /**
     * Function used to render the options for this controller
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderOptions()
    {
        // If friendly url is not active, do not display custom routes form
        if (Configuration::get('PS_REWRITING_SETTINGS')) {
            $this->addAllRouteFields();
        }

        if ($this->fields_options && is_array($this->fields_options)) {
            $helper = new HelperOptions($this);
            $this->setHelperDisplay($helper);
            $helper->toolbar_scroll = true;
            $helper->toolbar_btn = [
                'save' => [
                    'href' => '#',
                    'desc' => $this->l('Save'),
                ],
            ];
            $helper->id = $this->id;
            $helper->tpl_vars = $this->tpl_option_vars;
            $options = $helper->generateOptions($this->fields_options);

            return $options;
        }

        return '';
    }

    /**
     * Add all custom route fields to the options form
     *
     * @since 1.0.0
     */
    public function addAllRouteFields()
    {
        $this->addFieldRoute('product_rule', $this->l('Route to products'));
        $this->addFieldRoute('category_rule', $this->l('Route to category'));
        $this->addFieldRoute('layered_rule', $this->l('Route to category which has the "selected_filter" attribute for the "Layered Navigation" (blocklayered) module'));
        $this->addFieldRoute('supplier_rule', $this->l('Route to supplier'));
        $this->addFieldRoute('manufacturer_rule', $this->l('Route to manufacturer'));
        $this->addFieldRoute('cms_rule', $this->l('Route to CMS page'));
        $this->addFieldRoute('cms_category_rule', $this->l('Route to CMS category'));
    }

    /**
     * Check if a file is writable
     *
     * @param string $file
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function checkConfiguration($file)
    {
        if (file_exists($file)) {
            return is_writable($file);
        }

        return is_writable(dirname($file));
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getRobotsContent()
    {
        $tab = [];

        // Special allow directives
        $tab['Allow'] = ['*/modules/*.css', '*/modules/*.js'];

        // Directories
        $tab['Directories'] = ['classes/', 'config/', 'download/', 'mails/', 'modules/', 'translations/', 'tools/'];

        // Files
        $disallowControllers = [
            'addresses', 'address', 'authentication', 'cart', 'discount', 'footer',
            'get-file', 'header', 'history', 'identity', 'images.inc', 'init', 'my-account', 'order', 'order-opc',
            'order-slip', 'order-detail', 'order-follow', 'order-return', 'order-confirmation', 'pagination', 'password',
            'pdf-invoice', 'pdf-order-return', 'pdf-order-slip', 'product-sort', 'search', 'statistics', 'attachment', 'guest-tracking',
        ];

        // Rewrite files
        $tab['Files'] = [];
        if (Configuration::get('PS_REWRITING_SETTINGS')) {
            $sql = 'SELECT ml.url_rewrite, l.iso_code
					FROM '._DB_PREFIX_.'meta m
					INNER JOIN '._DB_PREFIX_.'meta_lang ml ON ml.id_meta = m.id_meta
					INNER JOIN '._DB_PREFIX_.'lang l ON l.id_lang = ml.id_lang
					WHERE l.active = 1 AND m.page IN (\''.implode('\', \'', $disallowControllers).'\')';
            if ($results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql)) {
                foreach ($results as $row) {
                    $tab['Files'][$row['iso_code']][] = $row['url_rewrite'];
                }
            }
        }

        $tab['GB'] = [
            '?orderby=', '?orderway=', '?tag=', '?id_currency=', '?search_query=', '?back=', '?n=',
            '&orderby=', '&orderway=', '&tag=', '&id_currency=', '&search_query=', '&back=', '&n=',
        ];

        foreach ($disallowControllers as $controller) {
            $tab['GB'][] = 'controller='.$controller;
        }

        return $tab;
    }

    /**
     * Check if the rule contains duplicate keywords
     *
     * @param string $rule
     *
     * @return bool
     *
     * @since 1.0.2 To prevent duplicate keywords in rules
     */
    protected function checkRedundantRewriteKeywords($rule)
    {
        preg_match_all('#\{([^{}]*:)?([a-zA-Z]+)(:[^{}]*)?\}#', $rule, $matches);

        if (isset($matches[2]) && is_array($matches[2])) {
            foreach (array_count_values($matches[2]) as $val => $c) {
                if ($c > 1) {
                    return false;
                }
            }
        }

        return true;
    }
}
