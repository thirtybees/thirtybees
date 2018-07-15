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
 * Class AddressControllerCore
 *
 * @since 1.0.0
 */
class AddressControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var bool $auth */
    public $auth = true;
    /** @var bool $guestAllowed */
    public $guestAllowed = true;
    /** @var string $php_self */
    public $php_self = 'address';
    /** @var string $authRedirection */
    public $authRedirection = 'addresses';
    /** @var bool $ssl */
    public $ssl = true;
    /**
     * @var Address Current address
     *
     * @since 1.0.0
     */
    protected $_address;
    /** @var int $id_country */
    protected $id_country;
    // @codingStandardsIgnoreEnd

    /**
     * Set default medias for this controller
     *
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJS(
            [
                _THEME_JS_DIR_.'tools/vatManagement.js',
                _THEME_JS_DIR_.'tools/statesManagement.js',
                _PS_JS_DIR_.'validate.js',
            ]
        );
    }

    /**
     * Initialize address controller
     * @see FrontController::init()
     *
     * @since 1.0.0
     */
    public function init()
    {
        parent::init();

        // Get address ID
        $idAddress = 0;
        if ($this->ajax && Tools::isSubmit('type')) {
            if (Tools::getValue('type') == 'delivery' && isset($this->context->cart->id_address_delivery)) {
                $idAddress = (int) $this->context->cart->id_address_delivery;
            } elseif (Tools::getValue('type') == 'invoice' && isset($this->context->cart->id_address_invoice)
                        && $this->context->cart->id_address_invoice != $this->context->cart->id_address_delivery) {
                $idAddress = (int) $this->context->cart->id_address_invoice;
            }
        } else {
            $idAddress = (int) Tools::getValue('id_address', 0);
        }

        // Initialize address
        if ($idAddress) {
            $this->_address = new Address($idAddress);
            if (Validate::isLoadedObject($this->_address) && Customer::customerHasAddress($this->context->customer->id, $idAddress)) {
                if (Tools::isSubmit('delete')) {
                    if ($this->_address->delete()) {
                        if ($this->context->cart->id_address_invoice == $this->_address->id) {
                            unset($this->context->cart->id_address_invoice);
                        }
                        if ($this->context->cart->id_address_delivery == $this->_address->id) {
                            unset($this->context->cart->id_address_delivery);
                            $this->context->cart->updateAddressId($this->_address->id, (int) Address::getFirstCustomerAddressId($this->context->customer->id));
                        }
                        Tools::redirect('index.php?controller=addresses');
                    }
                    $this->errors[] = Tools::displayError('This address cannot be deleted.');
                }
            } elseif ($this->ajax) {
                exit;
            } else {
                Tools::redirect('index.php?controller=addresses');
            }
        }
    }

    /**
     * Start forms process
     *
     * @see FrontController::postProcess()
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitAddress')) {
            $this->processSubmitAddress();
        } elseif (!Validate::isLoadedObject($this->_address) && Validate::isLoadedObject($this->context->customer)) {
            $_POST['firstname'] = $this->context->customer->firstname;
            $_POST['lastname'] = $this->context->customer->lastname;
            $_POST['company'] = $this->context->customer->company;
        }
    }

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version.
     * @version 1.0.6 Use VatNumber::assignTemplateVars() and
     *                VatNumber::adjustAddressForLayout(), if present.
     */
    public function initContent()
    {
        parent::initContent();

        $this->assignCountries();
        $this->assignAddressFormat();

        if (Module::isInstalled('vatnumber')
            && Module::isEnabled('vatnumber')
            && file_exists(_PS_MODULE_DIR_.'vatnumber/vatnumber.php')) {
            include_once _PS_MODULE_DIR_.'vatnumber/vatnumber.php';

            if (method_exists('VatNumber', 'adjustAddressForLayout')) {
                VatNumber::adjustAddressForLayout($this->_address);
                VatNumber::assignTemplateVars($this->context);
            } else {
                // Retrocompatibility for module version < 2.1.0 (07/2018).
                $this->assignVatNumber();
            }
        }

        // Assign common vars
        $this->context->smarty->assign(
            [
                'address_validation' => Address::$definition['fields'],
                'one_phone_at_least' => (int) Configuration::get('PS_ONE_PHONE_AT_LEAST'),
                'onr_phone_at_least' => (int) Configuration::get('PS_ONE_PHONE_AT_LEAST'), //retro compat
                'ajaxurl'            => _MODULE_DIR_,
                'errors'             => $this->errors,
                'token'              => Tools::getToken(false),
                'select_address'     => (int) Tools::getValue('select_address'),
                'address'            => $this->_address,
                'id_address'         => (Validate::isLoadedObject($this->_address)) ? $this->_address->id : 0,
            ]
        );

        if ($back = Tools::getValue('back')) {
            $this->context->smarty->assign('back', Tools::safeOutput($back));
        }
        if ($mod = Tools::getValue('mod')) {
            $this->context->smarty->assign('mod', Tools::safeOutput($mod));
        }
        if (isset($this->context->cookie->account_created)) {
            $this->context->smarty->assign('account_created', 1);
            unset($this->context->cookie->account_created);
        }

        $this->setTemplate(_PS_THEME_DIR_.'address.tpl');
    }

    /**
     * @since 1.0.0
     */
    public function displayAjax()
    {
        if (count($this->errors)) {
            $return = [
                'hasError' => !empty($this->errors),
                'errors'   => $this->errors,
            ];
            $this->ajaxDie(json_encode($return));
        }
    }

    /**
     * Process changes on an address
     *
     * @since 1.0.0
     */
    protected function processSubmitAddress()
    {
        $address = new Address();
        $this->errors = $address->validateController();
        $address->id_customer = (int) $this->context->customer->id;

        // Check page token
        if ($this->context->customer->isLogged() && !$this->isTokenValid()) {
            $this->errors[] = Tools::displayError('Invalid token.');
        }

        // Check phone
        if (Configuration::get('PS_ONE_PHONE_AT_LEAST') && !Tools::getValue('phone') && !Tools::getValue('phone_mobile')) {
            $this->errors[] = Tools::displayError('You must register at least one phone number.');
        }
        if ($address->id_country) {
            // Check country
            if (!($country = new Country($address->id_country)) || !Validate::isLoadedObject($country)) {
                throw new PrestaShopException('Country cannot be loaded with address->id_country');
            }

            if ((int) $country->contains_states && !(int) $address->id_state) {
                $this->errors[] = Tools::displayError('This country requires you to chose a State.');
            }

            if (!$country->active) {
                $this->errors[] = Tools::displayError('This country is not active.');
            }

            $postcode = Tools::getValue('postcode');
            /* Check zip code format */
            if ($country->zip_code_format && !$country->checkZipCode($postcode)) {
                $this->errors[] = sprintf(Tools::displayError('The Zip/Postal code you\'ve entered is invalid. It must follow this format: %s'), str_replace('C', $country->iso_code, str_replace('N', '0', str_replace('L', 'A', $country->zip_code_format))));
            } elseif (empty($postcode) && $country->need_zip_code) {
                $this->errors[] = Tools::displayError('A Zip/Postal code is required.');
            } elseif ($postcode && !Validate::isPostCode($postcode)) {
                $this->errors[] = Tools::displayError('The Zip/Postal code is invalid.');
            }

            // Check country DNI
            if ($country->isNeedDni() && (!Tools::getValue('dni') || !Validate::isDniLite(Tools::getValue('dni')))) {
                $this->errors[] = Tools::displayError('The identification number is incorrect or has already been used.');
            } elseif (!$country->isNeedDni()) {
                $address->dni = null;
            }
        }
        // Check if the alias exists
        if (!$this->context->customer->is_guest && !empty($_POST['alias']) && (int) $this->context->customer->id > 0) {
            $idAddress = Tools::getValue('id_address');
            if (Configuration::get('PS_ORDER_PROCESS_TYPE') && (int) Tools::getValue('opc_id_address_'.Tools::getValue('type')) > 0) {
                $idAddress = Tools::getValue('opc_id_address_'.Tools::getValue('type'));
            }

            if (Address::aliasExist(Tools::getValue('alias'), (int) $idAddress, (int) $this->context->customer->id)) {
                $this->errors[] = sprintf(Tools::displayError('The alias "%s" has already been used. Please select another one.'), Tools::safeOutput(Tools::getValue('alias')));
            }
        }

        // Check the requires fields which are settings in the BO
        $this->errors = array_merge($this->errors, $address->validateFieldsRequiredDatabase());

        // Don't continue this process if we have errors !
        if (!empty($this->errors) && !$this->ajax) {
            return;
        }

        // If we edit this address, delete old address and create a new one
        if (Validate::isLoadedObject($this->_address)) {
            if (isset($country) && Validate::isLoadedObject($country) && !$country->contains_states) {
                $address->id_state = 0;
            }
            $addressOld = $this->_address;
            if (Customer::customerHasAddress($this->context->customer->id, (int) $addressOld->id)) {
                if ($addressOld->isUsed()) {
                    $addressOld->delete();
                } else {
                    $address->id = (int) $addressOld->id;
                    $address->date_add = $addressOld->date_add;
                }
            }
        }

        if ($this->ajax && Configuration::get('PS_ORDER_PROCESS_TYPE')) {
            $this->errors = array_unique(array_merge($this->errors, $address->validateController()));
            if (count($this->errors)) {
                $return = [
                    'hasError' => (bool) $this->errors,
                    'errors'   => $this->errors,
                ];
                $this->ajaxDie(json_encode($return));
            }
        }

        // Save address
        if ($address->save()) {
            // Update id address of the current cart if necessary
            if (isset($addressOld) && $addressOld->isUsed()) {
                $this->context->cart->updateAddressId($addressOld->id, $address->id);
            } else { // Update cart address
                $this->context->cart->autosetProductAddress();
            }

            if (Tools::getValue('select_address', false) || (Tools::getValue('type') == 'invoice' && Configuration::get('PS_ORDER_PROCESS_TYPE'))) {
                $this->context->cart->id_address_invoice = (int) $address->id;
            } elseif (Configuration::get('PS_ORDER_PROCESS_TYPE')) {
                $this->context->cart->id_address_invoice = (int) $this->context->cart->id_address_delivery;
            }
            $this->context->cart->update();

            if ($this->ajax) {
                $return = [
                    'hasError'            => (bool) $this->errors,
                    'errors'              => $this->errors,
                    'id_address_delivery' => (int) $this->context->cart->id_address_delivery,
                    'id_address_invoice'  => (int) $this->context->cart->id_address_invoice,
                ];
                $this->ajaxDie(json_encode($return));
            }

            // Redirect to old page or current page
            if ($back = Tools::getValue('back')) {
                if ($back == Tools::secureReferrer(Tools::getValue('back'))) {
                    Tools::redirect(html_entity_decode($back));
                }
                $mod = Tools::getValue('mod');
                Tools::redirect('index.php?controller='.$back.($mod ? '&back='.$mod : ''));
            } else {
                Tools::redirect('index.php?controller=addresses');
            }
        }
        $this->errors[] = Tools::displayError('An error occurred while updating your address.');
    }

    /**
     * Assign template vars related to countries display
     *
     * @since 1.0.0
     */
    protected function assignCountries()
    {
        $this->id_country = (int) Tools::getCountry($this->_address);
        // Generate countries list
        if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')) {
            $countries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        } else {
            $countries = Country::getCountries($this->context->language->id, true);
        }

        // @todo use helper
        $list = '';
        foreach ($countries as $country) {
            $selected = ((int) $country['id_country'] === $this->id_country) ? ' selected="selected"' : '';
            $list .= '<option value="'.(int) $country['id_country'].'"'.$selected.'>'.htmlentities($country['name'], ENT_COMPAT, 'UTF-8').'</option>';
        }

        // Assign vars
        $this->context->smarty->assign(
            [
                'countries_list' => $list,
                'countries'      => $countries,
                'sl_country'     => (int) $this->id_country,
            ]
        );
    }

    /**
     * Assign template vars related to address format
     *
     * @since 1.0.0
     */
    protected function assignAddressFormat()
    {
        $idCountry = is_null($this->_address) ? (int) $this->id_country : (int) $this->_address->id_country;
        $requireFormFieldsList = AddressFormat::getFieldsRequired();
        $orderedAdrFields = AddressFormat::getOrderedAddressFields($idCountry, true, true);
        $orderedAdrFields = array_unique(array_merge($orderedAdrFields, $requireFormFieldsList));

        $this->context->smarty->assign(
            [
                'ordered_adr_fields' => $orderedAdrFields,
                'required_fields'    => $requireFormFieldsList,
            ]
        );
    }

    /**
     * Assign template vars related to vat number
     * For retrocompatibility with vatnumber module version < 2.1.0 (07/2018).
     *
     * @since 1.0.0
     * @deprecated 1.0.6 Moved into the vatnumber module,
     *                   VatNumber::assignTemplateVars().
     */
    protected function assignVatNumber()
    {
        $vatNumberExists = file_exists(_PS_MODULE_DIR_.'vatnumber/vatnumber.php');
        $vatNumberManagement = Configuration::get('VATNUMBER_MANAGEMENT');
        if ($vatNumberManagement && $vatNumberExists) {
            include_once(_PS_MODULE_DIR_.'vatnumber/vatnumber.php');
        }

        if ($vatNumberManagement && $vatNumberExists && VatNumber::isApplicable((int) Tools::getCountry())) {
            $vatDisplay = 2;
        } elseif ($vatNumberManagement) {
            $vatDisplay = 1;
        } else {
            $vatDisplay = 0;
        }

        $this->context->smarty->assign(
            [
                'vatnumber_ajax_call' => file_exists(_PS_MODULE_DIR_.'vatnumber/ajax.php'),
                'vat_display'         => $vatDisplay,
            ]
        );
    }
}
