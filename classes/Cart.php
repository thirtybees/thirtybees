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
 * Class CartCore
 *
 * @since 1.0.0
 */
class CartCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    const ONLY_PRODUCTS = 1;
    const ONLY_DISCOUNTS = 2;
    const BOTH = 3;
    const BOTH_WITHOUT_SHIPPING = 4;
    const ONLY_SHIPPING = 5;
    const ONLY_WRAPPING = 6;
    const ONLY_PRODUCTS_WITHOUT_SHIPPING = 7;
    const ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING = 8;
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'cart',
        'primary' => 'id_cart',
        'fields'  => [
            'id_shop_group'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_shop'                 => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_address_delivery'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_address_invoice'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_carrier'              => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_currency'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_customer'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_guest'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_lang'                 => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'recyclable'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'gift'                    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'gift_message'            => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
            'mobile_theme'            => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'delivery_option'         => ['type' => self::TYPE_STRING],
            'secure_key'              => ['type' => self::TYPE_STRING, 'size' => 32],
            'allow_seperated_package' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
    /** @var array $_nbProducts */
    protected static $_nbProducts = [];
    /** @var array $_isVirtualCart */
    protected static $_isVirtualCart = [];
    /** @var array $_totalWeight */
    protected static $_totalWeight = [];
    protected static $_carriers = null;
    protected static $_taxes_rate = null;
    protected static $_attributesLists = [];
    /** @var Customer|null */
    protected static $_customer = null;
    public $id_shop_group;
    public $id_shop;
    /** @var int Customer delivery address ID */
    public $id_address_delivery;
    /** @var int Customer invoicing address ID */
    public $id_address_invoice;
    /** @var int Customer currency ID */
    public $id_currency;
    /** @var int Customer ID */
    public $id_customer;
    /** @var int Guest ID */
    public $id_guest;
    /** @var int Language ID */
    public $id_lang;
    /** @var bool True if the customer wants a recycled package */
    public $recyclable = 0;
    /** @var bool True if the customer wants a gift wrapping */
    public $gift = 0;
    /** @var string Gift message if specified */
    public $gift_message;
    /** @var bool Mobile Theme */
    public $mobile_theme;
    /** @var string Object creation date */
    public $date_add;
    /** @var string secure_key */
    public $secure_key;
    /** @var int Carrier ID */
    public $id_carrier = 0;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var bool $checkedTos */
    public $checkedTos = false;
    public $pictures;
    public $textFields;
    public $delivery_option;
    /** @var bool Allow to seperate order in multiple package in order to recieve as soon as possible the available products */
    public $allow_seperated_package = false;
    protected $_products = null;
    protected $_taxCalculationMethod = PS_TAX_EXC;
    protected $webserviceParameters = [
        'fields'       => [
            'id_address_delivery' => ['xlink_resource' => 'addresses'],
            'id_address_invoice'  => ['xlink_resource' => 'addresses'],
            'id_currency'         => ['xlink_resource' => 'currencies'],
            'id_customer'         => ['xlink_resource' => 'customers'],
            'id_guest'            => ['xlink_resource' => 'guests'],
            'id_lang'             => ['xlink_resource' => 'languages'],
        ],
        'associations' => [
            'cart_rows' => [
                'resource' => 'cart_row', 'virtual_entity' => true, 'fields' => [
                    'id_product'           => ['required' => true, 'xlink_resource' => 'products'],
                    'id_product_attribute' => ['required' => true, 'xlink_resource' => 'combinations'],
                    'id_address_delivery'  => ['required' => true, 'xlink_resource' => 'addresses'],
                    'quantity'             => ['required' => true],
                ],
            ],
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * CartCore constructor.
     *
     * @param null $id
     * @param null $idLang
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id);

        if (!is_null($idLang)) {
            $this->id_lang = (int) (Language::getLanguage($idLang) !== false) ? $idLang : Configuration::get('PS_LANG_DEFAULT');
        }

        if ($this->id_customer) {
            if (isset(Context::getContext()->customer) && Context::getContext()->customer->id == $this->id_customer) {
                $customer = Context::getContext()->customer;
            } else {
                $customer = new Customer((int) $this->id_customer);
            }

            static::$_customer = $customer;

            if ((!$this->secure_key || $this->secure_key == '-1') && $customer->secure_key) {
                $this->secure_key = $customer->secure_key;
                $this->save();
            }
        }

        $this->setTaxCalculationMethod();
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function setTaxCalculationMethod()
    {
        $this->_taxCalculationMethod = Group::getPriceDisplayMethod(Group::getCurrent()->id);
    }

    /**
     * Get the average tax used in the Cart
     *
     * @param int $idCart
     *
     * @return float|int
     * @throws PrestaShopException
     */
    public static function getTaxesAverageUsed($idCart)
    {
        $cart = new Cart((int) $idCart);
        if (!Validate::isLoadedObject($cart)) {
            die(Tools::displayError());
        }

        if (!Configuration::get('PS_TAX')) {
            return 0;
        }

        $products = $cart->getProducts();
        $totalProductsMoy = 0;
        $ratioTax = 0;

        if (!count($products)) {
            return 0;
        }

        foreach ($products as $product) {
            // products refer to the cart details

            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
                $addressId = (int) $cart->id_address_invoice;
            } else {
                $addressId = (int) $product['id_address_delivery'];
            } // Get delivery address of the product from the cart
            if (!Address::addressExists($addressId)) {
                $addressId = null;
            }

            $totalProductsMoy += $product['total_wt'];
            $ratioTax += $product['total_wt'] * Tax::getProductTaxRate((int) $product['id_product'], (int) $addressId);
        }

        if ($totalProductsMoy > 0) {
            return $ratioTax / $totalProductsMoy;
        }

        return 0;
    }

    /**
     * Return cart products
     *
     * @param bool $refresh
     * @param bool $idProduct
     * @param null $idCountry
     *
     * @return array|null
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function getProducts($refresh = false, $idProduct = false, $idCountry = null)
    {
        if (!$this->id) {
            return [];
        }
        // Product cache must be strictly compared to NULL, or else an empty cart will add dozens of queries
        if ($this->_products !== null && !$refresh) {
            // Return product row with specified ID if it exists
            if (is_int($idProduct)) {
                foreach ($this->_products as $product) {
                    if ($product['id_product'] == $idProduct) {
                        return [$product];
                    }
                }

                return [];
            }

            return $this->_products;
        }

        // Build query
        $sql = new DbQuery();

        // Build SELECT
        $sql->select('cp.`id_product_attribute`');
        $sql->select('cp.`id_product`');
        $sql->select('cp.`quantity` AS `cart_quantity`');
        $sql->select('cp.`id_shop`');
        $sql->select('pl.`name`');
        $sql->select('p.`is_virtual`');
        $sql->select('pl.`description_short`');
        $sql->select('pl.`available_now`');
        $sql->select('pl.`available_later`');
        $sql->select('product_shop.`id_category_default`');
        $sql->select('p.`id_supplier`');
        $sql->select('p.`id_manufacturer`');
        $sql->select('product_shop.`on_sale`');
        $sql->select('product_shop.`ecotax`');
        $sql->select('product_shop.`additional_shipping_cost`');
        $sql->select('product_shop.`available_for_order`');
        $sql->select('product_shop.`price`');
        $sql->select('product_shop.`active`');
        $sql->select('product_shop.`unity`');
        $sql->select('product_shop.`unit_price_ratio`');
        $sql->select('stock.`quantity` AS `quantity_available`');
        $sql->select('p.`width`');
        $sql->select('p.`height`');
        $sql->select('p.`depth`');
        $sql->select('p.`weight`');
        $sql->select('stock.`out_of_stock`');
        $sql->select('p.`date_add`');
        $sql->select('p.`date_upd`');
        $sql->select('IFNULL(stock.`quantity`, 0) AS `quantity`');
        $sql->select('pl.`link_rewrite`');
        $sql->select('cl.`link_rewrite` AS `category`');
        $sql->select('CONCAT(LPAD(cp.`id_product`, 10, 0), LPAD(IFNULL(cp.`id_product_attribute`, 0), 10, 0), IFNULL(cp.`id_address_delivery`, 0)) AS unique_id');
        $sql->select('cp.`id_address_delivery`');
        $sql->select('product_shop.`advanced_stock_management`');
        $sql->select('ps.`product_supplier_reference` AS `supplier_reference`');

        // Build FROM
        $sql->from('cart_product', 'cp');

        // Build JOIN
        $sql->leftJoin('product', 'p', 'p.`id_product` = cp.`id_product`');
        $sql->innerJoin('product_shop', 'product_shop', '(product_shop.`id_shop` = cp.`id_shop` AND product_shop.`id_product` = p.`id_product`)');
        $sql->leftJoin(
            'product_lang',
            'pl',
            'p.`id_product` = pl.`id_product` AND pl.`id_lang` = '.(int) $this->id_lang.Shop::addSqlRestrictionOnLang('pl', 'cp.id_shop')
        );

        $sql->leftJoin(
            'category_lang',
            'cl',
            'product_shop.`id_category_default` = cl.`id_category` AND cl.`id_lang` = '.(int) $this->id_lang.Shop::addSqlRestrictionOnLang('cl', 'cp.id_shop')
        );

        $sql->leftJoin('product_supplier', 'ps', 'ps.`id_product` = cp.`id_product` AND ps.`id_product_attribute` = cp.`id_product_attribute` AND ps.`id_supplier` = p.`id_supplier`');

        // @todo test if everything is ok, then refactorise call of this method
        $sql->join(Product::sqlStock('cp', 'cp'));

        // Build WHERE clauses
        $sql->where('cp.`id_cart` = '.(int) $this->id);
        if ($idProduct) {
            $sql->where('cp.`id_product` = '.(int) $idProduct);
        }
        $sql->where('p.`id_product` IS NOT NULL');

        // Build ORDER BY
        $sql->orderBy('cp.`date_add`, cp.`id_product`, cp.`id_product_attribute` ASC');

        if (Customization::isFeatureActive()) {
            $sql->select('cu.`id_customization`, cu.`quantity` AS customization_quantity');
            $sql->leftJoin(
                'customization',
                'cu',
                'p.`id_product` = cu.`id_product` AND cp.`id_product_attribute` = cu.`id_product_attribute` AND cu.`id_cart` = '.(int) $this->id
            );
            $sql->groupBy('cp.`id_product_attribute`, cp.`id_product`, cp.`id_shop`');
        } else {
            $sql->select('NULL AS customization_quantity, NULL AS id_customization');
        }

        if (Combination::isFeatureActive()) {
            $sql->select('product_attribute_shop.`price` AS price_attribute, product_attribute_shop.`ecotax` AS ecotax_attr');
            $sql->select('IF (IFNULL(pa.`reference`, \'\') = \'\', p.`reference`, pa.`reference`) AS reference');
            $sql->select('(p.`weight`+ pa.`weight`) weight_attribute');
            $sql->select('IF (IFNULL(pa.`ean13`, \'\') = \'\', p.`ean13`, pa.`ean13`) AS ean13');
            $sql->select('IF (IFNULL(pa.`upc`, \'\') = \'\', p.`upc`, pa.`upc`) AS upc');
            $sql->select('IFNULL(product_attribute_shop.`minimal_quantity`, product_shop.`minimal_quantity`) as minimal_quantity');
            $sql->select('IF(product_attribute_shop.wholesale_price > 0,  product_attribute_shop.wholesale_price, product_shop.`wholesale_price`) wholesale_price');
            $sql->leftJoin('product_attribute', 'pa', 'pa.`id_product_attribute` = cp.`id_product_attribute`');
            $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', '(product_attribute_shop.`id_shop` = cp.`id_shop` AND product_attribute_shop.`id_product_attribute` = pa.`id_product_attribute`)');
        } else {
            $sql->select('p.`reference` AS `reference`');
            $sql->select('p.`ean13`');
            $sql->select('p.`upc` AS `upc`');
            $sql->select('product_shop.`minimal_quantity` AS `minimal_quantity`');
            $sql->select('product_shop.`wholesale_price` AS `wholesale_price`');
        }

        $sql->select('image_shop.`id_image` id_image, il.`legend`');
        $sql->leftJoin('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $this->id_shop);
        $sql->leftJoin('image_lang', 'il', 'il.`id_image` = image_shop.`id_image` AND il.`id_lang` = '.(int) $this->id_lang);

        $result = Db::getInstance()->executeS($sql);

        // Reset the cache before the following return, or else an empty cart will add dozens of queries
        $productsIds = [];
        $paIds = [];
        if ($result) {
            foreach ($result as $key => $row) {
                $productsIds[] = $row['id_product'];
                $paIds[] = $row['id_product_attribute'];
                $specificPrice = SpecificPrice::getSpecificPrice($row['id_product'], $this->id_shop, $this->id_currency, $idCountry, $this->id_shop_group, $row['cart_quantity'], $row['id_product_attribute'], $this->id_customer, $this->id);
                if ($specificPrice) {
                    $reductionTypeRow = ['reduction_type' => $specificPrice['reduction_type']];
                } else {
                    $reductionTypeRow = ['reduction_type' => 0];
                }

                $result[$key] = array_merge($row, $reductionTypeRow);
            }
        }
        // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
        Product::cacheProductsFeatures($productsIds);
        static::cacheSomeAttributesLists($paIds, $this->id_lang);

        $this->_products = [];
        if (empty($result)) {
            return [];
        }

        $ecotaxRate = (float) Tax::getProductEcotaxRate($this->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $applyEcoTax = Product::$_taxCalculationMethod == PS_TAX_INC && (int) Configuration::get('PS_TAX');
        $cartShopContext = Context::getContext()->cloneContext();

        foreach ($result as &$row) {
            if (isset($row['ecotax_attr']) && $row['ecotax_attr'] > 0) {
                $row['ecotax'] = (float) $row['ecotax_attr'];
            }

            $row['stock_quantity'] = (int) $row['quantity'];
            // for compatibility with 1.2 themes
            $row['quantity'] = (int) $row['cart_quantity'];

            if (isset($row['id_product_attribute']) && (int) $row['id_product_attribute'] && isset($row['weight_attribute'])) {
                $row['weight'] = (float) $row['weight_attribute'];
            }

            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
                $addressId = (int) $this->id_address_invoice;
            } else {
                $addressId = (int) $row['id_address_delivery'];
            }
            if (!Address::addressExists($addressId)) {
                $addressId = null;
            }

            if ($cartShopContext->shop->id != $row['id_shop']) {
                $cartShopContext->shop = new Shop((int) $row['id_shop']);
            }

            $address = Address::initialize($addressId, true);
            $idTaxRulesGroup = Product::getIdTaxRulesGroupByIdProduct((int) $row['id_product'], $cartShopContext);
            $taxCalculator = TaxManagerFactory::getManager($address, $idTaxRulesGroup)->getTaxCalculator();

            $row['price_without_reduction'] = Product::getPriceStatic(
                (int) $row['id_product'],
                true,
                isset($row['id_product_attribute']) ? (int) $row['id_product_attribute'] : null,
                6,
                null,
                false,
                false,
                $row['cart_quantity'],
                false,
                (int) $this->id_customer ? (int) $this->id_customer : null,
                (int) $this->id,
                $addressId,
                $specificPriceOutput,
                true,
                true,
                $cartShopContext
            );

            $row['price_with_reduction'] = Product::getPriceStatic(
                (int) $row['id_product'],
                true,
                isset($row['id_product_attribute']) ? (int) $row['id_product_attribute'] : null,
                6,
                null,
                false,
                true,
                $row['cart_quantity'],
                false,
                (int) $this->id_customer ? (int) $this->id_customer : null,
                (int) $this->id,
                $addressId,
                $specificPriceOutput,
                true,
                true,
                $cartShopContext
            );

            $row['price'] = $row['price_with_reduction_without_tax'] = Product::getPriceStatic(
                (int) $row['id_product'],
                false,
                isset($row['id_product_attribute']) ? (int) $row['id_product_attribute'] : null,
                6,
                null,
                false,
                true,
                $row['cart_quantity'],
                false,
                (int) $this->id_customer ? (int) $this->id_customer : null,
                (int) $this->id,
                $addressId,
                $specificPriceOutput,
                true,
                true,
                $cartShopContext
            );

            switch (Configuration::get('PS_ROUND_TYPE')) {
                case Order::ROUND_TOTAL:
                    $row['total'] = $row['price_with_reduction_without_tax'] * (int) $row['cart_quantity'];
                    $row['total_wt'] = $row['price_with_reduction'] * (int) $row['cart_quantity'];
                    break;
                case Order::ROUND_LINE:
                    $row['total'] = Tools::ps_round($row['price_with_reduction_without_tax'] * (int) $row['cart_quantity'], _TB_PRICE_DATABASE_PRECISION_);
                    $row['total_wt'] = Tools::ps_round($row['price_with_reduction'] * (int) $row['cart_quantity'], _TB_PRICE_DATABASE_PRECISION_);
                    break;

                case Order::ROUND_ITEM:
                default:
                    $row['total'] = Tools::ps_round($row['price_with_reduction_without_tax'], _TB_PRICE_DATABASE_PRECISION_) * (int) $row['cart_quantity'];
                    $row['total_wt'] = Tools::ps_round($row['price_with_reduction'], _TB_PRICE_DATABASE_PRECISION_) * (int) $row['cart_quantity'];
                    break;
            }

            $row['price_wt'] = $row['price_with_reduction'];
            $row['description_short'] = Tools::nl2br($row['description_short']);

            // check if a image associated with the attribute exists
            if ($row['id_product_attribute']) {
                $row2 = Image::getBestImageAttribute($row['id_shop'], $this->id_lang, $row['id_product'], $row['id_product_attribute']);
                if ($row2) {
                    $row = array_merge($row, $row2);
                }
            }

            $row['reduction_applies'] = ($specificPriceOutput && (float) $specificPriceOutput['reduction']);
            $row['quantity_discount_applies'] = ($specificPriceOutput && $row['cart_quantity'] >= (int) $specificPriceOutput['from_quantity']);
            $row['id_image'] = Product::defineProductImage($row, $this->id_lang);
            $row['allow_oosp'] = Product::isAvailableWhenOutOfStock($row['out_of_stock']);
            $row['features'] = Product::getFeaturesStatic((int) $row['id_product']);

            if (array_key_exists($row['id_product_attribute'].'-'.$this->id_lang, static::$_attributesLists)) {
                $row = array_merge($row, static::$_attributesLists[$row['id_product_attribute'].'-'.$this->id_lang]);
            }

            $row = Product::getTaxesInformations($row, $cartShopContext);

            $this->_products[] = $row;
        }

        return $this->_products;
    }

    /**
     * @param array $ipaList
     * @param int   $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cacheSomeAttributesLists($ipaList, $idLang)
    {
        if (!Combination::isFeatureActive()) {
            return;
        }

        $paImplode = [];

        foreach ($ipaList as $idProductAttribute) {
            if ((int) $idProductAttribute && !array_key_exists($idProductAttribute.'-'.$idLang, static::$_attributesLists)) {
                $paImplode[] = (int) $idProductAttribute;
                static::$_attributesLists[(int) $idProductAttribute.'-'.$idLang] = ['attributes' => '', 'attributes_small' => ''];
            }
        }

        if (!count($paImplode)) {
            return;
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('pac.`id_product_attribute`, agl.`public_name` AS `public_group_name`, al.`name` AS `attribute_name`')
                ->from('product_attribute_combination', 'pac')
                ->leftJoin('attribute', 'a', 'a.`id_attribute` = pac.`id_attribute`')
                ->leftJoin('attribute_group', 'ag', 'ag.`id_attribute_group` = a.`id_attribute_group`')
                ->leftJoin('attribute_lang', 'al', 'a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) $idLang)
                ->leftJoin('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) $idLang)
                ->where('pac.`id_product_attribute` IN ('.implode(',', $paImplode).')')
                ->orderBy('ag.`position` ASC, a.`position` ASC')
        );

        foreach ($result as $row) {
            static::$_attributesLists[$row['id_product_attribute'].'-'.$idLang]['attributes'] .= $row['public_group_name'].' : '.$row['attribute_name'].', ';
            static::$_attributesLists[$row['id_product_attribute'].'-'.$idLang]['attributes_small'] .= $row['attribute_name'].', ';
        }

        foreach ($paImplode as $idProductAttribute) {
            static::$_attributesLists[$idProductAttribute.'-'.$idLang]['attributes'] = rtrim(
                static::$_attributesLists[$idProductAttribute.'-'.$idLang]['attributes'],
                ', '
            );

            static::$_attributesLists[$idProductAttribute.'-'.$idLang]['attributes_small'] = rtrim(
                static::$_attributesLists[$idProductAttribute.'-'.$idLang]['attributes_small'],
                ', '
            );
        }
    }

    /**
     * @param int $idCart
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getOrderTotalUsingTaxCalculationMethod($idCart)
    {
        return static::getTotalCart($idCart, true);
    }

    /**
     * @param int  $idCart
     * @param bool $useTaxDisplay
     * @param int  $type
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTotalCart($idCart, $useTaxDisplay = false, $type = self::BOTH)
    {
        $cart = new Cart($idCart);
        if (!Validate::isLoadedObject($cart)) {
            die(Tools::displayError());
        }

        $withTaxes = $useTaxDisplay ? $cart->_taxCalculationMethod != PS_TAX_EXC : true;

        return Tools::displayPrice($cart->getOrderTotal($withTaxes, $type), Currency::getCurrencyInstance((int) $cart->id_currency), false);
    }

    /**
     * This function returns the total cart amount
     *
     * Possible values for $type:
     * static::ONLY_PRODUCTS
     * static::ONLY_DISCOUNTS
     * static::BOTH
     * static::BOTH_WITHOUT_SHIPPING
     * static::ONLY_SHIPPING
     * static::ONLY_WRAPPING
     * static::ONLY_PRODUCTS_WITHOUT_SHIPPING
     * static::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING
     *
     * @param bool       $withTaxes With or without taxes
     * @param int        $type      Total type
     * @param array|null $products
     * @param int|null   $idCarrier
     * @param bool       $useCache  Allow using cache of the method CartRule::getContextualValue
     *
     * @return float Order total
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getOrderTotal($withTaxes = true, $type = self::BOTH, $products = null, $idCarrier = null, $useCache = true)
    {
        // Dependencies
        /** @var Adapter_AddressFactory $addressFactory */
        $addressFactory = Adapter_ServiceLocator::get('Adapter_AddressFactory');
        /** @var Adapter_ProductPriceCalculator $priceCalculator */
        $priceCalculator = Adapter_ServiceLocator::get('Adapter_ProductPriceCalculator');
        /** @var Core_Business_ConfigurationInterface $configuration */
        $configuration = Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');

        $psTaxAddressType = $configuration->get('PS_TAX_ADDRESS_TYPE');
        $psUseEcotax = $configuration->get('PS_USE_ECOTAX');
        $psRoundType = $configuration->get('PS_ROUND_TYPE');
        $displayPrecision = $configuration->get('_PS_PRICE_DISPLAY_PRECISION_');

        if (!$this->id) {
            return 0;
        }

        $type = (int) $type;
        $arrayType = [
            static::ONLY_PRODUCTS,
            static::ONLY_DISCOUNTS,
            static::BOTH,
            static::BOTH_WITHOUT_SHIPPING,
            static::ONLY_SHIPPING,
            static::ONLY_WRAPPING,
            static::ONLY_PRODUCTS_WITHOUT_SHIPPING,
            static::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING,
        ];

        // Define virtual context to prevent case where the cart is not the in the global context
        $virtualContext = Context::getContext()->cloneContext();
        $virtualContext->cart = $this;

        if (!in_array($type, $arrayType)) {
            die(Tools::displayError());
        }

        $withShipping = in_array($type, [static::BOTH, static::ONLY_SHIPPING]);

        // if cart rules are not used
        if ($type == static::ONLY_DISCOUNTS && !CartRule::isFeatureActive()) {
            return 0;
        }

        // no shipping cost if is a cart with only virtuals products
        $virtual = $this->isVirtualCart();
        if ($virtual && $type == static::ONLY_SHIPPING) {
            return 0;
        }

        if ($virtual && $type == static::BOTH) {
            $type = static::BOTH_WITHOUT_SHIPPING;
        }

        if ($withShipping || $type == static::ONLY_DISCOUNTS) {
            if (is_null($products) && is_null($idCarrier)) {
                $shippingFees = $this->getTotalShippingCost(null, (bool) $withTaxes);
            } else {
                $shippingFees = $this->getPackageShippingCost((int) $idCarrier, (bool) $withTaxes, null, $products);
            }
        } else {
            $shippingFees = 0;
        }

        if ($type == static::ONLY_SHIPPING) {
            return $shippingFees;
        }

        if ($type == static::ONLY_PRODUCTS_WITHOUT_SHIPPING) {
            $type = static::ONLY_PRODUCTS;
        }

        $paramProduct = true;
        if (is_null($products)) {
            $paramProduct = false;
            $products = $this->getProducts();
        }

        if ($type == static::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING) {
            foreach ($products as $key => $product) {
                if ($product['is_virtual']) {
                    unset($products[$key]);
                }
            }
            $type = static::ONLY_PRODUCTS;
        }

        $orderTotal = 0;
        if (Tax::excludeTaxeOption()) {
            $withTaxes = false;
        }

        $productsTotal = [];

        foreach ($products as $product) {
            // products refer to the cart details
            if ($virtualContext->shop->id != $product['id_shop']) {
                $virtualContext->shop = new Shop((int) $product['id_shop']);
            }

            if ($psTaxAddressType == 'id_address_invoice') {
                $idAddress = (int) $this->id_address_invoice;
            } else {
                $idAddress = (int) $product['id_address_delivery'];
            } // Get delivery address of the product from the cart
            if (!$addressFactory->addressExists($idAddress)) {
                $idAddress = null;
            }

            // The $null variable below is not used,
            // but it is necessary to pass it to getProductPrice because
            // it expects a reference.
            $null = null;
            $price = $priceCalculator->getProductPrice(
                (int) $product['id_product'],
                $withTaxes,
                (int) $product['id_product_attribute'],
                _TB_PRICE_DATABASE_PRECISION_,
                null,
                false,
                true,
                $product['cart_quantity'],
                false,
                (int) $this->id_customer ? (int) $this->id_customer : null,
                (int) $this->id,
                $idAddress,
                $null,
                $psUseEcotax,
                true,
                $virtualContext
            );

            if ($withTaxes) {
                $idTaxRulesGroup = Product::getIdTaxRulesGroupByIdProduct((int) $product['id_product'], $virtualContext);
            } else {
                $idTaxRulesGroup = 0;
            }

            if (in_array($psRoundType, [Order::ROUND_ITEM, Order::ROUND_LINE])) {
                if (!isset($productsTotal[$idTaxRulesGroup])) {
                    $productsTotal[$idTaxRulesGroup] = 0;
                }
            } elseif (!isset($productsTotal[$idTaxRulesGroup.'_'.$idAddress])) {
                $productsTotal[$idTaxRulesGroup.'_'.$idAddress] = 0;
            }

            switch ($psRoundType) {
                case Order::ROUND_TOTAL:
                    $productsTotal[$idTaxRulesGroup.'_'.$idAddress] += $price * (int) $product['cart_quantity'];
                    break;

                case Order::ROUND_LINE:
                    $productsTotal[$idTaxRulesGroup] += Tools::ps_round($price * $product['cart_quantity'], $displayPrecision);
                    break;

                case Order::ROUND_ITEM:
                default:
                    $productPrice = $price;
                    $productsTotal[$idTaxRulesGroup] += Tools::ps_round($productPrice, $displayPrecision) * (int) $product['cart_quantity'];
                    break;
            }
        }

        foreach ($productsTotal as $key => $price) {
            $orderTotal += $price;
        }

        $orderTotalProducts = $orderTotal;

        if ($type == static::ONLY_DISCOUNTS) {
            $orderTotal = 0;
        }

        // Wrapping Fees
        $wrappingFees = 0;

        // With PS_ATCP_SHIPWRAP on the gift wrapping cost computation calls getOrderTotal with $type === static::ONLY_PRODUCTS, so the flag below prevents an infinite recursion.
        $includeGiftWrapping = (!$configuration->get('PS_ATCP_SHIPWRAP') || $type !== static::ONLY_PRODUCTS);

        if ($this->gift && $includeGiftWrapping) {
            $wrappingFees = Tools::convertPrice(Tools::ps_round($this->getGiftWrappingPrice($withTaxes), $displayPrecision), Currency::getCurrencyInstance((int) $this->id_currency));
        }
        if ($type == static::ONLY_WRAPPING) {
            return $wrappingFees;
        }

        $orderTotalDiscount = 0;
        $orderShippingDiscount = 0;
        if (!in_array($type, [static::ONLY_SHIPPING, static::ONLY_PRODUCTS]) && CartRule::isFeatureActive()) {
            // First, retrieve the cart rules associated to this "getOrderTotal"
            if ($withShipping || $type == static::ONLY_DISCOUNTS) {
                $cartRules = $this->getCartRules(CartRule::FILTER_ACTION_ALL);
            } else {
                $cartRules = $this->getCartRules(CartRule::FILTER_ACTION_REDUCTION);
                // Cart Rules array are merged manually in order to avoid doubles
                foreach ($this->getCartRules(CartRule::FILTER_ACTION_GIFT) as $tmpCartRule) {
                    $flag = false;
                    foreach ($cartRules as $cartRule) {
                        if ($tmpCartRule['id_cart_rule'] == $cartRule['id_cart_rule']) {
                            $flag = true;
                        }
                    }
                    if (!$flag) {
                        $cartRules[] = $tmpCartRule;
                    }
                }
            }

            $idAddressDelivery = 0;
            if (isset($products[0])) {
                $idAddressDelivery = (is_null($products) ? $this->id_address_delivery : $products[0]['id_address_delivery']);
            }
            $package = ['id_carrier' => $idCarrier, 'id_address' => $idAddressDelivery, 'products' => $products];

            // Then, calculate the contextual value for each one
            $flag = false;
            foreach ($cartRules as $cartRule) {
                /** @var CartRule $cartRuleObject */
                $cartRuleObject = $cartRule['obj'];
                // If the cart rule offers free shipping, add the shipping cost
                if (($withShipping || $type == static::ONLY_DISCOUNTS) && $cartRuleObject->free_shipping && !$flag) {
                    $orderShippingDiscount = (float) $cartRuleObject->getContextualValue($withTaxes, $virtualContext, CartRule::FILTER_ACTION_SHIPPING, ($paramProduct ? $package : null), $useCache);
                    $flag = true;
                }

                // If the cart rule is a free gift, then add the free gift value only if the gift is in this package
                if ((int) $cartRuleObject->gift_product) {
                    $inOrder = false;
                    if (is_null($products)) {
                        $inOrder = true;
                    } else {
                        foreach ($products as $product) {
                            if ($cartRuleObject->gift_product == $product['id_product'] && $cartRuleObject->gift_product_attribute == $product['id_product_attribute']) {
                                $inOrder = true;
                            }
                        }
                    }

                    if ($inOrder) {
                        $orderTotalDiscount += $cartRuleObject->getContextualValue($withTaxes, $virtualContext, CartRule::FILTER_ACTION_GIFT, $package, $useCache);
                    }
                }

                // If the cart rule offers a reduction, the amount is prorated (with the products in the package)
                if ($cartRuleObject->reduction_percent > 0 || $cartRuleObject->reduction_amount > 0) {
                    $orderTotalDiscount += $cartRuleObject->getContextualValue($withTaxes, $virtualContext, CartRule::FILTER_ACTION_REDUCTION, $package, $useCache);
                }
            }
            $orderTotalDiscount = min($orderTotalDiscount, (float) $orderTotalProducts) + (float) $orderShippingDiscount;
            $orderTotal -= $orderTotalDiscount;
        }

        if ($type == static::BOTH) {
            $orderTotal += $shippingFees + $wrappingFees;
        }

        if ($orderTotal < 0 && $type != static::ONLY_DISCOUNTS) {
            return 0;
        }

        if ($type == static::ONLY_DISCOUNTS) {
            return $orderTotalDiscount;
        }

        return Tools::ps_round((float) $orderTotal, $displayPrecision);
    }

    /**
     * Check if cart contains only virtual products
     *
     * @return bool true if is a virtual cart or false
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function isVirtualCart()
    {
        if (!ProductDownload::isFeatureActive()) {
            return false;
        }

        if (!isset(static::$_isVirtualCart[$this->id])) {
            $products = $this->getProducts();
            if (!count($products)) {
                return false;
            }

            $isVirtual = 1;
            foreach ($products as $product) {
                if (empty($product['is_virtual'])) {
                    $isVirtual = 0;
                }
            }
            static::$_isVirtualCart[$this->id] = (int) $isVirtual;
        }

        return static::$_isVirtualCart[$this->id];
    }

    /**
     * Return shipping total for the cart
     *
     * @param array|null   $deliveryOption Array of the delivery option for each address
     * @param bool         $useTax
     * @param Country|null $defaultCountry
     *
     * @return float Shipping total
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTotalShippingCost($deliveryOption = null, $useTax = true, Country $defaultCountry = null)
    {
        if (isset(Context::getContext()->cookie->id_country)) {
            $defaultCountry = new Country(Context::getContext()->cookie->id_country);
        }
        if (is_null($deliveryOption)) {
            $deliveryOption = $this->getDeliveryOption($defaultCountry, false, false);
        }

        $totalShipping = 0;
        $deliveryOptionList = $this->getDeliveryOptionList($defaultCountry);
        foreach ($deliveryOption as $idAddress => $key) {
            if (!isset($deliveryOptionList[$idAddress]) || !isset($deliveryOptionList[$idAddress][$key])) {
                continue;
            }
            if ($useTax) {
                $totalShipping += $deliveryOptionList[$idAddress][$key]['total_price_with_tax'];
            } else {
                $totalShipping += $deliveryOptionList[$idAddress][$key]['total_price_without_tax'];
            }
        }

        return $totalShipping;
    }

    /**
     * Get the delivery option selected, or if no delivery option was selected,
     * the cheapest option for each address
     *
     * @param Country|null $defaultCountry
     * @param bool         $dontAutoSelectOptions
     * @param bool         $useCache
     *
     * @return array|bool|mixed Delivery option
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws Adapter_Exception
     */
    public function getDeliveryOption($defaultCountry = null, $dontAutoSelectOptions = false, $useCache = true)
    {
        static $cache = [];
        $cacheId = (int) (is_object($defaultCountry) ? $defaultCountry->id : 0).'-'.(int) $dontAutoSelectOptions;
        if (isset($cache[$cacheId]) && $useCache) {
            return $cache[$cacheId];
        }

        $deliveryOptionList = $this->getDeliveryOptionList($defaultCountry);

        // The delivery option was selected
        if (isset($this->delivery_option) && $this->delivery_option != '') {
            $deliveryOption = json_decode($this->delivery_option);
            $validated = true;
            if (is_array($delivery_option)) {
                foreach ($deliveryOption as $idAddress => $key) {
                    if (!isset($deliveryOptionList[$idAddress][$key])) {
                        $validated = false;
                        break;
                    }
                }

                if ($validated) {
                    $cache[$cacheId] = $deliveryOption;

                    return $deliveryOption;
                }
            }
        }

        if ($dontAutoSelectOptions) {
            return false;
        }

        // No delivery option selected or delivery option selected is not valid, get the better for all options
        $deliveryOption = [];
        foreach ($deliveryOptionList as $idAddress => $options) {
            foreach ($options as $key => $option) {
                if (Configuration::get('PS_CARRIER_DEFAULT') == -1 && $option['is_best_price']) {
                    $deliveryOption[$idAddress] = $key;
                    break;
                } elseif (Configuration::get('PS_CARRIER_DEFAULT') == -2 && $option['is_best_grade']) {
                    $deliveryOption[$idAddress] = $key;
                    break;
                } elseif ($option['unique_carrier'] && in_array(Configuration::get('PS_CARRIER_DEFAULT'), array_keys($option['carrier_list']))) {
                    $deliveryOption[$idAddress] = $key;
                    break;
                }
            }

            reset($options);
            if (!isset($deliveryOption[$idAddress])) {
                $deliveryOption[$idAddress] = key($options);
            }
        }

        $cache[$cacheId] = $deliveryOption;

        return $deliveryOption;
    }

    /**
     * Set the delivery option and id_carrier, if there is only one carrier
     *
     * @param array ?null $deliveryOption
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setDeliveryOption($deliveryOption = null)
    {
        if (empty($deliveryOption) || count($deliveryOption) == 0) {
            $this->delivery_option = '';
            $this->id_carrier = 0;

            return;
        }
        Cache::clean('getContextualValue_*');
        $deliveryOptionList = $this->getDeliveryOptionList(null, true);

        foreach ($deliveryOptionList as $idAddress => $options) {
            if (!isset($deliveryOption[$idAddress])) {
                foreach ($options as $key => $option) {
                    if ($option['is_best_price']) {
                        $deliveryOption[$idAddress] = $key;
                        break;
                    }
                }
            }
        }

        if (count($deliveryOption) == 1) {
            $this->id_carrier = $this->getIdCarrierFromDeliveryOption($deliveryOption);
        }

        $this->delivery_option = json_encode($deliveryOption);
    }

    /**
     * Get all deliveries options available for the current cart
     *
     * @param Country $defaultCountry
     * @param bool    $flush Force flushing cache
     *
     * @return array array(
     *                   0 => array( // First address
     *                       '12,' => array(  // First delivery option available for this address
     *                           carrier_list => array(
     *                               12 => array( // First carrier for this option
     *                                   'instance' => Carrier Object,
     *                                   'logo' => <url to the carriers logo>,
     *                                   'price_with_tax' => 12.4,
     *                                   'price_without_tax' => 12.4,
     *                                   'package_list' => array(
     *                                       1,
     *                                       3,
     *                                   ),
     *                               ),
     *                           ),
     *                           is_best_grade => true, // Does this option have the biggest grade (quick shipping) for this shipping address
     *                           is_best_price => true, // Does this option have the lower price for this shipping address
     *                           unique_carrier => true, // Does this option use a unique carrier
     *                           total_price_with_tax => 12.5,
     *                           total_price_without_tax => 12.5,
     *                           position => 5, // Average of the carrier position
     *                       ),
     *                   ),
     *               );
     *               If there are no carriers available for an address, return an empty  array
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getDeliveryOptionList(Country $defaultCountry = null, $flush = false)
    {
        static $cache = [];
        if (isset($cache[$this->id]) && !$flush) {
            return $cache[$this->id];
        }

        $deliveryOptionList = [];
        $carriersPrice = [];
        $carrierCollection = [];
        $packageList = $this->getPackageList($flush);

        // Foreach addresses
        foreach ($packageList as $idAddress => $packages) {
            // Initialize vars
            $deliveryOptionList[$idAddress] = [];
            $carriersPrice[$idAddress] = [];
            $commonCarriers = null;
            $bestPriceCarriers = [];
            $bestGradeCarriers = [];
            $carriersInstance = [];

            // Get country
            if ($idAddress) {
                $address = new Address($idAddress);
                $country = new Country($address->id_country);
            } else {
                $country = $defaultCountry;
            }

            // Foreach packages, get the carriers with best price, best position and best grade
            foreach ($packages as $idPackage => $package) {
                // No carriers available
                if (count($packages) == 1 && count($package['carrier_list']) == 1 && current($package['carrier_list']) == 0) {
                    $cache[$this->id] = [];

                    return $cache[$this->id];
                }

                $carriersPrice[$idAddress][$idPackage] = [];

                // Get all common carriers for each packages to the same address
                if (is_null($commonCarriers)) {
                    $commonCarriers = $package['carrier_list'];
                } else {
                    $commonCarriers = array_intersect($commonCarriers, $package['carrier_list']);
                }

                $bestPrice = null;
                $bestPriceCarrier = null;
                $bestGrade = null;
                $bestGradeCarrier = null;

                // Foreach carriers of the package, calculate his price, check if it the best price, position and grade
                foreach ($package['carrier_list'] as $idCarrier) {
                    if (!isset($carriersInstance[$idCarrier])) {
                        $carriersInstance[$idCarrier] = new Carrier($idCarrier);
                    }

                    $priceWithTax = $this->getPackageShippingCost((int) $idCarrier, true, $country, $package['product_list']);
                    $priceWithoutTax = $this->getPackageShippingCost((int) $idCarrier, false, $country, $package['product_list']);
                    if (is_null($bestPrice) || $priceWithTax < $bestPrice) {
                        $bestPrice = $priceWithTax;
                        $bestPriceCarrier = $idCarrier;
                    }
                    $carriersPrice[$idAddress][$idPackage][$idCarrier] = [
                        'without_tax' => $priceWithoutTax,
                        'with_tax'    => $priceWithTax,
                    ];

                    $grade = $carriersInstance[$idCarrier]->grade;
                    if (is_null($bestGrade) || $grade > $bestGrade) {
                        $bestGrade = $grade;
                        $bestGradeCarrier = $idCarrier;
                    }
                }

                $bestPriceCarriers[$idPackage] = $bestPriceCarrier;
                $bestGradeCarriers[$idPackage] = $bestGradeCarrier;
            }

            // Reset $best_price_carrier, it's now an array
            $bestPriceCarrier = [];
            $key = '';

            // Get the delivery option with the lower price
            foreach ($bestPriceCarriers as $idPackage => $idCarrier) {
                $key .= $idCarrier.',';
                if (!isset($bestPriceCarrier[$idCarrier])) {
                    $bestPriceCarrier[$idCarrier] = [
                        'price_with_tax'    => 0,
                        'price_without_tax' => 0,
                        'package_list'      => [],
                        'product_list'      => [],
                    ];
                }
                $bestPriceCarrier[$idCarrier]['price_with_tax'] += $carriersPrice[$idAddress][$idPackage][$idCarrier]['with_tax'];
                $bestPriceCarrier[$idCarrier]['price_without_tax'] += $carriersPrice[$idAddress][$idPackage][$idCarrier]['without_tax'];
                $bestPriceCarrier[$idCarrier]['package_list'][] = $idPackage;
                $bestPriceCarrier[$idCarrier]['product_list'] = array_merge($bestPriceCarrier[$idCarrier]['product_list'], $packages[$idPackage]['product_list']);
                $bestPriceCarrier[$idCarrier]['instance'] = $carriersInstance[$idCarrier];
                $realBestPrice = !isset($realBestPrice) || $realBestPrice > $carriersPrice[$idAddress][$idPackage][$idCarrier]['with_tax'] ?
                    $carriersPrice[$idAddress][$idPackage][$idCarrier]['with_tax'] : $realBestPrice;
                $realBestPriceWt = !isset($realBestPriceWt) || $realBestPriceWt > $carriersPrice[$idAddress][$idPackage][$idCarrier]['without_tax'] ?
                    $carriersPrice[$idAddress][$idPackage][$idCarrier]['without_tax'] : $realBestPriceWt;
            }

            // Add the delivery option with best price as best price
            $deliveryOptionList[$idAddress][$key] = [
                'carrier_list'   => $bestPriceCarrier,
                'is_best_price'  => true,
                'is_best_grade'  => false,
                'unique_carrier' => (count($bestPriceCarrier) <= 1),
            ];

            // Reset $best_grade_carrier, it's now an array
            $bestGradeCarrier = [];
            $key = '';

            // Get the delivery option with the best grade
            foreach ($bestGradeCarriers as $idPackage => $idCarrier) {
                $key .= $idCarrier.',';
                if (!isset($bestGradeCarrier[$idCarrier])) {
                    $bestGradeCarrier[$idCarrier] = [
                        'price_with_tax'    => 0,
                        'price_without_tax' => 0,
                        'package_list'      => [],
                        'product_list'      => [],
                    ];
                }
                $bestGradeCarrier[$idCarrier]['price_with_tax'] += $carriersPrice[$idAddress][$idPackage][$idCarrier]['with_tax'];
                $bestGradeCarrier[$idCarrier]['price_without_tax'] += $carriersPrice[$idAddress][$idPackage][$idCarrier]['without_tax'];
                $bestGradeCarrier[$idCarrier]['package_list'][] = $idPackage;
                $bestGradeCarrier[$idCarrier]['product_list'] = array_merge($bestGradeCarrier[$idCarrier]['product_list'], $packages[$idPackage]['product_list']);
                $bestGradeCarrier[$idCarrier]['instance'] = $carriersInstance[$idCarrier];
            }

            // Add the delivery option with best grade as best grade
            if (!isset($deliveryOptionList[$idAddress][$key])) {
                $deliveryOptionList[$idAddress][$key] = [
                    'carrier_list'   => $bestGradeCarrier,
                    'is_best_price'  => false,
                    'unique_carrier' => (count($bestGradeCarrier) <= 1),
                ];
            }
            $deliveryOptionList[$idAddress][$key]['is_best_grade'] = true;

            // Get all delivery options with a unique carrier
            foreach ($commonCarriers as $idCarrier) {
                $key = '';
                $packageList = [];
                $productList = [];
                $priceWithTax = 0;
                $priceWithoutTax = 0;

                foreach ($packages as $idPackage => $package) {
                    $key .= $idCarrier.',';
                    $priceWithTax += $carriersPrice[$idAddress][$idPackage][$idCarrier]['with_tax'];
                    $priceWithoutTax += $carriersPrice[$idAddress][$idPackage][$idCarrier]['without_tax'];
                    $packageList[] = $idPackage;
                    $productList = array_merge($productList, $package['product_list']);
                }

                if (!isset($deliveryOptionList[$idAddress][$key])) {
                    $deliveryOptionList[$idAddress][$key] = [
                        'is_best_price'  => false,
                        'is_best_grade'  => false,
                        'unique_carrier' => true,
                        'carrier_list'   => [
                            $idCarrier => [
                                'price_with_tax'    => $priceWithTax,
                                'price_without_tax' => $priceWithoutTax,
                                'instance'          => $carriersInstance[$idCarrier],
                                'package_list'      => $packageList,
                                'product_list'      => $productList,
                            ],
                        ],
                    ];
                } else {
                    $deliveryOptionList[$idAddress][$key]['unique_carrier'] = (count($deliveryOptionList[$idAddress][$key]['carrier_list']) <= 1);
                }
            }
        }

        $cartRules = CartRule::getCustomerCartRules(Context::getContext()->cookie->id_lang, Context::getContext()->cookie->id_customer, true, true, false, $this, true);

        $result = false;
        if ($this->id) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                ->select('*')
                ->from('cart_cart_rule')
                ->where('`id_cart` = '.(int) $this->id)
            );
        }

        $cartRulesInCart = [];

        if (is_array($result)) {
            foreach ($result as $row) {
                $cartRulesInCart[] = $row['id_cart_rule'];
            }
        }

        $totalProductsTaxIncluded = $this->getOrderTotal(true, static::ONLY_PRODUCTS);
        $totalProducts = $this->getOrderTotal(false, static::ONLY_PRODUCTS);

        $freeCarriersRules = [];

        $context = Context::getContext();
        foreach ($cartRules as $cartRule) {
            $totalPrice = $cartRule['minimum_amount_tax'] ? $totalProductsTaxIncluded : $totalProducts;
            $totalPrice += (isset($realBestPrice) && $cartRule['minimum_amount_tax'] && $cartRule['minimum_amount_shipping']) ? $realBestPrice : 0;
            $totalPrice += (isset($realBestPriceWt) && !$cartRule['minimum_amount_tax'] && $cartRule['minimum_amount_shipping']) ? $realBestPriceWt : 0;
            $condition = ($cartRule['free_shipping'] && $cartRule['carrier_restriction'] && $cartRule['minimum_amount'] <= $totalPrice) ? 1 : 0;
            if (isset($cartRule['code']) && !empty($cartRule['code'])) {
                $condition = ($cartRule['free_shipping'] && $cartRule['carrier_restriction'] && in_array($cartRule['id_cart_rule'], $cartRulesInCart)
                    && $cartRule['minimum_amount'] <= $totalPrice) ? 1 : 0;
            }
            if ($condition) {
                $cr = new CartRule((int) $cartRule['id_cart_rule']);
                if (Validate::isLoadedObject($cr) &&
                    $cr->checkValidity($context, in_array((int) $cartRule['id_cart_rule'], $cartRulesInCart), false, false)
                ) {
                    $carriers = $cr->getAssociatedRestrictions('carrier', true, false);
                    if (is_array($carriers) && count($carriers) && isset($carriers['selected'])) {
                        foreach ($carriers['selected'] as $carrier) {
                            if (isset($carrier['id_carrier']) && $carrier['id_carrier']) {
                                $freeCarriersRules[] = (int) $carrier['id_carrier'];
                            }
                        }
                    }
                }
            }
        }

        // For each delivery options :
        //    - Set the carrier list
        //    - Calculate the price
        //    - Calculate the average position
        foreach ($deliveryOptionList as $idAddress => $deliveryOption) {
            foreach ($deliveryOption as $key => $value) {
                $totalPriceWithTax = 0;
                $totalPriceWithoutTax = 0;
                $position = 0;
                foreach ($value['carrier_list'] as $idCarrier => $data) {
                    $totalPriceWithTax += $data['price_with_tax'];
                    $totalPriceWithoutTax += $data['price_without_tax'];
                    $totalPriceWithoutTaxWithRules = (in_array($idCarrier, $freeCarriersRules)) ? 0 : $totalPriceWithoutTax;

                    if (!isset($carrierCollection[$idCarrier])) {
                        $carrierCollection[$idCarrier] = new Carrier($idCarrier);
                    }
                    $deliveryOptionList[$idAddress][$key]['carrier_list'][$idCarrier]['instance'] = $carrierCollection[$idCarrier];

                    if (file_exists(_PS_SHIP_IMG_DIR_.$idCarrier.'.jpg')) {
                        $deliveryOptionList[$idAddress][$key]['carrier_list'][$idCarrier]['logo'] = _THEME_SHIP_DIR_.$idCarrier.'.jpg';
                    } else {
                        $deliveryOptionList[$idAddress][$key]['carrier_list'][$idCarrier]['logo'] = false;
                    }

                    $position += $carrierCollection[$idCarrier]->position;
                }
                if (!isset($totalPriceWithoutTaxWithRules)) {
                    $totalPriceWithoutTaxWithRules = false;
                }
                $deliveryOptionList[$idAddress][$key]['total_price_with_tax'] = $totalPriceWithTax;
                $deliveryOptionList[$idAddress][$key]['total_price_without_tax'] = $totalPriceWithoutTax;
                $deliveryOptionList[$idAddress][$key]['is_free'] = !$totalPriceWithoutTaxWithRules ? true : false;
                $deliveryOptionList[$idAddress][$key]['position'] = $position / count($value['carrier_list']);
            }
        }

        // Sort delivery option list
        foreach ($deliveryOptionList as &$array) {
            uasort($array, ['Cart', 'sortDeliveryOptionList']);
        }

        $cache[$this->id] = $deliveryOptionList;

        return $cache[$this->id];
    }

    /**
     * Get products grouped by package and by addresses to be sent individualy (one package = one shipping cost).
     *
     * @param bool $flush
     *
     * @return array array(
     *                   0 => array( // First address
     *                       0 => array(  // First package
     *                           'product_list' => array(...),
     *                           'carrier_list' => array(...),
     *                           'id_warehouse' => array(...),
     *                       ),
     *                   ),
     *               );
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @todo Add availability check
     */
    public function getPackageList($flush = false)
    {
        static $cache = [];
        $cacheKey = (int) $this->id.'_'.(int) $this->id_address_delivery;
        if (isset($cache[$cacheKey]) && $cache[$cacheKey] !== false && !$flush) {
            return $cache[$cacheKey];
        }

        $productList = $this->getProducts($flush);
        // Step 1 : Get product informations (warehouse_list and carrier_list), count warehouse
        // Determine the best warehouse to determine the packages
        // For that we count the number of time we can use a warehouse for a specific delivery address
        $warehouseCountByAddress = [];

        $stockManagementActive = Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT');

        foreach ($productList as &$product) {
            if ((int) $product['id_address_delivery'] == 0) {
                $product['id_address_delivery'] = (int) $this->id_address_delivery;
            }

            if (!isset($warehouseCountByAddress[$product['id_address_delivery']])) {
                $warehouseCountByAddress[$product['id_address_delivery']] = [];
            }

            $product['warehouse_list'] = [];

            if ($stockManagementActive &&
                (int) $product['advanced_stock_management'] == 1
            ) {
                $warehouseList = Warehouse::getProductWarehouseList($product['id_product'], $product['id_product_attribute'], $this->id_shop);
                if (count($warehouseList) == 0) {
                    $warehouseList = Warehouse::getProductWarehouseList($product['id_product'], $product['id_product_attribute']);
                }
                // Does the product is in stock ?
                // If yes, get only warehouse where the product is in stock

                $warehouseInStock = [];
                $manager = StockManagerFactory::getManager();

                foreach ($warehouseList as $key => $warehouse) {
                    $productRealQuantities = $manager->getProductRealQuantities(
                        $product['id_product'],
                        $product['id_product_attribute'],
                        [$warehouse['id_warehouse']],
                        true
                    );

                    if ($productRealQuantities > 0 || Pack::isPack((int) $product['id_product'])) {
                        $warehouseInStock[] = $warehouse;
                    }
                }

                if (!empty($warehouseInStock)) {
                    $warehouseList = $warehouseInStock;
                    $product['in_stock'] = true;
                } else {
                    $product['in_stock'] = false;
                }
            } else {
                //simulate default warehouse
                $warehouseList = [0 => ['id_warehouse' => 0]];
                $product['in_stock'] = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute']) > 0;
            }

            foreach ($warehouseList as $warehouse) {
                $product['warehouse_list'][$warehouse['id_warehouse']] = $warehouse['id_warehouse'];
                if (!isset($warehouseCountByAddress[$product['id_address_delivery']][$warehouse['id_warehouse']])) {
                    $warehouseCountByAddress[$product['id_address_delivery']][$warehouse['id_warehouse']] = 0;
                }

                $warehouseCountByAddress[$product['id_address_delivery']][$warehouse['id_warehouse']]++;
            }
        }
        unset($product);

        arsort($warehouseCountByAddress);

        // Step 2 : Group product by warehouse
        $groupedByWarehouse = [];

        foreach ($productList as &$product) {
            if (!isset($groupedByWarehouse[$product['id_address_delivery']])) {
                $groupedByWarehouse[$product['id_address_delivery']] = [
                    'in_stock'     => [],
                    'out_of_stock' => [],
                ];
            }

            $product['carrier_list'] = [];
            $idWarehouse = 0;
            foreach ($warehouseCountByAddress[$product['id_address_delivery']] as $idWar => $val) {
                if (array_key_exists((int) $idWar, $product['warehouse_list'])) {
                    $product['carrier_list'] = array_replace($product['carrier_list'], Carrier::getAvailableCarrierList(new Product($product['id_product']), $idWar, $product['id_address_delivery'], null, $this));
                    if (!$idWarehouse) {
                        $idWarehouse = (int) $idWar;
                    }
                }
            }

            if (!isset($groupedByWarehouse[$product['id_address_delivery']]['in_stock'][$idWarehouse])) {
                $groupedByWarehouse[$product['id_address_delivery']]['in_stock'][$idWarehouse] = [];
                $groupedByWarehouse[$product['id_address_delivery']]['out_of_stock'][$idWarehouse] = [];
            }

            if (!$this->allow_seperated_package) {
                $key = 'in_stock';
            } else {
                $key = $product['in_stock'] ? 'in_stock' : 'out_of_stock';
                $productQuantityInStock = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute']);
                if ($product['in_stock'] && $product['cart_quantity'] > $productQuantityInStock) {
                    $outStockPart = $product['cart_quantity'] - $productQuantityInStock;
                    $productBis = $product;
                    $productBis['cart_quantity'] = $outStockPart;
                    $productBis['in_stock'] = 0;
                    $product['cart_quantity'] -= $outStockPart;
                    $groupedByWarehouse[$product['id_address_delivery']]['out_of_stock'][$idWarehouse][] = $productBis;
                }
            }

            if (empty($product['carrier_list'])) {
                $product['carrier_list'] = [0 => 0];
            }

            $groupedByWarehouse[$product['id_address_delivery']][$key][$idWarehouse][] = $product;
        }
        unset($product);

        // Step 3 : grouped product from grouped_by_warehouse by available carriers
        $groupedByCarriers = [];
        foreach ($groupedByWarehouse as $idAddressDelivery => $productsInStockList) {
            if (!isset($groupedByCarriers[$idAddressDelivery])) {
                $groupedByCarriers[$idAddressDelivery] = [
                    'in_stock'     => [],
                    'out_of_stock' => [],
                ];
            }
            foreach ($productsInStockList as $key => $warehouseList) {
                if (!isset($groupedByCarriers[$idAddressDelivery][$key])) {
                    $groupedByCarriers[$idAddressDelivery][$key] = [];
                }
                foreach ($warehouseList as $idWarehouse => $productList) {
                    if (!isset($groupedByCarriers[$idAddressDelivery][$key][$idWarehouse])) {
                        $groupedByCarriers[$idAddressDelivery][$key][$idWarehouse] = [];
                    }
                    foreach ($productList as $product) {
                        $packageCarriersKey = implode(',', $product['carrier_list']);

                        if (!isset($groupedByCarriers[$idAddressDelivery][$key][$idWarehouse][$packageCarriersKey])) {
                            $groupedByCarriers[$idAddressDelivery][$key][$idWarehouse][$packageCarriersKey] = [
                                'product_list'   => [],
                                'carrier_list'   => $product['carrier_list'],
                                'warehouse_list' => $product['warehouse_list'],
                            ];
                        }

                        $groupedByCarriers[$idAddressDelivery][$key][$idWarehouse][$packageCarriersKey]['product_list'][] = $product;
                    }
                }
            }
        }

        $packageList = [];
        // Step 4 : merge product from grouped_by_carriers into $package to minimize the number of package
        foreach ($groupedByCarriers as $idAddressDelivery => $productsInStockList) {
            if (!isset($packageList[$idAddressDelivery])) {
                $packageList[$idAddressDelivery] = [
                    'in_stock'     => [],
                    'out_of_stock' => [],
                ];
            }

            foreach ($productsInStockList as $key => $warehouseList) {
                if (!isset($packageList[$idAddressDelivery][$key])) {
                    $packageList[$idAddressDelivery][$key] = [];
                }
                // Count occurance of each carriers to minimize the number of packages
                $carrierCount = [];
                foreach ($warehouseList as $idWarehouse => $productsGroupedByCarriers) {
                    foreach ($productsGroupedByCarriers as $data) {
                        foreach ($data['carrier_list'] as $idCarrier) {
                            if (!isset($carrierCount[$idCarrier])) {
                                $carrierCount[$idCarrier] = 0;
                            }
                            $carrierCount[$idCarrier]++;
                        }
                    }
                }
                arsort($carrierCount);
                foreach ($warehouseList as $idWarehouse => $productsGroupedByCarriers) {
                    if (!isset($packageList[$idAddressDelivery][$key][$idWarehouse])) {
                        $packageList[$idAddressDelivery][$key][$idWarehouse] = [];
                    }
                    foreach ($productsGroupedByCarriers as $data) {
                        foreach ($carrierCount as $idCarrier => $rate) {
                            if (array_key_exists($idCarrier, $data['carrier_list'])) {
                                if (!isset($packageList[$idAddressDelivery][$key][$idWarehouse][$idCarrier])) {
                                    $packageList[$idAddressDelivery][$key][$idWarehouse][$idCarrier] = [
                                        'carrier_list'   => $data['carrier_list'],
                                        'warehouse_list' => $data['warehouse_list'],
                                        'product_list'   => [],
                                    ];
                                }
                                $packageList[$idAddressDelivery][$key][$idWarehouse][$idCarrier]['carrier_list'] =
                                    array_intersect($packageList[$idAddressDelivery][$key][$idWarehouse][$idCarrier]['carrier_list'], $data['carrier_list']);
                                $packageList[$idAddressDelivery][$key][$idWarehouse][$idCarrier]['product_list'] =
                                    array_merge($packageList[$idAddressDelivery][$key][$idWarehouse][$idCarrier]['product_list'], $data['product_list']);

                                break;
                            }
                        }
                    }
                }
            }
        }

        // Step 5 : Reduce depth of $package_list
        $finalPackageList = [];
        foreach ($packageList as $idAddressDelivery => $productsInStockList) {
            if (!isset($finalPackageList[$idAddressDelivery])) {
                $finalPackageList[$idAddressDelivery] = [];
            }

            foreach ($productsInStockList as $key => $warehouseList) {
                foreach ($warehouseList as $idWarehouse => $productsGroupedByCarriers) {
                    foreach ($productsGroupedByCarriers as $data) {
                        $finalPackageList[$idAddressDelivery][] = [
                            'product_list'   => $data['product_list'],
                            'carrier_list'   => $data['carrier_list'],
                            'warehouse_list' => $data['warehouse_list'],
                            'id_warehouse'   => $idWarehouse,
                        ];
                    }
                }
            }
        }
        $cache[$cacheKey] = $finalPackageList;

        return $finalPackageList;
    }

    /**
     * Return package shipping cost
     *
     * @param int          $idCarrier      Carrier ID (default : current carrier)
     * @param bool         $useTax
     * @param Country|null $defaultCountry
     * @param array|null   $productList    List of product concerned by the shipping.
     *                                     If null, all the product of the cart are used to calculate the shipping cost
     * @param int|null     $idZone
     *
     * @return bool|float Shipping total
     *                    `false` if shipping is not possible
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public function getPackageShippingCost($idCarrier = null, $useTax = true, Country $defaultCountry = null, $productList = null, $idZone = null)
    {
        if ($this->isVirtualCart()) {
            return 0;
        }

        if (!$defaultCountry) {
            $defaultCountry = Context::getContext()->country;
        }

        if (!is_null($productList)) {
            foreach ($productList as $key => $value) {
                if ($value['is_virtual'] == 1) {
                    unset($productList[$key]);
                }
            }
        }

        if (is_null($productList)) {
            $products = $this->getProducts();
        } else {
            $products = $productList;
        }

        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
            $addressId = (int) $this->id_address_invoice;
        } elseif (is_array($productList) && count($productList)) {
            $prod = current($productList);
            $addressId = (int) $prod['id_address_delivery'];
        } else {
            $addressId = null;
        }
        if (!Address::addressExists($addressId)) {
            $addressId = null;
        }

        if (is_null($idCarrier) && !empty($this->id_carrier)) {
            $idCarrier = (int) $this->id_carrier;
        }

        $cacheId = 'getPackageShippingCost_'.(int) $this->id.'_'.(int) $addressId.'_'.(int) $idCarrier.'_'.(int) $useTax.'_'.(int) $defaultCountry->id.'_'.(int) $idZone;
        if ($products) {
            foreach ($products as $product) {
                $cacheId .= '_'.(int) $product['id_product'].'_'.(int) $product['id_product_attribute'];
            }
        }

        if (Cache::isStored($cacheId)) {
            return Cache::retrieve($cacheId);
        }

        // Order total in default currency without fees
        $orderTotal = $this->getOrderTotal(true, static::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING, $productList);

        // Start with shipping cost at 0
        $shippingCost = 0;
        // If no product added, return 0
        if (!count($products)) {
            Cache::store($cacheId, $shippingCost);

            return $shippingCost;
        }

        if (!isset($idZone)) {
            // Get id zone
            if (!$this->isMultiAddressDelivery()
                && isset($this->id_address_delivery) // Be carefull, id_address_delivery is not usefull one 1.5
                && $this->id_address_delivery
                && Customer::customerHasAddress(
                    $this->id_customer,
                    $this->id_address_delivery
                )
            ) {
                $idZone = Address::getZoneById((int) $this->id_address_delivery);
            } else {
                if (!Validate::isLoadedObject($defaultCountry)) {
                    $defaultCountry = new Country(Configuration::get('PS_COUNTRY_DEFAULT'), Configuration::get('PS_LANG_DEFAULT'));
                }

                $idZone = (int) $defaultCountry->id_zone;
            }
        }

        if ($idCarrier && !$this->isCarrierInRange((int) $idCarrier, (int) $idZone)) {
            $idCarrier = '';
        }

        if (empty($idCarrier) && $this->isCarrierInRange((int) Configuration::get('PS_CARRIER_DEFAULT'), (int) $idZone)) {
            $idCarrier = (int) Configuration::get('PS_CARRIER_DEFAULT');
        }

        $totalPackageWithoutShippingTaxInc = $this->getOrderTotal(true, static::BOTH_WITHOUT_SHIPPING, $productList);
        if (empty($idCarrier)) {
            if ((int) $this->id_customer) {
                $customer = new Customer((int) $this->id_customer);
                $result = Carrier::getCarriers((int) Configuration::get('PS_LANG_DEFAULT'), true, false, (int) $idZone, $customer->getGroups());
                unset($customer);
            } else {
                $result = Carrier::getCarriers((int) Configuration::get('PS_LANG_DEFAULT'), true, false, (int) $idZone);
            }

            foreach ($result as $k => $row) {
                if ($row['id_carrier'] == Configuration::get('PS_CARRIER_DEFAULT')) {
                    continue;
                }

                if (!isset(static::$_carriers[$row['id_carrier']])) {
                    static::$_carriers[$row['id_carrier']] = new Carrier((int) $row['id_carrier']);
                }

                /** @var Carrier $carrier */
                $carrier = static::$_carriers[$row['id_carrier']];

                $shippingMethod = $carrier->getShippingMethod();
                // Get only carriers that are compliant with shipping method
                if (($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT && $carrier->getMaxDeliveryPriceByWeight((int) $idZone) === false)
                    || ($shippingMethod == Carrier::SHIPPING_METHOD_PRICE && $carrier->getMaxDeliveryPriceByPrice((int) $idZone) === false)
                ) {
                    unset($result[$k]);
                    continue;
                }

                // If out-of-range behavior carrier is set to "Deactivate carrier"
                if ($row['range_behavior']) {
                    $checkDeliveryPriceByWeight = Carrier::checkDeliveryPriceByWeight($row['id_carrier'], $this->getTotalWeight(), (int) $idZone);

                    $totalOrder = $totalPackageWithoutShippingTaxInc;
                    $checkDeliveryPriceByPrice = Carrier::checkDeliveryPriceByPrice($row['id_carrier'], $totalOrder, (int) $idZone, (int) $this->id_currency);

                    // Get only carriers that have a range compatible with cart
                    if (($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT && !$checkDeliveryPriceByWeight)
                        || ($shippingMethod == Carrier::SHIPPING_METHOD_PRICE && !$checkDeliveryPriceByPrice)
                    ) {
                        unset($result[$k]);
                        continue;
                    }
                }

                if ($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT) {
                    $shipping = $carrier->getDeliveryPriceByWeight($this->getTotalWeight($productList), (int) $idZone);
                } else {
                    $shipping = $carrier->getDeliveryPriceByPrice($orderTotal, (int) $idZone, (int) $this->id_currency);
                }

                if (!isset($minShippingPrice)) {
                    $minShippingPrice = $shipping;
                }

                if ($shipping <= $minShippingPrice) {
                    $idCarrier = (int) $row['id_carrier'];
                    $minShippingPrice = $shipping;
                }
            }
        }

        if (empty($idCarrier)) {
            $idCarrier = Configuration::get('PS_CARRIER_DEFAULT');
        }

        if (!isset(static::$_carriers[$idCarrier])) {
            static::$_carriers[$idCarrier] = new Carrier((int) $idCarrier, Configuration::get('PS_LANG_DEFAULT'));
        }

        $carrier = static::$_carriers[$idCarrier];

        // No valid Carrier or $id_carrier <= 0 ?
        if (!Validate::isLoadedObject($carrier)) {
            Cache::store($cacheId, 0);

            return 0;
        }
        $shippingMethod = $carrier->getShippingMethod();

        if (!$carrier->active) {
            Cache::store($cacheId, $shippingCost);

            return $shippingCost;
        }

        // Free fees if free carrier
        if ($carrier->is_free == 1) {
            Cache::store($cacheId, 0);

            return 0;
        }

        // Select carrier tax
        if ($useTax && !Tax::excludeTaxeOption()) {
            $address = Address::initialize((int) $addressId);

            if (Configuration::get('PS_ATCP_SHIPWRAP')) {
                // With PS_ATCP_SHIPWRAP, pre-tax price is deduced
                // from post tax price, so no $carrier_tax here
                // even though it sounds weird.
                $carrierTax = 0;
            } else {
                $carrierTax = $carrier->getTaxesRate($address);
            }
        }

        $configuration = Configuration::getMultiple(
            [
                'PS_SHIPPING_FREE_PRICE',
                'PS_SHIPPING_HANDLING',
                'PS_SHIPPING_METHOD',
                'PS_SHIPPING_FREE_WEIGHT',
            ]
        );

        // Free fees
        $freeFeesPrice = 0;
        if (isset($configuration['PS_SHIPPING_FREE_PRICE'])) {
            $freeFeesPrice = Tools::convertPrice((float) $configuration['PS_SHIPPING_FREE_PRICE'], Currency::getCurrencyInstance((int) $this->id_currency));
        }
        $orderTotalWithDiscounts = $this->getOrderTotal(true, static::BOTH_WITHOUT_SHIPPING, null, null, false);
        if ($orderTotalWithDiscounts >= (float) ($freeFeesPrice) && (float) ($freeFeesPrice) > 0) {
            Cache::store($cacheId, $shippingCost);

            return $shippingCost;
        }

        if (isset($configuration['PS_SHIPPING_FREE_WEIGHT'])
            && $this->getTotalWeight() >= (float) $configuration['PS_SHIPPING_FREE_WEIGHT']
            && (float) $configuration['PS_SHIPPING_FREE_WEIGHT'] > 0
        ) {
            Cache::store($cacheId, $shippingCost);

            return $shippingCost;
        }

        // Get shipping cost using correct method
        if ($carrier->range_behavior) {
            if (!isset($idZone)) {
                // Get id zone
                if (isset($this->id_address_delivery)
                    && $this->id_address_delivery
                    && Customer::customerHasAddress($this->id_customer, $this->id_address_delivery)
                ) {
                    $idZone = Address::getZoneById((int) $this->id_address_delivery);
                } else {
                    $idZone = (int) $defaultCountry->id_zone;
                }
            }

            if (($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT && !Carrier::checkDeliveryPriceByWeight($carrier->id, $this->getTotalWeight(), (int) $idZone))
                || ($shippingMethod == Carrier::SHIPPING_METHOD_PRICE && !Carrier::checkDeliveryPriceByPrice($carrier->id, $totalPackageWithoutShippingTaxInc, $idZone, (int) $this->id_currency)
                )
            ) {
                $shippingCost += 0;
            } else {
                if ($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT) {
                    $shippingCost += $carrier->getDeliveryPriceByWeight($this->getTotalWeight($productList), $idZone);
                } else { // by price
                    $shippingCost += $carrier->getDeliveryPriceByPrice($orderTotal, $idZone, (int) $this->id_currency);
                }
            }
        } else {
            if ($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT) {
                $shippingCost += $carrier->getDeliveryPriceByWeight($this->getTotalWeight($productList), $idZone);
            } else {
                $shippingCost += $carrier->getDeliveryPriceByPrice($orderTotal, $idZone, (int) $this->id_currency);
            }
        }
        // Adding handling charges
        if (isset($configuration['PS_SHIPPING_HANDLING']) && $carrier->shipping_handling) {
            $shippingCost += (float) $configuration['PS_SHIPPING_HANDLING'];
        }

        // Additional Shipping Cost per product
        foreach ($products as $product) {
            if (!$product['is_virtual']) {
                $shippingCost += $product['additional_shipping_cost'] * $product['cart_quantity'];
            }
        }

        $shippingCost = Tools::convertPrice($shippingCost, Currency::getCurrencyInstance((int) $this->id_currency));

        //get external shipping cost from module
        if ($carrier->shipping_external) {
            $moduleName = $carrier->external_module_name;

            /** @var CarrierModule $module */
            $module = Module::getInstanceByName($moduleName);

            if (Validate::isLoadedObject($module)) {
                if (property_exists($module, 'id_carrier')) {
                    $module->id_carrier = $carrier->id;
                }
                if ($carrier->need_range) {
                    if (method_exists($module, 'getPackageShippingCost')) {
                        $shippingCost = $module->getPackageShippingCost($this, $shippingCost, $products);
                    } else {
                        $shippingCost = $module->getOrderShippingCost($this, $shippingCost);
                    }
                } else {
                    $shippingCost = $module->getOrderShippingCostExternal($this);
                }

                // Check if carrier is available
                if ($shippingCost === false) {
                    Cache::store($cacheId, false);

                    return false;
                }
            } else {
                Cache::store($cacheId, false);

                return false;
            }
        }

        if (Configuration::get('PS_ATCP_SHIPWRAP')) {
            if ($useTax) {
                // With PS_ATCP_SHIPWRAP, we apply the proportionate tax rate to the shipping
                // costs. This is on purpose and required in many countries in the European Union.
                $shippingCost *= (1 + $this->getAverageProductsTaxRate());
            }
        } else {
            // Apply tax
            if ($useTax && isset($carrierTax)) {
                $shippingCost *= 1 + ($carrierTax / 100);
            }
        }

        $shippingCost = (float) Tools::ps_round((float) $shippingCost, (Currency::getCurrencyInstance((int) $this->id_currency)->decimals * _PS_PRICE_DISPLAY_PRECISION_));
        Cache::store($cacheId, $shippingCost);

        return $shippingCost;
    }

    /**
     * Does the cart use multiple address
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function isMultiAddressDelivery()
    {
        static $cache = [];

        if (!isset($cache[$this->id])) {
            $sql = new DbQuery();
            $sql->select('count(distinct id_address_delivery)');
            $sql->from('cart_product', 'cp');
            $sql->where('id_cart = '.(int) $this->id);

            $cache[$this->id] = Db::getInstance()->getValue($sql) > 1;
        }

        return $cache[$this->id];
    }

    /**
     * isCarrierInRange
     *
     * Check if the specified carrier is in range
     *
     * @param int $idCarrier
     * @param int $idZone
     *
     * @return bool
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since      1.0.0
     * @version    1.0.0 Initial version
     */
    public function isCarrierInRange($idCarrier, $idZone)
    {
        $carrier = new Carrier((int) $idCarrier, Configuration::get('PS_LANG_DEFAULT'));
        $shippingMethod = $carrier->getShippingMethod();
        if (!$carrier->range_behavior) {
            return true;
        }

        if ($shippingMethod == Carrier::SHIPPING_METHOD_FREE) {
            return true;
        }

        $checkDeliveryPriceByWeight = Carrier::checkDeliveryPriceByWeight(
            (int) $idCarrier,
            $this->getTotalWeight(),
            $idZone
        );
        if ($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT && $checkDeliveryPriceByWeight) {
            return true;
        }

        $checkDeliveryPriceByPrice = Carrier::checkDeliveryPriceByPrice(
            (int) $idCarrier,
            $this->getOrderTotal(
                true,
                static::BOTH_WITHOUT_SHIPPING
            ),
            $idZone,
            (int) $this->id_currency
        );
        if ($shippingMethod == Carrier::SHIPPING_METHOD_PRICE && $checkDeliveryPriceByPrice) {
            return true;
        }

        return false;
    }

    /**
     * Return cart weight
     *
     * @param array|null $products
     *
     * @return float Cart weight
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function getTotalWeight($products = null)
    {
        if (!is_null($products)) {
            $totalWeight = 0;
            foreach ($products as $product) {
                if (!isset($product['weight_attribute']) || is_null($product['weight_attribute'])) {
                    $totalWeight += $product['weight'] * $product['cart_quantity'];
                } else {
                    $totalWeight += $product['weight_attribute'] * $product['cart_quantity'];
                }
            }

            return $totalWeight;
        }

        if (!isset(static::$_totalWeight[$this->id])) {
            if (Combination::isFeatureActive()) {
                $weightProductWithAttribute = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    (new DbQuery())
                        ->select('SUM((p.`weight` + pa.`weight`) * cp.`quantity`) AS `nb`')
                        ->from('cart_product', 'cp')
                        ->leftJoin('product', 'p', 'cp.`id_product` = p.`id_product`')
                        ->leftJoin('product_attribute', 'pa', 'cp.`id_product_attribute` = pa.`id_product_attribute`')
                        ->where('cp.`id_product_attribute` IS NOT NULL')
                        ->where('cp.`id_product_attribute` != 0')
                        ->where('cp.`id_cart` = '.(int) $this->id)
                );
            } else {
                $weightProductWithAttribute = 0;
            }

            $weightProductWithoutAttribute = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('SUM(p.`weight` * cp.`quantity`) AS `nb`')
                    ->from('cart_product', 'cp')
                    ->leftJoin('product', 'p', 'cp.`id_product` = p.`id_product`')
                    ->where('cp.`id_product_attribute` IS NULL OR cp.`id_product_attribute` = 0')
                    ->where('cp.`id_cart` = '.(int) $this->id)
            );

            static::$_totalWeight[$this->id] = round((float) $weightProductWithAttribute + (float) $weightProductWithoutAttribute, 6);
        }

        return static::$_totalWeight[$this->id];
    }

    /**
     * The arguments are optional and only serve as return values in case caller needs the details.
     *
     * @param null $cartAmountTaxExcluded
     * @param null $cartAmountTaxIncluded
     *
     * @return float|int
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getAverageProductsTaxRate(&$cartAmountTaxExcluded = null, &$cartAmountTaxIncluded = null)
    {
        $cartAmountTaxIncluded = $this->getOrderTotal(true, static::ONLY_PRODUCTS);
        $cartAmountTaxExcluded = $this->getOrderTotal(false, static::ONLY_PRODUCTS);

        // Get the rate according to the applied rounding method
        $roundingMethod = (int) Configuration::get('PS_ROUND_TYPE');
        $precision = _PS_PRICE_DISPLAY_PRECISION_;

        switch ($roundingMethod) {
            case Order::ROUND_ITEM:
                // Round on item
                $total = 0;
                $totalTax = 0;
                foreach ($this->getProducts() as $product) {
		    if(!$product['price'])
                        continue;
                    $price = Tools::ps_round($product['price'], $precision);
                    $priceWithTax = Tools::ps_round($product['price_wt'], $precision);
                    $appliedTaxRate = $priceWithTax / $price - 1;

                    $total += $price * $product['quantity'];
                    $totalTax += $appliedTaxRate * $price * $product['quantity'];
                }

                if ($total <= 0) {
                    return $total;
                }

                return $totalTax / $total;
            case Order::ROUND_LINE:
                // Round on cart line
                $total = 0;
                $totalTax = 0;
                foreach ($this->getProducts() as $product) {
                    $lineTotal = Tools::ps_round($product['total'], $precision);
                    $lineTotalWithTax = Tools::ps_round($product['total_wt'], $precision);
                    $appliedTaxRate = $lineTotalWithTax / $lineTotal - 1;

                    $total += $lineTotal;
                    $totalTax += $appliedTaxRate * $lineTotal;
                }

                if ($total <= 0) {
                    return $total;
                }

                return $totalTax / $total;
            default:
                // Round on total
                $cartVatAmount = $cartAmountTaxIncluded - $cartAmountTaxExcluded;

                if ($cartVatAmount == 0 || $cartAmountTaxExcluded == 0) {
                    return 0;
                } else {
                    return $cartVatAmount / $cartAmountTaxExcluded;
                }
                break;
        }
    }

    /**
     * Get the gift wrapping price
     *
     * @param bool     $withTaxes With or without taxes
     * @param int|null $idAddress Address ID
     *
     * @return float wrapping price
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getGiftWrappingPrice($withTaxes = true, $idAddress = null)
    {
        static $address = [];

        $wrappingFees = (float) Configuration::get('PS_GIFT_WRAPPING_PRICE');

        if ($wrappingFees <= 0) {
            return $wrappingFees;
        }

        if ($withTaxes) {
            if (Configuration::get('PS_ATCP_SHIPWRAP')) {
                // With PS_ATCP_SHIPWRAP, wrapping fee is by default tax included
                // so nothing to do here.
            } else {
                if (!isset($address[$this->id])) {
                    if ($idAddress === null) {
                        $idAddress = (int) $this->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
                    }
                    try {
                        $address[$this->id] = Address::initialize($idAddress);
                    } catch (Exception $e) {
                        $address[$this->id] = new Address();
                        $address[$this->id]->id_country = Configuration::get('PS_COUNTRY_DEFAULT');
                    }
                }

                $taxManager = TaxManagerFactory::getManager($address[$this->id], (int) Configuration::get('PS_GIFT_WRAPPING_TAX_RULES_GROUP'));
                $taxCalculator = $taxManager->getTaxCalculator();
                $wrappingFees = $taxCalculator->addTaxes($wrappingFees);
            }

            if (Configuration::get('PS_ATCP_SHIPWRAP')) {
                // With PS_ATCP_SHIPWRAP, wrapping fee is by default tax included, so we convert it
                // when asked for the pre tax price.
                $wrappingFees = Tools::ps_round(
                    $wrappingFees * (1 + $this->getAverageProductsTaxRate()),
                    _TB_PRICE_DATABASE_PRECISION_
                );
            }
        }

        return $wrappingFees;
    }

    /**
     * @param int $filter
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getCartRules($filter = CartRule::FILTER_ACTION_ALL)
    {
        // If the cart has not been saved, then there can't be any cart rule applied
        if (!CartRule::isFeatureActive() || !$this->id) {
            return [];
        }

        $cacheKey = 'static::getCartRules_'.$this->id.'-'.$filter;
        if (!Cache::isStored($cacheKey)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('cr.*, crl.`id_lang`, crl.`name`, cd.`id_cart`')
                    ->from('cart_cart_rule', 'cd')
                    ->leftJoin('cart_rule', 'cr', 'cd.`id_cart_rule` = cr.`id_cart_rule`')
                    ->leftJoin('cart_rule_lang', 'crl', 'cd.`id_cart_rule` = crl.`id_cart_rule` AND crl.`id_lang` = '.(int) $this->id_lang)
                    ->where('`id_cart` = '.(int) $this->id)
                    ->where((int) $filter === CartRule::FILTER_ACTION_SHIPPING ? '`free_shipping` = 1' : '')
                    ->where((int) $filter === CartRule::FILTER_ACTION_GIFT ? '`gift_product` = 0' : '')
                    ->where((int) $filter === CartRule::FILTER_ACTION_REDUCTION ? '`reduction_percent` != 0 OR `reduction_amount` != 0' : '')
                    ->orderBy('cr.`priority` ASC')
            );
            Cache::store($cacheKey, $result);
        } else {
            $result = Cache::retrieve($cacheKey);
        }

        // Define virtual context to prevent case where the cart is not the in the global context
        $virtualContext = Context::getContext()->cloneContext();
        $virtualContext->cart = $this;

        foreach ($result as &$row) {
            $cartRule = new CartRule();
            $cartRule->hydrate($row);

            $row['obj'] = $cartRule;
            $row['value_real'] = $cartRule->getContextualValue(true, $virtualContext, $filter);
            $row['value_tax_exc'] = $cartRule->getContextualValue(false, $virtualContext, $filter);
            // Retro compatibility < 1.5.0.2
            $row['id_discount'] = $row['id_cart_rule'];
            $row['description'] = $row['name'];
        }

        return $result;
    }

    /*
    ** Customization management
    */

    /**
     * @param $deliveryOption
     *
     * @return int|mixed
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getIdCarrierFromDeliveryOption($deliveryOption)
    {
        $deliveryOptionList = $this->getDeliveryOptionList();
        foreach ($deliveryOption as $key => $value) {
            if (isset($deliveryOptionList[$key]) && isset($deliveryOptionList[$key][$value])) {
                if (count($deliveryOptionList[$key][$value]['carrier_list']) == 1) {
                    return current(array_keys($deliveryOptionList[$key][$value]['carrier_list']));
                }
            }
        }

        return 0;
    }

    /**
     *
     * Sort list of option delivery by parameters define in the BO
     *
     * @param array $option1
     * @param array $option2
     *
     * @return int -1 if $option 1 must be placed before and 1 if the $option1 must be placed after the $option2
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function sortDeliveryOptionList($option1, $option2)
    {
        static $orderByPrice = null;
        static $orderWay = null;
        if (is_null($orderByPrice)) {
            $orderByPrice = !Configuration::get('PS_CARRIER_DEFAULT_SORT');
        }
        if (is_null($orderWay)) {
            $orderWay = Configuration::get('PS_CARRIER_DEFAULT_ORDER');
        }

        if ($orderByPrice) {
            if ($orderWay) {
                return ($option1['total_price_with_tax'] < $option2['total_price_with_tax']) * 2 - 1;
            } // return -1 or 1
            else {
                return ($option1['total_price_with_tax'] >= $option2['total_price_with_tax']) * 2 - 1;
            }
        } // return -1 or 1
        elseif ($orderWay) {
            return ($option1['position'] < $option2['position']) * 2 - 1;
        } // return -1 or 1
        else {
            return ($option1['position'] >= $option2['position']) * 2 - 1;
        } // return -1 or 1
    }

    /**
     * Translate a int option_delivery identifier (3240002000) in a string ('24,3,')
     *
     * @param int    $int
     * @param string $delimiter
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function desintifier($int, $delimiter = ',')
    {
        $delimiterLen = $int[0];
        $int = strrev(substr($int, 1));
        $elm = explode(str_repeat('0', $delimiterLen + 1), $int);

        return strrev(implode($delimiter, $elm));
    }

    /**
     * @param int $idCustomer
     *
     * @return bool|int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function lastNoneOrderedCart($idCustomer)
    {
        if (!$idCart = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('c.`id_cart`')
                ->from('cart', 'c')
                ->where('NOT EXISTS (SELECT 1 FROM '._DB_PREFIX_.'orders o WHERE o.`id_cart` = c.`id_cart`AND o.`id_customer` = '.(int) $idCustomer.')')
                ->where('c.`id_customer` = '.(int) $idCustomer.' '.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'c'))
                ->orderBy('c.`date_upd` DESC')
        )) {
            return false;
        }

        return (int) $idCart;
    }

    /**
     * Build cart object from provided id_order
     *
     * @param int $idOrder
     *
     * @return Cart|bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCartByOrderId($idOrder)
    {
        if ($idCart = static::getCartIdByOrderId($idOrder)) {
            return new Cart((int) $idCart);
        }

        return false;
    }

    /**
     * @param int $idOrder
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCartIdByOrderId($idOrder)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_cart`')
                ->from('orders')
                ->where('`id_order` = '.(int) $idOrder)
        );
        if (!$result || empty($result) || !array_key_exists('id_cart', $result)) {
            return false;
        }

        return $result['id_cart'];
    }

    /**
     * @param int  $idCustomer
     * @param bool $withOrder
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCustomerCarts($idCustomer, $withOrder = true)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('cart', 'c')
                ->where('c.`id_customer` = '.(int) $idCustomer)
                ->where($withOrder ? 'NOT EXISTS (SELECT 1 FROM '._DB_PREFIX_.'orders o WHERE o.`id_cart` = c.`id_cart`)' : '')
                ->orderBy('c.`date_add` DESC')
        );
    }

    /**
     * @param string $echo
     * @param mixed  $tr
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function replaceZeroByShopName($echo, $tr)
    {
        return ($echo == '0' ? Carrier::getCarrierNameFromShopName() : $echo);
    }

    /**
     * isGuestCartByCartId
     *
     * @param int $idCart
     *
     * @return bool true if cart has been made by a guest customer
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isGuestCartByCartId($idCart)
    {
        if (!(int) $idCart) {
            return false;
        }

        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`is_guest`')
                ->from('customer', 'cu')
                ->leftJoin('cart', 'ca', 'ca.`id_customer` = cu.`id_customer`')
                ->where('ca.`id_cart` = '.(int) $idCart)
        );
    }

    /**
     *
     * Execute hook displayCarrierList (extraCarrier) and merge theme to the $array
     *
     * @param array $array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function addExtraCarriers(&$array)
    {
        $first = true;
        $hookExtracarrierAddr = [];
        foreach (Context::getContext()->cart->getAddressCollection() as $address) {
            $hook = Hook::exec('displayCarrierList', ['address' => $address]);
            $hookExtracarrierAddr[$address->id] = $hook;

            if ($first) {
                $array = array_merge(
                    $array,
                    ['HOOK_EXTRACARRIER' => $hook]
                );
                $first = false;
            }
            $array = array_merge(
                $array,
                ['HOOK_EXTRACARRIER_ADDR' => $hookExtracarrierAddr]
            );
        }
    }

    /**
     * Get all delivery addresses object for the current cart
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getAddressCollection()
    {
        $collection = [];
        $cacheId = 'static::getAddressCollection'.(int) $this->id;
        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance()->executeS(
                (new DbQuery())
                    ->select('DISTINCT `id_address_delivery`')
                    ->from('cart_product')
                    ->where('`id_cart` = '.(int) $this->id)
            );
            Cache::store($cacheId, $result);
        } else {
            $result = Cache::retrieve($cacheId);
        }

        $result[] = ['id_address_delivery' => (int) $this->id_address_delivery];

        foreach ($result as $row) {
            if ((int) $row['id_address_delivery'] != 0) {
                $collection[(int) $row['id_address_delivery']] = new Address((int) $row['id_address_delivery']);
            }
        }

        return $collection;
    }

    /**
     * Update the address id of the cart
     *
     * @param int $idAddress    Current address id to change
     * @param int $idAddressNew New address id
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updateAddressId($idAddress, $idAddressNew)
    {
        $toUpdate = false;
        if (!isset($this->id_address_invoice) || $this->id_address_invoice == $idAddress) {
            $toUpdate = true;
            $this->id_address_invoice = $idAddressNew;
        }
        if (!isset($this->id_address_delivery) || $this->id_address_delivery == $idAddress) {
            $toUpdate = true;
            $this->id_address_delivery = $idAddressNew;
        }
        if ($toUpdate) {
            $this->update();
        }

        Db::getInstance()->update(
            'cart_product',
            [
                'id_address_delivery' => (int) $idAddressNew,
            ],
            '`id_cart` = '.(int) $this->id.' AND `id_address_delivery` = '.(int) $idAddress
        );

        Db::getInstance()->update(
            'customization',
            [
                'id_address_delivery' => (int) $idAddressNew,
            ],
            '`id_cart` = '.(int) $this->id.' AND `id_address_delivery` = '.(int) $idAddress
        );
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($nullValues = false)
    {
        if (isset(static::$_nbProducts[$this->id])) {
            unset(static::$_nbProducts[$this->id]);
        }

        if (isset(static::$_totalWeight[$this->id])) {
            unset(static::$_totalWeight[$this->id]);
        }

        $this->_products = null;
        $return = parent::update($nullValues);
        Hook::exec('actionCartSave', ['cart' => $this]);

        return $return;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if ($this->OrderExists()) { //NOT delete a cart which is associated with an order
            return false;
        }

        $uploadedFiles = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cd.`value`')
                ->from('customized_data', 'cd')
                ->innerJoin('customization', 'c', 'cd.`id_customization` = c.`id_customization`')
                ->where('cd.`type` = 0')
                ->where('c.`id_cart` = '.(int) $this->id)
        );

        foreach ($uploadedFiles as $mustUnlink) {
            unlink(_PS_UPLOAD_DIR_.$mustUnlink['value'].'_small');
            unlink(_PS_UPLOAD_DIR_.$mustUnlink['value']);
        }

        Db::getInstance()->delete(
            'customized_data',
            '`id_customization` IN (SELECT `id_customization` FROM `'._DB_PREFIX_.'customization` WHERE `id_cart`='.(int) $this->id.')'
        );

        Db::getInstance()->delete(
            'customization',
            '`id_cart` = '.(int) $this->id
        );

        if (!Db::getInstance()->delete('cart_cart_rule', '`id_cart` = '.(int) $this->id)
            || !Db::getInstance()->delete('cart_product', '`id_cart` = '.(int) $this->id)
        ) {
            return false;
        }

        return parent::delete();
    }

    /**
     * Check if order has already been placed
     *
     * @return bool result
     *
     * @since   1.0.0
     * @version 1.0.0
     * @throws PrestaShopException
     */
    public function orderExists()
    {
        $cacheId = 'static::orderExists_'.(int) $this->id;
        if (!Cache::isStored($cacheId)) {
            $result = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('orders')
                    ->where('`id_cart` = '.(int) $this->id)
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @deprecated 1.0.0, use Cart->getCartRules()
     *
     * @param bool $lite
     * @param bool $refresh
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getDiscounts($lite = false, $refresh = false)
    {
        Tools::displayAsDeprecated();

        return $this->getCartRules();
    }

    /**
     * Return the cart rules Ids on the cart.
     *
     * @param int $filter
     *
     * @return array
     * @throws PrestaShopDatabaseException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getOrderedCartRulesIds($filter = CartRule::FILTER_ACTION_ALL)
    {
        $cacheKey = 'static::getOrderedCartRulesIds_'.$this->id.'-'.$filter.'-ids';
        if (!Cache::isStored($cacheKey)) {
            $result = Db::getInstance()->executeS(
                (new DbQuery())
                    ->select('cr.`id_cart_rule`')
                    ->from('cart_cart_rule', 'cd')
                    ->leftJoin('cart_rule', 'cr', 'cd.`id_cart_rule` = cr.`id_cart_rule`')
                    ->leftJoin('cart_rule_lang', 'crl', 'cd.`id_cart_rule` = crl.`id_cart_rule` AND crl.`id_lang` = '.(int) $this->id_lang)
                    ->where('cd.`id_cart` = '.(int) $this->id)
                    ->where($filter === CartRule::FILTER_ACTION_SHIPPING ? 'cr.`free_shipping` = 1' : '')
                    ->where($filter === CartRule::FILTER_ACTION_GIFT ? 'cr.`gift_product` = 1' : '')
                    ->where($filter === CartRule::FILTER_ACTION_REDUCTION ? 'cr.`reduction_percent` != 0 OR cr.`reduction_amount` != 0' : '')
                    ->orderBy('cr.`priority` ASC')
            );
            Cache::store($cacheKey, $result);
        } else {
            $result = Cache::retrieve($cacheKey);
        }

        return $result;
    }

    /**
     * @param int $idCartRule
     *
     * @return int|null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getDiscountsCustomer($idCartRule)
    {
        if (!CartRule::isFeatureActive()) {
            return 0;
        }
        $cacheId = 'static::getDiscountsCustomer_'.(int) $this->id.'-'.(int) $idCartRule;
        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('cart_cart_rule')
                    ->where('`id_cart_rule` = '.(int) $idCartRule)
                    ->where('`id_cart` = '.(int) $this->id)
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @return bool|mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLastProduct()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_product`, `id_product_attribute`, `id_shop`')
                ->from('cart_product', 'cp')
                ->where('`id_cart` = '.(int) $this->id)
                ->orderBy('`date_add` DESC')
        );
        if ($result && isset($result['id_product']) && $result['id_product']) {
            foreach ($this->getProducts() as $product) {
                if ($result['id_product'] == $product['id_product']
                    && (
                        !$result['id_product_attribute']
                        || $result['id_product_attribute'] == $product['id_product_attribute']
                    )
                ) {
                    return $product;
                }
            }
        }

        return false;
    }

    /**
     * Return cart products quantity
     *
     * @result  integer Products quantity
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function nbProducts()
    {
        if (!$this->id) {
            return 0;
        }

        return static::getNbProducts($this->id);
    }

    /**
     * @param int $id
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getNbProducts($id)
    {
        // Must be strictly compared to NULL, or else an empty cart will bypass the cache and add dozens of queries
        if (isset(static::$_nbProducts[$id]) && static::$_nbProducts[$id] !== null) {
            return static::$_nbProducts[$id];
        }

        static::$_nbProducts[$id] = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('SUM(`quantity`)')
                ->from('cart_product')
                ->where('`id_cart` = '.(int) $id)
        );

        return static::$_nbProducts[$id];
    }

    /**
     * @deprecated 1.0.0, use Cart->addCartRule()
     *
     * @param int $idCartRule
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function addDiscount($idCartRule)
    {
        Tools::displayAsDeprecated();

        return $this->addCartRule($idCartRule);
    }

    /**
     * @param int $idCartRule
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function addCartRule($idCartRule)
    {
        // You can't add a cart rule that does not exist
        $cartRule = new CartRule($idCartRule, Context::getContext()->language->id);

        if (!Validate::isLoadedObject($cartRule)) {
            return false;
        }

        if (Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_cart_rule`')
                ->from('cart_cart_rule')
                ->where('`id_cart_rule` = '.(int) $idCartRule)
                ->where('`id_cart` = '.(int) $this->id)
        )) {
            return false;
        }

        // Add the cart rule to the cart
        if (!Db::getInstance()->insert(
            'cart_cart_rule',
            [
                'id_cart_rule' => (int) $idCartRule,
                'id_cart'      => (int) $this->id,
            ]
        )
        ) {
            return false;
        }

        Cache::clean('static::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_ALL);
        Cache::clean('static::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_SHIPPING);
        Cache::clean('static::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_REDUCTION);
        Cache::clean('static::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_GIFT);

        Cache::clean('static::getOrderedCartRulesIds_'.$this->id.'-'.CartRule::FILTER_ACTION_ALL.'-ids');
        Cache::clean('static::getOrderedCartRulesIds_'.$this->id.'-'.CartRule::FILTER_ACTION_SHIPPING.'-ids');
        Cache::clean('static::getOrderedCartRulesIds_'.$this->id.'-'.CartRule::FILTER_ACTION_REDUCTION.'-ids');
        Cache::clean('static::getOrderedCartRulesIds_'.$this->id.'-'.CartRule::FILTER_ACTION_GIFT.'-ids');

        if ((int) $cartRule->gift_product) {
            $this->updateQty(1, $cartRule->gift_product, $cartRule->gift_product_attribute, false, 'up', 0, null, false);
        }

        return true;
    }

    /**
     * Update product quantity
     *
     * @param int      $quantity           Quantity to add (or substract)
     * @param int      $idProduct          Product ID
     * @param int      $idProductAttribute Attribute ID if needed
     * @param int|bool $idCustomization
     * @param string   $operator           Indicate if quantity must be increased or decreased
     * @param int      $idAddressDelivery
     * @param Shop     $shop
     * @param bool     $autoAddCartRule
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updateQty(
        $quantity,
        $idProduct,
        $idProductAttribute = null,
        $idCustomization = false,
        $operator = 'up',
        $idAddressDelivery = 0,
        Shop $shop = null,
        $autoAddCartRule = true
    ) {
        if (!$shop) {
            $shop = Context::getContext()->shop;
        }

        if (Context::getContext()->customer->id) {
            if ($idAddressDelivery == 0 && (int) $this->id_address_delivery) { // The $id_address_delivery is null, use the cart delivery address
                $idAddressDelivery = $this->id_address_delivery;
            } elseif ($idAddressDelivery == 0) { // The $id_address_delivery is null, get the default customer address
                $idAddressDelivery = (int) Address::getFirstCustomerAddressId((int) Context::getContext()->customer->id);
            } elseif (!Customer::customerHasAddress(Context::getContext()->customer->id, $idAddressDelivery)) { // The $id_address_delivery must be linked with customer
                $idAddressDelivery = 0;
            }
        }

        $quantity = (int) $quantity;
        $idProduct = (int) $idProduct;
        $idProductAttribute = (int) $idProductAttribute;
        $product = new Product($idProduct, false, Configuration::get('PS_LANG_DEFAULT'), $shop->id);

        if ($idProductAttribute) {
            $combination = new Combination((int) $idProductAttribute);
            if ($combination->id_product != $idProduct) {
                return false;
            }
        }

        /* If we have a product combination, the minimal quantity is set with the one of this combination */
        if (!empty($idProductAttribute)) {
            $minimalQuantity = (int) Attribute::getAttributeMinimalQty($idProductAttribute);
        } else {
            $minimalQuantity = (int) $product->minimal_quantity;
        }

        if (!Validate::isLoadedObject($product)) {
            die(Tools::displayError());
        }

        if (isset(static::$_nbProducts[$this->id])) {
            unset(static::$_nbProducts[$this->id]);
        }

        if (isset(static::$_totalWeight[$this->id])) {
            unset(static::$_totalWeight[$this->id]);
        }

        Hook::exec(
            'actionBeforeCartUpdateQty',
            [
                'cart'                 => $this,
                'product'              => $product,
                'id_product_attribute' => $idProductAttribute,
                'id_customization'     => $idCustomization,
                'quantity'             => $quantity,
                'operator'             => $operator,
                'id_address_delivery'  => $idAddressDelivery,
                'shop'                 => $shop,
                'auto_add_cart_rule'   => $autoAddCartRule,
            ]
        );

        if ((int) $quantity <= 0) {
            return $this->deleteProduct($idProduct, $idProductAttribute, (int) $idCustomization, 0, $autoAddCartRule);
        } elseif (!$product->available_for_order || (Configuration::get('PS_CATALOG_MODE') && !defined('_PS_ADMIN_DIR_'))) {
            return false;
        } else {
            /* Check if the product is already in the cart */
            $result = $this->containsProduct($idProduct, $idProductAttribute, (int) $idCustomization, (int) $idAddressDelivery);

            /* Update quantity if product already exist */
            if ($result) {
                if ($operator == 'up') {
                    $result2 = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                        (new DbQuery())
                            ->select('stock.`out_of_stock`, IFNULL(stock.`quantity`, 0) AS `quantity`')
                            ->from('product', 'p')
                            ->join(Product::sqlStock('p', $idProductAttribute, true, $shop))
                            ->where('p.`id_product` = '.(int) $idProduct)
                    );
                    $productQty = (int) $result2['quantity'];
                    // Quantity for product pack
                    if (Pack::isPack($idProduct)) {
                        $productQty = Pack::getQuantity($idProduct, $idProductAttribute);
                    }
                    $newQty = (int) $result['quantity'] + (int) $quantity;
                    $qty = '+ '.(int) $quantity;

                    if (!Product::isAvailableWhenOutOfStock((int) $result2['out_of_stock'])) {
                        if ($newQty > $productQty) {
                            return false;
                        }
                    }
                } elseif ($operator == 'down') {
                    $qty = '- '.(int) $quantity;
                    $newQty = (int) $result['quantity'] - (int) $quantity;
                    if ($newQty < $minimalQuantity && $minimalQuantity > 1) {
                        return -1;
                    }
                } else {
                    return false;
                }

                /* Delete product from cart */
                if ($newQty <= 0) {
                    return $this->deleteProduct((int) $idProduct, (int) $idProductAttribute, (int) $idCustomization, 0, $autoAddCartRule);
                } elseif ($newQty < $minimalQuantity) {
                    return -1;
                } else {
                    Db::getInstance()->update(
                        'cart_product',
                        [
                            'quantity' => ['type' => 'sql', 'value' => '`quantity` '.$qty],
                            'date_add' => ['type' => 'sql', 'value' => 'NOW()'],
                        ],
                        '`id_product` = '.(int) $idProduct.(!empty($idProductAttribute) ? ' AND `id_product_attribute` = '.(int) $idProductAttribute : '').' AND `id_cart` = '.(int) $this->id.(Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery() ? ' AND `id_address_delivery` = '.(int) $idAddressDelivery : ''),
                        1
                    );
                }
            } elseif ($operator == 'up') {
                /* Add product to the cart */
                $result2 = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                    (new DbQuery())
                        ->select('stock.`out_of_stock`, IFNULL(stock.`quantity`, 0) AS `quantity`')
                        ->from('product', 'p')
                        ->join(Product::sqlStock('p', $idProductAttribute, true, $shop))
                        ->where('p.`id_product` = '.(int) $idProduct)
                );

                // Quantity for product pack
                if (Pack::isPack($idProduct)) {
                    $result2['quantity'] = Pack::getQuantity($idProduct, $idProductAttribute);
                }

                if (!Product::isAvailableWhenOutOfStock((int) $result2['out_of_stock'])) {
                    if ((int) $quantity > $result2['quantity']) {
                        return false;
                    }
                }

                if ((int) $quantity < $minimalQuantity) {
                    return -1;
                }

                $resultAdd = Db::getInstance()->insert(
                    'cart_product',
                    [
                        'id_product'           => (int) $idProduct,
                        'id_product_attribute' => (int) $idProductAttribute,
                        'id_cart'              => (int) $this->id,
                        'id_address_delivery'  => (int) $idAddressDelivery,
                        'id_shop'              => $shop->id,
                        'quantity'             => (int) $quantity,
                        'date_add'             => date('Y-m-d H:i:s'),
                    ]
                );

                if (!$resultAdd) {
                    return false;
                }
            }
        }

        // refresh cache of static::_products
        $this->_products = $this->getProducts(true);
        $this->update();
        $context = Context::getContext()->cloneContext();
        $context->cart = $this;
        Cache::clean('getContextualValue_*');
        if ($autoAddCartRule) {
            CartRule::autoAddToCart($context);
        }

        if ($product->customizable) {
            return $this->_updateCustomizationQuantity((int) $quantity, (int) $idCustomization, (int) $idProduct, (int) $idProductAttribute, (int) $idAddressDelivery, $operator);
        } else {
            return true;
        }
    }

    /**
     * Delete a product from the cart
     *
     * @param int  $idProduct          Product ID
     * @param int  $idProductAttribute Attribute ID if needed
     * @param int  $idCustomization    Customization id
     * @param int  $idAddressDelivery
     * @param bool $autoAddCartRule
     *
     * @return bool result
     * @throws PrestaShopException
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function deleteProduct($idProduct, $idProductAttribute = null, $idCustomization = null, $idAddressDelivery = 0, $autoAddCartRule = true)
    {
        if (isset(static::$_nbProducts[$this->id])) {
            unset(static::$_nbProducts[$this->id]);
        }

        if (isset(static::$_totalWeight[$this->id])) {
            unset(static::$_totalWeight[$this->id]);
        }

        if ((int) $idCustomization) {
            $productTotalQuantity = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`quantity`')
                    ->from('cart_product')
                    ->where('`id_cart` = '.(int) $this->id)
                    ->where('`id_product` = '.(int) $idProduct)
                    ->where('`id_product_attribute` = '.(int) $idProductAttribute)
            );

            $customizationQuantity = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`quantity`')
                    ->from('customization')
                    ->where('`id_cart` = '.(int) $this->id)
                    ->where('`id_product` = '.(int) $idProduct)
                    ->where('`id_product_attribute` = '.(int) $idProductAttribute)
                    ->where($idAddressDelivery ? '`id_address_delivery` = '.(int) $idAddressDelivery : '')
            );

            if (!$this->_deleteCustomization((int) $idCustomization, (int) $idProduct, (int) $idProductAttribute, (int) $idAddressDelivery)) {
                return false;
            }

            // refresh cache of static::_products
            $this->_products = $this->getProducts(true);

            return ($customizationQuantity == $productTotalQuantity && $this->deleteProduct((int) $idProduct, (int) $idProductAttribute, null, (int) $idAddressDelivery));
        }

        /* Get customization quantity */
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('SUM(`quantity`)')
                ->from('customization')
                ->where('`id_cart` = '.(int) $this->id)
                ->where('`id_product` = '.(int) $idProduct)
                ->where('`id_product_attribute` = '.(int) $idProductAttribute)
        );

        if ($result === false) {
            return false;
        }

        /* If the product still possesses customization it does not have to be deleted */
        if (Db::getInstance()->NumRows() && isset($result['quantity']) && (int) $result['quantity']) {
            return Db::getInstance()->update(
                'cart_product',
                [
                    'quantity' => (int) $result['quantity'],
                ],
                '`id_cart` = '.(int) $this->id.' AND `id_product` = '.(int) $idProduct.($idProductAttribute != null ? ' AND `id_product_attribute` = '.(int) $idProductAttribute : '')
            );
        }

        /* Product deletion */
        $result = Db::getInstance()->delete(
            'cart_product',
            '`id_product` = '.(int) $idProduct.' '.(!is_null($idProductAttribute) ? ' AND `id_product_attribute` = '.(int) $idProductAttribute : '').' AND `id_cart` = '.(int) $this->id.' '.((int) $idAddressDelivery ? 'AND `id_address_delivery` = '.(int) $idAddressDelivery : '')
        );

        if ($result) {
            $return = $this->update();
            // refresh cache of static::_products
            $this->_products = $this->getProducts(true);
            CartRule::autoRemoveFromCart();
            if ($autoAddCartRule) {
                CartRule::autoAddToCart();
            }

            return $return;
        }

        return false;
    }

    /**
     * Delete a customization from the cart. If customization is a Picture,
     * then the image is also deleted
     *
     * @param int $idCustomization
     *
     * @return bool result
     *
     * @deprecated 2.0.0
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    // @codingStandardsIgnoreStart
    protected function _deleteCustomization($idCustomization, $idProduct, $idProductAttribute, $idAddressDelivery = 0)
    {
        // @codingStandardsIgnoreEnd
        $result = true;
        $customization = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('customization')
                ->where('`id_customization` = '.(int) $idCustomization)
        );

        if ($customization) {
            $custData = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('*')
                    ->from('customized_data')
                    ->where('`id_customization` = '.(int) $idCustomization)
            );

            // Delete customization picture if necessary
            if (isset($custData['type']) && $custData['type'] == 0) {
                $result &= (@unlink(_PS_UPLOAD_DIR_.$custData['value']) && @unlink(_PS_UPLOAD_DIR_.$custData['value'].'_small'));
            }

            $result &= Db::getInstance()->delete('customized_data', '`id_customization` = '.(int) $idCustomization);

            if ($result) {
                $result &= Db::getInstance()->update(
                    'cart_product',
                    [
                        'quantity' => ['type' => 'sql', 'value' => '`quantity` - '.(int) $customization['quantity']],
                    ],
                    '`id_cart` = '.(int) $this->id.' AND `id_product` = '.(int) $idProduct.((int) $idProductAttribute ? ' AND `id_product_attribute` = '.(int) $idProductAttribute : '').' AND `id_address_delivery` = '.(int) $idAddressDelivery
                );
            }

            if (!$result) {
                return false;
            }

            return Db::getInstance()->delete('customization', '`id_customization` = '.(int) $idCustomization);
        }

        return true;
    }

    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idCustomization
     * @param int $idAddressDelivery
     *
     * @return array|bool|null|object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function containsProduct($idProduct, $idProductAttribute = 0, $idCustomization = 0, $idAddressDelivery = 0)
    {
        $sql = (new DbQuery())
            ->select('cp.`quantity`')
            ->from('cart_product', 'cp');

        if ($idCustomization) {
            $sql->leftJoin('customization', 'c', 'c.`id_product` = cp.`id_product`');
            $sql->where('c.`id_product_attribute` = cp.`id_product_attribute`');
        }

        $sql->where('cp.`id_product` = '.(int) $idProduct);
        $sql->where('cp.`id_product_attribute` = '.(int) $idProductAttribute);
        $sql->where('cp.`id_cart` = '.(int) $this->id);
        if (Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery()) {
            $sql->where('cp.`id_address_delivery` = '.(int) $idAddressDelivery);
        }

        if ($idCustomization) {
            $sql->where('c.`id_customization` = '.(int) $idCustomization);
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
    }

    /**
     * @param int    $quantityChange     Quantity change
     * @param int    $idCustomization    Customization ID
     * @param int    $idProduct          Product ID
     * @param int    $idProductAttribute Product Attribute ID
     * @param int    $idAddressDelivery  Address ID
     * @param string $operator           `up` or `down`
     *
     * @return bool
     *
     * @deprecated 2.0.0
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    protected function _updateCustomizationQuantity($quantityChange, $idCustomization, $idProduct, $idProductAttribute, $idAddressDelivery, $operator = 'up')
    {
        // Link customization to product combination when it is first added to cart
        if (empty($idCustomization) && $operator === 'up') {
            $customization = $this->getProductCustomization($idProduct, null, true);
            foreach ($customization as $field) {
                if ((int) $field['quantity'] === 0) {
                    Db::getInstance()->update(
                        'customization',
                        [
                            'quantity'             => (int) $quantityChange,
                            'id_product'           => (int) $idProduct,
                            'id_product_attribute' => (int) $idProductAttribute,
                            'id_address_delivery'  => (int) $idAddressDelivery,
                            'in_cart'              => true,
                        ],
                        '`id_customization` = '.(int) $field['id_customization']
                    );
                }
            }
        }

        /* Quantity update */
        if (!empty($idCustomization)) {
            $result = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`quantity`')
                    ->from('customization')
                    ->where('`id_customization` = '.(int) $idCustomization)
            );

            if ($operator === 'down' && ((int) $result - (int) $quantityChange) < 1) {
                return Db::getInstance()->delete('customization', '`id_customization` = '.(int) $idCustomization);
            }

            return Db::getInstance()->update(
                'customization',
                [
                    'quantity'            => ['type' => 'sql', 'value' => '`quantity` '.($operator === 'up' ? '+' : '-').(int) $quantityChange],
                    'id_address_delivery' => (int) $idAddressDelivery,
                    'in_cart'             => true,
                ],
                '`id_customization` = '.(int) $idCustomization
            );
        }
        // refresh cache of static::_products
        $this->_products = $this->getProducts(true);
        $this->update();

        return true;
    }

    /**
     * Return custom pictures in this cart for a specified product
     *
     * @param int  $idProduct
     * @param int  $type      only return customization of this type
     * @param bool $notInCart only return customizations that are not in cart already
     *
     * @return array result rows
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductCustomization($idProduct, $type = null, $notInCart = false)
    {
        if (!Customization::isFeatureActive()) {
            return [];
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cu.`id_customization`, cd.`index`, cd.`value`, cd.`type`, cu.`in_cart`, cu.`quantity`')
                ->from('customization', 'cu')
                ->leftJoin('customized_data', 'cd', 'cu.`id_customization` = cd.`id_customization`')
                ->where('cu.`id_cart` = '.(int) $this->id)
                ->where('cu.`id_product` = '.(int) $idProduct)
                ->where($type === Product::CUSTOMIZE_FILE ? 'cd.`type` = '.(int) Product::CUSTOMIZE_FILE : '')
                ->where($type === Product::CUSTOMIZE_TEXTFIELD ? 'cd.`type` = '.(int) Product::CUSTOMIZE_TEXTFIELD : '')
                ->where($notInCart ? 'cu.`in_cart` = 0' : '')
        );

        return $result;
    }

    /**
     * @deprecated 1.0.0, use Cart->removeCartRule()
     *
     * @param int $idCartRule
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteDiscount($idCartRule)
    {
        Tools::displayAsDeprecated();

        return $this->removeCartRule($idCartRule);
    }

    /**
     * @param int $idCartRule
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function removeCartRule($idCartRule)
    {
        Cache::clean('static::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_ALL);
        Cache::clean('static::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_SHIPPING);
        Cache::clean('static::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_REDUCTION);
        Cache::clean('static::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_GIFT);

        Cache::clean('static::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_ALL.'-ids');
        Cache::clean('static::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_SHIPPING.'-ids');
        Cache::clean('static::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_REDUCTION.'-ids');
        Cache::clean('static::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_GIFT.'-ids');

        $result = Db::getInstance()->delete('cart_cart_rule', '`id_cart_rule` = '.(int) $idCartRule.' AND `id_cart` = '.(int) $this->id, 1);

        $cartRule = new CartRule($idCartRule, Configuration::get('PS_LANG_DEFAULT'));
        if ((int) $cartRule->gift_product) {
            $this->updateQty(1, $cartRule->gift_product, $cartRule->gift_product_attribute, null, 'down', 0, null, false);
        }

        return $result;
    }

    /**
     * Get the number of packages
     *
     * @return int number of packages
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getNbOfPackages()
    {
        static $nbPackages = [];

        if (!isset($nbPackages[$this->id])) {
            $nbPackages[$this->id] = 0;
            foreach ($this->getPackageList() as $byAddress) {
                $nbPackages[$this->id] += count($byAddress);
            }
        }

        return $nbPackages[$this->id];
    }

    /**
     * @param array    $package
     * @param int|null $idCarrier
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getPackageIdWarehouse($package, $idCarrier = null)
    {
        if ($idCarrier === null) {
            if (isset($package['id_carrier'])) {
                $idCarrier = (int) $package['id_carrier'];
            }
        }

        if ($idCarrier == null) {
            return $package['id_warehouse'];
        }

        foreach ($package['warehouse_list'] as $idWarehouse) {
            $warehouse = new Warehouse((int) $idWarehouse);
            $availableWarehouseCarriers = $warehouse->getCarriers();
            if (in_array($idCarrier, $availableWarehouseCarriers)) {
                return (int) $idWarehouse;
            }
        }

        return 0;
    }

    /**
     * @param int $idCarrier
     * @param int $idAddress
     *
     * @return bool
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function carrierIsSelected($idCarrier, $idAddress)
    {
        $deliveryOption = $this->getDeliveryOption();
        $deliveryOptionList = $this->getDeliveryOptionList();

        if (!isset($deliveryOption[$idAddress])) {
            return false;
        }

        if (!isset($deliveryOptionList[$idAddress][$deliveryOption[$idAddress]])) {
            return false;
        }

        if (!in_array($idCarrier, array_keys($deliveryOptionList[$idAddress][$deliveryOption[$idAddress]]['carrier_list']))) {
            return false;
        }

        return true;
    }

    /**
     * Get all deliveries options available for the current cart formated like Carriers::getCarriersForOrder
     * This method was wrote for retrocompatibility with 1.4 theme
     * New theme need to use static::getDeliveryOptionList() to generate carriers option in the checkout process
     *
     * @param Country $defaultCountry
     * @param bool    $flush Force flushing cache
     *
     * @return array
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function simulateCarriersOutput(Country $defaultCountry = null, $flush = false)
    {
        $deliveryOptionList = $this->getDeliveryOptionList($defaultCountry, $flush);

        // This method cannot work if there is multiple address delivery
        if (count($deliveryOptionList) > 1 || empty($deliveryOptionList)) {
            return [];
        }

        $carriers = [];
        foreach (reset($deliveryOptionList) as $key => $option) {
            $price = $option['total_price_with_tax'];
            $priceTaxExcluded = $option['total_price_without_tax'];
            $name = $img = $delay = '';

            if ($option['unique_carrier']) {
                $carrier = reset($option['carrier_list']);
                if (isset($carrier['instance'])) {
                    $name = $carrier['instance']->name;
                    $delay = $carrier['instance']->delay;
                    $delay = isset($delay[Context::getContext()->language->id]) ?
                        $delay[Context::getContext()->language->id] : $delay[(int) Configuration::get('PS_LANG_DEFAULT')];
                }
                if (isset($carrier['logo'])) {
                    $img = $carrier['logo'];
                }
            } else {
                $nameList = [];
                foreach ($option['carrier_list'] as $carrier) {
                    $nameList[] = $carrier['instance']->name;
                }
                $name = join(' -', $nameList);
                $img = ''; // No images if multiple carriers
                $delay = '';
            }
            $carriers[] = [
                'name'          => $name,
                'img'           => $img,
                'delay'         => $delay,
                'price'         => $price,
                'price_tax_exc' => $priceTaxExcluded,
                'id_carrier'    => static::intifier($key), // Need to translate to an integer for retrocompatibility reason, in 1.4 template we used intval
                'is_module'     => false,
            ];
        }

        return $carriers;
    }

    /**
     * Translate a string option_delivery identifier ('24,3,') in a int (3240002000)
     *
     * The  option_delivery identifier is a list of integers separated by a ','.
     * This method replace the delimiter by a sequence of '0'.
     * The size of this sequence is fixed by the first digit of the return
     *
     * @param string $string
     * @param string $delimiter
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function intifier($string, $delimiter = ',')
    {
        $elm = explode($delimiter, $string);
        $max = max($elm);

        return strlen($max).implode(str_repeat('0', strlen($max) + 1), $elm);
    }

    /**
     * @param bool $useCache
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function simulateCarrierSelectedOutput($useCache = true)
    {
        $deliveryOption = $this->getDeliveryOption(null, false, $useCache);

        if (count($deliveryOption) > 1 || empty($deliveryOption)) {
            return 0;
        }

        return static::intifier(reset($deliveryOption));
    }

    /**
     * Return shipping total of a specific carriers for the cart
     *
     * @param int          $idCarrier
     * @param bool         $useTax
     * @param Country|null $defaultCountry
     * @param array|null   $deliveryOption Array of the delivery option for each address
     *
     * @return float Shipping total
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getCarrierCost($idCarrier, $useTax = true, Country $defaultCountry = null, $deliveryOption = null)
    {
        if (is_null($deliveryOption)) {
            $deliveryOption = $this->getDeliveryOption($defaultCountry);
        }

        $totalShipping = 0;
        $deliveryOptionList = $this->getDeliveryOptionList();

        foreach ($deliveryOption as $idAddress => $key) {
            if (!isset($deliveryOptionList[$idAddress]) || !isset($deliveryOptionList[$idAddress][$key])) {
                continue;
            }
            if (isset($deliveryOptionList[$idAddress][$key]['carrier_list'][$idCarrier])) {
                if ($useTax) {
                    $totalShipping += $deliveryOptionList[$idAddress][$key]['carrier_list'][$idCarrier]['price_with_tax'];
                } else {
                    $totalShipping += $deliveryOptionList[$idAddress][$key]['carrier_list'][$idCarrier]['price_without_tax'];
                }
            }
        }

        return $totalShipping;
    }

    /**
     * @deprecated 1.0.0, use static::getPackageShippingCost
     *
     * @param int|null     $idCarrier
     * @param bool         $useTax
     * @param Country|null $defaultCountry
     * @param array|null   $productList
     *
     * @return bool|float
     * @throws Adapter_Exception
     * @throws PrestaShopException
     */
    public function getOrderShippingCost($idCarrier = null, $useTax = true, Country $defaultCountry = null, $productList = null)
    {
        Tools::displayAsDeprecated();

        return $this->getPackageShippingCost((int) $idCarrier, $useTax, $defaultCountry, $productList);
    }

    /**
     * @deprecated 1.0.0
     *
     * @param CartRule $obj
     * @param mixed    $discounts
     * @param mixed    $orderTotal
     * @param mixed    $products
     * @param bool     $checkCartDiscount
     *
     * @return bool|string
     * @throws PrestaShopException
     */
    public function checkDiscountValidity($obj, $discounts, $orderTotal, $products, $checkCartDiscount = false)
    {
        Tools::displayAsDeprecated();
        $context = Context::getContext()->cloneContext();
        $context->cart = $this;

        return $obj->checkValidity($context);
    }

    /**
     * Return useful informations for cart
     *
     * @param int|null $idLang
     * @param bool     $refresh
     *
     * @return array Cart details
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getSummaryDetails($idLang = null, $refresh = false)
    {
        $context = Context::getContext();
        if (!$idLang) {
            $idLang = $context->language->id;
        }

        $delivery = new Address((int) $this->id_address_delivery);
        $invoice = new Address((int) $this->id_address_invoice);

        // New layout system with personalization fields
        $formattedAddresses = [
            'delivery' => AddressFormat::getFormattedLayoutData($delivery),
            'invoice'  => AddressFormat::getFormattedLayoutData($invoice),
        ];

        $baseTotalTaxInc = $this->getOrderTotal(true);
        $baseTotalTaxExc = $this->getOrderTotal(false);

        $totalTax = $baseTotalTaxInc - $baseTotalTaxExc;

        if ($totalTax < 0) {
            $totalTax = 0;
        }

        $currency = new Currency($this->id_currency);

        $products = $this->getProducts($refresh);

        foreach ($products as $key => &$product) {
            $product['price_without_quantity_discount'] = Product::getPriceStatic(
                $product['id_product'],
                !Product::getTaxCalculationMethod(),
                $product['id_product_attribute'],
                6,
                null,
                false,
                false
            );

            if ($product['reduction_type'] == 'amount') {
                $reduction = (!Product::getTaxCalculationMethod() ? (float) $product['price_wt'] : (float) $product['price']) - (float) $product['price_without_quantity_discount'];
                $product['reduction_formatted'] = Tools::displayPrice($reduction);
            }
        }

        $giftProducts = [];
        $cartRules = $this->getCartRules();
        $totalShipping = $this->getTotalShippingCost();
        $totalShippingTaxExc = $this->getTotalShippingCost(null, false);
        $totalProductsWt = $this->getOrderTotal(true, static::ONLY_PRODUCTS);
        $totalProducts = $this->getOrderTotal(false, static::ONLY_PRODUCTS);
        $totalDiscounts = $this->getOrderTotal(true, static::ONLY_DISCOUNTS);
        $totalDiscountsTaxExc = $this->getOrderTotal(false, static::ONLY_DISCOUNTS);

        // The cart content is altered for display
        foreach ($cartRules as &$cartRule) {
            // If the cart rule is automatic (wihtout any code) and include free shipping, it should not be displayed as a cart rule but only set the shipping cost to 0
            if ($cartRule['free_shipping'] && (empty($cartRule['code']) || preg_match('/^'.CartRule::BO_ORDER_CODE_PREFIX.'[0-9]+/', $cartRule['code']))) {
                $cartRule['value_real'] -= $totalShipping;
                $cartRule['value_tax_exc'] -= $totalShippingTaxExc;
                $cartRule['value_real'] = Tools::ps_round($cartRule['value_real'], (int) $context->currency->decimals * _TB_PRICE_DATABASE_PRECISION_);
                $cartRule['value_tax_exc'] = Tools::ps_round($cartRule['value_tax_exc'], (int) $context->currency->decimals * _TB_PRICE_DATABASE_PRECISION_);
                if ($totalDiscounts > $cartRule['value_real']) {
                    $totalDiscounts -= $totalShipping;
                }
                if ($totalDiscountsTaxExc > $cartRule['value_tax_exc']) {
                    $totalDiscountsTaxExc -= $totalShippingTaxExc;
                }

                // Update total shipping
                $totalShipping = 0;
                $totalShippingTaxExc = 0;
            }

            if ($cartRule['gift_product']) {
                foreach ($products as $key => &$product) {
                    if (empty($product['gift']) && $product['id_product'] == $cartRule['gift_product'] && $product['id_product_attribute'] == $cartRule['gift_product_attribute']) {
                        // Update total products
                        $totalProductsWt = Tools::ps_round($totalProductsWt - $product['price_wt'], (int) $context->currency->decimals * _TB_PRICE_DATABASE_PRECISION_);
                        $totalProducts = Tools::ps_round($totalProducts - $product['price'], (int) $context->currency->decimals * _TB_PRICE_DATABASE_PRECISION_);

                        // Update total discounts
                        $totalDiscounts = $totalDiscounts - $product['price_wt'];
                        $totalDiscountsTaxExc = $totalDiscountsTaxExc - $product['price'];

                        // Update cart rule value
                        $cartRule['value_real'] = Tools::ps_round($cartRule['value_real'] - $product['price_wt'], (int) $context->currency->decimals * _TB_PRICE_DATABASE_PRECISION_);
                        $cartRule['value_tax_exc'] = Tools::ps_round($cartRule['value_tax_exc'] - $product['price'], (int) $context->currency->decimals * _TB_PRICE_DATABASE_PRECISION_);

                        // Update product quantity
                        $product['total_wt'] = Tools::ps_round($product['total_wt'] - $product['price_wt'], (int) $currency->decimals * _TB_PRICE_DATABASE_PRECISION_);
                        $product['total'] = Tools::ps_round($product['total'] - $product['price'], (int) $currency->decimals * _TB_PRICE_DATABASE_PRECISION_);
                        $product['cart_quantity']--;

                        if (!$product['cart_quantity']) {
                            unset($products[$key]);
                        }

                        // Add a new product line
                        $giftProduct = $product;
                        $giftProduct['cart_quantity'] = 1;
                        $giftProduct['price'] = 0;
                        $giftProduct['price_wt'] = 0;
                        $giftProduct['total_wt'] = 0;
                        $giftProduct['total'] = 0;
                        $giftProduct['gift'] = true;
                        $giftProducts[] = $giftProduct;

                        break; // One gift product per cart rule
                    }
                }
            }
        }

        foreach ($cartRules as $key => &$cartRule) {
            if (((float) $cartRule['value_real'] == 0 && (int) $cartRule['free_shipping'] == 0)) {
                unset($cartRules[$key]);
            }
        }

        $summary = [
            'delivery'                  => $delivery,
            'delivery_state'            => State::getNameById($delivery->id_state),
            'invoice'                   => $invoice,
            'invoice_state'             => State::getNameById($invoice->id_state),
            'formattedAddresses'        => $formattedAddresses,
            'products'                  => array_values($products),
            'gift_products'             => $giftProducts,
            'discounts'                 => array_values($cartRules),
            'is_virtual_cart'           => (int) $this->isVirtualCart(),
            'total_discounts'           => $totalDiscounts,
            'total_discounts_tax_exc'   => $totalDiscountsTaxExc,
            'total_wrapping'            => $this->getOrderTotal(true, static::ONLY_WRAPPING),
            'total_wrapping_tax_exc'    => $this->getOrderTotal(false, static::ONLY_WRAPPING),
            'total_shipping'            => $totalShipping,
            'total_shipping_tax_exc'    => $totalShippingTaxExc,
            'total_products_wt'         => $totalProductsWt,
            'total_products'            => $totalProducts,
            'total_price'               => $baseTotalTaxInc,
            'total_tax'                 => $totalTax,
            'total_price_without_tax'   => $baseTotalTaxExc,
            'is_multi_address_delivery' => $this->isMultiAddressDelivery() || ((int) Tools::getValue('multi-shipping') == 1),
            'free_ship'                 => !$totalShipping && !count($this->getDeliveryAddressesWithoutCarriers(true, $errors)),
            'carrier'                   => new Carrier($this->id_carrier, $idLang),
        ];

        $hook = Hook::exec('actionCartSummary', $summary, null, true);
        if (is_array($hook)) {
            $summary = array_merge($summary, array_shift($hook));
        }

        return $summary;
    }

    /**
     * Get all the ids of the delivery addresses without carriers
     *
     * @param bool  $returnCollection Return a collection
     * @param array $error            contains an error message if an error occurs
     *
     * @return array Array of address id or of address object
     * @throws PrestaShopException
     */
    public function getDeliveryAddressesWithoutCarriers($returnCollection = false, &$error = [])
    {
        $addressesWithoutCarriers = [];
        foreach ($this->getProducts() as $product) {
            if (!in_array($product['id_address_delivery'], $addressesWithoutCarriers)
                && !count(Carrier::getAvailableCarrierList(new Product($product['id_product']), null, $product['id_address_delivery'], null, null, $error))
            ) {
                $addressesWithoutCarriers[] = $product['id_address_delivery'];
            }
        }
        if (!$returnCollection) {
            return $addressesWithoutCarriers;
        } else {
            $addressesInstanceWithoutCarriers = [];
            foreach ($addressesWithoutCarriers as $idAddress) {
                $addressesInstanceWithoutCarriers[] = new Address($idAddress);
            }

            return $addressesInstanceWithoutCarriers;
        }
    }

    /**
     * @param bool $returnProduct
     *
     * @return bool|mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function checkQuantities($returnProduct = false)
    {
        if (Configuration::get('PS_CATALOG_MODE') && !defined('_PS_ADMIN_DIR_')) {
            return false;
        }

        foreach ($this->getProducts() as $product) {
            if (!$this->allow_seperated_package && !$product['allow_oosp'] && StockAvailable::dependsOnStock($product['id_product']) &&
                $product['advanced_stock_management'] && (bool) Context::getContext()->customer->isLogged() && ($delivery = $this->getDeliveryOption()) && !empty($delivery)
            ) {
                $product['stock_quantity'] = StockManager::getStockByCarrier((int) $product['id_product'], (int) $product['id_product_attribute'], $delivery);
            }
            if (!$product['active'] || !$product['available_for_order']
                || (!$product['allow_oosp'] && $product['stock_quantity'] < $product['cart_quantity'])
            ) {
                return $returnProduct ? $product : false;
            }
        }

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function checkProductsAccess()
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return true;
        }

        foreach ($this->getProducts() as $product) {
            if (!Product::checkAccessStatic($product['id_product'], $this->id_customer)) {
                return $product['id_product'];
            }
        }

        return false;
    }

    /**
     * Add customer's text
     *
     * @param int    $idProduct
     * @param int    $index
     * @param int    $type
     * @param string $textValue
     *
     * @return bool Always true
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public function addTextFieldToProduct($idProduct, $index, $type, $textValue)
    {
        return $this->_addCustomization($idProduct, 0, $index, $type, $textValue, 0);
    }

    /**
     * Add customization item to database
     *
     * @param int    $idProduct
     * @param int    $idProductAttribute
     * @param int    $index
     * @param int    $type
     * @param string $field
     * @param int    $quantity
     *
     * @return bool success
     *
     * @since   1.0.0
     * @version 1.0.0
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    // @codingStandardsIgnoreStart
    public function _addCustomization($idProduct, $idProductAttribute, $index, $type, $field, $quantity)
    {
        // @codingStandardsIgnoreEnd
        $exisingCustomization = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cu.`id_customization`, cd.`index`, cd.`value`, cd.`type`')
                ->from('customization', 'cu')
                ->leftJoin('customized_data', 'cd', 'cu.`id_customization` = cd.`id_customization`')
                ->where('cu.`id_cart` = '.(int) $this->id)
                ->where('cu.`id_product` = '.(int) $idProduct)
                ->where('`in_cart` = 0')
        );

        if ($exisingCustomization) {
            // If the customization field is alreay filled, delete it
            foreach ($exisingCustomization as $customization) {
                if ($customization['type'] == $type && $customization['index'] == $index) {
                    Db::getInstance()->delete(
                        'customized_data',
                        'WHERE id_customization = '.(int) $customization['id_customization'].' AND type = '.(int) $customization['type'].' AND `index` = '.(int) $customization['index']

                    );
                    if ($type == Product::CUSTOMIZE_FILE) {
                        @unlink(_PS_UPLOAD_DIR_.$customization['value']);
                        @unlink(_PS_UPLOAD_DIR_.$customization['value'].'_small');
                    }
                    break;
                }
            }
            $idCustomization = $exisingCustomization[0]['id_customization'];
        } else {
            Db::getInstance()->insert(
                'customization',
                [
                    'id_cart'              => (int) $this->id,
                    'id_product'           => (int) $idProduct,
                    'id_product_attribute' => (int) $idProductAttribute,
                    'quantity'             => (int) $quantity,
                ]
            );
            $idCustomization = Db::getInstance()->Insert_ID();
        }

        if (!Db::getInstance()->insert(
            'customized_data',
            [
                'id_customization' => (int) $idCustomization,
                'type'             => (int) $type,
                'index'            => (int) $index,
                'value'            => pSQL($field),
            ]
        )) {
            return false;
        }

        return true;
    }

    /**
     * Add customer's pictures
     *
     * @param int    $idProduct
     * @param int    $index
     * @param int    $type
     * @param string $file
     *
     * @return bool Always true
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0
     */
    public function addPictureToProduct($idProduct, $index, $type, $file)
    {
        return $this->_addCustomization($idProduct, 0, $index, $type, $file, 0);
    }

    /**
     * @deprecated 1.0.0
     *
     * @param int $idProduct
     * @param int $index
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function deletePictureToProduct($idProduct, $index)
    {
        Tools::displayAsDeprecated();

        return $this->deleteCustomizationToProduct($idProduct, 0);
    }

    /**
     * Remove a customer's customization
     *
     * @param int $idProduct
     * @param int $index
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteCustomizationToProduct($idProduct, $index)
    {
        $result = true;

        $custData = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('cu.`id_customization`, cd.`index`, cd.`value`, cd.`type`')
                ->from('customization', 'cu')
                ->leftJoin('customized_data', 'cd', 'cu.`id_customization` = cd.`id_customization`')
                ->where('cu.`id_cart` = '.(int) $this->id)
                ->where('cu.`id_product` = '.(int) $idProduct)
                ->where('`index` = '.(int) $index)
                ->where('`in_cart` = 0')
        );

        // Delete customization picture if necessary
        if ($custData['type'] == 0) {
            $result &= (@unlink(_PS_UPLOAD_DIR_.$custData['value']) && @unlink(_PS_UPLOAD_DIR_.$custData['value'].'_small'));
        }

        $result &= Db::getInstance()->delete('customized_data', '`id_customization` = '.(int) $custData['id_customization'].' AND `index` = '.(int) $index);

        return $result;
    }

    /**
     * @return false|array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0
     */
    public function duplicate()
    {
        if (!Validate::isLoadedObject($this)) {
            return false;
        }

        $cart = new Cart($this->id);
        $cart->id = null;
        $cart->id_shop = $this->id_shop;
        $cart->id_shop_group = $this->id_shop_group;

        if (!Customer::customerHasAddress((int) $cart->id_customer, (int) $cart->id_address_delivery)) {
            $cart->id_address_delivery = (int) Address::getFirstCustomerAddressId((int) $cart->id_customer);
        }

        if (!Customer::customerHasAddress((int) $cart->id_customer, (int) $cart->id_address_invoice)) {
            $cart->id_address_invoice = (int) Address::getFirstCustomerAddressId((int) $cart->id_customer);
        }

        if ($cart->id_customer) {
            $cart->secure_key = static::$_customer->secure_key;
        }

        $cart->add();

        if (!Validate::isLoadedObject($cart)) {
            return false;
        }

        $success = true;
        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('cart_product')
                ->where('`id_cart` = '.(int) $this->id)
        );

        $productGift = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cr.`gift_product`, cr.`gift_product_attribute`')
                ->from('cart_rule', 'cr')
                ->leftJoin('order_cart_rule', 'ocr', 'ocr.`id_cart_rule` = cr.`id_cart_rule`')
                ->where('ocr.`id_order` = '.(int) $this->id)
        );

        $idAddressDelivery = Configuration::get('PS_ALLOW_MULTISHIPPING') ? $cart->id_address_delivery : 0;

        foreach ($products as $product) {
            if ($idAddressDelivery) {
                if (Customer::customerHasAddress((int) $cart->id_customer, $product['id_address_delivery'])) {
                    $idAddressDelivery = $product['id_address_delivery'];
                }
            }

            foreach ($productGift as $gift) {
                if (isset($gift['gift_product']) && isset($gift['gift_product_attribute']) && (int) $gift['gift_product'] == (int) $product['id_product'] && (int) $gift['gift_product_attribute'] == (int) $product['id_product_attribute']) {
                    $product['quantity'] = (int) $product['quantity'] - 1;
                }
            }

            $success &= $cart->updateQty(
                (int) $product['quantity'],
                (int) $product['id_product'],
                (int) $product['id_product_attribute'],
                null,
                'up',
                (int) $idAddressDelivery,
                new Shop((int) $cart->id_shop),
                false
            );
        }

        // Customized products
        $customs = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('customization', 'c')
                ->leftJoin('customized_data', 'cd', 'cd.`id_customization` = c.`id_customization`')
                ->where('c.`id_cart` = '.(int) $this->id)
        );

        // Get datas from customization table
        $customsById = [];
        foreach ($customs as $custom) {
            if (!isset($customsById[$custom['id_customization']])) {
                $customsById[$custom['id_customization']] = [
                    'id_product_attribute' => $custom['id_product_attribute'],
                    'id_product'           => $custom['id_product'],
                    'quantity'             => $custom['quantity'],
                ];
            }
        }

        // Insert new customizations
        $customIds = [];
        foreach ($customsById as $customizationId => $val) {
            Db::getInstance()->insert(
                'customization',
                [
                    'id_cart'              => (int) $cart->id,
                    'id_product_attribute' => (int) $val['id_product_attribute'],
                    'id_product'           => (int) $val['id_product'],
                    'id_address_delivery'  => (int) $idAddressDelivery,
                    'quantity'             => (int) $val['quantity'],
                    'quantity_refunded'    => 0,
                    'quantity_returned'    => 0,
                    'in_cart'              => 1,
                ]
            );
            $customIds[$customizationId] = Db::getInstance(_PS_USE_SQL_SLAVE_)->Insert_ID();
        }

        // Insert customized_data
        if (count($customs)) {
            $insert = [];
            foreach ($customs as $custom) {
                $customizedValue = $custom['value'];

                if ((int) $custom['type'] == 0) {
                    $customizedValue = md5(uniqid(rand(), true));
                    Tools::copy(_PS_UPLOAD_DIR_.$custom['value'], _PS_UPLOAD_DIR_.$customizedValue);
                    Tools::copy(_PS_UPLOAD_DIR_.$custom['value'].'_small', _PS_UPLOAD_DIR_.$customizedValue.'_small');
                }

                $insert[] = [
                    'id_customization' => (int) $customIds[$custom['id_customization']],
                    'type'             => (int) $custom['type'],
                    'index'            => (int) $custom['index'],
                    'value'            => pSQL($customizedValue),
                ];
            }
            Db::getInstance()->insert('customized_data', $insert);
        }

        return ['cart' => $cart, 'success' => $success];
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (!$this->id_lang) {
            $this->id_lang = Configuration::get('PS_LANG_DEFAULT');
        }
        if (!$this->id_shop) {
            $this->id_shop = Context::getContext()->shop->id;
        }

        $return = parent::add($autoDate, $nullValues);
        Hook::exec('actionCartSave', ['cart' => $this]);

        return $return;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0
     */
    public function getWsCartRows()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_product`, `id_product_attribute`, `quantity`, `id_address_delivery`')
                ->from('cart_product')
                ->where('`id_cart` = '.(int) $this->id)
                ->where('`id_shop` = '.(int) Context::getContext()->shop->id)
        );
    }

    /**
     * @param array $values
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0
     */
    public function setWsCartRows($values)
    {
        if ($this->deleteAssociations()) {
            $insert = [];
            foreach ($values as $value) {
                $insert[] = [
                    'id_cart'              => (int) $this->id,
                    'id_product'           => (int) $value['id_product'],
                    'id_product_attribute' => isset($value['id_product_attribute']) ? (int) $value['id_product_attribute'] : null,
                    'id_address_delivery'  => isset($value['id_address_delivery']) ? (int) $value['id_address_delivery'] : 0,
                    'quantity'             => (int) $value['quantity'],
                    'date_add'             => ['type' => 'sql', 'value' => 'NOW()'],
                    'id_shop'              => (int) Context::getContext()->shop->id,
                ];
            }

            Db::getInstance()->insert('cart_product', $insert);
        }

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0
     * @throws PrestaShopDatabaseException
     */
    public function deleteAssociations()
    {
        return (bool) Db::getInstance()->delete('cart_product', '`id_cart` = '.(int) $this->id);
    }

    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $oldIdAddressDelivery
     * @param int $newIdAddressDelivery
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0
     */
    public function setProductAddressDelivery($idProduct, $idProductAttribute, $oldIdAddressDelivery, $newIdAddressDelivery)
    {
        // Check address is linked with the customer
        if (!Customer::customerHasAddress(Context::getContext()->customer->id, $newIdAddressDelivery)) {
            return false;
        }

        if ($newIdAddressDelivery == $oldIdAddressDelivery) {
            return false;
        }

        // Checking if the product with the old address delivery exists
        $sql = new DbQuery();
        $sql->select('count(*)');
        $sql->from('cart_product', 'cp');
        $sql->where('id_product = '.(int) $idProduct);
        $sql->where('id_product_attribute = '.(int) $idProductAttribute);
        $sql->where('id_address_delivery = '.(int) $oldIdAddressDelivery);
        $sql->where('id_cart = '.(int) $this->id);
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

        if ($result == 0) {
            return false;
        }

        // Checking if there is no others similar products with this new address delivery
        $sql = new DbQuery();
        $sql->select('sum(quantity) as qty');
        $sql->from('cart_product', 'cp');
        $sql->where('id_product = '.(int) $idProduct);
        $sql->where('id_product_attribute = '.(int) $idProductAttribute);
        $sql->where('id_address_delivery = '.(int) $newIdAddressDelivery);
        $sql->where('id_cart = '.(int) $this->id);
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

        // Removing similar products with this new address delivery
        Db::getInstance()->delete(
            'cart_product',
            'id_product = '.(int) $idProduct.' AND id_product_attribute = '.(int) $idProductAttribute.' AND id_address_delivery = '.(int) $newIdAddressDelivery.' AND id_cart = '.(int) $this->id,
            1
        );

        // Changing the address
        Db::getInstance()->update(
            'cart_product',
            [
                'id_address_delivery' => (int) $newIdAddressDelivery,
                'quantity' => ['type' => 'sql', 'value' => '`quantity` + '.(int) $result],
            ],
            '`id_product` = '.(int) $idProduct.' AND `id_product_attribute` = '.(int) $idProductAttribute.' AND `id_address_delivery` = '.(int) $oldIdAddressDelivery.' AND `id_cart` = '.(int) $this->id,
            1
        );

        return true;
    }

    /**
     * @param int  $idProduct
     * @param int  $idProductAttribute
     * @param int  $idAddressDelivery
     * @param int  $newIdAddressDelivery
     * @param int  $quantity
     * @param bool $keepQuantity
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0
     */
    public function duplicateProduct(
        $idProduct,
        $idProductAttribute,
        $idAddressDelivery,
        $newIdAddressDelivery,
        $quantity = 1,
        $keepQuantity = false
    ) {
        // Check address is linked with the customer
        if (!Customer::customerHasAddress(Context::getContext()->customer->id, $newIdAddressDelivery)) {
            return false;
        }

        // Checking the product do not exist with the new address
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('cart_product', 'c')
                ->where('id_product = '.(int) $idProduct)
                ->where('`id_product_attribute` = '.(int) $idProductAttribute)
                ->where('`id_address_delivery` = '.(int) $newIdAddressDelivery)
                ->where('`id_cart` = '.(int) $this->id)
        );

        if ($result > 0) {
            return false;
        }

        Db::getInstance()->insert(
            'cart_product',
            [
                'id_cart'              => (int) $this->id,
                'id_product'           => (int) $idProduct,
                'id_shop'              => (int) $this->id_shop,
                'id_product_attribute' => (int) $idProductAttribute,
                'quantity'             => (int) $quantity,
                'date_add'             => ['type' => 'sql', 'value' => 'NOW()'],
                'id_address_delivery'  => (int) $newIdAddressDelivery,
            ]
        );

        if (!$keepQuantity) {
            $duplicatedQuantity = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('quantity')
                    ->from('cart_product', 'c')
                    ->where('id_product = '.(int) $idProduct)
                    ->where('id_product_attribute = '.(int) $idProductAttribute)
                    ->where('id_address_delivery = '.(int) $idAddressDelivery)
                    ->where('id_cart = '.(int) $this->id)
            );

            if ($duplicatedQuantity > $quantity) {
                Db::getInstance()->update(
                    'cart_product',
                    [
                        'quantity'             => ['type' => 'sql', 'value' => '`quantity - `'.(int) $quantity],
                        'id_product'           => (int) $idProduct,
                        'id_shop'              => (int) $this->id_shop,
                        'id_product_attribute' => (int) $idProductAttribute,
                        'id_address_delivery'  => (int) $idAddressDelivery,
                    ],
                    '`id_cart` ='.(int) $this->id
                );
            }
        }

        // Checking if there is customizations
        $results = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('*')
                ->from('customization', 'c')
                ->where('id_product = '.(int) $idProduct)
                ->where('id_product_attribute = '.(int) $idProductAttribute)
                ->where('id_address_delivery = '.(int) $idAddressDelivery)
                ->where('id_cart = '.(int) $this->id)
        );

        foreach ($results as $customization) {
            // Duplicate customization
            Db::getInstance()->insert(
                'customization',
                [
                    'id_product_attribute' => (int) $customization['id_product_attribute'],
                    'id_address_delivery'  => (int) $newIdAddressDelivery,
                    'id_cart'              => (int) $customization['id_cart'],
                    'id_product'           => (int) $customization['id_product'],
                    'quantity'             => (int) $quantity,
                    'in_cart'              => $customization['in_cart'],
                ]
            );

            // Save last insert ID before doing another query
            $lastId = (int) Db::getInstance()->Insert_ID();

            // Get data from duplicated customizations
            $lastRow = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('`type`, `index`, `value`')
                    ->from('customized_data')
                    ->where('id_customization = '.$customization['id_customization'])
            );

            // Insert new copied data with new customization ID into customized_data table
            $lastRow['id_customization'] = $lastId;
            Db::getInstance()->insert('customized_data', $lastRow);
        }

        $customizationCount = count($results);
        if ($customizationCount > 0) {
            Db::getInstance()->update(
                'cart_product',
                [
                    'quantity' => ['type' => 'sql', 'value' => '`quantity` + '.(int) $customizationCount * $quantity],
                ],
                'id_cart = '.(int) $this->id.' AND id_product = '.(int) $idProduct.' AND id_shop = '.(int) $this->id_shop.' AND id_product_attribute = '.(int) $idProductAttribute.' AND id_address_delivery = '.(int) $newIdAddressDelivery
            );
        }

        return true;
    }

    /**
     * Update products cart address delivery with the address delivery of the cart
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function setNoMultishipping()
    {
        $emptyCache = false;
        if (Configuration::get('PS_ALLOW_MULTISHIPPING')) {
            // Upgrading quantities
            $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('SUM(`quantity`) AS `quantity`, `id_product`, `id_product_attribute`, COUNT(*) AS `count`')
                    ->from('cart_product')
                    ->where('`id_cart` = '.(int) $this->id)
                    ->where('`id_shop` = '.(int) $this->id_shop)
                    ->groupBy('`id_product`, `id_product_attribute`')
                    ->having('`count` > 1')
            );

            if (is_array($products)) {
                foreach ($products as $product) {
                    if (Db::getInstance()->update(
                        'cart_product',
                        [
                            'quantity' => (int) $product['quantity'],
                        ],
                        '`id_cart` = '.(int) $this->id.' AND `id_shop` = '.(int) $this->id_shop.' AND id_product = '.$product['id_product'].' AND id_product_attribute = '.$product['id_product_attribute']
                    )) {
                        $emptyCache = true;
                    }
                }
            }

            // Merging multiple lines
            $sql = 'DELETE cp1
				FROM `'._DB_PREFIX_.'cart_product` cp1
					INNER JOIN `'._DB_PREFIX_.'cart_product` cp2
					ON (
						(cp1.id_cart = cp2.id_cart)
						AND (cp1.id_product = cp2.id_product)
						AND (cp1.id_product_attribute = cp2.id_product_attribute)
						AND (cp1.id_address_delivery <> cp2.id_address_delivery)
						AND (cp1.date_add > cp2.date_add)
					)';
            Db::getInstance()->execute($sql);
        }

        // Update delivery address for each product line
        $cacheId = 'static::setNoMultishipping'.(int) $this->id.'-'.(int) $this->id_shop.((isset($this->id_address_delivery) && $this->id_address_delivery) ? '-'.(int) $this->id_address_delivery : '');
        if (!Cache::isStored($cacheId)) {
            if ($result = (bool) Db::getInstance()->update(
                'cart_product',
                [
                    'id_address_delivery' => ['type' => 'sql', 'value' => '(SELECT `id_address_delivery` FROM `'._DB_PREFIX_.'cart` WHERE `id_cart` = '.(int) $this->id.' AND `id_shop` = '.(int) $this->id_shop.' LIMIT 1)'],
                ],
                '`id_cart` = '.(int) $this->id.' '.(Configuration::get('PS_ALLOW_MULTISHIPPING') ? ' AND `id_shop` = '.(int) $this->id_shop : '')
            )) {
                $emptyCache = true;
            }
            Cache::store($cacheId, $result);
        }

        if (Customization::isFeatureActive()) {
            Db::getInstance()->update(
                'customization',
                [
                    'id_address_delivery' => ['type' => 'sql', 'value' => '(SELECT `id_address_delivery` FROM `'._DB_PREFIX_.'cart` WHERE `id_cart` = '.(int) $this->id.' LIMIT 1)'],
                ],
                '`id_cart` = '.(int) $this->id
            );
        }

        if ($emptyCache) {
            $this->_products = null;
        }
    }

    /**
     * Set an address to all products on the cart without address delivery
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function autosetProductAddress()
    {
        // Get the main address of the customer
        if ((int) $this->id_address_delivery > 0) {
            $idAddressDelivery = (int) $this->id_address_delivery;
        } else {
            $idAddressDelivery = (int) Address::getFirstCustomerAddressId(Context::getContext()->customer->id);
        }

        if (!$idAddressDelivery) {
            return;
        }

        // Update
        Db::getInstance()->update(
            'cart_product',
            [
                'id_address_delivery' => (int) $idAddressDelivery,
            ],
            '`id_cart` = '.(int) $this->id.' AND (`id_address_delivery` = 0 OR `id_address_delivery` IS NULL) AND `id_shop` = '.(int) $this->id_shop
        );

        Db::getInstance()->update(
            'customization',
            [
                'id_address_delivery' => (int) $idAddressDelivery,
            ],
            '`id_cart` = '.(int) $this->id.' AND (`id_address_delivery` = 0 OR `id_address_delivery` IS NULL)'
        );
    }

    /**
     * @param bool $ignoreVirtual Ignore virtual product
     * @param bool $exclusive     If true, the validation is exclusive : it must be present product in stock and out of stock
     *
     * @return bool false is some products from the cart are out of stock
     *
     * @since   1.0.0
     * @version 1.0.0
     * @throws PrestaShopException
     */
    public function isAllProductsInStock($ignoreVirtual = false, $exclusive = false)
    {
        $productOutOfStock = 0;
        $productInStock = 0;
        foreach ($this->getProducts() as $product) {
            if (!$exclusive) {
                if (((int) $product['quantity_available'] - (int) $product['cart_quantity']) < 0
                    && (!$ignoreVirtual || !$product['is_virtual'])
                ) {
                    return false;
                }
            } else {
                if ((int) $product['quantity_available'] <= 0
                    && (!$ignoreVirtual || !$product['is_virtual'])
                ) {
                    $productOutOfStock++;
                }
                if ((int) $product['quantity_available'] > 0
                    && (!$ignoreVirtual || !$product['is_virtual'])
                ) {
                    $productInStock++;
                }

                if ($productInStock > 0 && $productOutOfStock > 0) {
                    return false;
                }
            }
        }

        return true;
    }
}
