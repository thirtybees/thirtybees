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
 * Class PackCore
 */
class PackCore extends Product
{
    protected static $cachePackItems = [];
    protected static $cacheIsPack = [];
    protected static $cacheIsPacked = [];

    /**
     * @param int $idProduct
     *
     * @return float|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public static function noPackPrice($idProduct)
    {
        $sum = 0;
        $priceDisplayMethod = !static::$_taxCalculationMethod;
        $items = Pack::getItems($idProduct, Configuration::get('PS_LANG_DEFAULT'));
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
     * @return array|mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getItems($idProduct, $idLang)
    {
        if (!Pack::isFeatureActive()) {
            return [];
        }

        if (array_key_exists($idProduct, static::$cachePackItems)) {
            return static::$cachePackItems[$idProduct];
        }
        $result = Db::getInstance()->executeS('SELECT id_product_item, id_product_attribute_item, quantity FROM `'._DB_PREFIX_.'pack` where id_product_pack = '.(int) $idProduct);
        $arrayResult = [];
        foreach ($result as $row) {
            $p = new Product($row['id_product_item'], false, $idLang);
            $p->loadStockData();
            $p->pack_quantity = $row['quantity'];
            $p->id_pack_product_attribute = (isset($row['id_product_attribute_item']) && $row['id_product_attribute_item'] ? $row['id_product_attribute_item'] : 0);
            if (isset($row['id_product_attribute_item']) && $row['id_product_attribute_item']) {
                $sql = 'SELECT agl.`name` AS group_name, al.`name` AS attribute_name
					FROM `'._DB_PREFIX_.'product_attribute` pa
					'.Shop::addSqlAssociation('product_attribute', 'pa').'
					LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
					LEFT JOIN `'._DB_PREFIX_.'attribute` a ON a.`id_attribute` = pac.`id_attribute`
					LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
					LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) Context::getContext()->language->id.')
					LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) Context::getContext()->language->id.')
					WHERE pa.`id_product_attribute` = '.$row['id_product_attribute_item'].'
					GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
					ORDER BY pa.`id_product_attribute`';

                $combinations = Db::getInstance()->executeS($sql);
                foreach ($combinations as $k => $combination) {
                    $p->name .= ' '.$combination['group_name'].'-'.$combination['attribute_name'];
                }
            }
            $arrayResult[] = $p;
        }
        static::$cachePackItems[$idProduct] = $arrayResult;

        return static::$cachePackItems[$idProduct];
    }

    /**
     * This method is allow to know if a feature is used or active
     *
     * @since 1.5.0.1
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
        $items = Pack::getItems($idProduct, Configuration::get('PS_LANG_DEFAULT'));
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
        if (!Pack::isFeatureActive()) {
            return true;
        }

        $items = Pack::getItems((int) $idProduct, Configuration::get('PS_LANG_DEFAULT'));

        foreach ($items as $item) {
            /** @var Product $item */
            // Updated for 1.5.0
            if (Product::getQuantity($item->id) < $item->pack_quantity && !$item->isAvailableWhenOutOfStock((int) $item->out_of_stock)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int  $idProduct
     * @param int  $idLang
     * @param bool $full
     *
     * @return array|false|null|PDOStatement
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getItemTable($idProduct, $idLang, $full = false)
    {
        if (!Pack::isFeatureActive()) {
            return [];
        }

        $context = Context::getContext();

        $sql = 'SELECT p.*, product_shop.*, pl.*, image_shop.`id_image` id_image, il.`legend`, cl.`name` AS category_default, a.quantity AS pack_quantity, product_shop.`id_category_default`, a.id_product_pack, a.id_product_attribute_item
				FROM `'._DB_PREFIX_.'pack` a
				LEFT JOIN `'._DB_PREFIX_.'product` p ON p.id_product = a.id_product_item
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON p.id_product = pl.id_product
					AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').'
				LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $context->shop->id.')
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $idLang.')
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
					ON product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('cl').'
				WHERE product_shop.`id_shop` = '.(int) $context->shop->id.'
				AND a.`id_product_pack` = '.(int) $idProduct.'
				GROUP BY a.`id_product_item`, a.`id_product_attribute_item`';

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        foreach ($result as &$line) {
            if (Combination::isFeatureActive() && isset($line['id_product_attribute_item']) && $line['id_product_attribute_item']) {
                $line['cache_default_attribute'] = $line['id_product_attribute'] = $line['id_product_attribute_item'];

                $sql = 'SELECT agl.`name` AS group_name, al.`name` AS attribute_name,  pai.`id_image` AS id_product_attribute_image
				FROM `'._DB_PREFIX_.'product_attribute` pa
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON pac.`id_product_attribute` = '.$line['id_product_attribute_item'].'
				LEFT JOIN `'._DB_PREFIX_.'attribute` a ON a.`id_attribute` = pac.`id_attribute`
				LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
				LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) Context::getContext()->language->id.')
				LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) Context::getContext()->language->id.')
				LEFT JOIN `'._DB_PREFIX_.'product_attribute_image` pai ON ('.$line['id_product_attribute_item'].' = pai.`id_product_attribute`)
				WHERE pa.`id_product` = '.(int) $line['id_product'].' AND pa.`id_product_attribute` = '.$line['id_product_attribute_item'].'
				GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
				ORDER BY pa.`id_product_attribute`';

                $attrName = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

                if (isset($attrName[0]['id_product_attribute_image']) && $attrName[0]['id_product_attribute_image']) {
                    $line['id_image'] = $attrName[0]['id_product_attribute_image'];
                }
                $line['name'] .= "\n";
                foreach ($attrName as $value) {
                    $line['name'] .= ' '.$value['group_name'].'-'.$value['attribute_name'];
                }
            }
            $line = Product::getTaxesInformations($line);
        }

        if (!$full) {
            return $result;
        }

        $arrayResult = [];
        foreach ($result as $prow) {
            if (!Pack::isPack($prow['id_product'])) {
                $prow['id_product_attribute'] = (int) $prow['id_product_attribute_item'];
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
        if (!Pack::isFeatureActive()) {
            return false;
        }

        if (!$idProduct) {
            return false;
        }

        if (!array_key_exists($idProduct, static::$cacheIsPack)) {
            $result = Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.'pack` WHERE id_product_pack = '.(int) $idProduct);
            static::$cacheIsPack[$idProduct] = ($result > 0);
        }

        return static::$cacheIsPack[$idProduct];
    }

    /**
     * @param int  $idProduct
     * @param int  $idLang
     * @param bool $full
     * @param null $limit
     *
     * @return array|false|null|PDOStatement
     * @throws PrestaShopException
     */
    public static function getPacksTable($idProduct, $idLang, $full = false, $limit = null)
    {
        if (!Pack::isFeatureActive()) {
            return [];
        }

        $packs = Db::getInstance()->getValue(
            '
		SELECT GROUP_CONCAT(a.`id_product_pack`)
		FROM `'._DB_PREFIX_.'pack` a
		WHERE a.`id_product_item` = '.(int) $idProduct
        );

        if (!(int) $packs) {
            return [];
        }

        $context = Context::getContext();

        $sql = '
		SELECT p.*, product_shop.*, pl.*, image_shop.`id_image` id_image, il.`legend`, IFNULL(product_attribute_shop.id_product_attribute, 0) id_product_attribute
		FROM `'._DB_PREFIX_.'product` p
		NATURAL LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
		'.Shop::addSqlAssociation('product', 'p').'
		LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop
	   		ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $context->shop->id.')
		LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
			ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $context->shop->id.')
		LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $idLang.')
		WHERE pl.`id_lang` = '.(int) $idLang.'
			'.Shop::addSqlRestrictionOnLang('pl').'
			AND p.`id_product` IN ('.$packs.')
		GROUP BY p.id_product';
        if ($limit) {
            $sql .= ' LIMIT '.(int) $limit;
        }
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!$full) {
            return $result;
        }

        $arrayResult = [];
        foreach ($result as $row) {
            if (!Pack::isPacked($row['id_product'])) {
                $arrayResult[] = Product::getProductProperties($idLang, $row);
            }
        }

        return $arrayResult;
    }

    /**
     * Is product in a pack?
     * If $id_product_attribute specified, then will restrict search on the given combination,
     * else this method will match a product if at least one of all its combination is in a pack.
     *
     * @param int      $idProduct
     * @param bool|int $idProductAttribute Optional combination of the product
     *
     * @return bool
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public static function isPacked($idProduct, $idProductAttribute = false)
    {

        if (!Pack::isFeatureActive()) {
            return false;
        }
        if ($idProductAttribute === false) {
            $cacheKey = $idProduct.'-0';
            if (!array_key_exists($cacheKey, static::$cacheIsPacked)) {
                $result = Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.'pack` WHERE id_product_item = '.(int) $idProduct);
                static::$cacheIsPacked[$cacheKey] = ($result > 0);
            }

            return static::$cacheIsPacked[$cacheKey];
        } else {
            $cacheKey = $idProduct.'-'.$idProductAttribute;
            if (!array_key_exists($cacheKey, static::$cacheIsPacked)) {
                $result = Db::getInstance()->getValue(
                    'SELECT COUNT(*) FROM `'._DB_PREFIX_.'pack` WHERE id_product_item = '.((int) $idProduct).' AND
					id_product_attribute_item = '.((int) $idProductAttribute)
                );
                static::$cacheIsPacked[$cacheKey] = ($result > 0);
            }

            return static::$cacheIsPacked[$cacheKey];
        }
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public static function deleteItems($idProduct)
    {
        return Db::getInstance()->update('product', ['cache_is_pack' => 0], 'id_product = '.(int) $idProduct) &&
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'pack` WHERE `id_product_pack` = '.(int) $idProduct) &&
            Configuration::updateGlobalValue('PS_PACK_FEATURE_ACTIVE', Pack::isCurrentlyUsed());
    }

    /**
     * This method is allow to know if a Pack entity is currently used
     *
     * @since 1.5.0
     *
     * @param string $table
     * @param bool   $hasActiveColumn
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function isCurrentlyUsed($table = null, $hasActiveColumn = false)
    {
        // We dont't use the parent method because the identifier isn't id_pack
        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
			SELECT `id_product_pack`
			FROM `'._DB_PREFIX_.'pack`
		'
        );
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
     * @throws PrestaShopException
     */
    public static function addItem($idProduct, $idItem, $qty, $idAttributeItem = 0)
    {
        $idAttributeItem = (int) $idAttributeItem ? (int) $idAttributeItem : Product::getDefaultAttribute((int) $idItem);

        return Db::getInstance()->update('product', ['cache_is_pack' => 1], 'id_product = '.(int) $idProduct) &&
            Db::getInstance()->insert(
                'pack',
                [
                    'id_product_pack'           => (int) $idProduct,
                    'id_product_item'           => (int) $idItem,
                    'id_product_attribute_item' => (int) $idAttributeItem,
                    'quantity'                  => (int) $qty,
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
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public static function duplicate($idProductOld, $idProductNew)
    {
        Db::getInstance()->execute(
            'INSERT INTO `'._DB_PREFIX_.'pack` (`id_product_pack`, `id_product_item`, `id_product_attribute_item`, `quantity`)
		(SELECT '.(int) $idProductNew.', `id_product_item`, `id_product_attribute_item`, `quantity` FROM `'._DB_PREFIX_.'pack` WHERE `id_product_pack` = '.(int) $idProductOld.')'
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
     */
    public static function usesAdvancedStockManagement($idProduct)
    {
        if (!Pack::isPack($idProduct)) {
            return false;
        }

        $products = Pack::getItems($idProduct, Configuration::get('PS_LANG_DEFAULT'));
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
     */
    public static function allUsesAdvancedStockManagement($idProduct)
    {
        if (!Pack::isPack($idProduct)) {
            return false;
        }

        $products = Pack::getItems($idProduct, Configuration::get('PS_LANG_DEFAULT'));
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
     * Returns Packs that conatins the given product in the right declinaison.
     *
     * @param integer $idItem          Product item id that could be contained in a|many pack(s)
     * @param integer $idAttributeItem The declinaison of the product
     * @param integer $idLang
     *
     * @return array[Product] Packs that contains the given product
     */
    public static function getPacksContainingItem($idItem, $idAttributeItem, $idLang)
    {
        if (!Pack::isFeatureActive() || !$idItem) {
            return [];
        }

        $query = 'SELECT `id_product_pack`, `quantity` FROM `'._DB_PREFIX_.'pack`
			WHERE `id_product_item` = '.((int) $idItem);
        if (Combination::isFeatureActive()) {
            $query .= ' AND `id_product_attribute_item` = '.((int) $idAttributeItem);
        }
        $result = Db::getInstance()->executeS($query);
        $arrayResult = [];
        foreach ($result as $row) {
            $p = new Product($row['id_product_pack'], true, $idLang);
            $p->loadStockData();
            $p->pack_item_quantity = $row['quantity']; // Specific need from StockAvailable::updateQuantity()
            $arrayResult[] = $p;
        }

        return $arrayResult;
    }
}
