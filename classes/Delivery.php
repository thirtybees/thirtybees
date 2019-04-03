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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class DeliveryCore
 *
 * @since 1.0.0
 */
class DeliveryCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int */
    public $id_delivery;
    /** @var int * */
    public $id_shop;
    /** @var int * */
    public $id_shop_group;
    /** @var int */
    public $id_carrier;
    /** @var int */
    public $id_range_price;
    /** @var int */
    public $id_range_weight;
    /** @var int */
    public $id_zone;
    /** @var float */
    public $price;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'delivery',
        'primary' => 'id_delivery',
        'fields'  => [
            'id_shop'         => ['type' => self::TYPE_INT, 'size' => 10],
            'id_shop_group'   => ['type' => self::TYPE_INT, 'size' => 10],
            'id_carrier'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_range_price'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'dbNullable' => true],
            'id_range_weight' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'dbNullable' => true],
            'id_zone'         => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'price'           => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true],
        ],
        'keys' => [
            'delivery' => [
                'id_carrier'      => ['type' => ObjectModel::KEY, 'columns' => ['id_carrier', 'id_zone']],
                'id_range_price'  => ['type' => ObjectModel::KEY, 'columns' => ['id_range_price']],
                'id_range_weight' => ['type' => ObjectModel::KEY, 'columns' => ['id_range_weight']],
                'id_zone'         => ['type' => ObjectModel::KEY, 'columns' => ['id_zone']],
            ],
        ],
    ];
    protected $webserviceParameters = [
        'objectsNodeName' => 'deliveries',
        'fields'          => [
            'id_carrier'      => ['xlink_resource' => 'carriers'],
            'id_range_price'  => ['xlink_resource' => 'price_ranges'],
            'id_range_weight' => ['xlink_resource' => 'weight_ranges'],
            'id_zone'         => ['xlink_resource' => 'zones'],
        ],
    ];

    /**
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getFields()
    {
        $fields = parent::getFields();

        // @todo add null management in definitions
        if ($this->id_shop) {
            $fields['id_shop'] = (int) $this->id_shop;
        } else {
            $fields['id_shop'] = null;
        }

        if ($this->id_shop_group) {
            $fields['id_shop_group'] = (int) $this->id_shop_group;
        } else {
            $fields['id_shop_group'] = null;
        }

        return $fields;
    }
}
