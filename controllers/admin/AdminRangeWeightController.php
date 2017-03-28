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
 * Class AdminRangeWeightControllerCore
 *
 * @since 1.0.0
 */
class AdminRangeWeightControllerCore extends AdminController
{
    /**
     * AdminRangeWeightControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'range_weight';
        $this->className = 'RangeWeight';
        $this->lang = false;

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = ['delete' => ['text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?')]];

        $this->fields_list = [
            'id_range_weight' => ['title' => $this->l('ID'), 'align' => 'center', 'width' => 25],
            'carrier_name'    => ['title' => $this->l('Carrier'), 'align' => 'left', 'width' => 'auto', 'filter_key' => 'ca!name'],
            'delimiter1'      => ['title' => $this->l('From'), 'width' => 86, 'type' => 'float', 'suffix' => Configuration::get('PS_WEIGHT_UNIT'), 'align' => 'right'],
            'delimiter2'      => ['title' => $this->l('To'), 'width' => 86, 'type' => 'float', 'suffix' => Configuration::get('PS_WEIGHT_UNIT'), 'align' => 'right'],
        ];

        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'carrier ca ON (ca.`id_carrier` = a.`id_carrier`)';
        $this->_select = 'ca.`name` AS carrier_name';
        $this->_where = 'AND ca.`deleted` = 0';
        $this->_use_found_rows = false;

        parent::__construct();
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
        $this->page_header_toolbar_title = $this->l('Weight ranges');
        $this->page_header_toolbar_btn['new_weight_range'] = [
            'href' => static::$currentIndex.'&addrange_weight&token='.$this->token,
            'desc' => $this->l('Add new weight range', null, null, false),
            'icon' => 'process-icon-new',
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
        $carriers = Carrier::getCarriers($this->context->language->id, true, false, false, null, Carrier::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
        foreach ($carriers as $key => $carrier) {
            if ($carrier['is_free']) {
                unset($carriers[$key]);
            }
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Weight ranges'),
                'icon'  => 'icon-suitcase',
            ],
            'input'  => [
                [
                    'type'          => 'select',
                    'label'         => $this->l('Carrier'),
                    'name'          => 'id_carrier',
                    'required'      => false,
                    'hint'          => $this->l('You can apply this range to a different carrier by selecting its name.'),
                    'options'       => [
                        'query' => $carriers,
                        'id'    => 'id_carrier',
                        'name'  => 'name',
                    ],
                    'empty_message' => '<p class="alert alert-block">'.$this->l('There is no carrier available for this weight range.').'</p>',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('From'),
                    'name'     => 'delimiter1',
                    'required' => true,
                    'suffix'   => Configuration::get('PS_WEIGHT_UNIT'),
                    'hint'     => $this->l('Start range (included).'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('To'),
                    'name'     => 'delimiter2',
                    'required' => true,
                    'suffix'   => Configuration::get('PS_WEIGHT_UNIT'),
                    'hint'     => $this->l('End range (excluded).'),
                ],
            ],
            'submit' => [
                'title' => $this->l('   Save   '),
                'class' => 'btn btn-default',
            ],
        ];

        return parent::renderForm();
    }

    /**
     * Get list
     *
     * @param int         $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int         $start
     * @param int|null    $limit
     * @param int|bool    $idLangShop
     *
     * @since 1.0.0
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);
        if ($this->_list && is_array($this->_list)) {
            foreach ($this->_list as $key => $list) {
                if ($list['carrier_name'] == '0') {
                    $this->_list[$key]['carrier_name'] = Carrier::getCarrierNameFromShopName();
                }
            }
        }
    }

    /**
     * Post processing
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        $id = (int) Tools::getValue('id_'.$this->table);

        if (Tools::getValue('submitAdd'.$this->table)) {
            if (Tools::getValue('delimiter1') >= Tools::getValue('delimiter2')) {
                $this->errors[] = Tools::displayError('Invalid range');
            } elseif (!$id && RangeWeight::rangeExist((int) Tools::getValue('id_carrier'), (float) Tools::getValue('delimiter1'), (float) Tools::getValue('delimiter2'))) {
                $this->errors[] = Tools::displayError('The range already exists');
            } elseif (RangeWeight::isOverlapping((int) Tools::getValue('id_carrier'), (float) Tools::getValue('delimiter1'), (float) Tools::getValue('delimiter2'), ($id ? (int) $id : null))) {
                $this->errors[] = Tools::displayError('Error: Ranges are overlapping');
            } elseif (!count($this->errors)) {
                parent::postProcess();
            }
        } else {
            parent::postProcess();
        }
    }
}
