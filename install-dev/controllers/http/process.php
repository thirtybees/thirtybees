<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
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

    public $process_steps = [];

    public $previous_button = false;

    /** @var InstallModelInstall $model_install */
    protected $model_install;

    public function init()
    {
        require_once _PS_INSTALL_MODELS_PATH_.'install.php';
        $this->model_install = new InstallModelInstall();
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
        if (file_exists(_PS_ROOT_DIR_.'/'.self::SETTINGS_FILE)) {
            require_once _PS_ROOT_DIR_.'/'.self::SETTINGS_FILE;
        }

        if (!$this->session->process_validated) {
            $this->session->process_validated = [];
        }

        if (Tools::getValue('generateSettingsFile')) {
            $this->processGenerateSettingsFile();
        } elseif (Tools::getValue('installDatabase') && !empty($this->session->process_validated['generateSettingsFile'])) {
            $this->processInstallDatabase();
        } elseif (Tools::getValue('installDefaultData')) {
            $this->processInstallDefaultData();
        } elseif (Tools::getValue('populateDatabase') && !empty($this->session->process_validated['installDatabase'])) {
            $this->processPopulateDatabase();
        } elseif (Tools::getValue('configureShop') && !empty($this->session->process_validated['populateDatabase'])) {
            $this->processConfigureShop();
        } elseif (Tools::getValue('installFixtures') && !empty($this->session->process_validated['configureShop'])) {
            $this->processInstallFixtures();
        } elseif (Tools::getValue('installModules') && (!empty($this->session->process_validated['installFixtures']) || $this->session->install_type != 'full')) {
            $this->processInstallModules();
        } elseif (Tools::getValue('installTheme')) {
            $this->processInstallTheme();
        } elseif (Tools::getValue('sendEmail') && !empty($this->session->process_validated['installTheme'])) {
            $this->processSendEmail();
        } else {
            // With no parameters, we consider that we are doing a new install, so session where the last process step
            // was stored can be cleaned
            if (Tools::getValue('restart')) {
                $this->session->process_validated = [];
                $this->session->database_clear = true;
                if (Tools::getSafeModeStatus()) {
                    $this->session->safe_mode = true;
                }
            } elseif (!Tools::getValue('submitNext')) {
                $this->session->step = 'configure';
                $this->session->last_step = 'configure';
                Tools::redirect('index.php');
            }
        }
    }

    /**
     * PROCESS : generateSettingsFile
     */
    public function processGenerateSettingsFile()
    {
        $success = $this->model_install->generateSettingsFile(
            $this->session->database_server,
            $this->session->database_login,
            $this->session->database_password,
            $this->session->database_name,
            $this->session->database_prefix,
            $this->session->database_engine
        );

        if (!$success) {
            $this->ajaxJsonAnswer(false);
        }
        $this->session->process_validated = array_merge($this->session->process_validated, ['generateSettingsFile' => true]);
        $this->ajaxJsonAnswer(true);
    }

    /**
     * PROCESS : installDatabase
     * Create database structure
     */
    public function processInstallDatabase()
    {
        if (!$this->model_install->installDatabase($this->session->database_clear) || $this->model_install->getErrors()) {
            $this->ajaxJsonAnswer(false, $this->model_install->getErrors());
        }
        $this->session->process_validated = array_merge($this->session->process_validated, ['installDatabase' => true]);
        $this->ajaxJsonAnswer(true);
    }

    /**
     * PROCESS : installDefaultData
     * Create default shop and languages
     */
    public function processInstallDefaultData()
    {
        // @todo remove true in populateDatabase for 1.5.0 RC version
        $result = $this->model_install->installDefaultData($this->session->shop_name, $this->session->shop_country, false, true);

        if (!$result || $this->model_install->getErrors()) {
            $this->ajaxJsonAnswer(false, $this->model_install->getErrors());
        }
        $this->ajaxJsonAnswer(true);
    }

    /**
     * PROCESS : populateDatabase
     * Populate database with default data
     */
    public function processPopulateDatabase()
    {
        $this->initializeContext();

        $this->model_install->xml_loader_ids = $this->session->xml_loader_ids;
        $result = $this->model_install->populateDatabase(Tools::getValue('entity'));
        if (!$result || $this->model_install->getErrors()) {
            $this->ajaxJsonAnswer(false, $this->model_install->getErrors());
        }
        $this->session->xml_loader_ids = $this->model_install->xml_loader_ids;
        $this->session->process_validated = array_merge($this->session->process_validated, ['populateDatabase' => true]);
        $this->ajaxJsonAnswer(true);
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

        $success = $this->model_install->configureShop(
            [
                'shop_name'              => $this->session->shop_name,
                'shop_activity'          => $this->session->shop_activity,
                'shop_country'           => $this->session->shop_country,
                'shop_timezone'          => $this->session->shop_timezone,
                'admin_firstname'        => $this->session->admin_firstname,
                'admin_lastname'         => $this->session->admin_lastname,
                'admin_password'         => $this->session->admin_password,
                'admin_email'            => $this->session->admin_email,
                'send_informations'      => $this->session->send_informations,
                'configuration_agrement' => $this->session->configuration_agrement,
                'rewrite_engine'         => $this->session->rewrite_engine,
            ]
        );

        if (!$success || $this->model_install->getErrors()) {
            $this->ajaxJsonAnswer(false, $this->model_install->getErrors());
        }

        $this->session->process_validated = array_merge($this->session->process_validated, ['configureShop' => true]);
        $this->ajaxJsonAnswer(true);
    }

    /**
     * PROCESS : installFixtures
     * Install fixtures (E.g. demo products)
     */
    public function processInstallFixtures()
    {
        $this->initializeContext();

        $this->model_install->xml_loader_ids = $this->session->xml_loader_ids;
        if (!$this->model_install->installFixtures(Tools::getValue('entity', null), ['shop_activity' => $this->session->shop_activity, 'shop_country' => $this->session->shop_country]) || $this->model_install->getErrors()) {
            $this->ajaxJsonAnswer(false, $this->model_install->getErrors());
        }
        $this->session->xml_loader_ids = $this->model_install->xml_loader_ids;
        $this->session->process_validated = array_merge($this->session->process_validated, ['installFixtures' => true]);
        $this->ajaxJsonAnswer(true);
    }

    /**
     * PROCESS : installModules
     * Install all modules in ~/modules/ directory
     */
    public function processInstallModules()
    {
        $this->initializeContext();

        $result = $this->model_install->installModules(Tools::getValue('module'));
        if (!$result || $this->model_install->getErrors()) {
            $this->ajaxJsonAnswer(false, $this->model_install->getErrors());
        }
        $this->session->process_validated = array_merge($this->session->process_validated, ['installModules' => true]);
        $this->ajaxJsonAnswer(true);
    }

    /**
     * PROCESS : installTheme
     * Install theme
     */
    public function processInstallTheme()
    {
        $this->initializeContext();

        $this->model_install->installTheme();
        if ($this->model_install->getErrors()) {
            $this->ajaxJsonAnswer(false, $this->model_install->getErrors());
        }

        $this->session->process_validated = array_merge($this->session->process_validated, ['installTheme' => true]);
        $this->ajaxJsonAnswer(true);
    }

    /**
     * @see InstallAbstractModel::display()
     */
    public function display()
    {
        // The installer SHOULD take less than 32M, but may take up to 35/36M sometimes. So 42M is a good value :)
        $low_memory = Tools::getMemoryLimit() < Tools::getOctets('42M');

        // We fill the process step used for Ajax queries
        $this->process_steps[] = ['key' => 'generateSettingsFile', 'lang' => $this->l('Create settings.inc file')];
        $this->process_steps[] = ['key' => 'installDatabase', 'lang' => $this->l('Create database tables')];
        $this->process_steps[] = ['key' => 'installDefaultData', 'lang' => $this->l('Create default shop and languages')];

        // If low memory, create subtasks for populateDatabase step (entity per entity)
        $populate_step = ['key' => 'populateDatabase', 'lang' => $this->l('Populate database tables')];
        if ($low_memory) {
            $populate_step['subtasks'] = [];
            $xml_loader = new InstallXmlLoader();
            foreach ($xml_loader->getSortedEntities() as $entity) {
                $populate_step['subtasks'][] = ['entity' => $entity];
            }
        }

        $this->process_steps[] = $populate_step;
        $this->process_steps[] = ['key' => 'configureShop', 'lang' => $this->l('Configure shop information')];

        if ($this->session->install_type == 'full') {
            // If low memory, create subtasks for installFixtures step (entity per entity)
            $fixtures_step = ['key' => 'installFixtures', 'lang' => $this->l('Install demonstration data')];
            if ($low_memory) {
                $fixtures_step['subtasks'] = [];
                $xml_loader = new InstallXmlLoader();
                $xml_loader->setFixturesPath();
                foreach ($xml_loader->getSortedEntities() as $entity) {
                    $fixtures_step['subtasks'][] = ['entity' => $entity];
                }
            }
            $this->process_steps[] = $fixtures_step;
        }

        $install_modules = ['key' => 'installModules', 'lang' => $this->l('Install modules')];
        if ($low_memory) {
            foreach ($this->model_install->getModulesList() as $module) {
                $install_modules['subtasks'][] = ['module' => $module];
            }
        }
        $this->process_steps[] = $install_modules;

        $this->process_steps[] = ['key' => 'installTheme', 'lang' => $this->l('Install theme')];

        $this->displayTemplate('process');
    }
}
