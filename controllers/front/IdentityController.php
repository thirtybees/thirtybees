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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class IdentityControllerCore
 */
class IdentityControllerCore extends FrontController
{
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'identity';
    /** @var string $authRedirection */
    public $authRedirection = 'identity';
    /** @var bool $ssl */
    public $ssl = true;
    /** @var Customer */
    protected $customer;

    /**
     * Initialize controller
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function init()
    {
        parent::init();
        $this->customer = $this->context->customer;
    }

    /**
     * Start forms process
     *
     * @return Customer
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $originNewsletter = (bool) $this->customer->newsletter;

        if (Tools::isSubmit('submitIdentity')) {
            $email = trim(Tools::getValue('email'));
            $passwd = Tools::getValue('passwd');
            $passwdConfirmation = Tools::getValue('passwd_confirmation', Tools::getValue('confirmation')); // 'confirmation' is for backward compatibility

            if (Tools::getValue('birthday')) {
                $this->customer->birthday = Tools::getValue('birthday');
            }
            elseif (Tools::getValue('months') != '' && Tools::getValue('days') != '' && Tools::getValue('years') != '') {
                $this->customer->birthday = Tools::getIntValue('years').'-'.Tools::getIntValue('months').'-'.Tools::getIntValue('days');
            } elseif (Tools::getValue('months') == '' && Tools::getValue('days') == '' && Tools::getValue('years') == '') {
                $this->customer->birthday = null;
            } else {
                $this->errors[] = Tools::displayError('Invalid date of birth.');
            }

            if (!Validate::isEmail($email)) {
                $this->errors[] = Tools::displayError('This email address is not valid');
            } elseif ($this->customer->email != $email && Customer::customerExists($email, true)) {
                $this->errors[] = Tools::displayError('An account using this email address has already been registered.');
            } else {
                if ($this->customer->email != $email || $passwd || $passwdConfirmation) {
                    // If email or password value does change, the current password is required
                    if (!Tools::getIsset('old_passwd') || !Customer::checkPassword($this->context->customer->id, Tools::getValue('old_passwd'))) {
                        $this->errors[] = Tools::displayError('The password you entered is incorrect.');
                    } elseif ($passwd !== $passwdConfirmation) {
                        $this->errors[] = Tools::displayError('The password and confirmation do not match.');
                    }
                }
            }

            if (empty($this->errors)) {
                $prevIdDefaultGroup = $this->customer->id_default_group;

                // Merge all errors of this file and of the Object Model
                $this->errors = array_merge($this->errors, $this->customer->validateController());
            }

            if (!count($this->errors)) {
                $this->customer->id_default_group = isset($prevIdDefaultGroup) ? (int) $prevIdDefaultGroup : 3;
                $this->customer->firstname = Tools::ucwords($this->customer->firstname);

                if (Configuration::get('PS_B2B_ENABLE')) {
                    $this->customer->website = Tools::getValue('website'); // force update of website, even if box is empty, this allows user to remove the website
                    $this->customer->company = Tools::getValue('company');
                }

                if (!Tools::getIsset('newsletter')) {
                    $this->customer->newsletter = 0;
                } elseif (!$originNewsletter && Tools::getIsset('newsletter')) {
                    if ($moduleNewsletter = Module::getInstanceByName('blocknewsletter')) {
                        /** @var Blocknewsletter $moduleNewsletter */
                        if ($moduleNewsletter->active) {
                            $moduleNewsletter->confirmSubscription($this->customer->email);
                        }
                    }
                }

                if (!Tools::getIsset('optin')) {
                    $this->customer->optin = 0;
                }
                if (Tools::getValue('passwd')) {
                    $this->context->cookie->passwd = $this->customer->passwd;
                }
                if ($this->customer->update()) {
                    $this->context->cookie->customer_lastname = $this->customer->lastname;
                    $this->context->cookie->customer_firstname = $this->customer->firstname;
                    $this->context->cookie->email = $this->customer->email;
                    $this->context->smarty->assign('confirmation', 1);
                } else {
                    $this->errors[] = Tools::displayError('The information cannot be updated.');
                }
            }
        } else {
            $customer_fields = array_map('stripslashes', $this->customer->getFields());
            $_POST = array_merge($_POST, $customer_fields);
        }

        return $this->customer;
    }

    /**
     * Assign template vars related to page content
     *
     * @throws PrestaShopException
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        if ($this->customer->birthday) {
            $birthday = explode('-', $this->customer->birthday);
        } else {
            $birthday = ['-', '-', '-'];
        }

        /* Generate years, months and days */
        $this->context->smarty->assign(
            [
                'years'    => Tools::dateYears(),
                'sl_year'  => $birthday[0],
                'months'   => Tools::dateMonths(),
                'sl_month' => $birthday[1],
                'days'     => Tools::dateDays(),
                'sl_day'   => $birthday[2],
                'errors'   => $this->errors,
                'genders'  => Gender::getGenders(),
            ]
        );

        // Call a hook to display more information
        $this->context->smarty->assign(
            [
                'HOOK_CUSTOMER_IDENTITY_FORM' => Hook::displayHook('displayCustomerIdentityForm'),
            ]
        );

        $newsletter = Configuration::get('PS_CUSTOMER_NWSL') || (Module::isInstalled('blocknewsletter') && Module::getInstanceByName('blocknewsletter')->active);
        $this->context->smarty->assign('newsletter', $newsletter);
        $this->context->smarty->assign('optin', (bool) Configuration::get('PS_CUSTOMER_OPTIN'));

        $this->context->smarty->assign('field_required', $this->context->customer->validateFieldsRequiredDatabase());

        $this->setTemplate(_PS_THEME_DIR_.'identity.tpl');
    }

    /**
     * Set media
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_.'identity.css');
        $this->addJS(_PS_JS_DIR_.'validate.js');
    }
}
