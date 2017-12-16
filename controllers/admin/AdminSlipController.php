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
 * Class AdminSlipControllerCore
 *
 * @since 1.0.0
 */
class AdminSlipControllerCore extends AdminController
{
    /**
     * AdminSlipControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'order_slip';
        $this->className = 'OrderSlip';

        $this->_select = ' o.`id_shop`';
        $this->_join .= ' LEFT JOIN '._DB_PREFIX_.'orders o ON (o.`id_order` = a.`id_order`)';
        $this->_group = ' GROUP BY a.`id_order_slip`';

        $this->fields_list = [
            'id_order_slip' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'id_order'      => [
                'title'        => $this->l('Order ID'),
                'align'        => 'left',
                'class'        => 'fixed-width-md',
                'havingFilter' => true,
            ],
            'date_add'      => [
                'title'      => $this->l('Date issued'),
                'type'       => 'date',
                'align'      => 'right',
                'filter_key' => 'a!date_add',
            ],
            'id_pdf'        => [
                'title'          => $this->l('PDF'),
                'align'          => 'center',
                'callback'       => 'printPDFIcons',
                'orderby'        => false,
                'search'         => false,
                'remove_onclick' => true,
            ],
        ];

        $this->_select = 'a.id_order_slip AS id_pdf';
        $this->optionTitle = $this->l('Slip');

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Credit slip options'),
                'fields' => [
                    'PS_CREDIT_SLIP_PREFIX' => [
                        'title' => $this->l('Credit slip prefix'),
                        'desc'  => $this->l('Prefix used for credit slips.'),
                        'size'  => 6,
                        'type'  => 'textLang',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        parent::__construct();

        $this->_where = Shop::addSqlRestriction(false, 'o');
    }

    /**
     * Post processing
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::getValue('submitAddorder_slip')) {
            if (!Validate::isDate(Tools::getValue('date_from'))) {
                $this->errors[] = $this->l('Invalid "From" date');
            }
            if (!Validate::isDate(Tools::getValue('date_to'))) {
                $this->errors[] = $this->l('Invalid "To" date');
            }
            if (!count($this->errors)) {
                $orderSlips = OrderSlip::getSlipsIdByDate(Tools::getValue('date_from'), Tools::getValue('date_to'));
                if (count($orderSlips)) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminPdf').'&submitAction=generateOrderSlipsPDF&date_from='.urlencode(Tools::getValue('date_from')).'&date_to='.urlencode(Tools::getValue('date_to')));
                }
                $this->errors[] = $this->l('No order slips were found for this period.');
            }
        } else {
            return parent::postProcess();
        }

        return false;
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
        $this->initTabModuleList();
        $this->initToolbar();
        $this->initPageHeaderToolbar();
        $this->content .= $this->renderList();
        $this->content .= $this->renderForm();
        $this->content .= $this->renderOptions();

        $this->context->smarty->assign(
            [
                'content'                   => $this->content,
                'url_post'                  => static::$currentIndex.'&token='.$this->token,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * Initialize toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initToolbar()
    {
        $this->toolbar_btn['save-date'] = [
            'href' => '#',
            'desc' => $this->l('Generate PDF file'),
        ];
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn['generate_pdf'] = [
            'href' => static::$currentIndex.'&token='.$this->token,
            'desc' => $this->l('Generate PDF', null, null, false),
            'icon' => 'process-icon-save-date',
        ];

        parent::initPageHeaderToolbar();
    }

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Print a PDF'),
                'icon'  => 'icon-print',
            ],
            'input'  => [
                [
                    'type'      => 'date',
                    'label'     => $this->l('From'),
                    'name'      => 'date_from',
                    'maxlength' => 10,
                    'required'  => true,
                    'hint'      => $this->l('Format: 2011-12-31 (inclusive).'),
                ],
                [
                    'type'      => 'date',
                    'label'     => $this->l('To'),
                    'name'      => 'date_to',
                    'maxlength' => 10,
                    'required'  => true,
                    'hint'      => $this->l('Format: 2012-12-31 (inclusive).'),
                ],
            ],
            'submit' => [
                'title' => $this->l('Generate PDF file'),
                'id'    => 'submitPrint',
                'icon'  => 'process-icon-download-alt',
            ],
        ];

        $this->fields_value = [
            'date_from' => date('Y-m-d'),
            'date_to'   => date('Y-m-d'),
        ];

        $this->show_toolbar = false;

        return parent::renderForm();
    }

    /**
     * Print PDF icons
     *
     * @param int   $idOrderSlip
     * @param array $tr
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function printPDFIcons($idOrderSlip, $tr)
    {
        $orderSlip = new OrderSlip((int) $idOrderSlip);
        if (!Validate::isLoadedObject($orderSlip)) {
            return '';
        }

        $this->context->smarty->assign([
            'order_slip' => $orderSlip,
            'tr'         => $tr,
        ]);

        return $this->createTemplate('_print_pdf_icon.tpl')->fetch();
    }
}
