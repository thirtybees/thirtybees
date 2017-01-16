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
 * Class ThemeCore
 *
 * @since 1.0.0
 */
class ThemeCore extends ObjectModel
{
    const CACHE_FILE_CUSTOMER_THEMES_LIST = '/config/xml/customer_themes_list.xml';
    const CACHE_FILE_MUST_HAVE_THEMES_LIST = '/config/xml/must_have_themes_list.xml';
    const UPLOADED_THEME_DIR_NAME = 'uploaded';
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
    public $name;
    public $directory;
    public $responsive;
    public $default_left_column;
    public $default_right_column;
    public $product_per_page;

    /**
     * @param bool $excludedIds
     *
     * @return PrestaShopCollection
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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

        if ($installedOnly) {
            $themes = Theme::getThemes();
            foreach ($themes as $themeObj) {
                /** @var Theme $themeObj */
                $themes_dir[] = $themeObj->directory;
            }

            foreach ($dirlist as $theme) {
                if (false !== array_search($theme, $themes_dir)) {
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
     */
    public static function getThemes()
    {
        $themes = new PrestaShopCollection('Theme');
        $themes->orderBy('name');

        return $themes;
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
     */
    public static function getByDirectory($directory)
    {
        if (is_string($directory) && strlen($directory) > 0 && file_exists(_PS_ALL_THEMES_DIR_.$directory) && is_dir(_PS_ALL_THEMES_DIR_.$directory)) {
            $idTheme = (int) Db::getInstance()->getValue('SELECT id_theme FROM '._DB_PREFIX_.'theme WHERE directory="'.pSQL($directory).'"');

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
     */
    public static function getThemeInfo($idTheme)
    {
        $theme = new Theme((int) $idTheme);
        $themeArr = [];

        if (file_exists(_PS_ROOT_DIR_.'/config/xml/themes/'.$theme->directory.'.xml')) {
            $configFile = _PS_ROOT_DIR_.'/config/xml/themes/'.$theme->directory.'.xml';
        } elseif ($theme->name == 'community-theme-default') {
            $configFile = _PS_ROOT_DIR_.'/config/xml/themes/default.xml';
        } else {
            $configFile = false;
        }

        if ($configFile) {
            $themeArr['theme_id'] = (int) $theme->id;
            $xmlTheme = @simplexml_load_file($configFile);

            if ($xmlTheme !== false) {
                foreach ($xmlTheme->attributes() as $key => $value) {
                    $themeArr['theme_'.$key] = (string) $value;
                }

                foreach ($xmlTheme->author->attributes() as $key => $value) {
                    $themeArr['author_'.$key] = (string) $value;
                }

                if ($themeArr['theme_name'] == 'community-theme-default') {
                    $themeArr['tc'] = Module::isEnabled('themeconfigurator');
                }
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNonInstalledTheme()
    {
        $installedThemeDirectories = Theme::getInstalledThemeDirectories();
        $notInstalledTheme = [];
        foreach (glob(_PS_ALL_THEMES_DIR_.'*', GLOB_ONLYDIR) as $themeDir) {
            $dir = basename($themeDir);
            $configFile = _PS_ALL_THEMES_DIR_.$dir.'/config.xml';
            if (!in_array($dir, $installedThemeDirectories) && @filemtime($configFile)) {
                if ($xmlTheme = @simplexml_load_file($configFile)) {
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getInstalledThemeDirectories()
    {
        $list = [];
        $tmp = Db::getInstance()->executeS('SELECT `directory` FROM '._DB_PREFIX_.'theme');
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
     */
    public function isUsed()
    {
        return Db::getInstance()->getValue(
            'SELECT count(*)
			FROM '._DB_PREFIX_.'shop WHERE id_theme = '.(int) $this->id
        );
    }

    /**
     * add only theme if the directory exists
     *
     * @param bool $nullValues
     * @param bool $autodate
     *
     * @return bool Insertion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autodate = true, $nullValues = false)
    {
        if (!is_dir(_PS_ALL_THEMES_DIR_.$this->directory)) {
            return false;
        }

        return parent::add($autodate, $nullValues);
    }

    /**
     * update the table PREFIX_theme_meta for the current theme
     *
     * @param array $metas
     * @param bool  $fullUpdate If true, all the meta of the theme will be deleted prior the insert, otherwise only the current $metas will be deleted
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param $page
     *
     * @return array|bool|null|object
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function hasColumns($page)
    {
        return Db::getInstance()->getRow(
            '
		SELECT IFNULL(left_column, default_left_column) AS left_column, IFNULL(right_column, default_right_column) AS right_column
		FROM '._DB_PREFIX_.'theme t
		LEFT JOIN '._DB_PREFIX_.'theme_meta tm ON (t.id_theme = tm.id_theme)
		LEFT JOIN '._DB_PREFIX_.'meta m ON (m.id_meta = tm.id_meta)
		WHERE t.id_theme ='.(int) $this->id.' AND m.page = "'.pSQL($page).'"'
        );
    }

    /**
     * @param $page
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function hasColumnsSettings($page)
    {
        return (bool) Db::getInstance()->getValue(
            '
		SELECT m.`id_meta`
		FROM '._DB_PREFIX_.'theme t
		LEFT JOIN '._DB_PREFIX_.'theme_meta tm ON (t.id_theme = tm.id_theme)
		LEFT JOIN '._DB_PREFIX_.'meta m ON (m.id_meta = tm.id_meta)
		WHERE t.id_theme ='.(int) $this->id.' AND m.page = "'.pSQL($page).'"'
        );
    }

    /**
     * @param null $page
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function hasLeftColumn($page = null)
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT IFNULL(
			(
				SELECT left_column
				FROM '._DB_PREFIX_.'theme t
				LEFT JOIN '._DB_PREFIX_.'theme_meta tm ON ( t.id_theme = tm.id_theme )
				LEFT JOIN '._DB_PREFIX_.'meta m ON ( m.id_meta = tm.id_meta )
				WHERE t.id_theme ='.(int) $this->id.'
				AND m.page = "'.pSQL($page).'" ) , default_left_column
			)
			FROM '._DB_PREFIX_.'theme
			WHERE id_theme ='.(int) $this->id
        );
    }

    /**
     * @param null $page
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function hasRightColumn($page = null)
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT IFNULL(
			(
				SELECT right_column
				FROM '._DB_PREFIX_.'theme t
				LEFT JOIN '._DB_PREFIX_.'theme_meta tm ON ( t.id_theme = tm.id_theme )
				LEFT JOIN '._DB_PREFIX_.'meta m ON ( m.id_meta = tm.id_meta )
				WHERE t.id_theme ='.(int) $this->id.'
				AND m.page = "'.pSQL($page).'" ) , default_right_column
			)
			FROM '._DB_PREFIX_.'theme
			WHERE id_theme ='.(int) $this->id
        );
    }

    /**
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getMetas()
    {
        if (!Validate::isUnsignedId($this->id) || $this->id == 0) {
            return false;
        }

        return Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'theme_meta WHERE id_theme = '.(int) $this->id);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
        if (!array_key_exists('responsive', $this)) {
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
        if (!array_key_exists('default_left_column', $this)) {
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
        if (!array_key_exists('default_right_column', $this)) {
            throw new PrestaShopException('property "default_right_column" is missing in object '.get_class($this));
        }

        $this->setFieldsToUpdate(['default_right_column' => true]);

        $this->default_right_column = !(int) $this->default_right_column;

        return $this->update(false);
    }
}
