<?php
/**
 * Copyright (C) 2025-2025 thirty bees
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
 * @copyright 2025-2025 thirty bees
 * @license   Open Software License (OSL 3.0)
 */


/**
 * Class StoreCreditCore
 */
class StoreCreditCore extends ObjectModel
{
    /**
     * Payment method label used for the OrderPayment row that surfaces spent
     * store credit on the order and its documents.
     */
    const ORDER_PAYMENT_METHOD = 'Store credit';

    /**
     * @var int $id
     */
    public $id;

    /**
     * @var string|string[] $name
     */
    public $name;

    /**
     * @var int $id_customer
     */
    public $id_customer;

    /**
     * @var string
     */
    public $code;

    /**
     * @var string $date_from
     */
    public $date_from;

    /**
     * @var string $date_to
     */
    public $date_to;

    /**
     * @var string $description
     */
    public $description;

    /**
     * @var int $quantity
     */
    public $amount;

    /**
     * @var int $quantity
     */
    public $amount_used;

    /**
     * @var string $date_add
     */
    public $date_add;

    /**
     * @var string $date_upd
     */
    public $date_upd;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'     => 'store_credit',
        'primary'   => 'id_store_credit',
        'fields'    => [
            'id_customer'  => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'],
            'code'         => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 254, 'unique' => true],
            'name'         => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 254],
            'description'  => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => ObjectModel::SIZE_TEXT],
            'date_from'    => ['type' => self::TYPE_DATE,   'validate' => 'isDate', 'dbDefault' => '0000-00-00 00:00:00'],
            'date_to'      => ['type' => self::TYPE_DATE,   'validate' => 'isDate', 'dbDefault' => '0000-00-00 00:00:00'],
            'amount'       => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'],
            'amount_used'  => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'],
            'date_add'     => ['type' => self::TYPE_DATE,   'validate' => 'isDate', 'dbNullable' => false],
            'date_upd'     => ['type' => self::TYPE_DATE,   'validate' => 'isDate', 'dbNullable' => false],
        ],
        'keys' => [],
    ];

    /**
     * @param $id
     * @param $idLang
     * @param $idShop
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
        if ($this->date_from === '0000-00-00 00:00:00') {
            $this->date_from = null;
        }
        if ($this->date_to === '0000-00-00 00:00:00') {
            $this->date_to = null;
        }
    }

    /**
     * Remaining (unspent) amount on this credit.
     *
     * @return float
     */
    public function getRemainingAmount(): float
    {
        return max(0.0, (float)$this->amount - (float)$this->amount_used);
    }

    /**
     * Whether the credit is within its validity window. Empty bounds
     * (normalized to null in the constructor) mean "no restriction".
     *
     * @return bool
     */
    public function isCurrentlyValid(): bool
    {
        $now = date('Y-m-d H:i:s');
        if ($this->date_from && $this->date_from > $now) {
            return false;
        }
        if ($this->date_to && $this->date_to < $now) {
            return false;
        }
        return true;
    }

    /**
     * Bind this credit to a customer account. Only unowned credits (gift
     * cards waiting to be redeemed by whoever received the code) or credits
     * already owned by this customer can be claimed - entering a code must
     * never move credit away from another customer's account.
     *
     * @param int $idCustomer
     *
     * @return bool true when the credit is (now) owned by $idCustomer
     *
     * @throws PrestaShopException
     */
    public function claimForCustomer(int $idCustomer): bool
    {
        if ($idCustomer <= 0) {
            return false;
        }
        $owner = (int)$this->id_customer;
        if ($owner === $idCustomer) {
            return true;
        }
        if ($owner !== 0) {
            return false;
        }
        $this->id_customer = $idCustomer;
        return (bool)$this->update();
    }

    /**
     * @param string $code
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function getIdByCode(string $code): int
    {
        $conn = Db::readOnly();
        return (int)$conn->getValue((new DbQuery())
            ->select('id_store_credit')
            ->from('store_credit')
            ->where('code = "' . pSQL($code) . '"')
        );
    }

    /**
     * @param string $code
     *
     * @return static|null
     *
     * @throws PrestaShopException
     */
    public static function getByCode(string $code)
    {
        $id = static::getIdByCode($code);
        if ($id) {
            return new static($id);
        }
        return null;
    }


    /**
     * @param int $shopId
     * @param int $customerId
     *
     * @return float
     *
     * @throws PrestaShopException
     */
    public static function getByCustomerId(int $shopId, int $customerId): float
    {
        // Master connection on purpose: this figure decides how much money the
        // checkout may spend, so it must not lag behind a just-booked debit on
        // a replicated setup.
        $conn = Db::getInstance();
        $sql = (new DbQuery())
            ->select('SUM(c.amount - c.amount_used)')
            ->from('store_credit', 'c')
            ->innerJoin('store_credit_shop', 'cs', 'c.id_store_credit = cs.id_store_credit AND cs.id_shop = ' . (int)$shopId)
            ->where('c.id_customer = ' . (int)$customerId)
            ->where('c.date_from <= NOW()')
            // Dates before 1900 mean "no expiry": the constructor normalizes
            // 0000-00-00 to null and empty bounds are stored as zero dates.
            ->where('(c.date_to < "1900-00-00" OR c.date_to >= NOW())');
        return (float)$conn->getValue($sql);
    }


    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function isFeatureActive(): bool
    {
        return static::isCurrentlyUsed('store_credit');
    }

    /**
     * Debit $amount from the order's customer credit balance and record one
     * StoreCreditSpend row per credit touched. This is the durable side of
     * paying with store credit: Cart::getOrderTotal() only lowers the payable
     * total, and without this booking the balance would never decrease and
     * nothing on the order would show the credit was used.
     *
     * Credits are consumed deterministically: soonest expiry first (credits
     * without an expiry last), then oldest first. Each debit is a single
     * conditional UPDATE guarded by the remaining balance, so two concurrent
     * checkouts can never spend the same money; a lost race simply moves on
     * to the customer's next credit.
     *
     * Either the full amount is booked or nothing is: on shortfall (usually a
     * concurrent spend) all debits and spend rows of this call are rolled
     * back and a PrestaShopException is thrown, so order validation aborts
     * instead of leaving a half-booked payment.
     *
     * @param Order $order
     * @param float $amount tax-included amount the cart covered from credit
     *
     * @return array rows: id_store_credit, code, amount - one per credit used
     *
     * @throws PrestaShopException
     */
    public static function spendForOrder(Order $order, float $amount): array
    {
        $amount = Tools::roundPrice(max(0.0, $amount));
        if (!$amount) {
            return [];
        }
        $idCustomer = (int) $order->id_customer;
        if ($idCustomer <= 0) {
            throw new PrestaShopException('Store credit can only be spent by a customer account.');
        }

        $conn = Db::getInstance();
        $candidates = $conn->getArray((new DbQuery())
            ->select('c.id_store_credit, c.code, c.amount, c.amount_used')
            ->from('store_credit', 'c')
            ->innerJoin('store_credit_shop', 'cs', 'c.id_store_credit = cs.id_store_credit AND cs.id_shop = ' . (int) $order->id_shop)
            ->where('c.id_customer = ' . $idCustomer)
            ->where('c.date_from <= NOW()')
            // Dates before 1900 mean "no expiry", see getByCustomerId().
            ->where('(c.date_to < "1900-00-00" OR c.date_to >= NOW())')
            ->where('(c.amount - c.amount_used) > 0')
            ->orderBy('IF(c.date_to < "1900-00-00", 1, 0) ASC, c.date_to ASC, c.date_add ASC, c.id_store_credit ASC')
        );

        $remaining = $amount;
        $spends = [];
        foreach ($candidates as $row) {
            if (Tools::roundPrice($remaining) <= 0) {
                break;
            }
            $balance = (float) $row['amount'] - (float) $row['amount_used'];

            // Two attempts per credit: the first uses the balance from the
            // candidate snapshot; when that debit loses a race against a
            // concurrent checkout, the balance is re-read once and the debit
            // retried with the smaller slice, so money that still exists is
            // not skipped (skipping would over-consume later-expiring credits
            // or abort an order the funds do cover).
            for ($attempt = 0; $attempt < 2; $attempt++) {
                $take = min($remaining, $balance);
                if (Tools::roundPrice($take) <= 0) {
                    break;
                }
                $takeSql = static::sqlAmount($take);

                // Conditional debit: succeeds only when the balance still
                // covers it, so a concurrent checkout can never spend the
                // same money.
                $debited = $conn->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'store_credit`
                     SET amount_used = amount_used + ' . $takeSql . ', date_upd = NOW()
                     WHERE id_store_credit = ' . (int) $row['id_store_credit'] . '
                       AND (amount - amount_used) >= ' . $takeSql
                );
                if (!$debited || !$conn->Affected_Rows()) {
                    // Lost a race; refresh the live balance and try the
                    // remainder of this credit once before moving on.
                    $balance = (float) $conn->getValue((new DbQuery())
                        ->select('amount - amount_used')
                        ->from('store_credit')
                        ->where('id_store_credit = ' . (int) $row['id_store_credit'])
                    );
                    continue;
                }

                try {
                    $spend = new StoreCreditSpend();
                    $spend->id_store_credit = (int) $row['id_store_credit'];
                    $spend->id_order = (int) $order->id;
                    $spend->amount = $take;
                    $recorded = $spend->add();
                } catch (Throwable $e) {
                    $recorded = false;
                }
                if (!$recorded) {
                    // The debit must never exist without its record; give the
                    // money back and stop with an error.
                    static::revertSpends($spends, (int) $row['id_store_credit'], $takeSql);
                    throw new PrestaShopException('Could not record the store credit spend.');
                }

                $spends[] = [
                    'id_store_credit' => (int) $row['id_store_credit'],
                    'id_spend' => (int) $spend->id,
                    'code' => (string) $row['code'],
                    'amount' => $take,
                ];
                $remaining -= $take;
                break;
            }
        }

        if (Tools::roundPrice($remaining) > 0) {
            // Balance fell short (usually a concurrent spend between the cart
            // total and this booking). All or nothing: undo and abort.
            static::revertSpends($spends);
            throw new PrestaShopException('The store credit balance no longer covers this order. Please review your cart and try again.');
        }

        return $spends;
    }

    /**
     * Compensation for a spend that must not stand: restore the debited
     * balances and remove the spend rows. Used by spendForOrder() itself and
     * by PaymentModule::validateOrder() when order creation fails after the
     * credit was already booked. Optionally also reverts one extra bare debit
     * that has no spend row yet.
     *
     * Failures in here are logged loudly instead of thrown: this method runs
     * on error paths where a second exception would mask the original one,
     * but a debit that could not be restored must never disappear silently.
     *
     * @param array $spends rows as built by spendForOrder()
     * @param int|null $extraIdCredit credit debited but not yet recorded
     * @param string|null $extraAmountSql its amount, pre-formatted for SQL
     *
     * @throws PrestaShopException
     */
    public static function revertSpends(array $spends, ?int $extraIdCredit = null, ?string $extraAmountSql = null)
    {
        $conn = Db::getInstance();
        foreach ($spends as $spend) {
            $amountSql = static::sqlAmount((float) $spend['amount']);
            $restored = $conn->execute(
                'UPDATE `' . _DB_PREFIX_ . 'store_credit`
                 SET amount_used = GREATEST(0, amount_used - ' . $amountSql . '), date_upd = NOW()
                 WHERE id_store_credit = ' . (int) $spend['id_store_credit']
            );
            if (!$restored) {
                Logger::addLog('StoreCredit::revertSpends - could not restore ' . $amountSql . ' to credit ' . (int) $spend['id_store_credit'] . ', manual correction needed', 3);
            }
            if (!$conn->delete('store_credit_spend', 'id_store_credit_spend = ' . (int) $spend['id_spend'])) {
                Logger::addLog('StoreCredit::revertSpends - could not remove spend row ' . (int) $spend['id_spend'], 3);
            }
        }
        if ($extraIdCredit !== null && $extraAmountSql !== null) {
            $restored = $conn->execute(
                'UPDATE `' . _DB_PREFIX_ . 'store_credit`
                 SET amount_used = GREATEST(0, amount_used - ' . $extraAmountSql . '), date_upd = NOW()
                 WHERE id_store_credit = ' . $extraIdCredit
            );
            if (!$restored) {
                Logger::addLog('StoreCredit::revertSpends - could not restore ' . $extraAmountSql . ' to credit ' . $extraIdCredit . ', manual correction needed', 3);
            }
        }
    }

    /**
     * Give the credit spent on an order back to the customer. Called when an
     * order reaches a cancelled, payment-error or refund state, so credit
     * spent on an order that never completes is not lost.
     *
     * Idempotent and race safe: each spend row is claimed by a conditional
     * "mark reverted" UPDATE before the money moves, so a state bouncing
     * between cancelled and error can never refund twice. Marking first means
     * a crash between the two statements loses the refund rather than paying
     * it double; that case is logged for manual correction (the safer of the
     * two failure modes for the shop).
     *
     * @param int $idOrder
     *
     * @return int number of spend rows restored
     *
     * @throws PrestaShopException
     */
    public static function restoreForOrder(int $idOrder): int
    {
        $conn = Db::getInstance();
        // Zero dates mean "never reverted", matching the credit validity
        // convention (the column defaults to 0000-00-00, not NULL).
        $notReverted = '(date_reverted IS NULL OR date_reverted < "1900-00-00")';
        $rows = $conn->getArray((new DbQuery())
            ->select('id_store_credit_spend, id_store_credit, amount')
            ->from('store_credit_spend')
            ->where('id_order = ' . (int) $idOrder)
            ->where($notReverted)
        );

        $restored = 0;
        foreach ($rows as $row) {
            $claimed = $conn->execute(
                'UPDATE `' . _DB_PREFIX_ . 'store_credit_spend`
                 SET date_reverted = NOW()
                 WHERE id_store_credit_spend = ' . (int) $row['id_store_credit_spend'] . '
                   AND ' . $notReverted
            );
            if (!$claimed || !$conn->Affected_Rows()) {
                // Another process already reverted this row.
                continue;
            }

            $amountSql = static::sqlAmount((float) $row['amount']);
            $refunded = $conn->execute(
                'UPDATE `' . _DB_PREFIX_ . 'store_credit`
                 SET amount_used = GREATEST(0, amount_used - ' . $amountSql . '), date_upd = NOW()
                 WHERE id_store_credit = ' . (int) $row['id_store_credit']
            );
            if (!$refunded) {
                Logger::addLog('StoreCredit::restoreForOrder - spend row ' . (int) $row['id_store_credit_spend'] . ' marked reverted but restoring ' . $amountSql . ' to credit ' . (int) $row['id_store_credit'] . ' failed, manual correction needed', 3);
                continue;
            }
            $restored++;
        }

        return $restored;
    }

    /**
     * Format a monetary amount for direct use in SQL, at the database price
     * precision. Shared by every raw credit UPDATE so the debit, the revert
     * and the restore always move exactly the same figure.
     *
     * @param float $amount
     *
     * @return string
     */
    protected static function sqlAmount(float $amount): string
    {
        return number_format($amount, _TB_PRICE_DATABASE_PRECISION_, '.', '');
    }

}
