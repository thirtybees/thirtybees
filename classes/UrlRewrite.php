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
    public static $definition = [];
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
     * UrlRewriteCore constructor.
     *
     * @param null $id
     * @param null $idLang
     * @param null $idShop
     *
     * @deprecated 1.0.1
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param null     $idLang
     * @param int|null $idShop
     * @param array    $entities
     *
     * @return void
     * @deprecated 1.0.1
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
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param int      $entityType
     * @param int|null $idEntity
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return void
     * @deprecated 1.0.1
     */
    public static function regenerateUrlRewrite($entityType, $idEntity = null, $idLang = null, $idShop = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param string $route
     * @param array  $params
     *
     * @return void
     * @deprecated 1.0.1
     */
    public static function createBaseUrl($route, $params)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * Delete URL rewrites
     *
     * @param int      $entityType
     * @param int      $idLang
     * @param int      $idShop
     * @param int|null $idEntity
     *
     * @return void
     * @deprecated 1.0.1
     */
    public static function deleteUrlRewrites($entityType, $idLang, $idShop, $idEntity = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param int      $entityType
     * @param int      $idEntity
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return void
     * @deprecated 1.0.1
     */
    public static function deleteUrlRewrite($entityType, $idEntity, $idLang = null, $idShop = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param string   $rewrite
     * @param int      $idLang
     * @param int      $idShop
     * @param int|null $redirect
     * @param int|null $entityType
     *
     * @return void
     * @since 1.0.0
     */
    public static function lookup($rewrite, $idLang, $idShop, $redirect = null, $entityType = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param int      $idEntity
     * @param int      $entityType
     * @param int      $idLang
     * @param int      $idShop
     * @param int|null $redirect
     *
     * @return void
     * @since 1.0.0
     */
    public static function reverseLookup($idEntity, $entityType, $idLang, $idShop, $redirect = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function getCategoryInfo($idLang, $idShop)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function getCmsCategoryInfo($idLang, $idShop)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param int      $idLang
     * @param int      $idShop
     * @param array    $categories
     * @param array    $newRoutes
     * @param int|null $idEntity
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function generateProductUrlRewrites($idLang, $idShop, $categories, $newRoutes, $idEntity = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param int   $idLang
     * @param int   $idShop
     * @param array $categories
     * @param array $newRoutes
     * @param int|null  $idEntity
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function generateCategoryUrlRewrites($idLang, $idShop, $categories, &$newRoutes, $idEntity = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $newRoutes
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function generateSupplierUrlRewrites($idLang, $idShop, $newRoutes, $idEntity = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $newRoutes
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function generateManufacturerUrlRewrites($idLang, $idShop, &$newRoutes, $idEntity = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $categories
     * @param array $newRoutes
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function generateCmsUrlRewrites($idLang, $idShop, $categories, &$newRoutes, $idEntity = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $categories
     * @param array $newRoutes
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function generateCmsCategoryUrlRewrites($idLang, $idShop, $categories, &$newRoutes, $idEntity = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * Fills the routes array with the current available routes
     *
     * @param int   $idLang
     * @param int   $idShop
     * @param array $routes
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function getRoutes($idLang, $idShop, &$routes)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * Check if a keyword is written in a route rule
     *
     * @param        $rule
     * @param string $keyword
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function hasKeyword($rule, $keyword)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    protected static function generatePageUrlRewrites($idLang, $idShop)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }

    /**
     * @param int      $idLang
     * @param int      $idShop
     * @param int|null $idProduct
     *
     * @return void
     * @deprecated 1.0.1
     */
    public static function updateProductRewrite($idLang, $idShop, $idProduct = null)
    {
        Tools::displayAsDeprecated('UrlRewrite class has been removed');
    }
}
