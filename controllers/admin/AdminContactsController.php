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
 * Class AdminContactsControllerCore
 *
 * @property Contact|null $object
 */
class AdminContactsControllerCore extends AdminController
{
    /**
     * AdminContactsControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'contact';
        $this->className = 'Contact';
        $this->lang = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fields_list = [
            'id_contact'  => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name'        => ['title' => $this->l('Title')],
            'email'       => ['title' => $this->l('Email address')],
            'description' => ['title' => $this->l('Description')],
            'active'      => [
                'title'    => $this->l('Active'),
                'align'    => 'text-center',
                'type'     => 'bool',
                'callback' => 'printContactActiveIcon',
                'orderby'  => false,
                'class'    => 'fixed-width-sm',
            ],
            'send_confirm'      => [
                'title'    => $this->l('Confirmation email'),
                'align'    => 'text-center',
                'type'     => 'bool',
                'callback' => 'printSendConfirmIcon',
                'orderby'  => false,
                'class'    => 'fixed-width-sm',
            ],
        ];

        parent::__construct();
    }

    /**
     * Render form
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Contacts'),
                'icon'  => 'icon-envelope-alt',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Title'),
                    'name'     => 'name',
                    'required' => true,
                    'lang'     => true,
                    'col'      => 4,
                    'hint'     => $this->l('Contact name (e.g. Customer Support).'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Email address'),
                    'name'     => 'email',
                    'required' => false,
                    'col'      => 4,
                    'hint'     => $this->l('Emails will be sent to this address.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Save messages?'),
                    'name'     => 'customer_service',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'hint'     => $this->l('If enabled, all messages will be saved in the "Customer Service" page under the "Customer" menu.'),
                    'values'   => [
                        [
                            'id'    => 'customer_service_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'customer_service_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'textarea',
                    'label'    => $this->l('Description'),
                    'name'     => 'description',
                    'required' => false,
                    'lang'     => true,
                    'col'      => 6,
                    'hint'     => $this->l('Further information regarding this contact.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Is contact active?'),
                    'name'     => 'active',
                    'required' => false,
                    'is_bool'  => true,
                    'default_value' => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Send confirmation email to customer?'),
                    'hint'     => $this->l('If enabled, confirmation email will be send to customer. Warning: this can be used for sending spam!'),
                    'name'     => 'send_confirm',
                    'required' => false,
                    'is_bool'  => true,
                    'default_value' => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        return parent::renderForm();
    }

    /**
     * Toggle contact active flag
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processChangeContactActiveVal()
    {
        $contact = new Contact($this->id_object);
        if (!Validate::isLoadedObject($contact)) {
            $this->errors[] = Tools::displayError('An error occurred while updating this contact.');
        }
        $contact->active = !$contact->active;
        if (! $contact->update()) {
            $this->errors[] = Tools::displayError('An error occurred while updating this contact.');
        }
        Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
    }

    /**
     * Print enable / disable icon for is contact active option
     *
     * @param bool $active Contact active flag
     * @param array $tr Row data
     *
     * @return string HTML link and icon
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function printContactActiveIcon($active, $tr)
    {
        $active = (bool)$active;
        $contactId = (int)$tr['id_contact'];
        return (
            '<a class="list-action-enable'.($active ? ' action-enabled' : ' action-disabled').'" href="index.php?controller=AdminContacts&amp;id_contact='.$contactId.'&amp;changeContactActiveVal&amp;token='.Tools::getAdminTokenLite('AdminContacts').'">
				'.($active ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>').
            '</a>'
        );
    }

    /**
     * Toggle contact send_contact flag
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processChangeContactSendConfirm()
    {
        $contact = new Contact($this->id_object);
        if (!Validate::isLoadedObject($contact)) {
            $this->errors[] = Tools::displayError('An error occurred while updating this contact.');
        }
        $contact->send_confirm = !$contact->send_confirm;
        if (! $contact->update()) {
            $this->errors[] = Tools::displayError('An error occurred while updating this contact.');
        }
        Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
    }

    /**
     * Print send confirm emmail flag
     *
     * @param bool $sendConfirm
     * @param array $tr Row data
     *
     * @return string HTML link and icon
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function printSendConfirmIcon($sendConfirm, $tr)
    {
        $sendConfirm = (bool)$sendConfirm;
        $contactId = (int)$tr['id_contact'];
        return (
            '<a class="list-action-enable'.($sendConfirm ? ' action-enabled' : ' action-disabled').'" href="index.php?controller=AdminContacts&amp;id_contact='.$contactId.'&amp;changeContactSendConfirm&amp;token='.Tools::getAdminTokenLite('AdminContacts').'">
				'.($sendConfirm ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>').
            '</a>'
        );
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        $this->initToolbar();
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_contact'] = [
                'href' => static::$currentIndex.'&addcontact&token='.$this->token,
                'desc' => $this->l('Add new contact', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @return void
     */
    public function initProcess()
    {
        parent::initProcess();

        $this->id_object = Tools::getIntValue('id_'.$this->table);

        if ($this->id_object) {
            if (Tools::isSubmit('changeContactActiveVal')) {
                $this->action = 'change_contact_active_val';
            } elseif (Tools::isSubmit('changeContactSendConfirm')) {
                $this->action = 'change_contact_send_confirm';
            }
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
            $row['email'] = Tools::convertEmailFromIdn($row['email']);
        }
    }

    /**
     * Return the list of fields value
     *
     * @param ObjectModel $obj Object
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getFieldsValue($obj)
    {
        $fieldsValue = parent::getFieldsValue($obj);
        $fieldsValue['email'] = Tools::convertEmailFromIdn($fieldsValue['email']);

        return $fieldsValue;
    }
}
