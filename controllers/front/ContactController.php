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
 * Class ContactControllerCore
 */
class ContactControllerCore extends FrontController
{
    /** @var string $php_self */
    public $php_self = 'contact';
    /** @var bool $ssl */
    public $ssl = true;

    /**
     * Start forms process
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitMessage')) {

            // Todo: Once getFileInformations() is also defined for other types than image, the $extensions array can be emptied
            $extension = ['.txt', '.rtf', '.doc', '.docx', '.pdf', '.zip'];

            $fileInfos = Media::getFileInformations();

            foreach ($fileInfos as $fileInfo) {
                foreach ($fileInfo as $mainExtension => $fileExtensionInfo) {
                    if ($fileExtensionInfo['uploadFrontOffice']) {
                        $extension[] = '.'.$mainExtension;
                    }
                }
            }

            $fileAttachment = Tools::fileAttachment('fileUpload');
            $message = Tools::getValue('message'); // Html entities is not usefull, iscleanHtml check there is no bad html tags.
            if (!($from = Tools::convertEmailToIdn(trim(Tools::getValue('from')))) || !Validate::isEmail($from)) {
                $this->errors[] = Tools::displayError('Invalid email address.');
            } elseif (!$message) {
                $this->errors[] = Tools::displayError('The message cannot be blank.');
            } elseif (!Validate::isCleanHtml($message)) {
                $this->errors[] = Tools::displayError('Invalid message');
            } elseif (!($idContact = Tools::getIntValue('id_contact')) || !(Validate::isLoadedObject($contact = new Contact($idContact, $this->context->language->id)))) {
                $this->errors[] = Tools::displayError('Please select a subject from the list provided. ');
            } elseif (!empty($fileAttachment['name']) && $fileAttachment['error'] != 0) {
                $this->errors[] = Tools::displayError('An error occurred during the file-upload process.');
            } elseif (!empty($fileAttachment['name']) && !in_array(mb_strtolower(substr($fileAttachment['name'], -4)), $extension) && !in_array(mb_strtolower(substr($fileAttachment['name'], -5)), $extension)) {
                $this->errors[] = Tools::displayError('Bad file extension');
            } else {
                $customer = $this->context->customer;
                if (!$customer->id) {
                    $customer->getByEmail($from);
                }

                $idOrder = (int) $this->getOrder($customer);

                $conn = Db::readOnly();
                $idCustomerThread = $this->resolveCustomerThreadId($from, $idOrder, $customer, $idContact);
                $oldMessage = '';
                if ($idCustomerThread) {
                    $oldMessage = $conn->getValue((new DbQuery())
                        ->select('cm.`message`')
                        ->from('customer_message', 'cm')
                        ->leftJoin('customer_thread', 'cc', 'cm.`id_customer_thread` = cc.`id_customer_thread`')
                        ->where('cc.`id_customer_thread` = ' . (int)$idCustomerThread)
                        ->where('cc.`id_shop` = ' . (int)$this->context->shop->id)
                        ->orderBy('cm.`date_add` DESC')
                    );
                }
                if ($oldMessage === $message) {
                    $this->context->smarty->assign('alreadySent', 1);
                } else {
                    $ct = null;
                    if ($contact->customer_service) {
                        if ((int)$idCustomerThread) {
                            $ct = new CustomerThread($idCustomerThread);
                            $ct->status = 'open';
                            $ct->id_lang = (int)$this->context->language->id;
                            $ct->id_contact = (int)$idContact;
                            $ct->id_order = (int)$idOrder;
                            if ($idProduct = Tools::getIntValue('id_product')) {
                                $ct->id_product = $idProduct;
                            }
                            $ct->update();
                        } else {
                            $ct = new CustomerThread();
                            if (isset($customer->id)) {
                                $ct->id_customer = (int)$customer->id;
                            }
                            $ct->id_shop = (int)$this->context->shop->id;
                            $ct->id_order = (int)$idOrder;
                            if ($idProduct = Tools::getIntValue('id_product')) {
                                $ct->id_product = $idProduct;
                            }
                            $ct->id_contact = (int)$idContact;
                            $ct->id_lang = (int)$this->context->language->id;
                            $ct->email = $from;
                            $ct->status = 'open';
                            $ct->token = Tools::passwdGen(12);
                            $ct->add();
                        }

                        if ($ct->id) {
                            $cm = new CustomerMessage();
                            $cm->id_customer_thread = $ct->id;
                            $cm->message = $message;
                            if (!empty($fileAttachment['rename'])) {
                                $cm->file_name = basename($fileAttachment['rename']);
                                if (! rename($fileAttachment['tmp_name'], $cm->getFilePath())) {
                                    $cm->file_name = null;
                                }
                            }
                            $cm->ip_address = (int)ip2long(Tools::getRemoteAddr());
                            $length = ObjectModel::getDefinition('CustomerMessage', 'user_agent')['size'];
                            $cm->user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, $length);
                            if (!$cm->add()) {
                                $this->errors[] = Tools::displayError('An error occurred while sending the message.');
                            }
                        } else {
                            $this->errors[] = Tools::displayError('An error occurred while sending the message.');
                        }
                    }

                    if (! $this->errors) {
                        $this->sendEmails($message, $from, $fileAttachment, $ct, $contact);
                    }
                }

                if (! $this->errors) {
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
     * @throws PrestaShopException
     */
    protected function getOrder(Customer $customer)
    {
        if (Validate::isLoadedObject($customer)) {
            $idOrder = Tools::getIntValue('id_order');
            if ($idOrder) {
                $order = new Order($idOrder);
            } else {
                $reference = trim((string)Tools::getValue('id_order'));
                if ($reference) {
                    $order = Order::getByReference($reference)->getFirst();
                } else {
                    $order = null;
                }
            }

            if (Validate::isLoadedObject($order) && (int)$order->id_customer === (int)$customer->id) {
                return (int)$order->id;
            }

        }
        return 0;
    }

    /**
     * Set media
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @throws PrestaShopException
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $this->assignOrderList();

        $email = Tools::convertEmailToIdn(Tools::safeOutput(
            Tools::getValue(
                'from',
                ((isset($this->context->cookie) && isset($this->context->cookie->email) && Validate::isEmail($this->context->cookie->email)) ? $this->context->cookie->email : '')
            )
        ));
        $this->context->smarty->assign(
            [
                'errors'          => $this->errors,
                'email'           => $email,
                'fileupload'      => Configuration::get('PS_CUSTOMER_SERVICE_FILE_UPLOAD'),
                'max_upload_size' => (int) Tools::getMaxUploadSize(),
            ]
        );

        if (($idCustomerThread = Tools::getIntValue('id_customer_thread')) && $token = Tools::getValue('token')) {
            $customerThread = Db::readOnly()->getRow(
                (new DbQuery())
                    ->select('cm.*')
                    ->from('customer_thread', 'cm')
                    ->where('cm.`id_customer_thread` = '.(int) $idCustomerThread)
                    ->where('cm.`id_shop` = '.(int) $this->context->shop->id)
                    ->where('cm.`token` = \''.pSQL($token).'\'')
            );
            if ($customerThread) {
                $order = new Order((int) $customerThread['id_order']);
                if (Validate::isLoadedObject($order)) {
                    $customerThread['reference'] = $order->getUniqReference();
                }
                $this->context->smarty->assign('customerThread', $customerThread);
            }
        }

        $this->context->smarty->assign(
            [
                'contacts' => Contact::getContacts($this->context->language->id, true),
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function assignOrderList()
    {
        if ($this->context->customer->isLogged()) {
            $this->context->smarty->assign('isLogged', 1);

            $products = [];
            $result = Db::readOnly()->getArray(
                (new DbQuery())
                    ->select('`id_order`')
                    ->from('orders')
                    ->where('`id_customer` = '.(int) $this->context->customer->id.Shop::addSqlRestriction(Shop::SHARE_ORDER))
                    ->orderBy('`date_add`')
            );

            $orders = [];

            $orderId = (int)$this->getOrder($this->context->customer);
            foreach ($result as $row) {
                $order = new Order($row['id_order']);
                $date = explode(' ', $order->date_add);
                $tmp = $order->getProducts();
                foreach ($tmp as $val) {
                    $products[$row['id_order']][$val['product_id']] = ['value' => $val['product_id'], 'label' => $val['product_name']];
                }

                $orders[] = [
                    'value' => (int)$order->id,
                    'label' => $order->getUniqReference().' - '.Tools::displayDate($date[0], null),
                    'selected' => $orderId == $order->id
                ];
            }

            $this->context->smarty->assign('orderList', $orders);
            $this->context->smarty->assign('orderedProductList', $products);
        }
    }

    /**
     * Sends notification email to Contact email address
     *
     * @param array $varList
     * @param Contact $contact
     * @param array|null $fileAttachment
     * @param string $from
     *
     * @return bool
     * @throws PrestaShopException
     */
    protected function sendNotificationEmail(array $varList, Contact $contact, ?array $fileAttachment, string $from): bool
    {
        return Mail::Send(
            $this->context->language->id,
            'contact',
            Mail::l('Message from contact form') . ' [no_sync]',
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
        );
    }

    /**
     * Sends confirmation email to customer email address, if enabled in Contact object
     *
     * @param CustomerThread|null $ct
     * @param array $varList
     * @param string $to
     * @param array|null $fileAttachment
     * @param Contact $contact
     *
     * @return bool
     * @throws PrestaShopException
     */
    protected function sendConfirmationEmail(?CustomerThread $ct, array $varList, string $to, ?array $fileAttachment, Contact $contact): bool
    {
        if ($contact->send_confirm) {
            if (Validate::isLoadedObject($ct)) {
                return Mail::Send(
                    $this->context->language->id,
                    'contact_form',
                    sprintf(Mail::l('Your message has been correctly sent #ct%1$s #tc%2$s'), $ct->id, $ct->token),
                    $varList,
                    $to,
                    null,
                    null,
                    null,
                    $fileAttachment,
                    null,
                    _PS_MAIL_DIR_,
                    false,
                    null,
                    null,
                    $contact->email
                );
            } else {
                return Mail::Send(
                    $this->context->language->id,
                    'contact_form',
                    Mail::l('Your message has been correctly sent'),
                    $varList,
                    $to,
                    null,
                    null,
                    null,
                    $fileAttachment,
                    null,
                    _PS_MAIL_DIR_,
                    false,
                    null,
                    null,
                    $contact->email
                );
            }
        }
        return true;
    }

    /**
     * Send notification email to employee, and optionally confirmation email to customer
     *
     * @param string $message
     * @param string $from
     * @param array|null $fileAttachment
     * @param CustomerThread|null $ct
     * @param Contact $contact
     *
     * @return void
     * @throws PrestaShopException
     */
    protected function sendEmails($message, string $from, ?array $fileAttachment, ?CustomerThread $ct, Contact $contact): void
    {
        $varList = [
            '{order_name}' => '-',
            '{attached_file}' => '-',
            '{message}' => Tools::nl2br(stripslashes($message)),
            '{email}' => $from,
            '{product_name}' => '',
        ];

        if (isset($fileAttachment['name'])) {
            $varList['{attached_file}'] = $fileAttachment['name'];
        }

        if (Validate::isLoadedObject($ct) && $ct->id_order) {
            $order = new Order((int)$ct->id_order);
            $varList['{order_name}'] = $order->getUniqReference();
            $varList['{id_order}'] = (int)$order->id;
        }

        $idProduct = Tools::getIntValue('id_product');
        if ($idProduct) {
            $product = new Product((int)$idProduct);
            if (Validate::isLoadedObject($product) && isset($product->name[$this->context->language->id])) {
                $varList['{product_name}'] = $product->name[$this->context->language->id];
            }
        }

        if (!$this->sendNotificationEmail($varList, $contact, $fileAttachment, $from)) {
            $this->errors[] = Tools::displayError('An error occurred while sending the message.');
        }

        if (!$this->sendConfirmationEmail($ct, $varList, $from, $fileAttachment, $contact)) {
            $this->errors[] = Tools::displayError('An error occurred while sending the message.');
        }
    }

    /**
     * @param string $from
     * @param int $idOrder
     * @param Customer $customer
     * @param int $idContact
     *
     * @return int
     * @throws PrestaShopException
     */
    protected function resolveCustomerThreadId(string $from, int $idOrder, Customer $customer, int $idContact)
    {
        $conn = Db::readOnly();

        // first, check explicitly provided customer thread id
        $idCustomerThread = Tools::getIntValue('id_customer_thread');
        if ($idCustomerThread) {
            // verify token value
            $tokenMatches = (bool)$conn->getValue(
                (new DbQuery())
                    ->select('ct.`id_customer_thread`')
                    ->from('customer_thread', 'ct')
                    ->where('ct.`id_customer_thread` = ' . (int)$idCustomerThread)
                    ->where('ct.`id_shop` = ' . (int)$this->context->shop->id)
                    ->where('ct.`token` = \'' . pSQL(Tools::getValue('token')) . '\'')
            );
            if ($tokenMatches) {
                return $idCustomerThread;
            }
        }

        // find customer thread by combination of from address and order id
        $idCustomerThread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($from, $idOrder);
        if ($idCustomerThread) {
            return $idCustomerThread;
        }

        // find best matching customer thread
        $idCustomerThread = 0;
        $fields = $conn->getArray(
            (new DbQuery())
                ->select('ct.`id_customer_thread`, ct.`id_contact`, ct.`id_customer`, ct.`id_order`, ct.`id_product`, ct.`email`')
                ->from('customer_thread', 'ct')
                ->where('ct.`email` = \'' . pSQL($from) . '\'')
                ->where('ct.`id_shop` = ' . (int)$this->context->shop->id)
                ->where('(' . ($customer->id ? 'id_customer = ' . (int)$customer->id . ' OR ' : '') . ' id_order = ' . (int)$idOrder . ')')
        );
        $score = 0;
        foreach ($fields as $row) {
            $tmp = 0;
            if ((int)$row['id_customer'] && $row['id_customer'] != $customer->id && $row['email'] != $from) {
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
            if (Tools::getIntValue('id_product') !== 0 && (int)$row['id_product'] === Tools::getIntValue('id_product')) {
                $tmp += 2;
            }
            if ($tmp >= 5 && $tmp >= $score) {
                $score = $tmp;
                $idCustomerThread = (int)$row['id_customer_thread'];
            }
        }
        return $idCustomerThread;
    }
}
