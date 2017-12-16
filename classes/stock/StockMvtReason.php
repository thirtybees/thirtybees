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
 * Class StockMvtReasonCore
 *
 * @since 1.0.0
 */
class StockMvtReasonCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int identifier of the movement reason */
    public $id;

    /** @var string the name of the movement reason */
    public $name;

    /** @var int detrmine if the movement reason correspond to a positive or negative operation */
    public $sign;

    /** @var string the creation date of the movement reason */
    public $date_add;

    /** @var string the last update date of the movement reason */
    public $date_upd;

    /** @var bool True if the movement reason has been deleted (staying in database as deleted) */
    public $deleted = 0;
    // @codingStandardsIgnoreEnd

    /**
     * @since 1.5.0
     * @see   ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'stock_mvt_reason',
        'primary'   => 'id_stock_mvt_reason',
        'multilang' => true,
        'fields'    => [
            'sign'     => ['type' => self::TYPE_INT],
            'deleted'  => ['type' => self::TYPE_BOOL],
            'date_add' => ['type' => self::TYPE_DATE,                   'validate' => 'isDate'                                          ],
            'date_upd' => ['type' => self::TYPE_DATE,                   'validate' => 'isDate'                                          ],
            'name'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
        ],
    ];

    /**
     * @see ObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'objectsNodeName' => 'stock_movement_reasons',
        'objectNodeName'  => 'stock_movement_reason',
        'fields'          => [
            'sign' => [],
        ],
    ];

    /**
     * Gets Stock Mvt Reasons
     *
     * @param int $idLang
     * @param int $sign Optional
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getStockMvtReasons($idLang, $sign = null)
    {
        $query = new DbQuery();
        $query->select('smrl.name, smr.id_stock_mvt_reason, smr.sign');
        $query->from('stock_mvt_reason', 'smr');
        $query->leftjoin('stock_mvt_reason_lang', 'smrl', 'smr.id_stock_mvt_reason = smrl.id_stock_mvt_reason AND smrl.id_lang='.(int) $idLang);
        $query->where('smr.deleted = 0');

        if ($sign != null) {
            $query->where('smr.sign = '.(int) $sign);
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * Same as StockMvtReason::getStockMvtReasons(), ignoring a specific lists of ids
     *
     * @param int   $idLang
     * @param array $idsIgnore
     * @param int   $sign optional
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @return array|false|null|PDOStatement
     */
    public static function getStockMvtReasonsWithFilter($idLang, $idsIgnore, $sign = null)
    {
        $query = new DbQuery();
        $query->select('smrl.name, smr.id_stock_mvt_reason, smr.sign');
        $query->from('stock_mvt_reason', 'smr');
        $query->leftjoin('stock_mvt_reason_lang', 'smrl', 'smr.id_stock_mvt_reason = smrl.id_stock_mvt_reason AND smrl.id_lang='.(int) $idLang);
        $query->where('smr.deleted = 0');

        if ($sign != null) {
            $query->where('smr.sign = '.(int) $sign);
        }

        if (count($idsIgnore)) {
            $idsIgnore = array_map('intval', $idsIgnore);
            $query->where('smr.id_stock_mvt_reason NOT IN('.implode(', ', $idsIgnore).')');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * For a given id_stock_mvt_reason, tells if it exists
     *
     * @param int $idStockMvtReason
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function exists($idStockMvtReason)
    {
        $query = new DbQuery();
        $query->select('smr.id_stock_mvt_reason');
        $query->from('stock_mvt_reason', 'smr');
        $query->where('smr.id_stock_mvt_reason = '.(int) $idStockMvtReason);
        $query->where('smr.deleted = 0');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }
}
