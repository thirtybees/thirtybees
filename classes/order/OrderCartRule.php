<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class OrderCartRuleCore
 */
class OrderCartRuleCore extends ObjectModel
{
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

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'order_cart_rule',
        'primary' => 'id_order_cart_rule',
        'fields'  => [
            'id_order'         => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_cart_rule'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order_invoice' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '0', 'dbNullable' => true],
            'name'             => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 254],
            'value'            => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbDefault' => '0.000000'],
            'value_tax_excl'   => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbDefault' => '0.000000'],
            'free_shipping'    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        ],
        'keys' => [
            'order_cart_rule' => [
                'id_cart_rule' => ['type' => ObjectModel::KEY, 'columns' => ['id_cart_rule']],
                'id_order'     => ['type' => ObjectModel::KEY, 'columns' => ['id_order']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'fields' => [
            'id_order' => ['xlink_resource' => 'orders'],
        ],
    ];
}
