<?php
/**
 * Copyright (C) 2025-2026 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2026 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * Class StoreCreditSpendCore
 *
 * One row per store credit consumed by an order: the durable record of "this
 * order was paid with this much of that credit". An order may consume several
 * credits, and a credit may serve several orders through partial use. Written
 * by StoreCredit::spendForOrder() during order validation; read by documents,
 * support tooling and modules.
 */
class StoreCreditSpendCore extends ObjectModel
{
    /**
     * @var int $id
     */
    public $id;

    /**
     * @var int $id_store_credit
     */
    public $id_store_credit;

    /**
     * @var int $id_order
     */
    public $id_order;

    /**
     * @var float $amount Amount taken from this credit for this order
     */
    public $amount;

    /**
     * @var string|null $date_reverted When set, this spend has been given back
     *                                 to the credit (order cancelled, payment
     *                                 error or refund) and must not be
     *                                 reverted again.
     */
    public $date_reverted;

    /**
     * @var string $date_add
     */
    public $date_add;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'store_credit_spend',
        'primary' => 'id_store_credit_spend',
        'fields'  => [
            'id_store_credit' => ['type' => self::TYPE_INT,   'validate' => 'isUnsignedId', 'required' => true],
            'id_order'        => ['type' => self::TYPE_INT,   'validate' => 'isUnsignedId', 'required' => true],
            'amount'          => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'],
            'date_reverted'   => ['type' => self::TYPE_DATE,  'validate' => 'isDate', 'dbDefault' => '0000-00-00 00:00:00'],
            'date_add'        => ['type' => self::TYPE_DATE,  'validate' => 'isDate', 'dbNullable' => false],
        ],
        'keys' => [
            'store_credit_spend' => [
                'id_store_credit' => ['type' => ObjectModel::KEY, 'columns' => ['id_store_credit']],
                'id_order'        => ['type' => ObjectModel::KEY, 'columns' => ['id_order']],
            ],
        ],
    ];

    /**
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
        // Same zero-date convention as StoreCredit's validity bounds: the
        // column stores 0000-00-00 for "never reverted", PHP sees null.
        if ($this->date_reverted === '0000-00-00 00:00:00') {
            $this->date_reverted = null;
        }
    }

    /**
     * All credit spends recorded for an order, including the credit's code
     * and name so documents can print them without another lookup. Consumed
     * by StoreCredit::restoreForOrder() and intended as the read API for
     * refund tooling and modules (gift-card modules print "paid with voucher
     * X" on documents and report redemption per period from these rows).
     *
     * @param int $idOrder
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function getSpendsForOrder(int $idOrder): array
    {
        // date_reverted is normalized in SQL: the zero-date "never reverted"
        // sentinel comes out as NULL, so callers can simply truth-test it.
        return Db::readOnly()->getArray((new DbQuery())
            ->select('s.id_store_credit_spend, s.id_store_credit, s.amount, s.date_add, c.code, c.name,
                      IF(s.date_reverted IS NULL OR s.date_reverted < "1900-00-00", NULL, s.date_reverted) AS date_reverted')
            ->from('store_credit_spend', 's')
            ->innerJoin('store_credit', 'c', 'c.id_store_credit = s.id_store_credit')
            ->where('s.id_order = ' . (int) $idOrder)
            ->orderBy('s.id_store_credit_spend ASC')
        );
    }

    /**
     * All orders a credit was spent on, newest first. Used by the back office
     * to answer "where did this balance go?".
     *
     * @param int $idStoreCredit
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function getSpendsForCredit(int $idStoreCredit): array
    {
        return Db::readOnly()->getArray((new DbQuery())
            ->select('s.id_store_credit_spend, s.id_order, s.amount, s.date_add, o.reference,
                      IF(s.date_reverted IS NULL OR s.date_reverted < "1900-00-00", NULL, s.date_reverted) AS date_reverted')
            ->from('store_credit_spend', 's')
            ->leftJoin('orders', 'o', 'o.id_order = s.id_order')
            ->where('s.id_store_credit = ' . (int) $idStoreCredit)
            ->orderBy('s.id_store_credit_spend DESC')
        );
    }
}
