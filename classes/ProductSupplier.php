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
 * Class ProductSupplierCore
 */
class ProductSupplierCore extends ObjectModel
{
    /**
     * @var int product ID
     */
    public $id_product;

    /**
     * @var int product attribute ID
     */
    public $id_product_attribute;

    /**
     * @var int the supplier ID
     */
    public $id_supplier;

    /**
     * @var string The supplier name of the product
     */
    public $product_supplier_name;

    /**
     * @var string The supplier reference of the product
     */
    public $product_supplier_reference;

    /**
     * @var int the currency ID for unit price tax excluded
     */
    public $id_currency;

    /**
     * @var float The unit price tax excluded of the product
     */
    public $product_supplier_price_te;

    /**
     * @var string Additional information for this product
     */
    public $product_supplier_comment;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'product_supplier',
        'primary' => 'id_product_supplier',
        'fields'  => [
            'id_product'                 => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true               ],
            'id_product_attribute'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'dbDefault' => '0'],
            'id_supplier'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true               ],
            'product_supplier_name'      => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'size' => 128],
            'product_supplier_reference' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => self::SIZE_REFERENCE],
            'product_supplier_price_te'  => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
            'product_supplier_comment'   => ['type' => self::TYPE_STRING, 'size' => 250],
            'id_currency'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false],
        ],
        'keys' => [
            'product_supplier' => [
                'id_product'  => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['id_product', 'id_product_attribute', 'id_supplier']],
                'id_supplier' => ['type' => ObjectModel::KEY, 'columns' => ['id_supplier', 'id_product']],
            ],
        ],
    ];

    /**
     * @see ObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'objectsNodeName' => 'product_suppliers',
        'objectNodeName'  => 'product_supplier',
        'fields'          => [
            'id_product'           => ['xlink_resource' => 'products'],
            'id_product_attribute' => ['xlink_resource' => 'combinations'],
            'id_supplier'          => ['xlink_resource' => 'suppliers'],
            'id_currency'          => ['xlink_resource' => 'currencies'],
        ],
    ];

    /**
     * For a given product and supplier, gets the product supplier reference
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idSupplier
     *
     * @return string|false
     *
     * @throws PrestaShopException
     */
    public static function getProductSupplierReference($idProduct, $idProductAttribute, $idSupplier)
    {
        return Db::readOnly()->getValue(
            (new DbQuery())
                ->select('ps.`product_supplier_reference`')
                ->from('product_supplier', 'ps')
                ->where('ps.`id_product` = '.(int) $idProduct)
                ->where('ps.`id_product_attribute` = '.(int) $idProductAttribute)
                ->where('ps.`id_supplier` = '.(int) $idSupplier)
        );
    }

    /**
     * For a given product and supplier, gets the product supplier unit price
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idSupplier
     * @param bool $withCurrency Optional
     *
     * @return int|array
     *
     * @throws PrestaShopException
     */
    public static function getProductSupplierPrice($idProduct, $idProductAttribute, $idSupplier, $withCurrency = false)
    {
        // build query
        $query = new DbQuery();
        $query->select('ps.product_supplier_price_te');
        if ($withCurrency) {
            $query->select('ps.id_currency');
        }
        $query->from('product_supplier', 'ps');
        $query->where(
            'ps.id_product = '.(int) $idProduct.'
			AND ps.id_product_attribute = '.(int) $idProductAttribute.'
			AND ps.id_supplier = '.(int) $idSupplier
        );

        if (!$withCurrency) {
            return (int) Db::readOnly()->getValue($query);
        }

        $res = Db::readOnly()->getArray($query);
        if (isset($res[0])) {
            return $res[0];
        }

        return $res;
    }

    /**
     * For a given product and supplier, gets corresponding ProductSupplier ID
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idSupplier
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function getIdByProductAndSupplier($idProduct, $idProductAttribute, $idSupplier)
    {
        return (int) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('ps.id_product_supplier')
                ->from('product_supplier', 'ps')
                ->where('ps.id_product = '.(int) $idProduct)
                ->where('ps.id_product_attribute = '.(int) $idProductAttribute)
                ->where('ps.id_supplier = '.(int) $idSupplier)
        );
    }

    /**
     * For a given Supplier, Product, returns the purchased price
     *
     * @param int $idSupplier
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param bool $convertedPrice Whether price should be converted to the current currency.
     *
     * @return float|false Price, rounded to _TB_PRICE_DATABASE_PRECISION_, or false on failure.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getProductPrice($idSupplier, $idProduct, $idProductAttribute = 0, $convertedPrice = false)
    {
        if (is_null($idSupplier) || is_null($idProduct)) {
            return false;
        }

        $row = Db::readOnly()->getRow(
            (new DbQuery())
                ->select('product_supplier_price_te as price_te, id_currency')
                ->from('product_supplier')
                ->where('id_product = '.(int) $idProduct.' AND id_product_attribute = '.(int) $idProductAttribute)
                ->where('id_supplier = '.(int) $idSupplier)
        );
        if ($row && isset($row['price_te'])) {
            if ($convertedPrice) {
                return Tools::convertPrice($row['price_te'], $row['id_currency'], false);
            }

            return $row['price_te'];
        }

        return false;
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete()
    {
        $res = parent::delete();

        if ($res && $this->id_product_attribute == 0) {
            $items = ProductSupplier::getSupplierCollection($this->id_product, false);
            foreach ($items as $item) {
                /** @var ProductSupplier $item */
                if ($item->id_product_attribute > 0) {
                    $item->delete();
                }
            }
        }

        return $res;
    }

    /**
     * For a given product, retrieves its suppliers
     *
     * @param int $idProduct
     * @param bool $groupBySupplier
     *
     * @return PrestaShopCollection Collection of ProductSupplier
     *
     * @throws PrestaShopException
     */
    public static function getSupplierCollection($idProduct, $groupBySupplier = true)
    {
        $suppliers = new PrestaShopCollection('ProductSupplier');
        $suppliers->where('id_product', '=', (int) $idProduct);

        if ($groupBySupplier) {
            $suppliers->groupBy('id_supplier');
        }

        return $suppliers;
    }
}
