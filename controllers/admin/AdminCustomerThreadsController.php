<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class AdminCustomerThreadsControllerCore
 *
 * @since 1.0.0
 */
class AdminCustomerThreadsControllerCore extends AdminController
{
    /**
     * AdminCustomerThreadsControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'customer_thread';
        $this->className = 'CustomerThread';
        $this->lang = false;

        $contactArray = [];
        $contacts = Contact::getContacts($this->context->language->id);

        foreach ($contacts as $contact) {
            $contactArray[$contact['id_contact']] = $contact['name'];
        }

        $languageArray = [];
        $languages = Language::getLanguages();
        foreach ($languages as $language) {
            $languageArray[$language['id_lang']] = $language['name'];
        }

        $iconArray = [
            'open'     => ['class' => 'icon-circle text-success', 'alt' => $this->l('Open')],
            'closed'   => ['class' => 'icon-circle text-danger', 'alt' => $this->l('Closed')],
            'pending1' => ['class' => 'icon-circle text-warning', 'alt' => $this->l('Pending 1')],
            'pending2' => ['class' => 'icon-circle text-warning', 'alt' => $this->l('Pending 2')],
        ];

        $statusArray = [];
        foreach ($iconArray as $k => $v) {
            $statusArray[$k] = $v['alt'];
        }

        $this->fields_list = [
            'id_customer_thread' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'customer'           => [
                'title'          => $this->l('Customer'),
                'filter_key'     => 'customer',
                'tmpTableFilter' => true,
            ],
            'email'              => [
                'title'      => $this->l('Email'),
                'filter_key' => 'a!email',
            ],
            'contact'            => [
                'title'       => $this->l('Type'),
                'type'        => 'select',
                'list'        => $contactArray,
                'filter_key'  => 'cl!id_contact',
                'filter_type' => 'int',
            ],
            'language'           => [
                'title'       => $this->l('Language'),
                'type'        => 'select',
                'list'        => $languageArray,
                'filter_key'  => 'l!id_lang',
                'filter_type' => 'int',
            ],
            'status'             => [
                'title'       => $this->l('Status'),
                'type'        => 'select',
                'list'        => $statusArray,
                'icon'        => $iconArray,
                'align'       => 'center',
                'filter_key'  => 'a!status',
                'filter_type' => 'string',
            ],
            'employee'           => [
                'title'          => $this->l('Employee'),
                'filter_key'     => 'employee',
                'tmpTableFilter' => true,
            ],
            'messages'           => [
                'title'          => $this->l('Messages'),
                'filter_key'     => 'messages',
                'tmpTableFilter' => true,
                'maxlength'      => 40,
            ],
            'private'            => [
                'title'      => $this->l('Private'),
                'type'       => 'select',
                'filter_key' => 'private',
                'align'      => 'center',
                'cast'       => 'intval',
                'callback'   => 'printOptinIcon',
                'list'       => [
                    '0' => $this->l('No'),
                    '1' => $this->l('Yes'),
                ],
            ],
            'date_upd'           => [
                'title'        => $this->l('Last message'),
                'havingFilter' => true,
                'type'         => 'datetime',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->shopLinkType = 'shop';

        $this->fields_options = [
            'contact' => [
                'title'  => $this->l('Contact options'),
                'fields' => [
                    'PS_CUSTOMER_SERVICE_FILE_UPLOAD' => [
                        'title' => $this->l('Allow file uploading'),
                        'hint'  => $this->l('Allow customers to upload files using the contact page.'),
                        'type'  => 'bool',
                    ],
                    'PS_CUSTOMER_SERVICE_SIGNATURE'   => [
                        'title' => $this->l('Default message'),
                        'hint'  => $this->l('Please fill out the message fields that appear by default when you answer a thread on the customer service page.'),
                        'type'  => 'textareaLang',
                        'lang'  => true,
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'general' => [
                'title'  => $this->l('Customer service options'),
                'fields' => [
                    'PS_SAV_IMAP_URL'                 => [
                        'title' => $this->l('IMAP URL'),
                        'hint'  => $this->l('URL for your IMAP server (ie.: mail.server.com).'),
                        'type'  => 'text',
                    ],
                    'PS_SAV_IMAP_PORT'                => [
                        'title'        => $this->l('IMAP port'),
                        'hint'         => $this->l('Port to use to connect to your IMAP server.'),
                        'type'         => 'text',
                        'defaultValue' => 143,
                    ],
                    'PS_SAV_IMAP_USER'                => [
                        'title' => $this->l('IMAP user'),
                        'hint'  => $this->l('User to use to connect to your IMAP server.'),
                        'type'  => 'text',
                    ],
                    'PS_SAV_IMAP_PWD'                 => [
                        'title' => $this->l('IMAP password'),
                        'hint'  => $this->l('Password to use to connect your IMAP server.'),
                        'type'  => 'text',
                    ],
                    'PS_SAV_IMAP_DELETE_MSG'          => [
                        'title' => $this->l('Delete messages'),
                        'hint'  => $this->l('Delete messages after synchronization. If you do not enable this option, the synchronization will take more time.'),
                        'type'  => 'bool',
                    ],
                    'PS_SAV_IMAP_CREATE_THREADS'      => [
                        'title' => $this->l('Create new threads'),
                        'hint'  => $this->l('Create new threads for unrecognized emails.'),
                        'type'  => 'bool',
                    ],
                    'PS_SAV_IMAP_OPT_NORSH'           => [
                        'title' => $this->l('IMAP options').' (/norsh)',
                        'type'  => 'bool',
                        'hint'  => $this->l('Do not use RSH or SSH to establish a preauthenticated IMAP sessions.'),
                    ],
                    'PS_SAV_IMAP_OPT_SSL'             => [
                        'title' => $this->l('IMAP options').' (/ssl)',
                        'type'  => 'bool',
                        'hint'  => $this->l('Use the Secure Socket Layer (TLS/SSL) to encrypt the session.'),
                    ],
                    'PS_SAV_IMAP_OPT_VALIDATE-CERT'   => [
                        'title' => $this->l('IMAP options').' (/validate-cert)',
                        'type'  => 'bool',
                        'hint'  => $this->l('Validate certificates from the TLS/SSL server.'),
                    ],
                    'PS_SAV_IMAP_OPT_NOVALIDATE-CERT' => [
                        'title' => $this->l('IMAP options').' (/novalidate-cert)',
                        'type'  => 'bool',
                        'hint'  => $this->l('Do not validate certificates from the TLS/SSL server. This is only needed if a server uses self-signed certificates.'),
                    ],
                    'PS_SAV_IMAP_OPT_TLS'             => [
                        'title' => $this->l('IMAP options').' (/tls)',
                        'type'  => 'bool',
                        'hint'  => $this->l('Force use of start-TLS to encrypt the session, and reject connection to servers that do not support it.'),
                    ],
                    'PS_SAV_IMAP_OPT_NOTLS'           => [
                        'title' => $this->l('IMAP options').' (/notls)',
                        'type'  => 'bool',
                        'hint'  => $this->l('Do not use start-TLS to encrypt the session, even with servers that support it.'),
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        parent::__construct();
    }

    /**
     * Render list
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        // Check the new IMAP messages before rendering the list
        $this->renderProcessSyncImap();

        $this->addRowAction('view');
        $this->addRowAction('delete');

        $this->_select = '
			CONCAT(c.`firstname`," ",c.`lastname`) as customer, cl.`name` as contact, l.`name` as language, group_concat(message) as messages, cm.private,
			(
				SELECT IFNULL(CONCAT(LEFT(e.`firstname`, 1),". ",e.`lastname`), "--")
				FROM `'._DB_PREFIX_.'customer_message` cm2
				INNER JOIN '._DB_PREFIX_.'employee e
					ON e.`id_employee` = cm2.`id_employee`
				WHERE cm2.id_employee > 0
					AND cm2.`id_customer_thread` = a.`id_customer_thread`
				ORDER BY cm2.`date_add` DESC LIMIT 1
			) as employee';

        $this->_join = '
			LEFT JOIN `'._DB_PREFIX_.'customer` c
				ON c.`id_customer` = a.`id_customer`
			LEFT JOIN `'._DB_PREFIX_.'customer_message` cm
				ON cm.`id_customer_thread` = a.`id_customer_thread`
			LEFT JOIN `'._DB_PREFIX_.'lang` l
				ON l.`id_lang` = a.`id_lang`
			LEFT JOIN `'._DB_PREFIX_.'contact_lang` cl
				ON (cl.`id_contact` = a.`id_contact` AND cl.`id_lang` = '.(int) $this->context->language->id.')';

        if ($idOrder = Tools::getValue('id_order')) {
            $this->_where .= ' AND id_order = '.(int) $idOrder;
        }

        $this->_group = 'GROUP BY cm.id_customer_thread';
        $this->_orderBy = 'id_customer_thread';
        $this->_orderWay = 'DESC';

        $contacts = CustomerThread::getContacts();

        $categories = Contact::getCategoriesContacts();

        $params = [
            $this->l('Total threads')                     => $all = CustomerThread::getTotalCustomerThreads(),
            $this->l('Threads pending')                   => $pending = CustomerThread::getTotalCustomerThreads('status LIKE "%pending%"'),
            $this->l('Total number of customer messages') => CustomerMessage::getTotalCustomerMessages('id_employee = 0'),
            $this->l('Total number of employee messages') => CustomerMessage::getTotalCustomerMessages('id_employee != 0'),
            $this->l('Unread threads')                    => $unread = CustomerThread::getTotalCustomerThreads('status = "open"'),
            $this->l('Closed threads')                    => $all - ($unread + $pending),
        ];

        $this->tpl_list_vars = [
            'contacts'   => $contacts,
            'categories' => $categories,
            'params'     => $params,
        ];

        return parent::renderList();
    }

    /**
     * Call the IMAP synchronization during the render process.
     */
    public function renderProcessSyncImap()
    {
        // To avoid an error if the IMAP isn't configured, we check the configuration here, like during
        // the synchronization. All parameters will exists.
        if (!(Configuration::get('PS_SAV_IMAP_URL')
            || Configuration::get('PS_SAV_IMAP_PORT')
            || Configuration::get('PS_SAV_IMAP_USER')
            || Configuration::get('PS_SAV_IMAP_PWD'))
        ) {
            return;
        }

        // Executes the IMAP synchronization.
        $sync_errors = $this->syncImap();

        // Show the errors.
        if (isset($sync_errors['hasError']) && $sync_errors['hasError']) {
            if (isset($sync_errors['errors'])) {
                foreach ($sync_errors['errors'] as &$error) {
                    $this->displayWarning($error);
                }
            }
        }
    }

    /**
     * Imap synchronization method.
     *
     * @return array Errors list.
     */
    public function syncImap()
    {
        if (!($url = Configuration::get('PS_SAV_IMAP_URL'))
            || !($port = Configuration::get('PS_SAV_IMAP_PORT'))
            || !($user = Configuration::get('PS_SAV_IMAP_USER'))
            || !($password = Configuration::get('PS_SAV_IMAP_PWD'))
        ) {
            return ['hasError' => true, 'errors' => ['IMAP configuration is not correct']];
        }

        $conf = Configuration::getMultiple(
            [
                'PS_SAV_IMAP_OPT_NORSH', 'PS_SAV_IMAP_OPT_SSL',
                'PS_SAV_IMAP_OPT_VALIDATE-CERT', 'PS_SAV_IMAP_OPT_NOVALIDATE-CERT',
                'PS_SAV_IMAP_OPT_TLS', 'PS_SAV_IMAP_OPT_NOTLS',
            ]
        );

        $conf_str = '';
        if ($conf['PS_SAV_IMAP_OPT_NORSH']) {
            $conf_str .= '/norsh';
        }
        if ($conf['PS_SAV_IMAP_OPT_SSL']) {
            $conf_str .= '/ssl';
        }
        if ($conf['PS_SAV_IMAP_OPT_VALIDATE-CERT']) {
            $conf_str .= '/validate-cert';
        }
        if ($conf['PS_SAV_IMAP_OPT_NOVALIDATE-CERT']) {
            $conf_str .= '/novalidate-cert';
        }
        if ($conf['PS_SAV_IMAP_OPT_TLS']) {
            $conf_str .= '/tls';
        }
        if ($conf['PS_SAV_IMAP_OPT_NOTLS']) {
            $conf_str .= '/notls';
        }

        if (!function_exists('imap_open')) {
            return ['hasError' => true, 'errors' => ['imap is not installed on this server']];
        }

        $mbox = @imap_open('{'.$url.':'.$port.$conf_str.'}', $user, $password);

        //checks if there is no error when connecting imap server
        $errors = imap_errors();
        if (is_array($errors)) {
            $errors = array_unique($errors);
        }
        $str_errors = '';
        $str_error_delete = '';

        if (count($errors) && is_array($errors)) {
            $str_errors = '';
            foreach ($errors as $error) {
                $str_errors .= $error.', ';
            }
            $str_errors = rtrim(trim($str_errors), ',');
        }
        //checks if imap connexion is active
        if (!$mbox) {
            return ['hasError' => true, 'errors' => ['Cannot connect to the mailbox :<br />'.($str_errors)]];
        }

        //Returns information about the current mailbox. Returns FALSE on failure.
        $check = imap_check($mbox);
        if (!$check) {
            return ['hasError' => true, 'errors' => ['Fail to get information about the current mailbox']];
        }

        if ($check->Nmsgs == 0) {
            return ['hasError' => true, 'errors' => ['NO message to sync']];
        }

        $result = imap_fetch_overview($mbox, "1:{$check->Nmsgs}", 0);
        foreach ($result as $overview) {
            //check if message exist in database
            if (isset($overview->subject)) {
                $subject = $overview->subject;
            } else {
                $subject = '';
            }
            //Creating an md5 to check if message has been allready processed
            $md5 = md5($overview->date.$overview->from.$subject.$overview->msgno);
            $exist = Db::getInstance()->getValue(
                'SELECT `md5_header`
						 FROM `'._DB_PREFIX_.'customer_message_sync_imap`
						 WHERE `md5_header` = \''.pSQL($md5).'\''
            );
            if ($exist) {
                if (Configuration::get('PS_SAV_IMAP_DELETE_MSG')) {
                    if (!imap_delete($mbox, $overview->msgno)) {
                        $str_error_delete = ', Fail to delete message';
                    }
                }
            } else {
                //check if subject has id_order
                preg_match('/\#ct([0-9]*)/', $subject, $matches1);
                preg_match('/\#tc([0-9-a-z-A-Z]*)/', $subject, $matches2);
                $match_found = false;
                if (isset($matches1[1]) && isset($matches2[1])) {
                    $match_found = true;
                }

                $new_ct = (Configuration::get('PS_SAV_IMAP_CREATE_THREADS') && !$match_found && (strpos($subject, '[no_sync]') == false));

                if ($match_found || $new_ct) {
                    if ($new_ct) {
                        if (!preg_match('/<('.Tools::cleanNonUnicodeSupport('[a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+[._a-z\p{L}0-9-]*\.[a-z0-9]+').')>/', $overview->from, $result)
                            || !Validate::isEmail($from = $result[1])
                        ) {
                            continue;
                        }

                        // we want to assign unrecognized mails to the right contact category
                        $contacts = Contact::getContacts($this->context->language->id);
                        if (!$contacts) {
                            continue;
                        }

                        foreach ($contacts as $contact) {
                            if (strpos($overview->to, $contact['email']) !== false) {
                                $id_contact = $contact['id_contact'];
                            }
                        }

                        if (!isset($id_contact)) { // if not use the default contact category
                            $id_contact = $contacts[0]['id_contact'];
                        }

                        $customer = new Customer;
                        $client = $customer->getByEmail($from); //check if we already have a customer with this email
                        $ct = new CustomerThread();
                        if (isset($client->id)) { //if mail is owned by a customer assign to him
                            $ct->id_customer = $client->id;
                        }
                        $ct->email = $from;
                        $ct->id_contact = $id_contact;
                        $ct->id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
                        $ct->id_shop = $this->context->shop->id; //new customer threads for unrecognized mails are not shown without shop id
                        $ct->status = 'open';
                        $ct->token = Tools::passwdGen(12);
                        $ct->add();
                    } else {
                        $ct = new CustomerThread((int) $matches1[1]);
                    } //check if order exist in database

                    if (Validate::isLoadedObject($ct) && ((isset($matches2[1]) && $ct->token == $matches2[1]) || $new_ct)) {
                        $message = imap_fetchbody($mbox, $overview->msgno, 1);
                        $message = quoted_printable_decode($message);
                        $message = utf8_encode($message);
                        $message = quoted_printable_decode($message);
                        $message = nl2br($message);
                        $message = Tools::substr($message, 0, (int) CustomerMessage::$definition['fields']['message']['size']);

                        $cm = new CustomerMessage();
                        $cm->id_customer_thread = $ct->id;
                        if (empty($message) || !Validate::isCleanHtml($message)) {
                            $str_errors .= Tools::displayError(sprintf('Invalid Message Content for subject: %1s', $subject));
                        } else {
                            $cm->message = $message;
                            $cm->add();
                        }
                    }
                }
                Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'customer_message_sync_imap` (`md5_header`) VALUES (\''.pSQL($md5).'\')');
            }
        }
        imap_expunge($mbox);
        imap_close($mbox);
        if ($str_errors.$str_error_delete) {
            return ['hasError' => true, 'errors' => [$str_errors.$str_error_delete]];
        } else {
            return ['hasError' => false, 'errors' => ''];
        }
    }

    /**
     * @return void
     *
     * @since 1.0.0
     */
    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    /**
     * @param $value
     * @param $customer
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function printOptinIcon($value, $customer)
    {
        return ($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if ($idCustomerThread = (int) Tools::getValue('id_customer_thread')) {
            if (($idContact = (int) Tools::getValue('id_contact'))) {
                Db::getInstance()->execute(
                    '
					UPDATE '._DB_PREFIX_.'customer_thread
					SET id_contact = '.(int) $idContact.'
					WHERE id_customer_thread = '.(int) $idCustomerThread
                );
            }
            if ($idStatus = (int) Tools::getValue('setstatus')) {
                $statusArray = [1 => 'open', 2 => 'closed', 3 => 'pending1', 4 => 'pending2'];
                Db::getInstance()->execute(
                    '
					UPDATE '._DB_PREFIX_.'customer_thread
					SET status = "'.$statusArray[$idStatus].'"
					WHERE id_customer_thread = '.(int) $idCustomerThread.' LIMIT 1
				'
                );
            }
            if (isset($_POST['id_employee_forward'])) {
                $messages = Db::getInstance()->getRow(
                    '
					SELECT ct.*, cm.*, cl.name subject, CONCAT(e.firstname, \' \', e.lastname) employee_name,
						CONCAT(c.firstname, \' \', c.lastname) customer_name, c.firstname
					FROM '._DB_PREFIX_.'customer_thread ct
					LEFT JOIN '._DB_PREFIX_.'customer_message cm
						ON (ct.id_customer_thread = cm.id_customer_thread)
					LEFT JOIN '._DB_PREFIX_.'contact_lang cl
						ON (cl.id_contact = ct.id_contact AND cl.id_lang = '.(int) $this->context->language->id.')
					LEFT OUTER JOIN '._DB_PREFIX_.'employee e
						ON e.id_employee = cm.id_employee
					LEFT OUTER JOIN '._DB_PREFIX_.'customer c
						ON (c.email = ct.email)
					WHERE ct.id_customer_thread = '.(int) Tools::getValue('id_customer_thread').'
					ORDER BY cm.date_add DESC
				'
                );
                $output = $this->displayMessage($messages, true, (int) Tools::getValue('id_employee_forward'));
                $cm = new CustomerMessage();
                $cm->id_employee = (int) $this->context->employee->id;
                $cm->id_customer_thread = (int) Tools::getValue('id_customer_thread');
                $cm->ip_address = (int) ip2long(Tools::getRemoteAddr());
                $currentEmployee = $this->context->employee;
                $idEmployee = (int) Tools::getValue('id_employee_forward');
                $employee = new Employee($idEmployee);
                $email = Tools::getValue('email');
                $message = Tools::getValue('message_forward');
                if (($error = $cm->validateField('message', $message, null, [], true)) !== true) {
                    $this->errors[] = $error;
                } elseif ($idEmployee && $employee && Validate::isLoadedObject($employee)) {
                    $params = [
                        '{messages}'  => stripslashes($output),
                        '{employee}'  => $currentEmployee->firstname.' '.$currentEmployee->lastname,
                        '{comment}'   => stripslashes(Tools::nl2br($_POST['message_forward'])),
                        '{firstname}' => $employee->firstname,
                        '{lastname}'  => $employee->lastname,
                    ];

                    if (Mail::Send(
                        $this->context->language->id,
                        'forward_msg',
                        Mail::l('Fwd: Customer message', $this->context->language->id),
                        $params,
                        $employee->email,
                        $employee->firstname.' '.$employee->lastname,
                        $currentEmployee->email,
                        $currentEmployee->firstname.' '.$currentEmployee->lastname,
                        null,
                        null,
                        _PS_MAIL_DIR_,
                        true
                    )) {
                        $cm->private = 1;
                        $cm->message = $this->l('Message forwarded to').' '.$employee->firstname.' '.$employee->lastname."\n".$this->l('Comment:').' '.$message;
                        $cm->add();
                    }
                } elseif ($email && Validate::isEmail($email)) {
                    $params = [
                        '{messages}'  => Tools::nl2br(stripslashes($output)),
                        '{employee}'  => $currentEmployee->firstname.' '.$currentEmployee->lastname,
                        '{comment}'   => stripslashes($_POST['message_forward']),
                        '{firstname}' => '',
                        '{lastname}'  => '',
                    ];

                    if (Mail::Send(
                        $this->context->language->id,
                        'forward_msg',
                        Mail::l('Fwd: Customer message', $this->context->language->id),
                        $params,
                        $email,
                        null,
                        $currentEmployee->email,
                        $currentEmployee->firstname.' '.$currentEmployee->lastname,
                        null,
                        null,
                        _PS_MAIL_DIR_,
                        true
                    )) {
                        $cm->message = $this->l('Message forwarded to').' '.$email."\n".$this->l('Comment:').' '.$message;
                        $cm->add();
                    }
                } else {
                    $this->errors[] = '<div class="alert error">'.Tools::displayError('The email address is invalid.').'</div>';
                }
            }
            if (Tools::isSubmit('submitReply')) {
                $ct = new CustomerThread($idCustomerThread);

                ShopUrl::cacheMainDomainForShop((int) $ct->id_shop);

                $cm = new CustomerMessage();
                $cm->id_employee = (int) $this->context->employee->id;
                $cm->id_customer_thread = $ct->id;
                $cm->ip_address = (int) ip2long(Tools::getRemoteAddr());
                $cm->message = Tools::getValue('reply_message');
                if (($error = $cm->validateField('message', $cm->message, null, [], true)) !== true) {
                    $this->errors[] = $error;
                } elseif (isset($_FILES) && !empty($_FILES['joinFile']['name']) && $_FILES['joinFile']['error'] != 0) {
                    $this->errors[] = Tools::displayError('An error occurred during the file upload process.');
                } elseif ($cm->add()) {
                    $fileAttachment = null;
                    if (!empty($_FILES['joinFile']['name'])) {
                        $fileAttachment['content'] = file_get_contents($_FILES['joinFile']['tmp_name']);
                        $fileAttachment['name'] = $_FILES['joinFile']['name'];
                        $fileAttachment['mime'] = $_FILES['joinFile']['type'];
                    }
                    $customer = new Customer($ct->id_customer);
                    $params = [
                        '{reply}'     => Tools::nl2br(Tools::getValue('reply_message')),
                        '{link}'      => Tools::url(
                            $this->context->link->getPageLink('contact', true, null, null, false, $ct->id_shop),
                            'id_customer_thread='.(int) $ct->id.'&token='.$ct->token
                        ),
                        '{firstname}' => $customer->firstname,
                        '{lastname}'  => $customer->lastname,
                    ];
                    //#ct == id_customer_thread    #tc == token of thread   <== used in the synchronization imap
                    $contact = new Contact((int) $ct->id_contact, (int) $ct->id_lang);

                    if (Validate::isLoadedObject($contact)) {
                        $fromName = $contact->name;
                        $fromEmail = $contact->email;
                    } else {
                        $fromName = null;
                        $fromEmail = null;
                    }

                    if (Mail::Send(
                        (int) $ct->id_lang,
                        'reply_msg',
                        sprintf(Mail::l('An answer to your message is available #ct%1$s #tc%2$s', $ct->id_lang), $ct->id, $ct->token),
                        $params,
                        Tools::getValue('msg_email'),
                        null,
                        $fromEmail,
                        $fromName,
                        $fileAttachment,
                        null,
                        _PS_MAIL_DIR_,
                        true,
                        $ct->id_shop
                    )) {
                        $ct->status = 'closed';
                        $ct->update();
                    }
                    Tools::redirectAdmin(
                        static::$currentIndex.'&id_customer_thread='.(int) $idCustomerThread.'&viewcustomer_thread&token='.Tools::getValue('token')
                    );
                } else {
                    $this->errors[] = Tools::displayError('An error occurred. Your message was not sent. Please contact your system administrator.');
                }
            }
        }

        return parent::postProcess();
    }

    /**
     * @param      $message
     * @param bool $email
     * @param null $idEmployee
     *
     * @return string
     *
     * @since 1.0.
     */
    protected function displayMessage($message, $email = false, $idEmployee = null)
    {
        $tpl = $this->createTemplate('message.tpl');

        $contacts = Contact::getContacts($this->context->language->id);
        $contactArray = [];
        foreach ($contacts as $contact) {
            $contactArray[$contact['id_contact']] = ['id_contact' => $contact['id_contact'], 'name' => $contact['name']];
        }
        $contacts = $contactArray;

        if (!$email) {
            if (!empty($message['id_product']) && empty($message['employee_name'])) {
                $idOrderProduct = Order::getIdOrderProduct((int) $message['id_customer'], (int) $message['id_product']);
            }
        }
        $message['date_add'] = Tools::displayDate($message['date_add'], null, true);
        $message['user_agent'] = strip_tags($message['user_agent']);
        $message['message'] = preg_replace(
            '/(https?:\/\/[a-z0-9#%&_=\(\)\.\? \+\-@\/]{6,1000})([\s\n<])/Uui',
            '<a href="\1">\1</a>\2',
            html_entity_decode(
                $message['message'],
                ENT_QUOTES,
                'UTF-8'
            )
        );

        $isValidOrderId = true;
        $order = new Order((int) $message['id_order']);

        if (!Validate::isLoadedObject($order)) {
            $isValidOrderId = false;
        }

        $tpl->assign(
            [
                'thread_url'        => Tools::getAdminUrl(basename(_PS_ADMIN_DIR_).'/'.$this->context->link->getAdminLink('AdminCustomerThreads').'&amp;id_customer_thread='.(int) $message['id_customer_thread'].'&amp;viewcustomer_thread=1'),
                'link'              => Context::getContext()->link,
                'current'           => static::$currentIndex,
                'token'             => $this->token,
                'message'           => $message,
                'id_order_product'  => isset($idOrderProduct) ? $idOrderProduct : null,
                'email'             => $email,
                'id_employee'       => $idEmployee,
                'PS_SHOP_NAME'      => Configuration::get('PS_SHOP_NAME'),
                'file_name'         => file_exists(_PS_UPLOAD_DIR_.$message['file_name']),
                'contacts'          => $contacts,
                'is_valid_order_id' => $isValidOrderId,
            ]
        );

        return $tpl->fetch();
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        if (isset($_GET['filename']) && file_exists(_PS_UPLOAD_DIR_.$_GET['filename']) && Validate::isFileName($_GET['filename'])) {
            static::openUploadedFile();
        }

        parent::initContent();
    }

    /**
     * Render KPIs
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function renderKpis()
    {
        $time = time();
        $kpis = [];

        /* The data generation is located in AdminStatsControllerCore */

        $helper = new HelperKpi();
        $helper->id = 'box-pending-messages';
        $helper->icon = 'icon-envelope';
        $helper->color = 'color1';
        $helper->href = $this->context->link->getAdminLink('AdminCustomerThreads');
        $helper->title = $this->l('Pending Discussion Threads', null, null, false);
        if (ConfigurationKPI::get('PENDING_MESSAGES') !== false) {
            $helper->value = ConfigurationKPI::get('PENDING_MESSAGES');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=pending_messages';
        $helper->refresh = (bool) (ConfigurationKPI::get('PENDING_MESSAGES_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-age';
        $helper->icon = 'icon-time';
        $helper->color = 'color2';
        $helper->title = $this->l('Average Response Time', null, null, false);
        $helper->subtitle = $this->l('30 days', null, null, false);
        if (ConfigurationKPI::get('AVG_MSG_RESPONSE_TIME') !== false) {
            $helper->value = ConfigurationKPI::get('AVG_MSG_RESPONSE_TIME');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=avg_msg_response_time';
        $helper->refresh = (bool) (ConfigurationKPI::get('AVG_MSG_RESPONSE_TIME_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpi();
        $helper->id = 'box-messages-per-thread';
        $helper->icon = 'icon-copy';
        $helper->color = 'color3';
        $helper->title = $this->l('Messages per Thread', null, null, false);
        $helper->subtitle = $this->l('30 day', null, null, false);
        if (ConfigurationKPI::get('MESSAGES_PER_THREAD') !== false) {
            $helper->value = ConfigurationKPI::get('MESSAGES_PER_THREAD');
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=messages_per_thread';
        $helper->refresh = (bool) (ConfigurationKPI::get('MESSAGES_PER_THREAD_EXPIRE') < $time);
        $kpis[] = $helper->generate();

        $helper = new HelperKpiRow();
        $helper->kpis = $kpis;

        return $helper->generate();
    }

    /**
     * Render view
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderView()
    {
        if (!$idCustomerThread = (int) Tools::getValue('id_customer_thread')) {
            return '';
        }

        $this->context = Context::getContext();
        if (!($thread = $this->loadObject())) {
            return '';
        }
        $this->context->cookie->{'customer_threadFilter_cl!id_contact'} = $thread->id_contact;

        $employees = Employee::getEmployees();

        $messages = CustomerThread::getMessageCustomerThreads($idCustomerThread);

        foreach ($messages as $key => $mess) {
            if ($mess['id_employee']) {
                $employee = new Employee($mess['id_employee']);
                $messages[$key]['employee_image'] = $employee->getImage();
            }
            if (isset($mess['file_name']) && $mess['file_name'] != '') {
                $messages[$key]['file_name'] = _THEME_PROD_PIC_DIR_.$mess['file_name'];
            } else {
                unset($messages[$key]['file_name']);
            }

            if ($mess['id_product']) {
                $product = new Product((int) $mess['id_product'], false, $this->context->language->id);
                if (Validate::isLoadedObject($product)) {
                    $messages[$key]['product_name'] = $product->name;
                    $messages[$key]['product_link'] = $this->context->link->getAdminLink('AdminProducts').'&updateproduct&id_product='.(int) $product->id;
                }
            }
        }

        $nextThread = CustomerThread::getNextThread((int) $thread->id);

        $contacts = Contact::getContacts($this->context->language->id);

        $actions = [];

        if ($nextThread) {
            $nextThread = [
                'href' => static::$currentIndex.'&id_customer_thread='.(int) $nextThread.'&viewcustomer_thread&token='.$this->token,
                'name' => $this->l('Reply to the next unanswered message in this thread'),
            ];
        }

        if ($thread->status != 'closed') {
            $actions['closed'] = [
                'href'  => static::$currentIndex.'&viewcustomer_thread&setstatus=2&id_customer_thread='.(int) Tools::getValue('id_customer_thread').'&viewmsg&token='.$this->token,
                'label' => $this->l('Mark as "handled"'),
                'name'  => 'setstatus',
                'value' => 2,
            ];
        } else {
            $actions['open'] = [
                'href'  => static::$currentIndex.'&viewcustomer_thread&setstatus=1&id_customer_thread='.(int) Tools::getValue('id_customer_thread').'&viewmsg&token='.$this->token,
                'label' => $this->l('Re-open'),
                'name'  => 'setstatus',
                'value' => 1,
            ];
        }

        if ($thread->status != 'pending1') {
            $actions['pending1'] = [
                'href'  => static::$currentIndex.'&viewcustomer_thread&setstatus=3&id_customer_thread='.(int) Tools::getValue('id_customer_thread').'&viewmsg&token='.$this->token,
                'label' => $this->l('Mark as "pending 1" (will be answered later)'),
                'name'  => 'setstatus',
                'value' => 3,
            ];
        } else {
            $actions['pending1'] = [
                'href'  => static::$currentIndex.'&viewcustomer_thread&setstatus=1&id_customer_thread='.(int) Tools::getValue('id_customer_thread').'&viewmsg&token='.$this->token,
                'label' => $this->l('Disable pending status'),
                'name'  => 'setstatus',
                'value' => 1,
            ];
        }

        if ($thread->status != 'pending2') {
            $actions['pending2'] = [
                'href'  => static::$currentIndex.'&viewcustomer_thread&setstatus=4&id_customer_thread='.(int) Tools::getValue('id_customer_thread').'&viewmsg&token='.$this->token,
                'label' => $this->l('Mark as "pending 2" (will be answered later)'),
                'name'  => 'setstatus',
                'value' => 4,
            ];
        } else {
            $actions['pending2'] = [
                'href'  => static::$currentIndex.'&viewcustomer_thread&setstatus=1&id_customer_thread='.(int) Tools::getValue('id_customer_thread').'&viewmsg&token='.$this->token,
                'label' => $this->l('Disable pending status'),
                'name'  => 'setstatus',
                'value' => 1,
            ];
        }

        if ($thread->id_customer) {
            $customer = new Customer($thread->id_customer);
            $orders = Order::getCustomerOrders($customer->id);
            if ($orders && count($orders)) {
                $totalOk = 0;
                $ordersOk = [];
                foreach ($orders as $key => $order) {
                    if ($order['valid']) {
                        $ordersOk[] = $order;
                        $totalOk += $order['total_paid_real'] / $order['conversion_rate'];
                    }
                    $orders[$key]['date_add'] = Tools::displayDate($order['date_add']);
                    $orders[$key]['total_paid_real'] = Tools::displayPrice($order['total_paid_real'], new Currency((int) $order['id_currency']));
                }
            }

            $products = $customer->getBoughtProducts();
            if ($products && count($products)) {
                foreach ($products as $key => $product) {
                    $products[$key]['date_add'] = Tools::displayDate($product['date_add'], null, true);
                }
            }
        }
        $timelineItems = $this->getTimeline($messages, $thread->id_order);
        $firstMessage = $messages[0];

        if (!$messages[0]['id_employee']) {
            unset($messages[0]);
        }

        $contact = '';
        foreach ($contacts as $c) {
            if ($c['id_contact'] == $thread->id_contact) {
                $contact = $c['name'];
            }
        }

        $this->tpl_view_vars = [
            'id_customer_thread'            => $idCustomerThread,
            'thread'                        => $thread,
            'actions'                       => $actions,
            'employees'                     => $employees,
            'current_employee'              => $this->context->employee,
            'messages'                      => $messages,
            'first_message'                 => $firstMessage,
            'contact'                       => $contact,
            'next_thread'                   => $nextThread,
            'orders'                        => isset($orders) ? $orders : false,
            'customer'                      => isset($customer) ? $customer : false,
            'products'                      => isset($products) ? $products : false,
            'total_ok'                      => isset($totalOk) ? Tools::displayPrice($totalOk, $this->context->currency) : false,
            'orders_ok'                     => isset($ordersOk) ? $ordersOk : false,
            'count_ok'                      => isset($ordersOk) ? count($ordersOk) : false,
            'PS_CUSTOMER_SERVICE_SIGNATURE' => str_replace('\r\n', "\n", Configuration::get('PS_CUSTOMER_SERVICE_SIGNATURE', (int) $thread->id_lang)),
            'timeline_items'                => $timelineItems,
        ];

        if ($nextThread) {
            $this->tpl_view_vars['next_thread'] = $nextThread;
        }

        return parent::renderView();
    }

    /**
     * Get timeline
     *
     * @param $messages
     * @param $idOrder
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getTimeline($messages, $idOrder)
    {
        $timeline = [];
        foreach ($messages as $message) {
            $product = new Product((int) $message['id_product'], false, $this->context->language->id);

            $content = '';
            if (!$message['private']) {
                $content .= $this->l('Message to: ').' <span class="badge">'.(!$message['id_employee'] ? $message['subject'] : $message['customer_name']).'</span><br/>';
            }
            if (Validate::isLoadedObject($product)) {
                $content .= '<br/>'.$this->l('Product: ').'<span class="label label-info">'.$product->name.'</span><br/><br/>';
            }
            $content .= Tools::safeOutput($message['message']);

            $timeline[$message['date_add']][] = [
                'arrow'            => 'left',
                'background_color' => '',
                'icon'             => 'icon-envelope',
                'content'          => $content,
                'date'             => $message['date_add'],
            ];
        }

        $order = new Order((int) $idOrder);
        if (Validate::isLoadedObject($order)) {
            $orderHistory = $order->getHistory($this->context->language->id);
            foreach ($orderHistory as $history) {
                $linkOrder = $this->context->link->getAdminLink('AdminOrders').'&vieworder&id_order='.(int) $order->id;

                $content = '<a class="badge" target="_blank" href="'.Tools::safeOutput($linkOrder).'">'.$this->l('Order').' #'.(int) $order->id.'</a><br/><br/>';

                $content .= '<span>'.$this->l('Status:').' '.$history['ostate_name'].'</span>';

                $timeline[$history['date_add']][] = [
                    'arrow'            => 'right',
                    'alt'              => true,
                    'background_color' => $history['color'],
                    'icon'             => 'icon-credit-card',
                    'content'          => $content,
                    'date'             => $history['date_add'],
                    'see_more_link'    => $linkOrder,
                ];
            }
        }
        krsort($timeline);

        return $timeline;
    }

    /**
     * Render options
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderOptions()
    {
        if (Configuration::get('PS_SAV_IMAP_URL')
            && Configuration::get('PS_SAV_IMAP_PORT')
            && Configuration::get('PS_SAV_IMAP_USER')
            && Configuration::get('PS_SAV_IMAP_PWD')
        ) {
            $this->tpl_option_vars['use_sync'] = true;
        } else {
            $this->tpl_option_vars['use_sync'] = false;
        }

        return parent::renderOptions();
    }

    /**
     * AdminController::getList() override
     *
     * @see AdminController::getList()
     *
     * @param int         $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int         $start
     * @param int|null    $limit
     * @param int|bool    $idLangShop
     *
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        $nbItems = count($this->_list);
        for ($i = 0; $i < $nbItems; ++$i) {
            if (isset($this->_list[$i]['messages'])) {
                $this->_list[$i]['messages'] = Tools::htmlentitiesDecodeUTF8($this->_list[$i]['messages']);
            }
        }
    }

    /**
     * @param $value
     *
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    public function updateOptionPsSavImapOpt($value)
    {
        if ($this->tabAccess['edit'] != '1') {
            throw new PrestaShopException(Tools::displayError('You do not have permission to edit this.'));
        }

        if (!$this->errors && $value) {
            Configuration::updateValue('PS_SAV_IMAP_OPT', implode('', $value));
        }
    }

    /**
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    public function ajaxProcessMarkAsRead()
    {
        if ($this->tabAccess['edit'] != '1') {
            throw new PrestaShopException(Tools::displayError('You do not have permission to edit this.'));
        }

        $idThread = Tools::getValue('id_thread');
        $messages = CustomerThread::getMessageCustomerThreads($idThread);
        if (count($messages)) {
            Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'customer_message` set `read` = 1 WHERE `id_employee` = '.(int) $this->context->employee->id.' AND `id_customer_thread` = '.(int) $idThread);
        }
    }

    /**
     * Call the IMAP synchronization during an AJAX process.
     *
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    public function ajaxProcessSyncImap()
    {
        if ($this->tabAccess['edit'] != '1') {
            throw new PrestaShopException(Tools::displayError('You do not have permission to edit this.'));
        }

        if (Tools::isSubmit('syncImapMail')) {
            die(json_encode($this->syncImap()));
        }
    }

    protected function openUploadedFile()
    {
        $filename = $_GET['filename'];

        $extensions = [
            '.txt'  => 'text/plain',
            '.rtf'  => 'application/rtf',
            '.doc'  => 'application/msword',
            '.docx' => 'application/msword',
            '.pdf'  => 'application/pdf',
            '.zip'  => 'multipart/x-zip',
            '.png'  => 'image/png',
            '.jpeg' => 'image/jpeg',
            '.gif'  => 'image/gif',
            '.jpg'  => 'image/jpeg',
        ];

        $extension = false;
        foreach ($extensions as $key => $val) {
            if (substr(Tools::strtolower($filename), -4) == $key || substr(Tools::strtolower($filename), -5) == $key) {
                $extension = $val;
                break;
            }
        }

        if (!$extension || !Validate::isFileName($filename)) {
            die(Tools::displayError());
        }

        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }
        header('Content-Type: '.$extension);
        header('Content-Disposition:attachment;filename="'.$filename.'"');
        readfile(_PS_UPLOAD_DIR_.$filename);
        die;
    }

    /**
     * @param $content
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function displayButton($content)
    {
        return '<div><p>'.$content.'</p></div>';
    }
}
