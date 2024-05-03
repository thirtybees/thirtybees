<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * Class CoreModelsCore
 *
 * This class holds metadata information about database tables that are required by thirtybees system,
 * but for which no ObjectModel class exists
 *
 * These metadata are required in order to create database structure based on thirtybees php code only
 */
class CoreModelsCore
{
    /**
     * Returns array of definitions with the same format as ObjectModel::$definition
     *
     * @return array
     */
    public static function getModels()
    {
        $access = [
            'table' => 'access',
            'fields' => [
                'id_profile' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_tab'     => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'view'       => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
                'add'        => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
                'edit'       => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
                'delete'     => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
            ],
            'keys' => [
                'access' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_profile', 'id_tab']],
                ],
            ],
        ];

        $accessory = [
            'table' => 'accessory',
            'fields' => [
                'id_product_1' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_product_2' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'accessory' => [
                    'accessory_product' => ['type' => ObjectModel::KEY, 'columns' => ['id_product_1', 'id_product_2']],
                ],
            ],
        ];

        $attributeImpact = [
            'table' => 'attribute_impact',
            'primary' => 'id_attribute_impact',
            'fields' => [
                'id_product'          => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_attribute'        => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'weight'              => ['type' => ObjectModel::TYPE_FLOAT, 'required' => true],
                'price'               => ['type' => ObjectModel::TYPE_FLOAT, 'required' => true],
            ],
            'keys' => [
                'attribute_impact' => [
                    'id_product' => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['id_product', 'id_attribute']],
                ],
            ],
        ];

        $carrierGroup = [
            'table' => 'carrier_group',
            'fields' => [
                'id_carrier' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_group'    => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'carrier_group' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_carrier', 'id_group']],
                ],
            ],
        ];

        $carrierTaxRulesGroupShop = [
            'table' => 'carrier_tax_rules_group_shop',
            'fields' => [
                'id_carrier'         => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_tax_rules_group' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_shop'            => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'carrier_tax_rules_group_shop' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_carrier', 'id_tax_rules_group', 'id_shop']],
                ],
            ],
        ];

        $carrierZone = [
            'table' => 'carrier_zone',
            'fields' => [
                'id_carrier' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_zone'    => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'carrier_zone' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_carrier', 'id_zone']],
                ],
            ],
        ];

        $cartCartRule = [
            'table' => 'cart_cart_rule',
            'fields' => [
                'id_cart'      => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_cart_rule' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'cart_cart_rule' => [
                    'primary'      => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_cart', 'id_cart_rule']],
                    'id_cart_rule' => ['type' => ObjectModel::KEY, 'columns' => ['id_cart_rule']],
                ],
            ],
        ];

        $cartProduct = [
            'table' => 'cart_product',
            'fields' => [
                'id_cart'              => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_product'           => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_address_delivery'  => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
                'id_shop'              => ['type' => ObjectModel::TYPE_INT, 'default' => '1'],
                'id_product_attribute' => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
                'quantity'             => ['type' => ObjectModel::TYPE_INT, 'default' => '0'],
                'date_add'             => ['type' => ObjectModel::TYPE_DATE, 'required' => true],
                'date_upd'             => ['type' => ObjectModel::TYPE_DATE, 'default' => ObjectModel::DEFAULT_CURRENT_TIMESTAMP],
            ],
            'keys' => [
                'cart_product' => [
                    'primary'              => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_cart', 'id_product', 'id_product_attribute', 'id_address_delivery']],
                    'id_product_attribute' => ['type' => ObjectModel::KEY, 'columns' => ['id_product_attribute']],
                    'id_cart_order'        => ['type' => ObjectModel::KEY, 'columns' => ['id_cart', 'date_add', 'id_product', 'id_product_attribute']],
                ],
            ],
        ];

        $cartRuleCarrier = [
            'table' => 'cart_rule_carrier',
            'fields' => [
                'id_cart_rule' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_carrier'   => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'cart_rule_carrier' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_cart_rule', 'id_carrier']],
                ],
            ],
        ];

        $cartRuleCombination = [
            'table' => 'cart_rule_combination',
            'fields' => [
                'id_cart_rule_1' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_cart_rule_2' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'cart_rule_combination' => [
                    'primary'        => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_cart_rule_1', 'id_cart_rule_2']],
                    'id_cart_rule_1' => ['type' => ObjectModel::KEY, 'columns' => ['id_cart_rule_1']],
                    'id_cart_rule_2' => ['type' => ObjectModel::KEY, 'columns' => ['id_cart_rule_2']],
                ],
            ],
        ];

        $cartRuleCountry = [
            'table' => 'cart_rule_country',
            'fields' => [
                'id_cart_rule' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_country'   => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'cart_rule_country' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_cart_rule', 'id_country']],
                ],
            ],
        ];

        $cartRuleGroup = [
            'table' => 'cart_rule_group',
            'fields' => [
                'id_cart_rule' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_group'     => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'cart_rule_group' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_cart_rule', 'id_group']],
                ],
            ],
        ];

        $cartRuleProductRule = [
            'table' => 'cart_rule_product_rule',
            'primary' => 'id_product_rule',
            'fields' => [
                'id_product_rule_group' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'type'                  => ['type' => ObjectModel::TYPE_STRING, 'required' => true, 'values' => ['products','categories','attributes','manufacturers','suppliers']],
            ],
        ];

        $cartRuleProductRuleGroup = [
            'table' => 'cart_rule_product_rule_group',
            'primary' => 'id_product_rule_group',
            'fields' => [
                'id_cart_rule'          => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'quantity'              => ['type' => ObjectModel::TYPE_INT, 'default' => '1', 'required' => true],
            ],
        ];

        $cartRuleProductRuleValue = [
            'table' => 'cart_rule_product_rule_value',
            'fields' => [
                'id_product_rule' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_item'         => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'cart_rule_product_rule_value' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_product_rule', 'id_item']],
                ],
            ],
        ];

        $cartRuleShop = [
            'table' => 'cart_rule_shop',
            'fields' => [
                'id_cart_rule' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_shop'      => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
        ];

        $categoryGroup = [
            'table' => 'category_group',
            'fields' => [
                'id_category' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_group'    => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'category_group' => [
                    'primary'     => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_category', 'id_group']],
                    'id_category' => ['type' => ObjectModel::KEY, 'columns' => ['id_category']],
                    'id_group'    => ['type' => ObjectModel::KEY, 'columns' => ['id_group']],
                ],
            ],
        ];

        $categoryProduct = [
            'table' => 'category_product',
            'fields' => [
                'id_category' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_product'  => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'position'  => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
            ],
            'keys' => [
                'category_product' => [
                    'primary'     => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_category', 'id_product']],
                    'id_category' => ['type' => ObjectModel::KEY, 'columns' => ['id_category', 'position']],
                    'id_product'  => ['type' => ObjectModel::KEY, 'columns' => ['id_product']],
                ],
            ],
        ];

        $cmsRoleLang = [
            'table' => 'cms_role_lang',
            'fields' => [
                'id_cms_role' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_lang'     => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_shop'     => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'name'        => ['type' => ObjectModel::TYPE_STRING, 'size' => 128],
            ],
        ];

        $compareProduct = [
            'table' => 'compare_product',
            'fields' => [
                'id_compare' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_product' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'date_add'   => ['type' => ObjectModel::TYPE_DATE, 'required' => true],
                'date_upd'   => ['type' => ObjectModel::TYPE_DATE, 'required' => true],
            ],
        ];

        $configurationKpi = [
            'table' => 'configuration_kpi',
            'primary' => 'id_configuration_kpi',
            'fields' => [
                'id_shop_group'        => ['type' => ObjectModel::TYPE_INT],
                'id_shop'              => ['type' => ObjectModel::TYPE_INT],
                'name'                 => ['type' => ObjectModel::TYPE_STRING, 'size' => 64, 'required' => true],
                'value'                => ['type' => ObjectModel::TYPE_STRING, 'size' => ObjectModel::SIZE_TEXT],
                'date_add'             => ['type' => ObjectModel::TYPE_DATE, 'required' => true],
                'date_upd'             => ['type' => ObjectModel::TYPE_DATE, 'required' => true],
            ],
        ];

        $configurationKpiLang = [
            'table' => 'configuration_kpi_lang',
            'fields' => [
                'id_configuration_kpi' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_lang'              => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'value'                => ['type' => ObjectModel::TYPE_STRING, 'size' => ObjectModel::SIZE_TEXT],
                'date_upd'             => ['type' => ObjectModel::TYPE_DATE],
            ],
        ];

        $connectionsPage = [
            'table' => 'connections_page',
            'fields' => [
                'id_connections' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_page'        => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'time_start'     => ['type' => ObjectModel::TYPE_DATE, 'required' => true],
                'time_end'       => ['type' => ObjectModel::TYPE_DATE],
            ],
            'keys' => [
                'connections_page' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_connections', 'id_page', 'time_start']],
                ],
            ],
        ];

        $currencyModule = [
            'table' => 'currency_module',
            'fields' => [
                'id_currency' => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'unique' => 'uc_id_currency'],
                'id_module'   => ['type' => ObjectModel::TYPE_INT],
            ],
        ];

        $customerGroup = [
            'table' => 'customer_group',
            'fields' => [
                'id_customer' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_group'    => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'customer_group' => [
                    'primary'        => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_customer', 'id_group']],
                    'customer_login' => ['type' => ObjectModel::KEY, 'columns' => ['id_group']],
                    'id_customer'    => ['type' => ObjectModel::KEY, 'columns' => ['id_customer']],
                ],
            ],
        ];

        $customerMessageSyncImap = [
            'table' => 'customer_message_sync_imap',
            'fields' => [
                'md5_header' => ['type' => ObjectModel::TYPE_STRING, 'required' => true, 'size' => 32, 'dbType' => 'varbinary(32)', 'charset' => [null, null]],
            ],
            'keys' => [
                'customer_message_sync_imap' => [
                    'md5_header_index' => ['type' => ObjectModel::KEY, 'columns' => ['md5_header'], 'subParts' => [4]],
                ],
            ],
        ];

        $customizedData = [
            'table' => 'customized_data',
            'fields' => [
                'id_customization' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'type'             => ['type' => ObjectModel::TYPE_INT, 'size' => 1, 'required' => true, 'dbType' => 'tinyint(1)'],
                'index'            => ['type' => ObjectModel::TYPE_INT, 'size' => 3, 'required' => true, 'dbType' => 'int(3)'],
                'value'            => ['type' => ObjectModel::TYPE_STRING, 'required' => true],
            ],
            'keys' => [
                'customized_data' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_customization', 'type', 'index']],
                ],
            ],
        ];

        $employeeNotification = [
            'table'   => 'employee_notification',
            'fields'  => [
                'id_employee' => ['type' => ObjectModel::TYPE_INT, 'validate' => 'isInt', 'required' => true ],
                'type' => ['type' => ObjectModel::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 64 ],
                'last_id' => ['type' => ObjectModel::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'default' => '0'],
            ],
            'keys' => [
                'employee_notification' => [
                    'primary'          => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_employee', 'type']],
                ]
            ],
        ];

        $featureProduct = [
            'table' => 'feature_product',
            'multilang' => true,
            'fields' => [
                'id_feature'       => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_product'       => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_feature_value' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'displayable'      => ['type' => ObjectModel::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            ],
            'keys' => [
                'feature_product' => [
                    'primary'          => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_feature', 'id_product', 'id_feature_value']],
                    'id_feature_value' => ['type' => ObjectModel::KEY, 'columns' => ['id_feature_value']],
                    'id_product'       => ['type' => ObjectModel::KEY, 'columns' => ['id_product']],
                ],
            ],
        ];

        $hookAlias = [
            'table' => 'hook_alias',
            'primary' => 'id_hook_alias',
            'fields' => [
                'alias'         => ['type' => ObjectModel::TYPE_STRING, 'size' => 64, 'required' => true, 'unique' => true],
                'name'          => ['type' => ObjectModel::TYPE_STRING, 'size' => 64, 'required' => true],
            ],
        ];

        $hookModule = [
            'table' => 'hook_module',
            'fields' => [
                'id_module' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_shop'   => ['type' => ObjectModel::TYPE_INT, 'default' => '1'],
                'id_hook'   => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'position'  => ['type' => ObjectModel::TYPE_INT, 'size' => 2, 'required' => true, 'dbType' => 'tinyint(2) unsigned'],
            ],
            'keys' => [
                'hook_module' => [
                    'primary'   => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_module', 'id_hook', 'id_shop']],
                    'id_hook'   => ['type' => ObjectModel::KEY, 'columns' => ['id_hook']],
                    'id_module' => ['type' => ObjectModel::KEY, 'columns' => ['id_module']],
                    'position'  => ['type' => ObjectModel::KEY, 'columns' => ['id_shop', 'position']],
                ],
            ],
        ];

        $hookModuleExceptions = [
            'table' => 'hook_module_exceptions',
            'primary' => 'id_hook_module_exceptions',
            'fields' => [
                'id_shop'                   => ['type' => ObjectModel::TYPE_INT, 'default' => '1'],
                'id_module'                 => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_hook'                   => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'file_name'                 => ['type' => ObjectModel::TYPE_STRING],
            ],
            'keys' => [
                'hook_module_exceptions' => [
                    'id_hook'   => ['type' => ObjectModel::KEY, 'columns' => ['id_hook']],
                    'id_module' => ['type' => ObjectModel::KEY, 'columns' => ['id_module']],
                ],
            ],
        ];

        $importMatch = [
            'table' => 'import_match',
            'primary' => 'id_import_match',
            'primaryKeyDbType' => 'int(10)',
            'fields' => [
                'name'            => ['type' => ObjectModel::TYPE_STRING, 'size' => 32, 'required' => true],
                'match'           => ['type' => ObjectModel::TYPE_STRING, 'size' => ObjectModel::SIZE_TEXT, 'required' => true],
                'skip'            => ['type' => ObjectModel::TYPE_INT, 'size' => 2, 'required' => true, 'dbType' => 'int(2)'],
            ],
        ];

        $memcachedServers = [
            'table' => 'memcached_servers',
            'primary' => 'id_memcached_server',
            'fields' => [
                'ip'                  => ['type' => ObjectModel::TYPE_STRING, 'size' => 254, 'required' => true],
                'port'                => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'weight'              => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
        ];

        $messageReaded = [
            'table' => 'message_readed',
            'fields' => [
                'id_message'  => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_employee' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'date_add'    => ['type' => ObjectModel::TYPE_DATE, 'required' => true],
            ],
            'keys' => [
                'message_readed' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_message', 'id_employee']],
                ],
            ],
        ];

        $module = [
            'table' => 'module',
            'primary' => 'id_module',
            'fields' => [
                'name'      => ['type' => ObjectModel::TYPE_STRING, 'size' => 64, 'required' => true],
                'active'    => ['type' => ObjectModel::TYPE_BOOL, 'required' => true, 'default' => '0'],
                'version'   => ['type' => ObjectModel::TYPE_STRING, 'size' => 8, 'required' => true],
            ],
            'keys' => [
                'module' => [
                    'name' => ['type' => ObjectModel::KEY, 'columns' => ['name']],
                ],
                'module_shop' => [
                    'id_shop' => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
                ],
            ],
        ];

        $moduleAccess = [
            'table' => 'module_access',
            'fields' => [
                'id_profile' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_module'  => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'view'       => ['type' => ObjectModel::TYPE_BOOL, 'required' => true, 'default' => '0', 'dbType' => 'tinyint(1)'],
                'configure'  => ['type' => ObjectModel::TYPE_BOOL, 'required' => true, 'default' => '0', 'dbType' => 'tinyint(1)'],
                'uninstall'  => ['type' => ObjectModel::TYPE_BOOL, 'required' => true, 'default' => '0', 'dbType' => 'tinyint(1)'],
            ],
            'keys' => [
                'module_access' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_profile', 'id_module']],
                ],
            ],
        ];

        $moduleCarrier = [
            'table' => 'module_carrier',
            'fields' => [
                'id_module'    => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_shop'      => ['type' => ObjectModel::TYPE_INT, 'default' => '1'],
                'id_reference' => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
            ],
        ];

        $moduleCountry = [
            'table' => 'module_country',
            'fields' => [
                'id_module'  => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_shop'    => ['type' => ObjectModel::TYPE_INT, 'default' => '1'],
                'id_country' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'module_country' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_module', 'id_shop', 'id_country']],
                ],
            ],
        ];

        $moduleCurrency = [
            'table' => 'module_currency',
            'fields' => [
                'id_module'   => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_shop'     => ['type' => ObjectModel::TYPE_INT, 'default' => '1'],
                'id_currency' => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
            ],
            'keys' => [
                'module_currency' => [
                    'primary'   => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_module', 'id_shop', 'id_currency']],
                    'id_module' => ['type' => ObjectModel::KEY, 'columns' => ['id_module']],
                ],
            ],
        ];

        $moduleGroup = [
            'table' => 'module_group',
            'fields' => [
                'id_module' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_shop'   => ['type' => ObjectModel::TYPE_INT, 'default' => '1'],
                'id_group'  => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'module_group' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_module', 'id_shop', 'id_group']],
                ],
            ],
        ];

        $modulePreference = [
            'table' => 'module_preference',
            'primary' => 'id_module_preference',
            'primaryKeyDbType' => 'int(11)',
            'fields' => [
                'id_employee'          => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
                'module'               => ['type' => ObjectModel::TYPE_STRING, 'size' => 64, 'required' => true],
                'interest'             => ['type' => ObjectModel::TYPE_BOOL, 'dbType' => 'tinyint(1)'],
                'favorite'             => ['type' => ObjectModel::TYPE_BOOL, 'dbType' => 'tinyint(1)'],
            ],
            'keys' => [
                'module_preference' => [
                    'employee_module' => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['id_employee', 'module']],
                ],
            ],
        ];

        $moduleShop = [
            'table' => 'module_shop',
            'fields' => [
                'id_module'     => ['type' => ObjectModel::TYPE_INT],
                'id_shop'       => ['type' => ObjectModel::TYPE_INT],
                'enable_device' => ['type' => ObjectModel::TYPE_BOOL, 'default' => '7', 'dbType' => 'tinyint(1)'],
            ],
        ];

        $modulesPerfs = [
            'table' => 'modules_perfs',
            'primary' => 'id_modules_perfs',
            'fields' => [
                'session'          => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'module'           => ['type' => ObjectModel::TYPE_STRING, 'size' => 64, 'required' => true],
                'method'           => ['type' => ObjectModel::TYPE_STRING, 'size' => 126, 'required' => true],
                'time_start'       => ['type' => ObjectModel::TYPE_FLOAT, 'dbType' => 'double unsigned', 'required' => true],
                'time_end'         => ['type' => ObjectModel::TYPE_FLOAT, 'dbType' => 'double unsigned', 'required' => true],
                'memory_start'     => ['type' => ObjectModel::TYPE_INT, 'size' => 10, 'required' => true],
                'memory_end'       => ['type' => ObjectModel::TYPE_INT, 'size' => 10, 'required' => true],
            ],
            'keys' => [
                'modules_perfs' => [
                    'session' => ['type' => ObjectModel::KEY, 'columns' => ['session']],
                ],
            ],
        ];

        $operatingSystem = [
            'table' => 'operating_system',
            'primary' => 'id_operating_system',
            'fields' => [
                'name'                => ['type' => ObjectModel::TYPE_STRING, 'size' => 64],
            ],
            'keys' => [
                'operating_system' => [
                    'os_name' => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['name']]
                ]
            ]
        ];

        $orderDetailTax = [
            'table' => 'order_detail_tax',
            'fields' => [
                'id_order_detail' => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
                'id_tax'          => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
                'unit_amount'     => ['type' => ObjectModel::TYPE_FLOAT, 'default' => '0.000000'],
                'total_amount'    => ['type' => ObjectModel::TYPE_FLOAT, 'default' => '0.000000'],
            ],
            'keys' => [
                'order_detail_tax' => [
                    'id_order_detail' => ['type' => ObjectModel::KEY, 'columns' => ['id_order_detail']],
                    'id_tax'          => ['type' => ObjectModel::KEY, 'columns' => ['id_tax']],
                ],
            ],
        ];

        $orderInvoicePayment = [
            'table' => 'order_invoice_payment',
            'fields' => [
                'id_order_invoice' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_order_payment' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_order'         => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'order_invoice_payment' => [
                    'primary'       => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_order_invoice', 'id_order_payment']],
                    'id_order'      => ['type' => ObjectModel::KEY, 'columns' => ['id_order']],
                    'order_payment' => ['type' => ObjectModel::KEY, 'columns' => ['id_order_payment']],
                ],
            ],
        ];

        $orderInvoiceTax = [
            'table' => 'order_invoice_tax',
            'fields' => [
                'id_order_invoice' => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
                'type'             => ['type' => ObjectModel::TYPE_STRING, 'size' => 15, 'required' => true],
                'id_tax'           => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
                'amount'           => ['type' => ObjectModel::TYPE_FLOAT, 'default' => '0.000000'],
            ],
            'keys' => [
                'order_invoice_tax' => [
                    'id_tax' => ['type' => ObjectModel::KEY, 'columns' => ['id_tax']],
                ],
            ],
        ];

        $orderReturnDetail = [
            'table' => 'order_return_detail',
            'fields' => [
                'id_order_return'  => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_order_detail'  => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_customization' => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
                'product_quantity' => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
            ],
            'keys' => [
                'order_return_detail' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_order_return', 'id_order_detail', 'id_customization']],
                ],
            ],
        ];

        $orderSlipDetail = [
            'table' => 'order_slip_detail',
            'fields' => [
                'id_order_slip'        => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_order_detail'      => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'product_quantity'     => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
                'unit_price_tax_excl'  => ['type' => ObjectModel::TYPE_FLOAT],
                'unit_price_tax_incl'  => ['type' => ObjectModel::TYPE_FLOAT],
                'total_price_tax_excl' => ['type' => ObjectModel::TYPE_FLOAT],
                'total_price_tax_incl' => ['type' => ObjectModel::TYPE_FLOAT],
                'amount_tax_excl'      => ['type' => ObjectModel::TYPE_FLOAT],
                'amount_tax_incl'      => ['type' => ObjectModel::TYPE_FLOAT],
            ],
            'keys' => [
                'order_slip_detail' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_order_slip', 'id_order_detail']],
                ],
            ],
        ];

        $orderSlipDetailTax = [
            'table' => 'order_slip_detail_tax',
            'fields' => [
                'id_order_slip_detail' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_tax'               => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'unit_amount'          => ['type' => ObjectModel::TYPE_FLOAT, 'default' => '0.000000'],
                'total_amount'         => ['type' => ObjectModel::TYPE_FLOAT, 'default' => '0.000000'],
            ],
            'keys' => [
                'order_slip_detail_tax' => [
                    'id_order_slip_detail' => ['type' => ObjectModel::KEY, 'columns' => ['id_order_slip_detail']],
                    'id_tax'               => ['type' => ObjectModel::KEY, 'columns' => ['id_tax']],
                ],
            ],
        ];

        $pack = [
            'table' => 'pack',
            'fields' => [
                'id_product_pack'           => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_product_item'           => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_product_attribute_item' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'quantity'                  => ['type' => ObjectModel::TYPE_INT, 'default' => '1'],
            ],
            'keys' => [
                'pack' => [
                    'primary'      => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_product_pack', 'id_product_item', 'id_product_attribute_item']],
                    'product_item' => ['type' => ObjectModel::KEY, 'columns' => ['id_product_item', 'id_product_attribute_item']],
                ],
            ],
        ];

        $pageCache = [
            'table' => 'page_cache',
            'fields' => [
                'cache_hash'  => ['type' => ObjectModel::TYPE_STRING, 'size' => 32, 'dbType' => 'char(32)', 'required' => true],
                'id_currency' => ['type' => ObjectModel::TYPE_INT],
                'id_language' => ['type' => ObjectModel::TYPE_INT],
                'id_country'  => ['type' => ObjectModel::TYPE_INT],
                'id_shop'     => ['type' => ObjectModel::TYPE_INT],
                'entity_type' => ['type' => ObjectModel::TYPE_STRING, 'size' => 12, 'required' => true],
                'id_entity'   => ['type' => ObjectModel::TYPE_INT],
            ],
            'keys' => [
                'page_cache' => [
                    'primary'     => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['cache_hash']],
                    'cache_combo' => ['type' => ObjectModel::KEY, 'columns' => ['cache_hash', 'id_currency', 'id_language', 'id_country', 'id_shop']],
                ],
            ],
        ];

        $pageType = [
            'table' => 'page_type',
            'primary' => 'id_page_type',
            'fields' => [
                'name'         => ['type' => ObjectModel::TYPE_STRING, 'required' => true],
            ],
        ];

        $pageViewed = [
            'table' => 'page_viewed',
            'fields' => [
                'id_page'       => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_shop_group' => ['type' => ObjectModel::TYPE_INT, 'size' => 10, 'default' => '1'],
                'id_shop'       => ['type' => ObjectModel::TYPE_INT, 'size' => 10, 'default' => '1'],
                'id_date_range' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'counter'       => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'page_viewed' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_page', 'id_date_range', 'id_shop']],
                ],
            ],
        ];

        $productAttachment = [
            'table' => 'product_attachment',
            'fields' => [
                'id_product'    => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_attachment' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'product_attachment' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_product', 'id_attachment']],
                ],
            ],
        ];

        $productAttributeCombination = [
            'table' => 'product_attribute_combination',
            'fields' => [
                'id_attribute'         => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_product_attribute' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'product_attribute_combination' => [
                    'primary'              => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_attribute', 'id_product_attribute']],
                    'id_product_attribute' => ['type' => ObjectModel::KEY, 'columns' => ['id_product_attribute']],
                ],
            ],
        ];

        $productAttributeImage = [
            'table' => 'product_attribute_image',
            'fields' => [
                'id_product_attribute' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_image'             => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'product_attribute_image' => [
                    'primary'  => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_product_attribute', 'id_image']],
                    'id_image' => ['type' => ObjectModel::KEY, 'columns' => ['id_image']],
                ],
            ],
        ];

        $productCarrier = [
            'table' => 'product_carrier',
            'fields' => [
                'id_product'           => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_carrier_reference' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_shop'              => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'product_carrier' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_product', 'id_carrier_reference', 'id_shop']],
                ],
            ],
        ];

        $productCountryTax = [
            'table' => 'product_country_tax',
            'fields' => [
                'id_product' => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
                'id_country' => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
                'id_tax'     => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'dbType' => 'int(11)'],
            ],
            'keys' => [
                'product_country_tax' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_product', 'id_country']],
                ],
            ],
        ];

        $productGroupReductionCache = [
            'table' => 'product_group_reduction_cache',
            'fields' => [
                'id_product' => ['type' => ObjectModel::TYPE_INT, 'size' => 10, 'required' => true],
                'id_group'   => ['type' => ObjectModel::TYPE_INT, 'size' => 10, 'required' => true],
                'reduction'  => ['type' => ObjectModel::TYPE_FLOAT, 'size' => 4, 'decimals' => 3, 'required' => true],
            ],
            'keys' => [
                'product_group_reduction_cache' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_product', 'id_group']],
                ],
            ],
        ];

        $productSale = [
            'table' => 'product_sale',
            'fields' => [
                'id_product' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'quantity'   => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
                'sale_nbr'   => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
                'date_upd'   => ['type' => ObjectModel::TYPE_DATE, 'required' => true, 'dbType' => 'date'],
            ],
            'keys' => [
                'product_sale' => [
                    'primary'  => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_product']],
                    'quantity' => ['type' => ObjectModel::KEY, 'columns' => ['quantity']],
                ],
            ],
        ];

        $productTag = [
            'table' => 'product_tag',
            'fields' => [
                'id_product' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_tag'     => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_lang'    => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'product_tag' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_product', 'id_tag']],
                    'id_lang' => ['type' => ObjectModel::KEY, 'columns' => ['id_lang', 'id_tag']],
                    'id_tag'  => ['type' => ObjectModel::KEY, 'columns' => ['id_tag']],
                ],
            ],
        ];

        $profilePermission = [
            'table' => 'profile_permission',
            'primary' => 'id_profile_permission',
            'fields' => [
                'id_profile' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'perm_type' => ['type' => ObjectModel::TYPE_STRING, 'size' => 32, 'required' => false],
                'perm_group' => ['type' => ObjectModel::TYPE_STRING, 'size' => 80, 'required' => true],
                'permission' => ['type' => ObjectModel::TYPE_STRING, 'size' => 80, 'required' => true],
                'level'      => ['type' => ObjectModel::TYPE_STRING, 'size' => 80, 'required' => true],
            ],
            'keys' => [
                'profile_permission' => [
                    'perm' => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['id_profile', 'perm_group', 'permission']],
                ],
            ],
        ];

        $redisServers = [
            'table' => 'redis_servers',
            'primary' => 'id_redis_server',
            'fields' => [
                'ip'              => ['type' => ObjectModel::TYPE_STRING, 'size' => 46, 'required' => true],
                'port'            => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'auth'            => ['type' => ObjectModel::TYPE_STRING, 'size' => ObjectModel::SIZE_TEXT],
                'db'              => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
        ];

        $referrerCache = [
            'table' => 'referrer_cache',
            'fields' => [
                'id_connections_source' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_referrer'           => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'referrer_cache' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_connections_source', 'id_referrer']],
                ],
            ],
        ];

        $requiredField = [
            'table' => 'required_field',
            'primary' => 'id_required_field',
            'primaryKeyDbType' => 'int(11)',
            'fields' => [
                'object_name'       => ['type' => ObjectModel::TYPE_STRING, 'size' => 32, 'required' => true],
                'field_name'        => ['type' => ObjectModel::TYPE_STRING, 'size' => 32, 'required' => true],
            ],
            'keys' => [
                'required_field' => [
                    'object_name' => ['type' => ObjectModel::KEY, 'columns' => ['object_name']],
                ],
            ],
        ];

        $sceneCategory = [
            'table' => 'scene_category',
            'fields' => [
                'id_scene'    => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_category' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'scene_category' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_scene', 'id_category']],
                ],
            ],
        ];

        $sceneProducts = [
            'table' => 'scene_products',
            'fields' => [
                'id_scene'    => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_product'  => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'x_axis'      => ['type' => ObjectModel::TYPE_INT, 'size' => 4, 'dbType' => 'int(4)', 'required' => true],
                'y_axis'      => ['type' => ObjectModel::TYPE_INT, 'size' => 4, 'dbType' => 'int(4)', 'required' => true],
                'zone_width'  => ['type' => ObjectModel::TYPE_INT, 'size' => 3, 'dbType' => 'int(3)', 'required' => true],
                'zone_height' => ['type' => ObjectModel::TYPE_INT, 'size' => 3, 'dbType' => 'int(3)', 'required' => true],
            ],
            'keys' => [
                'scene_products' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_scene', 'id_product', 'x_axis', 'y_axis']],
                ],
            ],
        ];

        $searchIndex = [
            'table' => 'search_index',
            'fields' => [
                'id_product' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_word'    => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'weight'     => ['type' => ObjectModel::TYPE_INT, 'size' => 4, 'dbType' => 'smallint(4) unsigned', 'default' => '1'],
            ],
            'keys' => [
                'search_index' => [
                    'primary'    => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_word', 'id_product']],
                    'id_product' => ['type' => ObjectModel::KEY, 'columns' => ['id_product']],
                ],
            ],
        ];

        $searchWord = [
            'table' => 'search_word',
            'primary' => 'id_word',
            'fields' => [
                'id_shop' => ['type' => ObjectModel::TYPE_INT, 'default' => '1', 'required' => true],
                'id_lang' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'word'    => ['type' => ObjectModel::TYPE_STRING, 'size' => 30, 'required' => true],
            ],
            'keys' => [
                'search_word' => [
                    'id_lang' => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['id_lang', 'id_shop', 'word']],
                ],
            ],
        ];

        $smartyCache = [
            'table' => 'smarty_cache',
            'fields' => [
                'id_smarty_cache' => ['type' => ObjectModel::TYPE_STRING, 'size' => 40, 'dbType' => 'char(40)', 'required' => true],
                'name'            => ['type' => ObjectModel::TYPE_STRING, 'size' => 40, 'dbType' => 'char(40)', 'required' => true],
                'cache_id'        => ['type' => ObjectModel::TYPE_STRING, 'size' => 64],
                'modified'        => ['type' => ObjectModel::TYPE_DATE, 'dbType' => 'timestamp', 'dbDefault' => 'CURRENT_TIMESTAMP'],
                'content'         => ['type' => ObjectModel::TYPE_STRING, 'size' => ObjectModel::SIZE_LONG_TEXT, 'required' => true],
            ],
            'keys' => [
                'smarty_cache' => [
                    'primary'  => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_smarty_cache']],
                    'cache_id' => ['type' => ObjectModel::KEY, 'columns' => ['cache_id']],
                    'modified' => ['type' => ObjectModel::KEY, 'columns' => ['modified']],
                    'name'     => ['type' => ObjectModel::KEY, 'columns' => ['name']],
                ],
            ],
        ];

        $smartyLastFlush = [
            'table' => 'smarty_last_flush',
            'fields' => [
                'type'       => ['type' => ObjectModel::TYPE_STRING, 'required' => true, 'values' => ['compile','template'], 'default' => 'compile'],
                'last_flush' => ['type' => ObjectModel::TYPE_DATE, 'default' => '1970-01-01 00:00:00'],
            ],
            'keys' => [
                'smarty_last_flush' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['type']],
                ],
            ],
        ];

        $smartyLazyCache = [
            'table' => 'smarty_lazy_cache',
            'fields' => [
                'template_hash' => ['type' => ObjectModel::TYPE_STRING, 'size' => 32, 'required' => true, 'dbDefault' => ''],
                'cache_id'      => ['type' => ObjectModel::TYPE_STRING, 'size' => 64, 'required' => true, 'dbDefault' => ''],
                'compile_id'    => ['type' => ObjectModel::TYPE_STRING, 'size' => 32, 'required' => true, 'dbDefault' => ''],
                'filepath'      => ['type' => ObjectModel::TYPE_STRING, 'required' => true, 'dbDefault' => ''],
                'last_update'   => ['type' => ObjectModel::TYPE_DATE, 'default' => '1970-01-01 00:00:00'],
            ],
            'keys' => [
                'smarty_lazy_cache' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['template_hash', 'cache_id', 'compile_id']],
                ],
            ],
        ];

        $specificPricePriority = [
            'table' => 'specific_price_priority',
            'primary' => 'id_specific_price_priority',
            'primaryKeyDbType' => 'int(11)',
            'fields' => [
                'id_product'                 => ['type' => ObjectModel::TYPE_INT, 'dbType' => 'int(11)', 'required' => true, 'unique' => true],
                'priority'                   => ['type' => ObjectModel::TYPE_STRING, 'size' => 80, 'required' => true],
            ],
            'keys' => [
                'specific_price_priority' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_specific_price_priority', 'id_product']],
                ]
            ]
        ];

        $specificPriceRuleCondition = [
            'table' => 'specific_price_rule_condition',
            'primary' => 'id_specific_price_rule_condition',
            'fields' => [
                'id_specific_price_rule_condition_group' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'type'                                   => ['type' => ObjectModel::TYPE_STRING, 'required' => true],
                'value'                                  => ['type' => ObjectModel::TYPE_STRING, 'required' => true],
            ],
            'keys' => [
                'specific_price_rule_condition' => [
                    'id_specific_price_rule_condition_group' => ['type' => ObjectModel::KEY, 'columns' => ['id_specific_price_rule_condition_group']],
                ],
            ],
        ];

        $specificPriceRuleConditionGroup = [
            'table' => 'specific_price_rule_condition_group',
            'primary' => 'id_specific_price_rule_condition_group',
            'fields' => [
                'id_specific_price_rule'  => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'specific_price_rule_condition_group' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_specific_price_rule_condition_group', 'id_specific_price_rule']],
                ],
            ],
        ];

        $tagCount = [
            'table' => 'tag_count',
            'fields' => [
                'id_group' => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
                'id_tag'   => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
                'id_lang'  => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
                'id_shop'  => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
                'counter'  => ['type' => ObjectModel::TYPE_INT, 'required' => true, 'default' => '0'],
            ],
            'keys' => [
                'tag_count' => [
                    'primary'  => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_group', 'id_tag']],
                    'id_group' => ['type' => ObjectModel::KEY, 'columns' => ['id_group', 'id_lang', 'id_shop', 'counter']],
                ],
            ],
        ];

        $themeMeta = [
            'table' => 'theme_meta',
            'primary' => 'id_theme_meta',
            'primaryKeyDbType' => 'int(11)',
            'fields' => [
                'id_theme'      => ['type' => ObjectModel::TYPE_INT, 'dbType' => 'int(11)', 'required' => true],
                'id_meta'       => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'left_column'   => ['type' => ObjectModel::TYPE_BOOL, 'default' => '1', 'dbType' => 'tinyint(1)'],
                'right_column'  => ['type' => ObjectModel::TYPE_BOOL, 'default' => '1', 'dbType' => 'tinyint(1)'],
            ],
            'keys' => [
                'theme_meta' => [
                    'id_theme_2' => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['id_theme', 'id_meta']],
                    'id_meta'    => ['type' => ObjectModel::KEY, 'columns' => ['id_meta']],
                    'id_theme'   => ['type' => ObjectModel::KEY, 'columns' => ['id_theme']],
                ],
            ],
        ];

        $themeSpecific = [
            'table' => 'theme_specific',
            'fields' => [
                'id_theme'  => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_shop'   => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'entity'    => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_object' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'theme_specific' => [
                    'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_theme', 'id_shop', 'entity', 'id_object']],
                ],
            ],
        ];

        $timezone = [
            'table' => 'timezone',
            'primary' => 'id_timezone',
            'fields' => [
                'name'        => ['type' => ObjectModel::TYPE_STRING, 'size' => 32, 'required' => true],
            ],
        ];

        $warehouseCarrier = [
            'table' => 'warehouse_carrier',
            'fields' => [
                'id_carrier'   => ['type' => ObjectModel::TYPE_INT, 'required' => true],
                'id_warehouse' => ['type' => ObjectModel::TYPE_INT, 'required' => true],
            ],
            'keys' => [
                'warehouse_carrier' => [
                    'primary'      => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_warehouse', 'id_carrier']],
                    'id_carrier'   => ['type' => ObjectModel::KEY, 'columns' => ['id_carrier']],
                    'id_warehouse' => ['type' => ObjectModel::KEY, 'columns' => ['id_warehouse']],
                ],
            ],
        ];

        $webBrowser = [
            'table' => 'web_browser',
            'primary' => 'id_web_browser',
            'fields' => [
                'name'           => ['type' => ObjectModel::TYPE_STRING, 'size' => 64],
            ],
        ];

        $webservicePermission = [
            'table' => 'webservice_permission',
            'primary' => 'id_webservice_permission',
            'primaryKeyDbType' => 'int(11)',
            'fields' => [
                'resource'                 => ['type' => ObjectModel::TYPE_STRING, 'size' => 50, 'required' => true],
                'method'                   => ['type' => ObjectModel::TYPE_STRING, 'values' => ['GET','POST','PUT','DELETE','HEAD'], 'required' => true],
                'id_webservice_account'    => ['type' => ObjectModel::TYPE_INT, 'dbType' => 'int(11)', 'required' => true],
            ],
            'keys' => [
                'webservice_permission' => [
                    'resource_2'            => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['resource', 'method', 'id_webservice_account']],
                    'id_webservice_account' => ['type' => ObjectModel::KEY, 'columns' => ['id_webservice_account']],
                    'method'                => ['type' => ObjectModel::KEY, 'columns' => ['method']],
                    'resource'              => ['type' => ObjectModel::KEY, 'columns' => ['resource']],
                ],
            ],
        ];

        return [
            'Access' => $access,
            'Accessory' => $accessory,
            'AttributeImpact' => $attributeImpact,
            'CarrierGroup' => $carrierGroup,
            'CarrierTaxRulesGroupShop' => $carrierTaxRulesGroupShop,
            'CarrierZone' => $carrierZone,
            'CartCartRule' => $cartCartRule,
            'CartProduct' => $cartProduct,
            'CartRuleCarrier' => $cartRuleCarrier,
            'CartRuleCombination' => $cartRuleCombination,
            'CartRuleCountry' => $cartRuleCountry,
            'CartRuleGroup' => $cartRuleGroup,
            'CartRuleProductRule' => $cartRuleProductRule,
            'CartRuleProductRuleGroup' => $cartRuleProductRuleGroup,
            'CartRuleProductRuleValue' => $cartRuleProductRuleValue,
            'CartRuleShop' => $cartRuleShop,
            'CategoryGroup' => $categoryGroup,
            'CategoryProduct' => $categoryProduct,
            'CmsRoleLang' => $cmsRoleLang,
            'CompareProduct' => $compareProduct,
            'ConfigurationKpi' => $configurationKpi,
            'ConfigurationKpiLang' => $configurationKpiLang,
            'ConnectionsPage' => $connectionsPage,
            'CurrencyModule' => $currencyModule,
            'CustomerGroup' => $customerGroup,
            'CustomerMessageSyncImap' => $customerMessageSyncImap,
            'CustomizedData' => $customizedData,
            'EmployeeNotification' => $employeeNotification,
            'FeatureProduct' => $featureProduct,
            'HookAlias' => $hookAlias,
            'HookModule' => $hookModule,
            'HookModuleExceptions' => $hookModuleExceptions,
            'ImportMatch' => $importMatch,
            'MemcachedServers' => $memcachedServers,
            'MessageReaded' => $messageReaded,
            'Module' => $module,
            'ModuleAccess' => $moduleAccess,
            'ModuleCarrier' => $moduleCarrier,
            'ModuleCountry' => $moduleCountry,
            'ModuleCurrency' => $moduleCurrency,
            'ModuleGroup' => $moduleGroup,
            'ModulePreference' => $modulePreference,
            'ModuleShop' => $moduleShop,
            'ModulesPerfs' => $modulesPerfs,
            'OperatingSystem' => $operatingSystem,
            'OrderDetailTax' => $orderDetailTax,
            'OrderInvoicePayment' => $orderInvoicePayment,
            'OrderInvoiceTax' => $orderInvoiceTax,
            'OrderReturnDetail' => $orderReturnDetail,
            'OrderSlipDetail' => $orderSlipDetail,
            'OrderSlipDetailTax' => $orderSlipDetailTax,
            'Pack' => $pack,
            'PageCache' => $pageCache,
            'PageType' => $pageType,
            'PageViewed' => $pageViewed,
            'ProductAttachment' => $productAttachment,
            'ProductAttributeCombination' => $productAttributeCombination,
            'ProductAttributeImage' => $productAttributeImage,
            'ProductCarrier' => $productCarrier,
            'ProductCountryTax' => $productCountryTax,
            'ProductGroupReductionCache' => $productGroupReductionCache,
            'ProductSale' => $productSale,
            'ProductTag' => $productTag,
            'ProfilePermission' => $profilePermission,
            'RedisServers' => $redisServers,
            'ReferrerCache' => $referrerCache,
            'RequiredField' => $requiredField,
            'SceneCategory' => $sceneCategory,
            'SceneProducts' => $sceneProducts,
            'SearchIndex' => $searchIndex,
            'SearchWord' => $searchWord,
            'SmartyCache' => $smartyCache,
            'SmartyLastFlush' => $smartyLastFlush,
            'SmartyLazyCache' => $smartyLazyCache,
            'SpecificPricePriority' => $specificPricePriority,
            'SpecificPriceRuleCondition' => $specificPriceRuleCondition,
            'SpecificPriceRuleConditionGroup' => $specificPriceRuleConditionGroup,
            'TagCount' => $tagCount,
            'ThemeMeta' => $themeMeta,
            'ThemeSpecific' => $themeSpecific,
            'Timezone' => $timezone,
            'WarehouseCarrier' => $warehouseCarrier,
            'WebBrowser' => $webBrowser,
            'WebservicePermission' => $webservicePermission,
        ];
    }
}

