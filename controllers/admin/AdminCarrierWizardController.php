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
 * Class AdminCarrierWizardControllerCore
 *
 * @since 1.0.0
 */
class AdminCarrierWizardControllerCore extends AdminController
{
    protected $wizard_access;

    /**
     * AdminCarrierWizardControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        $this->table = 'carrier';
        $this->identifier = 'id_carrier';
        $this->className = 'Carrier';
        $this->lang = false;
        $this->deleted = true;
        $this->step_number = 0;
        $this->type_context = Shop::getContext();
        $this->old_context = Context::getContext();
        $this->multishop_context = Shop::CONTEXT_ALL;

        $this->fieldImageSettings = [
            'name' => 'logo',
            'dir'  => 's',
        ];

        parent::__construct();

        try {
            $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminCarriers'));
        } catch (PrestaShopException $e) {
            $this->errors[] = $e->getMessage();
            $this->tabAccess = false;
        }
    }

    /**
     * @param $field
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function displayFieldName($field)
    {
        return $field;
    }

    /**
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryPlugin('smartWizard');
        $this->addJqueryPlugin('typewatch');
        $this->addJs(_PS_JS_DIR_.'admin/carrier_wizard.js');
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function renderView()
    {
        $this->initWizard();

        if (Tools::getValue('id_carrier') && $this->tabAccess['edit']) {
            $carrier = $this->loadObject();
        } elseif ($this->tabAccess['add']) {
            $carrier = new Carrier();
        }

        if ((!$this->tabAccess['edit'] && Tools::getValue('id_carrier')) || (!$this->tabAccess['add'] && !Tools::getValue('id_carrier'))) {
            $this->errors[] = Tools::displayError('You do not have permission to use this wizard.');

            return '';
        }

        try {
            $currency = $this->getActualCurrency();
        } catch (PrestaShopException $e) {
            $this->errors[] = $e->getMessage();
            return '';
        }

        $this->tpl_view_vars = [
            'currency_sign'     => $currency->sign,
            'currency_iso_code' => $currency->iso_code,
            'PS_WEIGHT_UNIT'    => Configuration::get('PS_WEIGHT_UNIT'),
            'enableAllSteps'    => Validate::isLoadedObject($carrier),
            'wizard_steps'      => $this->wizard_steps,
            'validate_url'      => $this->context->link->getAdminLink('AdminCarrierWizard'),
            'carrierlist_url'   => $this->context->link->getAdminLink('AdminCarriers').'&conf='.((int) Validate::isLoadedObject($carrier) ? 4 : 3),
            'multistore_enable' => Shop::isFeatureActive(),
            'wizard_contents'   => [
                'contents' => [
                    0 => $this->renderStepOne($carrier),
                    1 => $this->renderStepThree($carrier),
                    2 => $this->renderStepFour($carrier),
                    3 => $this->renderStepFive($carrier),
                ],
            ],
            'labels'            => ['next' => $this->l('Next'), 'previous' => $this->l('Previous'), 'finish' => $this->l('Finish')],
        ];

        if (Shop::isFeatureActive()) {
            array_splice($this->tpl_view_vars['wizard_contents']['contents'], 1, 0, [0 => $this->renderStepTwo($carrier)]);
        }

        $this->context->smarty->assign(
            [
                'carrier_logo' => (Validate::isLoadedObject($carrier) && file_exists(_PS_SHIP_IMG_DIR_.$carrier->id.'.jpg') ? _THEME_SHIP_DIR_.$carrier->id.'.jpg' : false),
            ]
        );

        try {
            $this->context->smarty->assign(
                [
                    'logo_content' => $this->createTemplate('logo.tpl')->fetch(),
                ]
            );
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return '';
        }

        $this->addjQueryPlugin(['ajaxfileupload']);

        return parent::renderView();
    }

    /**
     * @since 1.0.0
     */
    public function initWizard()
    {
        $this->wizard_steps = [
            'name'  => 'carrier_wizard',
            'steps' => [
                [
                    'title' => $this->l('General settings'),
                ],
                [
                    'title' => $this->l('Shipping locations and costs'),
                ],
                [
                    'title' => $this->l('Size, weight, and group access'),
                ],
                [
                    'title' => $this->l('Summary'),
                ],
            ],
        ];

        if (Shop::isFeatureActive()) {
            $multistoreStep = [
                [
                    'title' => $this->l('MultiStore'),
                ],
            ];
            array_splice($this->wizard_steps['steps'], 1, 0, $multistoreStep);
        }
    }

    /**
     * @return Currency
     *
     * @since 1.0.0
     */
    public function getActualCurrency()
    {
        try {
            if ($this->type_context == Shop::CONTEXT_SHOP) {
                Shop::setContext($this->type_context, $this->old_context->shop->id);
            } elseif ($this->type_context == Shop::CONTEXT_GROUP) {
                Shop::setContext($this->type_context, $this->old_context->shop->id_shop_group);
            }

            $currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));

            Shop::setContext(Shop::CONTEXT_ALL);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();

            return new Currency();
        }

        return $currency;
    }

    /**
     * @param Carrier $carrier
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderStepOne($carrier)
    {
        $this->fields_form = [
            'form' => [
                'id_form' => 'step_carrier_general',
                'input'   => [
                    [
                        'type'     => 'text',
                        'label'    => $this->l('Carrier name'),
                        'name'     => 'name',
                        'required' => true,
                        'hint'     => [
                            sprintf($this->l('Allowed characters: letters, spaces and "%s".'), '().-'),
                            $this->l('The carrier\'s name will be displayed during checkout.'),
                            $this->l('For in-store pickup, enter 0 to replace the carrier name with your shop name.'),
                        ],
                    ],
                    [
                        'type'      => 'text',
                        'label'     => $this->l('Transit time'),
                        'name'      => 'delay',
                        'lang'      => true,
                        'required'  => true,
                        'maxlength' => 128,
                        'hint'      => $this->l('The estimated delivery time will be displayed during checkout.'),
                    ],
                    [
                        'type'     => 'text',
                        'label'    => $this->l('Speed grade'),
                        'name'     => 'grade',
                        'required' => false,
                        'size'     => 1,
                        'hint'     => $this->l('Enter "0" for a longest shipping delay, or "9" for the shortest shipping delay.'),
                    ],
                    [
                        'type'  => 'logo',
                        'label' => $this->l('Logo'),
                        'name'  => 'logo',
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Tracking URL'),
                        'name'  => 'url',
                        'hint'  => $this->l('Delivery tracking URL: Type \'@\' where the tracking number should appear. It will be automatically replaced by the tracking number.'),
                        'desc'  => $this->l('For example: \'http://example.com/track.php?num=@\' with \'@\' where the tracking number should appear.'),
                    ],
                ],
            ],
        ];

        $tplVars = ['max_image_size' => (int) Configuration::get('PS_PRODUCT_PICTURE_MAX_SIZE') / 1024 / 1024];
        $fieldsValue = $this->getStepOneFieldsValues($carrier);

        return $this->renderGenericForm(['form' => $this->fields_form], $fieldsValue, $tplVars);
    }

    /**
     * @param $carrier
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getStepOneFieldsValues($carrier)
    {
        return [
            'id_carrier' => $this->getFieldValue($carrier, 'id_carrier'),
            'name'       => $this->getFieldValue($carrier, 'name'),
            'delay'      => $this->getFieldValue($carrier, 'delay'),
            'grade'      => $this->getFieldValue($carrier, 'grade'),
            'url'        => $this->getFieldValue($carrier, 'url'),
        ];
    }

    /**
     * @param       $fieldsForm
     * @param       $fieldsValue
     * @param array $tplVars
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderGenericForm($fieldsForm, $fieldsValue, $tplVars = [])
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = [];
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->tpl_vars = array_merge(
            [
                'fields_value' => $fieldsValue,
                'languages'    => $this->getLanguages(),
                'id_language'  => $this->context->language->id,
            ],
            $tplVars
        );
        $helper->override_folder = 'carrier_wizard/';

        return $helper->generateForm($fieldsForm);
    }

    /**
     * @param $carrier
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderStepThree($carrier)
    {
        $this->fields_form = [
            'form' => [
                'id_form' => 'step_carrier_ranges',
                'input'   => [
                    'shipping_handling'  => [
                        'type'     => 'switch',
                        'label'    => $this->l('Add handling costs'),
                        'name'     => 'shipping_handling',
                        'required' => false,
                        'class'    => 't',
                        'is_bool'  => true,
                        'values'   => [
                            [
                                'id'    => 'shipping_handling_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'shipping_handling_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                        'hint'     => $this->l('Include the handling costs (as set in Shipping > Preferences) in the final carrier price.'),
                    ],
                    'is_free'            => [
                        'type'     => 'switch',
                        'label'    => $this->l('Free shipping'),
                        'name'     => 'is_free',
                        'required' => false,
                        'class'    => 't',
                        'values'   => [
                            [
                                'id'    => 'is_free_on',
                                'value' => 1,
                                'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" />',
                            ],
                            [
                                'id'    => 'is_free_off',
                                'value' => 0,
                                'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" />',
                            ],
                        ],
                    ],
                    'shipping_method'    => [
                        'type'     => 'radio',
                        'label'    => $this->l('Billing'),
                        'name'     => 'shipping_method',
                        'required' => false,
                        'class'    => 't',
                        'br'       => true,
                        'values'   => [
                            [
                                'id'    => 'billing_price',
                                'value' => Carrier::SHIPPING_METHOD_PRICE,
                                'label' => $this->l('According to total price.'),
                            ],
                            [
                                'id'    => 'billing_weight',
                                'value' => Carrier::SHIPPING_METHOD_WEIGHT,
                                'label' => $this->l('According to total weight.'),
                            ],
                        ],
                    ],
                    'id_tax_rules_group' => [
                        'type'    => 'select',
                        'label'   => $this->l('Tax'),
                        'name'    => 'id_tax_rules_group',
                        'options' => [
                            'query'   => TaxRulesGroup::getTaxRulesGroups(true),
                            'id'      => 'id_tax_rules_group',
                            'name'    => 'name',
                            'default' => [
                                'label' => $this->l('No tax'),
                                'value' => 0,
                            ],
                        ],
                    ],
                    'range_behavior'     => [
                        'type'    => 'select',
                        'label'   => $this->l('Out-of-range behavior'),
                        'name'    => 'range_behavior',
                        'options' => [
                            'query' => [
                                [
                                    'id'   => 0,
                                    'name' => $this->l('Apply the cost of the highest defined range'),
                                ],
                                [
                                    'id'   => 1,
                                    'name' => $this->l('Disable carrier'),
                                ],
                            ],
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                        'hint'    => $this->l('Out-of-range behavior occurs when no defined range matches the customer\'s cart (e.g. when the weight of the cart is greater than the highest weight limit defined by the weight ranges).'),
                    ],
                    'zones'              => [
                        'type' => 'zone',
                        'name' => 'zones',
                    ],
                ],
            ],
        ];

        if (Configuration::get('PS_ATCP_SHIPWRAP')) {
            unset($this->fields_form['form']['input']['id_tax_rules_group']);
        }

        $tplVars = [];
        $tplVars['PS_WEIGHT_UNIT'] = Configuration::get('PS_WEIGHT_UNIT');

        $currency = $this->getActualCurrency();

        $tplVars['currency_sign'] = $currency->sign;
        $tplVars['currency_decimals'] = $currency->decimals;

        $fieldsValue = $this->getStepThreeFieldsValues($carrier);

        $this->getTplRangesVarsAndValues($carrier, $tplVars, $fieldsValue);

        return $this->renderGenericForm(['form' => $this->fields_form], $fieldsValue, $tplVars);
    }

    /**
     * @param $carrier
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getStepThreeFieldsValues($carrier)
    {
        $idTaxRulesGroup = (is_object($this->object) && !$this->object->id) ? Carrier::getIdTaxRulesGroupMostUsed() : $this->getFieldValue($carrier, 'id_tax_rules_group');

        $shippingHandling = (is_object($this->object) && !$this->object->id) ? 0 : $this->getFieldValue($carrier, 'shipping_handling');

        return [
            'is_free'            => $this->getFieldValue($carrier, 'is_free'),
            'id_tax_rules_group' => (int) $idTaxRulesGroup,
            'shipping_handling'  => $shippingHandling,
            'shipping_method'    => $this->getFieldValue($carrier, 'shipping_method'),
            'range_behavior'     => $this->getFieldValue($carrier, 'range_behavior'),
            'zones'              => $this->getFieldValue($carrier, 'zones'),
        ];
    }

    /**
     * @param Carrier $carrier
     * @param array   $tplVars
     * @param array   $fieldsValue
     *
     * @since 1.0.0
     */
    protected function getTplRangesVarsAndValues($carrier, &$tplVars, &$fieldsValue)
    {
        $tplVars['zones'] = Zone::getZones(false);
        $carrierZones = $carrier->getZones();
        $carrierZonesIds = [];
        if (is_array($carrierZones)) {
            foreach ($carrierZones as $carrierZone) {
                $carrierZonesIds[] = $carrierZone['id_zone'];
            }
        }

        $rangeTable = $carrier->getRangeTable();
        $shippingMethod = $carrier->getShippingMethod();

        $zones = Zone::getZones(false);
        foreach ($zones as $zone) {
            $fieldsValue['zones'][$zone['id_zone']] = Tools::getValue('zone_'.$zone['id_zone'], (in_array($zone['id_zone'], $carrierZonesIds)));
        }

        if ($shippingMethod == Carrier::SHIPPING_METHOD_FREE) {
            $rangeObj = $carrier->getRangeObject($carrier->shipping_method);
            $priceByRange = [];
        } else {
            $rangeObj = $carrier->getRangeObject();
            $priceByRange = Carrier::getDeliveryPriceByRanges($rangeTable, (int) $carrier->id);
        }

        foreach ($priceByRange as $price) {
            $tplVars['price_by_range'][$price['id_'.$rangeTable]][$price['id_zone']] = $price['price'];
        }

        $tmpRange = $rangeObj->getRanges((int) $carrier->id);
        $tplVars['ranges'] = [];
        if ($shippingMethod != Carrier::SHIPPING_METHOD_FREE) {
            foreach ($tmpRange as $id => $range) {
                $tplVars['ranges'][$range['id_'.$rangeTable]] = $range;
                $tplVars['ranges'][$range['id_'.$rangeTable]]['id_range'] = $range['id_'.$rangeTable];
            }
        }

        // init blank range
        if (!count($tplVars['ranges'])) {
            $tplVars['ranges'][] = ['id_range' => 0, 'delimiter1' => 0, 'delimiter2' => 0];
        }
    }

    /**
     * @param Carrier $carrier
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderStepFour($carrier)
    {
        $this->fields_form = [
            'form' => [
                'id_form' => 'step_carrier_conf',
                'input'   => [
                    [
                        'type'     => 'text',
                        'label'    => sprintf($this->l('Maximum package width (%s)'), Configuration::get('PS_DIMENSION_UNIT')),
                        'name'     => 'max_width',
                        'required' => false,
                        'hint'     => $this->l('Maximum width managed by this carrier. Set the value to "0", or leave this field blank to ignore.').' '.$this->l('The value must be an integer.'),
                    ],
                    [
                        'type'     => 'text',
                        'label'    => sprintf($this->l('Maximum package height (%s)'), Configuration::get('PS_DIMENSION_UNIT')),
                        'name'     => 'max_height',
                        'required' => false,
                        'hint'     => $this->l('Maximum height managed by this carrier. Set the value to "0", or leave this field blank to ignore.').' '.$this->l('The value must be an integer.'),
                    ],
                    [
                        'type'     => 'text',
                        'label'    => sprintf($this->l('Maximum package depth (%s)'), Configuration::get('PS_DIMENSION_UNIT')),
                        'name'     => 'max_depth',
                        'required' => false,
                        'hint'     => $this->l('Maximum depth managed by this carrier. Set the value to "0", or leave this field blank to ignore.').' '.$this->l('The value must be an integer.'),
                    ],
                    [
                        'type'     => 'text',
                        'label'    => sprintf($this->l('Maximum package weight (%s)'), Configuration::get('PS_WEIGHT_UNIT')),
                        'name'     => 'max_weight',
                        'required' => false,
                        'hint'     => $this->l('Maximum weight managed by this carrier. Set the value to "0", or leave this field blank to ignore.'),
                    ],
                    [
                        'type'   => 'group',
                        'label'  => $this->l('Group access'),
                        'name'   => 'groupBox',
                        'values' => Group::getGroups($this->context->language->id),
                        'hint'   => $this->l('Mark the groups that are allowed access to this carrier.'),
                    ],
                ],
            ],
        ];

        $fieldsValue = $this->getStepFourFieldsValues($carrier);

        // Added values of object Group
        $carrierGroups = $carrier->getGroups();
        $carrierGroupsIds = [];
        if (is_array($carrierGroups)) {
            foreach ($carrierGroups as $carrierGroup) {
                $carrierGroupsIds[] = $carrierGroup['id_group'];
            }
        }

        $groups = Group::getGroups($this->context->language->id);

        foreach ($groups as $group) {
            $fieldsValue['groupBox_'.$group['id_group']] = Tools::getValue('groupBox_'.$group['id_group'], (in_array($group['id_group'], $carrierGroupsIds) || empty($carrierGroupsIds) && !$carrier->id));
        }

        return $this->renderGenericForm(['form' => $this->fields_form], $fieldsValue);
    }

    /**
     * @param $carrier
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getStepFourFieldsValues($carrier)
    {
        return [
            'range_behavior' => $this->getFieldValue($carrier, 'range_behavior'),
            'max_height'     => $this->getFieldValue($carrier, 'max_height'),
            'max_width'      => $this->getFieldValue($carrier, 'max_width'),
            'max_depth'      => $this->getFieldValue($carrier, 'max_depth'),
            'max_weight'     => $this->getFieldValue($carrier, 'max_weight'),
            'group'          => $this->getFieldValue($carrier, 'group'),
        ];
    }

    /**
     * @param $carrier
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderStepFive($carrier)
    {
        $this->fields_form = [
            'form' => [
                'id_form' => 'step_carrier_summary',
                'input'   => [
                    [
                        'type'     => 'switch',
                        'label'    => $this->l('Enabled'),
                        'name'     => 'active',
                        'required' => false,
                        'class'    => 't',
                        'is_bool'  => true,
                        'values'   => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                            ],
                        ],
                        'hint'     => $this->l('Enable the carrier in the front office.'),
                    ],
                ],
            ],
        ];
        $template = $this->createTemplate('controllers/carrier_wizard/summary.tpl');
        $fieldsValue = $this->getStepFiveFieldsValues($carrier);
        $activeForm = $this->renderGenericForm(['form' => $this->fields_form], $fieldsValue);
        $activeForm = str_replace(['<fieldset id="fieldset_form">', '</fieldset>'], '', $activeForm);
        $template->assign('active_form', $activeForm);

        return $template->fetch('controllers/carrier_wizard/summary.tpl');
    }

    /**
     * @param $carrier
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getStepFiveFieldsValues($carrier)
    {
        return ['active' => $this->getFieldValue($carrier, 'active')];
    }

    /**
     * @param $carrier
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderStepTwo($carrier)
    {
        $this->fields_form = [
            'form' => [
                'id_form' => 'step_carrier_shops',
                'force'   => true,
                'input'   => [
                    [
                        'type'  => 'shop',
                        'label' => $this->l('Shop association'),
                        'name'  => 'checkBoxShopAsso',
                    ],
                ],
            ],
        ];
        $fieldsValue = $this->getStepTwoFieldsValues($carrier);

        return $this->renderGenericForm(['form' => $this->fields_form], $fieldsValue);
    }

    /**
     * @param $carrier
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getStepTwoFieldsValues($carrier)
    {
        return ['shop' => $this->getFieldValue($carrier, 'shop')];
    }

    /**
     * @param null $tabId
     * @param null $tabs
     *
     * @since 1.0.0
     */
    public function initBreadcrumbs($tabId = null, $tabs = null)
    {
        if (Tools::getValue('id_carrier')) {
            $this->display = 'edit';
        } else {
            $this->display = 'add';
        }

        parent::initBreadcrumbs((int) Tab::getIdFromClassName('AdminCarriers'));

        $this->display = 'view';
    }

    /**
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        $this->page_header_toolbar_btn['cancel'] = [
            'href' => $this->context->link->getAdminLink('AdminCarriers'),
            'desc' => $this->l('Cancel', null, null, false),
        ];
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessChangeRanges()
    {
        if ((Validate::isLoadedObject($this->object) && !$this->tabAccess['edit']) || !$this->tabAccess['add']) {
            $this->errors[] = Tools::displayError('You do not have permission to use this wizard.');

            return;
        }
        if ((!(int) $shippingMethod = Tools::getValue('shipping_method')) || !in_array($shippingMethod, [Carrier::SHIPPING_METHOD_PRICE, Carrier::SHIPPING_METHOD_WEIGHT])) {
            return;
        }

        $carrier = $this->loadObject(true);
        $carrier->shipping_method = $shippingMethod;

        $tplVars = [];
        $fieldsValue = $this->getStepThreeFieldsValues($carrier);
        $this->getTplRangesVarsAndValues($carrier, $tplVars, $fieldsValue);
        $template = $this->createTemplate('controllers/carrier_wizard/helpers/form/form_ranges.tpl');
        $template->assign($tplVars);
        $template->assign('change_ranges', 1);

        $template->assign('fields_value', $fieldsValue);
        $template->assign('input', ['type' => 'zone', 'name' => 'zones']);

        $currency = $this->getActualCurrency();

        $template->assign('currency_sign', $currency->sign);
        $template->assign('PS_WEIGHT_UNIT', Configuration::get('PS_WEIGHT_UNIT'));

        die($template->fetch());
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessValidateStep()
    {
        $this->validateForm(true);
    }

    /**
     * @param bool $die
     *
     * @since 1.0.0
     */
    protected function validateForm($die = true)
    {
        $stepNumber = (int) Tools::getValue('step_number');
        $return = ['has_error' => false];

        if (!$this->tabAccess['edit']) {
            $this->errors[] = Tools::displayError('You do not have permission to use this wizard.');
        } else {
            if (Shop::isFeatureActive() && $stepNumber == 2) {
                if (!Tools::getValue('checkBoxShopAsso_carrier')) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('You must choose at least one shop or group shop.');
                }
            } else {
                $this->validateRules();
            }
        }

        if (count($this->errors)) {
            $return['has_error'] = true;
            $return['errors'] = $this->errors;
        }
        if (count($this->errors) || $die) {
            $this->ajaxDie(json_encode($return));
        }
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessUploadLogo()
    {
        if (!$this->tabAccess['edit']) {
            die('<return result="error" message="'.Tools::displayError('You do not have permission to use this wizard.').'" />');
        }

        $logo = (isset($_FILES['carrier_logo_input']) ? $_FILES['carrier_logo_input'] : false);
        if ($logo && !empty($logo['tmp_name']) && $logo['tmp_name'] != 'none'
            && (!isset($logo['error']) || !$logo['error'])
            && preg_match('/\.(jpe?g|gif|png)$/', $logo['name'])
            && is_uploaded_file($logo['tmp_name'])
            && ImageManager::isRealImage($logo['tmp_name'], $logo['type'])
        ) {
            $file = $logo['tmp_name'];
            do {
                $tmpName = uniqid().'.jpg';
            } while (file_exists(_PS_TMP_IMG_DIR_.$tmpName));
            if (!ImageManager::resize($file, _PS_TMP_IMG_DIR_.$tmpName)) {
                die('<return result="error" message="Impossible to resize the image into '.Tools::safeOutput(_PS_TMP_IMG_DIR_).'" />');
            }
            @unlink($file);
            die('<return result="success" message="'.Tools::safeOutput(_PS_TMP_IMG_.$tmpName).'" />');
        } else {
            die('<return result="error" message="Cannot upload file" />');
        }
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessFinishStep()
    {
        $return = ['has_error' => false];
        if (!$this->tabAccess['edit']) {
            $return = [
                'has_error' => true,
                $return['errors'][] = Tools::displayError('You do not have permission to use this wizard.'),
            ];
        } else {
            $this->validateForm(false);
            if ($idCarrier = Tools::getValue('id_carrier')) {
                $currentCarrier = new Carrier((int) $idCarrier);

                // if update we duplicate current Carrier
                /** @var Carrier $newCarrier */
                $newCarrier = $currentCarrier->duplicateObject();

                if (Validate::isLoadedObject($newCarrier)) {
                    // Set flag deteled to true for historization
                    $currentCarrier->deleted = true;
                    $currentCarrier->update();

                    // Fill the new carrier object
                    $this->copyFromPost($newCarrier, $this->table);
                    $newCarrier->position = $currentCarrier->position;
                    $newCarrier->update();

                    $this->updateAssoShop((int) $newCarrier->id);
                    $this->duplicateLogo((int) $newCarrier->id, (int) $currentCarrier->id);
                    $this->changeGroups((int) $newCarrier->id);

                    //Copy default carrier
                    if (Configuration::get('PS_CARRIER_DEFAULT') == $currentCarrier->id) {
                        Configuration::updateValue('PS_CARRIER_DEFAULT', (int) $newCarrier->id);
                    }

                    // Call of hooks
                    Hook::exec(
                        'actionCarrierUpdate', [
                            'id_carrier' => (int) $currentCarrier->id,
                            'carrier'    => $newCarrier,
                        ]
                    );
                    $this->postImage($newCarrier->id);
                    $this->changeZones($newCarrier->id);
                    $newCarrier->setTaxRulesGroup((int) Tools::getValue('id_tax_rules_group'));
                    $carrier = $newCarrier;
                }
            } else {
                $carrier = new Carrier();
                $this->copyFromPost($carrier, $this->table);
                if (!$carrier->add()) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving this carrier.');
                }
            }

            if ($carrier->is_free) {
                //if carrier is free delete shipping cost
                $carrier->deleteDeliveryPrice('range_weight');
                $carrier->deleteDeliveryPrice('range_price');
            }

            if (Validate::isLoadedObject($carrier)) {
                if (!$this->changeGroups((int) $carrier->id)) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving carrier groups.');
                }

                if (!$this->changeZones((int) $carrier->id)) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving carrier zones.');
                }

                if (!$carrier->is_free) {
                    if (!$this->processRanges((int) $carrier->id)) {
                        $return['has_error'] = true;
                        $return['errors'][] = $this->l('An error occurred while saving carrier ranges.');
                    }
                }

                if (Shop::isFeatureActive() && !$this->updateAssoShop((int) $carrier->id)) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving associations of shops.');
                }

                if (!$carrier->setTaxRulesGroup((int) Tools::getValue('id_tax_rules_group'))) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving the tax rules group.');
                }

                if (Tools::getValue('logo')) {
                    if (Tools::getValue('logo') == 'null' && file_exists(_PS_SHIP_IMG_DIR_.$carrier->id.'.jpg')) {
                        unlink(_PS_SHIP_IMG_DIR_.$carrier->id.'.jpg');
                    } else {
                        $logo = basename(Tools::getValue('logo'));
                        if (!file_exists(_PS_TMP_IMG_DIR_.$logo) || !@copy(_PS_TMP_IMG_DIR_.$logo, _PS_SHIP_IMG_DIR_.$carrier->id.'.jpg')) {
                            $return['has_error'] = true;
                            $return['errors'][] = $this->l('An error occurred while saving carrier logo.');
                        }
                    }
                }
                $return['id_carrier'] = $carrier->id;
            }
        }
        $this->ajaxDie(json_encode($return));
    }

    /**
     * @param int $newId
     * @param int $oldId
     *
     * @since 1.0.0
     */
    public function duplicateLogo($newId, $oldId)
    {
        $oldLogo = _PS_SHIP_IMG_DIR_.'/'.(int) $oldId.'.jpg';
        if (file_exists($oldLogo)) {
            @copy($oldLogo, _PS_SHIP_IMG_DIR_.'/'.(int) $newId.'.jpg');
        }

        $oldTmpLogo = _PS_TMP_IMG_DIR_.'/carrier_mini_'.(int) $oldId.'.jpg';
        if (file_exists($oldTmpLogo)) {
            if (!isset($_FILES['logo'])) {
                @copy($oldTmpLogo, _PS_TMP_IMG_DIR_.'/carrier_mini_'.$newId.'.jpg');
            }
            unlink($oldTmpLogo);
        }
    }

    /**
     * @param      $idCarrier
     * @param bool $delete
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function changeGroups($idCarrier, $delete = true)
    {
        $carrier = new Carrier((int) $idCarrier);
        if (!Validate::isLoadedObject($carrier)) {
            return false;
        }

        return $carrier->setGroups(Tools::getValue('groupBox'));
    }

    /**
     * @param int $id
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function changeZones($id)
    {
        $return = true;
        $carrier = new Carrier($id);
        if (!Validate::isLoadedObject($carrier)) {
            die(Tools::displayError('The object cannot be loaded.'));
        }
        $zones = Zone::getZones(false);
        foreach ($zones as $zone) {
            if (count($carrier->getZone($zone['id_zone']))) {
                if (!isset($_POST['zone_'.$zone['id_zone']]) || !$_POST['zone_'.$zone['id_zone']]) {
                    $return &= $carrier->deleteZone((int) $zone['id_zone']);
                }
            } elseif (isset($_POST['zone_'.$zone['id_zone']]) && $_POST['zone_'.$zone['id_zone']]) {
                $return &= $carrier->addZone((int) $zone['id_zone']);
            }
        }

        return $return;
    }

    /**
     * @param int $idCarrier
     *
     * @return bool|null
     *
     * @since 1.0.0
     */
    public function processRanges($idCarrier)
    {
        if (!$this->tabAccess['edit'] || !$this->tabAccess['add']) {
            $this->errors[] = Tools::displayError('You do not have permission to use this wizard.');

            return null;
        }

        $carrier = new Carrier((int) $idCarrier);
        if (!Validate::isLoadedObject($carrier)) {
            return false;
        }

        $rangeInf = Tools::getValue('range_inf');
        $rangeSup = Tools::getValue('range_sup');
        $rangeType = Tools::getValue('shipping_method');

        $fees = Tools::getValue('fees');

        $carrier->deleteDeliveryPrice($carrier->getRangeTable());
        if ($rangeType != Carrier::SHIPPING_METHOD_FREE) {
            foreach ($rangeInf as $key => $delimiter1) {
                if (!isset($rangeSup[$key])) {
                    continue;
                }

                $range = $carrier->getRangeObject((int) $rangeType);
                $range->id_carrier = (int) $carrier->id;
                $range->delimiter1 = (float) $delimiter1;
                $range->delimiter2 = (float) $rangeSup[$key];
                $range->save();

                if (!Validate::isLoadedObject($range)) {
                    return false;
                }
                $priceList = [];
                if (is_array($fees) && count($fees)) {
                    foreach ($fees as $idZone => $fee) {
                        $priceList[] = [
                            'id_range_price'  => ($rangeType == Carrier::SHIPPING_METHOD_PRICE ? (int) $range->id : null),
                            'id_range_weight' => ($rangeType == Carrier::SHIPPING_METHOD_WEIGHT ? (int) $range->id : null),
                            'id_carrier'      => (int) $carrier->id,
                            'id_zone'         => (int) $idZone,
                            'price'           => isset($fee[$key]) ? priceval($fee[$key]) : 0,
                        ];
                    }
                }

                if (count($priceList) && !$carrier->addDeliveryPrice($priceList, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array|void
     *
     * @since 1.0.0
     */
    public function getValidationRules()
    {
        $stepNumber = (int) Tools::getValue('step_number');
        if (!$stepNumber) {
            return;
        }

        if ($stepNumber == 4 && !Shop::isFeatureActive() || $stepNumber == 5 && Shop::isFeatureActive()) {
            return ['fields' => []];
        }

        $stepFields = [
            1 => ['name', 'delay', 'grade', 'url'],
            2 => ['is_free', 'id_tax_rules_group', 'shipping_handling', 'shipping_method', 'range_behavior'],
            3 => ['range_behavior', 'max_height', 'max_width', 'max_depth', 'max_weight'],
            4 => [],
        ];
        if (Shop::isFeatureActive()) {
            $tmp = $stepFields;
            $stepFields = array_slice($tmp, 0, 1, true) + [2 => ['shop']];
            $stepFields[3] = $tmp[2];
            $stepFields[4] = $tmp[3];
        }

        $definition = ObjectModel::getDefinition('Carrier');
        foreach ($definition['fields'] as $field => $def) {
            if (is_array($stepFields[$stepNumber]) && !in_array($field, $stepFields[$stepNumber])) {
                unset($definition['fields'][$field]);
            }
        }

        return $definition;
    }
}
