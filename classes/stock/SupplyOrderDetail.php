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
 * Class SupplyOrderDetailCore
 *
 * @since 1.0.0
 */
class SupplyOrderDetailCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @var int Supply order
     */
    public $id_supply_order;

    /**
     * @var int Product ordered
     */
    public $id_product;

    /**
     * @var int Product attribute ordered
     */
    public $id_product_attribute;

    /**
     * @var string Product reference
     */
    public $reference;

    /**
     * @var string Product supplier reference
     */
    public $supplier_reference;

    /**
     * @var int Product name
     */
    public $name;

    /**
     * @var int Product EAN13
     */
    public $ean13;

    /**
     * @var string UPC
     */
    public $upc;

    /**
     * @var int Currency used to buy this particular product
     */
    public $id_currency;

    /**
     * @var float Exchange rate between $id_currency and SupplyOrder::$id_ref_currency, at the time
     */
    public $exchange_rate;

    /**
     * @var float Unit price without discount, without tax
     */
    public $unit_price_te = 0;

    /**
     * @var int Quantity ordered
     */
    public $quantity_expected = 0;

    /**
     * @var int Quantity received
     */
    public $quantity_received = 0;

    /**
     * @var float This defines the price of the product, considering the number of units to buy.
     * ($unit_price_te * $quantity), without discount, without tax
     */
    public $price_te = 0;

    /**
     * @var float Supplier discount rate for a given product
     */
    public $discount_rate = 0;

    /**
     * @var float Supplier discount value (($discount_rate / 100) * $price_te), without tax
     */
    public $discount_value_te = 0;

    /**
     * @var float ($price_te - $discount_value_te), with discount, without tax
     */
    public $price_with_discount_te = 0;

    /**
     * @var int Tax rate for the given product
     */
    public $tax_rate = 0;

    /**
     * @var float Tax value for the given product
     */
    public $tax_value = 0;

    /**
     * @var float ($price_with_discount_te + $tax_value)
     */
    public $price_ti = 0;

    /**
     * @var float Tax value of the given product after applying the global order discount (i.e. if SupplyOrder::discount_rate is set)
     */
    public $tax_value_with_order_discount = 0;

    /**
     * @var float This is like $price_with_discount_te, considering the global order discount.
     * (i.e. if SupplyOrder::discount_rate is set)
     */
    public $price_with_order_discount_te = 0;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'supply_order_detail',
        'primary' => 'id_supply_order_detail',
        'fields'  => [
            'id_supply_order'               => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'id_product'                    => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'id_product_attribute'          => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'reference'                     => ['type' => self::TYPE_STRING, 'validate' => 'isReference'                      ],
            'supplier_reference'            => ['type' => self::TYPE_STRING, 'validate' => 'isReference'                      ],
            'name'                          => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'ean13'                         => ['type' => self::TYPE_STRING, 'validate' => 'isEan13'                          ],
            'upc'                           => ['type' => self::TYPE_STRING, 'validate' => 'isUpc'                            ],
            'id_currency'                   => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'exchange_rate'                 => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat',       'required' => true],
            'unit_price_te'                 => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',       'required' => true],
            'quantity_expected'             => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'required' => true],
            'quantity_received'             => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                    ],
            'price_te'                      => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',       'required' => true],
            'discount_rate'                 => ['type' => self::TYPE_FLOAT,  'validate' => 'isPercentage',  'required' => true],
            'discount_value_te'             => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',       'required' => true],
            'price_with_discount_te'        => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',       'required' => true],
            'tax_rate'                      => ['type' => self::TYPE_FLOAT,  'validate' => 'isPercentage',  'required' => true],
            'tax_value'                     => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',       'required' => true],
            'price_ti'                      => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',       'required' => true],
            'tax_value_with_order_discount' => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',       'required' => true],
            'price_with_order_discount_te'  => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',       'required' => true],
        ],
    ];

    /**
     * @see ObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'objectsNodeName' => 'supply_order_details',
        'objectNodeName'  => 'supply_order_detail',
        'fields'          => [
            'id_supply_order'      => ['xlink_resource' => 'supply_orders'],
            'id_product'           => ['xlink_resource' => 'products'],
            'id_product_attribute' => ['xlink_resource' => 'combinations'],
        ],
        'hidden_fields'   => [
            'id_currency',
        ],
    ];

    /**
     * @see ObjectModel::update()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($nullValues = false)
    {
        $this->calculatePrices();

        parent::update($nullValues);
    }

    /**
     * @see ObjectModel::add()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false)
    {
        $this->calculatePrices();

        parent::add($autoDate, $nullValues);
    }

    /**
     * Calculates all prices for this product based on its quantity and unit price
     * Applies discount if necessary
     * Calculates tax value, function of tax rate
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function calculatePrices()
    {
        // calculates entry price
        $this->price_te = round(
            $this->unit_price_te * (int) $this->quantity_expected,
            _TB_PRICE_DATABASE_PRECISION_
        );

        // calculates entry discount value
        if ($this->discount_rate) {
            $this->discount_value_te = round(
                $this->price_te * ($this->discount_rate / 100),
                _TB_PRICE_DATABASE_PRECISION_
            );
        }

        // calculates entry price with discount
        $this->price_with_discount_te = $this->price_te
                                        - $this->discount_value_te;

        // calculates tax value
        $this->tax_value = round(
            $this->price_with_discount_te * ($this->tax_rate / 100),
            _TB_PRICE_DATABASE_PRECISION_
        );
        $this->price_ti = $this->price_with_discount_te + $this->tax_value;

        // defines default values for order discount fields
        $this->tax_value_with_order_discount = $this->tax_value;
        $this->price_with_order_discount_te = $this->price_with_discount_te;
    }

    /**
     * Applies a global order discount rate, for the current product (i.e detail)
     * Calls ObjectModel::update()
     *
     * @param float|int $discountRate The discount rate in percent (Ex. 5 for 5 percents)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function applyGlobalDiscount($discountRate)
    {
        if ($discountRate != null && is_numeric($discountRate) && (float) $discountRate > 0) {
            // calculates new price, with global order discount, tax ecluded
            $this->price_with_order_discount_te = round(
                $this->price_with_discount_te
                - $this->price_with_discount_te * $discountRate / 100,
                _TB_PRICE_DATABASE_PRECISION_
            );

            // calculates new tax value, with global order discount
            $this->tax_value_with_order_discount = round(
                $this->price_with_order_discount_te * $this->tax_rate / 100,
                _TB_PRICE_DATABASE_PRECISION_
            );

            parent::update();
        }
    }

    /**
     * @see ObjectModel::hydrate()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function hydrate(array $data, $idLang = null)
    {
        parent::hydrate($data, $idLang);

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this)
                && $this->def['fields'][$key]['validate'] === 'isPrice') {
                $this->$key = round($value, _TB_PRICE_DATABASE_PRECISION_);
            }
        }
    }
}
