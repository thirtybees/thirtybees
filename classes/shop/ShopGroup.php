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
 * Class ShopGroupCore
 */
class ShopGroupCore extends ObjectModel
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $active = true;

    /**
     * @var bool
     */
    public $share_customer;

    /**
     * @var bool
     */
    public $share_stock;

    /**
     * @var bool
     */
    public $share_order;

    /**
     * @var bool
     */
    public $deleted;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'shop_group',
        'primary' => 'id_shop_group',
        'fields'  => [
            'name'           => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'share_customer' => ['type' => self::TYPE_BOOL,   'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbNullable' => false],
            'share_order'    => ['type' => self::TYPE_BOOL,   'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbNullable' => false],
            'share_stock'    => ['type' => self::TYPE_BOOL,   'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbNullable' => false],
            'active'         => ['type' => self::TYPE_BOOL,   'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'deleted'        => ['type' => self::TYPE_BOOL,   'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        ],
        'keys' => [
            'shop_group' => [
                'deleted' => ['type' => ObjectModel::KEY, 'columns' => ['deleted', 'name']],
            ],
        ],
    ];

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function getFields()
    {
        if (!$this->share_customer || !$this->share_stock) {
            $this->share_order = false;
        }

        return parent::getFields();
    }

    /**
     * @param bool $active
     *
     * @return PrestaShopCollection
     *
     * @throws PrestaShopException
     */
    public static function getShopGroups($active = true)
    {
        $groups = new PrestaShopCollection('ShopGroup');
        $groups->where('deleted', '=', false);
        if ($active) {
            $groups->where('active', '=', true);
        }

        return $groups;
    }

    /**
     * @param bool $active
     *
     * @return int Total of shop groups
     *
     * @throws PrestaShopException
     */
    public static function getTotalShopGroup($active = true)
    {
        return count(ShopGroup::getShopGroups($active));
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function haveShops()
    {
        return (bool) $this->getTotalShops();
    }

    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    public function getTotalShops()
    {
        return (int) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('shop', 's')
                ->where('`id_shop_group` = '.(int) $this->id)
        );
    }

    /**
     * @param int $idGroup
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getShopsFromGroup($idGroup)
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('s.`id_shop`')
                ->from('shop', 's')
                ->where('`id_shop_group` = '.(int) $idGroup)
        );
    }

    /**
     * Return a group shop ID from group shop name
     *
     * @param string $name
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function getIdByName($name)
    {
        return (int) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_shop_group`')
                ->from('shop_group')
                ->where('`name` = \''.pSQL($name).'\'')
        );
    }

    /**
     * Detect dependency with customer or orders
     *
     * @param int $idShopGroup
     * @param string $check all|customer|order
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function hasDependency($idShopGroup, $check = 'all')
    {
        $listShops = Shop::getShops(false, $idShopGroup, true);
        if (!$listShops) {
            return false;
        }

        $connection = Db::readOnly();
        if ($check == 'all' || $check == 'customer') {
            $totalCustomer = (int) $connection->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('customer')
                    ->where('`id_shop` IN ('.implode(', ', $listShops).')')
            );
            if ($totalCustomer) {
                return true;
            }
        }

        if ($check == 'all' || $check == 'order') {
            $totalOrder = (int) $connection->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('orders')
                    ->where('`id_shop` IN ('.implode(', ', $listShops).')')
            );
            if ($totalOrder) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $name
     * @param bool $idShop
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function shopNameExists($name, $idShop = false)
    {
        return Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_shop`')
                ->from('shop')
                ->where('`name` = \''.pSQL($name).'\'')
                ->where('`id_shop_group` = '.(int) $this->id)
                ->where($idShop ? 'id_shop != '.(int) $idShop : '')
        );
    }
}
