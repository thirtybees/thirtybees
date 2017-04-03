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
            static::$main_domain[(int) $idShop] = $row['domain'];
            static::$main_domain_ssl[(int) $idShop] = $row['domain_ssl'];
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
