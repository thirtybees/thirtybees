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
 * Class ProductSaleCore
 *
 * @since 1.0.0
 */
class ProductSaleCore
{
    /**
     * Fill the `product_sale` SQL table with data from `order_detail`
     *
     * @return bool True on success
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function fillProductSales()
    {
        $sql = 'REPLACE INTO '._DB_PREFIX_.'product_sale
				(`id_product`, `quantity`, `sale_nbr`, `date_upd`)
				SELECT od.product_id, SUM(od.product_quantity), COUNT(od.product_id), NOW()
							FROM '._DB_PREFIX_.'order_detail od GROUP BY od.product_id';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Get number of actives products sold
     *
     * @return int number of actives products listed in product_sales
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getNbSales()
    {
        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(ps.`id_product`) AS `nb`')
                ->from('product_sale', 'ps')
                ->leftJoin('product', 'p', 'p.`id_product` = ps.`id_product`')
                ->join(Shop::addSqlAssociation('product', 'p'))
                ->where('product_shop.`active` = 1')
        );
    }

    /**
     * Get required informations on best sales products
     *
     * @param int         $idLang     Language id
     * @param int         $pageNumber Start from (optional)
     * @param int         $nbProducts Number of products to return (optional)
     * @param string|null $orderBy
     * @param string|null $orderWay
     *
     * @return false| array from Product::getProductProperties
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getBestSales($idLang, $pageNumber = 0, $nbProducts = 10, $orderBy = null, $orderWay = null)
    {
        $context = Context::getContext();
        if ($pageNumber < 0) {
            $pageNumber = 0;
        }
        if ($nbProducts < 1) {
            $nbProducts = 10;
        }
        $finalOrderBy = $orderBy;
        $orderTable = '';

        if (is_null($orderBy)) {
            $orderBy = 'quantity';
            $orderTable = 'ps';
        }

        if ($orderBy == 'date_add' || $orderBy == 'date_upd') {
            $orderTable = 'product_shop';
        }

        if (is_null($orderWay) || $orderBy == 'sales') {
            $orderWay = 'DESC';
        }

        $interval = Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20;

        // no group by needed : there's only one attribute with default_on=1 for a given id_product + shop
        // same for image with cover=1
        $sql = (new DbQuery())
            ->select('p.*, product_shop.*, stock.`out_of_stock`, IFNULL(stock.quantity, 0) as quantity')
            ->select(Combination::isFeatureActive() ? 'product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute' : '')
            ->select('pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`')
            ->select('pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`')
            ->select('m.`name` AS manufacturer_name, p.`id_manufacturer` as id_manufacturer')
            ->select('image_shop.`id_image` id_image, il.`legend`')
            ->select('ps.`quantity` AS sales, t.`rate`, pl.`meta_keywords`, pl.`meta_title`, pl.`meta_description`')
            ->select('DATEDIFF(p.`date_add`, DATE_SUB("'.date('Y-m-d').' 00:00:00"')
            ->select('INTERVAL '.(int) $interval.' DAY)) > 0 AS new')
            ->from('product_sale', 'ps')
            ->leftJoin('product', 'p', 'ps.`id_product` = p.`id_product`')
            ->join(Shop::addSqlAssociation('product', 'p', false))
            ->join(Combination::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $context->shop->id.')' : '')
            ->leftJoin('product_lang', 'pl', 'p.`id_product` = pl.`id_product`')
            ->leftJoin('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.`cover` = 1 AND image_shop.`id_shop` = '.(int) $context->shop->id)
            ->leftJoin('image_lang', 'il', 'image_shop.`id_image` = il.`id_image`')
            ->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')
            ->leftJoin('tax_rule', 'tr', 'product_shop.`id_tax_rules_group` = tr.`id_tax_rules_group` AND tr.`id_country` = '.(int) $context->country->id.' AND tr.`id_state` = 0')
            ->leftJoin('tax', 't', 't.`id_tax` = tr.`id_tax` '.Product::sqlStock('p', 0))
            ->where('pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl'))
            ->where('il.`id_lang` = '.(int) $idLang)
            ->where('product_shop.`active` = 1')
            ->where('p.`visibility` != \'none\'')
            ->where('EXISTS(SELECT 1 FROM `'._DB_PREFIX_.'category_product` cp JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` '.(count(FrontController::getCurrentCustomerGroups()) ? 'IN ('.implode(',', FrontController::getCurrentCustomerGroups()).')' : '= 1').') WHERE cp.`id_product` = p.`id_product`)');

        if ($finalOrderBy != 'price') {
            $sql->orderBy((!empty($orderTable) ? '`'.pSQL($orderTable).'`.' : '').'`'.pSQL($orderBy).'` '.pSQL($orderWay));
            $sql->limit((int) $nbProducts, (int) ($pageNumber * $nbProducts));
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if ($finalOrderBy == 'price') {
            Tools::orderbyPrice($result, $orderWay);
        }
        if (!$result) {
            return false;
        }

        return Product::getProductsProperties($idLang, $result);
    }

    /**
     * Get required informations on best sales products
     *
     * @param int $idLang     Language id
     * @param int $pageNumber Start from (optional)
     * @param int $nbProducts Number of products to return (optional)
     *
     * @return array keys : id_product, link_rewrite, name, id_image, legend, sales, ean13, upc, link
     */
    public static function getBestSalesLight($idLang, $pageNumber = 0, $nbProducts = 10, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }
        if ($pageNumber < 0) {
            $pageNumber = 0;
        }
        if ($nbProducts < 1) {
            $nbProducts = 10;
        }

        // no group by needed : there's only one attribute with default_on=1 for a given id_product + shop
        // same for image with cover=1
        $sql = '
		SELECT
			p.id_product, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute, pl.`link_rewrite`, pl.`name`, pl.`description_short`, product_shop.`id_category_default`,
			image_shop.`id_image` id_image, il.`legend`,
			ps.`quantity` AS sales, p.`ean13`, p.`upc`, cl.`link_rewrite` AS category, p.show_price, p.available_for_order, IFNULL(stock.quantity, 0) as quantity, p.customizable,
			IFNULL(pa.minimal_quantity, p.minimal_quantity) as minimal_quantity, stock.out_of_stock,
			product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'" as new,
			product_shop.`on_sale`, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity
		FROM `'._DB_PREFIX_.'product_sale` ps
		LEFT JOIN `'._DB_PREFIX_.'product` p ON ps.`id_product` = p.`id_product`
		'.Shop::addSqlAssociation('product', 'p').'
		LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop
			ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $context->shop->id.')
		LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (product_attribute_shop.id_product_attribute=pa.id_product_attribute)
		LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
			ON p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').'
		LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
			ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $context->shop->id.')
		LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $idLang.')
		LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
			ON cl.`id_category` = product_shop.`id_category_default`
			AND cl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('cl').Product::sqlStock('p', 0);

        $sql .= '
		WHERE product_shop.`active` = 1
		AND p.`visibility` != \'none\'';

        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sql .= ' AND EXISTS(SELECT 1 FROM `'._DB_PREFIX_.'category_product` cp
				JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').')
				WHERE cp.`id_product` = p.`id_product`)';
        }

        $sql .= '
		ORDER BY ps.quantity DESC
		LIMIT '.(int) ($pageNumber * $nbProducts).', '.(int) $nbProducts;

        if (!$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql)) {
            return false;
        }

        return Product::getProductsProperties($idLang, $result);
    }

    /**
     * @param int $idProduct
     * @param int $qty
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function addProductSale($idProduct, $qty = 1)
    {
        return Db::getInstance()->execute(
            '
			INSERT INTO '._DB_PREFIX_.'product_sale
			(`id_product`, `quantity`, `sale_nbr`, `date_upd`)
			VALUES ('.(int) $idProduct.', '.(int) $qty.', 1, NOW())
			ON DUPLICATE KEY UPDATE `quantity` = `quantity` + '.(int) $qty.', `sale_nbr` = `sale_nbr` + 1, `date_upd` = NOW()'
        );
    }

    /**
     * @param int $idProduct
     * @param int $qty
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function removeProductSale($idProduct, $qty = 1)
    {
        $totalSales = ProductSale::getNbrSales($idProduct);
        if ($totalSales > 1) {
            return Db::getInstance()->execute(
                '
				UPDATE '._DB_PREFIX_.'product_sale
				SET `quantity` = CAST(`quantity` AS SIGNED) - '.(int) $qty.', `sale_nbr` = CAST(`sale_nbr` AS SIGNED) - 1, `date_upd` = NOW()
				WHERE `id_product` = '.(int) $idProduct
            );
        } elseif ($totalSales == 1) {
            return Db::getInstance()->delete('product_sale', 'id_product = '.(int) $idProduct);
        }

        return true;
    }

    /**
     * @param int $idProduct
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNbrSales($idProduct)
    {
        $result = Db::getInstance()->getRow('SELECT `sale_nbr` FROM '._DB_PREFIX_.'product_sale WHERE `id_product` = '.(int) $idProduct);
        if (!$result || empty($result) || !array_key_exists('sale_nbr', $result)) {
            return -1;
        }

        return (int) $result['sale_nbr'];
    }
}
