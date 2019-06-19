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
 * Class MetaCore
 *
 * @since 1.0.0
 */
class MetaCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    public $page;
    /**
     * @var bool $configurable
     *
     * True:  The meta is configurable by AdminMetaController.
     * False: The meta is only a helper for a theme meta.
     */
    public $configurable = 1;
    public $title;
    public $description;
    public $keywords;
    public $url_rewrite;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'          => 'meta',
        'primary'        => 'id_meta',
        'multilang'      => true,
        'multilang_shop' => true,
        'fields'         => [
            'page'         => ['type' => self::TYPE_STRING, 'validate' => 'isFileName', 'required' => true, 'size' => 64],
            'configurable' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],

            /* Lang fields */
            'title'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
            'description'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'keywords'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'url_rewrite'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'size' => 255],
        ],
    ];

    /**
     * @param bool $excludeFilled
     * @param bool $addPage
     * @param bool $forTheme      If true, return 'forbidden' pages as well.
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @version 1.0.0 Initial version.
     * @version 1.1.0 New parameter $forTheme.
     */
    public static function getPages(
        $excludeFilled = false,
        $addPage = false,
        $forTheme = false
    ) {
        $selectedPages = [];

        $files = Tools::scandir(_PS_FRONT_CONTROLLER_DIR_, 'php', '', true);
        if ( ! $files) {
            die(Tools::displayError('Cannot scan root directory'));
        }
        $overrideFiles = Tools::scandir(
            _PS_OVERRIDE_DIR_.'controllers/front/',
            'php', '', true
        );
        if ( ! $overrideFiles) {
            die(Tools::displayError('Cannot scan override directory'));
        }

        $files = array_values(array_unique(array_merge($files, $overrideFiles)));

        // Exclude pages forbidden.
        $exludePages = [];
        if ( ! $forTheme) {
            $exludePages = [
                'category',
                'changecurrency',
                'cms',
                'footer',
                'header',
                'pagination',
                'product',
                'product-sort',
                'statistics',
            ];
        }

        foreach ($files as $file) {
            if ($file != 'index.php' && !in_array(strtolower(str_replace('Controller.php', '', $file)), $exludePages)) {
                $className = str_replace('.php', '', $file);
                $reflection = class_exists($className) ? new ReflectionClass(str_replace('.php', '', $file)) : false;
                $properties = $reflection ? $reflection->getDefaultProperties() : [];
                if (isset($properties['php_self'])) {
                    $selectedPages[$properties['php_self']] = $properties['php_self'];
                } elseif (preg_match('/^[a-z0-9_.-]*\.php$/i', $file)) {
                    $selectedPages[strtolower(str_replace('Controller.php', '', $file))] = strtolower(str_replace('Controller.php', '', $file));
                } elseif (preg_match('/^([a-z0-9_.-]*\/)?[a-z0-9_.-]*\.php$/i', $file)) {
                    $selectedPages[strtolower(sprintf(Tools::displayError('%2$s (in %1$s)'), dirname($file), str_replace('Controller.php', '', basename($file))))] = strtolower(str_replace('Controller.php', '', basename($file)));
                }
            }
        }

        // Add module controllers to list.
        $moduleDirs = Module::getModulesDirOnDisk();
        foreach ($moduleDirs as $module) {
            if (Module::isInstalled($module)) {
                $path = _PS_MODULE_DIR_.$module.'/controllers/front/';
                if ( ! is_dir($path)) {
                    continue;
                }

                foreach (scandir($path) as $file) {
                    if (in_array($file, ['.', '..', 'index.php'])) {
                        continue;
                    }

                    $filename = mb_strtolower(basename($file, '.php'));
                    $selectedPages[$module.' - '.$filename]
                        = 'module-'.$module.'-'.$filename;
                }
            }
        }

        // Exclude page already filled
        if ($excludeFilled) {
            $metas = Meta::getMetas();
            foreach ($metas as $meta) {
                if (in_array($meta['page'], $selectedPages)) {
                    unset($selectedPages[array_search($meta['page'], $selectedPages)]);
                }
            }
        }
        // Add selected page
        if ($addPage) {
            $name = $addPage;
            if (preg_match('#module-([a-z0-9_-]+)-([a-z0-9]+)$#i', $addPage, $m)) {
                $addPage = $m[1].' - '.$m[2];
            }
            $selectedPages[$addPage] = $name;
            asort($selectedPages);
        }

        return $selectedPages;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMetas()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('meta')
                ->orderBy('`page` ASC')
        );
    }

    /**
     * @param int $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMetasByIdLang($idLang)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('meta', 'm')
                ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta`')
                ->where('ml.`id_lang` = '.(int) $idLang.' '.Shop::addSqlRestrictionOnLang('ml'))
                ->orderBy('`page` ASC')
        );
    }

    /**
     * @param int    $newIdLang
     * @param int    $idLang
     * @param string $urlRewrite
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public static function getEquivalentUrlRewrite($newIdLang, $idLang, $urlRewrite)
    {
        $metaSql = (new DbQuery())
            ->select('`id_meta`')
            ->from('meta_lang')
            ->where('`url_rewrite` = \''.pSQL($urlRewrite).'\'')
            ->where('`id_lang` = '.(int) $idLang)
            ->where('`id_shop` = '.(int) Context::getContext()->shop->id);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('url_rewrite')
                ->from('meta_lang')
                ->where('id_meta = ('.$metaSql->build().')')
                ->where('`id_lang` = '.(int) $newIdLang)
                ->where('`id_shop` = '.(int) Context::getContext()->shop->id)
        );
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param int    $idLang
     * @param string $pageName
     * @param string $title
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getMetaTags($idLang, $pageName, $title = '')
    {
        if (!(!Configuration::get('PS_SHOP_ENABLE')
            && !in_array(Tools::getRemoteAddr(), explode(',', Configuration::get('PS_MAINTENANCE_IP'))))
        ) {
            if ($pageName == 'product' && ($idProduct = Tools::getValue('id_product'))) {
                return Meta::getProductMetas($idProduct, $idLang, $pageName);
            } elseif ($pageName == 'category' && ($idCategory = Tools::getValue('id_category'))) {
                return Meta::getCategoryMetas($idCategory, $idLang, $pageName, $title);
            } elseif ($pageName == 'manufacturer' && ($idManufacturer = Tools::getValue('id_manufacturer'))) {
                return Meta::getManufacturerMetas($idManufacturer, $idLang, $pageName);
            } elseif ($pageName == 'supplier' && ($idSupplier = Tools::getValue('id_supplier'))) {
                return Meta::getSupplierMetas($idSupplier, $idLang, $pageName);
            } elseif ($pageName == 'cms' && ($idCms = Tools::getValue('id_cms'))) {
                return Meta::getCmsMetas($idCms, $idLang, $pageName);
            } elseif ($pageName == 'cms' && ($idCmsCategory = Tools::getValue('id_cms_category'))) {
                return Meta::getCmsCategoryMetas($idCmsCategory, $idLang, $pageName);
            }
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    /**
     * Get product meta tags
     *
     *
     * @param int    $idProduct
     * @param int    $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProductMetas($idProduct, $idLang, $pageName)
    {
        if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`name`, `meta_title`, `meta_description`, `meta_keywords`, `description_short`')
                ->from('product', 'p')
                ->join(Shop::addSqlAssociation('product', 'p'))
                ->leftJoin('product_lang', 'pl', 'pl.`id_product` = p.`id_product` '.Shop::addSqlRestrictionOnLang('pl'))
                ->where('pl.`id_lang` = '.(int) $idLang)
                ->where('pl.`id_product` = '.(int) $idProduct)
                ->where('product_shop.`active` = 1')
        )) {
            if (empty($row['meta_description'])) {
                $row['meta_description'] = strip_tags($row['description_short']);
            }

            return Meta::completeMetaTags($row, $row['name']);
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    /**
     * @param array   $metaTags
     * @param string  $defaultValue
     * @param Context $context
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function completeMetaTags($metaTags, $defaultValue, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        if (empty($metaTags['meta_title'])) {
            $metaTags['meta_title'] = $defaultValue.' - '.Configuration::get('PS_SHOP_NAME');
        }
        if (empty($metaTags['meta_description'])) {
            $metaTags['meta_description'] = Configuration::get('PS_META_DESCRIPTION', $context->language->id) ? Configuration::get('PS_META_DESCRIPTION', $context->language->id) : '';
        }
        if (empty($metaTags['meta_keywords'])) {
            $metaTags['meta_keywords'] = Configuration::get('PS_META_KEYWORDS', $context->language->id) ? Configuration::get('PS_META_KEYWORDS', $context->language->id) : '';
        }

        return $metaTags;
    }

    /**
     * Get meta tags for a given page
     *
     * @param int    $idLang
     * @param string $pageName
     *
     * @return array Meta tags
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getHomeMetas($idLang, $pageName)
    {
        $metas = Meta::getMetaByPage($pageName, $idLang);
        $ret['meta_title'] = (isset($metas['title']) && $metas['title']) ? $metas['title'].' - '.Configuration::get('PS_SHOP_NAME') : Configuration::get('PS_SHOP_NAME');
        $ret['meta_description'] = (isset($metas['description']) && $metas['description']) ? $metas['description'] : '';
        $ret['meta_keywords'] = (isset($metas['keywords']) && $metas['keywords']) ? $metas['keywords'] : '';

        return $ret;
    }

    /**
     * @param string $page
     * @param int    $idLang
     *
     * @return array|bool|null|object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMetaByPage($page, $idLang)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('meta', 'm')
                ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta`')
                ->where('m.`page` = \''.pSQL($page).'\' OR m.`page` = \''.pSQL(str_replace('_', '', strtolower($page))).'\'')
                ->where('ml.`id_lang` = '.(int) $idLang.' '.Shop::addSqlRestrictionOnLang('ml'))
        );
    }

    /**
     * Get category meta tags
     *
     * @param int    $idCategory
     * @param int    $idLang
     * @param string $pageName
     * @param string $title
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCategoryMetas($idCategory, $idLang, $pageName, $title = '')
    {
        if (!empty($title)) {
            $title = ' - '.$title;
        }
        $pageNumber = (int) Tools::getValue('p');
        $cacheId = 'Meta::getCategoryMetas'.(int) $idCategory.'-'.(int) $idLang;
        if (!Cache::isStored($cacheId)) {
            if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('`name`, `meta_title`, `meta_description`, `meta_keywords`, `description`')
                    ->from('category_lang', 'cl')
                    ->where('cl.`id_lang` = '.(int) $idLang)
                    ->where('cl.`id_category` = '.(int) $idCategory.' '.Shop::addSqlRestrictionOnLang('cl'))
            )) {
                if (empty($row['meta_description'])) {
                    $row['meta_description'] = strip_tags($row['description']);
                }

                // Paginate title
                if (!empty($row['meta_title'])) {
                    $row['meta_title'] = $title.$row['meta_title'].(!empty($pageNumber) ? ' ('.$pageNumber.')' : '').' - '.Configuration::get('PS_SHOP_NAME');
                } else {
                    $row['meta_title'] = $row['name'].(!empty($pageNumber) ? ' ('.$pageNumber.')' : '').' - '.Configuration::get('PS_SHOP_NAME');
                }

                if (!empty($title)) {
                    $row['meta_title'] = $title.(!empty($pageNumber) ? ' ('.$pageNumber.')' : '').' - '.Configuration::get('PS_SHOP_NAME');
                }

                $result = Meta::completeMetaTags($row, $row['name']);
            } else {
                $result = Meta::getHomeMetas($idLang, $pageName);
            }
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Get manufacturer meta tags
     *
     * @param int    $idManufacturer
     * @param int    $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getManufacturerMetas($idManufacturer, $idLang, $pageName)
    {
        $pageNumber = (int) Tools::getValue('p');
        if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`name`, `meta_title`, `meta_description`, `meta_keywords`')
                ->from('manufacturer_lang', 'ml')
                ->leftJoin('manufacturer', 'm', 'ml.`id_manufacturer` = m.`id_manufacturer`')
                ->where('ml.`id_lang` = '.(int) $idLang)
                ->where('ml.`id_manufacturer` = '.(int) $idManufacturer)
        )) {
            if (!empty($row['meta_description'])) {
                $row['meta_description'] = strip_tags($row['meta_description']);
            }
            $row['meta_title'] = ($row['meta_title'] ? $row['meta_title'] : $row['name']).(!empty($pageNumber) ? ' ('.$pageNumber.')' : '');
            $row['meta_title'] .= ' - '.Configuration::get('PS_SHOP_NAME');

            return Meta::completeMetaTags($row, $row['meta_title']);
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    /**
     * Get supplier meta tags
     *
     * @param int    $idSupplier
     * @param int    $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getSupplierMetas($idSupplier, $idLang, $pageName)
    {
        if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`name`, `meta_title`, `meta_description`, `meta_keywords`')
            ->from('supplier_lang', 'sl')
            ->leftJoin('supplier', 's', 'sl.`id_supplier` = s.`id_supplier`')
            ->where('sl.`id_lang` = '.(int) $idLang)
            ->where('sl.`id_supplier` = '.(int) $idSupplier)
        )) {
            if (!empty($row['meta_description'])) {
                $row['meta_description'] = strip_tags($row['meta_description']);
            }
            if (!empty($row['meta_title'])) {
                $row['meta_title'] = $row['meta_title'].' - '.Configuration::get('PS_SHOP_NAME');
            }

            return Meta::completeMetaTags($row, $row['name']);
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    /**
     * Get CMS meta tags
     *
     * @param int    $idCms
     * @param int    $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCmsMetas($idCms, $idLang, $pageName)
    {
        if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`meta_title`, `meta_description`, `meta_keywords`')
                ->from('cms_lang')
                ->where('`id_lang` = '.(int) $idLang)
                ->where('`id_cms` = '.(int) $idCms)
                ->where(Context::getContext()->shop->id ? '`id_shop` = '.(int) Context::getContext()->shop->id : '')
        )) {
            $row['meta_title'] = $row['meta_title'].' - '.Configuration::get('PS_SHOP_NAME');

            return Meta::completeMetaTags($row, $row['meta_title']);
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    /**
     * Get CMS category meta tags
     *
     * @param int    $idCmsCategory
     * @param int    $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCmsCategoryMetas($idCmsCategory, $idLang, $pageName)
    {
        if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`meta_title`, `meta_description`, `meta_keywords`')
            ->from('cms_category_lang')
            ->where('`id_lang` = '.(int) $idLang)
            ->where('`id_cms_category` = '.(int) $idCmsCategory)
            ->where(Context::getContext()->shop->id ? '`id_shop` = '.(int) Context::getContext()->shop->id : '')
        )) {
            $row['meta_title'] = $row['meta_title'].' - '.Configuration::get('PS_SHOP_NAME');

            return Meta::completeMetaTags($row, $row['meta_title']);
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($nullValues = false)
    {
        if (!parent::update($nullValues)) {
            return false;
        }

        return Tools::generateHtaccess();
    }

    /**
     * @param array $selection
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function deleteSelection($selection)
    {
        if (!is_array($selection)) {
            die(Tools::displayError());
        }
        $result = true;
        foreach ($selection as $id) {
            $this->id = (int) $id;
            $result = $result && $this->delete();
        }

        return $result && Tools::generateHtaccess();
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }

        return Tools::generateHtaccess();
    }
}
