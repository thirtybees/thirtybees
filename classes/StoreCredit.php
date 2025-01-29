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
        $conn = Db::readOnly();
        $sql = (new DbQuery())
            ->select('SUM(c.amount - c.amount_used)')
            ->from('store_credit', 'c')
            ->innerJoin('store_credit_shop', 'cs', 'c.id_store_credit = cs.id_store_credit AND cs.id_shop = ' . (int)$shopId)
            ->where('c.id_customer = ' . (int)$customerId)
            ->where('c.date_from <= NOW()')
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

}
