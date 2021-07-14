<?php
/**
 * Copyright (C) 2021 thirty bees
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
 * @copyright 2021 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

use \Thirtybees\Core\InitializationCallback;

/**
 * Class AdminCustomerMergeControllerCore
 *
 * @since 1.3.0
 */
class AdminCustomerMergeControllerCore extends AdminController implements InitializationCallback
{
    /**
     * AdminMaintenanceControllerCore constructor.
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Customer';
        $this->table = 'customer';
        parent::__construct();

        $tables = $this->getTables();

        $coreTables = [];
        $modulesTables = [];
        $otherTables = [];

        foreach ($tables as $table) {
            $operation = $this->getOperation($table);
            if (array_key_exists($table, Customer::DEFAULT_MERGE_OPERATIONS)) {
                $coreTables[$table] = [
                    'title' => _DB_PREFIX_ . $table,
                    'no_multishop_checkbox' => true,
                    'type' => 'select',
                    'identifier' => 'id',
                    'auto_value' => false,
                    'value' => $operation,
                    'disabled' => true,
                    'list' => [
                        [
                            'id' => 'update',
                            'name' => $this->l('Update')
                        ],
                        [
                            'id' => 'delete',
                            'name' => $this->l('Delete')
                        ]
                    ]
                ];
            } elseif (isset($operations[$table])) {
            } else {
                $otherTables[$table] = [
                    'title' => _DB_PREFIX_ . $table,
                    'no_multishop_checkbox' => true,
                    'type' => 'select',
                    'identifier' => 'id',
                    'auto_value' => false,
                    'value' => $operation,
                    'list' => [
                        [
                            'id' => '',
                            'name' => $this->l('Select merge method')
                        ],
                        [
                            'id' => 'update',
                            'name' => $this->l('Update')
                        ],
                        [
                            'id' => 'delete',
                            'name' => $this->l('Delete')
                        ]
                    ]
                ];
            }
        }


        $this->fields_options = [
            'standard' => [
                'title'  => $this->l('Tables managed by core'),
                'description' => $this->l('Overview of core tables that will be affected by merge. This is for your information only'),
                'fields' => $coreTables,
            ],
        ];

        if ($modulesTables) {
            $this->fields_options['module'] = [
                'title' => $this->l('Tables managed by modules'),
                'description' => $this->l('Overview of tables managed by third party modules. Modules provided information about merge strategy'),
                'fields' => $modulesTables,
            ];
        }

        if ($otherTables) {
            $this->fields_options['other'] = [
                'title' => $this->l('Additional Tables'),
                'description' => $this->l('This section contains database tables that reference customer table. System does not know anything about these tables, you have to decide on merge strategy'),
                'fields' => $otherTables,
            ];
        };

        $this->fields_options['merge'] =[
            'title' => $this->l('Merge customers'),
            'fields' => [
                'source_customer_id' => [
                    'type' => 'hidden',
                    'no_multishop_checkbox' => true,
                    'auto_value' => false,
                    'value' => Tools::getValue('source_customer_id'),
                ],
                'target_customer_id' => [
                    'type' => 'hidden',
                    'no_multishop_checkbox' => true,
                    'auto_value' => false,
                    'value' => Tools::getValue('target_customer_id'),
                ],
            ],
            'submit' => [
                'name' => 'submitMerge',
                'title' => $this->l('Merge')
            ],
        ];
    }

    public function initToolbarTitle()
    {
        $this->toolbar_title = [
            $this->l('Merge customers')
        ];
    }

    /**
     * Post process logic
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $source = $this->loadCustomer((int)Tools::getValue('source_customer_id'), $this->l('Source'));
        $target = $this->loadCustomer((int)Tools::getValue('target_customer_id'), $this->l('Target'));

        if (Tools::isSubmit('submitMerge')) {
            $tables = $this->getTables();
            $options = [];
            foreach ($tables as $table) {
                $operation = Tools::getValue($table);
                if (! $operation) {
                    $this->errors[] = sprintf($this->l('Please select merge mode for table %s'), $table);
                }
                if (! array_key_exists($table, Customer::DEFAULT_MERGE_OPERATIONS)) {
                    Configuration::updateGlobalValue('CUSTOMER_MERGE_' . strtoupper($table), $operation);
                }
                $options[$table] = $operation;
            }

            if (! $this->errors) {
                $this->merge($options, $source, $target);
            }
            return true;
        }

        return parent::postProcess();
    }

    /**
     * Returns all tables with id_customer column
     *
     * @return string[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getTables()
    {
        $results = Db::getInstance()->executeS("SELECT TABLE_NAME AS n FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=database() AND COLUMN_NAME = 'id_customer'");
        $tables = [];
        if (is_array($results)) {
            foreach ($results as $row) {
                $table = preg_replace('/^' . _DB_PREFIX_ .'/', '', $row['n']);
                $tables[] = $table;
            }
        }
        sort($tables);
        return $tables;
    }

    /**
     * Resolves merge operation
     *
     * @param string $table
     * @return string
     */
    protected function getOperation($table)
    {
        $operation = Tools::getValue($table);
        if ($operation) {
            return $operation;
        }

        $operation = Configuration::getGlobalValue('CUSTOMER_MERGE_' . strtoupper($table));
        if ($operation) {
            return $operation;
        }

        if (array_key_exists($table, Customer::DEFAULT_MERGE_OPERATIONS)) {
            return Customer::DEFAULT_MERGE_OPERATIONS[$table];
        }

        return '';
    }

    /**
     * Merges customer using options
     *
     * @param array $options
     * @param Customer $source
     * @param Customer $target
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function merge($options, Customer $source, Customer $target)
    {
        Customer::mergeAccounts($target, $source, $options);
        $idLang = $this->context->language->id;
        $params = [
            'token' => Tools::getAdminTokenLite('AdminCustomers'),
            'viewcustomer' => 1,
            'id_customer' => $target->id
        ];
        $url = Dispatcher::getInstance()->createUrl('AdminCustomers', $idLang, $params, false);
        Tools::redirectAdmin($url);
    }

    /**
     * Loads customer from database
     *
     * @param int $id customer id
     * @param string $type type - source, target
     * @return Customer | null
     * @throws PrestaShopException
     */
    protected function loadCustomer($id, $type)
    {
        if (! $id) {
            $this->errors[] = sprintf($this->l('%s customer not found'), $type);
        } else {
            $customer = new Customer($id);
            if (!Validate::isLoadedObject($customer)) {
                $this->errors[] = sprintf($this->l('%s customer with id %s not found'), $type, $id);
            }
            return $customer;
        }
        return null;
    }


    /**
     * Callback method to initialize class
     *
     * @param Db $conn
     * @return void
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public static function initializationCallback(Db $conn)
    {
        $classname = 'AdminCustomerMerge';
        $tabId = Tab::getIdFromClassName($classname);
        if (! $tabId) {
            $tab = new Tab();
            $tab->class_name = $classname;
            $tab->id_parent = -1;
            $tab->add();
        }
    }
}
