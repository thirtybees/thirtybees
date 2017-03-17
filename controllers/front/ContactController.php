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
 * Class ContactControllerCore
 *
 * @since 1.0.0
 */
class ContactControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'contact';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    /**
     * Start forms process
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitMessage')) {
            $extension = ['.txt', '.rtf', '.doc', '.docx', '.pdf', '.zip', '.png', '.jpeg', '.gif', '.jpg'];
            $fileAttachment = Tools::fileAttachment('fileUpload');
            $message = Tools::getValue('message'); // Html entities is not usefull, iscleanHtml check there is no bad html tags.
            if (!($from = trim(Tools::getValue('from'))) || !Validate::isEmail($from)) {
                $this->errors[] = Tools::displayError('Invalid email address.');
            } elseif (!$message) {
                $this->errors[] = Tools::displayError('The message cannot be blank.');
            } elseif (!Validate::isCleanHtml($message)) {
                $this->errors[] = Tools::displayError('Invalid message');
            } elseif (!($idContact = (int) Tools::getValue('id_contact')) || !(Validate::isLoadedObject($contact = new Contact($idContact, $this->context->language->id)))) {
                $this->errors[] = Tools::displayError('Please select a subject from the list provided. ');
            } elseif (!empty($fileAttachment['name']) && $fileAttachment['error'] != 0) {
                $this->errors[] = Tools::displayError('An error occurred during the file-upload process.');
            } elseif (!empty($fileAttachment['name']) && !in_array(Tools::strtolower(substr($fileAttachment['name'], -4)), $extension) && !in_array(Tools::strtolower(substr($fileAttachment['name'], -5)), $extension)) {
                $this->errors[] = Tools::displayError('Bad file extension');
            } else {
                $customer = $this->context->customer;
                if (!$customer->id) {
                    $customer->getByEmail($from);
                }

                $idOrder = (int) $this->getOrder();

                if (!((
                        ($idCustomerThread = (int) Tools::getValue('id_customer_thread'))
                        && (int) Db::getInstance()->getValue(
                            '
						SELECT cm.id_customer_thread FROM '._DB_PREFIX_.'customer_thread cm
						WHERE cm.id_customer_thread = '.(int) $idCustomerThread.' AND cm.id_shop = '.(int) $this->context->shop->id.' AND token = \''.pSQL(Tools::getValue('token')).'\''
                        )
                    ) || (
                    $idCustomerThread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($from, $idOrder)
                    ))
                ) {
                    $fields = Db::getInstance()->executeS(
                        '
					SELECT cm.id_customer_thread, cm.id_contact, cm.id_customer, cm.id_order, cm.id_product, cm.email
					FROM '._DB_PREFIX_.'customer_thread cm
					WHERE email = \''.pSQL($from).'\' AND cm.id_shop = '.(int) $this->context->shop->id.' AND ('.($customer->id ? 'id_customer = '.(int) $customer->id.' OR ' : '').' id_order = '.(int) $idOrder.')'
                    );
                    $score = 0;
                    foreach ($fields as $key => $row) {
                        $tmp = 0;
                        if ((int) $row['id_customer'] && $row['id_customer'] != $customer->id && $row['email'] != $from) {
                            continue;
                        }
                        if ($row['id_order'] != 0 && $idOrder != $row['id_order']) {
                            continue;
                        }
                        if ($row['email'] == $from) {
                            $tmp += 4;
                        }
                        if ($row['id_contact'] == $idContact) {
                            $tmp++;
                        }
                        if (Tools::getValue('id_product') != 0 && $row['id_product'] == Tools::getValue('id_product')) {
                            $tmp += 2;
                        }
                        if ($tmp >= 5 && $tmp >= $score) {
                            $score = $tmp;
                            $idCustomerThread = $row['id_customer_thread'];
                        }
                    }
                }
                $oldMessage = Db::getInstance()->getValue(
                    '
					SELECT cm.message FROM '._DB_PREFIX_.'customer_message cm
					LEFT JOIN '._DB_PREFIX_.'customer_thread cc on (cm.id_customer_thread = cc.id_customer_thread)
					WHERE cc.id_customer_thread = '.(int) $idCustomerThread.' AND cc.id_shop = '.(int) $this->context->shop->id.'
					ORDER BY cm.date_add DESC'
                );
                if ($oldMessage == $message) {
                    $this->context->smarty->assign('alreadySent', 1);
                    $contact->email = '';
                    $contact->customer_service = 0;
                }

                if ($contact->customer_service) {
                    if ((int) $idCustomerThread) {
                        $ct = new CustomerThread($idCustomerThread);
                        $ct->status = 'open';
                        $ct->id_lang = (int) $this->context->language->id;
                        $ct->id_contact = (int) $idContact;
                        $ct->id_order = (int) $idOrder;
                        if ($idProduct = (int) Tools::getValue('id_product')) {
                            $ct->id_product = $idProduct;
                        }
                        $ct->update();
                    } else {
                        $ct = new CustomerThread();
                        if (isset($customer->id)) {
                            $ct->id_customer = (int) $customer->id;
                        }
                        $ct->id_shop = (int) $this->context->shop->id;
                        $ct->id_order = (int) $idOrder;
                        if ($idProduct = (int) Tools::getValue('id_product')) {
                            $ct->id_product = $idProduct;
                        }
                        $ct->id_contact = (int) $idContact;
                        $ct->id_lang = (int) $this->context->language->id;
                        $ct->email = $from;
                        $ct->status = 'open';
                        $ct->token = Tools::passwdGen(12);
                        $ct->add();
                    }

                    if ($ct->id) {
                        $cm = new CustomerMessage();
                        $cm->id_customer_thread = $ct->id;
                        $cm->message = $message;
                        if (isset($fileAttachment['rename']) && !empty($fileAttachment['rename']) && rename($fileAttachment['tmp_name'], _PS_UPLOAD_DIR_.basename($fileAttachment['rename']))) {
                            $cm->file_name = $fileAttachment['rename'];
                            @chmod(_PS_UPLOAD_DIR_.basename($fileAttachment['rename']), 0664);
                        }
                        $cm->ip_address = (int) ip2long(Tools::getRemoteAddr());
                        $cm->user_agent = $_SERVER['HTTP_USER_AGENT'];
                        if (!$cm->add()) {
                            $this->errors[] = Tools::displayError('An error occurred while sending the message.');
                        }
                    } else {
                        $this->errors[] = Tools::displayError('An error occurred while sending the message.');
                    }
                }

                if (!count($this->errors)) {
                    $varList = [
                        '{order_name}'    => '-',
                        '{attached_file}' => '-',
                        '{message}'       => Tools::nl2br(stripslashes($message)),
                        '{email}'         => $from,
                        '{product_name}'  => '',
                    ];

                    if (isset($fileAttachment['name'])) {
                        $varList['{attached_file}'] = $fileAttachment['name'];
                    }

                    $idProduct = (int) Tools::getValue('id_product');

                    if (isset($ct) && Validate::isLoadedObject($ct) && $ct->id_order) {
                        $order = new Order((int) $ct->id_order);
                        $varList['{order_name}'] = $order->getUniqReference();
                        $varList['{id_order}'] = (int) $order->id;
                    }

                    if ($idProduct) {
                        $product = new Product((int) $idProduct);
                        if (Validate::isLoadedObject($product) && isset($product->name[Context::getContext()->language->id])) {
                            $varList['{product_name}'] = $product->name[Context::getContext()->language->id];
                        }
                    }

                    if (empty($contact->email)) {
                        Mail::Send($this->context->language->id, 'contact_form', ((isset($ct) && Validate::isLoadedObject($ct)) ? sprintf(Mail::l('Your message has been correctly sent #ct%1$s #tc%2$s'), $ct->id, $ct->token) : Mail::l('Your message has been correctly sent')), $varList, $from, null, null, null, $fileAttachment);
                    } else {
                        if (!Mail::Send(
                            $this->context->language->id,
                            'contact',
                            Mail::l('Message from contact form').' [no_sync]',
                            $varList,
                            $contact->email,
                            $contact->name,
                            null,
                            null,
                            $fileAttachment,
                            null,
                            _PS_MAIL_DIR_,
                            false,
                            null,
                            null,
                            $from
                        ) || !Mail::Send($this->context->language->id, 'contact_form', ((isset($ct) && Validate::isLoadedObject($ct)) ? sprintf(Mail::l('Your message has been correctly sent #ct%1$s #tc%2$s'), $ct->id, $ct->token) : Mail::l('Your message has been correctly sent')), $varList, $from, null, null, null, $fileAttachment, null, _PS_MAIL_DIR_, false, null, null, $contact->email)
                        ) {
                            $this->errors[] = Tools::displayError('An error occurred while sending the message.');
                        }
                    }
                }

                if (count($this->errors) > 1) {
                    array_unique($this->errors);
                } elseif (!count($this->errors)) {
                    $this->context->smarty->assign('confirmation', 1);
                }
            }
        }
    }

    /**
     * Get Order ID
     *
     * @return int Order ID
     *
     * @since 1.0.0
     */
    protected function getOrder()
    {
        $idOrder = false;
        if (!is_numeric($reference = Tools::getValue('id_order'))) {
            $reference = ltrim($reference, '#');
            $orders = Order::getByReference($reference);
            if ($orders) {
                foreach ($orders as $order) {
                    $idOrder = (int) $order->id;
                    break;
                }
            }
        } elseif (Order::getCartIdStatic((int) Tools::getValue('id_order'))) {
            $idOrder = (int) Tools::getValue('id_order');
        }

        return (int) $idOrder;
    }

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_.'contact-form.css');
        $this->addJS(_THEME_JS_DIR_.'contact-form.js');
        $this->addJS(_PS_JS_DIR_.'validate.js');
    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        parent::initContent();

        $this->assignOrderList();

        $email = Tools::safeOutput(
            Tools::getValue(
                'from',
                ((isset($this->context->cookie) && isset($this->context->cookie->email) && Validate::isEmail($this->context->cookie->email)) ? $this->context->cookie->email : '')
            )
        );
        $this->context->smarty->assign(
            [
                'errors'          => $this->errors,
                'email'           => $email,
                'fileupload'      => Configuration::get('PS_CUSTOMER_SERVICE_FILE_UPLOAD'),
                'max_upload_size' => (int) Tools::getMaxUploadSize(),
            ]
        );

        if (($idCustomerThread = (int) Tools::getValue('id_customer_thread')) && $token = Tools::getValue('token')) {
            $customerThread = Db::getInstance()->getRow(
                '
				SELECT cm.*
				FROM '._DB_PREFIX_.'customer_thread cm
				WHERE cm.id_customer_thread = '.(int) $idCustomerThread.'
				AND cm.id_shop = '.(int) $this->context->shop->id.'
				AND token = \''.pSQL($token).'\'
			'
            );

            $order = new Order((int) $customerThread['id_order']);
            if (Validate::isLoadedObject($order)) {
                $customerThread['reference'] = $order->getUniqReference();
            }
            $this->context->smarty->assign('customerThread', $customerThread);
        }

        $this->context->smarty->assign(
            [
                'contacts' => Contact::getContacts($this->context->language->id),
                'message'  => html_entity_decode(Tools::getValue('message')),
            ]
        );

        $this->setTemplate(_PS_THEME_DIR_.'contact-form.tpl');
    }

    /**
     * Assign template vars related to order list and product list ordered by the customer
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function assignOrderList()
    {
        if ($this->context->customer->isLogged()) {
            $this->context->smarty->assign('isLogged', 1);

            $products = [];
            $result = Db::getInstance()->executeS(
                '
			SELECT id_order
			FROM '._DB_PREFIX_.'orders
			WHERE id_customer = '.(int) $this->context->customer->id.Shop::addSqlRestriction(Shop::SHARE_ORDER).' ORDER BY date_add'
            );

            $orders = [];

            foreach ($result as $row) {
                $order = new Order($row['id_order']);
                $date = explode(' ', $order->date_add);
                $tmp = $order->getProducts();
                foreach ($tmp as $key => $val) {
                    $products[$row['id_order']][$val['product_id']] = ['value' => $val['product_id'], 'label' => $val['product_name']];
                }

                $orders[] = ['value' => $order->id, 'label' => $order->getUniqReference().' - '.Tools::displayDate($date[0], null), 'selected' => (int) $this->getOrder() == $order->id];
            }

            $this->context->smarty->assign('orderList', $orders);
            $this->context->smarty->assign('orderedProductList', $products);
        }
    }
}
