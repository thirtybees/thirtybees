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
 */
class OrderCarrierCore extends ObjectModel
{
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

    /** @var float */
    public $shipping_cost_accounting;

    /** @var int */
    public $tracking_number;

    /** @var string Object creation date */
    public $date_add;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'order_carrier',
        'primary' => 'id_order_carrier',
        'primaryKeyDbType' => 'int(11)',
        'fields'  => [
            'id_order'                  => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',     'required' => true],
            'id_carrier'                => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',     'required' => true],
            'id_order_invoice'          => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                        ],
            'weight'                    => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                             ],
            'shipping_cost_tax_excl'    => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                             ],
            'shipping_cost_tax_incl'    => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                             ],
            'shipping_cost_accounting'  => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                             ],
            'tracking_number'           => ['type' => self::TYPE_STRING, 'validate' => 'isTrackingNumber', 'size' => 64],
            'date_add'                  => ['type' => self::TYPE_DATE,   'validate' => 'isDate', 'dbNullable' => false],
        ],
        'keys' => [
            'order_carrier' => [
                'id_carrier'       => ['type' => ObjectModel::KEY, 'columns' => ['id_carrier']],
                'id_order'         => ['type' => ObjectModel::KEY, 'columns' => ['id_order']],
                'id_order_invoice' => ['type' => ObjectModel::KEY, 'columns' => ['id_order_invoice']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'fields' => [
            'id_order'   => ['xlink_resource' => 'orders'  ],
            'id_carrier' => ['xlink_resource' => 'carriers'],
        ],
    ];

    /**
     * Set shipping_cost_accounting value
     *
     * @param object $carrier CarrierObject or id_carrier
     * @param float $shipping_cost Shipping cost paid by customer (default tax_excl)
     * @param int $id_currency ID Currency
     * @param float $conversionRate Order conversion rate
     *
     */
    public function setShippingCostAccounting($carrier, $shipping_cost, $id_country, $conversionRate) {

        if (!is_object($carrier) && Validate::isUnsignedId($carrier)) {
            $carrier = new Carrier($carrier);
        }

        if ($id_country==Configuration::get('PS_COUNTRY_DEFAULT')) {
            $fee_relative = Configuration::get('CONF_'.$carrier->id_reference.'_SHIP');
            $fee_absolute = Configuration::get('CONF_'.$carrier->id_reference.'_SHIP_FIXED');
        }
        else {
            $fee_relative = Configuration::get('CONF_'.$carrier->id_reference.'_SHIP_OVERSEAS');
            $fee_absolute = Configuration::get('CONF_'.$carrier->id_reference.'_SHIP_FIXED_OVERSEAS');
        }

        $this->shipping_cost_accounting = Tools::ps_round($fee_absolute*$conversionRate + ($fee_relative/100*$shipping_cost), 6);
    }
}
