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

use Thirtybees\Core\Mail\MailAddress;
use Thirtybees\Core\Mail\MailAttachement;
use Thirtybees\Core\Mail\MailTemplate;
use Thirtybees\Core\Mail\MailTransport;
use Thirtybees\Core\Mail\Template\SimpleMailTemplate;
use Thirtybees\Core\Mail\Transport\MailTransportNone;

/**
 * Class MailCore
 */
class MailCore extends ObjectModel
{
    const TYPE_HTML = 1;
    const TYPE_TEXT = 2;
    const TYPE_BOTH = 3;

    const TRANSPORT_NONE = 'core:none';

    const RECIPIENT_TYPE_TO = 'to';
    const RECIPIENT_TYPE_BCC = 'bcc';
    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table' => 'mail',
        'primary' => 'id_mail',
        'fields' => [
            'recipient_type' => ['type' => self::TYPE_STRING, 'copy_post' => false, 'required' => true, 'values' => [self::RECIPIENT_TYPE_TO, self::RECIPIENT_TYPE_BCC], 'dbDefault' => self::RECIPIENT_TYPE_TO],
            'recipient' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'copy_post' => false, 'required' => true, 'size' => 126],
            'from' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'copy_post' => false, 'required' => true, 'size' => 126],
            'template' => ['type' => self::TYPE_STRING, 'validate' => 'isTplName', 'copy_post' => false, 'required' => true, 'size' => 62],
            'subject' => ['type' => self::TYPE_STRING, 'validate' => 'isMailSubject', 'copy_post' => false, 'required' => true, 'size' => 254],
            'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false, 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false, 'required' => true, 'dbType' => 'timestamp', 'dbDefault' => ObjectModel::DEFAULT_CURRENT_TIMESTAMP],
        ],
        'keys' => [
            'mail' => [
                'recipient' => ['type' => ObjectModel::KEY, 'columns' => ['recipient'], 'subParts' => [10]],
            ],
        ],
    ];

    /**
     * @var string Recipient type
     */
    public $recipient_type = self::RECIPIENT_TYPE_TO;

    /**
     * @var string Recipient
     */
    public $recipient;

    /**
     * @var string From
     */
    public $from;

    /**
     * @var string Template
     */
    public $template;

    /**
     * @var string Subject
     */
    public $subject;

    /**
     * @var int Language ID
     */
    public $id_lang;

    /**
     * @var string Timestamp
     */
    public $date_add;

    /**
     * Send Email
     *
     * @param int $idLang Language ID of the email (to translate the template)
     * @param string $template Template: the name of template not be a var but a string !
     * @param string $subject Subject of the email
     * @param array $templateVars Template variables for the email
     * @param string|string[] $to To email
     * @param string|string[] $toName To name
     * @param string $from From email
     * @param string $fromName To email
     * @param array $fileAttachment Array with three parameters (content, mime and name). You can use an array of array to attach multiple files
     * @param bool $modeSmtp SMTP mode (deprecated)
     * @param string $templatePath Template path
     * @param bool $die Die after error
     * @param int $idShop Shop ID
     * @param string|string[]|null $bcc Bcc recipient (email address)
     * @param string $replyTo Email address for setting the Reply-To header
     *
     * @return bool Whether sending was successful
     *
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
    )
    {
        try {
            // allow hooks to modify input parameters
            $result = Hook::getResponses('actionEmailSendBefore', [
                'idLang' => &$idLang,
                'template' => &$template,
                'subject' => &$subject,
                'templateVars' => &$templateVars,
                'to' => &$to,
                'toName' => &$toName,
                'from' => &$from,
                'fromName' => &$fromName,
                'fileAttachment' => &$fileAttachment,
                'modeSmtp' => &$modeSmtp,
                'templatePath' => &$templatePath,
                'die' => &$die,
                'idShop' => &$idShop,
                'bcc' => &$bcc,
                'replyTo' => &$replyTo,
            ]);

            // do NOT continue if any module returned false
            if (in_array(false, $result, true)) {
                return true;
            }

            $idLang = (int)$idLang;

            // Resolve shop context
            if (!$idShop) {
                $shop = Context::getContext()->shop;
                $idShop = (int)$shop->id;
            } else {
                $idShop = (int)$idShop;
                $shop = new Shop($idShop);
            }

            // resolve addresses
            $fromAddress = static::getFromEmailAddress($from, $fromName, $idShop);
            $toAddresses = static::getToEmailAddresses($to, $toName);
            $bccAddresses = static::getBccEmailAddresses($bcc, $idShop);
            $replyTo = static::getReplyTo($replyTo, $fromAddress);

            // resolve template content
            $templates = static::getMailTemplates($template, $templatePath, $shop, $idLang);

            // resolve template variables
            $templateVars = static::getTemplateVars($template, $templateVars, $idShop, $idLang);

            // resolve subject
            $subject = static::formatSubject($subject, $idShop);

            $attachements = static::getFileAttachements($fileAttachment);


            // send email via transport
            $success = static::getTransport()->sendMail(
                $idShop,
                $idLang,
                $fromAddress,
                $toAddresses,
                $bccAddresses,
                $replyTo,
                $subject,
                $templates,
                $templateVars,
                $attachements
            );

            if ($success && Configuration::get(Configuration::LOG_EMAILS)) {
                foreach ($toAddresses as $address) {
                    static::logMail($fromAddress, static::RECIPIENT_TYPE_TO, $address, $template, $subject, $idLang);
                }
                foreach ($bccAddresses as $address) {
                    static::logMail($fromAddress, static::RECIPIENT_TYPE_BCC, $address, $template, $subject, $idLang);
                }
            }

            return $success;
        } catch (Throwable $e) {
            return static::handleError($die, $e);
        }
    }

    /**
     * Returns from email address
     *
     * @param string|null $from
     * @param string|null $fromName
     * @param int $idShop
     *
     * @return MailAddress
     *
     * @throws PrestaShopException
     */
    protected static function getFromEmailAddress($from, $fromName, $idShop): MailAddress
    {
        if (!Validate::isEmail($from)) {
            $from = Configuration::get(Configuration::SHOP_EMAIL, null, null, $idShop);
        }
        if (!isset($fromName) || !Validate::isMailName($fromName)) {
            $fromName = Configuration::get(Configuration::SHOP_NAME, null, null, $idShop);
        }
        return new MailAddress($from, $fromName);
    }

    /**
     * Resolve primary recipient addresses
     *
     * @param string|string[] $to
     * @param string|string[]|null $toName
     *
     * @return MailAddress[]
     *
     * @throws PrestaShopException
     */
    protected static function getToEmailAddresses($to, $toName)
    {
        $result = [];

        $to = static::toStringArray($to);

        if (!$to) {
            throw new PrestaShopException(Tools::displayError('Parameter "to" not provided'));
        } else {
            $toName = static::toStringArray($toName);
            foreach ($to as $key => $address) {
                if (Validate::isEmail($address)) {
                    $name = $toName[$key] ?? null;
                    $result[] = new MailAddress($address, $name);
                } else {
                    throw new PrestaShopException(Tools::displayError('Parameter "to" is corrupted'));
                }
            }
        }

        return $result;
    }

    /**
     * @param string|string[]|null $input
     *
     * @return string[]
     */
    private static function toStringArray($input)
    {
        if (is_null($input)) {
            return [];
        }

        if (is_string($input)) {
            return [$input];
        }

        if (is_array($input)) {
            return $input;
        }
        throw new RuntimeException('Invalid string array input');
    }

    /**
     * Resolve BCC email addresses
     *
     * @param string|string[]|null $bcc
     * @param int $idShop
     *
     * @return MailAddress[]
     *
     * @throws PrestaShopException
     */
    protected static function getBccEmailAddresses($bcc, $idShop)
    {
        $addresses = [];
        $bcc = static::toStringArray($bcc);
        foreach ($bcc as $address) {
            if (Validate::isEmail($address)) {
                $addresses[] = $address;
            } else {
                throw new PrestaShopException(Tools::displayError('Parameter "bcc" is corrupted'));
            }
        }

        // Check if there is any configuration for emails to add as BCC to all outgoing emails
        $bccAllMailsTo = Configuration::get('TB_BCC_ALL_MAILS_TO', null, null, $idShop);
        if (!empty($bccAllMailsTo)) {
            $bccAllMailsTo = explode(';', $bccAllMailsTo);
            foreach ($bccAllMailsTo as $address) {
                if (Validate::isEmail($address)) {
                    $addresses[] = $address;
                }
            }
        }

        return array_map(function ($address) {
            return new MailAddress($address, null);
        }, array_unique($addresses));
    }

    /**
     * Resolves reply-to address
     *
     * @param string|null $replyTo
     * @param MailAddress $fromAddress
     *
     * @return MailAddress
     */
    protected static function getReplyTo($replyTo, MailAddress $fromAddress)
    {
        if (Validate::isEmail($replyTo)) {
            return new MailAddress($replyTo, null);
        }
        return $fromAddress;
    }

    /**
     * Resolve template content
     *
     * @param string $template
     * @param string $templatePath
     * @param Shop $shop
     * @param int $idLang
     *
     * @return MailTemplate[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function getMailTemplates($template, string $templatePath, $shop, $idLang): array
    {
        if (!Validate::isTplName($template)) {
            throw new PrestaShopException(Tools::displayError('invalid e-mail template'));
        }

        $iso = Language::getIsoById((int)$idLang);
        if (!$iso) {
            throw new PrestaShopException(Tools::displayError('No ISO code for email'));
        }

        $mailType = (int)Configuration::get(Configuration::MAIL_TYPE, null, null, $shop->id);
        if (!in_array($mailType, [static::TYPE_BOTH, static::TYPE_TEXT, static::TYPE_HTML])) {
            $mailType = static::TYPE_BOTH;
        }
        $sendTxtContent = $mailType === static::TYPE_BOTH || $mailType === static::TYPE_TEXT;
        $sendHtmlContent = $mailType === static::TYPE_BOTH || $mailType === static::TYPE_HTML;

        $templateHtml = '';
        $templateTxt = '';
        Hook::triggerEvent('actionEmailAddBeforeContent', [
            'template' => $template,
            'template_html' => &$templateHtml,
            'template_txt' => &$templateTxt,
            'id_lang' => (int)$idLang,
        ]);

        // load html template content
        if ($sendHtmlContent) {
            $filePath = static::getTemplatePath($template, '.html', $iso, $shop, $templatePath);
            if ($filePath) {
                $templateHtml .= file_get_contents($filePath);
            }
        }

        // load txt template content
        if ($sendTxtContent) {
            $filePath = static::getTemplatePath($template, '.txt', $iso, $shop, $templatePath);
            if ($filePath) {
                $templateTxt .= strip_tags(html_entity_decode(file_get_contents($filePath), ENT_NOQUOTES, 'utf-8'));
            }
        }

        Hook::triggerEvent('actionEmailAddAfterContent', [
            'template' => $template,
            'template_html' => &$templateHtml,
            'template_txt' => &$templateTxt,
            'id_lang' => (int)$idLang,
        ]);

        $templates = [];
        if ($templateHtml) {
            $templates[] = new SimpleMailTemplate($template, 'text/html', $templateHtml);
        }
        if ($templateTxt) {
            $templates[] = new SimpleMailTemplate($template, 'text/plain', $templateTxt);
        }

        if (!$templates) {
            throw new PrestaShopException("No template");
        }

        return $templates;
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function getTemplatePath($template, $suffix, $iso, Shop $shop, $baseTemplatePath)
    {
        $relativePath = $iso . '/' . $template . $suffix;
        $themePath = _PS_ALL_THEMES_DIR_ . $shop->getTheme() . '/';

        // create candidate file paths list
        $paths = [];
        $moduleName = static::getModuleName($baseTemplatePath, $shop);
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
     * Derives module name from template path
     *
     * @param string $baseTemplatePath
     * @param Shop $shop
     *
     * @return string | null
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
     * Logs information about missing email template to system log
     *
     * @param string $template template name
     * @param string $suffix template suffix
     * @param string $iso language iso code
     * @param string[] $paths searched paths
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private static function logMissingTemplate($template, $suffix, $iso, $paths)
    {
        $filename = $template . $suffix;
        $localPaths = array_map(function ($path) {
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
     * Resolve template variables
     *
     * @param string $template
     * @param array|null $templateVars
     * @param int $idShop
     * @param int $idLang
     *
     * @return array
     * @throws PrestaShopException
     */
    protected static function getTemplateVars($template, $templateVars, $idShop, $idLang)
    {
        $link = Context::getContext()->link;
        if (!is_array($templateVars)) {
            $templateVars = [];
        } else {
            $templateVars = array_map(['Tools', 'htmlentitiesDecodeUTF8'], $templateVars);
        }

        $templateVars['{shop_logo}'] = [
            'type' => 'imageFile',
            'filepath' => static::getLogoFilePath($idShop)
        ];
        $templateVars['{shop_name}'] = Tools::safeOutput(Configuration::get('PS_SHOP_NAME', null, null, $idShop));
        $templateVars['{shop_url}'] = $link->getPageLink('index', true, $idLang, null, false, $idShop);
        $templateVars['{my_account_url}'] = $link->getPageLink('my-account', true, $idLang, null, false, $idShop);
        $templateVars['{guest_tracking_url}'] = $link->getPageLink('guest-tracking', true, $idLang, null, false, $idShop);
        $templateVars['{history_url}'] = $link->getPageLink('history', true, $idLang, null, false, $idShop);
        $templateVars['{color}'] = Tools::safeOutput(Configuration::get('PS_MAIL_COLOR', null, null, $idShop));

        // Get extra template_vars
        $extraTemplateVars = [];
        Hook::triggerEvent('actionGetExtraMailTemplateVars', [
            'template' => $template,
            'template_vars' => $templateVars,
            'extra_template_vars' => &$extraTemplateVars,
            'id_lang' => (int)$idLang,
        ]);

        $templateVars = array_merge($templateVars, $extraTemplateVars);
        return $templateVars;
    }

    /**
     * Returns path to logo file
     *
     * @param int $idShop
     *
     * @return string
     * @throws PrestaShopException
     */
    protected static function getLogoFilePath($idShop)
    {
        // return first logo
        foreach (['PS_LOGO_MAIL', 'PS_LOGO'] as $configKey) {
            $logo = Configuration::get($configKey, null, null, $idShop);
            if ($logo && file_exists(_PS_IMG_DIR_ . $logo)) {
                return _PS_IMG_DIR_ . $logo;
            }
        }
        // logo not found
        return '';
    }

    /**
     * Format email subject using email subject template
     *
     * @param string $subject email subject
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected static function formatSubject($subject, $idShop)
    {
        if (!Validate::isMailSubject($subject)) {
            throw new PrestaShopException(Tools::displayError('Error: invalid e-mail subject'));
        }

        $template = Configuration::get('TB_MAIL_SUBJECT_TEMPLATE', null, null, $idShop);
        if (!$template || strpos($template, '{subject}') === false) {
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
     * @return MailTransport
     * @throws PrestaShopException
     */
    public static function getTransport()
    {
        $transports = static::getAvailableTransports();
        return $transports[static::resolveSelectedTransport($transports)];
    }

    /**
     * Returns string identifier of selected email transport
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function getSelectedTransport()
    {
        return static::resolveSelectedTransport(static::getAvailableTransports());
    }

    /**
     * Returns string identifier of selected email transport
     *
     * @return string
     * @throws PrestaShopException
     */
    protected static function resolveSelectedTransport(array $transports)
    {
        $transports = static::getAvailableTransports();
        $selected = Configuration::get(Configuration::MAIL_TRANSPORT);
        if ($selected) {
            if (isset($transports[$selected])) {
                return $selected;
            } else {
                trigger_error("Mail transport '$selected' not found", E_USER_WARNING);
            }
        }
        return static::TRANSPORT_NONE;
    }

    /**
     * @return MailTransport[]
     *
     * @throws PrestaShopException
     */
    public static function getAvailableTransports()
    {
        $transports = null;
        if (is_null($transports)) {
            $transports = [
                static::TRANSPORT_NONE => new MailTransportNone()
            ];
            $res = Hook::getResponses('actionRegisterMailTransport');
            foreach ($res as $mod => $modTransports) {
                if (!is_array($modTransports)) {
                    $modTransports = ['default' => $modTransports];
                }
                foreach ($modTransports as $transportId => $transport) {
                    if ($transport instanceof MailTransport) {
                        $key = $mod . ':' . $transportId;
                        $transports[$key] = $transport;
                    } else {
                        trigger_error("Module $mod returned invalid mail transport: $transportId", E_USER_WARNING);
                    }
                }
            }
        }
        return $transports;
    }

    /**
     * @param int $idMail Mail ID
     *
     * @return bool Whether removal succeeded
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function eraseLog($idMail)
    {
        return Db::getInstance()->delete('mail', 'id_mail = ' . (int)$idMail);
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function eraseAllLogs()
    {
        return Db::getInstance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . 'mail');
    }

    /**
     * This method is used to get the translation for email Object.
     * For an object is forbidden to use htmlentities,
     * we have to return a sentence with accents.
     *
     * @param string $string raw sentence (write directly in file)
     * @param int|null $idLang
     * @param Context|null $context
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function l($string, $idLang = null, Context $context = null)
    {
        global $_LANGMAIL;

        if (!$context) {
            $context = Context::getContext();
        }
        if ($idLang == null) {
            $idLang = (!isset($context->language) || !is_object($context->language)) ? (int)Configuration::get('PS_LANG_DEFAULT') : (int)$context->language->id;
        }
        $isoCode = Language::getIsoById((int)$idLang);

        $fileCore = _PS_ROOT_DIR_ . '/mails/' . $isoCode . '/lang.php';
        if (file_exists($fileCore) && empty($_LANGMAIL)) {
            include($fileCore);
        }

        $fileTheme = _PS_THEME_DIR_ . 'mails/' . $isoCode . '/lang.php';
        if (file_exists($fileTheme)) {
            include($fileTheme);
        }

        if (!is_array($_LANGMAIL)) {
            return (str_replace('"', '&quot;', $string));
        }

        $key = str_replace('\'', '\\\'', $string);

        return str_replace('"', '&quot;', (array_key_exists($key, $_LANGMAIL) && !empty($_LANGMAIL[$key])) ? $_LANGMAIL[$key] : $string);
    }

    /**
     * @param MailAddress $fromAddress
     * @param string $recipientType
     * @param MailAddress $recipient
     * @param string $template
     * @param string $subject
     * @param int $idLang
     *
     * @throws PrestaShopException
     */
    protected static function logMail(MailAddress $fromAddress, string $recipientType, MailAddress $recipient, string $template, string $subject, int $idLang)
    {
        $mail = new static();
        $mail->recipient_type = $recipientType;
        $mail->recipient = mb_substr($recipient->getAddress(), 0, 126);
        $mail->from = mb_substr($fromAddress->getAddress(), 0, 126);
        $mail->template = mb_substr($template, 0, 62);
        $mail->subject = mb_substr($subject, 0, 254);
        $mail->id_lang = (int)$idLang;
        $mail->add();
    }

    /**
     * @param array|MailAttachement $input
     *
     * @return MailAttachement[]
     */
    protected static function getFileAttachements($input)
    {
        $attachments = [];

        if ($input instanceof MailAttachement) {
            $input = [ $input ];
        }

        if (is_array($input)) {
            if (isset($input['content'])) {
                $input = [ $input ];
            }
            foreach ($input as $attachment) {
                if ($attachment instanceof MailAttachement) {
                    $attachments[] = $attachment;
                } elseif (isset($attachment['content']) && isset($attachment['name']) && isset($attachment['mime'])) {
                    $attachments[] = new MailAttachement(
                        $attachment['content'],
                        $attachment['name'],
                        $attachment['mime']
                    );
                } else {
                    trigger_error("Warning: invalid file attachement: " . json_encode($attachment), E_USER_WARNING);
                }
            }
        }

        return $attachments;
    }

    /**
     * @param bool $die
     * @param Throwable $e
     *
     * @return false
     *
     * @throws PrestaShopException
     *
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected static function handleError($die, Throwable $e)
    {
        $message = 'Send Email Error: ' . $e->getMessage();
        Logger::addLog($message, 3, null, Logger::MAIL_ERROR, 0, true);
        if ($die) {
            if ($e instanceof PrestaShopException) {
                throw $e;
            } else {
                throw new PrestaShopException("Failed to send email", 0, $e);
            }
        } else {
            return false;
        }
    }
}
