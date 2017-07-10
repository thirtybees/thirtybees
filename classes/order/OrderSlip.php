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
 * Class OrderSlipCore
 *
 * @since 1.0.0
 */
class OrderSlipCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'order_slip',
        'primary' => 'id_order_slip',
        'fields'  => [
            'id_customer'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'conversion_rate'         => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'total_products_tax_excl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'total_products_tax_incl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'total_shipping_tax_excl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'total_shipping_tax_incl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'amount'                  => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'shipping_cost'           => ['type' => self::TYPE_INT],
            'shipping_cost_amount'    => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'partial'                 => ['type' => self::TYPE_INT],
            'date_add'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'order_slip_type'         => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
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
    /** @var int */
    public $amount;
    /** @var int */
    public $shipping_cost;
    /** @var int */
    public $shipping_cost_amount;
    /** @var int */
    public $partial;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var int */
    public $order_slip_type = 0;
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
    // @codingStandardsIgnoreEnd

    /**
     * @param int  $customerId
     * @param bool $orderId
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getOrdersSlip($customerId, $orderId = false)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select(' *')
                ->from('order_slip')
                ->where('`id_customer` = '.(int) $customerId)
                ->where($orderId ? '`id_order` = '.(int) $orderId : '')
                ->orderBy('`date_add` DESC')
        );
    }

    /**
     * @param int   $orderSlipId
     * @param Order $order
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getOrdersSlipProducts($orderSlipId, $order)
    {
        $productsRet = OrderSlip::getOrdersSlipDetail($orderSlipId);
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
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getOrdersSlipDetail($idOrderSlip = false, $idOrderDetail = false)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @return array|false|null|PDOStatement
     */
    public static function getProductSlipDetail($idOrderDetail)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getSlipsIdByDate($dateFrom, $dateTo)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     * @param array $productList
     * @param array $qtyList
     * @param bool  $shippingCost
     *
     * @return bool
     */
    public static function createOrderSlip($order, $productList, $qtyList, $shippingCost = false)
    {
        Tools::displayAsDeprecated();

        $productList = [];
        $shipping = false;
        foreach ($productList as $idOrderDetail) {
            $orderDetail = new OrderDetail((int) $idOrderDetail);
            $productList[$idOrderDetail] = [
                'id_order_detail' => $idOrderDetail,
                'quantity'        => $qtyList[$idOrderDetail],
                'unit_price'      => $orderDetail->unit_price_tax_excl,
                'amount'          => $orderDetail->unit_price_tax_incl * $qtyList[$idOrderDetail],
            ];

            $shipping = $shippingCost ? null : false;
        }

        return OrderSlip::create($order, $productList, $shipping);
    }

    /**
     * @param Order $order
     * @param array $productList
     * @param bool  $shippingCost
     * @param int   $amount
     * @param bool  $amountChoosen
     * @param bool  $addTax
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function create(Order $order, $productList, $shippingCost = false, $amount = 0, $amountChoosen = false, $addTax = true)
    {
        $currency = new Currency((int) $order->id_currency);
        $orderSlip = new OrderSlip();
        $orderSlip->id_customer = (int) $order->id_customer;
        $orderSlip->id_order = (int) $order->id;
        $orderSlip->conversion_rate = $currency->conversion_rate;

        if ($addTax) {
            $addOrRemove = 'add';
            $incOrEx1 = 'excl';
            $incOrEx2 = 'incl';
        } else {
            $addOrRemove = 'remove';
            $incOrEx1 = 'incl';
            $incOrEx2 = 'excl';
        }

        $orderSlip->{'total_shipping_tax_'.$incOrEx1} = 0;
        $orderSlip->{'total_shipping_tax_'.$incOrEx2} = 0;
        $orderSlip->partial = 0;

        if ($shippingCost !== false) {
            $orderSlip->shipping_cost = true;
            $carrier = new Carrier((int) $order->id_carrier);
            $address = Address::initialize($order->id_address_delivery, false);
            $taxCalculator = $carrier->getTaxCalculator($address);
            $orderSlip->{'total_shipping_tax_'.$incOrEx1} = ($shippingCost === null ? $order->{'total_shipping_tax_'.$incOrEx1} : (float) $shippingCost);

            if ($taxCalculator instanceof TaxCalculator) {
                $orderSlip->{'total_shipping_tax_'.$incOrEx2} = Tools::ps_round($taxCalculator->{$addOrRemove.'Taxes'}($orderSlip->{'total_shipping_tax_'.$incOrEx1}), _TB_PRICE_DATABASE_PRECISION_);
            } else {
                $orderSlip->{'total_shipping_tax_'.$incOrEx2} = $orderSlip->{'total_shipping_tax_'.$incOrEx1};
            }
        } else {
            $orderSlip->shipping_cost = false;
        }

        $orderSlip->amount = 0;
        $orderSlip->{'total_products_tax_'.$incOrEx1} = 0;
        $orderSlip->{'total_products_tax_'.$incOrEx2} = 0;

        foreach ($productList as &$product) {
            $orderDetail = new OrderDetail((int) $product['id_order_detail']);
            $price = (float) $product['unit_price'];
            $quantity = (int) $product['quantity'];
            $orderSlipResume = OrderSlip::getProductSlipResume((int) $orderDetail->id);

            if ($quantity + $orderSlipResume['product_quantity'] > $orderDetail->product_quantity) {
                $quantity = $orderDetail->product_quantity - $orderSlipResume['product_quantity'];
            }

            if ($quantity == 0) {
                continue;
            }

            if (!Tools::isSubmit('cancelProduct') && $order->hasBeenPaid()) {
                $orderDetail->product_quantity_refunded += $quantity;
            }

            $orderDetail->save();

            $address = Address::initialize($order->id_address_invoice, false);
            $idAddress = (int) $address->id;
            $idTaxRulesGroup = Product::getIdTaxRulesGroupByIdProduct((int) $orderDetail->product_id);
            $taxCalculator = TaxManagerFactory::getManager($address, $idTaxRulesGroup)->getTaxCalculator();

            $orderSlip->{'total_products_tax_'.$incOrEx1} += $price * $quantity;

            if (in_array(Configuration::get('PS_ROUND_TYPE'), [Order::ROUND_ITEM, Order::ROUND_LINE])) {
                if (!isset($totalProducts[$idTaxRulesGroup])) {
                    $totalProducts[$idTaxRulesGroup] = 0;
                }
            } else {
                if (!isset($totalProducts[$idTaxRulesGroup.'_'.$idAddress])) {
                    $totalProducts[$idTaxRulesGroup.'_'.$idAddress] = 0;
                }
            }

            $productTaxInclLine = Tools::ps_round($taxCalculator->{$addOrRemove.'Taxes'}($price) * $quantity, _TB_PRICE_DATABASE_PRECISION_);

            switch (Configuration::get('PS_ROUND_TYPE')) {
                case Order::ROUND_ITEM:
                    $productTaxIncl = Tools::ps_round($taxCalculator->{$addOrRemove.'Taxes'}($price), _TB_PRICE_DATABASE_PRECISION_) * $quantity;
                    $totalProducts[$idTaxRulesGroup] += $productTaxIncl;
                    break;
                case Order::ROUND_LINE:
                    $productTaxIncl = $productTaxInclLine;
                    $totalProducts[$idTaxRulesGroup] += $productTaxIncl;
                    break;
                case Order::ROUND_TOTAL:
                    $productTaxIncl = $productTaxInclLine;
                    $totalProducts[$idTaxRulesGroup.'_'.$idAddress] += $price * $quantity;
                    break;
            }

            $product['unit_price_tax_'.$incOrEx1] = $price;
            $product['unit_price_tax_'.$incOrEx2] = Tools::ps_round($taxCalculator->{$addOrRemove.'Taxes'}($price), _TB_PRICE_DATABASE_PRECISION_);
            $product['total_price_tax_'.$incOrEx1] = Tools::ps_round($price * $quantity, _TB_PRICE_DATABASE_PRECISION_);
            $product['total_price_tax_'.$incOrEx2] = Tools::ps_round($productTaxIncl, _TB_PRICE_DATABASE_PRECISION_);
        }

        unset($product);

        foreach ($totalProducts as $key => $price) {
            if (Configuration::get('PS_ROUND_TYPE') == Order::ROUND_TOTAL) {
                $tmp = explode('_', $key);
                $address = Address::initialize((int) $tmp[1], true);
                $taxCalculator = TaxManagerFactory::getManager($address, $tmp[0])->getTaxCalculator();
                $orderSlip->{'total_products_tax_'.$incOrEx2} += Tools::ps_round($taxCalculator->{$addOrRemove.'Taxes'}($price), _TB_PRICE_DATABASE_PRECISION_);
            } else {
                $orderSlip->{'total_products_tax_'.$incOrEx2} += $price;
            }
        }

        $orderSlip->{'total_products_tax_'.$incOrEx2} -= (float) $amount && !$amountChoosen ? (float) $amount : 0;
        $orderSlip->amount = $amountChoosen ? (float) $amount : $orderSlip->{'total_products_tax_'.$incOrEx1};
        $orderSlip->shipping_cost_amount = $orderSlip->total_shipping_tax_incl;

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
            $res &= $orderSlip->addProductOrderSlip($product);
        }

        return $res;
    }

    /**
     * Get resume of all refund for one product line
     *
     * @param int $idOrderDetail
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @return array|bool|null|object
     */
    public static function getProductSlipResume($idOrderDetail)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function createPartialOrderSlip($order, $amount, $shippingCostAmount, $orderDetailList)
    {
        $currency = new Currency($order->id_currency);
        $orderSlip = new OrderSlip();
        $orderSlip->id_customer = (int) $order->id_customer;
        $orderSlip->id_order = (int) $order->id;
        $orderSlip->amount = (float) $amount;
        $orderSlip->shipping_cost = false;
        $orderSlip->shipping_cost_amount = (float) $shippingCostAmount;
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
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addPartialSlipDetail($orderDetailList)
    {
        foreach ($orderDetailList as $idOrderDetail => $tab) {
            $orderDetail = new OrderDetail($idOrderDetail);
            $orderSlipResume = OrderSlip::getProductSlipResume($idOrderDetail);

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

            $idTax = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_tax`')
                    ->from('order_detail_tax')
                    ->where('`id_order_detail` = '.(int) $idOrderDetail)
            );

            if ($idTax > 0) {
                $rate = (float) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    (new DbQuery())
                        ->select('`rate`')
                        ->from('tax')
                        ->where('`id_tax` = '.(int) $idTax)
                );

                if ($rate > 0) {
                    $rate = 1 + ($rate / 100);
                    $tab['amount_tax_excl'] = $tab['amount_tax_excl'] / $rate;
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addSlipDetail($orderDetailList, $productQtyList)
    {
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
                            'amount_tax_excl'  => $orderDetail->unit_price_tax_excl * $qty,
                            'amount_tax_incl'  => $orderDetail->unit_price_tax_incl * $qty,
                        ]
                    );
                }
            }
        }
    }

    /**
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProducts()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getEcoTaxTaxesBreakdown()
    {
        $ecotaxDetail = [];
        foreach ($this->getOrdersSlipDetail((int) $this->id) as $orderSlipDetails) {
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('`ecotax_tax_rate` AS `rate`, `ecotax` AS `ecotax_tax_excl`, `ecotax` AS `ecotax_tax_incl`, `product_quantity`')
                    ->from('order_detail')
                    ->where('`id_order_detail` = '.(int) $orderSlipDetails['id_order_detail'])
            );

            if (!isset($ecotaxDetail[$row['rate']])) {
                $ecotaxDetail[$row['rate']] = ['ecotax_tax_incl' => 0, 'ecotax_tax_excl' => 0, 'rate' => $row['rate']];
            }

            $ecotaxDetail[$row['rate']]['ecotax_tax_incl'] += Tools::ps_round(($row['ecotax_tax_excl'] * $orderSlipDetails['product_quantity']) + ($row['ecotax_tax_excl'] * $orderSlipDetails['product_quantity'] * $row['rate'] / 100), 2);
            $ecotaxDetail[$row['rate']]['ecotax_tax_excl'] += Tools::ps_round($row['ecotax_tax_excl'] * $orderSlipDetails['product_quantity'], 2);
        }

        return $ecotaxDetail;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsOrderSlipDetails()
    {
        $result = Db::getInstance()->executeS(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setWsOrderSlipDetails($values)
    {
        if (Db::getInstance()->delete('order_slip_detail', '`id_order_slip` = '.(int) $this->id)) {
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
            Db::getInstance()->insert('order_slip_detail', $insert);
        }

        return true;
    }

    /**
     * @param $product
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function addProductOrderSlip($product)
    {
        return Db::getInstance()->insert(
            'order_slip_detail',
            [
                'id_order_slip'        => (int) $this->id,
                'id_order_detail'      => (int) $product['id_order_detail'],
                'product_quantity'     => $product['quantity'],
                'unit_price_tax_excl'  => $product['unit_price_tax_excl'],
                'unit_price_tax_incl'  => $product['unit_price_tax_incl'],
                'total_price_tax_excl' => $product['total_price_tax_excl'],
                'total_price_tax_incl' => $product['total_price_tax_incl'],
                'amount_tax_excl'      => $product['total_price_tax_excl'],
                'amount_tax_incl'      => $product['total_price_tax_incl'],
            ]
        );
    }
}
