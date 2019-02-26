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
 * Class CarrierCore
 *
 * @since 1.0.0
 */
class CarrierCore extends ObjectModel
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

    // @codingStandardsIgnoreStart
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
    /** @var string Name */
    public $name;
    /** @var string URL with a '@' for */
    public $url;
    /** @var string Delay needed to deliver customer */
    public $delay;
    /** @var bool Carrier statuts */
    public $active = true;
    /** @var bool True if carrier has been deleted (staying in database as deleted) */
    public $deleted = 0;
    /** @var bool Active or not the shipping handling */
    public $shipping_handling = true;
    /** @var int Behavior taken for unknown range */
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
    /** @var int maximum package weight managed by the transporter */
    public $max_weight;
    /** @var int grade of the shipping delay (0 for longest, 9 for shortest) */
    public $grade;
    /** @var int $id_tax_rules_group */
    public $id_tax_rules_group;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'          => 'carrier',
        'primary'        => 'id_carrier',
        'multilang'      => true,
        'multilang_shop' => true,
        'fields'         => [
            /* Classic fields */
            'id_reference'         => ['type' => self::TYPE_INT],
            'name'                 => ['type' => self::TYPE_STRING, 'validate' => 'isCarrierName', 'required' => true, 'size' => 64],
            'active'               => ['type' => self::TYPE_BOOL,   'validate' => 'isBool',        'required' => true              ],
            'is_free'              => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'url'                  => ['type' => self::TYPE_STRING, 'validate' => 'isAbsoluteUrl'                                  ],
            'shipping_handling'    => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'shipping_external'    => ['type' => self::TYPE_BOOL                                                                   ],
            'range_behavior'       => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'shipping_method'      => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                                  ],
            'max_width'            => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                                  ],
            'max_height'           => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                                  ],
            'max_depth'            => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                                  ],
            'max_weight'           => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                                        ],
            'grade'                => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt',                     'size' => 1 ],
            'external_module_name' => ['type' => self::TYPE_STRING,                                                    'size' => 64],
            'is_module'            => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'need_range'           => ['type' => self::TYPE_BOOL                                                                   ],
            'position'             => ['type' => self::TYPE_INT                                                                    ],
            'deleted'              => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'id_tax_rules_group'   => ['type' => self::TYPE_INT,    'validate' => 'isInt'                                          ],

            /* Lang fields */
            'delay'                => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'deleted'            => [],
            'is_module'          => [],
            'id_tax_rules_group' => [
                'getter'         => 'getIdTaxRulesGroup',
                'setter'         => 'setTaxRulesGroup',
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
        }

        if ($this->name == '0') {
            $this->name = static::getCarrierNameFromShopName();
        }

        $this->image_dir = _PS_SHIP_IMG_DIR_;
    }

    /**
     * Hydrate function for the Carrier
     *
     * @param array    $data
     * @param int|null $idLang
     *
     * @return void
     *
     * @since 1.0.2 Fix the hydrate function of the carrier
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

        if ($this->name === '0' && static::getCarrierNameFromShopName()) {
            $this->name = static::getCarrierNameFromShopName();
        }
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
     * @param array    $data
     *
     * @return void
     *
     * @since 1.0.2 Fix the hydrate function of the carrier
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

        if ($this->name === '0' && static::getCarrierNameFromShopName()) {
            $this->name = static::getCarrierNameFromShopName();
        }
    }

    /**
     * @param Context|null $context
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getIdTaxRulesGroup(Context $context = null)
    {
        return static::getIdTaxRulesGroupByIdCarrier((int) $this->id, $context);
    }

    /**
     * @param int          $idCarrier
     * @param Context|null $context
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIdTaxRulesGroupByIdCarrier($idCarrier, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $key = 'carrier_id_tax_rules_group_'.(int) $idCarrier.'_'.(int) $context->shop->id;
        if (!Cache::isStored($key)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
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
     * @param int    $idCarrier
     *
     * @return array Delivery prices
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getDeliveryPriceByRanges($rangeTable, $idCarrier)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     * @since   1.5.0
     *
     * @param string $rangeTable
     * @param string $alias
     *
     * @return string
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIdTaxRulesGroupMostUsed()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
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
     * @param int       $idLang
     * @param bool      $activeCountries
     * @param bool      $activeCarriers
     * @param bool|null $containStates
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getDeliveredCountries($idLang, $activeCountries = false, $activeCarriers = false, $containStates = null)
    {
        if (!Validate::isBool($activeCountries) || !Validate::isBool($activeCarriers)) {
            die(Tools::displayError());
        }

        $states = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('s.*')
                ->from('state', 's')
                ->orderBy('s.`name` ASC')
        );

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     * @param int   $defaultCarrier the last carrier selected
     *
     * @return number the id of the default carrier
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
        $carrierData = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     * @since   1.5.0
     *
     * @param Product $product The id of the product, or an array with at least the package size and weight
     * @param int     $idWarehouse
     * @param int     $idAddressDelivery
     * @param int     $idShop
     * @param Cart    $cart
     * @param array   $error   contains an error message if an error occurs
     *
     * @return array
     * @throws PrestaShopDatabaseException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getAvailableCarrierList(Product $product, $idWarehouse, $idAddressDelivery = null, $idShop = null, $cart = null, &$error = [])
    {
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

        if (is_null($error) || !is_array($error)) {
            $error = [];
        }

        $idAddress = (int) ((!is_null($idAddressDelivery) && $idAddressDelivery != 0) ? $idAddressDelivery : $cart->id_address_delivery);
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

            $carriersForProduct = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
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

        $error = array_merge($error, $carrierError);

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

        $cartQuantity = 0;
        $cartWeight = 0;

        foreach ($cart->getProducts(false, false) as $cartProduct) {
            if ($cartProduct['id_product'] == $product->id) {
                $cartQuantity += $cartProduct['cart_quantity'];
            }
            if (isset($cartProduct['weight_attribute']) && $cartProduct['weight_attribute'] > 0) {
                $cartWeight += ($cartProduct['weight_attribute'] * $cartProduct['cart_quantity']);
            } else {
                $cartWeight += ($cartProduct['weight'] * $cartProduct['cart_quantity']);
            }
        }

        if ($product->width > 0 || $product->height > 0 || $product->depth > 0 || $product->weight > 0 || $cartWeight > 0) {
            foreach ($carrierList as $key => $idCarrier) {
                $carrier = new Carrier($idCarrier);

                // Get the sizes of the carrier and the product and sort them to check if the carrier can take the product.
                $carrierSizes = [(int) $carrier->max_width, (int) $carrier->max_height, (int) $carrier->max_depth];
                $productSizes = [(int) $product->width, (int) $product->height, (int) $product->depth];
                rsort($carrierSizes, SORT_NUMERIC);
                rsort($productSizes, SORT_NUMERIC);

                if (($carrierSizes[0] > 0 && $carrierSizes[0] < $productSizes[0])
                    || ($carrierSizes[1] > 0 && $carrierSizes[1] < $productSizes[1])
                    || ($carrierSizes[2] > 0 && $carrierSizes[2] < $productSizes[2])
                ) {
                    $error[$carrier->id] = static::SHIPPING_SIZE_EXCEPTION;
                    unset($carrierList[$key]);
                }

                if ($carrier->max_weight > 0 && ($carrier->max_weight < $product->weight * $cartQuantity || $carrier->max_weight < $cartWeight)) {
                    $error[$carrier->id] = static::SHIPPING_WEIGHT_EXCEPTION;
                    unset($carrierList[$key]);
                }
            }
        }

        return $carrierList;
    }

    /**
     * @param int $idCarrier
     * @param int $idZone
     *
     * @return null|int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function checkCarrierZone($idCarrier, $idZone)
    {
        $cacheId = 'Carrier::checkCarrierZone_'.(int) $idCarrier.'-'.(int) $idZone;
        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
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
     *
     * @param int       $idZone
     * @param array     $groups group of the customer
     * @param Cart|null $cart
     * @param array     $error  contains an error message if an error occurs
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
                        $idZone = (int) Country::getIdZone(Country::getDefaultCountryId());
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

            $row['name'] = (strval($row['name']) != '0' ? $row['name'] : static::getCarrierNameFromShopName());
            $row['price'] = (($shippingMethod === static::SHIPPING_METHOD_FREE) ? 0 : $cart->getPackageShippingCost((int) $row['id_carrier'], true, null, null, $idZone));
            $row['price_tax_exc'] = (($shippingMethod === static::SHIPPING_METHOD_FREE) ? 0 : $cart->getPackageShippingCost((int) $row['id_carrier'], false, null, null, $idZone));
            $row['img'] = file_exists(_PS_SHIP_IMG_DIR_.(int) $row['id_carrier'].'.jpg') ? _THEME_SHIP_DIR_.(int) $row['id_carrier'].'.jpg' : '';

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
     * @param int  $idLang         Language id
     * @param bool $active         Returns only active carriers when true
     *
     * @param bool $delete
     * @param bool $idZone
     * @param null $idsGroup
     * @param int  $modulesFilters Possible values:
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
            ->select('c.*, cl.`delay`')
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
            $carriers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            Cache::store($cacheId, $carriers);
        } else {
            $carriers = Cache::retrieve($cacheId);
        }

        foreach ($carriers as $key => $carrier) {
            if ($carrier['name'] == '0') {
                $carriers[$key]['name'] = static::getCarrierNameFromShopName();
            }
        }

        return $carriers;
    }

    /**
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getMaxDeliveryPriceByWeight($idZone)
    {
        $cacheId = 'Carrier::getMaxDeliveryPriceByWeight_'.(int) $this->id.'-'.(int) $idZone;
        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
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
     * @return null
     *
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getMaxDeliveryPriceByPrice($idZone)
    {
        $cacheId = 'Carrier::getMaxDeliveryPriceByPrice_'.(int) $this->id.'-'.(int) $idZone;
        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
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
     * @param int   $idCarrier
     * @param float $totalWeight
     * @param int   $idZone
     *
     * @return mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function checkDeliveryPriceByWeight($idCarrier, $totalWeight, $idZone)
    {
        $idCarrier = (int) $idCarrier;
        $cacheKey = $idCarrier.'_'.$totalWeight.'_'.$idZone;
        // @codingStandardsIgnoreStart
        if (!isset(static::$price_by_weight2[$cacheKey])) {
            // @codingStandardsIgnoreEnd
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
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
            // @codingStandardsIgnoreStart
            static::$price_by_weight2[$cacheKey] = (isset($result['price']));
            // @codingStandardsIgnoreEnd
        }

        $priceByWeight = Hook::exec('actionDeliveryPriceByWeight', ['id_carrier' => $idCarrier, 'total_weight' => $totalWeight, 'id_zone' => $idZone]);
        if (is_numeric($priceByWeight)) {
            // @codingStandardsIgnoreStart
            static::$price_by_weight2[$cacheKey] = round(
                $priceByWeight,
                _TB_PRICE_DATABASE_PRECISION_
            );
            // @codingStandardsIgnoreEnd
        }

        // @codingStandardsIgnoreStart
        return static::$price_by_weight2[$cacheKey];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Check delivery prices for a given order
     *
     * @param int      $idCarrier
     * @param float    $orderTotal Order total to pay
     * @param int      $idZone     Zone id (for customer delivery address)
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
        // @codingStandardsIgnoreStart
        if (!isset(static::$price_by_price2[$cacheKey])) {
            // @codingStandardsIgnoreEnd
            if (!empty($idCurrency)) {
                $orderTotal = Tools::convertPrice($orderTotal, $idCurrency, false);
            }

            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
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
            // @codingStandardsIgnoreStart
            static::$price_by_price2[$cacheKey] = (isset($result['price']));
            // @codingStandardsIgnoreEnd
        }

        $priceByPrice = Hook::exec('actionDeliveryPriceByPrice', ['id_carrier' => $idCarrier, 'order_total' => $orderTotal, 'id_zone' => $idZone]);
        if (is_numeric($priceByPrice)) {
            // @codingStandardsIgnoreStart
            static::$price_by_price2[$cacheKey] = round(
                $priceByPrice,
                _TB_PRICE_DATABASE_PRECISION_
            );
            // @codingStandardsIgnoreEnd
        }

        // @codingStandardsIgnoreStart
        return static::$price_by_price2[$cacheKey];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Assign one (ore more) group to all carriers
     *
     * @param int|array $idGroupList group id or list of group ids
     * @param array     $exception   list of id carriers to ignore
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function assignGroupToAllCarriers($idGroupList, $exception = null)
    {
        if (!is_array($idGroupList)) {
            $idGroupList = [$idGroupList];
        }

        Db::getInstance()->delete(
            'carrier_group',
            '`id_group` IN ('.join(',', $idGroupList).')'
        );

        $carrierList = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_carrier`')
                ->from('carrier')
                ->where('`deleted` = 0')
                ->where(is_array($exception) ? '`id_carrier` NOT IN ('.join(',', $exception).')' : '')
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if ($this->position <= 0) {
            $this->position = static::getHigherPosition() + 1;
        }
        if (!parent::add($autoDate, $nullValues) || !Validate::isLoadedObject($this)) {
            return false;
        }
        if (!$count = Db::getInstance()->getValue('SELECT count(`id_carrier`) FROM `'._DB_PREFIX_.$this->def['table'].'` WHERE `deleted` = 0')) {
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
     * Gets the highest carrier position
     *
     * @since   1.5.0
     * @return int $position
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getHigherPosition()
    {
        $position = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }
        static::cleanPositions();

        return (Db::getInstance()->delete('cart_rule_carrier', '`id_carrier` = '.(int) $this->id)
            && Db::getInstance()->delete('module_carrier', '`id_reference` = '.(int) $this->id_reference)
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cleanPositions()
    {
        $return = true;

        $result = Db::getInstance()->executeS(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function deleteTaxRulesGroup(array $shops = null)
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param int   $idZone      Zone ID (for customer delivery address)
     *
     * @return float Delivery price
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getDeliveryPriceByWeight($totalWeight, $idZone)
    {
        $idCarrier = (int) $this->id;
        $cacheKey = $idCarrier.'_'.$totalWeight.'_'.$idZone;
        // @codingStandardsIgnoreStart
        if (!isset(static::$price_by_weight[$cacheKey])) {
            // @codingStandardsIgnoreEnd
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
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
                // @codingStandardsIgnoreStart
                static::$price_by_weight[$cacheKey] = $this->getMaxDeliveryPriceByWeight($idZone);
                // @codingStandardsIgnoreEnd
            } else {
                // @codingStandardsIgnoreStart
                static::$price_by_weight[$cacheKey] = $result['price'];
                // @codingStandardsIgnoreEnd
            }
        }

        $priceByWeight = Hook::exec('actionDeliveryPriceByWeight', ['id_carrier' => $idCarrier, 'total_weight' => $totalWeight, 'id_zone' => $idZone]);
        if (is_numeric($priceByWeight)) {
            // @codingStandardsIgnoreStart
            static::$price_by_weight[$cacheKey] = round(
                $priceByWeight,
                _TB_PRICE_DATABASE_PRECISION_
            );
            // @codingStandardsIgnoreEnd
        }

        // @codingStandardsIgnoreStart
        return static::$price_by_weight[$cacheKey];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Get delivery prices for a given order
     *
     * @param float    $orderTotal Order total to pay
     * @param int      $idZone     Zone id (for customer delivery address)
     * @param int|null $idCurrency
     *
     * @return float Delivery price
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getDeliveryPriceByPrice($orderTotal, $idZone, $idCurrency = null)
    {
        $idCarrier = (int) $this->id;
        $cacheKey = $this->id.'_'.$orderTotal.'_'.$idZone.'_'.$idCurrency;
        // @codingStandardsIgnoreStart
        if (!isset(static::$price_by_price[$cacheKey])) {
            // @codingStandardsIgnoreEnd
            if (!empty($idCurrency)) {
                $orderTotal = Tools::convertPrice($orderTotal, $idCurrency, false);
            }
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
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
                // @codingStandardsIgnoreStart
                static::$price_by_price[$cacheKey] = $this->getMaxDeliveryPriceByPrice($idZone);
                // @codingStandardsIgnoreEnd
            } else {
                // @codingStandardsIgnoreStart
                static::$price_by_price[$cacheKey] = $result['price'];
                // @codingStandardsIgnoreEnd
            }
        }

        $priceByPrice = Hook::exec('actionDeliveryPriceByPrice', ['id_carrier' => $idCarrier, 'order_total' => $orderTotal, 'id_zone' => $idZone]);
        if (is_numeric($priceByPrice)) {
            // @codingStandardsIgnoreStart
            static::$price_by_price[$cacheKey] = round(
                $priceByPrice,
                _TB_PRICE_DATABASE_PRECISION_
            );
            // @codingStandardsIgnoreEnd
        }

        // @codingStandardsIgnoreStart
        return static::$price_by_price[$cacheKey];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Get all zones
     *
     * @return array Zones
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getZones()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getZone($idZone)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param int $idZone
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addZone($idZone)
    {
        if (Db::getInstance()->insert(
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

                return Db::getInstance()->insert('delivery', $insert);
            }

            return true;
        }

        return false;
    }

    /**
     * Delete zone
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param int $idZone
     *
     * @return bool
     * @throws PrestaShopDatabaseException
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getGroups()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
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
     * @param bool  $delete
     *
     * @return bool Insertion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
        foreach ($priceList as $values) {
            if (!isset($values['id_shop'])) {
                $values['id_shop'] = (Shop::getContext() == Shop::CONTEXT_SHOP) ? Shop::getContextShopID() : null;
            }
            if (!isset($values['id_shop_group'])) {
                $values['id_shop_group'] = (Shop::getContext() != Shop::CONTEXT_ALL) ? Shop::getContextShopGroupID() : null;
            }

            if ($delete) {
                Db::getInstance()->execute(
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

        return Db::getInstance()->execute($sql);
    }

    /**
     * Copy old carrier informations when update carrier
     *
     * @param int $oldId Old id carrier (copy from that id)
     *
     * @return void
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function copyCarrierData($oldId)
    {
        if (!Validate::isUnsignedId($oldId)) {
            throw new PrestaShopException('Incorrect identifier for carrier');
        }

        if (!$this->id) {
            return;
        }

        $oldLogo = _PS_SHIP_IMG_DIR_.'/'.(int) $oldId.'.jpg';
        if (file_exists($oldLogo)) {
            copy($oldLogo, _PS_SHIP_IMG_DIR_.'/'.(int) $this->id.'.jpg');
        }

        $oldTmpLogo = _PS_TMP_IMG_DIR_.'/carrier_mini_'.(int) $oldId.'.jpg';
        if (file_exists($oldTmpLogo)) {
            if (!isset($_FILES['logo'])) {
                copy($oldTmpLogo, _PS_TMP_IMG_DIR_.'/carrier_mini_'.$this->id.'.jpg');
            }
            unlink($oldTmpLogo);
        }

        // Copy existing ranges price
        foreach (['range_price', 'range_weight'] as $range) {
            $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('`id_'.$range.'` as `id_range`, `delimiter1`, `delimiter2`')
                    ->from($range)
                    ->where('`id_carrier` = '.(int) $oldId)
            );
            if (count($res)) {
                foreach ($res as $val) {
                    Db::getInstance()->insert(
                        $range,
                        [
                            'id_carrier' => (int) $this->id,
                            'delimiter1' => (float) $val['delimiter1'],
                            'delimiter2' => (float) $val['delimiter2'],
                        ]
                    );
                    $idRange = (int) Db::getInstance()->Insert_ID();

                    $idRangePrice = ($range == 'range_price') ? $idRange : 'NULL';
                    $idRangeWeight = ($range == 'range_weight') ? $idRange : 'NULL';

                    Db::getInstance()->execute(
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
        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('carrier_zone')
                ->where('`id_carrier` = '.(int) $oldId)
        );
        foreach ($res as $val) {
            Db::getInstance()->insert(
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
        $idReference = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_reference`')
                ->from(bqSQL(static::$definition['table']))
                ->where('`id_carrier` = '.(int) $oldId)
        );
        Db::getInstance()->update(
            bqSQL(static::$definition['table']),
            [
                'id_reference' => (int) $idReference,
            ],
            '`id_carrier` = '.(int) $this->id
        );

        $this->id_reference = (int) $idReference;

        // Copy tax rules group
        Db::getInstance()->execute(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function isUsed()
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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

    /*
    *
     * @param bool $shippingMethod
     *
     * @return bool|RangePrice|RangeWeight
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param null $currency
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param int  $idTaxRulesGroup
     * @param bool $allShops
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setTaxRulesGroup($idTaxRulesGroup, $allShops = false)
    {
        if (!Validate::isUnsignedId($idTaxRulesGroup)) {
            die(Tools::displayError());
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
     * Returns the taxes rate associated to the carrier
     *
     * @param Address $address
     *
     * @return float
     *
     * @throws Adapter_Exception
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTaxesRate(Address $address)
    {
        $taxCalculator = $this->getTaxCalculator($address);

        return $taxCalculator->getTotalRate();
    }

    /**
     * Returns the taxes calculator associated to the carrier
     *
     * @param Address  $address
     * @param int|null $idOrder
     * @param bool     $useAverageTaxOfProducts
     *
     * @return AverageTaxOfProductsTaxCalculator|TaxCalculator
     * @throws Adapter_Exception
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param int  $position
     *
     * @return bool Update result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updatePosition($way, $position)
    {
        if (!$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
        return Db::getInstance()->update(
            'carrier',
            [
                'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
            ],
            '`position` '.($way ? '> '.(int) $movedCarrier['position'].' AND `position` <= '.(int) $position : '< '.(int) $movedCarrier['position'].' AND `position` >= '.(int) $position.' AND `deleted` = 0')
        ) && Db::getInstance()->update(
            'carrier',
            [
                'position' => (int) $position,
            ],
            '`id_carrier` = '.(int) $movedCarrier['id_carrier']
        );
    }

    /**
     * @param array $groups
     * @param bool  $delete
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
}
