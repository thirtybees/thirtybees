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
 * Class SupplyOrderCore
 *
 * @since 1.0.0
 */
class SupplyOrderCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @var int Supplier
     */
    public $id_supplier;

    /**
     * @var string Supplier Name
     */
    public $supplier_name;

    /**
     * @var int The language id used on the delivery note
     */
    public $id_lang;

    /**
     * @var int Warehouse where products will be delivered
     */
    public $id_warehouse;

    /**
     * @var int Current state of the order
     */
    public $id_supply_order_state;

    /**
     * @var int Currency used for the order
     */
    public $id_currency;

    /**
     * @var int Currency used by default in main global configuration (i.e. by default for all shops)
     */
    public $id_ref_currency;

    /**
     * @var string Reference of the order
     */
    public $reference;

    /**
     * @var string Date when added
     */
    public $date_add;

    /**
     * @var string Date when updated
     */
    public $date_upd;

    /**
     * @var string Expected delivery date
     */
    public $date_delivery_expected;

    /**
     * @var float Total price without tax
     */
    public $total_te = 0;

    /**
     * @var float Total price after discount, without tax
     */
    public $total_with_discount_te = 0;

    /**
     * @var float Total price with tax
     */
    public $total_ti = 0;

    /**
     * @var float Total tax value
     */
    public $total_tax = 0;

    /**
     * @var float Supplier discount rate (for the whole order)
     */
    public $discount_rate = 0;

    /**
     * @var float Supplier discount value without tax (for the whole order)
     */
    public $discount_value_te = 0;

    /**
     * @var int Tells if this order is a template
     */
    public $is_template = 0;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'supply_order',
        'primary' => 'id_supply_order',
        'fields'  => [
            'id_supplier'            => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'supplier_name'          => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'required' => false],
            'id_lang'                => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'id_warehouse'           => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'id_supply_order_state'  => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'id_currency'            => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'id_ref_currency'        => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'reference'              => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'date_delivery_expected' => ['type' => self::TYPE_DATE,   'validate' => 'isDate',        'required' => true],
            'total_te'               => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'total_with_discount_te' => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'total_ti'               => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'total_tax'              => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'discount_rate'          => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat',      'required' => false],
            'discount_value_te'      => ['type' => self::TYPE_PRICE,  'validate' => 'isPrice'                          ],
            'is_template'            => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                           ],
            'date_add'               => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                           ],
            'date_upd'               => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                           ],
        ],
    ];

    /**
     * @see ObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'fields'        => [
            'id_supplier'           => ['xlink_resource' => 'suppliers'],
            'id_lang'               => ['xlink_resource' => 'languages'],
            'id_warehouse'          => ['xlink_resource' => 'warehouses'],
            'id_supply_order_state' => ['xlink_resource' => 'supply_order_states'],
            'id_currency'           => ['xlink_resource' => 'currencies'],
        ],
        'hidden_fields' => [
            'id_ref_currency',
        ],
        'associations'  => [
            'supply_order_details' => [
                'resource' => 'supply_order_detail',
                'fields'   => [
                    'id'                   => [],
                    'id_product'           => [],
                    'id_product_attribute' => [],
                    'supplier_reference'   => [],
                    'product_name'         => [],
                ],
            ],
        ],
    ];

    /**
     * @see     ObjectModel::update()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        $this->calculatePrices();

        $res = parent::update($nullValues);

        if ($res && !$this->is_template) {
            $this->addHistory();
        }

        return $res;
    }

    /**
     * @see     ObjectModel::add()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        $this->calculatePrices();

        $res = parent::add($autoDate, $nullValues);

        if ($res && !$this->is_template) {
            $this->addHistory();
        }

        return $res;
    }

    /**
     * Checks all products in this order and calculate prices
     * Applies the global discount if necessary
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function calculatePrices()
    {
        $this->total_te = 0;
        $this->total_with_discount_te = 0;
        $this->total_tax = 0;
        $this->total_ti = 0;
        $isDiscount = false;

        if (is_numeric($this->discount_rate) && $this->discount_rate >= 0) {
            $isDiscount = true;
        }

        // gets all product entries in this order
        $entries = $this->getEntriesCollection();

        foreach ($entries as $entry) {
            /** @var SupplyOrderDetail $entry */
            // applys global discount rate on each product if possible
            if ($isDiscount) {
                $entry->applyGlobalDiscount((float) $this->discount_rate);
            }

            // adds new prices to the total
            $this->total_te += $entry->price_with_discount_te;
            $this->total_with_discount_te += $entry->price_with_order_discount_te;
            $this->total_tax += $entry->tax_value_with_order_discount;
            $this->total_ti = $this->total_tax + $this->total_with_discount_te;
        }

        // applies global discount rate if possible
        if ($isDiscount) {
            $this->discount_value_te = $this->total_te - $this->total_with_discount_te;
        }
    }

    /**
     * Retrieves the product entries for the current order
     *
     * @param int $idLang Optional Id Lang - Uses Context::language::id by default
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getEntries($idLang = null)
    {
        if ($idLang == null) {
            $idLang = Context::getContext()->language->id;
        }

        // build query
        $query = new DbQuery();

        $query->select(
            '
			s.*,
			IFNULL(CONCAT(pl.name, \' : \', GROUP_CONCAT(agl.name, \' - \', al.name SEPARATOR \', \')), pl.name) as name_displayed'
        );

        $query->from('supply_order_detail', 's');

        $query->innerjoin('product_lang', 'pl', 'pl.id_product = s.id_product AND pl.id_lang = '.$idLang);

        $query->leftjoin('product', 'p', 'p.id_product = s.id_product');
        $query->leftjoin('product_attribute_combination', 'pac', 'pac.id_product_attribute = s.id_product_attribute');
        $query->leftjoin('attribute', 'atr', 'atr.id_attribute = pac.id_attribute');
        $query->leftjoin('attribute_lang', 'al', 'al.id_attribute = atr.id_attribute AND al.id_lang = '.$idLang);
        $query->leftjoin('attribute_group_lang', 'agl', 'agl.id_attribute_group = atr.id_attribute_group AND agl.id_lang = '.$idLang);

        $query->where('s.id_supply_order = '.(int) $this->id);

        $query->groupBy('s.id_supply_order_detail');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * Retrieves the details entries (i.e. products) collection for the current order
     *
     * @return PrestaShopCollection Collection of SupplyOrderDetail
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getEntriesCollection()
    {
        $details = new PrestaShopCollection('SupplyOrderDetail');
        $details->where('id_supply_order', '=', $this->id);

        return $details;
    }

    /**
     * Check if the order has entries
     *
     * @return bool Has/Has not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function hasEntries()
    {
        $query = new DbQuery();
        $query->select('COUNT(*)');
        $query->from('supply_order_detail', 's');
        $query->where('s.id_supply_order = '.(int) $this->id);

        return (Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query) > 0);
    }

    /**
     * Check if the current state allows to edit the current order
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function isEditable()
    {
        $query = new DbQuery();
        $query->select('s.editable');
        $query->from('supply_order_state', 's');
        $query->where('s.id_supply_order_state = '.(int) $this->id_supply_order_state);

        return (Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query) == 1);
    }

    /**
     * Checks if the current state allows to generate a delivery note for this order
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function isDeliveryNoteAvailable()
    {
        $query = new DbQuery();
        $query->select('s.delivery_note');
        $query->from('supply_order_state', 's');
        $query->where('s.id_supply_order_state = '.(int) $this->id_supply_order_state);

        return (Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query) == 1);
    }

    /**
     * Checks if the current state allows to add products in stock
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function isInReceiptState()
    {
        $query = new DbQuery();
        $query->select('s.receipt_state');
        $query->from('supply_order_state', 's');
        $query->where('s.id_supply_order_state = '.(int) $this->id_supply_order_state);

        return (Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query) == 1);
    }

    /**
     * Historizes the order : its id, its state, and the employee responsible for the current action
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function addHistory()
    {
        $context = Context::getContext();
        $history = new SupplyOrderHistory();
        $history->id_supply_order = $this->id;
        $history->id_state = $this->id_supply_order_state;
        $history->id_employee = (int) $context->employee->id;
        $history->employee_firstname = pSQL($context->employee->firstname);
        $history->employee_lastname = pSQL($context->employee->lastname);

        $history->save();
    }

    /**
     * Removes all products from the order
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function resetProducts()
    {
        $products = $this->getEntriesCollection();

        foreach ($products as $p) {
            $p->delete();
        }
    }

    /**
     * For a given $id_warehouse, tells if it has pending supply orders
     *
     * @param int $idWarehouse
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function warehouseHasPendingOrders($idWarehouse)
    {
        if (!$idWarehouse) {
            return false;
        }

        $query = new DbQuery();
        $query->select('COUNT(so.id_supply_order) as supply_orders');
        $query->from('supply_order', 'so');
        $query->leftJoin('supply_order_state', 'sos', 'so.id_supply_order_state = sos.id_supply_order_state');
        $query->where('sos.enclosed != 1');
        $query->where('so.id_warehouse = '.(int) $idWarehouse);

        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        return ($res > 0);
    }

    /**
     * For a given $id_supplier, tells if it has pending supply orders
     *
     * @param int $idSupplier Id Supplier
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function supplierHasPendingOrders($idSupplier)
    {
        if (!$idSupplier) {
            return false;
        }

        $query = new DbQuery();
        $query->select('COUNT(so.id_supply_order) as supply_orders');
        $query->from('supply_order', 'so');
        $query->leftJoin('supply_order_state', 'sos', 'so.id_supply_order_state = sos.id_supply_order_state');
        $query->where('sos.enclosed != 1');
        $query->where('so.id_supplier = '.(int) $idSupplier);

        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        return ($res > 0);
    }

    /**
     * For a given id or reference, tells if the supply order exists
     *
     * @param int|string $match Either the reference of the order, or the Id of the order
     *
     * @return int SupplyOrder Id
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function exists($match)
    {
        if (!$match) {
            return false;
        }

        $query = new DbQuery();
        $query->select('id_supply_order');
        $query->from('supply_order', 'so');
        $query->where('so.id_supply_order = '.(int) $match.' OR so.reference = "'.pSQL($match).'"');

        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        return ((int) $res);
    }

    /**
     * For a given reference, returns the corresponding supply order
     *
     * @param string $reference Reference of the order
     *
     * @return bool|SupplyOrder
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getSupplyOrderByReference($reference)
    {
        if (!$reference) {
            return false;
        }

        $query = new DbQuery();
        $query->select('id_supply_order');
        $query->from('supply_order', 'so');
        $query->where('so.reference = "'.pSQL($reference).'"');
        $idSupplyOrder = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        if (!$idSupplyOrder) {
            return false;
        }

        return new SupplyOrder($idSupplyOrder);;
    }

    /**
     * @see ObjectModel::hydrate()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function hydrate(array $data, $idLang = null)
    {
        $this->id_lang = $idLang;
        if (isset($data[$this->def['primary']])) {
            $this->id = $data[$this->def['primary']];
        }
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this)) {
                // formats prices and floats
                if ($this->def['fields'][$key]['validate'] == 'isPrice') {
                    $value = round($value, _TB_PRICE_DATABASE_PRECISION_);
                }
                $this->$key = $value;
            }
        }
    }

    /**
     * Gets the reference of a given order
     *
     * @param int $idSupplyOrder
     *
     * @return bool|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getReferenceById($idSupplyOrder)
    {
        if (!$idSupplyOrder) {
            return false;
        }

        $query = new DbQuery();
        $query->select('so.reference');
        $query->from('supply_order', 'so');
        $query->where('so.id_supply_order = '.(int) $idSupplyOrder);
        $ref = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        return (pSQL($ref));
    }

    /**
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getAllExpectedQuantity()
    {
        return Db::getInstance()->getValue(
            '
			SELECT SUM(`quantity_expected`)
			FROM `'._DB_PREFIX_.'supply_order_detail`
			WHERE `id_supply_order` = '.(int) $this->id
        );
    }

    /**
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getAllReceivedQuantity()
    {
        return Db::getInstance()->getValue(
            '
			SELECT SUM(`quantity_received`)
			FROM `'._DB_PREFIX_.'supply_order_detail`
			WHERE `id_supply_order` = '.(int) $this->id
        );
    }

    /**
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getAllPendingQuantity()
    {
        return Db::getInstance()->getValue(
            '
			SELECT (SUM(`quantity_expected`) - SUM(`quantity_received`))
			FROM `'._DB_PREFIX_.'supply_order_detail`
			WHERE `id_supply_order` = '.(int) $this->id
        );
    }

    /*********************************\
     *
     * Webservices Specific Methods
     *
     *********************************/

    /**
     * Webservice : gets the ids supply_order_detail associated to this order
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsSupplyOrderDetails()
    {
        $query = new DbQuery();
        $query->select(
            'sod.id_supply_order_detail as id, sod.id_product,
						sod.id_product_attribute,
					    sod.name as product_name, supplier_reference'
        );
        $query->from('supply_order_detail', 'sod');
        $query->where('id_supply_order = '.(int) $this->id);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }
}
