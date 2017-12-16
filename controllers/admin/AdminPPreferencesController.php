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
 * Class AdminPPreferencesControllerCore
 *
 * @since 1.0.0
 */
class AdminPPreferencesControllerCore extends AdminController
{
    /**
     * AdminPPreferencesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        $warehouseList = Warehouse::getWarehouses();
        $warehouseNo = [['id_warehouse' => 0, 'name' => $this->l('No default warehouse (default setting)')]];
        $warehouseList = array_merge($warehouseNo, $warehouseList);

        $this->fields_options = [
            'products'            => [
                'title'  => $this->l('Products (general)'),
                'fields' => [
                    'PS_CATALOG_MODE'                => [
                        'title'      => $this->l('Catalog mode'),
                        'hint'       => $this->l('When active, all shopping features will be disabled.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'PS_COMPARATOR_MAX_ITEM'         => [
                        'title'      => $this->l('Product comparison'),
                        'hint'       => $this->l('Set the maximum number of products that can be selected for comparison. Set to "0" to disable this feature.'),
                        'validation' => 'isUnsignedId',
                        'required'   => true,
                        'cast'       => 'intval',
                        'type'       => 'text',
                    ],
                    'PS_NB_DAYS_NEW_PRODUCT'         => [
                        'title'      => $this->l('Number of days for which the product is considered \'new\''),
                        'validation' => 'isUnsignedInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                    ],
                    'PS_CART_REDIRECT'               => [
                        'title'      => $this->l('Redirect after adding product to cart'),
                        'hint'       => $this->l('Only for non-AJAX versions of the cart.'),
                        'cast'       => 'intval',
                        'show'       => true,
                        'required'   => false,
                        'type'       => 'radio',
                        'validation' => 'isBool',
                        'choices'    => [
                            0 => $this->l('Previous page'),
                            1 => $this->l('Cart summary'),
                        ],
                    ],
                    'PS_PRODUCT_SHORT_DESC_LIMIT'    => [
                        'title'      => $this->l('Max size of short description'),
                        'hint'       => $this->l('Set the maximum size of product short description (in characters).'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('characters'),
                    ],
                    'PS_QTY_DISCOUNT_ON_COMBINATION' => [
                        'title'      => $this->l('Quantity discounts based on'),
                        'hint'       => $this->l('How to calculate quantity discounts.'),
                        'cast'       => 'intval',
                        'show'       => true,
                        'required'   => false,
                        'type'       => 'radio',
                        'validation' => 'isBool',
                        'choices'    => [
                            0 => $this->l('Products'),
                            1 => $this->l('Combinations'),
                        ],
                    ],
                    'PS_FORCE_FRIENDLY_PRODUCT'      => [
                        'title'      => $this->l('Force update of friendly URL'),
                        'hint'       => $this->l('When active, friendly URL will be updated on every save.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'order_by_pagination' => [
                'title'  => $this->l('Pagination'),
                'fields' => [
                    'PS_PRODUCTS_PER_PAGE'  => [
                        'title'      => $this->l('Products per page'),
                        'hint'       => $this->l('Number of products displayed per page. Default is 10.'),
                        'validation' => 'isUnsignedInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                    ],
                    'PS_PRODUCTS_ORDER_BY'  => [
                        'title'      => $this->l('Default order by'),
                        'hint'       => $this->l('The order in which products are displayed in the product list.'),
                        'type'       => 'select',
                        'list'       => [
                            ['id' => '0', 'name' => $this->l('Product name')],
                            ['id' => '1', 'name' => $this->l('Product price')],
                            ['id' => '2', 'name' => $this->l('Product add date')],
                            ['id' => '3', 'name' => $this->l('Product modified date')],
                            ['id' => '4', 'name' => $this->l('Position inside category')],
                            ['id' => '5', 'name' => $this->l('Manufacturer')],
                            ['id' => '6', 'name' => $this->l('Product quantity')],
                            ['id' => '7', 'name' => $this->l('Product reference')],
                        ],
                        'identifier' => 'id',
                    ],
                    'PS_PRODUCTS_ORDER_WAY' => [
                        'title'      => $this->l('Default order method'),
                        'hint'       => $this->l('Default order method for product list.'),
                        'type'       => 'select',
                        'list'       => [
                            [
                                'id'   => '0',
                                'name' => $this->l('Ascending'),
                            ],
                            [
                                'id'   => '1',
                                'name' => $this->l('Descending'),
                            ],
                        ],
                        'identifier' => 'id',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'fo_product_page'     => [
                'title'  => $this->l('Product page'),
                'fields' => [
                    'PS_DISPLAY_QTIES'              => [
                        'title'      => $this->l('Display available quantities on the product page'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'PS_LAST_QTIES'                 => [
                        'title'      => $this->l('Display remaining quantities when the quantity is lower than'),
                        'hint'       => $this->l('Set to "0" to disable this feature.'),
                        'validation' => 'isUnsignedId',
                        'required'   => true,
                        'cast'       => 'intval',
                        'type'       => 'text',
                    ],
                    'PS_DISPLAY_JQZOOM'             => [
                        'title'      => $this->l('Enable JqZoom instead of Fancybox on the product page'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'PS_DISP_UNAVAILABLE_ATTR'      => [
                        'title'      => $this->l('Display unavailable product attributes on the product page'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'PS_ATTRIBUTE_CATEGORY_DISPLAY' => [
                        'title'      => $this->l('Display the "add to cart" button when a product has attributes'),
                        'hint'       => $this->l('Display or hide the "add to cart" button on category pages for products that have attributes forcing customers to see product details.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_ATTRIBUTE_ANCHOR_SEPARATOR' => [
                        'title'      => $this->l('Separator of attribute anchor on the product links'),
                        'type'       => 'select',
                        'list'       => [
                            ['id' => '-', 'name' => '-'],
                            ['id' => ',', 'name' => ','],
                        ],
                        'identifier' => 'id',
                    ],
                    'PS_DISPLAY_DISCOUNT_PRICE'     => [
                        'title'      => $this->l('Display discounted price'),
                        'desc'       => $this->l('In the volume discounts board, display the new price with the applied discount instead of showing the discount (ie. "-5%").'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'PS_SHOW_CONDITION'             => [
                        'title'      => $this->l('Show condition'),
                        'hint'       => $this->l('Show/Hide condition on product page.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'stock'               => [
                'title'  => $this->l('Products stock'),
                'fields' => [
                    'PS_ORDER_OUT_OF_STOCK'            => [
                        'title'      => $this->l('Allow ordering of out-of-stock products'),
                        'hint'       => $this->l('By default, the Add to Cart button is hidden when a product is unavailable. You can choose to have it displayed in all cases.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'PS_STOCK_MANAGEMENT'              => [
                        'title'      => $this->l('Enable stock management'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                        'js'         => [
                            'on'  => 'onchange="stockManagementActivationAuthorization()"',
                            'off' => 'onchange="stockManagementActivationAuthorization()"',
                        ],
                    ],
                    'PS_ADVANCED_STOCK_MANAGEMENT'     => [
                        'title'      => $this->l('Enable advanced stock management'),
                        'hint'       => $this->l('Allows you to manage physical stock, warehouses and supply orders in a new Stock menu.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                        'visibility' => Shop::CONTEXT_ALL,
                        'js'         => [
                            'on'  => 'onchange="advancedStockManagementActivationAuthorization()"',
                            'off' => 'onchange="advancedStockManagementActivationAuthorization()"',
                        ],
                    ],
                    'PS_FORCE_ASM_NEW_PRODUCT'         => [
                        'title'      => $this->l('New products use advanced stock management'),
                        'hint'       => $this->l('New products will automatically use advanced stock management and depends on stock, but no warehouse will be selected'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_DEFAULT_WAREHOUSE_NEW_PRODUCT' => [
                        'title'      => $this->l('Default warehouse on new products'),
                        'hint'       => $this->l('Automatically set a default warehouse when new product is created'),
                        'type'       => 'select',
                        'list'       => $warehouseList,
                        'identifier' => 'id_warehouse',
                    ],
                    'PS_PACK_STOCK_TYPE'               => [
                        'title'      => $this->l('Default pack stock management'),
                        'type'       => 'select',
                        'list'       => [
                            [
                                'pack_stock' => 0,
                                'name'       => $this->l('Decrement pack only.'),
                            ],
                            [
                                'pack_stock' => 1,
                                'name'       => $this->l('Decrement products in pack only.'),
                            ],
                            [
                                'pack_stock' => 2,
                                'name'       => $this->l('Decrement both.'),
                            ],
                        ],
                        'identifier' => 'pack_stock',
                    ],
                ],
                'bottom' => '<script type="text/javascript">stockManagementActivationAuthorization();advancedStockManagementActivationAuthorization();</script>',
                'submit' => ['title' => $this->l('Save')],
            ],
        ];
    }

    /**
     * Before updating options
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function beforeUpdateOptions()
    {
        if (!Tools::getValue('PS_STOCK_MANAGEMENT', true)) {
            $_POST['PS_ORDER_OUT_OF_STOCK'] = 1;
            $_POST['PS_DISPLAY_QTIES'] = 0;
        }

        // if advanced stock management is disabled, updates concerned tables
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') == 1 && (int) Tools::getValue('PS_ADVANCED_STOCK_MANAGEMENT') == 0) {
            $idShopList = Shop::getContextListShopID();
            $sqlShop = 'UPDATE `'._DB_PREFIX_.'product_shop` SET `advanced_stock_management` = 0 WHERE
			`advanced_stock_management` = 1 AND (`id_shop` = '.implode(' OR `id_shop` = ', $idShopList).')';

            $sqlStock = 'UPDATE `'._DB_PREFIX_.'stock_available` SET `depends_on_stock` = 0, `quantity` = 0
					 WHERE `depends_on_stock` = 1 AND (`id_shop` = '.implode(' OR `id_shop` = ', $idShopList).')';

            $sql = 'UPDATE `'._DB_PREFIX_.'product` SET `advanced_stock_management` = 0 WHERE
			`advanced_stock_management` = 1 AND (`id_shop_default` = '.implode(' OR `id_shop_default` = ', $idShopList).')';

            Db::getInstance()->execute($sqlShop);
            Db::getInstance()->execute($sqlStock);
            Db::getInstance()->execute($sql);
        }

        if (Tools::getIsset('PS_CATALOG_MODE')) {
            Tools::clearSmartyCache();
            Media::clearCache();
        }
    }
}
