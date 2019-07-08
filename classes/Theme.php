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
 * Class ThemeCore
 *
 * @since 1.0.0
 */
class ThemeCore extends ObjectModel
{
    const CACHE_FILE_CUSTOMER_THEMES_LIST = '/config/xml/customer_themes_list.xml';
    const CACHE_FILE_MUST_HAVE_THEMES_LIST = '/config/xml/must_have_themes_list.xml';
    const UPLOADED_THEME_DIR_NAME = 'uploaded';

    // @codingStandardsIgnoreStart
    /** @var int access rights of created folders (octal) */
    public static $access_rights = 0775;
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'theme',
        'primary' => 'id_theme',
        'fields'  => [
            'name'                 => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64, 'required' => true],
            'directory'            => ['type' => self::TYPE_STRING, 'validate' => 'isDirName', 'size' => 64, 'required' => true],
            'responsive'           => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'default_left_column'  => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'default_right_column' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'product_per_page'     => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
        ],
    ];
    /** @var string $name */
    public $name;
    /** @var string $directory */
    public $directory;
    /** @var bool $responsive */
    public $responsive;
    /** @var int $default_left_column */
    public $default_left_column;
    /** @var int $default_right_column */
    public $default_right_column;
    /** @var int $product_per_page */
    public $product_per_page;
    // @codingStandardsIgnoreEnd

    /**
     * @param bool $excludedIds
     *
     * @return PrestaShopCollection
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getAllThemes($excludedIds = false)
    {
        $themes = new PrestaShopCollection('Theme');

        if (is_array($excludedIds) && !empty($excludedIds)) {
            $themes->where('id_theme', 'notin', $excludedIds);
        }

        $themes->orderBy('name');

        return $themes;
    }

    /**
     * return an array of all available theme (installed or not)
     *
     * @param bool $installedOnly
     *
     * @return array string (directory)
     * @throws PrestaShopException
     */
    public static function getAvailable($installedOnly = true)
    {
        static $dirlist = [];
        $availableTheme = [];

        if (empty($dirlist)) {
            $themes = scandir(_PS_ALL_THEMES_DIR_);
            foreach ($themes as $theme) {
                if (is_dir(_PS_ALL_THEMES_DIR_.DIRECTORY_SEPARATOR.$theme) && $theme[0] != '.') {
                    $dirlist[] = $theme;
                }
            }
        }

        $themesDir = [];
        if ($installedOnly) {
            $themes = Theme::getThemes();
            foreach ($themes as $themeObj) {
                /** @var Theme $themeObj */
                $themesDir[] = $themeObj->directory;
            }

            foreach ($dirlist as $theme) {
                if (false !== array_search($theme, $themesDir)) {
                    $availableTheme[] = $theme;
                }
            }
        } else {
            $availableTheme = $dirlist;
        }

        return $availableTheme;
    }

    /**
     * @return PrestaShopCollection
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getThemes()
    {
        $themes = new PrestaShopCollection('Theme');
        $themes->orderBy('name');

        return $themes;
    }

    /**
     * Find a theme by name.
     *
     * @param string $directory
     *
     * @return bool|Theme Theme instance on success, false if theme not found.
     *
     * @version 1.1.0 Initial version.
     */
    public static function getByName($name)
    {
        $idTheme = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
            ->select('`id_theme`')
            ->from('theme')
            ->where('`name` = \''.pSQL($name).'\'')
        );

        if ($idTheme) {
            return new Theme($idTheme);
        }

        return false;
    }

    /**
     * Checks if theme exists (by folder) and returns Theme object.
     *
     * @param string $directory
     *
     * @return bool|Theme
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getByDirectory($directory)
    {
        if (is_string($directory) && strlen($directory) > 0 && file_exists(_PS_ALL_THEMES_DIR_.$directory) && is_dir(_PS_ALL_THEMES_DIR_.$directory)) {
            $idTheme = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_theme`')
                    ->from('theme')
                    ->where('`directory` = \''.pSQL($directory).'\'')
            );

            return $idTheme ? new Theme($idTheme) : false;
        }

        return false;
    }

    /**
     * @param int $idTheme
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getThemeInfo($idTheme)
    {
        $theme = new Theme((int) $idTheme);
        $themeArr = [];

        $xmlTheme = $theme->loadConfigFile();
        if ($xmlTheme) {
            $themeArr['theme_id'] = (int) $theme->id;

            foreach ($xmlTheme->attributes() as $key => $value) {
                $themeArr['theme_'.$key] = (string) $value;
            }

            foreach ($xmlTheme->author->attributes() as $key => $value) {
                $themeArr['author_'.$key] = (string) $value;
            }

            if ($themeArr['theme_name'] == 'community-theme-default') {
                $themeArr['tc'] = Module::isEnabled('themeconfigurator');
            }
        } else {
            // If no xml we use data from database
            $themeArr['theme_id'] = (int) $theme->id;
            $themeArr['theme_name'] = $theme->name;
            $themeArr['theme_directory'] = $theme->directory;
        }

        return $themeArr;
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNonInstalledTheme()
    {
        $installedThemeDirectories = Theme::getInstalledThemeDirectories();
        $notInstalledTheme = [];
        foreach (scandir(_PS_ALL_THEMES_DIR_) as $dir) {
            if (is_dir(_PS_ALL_THEMES_DIR_.$dir)
                && ! in_array($dir, ['.', '..'])
                && ! in_array($dir, $installedThemeDirectories)) {
                $xmlTheme = static::loadDefaultConfig(_PS_ALL_THEMES_DIR_.$dir);
                if ($xmlTheme) {
                    $theme = [];
                    foreach ($xmlTheme->attributes() as $key => $value) {
                        $theme[$key] = (string) $value;
                    }

                    if (!empty($theme)) {
                        $notInstalledTheme[] = $theme;
                    }
                }
            }
        }

        return $notInstalledTheme;
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getInstalledThemeDirectories()
    {
        $list = [];
        $tmp = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`directory`')
                ->from('theme')
        );
        foreach ($tmp as $t) {
            $list[] = $t['directory'];
        }

        return $list;
    }

    /**
     * check if a theme is used by a shop
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function isUsed()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('shop')
                ->where('`id_theme` = '.(int) $this->id)
        );
    }

    /**
     * add only theme if the directory exists
     *
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool Insertion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (!is_dir(_PS_ALL_THEMES_DIR_.$this->directory)) {
            return false;
        }

        return parent::add($autoDate, $nullValues);
    }

    /**
     * update the table PREFIX_theme_meta for the current theme
     *
     * @param array $metas
     * @param bool  $fullUpdate If true, all the meta of the theme will be deleted prior the insert, otherwise only the current $metas will be deleted
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updateMetas($metas, $fullUpdate = false)
    {
        if ($fullUpdate) {
            Db::getInstance()->delete('theme_meta', 'id_theme='.(int) $this->id);
        }

        $values = [];
        if ($this->id > 0) {
            foreach ($metas as $meta) {
                if (!$fullUpdate) {
                    Db::getInstance()->delete('theme_meta', 'id_theme='.(int) $this->id.' AND id_meta='.(int) $meta['id_meta']);
                }

                $values[] = [
                    'id_theme'     => (int) $this->id,
                    'id_meta'      => (int) $meta['id_meta'],
                    'left_column'  => (int) $meta['left'],
                    'right_column' => (int) $meta['right'],
                ];
            }
            Db::getInstance()->insert('theme_meta', $values);
        }
    }

    /**
     * @param string $page
     *
     * @return array|bool|null|object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function hasColumns($page)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('IFNULL(`left_column`, `default_left_column`) AS `left_column`, IFNULL(`right_column`, `default_right_column`) AS `right_column`')
                ->from('theme', 't')
                ->leftJoin('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')
                ->leftJoin('meta', 'm', 'm.`id_meta` = tm.`id_meta`')
                ->where('t.`id_theme` = '.(int) $this->id)
                ->where('m.`page` = \''.pSQL($page).'\'')
        );
    }

    /**
     * @param string $page
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function hasColumnsSettings($page)
    {
        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('m.`id_meta`')
                ->from('theme', 't')
                ->leftJoin('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')
                ->leftJoin('meta', 'm', 'm.`id_meta` = tm.`id_meta`')
                ->where('t.`id_theme` = '.(int) $this->id)
                ->where('m.`page` = \''.pSQL($page).'\'')
        );
    }

    /**
     * @param null $page
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function hasLeftColumn($page = null)
    {
        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('IFNULL(`left_column`, `default_left_column`)')
                ->from('theme', 't')
                ->leftJoin('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')
                ->leftJoin('meta', 'm', 'm.`id_meta` = tm.`id_meta`')
                ->where('t.`id_theme` = '.(int) $this->id)
                ->where('m.`page` = \''.pSQL($page).'\'')
        );
    }

    /**
     * @param null $page
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function hasRightColumn($page = null)
    {
        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('IFNULL(`right_column`, `default_right_column`)')
                ->from('theme', 't')
                ->leftJoin('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')
                ->leftJoin('meta', 'm', 'm.`id_meta` = tm.`id_meta`')
                ->where('t.`id_theme` = '.(int) $this->id)
                ->where('m.`page` = \''.pSQL($page).'\'')
        );
    }

    /**
     * @return array|bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getMetas()
    {
        if (!Validate::isUnsignedId($this->id) || $this->id == 0) {
            return false;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('theme_meta')
                ->where('`id_theme` = '.(int) $this->id)
        );
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public function removeMetas()
    {
        if (!Validate::isUnsignedId($this->id) || $this->id == 0) {
            return false;
        }

        return Db::getInstance()->delete('theme_meta', 'id_theme = '.(int) $this->id);
    }

    /**
     * @return bool
     * @throws PrestaShopException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function toggleResponsive()
    {
        // Object must have a variable called 'responsive'
        if (!method_exists($this, 'responsive')) {
            throw new PrestaShopException('property "responsive" is missing in object '.get_class($this));
        }

        // Update only responsive field
        $this->setFieldsToUpdate(['responsive' => true]);

        // Update active responsive on object
        $this->responsive = !(int) $this->responsive;

        // Change responsive to active/inactive
        return $this->update(false);
    }

    /**
     * @return bool
     * @throws PrestaShopException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function toggleDefaultLeftColumn()
    {
        if (!method_exists($this, 'default_left_column')) {
            throw new PrestaShopException('property "default_left_column" is missing in object '.get_class($this));
        }

        $this->setFieldsToUpdate(['default_left_column' => true]);

        $this->default_left_column = !(int) $this->default_left_column;

        return $this->update(false);
    }

    /**
     * @return bool
     * @throws PrestaShopException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function toggleDefaultRightColumn()
    {
        if (!method_exists($this, 'default_right_column')) {
            throw new PrestaShopException('property "default_right_column" is missing in object '.get_class($this));
        }

        $this->setFieldsToUpdate(['default_right_column' => true]);

        $this->default_right_column = !(int) $this->default_right_column;

        return $this->update(false);
    }

    /**
     * Get the configuration file as an array
     *
     * @return array
     *
     * @since 1.0.4
     */
    public function getConfiguration()
    {
        $ob = $this->loadConfigFile();
        if ($ob) {
            // convert SimpleXMLElement to array
            return json_decode(json_encode($ob), true);
        }

        return [];
    }

    /**
     * Install a theme with just the directory given.
     *
     * Note that files of the theme might be located not yet in
     * _PS_ALL_THEMES_DIR_ (themes/). If not, the theme gets moved into the
     * right place.
     *
     * @param string $themeDir Theme directory inside _PS_ALL_THEMES_DIR_.
     *
     * @return Theme|string Error message or Theme instance on success.
     *
     * @version 1.1.0 Initial version.
     */
    public static function installFromDir($themeDir)
    {
        $xml = static::loadDefaultConfig($themeDir);
        if ( ! $xml) {
            return sprintf(Tools::displayError(
                'Bad or missing config.xml in theme in %s.'),
                $themeDir
            );
        }
        $xmlAttributes = $xml->attributes();

        if (static::getByName((string) $xmlAttributes['name']) !== false) {
            return sprintf(Tools::displayError(
                'A theme with the same name as the theme in %s is already installed.'),
                $themeDir
            );
        }

        $theme = new Theme();
        $theme->name = (string) $xmlAttributes['name'];
        $theme->directory = (string) $xmlAttributes['directory'];

        // These are defaults, likely overwritten by the variation.
        $theme->product_per_page = Configuration::get('PS_PRODUCTS_PER_PAGE');
        $theme->responsive = false;
        $theme->default_left_column = true;
        $theme->default_right_column = true;

        /**
         * This is an intentional deviation from PrestaShop: only the first
         * variation gets installed, 'name' and 'directory' of the variation
         * gets ignored. Having theme distributions with multiple variations is
         * considered to be overengineering.
         */
        if (isset($xml->variations)) {
            if (count($xml->variations) > 1) {
                return sprintf(Tools::displayError(
                    'thirty bees supports only themes with at most one variation, the theme in %s has multiple ones.'),
                    $themeDir
                );
            }

            $variation = $xml->variations->variation[0];

            if (isset($variation['product_per_page'])) {
                $theme->product_per_page = (int) $variation['product_per_page'];
            }
            if (isset($variation['responsive'])) {
                $theme->responsive = (bool) (string) $variation['responsive'];
            }
            if (isset($variation['default_left_column'])) {
                $theme->default_left_column =
                    (bool) (string) $variation['default_left_column'];
            }
            if (isset($variation['default_right_column'])) {
                $theme->default_right_column =
                    (bool) (string) $variation['default_right_column'];
            }
        }

        $theme->add();
        if ( ! Validate::isLoadedObject($theme)) {
            return sprintf(Tools::displayError(
                'Error while installing theme in %s'),
                $themeDir
            );
        }

        return $theme;
    }

    /**
     * Install this theme in the current shop context.
     *
     * @return array Array with the following entries:
     *               'imageTypes':   List of image types updated or created for
     *                               this theme. Each item an array with 'name',
     *                               'width' and 'height'.
     *               'moduleErrors': List of module installation errors. Each
     *                               item with 'module_name' and an 'errors',
     *                               an array with error messages.
     *               'documents':    Array with documentation links.
     *
     * @version 1.1.0 Excerpt from AdminThemesController->processInstallTheme().
     */
    public function installIntoShopContext()
    {
        $return = [
            'imageTypes'    => [],
            'moduleErrors'  => [],
            'documents'     => [],
        ];

        $xml = $this->loadConfigFile();
        if ($xml) {
            /**
             * Create/update image types.
             */
            if (isset($xml->images->image)) {
                foreach ($xml->images->image as $imageType) {
                    // It's installation time, name variants can get ignored.
                    $type = ImageType::getInstanceByName(
                        (string) $imageType['name'],
                        $this->name
                    );

                    $type->width = (int) $imageType['width'];
                    $type->height = (int) $imageType['height'];
                    foreach ($type->def['fields'] as $name => $field) {
                        if ($field['type'] == ObjectModel::TYPE_BOOL) {
                            $type->{$name} = $imageType[$name] == 'true';
                        }
                    }

                    $type->save();

                    $return['imageTypes'][] = [
                        'name'   => $type->name,
                        'width'  => $type->width,
                        'height' => $type->height,
                    ];
                }
            }

            /**
             * Install/enable/disable theme related modules. All Module methods
             * used work on the current shop context.
             */
            $unrelatedModules = Module::getNotThemeRelatedModules();
            foreach ($xml->modules->module as $moduleRow) {
                $moduleName = (string) $moduleRow['name'];
                if (in_array($moduleName, $unrelatedModules)) {
                    continue;
                }

                $module = Module::getInstanceByName($moduleName);
                if ( ! $module) {
                    continue;
                }

                $isInstalledSuccess = true;
                if ((string) $moduleRow['action'] === 'install'
                    || (string) $moduleRow['action'] === 'enable') {
                    if ( ! Module::isInstalled($moduleName)) {
                        $isInstalledSuccess = $module->install();
                    }
                    if ( ! $isInstalledSuccess) {
                        $return['moduleErrors'][] = [
                            'module_name' => $moduleName,
                            'errors'      => $module->getErrors(),
                        ];
                    }
                }

                if ($isInstalledSuccess
                    && (string) $moduleRow['action'] === 'enable'
                ) {
                    $module->enable();

                    /**
                     * Replace the modules' default hooks and hook
                     * exceptions with those of the theme configuration.
                     */
                    $allHooks = $module->getPossibleHooksList();
                    foreach ($allHooks as $hook) {
                        $module->unregisterExceptions($hook['id_hook']);
                        $module->unregisterHook($hook['id_hook']);
                    }

                    foreach ($xml->modules->hooks->hook as $hook) {
                        if ((string) $hook['module'] === $moduleName) {
                            $idHook = Hook::getIdByName((string) $hook['hook']);

                            $module->registerHook((string) $hook['hook']);
                            if (isset($hook['position'])) {
                                $module->updatePosition(
                                    $idHook,
                                    false,
                                    (int) $hook['position']
                                );
                            }

                            if (isset($hook['exceptions'])) {
                                $module->registerExceptions(
                                    $idHook,
                                    explode(',', (string) $hook['exceptions'])
                                );
                            }
                        }
                    }
                }

                if ((string) $moduleRow['action'] === 'disable') {
                    $module->disable();
                }
            }

            /**
             * Create/update theme metas.
             */
            $metasXml = [];

            // Collect defined metas.
            if ($xml->metas->meta) {
                foreach ($xml->metas->meta as $meta) {
                    $metaId = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('`id_meta`')
                            ->from('meta')
                            ->where('`page` = \''.pSQL($meta['meta_page']).'\'')
                    );
                    $metaId = (int) $metaId;
                    if ($metaId) {
                        $metasXml[$metaId] = [
                            'id_meta' => $metaId,
                            'left'    => (bool) (int) $meta['left'],
                            'right'   => (bool) (int) $meta['right'],
                        ];
                    }
                }
            }

            // Fill all other metas with default values.
            foreach (Meta::getMetas() as $meta) {
                $metaId = (int) $meta['id_meta'];
                if ( ! array_key_exists($metaId, $metasXml)) {
                    $metasXml[$metaId] = [
                        'id_meta' => $metaId,
                        'left'    => $this->default_left_column,
                        'right'   => $this->default_right_column,
                    ];
                }
            }

            $this->updateMetas($metasXml, true);

            /**
             * Install the theme into all shops of the current context.
             */
            $shops = Shop::getContextListShopID();
            foreach ($shops as $idShop) {
                $shop = new Shop((int) $idShop);
                $shop->id_theme = $this->id;
                $shop->save();

                if (Shop::isFeatureActive()) {
                    Configuration::updateValue('PS_PRODUCTS_PER_PAGE', (int) $this->product_per_page, false, null, (int) $idShop);
                } else {
                    Configuration::updateValue('PS_PRODUCTS_PER_PAGE', (int) $this->product_per_page);
                }
            }
            $context = Context::getContext();
            $context->shop->id_theme = $this->id;
            $context->shop->update();

            /**
             * Create documentation link.
             */
            foreach ($xml->docs->doc as $row) {
                $return['documents'][(string) $row['name']] =
                    preg_replace(
                        '#^'._PS_ROOT_DIR_.'#',
                        __PS_BASE_URI__,
                        _PS_ALL_THEMES_DIR_
                    ).$this->directory.'/'.$row['path'];
            }
        } else {
            // Invalid themes shouldn't get offered for installation.
            throw new PrestaShopException('Attempt to install theme '.$this->name.' despite its invalid config.xml.');
        }

        return $return;
    }

    /**
     * Get the configuration file as SimpleXMLElement
     *
     * @param boolean $validate - if true, configuration file will be validated
     *
     * @return SimpleXMLElement | false
     *
     * @version 1.0.7 Initial version.
     * @version 1.1.0 Abandon XMLs in config/xml/themes/ in favor of config.xml
     *                inside the theme directory.
     */
    public function loadConfigFile($validate = false)
    {
        $this->collectConfigFilesForRetrocompatibility();

        $xml = static::loadDefaultConfig(_PS_ALL_THEMES_DIR_.$this->directory);
        if ( ! $xml || (string) $xml->attributes()->name !== $this->name) {
            return false;
        }

        return $xml;
    }

    /**
     * Retrocompatibility with < 1.1.0: collect old configuration files.
     *
     * Where did these files come from?
     *
     *  - PrestaShop didn't have them in the themes folder, but installed
     *    them in config/xml/themes/. It was a copy of Config.xml in the top
     *    level of a theme package, outside of the actual theme.
     *
     *  - thirty bees < 1.1.0 put a copy of the config.xml in the theme
     *    folder there.
     *
     * TODO: move this into Core Updater.
     *
     * @version 1.1.0 Initial version.
     */
    private function collectConfigFilesForRetrocompatibility()
    {
        $oldConfigFound = false;
        $oldConfigs = [
            _PS_CONFIG_DIR_.'xml/themes/'.$this->directory.'.xml',
            _PS_CONFIG_DIR_.'xml/themes/'.$this->name.'.xml',
        ];
        if ($this->name === 'community-theme-default') {
            $oldConfigs[] = _PS_CONFIG_DIR_.'xml/themes/default.xml';
        }
        foreach ($oldConfigs as $oldConfig) {
            if ($oldConfigFound) {
                @unlink($oldConfig);
            } elseif (file_exists($oldConfig)) {
                rename(
                    $oldConfig,
                    _PS_ALL_THEMES_DIR_.$this->directory.'/config.xml'
                );
                $oldConfigFound = true;
            }
        }
    }

    /**
     * Return full path of theme's configuration file.
     *
     * @return string|bool Path of the config file or false if none found.
     *
     * @version 1.0.0 Initial version.
     * @version 1.1.0 Abandon XMLs in config/xml/themes/ in favor of config.xml
     *                inside the theme directory.
     * @deprecated 1.1.0 Use loadConfigFile() or loadDefaultConfig() directly.
     */
    public function getConfigFilePath()
    {
        $this->collectConfigFilesForRetrocompatibility();

        return _PS_ALL_THEMES_DIR_.$this->directory.'/config.xml';
    }

    /**
     * Get the configuration file as SimpleXMLElement
     *
     * @param string $filePath - path to xml config file to load
     * @param boolean $validate - if true, configuration file will be validated
     *
     * @return SimpleXMLElement | false
     *
     * @version 1.0.7 Initial version.
     */
    public static function loadConfigFromFile($filePath, $validate)
    {
        if (file_exists($filePath)) {
            $content = @simplexml_load_file($filePath);
            if ($content && $validate && !static::validateConfigFile($content)) {
                return false;
            }

            return $content;
        }

        return false;
    }

    /**
     * Validate xml fields in config file
     *
     * @param SimpleXMLElement $xml
     *
     * @return boolean
     *
     * @since 1.0.7
     */
    public static function validateConfigFile($xml)
    {
        if (! $xml) {
            return false;
        }
        if (!$xml['version'] || !$xml['name']) {
            return false;
        }
        foreach ($xml->variations->variation as $val) {
            if (!$val['name'] || !$val['directory'] || !$val['from']) {
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
     * Get the default configuration file of a theme as SimpleXMLElement. This
     * works for installed themes and for theme packages before import.
     *
     *
     * @param string $themePath Directory of the theme. For installed themes
     *                          that's _PS_ALL_THEMES_DIR_.$theme->directory.
     *
     * @return SimpleXMLElement | false
     *
     * @version 1.1.0 Initial version.
     */
    public static function loadDefaultConfig($themePath)
    {
        $themePath = rtrim($themePath, '/');

        $path = $themePath.'/config.xml'; // Preferred name: all lowercase.
        if ( ! file_exists($path)) {
            // Try to find differently cased variants.
            foreach (scandir($themePath) as $variant) {
                if (strcasecmp($variant, 'config.xml') === 0) {
                    $path = $themePath.'/'.$variant;
                    break;
                }
            }
        }

        return static::loadConfigFromFile($path, true);
    }
}
