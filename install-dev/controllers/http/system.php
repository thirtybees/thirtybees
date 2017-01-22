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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Step 2 : check system configuration (permissions on folders, PHP version, etc.)
 */
class InstallControllerHttpSystem extends InstallControllerHttp
{
    public $tests = [];

    /**
     * @var InstallModelSystem
     */
    public $model_system;

    /**
     * @see InstallAbstractModel::init()
     */
    public function init()
    {
        require_once _PS_INSTALL_MODELS_PATH_.'system.php';
        $this->model_system = new InstallModelSystem();
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
        $this->tests['required'] = $this->model_system->checkRequiredTests();

        return $this->tests['required']['success'];
    }

    /**
     * Display system step
     */
    public function display()
    {
        if (!isset($this->tests['required'])) {
            $this->tests['required'] = $this->model_system->checkRequiredTests();
        }
        if (!isset($this->tests['optional'])) {
            $this->tests['optional'] = $this->model_system->checkOptionalTests();
        }

        if (!is_callable('getenv') || !($user = @getenv('APACHE_RUN_USER'))) {
            $user = 'Apache';
        }

        // Generate display array
        $this->tests_render = [
            'required' => [
                [
                    'title'   => $this->l('Required PHP parameters'),
                    'success' => 1,
                    'checks'  => [
                        'phpversion'    => $this->l('PHP 5.5.0 or later is not enabled'),
                        'upload'        => $this->l('Cannot upload files'),
                        'system'        => $this->l('Cannot create new files and folders'),
                        'gd'            => $this->l('GD library is not installed'),
                        'pdo_mysql'     => $this->l('PDO MySQL extension is not loaded'),
                        'intl'          => $this->l('The PHP intl extension is not enabled'),
                        'xml'           => $this->l('The PHP xml extension is not enabled'),
                        'json'          => $this->l('The PHP json extension is not enabled'),
                        'zip'           => $this->l('The PHP zip extension/functionality is not enabled'),
                    ],
                ],
                [
                    'title'   => $this->l('Files'),
                    'success' => 1,
                    'checks'  => [
                        'files' => $this->l('Not all files were successfully uploaded on your server'),
                    ],
                ],
                [
                    'title'   => $this->l('Permissions on files and folders'),
                    'success' => 1,
                    'checks'  => [
                        'config_dir'                => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/config/'),
                        'cache_dir'                 => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/cache/'),
                        'log_dir'                   => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/log/'),
                        'img_dir'                   => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/img/'),
                        'mails_dir'                 => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/mails/'),
                        'module_dir'                => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/modules/'),
                        'theme_lang_dir'            => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/themes/community-theme-default/lang/'),
                        'theme_pdf_lang_dir'        => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/themes/community-theme-default/pdf/lang/'),
                        'theme_cache_dir'           => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/themes/community-theme-default/cache/'),
                        'translations_dir'          => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/translations/'),
                        'customizable_products_dir' => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/upload/'),
                        'virtual_products_dir'      => $this->l('Recursive write permissions for %1$s user on %2$s', $user, '~/download/'),
                    ],
                ],
            ],
            'optional' => [
                [
                    'title'   => $this->l('Recommended PHP parameters'),
                    'success' => $this->tests['optional']['success'],
                    'checks'  => [
                        'new_phpversion'   => sprintf($this->l('You are using PHP %s version. The next minor version of thirty bees (1.1.0) will require PHP 5.6. To make sure you’re ready for the future, we recommend you to upgrade to PHP 5.6 now!'), phpversion()),
                        'register_globals' => $this->l('PHP register_globals option is enabled'),
                        'gz'               => $this->l('GZIP compression is not activated'),
                        'mbstring'         => $this->l('Mbstring extension is not enabled'),
                        'tlsv1_2'          => $this->l('Could not make a secure connection with PayPal. Your store might not be able to process payments.'),
                    ],
                ],
            ],
        ];

        foreach ($this->tests_render['required'] as &$category) {
            foreach ($category['checks'] as $id => $check) {
                if ($this->tests['required']['checks'][$id] != 'ok') {
                    $category['success'] = 0;
                }
            }
        }
        
        // If required tests failed, disable next button
        if (!$this->tests['required']['success']) {
            $this->next_button = false;
        }

        $this->displayTemplate('system');
    }
}
