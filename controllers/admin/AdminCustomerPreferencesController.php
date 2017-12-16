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
 * Class AdminCustomerPreferencesControllerCore
 *
 * @since 1.0.0
 */
class AdminCustomerPreferencesControllerCore extends AdminController
{
    /**
     * AdminCustomerPreferencesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        $registrationProcessType = [
            [
                'value' => PS_REGISTRATION_PROCESS_STANDARD,
                'name'  => $this->l('Only account creation'),
            ],
            [
                'value' => PS_REGISTRATION_PROCESS_AIO,
                'name'  => $this->l('Standard (account creation and address creation)'),
            ],
        ];

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('General'),
                'icon'   => 'icon-cogs',
                'fields' => [
                    'PS_REGISTRATION_PROCESS_TYPE' => [
                        'title'      => $this->l('Registration process type'),
                        'hint'       => $this->l('The "Only account creation" registration option allows the customer to register faster, and create his/her address later.'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => $registrationProcessType,
                        'identifier' => 'value',
                    ],
                    'PS_ONE_PHONE_AT_LEAST'        => [
                        'title'      => $this->l('Phone number is mandatory'),
                        'hint'       => $this->l('If you chose yes, your customer will have to provide at least one phone number to register.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_CART_FOLLOWING'            => [
                        'title'      => $this->l('Re-display cart at login'),
                        'hint'       => $this->l('After a customer logs in, you can recall and display the content of his/her last shopping cart.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_CUSTOMER_CREATION_EMAIL'   => [
                        'title'      => $this->l('Send an email after registration'),
                        'hint'       => $this->l('Send an email with summary of the account information (email, password) after registration.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_PASSWD_TIME_FRONT'         => [
                        'title'      => $this->l('Password reset delay'),
                        'hint'       => $this->l('Minimum time required between two requests for a password reset.'),
                        'validation' => 'isUnsignedInt',
                        'cast'       => 'intval',
                        'size'       => 5,
                        'type'       => 'text',
                        'suffix'     => $this->l('minutes'),
                    ],
                    'PS_B2B_ENABLE'                => [
                        'title'      => $this->l('Enable B2B mode'),
                        'hint'       => $this->l('Activate or deactivate B2B mode. When this option is enabled, B2B features will be made available.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_CUSTOMER_NWSL'             => [
                        'title'      => $this->l('Enable newsletter registration'),
                        'hint'       => $this->l('Display or not the newsletter registration tick box.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_CUSTOMER_OPTIN'            => [
                        'title'      => $this->l('Enable opt-in'),
                        'hint'       => $this->l('Display or not the opt-in tick box, to receive offers from the store\'s partners.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];
    }

    /**
     * Update PS_B2B_ENABLE and enables / disables the associated tabs
     *
     * @param int $value Value of option
     *
     * @since 1.0.0
     */
    public function updateOptionPsB2bEnable($value)
    {
        $value = (int) $value;

        $tabsClassName = ['AdminOutstanding'];
        if (!empty($tabsClassName)) {
            foreach ($tabsClassName as $tabClassName) {
                $tab = Tab::getInstanceFromClassName($tabClassName);
                if (Validate::isLoadedObject($tab)) {
                    $tab->active = $value;
                    $tab->save();
                }
            }
        }
        Configuration::updateValue('PS_B2B_ENABLE', $value);
    }
}
