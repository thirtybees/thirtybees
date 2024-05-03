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
 * Class ZoneCore
 */
class ZoneCore extends ObjectModel
{
    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'zone',
        'primary' => 'id_zone',
        'fields'  => [
            'name'   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'active' => ['type' => self::TYPE_BOOL,   'validate' => 'isBool', 'dbDefault' => '0'],
        ],
        'keys' => [
            'zone_shop' => [
                'id_shop' => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
            ],
        ],

    ];

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var bool Zone status
     */
    public $active = true;

    /**
     * @var array Webservice parameters
     */
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
     */
    public static function getZones($active = false)
    {
        $cacheId = 'Zone::getZones_'.(bool) $active;
        if (!Cache::isStored($cacheId)) {
            $result = Db::readOnly()->getArray(
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
     * @throws PrestaShopException
     */
    public static function getIdByName($name)
    {
        return Db::readOnly()->getValue(
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (parent::delete()) {
            // Delete regarding delivery preferences
            $conn = Db::getInstance();
            $result = $conn->delete('carrier_zone', 'id_zone = '.(int) $this->id);
            $result = $conn->delete('delivery', 'id_zone = '.(int) $this->id) && $result;

            // Update Country & state zone with 0
            $result = $conn->update('country', ['id_zone' => 0], 'id_zone = '.(int) $this->id) && $result;
            $result = $conn->update('state', ['id_zone' => 0], 'id_zone = '.(int) $this->id) && $result;

            return $result;
        }

        return false;
    }
}
