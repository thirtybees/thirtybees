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
 * Class AdminStockInstantStateControllerCore
 *
 * @since 1.0.0
 */
class AdminStockInstantStateControllerCore extends AdminController
{
    protected $stock_instant_state_warehouses = [];

    /**
     * AdminStockInstantStateControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'stock';
        $this->list_id = 'stock';
        $this->className = 'Stock';
        $this->tpl_list_vars['show_filter'] = true;
        $this->lang = false;
        $this->multishop_context = Shop::CONTEXT_ALL;

        $this->fields_list = [
            'reference'         => [
                'title'        => $this->l('Reference'),
                'align'        => 'center',
                'havingFilter' => true,
            ],
            'ean13'             => [
                'title' => $this->l('EAN13'),
                'align' => 'center',
            ],
            'upc'               => [
                'title' => $this->l('UPC'),
                'align' => 'center',
            ],
            'name'              => [
                'title'        => $this->l('Name'),
                'havingFilter' => true,
            ],
            'price_te'          => [
                'title'    => $this->l('Price (tax excl.)'),
                'orderby'  => true,
                'search'   => false,
                'type'     => 'price',
                'currency' => true,
            ],
            'valuation'         => [
                'title'    => $this->l('Valuation'),
                'orderby'  => false,
                'search'   => false,
                'type'     => 'price',
                'currency' => true,
                'hint'     => $this->l('Total value of the physical quantity. The sum (for all prices) is not available for all warehouses, please filter by warehouse.'),
            ],
            'physical_quantity' => [
                'title'   => $this->l('Physical quantity'),
                'class'   => 'fixed-width-xs',
                'align'   => 'center',
                'orderby' => true,
                'search'  => false,
            ],
            'usable_quantity'   => [
                'title'   => $this->l('Usable quantity'),
                'class'   => 'fixed-width-xs',
                'align'   => 'center',
                'orderby' => true,
                'search'  => false,
            ],
        ];

        $this->addRowAction('details');
        $this->stock_instant_state_warehouses = Warehouse::getWarehouses(true);
        array_unshift($this->stock_instant_state_warehouses, ['id_warehouse' => -1, 'name' => $this->l('All Warehouses')]);

        parent::__construct();
    }

    /**
     * @return void
     *
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_title = $this->l('Instant stock status');

        if ($this->display == 'details') {
            $this->page_header_toolbar_btn['back_to_list'] = [
                'href' => $this->context->link->getAdminLink('AdminStockInstantState').(Tools::getValue('id_warehouse') ? '&id_warehouse='.Tools::getValue('id_warehouse') : ''),
                'desc' => $this->l('Back to list', null, null, false),
                'icon' => 'process-icon-back',
            ];
        } elseif (Tools::isSubmit('id_warehouse') && (int) Tools::getValue('id_warehouse') != -1) {
            $this->page_header_toolbar_btn['export-stock-state-quantities-csv'] = [
                'short' => $this->l('Export this list as CSV', null, null, false),
                'href'  => $this->context->link->getAdminLink('AdminStockInstantState').'&csv_quantities&id_warehouse='.(int) $this->getCurrentCoverageWarehouse(),
                'desc'  => $this->l('Export Quantities (CSV)', null, null, false),
                'class' => 'process-icon-export',
            ];

            $this->page_header_toolbar_btn['export-stock-state-prices-csv'] = [
                'short' => $this->l('Export this list as CSV', null, null, false),
                'href'  => $this->context->link->getAdminLink('AdminStockInstantState').'&csv_prices&id_warehouse='.(int) $this->getCurrentCoverageWarehouse(),
                'desc'  => $this->l('Export Prices (CSV)', null, null, false),
                'class' => 'process-icon-export',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Gets the current warehouse used
     *
     * @return int
     *
     * @since 1.0.0
     */
    protected function getCurrentCoverageWarehouse()
    {
        static $warehouse = 0;

        if ($warehouse == 0) {
            $warehouse = -1; // all warehouses
            if ((int) Tools::getValue('id_warehouse')) {
                $warehouse = (int) Tools::getValue('id_warehouse');
            }
        }

        return $warehouse;
    }

    /**
     * AdminController::renderList() override
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        $this->fields_list['real_quantity'] = [
            'title'   => $this->l('Real quantity'),
            'class'   => 'fixed-width-xs',
            'align'   => 'center',
            'orderby' => false,
            'search'  => false,
            'hint'    => $this->l('Physical quantity (usable) - Client orders + Supply Orders'),
        ];

        // query
        $this->_select = 'IFNULL(pa.ean13, p.ean13) as ean13,
            IFNULL(pa.upc, p.upc) as upc,
            IFNULL(pa.reference, p.reference) as reference,
			IFNULL(CONCAT(pl.name, \' : \', GROUP_CONCAT(DISTINCT agl.`name`, \' - \', al.name SEPARATOR \', \')),pl.name) as name,
			w.id_currency';

        $this->_join = 'INNER JOIN `'._DB_PREFIX_.'product` p ON (p.id_product = a.id_product AND p.advanced_stock_management = 1)';
        $this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'warehouse` w ON (w.id_warehouse = a.id_warehouse)';
        $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
			a.id_product = pl.id_product
			AND pl.id_lang = '.(int) $this->context->language->id.'
		)';
        $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON (pac.id_product_attribute = a.id_product_attribute AND a.id_product_attribute!=0)';
        $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (pa.id_product_attribute = a.id_product_attribute)';
        $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'attribute` atr ON (atr.id_attribute = pac.id_attribute)';
        $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (
			al.id_attribute = pac.id_attribute
			AND al.id_lang = '.(int) $this->context->language->id.'
		)';
        $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (
			agl.id_attribute_group = atr.id_attribute_group
			AND agl.id_lang = '.(int) $this->context->language->id.'
		)';

        $this->_group = 'GROUP BY a.id_product, a.id_product_attribute';

        $this->_orderBy = 'name';
        $this->_orderWay = 'ASC';

        if ($this->getCurrentCoverageWarehouse() != -1) {
            $this->_where .= ' AND a.id_warehouse = '.$this->getCurrentCoverageWarehouse();
            static::$currentIndex .= '&id_warehouse='.(int) $this->getCurrentCoverageWarehouse();
        }

        // toolbar btn
        $this->toolbar_btn = [];
        // disables link
        $this->list_no_link = true;

        // smarty
        $this->tpl_list_vars['stock_instant_state_warehouses'] = $this->stock_instant_state_warehouses;
        $this->tpl_list_vars['stock_instant_state_cur_warehouse'] = $this->getCurrentCoverageWarehouse();
        // adds ajax params
        $this->ajax_params = ['id_warehouse' => $this->getCurrentCoverageWarehouse()];

        // displays help information
        $this->displayInformation($this->l('This interface allows you to display detailed information about your stock per warehouse.'));

        // sets toolbar
        $this->initToolbar();

        $list = parent::renderList();

        // if export requested
        if ((Tools::isSubmit('csv_quantities') || Tools::isSubmit('csv_prices')) &&
            (int) Tools::getValue('id_warehouse') != -1
        ) {
            if (count($this->_list) > 0) {
                $this->renderCSV();
                die;
            } else {
                $this->displayWarning($this->l('There is nothing to export as CSV.'));
            }
        }

        return $list;
    }

    /**
     * @since 1.0.0
     */
    public function initToolbar()
    {
        if (Tools::isSubmit('id_warehouse') && (int) Tools::getValue('id_warehouse') != -1) {
            $this->toolbar_btn['export-stock-state-quantities-csv'] = [
                'short' => 'Export this list as CSV',
                'href'  => $this->context->link->getAdminLink('AdminStockInstantState').'&csv_quantities&id_warehouse='.(int) $this->getCurrentCoverageWarehouse(),
                'desc'  => $this->l('Export Quantities (CSV)'),
                'class' => 'process-icon-export',
            ];

            $this->toolbar_btn['export-stock-state-prices-csv'] = [
                'short' => 'Export this list as CSV',
                'href'  => $this->context->link->getAdminLink('AdminStockInstantState').'&csv_prices&id_warehouse='.(int) $this->getCurrentCoverageWarehouse(),
                'desc'  => $this->l('Export Prices (CSV)'),
                'class' => 'process-icon-export',
            ];
        }
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    /**
     * Export CSV
     *
     * @since 1.0.0
     */
    public function renderCSV()
    {
        if (count($this->_list) <= 0) {
            return;
        }

        // sets warehouse id and warehouse name
        $idWarehouse = (int) Tools::getValue('id_warehouse');
        $warehouseName = Warehouse::getWarehouseNameById($idWarehouse);

        // if quantities requested
        if (Tools::isSubmit('csv_quantities')) {
            // filename
            $filename = $this->l('stock_instant_state_quantities').'_'.$warehouseName.'.csv';

            // header
            header('Content-type: text/csv');
            header('Cache-Control: no-store, no-cache must-revalidate');
            header('Content-disposition: attachment; filename="'.$filename);

            // puts keys
            $keys = ['id_product', 'id_product_attribute', 'reference', 'ean13', 'upc', 'name', 'physical_quantity', 'usable_quantity', 'real_quantity'];
            echo sprintf("%s\n", implode(';', $keys));

            // puts rows
            foreach ($this->_list as $row) {
                $rowCsv = [
                    $row['id_product'], $row['id_product_attribute'], $row['reference'],
                    $row['ean13'], $row['upc'], $row['name'],
                    $row['physical_quantity'], $row['usable_quantity'], $row['real_quantity'],
                ];

                // puts one row
                echo sprintf("%s\n", implode(';', array_map(['CSVCore', 'wrap'], $rowCsv)));
            }
        } // if prices requested
        elseif (Tools::isSubmit('csv_prices')) {
            // sets filename
            $filename = $this->l('stock_instant_state_prices').'_'.$warehouseName.'.csv';

            // header
            header('Content-type: text/csv');
            header('Cache-Control: no-store, no-cache must-revalidate');
            header('Content-disposition: attachment; filename="'.$filename);

            // puts keys
            $keys = ['id_product', 'id_product_attribute', 'reference', 'ean13', 'upc', 'name', 'price_te', 'physical_quantity', 'usable_quantity'];
            echo sprintf("%s\n", implode(';', $keys));

            foreach ($this->_list as $row) {
                $idProduct = (int) $row['id_product'];
                $idProductAttribute = (int) $row['id_product_attribute'];

                // gets prices
                $query = new DbQuery();
                $query->select('s.price_te, SUM(s.physical_quantity) as physical_quantity, SUM(s.usable_quantity) as usable_quantity');
                $query->from('stock', 's');
                $query->leftJoin('warehouse', 'w', 'w.id_warehouse = s.id_warehouse');
                $query->where('s.id_product = '.$idProduct.' AND s.id_product_attribute = '.$idProductAttribute);
                $query->where('s.id_warehouse = '.$idWarehouse);
                $query->groupBy('s.price_te');
                $datas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

                // puts data
                foreach ($datas as $data) {
                    $rowCsv = [
                        $row['id_product'], $row['id_product_attribute'], $row['reference'],
                        $row['ean13'], $row['upc'], $row['name'],
                        $data['price_te'], $data['physical_quantity'], $data['usable_quantity'],
                    ];

                    // puts one row
                    echo sprintf("%s\n", implode(';', array_map(['CSVCore', 'wrap'], $rowCsv)));
                }
            }
        }
    }

    /**
     * Render details
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderDetails()
    {
        if (Tools::isSubmit('id_stock')) {
            // if a product id is submit

            $this->list_no_link = true;
            $this->lang = false;
            $this->table = 'stock';
            $this->list_id = 'details';
            $this->tpl_list_vars['show_filter'] = false;
            $this->actions = [];
            $this->list_simple_header = true;
            $ids = explode('_', Tools::getValue('id_stock'));

            if (count($ids) != 2) {
                die;
            }

            $idProduct = $ids[0];
            $idProductAttribute = $ids[1];
            $idWarehouse = Tools::getValue('id_warehouse', -1);
            $this->_select = 'IFNULL(pa.ean13, p.ean13) as ean13,
                IFNULL(pa.upc, p.upc) as upc,
                IFNULL(pa.reference, p.reference) as reference,
                IFNULL(CONCAT(pl.name, \' : \', GROUP_CONCAT(DISTINCT agl.`name`, \' - \', al.name SEPARATOR \', \')),pl.name) as name,
				w.id_currency, a.price_te';
            $this->_join = 'INNER JOIN `'._DB_PREFIX_.'product` p ON (p.id_product = a.id_product AND p.advanced_stock_management = 1)';
            $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'warehouse` AS w ON w.id_warehouse = a.id_warehouse';
            $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
				a.id_product = pl.id_product
				AND pl.id_lang = '.(int) $this->context->language->id.'
			)';
            $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON (pac.id_product_attribute = a.id_product_attribute)';
            $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (pa.id_product_attribute = a.id_product_attribute)';
            $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'attribute` atr ON (atr.id_attribute = pac.id_attribute)';
            $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (
				al.id_attribute = pac.id_attribute
				AND al.id_lang = '.(int) $this->context->language->id.'
			)';
            $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (
				agl.id_attribute_group = atr.id_attribute_group
				AND agl.id_lang = '.(int) $this->context->language->id.'
			)';
            $this->_where = 'AND a.id_product = '.(int) $idProduct.' AND a.id_product_attribute = '.(int) $idProductAttribute;

            if ($idWarehouse != -1) {
                $this->_where .= ' AND a.id_warehouse = '.(int) $idWarehouse;
            }

            $this->_orderBy = 'name';
            $this->_orderWay = 'ASC';

            $this->_group = 'GROUP BY a.price_te';

            static::$currentIndex = static::$currentIndex.'&id_stock='.Tools::getValue('id_stock').'&detailsstock';

            return parent::renderList();
        }

        return '';
    }

    /**
     * AdminController::getList() override
     *
     * @see AdminController::getList()
     *
     * @param int         $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int         $start
     * @param int|null    $limit
     * @param int|bool    $idLangShop
     *
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        if (Tools::isSubmit('id_stock')) {
            parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

            $nbItems = count($this->_list);

            for ($i = 0; $i < $nbItems; $i++) {
                $item = &$this->_list[$i];
                /** @var StockManager $manager */
                $manager = StockManagerFactory::getManager();

                // gets quantities and valuation
                $query = new DbQuery();
                $query->select('physical_quantity');
                $query->select('usable_quantity');
                $query->select('SUM(price_te * physical_quantity) as valuation');
                $query->from('stock');
                $query->where('id_stock = '.(int) $item['id_stock'].' AND id_product = '.(int) $item['id_product'].' AND id_product_attribute = '.(int) $item['id_product_attribute']);

                if ($this->getCurrentCoverageWarehouse() != -1) {
                    $query->where('id_warehouse = '.(int) $this->getCurrentCoverageWarehouse());
                }

                $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);

                $item['physical_quantity'] = $res['physical_quantity'];
                $item['usable_quantity'] = $res['usable_quantity'];
                $item['valuation'] = $res['valuation'];
                $item['real_quantity'] = $manager->getProductRealQuantities(
                    $item['id_product'],
                    $item['id_product_attribute'],
                    ($this->getCurrentCoverageWarehouse() == -1 ? null : [$this->getCurrentCoverageWarehouse()]),
                    true
                );
            }
        } else {
            if ((Tools::isSubmit('csv_quantities') || Tools::isSubmit('csv_prices')) &&
                (int) Tools::getValue('id_warehouse') != -1
            ) {
                $limit = false;
            }

            $orderByValuation = false;
            $orderByRealQuantity = false;

            if ($this->context->cookie->{$this->table.'Orderby'} == 'valuation') {
                unset($this->context->cookie->{$this->table.'Orderby'});
                $orderByValuation = true;
            } elseif ($this->context->cookie->{$this->table.'Orderby'} == 'real_quantity') {
                unset($this->context->cookie->{$this->table.'Orderby'});
                $orderByRealQuantity = true;
            }

            parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

            $nbItems = count($this->_list);

            for ($i = 0; $i < $nbItems; ++$i) {
                $item = &$this->_list[$i];

                $item['price_te'] = '--';
                $item[$this->identifier] = $item['id_product'].'_'.$item['id_product_attribute'];

                // gets stock manager
                $manager = StockManagerFactory::getManager();

                // gets quantities and valuation
                $query = new DbQuery();
                $query->select('SUM(physical_quantity) as physical_quantity');
                $query->select('SUM(usable_quantity) as usable_quantity');
                $query->select('SUM(price_te * physical_quantity) as valuation');
                $query->from('stock');
                $query->where('id_product = '.(int) $item['id_product'].' AND id_product_attribute = '.(int) $item['id_product_attribute']);

                if ($this->getCurrentCoverageWarehouse() != -1) {
                    $query->where('id_warehouse = '.(int) $this->getCurrentCoverageWarehouse());
                }

                $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);

                $item['physical_quantity'] = $res['physical_quantity'];
                $item['usable_quantity'] = $res['usable_quantity'];

                // gets real_quantity depending on the warehouse
                $item['real_quantity'] = $manager->getProductRealQuantities(
                    $item['id_product'],
                    $item['id_product_attribute'],
                    ($this->getCurrentCoverageWarehouse() == -1 ? null : [$this->getCurrentCoverageWarehouse()]),
                    true
                );

                // removes the valuation if the filter corresponds to 'all warehouses'
                if ($this->getCurrentCoverageWarehouse() == -1) {
                    $item['valuation'] = 'N/A';
                } else {
                    $item['valuation'] = $res['valuation'];
                }
            }

            if ($this->getCurrentCoverageWarehouse() != -1 && $orderByValuation) {
                usort($this->_list, [$this, 'valuationCmp']);
            } elseif ($orderByRealQuantity) {
                usort($this->_list, [$this, 'realQuantityCmp']);
            }
        }
    }

    /**
     * CMP
     *
     * @param array $n
     * @param array $m
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function valuationCmp($n, $m)
    {
        if ($this->context->cookie->{$this->table.'Orderway'} == 'desc') {
            return $n['valuation'] > $m['valuation'];
        } else {
            return $n['valuation'] < $m['valuation'];
        }
    }

    /**
     * CMP
     *
     * @param array $n
     * @param array $m
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function realQuantityCmp($n, $m)
    {
        if ($this->context->cookie->{$this->table.'Orderway'} == 'desc') {
            return $n['real_quantity'] > $m['real_quantity'];
        } else {
            return $n['real_quantity'] < $m['real_quantity'];
        }
    }

    /**
     * Initialize content
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            $this->warnings[md5('PS_ADVANCED_STOCK_MANAGEMENT')] = $this->l('You need to activate advanced stock management before using this feature.');

            return false;
        }

        parent::initContent();

        return true;
    }

    /**
     * Initialize processing
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function initProcess()
    {
        if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            $this->warnings[md5('PS_ADVANCED_STOCK_MANAGEMENT')] = $this->l('You need to activate advanced stock management before using this feature.');

            return false;
        }

        if (Tools::isSubmit('detailsproduct')) {
            $this->list_id = 'details';
        } else {
            $this->list_id = 'stock';
        }

        parent::initProcess();

        return true;
    }
}
