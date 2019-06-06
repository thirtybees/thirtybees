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
class InstallControllerConsoleProcess extends InstallControllerConsole
{
    const SETTINGS_FILE = 'config/settings.inc.php';
    public $processSteps = [];
    public $previousButton = false;

    /** @var InstallModelInstall $modelInstall */
    public $modelInstall;

    /** @var InstallModelDatabase $modelDatabase */
    public $modelDatabase;

    public function init()
    {
        require_once _PS_INSTALL_MODELS_PATH_.'install.php';
        require_once _PS_INSTALL_MODELS_PATH_.'database.php';
        $this->modelInstall = new InstallModelInstall();
        $this->modelDatabase = new InstallModelDatabase();
    }

    /**
     * @see InstallAbstractModel::processNextStep()
     */
    public function processNextStep()
    {
    }

    /**
     * @see InstallAbstractModel::validate()
     */
    public function validate()
    {
        return false;
    }

    public function process()
    {
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

        if ($this->datas->sendEmail) {
            if (!$this->processSendEmail()) {
                $this->printErrors();
            }
        }
    }

    /**
     * PROCESS : generateSettingsFile
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
     */
    public function processInstallDatabase()
    {
        return $this->modelInstall->installDatabase($this->datas->databaseClear);
    }

    /**
     * PROCESS : installDefaultData
     * Create default shop and languages
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

    public function initializeContext()
    {
        global $smarty;

        // Clean all cache values
        Cache::clean('*');

        Context::getContext()->shop = new Shop(1);
        Shop::setContext(Shop::CONTEXT_SHOP, 1);
        Configuration::loadConfiguration();
        if (!isset(Context::getContext()->language) || !Validate::isLoadedObject(Context::getContext()->language)) {
            if ($idLang = (int) Configuration::get('PS_LANG_DEFAULT')) {
                Context::getContext()->language = new Language($idLang);
            }
        }
        if (!isset(Context::getContext()->country) || !Validate::isLoadedObject(Context::getContext()->country)) {
            if ($idCountry = (int) Configuration::get('PS_COUNTRY_DEFAULT')) {
                Context::getContext()->country = new Country((int) $idCountry);
            }
        }
        if (!isset(Context::getContext()->currency) || !Validate::isLoadedObject(Context::getContext()->currency)) {
            if ($idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT')) {
                Context::getContext()->currency = new Currency((int) $idCurrency);
            }
        }

        Context::getContext()->cart = new Cart();
        Context::getContext()->employee = new Employee(1);
        if (!defined('_PS_SMARTY_FAST_LOAD_')) {
            define('_PS_SMARTY_FAST_LOAD_', true);
        }
        require_once _PS_ROOT_DIR_.'/config/smarty.config.inc.php';

        Context::getContext()->smarty = $smarty;
    }

    /**
     * PROCESS : populateDatabase
     * Populate database with default data
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
                'useSmtp'                => false,
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
     */
    public function processInstallFixtures()
    {
        $this->initializeContext();

        $result = $this->modelInstall->installFixtures(null, ['shopActivity' => $this->datas->shopActivity, 'shopCountry' => $this->datas->shopCountry]);

        return $result;
    }

    /**
     * PROCESS : installModules
     * Install all modules in ~/modules/ directory
     */
    public function processInstallModules()
    {
        $this->initializeContext();

        return $this->modelInstall->installModules();
    }

    /**
     * PROCESS : installTheme
     * Install theme
     */
    public function processInstallTheme()
    {
        $this->initializeContext();

        return $this->modelInstall->installTheme();
    }

    /**
     * PROCESS : sendEmail
     * Send information e-mail
     */
    public function processSendEmail()
    {
        require_once _PS_INSTALL_MODELS_PATH_.'mail.php';
        $mail = new InstallModelMail(
            false,
            $this->datas->smtpServer,
            $this->datas->smtpLogin,
            $this->datas->smtpPassword,
            $this->datas->smtpPort,
            $this->datas->smtpEncryption,
            $this->datas->adminEmail
        );

        if (file_exists(_PS_INSTALL_LANGS_PATH_.$this->language->getLanguageIso().'/mail_identifiers.txt')) {
            $content = file_get_contents(_PS_INSTALL_LANGS_PATH_.$this->language->getLanguageIso().'/mail_identifiers.txt');
        } else {
            $content = file_get_contents(_PS_INSTALL_LANGS_PATH_.InstallLanguages::DEFAULT_ISO.'/mail_identifiers.txt');
        }

        $vars = [
            '{firstname}' => $this->datas->adminFirstname,
            '{lastname}'  => $this->datas->adminLastname,
            '{shop_name}' => $this->datas->shopName,
            '{passwd}'    => $this->datas->adminPassword,
            '{email}'     => $this->datas->adminEmail,
            '{shop_url}'  => Tools::getHttpHost(true).__PS_BASE_URI__,
        ];
        $content = str_replace(array_keys($vars), array_values($vars), $content);

        $mail->send(
            $this->l('%s Login information', $this->datas->shopName),
            $content
        );

        return true;
    }
}
