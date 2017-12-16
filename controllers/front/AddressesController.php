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
 * Class AddressesControllerCore
 *
 * @since 1.0.0
 */
class AddressesControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'addresses';
    /** @var string $authRedirection */
    public $authRedirection = 'addresses';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    /**
     * Set default assets for this controller
     *
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();

        $this->addCSS(_THEME_CSS_DIR_.'addresses.css');
        $this->addJS(_THEME_JS_DIR_.'tools.js'); // retro compat themes 1.5
        $this->addJS(_THEME_JS_DIR_.'addresses.js');
    }

    /**
     * Initialize addresses controller
     *
     * @see   FrontController::init()
     *
     * @since 1.0.0
     */
    public function init()
    {
        parent::init();

        if (!Validate::isLoadedObject($this->context->customer)) {
            die(Tools::displayError('The customer could not be found.'));
        }
    }

    /**
     * Assign template vars related to page content
     *
     * @see   FrontController::initContent()
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        parent::initContent();

        $total = 0;
        $multipleAddressesFormatted = [];
        $orderedFields = [];
        $addresses = $this->context->customer->getAddresses($this->context->language->id);
        // @todo getAddresses() should send back objects
        foreach ($addresses as $detail) {
            $address = new Address($detail['id_address']);
            $multipleAddressesFormatted[$total] = AddressFormat::getFormattedLayoutData($address);
            unset($address);
            ++$total;

            // Retro theme < 1.4.2
            $orderedFields = AddressFormat::getOrderedAddressFields($detail['id_country'], false, true);
        }

        // Retro theme 1.4.2
        if ($key = array_search('Country:name', $orderedFields)) {
            $orderedFields[$key] = 'country';
        }

        $addressesStyle = [
            'company'      => 'address_company',
            'vat_number'   => 'address_company',
            'firstname'    => 'address_name',
            'lastname'     => 'address_name',
            'address1'     => 'address_address1',
            'address2'     => 'address_address2',
            'city'         => 'address_city',
            'country'      => 'address_country',
            'phone'        => 'address_phone',
            'phone_mobile' => 'address_phone_mobile',
            'alias'        => 'address_title',
        ];

        $this->context->smarty->assign(
            [
                'addresses_style'   => $addressesStyle,
                'multipleAddresses' => $multipleAddressesFormatted,
                'ordered_fields'    => $orderedFields,
                'addresses'         => $addresses, // retro compat themes 1.5ibility Theme < 1.4.1
            ]
        );

        $this->setTemplate(_PS_THEME_DIR_.'addresses.tpl');
    }
}
