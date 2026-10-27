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
 * Class PasswordControllerCore
 */
class PasswordControllerCore extends FrontController
{
    const RESET_TOKEN_LIFETIME = 3600;

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
     */
    public function postProcess()
    {
        if (Tools::isSubmit('email')) {
            if (!($email = trim(Tools::getValue('email'))) || !Validate::isEmail($email)) {
                $this->errors[] = Tools::displayError('Invalid email address.');
            } else {
                $customer = new Customer();
                $customer->getByemail($email);
                if (!Validate::isLoadedObject($customer) || !static::refreshCustomerForReset($customer, $email)) {
                    $this->errors[] = Tools::displayError('There is no account registered for this email address.');
                } elseif (!$customer->active) {
                    $this->errors[] = Tools::displayError('You cannot regenerate the password for this account.');
                } elseif ((strtotime($customer->last_passwd_gen.'+'.($minTime = (int) Configuration::get('PS_PASSWD_TIME_FRONT')).' minutes') - time()) > 0) {
                    $this->errors[] = sprintf(Tools::displayError('You can regenerate your password only every %d minute(s)'), (int) $minTime);
                } else {
                    $url = $this->context->link->getPageLink('password', true, null, 'token='.static::createResetToken($customer).'&id_customer='.(int) $customer->id);
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
            if ($this->ajax) {
                $return = [
                    'hasError' => !empty($this->errors),
                    'errors'   => $this->errors,
                ];
                $this->ajaxDie(json_encode($return));
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
                        $this->errors[] = Tools::displayError('Password does not match value from confirmation field');
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
     * @throws PrestaShopException
     */
    public function initContent()
    {
        parent::initContent();
        if ($customer = $this->getCustomer()) {
            // Themes submit $customer->secure_key as the hidden reset credential.
            // Keep that form contract without exposing or changing the real secure key.
            $templateCustomer = clone $customer;
            $templateCustomer->secure_key = Tools::getValue('token');
            $this->context->smarty->assign(['customer' => $templateCustomer]);
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
        $db = Db::getInstance();
        if (!$db->execute('START TRANSACTION')) {
            $this->errors[] = Tools::displayError('An error occurred with your account, which prevents us from sending you a new password. Please report this issue using the contact form.');
            return;
        }

        $committed = false;
        try {
            $result = $db->query('SELECT * FROM `'._DB_PREFIX_.'customer` WHERE `id_customer` = '.(int)$customer->id.' LIMIT 1 FOR UPDATE');
            $record = $result ? $db->nextRow($result) : false;
            $token = Tools::getValue('token');
            if (!$record || !$record['active'] || !is_string($token) || !static::isValidResetToken($token, (int)$customer->id, $record['passwd']) ||
                strtotime($record['last_passwd_gen'].'+'.(int)Configuration::get('PS_PASSWD_TIME_FRONT').' minutes') > time()) {
                $this->errors[] = Tools::displayError('We cannot regenerate your password with the data you\'ve submitted.');
                return;
            }

            $customer->hydrate($record);
            $customer->passwd = Tools::hash($password);
            $customer->last_passwd_gen = date('Y-m-d H:i:s');
            if (!$customer->update() || !$db->execute('COMMIT')) {
                $this->errors[] = Tools::displayError('An error occurred with your account, which prevents us from sending you a new password. Please report this issue using the contact form.');
                return;
            }
            $committed = true;
        } finally {
            if (!$committed) {
                $db->execute('ROLLBACK');
            }
        }

        if ($committed) {
            Hook::triggerEvent('actionPasswordRenew', [
                'customer' => $customer,
                'password' => $password
            ]);
            $this->context->smarty->assign(['confirmation' => 1]);
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
        $idCustomer = Tools::getIntValue('id_customer');
        if ($token !== false && !is_string($token)) {
            throw new PrestaShopException(Tools::displayError('We cannot regenerate your password with the data you\'ve submitted.'));
        }
        if ($token && $idCustomer) {
            $customer = new Customer($idCustomer);
            $record = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'customer` WHERE `id_customer` = '.(int)$idCustomer);
            if (Validate::isLoadedObject($customer) && $record && static::isValidResetToken($token, $idCustomer, $record['passwd'])) {
                $customer->hydrate($record);
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

    /**
     * Create a short-lived reset link bound to the current password hash.
     */
    protected static function createResetToken(Customer $customer)
    {
        $issuedAt = time();
        $nonce = bin2hex(random_bytes(16));
        $data = 'password-reset.'.(int)$customer->id.'.'.$issuedAt.'.'.$nonce.'.'.$customer->passwd;
        return $issuedAt.'.'.$nonce.'.'.Tools::signature($data);
    }

    /**
     * Use the primary database for the state that authorizes an emailed link.
     */
    protected static function refreshCustomerForReset(Customer $customer, $email)
    {
        $record = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'customer` WHERE `id_customer` = '.(int)$customer->id.
            ' AND `email` = \''.pSQL($email).'\' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER).
            ' AND `deleted` = 0 AND `is_guest` = 0');
        if (!$record) {
            return false;
        }
        $customer->hydrate($record);
        return true;
    }

    /**
     * Password changes invalidate all previously issued reset links.
     */
    protected static function isValidResetToken($token, $idCustomer, $passwordHash)
    {
        if (!preg_match('/\A([1-9][0-9]{9,10})\.([0-9a-f]{32})\.([0-9a-f]{64})\z/', $token, $matches)) {
            return false;
        }
        $now = time();
        $issuedAt = (int)$matches[1];
        if ($issuedAt > $now || $issuedAt <= $now - static::RESET_TOKEN_LIFETIME) {
            return false;
        }

        $data = 'password-reset.'.(int)$idCustomer.'.'.$matches[1].'.'.$matches[2].'.'.$passwordHash;
        return hash_equals(Tools::signature($data), $matches[3]);
    }

}
