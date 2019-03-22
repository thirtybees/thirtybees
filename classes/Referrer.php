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
 * Class ReferrerCore
 *
 * @since 1.0.0
 */
class ReferrerCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    protected static $_join = '(r.http_referer_like IS NULL OR r.http_referer_like = \'\' OR cs.http_referer LIKE r.http_referer_like)
			AND (r.request_uri_like IS NULL OR r.request_uri_like = \'\' OR cs.request_uri LIKE r.request_uri_like)
			AND (r.http_referer_like_not IS NULL OR r.http_referer_like_not = \'\' OR cs.http_referer NOT LIKE r.http_referer_like_not)
			AND (r.request_uri_like_not IS NULL OR r.request_uri_like_not = \'\' OR cs.request_uri NOT LIKE r.request_uri_like_not)
			AND (r.http_referer_regexp IS NULL OR r.http_referer_regexp = \'\' OR cs.http_referer REGEXP r.http_referer_regexp)
			AND (r.request_uri_regexp IS NULL OR r.request_uri_regexp = \'\' OR cs.request_uri REGEXP r.request_uri_regexp)
			AND (r.http_referer_regexp_not IS NULL OR r.http_referer_regexp_not = \'\' OR cs.http_referer NOT REGEXP r.http_referer_regexp_not)
			AND (r.request_uri_regexp_not IS NULL OR r.request_uri_regexp_not = \'\' OR cs.request_uri NOT REGEXP r.request_uri_regexp_not)';
    /** @var int $id_shop */
    public $id_shop;
    /** @var string $name */
    public $name;
    /** @var string $passwd */
    public $passwd;
    public $http_referer_regexp;
    public $http_referer_like;
    public $request_uri_regexp;
    public $request_uri_like;
    public $http_referer_regexp_not;
    public $http_referer_like_not;
    public $request_uri_regexp_not;
    public $request_uri_like_not;
    public $base_fee;
    public $percent_fee;
    public $click_fee;
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'referrer',
        'primary' => 'id_referrer',
        'fields'  => [
            'name'                    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'passwd'                  => ['type' => self::TYPE_STRING, 'validate' => 'isPasswd',                          'size' => 60],
            'http_referer_regexp'     => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',                       'size' => 64],
            'request_uri_regexp'      => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',                       'size' => 64],
            'http_referer_like'       => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',                       'size' => 64],
            'request_uri_like'        => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',                       'size' => 64],
            'http_referer_regexp_not' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'request_uri_regexp_not'  => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'http_referer_like_not'   => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'request_uri_like_not'    => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'base_fee'                => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'],
            'percent_fee'             => ['type' => self::TYPE_FLOAT,  'validate' => 'isPercentage'],
            'click_fee'               => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'],
            'date_add'                => ['type' => self::TYPE_DATE,   'validate' => 'isDate'],
        ],
    ];

    /**
     * @param int $idConnectionsSource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function cacheNewSource($idConnectionsSource)
    {
        if (!$idConnectionsSource) {
            return;
        }

        $sql = 'INSERT INTO '._DB_PREFIX_.'referrer_cache (id_referrer, id_connections_source) (
					SELECT id_referrer, id_connections_source
					FROM '._DB_PREFIX_.'referrer r
					LEFT JOIN '._DB_PREFIX_.'connections_source cs ON ('.static::$_join.')
					WHERE id_connections_source = '.(int) $idConnectionsSource.'
				)';
        Db::getInstance()->execute($sql);
    }

    /**
     * Get list of referrers connections of a customer
     *
     * @param int $idCustomer
     *
     * @return array|false|null|PDOStatement
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getReferrers($idCustomer)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('DISTINCT c.`date_add`, r.`name`, s.`name` AS `shop_name`')
                ->from('guest', 'g')
                ->leftJoin('connections', 'c', 'c.`id_guest` = g.`id_guest`')
                ->leftJoin('connections_source', 'cs', 'c.`id_connections` = cs.`id_connections`')
                ->leftJoin('referrer', 'r', static::$_join)
                ->leftJoin('shop', 's', 's.`id_shop` = c.`id_shop`')
                ->where('g.`id_customer` = '.(int) $idCustomer)
                ->where('r.`name` IS NOT NULL')
                ->orderBy('c.`date_add` DESC')
        );
    }

    /**
     * @param int  $idReferrer
     * @param int  $idProduct
     * @param null $employee
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAjaxProduct($idReferrer, $idProduct, $employee = null)
    {
        $product = new Product($idProduct, false, Configuration::get('PS_LANG_DEFAULT'));
        $currency = Currency::getCurrencyInstance(Configuration::get('PS_CURRENCY_DEFAULT'));
        $referrer = new Referrer($idReferrer);
        $statsVisits = $referrer->getStatsVisits($idProduct, $employee);
        $registrations = $referrer->getRegistrations($idProduct, $employee);
        $statsSales = $referrer->getStatsSales($idProduct, $employee);

        // If it's a product and it has no visits nor orders
        if ((int) $idProduct && !$statsVisits['visits'] && !$statsSales['orders']) {
            exit;
        }

        $jsonArray = [
            'id_product'    => (int) $product->id,
            'product_name'  => htmlspecialchars($product->name),
            'uniqs'         => (int) $statsVisits['uniqs'],
            'visitors'      => (int) $statsVisits['visitors'],
            'visits'        => (int) $statsVisits['visits'],
            'pages'         => (int) $statsVisits['pages'],
            'registrations' => (int) $registrations,
            'orders'        => (int) $statsSales['orders'],
            'sales'         => Tools::displayPrice($statsSales['sales'], $currency),
            'cart'          => Tools::displayPrice(((int) $statsSales['orders'] ? $statsSales['sales'] / (int) $statsSales['orders'] : 0), $currency),
            'reg_rate'      => number_format((int) $statsVisits['uniqs'] ? (int) $registrations / (int) $statsVisits['uniqs'] : 0, 4, '.', ''),
            'order_rate'    => number_format((int) $statsVisits['uniqs'] ? (int) $statsSales['orders'] / (int) $statsVisits['uniqs'] : 0, 4, '.', ''),
            'click_fee'     => Tools::displayPrice((int) $statsVisits['visits'] * $referrer->click_fee, $currency),
            'base_fee'      => Tools::displayPrice($statsSales['orders'] * $referrer->base_fee, $currency),
            'percent_fee'   => Tools::displayPrice($statsSales['sales'] * $referrer->percent_fee / 100, $currency),
        ];

        die('['.json_encode($jsonArray).']');
    }

    /**
     * Get some statistics on visitors connection for current referrer
     *
     * @param int $idProduct
     * @param int $employee
     *
     * @return array|bool|null|object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getStatsVisits($idProduct, $employee)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('COUNT(DISTINCT cs.`id_connections_source`) AS `visits`')
                ->select('COUNT(DISTINCT cs.`id_connections`) AS `visitors`')
                ->select('COUNT(DISTINCT c.`id_guest`) AS `uniqs`')
                ->select('COUNT(DISTINCT cp.`time_start`) AS `pages`')
                ->from('referrer_cache', 'rc')
                ->join($idProduct ? 'LEFT JOIN `'._DB_PREFIX_.'page` p ON cp.`id_page` = p.`id_page`' : '')
                ->join($idProduct ? 'LEFT JOIN `'._DB_PREFIX_.'page_type` pt ON pt.`id_page_type` = p.`id_page_type`' : '')
                ->leftJoin('referrer', 'r', 'rc.`id_referrer` = r.`id_referrer`'.($idProduct ? 'AND ('.static::$_join.')' : ''))
                ->leftJoin('referrer_shop', 'rs', 'r.`id_referrer` = rs.`id_referrer`')
                ->leftJoin('connections_source', 'cs', 'rc.`id_connections_source` = cs.`id_connections_source`')
                ->leftJoin('connections', 'c', 'cs.`id_connections` = c.`id_connections`')
                ->leftJoin('connections_page', 'cp', 'cp.`id_connections` = c.`id_connections`')
                ->where((isset($employee->stats_date_from) && isset($employee->stats_date_to)) ? 'cs.`date_add` BETWEEN \''.pSQL($employee->stats_date_from).' 00:00:00\' AND \''.pSQL($employee->stats_date_to).' 23:59:59\'' : '')
                ->where('1 '.Shop::addSqlRestriction(false, 'rs'))
                ->where('1 '.Shop::addSqlRestriction(false, 'c'))
                ->where('rc.`id_referrer` = '.(int) $this->id)
                ->where($idProduct ? 'pt.`name` = \'product\'' : '')
                ->where($idProduct ? 'p.`id_object` = '.(int) $idProduct : '')
        );
    }

    /**
     * Get some statistics on customers registrations for current referrer
     *
     * @param int $idProduct
     * @param int $employee
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getRegistrations($idProduct, $employee)
    {
        $sql = (new DbQuery())
            ->select('COUNT(DISTINCT cu.`id_customer`) AS `registrations`')
            ->from('referrer_cache', 'rc')
            ->leftJoin('referrer_shop', 'rs', 'rc.`id_referrer` = rs.`id_referrer`')
            ->leftJoin('connections_source', 'cs', 'rc.`id_connections_source` = cs.`id_connections_source`')
            ->leftJoin('connections', 'c', 'cs.`id_connections` = c.`id_connections`')
            ->leftJoin('guest', 'g', 'g.`id_guest` = c.`id_guest`')
            ->leftJoin('customer', 'cu', 'cu.`id_customer` = g.`id_customer`')
            ->where('cu.`date_add` BETWEEN '.ModuleGraph::getDateBetween($employee).' '.Shop::addSqlRestriction(false, 'rs').' '.Shop::addSqlRestriction(false, 'c').' '.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER, 'cu'))
            ->where('cu.`date_add` > cs.`date_add`')
            ->where('rc.`id_referrer` = '.(int) $this->id);

        if ($idProduct) {
            $sql->leftJoin('connections_page', 'cp', 'cp.`id_connections` = c.`id_connections`');
            $sql->leftJoin('page', 'p', 'cp.`id_page` = p.`id_page`');
            $sql->leftJoin('page_type', 'pt', 'pt.`id_paget_type` = p.`id_page_type`');
            $sql->where('pt.`name` = \'product\'');
            $sql->where('p.`id_object` = '.(int) $idProduct);
        }
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        return (int) $result['registrations'];
    }

    /**
     * Get some statistics on orders for current referrer
     *
     * @param int $idProduct
     * @param int $employee
     *
     * @return array|bool|null|object
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getStatsSales($idProduct, $employee)
    {
        $sql = (new DbQuery())
            ->select('oo.`id_order`')
            ->from('referrer_cache', 'rc')
            ->leftJoin('referrer_shop', 'rs', 'rc.`id_referrer` = rs.`id_referrer`')
            ->innerJoin('connections_source', 'cs', 'rc.`id_connections_source` = cs.`id_connections_source`')
            ->innerJoin('connections', 'c', 'cs.`id_connections` = c.`id_connections`')
            ->innerJoin('guest', 'g', 'g.`id_guest` = c.`id_guest`')
            ->innerJoin('orders', 'oo', 'oo.`id_customer` = g.`id_customer`')
            ->where('oo.`invoice_date` BETWEEN '.ModuleGraph::getDateBetween($employee).' '.Shop::addSqlRestriction(false, 'rs').' '.Shop::addSqlRestriction(false, 'c').' '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'oo'))
            ->where('oo.`date_add` > cs.`date_add`')
            ->where('rc.`id_referrer` = '.(int) $this->id)
            ->where('oo.`valid` = 1')
        ;

        if ($idProduct) {
            $sql->leftJoin('order_detail', 'od', 'oo.`id_order` = od.`id_order`');
            $sql->where('od.`product_id` = '.(int) $idProduct);
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        $implode = [];
        foreach ($result as $row) {
            if ((int) $row['id_order']) {
                $implode[] = (int) $row['id_order'];
            }
        }

        if ($implode) {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('COUNT(`id_order`) AS `orders`, SUM(`total_paid_real` / `conversion_rate`) AS `sales`')
                    ->from('orders')
                    ->where('`id_order` IN ('.implode($implode, ',').') '.Shop::addSqlRestriction(Shop::SHARE_ORDER))
                    ->where('`valid` = 1')
            );
        } else {
            return ['orders' => 0, 'sales' => 0];
        }
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (!($result = parent::add($autoDate, $nullValues))) {
            return false;
        }
        Referrer::refreshCache([['id_referrer' => $this->id]]);
        Referrer::refreshIndex([['id_referrer' => $this->id]]);

        return $result;
    }

    /**
     * Refresh cache data of referrer statistics in referrer_shop table
     *
     * @param array $referrers
     * @param int   $employee
     *
     * @return true
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function refreshCache($referrers = null, $employee = null)
    {
        if (!$referrers || !is_array($referrers)) {
            $referrers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS((new DbQuery())->select('`id_referrer`')->from('referrer'));
        }
        foreach ($referrers as $row) {
            $referrer = new Referrer($row['id_referrer']);
            foreach (Shop::getShops(true, null, true) as $idShop) {
                if (!$referrer->isAssociatedToShop($idShop)) {
                    continue;
                }

                $statsVisits = $referrer->getStatsVisits(null, $employee);
                $registrations = $referrer->getRegistrations(null, $employee);
                $statsSales = $referrer->getStatsSales(null, $employee);

                Db::getInstance()->update(
                    'referrer_shop',
                    [
                        'cache_visitors'      => (int) $statsVisits['uniqs'],
                        'cache_visits'        => (int) $statsVisits['visits'],
                        'cache_pages'         => (int) $statsVisits['pages'],
                        'cache_registrations' => (int) $registrations,
                        'cache_orders'        => (int) $statsSales['orders'],
                        'cache_sales'         => number_format($statsSales['sales'], 2, '.', ''),
                        'cache_reg_rate'      => $statsVisits['uniqs'] ? $registrations / $statsVisits['uniqs'] : 0,
                        'cache_order_rate'    => $statsVisits['uniqs'] ? $statsSales['orders'] / $statsVisits['uniqs'] : 0,
                    ],
                    'id_referrer = '.(int) $referrer->id.' AND `id_shop` = '.(int) $idShop
                );
            }
        }

        Configuration::updateValue('PS_REFERRERS_CACHE_LIKE', ModuleGraph::getDateBetween($employee));
        Configuration::updateValue('PS_REFERRERS_CACHE_DATE', date('Y-m-d H:i:s'));

        return true;
    }

    /**
     * Cache liaison between connections_source data and referrers data
     *
     * @param array $referrers
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public static function refreshIndex($referrers = null)
    {
        if (!$referrers || !is_array($referrers)) {
            Db::getInstance()->execute('TRUNCATE '._DB_PREFIX_.'referrer_cache');
            Db::getInstance()->execute(
                '
			INSERT INTO '._DB_PREFIX_.'referrer_cache (id_referrer, id_connections_source) (
				SELECT id_referrer, id_connections_source
				FROM '._DB_PREFIX_.'referrer r
				LEFT JOIN '._DB_PREFIX_.'connections_source cs ON ('.static::$_join.')
			)'
            );
        } else {
            foreach ($referrers as $row) {
                Db::getInstance()->delete('referrer_cache', '`id_referrer` = '.(int) $row['id_referrer']);
                Db::getInstance()->execute(
                    '
				INSERT INTO '._DB_PREFIX_.'referrer_cache (id_referrer, id_connections_source) (
					SELECT id_referrer, id_connections_source
					FROM '._DB_PREFIX_.'referrer r
					LEFT JOIN '._DB_PREFIX_.'connections_source cs ON ('.static::$_join.')
					WHERE id_referrer = '.(int) $row['id_referrer'].'
					AND id_connections_source IS NOT NULL
				)'
                );
            }
        }
    }
}
