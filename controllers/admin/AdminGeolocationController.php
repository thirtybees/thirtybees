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
 * Class AdminGeolocationControllerCore
 */
class AdminGeolocationControllerCore extends AdminController
{
    /**
     * AdminGeolocationControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();

        $this->bootstrap = true;
        $services = $this->getGeolocationServices();
        $hasServices = count($services) > 1;
        $this->fields_options = [
            'geolocationConfiguration' => [
                'title'  => $this->l('Geolocation service'),
                'icon'   => 'icon-map-marker',
                'description' => ($hasServices
                    ? $this->l('Enable geolocation feature by choosing service')
                    : Translate::ppTags(
                        $this->l('No geolocation service was found in the system. Please go to [1]Modules and Services[/1] to install service you want to use'),
                        ['<a href="' . $this->context->link->getAdminLink('AdminModules') . '">']
                    )
                ),
                'fields' => [
                    'PS_GEOLOCATION_SERVICE' => [
                        'title'      => $this->l('Geolocation service'),
                        'hint'       => $this->l('Choose module that provides geolocation services'),
                        'type'       => 'select',
                        'identifier' => 'key',
                        'list'       => $services
                    ]
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'geolocationCountries'     => [
                'title'       => $this->l('Options'),
                'icon'        => 'icon-map-marker',
                'fields'      => [
                    'PS_GEOLOCATION_BEHAVIOR'    => [
                        'title'      => $this->l('Geolocation behavior for restricted countries'),
                        'type'       => 'select',
                        'identifier' => 'key',
                        'list'       => [
                            ['key' => _PS_GEOLOCATION_NO_CATALOG_, 'name' => $this->l('Visitors cannot see your catalog.')],
                            ['key' => _PS_GEOLOCATION_NO_ORDER_, 'name' => $this->l('Visitors can see your catalog but cannot place an order.')],
                        ],
                    ],
                    'PS_GEOLOCATION_NA_BEHAVIOR' => [
                        'title'      => $this->l('Geolocation behavior for other countries'),
                        'type'       => 'select',
                        'identifier' => 'key',
                        'list'       => [
                            ['key' => '-1', 'name' => $this->l('All features are available')],
                            ['key' => _PS_GEOLOCATION_NO_CATALOG_, 'name' => $this->l('Visitors cannot see your catalog.')],
                            ['key' => _PS_GEOLOCATION_NO_ORDER_, 'name' => $this->l('Visitors can see your catalog but cannot place an order.')],
                        ],
                    ],
                ],
                'submit'      => ['title' => $this->l('Save')],
            ],
            'geolocationWhitelist'     => [
                'title'       => $this->l('IP address whitelist'),
                'icon'        => 'icon-sitemap',
                'description' => $this->l('You can add IP addresses that will always be allowed to access your shop (e.g. Google bots\' IP).'),
                'fields'      => [
                    'PS_GEOLOCATION_WHITELIST' => ['title' => $this->l('Whitelisted IP addresses'), 'type' => 'textarea_newlines', 'cols' => 15, 'rows' => 30],
                ],
                'submit'      => ['title' => $this->l('Save')],
            ],
        ];
    }

    /**
     * Registers javascript assets
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJS(_PS_JS_DIR_ . 'admin/geolocation.js');
    }

    /**
     * Process update options
     *
     * @return void
     * @throws PrestaShopException
     */
    public function processUpdateOptions()
    {
        if (empty($this->errors)) {
            if (!is_array(Tools::getValue('countries')) || !count(Tools::getValue('countries'))) {
                $this->errors[] = Tools::displayError('Country selection is invalid.');
            } else {
                Configuration::updateValue(
                    'PS_GEOLOCATION_BEHAVIOR',
                    (!Tools::getIntValue('PS_GEOLOCATION_BEHAVIOR') ? _PS_GEOLOCATION_NO_CATALOG_ : _PS_GEOLOCATION_NO_ORDER_)
                );
                Configuration::updateValue('PS_GEOLOCATION_NA_BEHAVIOR', Tools::getIntValue('PS_GEOLOCATION_NA_BEHAVIOR'));
                Configuration::updateValue('PS_ALLOWED_COUNTRIES', implode(';', Tools::getValue('countries')));
            }
        }

        parent::processUpdateOptions();
    }

    /**
     * Render options
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderOptions()
    {
        // This field is not declared in class constructor because we want it to be manually post processed
        $this->fields_options['geolocationCountries']['fields']['countries'] = [
            'title'      => $this->l('Select the countries from which your store is accessible'),
            'type'       => 'checkbox_table',
            'identifier' => 'iso_code',
            'list'       => Country::getCountries($this->context->language->id),
            'auto_value' => false,
        ];

        $this->tpl_option_vars = ['allowed_countries' => explode(';', (string)Configuration::get('PS_ALLOWED_COUNTRIES'))];

        return parent::renderOptions();
    }

    /**
     * Callback to save PS_GEOLOATION_WHITELIST option
     *
     * @param string $whitelist ip addresses separated by new line
     * @throws PrestaShopException
     */
    public function updateOptionPsGeolocationWhitelist($whitelist)
    {
        // validate whitelist
        $valid = true;
        $list = [];
        foreach (explode("\n", $whitelist) as $address) {
            $address = trim($address);
            if (! Tools::isEmpty($address)) {
                if (preg_match('/^[0-9]+[0-9.]*$/', $address)) {
                    $list[] = $address;
                } else {
                    $this->errors[] = sprintf(Tools::displayError('Invalid IP address: %s'), Tools::htmlentitiesUTF8($address));
                    $valid = false;
                }
            }
        }

        if ($valid) {
            Configuration::updateValue('PS_GEOLOCATION_WHITELIST', implode(';', $list));
        }
    }

    /**
     * Callback to save selected geolocation service
     * For backwards compatibility reasons it also set PS_GEOLOCATION_ENABLED flag
     *
     * @param string $service name of selected geolocation module
     *
     * @throws PrestaShopException
     */
    public function updateOptionPsGeolocationService($service)
    {
        if ($service) {
            Configuration::updateGlobalValue('PS_GEOLOCATION_SERVICE', $service);
            Configuration::updateGlobalValue('PS_GEOLOCATION_ENABLED', 1);
        } else {
            Configuration::deleteByName('PS_GEOLOCATION_SERVICE');
            Configuration::updateGlobalValue('PS_GEOLOCATION_ENABLED', 0);
        }
    }

    /**
     * Return list of all geolocation services installed in the system
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function getGeolocationServices()
    {
        $services = [
            ['key' => '', 'name' => $this->l('- No service selected -') ]
        ];
        $moduleList = Hook::getHookModuleExecList('actionGeoLocation');
        if ($moduleList) {
            foreach ($moduleList as $info) {
                $services[] = [
                    'key' => $info['module'],
                    'name' => Module::getModuleName($info['module']),
                ];
            }
        }
        return $services;
    }
}
