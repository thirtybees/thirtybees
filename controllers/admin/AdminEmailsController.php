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

use Thirtybees\Core\DependencyInjection\ServiceLocator;
use Thirtybees\Core\Error\Response\JSendErrorResponse;

/**
 * Class AdminEmailsControllerCore
 */
class AdminEmailsControllerCore extends AdminController
{
    /**
     * AdminEmailsControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;

        if (Configuration::get('PS_LOG_EMAILS')) {
            $this->table = 'mail';
            $this->className = 'Mail';

            $this->lang = false;
            $this->noLink = true;
            $this->list_no_link = true;
            $this->addRowAction('delete');

            $this->bulk_actions = [
                'delete' => [
                    'text'    => $this->l('Delete selected'),
                    'confirm' => $this->l('Delete selected items?'),
                    'icon'    => 'icon-trash',
                ],
            ];

            $languages = [];
            foreach (Language::getLanguages() as $language) {
                $languages[$language['id_lang']] = $language['name'];
            }

            $this->fields_list = [
                'id_mail'   => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
                'from' => [ 'title' => $this->l('From') ],
                'recipient' => [
                    'title' => $this->l('Recipient'),
                    'callback_object' => $this,
                    'callback' => 'renderRecipient',
                ],
                'template'  => ['title' => $this->l('Template')],
                'language'  => [
                    'title'       => $this->l('Language'),
                    'type'        => 'select',
                    'color'       => 'color',
                    'list'        => $languages,
                    'filter_key'  => 'a!id_lang',
                    'filter_type' => 'int',
                    'order_key'   => 'language',
                ],
                'subject'   => ['title' => $this->l('Subject')],
                'date_add'  => [
                    'title' => $this->l('Sent'),
                    'type'  => 'datetime',
                ],
            ];
            $this->_select .= 'l.name as language';
            $this->_join .= ' LEFT JOIN '._DB_PREFIX_.'lang l ON (a.id_lang = l.id_lang)';
            $this->_use_found_rows = false;
        }

        parent::__construct();


        $transports = [];
        foreach (Mail::getAvailableTransports() as $key => $transport) {
            $transports[] = [
                'id' => $key,
                'name' => $transport->getName(),
                'hint' => $transport->getDescription(),
                'config' => $transport->getConfigUrl()
            ];
        }

        $this->fields_options = [
            'email' => [
                'title'  => $this->l('Sending options'),
                'icon'   => 'icon-envelope',
                'fields' => [
                    Configuration::MAIL_TRANSPORT        => [
                        'title'      => $this->l('Email transport'),
                        'validation' => 'isGenericName',
                        'hint'       => $this->l('Select method for sending emails. If none is available, install some email transport module'),
                        'type'       => 'select',
                        'required'   => true,
                        'identifier' => 'id',
                        'list'       => $transports,
                    ],
                    'PS_MAIL_TYPE'          => [
                        'title'      => $this->l('Format'),
                        'hint'       => $this->l('Select in what formats should your store send emails'),
                        'validation' => 'isGenericName',
                        'type'       => 'select',
                        'cast'       => 'intval',
                        'required'   => true,
                        'identifier' => 'format',
                        'list'    => [
                            [
                                'format' => Mail::TYPE_HTML,
                                'name' => $this->l('Send email in HTML format'),
                            ],
                            [
                                'format' => Mail::TYPE_TEXT,
                                'name' => $this->l('Send email in text format'),
                            ],
                            [
                                'format' => Mail::TYPE_BOTH,
                                'name' => $this->l('Send email as HTML and text'),
                            ]
                        ],
                    ],
                    'PS_LOG_EMAILS'         => [
                        'title'      => $this->l('Log Emails'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'TB_BCC_ALL_MAILS_TO'   => [
                        'title'      => $this->l('BCC all mails to'),
                        'desc'       => $this->l('Make sure you enter valid e-mail addresses separated by semicolons (;). Example: "account1@yourshop.com;account2@yourshop.com"'),
                        'validation' => 'isString',
                        'type'       => 'text',
                    ],
                    'TB_MAIL_SUBJECT_TEMPLATE'         => [
                        'title'      => $this->l('Email subject template'),
                        'desc'       => $this->l('You can use following placeholders: {subject} {shop_name}'),
                        'validation' => 'isString',
                        'type'       => 'text',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'test'  => [
                'title'                   => $this->l('Test your email configuration'),
                'hide_multishop_checkbox' => true,
                'fields'                  => [
                    'TB_SEND_TEST_EMAIL' => [
                        'title'                 => $this->l('Send a test email to'),
                        'type'                  => 'text',
                        'id'                    => 'testEmail',
                        'defaultValue'          => Configuration::get('PS_SHOP_EMAIL'),
                        'no_multishop_checkbox' => true,
                    ],
                ],
                'bottom'                  => '<div class="row"><div class="col-lg-9 col-lg-offset-3">
					<div class="alert" id="mailResultCheck" style="display:none;"></div>
				</div></div>',
                'buttons'                 => [
                    [
                        'title' => $this->l('Send a test email'),
                        'icon'  => 'process-icon-envelope',
                        'name'  => 'btEmailTest',
                        'js'    => 'sendTestEmail()',
                        'class' => 'btn btn-default pull-right',
                    ],
                ],
            ],

        ];
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJS(_PS_JS_DIR_.'validate.js');
    }


    /**
     * Process delete
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processDelete()
    {
        if ((int) $idMail = Tools::getValue('id_mail', 0)) {
            $return = Mail::eraseLog((int) $idMail);
        } else {
            $return = Mail::eraseAllLogs();
        }

        return $return;
    }

    /**
     * Initialize toolbar
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function initToolbar()
    {
        parent::initToolbar();
        $this->toolbar_btn['delete'] = [
            'short' => 'Erase',
            'desc'  => $this->l('Erase all'),
            'js'    => 'if (confirm(\''.$this->l('Are you sure?').'\')) document.location = \''.Tools::safeOutput($this->context->link->getAdminLink('AdminEmails')).'&amp;token='.$this->token.'&amp;deletemail=1\';',
        ];
        unset($this->toolbar_btn['new']);
    }

    /**
     * Before options update
     */
    public function beforeUpdateOptions()
    {
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');
            return;
        }

        if (isset($_POST['TB_BCC_ALL_MAILS_TO'])) {
            $bccMails = explode(';', $_POST['TB_BCC_ALL_MAILS_TO']);

            // Validate all emails
            $bccMailsAreValid = true;
            foreach ($bccMails as $index => $bccMail) {
                // Make a cleanup for spaces and tabs
                $bccMail = trim($bccMail);
                if ( ! $bccMail) {
                    // Empty string, double semicolons, whatever.
                    unset($bccMails[$index]);
                } elseif (Validate::isEmail($bccMail)) {
                    $bccMails[$index] = $bccMail;
                } else {
                    $bccMailsAreValid = false;

                    // There's no need to validate the remaining emails since
                    // we have at least one invalid email.
                    break;
                }
            }

            if ($bccMailsAreValid) {
                // Reassign the value with the one that contains trimmed values.
                $_POST['TB_BCC_ALL_MAILS_TO'] = implode(';', $bccMails);
            } else {
                // Don't update the existing value if there's an invalid email.
                unset($_POST['TB_BCC_ALL_MAILS_TO']);
                $this->errors[] = Tools::displayError('Make sure email addresses for adding as BCC to all outgoing mails are valid.');
            }
        }

        if (isset($_POST['TB_MAIL_SUBJECT_TEMPLATE']) && strpos($_POST['TB_MAIL_SUBJECT_TEMPLATE'], '{subject}') === false) {
            $this->errors[] = Tools::displayError('Email template must contains {subject} placeholder');
        }
    }

    /**
     * @param int $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int $start
     * @param int|null $limit
     * @param bool $idLangShop
     *
     * @throws PrestaShopException
     */
    public function getList(
        $idLang,
        $orderBy = null,
        $orderWay = null,
        $start = 0,
        $limit = null,
        $idLangShop = false
    ) {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        foreach ($this->_list as &$row) {
            $row['recipient'] = Tools::convertEmailFromIdn($row['recipient']);
        }
    }

    /**
     * @param string $recipient
     * @param array $row
     *
     * @return string
     */
    public function renderRecipient($recipient, $row)
    {
        $type = $row['recipient_type'];
        if ($type === Mail::RECIPIENT_TYPE_BCC) {
            return '<span title="'.Tools::safeOutput($this->l('BCC recipient')).'"><i class="icon-eye-slash"></i>&nbsp;'.$recipient.'</span>';
        } else {
            return '<span title="'.Tools::safeOutput($this->l('Primary recipient')).'"><i class="icon-envelope-o"></i>&nbsp;'.$recipient.'</span>';
        }
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function ajaxProcessSendTestEmail()
    {
        try {
            $email = Tools::getValue('email');
            if (! Validate::isEmail($email)) {
                throw new PrestaShopException("Invalid email address");
            }
            Configuration::updateValue('TB_SEND_TEST_EMAIL', $email);
            $languageId = (int)Context::getContext()->language->id;
            if (Mail::Send(
                $languageId,
                'test',
                Mail::l('Test email'),
                [],
                $email,
                null,
                null,
                null,
                null,
                null,
                _PS_MAIL_DIR_,
                true
            )) {
                $this->ajaxDie(json_encode(['status' => 'success']));
            } else {
                throw new PrestaShopException("Failed to send email");
            }
        } catch (Throwable $throwable) {
            $this->ajaxDie(json_encode([
                'status' => 'error',
                'message' => $throwable->getMessage()
            ]));
        }
    }
}
