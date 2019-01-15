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
 * Class CompareProductCore
 *
 * @since 1.0.0
 */
class CompareProductCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'compare',
        'primary' => 'id_compare',
        'fields'  => [
            'id_compare'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
        ],
    ];
    /** @var int $id_compare */
    public $id_compare;
    /** @var int $id_customer */
    public $id_customer;
    /** @var string $date_add */
    public $date_add;
    /** @var string $date_upd */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * Get all compare products of the customer
     *
     * @param int $idCompare
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCompareProducts($idCompare)
    {
        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('DISTINCT `id_product`')
                ->from('compare', 'c')
                ->leftJoin('compare_product', 'cp', 'cp.`id_compare` = c.`id_compare`')
                ->where('cp.`id_compare` = '.(int) $idCompare)
        );

        $compareProducts = [];

        if ($results) {
            foreach ($results as $result) {
                $compareProducts[] = (int) $result['id_product'];
            }
        }

        return $compareProducts;
    }

    /**
     * Add a compare product for the customer
     *
     * @param int $idCompare
     * @param int $idProduct
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public static function addCompareProduct($idCompare, $idProduct)
    {
        // Check if compare row exists
        $idCompare = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_compare`')
                ->from('compare')
                ->where('`id_compare` = '.(int) $idCompare)
        );

        if (!$idCompare) {
            $idCustomer = false;
            if (Context::getContext()->customer) {
                $idCustomer = Context::getContext()->customer->id;
            }
            $sql = Db::getInstance()->insert(
                'compare',
                [
                    'id_compare'  => ['type' => 'sql', 'value' => 'NULL'],
                    'id_customer' => (int) $idCustomer,
                ],
                true
            );
            if ($sql) {
                $idCompare = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    (new DbQuery())
                        ->select('MAX(`id_compare`)')
                        ->from('compare')
                );
                Context::getContext()->cookie->id_compare = $idCompare;
            }
        }

        return Db::getInstance()->insert(
            'compare_product',
            [
                'id_compare' => (int) $idCompare,
                'id_product' => (int) $idProduct,
                'date_add'   => ['type' => 'sql', 'value' => 'NOW()'],
                'date_upd'   => ['type' => 'sql', 'value' => 'NOW()'],
            ]
        );
    }

    /**
     * Remove a compare product for the customer
     *
     * @param int $idCompare
     * @param int $idProduct
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function removeCompareProduct($idCompare, $idProduct)
    {
        return Db::getInstance()->execute('
            DELETE cp FROM `'._DB_PREFIX_.'compare_product` cp, `'._DB_PREFIX_.'compare` c
            WHERE cp.`id_compare`=c.`id_compare`
            AND cp.`id_product` = '.(int) $idProduct.'
            AND c.`id_compare` = '.(int) $idCompare);
    }

    /**
     * Get the number of compare products of the customer
     *
     * @param int $idCompare
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getNumberProducts($idCompare)
    {
        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(`id_compare`)')
                ->from('compare_product')
                ->where('`id_compare` = '.(int) $idCompare)
        );
    }

    /**
     * Clean entries which are older than the period
     *
     * @param string $period
     *
     * @return void
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function cleanCompareProducts($period = null)
    {
        if ($period !== null) {
            Tools::displayParameterAsDeprecated('period');
        }

        Db::getInstance()->execute(
            '
        DELETE cp, c FROM `'._DB_PREFIX_.'compare_product` cp, `'._DB_PREFIX_.'compare` c
        WHERE cp.date_upd < DATE_SUB(NOW(), INTERVAL 1 WEEK) AND c.`id_compare`=cp.`id_compare`'
        );
    }

    /**
     * Get the id_compare by id_customer
     *
     * @param int $idCustomer
     *
     * @return int $id_compare
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getIdCompareByIdCustomer($idCustomer)
    {
        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_compare`')
                ->from('compare')
                ->where('`id_customer` = '.(int) $idCustomer)
        );
    }
}
