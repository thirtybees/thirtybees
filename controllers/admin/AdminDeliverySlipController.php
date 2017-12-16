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
 * Class AdminDeliverySlipControllerCore
 *
 * @since 1.0.0
 */
class AdminDeliverySlipControllerCore extends AdminController
{
    /**
     * AdminDeliverySlipControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'delivery';

        $this->context = Context::getContext();

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Delivery slip options'),
                'fields' => [
                    'PS_DELIVERY_PREFIX'  => [
                        'title' => $this->l('Delivery prefix'),
                        'desc'  => $this->l('Prefix used for delivery slips.'),
                        'type'  => 'textLang',
                    ],
                    'PS_DELIVERY_NUMBER'  => [
                        'title' => $this->l('Delivery number'),
                        'desc'  => $this->l('The next delivery slip will begin with this number and then increase with each additional slip.'),
                        'cast'  => 'intval',
                        'type'  => 'text',
                    ],
                    'PS_PDF_IMG_DELIVERY' => [
                        'title'      => $this->l('Enable product image'),
                        'hint'       => $this->l('Adds an image before product name on Delivery-slip'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        parent::__construct();
    }

    /**
     * Post processing
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitAdddelivery')) {
            if (!Validate::isDate(Tools::getValue('date_from'))) {
                $this->errors[] = Tools::displayError('Invalid \'from\' date');
            }
            if (!Validate::isDate(Tools::getValue('date_to'))) {
                $this->errors[] = Tools::displayError('Invalid \'to\' date');
            }
            if (!count($this->errors)) {
                if (count(OrderInvoice::getByDeliveryDateInterval(Tools::getValue('date_from'), Tools::getValue('date_to')))) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminPdf').'&submitAction=generateDeliverySlipsPDF&date_from='.urlencode(Tools::getValue('date_from')).'&date_to='.urlencode(Tools::getValue('date_to')));
                } else {
                    $this->errors[] = Tools::displayError('No delivery slip was found for this period.');
                }
            }
        } else {
            parent::postProcess();
        }
    }

    /**
     * Initialize content
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        $this->initTabModuleList();
        $this->initPageHeaderToolbar();
        $this->show_toolbar = false;
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
                'title' => $this->l('Print PDF delivery slips'),
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
                'icon'  => 'process-icon-download-alt',
            ],
        ];

        $this->fields_value = [
            'date_from' => date('Y-m-d'),
            'date_to'   => date('Y-m-d'),
        ];

        return parent::renderForm();
    }
}
