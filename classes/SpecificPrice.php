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
 * Class SpecificPriceCore
 */
class SpecificPriceCore extends ObjectModel
{
    /** @var array $_specificPriceCache */
    protected static $_specificPriceCache = [];
    /** @var array $_filterOutCache */
    protected static $_filterOutCache = [];
    /** @var array $_cache_priorities */
    protected static $_cache_priorities = [];
    /** @var array $_no_specific_values */
    protected static $_no_specific_values = [];
    /** @var int $id_product */
    public $id_product;
    /** @var int $id_specific_price_rule */
    public $id_specific_price_rule = 0;
    /** @var int $id_cart */
    public $id_cart = 0;
    /** @var int $id_product_attribute */
    public $id_product_attribute;
    /** @var int $id_shop */
    public $id_shop;
    /** @var int $id_shop_group */
    public $id_shop_group;
    /** @var int $id_currency */
    public $id_currency;
    /** @var int $id_country */
    public $id_country;
    /** @var int $id_group */
    public $id_group;
    /** @var int $id_customer */
    public $id_customer;
    /** @var float $price */
    public $price;
    /** @var int $from_quantity */
    public $from_quantity;
    /** @var float $reduction */
    public $reduction;
    /** @var int $reduction_tax */
    public $reduction_tax = 1;
    /** @var string $reduction_type */
    public $reduction_type;
    /** @var string $from */
    public $from;
    /** @var string $to */
    public $to;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'specific_price',
        'primary' => 'id_specific_price',
        'primaryKeyDbType' => 'int(10) unsigned',
        'fields'  => [
            'id_specific_price_rule' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false],
            'id_cart'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_product'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'size' => 10],
            'id_shop'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'dbDefault' => '1'],
            'id_shop_group'          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false],
            'id_currency'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'size' => 10],
            'id_country'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'size' => 10],
            'id_group'               => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'size' => 10],
            'id_customer'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'size' => 10],
            'id_product_attribute'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'size' => 10, 'dbNullable' => false],
            'price'                  => ['type' => self::TYPE_PRICE, 'validate' => 'isNegativePrice', 'required' => true],
            'from_quantity'          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'dbType' => 'mediumint(8) unsigned'],
            'reduction'              => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true],
            'reduction_tax'          => ['type' => self::TYPE_INT, 'validate' => 'isBool', 'required' => true, 'size' => 1, 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'reduction_type'         => ['type' => self::TYPE_STRING, 'validate' => 'isReductionType', 'required' => true, 'values' => ['amount', 'percentage']],
            'from'                   => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'dbNullable' => false],
            'to'                     => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'dbNullable' => false],
        ],
        'keys' => [
            'specific_price' => [
                'id_product_2'           => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['id_product', 'id_product_attribute', 'id_customer', 'id_cart', 'from', 'to', 'id_shop', 'id_shop_group', 'id_currency', 'id_country', 'id_group', 'from_quantity', 'id_specific_price_rule']],
                'from'                   => ['type' => ObjectModel::KEY, 'columns' => ['from']],
                'from_quantity'          => ['type' => ObjectModel::KEY, 'columns' => ['from_quantity']],
                'id_cart'                => ['type' => ObjectModel::KEY, 'columns' => ['id_cart']],
                'id_customer'            => ['type' => ObjectModel::KEY, 'columns' => ['id_customer']],
                'id_product'             => ['type' => ObjectModel::KEY, 'columns' => ['id_product', 'id_shop', 'id_currency', 'id_country', 'id_group', 'id_customer', 'from_quantity', 'from', 'to']],
                'id_product_attribute'   => ['type' => ObjectModel::KEY, 'columns' => ['id_product_attribute']],
                'id_shop'                => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
                'id_specific_price_rule' => ['type' => ObjectModel::KEY, 'columns' => ['id_specific_price_rule']],
                'to'                     => ['type' => ObjectModel::KEY, 'columns' => ['to']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'objectsNodeName' => 'specific_prices',
        'objectNodeName'  => 'specific_price',
        'fields'          => [
            'id_shop_group'        => ['xlink_resource' => 'shop_groups'],
            'id_shop'              => ['xlink_resource' => 'shops', 'required' => true],
            'id_cart'              => ['xlink_resource' => 'carts', 'required' => true],
            'id_product'           => ['xlink_resource' => 'products', 'required' => true],
            'id_product_attribute' => ['xlink_resource' => 'product_attributes'],
            'id_currency'          => ['xlink_resource' => 'currencies', 'required' => true],
            'id_country'           => ['xlink_resource' => 'countries', 'required' => true],
            'id_group'             => ['xlink_resource' => 'groups', 'required' => true],
            'id_customer'          => ['xlink_resource' => 'customers', 'required' => true],
        ],
    ];

    /**
     * @param int $idProduct
     * @param int|bool $idProductAttribute
     * @param int|bool $idCart
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getByProductId($idProduct, $idProductAttribute = false, $idCart = false)
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('*')
                ->from('specific_price')
                ->where('`id_product` = '.(int) $idProduct)
                ->where($idProductAttribute ? '`id_product_attribute` = '.(int) $idProductAttribute : '')
                ->where('`id_cart` = '.(int) $idCart)
        );
    }

    /**
     * @param int| $idCart
     * @param int|bool $idProduct
     * @param int|bool $idProductAttribute
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function deleteByIdCart($idCart, $idProduct = false, $idProductAttribute = false)
    {
        if (! (int)$idCart) {
            return false;
        }
        return Db::getInstance()->delete(
            'specific_price',
            '`id_cart` = '.(int) $idCart.($idProduct ? ' AND `id_product` = '.(int) $idProduct.' AND `id_product_attribute` = '.(int) $idProductAttribute : '')
        );
    }

    /**
     * @param int $idProduct
     * @param int|bool $idProductAttribute
     * @param int $idCart
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getIdsByProductId($idProduct, $idProductAttribute = false, $idCart = 0)
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_specific_price`')
                ->from('specific_price')
                ->where('`id_product` = '.(int) $idProduct)
                ->where('`id_product_attribute` = '.(int) $idProductAttribute)
                ->where('`id_cart` = '.(int) $idCart)
        );
    }

    /**
     * @param int $idProduct
     * @param int $idShop
     * @param int $idCurrency
     * @param int $idCountry
     * @param int $idGroup
     * @param int $quantity
     * @param int|null $idProductAttribute
     * @param int $idCustomer
     * @param int $idCart
     * @param int $realQuantity
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getSpecificPrice($idProduct, $idShop, $idCurrency, $idCountry, $idGroup, $quantity, $idProductAttribute = null, $idCustomer = 0, $idCart = 0, $realQuantity = 0)
    {
        if (!static::isFeatureActive()) {
            return [];
        }
        /*
        ** The date is not taken into account for the cache, but this is for the better because it keeps the consistency for the whole script.
        ** The price must not change between the top and the bottom of the page
        */

        $key = ((int) $idProduct.'-'.(int) $idShop.'-'.(int) $idCurrency.'-'.(int) $idCountry.'-'.(int) $idGroup.'-'.(int) $quantity.'-'.(int) $idProductAttribute.'-'.(int) $idCart.'-'.(int) $idCustomer.'-'.(int) $realQuantity);
        if (!array_key_exists($key, static::$_specificPriceCache)) {
            $queryExtra = static::computeExtraConditions($idProduct, $idProductAttribute, $idCustomer, $idCart);
            $fromQuantity = (Configuration::get('PS_QTY_DISCOUNT_ON_COMBINATION') || !$idCart || !$realQuantity) ? (int) $quantity : max(1, (int) $realQuantity);

            static::$_specificPriceCache[$key] = Db::readOnly()->getRow(
                (new DbQuery())
                    ->select('*, '.static::_getScoreQuery($idProduct, $idShop, $idCurrency, $idCountry, $idGroup, $idCustomer))
                    ->from(bqSQL(static::$definition['table']))
                    ->where('`id_shop` '.static::formatIntInQuery(0, $idShop))
                    ->where('`id_currency` '.static::formatIntInQuery(0, $idCurrency))
                    ->where('`id_country` '.static::formatIntInQuery(0, $idCountry))
                    ->where('`id_group` '.static::formatIntInQuery(0, $idGroup).' '.$queryExtra)
                    ->where('IF(`from_quantity` > 1, `from_quantity`, 0) <= '.(int) $fromQuantity)
                    ->orderBy('`id_cart` DESC')
                    ->orderBy('`id_product_attribute` DESC')
                    ->orderBy('`from_quantity` DESC')
                    ->orderBy('`id_specific_price_rule` ASC')
                    ->orderBy('`score` DESC')
                    ->orderBy('`to` DESC')
                    ->orderBy('`from` DESC')
            );
        }

        return static::$_specificPriceCache[$key];
    }

    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function isFeatureActive()
    {
        static $featureActive = null;

        if ($featureActive === null) {
            $featureActive = Configuration::get('PS_SPECIFIC_PRICE_FEATURE_ACTIVE');
        }

        return $featureActive;
    }

    /**
     * Remove or add useless fields value depending on the values in the database (cache friendly)
     *
     * @param int|null $idProduct
     * @param int|null $idProductAttribute
     * @param int|null $idCustomer
     * @param int|null $idCart
     * @param string|null $beginning
     * @param string|null $ending
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function computeExtraConditions($idProduct, $idProductAttribute, $idCustomer, $idCart, $beginning = null, $ending = null)
    {
        $firstDate = date('Y-m-d 00:00:00');
        $lastDate = date('Y-m-d 23:59:59');
        $now = date('Y-m-d H:i:00');
        if ($beginning === null) {
            $beginning = $now;
        }
        if ($ending === null) {
            $ending = $now;
        }
        $idCustomer = (int) $idCustomer;
        $idCart = (int) $idCart;

        $queryExtra = '';

        if ($idProduct !== null) {
            $queryExtra .= static::filterOutField('id_product', $idProduct);
        }

        $queryExtra .= static::filterOutField('id_customer', $idCustomer);

        if ($idProductAttribute !== null) {
            $queryExtra .= static::filterOutField('id_product_attribute', $idProductAttribute);
        }

        $queryExtra .= static::filterOutField('id_cart', $idCart);

        if ($ending == $now && $beginning == $now) {
            $key = __FUNCTION__.'-'.$firstDate.'-'.$lastDate;
            if (!array_key_exists($key, static::$_filterOutCache)) {
                $conn = Db::readOnly();
                $fromSpecificCount = $conn->getValue(
                    (new DbQuery())
                        ->select('1')
                        ->from('specific_price')
                        ->where('`from` BETWEEN \''.$firstDate.'\' AND \''.$lastDate.'\'')
                );
                $toSpecificCount = $conn->getValue(
                    (new DbQuery())
                        ->select('1')
                        ->from('specific_price')
                        ->where('`to` BETWEEN \''.$firstDate.'\' AND \''.$lastDate.'\'')
                );
                static::$_filterOutCache[$key] = [$fromSpecificCount, $toSpecificCount];
            } else {
                list($fromSpecificCount, $toSpecificCount) = static::$_filterOutCache[$key];
            }
        } else {
            $fromSpecificCount = $toSpecificCount = 1;
        }

        // if the from and to is not reached during the current day, just change $ending & $beginning to any date of the day to improve the cache
        if (!$fromSpecificCount && !$toSpecificCount) {
            $ending = $beginning = $firstDate;
        }

        $queryExtra .= ' AND (`from` = \'0000-00-00 00:00:00\' OR \''.$beginning.'\' >= `from`) AND (`to` = \'0000-00-00 00:00:00\' OR \''.$ending.'\' <= `to`)';

        return $queryExtra;
    }

    /**
     * Remove or add a field value to a query if values are present in the database (cache friendly)
     *
     * @param string $fieldName
     * @param int $fieldValue
     * @param int $threshold
     *
     * @return string
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    protected static function filterOutField($fieldName, $fieldValue, $threshold = 1000)
    {
        $queryExtra = 'AND `'.$fieldName.'` = 0 ';
        if ($fieldValue == 0 || array_key_exists($fieldName, static::$_no_specific_values)) {
            return $queryExtra;
        }
        $keyCache = __FUNCTION__.'-'.$fieldName.'-'.$threshold;
        $specificList = [];
        if (!array_key_exists($keyCache, static::$_filterOutCache)) {
            $queryCount = 'SELECT COUNT(DISTINCT `'.$fieldName.'`) FROM `'._DB_PREFIX_.'specific_price` WHERE `'.$fieldName.'` != 0';
            $conn = Db::readOnly();
            $specificCount = $conn->getValue($queryCount);
            if ($specificCount == 0) {
                static::$_no_specific_values[$fieldName] = true;

                return $queryExtra;
            }
            if ($specificCount < $threshold) {
                $query = 'SELECT DISTINCT `'.$fieldName.'` FROM `'._DB_PREFIX_.'specific_price` WHERE `'.$fieldName.'` != 0';
                $tmpSpecificList = $conn->getArray($query);
                foreach ($tmpSpecificList as $value) {
                    $specificList[] = $value[$fieldName];
                }
            }
            static::$_filterOutCache[$keyCache] = $specificList;
        } else {
            $specificList = static::$_filterOutCache[$keyCache];
        }

        // $specific_list is empty if the threshold is reached
        if (empty($specificList) || in_array($fieldValue, $specificList)) {
            $queryExtra = 'AND `'.$fieldName.'` '.static::formatIntInQuery(0, $fieldValue).' ';
        }

        return $queryExtra;
    }

    /**
     * @param int $firstValue
     * @param int $secondValue
     *
     * @return string
     */
    protected static function formatIntInQuery($firstValue, $secondValue)
    {
        $firstValue = (int) $firstValue;
        $secondValue = (int) $secondValue;
        if ($firstValue != $secondValue) {
            return 'IN ('.$firstValue.', '.$secondValue.')';
        } else {
            return ' = '.$firstValue;
        }
    }

    /**
     * score generation for quantity discount
     *
     * @throws PrestaShopException
     */
    protected static function _getScoreQuery($idProduct, $idShop, $idCurrency, $idCountry, $idGroup, $idCustomer)
    {
        $select = '(';

        $priority = static::getPriority($idProduct);
        $definition = array_keys(static::$definition['fields']);
        foreach (array_reverse($priority) as $k => $field) {
            $snakeCaseField = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $field))));
            if (!empty($field) && isset($$snakeCaseField) && in_array($field, $definition)) {
                $select .= ' IF (`'.bqSQL($field).'` = '.(int) $$snakeCaseField.', '.pow(2, $k + 1).', 0) + ';
            }
        }

        return rtrim($select, ' +').') AS `score`';
    }

    /**
     * @param int $idProduct
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function getPriority($idProduct)
    {
        if (!static::isFeatureActive()) {
            return explode(';', (string)Configuration::get('PS_SPECIFIC_PRICE_PRIORITIES'));
        }

        if (!isset(static::$_cache_priorities[(int) $idProduct])) {
            static::$_cache_priorities[(int) $idProduct] = Db::readOnly()->getValue(
                (new DbQuery())
                    ->select('`priority`, `id_specific_price_priority`')
                    ->from('specific_price_priority')
                    ->where('`id_product` = '.(int) $idProduct)
                    ->orderBy('`id_specific_price_priority` DESC')

            );
        }

        $priority = static::$_cache_priorities[(int) $idProduct];

        if (!$priority) {
            $priority = Configuration::get('PS_SPECIFIC_PRICE_PRIORITIES');
        }
        $priority = 'id_customer;'.$priority;

        return explode(';', $priority);
    }

    /**
     * @param array $priorities
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function setPriorities($priorities)
    {
        $value = '';
        if (is_array($priorities)) {
            foreach ($priorities as $priority) {
                $value .= pSQL($priority).';';
            }
        }

        static::deletePriorities();

        return Configuration::updateValue('PS_SPECIFIC_PRICE_PRIORITIES', rtrim($value, ';'));
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function deletePriorities()
    {
        return Db::getInstance()->execute('TRUNCATE `'._DB_PREFIX_.'specific_price_priority`');
    }

    /**
     * @param int $idProduct
     * @param array $priorities
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function setSpecificPriority($idProduct, $priorities)
    {
        $value = '';
        foreach ($priorities as $priority) {
            $value .= pSQL($priority).';';
        }

        return Db::getInstance()->execute(
            '
		INSERT INTO `'._DB_PREFIX_.'specific_price_priority` (`id_product`, `priority`)
		VALUES ('.(int) $idProduct.',\''.pSQL(rtrim($value, ';')).'\')
		ON DUPLICATE KEY UPDATE `priority` = \''.pSQL(rtrim($value, ';')).'\'
		'
        );
    }

    /**
     * @param int $idProduct
     * @param int $idShop
     * @param int $idCurrency
     * @param int $idCountry
     * @param int $idGroup
     * @param int|null $idProductAttribute
     * @param bool $allCombinations
     * @param int $idCustomer
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getQuantityDiscounts($idProduct, $idShop, $idCurrency, $idCountry, $idGroup, $idProductAttribute = null, $allCombinations = false, $idCustomer = 0)
    {
        if (!static::isFeatureActive()) {
            return [];
        }

        $queryExtra = static::computeExtraConditions($idProduct, ((!$allCombinations) ? $idProductAttribute : null), $idCustomer, null);
        $result = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('*, '.static::_getScoreQuery($idProduct, $idShop, $idCurrency, $idCountry, $idGroup, $idCustomer))
                ->from('specific_price')
                ->where('`id_shop` '.static::formatIntInQuery(0, $idShop))
                ->where('`id_currency` '.static::formatIntInQuery(0, $idCurrency))
                ->where('`id_country` '.static::formatIntInQuery(0, $idCountry))
                ->where('`id_group` '.static::formatIntInQuery(0, $idGroup).' '.$queryExtra)
                ->orderBy('`from_quantity` ASC, `id_specific_price_rule` ASC, `score` DESC, `to` DESC, `from` DESC')
        );

        $targetedPrices = [];
        $lastQuantity = [];

        foreach ($result as $specificPrice) {
            if (!isset($lastQuantity[(int) $specificPrice['id_product_attribute']])) {
                $lastQuantity[(int) $specificPrice['id_product_attribute']] = $specificPrice['from_quantity'];
            } elseif ($lastQuantity[(int) $specificPrice['id_product_attribute']] == $specificPrice['from_quantity']) {
                continue;
            }

            $lastQuantity[(int) $specificPrice['id_product_attribute']] = $specificPrice['from_quantity'];
            if ($specificPrice['from_quantity'] > 1) {
                $targetedPrices[] = $specificPrice;
            }
        }

        return $targetedPrices;
    }

    /**
     * @param int $idProduct
     * @param int $idShop
     * @param int $idCurrency
     * @param int $idCountry
     * @param int $idGroup
     * @param int $quantity
     * @param int|null $idProductAttribute
     * @param int $idCustomer
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getQuantityDiscount($idProduct, $idShop, $idCurrency, $idCountry, $idGroup, $quantity, $idProductAttribute = null, $idCustomer = 0)
    {
        if (!static::isFeatureActive()) {
            return [];
        }

        $queryExtra = static::computeExtraConditions($idProduct, $idProductAttribute, $idCustomer, null);

        return Db::readOnly()->getRow(
            (new DbQuery())
                ->select('*, '.static::_getScoreQuery($idProduct, $idShop, $idCurrency, $idCountry, $idGroup, $idCustomer))
                ->from('specific_price')
                ->where('`id_shop` '.static::formatIntInQuery(0, $idShop))
                ->where('`id_currency` '.static::formatIntInQuery(0, $idCurrency))
                ->where('`id_country` '.static::formatIntInQuery(0, $idCountry))
                ->where('`id_group` '.static::formatIntInQuery(0, $idGroup))
                ->where('`from_quantity` >= '.(int) $quantity.' '.$queryExtra)
                ->orderBy('`from_quantity` DESC, `score` DESC, `to` DESC, `from` DESC')
        );
    }

    /**
     * @param int $idShop
     * @param int $idCurrency
     * @param int $idCountry
     * @param int $idGroup
     * @param string $beginning
     * @param string $ending
     * @param int $idCustomer
     * @param bool $withCombinationId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getProductIdByDate($idShop, $idCurrency, $idCountry, $idGroup, $beginning, $ending, $idCustomer = 0, $withCombinationId = false)
    {
        if (!static::isFeatureActive()) {
            return [];
        }

        $queryExtra = static::computeExtraConditions(null, null, $idCustomer, null, $beginning, $ending);
        $results = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_product`, `id_product_attribute`')
                ->from('specific_price')
                ->where('`id_shop` '.static::formatIntInQuery(0, $idShop))
                ->where('`id_currency` '.static::formatIntInQuery(0, $idCurrency))
                ->where('`id_country` '.static::formatIntInQuery(0, $idCountry))
                ->where('`id_group` '.static::formatIntInQuery(0, $idGroup))
                ->where('`from_quantity` = 1')
                ->where('`reduction` > 0 '.$queryExtra)
        );
        $idsProduct = [];
        foreach ($results as $value) {
            $idsProduct[] = $withCombinationId ? ['id_product' => (int) $value['id_product'], 'id_product_attribute' => (int) $value['id_product_attribute']] : (int) $value['id_product'];
        }

        return $idsProduct;
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function deleteByProductId($idProduct)
    {
        if (Db::getInstance()->delete('specific_price', '`id_product` = '.(int) $idProduct)) {
            // Refresh cache of feature detachable
            Configuration::updateGlobalValue('PS_SPECIFIC_PRICE_FEATURE_ACTIVE', static::isCurrentlyUsed('specific_price'));

            return true;
        }

        return false;
    }

    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idShop
     * @param int $idGroup
     * @param int $idCountry
     * @param int $idCurrency
     * @param int $idCustomer
     * @param int $fromQuantity
     * @param string $from
     * @param string $to
     * @param bool $rule
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function exists($idProduct, $idProductAttribute, $idShop, $idGroup, $idCountry, $idCurrency, $idCustomer, $fromQuantity, $from, $to, $rule = false)
    {
        $rule = ' AND `id_specific_price_rule`'.(!$rule ? ' = 0' : ' != 0');

        return (int) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_specific_price`')
                ->from('specific_price')
                ->where('`id_product` = '.(int) $idProduct)
                ->where('`id_product_attribute` = '.(int) $idProductAttribute)
                ->where('`id_shop` = '.(int) $idShop)
                ->where('`id_group` = '.(int) $idGroup)
                ->where('`id_country` = '.(int) $idCountry)
                ->where('`id_currency` = '.(int) $idCurrency)
                ->where('`id_customer` = '.(int) $idCustomer)
                ->where('`from_quantity` = '.(int) $fromQuantity)
                ->where('`from` >= \''.pSQL($from).'\'')
                ->where('`to` <= \''.pSQL($to).'\''.$rule)
        );
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
        if (parent::update($nullValues)) {
            // Flush cache when we updating a new specific price
            $this->flushCache();

            return true;
        }

        return false;
    }

    /**
     * Flush local cache
     *
     * @return void
     */
    protected function flushCache()
    {
        static::$_specificPriceCache = [];
        static::$_filterOutCache = [];
        static::$_cache_priorities = [];
        static::$_no_specific_values = [];
        Product::flushPriceCache();
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (parent::delete()) {
            // Flush cache when we deletind a new specific price
            $this->flushCache();
            // Refresh cache of feature detachable
            Configuration::updateGlobalValue('PS_SPECIFIC_PRICE_FEATURE_ACTIVE', static::isCurrentlyUsed($this->def['table']));

            return true;
        }

        return false;
    }

    /**
     * @param bool $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function duplicate($idProduct = false)
    {
        if ($idProduct) {
            $this->id_product = (int) $idProduct;
        }
        unset($this->id);

        return $this->add();
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
        if (parent::add($autoDate, $nullValues)) {
            // Flush cache when we adding a new specific price
            $this->flushCache();
            // Set cache of feature detachable to true
            Configuration::updateGlobalValue('PS_SPECIFIC_PRICE_FEATURE_ACTIVE', '1');

            return true;
        }

        return false;
    }
}
