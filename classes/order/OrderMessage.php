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
 * Class OrderMessageCore
 */
class OrderMessageCore extends ObjectModel
{
    /** @var string|string[] name name */
    public $name;

    /** @var string|string[] message content */
    public $message;

    /** @var string Object creation date */
    public $date_add;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'     => 'order_message',
        'primary'   => 'id_order_message',
        'multilang' => true,
        'fields'    => [
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
            'name'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128 ],
            'message'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isMessage', 'required' => true, 'size' => 1200],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'fields' => [
            'id'       => ['sqlId' => 'id_discount_type', 'xlink_resource' => 'order_message_lang'],
            'date_add' => ['sqlId' => 'date_add'],
        ],
    ];

    /**
     * @param int $idLang
     * @param \Order|int $order
     * @param \Customer $customer
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getOrderMessages($idLang, $order = null, $customer = null)
    {
        $orderMessages = Db::readOnly()->getArray('
		SELECT om.id_order_message, oml.name, oml.message
		FROM '._DB_PREFIX_.'order_message om
		LEFT JOIN '._DB_PREFIX_.'order_message_lang oml ON (oml.id_order_message = om.id_order_message)
		WHERE oml.id_lang = '.(int) $idLang.'
		ORDER BY name ASC');

        // Replace Shortcodes
        if ($order) {
            if (!Validate::isLoadedObject($order) && Validate::isUnsignedInt($order)) {
                $order = new Order($order);
            }

            if (!Validate::isLoadedObject($customer)) {
                $customer = new Customer($order->id_customer);
            }

            if (Validate::isLoadedObject($order) && Validate::isLoadedObject($customer)) {
                $orderMessages = self::replaceShortcodesInOrderMessages($orderMessages, $idLang, $order, $customer);
            }

        }

        return $orderMessages;
    }

    /**
     * @param array $orderMessages
     * @param int $idLang
     * @param \Order $order
     * @param \Customer $customer
     * @param bool $returnShortcodeList
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    private static function replaceShortcodesInOrderMessages($orderMessages, $idLang, $order, $customer, $returnShortcodeList = false) {

        $gender = new Gender($customer->id_gender, $idLang, $order->id_shop);
        $addressDelivery = new Address($order->id_address_delivery, $idLang);
        $addressInvoice = new Address($order->id_address_invoice, $idLang);

        $context = Context::getContext();

        $shortcodesList = [
            '[customer_firstname]' => $customer->firstname,
            '[customer_lastname]' => $customer->lastname,
            '[customer_gender]' => $gender->name,
            '[order_reference]' => $order->reference,
            '[order_date]' => Tools::displayDate($order->date_add),
            '[order_delivery_date]' => Tools::displayDate($order->delivery_date),
            '[order_total_paid_tax_incl]' => Tools::displayPrice($order->total_paid_tax_incl, $order->id_currency),
            '[order_total_paid_tax_excl]' => Tools::displayPrice($order->total_paid_tax_excl, $order->id_currency),
            '[order_address_delivery]' => AddressFormat::generateAddress($addressDelivery),
            '[order_address_invoice]' => AddressFormat::generateAddress($addressInvoice),
            '[employee_firstname]' => $context->employee->firstname,
            '[employee_lastname]' => $context->employee->lastname,
        ];

        if ($returnShortcodeList) {
            return array_keys($shortcodesList);
        }

        foreach ($orderMessages as &$orderMessage) {
            $orderMessage['message'] = str_replace(array_keys($shortcodesList), array_values($shortcodesList), $orderMessage['message']);
        }

        return $orderMessages;
    }

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function getShortcodeList() {
        return self::replaceShortcodesInOrderMessages([], Configuration::get('PS_LANG_DEFAULT'), new Order(), new Customer(), true);
    }
}
