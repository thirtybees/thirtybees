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
 * @deprecated 1.5.0.1
 */
define('_CUSTOMIZE_FILE_', 0);
/**
 * @deprecated 1.5.0.1
 */
define('_CUSTOMIZE_TEXTFIELD_', 1);

/**
 * Class ProductCore
 *
 * @since 1.0.0
 */
class ProductCore extends ObjectModel
{
    const CUSTOMIZE_FILE = 0;
    const CUSTOMIZE_TEXTFIELD = 1;
    /**
     * Note:  prefix is "PTYPE" because TYPE_ is used in ObjectModel (definition)
     */
    const PTYPE_SIMPLE = 0;
    const PTYPE_PACK = 1;
    const PTYPE_VIRTUAL = 2;

    // @codingStandardsIgnoreStart
    public static $_taxCalculationMethod = null;
    protected static $_prices = [];
    protected static $_pricesLevel2 = [];
    protected static $_incat = [];
    /**
     * @deprecated 1.0.0
     * @var array $_cart_quantity is deprecated since 1.0.0
     */
    protected static $_cart_quantity = [];
    protected static $_tax_rules_group = [];
    protected static $_cacheFeatures = [];
    protected static $_frontFeaturesCache = [];
    protected static $producPropertiesCache = [];
    /** @var array cache stock data in getStock() method */
    protected static $cacheStock = [];
    /** @var string Tax name */
    public $tax_name;
    /** @var string Tax rate */
    public $tax_rate;
    /** @var int Manufacturer id */
    public $id_manufacturer;
    /** @var int Supplier id */
    public $id_supplier;
    /** @var int default Category id */
    public $id_category_default;
    /** @var int default Shop id */
    public $id_shop_default;
    /** @var string Manufacturer name */
    public $manufacturer_name;
    /** @var string Supplier name */
    public $supplier_name;
    /** @var string Name */
    public $name;
    /** @var string Long description */
    public $description;
    /** @var string Short description */
    public $description_short;
    /** @var int Quantity available */
    public $quantity = 0;
    /** @var int Minimal quantity for add to cart */
    public $minimal_quantity = 1;
    /** @var string available_now */
    public $available_now;
    /** @var string available_later */
    public $available_later;
    /** @var float Price in euros */
    public $price = 0;
    public $specificPrice = 0;
    /** @var float Additional shipping cost */
    public $additional_shipping_cost = 0;
    /** @var float Wholesale Price in euros */
    public $wholesale_price = 0;
    /** @var bool on_sale */
    public $on_sale = false;
    /** @var bool online_only */
    public $online_only = false;
    /** @var string unity */
    public $unity = null;
    /** @var float price for product's unity */
    public $unit_price;
    /** @var float price for product's unity ratio */
    public $unit_price_ratio = 0;
    /** @var float Ecotax */
    public $ecotax = 0;
    /** @var string Reference */
    public $reference;
    /** @var string Supplier Reference */
    public $supplier_reference;
    /** @var string Location */
    public $location;
    /** @var float Width in default width unit */
    public $width = 0;
    /** @var float Height in default height unit */
    public $height = 0;
    /** @var float Depth in default depth unit */
    public $depth = 0;
    /** @var float Weight in default weight unit */
    public $weight = 0;
    /** @var string Ean-13 barcode */
    public $ean13;
    /** @var string Upc barcode */
    public $upc;
    /** @var string Friendly URL */
    public $link_rewrite;
    /** @var string Meta tag description */
    public $meta_description;
    /** @var string Meta tag keywords */
    public $meta_keywords;
    /** @var string Meta tag title */
    public $meta_title;
    /** @var bool Product statuts */
    public $quantity_discount = 0;
    /** @var bool Product customization */
    public $customizable;
    /** @var bool Product is new */
    public $new = null;
    /** @var int Number of uploadable files (concerning customizable products) */
    public $uploadable_files;
    /** @var int Number of text fields */
    public $text_fields;
    /** @var bool Product statuts */
    public $active = true;
    /** @var bool Product statuts */
    public $redirect_type = '';
    /** @var bool Product statuts */
    public $id_product_redirected = 0;
    /** @var bool Product available for order */
    public $available_for_order = true;
    /** @var string Object available order date */
    public $available_date = '0000-00-00';
    /** @var string Enumerated (enum) product condition (new, used, refurbished) */
    public $condition;
    /** @var bool Show price of Product */
    public $show_price = true;
    /** @var bool is the product indexed in the search index? */
    public $indexed = 0;
    /** @var string ENUM('both', 'catalog', 'search', 'none') front office visibility */
    public $visibility;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /*** @var array Tags */
    public $tags;
    /**
     * @var float Base price of the product
     * @deprecated 1.6.0.13
     */
    public $base_price;
    public $id_tax_rules_group = 1;
    /**
     * We keep this variable for retrocompatibility for themes
     *
     * @deprecated 1.5.0
     */
    public $id_color_default = 0;
    /**
     * @since 1.5.0
     * @var bool Tells if the product uses the advanced stock management
     */
    public $advanced_stock_management = 0;
    public $out_of_stock;
    public $depends_on_stock;
    public $isFullyLoaded = false;
    public $cache_is_pack;
    public $cache_has_attachments;
    public $is_virtual;
    public $id_pack_product_attribute;
    public $cache_default_attribute;
    /**
     * @var string If product is populated, this property contain the rewrite link of the default category
     */
    public $category;
    /**
     * @var int tell the type of stock management to apply on the pack
     */
    public $pack_stock_type = 3;
    // @codingStandardsIgnoreEnd

    public $packItems;

    public $productDownload;

    public static $definition = [
        'table'          => 'product',
        'primary'        => 'id_product',
        'multilang'      => true,
        'multilang_shop' => true,
        'fields'         => [
            /* Classic fields */
            'id_shop_default'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_manufacturer'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_supplier'               => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'reference'                 => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 32],
            'supplier_reference'        => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 32],
            'location'                  => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 64],
            'width'                     => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'],
            'height'                    => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'],
            'depth'                     => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'],
            'weight'                    => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'],
            'quantity_discount'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'ean13'                     => ['type' => self::TYPE_STRING, 'validate' => 'isEan13', 'size' => 13],
            'upc'                       => ['type' => self::TYPE_STRING, 'validate' => 'isUpc', 'size' => 12],
            'cache_is_pack'             => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'cache_has_attachments'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'is_virtual'                => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

            /* Shop fields */
            'id_category_default'       => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'],
            'id_tax_rules_group'        => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'],
            'on_sale'                   => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'],
            'online_only'               => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'],
            'ecotax'                    => ['type' => self::TYPE_PRICE, 'shop' => true, 'validate' => 'isPrice'],
            'minimal_quantity'          => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'],
            'price'                     => ['type' => self::TYPE_PRICE, 'shop' => true, 'validate' => 'isPrice', 'required' => true],
            'wholesale_price'           => ['type' => self::TYPE_PRICE, 'shop' => true, 'validate' => 'isPrice'],
            'unity'                     => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isString'],
            'unit_price_ratio'          => ['type' => self::TYPE_FLOAT, 'shop' => true],
            'additional_shipping_cost'  => ['type' => self::TYPE_PRICE, 'shop' => true, 'validate' => 'isPrice'],
            'customizable'              => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'],
            'text_fields'               => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'],
            'uploadable_files'          => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'],
            'active'                    => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'],
            'redirect_type'             => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isString'],
            'id_product_redirected'     => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'],
            'available_for_order'       => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'],
            'available_date'            => ['type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat'],
            'condition'                 => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isGenericName', 'values' => ['new', 'used', 'refurbished'], 'default' => 'new'],
            'show_price'                => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'],
            'indexed'                   => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'],
            'visibility'                => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isProductVisibility', 'values' => ['both', 'catalog', 'search', 'none'], 'default' => 'both'],
            'cache_default_attribute'   => ['type' => self::TYPE_INT, 'shop' => true],
            'advanced_stock_management' => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'],
            'date_add'                  => ['type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'],
            'date_upd'                  => ['type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'],
            'pack_stock_type'           => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'],

            /* Lang fields */
            'meta_description'          => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'meta_keywords'             => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'meta_title'                => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
            'link_rewrite'              => [
                'type'        => self::TYPE_STRING,
                'lang'        => true,
                'validate'    => 'isLinkRewrite',
                'required'    => true,
                'size'        => 128,
                'ws_modifier' => [
                    'http_method' => WebserviceRequest::HTTP_POST,
                    'modifier'    => 'modifierWsLinkRewrite',
                ],
            ],
            'name'                      => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128],
            'description'               => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'description_short'         => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'available_now'             => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'available_later'           => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'IsGenericName', 'size' => 255],
        ],
        'associations'   => [
            'manufacturer'     => ['type' => self::HAS_ONE],
            'supplier'         => ['type' => self::HAS_ONE],
            'default_category' => ['type' => self::HAS_ONE, 'field' => 'id_category_default', 'object' => 'Category'],
            'tax_rules_group'  => ['type' => self::HAS_ONE],
            'categories'       => ['type' => self::BELONGS_TO_MANY, 'object' => 'Category', 'joinTable' => 'category_product'],
            'stock_availables' => ['type' => self::HAS_MANY, 'field' => 'id_product', 'foreignField' => 'id_product', 'object' => 'StockAvailable'],
        ],
    ];

    protected $webserviceParameters = [
        'objectMethods'   => [
            'add'    => 'addWs',
            'update' => 'updateWs',
        ],
        'objectNodeNames' => 'products',
        'fields'          => [
            'id_manufacturer'         => [
                'xlink_resource' => 'manufacturers',
            ],
            'id_supplier'             => [
                'xlink_resource' => 'suppliers',
            ],
            'id_category_default'     => [
                'xlink_resource' => 'categories',
            ],
            'new'                     => [],
            'cache_default_attribute' => [],
            'id_default_image'        => [
                'getter'         => 'getCoverWs',
                'setter'         => 'setCoverWs',
                'xlink_resource' => [
                    'resourceName'    => 'images',
                    'subResourceName' => 'products',
                ],
            ],
            'id_default_combination'  => [
                'getter'         => 'getWsDefaultCombination',
                'setter'         => 'setWsDefaultCombination',
                'xlink_resource' => [
                    'resourceName' => 'combinations',
                ],
            ],
            'id_tax_rules_group'      => [
                'xlink_resource' => [
                    'resourceName' => 'tax_rule_groups',
                ],
            ],
            'position_in_category'    => [
                'getter' => 'getWsPositionInCategory',
                'setter' => 'setWsPositionInCategory',
            ],
            'manufacturer_name'       => [
                'getter' => 'getWsManufacturerName',
                'setter' => false,
            ],
            'quantity'                => [
                'getter' => false,
                'setter' => false,
            ],
            'type'                    => [
                'getter' => 'getWsType',
                'setter' => 'setWsType',
            ],
        ],
        'associations'    => [
            'categories'            => [
                'resource' => 'category',
                'fields'   => [
                    'id' => ['required' => true],
                ],
            ],
            'images'                => [
                'resource' => 'image',
                'fields'   => ['id' => []],
            ],
            'combinations'          => [
                'resource' => 'combination',
                'fields'   => [
                    'id' => ['required' => true],
                ],
            ],
            'product_option_values' => [
                'resource' => 'product_option_value',
                'fields'   => [
                    'id' => ['required' => true],
                ],
            ],
            'product_features'      => [
                'resource' => 'product_feature',
                'fields'   => [
                    'id'               => ['required' => true],
                    'id_feature_value' => [
                        'required'       => true,
                        'xlink_resource' => 'product_feature_values',
                    ],
                ],
            ],
            'tags'                  => [
                'resource' => 'tag',
                'fields'   => [
                    'id' => ['required' => true],
                ],
            ],
            'stock_availables'      => [
                'resource' => 'stock_available',
                'fields'   => [
                    'id'                   => ['required' => true],
                    'id_product_attribute' => ['required' => true],
                ],
                'setter'   => false,
            ],
            'accessories'           => [
                'resource' => 'product',
                'api'      => 'products',
                'fields'   => [
                    'id' => [
                        'required'       => true,
                        'xlink_resource' => 'product',
                    ],
                ],
            ],
            'product_bundle'        => [
                'resource' => 'product',
                'api'      => 'products',
                'fields'   => [
                    'id'       => ['required' => true],
                    'quantity' => [],
                ],
            ],
        ],
    ];

    /**
     * ProductCore constructor.
     *
     * @param null         $idProduct
     * @param bool         $full
     * @param null         $idLang
     * @param null         $idShop
     * @param Context|null $context
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function __construct($idProduct = null, $full = false, $idLang = null, $idShop = null, Context $context = null)
    {
        parent::__construct($idProduct, $idLang, $idShop);
        if ($full && $this->id) {
            if (!$context) {
                $context = Context::getContext();
            }

            $this->isFullyLoaded = $full;
            $this->tax_name = 'deprecated'; // The applicable tax may be BOTH the product one AND the state one (moreover this variable is some deadcode)
            $this->manufacturer_name = Manufacturer::getNameById((int) $this->id_manufacturer);
            $this->supplier_name = Supplier::getNameById((int) $this->id_supplier);
            $address = null;
            if (is_object($context->cart) && $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')} != null) {
                $address = $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
            }

            $this->tax_rate = $this->getTaxesRate(new Address($address));

            $this->new = $this->isNew();

            // Keep base price
            $this->base_price = $this->price;

            $this->price = static::getPriceStatic(
                (int) $this->id,
                false,
                null,
                _TB_PRICE_DATABASE_PRECISION_,
                null,
                false,
                true,
                1,
                false,
                null,
                null,
                null,
                $this->specificPrice
            );
            $this->unit_price = ($this->unit_price_ratio != 0 ?
                round(
                    $this->price / $this->unit_price_ratio,
                    _TB_PRICE_DATABASE_PRECISION_
                ) :
                0
            );
            $this->tags = Tag::getProductTags((int) $this->id);

            $this->loadStockData();
        }

        if ($this->id_category_default) {
            $this->category = Category::getLinkRewrite((int) $this->id_category_default, (int) $idLang);
        }
    }

    /**
     * Returns tax rate.
     *
     * @param Address|null $address
     *
     * @return float The total taxes rate applied to the product
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getTaxesRate(Address $address = null)
    {
        if (!$address || !$address->id_country) {
            $address = Address::initialize();
        }

        $taxManager = TaxManagerFactory::getManager($address, $this->id_tax_rules_group);
        $taxCalculator = $taxManager->getTaxCalculator();

        return $taxCalculator->getTotalRate();
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function isNew()
    {
        $result = Db::getInstance()->executeS(
            '
			SELECT p.id_product
			FROM `'._DB_PREFIX_.'product` p
			'.Shop::addSqlAssociation('product', 'p').'
			WHERE p.id_product = '.(int) $this->id.'
			AND DATEDIFF(
				product_shop.`date_add`,
				DATE_SUB(
					"'.date('Y-m-d').' 00:00:00",
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY
				)
			) > 0
		', true, false
        );

        return count($result) > 0;
    }

    /**
     * Returns product price
     *
     * @param int      $idProduct           Product id
     * @param bool     $usetax              With taxes or not (optional)
     * @param int|null $idProductAttribute  Product attribute id (optional).
     *                                      If set to false, do not apply the combination price impact.
     *                                      NULL does apply the default combination price impact.
     * @param int      $decimals            Number of decimals (optional)
     * @param int|null $divisor             Useful when paying many time without fees (optional)
     * @param bool     $onlyReduc           Returns only the reduction amount
     * @param bool     $usereduc            Set if the returned amount will include reduction
     * @param int      $quantity            Required for quantity discount application (default value: 1)
     * @param bool     $forceAssociatedTax  DEPRECATED - NOT USED Force to apply the associated tax.
     *                                      Only works when the parameter $usetax is true
     * @param int|null $idCustomer          Customer ID (for customer group reduction)
     * @param int|null $idCart              Cart ID. Required when the cookie is not accessible
     *                                      (e.g., inside a payment module, a cron task...)
     * @param int|null $idAddress           Customer address ID. Required for price (tax included)
     *                                      calculation regarding the guest localization
     * @param null     $specificPriceOutput If a specific price applies regarding the previous parameters,
     *                                      this variable is filled with the corresponding SpecificPrice object
     * @param bool     $withEcotax          Insert ecotax in price output.
     * @param bool     $useGroupReduction
     * @param Context  $context
     * @param bool     $useCustomerPrice
     *
     * @return float                        Product price
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getPriceStatic(
        $idProduct,
        $usetax = true,
        $idProductAttribute = null,
        $decimals = _TB_PRICE_DATABASE_PRECISION_,
        $divisor = null,
        $onlyReduc = false,
        $usereduc = true,
        $quantity = 1,
        $forceAssociatedTax = false,
        $idCustomer = null,
        $idCart = null,
        $idAddress = null,
        &$specificPriceOutput = null,
        $withEcotax = true,
        $useGroupReduction = true,
        Context $context = null,
        $useCustomerPrice = true
    ) {
        if (!$context) {
            $context = Context::getContext();
        }

        $curCart = $context->cart;

        if ($divisor !== null) {
            Tools::displayParameterAsDeprecated('divisor');
        }

        if (!Validate::isBool($usetax) || !Validate::isUnsignedId($idProduct)) {
            die(Tools::displayError());
        }

        // Initializations
        $idGroup = null;
        if ($idCustomer) {
            $idGroup = Customer::getDefaultGroupId((int) $idCustomer);
        }
        if (!$idGroup) {
            $idGroup = (int) Group::getCurrent()->id;
        }

        // If there is cart in context or if the specified id_cart is different from the context cart id
        if (!is_object($curCart) || (Validate::isUnsignedInt($idCart) && $idCart && $curCart->id != $idCart)) {
            /*
            * When a user (e.g., guest, customer, Google...) is on PrestaShop, he has already its cart as the global (see /init.php)
            * When a non-user calls directly this method (e.g., payment module...) is on PrestaShop, he does not have already it BUT knows the cart ID
            * When called from the back office, cart ID can be inexistant
            */
            if (!$idCart && !isset($context->employee)) {
                die(Tools::displayError());
            }
            $curCart = new Cart($idCart);
            // Store cart in context to avoid multiple instantiations in BO
            if (!Validate::isLoadedObject($context->cart)) {
                $context->cart = $curCart;
            }
        }

        $cartQuantity = 0;
        if ((int) $idCart) {
            $cacheId = 'Product::getPriceStatic_'.(int) $idProduct.'-'.(int) $idCart;
            if (!Cache::isStored($cacheId) || ($cartQuantity = Cache::retrieve($cacheId) != (int) $quantity)) {
                $sql = 'SELECT SUM(`quantity`)
				FROM `'._DB_PREFIX_.'cart_product`
				WHERE `id_product` = '.(int) $idProduct.'
				AND `id_cart` = '.(int) $idCart;
                $cartQuantity = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
                Cache::store($cacheId, $cartQuantity);
            } else {
                $cartQuantity = Cache::retrieve($cacheId);
            }
        }

        $idCurrency = Validate::isLoadedObject($context->currency) ? (int) $context->currency->id : (int) Configuration::get('PS_CURRENCY_DEFAULT');

        // retrieve address informations
        $idCountry = (int) $context->country->id;
        $idState = 0;
        $zipcode = 0;

        if (!$idAddress && Validate::isLoadedObject($curCart)) {
            $idAddress = $curCart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
        }

        if ($idAddress) {
            $addressInfos = Address::getCountryAndState($idAddress);
            if ($addressInfos['id_country']) {
                $idCountry = (int) $addressInfos['id_country'];
                $idState = (int) $addressInfos['id_state'];
                $zipcode = $addressInfos['postcode'];
            }
        } elseif (isset($context->customer->geoloc_id_country)) {
            $idCountry = (int) $context->customer->geoloc_id_country;
            $idState = (int) $context->customer->id_state;
            $zipcode = $context->customer->postcode;
        }

        if (Tax::excludeTaxeOption()) {
            $usetax = false;
        }

        // @TODO: Use a hook for this. Like:
        //        Hook::exec('isVatExemption', ['address' => &$address]);
        if (Module::isEnabled('vatnumber') && $idAddress) {
            require_once _PS_MODULE_DIR_.'/vatnumber/VATNumberTaxManager.php';

            $address = new Address($idAddress);
            $usetax = $usetax
                      && ! VATNumberTaxManager::isAvailableForThisAddress($address);
        }

        if (is_null($idCustomer) && Validate::isLoadedObject($context->customer)) {
            $idCustomer = $context->customer->id;
        }

        $return = static::priceCalculation(
            $context->shop->id,
            $idProduct,
            $idProductAttribute,
            $idCountry,
            $idState,
            $zipcode,
            $idCurrency,
            $idGroup,
            $quantity,
            $usetax,
            $decimals,
            $onlyReduc,
            $usereduc,
            $withEcotax,
            $specificPriceOutput,
            $useGroupReduction,
            $idCustomer,
            $useCustomerPrice,
            $idCart,
            $cartQuantity
        );

        return $return;
    }

    /**
     * Price calculation / Get product price
     *
     * @param int    $idShop             Shop id
     * @param int    $idProduct          Product id
     * @param int    $idProductAttribute Product attribute id
     * @param int    $idCountry          Country id
     * @param int    $idState            State id
     * @param string $zipcode
     * @param int    $idCurrency         Currency id
     * @param int    $idGroup            Group id
     * @param int    $quantity           Quantity Required for Specific prices : quantity discount application
     * @param bool   $useTax             with (1) or without (0) tax
     * @param int    $decimals           Number of decimals returned
     * @param bool   $onlyReduc          Returns only the reduction amount
     * @param bool   $useReduc           Set if the returned amount will include reduction
     * @param bool   $withEcotax         insert ecotax in price output.
     * @param null   $specificPrice      If a specific price applies regarding the previous parameters,
     *                                   this variable is filled with the corresponding SpecificPrice object
     * @param bool   $useGroupReduction
     * @param int    $idCustomer
     * @param bool   $useCustomerPrice
     * @param int    $idCart
     * @param int    $realQuantity
     *
     * @return float Product price
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function priceCalculation(
        $idShop,
        $idProduct,
        $idProductAttribute,
        $idCountry,
        $idState,
        $zipcode,
        $idCurrency,
        $idGroup,
        $quantity,
        $useTax,
        $decimals,
        $onlyReduc,
        $useReduc,
        $withEcotax,
        &$specificPrice,
        $useGroupReduction,
        $idCustomer = 0,
        $useCustomerPrice = true,
        $idCart = 0,
        $realQuantity = 0
    ) {
        static $address = null;
        static $context = null;

        if ($address === null) {
            $address = new Address();
        }

        if ($context == null) {
            $context = Context::getContext()->cloneContext();
        }

        if ($idShop !== null && $context->shop->id != (int) $idShop) {
            $context->shop = new Shop((int) $idShop);
        }

        if (!$useCustomerPrice) {
            $idCustomer = 0;
        }

        if ($idProductAttribute === null) {
            $idProductAttribute = static::getDefaultAttribute($idProduct);
        }

        $cacheId = (int) $idProduct.'-'.(int) $idShop.'-'.(int) $idCurrency.'-'.(int) $idCountry.'-'.$idState.'-'.$zipcode.'-'.(int) $idGroup.
            '-'.(int) $quantity.'-'.(int) $idProductAttribute.
            '-'.(int) $withEcotax.'-'.(int) $idCustomer.'-'.(int) $useGroupReduction.'-'.(int) $idCart.'-'.(int) $realQuantity.
            '-'.($onlyReduc ? '1' : '0').'-'.($useReduc ? '1' : '0').'-'.($useTax ? '1' : '0').'-'.(int) $decimals;

        // reference parameter is filled before any returns
        $specificPrice = SpecificPrice::getSpecificPrice(
            (int) $idProduct,
            $idShop,
            $idCurrency,
            $idCountry,
            $idGroup,
            $quantity,
            $idProductAttribute,
            $idCustomer,
            $idCart,
            $realQuantity
        );

        if (isset(static::$_prices[$cacheId])) {
            /* Affect reference before returning cache */
            if (isset($specificPrice['price']) && $specificPrice['price'] > 0) {
                $specificPrice['price'] = static::$_prices[$cacheId];
            }

            return static::$_prices[$cacheId];
        }

        // fetch price & attribute price
        $cacheId2 = $idProduct.'-'.$idShop;
        if (!isset(static::$_pricesLevel2[$cacheId2])) {
            $sql = new DbQuery();
            $sql->select('product_shop.`price`, product_shop.`ecotax`');
            $sql->from('product', 'p');
            $sql->innerJoin('product_shop', 'product_shop', '(product_shop.id_product=p.id_product AND product_shop.id_shop = '.(int) $idShop.')');
            $sql->where('p.`id_product` = '.(int) $idProduct);
            if (Combination::isFeatureActive()) {
                $sql->select('IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute, product_attribute_shop.`price` AS attribute_price, product_attribute_shop.default_on');
                $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', '(product_attribute_shop.id_product = p.id_product AND product_attribute_shop.id_shop = '.(int) $idShop.')');
            } else {
                $sql->select('0 as id_product_attribute');
            }

            $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

            if (is_array($res) && count($res)) {
                foreach ($res as $row) {
                    $arrayTmp = [
                        'price'           => $row['price'],
                        'ecotax'          => $row['ecotax'],
                        'attribute_price' => (isset($row['attribute_price']) ? $row['attribute_price'] : null),
                    ];
                    static::$_pricesLevel2[$cacheId2][(int) $row['id_product_attribute']] = $arrayTmp;

                    if (isset($row['default_on']) && $row['default_on'] == 1) {
                        static::$_pricesLevel2[$cacheId2][0] = $arrayTmp;
                    }
                }
            }
        }

        if (!isset(static::$_pricesLevel2[$cacheId2][(int) $idProductAttribute])) {
            return;
        }

        $result = static::$_pricesLevel2[$cacheId2][(int) $idProductAttribute];

        if (!$specificPrice || $specificPrice['price'] < 0) {
            $price = (float) $result['price'];
        } else {
            $price = (float) $specificPrice['price'];
        }
        // convert only if the specific price is in the default currency (id_currency = 0)
        if (!$specificPrice || !($specificPrice['price'] >= 0 && $specificPrice['id_currency'])) {
            $price = Tools::convertPrice($price, $idCurrency);
            if (isset($specificPrice['price']) && $specificPrice['price'] >= 0) {
                $specificPrice['price'] = $price;
            }
        }

        // Attribute price
        if (is_array($result) && (!$specificPrice || !$specificPrice['id_product_attribute'] || $specificPrice['price'] < 0)) {
            $attributePrice = Tools::convertPrice($result['attribute_price'] !== null ? (float) $result['attribute_price'] : 0, $idCurrency);
            // If you want the default combination, please use NULL value instead
            if ($idProductAttribute !== false) {
                $price += $attributePrice;
            }
        }

        // Tax
        $address->id_country = $idCountry;
        $address->id_state = $idState;
        $address->postcode = $zipcode;

        $taxManager = TaxManagerFactory::getManager($address, static::getIdTaxRulesGroupByIdProduct((int) $idProduct, $context));
        $productTaxCalculator = $taxManager->getTaxCalculator();

        // Add Tax
        if ($useTax) {
            $price = $productTaxCalculator->addTaxes($price);
        }

        // Eco Tax
        if (($result['ecotax'] || isset($result['attribute_ecotax'])) && $withEcotax) {
            $ecotax = $result['ecotax'];
            if (isset($result['attribute_ecotax']) && $result['attribute_ecotax'] > 0) {
                $ecotax = $result['attribute_ecotax'];
            }

            if ($idCurrency) {
                $ecotax = Tools::convertPrice($ecotax, $idCurrency);
            }
            if ($useTax) {
                // reinit the tax manager for ecotax handling
                $taxManager = TaxManagerFactory::getManager(
                    $address,
                    (int) Configuration::get('PS_ECOTAX_TAX_RULES_GROUP_ID')
                );
                $ecotaxTaxCalculator = $taxManager->getTaxCalculator();
                $price += $ecotaxTaxCalculator->addTaxes($ecotax);
            } else {
                $price += $ecotax;
            }
        }

        // Reduction
        $specificPriceReduction = 0;
        if (($onlyReduc || $useReduc) && $specificPrice) {
            if ($specificPrice['reduction_type'] == 'amount') {
                $reductionAmount = $specificPrice['reduction'];

                if (!$specificPrice['id_currency']) {
                    $reductionAmount = Tools::convertPrice($reductionAmount, $idCurrency);
                }

                $specificPriceReduction = $reductionAmount;

                // Adjust taxes if required
                if (!$useTax && $specificPrice['reduction_tax']) {
                    if (!$productTaxCalculator->getTotalRate()) {
                        $tax = new Tax(Configuration::get('TB_DEFAULT_SPECIFIC_PRICE_RULE_TAX'));
                        if (Validate::isLoadedObject($tax)) {
                            $specificPriceReduction = round(
                                $specificPriceReduction / (1 + $tax->rate / 100),
                                _TB_PRICE_DATABASE_PRECISION_
                            );
                        }
                    } else {
                        $specificPriceReduction = $productTaxCalculator->removeTaxes($specificPriceReduction);
                    }
                }
                if ($useTax && !$specificPrice['reduction_tax']) {
                    $specificPriceReduction = $productTaxCalculator->addTaxes($specificPriceReduction);
                }
            } else {
                $specificPriceReduction = round(
                    $price * $specificPrice['reduction'],
                    _TB_PRICE_DATABASE_PRECISION_
                );
            }
        }

        if ($useReduc) {
            $price -= $specificPriceReduction;
        }

        // Group reduction
        if ($useGroupReduction) {
            $reductionFromCategory = GroupReduction::getValueForProduct($idProduct, $idGroup);
            if ($reductionFromCategory !== false) {
                $groupReduction = round(
                    $price * $reductionFromCategory,
                    _TB_PRICE_DATABASE_PRECISION_
                );
            } else {
                // Apply group reduction if there is no group reduction for
                // this category.
                $reduc = Group::getReductionByIdGroup($idGroup);
                $groupReduction = ($reduc ?
                    round($price * $reduc / 100, _TB_PRICE_DATABASE_PRECISION_) :
                    0
                );
            }

            $price -= $groupReduction;
        }

        if ($onlyReduc) {
            if ($decimals >= _TB_PRICE_DATABASE_PRECISION_) {
                return round(
                    $specificPriceReduction,
                    _TB_PRICE_DATABASE_PRECISION_
                );
            } else {
                return Tools::ps_round($specificPriceReduction, $decimals);
            }
        }

        if ($decimals >= _TB_PRICE_DATABASE_PRECISION_) {
            $price = round($price, _TB_PRICE_DATABASE_PRECISION_);
        } else {
            $price = Tools::ps_round($price, $decimals);
        }

        if ($price < 0) {
            $price = 0;
        }

        static::$_prices[$cacheId] = $price;

        return static::$_prices[$cacheId];
    }

    /**
     * Get the default attribute for a product
     *
     * @param int  $idProduct
     * @param int  $minimumQuantity
     * @param bool $reset
     *
     * @return int Attributes list
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public static function getDefaultAttribute($idProduct, $minimumQuantity = 0, $reset = false)
    {
        static $combinations = [];

        if (!Combination::isFeatureActive()) {
            return 0;
        }

        if ($reset && isset($combinations[$idProduct])) {
            unset($combinations[$idProduct]);
        }

        if (!isset($combinations[$idProduct])) {
            $combinations[$idProduct] = [];
        }
        if (isset($combinations[$idProduct][$minimumQuantity])) {
            return $combinations[$idProduct][$minimumQuantity];
        }

        $sql = 'SELECT product_attribute_shop.id_product_attribute
				FROM '._DB_PREFIX_.'product_attribute pa
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				WHERE pa.id_product = '.(int) $idProduct;

        $resultNoFilter = Db::getInstance()->getValue($sql);
        if (!$resultNoFilter) {
            $combinations[$idProduct][$minimumQuantity] = 0;

            return 0;
        }

        $sql = 'SELECT product_attribute_shop.id_product_attribute
				FROM '._DB_PREFIX_.'product_attribute pa
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				'.($minimumQuantity > 0 ? static::sqlStock('pa', 'pa') : '').
            ' WHERE product_attribute_shop.default_on = 1 '
            .($minimumQuantity > 0 ? ' AND IFNULL(stock.quantity, 0) >= '.(int) $minimumQuantity : '').
            ' AND pa.id_product = '.(int) $idProduct;
        $result = Db::getInstance()->getValue($sql);

        if (!$result) {
            $sql = 'SELECT product_attribute_shop.id_product_attribute
					FROM '._DB_PREFIX_.'product_attribute pa
					'.Shop::addSqlAssociation('product_attribute', 'pa').'
					'.($minimumQuantity > 0 ? static::sqlStock('pa', 'pa') : '').
                ' WHERE pa.id_product = '.(int) $idProduct
                .($minimumQuantity > 0 ? ' AND IFNULL(stock.quantity, 0) >= '.(int) $minimumQuantity : '');

            $result = Db::getInstance()->getValue($sql);
        }

        if (!$result) {
            $sql = 'SELECT product_attribute_shop.id_product_attribute
					FROM '._DB_PREFIX_.'product_attribute pa
					'.Shop::addSqlAssociation('product_attribute', 'pa').'
					WHERE product_attribute_shop.`default_on` = 1
					AND pa.id_product = '.(int) $idProduct;

            $result = Db::getInstance()->getValue($sql);
        }

        if (!$result) {
            $result = $resultNoFilter;
        }

        $combinations[$idProduct][$minimumQuantity] = $result;

        return $result;
    }

    /**
     * Create JOIN query with 'stock_available' table
     *
     * @param string     $productAlias     Alias of product table
     * @param string|int $productAttribute If string : alias of PA table ; if int : value of PA ; if null : nothing about PA
     * @param bool       $innerJoin        LEFT JOIN or INNER JOIN
     * @param Shop       $shop
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function sqlStock($productAlias, $productAttribute = null, $innerJoin = false, Shop $shop = null)
    {
        $idShop = ($shop !== null ? (int) $shop->id : null);
        $sql = (($innerJoin) ? ' INNER ' : ' LEFT ')
            .'JOIN '._DB_PREFIX_.'stock_available stock
			ON (stock.id_product = '.pSQL($productAlias).'.id_product';

        if (!is_null($productAttribute)) {
            if (!Combination::isFeatureActive()) {
                $sql .= ' AND stock.id_product_attribute = 0';
            } elseif (is_numeric($productAttribute)) {
                $sql .= ' AND stock.id_product_attribute = '.$productAttribute;
            } elseif (is_string($productAttribute)) {
                $sql .= ' AND stock.id_product_attribute = IFNULL(`'.bqSQL($productAttribute).'`.id_product_attribute, 0)';
            }
        }

        $sql .= StockAvailable::addSqlShopRestriction(null, $idShop, 'stock').' )';

        return $sql;
    }

    /**
     * @param int          $idProduct
     * @param Context|null $context
     *
     * @return int|null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getIdTaxRulesGroupByIdProduct($idProduct, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }
        $key = 'product_id_tax_rules_group_'.(int) $idProduct.'_'.(int) $context->shop->id;
        if (!Cache::isStored($key)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                '
							SELECT `id_tax_rules_group`
							FROM `'._DB_PREFIX_.'product_shop`
							WHERE `id_product` = '.(int) $idProduct.' AND id_shop='.(int) $context->shop->id
            );
            Cache::store($key, (int) $result);

            return (int) $result;
        }

        return Cache::retrieve($key);
    }

    /**
     * Fill the variables used for stock management
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function loadStockData()
    {
        if (Validate::isLoadedObject($this)) {
            // By default, the product quantity correspond to the available quantity to sell in the current shop
            $this->quantity = StockAvailable::getQuantityAvailableByProduct($this->id, 0);
            $this->out_of_stock = StockAvailable::outOfStock($this->id);
            $this->depends_on_stock = StockAvailable::dependsOnStock($this->id);
            if (Context::getContext()->shop->getContext() == Shop::CONTEXT_GROUP && Context::getContext()->shop->getContextShopGroup()->share_stock == 1) {
                $this->advanced_stock_management = $this->useAdvancedStockManagement();
            }
        }
    }

    /**
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function useAdvancedStockManagement()
    {
        return Db::getInstance()->getValue(
            '
					SELECT `advanced_stock_management`
					FROM '._DB_PREFIX_.'product_shop
					WHERE id_product='.(int) $this->id.Shop::addSqlRestriction()
        );
    }

    /**
     * @param null $idCustomer
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTaxCalculationMethod($idCustomer = null)
    {
        if (static::$_taxCalculationMethod === null || $idCustomer !== null) {
            static::initPricesComputation($idCustomer);
        }

        return (int) static::$_taxCalculationMethod;
    }

    /**
     * @param int|null $idCustomer
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function initPricesComputation($idCustomer = null)
    {
        if ($idCustomer) {
            $customer = new Customer((int) $idCustomer);
            if (!Validate::isLoadedObject($customer)) {
                die(Tools::displayError());
            }
            static::$_taxCalculationMethod = Group::getPriceDisplayMethod((int) $customer->id_default_group);
            $curCart = Context::getContext()->cart;
            $idAddress = 0;
            if (Validate::isLoadedObject($curCart)) {
                $idAddress = (int) $curCart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
            }

            // @TODO: Use a hook for this. Like:
            //        Hook::exec('isVatExemption', ['address' => &$address]);
            if (Module::isEnabled('vatnumber')
                && static::$_taxCalculationMethod != PS_TAX_EXC) {
                require_once _PS_MODULE_DIR_.'/vatnumber/VATNumberTaxManager.php';

                $address = new Address($idAddress);
                if (VATNumberTaxManager::isAvailableForThisAddress($address)) {
                    static::$_taxCalculationMethod = PS_TAX_EXC;
                }
            }
        } else {
            static::$_taxCalculationMethod = Group::getPriceDisplayMethod(Group::getCurrent()->id);
        }
    }

    /**
     * For a given id_product and id_product_attribute, return available date
     *
     * @param int $idProduct
     * @param int $idProductAttribute Optional
     *
     * @return string/null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getAvailableDate($idProduct, $idProductAttribute = null)
    {
        $sql = 'SELECT';

        if ($idProductAttribute === null) {
            $sql .= ' p.`available_date`';
        } else {
            $sql .= ' IF(pa.`available_date` = "0000-00-00", p.`available_date`, pa.`available_date`) AS available_date';
        }

        $sql .= ' FROM `'._DB_PREFIX_.'product` p';

        if ($idProductAttribute !== null) {
            $sql .= ' LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (pa.`id_product` = p.`id_product`)';
        }

        $sql .= Shop::addSqlAssociation('product', 'p');

        if ($idProductAttribute !== null) {
            $sql .= Shop::addSqlAssociation('product_attribute', 'pa');
        }

        $sql .= ' WHERE p.`id_product` = '.(int) $idProduct;

        if ($idProductAttribute !== null) {
            $sql .= ' AND pa.`id_product` = '.(int) $idProduct.' AND pa.`id_product_attribute` = '.(int) $idProductAttribute;
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

        if ($result == '0000-00-00') {
            $result = null;
        }

        return $result;
    }

    /**
     * @param int  $idProduct
     * @param bool $isVirtual
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function updateIsVirtual($idProduct, $isVirtual = true)
    {
        Db::getInstance()->update(
            'product', [
            'is_virtual' => (bool) $isVirtual,
        ], 'id_product = '.(int) $idProduct
        );
    }

    /**
     * Get all available products
     *
     * @param int          $idLang   Language id
     * @param int          $start    Start number
     * @param int          $limit    Number of products to return
     * @param string       $orderBy  Field for ordering
     * @param string       $orderWay Way for ordering (ASC or DESC)
     *
     * @param bool         $idCategory
     * @param bool         $onlyActive
     * @param Context|null $context
     *
     * @return array Products details
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProducts(
        $idLang,
        $start,
        $limit,
        $orderBy,
        $orderWay,
        $idCategory = false,
        $onlyActive = false,
        Context $context = null
    ) {
        if (!$context) {
            $context = Context::getContext();
        }

        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        if (!Validate::isOrderBy($orderBy) || !Validate::isOrderWay($orderWay)) {
            die(Tools::displayError());
        }
        if ($orderBy == 'id_product' || $orderBy == 'price' || $orderBy == 'date_add' || $orderBy == 'date_upd') {
            $orderByPrefix = 'p';
        } elseif ($orderBy == 'name') {
            $orderByPrefix = 'pl';
        } elseif ($orderBy == 'position') {
            $orderByPrefix = 'c';
        }

        if (strpos($orderBy, '.') > 0) {
            $orderBy = explode('.', $orderBy);
            $orderByPrefix = $orderBy[0];
            $orderBy = $orderBy[1];
        }
        $sql = 'SELECT p.*, product_shop.*, pl.* , m.`name` AS manufacturer_name, s.`name` AS supplier_name
				FROM `'._DB_PREFIX_.'product` p
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` '.Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
				LEFT JOIN `'._DB_PREFIX_.'supplier` s ON (s.`id_supplier` = p.`id_supplier`)'.
            ($idCategory ? 'LEFT JOIN `'._DB_PREFIX_.'category_product` c ON (c.`id_product` = p.`id_product`)' : '').'
				WHERE pl.`id_lang` = '.(int) $idLang.
            ($idCategory ? ' AND c.`id_category` = '.(int) $idCategory : '').
            ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').
            ($onlyActive ? ' AND product_shop.`active` = 1' : '').'
				ORDER BY '.(isset($orderByPrefix) ? pSQL($orderByPrefix).'.' : '').'`'.pSQL($orderBy).'` '.pSQL($orderWay).
            ($limit > 0 ? ' LIMIT '.(int) $start.','.(int) $limit : '');
        $rq = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if ($orderBy == 'price') {
            Tools::orderbyPrice($rq, $orderWay);
        }

        foreach ($rq as &$row) {
            $row = static::getTaxesInformations($row);
        }

        return ($rq);
    }

    /**
     * @param              $row
     * @param Context|null $context
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getTaxesInformations($row, Context $context = null)
    {
        static $address = null;

        if ($context === null) {
            $context = Context::getContext();
        }
        if ($address === null) {
            $address = new Address();
        }

        $address->id_country = (int) $context->country->id;
        $address->id_state = 0;
        $address->postcode = 0;

        $taxManager = TaxManagerFactory::getManager($address, static::getIdTaxRulesGroupByIdProduct((int) $row['id_product'], $context));
        $row['rate'] = $taxManager->getTaxCalculator()->getTotalRate();
        $row['tax_name'] = $taxManager->getTaxCalculator()->getTaxesName();

        return $row;
    }

    /**
     * @param int          $idLang
     * @param Context|null $context
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getSimpleProducts($idLang, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        $sql = 'SELECT p.`id_product`, pl.`name`
				FROM `'._DB_PREFIX_.'product` p
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` '.Shop::addSqlRestrictionOnLang('pl').')
				WHERE pl.`id_lang` = '.(int) $idLang.'
				'.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
				ORDER BY pl.`name`';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * @param int $idProductAttribute
     * @param int $idLang
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCombinationImageById($idProductAttribute, $idLang)
    {
        if (!Combination::isFeatureActive() || !$idProductAttribute) {
            return false;
        }

        $result = Db::getInstance()->executeS(
            '
			SELECT pai.`id_image`, pai.`id_product_attribute`, il.`legend`
			FROM `'._DB_PREFIX_.'product_attribute_image` pai
			LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (il.`id_image` = pai.`id_image`)
			LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_image` = pai.`id_image`)
			WHERE pai.`id_product_attribute` = '.(int) $idProductAttribute.' AND il.`id_lang` = '.(int) $idLang.' ORDER BY i.`position` LIMIT 1'
        );

        if (!$result) {
            return false;
        }

        return $result[0];
    }

    /**
     * Get new products
     *
     * @param int     $idLang     Language id
     * @param int     $pageNumber Start from (optional)
     * @param int     $nbProducts Number of products to return (optional)
     * @param bool    $count
     * @param null    $orderBy
     * @param null    $orderWay
     * @param Context $context
     *
     * @return array New products
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public static function getNewProducts($idLang, $pageNumber = 0, $nbProducts = 10, $count = false, $orderBy = null, $orderWay = null, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        if ($pageNumber < 0) {
            $pageNumber = 0;
        }
        if ($nbProducts < 1) {
            $nbProducts = 10;
        }
        if (empty($orderBy) || $orderBy == 'position') {
            $orderBy = 'date_add';
        }
        if (empty($orderWay)) {
            $orderWay = 'DESC';
        }
        if ($orderBy == 'id_product' || $orderBy == 'price' || $orderBy == 'date_add' || $orderBy == 'date_upd') {
            $orderByPrefix = 'product_shop';
        } elseif ($orderBy == 'name') {
            $orderByPrefix = 'pl';
        }
        if (!Validate::isOrderBy($orderBy) || !Validate::isOrderWay($orderWay)) {
            die(Tools::displayError());
        }

        $sqlGroups = '';
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sqlGroups = ' AND EXISTS(SELECT 1 FROM `'._DB_PREFIX_.'category_product` cp
				JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').')
				WHERE cp.`id_product` = p.`id_product`)';
        }

        if (strpos($orderBy, '.') > 0) {
            $orderBy = explode('.', $orderBy);
            $orderByPrefix = $orderBy[0];
            $orderBy = $orderBy[1];
        }

        if ($count) {
            $sql = 'SELECT COUNT(p.`id_product`) AS nb
					FROM `'._DB_PREFIX_.'product` p
					'.Shop::addSqlAssociation('product', 'p').'
					WHERE product_shop.`active` = 1
					AND product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'"
					'.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
					'.$sqlGroups;

            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }

        $sql = new DbQuery();
        $sql->select(
            'p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
			pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`, image_shop.`id_image` id_image, il.`legend`, m.`name` AS manufacturer_name,
			product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'" as new'
        );

        $sql->from('product', 'p');
        $sql->join(Shop::addSqlAssociation('product', 'p'));
        $sql->leftJoin(
            'product_lang',
            'pl',
            'p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl')
        );
        $sql->leftJoin('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $context->shop->id);
        $sql->leftJoin('image_lang', 'il', 'image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $idLang);
        $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');

        $sql->where('product_shop.`active` = 1');
        if ($front) {
            $sql->where('product_shop.`visibility` IN ("both", "catalog")');
        }
        $sql->where('product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'"');
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sql->where(
                'EXISTS(SELECT 1 FROM `'._DB_PREFIX_.'category_product` cp
				JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').')
				WHERE cp.`id_product` = p.`id_product`)'
            );
        }

        $sql->orderBy((isset($orderByPrefix) ? pSQL($orderByPrefix).'.' : '').'`'.pSQL($orderBy).'` '.pSQL($orderWay));
        $sql->limit($nbProducts, $pageNumber * $nbProducts);

        if (Combination::isFeatureActive()) {
            $sql->select('product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute');
            $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', 'p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $context->shop->id);
        }
        $sql->join(static::sqlStock('p', 0));

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$result) {
            return false;
        }

        if ($orderBy == 'price') {
            Tools::orderbyPrice($result, $orderWay);
        }

        $productsIds = [];
        foreach ($result as $row) {
            $productsIds[] = $row['id_product'];
        }
        // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
        static::cacheFrontFeatures($productsIds, $idLang);

        return static::getProductsProperties((int) $idLang, $result);
    }

    /**
     * @param array $productIds
     * @param int   $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cacheFrontFeatures($productIds, $idLang)
    {
        if (!Feature::isFeatureActive()) {
            return;
        }

        $productImplode = [];
        foreach ($productIds as $idProduct) {
            if ((int) $idProduct && !array_key_exists($idProduct.'-'.$idLang, static::$_cacheFeatures)) {
                $productImplode[] = (int) $idProduct;
            }
        }
        if (!count($productImplode)) {
            return;
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT id_product, name, value, pf.id_feature
		FROM '._DB_PREFIX_.'feature_product pf
		LEFT JOIN '._DB_PREFIX_.'feature_lang fl ON (fl.id_feature = pf.id_feature AND fl.id_lang = '.(int) $idLang.')
		LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.id_feature_value = pf.id_feature_value AND fvl.id_lang = '.(int) $idLang.')
		LEFT JOIN '._DB_PREFIX_.'feature f ON (f.id_feature = pf.id_feature)
		'.Shop::addSqlAssociation('feature', 'f').'
		WHERE `id_product` IN ('.implode($productImplode, ',').')
		ORDER BY f.position ASC'
        );

        foreach ($result as $row) {
            if (!array_key_exists($row['id_product'].'-'.$idLang, static::$_frontFeaturesCache)) {
                static::$_frontFeaturesCache[$row['id_product'].'-'.$idLang] = [];
            }
            if (!isset(static::$_frontFeaturesCache[$row['id_product'].'-'.$idLang][$row['id_feature']])) {
                static::$_frontFeaturesCache[$row['id_product'].'-'.$idLang][$row['id_feature']] = $row;
            }
        }
    }

    /**
     * @param int   $idLang
     * @param array $queryResult
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getProductsProperties($idLang, $queryResult)
    {
        $resultsArray = [];

        if (is_array($queryResult)) {
            foreach ($queryResult as $row) {
                if ($row2 = static::getProductProperties($idLang, $row)) {
                    $resultsArray[] = $row2;
                }
            }
        }

        return $resultsArray;
    }

    /**
     * @param int          $idLang
     * @param array        $row
     * @param Context|null $context
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getProductProperties($idLang, $row, Context $context = null)
    {
        if (!$row['id_product']) {
            return false;
        }

        if ($context == null) {
            $context = Context::getContext();
        }

        $idProductAttribute = $row['id_product_attribute'] = (!empty($row['id_product_attribute']) ? (int) $row['id_product_attribute'] : null);

        // Product::getDefaultAttribute is only called if id_product_attribute is missing from the SQL query at the origin of it:
        // consider adding it in order to avoid unnecessary queries
        $row['allow_oosp'] = static::isAvailableWhenOutOfStock($row['out_of_stock']);
        if (Combination::isFeatureActive() && $idProductAttribute === null
            && ((isset($row['cache_default_attribute']) && ($ipaDefault = $row['cache_default_attribute']) !== null)
                || ($ipaDefault = static::getDefaultAttribute($row['id_product'], !$row['allow_oosp'])))
        ) {
            $idProductAttribute = $row['id_product_attribute'] = $ipaDefault;
        }
        if (!Combination::isFeatureActive() || !isset($row['id_product_attribute'])) {
            $idProductAttribute = $row['id_product_attribute'] = 0;
        }

        // Tax
        $usetax = Tax::excludeTaxeOption();

        $cacheKey = $row['id_product'].'-'.$idProductAttribute.'-'.$idLang.'-'.(int) $usetax;
        if (isset($row['id_product_pack'])) {
            $cacheKey .= '-pack'.$row['id_product_pack'];
        }

        if (isset(static::$producPropertiesCache[$cacheKey])) {
            return array_merge($row, static::$producPropertiesCache[$cacheKey]);
        }

        // Datas
        if (!isset($row['id_category_default']) && $row['id_category_default']) {
            $row['id_category_default'] = (int) Db::getInstance()->getValue(
                (new DbQuery())
                    ->select('product_shop.`id_category_default`')
                    ->from('product', 'p')
                    ->join(Shop::addSqlAssociation('product', 'p'))
                    ->where('p.`id_product` = '.(int) $row['id_product'])
            );
            if (!$row['id_category_default']) {
                $row['id_category_default'] = Context::getContext()->shop->id_category;
            }
        }
        $row['category'] = Category::getLinkRewrite((int) $row['id_category_default'], (int) $idLang);
        $row['link'] = $context->link->getProductLink((int) $row['id_product'], $row['link_rewrite'], $row['category'], $row['ean13']);

        $row['attribute_price'] = 0;
        if ($idProductAttribute) {
            $row['attribute_price'] = (float) static::getProductAttributePrice($idProductAttribute);
        }

        $row['price_tax_exc'] = static::getPriceStatic(
            (int) $row['id_product'],
            false,
            $idProductAttribute
        );
        $row['price'] = static::getPriceStatic(
            (int) $row['id_product'],
            true,
            $idProductAttribute
        );
        $row['price_without_reduction'] = static::getPriceStatic(
            (int) $row['id_product'],
            static::$_taxCalculationMethod != PS_TAX_EXC,
            $idProductAttribute,
            _TB_PRICE_DATABASE_PRECISION_,
            null,
            false,
            false
        );
        $row['reduction'] = static::getPriceStatic(
            (int) $row['id_product'],
            static::$_taxCalculationMethod != PS_TAX_EXC,
            $idProductAttribute,
            _TB_PRICE_DATABASE_PRECISION_,
            null,
            true,
            true,
            1,
            true,
            null,
            null,
            null,
            $specificPrices
        );

        $row['specific_prices'] = $specificPrices;

        $row['quantity'] = static::getQuantity(
            (int) $row['id_product'],
            0,
            isset($row['cache_is_pack']) ? $row['cache_is_pack'] : null
        );

        $row['quantity_all_versions'] = $row['quantity'];

        if ($row['id_product_attribute']) {
            $row['quantity'] = static::getQuantity(
                (int) $row['id_product'],
                $idProductAttribute,
                isset($row['cache_is_pack']) ? $row['cache_is_pack'] : null
            );
        }

        $row['id_image'] = static::defineProductImage($row, $idLang);
        $row['features'] = static::getFrontFeaturesStatic((int) $idLang, $row['id_product']);

        $row['attachments'] = [];
        if (!isset($row['cache_has_attachments']) || $row['cache_has_attachments']) {
            $row['attachments'] = static::getAttachmentsStatic((int) $idLang, $row['id_product']);
        }

        $row['virtual'] = ((!isset($row['is_virtual']) || $row['is_virtual']) ? 1 : 0);

        // Pack management
        $row['pack'] = (!isset($row['cache_is_pack']) ? Pack::isPack($row['id_product']) : (int) $row['cache_is_pack']);
        $row['packItems'] = $row['pack'] ? Pack::getItemTable($row['id_product'], $idLang) : [];
        $row['nopackprice'] = $row['pack'] ? Pack::noPackPrice($row['id_product']) : 0;
        if ($row['pack'] && !Pack::isInStock($row['id_product'])) {
            $row['quantity'] = 0;
        }

        $row['customization_required'] = false;
        if (isset($row['customizable']) && $row['customizable'] && Customization::isFeatureActive()) {
            if (count(static::getRequiredCustomizableFieldsStatic((int) $row['id_product']))) {
                $row['customization_required'] = true;
            }
        }

        $row = static::getTaxesInformations($row, $context);
        static::$producPropertiesCache[$cacheKey] = $row;

        return static::$producPropertiesCache[$cacheKey];
    }

    /**
     * @param int $outOfStock
     *
     * @return bool|int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isAvailableWhenOutOfStock($outOfStock)
    {
        // @TODO 1.5.0 Update of STOCK_MANAGEMENT & ORDER_OUT_OF_STOCK
        static $psStockManagement = null;
        if ($psStockManagement === null) {
            $psStockManagement = Configuration::get('PS_STOCK_MANAGEMENT');
        }

        if (!$psStockManagement) {
            return true;
        } else {
            static $psOrderOutOfStock = null;
            if ($psOrderOutOfStock === null) {
                $psOrderOutOfStock = Configuration::get('PS_ORDER_OUT_OF_STOCK');
            }

            return (int) $outOfStock == 2 ? (int) $psOrderOutOfStock : (int) $outOfStock;
        }
    }

    /**
     * @deprecated 1.0.0 Use Combination::getPrice
     *
     * @param int $idProductAttribute
     *
     * @return float
     * @throws PrestaShopException
     */
    public static function getProductAttributePrice($idProductAttribute)
    {
        return Combination::getPrice($idProductAttribute);
    }

    /**
     * Get available product quantities
     *
     * @param int  $idProduct          Product id
     * @param int  $idProductAttribute Product attribute id (optional)
     *
     * @param null $cacheIsPack
     *
     * @return int Available quantities
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getQuantity($idProduct, $idProductAttribute = null, $cacheIsPack = null)
    {
        if ((int) $cacheIsPack || ($cacheIsPack === null && Pack::isPack((int) $idProduct))) {
            if (!Pack::isInStock((int) $idProduct)) {
                return 0;
            }
        }

        return (StockAvailable::getQuantityAvailableByProduct($idProduct, $idProductAttribute));
    }

    /**
     * @param array $row
     * @param int   $idLang
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function defineProductImage($row, $idLang)
    {
        if (isset($row['id_image']) && $row['id_image']) {
            return $row['id_product'].'-'.$row['id_image'];
        }

        return Language::getIsoById((int) $idLang).'-default';
    }

    /**
     * @param int $idLang
     * @param int $idProduct
     *
     * @return array|mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFrontFeaturesStatic($idLang, $idProduct)
    {
        if (!Feature::isFeatureActive()) {
            return [];
        }
        if (!array_key_exists($idProduct.'-'.$idLang, static::$_frontFeaturesCache)) {
            static::$_frontFeaturesCache[$idProduct.'-'.$idLang] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
				SELECT name, value, pf.id_feature
				FROM '._DB_PREFIX_.'feature_product pf
				LEFT JOIN '._DB_PREFIX_.'feature_lang fl ON (fl.id_feature = pf.id_feature AND fl.id_lang = '.(int) $idLang.')
				LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.id_feature_value = pf.id_feature_value AND fvl.id_lang = '.(int) $idLang.')
				LEFT JOIN '._DB_PREFIX_.'feature f ON (f.id_feature = pf.id_feature AND fl.id_lang = '.(int) $idLang.')
				'.Shop::addSqlAssociation('feature', 'f').'
				WHERE pf.id_product = '.(int) $idProduct.'
				ORDER BY f.position ASC'
            );
        }

        return static::$_frontFeaturesCache[$idProduct.'-'.$idLang];
    }

    /**
     * @param int $idLang
     * @param int $idProduct
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAttachmentsStatic($idLang, $idProduct)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT *
		FROM '._DB_PREFIX_.'product_attachment pa
		LEFT JOIN '._DB_PREFIX_.'attachment a ON a.id_attachment = pa.id_attachment
		LEFT JOIN '._DB_PREFIX_.'attachment_lang al ON (a.id_attachment = al.id_attachment AND al.id_lang = '.(int) $idLang.')
		WHERE pa.id_product = '.(int) $idProduct
        );
    }

    /**
     * @param int $id
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getRequiredCustomizableFieldsStatic($id)
    {
        if (!$id || !Customization::isFeatureActive()) {
            return [];
        }

        return Db::getInstance()->executeS(
            '
			SELECT `id_customization_field`, `type`
			FROM `'._DB_PREFIX_.'customization_field`
			WHERE `id_product` = '.(int) $id.'
			AND `required` = 1'
        );
    }

    /**
     * Get a random special
     *
     * @param int     $idLang Language id
     * @param bool    $beginning
     * @param bool    $ending
     * @param Context $context
     *
     * @return array|bool Special
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getRandomSpecial($idLang, $beginning = false, $ending = false, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        $currentDate = date('Y-m-d H:i:00');
        $productReductions = static::_getProductIdByDate((!$beginning ? $currentDate : $beginning), (!$ending ? $currentDate : $ending), $context, true);

        if ($productReductions) {
            $idsProducts = '';
            foreach ($productReductions as $productReduction) {
                $idsProducts .= '('.(int) $productReduction['id_product'].','.($productReduction['id_product_attribute'] ? (int) $productReduction['id_product_attribute'] : '0').'),';
            }

            $idsProducts = rtrim($idsProducts, ',');
            Db::getInstance()->execute('CREATE TEMPORARY TABLE IF NOT EXISTS `'._DB_PREFIX_.'product_reductions` (id_product INT UNSIGNED NOT NULL DEFAULT 0, id_product_attribute INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=MEMORY', false);
            if ($idsProducts) {
                Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'product_reductions` VALUES '.$idsProducts, false);
            }

            $groups = FrontController::getCurrentCustomerGroups();
            $sqlGroups = ' AND EXISTS(SELECT 1 FROM `'._DB_PREFIX_.'category_product` cp
				JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').')
				WHERE cp.`id_product` = p.`id_product`)';

            // Please keep 2 distinct queries because RAND() is an awful way to achieve this result
            $sql = 'SELECT product_shop.id_product, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute
					FROM
					`'._DB_PREFIX_.'product_reductions` pr,
					`'._DB_PREFIX_.'product` p
					'.Shop::addSqlAssociation('product', 'p').'
					LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop
				   		ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $context->shop->id.')
					WHERE p.id_product=pr.id_product AND (pr.id_product_attribute = 0 OR product_attribute_shop.id_product_attribute = pr.id_product_attribute) AND product_shop.`active` = 1
						'.$sqlGroups.'
					'.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
					ORDER BY RAND()';

            $result = Db::getInstance()->getRow($sql);

            Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.'product_reductions`', false);

            if (!$idProduct = $result['id_product']) {
                return false;
            }

            // no group by needed : there's only one attribute with cover=1 for a given id_product + shop
            $sql = 'SELECT p.*, product_shop.*, stock.`out_of_stock` out_of_stock, pl.`description`, pl.`description_short`,
						pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`,
						p.`ean13`, p.`upc`, image_shop.`id_image` id_image, il.`legend`,
						DATEDIFF(product_shop.`date_add`, DATE_SUB("'.date('Y-m-d').' 00:00:00",
						INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
							DAY)) > 0 AS new
					FROM `'._DB_PREFIX_.'product` p
					LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
						p.`id_product` = pl.`id_product`
						AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').'
					)
					'.Shop::addSqlAssociation('product', 'p').'
					LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
						ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $context->shop->id.')
					LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $idLang.')
					'.static::sqlStock('p', 0).'
					WHERE p.id_product = '.(int) $idProduct;

            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
            if (!$row) {
                return false;
            }

            $row['id_product_attribute'] = (int) $result['id_product_attribute'];

            return static::getProductProperties($idLang, $row);
        } else {
            return false;
        }
    }

    /**
     * @param              $beginning
     * @param              $ending
     * @param Context|null $context
     * @param bool         $withCombination
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function _getProductIdByDate($beginning, $ending, Context $context = null, $withCombination = false)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $idAddress = $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
        $ids = Address::getCountryAndState($idAddress);
        $idCountry = $ids['id_country'] ? (int) $ids['id_country'] : (int) Configuration::get('PS_COUNTRY_DEFAULT');

        return SpecificPrice::getProductIdByDate(
            $context->shop->id,
            $context->currency->id,
            $idCountry,
            $context->customer->id_default_group,
            $beginning,
            $ending,
            0,
            $withCombination
        );
    }

    /**
     * Get prices drop
     *
     * @param int     $idLang     Language id
     * @param int     $pageNumber Start from (optional)
     * @param int     $nbProducts Number of products to return (optional)
     * @param bool    $count      Only in order to get total number (optional)
     *
     * @param null    $orderBy
     * @param null    $orderWay
     * @param bool    $beginning
     * @param bool    $ending
     * @param Context $context
     *
     * @return array Prices drop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getPricesDrop(
        $idLang,
        $pageNumber = 0,
        $nbProducts = 10,
        $count = false,
        $orderBy = null,
        $orderWay = null,
        $beginning = false,
        $ending = false,
        Context $context = null
    ) {
        if (!Validate::isBool($count)) {
            die(Tools::displayError());
        }

        if (!$context) {
            $context = Context::getContext();
        }
        if ($pageNumber < 0) {
            $pageNumber = 0;
        }
        if ($nbProducts < 1) {
            $nbProducts = 10;
        }
        if (empty($orderBy) || $orderBy == 'position') {
            $orderBy = 'price';
        }
        if (empty($orderWay)) {
            $orderWay = 'DESC';
        }
        if ($orderBy == 'id_product' || $orderBy == 'price' || $orderBy == 'date_add' || $orderBy == 'date_upd') {
            $orderByPrefix = 'product_shop';
        } elseif ($orderBy == 'name') {
            $orderByPrefix = 'pl';
        }
        if (!Validate::isOrderBy($orderBy) || !Validate::isOrderWay($orderWay)) {
            die(Tools::displayError());
        }
        $currentDate = date('Y-m-d H:i:00');
        $idsProduct = static::_getProductIdByDate((!$beginning ? $currentDate : $beginning), (!$ending ? $currentDate : $ending), $context);

        $tabIdProduct = [];
        foreach ($idsProduct as $product) {
            if (is_array($product)) {
                $tabIdProduct[] = (int) $product['id_product'];
            } else {
                $tabIdProduct[] = (int) $product;
            }
        }

        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        $sqlGroups = '';
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sqlGroups = ' AND EXISTS(SELECT 1 FROM `'._DB_PREFIX_.'category_product` cp
				JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').')
				WHERE cp.`id_product` = p.`id_product`)';
        }

        if ($count) {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                '
			SELECT COUNT(DISTINCT p.`id_product`)
			FROM `'._DB_PREFIX_.'product` p
			'.Shop::addSqlAssociation('product', 'p').'
			WHERE product_shop.`active` = 1
			AND product_shop.`show_price` = 1
			'.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
			'.((!$beginning && !$ending) ? 'AND p.`id_product` IN('.((is_array($tabIdProduct) && count($tabIdProduct)) ? implode(', ', $tabIdProduct) : 0).')' : '').'
			'.$sqlGroups
            );
        }

        if (strpos($orderBy, '.') > 0) {
            $orderBy = explode('.', $orderBy);
            $orderBy = pSQL($orderBy[0]).'.`'.pSQL($orderBy[1]).'`';
        }

        $sql = '
		SELECT
			p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`available_now`, pl.`available_later`,
			IFNULL(product_attribute_shop.id_product_attribute, 0) id_product_attribute,
			pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`,
			pl.`name`, image_shop.`id_image` id_image, il.`legend`, m.`name` AS manufacturer_name,
			DATEDIFF(
				p.`date_add`,
				DATE_SUB(
					"'.date('Y-m-d').' 00:00:00",
					INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY
				)
			) > 0 AS new
		FROM `'._DB_PREFIX_.'product` p
		'.Shop::addSqlAssociation('product', 'p').'
		LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop
			ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $context->shop->id.')
		'.static::sqlStock('p', 0, false, $context->shop).'
		LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
			p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').'
		)
		LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
			ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $context->shop->id.')
		LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $idLang.')
		LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
		WHERE product_shop.`active` = 1
		AND product_shop.`show_price` = 1
		'.($front ? ' AND p.`visibility` IN ("both", "catalog")' : '').'
		'.((!$beginning && !$ending) ? ' AND p.`id_product` IN ('.((is_array($tabIdProduct) && count($tabIdProduct)) ? implode(', ', $tabIdProduct) : 0).')' : '').'
		'.$sqlGroups.'
		ORDER BY '.(isset($orderByPrefix) ? pSQL($orderByPrefix).'.' : '').pSQL($orderBy).' '.pSQL($orderWay).'
		LIMIT '.(int) ($pageNumber * $nbProducts).', '.(int) $nbProducts;

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$result) {
            return false;
        }

        if ($orderBy == 'price') {
            Tools::orderbyPrice($result, $orderWay);
        }

        return static::getProductsProperties($idLang, $result);
    }

    /**
     * @param string   $idProduct
     * @param int|null $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProductCategoriesFull($idProduct = '', $idLang = null)
    {
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }

        $ret = [];
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT cp.`id_category`, cl.`name`, cl.`link_rewrite` FROM `'._DB_PREFIX_.'category_product` cp
			LEFT JOIN `'._DB_PREFIX_.'category` c ON (c.id_category = cp.id_category)
			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (cp.`id_category` = cl.`id_category`'.Shop::addSqlRestrictionOnLang('cl').')
			'.Shop::addSqlAssociation('category', 'c').'
			WHERE cp.`id_product` = '.(int) $idProduct.'
				AND cl.`id_lang` = '.(int) $idLang
        );

        foreach ($row as $val) {
            $ret[$val['id_category']] = $val;
        }

        return $ret;
    }

    /**
     * @param float        $price
     * @param bool         $currency
     * @param Context|null $context
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function convertAndFormatPrice($price, $currency = false, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }
        if (!$currency) {
            $currency = $context->currency;
        }

        return Tools::displayPrice(Tools::convertPrice($price, $currency), $currency);
    }

    /**
     * @param int          $idProduct
     * @param int          $quantity
     * @param Context|null $context
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isDiscounted($idProduct, $quantity = 1, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $idGroup = $context->customer->id_default_group;
        $cartQuantity = !$context->cart ? 0 : Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
			SELECT SUM(`quantity`)
			FROM `'._DB_PREFIX_.'cart_product`
			WHERE `id_product` = '.(int) $idProduct.' AND `id_cart` = '.(int) $context->cart->id
        );
        $quantity = $cartQuantity ? $cartQuantity : $quantity;

        $idCurrency = (int) $context->currency->id;
        $ids = Address::getCountryAndState((int) $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $idCountry = $ids['id_country'] ? (int) $ids['id_country'] : (int) Configuration::get('PS_COUNTRY_DEFAULT');

        return (bool) SpecificPrice::getSpecificPrice((int) $idProduct, $context->shop->id, $idCurrency, $idCountry, $idGroup, $quantity, null, 0, 0, $quantity);
    }

    /**
     * Display price with right format and currency
     *
     * @param array  $params Params
     * @param Smarty $smarty Smarty object
     *
     * @return string Price with right format and currency
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function convertPrice($params, $smarty)
    {
        return Tools::displayPrice($params['price'], Context::getContext()->currency);
    }

    /**
     * Convert price with currency
     *
     * @param array  $params
     * @param Smarty $smarty DEPRECATED
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function convertPriceWithCurrency($params, $smarty)
    {
        return Tools::displayPrice($params['price'], $params['currency'], false);
    }

    /**
     * @param array  $params
     * @param Smarty $smarty
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function displayWtPrice($params, $smarty)
    {
        return Tools::displayPrice($params['p'], Context::getContext()->currency);
    }

    /**
     * Display WT price with currency
     *
     * @param array  $params
     * @param Smarty $smarty DEPRECATED
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function displayWtPriceWithCurrency($params, $smarty)
    {
        return Tools::displayPrice($params['price'], $params['currency'], false);
    }

    /**
     * It's not possible to use this method with new stockManager and stockAvailable features
     * Now this method do nothing
     *
     * @see        StockManager if you want to manage real stock
     * @see        StockAvailable if you want to manage available quantities for sale on your shop(s)
     *
     * @deprecated 1.0.0
     * @return false
     */
    public static function updateQuantity()
    {
        Tools::displayAsDeprecated();

        return false;
    }

    /**
     * It's not possible to use this method with new stockManager and stockAvailable features
     * Now this method do nothing
     *
     * @deprecated 1.0.0
     * @see        StockManager if you want to manage real stock
     * @see        StockAvailable if you want to manage available quantities for sale on your shop(s)
     * @return false
     */
    public static function reinjectQuantities()
    {
        Tools::displayAsDeprecated();

        return false;
    }

    /**
     * @param array $products
     * @param bool  $haveStock
     *
     * @return array|bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAttributesColorList(array $products, $haveStock = true)
    {
        if (!count($products)) {
            return [];
        }

        $idLang = Context::getContext()->language->id;

        $checkStock = !Configuration::get('PS_DISP_UNAVAILABLE_ATTR');
        if (!$res = Db::getInstance()->executeS(
            '
			SELECT pa.`id_product`, a.`color`, pac.`id_product_attribute`, '.($checkStock ? 'SUM(IF(stock.`quantity` > 0, 1, 0))' : '0').' qty, a.`id_attribute`, al.`name`, IF(color = "", a.id_attribute, color) group_by
			FROM `'._DB_PREFIX_.'product_attribute` pa
			'.Shop::addSqlAssociation('product_attribute', 'pa').($checkStock ? static::sqlStock('pa', 'pa') : '').'
			JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON (pac.`id_product_attribute` = product_attribute_shop.`id_product_attribute`)
			JOIN `'._DB_PREFIX_.'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
			JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) $idLang.')
			JOIN `'._DB_PREFIX_.'attribute_group` ag ON (a.id_attribute_group = ag.`id_attribute_group`)
			WHERE pa.`id_product` IN ('.implode(array_map('intval', $products), ',').') AND ag.`is_color_group` = 1
			GROUP BY pa.`id_product`, a.`id_attribute`, `group_by`
			'.($checkStock ? 'HAVING qty > 0' : '').'
			ORDER BY a.`position` ASC;'
        )
        ) {
            return false;
        }

        $colors = [];
        foreach ($res as $row) {
            if (Tools::isEmpty($row['color']) && !@filemtime(_PS_COL_IMG_DIR_.$row['id_attribute'].'.jpg')) {
                continue;
            }

            $colors[(int) $row['id_product']][] = ['id_product_attribute' => (int) $row['id_product_attribute'], 'color' => $row['color'], 'id_product' => $row['id_product'], 'name' => $row['name'], 'id_attribute' => $row['id_attribute']];
        }

        return $colors;
    }

    /**
     * Get product accessories (only names)
     *
     * @param int $idLang    Language id
     * @param int $idProduct Product id
     *
     * @return array Product accessories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAccessoriesLight($idLang, $idProduct)
    {
        return Db::getInstance()->executeS(
            '
			SELECT p.`id_product`, p.`reference`, pl.`name`
			FROM `'._DB_PREFIX_.'accessory`
			LEFT JOIN `'._DB_PREFIX_.'product` p ON (p.`id_product`= `id_product_2`)
			'.Shop::addSqlAssociation('product', 'p').'
			LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
				p.`id_product` = pl.`id_product`
				AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').'
			)
			WHERE `id_product_1` = '.(int) $idProduct
        );
    }

    /**
     * @param int $idProduct
     *
     * @return array|bool|null|object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAccessoryById($idProduct)
    {
        return Db::getInstance()->getRow('SELECT `id_product`, `name` FROM `'._DB_PREFIX_.'product_lang` WHERE `id_product` = '.(int) $idProduct);
    }

    /**
     * @param int $idProduct
     * @param int $idFeature
     * @param int $idFeatureValue
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function addFeatureProductImport($idProduct, $idFeature, $idFeatureValue)
    {
        return Db::getInstance()->execute(
            '
			INSERT INTO `'._DB_PREFIX_.'feature_product` (`id_feature`, `id_product`, `id_feature_value`)
			VALUES ('.(int) $idFeature.', '.(int) $idProduct.', '.(int) $idFeatureValue.')
			ON DUPLICATE KEY UPDATE `id_feature_value` = '.(int) $idFeatureValue
        );
    }

    /**
     * @param array $productIds
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cacheProductsFeatures($productIds)
    {
        if (!Feature::isFeatureActive()) {
            return;
        }

        $productImplode = [];
        foreach ($productIds as $idProduct) {
            if ((int) $idProduct && !array_key_exists($idProduct, static::$_cacheFeatures)) {
                $productImplode[] = (int) $idProduct;
            }
        }
        if (!count($productImplode)) {
            return;
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT id_feature, id_product, id_feature_value
		FROM `'._DB_PREFIX_.'feature_product`
		WHERE `id_product` IN ('.implode($productImplode, ',').')'
        );
        foreach ($result as $row) {
            if (!array_key_exists($row['id_product'], static::$_cacheFeatures)) {
                static::$_cacheFeatures[$row['id_product']] = [];
            }
            static::$_cacheFeatures[$row['id_product']][] = $row;
        }
    }

    /**
     * Admin panel product search
     *
     * @param int     $idLang Language id
     * @param string  $query  Search query
     * @param Context $context
     *
     * @return array Matching products
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function searchByName($idLang, $query, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $sql = new DbQuery();
        $sql->select('p.`id_product`, pl.`name`, p.`ean13`, p.`upc`, p.`active`, p.`reference`, m.`name` AS manufacturer_name, stock.`quantity`, product_shop.advanced_stock_management, p.`customizable`');
        $sql->from('product', 'p');
        $sql->join(Shop::addSqlAssociation('product', 'p'));
        $sql->leftJoin(
            'product_lang',
            'pl',
            'p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl')
        );
        $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');

        $where = 'pl.`name` LIKE \'%'.pSQL($query).'%\'
		OR p.`ean13` LIKE \'%'.pSQL($query).'%\'
		OR p.`upc` LIKE \'%'.pSQL($query).'%\'
		OR p.`reference` LIKE \'%'.pSQL($query).'%\'
		OR p.`supplier_reference` LIKE \'%'.pSQL($query).'%\'
		OR EXISTS(SELECT * FROM `'._DB_PREFIX_.'product_supplier` sp WHERE sp.`id_product` = p.`id_product` AND `product_supplier_reference` LIKE \'%'.pSQL($query).'%\')';

        $sql->orderBy('pl.`name` ASC');

        if (Combination::isFeatureActive()) {
            $where .= ' OR EXISTS(SELECT * FROM `'._DB_PREFIX_.'product_attribute` `pa` WHERE pa.`id_product` = p.`id_product` AND (pa.`reference` LIKE \'%'.pSQL($query).'%\'
			OR pa.`supplier_reference` LIKE \'%'.pSQL($query).'%\'
			OR pa.`ean13` LIKE \'%'.pSQL($query).'%\'
			OR pa.`upc` LIKE \'%'.pSQL($query).'%\'))';
        }
        $sql->where($where);
        $sql->join(static::sqlStock('p', 0));

        $result = Db::getInstance()->executeS($sql);

        if (!$result) {
            return false;
        }

        $resultsArray = [];
        foreach ($result as $row) {
            $row['price_tax_incl'] = static::getPriceStatic($row['id_product'], true);
            $row['price_tax_excl'] = static::getPriceStatic($row['id_product'], false);
            $resultsArray[] = $row;
        }

        return $resultsArray;
    }

    /**
     * Duplicate attributes when duplicating a product
     *
     * @param int $idProductOld Old product ID
     * @param int $idProductNew New product ID
     *
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @throws PrestaShopException
     */
    public static function duplicateAttributes($idProductOld, $idProductNew)
    {
        $return = true;
        $combinationImages = [];

        $result = Db::getInstance()->executeS(
            '
		SELECT pa.*, product_attribute_shop.*
			FROM `'._DB_PREFIX_.'product_attribute` pa
			'.Shop::addSqlAssociation('product_attribute', 'pa').'
			WHERE pa.`id_product` = '.(int) $idProductOld
        );
        $combinations = [];

        foreach ($result as $row) {
            $idProductAttributeOld = (int) $row['id_product_attribute'];
	        $quantityAttributeOld = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
		        (new DbQuery())
			        ->select('`quantity`')
			        ->from('stock_available')
			        ->where('`id_product` = '.(int) $idProductOld)
			        ->where('`id_product_attribute` = '.(int) $row['id_product_attribute'])
	        );
	        if (!isset($combinations[$idProductAttributeOld])) {
                $idCombination = null;
                $idShop = null;
                $result2 = Db::getInstance()->executeS(
                    '
				SELECT *
				FROM `'._DB_PREFIX_.'product_attribute_combination`
					WHERE `id_product_attribute` = '.$idProductAttributeOld
                );
            } else {
                $idCombination = (int) $combinations[$idProductAttributeOld];
                $idShop = (int) $row['id_shop'];
                $contextOld = Shop::getContext();
                $contextShopIdOld = Shop::getContextShopID();
                Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
            }

            $row['id_product'] = $idProductNew;
            unset($row['id_product_attribute']);

            $combination = new Combination($idCombination, null, $idShop);
            foreach ($row as $k => $v) {
                $combination->$k = $v;
            }
            $return &= $combination->save();

            $idProductAttributeNew = (int) $combination->id;

	        // Set stock quantity
	        StockAvailable::setQuantity((int) $idProductNew, $idProductAttributeNew, (int) $quantityAttributeOld, $idShop);

            if ($resultImages = static::_getAttributeImageAssociations($idProductAttributeOld)) {
                $combinationImages['old'][$idProductAttributeOld] = $resultImages;
                $combinationImages['new'][$idProductAttributeNew] = $resultImages;
            }

            if (!isset($combinations[$idProductAttributeOld])) {
                $combinations[$idProductAttributeOld] = (int) $idProductAttributeNew;
                foreach ($result2 as $row2) {
                    $row2['id_product_attribute'] = $idProductAttributeNew;
                    $return &= Db::getInstance()->insert('product_attribute_combination', $row2);
                }
            } else {
                Shop::setContext($contextOld, $contextShopIdOld);
            }

            //Copy suppliers
            $result3 = Db::getInstance()->executeS(
                '
			SELECT *
			FROM `'._DB_PREFIX_.'product_supplier`
			WHERE `id_product_attribute` = '.(int) $idProductAttributeOld.'
			AND `id_product` = '.(int) $idProductOld
            );

            foreach ($result3 as $row3) {
                unset($row3['id_product_supplier']);
                $row3['id_product'] = $idProductNew;
                $row3['id_product_attribute'] = $idProductAttributeNew;
                $return &= Db::getInstance()->insert('product_supplier', $row3);
            }
        }

        $impacts = static::getAttributesImpacts($idProductOld);

        if (is_array($impacts) && count($impacts)) {
            $impactSql = 'INSERT INTO `'._DB_PREFIX_.'attribute_impact` (`id_product`, `id_attribute`, `weight`, `price`) VALUES ';

            foreach ($impacts as $idAttribute => $impact) {
                $impactSql .= '('.(int) $idProductNew.', '.(int) $idAttribute.', '.(float) $impacts[$idAttribute]['weight'].', '.(float) $impacts[$idAttribute]['price'].'),';
            }

            $impactSql = substr_replace($impactSql, '', -1);
            $impactSql .= ' ON DUPLICATE KEY UPDATE `price` = VALUES(price), `weight` = VALUES(weight)';

            Db::getInstance()->execute($impactSql);
        }

        return !$return ? false : $combinationImages;
    }

    /**
     * Get product attribute image associations
     *
     * @param int $idProductAttribute
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function _getAttributeImageAssociations($idProductAttribute)
    {
        $combinationImages = [];
        $data = Db::getInstance()->executeS(
            '
			SELECT `id_image`
			FROM `'._DB_PREFIX_.'product_attribute_image`
			WHERE `id_product_attribute` = '.(int) $idProductAttribute
        );
        foreach ($data as $row) {
            $combinationImages[] = (int) $row['id_image'];
        }

        return $combinationImages;
    }

    /**
     * @param int $idProduct
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAttributesImpacts($idProduct)
    {
        $return = [];
        $result = Db::getInstance()->executeS(
            'SELECT ai.`id_attribute`, ai.`price`, ai.`weight`
			FROM `'._DB_PREFIX_.'attribute_impact` ai
			WHERE ai.`id_product` = '.(int) $idProduct
        );

        if (!$result) {
            return [];
        }
        foreach ($result as $impact) {
            $return[$impact['id_attribute']]['price'] = (float) $impact['price'];
            $return[$impact['id_attribute']]['weight'] = (float) $impact['weight'];
        }

        return $return;
    }

    /**
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function duplicateAccessories($idProductOld, $idProductNew)
    {
        $return = true;

        $result = Db::getInstance()->executeS(
            '
		SELECT *
		FROM `'._DB_PREFIX_.'accessory`
		WHERE `id_product_1` = '.(int) $idProductOld
        );
        foreach ($result as $row) {
            $data = [
                'id_product_1' => (int) $idProductNew,
                'id_product_2' => (int) $row['id_product_2'],
            ];
            $return &= Db::getInstance()->insert('accessory', $data);
        }

        return $return;
    }

    /**
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function duplicateTags($idProductOld, $idProductNew)
    {
        $tags = Db::getInstance()->executeS('SELECT `id_tag`, `id_lang` FROM `'._DB_PREFIX_.'product_tag` WHERE `id_product` = '.(int) $idProductOld);
        if (!Db::getInstance()->NumRows()) {
            return true;
        }

        $data = [];
        foreach ($tags as $tag) {
            $data[] = [
                'id_product' => (int) $idProductNew,
                'id_tag'     => (int) $tag['id_tag'],
                'id_lang'    => (int) $tag['id_lang'],
            ];
        }

        return Db::getInstance()->insert('product_tag', $data);
    }

    /**
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function duplicateDownload($idProductOld, $idProductNew)
    {
        $sql = 'SELECT `display_filename`, `filename`, `date_add`, `date_expiration`, `nb_days_accessible`, `nb_downloadable`, `active`, `is_shareable`
				FROM `'._DB_PREFIX_.'product_download`
				WHERE `id_product` = '.(int) $idProductOld;
        $results = Db::getInstance()->executeS($sql);
        if (!$results) {
            return true;
        }

        $data = [];
        foreach ($results as $row) {
            $newFilename = ProductDownload::getNewFilename();
            copy(_PS_DOWNLOAD_DIR_.$row['filename'], _PS_DOWNLOAD_DIR_.$newFilename);

            $data[] = [
                'id_product'         => (int) $idProductNew,
                'display_filename'   => pSQL($row['display_filename']),
                'filename'           => pSQL($newFilename),
                'date_expiration'    => pSQL($row['date_expiration']),
                'nb_days_accessible' => (int) $row['nb_days_accessible'],
                'nb_downloadable'    => (int) $row['nb_downloadable'],
                'active'             => (int) $row['active'],
                'is_shareable'       => (int) $row['is_shareable'],
                'date_add'           => date('Y-m-d H:i:s'),
            ];
        }

        return Db::getInstance()->insert('product_download', $data);
    }

    /**
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function duplicateAttachments($idProductOld, $idProductNew)
    {
        // Get all ids attachments of the old product
        $sql = 'SELECT `id_attachment` FROM `'._DB_PREFIX_.'product_attachment` WHERE `id_product` = '.(int) $idProductOld;
        $results = Db::getInstance()->executeS($sql);

        if (!$results) {
            return true;
        }

        $data = [];

        // Prepare data of table product_attachment
        foreach ($results as $row) {
            $data[] = [
                'id_product'    => (int) $idProductNew,
                'id_attachment' => (int) $row['id_attachment'],
            ];
        }

        // Duplicate product attachement
        $res = Db::getInstance()->insert('product_attachment', $data);
        static::updateCacheAttachment((int) $idProductNew);

        return $res;
    }

    /**
     * Duplicate features when duplicating a product
     *
     * @param int $idProductOld Old product id
     * @param int $idProductNew New product id
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function duplicateFeatures($idProductOld, $idProductNew)
    {
        $return = true;

        $result = Db::getInstance()->executeS(
            '
		SELECT *
		FROM `'._DB_PREFIX_.'feature_product`
		WHERE `id_product` = '.(int) $idProductOld
        );
        foreach ($result as $row) {
            $result2 = Db::getInstance()->getRow(
                '
			SELECT *
			FROM `'._DB_PREFIX_.'feature_value`
			WHERE `id_feature_value` = '.(int) $row['id_feature_value']
            );
            // Custom feature value, need to duplicate it
            if ($result2['custom']) {
                $oldIdFeatureValue = $result2['id_feature_value'];
                unset($result2['id_feature_value']);
                $return &= Db::getInstance()->insert('feature_value', $result2);
                $maxFv = Db::getInstance()->getRow(
                    '
					SELECT MAX(`id_feature_value`) AS nb
					FROM `'._DB_PREFIX_.'feature_value`'
                );
                $newIdFeatureValue = $maxFv['nb'];

                foreach (Language::getIDs(false) as $idLang) {
                    $result3 = Db::getInstance()->getRow(
                        '
					SELECT *
					FROM `'._DB_PREFIX_.'feature_value_lang`
					WHERE `id_feature_value` = '.(int) $oldIdFeatureValue.'
					AND `id_lang` = '.(int) $idLang
                    );

                    if ($result3) {
                        $result3['id_feature_value'] = (int) $newIdFeatureValue;
                        $result3['value'] = pSQL($result3['value']);
                        $return &= Db::getInstance()->insert('feature_value_lang', $result3);
                    }
                }
                $row['id_feature_value'] = $newIdFeatureValue;
            }

            $row['id_product'] = (int) $idProductNew;
            $return &= Db::getInstance()->insert('feature_product', $row);
        }

        return $return;
    }

    /**
     * @param int $oldProductId
     * @param int $productId
     *
     * @return bool
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function duplicateSpecificPrices($oldProductId, $productId)
    {
        // remove all existing specific prices that might exists for target product
        if (! SpecificPrice::deleteByProductId($productId)) {
            return false;
        }

        // duplicate specific prices from source product
        foreach (SpecificPrice::getByProductId((int) $oldProductId) as $data) {
            $specificPrice = new SpecificPrice((int) $data['id_specific_price']);
            if (!$specificPrice->duplicate((int) $productId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $oldProductId
     * @param int $productId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function duplicateCustomizationFields($oldProductId, $productId)
    {
        // If customization is not activated, return success
        if (!Customization::isFeatureActive()) {
            return true;
        }
        if (($customizations = static::_getCustomizationFieldsNLabels($oldProductId)) === false) {
            return false;
        }
        if (empty($customizations)) {
            return true;
        }
        foreach ($customizations['fields'] as $customizationField) {
            /* The new datas concern the new product */
            $customizationField['id_product'] = (int) $productId;
            $oldCustomizationFieldId = (int) $customizationField['id_customization_field'];

            unset($customizationField['id_customization_field']);

            if (!Db::getInstance()->insert('customization_field', $customizationField)
                || !$customizationFieldId = Db::getInstance()->Insert_ID()
            ) {
                return false;
            }

            if (isset($customizations['labels'])) {
                foreach ($customizations['labels'][$oldCustomizationFieldId] as $customizationLabel) {
                    $data = [
                        'id_customization_field' => (int) $customizationFieldId,
                        'id_lang'                => (int) $customizationLabel['id_lang'],
                        'id_shop'                => (int) $customizationLabel['id_shop'],
                        'name'                   => pSQL($customizationLabel['name']),
                    ];

                    if (!Db::getInstance()->insert('customization_field_lang', $data)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param int      $productId
     * @param int|null $idShop
     *
     * @return array|bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function _getCustomizationFieldsNLabels($productId, $idShop = null)
    {
        if (!Customization::isFeatureActive()) {
            return false;
        }

        if (Shop::isFeatureActive() && !$idShop) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        $customizations = [];
        if (($customizations['fields'] = Db::getInstance()->executeS(
                '
			SELECT `id_customization_field`, `type`, `required`
			FROM `'._DB_PREFIX_.'customization_field`
			WHERE `id_product` = '.(int) $productId.'
			ORDER BY `id_customization_field`'
            )) === false
        ) {
            return false;
        }

        if (empty($customizations['fields'])) {
            return [];
        }

        $customizationFieldIds = [];
        foreach ($customizations['fields'] as $customizationField) {
            $customizationFieldIds[] = (int) $customizationField['id_customization_field'];
        }

        if (($customizationLabels = Db::getInstance()->executeS(
                '
			SELECT `id_customization_field`, `id_lang`, `id_shop`, `name`
			FROM `'._DB_PREFIX_.'customization_field_lang`
			WHERE `id_customization_field` IN ('.implode(', ', $customizationFieldIds).')'.($idShop ? ' AND `id_shop` = '.$idShop : '').'
			ORDER BY `id_customization_field`'
            )) === false
        ) {
            return false;
        }

        foreach ($customizationLabels as $customizationLabel) {
            $customizations['labels'][$customizationLabel['id_customization_field']][] = $customizationLabel;
        }

        return $customizations;
    }

    /**
     * Adds suppliers from old product onto a newly duplicated product
     *
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function duplicateSuppliers($idProductOld, $idProductNew)
    {
        $result = Db::getInstance()->executeS(
            '
		SELECT *
		FROM `'._DB_PREFIX_.'product_supplier`
		WHERE `id_product` = '.(int) $idProductOld.' AND `id_product_attribute` = 0'
        );

        foreach ($result as $row) {
            unset($row['id_product_supplier']);
            $row['id_product'] = $idProductNew;
            if (!Db::getInstance()->insert('product_supplier', $row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int      $idCart
     * @param int|null $idLang
     * @param int|bool $onlyInCart
     * @param int|null $idShop
     *
     * @return array|bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAllCustomizedDatas($idCart, $idLang = null, $onlyInCart = true, $idShop = null)
    {
        if (!Customization::isFeatureActive()) {
            return false;
        }

        // No need to query if there isn't any real cart!
        if (!$idCart) {
            return false;
        }
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }
        if (Shop::isFeatureActive() && !$idShop) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        if (!$result = Db::getInstance()->executeS(
            '
			SELECT cd.`id_customization`, c.`id_address_delivery`, c.`id_product`, cfl.`id_customization_field`, c.`id_product_attribute`,
				cd.`type`, cd.`index`, cd.`value`, cfl.`name`
			FROM `'._DB_PREFIX_.'customized_data` cd
			NATURAL JOIN `'._DB_PREFIX_.'customization` c
			LEFT JOIN `'._DB_PREFIX_.'customization_field_lang` cfl ON (cfl.id_customization_field = cd.`index` AND id_lang = '.(int) $idLang.
            ($idShop ? ' AND cfl.`id_shop` = '.$idShop : '').')
			WHERE c.`id_cart` = '.(int) $idCart.
            ($onlyInCart ? ' AND c.`in_cart` = 1' : '').'
			ORDER BY `id_product`, `id_product_attribute`, `type`, `index`'
        )
        ) {
            return false;
        }

        $customizedDatas = [];

        foreach ($result as $row) {
            $customizedDatas[(int) $row['id_product']][(int) $row['id_product_attribute']][(int) $row['id_address_delivery']][(int) $row['id_customization']]['datas'][(int) $row['type']][] = $row;
        }

        if (!$result = Db::getInstance()->executeS(
            'SELECT `id_product`, `id_product_attribute`, `id_customization`, `id_address_delivery`, `quantity`, `quantity_refunded`, `quantity_returned`
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_cart` = '.(int) $idCart.($onlyInCart ? '
			AND `in_cart` = 1' : '')
        )
        ) {
            return false;
        }

        foreach ($result as $row) {
            $customizedDatas[(int) $row['id_product']][(int) $row['id_product_attribute']][(int) $row['id_address_delivery']][(int) $row['id_customization']]['quantity'] = (int) $row['quantity'];
            $customizedDatas[(int) $row['id_product']][(int) $row['id_product_attribute']][(int) $row['id_address_delivery']][(int) $row['id_customization']]['quantity_refunded'] = (int) $row['quantity_refunded'];
            $customizedDatas[(int) $row['id_product']][(int) $row['id_product_attribute']][(int) $row['id_address_delivery']][(int) $row['id_customization']]['quantity_returned'] = (int) $row['quantity_returned'];
        }

        return $customizedDatas;
    }

    /**
     * @param array $products
     * @param array $customizedDatas
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function addCustomizationPrice(&$products, &$customizedDatas)
    {
        if (!$customizedDatas) {
            return;
        }

        foreach ($products as &$productUpdate) {
            if (!Customization::isFeatureActive()) {
                $productUpdate['customizationQuantityTotal'] = 0;
                $productUpdate['customizationQuantityRefunded'] = 0;
                $productUpdate['customizationQuantityReturned'] = 0;
            } else {
                $customizationQuantity = 0;
                $customizationQuantityRefunded = 0;
                $customizationQuantityReturned = 0;

                /* Compatibility */
                $idProduct = isset($productUpdate['id_product']) ? (int) $productUpdate['id_product'] : (int) $productUpdate['product_id'];
                $idProductAttribute = isset($productUpdate['id_product_attribute']) ? (int) $productUpdate['id_product_attribute'] : (int) $productUpdate['product_attribute_id'];
                $idAddressDelivery = (int) $productUpdate['id_address_delivery'];
                $productQuantity = isset($productUpdate['cart_quantity']) ? (int) $productUpdate['cart_quantity'] : (int) $productUpdate['product_quantity'];
                $price = isset($productUpdate['price']) ? $productUpdate['price'] : $productUpdate['product_price'];
                if (isset($productUpdate['price_wt']) && $productUpdate['price_wt']) {
                    $priceWt = $productUpdate['price_wt'];
                } else {
                    $taxRate = isset($productUpdate['tax_rate']) ?
                        $productUpdate['tax_rate'] :
                        $productUpdate['rate'];
                    $priceWt = round(
                        $price * (1 + $taxRate / 100),
                        _TB_PRICE_DATABASE_PRECISION_
                    );
                }

                if (!isset($customizedDatas[$idProduct][$idProductAttribute][$idAddressDelivery])) {
                    $idAddressDelivery = 0;
                }
                if (isset($customizedDatas[$idProduct][$idProductAttribute][$idAddressDelivery])) {
                    foreach ($customizedDatas[$idProduct][$idProductAttribute][$idAddressDelivery] as $customization) {
                        $customizationQuantity += (int) $customization['quantity'];
                        $customizationQuantityRefunded += (int) $customization['quantity_refunded'];
                        $customizationQuantityReturned += (int) $customization['quantity_returned'];
                    }
                }

                $productUpdate['customizationQuantityTotal'] = $customizationQuantity;
                $productUpdate['customizationQuantityRefunded'] = $customizationQuantityRefunded;
                $productUpdate['customizationQuantityReturned'] = $customizationQuantityReturned;

                if ($customizationQuantity) {
                    $productUpdate['total_wt'] = $priceWt * ($productQuantity - $customizationQuantity);
                    $productUpdate['total_customization_wt'] = $priceWt * $customizationQuantity;
                    $productUpdate['total'] = $price * ($productQuantity - $customizationQuantity);
                    $productUpdate['total_customization'] = $price * $customizationQuantity;
                }
            }
        }
    }

    /**
     * Checks if the product is in at least one of the submited categories
     *
     * @param int   $idProduct
     * @param array $categories array of category arrays
     *
     * @return bool is the product in at least one category
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function idIsOnCategoryId($idProduct, $categories)
    {
        if (!((int) $idProduct > 0) || !is_array($categories) || empty($categories)) {
            return false;
        }
        $sql = 'SELECT id_product FROM `'._DB_PREFIX_.'category_product` WHERE `id_product` = '.(int) $idProduct.' AND `id_category` IN (';
        foreach ($categories as $category) {
            $sql .= (int) $category['id_category'].',';
        }
        $sql = rtrim($sql, ',').')';

        $hash = md5($sql);
        if (!isset(static::$_incat[$hash])) {
            if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql)) {
                return false;
            }
            static::$_incat[$hash] = (Db::getInstance(_PS_USE_SQL_SLAVE_)->NumRows() > 0 ? true : false);
        }

        return static::$_incat[$hash];
    }

    /**
     * @param int $idProduct
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getUrlRewriteInformations($idProduct)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT pl.`id_lang`, pl.`link_rewrite`, p.`ean13`, cl.`link_rewrite` AS category_rewrite
			FROM `'._DB_PREFIX_.'product` p
			LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product`'.Shop::addSqlRestrictionOnLang('pl').')
			'.Shop::addSqlAssociation('product', 'p').'
			LEFT JOIN `'._DB_PREFIX_.'lang` l ON (pl.`id_lang` = l.`id_lang`)
			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (cl.`id_category` = product_shop.`id_category_default`  AND cl.`id_lang` = pl.`id_lang`'.Shop::addSqlRestrictionOnLang('cl').')
			WHERE p.`id_product` = '.(int) $idProduct.'
			AND l.`active` = 1
		'
        );
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function resetEcoTax()
    {
        return ObjectModel::updateMultishopTable(
            'product',
            [
                'ecotax' => 0,
            ]
        );
    }

    /**
     * Get all product attributes ids
     *
     * @param int  $idProduct the id of the product
     *
     * @param bool $shopOnly
     *
     * @return array product attribute id list
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProductAttributesIds($idProduct, $shopOnly = false)
    {
        return Db::getInstance()->executeS(
            '
		SELECT pa.id_product_attribute
		FROM `'._DB_PREFIX_.'product_attribute` pa'.
            ($shopOnly ? Shop::addSqlAssociation('product_attribute', 'pa') : '').'
		WHERE pa.`id_product` = '.(int) $idProduct
        );
    }

    /**
     * @todo    Remove existing module condition
     *
     * @param int $idProduct
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAttributesInformationsByProduct($idProduct)
    {
        // if blocklayered module is installed we check if user has set custom attribute name
        if (Module::isInstalled('blocklayered') && Module::isEnabled('blocklayered')) {
            $nbCustomValues = Db::getInstance()->executeS(
                '
			SELECT DISTINCT la.`id_attribute`, la.`url_name` AS `attribute`
			FROM `'._DB_PREFIX_.'attribute` a
			LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
				ON (a.`id_attribute` = pac.`id_attribute`)
			LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
			'.Shop::addSqlAssociation('product_attribute', 'pa').'
			LEFT JOIN `'._DB_PREFIX_.'layered_indexable_attribute_lang_value` la
				ON (la.`id_attribute` = a.`id_attribute` AND la.`id_lang` = '.(int) Context::getContext()->language->id.')
			WHERE la.`url_name` IS NOT NULL AND la.`url_name` != \'\'
			AND pa.`id_product` = '.(int) $idProduct
            );

            if (!empty($nbCustomValues)) {
                $tabIdAttribute = [];
                foreach ($nbCustomValues as $attribute) {
                    $tabIdAttribute[] = $attribute['id_attribute'];

                    $group = Db::getInstance()->executeS(
                        '
					SELECT g.`id_attribute_group`, g.`url_name` AS `group`
					FROM `'._DB_PREFIX_.'layered_indexable_attribute_group_lang_value` g
					LEFT JOIN `'._DB_PREFIX_.'attribute` a
						ON (a.`id_attribute_group` = g.`id_attribute_group`)
					WHERE a.`id_attribute` = '.(int) $attribute['id_attribute'].'
					AND g.`id_lang` = '.(int) Context::getContext()->language->id.'
					AND g.`url_name` IS NOT NULL AND g.`url_name` != \'\''
                    );
                    if (empty($group)) {
                        $group = Db::getInstance()->executeS(
                            '
						SELECT g.`id_attribute_group`, g.`name` AS `group`
						FROM `'._DB_PREFIX_.'attribute_group_lang` g
						LEFT JOIN `'._DB_PREFIX_.'attribute` a
							ON (a.`id_attribute_group` = g.`id_attribute_group`)
						WHERE a.`id_attribute` = '.(int) $attribute['id_attribute'].'
						AND g.`id_lang` = '.(int) Context::getContext()->language->id.'
						AND g.`name` IS NOT NULL'
                        );
                    }
                    $result[] = array_merge($attribute, $group[0]);
                }
                $valuesNotCustom = Db::getInstance()->executeS(
                    '
				SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` AS `attribute`, agl.`name` AS `group`
				FROM `'._DB_PREFIX_.'attribute` a
				LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
					ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) Context::getContext()->language->id.')
				LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
					ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) Context::getContext()->language->id.')
				LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
					ON (a.`id_attribute` = pac.`id_attribute`)
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
					ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				'.Shop::addSqlAssociation('attribute', 'pac').'
				WHERE pa.`id_product` = '.(int) $idProduct.'
				AND a.`id_attribute` NOT IN('.implode(', ', $tabIdAttribute).')'
                );
                $result = array_merge($valuesNotCustom, $result);
            } else {
                $result = Db::getInstance()->executeS(
                    '
				SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` AS `attribute`, agl.`name` AS `group`
				FROM `'._DB_PREFIX_.'attribute` a
				LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
					ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) Context::getContext()->language->id.')
				LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
					ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) Context::getContext()->language->id.')
				LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
					ON (a.`id_attribute` = pac.`id_attribute`)
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
					ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				'.Shop::addSqlAssociation('attribute', 'pac').'
				WHERE pa.`id_product` = '.(int) $idProduct
                );
            }
        } else {
            $result = Db::getInstance()->executeS(
                '
			SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` AS `attribute`, agl.`name` AS `group`
			FROM `'._DB_PREFIX_.'attribute` a
			LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
				ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) Context::getContext()->language->id.')
			LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
				ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) Context::getContext()->language->id.')
			LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
				ON (a.`id_attribute` = pac.`id_attribute`)
			LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
			'.Shop::addSqlAssociation('product_attribute', 'pa').'
			'.Shop::addSqlAssociation('attribute', 'pac').'
			WHERE pa.`id_product` = '.(int) $idProduct
            );
        }

        return $result;
    }

    /**
     * Gets the name of a given product, in the given lang
     *
     * @param int $idProduct
     * @param int $idProductAttribute Optional
     * @param int $idLang             Optional
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getProductName($idProduct, $idProductAttribute = null, $idLang = null)
    {
        // use the lang in the context if $id_lang is not defined
        if (!$idLang) {
            $idLang = (int) Context::getContext()->language->id;
        }

        // creates the query object
        $query = new DbQuery();

        // selects different names, if it is a combination
        if ($idProductAttribute) {
            $query->select('IFNULL(CONCAT(pl.name, \' : \', GROUP_CONCAT(DISTINCT agl.`name`, \' - \', al.name SEPARATOR \', \')),pl.name) as name');
        } else {
            $query->select('DISTINCT pl.name as name');
        }

        // adds joins & where clauses for combinations
        if ($idProductAttribute) {
            $query->from('product_attribute', 'pa');
            $query->join(Shop::addSqlAssociation('product_attribute', 'pa'));
            $query->innerJoin('product_lang', 'pl', 'pl.id_product = pa.id_product AND pl.id_lang = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl'));
            $query->leftJoin('product_attribute_combination', 'pac', 'pac.id_product_attribute = pa.id_product_attribute');
            $query->leftJoin('attribute', 'atr', 'atr.id_attribute = pac.id_attribute');
            $query->leftJoin('attribute_lang', 'al', 'al.id_attribute = atr.id_attribute AND al.id_lang = '.(int) $idLang);
            $query->leftJoin('attribute_group_lang', 'agl', 'agl.id_attribute_group = atr.id_attribute_group AND agl.id_lang = '.(int) $idLang);
            $query->where('pa.id_product = '.(int) $idProduct.' AND pa.id_product_attribute = '.(int) $idProductAttribute);
        } else {
            // or just adds a 'where' clause for a simple product

            $query->from('product_lang', 'pl');
            $query->where('pl.id_product = '.(int) $idProduct);
            $query->where('pl.id_lang = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl'));
        }

        return Db::getInstance()->getValue($query);
    }

    /**
     * For a given product, returns its real quantity
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idWarehouse
     * @param int $idShop
     *
     * @return int real_quantity
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getRealQuantity($idProduct, $idProductAttribute = 0, $idWarehouse = 0, $idShop = null)
    {
        static $manager = null;

        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && is_null($manager)) {
            $manager = StockManagerFactory::getManager();
        }

        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && static::usesAdvancedStockManagement($idProduct) &&
            StockAvailable::dependsOnStock($idProduct, $idShop)
        ) {
            return $manager->getProductRealQuantities($idProduct, $idProductAttribute, $idWarehouse, true);
        } else {
            return StockAvailable::getQuantityAvailableByProduct($idProduct, $idProductAttribute, $idShop);
        }
    }

    /**
     * For a given product, tells if it uses the advanced stock management
     *
     * @param int $idProduct
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function usesAdvancedStockManagement($idProduct)
    {
        $query = new DbQuery();
        $query->select('product_shop.advanced_stock_management');
        $query->from('product', 'p');
        $query->join(Shop::addSqlAssociation('product', 'p'));
        $query->where('p.id_product = '.(int) $idProduct);

        return (bool) Db::getInstance()->getValue($query);
    }

    /**
     * This method allows to flush price cache
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function flushPriceCache()
    {
        static::$_prices = [];
        static::$_pricesLevel2 = [];
    }

    /**
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getIdTaxRulesGroupMostUsed()
    {
        return Db::getInstance()->getValue(
            '
					SELECT id_tax_rules_group
					FROM (
						SELECT COUNT(*) n, product_shop.id_tax_rules_group
						FROM '._DB_PREFIX_.'product p
						'.Shop::addSqlAssociation('product', 'p').'
						JOIN '._DB_PREFIX_.'tax_rules_group trg ON (product_shop.id_tax_rules_group = trg.id_tax_rules_group)
						WHERE trg.active = 1 AND trg.deleted = 0
						GROUP BY product_shop.id_tax_rules_group
						ORDER BY n DESC
						LIMIT 1
					) most_used'
        );
    }

    /**
     * For a given ean13 reference, returns the corresponding id
     *
     * @param string $ean13
     *
     * @return int id
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getIdByEan13($ean13)
    {
        if (empty($ean13)) {
            return 0;
        }

        if (!Validate::isEan13($ean13)) {
            return 0;
        }

        $query = new DbQuery();
        $query->select('p.id_product');
        $query->from('product', 'p');
        $query->where('p.ean13 = \''.pSQL($ean13).'\'');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * @param int  $idProduct
     * @param bool $full
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getColorsListCacheId($idProduct, $full = true)
    {
        $cacheId = 'productlist_colors';
        if ($idProduct) {
            $cacheId .= '|'.(int) $idProduct;
        }

        if ($full) {
            $cacheId .= '|'.(int) Context::getContext()->shop->id.'|'.(int) Context::getContext()->cookie->id_lang;
        }

        return $cacheId;
    }

    /**
     * @param int $idProduct
     * @param int $packStockType
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function setPackStockType($idProduct, $packStockType)
    {
        return Db::getInstance()->execute(
            'UPDATE '._DB_PREFIX_.'product p
		'.Shop::addSqlAssociation('product', 'p').' SET product_shop.pack_stock_type = '.(int) $packStockType.' WHERE p.`id_product` = '.(int) $idProduct
        );
    }

    /**
     * @see     ObjectModel::getFieldsShop()
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getFieldsShop()
    {
        $fields = parent::getFieldsShop();
        if (is_null($this->update_fields) || (!empty($this->update_fields['price']) && !empty($this->update_fields['unit_price']))) {
            $fields['unit_price_ratio'] = (float) $this->unit_price > 0 ? $this->price / $this->unit_price : 0;
        }
        $fields['unity'] = pSQL($this->unity);

        return $fields;
    }

    /**
     * Move a product inside its category
     *
     * @param bool $way Up (1)  or Down (0)
     * @param int  $position
     *
     * @return bool Update result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function updatePosition($way, $position)
    {
        if (!$res = Db::getInstance()->executeS(
            '
            SELECT cp.`id_product`, cp.`position`, cp.`id_category`
            FROM `'._DB_PREFIX_.'category_product` cp
            WHERE cp.`id_category` = '.(int) Tools::getValue('id_category', 1).'
            ORDER BY cp.`position` ASC'
        )
        ) {
            return false;
        }

        foreach ($res as $product) {
            if ((int) $product['id_product'] == (int) $this->id) {
                $movedProduct = $product;
            }
        }

        if (!isset($movedProduct) || !isset($position)) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $result = (Db::getInstance()->execute(
                '
            UPDATE `'._DB_PREFIX_.'category_product` cp
            INNER JOIN `'._DB_PREFIX_.'product` p ON (p.`id_product` = cp.`id_product`)
            '.Shop::addSqlAssociation('product', 'p').'
            SET cp.`position`= `position` '.($way ? '- 1' : '+ 1').',
            p.`date_upd` = "'.date('Y-m-d H:i:s').'", product_shop.`date_upd` = "'.date('Y-m-d H:i:s').'"
            WHERE cp.`position`
            '.($way
                    ? '> '.(int) $movedProduct['position'].' AND `position` <= '.(int) $position
                    : '< '.(int) $movedProduct['position'].' AND `position` >= '.(int) $position).'
            AND `id_category`='.(int) $movedProduct['id_category']
            )
            && Db::getInstance()->execute(
                '
            UPDATE `'._DB_PREFIX_.'category_product` cp
            INNER JOIN `'._DB_PREFIX_.'product` p ON (p.`id_product` = cp.`id_product`)
            '.Shop::addSqlAssociation('product', 'p').'
            SET cp.`position` = '.(int) $position.',
            p.`date_upd` = "'.date('Y-m-d H:i:s').'", product_shop.`date_upd` = "'.date('Y-m-d H:i:s').'"
            WHERE cp.`id_product` = '.(int) $movedProduct['id_product'].'
            AND cp.`id_category`='.(int) $movedProduct['id_category']
            )

        );
        Hook::exec('actionProductUpdate', ['id_product' => (int) $this->id, 'product' => $this]);

        return $result;
    }

    /**
     * @see ObjectModel::validateField()
     *
     * @param string $field
     * @param mixed  $value
     * @param null   $idLang
     * @param array  $skip
     * @param bool   $humanErrors
     *
     * @return string|true
     * @throws PrestaShopException
     */
    public function validateField($field, $value, $idLang = null, $skip = [], $humanErrors = false)
    {
        if ($field == 'description_short') {
            $limit = (int) Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT');
            if ($limit <= 0) {
                $limit = 800;
            }

            $sizeWithoutHtml = mb_strlen(strip_tags($value));
            $sizeWithHtml = mb_strlen($value);
            $this->def['fields']['description_short']['size'] = $limit + $sizeWithHtml - $sizeWithoutHtml;
        }

        return parent::validateField($field, $value, $idLang, $skip, $humanErrors);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function toggleStatus()
    {
        //test if the product is active and if redirect_type is empty string and set default value to id_product_redirected & redirect_type
        //  /!\ after parent::toggleStatus() active will be false, that why we set 404 by default :p
        if ($this->active) {
            //case where active will be false after parent::toggleStatus()
            $this->id_product_redirected = 0;
            $this->redirect_type = '404';
        } else {
            //case where active will be true after parent::toggleStatus()
            $this->id_product_redirected = 0;
            $this->redirect_type = '';
        }

        return parent::toggleStatus();
    }

    /**
     * @param array $products
     *
     * @return bool|int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws Adapter_Exception
     */
    public function deleteSelection($products)
    {
        $return = 1;
        if (is_array($products) && ($count = count($products))) {
            // Deleting products can be quite long on a cheap server. Let's say 1.5 seconds by product (I've seen it!).
            if (intval(ini_get('max_execution_time')) < round($count * 1.5)) {
                ini_set('max_execution_time', round($count * 1.5));
            }

            foreach ($products as $idProduct) {
                $product = new Product((int) $idProduct);
                $return &= $product->delete();
            }
        }

        return $return;
    }

    /**
     * @return bool
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        /*
         * @since 1.5.0
         * It is NOT possible to delete a product if there are currently:
         * - physical stock for this product
         * - supply order(s) for this product
         */
        if (PageCache::isEnabled()) {
            PageCache::invalidateEntity('product', $this->id);
        }

        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $this->advanced_stock_management) {
            $stockManager = StockManagerFactory::getManager();
            $physicalQuantity = $stockManager->getProductPhysicalQuantities($this->id, 0);
            $realQuantity = $stockManager->getProductRealQuantities($this->id, 0);
            if ($physicalQuantity > 0) {
                return false;
            }
            if ($realQuantity > $physicalQuantity) {
                return false;
            }

            $warehouseProductLocations = Adapter_ServiceLocator::get('Core_Foundation_Database_EntityManager')->getRepository('WarehouseProductLocation')->findByIdProduct($this->id);
            foreach ($warehouseProductLocations as $warehouseProductLocation) {
                $warehouseProductLocation->delete();
            }

            $stocks = Adapter_ServiceLocator::get('Core_Foundation_Database_EntityManager')->getRepository('Stock')->findByIdProduct($this->id);
            foreach ($stocks as $stock) {
                $stock->delete();
            }
        }
        $result = parent::delete();

        // Removes the product from StockAvailable, for the current shop
        StockAvailable::removeProductFromStockAvailable($this->id);
        $result &= ($this->deleteProductAttributes() && $this->deleteImages() && $this->deleteSceneProducts());
        // If there are still entries in product_shop, don't remove completely the product
        if ($this->hasMultishopEntries()) {
            return true;
        }

        Hook::exec('actionProductDelete', ['id_product' => (int) $this->id, 'product' => $this]);
        if (!$result ||
            !GroupReduction::deleteProductReduction($this->id) ||
            !$this->deleteCategories(true) ||
            !$this->deleteProductFeatures() ||
            !$this->deleteTags() ||
            !$this->deleteCartProducts() ||
            !$this->deleteAttributesImpacts() ||
            !$this->deleteAttachments(false) ||
            !$this->deleteCustomization() ||
            !SpecificPrice::deleteByProductId((int) $this->id) ||
            !$this->deletePack() ||
            !$this->deleteProductSale() ||
            !$this->deleteSearchIndexes() ||
            !$this->deleteAccessories() ||
            !$this->deleteFromAccessories() ||
            !$this->deleteFromSupplier() ||
            !$this->deleteDownload() ||
            !$this->deleteFromCartRules()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Delete product attributes
     *
     * @return array Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteProductAttributes()
    {
        Hook::exec('actionProductAttributeDelete', ['id_product_attribute' => 0, 'id_product' => (int) $this->id, 'deleteAllAttributes' => true]);

        $result = true;
        $combinations = new PrestaShopCollection('Combination');
        $combinations->where('id_product', '=', $this->id);
        foreach ($combinations as $combination) {
            $result &= $combination->delete();
        }
        SpecificPriceRule::applyAllRules([(int) $this->id]);
        Tools::clearColorListCache($this->id);

        return $result;
    }

    /**
     * Delete product images from database
     *
     * @return bool success
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function deleteImages()
    {
        $result = Db::getInstance()->executeS(
            '
			SELECT `id_image`
			FROM `'._DB_PREFIX_.'image`
			WHERE `id_product` = '.(int) $this->id
        );

        $status = true;
        if ($result) {
            foreach ($result as $row) {
                $image = new Image($row['id_image']);
                $status &= $image->delete();
            }
        }

        return $status;
    }

    /**
     * Delete product in its scenes
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function deleteSceneProducts()
    {
        return Db::getInstance()->execute(
            'DELETE FROM `'._DB_PREFIX_.'scene_products`
			WHERE `id_product` = '.(int) $this->id
        );
    }

    /**
     * Delete all association to category where product is indexed
     *
     * @param bool $cleanPositions clean category positions after deletion
     *
     * @return array Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteCategories($cleanPositions = false)
    {
        if ($cleanPositions === true) {
            $result = Db::getInstance()->executeS(
                'SELECT `id_category`, `position`
				FROM `'._DB_PREFIX_.'category_product`
				WHERE `id_product` = '.(int) $this->id
            );
        }

        $return = Db::getInstance()->delete('category_product', 'id_product = '.(int) $this->id);
        if ($cleanPositions === true && is_array($result)) {
            foreach ($result as $row) {
                $return &= $this->cleanPositions((int) $row['id_category'], (int) $row['position']);
            }
        }

        return $return;
    }

    /**
     * Reorder product position in category $id_category.
     * Call it after deleting a product from a category.
     *
     * @param int $idCategory
     * @param int $position
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @return bool
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public static function cleanPositions($idCategory, $position = 0)
    {
        $idCategory = (int) $idCategory;
        $position = (int) $position;
        $now = date('Y-m-d H:i:s');

        $return = true;
        if (! $position) {
            // reset positions of all products within category
            $return = Db::getInstance()->execute('
                SET @rank:=-1;
                UPDATE `'._DB_PREFIX_.'category_product`
                SET position = @rank:=@rank+1
                WHERE `id_category` = '.$idCategory.'
                ORDER BY `position`
            ');
        } else {
            // decrease positions of all subsequent products within category
            $return = Db::getInstance()->update(
                'category_product',
                ['position' => ['type' => 'sql', 'value' => '`position`-1']],
                '`id_category` = '.$idCategory.' AND `position` > '.$position
            );
        }

        // mark all products whose position within category (might) have changed as modified
        $return &= Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'product` p'.Shop::addSqlAssociation('product', 'p').'
            INNER JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = '.$idCategory.' AND cp.`id_product` = p.`id_product` AND cp.`position` >= '.$position.')
            SET p.`date_upd` = "'.$now.'", product_shop.`date_upd` = "'.$now.'"
        ');

        return $return;
    }

    /**
     * Delete product features
     *
     * @return array Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteProductFeatures()
    {
        SpecificPriceRule::applyAllRules([(int) $this->id]);

        return $this->deleteFeatures();
    }

    /**
     * Delete features
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteFeatures()
    {
        // List products features
        $features = Db::getInstance()->executeS(
            '
		SELECT p.*, f.*
		FROM `'._DB_PREFIX_.'feature_product` AS p
		LEFT JOIN `'._DB_PREFIX_.'feature_value` AS f ON (f.`id_feature_value` = p.`id_feature_value`)
		WHERE `id_product` = '.(int) $this->id
        );
        foreach ($features as $tab) {
            // Delete product custom features
            if ($tab['custom']) {
                Db::getInstance()->execute(
                    '
				DELETE FROM `'._DB_PREFIX_.'feature_value`
				WHERE `id_feature_value` = '.(int) $tab['id_feature_value']
                );
                Db::getInstance()->execute(
                    '
				DELETE FROM `'._DB_PREFIX_.'feature_value_lang`
				WHERE `id_feature_value` = '.(int) $tab['id_feature_value']
                );
            }
        }
        // Delete product features
        $result = Db::getInstance()->execute(
            '
		DELETE FROM `'._DB_PREFIX_.'feature_product`
		WHERE `id_product` = '.(int) $this->id
        );

        SpecificPriceRule::applyAllRules([(int) $this->id]);

        return ($result);
    }

    /**
     * Delete products tags entries
     *
     * @return array Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteTags()
    {
        return Tag::deleteTagsForProduct((int) $this->id);
    }

    /**
     * Delete product from cart
     *
     * @return array|bool Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteCartProducts()
    {
        return Db::getInstance()->delete('cart_product', 'id_product = '.(int) $this->id);
    }

    /**
     * Delete product attributes impacts
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteAttributesImpacts()
    {
        return Db::getInstance()->execute(
            'DELETE FROM `'._DB_PREFIX_.'attribute_impact`
			WHERE `id_product` = '.(int) $this->id
        );
    }

    /**
     * Delete product attachments
     *
     * @param bool $updateAttachmentCache
     *
     * @return array Deletion result
     *
     * @internal param bool $update_cache If set to true attachment cache will be updated
     *
     * @since    1.0.0
     * @version  1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteAttachments($updateAttachmentCache = true)
    {
        $res = Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'product_attachment`
			WHERE `id_product` = '.(int) $this->id
        );

        if (isset($updateAttachmentCache) && (bool) $updateAttachmentCache === true) {
            static::updateCacheAttachment((int) $this->id);
        }

        return $res;
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function updateCacheAttachment($idProduct)
    {
        $value = (bool) Db::getInstance()->getValue(
            '
								SELECT id_attachment
								FROM '._DB_PREFIX_.'product_attachment
								WHERE id_product='.(int) $idProduct
        );

        return Db::getInstance()->update(
            'product',
            ['cache_has_attachments' => (int) $value],
            'id_product = '.(int) $idProduct
        );
    }

    /**
     * Delete product customizations
     *
     * @return array Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteCustomization()
    {
        return (
            Db::getInstance()->execute(
                'DELETE FROM `'._DB_PREFIX_.'customization_field`
				WHERE `id_product` = '.(int) $this->id
            )
            &&
            Db::getInstance()->execute(
                'DELETE `'._DB_PREFIX_.'customization_field_lang` FROM `'._DB_PREFIX_.'customization_field_lang` LEFT JOIN `'._DB_PREFIX_.'customization_field`
				ON ('._DB_PREFIX_.'customization_field.id_customization_field = '._DB_PREFIX_.'customization_field_lang.id_customization_field)
				WHERE '._DB_PREFIX_.'customization_field.id_customization_field IS NULL'
            )
        );
    }

    /**
     * Delete product pack details
     *
     * @return array Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deletePack()
    {
        return Db::getInstance()->execute(
            'DELETE FROM `'._DB_PREFIX_.'pack`
			WHERE `id_product_pack` = '.(int) $this->id.'
			OR `id_product_item` = '.(int) $this->id
        );
    }

    /**
     * Delete product sales
     *
     * @return array Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteProductSale()
    {
        return Db::getInstance()->execute(
            'DELETE FROM `'._DB_PREFIX_.'product_sale`
			WHERE `id_product` = '.(int) $this->id
        );
    }

    /**
     * Delete product indexed words
     *
     * @return array Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteSearchIndexes()
    {
        return (
            Db::getInstance()->execute(
                'DELETE FROM `'._DB_PREFIX_.'search_index`
                    WHERE `id_product` = '.(int)$this->id
            )
            &&
            Db::getInstance()->execute(
                'DELETE FROM `'._DB_PREFIX_.'search_word`
                    WHERE `id_word` NOT IN (
                        SELECT id_word
                        FROM `'._DB_PREFIX_.'search_index`
                    )'
            )
        );
    }

    /**
     * Delete product accessories
     *
     * @return mixed Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public function deleteAccessories()
    {
        return Db::getInstance()->delete('accessory', 'id_product_1 = '.(int) $this->id);
    }

    /**
     * Delete product from other products accessories
     *
     * @return mixed Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public function deleteFromAccessories()
    {
        return Db::getInstance()->delete('accessory', 'id_product_2 = '.(int) $this->id);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public function deleteFromSupplier()
    {
        return Db::getInstance()->delete('product_supplier', 'id_product = '.(int) $this->id);
    }

    /**
     * Remove all downloadable files for product and its attributes
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteDownload()
    {
        $result = true;
        $collectionDownload = new PrestaShopCollection('ProductDownload');
        $collectionDownload->where('id_product', '=', $this->id);
        foreach ($collectionDownload as $productDownload) {
            /** @var ProductDownload $productDownload */
            $result &= $productDownload->delete($productDownload->checkFile());
        }

        return $result;
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function deleteFromCartRules()
    {
        CartRule::cleanProductRuleIntegrity('products', $this->id);

        return true;
    }

    /**
     * Update the categories this product belongs to
     *
     * @param array $categories
     * @param bool  $keepCurrentPosition Someone thought it would be a good idea
     *                                   to add this parameter, but it has never actually
     *                                   done anything, so you can ignore it. Maybe we'll
     *                                   do something with it in thirty bees 1.1, maybe
     *                                   we don't. As for tb 1.0 we can't change its behavior
     *                                   due to backwards compatibility.
     *
     * @return bool Update/insertion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since    1.0.0
     * @version  1.0.0 Initial version
     */
    public function updateCategories($categories, $keepCurrentPosition = false)
    {
        if (empty($categories)) {
            return false;
        }

        $sql = new DbQuery();
        $sql->select('c.`id_category`');
        $sql->from('category_product', 'cp');
        $sql->leftJoin('category', 'c', 'c.`id_category` = cp.`id_category`');
        $sql->join(Shop::addSqlAssociation('category', 'c', true));
        $sql->where('cp.`id_category` NOT IN ('.implode(',', array_map('intval', $categories)).')');
        $sql->where('cp.`id_product` = '.(int) $this->id);
        $result = Db::getInstance()->executeS($sql);

        if (is_array($result)) {
            foreach ($result as $categoryToDelete) {
                $this->deleteCategory($categoryToDelete['id_category']);
            }
        }

        if (!$this->addToCategories($categories)) {
            return false;
        }

        SpecificPriceRule::applyAllRules([(int) $this->id]);

        return true;
    }

    /**
     * deleteCategory delete this product from the category $id_category
     *
     * @param mixed $idCategory
     * @param mixed $cleanPositions
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteCategory($idCategory, $cleanPositions = true)
    {
        $result = Db::getInstance()->executeS(
            'SELECT `id_category`, `position`
			FROM `'._DB_PREFIX_.'category_product`
			WHERE `id_product` = '.(int) $this->id.'
			AND id_category = '.(int) $idCategory.''
        );

        $return = Db::getInstance()->delete('category_product', 'id_product = '.(int) $this->id.' AND id_category = '.(int) $idCategory);
        if ($cleanPositions === true) {
            foreach ($result as $row) {
                $this->cleanPositions((int) $row['id_category'], (int) $row['position']);
            }
        }
        SpecificPriceRule::applyAllRules([(int) $this->id]);

        return $return;
    }

    /**
     * addToCategories add this product to the category/ies if not exists.
     *
     * @param mixed $categories id_category or array of id_category
     *
     * @return bool true if succeed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addToCategories($categories = [])
    {
        if (empty($categories)) {
            return false;
        }

        if (!is_array($categories)) {
            $categories = [$categories];
        }

        if (!count($categories)) {
            return false;
        }

        $categories = array_map('intval', $categories);

        $currentCategories = $this->getCategories();
        $currentCategories = array_map('intval', $currentCategories);

        // for new categ, put product at last position
        $resCategNewPos = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT id_category, MAX(position)+1 newPos
			FROM `'._DB_PREFIX_.'category_product`
			WHERE `id_category` IN('.implode(',', $categories).')
			GROUP BY id_category'
        );
        foreach ($resCategNewPos as $array) {
            $newCategories[(int) $array['id_category']] = (int) $array['newPos'];
        }

        $newCategoryPos = [];
        foreach ($categories as $idCategory) {
            $newCategoryPos[$idCategory] = isset($newCategories[$idCategory]) ? $newCategories[$idCategory] : 0;
        }

        foreach ($categories as $newIdCateg) {
            if (!in_array($newIdCateg, $currentCategories)) {
                Db::getInstance()->insert(
                    'category_product',
                    [
                        'id_category' => (int) $newIdCateg,
                        'id_product'  => (int) $this->id,
                        'position'    => (int) $newCategoryPos[$newIdCateg],
                    ],
                    false,
                    true,
                    Db::INSERT_IGNORE
                );
            }
        }

        return true;
    }

    /**
     * getCategories return an array of categories which this product belongs to
     *
     * @return array of categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getCategories()
    {
        return static::getProductCategories($this->id);
    }

    /**
     * getProductCategories return an array of categories which this product belongs to
     *
     * @param string $idProduct
     *
     * @return array of categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProductCategories($idProduct = '')
    {
        $cacheId = 'Product::getProductCategories_'.(int) $idProduct;
        if (!Cache::isStored($cacheId)) {
            $ret = [];

            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
				SELECT `id_category` FROM `'._DB_PREFIX_.'category_product`
				WHERE `id_product` = '.(int) $idProduct
            );

            if ($row) {
                foreach ($row as $val) {
                    $ret[] = $val['id_category'];
                }
            }
            Cache::store($cacheId, $ret);

            return $ret;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * addProductAttribute is deprecated
     *
     * The quantity params now set StockAvailable for the current shop with the specified quantity
     * The supplier_reference params now set the supplier reference of the default supplier of the product if possible
     *
     * @see        StockManager if you want to manage real stock
     * @see        StockAvailable if you want to manage available quantities for sale on your shop(s)
     * @see        ProductSupplier for manage supplier reference(s)
     *
     * @deprecated since 1.5.0
     *
     * @param      $price
     * @param      $weight
     * @param      $unitImpact
     * @param      $ecotax
     * @param      $quantity
     * @param      $idImages
     * @param      $reference
     * @param null $idSupplier
     * @param      $ean13
     * @param      $default
     * @param null $location
     * @param null $upc
     * @param int  $minimalQuantity
     *
     * @return bool|mixed
     * @throws PrestaShopException
     */
    public function addProductAttribute(
        $price,
        $weight,
        $unitImpact,
        $ecotax,
        $quantity,
        $idImages,
        $reference,
        $idSupplier = null,
        $ean13,
        $default,
        $location = null,
        $upc = null,
        $minimalQuantity = 1
    ) {
        Tools::displayAsDeprecated();

        $idProductAttribute = $this->addAttribute($price, $weight, $unitImpact, $ecotax, $idImages, $reference, $ean13, $default, $location, $upc, $minimalQuantity);

        if (!$idProductAttribute) {
            return false;
        }

        StockAvailable::setQuantity($this->id, $idProductAttribute, $quantity);
        //Try to set the default supplier reference
        $this->addSupplierReference($idSupplier, $idProductAttribute);

        return $idProductAttribute;
    }

    /**
     * Add a product attribute
     *
     * @since 1.5.0.1
     *
     * @param float  $price           Additional price
     * @param float  $weight          Additional weight
     * @param float  $unitImpact
     * @param float  $ecotax          Additional ecotax
     * @param int    $idImages        Image ids
     * @param string $reference       Reference
     * @param string $ean13           Ean-13 barcode
     * @param bool   $default         Is default attribute for product
     * @param string $location        Location
     * @param null   $upc
     * @param int    $minimalQuantity Minimal quantity to add to cart
     * @param array  $idShopList
     * @param null   $availableDate
     *
     * @return mixed $id_product_attribute or false
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function addAttribute(
        $price,
        $weight,
        $unitImpact,
        $ecotax,
        $idImages,
        $reference,
        $ean13,
        $default,
        $location = null,
        $upc = null,
        $minimalQuantity = 1,
        array $idShopList = [],
        $availableDate = null
    ) {
        if (!$this->id) {
            return;
        }

        $price = str_replace(',', '.', $price);
        $weight = str_replace(',', '.', $weight);

        $combination = new Combination();
        $combination->id_product = (int) $this->id;
        $combination->price = round($price, _TB_PRICE_DATABASE_PRECISION_);
        $combination->ecotax = round($ecotax, _TB_PRICE_DATABASE_PRECISION_);
        $combination->quantity = 0;
        $combination->weight = (float) $weight;
        $combination->unit_price_impact = round(
            $unitImpact,
            _TB_PRICE_DATABASE_PRECISION_
        );
        $combination->reference = pSQL($reference);
        $combination->location = pSQL($location);
        $combination->ean13 = pSQL($ean13);
        $combination->upc = pSQL($upc);
        $combination->default_on = (int) $default;
        $combination->minimal_quantity = (int) $minimalQuantity;
        $combination->available_date = $availableDate;

        if (count($idShopList)) {
            $combination->id_shop_list = array_unique($idShopList);
        }

        $combination->add();

        if (!$combination->id) {
            return false;
        }

        $totalQuantity = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
			SELECT SUM(quantity) AS quantity
			FROM '._DB_PREFIX_.'stock_available
			WHERE id_product = '.(int) $this->id.'
			AND id_product_attribute <> 0 '
        );

        if (!$totalQuantity) {
            Db::getInstance()->update('stock_available', ['quantity' => 0], '`id_product` = '.$this->id);
        }

        $idDefaultAttribute = static::updateDefaultAttribute($this->id);

        if ($idDefaultAttribute) {
            $this->cache_default_attribute = $idDefaultAttribute;
            if (!$combination->available_date) {
                $this->setAvailableDate();
            }
        }

        if (!empty($idImages)) {
            $combination->setImages($idImages);
        }

        Tools::clearColorListCache($this->id);

        if (Configuration::get('PS_DEFAULT_WAREHOUSE_NEW_PRODUCT') != 0 && Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            $warehouseLocationEntity = new WarehouseProductLocation();
            $warehouseLocationEntity->id_product = $this->id;
            $warehouseLocationEntity->id_product_attribute = (int) $combination->id;
            $warehouseLocationEntity->id_warehouse = Configuration::get('PS_DEFAULT_WAREHOUSE_NEW_PRODUCT');
            $warehouseLocationEntity->location = pSQL('');
            $warehouseLocationEntity->save();
        }

        return (int) $combination->id;
    }

    /**
     * @param int $idProduct
     *
     * @return bool|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function updateDefaultAttribute($idProduct)
    {
        $idDefaultAttribute = (int) static::getDefaultAttribute($idProduct, 0, true);

        $result = Db::getInstance()->update(
            'product_shop',
            [
                'cache_default_attribute' => $idDefaultAttribute,
            ],
            'id_product = '.(int) $idProduct.Shop::addSqlRestriction()
        );

        $result &= Db::getInstance()->update(
            'product',
            [
                'cache_default_attribute' => $idDefaultAttribute,
            ],
            'id_product = '.(int) $idProduct
        );

        if ($result && $idDefaultAttribute) {
            return $idDefaultAttribute;
        } else {
            return $result;
        }
    }

    /**
     * @param int $idProduct
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getShopsByProduct($idProduct)
    {
        return Db::getInstance()->executeS(
            '
			SELECT `id_shop`
			FROM `'._DB_PREFIX_.'product_shop`
			WHERE `id_product` = '.(int) $idProduct
        );
    }

    /**
     * @param $combinations
     * @param $attributes
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function generateMultipleCombinations($combinations, $attributes)
    {
        $res = true;
        $defaultOn = 1;
        foreach ($combinations as $key => $combination) {
            $idCombination = (int) $this->productAttributeExists($attributes[$key], false, null, true, true);
            $obj = new Combination($idCombination);

            if ($idCombination) {
                $obj->minimal_quantity = 1;
                $obj->available_date = '0000-00-00';
            }

            foreach ($combination as $field => $value) {
                $obj->$field = $value;
            }

            $obj->default_on = $defaultOn;
            $defaultOn = 0;
            $this->setAvailableDate();

            $obj->save();

            if (!$idCombination) {
                $attributeList = [];
                foreach ($attributes[$key] as $idAttribute) {
                    $attributeList[] = [
                        'id_product_attribute' => (int) $obj->id,
                        'id_attribute'         => (int) $idAttribute,
                    ];
                }
                $res &= Db::getInstance()->insert('product_attribute_combination', $attributeList);
            }
        }

        return $res;
    }

    /**
     * @param array        $attributesList
     * @param bool         $currentProductAttribute
     * @param Context|null $context
     * @param bool         $allShops
     * @param bool         $returnId
     *
     * @return bool|int|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function productAttributeExists($attributesList, $currentProductAttribute = false, Context $context = null, $allShops = false, $returnId = false)
    {
        if (!Combination::isFeatureActive()) {
            return false;
        }
        if ($context === null) {
            $context = Context::getContext();
        }
        $result = Db::getInstance()->executeS(
            'SELECT pac.`id_attribute`, pac.`id_product_attribute`
			FROM `'._DB_PREFIX_.'product_attribute` pa
			JOIN `'._DB_PREFIX_.'product_attribute_shop` pas ON (pas.id_product_attribute = pa.id_product_attribute)
			LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
			WHERE 1 '.(!$allShops ? ' AND pas.id_shop ='.(int) $context->shop->id : '').' AND pa.`id_product` = '.(int) $this->id.
            ($allShops ? ' GROUP BY pac.id_attribute, pac.id_product_attribute ' : '')
        );

        /* If something's wrong */
        if (!$result || empty($result)) {
            return false;
        }
        /* Product attributes simulation */
        $productAttributes = [];
        foreach ($result as $productAttribute) {
            $productAttributes[$productAttribute['id_product_attribute']][] = $productAttribute['id_attribute'];
        }
        /* Checking product's attribute existence */
        foreach ($productAttributes as $key => $productAttribute) {
            if (count($productAttribute) == count($attributesList)) {
                $diff = false;
                for ($i = 0; $diff == false && isset($productAttribute[$i]); $i++) {
                    if (!in_array($productAttribute[$i], $attributesList) || $key == $currentProductAttribute) {
                        $diff = true;
                    }
                }
                if (!$diff) {
                    if ($returnId) {
                        return $key;
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $availableDate
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setAvailableDate($availableDate = '0000-00-00')
    {
        if (Validate::isDateFormat($availableDate) && $this->available_date != $availableDate) {
            $this->available_date = $availableDate;

            return $this->update();
        }

        return false;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($nullValues = false)
    {
        if (PageCache::isEnabled()) {
            PageCache::invalidateEntity('product', $this->id);
        }

        $return = parent::update($nullValues);

        $this->setGroupReduction();

        // Sync stock Reference, EAN13 and UPC
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && StockAvailable::dependsOnStock($this->id, Context::getContext()->shop->id)) {
            Db::getInstance()->update(
                'stock',
                [
                    'reference' => pSQL($this->reference),
                    'ean13'     => pSQL($this->ean13),
                    'upc'       => pSQL($this->upc),
                ],
                'id_product = '.(int) $this->id.' AND id_product_attribute = 0'
            );
        }

        Hook::exec('actionProductSave', ['id_product' => (int) $this->id, 'product' => $this]);
        Hook::exec('actionProductUpdate', ['id_product' => (int) $this->id, 'product' => $this]);
        if ($this->getType() == static::PTYPE_VIRTUAL && $this->active && !Configuration::get('PS_VIRTUAL_PROD_FEATURE_ACTIVE')) {
            Configuration::updateGlobalValue('PS_VIRTUAL_PROD_FEATURE_ACTIVE', '1');
        }

        return $return;
    }

    /**
     * Set Group reduction if needed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setGroupReduction()
    {
        return GroupReduction::setProductReduction($this->id);
    }

    /**
     * Get the product type (simple, virtual, pack)
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getType()
    {
        if (!$this->id) {
            return static::PTYPE_SIMPLE;
        }
        if (Pack::isPack($this->id)) {
            return static::PTYPE_PACK;
        }
        if ($this->is_virtual) {
            return static::PTYPE_VIRTUAL;
        }

        return static::PTYPE_SIMPLE;
    }

    /**
     * @param       $wholesalePrice
     * @param       $price
     * @param       $weight
     * @param       $unitImpact
     * @param       $ecotax
     * @param int   $quantity DEPRECATED
     * @param       $idImages
     * @param       $reference
     * @param       $idSupplier
     * @param       $ean13
     * @param       $default
     * @param null  $location
     * @param null  $upc
     * @param int   $minimalQuantity
     * @param array $idShopList
     * @param null  $availableDate
     *
     * @return bool|mixed
     * @internal param string $supplier_reference DEPRECATED
     *
     * @since    1.0.0
     * @version  1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function addCombinationEntity(
        $wholesalePrice,
        $price,
        $weight,
        $unitImpact,
        $ecotax,
        $quantity,
        $idImages,
        $reference,
        $idSupplier,
        $ean13,
        $default,
        $location = null,
        $upc = null,
        $minimalQuantity = 1,
        array $idShopList = [],
        $availableDate = null
    ) {
        $idProductAttribute = $this->addAttribute($price, $weight, $unitImpact, $ecotax, $idImages, $reference, $ean13, $default, $location, $upc, $minimalQuantity, $idShopList, $availableDate);
        $this->addSupplierReference($idSupplier, $idProductAttribute);
        $result = ObjectModel::updateMultishopTable(
            'Combination',
            [
                'wholesale_price' => round(
                    $wholesalePrice,
                    _TB_PRICE_DATABASE_PRECISION_
                ),
            ],
            'a.id_product_attribute = '.(int) $idProductAttribute
        );

        if (!$idProductAttribute || !$result) {
            return false;
        }

        return $idProductAttribute;
    }

    /**
     * Sets or updates Supplier Reference
     *
     * @param int    $idSupplier
     * @param int    $idProductAttribute
     * @param string $supplierReference
     * @param float  $price
     * @param int    $idCurrency
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function addSupplierReference($idSupplier, $idProductAttribute, $supplierReference = null, $price = null, $idCurrency = null)
    {
        //in some case we need to add price without supplier reference
        if ($supplierReference === null) {
            $supplierReference = '';
        }

        //Try to set the default supplier reference
        if (($idSupplier > 0) && ($this->id > 0)) {
            $idProductSupplier = (int) ProductSupplier::getIdByProductAndSupplier($this->id, $idProductAttribute, $idSupplier);

            $productSupplier = new ProductSupplier($idProductSupplier);

            if (!$idProductSupplier) {
                $productSupplier->id_product = (int) $this->id;
                $productSupplier->id_product_attribute = (int) $idProductAttribute;
                $productSupplier->id_supplier = (int) $idSupplier;
            }

            $productSupplier->product_supplier_reference = pSQL($supplierReference);
            $productSupplier->product_supplier_price_te = !is_null($price) ? (float) $price : (float) $productSupplier->product_supplier_price_te;
            $productSupplier->id_currency = !is_null($idCurrency) ? (int) $idCurrency : (int) $productSupplier->id_currency;
            $productSupplier->save();
        }
    }

    /**
     * @param array $attributes
     * @param bool  $setDefault
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addProductAttributeMultiple($attributes, $setDefault = true)
    {
        Tools::displayAsDeprecated();
        $return = [];
        $defaultValue = 1;
        foreach ($attributes as &$attribute) {
            $obj = new Combination();
            foreach ($attribute as $key => $value) {
                $obj->$key = $value;
            }

            if ($setDefault) {
                $obj->default_on = $defaultValue;
                $defaultValue = 0;
                // if we add a combination for this shop and this product does not use the combination feature in other shop,
                // we clone the default combination in every shop linked to this product
                if (!$this->hasAttributesInOtherShops()) {
                    $idShopListArray = static::getShopsByProduct($this->id);
                    $idShopList = [];
                    foreach ($idShopListArray as $arrayShop) {
                        $idShopList[] = $arrayShop['id_shop'];
                    }
                    $obj->id_shop_list = $idShopList;
                }
            }
            $obj->add();
            $return[] = $obj->id;
        }

        return $return;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function hasAttributesInOtherShops()
    {
        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
			SELECT pa.id_product_attribute
			FROM `'._DB_PREFIX_.'product_attribute` pa
			LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` pas ON (pa.`id_product_attribute` = pas.`id_product_attribute`)
			WHERE pa.`id_product` = '.(int) $this->id
        );
    }

    /**
     * Update a product attribute
     *
     * @deprecated since 1.5
     * @see        updateAttribute() to use instead
     * @see        ProductSupplier for manage supplier reference(s)
     *
     * @param int    $idProductAttribute
     * @param float  $wholesalePrice
     * @param float  $price
     * @param float  $weight
     * @param        $unit
     * @param float  $ecotax
     * @param array  $idImages
     * @param string $reference
     * @param null   $idSupplier
     * @param string $ean13
     * @param        $default
     * @param null   $location
     * @param null   $upc
     * @param int    $minimalQuantity
     * @param string $availableDate
     *
     * @return array
     * @throws PrestaShopException
     */
    public function updateProductAttribute(
        $idProductAttribute,
        $wholesalePrice,
        $price,
        $weight,
        $unit,
        $ecotax,
        $idImages,
        $reference,
        $idSupplier = null,
        $ean13,
        $default,
        $location = null,
        $upc = null,
        $minimalQuantity,
        $availableDate
    ) {
        Tools::displayAsDeprecated();

        $return = $this->updateAttribute($idProductAttribute, $wholesalePrice, $price, $weight, $unit, $ecotax, $idImages, $reference, $ean13, $default, $location = null, $upc = null, $minimalQuantity, $availableDate);
        $this->addSupplierReference($idSupplier, $idProductAttribute);

        return $return;
    }

    /**
     * Update a product attribute
     *
     * @param int    $idProductAttribute Product attribute id
     * @param float  $wholesalePrice     Wholesale price
     * @param float  $price              Additional price
     * @param float  $weight             Additional weight
     * @param float  $unit
     * @param float  $ecotax             Additional ecotax
     * @param array  $idImages           Image id
     * @param string $reference          Reference
     * @param string $ean13              Ean-13 barcode
     * @param int    $default            Default On
     * @param null   $location
     * @param string $upc                Upc barcode
     * @param string $minimalQuantity    Minimal quantity
     * @param null   $availableDate
     * @param bool   $updateAllFields
     * @param array  $idShopList
     *
     * @return array Update result
     * @throws PrestaShopException
     */
    public function updateAttribute(
        $idProductAttribute,
        $wholesalePrice,
        $price,
        $weight,
        $unit,
        $ecotax,
        $idImages,
        $reference,
        $ean13,
        $default,
        $location = null,
        $upc = null,
        $minimalQuantity = null,
        $availableDate = null,
        $updateAllFields = true,
        array $idShopList = []
    ) {
        $combination = new Combination($idProductAttribute);

        if (!$updateAllFields) {
            $combination->setFieldsToUpdate(
                [
                    'price'             => !is_null($price),
                    'wholesale_price'   => !is_null($wholesalePrice),
                    'ecotax'            => !is_null($ecotax),
                    'weight'            => !is_null($weight),
                    'unit_price_impact' => !is_null($unit),
                    'default_on'        => !is_null($default),
                    'minimal_quantity'  => !is_null($minimalQuantity),
                    'available_date'    => !is_null($availableDate),
                ]
            );
        }

        $price = str_replace(',', '.', $price);
        $weight = str_replace(',', '.', $weight);

        $combination->price = round($price, _TB_PRICE_DATABASE_PRECISION_);
        $combination->wholesale_price = round(
            $wholesalePrice,
            _TB_PRICE_DATABASE_PRECISION_
        );
        $combination->ecotax = round($ecotax, _TB_PRICE_DATABASE_PRECISION_);
        $combination->weight = (float) $weight;
        $combination->unit_price_impact = round(
            $unit,
            _TB_PRICE_DATABASE_PRECISION_
        );
        $combination->reference = pSQL($reference);
        $combination->location = pSQL($location);
        $combination->ean13 = pSQL($ean13);
        $combination->upc = pSQL($upc);
        $combination->default_on = (int) $default;
        $combination->minimal_quantity = (int) $minimalQuantity;
        $combination->available_date = $availableDate ? pSQL($availableDate) : '0000-00-00';

        if (count($idShopList)) {
            $combination->id_shop_list = $idShopList;
        }

        $combination->save();

        if (is_array($idImages) && count($idImages)) {
            $combination->setImages($idImages);
        }

        $idDefaultAttribute = (int) static::updateDefaultAttribute($this->id);
        if ($idDefaultAttribute) {
            $this->cache_default_attribute = $idDefaultAttribute;
        }

        // Sync stock Reference, EAN13 and UPC for this attribute
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && StockAvailable::dependsOnStock($this->id, Context::getContext()->shop->id)) {
            Db::getInstance()->update(
                'stock',
                [
                    'reference' => pSQL($reference),
                    'ean13'     => pSQL($ean13),
                    'upc'       => pSQL($upc),
                ],
                'id_product = '.$this->id.' AND id_product_attribute = '.(int) $idProductAttribute
            );
        }

        Hook::exec('actionProductAttributeUpdate', ['id_product_attribute' => (int) $idProductAttribute]);
        Tools::clearColorListCache($this->id);

        return true;
    }

    /**
     * @return bool
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    public function updateQuantityProductWithAttributeQuantity()
    {
        Tools::displayAsDeprecated();

        return Db::getInstance()->execute(
            '
		UPDATE `'._DB_PREFIX_.'product`
		SET `quantity` = IFNULL(
		(
			SELECT SUM(`quantity`)
			FROM `'._DB_PREFIX_.'product_attribute`
			WHERE `id_product` = '.(int) $this->id.'
		), \'0\')
		WHERE `id_product` = '.(int) $this->id
        );
    }

    /**
     * Add a product attributes combinaison
     *
     * @param int   $idProductAttribute Product attribute id
     * @param array $attributes         Attributes to forge combinaison
     *
     * @return bool Insertion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function addAttributeCombinaison($idProductAttribute, $attributes)
    {
        Tools::displayAsDeprecated();
        if (!is_array($attributes)) {
            die(Tools::displayError());
        }
        if (!count($attributes)) {
            return false;
        }

        $combination = new Combination((int) $idProductAttribute);

        return $combination->setAttributes($attributes);
    }

    /**
     * @deprecated 1.0.0
     *
     * @param array $idAttributes
     * @param array $combinations
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addAttributeCombinationMultiple($idAttributes, $combinations)
    {
        Tools::displayAsDeprecated();
        $attributesList = [];
        foreach ($idAttributes as $nb => $idProductAttribute) {
            if (isset($combinations[$nb])) {
                foreach ($combinations[$nb] as $idAttribute) {
                    $attributesList[] = [
                        'id_product_attribute' => (int) $idProductAttribute,
                        'id_attribute'         => (int) $idAttribute,
                    ];
                }
            }
        }

        return Db::getInstance()->insert('product_attribute_combination', $attributesList);
    }

    /**
     * Get all available product attributes resume
     *
     * @param int    $idLang                  Language id
     * @param string $attributeValueSeparator
     * @param string $attributeSeparator
     *
     * @return array Product attributes combinations
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getAttributesResume($idLang, $attributeValueSeparator = ' - ', $attributeSeparator = ', ')
    {
        if (!Combination::isFeatureActive()) {
            return [];
        }

        $combinations = Db::getInstance()->executeS(
            'SELECT pa.*, product_attribute_shop.*
				FROM `'._DB_PREFIX_.'product_attribute` pa
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				WHERE pa.`id_product` = '.(int) $this->id.'
				GROUP BY pa.`id_product_attribute`'
        );

        if (!$combinations) {
            return false;
        }

        $productAttributes = [];
        foreach ($combinations as $combination) {
            $productAttributes[] = (int) $combination['id_product_attribute'];
        }

        $lang = Db::getInstance()->executeS(
            'SELECT pac.id_product_attribute, GROUP_CONCAT(agl.`name`, \''.pSQL($attributeValueSeparator).'\',al.`name` ORDER BY agl.`id_attribute_group` SEPARATOR \''.pSQL($attributeSeparator).'\') as attribute_designation
				FROM `'._DB_PREFIX_.'product_attribute_combination` pac
				LEFT JOIN `'._DB_PREFIX_.'attribute` a ON a.`id_attribute` = pac.`id_attribute`
				LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
				LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) $idLang.')
				LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) $idLang.')
				WHERE pac.id_product_attribute IN ('.implode(',', $productAttributes).')
				GROUP BY pac.id_product_attribute'
        );

        foreach ($lang as $k => $row) {
            $combinations[$k]['attribute_designation'] = $row['attribute_designation'];
        }

        //Get quantity of each variations
        foreach ($combinations as $key => $row) {
            $cacheKey = $row['id_product'].'_'.$row['id_product_attribute'].'_quantity';

            if (!Cache::isStored($cacheKey)) {
                $result = StockAvailable::getQuantityAvailableByProduct($row['id_product'], $row['id_product_attribute']);
                Cache::store(
                    $cacheKey,
                    $result
                );
                $combinations[$key]['quantity'] = $result;
            } else {
                $combinations[$key]['quantity'] = Cache::retrieve($cacheKey);
            }
        }

        return $combinations;
    }

    /**
     * Get product attribute combination by id_product_attribute
     *
     * @param int $idProductAttribute
     * @param int $idLang Language id
     *
     * @return array Product attribute combination by id_product_attribute
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getAttributeCombinationsById($idProductAttribute, $idLang)
    {
        if (!Combination::isFeatureActive()) {
            return [];
        }
        $sql = 'SELECT pa.*, product_attribute_shop.*, ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, al.`name` AS attribute_name,
					a.`id_attribute`
				FROM `'._DB_PREFIX_.'product_attribute` pa
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
				LEFT JOIN `'._DB_PREFIX_.'attribute` a ON a.`id_attribute` = pac.`id_attribute`
				LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
				LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) $idLang.')
				LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) $idLang.')
				WHERE pa.`id_product` = '.(int) $this->id.'
				AND pa.`id_product_attribute` = '.(int) $idProductAttribute.'
				GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
				ORDER BY pa.`id_product_attribute`';

        $res = Db::getInstance()->executeS($sql);

        //Get quantity of each variations
        foreach ($res as $key => $row) {
            $cacheKey = $row['id_product'].'_'.$row['id_product_attribute'].'_quantity';

            if (!Cache::isStored($cacheKey)) {
                $result = StockAvailable::getQuantityAvailableByProduct($row['id_product'], $row['id_product_attribute']);
                Cache::store(
                    $cacheKey,
                    $result
                );
                $res[$key]['quantity'] = $result;
            } else {
                $res[$key]['quantity'] = Cache::retrieve($cacheKey);
            }
        }

        return $res;
    }

    /**
     * @param int $idLang
     *
     * @return array|bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getCombinationImages($idLang)
    {
        if (!Combination::isFeatureActive()) {
            return false;
        }

        $productAttributes = Db::getInstance()->executeS(
            'SELECT `id_product_attribute`
			FROM `'._DB_PREFIX_.'product_attribute`
			WHERE `id_product` = '.(int) $this->id
        );

        if (!$productAttributes) {
            return false;
        }

        $ids = [];

        foreach ($productAttributes as $productAttribute) {
            $ids[] = (int) $productAttribute['id_product_attribute'];
        }

        $result = Db::getInstance()->executeS(
            '
			SELECT pai.`id_image`, pai.`id_product_attribute`, il.`legend`
			FROM `'._DB_PREFIX_.'product_attribute_image` pai
			LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (il.`id_image` = pai.`id_image`)
			LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_image` = pai.`id_image`)
			WHERE pai.`id_product_attribute` IN ('.implode(', ', $ids).') AND il.`id_lang` = '.(int) $idLang.' ORDER BY i.`position`'
        );

        if (!$result) {
            return false;
        }

        $images = [];

        foreach ($result as $row) {
            $images[$row['id_product_attribute']][] = $row;
        }

        return $images;
    }

    /**
     * Check if product has attributes combinations
     *
     * @return int Attributes combinations number
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function hasAttributes()
    {
        if (!Combination::isFeatureActive()) {
            return 0;
        }

	    $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
		    (new DbQuery())
			    ->select('COUNT(*)')
			    ->from('product_attribute', 'pa')
			    ->join(Shop::addSqlAssociation('product_attribute', 'pa'))
			    ->where('pa.`id_product` = '.(int) $this->id)
	    );

        return (int) $result;
    }

    /**
     * Gets carriers assigned to the product
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @return mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getCarriers()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT c.*
			FROM `'._DB_PREFIX_.'product_carrier` pc
			INNER JOIN `'._DB_PREFIX_.'carrier` c
				ON (c.`id_reference` = pc.`id_carrier_reference` AND c.`deleted` = 0)
			WHERE pc.`id_product` = '.(int) $this->id.'
				AND pc.`id_shop` = '.(int) $this->id_shop
        );
    }

    /**
     * Sets carriers assigned to the product
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param array $carrierList
     *
     * @throws PrestaShopException
     */
    public function setCarriers($carrierList)
    {
        $data = [];

        foreach ($carrierList as $carrier) {
            $data[] = [
                'id_product'           => (int) $this->id,
                'id_carrier_reference' => (int) $carrier,
                'id_shop'              => (int) $this->id_shop,
            ];
        }
        Db::getInstance()->execute(
            'DELETE FROM `'._DB_PREFIX_.'product_carrier`
			WHERE id_product = '.(int) $this->id.'
			AND id_shop = '.(int) $this->id_shop
        );

        $uniqueArray = [];
        foreach ($data as $subArray) {
            if (!in_array($subArray, $uniqueArray)) {
                $uniqueArray[] = $subArray;
            }
        }

        if (count($uniqueArray)) {
            Db::getInstance()->insert('product_carrier', $uniqueArray, false, true, Db::INSERT_IGNORE);
        }
    }

    /**
     * Get product images and legends
     *
     * @param int     $idLang Language id for multilingual legends
     * @param Context $context
     *
     * @return array Product images and legends
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getImages($idLang, Context $context = null)
    {
        return Db::getInstance()->executeS(
            '
			SELECT image_shop.`cover`, i.`id_image`, il.`legend`, i.`position`
			FROM `'._DB_PREFIX_.'image` i
			'.Shop::addSqlAssociation('image', 'i').'
			LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $idLang.')
			WHERE i.`id_product` = '.(int) $this->id.'
			ORDER BY `position`'
        );
    }

    /**
     * Get product price
     * Same as static function getPriceStatic, no need to specify product id
     *
     * @param bool $tax                With taxes or not (optional)
     * @param int  $idProductAttribute Product attribute id (optional)
     * @param int  $decimals           Number of decimals (optional)
     * @param int  $divisor            Util when paying many time without fees (optional)
     *
     * @return float Product price in euros
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getPrice(
        $tax = true,
        $idProductAttribute = null,
        $decimals = _TB_PRICE_DATABASE_PRECISION_,
        $divisor = null,
        $onlyReduc = false,
        $usereduc = true,
        $quantity = 1
    ) {
        return static::getPriceStatic((int) $this->id, $tax, $idProductAttribute, $decimals, $divisor, $onlyReduc, $usereduc, $quantity);
    }

    /*
    ** Customization fields' label management
    */

    /**
     * @param bool $tax
     * @param null $idProductAttribute
     * @param int  $decimals
     * @param null $divisor
     * @param bool $onlyReduc
     * @param bool $usereduc
     * @param int  $quantity
     *
     * @return float
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getPublicPrice(
        $tax = true,
        $idProductAttribute = null,
        $decimals = _TB_PRICE_DATABASE_PRECISION_,
        $divisor = null,
        $onlyReduc = false,
        $usereduc = true,
        $quantity = 1
    ) {
        $specificPriceOutput = null;

        return static::getPriceStatic((int) $this->id, $tax, $idProductAttribute, $decimals, $divisor, $onlyReduc, $usereduc, $quantity, false, null, null, null, $specificPriceOutput, true, true, null, false);
    }

    /**
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getIdProductAttributeMostExpensive()
    {
        if (!Combination::isFeatureActive()) {
            return 0;
        }

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
		SELECT pa.`id_product_attribute`
		FROM `'._DB_PREFIX_.'product_attribute` pa
		'.Shop::addSqlAssociation('product_attribute', 'pa').'
		WHERE pa.`id_product` = '.(int) $this->id.'
		ORDER BY product_attribute_shop.`price` DESC'
        );
    }

    /**
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getDefaultIdProductAttribute()
    {
        if (!Combination::isFeatureActive()) {
            return 0;
        }

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
			SELECT pa.`id_product_attribute`
			FROM `'._DB_PREFIX_.'product_attribute` pa
			'.Shop::addSqlAssociation('product_attribute', 'pa').'
			WHERE pa.`id_product` = '.(int) $this->id.'
			AND product_attribute_shop.default_on = 1'
        );
    }

    /**
     * @param bool $notax
     * @param bool $idProductAttribute
     * @param int  $decimals
     *
     * @return float
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getPriceWithoutReduct($notax = false, $idProductAttribute = false, $decimals = _TB_PRICE_DATABASE_PRECISION_)
    {
        return static::getPriceStatic((int) $this->id, !$notax, $idProductAttribute, $decimals, null, false, false);
    }

    /**
     * Check product availability
     *
     * @param int $qty Quantity desired
     *
     * @return bool True if product is available with this quantity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function checkQty($qty)
    {
        if (Pack::isPack((int) $this->id) && !Pack::isInStock((int) $this->id)) {
            return false;
        }

        if ($this->isAvailableWhenOutOfStock(StockAvailable::outOfStock($this->id))) {
            return true;
        }

        if (isset($this->id_product_attribute)) {
            $idProductAttribute = $this->id_product_attribute;
        } else {
            $idProductAttribute = 0;
        }

        return ($qty <= StockAvailable::getQuantityAvailableByProduct($this->id, $idProductAttribute));
    }

    /**
     * Check if there is no default attribute and create it if not
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function checkDefaultAttributes()
    {
        if (!$this->id) {
            return false;
        }

        if (Db::getInstance()->getValue(
                'SELECT COUNT(*)
				FROM `'._DB_PREFIX_.'product_attribute` pa
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				WHERE product_attribute_shop.`default_on` = 1
				AND pa.`id_product` = '.(int) $this->id
            ) > Shop::getTotalShops(true)
        ) {
            Db::getInstance()->execute(
                'UPDATE '._DB_PREFIX_.'product_attribute_shop product_attribute_shop, '._DB_PREFIX_.'product_attribute pa
					SET product_attribute_shop.default_on=NULL, pa.default_on = NULL
					WHERE product_attribute_shop.id_product_attribute=pa.id_product_attribute AND pa.id_product='.(int) $this->id.Shop::addSqlRestriction(false, 'product_attribute_shop')
            );
        }

        $row = Db::getInstance()->getRow(
            '
			SELECT pa.id_product
			FROM `'._DB_PREFIX_.'product_attribute` pa
			'.Shop::addSqlAssociation('product_attribute', 'pa').'
			WHERE product_attribute_shop.`default_on` = 1
				AND pa.`id_product` = '.(int) $this->id
        );
        if ($row) {
            return true;
        }

        $mini = Db::getInstance()->getRow(
            '
		SELECT MIN(pa.id_product_attribute) AS `id_attr`
		FROM `'._DB_PREFIX_.'product_attribute` pa
			'.Shop::addSqlAssociation('product_attribute', 'pa').'
			WHERE pa.`id_product` = '.(int) $this->id
        );
        if (!$mini) {
            return false;
        }

        if (!ObjectModel::updateMultishopTable('Combination', ['default_on' => 1], 'a.id_product_attribute = '.(int) $mini['id_attr'])) {
            return false;
        }

        return true;
    }

    /**
     * Get all available attribute groups
     *
     * @param int $idLang Language id
     *
     * @return array Attribute groups
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getAttributesGroups($idLang)
    {
        if (!Combination::isFeatureActive()) {
            return [];
        }
        $sql = 'SELECT ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, agl.`public_name` AS public_group_name,
					a.`id_attribute`, al.`name` AS attribute_name, a.`color` AS attribute_color, product_attribute_shop.`id_product_attribute`,
					IFNULL(stock.quantity, 0) AS quantity, product_attribute_shop.`price`, product_attribute_shop.`ecotax`, product_attribute_shop.`weight`,
					product_attribute_shop.`default_on`, pa.`reference`, product_attribute_shop.`unit_price_impact`,
					product_attribute_shop.`minimal_quantity`, product_attribute_shop.`available_date`, ag.`group_type`
				FROM `'._DB_PREFIX_.'product_attribute` pa
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				'.static::sqlStock('pa', 'pa').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
				LEFT JOIN `'._DB_PREFIX_.'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
				LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
				LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute`)
				LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group`)
				'.Shop::addSqlAssociation('attribute', 'a').'
				WHERE pa.`id_product` = '.(int) $this->id.'
					AND al.`id_lang` = '.(int) $idLang.'
					AND agl.`id_lang` = '.(int) $idLang.'
				GROUP BY id_attribute_group, id_product_attribute
				ORDER BY ag.`position` ASC, a.`position` ASC, agl.`name` ASC';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get product accessories
     *
     * @param int  $idLang Language id
     * @param bool $active
     *
     * @return array Product accessories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getAccessories($idLang, $active = true)
    {
        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`link_rewrite`,
					pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`,
					image_shop.`id_image` id_image, il.`legend`, m.`name` as manufacturer_name, cl.`name` AS category_default, IFNULL(product_attribute_shop.id_product_attribute, 0) id_product_attribute,
					DATEDIFF(
						p.`date_add`,
						DATE_SUB(
							"'.date('Y-m-d').' 00:00:00",
							INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY
						)
					) > 0 AS new
				FROM `'._DB_PREFIX_.'accessory`
				LEFT JOIN `'._DB_PREFIX_.'product` p ON p.`id_product` = `id_product_2`
				'.Shop::addSqlAssociation('product', 'p').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop
					ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $this->id_shop.')
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
					p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').'
				)
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (
					product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('cl').'
				)
				LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $this->id_shop.')
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $idLang.')
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (p.`id_manufacturer`= m.`id_manufacturer`)
				'.static::sqlStock('p', 0).'
				WHERE `id_product_1` = '.(int) $this->id.
            ($active ? ' AND product_shop.`active` = 1 AND product_shop.`visibility` != \'none\'' : '').'
				GROUP BY product_shop.id_product';

        if (!$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql)) {
            return false;
        }

        foreach ($result as &$row) {
            $row['id_product_attribute'] = static::getDefaultAttribute((int) $row['id_product']);
        }

        return $this->getProductsProperties($idLang, $result);
    }

    /**
     * Link accessories with product
     *
     * @param array $accessoriesId Accessories ids
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function changeAccessories($accessoriesId)
    {
        foreach ($accessoriesId as $idProduct2) {
            Db::getInstance()->insert(
                'accessory',
                [
                    'id_product_1' => (int) $this->id,
                    'id_product_2' => (int) $idProduct2,
                ]
            );
        }
    }

    /**
     * Add new feature to product
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param int    $idValue
     * @param int    $idLang
     * @param string $cust
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addFeaturesCustomToDB($idValue, $idLang, $cust)
    {
        $row = ['id_feature_value' => (int) $idValue, 'id_lang' => (int) $idLang, 'value' => pSQL($cust)];

        return Db::getInstance()->insert('feature_value_lang', $row);
    }

    /**
     * Get the link of the product page of this product
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param Context $context
     *
     * @return string
     */
    public function getLink(Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        return $context->link->getProductLink($this);
    }

    /**
     * @param int $idLang
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTags($idLang)
    {
        if (!$this->isFullyLoaded && is_null($this->tags)) {
            $this->tags = Tag::getProductTags($this->id);
        }

        if (!($this->tags && array_key_exists($idLang, $this->tags))) {
            return '';
        }

        $result = '';
        foreach ($this->tags[$idLang] as $tagName) {
            $result .= $tagName.', ';
        }

        return rtrim($result, ', ');
    }

    /**
     * @param int $idLang
     *
     * @return array|mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFrontFeatures($idLang)
    {
        return static::getFrontFeaturesStatic($idLang, $this->id);
    }

    /**
     * @param int $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getAttachments($idLang)
    {
        return static::getAttachmentsStatic($idLang, $this->id);
    }

    /**
     * @param int $uploadableFiles
     * @param int $textFields
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function createLabels($uploadableFiles, $textFields)
    {
        $languages = Language::getLanguages();
        if ((int) $uploadableFiles > 0) {
            for ($i = 0; $i < (int) $uploadableFiles; $i++) {
                if (!$this->_createLabel($languages, static::CUSTOMIZE_FILE)) {
                    return false;
                }
            }
        }

        if ((int) $textFields > 0) {
            for ($i = 0; $i < (int) $textFields; $i++) {
                if (!$this->_createLabel($languages, static::CUSTOMIZE_TEXTFIELD)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param array $languages
     * @param int   $type
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    protected function _createLabel($languages, $type)
    {
        // Label insertion
        if (!Db::getInstance()->execute(
                '
			INSERT INTO `'._DB_PREFIX_.'customization_field` (`id_product`, `type`, `required`)
			VALUES ('.(int) $this->id.', '.(int) $type.', 0)'
            ) ||
            !$idCustomizationField = (int) Db::getInstance()->Insert_ID()
        ) {
            return false;
        }

        // Multilingual label name creation
        $values = '';

        foreach ($languages as $language) {
            foreach (Shop::getContextListShopID() as $idShop) {
                $values .= '('.(int) $idCustomizationField.', '.(int) $language['id_lang'].', '.$idShop.',\'\'), ';
            }
        }

        $values = rtrim($values, ', ');
        if (!Db::getInstance()->execute(
            '
			INSERT INTO `'._DB_PREFIX_.'customization_field_lang` (`id_customization_field`, `id_lang`, `id_shop`, `name`)
			VALUES '.$values
        )
        ) {
            return false;
        }

        // Set cache of feature detachable to true
        Configuration::updateGlobalValue('PS_CUSTOMIZATION_FEATURE_ACTIVE', '1');

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function updateLabels()
    {
        $hasRequiredFields = 0;
        foreach ($_POST as $field => $value) {
            /* Label update */
            if (strncmp($field, 'label_', 6) == 0) {
                if (!$tmp = $this->_checkLabelField($field, $value)) {
                    return false;
                }
                /* Multilingual label name update */
                if (Shop::isFeatureActive()) {
                    foreach (Shop::getContextListShopID() as $idShop) {
                        if (!Db::getInstance()->execute(
                            'INSERT INTO `'._DB_PREFIX_.'customization_field_lang`
						(`id_customization_field`, `id_lang`, `id_shop`, `name`) VALUES ('.(int) $tmp[2].', '.(int) $tmp[3].', '.$idShop.', \''.pSQL($value).'\')
						ON DUPLICATE KEY UPDATE `name` = \''.pSQL($value).'\''
                        )
                        ) {
                            return false;
                        }
                    }
                } elseif (!Db::getInstance()->execute(
                    '
					INSERT INTO `'._DB_PREFIX_.'customization_field_lang`
					(`id_customization_field`, `id_lang`, `name`) VALUES ('.(int) $tmp[2].', '.(int) $tmp[3].', \''.pSQL($value).'\')
					ON DUPLICATE KEY UPDATE `name` = \''.pSQL($value).'\''
                )
                ) {
                    return false;
                }

                $isRequired = isset($_POST['require_'.(int) $tmp[1].'_'.(int) $tmp[2]]) ? 1 : 0;
                $hasRequiredFields |= $isRequired;
                /* Require option update */
                if (!Db::getInstance()->execute(
                    'UPDATE `'._DB_PREFIX_.'customization_field`
					SET `required` = '.(int) $isRequired.'
					WHERE `id_customization_field` = '.(int) $tmp[2]
                )
                ) {
                    return false;
                }
            }
        }

        if ($hasRequiredFields && !ObjectModel::updateMultishopTable('product', ['customizable' => 2], 'a.id_product = '.(int) $this->id)) {
            return false;
        }

        if (!$this->_deleteOldLabels()) {
            return false;
        }

        return true;
    }

    /**
     * @param $field
     * @param $value
     *
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _checkLabelField($field, $value)
    {
        if (!Validate::isLabel($value)) {
            return false;
        }
        $tmp = explode('_', $field);
        if (count($tmp) < 4) {
            return false;
        }

        return $tmp;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    protected function _deleteOldLabels()
    {
        $max = [
            static::CUSTOMIZE_FILE      => (int) $this->uploadable_files,
            static::CUSTOMIZE_TEXTFIELD => (int) $this->text_fields,
        ];

        /* Get customization field ids */
        if (($result = Db::getInstance()->executeS(
                'SELECT `id_customization_field`, `type`
			FROM `'._DB_PREFIX_.'customization_field`
			WHERE `id_product` = '.(int) $this->id.'
			ORDER BY `id_customization_field`'
            )) === false
        ) {
            return false;
        }

        if (empty($result)) {
            return true;
        }

        $customizationFields = [
            static::CUSTOMIZE_FILE      => [],
            static::CUSTOMIZE_TEXTFIELD => [],
        ];

        foreach ($result as $row) {
            $customizationFields[(int) $row['type']][] = (int) $row['id_customization_field'];
        }

        $extraFile = count($customizationFields[static::CUSTOMIZE_FILE]) - $max[static::CUSTOMIZE_FILE];
        $extraText = count($customizationFields[static::CUSTOMIZE_TEXTFIELD]) - $max[static::CUSTOMIZE_TEXTFIELD];

        /* If too much inside the database, deletion */
        if ($extraFile > 0 && count($customizationFields[static::CUSTOMIZE_FILE]) - $extraFile >= 0 &&
            (!Db::getInstance()->execute(
                'DELETE `'._DB_PREFIX_.'customization_field`,`'._DB_PREFIX_.'customization_field_lang`
			FROM `'._DB_PREFIX_.'customization_field` JOIN `'._DB_PREFIX_.'customization_field_lang`
			WHERE `'._DB_PREFIX_.'customization_field`.`id_product` = '.(int) $this->id.'
			AND `'._DB_PREFIX_.'customization_field`.`type` = '.static::CUSTOMIZE_FILE.'
			AND `'._DB_PREFIX_.'customization_field_lang`.`id_customization_field` = `'._DB_PREFIX_.'customization_field`.`id_customization_field`
			AND `'._DB_PREFIX_.'customization_field`.`id_customization_field` >= '.(int) $customizationFields[static::CUSTOMIZE_FILE][count($customizationFields[static::CUSTOMIZE_FILE]) - $extraFile]
            ))
        ) {
            return false;
        }

        if ($extraText > 0 && count($customizationFields[static::CUSTOMIZE_TEXTFIELD]) - $extraText >= 0 &&
            (!Db::getInstance()->execute(
                'DELETE `'._DB_PREFIX_.'customization_field`,`'._DB_PREFIX_.'customization_field_lang`
			FROM `'._DB_PREFIX_.'customization_field` JOIN `'._DB_PREFIX_.'customization_field_lang`
			WHERE `'._DB_PREFIX_.'customization_field`.`id_product` = '.(int) $this->id.'
			AND `'._DB_PREFIX_.'customization_field`.`type` = '.static::CUSTOMIZE_TEXTFIELD.'
			AND `'._DB_PREFIX_.'customization_field_lang`.`id_customization_field` = `'._DB_PREFIX_.'customization_field`.`id_customization_field`
			AND `'._DB_PREFIX_.'customization_field`.`id_customization_field` >= '.(int) $customizationFields[static::CUSTOMIZE_TEXTFIELD][count($customizationFields[static::CUSTOMIZE_TEXTFIELD]) - $extraText]
            ))
        ) {
            return false;
        }

        // Refresh cache of feature detachable
        Configuration::updateGlobalValue('PS_CUSTOMIZATION_FEATURE_ACTIVE', Customization::isCurrentlyUsed());

        return true;
    }

    /**
     * @param int|bool $idLang
     * @param null     $idShop
     *
     * @return array|bool|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getCustomizationFields($idLang = false, $idShop = null)
    {
        if (!Customization::isFeatureActive()) {
            return false;
        }

        if (Shop::isFeatureActive() && !$idShop) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        if (!$result = Db::getInstance()->executeS(
            '
			SELECT cf.`id_customization_field`, cf.`type`, cf.`required`, cfl.`name`, cfl.`id_lang`
			FROM `'._DB_PREFIX_.'customization_field` cf
			NATURAL JOIN `'._DB_PREFIX_.'customization_field_lang` cfl
			WHERE cf.`id_product` = '.(int) $this->id.($idLang ? ' AND cfl.`id_lang` = '.(int) $idLang : '').
            ($idShop ? ' AND cfl.`id_shop` = '.$idShop : '').'
			ORDER BY cf.`id_customization_field`'
        )
        ) {
            return false;
        }

        if ($idLang) {
            return $result;
        }

        $customizationFields = [];
        foreach ($result as $row) {
            $customizationFields[(int) $row['type']][(int) $row['id_customization_field']][(int) $row['id_lang']] = $row;
        }

        return $customizationFields;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getCustomizationFieldIds()
    {
        if (!Customization::isFeatureActive()) {
            return [];
        }

        return Db::getInstance()->executeS(
            '
			SELECT `id_customization_field`, `type`, `required`
			FROM `'._DB_PREFIX_.'customization_field`
			WHERE `id_product` = '.(int) $this->id
        );
    }

    /**
     * @param Context|null $context
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function hasAllRequiredCustomizableFields(Context $context = null)
    {
        if (!Customization::isFeatureActive()) {
            return true;
        }
        if (!$context) {
            $context = Context::getContext();
        }

        $fields = $context->cart->getProductCustomization($this->id, null, true);
        if (($requiredFields = $this->getRequiredCustomizableFields()) === false) {
            return false;
        }

        $fieldsPresent = [];
        foreach ($fields as $field) {
            $fieldsPresent[] = ['id_customization_field' => $field['index'], 'type' => $field['type']];
        }

        if (is_array($requiredFields) && count($requiredFields)) {
            foreach ($requiredFields as $requiredField) {
                if (!in_array($requiredField, $fieldsPresent)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getRequiredCustomizableFields()
    {
        if (!Customization::isFeatureActive()) {
            return [];
        }

        return static::getRequiredCustomizableFieldsStatic($this->id);
    }

    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getNoPackPrice()
    {
        return Pack::noPackPrice((int) $this->id);
    }

    /**
     * @param $idCustomer
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function checkAccess($idCustomer)
    {
        return static::checkAccessStatic((int) $this->id, (int) $idCustomer);
    }

    /**
     * @param $idProduct
     * @param $idCustomer
     *
     * @return bool|null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function checkAccessStatic($idProduct, $idCustomer)
    {
        if (!Group::isFeatureActive()) {
            return true;
        }

        $cacheId = 'Product::checkAccess_'.(int) $idProduct.'-'.(int) $idCustomer.(!$idCustomer ? '-'.(int) Group::getCurrent()->id : '');
        if (!Cache::isStored($cacheId)) {
            if (!$idCustomer) {
                $result = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    '
				SELECT ctg.`id_group`
				FROM `'._DB_PREFIX_.'category_product` cp
				INNER JOIN `'._DB_PREFIX_.'category_group` ctg ON (ctg.`id_category` = cp.`id_category`)
				WHERE cp.`id_product` = '.(int) $idProduct.' AND ctg.`id_group` = '.(int) Group::getCurrent()->id
                );
            } else {
                $result = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    '
				SELECT cg.`id_group`
				FROM `'._DB_PREFIX_.'category_product` cp
				INNER JOIN `'._DB_PREFIX_.'category_group` ctg ON (ctg.`id_category` = cp.`id_category`)
				INNER JOIN `'._DB_PREFIX_.'customer_group` cg ON (cg.`id_group` = ctg.`id_group`)
				WHERE cp.`id_product` = '.(int) $idProduct.' AND cg.`id_customer` = '.(int) $idCustomer
                );
            }

            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Add a stock movement for current product
     *
     * Since 1.5, this method only permit to add/remove available quantities of the current product in the current shop
     *
     * @see        StockManager if you want to manage real stock
     * @see        StockAvailable if you want to manage available quantities for sale on your shop(s)
     *
     * @deprecated since 1.5.0
     *
     * @param int $quantity
     * @param int $idReason   - useless
     * @param int $idProductAttribute
     * @param int $idOrder    - DEPRECATED
     * @param int $idEmployee - DEPRECATED
     *
     * @return bool
     * @throws Adapter_Exception
     */
    public function addStockMvt($quantity, $idReason, $idProductAttribute = null, $idOrder = null, $idEmployee = null)
    {
        if (!$this->id || !$idReason) {
            return false;
        }

        if ($idProductAttribute == null) {
            $idProductAttribute = 0;
        }

        $reason = new StockMvtReason((int) $idReason);
        if (!Validate::isLoadedObject($reason)) {
            return false;
        }

        $quantity = abs((int) $quantity) * $reason->sign;

        return StockAvailable::updateQuantity($this->id, $idProductAttribute, $quantity);
    }

    /**
     * @deprecated 1.0.0
     *
     * @param int $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getStockMvts($idLang)
    {
        Tools::displayAsDeprecated();

        return Db::getInstance()->executeS(
            '
			SELECT sm.id_stock_mvt, sm.date_add, sm.quantity, sm.id_order,
			CONCAT(pl.name, \' \', GROUP_CONCAT(IFNULL(al.name, \'\'), \'\')) product_name, CONCAT(e.lastname, \' \', e.firstname) employee, mrl.name reason
			FROM `'._DB_PREFIX_.'stock_mvt` sm
			LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
				sm.id_product = pl.id_product
				AND pl.id_lang = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').'
			)
			LEFT JOIN `'._DB_PREFIX_.'stock_mvt_reason_lang` mrl ON (
				sm.id_stock_mvt_reason = mrl.id_stock_mvt_reason
				AND mrl.id_lang = '.(int) $idLang.'
			)
			LEFT JOIN `'._DB_PREFIX_.'employee` e ON (
				e.id_employee = sm.id_employee
			)
			LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON (
				pac.id_product_attribute = sm.id_product_attribute
			)
			LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (
				al.id_attribute = pac.id_attribute
				AND al.id_lang = '.(int) $idLang.'
			)
			WHERE sm.id_product='.(int) $this->id.'
			GROUP BY sm.id_stock_mvt
		'
        );
    }

    /**
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getIdTaxRulesGroup()
    {
        return $this->id_tax_rules_group;
    }

    /**
     * Webservice getter : get product features association
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsProductFeatures()
    {
        $rows = $this->getFeatures();
        foreach ($rows as $keyrow => $row) {
            foreach ($row as $keyfeature => $feature) {
                if ($keyfeature == 'id_feature') {
                    $rows[$keyrow]['id'] = $feature;
                    unset($rows[$keyrow]['id_feature']);
                }
                unset($rows[$keyrow]['id_product']);
                unset($rows[$keyrow]['custom']);
            }
            asort($rows[$keyrow]);
        }

        return $rows;
    }

    /**
     * Select all features for the object
     *
     * @return array Array with feature product's data
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFeatures()
    {
        return static::getFeaturesStatic((int) $this->id);
    }

    /**
     * @param int $idProduct
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFeaturesStatic($idProduct)
    {
        if (!Feature::isFeatureActive()) {
            return [];
        }
        if (!array_key_exists($idProduct, static::$_cacheFeatures)) {
            static::$_cacheFeatures[$idProduct] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
				SELECT fp.id_feature, fp.id_product, fp.id_feature_value, custom
				FROM `'._DB_PREFIX_.'feature_product` fp
				LEFT JOIN `'._DB_PREFIX_.'feature_value` fv ON (fp.id_feature_value = fv.id_feature_value)
				WHERE `id_product` = '.(int) $idProduct
            );
        }

        return static::$_cacheFeatures[$idProduct];
    }

    /**
     * Webservice setter : set product features association
     *
     * @param array $productFeatures Product Feature ids
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function setWsProductFeatures($productFeatures)
    {
        Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'feature_product`
			WHERE `id_product` = '.(int) $this->id
        );
        foreach ($productFeatures as $productFeature) {
            $this->addFeaturesToDB($productFeature['id'], $productFeature['id_feature_value']);
        }

        return true;
    }

    /**
     * @param int $idFeature
     * @param int $idValue
     * @param int $cust
     *
     * @return int|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addFeaturesToDB($idFeature, $idValue, $cust = 0)
    {
        if ($cust) {
            $row = ['id_feature' => (int) $idFeature, 'custom' => 1];
            Db::getInstance()->insert('feature_value', $row);
            $idValue = Db::getInstance()->Insert_ID();
        }
        $row = ['id_feature' => (int) $idFeature, 'id_product' => (int) $this->id, 'id_feature_value' => (int) $idValue];
        Db::getInstance()->insert('feature_product', $row);
        SpecificPriceRule::applyAllRules([(int) $this->id]);
        if ($idValue) {
            return ($idValue);
        }
    }

    /**
     * Webservice getter : get virtual field default combination
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getWsDefaultCombination()
    {
        return static::getDefaultAttribute($this->id);
    }

    /**
     * Webservice setter : set virtual field default combination
     *
     * @param int $idCombination id default combination
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function setWsDefaultCombination($idCombination)
    {
        $this->deleteDefaultAttributes();

        return $this->setDefaultAttribute((int) $idCombination);
    }

    /**
     * Del all default attributes for product
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteDefaultAttributes()
    {
        return ObjectModel::updateMultishopTable(
            'Combination',
            [
                'default_on' => null,
            ],
            'a.`id_product` = '.(int) $this->id
        );
    }

    /**
     * @param int $idProductAttribute
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function setDefaultAttribute($idProductAttribute)
    {
        $result = ObjectModel::updateMultishopTable(
            'Combination',
            [
                'default_on' => 1,
            ],
            'a.`id_product` = '.(int) $this->id.' AND a.`id_product_attribute` = '.(int) $idProductAttribute
        );

        $result &= ObjectModel::updateMultishopTable(
            'product',
            [
                'cache_default_attribute' => (int) $idProductAttribute,
            ],
            'a.`id_product` = '.(int) $this->id
        );
        $this->cache_default_attribute = (int) $idProductAttribute;

        return $result;
    }

    /**
     * Webservice getter : get category ids of current product for association
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsCategories()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cp.`id_category` AS `id`')
                ->from('category_product', 'cp')
                ->leftJoin('category', 'c', 'c.`id_category` = cp.`id_category`')
                ->join(Shop::addSqlAssociation('category', 'c'))
                ->where('cp.`id_product` = '.(int) $this->id)
        );
    }

    /**
     * Webservice setter : set category ids of current product for association
     *
     * @param array $categoryIds category ids
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function setWsCategories($categoryIds)
    {
        $ids = [];
        foreach ($categoryIds as $value) {
            $ids[] = $value['id'];
        }
        if ($this->deleteCategories()) {
            if ($ids) {
                $ids = array_map('intval', $ids);
                $inserts = [];
                foreach ($ids as $position => $id) {
                    $inserts[] = [
                        'id_category' => (int) $id,
                        'id_product'  => (int) $this->id,
                        'position'    => (int) $position,
                    ];
                }
                $result = Db::getInstance()->insert(
                    'category_product',
                    $inserts
                );
                Hook::exec('updateProduct', ['id_product' => (int) $this->id]);

                return $result;
            }
        }
        Hook::exec('updateProduct', ['id_product' => (int) $this->id]);

        return true;
    }

    /**
     * Webservice getter : get product accessories ids of current product for association
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsAccessories()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('p.`id_product` AS `id`')
                ->from('accessory', 'a')
                ->leftJoin('product', 'p', 'p.`id_product` = a.`id_product_2`')
                ->join(Shop::addSqlAssociation('product', 'p'))
                ->where('a.`id_product_1` = '.(int) $this->id)
        );
    }

    /**
     * Webservice setter : set product accessories ids of current product for association
     *
     * @param array $accessories product ids
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @return bool
     * @throws PrestaShopException
     */
    public function setWsAccessories($accessories)
    {
        $this->deleteAccessories();
        foreach ($accessories as $accessory) {
            Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'accessory` (`id_product_1`, `id_product_2`) VALUES ('.(int) $this->id.', '.(int) $accessory['id'].')');
        }

        return true;
    }

    /**
     * Webservice getter : get combination ids of current product for association
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsCombinations()
    {
        $result = Db::getInstance()->executeS(
            'SELECT pa.`id_product_attribute` AS id
			FROM `'._DB_PREFIX_.'product_attribute` pa
			'.Shop::addSqlAssociation('product_attribute', 'pa').'
			WHERE pa.`id_product` = '.(int) $this->id
        );

        return $result;
    }

    /**
     * Webservice setter : set combination ids of current product for association
     *
     * @param array $combinations combination ids
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @return bool
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function setWsCombinations($combinations)
    {
        // No hook exec
        $idsNew = [];
        foreach ($combinations as $combination) {
            $idsNew[] = (int) $combination['id'];
        }

        $idsOrig = [];
        $original = Db::getInstance()->executeS(
            'SELECT pa.`id_product_attribute` AS id
			FROM `'._DB_PREFIX_.'product_attribute` pa
			'.Shop::addSqlAssociation('product_attribute', 'pa').'
			WHERE pa.`id_product` = '.(int) $this->id
        );

        if (is_array($original)) {
            foreach ($original as $id) {
                $idsOrig[] = $id['id'];
            }
        }

        $allIds = [];
        $all = Db::getInstance()->executeS('SELECT pa.`id_product_attribute` AS id FROM `'._DB_PREFIX_.'product_attribute` pa '.Shop::addSqlAssociation('product_attribute', 'pa'));
        if (is_array($all)) {
            foreach ($all as $id) {
                $allIds[] = $id['id'];
            }
        }

        $toAdd = [];
        foreach ($idsNew as $id) {
            if (!in_array($id, $idsOrig)) {
                $toAdd[] = $id;
            }
        }

        $toDelete = [];
        foreach ($idsOrig as $id) {
            if (!in_array($id, $idsNew)) {
                $toDelete[] = $id;
            }
        }

        // Delete rows
        if (count($toDelete) > 0) {
            foreach ($toDelete as $id) {
                $combination = new Combination($id);
                $combination->delete();
            }
        }

        foreach ($toAdd as $id) {
            // Update id_product if exists else create
            if (in_array($id, $allIds)) {
                Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'product_attribute` SET id_product = '.(int) $this->id.' WHERE id_product_attribute='.$id);
            } else {
                Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'product_attribute` (`id_product`) VALUES ('.$this->id.')');
            }
        }

        return true;
    }

    /**
     * Webservice getter : get product option ids of current product for association
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsProductOptionValues()
    {
        $result = Db::getInstance()->executeS(
            'SELECT DISTINCT pac.id_attribute AS id
			FROM `'._DB_PREFIX_.'product_attribute` pa
			'.Shop::addSqlAssociation('product_attribute', 'pa').'
			LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON (pac.id_product_attribute = pa.id_product_attribute)
			WHERE pa.id_product = '.(int) $this->id
        );

        return $result;
    }

    /**
     * Webservice setter : set virtual field position in category
     *
     * @param int $position
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setWsPositionInCategory($position)
    {
        if ($position < 0) {
            WebserviceRequest::getInstance()->setError(500, Tools::displayError('You cannot set a negative position, the minimum for a position is 0.'), 134);
        }
        $result = Db::getInstance()->executeS(
            '
			SELECT `id_product`
			FROM `'._DB_PREFIX_.'category_product`
			WHERE `id_category` = '.(int) $this->id_category_default.'
			ORDER BY `position`
		'
        );
        if (($position > 0) && ($position + 1 > count($result))) {
            WebserviceRequest::getInstance()->setError(500, Tools::displayError('You cannot set a position greater than the total number of products in the category, minus 1 (position numbering starts at 0).'), 135);
        }

        foreach ($result as &$value) {
            $value = $value['id_product'];
        }
        $currentPosition = $this->getWsPositionInCategory();

        if ($currentPosition && isset($result[$currentPosition])) {
            $save = $result[$currentPosition];
            unset($result[$currentPosition]);
            array_splice($result, (int) $position, 0, $save);
        }

        foreach ($result as $position => $idProduct) {
            Db::getInstance()->update(
                'category_product',
                [
                    'position' => $position,
                ],
                '`id_category` = '.(int) $this->id_category_default.' AND `id_product` = '.(int) $idProduct
            );
        }

        return true;
    }

    /**
     * Webservice getter : get virtual field position in category
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsPositionInCategory()
    {
        $result = Db::getInstance()->executeS(
            'SELECT position
			FROM `'._DB_PREFIX_.'category_product`
			WHERE id_category = '.(int) $this->id_category_default.'
			AND id_product = '.(int) $this->id
        );
        if (count($result) > 0) {
            return $result[0]['position'];
        }

        return '';
    }

    /**
     * Webservice getter : get virtual field id_default_image in category
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getCoverWs()
    {
        $result = $this->getCover($this->id);

        return $result['id_image'];
    }

    /**
     * Get product cover image
     *
     * @param int     $idProduct
     * @param Context $context
     *
     * @return array Product cover image
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCover($idProduct, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }
        $cacheId = 'Product::getCover_'.(int) $idProduct.'-'.(int) $context->shop->id;
        if (!Cache::isStored($cacheId)) {
            $sql = 'SELECT image_shop.`id_image`
					FROM `'._DB_PREFIX_.'image` i
					'.Shop::addSqlAssociation('image', 'i').'
					WHERE i.`id_product` = '.(int) $idProduct.'
					AND image_shop.`cover` = 1';
            $result = Db::getInstance()->getRow($sql);
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Webservice setter : set virtual field id_default_image in category
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function setCoverWs($idImage)
    {
        Db::getInstance()->execute(
            'UPDATE `'._DB_PREFIX_.'image_shop` image_shop, `'._DB_PREFIX_.'image` i
			SET image_shop.`cover` = NULL
			WHERE i.`id_product` = '.(int) $this->id.' AND i.id_image = image_shop.id_image
			AND image_shop.id_shop='.(int) Context::getContext()->shop->id
        );
        Db::getInstance()->execute(
            'UPDATE `'._DB_PREFIX_.'image_shop`
			SET `cover` = 1 WHERE `id_image` = '.(int) $idImage
        );

        return true;
    }

    /**
     * Webservice getter : get image ids of current product for association
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsImages()
    {
        return Db::getInstance()->executeS(
            '
		SELECT i.`id_image` AS id
		FROM `'._DB_PREFIX_.'image` i
		'.Shop::addSqlAssociation('image', 'i').'
		WHERE i.`id_product` = '.(int) $this->id.'
		ORDER BY i.`position`'
        );
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsStockAvailables()
    {
        return Db::getInstance()->executeS(
            'SELECT `id_stock_available` id, `id_product_attribute`
														FROM `'._DB_PREFIX_.'stock_available`
														WHERE `id_product`='.($this->id).StockAvailable::addSqlShopRestriction()
        );
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsTags()
    {
        return Db::getInstance()->executeS(
            '
		SELECT `id_tag` AS id
		FROM `'._DB_PREFIX_.'product_tag`
		WHERE `id_product` = '.(int) $this->id
        );
    }

    /**
     * Webservice setter : set tag ids of current product for association
     *
     * @param array $tagIds tag ids
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @return bool
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function setWsTags($tagIds)
    {
        $ids = [];
        foreach ($tagIds as $value) {
            $ids[] = $value['id'];
        }
        if ($this->deleteWsTags()) {
            if ($ids) {
                $sqlValues = [];
                $ids = array_map('intval', $ids);
                foreach ($ids as $position => $id) {
                    $idLang = Db::getInstance()->getValue('SELECT `id_lang` FROM `'._DB_PREFIX_.'tag` WHERE `id_tag`='.(int) $id);
                    $sqlValues[] = '('.(int) $this->id.', '.(int) $id.', '.(int) $idLang.')';
                }
                $result = Db::getInstance()->execute(
                    '
					INSERT INTO `'._DB_PREFIX_.'product_tag` (`id_product`, `id_tag`, `id_lang`)
					VALUES '.implode(',', $sqlValues)
                );

                return $result;
            }
        }

        return true;
    }

    /**
     * Delete products tags entries without delete tags for webservice usage
     *
     * @return array Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public function deleteWsTags()
    {
        return Db::getInstance()->delete('product_tag', 'id_product = '.(int) $this->id);
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getWsManufacturerName()
    {
        return Manufacturer::getNameById((int) $this->id_manufacturer);
    }

    /**
     * Checks if reference exists
     *
     * @param string $reference
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function existsRefInDatabase($reference)
    {
        $row = Db::getInstance()->getRow(
            '
		SELECT `reference`
		FROM `'._DB_PREFIX_.'product` p
		WHERE p.reference = "'.pSQL($reference).'"'
        );

        return isset($row['reference']);
    }

    /**
     * Get the combination url anchor of the product
     *
     * @param int  $idProductAttribute
     *
     * @param bool $withId
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getAnchor($idProductAttribute, $withId = false)
    {
        $attributes = static::getAttributesParams($this->id, $idProductAttribute);
        $anchor = '#';
        $sep = Configuration::get('PS_ATTRIBUTE_ANCHOR_SEPARATOR');
        foreach ($attributes as &$a) {
            foreach ($a as &$b) {
                $b = str_replace($sep, '_', Tools::link_rewrite($b));
            }
            $anchor .= '/'.($withId && isset($a['id_attribute']) && $a['id_attribute'] ? (int) $a['id_attribute'].$sep : '').$a['group'].$sep.$a['name'];
        }

        return $anchor;
    }

    /**
     * Get label by lang and value by lang too
     *
     * @todo    Remove existing module condition
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAttributesParams($idProduct, $idProductAttribute)
    {
        $idLang = (int) Context::getContext()->language->id;
        $idShop = (int) Context::getContext()->shop->id;
        $cacheId = 'Product::getAttributesParams_'.(int) $idProduct.'-'.(int) $idProductAttribute.'-'.(int) $idLang.'-'.(int) $idShop;

        // if blocklayered module is installed we check if user has set custom attribute name
        if (Module::isInstalled('blocklayered') && Module::isEnabled('blocklayered')) {
            $nbCustomValues = Db::getInstance()->executeS(
                '
			SELECT DISTINCT la.`id_attribute`, la.`url_name` AS `name`
			FROM `'._DB_PREFIX_.'attribute` a
			LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
				ON (a.`id_attribute` = pac.`id_attribute`)
			LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
			'.Shop::addSqlAssociation('product_attribute', 'pa').'
			LEFT JOIN `'._DB_PREFIX_.'layered_indexable_attribute_lang_value` la
				ON (la.`id_attribute` = a.`id_attribute` AND la.`id_lang` = '.(int) $idLang.')
			WHERE la.`url_name` IS NOT NULL AND la.`url_name` != \'\'
			AND pa.`id_product` = '.(int) $idProduct.'
			AND pac.`id_product_attribute` = '.(int) $idProductAttribute
            );

            if (!empty($nbCustomValues)) {
                $tabIdAttribute = [];
                foreach ($nbCustomValues as $attribute) {
                    $tabIdAttribute[] = $attribute['id_attribute'];

                    $group = Db::getInstance()->executeS(
                        '
					SELECT a.`id_attribute`, g.`id_attribute_group`, g.`url_name` AS `group`
					FROM `'._DB_PREFIX_.'layered_indexable_attribute_group_lang_value` g
					LEFT JOIN `'._DB_PREFIX_.'attribute` a
						ON (a.`id_attribute_group` = g.`id_attribute_group`)
					WHERE a.`id_attribute` = '.(int) $attribute['id_attribute'].'
					AND g.`id_lang` = '.(int) $idLang.'
					AND g.`url_name` IS NOT NULL AND g.`url_name` != \'\''
                    );
                    if (empty($group)) {
                        $group = Db::getInstance()->executeS(
                            '
						SELECT g.`id_attribute_group`, g.`name` AS `group`
						FROM `'._DB_PREFIX_.'attribute_group_lang` g
						LEFT JOIN `'._DB_PREFIX_.'attribute` a
							ON (a.`id_attribute_group` = g.`id_attribute_group`)
						WHERE a.`id_attribute` = '.(int) $attribute['id_attribute'].'
						AND g.`id_lang` = '.(int) $idLang.'
						AND g.`name` IS NOT NULL'
                        );
                    }
                    $result[] = array_merge($attribute, $group[0]);
                }
                $valuesNotCustom = Db::getInstance()->executeS(
                    '
				SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name`, agl.`name` AS `group`
				FROM `'._DB_PREFIX_.'attribute` a
				LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
					ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) $idLang.')
				LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
					ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) $idLang.')
				LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
					ON (a.`id_attribute` = pac.`id_attribute`)
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
					ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				WHERE pa.`id_product` = '.(int) $idProduct.'
				AND pac.id_product_attribute = '.(int) $idProductAttribute.'
				AND a.`id_attribute` NOT IN('.implode(', ', $tabIdAttribute).')'
                );

                return array_merge($valuesNotCustom, $result);
            }
        }

        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance()->executeS(
                '
			SELECT a.`id_attribute`, a.`id_attribute_group`, al.`name`, agl.`name` AS `group`
			FROM `'._DB_PREFIX_.'attribute` a
			LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
				ON (al.`id_attribute` = a.`id_attribute` AND al.`id_lang` = '.(int) $idLang.')
			LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
				ON (pac.`id_attribute` = a.`id_attribute`)
			LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
				ON (pa.`id_product_attribute` = pac.`id_product_attribute`)
			'.Shop::addSqlAssociation('product_attribute', 'pa').'
			LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
				ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) $idLang.')
			WHERE pa.`id_product` = '.(int) $idProduct.'
				AND pac.`id_product_attribute` = '.(int) $idProductAttribute.'
				AND agl.`id_lang` = '.(int) $idLang
            );
            Cache::store($cacheId, $result);
        } else {
            $result = Cache::retrieve($cacheId);
        }

        return $result;
    }

    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function addWs($autodate = true, $nullValues = false)
    {
        $success = $this->add($autodate, $nullValues);
        if ($success && Configuration::get('PS_SEARCH_INDEXATION')) {
            Search::indexation(false, $this->id);
        }

        return $success;
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
        if (!parent::add($autoDate, $nullValues)) {
            return false;
        }

        $idShopList = Shop::getContextListShopID();
        if ($this->getType() == static::PTYPE_VIRTUAL) {
            foreach ($idShopList as $value) {
                StockAvailable::setProductOutOfStock((int) $this->id, 1, $value);
            }

            if ($this->active && !Configuration::get('PS_VIRTUAL_PROD_FEATURE_ACTIVE')) {
                Configuration::updateGlobalValue('PS_VIRTUAL_PROD_FEATURE_ACTIVE', '1');
            }
        } else {
            foreach ($idShopList as $value) {
                StockAvailable::setProductOutOfStock((int) $this->id, 2, $value);
            }
        }

        $this->setGroupReduction();
        Hook::exec('actionProductSave', ['id_product' => (int) $this->id, 'product' => $this]);

        return true;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function updateWs($nullValues = false)
    {
        $success = parent::update($nullValues);
        if ($success && Configuration::get('PS_SEARCH_INDEXATION')) {
            Search::indexation(false, $this->id);
        }
        Hook::exec('updateProduct', ['id_product' => (int) $this->id]);

        return $success;
    }

    /**
     * Get list of parent categories
     *
     * @param int $idLang
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getParentCategories($idLang = null)
    {
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }

        $interval = Category::getInterval($this->id_category_default);
        $sql = new DbQuery();
        $sql->from('category', 'c');
        $sql->leftJoin('category_lang', 'cl', 'c.id_category = cl.id_category AND id_lang = '.(int) $idLang.Shop::addSqlRestrictionOnLang('cl'));
        $sql->where('c.nleft <= '.(int) $interval['nleft'].' AND c.nright >= '.(int) $interval['nright']);
        $sql->orderBy('c.nleft');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * @param int $value
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function setAdvancedStockManagement($value)
    {
        $this->advanced_stock_management = (int) $value;
        if (Context::getContext()->shop->getContext() == Shop::CONTEXT_GROUP && Context::getContext()->shop->getContextShopGroup()->share_stock == 1) {
            Db::getInstance()->execute(
                '
				UPDATE `'._DB_PREFIX_.'product_shop`
				SET `advanced_stock_management`='.(int) $value.'
				WHERE id_product='.(int) $this->id.Shop::addSqlRestriction()
            );
        } else {
            $this->setFieldsToUpdate(['advanced_stock_management' => true]);
            $this->save();
        }
    }

    /**
     * get the default category according to the shop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getDefaultCategory()
    {
        $defaultCategory = Db::getInstance()->getValue(
            '
			SELECT product_shop.`id_category_default`
			FROM `'._DB_PREFIX_.'product` p
			'.Shop::addSqlAssociation('product', 'p').'
			WHERE p.`id_product` = '.(int) $this->id
        );

        if (!$defaultCategory) {
            return ['id_category_default' => Context::getContext()->shop->id_category];
        } else {
            return $defaultCategory;
        }
    }

    /**
     * @deprecated 1.0.0
     * @see        Product::getAttributeCombinations()
     *
     * @param int $idLang
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getAttributeCombinaisons($idLang)
    {
        Tools::displayAsDeprecated('Use Product::getAttributeCombinations($id_lang)');

        return $this->getAttributeCombinations($idLang);
    }

    /**
     * Get all available product attributes combinations
     *
     * @param int $idLang Language id
     *
     * @return array Product attributes combinations
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getAttributeCombinations($idLang)
    {
        if (!Combination::isFeatureActive()) {
            return [];
        }

        $sql = 'SELECT pa.*, product_attribute_shop.*, ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, al.`name` AS attribute_name,
					a.`id_attribute`
				FROM `'._DB_PREFIX_.'product_attribute` pa
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
				LEFT JOIN `'._DB_PREFIX_.'attribute` a ON a.`id_attribute` = pac.`id_attribute`
				LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
				LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) $idLang.')
				LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) $idLang.')
				WHERE pa.`id_product` = '.(int) $this->id.'
				GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
				ORDER BY pa.`id_product_attribute`';

        $res = Db::getInstance()->executeS($sql);

        //Get quantity of each variations
        foreach ($res as $key => $row) {
            $cacheKey = $row['id_product'].'_'.$row['id_product_attribute'].'_quantity';

            if (!Cache::isStored($cacheKey)) {
                Cache::store(
                    $cacheKey,
                    StockAvailable::getQuantityAvailableByProduct($row['id_product'], $row['id_product_attribute'])
                );
            }

            $res[$key]['quantity'] = Cache::retrieve($cacheKey);
        }

        return $res;
    }

    /**
     * @deprecated 1.0.0
     * @see        Product::deleteAttributeCombination()
     *
     * @param int $idProductAttribute
     *
     * @return array
     */
    public function deleteAttributeCombinaison($idProductAttribute)
    {
        Tools::displayAsDeprecated('Use Product::deleteAttributeCombination($id_product_attribute)');

        return $this->deleteAttributeCombination($idProductAttribute);
    }

    /*
        Create the link rewrite if not exists or invalid on product creation
    */

    /**
     * Delete a product attributes combination
     *
     * @param int $idProductAttribute Product attribute id
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function deleteAttributeCombination($idProductAttribute)
    {
        if (!$this->id || !$idProductAttribute || !is_numeric($idProductAttribute)) {
            return false;
        }

        Hook::exec(
            'deleteProductAttribute',
            [
                'id_product_attribute' => $idProductAttribute,
                'id_product'           => $this->id,
                'deleteAllAttributes'  => false,
            ]
        );

        $combination = new Combination($idProductAttribute);
        $res = $combination->delete();
        SpecificPriceRule::applyAllRules([(int) $this->id]);

        return $res;
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getWsType()
    {
        $typeInformation = [
            static::PTYPE_SIMPLE  => 'simple',
            static::PTYPE_PACK    => 'pack',
            static::PTYPE_VIRTUAL => 'virtual',
        ];

        return $typeInformation[$this->getType()];
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function modifierWsLinkRewrite()
    {
        foreach ($this->name as $idLang => $name) {
            if (empty($this->link_rewrite[$idLang])) {
                $this->link_rewrite[$idLang] = Tools::link_rewrite($name);
            } elseif (!Validate::isLinkRewrite($this->link_rewrite[$idLang])) {
                $this->link_rewrite[$idLang] = Tools::link_rewrite($this->link_rewrite[$idLang]);
            }
        }

        return true;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsProductBundle()
    {
        return Db::getInstance()->executeS('SELECT id_product_item AS id, quantity FROM '._DB_PREFIX_.'pack WHERE id_product_pack = '.(int) $this->id);
    }

    /**
     * @param $typeStr
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function setWsType($typeStr)
    {
        $reverseTypeInformation = [
            'simple'  => static::PTYPE_SIMPLE,
            'pack'    => static::PTYPE_PACK,
            'virtual' => static::PTYPE_VIRTUAL,
        ];

        if (!isset($reverseTypeInformation[$typeStr])) {
            return false;
        }

        $type = $reverseTypeInformation[$typeStr];

        if (Pack::isPack((int) $this->id) && $type != static::PTYPE_PACK) {
            Pack::deleteItems($this->id);
        }

        $this->cache_is_pack = ($type == static::PTYPE_PACK);
        $this->is_virtual = ($type == static::PTYPE_VIRTUAL);

        return true;
    }

    /**
     * @param array $items
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setWsProductBundle($items)
    {
        if ($this->is_virtual) {
            return false;
        }

        Pack::deleteItems($this->id);

        foreach ($items as $item) {
            if ((int) $item['id'] > 0) {
                Pack::addItem($this->id, (int) $item['id'], (int) $item['quantity']);
            }
        }

        return true;
    }

    /**
     * @param int $idAttribute
     * @param int $idShop
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function isColorUnavailable($idAttribute, $idShop)
    {
        return Db::getInstance()->getValue(
            '
			SELECT sa.id_product_attribute
			FROM '._DB_PREFIX_.'stock_available sa
			WHERE id_product='.(int) $this->id.' AND quantity <= 0
			'.StockAvailable::addSqlShopRestriction(null, $idShop, 'sa').'
			AND EXISTS (
				SELECT 1
				FROM '._DB_PREFIX_.'product_attribute pa
				JOIN '._DB_PREFIX_.'product_attribute_shop product_attribute_shop
					ON (product_attribute_shop.id_product_attribute = pa.id_product_attribute AND product_attribute_shop.id_shop='.(int) $idShop.')
				JOIN '._DB_PREFIX_.'product_attribute_combination pac
					ON (pac.id_product_attribute AND product_attribute_shop.id_product_attribute)
				WHERE sa.id_product_attribute = pa.id_product_attribute AND pa.id_product='.(int) $this->id.' AND pac.id_attribute='.(int) $idAttribute.'
			)'
        );
    }
}
