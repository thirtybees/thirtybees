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
        'table'   => 'shop_url',
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
     * Do the opposite of update(): forward $shopUrlConfig to the DB. Also
     * expected to be temporary, only.
     */
    public static function push($storage)
    {
        // To make sure we also drop records no longer existing, we drop the
        // entire table and write a fresh one. Performance is no issue here.
        Db::getInstance()->delete('shop_url');

        foreach ($storage as $key => $url) {
            $url['id_shop_url'] = $key;

            Db::getInstance()->insert('shop_url', $url);
        }
    }

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
        $storage = static::getStorage();

        foreach ($storage as $key => $url) {
            if ($url['id_shop'] == $idShop) {
                unset($storage[$key]);
            }
        }

        static::writeStorage($storage);
        // Remove later. Comment out to see wether the code here actually works,
        // or wether DB gets written by some other means we no longer want.
        ShopUrl::push($storage);
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
     * Get list of shop URLs. For getting just the data, use
     * ShopUrl::getStorage().
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
        $urls = [];
        foreach (static::getStorage() as $id => $url) {
            if (!$idShop || $url['id_shop'] == $idShop) {
                $urls[] = new ShopUrl($id);
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
        $res = false;
        $storage = static::getStorage();
        foreach ($storage as $id => &$url) {
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
        $this->main = true;

        static::writeStorage($storage);
        // Remove later. Comment out to see wether the code here actually works,
        // or wether DB gets written by some other means we no longer want.
        ShopUrl::push($storage);

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
     * @return bool True = URL exists already, false = URL doesn't exit yet.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function canAddThisUrl($domain, $domainSsl, $physicalUri, $virtualUri)
    {
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
        foreach (static::getStorage() as $url) {
            if ($url['physical_uri'] === $physicalUri &&
                $url['virtual_uri'] === $virtualUri &&
                ($url['domain'] === $domain || $url['domain_ssl'] === $domainSsl)) {

                $exists = true;
                break;
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
    public static function cacheMainDomainForShop($idShop)
    {
        if (!isset(static::$main_domain_ssl[(int) $idShop]) || !isset(static::$main_domain[(int) $idShop])) {
            $row = Db::getInstance()->getRow(
                '
			SELECT domain, domain_ssl
			FROM '._DB_PREFIX_.'shop_url
			WHERE main = 1
			AND id_shop = '.($idShop !== null ? (int) $idShop : (int) Context::getContext()->shop->id)
            );

            // Adjust automatic values.
            if ($row['domain'] === '*automatic*') {
                static::$main_domain[(int) $idShop] = $_SERVER['HTTP_HOST'];
            } else {
                static::$main_domain[(int) $idShop] = $row['domain'];
            }
            if ($row['domain_ssl'] === '*automatic*') {
                static::$main_domain_ssl[(int) $idShop] = $_SERVER['HTTP_HOST'];
            } else {
                static::$main_domain_ssl[(int) $idShop] = $row['domain_ssl'];
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
