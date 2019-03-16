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
 * Class OrderDetailCore
 *
 * @since 1.0.0
 */
class OrderDetailCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
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
            'product_price'                 => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',       'required' => true],
            'reduction_percent'             => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                          ],
            'reduction_amount'              => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'reduction_amount_tax_incl'     => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'reduction_amount_tax_excl'     => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
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
            'ecotax'                        => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'ecotax_tax_rate'               => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                          ],
            'discount_quantity_applied'     => ['type' => self::TYPE_INT,    'validate' => 'isInt'                            ],
            'download_hash'                 => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'                    ],
            'download_nb'                   => ['type' => self::TYPE_INT,    'validate' => 'isInt'                            ],
            'download_deadline'             => ['type' => self::TYPE_DATE,   'validate' => 'isDateFormat'                     ],
            'unit_price_tax_incl'           => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'unit_price_tax_excl'           => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'total_price_tax_incl'          => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'total_price_tax_excl'          => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'total_shipping_price_tax_excl' => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'total_shipping_price_tax_incl' => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'purchase_supplier_price'       => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'original_product_price'        => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'original_wholesale_price'      => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
        ],
    ];
    /** @var int $id_order_detail */
    public $id_order_detail;
    /** @var int $id_order */
    public $id_order;
    /** @var int $id_order_invoice */
    public $id_order_invoice;
    /** @var int $product_id */
    public $product_id;
    /** @var int $id_shop */
    public $id_shop;
    /** @var int $product_attribute_id */
    public $product_attribute_id;
    /** @var string $product_name */
    public $product_name;
    /** @var int $product_quantity */
    public $product_quantity;
    /** @var int $product_quantity_in_stock */
    public $product_quantity_in_stock;
    /** @var int $product_quantity_return */
    public $product_quantity_return;
    /** @var int $product_quantity_refunded */
    public $product_quantity_refunded;
    /** @var int $product_quantity_reinjected */
    public $product_quantity_reinjected;
    /** @var float $product_price */
    public $product_price;
    /** @var float $original_product_price */
    public $original_product_price;
    /** @var float $unit_price_tax_incl */
    public $unit_price_tax_incl;
    /** @var float $unit_price_tax_excl */
    public $unit_price_tax_excl;
    /** @var float $total_price_tax_incl */
    public $total_price_tax_incl;
    /** @var float $total_price_tax_excl */
    public $total_price_tax_excl;
    /** @var float $reduction_percent */
    public $reduction_percent;
    /** @var float $reduction_amount */
    public $reduction_amount;
    /** @var float $reduction_amount_tax_excl */
    public $reduction_amount_tax_excl;
    /** @var float $reduction_amount_tax_incl */
    public $reduction_amount_tax_incl;
    /** @var float $group_reduction */
    public $group_reduction;
    /** @var float $product_quantity_discount */
    public $product_quantity_discount;
    /** @var string $product_ean13 */
    public $product_ean13;
    /** @var string $product_upc */
    public $product_upc;
    /** @var string $product_reference */
    public $product_reference;
    /** @var string $product_supplier_reference */
    public $product_supplier_reference;
    /** @var float $product_weight */
    public $product_weight;
    /** @var float $ecotax */
    public $ecotax;
    /** @var float $ecotax_tax_rate */
    public $ecotax_tax_rate;
    /** @var int $discount_quantity_applied */
    public $discount_quantity_applied;
    /** @var string $download_hash */
    public $download_hash;
    /** @var int $download_nb */
    public $download_nb;
    /** @var datetime $download_deadline */
    public $download_deadline;
    /** @var string $tax_name * */
    public $tax_name;
    /** @var float $tax_rate * */
    public $tax_rate;
    /** @var float $tax_computation_method * */
    public $tax_computation_method;
    /** @var int $id_tax_rules_group Id tax rules group */
    public $id_tax_rules_group;
    /** @var int $id_warehouse Id warehouse */
    public $id_warehouse;
    /** @var float $total_shipping_price_tax_excl additional shipping price tax excl */
    public $total_shipping_price_tax_excl;
    /** @var float $total_shipping_price_tax_incl additional shipping price tax incl */
    public $total_shipping_price_tax_incl;
    /** @var float $purchase_supplier_price */
    public $purchase_supplier_price;
    /** @var float $original_wholesale_price */
    public $original_wholesale_price;
    /** @var bool $outOfStock */
    protected $outOfStock = false;
    /** @var null|TaxCalculator $tax_calculator */
    protected $tax_calculator = null;
    /** @var null|Address $vat_address */
    protected $vat_address = null;
    /** @var null|Address $specificPrice */
    protected $specificPrice = null;
    /** @var null|Customer $customer */
    protected $customer = null;
    /** @var null|Context $context */
    protected $context = null;

    // @codingStandardsIgnoreEnd

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
                'fields'   => ['id' => []],
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
        $idShop = null;
        if ($this->context != null && isset($this->context->shop)) {
            $idShop = $this->context->shop->id;
        }
        parent::__construct($id, $idLang, $idShop);

        if ($context == null) {
            $context = Context::getContext();
        }
        $this->context = $context->cloneContext();
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
        if (!$res = parent::delete()) {
            return false;
        }

        Db::getInstance()->delete('order_detail_tax', 'id_order_detail='.(int) $this->id);

        return $res;
    }

    /**
     * @param string $hash
     *
     * @return array|bool|null|object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getDownloadFromHash($hash)
    {
        if ($hash == '') {
            return false;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('order_detail', 'od')
                ->leftJoin('product_download', 'pd', 'od.`product_id` = pd.`id_product`')
                ->where('od.`download_hash` = \''.pSQL($hash).'\'')
                ->where('pd.`active` = 1')
        );
    }

    /**
     * @param int $idOrderDetail
     * @param int $increment
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function incrementDownload($idOrderDetail, $increment = 1)
    {
        return Db::getInstance()->update(
            'order_detail',
            [
                'download_nb' => ['type' => 'sql', 'value' => '`download_nb` + '.(int) $increment],
            ],
            '`id_order_detail` = '.(int) $idOrderDetail
        );
    }

    /**
     * Returns the tax calculator associated to this order detail.
     *
     * @return TaxCalculator
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTaxCalculator()
    {
        return static::getTaxCalculatorStatic($this->id);
    }

    /**
     * Return the tax calculator associated to this order_detail
     *
     * @param int $idOrderDetail
     *
     * @return TaxCalculator
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTaxCalculatorStatic($idOrderDetail)
    {
        $computationMethod = 1;
        $taxes = [];
        if ($results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.*, d.`tax_computation_method`')
                ->from('order_detail_tax', 't')
                ->leftJoin('order_detail', 'd', 'd.`id_order_detail` = t.`id_order_detail`')
                ->where('d.`id_order_detail` = '.(int) $idOrderDetail)
        )) {
            foreach ($results as $result) {
                $taxes[] = new Tax((int) $result['id_tax']);
                $computationMethod = $result['tax_computation_method'];
            }

        }

        return new TaxCalculator($taxes, $computationMethod);
    }

    /**
     * Save the tax calculator
     *
     * @param Order $order
     * @param bool  $replace
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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

        foreach ($order->getCartRules() as $cartRule) {
            if ($cartRule['free_shipping']) {
                $shippingTaxAmount = $order->total_shipping_tax_excl;
                break;
            }
        }

        $ratio = $this->unit_price_tax_excl / $order->total_products;
        $orderReductionAmount = round(
            ($order->total_discounts_tax_excl - $shippingTaxAmount) * $ratio,
            _TB_PRICE_DATABASE_PRECISION_
        );
        $discountedPriceTaxExcl = $this->unit_price_tax_excl - $orderReductionAmount;

        $values = [];
        foreach ($this->tax_calculator->getTaxesAmount($discountedPriceTaxExcl) as $idTax => $amount) {
            $totalAmount = $amount * (int) $this->product_quantity;

            $values[] = [
                static::$definition['primary'] => (int) $this->id,
                Tax::$definition['primary']    => (int) $idTax,
                'unit_amount'                  => $amount,
                'total_amount'                 => $totalAmount,
            ];
        }

        if ($replace) {
            Db::getInstance()->delete('order_detail_tax', '`id_order_detail` = '.(int) $this->id);
        }

        return Db::getInstance()->insert('order_detail_tax', $values);
    }

    /**
     * @param Order $order
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getList($idOrder)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('order_detail')
                ->where('`id_order` = '.(int) $idOrder)
        );
    }

    /**
     * @return mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTaxList()
    {
        return static::getTaxListStatic($this->id);
    }

    /**
     * @param int $idOrderDetail
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTaxListStatic($idOrderDetail)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('order_detail_tax')
                ->where('`'.bqSQL(static::$definition['primary']).'` = '.(int) $idOrderDetail)
        );
    }

    /**
     * Create a list of order detail for a specified id_order using cart
     *
     * @param object|Order $order
     * @param Cart|object  $cart
     * @param int          $idOrderState
     * @param array[]      $productList
     * @param int          $idOrderInvoice
     * @param bool         $useTaxes set to false if you don't want to use taxes
     * @param int          $idWarehouse
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since    1.0.0
     * @version  1.0.0 Initial version
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
     * @return bool
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
     * @param array $product
     *
     * @return void
     *
     * @throws Adapter_Exception
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setShippingCost(Order $order, $product)
    {
        $taxRate = 0;

        $carrier = OrderInvoice::getCarrier((int) $this->id_order_invoice);
        if (isset($carrier) && Validate::isLoadedObject($carrier)) {
            $taxRate = $carrier->getTaxesRate(new Address((int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
        }

        $this->total_shipping_price_tax_excl = round(
            $product['additional_shipping_cost'],
            _TB_PRICE_DATABASE_PRECISION_
        );
        $this->total_shipping_price_tax_incl = round(
            $this->total_shipping_price_tax_excl * (1 + $taxRate / 100),
            _TB_PRICE_DATABASE_PRECISION_
        );
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsTaxes()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('id_tax as id')
                ->from('order_detail_tax', 'tax')
                ->leftJoin('order_detail', 'od', 'tax.`id_order_detail` = od.`id_order_detail`')
                ->where('od.`id_order_detail` = '.(int) $this->id_order_detail)
        );
    }

    /**
     * @param int $idProduct
     * @param int $idLang
     * @param int $limit
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCrossSells($idProduct, $idLang, $limit = 12)
    {
        if (!$idProduct || !$idLang) {
            return [];
        }

        $front = true;
        if (!in_array(Context::getContext()->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        $orders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('o.`id_order`')
                ->from('orders', 'o')
                ->leftJoin('order_detail', 'od', 'od.`id_order` = o.`id_order`')
                ->where('o.`valid` = 1')
                ->where('od.`product_id` = '.(int) $idProduct)
        );

        if (count($orders)) {
            $list = '';
            foreach ($orders as $order) {
                $list .= (int) $order['id_order'].',';
            }
            $list = rtrim($list, ',');

            $orderProducts = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('DISTINCT od.`product_id`, p.`id_product`, pl.`name`, pl.`link_rewrite`, p.`reference`, i.`id_image`, product_shop.`show_price`')
                    ->select('cl.`link_rewrite` AS `category`, p.`ean13`, p.`out_of_stock`, p.`id_category_default`')
                    ->select(Combination::isFeatureActive() ? 'IFNULL(`product_attribute_shop`.`id_product_attribute`, 0) id_product_attribute' : '')
                    ->from('order_detail', 'od')
                    ->leftJoin('product', 'p', 'p.`id_product` = od.`product_id`')
                    ->join(Shop::addSqlAssociation('product', 'p'))
                    ->join((Combination::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) Context::getContext()->shop->id.')' : ''))
                    ->leftJoin('product_lang', 'pl', 'pl.`id_product` = od.`product_id` AND pl.`id_lang` = '.(int) $idLang.' '.Shop::addSqlRestrictionOnLang('pl'))
                    ->leftJoin('category_lang', 'cl', 'cl.`id_category` = product_shop.`id_category_default` AND cl.`id_lang` = '.(int) $idLang.' '.Shop::addSqlRestrictionOnLang('cl'))
                    ->leftJoin('image', 'i', 'i.`id_product` = od.`product_id` '.Shop::addSqlAssociation('image', 'i', true, 'image_shop.cover=1'))
                    ->where('od.`id_order` IN ('.$list.')')
                    ->where('od.`product_id` != '.(int) $idProduct)
                    ->where($front ? '`product_shop`.`visibility` IN ("both", "catalog")' : '')
                    ->orderBy('RAND()')
                    ->limit((int) $limit),
                true,
                false
            );

            $taxCalc = Product::getTaxCalculationMethod();
            if (is_array($orderProducts)) {
                foreach ($orderProducts as &$orderProduct) {
                    $orderProduct['image'] = Context::getContext()->link->getImageLink(
                        $orderProduct['link_rewrite'],
                        (int) $orderProduct['product_id'].'-'.(int) $orderProduct['id_image'],
                        ImageType::getFormatedName('medium')
                    );
                    $orderProduct['link'] = Context::getContext()->link->getProductLink(
                        (int) $orderProduct['product_id'],
                        $orderProduct['link_rewrite'],
                        $orderProduct['category'],
                        $orderProduct['ean13']
                    );
                    if ($taxCalc == 0 || $taxCalc == 2) {
                        $orderProduct['displayed_price'] = Product::getPriceStatic((int) $orderProduct['product_id'], true, null);
                    } elseif ($taxCalc == 1) {
                        $orderProduct['displayed_price'] = Product::getPriceStatic((int) $orderProduct['product_id'], false, null);
                    }
                }

                return Product::getProductsProperties($idLang, $orderProducts);
            }
        }

        return [];
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
            if ($combination && $combination->wholesale_price != 0.0) {
                $wholesalePrice = $combination->wholesale_price;
            }
        }

        return round($wholesalePrice, _TB_PRICE_DATABASE_PRECISION_);
    }

    /**
     * @param int $idShop
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setContext($idShop)
    {
        if ($this->context->shop->id != $idShop) {
            $this->context->shop = new Shop((int) $idShop);
        }
    }

    /**
     * @param $product
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
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
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function checkProductStock($product, $idOrderState)
    {
        if ($idOrderState != Configuration::get('PS_OS_CANCELED') && $idOrderState != Configuration::get('PS_OS_ERROR')) {
            $updateQuantity = StockAvailable::updateQuantity($product['id_product'], $product['id_product_attribute'], -(int) $product['cart_quantity']);

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
     * @param Order $order
     * @param array $product
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function setProductTax(Order $order, $product)
    {
        $this->ecotax = Tools::convertPrice(floatval($product['ecotax']), intval($order->id_currency));

        // Exclude VAT
        if (!Tax::excludeTaxeOption()) {
            $this->setContext((int) $product['id_shop']);
            $this->id_tax_rules_group = (int) Product::getIdTaxRulesGroupByIdProduct((int) $product['id_product'], $this->context);

            $taxManager = TaxManagerFactory::getManager($this->vat_address, $this->id_tax_rules_group);
            $this->tax_calculator = $taxManager->getTaxCalculator();
            $this->tax_computation_method = (int) $this->tax_calculator->computation_method;
        }

        $this->tax_name = $product['tax_name'];
        $this->tax_rate = $product['rate'];

        $this->ecotax_tax_rate = 0;
        if (!empty($product['ecotax'])) {
            $this->ecotax_tax_rate = Tax::getProductEcotaxRate($order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        }
    }

    /**
     * Set specific price of the product
     *
     * @param Order      $order
     * @param array|null $product
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
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
                    $idTaxRules = (int) Product::getIdTaxRulesGroupByIdProduct((int) $this->specificPrice['id_product'], $this->context);
                    $taxManager = TaxManagerFactory::getManager($this->vat_address, $idTaxRules);
                    $this->tax_calculator = $taxManager->getTaxCalculator();

                    if ($this->specificPrice['reduction_tax']) {
                        $this->reduction_amount_tax_incl = $this->reduction_amount;
                        $this->reduction_amount_tax_excl = $this->tax_calculator->removeTaxes($this->reduction_amount);
                    } else {
                        $this->reduction_amount_tax_incl = $this->tax_calculator->addTaxes($this->reduction_amount);
                        $this->reduction_amount_tax_excl = $this->reduction_amount;
                    }
                    break;
            }
        }
    }

    /**
     * Set detailed product price to the order detail
     *
     * @param Order $order
     * @param Cart  $cart
     * @param array $product
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setDetailProductPrice(Order $order, Cart $cart, $product)
    {
        $this->setContext((int) $product['id_shop']);
        Product::getPriceStatic(
            (int) $product['id_product'],
            true,
            (int) $product['id_product_attribute'],
            _TB_PRICE_DATABASE_PRECISION_,
            null,
            false,
            true,
            $product['cart_quantity'],
            false,
            (int) $order->id_customer,
            (int) $order->id_cart,
            (int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')},
            $specificPrice,
            true,
            true,
            $this->context
        );
        $this->specificPrice = $specificPrice;
        $this->original_product_price = Product::getPriceStatic(
            $product['id_product'],
            false,
            (int) $product['id_product_attribute'],
            _TB_PRICE_DATABASE_PRECISION_,
            null,
            false,
            false,
            1,
            false,
            null,
            null,
            null,
            $null,
            true,
            true,
            $this->context
        );
        $this->product_price = $this->original_product_price;
        $this->unit_price_tax_incl = round(
            $product['price_wt'],
            _TB_PRICE_DATABASE_PRECISION_
        );
        $this->unit_price_tax_excl = round(
            $product['price'],
            _TB_PRICE_DATABASE_PRECISION_
        );
        $this->total_price_tax_incl = round(
            $product['total_wt'],
            _TB_PRICE_DATABASE_PRECISION_
        );
        $this->total_price_tax_excl = round(
            $product['total'],
            _TB_PRICE_DATABASE_PRECISION_
        );

        $this->purchase_supplier_price = round(
            $product['wholesale_price'],
            _TB_PRICE_DATABASE_PRECISION_
        );
        if ($product['id_supplier']) {
            $supplierPrice = ProductSupplier::getProductPrice(
                (int) $product['id_supplier'],
                $product['id_product'],
                $product['id_product_attribute'],
                true
            );
            if ($supplierPrice !== false) {
                $this->purchase_supplier_price = $supplierPrice;
            }
        }

        $this->setSpecificPrice($order, $product);

        $this->group_reduction = (float) Group::getReduction((int) $order->id_customer);

        $shopId = $this->context->shop->id;

        $quantityDiscount = SpecificPrice::getQuantityDiscount(
            (int) $product['id_product'],
            $shopId,
            (int) $cart->id_currency,
            (int) $this->vat_address->id_country,
            (int) $this->customer->id_default_group,
            (int) $product['cart_quantity'],
            false,
            null
        );
        $unitPrice = Product::getPriceStatic(
            (int) $product['id_product'],
            true,
            ($product['id_product_attribute'] ? intval($product['id_product_attribute']) : null),
            _TB_PRICE_DATABASE_PRECISION_,
            null,
            false,
            true,
            1,
            false,
            (int) $order->id_customer,
            null,
            (int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')},
            $null,
            true,
            true,
            $this->context
        );
        $this->product_quantity_discount = 0.0;
        if ($quantityDiscount) {
            $this->product_quantity_discount = $unitPrice;
            if (isset($this->tax_calculator)) {
                $this->product_quantity_discount -= $this->tax_calculator->addTaxes($quantityDiscount['price']);
            }
        }

        $this->discount_quantity_applied = (($this->specificPrice && $this->specificPrice['from_quantity'] > 1) ? 1 : 0);
    }

    /**
     * Create an order detail liable to an id_order
     *
     * @param Order $order
     * @param Cart  $cart
     * @param array $product
     * @param int   $idOrderState
     * @param int   $idOrderInvoice
     * @param bool  $useTaxes set to false if you don't want to use taxes
     * @param int   $idWarehouse
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
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
        $this->product_name = $product['name'].((isset($product['attributes']) && $product['attributes'] != null) ? ' - '.$product['attributes'] : '');

        $this->product_quantity = (int) $product['cart_quantity'];
        $this->product_ean13 = empty($product['ean13']) ? null : pSQL($product['ean13']);
        $this->product_upc = empty($product['upc']) ? null : pSQL($product['upc']);
        $this->product_reference = empty($product['reference']) ? null : pSQL($product['reference']);
        $this->product_supplier_reference = empty($product['supplier_reference']) ? null : pSQL($product['supplier_reference']);
        $this->product_weight = $product['id_product_attribute'] ? (float) $product['weight_attribute'] : (float) $product['weight'];
        $this->id_warehouse = $idWarehouse;

        $productQuantity = (int) Product::getQuantity($this->product_id, $this->product_attribute_id);
        $this->product_quantity_in_stock = ($productQuantity - (int) $product['cart_quantity'] < 0) ?
            $productQuantity : (int) $product['cart_quantity'];

        $this->setVirtualProductInformation($product);
        $this->checkProductStock($product, $idOrderState);

        if ($useTaxes) {
            $this->setProductTax($order, $product);
        }
        $this->setShippingCost($order, $product);
        $this->setDetailProductPrice($order, $cart, $product);
        $this->original_wholesale_price = $this->getWholeSalePrice();

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
}
