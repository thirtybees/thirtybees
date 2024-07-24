<?php

namespace Thirtybees\Core\View\Model;

use Currency;
use Order;
use Address;
use Carrier;
use State;

class GuestTrackingOrderModelCore extends Order
{
    /**
     * @var int
     */
    public $id_order_state;

    /**
     * @var bool
     */
    public $invoice;

    /**
     * @var array[]
     */
    public $order_history;

    /**
     * @var Carrier
     */
    public $carrier;

    /**
     * @var Address
     */
    public $address_invoice;

    /**
     * @var Address
     */
    public $address_delivery;

    /**
     * @var array
     */
    public $inv_adr_fields;

    /**
     * @var array
     */
    public $dlv_adr_fields;

    /**
     * @var array
     */
    public $invoiceAddressFormatedValues;

    /**
     * @var array
     */
    public $deliveryAddressFormatedValues;

    /**
     * @var Currency
     */
    public $currency;

    /**
     * @var array
     */
    public $discounts;

    /**
     * @var State|false
     */
    public $invoiceState;

    /**
     * @var State|false
     */
    public $deliveryState;

    /**
     * @var array
     */
    public $products;

    /**
     * @var array|false
     */
    public $customizedDatas;

    /**
     * @var false|float
     */
    public $total_old;

    /**
     * @var string|null
     */
    public $followup;

    /**
     * @var string
     */
    public $hook_orderdetaildisplayed;

}
