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
 * Class RangeWeightCore
 *
 * @since 1.0.0
 */
class RangeWeightCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int $id_carrier */
    public $id_carrier;
    /** @var float $delimiter1 */
    public $delimiter1;
    /** @var float $delimiter2 */
    public $delimiter2;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'range_weight',
        'primary' => 'id_range_weight',
        'fields'  => [
            'id_carrier' => ['type' => self::TYPE_INT,   'validate' => 'isInt',           'required' => true],
            'delimiter1' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => true],
            'delimiter2' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => true],
        ],
    ];

    protected $webserviceParameters = [
        'objectNodeName'  => 'weight_range',
        'objectsNodeName' => 'weight_ranges',
        'fields'          => [
            'id_carrier' => ['xlink_resource' => 'carriers'],
        ],
    ];

    /**
     * Override add to create delivery value for all zones
     *
     * @see     classes/ObjectModelCore::add()
     *
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool Insertion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (!parent::add($autoDate, $nullValues) || !Validate::isLoadedObject($this)) {
            return false;
        }
        if (defined('TB_INSTALLATION_IN_PROGRESS')) {
            return true;
        }
        $carrier = new Carrier((int) $this->id_carrier);
        $priceList = [];
        foreach ($carrier->getZones() as $zone) {
            $priceList[] = [
                'id_range_price'  => null,
                'id_range_weight' => (int) $this->id,
                'id_carrier'      => (int) $this->id_carrier,
                'id_zone'         => (int) $zone['id_zone'],
                'price'           => 0,
            ];
        }
        $carrier->addDeliveryPrice($priceList);

        return true;
    }

    /**
     * Get all available price ranges
     *
     * @param int $idCarrier
     *
     * @return array Ranges
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getRanges($idCarrier)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('range_weight')
                ->where('`id_carrier` = '.(int) $idCarrier)
                ->orderBy('`delimiter1` ASC')
        );
    }

    /**
     * @param int      $idCarrier
     * @param float    $delimiter1
     * @param float    $delimiter2
     * @param int|null $idReference
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function rangeExist($idCarrier, $delimiter1, $delimiter2, $idReference = null)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('range_weight', 'rw')
                ->join((is_null($idCarrier) && $idReference ? ' INNER JOIN `'._DB_PREFIX_.'carrier` c on (rw.`id_carrier` = c.`id_carrier`)' : ''))
                ->where($idCarrier ? '`id_carrier` = '.(int) $idCarrier : '')
                ->where((is_null($idCarrier) && $idReference ? 'c.`id_reference` = '.(int) $idReference : ''))
                ->where((is_null($idCarrier) && $idReference ? 'c.`id_reference` = '.(int) $idReference : ''))
                ->where('`delimiter1` = '.(float) $delimiter1)
                ->where('`delimiter2` = '.(float) $delimiter2)
        );
    }

    /**
     * @param int      $idCarrier
     * @param float    $delimiter1
     * @param float    $delimiter2
     * @param int|null $idRang
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isOverlapping($idCarrier, $delimiter1, $delimiter2, $idRang = null)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('range_weight')
                ->where('`id_carrier` = '.(int) $idCarrier)
                ->where('((`delimiter1` >= '.(float) $delimiter1.' AND `delimiter1` < '.(float) $delimiter2.') OR (`delimiter2` > '.(float) $delimiter1.' AND `delimiter2` < '.(float) $delimiter2.') OR ('.(float) $delimiter1.' > `delimiter1` AND '.(float) $delimiter1.' < `delimiter2`) OR ('.(float) $delimiter2.' < `delimiter1` AND '.(float) $delimiter2.' > `delimiter2`)')
                ->where(!is_null($idRang) ? '`id_range_weight` != '.(int) $idRang : '')
        );
    }
}
