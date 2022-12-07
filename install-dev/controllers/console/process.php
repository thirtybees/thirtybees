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
class InstallControllerConsoleProcess
{
    const SETTINGS_FILE = 'config/settings.inc.php';

    /**
     * @var InstallLanguages
     */
    public $language;

    /**
     * @var Datas $datas
     */
    public $datas;

    /**
     * @var InstallModelInstall $modelInstall
     */
    public $modelInstall;

    /**
     * @var InstallModelDatabase $modelDatabase
     */
    public $modelDatabase;

    /**
     * @param Datas $datas
     *
     * @throws PrestashopInstallerException
     */
    public function __construct(Datas $datas)
    {

        $this->datas = $datas;

        // Set current language
        $this->language = InstallLanguages::getInstance();
        if (!$this->datas->language) {
            die('No language defined');
        }
        $this->language->setLanguage($this->datas->language);
        $this->modelInstall = new InstallModelInstall();
        $this->modelDatabase = new InstallModelDatabase();
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws PrestashopInstallerException
     */
    public function process()
    {
        $_SERVER['HTTP_HOST'] = $this->datas->httpHost;
        @date_default_timezone_set($this->datas->timezone);

        $steps = explode(',', $this->datas->step);
        if (in_array('all', $steps)) {
            $steps = ['database', 'fixtures', 'modules', 'theme'];
        }

        if (in_array('database', $steps)) {
            if (!$this->processGenerateSettingsFile()) {
                $this->printErrors();
            }

            if ($this->datas->databaseCreate) {
                $this->modelDatabase->createDatabase($this->datas->databaseServer, $this->datas->databaseName, $this->datas->databaseLogin, $this->datas->databasePassword);
            }

            if (!$this->modelDatabase->testDatabaseSettings($this->datas->databaseServer, $this->datas->databaseName, $this->datas->databaseLogin, $this->datas->databasePassword, $this->datas->databasePrefix, $this->datas->databaseClear)) {
                $this->printErrors();
            }
            if (!$this->processInstallDatabase()) {
                $this->printErrors();
            }
            if (!$this->processInstallDefaultData()) {
                $this->printErrors();
            }
            if (!$this->processPopulateDatabase()) {
                $this->printErrors();
            }
            if (!$this->processConfigureShop()) {
                $this->printErrors();
            }
        }

        if (in_array('fixtures', $steps)) {
            if (!$this->processInstallFixtures()) {
                $this->printErrors();
            }
            if (!$this->processInitializeClasses()) {
                $this->printErrors();
            }
        }

        if (in_array('modules', $steps)) {
            if (!$this->processInstallModules()) {
                $this->printErrors();
            }
        }

        if (in_array('theme', $steps)) {
            if (!$this->processInstallTheme()) {
                $this->printErrors();
            }
        }
    }

    /**
     * PROCESS : generateSettingsFile
     *
     * @return bool
     *
     * @throws PrestashopInstallerException
     */
    public function processGenerateSettingsFile()
    {
        return $this->modelInstall->generateSettingsFile(
            $this->datas->databaseServer,
            $this->datas->databaseLogin,
            $this->datas->databasePassword,
            $this->datas->databaseName,
            $this->datas->databasePrefix
        );
    }

    /**
     * PROCESS : installDatabase
     * Create database structure
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function processInstallDatabase()
    {
        return $this->modelInstall->installDatabase($this->datas->databaseClear);
    }

    /**
     * PROCESS : installDefaultData
     * Create default shop and languages
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processInstallDefaultData()
    {
        $this->initializeContext();
        if (!$res = $this->modelInstall->installDefaultData($this->datas->shopName, $this->datas->shopCountry, (int) $this->datas->allLanguages, true)) {
            return false;
        }

        if ($this->datas->baseUri != '/') {
            $shopUrl = new ShopUrl(1);
            $shopUrl->physical_uri = $this->datas->baseUri;
            $shopUrl->save();
        }

        return $res;
    }

    /**
     * Reset context
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initializeContext()
    {
        // Clean all cache values
        Cache::clean('*');


        $context = Context::getContext();

        // load configuration
        $context->shop = new Shop(1);
        Shop::setContext(Shop::CONTEXT_SHOP, 1);
        Configuration::loadConfiguration();

        if (!isset($context->language) || !Validate::isLoadedObject($context->language)) {
            if ($idLang = (int) Configuration::get('PS_LANG_DEFAULT')) {
                $context->language = new Language($idLang);
            }
        }

        if (!isset($context->country) || !Validate::isLoadedObject($context->country)) {
            if ($idCountry = (int) Configuration::get('PS_COUNTRY_DEFAULT')) {
                $context->country = new Country((int) $idCountry);
            }
        }

        if (!isset($context->currency) || !Validate::isLoadedObject($context->currency)) {
            if ($idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT')) {
                $context->currency = new Currency((int) $idCurrency);
            }
        }

        $context->cart = new Cart();
        $context->employee = new Employee(1);

        if (! isset($context->smarty)) {
            $context->smarty = require_once(_PS_ROOT_DIR_.'/config/smarty.config.inc.php');
        }

        if (! isset($context->link)) {
            $protocol = (Tools::usingSecureMode() && Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
            $context->link = new Link($protocol, $protocol);
        }
    }

    /**
     * PROCESS : populateDatabase
     * Populate database with default data
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processPopulateDatabase()
    {
        $this->initializeContext();

        $result = $this->modelInstall->populateDatabase();

        return $result;
    }

    /**
     * PROCESS : configureShop
     * Set default shop configuration
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processConfigureShop()
    {
        $this->initializeContext();

        return $this->modelInstall->configureShop(
            [
                'shopName'               => $this->datas->shopName,
                'shopActivity'           => $this->datas->shopActivity,
                'shopCountry'            => $this->datas->shopCountry,
                'shopTimezone'           => $this->datas->timezone,
                'adminFirstname'         => $this->datas->adminFirstname,
                'adminLastname'          => $this->datas->adminLastname,
                'adminPassword'          => $this->datas->adminPassword,
                'adminEmail'             => $this->datas->adminEmail,
                'configurationAgreement' => true,
                'sendInformations'       => true,
            ]
        );
    }

    /**
     * PROCESS : installFixtures
     * Install fixtures (E.g. demo products)
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processInstallFixtures()
    {
        $this->initializeContext();

        $result = $this->modelInstall->installFixtures(null, ['shopActivity' => $this->datas->shopActivity, 'shopCountry' => $this->datas->shopCountry]);

        return $result;
    }

    /**
     * PROCESS : initializeClasses
     * Executes initialization callbacks on all classes that implements the interface
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processInitializeClasses()
    {
        $this->initializeContext();

        return $this->modelInstall->initializeClasses();
    }

    /**
     * PROCESS : installModules
     * Install all modules in ~/modules/ directory
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processInstallModules()
    {
        $this->initializeContext();

        return $this->modelInstall->installModules();
    }

    /**
     * PROCESS : installTheme
     * Install theme
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processInstallTheme()
    {
        $this->initializeContext();

        return $this->modelInstall->installTheme();
    }

    /**
     * @since 1.0.0
     */
    public function printErrors()
    {
        $errors = $this->modelInstall->getErrors();
        if (count($errors)) {
            if (!is_array($errors)) {
                $errors = [$errors];
            }
            echo 'Errors :' . "\n";
            foreach ($errors as $errorProcess) {
                foreach ($errorProcess as $error) {
                    echo (is_string($error) ? $error : print_r($error, true)) . "\n";
                }
            }
            die;
        }
    }

    /**
     * Get translated string
     *
     * @param string $str String to translate
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function l($str)
    {
        $args = func_get_args();
        return call_user_func_array([$this->language, 'l'], $args);
    }
}
