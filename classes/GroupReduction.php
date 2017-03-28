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
            'reduction'   => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
        ],
    ];

    /**
     * @param int $idGroup
     * @param int $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGroupReductions($idGroup, $idLang)
    {
        $lang = $idLang.Shop::addSqlRestrictionOnLang('cl');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT gr.`id_group_reduction`, gr.`id_group`, gr.`id_category`, gr.`reduction`, cl.`name` AS category_name
			FROM `'._DB_PREFIX_.'group_reduction` gr
			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (cl.`id_category` = gr.`id_category` AND cl.`id_lang` = '.(int) $lang.')
			WHERE `id_group` = '.(int) $idGroup
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
     */
    public static function getValueForProduct($idProduct, $idGroup)
    {
        if (!Group::isFeatureActive()) {
            return 0;
        }

        if (!isset(static::$reduction_cache[$idProduct.'-'.$idGroup])) {
            static::$reduction_cache[$idProduct.'-'.$idGroup] = Db::getInstance()->getValue(
                '
			SELECT `reduction`
			FROM `'._DB_PREFIX_.'product_group_reduction_cache`
			WHERE `id_product` = '.(int) $idProduct.' AND `id_group` = '.(int) $idGroup
            );
        }

        // Should return string (decimal in database) and not a float
        return static::$reduction_cache[$idProduct.'-'.$idGroup];
    }

    /**
     * @param int $idGroup
     * @param int $idCategory
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function doesExist($idGroup, $idCategory)
    {
        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
		SELECT `id_group`
		FROM `'._DB_PREFIX_.'group_reduction`
		WHERE `id_group` = '.(int) $idGroup.' AND `id_category` = '.(int) $idCategory
        );
    }

    /**
     * @deprecated 1.0.0
     *
     * @param int $idCategory
     *
     * @return array|null
     */
    public static function getGroupByCategoryId($idCategory)
    {
        Tools::displayAsDeprecated('Use GroupReduction::getGroupsByCategoryId($id_category)');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            '
			SELECT gr.`id_group` AS id_group, gr.`reduction` AS reduction, id_group_reduction
			FROM `'._DB_PREFIX_.'group_reduction` gr
			WHERE `id_category` = '.(int) $idCategory, false
        );
    }

    /**
     * @param int $idCategory
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGroupsReductionByCategoryId($idCategory)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT gr.`id_group_reduction` AS id_group_reduction, id_group
			FROM `'._DB_PREFIX_.'group_reduction` gr
			WHERE `id_category` = '.(int) $idCategory
        );
    }

    /**
     * @deprecated 1.0.0
     *
     * @param int $idCategory
     *
     * @return array|null
     */
    public static function getGroupReductionByCategoryId($idCategory)
    {
        Tools::displayAsDeprecated('Use GroupReduction::getGroupsByCategoryId($id_category)');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            '
			SELECT gr.`id_group_reduction` AS id_group_reduction
			FROM `'._DB_PREFIX_.'group_reduction` gr
			WHERE `id_category` = '.(int) $idCategory, false
        );
    }

    /**
     * @param int     $idProduct
     * @param int|null $id_group
     * @param int|null $id_category
     * @param int|null $reduction
     *
     * @return bool
     *
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
     */
    public static function deleteProductReduction($idProduct)
    {
        $query = 'DELETE FROM `'._DB_PREFIX_.'product_group_reduction_cache` WHERE `id_product` = '.(int) $idProduct;
        if (Db::getInstance()->execute($query) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param int $idCategory
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGroupsByCategoryId($idCategory)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT gr.`id_group` AS id_group, gr.`reduction` AS reduction, id_group_reduction
			FROM `'._DB_PREFIX_.'group_reduction` gr
			WHERE `id_category` = '.(int) $idCategory
        );
    }

    /**
     * @param int $idProductOld
     * @param int $idProduct
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function duplicateReduction($idProductOld, $idProduct)
    {
        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executes(
            '
			SELECT pgr.`id_product`, pgr.`id_group`, pgr.`reduction`
			FROM `'._DB_PREFIX_.'product_group_reduction_cache` pgr
			WHERE pgr.`id_product` = '.(int) $idProductOld
        );

        if (!$res) {
            return true;
        }

        $query = '';

        foreach ($res as $row) {
            $query .= 'INSERT INTO `'._DB_PREFIX_.'product_group_reduction_cache` (`id_product`, `id_group`, `reduction`) VALUES ';
            $query .= '('.(int) $idProduct.', '.(int) $row['id_group'].', '.(float) $row['reduction'].') ON DUPLICATE KEY UPDATE `reduction` = '.(float) $row['reduction'].';';
        }

        return Db::getInstance()->execute($query);
    }

    /**
     * @param int $idCategory
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function deleteCategory($idCategory)
    {
        $query = 'DELETE FROM `'._DB_PREFIX_.'group_reduction` WHERE `id_category` = '.(int) $idCategory;
        if (Db::getInstance()->Execute($query) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autodate = true, $nullValues = false)
    {
        return (parent::add($autodate, $nullValues) && $this->_setCache());
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _setCache()
    {
        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT cp.`id_product`
			FROM `'._DB_PREFIX_.'category_product` cp
			WHERE cp.`id_category` = '.(int) $this->id_category
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _updateCache()
    {
        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT cp.`id_product`
			FROM `'._DB_PREFIX_.'category_product` cp
			WHERE cp.`id_category` = '.(int) $this->id_category,
            false
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
                'id_product IN('.implode(', ', $ids).') AND id_group = '.(int) $this->id_group
            );
        }

        return $result;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT cp.`id_product`
			FROM `'._DB_PREFIX_.'category_product` cp
			WHERE cp.`id_category` = '.(int) $this->id_category
        );

        $ids = [];
        foreach ($products as $row) {
            $ids[] = $row['id_product'];
        }

        if ($ids) {
            Db::getInstance()->delete('product_group_reduction_cache', 'id_product IN ('.implode(', ', $ids).')');
        }

        return (parent::delete());
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _clearCache()
    {
        return Db::getInstance()->delete('product_group_reduction_cache', 'id_group = '.(int) $this->id_group);
    }
}
