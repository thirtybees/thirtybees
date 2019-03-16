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
 * Class OrderPaymentCore
 *
 * @since 1.0.0
 */
class OrderPaymentCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var string $order_reference */
    public $order_reference;
    /** @var int $id_currency */
    public $id_currency;
    /** @var float $amount */
    public $amount;
    /** @var string $payment_method */
    public $payment_method;
    /** @var float $conversion_rate */
    public $conversion_rate;
    /** @var string $transaction_id */
    public $transaction_id;
    /** @var string $card_number */
    public $card_number;
    /** @var string $card_brand */
    public $card_brand;
    /** @var string $card_expiration */
    public $card_expiration;
    /** @var string $card_holder */
    public $card_holder;
    /** @var string $date_add */
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'order_payment',
        'primary' => 'id_order_payment',
        'fields'  => [
            'order_reference' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything',       'size' => 9                       ],
            'id_currency'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',                     'required' => true],
            'amount'          => ['type' => self::TYPE_PRICE,  'validate' => 'isNegativePrice',                  'required' => true],
            'payment_method'  => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'                                       ],
            'conversion_rate' => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                                             ],
            'transaction_id'  => ['type' => self::TYPE_STRING, 'validate' => 'isAnything',       'size' => 254                     ],
            'card_number'     => ['type' => self::TYPE_STRING, 'validate' => 'isAnything',       'size' => 254                     ],
            'card_brand'      => ['type' => self::TYPE_STRING, 'validate' => 'isAnything',       'size' => 254                     ],
            'card_expiration' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything',       'size' => 254                     ],
            'card_holder'     => ['type' => self::TYPE_STRING, 'validate' => 'isAnything',       'size' => 254                     ],
            'date_add'        => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                                              ],
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
        if (parent::add($autoDate, $nullValues)) {
            Hook::exec('actionPaymentCCAdd', ['paymentCC' => $this]);

            return true;
        }

        return false;
    }

    /**
     * Get the detailed payment of an order
     *
     * @param int $idOrder
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getByOrderId($idOrder)
    {
        return ObjectModel::hydrateCollection(
            'OrderPayment',
            Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('*')
                    ->from('order_payment')
                    ->where('`id_order` = '.(int) $idOrder)
            )
        );
    }

    /**
     * Get the detailed payment of an order
     *
     * @param int $orderReference
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getByOrderReference($orderReference)
    {
        return ObjectModel::hydrateCollection(
            'OrderPayment',
            Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('*')
                    ->from('order_payment')
                    ->where('`order_reference` = \''.pSQL($orderReference).'\'')
            )
        );
    }

    /**
     * Get Order Payments By Invoice ID
     *
     * @param int $idInvoice Invoice ID
     *
     * @return PrestaShopCollection Collection of OrderPayment
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getByInvoiceId($idInvoice)
    {
        $payments = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_order_payment`')
                ->from('order_invoice_payment')
                ->where('`id_order_invoice` = '.(int) $idInvoice)
        );
        if (!$payments) {
            return new PrestaShopCollection('OrderPayment');
        }

        $paymentList = [];
        foreach ($payments as $payment) {
            $paymentList[] = $payment['id_order_payment'];
        }

        $payments = new PrestaShopCollection('OrderPayment');
        $payments->where('id_order_payment', 'IN', $paymentList);

        return $payments;
    }

    /**
     * Return order invoice object linked to the payment
     *
     * @param int $idOrder Order Id
     *
     * @return bool|OrderInvoice
     * @throws PrestaShopException
     */
    public function getOrderInvoice($idOrder)
    {
        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_order_invoice`')
                ->from('order_invoice_payment')
                ->where('`id_order_payment` = '.(int) $this->id)
                ->where('`id_order` = '.(int) $idOrder)
        );

        if (!$res) {
            return false;
        }

        return new OrderInvoice((int) $res);
    }
}
