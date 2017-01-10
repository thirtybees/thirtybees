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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'order_slip',
        'primary' => 'id_order_slip',
        'fields'  => [
            'id_customer'             => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'id_order'                => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'conversion_rate'         => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat',      'required' => true],
            'total_products_tax_excl' => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat',      'required' => true],
            'total_products_tax_incl' => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat',      'required' => true],
            'total_shipping_tax_excl' => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat',      'required' => true],
            'total_shipping_tax_incl' => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat',      'required' => true],
            'amount'                  => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                         ],
            'shipping_cost'           => ['type' => self::TYPE_INT                                                     ],
            'shipping_cost_amount'    => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                         ],
            'partial'                 => ['type' => self::TYPE_INT                                                     ],
            'date_add'                => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                          ],
            'date_upd'                => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                          ],
            'order_slip_type'         => ['type' => self::TYPE_INT,    'validate' => 'isInt'                           ],
        ],
    ];

    protected $webserviceParameters = [
        'objectNodeName' => 'order_slip',
        'objectsNodeName' => 'order_slips',
        'fields' => [
            'id_customer' => ['xlink_resource'=> 'customers'],
            'id_order' => ['xlink_resource'=> 'orders'],
        ],
        'associations' => [
            'order_slip_details' => [
                'resource' => 'order_slip_detail', 'setter' => false, 'virtual_entity' => true,
                'fields' => [
                    'id' =>  [],
                    'id_order_detail' => ['required' => true],
                    'product_quantity' => ['required' => true],
                    'amount_tax_excl' => ['required' => true],
                    'amount_tax_incl' => ['required' => true],
                ]
            ],
        ],
    ];

    /**
     * @param $orderDetailList
     * @param $productQtyList
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function addSlipDetail($orderDetailList, $productQtyList)
    {
        foreach ($orderDetailList as $key => $idOrderDetail) {
            if ($qty = (int) ($productQtyList[$key])) {
                $orderDetail = new OrderDetail((int)$idOrderDetail);

                if (Validate::isLoadedObject($orderDetail)) {
                    Db::getInstance()->insert('order_slip_detail', [
                        'id_order_slip' => (int)$this->id,
                        'id_order_detail' => (int)$idOrderDetail,
                        'product_quantity' => $qty,
                        'amount_tax_excl' => $orderDetail->unit_price_tax_excl * $qty,
                        'amount_tax_incl' => $orderDetail->unit_price_tax_incl * $qty
                    ]
                    );
                }
            }
        }
    }

    /**
     * @param      $customerId
     * @param bool $orderId
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getOrdersSlip($customerId, $orderId = false)
    {
        return Db::getInstance()->executeS('
		SELECT *
		FROM `'._DB_PREFIX_.'order_slip`
		WHERE `id_customer` = '.(int) ($customerId).
        ($orderId ? ' AND `id_order` = '.(int) ($orderId) : '').'
		ORDER BY `date_add` DESC');
    }

    /**
     * @param bool $idOrderSlip
     * @param bool $idOrderDetail
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getOrdersSlipDetail($idOrderSlip = false, $idOrderDetail = false)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
        ($idOrderDetail ? 'SELECT SUM(`product_quantity`) AS `total`' : 'SELECT *').
        'FROM `'._DB_PREFIX_.'order_slip_detail`'
        .($idOrderSlip ? ' WHERE `id_order_slip` = '.(int) ($idOrderSlip) : '')
        .($idOrderDetail ? ' WHERE `id_order_detail` = '.(int) ($idOrderDetail) : ''));
    }

    /**
     * @param int $orderSlipId
     * @param Order $order
     *
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getOrdersSlipProducts($orderSlipId, $order)
    {
        $cartRules = $order->getCartRules(true);
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
     *
     * Get resume of all refund for one product line
     *
     * @param $idOrderDetail
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProductSlipResume($idOrderDetail)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
			SELECT SUM(product_quantity) product_quantity, SUM(amount_tax_excl) amount_tax_excl, SUM(amount_tax_incl) amount_tax_incl
			FROM `'._DB_PREFIX_.'order_slip_detail`
			WHERE `id_order_detail` = '.(int) $idOrderDetail);
    }

    /**
     *
     * Get refund details for one product line
     *
     * @param $idOrderDetail
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProductSlipDetail($idOrderDetail)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT product_quantity, amount_tax_excl, amount_tax_incl, date_add
			FROM `'._DB_PREFIX_.'order_slip_detail` osd
			LEFT JOIN `'._DB_PREFIX_.'order_slip` os
			ON os.id_order_slip = osd.id_order_slip
			WHERE osd.`id_order_detail` = '.(int) $idOrderDetail);
    }

    /**
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProducts()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT *, osd.product_quantity
		FROM `'._DB_PREFIX_.'order_slip_detail` osd
		INNER JOIN `'._DB_PREFIX_.'order_detail` od ON osd.id_order_detail = od.id_order_detail
		WHERE osd.`id_order_slip` = '.(int) $this->id);

        $order = new Order($this->id_order);
        $products = [];
        foreach ($result as $row) {
            $order->setProductPrices($row);
            $products[] = $row;
        }
        return $products;
    }

    /**
     * @param $dateFrom
     * @param $dateTo
     *
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getSlipsIdByDate($dateFrom, $dateTo)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT `id_order_slip`
		FROM `'._DB_PREFIX_.'order_slip` os
		LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = os.`id_order`)
		WHERE os.`date_add` BETWEEN \''.pSQL($dateFrom).' 00:00:00\' AND \''.pSQL($dateTo).' 23:59:59\'
		'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
		ORDER BY os.`date_add` ASC');

        $slips = [];
        foreach ($result as $slip) {
            $slips[] = (int)$slip['id_order_slip'];
        }
        return $slips;
    }

    /**
     * @deprecated 1.0.0 use OrderSlip::create() instead
     *
     */
    public static function createOrderSlip($order, $productList, $qtyList, $shipping_cost = false)
    {
        Tools::displayAsDeprecated();

        $product_list = [];
        foreach ($productList as $id_order_detail) {
            $order_detail = new OrderDetail((int)$id_order_detail);
            $product_list[$id_order_detail] = [
                'id_order_detail' => $id_order_detail,
                'quantity' => $qtyList[$id_order_detail],
                'unit_price' => $order_detail->unit_price_tax_excl,
                'amount' => $order_detail->unit_price_tax_incl * $qtyList[$id_order_detail],
            ];

            $shipping = $shipping_cost ? null : false;
        }

        return OrderSlip::create($order, $product_list, $shipping);
    }

    /**
     * @param Order $order
     * @param       $productList
     * @param bool  $shippingCost
     * @param int   $amount
     * @param bool  $amountChoosen
     * @param bool  $addTax
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function create(Order $order, $productList, $shippingCost = false, $amount = 0, $amountChoosen = false, $addTax = true)
    {
        $currency = new Currency((int)$order->id_currency);
        $order_slip = new OrderSlip();
        $order_slip->id_customer = (int)$order->id_customer;
        $order_slip->id_order = (int)$order->id;
        $order_slip->conversion_rate = $currency->conversion_rate;

        if ($addTax) {
            $add_or_remove = 'add';
            $inc_or_ex_1 = 'excl';
            $inc_or_ex_2 = 'incl';
        } else {
            $add_or_remove = 'remove';
            $inc_or_ex_1 = 'incl';
            $inc_or_ex_2 = 'excl';
        }

        $order_slip->{'total_shipping_tax_'.$inc_or_ex_1} = 0;
        $order_slip->{'total_shipping_tax_'.$inc_or_ex_2} = 0;
        $order_slip->partial = 0;

        if ($shippingCost !== false) {
            $order_slip->shipping_cost = true;
            $carrier = new Carrier((int)$order->id_carrier);
            $address = Address::initialize($order->id_address_delivery, false);
            $tax_calculator = $carrier->getTaxCalculator($address);
            $order_slip->{'total_shipping_tax_'.$inc_or_ex_1} = ($shippingCost === null ? $order->{'total_shipping_tax_'.$inc_or_ex_1} : (float)$shippingCost);

            if ($tax_calculator instanceof TaxCalculator) {
                $order_slip->{'total_shipping_tax_'.$inc_or_ex_2} = Tools::ps_round($tax_calculator->{$add_or_remove.'Taxes'}($order_slip->{'total_shipping_tax_'.$inc_or_ex_1}), _PS_PRICE_COMPUTE_PRECISION_);
            } else {
                $order_slip->{'total_shipping_tax_'.$inc_or_ex_2} = $order_slip->{'total_shipping_tax_'.$inc_or_ex_1};
            }
        } else {
            $order_slip->shipping_cost = false;
        }

        $order_slip->amount = 0;
        $order_slip->{'total_products_tax_'.$inc_or_ex_1} = 0;
        $order_slip->{'total_products_tax_'.$inc_or_ex_2} = 0;

        foreach ($productList as &$product) {
            $order_detail = new OrderDetail((int)$product['id_order_detail']);
            $price = (float)$product['unit_price'];
            $quantity = (int)$product['quantity'];
            $order_slip_resume = OrderSlip::getProductSlipResume((int)$order_detail->id);

            if ($quantity + $order_slip_resume['product_quantity'] > $order_detail->product_quantity) {
                $quantity = $order_detail->product_quantity - $order_slip_resume['product_quantity'];
            }

            if ($quantity == 0) {
                continue;
            }

            if (!Tools::isSubmit('cancelProduct') && $order->hasBeenPaid()) {
                $order_detail->product_quantity_refunded += $quantity;
            }

            $order_detail->save();

            $address = Address::initialize($order->id_address_invoice, false);
            $id_address = (int)$address->id;
            $id_tax_rules_group = Product::getIdTaxRulesGroupByIdProduct((int)$order_detail->product_id);
            $tax_calculator = TaxManagerFactory::getManager($address, $id_tax_rules_group)->getTaxCalculator();

            $order_slip->{'total_products_tax_'.$inc_or_ex_1} += $price * $quantity;

            if (in_array(Configuration::get('PS_ROUND_TYPE'), [Order::ROUND_ITEM, Order::ROUND_LINE])) {
                if (!isset($total_products[$id_tax_rules_group])) {
                    $total_products[$id_tax_rules_group] = 0;
                }
            } else {
                if (!isset($total_products[$id_tax_rules_group.'_'.$id_address])) {
                    $total_products[$id_tax_rules_group.'_'.$id_address] = 0;
                }
            }

            $product_tax_incl_line = Tools::ps_round($tax_calculator->{$add_or_remove.'Taxes'}($price) * $quantity, _PS_PRICE_COMPUTE_PRECISION_);

            switch (Configuration::get('PS_ROUND_TYPE')) {
                case Order::ROUND_ITEM:
                    $product_tax_incl = Tools::ps_round($tax_calculator->{$add_or_remove.'Taxes'}($price), _PS_PRICE_COMPUTE_PRECISION_) * $quantity;
                    $total_products[$id_tax_rules_group] += $product_tax_incl;
                    break;
                case Order::ROUND_LINE:
                    $product_tax_incl = $product_tax_incl_line;
                    $total_products[$id_tax_rules_group] += $product_tax_incl;
                    break;
                case Order::ROUND_TOTAL:
                    $product_tax_incl = $product_tax_incl_line;
                    $total_products[$id_tax_rules_group.'_'.$id_address] += $price * $quantity;
                    break;
            }

            $product['unit_price_tax_'.$inc_or_ex_1] = $price;
            $product['unit_price_tax_'.$inc_or_ex_2] = Tools::ps_round($tax_calculator->{$add_or_remove.'Taxes'}($price), _PS_PRICE_COMPUTE_PRECISION_);
            $product['total_price_tax_'.$inc_or_ex_1] = Tools::ps_round($price * $quantity, _PS_PRICE_COMPUTE_PRECISION_);
            $product['total_price_tax_'.$inc_or_ex_2] = Tools::ps_round($product_tax_incl, _PS_PRICE_COMPUTE_PRECISION_);
        }

        unset($product);

        foreach ($total_products as $key => $price) {
            if (Configuration::get('PS_ROUND_TYPE') == Order::ROUND_TOTAL) {
                $tmp = explode('_', $key);
                $address = Address::initialize((int)$tmp[1], true);
                $tax_calculator = TaxManagerFactory::getManager($address, $tmp[0])->getTaxCalculator();
                $order_slip->{'total_products_tax_'.$inc_or_ex_2} += Tools::ps_round($tax_calculator->{$add_or_remove.'Taxes'}($price), _PS_PRICE_COMPUTE_PRECISION_);
            } else {
                $order_slip->{'total_products_tax_'.$inc_or_ex_2} += $price;
            }
        }

        $order_slip->{'total_products_tax_'.$inc_or_ex_2} -= (float)$amount && !$amountChoosen ? (float)$amount : 0;
        $order_slip->amount = $amountChoosen ? (float)$amount : $order_slip->{'total_products_tax_'.$inc_or_ex_1};
        $order_slip->shipping_cost_amount = $order_slip->total_shipping_tax_incl;

        if ((float)$amount && !$amountChoosen) {
            $order_slip->order_slip_type = 1;
        }
        if (((float)$amount && $amountChoosen) || $order_slip->shipping_cost_amount > 0) {
            $order_slip->order_slip_type = 2;
        }

        if (!$order_slip->add()) {
            return false;
        }

        $res = true;

        foreach ($productList as $product) {
            $res &= $order_slip->addProductOrderSlip($product);
        }

        return $res;
    }

    /**
     * @param $product
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function addProductOrderSlip($product)
    {
        return Db::getInstance()->insert(
            'order_slip_detail', [
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

    /**
     * @param $order
     * @param $amount
     * @param $shippingCostAmount
     * @param $orderDetailList
     *
     * @return bool
     *
     * @since 1.0.0
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
     * @param $orderDetailList
     *
     *
     * @since 1.0.0
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

            $id_tax = (int)Db::getInstance()->getValue('
				SELECT `id_tax`
				FROM `'._DB_PREFIX_.'order_detail_tax`
				WHERE `id_order_detail` = '.(int)$idOrderDetail
            );

            if ($id_tax > 0) {
                $rate = (float)Db::getInstance()->getValue('
					SELECT `rate`
					FROM `'._DB_PREFIX_.'tax`
					WHERE `id_tax` = '.(int)$id_tax
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
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getEcoTaxTaxesBreakdown()
    {
        $ecotaxDetail = [];
        foreach ($this->getOrdersSlipDetail((int) $this->id) as $orderSlipDetails) {
            $row = Db::getInstance()->getRow(
                '
					SELECT `ecotax_tax_rate` as `rate`, `ecotax` as `ecotax_tax_excl`, `ecotax` as `ecotax_tax_incl`, `product_quantity`
					FROM `'._DB_PREFIX_.'order_detail`
					WHERE `id_order_detail` = '.(int) $orderSlipDetails['id_order_detail']
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
        $query = 'SELECT id_order_slip as id, id_order_detail, product_quantity, amount_tax_excl, amount_tax_incl
		FROM `'._DB_PREFIX_.'order_slip_detail`
		WHERE id_order_slip = '.(int) $this->id;
        $result = Db::getInstance()->executeS($query);

        return $result;
    }

    /**
     * @param $values
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setWsOrderSlipDetails($values)
    {
        if (Db::getInstance()->execute('DELETE from `'._DB_PREFIX_.'order_slip_detail` where id_order_slip = '.(int) $this->id)) {
            $query = 'INSERT INTO `'._DB_PREFIX_.'order_slip_detail`(`id_order_slip`, `id_order_detail`, `product_quantity`, `amount_tax_excl`, `amount_tax_incl`) VALUES ';

            foreach ($values as $value) {
                $query .= '('.(int) $this->id.', '.(int) $value['id_order_detail'].', '.(int) $value['product_quantity'].', '.
                    (isset($value['amount_tax_excl']) ? (float) $value['amount_tax_excl'] : 'NULL').', '.
                    (isset($value['amount_tax_incl']) ? (float) $value['amount_tax_incl'] : 'NULL').
                    '),';
            }
            $query = rtrim($query, ',');
            Db::getInstance()->execute($query);
        }

        return true;
    }
}
