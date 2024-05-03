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
 * Class OrderSlipCore
 */
class OrderSlipCore extends ObjectModel
{
    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'order_slip',
        'primary' => 'id_order_slip',
        'fields'  => [
            'conversion_rate'         => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true, 'size' => 13, 'decimals' => 6, 'dbDefault' => '1.000000'],
            'id_customer'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'total_products_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbNullable' => true],
            'total_products_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbNullable' => true],
            'total_shipping_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbNullable' => true],
            'total_shipping_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbNullable' => true],
            'shipping_cost'           => ['type' => self::TYPE_INT, 'dbType' => 'tinyint(3) unsigned', 'dbDefault' => '0'],
            'amount'                  => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbNullable' => false],
            'shipping_cost_amount'    => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbNullable' => false],
            'partial'                 => ['type' => self::TYPE_INT, 'dbType' => 'tinyint(1)', 'dbNullable' => false],
            'order_slip_type'         => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 1, 'dbDefault' => '0'],
            'date_add'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
            'date_upd'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        ],
        'keys' => [
            'order_slip' => [
                'id_order'            => ['type' => ObjectModel::KEY, 'columns' => ['id_order']],
                'order_slip_customer' => ['type' => ObjectModel::KEY, 'columns' => ['id_customer']],
            ],
        ],
    ];
    /** @var int */
    public $id;
    /** @var int */
    public $id_customer;
    /** @var int */
    public $id_order;
    /** @var float */
    public $conversion_rate;
    /** @var float */
    public $total_products_tax_excl;
    /** @var float */
    public $total_products_tax_incl;
    /** @var float */
    public $total_shipping_tax_excl;
    /** @var float */
    public $total_shipping_tax_incl;
    /** @var float */
    public $amount;
    /** @var int */
    public $shipping_cost;
    /** @var float */
    public $shipping_cost_amount;
    /** @var int */
    public $partial;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var int */
    public $order_slip_type = 0;

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'objectNodeName'  => 'order_slip',
        'objectsNodeName' => 'order_slips',
        'fields'          => [
            'id_customer' => ['xlink_resource' => 'customers'],
            'id_order'    => ['xlink_resource' => 'orders'],
        ],
        'associations'    => [
            'order_slip_details' => [
                'resource' => 'order_slip_detail', 'setter' => false, 'virtual_entity' => true,
                'fields'   => [
                    'id'               => [],
                    'id_order_detail'  => ['required' => true],
                    'product_quantity' => ['required' => true],
                    'amount_tax_excl'  => ['required' => true],
                    'amount_tax_incl'  => ['required' => true],
                ],
            ],
        ],
    ];

    /**
     * @param int $customerId
     * @param bool $orderId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getOrdersSlip($customerId, $orderId = false)
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select(' *')
                ->from('order_slip')
                ->where('`id_customer` = '.(int) $customerId)
                ->where($orderId ? '`id_order` = '.(int) $orderId : '')
                ->orderBy('`date_add` DESC')
        );
    }

    /**
     * @param int $orderSlipId
     * @param Order $order
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getOrdersSlipProducts($orderSlipId, $order)
    {
        $productsRet = static::getOrdersSlipDetail($orderSlipId);
        $orderDetails = $order->getProductsDetail();

        $slipQuantity = [];
        foreach ($productsRet as $slipDetail) {
            $slipQuantity[$slipDetail['id_order_detail']] = $slipDetail;
        }

        $products = [];
        foreach ($orderDetails as $key => $product) {
            if (isset($slipQuantity[$product['id_order_detail']]) && $slipQuantity[$product['id_order_detail']]['product_quantity']) {
                $products[$key] = $product;
                $products[$key] = array_merge($products[$key], $slipQuantity[$product['id_order_detail']]);
            }
        }

        return $order->getProducts($products);
    }

    /**
     * @param bool $idOrderSlip
     * @param bool $idOrderDetail
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getOrdersSlipDetail($idOrderSlip = false, $idOrderDetail = false)
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select($idOrderDetail ? 'SUM(`product_quantity`) AS `total`' : '*')
                ->from('order_slip_detail')
                ->where($idOrderSlip ? '`id_order_slip` = '.(int) $idOrderSlip : '')
                ->where($idOrderDetail ? '`id_order_detail` = '.(int) $idOrderDetail : '')
        );
    }

    /**
     * Get refund details for one product line
     *
     * @param int $idOrderDetail
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getProductSlipDetail($idOrderDetail)
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`product_quantity`, `amount_tax_excl`, `amount_tax_incl`, `date_add`')
                ->from('order_slip_detail', 'osd')
                ->leftJoin('order_slip', 'os', 'os.`id_order_slip` = osd.`id_order_slip`')
                ->where('osd.`id_order_detail` = '.(int) $idOrderDetail)
        );
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getSlipsIdByDate($dateFrom, $dateTo)
    {
        $result = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_order_slip`')
                ->from('order_slip', 'os')
                ->leftJoin('orders', 'o', 'o.`id_order` = os.`id_order`')
                ->where('os.`date_add` BETWEEN \''.pSQL($dateFrom).' 00:00:00\' AND \''.pSQL($dateTo).' 23:59:59\' '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o'))
                ->orderBy('os.`date_add` ASC')
        );

        $slips = [];
        foreach ($result as $slip) {
            $slips[] = (int) $slip['id_order_slip'];
        }

        return $slips;
    }

    /**
     * @deprecated 1.0.0 use OrderSlip::create() instead
     *
     * @param Order $order
     * @param int[] $selectedOrderLines list of order details line IDs selected for refund
     * @param int[] $qtyList refund quantities associative array
     * @param float|bool $shippingCost Shipping costs to be refunded. Explicit shipping costs amount can be passed,
     *                                 or boolean value to indicate if total shipping costs should be refunded
     *
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function createOrderSlip($order, $selectedOrderLines, $qtyList, $shippingCost = false)
    {
        Tools::displayAsDeprecated();

        $newProductList = [];
        foreach ($selectedOrderLines as $idOrderDetail) {
            $orderDetail = new OrderDetail((int) $idOrderDetail);
            $newProductList[$idOrderDetail] = [
                'id_order_detail' => $idOrderDetail,
                'quantity'        => $qtyList[$idOrderDetail],
                'unit_price'      => $orderDetail->unit_price_tax_excl,
                'amount'          => $orderDetail->unit_price_tax_incl * $qtyList[$idOrderDetail],
            ];
        }
        return static::create($order, $newProductList, $shippingCost);
    }

    /**
     * @param Order $order The order this refunding is related to.
     * @param array $productList List of arrays with product descriptions.
     * @param float|bool $shippingCost Shipping costs to be refunded. Explicit shipping costs amount can be passed,
     *                                 or boolean value to indicate if total shipping costs should be refunded
     * @param int $amount Appears to be always zero as of 1.0.8.
     * @param bool $amountChoosen Appears to be always false as of 1.0.8.
     * @param bool $addTax True if prices are without tax, else false.
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function create(Order $order, $productList, $shippingCost = false, $amount = 0, $amountChoosen = false, $addTax = true)
    {
        $currency = new Currency((int) $order->id_currency);
        $orderSlip = new OrderSlip();
        $orderSlip->id_customer = (int) $order->id_customer;
        $orderSlip->id_order = (int) $order->id;
        $orderSlip->conversion_rate = $currency->conversion_rate;

        $orderSlip->total_shipping_tax_excl = 0;
        $orderSlip->total_shipping_tax_incl = 0;
        // TODO: deprecate this, nowhere in use.
        $orderSlip->partial = 0;

        $shippingCost = static::resolveShippingCost($shippingCost, $order, $addTax);

        if ($shippingCost > 0.0) {
            $orderSlip->shipping_cost = true;
            $orderSlip->shipping_cost_amount = $shippingCost;

            // Use taxes from the given order.
            $tax = new Tax();
            $tax->rate = $order->carrier_tax_rate;
            $taxCalculator = new TaxCalculator([$tax]);

            if ($addTax == true) {
                $orderSlip->total_shipping_tax_excl = $shippingCost;
                $orderSlip->total_shipping_tax_incl = $taxCalculator->addTaxes(
                    $shippingCost
                );
            } else {
                $orderSlip->total_shipping_tax_incl = $shippingCost;
                $orderSlip->total_shipping_tax_excl = $taxCalculator->removeTaxes(
                    $shippingCost
                );
            }
        } else {
            $orderSlip->shipping_cost = false;
            $orderSlip->shipping_cost_amount = 0;
        }

        $orderSlip->total_products_tax_excl = 0;
        $orderSlip->total_products_tax_incl = 0;

        foreach ($productList as &$product) {
            $orderDetail = new OrderDetail((int) $product['id_order_detail']);
            $quantity = (int) $product['quantity'];
            $orderSlipResume = static::getProductSlipResume((int) $orderDetail->id);

            if ($quantity + $orderSlipResume['product_quantity'] > $orderDetail->product_quantity) {
                $quantity = $orderDetail->product_quantity - $orderSlipResume['product_quantity'];
            }

            if ($quantity == 0) {
                continue;
            }

            if (!Tools::isSubmit('cancelProduct')) {
                $orderDetail->product_quantity_refunded += $quantity;
            }

            $orderDetail->save();

            // Use taxes from the given order detail.
            $tax = new Tax();
            $tax->rate = $orderDetail->tax_rate;
            $taxCalculator = new TaxCalculator([$tax]);

            // In case of a distinction between product value in the order and
            // product value in the refund (choosen by the merchant on refund
            // creation), these prices are reduced already.
            if ($addTax == true) {
                $product['unit_price_tax_excl'] = round(
                    $product['unit_price'],
                    _TB_PRICE_DATABASE_PRECISION_
                );
                $product['unit_price_tax_incl'] = $taxCalculator->addTaxes(
                    $product['unit_price']
                );
            } else {
                $product['unit_price_tax_incl'] = round(
                    $product['unit_price'],
                    _TB_PRICE_DATABASE_PRECISION_
                );
                $product['unit_price_tax_excl'] = $taxCalculator->removeTaxes(
                    $product['unit_price']
                );
            }

            $product['total_price_tax_excl'] =
                $product['unit_price_tax_excl'] * $quantity;
            $product['total_price_tax_incl'] =
                $product['unit_price_tax_incl'] * $quantity;
            $orderSlip->total_products_tax_excl
                += $product['total_price_tax_excl'];
            $orderSlip->total_products_tax_incl
                += $product['total_price_tax_incl'];

            // Do not round to display precision, because these values are
            // taken from the order, which contains rounded values already.
        }
        unset($product);

        if ($addTax == true) {
            $orderSlip->amount = $orderSlip->total_products_tax_excl;
        } else {
            $orderSlip->amount = $orderSlip->total_products_tax_incl;
        }

        if ((float) $amount && !$amountChoosen) {
            $orderSlip->order_slip_type = 1;
        }
        if (((float) $amount && $amountChoosen) || $orderSlip->shipping_cost_amount > 0) {
            $orderSlip->order_slip_type = 2;
        }

        if (!$orderSlip->add()) {
            return false;
        }

        $res = true;

        foreach ($productList as $product) {
            $res = $orderSlip->addProductOrderSlip($product) && $res;
        }

        return $res;
    }

    /**
     * Get resume of all refund for one product line
     *
     * @param int $idOrderDetail
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getProductSlipResume($idOrderDetail)
    {
        return Db::readOnly()->getRow(
            (new DbQuery())
                ->select('SUM(`product_quantity`) AS `product_quantity`, SUM(`amount_tax_excl`) AS `amount_tax_excl`, SUM(`amount_tax_incl`) AS `amount_tax_incl`')
                ->from('order_slip_detail')
                ->where('`id_order_detail` = '.(int) $idOrderDetail)
        );
    }

    /**
     * @param Order $order
     * @param float $amount
     * @param float $shippingCostAmount
     * @param array $orderDetailList
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function createPartialOrderSlip($order, $amount, $shippingCostAmount, $orderDetailList)
    {
        Tools::displayAsDeprecated();

        $currency = new Currency($order->id_currency);
        $orderSlip = new OrderSlip();
        $orderSlip->id_customer = (int) $order->id_customer;
        $orderSlip->id_order = (int) $order->id;
        $orderSlip->amount = round($amount, _TB_PRICE_DATABASE_PRECISION_);
        $orderSlip->shipping_cost = false;
        $orderSlip->shipping_cost_amount = round(
            $shippingCostAmount,
            _TB_PRICE_DATABASE_PRECISION_
        );
        $orderSlip->conversion_rate = $currency->conversion_rate;
        $orderSlip->partial = 1;
        if (!$orderSlip->add()) {
            return false;
        }

        $orderSlip->addPartialSlipDetail($orderDetailList);

        return true;
    }

    /**
     * @param array $orderDetailList
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addPartialSlipDetail($orderDetailList)
    {
        Tools::displayAsDeprecated();

        foreach ($orderDetailList as $idOrderDetail => $tab) {
            $orderDetail = new OrderDetail($idOrderDetail);
            $orderSlipResume = static::getProductSlipResume($idOrderDetail);

            if ($tab['amount'] + $orderSlipResume['amount_tax_incl'] > $orderDetail->total_price_tax_incl) {
                $tab['amount'] = $orderDetail->total_price_tax_incl - $orderSlipResume['amount_tax_incl'];
            }

            if ($tab['amount'] == 0) {
                continue;
            }

            if ($tab['quantity'] + $orderSlipResume['product_quantity'] > $orderDetail->product_quantity) {
                $tab['quantity'] = $orderDetail->product_quantity - $orderSlipResume['product_quantity'];
            }

            $tab['amount_tax_excl'] = $tab['amount_tax_incl'] = $tab['amount'];

            $connection = Db::readOnly();
            $idTax = (int) $connection->getValue(
                (new DbQuery())
                    ->select('`id_tax`')
                    ->from('order_detail_tax')
                    ->where('`id_order_detail` = '.(int) $idOrderDetail)
            );

            if ($idTax > 0) {
                $rate = (float) $connection->getValue(
                    (new DbQuery())
                        ->select('`rate`')
                        ->from('tax')
                        ->where('`id_tax` = '.(int) $idTax)
                );

                if ($rate > 0) {
                    $rate = 1 + ($rate / 100);
                    $tab['amount_tax_excl'] = round(
                        $tab['amount_tax_excl'] / $rate,
                        _TB_PRICE_DATABASE_PRECISION_
                    );
                }
            }

            if ($tab['quantity'] > 0 && $tab['quantity'] > $orderDetail->product_quantity_refunded) {
                $orderDetail->product_quantity_refunded = $tab['quantity'];
                $orderDetail->save();
            }

            $insertOrderSlip = [
                'id_order_slip'    => (int) $this->id,
                'id_order_detail'  => (int) $idOrderDetail,
                'product_quantity' => (int) $tab['quantity'],
                'amount_tax_excl'  => (float) $tab['amount_tax_excl'],
                'amount_tax_incl'  => (float) $tab['amount_tax_incl'],
            ];

            Db::getInstance()->insert('order_slip_detail', $insertOrderSlip);
        }
    }

    /**
     * @param array $orderDetailList
     * @param array $productQtyList
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addSlipDetail($orderDetailList, $productQtyList)
    {
        Tools::displayAsDeprecated();

        foreach ($orderDetailList as $key => $idOrderDetail) {
            if ($qty = (int) ($productQtyList[$key])) {
                $orderDetail = new OrderDetail((int) $idOrderDetail);

                if (Validate::isLoadedObject($orderDetail)) {
                    Db::getInstance()->insert(
                        'order_slip_detail',
                        [
                            'id_order_slip'    => (int) $this->id,
                            'id_order_detail'  => (int) $idOrderDetail,
                            'product_quantity' => $qty,
                            'amount_tax_excl'  => round(
                                $orderDetail->unit_price_tax_excl * $qty,
                                _TB_PRICE_DATABASE_PRECISION_
                            ),
                            'amount_tax_incl'  => round(
                                $orderDetail->unit_price_tax_incl * $qty,
                                _TB_PRICE_DATABASE_PRECISION_
                            ),
                        ]
                    );
                }
            }
        }
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getProducts()
    {
        $result = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('*')
                ->from('order_slip_detail', 'osd')
                ->innerJoin('order_detail', 'od', 'osd.`id_order_detail` = od.`id_order_detail`')
                ->where('osd.`id_order_slip` = '.(int) $this->id)
        );

        $order = new Order($this->id_order);
        $products = [];
        foreach ($result as $row) {
            $order->setProductPrices($row);
            $products[] = $row;
        }

        return $products;
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getEcoTaxTaxesBreakdown()
    {
        $ecotaxDetail = [];
        foreach ($this->getOrdersSlipDetail((int) $this->id) as $orderSlipDetails) {
            $row = Db::readOnly()->getRow(
                (new DbQuery())
                    ->select('`ecotax_tax_rate` AS `rate`, `ecotax` AS `ecotax_tax_excl`, `ecotax` AS `ecotax_tax_incl`, `product_quantity`')
                    ->from('order_detail')
                    ->where('`id_order_detail` = '.(int) $orderSlipDetails['id_order_detail'])
            );

            if (!isset($ecotaxDetail[$row['rate']])) {
                $ecotaxDetail[$row['rate']] = ['ecotax_tax_incl' => 0, 'ecotax_tax_excl' => 0, 'rate' => $row['rate']];
            }

            $quantity = (int) $orderSlipDetails['product_quantity'];
            $ecotaxDetail[$row['rate']]['ecotax_tax_incl'] += round(
                $row['ecotax_tax_excl'] * $quantity * (1 + $row['rate'] / 100),
                _TB_PRICE_DATABASE_PRECISION_
            );
            $ecotaxDetail[$row['rate']]['ecotax_tax_excl'] += round(
                $row['ecotax_tax_excl'] * $quantity,
                _TB_PRICE_DATABASE_PRECISION_
            );
        }

        return $ecotaxDetail;
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getWsOrderSlipDetails()
    {
        Tools::displayAsDeprecated();

        $result = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_order_slip` AS `id`, `id_order_detail`, `product_quantity`, `amount_tax_excl`, `amount_tax_incl`')
                ->from('order_slip_detail')
                ->where('`id_order_slip` = '.(int) $this->id)
        );

        return $result;
    }

    /**
     * @param array $values
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setWsOrderSlipDetails($values)
    {
        Tools::displayAsDeprecated();

        $conn = Db::getInstance();
        if ($conn->delete('order_slip_detail', '`id_order_slip` = '.(int) $this->id)) {
            $insert = [];
            foreach ($values as $value) {
                $insert[] = [
                    'id_order_slip' => (int) $this->id,
                    'id_order_detail' => (int) $value['id_order_detail'],
                    'product_quantity' => (int) $value['product_quantity'],
                    'amount_tax_excl' => ['type' => 'sql', 'value' => isset($value['amount_tax_excl']) ? (float) $value['amount_tax_excl'] : 'NULL'],
                    'amount_tax_incl' => ['type' => 'sql', 'value' => isset($value['amount_tax_incl']) ? (float) $value['amount_tax_incl'] : 'NULL'],
                ];
            }
            $conn->insert('order_slip_detail', $insert);
        }

        return true;
    }

    /**
     * @param array $product
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function addProductOrderSlip($product)
    {
        return Db::getInstance()->insert('order_slip_detail', [
            'id_order_slip'        => (int) $this->id,
            'id_order_detail'      => (int) $product['id_order_detail'],
            'product_quantity'     => (int) $product['quantity'],
            'unit_price_tax_excl'  => round(
                $product['unit_price_tax_excl'],
                _TB_PRICE_DATABASE_PRECISION_
            ),
            'unit_price_tax_incl'  => round(
                $product['unit_price_tax_incl'],
                _TB_PRICE_DATABASE_PRECISION_
            ),
            'total_price_tax_excl' => round(
                $product['total_price_tax_excl'],
                _TB_PRICE_DATABASE_PRECISION_
            ),
            'total_price_tax_incl' => round(
                $product['total_price_tax_incl'],
                _TB_PRICE_DATABASE_PRECISION_
            ),
            'amount_tax_excl'      => round(
                $product['total_price_tax_excl'],
                _TB_PRICE_DATABASE_PRECISION_
            ),
            'amount_tax_incl'      => round(
                $product['total_price_tax_incl'],
                _TB_PRICE_DATABASE_PRECISION_
            ),
        ]);
    }

    /**
     * Returns shipping costs for refund
     *
     * The shipping cost can either be explicitly specified by $shippingCost parameter. Alternatively, boolean
     * value can be provided to specify if total order shipping cost should be returned
     *
     * @param float|bool $shippingCost Explicit value, or boolean to indicate if order total shipping cost should be used
     * @param Order $order associated order
     * @param bool $withoutTax
     *
     * @return float
     */
    protected static function resolveShippingCost($shippingCost, Order $order, $withoutTax)
    {
        if ($shippingCost === false) {
            return 0.0;
        }

        if (! is_numeric($shippingCost)) {
            if ($withoutTax) {
                $shippingCost = $order->total_shipping_tax_excl;
            } else {
                $shippingCost = $order->total_shipping_tax_incl;
            }
        }

        return (float)round($shippingCost, _TB_PRICE_DATABASE_PRECISION_);
    }

}
