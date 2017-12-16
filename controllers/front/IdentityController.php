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
 * Class IdentityControllerCore
 *
 * @since 1.0.0
 */
class IdentityControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
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
    // @codingStandardsIgnoreEnd

    /**
     * Initialize controller
     *
     * @since 1.0.0
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
     * @since 1.0.0
     */
    public function postProcess()
    {
        $originNewsletter = (bool) $this->customer->newsletter;

        if (Tools::isSubmit('submitIdentity')) {
            $email = trim(Tools::getValue('email'));

            if (Tools::getValue('months') != '' && Tools::getValue('days') != '' && Tools::getValue('years') != '') {
                $this->customer->birthday = (int) Tools::getValue('years').'-'.(int) Tools::getValue('months').'-'.(int) Tools::getValue('days');
            } elseif (Tools::getValue('months') == '' && Tools::getValue('days') == '' && Tools::getValue('years') == '') {
                $this->customer->birthday = null;
            } else {
                $this->errors[] = Tools::displayError('Invalid date of birth.');
            }

            if (!Validate::isEmail($email)) {
                $this->errors[] = Tools::displayError('This email address is not valid');
            } elseif ($this->customer->email != $email && Customer::customerExists($email, true)) {
                $this->errors[] = Tools::displayError('An account using this email address has already been registered.');
            } elseif (!Tools::getIsset('old_passwd') || !Customer::checkPassword($this->context->customer->id, Tools::getValue('old_passwd'))) {
                $this->errors[] = Tools::displayError('The password you entered is incorrect.');
            } elseif (Tools::getValue('passwd') != Tools::getValue('confirmation')) {
                $this->errors[] = Tools::displayError('The password and confirmation do not match.');
            } else {
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
                    $this->context->smarty->assign('confirmation', 1);
                } else {
                    $this->errors[] = Tools::displayError('The information cannot be updated.');
                }
            }
        } else {
            $_POST = array_map('stripslashes', $this->customer->getFields());
        }

        return $this->customer;
    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @since 1.0.0
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
                'HOOK_CUSTOMER_IDENTITY_FORM' => Hook::exec('displayCustomerIdentityForm'),
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
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_.'identity.css');
        $this->addJS(_PS_JS_DIR_.'validate.js');
    }
}
