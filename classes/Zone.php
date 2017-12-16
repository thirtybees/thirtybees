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
 * Class ZoneCore
 *
 * @since 1.0.0
 */
class ZoneCore extends ObjectModel
{
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'zone',
        'primary' => 'id_zone',
        'fields'  => [
            'name'   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'active' => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
        ],
    ];
    /** @var string Name */
    public $name;
    /** @var bool Zone status */
    public $active = true;
    protected $webserviceParameters = [];

    /**
     * Get all available geographical zones
     *
     * @param bool $active
     *
     * @return array Zones
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getZones($active = false)
    {
        $cacheId = 'Zone::getZones_'.(bool) $active;
        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('*')
                    ->from('zone')
                    ->where($active ? '`active` = 1' : '')
                    ->orderBy('`name` ASC')
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Get a zone ID from its default language name
     *
     * @param string $name
     *
     * @return int id_zone
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getIdByName($name)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_zone`')
                ->from('zone')
                ->where('`name` = \''.pSQL($name).'\'')
        );
    }

    /**
     * Delete a zone
     *
     * @return bool Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (parent::delete()) {
            // Delete regarding delivery preferences
            $result = Db::getInstance()->delete('carrier_zone', 'id_zone = '.(int) $this->id);
            $result &= Db::getInstance()->delete('delivery', 'id_zone = '.(int) $this->id);

            // Update Country & state zone with 0
            $result &= Db::getInstance()->update('country', ['id_zone' => 0], 'id_zone = '.(int) $this->id);
            $result &= Db::getInstance()->update('state', ['id_zone' => 0], 'id_zone = '.(int) $this->id);

            return $result;
        }

        return false;
    }
}
