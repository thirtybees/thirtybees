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
 * Class InstallModelInstall
 *
 * @since 1.0.0
 */
class InstallModelInstall extends InstallAbstractModel
{
    const SETTINGS_FILE = 'config/settings.inc.php';
    private static $cacheLocalizationPackContent = null;

    public $xmlLoaderIds;
    /**
     * @var FileLogger
     */
    public $logger;

    /**
     * InstallModelInstall constructor.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct()
    {
        parent::__construct();

        $this->logger = new FileLogger();
        if (is_writable(_PS_ROOT_DIR_.'/log/')) {
            $this->logger->setFilename(_PS_ROOT_DIR_.'/log/'.@date('Ymd').'_installation.log');
        }
    }

    /**
     * Generate settings file
     *
     * @param string $databaseServer
     * @param string $databaseLogin
     * @param string $databasePassword
     * @param string $databaseName
     * @param string $databasePrefix
     * @param string $databaseEngine
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function generateSettingsFile($databaseServer, $databaseLogin, $databasePassword, $databaseName, $databasePrefix)
    {
        // Check permissions for settings file
        if (file_exists(_PS_ROOT_DIR_.'/'.self::SETTINGS_FILE) && !is_writable(_PS_ROOT_DIR_.'/'.self::SETTINGS_FILE)) {
            $this->setError($this->language->l('%s file is not writable (check permissions)', self::SETTINGS_FILE));

            return false;
        } elseif (!file_exists(_PS_ROOT_DIR_.'/'.self::SETTINGS_FILE) && !is_writable(_PS_ROOT_DIR_.'/'.dirname(self::SETTINGS_FILE))) {
            $this->setError($this->language->l('%s folder is not writable (check permissions)', dirname(self::SETTINGS_FILE)));

            return false;
        }

        // Generate settings content and write file
        $settingsConstants = [
            '_DB_SERVER_'         => $databaseServer,
            '_DB_NAME_'           => $databaseName,
            '_DB_USER_'           => $databaseLogin,
            '_DB_PASSWD_'         => $databasePassword,
            '_DB_PREFIX_'         => $databasePrefix,
            '_MYSQL_ENGINE_'      => 'InnoDB',
            '_PS_CACHING_SYSTEM_' => 'CacheMemcache',
            '_PS_CACHE_ENABLED_'  => '0',
            '_COOKIE_KEY_'        => Tools::passwdGen(56),
            '_COOKIE_IV_'         => Tools::passwdGen(8),
            '_PS_CREATION_DATE_'  => date('Y-m-d'),
            '_TB_VERSION_'        => _TB_INSTALL_VERSION_,
            '_PS_VERSION_'        => '1.6.1.999',
        ];

        // If mcrypt is activated, add Rijndael 128 configuration
        if (function_exists('mcrypt_encrypt') && PHP_VERSION_ID < 70100) {
            $settingsConstants['_RIJNDAEL_KEY_'] = Tools::passwdGen(mcrypt_get_key_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC));
            $settingsConstants['_RIJNDAEL_IV_'] = base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND));
        }

        $settingsContent = "<?php\n";

        foreach ($settingsConstants as $constant => $value) {
            if ($constant == '_TB_VERSION_') {
                $settingsContent .= 'if (!defined(\''.$constant.'\'))'."\n\t";
            }

            $settingsContent .= "define('$constant', '".str_replace('\'', '\\\'', $value)."');\n";
        }

        if (!file_put_contents(_PS_ROOT_DIR_.'/'.self::SETTINGS_FILE, $settingsContent)) {
            $this->setError($this->language->l('Cannot write settings file'));

            return false;
        }

        return true;
    }

    /**
     * @param string|array $errors
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setError($errors)
    {
        if (!is_array($errors)) {
            $errors = [$errors];
        }

        parent::setError($errors);

        foreach ($errors as $error) {
            $this->logger->logError($error);
        }
    }

    /**
     * PROCESS : installDatabase
     * Generate settings file and create database structure
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function installDatabase($clearDatabase = false)
    {
        // Clear database (only tables with same prefix)
        require_once _PS_ROOT_DIR_.'/'.self::SETTINGS_FILE;
        if ($clearDatabase) {
            $this->clearDatabase();
        }

        // Install database structure
        $sqlLoader = new InstallSqlLoader();
        $sqlLoader->setMetaData(
            [
                'PREFIX_' => _DB_PREFIX_,
            ]
        );

        try {
            $sqlLoader->parseFile(_PS_INSTALL_DATA_PATH_.'db_schema.sql');
            $sqlLoader->parseFile(_PS_INSTALL_DATA_PATH_.'foreign_keys.sql');
        } catch (PrestashopInstallerException $e) {
            $this->setError($this->language->l('Database structure file not found'));

            return false;
        }

        if ($errors = $sqlLoader->getErrors()) {
            foreach ($errors as $error) {
                ddd($error);
                $this->setError($this->language->l('SQL error on query <i>%s</i>', $error['error']));
            }

            return false;
        }

        return true;
    }

    /**
     * Clear database (only tables with same prefix)
     *
     * @param bool $truncate If true truncate the table, if false drop the table
     *
     * @since 1.0.0 Truncate no longer allowed!
     */
    public function clearDatabase($truncate = false)
    {
        foreach (Db::getInstance()->executeS('SHOW TABLES') as $row) {
            $table = current($row);
            if (!_DB_PREFIX_ || preg_match('#^'._DB_PREFIX_.'#i', $table)) {
                Db::getInstance()->execute(('DROP TABLE').' `'.$table.'`');
            }
        }
    }

    /**
     * PROCESS : installDefaultData
     * Create default shop and languages
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param string   $shopName
     * @param int|bool $isoCountry
     * @param bool     $allLanguages
     * @param bool     $clearDatabase
     *
     * @return bool
     */
    public function installDefaultData($shopName, $isoCountry = false, $allLanguages = false, $clearDatabase = false)
    {
        // Install first shop
        if (!$this->createShop($shopName)) {
            return false;
        }

        // Install languages
        try {
            if (!$allLanguages) {
                $isoCodesToInstall = [$this->language->getLanguageIso()];
                if ($isoCountry) {
                    $version = str_replace('.', '', _TB_VERSION_);
                    $version = substr($version, 0, 2);
                    $localizationFileContent = $this->getLocalizationPackContent($version, $isoCountry);

                    if ($xml = @simplexml_load_string($localizationFileContent)) {
                        foreach ($xml->languages->language as $language) {
                            $isoCodesToInstall[] = (string) $language->attributes()->iso_code;
                        }
                    }
                }
            } else {
                $isoCodesToInstall = null;
            }
            $isoCodesToInstall = array_flip(array_flip($isoCodesToInstall));
            $languages = $this->installLanguages($isoCodesToInstall);
        } catch (PrestashopInstallerException $e) {
            $this->setError($e->getMessage());

            return false;
        }

        $flipLanguages = array_flip($languages);
        $idLang = (!empty($flipLanguages[$this->language->getLanguageIso()])) ? $flipLanguages[$this->language->getLanguageIso()] : 1;
        Configuration::updateGlobalValue('PS_LANG_DEFAULT', $idLang);
        Configuration::updateGlobalValue('PS_VERSION_DB', _TB_INSTALL_VERSION_);
        Configuration::updateGlobalValue('PS_INSTALL_VERSION', _TB_INSTALL_VERSION_);

        return true;
    }

    /**
     * @param string $shopName
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function createShop($shopName)
    {
        // Create default group shop
        $shopGroup = new ShopGroup();
        $shopGroup->name = 'Default';
        $shopGroup->active = true;
        if (!$shopGroup->add()) {
            $this->setError($this->language->l('Cannot create group shop').' / '.Db::getInstance()->getMsgError());

            return false;
        }

        // Create default shop
        $shop = new Shop();
        $shop->active = true;
        $shop->id_shop_group = $shopGroup->id;
        $shop->id_category = 2;
        $shop->id_theme = 1;
        $shop->name = $shopName;
        if (!$shop->add()) {
            $this->setError($this->language->l('Cannot create shop').' / '.Db::getInstance()->getMsgError());

            return false;
        }
        Context::getContext()->shop = $shop;

        // Create default shop URL
        $shopUrl = new ShopUrl();
        $shopUrl->domain = Tools::getHttpHost();
        $shopUrl->domain_ssl = Tools::getHttpHost();
        $shopUrl->physical_uri = __PS_BASE_URI__;
        $shopUrl->id_shop = $shop->id;
        $shopUrl->main = true;
        $shopUrl->active = true;
        if (!$shopUrl->add()) {
            $this->setError($this->language->l('Cannot create shop URL').' / '.Db::getInstance()->getMsgError());

            return false;
        }

        return true;
    }

    /**
     * @param $version
     * @param $country
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLocalizationPackContent($version, $country)
    {
        if (InstallModelInstall::$cacheLocalizationPackContent === null || array_key_exists($country, InstallModelInstall::$cacheLocalizationPackContent)) {
            $pathCacheFile = _PS_CACHE_DIR_.'sandbox'.DIRECTORY_SEPARATOR.$version.$country.'.xml';
            if (is_file($pathCacheFile)) {
                $localizationFileContent = file_get_contents($pathCacheFile);
            } else {
                $guzzle = new \GuzzleHttp\Client(['http_errors' => false]);
                $localizationFileContent = (string) $guzzle->get('http://api.prestashop.com/localization/'.$version.'/'.$country.'.xml')->getBody();
                if (!@simplexml_load_string($localizationFileContent)) {
                    $localizationFileContent = false;
                }
                if (!$localizationFileContent) {
                    $localizationFile = _PS_ROOT_DIR_.'/localization/default.xml';
                    if (file_exists(_PS_ROOT_DIR_.'/localization/'.$country.'.xml')) {
                        $localizationFile = _PS_ROOT_DIR_.'/localization/'.$country.'.xml';
                    }

                    $localizationFileContent = file_get_contents($localizationFile);
                }
                file_put_contents($pathCacheFile, $localizationFileContent);
            }
            InstallModelInstall::$cacheLocalizationPackContent[$country] = $localizationFileContent;
        }

        return isset(InstallModelInstall::$cacheLocalizationPackContent[$country]) ? InstallModelInstall::$cacheLocalizationPackContent[$country] : false;
    }

    /**
     * Install languages
     *
     * @return array Association between ID and iso array(id_lang => iso, ...)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function installLanguages($languagesList = null)
    {
        if ($languagesList == null || !is_array($languagesList) || !count($languagesList)) {
            $languagesList = $this->language->getIsoList();
        }

        $languagesAvailable = $this->language->getIsoList();
        $languages = [];
        foreach ($languagesList as $iso) {
            if (!in_array($iso, $languagesAvailable)) {
                continue;
            }
            if (!file_exists(_PS_INSTALL_LANGS_PATH_.$iso.'/language.xml')) {
                throw new PrestashopInstallerException($this->language->l('File "language.xml" not found for language iso "%s"', $iso));
            }

            if (!$xml = @simplexml_load_file(_PS_INSTALL_LANGS_PATH_.$iso.'/language.xml')) {
                throw new PrestashopInstallerException($this->language->l('File "language.xml" not valid for language iso "%s"', $iso));
            }

            $paramsLang = [
                'name'                     => (string) $xml->name,
                'iso_code'                 => substr((string) $xml->language_code, 0, 2),
                'allow_accented_chars_url' => (string) $xml->allow_accented_chars_url,
            ];

            if (InstallSession::getInstance()->safe_mode) {
                Language::checkAndAddLanguage($iso, false, true, $paramsLang);
            } else {
                Language::downloadAndInstallLanguagePack($iso, _TB_INSTALL_VERSION_, $paramsLang);
            }

            Language::loadLanguages();
            Tools::clearCache();
            if (!$idLang = Language::getIdByIso($iso, true)) {
                throw new PrestashopInstallerException($this->language->l('Cannot install language "%s"', ($xml->name) ? $xml->name : $iso));
            }
            $languages[$idLang] = $iso;

            // Copy language flag
            if (is_writable(_PS_IMG_DIR_.'l/')) {
                if (!copy(_PS_INSTALL_LANGS_PATH_.$iso.'/flag.jpg', _PS_IMG_DIR_.'l/'.$idLang.'.jpg')) {
                    throw new PrestashopInstallerException($this->language->l('Cannot copy flag language "%s"', _PS_INSTALL_LANGS_PATH_.$iso.'/flag.jpg => '._PS_IMG_DIR_.'l/'.$idLang.'.jpg'));
                }
            }
        }

        return $languages;
    }

    /**
     * PROCESS : populateDatabase
     * Populate database with default data
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param null $entity
     *
     * @return bool
     */
    public function populateDatabase($entity = null)
    {
        $languages = [];
        foreach (Language::getLanguages(true) as $lang) {
            $languages[$lang['id_lang']] = $lang['iso_code'];
        }

        // Install XML data (data/xml/ folder)
        $xmlLoader = new InstallXmlLoader();
        $xmlLoader->setLanguages($languages);

        if (isset($this->xmlLoaderIds) && $this->xmlLoaderIds) {
            $xmlLoader->setIds($this->xmlLoaderIds);
        }

        if ($entity) {
            $xmlLoader->populateEntity($entity);
        } else {
            $xmlLoader->populateFromXmlFiles();
        }
        if ($errors = $xmlLoader->getErrors()) {
            $this->setError($errors);

            return false;
        }

        // IDS from xmlLoader are stored in order to use them for fixtures
        $this->xmlLoaderIds = $xmlLoader->getIds();
        unset($xmlLoader);

        // Install custom SQL data (db_data.sql file)
        if (file_exists(_PS_INSTALL_DATA_PATH_.'db_data.sql')) {
            $sqlLoader = new InstallSqlLoader();
            $sqlLoader->setMetaData(
                [
                    'PREFIX_'     => _DB_PREFIX_,
                    'ENGINE_TYPE' => _MYSQL_ENGINE_,
                ]
            );

            $sqlLoader->parseFile(_PS_INSTALL_DATA_PATH_.'db_data.sql', false);
            if ($errors = $sqlLoader->getErrors()) {
                $this->setError($errors);

                return false;
            }
        }

        // Copy language default images (we do this action after database in populated because we need image types information)
        foreach ($languages as $iso) {
            $this->copyLanguageImages($iso);
        }

        return true;
    }

    /**
     * @param $iso
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function copyLanguageImages($iso)
    {
        $imgPath = _PS_INSTALL_LANGS_PATH_.$iso.'/img/';
        if (!is_dir($imgPath)) {
            return;
        }

        $list = [
            'products'      => _PS_PROD_IMG_DIR_,
            'categories'    => _PS_CAT_IMG_DIR_,
            'manufacturers' => _PS_MANU_IMG_DIR_,
            'suppliers'     => _PS_SUPP_IMG_DIR_,
            'scenes'        => _PS_SCENE_IMG_DIR_,
            'stores'        => _PS_STORE_IMG_DIR_,
            null            => _PS_IMG_DIR_.'l/', // Little trick to copy images in img/l/ path with all types
        ];

        foreach ($list as $cat => $dstPath) {
            if (!is_writable($dstPath)) {
                continue;
            }

            copy($imgPath.$iso.'.jpg', $dstPath.$iso.'.jpg');

            $types = ImageType::getImagesTypes($cat);
            foreach ($types as $type) {
                if (file_exists($imgPath.$iso.'-default-'.$type['name'].'.jpg')) {
                    copy($imgPath.$iso.'-default-'.$type['name'].'.jpg', $dstPath.$iso.'-default-'.$type['name'].'.jpg');
                } else {
                    ImageManager::resize($imgPath.$iso.'.jpg', $dstPath.$iso.'-default-'.$type['name'].'.jpg', $type['width'], $type['height']);
                }
            }
        }
    }

    /**
     * PROCESS : configureShop
     * Set default shop configuration
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function configureShop(array $data = [])
    {
        //clear image cache in tmp folder
        if (file_exists(_PS_TMP_IMG_DIR_)) {
            foreach (scandir(_PS_TMP_IMG_DIR_) as $file) {
                if ($file[0] != '.' && $file != 'index.php') {
                    Tools::deleteFile(_PS_TMP_IMG_DIR_.$file);
                }
            }
        }

        $defaultData = [
            'shopName'       => 'My Shop',
            'shopActivity'   => '',
            'shopCountry'    => 'us',
            'shopTimezone'   => 'US/Eastern',
            'useSmtp'        => false,
            'smtpEncryption' => 'off',
            'smtpPort'       => 25,
            'rewriteEngine'  => false,
        ];

        foreach ($defaultData as $k => $v) {
            if (!isset($data[$k])) {
                $data[$k] = $v;
            }
        }

        Context::getContext()->shop = new Shop(1);
        Configuration::loadConfiguration();

        // use the old image system if the safe_mod is enabled otherwise the installer will fail with the fixtures installation
        if (InstallSession::getInstance()->safe_mode) {
            Configuration::updateGlobalValue('PS_LEGACY_IMAGES', 1);
        }

        $idCountry = (int) Country::getByIso($data['shopCountry']);

        // Set default configuration
        Configuration::updateGlobalValue('PS_SHOP_DOMAIN', Tools::getHttpHost());
        Configuration::updateGlobalValue('PS_SHOP_DOMAIN_SSL', Tools::getHttpHost());
        Configuration::updateGlobalValue('PS_INSTALL_VERSION', _TB_INSTALL_VERSION_);
        Configuration::updateGlobalValue('PS_LOCALE_LANGUAGE', $this->language->getLanguageIso());
        Configuration::updateGlobalValue('PS_SHOP_NAME', $data['shopName']);
        Configuration::updateGlobalValue('PS_SHOP_ACTIVITY', $data['shopActivity']);
        Configuration::updateGlobalValue('PS_COUNTRY_DEFAULT', $idCountry);
        Configuration::updateGlobalValue('PS_LOCALE_COUNTRY', $data['shopCountry']);
        Configuration::updateGlobalValue('PS_TIMEZONE', $data['shopTimezone']);
        Configuration::updateGlobalValue('PS_CONFIGURATION_AGREMENT', (int) $data['configurationAgreement']);

        // Set mails configuration
        Configuration::updateGlobalValue('PS_MAIL_METHOD', ($data['useSmtp']) ? 2 : 1);
        Configuration::updateGlobalValue('PS_MAIL_SMTP_ENCRYPTION', $data['smtpEncryption']);
        Configuration::updateGlobalValue('PS_MAIL_SMTP_PORT', $data['smtpPort']);

        // Set default rewriting settings
        Configuration::updateGlobalValue('PS_REWRITING_SETTINGS', $data['rewriteEngine']);

        // Activate rijndael 128 encrypt algorihtm if mcrypt is activated
        Configuration::updateGlobalValue('PS_CIPHER_ALGORITHM', function_exists('mcrypt_encrypt') ? 1 : 0);

        $groups = Group::getGroups((int) Configuration::get('PS_LANG_DEFAULT'));
        $groupsDefault = Db::getInstance()->executeS('SELECT `name` FROM '._DB_PREFIX_.'configuration WHERE `name` LIKE "PS_%_GROUP" ORDER BY `id_configuration`');
        foreach ($groupsDefault as &$groupDefault) {
            if (is_array($groupDefault) && isset($groupDefault['name'])) {
                $groupDefault = $groupDefault['name'];
            }
        }

        if (is_array($groups) && count($groups)) {
            foreach ($groups as $key => $group) {
                if (Configuration::get($groupsDefault[$key]) != $groups[$key]['id_group']) {
                    Configuration::updateGlobalValue($groupsDefault[$key], (int) $groups[$key]['id_group']);
                }
            }
        }

        $states = Db::getInstance()->executeS('SELECT `id_order_state` FROM '._DB_PREFIX_.'order_state ORDER BY `id_order_state`');
        $statesDefault = Db::getInstance()->executeS('SELECT MIN(`id_configuration`), `name` FROM '._DB_PREFIX_.'configuration WHERE `name` LIKE "PS_OS_%" GROUP BY `value` ORDER BY`id_configuration`');

        foreach ($statesDefault as &$stateDefault) {
            if (is_array($stateDefault) && isset($stateDefault['name'])) {
                $stateDefault = $stateDefault['name'];
            }
        }

        if (is_array($states) && count($states)) {
            foreach ($states as $key => $state) {
                if (Configuration::get($statesDefault[$key]) != $states[$key]['id_order_state']) {
                    Configuration::updateGlobalValue($statesDefault[$key], (int) $states[$key]['id_order_state']);
                }
            }
            /* deprecated order state */
            Configuration::updateGlobalValue('PS_OS_OUTOFSTOCK_PAID', (int) Configuration::get('PS_OS_OUTOFSTOCK'));
        }

        // Set logo configuration
        if (file_exists(_PS_IMG_DIR_.'logo.jpg')) {
            list($width, $height) = getimagesize(_PS_IMG_DIR_.'logo.jpg');
            Configuration::updateGlobalValue('SHOP_LOGO_WIDTH', round($width));
            Configuration::updateGlobalValue('SHOP_LOGO_HEIGHT', round($height));
        }

        // Disable cache for debug mode
        if (_PS_MODE_DEV_) {
            Configuration::updateGlobalValue('PS_SMARTY_CACHE', 1);
        }

        // Active only the country selected by the merchant
        Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'country SET active = 0 WHERE id_country != '.(int) $idCountry);

        // Set localization configuration
        $version = str_replace('.', '', _TB_VERSION_);
        $version = substr($version, 0, 2);
        $localizationFileContent = $this->getLocalizationPackContent($version, $data['shopCountry']);

        $locale = new LocalizationPackCore();
        $locale->loadLocalisationPack($localizationFileContent, '', true);

        // Create default employee
        if (isset($data['adminFirstname']) && isset($data['adminLastname']) && isset($data['adminPassword']) && isset($data['adminEmail'])) {
            $employee = new Employee();
            $employee->firstname = Tools::ucfirst($data['adminFirstname']);
            $employee->lastname = Tools::ucfirst($data['adminLastname']);
            $employee->email = $data['adminEmail'];
            $employee->passwd = md5(_COOKIE_KEY_.$data['adminPassword']);
            $employee->last_passwd_gen = date('Y-m-d h:i:s', strtotime('-360 minutes'));
            $employee->bo_theme = 'default';
            $employee->bo_css = 'schemes/admin-theme-thirtybees.css';
            $employee->default_tab = 1;
            $employee->active = true;
            $employee->optin = true;
            $employee->id_profile = 1;
            $employee->id_lang = Configuration::get('PS_LANG_DEFAULT');
            $employee->bo_menu = 1;
            if (!$employee->add()) {
                $this->setError($this->language->l('Cannot create admin account'));

                return false;
            }
        } else {
            $this->setError($this->language->l('Cannot create admin account'));

            return false;
        }

        // Update default contact
        if (isset($data['adminEmail'])) {
            Configuration::updateGlobalValue('PS_SHOP_EMAIL', $data['adminEmail']);

            $contacts = new PrestaShopCollection('Contact');
            foreach ($contacts as $contact) {
                /** @var Contact $contact */
                $contact->email = $data['adminEmail'];
                $contact->update();
            }
        }

        if (!@Tools::generateHtaccess(null, $data['rewriteEngine'])) {
            Configuration::updateGlobalValue('PS_REWRITING_SETTINGS', 0);
        }

        return true;
    }

    /**
     * PROCESS : installModules
     * Download module from addons and Install all modules in ~/modules/ directory
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param null $module
     *
     * @return bool
     */
    public function installModules($module = null)
    {
        if ($module && !is_array($module)) {
            $module = [$module];
        }

        $modules = $module ? $module : $this->getModulesList();

        Module::updateTranslationsAfterInstall(false);

        $errors = [];
        foreach ($modules as $moduleName) {
            if (!file_exists(_PS_MODULE_DIR_.$moduleName.'/'.$moduleName.'.php')) {
                continue;
            }

            $module = Module::getInstanceByName($moduleName);
            if (!$module->install()) {
                $errors[] = $this->language->l('Cannot install module "%s"', $moduleName);
            }
        }

        if ($errors) {
            $this->setError($errors);

            return false;
        }

        Module::updateTranslationsAfterInstall(true);
        Language::updateModulesTranslations($modules);

        return true;
    }

    /**
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getModulesList()
    {
        $modules = [];
        if (false) {
            foreach (scandir(_PS_MODULE_DIR_) as $module) {
                if ($module[0] != '.' && is_dir(_PS_MODULE_DIR_.$module) && file_exists(_PS_MODULE_DIR_.$module.'/'.$module.'.php')) {
                    $modules[] = $module;
                }
            }
        } else {
            $modules = [
                'socialsharing',
                'blockbanner',
                'bankwire',
                'blockbestsellers',
                'blockcart',
                'blocksocial',
                'blockcategories',
                'blockcurrencies',
                'blockfacebook',
                'blocklanguages',
                'blocklayered',
                'blockcms',
                'blockcmsinfo',
                'blockcontact',
                'blockcontactinfos',
                'blockmanufacturer',
                'blockmyaccount',
                'blockmyaccountfooter',
                'blocknewproducts',
                'blocknewsletter',
                'blockpaymentlogo',
                'blocksearch',
                'blockspecials',
                'blockstore',
                'blocksupplier',
                'blocktags',
                'blocktopmenu',
                'blockuserinfo',
                'blockviewed',
                'cheque',
                'ctconfiguration',
                'ctdev',
                'cttopmenu',
                'dashactivity',
                'dashtrends',
                'dashgoals',
                'dashproducts',
                'graphnvd3',
                'gridhtml',
                'homeslider',
                'homefeatured',
                'productpaymentlogos',
                'pagesnotfound',
                'sekeywords',
                'statsbestcategories',
                'statsbestcustomers',
                'statsbestproducts',
                'statsbestsuppliers',
                'statsbestvouchers',
                'statscarrier',
                'statscatalog',
                'statscheckup',
                'statsdata',
                'statsequipment',
                'statsforecast',
                'statslive',
                'statsnewsletter',
                'statsorigin',
                'statspersonalinfos',
                'statsproduct',
                'statsregistrations',
                'statssales',
                'statssearch',
                'statsstock',
                'statsvisits',
                'themeconfigurator',
            ];
        }

        return $modules;
    }

    /**
     * PROCESS : installFixtures
     * Install fixtures (E.g. demo products)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param null  $entity
     * @param array $data
     *
     * @return bool
     */
    public function installFixtures($entity = null, array $data = [])
    {
        $fixturesPath = _PS_INSTALL_FIXTURES_PATH_.'fashion/';
        $fixturesName = 'fashion';
        $zipFile = _PS_ROOT_DIR_.'/download/fixtures.zip';
        $tempDir = _PS_ROOT_DIR_.'/download/fixtures/';

        // try to download fixtures if no low memory mode
        if ($entity === null) {
            if (Tools::copy('http://api.prestashop.com/fixtures/'.$data['shopCountry'].'/'.$data['shopActivity'].'/fixtures.zip', $zipFile)) {
                Tools::deleteDirectory($tempDir, true);
                if (Tools::ZipTest($zipFile)) {
                    if (Tools::ZipExtract($zipFile, $tempDir)) {
                        $files = scandir($tempDir);
                        if (count($files)) {
                            foreach ($files as $file) {
                                if (!preg_match('/^\./', $file) && is_dir($tempDir.$file.'/')) {
                                    $fixturesPath = $tempDir.$file.'/';
                                    $fixturesName = $file;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Load class (use fixture class if one exists, or use InstallXmlLoader)
        if (file_exists($fixturesPath.'/install.php')) {
            require_once $fixturesPath.'/install.php';
            $class = 'InstallFixtures'.Tools::toCamelCase($fixturesName);
            if (!class_exists($class, false)) {
                $this->setError($this->language->l('Fixtures class "%s" not found', $class));

                return false;
            }

            $xmlLoader = new $class();
            if (!$xmlLoader instanceof InstallXmlLoader) {
                $this->setError($this->language->l('"%s" must be an instance of "InstallXmlLoader"', $class));

                return false;
            }
        } else {
            $xmlLoader = new InstallXmlLoader();
        }

        // Install XML data (data/xml/ folder)
        $xmlLoader->setFixturesPath($fixturesPath);
        if (isset($this->xmlLoaderIds) && $this->xmlLoaderIds) {
            $xmlLoader->setIds($this->xmlLoaderIds);
        }

        $languages = [];
        foreach (Language::getLanguages(false) as $lang) {
            $languages[$lang['id_lang']] = $lang['iso_code'];
        }
        $xmlLoader->setLanguages($languages);

        if ($entity) {
            $xmlLoader->populateEntity($entity);
        } else {
            $xmlLoader->populateFromXmlFiles();
            Tools::deleteDirectory($tempDir, true);
            @unlink($zipFile);
        }

        if ($errors = $xmlLoader->getErrors()) {
            $this->setError($errors);

            return false;
        }

        // IDS from xmlLoader are stored in order to use them for fixtures
        $this->xmlLoaderIds = $xmlLoader->getIds();
        unset($xmlLoader);

        // Index products in search tables
        Search::indexation(true);

        return true;
    }

    /**
     * PROCESS : installTheme
     * Install theme
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function installTheme()
    {
        // @todo do a real install of the theme
        $sqlLoader = new InstallSqlLoader();
        $sqlLoader->setMetaData(
            [
                'PREFIX_'     => _DB_PREFIX_,
                'ENGINE_TYPE' => _MYSQL_ENGINE_,
            ]
        );

        $sqlLoader->parseFile(_PS_INSTALL_DATA_PATH_.'theme.sql', false);
        if ($errors = $sqlLoader->getErrors()) {
            $this->setError($errors);

            return false;
        }
    }
}
