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
 * Class OrderCartRuleCore
 *
 * @since 1.0.0
 */
class OrderCartRuleCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int */
    public $id_order_cart_rule;

    /** @var int */
    public $id_order;

    /** @var int */
    public $id_cart_rule;

    /** @var int */
    public $id_order_invoice;

    /** @var string */
    public $name;

    /** @var float value (tax incl.) of voucher */
    public $value;

    /** @var float value (tax excl.) of voucher */
    public $value_tax_excl;

    /** @var bool value : voucher gives free shipping or not */
    public $free_shipping;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'order_cart_rule',
        'primary' => 'id_order_cart_rule',
        'fields'  => [
            'id_order'         => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'id_cart_rule'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'id_order_invoice' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                    ],
            'name'             => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',  'required' => true],
            'value'            => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',      'required' => true],
            'value_tax_excl'   => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice',      'required' => true],
            'free_shipping'    => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                          ],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_order' => ['xlink_resource' => 'orders'],
        ],
    ];
}
