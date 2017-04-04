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
 * Class AdminRedirectsControllerCore
 *
 * @since 1.0.0
 */
class AdminRedirectsControllerCore extends AdminController
{
    /**
     * AdminCartsControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'url_rewrite';
        $this->className = 'UrlRewrite';
        $this->lang = false;
        $this->explicitSelect = false;

        $this->addRowAction('delete');
        $this->allow_export = true;

        if (Tools::getValue('action') && Tools::getValue('action') == 'filterOnlyAbandonedCarts') {
            $this->_having = 'status = \''.$this->l('Abandoned cart').'\'';
        } else {
            $this->_use_found_rows = false;
        }

        $this->fields_list = [
            'id_url_rewrite'  => [
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'entity' => [
                'title'        => $this->l('Entity type'),
                'align'        => 'text-center',
                'callback'     => 'replaceEntityTypeWithText',
                'havingFilter' => true,
            ],
            'id_entity'   => [
                'title'        => $this->l('Entity ID'),
                'align'        => 'text-center',
                'havingFilter' => true,
            ],
            'id_lang'    => [
                'title'        => $this->l('Language ID'),
                'align'        => 'text-center',
                'havingFilter' => true,
            ],
            'id_shop'  => [
                'title'      => $this->l('Shop Id'),
                'align'      => 'text-center',
                'havingFilter' => true,
            ],
            'rewrite' => [
                'title'      => $this->l('Rewrite'),
                'align'      => 'text-left',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        parent::__construct();
    }

    /**
     * @param $echo
     * @param $tr
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function replaceEntityTypeWithText($echo, $tr)
    {
        switch ($echo) {
            case UrlRewrite::ENTITY_PRODUCT:
                return Translate::getAdminTranslation('Product');
            case UrlRewrite::ENTITY_CATEGORY:
                return Translate::getAdminTranslation('Product');
            case UrlRewrite::ENTITY_SUPPLIER:
                return Translate::getAdminTranslation('Product');
            case UrlRewrite::ENTITY_MANUFACTURER:
                return Translate::getAdminTranslation('Product');
            case UrlRewrite::ENTITY_CMS:
                return Translate::getAdminTranslation('Product');
            case UrlRewrite::ENTITY_CMS_CATEGORY:
                return Translate::getAdminTranslation('Product');
            default:
                return $echo;
        }
    }

    /**
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['exporturl_rewrite'] = [
                'href' => static::$currentIndex.'&exporturl_rewrite&token='.$this->token,
                'desc' => $this->l('Export redirects', null, null, false),
                'icon' => 'process-icon-export',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @param null $token
     * @param      $id
     * @param null $name
     *
     * @return string|void
     *
     * @since 1.0.0
     */
    public function displayDeleteLink($token = null, $id, $name = null)
    {
        // don't display ordered carts
        foreach ($this->_list as $row) {
            if ($row['id_cart'] == $id && isset($row['id_order']) && is_numeric($row['id_order'])) {
                return;
            }
        }

        return $this->helper->displayDeleteLink($token, $id, $name);
    }
}
