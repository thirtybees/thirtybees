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
 * Class CartRuleCore
 *
 * @since 1.0.0
 */
class CartRuleCore extends ObjectModel
{
    /* Filters used when retrieving the cart rules applied to a cart of when calculating the value of a reduction */
    const FILTER_ACTION_ALL = 1;
    const FILTER_ACTION_SHIPPING = 2;
    const FILTER_ACTION_REDUCTION = 3;
    const FILTER_ACTION_GIFT = 4;
    const FILTER_ACTION_ALL_NOCAP = 5;

    const BO_ORDER_CODE_PREFIX = 'BO_ORDER_';

    // @codingStandardsIgnoreStart
    /**
     * This variable controls that a free gift is offered only once, even when multi-shipping is activated and the same product is delivered in both addresses
     *
     * @var array
     */
    protected static $onlyOneGift = [];
    /** @var int $id */
    public $id;
    /** @var string $name */
    public $name;
    /** @var int $id_customer */
    public $id_customer;
    /** @var string $date_from */
    public $date_from;
    /** @var string $date_to */
    public $date_to;
    /**
     * @FIXME: with 1.0.x the cart rule cannot register the calculated
     *       cheapest product in case it is converted into an order.
     *       The copied cart rule is then injected with this information
     *       in the `description` field and looks like this:
     *       {
     *         "type": "cheapest_product",
     *         "id_product": "7",
     *         "id_product_attribute": "0"
     *       }
     *
     *       In the AdminCartRulesController, the field is then disabled to prevent the user from changing it
     *
     *       When making an update script for 1.1.x, don't forget to clean this field up and convert it to
     *       a proper database table.
     *
     * @var string $description
     */
    public $description;
    /** @var int $quantity */
    public $quantity = 1;
    /** @var int $quantity_per_user */
    public $quantity_per_user = 1;
    /** @var int $priority */
    public $priority = 1;
    /** @var int $partial_use */
    public $partial_use = 1;
    /** @var string $code */
    public $code;
    /** @var float $minimum_amount */
    public $minimum_amount;
    /** @var bool $minimum_amount_tax */
    public $minimum_amount_tax;
    /** @var int $minimum_amount_currency */
    public $minimum_amount_currency;
    /** @var bool $minimum_amount_shipping */
    public $minimum_amount_shipping;
    /** @var bool $country_restriction */
    public $country_restriction;
    /** @var bool $carrier_restriction */
    public $carrier_restriction;
    /** @var bool $group_restriction */
    public $group_restriction;
    /** @var bool $cart_rule_restriction */
    public $cart_rule_restriction;
    /** @var bool $product_restriction */
    public $product_restriction;
    /** @var bool $shop_restriction */
    public $shop_restriction;
    /** @var bool $free_shipping */
    public $free_shipping;
    /** @var float $reduction_percent */
    public $reduction_percent;
    /** @var float $reduction_amount */
    public $reduction_amount;
    /** @var bool $reduction_tax */
    public $reduction_tax;
    /** @var bool $reduction_currency */
    public $reduction_currency;
    /** @var int $reduction_product */
    public $reduction_product;
    /** @var int $gift_product */
    public $gift_product;
    /** @var int $gift_product_attribute */
    public $gift_product_attribute;
    /** @var bool $highlight */
    public $highlight;
    /** @var int $active */
    public $active = 1;
    /** @var string $date_add */
    public $date_add;
    /** @var string $date_upd */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'cart_rule',
        'primary'   => 'id_cart_rule',
        'multilang' => true,
        'fields'    => [
            'id_customer'             => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'],
            'date_from'               => ['type' => self::TYPE_DATE,   'validate' => 'isDate', 'required' => true],
            'date_to'                 => ['type' => self::TYPE_DATE,   'validate' => 'isDate', 'required' => true],
            'description'             => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 65534],
            'quantity'                => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'],
            'quantity_per_user'       => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'],
            'priority'                => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'],
            'partial_use'             => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'code'                    => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 254],
            'minimum_amount'          => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'],
            'minimum_amount_tax'      => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'minimum_amount_currency' => ['type' => self::TYPE_INT,    'validate' => 'isInt'],
            'minimum_amount_shipping' => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'country_restriction'     => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'carrier_restriction'     => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'group_restriction'       => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'cart_rule_restriction'   => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'product_restriction'     => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'shop_restriction'        => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'free_shipping'           => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'reduction_percent'       => ['type' => self::TYPE_FLOAT,  'validate' => 'isPercentage'],
            'reduction_amount'        => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'],
            'reduction_tax'           => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'reduction_currency'      => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'],
            'reduction_product'       => ['type' => self::TYPE_INT,    'validate' => 'isInt'],
            'gift_product'            => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'],
            'gift_product_attribute'  => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'],
            'highlight'               => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'active'                  => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            'date_add'                => ['type' => self::TYPE_DATE,   'validate' => 'isDate'],
            'date_upd'                => ['type' => self::TYPE_DATE,   'validate' => 'isDate'],

            /* Lang fields */
            'name'                    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 254],
        ],
    ];

    /**
     * Copy conditions from one cart rule to an other
     *
     * @param int $idCartRuleSource
     * @param int $idCartRuleDestination
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function copyConditions($idCartRuleSource, $idCartRuleDestination)
    {
        Db::getInstance()->execute(
            '
		INSERT INTO `'._DB_PREFIX_.'cart_rule_shop` (`id_cart_rule`, `id_shop`)
		(SELECT '.(int) $idCartRuleDestination.', id_shop FROM `'._DB_PREFIX_.'cart_rule_shop` WHERE `id_cart_rule` = '.(int) $idCartRuleSource.')'
        );
        Db::getInstance()->execute(
            '
		INSERT INTO `'._DB_PREFIX_.'cart_rule_carrier` (`id_cart_rule`, `id_carrier`)
		(SELECT '.(int) $idCartRuleDestination.', id_carrier FROM `'._DB_PREFIX_.'cart_rule_carrier` WHERE `id_cart_rule` = '.(int) $idCartRuleSource.')'
        );
        Db::getInstance()->execute(
            '
		INSERT INTO `'._DB_PREFIX_.'cart_rule_group` (`id_cart_rule`, `id_group`)
		(SELECT '.(int) $idCartRuleDestination.', id_group FROM `'._DB_PREFIX_.'cart_rule_group` WHERE `id_cart_rule` = '.(int) $idCartRuleSource.')'
        );
        Db::getInstance()->execute(
            '
		INSERT INTO `'._DB_PREFIX_.'cart_rule_country` (`id_cart_rule`, `id_country`)
		(SELECT '.(int) $idCartRuleDestination.', id_country FROM `'._DB_PREFIX_.'cart_rule_country` WHERE `id_cart_rule` = '.(int) $idCartRuleSource.')'
        );
        Db::getInstance()->execute(
            '
		INSERT INTO `'._DB_PREFIX_.'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`)
		(SELECT '.(int) $idCartRuleDestination.', IF(id_cart_rule_1 != '.(int) $idCartRuleSource.', id_cart_rule_1, id_cart_rule_2) FROM `'._DB_PREFIX_.'cart_rule_combination`
		WHERE `id_cart_rule_1` = '.(int) $idCartRuleSource.' OR `id_cart_rule_2` = '.(int) $idCartRuleSource.')'
        );

        // Todo : should be changed soon, be must be copied too
        // Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'cart_rule_product_rule` WHERE `id_cart_rule` = '.(int)$this->id);
        // Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'cart_rule_product_rule_value` WHERE `id_product_rule` NOT IN (SELECT `id_product_rule` FROM `'._DB_PREFIX_.'cart_rule_product_rule`)');

        // Copy products/category filters
        $sql = new DbQuery();
        $sql->select('`id_product_rule_group`, `quantity`');
        $sql->from('cart_rule_product_rule_group');
        $sql->where('`id_cart_rule` = '.(int) $idCartRuleSource);
        $productsRulesGroupSource = Db::getInstance()->ExecuteS($sql);

        foreach ($productsRulesGroupSource as $productRuleGroupSource) {
            Db::getInstance()->insert(
                'cart_rule_product_rule_group',
                [
                    'id_cart_rule' => (int) $idCartRuleDestination,
                    'quantity'     => (int) $productRuleGroupSource['quantity'],
                ]
            );
            $idProductRuleGroupDestination = Db::getInstance()->Insert_ID();

            $productsRulesSource = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('`id_product_rule`, `type`')
                    ->from('cart_rule_product_rule')
                    ->where('`id_product_rule_group` = '.(int) $productsRulesGroupSource['id_product_rule_group'])
            );

            foreach ($productsRulesSource as $productRuleSource) {
                Db::getInstance()->insert(
                    'cart_rule_product_rule',
                    [
                        'id_product_rule_group' => (int) $idProductRuleGroupDestination,
                        'type'                  => pSQL($productRuleSource['type']),
                    ]
                );
                $idProductRuleDestination = Db::getInstance()->Insert_ID();

                $productsRulesValuesSource = Db::getInstance()->executeS(
                    (new DbQuery())
                        ->select('`id_item`')
                        ->from('cart_rule_product_rule_value')
                        ->where('`id_product_rule` = '.(int) $productsRulesSource['id_product_rule'])
                );

                foreach ($productsRulesValuesSource as $productRuleValueSource) {
                    Db::getInstance()->insert(
                        'cart_rule_product_rule_value',
                        [
                            'id_product_rule' => (int) $idProductRuleDestination,
                            'id_item'         => (int) $productRuleValueSource['id_item'],

                        ]
                    );
                }
            }
        }
    }

    /**
     * Retrieves the id associated to the given code
     *
     * @param string $code
     *
     * @return int|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getIdByCode($code)
    {
        if (!Validate::isCleanHtml($code)) {
            return false;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_cart_rule`')
                ->from('cart_rule')
                ->where('`code` = \''.pSQL($code).'\'')
        );
    }

    /**
     * @param int       $idLang
     * @param int       $idCustomer
     * @param bool      $active
     * @param bool      $includeGeneric
     * @param bool      $inStock
     * @param Cart|null $cart
     * @param bool      $freeShippingOnly
     * @param bool      $highlightOnly
     *
     * @return array
     * @throws PrestaShopDatabaseException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     * @throws PrestaShopException
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public static function getCustomerCartRules($idLang, $idCustomer, $active = false, $includeGeneric = true, $inStock = false, Cart $cart = null, $freeShippingOnly = false, $highlightOnly = false)
    {
        if (!static::isFeatureActive()) {
            return [];
        }

        $sql = (new DbQuery())
            ->select('*')
            ->from('cart_rule', 'cr')
            ->leftJoin('cart_rule_lang', 'crl', 'cr.`id_cart_rule` = crl.`id_cart_rule` AND crl.`id_lang` = '.(int) $idLang)
            ->where('cr.`date_from` < \''.date('Y-m-d H:i:s').'\'')
            ->where('cr.`date_to` > \''.date('Y-m-d H:i:s').'\'');
        if ($active) {
            $sql->where('cr.`active` = 1');
        }
        if ($inStock) {
            $sql->where('cr.`quantity` > 0');
        }
        if ($freeShippingOnly) {
            $sql->where('`free_shipping` = 1');
            $sql->where('`carrier_restriction` = 1');
        }
        if ($highlightOnly) {
            $sql->where('`highlight` = 1');
            $sql->where('`code` NOT LIKE \''.pSQL(static::BO_ORDER_CODE_PREFIX).'%\'');
        }
        $sql->where('cr.`id_customer` = '.(int) $idCustomer.' OR cr.`group_restriction` = 1'.(($includeGeneric && (int) $idCustomer !== 0) ? ' OR cr.`id_customer` = 0' : ''));

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true);

        if (empty($result)) {
            return [];
        }

        // Remove cart rule that does not match the customer groups
        $customerGroups = Customer::getGroupsStatic($idCustomer);

        foreach ($result as $key => $cartRule) {
            if ($cartRule['group_restriction']) {
                $cartRuleGroups = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('`id_group`')
                        ->from('cart_rule_group')
                        ->where('id_cart_rule = '.(int) $cartRule['id_cart_rule'])
                );
                foreach ($cartRuleGroups as $cartRuleGroup) {
                    if (in_array($cartRuleGroup['id_group'], $customerGroups)) {
                        continue 2;
                    }
                }
                unset($result[$key]);
            }
        }

        foreach ($result as &$cartRule) {
            if ($cartRule['quantity_per_user']) {
                $quantityUsed = Order::getDiscountsCustomer((int) $idCustomer, (int) $cartRule['id_cart_rule']);
                if (isset($cart) && isset($cart->id)) {
                    $quantityUsed += $cart->getDiscountsCustomer((int) $cartRule['id_cart_rule']);
                }
                $cartRule['quantity_for_user'] = $cartRule['quantity_per_user'] - $quantityUsed;
            } else {
                $cartRule['quantity_for_user'] = 0;
            }
            // Backwards compatibility
            $cartRule['id_group'] = 0;
            if ($cartRule['free_shipping']) {
                $cartRule['id_discount_type'] = 3;
            } elseif ($cartRule['reduction_percent'] > 0) {
                $cartRule['id_discount_type'] = 1;
            } elseif ($cartRule['reduction_amount'] > 0) {
                $cartRule['id_discount_type'] = 2;
            }
            if ($cartRule['reduction_percent'] > 0) {
                $cartRule['value'] = $cartRule['reduction_percent'];
            } elseif ($cartRule['reduction_amount'] > 0) {
                $cartRule['value'] = $cartRule['reduction_amount'];
            }
            $cartRule['cumulable'] = $cartRule['cart_rule_restriction'];
            $cartRule['cumulable_reduction'] = false;
            $cartRule['minimal'] = $cartRule['minimum_amount'];
            $cartRule['include_tax'] = $cartRule['reduction_tax'];
            $cartRule['behavior_not_exhausted'] = $cartRule['partial_use'];
            $cartRule['cart_display'] = true;
        }
        unset($cartRule);

        foreach ($result as $key => $cartRule) {
            if ($cartRule['shop_restriction']) {
                $cartRuleShops = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('`id_shop`')
                        ->from('cart_rule_shop')
                        ->where('`id_cart_rule` = '.(int) $cartRule['id_cart_rule'])
                );
                foreach ($cartRuleShops as $cartRuleShop) {
                    if (Shop::isFeatureActive() && ($cartRuleShop['id_shop'] == Context::getContext()->shop->id)) {
                        continue 2;
                    }
                }
                unset($result[$key]);
            }
        }

        if (isset($cart) && isset($cart->id)) {
            foreach ($result as $key => $cartRule) {
                if ($cartRule['product_restriction']) {
                    $cr = new CartRule((int) $cartRule['id_cart_rule']);
                    $r = $cr->checkProductRestrictions(Context::getContext(), false, false);
                    if ($r !== false) {
                        continue;
                    }
                    unset($result[$key]);
                }
            }
        }

        $resultBak = $result;
        $result = [];
        $countryRestriction = false;
        foreach ($resultBak as $key => $cartRule) {
            if ($cartRule['country_restriction']) {
                $countryRestriction = true;
                $countries = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('`id_country`')
                        ->from('address')
                        ->where('`id_customer` = '.(int) $idCustomer)
                        ->where('`deleted` = 0')
                );

                if (is_array($countries) && !empty($countries)) {
                    foreach ($countries as $country) {
                        $idCartRule = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                            (new DbQuery())
                                ->select('crc.`id_cart_rule`')
                                ->from('cart_rule_country', 'crc')
                                ->where('crc.`id_cart_rule` = '.(int) $cartRule['id_cart_rule'])
                                ->where('crc.`id_country` = '.(int) $country['id_country'])
                        );
                        if ($idCartRule) {
                            $result[] = $resultBak[$key];
                            break;
                        }
                    }
                }
            } else {
                $result[] = $resultBak[$key];
            }
        }

        if (!$countryRestriction) {
            $result = $resultBak;
        }

        return $result;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isFeatureActive()
    {
        static $isFeatureActive = null;
        if ($isFeatureActive === null) {
            $isFeatureActive = (bool) Configuration::get('PS_CART_RULE_FEATURE_ACTIVE');
        }

        return $isFeatureActive;
    }

    /**
     * @param Context $context
     * @param bool    $returnProducts
     * @param bool    $displayError
     * @param bool    $alreadyInCart
     *
     * @return array|bool|mixed|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function checkProductRestrictions(Context $context, $returnProducts = false, $displayError = true, $alreadyInCart = false)
    {
        $selectedProducts = [];

        // Check if the products chosen by the customer are usable with the cart rule
        if ($this->product_restriction) {
            $productRuleGroups = $this->getProductRuleGroups();
            foreach ($productRuleGroups as $idProductRuleGroup => $productRuleGroup) {
                $eligibleProductsList = [];
                if (isset($context->cart) && is_object($context->cart) && is_array($products = $context->cart->getProducts())) {
                    foreach ($products as $product) {
                        $eligibleProductsList[] = (int) $product['id_product'].'-'.(int) $product['id_product_attribute'];
                    }
                }
                if (!count($eligibleProductsList)) {
                    return (!$displayError) ? false : Tools::displayError('You cannot use this voucher in an empty cart');
                }

                $productRules = $this->getProductRules($idProductRuleGroup);
                foreach ($productRules as $productRule) {
                    switch ($productRule['type']) {
                        case 'attributes':
                            $cartAttributes = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                                (new DbQuery())
                                    ->select('cp.`quantity`, cp.`id_product`, pac.`id_attribute`, cp.`id_product_attribute`')
                                    ->from('cart_product', 'cp')
                                    ->leftJoin('product_attribute_combination', 'pac', 'cp.`id_product_attribute` = pac.`id_product_attribute`')
                                    ->where('cp.`id_cart` = '.(int) $context->cart->id)
                                    ->where('cp.`id_product` IN ('.implode(',', array_map('intval', $eligibleProductsList)).')')
                                    ->where('cp.`id_product_attribute` > 0')
                            );
                            $countMatchingProducts = 0;
                            $matchingProductsList = [];
                            foreach ($cartAttributes as $cartAttribute) {
                                if (in_array($cartAttribute['id_attribute'], $productRule['values'])) {
                                    $countMatchingProducts += $cartAttribute['quantity'];
                                    if ($alreadyInCart && $this->gift_product == $cartAttribute['id_product']
                                        && $this->gift_product_attribute == $cartAttribute['id_product_attribute']
                                    ) {
                                        --$countMatchingProducts;
                                    }
                                    $matchingProductsList[] = $cartAttribute['id_product'].'-'.$cartAttribute['id_product_attribute'];
                                }
                            }
                            if ($countMatchingProducts < $productRuleGroup['quantity']) {
                                return (!$displayError) ? false : Tools::displayError('You cannot use this voucher with these products');
                            }
                            $eligibleProductsList = static::array_uintersect($eligibleProductsList, $matchingProductsList);
                            break;
                        case 'products':
                            $cartProducts = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                                (new DbQuery())
                                    ->select('cp.`quantity`, cp.`id_product`')
                                    ->from('cart_product', 'cp')
                                    ->where('cp.`id_cart` = '.(int) $context->cart->id)
                                    ->where('cp.`id_product` IN ('.implode(',', array_map('intval', $eligibleProductsList)).')')
                            );
                            $countMatchingProducts = 0;
                            $matchingProductsList = [];
                            foreach ($cartProducts as $cartProduct) {
                                if (in_array($cartProduct['id_product'], $productRule['values'])) {
                                    $countMatchingProducts += $cartProduct['quantity'];
                                    if ($alreadyInCart && $this->gift_product == $cartProduct['id_product']) {
                                        --$countMatchingProducts;
                                    }
                                    $matchingProductsList[] = $cartProduct['id_product'].'-0';
                                }
                            }
                            if ($countMatchingProducts < $productRuleGroup['quantity']) {
                                return (!$displayError) ? false : Tools::displayError('You cannot use this voucher with these products');
                            }
                            $eligibleProductsList = static::array_uintersect($eligibleProductsList, $matchingProductsList);
                            break;
                        case 'categories':
                            $cartCategories = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                                (new DbQuery())
                                    ->select('cp.quantity, cp.`id_product`, cp.`id_product_attribute`, catp.`id_category`')
                                    ->from('cart_product', 'cp')
                                    ->leftJoin('category_product', 'catp', 'cp.`id_product` = catp.`id_product`')
                                    ->where('cp.`id_cart` = '.(int) $context->cart->id)
                                    ->where('cp.`id_product` IN ('.implode(',', array_map('intval', $eligibleProductsList)).')')
                                    ->where('cp.`id_product` <> '.(int) $this->gift_product)
                            );
                            $countMatchingProducts = 0;
                            $matchingProductsList = [];
                            foreach ($cartCategories as $cartCategory) {
                                if (in_array($cartCategory['id_category'], $productRule['values'])
                                    /**
                                     * We also check that the product is not already in the matching product list,
                                     * because there are doubles in the query results (when the product is in multiple categories)
                                     */
                                    && !in_array($cartCategory['id_product'].'-'.$cartCategory['id_product_attribute'], $matchingProductsList)
                                ) {
                                    $countMatchingProducts += $cartCategory['quantity'];
                                    $matchingProductsList[] = $cartCategory['id_product'].'-'.$cartCategory['id_product_attribute'];
                                }
                            }
                            if ($countMatchingProducts < $productRuleGroup['quantity']) {
                                return (!$displayError) ? false : Tools::displayError('You cannot use this voucher with these products');
                            }
                            // Attribute id is not important for this filter in the global list, so the ids are replaced by 0
                            foreach ($matchingProductsList as &$matchingProduct) {
                                $matchingProduct = preg_replace('/^([0-9]+)-[0-9]+$/', '$1-0', $matchingProduct);
                            }
                            $eligibleProductsList = static::array_uintersect($eligibleProductsList, $matchingProductsList);
                            break;
                        case 'manufacturers':
                            $cartManufacturers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                                (new DbQuery())
                                    ->select('cp.quantity, cp.`id_product`, p.`id_manufacturer`')
                                    ->from('cart_product', 'cp')
                                    ->leftJoin('product', 'p', 'cp.`id_product` = p.`id_product`')
                                    ->where('cp.`id_cart` = '.(int) $context->cart->id)
                                    ->where('cp.`id_product` IN ('.implode(',', array_map('intval', $eligibleProductsList)).')')
                            );
                            $countMatchingProducts = 0;
                            $matchingProductsList = [];
                            foreach ($cartManufacturers as $cartManufacturer) {
                                if (in_array($cartManufacturer['id_manufacturer'], $productRule['values'])) {
                                    $countMatchingProducts += $cartManufacturer['quantity'];
                                    $matchingProductsList[] = $cartManufacturer['id_product'].'-0';
                                }
                            }
                            if ($countMatchingProducts < $productRuleGroup['quantity']) {
                                return (!$displayError) ? false : Tools::displayError('You cannot use this voucher with these products');
                            }
                            $eligibleProductsList = static::array_uintersect($eligibleProductsList, $matchingProductsList);
                            break;
                        case 'suppliers':
                            $cartSuppliers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                                (new DbQuery())
                                    ->select('cp.`quantity`, cp.`id_product`, p.`id_supplier`')
                                    ->from('cart_product', 'cp')
                                    ->leftJoin('product', 'p', 'cp.`id_product` = p.`id_product`')
                                    ->where('cp.`id_cart` = '.(int) $context->cart->id)
                                    ->where('cp.`id_product` IN ('.implode(',', array_map('intval', $eligibleProductsList)).')')
                            );
                            $countMatchingProducts = 0;
                            $matchingProductsList = [];
                            foreach ($cartSuppliers as $cartSupplier) {
                                if (in_array($cartSupplier['id_supplier'], $productRule['values'])) {
                                    $countMatchingProducts += $cartSupplier['quantity'];
                                    $matchingProductsList[] = $cartSupplier['id_product'].'-0';
                                }
                            }
                            if ($countMatchingProducts < $productRuleGroup['quantity']) {
                                return (!$displayError) ? false : Tools::displayError('You cannot use this voucher with these products');
                            }
                            $eligibleProductsList = static::array_uintersect($eligibleProductsList, $matchingProductsList);
                            break;
                    }

                    if (!count($eligibleProductsList)) {
                        return (!$displayError) ? false : Tools::displayError('You cannot use this voucher with these products');
                    }
                }
                $selectedProducts = array_merge($selectedProducts, $eligibleProductsList);
            }
        }

        if ($returnProducts) {
            return $selectedProducts;
        }

        return (!$displayError) ? true : false;
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductRuleGroups()
    {
        if (!Validate::isLoadedObject($this) || $this->product_restriction == 0) {
            return [];
        }

        $productRuleGroups = [];
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('cart_rule_product_rule_group')
                ->where('`id_cart_rule` = '.(int) $this->id)
        );
        foreach ($result as $row) {
            if (!isset($productRuleGroups[$row['id_product_rule_group']])) {
                $productRuleGroups[$row['id_product_rule_group']] = ['id_product_rule_group' => $row['id_product_rule_group'], 'quantity' => $row['quantity']];
            }
            $productRuleGroups[$row['id_product_rule_group']]['product_rules'] = $this->getProductRules($row['id_product_rule_group']);
        }

        return $productRuleGroups;
    }

    /**
     * @param int $idProductRuleGroup
     *
     * @return array ('type' => ? , 'values' => ?)
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getProductRules($idProductRuleGroup)
    {
        if (!Validate::isLoadedObject($this) || $this->product_restriction == 0) {
            return [];
        }

        $productRules = [];
        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('cart_rule_product_rule', 'pr')
                ->leftJoin('cart_rule_product_rule_value', 'prv', 'pr.`id_product_rule` = prv.`id_product_rule`')
                ->where('pr.`id_product_rule_group` = '.(int) $idProductRuleGroup)
        );
        foreach ($results as $row) {
            if (!isset($productRules[$row['id_product_rule']])) {
                $productRules[$row['id_product_rule']] = ['type' => $row['type'], 'values' => []];
            }
            $productRules[$row['id_product_rule']]['values'][] = $row['id_item'];
        }

        return $productRules;
    }

    /**
     * @param $array1
     * @param $array2
     *
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function array_uintersect($array1, $array2)
    {
        $intersection = [];
        foreach ($array1 as $value1) {
            foreach ($array2 as $value2) {
                if (static::array_uintersect_compare($value1, $value2) == 0) {
                    $intersection[] = $value1;
                    break 1;
                }
            }
        }

        return $intersection;
    }

    /**
     * @param $a
     * @param $b
     *
     * @return int
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function array_uintersect_compare($a, $b)
    {
        if ($a == $b) {
            return 0;
        }

        $asplit = explode('-', $a);
        $bsplit = explode('-', $b);
        if ($asplit[0] == $bsplit[0] && (!(int) $asplit[1] || !(int) $bsplit[1])) {
            return 0;
        }

        return 1;
    }

    /**
     * @param string $name
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function cartRuleExists($name)
    {
        if (!static::isFeatureActive()) {
            return false;
        }

        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_cart_rule`')
                ->from('cart_rule')
                ->where('`code` = \''.pSQL($name).'\'')
        );
    }

    /**
     * @param int $idCustomer
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function deleteByIdCustomer($idCustomer)
    {
        $return = true;
        $cartRules = new PrestaShopCollection('CartRule');
        $cartRules->where('id_customer', '=', $idCustomer);
        foreach ($cartRules as $cartRule) {
            $return &= $cartRule->delete();
        }

        return $return;
    }

    /**
     * Make sure caches are empty
     * Must be called before calling multiple time getContextualValue()
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cleanCache()
    {
        static::$onlyOneGift = [];
    }

    /**
     * @param null $context
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function autoRemoveFromCart($context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }
        if (!static::isFeatureActive() || !Validate::isLoadedObject($context->cart)) {
            return [];
        }

        static $errors = [];
        foreach ($context->cart->getCartRules() as $cartRule) {
            /** @var CartRule $cartRuleObject */
            $cartRuleObject = $cartRule['obj'];
            if ($error = $cartRuleObject->checkValidity($context, true)) {
                $context->cart->removeCartRule($cartRuleObject->id);
                $context->cart->update();
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * @param Context|null $context
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function autoAddToCart(Context $context = null)
    {
        if ($context === null) {
            $context = Context::getContext();
        }
        if (!static::isFeatureActive() || !Validate::isLoadedObject($context->cart)) {
            return;
        }

        $sql = '
		SELECT SQL_NO_CACHE cr.*
		FROM '._DB_PREFIX_.'cart_rule cr
		LEFT JOIN '._DB_PREFIX_.'cart_rule_shop crs ON cr.id_cart_rule = crs.id_cart_rule
		'.(!$context->customer->id && Group::isFeatureActive() ? ' LEFT JOIN '._DB_PREFIX_.'cart_rule_group crg ON cr.id_cart_rule = crg.id_cart_rule' : '').'
		LEFT JOIN '._DB_PREFIX_.'cart_rule_carrier crca ON cr.id_cart_rule = crca.id_cart_rule
		'.($context->cart->id_carrier ? 'LEFT JOIN '._DB_PREFIX_.'carrier c ON (c.id_reference = crca.id_carrier AND c.deleted = 0)' : '').'
		LEFT JOIN '._DB_PREFIX_.'cart_rule_country crco ON cr.id_cart_rule = crco.id_cart_rule
		WHERE cr.active = 1
		AND cr.code = ""
		AND cr.quantity > 0
		AND cr.date_from < "'.date('Y-m-d H:i:s').'"
		AND cr.date_to > "'.date('Y-m-d H:i:s').'"
		AND (
			cr.id_customer = 0
			'.($context->customer->id ? 'OR cr.id_customer = '.(int) $context->cart->id_customer : '').'
		)
		AND (
			cr.`carrier_restriction` = 0
			'.($context->cart->id_carrier ? 'OR c.id_carrier = '.(int) $context->cart->id_carrier : '').'
		)
		AND (
			cr.`shop_restriction` = 0
			'.((Shop::isFeatureActive() && $context->shop->id) ? 'OR crs.id_shop = '.(int) $context->shop->id : '').'
		)
		AND (
			cr.`group_restriction` = 0
			'.($context->customer->id ? 'OR EXISTS (
				SELECT 1
				FROM `'._DB_PREFIX_.'customer_group` cg
				INNER JOIN `'._DB_PREFIX_.'cart_rule_group` crg ON cg.id_group = crg.id_group
				WHERE cr.`id_cart_rule` = crg.`id_cart_rule`
				AND cg.`id_customer` = '.(int) $context->customer->id.'
				LIMIT 1
			)' : (Group::isFeatureActive() ? 'OR crg.`id_group` = '.(int) Configuration::get('PS_UNIDENTIFIED_GROUP') : '')).'
		)
		AND (
			cr.`reduction_product` <= 0
			OR EXISTS (
				SELECT 1
				FROM `'._DB_PREFIX_.'cart_product`
				WHERE `'._DB_PREFIX_.'cart_product`.`id_product` = cr.`reduction_product` AND `id_cart` = '.(int) $context->cart->id.'
			)
		)
		AND NOT EXISTS (SELECT 1 FROM '._DB_PREFIX_.'cart_cart_rule WHERE cr.id_cart_rule = '._DB_PREFIX_.'cart_cart_rule.id_cart_rule
																			AND id_cart = '.(int) $context->cart->id.')
		ORDER BY priority';
        $result = Db::getInstance()->executeS($sql, true, false);
        if ($result) {
            $cartRules = ObjectModel::hydrateCollection('CartRule', $result);
            if ($cartRules) {
                foreach ($cartRules as $cartRule) {
                    /** @var CartRule $cartRule */
                    if ($cartRule->checkValidity($context, false, false)) {
                        $context->cart->addCartRule($cartRule->id);
                    }
                }
            }
        }
    }

    /**
     * Check if this cart rule can be applied
     *
     * @param Context $context
     * @param bool    $alreadyInCart Check if the voucher is already on the cart
     * @param bool    $displayError  Display error
     *
     * @return bool|mixed|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function checkValidity(Context $context, $alreadyInCart = false, $displayError = true, $checkCarrier = true)
    {
        if (!static::isFeatureActive()) {
            return false;
        }

        if (!$this->active) {
            return (!$displayError) ? false : Tools::displayError('This voucher is disabled');
        }
        if (!$this->quantity) {
            return (!$displayError) ? false : Tools::displayError('This voucher has already been used');
        }
        if (strtotime($this->date_from) > time()) {
            return (!$displayError) ? false : Tools::displayError('This voucher is not valid yet');
        }
        if (strtotime($this->date_to) < time()) {
            return (!$displayError) ? false : Tools::displayError('This voucher has expired');
        }

        if ($context->cart->id_customer) {
            $quantityUsed = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('orders', 'o')
                    ->leftJoin('order_cart_rule', 'od', 'od.`id_order` = o.`id_order`')
                    ->where('o.`id_customer` = '.(int) $context->cart->id_customer)
                    ->where('od.`id_cart_rule` = '.(int) $this->id)
                    ->where('o.`current_state` != '.(int) Configuration::get('PS_OS_ERROR'))
            );
            if ($quantityUsed + 1 > $this->quantity_per_user) {
                return (!$displayError) ? false : Tools::displayError('You cannot use this voucher anymore (usage limit reached)');
            }
        }

        // Get an intersection of the customer groups and the cart rule groups (if the customer is not logged in, the default group is Visitors)
        if ($this->group_restriction) {
            $idCartRule = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('crg.`id_cart_rule`')
                    ->from('cart_rule_group', 'crg')
                    ->where('crg.`id_cart_rule` = '.(int) $this->id)
                    ->where('crg.`id_group` '.($context->cart->id_customer ? 'IN (SELECT cg.id_group FROM '._DB_PREFIX_.'customer_group cg WHERE cg.id_customer = '.(int) $context->cart->id_customer.')' : '= '.(int) Configuration::get('PS_UNIDENTIFIED_GROUP')))
            );
            if (!$idCartRule) {
                return (!$displayError) ? false : Tools::displayError('You cannot use this voucher');
            }
        }

        // Check if the customer delivery address is usable with the cart rule
        if ($this->country_restriction) {
            if (!$context->cart->id_address_delivery) {
                return (!$displayError) ? false : Tools::displayError('You must choose a delivery address before applying this voucher to your order');
            }
            $idCartRule = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('crc.`id_cart_rule`')
                    ->from('cart_rule_country', 'crc')
                    ->where('crc.`id_cart_rule` = '.(int) $this->id)
                    ->where('crc.`id_country`  = (SELECT a.id_country FROM '._DB_PREFIX_.'address a WHERE a.id_address = '.(int) $context->cart->id_address_delivery.' LIMIT 1)')
            );
            if (!$idCartRule) {
                return (!$displayError) ? false : Tools::displayError('You cannot use this voucher in your country of delivery');
            }
        }

        // Check if the carrier chosen by the customer is usable with the cart rule
        if ($this->carrier_restriction && $checkCarrier) {
            if (!$context->cart->id_carrier) {
                return (!$displayError) ? false : Tools::displayError('You must choose a carrier before applying this voucher to your order');
            }
            $idCartRule = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('crc.`id_cart_rule`')
                    ->from('cart_rule_carrier', 'crc')
                    ->innerJoin('carrier', 'c', 'c.`id_reference` = crc.`id_carrier` AND c.`deleted` = 0')
                    ->where('crc.`id_cart_rule` = '.(int) $this->id)
                    ->where('c.`id_carrier` = '.(int) $context->cart->id_carrier)
            );
            if (!$idCartRule) {
                return (!$displayError) ? false : Tools::displayError('You cannot use this voucher with this carrier');
            }
        }

        // Check if the cart rules appliy to the shop browsed by the customer
        if ($this->shop_restriction && $context->shop->id && Shop::isFeatureActive()) {
            $idCartRule = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('crs.`id_cart_rule`')
                    ->from('cart_rule_shop', 'crs')
                    ->where('crs.`id_cart_rule` = '.(int) $this->id)
                    ->where('crs.`id_shop` = '.(int) $context->shop->id)
            );
            if (!$idCartRule) {
                return (!$displayError) ? false : Tools::displayError('You cannot use this voucher');
            }
        }

        // Check if the products chosen by the customer are usable with the cart rule
        if ($this->product_restriction) {
            $r = $this->checkProductRestrictions($context, false, $displayError, $alreadyInCart);
            if ($r !== false && $displayError) {
                return $r;
            } elseif (!$r && !$displayError) {
                return false;
            }
        }

        // Check if the cart rule is only usable by a specific customer, and if the current customer is the right one
        if ($this->id_customer && $context->cart->id_customer != $this->id_customer) {
            if (!Context::getContext()->customer->isLogged()) {
                return (!$displayError) ? false : (Tools::displayError('You cannot use this voucher').' - '.Tools::displayError('Please log in first'));
            }

            return (!$displayError) ? false : Tools::displayError('You cannot use this voucher');
        }

        if ($this->minimum_amount && $checkCarrier) {
            // Minimum amount is converted to the contextual currency
            $minimumAmount = $this->minimum_amount;
            if ($this->minimum_amount_currency != Context::getContext()->currency->id) {
                $minimumAmount = Tools::convertPriceFull($minimumAmount, new Currency($this->minimum_amount_currency), Context::getContext()->currency);
            }

            $cartTotal = $context->cart->getOrderTotal($this->minimum_amount_tax, Cart::ONLY_PRODUCTS);
            if ($this->minimum_amount_shipping) {
                $cartTotal += $context->cart->getOrderTotal($this->minimum_amount_tax, Cart::ONLY_SHIPPING);
            }
            $products = $context->cart->getProducts();
            $cartRules = $context->cart->getCartRules();

            foreach ($cartRules as &$cartRule) {
                if ($cartRule['gift_product']) {
                    foreach ($products as $key => &$product) {
                        if (empty($product['gift'])
                            && $product['id_product']
                               == $cartRule['gift_product']
                            && $product['id_product_attribute']
                               == $cartRule['gift_product_attribute']) {
                            if ($this->minimum_amount_tax) {
                                $cartTotal = $cartTotal - $product['price_wt'];
                            } else {
                                $cartTotal = $cartTotal - $product['price'];
                            }
                        }
                    }
                }
            }

            if ($cartTotal < $minimumAmount) {
                return (!$displayError) ? false : Tools::displayError('You have not reached the minimum amount required to use this voucher');
            }
        }

        /* This loop checks:
            - if the voucher is already in the cart
            - if a non compatible voucher is in the cart
            - if there are products in the cart (gifts excluded)
            Important note: this MUST be the last check, because if the tested cart rule has priority over a non combinable one in the cart, we will switch them
        */
        $nbProducts = Cart::getNbProducts($context->cart->id);
        $otherCartRules = [];
        if ($checkCarrier) {
            $otherCartRules = $context->cart->getCartRules();
        }
        if (count($otherCartRules)) {
            foreach ($otherCartRules as $otherCartRule) {
                if ($otherCartRule['id_cart_rule'] == $this->id && !$alreadyInCart) {
                    return (!$displayError) ? false : Tools::displayError('This voucher is already in your cart');
                }
                if ($otherCartRule['gift_product']) {
                    --$nbProducts;
                }

                if ($this->cart_rule_restriction && $otherCartRule['cart_rule_restriction'] && $otherCartRule['id_cart_rule'] != $this->id) {
                    $combinable = Db::getInstance()->getValue(
                        '
					SELECT id_cart_rule_1
					FROM '._DB_PREFIX_.'cart_rule_combination
					WHERE (id_cart_rule_1 = '.(int) $this->id.' AND id_cart_rule_2 = '.(int) $otherCartRule['id_cart_rule'].')
					OR (id_cart_rule_2 = '.(int) $this->id.' AND id_cart_rule_1 = '.(int) $otherCartRule['id_cart_rule'].')'
                    );
                    if (!$combinable) {
                        $cartRule = new CartRule((int) $otherCartRule['id_cart_rule'], $context->cart->id_lang);
                        // The cart rules are not combinable and the cart rule currently in the cart has priority over the one tested
                        if ($cartRule->priority <= $this->priority) {
                            return (!$displayError) ? false : Tools::displayError('This voucher is not combinable with an other voucher already in your cart:').' '.$cartRule->name;
                        } // But if the cart rule that is tested has priority over the one in the cart, we remove the one in the cart and keep this new one
                        else {
                            $context->cart->removeCartRule($cartRule->id);
                        }
                    }
                }
            }
        }

        if (!$nbProducts) {
            return (!$displayError) ? false : Tools::displayError('Cart is empty');
        }

        if (!$displayError) {
            return true;
        }
    }

    /**
     * @param $type
     * @param $list
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public static function cleanProductRuleIntegrity($type, $list)
    {
        // Type must be available in the 'type' enum of the table cart_rule_product_rule
        if (!in_array($type, ['products', 'categories', 'attributes', 'manufacturers', 'suppliers'])) {
            return false;
        }

        // This check must not be removed because this var is used a few lines below
        $list = (is_array($list) ? implode(',', array_map('intval', $list)) : (int) $list);
        if (!preg_match('/^[0-9,]+$/', $list)) {
            return false;
        }

        // Delete associated restrictions on cart rules
        Db::getInstance()->execute(
            '
		DELETE crprv
		FROM `'._DB_PREFIX_.'cart_rule_product_rule` crpr
		LEFT JOIN `'._DB_PREFIX_.'cart_rule_product_rule_value` crprv ON crpr.`id_product_rule` = crprv.`id_product_rule`
		WHERE crpr.`type` = "'.pSQL($type).'"
		AND crprv.`id_item` IN ('.$list.')'
        ); // $list is checked a few lines above

        // Delete the product rules that does not have any values
        if (Db::getInstance()->Affected_Rows() > 0) {
            Db::getInstance()->delete(
                'cart_rule_product_rule', 'NOT EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'cart_rule_product_rule_value`
																							WHERE `'._DB_PREFIX_.'cart_rule_product_rule`.`id_product_rule` = `'._DB_PREFIX_.'cart_rule_product_rule_value`.`id_product_rule`)'
            );
        }
        // If the product rules were the only conditions of a product rule group, delete the product rule group
        if (Db::getInstance()->Affected_Rows() > 0) {
            Db::getInstance()->delete(
                'cart_rule_product_rule_group', 'NOT EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'cart_rule_product_rule`
																						WHERE `'._DB_PREFIX_.'cart_rule_product_rule`.`id_product_rule_group` = `'._DB_PREFIX_.'cart_rule_product_rule_group`.`id_product_rule_group`)'
            );
        }

        // If the product rule group were the only restrictions of a cart rule, update de cart rule restriction cache
        if (Db::getInstance()->Affected_Rows() > 0) {
            Db::getInstance()->execute(
                '
				UPDATE `'._DB_PREFIX_.'cart_rule` cr
				LEFT JOIN `'._DB_PREFIX_.'cart_rule_product_rule_group` crprg ON cr.id_cart_rule = crprg.id_cart_rule
				SET product_restriction = IF(crprg.id_product_rule_group IS NULL, 0, 1)'
            );
        }

        return true;
    }

    /**
     * @param string $name
     * @param int    $idLang
     *
     * @param bool   $extended
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCartsRuleByCode($name, $idLang, $extended = false)
    {
        $sqlBase = 'SELECT cr.*, crl.*
						FROM '._DB_PREFIX_.'cart_rule cr
						LEFT JOIN '._DB_PREFIX_.'cart_rule_lang crl ON (cr.id_cart_rule = crl.id_cart_rule AND crl.id_lang = '.(int) $idLang.')';
        if ($extended) {
            return Db::getInstance()->executeS('('.$sqlBase.' WHERE code LIKE \'%'.pSQL($name).'%\') UNION ('.$sqlBase.' WHERE name LIKE \'%'.pSQL($name).'%\')');
        } else {
            return Db::getInstance()->executeS($sqlBase.' WHERE code LIKE \'%'.pSQL($name).'%\'');
        }
    }

    /**
     * @see     ObjectModel::add()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (!$this->reduction_currency) {
            $this->reduction_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        }

        if (!parent::add($autoDate, $nullValues)) {
            return false;
        }

        Configuration::updateGlobalValue('PS_CART_RULE_FEATURE_ACTIVE', '1');

        return true;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        Cache::clean('getContextualValue_'.$this->id.'_*');

        if (!$this->reduction_currency) {
            $this->reduction_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        }

        return parent::update($nullValues);
    }

    /**
     * @see     ObjectModel::delete()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }

        Configuration::updateGlobalValue('PS_CART_RULE_FEATURE_ACTIVE', static::isCurrentlyUsed($this->def['table'], true));

        $r = Db::getInstance()->delete('cart_cart_rule', '`id_cart_rule` = '.(int) $this->id);
        $r &= Db::getInstance()->delete('cart_rule_carrier', '`id_cart_rule` = '.(int) $this->id);
        $r &= Db::getInstance()->delete('cart_rule_shop', '`id_cart_rule` = '.(int) $this->id);
        $r &= Db::getInstance()->delete('cart_rule_group', '`id_cart_rule` = '.(int) $this->id);
        $r &= Db::getInstance()->delete('cart_rule_country', '`id_cart_rule` = '.(int) $this->id);
        $r &= Db::getInstance()->delete('cart_rule_combination', '`id_cart_rule_1` = '.(int) $this->id.' OR `id_cart_rule_2` = '.(int) $this->id);
        $r &= Db::getInstance()->delete('cart_rule_product_rule_group', '`id_cart_rule` = '.(int) $this->id);
        $r &= Db::getInstance()->delete(
            'cart_rule_product_rule', 'NOT EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'cart_rule_product_rule_group`
			WHERE `'._DB_PREFIX_.'cart_rule_product_rule`.`id_product_rule_group` = `'._DB_PREFIX_.'cart_rule_product_rule_group`.`id_product_rule_group`)'
        );
        $r &= Db::getInstance()->delete(
            'cart_rule_product_rule_value', 'NOT EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'cart_rule_product_rule`
			WHERE `'._DB_PREFIX_.'cart_rule_product_rule_value`.`id_product_rule` = `'._DB_PREFIX_.'cart_rule_product_rule`.`id_product_rule`)'
        );

        return $r;
    }

    /**
     * @param int $idCustomer
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function usedByCustomer($idCustomer)
    {
        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_cart_rule`')
                ->from('order_cart_rule', 'ocr')
                ->leftJoin('orders', 'o', 'ocr.`id_order` = o.`id_order`')
                ->where('ocr.`id_cart_rule` = '.(int) $this->id)
                ->where('o.`id_customer` = '.(int) $idCustomer)
        );
    }

    /**
     * The reduction value is POSITIVE
     *
     * @param bool    $useTax
     * @param Context $context
     * @param null    $filter
     * @param null    $package
     * @param bool    $useCache Allow using cache to avoid multiple free gift using multishipping
     *
     * @return float|int|string
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getContextualValue($useTax, Context $context = null, $filter = null, $package = null, $useCache = true)
    {
        if (!static::isFeatureActive()) {
            return 0;
        }
        if (!$context) {
            $context = Context::getContext();
        }
        if (!$filter) {
            $filter = static::FILTER_ACTION_ALL;
        }
        $roundType = (int) Configuration::get('PS_ROUND_TYPE');
        $displayDecimals = 0;
        if ($context->currency->decimals) {
            $displayDecimals = Configuration::get('PS_PRICE_DISPLAY_PRECISION');
        }

        $allProducts = $context->cart->getProducts();
        $packageProducts = (is_null($package) ? $allProducts : $package['products']);

        $reductionValue = 0;

        $cacheId = 'getContextualValue_'.(int) $this->id.'_'.(int) $useTax.'_'.(int) $context->cart->id.'_'.(int) $filter;
        foreach ($packageProducts as $product) {
            $cacheId .= '_'.(int) $product['id_product'].'_'.(int) $product['id_product_attribute'].(isset($product['in_stock']) ? '_'.(int) $product['in_stock'] : '');
        }

        if (Cache::isStored($cacheId)) {
            return Cache::retrieve($cacheId);
        }

        $allCartRulesIds = $context->cart->getOrderedCartRulesIds();

        $cartAmountTaxIncluded = $context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $cartAmountTaxExcluded = $context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);

        // Free shipping on selected carriers
        if ($this->free_shipping && in_array($filter, [static::FILTER_ACTION_ALL, static::FILTER_ACTION_ALL_NOCAP, static::FILTER_ACTION_SHIPPING])) {
            if (!$this->carrier_restriction) {
                $reductionValue += $context->cart->getOrderTotal($useTax, Cart::ONLY_SHIPPING, is_null($package) ? null : $package['products'], is_null($package) ? null : $package['id_carrier']);
            } else {
                $data = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('crc.`id_cart_rule`, c.`id_carrier`')
                        ->from('cart_rule_carrier', 'crc')
                        ->innerJoin('carrier', 'c', 'c.`id_reference` = crc.`id_carrier` AND c.`deleted` = 0')
                        ->where('crc.`id_cart_rule` = '.(int) $this->id)
                        ->where('c.`id_carrier` = '.(int) $context->cart->id_carrier)
                );

                if ($data) {
                    foreach ($data as $cartRule) {
                        $reductionValue += $context->cart->getCarrierCost((int) $cartRule['id_carrier'], $useTax, $context->country);
                    }
                }
            }
        }

        if (in_array($filter, [static::FILTER_ACTION_ALL, static::FILTER_ACTION_ALL_NOCAP, static::FILTER_ACTION_REDUCTION])) {
            // Discount (%) on the whole order
            if ($this->reduction_percent && $this->reduction_product == 0) {
                // Do not give a reduction on free products!
                $orderTotal = $context->cart->getOrderTotal($useTax, Cart::ONLY_PRODUCTS, $packageProducts);
                foreach ($context->cart->getCartRules(static::FILTER_ACTION_GIFT) as $cartRule) {
                    $reduction = $cartRule['obj']->getContextualValue(
                        $useTax, $context,
                        static::FILTER_ACTION_GIFT, $package
                    );
                    if ($roundType === Order::ROUND_ITEM) {
                        $reduction = round($reduction, $displayDecimals);
                    }
                    $orderTotal -= $reduction;
                }

                $reductionValue += round(
                    $orderTotal * $this->reduction_percent / 100,
                    _TB_PRICE_DATABASE_PRECISION_
                );
            }

            // Discount (%) on a specific product
            if ($this->reduction_percent && $this->reduction_product > 0) {
                foreach ($packageProducts as $product) {
                    if ($product['id_product'] == $this->reduction_product) {
                        if ($useTax == true) {
                            $reduction = $product['total_wt'];
                        } else {
                            $reduction = $product['total'];
                        }
                        $reductionValue += round(
                            $reduction * $this->reduction_percent / 100,
                            _TB_PRICE_DATABASE_PRECISION_
                        );
                    }
                }
            }

            // Discount (%) on the cheapest product
            if ($this->reduction_percent && $this->reduction_product == -1) {
                $minPrice = false;
                $cheapestProduct = null;
                $selectedProducts = $this->checkProductRestrictions($context, true);
                foreach ($allProducts as $product) {
                    if (!is_array($selectedProducts) ||
                        (!in_array($product['id_product'].'-'.$product['id_product_attribute'], $selectedProducts) && !in_array($product['id_product'].'-0', $selectedProducts))
                    ) {
                        continue;
                    }

                    $price = $product['price'];
                    if ($useTax == true) {
                        $price = round(
                            $price * (1 + $product['rate'] / 100),
                            _TB_PRICE_DATABASE_PRECISION_
                        );
                    }

                    if ($price > 0 && ($minPrice === false || $minPrice > $price)) {
                        $minPrice = $price;
                        $cheapestProduct = $product['id_product'].'-'.$product['id_product_attribute'];
                    }
                }

                // Check if the cheapest product is in the package
                $inPackage = false;
                foreach ($packageProducts as $product) {
                    if ($product['id_product'].'-'.$product['id_product_attribute'] == $cheapestProduct || $product['id_product'].'-0' == $cheapestProduct) {
                        $inPackage = true;
                    }
                }
                if ($inPackage) {
                    $reductionValue += round(
                        $minPrice * $this->reduction_percent / 100,
                        _TB_PRICE_DATABASE_PRECISION_
                    );
                }
            }

            // Discount (%) on the selection of products
            if ($this->reduction_percent && $this->reduction_product == -2) {
                $selectedProductsReduction = 0;
                $selectedProducts = $this->checkProductRestrictions($context, true);
                if (is_array($selectedProducts)) {
                    foreach ($packageProducts as $product) {
                        if (in_array($product['id_product'].'-'.$product['id_product_attribute'], $selectedProducts)
                            || in_array($product['id_product'].'-0', $selectedProducts)
                        ) {
                            $price = $product['price'];
                            if ($useTax == true) {
                                $price = round(
                                    $price * (1 + $product['rate'] / 100),
                                    _TB_PRICE_DATABASE_PRECISION_
                                );
                            }

                            $selectedProductsReduction += $price * $product['cart_quantity'];
                        }
                    }
                }
                $reductionValue += round(
                    $selectedProductsReduction * $this->reduction_percent / 100,
                    _TB_PRICE_DATABASE_PRECISION_
                );
            }

            // Discount (¤)
            if ($this->reduction_amount > 0) {
                $prorata = 1;
                if (!is_null($package) && count($allProducts)) {
                    $totalProducts = $context->cart->getOrderTotal($useTax, Cart::ONLY_PRODUCTS);
                    if ($totalProducts) {
                        $prorata = $context->cart->getOrderTotal($useTax, Cart::ONLY_PRODUCTS, $package['products']) / $totalProducts;
                    }
                }

                $reductionAmount = $this->reduction_amount;
                $voucherCurrency = new Currency($this->reduction_currency);
                // First we convert the voucher value to the default currency.
                $reductionAmount = Tools::convertPrice(
                    $reductionAmount,
                    $voucherCurrency,
                    false
                );
                // Then we convert the voucher value to the cart currency.
                $reductionAmount = Tools::convertPrice(
                    $reductionAmount,
                    $context->currency,
                    true
                );

                // If it has the same tax application that you need, then it's the right value, whatever the product!
                if ($this->reduction_tax == $useTax) {
                    // The reduction cannot exceed the products total, except when we do not want it to be limited (for the partial use calculation)
                    if ($filter != static::FILTER_ACTION_ALL_NOCAP) {
                        $cartAmount = $context->cart->getOrderTotal($useTax, Cart::ONLY_PRODUCTS);
                        $reductionAmount = min($reductionAmount, $cartAmount);
                    }
                    $reductionValue += $prorata * $reductionAmount;
                } else {
                    if ($this->reduction_product > 0) {
                        foreach ($context->cart->getProducts() as $product) {
                            if ($product['id_product'] == $this->reduction_product) {
                                $productPriceTaxIncluded = $product['price_wt'];
                                $productPriceTaxExcluded = $product['price'];
                                $productVatAmount = $productPriceTaxIncluded - $productPriceTaxExcluded;

                                if ($productVatAmount == 0 || $productPriceTaxExcluded == 0) {
                                    $productVatRate = 0;
                                } else {
                                    $productVatRate = $productVatAmount / $productPriceTaxExcluded;
                                }

                                if ($this->reduction_tax && !$useTax) {
                                    $reductionValue += round(
                                        $prorata * $reductionAmount
                                        / (1 + $productVatRate),
                                        _TB_PRICE_DATABASE_PRECISION_
                                    );
                                } elseif (!$this->reduction_tax && $useTax) {
                                    $reductionValue += round(
                                        $prorata * $reductionAmount
                                        * (1 + $productVatRate),
                                        _TB_PRICE_DATABASE_PRECISION_
                                    );
                                }
                            }
                        }
                    } // Discount (¤) on the whole order
                    elseif ($this->reduction_product == 0) {
                        $cartAmountTaxExcluded = null;
                        $cartAmountTaxIncluded = null;
                        $cartAverageVatRate = $context->cart->getAverageProductsTaxRate($cartAmountTaxExcluded, $cartAmountTaxIncluded);

                        // The reduction cannot exceed the products total, except when we do not want it to be limited (for the partial use calculation)
                        if ($filter != static::FILTER_ACTION_ALL_NOCAP) {
                            $reductionAmount = min($reductionAmount, $this->reduction_tax ? $cartAmountTaxIncluded : $cartAmountTaxExcluded);
                        }

                        if ($this->reduction_tax && !$useTax) {
                            $reductionValue += round(
                                $prorata * $reductionAmount
                                / (1 + $cartAverageVatRate),
                                _TB_PRICE_DATABASE_PRECISION_
                            );
                        } elseif (!$this->reduction_tax && $useTax) {
                            $reductionValue += round(
                                $prorata * $reductionAmount
                                * (1 + $cartAverageVatRate),
                                _TB_PRICE_DATABASE_PRECISION_
                            );
                        }
                    }
                    /*
                     * Reduction on the cheapest or on the selection is not really meaningful and has been disabled in the backend
                     * Please keep this code, so it won't be considered as a bug
                     * elseif ($this->reduction_product == -1)
                     * elseif ($this->reduction_product == -2)
                    */
                }

                // Take care of the other cart rules values if the filter allow it
                if ($filter != static::FILTER_ACTION_ALL_NOCAP) {
                    // Cart values
                    $cart = Context::getContext()->cart;

                    if (!Validate::isLoadedObject($cart)) {
                        $cart = new Cart();
                    }

                    $cartAverageVatRate = $cart->getAverageProductsTaxRate();
                    $currentCartAmount = $useTax ? $cartAmountTaxIncluded : $cartAmountTaxExcluded;

                    foreach ($allCartRulesIds as $currentCartRuleId) {
                        if ((int) $currentCartRuleId['id_cart_rule'] == (int) $this->id) {
                            break;
                        }

                        $previousCartRule = new CartRule((int) $currentCartRuleId['id_cart_rule']);
                        $previousReductionAmount = $previousCartRule->reduction_amount;

                        if ($previousCartRule->reduction_tax && !$useTax) {
                            $previousReductionAmount = round(
                                $previousReductionAmount
                                * $prorata
                                / (1 + $cartAverageVatRate),
                                _TB_PRICE_DATABASE_PRECISION_
                            );
                        } elseif (!$previousCartRule->reduction_tax && $useTax) {
                            $previousReductionAmount = round(
                                $previousReductionAmount
                                * $prorata
                                * (1 + $cartAverageVatRate),
                                _TB_PRICE_DATABASE_PRECISION_
                            );
                        }

                        $currentCartAmount = max($currentCartAmount - (float) $previousReductionAmount, 0);
                    }

                    $reductionValue = min($reductionValue, $currentCartAmount);
                }
            }

            if ($roundType === Order::ROUND_LINE) {
                $reductionValue = Tools::ps_round(
                    $reductionValue,
                    $displayDecimals
                );
            }
        }

        // Free gift
        if ((int) $this->gift_product && in_array($filter, [static::FILTER_ACTION_ALL, static::FILTER_ACTION_ALL_NOCAP, static::FILTER_ACTION_GIFT])) {
            $idAddress = (is_null($package) ? 0 : $package['id_address']);
            foreach ($packageProducts as $product) {
                if ($product['id_product'] == $this->gift_product && ($product['id_product_attribute'] == $this->gift_product_attribute || !(int) $this->gift_product_attribute)) {
                    // The free gift coupon must be applied to one product only (needed for multi-shipping which manage multiple product lists)
                    if (!isset(static::$onlyOneGift[$this->id.'-'.$this->gift_product])
                        || static::$onlyOneGift[$this->id.'-'.$this->gift_product] == $idAddress
                        || static::$onlyOneGift[$this->id.'-'.$this->gift_product] == 0
                        || $idAddress == 0
                        || !$useCache
                    ) {
                        $reductionValue += ($useTax ? $product['price_wt'] : $product['price']);
                        if ($useCache && (!isset(static::$onlyOneGift[$this->id.'-'.$this->gift_product]) || static::$onlyOneGift[$this->id.'-'.$this->gift_product] == 0)) {
                            static::$onlyOneGift[$this->id.'-'.$this->gift_product] = $idAddress;
                        }
                        break;
                    }
                }
            }
        }

        Cache::store($cacheId, $reductionValue);

        return $reductionValue;
    }

    /* When an entity associated to a product rule (product, category, attribute, supplier, manufacturer...) is deleted, the product rules must be updated */

    /**
     * @param string $type
     * @param bool   $activeOnly
     * @param bool   $i18n
     * @param int    $offset
     * @param int    $limit
     * @param string $searchCartRuleName
     *
     * @return array|bool
     * @throws PrestaShopDatabaseException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function getAssociatedRestrictions($type, $activeOnly, $i18n, $offset = null, $limit = null, $searchCartRuleName = '')
    {
        $array = ['selected' => [], 'unselected' => []];

        if (!in_array($type, ['country', 'carrier', 'group', 'cart_rule', 'shop'])) {
            return false;
        }

        $shopList = '';
        if ($type == 'shop') {
            $shops = Context::getContext()->employee->getAssociatedShops();
            if (count($shops)) {
                $shopList = ' AND t.id_shop IN ('.implode(array_map('intval', $shops), ',').') ';
            }
        }

        if ($offset !== null && $limit !== null) {
            $sqlLimit = ' LIMIT '.(int) $offset.', '.(int) ($limit + 1);
        } else {
            $sqlLimit = '';
        }

        if (!Validate::isLoadedObject($this) || $this->{$type.'_restriction'} == 0) {
            $array['selected'] = Db::getInstance()->executeS(
                '
			SELECT t.*'.($i18n ? ', tl.*' : '').', 1 as selected
			FROM `'._DB_PREFIX_.$type.'` t
			'.($i18n ? 'LEFT JOIN `'._DB_PREFIX_.$type.'_lang` tl ON (t.id_'.$type.' = tl.id_'.$type.' AND tl.id_lang = '.(int) Context::getContext()->language->id.')' : '').'
			WHERE 1
			'.($activeOnly ? 'AND t.active = 1' : '').'
			'.(in_array($type, ['carrier', 'shop']) ? ' AND t.deleted = 0' : '').'
			'.($type == 'cart_rule' ? 'AND t.id_cart_rule != '.(int) $this->id : '').
                $shopList.
                (in_array($type, ['carrier', 'shop']) ? ' ORDER BY t.name ASC ' : '').
                (in_array($type, ['country', 'group', 'cart_rule']) && $i18n ? ' ORDER BY tl.name ASC ' : '').
                $sqlLimit
            );
        } else {
            if ($type == 'cart_rule') {
                $array = $this->getCartRuleCombinations($offset, $limit, $searchCartRuleName);
            } else {
                $resource = Db::getInstance()->executeS(
                    '
				SELECT t.*'.($i18n ? ', tl.*' : '').', IF(crt.id_'.$type.' IS NULL, 0, 1) as selected
				FROM `'._DB_PREFIX_.$type.'` t
				'.($i18n ? 'LEFT JOIN `'._DB_PREFIX_.$type.'_lang` tl ON (t.id_'.$type.' = tl.id_'.$type.' AND tl.id_lang = '.(int) Context::getContext()->language->id.')' : '').'
				LEFT JOIN (SELECT id_'.$type.' FROM `'._DB_PREFIX_.'cart_rule_'.$type.'` WHERE id_cart_rule = '.(int) $this->id.') crt ON t.id_'.($type == 'carrier' ? 'reference' : $type).' = crt.id_'.$type.'
				WHERE 1 '.($activeOnly ? ' AND t.active = 1' : '').
                    $shopList
                    .(in_array($type, ['carrier', 'shop']) ? ' AND t.deleted = 0' : '').
                    (in_array($type, ['carrier', 'shop']) ? ' ORDER BY t.name ASC ' : '').
                    (in_array($type, ['country', 'group', 'cart_rule']) && $i18n ? ' ORDER BY tl.name ASC ' : '').
                    $sqlLimit,
                    false
                );
                foreach ($resource as $row) {
                    $array[($row['selected'] || $this->{$type.'_restriction'} == 0) ? 'selected' : 'unselected'][] = $row;
                }
            }
        }

        return $array;
    }

    /**
     * Find the cheapest product
     *
     * @param array $package
     *
     * @return null|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.2
     */
    public function findCheapestProduct($package)
    {
        $context = Context::getContext();
        $cheapestProduct = null;
        $allProducts = $package['products'];

        if ($this->reduction_percent && $this->reduction_product == -1) {
            $minPrice = false;
            $selectedProducts = $this->checkProductRestrictions($context, true);
            foreach ($allProducts as $product) {
                if (!is_array($selectedProducts) ||
                    (!in_array($product['id_product'].'-'.$product['id_product_attribute'], $selectedProducts) && !in_array($product['id_product'].'-0', $selectedProducts))
                ) {
                    continue;
                }

                $price = $product['price'];
                if ($price > 0 && ($minPrice === false || $minPrice > $price)) {
                    $minPrice = $price;
                    $cheapestProduct = $product['id_product'].'-'.$product['id_product_attribute'];
                }
            }
        }

        return $cheapestProduct;
    }

    /**
     * @param int    $offset
     * @param int    $limit
     * @param string $search
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getCartRuleCombinations($offset = null, $limit = null, $search = '')
    {
        $array = [];
        if ($offset !== null && $limit !== null) {
            $sqlLimit = ' LIMIT '.(int) $offset.', '.(int) ($limit + 1);
        } else {
            $sqlLimit = '';
        }

        $array['selected'] = Db::getInstance()->executeS(
            '
		SELECT cr.*, crl.*, 1 AS selected
		FROM '._DB_PREFIX_.'cart_rule cr
		LEFT JOIN '._DB_PREFIX_.'cart_rule_lang crl ON (cr.id_cart_rule = crl.id_cart_rule AND crl.id_lang = '.(int) Context::getContext()->language->id.')
		WHERE cr.id_cart_rule != '.(int) $this->id.($search ? ' AND crl.name LIKE "%'.pSQL($search).'%"' : '').'
		AND (
			cr.cart_rule_restriction = 0
			OR EXISTS (
				SELECT 1
				FROM '._DB_PREFIX_.'cart_rule_combination
				WHERE cr.id_cart_rule = '._DB_PREFIX_.'cart_rule_combination.id_cart_rule_1 AND '.(int) $this->id.' = id_cart_rule_2
			)
			OR EXISTS (
				SELECT 1
				FROM '._DB_PREFIX_.'cart_rule_combination
				WHERE cr.id_cart_rule = '._DB_PREFIX_.'cart_rule_combination.id_cart_rule_2 AND '.(int) $this->id.' = id_cart_rule_1
			)
		) ORDER BY cr.id_cart_rule'.$sqlLimit
        );

        $array['unselected'] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cr.*, crl.*, 1 AS `selected`')
                ->from('cart_rule', 'cr')
                ->innerJoin('cart_rule_lang', 'crl', 'cr.`id_cart_rule` = crl.`id_cart_rule` AND crl.`id_lang` = '.(int) Context::getContext()->language->id)
                ->leftJoin('cart_rule_combination', 'crc1', 'cr.`id_cart_rule` = crc1.`id_cart_rule_1` AND crc1.`id_cart_rule_2` = '.(int) $this->id)
                ->leftJoin('cart_rule_combination', 'crc2', 'cr.`id_cart_rule` = crc2.`id_cart_rule_2` AND crc2.`id_cart_rule_1` = '.(int) $this->id)
                ->where('cr.`cart_rule_restriction` = 1')
                ->where('cr.`id_cart_rule` != '.(int) $this->id)
                ->where($search ? 'crl.`name` LIKE "%'.pSQL($search).'%"' : '')
                ->where('crc1.`id_cart_rule_1` IS NULL')
                ->where('crc2.`id_cart_rule_1` IS NULL')
                ->orderBy('cr.`id_cart_rule`')
                ->limit($limit, $offset)
        );

        return $array;
    }
}
