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
 * Class PackCore
 */
class PackCore extends Product
{
    const STOCK_TYPE_DECREMENT_PACK = 0;
    const STOCK_TYPE_DECREMENT_PRODUCTS = 1;
    const STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS = 2;
    const STOCK_TYPE_DECREMENT_GLOBAL_SETTINGS = 3;

    const STOCK_TYPE_ITEMS = 1;

    /**
     * @param int $idProduct
     *
     * @return float|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function noPackPrice($idProduct)
    {
        $sum = 0;
        $priceDisplayMethod = !static::$_taxCalculationMethod;
        $items = static::getItems($idProduct, Configuration::get('PS_LANG_DEFAULT'));
        foreach ($items as $item) {
            /** @var Product $item */
            $sum += $item->getPrice($priceDisplayMethod, ($item->id_pack_product_attribute ? $item->id_pack_product_attribute : null)) * $item->pack_quantity;
        }

        return $sum;
    }

    /**
     * @param int $idProduct
     * @param int $idLang
     *
     * @return Product[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getItems($idProduct, $idLang)
    {
        if (!static::isFeatureActive()) {
            return [];
        }

        $idProduct = (int)$idProduct;
        $idLang = (int)$idLang;

        $cacheKey = "Pack::getItems($idProduct,$idLang)";
        if (!Cache::isStored($cacheKey)) {
            Cache::store($cacheKey, static::retrieveItems($idProduct, $idLang));
        }
        return Cache::retrieve($cacheKey);
    }

    /**
     * @param int $idProduct
     * @param int $idLang
     * @return Product[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function retrieveItems($idProduct, $idLang)
    {
        $idProduct = (int)$idProduct;
        $idLang = (int)$idLang;
        $arrayResult = [];
        foreach (static::getPackContent($idProduct) as $row) {
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
		    $p->reference = $combination['attribute_reference'];
                }
            }
            $arrayResult[] = $p;
        }
        return $arrayResult;
    }

    /**
     * Returns information about pack items.
     *
     * @param int $idProduct
     * @return array
     * @throws PrestaShopException
     */
    public static function getPackContent($idProduct)
    {
        $idProduct = (int)$idProduct;
        if (!$idProduct || !static::isFeatureActive()) {
            return [];
        }

        $cacheKey = "Pack::getPackContent($idProduct)";
        if (!Cache::isStored($cacheKey)) {
            Cache::store($cacheKey, static::retrievePackContent($idProduct));
        }
        return Cache::retrieve($cacheKey);
    }

    /**
     * Retrieves information about pack items from database
     *
     * @param int $idProduct
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function retrievePackContent($idProduct)
    {
        $idProduct = (int)$idProduct;
        $content = [];
        $sql = (new DbQuery())
            ->select('id_product_item AS id_product')
            ->select('id_product_attribute_item AS id_product_attribute')
            ->select('quantity')
            ->from('pack')
            ->where('id_product_pack = ' . $idProduct)
            ->orderBy('id_product_item, id_product_attribute_item');
        $result = Db::readOnly()->getArray($sql);
        foreach ($result as $row) {
            $content[] = [
                'id_product' => (int)$row['id_product'],
                'id_product_attribute' => (int)$row['id_product_attribute'],
                'quantity' => (int)$row['quantity']
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

    /**
     * @param int $idProduct
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function noPackWholesalePrice($idProduct)
    {
        $sum = 0;
        $items = static::getItems($idProduct, Configuration::get('PS_LANG_DEFAULT'));
        foreach ($items as $item) {
            $sum += $item->wholesale_price * $item->pack_quantity;
        }

        return $sum;
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function isInStock($idProduct)
    {
        $items = static::getItems((int)$idProduct, Configuration::get('PS_LANG_DEFAULT'));
        foreach ($items as $item) {
            if (Product::getQuantity($item->id) < $item->pack_quantity && !$item->isAvailableWhenOutOfStock((int)$item->out_of_stock)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param int $idProduct
     * @param int $idLang
     * @param bool $full
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getItemTable($idProduct, $idLang, $full = false)
    {
        $idProduct = (int)$idProduct;
        if (!$idProduct || !static::isFeatureActive()) {
            return [];
        }

        $context = Context::getContext();

        $sql = 'SELECT p.*, product_shop.*, pl.*, image_shop.`id_image` id_image, il.`legend`, cl.`name` AS category_default, a.quantity AS pack_quantity, product_shop.`id_category_default`, a.id_product_pack, a.id_product_attribute_item
				FROM `' . _DB_PREFIX_ . 'pack` a
				LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.id_product = a.id_product_item
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
					ON p.id_product = pl.id_product
					AND pl.`id_lang` = ' . (int)$idLang . Shop::addSqlRestrictionOnLang('pl') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int)$context->shop->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int)$idLang . ')
				' . Shop::addSqlAssociation('product', 'p') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
					ON product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = ' . (int)$idLang . Shop::addSqlRestrictionOnLang('cl') . '
				WHERE product_shop.`id_shop` = ' . (int)$context->shop->id . '
				AND a.`id_product_pack` = ' . $idProduct . '
				AND product_shop.active
				AND product_shop.visibility IN ("both", "catalog")
				GROUP BY a.`id_product_item`, a.`id_product_attribute_item`';

        $connection = Db::readOnly();
        $result = $connection->getArray($sql);

        foreach ($result as &$line) {
            if (Combination::isFeatureActive() && isset($line['id_product_attribute_item']) && $line['id_product_attribute_item']) {
                $line['cache_default_attribute'] = $line['id_product_attribute'] = $line['id_product_attribute_item'];

                $sql = 'SELECT agl.`name` AS group_name, al.`name` AS attribute_name,  pai.`id_image` AS id_product_attribute_image
				FROM `' . _DB_PREFIX_ . 'product_attribute` pa
				' . Shop::addSqlAssociation('product_attribute', 'pa') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON pac.`id_product_attribute` = ' . $line['id_product_attribute_item'] . '
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int)Context::getContext()->language->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int)Context::getContext()->language->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_image` pai ON (' . $line['id_product_attribute_item'] . ' = pai.`id_product_attribute`)
				WHERE pa.`id_product` = ' . (int)$line['id_product'] . ' AND pa.`id_product_attribute` = ' . $line['id_product_attribute_item'] . '
				GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
				ORDER BY pa.`id_product_attribute`';

                $attrName = $connection->getArray($sql);

                if (isset($attrName[0]['id_product_attribute_image']) && $attrName[0]['id_product_attribute_image']) {
                    $line['id_image'] = $attrName[0]['id_product_attribute_image'];
                }
                $line['name'] .= "\n";
                foreach ($attrName as $value) {
                    $line['name'] .= ' ' . $value['group_name'] . '-' . $value['attribute_name'];
                }
            }
            $line = Product::getTaxesInformations($line);
        }

        if (!$full) {
            return $result;
        }

        $arrayResult = [];
        foreach ($result as $prow) {
            if (!static::isPack($prow['id_product'])) {
                $prow['id_product_attribute'] = (int)$prow['id_product_attribute_item'];
                $arrayResult[] = Product::getProductProperties($idLang, $prow);
            }
        }

        return $arrayResult;
    }

    /**
     * Is product a pack?
     *
     * @param int $idProduct
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function isPack($idProduct)
    {
        return (bool)static::getPackContent($idProduct);
    }

    /**
     * @param int $idProduct
     * @param int $idLang
     * @param bool $full
     * @param int|null $limit
     *
     * @return array
     * @throws PrestaShopException
     */
    public static function getPacksTable($idProduct, $idLang, $full = false, $limit = null)
    {
        if (!static::isFeatureActive()) {
            return [];
        }

        $connection = Db::readOnly();
        $packs = $connection->getValue(
            '
		SELECT GROUP_CONCAT(a.`id_product_pack`)
		FROM `' . _DB_PREFIX_ . 'pack` a
		WHERE a.`id_product_item` = ' . (int)$idProduct
        );

        if (!(int)$packs) {
            return [];
        }

        $context = Context::getContext();

        $sql = '
		SELECT p.*, product_shop.*, pl.*, image_shop.`id_image` id_image, il.`legend`, IFNULL(product_attribute_shop.id_product_attribute, 0) id_product_attribute
		FROM `' . _DB_PREFIX_ . 'product` p
		NATURAL LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
		' . Shop::addSqlAssociation('product', 'p') . '
		LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
	   		ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int)$context->shop->id . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
			ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int)$context->shop->id . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int)$idLang . ')
		WHERE pl.`id_lang` = ' . (int)$idLang . '
			' . Shop::addSqlRestrictionOnLang('pl') . '
			AND p.`id_product` IN (' . $packs . ')
		GROUP BY p.id_product';
        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }
        $result = $connection->getArray($sql);
        if (!$full) {
            return $result;
        }

        $arrayResult = [];
        foreach ($result as $row) {
            if (!static::isPacked($row['id_product'])) {
                $arrayResult[] = Product::getProductProperties($idLang, $row);
            }
        }

        return $arrayResult;
    }

    /**
     * Is product in a pack?
     *
     * If $id_product_attribute specified, then will restrict search on the given combination,
     * else this method will match a product if at least one of all its combination is in a pack.
     *
     * @param int $idProduct
     * @param bool|int $idProductAttribute Optional combination of the product
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function isPacked($idProduct, $idProductAttribute = false)
    {
        $idProduct = (int)$idProduct;
        if (!$idProduct || !static::isFeatureActive()) {
            return false;
        }
        $idProductAttribute = (int)$idProductAttribute;
        if ($idProductAttribute) {
            return (bool)static::getItemQuantitiesInPacks($idProduct, $idProductAttribute);
        }

        $cacheKey = "Pack::isPacked($idProduct)";
        if (!Cache::isStored($cacheKey)) {
            Cache::store($cacheKey, static::resolveIsPacked($idProduct));
        }
        return (bool)Cache::retrieve($cacheKey);
    }

    /**
     * Is product in a pack
     *
     * @param int $idProduct
     * @return boolean
     * @throws PrestaShopException
     */
    protected static function resolveIsPacked($idProduct)
    {
        $idProduct = (int)$idProduct;
        $sql = (new DbQuery())
            ->select("COUNT(1)")
            ->from('pack')
            ->where('id_product_item = ' . $idProduct);
        return (bool)Db::readOnly()->getValue($sql);
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function deleteItems($idProduct)
    {
        $idProduct = (int)$idProduct;
        $conn = Db::getInstance();
        return (
            $conn->update('product', ['cache_is_pack' => 0], 'id_product = ' . $idProduct) &&
            $conn->delete('pack', 'id_product_pack = ' . $idProduct) &&
            Configuration::updateGlobalValue('PS_PACK_FEATURE_ACTIVE', static::isCurrentlyUsed())
        );
    }

    /**
     * This method returns true, if at least one pack is defined
     *
     * @param string $table
     * @param bool $hasActiveColumn
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function isCurrentlyUsed($table = null, $hasActiveColumn = false)
    {
        $sql = (new DbQuery())
            ->select(1)
            ->from('pack');
        return (bool)Db::readOnly()->getValue($sql);
    }

    /**
     * Add an item to the pack
     *
     * @param int $idProduct
     * @param int $idItem
     * @param int $qty
     * @param int $idAttributeItem
     *
     * @return bool true if everything was fine
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function addItem($idProduct, $idItem, $qty, $idAttributeItem = 0)
    {
        $idAttributeItem = (int)$idAttributeItem ? (int)$idAttributeItem : Product::getDefaultAttribute((int)$idItem);

        $conn = Db::getInstance();
        return $conn->update('product', ['cache_is_pack' => 1], 'id_product = ' . (int)$idProduct) &&
            $conn->insert(
                'pack',
                [
                    'id_product_pack' => (int)$idProduct,
                    'id_product_item' => (int)$idItem,
                    'id_product_attribute_item' => (int)$idAttributeItem,
                    'quantity' => (int)$qty,
                ]
            )
            && Configuration::updateGlobalValue('PS_PACK_FEATURE_ACTIVE', '1');
    }

    /**
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function duplicate($idProductOld, $idProductNew)
    {
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'pack` (`id_product_pack`, `id_product_item`, `id_product_attribute_item`, `quantity`)
		(SELECT ' . (int)$idProductNew . ', `id_product_item`, `id_product_attribute_item`, `quantity` FROM `' . _DB_PREFIX_ . 'pack` WHERE `id_product_pack` = ' . (int)$idProductOld . ')'
        );

        // If return query result, a non-pack product will return false
        return true;
    }

    /**
     * For a given pack, tells if it has at least one product using the advanced stock management
     *
     * @param int $idProduct id_pack
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function usesAdvancedStockManagement($idProduct)
    {
        $products = static::getItems($idProduct, Configuration::get('PS_LANG_DEFAULT'));
        foreach ($products as $product) {
            // if one product uses the advanced stock management
            if ($product->advanced_stock_management == 1) {
                return true;
            }
        }
        // not used
        return false;
    }

    /**
     * For a given pack, tells if all products using the advanced stock management
     *
     * @param int $idProduct id_pack
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function allUsesAdvancedStockManagement($idProduct)
    {
        if (!static::isPack($idProduct)) {
            return false;
        }

        $products = static::getItems($idProduct, Configuration::get('PS_LANG_DEFAULT'));
        foreach ($products as $product) {
            // if one product uses the advanced stock management
            if ($product->advanced_stock_management == 0) {
                return false;
            }
        }

        // not used
        return true;
    }

    /**
     * Returns Packs that contains the given product in the right declinaison.
     *
     * @param integer $idItem Product item id that could be contained in a|many pack(s)
     * @param integer $idAttributeItem The declinaison of the product
     * @param integer $idLang
     *
     * @return Product[] Packs that contains the given product
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getPacksContainingItem($idItem, $idAttributeItem, $idLang)
    {
        $arrayResult = [];
        foreach (static::getItemQuantitiesInPacks($idItem, $idAttributeItem) as $packId => $itemQuantity) {
            $pack = new Product($packId, true, $idLang);
            $pack->loadStockData();
            // Specific need from StockAvailable::updateQuantity()
            $pack->pack_item_quantity = $itemQuantity;
            $arrayResult[] = $pack;
        }
        return $arrayResult;
    }

    /**
     * Returns information about all packs $idItem is part of, and item quantity
     *
     * @param int $idItem
     * @param int $idAttributeItem
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getItemQuantitiesInPacks($idItem, $idAttributeItem)
    {
        $idItem = (int)$idItem;
        if (!$idItem || !static::isFeatureActive()) {
            return [];
        }
        $idAttributeItem = (int)$idAttributeItem;

        $cacheKey = "Pack::getItemQuantitiesInPacks($idItem,$idAttributeItem)";
        if (! Cache::isStored($cacheKey)) {
            Cache::store($cacheKey, static::resolveItemQuantitiesInPacks($idItem, $idAttributeItem));
        }
        return Cache::retrieve($cacheKey);
    }

    /**
     * Returns information about all packs $idItem is part of, and item quantity
     *
     * @param int $idItem
     * @param int $idAttributeItem
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function resolveItemQuantitiesInPacks($idItem, $idAttributeItem)
    {
        $idItem = (int)$idItem;
        $idAttributeItem = (int)$idAttributeItem;

        $query = (new DbQuery())
            ->select('p.id_product_pack')
            ->select('p.quantity')
            ->from('pack', 'p')
            ->innerJoin('product', 'prod', 'prod.id_product = p.id_product_pack')
            ->where("p.id_product_item = $idItem")
            ->where("p.id_product_attribute_item = $idAttributeItem");

        $result = Db::readOnly()->getArray($query);
        $ret = [];
        foreach ($result as $row) {
            $packId = (int)$row['id_product_pack'];
            $quantity = (int)$row['quantity'];
            $ret[$packId] = $quantity;
        }
        return $ret;
    }

    /**
     * Returns true, if $stockType value is one of the three allowed settings
     *   - STOCK_TYPE_DECREMENT_PACK,
     *   - STOCK_TYPE_DECREMENT_PRODUCTS
     *   - STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS
     * returns false for anything else, even STOCK_TYPE_DECREMENT_GLOBAL_SETTINGS
     *
     * @param int $stockType
     * @return boolean
     */
    public static function isValidStockType($stockType)
    {
        $stockType = (int)$stockType;
        return (
            ($stockType === static::STOCK_TYPE_DECREMENT_PACK) ||
            ($stockType === static::STOCK_TYPE_DECREMENT_PRODUCTS) ||
            ($stockType === static::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS)
        );
    }

    /**
     * Returns public configuration for pack quantity adjustment
     *
     * @return int
     */
    public static function getGlobalStockTypeSettings()
    {
        try {
            $stockType = (int)Configuration::get(Configuration::PACK_STOCK_TYPE);
            if (static::isValidStockType($stockType)) {
                return $stockType;
            }
        } catch (Exception $ignored) {
        }
        return static::STOCK_TYPE_DECREMENT_PACK;
    }

    /**
     * Returns ids of dynamic packs products
     *
     * @return int[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getDynamicPacks()
    {
        $sql = (new DbQuery())
            ->select('DISTINCT id_product')
            ->from('product_shop')
            ->where('pack_dynamic');
        $conn = Db::readOnly();
        $result = $conn->getArray($sql);
        return array_map('intval', array_column($result, 'id_product'));
    }
}
