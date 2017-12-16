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
 * Class ShopUrlCore
 *
 * @since 1.0.0
 */
class ShopUrlCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int $id_shop */
    public $id_shop;
    /** @var string $domain */
    public $domain;
    /** @var string $domain_ssl */
    public $domain_ssl;
    /** @var string $physical_uri */
    public $physical_uri;
    /** @var string $virtual_uri */
    public $virtual_uri;
    /** @var bool $main */
    public $main;
    /** @var bool $active */
    public $active;
    /** @var array $main_domain */
    protected static $main_domain = [];
    /** @var array $main_domain_ssl */
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
     * @throws PrestaShopException
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
     * @throws PrestaShopException
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @param string $domain
     * @param string $domainSsl
     * @param string $physicalUri
     * @param string $virtualUri
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
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

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_shop_url`')
                ->from('shop_url')
                ->where('`physical_uri` = \''.pSQL($physicalUri).'\'')
                ->where('`virtual_uri` = \''.pSQL($virtualUri).'\'')
                ->where('`domain` = \''.pSQL($domain).'\''.(($domainSsl) ? ' OR domain_ssl = \''.pSQL($domainSsl).'\'' : ''))
                ->where($this->id ? '`id_shop_url` != '.(int) $this->id : '')
        );
    }

    /**
     * @param int $idShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cacheMainDomainForShop($idShop)
    {
        // @codingStandardsIgnoreStart
        if (!isset(static::$main_domain_ssl[(int) $idShop]) || !isset(static::$main_domain[(int) $idShop])) {
            $row = Db::getInstance()->getRow(
                (new DbQuery())
                    ->select('`domain`, `domain_ssl`')
                    ->from('shop_url')
                    ->where('`main` = 1')
                    ->where('`id_shop` = '.($idShop !== null ? (int) $idShop : (int) Context::getContext()->shop->id))
            );
            static::$main_domain[(int) $idShop] = $row['domain'];
            static::$main_domain_ssl[(int) $idShop] = $row['domain_ssl'];
        }
        // @codingStandardsIgnoreEnd
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function resetMainDomainCache()
    {
        // @codingStandardsIgnoreStart
        static::$main_domain = [];
        static::$main_domain_ssl = [];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param null $idShop
     *
     * @return mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMainShopDomain($idShop = null)
    {
        static::cacheMainDomainForShop($idShop);

        // @codingStandardsIgnoreStart
        return static::$main_domain[(int) $idShop];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param int|null $idShop
     *
     * @return mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMainShopDomainSSL($idShop = null)
    {
        static::cacheMainDomainForShop($idShop);

        // @codingStandardsIgnoreStart
        return static::$main_domain_ssl[(int) $idShop];
        // @codingStandardsIgnoreEnd
    }
}
