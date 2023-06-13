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
 */
class ShopUrlCore extends ObjectModel
{
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

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'shop_url',
        'primary' => 'id_shop_url',
        'fields'  => [
            'id_shop'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true               ],
            'domain'       => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 150],
            'domain_ssl'   => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 150, 'dbNullable' => false],
            'physical_uri' => ['type' => self::TYPE_STRING, 'validate' => 'isUriPath', 'size' => 64, 'dbNullable' => false],
            'virtual_uri'  => ['type' => self::TYPE_STRING, 'validate' => 'isUriPath', 'size' => 64, 'dbNullable' => false],
            'main'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbNullable' => false],
            'active'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbNullable' => false],
        ],
        'keys' => [
            'shop_url' => [
                'full_shop_url'     => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['domain', 'physical_uri', 'virtual_uri']],
                'full_shop_url_ssl' => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['domain_ssl', 'physical_uri', 'virtual_uri']],
                'id_shop'           => ['type' => ObjectModel::KEY, 'columns' => ['id_shop', 'main']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'fields' => [
            'id_shop' => ['xlink_resource' => 'shops'],
        ],
    ];

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function getFields()
    {
        $this->domain = trim($this->domain);
        $this->domain_ssl = trim($this->domain_ssl);

        if ($this->physical_uri) {
            $this->physical_uri = trim(str_replace(' ', '', $this->physical_uri), '/');
            $this->physical_uri = preg_replace('#/+#', '/', '/'.$this->physical_uri.'/');
        } else {
            $this->physical_uri = '/';
        }

        if ($this->virtual_uri) {
            $this->virtual_uri = trim(str_replace(' ', '', $this->virtual_uri), '/');
            $this->virtual_uri = preg_replace('#/+#', '/', trim($this->virtual_uri, '/')).'/';
        }

        return parent::getFields();
    }

    /**
     * @return string
     */
    public function getBaseURI()
    {
        return $this->physical_uri.$this->virtual_uri;
    }

    /**
     * @param bool $ssl
     *
     * @return string|null
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
     */
    public function setMain()
    {
        $conn = Db::getInstance();

        $res = $conn->update('shop_url', ['main' => 0], 'id_shop = '.(int) $this->id_shop);
        $res = $conn->update('shop_url', ['main' => 1], 'id_shop_url = '.(int) $this->id) && $res;

        $this->main = true;

        // Reset main URL for all shops to prevent problems
        $sql = 'SELECT s1.id_shop_url FROM '._DB_PREFIX_.'shop_url s1
				WHERE (
					SELECT COUNT(*) FROM '._DB_PREFIX_.'shop_url s2
					WHERE s2.main = 1
					AND s2.id_shop = s1.id_shop
				) = 0
				GROUP BY s1.id_shop';
        foreach ($conn->getArray($sql) as $row) {
            $conn->update('shop_url', ['main' => 1], 'id_shop_url = '.$row['id_shop_url']);
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

        return Db::readOnly()->getValue(
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
     */
    public static function cacheMainDomainForShop($idShop)
    {
        if (!isset(static::$main_domain_ssl[(int) $idShop]) || !isset(static::$main_domain[(int) $idShop])) {
            $row = Db::readOnly()->getRow(
                (new DbQuery())
                    ->select('`domain`, `domain_ssl`')
                    ->from('shop_url')
                    ->where('`main` = 1')
                    ->where('`id_shop` = '.($idShop !== null ? (int) $idShop : (int) Context::getContext()->shop->id))
            );
            static::$main_domain[(int)$idShop] = isset($row['domain']) ? $row['domain'] : '';
            static::$main_domain_ssl[(int)$idShop] = isset($row['domain_ssl']) ? $row['domain_ssl'] : '';
        }
    }

    /**
     * @return void
     */
    public static function resetMainDomainCache()
    {
        static::$main_domain = [];
        static::$main_domain_ssl = [];
    }

    /**
     * @param int|null $idShop
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getMainShopDomain($idShop = null)
    {
        static::cacheMainDomainForShop($idShop);

        return static::$main_domain[(int) $idShop];
    }

    /**
     * @param int|null $idShop
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getMainShopDomainSSL($idShop = null)
    {
        static::cacheMainDomainForShop($idShop);

        return static::$main_domain_ssl[(int) $idShop];
    }
}
