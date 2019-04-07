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
 * Class MailCore
 *
 * @since 1.0.0
 */
class MailCore extends ObjectModel
{
    const TYPE_HTML = 1;
    const TYPE_TEXT = 2;
    const TYPE_BOTH = 3;

    // @codingStandardsIgnoreStart
    /** @var string Recipient */
    public $recipient;
    /** @var string Template */
    public $template;
    /** @var string Subject */
    public $subject;
    /** @var int Language ID */
    public $id_lang;
    /** @var int Timestamp */
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'mail',
        'primary' => 'id_mail',
        'fields'  => [
            'recipient' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'copy_post' => false, 'required' => true, 'size' => 126],
            'template'  => ['type' => self::TYPE_STRING, 'validate' => 'isTplName', 'copy_post' => false, 'required' => true, 'size' => 62],
            'subject'   => ['type' => self::TYPE_STRING, 'validate' => 'isMailSubject', 'copy_post' => false, 'required' => true, 'size' => 254],
            'id_lang'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false, 'required' => true],
            'date_add'  => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false, 'required' => true],
        ],
    ];

    /**
     * Send Email
     *
     * @param int    $idLang         Language ID of the email (to translate the template)
     * @param string $template       Template: the name of template not be a var but a string !
     * @param string $subject        Subject of the email
     * @param string $templateVars   Template variables for the email
     * @param string $to             To email
     * @param string $toName         To name
     * @param string $from           From email
     * @param string $fromName       To email
     * @param array  $fileAttachment Array with three parameters (content, mime and name). You can use an array of array to attach multiple files
     * @param bool   $mode_smtp      SMTP mode (deprecated)
     * @param string $templatePath   Template path
     * @param bool   $die            Die after error
     * @param int    $idShop         Shop ID
     * @param string $bcc            Bcc recipient (email address)
     * @param string $replyTo        Email address for setting the Reply-To header
     *
     * @return bool|int Whether sending was successful. If not at all, false, otherwise amount of recipients succeeded.
     * @throws PrestaShopException
     */
    public static function Send(
        $idLang,
        $template,
        $subject,
        $templateVars,
        $to,
        $toName = null,
        $from = null,
        $fromName = null,
        $fileAttachment = null,
        $modeSmtp = null,
        $templatePath = _PS_MAIL_DIR_,
        $die = false,
        $idShop = null,
        $bcc = null,
        $replyTo = null
    ) {

        // allow hooks to modify input parameters
        $result = Hook::exec('actionEmailSendBefore', [
            'idLang'          => &$idLang,
            'template'        => &$template,
            'subject'         => &$subject,
            'templateVars'    => &$templateVars,
            'to'              => &$to,
            'toName'          => &$toName,
            'from'            => &$from,
            'fromName'        => &$fromName,
            'fileAttachment'  => &$fileAttachment,
            'modeSmtp'        => &$modeSmtp,
            'templatePath'    => &$templatePath,
            'die'             => &$die,
            'idShop'          => &$idShop,
            'bcc'             => &$bcc,
            'replyTo'         => &$replyTo,
        ], null, true);

        // do NOT continue if any module returned false
        if (is_array($result) && in_array(false, $result, true)) {
            return true;
        }

        $idShop = (int)$idShop;
        $shop = Context::getContext()->shop;
        if ($idShop) {
            $shop = new Shop($idShop);
        }

        $configuration = Configuration::getMultiple(
            [
                'PS_SHOP_EMAIL',
                'PS_MAIL_METHOD',
                'PS_MAIL_SERVER',
                'PS_MAIL_USER',
                'PS_MAIL_PASSWD',
                'PS_SHOP_NAME',
                'PS_MAIL_SMTP_ENCRYPTION',
                'PS_MAIL_SMTP_PORT',
                'PS_MAIL_TYPE',
            ],
            null,
            null,
            $idShop
        );

        if (!isset($configuration['PS_MAIL_SMTP_ENCRYPTION']) || mb_strtolower($configuration['PS_MAIL_SMTP_ENCRYPTION']) === 'off') {
            $configuration['PS_MAIL_SMTP_ENCRYPTION'] = false;
        }
        if (!isset($configuration['PS_MAIL_SMTP_PORT'])) {
            $configuration['PS_MAIL_SMTP_PORT'] = 'default';
        }

        // Sending an e-mail can be of vital importance for the merchant, when his password is lost for example, so we must not die but do our best to send the e-mail

        if (!isset($from) || !Validate::isEmail($from)) {
            $from = $configuration['PS_SHOP_EMAIL'];
        }

        if (!Validate::isEmail($from)) {
            $from = null;
        }
        $from = Tools::convertEmailToIdn($from);

        // $from_name is not that important, no need to die if it is not valid
        if (!isset($fromName) || !Validate::isMailName($fromName)) {
            $fromName = $configuration['PS_SHOP_NAME'];
        }
        if (!Validate::isMailName($fromName)) {
            $fromName = null;
        }

        // It would be difficult to send an e-mail if the e-mail is not valid, so this time we can die if there is a problem
        if (!is_array($to) && !Validate::isEmail($to)) {
            return static::logError(Tools::displayError('Error: parameter "to" is corrupted'), $die);
        }
        if (is_array($to)) {
            foreach ($to as &$address) {
                $address = Tools::convertEmailToIdn($address);
            }
        } elseif (is_string($to)) {
            $to = Tools::convertEmailToIdn($to);
        }

        // if bcc is not null, make sure it's a vaild e-mail
        if (!is_null($bcc) && !is_array($bcc) && !Validate::isEmail($bcc)) {
            static::logError(Tools::displayError('Error: parameter "bcc" is corrupted'), $die);
            $bcc = null;
        }
        if (is_array($bcc)) {
            foreach ($bcc as &$address) {
                $address = Tools::convertEmailToIdn($address);
            }
        } elseif (is_string($bcc)) {
            $bcc = Tools::convertEmailToIdn($bcc);
        }

        if (!is_array($templateVars)) {
            $templateVars = [];
        }

        // Do not crash for this error, that may be a complicated customer name
        if (is_string($toName) && !empty($toName) && !Validate::isMailName($toName)) {
            $toName = null;
        }

        if (!Validate::isTplName($template)) {
            return static::logError(Tools::displayError('Error: invalid e-mail template'), $die);
        }

        if (!Validate::isMailSubject($subject)) {
            return static::logError(Tools::displayError('Error: invalid e-mail subject'), $die);
        }

        /* Construct multiple recipients list if needed */
        $message = Swift_Message::newInstance();
        if (is_array($to) && isset($to)) {
            foreach ($to as $key => $addr) {
                $addr = trim($addr);
                if (!Validate::isEmail($addr)) {
                    return static::logError(Tools::displayError('Error: invalid e-mail address'), $die);
                }

                if (is_array($toName) && isset($toName[$key])) {
                    $addrName = $toName[$key];
                } else {
                    $addrName = $toName;
                }

                $addrName = (($addrName == null || $addrName == $addr || !Validate::isGenericName($addrName)) ? '' : self::mimeEncode($addrName));
                $message->addTo($addr, $addrName);
            }
            $toPlugin = $to[0];
        } else {
            /* Simple recipient, one address */
            $toPlugin = $to;
            $toName = (($toName == null || $toName == $to) ? '' : static::mimeEncode($toName));
            $message->addTo($to, $toName);
        }

        if (isset($bcc) && is_array($bcc)) {
            foreach ($bcc as $addr) {
                $addr = trim($addr);
                if (!Validate::isEmail($addr)) {
                    return static::logError(Tools::displayError('Error: invalid e-mail address'), $die);
                }
                $message->addBcc($addr);
            }
        } elseif (isset($bcc)) {
            $message->addBcc($bcc);
        }

        try {
            /* Connect with the appropriate configuration */
            if ($configuration['PS_MAIL_METHOD'] == 2) {
                if (empty($configuration['PS_MAIL_SERVER']) || empty($configuration['PS_MAIL_SMTP_PORT'])) {
                    return static::logError(Tools::displayError('Error: invalid SMTP server or SMTP port'), $die);
                }

                $connection = Swift_SmtpTransport::newInstance($configuration['PS_MAIL_SERVER'], $configuration['PS_MAIL_SMTP_PORT'], $configuration['PS_MAIL_SMTP_ENCRYPTION'])
                    ->setUsername($configuration['PS_MAIL_USER'])
                    ->setPassword($configuration['PS_MAIL_PASSWD']);

            } else {
                $connection = Swift_MailTransport::newInstance();
            }

            if (!$connection) {
                return false;
            }
            $swift = Swift_Mailer::newInstance($connection);
            /* Get templates content */
            $iso = Language::getIsoById((int) $idLang);
            if (!$iso) {
                return static::logError(Tools::displayError('Error - No ISO code for email'), $die);
            }

            $sendTxtContent = $configuration['PS_MAIL_TYPE'] == Mail::TYPE_BOTH || $configuration['PS_MAIL_TYPE'] == Mail::TYPE_TEXT;
            $sendHtmlContent = $configuration['PS_MAIL_TYPE'] == Mail::TYPE_BOTH || $configuration['PS_MAIL_TYPE'] == Mail::TYPE_HTML;

            $templateHtml = '';
            $templateTxt = '';
            Hook::exec(
                'actionEmailAddBeforeContent', [
                'template'      => $template,
                'template_html' => &$templateHtml,
                'template_txt'  => &$templateTxt,
                'id_lang'       => (int) $idLang,
            ], null, true
            );
            // load html template content
            if ($sendHtmlContent) {
                $filePath = self::getTemplatePath($template, '.html', $iso, $shop, $templatePath);
                if (!$filePath) {
                    return static::logError(Tools::displayError('Html e-mail template is missing:') . ' ' . $template, $die);
                }
                $templateHtml .= file_get_contents($filePath);
            }

            // load txt template content
            if ($sendTxtContent) {
                $filePath = self::getTemplatePath($template, '.txt', $iso, $shop, $templatePath);
                if (!$filePath) {
                    return static::logError(Tools::displayError('Text e-mail template is missing:') . ' ' . $template, $die);
                }
                $templateTxt .= strip_tags(html_entity_decode(file_get_contents($filePath), null, 'utf-8'));
            }

            Hook::exec(
                'actionEmailAddAfterContent', [
                'template'      => $template,
                'template_html' => &$templateHtml,
                'template_txt'  => &$templateTxt,
                'id_lang'       => (int) $idLang,
            ], null, true
            );

            /* Create mail and attach differents parts */
            $subject = static::formatSubject($subject);
            $message->setSubject($subject);

            $message->setCharset('utf-8');

            /* Set Message-ID - getmypid() is blocked on some hosting */
            $message->setId(Mail::generateId());

            if (!($replyTo && Validate::isEmail($replyTo))) {
                $replyTo = $from;
            }

            if (isset($replyTo) && $replyTo) {
                $message->setReplyTo(Tools::convertEmailToIdn($replyTo));
            }

            $templateVars = array_map(['Tools', 'htmlentitiesDecodeUTF8'], $templateVars);
            $templateVars = array_map(['Tools', 'stripslashes'], $templateVars);

            if (Configuration::get('PS_LOGO_MAIL') !== false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO_MAIL', null, null, $idShop))) {
                $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO_MAIL', null, null, $idShop);
            } else {
                if (file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, $idShop))) {
                    $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, $idShop);
                } else {
                    $templateVars['{shop_logo}'] = '';
                }
            }
            ShopUrl::cacheMainDomainForShop((int) $idShop);
            /* don't attach the logo as */
            if (isset($logo)) {
                $templateVars['{shop_logo}'] = $message->embed(Swift_Image::fromPath($logo));
            }

            if ((Context::getContext()->link instanceof Link) === false) {
                Context::getContext()->link = new Link();
            }

            $templateVars['{shop_name}'] = Tools::safeOutput(Configuration::get('PS_SHOP_NAME', null, null, $idShop));
            $templateVars['{shop_url}'] = Context::getContext()->link->getPageLink('index', true, Context::getContext()->language->id, null, false, $idShop);
            $templateVars['{my_account_url}'] = Context::getContext()->link->getPageLink('my-account', true, Context::getContext()->language->id, null, false, $idShop);
            $templateVars['{guest_tracking_url}'] = Context::getContext()->link->getPageLink('guest-tracking', true, Context::getContext()->language->id, null, false, $idShop);
            $templateVars['{history_url}'] = Context::getContext()->link->getPageLink('history', true, Context::getContext()->language->id, null, false, $idShop);
            $templateVars['{color}'] = Tools::safeOutput(Configuration::get('PS_MAIL_COLOR', null, null, $idShop));
            // Get extra template_vars
            $extraTemplateVars = [];
            Hook::exec(
                'actionGetExtraMailTemplateVars', [
                'template'            => $template,
                'template_vars'       => $templateVars,
                'extra_template_vars' => &$extraTemplateVars,
                'id_lang'             => (int) $idLang,
            ], null, true
            );
            $templateVars = array_merge($templateVars, $extraTemplateVars);
            $swift->registerPlugin(new Swift_Plugins_DecoratorPlugin([$toPlugin => $templateVars]));
            if ($sendTxtContent) {
                $message->addPart($templateTxt, 'text/plain', 'utf-8');
            }
            if ($sendHtmlContent) {
                $message->addPart($templateHtml, 'text/html', 'utf-8');
            }
            if ($fileAttachment && !empty($fileAttachment)) {
                // Multiple attachments?
                if (!is_array(current($fileAttachment))) {
                    $fileAttachment = [$fileAttachment];
                }

                foreach ($fileAttachment as $attachment) {
                    if (isset($attachment['content']) && isset($attachment['name']) && isset($attachment['mime'])) {
                        $message->attach(Swift_Attachment::newInstance()->setFilename($attachment['name'])->setContentType($attachment['mime'])->setBody($attachment['content']));
                    }
                }
            }
            /* Send mail */
            $message->setFrom([$from => $fromName]);
            $shouldSend = $configuration['PS_MAIL_METHOD'] != 3;
            $send = $shouldSend ? $swift->send($message) : true;

            ShopUrl::resetMainDomainCache();

            if ($send && Configuration::get('PS_LOG_EMAILS')) {
                $mail = new Mail();
                $mail->template = mb_substr($template, 0, 62);
                $mail->subject = mb_substr($subject, 0, 254);
                $mail->id_lang = (int) $idLang;
                $recipientsTo = $message->getTo();
                $recipientsCc = $message->getCc();
                $recipientsBcc = $message->getBcc();
                if (!is_array($recipientsTo)) {
                    $recipientsTo = [];
                }
                if (!is_array($recipientsCc)) {
                    $recipientsCc = [];
                }
                if (!is_array($recipientsBcc)) {
                    $recipientsBcc = [];
                }
                foreach (array_merge($recipientsTo, $recipientsCc, $recipientsBcc) as $email => $recipientName) {
                    /** @var Swift_Address $recipient */
                    $mail->id = null;
                    $mail->recipient = mb_substr($email, 0, 126);
                    $mail->add();
                }
            }

            return $send;
        } catch (Swift_SwiftException $e) {
            Logger::addLog(
                'Swift Error: '.$e->getMessage(),
                3,
                null,
                'Swift_Message'
            );

            return false;
        }
    }

    /**
     * MIME encode the string
     *
     * @param string $string  The string to encode
     * @param string $charset The character set to use
     * @param string $newline The newline character(s)
     *
     * @return mixed|string MIME encoded string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function mimeEncode($string, $charset = 'UTF-8', $newline = "\r\n")
    {
        if (!static::isMultibyte($string) && mb_strlen($string) < 75) {
            return $string;
        }

        $charset = mb_strtoupper($charset);
        $start = '=?'.$charset.'?B?';
        $end = '?=';
        $sep = $end.$newline.' '.$start;
        $length = 75 - mb_strlen($start) - mb_strlen($end);
        $length = $length - ($length % 4);

        if ($charset === 'UTF-8') {
            $parts = [];
            $maxchars = floor(($length * 3) / 4);
            $stringLength = mb_strlen($string);

            while ($stringLength > $maxchars) {
                $i = (int) $maxchars;
                $result = ord($string[$i]);

                while ($result >= 128 && $result <= 191) {
                    $result = ord($string[--$i]);
                }

                $parts[] = base64_encode(mb_substr($string, 0, $i));
                $string = mb_substr($string, $i);
                $stringLength = mb_strlen($string);
            }

            $parts[] = base64_encode($string);
            $string = implode($sep, $parts);
        } else {
            $string = chunk_split(base64_encode($string), $length, $sep);
            $string = preg_replace('/'.preg_quote($sep).'$/', '', $string);
        }

        return $start.$string.$end;
    }

    /**
     * Check if a multibyte character set is used for the data
     *
     * @param string $data Data
     *
     * @return bool Whether the string uses a multibyte character set
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isMultibyte($data)
    {
        $length = mb_strlen($data);
        for ($i = 0; $i < $length; $i++) {
            if (ord(($data[$i])) > 128) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param null $idstring
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function generateId($idstring = null)
    {
        $midparams = [
            'utctime'   => gmstrftime('%Y%m%d%H%M%S'),
            'randint'   => mt_rand(),
            'customstr' => (preg_match("/^(?<!\\.)[a-z0-9\\.]+(?!\\.)\$/iD", $idstring) ? $idstring : "swift"),
            'hostname'  => ((isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : php_uname('n')),
        ];

        return vsprintf("%s.%d.%s@%s", $midparams);
    }

    /**
     * Format email subject using email subject template
     *
     * @param $subject Unformatted email subject
     *
     * @return string
     *
     * @since   1.0.8
     * @version 1.0.8 Initial version
     */
    protected static function formatSubject($subject)
    {
        $idShop = Context::getContext()->shop->id;
        $template = Configuration::get('TB_MAIL_SUBJECT_TEMPLATE', null, null, $idShop);
        if (! $template || strpos($template, '{subject}') === false) {
            $template = "[{shop_name}] {subject}";
        }
        if (preg_match_all('#\{[a-z0-9_]+\}#i', $template, $m)) {
            for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
                $key = $m[0][$i];
                switch ($key) {
                    case '{shop_name}':
                      $template = str_replace($key, Configuration::get('PS_SHOP_NAME', null, null, $idShop), $template);
                      break;
                    case '{subject}':
                      $template = str_replace($key, $subject, $template);
                      break;
                }
            }
        }
        return $template;
    }

    /**
     * @param int $idMail Mail ID
     *
     * @return bool Whether removal succeeded
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public static function eraseLog($idMail)
    {
        return Db::getInstance()->delete('mail', 'id_mail = '.(int) $idMail);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function eraseAllLogs()
    {
        return Db::getInstance()->execute('TRUNCATE TABLE '._DB_PREFIX_.'mail');
    }

    /**
     * Send a test email
     *
     * @param bool        $smtpChecked    Is SMTP checked?
     * @param string      $smtpServer     SMTP Server hostname
     * @param string      $content        Content of the email
     * @param string      $subject        Subject of the email
     * @param bool        $type           Deprecated
     * @param string      $to             To email address
     * @param string      $from           From email address
     * @param string      $smtpLogin      SMTP login name
     * @param string      $smtpPassword   SMTP password
     * @param int         $smtpPort       SMTP Port
     * @param bool|string $smtpEncryption Encryption type. "off" or false disable encryption.
     *
     * @return bool|string True if succeeded, otherwise the error message
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function sendMailTest($smtpChecked, $smtpServer, $content, $subject, $type, $to, $from, $smtpLogin, $smtpPassword, $smtpPort = 25, $smtpEncryption)
    {
        $result = false;
        try {
            if ($smtpChecked) {
                if (mb_strtolower($smtpEncryption) === 'off') {
                    $smtpEncryption = false;
                }
                $smtp = Swift_SmtpTransport::newInstance($smtpServer, $smtpPort, $smtpEncryption)
                    ->setUsername($smtpLogin)
                    ->setPassword($smtpPassword);
                $swift = Swift_Mailer::newInstance($smtp);
            } else {
                $swift = Swift_Mailer::newInstance(Swift_MailTransport::newInstance());
            }

            $message = Swift_Message::newInstance();

            $message
                ->setFrom(Tools::convertEmailToIdn($from))
                ->setTo(Tools::convertEmailToIdn($to))
                ->setSubject(static::formatSubject($subject))
                ->setBody($content);

            if ($swift->send($message)) {
                $result = true;
            }
        } catch (Swift_SwiftException $e) {
            $result = $e->getMessage();
        }

        return $result;
    }

    /**
     * This method is used to get the translation for email Object.
     * For an object is forbidden to use htmlentities,
     * we have to return a sentence with accents.
     *
     * @param string $string raw sentence (write directly in file)
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function l($string, $idLang = null, Context $context = null)
    {
        global $_LANGMAIL;

        if (!$context) {
            $context = Context::getContext();
        }
        if ($idLang == null) {
            $idLang = (!isset($context->language) || !is_object($context->language)) ? (int) Configuration::get('PS_LANG_DEFAULT') : (int) $context->language->id;
        }
        $isoCode = Language::getIsoById((int) $idLang);

        $fileCore = _PS_ROOT_DIR_.'/mails/'.$isoCode.'/lang.php';
        if (file_exists($fileCore) && empty($_LANGMAIL)) {
            include($fileCore);
        }

        $fileTheme = _PS_THEME_DIR_.'mails/'.$isoCode.'/lang.php';
        if (file_exists($fileTheme)) {
            include($fileTheme);
        }

        if (!is_array($_LANGMAIL)) {
            return (str_replace('"', '&quot;', $string));
        }

        $key = str_replace('\'', '\\\'', $string);

        return str_replace('"', '&quot;', Tools::stripslashes((array_key_exists($key, $_LANGMAIL) && !empty($_LANGMAIL[$key])) ? $_LANGMAIL[$key] : $string));
    }

    /**
     * This method finds file path for email template in given language. If template does not exists, it fallbacks
     * to english version. Returns null, if no email template can be used
     *
     * @param string $template template name
     * @param string $suffix template suffix, either .txt or .html
     * @param string $iso language iso code
     * @param Shop $shop shop for which we are sending email
     * @param string $baseTemplatePath base template path
     *
     * @return string | null
     * @since 1.1.0
     */
    protected static function getTemplatePath($template, $suffix, $iso, Shop $shop, $baseTemplatePath)
    {
        $relativePath = $iso . '/' . $template . $suffix;
        $themePath = _PS_ALL_THEMES_DIR_ . $shop->getTheme() . '/';

        // create candidate file paths list
        $paths = [];
        $moduleName = self::getModuleName($baseTemplatePath, $shop);
        if ($moduleName) {
            $paths[] = $themePath . 'modules/' . $moduleName . '/mails/' . $relativePath;
        }
        $paths[] = $themePath . 'mails/' . $relativePath;
        $paths[] = $baseTemplatePath . $relativePath;
        $paths[] = _PS_MAIL_DIR_ . $relativePath;
        $paths = array_unique($paths);

        // return first template file in paths
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // email template was not found, log missing template
        static::logMissingTemplate($template, $suffix, $iso, $paths);

        // If template wasn't found, let's try to fallback to english template
        if ($iso !== 'en') {
            return static::getTemplatePath($template, $suffix, 'en', $shop, $baseTemplatePath);
        }

        return null;
    }

    /**
     * Logs information about missing email template to system log
     *
     * @param string $template template name
     * @param string $suffix template suffix
     * @param string $iso language iso code
     * @param string[] $paths searched paths
     */
    private static function logMissingTemplate($template, $suffix, $iso, $paths)
    {
        $filename = $template . $suffix;
        $localPaths = array_map(function($path) {
            return str_replace(_PS_ROOT_DIR_, '', $path);
        }, $paths);
        Logger::addLog(sprintf(
            'Email template %s for language %s not found in [%s]',
            $filename,
            $iso,
            join(', ', $localPaths)
        ), 3);
    }

    /**
     * Derives module name from template path
     *
     * @param string $baseTemplatePath
     * @param Shop $shop
     *
     * @return string | null
     * @since 1.1.0
     */
    private static function getModuleName($baseTemplatePath, Shop $shop)
    {
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $baseTemplatePath);
        $res = [];
        if (preg_match('#' . $shop->physical_uri . 'modules/#', $path) &&
            preg_match('#modules/([a-z0-9_-]+)/#ui', $path, $res)) {
            return $res[1];
        }
        return null;
    }

    /**
     * This method logs an error message and optionally terminates execution of
     * the script.
     *
     * @param string $message Error message to be logged.
     * @param bool   $die     Wether to die instead of returning.
     *
     * @return false This method always, if it returns, returns false.
     *
     * @since   1.0.7
     * @version 1.0.7 Initial version
     * @throws PrestaShopException
     */
    private static function logError($message, $die)
    {
      Logger::addLog($message, 3);
      if ($die) {
        die($message);
      } else {
        return false;
      }
    }
}
