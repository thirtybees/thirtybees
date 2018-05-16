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
 * Class AdminThemesControllerCore
 *
 * @since 1.0.0
 */
class AdminThemesControllerCore extends AdminController
{
    const MAX_NAME_LENGTH = 128;
    // @codingStandardsIgnoreStart
    /**
     * This value is used in isThemeCompatible method. only version node with an
     * higher version number will be used in [theme]/config.xml
     *
     * @since 1.4.0.11, check theme compatibility 1.4
     * @var string
     */
    public static $check_features_version = '1.4';
    /**
     * Multidimensional array used to check [theme]/config.xml values,
     * and also checks thirty bees current configuration if not match.
     *
     * @var array
     */
    public static $check_features = [
        'ccc'               => [
            'attributes' => [
                'available' => [
                    'value'              => 'true',
                    /*
                     * accepted attribute value if value doesn't match, thirty bees configuration value must have those values
                    */
                    'check_if_not_valid' => [
                        'PS_CSS_THEME_CACHE'           => 0,
                        'PS_JS_THEME_CACHE'            => 0,
                        'PS_JS_HTML_THEME_COMPRESSION' => 0,
                    ],
                ],
            ],
            'error'      => 'This theme may not correctly use thirty bees\' "combine, compress and cache" options.',
            'tab'        => 'AdminPerformance',
        ],
        'guest_checkout'    => [
            'attributes' => [
                'available' => [
                    'value'              => 'true',
                    'check_if_not_valid' => ['PS_GUEST_CHECKOUT_ENABLED' => 0],
                ],
            ],
            'error'      => 'This theme may not correctly use thirty bees\' "guest checkout" feature.',
            'tab'        => 'AdminPreferences',
        ],
        'one_page_checkout' => [
            'attributes' => [
                'available' => [
                    'value'              => 'true',
                    'check_if_not_valid' => ['PS_ORDER_PROCESS_TYPE' => 0],
                ],
            ],
            'error'      => 'This theme may not correctly use thirty bees\' "one-page checkout" feature.',
            'tab'        => 'AdminPreferences',
        ],
        'store_locator'     => [
            'attributes' => [
                'available' => [
                    'value'              => 'true',
                    'check_if_not_valid' => [
                        'PS_STORES_SIMPLIFIED'     => 0,
                        'PS_STORES_DISPLAY_FOOTER' => 0,
                    ],
                ],
            ],
            'error'      => 'This theme may not correctly use thirty bees\' "store locator" feature.',
            'tab'        => 'AdminStores',
        ],
    ];
    public $className = 'Theme';
    public $table = 'theme';
    protected $toolbar_scroll = false;
    private $img_error;
    public $can_display_themes = false;
    public $to_install = [];
    public $to_enable = [];
    public $to_disable = [];
    public $to_hook = [];
    public $hook_list = [];
    public $module_list = [];
    public $native_modules = [];
    public $user_doc = [];
    public $image_list = [];
    public $to_export = [];
    // @codingStandardsIgnoreEnd

    /**
     * AdminThemesControllerCore constructor.
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * Initialize
     *
     * @return void
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function init()
    {
        // No cache for auto-refresh uploaded logo
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

        parent::init();
        $this->can_display_themes = (!Shop::isFeatureActive() || Shop::getContext() == Shop::CONTEXT_SHOP);

        libxml_use_internal_errors(true);

        // Employee languages used for link and utm_source
        $lang = new Language($this->context->language->id);
        $isoLangUc = strtoupper($lang->iso_code);

        $this->fields_options = [
            'appearance' => [
                'title'      => $this->l('Your current theme'),
                'icon'       => 'icon-html5',
                'tabs'       => [
                    'logo'  => $this->l('Logo'),
                    'logo2' => $this->l('Invoice & Email Logos'),
                    'icons' => $this->l('Icons'),
                ],
                'fields'     => [
                    'PS_LOGO'         => [
                        'title' => $this->l('Header logo'),
                        'hint'  => $this->l('Will appear on main page. Recommended height: 52px. Maximum height on default theme: 65px.'),
                        'type'  => 'file',
                        'name'  => 'PS_LOGO',
                        'tab'   => 'logo',
                        'thumb' => _PS_IMG_.Configuration::get('PS_LOGO'),
                    ],
                    'PS_LOGO_MAIL'    => [
                        'title' => $this->l('Mail logo'),
                        'desc'  => ((Configuration::get('PS_LOGO_MAIL') === false) ? '<span class="light-warning">'.$this->l('Warning: if no email logo is available, the main logo will be used instead.').'</span><br />' : ''),
                        'hint'  => $this->l('Will appear on email headers. If undefined, the header logo will be used.'),
                        'type'  => 'file',
                        'name'  => 'PS_LOGO_MAIL',
                        'tab'   => 'logo2',
                        'thumb' => (Configuration::get('PS_LOGO_MAIL') !== false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO_MAIL'))) ? _PS_IMG_.Configuration::get('PS_LOGO_MAIL') : _PS_IMG_.Configuration::get('PS_LOGO'),
                    ],
                    'PS_LOGO_INVOICE' => [
                        'title' => $this->l('Invoice logo'),
                        'desc'  => ((Configuration::get('PS_LOGO_INVOICE') === false) ? '<span class="light-warning">'.$this->l('Warning: if no invoice logo is available, the main logo will be used instead.').'</span><br />' : ''),
                        'hint'  => $this->l('Will appear on invoice headers.').' '.$this->l('Warning: you can use a PNG file for transparency, but it can take up to 1 second per page for processing. Please consider using JPG instead.'),
                        'type'  => 'file',
                        'name'  => 'PS_LOGO_INVOICE',
                        'tab'   => 'logo2',
                        'thumb' => (Configuration::get('PS_LOGO_INVOICE') !== false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE'))) ? _PS_IMG_.Configuration::get('PS_LOGO_INVOICE') : _PS_IMG_.Configuration::get('PS_LOGO'),
                    ],
                    'PS_FAVICON'      => [
                        'title' => $this->l('Favicon and phone icon'),
                        'hint'  => $this->l('Will appear in the address bar of your web browser or home phone screen'),
                        'desc'  => $this->l('Use a square image 512 x 512 for best results.'),
                        'type'  => 'file',
                        'name'  => 'PS_FAVICON',
                        'tab'   => 'icons',
                        'thumb' => Media::getMediaPath(_PS_IMG_DIR_."favicon_{$this->context->shop->id}.ico").'?'.time(),
                    ],
                    'TB_SOURCE_FAVICON'  => [
                        'title' => $this->l('Source favicon (PNG)'),
                        'hint'  => $this->l('Will appear in the address bar of your web browser.'),
                        'desc'  => $this->l('Make sure you upload a big enough favicon. Preferably one that covers all sizes and a square size.'),
                        'type'  => 'file',
                        'name'  => 'TB_SOURCE_FAVICON',
                        'tab'   => 'icons',
                        'thumb' => $this->thumbnail(_PS_IMG_DIR_."favicon/favicon_{$this->context->shop->id}_source.png", 'favicon_source.png', 512, 'png', true, true),
                    ],
                    'TB_SOURCE_FAVICON_CODE'   => [
                        'title'                     => $this->l('Favicon metas'),
                        'hint'                      => $this->l('The literal favicon meta code that gets included on every page.'),
                        'type'                      => 'code',
                        'mode'                      => 'html',
                        'enableBasicAutocompletion' => true,
                        'enableSnippets'            => true,
                        'enableLiveAutocompletion'  => true,
                        'visibility'                => Shop::CONTEXT_ALL,
                        'minLines'                  => 20,
                        'maxLines'                  => 30,
                        'tab'                       => 'icons',
                        'grab_favicon_template'     => true,
                        'auto_value'                => false,
                        'value'                     => preg_replace('/\<br(\s*)?\/?\>/i', "\n", Configuration::get('TB_SOURCE_FAVICON_CODE')),
                    ],
                    'PS_STORES_ICON'  => [
                        'title' => $this->l('Store icon'),
                        'hint'  => $this->l('Will appear on the store locator (inside Google Maps).').'<br />'.$this->l('Suggested size: 30x30, transparent GIF.'),
                        'type'  => 'file',
                        'name'  => 'PS_STORES_ICON',
                        'tab'   => 'icons',
                        'thumb' => _PS_IMG_.Configuration::get('PS_STORES_ICON'),
                    ],
                ],
                'after_tabs' => [
                    'cur_theme' => Theme::getThemeInfo($this->context->shop->id_theme),
                ],
                'submit'     => ['title' => $this->l('Save')],
                'buttons'    => [],
            ],
        ];

        $installedTheme = Theme::getAllThemes([$this->context->shop->id_theme]);
        $nonInstalledTheme = ($this->context->mode == Context::MODE_HOST) ? [] : Theme::getNonInstalledTheme();
        if (count($installedTheme) || !empty($nonInstalledTheme)) {
            $this->fields_options['theme'] = [
                'title'       => sprintf($this->l('Select a theme for the "%s" shop'), $this->context->shop->name),
                'description' => (!$this->can_display_themes) ? $this->l('You must select a shop from the above list if you wish to choose a theme.') : '',
                'fields'      => [
                    'theme_for_shop' => [
                        'type'                  => 'theme',
                        'themes'                => $installedTheme,
                        'not_installed'         => $nonInstalledTheme,
                        'id_theme'              => $this->context->shop->id_theme,
                        'can_display_themes'    => $this->can_display_themes,
                        'no_multishop_checkbox' => true,
                    ],
                ],
            ];
        }
    }

    /**
     * Render form
     *
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function renderForm()
    {
        $getAvailableThemes = Theme::getAvailable(false);
        $availableThemeDir = [];
        $selectedThemeDir = null;
        $metas = Meta::getMetas();
        $formatedMetas = [];

        $imageUrl = false;
        if ($this->object) {
            if ((int) $this->object->id > 0) {
                $theme = new Theme((int) $this->object->id);
                $themeMetas = Db::getInstance()->executeS(
                    (new DbQuery())
                    ->select('ml.`title`, m.`page`, tm.`left_column` as `left`, tm.`right_column` as `right`, m.`id_meta`, tm.`id_theme_meta`')
                    ->from('theme_meta', 'tm')
                    ->leftJoin('meta', 'm', 'm.`id_meta` = tm.`id_meta`')
                    ->leftJoin('meta_lang', 'ml', 'ml.`id_meta` = m.`id_meta`')
                    ->where('ml.`id_lang` = '.(int) $this->context->language->id)
                    ->where('ml.`id_shop` = '.(int) $this->context->shop->id)
                    ->where('tm.`id_theme` = '.(int) $this->object->id)
                );

                // if no theme_meta are found, we must create them
                if (empty($themeMetas)) {
                    $metas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                        (new DbQuery())
                        ->select('`id_meta`')
                        ->from('meta')
                    );
                    $metasDefault = [];
                    foreach ($metas as $meta) {
                        $tmpMeta['id_meta'] = (int) $meta['id_meta'];
                        $tmpMeta['left'] = 1;
                        $tmpMeta['right'] = 1;
                        $metasDefault[] = $tmpMeta;
                    }
                    $theme->updateMetas($metasDefault);
                    $themeMetas = Db::getInstance()->executeS(
                        (new DbQuery())
                        ->select('ml.`title`, m.`page`, tm.`left_column` as `left`, tm.`right_column` as `right`, m.`id_meta`, tm.`id_theme_meta`')
                        ->from('theme_meta', 'tm')
                        ->leftJoin('meta', 'm', 'm.`id_meta` = tm.`id_meta`')
                        ->leftJoin('meta_lang', 'ml', 'ml.`id_meta` = m.`id_meta`')
                        ->where('ml.`id_lang` = '.(int) $this->context->language->id)
                        ->where('tm.`id_theme` = '.(int) $this->object->id)
                    );
                }

                $imageUrl = '<img alt="preview" src="'.__PS_BASE_URI__.'themes/'.$theme->directory.'/preview.jpg">';

                foreach ($themeMetas as $key => &$meta) {
                    if (!isset($meta['title']) || !$meta['title'] || $meta['title'] == '') {
                        $meta['title'] = $meta['page'];
                    }
                }

                $formatedMetas = $themeMetas;
            }
            $selectedThemeDir = $this->object->directory;
        }

        foreach ($getAvailableThemes as $k => $dirname) {
            $availableThemeDir[$k]['value'] = $dirname;
            $availableThemeDir[$k]['label'] = $dirname;
            $availableThemeDir[$k]['id'] = $dirname;
        };

        $this->fields_form = [
            'tinymce' => false,
            'legend'  => [
                'title' => $this->l('Theme'),
                'icon'  => 'icon-picture',
            ],
            'input'   => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name of the theme'),
                    'name'     => 'name',
                    'required' => true,
                    'hint'     => $this->l('Invalid characters:').' <>;=#{}',
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Preview image for the theme'),
                    'name'          => 'image_preview',
                    'display_image' => true,
                    'hint'          => sprintf($this->l('Maximum image size: %1s'), Tools::formatBytes(Tools::getMaxUploadSize())),
                    'image'         => $imageUrl,
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Default left column'),
                    'name'   => 'default_left_column',
                    'hint'   => $this->l('Choose a default behavior when displaying the column in a new page added by you or by a module.'),
                    'values' => [
                        [
                            'id'    => 'default_left_column_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'default_left_column_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Default right column'),
                    'name'   => 'default_right_column',
                    'hint'   => $this->l('Choose a default behavior when displaying the column in a new page added by you or by a module.'),
                    'values' => [
                        [
                            'id'    => 'default_right_column_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'default_right_column_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Number of products per page'),
                    'name'  => 'product_per_page',
                    'hint'  => $this->l('This value will be used when activating the theme.'),
                ],
            ],
            'submit'  => [
                'title' => $this->l('Save'),
            ],
        ];
        // adding a new theme, you can create a directory, and copy from an existing theme
        if ($this->display == 'add' || !Validate::isLoadedObject($this->object)) {
            $this->fields_form['input'][] = [
                'type'     => 'text',
                'label'    => $this->l('Name of the theme\'s directory'),
                'name'     => 'directory',
                'required' => true,
                'hint'     => $this->l('If the directory does not exist, thirty bees will create it automatically.'),
            ];

            $themeQuery = Theme::getThemes();
            $this->fields_form['input'][] = [
                'type'    => 'select',
                'name'    => 'based_on',
                'label'   => $this->l('Copy missing files from existing theme'),
                'hint'    => $this->l('If you create a new theme from scratch, it is recommended that you use the files from the default theme as a foundation.'),
                'options' => [
                    'id'      => 'id',
                    'name'    => 'name',
                    'default' => [
                        'value' => 0,
                        'label' => '-',
                    ],
                    'query'   => $themeQuery,
                ],
            ];

            $this->fields_form['input'][] = [
                'type'   => 'switch',
                'label'  => $this->l('Responsive'),
                'name'   => 'responsive',
                'hint'   => $this->l('Please indicate if the theme is adapted to all screen sizes (mobile, tablet, desktop).'),
                'values' => [
                    [
                        'id'    => 'responsive_on',
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ],
                    [
                        'id'    => 'responsive_off',
                        'value' => 0,
                        'label' => $this->l('No'),
                    ],
                ],
            ];
        } else {
            $this->fields_form['input'][] = [
                'type'     => 'radio',
                'label'    => $this->l('Directory'),
                'name'     => 'directory',
                'required' => true,
                'br'       => true,
                'values'   => $availableThemeDir,
                'selected' => $selectedThemeDir,
                'hint'     => $this->l('Please select a valid theme directory.'),
            ];
        }

        $list = '';
        if (Tools::getIsset('update'.$this->table)) {
            $fieldsList = [
                'title' => [
                    'title' => $this->l('Meta'),
                    'align' => 'center',
                    'width' => 'auto',
                ],
                'left'  => [
                    'title'  => $this->l('Left column'),
                    'active' => 'left',
                    'type'   => 'bool',
                    'ajax'   => true,
                ],
                'right' => [
                    'title'  => $this->l('Right column'),
                    'active' => 'right',
                    'type'   => 'bool',
                    'ajax'   => true,
                ],
            ];
            $helperList = new HelperList();
            $helperList->tpl_vars = ['icon' => 'icon-columns'];
            $helperList->title = $this->l('Appearance of columns');
            $helperList->no_link = true;
            $helperList->shopLinkType = '';
            $helperList->identifier = 'id_theme_meta';
            $helperList->table = 'meta';
            $helperList->tpl_vars['show_filters'] = false;
            $helperList->currentIndex = $this->context->link->getAdminLink('AdminThemes', false);
            $helperList->token = Tools::getAdminTokenLite('AdminThemes');

            $list = $helperList->generateList($formatedMetas, $fieldsList);
        }

        return parent::renderForm().$list;
    }

    /**
     * Render list
     *
     * @return false|string
     *
     * @throws PrestaShopException
     * @throws PrestaShopExceptionCore
     * @since 1.0.0
     */
    public function renderList()
    {
        return parent::renderList();
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function downloadAddonsThemes()
    {
        return true;
    }

    /**
     * Process add
     *
     * @return bool|Theme
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function processAdd()
    {
        if (Tools::getValue('directory') == '' || Tools::getValue('name') == '') {
            $this->errors[] = $this->l('Form invalid');
            $this->display = 'form';

            return false;
        }
        if (($newDir = Tools::getValue('directory')) != '') {
            if (!Validate::isDirName($newDir)) {
                $this->display = 'add';

                return !($this->errors[] = sprintf(Tools::displayError('"%s" is not a valid directory name'), $newDir));
            }
            if (Theme::getByDirectory($newDir)) {
                $this->display = 'add';

                return !($this->errors[] = Tools::displayError('A directory with this name already exists.'));
            }

            if (mkdir(_PS_ALL_THEMES_DIR_.$newDir, Theme::$access_rights)) {
                $this->confirmations[] = $this->l('The directory was successfully created.');
            }

            if (0 !== $idBased = (int) Tools::getValue('based_on')) {
                $baseTheme = new Theme($idBased);
                $this->copyTheme($baseTheme->directory, $newDir);
            }

            if (isset($_FILES['image_preview']) && $_FILES['image_preview']['error'] == 0) {
                if (@getimagesize($_FILES['image_preview']['tmp_name']) && !ImageManager::validateUpload($_FILES['image_preview'], Tools::getMaxUploadSize())) {
                    move_uploaded_file($_FILES['image_preview']['tmp_name'], _PS_ALL_THEMES_DIR_.$newDir.'/preview.jpg');
                } else {
                    $this->errors[] = $this->l('Image is not valid.');
                    $this->display = 'form';

                    return false;
                }
            }
        }

        /** @var Theme $theme */
        $theme = parent::processAdd();
        if ((int) $theme->product_per_page == 0) {
            $theme->product_per_page = 1;
            $theme->save();
        }
        if (is_object($theme) && (int) $theme->id > 0) {
            $metas = Meta::getMetas();

            foreach ($metas as &$meta) {
                $meta['left'] = $theme->default_left_column;
                $meta['right'] = $theme->default_right_column;
            }
            $theme->updateMetas($metas, true);
        }

        return $theme;
    }

    /**
     * copy $base_theme_dir into $target_theme_dir.
     *
     * @param string $baseThemeDir   relative path to base dir
     * @param string $targetThemeDir relative path to target dir
     *
     * @return bool true if success
     *
     * @since 1.0.0
     */
    protected static function copyTheme($baseThemeDir, $targetThemeDir)
    {
        $res = true;
        $baseThemeDir = Tools::normalizeDirectory($baseThemeDir);
        $baseDir = _PS_ALL_THEMES_DIR_.$baseThemeDir;
        $targetThemeDir = Tools::normalizeDirectory($targetThemeDir);
        $targetDir = _PS_ALL_THEMES_DIR_.$targetThemeDir;
        $files = scandir($baseDir);

        foreach ($files as $file) {
            if (!in_array($file[0], ['.', '..', '.svn'])) {
                if (is_dir($baseDir.$file)) {
                    if (!is_dir($targetDir.$file)) {
                        mkdir($targetDir.$file, Theme::$access_rights);
                    }

                    $res &= static::copyTheme($baseThemeDir.$file, $targetThemeDir.$file);
                } elseif (!file_exists($targetDir.$file)) {
                    $res &= copy($baseDir.$file, $targetDir.$file);
                }
            }
        }

        return $res;
    }

    /**
     * Process update
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function processUpdate()
    {
        if (!($this->tabAccess['delete'] && $this->tabAccess['edit'] && $this->tabAccess['add']) || _PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('You do not have permission to edit here.');
        } else {
            if (Tools::getIsset('id_theme') && Tools::getIsset('name') && Tools::getIsset('directory')) {
                $theme = new Theme((int) Tools::getValue('id_theme'));
                $theme->name = Tools::getValue('name');
                $theme->directory = Tools::getValue('directory');
                $theme->default_left_column = Tools::getValue('default_left_column');
                $theme->default_right_column = Tools::getValue('default_right_column');
                $nbProductPerPage = (int) Tools::getValue('product_per_page');
                if ($nbProductPerPage == 0) {
                    $nbProductPerPage = 1;
                }
                $theme->product_per_page = $nbProductPerPage;
                if ($this->context->shop->id_theme == (int) Tools::getValue('id_theme')) {
                    Configuration::updateValue('PS_PRODUCTS_PER_PAGE', $nbProductPerPage);
                }
                if (isset($_FILES['image_preview']) && $_FILES['image_preview']['error'] == 0) {
                    if (@getimagesize($_FILES['image_preview']['tmp_name']) && !ImageManager::validateUpload($_FILES['image_preview'], 300000)) {
                        move_uploaded_file($_FILES['image_preview']['tmp_name'], _PS_ALL_THEMES_DIR_.$theme->directory.'/preview.jpg');
                    } else {
                        $this->errors[] = $this->l('Image is not valid.');
                        $this->display = 'form';

                        return;
                    }
                }
                $theme->update();
            }
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminThemes').'&conf=29');
        }
    }

    /**
     * Process delete
     *
     * @return bool|false|ObjectModel
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function processDelete()
    {
        if (!$this->tabAccess['delete'] || _PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('You do not have permission to delete here.');
        } else {
            /** @var Theme $obj */
            $obj = $this->loadObject();
            if ($obj) {
                if ($obj->isUsed()) {
                    $this->errors[] = $this->l('The theme is being used by at least one shop. Please choose another theme before continuing.');

                    return false;
                }
                $themes = [];
                foreach (Theme::getThemes() as $theme) {
                    /** @var Theme $theme */
                    if ($theme->id != $obj->id) {
                        $themes[] = $theme->directory;
                    }
                }
                if (is_dir(_PS_ALL_THEMES_DIR_.$obj->directory) && !in_array($obj->directory, $themes)) {
                    Tools::deleteDirectory(_PS_ALL_THEMES_DIR_.$obj->directory.'/');
                }
                $obj->removeMetas();
            } elseif ($obj === false && $themeDir = Tools::getValue('theme_dir')) {
                $themeDir = basename($themeDir);
                if (Tools::deleteDirectory(_PS_ALL_THEMES_DIR_.$themeDir.'/')) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminThemes').'&conf=2');
                } else {
                    $this->errors[] = Tools::displayError('The folder cannot be deleted');
                }
            }
        }

        return parent::processDelete();
    }

    /**
     * Process theme export
     *
     * @return void
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function processExportTheme()
    {
        if (Tools::isSubmit('name')) {
            if ($this->checkPostedDatas()) {
                $filename = Tools::htmlentitiesUTF8($_FILES['documentation']['name']);
                $name = Tools::htmlentitiesUTF8(Tools::getValue('documentationName'));
                $this->user_doc = [$name.'¤doc/'.$filename];

                $table = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('`name`, `width`, `products`, `categories`, `manufacturers`, `suppliers`, `scenes`')
                        ->from('image_type')
                );

                $this->image_list = [];
                foreach ($table as $row) {
                    $this->image_list[] = $row['name'].';'.$row['width'].';'.$row['height'].';'.
                        ($row['products'] == 1 ? 'true' : 'false').';'.
                        ($row['categories'] == 1 ? 'true' : 'false').';'.
                        ($row['manufacturers'] == 1 ? 'true' : 'false').';'.
                        ($row['suppliers'] == 1 ? 'true' : 'false').';'.
                        ($row['scenes'] == 1 ? 'true' : 'false');
                }

                $idShop = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    (new DbQuery())
                        ->select('`id_shop`')
                        ->from('shop')
                        ->where('`id_theme` = '.(int) Tools::getValue('id_theme_export'))
                );

                // Select the list of module for this shop
                $this->module_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('m.`id_module`, m.`name`, m.`active`, ms.`id_shop`')
                        ->from('module', 'm')
                        ->leftJoin('module_shop', 'ms', 'm.`id_module` = ms.`id_module`')
                        ->where('ms.`id_shop` = '.(int) $idShop)
                );

                // Select the list of hook for this shop
                $this->hook_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('h.`id_hook`, h.`name` AS `name_hook`, hm.`position`, hm.`id_module`, m.`name` AS `name_module`, GROUP_CONCAT(hme.`file_name`, ",") AS `exceptions`')
                        ->from('hook', 'h')
                        ->leftJoin('hook_module', 'hm', 'hm.`id_hook` = h.`id_hook`')
                        ->leftJoin('module', 'm', 'hm.`id_module` = m.`id_module`')
                        ->leftOuterJoin('hook_module_exceptions', 'hme', 'hme.`id_module` = hm.`id_module` AND hme.`id_hook` = h.`id_hook`')
                        ->where('hm.`id_shop` = '.(int) $idShop)
                        ->groupBy('hm.`id_module`, h.`id_hook`')
                        ->orderBy('name_module')
                );

                $this->native_modules = $this->getNativeModule();

                foreach ($this->hook_list as &$row) {
                    $row['exceptions'] = trim(preg_replace('/(,,+)/', ',', $row['exceptions']), ',');
                }

                $this->to_install = [];
                $this->to_enable = [];
                $this->to_hook = [];

                foreach ($this->module_list as $array) {
                    if (!static::checkParentClass($array['name'])) {
                        continue;
                    }
                    if (in_array($array['name'], $this->native_modules)) {
                        if ($array['active'] == 1) {
                            $this->to_enable[] = $array['name'];
                        } else {
                            $this->to_disable[] = $array['name'];
                        }
                    } elseif ($array['active'] == 1) {
                        $this->to_install[] = $array['name'];
                    }
                }
                foreach ($this->native_modules as $str) {
                    $flag = 0;
                    if (!static::checkParentClass($str)) {
                        continue;
                    }
                    foreach ($this->module_list as $tmp) {
                        if (in_array($str, $tmp)) {
                            $flag = 1;
                            break;
                        }
                    }
                    if ($flag == 0) {
                        $this->to_disable[] = $str;
                    }
                }

                foreach ($_POST as $key => $value) {
                    if (strncmp($key, 'modulesToExport_module', strlen('modulesToExport_module')) == 0) {
                        $this->to_export[] = $value;
                    }
                }

                if ($this->to_install) {
                    foreach ($this->to_install as $string) {
                        foreach ($this->hook_list as $tmp) {
                            if ($tmp['name_module'] == $string) {
                                $this->to_hook[] = $string.';'.$tmp['name_hook'].';'.$tmp['position'].';'.$tmp['exceptions'];
                            }
                        }
                    }
                }
                if ($this->to_enable) {
                    foreach ($this->to_enable as $string) {
                        foreach ($this->hook_list as $tmp) {
                            if ($tmp['name_module'] == $string) {
                                $this->to_hook[] = $string.';'.$tmp['name_hook'].';'.$tmp['position'].';'.$tmp['exceptions'];
                            }
                        }
                    }
                }

                $themeToExport = new Theme((int) Tools::getValue('id_theme_export'));
                $metas = $themeToExport->getMetas();

                $this->generateXML($themeToExport, $metas);
                $this->generateArchive();
            } else {
                $this->display = 'exporttheme';
            }
        } else {
            $this->display = 'exporttheme';
        }
    }

    /**
     * Check posted data
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function checkPostedDatas()
    {
        $mail = Tools::getValue('email');
        $website = Tools::getValue('website');

        if ($mail && !preg_match('#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#', $mail)) {
            $this->errors[] = $this->l('There is an error in your email syntax!');
        } elseif ($website && (!Validate::isURL($website) || !Validate::isAbsoluteUrl($website))) {
            $this->errors[] = $this->l('There is an error in your URL syntax!');
        } elseif (!$this->checkVersionsAndCompatibility() || !$this->checkNames() || !$this->checkDocumentation()) {
            return false;
        } else {
            return true;
        }

        return false;
    }

    /**
     * Check versions and compatibility
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function checkVersionsAndCompatibility()
    {
        $exp = '#^[0-9]+[.]+[0-9.]*[0-9]$#';

        if (!preg_match('#^[0-9][.][0-9]$#', Tools::getValue('theme_version')) ||
            !preg_match($exp, Tools::getValue('compa_from')) || !preg_match($exp, Tools::getValue('compa_to')) ||
            version_compare(Tools::getValue('compa_from'), Tools::getValue('compa_to')) == 1
        ) {
            $this->errors[] = $this->l('Syntax error on version field. Only digits and periods (.) are allowed, and the compatibility version should be increasing or at least be equal to the previous version.');
        }

        if (count($this->errors) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Check names
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function checkNames()
    {
        $author = Tools::getValue('name');
        $themeName = Tools::getValue('theme_name');

        if (!$author || !Validate::isGenericName($author) || strlen($author) > static::MAX_NAME_LENGTH) {
            $this->errors[] = $this->l('Please enter a valid author name');
        } elseif (!$themeName || !Validate::isGenericName($themeName) || strlen($themeName) > static::MAX_NAME_LENGTH) {
            $this->errors[] = $this->l('Please enter a valid theme name');
        }

        if (count($this->errors) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Check documentation
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function checkDocumentation()
    {
        $extensions = [
            '.pdf',
            '.txt',
        ];

        if (isset($_FILES['documentation']) && $_FILES['documentation']['name'] != '') {
            $extension = strrchr($_FILES['documentation']['name'], '.');
            $name = Tools::getValue('documentationName');

            if (!in_array($extension, $extensions)) {
                $this->errors[] = $this->l('File extension must be .txt or .pdf');
            } elseif ($_FILES['documentation']['error'] > 0) {
                $this->errors[] = $this->l('An error occurred during documentation upload');
            } elseif ($_FILES['documentation']['size'] > 1048576) {
                $this->errors[] = $this->l('An error occurred while uploading the documentation. Maximum size allowed is 1MB.');
            } elseif (!$name || !Validate::isGenericName($name) || strlen($name) > static::MAX_NAME_LENGTH) {
                $this->errors[] = $this->l('Please enter a valid documentation name');
            }
        }

        if (count($this->errors) > 0) {
            return false;
        }

        return true;
    }

    /**
     *
     * @param int $type
     * $type = 0 both native & partner (default)
     * $type = 1 native
     * $type = 2 partner
     *
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function getNativeModule($type = 0)
    {
        return [
            'addsharethis',
            'bankwire',
            'blockadvertising',
            'blockbanner',
            'blockbestsellers',
            'blockcart',
            'blockcategories',
            'blockcms',
            'blockcmsinfo',
            'blockcontact',
            'blockcontactinfos',
            'blockcurrencies',
            'blockcustomerprivacy',
            'blockfacebook',
            'blocklanguages',
            'blocklayered',
            'blocklink',
            'blockmanufacturer',
            'blockmyaccount',
            'blockmyaccountfooter',
            'blocknewproducts',
            'blocknewsletter',
            'blockpaymentlogo',
            'blockpermanentlinks',
            'blockreinsurance',
            'blockrss',
            'blocksearch',
            'blocksharefb',
            'blocksocial',
            'blockspecials',
            'blockstore',
            'blocksupplier',
            'blocktags',
            'blocktopmenu',
            'blockuserinfo',
            'blockviewed',
            'blockwishlist',
            'carriercompare',
            'cashondelivery',
            'cheque',
            'crossselling',
            'dashactivity',
            'dashgoals',
            'dashproducts',
            'dashtrends',
            'dateofdelivery',
            'editorial',
            'favoriteproducts',
            'feeder',
            'followup',
            'gapi',
            'graphnvd3',
            'gridhtml',
            'homefeatured',
            'homeslider',
            'loyalty',
            'mailalerts',
            'newsletter',
            'pagesnotfound',
            'productcomments',
            'productpaymentlogos',
            'productscategory',
            'producttooltip',
            'pscleaner',
            'referralprogram',
            'sekeywords',
            'sendtoafriend',
            'socialsharing',
            'statsbestcategories',
            'statsbestcustomers',
            'statsbestmanufacturers',
            'statsbestproducts',
            'statsbestsuppliers',
            'statsbestvouchers',
            'statscarrier',
            'statscatalog',
            'statscheckup',
            'statsdata',
            'statsequipment',
            'statsforecast',
            'statslive',
            'statsnewsletter',
            'statsorigin',
            'statspersonalinfos',
            'statsproduct',
            'statsregistrations',
            'statssales',
            'statssearch',
            'statsstock',
            'statsvisits',
            'themeconfigurator',
            'trackingfront',
            'vatnumber',
            'watermark',
        ];
    }

    /**
     * Check parent class
     *
     * @param $name
     *
     * @return bool
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function checkParentClass($name)
    {
        if (!$obj = Module::getInstanceByName($name)) {
            return false;
        }
        if (is_callable([$obj, 'validateOrder'])) {
            return false;
        }
        if (is_callable([$obj, 'getDateBetween'])) {
            return false;
        }
        if (is_callable([$obj, 'getGridEngines'])) {
            return false;
        }
        if (is_callable([$obj, 'getGraphEngines'])) {
            return false;
        }
        if (is_callable([$obj, 'hookAdminStatsModules'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Generate XML
     *
     * @param Theme $themeToExport
     * @param array $metas
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    protected function generateXML($themeToExport, $metas)
    {
        $theme = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><!-- Copyright thirty bees --><theme></theme>');
        $theme->addAttribute('version', Tools::getValue('theme_version'));
        $theme->addAttribute('name', Tools::htmlentitiesUTF8(Tools::getValue('theme_name')));
        $theme->addAttribute('directory', Tools::htmlentitiesUTF8(Tools::getValue('theme_directory')));
        $author = $theme->addChild('author');
        $author->addAttribute('name', Tools::htmlentitiesUTF8(Tools::getValue('name')));
        $author->addAttribute('email', Tools::htmlentitiesUTF8(Tools::getValue('email')));
        $author->addAttribute('url', Tools::htmlentitiesUTF8(Tools::getValue('website')));

        $descriptions = $theme->addChild('descriptions');
        $languages = Language::getLanguages();
        foreach ($languages as $language) {
            $val = Tools::htmlentitiesUTF8(Tools::getValue('body_title_'.$language['id_lang']));
            $description = $descriptions->addChild('description', Tools::htmlentitiesUTF8($val));
            $description->addAttribute('iso', $language['iso_code']);
        }

        $variations = $theme->addChild('variations');

        $variation = $variations->addChild('variation');
        $variation->addAttribute('name', Tools::htmlentitiesUTF8(Tools::getValue('theme_name')));
        $variation->addAttribute('directory', Tools::getValue('theme_directory'));
        $variation->addAttribute('responsive', $themeToExport->responsive);
        $variation->addAttribute('default_left_column', $themeToExport->default_left_column);
        $variation->addAttribute('default_right_column', $themeToExport->default_right_column);
        $variation->addAttribute('product_per_page', $themeToExport->product_per_page);
        $variation->addAttribute('from', Tools::getValue('compa_from'));
        $variation->addAttribute('to', Tools::getValue('compa_to'));

        $docs = $theme->addChild('docs');
        if (isset($this->user_doc)) {
            foreach ($this->user_doc as $row) {
                $array = explode('¤', $row);
                $doc = $docs->addChild('doc');
                $doc->addAttribute('name', $array[0]);
                $doc->addAttribute('path', $array[1]);
            }
        }

        $metasXml = $theme->addChild('metas');

        foreach ($metas as $row) {
            $metaObj = new Meta((int) $row['id_meta']);

            $metaXml = $metasXml->addChild('meta');
            $metaXml->addAttribute('meta_page', $metaObj->page);
            $metaXml->addAttribute('left', $row['left_column']);
            $metaXml->addAttribute('right', $row['right_column']);
        }
        $modules = $theme->addChild('modules');
        if (isset($this->to_export)) {
            foreach ($this->to_export as $row) {
                if (!in_array($row, $this->native_modules)) {
                    $module = $modules->addChild('module');
                    $module->addAttribute('action', 'install');
                    $module->addAttribute('name', $row);
                }
            }
        }
        foreach ($this->to_enable as $row) {
            $module = $modules->addChild('module');
            $module->addAttribute('action', 'enable');
            $module->addAttribute('name', $row);
        }
        foreach ($this->to_disable as $row) {
            $module = $modules->addChild('module');
            $module->addAttribute('action', 'disable');
            $module->addAttribute('name', $row);
        }

        $hooks = $modules->addChild('hooks');
        foreach ($this->to_hook as $row) {
            $array = explode(';', $row);
            $hook = $hooks->addChild('hook');
            $hook->addAttribute('module', $array[0]);
            $hook->addAttribute('hook', $array[1]);
            $hook->addAttribute('position', $array[2]);
            if (!empty($array[3])) {
                $hook->addAttribute('exceptions', $array[3]);
            }
        }

        $images = $theme->addChild('images');
        foreach ($this->image_list as $row) {
            $array = explode(';', $row);
            $image = $images->addChild('image');
            $image->addAttribute('name', Tools::htmlentitiesUTF8($array[0]));
            $image->addAttribute('width', $array[1]);
            $image->addAttribute('height', $array[2]);
            $image->addAttribute('products', $array[3]);
            $image->addAttribute('categories', $array[4]);
            $image->addAttribute('manufacturers', $array[5]);
            $image->addAttribute('suppliers', $array[6]);
            $image->addAttribute('scenes', $array[7]);
        }
        $this->xml_file = $theme->asXML();
    }

    /**
     * Generate archive
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function generateArchive()
    {
        $zip = new ZipArchive();
        $zipFileName = md5(time()).'.zip';
        if ($zip->open(_PS_CACHE_DIR_.$zipFileName, ZipArchive::OVERWRITE | ZipArchive::CREATE) === true) {
            if (!$zip->addFromString('Config.xml', $this->xml_file)) {
                $this->errors[] = $this->l('Cannot create config file.');
            }

            if (isset($_FILES['documentation'])) {
                if (!empty($_FILES['documentation']['tmp_name']) &&
                    !empty($_FILES['documentation']['name']) &&
                    !$zip->addFile($_FILES['documentation']['tmp_name'], 'doc/'.$_FILES['documentation']['name'])
                ) {
                    $this->errors[] = $this->l('Cannot copy documentation.');
                }
            }

            $givenPath = realpath(_PS_ALL_THEMES_DIR_.Tools::getValue('theme_directory'));

            if ($givenPath !== false) {
                $psAllThemeDirLenght = strlen(realpath(_PS_ALL_THEMES_DIR_));
                $toComparePath = substr($givenPath, 0, $psAllThemeDirLenght);
                if ($toComparePath != realpath(_PS_ALL_THEMES_DIR_)) {
                    $this->errors[] = $this->l('Wrong theme directory path');
                } else {
                    $this->archiveThisFile($zip, Tools::getValue('theme_directory'), _PS_ALL_THEMES_DIR_, 'themes/');
                    foreach ($this->to_export as $row) {
                        if (!in_array($row, $this->native_modules)) {
                            $this->archiveThisFile($zip, $row, _PS_ROOT_DIR_.'/modules/', 'modules/');
                        }
                    }
                }
            } else {
                $this->errors[] = $this->l('Wrong theme directory path');
            }

            $zip->close();

            if (!is_file(_PS_CACHE_DIR_.$zipFileName)) {
                $this->errors[] = $this->l(sprintf('Could not create %1s', _PS_CACHE_DIR_.$zipFileName));
            }

            if (!$this->errors) {
                if (ob_get_length() > 0) {
                    ob_end_clean();
                }

                ob_start();
                header('Pragma: public');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Cache-Control: public');
                header('Content-Description: File Transfer');
                header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.$zipFileName.'"');
                header('Content-Transfer-Encoding: binary');
                ob_end_flush();
                readfile(_PS_CACHE_DIR_.$zipFileName);
                @unlink(_PS_CACHE_DIR_.$zipFileName);
                exit;
            }
        }

        $this->errors[] = $this->l('An error occurred during the archive generation');
    }

    /**
     * @param ZipArchive $obj
     * @param string     $file
     * @param string     $serverPath
     * @param string     $archivePath
     *
     * @since 1.0.0
     */
    protected function archiveThisFile($obj, $file, $serverPath, $archivePath)
    {
        if (is_dir($serverPath.$file)) {
            $dir = scandir($serverPath.$file);
            foreach ($dir as $row) {
                if ($row[0] != '.') {
                    $this->archiveThisFile($obj, $row, $serverPath.$file.'/', $archivePath.$file.'/');
                }
            }
        } elseif (!$obj->addFile($serverPath.$file, $archivePath.$file)) {
            $this->error = true;
        }
    }

    /**
     * Render theme export
     *
     * @return string
     *
     * @throws Adapter_Exception
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function renderExportTheme()
    {
        if (Tools::getIsset('id_theme_export') && (int) Tools::getValue('id_theme_export') > 0) {
            return $this->renderExportTheme1();
        }

        $themeList = Theme::getThemes();
        $fieldsForm = [
            'form' => [
                'tinymce' => false,
                'legend'  => [
                    'title' => $this->l('Theme'),
                    'icon'  => 'icon-picture',
                ],
                'input'   => [
                    [
                        'type'    => 'select',
                        'name'    => 'id_theme_export',
                        'label'   => $this->l('Choose the theme that you want to export'),
                        'options' => [
                            'id'    => 'id',
                            'name'  => 'name',
                            'query' => $themeList,
                        ],

                    ],
                ],
                'submit'  => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $toolbarBtn['save'] = [
            'href' => '#',
            'desc' => $this->l('Export'),
        ];

        $fieldsValue['id_theme_export'] = [];
        $helper = new HelperForm();

        $helper->currentIndex = $this->context->link->getAdminLink('AdminThemes', false).'&action=exporttheme';
        $helper->token = Tools::getAdminTokenLite('AdminThemes');
        $helper->show_toolbar = true;
        $helper->fields_value = $fieldsValue;
        $helper->toolbar_btn = $toolbarBtn;
        $helper->override_folder = $this->tpl_folder;

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * Render export theme 1
     *
     * @return string
     *
     * @throws Adapter_Exception
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    protected function renderExportTheme1()
    {
        $toInstall = [];

        $moduleList = Db::getInstance()->executeS(
            '
			SELECT m.`id_module`, m.`name`, m.`active`, ms.`id_shop`
			FROM `'._DB_PREFIX_.'module` m
			LEFT JOIN `'._DB_PREFIX_.'module_shop` ms On (m.`id_module` = ms.`id_module`)
			WHERE ms.`id_shop` = '.(int) $this->context->shop->id.'
		'
        );

        // Select the list of hook for this shop
        $hookList = Db::getInstance()->executeS(
            '
			SELECT h.`id_hook`, h.`name` as name_hook, hm.`position`, hm.`id_module`, m.`name` as name_module, GROUP_CONCAT(hme.`file_name`, ",") as exceptions
			FROM `'._DB_PREFIX_.'hook` h
			LEFT JOIN `'._DB_PREFIX_.'hook_module` hm ON hm.`id_hook` = h.`id_hook`
			LEFT JOIN `'._DB_PREFIX_.'module` m ON hm.`id_module` = m.`id_module`
			LEFT OUTER JOIN `'._DB_PREFIX_.'hook_module_exceptions` hme ON (hme.`id_module` = hm.`id_module` AND hme.`id_hook` = h.`id_hook`)
			WHERE hm.`id_shop` = '.(int) $this->context->shop->id.'
			GROUP BY `id_module`, `id_hook`
			ORDER BY `name_module`
		'
        );

        foreach ($hookList as &$row) {
            $row['exceptions'] = trim(preg_replace('/(,,+)/', ',', $row['exceptions']), ',');
        }

        $nativeModules = $this->getNativeModule();

        foreach ($moduleList as $array) {
            if (!static::checkParentClass($array['name'])) {
                continue;
            }
            if (in_array($array['name'], $nativeModules)) {
                if ($array['active'] == 1) {
                    $toEnable[] = $array['name'];
                } else {
                    $toDisable[] = $array['name'];
                }
            } elseif ($array['active'] == 1) {
                $toInstall[] = $array['name'];
            }
        }
        foreach ($nativeModules as $str) {
            $flag = 0;
            if (!$this->checkParentClass($str)) {
                continue;
            }
            foreach ($moduleList as $tmp) {
                if (in_array($str, $tmp)) {
                    $flag = 1;
                    break;
                }
            }
            if ($flag == 0) {
                $toDisable[] = $str;
            }
        }

        $employee = $this->context->employee;
        $mail = Tools::getValue('email') ? Tools::getValue('email') : $employee->email;
        $author = Tools::getValue('author_name') ? Tools::getValue('author_name') : $employee->firstname.' '.$employee->lastname;
        $website = Tools::getValue('website') ? Tools::getValue('website') : Tools::getHttpHost(true);

        $this->formatHelperArray($toInstall);

        $theme = new Theme(Tools::getValue('id_theme_export'));

        $fieldsForm = [
            'form' => [
                'tinymce' => false,
                'legend'  => [
                    'title' => $this->l('Theme configuration'),
                    'icon'  => 'icon-picture',
                ],
                'input'   => [
                    [
                        'type' => 'hidden',
                        'name' => 'id_theme_export',
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'name',
                        'label' => $this->l('Name'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'email',
                        'label' => $this->l('Email'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'website',
                        'label' => $this->l('Website'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'theme_name',
                        'label' => $this->l('Theme name'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'theme_directory',
                        'label' => $this->l('Theme directory'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'body_title',
                        'lang'  => true,
                        'label' => $this->l('Description'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'theme_version',
                        'label' => $this->l('Theme version'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'compa_from',
                        'label' => $this->l('Compatible from'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'compa_to',
                        'label' => $this->l('Compatible to'),
                    ],
                    [
                        'type'  => 'file',
                        'name'  => 'documentation',
                        'label' => $this->l('Documentation'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'documentationName',
                        'label' => $this->l('Documentation name'),
                    ],
                ],
                'submit'  => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        if (count($toInstall) > 0) {
            foreach ($toInstall as $module) {
                $fieldsValue['modulesToExport_module'.$module] = true;
            }

            $fieldsForm['form']['input'][] = [
                'type'   => 'checkbox',
                'label'  => $this->l('Select the theme\'s modules that you wish to export'),
                'values' => [
                    'query' => $this->formatHelperArray($toInstall),
                    'id'    => 'id',
                    'name'  => 'name',
                ],
                'name'   => 'modulesToExport',
            ];
        }

        $defaultLanguage = (int) $this->context->language->id;
        $languages = $this->getLanguages();

        foreach ($languages as $language) {
            $fieldsValue['body_title'][$language['id_lang']] = '';
        }

        $helper = new HelperForm();
        $helper->languages = $languages;
        $helper->default_form_language = $defaultLanguage;
        $fieldsValue['name'] = $author;
        $fieldsValue['email'] = $mail;
        $fieldsValue['website'] = $website;
        $fieldsValue['theme_name'] = $theme->name;
        $fieldsValue['theme_directory'] = $theme->directory;
        $fieldsValue['theme_version'] = '1.0';
        $fieldsValue['compa_from'] = _PS_VERSION_;
        $fieldsValue['compa_to'] = _PS_VERSION_;
        $fieldsValue['id_theme_export'] = Tools::getValue('id_theme_export');
        $fieldsValue['documentationName'] = $this->l('documentation');

        $toolbarBtn['save'] = [
            'href' => '',
            'desc' => $this->l('Save'),
        ];

        $helper->currentIndex = $this->context->link->getAdminLink('AdminThemes', false).'&action=exporttheme';
        $helper->token = Tools::getAdminTokenLite('AdminThemes');
        $helper->show_toolbar = true;
        $helper->fields_value = $fieldsValue;
        $helper->toolbar_btn = $toolbarBtn;
        $helper->override_folder = $this->tpl_folder;

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * Format helper array
     *
     * @param array $originArr
     *
     * @return array
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function formatHelperArray($originArr)
    {
        $formatArray = [];
        foreach ($originArr as $module) {
            $displayName = $module;

            $moduleObj = Module::getInstanceByName($module);
            if (Validate::isLoadedObject($moduleObj)) {
                $displayName = $moduleObj->displayName;
            }

            $tmp = [];
            $tmp['id'] = 'module'.$module;
            $tmp['val'] = $module;
            $tmp['name'] = $displayName;
            $formatArray[] = $tmp;
        }

        return $formatArray;
    }

    /**
     * Process import theme
     *
     * @return bool
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function processImportTheme()
    {
        if (!($this->tabAccess['add'] && $this->tabAccess['delete']) || _PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('You do not have permission to add here.');

            return false;
        } else {
            $this->display = 'importtheme';
            if ($this->context->mode == Context::MODE_HOST) {
                return true;
            }
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['themearchive']) && isset($_POST['filename']) && Tools::isSubmit('theme_archive_server')) {
                $uniqid = uniqid();
                $sandbox = _PS_CACHE_DIR_.'sandbox'.DIRECTORY_SEPARATOR.$uniqid.DIRECTORY_SEPARATOR;
                mkdir($sandbox, 0777, true);
                $archiveUploaded = false;
                if (Tools::getValue('filename') != '') {
                    $uploader = new Uploader('themearchive');
                    $uploader->setCheckFileSize(false);
                    $uploader->setAcceptTypes(array('zip'));
                    $uploader->setSavePath($sandbox);
                    $file = $uploader->process(Theme::UPLOADED_THEME_DIR_NAME.'.zip');
                    if ($file[0]['error'] === 0) {
                        if (Tools::ZipTest($sandbox.Theme::UPLOADED_THEME_DIR_NAME.'.zip')) {
                            $archiveUploaded = true;
                        } else {
                            $this->errors[] = $this->l('Zip file seems to be broken');
                        }
                    } else {
                        $this->errors[] = $file[0]['error'];
                    }
                } elseif (Tools::getValue('themearchiveUrl') != '') {
                    if (!Validate::isModuleUrl($url = Tools::getValue('themearchiveUrl'), $this->errors)) {
                        $this->errors[] = $this->l('Only zip files are allowed');
                    } elseif (!Tools::copy($url, $sandbox.Theme::UPLOADED_THEME_DIR_NAME.'.zip')) {
                        $this->errors[] = $this->l('Error during the file download');
                    } elseif (Tools::ZipTest($sandbox.Theme::UPLOADED_THEME_DIR_NAME.'.zip')) {
                        $archiveUploaded = true;
                    } else {
                        $this->errors[] = $this->l('Zip file seems to be broken');
                    }
                } elseif (Tools::getValue('theme_archive_server') != '') {
                    $filename = _PS_ALL_THEMES_DIR_.Tools::getValue('theme_archive_server');
                    if (substr($filename, -4) != '.zip') {
                        $this->errors[] = $this->l('Only zip files are allowed');
                    } elseif (!copy($filename, $sandbox.Theme::UPLOADED_THEME_DIR_NAME.'.zip')) {
                        $this->errors[] = $this->l('An error has occurred during the file copy.');
                    } elseif (Tools::ZipTest($sandbox.Theme::UPLOADED_THEME_DIR_NAME.'.zip')) {
                        $archiveUploaded = true;
                    } else {
                        $this->errors[] = $this->l('Zip file seems to be broken');
                    }
                } else {
                    $this->errors[] = $this->l('You must upload or enter a location of your zip');
                }
                if ($archiveUploaded) {
                    if ($this->extractTheme($sandbox.Theme::UPLOADED_THEME_DIR_NAME.'.zip', $sandbox)) {
                        $this->installTheme(Theme::UPLOADED_THEME_DIR_NAME, $sandbox);
                    }
                }
                Tools::deleteDirectory($sandbox);
                if (count($this->errors) > 0) {
                    $this->display = 'importtheme';
                } else {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminThemes').'&conf=18');
                }
            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                //method is POST but no uplad info -> there is post error
                $maxPost = (int)ini_get('post_max_size');
                $this->errors[] = sprintf($this->l('The file size exceeds the size allowed by the server. The limit is set to %s MB.'), '<b>'.$maxPost.'</b>');
            }
        }
    }

    /**
     * Extract theme
     *
     * @param $themeZipFile
     * @param $sandbox
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function extractTheme($themeZipFile, $sandbox)
    {
        if (!($this->tabAccess['add'] && $this->tabAccess['edit'] && $this->tabAccess['delete']) || _PS_MODE_DEMO_) {
            $this->errors[] = $this->l('You do not have permission to extract here.');

            return false;
        }

        if (Tools::ZipExtract($themeZipFile, $sandbox.Theme::UPLOADED_THEME_DIR_NAME.'/')) {
            return true;
        }
        $this->errors[] = $this->l('Error during zip extraction');

        return false;
    }

    /**
     * Install theme
     *
     * @param string $themeDir
     * @param bool   $sandbox
     * @param bool   $redirect
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function installTheme($themeDir, $sandbox = false, $redirect = true)
    {
        if ($this->tabAccess['add'] && $this->tabAccess['delete'] && !_PS_MODE_DEMO_) {
            if (!$sandbox) {
                $uniqid = uniqid();
                $sandbox = _PS_CACHE_DIR_.'sandbox'.DIRECTORY_SEPARATOR.$uniqid.DIRECTORY_SEPARATOR;
                mkdir($sandbox);
                Tools::recurseCopy(_PS_ALL_THEMES_DIR_.$themeDir, $sandbox.$themeDir);
            }
            $xmlFile = $sandbox.$themeDir.'/Config.xml';
            if (!$this->checkXmlFields($xmlFile)) {
                $this->errors[] = $this->l('Bad configuration file');
            } else {
                $importedTheme = $this->importThemeXmlConfig(simplexml_load_file($xmlFile));
                foreach ($importedTheme as $theme) {
                    if (Validate::isLoadedObject($theme)) {
                        if (!copy($sandbox.$themeDir.'/Config.xml', _PS_ROOT_DIR_.'/config/xml/themes/'.$theme->directory.'.xml')) {
                            $this->errors[] = $this->l('Can\'t copy configuration file');
                        }
                        $targetDir = _PS_ALL_THEMES_DIR_.$theme->directory;
                        if (file_exists($targetDir)) {
                            Tools::deleteDirectory($targetDir);
                        }
                        $themeDocDir = $targetDir.'/docs/';
                        if (file_exists($themeDocDir)) {
                            Tools::deleteDirectory($themeDocDir);
                        }
                        mkdir($targetDir);
                        mkdir($themeDocDir);
                        Tools::recurseCopy($sandbox.$themeDir.'/themes/'.$theme->directory.'/', $targetDir.'/');
                        Tools::recurseCopy($sandbox.$themeDir.'/doc/', $themeDocDir);
                        Tools::recurseCopy($sandbox.$themeDir.'/modules/', _PS_MODULE_DIR_);
                    } else {
                        $this->errors[] = $theme;
                    }
                }
            }
            Tools::deleteDirectory($sandbox);
        }
        if (!count($this->errors)) {
            if ($redirect) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminThemes').'&conf=18');
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Check XML fields
     *
     * @param string $xmlFile
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function checkXmlFields($xmlFile)
    {
        if (!file_exists($xmlFile) || !$xml = @simplexml_load_file($xmlFile)) {
            return false;
        }
        if (!$xml['version'] || !$xml['name']) {
            return false;
        }
        foreach ($xml->variations->variation as $val) {
            if (!$val['name'] || !$val['directory'] || !$val['from'] || !$val['to']) {
                return false;
            }
        }
        foreach ($xml->modules->module as $val) {
            if (!$val['action'] || !$val['name']) {
                return false;
            }
        }
        foreach ($xml->modules->hooks->hook as $val) {
            if (!$val['module'] || !$val['hook'] || !$val['position']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param SimpleXMLElement $xml
     * @param bool             $themeDir only used if the theme directory to import is already located on the shop
     *
     * @return array|string return array of themes on success, otherwise the error as a string is returned
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function importThemeXmlConfig(SimpleXMLElement $xml, $themeDir = false)
    {
        $attr = $xml->attributes();
        $thName = (string) $attr->name;
        if ($this->isThemeInstalled($thName)) {
            return [sprintf($this->l('Theme %s already installed.'), $thName)];
        }

        $newThemeArray = [];
        foreach ($xml->variations->variation as $variation) {
            $name = strval($variation['name']);

            $newTheme = new Theme();
            $newTheme->name = $name;

            $newTheme->directory = strval($variation['directory']);

            if ($themeDir) {
                $newTheme->name = $themeDir;
                $newTheme->directory = $themeDir;
            }

            if ($this->isThemeInstalled($newTheme->name)) {
                continue;
            }

            $newTheme->product_per_page = Configuration::get('PS_PRODUCTS_PER_PAGE');

            if (isset($variation['product_per_page'])) {
                $newTheme->product_per_page = intval($variation['product_per_page']);
            }

            $newTheme->responsive = false;
            if (isset($variation['responsive'])) {
                $newTheme->responsive = (bool) strval($variation['responsive']);
            }

            $newTheme->default_left_column = true;
            $newTheme->default_right_column = true;

            if (isset($variation['default_left_column'])) {
                $newTheme->default_left_column = (bool) strval($variation['default_left_column']);
            }

            if (isset($variation['default_right_column'])) {
                $newTheme->default_right_column = (bool) strval($variation['default_right_column']);
            }

            $fillDefaultMeta = true;
            $metasXml = [];
            if ($xml->metas->meta) {
                foreach ($xml->metas->meta as $meta) {
                    $metaId = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('`id_meta`')
                            ->from('meta')
                            ->where('`page` = \''.pSQL($meta['meta_page']).'\'')
                    );
                    if ((int) $metaId > 0) {
                        $tmpMeta = [];
                        $tmpMeta['id_meta'] = (int) $metaId;
                        $tmpMeta['left'] = intval($meta['left']);
                        $tmpMeta['right'] = intval($meta['right']);
                        $metasXml[(int) $metaId] = $tmpMeta;
                    }
                }
                $fillDefaultMeta = false;
                if (count($xml->metas->meta) < (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from('meta')
                )) {
                    $fillDefaultMeta = true;
                }
            }

            if ($fillDefaultMeta == true) {
                $metas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('`id_meta`')
                        ->from('meta')
                );
                foreach ($metas as $meta) {
                    if (!isset($metasXml[(int) $meta['id_meta']])) {
                        $tmpMeta['id_meta'] = (int) $meta['id_meta'];
                        $tmpMeta['left'] = $newTheme->default_left_column;
                        $tmpMeta['right'] = $newTheme->default_right_column;
                        $metasXml[(int) $meta['id_meta']] = $tmpMeta;
                    }
                }
            }

            if (!is_dir(_PS_ALL_THEMES_DIR_.$newTheme->directory)) {
                if (!mkdir(_PS_ALL_THEMES_DIR_.$newTheme->directory)) {
                    return sprintf($this->l('Error while creating %s directory'), _PS_ALL_THEMES_DIR_.$newTheme->directory);
                }
            }

            $newTheme->add();

            if ($newTheme->id > 0) {
                $newTheme->updateMetas($metasXml);
                $newThemeArray[] = $newTheme;
            } else {
                $newThemeArray[] = sprintf($this->l('Error while installing theme %s'), $newTheme->name);
            }
        }

        return $newThemeArray;
    }

    /**
     * Check if theme is installed
     *
     * @param string $themeName
     *
     * @return bool
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    protected function isThemeInstalled($themeName)
    {
        $themes = Theme::getThemes();

        foreach ($themes as $themeObject) {
            /** @var Theme $themeObject */
            if ($themeObject->name == $themeName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render theme import
     *
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function renderImportTheme()
    {
        $fieldsForm = [];

        $toolbarBtn['save'] = [
            'href' => '#',
            'desc' => $this->l('Save'),
        ];

        if ($this->context->mode != Context::MODE_HOST) {
            $fieldsForm[0] = [
                'form' => [
                    'tinymce' => false,
                    'legend'  => [
                        'title' => $this->l('Import from your computer'),
                        'icon'  => 'icon-picture',
                    ],
                    'input'   => [
                        [
                            'type'  => 'file',
                            'label' => $this->l('Zip file'),
                            'desc'  => $this->l('Browse your computer files and select the Zip file for your new theme.'),
                            'name'  => 'themearchive',
                        ],
                    ],
                    'submit'  => [
                        'id'    => 'zip',
                        'title' => $this->l('Save'),
                    ],
                ],
            ];

            $fieldsForm[1] = [
                'form' => [
                    'tinymce' => false,
                    'legend'  => [
                        'title' => $this->l('Import from the web'),
                        'icon'  => 'icon-picture',
                    ],
                    'input'   => [
                        [
                            'type'  => 'text',
                            'label' => $this->l('Archive URL'),
                            'desc'  => $this->l('Indicate the complete URL to an online Zip file that contains your new theme. For instance, "http://example.com/files/theme.zip".'),
                            'name'  => 'themearchiveUrl',
                        ],
                    ],
                    'submit'  => [
                        'title' => $this->l('Save'),
                    ],
                ],
            ];

            $themeArchiveServer = [];
            $files = scandir(_PS_ALL_THEMES_DIR_);
            $themeArchiveServer[] = '-';

            foreach ($files as $file) {
                if (is_file(_PS_ALL_THEMES_DIR_.$file) && substr(_PS_ALL_THEMES_DIR_.$file, -4) == '.zip') {
                    $themeArchiveServer[] = [
                        'id'   => basename(_PS_ALL_THEMES_DIR_.$file),
                        'name' => basename(_PS_ALL_THEMES_DIR_.$file),
                    ];
                }
            }

            $fieldsForm[2] = [
                'form' => [
                    'tinymce' => false,
                    'legend'  => [
                        'title' => $this->l('Import from FTP'),
                        'icon'  => 'icon-picture',
                    ],
                    'input'   => [
                        [
                            'type'    => 'select',
                            'label'   => $this->l('Select the archive'),
                            'name'    => 'theme_archive_server',
                            'desc'    => $this->l('This selector lists the Zip files that you uploaded in the \'/themes\' folder.'),
                            'options' => [
                                'id'    => 'id',
                                'name'  => 'name',
                                'query' => $themeArchiveServer,
                            ],
                        ],
                    ],
                    'submit'  => [
                        'title' => $this->l('Save'),
                    ],
                ],
            ];
        }

        $this->context->smarty->assign(
            [
                'import_theme'        => true,
                'logged_on_addons'    => false,
                'iso_code'            => $this->context->language->iso_code,
                'add_new_theme_href'  => static::$currentIndex.'&addtheme&token='.$this->token,
                'add_new_theme_label' => $this->l('Create a new theme'),
            ]
        );

        $createNewThemePanel = $this->context->smarty->fetch('controllers/themes/helpers/view/importtheme_view.tpl');

        $helper = new HelperForm();

        $helper->currentIndex = $this->context->link->getAdminLink('AdminThemes', false).'&action=importtheme';
        $helper->token = Tools::getAdminTokenLite('AdminThemes');
        $helper->show_toolbar = true;
        $helper->toolbar_btn = $toolbarBtn;
        $helper->fields_value['themearchiveUrl'] = '';
        $helper->fields_value['theme_archive_server'] = [];
        $helper->multiple_fieldsets = true;
        $helper->override_folder = $this->tpl_folder;
        $helper->languages = $this->getLanguages();
        $helper->default_form_language = (int) $this->context->language->id;

        return $helper->generateForm($fieldsForm).$createNewThemePanel;
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function initContent()
    {
        if ($this->display == 'list') {
            $this->display = '';
        }
        if (isset($this->display) && method_exists($this, 'render'.$this->display)) {
            $this->content .= $this->initPageHeaderToolbar();

            $this->content .= $this->{'render'.$this->display}();
            $this->context->smarty->assign(
                [
                    'content'                   => $this->content,
                    'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                    'page_header_toolbar_title' => $this->page_header_toolbar_title,
                    'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
                ]
            );
        } else {
            $content = '';
            if (Configuration::hasKey('PS_LOGO') && trim(Configuration::get('PS_LOGO')) != ''
                && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO')) && filesize(_PS_IMG_DIR_.Configuration::get('PS_LOGO'))
            ) {
                list($width, $height, $type, $attr) = getimagesize(_PS_IMG_DIR_.Configuration::get('PS_LOGO'));
                Configuration::updateValue('SHOP_LOGO_HEIGHT', (int) round($height));
                Configuration::updateValue('SHOP_LOGO_WIDTH', (int) round($width));
            }

            $this->content .= $content;

            parent::initContent();
        }
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        if (empty($this->display)) {
            $this->page_header_toolbar_btn['import_theme'] = [
                'href' => static::$currentIndex.'&action=importtheme&token='.$this->token,
                'desc' => $this->l('Add new theme', null, null, false),
                'icon' => 'process-icon-new',
            ];

            if ($this->context->mode) {
                unset($this->toolbar_btn['new']);
            }

            $this->page_header_toolbar_btn['export_theme'] = [
                'href' => static::$currentIndex.'&action=exporttheme&token='.$this->token,
                'desc' => $this->l('Export theme', null, null, false),
                'icon' => 'process-icon-export',
            ];
        }

        if ($this->display == 'importtheme') {
            $this->toolbar_title[] = $this->l('Import theme');
        } elseif ($this->display == 'exporttheme') {
            $this->toolbar_title[] = $this->l('Export theme');
        } else {
            $this->toolbar_title[] = $this->l('Theme');
        }

        $title = implode(' '.Configuration::get('PS_NAVIGATION_PIPE').' ', $this->toolbar_title);
        $this->page_header_toolbar_title = $title;
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessGetAddonsThemes()
    {
        exit;
    }

    /**
     * Render choose theme modules
     *
     * @return string
     *
     * @throws Adapter_Exception
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function renderChooseThemeModule()
    {
        $theme = new Theme((int) Tools::getValue('id_theme'));

        $xml = false;
        if (file_exists(_PS_ROOT_DIR_.'/config/xml/themes/'.$theme->directory.'.xml')) {
            $xml = @simplexml_load_file(_PS_ROOT_DIR_.'/config/xml/themes/'.$theme->directory.'.xml');
        } elseif (file_exists(_PS_ROOT_DIR_.'/config/xml/themes/default.xml')) {
            $xml = @simplexml_load_file(_PS_ROOT_DIR_.'/config/xml/themes/default.xml');
        }

        if ($xml) {
            $themeModule = $this->getModules($xml);

            $toolbarBtn['save'] = [
                'href' => '#',
                'desc' => $this->l('Save'),
            ];

            $toInstall = [];
            $toEnable = [];
            $toDisable = [];

            if (isset($themeModule['to_install'])) {
                $toInstall = $this->formatHelperArray($themeModule['to_install']);
            }
            if (isset($themeModule['to_enable'])) {
                $toEnable = $this->formatHelperArray($themeModule['to_enable']);
            }
            if (isset($themeModule['to_disable'])) {
                $toDisable = $this->formatHelperArray($themeModule['to_disable']);
            }

            $fieldsForm = [
                'form' => [
                    'tinymce'     => false,
                    'legend'      => [
                        'title' => $this->l('Modules to install'),
                        'icon'  => 'icon-picture',
                    ],
                    'description' => $this->l('Themes often include their own modules in order to work properly. This option enables you to choose which modules should be enabled and which should be disabled. If you are unsure of what to do next, just press the "Save" button and proceed to the next step.'),
                    'input'       => [
                        [
                            'type'  => 'shop',
                            'label' => $this->l('Shop association'),
                            'name'  => 'checkBoxShopAsso_theme',
                        ],
                        [
                            'type' => 'hidden',
                            'name' => 'id_theme',
                        ],
                    ],
                    'submit'      => [
                        'title' => $this->l('Save'),
                    ],
                ],
            ];

            if (count($toInstall) > 0) {
                $fieldsForm['form']['input'][] = [
                    'type'   => 'checkbox',
                    'label'  => $this->l('Select the theme\'s modules you wish to install'),
                    'values' => [
                        'query' => $toInstall,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'name'   => 'to_install',
                    'expand' => [
                        'print_total' => count($toInstall),
                        'default'     => 'show',
                        'show'        => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                        'hide'        => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
                    ],
                ];
            }
            if (count($toEnable) > 0) {
                $fieldsForm['form']['input'][] = [
                    'type'   => 'checkbox',
                    'label'  => $this->l('Select the theme\'s modules you wish to enable'),
                    'values' => [
                        'query' => $toEnable,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'name'   => 'to_enable',
                    'expand' => [
                        'print_total' => count($toEnable),
                        'default'     => 'show',
                        'show'        => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                        'hide'        => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
                    ],
                ];
            }
            if (count($toDisable) > 0) {
                $fieldsForm['form']['input'][] = [
                    'type'   => 'checkbox',
                    'label'  => $this->l('Select the theme\'s modules you wish to disable'),
                    'values' => [
                        'query' => $toDisable,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'name'   => 'to_disable',
                    'expand' => [
                        'print_total' => count($toDisable),
                        'default'     => 'show',
                        'show'        => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                        'hide'        => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
                    ],
                ];
            }
            $shops = [];
            $shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
            $tmp['id_shop'] = $shop->id;
            $tmp['id_theme'] = $shop->id_theme;
            $shops[] = $tmp;

            if (Shop::isFeatureActive()) {
                $shops = Shop::getShops();
            }

            $currentShop = $this->context->shop->id;

            foreach ($shops as $shop) {
                $shopTheme = new Theme((int) $shop['id_theme']);
                if ((int) Tools::getValue('id_theme') == (int) $shop['id_theme']) {
                    continue;
                }

                $oldXmlName = 'default.xml';
                if (file_exists(_PS_ROOT_DIR_.'/config/xml/themes/'.$shopTheme->directory.'.xml')) {
                    $oldXmlName = $shopTheme->directory.'.xml';
                }

                $shopXml = @simplexml_load_file(_PS_ROOT_DIR_.'/config/xml/themes/'.$oldXmlName);

                if (!$shopXml) {
                    continue;
                }

                $themeShopModule = $this->getModules($shopXml);

                $toShopUninstall = array_merge($themeShopModule['to_install'], $themeShopModule['to_enable']);

                $toShopUninstall = preg_grep('/dash/', $toShopUninstall, PREG_GREP_INVERT);

                $toShopUninstallClean = array_diff($toShopUninstall, $themeModule['to_enable']);

                $toShopUninstallFormated = $this->formatHelperArray($toShopUninstallClean);

                if (count($toShopUninstallFormated) == 0) {
                    continue;
                }

                $class = '';
                if ($shop['id_shop'] == $currentShop) {
                    $themeModule['to_disable_shop'.$shop['id_shop']] = array_merge($themeShopModule['to_install'], $toShopUninstallClean);
                } else {
                    $class = 'hide';
                }

                $fieldsForm['form']['input'][] = [
                    'type'             => 'checkbox',
                    'label'            => sprintf($this->l('Select the modules from the old %1s theme that you wish to disable'), $shopTheme->directory),
                    'form_group_class' => $class,
                    'values'           => [
                        'query' => $toShopUninstallFormated,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'expand'           => [
                        'print_total' => count($toShopUninstallFormated),
                        'default'     => 'show',
                        'show'        => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                        'hide'        => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
                    ],
                    'name'             => 'to_disable_shop'.$shop['id_shop'],
                ];
            }

            $fieldsValue = $this->formatHelperValuesArray($themeModule);

            $fieldsValue['id_theme'] = (int) Tools::getValue('id_theme');

            $helper = new HelperForm();

            $helper->currentIndex = $this->context->link->getAdminLink('AdminThemes', false).'&action=ThemeInstall';
            $helper->token = Tools::getAdminTokenLite('AdminThemes');
            $helper->submit_action = '';
            $helper->show_toolbar = true;
            $helper->toolbar_btn = $toolbarBtn;
            $helper->fields_value = $fieldsValue;
            $helper->languages = $this->getLanguages();
            $helper->default_form_language = (int) $this->context->language->id;
            $helper->table = 'theme';

            $helper->override_folder = $this->tpl_folder;

            return $helper->generateForm([$fieldsForm]);
        }

        Tools::redirectAdmin($this->context->link->getAdminLink('AdminThemes'));

        return '';
    }

    /**
     * Get modules
     *
     * @param $xml
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function getModules($xml)
    {
        $nativeModules = $this->getNativeModule();
        $themeModule = [];

        $themeModule['to_install'] = [];
        $themeModule['to_enable'] = [];
        $themeModule['to_disable'] = [];
        foreach ($xml->modules->module as $row) {
            if (strval($row['action']) == 'install' && !in_array(strval($row['name']), $nativeModules)) {
                $themeModule['to_install'][] = strval($row['name']);
            } elseif (strval($row['action']) == 'enable') {
                $themeModule['to_enable'][] = strval($row['name']);
            } elseif (strval($row['action']) == 'disable') {
                $themeModule['to_disable'][] = strval($row['name']);
            }
        }

        return $themeModule;
    }

    /**
     * Format helper values array
     *
     * @param array $originArr
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function formatHelperValuesArray($originArr)
    {
        $fmtArr = [];
        foreach ($originArr as $key => $type) {
            foreach ($type as $module) {
                $fmtArr[$key.'_module'.$module] = true;
            }
        }

        return $fmtArr;
    }

    /**
     * Process theme install
     *
     * @return void
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function processThemeInstall()
    {
        $shopsAsso = $this->context->employee->getAssociatedShops();
        if (Shop::isFeatureActive() && !Tools::getIsset('checkBoxShopAsso_theme') && count($shopsAsso) > 1) {
            $this->errors[] = $this->l('You must choose at least one shop.');
            $this->display = 'ChooseThemeModule';

            return;
        }

        $theme = new Theme((int) Tools::getValue('id_theme'));

        if (count($shopsAsso) == 1) {
            $shops = $shopsAsso;
        } else {
            $shops = [Configuration::get('PS_SHOP_DEFAULT')];
            if (Tools::isSubmit('checkBoxShopAsso_theme')) {
                $shops = Tools::getValue('checkBoxShopAsso_theme');
            }
        }

        $xml = false;
        if (file_exists(_PS_ROOT_DIR_.'/config/xml/themes/'.$theme->directory.'.xml')) {
            $xml = @simplexml_load_file(_PS_ROOT_DIR_.'/config/xml/themes/'.$theme->directory.'.xml');
        } elseif (file_exists(_PS_ROOT_DIR_.'/config/xml/themes/default.xml')) {
            $xml = @simplexml_load_file(_PS_ROOT_DIR_.'/config/xml/themes/default.xml');
        }

        if ($xml) {
            $moduleHook = [];
            foreach ($xml->modules->hooks->hook as $row) {
                $name = strval($row['module']);

                $exceptions = (isset($row['exceptions']) ? explode(',', strval($row['exceptions'])) : []);

                $moduleHook[$name]['hook'][] = [
                    'hook'       => strval($row['hook']),
                    'position'   => strval($row['position']),
                    'exceptions' => $exceptions,
                ];
            }

            $this->img_error = $this->updateImages($xml);

            $this->modules_errors = [];
            foreach ($shops as $idShop) {
                foreach ($_POST as $key => $value) {
                    if (strncmp($key, 'to_install', strlen('to_install')) == 0) {
                        $module = Module::getInstanceByName($value);
                        if ($module) {
                            $isInstalledSuccess = true;
                            if (!Module::isInstalled($module->name)) {
                                $isInstalledSuccess = $module->install();
                            }
                            if ($isInstalledSuccess) {
                                if (!Module::isEnabled($module->name)) {
                                    $module->enable();
                                }

                                if ((int) $module->id > 0 && isset($moduleHook[$module->name])) {
                                    $this->hookModule($module->id, $moduleHook[$module->name], $idShop);
                                }
                            } else {
                                $this->modules_errors[] = ['module_name' => $module->name, 'errors' => $module->getErrors()];
                            }

                            unset($moduleHook[$module->name]);
                        }
                    } elseif (strncmp($key, 'to_enable', strlen('to_enable')) == 0) {
                        $module = Module::getInstanceByName($value);
                        if ($module) {
                            $isInstalledSuccess = true;
                            if (!Module::isInstalled($module->name)) {
                                $isInstalledSuccess = $module->install();
                            }

                            if ($isInstalledSuccess) {
                                if (!Module::isEnabled($module->name)) {
                                    $module->enable();
                                }

                                if ((int) $module->id > 0 && isset($moduleHook[$module->name])) {
                                    $this->hookModule($module->id, $moduleHook[$module->name], $idShop);
                                }
                            } else {
                                $this->modules_errors[] = ['module_name' => $module->name, 'errors' => $module->getErrors()];
                            }

                            unset($moduleHook[$module->name]);
                        }
                    } elseif (strncmp($key, 'to_disable', strlen('to_disable')) == 0) {
                        $keyExploded = explode('_', $key);
                        $idShopModule = (int) substr($keyExploded[2], 4);

                        if ((int) $idShopModule > 0 && $idShopModule != (int) $idShop) {
                            continue;
                        }

                        $moduleObj = Module::getInstanceByName($value);
                        if (Validate::isLoadedObject($moduleObj)) {
                            if (Module::isEnabled($moduleObj->name)) {
                                $moduleObj->disable();
                            }

                            unset($moduleHook[$moduleObj->name]);
                        }
                    }
                }
                $shop = new Shop((int) $idShop);
                $shop->id_theme = (int) Tools::getValue('id_theme');
                $this->context->shop->id_theme = $shop->id_theme;
                $this->context->shop->update();
                $shop->save();

                if (Shop::isFeatureActive()) {
                    Configuration::updateValue('PS_PRODUCTS_PER_PAGE', (int) $theme->product_per_page, false, null, (int) $idShop);
                } else {
                    Configuration::updateValue('PS_PRODUCTS_PER_PAGE', (int) $theme->product_per_page);
                }
            }

            $this->doc = [];
            foreach ($xml->docs->doc as $row) {
                $this->doc[strval($row['name'])] = __PS_BASE_URI__.'themes/'.$theme->directory.'/docs/'.basename(strval($row['path']));
            }
        }

        Tools::clearCache($this->context->smarty);
        $this->theme_name = $theme->name;
        $this->display = 'view';
    }

    /**
     * Update images
     *
     * @param SimpleXMLElement $xml
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function updateImages($xml)
    {
        $return = [];

        if (isset($xml->images->image)) {
            foreach ($xml->images->image as $row) {
                Db::getInstance()->delete('image_type', '`name` = \''.pSQL($row['name']).'\'');
                Db::getInstance()->execute(
                    '
					INSERT INTO `'._DB_PREFIX_.'image_type` (`name`, `width`, `height`, `products`, `categories`, `manufacturers`, `suppliers`, `scenes`)
					VALUES (\''.pSQL($row['name']).'\',
						'.(int) $row['width'].',
						'.(int) $row['height'].',
						'.($row['products'] == 'true' ? 1 : 0).',
						'.($row['categories'] == 'true' ? 1 : 0).',
						'.($row['manufacturers'] == 'true' ? 1 : 0).',
						'.($row['suppliers'] == 'true' ? 1 : 0).',
						'.($row['scenes'] == 'true' ? 1 : 0).')'
                );

                $return['ok'][] = [
                    'name'   => strval($row['name']),
                    'width'  => (int) $row['width'],
                    'height' => (int) $row['height'],
                ];
            }
        }

        return $return;
    }

    /**
     * Hook module
     *
     * @param int   $idModule
     * @param array $moduleHooks
     * @param int   $shop
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function hookModule($idModule, $moduleHooks, $shop)
    {
        Db::getInstance()->execute('INSERT IGNORE INTO '._DB_PREFIX_.'module_shop (id_module, id_shop) VALUES('.(int) $idModule.', '.(int) $shop.')');

        Db::getInstance()->execute($sql = 'DELETE FROM `'._DB_PREFIX_.'hook_module` WHERE `id_module` = '.(int) $idModule.' AND id_shop = '.(int) $shop);

        foreach ($moduleHooks as $hooks) {
            foreach ($hooks as $hook) {
                $idHook = (int) Hook::getIdByName($hook['hook']);
	            // Create new hook if module hook is not registered
	            if (!$idHook) {
		            $newHook = new Hook();
		            $newHook->name = pSQL($hook['hook']);
		            $newHook->title = pSQL($hook['hook']);
		            $newHook->live_edit = (bool) preg_match('/^display/i', $newHook->name);
		            $newHook->position = (bool) $newHook->live_edit;
		            $newHook->add();
		            $idHook = (int) $newHook->id;
	            }
                $sqlHookModule = 'INSERT INTO `'._DB_PREFIX_.'hook_module` (`id_module`, `id_shop`, `id_hook`, `position`)
									VALUES ('.(int) $idModule.', '.(int) $shop.', '.$idHook.', '.(int) $hook['position'].')';

                if (count($hook['exceptions']) > 0) {
                    foreach ($hook['exceptions'] as $exception) {
                        $sqlHookModuleExcept = 'INSERT INTO `'._DB_PREFIX_.'hook_module_exceptions` (`id_module`, `id_hook`, `file_name`) VALUES ('.(int) $idModule.', '.$idHook.', "'.pSQL($exception).'")';
                        Db::getInstance()->execute($sqlHookModuleExcept);
                    }
                }
                Db::getInstance()->execute($sqlHookModule);
            }
        }
    }

    /**
     * Render view
     *
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function renderView()
    {
        $this->tpl_view_vars = [
            'doc'            => $this->doc,
            'theme_name'     => $this->theme_name,
            'img_error'      => $this->img_error,
            'modules_errors' => $this->modules_errors,
            'back_link'      => $this->context->link->getAdminLink('AdminThemes'),
            'image_link'     => $this->context->link->getAdminLink('AdminImages'),
        ];

        parent::renderView();
    }

    /**
     * This functions make checks about AdminThemes configuration edition only.
     *
     * @since 1.4
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitOptionstheme') && Tools::isSubmit('id_theme') && !Tools::isSubmit('deletetheme')
            && Tools::getValue('action') != 'ThemeInstall' && $this->context->shop->id_theme != Tools::getValue('id_theme')
        ) {
            $this->display = 'ChooseThemeModule';
        } elseif (Tools::isSubmit('installThemeFromFolder') && ($this->context->mode != Context::MODE_HOST)) {
            $themeDir = Tools::getValue('theme_dir');
            $this->installTheme($themeDir);
        } else {
            // new check compatibility theme feature (1.4) :
            $val = Tools::getValue('PS_THEME');
            Configuration::updateValue('PS_IMG_UPDATE_TIME', time());
            if (!empty($val) && !$this->_isThemeCompatible($val)) { // don't submit if errors
                unset($_POST['submitThemes'.$this->table]);
            }
            Tools::clearCache($this->context->smarty);

            return parent::postProcess();
        }
    }

    /**
     * This function checks if the theme designer has thunk to make his theme compatible 1.4,
     * and noticed it on the $theme_dir/config.xml file. If not, some new functionnalities has
     * to be desactivated
     *
     * @param string $themeDir theme directory
     *
     * @return bool Validity is ok or not
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    protected function _isThemeCompatible($themeDir)
    {
        $return = true;
        $checkVersion = AdminThemes::$check_features_version;

        if (!is_file(_PS_ALL_THEMES_DIR_.$themeDir.'/config.xml')) {
            $this->errors[] = Tools::displayError('The config.xml file is missing in your theme path.').'<br/>';
            $xml = null;
        } else {
            $xml = @simplexml_load_file(_PS_ALL_THEMES_DIR_.$themeDir.'/config.xml');
            if (!$xml) {
                $this->errors[] = Tools::displayError('The config.xml file in your theme path is not a valid XML file.').'<br/>';
            }
        }
        // will be set to false if any version node in xml is correct
        $xmlVersionTooOld = true;

        // foreach version in xml file,
        // node means feature, attributes has to match
        // the corresponding value in AdminThemes::$check_features[feature] array
        $xmlArray = simpleXMLToArray($xml);
        foreach ($xmlArray as $version) {
            if (isset($version['value']) && version_compare($version['value'], $checkVersion) >= 0) {
                foreach (AdminThemes::$check_features as $codeFeature => $arrConfigToCheck) {
                    foreach ($arrConfigToCheck['attributes'] as $attr => $v) {
                        if (!isset($version[$codeFeature]) || !isset($version[$codeFeature][$attr]) || $version[$codeFeature][$attr] != $v['value']) {
                            if (!$this->_checkConfigForFeatures($codeFeature, $attr)) { // feature missing in config.xml file, or wrong attribute value
                                $return = false;
                            }
                        }
                    }
                }
                $xmlVersionTooOld = false;
            }
        }
        if ($xmlVersionTooOld && !$this->_checkConfigForFeatures(array_keys(AdminThemes::$check_features))) {
            $this->errors[] .= Tools::displayError('The config.xml file has not been created for this version of thirty bees.');
            $return = false;
        }

        return $return;
    }

    /**
     * _checkConfigForFeatures
     *
     * @param array $arrFeatures array of feature code to check
     * @param mixed $configItem  will precise the attribute which not matches. If empty, will check every attributes
     *
     * @return bool Error message, or null if disabled
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function _checkConfigForFeatures($arrFeatures, $configItem = [])
    {
        $return = true;
        if (is_array($configItem)) {
            foreach ($arrFeatures as $feature) {
                if (!count($configItem)) {
                    $configItem = array_keys(AdminThemes::$check_features[$feature]['attributes']);
                }
            }
            foreach ($configItem as $attr) {
                $check = $this->_checkConfigForFeatures($arrFeatures, $attr);
                if ($check == false) {
                    $return = false;
                }
            }

            return $return;
        }

        $return = true;
        if (!is_array($arrFeatures)) {
            $arrFeatures = [$arrFeatures];
        }

        foreach ($arrFeatures as $feature) {
            $arrConfigToCheck = AdminThemes::$check_features[$feature]['attributes'][$configItem]['check_if_not_valid'];
            foreach ($arrConfigToCheck as $configKey => $configVal) {
                $configGet = Configuration::get($configKey);
                if ($configGet != $configVal) {
                    $this->errors[] = Tools::displayError(AdminThemes::$check_features[$feature]['error']).'.'.(!empty(AdminThemes::$check_features[$feature]['tab']) ? ' <a href="?tab='.AdminThemes::$check_features[$feature]['tab'].'&amp;token='.Tools::getAdminTokenLite(AdminThemes::$check_features[$feature]['tab']).'" ><u>'.Tools::displayError('You can disable this function.').'</u></a>' : '').'<br/>';
                    $return = false;
                    break; // break for this attributes
                }
            }
        }

        return $return;
    }

    /**
     * Update PS_LOGO
     *
     * @since 1.0.0
     */
    public function updateOptionPsLogo()
    {
        $this->updateLogo('PS_LOGO', 'logo');
    }

    /**
     * Generic function which allows logo upload
     *
     * @param string $fieldName
     * @param string $logoPrefix
     *
     * @return bool
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    protected function updateLogo($fieldName, $logoPrefix)
    {

        $idShop = $this->context->shop->id;
        if (isset($_FILES[$fieldName]['tmp_name']) && $_FILES[$fieldName]['tmp_name'] && $_FILES[$fieldName]['size']) {
            if ($error = ImageManager::validateUpload($_FILES[$fieldName], Tools::getMaxUploadSize())) {
                $this->errors[] = $error;

                return false;
            }
            $tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS');

            if (!$tmpName || !move_uploaded_file($_FILES[$fieldName]['tmp_name'], $tmpName)) {
                return false;
            }

            $ext = ($fieldName == 'PS_STORES_ICON') ? '.gif' : '.jpg';
            $logoName = str_replace('%', '', urlencode(Tools::link_rewrite($this->context->shop->name))).'-'.$logoPrefix.'-'.(int) Configuration::get('PS_IMG_UPDATE_TIME').(int) $idShop.$ext;

            if ($this->context->shop->getContext() == Shop::CONTEXT_ALL || $idShop == 0
                || Shop::isFeatureActive() == false
            ) {
                $logoName = str_replace('%', '', urlencode(Tools::link_rewrite($this->context->shop->name))).'-'.$logoPrefix.'-'.(int) Configuration::get('PS_IMG_UPDATE_TIME').$ext;
            }

            if ($fieldName == 'PS_STORES_ICON') {
                if (!@ImageManager::resize($tmpName, _PS_IMG_DIR_.$logoName, null, null, 'gif', true)) {
                    $this->errors[] = Tools::displayError('An error occurred while attempting to copy your logo.');
                }
            } else {
                if (!@ImageManager::resize($tmpName, _PS_IMG_DIR_.$logoName)) {
                    $this->errors[] = Tools::displayError('An error occurred while attempting to copy your logo.');
                }
            }
            $idShop = null;
            $idShopGroup = null;
            if (!count($this->errors) && @filemtime(_PS_IMG_DIR_.Configuration::get($fieldName))) {
                if (Shop::isFeatureActive()) {
                    if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                        $idShop = Shop::getContextShopID();
                        $idShopGroup = Shop::getContextShopGroupID();
                        Shop::setContext(Shop::CONTEXT_ALL);
                        $logoAll = Configuration::get($fieldName);
                        Shop::setContext(Shop::CONTEXT_GROUP);
                        $logoGroup = Configuration::get($fieldName);
                        Shop::setContext(Shop::CONTEXT_SHOP);
                        $logoShop = Configuration::get($fieldName);
                        if ($logoAll != $logoShop && $logoGroup != $logoShop && $logoShop != false) {
                            @unlink(_PS_IMG_DIR_.Configuration::get($fieldName));
                        }
                    } elseif (Shop::getContext() == Shop::CONTEXT_GROUP) {
                        $idShopGroup = Shop::getContextShopGroupID();
                        Shop::setContext(Shop::CONTEXT_ALL);
                        $logoAll = Configuration::get($fieldName);
                        Shop::setContext(Shop::CONTEXT_GROUP);
                        if ($logoAll != Configuration::get($fieldName)) {
                            @unlink(_PS_IMG_DIR_.Configuration::get($fieldName));
                        }
                    }
                } else {
                    @unlink(_PS_IMG_DIR_.Configuration::get($fieldName));
                }
            }
            Configuration::updateValue($fieldName, $logoName, false, $idShopGroup, $idShop);
            Hook::exec('actionAdminThemesControllerUpdate_optionsAfter');
            @unlink($tmpName);
        }
    }

    /**
     * Update PS_LOGO_MAIL
     *
     * @return void
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function updateOptionPsLogoMail()
    {
        $this->updateLogo('PS_LOGO_MAIL', 'logo_mail');
    }

    /**
     * Update PS_LOGO_INVOICE
     *
     * @return void
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function updateOptionPsLogoInvoice()
    {
        $this->updateLogo('PS_LOGO_INVOICE', 'logo_invoice');
    }

    /**
     * Update PS_STORES_ICON
     *
     * @return void
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function updateOptionPsStoresIcon()
    {
        $this->updateLogo('PS_STORES_ICON', 'logo_stores');
    }

    /**
     * Update PS_FAVICON
     *
     * @return void
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function updateOptionPsFavicon()
    {
        $idShop = $this->context->shop->id;

        if ($idShop == Configuration::get('PS_SHOP_DEFAULT')) {
            $this->uploadIco('PS_FAVICON', _PS_IMG_DIR_.'favicon.ico');
        }
        if ($this->uploadIco('PS_FAVICON', _PS_IMG_DIR_.'favicon_'.(int) $idShop.'.ico')) {
            Configuration::updateValue('PS_FAVICON', 'favicon_'.(int) $idShop.'.ico');
        }

        Configuration::updateGlobalValue('PS_FAVICON', 'favicon.ico');

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex.'&token='.$this->token;
        }
    }

    /**
     * Process the favicon sizes
     *
     * @since 1.0.4
     * @throws PrestaShopException
     */
    public function updateOptionTbSourceFaviconCode()
    {
        if (!file_exists(_PS_IMG_DIR_.'favicon')) {
            $definedUmask = defined('_TB_UMASK_') ? _TB_UMASK_ : 0000;
            $previousUmask = @umask($definedUmask);
            mkdir(_PS_IMG_DIR_.'favicon', 0777);
            @umask($previousUmask);
        }

        $idShop = (int) $this->context->shop->id;
        $this->uploadIco('TB_SOURCE_FAVICON', _PS_IMG_DIR_."favicon/favicon_{$idShop}_source.png");

        $newTemplate = Tools::getValue('TB_SOURCE_FAVICON_CODE');

        // Generate the new header HTML
        $filteredHtml = '';

        // Generate a browserconfig.xml
        $browserConfig = new DOMDocument('1.0', 'UTF-8');
        $main = $browserConfig->createElement('browserconfig');
        $ms = $browserConfig->createElement('msapplication');
        $tile = $browserConfig->createElement('tile');
        $ms->appendChild($tile);
        $main->appendChild($ms);
        $browserConfig->appendChild($main);
        $browserConfig->formatOutput = true;

        // Generate a new manifest.json
        $manifest = [
            'name'             => Configuration::get('PS_SHOP_NAME'),
            'icons'            => [],
            'theme_color'      => '#fad629',
            'background_color' => '#fad629',
            'display'          => 'standalone',
        ];

        // Filter and detect sizes
        $dom = new DOMDocument();
        $dom->loadHTML($newTemplate);
        $links = [];
        foreach ($dom->getElementsByTagName('link') as $elem) {
            $links[] = $elem;
        }
        foreach ($dom->getElementsByTagName('meta') as $elem) {
            $links[] = $elem;
        }
        foreach ($links as $link) {
            foreach ($link->attributes as $attribute) {
                /** @var DOMElement $link */
                if ($favicon = Tools::parseFaviconSizeTag(urldecode($attribute->value))) {
                    ImageManager::resize(
                        _PS_IMG_DIR_."favicon/favicon_{$idShop}_source.png",
                        _PS_IMG_DIR_."favicon/favicon_{$idShop}_{$favicon['width']}_{$favicon['height']}.png",
                        (int) $favicon['width'],
                        (int) $favicon['height'],
                        'png'
                    );

                    if (in_array("{$favicon['width']}x{$favicon['height']}", [
                        '70x70',
                        '150x150',
                        '310x310',
                        '310x150'
                    ])) {
                        $path = Media::getMediaPath(_PS_IMG_DIR_."favicon/favicon_{$idShop}_{$favicon['width']}_{$favicon['height']}.png");
                        $logo = $favicon['width'] == $favicon['height']
                            ? $browserConfig->createElement("square{$favicon['width']}x{$favicon['height']}logo", $path)
                            : $browserConfig->createElement("wide{$favicon['width']}x{$favicon['height']}logo", $path);
                        $tile->appendChild($logo);
                    }

                    $manifest['icons'][] = [
                        'src'   => Media::getMediaPath(_PS_IMG_DIR_."favicon/favicon_{$idShop}_{$favicon['width']}_{$favicon['height']}.png"),
                        'sizes' => "{$favicon['width']}x{$favicon['height']}",
                        'type'  => "image/{$favicon['type']}",
                    ];
                }

                if ($link->hasAttribute('name') && $link->getAttribute('name') === 'theme-color') {
                    $manifest['theme_color'] = $link->getAttribute('content');
                }
                if ($link->hasAttribute('name') && $link->getAttribute('name') === 'background-color') {
                    $manifest['background_color'] = $link->getAttribute('content');
                }
            }
            $filteredHtml .= $dom->saveHTML($link);
        }

        file_put_contents(_PS_IMG_DIR_."favicon/browserconfig_{$idShop}.xml", $browserConfig->saveXML());
        file_put_contents(_PS_IMG_DIR_."favicon/manifest_{$idShop}.json", json_encode($manifest, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT));
        Configuration::updateValue('TB_SOURCE_FAVICON_CODE', nl2br(urldecode($filteredHtml)), true);

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex.'&token='.$this->token;
        }
    }

    /**
     * Upload ICO
     *
     * @param string $name
     * @param string $dest
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function uploadIco($name, $dest)
    {
        if (isset($_FILES[$name]['tmp_name']) && !empty($_FILES[$name]['tmp_name'])) {
            // Check ico validity
            if ($error = ImageManager::validateIconUpload($_FILES[$name])) {
                $this->errors[] = $name . ': ' . $error;
            } elseif (mb_substr($dest, -3) === 'ico' && !@file_put_contents($dest, ImageManager::generateFavicon($_FILES[$name]['tmp_name']))) {
                // Copy new ico
                $this->errors[] = sprintf(Tools::displayError('An error occurred while uploading the favicon: cannot copy file "%s" to folder "%s".'), $_FILES[$name]['tmp_name'], $dest);
            } elseif (mb_substr($dest, -3) !== 'ico' && !@copy($_FILES[$name]['tmp_name'], $dest)) {
                $this->errors[] = sprintf(Tools::displayError('An error occurred while uploading the favicon: cannot copy file "%s" to folder "%s".'), $_FILES[$name]['tmp_name'], $dest);
            }
        }

        return !count($this->errors);
    }

    /**
     * Update PS_FAVICON_57
     *
     * @return void
     *
     * @since 1.0.0
     * @deprecated 1.0.4
     * @throws PrestaShopException
     */
    public function updateOptionPsFavicon_57()
    {
        $idShop = $this->context->shop->id;

        if ($idShop == Configuration::get('PS_SHOP_DEFAULT')) {
            $this->uploadIco('PS_FAVICON_57', _PS_IMG_DIR_.'favicon_57.png');
        }
        if ($this->uploadIco('PS_FAVICON_57', _PS_IMG_DIR_.'favicon_57-'.(int) $idShop.'.png')) {
            Configuration::updateValue('PS_FAVICON_57', 'favicon_57-'.(int) $idShop.'.png');
        }

        Configuration::updateGlobalValue('PS_FAVICON_57', 'favicon_57.png');

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex.'&token='.$this->token;
        } else $this->redirect_after = false;
    }

    /**
     * Update PS_FAVICON_72
     *
     * @since 1.0.0
     * @deprecated 1.0.4
     * @throws PrestaShopException
     */
    public function updateOptionPsFavicon_72()
    {
        $idShop = $this->context->shop->id;

        if ($idShop == Configuration::get('PS_SHOP_DEFAULT')) {
            $this->uploadIco('PS_FAVICON_72', _PS_IMG_DIR_.'favicon_72.png');
        }
        if ($this->uploadIco('PS_FAVICON_72', _PS_IMG_DIR_.'favicon_72-'.(int) $idShop.'.png')) {
            Configuration::updateValue('PS_FAVICON_72', 'favicon_72-'.(int) $idShop.'.png');
        }

        Configuration::updateGlobalValue('PS_FAVICON_72', 'favicon_72.png');

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex.'&token='.$this->token;
        } else $this->redirect_after = false;
    }

    /**
     * Update PS_FAVICON_114
     *
     * @since 1.0.0
     * @deprecated 1.0.4
     * @throws PrestaShopException
     */
    public function updateOptionPsFavicon_114()
    {
        $idShop = $this->context->shop->id;

        if ($idShop == Configuration::get('PS_SHOP_DEFAULT')) {
            $this->uploadIco('PS_FAVICON_114', _PS_IMG_DIR_.'favicon_114.png');
        }
        if ($this->uploadIco('PS_FAVICON_114', _PS_IMG_DIR_.'favicon_114-'.(int) $idShop.'.png')) {
            Configuration::updateValue('PS_FAVICON_114', 'favicon_114-'.(int) $idShop.'.png');
        }

        Configuration::updateGlobalValue('PS_FAVICON_114', 'favicon_114.png');

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex.'&token='.$this->token;
        } else $this->redirect_after = false;
    }

    /**
     * Update PS_FAVICON_144
     *
     * @since 1.0.0
     * @deprecated 1.0.4
     * @throws PrestaShopException
     */
    public function updateOptionPsFavicon_144()
    {
        $idShop = $this->context->shop->id;

        if ($idShop == Configuration::get('PS_SHOP_DEFAULT')) {
            $this->uploadIco('PS_FAVICON_144', _PS_IMG_DIR_.'favicon_144.png');
        }
        if ($this->uploadIco('PS_FAVICON_144', _PS_IMG_DIR_.'favicon_144-'.(int) $idShop.'.png')) {
            Configuration::updateValue('PS_FAVICON_144', 'favicon_144-'.(int) $idShop.'.png');
        }

        Configuration::updateGlobalValue('PS_FAVICON_144', 'favicon_144.png');

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex.'&token='.$this->token;
        } else $this->redirect_after = false;
    }

    /**
     * Update PS_FAVICON_192
     *
     * @since 1.0.0
     * @deprecated 1.0.4
     * @throws PrestaShopException
     */
    public function updateOptionPsFavicon_192()
    {
        $idShop = $this->context->shop->id;

        if ($idShop == Configuration::get('PS_SHOP_DEFAULT')) {
            $this->uploadIco('PS_FAVICON_192', _PS_IMG_DIR_.'favicon_192.png');
        }
        if ($this->uploadIco('PS_FAVICON_192', _PS_IMG_DIR_.'favicon_192-'.(int) $idShop.'.png')) {
            Configuration::updateValue('PS_FAVICON_192', 'favicon_192-'.(int) $idShop.'.png');
        }

        Configuration::updateGlobalValue('PS_FAVICON_192', 'favicon_192.png');

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex.'&token='.$this->token;
        } else $this->redirect_after = false;
    }

    /**
     * Refresh the favicon template
     *
     * @since 1.0.4 to enable the favicon template
     */
    function ajaxProcessRefreshFaviconTemplate()
    {
        try {
            $template = (string) (new \GuzzleHttp\Client([
                'http_errors' => false,
                'verify'      => _PS_TOOL_DIR_.'cacert.pem',
                'timeout'     => 60,
            ]))->get('https://raw.githubusercontent.com/thirtybees/favicons/master/template.html')->getBody();
        } catch (Exception $e) {
            $this->ajaxDie(json_encode([
                'hasError' => true,
                'error'    => $e->getMessage(),
            ]));
        }

        if (!$template) {
            $this->ajaxDie(json_encode([
                'hasError' => true,
                'error' => '',
            ]));
        }

        $this->ajaxDie(json_encode([
            'hasError' => false,
            'template' => base64_encode($template),
            'error'    => '',
        ]));
    }

    /**
     * Update theme for current shop
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function updateOptionThemeForShop()
    {
        if (!$this->can_display_themes) {
            return;
        }

        $idTheme = (int) Tools::getValue('id_theme');
        if ($idTheme && $this->context->shop->id_theme != $idTheme) {
            $this->context->shop->id_theme = $idTheme;
            $this->context->shop->update();
            $this->redirect_after = static::$currentIndex.'&token='.$this->token;
        }
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
        if (isset($_GET['error'])) {
            $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }

        if ((isset($_GET['responsive'.$this->table]) || isset($_GET['responsive'])) && Tools::getValue($this->identifier)) {
            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'responsive';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif ((isset($_GET['default_left_column'.$this->table]) || isset($_GET['default_left_column'])) && Tools::getValue($this->identifier)) {
            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'defaultleftcolumn';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif ((isset($_GET['default_right_column'.$this->table]) || isset($_GET['default_right_column'])) && Tools::getValue($this->identifier)) {
            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'defaultrightcolumn';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::getIsset('id_theme_meta') && Tools::getIsset('leftmeta')) {
            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'leftmeta';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::getIsset('id_theme_meta') && Tools::getIsset('rightmeta')) {
            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'rightmeta';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }

        parent::initProcess();
        // This is a composite page, we don't want the "options" display mode
        if ($this->display == 'options' || $this->display == 'list') {
            $this->display = '';
        }
    }

    /**
     * Print responsive icon
     *
     * @param mixed $value
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function printResponsiveIcon($value)
    {
        return ($value ? '<span class="list-action-enable  action-enabled"><i class="icon-check"></i></span>' : '<span class="list-action-enable  action-disabled"><i class="icon-remove"></i></span>');
    }

    /**
     * Process responsive
     *
     * @return false|ObjectModel|Theme
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function processResponsive()
    {
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            /** @var Theme $object */
            if ($object->toggleResponsive()) {
                $this->redirect_after = static::$currentIndex.'&conf=5&token='.$this->token;
            } else {
                $this->errors[] = Tools::displayError('An error occurred while updating responsive status.');
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating the responsive status for this object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }

        return $object;
    }

    /**
     * Process default left column
     *
     * @return false|ObjectModel|Theme
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function processDefaultLeftColumn()
    {
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            /** @var Theme $object */
            if ($object->toggleDefaultLeftColumn()) {
                $this->redirect_after = static::$currentIndex.'&conf=5&token='.$this->token;
            } else {
                $this->errors[] = Tools::displayError('An error occurred while updating default left column status.');
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating the default left column status for this object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }

        return $object;
    }

    /**
     * Process default right column
     *
     * @return false|ObjectModel|Theme
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function processDefaultRightColumn()
    {
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            /** @var Theme $object */
            if ($object->toggleDefaultRightColumn()) {
                $this->redirect_after = static::$currentIndex.'&conf=5&token='.$this->token;
            } else {
                $this->errors[] = Tools::displayError('An error occurred while updating default right column status.');
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating the default right column status for this object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }

        return $object;
    }

    /**
     * Ajax process left meta
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function ajaxProcessLeftMeta()
    {

        $result = Db::getInstance()->update(
            'theme_meta',
            [
                'left_column' => ['type' => 'sql', 'value' => 'NOT `left_column`'],
            ],
            '`id_theme_meta` = '.(int) Tools::getValue('id_theme_meta'),
            1
        );

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }
    }

    /**
     * Process left meta
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function processLeftMeta()
    {
        $result = Db::getInstance()->update(
            'theme_meta',
            [
                'left_column' => ['type' => 'sql', 'value' => 'NOT `left_column`'],
            ],
            '`id_theme_meta` = '.(int) Tools::getValue('id_theme_meta'),
            1
        );

        if ($result) {
            $idTheme = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_theme`')
                    ->from('theme_meta')
                    ->where('`id_theme_meta` = '.(int) Tools::getValue('id_theme_meta'))
            );

            $this->redirect_after = static::$currentIndex.'&updatetheme&id_theme='.$idTheme.'&conf=5&token='.$this->token;
        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating this meta.');
        }
    }

    /**
     * Ajax process right meta
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @sicne 1.0.0
     */
    public function ajaxProcessRightMeta()
    {
        $result = Db::getInstance()->update(
            'theme_meta',
            [
                'right_column' => ['type' => 'sql', 'value' => 'NOT `right_column`'],
            ],
            '`id_theme_meta` = '.(int) Tools::getValue('id_theme_meta'),
            1
        );

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }
    }

    /**
     * Process right meta
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function processRightMeta()
    {
        $result = Db::getInstance()->update(
            'theme_meta',
            [
                'right_column' => ['type' => 'sql', 'value' => 'NOT `right_column`'],
            ],
            '`id_theme_meta` = '.(int) Tools::getValue('id_theme_meta'),
            1
        );

        if ($result) {
            $idTheme = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_theme`')
                    ->from('theme_meta')
                    ->where('`id_theme_meta` = '.(int) Tools::getValue('id_theme_meta'))
            );

            $this->redirect_after = static::$currentIndex.'&updatetheme&id_theme='.$idTheme.'&conf=5&token='.$this->token;
        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating this meta.');
        }
    }

    /**
     * Function used to render the options for this controller
     *
     * @return string
     *
     * @throws Exception
     * @throws HTMLPurifier_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function renderOptions()
    {
        if (isset($this->display) && method_exists($this, 'render'.$this->display)) {
            return $this->{'render'.$this->display}();
        }

        if ($this->fields_options && is_array($this->fields_options)) {
            $helper = new HelperOptions($this);
            $this->setHelperDisplay($helper);
            $helper->toolbar_scroll = true;
            $helper->title = $this->l('Theme appearance');
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
     * Set media
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJS(_PS_JS_DIR_.'admin/themes.js');
    }

    /**
     * Process update options
     *
     * @return void
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    protected function processUpdateOptions()
    {
        if (!($this->tabAccess['add'] && $this->tabAccess['edit'] && $this->tabAccess['delete']) || _PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('You do not have permission to edit here.');
        } else {
            parent::processUpdateOptions();
        }

        if (!count($this->errors)) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminThemes').'&conf=6');
        }
    }

    /**
     * Recursive copy
     *
     * @param string $src
     * @param string $dst
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function recurseCopy($src, $dst)
    {
        if (!$dir = opendir($src)) {
            return;
        }
        if (!file_exists($dst)) {
            mkdir($dst);
        }
        while (($file = readdir($dir)) !== false) {
            if (strncmp($file, '.', 1) != 0) {
                if (is_dir($src.'/'.$file)) {
                    static::recurseCopy($src.'/'.$file, $dst.'/'.$file);
                } elseif (is_readable($src.'/'.$file) && $file != 'Thumbs.db' && $file != '.DS_Store' && substr($file, -1) != '~') {
                    copy($src.'/'.$file, $dst.'/'.$file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Generate a cached thumbnail for object lists (eg. carrier, order statuses...etc)
     *
     * @param string $image        Real image filename
     * @param string $cacheImage   Cached filename
     * @param int    $size         Desired size
     * @param string $imageType    Image type
     * @param bool   $disableCache When turned on a timestamp will be added to the image URI to disable the HTTP cache
     * @param bool   $regenerate   When turned on and the file already exist, the file will be regenerated
     *
     * @return string
     *
     * @since   1.0.4
     */
    protected function thumbnail($image, $cacheImage, $size, $imageType = 'jpg', $disableCache = true, $regenerate = false)
    {
        if (!file_exists($image)) {
            return '';
        }

        if (file_exists(_PS_TMP_IMG_DIR_.$cacheImage) && $regenerate) {
            @unlink(_PS_TMP_IMG_DIR_.$cacheImage);
        }

        if ($regenerate || !file_exists(_PS_TMP_IMG_DIR_.$cacheImage)) {
            $infos = getimagesize($image);

            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!ImageManager::checkImageMemoryLimit($image)) {
                return false;
            }

            $x = $infos[0];
            $y = $infos[1];
            $maxX = $size * 3;

            // Size is already ok
            if ($y < $size && $x <= $maxX) {
                copy($image, _PS_TMP_IMG_DIR_.$cacheImage);
            } // We need to resize */
            else {
                $ratio_x = $x / ($y / $size);
                if ($ratio_x > $maxX) {
                    $ratio_x = $maxX;
                    $size = $y / ($x / $maxX);
                }

                ImageManager::resize($image, _PS_TMP_IMG_DIR_.$cacheImage, $ratio_x, $size, $imageType);
            }
        }
        // Relative link will always work, whatever the base uri set in the admin
        if (Context::getContext()->controller->controller_type == 'admin') {
            return '../img/tmp/'.$cacheImage.($disableCache ? '?time='.time() : '');
        } else {
            return _PS_TMP_IMG_.$cacheImage.($disableCache ? '?time='.time() : '');
        }
    }
}
