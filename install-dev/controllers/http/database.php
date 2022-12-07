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
 * Step 3 : configure database and email connection
 */
class InstallControllerHttpDatabase extends InstallControllerHttp
{
    /**
     * @var InstallModelDatabase
     */
    public $modelDatabase;

    /**
     * @var string
     */
    public $databaseServer;

    /**
     * @var string
     */
    public $databaseName;

    /**
     * @var string
     */
    public $databaseLogin;

    /**
     * @var string
     */
    public $databasePassword;

    /**
     * @var string
     */
    public $databasePrefix;

    /**
     * @var bool
     */
    public $databaseClear;

    /**
     * @return void
     */
    public function init()
    {
        require_once _PS_INSTALL_MODELS_PATH_.'database.php';
        $this->modelDatabase = new InstallModelDatabase();
    }

    /**
     * @see InstallAbstractModel::processNextStep()
     */
    public function processNextStep()
    {
        // Save database config
        $this->session->databaseServer = trim(Tools::getValue('dbServer'));
        $this->session->databaseName = trim(Tools::getValue('dbName'));
        $this->session->databaseLogin = trim(Tools::getValue('dbLogin'));
        $this->session->databasePassword = trim(Tools::getValue('dbPassword'));
        $this->session->databasePrefix = trim(Tools::getValue('db_prefix'));
        $this->session->databaseClear = Tools::getValue('database_clear');
        $this->session->rewriteEngine = Tools::getValue('rewrite_engine');
    }

    /**
     * Database configuration must be valid to validate this step
     *
     * @see InstallAbstractModel::validate()
     */
    public function validate()
    {
        $this->errors = $this->modelDatabase->testDatabaseSettings(
            $this->session->databaseServer,
            $this->session->databaseName,
            $this->session->databaseLogin,
            $this->session->databasePassword,
            $this->session->databasePrefix,
            // We do not want to validate table prefix if we are already in install process
            ($this->session->step == 'process') ? true : $this->session->databaseClear
        );
        if (count($this->errors)) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     */
    public function process()
    {
        if (Tools::getValue('checkDb')) {
            $this->processCheckDb();
        } elseif (Tools::getValue('createDb')) {
            $this->processCreateDb();
        }
    }

    /**
     * Check if a connection to database is possible with these data
     *
     * @return void
     */
    public function processCheckDb()
    {
        $server = Tools::getValue('dbServer');
        $database = Tools::getValue('dbName');
        $login = Tools::getValue('dbLogin');
        $password = Tools::getValue('dbPassword');
        $prefix = Tools::getValue('db_prefix');
        $clear = Tools::getValue('clear');

        $errors = $this->modelDatabase->testDatabaseSettings($server, $database, $login, $password, $prefix, $clear);

        $this->ajaxJsonAnswer(
            (count($errors)) ? false : true,
            (count($errors)) ? implode('<br />', $errors) : $this->l('Database is connected')
        );
    }

    /**
     * Attempt to create the database
     *
     * @return void
     */
    public function processCreateDb()
    {
        $server = Tools::getValue('dbServer');
        $database = Tools::getValue('dbName');
        $login = Tools::getValue('dbLogin');
        $password = Tools::getValue('dbPassword');

        $success = $this->modelDatabase->createDatabase($server, $database, $login, $password);

        $this->ajaxJsonAnswer(
            $success,
            $success ? $this->l('Database is created') : $this->l('Cannot create the database automatically')
        );
    }

    /**
     * @see InstallAbstractModel::display()
     * @throws PrestashopInstallerException
     */
    public function display()
    {
        if (!$this->session->databaseServer) {
            if (file_exists(_PS_ROOT_DIR_.'/config/settings.inc.php')) {
                @include_once _PS_ROOT_DIR_.'/config/settings.inc.php';
                $this->databaseServer = _DB_SERVER_;
                $this->databaseName = _DB_NAME_;
                $this->databaseLogin = _DB_USER_;
                $this->databasePassword = _DB_PASSWD_;
                $this->databasePrefix = _DB_PREFIX_;
            } else {
                $this->databaseServer = 'localhost';
                $this->databaseName = 'thirtybees';
                $this->databaseLogin = 'root';
                $this->databasePassword = '';
                $this->databasePrefix = 'tb_';
            }

            $this->databaseClear = true;
        } else {
            $this->databaseServer = $this->session->databaseServer;
            $this->databaseName = $this->session->databaseName;
            $this->databaseLogin = $this->session->databaseLogin;
            $this->databasePassword = $this->session->databasePassword;
            $this->databasePrefix = $this->session->databasePrefix;
            $this->databaseClear = $this->session->databaseClear;
        }

        $this->displayTemplate('database');
    }
}
