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
 * Class ProfileCore
 *
 * @since 1.0.0
 */
class ProfileCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    protected static $_cache_accesses = [];
    /** @var string Name */
    public $name;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'profile',
        'primary'   => 'id_profile',
        'multilang' => true,
        'fields'    => [
            /* Lang fields */
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
        ],
    ];

    /**
     * Get all available profiles
     *
     * @param $idLang
     *
     * @return array Profiles
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProfiles($idLang)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     * @param int      $idProfile
     * @param int|null $idLang
     *
     * @return string Profile
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProfile($idProfile, $idLang = null)
    {
        if (!$idLang) {
            $idLang = Configuration::get('PS_LANG_DEFAULT');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
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
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProfileAccess($idProfile, $idTab)
    {
        // getProfileAccesses is cached so there is no performance leak
        $accesses = Profile::getProfileAccesses($idProfile);

        return (isset($accesses[$idTab]) ? $accesses[$idTab] : false);
    }

    /**
     * @param int    $idProfile
     * @param string $type
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProfileAccesses($idProfile, $type = 'id_tab')
    {
        if (!in_array($type, ['id_tab', 'class_name'])) {
            return false;
        }

        // @codingStandardsIgnoreStart
        if (!isset(static::$_cache_accesses[$idProfile])) {
            static::$_cache_accesses[$idProfile] = [];
        }
        if (!isset(static::$_cache_accesses[$idProfile][$type])) {
            static::$_cache_accesses[$idProfile][$type] = [];
            // Super admin profile has full auth
            if ($idProfile == _PS_ADMIN_PROFILE_) {
                foreach (Tab::getTabs(Context::getContext()->language->id) as $tab) {
                    static::$_cache_accesses[$idProfile][$type][$tab[$type]] = [
                        'id_profile' => _PS_ADMIN_PROFILE_,
                        'id_tab'     => $tab['id_tab'],
                        'class_name' => $tab['class_name'],
                        'view'       => '1',
                        'add'        => '1',
                        'edit'       => '1',
                        'delete'     => '1',
                    ];
                }
            } else {
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('*')
                        ->from('access', 'a')
                        ->leftJoin('tab', 't', 't.`id_tab` = a.`id_tab`')
                        ->where('`id_profile` = '.(int) $idProfile)
                );

                foreach ($result as $row) {
                    static::$_cache_accesses[$idProfile][$type][$row[$type]] = $row;
                }
            }
        }

        return static::$_cache_accesses[$idProfile][$type];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (parent::add($autoDate, true)) {
            $result = Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'access (SELECT '.(int) $this->id.', id_tab, 0, 0, 0, 0 FROM '._DB_PREFIX_.'tab)');
            $result &= Db::getInstance()->execute(
                '
				INSERT INTO '._DB_PREFIX_.'module_access
				(`id_profile`, `id_module`, `configure`, `view`, `uninstall`)
				(SELECT '.(int) $this->id.', id_module, 0, 1, 0 FROM '._DB_PREFIX_.'module)
			'
            );

            return $result;
        }

        return false;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public function delete()
    {
        if (parent::delete()) {
            return (
                Db::getInstance()->delete('access', '`id_profile` = '.(int) $this->id)
                && Db::getInstance()->delete('module_access', '`id_profile` = '.(int) $this->id)
            );
        }

        return false;
    }
}
