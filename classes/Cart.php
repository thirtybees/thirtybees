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
    protected static $_nbProducts = [];
    protected static $_isVirtualCart = [];
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
    public $checkedTos = false;
    public $pictures;
    public $textFields;
    public $delivery_option;
    /** @var bool Allow to seperate order in multiple package in order to recieve as soon as possible the available products */
    public $allow_seperated_package = false;
    protected $_products = null;
    // @codingStandardsIgnoreEnd
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

    /**
     * CartCore constructor.
     *
     * @param null $id
     * @param null $idLang
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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

            self::$_customer = $customer;

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
     */
    public function setTaxCalculationMethod()
    {
        $this->_taxCalculationMethod = Group::getPriceDisplayMethod(Group::getCurrent()->id);
    }

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
        $sql->select(
            'cp.`id_product_attribute`, cp.`id_product`, cp.`quantity` AS cart_quantity, cp.id_shop, pl.`name`, p.`is_virtual`,
						pl.`description_short`, pl.`available_now`, pl.`available_later`, product_shop.`id_category_default`, p.`id_supplier`,
						p.`id_manufacturer`, product_shop.`on_sale`, product_shop.`ecotax`, product_shop.`additional_shipping_cost`,
						product_shop.`available_for_order`, product_shop.`price`, product_shop.`active`, product_shop.`unity`, product_shop.`unit_price_ratio`,
						stock.`quantity` AS quantity_available, p.`width`, p.`height`, p.`depth`, stock.`out_of_stock`, p.`weight`,
						p.`date_add`, p.`date_upd`, IFNULL(stock.quantity, 0) as quantity, pl.`link_rewrite`, cl.`link_rewrite` AS category,
						CONCAT(LPAD(cp.`id_product`, 10, 0), LPAD(IFNULL(cp.`id_product_attribute`, 0), 10, 0), IFNULL(cp.`id_address_delivery`, 0)) AS unique_id, cp.id_address_delivery,
						product_shop.advanced_stock_management, ps.product_supplier_reference supplier_reference'
        );

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
            $sql->select(
                '
				product_attribute_shop.`price` AS price_attribute, product_attribute_shop.`ecotax` AS ecotax_attr,
				IF (IFNULL(pa.`reference`, \'\') = \'\', p.`reference`, pa.`reference`) AS reference,
				(p.`weight`+ pa.`weight`) weight_attribute,
				IF (IFNULL(pa.`ean13`, \'\') = \'\', p.`ean13`, pa.`ean13`) AS ean13,
				IF (IFNULL(pa.`upc`, \'\') = \'\', p.`upc`, pa.`upc`) AS upc,
				IFNULL(product_attribute_shop.`minimal_quantity`, product_shop.`minimal_quantity`) as minimal_quantity,
				IF(product_attribute_shop.wholesale_price > 0,  product_attribute_shop.wholesale_price, product_shop.`wholesale_price`) wholesale_price
			'
            );

            $sql->leftJoin('product_attribute', 'pa', 'pa.`id_product_attribute` = cp.`id_product_attribute`');
            $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', '(product_attribute_shop.`id_shop` = cp.`id_shop` AND product_attribute_shop.`id_product_attribute` = pa.`id_product_attribute`)');
        } else {
            $sql->select(
                'p.`reference` AS reference, p.`ean13`,
				p.`upc` AS upc, product_shop.`minimal_quantity` AS minimal_quantity, product_shop.`wholesale_price` wholesale_price'
            );
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
        self::cacheSomeAttributesLists($paIds, $this->id_lang);

        $this->_products = [];
        if (empty($result)) {
            return [];
        }

//        $ecotax_rate = (float) Tax::getProductEcotaxRate($this->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
//        $apply_eco_tax = Product::$_taxCalculationMethod == PS_TAX_INC && (int) Configuration::get('PS_TAX');
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

//            $address = Address::initialize($addressId, true);
//            $idTaxRulesGroup = Product::getIdTaxRulesGroupByIdProduct((int) $row['id_product'], $cartShopContext);
//            $taxCalculator = TaxManagerFactory::getManager($address, $idTaxRulesGroup)->getTaxCalculator();

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
                    $row['total'] = Tools::ps_round($row['price_with_reduction_without_tax'] * (int) $row['cart_quantity'], _PS_PRICE_COMPUTE_PRECISION_);
                    $row['total_wt'] = Tools::ps_round($row['price_with_reduction'] * (int) $row['cart_quantity'], _PS_PRICE_COMPUTE_PRECISION_);
                    break;

                case Order::ROUND_ITEM:
                default:
                    $row['total'] = Tools::ps_round($row['price_with_reduction_without_tax'], _PS_PRICE_COMPUTE_PRECISION_) * (int) $row['cart_quantity'];
                    $row['total_wt'] = Tools::ps_round($row['price_with_reduction'], _PS_PRICE_COMPUTE_PRECISION_) * (int) $row['cart_quantity'];
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

            if (array_key_exists($row['id_product_attribute'].'-'.$this->id_lang, self::$_attributesLists)) {
                $row = array_merge($row, self::$_attributesLists[$row['id_product_attribute'].'-'.$this->id_lang]);
            }

            $row = Product::getTaxesInformations($row, $cartShopContext);

            $this->_products[] = $row;
        }

        return $this->_products;
    }

    /**
     * @param $ipaList
     * @param $idLang
     *
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
            if ((int) $idProductAttribute && !array_key_exists($idProductAttribute.'-'.$idLang, self::$_attributesLists)) {
                $paImplode[] = (int) $idProductAttribute;
                self::$_attributesLists[(int) $idProductAttribute.'-'.$idLang] = ['attributes' => '', 'attributes_small' => ''];
            }
        }

        if (!count($paImplode)) {
            return;
        }

        $result = Db::getInstance()->executeS(
            '
			SELECT pac.`id_product_attribute`, agl.`public_name` AS public_group_name, al.`name` AS attribute_name
			FROM `'._DB_PREFIX_.'product_attribute_combination` pac
			LEFT JOIN `'._DB_PREFIX_.'attribute` a ON a.`id_attribute` = pac.`id_attribute`
			LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
			LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (
				a.`id_attribute` = al.`id_attribute`
				AND al.`id_lang` = '.(int) $idLang.'
			)
			LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (
				ag.`id_attribute_group` = agl.`id_attribute_group`
				AND agl.`id_lang` = '.(int) $idLang.'
			)
			WHERE pac.`id_product_attribute` IN ('.implode(',', $paImplode).')
			ORDER BY ag.`position` ASC, a.`position` ASC'
        );

        foreach ($result as $row) {
            self::$_attributesLists[$row['id_product_attribute'].'-'.$idLang]['attributes'] .= $row['public_group_name'].' : '.$row['attribute_name'].', ';
            self::$_attributesLists[$row['id_product_attribute'].'-'.$idLang]['attributes_small'] .= $row['attribute_name'].', ';
        }

        foreach ($paImplode as $idProductAttribute) {
            self::$_attributesLists[$idProductAttribute.'-'.$idLang]['attributes'] = rtrim(
                self::$_attributesLists[$idProductAttribute.'-'.$idLang]['attributes'],
                ', '
            );

            self::$_attributesLists[$idProductAttribute.'-'.$idLang]['attributes_small'] = rtrim(
                self::$_attributesLists[$idProductAttribute.'-'.$idLang]['attributes_small'],
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
        return self::getTotalCart($idCart, true);
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
     * self::ONLY_PRODUCTS
     * self::ONLY_DISCOUNTS
     * self::BOTH
     * self::BOTH_WITHOUT_SHIPPING
     * self::ONLY_SHIPPING
     * self::ONLY_WRAPPING
     * self::ONLY_PRODUCTS_WITHOUT_SHIPPING
     * self::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING
     *
     * @param bool $withTaxes With or without taxes
     * @param int  $type      Total type
     * @param bool $useCache  Allow using cache of the method CartRule::getContextualValue
     *
     * @return float Order total
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getOrderTotal($withTaxes = true, $type = self::BOTH, $products = null, $idCarrier = null, $useCache = true)
    {
        // Dependencies
        $addressFactory = Adapter_ServiceLocator::get('Adapter_AddressFactory');
        $priceCalculator = Adapter_ServiceLocator::get('Adapter_ProductPriceCalculator');
        $configuration = Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');

        $psTaxAddressType = $configuration->get('PS_TAX_ADDRESS_TYPE');
        $psUseEcotax = $configuration->get('PS_USE_ECOTAX');
        $psRoundType = $configuration->get('PS_ROUND_TYPE');
        $psEcotaxTaxRulesGroupId = $configuration->get('PS_ECOTAX_TAX_RULES_GROUP_ID');
        $computePrecision = $configuration->get('_PS_PRICE_COMPUTE_PRECISION_');

        if (!$this->id) {
            return 0;
        }

        $type = (int) $type;
        $arrayType = [
            self::ONLY_PRODUCTS,
            self::ONLY_DISCOUNTS,
            self::BOTH,
            self::BOTH_WITHOUT_SHIPPING,
            self::ONLY_SHIPPING,
            self::ONLY_WRAPPING,
            self::ONLY_PRODUCTS_WITHOUT_SHIPPING,
            self::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING,
        ];

        // Define virtual context to prevent case where the cart is not the in the global context
        $virtualContext = Context::getContext()->cloneContext();
        $virtualContext->cart = $this;

        if (!in_array($type, $arrayType)) {
            die(Tools::displayError());
        }

        $withShipping = in_array($type, [self::BOTH, self::ONLY_SHIPPING]);

        // if cart rules are not used
        if ($type == self::ONLY_DISCOUNTS && !CartRule::isFeatureActive()) {
            return 0;
        }

        // no shipping cost if is a cart with only virtuals products
        $virtual = $this->isVirtualCart();
        if ($virtual && $type == self::ONLY_SHIPPING) {
            return 0;
        }

        if ($virtual && $type == self::BOTH) {
            $type = self::BOTH_WITHOUT_SHIPPING;
        }

        if ($withShipping || $type == self::ONLY_DISCOUNTS) {
            if (is_null($products) && is_null($idCarrier)) {
                $shippingFees = $this->getTotalShippingCost(null, (bool) $withTaxes);
            } else {
                $shippingFees = $this->getPackageShippingCost((int) $idCarrier, (bool) $withTaxes, null, $products);
            }
        } else {
            $shippingFees = 0;
        }

        if ($type == self::ONLY_SHIPPING) {
            return $shippingFees;
        }

        if ($type == self::ONLY_PRODUCTS_WITHOUT_SHIPPING) {
            $type = self::ONLY_PRODUCTS;
        }

        $paramProduct = true;
        if (is_null($products)) {
            $paramProduct = false;
            $products = $this->getProducts();
        }

        if ($type == self::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING) {
            foreach ($products as $key => $product) {
                if ($product['is_virtual']) {
                    unset($products[$key]);
                }
            }
            $type = self::ONLY_PRODUCTS;
        }

        $orderTotal = 0;
        if (Tax::excludeTaxeOption()) {
            $withTaxes = false;
        }

        $productsTotal = [];
        $ecotaxTotal = 0;

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
                6,
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

            $address = $addressFactory->findOrCreate($idAddress, true);

            if ($withTaxes) {
                $idTaxRulesGroup = Product::getIdTaxRulesGroupByIdProduct((int) $product['id_product'], $virtualContext);
                $taxCalculator = TaxManagerFactory::getManager($address, $idTaxRulesGroup)->getTaxCalculator();
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
                    $productPrice = $price * $product['cart_quantity'];
                    $productsTotal[$idTaxRulesGroup] += Tools::ps_round($productPrice, $computePrecision);
                    break;

                case Order::ROUND_ITEM:
                default:
                    $productPrice = /*$with_taxes ? $tax_calculator->addTaxes($price) : */
                        $price;
                    $productsTotal[$idTaxRulesGroup] += Tools::ps_round($productPrice, $computePrecision) * (int) $product['cart_quantity'];
                    break;
            }
        }

        foreach ($productsTotal as $key => $price) {
            $orderTotal += $price;
        }

        $orderTotalProducts = $orderTotal;

        if ($type == self::ONLY_DISCOUNTS) {
            $orderTotal = 0;
        }

        // Wrapping Fees
        $wrappingFees = 0;

        // With PS_ATCP_SHIPWRAP on the gift wrapping cost computation calls getOrderTotal with $type === self::ONLY_PRODUCTS, so the flag below prevents an infinite recursion.
        $includeGiftWrapping = (!$configuration->get('PS_ATCP_SHIPWRAP') || $type !== self::ONLY_PRODUCTS);

        if ($this->gift && $includeGiftWrapping) {
            $wrappingFees = Tools::convertPrice(Tools::ps_round($this->getGiftWrappingPrice($withTaxes), $computePrecision), Currency::getCurrencyInstance((int) $this->id_currency));
        }
        if ($type == self::ONLY_WRAPPING) {
            return $wrappingFees;
        }

        $orderTotalDiscount = 0;
        $orderShippingDiscount = 0;
        if (!in_array($type, [self::ONLY_SHIPPING, self::ONLY_PRODUCTS]) && CartRule::isFeatureActive()) {
            // First, retrieve the cart rules associated to this "getOrderTotal"
            if ($withShipping || $type == self::ONLY_DISCOUNTS) {
                $cartRules = $this->getCartRules(CartRule::FILTER_ACTION_ALL);
            } else {
                $cartRules = $this->getCartRules(CartRule::FILTER_ACTION_REDUCTION);
                // Cart Rules array are merged manually in order to avoid doubles
                foreach ($this->getCartRules(CartRule::FILTER_ACTION_GIFT) as $tmp_cart_rule) {
                    $flag = false;
                    foreach ($cartRules as $cartRule) {
                        if ($tmp_cart_rule['id_cart_rule'] == $cartRule['id_cart_rule']) {
                            $flag = true;
                        }
                    }
                    if (!$flag) {
                        $cartRules[] = $tmp_cart_rule;
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
                // If the cart rule offers free shipping, add the shipping cost
                if (($withShipping || $type == self::ONLY_DISCOUNTS) && $cartRule['obj']->free_shipping && !$flag) {
                    $orderShippingDiscount = (float) Tools::ps_round($cartRule['obj']->getContextualValue($withTaxes, $virtualContext, CartRule::FILTER_ACTION_SHIPPING, ($paramProduct ? $package : null), $useCache), $computePrecision);
                    $flag = true;
                }

                // If the cart rule is a free gift, then add the free gift value only if the gift is in this package
                if ((int) $cartRule['obj']->gift_product) {
                    $inOrder = false;
                    if (is_null($products)) {
                        $inOrder = true;
                    } else {
                        foreach ($products as $product) {
                            if ($cartRule['obj']->gift_product == $product['id_product'] && $cartRule['obj']->gift_product_attribute == $product['id_product_attribute']) {
                                $inOrder = true;
                            }
                        }
                    }

                    if ($inOrder) {
                        $orderTotalDiscount += $cartRule['obj']->getContextualValue($withTaxes, $virtualContext, CartRule::FILTER_ACTION_GIFT, $package, $useCache);
                    }
                }

                // If the cart rule offers a reduction, the amount is prorated (with the products in the package)
                if ($cartRule['obj']->reduction_percent > 0 || $cartRule['obj']->reduction_amount > 0) {
                    $orderTotalDiscount += Tools::ps_round($cartRule['obj']->getContextualValue($withTaxes, $virtualContext, CartRule::FILTER_ACTION_REDUCTION, $package, $useCache), $computePrecision);
                }
            }
            $orderTotalDiscount = min(Tools::ps_round($orderTotalDiscount, 2), (float) $orderTotalProducts) + (float) $orderShippingDiscount;
            $orderTotal -= $orderTotalDiscount;
        }

        if ($type == self::BOTH) {
            $orderTotal += $shippingFees + $wrappingFees;
        }

        if ($orderTotal < 0 && $type != self::ONLY_DISCOUNTS) {
            return 0;
        }

        if ($type == self::ONLY_DISCOUNTS) {
            return $orderTotalDiscount;
        }

        return Tools::ps_round((float) $orderTotal, $computePrecision);
    }

    /**
     * Check if cart contains only virtual products
     *
     * @return bool true if is a virtual cart or false
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function isVirtualCart($strict = false)
    {
        if (!ProductDownload::isFeatureActive()) {
            return false;
        }

        if (!isset(self::$_isVirtualCart[$this->id])) {
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
            self::$_isVirtualCart[$this->id] = (int) $isVirtual;
        }

        return self::$_isVirtualCart[$this->id];
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
            $deliveryOption = Tools::unSerialize($this->delivery_option);
            $validated = true;
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

        $this->delivery_option = serialize($deliveryOption);
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
            $result = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'cart_cart_rule WHERE id_cart = '.(int) $this->id);
        }

        $cartRulesInCart = [];

        if (is_array($result)) {
            foreach ($result as $row) {
                $cartRulesInCart[] = $row['id_cart_rule'];
            }
        }

        $totalProductsTaxIncluded = $this->getOrderTotal(true, self::ONLY_PRODUCTS);
        $totalProducts = $this->getOrderTotal(false, self::ONLY_PRODUCTS);

        $freeCarriersRules = [];

        $context = Context::getContext();
        foreach ($cartRules as $cartRule) {
            $totalPrice = $cartRule['minimum_amount_tax'] ? $totalProductsTaxIncluded : $totalProducts;
            $totalPrice += (isset($realBestPrice) && $cartRule['minimum_amount_tax'] && $cartRule['minimum_amount_shipping']) ? $realBestPrice : 0;
            $totalPrice += (isset($realBestPriceWt) && !$cartRule['minimum_amount_tax'] && $cartRule['minimum_amount_shipping']) ? $realBestPriceWt : 0;
            if ($cartRule['free_shipping'] && $cartRule['carrier_restriction']
                && in_array($cartRule['id_cart_rule'], $cartRulesInCart)
                && $cartRule['minimum_amount'] <= $totalPrice
            ) {
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
                    $product['carrier_list'] = Tools::array_replace($product['carrier_list'], Carrier::getAvailableCarrierList(new Product($product['id_product']), $idWar, $product['id_address_delivery'], null, $this));
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
     * @param int          $idCarrier       Carrier ID (default : current carrier)
     * @param bool         $useTax
     * @param Country|null $defaultCountry
     * @param array|null   $productList     List of product concerned by the shipping.
     *                                      If null, all the product of the cart are used to calculate the shipping cost
     * @param int|null     $idZone
     *
     * @return bool|float Shipping total
     *                    `false` if shipping is not possible
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
        } elseif (count($productList)) {
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

        $cacheId = 'getPackageShippingCost_'.(int) $this->id.'_'.(int) $addressId.'_'.(int) $idCarrier.'_'.(int) $useTax.'_'.(int) $defaultCountry->id;
        if ($products) {
            foreach ($products as $product) {
                $cacheId .= '_'.(int) $product['id_product'].'_'.(int) $product['id_product_attribute'];
            }
        }

        if (Cache::isStored($cacheId)) {
            return Cache::retrieve($cacheId);
        }

        // Order total in default currency without fees
        $orderTotal = $this->getOrderTotal(true, self::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING, $productList);

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

        $totalPackageWithoutShippingTaxInc = $this->getOrderTotal(true, self::BOTH_WITHOUT_SHIPPING, $productList);
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

                if (!isset(self::$_carriers[$row['id_carrier']])) {
                    self::$_carriers[$row['id_carrier']] = new Carrier((int) $row['id_carrier']);
                }

                /** @var Carrier $carrier */
                $carrier = self::$_carriers[$row['id_carrier']];

                $shippingMethod = $carrier->getShippingMethod();
                // Get only carriers that are compliant with shipping method
                if (($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT && $carrier->getMaxDeliveryPriceByWeight((int) $idZone) === false)
                    || ($shippingMethod == Carrier::SHIPPING_METHOD_PRICE && $carrier->getMaxDeliveryPriceByPrice((int) $idZone) === false)
                ) {
                    unset($result[$k]);
                    continue;
                }

                // If out-of-range behavior carrier is set on "Desactivate carrier"
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

        if (!isset(self::$_carriers[$idCarrier])) {
            self::$_carriers[$idCarrier] = new Carrier((int) $idCarrier, Configuration::get('PS_LANG_DEFAULT'));
        }

        $carrier = self::$_carriers[$idCarrier];

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
        $orderTotalwithDiscounts = $this->getOrderTotal(true, self::BOTH_WITHOUT_SHIPPING, null, null, false);
        if ($orderTotalwithDiscounts >= (float) ($freeFeesPrice) && (float) ($freeFeesPrice) > 0) {
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
            if (!$useTax) {
                // With PS_ATCP_SHIPWRAP, we deduce the pre-tax price from the post-tax
                // price. This is on purpose and required in Germany.
                $shippingCost /= (1 + $this->getAverageProductsTaxRate());
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
     * @id_carrier int
     * @id_zone    int
     *
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
                self::BOTH_WITHOUT_SHIPPING
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
     * @return float Cart weight
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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

        if (!isset(self::$_totalWeight[$this->id])) {
            if (Combination::isFeatureActive()) {
                $weightProductWithAttribute = Db::getInstance()->getValue(
                    '
				SELECT SUM((p.`weight` + pa.`weight`) * cp.`quantity`) AS nb
				FROM `'._DB_PREFIX_.'cart_product` cp
				LEFT JOIN `'._DB_PREFIX_.'product` p ON (cp.`id_product` = p.`id_product`)
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (cp.`id_product_attribute` = pa.`id_product_attribute`)
				WHERE (cp.`id_product_attribute` IS NOT NULL AND cp.`id_product_attribute` != 0)
				AND cp.`id_cart` = '.(int) $this->id
                );
            } else {
                $weightProductWithAttribute = 0;
            }

            $weightProductWithoutAttribute = Db::getInstance()->getValue(
                '
			SELECT SUM(p.`weight` * cp.`quantity`) AS nb
			FROM `'._DB_PREFIX_.'cart_product` cp
			LEFT JOIN `'._DB_PREFIX_.'product` p ON (cp.`id_product` = p.`id_product`)
			WHERE (cp.`id_product_attribute` IS NULL OR cp.`id_product_attribute` = 0)
			AND cp.`id_cart` = '.(int) $this->id
            );

            self::$_totalWeight[$this->id] = round((float) $weightProductWithAttribute + (float) $weightProductWithoutAttribute, 6);
        }

        return self::$_totalWeight[$this->id];
    }

    /**
     * The arguments are optional and only serve as return values in case caller needs the details.
     *
     * @param null $cartAmountTaxExcluded
     * @param null $cartAmountTaxIncluded
     *
     * @return float|int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getAverageProductsTaxRate(&$cartAmountTaxExcluded = null, &$cartAmountTaxIncluded = null)
    {
        $cartAmountTaxIncluded = $this->getOrderTotal(true, self::ONLY_PRODUCTS);
        $cartAmountTaxExcluded = $this->getOrderTotal(false, self::ONLY_PRODUCTS);

        $cartVatAmount = $cartAmountTaxIncluded - $cartAmountTaxExcluded;

        if ($cartVatAmount == 0 || $cartAmountTaxExcluded == 0) {
            return 0;
        } else {
            return Tools::ps_round($cartVatAmount / $cartAmountTaxExcluded, 3);
        }
    }

    /**
     * Get the gift wrapping price
     *
     * @param bool $withTaxes With or without taxes
     *
     * @return float wrapping price
     *
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
        } elseif (Configuration::get('PS_ATCP_SHIPWRAP')) {
            // With PS_ATCP_SHIPWRAP, wrapping fee is by default tax included, so we convert it
            // when asked for the pre tax price.
            $wrappingFees = Tools::ps_round(
                $wrappingFees / (1 + $this->getAverageProductsTaxRate()),
                _PS_PRICE_COMPUTE_PRECISION_
            );
        }

        return $wrappingFees;
    }

    /**
     * @param int $filter
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getCartRules($filter = CartRule::FILTER_ACTION_ALL)
    {
        // If the cart has not been saved, then there can't be any cart rule applied
        if (!CartRule::isFeatureActive() || !$this->id) {
            return [];
        }

        $cacheKey = 'self::getCartRules_'.$this->id.'-'.$filter;
        if (!Cache::isStored($cacheKey)) {
            $result = Db::getInstance()->executeS(
                '
				SELECT cr.*, crl.`id_lang`, crl.`name`, cd.`id_cart`
				FROM `'._DB_PREFIX_.'cart_cart_rule` cd
				LEFT JOIN `'._DB_PREFIX_.'cart_rule` cr ON cd.`id_cart_rule` = cr.`id_cart_rule`
				LEFT JOIN `'._DB_PREFIX_.'cart_rule_lang` crl ON (
					cd.`id_cart_rule` = crl.`id_cart_rule`
					AND crl.id_lang = '.(int) $this->id_lang.'
				)
				WHERE `id_cart` = '.(int) $this->id.'
				'.($filter == CartRule::FILTER_ACTION_SHIPPING ? 'AND free_shipping = 1' : '').'
				'.($filter == CartRule::FILTER_ACTION_GIFT ? 'AND gift_product != 0' : '').'
				'.($filter == CartRule::FILTER_ACTION_REDUCTION ? 'AND (reduction_percent != 0 OR reduction_amount != 0)' : '')
                .' ORDER BY cr.priority ASC'
            );
            Cache::store($cacheKey, $result);
        } else {
            $result = Cache::retrieve($cacheKey);
        }

        // Define virtual context to prevent case where the cart is not the in the global context
        $virtualContext = Context::getContext()->cloneContext();
        $virtualContext->cart = $this;

        foreach ($result as &$row) {
            $row['obj'] = new CartRule($row['id_cart_rule'], (int) $this->id_lang);
            $row['value_real'] = $row['obj']->getContextualValue(true, $virtualContext, $filter);
            $row['value_tax_exc'] = $row['obj']->getContextualValue(false, $virtualContext, $filter);
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
     * @param        $int
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
     */
    public static function lastNoneOrderedCart($idCustomer)
    {
        $sql = 'SELECT c.`id_cart`
				FROM '._DB_PREFIX_.'cart c
				WHERE NOT EXISTS (SELECT 1 FROM '._DB_PREFIX_.'orders o WHERE o.`id_cart` = c.`id_cart`
									AND o.`id_customer` = '.(int) $idCustomer.')
				AND c.`id_customer` = '.(int) $idCustomer.'
					'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'c').'
				ORDER BY c.`date_upd` DESC';

        if (!$idCart = Db::getInstance()->getValue($sql)) {
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCartByOrderId($idOrder)
    {
        if ($idCart = self::getCartIdByOrderId($idOrder)) {
            return new Cart((int) $idCart);
        }

        return false;
    }

    /**
     * @param $idOrder
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCartIdByOrderId($idOrder)
    {
        $result = Db::getInstance()->getRow('SELECT `id_cart` FROM '._DB_PREFIX_.'orders WHERE `id_order` = '.(int) $idOrder);
        if (!$result || empty($result) || !array_key_exists('id_cart', $result)) {
            return false;
        }

        return $result['id_cart'];
    }

    /**
     * @param      $idCustomer
     * @param bool $withOrder
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCustomerCarts($idCustomer, $withOrder = true)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT *
		FROM '._DB_PREFIX_.'cart c
		WHERE c.`id_customer` = '.(int) $idCustomer.'
		'.(!$withOrder ? 'AND NOT EXISTS (SELECT 1 FROM '._DB_PREFIX_.'orders o WHERE o.`id_cart` = c.`id_cart`)' : '').'
		ORDER BY c.`date_add` DESC'
        );
    }

    /**
     * @param $echo
     * @param $tr
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     */
    public static function isGuestCartByCartId($idCart)
    {
        if (!(int) $idCart) {
            return false;
        }

        $sql = new DbQuery();
        $sql->select('`is_guest`');
        $sql->from('customer', 'cu');
        $sql->leftJoin('cart', 'ca', 'ca.`id_customer` = cu.`id_customer`');
        $sql->where('ca.`id_cart` = '.(int) $idCart);

        return (bool) Db::getInstance()->getValue($sql);
    }

    /**
     *
     * Execute hook displayCarrierList (extraCarrier) and merge theme to the $array
     *
     * @param array $array
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
     */
    public function getAddressCollection()
    {
        $collection = [];
        $cacheId = 'self::getAddressCollection'.(int) $this->id;
        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance()->executeS(
                'SELECT DISTINCT `id_address_delivery`
				FROM `'._DB_PREFIX_.'cart_product`
				WHERE id_cart = '.(int) $this->id
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

        $sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
		SET `id_address_delivery` = '.(int) $idAddressNew.'
		WHERE  `id_cart` = '.(int) $this->id.'
			AND `id_address_delivery` = '.(int) $idAddress;
        Db::getInstance()->execute($sql);

        $sql = 'UPDATE `'._DB_PREFIX_.'customization`
			SET `id_address_delivery` = '.(int) $idAddressNew.'
			WHERE  `id_cart` = '.(int) $this->id.'
				AND `id_address_delivery` = '.(int) $idAddress;
        Db::getInstance()->execute($sql);
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
        if (isset(self::$_nbProducts[$this->id])) {
            unset(self::$_nbProducts[$this->id]);
        }

        if (isset(self::$_totalWeight[$this->id])) {
            unset(self::$_totalWeight[$this->id]);
        }

        $this->_products = null;
        $return = parent::update($nullValues);
        Hook::exec('actionCartSave');

        return $return;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if ($this->OrderExists()) { //NOT delete a cart which is associated with an order
            return false;
        }

        $uploadedFiles = Db::getInstance()->executeS(
            '
			SELECT cd.`value`
			FROM `'._DB_PREFIX_.'customized_data` cd
			INNER JOIN `'._DB_PREFIX_.'customization` c ON (cd.`id_customization`= c.`id_customization`)
			WHERE cd.`type`= 0 AND c.`id_cart`='.(int) $this->id
        );

        foreach ($uploadedFiles as $mustUnlink) {
            unlink(_PS_UPLOAD_DIR_.$mustUnlink['value'].'_small');
            unlink(_PS_UPLOAD_DIR_.$mustUnlink['value']);
        }

        Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'customized_data`
			WHERE `id_customization` IN (
				SELECT `id_customization`
				FROM `'._DB_PREFIX_.'customization`
				WHERE `id_cart`='.(int) $this->id.'
			)'
        );

        Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'customization`
			WHERE `id_cart` = '.(int) $this->id
        );

        if (!Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'cart_cart_rule` WHERE `id_cart` = '.(int) $this->id)
            || !Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'cart_product` WHERE `id_cart` = '.(int) $this->id)
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
     */
    public function orderExists()
    {
        $cacheId = 'self::orderExists_'.(int) $this->id;
        if (!Cache::isStored($cacheId)) {
            $result = (bool) Db::getInstance()->getValue('SELECT count(*) FROM `'._DB_PREFIX_.'orders` WHERE `id_cart` = '.(int) $this->id);
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @deprecated 1.0.0, use Cart->getCartRules()
     */
    public function getDiscounts($lite = false, $refresh = false)
    {
        Tools::displayAsDeprecated();

        return $this->getCartRules();
    }

    /**
     * Return the cart rules Ids on the cart.
     *
     * @param $filter
     *
     * @return array
     * @throws PrestaShopDatabaseException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getOrderedCartRulesIds($filter = CartRule::FILTER_ACTION_ALL)
    {
        $cacheKey = 'self::getOrderedCartRulesIds_'.$this->id.'-'.$filter.'-ids';
        if (!Cache::isStored($cacheKey)) {
            $result = Db::getInstance()->executeS(
                '
				SELECT cr.`id_cart_rule`
				FROM `'._DB_PREFIX_.'cart_cart_rule` cd
				LEFT JOIN `'._DB_PREFIX_.'cart_rule` cr ON cd.`id_cart_rule` = cr.`id_cart_rule`
				LEFT JOIN `'._DB_PREFIX_.'cart_rule_lang` crl ON (
					cd.`id_cart_rule` = crl.`id_cart_rule`
					AND crl.id_lang = '.(int) $this->id_lang.'
				)
				WHERE `id_cart` = '.(int) $this->id.'
				'.($filter == CartRule::FILTER_ACTION_SHIPPING ? 'AND free_shipping = 1' : '').'
				'.($filter == CartRule::FILTER_ACTION_GIFT ? 'AND gift_product != 0' : '').'
				'.($filter == CartRule::FILTER_ACTION_REDUCTION ? 'AND (reduction_percent != 0 OR reduction_amount != 0)' : '')
                .' ORDER BY cr.priority ASC'
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
     */
    public function getDiscountsCustomer($idCartRule)
    {
        if (!CartRule::isFeatureActive()) {
            return 0;
        }
        $cacheId = 'self::getDiscountsCustomer_'.(int) $this->id.'-'.(int) $idCartRule;
        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::getInstance()->getValue(
                '
				SELECT COUNT(*)
				FROM `'._DB_PREFIX_.'cart_cart_rule`
				WHERE `id_cart_rule` = '.(int) $idCartRule.' AND `id_cart` = '.(int) $this->id
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @return bool|mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLastProduct()
    {
        $sql = new DbQuery();
        $sql->select('`id_product`, `id_product_attribute`, `id_shop`');
        $sql->from('cart_product', 'cp');
        $sql->where('`id_cart` = '.(int) $this->id);
        $sql->orderBy('`date_add` DESC');

        $result = Db::getInstance()->getRow($sql);
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
     */
    public function nbProducts()
    {
        if (!$this->id) {
            return 0;
        }

        return self::getNbProducts($this->id);
    }

    /**
     * @param int $id
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNbProducts($id)
    {
        // Must be strictly compared to NULL, or else an empty cart will bypass the cache and add dozens of queries
        if (isset(self::$_nbProducts[$id]) && self::$_nbProducts[$id] !== null) {
            return self::$_nbProducts[$id];
        }

        self::$_nbProducts[$id] = (int) Db::getInstance()->getValue(
            '
			SELECT SUM(`quantity`)
			FROM `'._DB_PREFIX_.'cart_product`
			WHERE `id_cart` = '.(int) $id
        );

        return self::$_nbProducts[$id];
    }

    /**
     * @deprecated 1.0.0, use Cart->addCartRule()
     *
     * @param int $idCartRule
     *
     * @return bool
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
     */
    public function addCartRule($idCartRule)
    {
        // You can't add a cart rule that does not exist
        $cartRule = new CartRule($idCartRule, Context::getContext()->language->id);

        if (!Validate::isLoadedObject($cartRule)) {
            return false;
        }

        if (Db::getInstance()->getValue('SELECT id_cart_rule FROM '._DB_PREFIX_.'cart_cart_rule WHERE id_cart_rule = '.(int) $idCartRule.' AND id_cart = '.(int) $this->id)) {
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

        Cache::clean('self::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_ALL);
        Cache::clean('self::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_SHIPPING);
        Cache::clean('self::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_REDUCTION);
        Cache::clean('self::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_GIFT);

        Cache::clean('self::getOrderedCartRulesIds_'.$this->id.'-'.CartRule::FILTER_ACTION_ALL.'-ids');
        Cache::clean('self::getOrderedCartRulesIds_'.$this->id.'-'.CartRule::FILTER_ACTION_SHIPPING.'-ids');
        Cache::clean('self::getOrderedCartRulesIds_'.$this->id.'-'.CartRule::FILTER_ACTION_REDUCTION.'-ids');
        Cache::clean('self::getOrderedCartRulesIds_'.$this->id.'-'.CartRule::FILTER_ACTION_GIFT.'-ids');

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

        if (isset(self::$_nbProducts[$this->id])) {
            unset(self::$_nbProducts[$this->id]);
        }

        if (isset(self::$_totalWeight[$this->id])) {
            unset(self::$_totalWeight[$this->id]);
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
                    $sql = 'SELECT stock.out_of_stock, IFNULL(stock.quantity, 0) AS quantity
							FROM '._DB_PREFIX_.'product p
							'.Product::sqlStock('p', $idProductAttribute, true, $shop).'
							WHERE p.id_product = '.$idProduct;

                    $result2 = Db::getInstance()->getRow($sql);
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
                    Db::getInstance()->execute(
                        '
						UPDATE `'._DB_PREFIX_.'cart_product`
						SET `quantity` = `quantity` '.$qty.', `date_add` = NOW()
						WHERE `id_product` = '.(int) $idProduct.
                        (!empty($idProductAttribute) ? ' AND `id_product_attribute` = '.(int) $idProductAttribute : '').'
						AND `id_cart` = '.(int) $this->id.(Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery() ? ' AND `id_address_delivery` = '.(int) $idAddressDelivery : '').'
						LIMIT 1'
                    );
                }
            } /* Add product to the cart */
            elseif ($operator == 'up') {
                $sql = 'SELECT stock.out_of_stock, IFNULL(stock.quantity, 0) AS quantity
						FROM '._DB_PREFIX_.'product p
						'.Product::sqlStock('p', $idProductAttribute, true, $shop).'
						WHERE p.id_product = '.$idProduct;

                $result2 = Db::getInstance()->getRow($sql);

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

        // refresh cache of self::_products
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
     * @param int $idProduct          Product ID
     * @param int $idProductAttribute Attribute ID if needed
     * @param int $idCustomization    Customization id
     *
     * @return bool result
     */
    public function deleteProduct($idProduct, $idProductAttribute = null, $idCustomization = null, $idAddressDelivery = 0, $autoAddCartRule = true)
    {
        if (isset(self::$_nbProducts[$this->id])) {
            unset(self::$_nbProducts[$this->id]);
        }

        if (isset(self::$_totalWeight[$this->id])) {
            unset(self::$_totalWeight[$this->id]);
        }

        if ((int) $idCustomization) {
            $productTotalQuantity = (int) Db::getInstance()->getValue(
                'SELECT `quantity`
				FROM `'._DB_PREFIX_.'cart_product`
				WHERE `id_product` = '.(int) $idProduct.'
				AND `id_cart` = '.(int) $this->id.'
				AND `id_product_attribute` = '.(int) $idProductAttribute
            );

            $customizationQuantity = (int) Db::getInstance()->getValue(
                '
			SELECT `quantity`
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_cart` = '.(int) $this->id.'
			AND `id_product` = '.(int) $idProduct.'
			AND `id_product_attribute` = '.(int) $idProductAttribute.'
			'.((int) $idAddressDelivery ? 'AND `id_address_delivery` = '.(int) $idAddressDelivery : '')
            );

            if (!$this->_deleteCustomization((int) $idCustomization, (int) $idProduct, (int) $idProductAttribute, (int) $idAddressDelivery)) {
                return false;
            }

            // refresh cache of self::_products
            $this->_products = $this->getProducts(true);

            return ($customizationQuantity == $productTotalQuantity && $this->deleteProduct((int) $idProduct, (int) $idProductAttribute, null, (int) $idAddressDelivery));
        }

        /* Get customization quantity */
        $result = Db::getInstance()->getRow(
            '
			SELECT SUM(`quantity`) AS \'quantity\'
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_cart` = '.(int) $this->id.'
			AND `id_product` = '.(int) $idProduct.'
			AND `id_product_attribute` = '.(int) $idProductAttribute
        );

        if ($result === false) {
            return false;
        }

        /* If the product still possesses customization it does not have to be deleted */
        if (Db::getInstance()->NumRows() && (int) $result['quantity']) {
            return Db::getInstance()->execute(
                '
				UPDATE `'._DB_PREFIX_.'cart_product`
				SET `quantity` = '.(int) $result['quantity'].'
				WHERE `id_cart` = '.(int) $this->id.'
				AND `id_product` = '.(int) $idProduct.
                ($idProductAttribute != null ? ' AND `id_product_attribute` = '.(int) $idProductAttribute : '')
            );
        }

        /* Product deletion */
        $result = Db::getInstance()->execute(
            '
		DELETE FROM `'._DB_PREFIX_.'cart_product`
		WHERE `id_product` = '.(int) $idProduct.'
		'.(!is_null($idProductAttribute) ? ' AND `id_product_attribute` = '.(int) $idProductAttribute : '').'
		AND `id_cart` = '.(int) $this->id.'
		'.((int) $idAddressDelivery ? 'AND `id_address_delivery` = '.(int) $idAddressDelivery : '')
        );

        if ($result) {
            $return = $this->update();
            // refresh cache of self::_products
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
     */
    protected function _deleteCustomization($idCustomization, $idProduct, $idProductAttribute, $idAddressDelivery = 0)
    {
        $result = true;
        $customization = Db::getInstance()->getRow(
            'SELECT *
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_customization` = '.(int) $idCustomization
        );

        if ($customization) {
            $custData = Db::getInstance()->getRow(
                'SELECT *
				FROM `'._DB_PREFIX_.'customized_data`
				WHERE `id_customization` = '.(int) $idCustomization
            );

            // Delete customization picture if necessary
            if (isset($custData['type']) && $custData['type'] == 0) {
                $result &= (@unlink(_PS_UPLOAD_DIR_.$custData['value']) && @unlink(_PS_UPLOAD_DIR_.$custData['value'].'_small'));
            }

            $result &= Db::getInstance()->execute(
                'DELETE FROM `'._DB_PREFIX_.'customized_data`
				WHERE `id_customization` = '.(int) $idCustomization
            );

            if ($result) {
                $result &= Db::getInstance()->execute(
                    'UPDATE `'._DB_PREFIX_.'cart_product`
					SET `quantity` = `quantity` - '.(int) $customization['quantity'].'
					WHERE `id_cart` = '.(int) $this->id.'
					AND `id_product` = '.(int) $idProduct.
                    ((int) $idProductAttribute ? ' AND `id_product_attribute` = '.(int) $idProductAttribute : '').'
					AND `id_address_delivery` = '.(int) $idAddressDelivery
                );
            }

            if (!$result) {
                return false;
            }

            return Db::getInstance()->execute(
                'DELETE FROM `'._DB_PREFIX_.'customization`
				WHERE `id_customization` = '.(int) $idCustomization
            );
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function containsProduct($idProduct, $idProductAttribute = 0, $idCustomization = 0, $idAddressDelivery = 0)
    {
        $sql = 'SELECT cp.`quantity` FROM `'._DB_PREFIX_.'cart_product` cp';

        if ($idCustomization) {
            $sql .= '
				LEFT JOIN `'._DB_PREFIX_.'customization` c ON (
					c.`id_product` = cp.`id_product`
					AND c.`id_product_attribute` = cp.`id_product_attribute`
				)';
        }

        $sql .= '
			WHERE cp.`id_product` = '.(int) $idProduct.'
			AND cp.`id_product_attribute` = '.(int) $idProductAttribute.'
			AND cp.`id_cart` = '.(int) $this->id;
        if (Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery()) {
            $sql .= ' AND cp.`id_address_delivery` = '.(int) $idAddressDelivery;
        }

        if ($idCustomization) {
            $sql .= ' AND c.`id_customization` = '.(int) $idCustomization;
        }

        return Db::getInstance()->getRow($sql);
    }

    /**
     * @param        $quantity
     * @param        $idCustomization
     * @param        $idProduct
     * @param        $idProductAttribute
     * @param        $idAddressDelivery
     * @param string $operator
     *
     * @return bool
     *
     * @deprecated 2.0.0
     */
    protected function _updateCustomizationQuantity($quantity, $idCustomization, $idProduct, $idProductAttribute, $idAddressDelivery, $operator = 'up')
    {
        // Link customization to product combination when it is first added to cart
        if (empty($idCustomization)) {
            $customization = $this->getProductCustomization($idProduct, null, true);
            foreach ($customization as $field) {
                if ($field['quantity'] == 0) {
                    Db::getInstance()->execute(
                        '
					UPDATE `'._DB_PREFIX_.'customization`
					SET `quantity` = '.(int) $quantity.',
						`id_product_attribute` = '.(int) $idProductAttribute.',
						`id_address_delivery` = '.(int) $idAddressDelivery.',
						`in_cart` = 1
					WHERE `id_customization` = '.(int) $field['id_customization']
                    );
                }
            }
        }

        /* Deletion */
        if (!empty($idCustomization) && (int) $quantity < 1) {
            return $this->_deleteCustomization((int) $idCustomization, (int) $idProduct, (int) $idProductAttribute);
        }

        /* Quantity update */
        if (!empty($idCustomization)) {
            $result = Db::getInstance()->getRow('SELECT `quantity` FROM `'._DB_PREFIX_.'customization` WHERE `id_customization` = '.(int) $idCustomization);
            if ($result && Db::getInstance()->NumRows()) {
                if ($operator == 'down' && (int) $result['quantity'] - (int) $quantity < 1) {
                    return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'customization` WHERE `id_customization` = '.(int) $idCustomization);
                }

                return Db::getInstance()->execute(
                    '
					UPDATE `'._DB_PREFIX_.'customization`
					SET
						`quantity` = `quantity` '.($operator == 'up' ? '+ ' : '- ').(int) $quantity.',
						`id_address_delivery` = '.(int) $idAddressDelivery.',
						`in_cart` = 1
					WHERE `id_customization` = '.(int) $idCustomization
                );
            } else {
                Db::getInstance()->execute(
                    '
					UPDATE `'._DB_PREFIX_.'customization`
					SET `id_address_delivery` = '.(int) $idAddressDelivery.',
					`in_cart` = 1
					WHERE `id_customization` = '.(int) $idCustomization
                );
            }
        }
        // refresh cache of self::_products
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductCustomization($idProduct, $type = null, $notInCart = false)
    {
        if (!Customization::isFeatureActive()) {
            return [];
        }

        $result = Db::getInstance()->executeS(
            '
			SELECT cu.id_customization, cd.index, cd.value, cd.type, cu.in_cart, cu.quantity
			FROM `'._DB_PREFIX_.'customization` cu
			LEFT JOIN `'._DB_PREFIX_.'customized_data` cd ON (cu.`id_customization` = cd.`id_customization`)
			WHERE cu.id_cart = '.(int) $this->id.'
			AND cu.id_product = '.(int) $idProduct.
            ($type === Product::CUSTOMIZE_FILE ? ' AND type = '.(int) Product::CUSTOMIZE_FILE : '').
            ($type === Product::CUSTOMIZE_TEXTFIELD ? ' AND type = '.(int) Product::CUSTOMIZE_TEXTFIELD : '').
            ($notInCart ? ' AND in_cart = 0' : '')
        );

        return $result;
    }

    /**
     * @deprecated 1.0.0, use Cart->removeCartRule()
     *
     * @param $idCartRule
     *
     * @return bool
     */
    public function deleteDiscount($idCartRule)
    {
        Tools::displayAsDeprecated();

        return $this->removeCartRule($idCartRule);
    }

    /**
     * @param $idCartRule
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function removeCartRule($idCartRule)
    {
        Cache::clean('self::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_ALL);
        Cache::clean('self::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_SHIPPING);
        Cache::clean('self::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_REDUCTION);
        Cache::clean('self::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_GIFT);

        Cache::clean('self::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_ALL.'-ids');
        Cache::clean('self::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_SHIPPING.'-ids');
        Cache::clean('self::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_REDUCTION.'-ids');
        Cache::clean('self::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_GIFT.'-ids');

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
     * New theme need to use self::getDeliveryOptionList() to generate carriers option in the checkout process
     *
     * @param Country $defaultCountry
     * @param bool    $flush Force flushing cache
     *
     * @return array
     *
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
                'id_carrier'    => self::intifier($key), // Need to translate to an integer for retrocompatibility reason, in 1.4 template we used intval
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function simulateCarrierSelectedOutput($useCache = true)
    {
        $deliveryOption = $this->getDeliveryOption(null, false, $useCache);

        if (count($deliveryOption) > 1 || empty($deliveryOption)) {
            return 0;
        }

        return self::intifier(reset($deliveryOption));
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
     * @deprecated 1.0.0, use self::getPackageShippingCost
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
     *
     * @return bool|string
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
     * @return array Cart details
     *
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
        $totalProductsWt = $this->getOrderTotal(true, self::ONLY_PRODUCTS);
        $totalProducts = $this->getOrderTotal(false, self::ONLY_PRODUCTS);
        $totalDiscounts = $this->getOrderTotal(true, self::ONLY_DISCOUNTS);
        $totalDiscountsTaxExc = $this->getOrderTotal(false, self::ONLY_DISCOUNTS);

        // The cart content is altered for display
        foreach ($cartRules as &$cartRule) {
            // If the cart rule is automatic (wihtout any code) and include free shipping, it should not be displayed as a cart rule but only set the shipping cost to 0
            if ($cartRule['free_shipping'] && (empty($cartRule['code']) || preg_match('/^'.CartRule::BO_ORDER_CODE_PREFIX.'[0-9]+/', $cartRule['code']))) {

                $cartRule['value_real'] -= $totalShipping;
                $cartRule['value_tax_exc'] -= $totalShippingTaxExc;
                $cartRule['value_real'] = Tools::ps_round($cartRule['value_real'], (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                $cartRule['value_tax_exc'] = Tools::ps_round($cartRule['value_tax_exc'], (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
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
                        $totalProductsWt = Tools::ps_round($totalProductsWt - $product['price_wt'], (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $totalProducts = Tools::ps_round($totalProducts - $product['price'], (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        // Update total discounts
                        $totalDiscounts = Tools::ps_round($totalDiscounts - $product['price_wt'], (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $totalDiscountsTaxExc = Tools::ps_round($totalDiscountsTaxExc - $product['price'], (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        // Update cart rule value
                        $cartRule['value_real'] = Tools::ps_round($cartRule['value_real'] - $product['price_wt'], (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $cartRule['value_tax_exc'] = Tools::ps_round($cartRule['value_tax_exc'] - $product['price'], (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        // Update product quantity
                        $product['total_wt'] = Tools::ps_round($product['total_wt'] - $product['price_wt'], (int) $currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $product['total'] = Tools::ps_round($product['total'] - $product['price'], (int) $currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
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
            'total_wrapping'            => $this->getOrderTotal(true, self::ONLY_WRAPPING),
            'total_wrapping_tax_exc'    => $this->getOrderTotal(false, self::ONLY_WRAPPING),
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
     * @param array &$error           contain an error message if an error occurs
     *
     * @return array Array of address id or of address object
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
     * @params  int $id_product
     * @params  int $index
     * @params  int $type
     * @params  string $textValue
     *
     * @return bool Always true
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     */
    public function _addCustomization($idProduct, $idProductAttribute, $index, $type, $field, $quantity)
    {
        $exisingCustomization = Db::getInstance()->executeS(
            '
			SELECT cu.`id_customization`, cd.`index`, cd.`value`, cd.`type` FROM `'._DB_PREFIX_.'customization` cu
			LEFT JOIN `'._DB_PREFIX_.'customized_data` cd
			ON cu.`id_customization` = cd.`id_customization`
			WHERE cu.id_cart = '.(int) $this->id.'
			AND cu.id_product = '.(int) $idProduct.'
			AND in_cart = 0'
        );

        if ($exisingCustomization) {
            // If the customization field is alreay filled, delete it
            foreach ($exisingCustomization as $customization) {
                if ($customization['type'] == $type && $customization['index'] == $index) {
                    Db::getInstance()->execute(
                        '
						DELETE FROM `'._DB_PREFIX_.'customized_data`
						WHERE id_customization = '.(int) $customization['id_customization'].'
						AND type = '.(int) $customization['type'].'
						AND `index` = '.(int) $customization['index']
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
            Db::getInstance()->execute(
                'INSERT INTO `'._DB_PREFIX_.'customization` (`id_cart`, `id_product`, `id_product_attribute`, `quantity`)
				VALUES ('.(int) $this->id.', '.(int) $idProduct.', '.(int) $idProductAttribute.', '.(int) $quantity.')'
            );
            $idCustomization = Db::getInstance()->Insert_ID();
        }

        $query = 'INSERT INTO `'._DB_PREFIX_.'customized_data` (`id_customization`, `type`, `index`, `value`)
			VALUES ('.(int) $idCustomization.', '.(int) $type.', '.(int) $index.', \''.pSQL($field).'\')';

        if (!Db::getInstance()->execute($query)) {
            return false;
        }

        return true;
    }

    /**
     * Add customer's pictures
     *
     * @param $idProduct
     * @param $index
     * @param $type
     * @param $file
     *
     * @return bool Always true
     *
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
     * @param     $index
     *
     * @return bool
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
     */
    public function deleteCustomizationToProduct($idProduct, $index)
    {
        $result = true;

        $custData = Db::getInstance()->getRow(
            '
			SELECT cu.`id_customization`, cd.`index`, cd.`value`, cd.`type` FROM `'._DB_PREFIX_.'customization` cu
			LEFT JOIN `'._DB_PREFIX_.'customized_data` cd
			ON cu.`id_customization` = cd.`id_customization`
			WHERE cu.`id_cart` = '.(int) $this->id.'
			AND cu.`id_product` = '.(int) $idProduct.'
			AND `index` = '.(int) $index.'
			AND `in_cart` = 0'
        );

        // Delete customization picture if necessary
        if ($custData['type'] == 0) {
            $result &= (@unlink(_PS_UPLOAD_DIR_.$custData['value']) && @unlink(_PS_UPLOAD_DIR_.$custData['value'].'_small'));
        }

        $result &= Db::getInstance()->execute(
            'DELETE
			FROM `'._DB_PREFIX_.'customized_data`
			WHERE `id_customization` = '.(int) $custData['id_customization'].'
			AND `index` = '.(int) $index
        );

        return $result;
    }

    /**
     * @return array
     *
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
            $cart->secure_key = self::$_customer->secure_key;
        }

        $cart->add();

        if (!Validate::isLoadedObject($cart)) {
            return false;
        }

        $success = true;
        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM `'._DB_PREFIX_.'cart_product` WHERE `id_cart` = '.(int) $this->id);

        $productGift = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT cr.`gift_product`, cr.`gift_product_attribute` FROM `'._DB_PREFIX_.'cart_rule` cr LEFT JOIN `'._DB_PREFIX_.'order_cart_rule` ocr ON (ocr.`id_order` = '.(int) $this->id.') WHERE ocr.`id_cart_rule` = cr.`id_cart_rule`');

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
            '
			SELECT *
			FROM '._DB_PREFIX_.'customization c
			LEFT JOIN '._DB_PREFIX_.'customized_data cd ON cd.id_customization = c.id_customization
			WHERE c.id_cart = '.(int) $this->id
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
            Db::getInstance()->execute(
                '
				INSERT INTO `'._DB_PREFIX_.'customization` (id_cart, id_product_attribute, id_product, `id_address_delivery`, quantity, `quantity_refunded`, `quantity_returned`, `in_cart`)
				VALUES('.(int) $cart->id.', '.(int) $val['id_product_attribute'].', '.(int) $val['id_product'].', '.(int) $idAddressDelivery.', '.(int) $val['quantity'].', 0, 0, 1)'
            );
            $customIds[$customizationId] = Db::getInstance(_PS_USE_SQL_SLAVE_)->Insert_ID();
        }

        // Insert customized_data
        if (count($customs)) {
            $first = true;
            $sqlCustomData = 'INSERT INTO '._DB_PREFIX_.'customized_data (`id_customization`, `type`, `index`, `value`) VALUES ';
            foreach ($customs as $custom) {
                if (!$first) {
                    $sqlCustomData .= ',';
                } else {
                    $first = false;
                }

                $customizedValue = $custom['value'];

                if ((int) $custom['type'] == 0) {
                    $customizedValue = md5(uniqid(rand(), true));
                    Tools::copy(_PS_UPLOAD_DIR_.$custom['value'], _PS_UPLOAD_DIR_.$customizedValue);
                    Tools::copy(_PS_UPLOAD_DIR_.$custom['value'].'_small', _PS_UPLOAD_DIR_.$customizedValue.'_small');
                }

                $sqlCustomData .= '('.(int) $customIds[$custom['id_customization']].', '.(int) $custom['type'].', '.(int) $custom['index'].', \''.pSQL($customizedValue).'\')';
            }
            Db::getInstance()->execute($sqlCustomData);
        }

        return ['cart' => $cart, 'success' => $success];
    }

    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autodate = true, $nullValues = false)
    {
        if (!$this->id_lang) {
            $this->id_lang = Configuration::get('PS_LANG_DEFAULT');
        }
        if (!$this->id_shop) {
            $this->id_shop = Context::getContext()->shop->id;
        }

        $return = parent::add($autodate, $nullValues);
        Hook::exec('actionCartSave');

        return $return;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function getWsCartRows()
    {
        return Db::getInstance()->executeS(
            '
			SELECT id_product, id_product_attribute, quantity, id_address_delivery
			FROM `'._DB_PREFIX_.'cart_product`
			WHERE id_cart = '.(int) $this->id.' AND id_shop = '.(int) Context::getContext()->shop->id
        );
    }

    /**
     * @param $values
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function setWsCartRows($values)
    {
        if ($this->deleteAssociations()) {
            $query = 'INSERT INTO `'._DB_PREFIX_.'cart_product`(`id_cart`, `id_product`, `id_product_attribute`, `id_address_delivery`, `quantity`, `date_add`, `id_shop`) VALUES ';

            foreach ($values as $value) {
                $query .= '('.(int) $this->id.', '.(int) $value['id_product'].', '.
                    (isset($value['id_product_attribute']) ? (int) $value['id_product_attribute'] : 'NULL').', '.
                    (isset($value['id_address_delivery']) ? (int) $value['id_address_delivery'] : 0).', '.
                    (int) $value['quantity'].', NOW(), '.(int) Context::getContext()->shop->id.'),';
            }

            Db::getInstance()->execute(rtrim($query, ','));
        }

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function deleteAssociations()
    {
        return (Db::getInstance()->execute(
                '
				DELETE FROM `'._DB_PREFIX_.'cart_product`
				WHERE `id_cart` = '.(int) $this->id
            ) !== false);
    }

    /**
     * @param $id_product
     * @param $id_product_attribute
     * @param $old_id_address_delivery
     * @param $new_id_address_delivery
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function setProductAddressDelivery($id_product, $id_product_attribute, $old_id_address_delivery, $new_id_address_delivery)
    {
        // Check address is linked with the customer
        if (!Customer::customerHasAddress(Context::getContext()->customer->id, $new_id_address_delivery)) {
            return false;
        }

        if ($new_id_address_delivery == $old_id_address_delivery) {
            return false;
        }

        // Checking if the product with the old address delivery exists
        $sql = new DbQuery();
        $sql->select('count(*)');
        $sql->from('cart_product', 'cp');
        $sql->where('id_product = '.(int) $id_product);
        $sql->where('id_product_attribute = '.(int) $id_product_attribute);
        $sql->where('id_address_delivery = '.(int) $old_id_address_delivery);
        $sql->where('id_cart = '.(int) $this->id);
        $result = Db::getInstance()->getValue($sql);

        if ($result == 0) {
            return false;
        }

        // Checking if there is no others similar products with this new address delivery
        $sql = new DbQuery();
        $sql->select('sum(quantity) as qty');
        $sql->from('cart_product', 'cp');
        $sql->where('id_product = '.(int) $id_product);
        $sql->where('id_product_attribute = '.(int) $id_product_attribute);
        $sql->where('id_address_delivery = '.(int) $new_id_address_delivery);
        $sql->where('id_cart = '.(int) $this->id);
        $result = Db::getInstance()->getValue($sql);

        // Removing similar products with this new address delivery
        $sql = 'DELETE FROM '._DB_PREFIX_.'cart_product
			WHERE id_product = '.(int) $id_product.'
			AND id_product_attribute = '.(int) $id_product_attribute.'
			AND id_address_delivery = '.(int) $new_id_address_delivery.'
			AND id_cart = '.(int) $this->id.'
			LIMIT 1';
        Db::getInstance()->execute($sql);

        // Changing the address
        $sql = 'UPDATE '._DB_PREFIX_.'cart_product
			SET `id_address_delivery` = '.(int) $new_id_address_delivery.',
			`quantity` = `quantity` + '.(int) $result.'
			WHERE id_product = '.(int) $id_product.'
			AND id_product_attribute = '.(int) $id_product_attribute.'
			AND id_address_delivery = '.(int) $old_id_address_delivery.'
			AND id_cart = '.(int) $this->id.'
			LIMIT 1';
        Db::getInstance()->execute($sql);

        // Changing the address of the customizations
        $sql = 'UPDATE '._DB_PREFIX_.'customization
			SET `id_address_delivery` = '.(int) $new_id_address_delivery.'
			WHERE id_product = '.(int) $id_product.'
			AND id_product_attribute = '.(int) $id_product_attribute.'
			AND id_address_delivery = '.(int) $old_id_address_delivery.'
			AND id_cart = '.(int) $this->id;
        Db::getInstance()->execute($sql);

        return true;
    }

    /**
     * @param      $id_product
     * @param      $id_product_attribute
     * @param      $id_address_delivery
     * @param      $new_id_address_delivery
     * @param int  $quantity
     * @param bool $keep_quantity
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function duplicateProduct(
        $id_product,
        $id_product_attribute,
        $id_address_delivery,
        $new_id_address_delivery,
        $quantity = 1,
        $keep_quantity = false
    ) {
        // Check address is linked with the customer
        if (!Customer::customerHasAddress(Context::getContext()->customer->id, $new_id_address_delivery)) {
            return false;
        }

        // Checking the product do not exist with the new address
        $sql = new DbQuery();
        $sql->select('count(*)');
        $sql->from('cart_product', 'c');
        $sql->where('id_product = '.(int) $id_product);
        $sql->where('id_product_attribute = '.(int) $id_product_attribute);
        $sql->where('id_address_delivery = '.(int) $new_id_address_delivery);
        $sql->where('id_cart = '.(int) $this->id);
        $result = Db::getInstance()->getValue($sql);

        if ($result > 0) {
            return false;
        }

        // Duplicating cart_product line
        $sql = 'INSERT INTO '._DB_PREFIX_.'cart_product
			(`id_cart`, `id_product`, `id_shop`, `id_product_attribute`, `quantity`, `date_add`, `id_address_delivery`)
			VALUES(
				'.(int) $this->id.',
				'.(int) $id_product.',
				'.(int) $this->id_shop.',
				'.(int) $id_product_attribute.',
				'.(int) $quantity.',
				NOW(),
				'.(int) $new_id_address_delivery.')';

        Db::getInstance()->execute($sql);

        if (!$keep_quantity) {
            $sql = new DbQuery();
            $sql->select('quantity');
            $sql->from('cart_product', 'c');
            $sql->where('id_product = '.(int) $id_product);
            $sql->where('id_product_attribute = '.(int) $id_product_attribute);
            $sql->where('id_address_delivery = '.(int) $id_address_delivery);
            $sql->where('id_cart = '.(int) $this->id);
            $duplicatedQuantity = Db::getInstance()->getValue($sql);

            if ($duplicatedQuantity > $quantity) {
                $sql = 'UPDATE '._DB_PREFIX_.'cart_product
					SET `quantity` = `quantity` - '.(int) $quantity.'
					WHERE id_cart = '.(int) $this->id.'
					AND id_product = '.(int) $id_product.'
					AND id_shop = '.(int) $this->id_shop.'
					AND id_product_attribute = '.(int) $id_product_attribute.'
					AND id_address_delivery = '.(int) $id_address_delivery;
                Db::getInstance()->execute($sql);
            }
        }

        // Checking if there is customizations
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('customization', 'c');
        $sql->where('id_product = '.(int) $id_product);
        $sql->where('id_product_attribute = '.(int) $id_product_attribute);
        $sql->where('id_address_delivery = '.(int) $id_address_delivery);
        $sql->where('id_cart = '.(int) $this->id);
        $results = Db::getInstance()->executeS($sql);

        foreach ($results as $customization) {
            // Duplicate customization
            $sql = 'INSERT INTO '._DB_PREFIX_.'customization
				(`id_product_attribute`, `id_address_delivery`, `id_cart`, `id_product`, `quantity`, `in_cart`)
				VALUES (
					'.(int) $customization['id_product_attribute'].',
					'.(int) $new_id_address_delivery.',
					'.(int) $customization['id_cart'].',
					'.(int) $customization['id_product'].',
					'.(int) $quantity.',
					'.(int) $customization['in_cart'].')';

            Db::getInstance()->execute($sql);

            // Save last insert ID before doing another query
            $last_id = (int) Db::getInstance()->Insert_ID();

            // Get data from duplicated customizations
            $sql = new DbQuery();
            $sql->select('`type`, `index`, `value`');
            $sql->from('customized_data');
            $sql->where('id_customization = '.$customization['id_customization']);
            $last_row = Db::getInstance()->getRow($sql);

            // Insert new copied data with new customization ID into customized_data table
            $last_row['id_customization'] = $last_id;
            Db::getInstance()->insert('customized_data', $last_row);
        }

        $customization_count = count($results);
        if ($customization_count > 0) {
            $sql = 'UPDATE '._DB_PREFIX_.'cart_product
				SET `quantity` = `quantity` + '.(int) $customization_count * $quantity.'
				WHERE id_cart = '.(int) $this->id.'
				AND id_product = '.(int) $id_product.'
				AND id_shop = '.(int) $this->id_shop.'
				AND id_product_attribute = '.(int) $id_product_attribute.'
				AND id_address_delivery = '.(int) $new_id_address_delivery;
            Db::getInstance()->execute($sql);
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
            $sql = 'SELECT sum(`quantity`) AS quantity, id_product, id_product_attribute, count(*) AS count
					FROM `'._DB_PREFIX_.'cart_product`
					WHERE `id_cart` = '.(int) $this->id.'
						AND `id_shop` = '.(int) $this->id_shop.'
					GROUP BY id_product, id_product_attribute
					HAVING count > 1';

            foreach (Db::getInstance()->executeS($sql) as $product) {
                $sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
					SET `quantity` = '.$product['quantity'].'
					WHERE  `id_cart` = '.(int) $this->id.'
						AND `id_shop` = '.(int) $this->id_shop.'
						AND id_product = '.$product['id_product'].'
						AND id_product_attribute = '.$product['id_product_attribute'];
                if (Db::getInstance()->execute($sql)) {
                    $emptyCache = true;
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
        $sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
		SET `id_address_delivery` = (
			SELECT `id_address_delivery` FROM `'._DB_PREFIX_.'cart`
			WHERE `id_cart` = '.(int) $this->id.' AND `id_shop` = '.(int) $this->id_shop.'
		)
		WHERE `id_cart` = '.(int) $this->id.'
		'.(Configuration::get('PS_ALLOW_MULTISHIPPING') ? ' AND `id_shop` = '.(int) $this->id_shop : '');

        $cache_id = 'self::setNoMultishipping'.(int) $this->id.'-'.(int) $this->id_shop.((isset($this->id_address_delivery) && $this->id_address_delivery) ? '-'.(int) $this->id_address_delivery : '');
        if (!Cache::isStored($cache_id)) {
            if ($result = (bool) Db::getInstance()->execute($sql)) {
                $emptyCache = true;
            }
            Cache::store($cache_id, $result);
        }

        if (Customization::isFeatureActive()) {
            Db::getInstance()->execute(
                '
			UPDATE `'._DB_PREFIX_.'customization`
			SET `id_address_delivery` = (
				SELECT `id_address_delivery` FROM `'._DB_PREFIX_.'cart`
				WHERE `id_cart` = '.(int) $this->id.'
			)
			WHERE `id_cart` = '.(int) $this->id
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
        $id_address_delivery = 0;
        // Get the main address of the customer
        if ((int) $this->id_address_delivery > 0) {
            $id_address_delivery = (int) $this->id_address_delivery;
        } else {
            $id_address_delivery = (int) Address::getFirstCustomerAddressId(Context::getContext()->customer->id);
        }

        if (!$id_address_delivery) {
            return;
        }

        // Update
        $sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
			SET `id_address_delivery` = '.(int) $id_address_delivery.'
			WHERE `id_cart` = '.(int) $this->id.'
				AND (`id_address_delivery` = 0 OR `id_address_delivery` IS NULL)
				AND `id_shop` = '.(int) $this->id_shop;
        Db::getInstance()->execute($sql);

        $sql = 'UPDATE `'._DB_PREFIX_.'customization`
			SET `id_address_delivery` = '.(int) $id_address_delivery.'
			WHERE `id_cart` = '.(int) $this->id.'
				AND (`id_address_delivery` = 0 OR `id_address_delivery` IS NULL)';

        Db::getInstance()->execute($sql);
    }

    /**
     * @param bool $ignoreVirtual Ignore virtual product
     * @param bool $exclusive     If true, the validation is exclusive : it must be present product in stock and out of stock
     *
     * @return bool false is some products from the cart are out of stock
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function isAllProductsInStock($ignoreVirtual = false, $exclusive = false)
    {
        $product_out_of_stock = 0;
        $product_in_stock = 0;
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
                    $product_out_of_stock++;
                }
                if ((int) $product['quantity_available'] > 0
                    && (!$ignoreVirtual || !$product['is_virtual'])
                ) {
                    $product_in_stock++;
                }

                if ($product_in_stock > 0 && $product_out_of_stock > 0) {
                    return false;
                }
            }
        }

        return true;
    }
}
