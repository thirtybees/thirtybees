<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Step 2 : check system configuration (permissions on folders, PHP version, etc.)
 */
class InstallControllerHttpSystem extends InstallControllerHttp
{
    /**
     * @var array
     */
    public $tests = [];

    /**
     * @var array
     */
    public $testsRender;

    /**
     * @var InstallModelSystem
     */
    public $modelSystem;

    /**
     * @see InstallAbstractModel::init()
     */
    public function init()
    {
        require_once _PS_INSTALL_MODELS_PATH_.'system.php';
        $this->modelSystem = new InstallModelSystem();
    }

    /**
     * @see InstallAbstractModel::processNextStep()
     */
    public function processNextStep()
    {
    }

    /**
     * Required tests must be passed to validate this step
     *
     * @see InstallAbstractModel::validate()
     */
    public function validate()
    {
        $this->tests['required'] = $this->modelSystem->checkRequiredTests();

        return $this->tests['required']['success'];
    }

    /**
     * Display system step
     *
     * @throws PrestashopInstallerException
     */
    public function display()
    {
        if (!isset($this->tests['required'])) {
            $this->tests['required'] = $this->modelSystem->checkRequiredTests();
        }
        if (!isset($this->tests['optional'])) {
            $this->tests['optional'] = $this->modelSystem->checkOptionalTests();
        }

        if (!is_callable('getenv') || !($user = @getenv('APACHE_RUN_USER'))) {
            $user = 'Apache';
        }

        // Generate display array
        $this->testsRender = [
            'required' => [
                [
                    'title'   => $this->l('Required PHP parameters'),
                    'success' => 1,
                    'checks'  => [
                        ConfigurationTest::TEST_BCMATH => $this->l('The PHP bcmath extension is enabled'),
                        ConfigurationTest::TEST_CACHE_DIR => $this->l('Can write to cache/'),
                        ConfigurationTest::TEST_LOG_DIR => $this->l('Can write to log/'),
                        ConfigurationTest::TEST_IMG_DIR => $this->l('Can write to img/'),
                        ConfigurationTest::TEST_MODULES_DIR => $this->l('Can write to modules/'),
                        ConfigurationTest::TEST_THEME_LANG_DIR => $this->l('Can write to themes/niara/lang/'),
                        ConfigurationTest::TEST_THEME_PDF_LANG_DIR => $this->l('Can write to themes/niara/pdf/lang/'),
                        ConfigurationTest::TEST_THEME_CACHE_DIR => $this->l('Can write to themes/niara/cache/'),
                        ConfigurationTest::TEST_TRANSLATIONS_DIR => $this->l('Can write to translations/'),
                        ConfigurationTest::TEST_CUSTOMIZABLE_PRODUCTS_DIR => $this->l('Can write to upload/'),
                        ConfigurationTest::TEST_VIRTUAL_PRODUCTS_DIR => $this->l('Can write to download/'),
                        ConfigurationTest::TEST_CONFIG_DIR => $this->l('Can write to config/'),
                        ConfigurationTest::TEST_MAILS_DIR => $this->l('Can write to mails/'),
                        ConfigurationTest::TEST_SYSTEM => $this->l('Critical PHP functions exist'),
                        ConfigurationTest::TEST_FOPEN => $this->l('PHP\'s \'allow_url_fopen\' enabled'),
                        ConfigurationTest::TEST_GD => $this->l('GD library is installed'),
                        ConfigurationTest::TEST_JSON => $this->l('The PHP json extension is enabled'),
                        ConfigurationTest::TEST_MAX_EXECUTION_TIME => $this->l('Max execution time is higher than 30'),
                        ConfigurationTest::TEST_MBSTRING => $this->l('Mbstring extension is enabled'),
                        ConfigurationTest::TEST_OPENSSL => $this->l('OpenSSL extension is enabled'),
                        ConfigurationTest::TEST_PDO_MYSQL => $this->l('PDO MySQL extension is loaded'),
                        ConfigurationTest::TEST_UPLOAD => $this->l('Can upload files'),
                        ConfigurationTest::TEST_XML => $this->l('The PHP xml extension is enabled'),
                        ConfigurationTest::TEST_ZIP => $this->l('The PHP zip extension/functionality is enabled'),
                    ],
                ],
                [
                    'title'   => $this->l('Files'),
                    'success' => 1,
                    'checks'  => [
                        ConfigurationTest::TEST_FILES => $this->l('Not all files were successfully uploaded on your server'),
                    ],
                ],
                [
                    'title'   => $this->l('Permissions on files and folders'),
                    'success' => 1,
                    'checks'  => [
                        ConfigurationTest::TEST_CONFIG_DIR => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/config/'),
                        ConfigurationTest::TEST_CACHE_DIR => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/cache/'),
                        ConfigurationTest::TEST_LOG_DIR => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/log/'),
                        ConfigurationTest::TEST_IMG_DIR => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/img/'),
                        ConfigurationTest::TEST_MAILS_DIR => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/mails/'),
                        ConfigurationTest::TEST_MODULES_DIR => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/modules/'),
                        ConfigurationTest::TEST_THEME_LANG_DIR => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/themes/' . _THEME_NAME_ . '/lang/'),
                        ConfigurationTest::TEST_THEME_PDF_LANG_DIR => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/themes/' . _THEME_NAME_ . '/pdf/lang/'),
                        ConfigurationTest::TEST_THEME_CACHE_DIR => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/themes/' . _THEME_NAME_ . '/cache/'),
                        ConfigurationTest::TEST_TRANSLATIONS_DIR => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/translations/'),
                        ConfigurationTest::TEST_CUSTOMIZABLE_PRODUCTS_DIR => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/upload/'),
                        ConfigurationTest::TEST_VIRTUAL_PRODUCTS_DIR => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/download/'),
                    ],
                ],
            ],
            'optional' => [
                [
                    'title'   => $this->l('Recommended PHP parameters'),
                    'success' => $this->tests['optional']['success'],
                    'checks'  => [
                        ConfigurationTest::TEST_GZ => $this->l('GZIP compression is not activated'),
                        ConfigurationTest::TEST_INTL => $this->l('The PHP intl extension is enabled'),
                        ConfigurationTest::TEST_SOAP => $this->l('The PHP soap extension is enabled'),
                    ],
                ],
            ],
        ];

        foreach ($this->testsRender['required'] as &$category) {
            foreach ($category['checks'] as $id => $check) {
                $result = $this->tests['required']['checks'][$id];
                if ($result != 'ok') {
                    $category['success'] = 0;
                    $category['checks'][$id] .= ': '.$result;
                }
            }
        }

        // If required tests failed, disable next button
        if (!$this->tests['required']['success']) {
            $this->nextButton = false;
        }

        $this->displayTemplate('system');
    }
}
