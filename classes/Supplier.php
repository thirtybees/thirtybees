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
 * Class SupplierCore
 *
 * @since 1.0.0
 */
class SupplierCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * Return name from id
     *
     * @param int $id_supplier Supplier ID
     *
     * @return string name
     */
    protected static $cache_name = [];
    /** @var int supplier ID */
    public $id_supplier;
    /** @var string Name */
    public $name;
    /** @var string A short description for the discount */
    public $description;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var string Friendly URL */
    public $link_rewrite;
    /** @var string Meta title */
    public $meta_title;
    /** @var string Meta keywords */
    public $meta_keywords;
    /** @var string Meta description */
    public $meta_description;
    /** @var bool active */
    public $active;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'supplier',
        'primary'   => 'id_supplier',
        'multilang' => true,
        'fields'    => [
            'name'             => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'required' => true, 'size' => 64],
            'active'           => ['type' => self::TYPE_BOOL],
            'date_add'         => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'         => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],

            /* Lang fields */
            'description'      => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'meta_title'       => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
            'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'meta_keywords'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'link_rewrite' => ['sqlId' => 'link_rewrite'],
        ],
    ];

    /**
     * SupplierCore constructor.
     *
     * @param null $id
     * @param null $idLang
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id, $idLang);

        $this->link_rewrite = $this->getLink();
        $this->image_dir = _PS_SUPP_IMG_DIR_;
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLink()
    {
        return Tools::link_rewrite($this->name);
    }

    /**
     * Return suppliers
     *
     * @param bool $getNbProducts
     * @param int  $idLang
     * @param bool $active
     * @param bool $p
     * @param bool $n
     * @param bool $allGroups
     *
     * @return array Suppliers
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getSuppliers($getNbProducts = false, $idLang = 0, $active = true, $p = false, $n = false, $allGroups = false)
    {
        if (!$idLang) {
            $idLang = Configuration::get('PS_LANG_DEFAULT');
        }
        if (!Group::isFeatureActive()) {
            $allGroups = true;
        }

        $query = new DbQuery();
        $query->select('s.*, sl.`description`');
        $query->from('supplier', 's');
        $query->leftJoin('supplier_lang', 'sl', 's.`id_supplier` = sl.`id_supplier` AND sl.`id_lang` = '.(int) $idLang);
        $query->join(Shop::addSqlAssociation('supplier', 's'));
        if ($active) {
            $query->where('s.`active` = 1');
        }
        $query->orderBy(' s.`name` ASC');
        $query->limit($n, ($p - 1) * $n);
        $query->groupBy('s.id_supplier');

        $suppliers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($suppliers === false) {
            return false;
        }
        if ($getNbProducts) {
            $sqlGroups = '';
            if (!$allGroups) {
                $groups = FrontController::getCurrentCustomerGroups();
                $sqlGroups = (count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1');
            }

            $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('ps.`id_supplier`, COUNT(DISTINCT ps.`id_product`) AS nb_products')
                    ->from('product_supplier', 'ps')
                    ->innerJoin('product', 'p', 'ps.`id_product` = p.`id_product`')
                    ->join(Shop::addSqlAssociation('product', 'p'))
                    ->leftJoin('supplier', 'm', 'm.`id_supplier` = p.`id_supplier`')
                    ->where('ps.`id_product_attribute` = 0')
                    ->where($active ? 'product_shop.`active` = 1' : '')
                    ->where('product_shop.`visibility` NOT IN ("none")')
                    ->where($allGroups ? 'ps.`id_product` IN (SELECT cp.`id_product` FROM `'._DB_PREFIX_.'category_group` cg LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`) WHERE cg.`id_group` '.$sqlGroups.')' : '')
                    ->groupBy('ps.`id_supplier`')
            );

            $counts = [];
            foreach ($results as $result) {
                $counts[(int) $result['id_supplier']] = (int) $result['nb_products'];
            }

            if (count($counts) && is_array($suppliers)) {
                foreach ($suppliers as $key => $supplier) {
                    if (isset($counts[(int) $supplier['id_supplier']])) {
                        $suppliers[$key]['nb_products'] = $counts[(int) $supplier['id_supplier']];
                    } else {
                        $suppliers[$key]['nb_products'] = 0;
                    }
                }
            }
        }

        $nbSuppliers = count($suppliers);
        $rewriteSettings = (int) Configuration::get('PS_REWRITING_SETTINGS');
        for ($i = 0; $i < $nbSuppliers; $i++) {
            $suppliers[$i]['link_rewrite'] = ($rewriteSettings ? Tools::link_rewrite($suppliers[$i]['name']) : 0);
        }

        return $suppliers;
    }

    /**
     * @param null $nullValues
     *
     * @return bool Indicates whether updating succeeded
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($nullValues = null)
    {
        if (PageCache::isEnabled()) {
            PageCache::invalidateEntity('supplier', $this->id);
        }

        return parent::update($nullValues);
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (PageCache::isEnabled()) {
            PageCache::invalidateEntity('supplier', $this->id);
        }

        return parent::delete();
    }

    /**
     * @param int $idSupplier
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getNameById($idSupplier)
    {
        // @codingStandardsIgnoreStart
        if (!isset(static::$cache_name[$idSupplier])) {
            static::$cache_name[$idSupplier] = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`name`')
                    ->from('supplier')
                    ->where('`id_supplier` = '.(int) $idSupplier)
            );
        }

        return static::$cache_name[$idSupplier];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param string $name
     *
     * @return bool|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIdByName($name)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_supplier`')
                ->from('supplier')
                ->where('`name` = \''.pSQL($name).'\'')
        );

        if (isset($result['id_supplier'])) {
            return (int) $result['id_supplier'];
        }

        return false;
    }

    /**
     * @param int         $idSupplier
     * @param int         $idLang
     * @param int         $p
     * @param int         $n
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param bool        $getTotal
     * @param bool        $active
     * @param bool        $activeCategory
     *
     * @return array|bool
     *
     * @since    1.0.0
     * @version  1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public static function getProducts(
        $idSupplier,
        $idLang,
        $p,
        $n,
        $orderBy = null,
        $orderWay = null,
        $getTotal = false,
        $active = true,
        $activeCategory = true
    ) {
        $context = Context::getContext();
        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        if ($p < 1) {
            $p = 1;
        }
        if (empty($orderBy) || $orderBy == 'position') {
            $orderBy = 'name';
        }
        if (empty($orderWay)) {
            $orderWay = 'ASC';
        }

        if (!Validate::isOrderBy($orderBy) || !Validate::isOrderWay($orderWay)) {
            die(Tools::displayError());
        }

        $sqlGroups = '';
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sqlGroups = 'cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1');
        }

        /* Return only the number of products */
        if ($getTotal) {
            $sql = new DbQuery();
            $sql->select('cp.`id_product`');
            $sql->from('category_product', 'cp');
            if (Group::isFeatureActive()) {
	            $sql->leftJoin('category_group', 'cg', 'cp.`id_category` = cg.`id_category`');
            }
            if ($activeCategory) {
	            $sql->innerJoin('category', 'ca', 'cp.`id_category` = ca.`id_category` AND ca.`active` = 1');
            }
	        $sql->where($sqlGroups);

            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('COUNT(DISTINCT ps.`id_product`)')
                    ->from('product_supplier', 'ps')
                    ->innerJoin('product', 'p', 'ps.`id_product` = p.`id_product`')
                    ->join(Shop::addSqlAssociation('product', 'p'))
                    ->where('ps.`id_supplier` = '.(int) $idSupplier)
                    ->where('ps.`id_product_attribute` = 0')
                    ->where($active ? 'product_shop.`active` = 1' : '')
                    ->where($front ? 'product_shop.`visibility` IN ("both", "catalog")' : '')
                    ->where('p.`id_product` IN ('.$sql->build().')')
            );
        }

        $nbDaysNewProduct = Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20;

        if (strpos('.', $orderBy) > 0) {
            $orderBy = explode('.', $orderBy);
            $orderBy = pSQL($orderBy[0]).'.`'.pSQL($orderBy[1]).'`';
        }
        $alias = '';
        if (in_array($orderBy, ['price', 'date_add', 'date_upd'])) {
            $alias = 'product_shop.';
        } elseif ($orderBy == 'id_product') {
            $alias = 'p.';
        } elseif ($orderBy == 'manufacturer_name') {
            $orderBy = 'name';
            $alias = 'm.';
        }

        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock,
					IFNULL(stock.quantity, 0) as quantity,
					pl.`description`,
					pl.`description_short`,
					pl.`link_rewrite`,
					pl.`meta_description`,
					pl.`meta_keywords`,
					pl.`meta_title`,
					pl.`name`,
					image_shop.`id_image` id_image,
					il.`legend`,
					s.`name` AS supplier_name,
					DATEDIFF(p.`date_add`, DATE_SUB("'.date('Y-m-d').' 00:00:00", INTERVAL '.($nbDaysNewProduct).' DAY)) > 0 AS new,
					m.`name` AS manufacturer_name'.(Combination::isFeatureActive() ? ', product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute' : '').'
				 FROM `'._DB_PREFIX_.'product` p
				'.Shop::addSqlAssociation('product', 'p').'
				JOIN `'._DB_PREFIX_.'product_supplier` ps ON (ps.id_product = p.id_product
					AND ps.id_product_attribute = 0) '.
            (Combination::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop
				ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $context->shop->id.')' : '').'
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $context->shop->id.')
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = '.(int) $idLang.')
				LEFT JOIN `'._DB_PREFIX_.'supplier` s ON s.`id_supplier` = p.`id_supplier`
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
				'.Product::sqlStock('p', 0);

        if (Group::isFeatureActive() || $activeCategory) {
            $sql .= 'JOIN `'._DB_PREFIX_.'category_product` cp ON (p.id_product = cp.id_product)';
            if (Group::isFeatureActive()) {
                $sql .= 'JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.`id_category` = cg.`id_category` AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').')';
            }
            if ($activeCategory) {
                $sql .= 'JOIN `'._DB_PREFIX_.'category` ca ON cp.`id_category` = ca.`id_category` AND ca.`active` = 1';
            }
        }

        $sql .= '
				WHERE ps.`id_supplier` = '.(int) $idSupplier.'
					'.($active ? ' AND product_shop.`active` = 1' : '').'
					'.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
				GROUP BY ps.id_product
				ORDER BY '.$alias.pSQL($orderBy).' '.pSQL($orderWay).'
				LIMIT '.(((int) $p - 1) * (int) $n).','.(int) $n;

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);

        if (!$result) {
            return false;
        }

        if ($orderBy == 'price') {
            Tools::orderbyPrice($result, $orderWay);
        }

        return Product::getProductsProperties($idLang, $result);
    }

    /**
     * @param int $idSupplier
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function supplierExists($idSupplier)
    {
        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('id_supplier')
                ->from('supplier')
                ->where('id_supplier = '.(int) $idSupplier)
        );

        return ($res > 0);
    }

    /**
     * Gets product informations
     *
     * @since   1.5.0
     *
     * @param int $idSupplier
     * @param int $idProduct
     * @param int $idProductAttribute
     *
     * @return false|array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProductInformationsBySupplier($idSupplier, $idProduct, $idProductAttribute = 0)
    {
        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('product_supplier_reference, product_supplier_price_te, id_currency')
                ->from('product_supplier')
                ->where('id_supplier = '.(int) $idSupplier)
                ->where('id_product = '.(int) $idProduct)
                ->where('id_product_attribute = '.(int) $idProductAttribute)
        );

        if (count($res)) {
            return $res[0];
        }

        return false;
    }

    /**
     * @param int $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductsLite($idLang)
    {
        $context = Context::getContext();
        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('p.`id_product`, pl.`name`')
                ->from('product', 'p')
                ->join(Shop::addSqlAssociation('product', 'p'))
                ->leftJoin('product_lang', 'pl', 'p.`id_product` = pl.`id_product` AND pl.`id_lang` = '.(int) $idLang)
                ->innerJoin('product_supplier', 'ps', 'p.`id_product` = ps.`id_product`')
                ->where('ps.`id_supplier` = '.(int) $this->id)
                ->where($front ? 'product_shop.`visibility` IN ("both", "catalog")': '')
                ->groupBy('p.`id_product`')
        );

        return $res;
    }
}
