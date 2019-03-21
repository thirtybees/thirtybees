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
 * Class AdminSpecificPriceRuleControllerCore
 *
 * @since 1.0.0
 */
class AdminSpecificPriceRuleControllerCore extends AdminController
{
    // @codingStandardsIgnoreStart
    /** @var array $list_reduction_type */
    public $list_reduction_type;
    // @codingStandardsIgnoreEnd

    /**
     * AdminSpecificPriceRuleControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'specific_price_rule';
        $this->className = 'SpecificPriceRule';
        $this->lang = false;
        $this->multishop_context = Shop::CONTEXT_ALL;

        /* if $_GET['id_shop'] is transmitted, virtual url can be loaded in config.php, so we wether transmit shop_id in herfs */
        if ($this->id_shop = (int) Tools::getValue('shop_id')) {
            $_GET['id_shop'] = $this->id_shop;
            $_POST['id_shop'] = $this->id_shop;
        }

        $this->list_reduction_type = [
            'percentage' => $this->l('Percentage'),
            'amount'     => $this->l('Amount'),
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->context = Context::getContext();

        $this->_select = 's.name shop_name, cu.name currency_name, cl.name country_name, gl.name group_name';
        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'shop s ON (s.id_shop = a.id_shop)
		LEFT JOIN '._DB_PREFIX_.'currency cu ON (cu.id_currency = a.id_currency)
		LEFT JOIN '._DB_PREFIX_.'country_lang cl ON (cl.id_country = a.id_country AND cl.id_lang='.(int) $this->context->language->id.')
		LEFT JOIN '._DB_PREFIX_.'group_lang gl ON (gl.id_group = a.id_group AND gl.id_lang='.(int) $this->context->language->id.')';
        $this->_use_found_rows = false;

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fields_list = [
            'id_specific_price_rule' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'                   => [
                'title'      => $this->l('Name'),
                'filter_key' => 'a!name',
                'width'      => 'auto',
            ],
            'shop_name'              => [
                'title'      => $this->l('Shop'),
                'filter_key' => 's!name',
            ],
            'currency_name'          => [
                'title'      => $this->l('Currency'),
                'align'      => 'center',
                'filter_key' => 'cu!name',
            ],
            'country_name'           => [
                'title'      => $this->l('Country'),
                'align'      => 'center',
                'filter_key' => 'cl!name',
            ],
            'group_name'             => [
                'title'      => $this->l('Group'),
                'align'      => 'center',
                'filter_key' => 'gl!name',
            ],
            'from_quantity'          => [
                'title' => $this->l('From quantity'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'reduction_type'         => [
                'title'      => $this->l('Reduction type'),
                'align'      => 'center',
                'type'       => 'select',
                'filter_key' => 'a!reduction_type',
                'list'       => $this->list_reduction_type,
            ],
            'reduction'              => [
                'title' => $this->l('Reduction'),
                'align' => 'center',
                'type'  => 'decimal',
                'class' => 'fixed-width-xs',
            ],
            'from'                   => [
                'title' => $this->l('Beginning'),
                'align' => 'right',
                'type'  => 'datetime',
            ],
            'to'                     => [
                'title' => $this->l('End'),
                'align' => 'right',
                'type'  => 'datetime',
            ],
        ];

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
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_specific_price_rule'] = [
                'href' => static::$currentIndex.'&addspecific_price_rule&token='.$this->token,
                'desc' => $this->l('Add new catalog price rule', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Get list
     *
     * @param int  $idLang
     * @param null $orderBy
     * @param null $orderWay
     * @param int  $start
     * @param null $limit
     * @param bool $idLangShop
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        foreach ($this->_list as $k => $list) {
            if ($list['reduction_type'] == 'amount') {
                $this->_list[$k]['reduction_type'] = $this->list_reduction_type['amount'];
            } elseif ($list['reduction_type'] == 'percentage') {
                $this->_list[$k]['reduction_type'] = $this->list_reduction_type['percentage'];
            }
        }
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
        if (!$this->object->id) {
            $this->object->price = -1;
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Catalog price rules'),
                'icon'  => 'icon-dollar',
            ],
            'input'  => [
                [
                    'type'      => 'text',
                    'label'     => $this->l('Name'),
                    'name'      => 'name',
                    'maxlength' => 32,
                    'required'  => true,
                    'hint'      => $this->l('Forbidden characters').' <>;=#{}',
                ],
                [
                    'type'          => 'select',
                    'label'         => $this->l('Shop'),
                    'name'          => 'shop_id',
                    'options'       => [
                        'query' => Shop::getShops(),
                        'id'    => 'id_shop',
                        'name'  => 'name',
                    ],
                    'condition'     => Shop::isFeatureActive(),
                    'default_value' => Shop::getContextShopID(),
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Currency'),
                    'name'    => 'id_currency',
                    'options' => [
                        'query' => array_merge([0 => ['id_currency' => 0, 'name' => $this->l('All currencies')]], Currency::getCurrencies(false, true, true)),
                        'id'    => 'id_currency',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Country'),
                    'name'    => 'id_country',
                    'options' => [
                        'query' => array_merge([0 => ['id_country' => 0, 'name' => $this->l('All countries')]], Country::getCountries((int) $this->context->language->id)),
                        'id'    => 'id_country',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Group'),
                    'name'    => 'id_group',
                    'options' => [
                        'query' => array_merge([0 => ['id_group' => 0, 'name' => $this->l('All groups')]], Group::getGroups((int) $this->context->language->id)),
                        'id'    => 'id_group',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('From quantity'),
                    'name'      => 'from_quantity',
                    'maxlength' => 10,
                    'required'  => true,
                ],
                [
                    'type'      => 'price',
                    'label'     => $this->l('Price'),
                    'name'      => 'price',
                    'disabled'  => $this->object->price == -1,
                ],
                [
                    'type'   => 'checkbox',
                    'name'   => 'leave_bprice',
                    'values' => [
                        'query' => [
                            [
                                'id'      => 'on',
                                'name'    => $this->l('Leave base price'),
                                'val'     => '1',
                                'checked' => '1',
                            ],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'  => 'datetime',
                    'label' => $this->l('From'),
                    'name'  => 'from',
                ],
                [
                    'type'  => 'datetime',
                    'label' => $this->l('To'),
                    'name'  => 'to',
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Reduction type'),
                    'name'    => 'reduction_type',
                    'options' => [
                        'query' => [['reduction_type' => 'amount', 'name' => $this->l('Amount')], ['reduction_type' => 'percentage', 'name' => $this->l('Percentage')]],
                        'id'    => 'reduction_type',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Reduction with or without taxes'),
                    'name'    => 'reduction_tax',
                    'align'   => 'center',
                    'options' => [
                        'query' => [
                            ['lab' => $this->l('Tax included'), 'val' => 1],
                            ['lab' => $this->l('Tax excluded'), 'val' => 0],
                        ],
                        'id'    => 'val',
                        'name'  => 'lab',
                    ],
                ],
                [
                    // Can be either an amount or a percentage. Treating both
                    // variants as price, except for field type, should work.
                    'type'        => 'text',
                    'label'       => $this->l('Reduction'),
                    'name'        => 'reduction',
                    'required'    => true,
                    'validation'  => 'isPrice',
                    'cast'        => 'priceval',
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];
        if (($value = $this->getFieldValue($this->object, 'price')) != -1) {
            $price = $value;
        } else {
            $price = '';
        }

        $this->fields_value = [
            'price'           => $price,
            'from_quantity'   => (($value = $this->getFieldValue($this->object, 'from_quantity')) ? $value : 1),
            'reduction'       => ($value = $this->getFieldValue($this->object, 'reduction')) ? $value : 0,
            'leave_bprice_on' => $price ? 0 : 1,
            'shop_id'         => (($value = $this->getFieldValue($this->object, 'id_shop')) ? $value : 1),
        ];

        $attributeGroups = [];
        $attributes = Attribute::getAttributes((int) $this->context->language->id);
        foreach ($attributes as $attribute) {
            if (!isset($attributeGroups[$attribute['id_attribute_group']])) {
                $attributeGroups[$attribute['id_attribute_group']] = [
                    'id_attribute_group' => $attribute['id_attribute_group'],
                    'name'               => $attribute['attribute_group'],
                ];
            }
            $attributeGroups[$attribute['id_attribute_group']]['attributes'][] = [
                'id_attribute' => $attribute['id_attribute'],
                'name'         => $attribute['name'],
            ];
        }
        $features = Feature::getFeatures((int) $this->context->language->id);
        foreach ($features as &$feature) {
            $feature['values'] = FeatureValue::getFeatureValuesWithLang((int) $this->context->language->id, $feature['id_feature'], true);
        }

        $this->tpl_form_vars = [
            'manufacturers'    => Manufacturer::getManufacturers(),
            'suppliers'        => Supplier::getSuppliers(),
            'attributes_group' => $attributeGroups,
            'features'         => $features,
            'categories'       => Category::getSimpleCategories((int) $this->context->language->id),
            'conditions'       => $this->object->getConditions(),
            'is_multishop'     => Shop::isFeatureActive(),
        ];

        return parent::renderForm();
    }

    /**
     * Process save
     *
     * @return false|SpecificPriceRule
     *
     * @since 1.0.0
     */
    public function processSave()
    {
        $_POST['price'] = Tools::getValue('leave_bprice_on') ?
            '-1' :
            priceval(Tools::getValue('price'));
        if (Validate::isLoadedObject(($object = parent::processSave()))) {
            /** @var SpecificPriceRule $object */
            $object->deleteConditions();
            foreach ($_POST as $key => $values) {
                if (preg_match('/^condition_group_([0-9]+)$/Ui', $key, $conditionGroup)) {
                    $conditions = [];
                    foreach ($values as $value) {
                        $condition = explode('_', $value);
                        $conditions[] = ['type' => $condition[0], 'value' => $condition[1]];
                    }
                    $object->addConditions($conditions);
                }
            }
            $object->apply();

            return $object;
        }

        return false;
    }

    /**
     * Post process
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        Tools::clearSmartyCache();

        return parent::postProcess();
    }
}
