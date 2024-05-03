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
 * Class AdminPreferencesControllerCore
 *
 * @property Configuration|null $object
 */
class AdminPreferencesControllerCore extends AdminController
{
    /**
     * AdminPreferencesControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->className = 'Configuration';
        $this->table = 'configuration';

        // Prevent classes which extend AdminPreferences to load useless data
        if (get_class($this) == 'AdminPreferencesController') {
            $roundMode = [
                [
                    'value' => PS_ROUND_HALF_UP,
                    'name'  => $this->l('Round up away from zero, when it is half way there (recommended)'),
                ],
                [
                    'value' => PS_ROUND_HALF_DOWN,
                    'name'  => $this->l('Round down towards zero, when it is half way there'),
                ],
                [
                    'value' => PS_ROUND_HALF_EVEN,
                    'name'  => $this->l('Round towards the next even value'),
                ],
                [
                    'value' => PS_ROUND_HALF_ODD,
                    'name'  => $this->l('Round towards the next odd value'),
                ],
                [
                    'value' => PS_ROUND_UP,
                    'name'  => $this->l('Round up to the nearest value'),
                ],
                [
                    'value' => PS_ROUND_DOWN,
                    'name'  => $this->l('Round down to the nearest value'),
                ],
            ];
            $activities1 = [
                0  => $this->l('-- Please choose your main activity --'),
                2  => $this->l('Animals and Pets'),
                3  => $this->l('Art and Culture'),
                4  => $this->l('Babies'),
                5  => $this->l('Beauty and Personal Care'),
                6  => $this->l('Cars'),
                7  => $this->l('Computer Hardware and Software'),
                8  => $this->l('Download'),
                9  => $this->l('Fashion and accessories'),
                10 => $this->l('Flowers, Gifts and Crafts'),
                11 => $this->l('Food and beverage'),
                12 => $this->l('HiFi, Photo and Video'),
                13 => $this->l('Home and Garden'),
                14 => $this->l('Home Appliances'),
                15 => $this->l('Jewelry'),
                1  => $this->l('Lingerie and Adult'),
                16 => $this->l('Mobile and Telecom'),
                17 => $this->l('Services'),
                18 => $this->l('Shoes and accessories'),
                19 => $this->l('Sport and Entertainment'),
                20 => $this->l('Travel'),
            ];
            $activities2 = [];
            foreach ($activities1 as $value => $name) {
                $activities2[] = ['value' => $value, 'name' => $name];
            }

            $fields = [
                'PS_SSL_ENABLED' => [
                    'title'      => $this->l('Enable SSL'),
                    'desc'       => $this->l('This uses HTTPS rather than HTTP for shop internal links.'),
                    'validation' => 'isBool',
                    'cast'       => 'intval',
                    'type'       => 'bool',
                    'default'    => '0',
                ],
            ];

            $fields = array_merge(
                $fields,
                [
                    'PS_TOKEN_ENABLE'             => [
                        'title'      => $this->l('Increase front office security'),
                        'desc'       => $this->l('Enable or disable token in the Front Office to improve thirty bees\' security.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                        'default'    => '0',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    Configuration::BO_FORCE_TOKEN => [
                        'title'      => $this->l('Always require back office token'),
                        'desc'       => $this->l('Always require token in Back Office to improve thirty bees\' security.'),
                        'validation' => 'isBool',
                        'type'       => 'bool',
                        'default'    => '0',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_ALLOW_HTML_IFRAME'        => [
                        'title'      => $this->l('Allow iframes on HTML fields'),
                        'desc'       => $this->l('Allow iframes on text fields like product description. We recommend that you leave this option disabled.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                        'default'    => '0',
                    ],
                    'PS_USE_HTMLPURIFIER'         => [
                        'title'      => $this->l('Use HTMLPurifier Library'),
                        'desc'       => $this->l('Clean the HTML content on text fields. We recommend that you leave this option enabled.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                        'default'    => '0',
                    ],
                    'PS_PRICE_ROUND_MODE'         => [
                        'title'      => $this->l('Round mode'),
                        'desc'       => $this->l('You can choose among 6 different ways of rounding prices. "Round up away from zero ..." is the recommended behavior.'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => $roundMode,
                        'identifier' => 'value',
                    ],
                    'PS_ROUND_TYPE'               => [
                        'title'      => $this->l('Round type'),
                        'desc'       => $this->l('You can choose when to round prices: either on each item, each line or the total (of an invoice, for example).'),
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => [
                            [
                                'name' => $this->l('Round on each item'),
                                'id'   => Order::ROUND_ITEM,
                            ],
                            [
                                'name' => $this->l('Round on each line'),
                                'id'   => Order::ROUND_LINE,
                            ],
                            [
                                'name' => $this->l('Round on the total'),
                                'id'   => Order::ROUND_TOTAL,
                            ],
                        ],
                        'identifier' => 'id',
                    ],
                    'PS_DISPLAY_SUPPLIERS'        => [
                        'title'      => $this->l('Display suppliers and manufacturers'),
                        'desc'       => $this->l('Enable suppliers and manufacturers pages on your front office even when their respective modules are disabled.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_DISPLAY_BEST_SELLERS'     => [
                        'title'      => $this->l('Display best sellers'),
                        'desc'       => $this->l('Enable best sellers page on your front office even when its respective module is disabled.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'TB_GOOGLE_MAPS_API_KEY' => [
                        'title' => $this->l('Google Maps API Key'),
                        'desc'  => $this->l('Add an API key to display Google Maps properly'),
                        'cast'  => 'strval',
                        'type'  => 'text',
                        'class' => 'fixed-width-xxl',
                    ],
                    'PS_MULTISHOP_FEATURE_ACTIVE' => [
                        'title'      => $this->l('Enable Multistore'),
                        'desc'       => $this->l('The multistore feature allows you to manage several e-shops with one Back Office. If this feature is enabled, a "Multistore" page will be available in the "Advanced Parameters" menu.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_SHOP_ACTIVITY'            => [
                        'title'      => $this->l('Main Shop Activity'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => $activities2,
                        'identifier' => 'value',
                    ],
                    'TB_EXPORT_FIELD_DELIMITER' => [
                        'title'        => $this->l('Export field delimiter'),
                        'desc'         => $this->l('Separator for exporting lists to CSV file'),
                        'cast'         => 'strval',
                        'type'         => 'text',
                        'class'        => 'fixed-width-sm',
                        'defaultValue' => ','
                    ],
                ]
            );

            // No HTTPS activation if you haven't already.
            if (!Tools::usingSecureMode() && !Configuration::get('PS_SSL_ENABLED')) {
                $fields['PS_SSL_ENABLED']['type'] = 'disabled';
                $fields['PS_SSL_ENABLED']['disabled'] = '<a class="btn btn-link" href="https://'.Tools::getShopDomainSsl().Tools::safeOutput($_SERVER['REQUEST_URI']).'">'.$this->l('Please click here to check if your shop supports HTTPS.').'</a>';
            }

            $this->fields_options = [
                'general' => [
                    'title'  => $this->l('General'),
                    'icon'   => 'icon-cogs',
                    'fields' => $fields,
                    'submit' => ['title' => $this->l('Save')],
                ],
            ];
        }

        parent::__construct();
    }

    /**
     * Enable / disable multishop menu if multishop feature is activated
     *
     * @param string $value
     *
     * @throws PrestaShopException
     */
    public function updateOptionPsMultishopFeatureActive($value)
    {
        Configuration::updateValue('PS_MULTISHOP_FEATURE_ACTIVE', $value);

        $tab = Tab::getInstanceFromClassName('AdminShopGroup');
        $tab->active = (bool) Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');
        $tab->update();
    }
}
