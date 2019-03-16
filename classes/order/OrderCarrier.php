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
 * Class OrderCarrierCore
 *
 * @since 1.0.0
 */
class OrderCarrierCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int */
    public $id_order_carrier;

    /** @var int */
    public $id_order;

    /** @var int */
    public $id_carrier;

    /** @var int */
    public $id_order_invoice;

    /** @var float */
    public $weight;

    /** @var float */
    public $shipping_cost_tax_excl;

    /** @var float */
    public $shipping_cost_tax_incl;

    /** @var int */
    public $tracking_number;

    /** @var string Object creation date */
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'order_carrier',
        'primary' => 'id_order_carrier',
        'fields'  => [
            'id_order'               => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',     'required' => true],
            'id_carrier'             => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',     'required' => true],
            'id_order_invoice'       => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                        ],
            'weight'                 => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                             ],
            'shipping_cost_tax_excl' => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                             ],
            'shipping_cost_tax_incl' => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                             ],
            'tracking_number'        => ['type' => self::TYPE_STRING, 'validate' => 'isTrackingNumber'                    ],
            'date_add'               => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                              ],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_order'   => ['xlink_resource' => 'orders'  ],
            'id_carrier' => ['xlink_resource' => 'carriers'],
        ],
    ];
}
