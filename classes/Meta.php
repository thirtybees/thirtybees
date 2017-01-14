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
 * Class MetaCore
 *
 * @since 1.0.0
 */
class MetaCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    public $page;
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
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getPages($excludeFilled = false, $addPage = false)
    {
        $selectedPages = [];
        if (!$files = Tools::scandir(_PS_CORE_DIR_.DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR.'front'.DIRECTORY_SEPARATOR, 'php', '', true)) {
            die(Tools::displayError('Cannot scan "root" directory'));
        }

        if (!$overrideFiles = Tools::scandir(_PS_CORE_DIR_.DIRECTORY_SEPARATOR.'override'.DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR.'front'.DIRECTORY_SEPARATOR, 'php', '', true)) {
            die(Tools::displayError('Cannot scan "override" directory'));
        }

        $files = array_values(array_unique(array_merge($files, $overrideFiles)));

        // Exclude pages forbidden
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

        // Add modules controllers to list (this function is cool !)
        foreach (glob(_PS_MODULE_DIR_.'*/controllers/front/*.php') as $file) {
            $filename = Tools::strtolower(basename($file, '.php'));
            if ($filename == 'index') {
                continue;
            }

            $module = Tools::strtolower(basename(dirname(dirname(dirname($file)))));
            $selectedPages[$module.' - '.$filename] = 'module-'.$module.'-'.$filename;
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMetas()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM '._DB_PREFIX_.'meta ORDER BY page ASC');
    }

    /**
     * @param $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMetasByIdLang($idLang)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT *
		FROM `'._DB_PREFIX_.'meta` m
		LEFT JOIN `'._DB_PREFIX_.'meta_lang` ml ON m.`id_meta` = ml.`id_meta`
		WHERE ml.`id_lang` = '.(int) $idLang
            .Shop::addSqlRestrictionOnLang('ml').
            'ORDER BY PAGE ASC'
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
     */
    public static function getEquivalentUrlRewrite($newIdLang, $idLang, $urlRewrite)
    {
        return Db::getInstance()->getValue(
            '
		SELECT url_rewrite
		FROM `'._DB_PREFIX_.'meta_lang`
		WHERE id_meta = (
			SELECT id_meta
			FROM `'._DB_PREFIX_.'meta_lang`
			WHERE url_rewrite = \''.pSQL($urlRewrite).'\' AND id_lang = '.(int) $idLang.'
			AND id_shop = '.Context::getContext()->shop->id.'
		)
		AND id_lang = '.(int) $newIdLang.'
		AND id_shop = '.Context::getContext()->shop->id
        );
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param        $idLang
     * @param        $pageName
     * @param string $title
     *
     * @return array
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProductMetas($idProduct, $idLang, $pageName)
    {
        $sql = 'SELECT `name`, `meta_title`, `meta_description`, `meta_keywords`, `description_short`
				FROM `'._DB_PREFIX_.'product` p
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (pl.`id_product` = p.`id_product`'.Shop::addSqlRestrictionOnLang('pl').')
				'.Shop::addSqlAssociation('product', 'p').'
				WHERE pl.id_lang = '.(int) $idLang.'
					AND pl.id_product = '.(int) $idProduct.'
					AND product_shop.active = 1';
        if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql)) {
            if (empty($row['meta_description'])) {
                $row['meta_description'] = strip_tags($row['description_short']);
            }

            return Meta::completeMetaTags($row, $row['name']);
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    /**
     * @param         $metaTags
     * @param         $defaultValue
     * @param Context $context
     *
     * @return
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param $page
     * @param $idLang
     *
     * @return array|bool|null|object
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMetaByPage($page, $idLang)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            '
		SELECT *
		FROM '._DB_PREFIX_.'meta m
		LEFT JOIN '._DB_PREFIX_.'meta_lang ml ON m.id_meta = ml.id_meta
		WHERE (
			m.page = "'.pSQL($page).'"
			OR m.page = "'.pSQL(str_replace('-', '', strtolower($page))).'"
		)
		AND ml.id_lang = '.(int) $idLang.'
		'.Shop::addSqlRestrictionOnLang('ml')
        );
    }

    /**
     * Get category meta tags
     *
     * @param int    $idCategory
     * @param int    $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCategoryMetas($idCategory, $idLang, $pageName, $title = '')
    {
        if (!empty($title)) {
            $title = ' - '.$title;
        }
        $pageNumber = (int) Tools::getValue('p');
        $sql = 'SELECT `name`, `meta_title`, `meta_description`, `meta_keywords`, `description`
				FROM `'._DB_PREFIX_.'category_lang` cl
				WHERE cl.`id_lang` = '.(int) $idLang.'
					AND cl.`id_category` = '.(int) $idCategory.Shop::addSqlRestrictionOnLang('cl');

        $cacheId = 'Meta::getCategoryMetas'.(int) $idCategory.'-'.(int) $idLang;
        if (!Cache::isStored($cacheId)) {
            if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql)) {
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getManufacturerMetas($idManufacturer, $idLang, $pageName)
    {
        $page_number = (int) Tools::getValue('p');
        $sql = 'SELECT `name`, `meta_title`, `meta_description`, `meta_keywords`
				FROM `'._DB_PREFIX_.'manufacturer_lang` ml
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (ml.`id_manufacturer` = m.`id_manufacturer`)
				WHERE ml.id_lang = '.(int) $idLang.'
					AND ml.id_manufacturer = '.(int) $idManufacturer;
        if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql)) {
            if (!empty($row['meta_description'])) {
                $row['meta_description'] = strip_tags($row['meta_description']);
            }
            $row['meta_title'] = ($row['meta_title'] ? $row['meta_title'] : $row['name']).(!empty($page_number) ? ' ('.$page_number.')' : '');
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getSupplierMetas($idSupplier, $idLang, $pageName)
    {
        $sql = 'SELECT `name`, `meta_title`, `meta_description`, `meta_keywords`
				FROM `'._DB_PREFIX_.'supplier_lang` sl
				LEFT JOIN `'._DB_PREFIX_.'supplier` s ON (sl.`id_supplier` = s.`id_supplier`)
				WHERE sl.id_lang = '.(int) $idLang.'
					AND sl.id_supplier = '.(int) $idSupplier;
        if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql)) {
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCmsMetas($idCms, $idLang, $pageName)
    {
        $sql = 'SELECT `meta_title`, `meta_description`, `meta_keywords`
				FROM `'._DB_PREFIX_.'cms_lang`
				WHERE id_lang = '.(int) $idLang.'
					AND id_cms = '.(int) $idCms.
            ((int) Context::getContext()->shop->id ?
                ' AND id_shop = '.(int) Context::getContext()->shop->id : '');

        if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql)) {
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCmsCategoryMetas($idCmsCategory, $idLang, $pageName)
    {
        $sql = 'SELECT `meta_title`, `meta_description`, `meta_keywords`
				FROM `'._DB_PREFIX_.'cms_category_lang`
				WHERE id_lang = '.(int) $idLang.'
					AND id_cms_category = '.(int) $idCmsCategory.
            ((int) Context::getContext()->shop->id ?
                ' AND id_shop = '.(int) Context::getContext()->shop->id : '');
        if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql)) {
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
