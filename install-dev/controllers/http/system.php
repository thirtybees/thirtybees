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
 * Step 2 : check system configuration (permissions on folders, PHP version, etc.)
 */
class InstallControllerHttpSystem extends InstallControllerHttp
{
    public $tests = [];

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
                        // This list should have a message for every test
                        // in ConfigurationTest::getDefaultTests().
                        // Exceptions: 'MysqlVersion', 'Files'
                        'Bcmath'           => $this->l('The PHP bcmath extension is enabled'),
                        'CacheDir'         => $this->l('Can write to cache/'),
                        'LogDir'           => $this->l('Can write to log/'),
                        'ImgDir'           => $this->l('Can write to img/'),
                        'ModuleDir'        => $this->l('Can write to modules/'),
                        'ThemeLangDir'     => $this->l('Can write to themes/niara/lang/'),
                        'ThemePdfLangDir'  => $this->l('Can write to themes/niara/pdf/lang/'),
                        'ThemeCacheDir'    => $this->l('Can write to themes/niara/cache/'),
                        'TranslationsDir'  => $this->l('Can write to translations/'),
                        'CustomizableProductsDir' => $this->l('Can write to upload/'),
                        'VirtualProductsDir'      => $this->l('Can write to download/'),
                        'ConfigDir'        => $this->l('Can write to config/'),
                        'MailsDir'         => $this->l('Can write to mails/'),
                        'System'           => $this->l('Critical PHP functions exist'),
                        'Fopen'            => $this->l('PHP\'s \'allow_url_fopen\' enabled'),
                        'Gd'               => $this->l('GD library is installed'),
                        'Json'             => $this->l('The PHP json extension is enabled'),
                        'MaxExecutionTime' => $this->l('Max execution time is higher than 30'),
                        'Mbstring'         => $this->l('Mbstring extension is enabled'),
                        'OpenSSL'          => $this->l('OpenSSL extension is enabled'),
                        'PdoMysql'         => $this->l('PDO MySQL extension is loaded'),
                        'PhpVersion'       => $this->l('PHP is 5.6.0 or later'),
                        'Upload'           => $this->l('Can upload files'),
                        'Xml'              => $this->l('The PHP xml extension is enabled'),
                        'Zip'              => $this->l('The PHP zip extension/functionality is enabled'),
                    ],
                ],
                [
                    'title'   => $this->l('Files'),
                    'success' => 1,
                    'checks'  => [
                        'Files' => $this->l('Not all files were successfully uploaded on your server'),
                    ],
                ],
                [
                    'title'   => $this->l('Permissions on files and folders'),
                    'success' => 1,
                    'checks'  => [
                        'ConfigDir'               => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/config/'),
                        'CacheDir'                => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/cache/'),
                        'LogDir'                  => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/log/'),
                        'ImgDir'                  => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/img/'),
                        'MailsDir'                => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/mails/'),
                        'ModuleDir'               => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/modules/'),
                        'ThemeLangDir'            => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/themes/'._THEME_NAME_.'/lang/'),
                        'ThemePdfLangDir'         => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/themes/'._THEME_NAME_.'/pdf/lang/'),
                        'ThemeCacheDir'           => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/themes/'._THEME_NAME_.'/cache/'),
                        'TranslationsDir'         => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/translations/'),
                        'CustomizableProductsDir' => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/upload/'),
                        'VirtualProductsDir'      => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/download/'),
                    ],
                ],
            ],
            'optional' => [
                [
                    'title'   => $this->l('Recommended PHP parameters'),
                    'success' => $this->tests['optional']['success'],
                    'checks'  => [
                        'Gz'              => $this->l('GZIP compression is not activated'),
                        'Tlsv12'          => $this->l('Could not make a secure connection with PayPal. Your store might not be able to process payments.'),
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
