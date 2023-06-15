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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class OrderCore
 */
class OrderCore extends ObjectModel
{
    const ROUND_ITEM = 1;
    const ROUND_LINE = 2;
    const ROUND_TOTAL = 3;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'orders',
        'primary' => 'id_order',
        'fields'  => [
            'reference'                => ['type' => self::TYPE_STRING, 'size' => 9],
            'id_shop_group'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '1'],
            'id_shop'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '1'],
            'id_carrier'               => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_lang'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_customer'              => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_cart'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_currency'              => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_address_delivery'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_address_invoice'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'current_state'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false],
            'secure_key'               => ['type' => self::TYPE_STRING, 'validate' => 'isMd5', 'size' => 32, 'dbDefault' => '-1'],
            'payment'                  => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'conversion_rate'          => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true, 'size' => 13, 'decimals' => 6, 'dbDefault' => '1.000000'],
            'module'                   => ['type' => self::TYPE_STRING, 'validate' => 'isModuleName', 'required' => true, 'size' => 64, 'dbNullable' => true],
            'recyclable'               => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'gift'                     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'gift_message'             => ['type' => self::TYPE_STRING, 'validate' => 'isMessage', 'size' => ObjectModel::SIZE_TEXT],
            'mobile_theme'             => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
            'shipping_number'          => ['type' => self::TYPE_STRING, 'validate' => 'isTrackingNumber', 'size' => 64],
            'total_discounts'          => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
            'total_discounts_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
            'total_discounts_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
            'total_paid'               => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbDefault' => '0.000000'],
            'total_paid_tax_incl'      => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
            'total_paid_tax_excl'      => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
            'total_paid_real'          => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => false, 'dbDefault' => '0.000000', 'dbNullable' => false],
            'total_products'           => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbDefault' => '0.000000'],
            'total_products_wt'        => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbDefault' => '0.000000'],
            'total_shipping'           => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
            'total_shipping_tax_incl'  => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
            'total_shipping_tax_excl'  => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
            'carrier_tax_rate'         => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'size' => 10, 'decimals' => 3, 'dbDefault' => '0.000'],
            'total_wrapping'           => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
            'total_wrapping_tax_incl'  => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
            'total_wrapping_tax_excl'  => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
            'round_mode'               => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'tinyint(1)', 'dbDefault' => '2'],
            'round_type'               => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'invoice_number'           => ['type' => self::TYPE_INT, 'dbDefault' => '0'],
            'delivery_number'          => ['type' => self::TYPE_INT, 'dbDefault' => '0'],
            'invoice_date'             => ['type' => self::TYPE_DATE, 'dbNullable' => false],
            'delivery_date'            => ['type' => self::TYPE_DATE, 'dbNullable' => false],
            'valid'                    => ['type' => self::TYPE_BOOL, 'dbType' => 'int(1) unsigned', 'dbDefault' => '0'],
            'date_add'                 => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
            'date_upd'                 => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        ],
        'keys' => [
            'orders' => [
                'current_state'       => ['type' => ObjectModel::KEY, 'columns' => ['current_state']],
                'date_add'            => ['type' => ObjectModel::KEY, 'columns' => ['date_add']],
                'id_address_delivery' => ['type' => ObjectModel::KEY, 'columns' => ['id_address_delivery']],
                'id_address_invoice'  => ['type' => ObjectModel::KEY, 'columns' => ['id_address_invoice']],
                'id_carrier'          => ['type' => ObjectModel::KEY, 'columns' => ['id_carrier']],
                'id_cart'             => ['type' => ObjectModel::KEY, 'columns' => ['id_cart']],
                'id_currency'         => ['type' => ObjectModel::KEY, 'columns' => ['id_currency']],
                'id_customer'         => ['type' => ObjectModel::KEY, 'columns' => ['id_customer']],
                'id_lang'             => ['type' => ObjectModel::KEY, 'columns' => ['id_lang']],
                'id_shop'             => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
                'id_shop_group'       => ['type' => ObjectModel::KEY, 'columns' => ['id_shop_group']],
                'invoice_number'      => ['type' => ObjectModel::KEY, 'columns' => ['invoice_number']],
                'reference'           => ['type' => ObjectModel::KEY, 'columns' => ['reference']],
            ],
        ],
    ];
    /** @var int Delivery address id */
    public $id_address_delivery;
    /** @var int Invoice address id */
    public $id_address_invoice;
    /** @var int $id_shop_group */
    public $id_shop_group;
    /** @var int $id_shop */
    public $id_shop;
    /** @var int Cart id */
    public $id_cart;
    /** @var int Currency id */
    public $id_currency;
    /** @var int Language id */
    public $id_lang;
    /** @var int Customer id */
    public $id_customer;
    /** @var int Carrier id */
    public $id_carrier;
    /** @var int Order Status id */
    public $current_state;
    /** @var string Secure key */
    public $secure_key;
    /** @var string Payment method */
    public $payment;
    /** @var string Payment module */
    public $module;
    /** @var float Currency exchange rate */
    public $conversion_rate;
    /** @var bool Customer is ok for a recyclable package */
    public $recyclable = 1;
    /** @var bool True if the customer wants a gift wrapping */
    public $gift = 0;
    /** @var string Gift message if specified */
    public $gift_message;
    /** @var bool Mobile Theme */
    public $mobile_theme;
    /**
     * @var string Shipping number
     * @deprecated 1.5.0.4
     * @see OrderCarrier->tracking_number
     */
    public $shipping_number;
    /** @var float Discounts total */
    public $total_discounts;
    /** @var float $total_discounts_tax_incl */
    public $total_discounts_tax_incl;
    /** @var float $total_discounts_tax_excl */
    public $total_discounts_tax_excl;
    /** @var float Total to pay */
    public $total_paid;
    /** @var float Total to pay tax included */
    public $total_paid_tax_incl;
    /** @var float Total to pay tax excluded */
    public $total_paid_tax_excl;
    /**
     * @var float Total really paid
     * @deprecated 1.5.0.1 use Order::getTotalPaid() method instead
     */
    public $total_paid_real;
    /** @var float Products total */
    public $total_products;
    /** @var float Products total tax included */
    public $total_products_wt;
    /** @var float Shipping total */
    public $total_shipping;
    /** @var float Shipping total tax included */
    public $total_shipping_tax_incl;
    /** @var float Shipping total tax excluded */
    public $total_shipping_tax_excl;
    /** @var float Shipping tax rate */
    public $carrier_tax_rate;
    /** @var float Wrapping total */
    public $total_wrapping;
    /** @var float Wrapping total tax included */
    public $total_wrapping_tax_incl;
    /** @var float Wrapping total tax excluded */
    public $total_wrapping_tax_excl;
    /** @var int Invoice number */
    public $invoice_number;
    /** @var int Delivery number */
    public $delivery_number;
    /** @var string Invoice creation date */
    public $invoice_date;
    /** @var string Delivery creation date */
    public $delivery_date;
    /** @var bool Order validity: current order status is logable (usually paid and not canceled) */
    public $valid;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var string Order reference, this reference is not unique, but unique for a payment */
    public $reference;
    /** @var int Round mode method used for this order */
    public $round_mode;
    /** @var int Round type method used for this order */
    public $round_type;
    /** @var array Webservice parameters */
    protected $webserviceParameters = [
        'objectMethods'   => ['add' => 'addWs'],
        'objectNodeName'  => 'order',
        'objectsNodeName' => 'orders',
        'fields'          => [
            'id_address_delivery' => ['xlink_resource' => 'addresses'],
            'id_address_invoice'  => ['xlink_resource' => 'addresses'],
            'id_cart'             => ['xlink_resource' => 'carts'],
            'id_currency'         => ['xlink_resource' => 'currencies'],
            'id_lang'             => ['xlink_resource' => 'languages'],
            'id_customer'         => ['xlink_resource' => 'customers'],
            'id_carrier'          => ['xlink_resource' => 'carriers'],
            'current_state'       => [
                'xlink_resource' => 'order_states',
                'setter'         => 'setWsCurrentState',
            ],
            'module'              => ['required' => true],
            'invoice_number'      => [],
            'invoice_date'        => [],
            'delivery_number'     => [],
            'delivery_date'       => [],
            'valid'               => [],
            'date_add'            => [],
            'date_upd'            => [],
            'shipping_number'     => [
                'getter' => 'getWsShippingNumber',
                'setter' => 'setWsShippingNumber',
            ],
        ],
        'associations'    => [
            'order_rows' => [
                'resource' => 'order_row',
                'getter' => 'getWsOrderRows',
                'setter' => false,
                'virtual_entity' => true,
                'fields'   => [
                    'id'                   => [],
                    'product_id'           => ['required' => true],
                    'product_attribute_id' => ['required' => true],
                    'product_quantity'     => ['required' => true],
                    'product_name'         => ['setter' => false],
                    'product_reference'    => ['setter' => false],
                    'product_ean13'        => ['setter' => false],
                    'product_upc'          => ['setter' => false],
                    'product_price'        => ['setter' => false],
                    'unit_price_tax_incl'  => ['setter' => false],
                    'unit_price_tax_excl'  => ['setter' => false],
                    'is_pack'              => ['setter' => false],
                ],
            ],
        ],
    ];

    /**
     * used to cache order customer
     */
    protected $cacheCustomer = null;

    /**
     * @var int
     */
    protected $_taxCalculationMethod = PS_TAX_EXC;

    /**
     * @var array
     */
    protected static $_historyCache = [];

    /**
     * OrderCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     *
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id, $idLang);

        $isAdmin = (is_object(Context::getContext()->controller) && Context::getContext()->controller->controller_type == 'admin');
        if ($this->id_customer && !$isAdmin) {
            $customer = new Customer((int) $this->id_customer);
            $this->_taxCalculationMethod = Group::getPriceDisplayMethod((int) $customer->id_default_group);
        } else {
            $this->_taxCalculationMethod = Group::getDefaultPriceDisplayMethod();
        }
    }

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function getFields()
    {
        if (!$this->id_lang) {
            $this->id_lang = Configuration::get('PS_LANG_DEFAULT', null, null, $this->id_shop);
        }

        return parent::getFields();
    }

    /**
     * Add this Order
     *
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = true)
    {
        if (parent::add($autoDate, $nullValues)) {
            return SpecificPrice::deleteByIdCart($this->id_cart);
        }

        return false;
    }

    /**
     * This function rounds all the decimal properties of this Object
     *
     * @deprecated 1.1.0
     */
    public function roundAmounts()
    {
        Tools::displayAsDeprecated('No longer needed, ObjectModel rounds now its self.');
    }

    /**
     * @return int
     */
    public function getTaxCalculationMethod()
    {
        return (int) $this->_taxCalculationMethod;
    }

    /**
     * Does NOT delete a product but "cancel" it (which means return/refund/delete it depending of the case)
     *
     * @param Order $order
     * @param OrderDetail $orderDetail
     * @param int $quantity
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function deleteProduct(Order $order, OrderDetail $orderDetail, $quantity)
    {
        if (!(int) $this->getCurrentState() || !validate::isLoadedObject($orderDetail)) {
            return false;
        }

        if ($this->hasBeenDelivered()) {
            if (!Configuration::get('PS_ORDER_RETURN', null, null, $this->id_shop)) {
                throw new PrestaShopException('PS_ORDER_RETURN is not defined in table configuration');
            }
            $orderDetail->product_quantity_return += (int) $quantity;

            return $orderDetail->update();
        } elseif ($this->hasBeenPaid()) {
            $orderDetail->product_quantity_refunded += (int) $quantity;

            return $orderDetail->update();
        }

        return $this->_deleteProduct($orderDetail, (int) $quantity);
    }

    /**
     * This function return products of the orders
     * It's similar to Order::getProducts but with similar outputs of Cart::getProducts
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getCartProducts()
    {
        $productIdList = [];
        $products = $this->getProducts();
        foreach ($products as &$product) {
            $product['id_product_attribute'] = $product['product_attribute_id'];
            $product['cart_quantity'] = $product['product_quantity'];
            $productIdList[] = $this->id_address_delivery.'_'.$product['product_id'].'_'.$product['product_attribute_id'].'_'.(isset($product['id_customization']) ? $product['id_customization'] : '0');
        }
        unset($product);

        $productList = [];
        foreach ($products as $product) {
            $key = $this->id_address_delivery.'_'.$product['id_product'].'_'.(isset($product['id_product_attribute']) ? $product['id_product_attribute'] : '0').'_'.(isset($product['id_customization']) ? $product['id_customization'] : '0');

            if (in_array($key, $productIdList)) {
                $productList[] = $product;
            }
        }

        return $productList;
    }

    /**
     * DOES delete the product
     *
     * @param OrderDetail $orderDetail
     * @param int $quantity
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function _deleteProduct($orderDetail, $quantity)
    {
        $productPriceTaxExcl = round(
            $orderDetail->unit_price_tax_excl,
            _TB_PRICE_DATABASE_PRECISION_
        ) * (int) $quantity;
        $productPriceTaxIncl = round(
            $orderDetail->unit_price_tax_incl,
            _TB_PRICE_DATABASE_PRECISION_
        ) * (int) $quantity;

        /* Update cart */
        $cart = new Cart($this->id_cart);
        $cart->updateQty($quantity, $orderDetail->product_id, $orderDetail->product_attribute_id, false, 'down'); // customization are deleted in deleteCustomization
        $cart->update();

        /* Update order */
        $shippingDiffTaxIncl = $this->total_shipping_tax_incl - round(
            $cart->getPackageShippingCost($this->id_carrier, true, null,
                                          $this->getCartProducts()),
            _TB_PRICE_DATABASE_PRECISION_
        );
        $shippingDiffTaxExcl = $this->total_shipping_tax_excl - round(
            $cart->getPackageShippingCost($this->id_carrier, false, null,
                                          $this->getCartProducts()),
            _TB_PRICE_DATABASE_PRECISION_
        );
        $this->total_shipping -= $shippingDiffTaxIncl;
        $this->total_shipping_tax_excl -= $shippingDiffTaxExcl;
        $this->total_shipping_tax_incl -= $shippingDiffTaxIncl;
        $this->total_products -= $productPriceTaxExcl;
        $this->total_products_wt -= $productPriceTaxIncl;
        $this->total_paid -= $productPriceTaxIncl + $shippingDiffTaxIncl;
        $this->total_paid_tax_incl -= $productPriceTaxIncl + $shippingDiffTaxIncl;
        $this->total_paid_tax_excl -= $productPriceTaxExcl + $shippingDiffTaxExcl;

        $fields = [
            'total_shipping',
            'total_shipping_tax_excl',
            'total_shipping_tax_incl',
            'total_products',
            'total_products_wt',
            'total_paid',
            'total_paid_tax_incl',
            'total_paid_tax_excl',
        ];

        /* Prevent from floating precision issues */
        foreach ($fields as $field) {
            if ($this->{$field} < 0) {
                $this->{$field} = 0;
            }
            $this->{$field} = round(
                $this->{$field},
                _TB_PRICE_DATABASE_PRECISION_
            );
        }

        /* Update order detail */
        $orderDetail->product_quantity -= (int) $quantity;
        if ($orderDetail->product_quantity == 0) {
            if (!$orderDetail->delete()) {
                return false;
            }
            if (count($this->getProductsDetail()) == 0) {
                $history = new OrderHistory();
                $history->id_order = (int) $this->id;
                $history->changeIdOrderState(Configuration::get('PS_OS_CANCELED'), $this);
                if (!$history->addWithemail()) {
                    return false;
                }
            }

            return $this->update();
        } else {
            $orderDetail->total_price_tax_incl -= $productPriceTaxIncl;
            $orderDetail->total_price_tax_excl -= $productPriceTaxExcl;
            $orderDetail->total_shipping_price_tax_incl -= $shippingDiffTaxIncl;
            $orderDetail->total_shipping_price_tax_excl -= $shippingDiffTaxExcl;
        }

        return $orderDetail->update() && $this->update();
    }

    /**
     * @param int $idCustomization
     * @param int $quantity
     * @param OrderDetail $orderDetail
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function deleteCustomization($idCustomization, $quantity, $orderDetail)
    {
        if (!(int) $this->getCurrentState()) {
            return false;
        }

        $conn = Db::getInstance();
        if ($this->hasBeenDelivered()) {
            return $conn->update('customization', ['quantity_returned' => ['type' => 'sql', 'value' => '`quantity_returned` + '.(int) $quantity]], '`id_customization` = '.(int) $idCustomization.' AND `id_cart` = '.(int) $this->id_cart.' AND `id_product` = '.(int) $orderDetail->product_id);
        } elseif ($this->hasBeenPaid()) {
            return $conn->update('customization', ['quantity_refunded' => ['type' => 'sql' , 'value' => '`quantity_refunded` + '.(int) $quantity]], '`id_customization` = '.(int) $idCustomization.' AND `id_cart` = '.(int) $this->id_cart.' AND `id_product` = '.(int) $orderDetail->product_id);
        }
        if (!$conn->update('customization', ['quantity' => ['type' => 'sql' , 'value' => '`quantity` - '.(int) $quantity]], '`id_customization` = '.(int) $idCustomization.' AND `id_cart` = '.(int) $this->id_cart.' AND `id_product` = '.(int) $orderDetail->product_id)) {
            return false;
        }
        if (!$conn->delete('customization', '`quantity` = 0')) {
            return false;
        }

        return $this->_deleteProduct($orderDetail, (int) $quantity);
    }

    /**
     * Get order history
     *
     * @param int $idLang Language id
     * @param bool|int $idOrderState Filter a specific order status
     * @param bool|int $noHidden Filter no hidden status
     * @param int $filters Flag to use specific field filter
     *
     * @return array History entries ordered by date DESC
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getHistory($idLang, $idOrderState = false, $noHidden = false, $filters = 0)
    {
        if (!$idOrderState) {
            $idOrderState = 0;
        }

        $logable = false;
        $delivery = false;
        $paid = false;
        $shipped = false;
        if ($filters > 0) {
            if ($filters & OrderState::FLAG_NO_HIDDEN) {
                $noHidden = true;
            }
            if ($filters & OrderState::FLAG_DELIVERY) {
                $delivery = true;
            }
            if ($filters & OrderState::FLAG_LOGABLE) {
                $logable = true;
            }
            if ($filters & OrderState::FLAG_PAID) {
                $paid = true;
            }
            if ($filters & OrderState::FLAG_SHIPPED) {
                $shipped = true;
            }
        }

        if (!isset(static::$_historyCache[$this->id.'_'.$idOrderState.'_'.$filters]) || $noHidden) {
            $idLang = $idLang ? (int) $idLang : 'o.`id_lang`';
            $result = Db::readOnly()->getArray(
                (new DbQuery())
                    ->select('os.*, oh.*, e.`firstname` AS `employee_firstname`, e.`lastname` AS `employee_lastname`, osl.`name` AS `ostate_name`')
                    ->from('orders', 'o')
                    ->leftJoin('order_history', 'oh', 'o.`id_order` = oh.`id_order`')
                    ->leftJoin('order_state', 'os', 'os.`id_order_state` = oh.`id_order_state`')
                    ->leftJoin('order_state_lang', 'osl', 'os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int) $idLang)
                    ->leftJoin('employee', 'e', 'e.`id_employee` = oh.`id_employee`')
                    ->where('oh.`id_order` = '.(int) $this->id)
                    ->where($noHidden ? 'os.`hidden` = 0' : '')
                    ->where($logable ? 'os.`logable` = 1' : '')
                    ->where($delivery ? 'os.`delivery` = 1' : '')
                    ->where($paid ? 'os.`paid` = 1' : '')
                    ->where($shipped ? 'os.`shipped` = 1' : '')
                    ->where((int) $idOrderState ? 'oh.`id_order_state` = '.(int) $idOrderState : '')
                    ->orderBy('oh.`date_add` DESC, oh.`id_order_history` DESC')
            );
            if ($noHidden) {
                return $result;
            }
            static::$_historyCache[$this->id.'_'.$idOrderState.'_'.$filters] = $result;
        }

        return static::$_historyCache[$this->id.'_'.$idOrderState.'_'.$filters];
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getProductsDetail()
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('*')
                ->from('order_detail', 'od')
                ->leftJoin('product', 'p', 'p.`id_product` = od.`product_id`')
                ->leftJoin('product_shop', 'ps', 'ps.`id_product` = od.`product_id` AND ps.`id_shop` = od.`id_shop`')
                ->where('od.`id_order` = '.(int) $this->id)
        );
    }

    /**
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function getFirstMessage()
    {
        return Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`message`')
                ->from('message')
                ->where('`id_order` = '.(int) $this->id)
                ->orderBy('`id_message`')
        );
    }

    /**
     * Marked as deprecated but should not throw any "deprecated" message
     * This function is used in order to keep front office backward compatibility 14 -> 1.5
     * (Order History)
     *
     * @deprecated 2.0.0
     *
     * @param array $row
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setProductPrices(&$row)
    {
        $taxCalculator = OrderDetail::getTaxCalculatorStatic((int) $row['id_order_detail']);
        $row['tax_calculator'] = $taxCalculator;
        $row['tax_rate'] = $taxCalculator->getTotalRate();

        $row['product_price'] = round(
            $row['unit_price_tax_excl'],
            _TB_PRICE_DATABASE_PRECISION_
        );
        $row['product_price_wt'] = round(
            $row['unit_price_tax_incl'],
            _TB_PRICE_DATABASE_PRECISION_
        );

        $row['product_price_wt_but_ecotax'] = $row['product_price_wt'] - $row['ecotax'];

        $row['total_wt'] = $row['total_price_tax_incl'];
        $row['total_price'] = $row['total_price_tax_excl'];
    }

    /**
     * Get order products
     *
     * @param array|bool $products
     * @param array|bool $selectedProducts
     * @param array|bool $selectedQty
     *
     * @return array Products with price, quantity (with taxe and without)
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getProducts($products = false, $selectedProducts = false, $selectedQty = false)
    {
        if (!$products) {
            $products = $this->getProductsDetail();
        }

        $customizedDatas = Product::getAllCustomizedDatas($this->id_cart);

        $resultArray = [];
        foreach ($products as $row) {
            // Change qty if selected
            if ($selectedQty) {
                $row['product_quantity'] = 0;
                if (is_array($selectedProducts) && !empty($selectedProducts)) {
                    foreach ($selectedProducts as $key => $idProduct) {
                        if ($row['id_order_detail'] == $idProduct) {
                            $row['product_quantity'] = (int) $selectedQty[$key];
                        }
                    }
                }
                if (!$row['product_quantity']) {
                    continue;
                }
            }

            $this->setProductImageInformations($row);
            $this->setProductCurrentStock($row);

            // Backward compatibility 1.4 -> 1.5
            $this->setProductPrices($row);

            $this->setProductCustomizedDatas($row, $customizedDatas);

            // Add information for virtual product
            if (!empty($row['download_hash'])) {
                $row['filename'] = ProductDownload::getFilenameFromIdProduct((int) $row['product_id']);
                // Get the display filename
                $row['display_filename'] = ProductDownload::getFilenameFromFilename($row['filename']);
            }

            $row['id_address_delivery'] = $this->id_address_delivery;

            /* Stock product */
            $resultArray[(int) $row['id_order_detail']] = $row;
        }

        if ($customizedDatas) {
            Product::addCustomizationPrice($resultArray, $customizedDatas);
        }

        return $resultArray;
    }

    /**
     * @param int $idCustomer
     * @param int $idProduct
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function getIdOrderProduct($idCustomer, $idProduct)
    {
        return (int) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('o.`id_order`')
                ->from('orders', 'o')
                ->leftJoin('order_detail', 'od', 'o.`id_order` = od.`id_order`')
                ->where('o.`id_customer` = '.(int) $idCustomer)
                ->where('od.`product_id` = '.(int) $idProduct)
                ->orderBy('o.`date_add` DESC')
        );
    }

    /**
     * @param array $product
     * @param array $customizedDatas
     */
    protected function setProductCustomizedDatas(&$product, $customizedDatas)
    {
        $product['customizedDatas'] = null;
        if (isset($customizedDatas[$product['product_id']][$product['product_attribute_id']])) {
            $product['customizedDatas'] = $customizedDatas[$product['product_id']][$product['product_attribute_id']];
        } else {
            $product['customizationQuantityTotal'] = 0;
        }
    }

    /**
     * This method allow to add stock information on a product detail
     *
     * If advanced stock management is active, get physical stock of this product in the warehouse associated to the ptoduct for the current order
     * Else get the available quantity of the product in fucntion of the shop associated to the order
     *
     * @param array &$product
     *
     * @throws PrestaShopException
     */
    protected function setProductCurrentStock(&$product)
    {
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
            && (int) $product['advanced_stock_management'] == 1
            && (int) $product['id_warehouse'] > 0) {
            $product['current_stock'] = StockManagerFactory::getManager()->getProductPhysicalQuantities($product['product_id'], $product['product_attribute_id'], (int) $product['id_warehouse'], true);
        } else {
            $product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], (int) $this->id_shop);
        }
    }

    /**
     * This method allow to add image information on a product detail
     *
     * @param array &$product
     *
     * @throws PrestaShopException
     */
    protected function setProductImageInformations(&$product)
    {
        $connection = Db::readOnly();
        if (isset($product['product_attribute_id']) && $product['product_attribute_id']) {
            $idImage = (int) $connection->getValue(
                (new DbQuery())
                    ->select('image_shop.`id_image`')
                    ->from('product_attribute_image', 'pai')
                    ->join(Shop::addSqlAssociation('image', 'pai', true))
                    ->leftJoin('image', 'i', 'i.`id_image` = pai.`id_image`')
                    ->where('`id_product_attribute` = '.(int) $product['product_attribute_id'])
                    ->orderBy('i.`position` ASC')
            );
        }

        if (!isset($idImage) || !$idImage) {
            $idImage = $connection->getValue(
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
     * @return float|int
     *
     * @throws PrestaShopException
     */
    public function getTaxesAverageUsed()
    {
        return Cart::getTaxesAverageUsed((int) $this->id_cart);
    }

    /**
     * Count virtual products in order
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getVirtualProducts()
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`product_id`, `product_attribute_id`, `download_hash`, `download_deadline`')
                ->from('order_detail', 'od')
                ->where('od.`id_order` = '.(int) $this->id)
                ->where('`download_hash` <> \'\'')
        );
    }

    /**
     * Check if order contains (only) virtual products
     *
     * @param bool $strict If false return true if there are at least one product virtual
     *
     * @return bool true if is a virtual order or false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function isVirtual($strict = true)
    {
        $products = $this->getProducts();
        if (count($products) < 1) {
            return false;
        }

        $virtual = true;

        foreach ($products as $product) {
            $isVirtual = (bool) $product['is_virtual'];
            if ($strict === false && $isVirtual) {
                return true;
            }

            $virtual = $virtual && $isVirtual;
        }

        return $virtual;
    }

    /**
     * @param bool $details
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *@deprecated 2.0.0
     */
    public function getDiscounts($details = false)
    {
        Tools::displayAsDeprecated();

        return Order::getCartRules();
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getCartRules()
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('*')
                ->from('order_cart_rule', 'ocr')
                ->where('ocr.`id_order` = '.(int) $this->id)
        );
    }

    /**
     * @param int $idCustomer
     * @param int $idCartRule
     *
     * @return int|null
     *
     * @throws PrestaShopException
     */
    public static function getDiscountsCustomer($idCustomer, $idCartRule)
    {
        $cacheId = 'Order::getDiscountsCustomer_'.(int) $idCustomer.'-'.(int) $idCartRule;
        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::readOnly()->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from(bqSQL(static::$definition['table']), 'o')
                    ->leftJoin('order_cart_rule', 'ocr', 'ocr.`id_order` = o.`id_order`')
                    ->where('o.`id_customer` = '.(int) $idCustomer)
                    ->where('ocr.`id_cart_rule` = '.(int) $idCartRule)
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Get current order status (eg. Awaiting payment, Delivered...)
     *
     * @return int Order status id
     */
    public function getCurrentState()
    {
        return $this->current_state;
    }

    /**
     * Get current order status name (eg. Awaiting payment, Delivered...)
     *
     * @param int $idLang
     *
     * @return array Order status details
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getCurrentStateFull($idLang)
    {
        return Db::readOnly()->getRow(
            (new DbQuery())
                ->select('os.`id_order_state`, osl.`name`, os.`logable`, os.`shipped`')
                ->from('order_state', 'os')
                ->leftJoin('order_state_lang', 'osl', 'osl.`id_order_state` = os.`id_order_state` AND osl.`id_lang` = '.(int) $idLang)
                ->where('os.`id_order_state` = '.(int) $this->current_state)
        );
    }

    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hasBeenDelivered()
    {
        return count($this->getHistory((int) $this->id_lang, false, false, OrderState::FLAG_DELIVERY));
    }

    /**
     * Has products returned by the merchant or by the customer?
     *
     * @throws PrestaShopException
     */
    public function hasProductReturned()
    {
        return Db::readOnly()->getValue(
            (new DbQuery())
                ->select('IFNULL(SUM(ord.`product_quantity`), SUM(`product_quantity_return`))')
                ->from('orders', 'o')
                ->innerJoin('order_detail', 'od', 'od.`id_order` = o.`id_order`')
                ->leftJoin('order_return_detail', 'ord', 'ord.`id_order_detail` = od.`id_order_detail`')
                ->where('o.`id_order` = '.(int) $this->id)
        );
    }

    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hasBeenPaid()
    {
        return count($this->getHistory((int) $this->id_lang, false, false, OrderState::FLAG_PAID));
    }

    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hasBeenShipped()
    {
        return count($this->getHistory((int) $this->id_lang, false, false, OrderState::FLAG_SHIPPED));
    }

    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function isInPreparation()
    {
        return count($this->getHistory((int) $this->id_lang, Configuration::get('PS_OS_PREPARATION')));
    }

    /**
     * Checks if the current order status is paid and shipped
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function isPaidAndShipped()
    {
        $orderState = $this->getCurrentOrderState();
        if ($orderState && $orderState->paid && $orderState->shipped) {
            return true;
        }

        return false;
    }

    /**
     * Get customer orders
     *
     * @param int $idCustomer Customer id
     * @param bool $showHiddenStatus Display or not hidden order statuses
     * @param Context|null $context
     *
     * @return array Customer orders
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCustomerOrders($idCustomer, $showHiddenStatus = false, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $connection = Db::readOnly();
        $res = $connection->getArray(
            (new DbQuery())
                ->select('o.*, COALESCE((SELECT SUM(od.`product_quantity`) FROM `'._DB_PREFIX_.'order_detail` od WHERE od.`id_order` = o.`id_order`), 0) nb_products')
                ->from('orders', 'o')
                ->where('o.`id_customer` = '.(int) $idCustomer.' '.Shop::addSqlRestriction(Shop::SHARE_ORDER))
                ->groupBy('o.`id_order`')
                ->orderBy('o.`date_add` DESC')
        );
        if (!$res) {
            return [];
        }

        foreach ($res as $key => $val) {
            $res2 = $connection->getArray(
                (new DbQuery())
                    ->select('os.`id_order_state`, osl.`name` AS `order_state`, os.`invoice`, os.`color` AS `order_state_color`')
                    ->from('order_history', 'oh')
                    ->leftJoin('order_state', 'os', 'os.`id_order_state` = oh.`id_order_state`')
                    ->innerJoin('order_state_lang', 'osl', 'os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int) $context->language->id)
                    ->where('oh.`id_order` = '.(int) $val['id_order'])
                    ->where(!$showHiddenStatus ? 'os.`hidden` != 1' : '')
                    ->orderBy('oh.`date_add` DESC, oh.`id_order_history` DESC')
                    ->limit(1)
            );

            if ($res2) {
                $res[$key] = array_merge($res[$key], $res2[0]);
            }
        }

        return $res;
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|null $idCustomer
     * @param string|null $type
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getOrdersIdByDate($dateFrom, $dateTo, $idCustomer = null, $type = null)
    {
        $result = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_order`')
                ->from('orders')
                ->where('DATE_ADD(`date_upd`, INTERVAL -1 DAY) <= \''.pSQL($dateTo).'\'')
                ->where('`date_upd`>= \''.pSQL($dateFrom).'\' '.Shop::addSqlRestriction())
                ->where($type ? '`'.bqSQL($type).'_number` != 0' : '')
                ->where($idCustomer ? '`id_customer` = '.(int) $idCustomer : '')
        );

        $orders = [];
        foreach ($result as $order) {
            $orders[] = (int) $order['id_order'];
        }

        return $orders;
    }

    /**
     * @param int|null $limit
     * @param Context|null $context
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getOrdersWithInformations($limit = null, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $stateNameSql = (new DbQuery())
            ->select('osl.`name`')
            ->from('order_state_lang', 'osl')
            ->where('osl.`id_order_state` = o.`current_state`')
            ->where('osl.`id_lang` = '.(int) $context->language->id)
            ->limit(1)
        ;

        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('*, ('.$stateNameSql->build().') AS `state_name`, o.`date_add` AS `date_add`, o.`date_upd` AS `date_upd`')
                ->from('orders', 'o')
                ->leftJoin('customer', 'c', 'c.`id_customer` = o.`id_customer`')
                ->where('1'.' '.Shop::addSqlRestriction(false, 'o'))
                ->orderBy('o.`date_add` DESC')
                ->limit((int) $limit ? (int) $limit : 0)
        );
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param int $idCustomer
     * @param string $type
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getOrdersIdInvoiceByDate($dateFrom, $dateTo, $idCustomer = null, $type = null)
    {
        $result = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_order`')
                ->from('orders')
                ->where('DATE_ADD(`invoice_date`, INTERVAL -1 DAY) <= \''.pSQL($dateTo).'\' AND `invoice_date` >= \''.pSQL($dateFrom).'\' '.Shop::addSqlRestriction())
                ->where($type ? '`'.bqSQL($type).'_number` != 0' : '')
                ->where($idCustomer ? '`id_customer` = '.(int) $idCustomer : '')
                ->orderBy('`invoice_date` ASC')
        );

        $orders = [];
        foreach ($result as $order) {
            $orders[] = (int) $order['id_order'];
        }

        return $orders;
    }

    /**
     * @param int $idOrderState
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getOrderIdsByStatus($idOrderState)
    {
        $result = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_order`')
                ->from('orders', 'o')
                ->where('o.`current_state` = '.(int) $idOrderState.' '.Shop::addSqlRestriction(false, 'o'))
                ->orderBy('`invoice_date` ASC')
        );

        $orders = [];
        foreach ($result as $order) {
            $orders[] = (int) $order['id_order'];
        }

        return $orders;
    }

    /**
     * Get product total without taxes
     *
     * @param false $products Deprecated.
     *
     * @return float Product total without taxes
     */
    public function getTotalProductsWithoutTaxes($products = false)
    {
        if ($products !== false) {
            Tools::displayParameterAsDeprecated('products');
        }

        return $this->total_products;
    }

    /**
     * Get product total with taxes
     *
     * @param false|array $products Deprecated.
     *
     * @return float Product total with taxes
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getTotalProductsWithTaxes($products = false)
    {
        if ($products !== false) {
            Tools::displayParameterAsDeprecated('products');
        }

        if ($this->total_products_wt != '0.00' && !$products) {
            return $this->total_products_wt;
        }

        /* Retro-compatibility (now set directly on the validateOrder() method) */
        if (!$products) {
            $products = $this->getProductsDetail();
        }

        $return = 0;
        foreach ($products as $row) {
            $return += $row['total_price_tax_incl'];
        }

        if (!$products) {
            $this->total_products_wt = $return;
            $this->update();
        }

        return $return;
    }

    /**
     * Get order customer
     *
     * @return Customer $customer
     *
     * @throws PrestaShopException
     */
    public function getCustomer()
    {
        if (is_null($this->cacheCustomer)) {
            $this->cacheCustomer = new Customer((int) $this->id_customer);
        }

        return $this->cacheCustomer;
    }

    /**
     * Get customer orders number
     *
     * @param int $idCustomer Customer id
     *
     * @return int Customer orders number
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCustomerNbOrders($idCustomer)
    {
        $result = Db::readOnly()->getRow(
            (new DbQuery())
                ->select('COUNT(`id_order`) AS `nb`')
                ->from('orders')
                ->where('`id_customer` = '.(int) $idCustomer.' '.Shop::addSqlRestriction())
        );

        return isset($result['nb']) ? $result['nb'] : 0;
    }

    /**
     * Get an order by its cart id
     *
     * @param int $idCart Cart id
     *
     * @return false|array Order details
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getOrderByCartId($idCart)
    {
        $result = Db::readOnly()->getRow(
            (new DbQuery())
                ->select('`id_order`')
                ->from('orders')
                ->where('`id_cart` = '.(int) $idCart.' '.Shop::addSqlRestriction())
        );

        return isset($result['id_order']) ? $result['id_order'] : false;
    }

    /**
     * @deprecated 2.0.0
     * @see Order::addCartRule()
     *
     * @param int $idCartRule
     * @param string $name
     * @param float $value
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addDiscount($idCartRule, $name, $value)
    {
        Tools::displayAsDeprecated();

        return Order::addCartRule($idCartRule, $name, ['tax_incl' => $value, 'tax_excl' => '0.00']);
    }

    /**
     * @param int $idCartRule
     * @param string $name
     * @param array $values
     * @param int $idOrderInvoice
     * @param bool|null $freeShipping
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addCartRule($idCartRule, $name, $values, $idOrderInvoice = 0, $freeShipping = null)
    {
        $orderCartRule = new OrderCartRule();
        $orderCartRule->id_order = $this->id;
        $orderCartRule->id_cart_rule = $idCartRule;
        $orderCartRule->id_order_invoice = $idOrderInvoice;
        $orderCartRule->name = $name;
        $orderCartRule->value = $values['tax_incl'];
        $orderCartRule->value_tax_excl = $values['tax_excl'];
        if ($freeShipping === null) {
            $cartRule = new CartRule($idCartRule);
            $freeShipping = $cartRule->free_shipping;
        }
        $orderCartRule->free_shipping = (int) $freeShipping;

        return $orderCartRule->add();
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getNumberOfDays()
    {
        $nbReturnDays = (int) Configuration::get('PS_ORDER_RETURN_NB_DAYS', null, null, $this->id_shop);
        if (!$nbReturnDays) {
            return true;
        }
        $result = Db::readOnly()->getRow(
            (new DbQuery())
                ->select('TO_DAYS("'.date('Y-m-d').' 00:00:00") - TO_DAYS(`delivery_date`) AS `days`')
                ->from('orders')
                ->where('`id_order` = '.(int) $this->id)
        );
        if ($result['days'] <= $nbReturnDays) {
            return true;
        }

        return false;
    }

    /**
     * Can this order be returned by the client?
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function isReturnable()
    {
        if (Configuration::get('PS_ORDER_RETURN', null, null, $this->id_shop) && $this->isPaidAndShipped()) {
            return $this->getNumberOfDays();
        }

        return false;
    }

    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function getLastInvoiceNumber()
    {
        $sql = (new DbQuery())
            ->select('MAX(`number`)')
            ->from('order_invoice');
        if (Configuration::get('PS_INVOICE_RESET')) {
            $sql->where('DATE_FORMAT(`date_add`, "%Y") = '.(int) date('Y'));
        }

        return (int)Db::readOnly()->getValue($sql);
    }

    /**
     * @param int $orderInvoiceId
     * @param int $idShop
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function setLastInvoiceNumber($orderInvoiceId, $idShop)
    {
        if (!$orderInvoiceId) {
            return false;
        }

        $number = Configuration::get('PS_INVOICE_START_NUMBER', null, null, $idShop);
        // If invoice start number has been set, you clean the value of this configuration
        if ($number) {
            Configuration::updateValue('PS_INVOICE_START_NUMBER', false, false, null, $idShop);
        }

        $sql = 'UPDATE `'._DB_PREFIX_.'order_invoice` SET number =';

        if ($number) {
            $sql .= (int) $number;
        } else {
            // Find the next number
            $newNumberSql = 'SELECT (MAX(`number`) + 1) AS new_number
                FROM `'._DB_PREFIX_.'order_invoice`'.(Configuration::get('PS_INVOICE_RESET') ?
                ' WHERE DATE_FORMAT(`date_add`, "%Y") = '.(int) date('Y') : '');
            $newNumber = Db::readOnly()->getValue($newNumberSql);

            $sql .= (int) $newNumber;
        }

        $sql .= ' WHERE `id_order_invoice` = '.(int) $orderInvoiceId;

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param int $orderInvoiceId
     *
     * @return bool|false|null|string
     * @throws PrestaShopException
     */
    public function getInvoiceNumber($orderInvoiceId)
    {
        if (!$orderInvoiceId) {
            return false;
        }

        return Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`number`')
                ->from('order_invoice')
                ->where('`id_order_invoice` = '.(int) $orderInvoiceId)
        );
    }

    /**
     * This method allows to generate first invoice of the current order
     *
     * @param bool $useExistingPayment
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setInvoice($useExistingPayment = false)
    {
        if (!$this->hasInvoice()) {
            if ($id = (int) $this->getOrderInvoiceIdIfHasDelivery()) {
                $orderInvoice = new OrderInvoice($id);
            } else {
                $orderInvoice = new OrderInvoice();
            }
            $orderInvoice->id_order = $this->id;
            if (!$id) {
                $orderInvoice->number = 0;
            }

            // Save Order invoice
            $this->setInvoiceDetails($orderInvoice);

            if (Configuration::get('PS_INVOICE')) {
                $this->setLastInvoiceNumber($orderInvoice->id, $this->id_shop);
            }

            $conn = Db::getInstance();
            // Update order_carrier
            $idOrderCarrier = $conn->getValue(
                (new DbQuery())
                    ->Select('`id_order_carrier`')
                    ->from('order_carrier')
                    ->where('`id_order` = '.(int) $orderInvoice->id_order)
                    ->where('`id_order_invoice` IS NULL OR `id_order_invoice` = 0')
            );

            if ($idOrderCarrier) {
                $orderCarrier = new OrderCarrier($idOrderCarrier);
                $orderCarrier->id_order_invoice = (int) $orderInvoice->id;
                $orderCarrier->update();
            }

            // Update order detail
            $conn->update(
                'order_detail',
                [
                    'id_order_invoice' => (int) $orderInvoice->id,
                ],
                '`id_order` = '.(int) $orderInvoice->id_order
            );
            Cache::clean('objectmodel_OrderDetail_*');

            $idOrderPayments = $conn->getArray(
                (new DbQuery())
                    ->select('DISTINCT op.`id_order_payment`')
                    ->from('order_payment', 'op')
                    ->innerJoin('orders', 'o', 'o.`reference` = op.`order_reference` AND o.`id_order` = '.(int) $orderInvoice->id_order)
                    ->leftJoin('order_invoice_payment', 'oip', 'oip.`id_order_payment` = op.`id_order_payment` AND oip.`id_order` = '.(int) $orderInvoice->id_order)
            );

            // Update order payment
            if ($useExistingPayment && !empty($idOrderPayments)) {
                foreach ($idOrderPayments as $orderPayment) {
                    $conn->insert(
                        'order_invoice_payment',
                        [
                            'id_order_invoice' => (int) $orderInvoice->id,
                            'id_order_payment' => (int) $orderPayment['id_order_payment'],
                            'id_order'         => (int) $orderInvoice->id_order,
                        ]
                    );
                }
            } else {
                // Since an invoice always requires an existing order payment, we are going to add one
                $orderPayment = new OrderPayment();
                $orderPayment->order_reference = $this->reference;
                $orderPayment->id_currency = $this->id_currency;
                $orderPayment->amount = $this->total_paid_tax_incl;
                $orderPayment->payment_method = $this->payment;
                $orderPayment->conversion_rate = $this->conversion_rate;

                $orderPayment->add();

                $conn->insert(
                    'order_invoice_payment',
                    [
                        'id_order_invoice' => (int) $orderInvoice->id,
                        'id_order_payment' => (int) $orderPayment->id,
                        'id_order'         => (int) $orderInvoice->id_order,
                    ]
                );

                $this->adjustTotalPaidAmount($orderPayment->amount, $orderPayment->id_currency);
            }
            // Clear cache
            Cache::clean('order_invoice_paid_*');

            // Update order cart rule
            $conn->update(
                'order_cart_rule',
                [
                    'id_order_invoice' => (int) $orderInvoice->id,
                ],
                '`id_order` = '.(int) $orderInvoice->id_order
            );

            // Keep it for retrocompatibility, to remove on 1.6 version
            $this->invoice_date = $orderInvoice->date_add;

            if (Configuration::get('PS_INVOICE')) {
                $this->invoice_number = $this->getInvoiceNumber($orderInvoice->id);
                $invoiceNumber = Hook::getFirstResponse(
                    'actionSetInvoice',
                    [
                        get_class($this)         => $this,
                        get_class($orderInvoice) => $orderInvoice,
                        'use_existing_payment'   => (bool) $useExistingPayment,
                    ]
                );

                if (is_numeric($invoiceNumber)) {
                    $this->invoice_number = (int) $invoiceNumber;
                } else {
                    $this->invoice_number = $this->getInvoiceNumber($orderInvoice->id);
                }
            }

            $this->update();
        }
    }

    /**
     * This method allows to fulfill the object order_invoice with sales figures
     *
     * @param OrderInvoice $orderInvoice
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function setInvoiceDetails($orderInvoice)
    {
        if (!$orderInvoice || !is_object($orderInvoice)) {
            return;
        }

        $address = new Address((int) $this->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $carrier = new Carrier((int) $this->id_carrier);

        if (Carrier::useProportionateTax()) {
            /** @var AverageTaxOfProductsTaxCalculator $wrappingTaxCalculator */
            $wrappingTaxCalculator = Adapter_ServiceLocator::get('AverageTaxOfProductsTaxCalculator')->setIdOrder($this->id);
            /** @var AverageTaxOfProductsTaxCalculator $taxCalculator */
            $taxCalculator = Adapter_ServiceLocator::get('AverageTaxOfProductsTaxCalculator')->setIdOrder($this->id);
        } else {
            $wrappingTaxManager = TaxManagerFactory::getManager($address, (int) Configuration::get('PS_GIFT_WRAPPING_TAX_RULES_GROUP'));
            $wrappingTaxCalculator = $wrappingTaxManager->getTaxCalculator();
            $taxCalculator = $carrier->getTaxCalculator($address);
        }

        $orderInvoice->total_discount_tax_excl = $this->total_discounts_tax_excl;
        $orderInvoice->total_discount_tax_incl = $this->total_discounts_tax_incl;
        $orderInvoice->total_paid_tax_excl = $this->total_paid_tax_excl;
        $orderInvoice->total_paid_tax_incl = $this->total_paid_tax_incl;
        $orderInvoice->total_products = $this->total_products;
        $orderInvoice->total_products_wt = $this->total_products_wt;
        $orderInvoice->total_shipping_tax_excl = $this->total_shipping_tax_excl;
        $orderInvoice->total_shipping_tax_incl = $this->total_shipping_tax_incl;
        $orderInvoice->shipping_tax_computation_method = $taxCalculator->computation_method;
        $orderInvoice->total_wrapping_tax_excl = $this->total_wrapping_tax_excl;
        $orderInvoice->total_wrapping_tax_incl = $this->total_wrapping_tax_incl;
        $orderInvoice->save();

        $orderInvoice->saveCarrierTaxCalculator($taxCalculator->getTaxesAmount($orderInvoice->total_shipping_tax_excl));
        $orderInvoice->saveWrappingTaxCalculator($wrappingTaxCalculator->getTaxesAmount($orderInvoice->total_wrapping_tax_excl));
    }

    /**
     * This method allows to generate first delivery slip of the current order
     *
     * @throws PrestaShopException
     */
    public function setDeliverySlip()
    {
        if (!$this->hasInvoice()) {
            $orderInvoice = new OrderInvoice();
            $orderInvoice->id_order = $this->id;
            $orderInvoice->number = 0;
            $this->setInvoiceDetails($orderInvoice);
            $this->delivery_date = $orderInvoice->date_add;
            $this->delivery_number = $this->getDeliveryNumber($orderInvoice->id);
            $this->update();
        }
    }

    /**
     * @param int $orderInvoiceId
     * @param int $idShop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function setDeliveryNumber($orderInvoiceId, $idShop)
    {
        if (!$orderInvoiceId) {
            return false;
        }

        $idShop = shop::getTotalShops() > 1 ? $idShop : null;

        $number = Configuration::get('PS_DELIVERY_NUMBER', null, null, $idShop);
        // If delivery slip start number has been set, you clean the value of this configuration
        if ($number) {
            Configuration::updateValue('PS_DELIVERY_NUMBER', false, false, null, $idShop);
        }

        $sql = 'UPDATE `'._DB_PREFIX_.'order_invoice` SET delivery_number = ';

        if ($number) {
            $sql .= (int) $number;
        } else {
            $sql .= '(SELECT `new_number` FROM (SELECT (MAX(`delivery_number`) + 1) AS `new_number` FROM `'._DB_PREFIX_.'order_invoice`) AS `result`)';
        }

        $sql .= ' WHERE `id_order_invoice` = '.(int) $orderInvoiceId;

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param int $orderInvoiceId
     *
     * @return bool|false|null|string
     *
     * @throws PrestaShopException
     */
    public function getDeliveryNumber($orderInvoiceId)
    {
        if (!$orderInvoiceId) {
            return false;
        }

        return Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`delivery_number`')
                ->from('order_invoice')
                ->where('`id_order_invoice` = '.(int) $orderInvoiceId)
        );
    }

    /**
     * @throws PrestaShopException
     */
    public function setDelivery()
    {
        // Get all invoice
        $orderInvoiceCollection = $this->getInvoicesCollection();
        foreach ($orderInvoiceCollection as $orderInvoice) {
            /** @var OrderInvoice $orderInvoice */
            if ($orderInvoice->delivery_number) {
                continue;
            }

            // Set delivery number on invoice
            $orderInvoice->delivery_number = 0;
            $orderInvoice->delivery_date = date('Y-m-d H:i:s');
            // Update Order Invoice
            $orderInvoice->update();
            $this->setDeliveryNumber($orderInvoice->id, $this->id_shop);
            $this->delivery_number = $this->getDeliveryNumber($orderInvoice->id);
        }

        // Keep it for backward compatibility, to remove on 1.6 version
        // Set delivery date
        $this->delivery_date = date('Y-m-d H:i:s');
        // Update object
        $this->update();
    }

    /**
     * @param int $idDelivery
     *
     * @return Order
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getByDelivery($idDelivery)
    {
        $res = Db::readOnly()->getRow(
            (new DbQuery())
                ->select('`id_order`')
                ->from('orders')
                ->where('`delivery_number` = '.(int) $idDelivery.' '.Shop::addSqlRestriction())
        );

        return new Order((int) $res['id_order']);
    }

    /**
     * Get a collection of orders using reference
     *
     * @param string $reference
     *
     * @return PrestaShopCollection Collection of Order
     *
     * @throws PrestaShopException
     */
    public static function getByReference($reference)
    {
        $orders = new PrestaShopCollection('Order');
        $orders->where('reference', '=', $reference);

        return $orders;
    }

    /**
     * @return float
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getTotalWeight()
    {
        $result = Db::readOnly()->getValue(
            (new DbQuery())
                ->select('SUM(`product_weight` * `product_quantity`)')
                ->from('order_detail')
                ->where('`id_order` = '.(int) $this->id)
        );

        return (float) $result;
    }

    /**
     * @param int $idInvoice
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getInvoice($idInvoice)
    {
        Tools::displayAsDeprecated();

        return Db::readOnly()->getRow(
            (new DbQuery())
                ->select('`invoice_number`, `id_order`')
                ->from('orders')
                ->where('`invoice_number` = '.(int) $idInvoice)
        );
    }

    /**
     * @param string $email
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function isAssociatedAtGuest($email)
    {
        if (!$email) {
            return false;
        }

        return (bool) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('orders', 'o')
                ->leftJoin('customer', 'c', 'c.`id_customer` = o.`id_customer`')
                ->where('o.`id_order` = '.(int) $this->id)
                ->where('c.`email` = \''.pSQL($email).'\'')
                ->where('c.`is_guest` = 1 '.Shop::addSqlRestriction(false, 'c'))
        );
    }

    /**
     * @param int $idOrder
     * @param int $idCustomer optionnal
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function getCartIdStatic($idOrder, $idCustomer = 0)
    {
        return (int) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_cart`')
                ->from('orders')
                ->where('`id_order` = '.(int) $idOrder)
                ->where($idCustomer ? '`id_customer` = '.(int) $idCustomer : '')
        );
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getWsOrderRows()
    {
        $sql = (new DbQuery())
                ->select('od.`id_order_detail` AS `id`')
                ->select('od.`product_id`')
                ->select('od.`product_price`')
                ->select('od.`id_order`')
                ->select('od.`product_attribute_id`')
                ->select('od.`product_quantity`')
                ->select('od.`product_name`')
                ->select('od.`product_reference`')
                ->select('od.`product_ean13`')
                ->select('od.`product_upc`')
                ->select('od.`unit_price_tax_incl`')
                ->select('od.`unit_price_tax_excl`')
                ->select('(CASE WHEN COUNT(odp.id_order_detail_pack) > 0 THEN 1 ELSE 0 END) as is_pack')
                ->from('order_detail', 'od')
                ->leftJoin('order_detail_pack', 'odp', 'od.id_order_detail = odp.id_order_detail')
                ->where('`id_order` = '.(int) $this->id)
                ->groupBy('od.id_order_detail');
        return Db::readOnly()->getArray($sql);
    }

    /** Set current order status
     *
     * @param int $idOrderState
     * @param int $idEmployee (/!\ not optional except for Webservice.
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function setCurrentState($idOrderState, $idEmployee = 0)
    {
        if (empty($idOrderState)) {
            return false;
        }
        $history = new OrderHistory();
        $history->id_order = (int) $this->id;
        $history->id_employee = (int) $idEmployee;
        $history->changeIdOrderState((int) $idOrderState, $this);
        $res = Db::readOnly()->getRow(
            (new DbQuery())
                ->select('`invoice_number`, `invoice_date`, `delivery_number`, `delivery_date`')
                ->from('orders')
                ->where('`id_order` = '.(int) $this->id)
        );
        $this->invoice_date = $res['invoice_date'];
        $this->invoice_number = $res['invoice_number'];
        $this->delivery_date = $res['delivery_date'];
        $this->delivery_number = $res['delivery_number'];
        $this->update();

        $history->addWithemail();
    }

    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function addWs($autodate = true, $nullValues = false)
    {
        if (! $this->module) {
            throw new PrestaShopException("Payment module not specified");
        }
        $paymentModule = Module::getInstanceByName($this->module);
        if ($paymentModule === false) {
           throw new PrestaShopException(sprintf("Payment module '%s' not found", $this->module));
        }
        if ($paymentModule instanceof PaymentModule) {
            $customer = new Customer($this->id_customer);
            $paymentModule->validateOrder(
                $this->id_cart,
                Configuration::get('PS_OS_WS_PAYMENT'),
                $this->total_paid,
                $this->payment,
                null,
                [],
                null,
                false,
                $customer->secure_key
            );
            $this->id = $paymentModule->currentOrder;
            return true;
        } else {
            throw new PrestaShopException(sprintf("Module '%s' is not payment module", $this->module));
        }
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteAssociations()
    {
        return Db::getInstance()->delete('order_detail', '`id_order` = '.(int) $this->id) !== false;
    }

    /**
     * This method return the ID of the previous order
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function getPreviousOrderId()
    {
        return (int) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_order`')
                ->from('orders')
                ->where('`id_order` < '.(int) $this->id.' '.Shop::addSqlRestriction())
                ->orderBy('`id_order` DESC')
        );
    }

    /**
     * This method return the ID of the next order
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function getNextOrderId()
    {
        return (int) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_order`')
                ->from('orders')
                ->where('`id_order` > '.(int) $this->id.' '.Shop::addSqlRestriction())
                ->orderBy('`id_order` ASC')
        );
    }

    /**
     * Get the an order detail list of the current order
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getOrderDetailList()
    {
        return OrderDetail::getList($this->id);
    }

    /**
     * Gennerate a unique reference for orders generated with the same cart id
     * This references, is usefull for check payment
     *
     * @return String
     */
    public static function generateReference()
    {
        return strtoupper(Tools::passwdGen(9, 'NO_NUMERIC'));
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function orderContainProduct($idProduct)
    {
        $productList = $this->getOrderDetailList();
        foreach ($productList as $product) {
            if ($product['product_id'] == (int) $idProduct) {
                return true;
            }
        }

        return false;
    }

    /**
     * This method returns true if at least one order details uses the
     * One After Another tax computation method.
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function useOneAfterAnotherTaxComputationMethod()
    {
        // if one of the order details use the tax computation method the display will be different
        return (bool) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('od.`tax_computation_method`')
                ->from('order_detail_tax', 'odt')
                ->leftJoin('order_detail', 'od', 'od.`id_order_detail` = odt.`id_order_detail`')
                ->where('od.`id_order` = '.(int) $this->id)
                ->where('od.`tax_computation_method` = '.(int) TaxCalculator::ONE_AFTER_ANOTHER_METHOD)
        );
    }

    /**
     * This method allows to get all Order Payment for the current order
     *
     * @return PrestaShopCollection Collection of OrderPayment
     *
     * @throws PrestaShopException
     */
    public function getOrderPaymentCollection()
    {
        $orderPayments = new PrestaShopCollection('OrderPayment');
        $orderPayments->where('order_reference', '=', $this->reference);

        return $orderPayments;
    }

    /**
     * This method allows to add a payment to the current order
     *
     * @param float $amountPaid
     * @param string $paymentMethod
     * @param string $paymentTransactionId
     * @param Currency $currency
     * @param string $date
     * @param OrderInvoice $orderInvoice
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addOrderPayment($amountPaid, $paymentMethod = null, $paymentTransactionId = null, $currency = null, $date = null, $orderInvoice = null)
    {
        $orderPayment = new OrderPayment();
        $orderPayment->order_reference = $this->reference;
        $orderPayment->id_currency = ($currency ? $currency->id : $this->id_currency);
        // we kept the currency rate for historization reasons
        $orderPayment->conversion_rate = ($currency ? $currency->conversion_rate : 1);
        // if payment_method is define, we used this
        $orderPayment->payment_method = ($paymentMethod ? $paymentMethod : $this->payment);
        $orderPayment->transaction_id = $paymentTransactionId;
        $orderPayment->amount = $amountPaid;
        $orderPayment->date_add = ($date ? $date : null);

        // Add time to the date if needed
        if ($orderPayment->date_add != null && preg_match('/^[0-9]+-[0-9]+-[0-9]+$/', $orderPayment->date_add)) {
            $orderPayment->date_add .= ' '.date('H:i:s');
        }

        $this->adjustTotalPaidAmount($orderPayment->amount, $orderPayment->id_currency);

        // We put autodate parameter of add method to true if date_add field is null
        $res = $orderPayment->add(is_null($orderPayment->date_add)) && $this->update();

        if (!$res) {
            return false;
        }

        if (!is_null($orderInvoice)) {
            $res = Db::getInstance()->insert(
                'order_invoice_payment',
                [
                    'id_order_invoice' => (int) $orderInvoice->id,
                    'id_order_payment' => (int) $orderPayment->id,
                    'id_order' => (int) $this->id,
                ]
            );

            // Clear cache
            Cache::clean('order_invoice_paid_*');
        }

        return $res;
    }

    /**
     * Get all documents linked to the current order
     *
     * @return OrderInvoice[]|OrderSlip[]
     *
     * @throws PrestaShopException
     */
    public function getDocuments()
    {
        /** @var OrderInvoice[] $invoices */
        $invoices = $this->getInvoicesCollection()->getResults();
        foreach ($invoices as $key => $invoice) {
            if ($invoice->number) {
                $invoice->delivery_number = 0;
            } else {
                unset($invoices[$key]);
            }
        }

        /** @var OrderInvoice[] $delivery_slips */
        $delivery_slips = $this->getDeliverySlipsCollection()->getResults();
        foreach ($delivery_slips as $key => $delivery) {
            if ($delivery->delivery_number) {
                $delivery->date_add = $delivery->delivery_date;
                $delivery->number = 0;
            } else {
                unset($delivery_slips[$key]);
            }
        }

        /** @var OrderSlip[] $order_slips */
        $order_slips = $this->getOrderSlipsCollection()->getResults();

        $documents = array_merge($invoices, $order_slips, $delivery_slips);
        usort($documents, ['Order', 'sortDocuments']);

        return $documents;
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getReturn()
    {
        return OrderReturn::getOrdersReturn($this->id_customer, $this->id);
    }

    /**
     * @return array return all shipping method for the current order
     * state_name sql var is now deprecated - use order_state_name for the state name and carrier_name for the carrier_name
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getShipping()
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('DISTINCT oc.`id_order_invoice`, oc.`weight`, oc.`shipping_cost_tax_excl`')
                ->select('oc.`shipping_cost_tax_incl`, c.`url`, oc.`id_carrier`, c.`name` AS `carrier_name`')
                ->select('oc.`date_add`, "Delivery" AS `type`, (CASE WHEN oc.id_order_carrier THEN 1 ELSE 0 END) AS `can_edit`, oc.`tracking_number`')
                ->select('oc.`id_order_carrier`, osl.`name` AS order_state_name, c.`name` AS `state_name`')
                ->from('orders', 'o')
                ->leftJoin('order_history', 'oh', 'o.`id_order` = oh.`id_order`')
                ->leftJoin('order_carrier', 'oc', 'o.`id_order` = oc.`id_order`')
                ->leftJoin('carrier', 'c', 'oc.`id_carrier` = c.`id_carrier`')
                ->leftJoin('order_state_lang', 'osl', 'oh.`id_order_state` = osl.`id_order_state`')
                ->where('o.`id_order` = '.(int) $this->id)
                ->groupBy('c.`id_carrier`')
        );
    }


    /**
     * Get all order_slips for the current order
     *
     * @return PrestaShopCollection Collection of OrderSlip
     *
     * @throws PrestaShopException
     */
    public function getOrderSlipsCollection()
    {
        $orderSlips = new PrestaShopCollection('OrderSlip');
        $orderSlips->where('id_order', '=', $this->id);

        return $orderSlips;
    }

    /**
     * Get all invoices for the current order
     *
     * @return PrestaShopCollection Collection of OrderInvoice
     *
     * @throws PrestaShopException
     */
    public function getInvoicesCollection()
    {
        $orderInvoices = new PrestaShopCollection('OrderInvoice');
        $orderInvoices->where('id_order', '=', $this->id);

        return $orderInvoices;
    }

    /**
     * Get all delivery slips for the current order
     *
     * @return PrestaShopCollection Collection of OrderInvoice
     *
     * @throws PrestaShopException
     */
    public function getDeliverySlipsCollection()
    {
        $orderInvoices = new PrestaShopCollection('OrderInvoice');
        $orderInvoices->where('id_order', '=', $this->id);
        $orderInvoices->where('delivery_number', '!=', '0');

        return $orderInvoices;
    }

    /**
     * Get all not paid invoices for the current order
     *
     * @return PrestaShopCollection Collection of Order invoice not paid
     *
     * @throws PrestaShopException
     */
    public function getNotPaidInvoicesCollection()
    {
        $invoices = $this->getInvoicesCollection();
        foreach ($invoices as $key => $invoice) {
            /** @var OrderInvoice $invoice */
            if ($invoice->isPaid()) {
                unset($invoices[$key]);
            }
        }

        return $invoices;
    }

    /**
     * Get total paid
     *
     * @param Currency $currency currency used for the total paid of the current order
     *
     * @return float amount in the $currency
     *
     * @throws PrestaShopException
     */
    public function getTotalPaid($currency = null)
    {
        if (!$currency) {
            $currency = new Currency($this->id_currency);
        }

        $total = 0;
        // Retrieve all payments
        $payments = $this->getOrderPaymentCollection();
        foreach ($payments as $payment) {
            /** @var OrderPayment $payment */
            if ($payment->id_currency == $currency->id) {
                $total += $payment->amount;
            } else {
                // get amount in shop default currency
                if ($payment->conversion_rate > 0.0) {
                    $amount = $payment->amount / $payment->conversion_rate;
                } else {
                    $amount = Tools::convertPrice($payment->amount, $payment->id_currency, false);
                }
                if ($currency->id == Configuration::get('PS_CURRENCY_DEFAULT', null, null, $this->id_shop)) {
                    $total += $amount;
                } else {
                    $total += Tools::convertPrice($amount, $currency->id, true);
                }
            }
        }

        return $total;
    }

    /**
     * Get the sum of total_paid_tax_incl of the orders with similar reference
     *
     * @return float
     *
     * @throws PrestaShopException
     */
    public function getOrdersTotalPaid()
    {
        return Db::readOnly()->getValue(
            (new DbQuery())
                ->select('SUM(`total_paid_tax_incl`)')
                ->from('orders')
                ->where('`reference` = \''.pSQL($this->reference).'\'')
                ->where('`id_cart` = '.(int) $this->id_cart)
        );
    }

    /**
     * This method allows to change the shipping cost of the current order
     *
     * @param float $amount
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updateShippingCost($amount)
    {
        $difference = $amount - $this->total_shipping;
        // if the current amount is same as the new, we return true
        if ($difference == 0) {
            return true;
        }

        // update the total_shipping value
        $this->total_shipping = $amount;
        // update the total of this order
        $this->total_paid += $difference;

        // update database
        return $this->update();
    }

    /**
     * Returns the correct product taxes breakdown.
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getProductTaxesBreakdown()
    {
        $connection = Db::readOnly();
        if ($this->useOneAfterAnotherTaxComputationMethod()) {
            // sum by taxes
            $taxesByTax = $connection->getArray('
			SELECT odt.`id_order_detail`, t.`name`, t.`rate`, SUM(`total_amount`) AS `total_amount`
			FROM `'._DB_PREFIX_.'order_detail_tax` odt
			LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.`id_tax` = odt.`id_tax`)
			LEFT JOIN `'._DB_PREFIX_.'order_detail` od ON (od.`id_order_detail` = odt.`id_order_detail`)
			WHERE od.`id_order` = '.(int) $this->id.'
			GROUP BY odt.`id_tax`
			');

            // format response
            $tmpTaxInfos = [];
            foreach ($taxesByTax as $taxInfos) {
                $tmpTaxInfos[$taxInfos['rate']]['total_amount'] = $taxInfos['tax_amount'];
                $tmpTaxInfos[$taxInfos['rate']]['name'] = $taxInfos['name'];
            }
        } else {
            // sum by order details in order to retrieve real taxes rate
            $taxesInfos = $connection->getArray('
			SELECT odt.`id_order_detail`, t.`rate` AS `name`, SUM(od.`total_price_tax_excl`) AS total_price_tax_excl, SUM(t.`rate`) AS rate, SUM(`total_amount`) AS `total_amount`
			FROM `'._DB_PREFIX_.'order_detail_tax` odt
			LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.`id_tax` = odt.`id_tax`)
			LEFT JOIN `'._DB_PREFIX_.'order_detail` od ON (od.`id_order_detail` = odt.`id_order_detail`)
			WHERE od.`id_order` = '.(int)$this->id.'
			GROUP BY odt.`id_order_detail`
			');

            // sum by taxes
            $tmpTaxInfos = [];
            foreach ($taxesInfos as $taxInfos) {
                if (!isset($tmpTaxInfos[$taxInfos['rate']])) {
                    $tmpTaxInfos[$taxInfos['rate']] = [
                        'total_amount'         => 0,
                        'name'                 => 0,
                        'total_price_tax_excl' => 0,
                    ];
                }

                $tmpTaxInfos[$taxInfos['rate']]['total_amount'] += $taxInfos['total_amount'];
                $tmpTaxInfos[$taxInfos['rate']]['name'] = $taxInfos['name'];
                $tmpTaxInfos[$taxInfos['rate']]['total_price_tax_excl'] += $taxInfos['total_price_tax_excl'];
            }
        }

        return $tmpTaxInfos;
    }

    /**
     * Returns the shipping taxes breakdown
     *
     * @return array
     */
    public function getShippingTaxesBreakdown()
    {
        $taxesBreakdown = [];

        $shippingTaxAmount = $this->total_shipping_tax_incl - $this->total_shipping_tax_excl;

        if ($shippingTaxAmount > 0) {
            $taxesBreakdown[] = [
                'rate'         => $this->carrier_tax_rate,
                'total_amount' => $shippingTaxAmount,
            ];
        }

        return $taxesBreakdown;
    }

    /**
     * Returns the wrapping taxes breakdown
     * @todo
     * @return array
     */
    public function getWrappingTaxesBreakdown()
    {
        $taxesBreakdown = [];

        return $taxesBreakdown;
    }

    /**
     * Returns the ecotax taxes breakdown
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getEcoTaxTaxesBreakdown()
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`eco_tax_rate`, SUM(`ecotax`) AS `ecotax_tax_excl`, SUM(`ecotax`) AS `ecotax_tax_incl`')
                ->from('order_detail')
                ->where('`id_order` = '.(int) $this->id)
        );
    }

    /**
     * Has invoice return true if this order has already an invoice
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function hasInvoice()
    {
        return (bool) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_order_invoice`')
                ->from('order_invoice')
                ->where('`id_order` = '.(int) $this->id)
                ->where(Configuration::get('PS_INVOICE') ? '`number` > 0' : '')
        );
    }

    /**
     * Has Delivery return true if this order has already a delivery slip
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function hasDelivery()
    {
        return (bool) $this->getOrderInvoiceIdIfHasDelivery();
    }

    /**
     * Get order invoice id if has delivery return id_order_invoice if this order has already a delivery slip
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function getOrderInvoiceIdIfHasDelivery()
    {
        return (int) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_order_invoice`')
                ->from('order_invoice')
                ->where('`id_order` = '.(int) $this->id)
                ->where('`delivery_number` > 0')
        );
    }

    /**
     * Get warehouse associated to the order
     *
     * @return array List of warehouse
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getWarehouseList()
    {
        $results = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_warehouse`')
                ->from('order_detail')
                ->where('`id_order` = '.(int) $this->id)
                ->groupBy('`id_warehouse`')
        );
        if (!$results) {
            return [];
        }

        $warehouseList = [];
        foreach ($results as $row) {
            $warehouseList[] = $row['id_warehouse'];
        }

        return $warehouseList;
    }

    /**
     * @return OrderState|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getCurrentOrderState()
    {
        if ($this->current_state) {
            return new OrderState($this->current_state);
        }

        return null;
    }

    /**
     * @see ObjectModel::getWebserviceObjectList()
     *
     * @param string $sqlJoin
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getWebserviceObjectList($sqlJoin, $sqlFilter, $sqlSort, $sqlLimit)
    {
        $sqlFilter .= Shop::addSqlRestriction(Shop::SHARE_ORDER, 'main');

        return parent::getWebserviceObjectList($sqlJoin, $sqlFilter, $sqlSort, $sqlLimit);
    }

    /**
     * Get all other orders with the same reference
     *
     * @return PrestaShopCollection
     *
     * @throws PrestaShopException
     */
    public function getBrother()
    {
        $collection = new PrestaShopCollection('order');
        $collection->where('reference', '=', $this->reference);
        $collection->where('id_order', '<>', $this->id);

        return $collection;
    }

    /**
     * Get a collection of order payments
     *
     * @throws PrestaShopException
     */
    public function getOrderPayments()
    {
        return OrderPayment::getByOrderReference($this->reference);
    }

    /**
     * Return a unique reference like : GWJTHMZUN#2
     *
     * With multishipping, order reference are the same for all orders made with the same cart
     * in this case this method suffix the order reference by a # and the order number
     *
     * @throws PrestaShopException
     */
    public function getUniqReference()
    {
        $query = new DbQuery();
        $query->select('MIN(id_order) as min, MAX(id_order) as max');
        $query->from('orders');
        $query->where('id_cart = '.(int) $this->id_cart);

        $order = Db::readOnly()->getRow($query);

        if ($order['min'] == $order['max']) {
            return $this->reference;
        } else {
            return $this->reference.'#'.($this->id + 1 - $order['min']);
        }
    }

    /**
     * Return a unique reference like : GWJTHMZUN#2
     *
     * With multishipping, order reference are the same for all orders made with the same cart
     * in this case this method suffix the order reference by a # and the order number
     *
     * @throws PrestaShopException
     */
    public static function getUniqReferenceOf($idOrder)
    {
        $order = new Order($idOrder);

        return $order->getUniqReference();
    }

    /**
     * Return id of carrier
     *
     * Get id of the carrier used in order
     *
     * @throws PrestaShopException
     */
    public function getIdOrderCarrier()
    {
        return (int) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_order_carrier`')
                ->from('order_carrier')
                ->where('`id_order` = '.(int) $this->id)
        );
    }

    /**
     * @param OrderInvoice|OrderSlip $a
     * @param OrderInvoice|OrderSlip $b
     *
     * @return int
     */
    public static function sortDocuments($a, $b)
    {
        if ($a->date_add == $b->date_add) {
            return 0;
        }

        return ($a->date_add < $b->date_add) ? -1 : 1;
    }

    /**
     * @return string|null
     *
     * @throws PrestaShopException
     */
    public function getWsShippingNumber()
    {
        $idOrderCarrier = Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_order_carrier`')
                ->from('order_carrier')
                ->where('`id_order` = '.(int) $this->id)
        );
        if ($idOrderCarrier) {
            $orderCarrier = new OrderCarrier($idOrderCarrier);

            return $orderCarrier->tracking_number;
        }

        return $this->shipping_number;
    }

    /**
     * @param string $shippingNumber
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setWsShippingNumber($shippingNumber)
    {
        $idOrderCarrier = Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_order_carrier`')
                ->from('order_carrier')
                ->where('`id_order` = '.(int) $this->id)
        );
        if ($idOrderCarrier) {
            $orderCarrier = new OrderCarrier($idOrderCarrier);
            $orderCarrier->tracking_number = $shippingNumber;
            $orderCarrier->update();
        } else {
            $this->shipping_number = $shippingNumber;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getWsCurrentState()
    {
        return $this->getCurrentState();
    }

    /**
     * @param int $state
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function setWsCurrentState($state)
    {
        if ($this->id) {
            $this->setCurrentState($state);
        }

        return true;
    }


    /**
     * By default this function was made for invoice, to compute tax amounts and balance delta (because of computation made on round values).
     * If you provide $limitToOrderDetails, only these item will be taken into account. This option is useful for order slips for example,
     * where only a sublist of the order is refunded.
     *
     * @param bool|array $limitToOrderDetails Optional array of OrderDetails to take into account. False by default to take all OrderDetails from the current Order.
     *
     * @return array A list of tax rows applied to the given OrderDetails (or all OrderDetails linked to the current Order).
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getProductTaxesDetails($limitToOrderDetails = false)
    {
        // compute products discount
        $orderDiscountTaxExcl = $this->total_discounts_tax_excl;

        $freeShippingTax = 0;
        $productSpecificDiscounts = [];
        $cheapestProductDiscounts = [];

        $expectedTotalBase = $this->total_products - $this->total_discounts_tax_excl;

        // We add $free_shipping_tax because when there is free shipping, the tax that would
        // be paid if there wasn't is included in $discounts_tax.
        $expectedTotalTax = $this->total_products_wt - $this->total_products;

        $orderDetailTaxRows = [];

        $breakdown = [];

        foreach ($this->getCartRules() as $orderCartRule) {
            if ($orderCartRule['free_shipping'] && $freeShippingTax === 0) {
                $freeShippingTax = $this->total_shipping_tax_incl - $this->total_shipping_tax_excl;
                $orderDiscountTaxExcl -= $this->total_shipping_tax_excl;
            }

            $cartRule = new CartRule($orderCartRule['id_cart_rule']);
            if ($cartRule->product_restriction) {
                if (!isset($productSpecificDiscounts[(int) $cartRule->reduction_product]) || empty($productSpecificDiscounts[(int) $cartRule->reduction_product])) {
                    $productSpecificDiscounts[(int) $cartRule->reduction_product] = 0;
                }

                $productSpecificDiscounts[(int) $cartRule->reduction_product] += $orderCartRule['value_tax_excl'];
                $orderDiscountTaxExcl -= $orderCartRule['value_tax_excl'];
            }
            if ($cheapestProduct = json_decode($cartRule->description)) {
                if (!isset($cheapestProductDiscounts[(int) $cheapestProduct->id_product]) || empty($cheapestProductDiscounts[(int) $cheapestProduct->id_product])) {
                    $cheapestProductDiscounts[(int) $cheapestProduct->id_product] = ['tax_amount' => 0, 'tax_base'  => 0];
                }

                $cheapestProductDiscounts[(int) $cheapestProduct->id_product]['tax_amount'] += ($orderCartRule['value'] - $orderCartRule['value_tax_excl']);
                $cheapestProductDiscounts[(int) $cheapestProduct->id_product]['tax_base'] += ($orderCartRule['value_tax_excl']);
            }
        }

        // Get order_details
        $orderDetails = $limitToOrderDetails ? $limitToOrderDetails : $this->getOrderDetailList();
        $orderEcotaxTax = 0;
        $taxRates = [];
        foreach ($orderDetails as $orderDetail) {
            $idOrderDetail = $orderDetail['id_order_detail'];
            $taxCalculator = OrderDetail::getTaxCalculatorStatic($idOrderDetail);

            $unitEcotaxTax = round(
                $orderDetail['ecotax'] * $orderDetail['ecotax_tax_rate'] / 100.0,
                _TB_PRICE_DATABASE_PRECISION_
            );

            $discountRatio = 0;
            if ($this->total_products > 0) {
                $discountRatio = ($orderDetail['unit_price_tax_excl'] + $orderDetail['ecotax']) / $this->total_products;
            }

            // share of global discount
            $discountedPriceTaxExcl = $orderDetail['unit_price_tax_excl'] - $discountRatio * $orderDiscountTaxExcl;
            // specific discount
            if (!empty($productSpecificDiscounts[$orderDetail['product_id']])) {
                $discountedPriceTaxExcl -= $productSpecificDiscounts[$orderDetail['product_id']];
            }

            $quantity = $orderDetail['product_quantity'];

            foreach ($taxCalculator->taxes as $tax) {
                $taxRates[$tax->id] = $tax->rate;
            }

            foreach ($taxCalculator->getTaxesAmount($discountedPriceTaxExcl) as $idTax => $unitAmount) {
                $totalTaxBase = $quantity * $discountedPriceTaxExcl;
                $totalAmount = $quantity * $unitAmount;

                if (isset($cheapestProductDiscounts[$orderDetail['product_id']]['tax_base'])) {
                    $totalTaxBase -= $cheapestProductDiscounts[$orderDetail['product_id']]['tax_base'];
                    $totalAmount -= $cheapestProductDiscounts[$orderDetail['product_id']]['tax_amount'];
                }

                if (!isset($breakdown[$idTax])) {
                    $breakdown[$idTax] = ['tax_base' => 0, 'tax_amount' => 0];
                }

                $breakdown[$idTax]['tax_base'] += $totalTaxBase;
                $breakdown[$idTax]['tax_amount'] += $totalAmount;

                $orderDetailTaxRows[] = [
                    'id_order_detail' => $idOrderDetail,
                    'id_tax'          => $idTax,
                    'tax_rate'        => $taxRates[$idTax],
                    'unit_tax_base'   => $discountedPriceTaxExcl,
                    'total_tax_base'  => $totalTaxBase,
                    'unit_amount'     => $unitAmount,
                    'total_amount'    => $totalAmount,
                ];
            }
        }

        return $orderDetailTaxRows;
    }

    /**
     * The primary purpose of this method is to be
     * called at the end of the generation of each order
     * in PaymentModule::validateOrder, to fill in
     * the order_detail_tax table with taxes
     * that will add up in such a way that
     * the sum of the tax amounts in the product tax breakdown
     * is equal to the difference between products with tax and
     * products without tax.
     *
     * @throws PrestaShopException
     */
    public function updateOrderDetailTax()
    {
        $orderDetailTaxRowsToInsert = $this->getProductTaxesDetails();

        if (empty($orderDetailTaxRowsToInsert)) {
            return;
        }

        $oldIdOrderDetails = [];
        $values = [];
        foreach ($orderDetailTaxRowsToInsert as $row) {
            $oldIdOrderDetails[] = (int) $row['id_order_detail'];
            $values[] = [
                'id_order_detail' => (int) $row['id_order_detail'],
                'id_tax'          => (int) $row['id_tax'],
                'unit_amount'     => (float) $row['unit_amount'],
                'total_amount'    => (float) $row['total_amount'],
            ];
        }

        $conn = Db::getInstance();

        // Remove current order_detail_tax'es
        $conn->delete('order_detail_tax', '`id_order_detail` IN ('.implode(', ', $oldIdOrderDetails).')');

        // Insert the adjusted ones instead
        $conn->insert('order_detail_tax', $values);
    }

    /**
     * Get order detail taxes breakdown
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getOrderDetailTaxes()
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('od.`id_tax_rules_group`, od.`product_quantity`, odt.*, t.*')
                ->from('orders', 'o')
                ->innerJoin('order_detail', 'od', 'od.`id_order` = o.`id_order`')
                ->innerJoin('order_detail_tax', 'odt', 'odt.`id_order_detail` = od.`id_order_detail`')
                ->innerJoin('tax', 't', 't.`id_tax` = odt.`id_tax`')
                ->where('o.`id_order` = '.(int) $this->id)
        );
    }

    /**
     * Adjust property total_paid_real
     *
     * Property total_paid_real is deprecated and should not be relied upon. To get information
     * about amount paid, use Order::getTotalPaid() method instead
     *
     * For backwards compatibility reasons, we try to keep this property in sync with content
     * of tb_order_payment table.
     *
     * @param float $amount
     * @param int $currencyId
     * @throws PrestaShopException
     */
    public function adjustTotalPaidAmount($amount, $currencyId)
    {
        $currencyId = (int)$currencyId;
        $amount = (float)$amount;
        $orderCurrency = (int)$this->id_currency;

        if ($orderCurrency == $currencyId) {
            $amountOrderCurrency = $amount;
        } else {
            // we need to convert $amount from source currency to order currency
            $amountDefaultCurrency = Tools::convertPrice($amount, $currencyId, false);
            $defaultCurrencyId = (int)Configuration::get('PS_CURRENCY_DEFAULT');
            if ($orderCurrency == $defaultCurrencyId) {
                $amountOrderCurrency = $amountDefaultCurrency;
            } else {
                $amountOrderCurrency = Tools::convertPrice($amountDefaultCurrency, $orderCurrency, true);
            }
        }

        // this should be the only place in the core that modifies this deprecated property
        /** @noinspection PhpDeprecationInspection */
        $this->total_paid_real += $amountOrderCurrency;
    }
}
