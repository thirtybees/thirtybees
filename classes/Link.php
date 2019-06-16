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
 * Class LinkCore
 *
 * @since 1.0.0
 *
 * Backwards compatible properties and methods (accessed via magic methods):
 * @property array|null $category_disable_rewrite
 */
class LinkCore
{
    // @codingStandardsIgnoreStart
    public static $cache = ['page' => []];
    /** @var array|null $categoryDisableRewrite */
    protected static $categoryDisableRewrite = null;
    public $protocol_link;
    public $protocol_content;
    /** @var bool Rewriting activation */
    protected $allow;
    protected $url;
    protected $ssl_enable;
    // @codingStandardsIgnoreEnd

    /**
     * Constructor (initialization only)
     *
     * @param null $protocolLink
     * @param null $protocolContent
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function __construct($protocolLink = null, $protocolContent = null)
    {
        $this->allow = (int) Configuration::get('PS_REWRITING_SETTINGS');
        $this->url = $_SERVER['SCRIPT_NAME'];
        $this->protocol_link = $protocolLink;
        $this->protocol_content = $protocolContent;

        if (!defined('_PS_BASE_URL_')) {
            define('_PS_BASE_URL_', Tools::getShopDomain(true));
        }
        if (!defined('_PS_BASE_URL_SSL_')) {
            define('_PS_BASE_URL_SSL_', Tools::getShopDomainSsl(true));
        }

        if (static::$categoryDisableRewrite === null) {
            static::$categoryDisableRewrite = [Configuration::get('PS_HOME_CATEGORY'), Configuration::get('PS_ROOT_CATEGORY')];
        }

        $this->ssl_enable = Configuration::get('PS_SSL_ENABLED');
    }

    /**
     * thirty bees' new coding style dictates that camelCase should be used
     * rather than snake_case
     * These magic methods provide backwards compatibility for modules/themes/whatevers
     * that still access properties via their snake_case names
     *
     * @param string $property Property name
     *
     * @return mixed
     *
     * @since 1.0.1
     */
    public function &__get($property)
    {
        // Property to camelCase for backwards compatibility
        $camelCaseProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));
        if (property_exists($this, $camelCaseProperty) && in_array($camelCaseProperty, ['categoryDisableRewrite'])) {
            return $this->$camelCaseProperty;
        }

        return $this->$property;
    }

    /**
     * Create a link to delete a product
     *
     * @param mixed $product   ID of the product OR a Product object
     * @param int   $idPicture ID of the picture to delete
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getProductDeletePictureLink($product, $idPicture)
    {
        $url = $this->getProductLink($product);

        return $url.((strpos($url, '?')) ? '&' : '?').'deletePicture='.$idPicture;
    }

    /**
     * @param int|Product $product
     * @param string|null $alias
     * @param int|null    $category
     * @param string|null $ean13
     * @param int|null    $idLang
     * @param int|null    $idShop
     * @param int         $ipa
     * @param bool        $forceRoutes
     * @param bool        $relativeProtocol
     * @param bool        $addAnchor
     * @param array       $extraParams
     *
     * @return string
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    public function getProductLink($product, $alias = null, $category = null, $ean13 = null, $idLang = null, $idShop = null, $ipa = 0, $forceRoutes = false, $relativeProtocol = false, $addAnchor = false, $extraParams = [])
    {
        $dispatcher = Dispatcher::getInstance();

        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }

        $url = $this->getBaseLink($idShop, null, $relativeProtocol).$this->getLangLink($idLang, null, $idShop);

        if (!is_object($product)) {
            if (is_array($product) && isset($product['id_product'])) {
                $product = new Product($product['id_product'], false, $idLang, $idShop);
            } elseif ((int) $product) {
                $product = new Product((int) $product, false, $idLang, $idShop);
            } else {
                throw new PrestaShopException('Invalid product vars');
            }
        }

        // Set available keywords
        $params = [];
        $params['id'] = $product->id;
        $params['rewrite'] = (!$alias) ? $product->getFieldByLang('link_rewrite') : $alias;

        $params['ean13'] = (!$ean13) ? $product->ean13 : $ean13;
        $params['meta_keywords'] =    Tools::str2url($product->getFieldByLang('meta_keywords'));
        $params['meta_title'] = Tools::str2url($product->getFieldByLang('meta_title'));

        if ($dispatcher->hasKeyword('product_rule', $idLang, 'manufacturer', $idShop)) {
            $params['manufacturer'] = Tools::str2url($product->isFullyLoaded ? $product->manufacturer_name : Manufacturer::getNameById($product->id_manufacturer));
        }

        if ($dispatcher->hasKeyword('product_rule', $idLang, 'supplier', $idShop)) {
            $params['supplier'] = Tools::str2url($product->isFullyLoaded ? $product->supplier_name : Supplier::getNameById($product->id_supplier));
        }

        if ($dispatcher->hasKeyword('product_rule', $idLang, 'price', $idShop)) {
            $params['price'] = $product->isFullyLoaded ?
                $product->price :
                Product::getPriceStatic(
                    $product->id,
                    false,
                    null,
                    _TB_PRICE_DATABASE_PRECISION_,
                    null,
                    false,
                    true,
                    1,
                    false,
                    null,
                    null,
                    null,
                    $product->specificPrice
                );
        }

        if ($dispatcher->hasKeyword('product_rule', $idLang, 'tags', $idShop)) {
            $params['tags'] = Tools::str2url($product->getTags($idLang));
        }

        if ($dispatcher->hasKeyword('product_rule', $idLang, 'category', $idShop)) {
            $params['category'] = (!is_null($product->category) && !empty($product->category)) ? Tools::str2url($product->category) : Tools::str2url($category);
        }

        if ($dispatcher->hasKeyword('product_rule', $idLang, 'reference', $idShop)) {
            $params['reference'] = Tools::str2url($product->reference);
        }

        if ($dispatcher->hasKeyword('product_rule', $idLang, 'categories', $idShop)) {
            $params['category'] = (!$category) ? $product->category : $category;
            $cats = [];
            $categoryDisableRewrite = static::$categoryDisableRewrite;
            foreach ($product->getParentCategories($idLang) as $cat) {
                if (!in_array($cat['id_category'], $categoryDisableRewrite)) {
                    //remove root and home category from the URL
                    $cats[] = $cat['link_rewrite'];
                }
            }
            $params['categories'] = implode('/', $cats);
        }
        $anchor = $ipa ? $product->getAnchor((int) $ipa, (bool) $addAnchor) : '';

        return $url.$dispatcher->createUrl('product_rule', $idLang, array_merge($params, $extraParams), $forceRoutes, $anchor, $idShop);
    }

    /**
     * @param int|null  $idShop
     * @param bool|null $ssl
     * @param bool      $relativeProtocol
     *
     * @return string
     *
     * @since 1.0.0 Function has become public
     * @throws PrestaShopException
     */
    public function getBaseLink($idShop = null, $ssl = null, $relativeProtocol = false)
    {
        static $forceSsl = null;

        if ($ssl === null) {
            if ($forceSsl === null) {
                $forceSsl = (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE'));
            }
            $ssl = $forceSsl;
        }

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && $idShop !== null) {
            $shop = new Shop($idShop);
        } else {
            $shop = Context::getContext()->shop;
        }

        if ($relativeProtocol) {
            $base = '//'.($ssl && $this->ssl_enable ? $shop->domain_ssl : $shop->domain);
        } else {
            $base = (($ssl && $this->ssl_enable) ? 'https://'.$shop->domain_ssl : 'http://'.$shop->domain);
        }

        return $base.$shop->getBaseURI();
    }

    /**
     * @param int|null     $idLang
     * @param Context|null $context
     * @param int|null     $idShop
     *
     * @return string
     *
     * @since 1.0.0 Function has become public
     * @throws PrestaShopException
     */
    public function getLangLink($idLang = null, Context $context = null, $idShop = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        if ((!$this->allow && in_array($idShop, [$context->shop->id, null])) || !Language::isMultiLanguageActivated($idShop) || !(int) Configuration::get('PS_REWRITING_SETTINGS', null, null, $idShop)) {
            return '';
        }

        if (!$idLang) {
            $idLang = $context->language->id;
        }

        return Language::getIsoById($idLang).'/';
    }

    /**
     * Use controller name to create a link
     *
     * @param string $controller
     * @param bool   $withToken include or not the token in the url
     *
     * @return string url
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getAdminLink($controller, $withToken = true)
    {
        $idLang = Context::getContext()->language->id;

        $params = $withToken ? ['token' => Tools::getAdminTokenLite($controller)] : [];

        return Dispatcher::getInstance()->createUrl($controller, $idLang, $params, false);
    }

    /**
     * Returns a link to a product image for display
     * Note: the new image filesystem stores product images in subdirectories of img/p/
     *
     * @param string $name    Rewrite link of the image
     * @param string $ids     ID part of the image filename - can be "id_product-id_image" (legacy support, recommended) or "id_image" (new)
     * @param string $type    Image type
     * @param string $format  Image format (jpg/png/webp)
     * @param bool   $highDpi Higher resolution
     *
     * @return string
     *
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getImageLink($name, $ids, $type = null, $format = 'jpg', $highDpi = false)
    {
        if (!$format) {
            $format = 'jpg';
        }
        $notDefault = false;

        $type = ImageType::getFormatedName($type);

        // Check if module is installed, enabled, customer is logged in and watermark logged option is on
        if (($type != '')
            && Configuration::get('WATERMARK_LOGGED')
            && (Module::isInstalled('watermark')
                && Module::isEnabled('watermark'))
            && isset(Context::getContext()->customer->id)
        ) {
            $type .= '-'.Configuration::get('WATERMARK_HASH');
        }

        // legacy mode or default image
        $theme = ((Shop::isFeatureActive() && file_exists(_PS_PROD_IMG_DIR_.$ids.($type ? '-'.$type : '').'-'.(int) Context::getContext()->shop->id_theme.($highDpi ? '2x.' : '.').$format)) ? '-'.Context::getContext()->shop->id_theme : '');
        if ((Configuration::get('PS_LEGACY_IMAGES')
                && (file_exists(_PS_PROD_IMG_DIR_.$ids.($type ? '-'.$type : '').$theme.($highDpi ? '2x.' : '.').$format)))
            || ($notDefault = strpos($ids, 'default') !== false)
        ) {
            if ($this->allow == 1 && !$notDefault) {
                $uriPath = __PS_BASE_URI__.$ids.($type ? '-'.$type : '').$theme.'/'.$name.($highDpi ? '2x.' : '.').$format;
            } else {
                $uriPath = _THEME_PROD_DIR_.$ids.($type ? '-'.$type : '').$theme.($highDpi ? '2x.' : '.').$format;
            }
        } else {
            // if ids if of the form id_product-id_image, we want to extract the id_image part
            $splitIds = explode('-', $ids);
            $idImage = (isset($splitIds[1]) ? $splitIds[1] : $splitIds[0]);
            $theme = ((Shop::isFeatureActive() && file_exists(_PS_PROD_IMG_DIR_.Image::getImgFolderStatic($idImage).$idImage.($type ? '-'.$type : '').'-'.(int) Context::getContext()->shop->id_theme.($highDpi ? '2x.' : '.').$format)) ? '-'.Context::getContext()->shop->id_theme : '');
            if ($this->allow == 1) {
                $uriPath = __PS_BASE_URI__.$idImage.($type ? '-'.$type : '').$theme.'/'.$name.($highDpi ? '2x.' : '.').$format;
            } else {
                $uriPath = _THEME_PROD_DIR_.Image::getImgFolderStatic($idImage).$idImage.($type ? '-'.$type : '').$theme.($highDpi ? '2x.' : '.').$format;
            }
        }

        return $this->protocol_content.Tools::getMediaServer($uriPath).$uriPath;
    }

    /**
     * @param string $filepath
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getMediaLink($filepath)
    {
        return $this->protocol_content.Tools::getMediaServer($filepath).$filepath;
    }

    /**
     * @param string      $name
     * @param int         $idCategory
     * @param string|null $type
     * @param string      $format
     *
     * @return string
     *
     * @throws PrestaShopException
     * @version 1.0.0 Initial version.
     * @version 1.1.0 Use getGenericImageLink().
     */
    public function getCatImageLink($name, $idCategory, $type = null, $format = 'jpg', $highDpi = false)
    {
        return static::getGenericImageLink(
            'categories',
            $idCategory,
            $type,
            $highDpi ? '2x' : '',
            $format === 'webp',
            $name
        );
    }

    /**
     * Get an image link to anything but products.
     *
     * @param string  $class      Class of the image. 'categories',
     *                            'manufacturers', ... For a list of choices,
     *                            see _TB_IMAGE_MAP_. For class 'products',
     *                            use getImageLink().
     * @param int     $id         ID of the image.
     * @param string  $type       Image type, like 'home', 'home_small', ...
     * @param string  $resolution Image resolution. '', '2x', '3x', ...
     * @param bool    $webp       Whether to use the WEBP format, if available.
     * @param string  $name       An image name for pretty/SEO-friendly URLs.
     *                            Currently, only (products and) categories
     *                            support such names.
     *
     * @return Full URL to the image.
     *
     * @version 1.1.0 Iniitial version.
     */
    public static function getGenericImageLink($class, $id, $type,
                                               $resolution = '',
                                               $webp = false, $name = '')
    {
        $type = ImageType::getFormatedName($type);
        $format = 'jpg';
        if ($webp && ImageManager::webpSupport()) {
            $format = 'webp';
        }

        if (Configuration::get('PS_REWRITING_SETTINGS')
            && $class == 'categories'
            && $name
        ) {
            $uriPath = __PS_BASE_URI__._TB_IMAGE_MAP_[$class]
                       .$id.'-'.$type.'/'
                       .$name.$resolution.'.'.$format;
        } else {
            $uriPath = _PS_IMG_._TB_IMAGE_MAP_[$class]
                       .$id.'-'.$type.$resolution.'.'.$format;
        }

        return Tools::getShopProtocol()
               .Tools::getMediaServer($uriPath)
               .$uriPath;
    }

    /**
     * Create link after language change, for the change language block
     *
     * @param int     $idLang Language ID
     * @param Context $context
     *
     * @return string link
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getLanguageLink($idLang, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $params = $_GET;
        unset($params['isolang'], $params['controller']);

        if (!$this->allow) {
            $params['id_lang'] = $idLang;
        } else {
            unset($params['id_lang']);
        }

        $controller = Dispatcher::getInstance()->getController();

        if (!empty($context->controller->php_self)) {
            $controller = $context->controller->php_self;
        }

        if ($controller == 'product' && isset($params['id_product'])) {
            return $this->getProductLink((int) $params['id_product'], null, null, null, (int) $idLang);
        } elseif ($controller == 'category' && isset($params['id_category'])) {
            return $this->getCategoryLink((int) $params['id_category'], null, (int) $idLang);
        } elseif ($controller == 'supplier' && isset($params['id_supplier'])) {
            return $this->getSupplierLink((int) $params['id_supplier'], null, (int) $idLang);
        } elseif ($controller == 'manufacturer' && isset($params['id_manufacturer'])) {
            return $this->getManufacturerLink((int) $params['id_manufacturer'], null, (int) $idLang);
        } elseif ($controller == 'cms' && isset($params['id_cms'])) {
            return $this->getCMSLink((int) $params['id_cms'], null, null, (int) $idLang);
        } elseif ($controller == 'cms' && isset($params['id_cms_category'])) {
            return $this->getCMSCategoryLink((int) $params['id_cms_category'], null, (int) $idLang);
        } elseif (isset($params['fc']) && $params['fc'] == 'module') {
            $module = Validate::isModuleName(Tools::getValue('module')) ? Tools::getValue('module') : '';
            if (!empty($module)) {
                unset($params['fc'], $params['module']);

                return $this->getModuleLink($module, $controller, $params, null, (int) $idLang);
            }
        }

        return $this->getPageLink($controller, null, $idLang, $params);
    }

    /**
     * @param int|Category $category
     * @param string|null  $alias
     * @param int|null     $idLang
     * @param string|null  $selectedFilters
     * @param int|null     $idShop
     * @param bool         $relativeProtocol
     *
     * @return string
     * @throws PrestaShopException
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function getCategoryLink($category, $alias = null, $idLang = null, $selectedFilters = null, $idShop = null, $relativeProtocol = false)
    {
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }
        $url = $this->getBaseLink($idShop, null, $relativeProtocol).$this->getLangLink($idLang, null, $idShop);
        if (!is_object($category)) {
            $category = new Category($category, $idLang);
        }
        // Set available keywords
        $params = [];
        $params['id'] = $category->id;
        $params['rewrite'] = (!$alias) ? $category->link_rewrite : $alias;
        $params['meta_keywords'] =    Tools::str2url($category->getFieldByLang('meta_keywords'));
        $params['meta_title'] = Tools::str2url($category->getFieldByLang('meta_title'));
        $cats = [];
        $categoryDisableRewrite = static::$categoryDisableRewrite;

        foreach ($category->getParentsCategories($idLang) as $cat) {
            if (!in_array($cat['id_category'], $categoryDisableRewrite)) {
                //remove root and home category from the URL
                $cats[] = $cat['link_rewrite'];
            }
        }
        array_shift($cats);
        $cats = array_reverse($cats);
        $params['categories'] = trim(implode('/', $cats), '/');

        // Selected filters are used by layered navigation modules
        $selectedFilters = is_null($selectedFilters) ? '' : $selectedFilters;
        if (empty($selectedFilters)) {
            $rule = 'category_rule';
        } else {
            $rule = 'layered_rule';
            $params['selected_filters'] = $selectedFilters;
        }

        return $url.Dispatcher::getInstance()->createUrl($rule, $idLang, $params, $this->allow, '', $idShop);
    }

    /**
     * Create a link to a supplier
     *
     * @param mixed    $supplier Supplier object (can be an ID supplier, but deprecated)
     * @param string   $alias
     * @param int      $idLang
     * @param int|null $idShop
     * @param bool     $relativeProtocol
     *
     * @return string
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function getSupplierLink($supplier, $alias = null, $idLang = null, $idShop = null, $relativeProtocol = false)
    {
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }

        $url = $this->getBaseLink($idShop, null, $relativeProtocol).$this->getLangLink($idLang, null, $idShop);

        $dispatcher = Dispatcher::getInstance();
        if (!is_object($supplier)) {
            if ($alias !== null && !$dispatcher->hasKeyword('supplier_rule', $idLang, 'meta_keywords', $idShop) && !$dispatcher->hasKeyword('supplier_rule', $idLang, 'meta_title', $idShop)) {
                return $url.$dispatcher->createUrl('supplier_rule', $idLang, ['id' => (int) $supplier, 'rewrite' => (string) $alias], $this->allow, '', $idShop);
            }
            $supplier = new Supplier($supplier, $idLang);
        }

        // Set available keywords
        $params = [];
        $params['id'] = $supplier->id;
        $params['rewrite'] = (!$alias) ? $supplier->link_rewrite : $alias;
        $params['meta_keywords'] = Tools::str2url($supplier->meta_keywords);
        $params['meta_title'] = Tools::str2url($supplier->meta_title);

        return $url.$dispatcher->createUrl('supplier_rule', $idLang, $params, $this->allow, '', $idShop);
    }

    /**
     * Create a link to a manufacturer
     *
     * @param mixed    $manufacturer Manufacturer object (can be an ID supplier, but deprecated)
     * @param string   $alias
     * @param int      $idLang
     * @param int|null $idShop
     * @param bool     $relativeProtocol
     *
     * @return string
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function getManufacturerLink($manufacturer, $alias = null, $idLang = null, $idShop = null, $relativeProtocol = false)
    {
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }

        $url = $this->getBaseLink($idShop, null, $relativeProtocol).$this->getLangLink($idLang, null, $idShop);

        $dispatcher = Dispatcher::getInstance();
        if (!is_object($manufacturer)) {
            if ($alias !== null && !$dispatcher->hasKeyword('manufacturer_rule', $idLang, 'meta_keywords', $idShop) && !$dispatcher->hasKeyword('manufacturer_rule', $idLang, 'meta_title', $idShop)) {
                return $url.$dispatcher->createUrl('manufacturer_rule', $idLang, ['id' => (int) $manufacturer, 'rewrite' => (string) $alias], $this->allow, '', $idShop);
            }
            $manufacturer = new Manufacturer($manufacturer, $idLang);
        }

        // Set available keywords
        $params = [];
        $params['id'] = $manufacturer->id;
        $params['rewrite'] = (!$alias) ? $manufacturer->link_rewrite : $alias;
        $params['meta_keywords'] = Tools::str2url($manufacturer->meta_keywords);
        $params['meta_title'] = Tools::str2url($manufacturer->meta_title);

        return $url.$dispatcher->createUrl('manufacturer_rule', $idLang, $params, $this->allow, '', $idShop);
    }

    /**
     * @param int|CMS     $cms
     * @param string|null $alias
     * @param null        $ssl
     * @param null        $idLang
     * @param null        $idShop
     * @param bool        $relativeProtocol
     *
     * @return string
     * @throws PrestaShopException
     */
    public function getCMSLink($cms, $alias = null, $ssl = null, $idLang = null, $idShop = null, $relativeProtocol = false)
    {
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }
        if (!$idShop) {
            $idShop = Context::getContext()->shop->id;
        }
        $url = $this->getBaseLink($idShop, $ssl, $relativeProtocol).$this->getLangLink($idLang, null, $idShop);
        $dispatcher = Dispatcher::getInstance();
        if (!is_object($cms)) {
            $cms = new CMS($cms, $idLang);
        }
        // Set available keywords
        $params = [];
        $params['id'] = $cms->id;
        $params['rewrite'] = (!$alias) ? (is_array($cms->link_rewrite) ? $cms->link_rewrite[(int) $idLang] : $cms->link_rewrite) : $alias;
        $params['meta_keywords'] = '';
        $params['categories'] = $this->findCMSSubcategories($cms->id, $idLang);

        if (isset($cms->meta_keywords) && !empty($cms->meta_keywords)) {
            $params['meta_keywords'] = is_array($cms->meta_keywords) ?  Tools::str2url($cms->meta_keywords[(int) $idLang]) :  Tools::str2url($cms->meta_keywords);
        }
        $params['meta_title'] = '';
        if (isset($cms->meta_title) && !empty($cms->meta_title)) {
            $params['meta_title'] = is_array($cms->meta_title) ? Tools::str2url($cms->meta_title[(int) $idLang]) : Tools::str2url($cms->meta_title);
        }

        return $url.$dispatcher->createUrl('cms_rule', $idLang, $params, $this->allow, '', $idShop);
    }

    /**
     * @param int $idCms
     * @param int $idLang
     *
     * @return string
     * @throws PrestaShopException
     */
    protected function findCMSSubcategories($idCms, $idLang)
    {
        $sql = new DbQuery();
        $sql->select('`'.bqSQL(CMSCategory::$definition['primary']).'`');
        $sql->from(bqSQL(CMS::$definition['table']));
        $sql->where('`'.bqSQL(CMS::$definition['primary']).'` = '.(int) $idCms);
        $idCmsCategory = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        if (empty($idCmsCategory)) {
            return '';
        }
        $subcategories = $this->findCMSCategorySubcategories($idCmsCategory, $idLang);

        return trim($subcategories, '/');
    }

    /**
     * @param int $idCmsCategory
     * @param int $idLang
     *
     * @return string
     */
    protected function findCMSCategorySubcategories($idCmsCategory, $idLang)
    {
        if (empty($idCmsCategory) || $idCmsCategory === 1) {
            return '';
        }
        $subcategories = '';
        while ($idCmsCategory > 1) {
            $subcategory = new CMSCategory($idCmsCategory);
            $subcategories = $subcategory->link_rewrite[$idLang].'/'.$subcategories;
            $idCmsCategory = $subcategory->id_parent;
        }

        return trim($subcategories, '/');
    }

    /**
     * @param int|CMSCategory $cmsCategory
     * @param string|null     $alias
     * @param int|null        $idLang
     * @param int|null        $idShop
     * @param bool            $relativeProtocol
     *
     * @return string
     * @throws PrestaShopException
     */
    public function getCMSCategoryLink($cmsCategory, $alias = null, $idLang = null, $idShop = null, $relativeProtocol = false)
    {
        if (empty($idLang)) {
            $idLang = Context::getContext()->language->id;
        }
        if (empty($idShop)) {
            $idShop = Context::getContext()->shop->id;
        }
        $url = $this->getBaseLink($idShop, null, $relativeProtocol).$this->getLangLink($idLang, null, $idShop);
        $dispatcher = Dispatcher::getInstance();
        if (!is_object($cmsCategory)) {
            $cmsCategory = new CMSCategory($cmsCategory, $idLang);
        }
        if (is_array($cmsCategory->link_rewrite) && isset($cmsCategory->link_rewrite[(int) $idLang])) {
            $cmsCategory->link_rewrite = $cmsCategory->link_rewrite[(int) $idLang];
        }
        if (is_array($cmsCategory->meta_keywords) && isset($cmsCategory->meta_keywords[(int) $idLang])) {
            $cmsCategory->meta_keywords = $cmsCategory->meta_keywords[(int) $idLang];
        }
        if (is_array($cmsCategory->meta_title) && isset($cmsCategory->meta_title[(int) $idLang])) {
            $cmsCategory->meta_title = $cmsCategory->meta_title[(int) $idLang];
        }
        // Set available keywords
        $params = [];
        $params['id'] = $cmsCategory->id;
        $params['rewrite'] = (!$alias) ? $cmsCategory->link_rewrite : $alias;
        $params['meta_keywords'] = Tools::str2url($cmsCategory->meta_keywords);
        $params['meta_title'] = Tools::str2url($cmsCategory->meta_title);
        $idParent = $this->findCMSCategoryParent($cmsCategory->id_cms_category);
        if (empty($idParent)) {
            $params['categories'] = '';
        } else {
            $params['categories'] = $this->findCMSCategorySubcategories($idParent, $idLang);
        }

        return $url.$dispatcher->createUrl('cms_category_rule', $idLang, $params, $this->allow, '', $idShop);
    }

    /**
     * @param int $idCmsCategory
     *
     * @return int
     * @throws PrestaShopException
     */
    protected function findCMSCategoryParent($idCmsCategory)
    {
        $sql = new DbQuery();
        $sql->select('`id_parent`');
        $sql->from(bqSQL(CMSCategory::$definition['table']));
        $sql->where('`'.bqSQL(CMSCategory::$definition['primary']).'` = '.(int) $idCmsCategory);
        $idParent = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        if (empty($idParent)) {
            return 0;
        }

        return (int) $idParent;
    }

    /**
     * Create a link to a module
     *
     * @param string   $module Module name
     * @param string   $controller
     * @param array    $params
     * @param null     $ssl
     * @param int      $idLang
     * @param int|null $idShop
     * @param bool     $relativeProtocol
     *
     * @return string
     * @internal param string $process Action name
     * @since    1.0.0
     * @version  1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getModuleLink($module, $controller = 'default', array $params = [], $ssl = null, $idLang = null, $idShop = null, $relativeProtocol = false)
    {
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }

        $url = $this->getBaseLink($idShop, $ssl, $relativeProtocol).$this->getLangLink($idLang, null, $idShop);

        // Set available keywords
        $params['module'] = $module;
        $params['controller'] = $controller ? $controller : 'default';

        // If the module has its own route ... just use it !
        if (Dispatcher::getInstance()->hasRoute('module-'.$module.'-'.$controller, $idLang, $idShop)) {
            return $this->getPageLink('module-'.$module.'-'.$controller, $ssl, $idLang, $params);
        } else {
            return $url.Dispatcher::getInstance()->createUrl('module', $idLang, $params, $this->allow, '', $idShop);
        }
    }

    /**
     * Create a simple link
     *
     * @param string       $controller
     * @param bool         $ssl
     * @param int          $idLang
     * @param string|array $request
     * @param bool         $requestUrlEncode Use URL encode
     * @param int|null     $idShop
     * @param bool         $relativeProtocol
     *
     * @return string Page link
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getPageLink($controller, $ssl = null, $idLang = null, $request = null, $requestUrlEncode = false, $idShop = null, $relativeProtocol = false)
    {
        //If $controller contains '&' char, it means that $controller contains request data and must be parsed first
        $p = strpos($controller, '&');
        if ($p !== false) {
            $request = substr($controller, $p + 1);
            $requestUrlEncode = false;
            $controller = substr($controller, 0, $p);
        }

        $controller = Tools::strReplaceFirst('.php', '', $controller);
        if (!$idLang) {
            $idLang = (int) Context::getContext()->language->id;
        }

        //need to be unset because getModuleLink need those params when rewrite is enable
        if (is_array($request)) {
            if (isset($request['module'])) {
                unset($request['module']);
            }
            if (isset($request['controller'])) {
                unset($request['controller']);
            }
        } else {
            $request = html_entity_decode($request);
            if ($requestUrlEncode) {
                $request = urlencode($request);
            }
            parse_str($request, $request);
        }

        $uriPath = Dispatcher::getInstance()->createUrl($controller, $idLang, $request, false, '', $idShop);

        return $this->getBaseLink($idShop, $ssl, $relativeProtocol).$this->getLangLink($idLang, null, $idShop).ltrim($uriPath, '/');
    }

    /**
     * @param string $url
     * @param int    $p
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function goPage($url, $p)
    {
        $url = rtrim(str_replace('?&', '?', $url), '?');

        return $url.($p == 1 ? '' : (!strstr($url, '?') ? '?' : '&').'p='.(int) $p);
    }

    /**
     * Get pagination link
     *
     * @param string     $type       Controller name
     * @param object|int $idObject
     * @param bool       $nb         Show nb element per page attribute
     * @param bool       $sort       Show sort attribute
     * @param bool       $pagination Show page number attribute
     * @param bool       $array      If false return an url, if true return an array
     *
     * @return array|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getPaginationLink($type, $idObject, $nb = false, $sort = false, $pagination = false, $array = false)
    {
        // If no parameter $type, try to get it by using the controller name
        if (!$type && !$idObject) {
            $methodName = 'get'.Dispatcher::getInstance()->getController().'Link';
            if (method_exists($this, $methodName) && isset($_GET['id_'.Dispatcher::getInstance()->getController()])) {
                $type = Dispatcher::getInstance()->getController();
                $idObject = $_GET['id_'.$type];
            }
        }

        if ($type && $idObject) {
            $url = $this->{'get'.$type.'Link'}($idObject, null);
        } else {
            if (isset(Context::getContext()->controller->php_self)) {
                $name = Context::getContext()->controller->php_self;
            } else {
                $name = Dispatcher::getInstance()->getController();
            }
            $url = $this->getPageLink($name);
        }

        $vars = [];
        $varsNb = ['n'];
        $varsSort = ['orderby', 'orderway'];
        $varsPagination = ['p'];

        foreach ($_GET as $k => $value) {
            if ($k != 'id_'.$type && $k != 'controller') {
                if (Configuration::get('PS_REWRITING_SETTINGS') && ($k == 'isolang' || $k == 'id_lang')) {
                    continue;
                }
                $ifNb = (!$nb || ($nb && !in_array($k, $varsNb)));
                $ifSort = (!$sort || ($sort && !in_array($k, $varsSort)));
                $ifPagination = (!$pagination || ($pagination && !in_array($k, $varsPagination)));
                if ($ifNb && $ifSort && $ifPagination) {
                    if (!is_array($value)) {
                        $vars[urlencode($k)] = $value;
                    } else {
                        foreach (explode('&', http_build_query([$k => $value], '', '&')) as $key => $val) {
                            $data = explode('=', $val);
                            $vars[urldecode($data[0])] = $data[1];
                        }
                    }
                }
            }
        }

        if (!$array) {
            if (count($vars)) {
                return $url.(!strstr($url, '?') && ($this->allow == 1 || $url == $this->url) ? '?' : '&').http_build_query($vars, '', '&');
            } else {
                return $url;
            }
        }

        $vars['requestUrl'] = $url;

        if ($type && $idObject) {
            $vars['id_'.$type] = (is_object($idObject) ? (int) $idObject->id : (int) $idObject);
        }

        if (!$this->allow == 1) {
            $vars['controller'] = Dispatcher::getInstance()->getController();
        }

        return $vars;
    }

    /**
     * @param string $url
     * @param string $orderby
     * @param string $orderway
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addSortDetails($url, $orderby, $orderway)
    {
        return $url.(!strstr($url, '?') ? '?' : '&').'orderby='.urlencode($orderby).'&orderway='.urlencode($orderway);
    }

    /**
     * @param string $url
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function matchQuickLink($url)
    {
        $quicklink = $this->getQuickLink($url);
        if (isset($quicklink) && $quicklink === ($this->getQuickLink($_SERVER['REQUEST_URI']))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $url
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getQuickLink($url)
    {
        $parsedUrl = parse_url($url);
        $output = [];
        if (is_array($parsedUrl) && isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $output);
            unset($output['token'], $output['conf'], $output['id_quick_access']);
        }

        return http_build_query($output);
    }
}
