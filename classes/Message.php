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
 * Class MessageCore
 *
 * @since 1.0.0
 */
class MessageCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var string message content */
    public $message;
    /** @var int Cart ID (if applicable) */
    public $id_cart;
    /** @var int Order ID (if applicable) */
    public $id_order;
    /** @var int Customer ID (if applicable) */
    public $id_customer;
    /** @var int Employee ID (if applicable) */
    public $id_employee;
    /** @var bool Message is not displayed to the customer */
    public $private;
    /** @var string Object creation date */
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'message',
        'primary' => 'id_message',
        'fields'  => [
            'message'     => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 1600],
            'id_cart'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                                   ],
            'id_order'    => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                                   ],
            'id_customer' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                                   ],
            'id_employee' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                                   ],
            'private'     => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'date_add'    => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                                         ],
        ],
    ];

    /**
     * Return the last message from cart
     *
     * @param int $idCart Cart ID
     *
     * @return array Message
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMessageByCartId($idCart)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('message')
                ->where('`id_cart` = '.(int) $idCart)
        );
    }

    /**
     * Return messages from Order ID
     *
     * @param int          $idOrder Order ID
     * @param bool         $private return WITH private messages
     * @param Context|null $context
     *
     * @return array Messages
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMessagesByOrderId($idOrder, $private = false, Context $context = null)
    {
        if (!Validate::isBool($private)) {
            die(Tools::displayError());
        }

        if (!$context) {
            $context = Context::getContext();
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('m.*')
                ->select('c.`firstname` AS `cfirstname`, c.`lastname` AS `clastname`')
                ->select('e.`firstname` AS `efirstname`, e.`lastname` AS `elastname`')
                ->select('(COUNT(mr.`id_message`) = 0 AND m.`id_customer` != 0) AS `is_new_for_me`')
                ->from('message', 'm')
                ->leftJoin('customer', 'c', 'm.`id_customer` = c.`id_customer`')
                ->leftJoin('message_readed', 'mr', 'mr.`id_message` = m.`id_message`')
                ->leftOuterJoin('employee', 'e', 'e.`id_employee` = m.`id_employee` AND mr.`id_employee` = '.(isset($context->employee) ? (int)$context->employee->id : '\'\''))
                ->where('`id_order` = '.(int) $idOrder)
                ->where($private ? 'm.`private` = 0' : '')
                ->groupBy('m.`id_message`')
                ->orderBy('m.`date_add` DESC')
        );
    }

    /**
     * Return messages from Cart ID
     *
     * @param int          $idCart
     * @param bool         $private return WITH private messages
     * @param Context|null $context
     *
     * @return array Messages
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since    1.0.0
     * @version  1.0.0 Initial version
     */
    public static function getMessagesByCartId($idCart, $private = false, Context $context = null)
    {
        if (!Validate::isBool($private)) {
            die(Tools::displayError());
        }

        if (!$context) {
            $context = Context::getContext();
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('m.*')
                ->select('c.`firstname` AS `cfirstname`, c.`lastname` AS `clastname`')
                ->select('e.`firstname` AS `efirstname`, e.`lastname` AS `elastname`')
                ->from('message', 'm')
                ->leftJoin('customer', 'c', 'm.`id_customer` = c.`id_customer`')
                ->leftJoin('message_readed', 'mr', 'mr.`id_message` = m.`id_message`')
                ->leftOuterJoin('employee', 'e', 'e.`id_employee` = m.`id_employee`')
                ->where('mr.`id_employee` = '.(int) $context->employee->id)
                ->where('`id_cart` = '.(int) $idCart)
                ->where(!$private ? 'm.`private` = 0' : '')
                ->groupBy('m.`id_message`')
                ->orderBy('m.`date_add` DESC')
        );
    }

    /**
     * Registered a message 'readed'
     *
     * @param int $idMessage  Message ID
     * @param int $idEmployee Employee ID
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function markAsReaded($idMessage, $idEmployee)
    {
        if (!Validate::isUnsignedId($idMessage) || !Validate::isUnsignedId($idEmployee)) {
            die(Tools::displayError());
        }

        $result = Db::getInstance()->insert(
            'message_readed',
            [
                'id_message'  => (int) $idMessage,
                'id_employee' => (int) $idEmployee,
                'date_add'    => ['type' => 'sql', 'value' => 'NOW()'],
            ]
        );

        return $result;
    }
}
