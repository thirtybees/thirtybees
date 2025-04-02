<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * Class OrderDetailPackCore
 */
class OrderDetailPackCore extends ObjectModel
{
    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'order_detail_pack',
        'primary' => 'id_order_detail_pack',
        'fields'  => [
            'id_order_detail'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_product'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'default' => '0'],
            'quantity'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
        ],
        'keys' => [
            'order_detail_pack' => [
                'detail' => ['type' => ObjectModel::KEY, 'columns' => ['id_order_detail']],
                'product' => ['type' => ObjectModel::KEY, 'columns' => ['id_product', 'id_product_attribute']],
            ],
        ],
    ];

    /**
     * @var int $id_order_detail
     */
    public $id_order_detail;

    /**
     * @var int $id_product
     */
    public $id_product;

    /**
     * @var int $id_product_attribute
     */
    public $id_product_attribute;

    /**
     * @var int $quantity
     */
    public $quantity;

    /**
     * Is product a pack?
     *
     * @param int $idOrderDetail
     * @return bool
     * @throws PrestaShopException
     */
    public static function isPack($idOrderDetail)
    {
        $$idOrderDetail = (int) $idOrderDetail;
        return (bool) static::getPackContent($idOrderDetail);
    }

    /**
     * @param int $idOrderDetail
     * @param int $idLang
     * @return Product[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getItems($idOrderDetail, $idLang)
    {
        if (!static::isFeatureActive()) {
            return [];
        }
        $idOrderDetail = (int) $idOrderDetail;
        $idLang = (int) $idLang;
        $cacheKey = "OrderDetailPack::getItems($idOrderDetail,$idLang)";
        if (!Cache::isStored($cacheKey)) {
            Cache::store($cacheKey, static::retrieveItems($idOrderDetail, $idLang));
        }
        return Cache::retrieve($cacheKey);
    }

    /**
     * @param int $idOrderDetail
     * @param int $idLang
     * @return Product[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function retrieveItems($idOrderDetail, $idLang)
    {
        $idOrderDetail = (int) $idOrderDetail;
        $idLang = (int) $idLang;
        $arrayResult = [];
        foreach (static::getPackContent($idOrderDetail) as $row) {
            $p = new Product($row['id_product'], false, $idLang);
            $p->loadStockData();
            $p->pack_quantity = $row['quantity'];
            $p->id_pack_product_attribute = $row['id_product_attribute'];
            if ($p->id_pack_product_attribute) {
                $sql = 'SELECT agl.`name` AS group_name, al.`name` AS attribute_name, pa.`reference` AS attribute_reference
                    FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                    ' . Shop::addSqlAssociation('product_attribute', 'pa') . '
                    LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . $idLang . ')
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . $idLang . ')
                    WHERE pa.`id_product_attribute` = ' . $p->id_pack_product_attribute . '
                    GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
                    ORDER BY pa.`id_product_attribute`';
                $combinations = Db::readOnly()->getArray($sql);
                foreach ($combinations as $combination) {
                    $p->name .= ' ' . $combination['group_name'] . '-' . $combination['attribute_name'];
                    $reference = (string) $combination['attribute_reference'];
                    if ($reference) {
                        $p->reference = $combination['attribute_reference'];
                    }
                }
            }
            $arrayResult[] = $p;
        }
        return $arrayResult;
    }

    /**
     * Returns information about pack items.
     *
     * @param int $idOrderDetail
     * @return array
     * @throws PrestaShopException
     */
    public static function getPackContent($idOrderDetail)
    {
        $idOrderDetail = (int) $idOrderDetail;
        if (!$idOrderDetail || !static::isFeatureActive()) {
            return [];
        }
        $cacheKey = "OrderDetailPack::getPackContent($idOrderDetail)";
        if (!Cache::isStored($cacheKey)) {
            Cache::store($cacheKey, static::retrievePackContent($idOrderDetail));
        }
        return Cache::retrieve($cacheKey);
    }

    /**
     * Retrieves information about pack items from database
     *
     * @param int $idOrderDetail
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function retrievePackContent($idOrderDetail)
    {
        $idOrderDetail = (int) $idOrderDetail;
        $content = [];
        $sql = (new DbQuery())
            ->select('id_product')
            ->select('id_product_attribute')
            ->select('quantity')
            ->from('order_detail_pack')
            ->where('id_order_detail = ' . $idOrderDetail)
            ->orderBy('id_product, id_product_attribute');
        $result = Db::readOnly()->getArray($sql);
        foreach ($result as $row) {
            $content[] = [
                'id_product' => (int) $row['id_product'],
                'id_product_attribute' => (int) $row['id_product_attribute'],
                'quantity' => (int) $row['quantity']
            ];
        }
        return $content;
    }

    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function isFeatureActive()
    {
        return Configuration::get('PS_PACK_FEATURE_ACTIVE');
    }
}
