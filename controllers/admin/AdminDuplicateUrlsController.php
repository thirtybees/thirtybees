<?php
/**
 * 2017 thirty bees
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
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

class AdminDuplicateUrlsControllerCore extends AdminController
{
    /**
     * AdminDuplicateUrlsController constructor.
     */
    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();
    }

    /**
     * Initialize content
     */
    public function initContent()
    {
        parent::initContent();
        $this->context->smarty->assign(
            [
                'languages'            => Language::getLanguages(true, $this->context->shop->id),
                'duplicates_languages' => $this->getDuplicates(),
            ]
        );
        $this->context->smarty->assign(
            'content',
            $this->context->smarty->fetch('controllers/duplicate_urls/duplicates.tpl')
        );
    }

    /**
     * Get all duplicate URLS
     * Format: a_type, a_id, a_view (url to fix), b_type, ..., a_url
     *
     * @return array
     */
    public function getDuplicates()
    {
        $urls = [];
        $languages = Language::getLanguages(true, $this->context->shop->id, true);
        foreach ($languages as $idLang) {
            $urls[(int) $idLang] = [];
            $urls[(int) $idLang] = array_merge($urls[(int) $idLang], $this->getProductUrls($idLang));
            $urls[(int) $idLang] = array_merge($urls[(int) $idLang], $this->getCategoryUrls($idLang));
            $urls[(int) $idLang] = array_merge($urls[(int) $idLang], $this->getCmsUrls($idLang));
            $urls[(int) $idLang] = array_merge($urls[(int) $idLang], $this->getCmsCategoryUrls($idLang));
            $urls[(int) $idLang] = array_merge($urls[(int) $idLang], $this->getSupplierUrls($idLang));
            $urls[(int) $idLang] = array_merge($urls[(int) $idLang], $this->getManufacturerUrls($idLang));
        }
        $duplicates = [];
        foreach ($languages as $idLang) {
            $duplicatesLang = [];
            $uniqueUrls = [];
            $uniques = [];
            foreach ($urls[(int) $idLang] as $item) {
                if (!in_array($item['url'], $uniqueUrls)) {
                    $uniqueUrls[] = $item['url'];
                    $uniques[] = $item;
                } else {
                    $item1 = [];
                    foreach ($uniques as $unique) {
                        if ($unique['url'] === $item['url']) {
                            $item1 = $unique;
                        }
                    }
                    if (!empty($item1)) {
                        $items = array_merge($this->processItem($item1, 'a'), $this->processItem($item, 'b'));
                        $duplicatesLang[] = $items;
                    }
                }
            }
            $duplicates[(int) $idLang] = $duplicatesLang;
        }

        return $duplicates;
    }

    /**
     * Get all product URLs for the selected language
     *
     * @param int $idLang
     *
     * @return array
     * @throws PrestaShopException
     */
    private function getProductUrls($idLang)
    {
        if (empty($idLang)) {
            return [];
        }

        $productUrls = [];
        foreach (Product::getProducts($idLang, 0, 0, 'id_product', 'DESC') as $product) {
            $productInfo = [
                'id_product' => $product['id_product'],
                'url'        => $this->context->link->getProductLink($product['id_product'], null, null, null, $idLang),
            ];
            $productUrls[] = $productInfo;
        }

        return $productUrls;
    }

    /**
     * Get all category URLs for the selected language
     *
     * @param int $idLang
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    private function getCategoryUrls($idLang)
    {
        if (empty($idLang)) {
            return [];
        }

        $sql = 'SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `active` = 1';
        $categories = Db::getInstance()->executeS($sql);
        if (empty($categories)) {
            return [];
        }

        $categoryUrls = [];
        foreach ($categories as $category) {
            $categoryInfo = [
                'id_category' => $category['id_category'],
                'url'         => $this->context->link->getCategoryLink($category['id_category'], null, $idLang),
            ];
            $categoryUrls[] = $categoryInfo;
        }

        return $categoryUrls;
    }

    /**
     * Get all CMS URLs for the selected language
     *
     * @param $idLang
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    private function getCmsUrls($idLang)
    {
        if (empty($idLang)) {
            return [];
        }

        $sql = 'SELECT `id_cms` FROM `'._DB_PREFIX_.'cms`';
        $cmss = Db::getInstance()->executeS($sql);
        if (empty($cmss)) {
            return [];
        }

        $cmsUrls = [];
        foreach ($cmss as $cms) {
            $cmsInfo = [
                'id_cms' => $cms['id_cms'],
                'url'    => $this->context->link->getCMSLink($cms['id_cms'], null, $idLang),
            ];
            $cmsUrls[] = $cmsInfo;
        }

        return $cmsUrls;
    }

    /**
     * Get all CMS category URLs for the selected language
     *
     * @param $idLang
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    private function getCmsCategoryUrls($idLang)
    {
        if (empty($idLang)) {
            return [];
        }

        $sql = 'SELECT `id_cms_category` FROM `'._DB_PREFIX_.'cms_category`';
        $categories = Db::getInstance()->executeS($sql);
        if (empty($categories)) {
            return [];
        }

        $categoryUrls = [];
        foreach ($categories as $category) {
            $categoryInfo = [
                'id_cms_category' => $category['id_cms_category'],
                'url'             => $this->context->link->getCMSCategoryLink($category['id_cms_category'], null, $idLang),
            ];
            $categoryUrls[] = $categoryInfo;
        }

        return $categoryUrls;
    }

    /**
     * Get all supplier URLs for the selected language
     *
     * @param $idLang
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    private function getSupplierUrls($idLang)
    {
        if (empty($idLang)) {
            return [];
        }

        $sql = 'SELECT `id_supplier` FROM `'._DB_PREFIX_.'supplier`';
        $suppliers = Db::getInstance()->executeS($sql);
        if (empty($suppliers)) {
            return [];
        }

        $supplierUrls = [];
        foreach ($suppliers as $supplier) {
            $supplierInfo = [
                'id_supplier' => $supplier['id_supplier'],
                'url'         => $this->context->link->getSupplierLink($supplier['id_supplier'], null, $idLang),
            ];
            $supplierUrls[] = $supplierInfo;
        }

        return $supplierUrls;
    }

    /**
     * Get all manufacturer URLs for the selected language
     *
     * @param int $idLang
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    private function getManufacturerUrls($idLang)
    {
        if (empty($idLang)) {
            return [];
        }

        $sql = 'SELECT `id_manufacturer` FROM `'._DB_PREFIX_.'manufacturer`';
        $manufacturers = Db::getInstance()->executeS($sql);
        if (empty($manufacturers)) {
            return [];
        }

        $manufacturerUrls = [];
        foreach ($manufacturers as $manufacturer) {
            $manufacturerInfo = [
                'id_manufacturer' => $manufacturer['id_manufacturer'],
                'url'             => $this->context->link->getManufacturerLink($manufacturer['id_manufacturer'], null, $idLang),
            ];
            $manufacturerUrls[] = $manufacturerInfo;
        }

        return $manufacturerUrls;
    }

    protected function processItem($item, $prefix = 'a')
    {
        $link = $this->context->link;
        reset($item);
        switch (key($item)) {
            case 'id_product':
                $item[$prefix.'_type'] = Translate::getAdminTranslation('Product', 'AdminProducts');
                $item[$prefix.'_id'] = $item['id_product'];
                $item[$prefix.'_view'] = $link->getAdminLink('AdminProducts', false).'&id_product='.
                    (int) $item['id_product'].'&updateproduct&token='.Tools::getAdminTokenLite('AdminProducts');
                unset($item['id_product']);
                break;
            case 'id_category':
                $item[$prefix.'_type'] = Translate::getAdminTranslation('Category', 'AdminCategories');
                $item[$prefix.'_id'] = $item['id_category'];
                $item[$prefix.'_view'] = $link->getAdminLink('AdminCategories', false).'&id_category='.
                    (int) $item['id_category'].'&updatecategory&token='.Tools::getAdminTokenLite('AdminCategories');
                unset($item['id_category']);
                break;
            case 'id_supplier':
                $item[$prefix.'_type'] = Translate::getAdminTranslation('Supplier', 'AdminSuppliers');
                $item[$prefix.'_id'] = $item['id_supplier'];
                $item[$prefix.'_view'] = $link->getAdminLink('AdminSuppliers', false).'&id_supplier='.
                    (int) $item['id_supplier'].'&updatesupplier&token='.Tools::getAdminTokenLite('AdminSuppliers');
                unset($item['id_supplier']);
                break;
            case 'id_manufacturer':
                $item[$prefix.'_type'] = Translate::getAdminTranslation('Manufacturer', 'AdminManufacturers');
                $item[$prefix.'_id'] = $item['id_manufacturer'];
                $item[$prefix.'_view'] = $link->getAdminLink('AdminManufacturers', false).'&id_manufacturer='.
                    (int) $item['id_manufacturer'].'&updateproduct&token='.
                    Tools::getAdminTokenLite('AdminManufacturers');
                unset($item['id_manufacturer']);
                break;
            case 'id_cms':
                $item[$prefix.'_type'] = Translate::getAdminTranslation('CMS', 'AdminCmsContent');
                $item[$prefix.'_id'] = $item['id_cms'];
                $item[$prefix.'_view'] = $link->getAdminLink('AdminCmsContent', false).'&id_cms='.
                    (int) $item['id_cms'].'&updatecms&token='.
                    Tools::getAdminTokenLite('AdminCmsContent');
                unset($item['id_cms']);
                break;
            case 'id_cms_category':
                $item[$prefix.'_type'] = Translate::getAdminTranslation('CMS Category', 'AdminCmsCategories');
                $item[$prefix.'_id'] = $item['id_cms_category'];
                $item[$prefix.'_view'] = $link->getAdminLink('AdminCmsContent', false).'&id_cms_category='.
                    (int) $item['id_cms_category'].'&updatecms_category&token='.
                    Tools::getAdminTokenLite('AdminCmsContent');
                unset($item['id_cms_category']);
                break;
        }
        $item[$prefix.'_url'] = $item['url'];
        unset($item['url']);

        return $item;
    }
}
