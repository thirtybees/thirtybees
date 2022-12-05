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
 * Class AdminCustomerServiceSettingsControllerCore
 */
class AdminCustomerServiceSettingsControllerCore extends AdminController
{
    /**
     * AdminCustomerServiceSettingsControllerCore constructor.
     *
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->lang = false;

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
                        'title'        => $this->l('IMAP password'),
                        'hint'         => $this->l('Password to use to connect your IMAP server.'),
                        'validation'   => 'isAnything',
                        'type'         => 'password',
                        'autocomplete' => 'false',
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

        $this->page_header_toolbar_title = $this->l('Customer service: Settings');
    }

    /**
     * Check rights to view the current tab
     *
     * @param bool $disable
     *
     * @return bool
     */
    public function viewAccess($disable = false)
    {
        return (
            parent::viewAccess($disable) &&
            $this->tabAccess['edit']
        );
    }

    /**
     * Update handler for parameter PS_SAV_IMAP_PWD
     *
     * @param string $value new valu
     *
     * @throws PrestaShopException
     */
    public function updateOptionPsSavImapPwd($value)
    {
        if ($value) {
            Configuration::updateValue('PS_SAV_IMAP_PWD', $value);
        }
    }

}
