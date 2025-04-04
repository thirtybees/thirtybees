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

use CoreUpdater\TableSchema;
use Thirtybees\Core\InitializationCallback;

/**
 * Class CarrierCore
 */
class CarrierCore extends ObjectModel implements InitializationCallback
{
    /**
     * getCarriers method filter
     */
    const PS_CARRIERS_ONLY = 1;
    const CARRIERS_MODULE = 2;
    const CARRIERS_MODULE_NEED_RANGE = 3;
    const PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE = 4;
    const ALL_CARRIERS = 5;

    const SHIPPING_METHOD_DEFAULT = 0;
    const SHIPPING_METHOD_WEIGHT = 1;
    const SHIPPING_METHOD_PRICE = 2;
    const SHIPPING_METHOD_FREE = 3;

    const SHIPPING_PRICE_EXCEPTION = 0;
    const SHIPPING_WEIGHT_EXCEPTION = 1;
    const SHIPPING_SIZE_EXCEPTION = 2;

    const SORT_BY_PRICE = 0;
    const SORT_BY_POSITION = 1;

    const SORT_BY_ASC = 0;
    const SORT_BY_DESC = 1;

    /** @var array $price_by_weight */
    protected static $price_by_weight = [];
    /** @var array $price_by_weight2 */
    protected static $price_by_weight2 = [];
    /** @var array $price_by_price */
    protected static $price_by_price = [];
    /** @var array $price_by_price2 */
    protected static $price_by_price2 = [];
    /** @var array $cache_tax_rule */
    protected static $cache_tax_rule = [];
    /** @var int common id for carrier historization */
    public $id_reference;
    /**
     * @var string Name
     * @deprecated 1.4.0 -- use display name instead
     */
    public $name;
    /** @var string|string[] Name */
    public $display_name;
    /** @var string URL with a '@' for */
    public $url;
    /** @var string|string[] Delay needed to deliver customer */
    public $delay;
    /** @var bool Carrier status */
    public $active = true;
    /** @var bool True if carrier has been deleted (staying in database as deleted) */
    public $deleted = 0;
    /** @var bool Active or not the shipping handling */
    public $shipping_handling = true;
    /** @var bool Behavior taken for unknown range */
    public $range_behavior;
    /** @var bool Carrier module */
    public $is_module;
    /** @var bool Free carrier */
    public $is_free = false;
    /** @var int shipping behavior: by weight or by price */
    public $shipping_method = 0;
    /** @var bool Shipping external */
    public $shipping_external = 0;
    /** @var string Shipping external */
    public $external_module_name = null;
    /** @var bool Need Range */
    public $need_range = 0;
    /** @var int Position */
    public $position;
    /** @var int maximum package width managed by the transporter */
    public $max_width;
    /** @var int maximum package height managed by the transporter */
    public $max_height;
    /** @var int maximum package deep managed by the transporter */
    public $max_depth;
    /** @var float minimum cart total managed by the transporter */
    public $min_total;
    /** @var bool flag thas specify if min_total value is with or without tax */
    public $min_total_tax;
    /** @var float maximum cart total managed by the transporter */
    public $max_total;
    /** @var bool flag thas specify if max_total value is with or without tax */
    public $max_total_tax;
    /** @var float minimum package weight managed by the transporter */
    public $min_weight;
    /** @var float maximum package weight managed by the transporter */
    public $max_weight;
    /** @var int grade of the shipping delay (0 for longest, 9 for shortest) */
    public $grade;
    /** @var bool prices_with_tax indicates if the shipping prices associated with carrier are with or without tax */
    public $prices_with_tax;
    /** @var int $id_tax_rules_group */
    public $id_tax_rules_group;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'          => 'carrier',
        'primary'        => 'id_carrier',
        'multilang'      => true,
        'multilang_shop' => true,
        'fields'         => [
            /* Classic fields */
            'id_reference'         => ['type' => self::TYPE_INT, 'dbNullable' => false],
            'id_tax_rules_group'   => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '0', 'dbNullable' => true],
            'name'                 => ['type' => self::TYPE_STRING, 'validate' => 'isCarrierName', 'required' => true, 'size' => 64],
            'url'                  => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'active'               => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbDefault' => '0'],
            'deleted'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'shipping_handling'    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '1'],
            'range_behavior'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'is_module'            => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'is_free'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'shipping_external'    => ['type' => self::TYPE_BOOL, 'dbDefault' => '0'],
            'need_range'           => ['type' => self::TYPE_BOOL, 'dbDefault' => '0'],
            'external_module_name' => ['type' => self::TYPE_STRING, 'size' => 64],
            'shipping_method'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'int(2)', 'dbDefault' => '0'],
            'position'             => ['type' => self::TYPE_INT, 'dbDefault' => '0'],
            'max_width'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'int(10)', 'dbDefault' => '0', 'dbNullable' => true],
            'max_height'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'int(10)', 'dbDefault' => '0', 'dbNullable' => true],
            'max_depth'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'int(10)', 'dbDefault' => '0', 'dbNullable' => true],
            'min_total'            => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000', 'dbNullable' => true],
            'min_total_tax'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
            'max_total'            => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000', 'dbNullable' => true],
            'max_total_tax'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
            'min_weight'           => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'dbDefault' => '0.000000', 'dbNullable' => true],
            'max_weight'           => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'dbDefault' => '0.000000', 'dbNullable' => true],
            'grade'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'int(10)', 'size' => 1, 'dbDefault' => '0', 'dbNullable' => true],
            'prices_with_tax'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],

            /* Lang fields */
            'display_name'         => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCarrierName', 'required' => true, 'size' => 64],
            'delay'                => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128, 'dbNullable' => true],
        ],
        'associations' => [
            'zone' => ['type' => self::BELONGS_TO_MANY, 'joinTable' => 'carrier_zone'],
            'group' => ['type' => self::BELONGS_TO_MANY, 'joinTable' => 'carrier_group'],
        ],
        'keys' => [
            'carrier' => [
                'deleted'            => ['type' => ObjectModel::KEY, 'columns' => ['deleted', 'active']],
                'id_tax_rules_group' => ['type' => ObjectModel::KEY, 'columns' => ['id_tax_rules_group']],
                'reference'          => ['type' => ObjectModel::KEY, 'columns' => ['id_reference', 'deleted', 'active']],
            ],
            'carrier_shop' => [
                'id_shop' => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
            ],
            'carrier_lang' => [
                'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_lang', 'id_shop', 'id_carrier']],
            ],
        ],
    ];

    /**
     * @var array[]
     */
    protected $webserviceParameters = [
        'fields' => [
            'deleted'            => [],
            'is_module'          => [],
            'id_tax_rules_group' => [
                'getter'         => 'getIdTaxRulesGroup',
                'setter'         => 'setWsTaxRulesGroup',
                'xlink_resource' => [
                    'resourceName' => 'tax_rule_groups',
                ],
            ],
        ],
    ];

    /**
     * CarrierCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     *
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id, $idLang);

        /**
         * keep retrocompatibility SHIPPING_METHOD_DEFAULT
         *
         * @deprecated 1.5.5
         */
        if ($this->shipping_method == static::SHIPPING_METHOD_DEFAULT) {
            $this->shipping_method = ((int) Configuration::get('PS_SHIPPING_METHOD') ? static::SHIPPING_METHOD_WEIGHT : static::SHIPPING_METHOD_PRICE);
        }

        /**
         * keep retrocompatibility id_tax_rules_group
         *
         * @deprecated 1.5.0
         */
        if ($this->id) {
            $this->id_tax_rules_group = $this->getIdTaxRulesGroup(Context::getContext());
            $this->fixNames();
        }

        $this->image_dir = _PS_SHIP_IMG_DIR_;
    }

    /**
     * Returns true, if proportionate shipping and wrapping tax is used
     *
     * @return boolean
     */
    public static function useProportionateTax()
    {
        try {
            return (bool)Configuration::get('PS_ATCP_SHIPWRAP');
        } catch (PrestaShopException $ignored) {
            return false;
        }
    }

    /**
     * Hydrate function for the Carrier
     *
     * @param array $data
     * @param int|null $idLang
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function hydrate(array $data, $idLang = null)
    {
        parent::hydrate($data, $idLang);

        /**
         * keep retrocompatibility id_tax_rules_group
         *
         * @deprecated PS 1.5
         */
        if ($this->id && !$this->id_tax_rules_group) {
            $this->id_tax_rules_group = $this->getIdTaxRulesGroup(Context::getContext());
        }

        $this->fixNames();
    }

    /**
     * Multilang-hydrate function for the Carrier
     *
     * Fill an object with given data. Data must be an array with this syntax:
     * array(
     *   array(id_lang => 1, objProperty => value, objProperty2 => value, etc.),
     *   array(id_lang => 2, objProperty => value, objProperty2 => value, etc.),
     * );
     *
     * @param array $data
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function hydrateMultilang(array $data)
    {
        parent::hydrateMultilang($data);

        /**
         * keep retrocompatibility id_tax_rules_group
         *
         * @deprecated PS 1.5
         */
        if ($this->id && !$this->id_tax_rules_group) {
            $this->id_tax_rules_group = $this->getIdTaxRulesGroup(Context::getContext());
        }

        $this->fixNames();
    }

    /**
     * @param Context|null $context
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function getIdTaxRulesGroup(?Context $context = null)
    {
        return static::getIdTaxRulesGroupByIdCarrier((int) $this->id, $context);
    }

    /**
     * @param int $idCarrier
     * @param Context|null $context
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function getIdTaxRulesGroupByIdCarrier($idCarrier, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $key = 'carrier_id_tax_rules_group_'.(int) $idCarrier.'_'.(int) $context->shop->id;
        if (!Cache::isStored($key)) {
            $result = Db::readOnly()->getValue(
                (new DbQuery())
                    ->select('`id_tax_rules_group`')
                    ->from('carrier_tax_rules_group_shop')
                    ->where('`id_carrier` = '.(int) $idCarrier)
                    ->where('`id_shop` = '.(int) $context->shop->id)
            );
            Cache::store($key, $result);

            return $result;
        }

        return Cache::retrieve($key);
    }

    /**
     * Return the carrier name from the shop name (e.g. if the carrier name is '0').
     *
     * The returned carrier name is the shop name without '#' and ';' because this is not the same validation.
     *
     * @return string Carrier name
     * @throws PrestaShopException
     */
    public static function getCarrierNameFromShopName()
    {
        return str_replace(
            ['#', ';'],
            '',
            Configuration::get('PS_SHOP_NAME')
        );
    }

    /**
     * Get delivery prices for a given shipping method (price/weight)
     *
     * @param string $rangeTable Table name (price or weight)
     * @param int $idCarrier
     *
     * @return array Delivery prices
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getDeliveryPriceByRanges($rangeTable, $idCarrier)
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('d.`id_'.bqSQL($rangeTable).'`, d.`id_carrier`, d.`id_zone`, d.`price`')
                ->from('delivery', 'd')
                ->leftJoin(bqSQL($rangeTable), 'r', 'r.`id_'.bqSQL($rangeTable).'` = d.`id_'.bqSQL($rangeTable).'`')
                ->where('d.`id_carrier` = '.(int) $idCarrier)
                ->where('d.`id_'.bqSQL($rangeTable).'` IS NOT NULL')
                ->where('d.`id_'.bqSQL($rangeTable).'` != 0 '.static::sqlDeliveryRangeShop($rangeTable))
                ->orderBy('r.`delimiter1`')
        );
    }

    /**
     * This tricky method generates a sql clause to check if ranged data are overloaded by multishop
     *
     * @param string $rangeTable
     * @param string $alias
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function sqlDeliveryRangeShop($rangeTable, $alias = 'd')
    {
        if (Shop::getContext() == Shop::CONTEXT_ALL) {
            $where = 'AND d2.id_shop IS NULL AND d2.id_shop_group IS NULL';
        } elseif (Shop::getContext() == Shop::CONTEXT_GROUP) {
            $where = 'AND ((d2.id_shop_group IS NULL OR d2.id_shop_group = '.Shop::getContextShopGroupID().') AND d2.id_shop IS NULL)';
        } else {
            $where = 'AND (d2.id_shop = '.Shop::getContextShopID().' OR (d2.id_shop_group = '.Shop::getContextShopGroupID().'
					AND d2.id_shop IS NULL) OR (d2.id_shop_group IS NULL AND d2.id_shop IS NULL))';
        }

        $sql = 'AND '.$alias.'.id_delivery = (
					SELECT d2.id_delivery
					FROM '._DB_PREFIX_.'delivery d2
					WHERE d2.id_carrier = `'.bqSQL($alias).'`.id_carrier
						AND d2.id_zone = `'.bqSQL($alias).'`.id_zone
						AND d2.`id_'.bqSQL($rangeTable).'` = `'.bqSQL($alias).'`.`id_'.bqSQL($rangeTable).'`
						'.$where.'
					ORDER BY d2.id_shop DESC, d2.id_shop_group DESC
					LIMIT 1
				)';

        return $sql;
    }

    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getIdTaxRulesGroupMostUsed()
    {
        $result = Db::readOnly()->getRow(
            (new DbQuery())
                ->select('COUNT(*) AS `n`, c.`id_tax_rules_group`')
                ->from('carrier', 'c')
                ->innerJoin('tax_rules_group', 'trg', 'c.`id_tax_rules_group` = trg.`id_tax_rules_group`')
                ->groupBy('c.`id_tax_rules_group`')
                ->orderBy('n DESC')
        );

        return isset($result['id_tax_rules_group']) ? (int) $result['id_tax_rules_group'] : false;
    }

    /**
     * @param int $idLang
     * @param bool $activeCountries
     * @param bool $activeCarriers
     * @param bool|null $containStates
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getDeliveredCountries($idLang, $activeCountries = false, $activeCarriers = false, $containStates = null)
    {
        $conn = Db::readOnly();
        $states = $conn->getArray(
            (new DbQuery())
                ->select('s.*')
                ->from('state', 's')
                ->orderBy('s.`name` ASC')
        );

        $result = $conn->getArray(
            (new DbQuery())
                ->select('cl.*, c.*, cl.`name` as `country`, z.`name` as `zone`')
                ->from('country', 'c')
                ->join(Shop::addSqlAssociation('country', 'c'))
                ->leftJoin('country_lang', 'cl', 'cl.`id_country` = c.`id_country` AND cl.`id_lang` = '.(int) $idLang)
                ->innerJoin('carrier_zone', 'cz', 'cz.`id_zone` = c.`id_zone`')
                ->innerJoin('carrier', 'cr', 'cr.`id_carrier` = cz.`id_carrier`')
                ->leftJoin('zone', 'z', 'cz.`id_zone` = z.`id_zone`')
                ->where('cr.`deleted` = 0')
                ->where($activeCarriers ? 'cr.`active` = 1' : '')
                ->where($activeCountries ? 'c.`active` = 1' : '')
                ->where(!is_null($containStates) ? 'c.`contains_states` = '.(int) $containStates : '')
                ->orderBy('cl.`name` ASC')
        );

        $countries = [];
        foreach ($result as &$country) {
            $countries[$country['id_country']] = $country;
        }
        foreach ($states as &$state) {
            if (isset($countries[$state['id_country']])) { /* Does not keep the state if its country has been disabled and not selected */
                if ($state['active'] == 1) {
                    $countries[$state['id_country']]['states'][] = $state;
                }
            }
        }

        return $countries;
    }

    /**
     * Return the default carrier to use
     *
     * @param array $carriers
     * @param int $defaultCarrier the last carrier selected
     *
     * @return int the id of the default carrier
     *
     * @throws PrestaShopException
     */
    public static function getDefaultCarrierSelection($carriers, $defaultCarrier = 0)
    {
        if (empty($carriers)) {
            return 0;
        }

        if ((int) $defaultCarrier != 0) {
            foreach ($carriers as $carrier) {
                if ($carrier['id_carrier'] == (int) $defaultCarrier) {
                    return (int) $carrier['id_carrier'];
                }
            }
        }
        foreach ($carriers as $carrier) {
            if ($carrier['id_carrier'] == (int) Configuration::get('PS_CARRIER_DEFAULT')) {
                return (int) $carrier['id_carrier'];
            }
        }

        return (int) $carriers[0]['id_carrier'];
    }

    /**
     * Get carrier using the reference id
     *
     * @param int $idReference
     *
     * @return bool|Carrier
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCarrierByReference($idReference)
    {
        $carrierData = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('*')
                ->from('carrier', 'c')
                ->innerJoin('carrier_lang', 'cl', 'cl.`id_carrier` = c.`id_carrier`')
                ->where('c.`id_reference` = '.(int) $idReference)
                ->where('c.`deleted` = 0')
                ->where('cl.`id_shop` = '.(int) Context::getContext()->shop->id)
                ->orderBy('c.`id_carrier` DESC')
        );
        if (!$carrierData) {
            return false;
        }

        $carrier = new Carrier();
        $carrier->hydrateMultilang($carrierData);

        return $carrier;
    }

    /**
     * For a given {product, warehouse}, gets the carrier available
     *
     * @param Product $product The id of the product, or an array with at least the package size and weight
     * @param int|null $idWarehouse
     * @param int $idAddressDelivery
     * @param int $idShop
     * @param Cart $cart
     * @param array $error contains an error message if an error occurs
     * @param int|null $combinationId calculate for specific product combination
     *
     * @return array
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public static function getAvailableCarrierList(
        Product $product,
        $idWarehouse,
        $idAddressDelivery = null,
        $idShop = null,
        $cart = null,
        &$error = [],
        $combinationId = 0
    ) {
        static $psCountryDefault = null;

        if ($psCountryDefault === null) {
            $psCountryDefault = Configuration::get('PS_COUNTRY_DEFAULT');
        }

        if (is_null($idShop)) {
            $idShop = Context::getContext()->shop->id;
        }
        if (is_null($cart)) {
            $cart = Context::getContext()->cart;
        }

        if (!is_array($error)) {
            $error = [];
        }

        $idAddress = (int)$idAddressDelivery;
        if (! $idAddress) {
            $idAddress = (int)$cart->id_address_delivery;
        }
        if ($idAddress) {
            $idZone = Address::getZoneById($idAddress);

            // Check the country of the address is activated
            if (!Address::isCountryActiveById($idAddress)) {
                return [];
            }
        } else {
            $country = new Country($psCountryDefault);
            $idZone = $country->id_zone;
        }

        // Does the product is linked with carriers?
        $cacheId = 'Carrier::getAvailableCarrierList_'.(int) $product->id.'-'.(int) $idShop;
        if (!Cache::isStored($cacheId)) {
            $query = new DbQuery();
            $query->select('id_carrier');
            $query->from('product_carrier', 'pc');
            $query->innerJoin(
                'carrier',
                'c',
                'c.`id_reference` = pc.`id_carrier_reference` AND c.`deleted` = 0 AND c.`active` = 1'
            );
            $query->where('pc.`id_product` = '.(int) $product->id);
            $query->where('pc.`id_shop` = '.(int) $idShop);

            $carriersForProduct = Db::readOnly()->getArray($query);
            Cache::store($cacheId, $carriersForProduct);
        } else {
            $carriersForProduct = Cache::retrieve($cacheId);
        }

        $carrierList = [];
        if (!empty($carriersForProduct)) {
            //the product is linked with carriers
            foreach ($carriersForProduct as $carrier) { //check if the linked carriers are available in current zone
                if (static::checkCarrierZone($carrier['id_carrier'], $idZone)) {
                    $carrierList[$carrier['id_carrier']] = $carrier['id_carrier'];
                }
            }
            if (empty($carrierList)) {
                return [];
            }//no linked carrier are available for this zone
        }

        // The product is not dirrectly linked with a carrier
        // Get all the carriers linked to a warehouse
        if ($idWarehouse) {
            $warehouse = new Warehouse($idWarehouse);
            $warehouseCarrierList = $warehouse->getCarriers();
        }

        $availableCarrierList = [];
        $cacheId = 'Carrier::getAvailableCarrierList_getCarriersForOrder_'.(int) $idZone.'-'.(int) $cart->id;
        if (!Cache::isStored($cacheId)) {
            $customer = new Customer($cart->id_customer);
            $carrierError = [];
            $carriers = static::getCarriersForOrder($idZone, $customer->getGroups(), $cart, $carrierError);
            Cache::store($cacheId, [$carriers, $carrierError]);
        } else {
            list($carriers, $carrierError) = Cache::retrieve($cacheId);
        }

        if (! $carriers) {
            $error = array_merge($error, $carrierError);
        }

        foreach ($carriers as $carrier) {
            $availableCarrierList[$carrier['id_carrier']] = $carrier['id_carrier'];
        }

        if ($carrierList) {
            $carrierList = array_intersect($availableCarrierList, $carrierList);
        } else {
            $carrierList = $availableCarrierList;
        }

        if (isset($warehouseCarrierList)) {
            $carrierList = array_intersect($carrierList, $warehouseCarrierList);
        }

        $cartWeight = 0;

        foreach ($cart->getProducts(false, false) as $cartProduct) {
            if (isset($cartProduct['weight_attribute']) && $cartProduct['weight_attribute'] > 0) {
                $cartWeight += ($cartProduct['weight_attribute'] * $cartProduct['cart_quantity']);
            } else {
                $cartWeight += ($cartProduct['weight'] * $cartProduct['cart_quantity']);
            }
        }

        $productWeight = $product->getWeight($combinationId);

        foreach ($carrierList as $key => $idCarrier) {
            $carrier = new Carrier($idCarrier);

            // Get the sizes of the carrier and the product and sort them to check if the carrier can take the product.
            $carrierSizes = [(int) $carrier->max_width, (int) $carrier->max_height, (int) $carrier->max_depth];
            $productSizes = [
                (int)round($product->getWidth($combinationId)),
                (int)round($product->getHeight($combinationId)),
                (int)round($product->getDepth($combinationId))
            ];
            rsort($carrierSizes, SORT_NUMERIC);
            rsort($productSizes, SORT_NUMERIC);

            if (($carrierSizes[0] > 0 && $carrierSizes[0] < $productSizes[0])
                || ($carrierSizes[1] > 0 && $carrierSizes[1] < $productSizes[1])
                || ($carrierSizes[2] > 0 && $carrierSizes[2] < $productSizes[2])
            ) {
                $error[$carrier->id] = static::SHIPPING_SIZE_EXCEPTION;
                unset($carrierList[$key]);
            }

            if ($carrier->min_total > 0 && ($carrier->min_total > $cart->getOrderTotal($carrier->min_total_tax, Cart::BOTH_WITHOUT_SHIPPING))) {
                $error[$carrier->id] = static::SHIPPING_PRICE_EXCEPTION;
                unset($carrierList[$key]);
            }

            if ($carrier->max_total > 0 && ($carrier->max_total < $cart->getOrderTotal($carrier->max_total_tax, Cart::BOTH_WITHOUT_SHIPPING))) {
                $error[$carrier->id] = static::SHIPPING_PRICE_EXCEPTION;
                unset($carrierList[$key]);
            }

            if ($carrier->min_weight > 0 && $cartWeight < $carrier->min_weight) {
                $error[$carrier->id] = static::SHIPPING_WEIGHT_EXCEPTION;
                unset($carrierList[$key]);
            }

            if ($carrier->max_weight > 0 && $cartWeight > $carrier->max_weight) {
                $error[$carrier->id] = static::SHIPPING_WEIGHT_EXCEPTION;
                unset($carrierList[$key]);
            }

            if ($carrier->max_weight > 0 && $productWeight > $carrier->max_weight) {
                $error[$carrier->id] = static::SHIPPING_WEIGHT_EXCEPTION;
                unset($carrierList[$key]);
            }
        }

        return $carrierList;
    }

    /**
     * @param int $idCarrier
     * @param int $idZone
     *
     * @return int|null
     *
     * @throws PrestaShopException
     */
    public static function checkCarrierZone($idCarrier, $idZone)
    {
        $cacheId = 'Carrier::checkCarrierZone_'.(int) $idCarrier.'-'.(int) $idZone;
        if (!Cache::isStored($cacheId)) {
            $result = Db::readOnly()->getValue(
                (new DbQuery())
                    ->select('c.`id_carrier`')
                    ->from('carrier', 'c')
                    ->leftJoin('carrier_zone', 'cz', 'cz.`id_carrier` = c.`id_carrier`')
                    ->leftJoin('zone', 'z', 'z.`id_zone` = '.(int) $idZone)
                    ->where('c.`id_carrier` = '.(int) $idCarrier)
                    ->where('c.`deleted` = 0')
                    ->where('c.`active` = 1')
                    ->where('cz.`id_zone` = '.(int) $idZone)
                    ->where('z.`active` = 1')
            );
            Cache::store($cacheId, $result);
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @param int $idZone
     * @param array $groups group of the customer
     * @param Cart|null $cart
     * @param array $error contains an error message if an error occurs
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCarriersForOrder($idZone, $groups = null, $cart = null, &$error = [])
    {
        $context = Context::getContext();
        $idLang = $context->language->id;
        if (is_null($cart)) {
            $cart = $context->cart;
        }
        if (isset($context->currency)) {
            $idCurrency = $context->currency->id;
        }

        if (is_array($groups) && !empty($groups)) {
            $result = static::getCarriers($idLang, true, false, (int) $idZone, $groups, static::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
        } else {
            $result = static::getCarriers($idLang, true, false, (int) $idZone, [Configuration::get('PS_UNIDENTIFIED_GROUP')], static::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
        }
        $resultsArray = [];

        foreach ($result as $k => $row) {
            $carrier = new Carrier((int) $row['id_carrier']);
            $shippingMethod = $carrier->getShippingMethod();
            if ($shippingMethod != static::SHIPPING_METHOD_FREE) {
                // Get only carriers that are compliant with shipping method
                if (($shippingMethod === static::SHIPPING_METHOD_WEIGHT && $carrier->getMaxDeliveryPriceByWeight($idZone) === false)) {
                    $error[$carrier->id] = static::SHIPPING_WEIGHT_EXCEPTION;
                    unset($result[$k]);
                    continue;
                }
                if (($shippingMethod === static::SHIPPING_METHOD_PRICE && $carrier->getMaxDeliveryPriceByPrice($idZone) === false)) {
                    $error[$carrier->id] = static::SHIPPING_PRICE_EXCEPTION;
                    unset($result[$k]);
                    continue;
                }

                // If out-of-range behavior carrier is set on "Desactivate carrier"
                if ($row['range_behavior']) {
                    // Get id zone
                    if (!$idZone) {
                        $idZone = (int) Country::getIdZone($context->country->id);
                    }

                    // Get only carriers that have a range compatible with cart
                    if ($shippingMethod === static::SHIPPING_METHOD_WEIGHT
                        && (!static::checkDeliveryPriceByWeight($row['id_carrier'], $cart->getTotalWeight(), $idZone))
                    ) {
                        $error[$carrier->id] = static::SHIPPING_WEIGHT_EXCEPTION;
                        unset($result[$k]);
                        continue;
                    }

                    if ($shippingMethod === static::SHIPPING_METHOD_PRICE
                        && (!static::checkDeliveryPriceByPrice($row['id_carrier'], $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING), $idZone, $idCurrency))
                    ) {
                        $error[$carrier->id] = static::SHIPPING_PRICE_EXCEPTION;
                        unset($result[$k]);
                        continue;
                    }
                }
            }

            $row['price'] = (($shippingMethod === static::SHIPPING_METHOD_FREE) ? 0 : $cart->getPackageShippingCost((int) $row['id_carrier'], true, null, null, $idZone));
            $row['price_tax_exc'] = (($shippingMethod === static::SHIPPING_METHOD_FREE) ? 0 : $cart->getPackageShippingCost((int) $row['id_carrier'], false, null, null, $idZone));
            $fileExtension = ImageManager::getDefaultImageExtension();
            $row['img'] = file_exists(_PS_SHIP_IMG_DIR_.(int) $row['id_carrier'].'.'.$fileExtension) ? _THEME_SHIP_DIR_.(int) $row['id_carrier'].'.'.$fileExtension : '';

            // If price is false, then the carrier is unavailable (carrier module)
            if ($row['price'] === false) {
                unset($result[$k]);
                continue;
            }
            $resultsArray[] = $row;
        }

        // if we have to sort carriers by price
        $prices = [];
        if (Configuration::get('PS_CARRIER_DEFAULT_SORT') === static::SORT_BY_PRICE) {
            foreach ($resultsArray as $r) {
                $prices[] = $r['price'];
            }
            if (Configuration::get('PS_CARRIER_DEFAULT_ORDER') === static::SORT_BY_ASC) {
                array_multisort($prices, SORT_ASC, SORT_NUMERIC, $resultsArray);
            } else {
                array_multisort($prices, SORT_DESC, SORT_NUMERIC, $resultsArray);
            }
        }

        return $resultsArray;
    }

    /**
     * Get all carriers in a given language
     *
     * WARNING: by default this method only returns native carrier and excludes carriers added by modules!
     *
     * @param int $idLang Language id
     * @param bool $active Returns only active carriers when true
     *
     * @param bool $delete
     * @param bool $idZone
     * @param int[]|null $idsGroup
     * @param int $modulesFilters Possible values:
     *                             PS_CARRIERS_ONLY
     *                             CARRIERS_MODULE
     *                             CARRIERS_MODULE_NEED_RANGE
     *                             PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE
     *                             ALL_CARRIERS
     *
     * @return array Carriers
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @todo    Check if the query has been fixed and remove the EXISTS subquery ^MD
     */
    public static function getCarriers($idLang, $active = false, $delete = false, $idZone = false, $idsGroup = null, $modulesFilters = self::PS_CARRIERS_ONLY)
    {
        // Filter by groups and no groups => return empty array
        if ($idsGroup && (!is_array($idsGroup) || !count($idsGroup))) {
            return [];
        }

        $sql = (new DbQuery())
            ->select('c.*, cl.`delay`, cl.`display_name`')
            ->from('carrier', 'c')
            ->leftJoin('carrier_lang', 'cl', 'c.`id_carrier` = cl.`id_carrier` AND cl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('cl'))
            ->leftJoin('carrier_zone', 'cz', 'cz.`id_carrier` = c.`id_carrier`')
            ->join(Shop::addSqlAssociation('carrier', 'c'))
            ->where('c.`deleted` = '.($delete ? '1' : '0'));
        if ($idZone) {
            $sql->leftJoin('zone', 'z', 'z.`id_zone` = '.(int) $idZone);
            $sql->where('cz.`id_zone` = '.(int) $idZone);
            $sql->where('z.`active` = 1');
        }
        if ($active) {
            $sql->where('c.`active` = 1');
        }
        if ($idsGroup) {
            $sql->where('EXISTS (SELECT 1 FROM '._DB_PREFIX_.'carrier_group
									WHERE '._DB_PREFIX_.'carrier_group.id_carrier = c.id_carrier
									AND id_group IN ('.implode(',', array_map('intval', $idsGroup)).'))');
        }

        switch ($modulesFilters) {
            case static::PS_CARRIERS_ONLY:
                $sql->where('c.`is_module` = 0');
                break;
            case static::CARRIERS_MODULE:
                $sql->where('c.`is_module` = 1');
                break;
            case static::CARRIERS_MODULE_NEED_RANGE:
                $sql->where('c.`is_module` = 1 AND c.`need_range` = 1');
                break;
            case static::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE:
                $sql->where('c.`is_module` = 0 OR c.`need_range` = 1');
                break;
        }
        $sql->groupBy('c.`id_carrier`');
        $sql->orderBy('c.`position` ASC');

        $cacheId = 'Carrier::getCarriers_'.md5($sql->build());
        if (!Cache::isStored($cacheId)) {
            $carriers = Db::readOnly()->getArray($sql);
            Cache::store($cacheId, $carriers);
        } else {
            $carriers = Cache::retrieve($cacheId);
        }

        foreach ($carriers as &$carrier) {
            if ($carrier['display_name']) {
                $carrier['name'] = $carrier['display_name'];
            }
            $carrier['name'] =  static::expandName($carrier['name']);
        }

        return $carriers;
    }

    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    public function getShippingMethod()
    {
        if ($this->is_free) {
            return static::SHIPPING_METHOD_FREE;
        }

        $method = (int) $this->shipping_method;

        if ($this->shipping_method === static::SHIPPING_METHOD_DEFAULT) {
            // backward compatibility
            if ((int) Configuration::get('PS_SHIPPING_METHOD')) {
                $method = static::SHIPPING_METHOD_WEIGHT;
            } else {
                $method = static::SHIPPING_METHOD_PRICE;
            }
        }

        return $method;
    }

    /**
     * @param int $idZone
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function getMaxDeliveryPriceByWeight($idZone)
    {
        $cacheId = 'Carrier::getMaxDeliveryPriceByWeight_'.(int) $this->id.'-'.(int) $idZone;
        if (!Cache::isStored($cacheId)) {
            $result = Db::readOnly()->getValue(
                (new DbQuery())
                    ->select('d.`price`')
                    ->from('delivery', 'd')
                    ->innerJoin('range_weight', 'w', 'd.`id_range_weight` = w.`id_range_weight`')
                    ->where('d.`id_zone` = '.(int) $idZone)
                    ->where('d.`id_carrier` = '.(int) $this->id.' '.static::sqlDeliveryRangeShop('range_weight'))
                    ->orderBy('w.`delimiter2` DESC')
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @param int $idZone
     *
     * @return float|false
     *
     * @throws PrestaShopException
     */
    public function getMaxDeliveryPriceByPrice($idZone)
    {
        $cacheId = 'Carrier::getMaxDeliveryPriceByPrice_'.(int) $this->id.'-'.(int) $idZone;
        if (!Cache::isStored($cacheId)) {
            $result = Db::readOnly()->getValue(
                (new DbQuery())
                    ->select('d.`price`')
                    ->from('delivery', 'd')
                    ->innerJoin('range_price', 'r', 'd.`id_range_price` = r.`id_range_price`')
                    ->where('d.`id_zone` = '.(int) $idZone)
                    ->where('d.`id_carrier` = '.(int) $this->id.' '.static::sqlDeliveryRangeShop('range_price'))
                    ->orderBy('r.`delimiter2` DESC')
            );
            Cache::store($cacheId, $result);
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @param int $idCarrier
     * @param float $totalWeight
     * @param int $idZone
     *
     * @return float
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function checkDeliveryPriceByWeight($idCarrier, $totalWeight, $idZone)
    {
        $idCarrier = (int) $idCarrier;
        $cacheKey = $idCarrier.'_'.$totalWeight.'_'.$idZone;
        if (!isset(static::$price_by_weight2[$cacheKey])) {
            $result = Db::readOnly()->getRow(
                (new DbQuery())
                    ->select('d.`price`')
                    ->from('delivery', 'd')
                    ->leftJoin('range_weight', 'w', 'd.`id_range_weight` = w.`id_range_weight`')
                    ->where('d.`id_zone` = '.(int) $idZone)
                    ->where((float) $totalWeight.' >= w.`delimiter1`')
                    ->where((float) $totalWeight.' < w.`delimiter2`')
                    ->where('d.`id_carrier` = '.(int) $idCarrier.' '.static::sqlDeliveryRangeShop('range_weight'))
                    ->orderBy('w.`delimiter1` ASC')
            );
            static::$price_by_weight2[$cacheKey] = (isset($result['price']));
        }

        $priceByWeight = Hook::getFirstResponse('actionDeliveryPriceByWeight', ['id_carrier' => $idCarrier, 'total_weight' => $totalWeight, 'id_zone' => $idZone]);
        if (is_numeric($priceByWeight)) {
            static::$price_by_weight2[$cacheKey] = round(
                $priceByWeight,
                _TB_PRICE_DATABASE_PRECISION_
            );
        }

        return static::$price_by_weight2[$cacheKey];
    }

    /**
     * Check delivery prices for a given order
     *
     * @param int $idCarrier
     * @param float $orderTotal Order total to pay
     * @param int $idZone Zone id (for customer delivery address)
     * @param int|null $idCurrency
     *
     * @return float Delivery price
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function checkDeliveryPriceByPrice($idCarrier, $orderTotal, $idZone, $idCurrency = null)
    {
        $idCarrier = (int) $idCarrier;
        $cacheKey = $idCarrier.'_'.$orderTotal.'_'.$idZone.'_'.$idCurrency;
        if (!isset(static::$price_by_price2[$cacheKey])) {
            if (!empty($idCurrency)) {
                $orderTotal = Tools::convertPrice($orderTotal, $idCurrency, false);
            }

            $result = Db::readOnly()->getRow(
                (new DbQuery())
                    ->select('d.`price`')
                    ->from('delivery', 'd')
                    ->leftJoin('range_price', 'r', 'd.`id_range_price` = r.`id_range_price`')
                    ->where('d.`id_zone` = '.(int) $idZone)
                    ->where((float) $orderTotal.' >= r.`delimiter1`')
                    ->where((float) $orderTotal.' < r.`delimiter2`')
                    ->where('d.`id_carrier` = '.(int) $idCarrier.' '.static::sqlDeliveryRangeShop('range_price'))
                    ->orderBy('r.`delimiter1` ASC')
            );
            static::$price_by_price2[$cacheKey] = (isset($result['price']));
        }

        $priceByPrice = Hook::getFirstResponse('actionDeliveryPriceByPrice', ['id_carrier' => $idCarrier, 'order_total' => $orderTotal, 'id_zone' => $idZone]);
        if (is_numeric($priceByPrice)) {
            static::$price_by_price2[$cacheKey] = round(
                $priceByPrice,
                _TB_PRICE_DATABASE_PRECISION_
            );
        }

        return static::$price_by_price2[$cacheKey];
    }

    /**
     * Assign one (ore more) group to all carriers
     *
     * @param int|array $idGroupList group id or list of group ids
     * @param array $exception list of id carriers to ignore
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function assignGroupToAllCarriers($idGroupList, $exception = null)
    {
        if (!is_array($idGroupList)) {
            $idGroupList = [$idGroupList];
        }

        Db::getInstance()->delete(
            'carrier_group',
            '`id_group` IN ('.implode(',', $idGroupList).')'
        );

        $carrierList = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_carrier`')
                ->from('carrier')
                ->where('`deleted` = 0')
                ->where(is_array($exception) ? '`id_carrier` NOT IN ('.implode(',', $exception).')' : '')
        );

        if ($carrierList) {
            $data = [];
            foreach ($carrierList as $carrier) {
                foreach ($idGroupList as $idGroup) {
                    $data[] = [
                        'id_carrier' => $carrier['id_carrier'],
                        'id_group'   => $idGroup,
                    ];
                }
            }

            return Db::getInstance()->insert('carrier_group', $data, false, false, Db::INSERT);
        }

        return true;
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if ($this->position <= 0) {
            $this->position = static::getHigherPosition() + 1;
        }

        $this->fixNames();

        if (!parent::add($autoDate, $nullValues) || !Validate::isLoadedObject($this)) {
            return false;
        }
        if (!$count = Db::readOnly()->getValue('SELECT count(`id_carrier`) FROM `'._DB_PREFIX_.$this->def['table'].'` WHERE `deleted` = 0')) {
            return false;
        }
        if ($count == 1) {
            Configuration::updateValue('PS_CARRIER_DEFAULT', (int) $this->id);
        }

        // Register reference
        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.$this->def['table'].'` SET `id_reference` = '.$this->id.' WHERE `id_carrier` = '.$this->id);

        return true;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        $this->fixNames();
        return parent::update($nullValues);
    }

    /**
     * Gets the highest carrier position
     *
     * @return int $position
     *
     * @throws PrestaShopException
     */
    public static function getHigherPosition()
    {
        $position = Db::readOnly()->getValue(
            (new DbQuery())
                ->select('MAX(`position`)')
                ->from('carrier')
                ->where('`deleted` = 0')
        );

        return (is_numeric($position)) ? $position : -1;
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }
        static::cleanPositions();

        $conn = Db::getInstance();
        return ($conn->delete('cart_rule_carrier', '`id_carrier` = '.(int) $this->id)
            && $conn->delete('module_carrier', '`id_reference` = '.(int) $this->id_reference)
            && $this->deleteTaxRulesGroup(Shop::getShops(true, null, true)));
    }

    /**
     * Reorders carrier positions.
     * Called after deleting a carrier.
     *
     * @return bool $return
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function cleanPositions()
    {
        $return = true;

        $result = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_carrier`')
                ->from('carrier')
                ->where('`deleted` = 0')
                ->orderBy('`position` ASC')
        );

        $i = 0;
        foreach ($result as $value) {
            $return = Db::getInstance()->update(
                'carrier',
                [
                    'position' => (int) $i++,
                ],
                '`id_carrier` = '.(int) $value['id_carrier']
            );
        }

        return $return;
    }

    /**
     * @param array|null $shops
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteTaxRulesGroup(?array $shops = null)
    {
        if (!$shops) {
            $shops = Shop::getContextListShopID();
        }

        $where = 'id_carrier = '.(int) $this->id;
        if ($shops) {
            $where .= ' AND id_shop IN('.implode(', ', array_map('intval', $shops)).')';
        }

        return Db::getInstance()->delete('carrier_tax_rules_group_shop', $where);
    }

    /**
     * Change carrier id in delivery prices when updating a carrier
     *
     * @param int $idOld Old id carrier
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setConfiguration($idOld)
    {
        Db::getInstance()->update(
            'delivery',
            [
                'id_carrier' => (int) $this->id,
            ],
            '`id_carrier` = '.(int) $idOld
        );
    }

    /**
     * Get delivery prices for a given order
     *
     * @param float $totalWeight Total order weight
     * @param int $idZone Zone ID (for customer delivery address)
     *
     * @return float Delivery price
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getDeliveryPriceByWeight($totalWeight, $idZone)
    {
        $idCarrier = (int) $this->id;
        $cacheKey = $idCarrier.'_'.$totalWeight.'_'.$idZone;
        if (!isset(static::$price_by_weight[$cacheKey])) {
            $result = Db::readOnly()->getRow(
                (new DbQuery())
                    ->select('d.`price`')
                    ->from('delivery', 'd')
                    ->leftJoin('range_weight', 'w', 'd.`id_range_weight` = w.`id_range_weight`')
                    ->where('d.`id_zone` = '.(int) $idZone)
                    ->where((float) $totalWeight.' >= w.`delimiter1`')
                    ->where((float) $totalWeight.' < w.`delimiter2`')
                    ->where('d.`id_carrier` = '.(int) $idCarrier.' '.static::sqlDeliveryRangeShop('range_weight'))
                    ->orderBy('w.`delimiter1` ASC')
            );
            if (!isset($result['price'])) {
                static::$price_by_weight[$cacheKey] = $this->getMaxDeliveryPriceByWeight($idZone);
            } else {
                static::$price_by_weight[$cacheKey] = $result['price'];
            }
        }

        $priceByWeight = Hook::getFirstResponse('actionDeliveryPriceByWeight', ['id_carrier' => $idCarrier, 'total_weight' => $totalWeight, 'id_zone' => $idZone]);
        if (is_numeric($priceByWeight)) {
            static::$price_by_weight[$cacheKey] = round(
                $priceByWeight,
                _TB_PRICE_DATABASE_PRECISION_
            );
        }

        return static::$price_by_weight[$cacheKey];
    }

    /**
     * Get delivery prices for a given order
     *
     * @param float $orderTotal Order total to pay
     * @param int $idZone Zone id (for customer delivery address)
     * @param int|null $idCurrency
     *
     * @return float Delivery price
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getDeliveryPriceByPrice($orderTotal, $idZone, $idCurrency = null)
    {
        $idCarrier = (int) $this->id;
        $cacheKey = $this->id.'_'.$orderTotal.'_'.$idZone.'_'.$idCurrency;
        if (!isset(static::$price_by_price[$cacheKey])) {
            if (!empty($idCurrency)) {
                $orderTotal = Tools::convertPrice($orderTotal, $idCurrency, false);
            }
            $result = Db::readOnly()->getRow(
                (new DbQuery())
                    ->select('d.`price`')
                    ->from('delivery', 'd')
                    ->leftJoin('range_price', 'r', 'd.`id_range_price` = r.`id_range_price`')
                    ->where('d.`id_zone` = '.(int) $idZone)
                    ->where((float) $orderTotal.' >= r.`delimiter1`')
                    ->where((float) $orderTotal.' < r.`delimiter2`')
                    ->where('d.`id_carrier` = '.(int) $idCarrier.' '.static::sqlDeliveryRangeShop('range_price'))
                    ->orderBy('r.`delimiter1` ASC')
            );
            if (!isset($result['price'])) {
                static::$price_by_price[$cacheKey] = $this->getMaxDeliveryPriceByPrice($idZone);
            } else {
                static::$price_by_price[$cacheKey] = $result['price'];
            }
        }

        $priceByPrice = Hook::getFirstResponse('actionDeliveryPriceByPrice', ['id_carrier' => $idCarrier, 'order_total' => $orderTotal, 'id_zone' => $idZone]);
        if (is_numeric($priceByPrice)) {
            static::$price_by_price[$cacheKey] = round(
                $priceByPrice,
                _TB_PRICE_DATABASE_PRECISION_
            );
        }

        return static::$price_by_price[$cacheKey];
    }

    /**
     * Get all zones
     *
     * @return array Zones
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getZones()
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('*')
                ->from('carrier_zone', 'cz')
                ->leftJoin('zone', 'z', 'cz.`id_zone` = z.`id_zone`')
                ->where('cz.`id_carrier` = '.(int) $this->id)
        );
    }

    /**
     * Get a specific zones
     *
     * @param int $idZone
     *
     * @return array Zone
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getZone($idZone)
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('*')
                ->from('carrier_zone')
                ->where('`id_carrier` = '.(int) $this->id)
                ->where('`id_zone` = '.(int) $idZone)
        );
    }

    /**
     * Add zone
     *
     * @param int $idZone
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addZone($idZone)
    {
        $conn = Db::getInstance();
        if ($conn->insert(
            'carrier_zone',
            [
                'id_carrier' => (int) $this->id,
                'id_zone'    => (int) $idZone,
            ]
        )) {
            // Get all ranges for this carrier
            $rangePrices = RangePrice::getRanges($this->id);
            $rangeWeights = RangeWeight::getRanges($this->id);
            // Create row in ps_delivery table
            if (count($rangePrices) || count($rangeWeights)) {
                $insert = [];
                if (count($rangePrices)) {
                    foreach ($rangePrices as $range) {
                        $insert[] = [
                            'id_carrier'      => (int) $this->id,
                            'id_range_price'  => (int) $range['id_range_price'],
                            'id_range_weight' => 0,
                            'id_zone'         => (int) $idZone,
                            'price'           => 0,
                        ];
                    }
                }

                if (count($rangeWeights)) {
                    foreach ($rangeWeights as $range) {
                        $insert[] = [
                            'id_carrier'      => (int) $this->id,
                            'id_range_price'  => 0,
                            'id_range_weight' => (int) $range['id_range_weight'],
                            'id_zone'         => (int) $idZone,
                            'price'           => 0,
                        ];
                    }
                }

                return $conn->insert('delivery', $insert);
            }

            return true;
        }

        return false;
    }

    /**
     * Delete zone
     *
     * @param int $idZone
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteZone($idZone)
    {
        if (Db::getInstance()->delete(
            'carrier_zone',
            '`id_carrier` = '.(int) $this->id.' AND `id_zone` = '.(int) $idZone,
            1
        )) {
            return Db::getInstance()->delete(
                'delivery',
                '`id_carrier` = '.(int) $this->id.' AND `id_zone` = '.(int) $idZone
            );
        }

        return false;
    }

    /**
     * Gets a specific group
     *
     * @return array Group
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getGroups()
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_group`')
                ->from('carrier_group')
                ->where('`id_carrier` = '.(int) $this->id)
        );
    }

    /**
     * Clean delivery prices (weight/price)
     *
     * @param string $rangeTable Table name to clean (weight or price according to shipping method)
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteDeliveryPrice($rangeTable)
    {
        $where = '`id_carrier` = '.(int) $this->id.' AND (`id_'.bqSQL($rangeTable).'` IS NOT NULL OR `id_'.bqSQL($rangeTable).'` = 0) ';

        if (Shop::getContext() == Shop::CONTEXT_ALL) {
            $where .= 'AND id_shop IS NULL AND id_shop_group IS NULL';
        } elseif (Shop::getContext() == Shop::CONTEXT_GROUP) {
            $where .= 'AND id_shop IS NULL AND id_shop_group = '.(int) Shop::getContextShopGroupID();
        } else {
            $where .= 'AND id_shop = '.(int) Shop::getContextShopID();
        }

        return Db::getInstance()->delete('delivery', $where);
    }

    /**
     * Add new delivery prices
     *
     * @param array $priceList Prices list in multiple arrays (changed to array since 1.5.0)
     * @param bool $delete
     *
     * @return bool Insertion result
     *
     * @throws PrestaShopException
     */
    public function addDeliveryPrice($priceList, $delete = false)
    {
        if (!$priceList) {
            return false;
        }

        $keys = array_keys($priceList[0]);
        if (!in_array('id_shop', $keys)) {
            $keys[] = 'id_shop';
        }
        if (!in_array('id_shop_group', $keys)) {
            $keys[] = 'id_shop_group';
        }

        $sql = 'INSERT INTO `'._DB_PREFIX_.'delivery` ('.implode(', ', $keys).') VALUES ';
        $db = Db::getInstance();
        foreach ($priceList as $values) {
            if (!isset($values['id_shop'])) {
                $values['id_shop'] = (Shop::getContext() == Shop::CONTEXT_SHOP) ? Shop::getContextShopID() : null;
            }
            if (!isset($values['id_shop_group'])) {
                $values['id_shop_group'] = (Shop::getContext() != Shop::CONTEXT_ALL) ? Shop::getContextShopGroupID() : null;
            }

            if ($delete) {
                $db->execute(
                    'DELETE FROM `'._DB_PREFIX_.'delivery`
                    WHERE '.(is_null($values['id_shop']) ? 'ISNULL(`id_shop`) ' : 'id_shop = '.(int) $values['id_shop']).'
                    AND '.(is_null($values['id_shop_group']) ? 'ISNULL(`id_shop`) ' : 'id_shop_group='.(int) $values['id_shop_group']).'
                    AND id_carrier='.(int) $values['id_carrier'].
                    ($values['id_range_price'] !== null ? ' AND id_range_price='.(int) $values['id_range_price'] : ' AND (ISNULL(`id_range_price`) OR `id_range_price` = 0)').
                    ($values['id_range_weight'] !== null ? ' AND id_range_weight='.(int) $values['id_range_weight'] : ' AND (ISNULL(`id_range_weight`) OR `id_range_weight` = 0)').'
					AND id_zone='.(int) $values['id_zone']
                );
            }

            $sql .= '(';
            foreach ($values as $v) {
                if (is_null($v)) {
                    $sql .= 'NULL';
                } elseif (is_int($v) || is_float($v)) {
                    $sql .= $v;
                } else {
                    $sql .= '\''.$v.'\'';
                }
                $sql .= ', ';
            }
            $sql = rtrim($sql, ', ').'), ';
        }
        $sql = rtrim($sql, ', ');

        return $db->execute($sql);
    }

    /**
     * Copy old carrier informations when update carrier
     *
     * @param int $oldId Old id carrier (copy from that id)
     *
     * @return void
     * @throws PrestaShopException
     */
    public function copyCarrierData($oldId)
    {
        if (!Validate::isUnsignedId($oldId)) {
            throw new PrestaShopException('Incorrect identifier for carrier');
        }

        if (!$this->id) {
            return;
        }

        $fileExtension = ImageManager::getDefaultImageExtension();

        if ($sourceFile = ImageManager::getSourceImage(_PS_SHIP_IMG_DIR_, $oldId)) {
            copy($sourceFile, _PS_SHIP_IMG_DIR_.'/'.(int) $this->id.'.'.$fileExtension);
        }

        if ($sourceFileOldTmpLogo = ImageManager::getSourceImage(_PS_TMP_IMG_DIR_, 'carrier_mini_'.(int) $oldId)) {
            if (!isset($_FILES['logo'])) {
                copy($sourceFileOldTmpLogo, _PS_TMP_IMG_DIR_.'/carrier_mini_'.$this->id.'.'.$fileExtension);
            }
            unlink($sourceFileOldTmpLogo);
        }

        // Copy existing ranges price
        $conn = Db::getInstance();
        foreach (['range_price', 'range_weight'] as $range) {
            $res = $conn->getArray(
                (new DbQuery())
                    ->select('`id_'.$range.'` as `id_range`, `delimiter1`, `delimiter2`')
                    ->from($range)
                    ->where('`id_carrier` = '.(int) $oldId)
            );
            if (count($res)) {
                foreach ($res as $val) {
                    $conn->insert(
                        $range,
                        [
                            'id_carrier' => (int) $this->id,
                            'delimiter1' => (float) $val['delimiter1'],
                            'delimiter2' => (float) $val['delimiter2'],
                        ]
                    );
                    $idRange = (int) $conn->Insert_ID();

                    $idRangePrice = ($range == 'range_price') ? $idRange : 'NULL';
                    $idRangeWeight = ($range == 'range_weight') ? $idRange : 'NULL';

                    $conn->execute(
                        '
						INSERT INTO `'._DB_PREFIX_.'delivery` (`id_carrier`, `id_shop`, `id_shop_group`, `id_range_price`, `id_range_weight`, `id_zone`, `price`) (
							SELECT '.(int) $this->id.', `id_shop`, `id_shop_group`, '.(int) $idRangePrice.', '.(int) $idRangeWeight.', `id_zone`, `price`
							FROM `'._DB_PREFIX_.'delivery`
							WHERE `id_carrier` = '.(int) $oldId.'
							AND `id_'.$range.'` = '.(int) $val['id_range'].'
						)
					'
                    );
                }
            }
        }

        // Copy existing zones
        $res = $conn->getArray(
            (new DbQuery())
                ->select('*')
                ->from('carrier_zone')
                ->where('`id_carrier` = '.(int) $oldId)
        );
        foreach ($res as $val) {
            $conn->insert(
                'carrier_zone',
                [
                    'id_carrier' => (int) $this->id,
                    'id_zone'    => (int) $val['id_zone'],
                ]
            );
        }

        //Copy default carrier
        if (Configuration::get('PS_CARRIER_DEFAULT') == $oldId) {
            Configuration::updateValue('PS_CARRIER_DEFAULT', (int) $this->id);
        }

        // Copy reference
        $idReference = $conn->getValue(
            (new DbQuery())
                ->select('`id_reference`')
                ->from(bqSQL(static::$definition['table']))
                ->where('`id_carrier` = '.(int) $oldId)
        );
        $conn->update(
            bqSQL(static::$definition['table']),
            [
                'id_reference' => (int) $idReference,
            ],
            '`id_carrier` = '.(int) $this->id
        );

        $this->id_reference = (int) $idReference;

        // Copy tax rules group
        $conn->execute(
            'INSERT INTO `'._DB_PREFIX_.'carrier_tax_rules_group_shop` (`id_carrier`, `id_tax_rules_group`, `id_shop`)
												(SELECT '.(int) $this->id.', `id_tax_rules_group`, `id_shop`
													FROM `'._DB_PREFIX_.'carrier_tax_rules_group_shop`
													WHERE `id_carrier`='.(int) $oldId.')'
        );
    }

    /**
     * Check if carrier is used (at least one order placed)
     *
     * @return int Order count for this carrier
     *
     * @throws PrestaShopException
     */
    public function isUsed()
    {
        $row = Db::readOnly()->getValue(
            (new DbQuery())
                ->select('COUNT(`id_carrier`) as `total`')
                ->from('orders')
                ->where('`id_carrier` = '.(int) $this->id)
        );

        return (int) $row['total'];
    }

    /**
     * @return bool|string
     *
     * @throws PrestaShopException
     */
    public function getRangeTable()
    {
        $shippingMethod = $this->getShippingMethod();
        if ($shippingMethod === static::SHIPPING_METHOD_WEIGHT) {
            return 'range_weight';
        } elseif ($shippingMethod === static::SHIPPING_METHOD_PRICE) {
            return 'range_price';
        }

        return false;
    }

    /**
     * @param bool $shippingMethod
     *
     * @return bool|RangePrice|RangeWeight
     *
     * @throws PrestaShopException
     */
    public function getRangeObject($shippingMethod = false)
    {
        if (!$shippingMethod) {
            $shippingMethod = $this->getShippingMethod();
        }

        if ((int) $shippingMethod === static::SHIPPING_METHOD_WEIGHT) {
            return new RangeWeight();
        } elseif ((int) $shippingMethod === static::SHIPPING_METHOD_PRICE) {
            return new RangePrice();
        }

        return false;
    }

    /**
     * @param Currency|null $currency
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getRangeSuffix($currency = null)
    {
        if (!$currency) {
            $currency = Context::getContext()->currency;
        }
        $suffix = Configuration::get('PS_WEIGHT_UNIT');
        if ($this->getShippingMethod() === static::SHIPPING_METHOD_PRICE) {
            $suffix = $currency->sign;
        }

        return $suffix;
    }

    /**
     * @param int $idTaxRulesGroup
     * @param bool $allShops
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setTaxRulesGroup($idTaxRulesGroup, $allShops = false)
    {
        if (!Validate::isUnsignedId($idTaxRulesGroup)) {
            throw new PrestaShopException("Invalid tax rules group ID");
        }

        if (!$allShops) {
            $shops = Shop::getContextListShopID();
        } else {
            $shops = Shop::getShops(true, null, true);
        }

        $this->deleteTaxRulesGroup($shops);

        $values = [];
        foreach ($shops as $idShop) {
            $values[] = [
                'id_carrier'         => (int) $this->id,
                'id_tax_rules_group' => (int) $idTaxRulesGroup,
                'id_shop'            => (int) $idShop,
            ];
        }
        Cache::clean('carrier_id_tax_rules_group_'.(int) $this->id.'_'.(int) Context::getContext()->shop->id);

        return Db::getInstance()->insert('carrier_tax_rules_group_shop', $values);
    }

    /**
     * @param string $idTaxRulesGroup
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function setWsTaxRulesGroup($idTaxRulesGroup)
    {
        return $this->setTaxRulesGroup((int)$idTaxRulesGroup);
    }

    /**
     * Returns the taxes rate associated to the carrier
     *
     * @param Address $address
     *
     * @return float
     *
     * @throws PrestaShopException
     */
    public function getTaxesRate(Address $address)
    {
        $taxCalculator = $this->getTaxCalculator($address);

        return $taxCalculator->getTotalRate();
    }

    /**
     * Returns the taxes calculator associated to the carrier
     *
     * @param Address $address
     * @param int|null $idOrder
     * @param bool $useAverageTaxOfProducts
     *
     * @return AverageTaxOfProductsTaxCalculator|TaxCalculator
     * @throws PrestaShopException
     */
    public function getTaxCalculator(Address $address, $idOrder = null, $useAverageTaxOfProducts = false)
    {
        if ($useAverageTaxOfProducts) {
            return Adapter_ServiceLocator::get('AverageTaxOfProductsTaxCalculator')->setIdOrder($idOrder);
        } else {
            $taxManager = TaxManagerFactory::getManager($address, $this->getIdTaxRulesGroup());

            return $taxManager->getTaxCalculator();
        }
    }

    /**
     * Moves a carrier
     *
     * @param bool $way Up (1) or Down (0)
     * @param int $position
     *
     * @return bool Update result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updatePosition($way, $position)
    {
        if (!$res = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_carrier`, `position`')
                ->from('carrier')
                ->where('`deleted` = 0')
                ->orderBy('`position` ASC')
        )) {
            return false;
        }

        foreach ($res as $carrier) {
            if ((int) $carrier['id_carrier'] == (int) $this->id) {
                $movedCarrier = $carrier;
            }
        }

        if (!isset($movedCarrier) || !isset($position)) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $conn = Db::getInstance();
        return $conn->update(
            'carrier',
            [
                'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
            ],
            '`position` '.($way ? '> '.(int) $movedCarrier['position'].' AND `position` <= '.(int) $position : '< '.(int) $movedCarrier['position'].' AND `position` >= '.(int) $position.' AND `deleted` = 0')
        ) && $conn->update(
            'carrier',
            [
                'position' => (int) $position,
            ],
            '`id_carrier` = '.(int) $movedCarrier['id_carrier']
        );
    }

    /**
     * @param array $groups
     * @param bool $delete
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setGroups($groups, $delete = true)
    {
        if ($delete) {
            Db::getInstance()->delete('carrier_group', '`id_carrier` = '.(int) $this->id);
        }
        if (!is_array($groups) || !count($groups)) {
            return true;
        }

        $insert = [];

        foreach ($groups as $idGroup) {
            $insert[] = [
                'id_carrier' => (int) $this->id,
                'id_group'   => (int) $idGroup,
            ];
        }

        return Db::getInstance()->insert('carrier_group', $insert);
    }

    /**
     * @param TableSchema $table
     */
    public static function processTableSchema($table)
    {
        if ($table->getNameWithoutPrefix() === 'carrier_lang') {
            $table->reorderColumns(['id_carrier', 'id_shop', 'id_lang']);
        }
    }

    /**
     * Helper method to resolve $carrier->name based on displayable name. Property $carrier->name
     * exists for backwards compatibility only, should not be used directly
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    protected function fixNames()
    {
        // if display_name is not assigned, but name is, we use it for initialization
        if (is_array($this->display_name)) {
            $this->display_name = array_filter($this->display_name);
        }
        if (!$this->display_name && is_string($this->name) && strlen($this->name) > 0) {
            if ($this->id_lang) {
                $this->display_name = static::expandName($this->name);
            } else {
                $this->display_name = [];
                foreach (Language::getLanguages(false, false, true) as $lang) {
                    $this->display_name[$lang] = static::expandName($this->name);
                }
            }
        }

        $this->name = $this->getName();
    }

    /**
     * Resolves carrier name
     *
     * @return string
     * @throws PrestaShopException
     */
    public function getName()
    {
        if (is_string($this->display_name) && strlen($this->display_name) > 0) {
            return static::expandName($this->display_name);
        }

        if (is_array($this->display_name)) {
            $languages = array_unique(array_merge(
                array_filter([
                    (int)$this->id_lang,
                    (int)Context::getContext()->language->id,
                    (int)Configuration::get('PS_LANG_DEFAULT'),
                ]),
                Language::getLanguages(true, null, true)
            ));
            foreach ($languages as $lang) {
                if (array_key_exists($lang, $this->display_name)) {
                    $name = $this->display_name[$lang];
                    if (is_string($name) && strlen($name) > 0) {
                        return static::expandName($name);
                    }
                }
            }
        }

        return static::expandName($this->name);
    }

    /**
     * Helper method that expands placeholder carrier name '0' to Shop Name
     *
     * @param string $name
     * @return string
     * @throws PrestaShopException
     */
    public static function expandName($name)
    {
        if ($name) {
            return $name;
        }

        $carrierName = static::getCarrierNameFromShopName();
        return $carrierName ? $carrierName : '0';
    }

    /**
     * @param Db $conn
     * @return void
     * @throws PrestaShopException
     */
    public static function initializationCallback(Db $conn)
    {
        $conn->execute('UPDATE '._DB_PREFIX_.'carrier_lang l '.
            'INNER JOIN '._DB_PREFIX_.'carrier c ON (c.id_carrier = l.id_carrier) '.
            'SET l.display_name = c.name '.
            'WHERE l.display_name = "" AND c.name != ""'
        );
    }
}
