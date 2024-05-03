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
 * Class AdminTaxRulesGroupControllerCore
 *
 * @property TaxRulesGroup|TaxRule|null $object
 */
class AdminTaxRulesGroupControllerCore extends AdminController
{
    /**
     * @var TaxRule
     */
    public $tax_rule;

    /**
     * @var array
     */
    public $selected_countries = [];

    /**
     * @var array
     */
    public $selected_states = [];

    /**
     * AdminTaxRulesGroupControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'tax_rules_group';
        $this->className = 'TaxRulesGroup';
        $this->lang = false;

        $this->context = Context::getContext();

        $this->fields_list = [
            'id_tax_rules_group' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'               => [
                'title' => $this->l('Name'),
            ],
            'active'             => [
                'title'   => $this->l('Enabled'),
                'active'  => 'status',
                'type'    => 'bool',
                'orderby' => false,
                'align'   => 'center',
                'class'   => 'fixed-width-sm',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->_where .= ' AND a.deleted = 0';

        parent::__construct();
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_tax_rules_group'] = [
                'href' => static::$currentIndex.'&addtax_rules_group&token='.$this->token,
                'desc' => $this->l('Add new tax rules group', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Render list
     *
     * @return false|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    /**
     * Render form
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Tax Rules'),
                'icon'  => 'icon-money',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'required' => true,
                    'hint'     => $this->l('Invalid characters:').' <>;=#{}',
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Enable'),
                    'name'     => 'active',
                    'required' => false,
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
                ],
            ],
            'submit' => [
                'title' => $this->l('Save and stay'),
                'stay'  => true,
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        if (!($obj = $this->loadObject(true))) {
            return '';
        }
        if (!isset($obj->id)) {
            $content = parent::renderForm();
        } else {
            $this->page_header_toolbar_btn['new'] = [
                'href' => '#',
                'desc' => $this->l('Add a new tax rule'),
            ];
            $content = parent::renderForm();
            $this->tpl_folder = 'tax_rules/';
            $content .= $this->initRuleForm();

            // We change the variable $ tpl_folder to avoid the overhead calling the file in list_action_edit.tpl in intList ();

            $content .= $this->initRulesList((int) $obj->id);
        }

        return $content;
    }

    /**
     * Initialize rule form
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initRuleForm()
    {
        $this->fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('New tax rule'),
                'icon'  => 'icon-money',
            ],
            'input'  => [
                [
                    'type'    => 'select',
                    'label'   => $this->l('Country'),
                    'name'    => 'country',
                    'id'      => 'country',
                    'options' => [
                        'query'   => Country::getCountries($this->context->language->id),
                        'id'      => 'id_country',
                        'name'    => 'name',
                        'default' => [
                            'value' => 0,
                            'label' => $this->l('All', 'AdminTaxRulesGroupController'),
                        ],
                    ],
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('State'),
                    'name'     => 'states[]',
                    'id'       => 'states',
                    'multiple' => true,
                    'options'  => [
                        'query'   => [],
                        'id'      => 'id_state',
                        'name'    => 'name',
                        'default' => [
                            'value' => 0,
                            'label' => $this->l('All', 'AdminTaxRulesGroupController'),
                        ],
                    ],
                ],
                [
                    'type' => 'hidden',
                    'name' => 'action',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Zip/postal code range'),
                    'name'     => 'zipcode',
                    'required' => false,
                    'hint'     => $this->l('You can define a range of Zip/postal codes (e.g., 75000-75015) or simply use one Zip/postal code.'),
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Behavior'),
                    'name'     => 'behavior',
                    'required' => false,
                    'options'  => [
                        'query' => [
                            [
                                'id'   => 0,
                                'name' => $this->l('This tax only'),
                            ],
                            [
                                'id'   => 1,
                                'name' => $this->l('Combine'),
                            ],
                            [
                                'id'   => 2,
                                'name' => $this->l('One after another'),
                            ],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'hint'     => [
                        $this->l('You must define the behavior if an address matches multiple rules:').'<br>',
                        $this->l('- This tax only: Will apply only this tax').'<br>',
                        $this->l('- Combine: Combine taxes (e.g.: 10% + 5% = 15%)').'<br>',
                        $this->l('- One after another: Apply taxes one after another (e.g.: 0 + 10% = 0 + 5% = 5.5)'),
                    ],
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Tax'),
                    'name'     => 'id_tax',
                    'required' => false,
                    'options'  => [
                        'query'   => Tax::getTaxes((int) $this->context->language->id),
                        'id'      => 'id_tax',
                        'name'    => 'name',
                        'default' => [
                            'value' => 0,
                            'label' => $this->l('No Tax'),
                        ],
                    ],
                    'hint'     => sprintf($this->l('(Total tax: %s)'), '9%'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Description'),
                    'name'  => 'description',
                ],
            ],
            'submit' => [
                'title' => $this->l('Save and stay'),
                'stay'  => true,
            ],
        ];

        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $this->fields_value = [
            'action'             => 'create_rule',
            'id_tax_rules_group' => $obj->id,
            'id_tax_rule'        => '',
        ];

        $helper = new HelperForm();
        $helper->override_folder = $this->tpl_folder;
        $helper->currentIndex = static::$currentIndex;
        $helper->token = $this->token;
        $helper->table = 'tax_rule';
        $helper->identifier = 'id_tax_rule';
        $helper->id = $obj->id;
        $helper->toolbar_scroll = true;
        $helper->show_toolbar = true;
        $helper->languages = $this->getLanguages();
        $helper->default_form_language = $this->getDefaultFormLanguage();
        $helper->allow_employee_form_lang = $this->getAllowEmployeeFormLanguage();
        $helper->fields_value = $this->getFieldsValue($this->object);
        $helper->toolbar_btn['save_new_rule'] = [
            'href'  => static::$currentIndex.'&amp;id_tax_rules_group='.$obj->id.'&amp;action=create_rule&amp;token='.$this->token,
            'desc'  => 'Save tax rule',
            'class' => 'process-icon-save',
        ];
        $helper->submit_action = 'create_rule';

        return $helper->generateForm($this->fields_form);
    }

    /**
     * Initialize rules list
     *
     * @param int $idGroup
     *
     * @return false|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initRulesList($idGroup)
    {
        $this->table = 'tax_rule';
        $this->list_id = 'tax_rule';
        $this->identifier = 'id_tax_rule';
        $this->className = 'TaxRule';
        $this->lang = false;
        $this->list_simple_header = false;
        $this->toolbar_btn = null;
        $this->list_no_link = true;

        $this->bulk_actions = [
            'delete' => ['text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?'), 'icon' => 'icon-trash'],
        ];

        $this->fields_list = [
            'country_name' => [
                'title' => $this->l('Country'),
            ],
            'state_name'   => [
                'title' => $this->l('State'),
            ],
            'zipcode'      => [
                'title' => $this->l('Zip/Postal code'),
                'class' => 'fixed-width-md',
            ],
            'behavior'     => [
                'title' => $this->l('Behavior'),
            ],
            'rate'         => [
                'title' => $this->l('Tax'),
                'class' => 'fixed-width-sm',
            ],
            'tax_name'     => [
                'title' => $this->l('Tax name'),
            ],
            'description'  => [
                'title' => $this->l('Description'),
            ],
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_select = '
			c.`name` AS country_name,
			s.`name` AS state_name,
			tl.`name` AS tax_name,
			CONCAT_WS(" - ", a.`zipcode_from`, a.`zipcode_to`) AS zipcode,
			t.rate';

        $this->_join = '
			LEFT JOIN `'._DB_PREFIX_.'country_lang` c
				ON (a.`id_country` = c.`id_country` AND id_lang = '.(int) $this->context->language->id.')
           		LEFT JOIN `'._DB_PREFIX_.'tax_lang` tl
                		ON (a.`id_tax` = tl.`id_tax` AND tl.`id_lang` = '.(int) $this->context->language->id.')
			LEFT JOIN `'._DB_PREFIX_.'state` s
				ON (a.`id_state` = s.`id_state`)
			LEFT JOIN `'._DB_PREFIX_.'tax` t
				ON (a.`id_tax` = t.`id_tax`)';
        $this->_where = 'AND `id_tax_rules_group` = '.(int) $idGroup;
        $this->_orderBy = 'c!name';
        $this->_use_found_rows = false;

        $this->show_toolbar = false;
        $this->tpl_list_vars = ['id_tax_rules_group' => (int) $idGroup];

        $this->_filter = false;

        return parent::renderList();
    }

    /**
     * Initialize processing
     *
     * @return void
     */
    public function initProcess()
    {
        if (Tools::isSubmit('deletetax_rule')) {
            if ($this->hasDeletePermission()) {
                $this->action = 'delete_tax_rule';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        } elseif (Tools::isSubmit('submitBulkdeletetax_rule')) {
            if ($this->hasDeletePermission()) {
                $this->action = 'bulk_delete_tax_rules';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        } elseif (Tools::getValue('action') == 'create_rule') {
            if ($this->hasAddPermission()) {
                $this->action = 'create_rule';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        } else {
            parent::initProcess();
        }
    }

    /**
     * Process rule create
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processCreateRule()
    {
        $zipCode = Tools::getValue('zipcode');
        $idRule = Tools::getIntValue('id_tax_rule');
        $idTax = Tools::getIntValue('id_tax');
        $idTaxRulesGroup = Tools::getIntValue('id_tax_rules_group');
        $behavior = Tools::getIntValue('behavior');
        $description = pSQL(Tools::getValue('description'));

        if ((int) ($idCountry = Tools::getValue('country')) == 0) {
            $countries = Country::getCountries($this->context->language->id);
            $this->selected_countries = [];
            foreach ($countries as $country) {
                $this->selected_countries[] = (int) $country['id_country'];
            }
        } else {
            $this->selected_countries = [$idCountry];
        }
        $this->selected_states = Tools::getValue('states');

        if (empty($this->selected_states) || count($this->selected_states) == 0) {
            $this->selected_states = [0];
        }
        $taxRulesGroup = new TaxRulesGroup((int) $idTaxRulesGroup);
        foreach ($this->selected_countries as $idCountry) {
            $first = true;
            foreach ($this->selected_states as $idState) {
                if ($taxRulesGroup->hasUniqueTaxRuleForCountry($idCountry, $idState, $idRule)) {
                    $this->errors[] = Tools::displayError('A tax rule already exists for this country/state with tax only behavior.');
                    continue;
                }
                $tr = new TaxRule();

                // update or creation?
                if ($first) {
                    $tr->id = $idRule;
                    $first = false;
                }

                $tr->id_tax = $idTax;
                $taxRulesGroup = new TaxRulesGroup((int) $idTaxRulesGroup);
                $tr->id_tax_rules_group = (int) $taxRulesGroup->id;
                $tr->id_country = (int) $idCountry;
                $tr->id_state = (int) $idState;
                list($tr->zipcode_from, $tr->zipcode_to) = $tr->breakDownZipCode($zipCode);

                // Construct Object Country
                $country = new Country((int) $idCountry, (int) $this->context->language->id);

                if ($zipCode && $country->need_zip_code) {
                    if ($country->zip_code_format) {
                        foreach ([$tr->zipcode_from, $tr->zipcode_to] as $zipCode) {
                            if ($zipCode) {
                                if (!$country->checkZipCode($zipCode)) {
                                    $this->errors[] = sprintf(
                                        Tools::displayError('The Zip/postal code is invalid. It must be typed as follows: %s for %s.'),
                                        str_replace('C', $country->iso_code, str_replace('N', '0', str_replace('L', 'A', $country->zip_code_format))), $country->name
                                    );
                                }
                            }
                        }
                    }
                }

                $tr->behavior = (int) $behavior;
                $tr->description = $description;
                $this->tax_rule = $tr;
                $_POST['id_state'] = $tr->id_state;

                $this->errors = array_merge($this->errors, $this->validateTaxRule($tr));

                if (count($this->errors) == 0) {
                    $taxRulesGroup = $this->updateTaxRulesGroup($taxRulesGroup);
                    $tr->id = (int) $taxRulesGroup->getIdTaxRuleGroupFromHistorizedId((int) $tr->id);
                    $tr->id_tax_rules_group = (int) $taxRulesGroup->id;

                    if (!$tr->save()) {
                        $this->errors[] = Tools::displayError('An error has occurred: Cannot save the current tax rule.');
                    }
                }
            }
        }

        if (count($this->errors) == 0) {
            Tools::redirectAdmin(
                static::$currentIndex.'&'.$this->identifier.'='.(int) $taxRulesGroup->id.'&conf=4&update'.$this->table.'&token='.$this->token
            );
        } else {
            $this->display = 'edit';
        }
    }

    /**
     * Check if the tax rule could be added in the database
     *
     * @param TaxRule $tr
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function validateTaxRule(TaxRule $tr)
    {
        // @TODO: check if the rule already exists
        return $tr->validateController();
    }

    /**
     * @param TaxRulesGroup $object
     *
     * @return TaxRulesGroup
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function updateTaxRulesGroup($object)
    {
        static $taxRulesGroup = null;
        if ($taxRulesGroup === null) {
            $object->update();
            $taxRulesGroup = $object;
        }

        return $taxRulesGroup;
    }

    /**
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processBulkDeleteTaxRules()
    {
        $this->deleteTaxRule(Tools::getArrayValue('tax_ruleBox'));
    }

    /**
     * Delete Tax Rule
     *
     * @param array $idTaxRuleList
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function deleteTaxRule(array $idTaxRuleList)
    {
        foreach ($idTaxRuleList as $idTaxRule) {
            $taxRule = new TaxRule((int) $idTaxRule);
            if (Validate::isLoadedObject($taxRule)) {
                $taxRulesGroup = new TaxRulesGroup((int) $taxRule->id_tax_rules_group);
                $taxRulesGroup = $this->updateTaxRulesGroup($taxRulesGroup);
                $taxRule = new TaxRule($taxRulesGroup->getIdTaxRuleGroupFromHistorizedId((int) $idTaxRule));
                if (Validate::isLoadedObject($taxRule)) {
                    $taxRule->delete();
                }
            }
        }

        Tools::redirectAdmin(
            static::$currentIndex.'&'.$this->identifier.'='.(int) $taxRulesGroup->id.'&conf=4&update'.$this->table.'&token='.$this->token
        );
    }

    /**
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processDeleteTaxRule()
    {
        $this->deleteTaxRule([Tools::getValue('id_tax_rule')]);
    }

    /**
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function displayAjaxUpdateTaxRule()
    {
        if ($this->hasViewPermission()) {
            $idTaxRule = Tools::getIntValue('id_tax_rule');
            $taxRules = new TaxRule($idTaxRule);
            $output = [];
            foreach ($taxRules as $key => $result) {
                $output[$key] = $result;
            }
            $this->ajaxDie(json_encode($output));
        }
    }
}
