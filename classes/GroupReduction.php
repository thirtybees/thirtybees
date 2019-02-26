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
 * Class GroupReductionCore
 *
 * @since 1.0.0
 */
class GroupReductionCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    protected static $reduction_cache = [];
    public $id_group;
    public $id_category;
    public $reduction;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'group_reduction',
        'primary' => 'id_group_reduction',
        'fields'  => [
            'id_group'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_category' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'reduction'   => ['type' => self::TYPE_FLOAT, 'validate' => 'isPercentage', 'required' => true],
        ],
    ];

    /**
     * @param int $idGroup
     * @param int $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGroupReductions($idGroup, $idLang)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('gr.`id_group_reduction`, gr.`id_group`, gr.`id_category`, gr.`reduction`, cl.`name` AS category_name')
                ->from('group_reduction', 'gr')
                ->leftJoin('category_lang', 'cl', 'cl.`id_category` = gr.`id_category`')
                ->where('gr.`id_group` = '.(int) $idGroup)
                ->where('cl.`id_lang` = '.(int) $idLang)
        );
    }

    /**
     * @param int $idProduct
     * @param int $idGroup
     *
     * @return int|mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getValueForProduct($idProduct, $idGroup)
    {
        if (!Group::isFeatureActive()) {
            return 0;
        }

        // @codingStandardsIgnoreStart
        if (!isset(static::$reduction_cache[$idProduct.'-'.$idGroup])) {
            static::$reduction_cache[$idProduct.'-'.$idGroup] = Db::getInstance()->getValue(
                (new DbQuery())
                    ->select('`reduction`')
                    ->from('product_group_reduction_cache')
                    ->where('`id_product` = '.(int) $idProduct)
                    ->where('`id_group` = '.(int) $idGroup)
            );
        }

        // Should return string (decimal in database) and not a float
        return static::$reduction_cache[$idProduct.'-'.$idGroup];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param int $idGroup
     * @param int $idCategory
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function doesExist($idGroup, $idCategory)
    {
        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('gr.`id_group`')
                ->from('group_reduction', 'gr')
                ->where('gr.`id_group` = '.(int) $idGroup)
                ->where('gr.`id_category` = '.(int) $idCategory)
        );
    }

    /**
     * @deprecated 1.0.0
     *
     * @param int $idCategory
     *
     * @return array|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getGroupByCategoryId($idCategory)
    {
        Tools::displayAsDeprecated('Use GroupReduction::getGroupsByCategoryId($id_category)');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('gr.`id_group`')
                ->from('group_reduction', 'gr')
                ->where('gr.`id_category` = '.(int) $idCategory)
        );
    }

    /**
     * @param int $idCategory
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGroupsReductionByCategoryId($idCategory)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('gr.`id_group_reduction` AS `id_group_reduction`, gr.`id_group`')
                ->from('group_reduction', 'gr')
                ->where('`id_category` = '.(int) $idCategory)
        );
    }

    /**
     * @deprecated 1.0.0
     *
     * @param int $idCategory
     *
     * @return array|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getGroupReductionByCategoryId($idCategory)
    {
        Tools::displayAsDeprecated('Use GroupReduction::getGroupsByCategoryId($id_category)');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('gr.`id_group_reduction`')
                ->from('group_reduction', 'gr')
                ->where('`id_category` = '.(int) $idCategory)
        );
    }

    /**
     * @param int      $idProduct
     * @param int|null $idGroup
     * @param int|null $idCategory
     * @param int|null $reduction
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function setProductReduction($idProduct, $idGroup = null, $idCategory = null, $reduction = null)
    {
        $res = true;
        GroupReduction::deleteProductReduction((int) $idProduct);

        $categories = Product::getProductCategories((int) $idProduct);

        if ($categories) {
            foreach ($categories as $category) {
                $reductions = GroupReduction::getGroupsByCategoryId((int) $category);
                if ($reductions) {
                    foreach ($reductions as $reduction) {
                        $currentGroupReduction = new GroupReduction((int) $reduction['id_group_reduction']);
                        $res &= $currentGroupReduction->_setCache();
                    }
                }
            }
        }

        return $res;
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public static function deleteProductReduction($idProduct)
    {
        return (bool) Db::getInstance()->delete('product_group_reduction_cache', '`id_product` = '.(int) $idProduct);
    }

    /**
     * @param int $idCategory
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGroupsByCategoryId($idCategory)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('gr.`id_group`, gr.`reduction`, gr.`id_group_reduction`')
                ->from('group_reduction', 'gr')
                ->where('`id_category` = '.(int) $idCategory)
        );
    }

    /**
     * @param int $idProductOld
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function duplicateReduction($idProductOld, $idProduct)
    {
        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executes(
            (new DbQuery())
                ->select('pgr.`id_product`, pgr.`id_group`, pgr.`reduction`')
                ->from('product_group_reduction_cache', 'pgr')
                ->where('pgr.`id_product` = '.(int) $idProductOld)
        );

        if (!$res) {
            return true;
        }

        $insert = [];

        foreach ($res as &$row) {
            $insert[] = [
                'id_product' => (int) $idProduct,
                'id_group'   => (int) $row['id_group'],
                'reduction'  => (float) $row['reduction'],
            ];
        }

        if (empty($insert)) {
            return true;
        }

        return Db::getInstance()->insert('product_group_reduction_cache', $insert, false, true, Db::ON_DUPLICATE_KEY);
    }

    /**
     * @param int $idCategory
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public static function deleteCategory($idCategory)
    {
        return (bool) Db::getInstance()->delete('group_reduction', '`id_category` = '.(int) $idCategory);
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        return (parent::add($autoDate, $nullValues) && $this->_setCache());
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    // @codingStandardsIgnoreStart
    protected function _setCache()
    {
        // @codingStandardsIgnoreEnd
        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cp.`id_product`')
                ->from('category_product', 'cp')
                ->where('cp.`id_category` = '.(int) $this->id_category)
        );

        $values = [];
        foreach ($products as $row) {
            $values[] = '('.(int) $row['id_product'].', '.(int) $this->id_group.', '.(float) $this->reduction.')';
        }

        if (count($values)) {
            $query = 'INSERT INTO `'._DB_PREFIX_.'product_group_reduction_cache` (`id_product`, `id_group`, `reduction`)
			VALUES '.implode(', ', $values).' ON DUPLICATE KEY UPDATE
			`reduction` = IF(VALUES(`reduction`) > `reduction`, VALUES(`reduction`), `reduction`)';

            return (Db::getInstance()->execute($query));
        }

        return true;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($nullValues = false)
    {
        return (parent::update($nullValues) && $this->_updateCache());
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    // @codingStandardsIgnoreStart
    protected function _updateCache()
    {
        // @codingStandardsIgnoreEnd
        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cp.`id_product`')
                ->from('category_product', 'cp')
                ->where('cp.`id_category` = '.(int) $this->id_category)
        );

        $ids = [];
        foreach ($products as $product) {
            $ids[] = $product['id_product'];
        }

        $result = true;
        if ($ids) {
            $result &= Db::getInstance()->update(
                'product_group_reduction_cache',
                [
                    'reduction' => (float) $this->reduction,
                ],
                '`id_product` IN('.implode(', ', $ids).') AND `id_group` = '.(int) $this->id_group
            );
        }

        return $result;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cp.`id_product`')
                ->from('category_product', 'cp')
                ->where('cp.`id_category` = '.(int) $this->id_category)
        );

        $ids = [];
        foreach ($products as $row) {
            $ids[] = $row['id_product'];
        }

        if ($ids) {
            Db::getInstance()->delete('product_group_reduction_cache', '`id_product` IN ('.implode(', ', $ids).')');
        }

        return (parent::delete());
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    // @codingStandardsIgnoreStart
    protected function _clearCache()
    {
        // @codingStandardsIgnoreEnd
        return Db::getInstance()->delete('product_group_reduction_cache', '`id_group` = '.(int) $this->id_group);
    }
}
