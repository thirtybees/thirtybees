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
 * Class AdminShopGroupControllerCore
 *
 * @since 1.0.0
 */
class AdminShopGroupControllerCore extends AdminController
{
    /**
     * AdminShopGroupControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'shop_group';
        $this->className = 'ShopGroup';
        $this->lang = false;
        $this->multishop_context = Shop::CONTEXT_ALL;

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        if (!Tools::getValue('realedit')) {
            $this->deleted = false;
        }

        $this->show_toolbar = false;

        $this->fields_list = [
            'id_shop_group' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'          => [
                'title'      => $this->l('Shop group'),
                'width'      => 'auto',
                'filter_key' => 'a!name',
            ],
            /*'active' => array(
                'title' => $this->l('Enabled'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'filter_key' => 'active',
                'width' => 50,
            ),*/
        ];

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Multistore options'),
                'fields' => [
                    'PS_SHOP_DEFAULT' => [
                        'title'      => $this->l('Default shop'),
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'identifier' => 'id_shop',
                        'list'       => Shop::getShops(),
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        parent::__construct();
    }

    /**
     * @param bool $disable
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function viewAccess($disable = false)
    {
        return Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        parent::initContent();

        $this->addJqueryPlugin('cooki-plugin');
        $data = Shop::getTree();

        foreach ($data as $keyGroup => &$group) {
            foreach ($group['shops'] as $keyShop => &$shop) {
                $currentShop = new Shop($shop['id_shop']);
                $urls = $currentShop->getUrls();

                foreach ($urls as $keyUrl => &$url) {
                    $title = $url['domain'].$url['physical_uri'].$url['virtual_uri'];
                    if (strlen($title) > 23) {
                        $title = substr($title, 0, 23).'...';
                    }

                    $url['name'] = $title;
                    $shop['urls'][$url['id_shop_url']] = $url;
                }
            }
        }

        $shopsTree = new HelperTreeShops('shops-tree', $this->l('Multistore tree'));
        $shopsTree->setNodeFolderTemplate('shop_tree_node_folder.tpl')->setNodeItemTemplate('shop_tree_node_item.tpl')
            ->setHeaderTemplate('shop_tree_header.tpl')->setActions(
                [
                    new TreeToolbarLink(
                        'Collapse All',
                        '#',
                        '$(\'#'.$shopsTree->getId().'\').tree(\'collapseAll\'); return false;',
                        'icon-collapse-alt'
                    ),
                    new TreeToolbarLink(
                        'Expand All',
                        '#',
                        '$(\'#'.$shopsTree->getId().'\').tree(\'expandAll\'); return false;',
                        'icon-expand-alt'
                    ),
                ]
            )
            ->setAttribute('url_shop_group', $this->context->link->getAdminLink('AdminShopGroup'))
            ->setAttribute('url_shop', $this->context->link->getAdminLink('AdminShop'))
            ->setAttribute('url_shop_url', $this->context->link->getAdminLink('AdminShopUrl'))
            ->setData($data);
        $shopsTree = $shopsTree->render(null, false, false);

        if ($this->display == 'edit') {
            $this->toolbar_title[] = $this->object->name;
        }

        $this->context->smarty->assign(
            [
                'toolbar_scroll' => 1,
                'toolbar_btn'    => $this->toolbar_btn,
                'title'          => $this->toolbar_title,
                'shops_tree'     => $shopsTree,
            ]
        );
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        if ($this->display != 'add' && $this->display != 'edit') {
            $this->page_header_toolbar_btn['new'] = [
                'desc' => $this->l('Add a new shop group'),
                'href' => static::$currentIndex.'&add'.$this->table.'&token='.$this->token,
            ];
            $this->page_header_toolbar_btn['new_2'] = [
                'desc'     => $this->l('Add a new shop'),
                'href'     => $this->context->link->getAdminLink('AdminShop').'&addshop',
                'imgclass' => 'new_2',
                'icon'     => 'process-icon-new',
            ];
        }
    }

    /**
     * Initialize toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initToolbar()
    {
        parent::initToolbar();

        if ($this->display != 'add' && $this->display != 'edit') {
            $this->toolbar_btn['new'] = [
                'desc' => $this->l('Add a new shop group'),
                'href' => static::$currentIndex.'&add'.$this->table.'&token='.$this->token,
            ];
        }
    }

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend'      => [
                'title' => $this->l('Shop group'),
                'icon'  => 'icon-shopping-cart',
            ],
            'description' => $this->l('Warning: Enabling the "share customers" and "share orders" options is not recommended. Once activated and orders are created, you will not be able to disable these options. If you need these options, we recommend using several categories rather than several shops.'),
            'input'       => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Shop group name'),
                    'name'     => 'name',
                    'required' => true,
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Share customers'),
                    'name'     => 'share_customer',
                    'required' => true,
                    'class'    => 't',
                    'is_bool'  => true,
                    'disabled' => ($this->id_object && $this->display == 'edit' && ShopGroup::hasDependency($this->id_object, 'customer')) ? true : false,
                    'values'   => [
                        [
                            'id'    => 'share_customer_on',
                            'value' => 1,
                        ],
                        [
                            'id'    => 'share_customer_off',
                            'value' => 0,
                        ],
                    ],
                    'desc'     => $this->l('Once this option is enabled, the shops in this group will share customers. If a customer registers in any one of these shops, the account will automatically be available in the others shops of this group.').'<br/>'.$this->l('Warning: you will not be able to disable this option once you have registered customers.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Share available quantities to sell'),
                    'name'     => 'share_stock',
                    'required' => true,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'share_stock_on',
                            'value' => 1,
                        ],
                        [
                            'id'    => 'share_stock_off',
                            'value' => 0,
                        ],
                    ],
                    'desc'     => $this->l('Share available quantities between shops of this group. When changing this option, all available products quantities will be reset to 0.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Share orders'),
                    'name'     => 'share_order',
                    'required' => true,
                    'class'    => 't',
                    'is_bool'  => true,
                    'disabled' => ($this->id_object && $this->display == 'edit' && ShopGroup::hasDependency($this->id_object, 'order')) ? true : false,
                    'values'   => [
                        [
                            'id'    => 'share_order_on',
                            'value' => 1,
                        ],
                        [
                            'id'    => 'share_order_off',
                            'value' => 0,
                        ],
                    ],
                    'desc'     => $this->l('Once this option is enabled (which is only possible if customers and available quantities are shared among shops), the customer\'s cart will be shared by all shops in this group. This way, any purchase started in one shop will be able to be completed in another shop from the same group.').'<br/>'.$this->l('Warning: You will not be able to disable this option once you\'ve started to accept orders.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Status'),
                    'name'     => 'active',
                    'required' => true,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                        ],
                    ],
                    'desc'     => $this->l('Enable or disable this shop group?'),
                ],
            ],
            'submit'      => [
                'title' => $this->l('Save'),
            ],
        ];

        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        if (Shop::getTotalShops() > 1 && $obj->id) {
            $disabled = [
                'share_customer' => true,
                'share_stock'    => true,
                'share_order'    => true,
                'active'         => false,
            ];
        } else {
            $disabled = false;
        }

        $defaultShop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
        $this->tpl_form_vars = [
            'disabled'     => $disabled,
            'checked'      => (Tools::getValue('addshop_group') !== false) ? true : false,
            'defaultGroup' => $defaultShop->id_shop_group,
        ];

        $this->fields_value = [
            'active' => true,
        ];

        return parent::renderForm();
    }

    /**
     * Get list
     *
     * @param int         $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int         $start
     * @param int|null    $limit
     * @param int|bool    $idLangShop
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);
        $shopGroupDeleteList = [];

        // test store authorized to remove
        foreach ($this->_list as $shopGroup) {
            $shops = Shop::getShops(true, $shopGroup['id_shop_group']);
            if (!empty($shops)) {
                $shopGroupDeleteList[] = $shopGroup['id_shop_group'];
            }
        }
        $this->addRowActionSkipList('delete', $shopGroupDeleteList);
    }

    /**
     * Post processing
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('delete'.$this->table) || Tools::isSubmit('status') || Tools::isSubmit('status'.$this->table)) {
            /** @var ShopGroup $object */
            $object = $this->loadObject();

            if (ShopGroup::getTotalShopGroup() == 1) {
                $this->errors[] = Tools::displayError('You cannot delete or disable the last shop group.');
            } elseif ($object->haveShops()) {
                $this->errors[] = Tools::displayError('You cannot delete or disable a shop group in use.');
            }

            if (count($this->errors)) {
                return false;
            }
        }

        return parent::postProcess();
    }

    /**
     * Render options
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderOptions()
    {
        if ($this->fields_options && is_array($this->fields_options)) {
            $this->display = 'options';
            $this->show_toolbar = false;
            $helper = new HelperOptions($this);
            $this->setHelperDisplay($helper);
            $helper->id = $this->id;
            $helper->tpl_vars = $this->tpl_option_vars;
            $options = $helper->generateOptions($this->fields_options);

            return $options;
        }
    }

    /**
     * After add
     *
     * @param ShopGroup $newShopGroup
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function afterAdd($newShopGroup)
    {
        //Reset available quantitites
        StockAvailable::resetProductFromStockAvailableByShopGroup($newShopGroup);
    }

    /**
     * After update
     *
     * @param ShopGroup $newShopGroup
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function afterUpdate($newShopGroup)
    {
        //Reset available quantitites
        StockAvailable::resetProductFromStockAvailableByShopGroup($newShopGroup);
    }
}
