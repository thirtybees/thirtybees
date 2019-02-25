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
 * Class AdminOrderPreferencesControllerCore
 *
 * @since 1.0.0
 */
class AdminOrderPreferencesControllerCore extends AdminController
{
    /**
     * AdminOrderPreferencesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        // List of CMS tabs
        $cmsTab = [
            0 => [
                'id'   => 0,
                'name' => $this->l('None'),
            ],
        ];
        foreach (CMS::listCms($this->context->language->id) as $cmsFile) {
            $cmsTab[] = ['id' => $cmsFile['id_cms'], 'name' => $cmsFile['meta_title']];
        }

        // List of order process types
        $orderProcessType = [
            [
                'value' => PS_ORDER_PROCESS_STANDARD,
                'name'  => $this->l('Standard (Five steps)'),
            ],
            [
                'value' => PS_ORDER_PROCESS_OPC,
                'name'  => $this->l('One-page checkout'),
            ],
        ];

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('General'),
                'icon'   => 'icon-cogs',
                'fields' => [
                    'PS_ORDER_PROCESS_TYPE'          => [
                        'title'      => $this->l('Order process type'),
                        'hint'       => $this->l('Please choose either the five-step or one-page checkout process.'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => $orderProcessType,
                        'identifier' => 'value',
                    ],
                    'PS_GUEST_CHECKOUT_ENABLED'      => [
                        'title'      => $this->l('Enable guest checkout'),
                        'hint'       => $this->l('Allow guest visitors to place an order without registering.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_DISALLOW_HISTORY_REORDERING' => [
                        'title'      => $this->l('Disable Reordering Option'),
                        'hint'       => $this->l('Disable the option to allow customers to reorder in one click from the order history page (required in some European countries).'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_PURCHASE_MINIMUM'            => [
                        'title'      => $this->l('Minimum purchase total required in order to validate the order'),
                        'hint'       => $this->l('Set to 0 to disable this feature.'),
                        'validation' => 'isPrice',
                        'cast'       => 'priceval',
                        'type'       => 'price',
                    ],
                    'PS_ALLOW_MULTISHIPPING'         => [
                        'title'      => $this->l('Allow multishipping'),
                        'hint'       => $this->l('Allow the customer to ship orders to multiple addresses. This option will convert the customer\'s cart into one or more orders.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_SHIP_WHEN_AVAILABLE'         => [
                        'title'      => $this->l('Delayed shipping'),
                        'hint'       => $this->l('Allows you to delay shipping at your customers\' request. '),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_CONDITIONS'                  => [
                        'title'      => $this->l('Terms of service'),
                        'hint'       => $this->l('Require customers to accept or decline terms of service before processing an order.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                        'js'         => [
                            'on'  => 'onchange="changeCMSActivationAuthorization()"',
                            'off' => 'onchange="changeCMSActivationAuthorization()"',
                        ],
                    ],
                    'PS_CONDITIONS_CMS_ID'           => [
                        'title'      => $this->l('CMS page for the Conditions of use'),
                        'hint'       => $this->l('Choose the CMS page which contains your store\'s conditions of use.'),
                        'validation' => 'isInt',
                        'type'       => 'select',
                        'list'       => $cmsTab,
                        'identifier' => 'id',
                        'cast'       => 'intval',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'gift'    => [
                'title'  => $this->l('Gift options'),
                'icon'   => 'icon-gift',
                'fields' => [
                    'PS_GIFT_WRAPPING'                 => [
                        'title'      => $this->l('Offer gift wrapping'),
                        'hint'       => $this->l('Suggest gift-wrapping to customers.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_GIFT_WRAPPING_PRICE'           => [
                        'title'      => $this->l('Gift-wrapping price'),
                        'hint'       => $this->l('Set a price for gift wrapping.'),
                        'validation' => 'isPrice',
                        'cast'       => 'priceval',
                        'type'       => 'price',
                    ],
                    'PS_GIFT_WRAPPING_TAX_RULES_GROUP' => [
                        'title'      => $this->l('Gift-wrapping tax'),
                        'hint'       => $this->l('Set a tax for gift wrapping.'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => array_merge([['id_tax_rules_group' => 0, 'name' => $this->l('None')]], TaxRulesGroup::getTaxRulesGroups(true)),
                        'identifier' => 'id_tax_rules_group',
                    ],
                    'PS_RECYCLABLE_PACK'               => [
                        'title'      => $this->l('Offer recycled packaging'),
                        'hint'       => $this->l('Suggest recycled packaging to customer.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        if (!Configuration::get('PS_ALLOW_MULTISHIPPING')) {
            unset($this->fields_options['general']['fields']['PS_ALLOW_MULTISHIPPING']);
        }

        if (Configuration::get('PS_ATCP_SHIPWRAP')) {
            unset($this->fields_options['gift']['fields']['PS_GIFT_WRAPPING_TAX_RULES_GROUP']);
        }
    }

    /**
     * This method is called before we start to update options configuration
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function beforeUpdateOptions()
    {
        $sql = new DbQuery();
        $sql->select('`id_cms`');
        $sql->from('cms');
        $sql->where('`id_cms` = '.(int) Tools::getValue('PS_CONDITIONS_CMS_ID'));
        if (Tools::getValue('PS_CONDITIONS') && (Tools::getValue('PS_CONDITIONS_CMS_ID') == 0 || !Db::getInstance()->getValue($sql))) {
            $this->errors[] = Tools::displayError('Assign a valid CMS page if you want it to be read.');
        }
    }
}
