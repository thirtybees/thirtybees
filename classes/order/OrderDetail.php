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
 * Class OrderDetailCore
 *
 * @since 1.0.0
 */
class OrderDetailCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int */
    public $id_order_detail;

    /** @var int */
    public $id_order;

    /** @var int */
    public $id_order_invoice;

    /** @var int */
    public $product_id;

    /** @var int */
    public $id_shop;

    /** @var int */
    public $product_attribute_id;

    /** @var string */
    public $product_name;

    /** @var int */
    public $product_quantity;

    /** @var int */
    public $product_quantity_in_stock;

    /** @var int */
    public $product_quantity_return;

    /** @var int */
    public $product_quantity_refunded;

    /** @var int */
    public $product_quantity_reinjected;

    /** @var float */
    public $product_price;

    /** @var float */
    public $original_product_price;

    /** @var float */
    public $unit_price_tax_incl;

    /** @var float */
    public $unit_price_tax_excl;

    /** @var float */
    public $total_price_tax_incl;

    /** @var float */
    public $total_price_tax_excl;

    /** @var float */
    public $reduction_percent;

    /** @var float */
    public $reduction_amount;

    /** @var float */
    public $reduction_amount_tax_excl;

    /** @var float */
    public $reduction_amount_tax_incl;

    /** @var float */
    public $group_reduction;

    /** @var float */
    public $product_quantity_discount;

    /** @var string */
    public $product_ean13;

    /** @var string */
    public $product_upc;

    /** @var string */
    public $product_reference;

    /** @var string */
    public $product_supplier_reference;

    /** @var float */
    public $product_weight;

    /** @var float */
    public $ecotax;

    /** @var float */
    public $ecotax_tax_rate;

    /** @var int */
    public $discount_quantity_applied;

    /** @var string */
    public $download_hash;

    /** @var int */
    public $download_nb;

    /** @var datetime */
    public $download_deadline;

    /** @var string $tax_name * */
    public $tax_name;

    /** @var float $tax_rate * */
    public $tax_rate;

    /** @var float $tax_computation_method * */
    public $tax_computation_method;

    /** @var int Id tax rules group */
    public $id_tax_rules_group;

    /** @var int Id warehouse */
    public $id_warehouse;

    /** @var float additional shipping price tax excl */
    public $total_shipping_price_tax_excl;

    /** @var float additional shipping price tax incl */
    public $total_shipping_price_tax_incl;

    /** @var float */
    public $purchase_supplier_price;

    /** @var float */
    public $original_wholesale_price;

    /** @var bool */
    protected $outOfStock = false;

    /** @var TaxCalculator object */
    protected $tax_calculator = null;

    /** @var Address object */
    protected $vat_address = null;

    /** @var Address object */
    protected $specificPrice = null;

    /** @var Customer object */
    protected $customer = null;

    /** @var Context object */
    protected $context = null;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'order_detail',
        'primary' => 'id_order_detail',
        'fields'  => [
            'id_order'                      => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'id_order_invoice'              => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                     ],
            'id_warehouse'                  => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'id_shop'                       => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'product_id'                    => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                     ],
            'product_attribute_id'          => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                     ],
            'product_name'                  => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'product_quantity'              => ['type' => self::TYPE_INT,    'validate' => 'isInt',         'required' => true],
            'product_quantity_in_stock'     => ['type' => self::TYPE_INT,    'validate' => 'isInt'                            ],
            'product_quantity_return'       => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                    ],
            'product_quantity_refunded'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                    ],
            'product_quantity_reinjected'   => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                    ],
            'product_price'                 => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice',       'required' => true],
            'reduction_percent'             => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                          ],
            'reduction_amount'              => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice'                          ],
            'reduction_amount_tax_incl'     => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice'                          ],
            'reduction_amount_tax_excl'     => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice'                          ],
            'group_reduction'               => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                          ],
            'product_quantity_discount'     => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                          ],
            'product_ean13'                 => ['type' => self::TYPE_STRING, 'validate' => 'isEan13'                          ],
            'product_upc'                   => ['type' => self::TYPE_STRING, 'validate' => 'isUpc'                            ],
            'product_reference'             => ['type' => self::TYPE_STRING, 'validate' => 'isReference'                      ],
            'product_supplier_reference'    => ['type' => self::TYPE_STRING, 'validate' => 'isReference'                      ],
            'product_weight'                => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                          ],
            'tax_name'                      => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'                    ],
            'tax_rate'                      => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                          ],
            'tax_computation_method'        => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                     ],
            'id_tax_rules_group'            => ['type' => self::TYPE_INT,    'validate' => 'isInt'                            ],
            'ecotax'                        => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                          ],
            'ecotax_tax_rate'               => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                          ],
            'discount_quantity_applied'     => ['type' => self::TYPE_INT,    'validate' => 'isInt'                            ],
            'download_hash'                 => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'                    ],
            'download_nb'                   => ['type' => self::TYPE_INT,    'validate' => 'isInt'                            ],
            'download_deadline'             => ['type' => self::TYPE_DATE,   'validate' => 'isDateFormat'                     ],
            'unit_price_tax_incl'           => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice'                          ],
            'unit_price_tax_excl'           => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice'                          ],
            'total_price_tax_incl'          => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice'                          ],
            'total_price_tax_excl'          => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice'                          ],
            'total_shipping_price_tax_excl' => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice'                          ],
            'total_shipping_price_tax_incl' => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice'                          ],
            'purchase_supplier_price'       => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice'                          ],
            'original_product_price'        => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice'                          ],
            'original_wholesale_price'      => ['type' => self::TYPE_FLOAT,  'validate' => 'isPrice'                          ],
        ],
    ];

    protected $webserviceParameters = [
        'fields'        => [
            'id_order'                    => ['xlink_resource' => 'orders'],
            'product_id'                  => ['xlink_resource' => 'products'],
            'product_attribute_id'        => ['xlink_resource' => 'combinations'],
            'product_quantity_reinjected' => [],
            'group_reduction'             => [],
            'discount_quantity_applied'   => [],
            'download_hash'               => [],
            'download_deadline'           => [],
        ],
        'hidden_fields' => ['tax_rate', 'tax_name'],
        'associations'  => [
            'taxes' => [
                'resource' => 'tax',
                'getter'   => 'getWsTaxes',
                'setter'   => false,
                'fields'   => ['id' => [],],
            ],
        ],
    ];

    /**
     * OrderDetailCore constructor.
     *
     * @param null $id
     * @param null $idLang
     * @param null $context
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($id = null, $idLang = null, $context = null)
    {
        $this->context = $context;
        $id_shop = null;
        if ($this->context != null && isset($this->context->shop)) {
            $id_shop = $this->context->shop->id;
        }
        parent::__construct($id, $idLang, $id_shop);

        if ($context == null) {
            $context = Context::getContext();
        }
        $this->context = $context->cloneContext();
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if (!$res = parent::delete()) {
            return false;
        }

        Db::getInstance()->delete('order_detail_tax', 'id_order_detail='.(int) $this->id);

        return $res;
    }

    /**
     * @param $id_shop
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setContext($id_shop)
    {
        if ($this->context->shop->id != $id_shop) {
            $this->context->shop = new Shop((int) $id_shop);
        }
    }

    /**
     * @param $hash
     *
     * @return array|bool|null|object
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getDownloadFromHash($hash)
    {
        if ($hash == '') {
            return false;
        }
        $sql = 'SELECT *
		FROM `'._DB_PREFIX_.'order_detail` od
		LEFT JOIN `'._DB_PREFIX_.'product_download` pd ON (od.`product_id`=pd.`id_product`)
		WHERE od.`download_hash` = \''.pSQL(strval($hash)).'\'
		AND pd.`active` = 1';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
    }

    /**
     * @param     $idOrderDetail
     * @param int $increment
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function incrementDownload($idOrderDetail, $increment = 1)
    {
        $sql = 'UPDATE `'._DB_PREFIX_.'order_detail`
			SET `download_nb` = `download_nb` + '.(int) $increment.'
			WHERE `id_order_detail`= '.(int) $idOrderDetail.'
			LIMIT 1';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Returns the tax calculator associated to this order detail.
     *
     * @return TaxCalculator
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTaxCalculator()
    {
        return OrderDetail::getTaxCalculatorStatic($this->id);
    }

    /**
     * Return the tax calculator associated to this order_detail
     *
     * @param int $idOrderDetail
     *
     * @return TaxCalculator
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTaxCalculatorStatic($idOrderDetail)
    {
        $sql = 'SELECT t.*, d.`tax_computation_method`
				FROM `'._DB_PREFIX_.'order_detail_tax` t
				LEFT JOIN `'._DB_PREFIX_.'order_detail` d ON (d.`id_order_detail` = t.`id_order_detail`)
				WHERE d.`id_order_detail` = '.(int) $idOrderDetail;

        $computationMethod = 1;
        $taxes = [];
        if ($results = Db::getInstance()->executeS($sql)) {
            foreach ($results as $result) {
                $taxes[] = new Tax((int) $result['id_tax']);
            }

            $computationMethod = $result['tax_computation_method'];
        }

        return new TaxCalculator($taxes, $computationMethod);
    }

    /**
     * Save the tax calculator
     *
     * @deprecated 2.0.0
     *
     *
     * @return bool
     */
    public function saveTaxCalculator(Order $order, $replace = false)
    {
        // Nothing to save
        if ($this->tax_calculator == null) {
            return true;
        }

        if (!($this->tax_calculator instanceof TaxCalculator)) {
            return false;
        }

        if (count($this->tax_calculator->taxes) == 0) {
            return true;
        }

        if ($order->total_products <= 0) {
            return true;
        }

        $shippingTaxAmount = 0;

        foreach ($order->getCartRules() as $cart_rule) {
            if ($cart_rule['free_shipping']) {
                $shippingTaxAmount = $order->total_shipping_tax_excl;
                break;
            }
        }

        $ratio = $this->unit_price_tax_excl / $order->total_products;
        $orderReductionAmount = ($order->total_discounts_tax_excl - $shippingTaxAmount) * $ratio;
        $discountedPriceTaxExcl = $this->unit_price_tax_excl - $orderReductionAmount;

        $values = '';
        foreach ($this->tax_calculator->getTaxesAmount($discountedPriceTaxExcl) as $id_tax => $amount) {
            switch (Configuration::get('PS_ROUND_TYPE')) {
                case Order::ROUND_ITEM:
                    $unitAmount = (float) Tools::ps_round($amount, _PS_PRICE_COMPUTE_PRECISION_);
                    $totalAmount = $unitAmount * $this->product_quantity;
                    break;
                case Order::ROUND_LINE:
                    $unitAmount = $amount;
                    $totalAmount = Tools::ps_round($unitAmount * $this->product_quantity, _PS_PRICE_COMPUTE_PRECISION_);
                    break;
                case Order::ROUND_TOTAL:
                    $unitAmount = $amount;
                    $totalAmount = $unitAmount * $this->product_quantity;
                    break;
            }

            $values .= '('.(int) $this->id.','.(int) $id_tax.','.(float) $unitAmount.','.(float) $totalAmount.'),';
        }

        if ($replace) {
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'order_detail_tax` WHERE id_order_detail='.(int) $this->id);
        }

        $values = rtrim($values, ',');
        $sql = 'INSERT INTO `'._DB_PREFIX_.'order_detail_tax` (id_order_detail, id_tax, unit_amount, total_amount)
				VALUES '.$values;

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param Order $order
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function updateTaxAmount(Order $order)
    {
        $this->setContext((int) $this->id_shop);
        $address = new Address((int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $taxManager = TaxManagerFactory::getManager($address, (int) Product::getIdTaxRulesGroupByIdProduct((int) $this->product_id, $this->context));
        $this->tax_calculator = $taxManager->getTaxCalculator();

        return $this->saveTaxCalculator($order, true);
    }

    /**
     * Get a detailed order list of an id_order
     *
     * @param int $idOrder
     *
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getList($idOrder)
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'order_detail` WHERE `id_order` = '.(int) $idOrder);
    }

    /**
     * @return mixed
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTaxList()
    {
        return static::getTaxList($this->id);
    }

    /**
     * @param $idOrderDetail
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTaxListStatic($idOrderDetail)
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'order_detail_tax`
					WHERE `id_order_detail` = '.(int) $idOrderDetail;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * @param $product
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setVirtualProductInformation($product)
    {
        // Add some informations for virtual products
        $this->download_deadline = '0000-00-00 00:00:00';
        $this->download_hash = null;

        if ($idProductDownload = ProductDownload::getIdFromIdProduct((int) $product['id_product'])) {
            $productDownload = new ProductDownload((int) $idProductDownload);
            $this->download_deadline = $productDownload->getDeadLine();
            $this->download_hash = $productDownload->getHash();

            unset($productDownload);
        }
    }

    /**
     * Check the order status
     *
     * @param array $product
     * @param int   $idOrderState
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function checkProductStock($product, $idOrderState)
    {
        if ($idOrderState != Configuration::get('PS_OS_CANCELED') && $idOrderState != Configuration::get('PS_OS_ERROR')) {
            $updateQuantity = true;
            if (!StockAvailable::dependsOnStock($product['id_product'])) {
                $updateQuantity = StockAvailable::updateQuantity($product['id_product'], $product['id_product_attribute'], -(int) $product['cart_quantity']);
            }

            if ($updateQuantity) {
                $product['stock_quantity'] -= $product['cart_quantity'];
            }

            if ($product['stock_quantity'] < 0 && Configuration::get('PS_STOCK_MANAGEMENT')) {
                $this->outOfStock = true;
            }
            Product::updateDefaultAttribute($product['id_product']);
        }
    }

    /**
     * Apply tax to the product
     *
     * @param object $order
     * @param array  $product
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setProductTax(Order $order, $product)
    {
        $this->ecotax = Tools::convertPrice(floatval($product['ecotax']), intval($order->id_currency));

        // Exclude VAT
        if (!Tax::excludeTaxeOption()) {
            $this->setContext((int) $product['id_shop']);
            $this->id_tax_rules_group = (int) Product::getIdTaxRulesGroupByIdProduct((int) $product['id_product'], $this->context);

            $tax_manager = TaxManagerFactory::getManager($this->vat_address, $this->id_tax_rules_group);
            $this->tax_calculator = $tax_manager->getTaxCalculator();
            $this->tax_computation_method = (int) $this->tax_calculator->computation_method;
        }

        $this->ecotax_tax_rate = 0;
        if (!empty($product['ecotax'])) {
            $this->ecotax_tax_rate = Tax::getProductEcotaxRate($order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        }
    }

    /**
     * Set specific price of the product
     *
     * @param object $order
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setSpecificPrice(Order $order, $product = null)
    {
        $this->reduction_amount = 0.00;
        $this->reduction_percent = 0.00;
        $this->reduction_amount_tax_incl = 0.00;
        $this->reduction_amount_tax_excl = 0.00;

        if ($this->specificPrice) {
            switch ($this->specificPrice['reduction_type']) {
                case 'percentage':
                    $this->reduction_percent = (float) $this->specificPrice['reduction'] * 100;
                    break;

                case 'amount':
                    $price = Tools::convertPrice($this->specificPrice['reduction'], $order->id_currency);
                    $this->reduction_amount = !$this->specificPrice['id_currency'] ? (float) $price : (float) $this->specificPrice['reduction'];
                    if ($product !== null) {
                        $this->setContext((int) $product['id_shop']);
                    }
                    $id_tax_rules = (int) Product::getIdTaxRulesGroupByIdProduct((int) $this->specificPrice['id_product'], $this->context);
                    $tax_manager = TaxManagerFactory::getManager($this->vat_address, $id_tax_rules);
                    $this->tax_calculator = $tax_manager->getTaxCalculator();

                    if ($this->specificPrice['reduction_tax']) {
                        $this->reduction_amount_tax_incl = $this->reduction_amount;
                        $this->reduction_amount_tax_excl = Tools::ps_round($this->tax_calculator->removeTaxes($this->reduction_amount), _PS_PRICE_COMPUTE_PRECISION_);
                    } else {
                        $this->reduction_amount_tax_incl = Tools::ps_round($this->tax_calculator->addTaxes($this->reduction_amount), _PS_PRICE_COMPUTE_PRECISION_);
                        $this->reduction_amount_tax_excl = $this->reduction_amount;
                    }
                    break;
            }
        }
    }

    /**
     * Set detailed product price to the order detail
     *
     * @param object $order
     * @param object $cart
     * @param array  $product
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setDetailProductPrice(Order $order, Cart $cart, $product)
    {
        $this->setContext((int) $product['id_shop']);
        Product::getPriceStatic((int) $product['id_product'], true, (int) $product['id_product_attribute'], 6, null, false, true, $product['cart_quantity'], false, (int) $order->id_customer, (int) $order->id_cart, (int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}, $specific_price, true, true, $this->context);
        $this->specificPrice = $specific_price;
        $this->original_product_price = Product::getPriceStatic($product['id_product'], false, (int) $product['id_product_attribute'], 6, null, false, false, 1, false, null, null, null, $null, true, true, $this->context);
        $this->product_price = $this->original_product_price;
        $this->unit_price_tax_incl = (float) $product['price_wt'];
        $this->unit_price_tax_excl = (float) $product['price'];
        $this->total_price_tax_incl = (float) $product['total_wt'];
        $this->total_price_tax_excl = (float) $product['total'];

        $this->purchase_supplier_price = (float) $product['wholesale_price'];
        if ($product['id_supplier'] > 0 && ($supplier_price = ProductSupplier::getProductPrice((int) $product['id_supplier'], $product['id_product'], $product['id_product_attribute'], true)) > 0) {
            $this->purchase_supplier_price = (float) $supplier_price;
        }

        $this->setSpecificPrice($order, $product);

        $this->group_reduction = (float) Group::getReduction((int) $order->id_customer);

        $shop_id = $this->context->shop->id;

        $quantity_discount = SpecificPrice::getQuantityDiscount(
            (int) $product['id_product'], $shop_id,
            (int) $cart->id_currency, (int) $this->vat_address->id_country,
            (int) $this->customer->id_default_group, (int) $product['cart_quantity'], false, null, null, $null, true, true, $this->context
        );

        $unit_price = Product::getPriceStatic(
            (int) $product['id_product'], true,
            ($product['id_product_attribute'] ? intval($product['id_product_attribute']) : null),
            2, null, false, true, 1, false, (int) $order->id_customer, null, (int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}, $null, true, true, $this->context
        );
        $this->product_quantity_discount = 0.00;
        if ($quantity_discount) {
            $this->product_quantity_discount = $unit_price;
            if (Product::getTaxCalculationMethod((int) $order->id_customer) == PS_TAX_EXC) {
                $this->product_quantity_discount = Tools::ps_round($unit_price, 2);
            }

            if (isset($this->tax_calculator)) {
                $this->product_quantity_discount -= $this->tax_calculator->addTaxes($quantity_discount['price']);
            }
        }

        $this->discount_quantity_applied = (($this->specificPrice && $this->specificPrice['from_quantity'] > 1) ? 1 : 0);
    }

    /**
     * Create an order detail liable to an id_order
     *
     * @param object $order
     * @param object $cart
     * @param array  $product
     * @param int    $id_order_status
     * @param int    $idOrderInvoice
     * @param bool   $useTaxes set to false if you don't want to use taxes
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function create(Order $order, Cart $cart, $product, $idOrderState, $idOrderInvoice, $useTaxes = true, $idWarehouse = 0)
    {
        if ($useTaxes) {
            $this->tax_calculator = new TaxCalculator();
        }

        $this->id = null;

        $this->product_id = (int) $product['id_product'];
        $this->product_attribute_id = $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : 0;
        $this->product_name = $product['name'].
            ((isset($product['attributes']) && $product['attributes'] != null) ?
                ' - '.$product['attributes'] : '');

        $this->product_quantity = (int) $product['cart_quantity'];
        $this->product_ean13 = empty($product['ean13']) ? null : pSQL($product['ean13']);
        $this->product_upc = empty($product['upc']) ? null : pSQL($product['upc']);
        $this->product_reference = empty($product['reference']) ? null : pSQL($product['reference']);
        $this->product_supplier_reference = empty($product['supplier_reference']) ? null : pSQL($product['supplier_reference']);
        $this->product_weight = $product['id_product_attribute'] ? (float) $product['weight_attribute'] : (float) $product['weight'];
        $this->id_warehouse = $idWarehouse;

        $product_quantity = (int) Product::getQuantity($this->product_id, $this->product_attribute_id);
        $this->product_quantity_in_stock = ($product_quantity - (int) $product['cart_quantity'] < 0) ?
            $product_quantity : (int) $product['cart_quantity'];

        $this->setVirtualProductInformation($product);
        $this->checkProductStock($product, $idOrderState);

        if ($useTaxes) {
            $this->setProductTax($order, $product);
        }
        $this->setShippingCost($order, $product);
        $this->setDetailProductPrice($order, $cart, $product);

        // Set order invoice id
        $this->id_order_invoice = (int) $idOrderInvoice;

        // Set shop id
        $this->id_shop = (int) $product['id_shop'];

        // Add new entry to the table
        $this->save();

        if ($useTaxes) {
            $this->saveTaxCalculator($order);
        }
        unset($this->tax_calculator);
    }

    /**
     * Create a list of order detail for a specified id_order using cart
     *
     * @param object $order
     * @param object $cart
     * @param int    $id_order_status
     * @param int    $idOrderInvoice
     * @param bool   $useTaxes set to false if you don't want to use taxes
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function createList(Order $order, Cart $cart, $idOrderState, $productList, $idOrderInvoice = 0, $useTaxes = true, $idWarehouse = 0)
    {
        $this->vat_address = new Address((int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $this->customer = new Customer((int) $order->id_customer);

        $this->id_order = $order->id;
        $this->outOfStock = false;

        foreach ($productList as $product) {
            $this->create($order, $cart, $product, $idOrderState, $idOrderInvoice, $useTaxes, $idWarehouse);
        }

        unset($this->vat_address);
        unset($products);
        unset($this->customer);
    }

    /**
     * Get the state of the current stock product
     *
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getStockState()
    {
        return $this->outOfStock;
    }

    /**
     * Set the additional shipping information
     *
     * @param Order $order
     * @param       $product
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setShippingCost(Order $order, $product)
    {
        $tax_rate = 0;

        $carrier = OrderInvoice::getCarrier((int) $this->id_order_invoice);
        if (isset($carrier) && Validate::isLoadedObject($carrier)) {
            $tax_rate = $carrier->getTaxesRate(new Address((int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
        }

        $this->total_shipping_price_tax_excl = (float) $product['additional_shipping_cost'];
        $this->total_shipping_price_tax_incl = (float) ($this->total_shipping_price_tax_excl * (1 + ($tax_rate / 100)));
        $this->total_shipping_price_tax_incl = Tools::ps_round($this->total_shipping_price_tax_incl, 2);
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsTaxes()
    {
        $query = new DbQuery();
        $query->select('id_tax as id');
        $query->from('order_detail_tax', 'tax');
        $query->leftJoin('order_detail', 'od', 'tax.`id_order_detail` = od.`id_order_detail`');
        $query->where('od.`id_order_detail` = '.(int) $this->id_order_detail);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * @param     $id_product
     * @param     $id_lang
     * @param int $limit
     *
     * @return array|void
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCrossSells($id_product, $id_lang, $limit = 12)
    {
        if (!$id_product || !$id_lang) {
            return;
        }

        $front = true;
        if (!in_array(Context::getContext()->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        $orders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT o.id_order
		FROM '._DB_PREFIX_.'orders o
		LEFT JOIN '._DB_PREFIX_.'order_detail od ON (od.id_order = o.id_order)
		WHERE o.valid = 1 AND od.product_id = '.(int) $id_product
        );

        if (count($orders)) {
            $list = '';
            foreach ($orders as $order) {
                $list .= (int) $order['id_order'].',';
            }
            $list = rtrim($list, ',');

            $orderProducts = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
				SELECT DISTINCT od.product_id, p.id_product, pl.name, pl.link_rewrite, p.reference, i.id_image, product_shop.show_price,
				cl.link_rewrite category, p.ean13, p.out_of_stock, p.id_category_default '.(Combination::isFeatureActive() ? ', IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute' : '').'
				FROM '._DB_PREFIX_.'order_detail od
				LEFT JOIN '._DB_PREFIX_.'product p ON (p.id_product = od.product_id)
				'.Shop::addSqlAssociation('product', 'p').
                (Combination::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop
				ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) Context::getContext()->shop->id.')' : '').'
				LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (pl.id_product = od.product_id'.Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = product_shop.id_category_default'.Shop::addSqlRestrictionOnLang('cl').')
				LEFT JOIN '._DB_PREFIX_.'image i ON (i.id_product = od.product_id)
				'.Shop::addSqlAssociation('image', 'i', true, 'image_shop.cover=1').'
				WHERE od.id_order IN ('.$list.')
					AND pl.id_lang = '.(int) $id_lang.'
					AND cl.id_lang = '.(int) $id_lang.'
					AND od.product_id != '.(int) $id_product.'
					AND product_shop.active = 1'
                .($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
				ORDER BY RAND()
				LIMIT '.(int) $limit.'
			', true, false
            );

            $tax_calc = Product::getTaxCalculationMethod();
            if (is_array($orderProducts)) {
                foreach ($orderProducts as &$orderProduct) {
                    $orderProduct['image'] = Context::getContext()->link->getImageLink(
                        $orderProduct['link_rewrite'],
                        (int) $orderProduct['product_id'].'-'.(int) $orderProduct['id_image'], ImageType::getFormatedName('medium')
                    );
                    $orderProduct['link'] = Context::getContext()->link->getProductLink(
                        (int) $orderProduct['product_id'],
                        $orderProduct['link_rewrite'], $orderProduct['category'], $orderProduct['ean13']
                    );
                    if ($tax_calc == 0 || $tax_calc == 2) {
                        $orderProduct['displayed_price'] = Product::getPriceStatic((int) $orderProduct['product_id'], true, null);
                    } elseif ($tax_calc == 1) {
                        $orderProduct['displayed_price'] = Product::getPriceStatic((int) $orderProduct['product_id'], false, null);
                    }
                }

                return Product::getProductsProperties($id_lang, $orderProducts);
            }
        }
    }

    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autodate = true, $nullValues = false)
    {
        foreach ($this->def['fields'] as $field => $data) {
            if (!empty($data['required']) || !empty($data['lang'])) {
                continue;
            }
            if ($this->validateField($field, $this->$field) !== true) {
                $this->$field = '';
            }
        }

        $this->original_wholesale_price = $this->getWholeSalePrice();

        return parent::add($autodate = true, $nullValues = false);
    }

    /**
     * @return float
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWholeSalePrice()
    {
        $product = new Product($this->product_id);
        $wholesalePrice = $product->wholesale_price;

        if ($this->product_attribute_id) {
            $combination = new Combination((int) $this->product_attribute_id);
            if ($combination && $combination->wholesale_price != '0.000000') {
                $wholesalePrice = $combination->wholesale_price;
            }
        }

        return $wholesalePrice;
    }
}
