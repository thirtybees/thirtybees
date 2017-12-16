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
 * Class AdminCarriersControllerCore
 *
 * @since 1.0.0
 */
class AdminCarriersControllerCore extends AdminController
{
    protected $position_identifier = 'id_carrier';

    /**
     * AdminCarriersControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        if ($idCarrier = Tools::getValue('id_carrier') && !Tools::isSubmit('deletecarrier') && !Tools::isSubmit('statuscarrier') && !Tools::isSubmit('isFreecarrier') && !Tools::isSubmit('onboarding_carrier')) {
            Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminCarrierWizard').'&id_carrier='.(int) $idCarrier);
        }

        $this->bootstrap = true;
        $this->table = 'carrier';
        $this->className = 'Carrier';
        $this->lang = false;
        $this->deleted = true;

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_defaultOrderBy = 'position';

        $this->context = Context::getContext();

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fieldImageSettings = [
            'name' => 'logo',
            'dir'  => 's',
        ];

        $this->fields_list = [
            'id_carrier' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'       => [
                'title' => $this->l('Name'),
            ],
            'image'      => [
                'title'   => $this->l('Logo'),
                'align'   => 'center',
                'image'   => 's',
                'class'   => 'fixed-width-xs',
                'orderby' => false,
                'search'  => false,
            ],
            'delay'      => [
                'title'   => $this->l('Delay'),
                'orderby' => false,
            ],
            'active'     => [
                'title'   => $this->l('Status'),
                'align'   => 'center',
                'active'  => 'status',
                'type'    => 'bool',
                'class'   => 'fixed-width-sm',
                'orderby' => false,
            ],
            'is_free'    => [
                'title'   => $this->l('Free Shipping'),
                'align'   => 'center',
                'active'  => 'isFree',
                'type'    => 'bool',
                'class'   => 'fixed-width-sm',
                'orderby' => false,
            ],
            'position'   => [
                'title'      => $this->l('Position'),
                'filter_key' => 'a!position',
                'align'      => 'center',
                'class'      => 'fixed-width-sm',
                'position'   => 'position',
            ],
        ];
        parent::__construct();

        if (Tools::isSubmit('onboarding_carrier')) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminCarrierWizard'));
        }
    }

    /**
     * @since 1.0.0
     */
    public function initToolbar()
    {
        parent::initToolbar();

        if (isset($this->toolbar_btn['new']) && $this->display != 'view') {
            $this->toolbar_btn['new']['href'] = $this->context->link->getAdminLink('AdminCarriers').'&onboarding_carrier';
        }
    }

    /**
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_title = $this->l('Carriers');
        if ($this->display != 'view') {
            $this->page_header_toolbar_btn['new_carrier'] = [
                'href' => $this->context->link->getAdminLink('AdminCarrierWizard'),
                'desc' => $this->l('Add new carrier', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @return false|string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        $this->_select = 'b.*';
        $this->_join = 'INNER JOIN `'._DB_PREFIX_.'carrier_lang` b ON a.id_carrier = b.id_carrier'.Shop::addSqlRestrictionOnLang('b').' AND b.id_lang = '.$this->context->language->id.' LEFT JOIN `'._DB_PREFIX_.'carrier_tax_rules_group_shop` ctrgs ON (a.`id_carrier` = ctrgs.`id_carrier` AND ctrgs.id_shop='.(int) $this->context->shop->id.')';
        $this->_use_found_rows = false;

        return parent::renderList();
    }

    /**
     * @return string|void
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Carriers'),
                'icon'  => 'icon-truck',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Company'),
                    'name'     => 'name',
                    'required' => true,
                    'hint'     => [
                        sprintf($this->l('Allowed characters: letters, spaces and %s'), '().-'),
                        $this->l('Carrier name displayed during checkout'),
                        $this->l('For in-store pickup, enter 0 to replace the carrier name with your shop name.'),
                    ],
                ],
                [
                    'type'  => 'file',
                    'label' => $this->l('Logo'),
                    'name'  => 'logo',
                    'hint'  => $this->l('Upload a logo from your computer.').' (.gif, .jpg, .jpeg '.$this->l('or').' .png)',
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Transit time'),
                    'name'      => 'delay',
                    'lang'      => true,
                    'required'  => true,
                    'maxlength' => 128,
                    'hint'      => $this->l('Estimated delivery time will be displayed during checkout.'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Speed grade'),
                    'name'     => 'grade',
                    'required' => false,
                    'hint'     => $this->l('Enter "0" for a longest shipping delay, or "9" for the shortest shipping delay.'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('URL'),
                    'name'  => 'url',
                    'hint'  => $this->l('Delivery tracking URL: Type \'@\' where the tracking number should appear. It will then be automatically replaced by the tracking number.'),
                ],
                [
                    'type'   => 'checkbox',
                    'label'  => $this->l('Zone'),
                    'name'   => 'zone',
                    'values' => [
                        'query' => Zone::getZones(false),
                        'id'    => 'id_zone',
                        'name'  => 'name',
                    ],
                    'hint'   => $this->l('The zones in which this carrier will be used.'),
                ],
                [
                    'type'   => 'group',
                    'label'  => $this->l('Group access'),
                    'name'   => 'groupBox',
                    'values' => Group::getGroups($this->context->language->id),
                    'hint'   => $this->l('Mark the groups that are allowed access to this carrier.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Status'),
                    'name'     => 'active',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
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
                    'hint'     => $this->l('Enable the carrier in the front office.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Apply shipping cost'),
                    'name'     => 'is_free',
                    'required' => false,
                    'class'    => 't',
                    'values'   => [
                        [
                            'id'    => 'is_free_on',
                            'value' => 0,
                            'label' => '<img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" />',
                        ],
                        [
                            'id'    => 'is_free_off',
                            'value' => 1,
                            'label' => '<img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" />',
                        ],
                    ],
                    'hint'     => $this->l('Apply both regular shipping cost and product-specific shipping costs.'),
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Tax'),
                    'name'    => 'id_tax_rules_group',
                    'options' => [
                        'query'   => TaxRulesGroup::getTaxRulesGroups(true),
                        'id'      => 'id_tax_rules_group',
                        'name'    => 'name',
                        'default' => [
                            'label' => $this->l('No Tax'),
                            'value' => 0,
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Shipping and handling'),
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
                    'hint'     => $this->l('Include the shipping and handling costs in the carrier price.'),
                ],
                [
                    'type'     => 'radio',
                    'label'    => $this->l('Billing'),
                    'name'     => 'shipping_method',
                    'required' => false,
                    'class'    => 't',
                    'br'       => true,
                    'values'   => [
                        [
                            'id'    => 'billing_default',
                            'value' => Carrier::SHIPPING_METHOD_DEFAULT,
                            'label' => $this->l('Default behavior'),
                        ],
                        [
                            'id'    => 'billing_price',
                            'value' => Carrier::SHIPPING_METHOD_PRICE,
                            'label' => $this->l('According to total price'),
                        ],
                        [
                            'id'    => 'billing_weight',
                            'value' => Carrier::SHIPPING_METHOD_WEIGHT,
                            'label' => $this->l('According to total weight'),
                        ],
                    ],
                ],
                [
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
                    'hint'    => $this->l('Out-of-range behavior occurs when none is defined (e.g. when a customer\'s cart weight is greater than the highest range limit).'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Maximum package height'),
                    'name'     => 'max_height',
                    'required' => false,
                    'hint'     => $this->l('Maximum height managed by this carrier. Set the value to "0," or leave this field blank to ignore.'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Maximum package width'),
                    'name'     => 'max_width',
                    'required' => false,
                    'hint'     => $this->l('Maximum width managed by this carrier. Set the value to "0," or leave this field blank to ignore.'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Maximum package depth'),
                    'name'     => 'max_depth',
                    'required' => false,
                    'hint'     => $this->l('Maximum depth managed by this carrier. Set the value to "0," or leave this field blank to ignore.'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Maximum package weight'),
                    'name'     => 'max_weight',
                    'required' => false,
                    'hint'     => $this->l('Maximum weight managed by this carrier. Set the value to "0," or leave this field blank to ignore.'),
                ],
                [
                    'type' => 'hidden',
                    'name' => 'is_module',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'external_module_name',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'shipping_external',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'need_range',
                ],
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        if (!($obj = $this->loadObject(true))) {
            return;
        }

        $this->getFieldsValues($obj);

        return parent::renderForm();
    }

    /**
     * Overload the property $fields_value
     *
     * @param ObjectModel $obj
     *
     * @since 1.0.0
     */
    public function getFieldsValues($obj)
    {
        if ($this->getFieldValue($obj, 'is_module')) {
            $this->fields_value['is_module'] = 1;
        }

        if ($this->getFieldValue($obj, 'shipping_external')) {
            $this->fields_value['shipping_external'] = 1;
        }

        if ($this->getFieldValue($obj, 'need_range')) {
            $this->fields_value['need_range'] = 1;
        }
        // Added values of object Zone
        $carrierZones = $obj->getZones();
        $carrierZonesIds = [];
        if (is_array($carrierZones)) {
            foreach ($carrierZones as $carrierZone) {
                $carrierZonesIds[] = $carrierZone['id_zone'];
            }
        }

        $zones = Zone::getZones(false);
        foreach ($zones as $zone) {
            $this->fields_value['zone_'.$zone['id_zone']] = Tools::getValue('zone_'.$zone['id_zone'], (in_array($zone['id_zone'], $carrierZonesIds)));
        }

        // Added values of object Group
        $carrierGroups = $obj->getGroups();
        $carrierGroupsIds = [];
        if (is_array($carrierGroups)) {
            foreach ($carrierGroups as $carrierGroup) {
                $carrierGroupsIds[] = $carrierGroup['id_group'];
            }
        }

        $groups = Group::getGroups($this->context->language->id);

        foreach ($groups as $group) {
            $this->fields_value['groupBox_'.$group['id_group']] = Tools::getValue('groupBox_'.$group['id_group'], (in_array($group['id_group'], $carrierGroupsIds) || empty($carrierGroupsIds) && !$obj->id));
        }

        $this->fields_value['id_tax_rules_group'] = $this->object->getIdTaxRulesGroup($this->context);
    }

    /**
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::getValue('action') == 'GetModuleQuickView' && Tools::getValue('ajax') == '1') {
            $this->ajaxProcessGetModuleQuickView();
        }

        if (Tools::getValue('submitAdd'.$this->table)) {
            /* Checking fields validity */
            $this->validateRules();
            if (!count($this->errors)) {
                $id = (int) Tools::getValue('id_'.$this->table);

                /* Object update */
                if (isset($id) && !empty($id)) {
                    try {
                        if ($this->tabAccess['edit'] === '1') {
                            $currentCarrier = new Carrier($id);
                            if (!Validate::isLoadedObject($currentCarrier)) {
                                throw new PrestaShopException('Cannot load Carrier object');
                            }

                            /** @var Carrier $newCarrier */
                            // Duplicate current Carrier
                            $newCarrier = $currentCarrier->duplicateObject();
                            if (Validate::isLoadedObject($newCarrier)) {
                                // Set flag deteled to true for historization
                                $currentCarrier->deleted = true;
                                $currentCarrier->update();

                                // Fill the new carrier object
                                $this->copyFromPost($newCarrier, $this->table);
                                $newCarrier->position = $currentCarrier->position;
                                $newCarrier->update();

                                $this->updateAssoShop($newCarrier->id);
                                $newCarrier->copyCarrierData((int) $currentCarrier->id);
                                $this->changeGroups($newCarrier->id);
                                // Call of hooks
                                Hook::exec(
                                    'actionCarrierUpdate',
                                    [
                                        'id_carrier' => (int) $currentCarrier->id,
                                        'carrier'    => $newCarrier,
                                    ]
                                );
                                $this->postImage($newCarrier->id);
                                $this->changeZones($newCarrier->id);
                                $newCarrier->setTaxRulesGroup((int) Tools::getValue('id_tax_rules_group'));
                                Tools::redirectAdmin(static::$currentIndex.'&id_'.$this->table.'='.$currentCarrier->id.'&conf=4&token='.$this->token);
                            } else {
                                $this->errors[] = Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.'</b>';
                            }
                        } else {
                            $this->errors[] = Tools::displayError('You do not have permission to edit this.');
                        }
                    } catch (PrestaShopException $e) {
                        $this->errors[] = $e->getMessage();
                    }
                } /* Object creation */
                else {
                    if ($this->tabAccess['add'] === '1') {
                        // Create new Carrier
                        $carrier = new Carrier();
                        $this->copyFromPost($carrier, $this->table);
                        $carrier->position = Carrier::getHigherPosition() + 1;
                        if ($carrier->add()) {
                            if (($_POST['id_'.$this->table] = $carrier->id /* voluntary */) && $this->postImage($carrier->id) && $this->_redirect) {
                                $carrier->setTaxRulesGroup((int) Tools::getValue('id_tax_rules_group'), true);
                                $this->changeZones($carrier->id);
                                $this->changeGroups($carrier->id);
                                $this->updateAssoShop($carrier->id);
                                Tools::redirectAdmin(static::$currentIndex.'&id_'.$this->table.'='.$carrier->id.'&conf=3&token='.$this->token);
                            }
                        } else {
                            $this->errors[] = Tools::displayError('An error occurred while creating an object.').' <b>'.$this->table.'</b>';
                        }
                    } else {
                        $this->errors[] = Tools::displayError('You do not have permission to add this.');
                    }
                }
            }
            parent::postProcess();
        } elseif (isset($_GET['isFree'.$this->table])) {
            $this->processIsFree();
        } else {
            if (Tools::isSubmit('delete'.$this->table)) {
                $id = (int) Tools::getValue('id_'.$this->table);
                // Delete from the reference_id and not from the carrier id
                $carrier = new Carrier((int) $id);
                Warehouse::removeCarrier($carrier->id_reference);
            } elseif (Tools::isSubmit($this->table.'Box') && count(Tools::getValue($this->table.'Box')) > 0) {
                $ids = Tools::getValue($this->table.'Box');
                array_walk($ids, 'intval');
                foreach ($ids as $id) {
                    // Delete from the reference_id and not from the carrier id
                    $carrier = new Carrier((int) $id);
                    Warehouse::removeCarrier($carrier->id_reference);
                }
            }
            parent::postProcess();
            Carrier::cleanPositions();
        }
    }

    /**
     * @param int  $idCarrier
     * @param bool $delete
     *
     * @since 1.0.0
     */
    protected function changeGroups($idCarrier, $delete = true)
    {
        if ($delete) {
            Db::getInstance()->delete('carrier_group', '`id_carrier` = '.(int) $idCarrier);
        }
        $groups = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('`id_group`')
            ->from('group')
        );
        foreach ($groups as $group) {
            if (Tools::getIsset('groupBox') && in_array($group['id_group'], Tools::getValue('groupBox'))) {
                Db::getInstance()->insert(
                    'carrier_group',
                    [
                        'id_group'   => (int) $group['id_group'],
                        'id_carrier' => (int) $idCarrier,
                    ]
                );
            }
        }
    }

    /**
     * @param int $id
     *
     * @since 1.0.0
     */
    public function changeZones($id)
    {
        /** @var Carrier $carrier */
        $carrier = new $this->className($id);
        if (!Validate::isLoadedObject($carrier)) {
            die(Tools::displayError('The object cannot be loaded.'));
        }
        $zones = Zone::getZones(false);
        foreach ($zones as $zone) {
            if (count($carrier->getZone($zone['id_zone']))) {
                if (!isset($_POST['zone_'.$zone['id_zone']]) || !$_POST['zone_'.$zone['id_zone']]) {
                    $carrier->deleteZone($zone['id_zone']);
                }
            } elseif (isset($_POST['zone_'.$zone['id_zone']]) && $_POST['zone_'.$zone['id_zone']]) {
                $carrier->addZone($zone['id_zone']);
            }
        }
    }

    /**
     * @since 1.0.0
     */
    public function processIsFree()
    {
        $carrier = new Carrier($this->id_object);
        if (!Validate::isLoadedObject($carrier)) {
            $this->errors[] = Tools::displayError('An error occurred while updating carrier information.');
        }
        $carrier->is_free = $carrier->is_free ? 0 : 1;
        if (!$carrier->update()) {
            $this->errors[] = Tools::displayError('An error occurred while updating carrier information.');
        }
        Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
    }

    /**
     * Modifying initial getList method to display position feature (drag and drop)
     *
     * @param int         $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int         $start
     * @param int|null    $limit
     * @param int|bool    $idLangShop
     *
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        foreach ($this->_list as $key => $list) {
            if ($list['name'] == '0') {
                $this->_list[$key]['name'] = Carrier::getCarrierNameFromShopName();
            }
        }
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessUpdatePositions()
    {
        $way = (int) (Tools::getValue('way'));
        $idCarrier = (int) (Tools::getValue('id'));
        $positions = Tools::getValue($this->table);

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);

            if (isset($pos[2]) && (int) $pos[2] === $idCarrier) {
                if ($carrier = new Carrier((int) $pos[2])) {
                    if (isset($position) && $carrier->updatePosition($way, $position)) {
                        echo 'ok position '.(int) $position.' for carrier '.(int) $pos[1].'\r\n';
                    } else {
                        echo '{"hasError" : true, "errors" : "Can not update carrier '.(int) $idCarrier.' to position '.(int) $position.' "}';
                    }
                } else {
                    echo '{"hasError" : true, "errors" : "This carrier ('.(int) $idCarrier.') can t be loaded"}';
                }

                break;
            }
        }
    }

    /**
     * @param null $token
     * @param      $id
     * @param null $name
     *
     * @return string|void
     *
     * @since 1.0.0
     */
    public function displayEditLink($token = null, $id, $name = null)
    {
        if ($this->tabAccess['edit'] == 1) {
            $tpl = $this->createTemplate('helpers/list/list_action_edit.tpl');
            if (!array_key_exists('Edit', static::$cache_lang)) {
                static::$cache_lang['Edit'] = $this->l('Edit', 'Helper');
            }

            $tpl->assign([
                'href'   => $this->context->link->getAdminLink('AdminCarrierWizard').'&id_carrier='.(int) $id,
                'action' => static::$cache_lang['Edit'],
                'id'     => $id,
            ]);

            return $tpl->fetch();
        } else {
            return;
        }
    }

    /**
     * @param null $token
     * @param      $id
     * @param null $name
     *
     * @return string|void
     *
     * @since 1.0.0
     */
    public function displayDeleteLink($token = null, $id, $name = null)
    {
        if ($this->tabAccess['delete'] == 1) {
            $tpl = $this->createTemplate('helpers/list/list_action_delete.tpl');

            if (!array_key_exists('Delete', static::$cache_lang)) {
                static::$cache_lang['Delete'] = $this->l('Delete', 'Helper');
            }

            if (!array_key_exists('DeleteItem', static::$cache_lang)) {
                static::$cache_lang['DeleteItem'] = $this->l('Delete selected item?', 'Helper');
            }

            if (!array_key_exists('Name', static::$cache_lang)) {
                static::$cache_lang['Name'] = $this->l('Name:', 'Helper');
            }

            if (!is_null($name)) {
                $name = '\n\n'.static::$cache_lang['Name'].' '.$name;
            }

            $data = [
                $this->identifier => $id,
                'href'            => $this->context->link->getAdminLink('AdminCarriers').'&id_carrier='.(int) $id.'&deletecarrier=1',
                'action'          => static::$cache_lang['Delete'],
            ];

            if ($this->specificConfirmDelete !== false) {
                $data['confirm'] = !is_null($this->specificConfirmDelete) ? '\r'.$this->specificConfirmDelete : addcslashes(Tools::htmlentitiesDecodeUTF8(static::$cache_lang['DeleteItem'].$name), '\'');
            }

            $tpl->assign(array_merge($this->tpl_delete_link_vars, $data));

            return $tpl->fetch();
        } else {
            return;
        }
    }

    /**
     * @param Carrier $object
     *
     * @return int
     */
    protected function beforeDelete($object)
    {
        return $object->isUsed();
    }
}
