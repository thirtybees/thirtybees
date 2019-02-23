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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

class OrderHistoryCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int Order id */
    public $id_order;
    /** @var int Order status id */
    public $id_order_state;
    /** @var int Employee id for this history entry */
    public $id_employee;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'order_history',
        'primary' => 'id_order_history',
        'fields'  => [
            'id_order'       => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedId', 'required' => true],
            'id_order_state' => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedId', 'required' => true],
            'id_employee'    => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedId'                    ],
            'date_add'       => ['type' => self::TYPE_DATE, 'validate' => 'isDate'                          ],
        ],
    ];

    /**
     * @see  ObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'objectsNodeName' => 'order_histories',
        'fields'          => [
            'id_employee'    => ['xlink_resource' => 'employees'],
            'id_order_state' => ['required' => true, 'xlink_resource' => 'order_states'],
            'id_order'       => ['xlink_resource' => 'orders'],
        ],
        'objectMethods'   => [
            'add' => 'addWs',
        ],
    ];

    /**
     * Sets the new state of the given order
     *
     * @param int       $newOrderState
     * @param int|Order $idOrder
     * @param bool      $useExistingPayment
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function changeIdOrderState($newOrderState, $idOrder, $useExistingPayment = false)
    {
        if (!$newOrderState || !$idOrder) {
            return;
        }

        if (is_numeric($idOrder)) {
            $order = new Order((int) $idOrder);
        } elseif ($idOrder instanceof Order) {
            $order = $idOrder;
        } else {
            return;
        }

        ShopUrl::cacheMainDomainForShop($order->id_shop);

        $newOs = new OrderState((int) $newOrderState, $order->id_lang);
        $oldOs = $order->getCurrentOrderState();

        // executes hook
        if (in_array($newOs->id, [Configuration::get('PS_OS_PAYMENT'), Configuration::get('PS_OS_WS_PAYMENT')])) {
            Hook::exec('actionPaymentConfirmation', ['id_order' => (int) $order->id], null, false, true, false, $order->id_shop);
        }

        // executes hook
        Hook::exec('actionOrderStatusUpdate', ['newOrderStatus' => $newOs, 'id_order' => (int) $order->id], null, false, true, false, $order->id_shop);

        if (Validate::isLoadedObject($order) && ($newOs instanceof OrderState)) {
            $context = Context::getContext();

            // An email is sent the first time a virtual item is validated
            $virtualProducts = $order->getVirtualProducts();
            if (is_array($virtualProducts) && !empty($virtualProducts) && (!$oldOs || !$oldOs->logable) && $newOs && $newOs->logable) {
                $assign = [];
                foreach ($virtualProducts as $key => $virtualProduct) {
                    $idProductDownload = ProductDownload::getIdFromIdProduct($virtualProduct['product_id']);
                    $productDownload = new ProductDownload($idProductDownload);
                    // If this virtual item has an associated file, we'll provide the link to download the file in the email
                    if ($productDownload->display_filename != '') {
                        $assign[$key]['name'] = $productDownload->display_filename;
                        $downloadLink = $productDownload->getTextLink(false, $virtualProduct['download_hash']).'&id_order='.(int) $order->id.'&secure_key='.$order->secure_key;
                        $assign[$key]['link'] = $downloadLink;
                        if (isset($virtualProduct['download_deadline']) && $virtualProduct['download_deadline'] != '0000-00-00 00:00:00') {
                            $assign[$key]['deadline'] = Tools::displayDate($virtualProduct['download_deadline']);
                        }
                        if ($productDownload->nb_downloadable != 0) {
                            $assign[$key]['downloadable'] = (int) $productDownload->nb_downloadable;
                        }
                    }
                }

                $customer = new Customer((int) $order->id_customer);

                $links = '<ul>';
                foreach ($assign as $product) {
                    $links .= '<li>';
                    $links .= '<a href="'.$product['link'].'">'.Tools::htmlentitiesUTF8($product['name']).'</a>';
                    if (isset($product['deadline'])) {
                        $links .= '&nbsp;'.Tools::htmlentitiesUTF8(Tools::displayError('expires on', false)).'&nbsp;'.$product['deadline'];
                    }
                    if (isset($product['downloadable'])) {
                        $links .= '&nbsp;'.Tools::htmlentitiesUTF8(sprintf(Tools::displayError('downloadable %d time(s)', false), (int) $product['downloadable']));
                    }
                    $links .= '</li>';
                }
                $links .= '</ul>';
                $data = [
                    '{lastname}'        => $customer->lastname,
                    '{firstname}'       => $customer->firstname,
                    '{id_order}'        => (int) $order->id,
                    '{order_name}'      => $order->getUniqReference(),
                    '{nbProducts}'      => count($virtualProducts),
                    '{virtualProducts}' => $links,
                ];
                // If there is at least one downloadable file
                if (!empty($assign)) {
                    Mail::Send((int) $order->id_lang, 'download_product', Mail::l('The virtual product that you bought is available for download', $order->id_lang), $data, $customer->email, $customer->firstname.' '.$customer->lastname, null, null, null, null, _PS_MAIL_DIR_, false, (int) $order->id_shop);
                }
            }

            // @since 1.5.0 : gets the stock manager
            $manager = null;
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $manager = StockManagerFactory::getManager();
            }

            $errorOrCanceledStatuses = [Configuration::get('PS_OS_ERROR'), Configuration::get('PS_OS_CANCELED')];

            $employee = null;
            if (!(int) $this->id_employee || !Validate::isLoadedObject(($employee = new Employee((int) $this->id_employee)))) {
                if (!Validate::isLoadedObject($oldOs) && $context != null) {
                    // First OrderHistory, there is no $old_os, so $employee is null before here
                    $employee = $context->employee; // filled if from BO and order created (because no old_os)
                    if ($employee) {
                        $this->id_employee = $employee->id;
                    }
                } else {
                    $employee = null;
                }
            }

            // foreach products of the order
            foreach ($order->getProductsDetail() as $product) {
                if (Validate::isLoadedObject($oldOs)) {
                    // if becoming logable => adds sale
                    if ($newOs->logable && !$oldOs->logable) {
                        ProductSale::addProductSale($product['product_id'], $product['product_quantity']);
                        // @since 1.5.0 - Stock Management
                        if (!Pack::isPack($product['product_id']) &&
                            in_array($oldOs->id, $errorOrCanceledStatuses) &&
                            !StockAvailable::dependsOnStock($product['id_product'], (int) $order->id_shop)) {
                            StockAvailable::updateQuantity($product['product_id'], $product['product_attribute_id'], -(int) $product['product_quantity'], $order->id_shop);
                        }
                    } elseif (!$newOs->logable && $oldOs->logable) {
                        // if becoming unlogable => removes sale
                        ProductSale::removeProductSale($product['product_id'], $product['product_quantity']);

                        // @since 1.5.0 - Stock Management
                        if (!Pack::isPack($product['product_id']) &&
                            in_array($newOs->id, $errorOrCanceledStatuses) &&
                            !StockAvailable::dependsOnStock($product['id_product'])) {
                            StockAvailable::updateQuantity($product['product_id'], $product['product_attribute_id'], (int) $product['product_quantity'], $order->id_shop);
                        }
                    } elseif (!$newOs->logable && !$oldOs->logable &&
                            in_array($newOs->id, $errorOrCanceledStatuses) &&
                            !in_array($oldOs->id, $errorOrCanceledStatuses) &&
                            !StockAvailable::dependsOnStock($product['id_product'])) {
                        // if waiting for payment => payment error/canceled
                        StockAvailable::updateQuantity($product['product_id'], $product['product_attribute_id'], (int) $product['product_quantity'], $order->id_shop);
                    }
                }
                // From here, there is 2 cases : $old_os exists, and we can test shipped state evolution,
                // Or old_os does not exists, and we should consider that initial shipped state is 0 (to allow decrease of stocks)

                // @since 1.5.0 : if the order is being shipped and this products uses the advanced stock management :
                // decrements the physical stock using $id_warehouse
                if ($newOs->shipped == 1 && (!Validate::isLoadedObject($oldOs) || $oldOs->shipped == 0) &&
                    Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') &&
                    Warehouse::exists($product['id_warehouse']) &&
                    $manager != null &&
                    (int) $product['advanced_stock_management'] == 1) {
                    // gets the warehouse
                    $warehouse = new Warehouse($product['id_warehouse']);

                    // decrements the stock (if it's a pack, the StockManager does what is needed)
                    $manager->removeProduct(
                        $product['product_id'],
                        $product['product_attribute_id'],
                        $warehouse,
                        ($product['product_quantity'] - $product['product_quantity_refunded'] - $product['product_quantity_return']),
                        Configuration::get('PS_STOCK_CUSTOMER_ORDER_REASON'),
                        true,
                        (int) $order->id,
                        0,
                        $employee
                    );
                } elseif ($newOs->shipped == 0 && Validate::isLoadedObject($oldOs) && $oldOs->shipped == 1 &&
                        Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') &&
                        Warehouse::exists($product['id_warehouse']) &&
                        $manager != null &&
                        (int) $product['advanced_stock_management'] == 1) {
                    // @since.1.5.0 : if the order was shipped, and is not anymore, we need to restock products
                    // if the product is a pack, we restock every products in the pack using the last negative stock mvts
                    if (Pack::isPack($product['product_id'])) {
                        $packProducts = Pack::getItems($product['product_id'], Configuration::get('PS_LANG_DEFAULT', null, null, $order->id_shop));
                        if (is_array($packProducts && !empty($packProducts))) {
                            foreach ($packProducts as $packProduct) {
                                if ($packProduct->advanced_stock_management == 1) {
                                    $mvts = StockMvt::getNegativeStockMvts($order->id, $packProduct->id, 0, $packProduct->pack_quantity * $product['product_quantity']);
                                    foreach ($mvts as $mvt) {
                                        $manager->addProduct(
                                            $packProduct->id,
                                            0,
                                            new Warehouse($mvt['id_warehouse']),
                                            $mvt['physical_quantity'],
                                            null,
                                            $mvt['price_te'],
                                            true,
                                            null
                                        );
                                    }
                                    if (!StockAvailable::dependsOnStock($product['id_product'])) {
                                        StockAvailable::updateQuantity($packProduct->id, 0, (int) $packProduct->pack_quantity * $product['product_quantity'], $order->id_shop);
                                    }
                                }
                            }
                        }
                    } else {
                        // else, it's not a pack, re-stock using the last negative stock mvts
                        $mvts = StockMvt::getNegativeStockMvts(
                            $order->id,
                            $product['product_id'],
                            $product['product_attribute_id'],
                            ($product['product_quantity'] - $product['product_quantity_refunded'] - $product['product_quantity_return'])
                        );

                        foreach ($mvts as $mvt) {
                            $manager->addProduct(
                                $product['product_id'],
                                $product['product_attribute_id'],
                                new Warehouse($mvt['id_warehouse']),
                                $mvt['physical_quantity'],
                                null,
                                $mvt['price_te'],
                                true
                            );
                        }
                    }
                }
            }
        }

        $this->id_order_state = (int) $newOrderState;

        // changes invoice number of order ?
        if (!Validate::isLoadedObject($newOs) || !Validate::isLoadedObject($order)) {
            die(Tools::displayError('Invalid new order status'));
        }

        // the order is valid if and only if the invoice is available and the order is not cancelled
        $order->current_state = $this->id_order_state;
        $order->valid = $newOs->logable;
        $order->update();

        if ($newOs->invoice && !$order->invoice_number) {
            $order->setInvoice($useExistingPayment);
        } elseif ($newOs->delivery && !$order->delivery_number) {
            $order->setDeliverySlip();
        }

        // set orders as paid
        if ($newOs->paid == 1) {
            $invoices = $order->getInvoicesCollection();
            if ($order->total_paid != 0) {
                $paymentMethod = Module::getInstanceByName($order->module);
            }

            foreach ($invoices as $invoice) {
                /** @var OrderInvoice $invoice */
                $restPaid = $invoice->getRestPaid();
                if ($restPaid > 0) {
                    $payment = new OrderPayment();
                    $payment->order_reference = mb_substr($order->reference, 0, 9);
                    $payment->id_currency = $order->id_currency;
                    $payment->amount = $restPaid;

                    if (isset($paymentMethod) && $order->total_paid != 0) {
                        $payment->payment_method = $paymentMethod->displayName;
                    } else {
                        $payment->payment_method = null;
                    }

                    // Update total_paid_real value for backward compatibility reasons
                    if ($payment->id_currency == $order->id_currency) {
                        $order->total_paid_real += $payment->amount;
                    } else {
                        $order->total_paid_real += Tools::convertPrice($payment->amount, $payment->id_currency, false);
                    }
                    $order->save();

                    $payment->conversion_rate = 1;
                    $payment->save();
                    Db::getInstance()->insert(
                        'order_invoice_payment',
                        [
                            'id_order_invoice' => (int) $invoice->id,
                            'id_order_payment' => (int) $payment->id,
                            'id_order'         => (int) $order->id,
                        ]
                    );
                }
            }
        }

        // updates delivery date even if it was already set by another state change
        if ($newOs->delivery) {
            $order->setDelivery();
        }

        // executes hook
        Hook::exec('actionOrderStatusPostUpdate', ['newOrderStatus' => $newOs, 'id_order' => (int) $order->id], null, false, true, false, $order->id_shop);

        ShopUrl::resetMainDomainCache();
    }

    /**
     * Returns the last order status
     *
     * @param int $idOrder
     *
     * @return OrderState|false
     *
     * @deprecated 2.0.0
     * @see        Order->current_state
     * @throws PrestaShopException
     */
    public static function getLastOrderState($idOrder)
    {
        Tools::displayAsDeprecated();
        $idOrderState = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_order_state`')
                ->from('order_history')
                ->where('`id_order` = '.(int) $idOrder)
                ->orderBy('`date_add` DESC, `id_order_history` DESC')
        );

        // returns false if there is no state
        if (!$idOrderState) {
            return false;
        }

        // else, returns an OrderState object if it can be loaded
        $orderState = new OrderState($idOrderState, Configuration::get('PS_LANG_DEFAULT'));

        if (Validate::isLoadedObject($orderState)) {
            return $orderState;
        }

        return false;
    }

    /**
     * @param bool       $autodate     Optional
     * @param bool|array $templateVars Optional
     * @param Context    $context      Deprecated
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial versions
     */
    public function addWithemail($autodate = true, $templateVars = false, Context $context = null)
    {
        $order = new Order($this->id_order);

        if (!$this->add($autodate)) {
            return false;
        }

        if (!$this->sendEmail($order, $templateVars)) {
            return false;
        }

        return true;
    }

    /**
     * @param Order $order
     * @param bool  $templateVars
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function sendEmail($order, $templateVars = false)
    {
        $result = Db::getInstance()->getRow('
			SELECT osl.`template`, c.`lastname`, c.`firstname`, osl.`name` AS osname, c.`email`, os.`module_name`, os.`id_order_state`, os.`pdf_invoice`, os.`pdf_delivery`
			FROM `'._DB_PREFIX_.'order_history` oh
				LEFT JOIN `'._DB_PREFIX_.'orders` o ON oh.`id_order` = o.`id_order`
				LEFT JOIN `'._DB_PREFIX_.'customer` c ON o.`id_customer` = c.`id_customer`
				LEFT JOIN `'._DB_PREFIX_.'order_state` os ON oh.`id_order_state` = os.`id_order_state`
				LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = o.`id_lang`)
			WHERE oh.`id_order_history` = '.(int) $this->id.' AND os.`send_email` = 1');
        if (isset($result['template']) && Validate::isEmail($result['email'])) {
            ShopUrl::cacheMainDomainForShop($order->id_shop);

            $topic = $result['osname'];
            $data = [
                '{lastname}'   => $result['lastname'],
                '{firstname}'  => $result['firstname'],
                '{id_order}'   => (int) $this->id_order,
                '{order_name}' => $order->getUniqReference(),
            ];

            if ($result['module_name']) {
                $module = Module::getInstanceByName($result['module_name']);
                if (Validate::isLoadedObject($module) && isset($module->extra_mail_vars) && is_array($module->extra_mail_vars)) {
                    $data = array_merge($data, $module->extra_mail_vars);
                }
            }

            if ($templateVars) {
                $data = array_merge($data, $templateVars);
            }

            $data['{total_paid}'] = Tools::displayPrice((float) $order->total_paid, new Currency((int) $order->id_currency), false);

            if (Validate::isLoadedObject($order)) {
                // Attach invoice and / or delivery-slip if they exists and status is set to attach them
                if (($result['pdf_invoice'] || $result['pdf_delivery'])) {
                    $context = Context::getContext();
                    $invoice = $order->getInvoicesCollection();
                    $fileAttachement = [];

                    if ($result['pdf_invoice'] && (int) Configuration::get('PS_INVOICE') && $order->invoice_number) {
                        Hook::exec('actionPDFInvoiceRender', ['order_invoice_list' => $invoice]);
                        $pdf = new PDF($invoice, PDF::TEMPLATE_INVOICE, $context->smarty);
                        $fileAttachement['invoice']['content'] = $pdf->render(false);
                        $fileAttachement['invoice']['name'] = Configuration::get('PS_INVOICE_PREFIX', (int) $order->id_lang, null, $order->id_shop).sprintf('%06d', $order->invoice_number).'.pdf';
                        $fileAttachement['invoice']['mime'] = 'application/pdf';
                    }
                    if ($result['pdf_delivery'] && $order->delivery_number) {
                        $pdf = new PDF($invoice, PDF::TEMPLATE_DELIVERY_SLIP, $context->smarty);
                        $fileAttachement['delivery']['content'] = $pdf->render(false);
                        $fileAttachement['delivery']['name'] = Configuration::get('PS_DELIVERY_PREFIX', Context::getContext()->language->id, null, $order->id_shop).sprintf('%06d', $order->delivery_number).'.pdf';
                        $fileAttachement['delivery']['mime'] = 'application/pdf';
                    }
                } else {
                    $fileAttachement = null;
                }

                if (!Mail::Send(
                    (int) $order->id_lang,
                    $result['template'],
                    $topic,
                    $data,
                    $result['email'],
                    $result['firstname'].' '.$result['lastname'],
                    null,
                    null,
                    $fileAttachement,
                    null,
                    _PS_MAIL_DIR_,
                    false,
                    (int) $order->id_shop
                )) {
                    return false;
                }
            }

            ShopUrl::resetMainDomainCache();
        }

        return true;
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (!parent::add($autoDate)) {
            return false;
        }

        $order = new Order((int) $this->id_order);
        // Update id_order_state attribute in Order
        $order->current_state = $this->id_order_state;
        $order->update();

        Hook::exec('actionOrderHistoryAddAfter', ['order_history' => $this], null, false, true, false, $order->id_shop);

        return true;
    }

    /**
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function isValidated()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(oh.`id_order_history` AS `nb`')
                ->from('order_state', 'os')
                ->leftJoin('order_history', 'oh', 'os.`id_order_state` = oh.`id_order_state`')
                ->where('oh.`id_order` = '.(int) $this->id_order)
                ->where('od.`logable` = 1')
        );
    }

    /**
     * Add method for webservice create resource Order History
     * If sendemail=1 GET parameter is present sends email to customer otherwise does not
     *
     * @return bool
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addWs()
    {
        $sendemail = (bool) Tools::getValue('sendemail', false);
        $this->changeIdOrderState($this->id_order_state, $this->id_order);

        if ($sendemail) {
            //Mail::Send requires link object on context and is not set when getting here
            $context = Context::getContext();
            if ($context->link == null) {
                $protocolLink = (Tools::usingSecureMode() && Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
                $protocolContent = (Tools::usingSecureMode() && Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
                $context->link = new Link($protocolLink, $protocolContent);
            }

            return $this->addWithemail();
        } else {
            return $this->add();
        }
    }
}
