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

    /** @var string */
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
            'id_order'               => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',     'required' => true],
            'id_carrier'             => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',     'required' => true],
            'id_order_invoice'       => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                        ],
            'weight'                 => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                             ],
            'shipping_cost_tax_excl' => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                             ],
            'shipping_cost_tax_incl' => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                             ],
            'tracking_number'        => ['type' => self::TYPE_STRING, 'validate' => 'isTrackingNumber', 'size' => 64],
            'date_add'               => ['type' => self::TYPE_DATE,   'validate' => 'isDate', 'dbNullable' => false],
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

    public function updateTrackingNumber($trackingNumber, $sendMail = true, &$errors = []) {
        if (!Validate::isTrackingNumber(Tools::getValue('tracking_number'))) {
            $errors[] = Tools::displayError('The tracking number is incorrect.');
            return false;
        }
        // update shipping number
        // Keep these two following lines for backward compatibility, remove on 1.6 version
        $orderObject = new Order($this->id_order);
        if($orderObject->id){
            $orderObject->shipping_number = $trackingNumber;
            $orderObject->update();
        }

        // Update order_carrier
        $this->tracking_number = $trackingNumber;
        if ($this->update()) {
            // Send mail to customer
            $customer = new Customer((int) $orderObject->id_customer);
            $carrier = new Carrier((int) $orderObject->id_carrier, $orderObject->id_lang);
            if (!Validate::isLoadedObject($customer)) {
                throw new PrestaShopException('Can\'t load Customer object');
            }
            if (!Validate::isLoadedObject($carrier)) {
                throw new PrestaShopException('Can\'t load Carrier object');
            }
            $followup_url = $followup = str_replace('@', $this->tracking_number, $carrier->url);
            if(empty($followup))
                $followup = $this->tracking_number;
            if(empty($followup_url))
                $followup_url = '#';
            $templateVars = [
                '{followup}'         => $followup,
                '{followup_url}'     => $followup_url,
                '{firstname}'        => $customer->firstname,
                '{lastname}'         => $customer->lastname,
                '{id_order}'         => $orderObject->id,
                '{tracking_number}'  => $this->tracking_number,
                '{carrier_name}'     => $carrier->display_name,
                '{order_name}'       => $orderObject->getUniqReference(),
                '{bankwire_owner}'   => (string) Configuration::get('BANK_WIRE_OWNER'),
                '{bankwire_details}' => nl2br((string) Configuration::get('BANK_WIRE_DETAILS')),
                '{bankwire_address}' => nl2br((string) Configuration::get('BANK_WIRE_ADDRESS')),
            ];
            $sendMailResult = false; // flag if email was sent successfully
            if($sendMail){
                $sendMailResult = Mail::Send(
                    (int) $orderObject->id_lang,
                    'in_transit',
                    Mail::l('Package in transit', (int) $orderObject->id_lang),
                    $templateVars,
                    $customer->email,
                    $customer->firstname.' '.$customer->lastname,
                    null,
                    null,
                    null,
                    null,
                    _PS_MAIL_DIR_,
                    true,
                    (int) $orderObject->id_shop
                );
                if(!$sendMailResult){
                    $errors[] = Tools::displayError('An error occurred while sending an email to the customer.');
                }
            }
            Hook::triggerEvent(
                'actionOrderCarrierTrackingNumberUpdate',
                [
                    'orderObject' => $orderObject,
                    'customer' => $customer,
                    'carrier' => $carrier,
                    'sendMail' => $sendMail,
                    'sendMailResult' => $sendMailResult, // will be true if any module blocked sending via actionEmailSendBefore
                ],
                $orderObject->id_shop
            );
            return true;
        } else {
            $errors[] = Tools::displayError('The orderObject carrier cannot be updated.');
        }
        return false;
    }
}
