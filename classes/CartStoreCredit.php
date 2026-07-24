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
 * Class CartStoreCreditCore
 *
 * Associates an unclaimed store credit code with a cart, so a visitor can
 * enter the code before signing in (guest redemption, enabled with the
 * PS_STORE_CREDIT_GUEST setting). The association only carries weight while
 * the credit is unclaimed: the moment a credit is claimed by an account, the
 * customer balance takes over and stale associations stop counting.
 */
class CartStoreCreditCore extends ObjectModel
{
    /**
     * @var int $id
     */
    public $id;

    /**
     * @var int $id_cart
     */
    public $id_cart;

    /**
     * @var int $id_store_credit
     */
    public $id_store_credit;

    /**
     * @var string $date_add
     */
    public $date_add;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'cart_store_credit',
        'primary' => 'id_cart_store_credit',
        'fields'  => [
            'id_cart'         => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedId', 'required' => true],
            'id_store_credit' => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedId', 'required' => true],
            'date_add'        => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        ],
        'keys' => [
            'cart_store_credit' => [
                'cart_credit' => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['id_cart', 'id_store_credit']],
                'id_store_credit' => ['type' => ObjectModel::KEY, 'columns' => ['id_store_credit']],
            ],
        ],
    ];

    /**
     * Attaches a store credit to a cart. Idempotent: attaching the same code
     * twice is a no-op thanks to the unique key.
     *
     * @param int $idCart
     * @param int $idStoreCredit
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function attach(int $idCart, int $idStoreCredit): bool
    {
        if ($idCart <= 0 || $idStoreCredit <= 0) {
            return false;
        }
        if (static::isAttached($idCart, $idStoreCredit)) {
            return true;
        }
        $association = new CartStoreCredit();
        $association->id_cart = $idCart;
        $association->id_store_credit = $idStoreCredit;
        try {
            $added = $association->add();
        } catch (PrestaShopException $e) {
            // Db only throws under _PS_DEBUG_SQL_; treat the exception the
            // same as a false return below.
            $added = false;
        }
        if (!$added) {
            // A concurrent attach of the same code hits the unique key; that
            // still means "attached", anything else is a real failure.
            if (static::isAttached($idCart, $idStoreCredit)) {
                return true;
            }
            Logger::addLog(
                'CartStoreCredit::attach() failed for cart ' . $idCart . ', credit ' . $idStoreCredit,
                3
            );
            return false;
        }
        return true;
    }

    /**
     * @param int $idCart
     * @param int $idStoreCredit
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function isAttached(int $idCart, int $idStoreCredit): bool
    {
        // Master connection: attach() relies on this recheck to recognize a
        // concurrently inserted row, which a lagging replica may not show yet.
        return (bool) Db::getInstance()->getValue((new DbQuery())
            ->select('id_cart_store_credit')
            ->from('cart_store_credit')
            ->where('id_cart = ' . $idCart)
            ->where('id_store_credit = ' . $idStoreCredit)
        );
    }

    /**
     * Removes every credit association from a cart. Used when the customer
     * removes the store credit line from the cart summary.
     *
     * @param int $idCart
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function deleteForCart(int $idCart): bool
    {
        $deleted = Db::getInstance()->delete('cart_store_credit', 'id_cart = ' . $idCart);
        if (!$deleted) {
            Logger::addLog('CartStoreCredit::deleteForCart() failed for cart ' . $idCart, 3);
        }
        return $deleted;
    }
}
