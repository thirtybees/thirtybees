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

// This file might not exist. For example, at install time it doesn't.
// Accordingly, we also have to check for the existence of $shopUrlConfig
// on every read access.
@include_once(_PS_ROOT_DIR_.'/config/shop.inc.php');

/**
 * Class ShopUrlCore
 *
 * @since 1.0.0
 */
class ShopUrlCore extends ObjectModel
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
        'path'    => '/config/shop.inc.php', // Has to match include() above.
        'storage' => 'shopUrlConfig',
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
     * This shall help with the transition from using a database table to
     * using an PHP array written to a file. It copies DB content to
     * the global configuration array.
     *
     * This method can go away as soon as this class is no longer inherited
     * from ObjectModel. By then we have to have some other means to write
     * the array, of course.
     */
    public function update($null_values = false)
    {
        $storageName = static::$definition['storage'];
        global ${$storageName};

        $result = parent::update($null_values);

        // Make sure each shop in the database is also in $shopUrlConfig.
        // This task can be removed as soon as shop changes are stored in
        // $shopUrlConfig by calling code.
        $sql = 'SELECT id_shop_url, id_shop, domain, domain_ssl, physical_uri, virtual_uri, main, active
                FROM '._DB_PREFIX_.'shop_url';
        $sqlResult = Db::getInstance()->executeS($sql);

        $storage = &${$storageName};
        $storage = array();
        foreach ($sqlResult as $url) {
            $storage[$url['id_shop_url']] = $url;
            unset($storage[$url['id_shop_url']]['id_shop_url']);
        }

        static::writeStorage();

        return $result;
    }

    /**
     * Do the opposite of update(): forward $shopUrlConfig to the DB. Also
     * expected to be temporary, only.
     */
    public static function push()
    {
        $storageName = static::$definition['storage'];
        global ${$storageName};

        if (is_array(${$storageName})) {
            // To make sure we also drop records no longer existing, we drop the
            // entire table and write a fresh one. Performance is no issue here.
            Db::getInstance()->delete('shop_url');

            foreach (${$storageName} as $key => $url) {
                $url['id_shop_url'] = $key;

                Db::getInstance()->insert('shop_url', $url);
            }
        }
    }

    /**
     * Write storage to the file. That's $shopUrlConfig here.
     *
     * @return int|bool Number of bytes written or false-equivalent on failure.
     *
     * @since   1.1.0
     * @version 1.1.0 Initial version
     */
    public static function writeStorage()
    {
        $storageName = static::$definition['storage'];
        global ${$storageName}; // Assume it exists.

        $result = file_put_contents(_PS_ROOT_DIR_.static::$definition['path'],
            "<?php\n\n".
            'global $'.$storageName.';'."\n\n".
            '$'.$storageName.' = '.
              var_export(${$storageName}, true).
            ';'."\n");

        // Clear most citizens in cache-mess-city. Else the include_once()
        // above may well read an old version on the next page load.
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Cache::getInstance()->flush();
        PageCache::flush();
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        return $result;
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
     * Get list of shop urls
     *
     * @param bool $idShop
     *
     * @return PrestaShopCollection Collection of ShopUrl
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getShopUrls($idShop = false)
    {
        $urls = new PrestaShopCollection('ShopUrl');
        if ($idShop) {
            $urls->where('id_shop', '=', $idShop);
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
        $res = Db::getInstance()->update('shop_url', ['main' => 0], 'id_shop = '.(int) $this->id_shop);
        $res &= Db::getInstance()->update('shop_url', ['main' => 1], 'id_shop_url = '.(int) $this->id);
        $this->main = true;

        // Reset main URL for all shops to prevent problems
        $sql = 'SELECT s1.id_shop_url FROM '._DB_PREFIX_.'shop_url s1
				WHERE (
					SELECT COUNT(*) FROM '._DB_PREFIX_.'shop_url s2
					WHERE s2.main = 1
					AND s2.id_shop = s1.id_shop
				) = 0
				GROUP BY s1.id_shop';
        foreach (Db::getInstance()->executeS($sql) as $row) {
            Db::getInstance()->update('shop_url', ['main' => 1], 'id_shop_url = '.$row['id_shop_url']);
        }

        return $res;
    }

    /**
     * @param $domain
     * @param $domainSsl
     * @param $physicalUri
     * @param $virtualUri
     *
     * @return false|null|string
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

        $sql = 'SELECT id_shop_url
				FROM '._DB_PREFIX_.'shop_url
				WHERE physical_uri = \''.pSQL($physicalUri).'\'
					AND virtual_uri = \''.pSQL($virtualUri).'\'
					AND (domain = \''.pSQL($domain).'\' '.(($domainSsl) ? ' OR domain_ssl = \''.pSQL($domainSsl).'\'' : '').')'
            .($this->id ? ' AND id_shop_url != '.(int) $this->id : '');

        return Db::getInstance()->getValue($sql);
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
