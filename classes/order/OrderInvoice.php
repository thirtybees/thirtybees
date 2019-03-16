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
 * Class OrderInvoiceCore
 *
 * @since 1.0.0
 */
class OrderInvoiceCore extends ObjectModel
{
    const TAX_EXCL = 0;
    const TAX_INCL = 1;
    const DETAIL = 2;

    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'order_invoice',
        'primary' => 'id_order_invoice',
        'fields'  => [
            'id_order'                        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'number'                          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'delivery_number'                 => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'delivery_date'                   => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'total_discount_tax_excl'         => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'total_discount_tax_incl'         => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'total_paid_tax_excl'             => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'total_paid_tax_incl'             => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'total_products'                  => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'total_products_wt'               => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'total_shipping_tax_excl'         => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'total_shipping_tax_incl'         => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'shipping_tax_computation_method' => ['type' => self::TYPE_INT],
            'total_wrapping_tax_excl'         => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'total_wrapping_tax_incl'         => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'shop_address'                    => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'size' => 1000],
            'invoice_address'                 => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'size' => 1000],
            'delivery_address'                => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'size' => 1000],
            'note'                            => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 65000],
            'date_add'                        => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
    /** @var array Total paid cache */
    protected static $_total_paid_cache = [];
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
    /** @var Order * */
    private $order;
    // @codingStandardsIgnoreEnd

    /**
     * @param int $idInvoice
     *
     * @return bool|OrderInvoice
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getInvoiceByNumber($idInvoice)
    {
        if (is_numeric($idInvoice)) {
            $idInvoice = (int) $idInvoice;
        } elseif (is_string($idInvoice)) {
            $matches = [];
            if (preg_match('/^(?:'.Configuration::get('PS_INVOICE_PREFIX', Context::getContext()->language->id).')\s*([0-9]+)$/i', $idInvoice, $matches)) {
                $idInvoice = $matches[1];
            }
        }
        if (!$idInvoice) {
            return false;
        }

        $idOrderInvoice = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_order_invoice`')
                ->from('order_invoice')
                ->where('`number` = '.(int) $idInvoice)
        );

        return ($idOrderInvoice ? new OrderInvoice($idOrderInvoice) : false);
    }

    /**
     * Returns all the order invoice that match the date interval
     *
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return static[] collection of OrderInvoice
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getByDateInterval($dateFrom, $dateTo)
    {
        $orderInvoiceList = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('oi.*')
                ->from('order_invoice', 'oi')
                ->leftJoin('orders', 'o', 'o.`id_order` = oi.`id_order`')
                ->where('DATE_ADD(oi.`date_add`, INTERVAL -1 DAY) <= \''.pSQL($dateTo).'\'')
                ->where('oi.`date_add` >= \''.pSQL($dateFrom).'\' '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o'))
                ->where('oi.`number` > 0')
                ->orderBy('oi.`date_add` ASC')
        );

        return ObjectModel::hydrateCollection(__CLASS__, $orderInvoiceList);
    }

    /**
     * @param int $idOrderState
     *
     * @return static[] collection of OrderInvoice
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getByStatus($idOrderState)
    {
        $orderInvoiceList = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('oi.*')
                ->from('order_invoice', 'oi')
                ->leftJoin('orders', 'o', 'o.`id_order` = oi.`id_order`')
                ->where('o.`current_state` = '.(int) $idOrderState.' '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o'))
                ->where('oi.`number` > 0')
                ->orderBy('oi.`date_add` ASC')
        );

        return ObjectModel::hydrateCollection(__CLASS__, $orderInvoiceList);
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return static[] collection of invoice
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getByDeliveryDateInterval($dateFrom, $dateTo)
    {
        $orderInvoiceList = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('oi.*')
                ->from('order_invoice', 'oi')
                ->leftJoin('orders', 'o', 'o.`id_order` = oi.`id_order`')
                ->where('DATE_ADD(oi.`delivery_date`, INTERVAL -1 DAY) <= \''.pSQL($dateTo).'\'')
                ->where('oi.`delivery_date` >= \''.pSQL($dateFrom).'\' '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o'))
                ->orderBy('oi.`delivery_date` ASC')
        );

        return ObjectModel::hydrateCollection(__CLASS__, $orderInvoiceList);
    }

    /**
     * @param int $idOrderInvoice
     *
     * @return Carrier
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
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
     * @param int $idOrderInvoice
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getCarrierId($idOrderInvoice)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_carrier`')
                ->from('order_carrier')
                ->where('`id_order_invoice` = '.(int) $idOrderInvoice)
        );
    }

    /**
     * @param int $id
     *
     * @return OrderInvoice
     * @throws PrestaShopException
     *
     * @since   1.0.0
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
     * This method is used to fix shop addresses that cannot be fixed during upgrade process
     * (because uses the whole environnement of PS classes that is not available during upgrade).
     * This method should execute once on an upgraded PrestaShop to fix all OrderInvoices in one shot.
     * This method is triggered once during a (non bulk) creation of a PDF from an OrderInvoice that is not fixed yet.
     *
     * @since   PS 1.6.1.1
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function fixAllShopAddresses()
    {
        $shopIds = Shop::getShops(false, null, true);
        $db = Db::getInstance();
        foreach ($shopIds as $idShop) {
            $address = static::getCurrentFormattedShopAddress($idShop);
            $escapedAddress = $db->escape($address, true, true);

            $db->execute(
                'UPDATE `'._DB_PREFIX_.'order_invoice` INNER JOIN `'._DB_PREFIX_.'orders` USING (`id_order`)
                SET `shop_address` = \''.$escapedAddress.'\' WHERE `shop_address` IS NULL AND `id_shop` = '.$idShop
            );
        }
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        $order = new Order($this->id_order);

        $this->shop_address = static::getCurrentFormattedShopAddress($order->id_shop);

        return parent::add();
    }

    /**
     * @param int|null $idShop
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
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
     * Get order products
     *
     * @param array|bool $products
     * @param array|bool $selectedProducts
     * @param int|bool   $selectedQty
     *
     * @return array Products with price, quantity (with taxe and without)
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProducts($products = false, $selectedProducts = false, $selectedQty = false)
    {
        if (!$products) {
            $products = $this->getProductsDetail();
        }

        $order = new Order($this->id_order);
        $customizedData = Product::getAllCustomizedDatas($order->id_cart);

        $resultArray = [];
        foreach ($products as $row) {
            // Change qty if selected
            if ($selectedQty) {
                $row['product_quantity'] = 0;
                foreach ($selectedProducts as $key => $idProduct) {
                    if ($row['id_order_detail'] == $idProduct) {
                        $row['product_quantity'] = (int) $selectedQty[$key];
                    }
                }
                if (!$row['product_quantity']) {
                    continue;
                }
            }

            $this->setProductImageInformations($row);
            $this->setProductCurrentStock($row);
            $this->setProductCustomizedDatas($row, $customizedData);

            // Add information for virtual product
            if ($row['download_hash'] && !empty($row['download_hash'])) {
                $row['filename'] = ProductDownload::getFilenameFromIdProduct((int) $row['product_id']);
                // Get the display filename
                $row['display_filename'] = ProductDownload::getFilenameFromFilename($row['filename']);
            }

            $row['id_address_delivery'] = $order->id_address_delivery;

            /* Ecotax */
            $row['ecotax_tax_excl'] = round(
                $row['ecotax'],
                _TB_PRICE_DATABASE_PRECISION_
            );
            $row['ecotax_tax_incl'] = round(
                $row['ecotax'] * (1 + $row['ecotax_tax_rate'] / 100),
                _TB_PRICE_DATABASE_PRECISION_
            );
            $row['ecotax_tax']
                = $row['ecotax_tax_incl'] - $row['ecotax_tax_excl'];

            $row['total_ecotax_tax_excl']
                = $row['ecotax_tax_excl'] * $row['product_quantity'];
            $row['total_ecotax_tax_incl']
                = $row['ecotax_tax_incl'] * $row['product_quantity'];
            $row['total_ecotax_tax']
                = $row['total_ecotax_tax_incl'] - $row['total_ecotax_tax_excl'];

            // Aliases
            $row['unit_price_tax_excl_including_ecotax'] = $row['unit_price_tax_excl'];
            $row['unit_price_tax_incl_including_ecotax'] = $row['unit_price_tax_incl'];
            $row['total_price_tax_excl_including_ecotax'] = $row['total_price_tax_excl'];
            $row['total_price_tax_incl_including_ecotax'] = $row['total_price_tax_incl'];

            /* Stock product */
            $resultArray[(int) $row['id_order_detail']] = $row;
        }

        if ($customizedData) {
            Product::addCustomizationPrice($resultArray, $customizedData);
        }

        return $resultArray;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductsDetail()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('order_detail', 'od')
                ->leftJoin('product', 'p', 'p.`id_product` = od.`product_id`')
                ->leftJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = od.id_shop')
                ->where('od.`id_order` = '.(int) $this->id_order)
                ->where($this->id && $this->number ? 'od.`id_order_invoice` = '.(int) $this->id : '')
                ->orderBy('od.`product_name`')
        );
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function displayTaxBasesInProductTaxesBreakdown()
    {
        return !$this->useOneAfterAnotherTaxComputationMethod();
    }

    /**
     * This method returns true if at least one order details uses the
     * One After Another tax computation method.
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function useOneAfterAnotherTaxComputationMethod()
    {
        // if one of the order details use the tax computation method the display will be different
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('od.`tax_computation_method`')
                ->from('order_detail_tax', 'odt')
                ->leftJoin('order_detail', 'od', 'od.`id_order_detail` = odt.`id_order_detail`')
                ->where('od.`id_order` = '.(int) $this->id_order)
                ->where('od.`id_order_invoice` = '.(int) $this->id)
                ->where('od.`tax_computation_method` = '.(int) TaxCalculator::ONE_AFTER_ANOTHER_METHOD)
        ) || Configuration::get('PS_INVOICE_TAXES_BREAKDOWN');
    }

    /**
     * @param Order|null $order
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
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
                        'tax_rate'       => 0,
                        'total_tax_base' => 0,
                        'total_amount'   => 0,
                        'id_tax'         => $row['id_tax'],
                    ];
                }

                $groupedDetails[$row['id_order_detail']]['tax_rate'] += $row['tax_rate'];
                $groupedDetails[$row['id_order_detail']]['total_tax_base'] += $row['total_tax_base'];
                $groupedDetails[$row['id_order_detail']]['total_amount'] += $row['total_amount'];
            }

            $details = $groupedDetails;
        }

        foreach ($details as $row) {
            $rate = (float) round($row['tax_rate'], 3);
            if (!isset($breakdown[$rate])) {
                $breakdown[$rate] = [
                    'total_price_tax_excl' => 0,
                    'total_amount'         => 0,
                    'id_tax'               => $row['id_tax'],
                    'rate'                 => $rate,
                ];
            }

            $breakdown[$rate]['total_price_tax_excl'] += $row['total_tax_base'];
            $breakdown[$rate]['total_amount'] += $row['total_amount'];
        }

        foreach ($breakdown as $rate => $data) {
            $breakdown[$rate]['total_price_tax_excl']
                = $data['total_price_tax_excl'];
            $breakdown[$rate]['total_amount'] = $data['total_amount'];
        }

        ksort($breakdown);

        return $breakdown;
    }

    /**
     * @return Order
     *
     * @since   1.0.0
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
     * Returns the shipping taxes breakdown
     *
     * @param Order $order
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getShippingTaxesBreakdown(Order $order)
    {
        // No shipping breakdown if no shipping!
        if ($this->total_shipping_tax_excl == 0) {
            return [];
        }

        // No shipping breakdown if it's free!
        foreach ($order->getCartRules() as $cartRule) {
            if ($cartRule['free_shipping']) {
                return [];
            }
        }

        $shippingTaxAmount = $this->total_shipping_tax_incl - $this->total_shipping_tax_excl;

        if (Configuration::get('PS_INVOICE_TAXES_BREAKDOWN') || Configuration::get('PS_ATCP_SHIPWRAP')) {
            $shippingBreakdown = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('t.`id_tax`, t.`rate`, oit.`amount` AS `total_amount`')
                    ->from('tax', 't')
                    ->innerJoin('order_invoice_tax', 'oit', 'oit.`id_tax` = t.`id_tax`')
                    ->where('oit.`type` = "shipping"')
                    ->where('oit.`id_order_invoice` = '.(int) $this->id)
            );

            $sumOfSplitTaxes = 0;
            $sumOfTaxBases = 0;
            foreach ($shippingBreakdown as &$row) {
                if (Configuration::get('PS_ATCP_SHIPWRAP')) {
                    $row['total_tax_excl'] = round(
                        $row['total_amount'] / $row['rate'] * 100,
                        _TB_PRICE_DATABASE_PRECISION_
                    );
                    $sumOfTaxBases += $row['total_tax_excl'];
                } else {
                    $row['total_tax_excl'] = $this->total_shipping_tax_excl;
                }

                $row['total_amount'] = round(
                    $row['total_amount'],
                    _TB_PRICE_DATABASE_PRECISION_
                );
                $sumOfSplitTaxes += $row['total_amount'];
            }
            unset($row);

            $deltaAmount = $shippingTaxAmount - $sumOfSplitTaxes;

            if ($deltaAmount != 0) {
                Tools::spreadAmount($deltaAmount, _TB_PRICE_DATABASE_PRECISION_, $shippingBreakdown, 'total_amount');
            }

            $deltaBase = $this->total_shipping_tax_excl - $sumOfTaxBases;

            if ($deltaBase != 0) {
                Tools::spreadAmount($deltaBase, _TB_PRICE_DATABASE_PRECISION_, $shippingBreakdown, 'total_tax_excl');
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWrappingTaxesBreakdown()
    {
        if ($this->total_wrapping_tax_excl == 0) {
            return [];
        }

        $wrappingTaxAmount = $this->total_wrapping_tax_incl - $this->total_wrapping_tax_excl;

        $wrappingBreakdown = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.`id_tax`, t.`rate`, oit.`amount` AS `total_amount`')
                ->from('tax', 't')
                ->innerJoin('order_invoice_tax', 'oit', 'oit.`id_tax` = t.`id_tax`')
                ->where('oit.`type` = "wrapping"')
                ->where('oit.`id_order_invoice` = '.(int) $this->id)
        );

        $sumOfSplitTaxes = 0;
        $sumOfTaxBases = 0;
        $totalTaxRate = 0;
        foreach ($wrappingBreakdown as &$row) {
            if (Configuration::get('PS_ATCP_SHIPWRAP')) {
                $row['total_tax_excl'] = round(
                    $row['total_amount'] / $row['rate'] * 100,
                    _TB_PRICE_DATABASE_PRECISION_
                );
                $sumOfTaxBases += $row['total_tax_excl'];
            } else {
                $row['total_tax_excl'] = $this->total_wrapping_tax_excl;
            }

            $row['total_amount'] = round(
                $row['total_amount'],
                _TB_PRICE_DATABASE_PRECISION_
            );
            $sumOfSplitTaxes += $row['total_amount'];
            $totalTaxRate += (float) $row['rate'];
        }
        unset($row);

        $deltaAmount = $wrappingTaxAmount - $sumOfSplitTaxes;

        if ($deltaAmount != 0) {
            Tools::spreadAmount($deltaAmount, _TB_PRICE_DATABASE_PRECISION_, $wrappingBreakdown, 'total_amount');
        }

        $deltaBase = $this->total_wrapping_tax_excl - $sumOfTaxBases;

        if ($deltaBase != 0) {
            Tools::spreadAmount($deltaBase, _TB_PRICE_DATABASE_PRECISION_, $wrappingBreakdown, 'total_tax_excl');
        }

        if (!Configuration::get('PS_INVOICE_TAXES_BREAKDOWN') && !Configuration::get('PS_ATCP_SHIPWRAP')) {
            $wrappingBreakdown = [
                [
                    'total_tax_excl' => $this->total_wrapping_tax_excl,
                    'rate'           => $totalTaxRate,
                    'total_amount'   => $wrappingTaxAmount,
                ],
            ];
        }

        return $wrappingBreakdown;
    }

    /**
     * Returns the ecotax taxes breakdown
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getEcoTaxTaxesBreakdown()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`ecotax_tax_rate` AS `rate`, `ecotax` AS `ecotax_tax_excl`, `product_quantity`')
                ->from('order_detail')
                ->where('`id_order` = '.(int) $this->id_order)
                ->where('`id_order_invoice` = '.(int) $this->id)
        );

        $taxes = [];
        foreach ($result as $row) {
            if ($row['ecotax_tax_excl'] > 0) {
                $row['ecotax_tax_incl']= round(
                    $row['ecotax_tax_excl'] * (1 + $row['rate'] / 100),
                    _TB_PRICE_DATABASE_PRECISION_
                );

                $row['ecotax_tax_excl'] *= $row['product_quantity'];
                $row['ecotax_tax_incl'] *= $row['product_quantity'];

                if (isset($taxes[$row['rate']])) {
                    $oldRow = $taxes[$row['rate']];
                    $oldRow['ecotax_tax_excl'] += $row['ecotax_tax_excl'];
                    $oldRow['ecotax_tax_incl'] += $row['ecotax_tax_incl'];
                } else {
                    $taxes[$row['rate']] = $row;
                }
            }
        }

        return array_values($taxes);
    }

    /**
     * Rest Paid
     *
     * @return float Rest Paid
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getRestPaid()
    {
        return $this->total_paid_tax_incl
               + $this->getSiblingTotal()
               - $this->getTotalPaid();
    }

    /**
     * Return total to paid of sibling invoices
     *
     * @param int $mod TAX_EXCL, TAX_INCL, DETAIL
     *
     * @return array|bool|null|object
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
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
     * Amounts of payments
     *
     * @return float Total paid
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
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
     * Return collection of order invoice object linked to the payments of the current order invoice object
     *
     * @return PrestaShopCollection|array Collection of OrderInvoice or empty array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
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

        $invoiceList = [];
        foreach ($invoices as $invoice) {
            $invoiceList[] = $invoice['id_order_invoice'];
        }

        $payments = new PrestaShopCollection('OrderInvoice');
        $payments->where('id_order_invoice', 'IN', $invoiceList);

        return $payments;
    }

    /**
     * Get global rest to paid
     *    This method will return something different of the method getRestPaid if
     *    there is an other invoice linked to the payments of the current invoice
     *
     * @return float
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getGlobalRestPaid()
    {
        static $cache;

        if (!isset($cache[$this->id])) {
            $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                'SELECT SUM(sub.paid) paid, SUM(sub.to_paid) to_paid
			FROM (
				SELECT
					op.amount AS paid, SUM(oi.total_paid_tax_incl) to_paid
				FROM `'._DB_PREFIX_.'order_invoice_payment` oip1
				INNER JOIN `'._DB_PREFIX_.'order_invoice_payment` oip2
					ON oip2.id_order_payment = oip1.id_order_payment
				INNER JOIN `'._DB_PREFIX_.'order_invoice` oi
					ON oi.id_order_invoice = oip2.id_order_invoice
				INNER JOIN `'._DB_PREFIX_.'order_payment` op
					ON op.id_order_payment = oip2.id_order_payment
				WHERE oip1.id_order_invoice = '.(int) $this->id.'
				GROUP BY op.id_order_payment
			) sub'
            );
            $cache[$this->id] = $res['to_paid'] - $res['paid'];
        }

        return $cache[$this->id];
    }

    /**
     * @return bool Is paid ?
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function isPaid()
    {
        return (string) round(
            $this->getTotalPaid(),
            _TB_PRICE_DATABASE_PRECISION_
        ) === (string) round(
            $this->total_paid_tax_incl,
            _TB_PRICE_DATABASE_PRECISION_
        );
    }

    /**
     * @return PrestaShopCollection Collection of Order payment
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getOrderPaymentCollection()
    {
        return OrderPayment::getByInvoiceId($this->id);
    }

    /**
     * Get the formatted number of invoice
     *
     * @param int      $idLang for invoice_prefix
     * @param int|null $idShop
     *
     * @return string
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getInvoiceNumberFormatted($idLang, $idShop = null)
    {
        $invoiceFormattedNumber = Hook::exec(
            'actionInvoiceNumberFormatted',
            [
                get_class($this) => $this,
                'id_lang'        => (int) $idLang,
                'id_shop'        => (int) $idShop,
                'number'         => (int) $this->number,
            ]
        );

        if (!empty($invoiceFormattedNumber)) {
            return $invoiceFormattedNumber;
        }

        $format = '%1$s%2$06d';

        if (Configuration::get('PS_INVOICE_USE_YEAR')) {
            $format = Configuration::get('PS_INVOICE_YEAR_POS') ? '%1$s%3$s/%2$06d' : '%1$s%2$06d/%3$s';
        }

        return sprintf($format, Configuration::get('PS_INVOICE_PREFIX', (int) $idLang, null, (int) $idShop), $this->number, date('Y', strtotime($this->date_add)));
    }

    /**
     * @param array $taxesAmount
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function saveCarrierTaxCalculator(array $taxesAmount)
    {
        $isCorrect = true;
        foreach ($taxesAmount as $idTax => $amount) {
            $isCorrect &= Db::getInstance()->insert(
                'order_invoice_tax',
                [
                    'id_order_invoice' => (int) $this->id,
                    'type'             => 'shipping',
                    'id_tax'           => (int) $idTax,
                    'amount'           => round(
                        $amount,
                        _TB_PRICE_DATABASE_PRECISION_
                    ),
                ]
            );
        }

        return $isCorrect;
    }

    /**
     * @param array $taxesAmount
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function saveWrappingTaxCalculator(array $taxesAmount)
    {
        $isCorrect = true;
        foreach ($taxesAmount as $idTax => $amount) {
            $isCorrect &= Db::getInstance()->insert(
                'order_invoice_tax',
                [
                    'id_order_invoice' => (int) $this->id,
                    'type'             => 'wrapping',
                    'id_tax'           => (int) $idTax,
                    'amount'           => (float) $amount,
                ]
            );
        }

        return $isCorrect;
    }

    /**
     *
     * This method allow to add image information on a product detail
     *
     * @param array &$product
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function setProductImageInformations(&$product)
    {
        if (isset($product['product_attribute_id']) && $product['product_attribute_id']) {
            $idImage = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('image_shop.`id_image`')
                    ->from('product_attribute_image', 'pai')
                    ->join(Shop::addSqlAssociation('image', 'pai', true))
                    ->where('`id_product_attribute` = '.(int) $product['product_attribute_id'])
            );
        }

        if (!isset($idImage) || !$idImage) {
            $idImage = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('image_shop.`id_image`')
                    ->from('image', 'i')
                    ->join(Shop::addSqlAssociation('image', 'i', true, 'image_shop.`cover` = 1'))
                    ->where('i.`id_product` = '.(int) $product['product_id'])
            );
        }

        $product['image'] = null;
        $product['image_size'] = null;

        if ($idImage) {
            $product['image'] = new Image($idImage);
        }
    }

    /**
     *
     * This method allow to add stock information on a product detail
     *
     * @param array &$product
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function setProductCurrentStock(&$product)
    {
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
            && (int) $product['advanced_stock_management'] == 1
            && (int) $product['id_warehouse'] > 0
        ) {
            $product['current_stock'] = StockManagerFactory::getManager()->getProductPhysicalQuantities($product['product_id'], $product['product_attribute_id'], null, true);
        } else {
            $product['current_stock'] = '--';
        }
    }

    /**
     * @param $product
     * @param $customizedData
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setProductCustomizedDatas(&$product, $customizedData)
    {
        $product['customizedDatas'] = null;
        if (isset($customizedData[$product['product_id']][$product['product_attribute_id']])) {
            $product['customizedDatas'] = $customizedData[$product['product_id']][$product['product_attribute_id']];
        } else {
            $product['customizationQuantityTotal'] = 0;
        }
    }
}
