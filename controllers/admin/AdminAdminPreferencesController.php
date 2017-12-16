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
 * Class AdminAdminPreferencesControllerCore
 *
 * @since 1.0.0
 */
class AdminAdminPreferencesControllerCore extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        // Upload quota
        $maxUpload = (int) ini_get('upload_max_filesize');
        $maxPost = (int) ini_get('post_max_size');
        $uploadMb = min($maxUpload, $maxPost);

        // Options list
        $this->fields_options = [
            'general'       => [
                'title'  => $this->l('General'),
                'icon'   => 'icon-cogs',
                'fields' => [
                    'PRESTASTORE_LIVE'      => [
                        'title'      => $this->l('Automatically check for module updates'),
                        'hint'       => $this->l('New modules and updates are displayed on the modules page.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_COOKIE_CHECKIP'     => [
                        'title'      => $this->l('Check the cookie\'s IP address'),
                        'hint'       => $this->l('Check the IP address of the cookie in order to prevent your cookie from being stolen.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                        'default'    => '0',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_COOKIE_LIFETIME_FO' => [
                        'title'      => $this->l('Lifetime of front office cookies'),
                        'hint'       => $this->l('Set the amount of hours during which the front office cookies are valid. After that amount of time, the customer will have to log in again.'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('hours'),
                        'default'    => '480',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_COOKIE_LIFETIME_BO' => [
                        'title'      => $this->l('Lifetime of back office cookies'),
                        'hint'       => $this->l('Set the amount of hours during which the back office cookies are valid. After that amount of time, the thirty bees user will have to log in again.'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('hours'),
                        'default'    => '480',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'upload'        => [
                'title'  => $this->l('Upload quota'),
                'icon'   => 'icon-cloud-upload',
                'fields' => [
                    'PS_ATTACHMENT_MAXIMUM_SIZE'  => [
                        'title'      => $this->l('Maximum size for attachment'),
                        'hint'       => sprintf($this->l('Set the maximum size allowed for attachment files (in megabytes). This value has to be lower or equal to the maximum file upload allotted by your server (currently: %s MB).'), $uploadMb),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('megabytes'),
                        'default'    => '2',
                    ],
                    'PS_LIMIT_UPLOAD_FILE_VALUE'  => [
                        'title'      => $this->l('Maximum size for a downloadable product'),
                        'hint'       => sprintf($this->l('Define the upload limit for a downloadable product (in megabytes). This value has to be lower or equal to the maximum file upload allotted by your server (currently: %s MB).'), $uploadMb),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('megabytes'),
                        'default'    => '1',
                    ],
                    'PS_LIMIT_UPLOAD_IMAGE_VALUE' => [
                        'title'      => $this->l('Maximum size for a product\'s image'),
                        'hint'       => sprintf($this->l('Define the upload limit for an image (in megabytes). This value has to be lower or equal to the maximum file upload allotted by your server (currently: %s MB).'), $uploadMb),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('megabytes'),
                        'default'    => '1',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'notifications' => [
                'title'       => $this->l('Notifications'),
                'icon'        => 'icon-list-alt',
                'description' => $this->l('Notifications are numbered bubbles displayed at the very top of your back office, right next to the shop\'s name. They display the number of new items since you last clicked on them.'),
                'fields'      => [
                    'PS_SHOW_NEW_ORDERS'    => [
                        'title'      => $this->l('Show notifications for new orders'),
                        'hint'       => $this->l('This will display notifications when new orders are made in your shop.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_SHOW_NEW_CUSTOMERS' => [
                        'title'      => $this->l('Show notifications for new customers'),
                        'hint'       => $this->l('This will display notifications every time a new customer registers in your shop.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_SHOW_NEW_MESSAGES'  => [
                        'title'      => $this->l('Show notifications for new messages'),
                        'hint'       => $this->l('This will display notifications when new messages are posted in your shop.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                ],
                'submit'      => ['title' => $this->l('Save')],
            ],
        ];
    }

    /**
     * @since 1.0.0
     */
    public function postProcess()
    {
        $uploadMaxSize = (int) str_replace('M', '', ini_get('upload_max_filesize'));
        $postMaxSize = (int) str_replace('M', '', ini_get('post_max_size'));
        $maxSize = $uploadMaxSize < $postMaxSize ? $uploadMaxSize : $postMaxSize;

        if (Tools::getValue('PS_LIMIT_UPLOAD_FILE_VALUE') > $maxSize || Tools::getValue('PS_LIMIT_UPLOAD_IMAGE_VALUE') > $maxSize) {
            $this->errors[] = Tools::displayError('The limit chosen is larger than the server\'s maximum upload limit. Please increase the limits of your server.');

            return;
        }

        if (Tools::getIsset('PS_LIMIT_UPLOAD_FILE_VALUE') && !Tools::getValue('PS_LIMIT_UPLOAD_FILE_VALUE')) {
            $_POST['PS_LIMIT_UPLOAD_FILE_VALUE'] = 1;
        }

        if (Tools::getIsset('PS_LIMIT_UPLOAD_IMAGE_VALUE') && !Tools::getValue('PS_LIMIT_UPLOAD_IMAGE_VALUE')) {
            $_POST['PS_LIMIT_UPLOAD_IMAGE_VALUE'] = 1;
        }

        parent::postProcess();
    }

    /**
     * Update PS_ATTACHMENT_MAXIMUM_SIZE
     *
     * @param mixed $value
     *
     * @since 1.0.0
     */
    public function updateOptionPsAttachementMaximumSize($value)
    {
        if (!$value) {
            return;
        }

        $uploadMaxSize = (int) str_replace('M', '', ini_get('upload_max_filesize'));
        $postMaxSize = (int) str_replace('M', '', ini_get('post_max_size'));
        $maxSize = $uploadMaxSize < $postMaxSize ? $uploadMaxSize : $postMaxSize;
        $value = ($maxSize < Tools::getValue('PS_ATTACHMENT_MAXIMUM_SIZE')) ? $maxSize : Tools::getValue('PS_ATTACHMENT_MAXIMUM_SIZE');
        Configuration::updateValue('PS_ATTACHMENT_MAXIMUM_SIZE', $value);
    }
}
