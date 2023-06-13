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
 * Class TabCore
 */
class TabCore extends ObjectModel
{
    /**
     * @var array|null
     */
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
     * @var string|string[]
     */
    public $name;

    /**
     * @var string Class and file name
     */
    public $class_name;

    /**
     * @var string
     */
    public $module;

    /**
     * @var int parent ID
     */
    public $id_parent;

    /**
     * @var int position
     */
    public $position;

    /**
     * @var bool active
     */
    public $active = true;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'     => 'tab',
        'primary'   => 'id_tab',
        'multilang' => true,
        'fields'    => [
            'id_parent'      => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'int(11)', 'dbNullable' => false],
            'class_name'     => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 64],
            'module'         => ['type' => self::TYPE_STRING, 'validate' => 'isTabName', 'size' => 64],
            'position'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbNullable' => false],
            'active'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            /* Lang fields */
            'name'           => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isTabName', 'size' => 64],
        ],
        'keys' => [
            'tab' => [
                'class_name' => ['type' => ObjectModel::KEY, 'columns' => ['class_name']],
                'id_parent'  => ['type' => ObjectModel::KEY, 'columns' => ['id_parent']],
            ],
        ],
    ];

    /**
     * Get tab id
     *
     * @return int tab id
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @return int|false Tab ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getIdFromClassName($className)
    {
        if (! is_string($className)) {
            return false;
        }
        $className = strtolower($className);
        if (str_ends_with($className, "core")) {
            $className = substr($className, 0, -4);
        }
        if (str_ends_with($className, "controller")) {
            $className = substr($className, 0, -10);
        }
        if (static::$_getIdFromClassName === null) {
            static::$_getIdFromClassName = [];
            $result = Db::readOnly()->getArray(
                (new DbQuery())
                    ->select('`id_tab`, `class_name`')
                    ->from('tab')
            );

            foreach ($result as $row) {
                static::$_getIdFromClassName[strtolower($row['class_name'])] = (int)$row['id_tab'];
            }
        }

        return isset(static::$_getIdFromClassName[$className])
            ? (int) static::$_getIdFromClassName[$className]
            : false;
    }

    /**
     * Get tab parent id
     *
     * @return int tab parent id
     * @throws PrestaShopException
     */
    public static function getCurrentParentId()
    {
        $cacheId = 'getCurrentParentId_'.mb_strtolower(Tools::getValue('controller'));
        if (!Cache::isStored($cacheId)) {
            $value = Db::readOnly()->getValue(
                (new DbQuery())
                    ->select('`id_parent`')
                    ->from('tab')
                    ->where('LOWER(`class_name`) = \''.pSQL(mb_strtolower(Tools::getValue('controller'))).'\'')
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getModuleTabList()
    {
        $list = [];

        $result = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('t.`class_name`, t.`module`')
                ->from('tab', 't')
                ->where('t.`module` IS NOT NULL')
                ->where('t.`module` != ""')
        );

        foreach ($result as $detail) {
            $list[strtolower($detail['class_name'])] = $detail;
        }

        return $list;
    }

    /**
     * @param int $idLang
     * @param int|null $idParent
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getTabs($idLang, $idParent = null)
    {
        if (!isset(static::$_cache_tabs[$idLang])) {
            static::$_cache_tabs[$idLang] = [];
            // Keep t.*, tl.name instead of only * because if translations are missing, the join on tab_lang will overwrite the id_tab in the results
            $result = Db::readOnly()->getArray(
                (new DbQuery())
                    ->select('t.*, COALESCE(NULLIF(tl.`name`, ""), tl_def.`name`) AS `name`')
                    ->from('tab', 't')
                    ->leftJoin('tab_lang', 'tl', 't.`id_tab` = tl.`id_tab` AND tl.`id_lang` = '.(int) $idLang)
                    ->leftJoin('tab_lang', 'tl_def', 't.`id_tab` = tl_def.`id_tab` AND tl_def.`id_lang` = ' . (int)Configuration::get('PS_LANG_DEFAULT'))
                    ->orderBy('t.`position` ASC')
            );

            foreach ($result as $row) {
                if (!isset(static::$_cache_tabs[$idLang][$row['id_parent']])) {
                    static::$_cache_tabs[$idLang][$row['id_parent']] = [];
                }
                static::$_cache_tabs[$idLang][$row['id_parent']][] = $row;
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
     * @param string $module Module Name
     *
     * @return bool Status
     *
     * @throws PrestaShopException
     */
    public static function enablingForModule($module)
    {
        $tabs = Tab::getCollectionFromModule($module);
        if (!empty($tabs)) {
            foreach ($tabs as $tab) {
                /** @var Tab $tab */
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
     * @param string $module Module name
     * @param int|null $idLang Language ID
     *
     * @return array|PrestaShopCollection Collection of tabs (or empty array)
     *
     * @throws PrestaShopException
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
     * @param string $module Module name
     *
     * @return bool Status
     *
     * @throws PrestaShopException
     */
    public static function disablingForModule($module)
    {
        $tabs = Tab::getCollectionFromModule($module);
        if (!empty($tabs)) {
            foreach ($tabs as $tab) {
                /** @var Tab $tab */
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
     * @param string $className Name of tab class
     * @param int|null $idLang id_lang
     *
     * @return Tab Tab object (empty if bad id or class name)
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getInstanceFromClassName($className, $idLang = null)
    {
        $idTab = (int) Tab::getIdFromClassName($className);

        return new Tab($idTab, $idLang);
    }

    /**
     * @param int $idTab
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function checkTabRights($idTab)
    {
        return Context::getContext()->employee->hasAccess($idTab, Profile::PERMISSION_VIEW);
    }

    /**
     * @param int $idTab
     * @param array $tabs
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function recursiveTab($idTab, $tabs=[])
    {
        $idTab = (int)$idTab;
        while ($idTab) {
            $adminTab = Tab::getTab((int)Context::getContext()->language->id, $idTab);
            if ($adminTab) {
                $tabs[] = $adminTab;
                $idTab = (int)$adminTab['id_parent'];
            } else {
                $idTab = 0;
            }
        }
        return $tabs;
    }

    /**
     * Get tab
     *
     * @param int $idLang
     * @param int $idTab
     *
     * @return array tab
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getTab($idLang, $idTab)
    {
        $cacheId = 'Tab::getTab_'.(int) $idLang.'-'.(int) $idTab;
        if (!Cache::isStored($cacheId)) {
            /* Tabs selection */
            $result = Db::readOnly()->getRow(
                (new DbQuery())
                    ->select('*')
                    ->from('tab', 't')
                    ->leftJoin('tab_lang', 'tl', 't.`id_tab` = tl.`id_tab` AND tl.`id_lang` = '.(int) $idLang)
                    ->where('t.`id_tab` = '.(int) $idTab)
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
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getTabByIdProfile($idParent, $idProfile)
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('t.`id_tab`, t.`id_parent`, tl.`name`, a.`id_profile`')
                ->from('tab', 't')
                ->leftJoin('access', 'a', 'a.`id_tab` = t.`id_tab`')
                ->leftJoin('tab_lang', 'tl', 't.`id_tab` = tl.`id_tab` AND tl.`id_lang` = '.(int) Context::getContext()->language->id)
                ->where('a.`id_profile` = '.(int) $idProfile)
                ->where('t.`id_parent` = '.(int) $idParent)
                ->where('a.`view` = 1')
                ->where('a.`edit` = 1')
                ->where('a.`delete` = 1')
                ->where('a.`add` = 1')
                ->where('t.`id_parent` != 0')
                ->where('t.`id_parent` != -1')
                ->orderBy('t.`id_parent` ASC')
        );
    }

    /**
     * @param int $idTab
     *
     * @return array
     */
    public static function getTabModulesList($idTab)
    {
        return ['default_list' => [], 'slider_list' => []];
    }

    /**
     * additionnal treatments for Tab when creating new one :
     * - generate a new position
     * - add access for admin profile
     *
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
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
        $this->module = mb_strtolower($this->module ?? '');

        // Add tab
        if (parent::add($autoDate, $nullValues)) {
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
     * @throws PrestaShopException
     */
    public static function getClassNameById($idTab)
    {
        return Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`class_name`')
                ->from('tab')
                ->where('`id_tab` = '.(int) $idTab)
        );
    }

    /**
     * return an available position in subtab for parent $id_parent
     *
     * @param int $idParent
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function getNewLastPosition($idParent)
    {
        return (Db::readOnly()->getValue(
            (new DbQuery())
                ->select('IFNULL(MAX(`position`), 0) + 1')
                ->from('tab')
                ->where('`id_parent` = '.(int) $idParent)
        ));
    }

    /** When creating a new tab $id_tab, this add default rights to the table access
     *
     * @param int $idTab
     * @param Context|null $context
     *
     * @return bool true if succeed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @todo    this should not be public static but protected
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
        $profiles = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_profile`')
                ->from('profile')
                ->where('`id_profile` != 1')
        );

        /* Query definition */
        $replace = [];
        $replace[] = [
            'id_profile' => 1,
            'id_tab'     => (int) $idTab,
            'view'       => 1,
            'add'        => 1,
            'edit'       => 1,
            'delete'     => 1,
        ];
        foreach ($profiles as $profile) {
            $rights = $profile['id_profile'] == $context->employee->id_profile ? 1 : 0;
            $replace[] = [
                'id_profile' => (int) $profile['id_profile'],
                'id_tab'     => (int) $idTab,
                'view'       => (int) $rights,
                'add'        => (int) $rights,
                'edit'       => (int) $rights,
                'delete'     => (int) $rights,
            ];
        }

        return Db::getInstance()->insert('access', $replace, false, true, Db::REPLACE);
    }

    /**
     * @param bool $nullValues
     * @param bool $autodate
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function save($nullValues = false, $autodate = true)
    {
        static::$_getIdFromClassName = null;

        return parent::save();
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (Db::getInstance()->delete('access', '`id_tab` = '.(int) $this->id) && parent::delete()) {
            if (is_array(static::$_getIdFromClassName) && isset(static::$_getIdFromClassName[strtolower($this->class_name)])) {
                static::$_getIdFromClassName = null;
            }

            return $this->cleanPositions($this->id_parent);
        }

        return false;
    }

    /**
     * @param int $idParent
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function cleanPositions($idParent)
    {
        $result = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_tab`')
                ->from('tab')
                ->where('`id_parent` = '.(int) $idParent)
                ->orderBy('position')
        );
        $sizeof = count($result);
        for ($i = 0; $i < $sizeof; ++$i) {
            Db::getInstance()->update(
                'tab',
                [
                    'position'  => $i,
                ],
                '`id_tab` = '.(int) $result[$i]['id_tab']
            );
        }

        return true;
    }

    /**
     * @param string $direction
     *
     * @return bool
     *
     * @throws PrestaShopException
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
     * @param int|null $idParent
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function getNbTabs($idParent = null)
    {
        return (int) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('tab', 't')
                ->where(!is_null($idParent) ? 't.`id_parent` = '.(int) $idParent : '')
        );
    }

    /**
     * Overrides update to set position to last when changing parent tab
     *
     * @see ObjectModel::update
     *
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
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
     * @param string $way
     * @param int $position
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updatePosition($way, $position)
    {
        if (!$res = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('t.`id_tab`, t.`position`, t.`id_parent`')
                ->from('tab', 't')
                ->where('t.`id_parent` = '.(int) $this->id_parent)
                ->orderBy('t.`position` ASC')
        )) {
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
        $conn = Db::getInstance();
        $result = ($conn->update(
            'tab',
            [

                'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
            ],
            '`position` '.($way ? '> '.(int) $movedTab['position'].' AND `position` <= '.(int) $position : '< '.(int) $movedTab['position'].' AND `position` >= '.(int) $position).' AND `id_parent`='.(int) $movedTab['id_parent']
        )
        && $conn->update(
            'tab',
            [
                'position' => (int) $position,
            ],
            '`id_parent` = '.(int) $movedTab['id_parent'].' AND `id_tab`='.(int) $movedTab['id_tab']
        ));

        return $result;
    }
}
