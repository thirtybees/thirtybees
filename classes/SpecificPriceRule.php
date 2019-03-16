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
 * Class SpecificPriceRuleCore
 *
 * @since 1.0.0
 */
class SpecificPriceRuleCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'specific_price_rule',
        'primary' => 'id_specific_price_rule',
        'fields'  => [
            'name'           => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName',   'required' => true],
            'id_shop'        => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',    'required' => true],
            'id_country'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',    'required' => true],
            'id_currency'    => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',    'required' => true],
            'id_group'       => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',    'required' => true],
            'from_quantity'  => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt',   'required' => true],
            'price'          => ['type' => self::TYPE_PRICE,  'validate' => 'isNegativePrice', 'required' => true],
            'reduction'      => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',         'required' => true],
            'reduction_tax'  => ['type' => self::TYPE_INT,    'validate' => 'isBool',          'required' => true],
            'reduction_type' => ['type' => self::TYPE_STRING, 'validate' => 'isReductionType', 'required' => true],
            'from'           => ['type' => self::TYPE_DATE,   'validate' => 'isDateFormat',    'required' => false],
            'to'             => ['type' => self::TYPE_DATE,   'validate' => 'isDateFormat',    'required' => false],
        ],
    ];
    /** @var bool $rules_application_enable */
    protected static $rules_application_enable = true;
    /** @var string $name */
    public $name;
    /** @var int $id_shop */
    public $id_shop;
    /** @var int $id_currency */
    public $id_currency;
    /** @var int $id_country */
    public $id_country;
    /** @var int $id_group */
    public $id_group;
    /** @var int $from_quantity */
    public $from_quantity;
    /** @var float $price */
    public $price;
    /** @var float $reduction */
    public $reduction;
    /** @var int $reduction_tax */
    public $reduction_tax;
    /** @var string $reduction_type */
    public $reduction_type;
    /** @var string $from */
    public $from;
    /** @var string $to */
    public $to;
    /** @var array $webserviceParameters */
    protected $webserviceParameters = [
        'objectsNodeName' => 'specific_price_rules',
        'objectNodeName'  => 'specific_price_rule',
        'fields'          => [
            'id_shop'     => ['xlink_resource' => 'shops', 'required' => true],
            'id_country'  => ['xlink_resource' => 'countries', 'required' => true],
            'id_currency' => ['xlink_resource' => 'currencies', 'required' => true],
            'id_group'    => ['xlink_resource' => 'groups', 'required' => true],
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function disableAnyApplication()
    {
        // @codingStandardsIgnoreStart
        static::$rules_application_enable = false;
        // @codingStandardsIgnoreEnd
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function enableAnyApplication()
    {
        // @codingStandardsIgnoreStart
        static::$rules_application_enable = true;
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param array|bool $products
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public static function applyAllRules($products = false)
    {
        // @codingStandardsIgnoreStart
        if (!static::$rules_application_enable) {
            return;
        }
        // @codingStandardsIgnoreEnd

        $rules = new PrestaShopCollection('SpecificPriceRule');
        foreach ($rules as $rule) {
            /** @var SpecificPriceRule $rule */
            $rule->apply($products);
        }
    }

    /**
     * @param bool $products
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function apply($products = false)
    {
        // @codingStandardsIgnoreStart
        if (!static::$rules_application_enable) {
            return;
        }
        // @codingStandardsIgnoreEnd

        $this->resetApplication($products);
        $products = $this->getAffectedProducts($products);
        foreach ($products as $product) {
            static::applyRuleToProduct((int) $this->id, (int) $product['id_product'], (int) $product['id_product_attribute']);
        }
    }

    /**
     * @param bool $products
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public function resetApplication($products = false)
    {
        $where = '';
        if ($products && is_array($products) && count($products)) {
            $where .= ' AND id_product IN ('.implode(', ', array_map('intval', $products)).')';
        }

        return Db::getInstance()->delete('specific_price', '`id_specific_price_rule` = '.(int) $this->id.$where);
    }

    /**
     * Return the product list affected by this specific rule.
     *
     * @param bool|array $products Products list limitation.
     *
     * @return array Affected products list IDs.
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getAffectedProducts($products = false)
    {
        $conditionsGroup = $this->getConditions();
        $currentShopId = Context::getContext()->shop->id;

        $result = [];

        if ($conditionsGroup) {
            foreach ($conditionsGroup as $idConditionGroup => $conditionGroup) {
                // Base request
                $query = (new DbQuery())
                    ->select('DISTINCT p.`id_product`')
                    ->from('product', 'p')
                    ->leftJoin('product_shop', 'ps', 'p.`id_product` = ps.`id_product`')
                    ->where('ps.id_shop = '.(int) $currentShopId)
                ;

                $attributesJoinAdded = false;

                // Add the conditions
                foreach ($conditionGroup as $idCondition => $condition) {
                    if ($condition['type'] == 'attribute') {
                        if (!$attributesJoinAdded) {
                            $query->select('pa.`id_product_attribute`')
                                ->leftJoin('product_attribute', 'pa', 'p.`id_product` = pa.`id_product`')
                                ->join(Shop::addSqlAssociation('product_attribute', 'pa', false));

                            $attributesJoinAdded = true;
                        }

                        $query->leftJoin('product_attribute_combination', 'pac'.(int) $idCondition, 'pa.`id_product_attribute` = pac'.(int) $idCondition.'.`id_product_attribute`')
                            ->where('pac'.(int) $idCondition.'.`id_attribute` = '.(int) $condition['value']);
                    } elseif ($condition['type'] == 'manufacturer') {
                        $query->where('p.id_manufacturer = '.(int) $condition['value']);
                    } elseif ($condition['type'] == 'category') {
                        $query->leftJoin('category_product', 'cp'.(int) $idCondition, 'p.`id_product` = cp'.(int) $idCondition.'.`id_product`')
                            ->where('cp'.(int) $idCondition.'.id_category = '.(int) $condition['value']);
                    } elseif ($condition['type'] == 'supplier') {
                        $query->where(
                            'EXISTS(
							SELECT
								`ps'.(int) $idCondition.'`.`id_product`
							FROM
								`'._DB_PREFIX_.'product_supplier` `ps'.(int) $idCondition.'`
							WHERE
								`p`.`id_product` = `ps'.(int) $idCondition.'`.`id_product`
								AND `ps'.(int) $idCondition.'`.`id_supplier` = '.(int) $condition['value'].'
						)'
                        );
                    } elseif ($condition['type'] == 'feature') {
                        $query->leftJoin('feature_product', 'fp'.(int) $idCondition, 'p.`id_product` = fp'.(int) $idCondition.'.`id_product`')
                            ->where('fp'.(int) $idCondition.'.`id_feature_value` = '.(int) $condition['value']);
                    }
                }

                // Products limitation
                if ($products && count($products)) {
                    $query->where('p.`id_product` IN ('.implode(', ', array_map('intval', $products)).')');
                }

                // Force the column id_product_attribute if not requested
                if (!$attributesJoinAdded) {
                    $query->select('NULL as `id_product_attribute`');
                }

                $result = array_merge($result, Db::getInstance()->executeS($query));
            }
        } else {
            // All products without conditions
            if ($products && count($products)) {
                $query = new DbQuery();
                $query->select('p.`id_product`')
                    ->select('NULL as `id_product_attribute`')
                    ->from('product', 'p')
                    ->leftJoin('product_shop', 'ps', 'p.`id_product` = ps.`id_product`')
                    ->where('ps.id_shop = '.(int) $currentShopId);
                $query->where('p.`id_product` IN ('.implode(', ', array_map('intval', $products)).')');
                $result = Db::getInstance()->executeS($query);
            } else {
                $result = [['id_product' => 0, 'id_product_attribute' => null]];
            }

        }

        return $result;
    }

    /**
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getConditions()
    {
        $conditions = Db::getInstance()->executeS(
            '
			SELECT g.*, c.*
			FROM '._DB_PREFIX_.'specific_price_rule_condition_group g
			LEFT JOIN '._DB_PREFIX_.'specific_price_rule_condition c
				ON (c.id_specific_price_rule_condition_group = g.id_specific_price_rule_condition_group)
			WHERE g.id_specific_price_rule='.(int) $this->id
        );
        $conditionsGroup = [];
        if ($conditions) {
            foreach ($conditions as &$condition) {
                if ($condition['type'] == 'attribute') {
                    $condition['id_attribute_group'] = Db::getInstance()->getValue(
                        'SELECT id_attribute_group
							 FROM '._DB_PREFIX_.'attribute
							 WHERE id_attribute='.(int) $condition['value']
                    );
                } elseif ($condition['type'] == 'feature') {
                    $condition['id_feature'] = Db::getInstance()->getValue(
                        'SELECT id_feature
							 FROM '._DB_PREFIX_.'feature_value
							 WHERE id_feature_value='.(int) $condition['value']
                    );
                }
                $conditionsGroup[(int) $condition['id_specific_price_rule_condition_group']][] = $condition;
            }
        }

        return $conditionsGroup;
    }

    /**
     * @param int  $idRule
     * @param int  $idProduct
     * @param null $idProductAttribute
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function applyRuleToProduct($idRule, $idProduct, $idProductAttribute = null)
    {
        $rule = new static((int) $idRule);
        if (!Validate::isLoadedObject($rule) || !Validate::isUnsignedInt($idProduct)) {
            return false;
        }

        $specificPrice = new SpecificPrice();
        $specificPrice->id_specific_price_rule = (int) $rule->id;
        $specificPrice->id_product = (int) $idProduct;
        $specificPrice->id_product_attribute = (int) $idProductAttribute;
        $specificPrice->id_customer = 0;
        $specificPrice->id_shop = (int) $rule->id_shop;
        $specificPrice->id_country = (int) $rule->id_country;
        $specificPrice->id_currency = (int) $rule->id_currency;
        $specificPrice->id_group = (int) $rule->id_group;
        $specificPrice->from_quantity = (int) $rule->from_quantity;
        $specificPrice->price = round(
            $rule->price,
            _TB_PRICE_DATABASE_PRECISION_
        );
        $specificPrice->reduction_type = $rule->reduction_type;
        $specificPrice->reduction_tax = $rule->reduction_tax;
        $specificPrice->reduction = ($rule->reduction_type === 'percentage' ?
            round(
                $rule->reduction / 100,
                _TB_PRICE_DATABASE_PRECISION_
            ) :
            (float) $rule->reduction
        );
        $specificPrice->from = $rule->from;
        $specificPrice->to = $rule->to;

        return $specificPrice->add();
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
        $this->deleteConditions();
        Db::getInstance()->delete('specific_price', '`id_specific_price_rule` = '.(int) $this->id);

        return parent::delete();
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteConditions()
    {
        $idsConditionGroup = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_specific_price_rule_condition_group`')
                ->from('specific_price_rule_condition_group')
                ->where('`id_specific_price_rule` = '.(int) $this->id)
        );
        if ($idsConditionGroup) {
            foreach ($idsConditionGroup as $row) {
                Db::getInstance()->delete('specific_price_rule_condition_group', '`id_specific_price_rule_condition_group` = '.(int) $row['id_specific_price_rule_condition_group']);
                Db::getInstance()->delete('specific_price_rule_condition', '`id_specific_price_rule_condition_group` = '.(int) $row['id_specific_price_rule_condition_group']);
            }
        }
    }

    /**
     * @param array $conditions
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addConditions($conditions)
    {
        if (!is_array($conditions)) {
            return false;
        }

        $result = Db::getInstance()->insert(
            'specific_price_rule_condition_group',
            [
                'id_specific_price_rule' => (int) $this->id,
            ]
        );
        if (!$result) {
            return false;
        }
        $idSpecificPriceRuleConditionGroup = (int) Db::getInstance()->Insert_ID();
        foreach ($conditions as $condition) {
            $result = Db::getInstance()->insert(
                'specific_price_rule_condition',
                [
                    'id_specific_price_rule_condition_group' => (int) $idSpecificPriceRuleConditionGroup,
                    'type'                                   => pSQL($condition['type']),
                    'value'                                  => round(
                        $condition['value'],
                        _TB_PRICE_DATABASE_PRECISION_
                    ),
                ]
            );
            if (!$result) {
                return false;
            }
        }

        return true;
    }
}
