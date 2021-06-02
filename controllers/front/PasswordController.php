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
 * Class PasswordControllerCore
 *
 * @since 1.0.0
 */
class PasswordControllerCore extends FrontController
{

    /** @var string $php_self */
    public $php_self = 'password';

    /** @var bool $auth */
    public $auth = false;

    /** @var bool|Customer $customer */
    protected $customer;

    /**
     * Start forms process
     *
     * @see FrontController::postProcess()
     *
     * @return void
     *
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('email')) {
            if (!($email = trim(Tools::getValue('email'))) || !Validate::isEmail($email)) {
                $this->errors[] = Tools::displayError('Invalid email address.');
            } else {
                $customer = new Customer();
                $customer->getByemail($email);
                if (!Validate::isLoadedObject($customer)) {
                    $this->errors[] = Tools::displayError('There is no account registered for this email address.');
                } elseif (!$customer->active) {
                    $this->errors[] = Tools::displayError('You cannot regenerate the password for this account.');
                } elseif ((strtotime($customer->last_passwd_gen.'+'.($minTime = (int) Configuration::get('PS_PASSWD_TIME_FRONT')).' minutes') - time()) > 0) {
                    $this->errors[] = sprintf(Tools::displayError('You can regenerate your password only every %d minute(s)'), (int) $minTime);
                } else {
                    $url = $this->context->link->getPageLink('password', true, null, 'token='.$customer->secure_key.'&id_customer='.(int) $customer->id);
                    $mailParams = [
                        '{email}'     => $customer->email,
                        '{lastname}'  => $customer->lastname,
                        '{firstname}' => $customer->firstname,
                        '{url}'       => $url,
                    ];
                    if (Mail::Send($this->context->language->id, 'password_query', Mail::l('Password query confirmation'), $mailParams, $customer->email, $customer->firstname.' '.$customer->lastname)) {
                        $this->context->smarty->assign([
                            'confirmation' => 2,
                            'customer_email' => $customer->email
                        ]);
                    } else {
                        $this->errors[] = Tools::displayError('An error occurred while sending the email.');
                    }
                }
            }
        } elseif ($customer = $this->getCustomer()) {
            if ((strtotime($customer->last_passwd_gen.'+'.(int) Configuration::get('PS_PASSWD_TIME_FRONT').' minutes') - time()) > 0) {
                Tools::redirect('index.php?controller=authentication&error_regen_pwd');
            } else {
                $password = Tools::getValue('password');
                $confirm = Tools::getValue('confirm_password');
                if ($password) {
                    if (! Validate::isPasswd($password)) {
                        $this->errors[] = Tools::displayError('This password does not meet security criteria');
                    } elseif ($password != $confirm) {
                        $this->errors[] = Tools::displayError('Passwords does not match');
                    } else {
                        $this->setNewPassword($customer, $password);
                    }
                }
            }
        }
    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @return void
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function initContent()
    {
        parent::initContent();
        if ($customer = $this->getCustomer()) {
            $this->context->smarty->assign(['customer' => $customer]);
            $this->setTemplate(_PS_THEME_DIR_.'password-set.tpl');
        } else {
            $this->setTemplate(_PS_THEME_DIR_.'password.tpl');
        }
    }

    /**
     * Method that to set new password
     *
     * @param Customer $customer
     * @param string $password
     * @throws PrestaShopException
     */
    public function setNewPassword(Customer $customer, $password)
    {
        $customer->passwd = Tools::hash($password);
        $customer->last_passwd_gen = date('Y-m-d H:i:s', time());
        if ($customer->update()) {
            Hook::exec('actionPasswordRenew', [
                'customer' => $customer,
                'password' => $password
            ]);
            $mailParams = [
                '{email}' => $customer->email,
                '{lastname}' => $customer->lastname,
                '{firstname}' => $customer->firstname,
                '{passwd}'    => $password,
            ];
            if (Mail::Send($this->context->language->id, 'password', Mail::l('Your password has been changed'), $mailParams, $customer->email, $customer->firstname . ' ' . $customer->lastname)) {
                $this->context->smarty->assign(['confirmation' => 1]);
            } else {
                $this->errors[] = Tools::displayError('An error occurred while sending the email.');
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred with your account, which prevents us from sending you a new password. Please report this issue using the contact form.');
        }
    }

    /**
     * Returns customer that requested password reset, if possible
     *
     * @return bool|Customer
     */
    protected function getCustomer()
    {
        if (is_null($this->customer)) {
            try {
                $this->customer = static::resolveCustomer();
            } catch (PrestaShopException $e) {
                $this->customer = false;
                $this->errors[] = $e->getMessage();
            }
        }
        return $this->customer;
    }

    /**
     * Resolves customer from request parameters
     *
     * @return bool|Customer
     *
     * @throws PrestaShopException
     */
    protected static function resolveCustomer()
    {
        $token = Tools::getValue('token');
        $idCustomer = (int) Tools::getValue('id_customer');
        if ($token && $idCustomer) {
            $email = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('c.`email`')
                    ->from('customer', 'c')
                    ->where('c.`secure_key` = \''.pSQL($token).'\'')
                    ->where('c.`id_customer` = '.(int) $idCustomer)
            );
            if ($email) {
                $customer = new Customer();
                $customer->getByemail($email);
                if (!Validate::isLoadedObject($customer)) {
                    throw new PrestaShopException(Tools::displayError('Customer account not found'));
                }
                if (!$customer->active) {
                    throw new PrestaShopException(Tools::displayError('You cannot regenerate the password for this account.'));
                }
                return $customer;
            } else {
                throw new PrestaShopException(Tools::displayError('We cannot regenerate your password with the data you\'ve submitted.'));
            }
        }
        if ($token || $idCustomer) {
            throw new PrestaShopException(Tools::displayError('We cannot regenerate your password with the data you\'ve submitted.'));
        }
        return false;
    }

}
