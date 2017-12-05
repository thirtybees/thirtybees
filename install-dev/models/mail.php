<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 thirty bees
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
 *  @copyright 2017 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class InstallModelMail
 */
class InstallModelMail extends InstallAbstractModel
{
    // @codingStandardsIgnoreStart
    /** @var bool $smtp_checked */
    public $smtp_checked;
    /** @var string $server */
    public $server;
    /** @var string $login */
    public $login;
    /** @var string $password */
    public $password;
    /** @var int $port */
    public $port;
    /** @var string $encryption */
    public $encryption;
    /** @var string $email */
    public $email;
    // @codingStandardsIgnoreEnd

    /**
     * @param bool   $smtpChecked
     * @param string $server
     * @param string $login
     * @param string $password
     * @param int    $port
     * @param string $encryption
     * @param string $email
     */
    public function __construct($smtpChecked, $server, $login, $password, $port, $encryption, $email)
    {
        parent::__construct();

        $this->smtp_checked = $smtpChecked;
        $this->server = $server;
        $this->login = $login;
        $this->password = $password;
        $this->port = $port;
        $this->encryption = $encryption;
        $this->email = $email;
    }

    /**
     * Send a mail
     *
     * @param string $subject
     * @param string $content
     * @return bool|string false is everything was fine, or error string
     */
    public function send($subject, $content)
    {
        try {
            // Test with custom SMTP connection
            if ($this->smtp_checked) {
                // Retrocompatibility
                if (mb_strtolower($this->encryption) === 'off') {
                    $this->encryption = false;
                }
                $smtp = Swift_SmtpTransport::newInstance($this->server, $this->port, $this->encryption);
                $smtp->setUsername($this->login);
                $smtp->setpassword($this->password);
                $smtp->setTimeout(5);
                $swift = Swift_Mailer::newInstance($smtp);
            } else {
                // Test with normal PHP mail() call
                $swift = Swift_Mailer::newInstance(Swift_MailTransport::newInstance());
            }

            $message = Swift_Message::newInstance();

            $message
                ->setFrom($this->email)
                ->setTo('no-reply@'.Tools::getHttpHost(false, false, true))
                ->setSubject($subject)
                ->setBody($content);
            $message = new Swift_Message($subject, $content, 'text/html');
            if (@$swift->send($message)) {
                $result = true;
            } else {
                $result = 'Could not send message';
            }

            $swift->disconnect();
        } catch (Swift_SwiftException $e) {
            $result = $e->getMessage();
        }

        return $result;
    }
}
