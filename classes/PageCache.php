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
 * Class PageCache
 *
 * @since 1.0.0
 * @since 1.0.1 Overridable
 */
class PageCacheCore
{
    /**
     * Register cache key and set its metadata
     *
     * @param string $key
     * @param int    $idCurrency
     * @param int    $idLanguage
     * @param int    $idCountry
     * @param int    $idShop
     * @param string $entityType
     * @param int    $idEntity
     *
     * @since 1.0.0
     */
    public static function cacheKey($key, $idCurrency, $idLanguage, $idCountry, $idShop, $entityType, $idEntity)
    {
        try {
            Db::getInstance()->insert(
                'page_cache',
                [
                    'cache_hash'  => pSQL($key),
                    'id_currency' => (int) $idCurrency,
                    'id_language' => (int) $idLanguage,
                    'id_country'  => (int) $idCountry,
                    'id_shop'     => (int) $idShop,
                    'entity_type' => pSQL($entityType),
                    'id_entity'   => (int) $idEntity,
                ],
                false,
                true,
                Db::ON_DUPLICATE_KEY
            );
        } catch (Exception $e) {
            // Hash already inserted
        }
    }

    /**
     * Invalidate an entity from the cache
     *
     * @param string   $entityType
     * @param int|null $idEntity
     *
     * @since 1.0.0
     */
    public static function invalidateEntity($entityType, $idEntity = null)
    {
        $keysToInvalidate = [];

        if ($entityType === 'product') {
            // Refresh the homepage
            $keysToInvalidate = array_merge(
                $keysToInvalidate,
                static::getKeysToInvalidate('index')
            );

            Db::getInstance()->delete(
                'page_cache',
                '`entity_type` = \'index\''
            );
            if ($idEntity) {
                // Invalidate product's categories only
                $product = new Product((int) $idEntity);
                if ($product) {
                    $categories = $product->getCategories();
                    foreach ($categories as $idCategory) {
                        $keysToInvalidate = array_merge(
                            $keysToInvalidate,
                            static::getKeysToInvalidate('category', $idCategory)
                        );
                        Db::getInstance()->delete(
                            'page_cache',
                            '`entity_type` = \'category\' AND `id_entity` = '.(int) $idCategory
                        );
                    }
                }
            } else {
                // Invalidate all parent categories
                $keysToInvalidate = array_merge(
                    $keysToInvalidate,
                    static::getKeysToInvalidate('category')
                );
                 Db::getInstance()->delete(
                    'page_cache',
                    '`entity_type` = \'category\''
                );
            }
        }

        $keysToInvalidate = array_merge(
            $keysToInvalidate,
            static::getKeysToInvalidate($entityType, $idEntity)
        );
        Db::getInstance()->delete(
            'page_cache',
            '`entity_type` = \''.pSQL($entityType).'\''.($idEntity ? ' AND `id_entity` = '.(int) $idEntity : '')
        );

        $cache = Cache::getInstance();
        foreach ($keysToInvalidate as $item) {
            $cache->delete($item);
        }
    }

    /**
     * Flush all data
     *
     * @since 1.0.0
     */
    public static function flush()
    {
        if (defined('_PS_CACHE_ENABLED_') && _PS_CACHE_ENABLED_) {
            Cache::getInstance()->flush();
        }

        Db::getInstance()->delete('page_cache');
    }

    /**
     * Get keys to invalidate
     *
     * @param string   $entityType
     * @param int|null $idEntity
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected static function getKeysToInvalidate($entityType, $idEntity = null)
    {
        $sql = new DbQuery();
        $sql->select('`cache_hash`');
        $sql->from('page_cache');
        $sql->where('`entity_type` = \''.pSQL($entityType).'\'');
        if ($idEntity) {
            $sql->where('`id_entity` = '.(int) $idEntity);
        }

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!is_array($results)) {
            return [];
        }

        return array_column($results, 'cache_hash');
    }
}
