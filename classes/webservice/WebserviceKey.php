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

use Thirtybees\Core\InitializationCallback;

/**
 * Class WebserviceKeyCore
 */
class WebserviceKeyCore extends ObjectModel implements InitializationCallback
{
    /** @var string Key */
    public $key;

    /** @var bool Webservice Account statuts */
    public $active = true;

    /** @var string Webservice Account description */
    public $description;

    /** @var string php class to handle web request. Default WebserviceRequest */
    public $class_name;

    /** @var boolean is this created by external module */
    public $is_module;

    /** @var string module name - webservice provider*/
    public $module_name;

    /** @var int context employee id */
    public $context_employee_id;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'webservice_account',
        'primary' => 'id_webservice_account',
        'primaryKeyDbType' => 'int(11)',
        'fields'  => [
            'key'                 => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 32],
            'description'         => ['type' => self::TYPE_STRING, 'size' => ObjectModel::SIZE_TEXT],
            'class_name'          => ['type' => self::TYPE_STRING, 'size' => 50, 'default' => 'WebserviceRequest'],
            'is_module'           => ['type' => self::TYPE_BOOL, 'dbType' => 'tinyint(2)', 'dbDefault' => '0'],
            'module_name'         => ['type' => self::TYPE_STRING, 'size' => 50],
            'active'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(2)', 'dbNullable' => false],
            'context_employee_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
        ],
        'keys' => [
            'webservice_account' => [
                'key' => ['type' => ObjectModel::KEY, 'columns' => ['key']],
            ],
            'webservice_account_shop' => [
                'id_shop' => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
            ],
        ],
    ];

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (WebserviceKey::keyExists($this->key)) {
            return false;
        }

        return parent::add($autoDate = true, $nullValues = false);
    }

    /**
     * Returns WebserviceKey instance associated with key $key
     *
     * @param string $key
     * @return static | null
     * @throws PrestaShopException
     */
    public static function getInstanceByKey($key)
    {
        static $cache = [];
        if (! $key) {
            return null;
        }
        if (! array_key_exists($key, $cache)) {
            $query = (new DbQuery())
                ->select('id_webservice_account')
                ->from('webservice_account')
                ->where('`key` = "' . pSQL($key) . '"');
            $connection = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $id = (int)$connection->getValue($query);
            if ($id) {
                $cache[$key] = new static($id);
            } else {
                $cache[$key] = null;
            }
        }
        return $cache[$key];
    }

    /**
     * @param string $key
     *
     * @return boolean
     *
     * @throws PrestaShopException
     */
    public static function keyExists($key)
    {
        return Validate::isLoadedObject(static::getInstanceByKey($key));
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete()
    {
        return (parent::delete() && ($this->deleteAssociations() !== false));
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteAssociations()
    {
        return Db::getInstance()->delete('webservice_permission', 'id_webservice_account = '.(int) $this->id);
    }

    /**
     * @param string $authKey
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getPermissionForAccount($authKey)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT p.*
			FROM `'._DB_PREFIX_.'webservice_permission` p
			LEFT JOIN `'._DB_PREFIX_.'webservice_account` a ON (a.id_webservice_account = p.id_webservice_account)
			WHERE a.key = \''.pSQL($authKey).'\'
		'
        );
        $permissions = [];
        if ($result) {
            foreach ($result as $row) {
                $permissions[$row['resource']][] = $row['method'];
            }
        }

        return $permissions;
    }

    /**
     * @param string $authKey
     *
     * @return boolean
     *
     * @throws PrestaShopException
     */
    public static function isKeyActive($authKey)
    {
        $instance = static::getInstanceByKey($authKey);
        return Validate::isLoadedObject($instance)
            ? $instance->active
            : false;
    }

    /**
     * Returns class_name associated with webservice key
     *
     * @param string $authKey
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function getClassFromKey($authKey)
    {
        $instance = static::getInstanceByKey($authKey);
        return Validate::isLoadedObject($instance)
            ? $instance->class_name
            : null;
    }

    /**
     * @param int $idAccount
     * @param array|null $permissionsToSet
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function setPermissionForAccount($idAccount, $permissionsToSet)
    {
        $ok = true;
        $sql = 'DELETE FROM `'._DB_PREFIX_.'webservice_permission` WHERE `id_webservice_account` = '.(int) $idAccount;
        if (!Db::getInstance()->execute($sql)) {
            $ok = false;
        }
        if (isset($permissionsToSet)) {
            $permissions = [];
            $resources = WebserviceRequest::getResources();
            $methods = ['GET', 'PUT', 'POST', 'DELETE', 'HEAD'];
            foreach ($permissionsToSet as $resourceName => $resourceMethods) {
                if (in_array($resourceName, array_keys($resources))) {
                    foreach (array_keys($resourceMethods) as $methodName) {
                        if (in_array($methodName, $methods)) {
                            $permissions[] = [$methodName, $resourceName];
                        }
                    }
                }
            }
            $account = new WebserviceKey($idAccount);
            if ($account->deleteAssociations() && $permissions) {
                $sql = 'INSERT INTO `'._DB_PREFIX_.'webservice_permission` (`id_webservice_permission` ,`resource` ,`method` ,`id_webservice_account`) VALUES ';
                foreach ($permissions as $permission) {
                    $sql .= '(NULL , \''.pSQL($permission[1]).'\', \''.pSQL($permission[0]).'\', '.(int) $idAccount.'), ';
                }
                $sql = rtrim($sql, ', ');
                if (!Db::getInstance()->execute($sql)) {
                    $ok = false;
                }
            }
        }

        return $ok;
    }

    /**
     * Callback method to initialize class
     *
     * @param Db $conn
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function initializationCallback(Db $conn)
    {
        $employees = Employee::getEmployeesByProfile(_PS_ADMIN_PROFILE_);
        if ($employees && count($employees) > 0) {
            $employeeId = (int)$employees[0]['id_employee'];
            $conn->update(
                static::$definition['table'],
                ['context_employee_id' => $employeeId],
                'context_employee_id IS NULL OR context_employee_id = 0'
            );
        }
    }
}
