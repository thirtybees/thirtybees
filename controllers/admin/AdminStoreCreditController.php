<?php
/**
 * Copyright (C) 2025-2025 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2025-2025 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * Class AdminStoreCreditController
 *
 * @property StoreCredit|null $object
 */
class AdminStoreCreditControllerCore extends AdminController
{
    /**
     * AdminStoreCreditController constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'store_credit';
        $this->className = 'StoreCredit';
        $this->lang = false;
        $this->_orderWay = 'DESC';

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            ]
        ];

        $isValid = '(a.date_to < "1900-00-00" OR a.date_to >= NOW())';
        $outstandingExpr = "IF($isValid, a.amount - a.amount_used, 0)";

        if ($this->isGroupedView()) {
            $this->addRowAction('view');
            $this->explicitSelect = true;
            $this->_use_found_rows = false;
            $this->identifier = 'id_customer';
            $this->bulk_actions = [];
            $this->list_id = 'storecreditsgrouped';
            $this->_join = implode('', [
                'LEFT JOIN `' . _DB_PREFIX_ . 'customer` `c` ON (`c`.`id_customer` = `a`.`id_customer`)',
            ]);
            $this->_select = implode(',', [
                'IF(a.id_customer = 0, "Not redeemed codes", CONCAT(`c`.`firstname`, " ", `c`.`lastname`)) AS `customer_name`',
                '`c`.`email` as email',
                "SUM($outstandingExpr) AS `amount_outstanding`",
            ]);
            $this->_defaultOrderBy = 'amount_outstanding';
            $this->_defaultOrderWay = 'DESC';
            $this->_group = ' GROUP BY c.`id_customer`';
            $this->fields_list = [
                'id_customer' => [
                    'title' => $this->l('Customer ID'),
                    'align' => 'center',
                    'class' => 'fixed-width-xs',
                    'filter_key' => 'c!id_customer',
                ],
                'customer_name' => [
                    'title' => $this->l('Customer Name'),
                    'callback_object' => $this,
                    'callback' => 'displayCustomerInfo',
                    'havingFilter' => true,
                ],
                'email' => [
                    'title' => $this->l('Customer Email'),
                    'callback_object' => $this,
                    'callback' => 'displayCustomerInfo',
                    'havingFilter' => true,
                ],
                'amount_outstanding' => [
                    'title' => $this->l('Amount outstanding'),
                    'align' => 'text-right',
                    'type' => 'price',
                    'currency' => true,
                    'havingFilter' => true,
                ],
            ];
        } else {
            $this->list_id = 'storecredits';
            $this->addRowAction('edit');
            $this->addRowAction('delete');
            $this->list_no_link = true;
            $customerId = Tools::getIntValue('id_customer');
            $this->_where .= 'AND a.id_customer = ' . $customerId;
            $this->_select = implode(', ', [
                "$outstandingExpr AS `amount_outstanding`",
            ]);
            $this->fields_list = [
                'id_store_credit' => [
                    'title' => $this->l('ID'),
                    'align' => 'center',
                    'class' => 'fixed-width-xs',
                ],
                'name' => [
                    'title' => $this->l('Name'),
                    'filter_key' => 'a!name',
                ],
                'code' => [
                    'title' => $this->l('Code'),
                    'class' => 'fixed-width-sm',
                ],
                'amount' => [
                    'title' => $this->l('Amount'),
                    'align' => 'text-right',
                    'type' => 'price',
                    'currency' => true,
                ],
                'amount_used' => [
                    'title' => $this->l('Amount used'),
                    'align' => 'text-right',
                    'type' => 'price',
                    'currency' => true,
                ],
                'amount_outstanding' => [
                    'title' => $this->l('Amount outstanding'),
                    'align' => 'text-right',
                    'type' => 'price',
                    'currency' => true,
                    'havingFilter' => true,
                ],
                'date_to' => [
                    'title' => $this->l('Expiration date'),
                    'type' => 'datetime',
                    'class' => 'fixed-width-lg',
                ],
            ];

            $currency = Currency::getCurrencyInstance(Configuration::get('PS_CURRENCY_DEFAULT'));
            $currencySymbol = $currency->getSign('left') . $currency->getSign('right');
            $this->fields_form = [
                'legend' => [
                    'title' => $this->l('Store credit'),
                    'icon'  => 'icon-money',
                ],
                'input'  => [
                    [
                        'type' => 'hidden',
                        'name' => 'id_customer',
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Code'),
                        'name'  => 'code',
                        'hint'  => $this->l('Unique code of the credit'),
                    ],
                    [
                        'type'  => 'price',
                        'prefix' => $currencySymbol,
                        'label' => $this->l('Amount'),
                        'name'  => 'amount',
                        'hint'  => $this->l('Original amount'),
                    ],
                    [
                        'type'  => 'price',
                        'prefix' => $currencySymbol,
                        'label' => $this->l('Amount used'),
                        'name'  => 'amount_used',
                        'hint'  => $this->l('Amount used'),
                    ],
                    [
                        'type'  => 'datetime',
                        'label' => $this->l('Valid from'),
                        'name'  => 'date_from',
                        'hint'  => $this->l('Date this credit is valid from'),
                    ],
                    [
                        'type'  => 'datetime',
                        'label' => $this->l('Valid to'),
                        'name'  => 'date_to',
                        'hint'  => $this->l('Credit expiration date'),
                    ],
                    [
                        'type'  => 'shop',
                        'label' => $this->l('Shop association'),
                        'name'  => 'checkBoxShopAsso',
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ];
        }

        parent::__construct();
    }

    /**
     * @throws PrestaShopException
     */
    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    /**
     * @param Helper $helper
     * @return void
     * @throws PrestaShopException
     */
    public function setHelperDisplay(Helper $helper)
    {
        parent::setHelperDisplay($helper);
        if ($helper instanceof HelperList) {
            if ($this->isGroupedView()) {
                $helper->title = $this->l('Store credits: grouped by customer');
                $helper->linkUrlCallback = [$this, 'getViewListUrl'];
            } else {
                $customerId = Tools::getIntValue('id_customer');
                $helper->currentIndex = $this->getCustomerCreditsUrl($customerId);
                if ($customerId) {
                    $customer = new Customer($customerId);
                    $customerName = trim($customer->firstname . ' ' . $customer->lastname);
                } else {
                    $customerName = $this->l('Not redeemed codes');
                }
                $helper->title = sprintf($this->l('Store credits: %s'), $customerName);
            }
        }
    }

    /**
     * @param array $row
     * @return string
     * @throws PrestaShopException
     */
    public function getViewListUrl($row)
    {
        return $this->getCustomerCreditsUrl((int)$row['id_customer']);
    }

    /**
     * @param string $value
     * @param array $row
     * @return string
     *
     * @throws PrestaShopException
     */
    public function displayCustomerInfo($value, $row): string
    {
        $customerId = (int)$row['id_customer'];
        if ($customerId) {
            $link = $this->context->link->getAdminLink('AdminCustomers', true, [
                'id_customer' => $customerId,
                'viewcustomer' => 1,
            ]);
            return '<a href="'.$link.'">' . Tools::safeOutput($value) . "</a>";
        } else {
            return $this->l('Not redeemed codes');
        }
    }

    /**
     * @return false|mixed
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $result = parent::postProcess();
        if ($this->redirect_after && Tools::isSubmit('id_customer')) {
            $this->setRedirectAfter($this->redirect_after . '&id_customer=' . (int)Tools::getValue('id_customer'));
        }
        return $result;
    }


    /**
     * @return bool
     */
    protected function isGroupedView(): bool
    {
        return !Tools::isSubmit('id_customer');
    }

    /**
     * @param int $customerId
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected function getCustomerCreditsUrl(int $customerId): string
    {
        return $this->context->link->getAdminLink('AdminStoreCredit', true, [
            'id_customer' => (int)$customerId,
        ]);
    }

    /**
     * Display view action link
     *
     * @param string|null $token
     * @param int $id
     * @param string|null $name
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayViewLink($token, $id, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
        $tpl->assign([
            'href'   => $this->getCustomerCreditsUrl((int)$id),
            'action' => $this->l('View vouchers')
        ]);

        return $tpl->fetch();
    }
}
