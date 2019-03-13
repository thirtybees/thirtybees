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
 * Class GroupCore
 *
 * @since 1.0.0
 */
class GroupCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    protected static $cache_reduction = [];
    protected static $group_price_display_method = [];
    /** @var string Lastname */
    public $name;
    /** @var string Reduction */
    public $reduction;
    /** @var int Price display method (tax inc/tax exc) */
    public $price_display_method;
    /** @var bool Show prices */
    public $show_prices = 1;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'group',
        'primary'   => 'id_group',
        'multilang' => true,
        'fields'    => [
            'reduction'            => ['type' => self::TYPE_FLOAT, 'validate' => 'isPercentage'],
            'price_display_method' => ['type' => self::TYPE_INT, 'validate' => 'isPriceDisplayMethod', 'required' => true],
            'show_prices'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add'             => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'             => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],

            /* Lang fields */
            'name'                 => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
        ],
    ];

    protected $webserviceParameters = [];

    /**
     * GroupCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
        // @codingStandardsIgnoreStart
        if ($this->id
            && ! isset(static::$group_price_display_method[$this->id])) {
            static::$group_price_display_method[$this->id] = $this->price_display_method;
        }
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param int      $idLang
     * @param int|bool $idShop: false  --> Return all groups.
     *                          true   --> Return groups associated with
     *                                     current shop (from context).
     *                          number --> Return groups associated with the
     *                                     specific shop with this ID.
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGroups($idLang, $idShop = false)
    {
        $shopCriteria = '';
        if (is_int($idShop)) {
            $shopCriteria = ' INNER JOIN `'._DB_PREFIX_.'group_shop` gs ON (gs.`id_group` = g.`id_group` AND gs.`id_shop` = '.$idShop.')';
        } elseif ($idShop) {
            $shopCriteria = Shop::addSqlAssociation('group', 'g');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('DISTINCT g.`id_group`, g.`reduction`, g.`price_display_method`, gl.`name`')
                ->from('group', 'g')
                ->leftJoin('group_lang', 'gl', 'g.`id_group` = gl.`id_group` AND gl.`id_lang` = '.(int) $idLang.$shopCriteria)
                ->orderBy('g.`id_group` ASC')
        );
    }

    /**
     * @param int|null $idCustomer
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getReduction($idCustomer = null)
    {
        // @codingStandardsIgnoreStart
        if (!isset(static::$cache_reduction['customer'][(int) $idCustomer])) {
            $idGroup = $idCustomer ?
                Customer::getDefaultGroupId((int) $idCustomer) :
                (int) static::getCurrent()->id;
            static::$cache_reduction['customer'][(int) $idCustomer]
                = static::getReductionByIdGroup($idGroup);
        }

        return static::$cache_reduction['customer'][(int) $idCustomer];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Return current group object
     * Use context
     *
     * @return Group Group object
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getCurrent()
    {
        static $groups = [];
        static $psUnidentifiedGroup = null;
        static $psCustomerGroup = null;

        if ($psUnidentifiedGroup === null) {
            $psUnidentifiedGroup = Configuration::get('PS_UNIDENTIFIED_GROUP');
        }

        if ($psCustomerGroup === null) {
            $psCustomerGroup = Configuration::get('PS_CUSTOMER_GROUP');
        }

        $customer = Context::getContext()->customer;
        if (Validate::isLoadedObject($customer)) {
            $idGroup = (int) $customer->id_default_group;
        } else {
            $idGroup = (int) $psUnidentifiedGroup;
        }

        if (!isset($groups[$idGroup])) {
            $groups[$idGroup] = new Group($idGroup);
        }

        if (!$groups[$idGroup]->isAssociatedToShop(Context::getContext()->shop->id)) {
            $idGroup = (int) $psCustomerGroup;
            if (!isset($groups[$idGroup])) {
                $groups[$idGroup] = new Group($idGroup);
            }
        }

        return $groups[$idGroup];
    }

    /**
     * Get reduction for a group, which happens to be a percentage.
     *
     * @param int $idGroup
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getReductionByIdGroup($idGroup)
    {
        // @codingStandardsIgnoreStart
        if (!isset(static::$cache_reduction['group'][$idGroup])) {
            static::$cache_reduction['group'][$idGroup] = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`reduction`')
                    ->from('group')
                    ->where('`id_group` = '.(int) $idGroup)
            );
        }

        return static::$cache_reduction['group'][$idGroup];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getDefaultPriceDisplayMethod()
    {
        return static::getPriceDisplayMethod(
            (int) Configuration::get('PS_CUSTOMER_GROUP')
        );
    }

    /**
     * @param int $idGroup
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getPriceDisplayMethod($idGroup)
    {
        // @codingStandardsIgnoreStart
        if ( ! isset(static::$group_price_display_method[$idGroup])) {
            static::$group_price_display_method[$idGroup] = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`price_display_method`')
                    ->from('group')
                    ->where('`id_group` = '.(int) $idGroup)
            );
        }

        return static::$group_price_display_method[$idGroup];
        // @codingStandardsIgnoreEnd
    }

    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     *
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isFeatureActive()
    {
        static $psGroupFeatureActive = null;
        if ($psGroupFeatureActive === null) {
            $psGroupFeatureActive = Configuration::get('PS_GROUP_FEATURE_ACTIVE');
        }

        return $psGroupFeatureActive;
    }

    /**
     * This method is allow to know if there are other groups than the default ones
     *
     * @param string $table
     * @param bool   $hasActiveColumn
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isCurrentlyUsed($table = null, $hasActiveColumn = false)
    {
        return (bool) (Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue((new DbQuery())->select('COUNT(*)')->from('group')) > 3);
    }

    /**
     * Truncate all restrictions by module
     *
     * @param int $idModule
     *
     * @return bool
     *
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public static function truncateRestrictionsByModule($idModule)
    {
        return Db::getInstance()->delete('module_group', '`id_module` = '.(int) $idModule);
    }

    /**
     * Adding restrictions modules to the group with id $id_group
     *
     * @param int   $idGroup
     * @param array $modules
     * @param array $shops
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function addModulesRestrictions($idGroup, $modules, $shops = [1])
    {
        if (!is_array($modules) || !count($modules) || !is_array($shops) || !count($shops)) {
            return false;
        }

        // Delete all record for this group
        Db::getInstance()->delete('module_group', '`id_group` = '.(int) $idGroup);

        $insert = [];
        foreach ($modules as $module) {
            foreach ($shops as $shop) {
                $insert[] = [
                    'id_module' => (int) $module,
                    'id_shop'   => (int) $shop,
                    'id_group'  => (int) $idGroup,
                ];
            }
        }

        return (bool) Db::getInstance()->insert('module_group', $insert);
    }

    /**
     * Add restrictions for a new module.
     * We authorize every groups to the new module
     *
     * @param int   $idModule
     * @param array $shops
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function addRestrictionsForModule($idModule, $shops = [1])
    {
        if (!is_array($shops) || !count($shops)) {
            return false;
        }

        $res = true;
        foreach ($shops as $shop) {
            $res &= Db::getInstance()->execute(
                '
			INSERT INTO `'._DB_PREFIX_.'module_group` (`id_module`, `id_shop`, `id_group`)
			(SELECT '.(int) $idModule.', '.(int) $shop.', id_group FROM `'._DB_PREFIX_.'group`)'
            );
        }

        return $res;
    }

    /**
     * Light back office search for Group
     *
     * @param string $query Searched string
     *
     * @return array Corresponding groups
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function searchByName($query)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('g.*, gl.*')
                ->from('group', 'g')
                ->leftJoin('group_lang', 'gl', 'g.`id_group` = gl.`id_group`')
                ->where('`name` = \''.pSQL($query).'\'')
        );
    }

    /**
     * @param bool $count
     * @param int  $start
     * @param int  $limit
     * @param bool $shopFiltering
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getCustomers($count = false, $start = 0, $limit = 0, $shopFiltering = false)
    {
        if ($count) {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('customer', 'c')
                    ->leftJoin('customer', 'c', 'cg.`id_customer` = c.`id_customer`')
                    ->where('cg.`id_group` = '.(int) $this->id.' '.($shopFiltering ? Shop::addSqlRestriction(Shop::SHARE_CUSTOMER) : ''))
                    ->where('c.`deleted` != 1')
            );
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cg.`id_customer`, c.*')
                ->from('customer_group', 'cg')
                ->leftJoin('customer', 'c', 'cg.`id_customer` = c.`id_customer`')
                ->where('cg.`id_group` = '.(int) $this->id)
                ->where('c.`deleted` != 1'.($shopFiltering ? Shop::addSqlRestriction(Shop::SHARE_CUSTOMER) : ''))
                ->orderBy('cg.`id_customer` ASC')
                ->limit($limit > 0 ? (int) $limit : 0, $limit ? (int) $start : 0)
        );
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false)
    {
        Configuration::updateGlobalValue('PS_GROUP_FEATURE_ACTIVE', '1');
        if (parent::add($autoDate, $nullValues)) {
            Category::setNewGroupForHome((int) $this->id);
            Carrier::assignGroupToAllCarriers((int) $this->id);

            return true;
        }

        return false;
    }

    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function update($autodate = true, $nullValues = false)
    {
        if (!Configuration::getGlobalValue('PS_GROUP_FEATURE_ACTIVE') && $this->reduction > 0) {
            Configuration::updateGlobalValue('PS_GROUP_FEATURE_ACTIVE', 1);
        }

        return parent::update($autodate, $nullValues);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function delete()
    {
        if ($this->id == (int) Configuration::get('PS_CUSTOMER_GROUP')) {
            return false;
        }
        if (parent::delete()) {
            Db::getInstance()->delete('cart_rule_group', '`id_group` = '.(int) $this->id);
            Db::getInstance()->delete('customer_group', '`id_group` = '.(int) $this->id);
            Db::getInstance()->delete('category_group', '`id_group` = '.(int) $this->id);
            Db::getInstance()->delete('group_reduction', '`id_group` = '.(int) $this->id);
            Db::getInstance()->delete('product_group_reduction_cache', '`id_group` = '.(int) $this->id);
            $this->truncateModulesRestrictions($this->id);

            // Add default group (id 3) to customers without groups
            Db::getInstance()->execute(
                'INSERT INTO `'._DB_PREFIX_.'customer_group` (
				SELECT c.id_customer, '.(int) Configuration::get('PS_CUSTOMER_GROUP').' FROM `'._DB_PREFIX_.'customer` c
				LEFT JOIN `'._DB_PREFIX_.'customer_group` cg
				ON cg.id_customer = c.id_customer
				WHERE cg.id_customer IS NULL)'
            );

            // Set to the customer the default group
            // Select the minimal id from customer_group
            Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.'customer` cg
				SET id_default_group =
					IFNULL((
						SELECT min(id_group) FROM `'._DB_PREFIX_.'customer_group`
						WHERE id_customer = cg.id_customer),
						'.(int) Configuration::get('PS_CUSTOMER_GROUP').')
				WHERE `id_default_group` = '.(int) $this->id
            );

            return Db::getInstance()->delete('module_group', '`id_group` = '.(int) $this->id);
        }

        return false;
    }

    /**
     * Truncate all modules restrictions for the group
     *
     * @param int $idGroup
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public static function truncateModulesRestrictions($idGroup)
    {
        return Db::getInstance()->delete(
            'module_group',
            '`id_group` = '.(int) $idGroup
        );
    }
}
