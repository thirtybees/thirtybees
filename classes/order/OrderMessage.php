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
     * @param Order|null $order
     * @param Customer|null $customer
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getOrderMessages($idLang, $order = null, $customer = null)
    {
        $idLang = (int)$idLang;

        $orderMessages = Db::readOnly()->getArray('
		SELECT om.id_order_message, oml.name, oml.message
		FROM '._DB_PREFIX_.'order_message om
		LEFT JOIN '._DB_PREFIX_.'order_message_lang oml ON (oml.id_order_message = om.id_order_message)
		WHERE oml.id_lang = '.(int) $idLang.'
		ORDER BY name ASC');

        // Replace Shortcodes
        if ($orderMessages) {
            $customer = static::resolveCustomer($customer, $order);
            return static::replaceShortcodesInOrderMessages($orderMessages, $idLang, $order, $customer);

        }
        return [];
    }

    /**
     * @param Customer|null $customer
     * @param Order|null $order
     *
     * @return Customer|null
     * @throws PrestaShopException
     */
    protected static function resolveCustomer(?Customer $customer, ?Order $order): ?Customer
    {
        if (Validate::isLoadedObject($customer)) {
            return $customer;
        }
        if ($order) {
            $customer = new Customer((int)$order->id_customer);
            if (Validate::isLoadedObject($customer)) {
                return $customer;
            }
        }
        return null;
    }

    /**
     * @param array $orderMessages
     * @param int $idLang
     * @param Order|null $order
     * @param Customer|null $customer
     * @param bool $returnShortcodeList
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    protected static function replaceShortcodesInOrderMessages(
        array $orderMessages,
        int $idLang,
        ?Order $order,
        ?Customer $customer,
        bool $returnShortcodeList = false
    ) {
        $genderName = '[customer_gender]';
        $customerFirstName = '[customer_firstname]';
        $customerLastName = '[customer_lastname]';
        $orderReference = '[order_reference]';
        $orderAddressDelivery = '[order_address_delivery]';
        $orderAddressInvoice = '[order_address_invoice]';
        $orderTotalPaidTaxIncl = '[order_total_paid_tax_incl]';
        $orderTotalPaidTaxExcl = '[order_total_paid_tax_excl]';
        $orderData = '[order_date]';
        $orderDeliveryDate = '[order_delivery_date]';

        if (Validate::isLoadedObject($customer)) {
            $gender = new Gender($customer->id_gender, $idLang, $order->id_shop);
            $genderName = $gender->name;
            $customerFirstName = $customer->firstname;
            $customerLastName = $customer->lastname;
        }
        if (Validate::isLoadedObject($order)) {
            $orderReference = $order->reference;
            $addressDelivery = new Address($order->id_address_delivery, $idLang);
            $addressInvoice = new Address($order->id_address_invoice, $idLang);
            $orderAddressDelivery = AddressFormat::generateAddress($addressDelivery);
            $orderAddressInvoice = AddressFormat::generateAddress($addressInvoice);
            $orderTotalPaidTaxIncl = Tools::displayPrice($order->total_paid_tax_incl, $order->id_currency);
            $orderTotalPaidTaxExcl = Tools::displayPrice($order->total_paid_tax_excl, $order->id_currency);
            $orderData = Tools::displayDate($order->date_add);
            $orderDeliveryDate = Tools::displayDate($order->delivery_date);
        }

        $context = Context::getContext();

        $shortcodesList = [
            '[customer_firstname]' => $customerFirstName,
            '[customer_lastname]' => $customerLastName,
            '[customer_gender]' => $genderName,
            '[order_reference]' => $orderReference,
            '[order_date]' => $orderData,
            '[order_delivery_date]' => $orderDeliveryDate,
            '[order_total_paid_tax_incl]' => $orderTotalPaidTaxIncl,
            '[order_total_paid_tax_excl]' => $orderTotalPaidTaxExcl,
            '[order_address_delivery]' => $orderAddressDelivery,
            '[order_address_invoice]' => $orderAddressInvoice,
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
    public static function getShortcodeList()
    {
        return static::replaceShortcodesInOrderMessages(
            [],
            (int)Configuration::get('PS_LANG_DEFAULT'),
            null,
            null,
            true
        );
    }
}
