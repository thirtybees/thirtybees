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
 * Class ManufacturerControllerCore
 */
class ManufacturerControllerCore extends FrontController
{
    /** @var string $php_self */
    public $php_self = 'manufacturer';
    /** @var Manufacturer */
    protected $manufacturer;

    /**
     * Set media
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_.'product_list.css');
    }

    /**
     * Initialize this controller
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function init()
    {
        parent::init();

        if ($idManufacturer = Tools::getIntValue('id_manufacturer')) {
            $this->manufacturer = new Manufacturer((int) $idManufacturer, $this->context->language->id);
            if (!Validate::isLoadedObject($this->manufacturer) || !$this->manufacturer->active || !$this->manufacturer->isAssociatedToShop()) {
                header('HTTP/1.1 404 Not Found');
                header('Status: 404 Not Found');
                $this->errors[] = Tools::displayError('The manufacturer does not exist.');
            } else {
                $this->canonicalRedirection();
            }
        }
    }

    /**
     * Canonical redirection
     *
     * @param string $canonicalURL
     *
     * @throws PrestaShopException
     */
    public function canonicalRedirection($canonicalURL = '')
    {
        if (Tools::getValue('live_edit')) {
            return;
        }
        if (Validate::isLoadedObject($this->manufacturer)) {
            parent::canonicalRedirection($this->context->link->getManufacturerLink($this->manufacturer));
        }
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        parent::initContent();

        if (Validate::isLoadedObject($this->manufacturer) && $this->manufacturer->active && $this->manufacturer->isAssociatedToShop()) {
            $this->productSort();
            $this->assignOne();
            $this->setTemplate(_PS_THEME_DIR_.'manufacturer.tpl');
        } else {
            $this->assignAll();
            $this->setTemplate(_PS_THEME_DIR_.'manufacturer-list.tpl');
        }
    }

    /**
     * Assign template vars if displaying one manufacturer
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function assignOne()
    {
        $nbProducts = $this->manufacturer->getProducts($this->manufacturer->id, null, null, null, $this->orderBy, $this->orderWay, true);
        $this->pagination((int) $nbProducts);

        $products = $this->manufacturer->getProducts($this->manufacturer->id, $this->context->language->id, (int) $this->p, (int) $this->n, $this->orderBy, $this->orderWay);
        $this->addColorsToProductList($products);

        $this->context->smarty->assign(
            [
                'nb_products'         => $nbProducts,
                'products'            => $products,
                'path'                => ($this->manufacturer->active ? Tools::safeOutput($this->manufacturer->name) : ''),
                'manufacturer'        => $this->manufacturer,
                'comparator_max_item' => Configuration::get('PS_COMPARATOR_MAX_ITEM'),
                'body_classes'        => [$this->php_self.'-'.$this->manufacturer->id, $this->php_self.'-'.$this->manufacturer->link_rewrite],
            ]
        );
    }

    /**
     * Assign template vars if displaying the manufacturer list
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function assignAll()
    {
        if (Configuration::get('PS_DISPLAY_SUPPLIERS')) {
            $data = Manufacturer::getManufacturers(false, $this->context->language->id, true, false, false, false);
            $nbProducts = count($data);
            $this->pagination($nbProducts);
            $data = Manufacturer::getManufacturers(true, $this->context->language->id, true, $this->p, $this->n, false);

            foreach ($data as &$item) {
                $item['image'] = (!file_exists(_PS_MANU_IMG_DIR_.$item['id_manufacturer'].'-'.ImageType::getFormatedName('medium').'.jpg')) ? $this->context->language->iso_code.'-default' : $item['id_manufacturer'];
            }

            $this->context->smarty->assign(
                [
                    'pages_nb'         => ceil($nbProducts / (int) $this->n),
                    'nbManufacturers'  => $nbProducts,
                    'mediumSize'       => Image::getSize(ImageType::getFormatedName('medium')),
                    'manufacturers'    => $data,
                    'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
                ]
            );
        } else {
            $this->context->smarty->assign('nbManufacturers', 0);
        }
    }

    /**
     * Get instance of current manufacturer
     *
     * @return Manufacturer
     */
    public function getManufacturer()
    {
        return $this->manufacturer;
    }

    /**
     * @param int $shopId
     * @param int $languageId
     *
     * @return string|null
     * @throws PrestaShopException
     */
    protected function getCurrentPageAlternateUrl(int $shopId, int $languageId)
    {
        if (Validate::isLoadedObject($this->manufacturer)) {
            return $this->context->link->getManufacturerLink($this->manufacturer->id, null, $languageId, $shopId);
        } else {
            return $this->context->link->getPageLink('manufacturer', null, $languageId, null, false, $shopId);
        }
    }

    /**
     * @return string|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getCurrentPagePrevNextRelTags()
    {
        if (Validate::isLoadedObject($this->manufacturer)) {
            return $this->getRelPrevNext('manufacturer', $this->manufacturer->id);
        }
        return null;
    }
}
