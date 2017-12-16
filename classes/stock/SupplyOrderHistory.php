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
 * Class SupplyOrderHistoryCore
 *
 * @since 1.0.0
 */
class SupplyOrderHistoryCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @var int Supply order Id
     */
    public $id_supply_order;

    /**
     * @var int Employee Id
     */
    public $id_employee;

    /**
     * @var string The first name of the employee responsible of the movement
     */
    public $employee_firstname;

    /**
     * @var string The last name of the employee responsible of the movement
     */
    public $employee_lastname;

    /**
     * @var int State of the supply order
     */
    public $id_state;

    /**
     * @var string Date
     */
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'supply_order_history',
        'primary' => 'id_supply_order_history',
        'fields'  => [
            'id_supply_order'    => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'id_employee'        => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'employee_firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isName'                          ],
            'employee_lastname'  => ['type' => self::TYPE_STRING, 'validate' => 'isName'                          ],
            'id_state'           => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'date_add'           => ['type' => self::TYPE_DATE,  'validate' => 'isDate',        'required' => true],
        ],
    ];

    /**
     * @see ObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'objectsNodeName' => 'supply_order_histories',
        'objectNodeName' => 'supply_order_history',
        'fields' => [
            'id_supply_order' => ['xlink_resource' => 'supply_orders'],
            'id_employee' => ['xlink_resource' => 'employees'],
            'id_state' => ['xlink_resource' => 'supply_order_states'],
        ],
    ];
}
