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
    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'password';
    /** @var bool $auth */
    public $auth = false;
    // @codingStandardsIgnoreEnd

    /**
     * Start forms process
     *
     * @see FrontController::postProcess()
     *
     * @return void
     *
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
                    $mailParams = [
                        '{email}'     => $customer->email,
                        '{lastname}'  => $customer->lastname,
                        '{firstname}' => $customer->firstname,
                        '{url}'       => $this->context->link->getPageLink('password', true, null, 'token='.$customer->secure_key.'&id_customer='.(int) $customer->id),
                    ];
                    if (Mail::Send($this->context->language->id, 'password_query', Mail::l('Password query confirmation'), $mailParams, $customer->email, $customer->firstname.' '.$customer->lastname)) {
                        $this->context->smarty->assign(['confirmation' => 2, 'customer_email' => $customer->email]);
                    } else {
                        $this->errors[] = Tools::displayError('An error occurred while sending the email.');
                    }
                }
            }
        } elseif (($token = Tools::getValue('token')) && ($idCustomer = (int) Tools::getValue('id_customer'))) {
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
                    $this->errors[] = Tools::displayError('Customer account not found');
                } elseif (!$customer->active) {
                    $this->errors[] = Tools::displayError('You cannot regenerate the password for this account.');
                } elseif ((strtotime($customer->last_passwd_gen.'+'.(int) Configuration::get('PS_PASSWD_TIME_FRONT').' minutes') - time()) > 0) {
                    Tools::redirect('index.php?controller=authentication&error_regen_pwd');
                } else {
                    $customer->passwd = Tools::hash($password = Tools::passwdGen(MIN_PASSWD_LENGTH, 'RANDOM'));
                    $customer->last_passwd_gen = date('Y-m-d H:i:s', time());
                    if ($customer->update()) {
                        Hook::exec('actionPasswordRenew', ['customer' => $customer, 'password' => $password]);
                        $mailParams = [
                            '{email}'     => $customer->email,
                            '{lastname}'  => $customer->lastname,
                            '{firstname}' => $customer->firstname,
                            '{passwd}'    => $password,
                        ];
                        if (Mail::Send($this->context->language->id, 'password', Mail::l('Your new password'), $mailParams, $customer->email, $customer->firstname.' '.$customer->lastname)) {
                            $this->context->smarty->assign(['confirmation' => 1, 'customer_email' => $customer->email]);
                        } else {
                            $this->errors[] = Tools::displayError('An error occurred while sending the email.');
                        }
                    } else {
                        $this->errors[] = Tools::displayError('An error occurred with your account, which prevents us from sending you a new password. Please report this issue using the contact form.');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('We cannot regenerate your password with the data you\'ve submitted.');
            }
        } elseif (Tools::getValue('token') || Tools::getValue('id_customer')) {
            $this->errors[] = Tools::displayError('We cannot regenerate your password with the data you\'ve submitted.');
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
     */
    public function initContent()
    {
        parent::initContent();
        $this->setTemplate(_PS_THEME_DIR_.'password.tpl');
    }
}
