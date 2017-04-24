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
 * Class TabCore
 *
 * @since 1.0.0
 */
class TabCore extends ObjectModel
{
    const TAB_MODULE_LIST_URL = _PS_TAB_MODULE_LIST_URL_;

    // @codingStandardsIgnoreStart
    protected static $_getIdFromClassName = null;
    /**
     * Get tabs
     *
     * @return array tabs
     */
    protected static $_cache_tabs = [];
    /**
     * Displayed name
     *
     * Multilang property
     *
     * @var array
     */
    public $name;
    /** @var string Class and file name */
    public $class_name;
    public $module;
    /** @var int parent ID */
    public $id_parent;
    /** @var int position */
    public $position;
    /** @var bool active */
    public $active = true;
    /** @var int hide_host_mode */
    public $hide_host_mode = false;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'tab',
        'primary'   => 'id_tab',
        'multilang' => true,
        'fields'    => [
            'id_parent'      => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'position'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'module'         => ['type' => self::TYPE_STRING, 'validate' => 'isTabName', 'size' => 64],
            'class_name'     => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 64],
            'active'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'hide_host_mode' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            /* Lang fields */
            'name'           => ['type' => self::TYPE_STRING, 'lang' => true, 'required' => true, 'validate' => 'isTabName', 'size' => 64],
        ],
    ];

    /**
     * Get tab id
     *
     * @return int tab id
     */
    public static function getCurrentTabId()
    {
        $idTab = Tab::getIdFromClassName(Tools::getValue('controller'));
        // retro-compatibility 1.4/1.5
        if (empty($idTab)) {
            $idTab = Tab::getIdFromClassName(Tools::getValue('tab'));
        }

        return $idTab;
    }

    /**
     * Get tab id from name
     *
     * @param string $className
     *
     * @return int id_tab
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIdFromClassName($className)
    {
        $className = strtolower($className);
        if (static::$_getIdFromClassName === null) {
            static::$_getIdFromClassName = [];
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT id_tab, class_name FROM `'._DB_PREFIX_.'tab`', true, false);

            if (is_array($result)) {
                foreach ($result as $row) {
                    static::$_getIdFromClassName[strtolower($row['class_name'])] = $row['id_tab'];
                }
            }
        }

        return (isset(static::$_getIdFromClassName[$className]) ? (int) static::$_getIdFromClassName[$className] : false);
    }

    /**
     * Get tab parent id
     *
     * @return int tab parent id
     */
    public static function getCurrentParentId()
    {
        $cacheId = 'getCurrentParentId_'.Tools::strtolower(Tools::getValue('controller'));
        if (!Cache::isStored($cacheId)) {
            $value = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                '
			SELECT `id_parent`
			FROM `'._DB_PREFIX_.'tab`
			WHERE LOWER(class_name) = \''.pSQL(Tools::strtolower(Tools::getValue('controller'))).'\''
            );
            if (!$value) {
                $value = -1;
            }
            Cache::store($cacheId, $value);

            return $value;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Return the list of tab used by a module
     *
     * @return array
     */
    public static function getModuleTabList()
    {
        $list = [];

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT t.`class_name`, t.`module`
			FROM `'._DB_PREFIX_.'tab` t
			WHERE t.`module` IS NOT NULL AND t.`module` != ""'
        );

        if (is_array($result)) {
            foreach ($result as $detail) {
                $list[strtolower($detail['class_name'])] = $detail;
            }
        }

        return $list;
    }

    /**
     * @param int|     $idLang
     * @param int|null $idParent
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTabs($idLang, $idParent = null)
    {
        if (!isset(static::$_cache_tabs[$idLang])) {
            static::$_cache_tabs[$idLang] = [];
            // Keep t.*, tl.name instead of only * because if translations are missing, the join on tab_lang will overwrite the id_tab in the results
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
				SELECT t.*, tl.name
				FROM `'._DB_PREFIX_.'tab` t
				LEFT JOIN `'._DB_PREFIX_.'tab_lang` tl ON (t.`id_tab` = tl.`id_tab` AND tl.`id_lang` = '.(int) $idLang.')
				WHERE 1 '.(defined('_PS_HOST_MODE_') ? ' AND `hide_host_mode` = 0' : '').'
				ORDER BY t.`position` ASC'
            );

            if (is_array($result)) {
                foreach ($result as $row) {
                    if (!isset(static::$_cache_tabs[$idLang][$row['id_parent']])) {
                        static::$_cache_tabs[$idLang][$row['id_parent']] = [];
                    }
                    static::$_cache_tabs[$idLang][$row['id_parent']][] = $row;
                }
            }
        }
        if ($idParent === null) {
            $arrayAll = [];
            foreach (static::$_cache_tabs[$idLang] as $arrayParent) {
                $arrayAll = array_merge($arrayAll, $arrayParent);
            }

            return $arrayAll;
        }

        return (isset(static::$_cache_tabs[$idLang][$idParent]) ? static::$_cache_tabs[$idLang][$idParent] : []);
    }

    /**
     * Enabling tabs for module
     *
     * @param $module string Module Name
     *
     * @return bool Status
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function enablingForModule($module)
    {
        $tabs = Tab::getCollectionFromModule($module);
        if (!empty($tabs)) {
            foreach ($tabs as $tab) {
                $tab->active = 1;
                $tab->save();
            }

            return true;
        }

        return false;
    }

    /**
     * Get collection from module name
     *
     * @param      $module  string Module name
     * @param null $idLang  integer Language ID
     *
     * @return array|PrestaShopCollection Collection of tabs (or empty array)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCollectionFromModule($module, $idLang = null)
    {
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        if (!Validate::isModuleName($module)) {
            return [];
        }

        $tabs = new PrestaShopCollection('Tab', (int) $idLang);
        $tabs->where('module', '=', $module);

        return $tabs;
    }

    /**
     * Disabling tabs for module
     *
     * @param $module string Module name
     *
     * @return bool Status
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function disablingForModule($module)
    {
        $tabs = Tab::getCollectionFromModule($module);
        if (!empty($tabs)) {
            foreach ($tabs as $tab) {
                $tab->active = 0;
                $tab->save();
            }

            return true;
        }

        return false;
    }

    /**
     * Get Instance from tab class name
     *
     * @param $className string Name of tab class
     * @param $idLang    integer id_lang
     *
     * @return Tab Tab object (empty if bad id or class name)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getInstanceFromClassName($className, $idLang = null)
    {
        $id_tab = (int) Tab::getIdFromClassName($className);

        return new Tab($id_tab, $idLang);
    }

    /**
     * @param $idTab
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function checkTabRights($idTab)
    {
        static $tabAccesses = null;

        if (Context::getContext()->employee->id_profile == _PS_ADMIN_PROFILE_) {
            return true;
        }

        if ($tabAccesses === null) {
            $tabAccesses = Profile::getProfileAccesses(Context::getContext()->employee->id_profile);
        }

        if (isset($tabAccesses[(int) $idTab]['view'])) {
            return ($tabAccesses[(int) $idTab]['view'] === '1');
        }

        return false;
    }

    /**
     * @param int   $idTab
     * @param array $tabs
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function recursiveTab($idTab, $tabs)
    {
        $adminTab = Tab::getTab((int) Context::getContext()->language->id, $idTab);
        $tabs[] = $adminTab;
        if ($adminTab['id_parent'] > 0) {
            $tabs = Tab::recursiveTab($adminTab['id_parent'], $tabs);
        }

        return $tabs;
    }

    /**
     * Get tab
     *
     * @return array tab
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTab($idLang, $idTab)
    {
        $cacheId = 'Tab::getTab_'.(int) $idLang.'-'.(int) $idTab;
        if (!Cache::isStored($cacheId)) {
            /* Tabs selection */
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                '
				SELECT *
				FROM `'._DB_PREFIX_.'tab` t
				LEFT JOIN `'._DB_PREFIX_.'tab_lang` tl
					ON (t.`id_tab` = tl.`id_tab` AND tl.`id_lang` = '.(int) $idLang.')
				WHERE t.`id_tab` = '.(int) $idTab.(defined('_PS_HOST_MODE_') ? ' AND `hide_host_mode` = 0' : '')
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @param int $idParent
     * @param int $idProfile
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTabByIdProfile($idParent, $idProfile)
    {
        return Db::getInstance()->executeS(
            '
			SELECT t.`id_tab`, t.`id_parent`, tl.`name`, a.`id_profile`
			FROM `'._DB_PREFIX_.'tab` t
			LEFT JOIN `'._DB_PREFIX_.'access` a
				ON (a.`id_tab` = t.`id_tab`)
			LEFT JOIN `'._DB_PREFIX_.'tab_lang` tl
				ON (t.`id_tab` = tl.`id_tab` AND tl.`id_lang` = '.(int) Context::getContext()->language->id.')
			WHERE a.`id_profile` = '.(int) $idProfile.'
			AND t.`id_parent` = '.(int) $idParent.'
			AND a.`view` = 1
			AND a.`edit` = 1
			AND a.`delete` = 1
			AND a.`add` = 1
			AND t.`id_parent` != 0 AND t.`id_parent` != -1
			'.(defined('_PS_HOST_MODE_') ? ' AND `hide_host_mode` = 0' : '').'
			ORDER BY t.`id_parent` ASC
		'
        );
    }

    /**
     * @param int $idTab
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTabModulesList($idTab)
    {
        $modulesList = ['default_list' => [], 'slider_list' => []];
        $xmlTabModulesList = false;

        if (file_exists(_PS_ROOT_DIR_.Module::CACHE_FILE_TAB_MODULES_LIST)) {
            $xmlTabModulesList = @simplexml_load_file(_PS_ROOT_DIR_.Module::CACHE_FILE_TAB_MODULES_LIST);
        }

        $className = null;
        $displayType = 'default_list';
        if ($xmlTabModulesList) {
            foreach ($xmlTabModulesList->tab as $tab) {
                foreach ($tab->attributes() as $key => $value) {
                    if ($key == 'class_name') {
                        $className = (string) $value;
                    }
                }

                if (Tab::getIdFromClassName((string) $className) == $idTab) {
                    foreach ($tab->attributes() as $key => $value) {
                        if ($key == 'display_type') {
                            $displayType = (string) $value;
                        }
                    }

                    foreach ($tab->children() as $module) {
                        $modulesList[$displayType][(int) $module['position']] = (string) $module['name'];
                    }
                    ksort($modulesList[$displayType]);
                }
            }
        }

        return $modulesList;
    }

    /**
     * additionnal treatments for Tab when creating new one :
     * - generate a new position
     * - add access for admin profile
     *
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return int id_tab
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autodate = true, $nullValues = false)
    {
        // @retrocompatibility with old menu (before 1.5.0.9)
        $retro = [
            'AdminPayment'     => 'AdminParentModules',
            'AdminOrders'      => 'AdminParentOrders',
            'AdminCustomers'   => 'AdminParentCustomer',
            'AdminShipping'    => 'AdminParentShipping',
            'AdminPreferences' => 'AdminParentPreferences',
            'AdminStats'       => 'AdminParentStats',
            'AdminEmployees'   => 'AdminAdmin',
        ];

        $className = Tab::getClassNameById($this->id_parent);
        if (isset($retro[$className])) {
            $this->id_parent = Tab::getIdFromClassName($retro[$className]);
        }
        static::$_cache_tabs = [];

        // Set good position for new tab
        $this->position = Tab::getNewLastPosition($this->id_parent);
        $this->module = Tools::strtolower($this->module);

        // Add tab
        if (parent::add($autodate, $nullValues)) {
            //forces cache to be reloaded
            static::$_getIdFromClassName = null;

            return Tab::initAccess($this->id);
        }

        return false;
    }

    /**
     * @param int $idTab
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getClassNameById($idTab)
    {
        return Db::getInstance()->getValue('SELECT class_name FROM '._DB_PREFIX_.'tab WHERE id_tab = '.(int) $idTab);
    }

    /**
     * return an available position in subtab for parent $id_parent
     *
     * @param mixed $idParent
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNewLastPosition($idParent)
    {
        return (Db::getInstance()->getValue(
            '
			SELECT IFNULL(MAX(position),0)+1
			FROM `'._DB_PREFIX_.'tab`
			WHERE `id_parent` = '.(int) $idParent
        ));
    }

    /** When creating a new tab $id_tab, this add default rights to the table access
     *
     * @todo    this should not be public static but protected
     *
     * @param int     $idTab
     * @param Context $context
     *
     * @return bool true if succeed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function initAccess($idTab, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }
        if (!$context->employee || !$context->employee->id_profile) {
            return false;
        }

        /* Profile selection */
        $profiles = Db::getInstance()->executeS('SELECT `id_profile` FROM '._DB_PREFIX_.'profile WHERE `id_profile` != 1');
        if (!$profiles || empty($profiles)) {
            return true;
        }

        /* Query definition */
        $query = 'REPLACE INTO `'._DB_PREFIX_.'access` (`id_profile`, `id_tab`, `view`, `add`, `edit`, `delete`) VALUES ';
        $query .= '(1, '.(int) $idTab.', 1, 1, 1, 1),';

        foreach ($profiles as $profile) {
            $rights = $profile['id_profile'] == $context->employee->id_profile ? 1 : 0;
            $query .= '('.(int) $profile['id_profile'].', '.(int) $idTab.', '.(int) $rights.', '.(int) $rights.', '.(int) $rights.', '.(int) $rights.'),';
        }
        $query = trim($query, ', ');

        return Db::getInstance()->execute($query);
    }

    /**
     * @param bool $nullValues
     * @param bool $autodate
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function save($nullValues = false, $autodate = true)
    {
        static::$_getIdFromClassName = null;

        return parent::save();
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if (Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'access WHERE `id_tab` = '.(int) $this->id) && parent::delete()) {
            if (is_array(static::$_getIdFromClassName) && isset(static::$_getIdFromClassName[strtolower($this->class_name)])) {
                static::$_getIdFromClassName = null;
            }

            return $this->cleanPositions($this->id_parent);
        }

        return false;
    }

    /**
     * @param $idParent
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function cleanPositions($idParent)
    {
        $result = Db::getInstance()->executeS(
            '
			SELECT `id_tab`
			FROM `'._DB_PREFIX_.'tab`
			WHERE `id_parent` = '.(int) $idParent.'
			ORDER BY `position`
		'
        );
        $sizeof = count($result);
        for ($i = 0; $i < $sizeof; ++$i) {
            Db::getInstance()->execute(
                '
				UPDATE `'._DB_PREFIX_.'tab`
				SET `position` = '.($i + 1).'
				WHERE `id_tab` = '.(int) $result[$i]['id_tab']
            );
        }

        return true;
    }

    /**
     * @param $direction
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function move($direction)
    {
        $nbTabs = Tab::getNbTabs($this->id_parent);
        if ($direction != 'l' && $direction != 'r') {
            return false;
        }
        if ($nbTabs <= 1) {
            return false;
        }
        if ($direction == 'l' && $this->position <= 1) {
            return false;
        }
        if ($direction == 'r' && $this->position >= $nbTabs) {
            return false;
        }

        $newPosition = ($direction == 'l') ? $this->position - 1 : $this->position + 1;
        Db::getInstance()->execute(
            '
			UPDATE `'._DB_PREFIX_.'tab` t
			SET position = '.(int) $this->position.'
			WHERE id_parent = '.(int) $this->id_parent.'
				AND position = '.(int) $newPosition
        );
        $this->position = $newPosition;

        return $this->update();
    }

    /**
     * @param null $idParent
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNbTabs($idParent = null)
    {
        return (int) Db::getInstance()->getValue(
            '
			SELECT COUNT(*)
			FROM `'._DB_PREFIX_.'tab` t
			'.(!is_null($idParent) ? 'WHERE t.`id_parent` = '.(int) $idParent : '')
        );
    }

    /**
     * Overrides update to set position to last when changing parent tab
     *
     * @see     ObjectModel::update
     *
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($nullValues = false)
    {
        $currentTab = new Tab($this->id);
        if ($currentTab->id_parent != $this->id_parent) {
            $this->position = Tab::getNewLastPosition($this->id_parent);
        }

        static::$_cache_tabs = [];

        return parent::update($nullValues);
    }

    /**
     * @param $way
     * @param $position
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updatePosition($way, $position)
    {
        if (!$res = Db::getInstance()->executeS(
            '
			SELECT t.`id_tab`, t.`position`, t.`id_parent`
			FROM `'._DB_PREFIX_.'tab` t
			WHERE t.`id_parent` = '.(int) $this->id_parent.'
			ORDER BY t.`position` ASC'
        )
        ) {
            return false;
        }

        foreach ($res as $tab) {
            if ((int) $tab['id_tab'] == (int) $this->id) {
                $movedTab = $tab;
            }
        }

        if (!isset($movedTab) || !isset($position)) {
            return false;
        }
        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $result = (Db::getInstance()->execute(
                '
			UPDATE `'._DB_PREFIX_.'tab`
			SET `position`= `position` '.($way ? '- 1' : '+ 1').'
			WHERE `position`
			'.($way
                    ? '> '.(int) $movedTab['position'].' AND `position` <= '.(int) $position
                    : '< '.(int) $movedTab['position'].' AND `position` >= '.(int) $position).'
			AND `id_parent`='.(int) $movedTab['id_parent']
            )
            && Db::getInstance()->execute(
                '
			UPDATE `'._DB_PREFIX_.'tab`
			SET `position` = '.(int) $position.'
			WHERE `id_parent` = '.(int) $movedTab['id_parent'].'
			AND `id_tab`='.(int) $movedTab['id_tab']
            ));

        return $result;
    }
}
