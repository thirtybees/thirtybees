<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class StatsModule
 */
class StatsModule extends ModuleStats
{
    private $query;
    private $columns;
    private $default_sort_column;
    private $default_sort_direction;
    private $empty_message;
    private $paging_message;

    public $modules = [
        'statsbestcategories',
        'statsregistrations',
        'statsstock',
        'statsbestcustomers',
        'statsbestsuppliers',
        'statsbestvouchers',
        'statsbestproducts',
        'statsequipment',
        'statscarrier',
        'statscheckup',
        'statscatalog',
        'statsnewsletter',
        'pagesnotfound',
        'statsproduct',
        'statspersonalinfos',
        'statssales',
        'sekeywords',
        'statssearch',
        'statsforecast',
        'statslive',
        'statsorigin',
        'statsvisits',
    ];

    public function __construct()
    {
        $this->name = 'statsmodule';
        $this->tab = 'analytics_stats';
        $this->version = '2.0.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        parent::__construct();

        $this->default_sort_column = 'totalPriceSold';
        $this->default_sort_direction = 'DESC';
        $this->empty_message = $this->l('Empty recordset returned');
        $this->paging_message = sprintf($this->l('Displaying %1$s of %2$s'), '{0} - {1}', '{2}');

        $this->columns = [
            [
                'id'        => 'name',
                'header'    => $this->l('Name'),
                'dataIndex' => 'name',
                'align'     => 'left',
            ],
            [
                'id'        => 'totalQuantitySold',
                'header'    => $this->l('Total Quantity Sold'),
                'dataIndex' => 'totalQuantitySold',
                'align'     => 'center',
            ],
            [
                'id'        => 'totalPriceSold',
                'header'    => $this->l('Total Price'),
                'dataIndex' => 'totalPriceSold',
                'align'     => 'right',
            ],
            [
                'id'        => 'totalWholeSalePriceSold',
                'header'    => $this->l('Total Margin'),
                'dataIndex' => 'totalWholeSalePriceSold',
                'align'     => 'center',
            ],
            [
                'id'        => 'totalPageViewed',
                'header'    => $this->l('Total Viewed'),
                'dataIndex' => 'totalPageViewed',
                'align'     => 'center',
            ],
        ];

        $this->displayName = $this->l('Stats Module');
        $this->description = $this->l('Addds several statistics to the shop');
//        $this->tb_versions_compliancy = '1.0.4+';
    }

    /**
     * Install this module
     *
     * @return bool
     */
    public function install()
    {

        foreach ($this->modules as $moduleCode) {

            $moduleInstance = Module::getInstanceByName($moduleCode);


            if(is_dir(_PS_MODULE_DIR_.$moduleCode))
            {
                try {
                    if($moduleInstance->uninstall() || !Module::isInstalled($moduleCode))
                        $this->recursiveDeleteOnDisk(_PS_MODULE_DIR_.$moduleCode);
                } catch (Exception $e) {
                    // Let it fail, time to go on
                }    
            }
            
        }
                
        if (!parent::install() || !$this->registerHook('search') || !$this->registerHook('top') || !$this->registerHook('AdminStatsModules')) {
            return false;
        }


        // statscheckup
        $confs = [
            'CHECKUP_DESCRIPTIONS_LT' => 100,
            'CHECKUP_DESCRIPTIONS_GT' => 400,
            'CHECKUP_IMAGES_LT'       => 1,
            'CHECKUP_IMAGES_GT'       => 2,
            'CHECKUP_SALES_LT'        => 1,
            'CHECKUP_SALES_GT'        => 2,
            'CHECKUP_STOCK_LT'        => 1,
            'CHECKUP_STOCK_GT'        => 3,
        ];
        foreach ($confs as $confname => $confdefault) {
            if (!Configuration::get($confname)) {
                Configuration::updateValue($confname, (int) $confdefault);
            }
        }

        // Search Engine Keywords
        Configuration::updateValue('SEK_MIN_OCCURENCES', 1);
        Configuration::updateValue('SEK_FILTER_KW', '');

        $sek = Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'sekeyword` (
			id_sekeyword INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
			id_shop INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			id_shop_group INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			keyword VARCHAR(256) NOT NULL,
			date_add DATETIME NOT NULL,
			PRIMARY KEY(id_sekeyword)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8');

        $pagenotfound = Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'pagenotfound` (
			id_pagenotfound INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
			id_shop INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			id_shop_group INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			request_uri VARCHAR(256) NOT NULL,
			http_referer VARCHAR(256) NOT NULL,
			date_add DATETIME NOT NULL,
			PRIMARY KEY(id_pagenotfound),
			INDEX (`date_add`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );

        $statssearch = Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'statssearch` (
			id_statssearch INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
			id_shop INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
		  	id_shop_group INTEGER UNSIGNED NOT NULL DEFAULT \'1\',
			keywords VARCHAR(255) NOT NULL,
			results INT(6) NOT NULL DEFAULT 0,
			date_add DATETIME NOT NULL,
			PRIMARY KEY(id_statssearch)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8');

        return $sek && $pagenotfound && $statssearch;
    }


    private function recursiveDeleteOnDisk($dir)
    {
        if (strpos(realpath($dir), realpath(_PS_MODULE_DIR_)) === false) {
            return;
        }
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir.'/'.$object) == 'dir') {
                        $this->recursiveDeleteOnDisk($dir.'/'.$object);
                    } else {
                        unlink($dir.'/'.$object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }


    public function getStatsModulesList()
    {
        foreach ($this->modules as $module) {
            $list[] = ['name' => $module];
        }

        return $list;
    }

    public function executeStatsInstance($moduleName, $hook = false)
    {
        require_once(dirname(__FILE__).'/stats/'.$moduleName.'.php');
        $module = new $moduleName();
        if ($hook) {
            return $module->hookAdminStatsModules(null);
        } else {
            return $module;
        }
    }


    protected function engine($type, $params)
    {
        return call_user_func_array([$this, 'engine'.$type], [$params]);
    }

    protected function getData($layers = null)
    {
        $currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        $dateBetween = $this->getDate();
        $idLang = $this->getLang();

        //If column 'order_detail.original_wholesale_price' does not exist, create it
        Db::getInstance(_PS_USE_SQL_SLAVE_)->query('SHOW COLUMNS FROM `'._DB_PREFIX_.'order_detail` LIKE "original_wholesale_price"');
        if (Db::getInstance()->NumRows() == 0) {
            Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'order_detail` ADD `original_wholesale_price` DECIMAL( 20, 6 ) NOT NULL DEFAULT  "0.000000"');
        }

        // If a shop is selected, get all children categories for the shop
        $categories = [];
        if (Shop::getContext() != Shop::CONTEXT_ALL) {
            $sql = 'SELECT c.nleft, c.nright
					FROM '._DB_PREFIX_.'category c
					WHERE c.id_category IN (
						SELECT s.id_category
						FROM '._DB_PREFIX_.'shop s
						WHERE s.id_shop IN ('.implode(', ', Shop::getContextListShopID()).')
					)';
            if ($result = Db::getInstance()->executeS($sql)) {
                $ntree_restriction = array();
                foreach ($result as $row) {
                    $ntree_restriction[] = '(nleft >= '.$row['nleft'].' AND nright <= '.$row['nright'].')';
                }

                if ($ntree_restriction) {
                    $sql = 'SELECT id_category
							FROM '._DB_PREFIX_.'category
							WHERE '.implode(' OR ', $ntree_restriction);
                    if ($result = Db::getInstance()->executeS($sql)) {
                        foreach ($result as $row) {
                            $categories[] = $row['id_category'];
                        }
                    }
                }
            }
        }

        $onlyChildren = '';
        if ((int) Tools::getValue('onlyChildren') == 1) {
            $onlyChildren = 'AND NOT EXISTS (SELECT NULL FROM '._DB_PREFIX_.'category WHERE id_parent = ca.id_category)';
        }

        // Get best categories
        if (version_compare(_PS_VERSION_, '1.6.1.1', '>=')) {
            $this->query = '
				SELECT SQL_CALC_FOUND_ROWS ca.`id_category`, CONCAT(parent.name, \' > \', calang.`name`) AS name,
				IFNULL(SUM(t.`totalQuantitySold`), 0) AS totalQuantitySold,
				ROUND(IFNULL(SUM(t.`totalPriceSold`), 0), 2) AS totalPriceSold,
				ROUND(IFNULL(SUM(t.`totalWholeSalePriceSold`), 0), 2) AS totalWholeSalePriceSold,
				(
					SELECT IFNULL(SUM(pv.`counter`), 0)
					FROM `'._DB_PREFIX_.'page` p
					LEFT JOIN `'._DB_PREFIX_.'page_viewed` pv ON p.`id_page` = pv.`id_page`
					LEFT JOIN `'._DB_PREFIX_.'date_range` dr ON pv.`id_date_range` = dr.`id_date_range`
					LEFT JOIN `'._DB_PREFIX_.'product` pr ON CAST(p.`id_object` AS UNSIGNED INTEGER) = pr.`id_product`
					LEFT JOIN `'._DB_PREFIX_.'category_product` capr2 ON capr2.`id_product` = pr.`id_product`
					WHERE capr.`id_category` = capr2.`id_category`
					AND p.`id_page_type` = 1
					AND dr.`time_start` BETWEEN '.$dateBetween.'
					AND dr.`time_end` BETWEEN '.$dateBetween.'
				) AS totalPageViewed,
				(
                    SELECT COUNT(id_category) FROM '._DB_PREFIX_.'category WHERE `id_parent` = ca.`id_category`
			    ) AS hasChildren
			FROM `'._DB_PREFIX_.'category` ca
			LEFT JOIN `'._DB_PREFIX_.'category_lang` calang ON (ca.`id_category` = calang.`id_category` AND calang.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('calang').')
			LEFT JOIN `'._DB_PREFIX_.'category_lang` parent ON (ca.`id_parent` = parent.`id_category` AND parent.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('parent').')
			LEFT JOIN `'._DB_PREFIX_.'category_product` capr ON ca.`id_category` = capr.`id_category`
			LEFT JOIN (
				SELECT pr.`id_product`, t.`totalQuantitySold`, t.`totalPriceSold`, t.`totalWholeSalePriceSold`
				FROM `'._DB_PREFIX_.'product` pr
				LEFT JOIN (
					SELECT pr.`id_product`, pa.`wholesale_price`,
						IFNULL(SUM(cp.`product_quantity`), 0) AS totalQuantitySold,
						IFNULL(SUM(cp.`product_price` * cp.`product_quantity`), 0) / o.conversion_rate AS totalPriceSold,
						IFNULL(SUM(
							CASE
								WHEN cp.`original_wholesale_price` <> "0.000000"
								THEN cp.`original_wholesale_price` * cp.`product_quantity`
								WHEN pa.`wholesale_price` <> "0.000000"
								THEN pa.`wholesale_price` * cp.`product_quantity`
								WHEN pr.`wholesale_price` <> "0.000000"
								THEN pr.`wholesale_price` * cp.`product_quantity`
							END
						), 0) / o.conversion_rate AS totalWholeSalePriceSold
					FROM `'._DB_PREFIX_.'product` pr
					LEFT OUTER JOIN `'._DB_PREFIX_.'order_detail` cp ON pr.`id_product` = cp.`product_id`
					LEFT JOIN `'._DB_PREFIX_.'orders` o ON o.`id_order` = cp.`id_order`
					LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON pa.`id_product_attribute` = cp.`product_attribute_id`
					'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
					WHERE o.valid = 1
					AND o.invoice_date BETWEEN '.$dateBetween.'
					GROUP BY pr.`id_product`
				) t ON t.`id_product` = pr.`id_product`
			) t	ON t.`id_product` = capr.`id_product`
			'.(($categories) ? 'WHERE ca.id_category IN ('.implode(', ', $categories).')' : '').'
			'.$onlyChildren.'
			GROUP BY ca.`id_category`
			HAVING ca.`id_category` != 1';
        } else {
            $this->query = '
			SELECT SQL_CALC_FOUND_ROWS ca.`id_category`, CONCAT(parent.name, \' > \', calang.`name`) AS name,
				IFNULL(SUM(t.`totalQuantitySold`), 0) AS totalQuantitySold,
				ROUND(IFNULL(SUM(t.`totalPriceSold`), 0), 2) AS totalPriceSold,
				(
					SELECT IFNULL(SUM(pv.`counter`), 0)
					FROM `'._DB_PREFIX_.'page` p
					LEFT JOIN `'._DB_PREFIX_.'page_viewed` pv ON p.`id_page` = pv.`id_page`
					LEFT JOIN `'._DB_PREFIX_.'date_range` dr ON pv.`id_date_range` = dr.`id_date_range`
					LEFT JOIN `'._DB_PREFIX_.'product` pr ON CAST(p.`id_object` AS UNSIGNED INTEGER) = pr.`id_product`
					LEFT JOIN `'._DB_PREFIX_.'category_product` capr2 ON capr2.`id_product` = pr.`id_product`
					WHERE capr.`id_category` = capr2.`id_category`
					AND p.`id_page_type` = 1
					AND dr.`time_start` BETWEEN '.$dateBetween.'
					AND dr.`time_end` BETWEEN '.$dateBetween.'
				) AS totalPageViewed,
				(
                    SELECT COUNT(id_category) FROM '._DB_PREFIX_.'category WHERE `id_parent` = ca.`id_category`
			    ) AS hasChildren
			FROM `'._DB_PREFIX_.'category` ca
			LEFT JOIN `'._DB_PREFIX_.'category_lang` calang ON (ca.`id_category` = calang.`id_category` AND calang.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('calang').')
			LEFT JOIN `'._DB_PREFIX_.'category_lang` parent ON (ca.`id_parent` = parent.`id_category` AND parent.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('parent').')
			LEFT JOIN `'._DB_PREFIX_.'category_product` capr ON ca.`id_category` = capr.`id_category`
			LEFT JOIN (
				SELECT pr.`id_product`, t.`totalQuantitySold`, t.`totalPriceSold`
				FROM `'._DB_PREFIX_.'product` pr
				LEFT JOIN (
					SELECT pr.`id_product`,
					IFNULL(SUM(cp.`product_quantity`), 0) AS totalQuantitySold,
					IFNULL(SUM(cp.`product_price` * cp.`product_quantity`), 0) / o.conversion_rate AS totalPriceSold
					FROM `'._DB_PREFIX_.'product` pr
					LEFT OUTER JOIN `'._DB_PREFIX_.'order_detail` cp ON pr.`id_product` = cp.`product_id`
					LEFT JOIN `'._DB_PREFIX_.'orders` o ON o.`id_order` = cp.`id_order`
					'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
					WHERE o.valid = 1
					AND o.invoice_date BETWEEN '.$dateBetween.'
					GROUP BY pr.`id_product`
				) t ON t.`id_product` = pr.`id_product`
			) t	ON t.`id_product` = capr.`id_product`
			'.(($categories) ? 'WHERE ca.id_category IN ('.implode(', ', $categories).')' : '').'
			'.$onlyChildren.'
			GROUP BY ca.`id_category`
			HAVING ca.`id_category` != 1';
        }

        if (Validate::IsName($this->_sort)) {
            $this->query .= ' ORDER BY `'.bqSQL($this->_sort).'`';
            if (isset($this->_direction) && Validate::isSortDirection($this->_direction)) {
                $this->query .= ' '.$this->_direction;
            }
        }

        if (($this->_start === 0 || Validate::IsUnsignedInt($this->_start)) && Validate::IsUnsignedInt($this->_limit)) {
            $this->query .= ' LIMIT '.(int) $this->_start.', '.(int) $this->_limit;
        }

        $values = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($this->query);
        foreach ($values as &$value) {
            if ((int) Tools::getIsset('export') == false) {
                $parts = explode('>', $value['name']);
                $value['name'] = '<i class="icon-folder-open"></i> '.trim($parts[0]).' > ';
                if ((int) $value['hasChildren'] == 0) {
                    $value['name'] .= '&bull; ';
                } else {
                    $value['name'] .= '<i class="icon-folder-open"></i> ';
                }
                $value['name'] .= trim($parts[1]);
            }

            if (isset($value['totalWholeSalePriceSold'])) {
                $value['totalWholeSalePriceSold'] = Tools::displayPrice($value['totalPriceSold'] - $value['totalWholeSalePriceSold'], $currency);
            }
            $value['totalPriceSold'] = Tools::displayPrice($value['totalPriceSold'], $currency);
        }

        $this->_values = $values;
        $this->_totalCount = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT FOUND_ROWS()');
    }

    public function render()
    {
        $this->_render->render();
    }

    public function hookSearch($params)
    {
        $sql = 'INSERT INTO `'._DB_PREFIX_.'statssearch` (`id_shop`, `id_shop_group`, `keywords`, `results`, `date_add`)
				VALUES ('.(int) $this->context->shop->id.', '.(int) $this->context->shop->id_shop_group.', \''.pSQL($params['expr']).'\', '.(int) $params['total'].', NOW())';
        Db::getInstance()->execute($sql);
    }

    /**
     * @param array $params Module params
     *
     * @return string
     */
    public function hookTop($params)
    {
        foreach ($this->modules as $moduleName) {
            if (include_once dirname(__FILE__).'/stats/'.$moduleName.'.php') {
                $module = new $moduleName();
                $refl = new ReflectionClass($moduleName);

                if ($refl->getMethod('hookTop')->class != 'StatsModule') {
                    return $module->hookTop($params);
                }
            }
        }

        return '';
    }
}
