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
 * Class AdminMaintenanceControllerCore
 *
 * @property Configuration|null $object
 */
class AdminMaintenanceControllerCore extends AdminController
{
    /**
     * AdminMaintenanceControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('General'),
                'fields' => [
                    'PS_SHOP_ENABLE'    => [
                        'title'      => $this->l('Enable Shop'),
                        'desc'       => $this->l('Activate or deactivate your shop (It is a good idea to deactivate your shop while you perform maintenance. Please note that the webservice will not be disabled).'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'MAINTENANCE_IP' => [
                        'title'      => $this->l('Maintenance IP'),
                        'hint'       => $this->l('IP addresses allowed to access the front office even if the shop is disabled. Please use a comma to separate them (e.g. 42.24.4.2,127.0.0.1,99.98.97.96)'),
                        'type'       => 'maintenance_ip',
                        'auto_value' => false,
                        'no_multishop_checkbox' => true,
                        'value'      => implode(',', Tools::getMaintenanceIPAddresses()),
                        'remoteIp'   => Tools::getRemoteAddr(),
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];
    }

    /**
     * @param string $value
     *
     * @return void
     * @throws PrestaShopException
     */
    public function updateOptionMaintenanceIp($value)
    {
        $ips = explode(',', (string)$value);
        $ips = array_map('trim', $ips);
        $ips = array_filter($ips);
        $ips = array_filter($ips, [Validate::class, 'isIPAddress']);
        sort($ips);
        $ips = array_unique($ips);
        $value = implode(',', $ips);
        if ($ips) {
            Configuration::updateGlobalValue(Configuration::MAINTENANCE_IP_ADDRESSES, $value);
            Db::getInstance()->delete('configuration', 'name = "'.pSQL(Configuration::MAINTENANCE_IP_ADDRESSES).'" AND (id_shop IS NOT NULL OR id_shop_group IS NOT NULL)');
        } else {
            Configuration::deleteByName(Configuration::MAINTENANCE_IP_ADDRESSES);
        }
        $this->fields_options['general']['fields']['MAINTENANCE_IP']['value'] = implode(',', Tools::getMaintenanceIPAddresses());
    }
}
