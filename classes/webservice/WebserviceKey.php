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
 * Class WebserviceKeyCore
 *
 * @since   1.0.0
 */
class WebserviceKeyCore extends ObjectModel
{
    /** @var string Key */
    public $key;

    /** @var bool Webservice Account statuts */
    public $active = true;

    /** @var string Webservice Account description */
    public $description;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'webservice_account',
        'primary' => 'id_webservice_account',
        'fields'  => [
            'active'      => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                 ],
            'key'         => ['type' => self::TYPE_STRING,                        'required' => true, 'size' => 32],
            'description' => ['type' => self::TYPE_STRING                                                         ],
        ],
    ];

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (WebserviceKey::keyExists($this->key)) {
            return false;
        }

        return parent::add($autoDate = true, $nullValues = false);
    }

    /**
     * @param $key
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function keyExists($key)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
		SELECT `key`
		FROM '._DB_PREFIX_.'webservice_account
		WHERE `key` = "'.pSQL($key).'"'
        );
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        return (parent::delete() && ($this->deleteAssociations() !== false));
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function deleteAssociations()
    {
        return Db::getInstance()->delete('webservice_permission', 'id_webservice_account = '.(int) $this->id);
    }

    /**
     * @param $authKey
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param $authKey
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isKeyActive($authKey)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
		SELECT active
		FROM `'._DB_PREFIX_.'webservice_account`
		WHERE `key` = "'.pSQL($authKey).'"'
        );
    }

    /**
     * @param $authKey
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getClassFromKey($authKey)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
		SELECT class_name
		FROM `'._DB_PREFIX_.'webservice_account`
		WHERE `key` = "'.pSQL($authKey).'"'
        );
    }

    /**
     * @param $idAccount
     * @param $permissionsToSet
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
}
