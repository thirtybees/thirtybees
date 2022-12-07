<?php
/**
 * Copyright (C) 2022-2022 thirty bees
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
 * @copyright 2022-2022 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * Class OrderDetailPackCore
 */
class OrderDetailPackCore extends ObjectModel
{
    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'order_detail_pack',
        'primary' => 'id_order_detail_pack',
        'fields'  => [
            'id_order_detail'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_product'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'default' => '0'],
            'quantity'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
        ],
        'keys' => [
            'order_detail_pack' => [
                'detail' => ['type' => ObjectModel::KEY, 'columns' => ['id_order_detail']],
                'product' => ['type' => ObjectModel::KEY, 'columns' => ['id_product', 'id_product_attribute']],
            ],
        ],
    ];

    /**
     * @var int $id_order_detail
     */
    public $id_order_detail;

    /**
     * @var int $id_product
     */
    public $id_product;

    /**
     * @var int $id_product_attribute
     */
    public $id_product_attribute;

    /**
     * @var int $quantity
     */
    public $quantity;

}
