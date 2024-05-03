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

/**
 * Class ProfileCore
 */
class ProfileCore extends ObjectModel
{
    const PERMISSION_VIEW = 'view';
    const PERMISSION_ADD = 'add';
    const PERMISSION_EDIT = 'edit';
    const PERMISSION_DELETE = 'delete';

    /**
     * @var array
     */
    protected static $_cache_accesses = [];

    /**
     * @var array
     */
    protected static $_cache_permissions = [];

    /**
     * @var string|string[] Name
     */
    public $name;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'     => 'profile',
        'primary'   => 'id_profile',
        'multilang' => true,
        'fields'    => [
            /* Lang fields */
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
        ],
    ];

    /**
     * Get all available profiles
     *
     * @param int $idLang
     *
     * @return array Profiles
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getProfiles($idLang)
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('p.`id_profile`, `name`')
                ->from('profile', 'p')
                ->leftJoin('profile_lang', 'pl', 'p.`id_profile` = pl.`id_profile`')
                ->where('`id_lang` = '.(int) $idLang)
                ->orderBy('`id_profile` ASC')
        );
    }

    /**
     * Get the current profile name
     *
     * @param int $idProfile
     * @param int|null $idLang
     *
     * @return string|false Profile
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getProfile($idProfile, $idLang = null)
    {
        if (!$idLang) {
            $idLang = Configuration::get('PS_LANG_DEFAULT');
        }

        return Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`name`')
                ->from('profile', 'p')
                ->leftJoin('profile_lang', 'pl', 'p.`id_profile` = pl.`id_profile`')
                ->where('p.`id_profile` = '.(int) $idProfile)
                ->where('pl.`id_lang` = '.(int) $idLang)
        );
    }

    /**
     * @param int $idProfile
     * @param int $idTab
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getProfileAccess($idProfile, $idTab)
    {
        $idProfile = (int)$idProfile;
        $accesses = Profile::getProfileAccesses($idProfile);

        if (isset($accesses[$idTab]) && is_array($accesses[$idTab])) {
            return $accesses[$idTab];
        }

        $perm = static::formatPermissionValue($idProfile === _PS_ADMIN_PROFILE_);
        return [
            'id_profile' => $idProfile,
            'id_tab'     => $idTab,
            'class_name' => '',
            'view'       => $perm,
            'add'        => $perm,
            'edit'       => $perm,
            'delete'     => $perm,
        ];
    }

    /**
     * Returns permission level
     *
     * @param int $idProfile
     * @param string $group
     * @param string $permission
     *
     * @return string | false
     *
     * @throws PrestaShopException
     */
    public static function getProfilePermission($idProfile, $group, $permission)
    {
        $idProfile = (int)$idProfile;
        if (! isset(static::$_cache_permissions[$idProfile])) {
            static::$_cache_permissions[$idProfile] = static::loadPermissions($idProfile);
        }

        return static::$_cache_permissions[$idProfile][$group][$permission] ?? false;
    }

    /**
     * Loads profile permissions from database
     *
     * @param int $idProfile
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function loadPermissions($idProfile)
    {
        $data = Db::readOnly()->getArray(
            (new DbQuery())
                ->from('profile_permission')
                ->where("id_profile = " . (int)$idProfile)
        );

        $result = [];
        foreach ($data as $row) {
            $group = $row['perm_group'];
            $permission = $row['permission'];
            $level = $row['level'];
            if (! isset($result[$group])) {
                $result[$group] = [];
            }
            $result[$group][$permission] = $level;
        }
        return $result;
    }

    /**
     * @param int $idProfile
     * @param string $type
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getProfileAccesses($idProfile, $type = 'id_tab')
    {
        $idProfile = (int)$idProfile;
        if (!in_array($type, ['id_tab', 'class_name'])) {
            return false;
        }

        if (!isset(static::$_cache_accesses[$idProfile])) {
            static::$_cache_accesses[$idProfile] = [];
        }
        if (!isset(static::$_cache_accesses[$idProfile][$type])) {
            static::$_cache_accesses[$idProfile][$type] = [];
            // Super admin profile has full auth
            if ($idProfile === _PS_ADMIN_PROFILE_) {
                foreach (Tab::getTabs(Context::getContext()->language->id) as $tab) {
                    static::$_cache_accesses[$idProfile][$type][$tab[$type]] = [
                        'id_profile' => _PS_ADMIN_PROFILE_,
                        'id_tab'     => $tab['id_tab'],
                        'class_name' => $tab['class_name'],
                        'view'       => static::formatPermissionValue(true),
                        'add'        => static::formatPermissionValue(true),
                        'edit'       => static::formatPermissionValue(true),
                        'delete'     => static::formatPermissionValue(true),
                    ];
                }
            } else {
                $result = Db::readOnly()->getArray(
                    (new DbQuery())
                        ->select('*')
                        ->from('access', 'a')
                        ->leftJoin('tab', 't', 't.`id_tab` = a.`id_tab`')
                        ->where('`id_profile` = '.$idProfile)
                );

                foreach ($result as $row) {
                    $row[static::PERMISSION_VIEW] = static::formatPermissionValue($row['view']);
                    $row[static::PERMISSION_ADD] = static::formatPermissionValue($row['add']);
                    $row[static::PERMISSION_EDIT] = static::formatPermissionValue($row['edit']);
                    $row[static::PERMISSION_DELETE] = static::formatPermissionValue($row['delete']);
                    static::$_cache_accesses[$idProfile][$type][$row[$type]] = $row;
                }
            }
        }

        return static::$_cache_accesses[$idProfile][$type];
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (parent::add($autoDate, true)) {
            $conn = Db::getInstance();
            $result = $conn->execute('INSERT INTO '._DB_PREFIX_.'access (SELECT '.(int) $this->id.', id_tab, 0, 0, 0, 0 FROM '._DB_PREFIX_.'tab)');
            $result = $conn->execute(
                '
				INSERT INTO '._DB_PREFIX_.'module_access
				(`id_profile`, `id_module`, `configure`, `view`, `uninstall`)
				(SELECT '.(int) $this->id.', id_module, 0, 1, 0 FROM '._DB_PREFIX_.'module)
			'
            ) && $result;

            return $result;
        }

        return false;
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (parent::delete()) {
            $conn = Db::getInstance();
            return (
                $conn->delete('access', '`id_profile` = '.(int) $this->id) &&
                $conn->delete('module_access', '`id_profile` = '.(int) $this->id)
            );
        }

        return false;
    }

    /**
     * @param \CoreUpdater\TableSchema $table
     */
    public static function processTableSchema($table)
    {
        if ($table->getNameWithoutPrefix() === 'profile_lang') {
            $table->reorderColumns(['id_lang', 'id_profile']);
        }
    }

    /**
     * Invalidates cache for permissions
     *
     * @param int $profileId
     */
    public static function invalidateCache($profileId)
    {
        if (isset(static::$_cache_permissions[$profileId])) {
            unset(static::$_cache_permissions[$profileId]);
        }
        if (isset(static::$_cache_accesses[$profileId])) {
            unset(static::$_cache_accesses[$profileId]);
        }
    }

    /**
     * Returns true, if $permission is a valid permission type: view, delete, add, edit
     *
     * @param string $permission
     * @return bool
     */
    public static function isValidPermission($permission)
    {
       return in_array((string)$permission, [
           Profile::PERMISSION_VIEW,
           Profile::PERMISSION_DELETE,
           Profile::PERMISSION_ADD,
           Profile::PERMISSION_EDIT,
       ]);
    }

    /**
     * Helper method to format permission value. In the future, this will return boolean value.
     * For compatibility reasons we have to use string values now
     *
     * @param bool $hasPermission
     * @return string
     */
    public static function formatPermissionValue($hasPermission)
    {
        return $hasPermission ? '1' : '0';
    }
}