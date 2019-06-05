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
 * Class InstallControllerHttpProcess
 *
 * @since 1.0.0
 */
class InstallControllerHttpProcess extends InstallControllerHttp
{
    const SETTINGS_FILE = 'config/settings.inc.php';

    public $processSteps = [];

    public $previousButton = false;

    /** @var InstallModelInstall $modelInstall */
    public $modelInstall;

    /** @var InstallSession $session */
    public $session;

    /**
     * @since 1.0.0
     */
    public function init()
    {
        require_once _PS_INSTALL_MODELS_PATH_.'install.php';
        $this->modelInstall = new InstallModelInstall();
    }

    /**
     * @since 1.0.0
     */
    public function processNextStep()
    {
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate()
    {
        return false;
    }

    /**
     * @since 1.0.0
     */
    public function process()
    {
        if (file_exists(_PS_ROOT_DIR_.'/'.self::SETTINGS_FILE)) {
            require_once _PS_ROOT_DIR_.'/'.self::SETTINGS_FILE;
        }

        if (!$this->session->processValidated) {
            $this->session->processValidated = [];
        }

        if (Tools::getValue('generateSettingsFile')) {
            $this->processGenerateSettingsFile();
        } elseif (Tools::getValue('installDatabase') && !empty($this->session->processValidated['generateSettingsFile'])) {
            $this->processInstallDatabase();
        } elseif (Tools::getValue('installDefaultData')) {
            $this->processInstallDefaultData();
        } elseif (Tools::getValue('populateDatabase') && !empty($this->session->processValidated['installDatabase'])) {
            $this->processPopulateDatabase();
        } elseif (Tools::getValue('configureShop') && !empty($this->session->processValidated['populateDatabase'])) {
            $this->processConfigureShop();
        } elseif (Tools::getValue('installFixtures') && !empty($this->session->processValidated['configureShop'])) {
            $this->processInstallFixtures();
        } elseif (Tools::getValue('installModules') && (!empty($this->session->processValidated['installFixtures']) || $this->session->installType != 'full')) {
            $this->processInstallModules();
        } elseif (Tools::getValue('installTheme')) {
            $this->processInstallTheme();
        } elseif (Tools::getValue('sendEmail') && !empty($this->session->processValidated['installTheme'])) {
            $this->processSendEmail();
        } else {
            // With no parameters, we consider that we are doing a new install, so session where the last process step
            // was stored can be cleaned
            if (Tools::getValue('restart')) {
                $this->session->processValidated = [];
                $this->session->databaseClear = true;
            } elseif (!Tools::getValue('submitNext')) {
                $this->session->step = 'configure';
                $this->session->lastStep = 'configure';
                Tools::redirect('index.php');
            }
        }
    }

    /**
     * PROCESS : generateSettingsFile
     *
     * @since 1.0.0
     */
    public function processGenerateSettingsFile()
    {
        $success = $this->modelInstall->generateSettingsFile(
            $this->session->databaseServer,
            $this->session->databaseLogin,
            $this->session->databasePassword,
            $this->session->databaseName,
            $this->session->databasePrefix
        );

        if (!$success) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }
        $this->session->processValidated = array_merge($this->session->processValidated, ['generateSettingsFile' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * PROCESS : installDatabase
     * Create database structure
     */
    public function processInstallDatabase()
    {
        if (!$this->modelInstall->installDatabase($this->session->databaseClear)) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }
        $this->session->processValidated = array_merge($this->session->processValidated, ['installDatabase' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * PROCESS : installDefaultData
     * Create default shop and languages
     */
    public function processInstallDefaultData()
    {
        $result = $this->modelInstall->installDefaultData($this->session->shopName, $this->session->shopCountry, false, true);

        if (!$result) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * PROCESS : populateDatabase
     * Populate database with default data
     */
    public function processPopulateDatabase()
    {
        $this->initializeContext();

        $this->modelInstall->xmlLoaderIds = $this->session->xmlLoaderIds;
        $result = $this->modelInstall->populateDatabase(Tools::getValue('entity'));
        if (!$result) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }
        $this->session->xmlLoaderIds = $this->modelInstall->xmlLoaderIds;
        $this->session->processValidated = array_merge($this->session->processValidated, ['populateDatabase' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    public function initializeContext()
    {
        global $smarty;

        Context::getContext()->shop = new Shop(1);
        Shop::setContext(Shop::CONTEXT_SHOP, 1);
        Configuration::loadConfiguration();
        Context::getContext()->language = new Language(Configuration::get('PS_LANG_DEFAULT'));
        Context::getContext()->country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'));
        Context::getContext()->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        Context::getContext()->cart = new Cart();
        Context::getContext()->employee = new Employee(1);
        define('_PS_SMARTY_FAST_LOAD_', true);
        require_once _PS_ROOT_DIR_.'/config/smarty.config.inc.php';

        Context::getContext()->smarty = $smarty;
    }

    /**
     * PROCESS : configureShop
     * Set default shop configuration
     */
    public function processConfigureShop()
    {
        $this->initializeContext();

        $success = $this->modelInstall->configureShop(
            [
                'shopName'               => $this->session->shopName,
                'shopActivity'           => $this->session->shopActivity,
                'shopCountry'            => $this->session->shopCountry,
                'shopTimezone'           => $this->session->shopTimezone,
                'adminFirstname'         => $this->session->adminFirstname,
                'adminLastname'          => $this->session->adminLastname,
                'adminPassword'          => $this->session->adminPassword,
                'adminEmail'             => $this->session->adminEmail,
                'sendInformations'       => $this->session->sendInformations,
                'configurationAgreement' => $this->session->configurationAgreement,
                'rewriteEngine'          => $this->session->rewriteEngine,
            ]
        );

        if (!$success) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }

        $this->session->processValidated = array_merge($this->session->processValidated, ['configureShop' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * PROCESS : installFixtures
     * Install fixtures (E.g. demo products)
     */
    public function processInstallFixtures()
    {
        $this->initializeContext();

        $this->modelInstall->xmlLoaderIds = $this->session->xmlLoaderIds;
        if (!$this->modelInstall->installFixtures(Tools::getValue('entity', null), ['shopActivity' => $this->session->shopActivity, 'shopCountry' => $this->session->shopCountry])) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }
        $this->session->xmlLoaderIds = $this->modelInstall->xmlLoaderIds;
        $this->session->processValidated = array_merge($this->session->processValidated, ['installFixtures' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * PROCESS : installModules
     * Install all modules in ~/modules/ directory
     */
    public function processInstallModules()
    {
        $this->initializeContext();

        $result = $this->modelInstall->installModules(Tools::getValue('module'));
        if (!$result) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }
        $this->session->processValidated = array_merge($this->session->processValidated, ['installModules' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * PROCESS : installTheme
     * Install theme
     */
    public function processInstallTheme()
    {
        $this->initializeContext();

        $result = $this->modelInstall->installTheme();
        if (!$result) {
            $this->ajaxJsonAnswer(false, $this->modelInstall->getErrors());
        }

        $this->session->processValidated = array_merge($this->session->processValidated, ['installTheme' => true]);
        $this->ajaxJsonAnswer(true, $this->modelInstall->getErrors());
    }

    /**
     * @see InstallAbstractModel::display()
     */
    public function display()
    {
        // We fill the process step used for Ajax queries
        $this->processSteps[] = [
            'key'   => 'generateSettingsFile',
            'lang'  => $this->l('Create settings.inc file'),
        ];

        $this->processSteps[] = [
            'key'   => 'installDatabase',
            'lang'  => $this->l('Create database tables'),
        ];

        $this->processSteps[] = [
            'key'   => 'installDefaultData',
            'lang'  => $this->l('Create default shop and languages'),
        ];

        $populateStep = [
            'key'       => 'populateDatabase',
            'lang'      => $this->l('Populate database tables'),
            'subtasks'  => [],
        ];
        $xmlLoader = new InstallXmlLoader();
        foreach (array_chunk($xmlLoader->getSortedEntities(), 10) as $entity) {
            $populateStep['subtasks'][] = ['entity' => $entity];
        }
        $this->processSteps[] = $populateStep;

        $this->processSteps[] = [
            'key'   => 'configureShop',
            'lang'  => $this->l('Configure shop information'),
        ];

        if ($this->session->installType == 'full') {
            $fixturesStep = [
                'key'       => 'installFixtures',
                'lang'      => $this->l('Install demonstration data'),
                'subtasks'  => [],
            ];
            $xmlLoader = new InstallXmlLoader();
            $xmlLoader->setFixturesPath();
            foreach (array_chunk($xmlLoader->getSortedEntities(), 10) as $entity) {
                $fixturesStep['subtasks'][] = ['entity' => $entity];
            }
            $this->processSteps[] = $fixturesStep;
        }

        $installModules = [
            'key'       => 'installModules',
            'lang'      => $this->l('Install modules'),
            'subtasks'  => [],
        ];
        foreach (array_chunk($this->modelInstall->getModulesList(), 5) as $module) {
            $installModules['subtasks'][] = ['module' => $module];
        }
        $this->processSteps[] = $installModules;

        $this->processSteps[] = [
            'key'   => 'installTheme',
            'lang'  => $this->l('Install theme'),
        ];

        $this->displayTemplate('process');
    }
}
