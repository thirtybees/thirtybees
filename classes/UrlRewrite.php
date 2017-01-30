<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class UrlRewrite
 *
 * @since 1.0.0
 */
class UrlRewriteCore extends Objectmodel
{
    const CANONICAL = 1;
    const DIRECT_SERVE = 2;
    const REDIRECT_301 = 3;
    const REDIRECT_302 = 4;

    const ENTITY_PRODUCT = 1;
    const ENTITY_CATEGORY = 2;
    const ENTITY_SUPPLIER = 3;
    const ENTITY_MANUFACTURER = 4;
    const ENTITY_CMS = 5;
    const ENTITY_CMS_CATEGORY = 6;
    const ENTITY_PAGE = 7;

    const MAX_CATEGORY_DEPTH = 10;

    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'          => 'url_rewrite',
        'primary'        => 'id_url_rewrite',
        'multilang_shop' => true,
        'fields'         => [
            'entity'    => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedInt'],
            'id_entity' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedInt '],
            'rewrite'   => ['type' => self::TYPE_STRING, 'lang' => true, 'required' => true, 'validate' => 'isString', 'size' => 2048],
            'redirect'  => ['type' => self::TYPE_INT, 'lang' => true, 'required' => true, 'validate' => 'isUnsignedInt'],
        ],
    ];
    /** @var int $entity */
    public $entity;
    /** @var int $id_entity */
    public $id_entity;
    /** @var string $rewrite */
    public $rewrite;
    /** @var int $redirect */
    public $redirect;
    // @codingStandardsIgnoreEnd

    /**
     * @param null     $idLang
     * @param int|null $idShop
     * @param array    $entities
     *
     * @return bool
     * @since 1.0.0
     *
     */
    public static function regenerateUrlRewrites(
        $idLang = null,
        $idShop = null,
        array $entities = [
            self::ENTITY_PRODUCT,
            self::ENTITY_CATEGORY,
            self::ENTITY_SUPPLIER,
            self::ENTITY_MANUFACTURER,
            self::ENTITY_CMS,
            self::ENTITY_CMS_CATEGORY,
            self::ENTITY_PAGE,
        ]
    ) {
        if ($idShop) {
            $idShops = [$idShop];
        } else {
            $idShops = Shop::getShops(false, null, true);
        }

        if ($idLang) {
            $idLangs = [$idLang];
        } else {
            $idLangs = Language::getLanguages(false, false, true);
        }

        foreach ($idShops as $idShop) {
            $newRoutes[$idShop] = [];
            foreach ($idLangs as $idLang) {
                $newRoutes[$idShop][$idLang] = [
                    self::ENTITY_PRODUCT      => Configuration::get('PS_ROUTE_product_rule') ? Configuration::get('PS_ROUTE_product_rule') : Dispatcher::getInstance()->routes[$idShop][$idLang]['product_rule']['rule'],
                    self::ENTITY_CATEGORY     => Configuration::get('PS_ROUTE_category_rule') ? Configuration::get('PS_ROUTE_category_rule') : Dispatcher::getInstance()->routes[$idShop][$idLang]['category_rule']['rule'],
                    self::ENTITY_SUPPLIER     => Configuration::get('PS_ROUTE_supplier_rule') ? Configuration::get('PS_ROUTE_supplier_rule') : Dispatcher::getInstance()->routes[$idShop][$idLang]['supplier_rule']['rule'],
                    self::ENTITY_MANUFACTURER => Configuration::get('PS_ROUTE_manufacturer_rule') ? Configuration::get('PS_ROUTE_manufacturer_rule') : Dispatcher::getInstance()->routes[$idShop][$idLang]['manufacturer_rule']['rule'],
                    self::ENTITY_CMS          => Configuration::get('PS_ROUTE_cms_rule') ? Configuration::get('PS_ROUTE_cms_rule') : Dispatcher::getInstance()->routes[$idShop][$idLang]['cms_rule']['rule'],
                    self::ENTITY_CMS_CATEGORY => Configuration::get('PS_ROUTE_cms_category_rule') ? Configuration::get('PS_ROUTE_cms_category_rule') : Dispatcher::getInstance()->routes[$idShop][$idLang]['cms_category_rule']['rule'],
                ];
                $categoryInfo = self::getCategoryInfo($idLang, $idShop);
                self::generateProductUrlRewrites($idLang, $idShop, $categoryInfo, $newRoutes);
                self::generateCategoryUrlRewrites($idLang, $idShop, $categoryInfo, $newRoutes);
                self::generateSupplierUrlRewrites($idLang, $idShop, $newRoutes);
                self::generateManufacturerUrlRewrites($idLang, $idShop, $newRoutes);
                $cmsCategoryInfo = self::getCmsCategoryInfo($idLang, $idShop);
                self::generateCmsUrlRewrites($idLang, $idShop, $cmsCategoryInfo, $newRoutes);
                self::generateCmsCategoryUrlRewrites($idLang, $idShop, $cmsCategoryInfo, $newRoutes);
                self::generatePageUrlRewrites($idLang, $idShop);
            }
        }
    }

    /**
     * Check if a keyword is written in a route rule
     *
     * @param        $rule
     * @param string $keyword
     *
     * @return bool
     *
     * @since    1.0.0
     * @version  1.0.0 Initial version
     */
    protected static function hasKeyword($rule, $keyword)
    {
        return preg_match('#\{([^{}]*:)?'.preg_quote($keyword, '#').'(:[^{}]*)?\}#', $rule);
    }

    /**
     * @param string $route
     * @param array  $params
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function createBaseUrl($route, $params)
    {
        // Build an url which match a route
        foreach ($params as $key => $value) {
            $route = preg_replace('#\{([^{}]*:)?'.$key.'(:[^{}]*)?\}#', $value, $route);
        }

        return (string) preg_replace('#\{([^{}]*:)?[a-z0-9_]+?(:[^{}]*)?\}#', '', $route);
    }

    /**
     * Delete URL rewrites
     *
     * @param int $entityType
     * @param int $idLang
     * @param int $idShop
     *
     * @since 1.0.0
     */
    public static function deleteUrlRewrites($entityType, $idLang, $idShop)
    {
        Db::getInstance()->delete(bqSQL(self::$definition['table']), '`entity` = '.(int) $entityType.' AND `id_lang` = '.(int) $idLang.' AND `id_shop` = '.(int) $idShop);
    }

    /**
     * @param string   $rewrite
     * @param int      $idLang
     * @param int      $idShop
     * @param int|null $redirect
     * @param int|null $entityType
     *
     * @return array|bool|false|null|PDOStatement|resource
     *
     * @since 1.0.0
     */
    public static function lookup($rewrite, $idLang, $idShop, $redirect = null, $entityType = null)
    {
        $sql = new DbQuery();
        $sql->select('`id_entity`, `entity`, `rewrite`, `redirect`');
        $sql->from(bqSQL(self::$definition['table']));
        $sql->where('`id_lang` = '.(int) $idLang);
        $sql->where('`id_shop` = '.(int) $idShop);
        $sql->where('`rewrite` = \''.pSQL($rewrite).'\'');
        if (is_int($entityType)) {
            $sql->where('`entity` = '.(int) $entityType);
        }
        if (!empty($redirect)) {
            if (is_array($redirect)) {
                foreach ($redirect as &$item) {
                    $item = (int) $item;
                }
                $sql->where('`redirect` IN ('.implode(',', $redirect).')');
            } else {
                $sql->where('`redirect` = '.(int) $redirect);
            }
        }

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!empty($results)) {
            return $results;
        }

        return false;
    }

    /**
     * @param int      $idEntity
     * @param int      $entityType
     * @param int      $idLang
     * @param int      $idShop
     * @param int|null $redirect
     *
     * @return bool|false|null|string
     * @since 1.0.0
     */
    public static function reverseLookup($idEntity, $entityType, $idLang, $idShop, $redirect = null)
    {
        $sql = new DbQuery();
        $sql->select('`rewrite`');
        $sql->from(bqSQL(self::$definition['table']));
        $sql->where('`id_entity` = '.(int) $idEntity);
        $sql->where('`entity` = '.(int) $entityType);
        $sql->where('`id_lang` = '.(int) $idLang);
        $sql->where('`id_shop` = '.(int) $idShop);
        if (!empty($redirect)) {
            if (is_array($redirect)) {
                foreach ($redirect as &$item) {
                    $item = (int) $item;
                }
                $sql->where('`redirect` IN ('.implode(',', $redirect).')');
            } else {
                $sql->where('`redirect` = '.(int) $redirect);
            }
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected static function getCategoryInfo($idLang, $idShop)
    {
        $filterCategories = [Configuration::get('PS_HOME_CATEGORY', null, null, $idShop), Configuration::get('PS_ROOT_CATEGORY', null, null, $idShop)];

        $sql = new DbQuery();
        $sql->select('c.`id_category`, c.`id_parent`, cl.`link_rewrite`, cl.`meta_keywords`, cl.`meta_title`');
        $sql->from('category', 'c');
        $sql->innerJoin('category_shop', 'cs', 'cs.`id_category` = c.`id_category`');
        $sql->innerJoin('category_lang', 'cl', 'cl.`id_category` = c.`id_category`');
        $sql->where('cl.`id_lang` = '.(int) $idLang);
        $sql->where('cs.`id_shop` = '.(int) $idShop);

        $categoryInfo = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        $categories = [];
        $count = count($categoryInfo);
        for ($i = 0; $i < $count; $i++) {
            $categories[$categoryInfo[$i]['id_category']] = $categoryInfo[$i];
            if (in_array($categoryInfo[$i]['id_category'], $filterCategories)) {
                $categories[$categoryInfo[$i]['id_category']]['link_rewrite'] = '';
            }
            $categories[$categoryInfo[$i]['id_category']]['rewrite'] = $categories[$categoryInfo[$i]['id_category']]['link_rewrite'];
            unset($categoryInfo[$i]);
        }

        return $categories;
    }

    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected static function getCmsCategoryInfo($idLang, $idShop)
    {
        $sql = new DbQuery();
        $sql->select('cc.`id_cms_category`, cc.`id_parent`, ccl.`link_rewrite`, ccl.`meta_keywords`, ccl.`meta_title`');
        $sql->from('cms_category', 'cc');
        $sql->innerJoin('cms_category_shop', 'ccs', 'ccs.`id_cms_category` = cc.`id_cms_category`');
        $sql->innerJoin('cms_category_lang', 'ccl', 'ccl.`id_cms_category` = cc.`id_cms_category`');
        $sql->where('ccl.`id_lang` = '.(int) $idLang);
        $sql->where('ccs.`id_shop` = '.(int) $idShop);

        $categoryInfo = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        $categories = [];
        $count = count($categoryInfo);
        for ($i = 0; $i < $count; $i++) {
            $categories[$categoryInfo[$i]['id_cms_category']] = $categoryInfo[$i];
            if (!$categoryInfo[$i]['id_parent']) {
                $categories[$categoryInfo[$i]['id_cms_category']]['link_rewrite'] = '';
            }
            unset($categoryInfo[$i]);
        }

        return $categories;
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $categories
     * @param array $newRoutes
     *
     * @since 1.0.0
     */
    protected static function generateProductUrlRewrites($idLang, $idShop, $categories, $newRoutes)
    {
        $usedKeys = [
            'id'            => self::hasKeyword($newRoutes[$idShop][$idLang][self::ENTITY_PRODUCT], 'id'),
            'rewrite'       => true,
            'ean13'         => self::hasKeyword($newRoutes[$idShop][$idLang][self::ENTITY_PRODUCT], 'ean13'),
            'category'      => self::hasKeyword($newRoutes[$idShop][$idLang][self::ENTITY_PRODUCT], 'category'),
            'categories'    => self::hasKeyword($newRoutes[$idShop][$idLang][self::ENTITY_PRODUCT], 'categories'),
            'reference'     => self::hasKeyword($newRoutes[$idShop][$idLang][self::ENTITY_PRODUCT], 'reference'),
            'meta_keywords' => self::hasKeyword($newRoutes[$idShop][$idLang][self::ENTITY_PRODUCT], 'meta_keywords'),
            'meta_title'    => self::hasKeyword($newRoutes[$idShop][$idLang][self::ENTITY_PRODUCT], 'meta_title'),
            'manufacturer'  => self::hasKeyword($newRoutes[$idShop][$idLang][self::ENTITY_PRODUCT], 'manufacturer'),
            'supplier'      => self::hasKeyword($newRoutes[$idShop][$idLang][self::ENTITY_PRODUCT], 'supplier'),
        ];


        $sql = new DbQuery();
        $select = 'p.`id_product` as `id`, pl.`link_rewrite` as `rewrite`';
        if ($usedKeys['category']) {
            $select .= ', cl.`link_rewrite` as `category`';
            $sql->leftJoin('category_lang', 'cl', 'p.`id_category_default` = cl.`id_category`');
            $sql->where('cl.`id_lang` = '.(int) $idLang);
        }
        if ($usedKeys['categories']) {
            $select .= ', p.`id_category_default`';
        }
        if ($usedKeys['reference']) {
            $select .= ', p.`reference`';
        }
        if ($usedKeys['meta_keywords']) {
            $select .= ', pl.`meta_keywords`';
        }
        if ($usedKeys['meta_title']) {
            $select .= ', pl.`meta_title`';
        }
        if ($usedKeys['manufacturer']) {
            $select .= ', m.`name` as `manufacturer_name`';
            $sql->leftJoin('manufacturer', 'm', 'p.`id_manufacturer` = m.`id_manufacturer`');
        }
        if ($usedKeys['supplier']) {
            $select .= ', s.`name` as `supplier_name`';
            $sql->leftJoin('supplier', 's', 'p.`id_manufacturer` = s.`id_manufacturer`');
        }

        $sql->select($select);
        $sql->from('product', 'p');
        $sql->leftJoin('product_shop', 'ps', 'ps.`id_product` = p.`id_product`');
        $sql->leftJoin('product_lang', 'pl', 'pl.`id_product` = p.`id_product`');
        $sql->where('pl.`id_lang` = '.(int) $idLang);
        $sql->where('pl.`id_shop` = '.(int) $idShop);

        $productInfos = Db::getInstance()->executeS($sql);
        if ($usedKeys['supplier'] || $usedKeys['manufacturer'] || $usedKeys['categories']) {
            foreach ($productInfos as &$productInfo) {
                if ($usedKeys['supplier']) {
                    $productInfo['supplier'] = Tools::link_rewrite($productInfo['supplier_name']);
                    unset($productInfo['supplier_name']);
                }
                if ($usedKeys['manufacturer']) {
                    $productInfo['manufacturer'] = Tools::link_rewrite($productInfo['manufacturer_name']);
                    unset($productInfo['manufacturer_name']);
                }
                if ($usedKeys['categories']) {
                    $categoryRewrite = '';
                    $idCategory = (int) $productInfo['id_category_default'];
                    $depth = 0;
                    while ($idCategory && $depth < self::MAX_CATEGORY_DEPTH && $categories[$idCategory]['link_rewrite']) {
                        $categoryRewrite = '/'.$categories[$idCategory]['link_rewrite'].$categoryRewrite;
                        $idCategory = (int) $categories[$idCategory]['id_parent'];
                        $depth++;
                    }
                    $productInfo['categories'] = ltrim($categoryRewrite.'/', '/');
                }
            }

        }

        $insert = [];
        foreach ($productInfos as $productInfo) {
            $insert[] = [
                'id_entity' => $productInfo['id'],
                'entity' => self::ENTITY_PRODUCT,
                'rewrite' => self::createBaseUrl($newRoutes[$idShop][$idLang][self::ENTITY_PRODUCT], $productInfo),
                'id_shop' => $idShop,
                'id_lang' => $idLang,
                'redirect' => self::CANONICAL,
            ];
        }

        self::deleteUrlRewrites(self::ENTITY_PRODUCT, $idLang, $idShop);
        Db::getInstance()->insert(bqSQL(self::$definition['table']), $insert);
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $categories
     * @param array $newRoutes
     *
     * @since 1.0.0
     */
    protected static function generateCategoryUrlRewrites($idLang, $idShop, $categories, &$newRoutes)
    {
        $filterCategories = [Configuration::get('PS_HOME_CATEGORY', null, null, $idShop), Configuration::get('PS_ROOT_CATEGORY', null, null, $idShop)];
        if (self::hasKeyword($newRoutes[$idShop][$idLang][self::ENTITY_CATEGORY], 'categories')) {
            foreach ($categories as &$category) {
                $categoryRewrite = '';
                $idCategory = (int) $category['id_parent'];
                $depth = 0;
                $path = [];
                while ($idCategory && !in_array($idCategory, $filterCategories) && $depth < self::MAX_CATEGORY_DEPTH && $categories[$idCategory]['link_rewrite']) {
                    $categoryRewrite = $categories[$idCategory]['link_rewrite'].'/'.$categoryRewrite;
                    $idCategory = (int) $categories[$idCategory]['id_parent'];
                    $path[] = $idCategory;
                    $depth++;
                }

                $categoryRewrite = trim($categoryRewrite, '/').'/';
                if ($categoryRewrite === '/') {
                    $categoryRewrite = '';
                }
                $category['categories'] = $categoryRewrite;
            }
        }

        $insert = [];
        foreach ($categories as $category) {
            $insert[] = [
                'id_entity' => $category['id_category'],
                'entity' => self::ENTITY_CATEGORY,
                'rewrite' => self::createBaseUrl($newRoutes[$idShop][$idLang][self::ENTITY_CATEGORY], $category),
                'id_shop' => $idShop,
                'id_lang' => $idLang,
                'redirect' => self::CANONICAL,
            ];
        }

        self::deleteUrlRewrites(self::ENTITY_CATEGORY, $idLang, $idShop);
        Db::getInstance()->insert(bqSQL(self::$definition['table']), $insert);
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $newRoutes
     */
    protected static function generateSupplierUrlRewrites($idLang, $idShop, $newRoutes)
    {
        $sql = new DbQuery();
        $sql->select('s.`id_supplier` as `id`, s.`name` as `rewrite`, sl.`meta_keywords`, sl.`meta_title`');
        $sql->from('supplier', 's');
        $sql->leftJoin('supplier_shop', 'ss', 'ss.`id_supplier` = s.`id_supplier`');
        $sql->leftJoin('supplier_lang', 'sl', 'sl.`id_supplier` = s.`id_supplier`');
        $sql->where('sl.`id_lang` = '.(int) $idLang);
        $sql->where('ss.`id_shop` = '.(int) $idShop);

        $suppliers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!$suppliers) {
            return;
        }

        $insert = [];
        foreach ($suppliers as &$supplier) {
            $supplier['rewrite'] = Tools::link_rewrite($supplier['rewrite']);
            $insert[] = [
                'id_entity' => $supplier['id'],
                'entity' => self::ENTITY_SUPPLIER,
                'rewrite' => self::createBaseUrl($newRoutes[$idShop][$idLang][self::ENTITY_SUPPLIER], $supplier),
                'id_shop' => $idShop,
                'id_lang' => $idLang,
                'redirect' => self::CANONICAL,
            ];
        }

        self::deleteUrlRewrites(self::ENTITY_SUPPLIER, $idLang, $idShop);
        Db::getInstance()->insert(bqSQL(self::$definition['table']), $insert);
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $newRoutes
     *
     * @since 1.0.0
     */
    protected static function generateManufacturerUrlRewrites($idLang, $idShop, &$newRoutes)
    {
        $sql = new DbQuery();
        $sql->select('m.`id_manufacturer` as `id`, m.`name` as `rewrite`, ml.`meta_keywords`, ml.`meta_title`');
        $sql->from('manufacturer', 'm');
        $sql->leftJoin('manufacturer_shop', 'ms', 'ms.`id_manufacturer` = m.`id_manufacturer`');
        $sql->leftJoin('manufacturer_lang', 'ml', 'ml.`id_manufacturer` = m.`id_manufacturer`');
        $sql->where('ml.`id_lang` = '.(int) $idLang);
        $sql->where('ms.`id_shop` = '.(int) $idShop);

        $manufacturers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!$manufacturers) {
            return;
        }

        $insert = [];
        foreach ($manufacturers as &$manufacturer) {
            $manufacturer['rewrite'] = Tools::link_rewrite($manufacturer['rewrite']);
            $insert[] = [
                'id_entity' => $manufacturer['id'],
                'entity' => self::ENTITY_MANUFACTURER,
                'rewrite' => self::createBaseUrl($newRoutes[$idShop][$idLang][self::ENTITY_MANUFACTURER], $manufacturer),
                'id_shop' => $idShop,
                'id_lang' => $idLang,
                'redirect' => self::CANONICAL,
            ];
        }

        self::deleteUrlRewrites(self::ENTITY_MANUFACTURER, $idLang, $idShop);
        Db::getInstance()->insert(bqSQL(self::$definition['table']), $insert);
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $categories
     * @param array $newRoutes
     *
     * @since 1.0.0
     */
    protected static function generateCmsUrlRewrites($idLang, $idShop, $categories, &$newRoutes)
    {
        if (self::hasKeyword($newRoutes[$idShop][$idLang][self::ENTITY_CMS], 'categories')) {
            foreach ($categories as &$category) {
                $categoryRewrite = '';
                $idCategory = (int) $category['id_cms_category'];
                $depth = 0;
                while ($idCategory && $depth < self::MAX_CATEGORY_DEPTH && $categories[$idCategory]['link_rewrite']) {
                    $categoryRewrite = '/'.$categories[$idCategory]['link_rewrite'].$categoryRewrite;
                    $idCategory = (int) $categories[$idCategory]['id_parent'];
                    $depth++;
                }
                $category['categories'] = ltrim($categoryRewrite, '/');
            }
        }

        $sql = new DbQuery();
        $sql->select('c.`id_cms` as `id`, cl.`link_rewrite` as `rewrite`, cl.`meta_keywords`, cl.`meta_title`');
        $sql->from('cms', 'c');
        $sql->leftJoin('cms_shop', 'cs', 'cs.`id_cms` = c.`id_cms`');
        $sql->leftJoin('cms_lang', 'cl', 'cl.`id_cms` = c.`id_cms`');
        $sql->where('cl.`id_lang` = '.(int) $idLang);
        $sql->where('cs.`id_shop` = '.(int) $idShop);

        $cmses = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!$cmses) {
            return;
        }

        $insert = [];
        foreach ($cmses as &$cms) {
            $insert[] = [
                'id_entity' => $cms['id'],
                'entity' => self::ENTITY_CMS,
                'rewrite' => self::createBaseUrl($newRoutes[$idShop][$idLang][self::ENTITY_CMS], $cms),
                'id_shop' => $idShop,
                'id_lang' => $idLang,
                'redirect' => self::CANONICAL,
            ];
        }

        self::deleteUrlRewrites(self::ENTITY_CMS, $idLang, $idShop);
        Db::getInstance()->insert(bqSQL(self::$definition['table']), $insert);
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $categories
     * @param array $newRoutes
     *
     * @since 1.0.0
     */
    protected static function generateCmsCategoryUrlRewrites($idLang, $idShop, $categories, &$newRoutes)
    {
        if (self::hasKeyword($newRoutes[$idShop][$idLang][self::ENTITY_CMS_CATEGORY], 'cms_categories')) {
            foreach ($categories as &$category) {
                $categoryRewrite = '';
                $idCategory = (int) $category['id_cms_category'];
                $depth = 0;
                while ($idCategory && $depth < self::MAX_CATEGORY_DEPTH && $categories[$idCategory]['link_rewrite']) {
                    $categoryRewrite = '/'.$categories[$idCategory]['link_rewrite'].$categoryRewrite;
                    $idCategory = (int) $categories[$idCategory]['id_parent'];
                    $depth++;
                }
                $category['categories'] = ltrim($categoryRewrite, '/');
            }
        }

        $insert = [];
        foreach ($categories as $category) {
            $insert[] = [
                'id_entity' => $category['id_cms_category'],
                'entity' => self::ENTITY_CMS_CATEGORY,
                'id_shop' => $idShop,
                'rewrite' => self::createBaseUrl($newRoutes[$idShop][$idLang][self::ENTITY_CMS_CATEGORY], $category),
                'id_lang' => $idLang,
                'redirect' => self::CANONICAL,
            ];
        }

        self::deleteUrlRewrites(self::ENTITY_CMS_CATEGORY, $idLang, $idShop);
        Db::getInstance()->insert(bqSQL(self::$definition['table']), $insert);
    }

    protected static function generatePageUrlRewrites($idLang, $idShop)
    {
        $sql = new DbQuery();
        $sql->select('m.`id_meta` as `id`, m.`page`, ml.`url_rewrite`, ml.`id_lang`');
        $sql->from('meta', 'm');
        $sql->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta` '.Shop::addSqlRestrictionOnLang('ml', $idShop));
        $sql->orderBy('LENGTH(ml.`url_rewrite`) DESC');
        if ($results = Db::getInstance()->executeS($sql)) {
            $insert = [];
            foreach ($results as $result) {
                $insert[] = [
                    'id_entity' => (int) $result['id'],
                    'entity'    => self::ENTITY_PAGE,
                    'id_shop'   => (int) $idShop,
                    'rewrite'   => pSQL($result['url_rewrite']),
                    'id_lang'   => (int) $idLang,
                    'redirect'  => self::CANONICAL,
                ];
            }

            self::deleteUrlRewrites(self::ENTITY_PAGE, $idLang, $idShop);
            Db::getInstance()->insert(bqSQL(self::$definition['table']), $insert);
        }
    }
}
