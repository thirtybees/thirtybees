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
 * Class OrderInvoiceCore
 *
 * @since 1.0.0
 */
class OrderInvoiceCore extends ObjectModel
{
    const TAX_EXCL = 0;
    const TAX_INCL = 1;
    const DETAIL = 2;

    // @codingStandarsIgnoreStart
    /** @var int */
    public $id_order;

    /** @var int */
    public $number;

    /** @var int */
    public $delivery_number;

    /** @var int */
    public $delivery_date = '0000-00-00 00:00:00';

    /** @var float */
    public $total_discount_tax_excl;

    /** @var float */
    public $total_discount_tax_incl;

    /** @var float */
    public $total_paid_tax_excl;

    /** @var float */
    public $total_paid_tax_incl;

    /** @var float */
    public $total_products;

    /** @var float */
    public $total_products_wt;

    /** @var float */
    public $total_shipping_tax_excl;

    /** @var float */
    public $total_shipping_tax_incl;

    /** @var int */
    public $shipping_tax_computation_method;

    /** @var float */
    public $total_wrapping_tax_excl;

    /** @var float */
    public $total_wrapping_tax_incl;

    /** @var string shop address */
    public $shop_address;

    /** @var string invoice address */
    public $invoice_address;

    /** @var string delivery address */
    public $delivery_address;

    /** @var string note */
    public $note;

    /** @var int */
    public $date_add;

    /** @var array Total paid cache */
    protected static $_total_paid_cache = [];

    /** @var Order **/
    private $order;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'order_invoice',
        'primary' => 'id_order_invoice',
        'fields'  => [
            'id_order'                        => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',                   'required' => true],
            'number'                          => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',                   'required' => true],
            'delivery_number'                 => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                                      ],
            'delivery_date'                   => ['type' => self::TYPE_DATE,   'validate' => 'isDateFormat'                                      ],
            'total_discount_tax_excl'         => ['type' => self::TYPE_FLOAT                                                                     ],
            'total_discount_tax_incl'         => ['type' => self::TYPE_FLOAT                                                                     ],
            'total_paid_tax_excl'             => ['type' => self::TYPE_FLOAT                                                                     ],
            'total_paid_tax_incl'             => ['type' => self::TYPE_FLOAT                                                                     ],
            'total_products'                  => ['type' => self::TYPE_FLOAT                                                                     ],
            'total_products_wt'               => ['type' => self::TYPE_FLOAT                                                                     ],
            'total_shipping_tax_excl'         => ['type' => self::TYPE_FLOAT                                                                     ],
            'total_shipping_tax_incl'         => ['type' => self::TYPE_FLOAT                                                                     ],
            'shipping_tax_computation_method' => ['type' => self::TYPE_INT                                                                       ],
            'total_wrapping_tax_excl'         => ['type' => self::TYPE_FLOAT                                                                     ],
            'total_wrapping_tax_incl'         => ['type' => self::TYPE_FLOAT                                                                     ],
            'shop_address'                    => ['type' => self::TYPE_HTML,   'validate' => 'isCleanHtml', 'size' => 1000                       ],
            'invoice_address'                 => ['type' => self::TYPE_HTML,   'validate' => 'isCleanHtml', 'size' => 1000                       ],
            'delivery_address'                => ['type' => self::TYPE_HTML,   'validate' => 'isCleanHtml', 'size' => 1000                       ],
            'note'                            => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 65000                      ],
            'date_add'                        => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                                            ],
        ],
    ];

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false)
    {
        $order = new Order($this->id_order);

        $this->shop_address = static::getCurrentFormattedShopAddress($order->id_shop);

        return parent::add();
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductsDetail()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT *
		FROM `'._DB_PREFIX_.'order_detail` od
		LEFT JOIN `'._DB_PREFIX_.'product` p
		ON p.id_product = od.product_id
		LEFT JOIN `'._DB_PREFIX_.'product_shop` ps ON (ps.id_product = p.id_product AND ps.id_shop = od.id_shop)
		WHERE od.`id_order` = '.(int)$this->id_order.'
		'.($this->id && $this->number ? ' AND od.`id_order_invoice` = '.(int)$this->id : '').' ORDER BY od.`product_name`');
    }

    /**
     * @param $id_invoice
     *
     * @return bool|OrderInvoice
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getInvoiceByNumber($id_invoice)
    {
        if (is_numeric($id_invoice)) {
            $id_invoice = (int)$id_invoice;
        } elseif (is_string($id_invoice)) {
            $matches = [];
            if (preg_match('/^(?:'.Configuration::get('PS_INVOICE_PREFIX', Context::getContext()->language->id).')\s*([0-9]+)$/i', $id_invoice, $matches)) {
                $id_invoice = $matches[1];
            }
        }
        if (!$id_invoice) {
            return false;
        }

        $idOrderInvoice = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT `id_order_invoice`
			FROM `'._DB_PREFIX_.'order_invoice`
			WHERE number = '.(int)$id_invoice);

        return ($idOrderInvoice ? new OrderInvoice($idOrderInvoice) : false);
    }

    /**
     * Get order products
     *
     * @return array Products with price, quantity (with taxe and without)
     *
     *               @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProducts($products = false, $selectedProducts = false, $selectedQty = false)
    {
        if (!$products) {
            $products = $this->getProductsDetail();
        }

        $order = new Order($this->id_order);
        $customized_datas = Product::getAllCustomizedDatas($order->id_cart);

        $result_array = [];
        foreach ($products as $row) {
            // Change qty if selected
            if ($selectedQty) {
                $row['product_quantity'] = 0;
                foreach ($selectedProducts as $key => $id_product) {
                    if ($row['id_order_detail'] == $id_product) {
                        $row['product_quantity'] = (int) $selectedQty[$key];
                    }
                }
                if (!$row['product_quantity']) {
                    continue;
                }
            }

            $this->setProductImageInformations($row);
            $this->setProductCurrentStock($row);
            $this->setProductCustomizedDatas($row, $customized_datas);

            // Add information for virtual product
            if ($row['download_hash'] && !empty($row['download_hash'])) {
                $row['filename'] = ProductDownload::getFilenameFromIdProduct((int)$row['product_id']);
                // Get the display filename
                $row['display_filename'] = ProductDownload::getFilenameFromFilename($row['filename']);
            }

            $row['id_address_delivery'] = $order->id_address_delivery;

            /* Ecotax */
            $roundMode = $order->round_mode;

            $row['ecotax_tax_excl'] = $row['ecotax']; // alias for coherence
            $row['ecotax_tax_incl'] = $row['ecotax'] * (100 + $row['ecotax_tax_rate']) / 100;
            $row['ecotax_tax'] = $row['ecotax_tax_incl'] - $row['ecotax_tax_excl'];

            if ($roundMode == Order::ROUND_ITEM) {
                $row['ecotax_tax_incl'] = Tools::ps_round($row['ecotax_tax_incl'], _PS_PRICE_COMPUTE_PRECISION_, $roundMode);
            }

            $row['total_ecotax_tax_excl'] = $row['ecotax_tax_excl'] * $row['product_quantity'];
            $row['total_ecotax_tax_incl'] = $row['ecotax_tax_incl'] * $row['product_quantity'];

            $row['total_ecotax_tax'] = $row['total_ecotax_tax_incl'] - $row['total_ecotax_tax_excl'];

            foreach ([
                'ecotax_tax_excl',
                'ecotax_tax_incl',
                'ecotax_tax',
                'total_ecotax_tax_excl',
                'total_ecotax_tax_incl',
                'total_ecotax_tax'
                     ] as $ecotax_field) {
                $row[$ecotax_field] = Tools::ps_round($row[$ecotax_field], _PS_PRICE_COMPUTE_PRECISION_, $roundMode);
            }

            // Aliases
            $row['unit_price_tax_excl_including_ecotax'] = $row['unit_price_tax_excl'];
            $row['unit_price_tax_incl_including_ecotax'] = $row['unit_price_tax_incl'];
            $row['total_price_tax_excl_including_ecotax'] = $row['total_price_tax_excl'];
            $row['total_price_tax_incl_including_ecotax'] = $row['total_price_tax_incl'];

            /* Stock product */
            $result_array[(int)$row['id_order_detail']] = $row;
        }

        if ($customized_datas) {
            Product::addCustomizationPrice($result_array, $customized_datas);
        }

        return $result_array;
    }

    /**
     * @param $product
     * @param $customized_datas
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setProductCustomizedDatas(&$product, $customized_datas)
    {
        $product['customizedDatas'] = null;
        if (isset($customized_datas[$product['product_id']][$product['product_attribute_id']])) {
            $product['customizedDatas'] = $customized_datas[$product['product_id']][$product['product_attribute_id']];
        } else {
            $product['customizationQuantityTotal'] = 0;
        }
    }

    /**
     *
     * This method allow to add stock information on a product detail
     * @param array &$product
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setProductCurrentStock(&$product)
    {
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
            && (int)$product['advanced_stock_management'] == 1
            && (int)$product['id_warehouse'] > 0) {
            $product['current_stock'] = StockManagerFactory::getManager()->getProductPhysicalQuantities($product['product_id'], $product['product_attribute_id'], null, true);
        } else {
            $product['current_stock'] = '--';
        }
    }

    /**
     *
     * This method allow to add image information on a product detail
     * @param array &$product
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setProductImageInformations(&$product)
    {
        if (isset($product['product_attribute_id']) && $product['product_attribute_id']) {
            $id_image = Db::getInstance()->getValue('
				SELECT image_shop.id_image
				FROM '._DB_PREFIX_.'product_attribute_image pai'.
                Shop::addSqlAssociation('image', 'pai', true).'
				WHERE id_product_attribute = '.(int)$product['product_attribute_id']);
        }

        if (!isset($id_image) || !$id_image) {
            $id_image = Db::getInstance()->getValue('
				SELECT image_shop.id_image
				FROM '._DB_PREFIX_.'image i'.
                Shop::addSqlAssociation('image', 'i', true, 'image_shop.cover=1').'
				WHERE i.id_product = '.(int)$product['product_id']);
        }

        $product['image'] = null;
        $product['image_size'] = null;

        if ($id_image) {
            $product['image'] = new Image($id_image);
        }
    }

    /**
     * This method returns true if at least one order details uses the
     * One After Another tax computation method.
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function useOneAfterAnotherTaxComputationMethod()
    {
        // if one of the order details use the tax computation method the display will be different
        return Db::getInstance()->getValue('
		SELECT od.`tax_computation_method`
		FROM `'._DB_PREFIX_.'order_detail_tax` odt
		LEFT JOIN `'._DB_PREFIX_.'order_detail` od ON (od.`id_order_detail` = odt.`id_order_detail`)
		WHERE od.`id_order` = '.(int)$this->id_order.'
		AND od.`id_order_invoice` = '.(int)$this->id.'
		AND od.`tax_computation_method` = '.(int)TaxCalculator::ONE_AFTER_ANOTHER_METHOD)
        || Configuration::get('PS_INVOICE_TAXES_BREAKDOWN');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function displayTaxBasesInProductTaxesBreakdown()
    {
        return !$this->useOneAfterAnotherTaxComputationMethod();
    }

    /**
     * @return Order
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getOrder()
    {
        if (!$this->order) {
            $this->order = new Order($this->id_order);
        }

        return $this->order;
    }

    /**
     * @param null $order
     *
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductTaxesBreakdown($order = null)
    {
        if (!$order) {
            $order = $this->getOrder();
        }

        $sumCompositeTaxes = !$this->useOneAfterAnotherTaxComputationMethod();

        // $breakdown will be an array with tax rates as keys and at least the columns:
        // 	- 'total_price_tax_excl'
        // 	- 'total_amount'
        $breakdown = [];

        $details = $order->getProductTaxesDetails();

        if ($sumCompositeTaxes) {
            $groupedDetails = [];
            foreach ($details as $row) {
                if (!isset($groupedDetails[$row['id_order_detail']])) {
                    $groupedDetails[$row['id_order_detail']] = [
                        'tax_rate' => 0,
                        'total_tax_base' => 0,
                        'total_amount' => 0,
                        'id_tax' => $row['id_tax'],
                    ];
                }

                $groupedDetails[$row['id_order_detail']]['tax_rate'] += $row['tax_rate'];
                $groupedDetails[$row['id_order_detail']]['total_tax_base'] += $row['total_tax_base'];
                $groupedDetails[$row['id_order_detail']]['total_amount'] += $row['total_amount'];
            }

            $details = $groupedDetails;
        }

        foreach ($details as $row) {
            $rate = sprintf('%.3f', $row['tax_rate']);
            if (!isset($breakdown[$rate])) {
                $breakdown[$rate] = [
                    'total_price_tax_excl' => 0,
                    'total_amount' => 0,
                    'id_tax' => $row['id_tax'],
                    'rate' =>$rate,
                ];
            }

            $breakdown[$rate]['total_price_tax_excl'] += $row['total_tax_base'];
            $breakdown[$rate]['total_amount'] += $row['total_amount'];
        }

        foreach ($breakdown as $rate => $data) {
            $breakdown[$rate]['total_price_tax_excl'] = Tools::ps_round($data['total_price_tax_excl'], _PS_PRICE_COMPUTE_PRECISION_, $order->round_mode);
            $breakdown[$rate]['total_amount'] = Tools::ps_round($data['total_amount'], _PS_PRICE_COMPUTE_PRECISION_, $order->round_mode);
        }

        ksort($breakdown);

        return $breakdown;
    }

    /**
     * Returns the shipping taxes breakdown
     *
     * @param Order $order
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getShippingTaxesBreakdown(Order $order)
    {
        // No shipping breakdown if no shipping!
        if ($this->total_shipping_tax_excl == 0) {
            return [];
        }

        // No shipping breakdown if it's free!
        foreach ($order->getCartRules() as $cart_rule) {
            if ($cart_rule['free_shipping']) {
                return [];
            }
        }

        $shippingTaxAmount = $this->total_shipping_tax_incl - $this->total_shipping_tax_excl;

        if (Configuration::get('PS_INVOICE_TAXES_BREAKDOWN') || Configuration::get('PS_ATCP_SHIPWRAP')) {
            $shippingBreakdown = Db::getInstance()->executeS(
                'SELECT t.id_tax, t.rate, oit.amount as total_amount
				 FROM `'._DB_PREFIX_.'tax` t
				 INNER JOIN `'._DB_PREFIX_.'order_invoice_tax` oit ON oit.id_tax = t.id_tax
				 WHERE oit.type = "shipping" AND oit.id_order_invoice = '.(int)$this->id
            );

            $sumOfSplitTaxes = 0;
            $sumOfTaxBases = 0;
            foreach ($shippingBreakdown as &$row) {
                if (Configuration::get('PS_ATCP_SHIPWRAP')) {
                    $row['total_tax_excl'] = Tools::ps_round($row['total_amount'] / $row['rate'] * 100, _PS_PRICE_COMPUTE_PRECISION_, $this->getOrder()->round_mode);
                    $sumOfTaxBases += $row['total_tax_excl'];
                } else {
                    $row['total_tax_excl'] = $this->total_shipping_tax_excl;
                }

                $row['total_amount'] = Tools::ps_round($row['total_amount'], _PS_PRICE_COMPUTE_PRECISION_, $this->getOrder()->round_mode);
                $sumOfSplitTaxes += $row['total_amount'];
            }
            unset($row);

            $delta_amount = $shippingTaxAmount - $sumOfSplitTaxes;

            if ($delta_amount != 0) {
                Tools::spreadAmount($delta_amount, _PS_PRICE_COMPUTE_PRECISION_, $shippingBreakdown, 'total_amount');
            }

            $delta_base = $this->total_shipping_tax_excl - $sumOfTaxBases;

            if ($delta_base != 0) {
                Tools::spreadAmount($delta_base, _PS_PRICE_COMPUTE_PRECISION_, $shippingBreakdown, 'total_tax_excl');
            }
        } else {
            $shippingBreakdown = [
                [
                    'total_tax_excl' => $this->total_shipping_tax_excl,
                    'rate'           => $order->carrier_tax_rate,
                    'total_amount'   => $shippingTaxAmount,
                    'id_tax'         => null,
                ],
            ];
        }

        return $shippingBreakdown;
    }

    /**
     * Returns the wrapping taxes breakdown
     *
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWrappingTaxesBreakdown()
    {
        if ($this->total_wrapping_tax_excl == 0) {
            return [];
        }

        $wrappingTaxAmount = $this->total_wrapping_tax_incl - $this->total_wrapping_tax_excl;

        $wrappingBreakdown = Db::getInstance()->executeS(
            'SELECT t.id_tax, t.rate, oit.amount as total_amount
			FROM `'._DB_PREFIX_.'tax` t
			INNER JOIN `'._DB_PREFIX_.'order_invoice_tax` oit ON oit.id_tax = t.id_tax
			WHERE oit.type = "wrapping" AND oit.id_order_invoice = '.(int)$this->id
        );

        $sumOfSplitTaxes = 0;
        $sumOfTaxBases = 0;
        $totalTaxRate = 0;
        foreach ($wrappingBreakdown as &$row) {
            if (Configuration::get('PS_ATCP_SHIPWRAP')) {
                $row['total_tax_excl'] = Tools::ps_round($row['total_amount'] / $row['rate'] * 100, _PS_PRICE_COMPUTE_PRECISION_, $this->getOrder()->round_mode);
                $sumOfTaxBases += $row['total_tax_excl'];
            } else {
                $row['total_tax_excl'] = $this->total_wrapping_tax_excl;
            }

            $row['total_amount'] = Tools::ps_round($row['total_amount'], _PS_PRICE_COMPUTE_PRECISION_, $this->getOrder()->round_mode);
            $sumOfSplitTaxes += $row['total_amount'];
            $totalTaxRate += (float)$row['rate'];
        }
        unset($row);

        $deltaAmount = $wrappingTaxAmount - $sumOfSplitTaxes;

        if ($deltaAmount != 0) {
            Tools::spreadAmount($deltaAmount, _PS_PRICE_COMPUTE_PRECISION_, $wrappingBreakdown, 'total_amount');
        }

        $deltaBase = $this->total_wrapping_tax_excl - $sumOfTaxBases;

        if ($deltaBase != 0) {
            Tools::spreadAmount($deltaBase, _PS_PRICE_COMPUTE_PRECISION_, $wrappingBreakdown, 'total_tax_excl');
        }

        if (!Configuration::get('PS_INVOICE_TAXES_BREAKDOWN') && !Configuration::get('PS_ATCP_SHIPWRAP')) {
            $wrappingBreakdown = [
                [
                    'total_tax_excl' => $this->total_wrapping_tax_excl,
                    'rate' => $totalTaxRate,
                    'total_amount' => $wrappingTaxAmount,
                ]
            ];
        }

        return $wrappingBreakdown;
    }

    /**
     * Returns the ecotax taxes breakdown
     *
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getEcoTaxTaxesBreakdown()
    {
        $result = Db::getInstance()->executeS('
		SELECT `ecotax_tax_rate` as `rate`, SUM(`ecotax` * `product_quantity`) as `ecotax_tax_excl`, SUM(`ecotax` * `product_quantity`) as `ecotax_tax_incl`
		FROM `'._DB_PREFIX_.'order_detail`
		WHERE `id_order` = '.(int)$this->id_order.'
		AND `id_order_invoice` = '.(int)$this->id.'
		GROUP BY `ecotax_tax_rate`');

        $taxes = [];
        foreach ($result as $row) {
            if ($row['ecotax_tax_excl'] > 0) {
                $row['ecotax_tax_incl'] = Tools::ps_round($row['ecotax_tax_excl'] + ($row['ecotax_tax_excl'] * $row['rate'] / 100), _PS_PRICE_DISPLAY_PRECISION_);
                $row['ecotax_tax_excl'] = Tools::ps_round($row['ecotax_tax_excl'], _PS_PRICE_DISPLAY_PRECISION_);
                $taxes[] = $row;
            }
        }
        return $taxes;
    }

    /**
     * Returns all the order invoice that match the date interval
     *
     * @param $dateFrom
     * @param $dateTo
     *
     * @return array collection of OrderInvoice
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getByDateInterval($dateFrom, $dateTo)
    {
        $order_invoice_list = Db::getInstance()->executeS('
			SELECT oi.*
			FROM `'._DB_PREFIX_.'order_invoice` oi
			LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = oi.`id_order`)
			WHERE DATE_ADD(oi.date_add, INTERVAL -1 DAY) <= \''.pSQL($dateTo).'\'
			AND oi.date_add >= \''.pSQL($dateFrom).'\'
			'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
			AND oi.number > 0
			ORDER BY oi.date_add ASC
		');

        return ObjectModel::hydrateCollection('OrderInvoice', $order_invoice_list);
    }

    /**
     * @param $idOrderState
     *
     * @return array collection of OrderInvoice
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getByStatus($idOrderState)
    {
        $order_invoice_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT oi.*
			FROM `'._DB_PREFIX_.'order_invoice` oi
			LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = oi.`id_order`)
			WHERE '.(int) $idOrderState.' = o.current_state
			'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
			AND oi.number > 0
			ORDER BY oi.`date_add` ASC
		');

        return ObjectModel::hydrateCollection('OrderInvoice', $order_invoice_list);
    }

    /**
     * @param $dateFrom
     * @param $dateTo
     *
     * @return array collection of invoice
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getByDeliveryDateInterval($dateFrom, $dateTo)
    {
        $order_invoice_list = Db::getInstance()->executeS('
			SELECT oi.*
			FROM `'._DB_PREFIX_.'order_invoice` oi
			LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = oi.`id_order`)
			WHERE DATE_ADD(oi.delivery_date, INTERVAL -1 DAY) <= \''.pSQL($dateTo).'\'
			AND oi.delivery_date >= \''.pSQL($dateFrom).'\'
			'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
			ORDER BY oi.delivery_date ASC
		');

        return ObjectModel::hydrateCollection('OrderInvoice', $order_invoice_list);
    }

    /**
     * @param $idOrderInvoice
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCarrier($idOrderInvoice)
    {
        $carrier = false;
        if ($idCarrier = static::getCarrierId($idOrderInvoice)) {
            $carrier = new Carrier((int) $idCarrier);
        }

        return $carrier;
    }

    /**
     * @param $idOrderInvoice
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCarrierId($idOrderInvoice)
    {
        $sql = 'SELECT `id_carrier`
				FROM `'._DB_PREFIX_.'order_carrier`
				WHERE `id_order_invoice` = '.(int) $idOrderInvoice;

        return Db::getInstance()->getValue($sql);
    }

    /**
     * @param int $id
     * @return OrderInvoice
     * @throws PrestaShopException
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function retrieveOneById($id)
    {
        $orderInvoice = new OrderInvoice($id);
        if (!Validate::isLoadedObject($orderInvoice)) {
            throw new PrestaShopException('Can\'t load Order Invoice object for id: '.$id);
        }

        return $orderInvoice;
    }

    /**
     * Amounts of payments
     *
     * @return float Total paid
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTotalPaid()
    {
        $cacheId = 'order_invoice_paid_'.(int) $this->id;
        if (!Cache::isStored($cacheId)) {
            $amount = 0;
            $payments = OrderPayment::getByInvoiceId($this->id);
            foreach ($payments as $payment) {
                /** @var OrderPayment $payment */
                $amount += $payment->amount;
            }
            Cache::store($cacheId, $amount);
            return $amount;
        }
        return Cache::retrieve($cacheId);
    }

    /**
     * Rest Paid
     *
     * @return float Rest Paid
     */
    public function getRestPaid()
    {
        return round($this->total_paid_tax_incl + $this->getSiblingTotal() - $this->getTotalPaid(), 2);
    }

    /**
     * Return collection of order invoice object linked to the payments of the current order invoice object
     *
     * @return PrestaShopCollection|array Collection of OrderInvoice or empty array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getSibling()
    {
        $query = new DbQuery();
        $query->select('oip2.id_order_invoice');
        $query->from('order_invoice_payment', 'oip1');
        $query->innerJoin(
            'order_invoice_payment',
            'oip2',
            'oip2.id_order_payment = oip1.id_order_payment AND oip2.id_order_invoice <> oip1.id_order_invoice AND oip1.id_order = oip2.id_order'
        );
        $query->where('oip1.id_order_invoice = '.$this->id);

        $invoices = Db::getInstance()->executeS($query);
        if (!$invoices) {
            return [];
        }

        $invoice_list = [];
        foreach ($invoices as $invoice) {
            $invoice_list[] = $invoice['id_order_invoice'];
        }

        $payments = new PrestaShopCollection('OrderInvoice');
        $payments->where('id_order_invoice', 'IN', $invoice_list);

        return $payments;
    }


    /**
     * Return total to paid of sibling invoices
     *
     * @param int $mod TAX_EXCL, TAX_INCL, DETAIL
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getSiblingTotal($mod = self::TAX_INCL)
    {
        $query = new DbQuery();
        $query->select('SUM(oi.total_paid_tax_incl) as total_paid_tax_incl, SUM(oi.total_paid_tax_excl) as total_paid_tax_excl');
        $query->from('order_invoice_payment', 'oip1');
        $query->innerJoin(
            'order_invoice_payment',
            'oip2',
            'oip2.id_order_payment = oip1.id_order_payment AND oip2.id_order_invoice <> oip1.id_order_invoice AND oip1.id_order = oip2.id_order'
        );
        $query->leftJoin(
            'order_invoice',
            'oi',
            'oi.id_order_invoice = oip2.id_order_invoice'
        );
        $query->where('oip1.id_order_invoice = '.$this->id);

        $row = Db::getInstance()->getRow($query);

        switch ($mod) {
            case static::TAX_EXCL:
                return $row['total_paid_tax_excl'];
            case static::TAX_INCL:
                return $row['total_paid_tax_incl'];
            default:
                return $row;
        }
    }

    /**
     * Get global rest to paid
     *    This method will return something different of the method getRestPaid if
     *    there is an other invoice linked to the payments of the current invoice
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getGlobalRestPaid()
    {
        static $cache;

        if (!isset($cache[$this->id])) {
            $res = Db::getInstance()->getRow('
			SELECT SUM(sub.paid) paid, SUM(sub.to_paid) to_paid
			FROM (
				SELECT
					op.amount as paid, SUM(oi.total_paid_tax_incl) to_paid
				FROM `'._DB_PREFIX_.'order_invoice_payment` oip1
				INNER JOIN `'._DB_PREFIX_.'order_invoice_payment` oip2
					ON oip2.id_order_payment = oip1.id_order_payment
				INNER JOIN `'._DB_PREFIX_.'order_invoice` oi
					ON oi.id_order_invoice = oip2.id_order_invoice
				INNER JOIN `'._DB_PREFIX_.'order_payment` op
					ON op.id_order_payment = oip2.id_order_payment
				WHERE oip1.id_order_invoice = '.(int)$this->id.'
				GROUP BY op.id_order_payment
			) sub');
            $cache[$this->id] = round($res['to_paid'] - $res['paid'], 2);
        }

        return $cache[$this->id];
    }

    /**
     * @return bool Is paid ?
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function isPaid()
    {
        return $this->getTotalPaid() == $this->total_paid_tax_incl;
    }

    /**
     * @return PrestaShopCollection Collection of Order payment
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getOrderPaymentCollection()
    {
        return OrderPayment::getByInvoiceId($this->id);
    }

    /**
     * Get the formatted number of invoice
     *
     * @param int $idLang for invoice_prefix
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getInvoiceNumberFormatted($idLang, $idShop = null)
    {
        $invoice_formatted_number = Hook::exec('actionInvoiceNumberFormatted', [
                get_class($this) => $this,
                'id_lang' => (int) $idLang,
                'id_shop' => (int)$idShop,
                'number' => (int)$this->number
        ]
        );

        if (!empty($invoice_formatted_number)) {
            return $invoice_formatted_number;
        }

        $format = '%1$s%2$06d';

        if (Configuration::get('PS_INVOICE_USE_YEAR')) {
            $format = Configuration::get('PS_INVOICE_YEAR_POS') ? '%1$s%3$s/%2$06d' : '%1$s%2$06d/%3$s';
        }

        return sprintf($format, Configuration::get('PS_INVOICE_PREFIX', (int) $idLang, null, (int)$idShop), $this->number, date('Y', strtotime($this->date_add)));
    }

    /**
     * @param array $taxesAmount
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function saveCarrierTaxCalculator(array $taxesAmount)
    {
        $is_correct = true;
        foreach ($taxesAmount as $idTax => $amount) {
            $sql = 'INSERT INTO `'._DB_PREFIX_.'order_invoice_tax` (`id_order_invoice`, `type`, `id_tax`, `amount`)
					VALUES ('.(int) $this->id.', \'shipping\', '.(int) $idTax.', '.(float) $amount.')';

            $is_correct &= Db::getInstance()->execute($sql);
        }

        return $is_correct;
    }

    /**
     * @param array $taxesAmount
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function saveWrappingTaxCalculator(array $taxesAmount)
    {
        $is_correct = true;
        foreach ($taxesAmount as $id_tax => $amount) {
            $sql = 'INSERT INTO `'._DB_PREFIX_.'order_invoice_tax` (`id_order_invoice`, `type`, `id_tax`, `amount`)
					VALUES ('.(int)$this->id.', \'wrapping\', '.(int)$id_tax.', '.(float)$amount.')';

            $is_correct &= Db::getInstance()->execute($sql);
        }

        return $is_correct;
    }

    /**
     * @param null $idShop
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCurrentFormattedShopAddress($idShop = null)
    {
        $address = new Address();
        $address->company = Configuration::get('PS_SHOP_NAME', null, null, $idShop);
        $address->address1 = Configuration::get('PS_SHOP_ADDR1', null, null, $idShop);
        $address->address2 = Configuration::get('PS_SHOP_ADDR2', null, null, $idShop);
        $address->postcode = Configuration::get('PS_SHOP_CODE', null, null, $idShop);
        $address->city = Configuration::get('PS_SHOP_CITY', null, null, $idShop);
        $address->phone = Configuration::get('PS_SHOP_PHONE', null, null, $idShop);
        $address->id_country = Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $idShop);
        $address->id_state = Configuration::get('PS_SHOP_STATE_ID', null, null, $idShop);

        return AddressFormat::generateAddress($address, [], '<br />', ' ');
    }

    /**
     * This method is used to fix shop addresses that cannot be fixed during upgrade process
     * (because uses the whole environnement of PS classes that is not available during upgrade).
     * This method should execute once on an upgraded PrestaShop to fix all OrderInvoices in one shot.
     * This method is triggered once during a (non bulk) creation of a PDF from an OrderInvoice that is not fixed yet.
     *
     * @since PS 1.6.1.1
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function fixAllShopAddresses()
    {
        $shopIds = Shop::getShops(false, null, true);
        $db = Db::getInstance();
        foreach ($shopIds as $idShop) {
            $address = static::getCurrentFormattedShopAddress($idShop);
            $escapedAddress = $db->escape($address, true, true);

            $db->execute('UPDATE `'._DB_PREFIX_.'order_invoice` INNER JOIN `'._DB_PREFIX_.'orders` USING (`id_order`)
                SET `shop_address` = \''.$escapedAddress.'\' WHERE `shop_address` IS NULL AND `id_shop` = '.$idShop);
        }
    }
}
