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
 * Class ShopUrlCore
 *
 * @since 1.0.0
 */
class ShopUrlCore extends ObjectFileModel
{
    // @codingStandardsIgnoreStart
    public $id_shop;
    public $domain;
    public $domain_ssl;
    public $physical_uri;
    public $virtual_uri;
    public $main;
    public $active;

    protected static $main_domain = [];
    protected static $main_domain_ssl = [];
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        // Note: 'table' and 'primary' aren't really needed for file based
        // storage. However, parent and related classes sometimes rely on the
        // assumption of database based storage and also use these properties
        // as kind of an identifier, so these properties are kept.
        //
        // For file based storage, we choose 'table' to be the variable name
        // written to the file. 'primary' is replaced by the index of the table
        // in that file.
        'table'   => 'shopUrlConfig',
        'primary' => 'id_shop_url',
        'path'    => '/config/shop.inc.php',
        'fields'  => [
            'active'       => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                          ],
            'main'         => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                          ],
            'domain'       => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',   'required' => true, 'size' => 255],
            'domain_ssl'   => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',                       'size' => 255],
            'id_shop'      => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'required' => true               ],
            'physical_uri' => ['type' => self::TYPE_STRING, 'validate' => 'isString',                          'size' => 64 ],
            'virtual_uri'  => ['type' => self::TYPE_STRING, 'validate' => 'isString',                          'size' => 64 ],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_shop' => ['xlink_resource' => 'shops'],
        ],
    ];

    /**
     * Deletes all URLs of a shop.
     *
     * @param string $idShop
     *
     * @since   1.1.0
     * @version 1.1.0 Initial version
     */
    public static function deleteShopUrls($idShop)
    {
        global $shopUrlConfig;

        if (is_array($shopUrlConfig)) {
            foreach ($shopUrlConfig as $key => $url) {
                if ($url['id_shop'] == $idShop) {
                    unset($shopUrlConfig[$key]);
                }
            }
        }

        (new ShopUrl)->write();
    }

    /**
     * @see     ObjectModel::getFields()
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFields()
    {
        $this->domain = trim($this->domain);
        $this->domain_ssl = trim($this->domain_ssl);
        $this->physical_uri = trim(str_replace(' ', '', $this->physical_uri), '/');

        if ($this->physical_uri) {
            $this->physical_uri = preg_replace('#/+#', '/', '/'.$this->physical_uri.'/');
        } else {
            $this->physical_uri = '/';
        }

        $this->virtual_uri = trim(str_replace(' ', '', $this->virtual_uri), '/');
        if ($this->virtual_uri) {
            $this->virtual_uri = preg_replace('#/+#', '/', trim($this->virtual_uri, '/')).'/';
        }

        return parent::getFields();
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getBaseURI()
    {
        return $this->physical_uri.$this->virtual_uri;
    }

    /**
     * @param bool $ssl
     *
     * @return string|null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getURL($ssl = false)
    {
        if (!$this->id) {
            return null;
        }

        $url = ($ssl) ? 'https://'.$this->domain_ssl : 'http://'.$this->domain;

        return $url.$this->getBaseUri();
    }

    /**
     * Get list of shop urls. For getting just the data, use ShopUrl::get().
     *
     * @param bool|int $idShop
     *
     * @return array Array of ShopUrl objects.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @version 1.1.0 Return array instead of a PrestaShopCollection.
     */
    public static function getShopUrls($idShop = false)
    {
        global $shopUrlConfig;

        $urls = [];
        if (is_array($shopUrlConfig)) {
            foreach ($shopUrlConfig as $id => $url) {
                if (!$idShop || $url['id_shop'] == $idShop) {
                    $urls[] = new ShopUrl($id);
                }
            }
        }

        return $urls;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setMain()
    {
        global $shopUrlConfig;

        $res = false;
        if (is_array($shopUrlConfig)) {
            foreach ($shopUrlConfig as $id => &$url) {
                if ($url['id_shop'] == $this->id_shop) {
                    if ($id == $this->id) {
                        $url['main'] = 1;
                        $res = true;
                    } else {
                        $url['main'] = 0;
                    }
                }
            }
            unset($url);
        }
        $this->main = true;

        $this->write();

        return $res;
    }

    /**
     * Test wether a combination of domain, physical URI and virtual URI
     * exists already.
     *
     * @param $domain
     * @param $domainSsl
     * @param $physicalUri
     * @param $virtualUri
     *
     * @return bool True = URL exists already, False = URL doesn't exit yet.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function canAddThisUrl($domain, $domainSsl, $physicalUri, $virtualUri)
    {
        global $shopUrlConfig;

        $physicalUri = trim($physicalUri, '/');

        if ($physicalUri) {
            $physicalUri = preg_replace('#/+#', '/', '/'.$physicalUri.'/');
        } else {
            $physicalUri = '/';
        }

        $virtualUri = trim($virtualUri, '/');
        if ($virtualUri) {
            $virtualUri = preg_replace('#/+#', '/', trim($virtualUri, '/')).'/';
        }

        $exists = false;
        if (is_array($shopUrlConfig)) {
            foreach ($shopUrlConfig as $url) {
                if ($url['physical_uri'] === $physicalUri &&
                    $url['virtual_uri'] === $virtualUri &&
                    ($url['domain'] === $domain || $url['domain_ssl'] === $domainSsl)) {

                    $exists = true;
                    break;
                }
            }
        }

        return $exists;
    }

    /**
     * @param $idShop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cacheMainDomainForShop($idShop = null)
    {
        global $shopUrlConfig;

        if (is_array($shopUrlConfig) &&
            (!isset(static::$main_domain_ssl[(int) $idShop]) ||
             !isset(static::$main_domain[(int) $idShop]))) {
            $idShopNotNull = $idShop;
            if ($idShopNotNull === null) {
                $idShopNotNull = Context::getContext()->shop->id;
            }

            foreach ($shopUrlConfig as $url) {
                if ($url['id_shop'] == $idShopNotNull && $url['main']) {
                    // Adjust automatic values.
                    if ($url['domain'] === '*automatic*') {
                        static::$main_domain[(int)$idShop] = $_SERVER['HTTP_HOST'];
                    } else {
                        static::$main_domain[(int)$idShop] = $url['domain'];
                    }
                    if ($url['domain_ssl'] === '*automatic*') {
                        static::$main_domain_ssl[(int)$idShop] = $_SERVER['HTTP_HOST'];
                    } else {
                        static::$main_domain_ssl[(int)$idShop] = $url['domain_ssl'];
                    }
                    break;
                }
            }
        }
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function resetMainDomainCache()
    {
        static::$main_domain = [];
        static::$main_domain_ssl = [];
    }

    /**
     * @param null $idShop
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMainShopDomain($idShop = null)
    {
        ShopUrl::cacheMainDomainForShop($idShop);

        return static::$main_domain[(int) $idShop];
    }

    /**
     * @param null $idShop
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMainShopDomainSSL($idShop = null)
    {
        ShopUrl::cacheMainDomainForShop($idShop);

        return static::$main_domain_ssl[(int) $idShop];
    }
}
