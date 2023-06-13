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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class ImportModuleCore
 *
 *
 * @deprecated 1.0.2 Even though this class still exists in thirty bees, it cannot be used in the
 *             same way as on PrestaShop 1.6, because thirty bees does not support the older
 *             mysql/mysqli ways of connecting with the database. Everything is modernized to
 *             support only the PDO mysql PHP extension. If your module does extend this class
 *             make sure you refactor everything to directly use the `Db` class instead of the
 *             methods of this class.
 */
abstract class ImportModuleCore extends Module
{
    /**
     * @var mixed
     */
    protected $_link = null;

    /**
     * @var mixed
     */
    public $server;

    /**
     * @var mixed
     */
    public $user;

    /**
     * @var mixed
     */
    public $passwd;

    /**
     * @var mixed
     */
    public $database;

    /**
     * @var string Prefix database
     */
    public $prefix;

    /**
     * ImportModule destructor
     */
    public function __destruct()
    {
    }

    /**
     * @return PDO
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function initDatabaseConnection()
    {
        return Db::getInstance()->getLink();
    }

    /**
     * @param string|DbQuery $query
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function executeS($query)
    {
        return Db::readOnly()->getArray($query);
    }

    /**
     * @param string|DbQuery $query
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     *        supports MySQL PDO.
     */
    public function execute($query)
    {
        return (bool) Db::getInstance()->execute($query);
    }

    /**
     * @param string|DbQuery $query
     *
     * @return int|mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getValue($query)
    {
        $this->initDatabaseConnection();
        $result = $this->executeS($query);
        if (!count($result)) {
            return 0;
        } else {
            return array_shift($result[0]);
        }
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getImportModulesOnDisk()
    {
        $modules = Module::getModulesOnDisk(true);
        foreach ($modules as $key => $module) {
            if (!isset($module->parent_class) || $module->parent_class != 'ImportModule') {
                unset($modules[$key]);
            }
        }

        return $modules;
    }

    /**
     * @return int
     */
    abstract public function getDefaultIdLang();
}
