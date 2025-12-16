<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is packd with this package in the file LICENSE.txt.
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
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class PackCore
 */
class PackCore
{
    const STOCK_TYPE_DECREMENT_PACK = 0;
    const STOCK_TYPE_DECREMENT_PRODUCTS = 1;
    const STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS = 2;
    const STOCK_TYPE_DECREMENT_GLOBAL_SETTINGS = 3;

    const STOCK_TYPE_ITEMS = 1;

    const PRODUCT_LEVEL_PACK = 0;
    const VIRTUAL_PRODUCT_ATTRIBUTE = 4294967295;
    const ANY_COMBINATION = -1;

    /**
     * @var int
     */
    protected int $productId;

    /**
     * @var int
     */
    protected int $combinationId;

    /**
     * @var PackItem[]
     */
    protected array $items = [];

    /**
     * @var Product|null
     */
    protected ?Product $product = null;


    /**
     * @param int $productId
     * @param int $combinationId
     */
    public function __construct(int $productId, int $combinationId)
    {
        $this->productId = $productId;
        $this->combinationId = $combinationId;
    }

    /**
     * @param int $productId
     * @param int $combinationId
     * @param int $quantity
     *
     * @return $this
     */
    public function addOrUpdateItem(int $productId, int $combinationId, int $quantity)
    {
        foreach ($this->items as $item) {
            if ($item->getProductId() === $productId && $item->getCombinationId() === $combinationId) {
                $item->setQuantity($item->getQuantity() + $quantity);
                if ($item->getQuantity() > $quantity) {
                    return $this;
                } else {
                    return $this->removeItem($productId, $combinationId);
                }
            }
        }
        if ($quantity > 0) {
            $this->items[] = new PackItem($productId, $combinationId, $quantity);
        }
        return $this;
    }


    /**
     * @param int $productId
     * @param int $combinationId
     * @return PackItem|null
     */
    public function findItem(int $productId, int $combinationId): ?PackItem
    {
        foreach ($this->items as $item) {
            if ($item->getProductId() === $productId && $item->getCombinationId() === $combinationId) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param int $productId
     * @param int $combinationId
     *
     * @return $this
     */
    public function removeItem(int $productId, int $combinationId)
    {
        $this->items = array_filter($this->items, function (PackItem $item) use ($productId, $combinationId) {
            return !($item->getProductId() === $productId && $item->getCombinationId() === $combinationId);
        });
        return $this;
    }

    /**
     * @return bool
     */
    public function hasItems(): bool
    {
        return (bool)$this->items;
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function save(): bool
    {
        $conn = Db::getInstance();
        $res = $this->deleteItemsFromDb();
        if (! $this->items) {
            return $res;
        }
        foreach ($this->items as $item) {
            $itemCombinationId = $item->getCombinationId();

            if ($item->getCombinationId() === static::VIRTUAL_PRODUCT_ATTRIBUTE) {
                $attributeGroup = AttributeGroup::createAttributeGroupForCombinationProduct($item->getProductId());
                if (! $attributeGroup) {
                    $itemCombinationId = 0;
                }
            }

            $res = $conn->insert('pack', [
                    'id_product_pack' => $this->productId,
                    'id_product_attribute_pack' => $this->combinationId,
                    'id_product_item' => $item->getProductId(),
                    'id_product_attribute_item' => $itemCombinationId,
                    'quantity' => $item->getQuantity(),
                ]) && $res;
        }
        $cacheKey = 'Pack_' . $this->productId;
        Cache::clean($cacheKey);
        Db::getInstance()->update('product', ['cache_is_pack' => 1], 'id_product = ' . $this->productId);
        return $res;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete(): bool
    {
        if ($this->deleteItemsFromDb()) {
            $cacheKey = 'Pack_' . $this->productId;
            Cache::clean($cacheKey);
            $packs = static::getPacks($this->productId);
            Db::getInstance()->update('product', ['cache_is_pack' => $packs ? 1 : 0], 'id_product = ' . $this->productId);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function deleteItemsFromDb(): bool
    {
        $conn = Db::getInstance();
        return $conn->delete('pack', 'id_product_pack = ' . $this->productId . ' AND id_product_attribute_pack = ' . $this->combinationId);
    }

    /**
     * @return PackItem[]
     */
    public function getPackItems(): array
    {
        return $this->items;
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * @return int
     */
    public function getCombinationId(): int
    {
        return $this->combinationId;
    }


    /**
     * Returns sum of items wholesale prices
     *
     * @return float
     *
     * @throws PrestaShopException
     */
    public function getWholesalePrice(): float
    {
        $sum = 0.0;
        foreach ($this->getPackItems() as $item) {
            $sum += $item->getUnitWholesalePrice() * $item->getQuantity();
        }
        return $sum;
    }

    /**
     * Returns sum of items prices
     *
     * @param bool $withTaxes
     *
     * @return float
     *
     * @throws PrestaShopException
     */
    public function getPrice(bool $withTaxes): float
    {
        $sum = 0.0;
        foreach ($this->getPackItems() as $item) {
            $sum += $item->getUnitPrice($withTaxes) * $item->getQuantity();
        }
        return $sum;
    }

    /**
     * @return float
     * @throws PrestaShopException
     */
    public function getWeight(): float
    {
        $weight = 0.0;
        foreach ($this->getPackItems() as $item) {
            $weight += $item->getUnitWeight() * $item->getQuantity();
        }
        return $weight;
    }


    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function canBeOrdered(): bool
    {
        if (! $this->hasItems()) {
            return false;
        }
        foreach ($this->getPackItems() as $item) {
            if (! $item->canBeOrdered()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function allItemsUsesAdvancedStockManagement()
    {
        foreach ($this->getPackItems() as $item) {
            // if one product uses the advanced stock management
            if (! $item->usesAdvancedStockManagement()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param int $productId
     *
     * @return Pack|null
     * @throws PrestaShopException
     */
    public static function getProductLevelPack(int $productId): ?Pack
    {
        return static::getPack($productId, static::PRODUCT_LEVEL_PACK);
    }

    /**
     * @param int $productId
     * @param int $combinationId
     *
     * @return Pack|null
     * @throws PrestaShopException
     */
    public static function getPack(int $productId, int $combinationId): ?Pack
    {
        $packs = static::getPacks($productId);
        if ($packs) {
            if (isset($packs[$combinationId])) {
                return $packs[$combinationId];
            }
            if (isset($packs[static::PRODUCT_LEVEL_PACK])) {
                return $packs[static::PRODUCT_LEVEL_PACK];
            }
        }
        return null;
    }

    /**
     * @param int $idProduct
     * @param int $combinationId
     * @return Pack
     * @throws PrestaShopException
     */
    public static function getOrCreate(int $idProduct, int $combinationId): Pack
    {
        $pack = static::getPack($idProduct, $combinationId);
        if (! $pack) {
            $pack = new static($idProduct, $combinationId);
        }
        return $pack;
    }

    /**
     * @param int $productId
     *
     * @return Pack[]
     *
     * @throws PrestaShopException
     */
    public static function getPacks(int $productId): array
    {
        $cacheKey = 'Pack_' . $productId;
        if (! Cache::isStored($cacheKey)) {
            $packs = static::loadPacks($productId);
            if ($packs) {
                Cache::store($cacheKey, $packs);
            } else {
                Cache::store($cacheKey, []);
            }
        }
        return Cache::retrieve($cacheKey);
    }

    /**
     * @param int $productId
     * @param int $combinationId
     *
     * @return Pack[]
     * @throws PrestaShopException
     */
    public static function getPacksContaining(int $productId, int $combinationId): array
    {
        $sql = (new DbQuery())
            ->select('DISTINCT pack.id_product_pack')
            ->select('pack.id_product_attribute_pack')
            ->select('pack.id_product_item')
            ->select('pack.id_product_attribute_item')
            ->select('pack.quantity')
            ->from('pack', 'pack')
            ->innerJoin('pack', 'f', '(pack.id_product_pack = f.id_product_pack AND pack.id_product_attribute_pack = f.id_product_attribute_pack)')
            ->where('f.id_product_item = ' . $productId)
            ->orderBy('pack.id_product_pack')
            ->orderBy('pack.id_product_attribute_pack')
            ->orderBy('pack.id_product_item')
            ->orderBy('pack.id_product_attribute_item');
        if ($combinationId !== static::ANY_COMBINATION && $combinationId !== static::VIRTUAL_PRODUCT_ATTRIBUTE) {
            $sql->where('f.id_product_attribute_item = ' . $combinationId);
        }

        $packs = [];
        $result = Db::readOnly()->getArray($sql);

        $pack = null;
        foreach ($result as $row) {
            $productId = (int)$row['id_product_pack'];
            $combinationId = (int)$row['id_product_attribute_pack'];
            if (!$pack || $pack->getProductId() !== $productId || $pack->getCombinationId() !== $combinationId) {
                $pack = new static($productId, $combinationId);
                $packs[] = $pack;
            }
            $pack->addOrUpdateItem(
                (int)$row['id_product_item'],
                (int)$row['id_product_attribute_item'],
                (int)$row['quantity']
            );
        }
        return $packs;
    }

    /**
     * @param int $langId
     * @param bool $full
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getItemsInformations(int $langId, bool $full)
    {
        $context = Context::getContext();
        $productId = $this->productId;
        $combinationId = $this->combinationId;
        $shopId = (int)$context->shop->id;

        $sql = 'SELECT p.*, product_shop.*, pl.*, image_shop.`id_image` id_image, il.`legend`, cl.`name` AS category_default, a.quantity AS pack_quantity, product_shop.`id_category_default`, a.id_product_pack, a.id_product_item, a.id_product_attribute_item
				FROM `' . _DB_PREFIX_ . 'pack` a
				LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.id_product = a.id_product_item
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
					ON p.id_product = pl.id_product
					AND pl.`id_lang` = ' . (int)$langId . Shop::addSqlRestrictionOnLang('pl') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . $shopId . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . $langId . ')
				' . Shop::addSqlAssociation('product', 'p') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
					ON product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = ' . $langId . Shop::addSqlRestrictionOnLang('cl') . '
				WHERE product_shop.`id_shop` = ' . $shopId . '
				AND a.`id_product_pack` = ' . $productId . '
				AND a.`id_product_attribute_pack` = ' . $combinationId . '
				AND product_shop.active
				AND product_shop.visibility IN ("both", "catalog")
				GROUP BY a.`id_product_item`, a.`id_product_attribute_item`';

        $connection = Db::readOnly();
        $result = $connection->getArray($sql);

        foreach ($result as &$line) {
            if (Combination::isFeatureActive() && isset($line['id_product_attribute_item']) && $line['id_product_attribute_item']) {
                $combinationId = (int)$line['id_product_attribute_item'];
                if ($combinationId === Pack::VIRTUAL_PRODUCT_ATTRIBUTE) {
                    $combinationId = Product::getProductDefaultCombinationId($line['id_product_item']);
                    $line['id_product_attribute_item'] = $combinationId;
                }
                $line['cache_default_attribute'] = $line['id_product_attribute'] = $combinationId;

                $sql = 'SELECT agl.`name` AS group_name, al.`name` AS attribute_name,  pai.`id_image` AS id_product_attribute_image
				FROM `' . _DB_PREFIX_ . 'product_attribute` pa
				' . Shop::addSqlAssociation('product_attribute', 'pa') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON pac.`id_product_attribute` = ' . $combinationId . '
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . $langId . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . $langId . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_image` pai ON (' . $combinationId . ' = pai.`id_product_attribute`)
				WHERE pa.`id_product` = ' . (int)$line['id_product'] . ' AND pa.`id_product_attribute` = ' . $combinationId . '
				GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
				ORDER BY pa.`id_product_attribute`';

                $attrName = $connection->getArray($sql);

                if (isset($attrName[0]['id_product_attribute_image']) && $attrName[0]['id_product_attribute_image']) {
                    $line['id_image'] = $attrName[0]['id_product_attribute_image'];
                }
                $line['name'] .= "\n";
                foreach ($attrName as $value) {
                    $line['name'] .= ' ' . $value['group_name'] . '-' . $value['attribute_name'];
                }
            }
            $line = Product::getTaxesInformations($line);
        }

        if (! $full) {
            return $result;
        }

        $arrayResult = [];
        foreach ($result as $prod) {
            $pack = static::getPack((int)$prod['id_product'], (int)$prod['id_product_attribute_item']);
            if (! $pack) {
                $prod['id_product_attribute'] = (int)$prod['id_product_attribute_item'];
                $arrayResult[] = Product::getProductProperties($langId, $prod);
            }
        }

        return $arrayResult;
    }

    /**
     * @param int $productId
     *
     * @return Pack[]
     * @throws PrestaShopException
     */
    protected static function loadPacks(int $productId): array
    {
        $sql = (new DbQuery())
            ->select('id_product_attribute_pack')
            ->select('id_product_item')
            ->select('id_product_attribute_item')
            ->select('quantity')
            ->from('pack')
            ->where('id_product_pack = ' . (int)$productId)
            ->orderBy('id_product_attribute_pack')
            ->orderBy('id_product_item')
            ->orderBy('id_product_attribute_item');
        $packs = [];
        $result = Db::readOnly()->getArray($sql);
        $pack = null;
        foreach ($result as $row) {
            $combinationId = (int)$row['id_product_attribute_pack'];
            if (! $pack || $pack->getCombinationId() !== $combinationId) {
                $pack = new static($productId, $combinationId);
                $packs[$combinationId] = $pack;
            }
            $pack->addOrUpdateItem(
                (int)$row['id_product_item'],
                (int)$row['id_product_attribute_item'],
                (int)$row['quantity']
            );
        }
        return $packs;
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function isDynamicPack(): bool
    {
        return (bool)$this->getProduct()->pack_dynamic;
    }

    /**
     * @return int
     * @throws PrestaShopException
     */
    public function getPackStockType(): int
    {
        return $this->getProduct()->getPackStockType();
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function usesAdvancedStockManagement(): bool
    {
        return (bool)$this->getProduct()->advanced_stock_management;
    }

    /**
     * @return Product
     * @throws PrestaShopException
     */
    protected function getProduct(): Product
    {
        if (is_null($this->product)) {
            $this->product = new Product($this->productId, true);
        }
        return $this->product;
    }


    /**
     * Returns true, if $stockType value is one of the three allowed settings
     *   - STOCK_TYPE_DECREMENT_PACK,
     *   - STOCK_TYPE_DECREMENT_PRODUCTS
     *   - STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS
     * returns false for anything else, even STOCK_TYPE_DECREMENT_GLOBAL_SETTINGS
     *
     * @param int $stockType
     *
     * @return boolean
     */
    public static function isValidStockType($stockType)
    {
        $stockType = (int)$stockType;
        return (
            ($stockType === static::STOCK_TYPE_DECREMENT_PACK) ||
            ($stockType === static::STOCK_TYPE_DECREMENT_PRODUCTS) ||
            ($stockType === static::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS)
        );
    }

    /**
     * Returns public configuration for pack quantity adjustment
     *
     * @return int
     */
    public static function getGlobalStockTypeSettings()
    {
        try {
            $stockType = (int)Configuration::get(Configuration::PACK_STOCK_TYPE);
            if (static::isValidStockType($stockType)) {
                return $stockType;
            }
        } catch (Exception $ignored) {
        }
        return static::STOCK_TYPE_DECREMENT_PACK;
    }

    /**
     * Returns true, if quantity of pack itself should be adjusted with sale of pack
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function shouldAdjustQuantity(): bool
    {
        switch ($this->getPackStockType()) {
            case static::STOCK_TYPE_DECREMENT_PACK:
                return true;
            case static::STOCK_TYPE_DECREMENT_PRODUCTS:
                return false;
            case static::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS:
                return true;
            default:
                throw new RuntimeException('Invariant: getPackStockType returned invalid value');
        }
    }

    /**
     * Returns true, if quantities of pack items should be adjusted with sale of pack
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function shouldAdjustItemsQuantities(): bool
    {
        switch ($this->getPackStockType()) {
            case static::STOCK_TYPE_DECREMENT_PACK:
                return false;
            case static::STOCK_TYPE_DECREMENT_PRODUCTS:
                return true;
            case static::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS:
                return true;
            default:
                throw new RuntimeException('Invariant: getPackStockType returned invalid value');
        }
    }

    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function isFeatureActive()
    {
        return static::isCurrentlyUsed();
    }
    
    /**
     * @param int $idProduct
     *
     * @return float
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function noPackPrice($idProduct)
    {
        Tools::displayAsDeprecated();
        $pack = static::getProductLevelPack($idProduct);
        $withTax = Product::getTaxCalculationMethod() === PS_TAX_INC;
        return $pack ? $pack->getPrice($withTax) : 0.0;
    }

    /**
     * @param int $idProduct
     * @param int $idLang
     *
     * @return Product[]
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function getItems($idProduct, $idLang)
    {
        Tools::displayAsDeprecated();
        $idProduct = (int)$idProduct;
        $idLang = (int)$idLang;
        $arrayResult = [];
        $pack = static::getProductLevelPack($idProduct);
        if ($pack) {
            foreach ($pack->getPackItems() as $item) {
                $p = new Product($item->getProductId(), false, $idLang);
                $p->loadStockData();
                $p->pack_quantity = $item->getQuantity();
                $p->id_pack_product_attribute = $item->getCombinationId();
                if ($item->hasResolvedCombination()) {
                    $p->name = $item->getName($idLang);
                    $p->reference = $item->getReference();
                }
                $arrayResult[] = $p;
            }
        }
        return $arrayResult;
    }

    /**
     * Returns information about pack items.
     *
     * @param int $idProduct
     *
     * @return array
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function getPackContent($idProduct)
    {
        Tools::displayAsDeprecated();
        $pack = static::getProductLevelPack($idProduct);
        $content = [];
        if ($pack) {
            foreach ($pack->getPackItems() as $item) {
                $content[] = [
                    'id_product' => $item->getProductId(),
                    'id_product_attribute' => $item->getCombinationId(),
                    'quantity' => $item->getQuantity()
                ];
            }
        }
        return $content;
    }


    /**
     * @param int $idProduct
     *
     * @return float
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function noPackWholesalePrice($idProduct)
    {
        Tools::displayAsDeprecated();
        $pack = static::getProductLevelPack($idProduct);
        return $pack ? $pack->getWholesalePrice() : 0.0;
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function isInStock($idProduct)
    {
        Tools::displayAsDeprecated();
        $pack = static::getProductLevelPack($idProduct);
        return $pack ? $pack->canBeOrdered() : false;
    }

    /**
     * @param int $idProduct
     * @param int $idLang
     * @param bool $full
     *
     * @return array
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function getItemTable($idProduct, $idLang, $full = false)
    {
        Tools::displayAsDeprecated();
        $pack = static::getProductLevelPack((int)$idProduct);
        return $pack ? $pack->getItemsInformations($idLang, $full) : [];
    }

    /**
     * Is product a pack?
     *
     * @param int $idProduct
     *
     * @return bool
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function isPack($idProduct)
    {
        Tools::displayAsDeprecated();
        $pack = static::getProductLevelPack($idProduct);
        return $pack && $pack->hasItems();
    }

    /**
     * @param int $idProduct
     * @param int $idLang
     * @param bool $full
     * @param int|null $limit
     *
     * @return array
     */
    public static function getPacksTable($idProduct, $idLang, $full = false, $limit = null)
    {
        Tools::displayAsDeprecated();
        return [];
    }

    /**
     * Is product in a pack?
     *
     * If $id_product_attribute specified, then will restrict search on the given combination,
     * else this method will match a product if at least one of all its combination is in a pack.
     *
     * @param int $idProduct
     * @param bool|int $idProductAttribute Optional combination of the product
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function isPacked($idProduct, $idProductAttribute = false)
    {
        $combinationId = $idProductAttribute === false
            ? static::ANY_COMBINATION
            : (int)$idProductAttribute;
        return (bool)static::getPacksContaining((int)$idProduct, $combinationId);
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function deleteItems($idProduct)
    {
        Tools::displayAsDeprecated();
        $pack = static::getProductLevelPack($idProduct);
        return $pack ? $pack->delete() : true;
    }

    /**
     * This method returns true, if at least one pack is defined
     *
     * @param string $table
     * @param bool $hasActiveColumn
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function isCurrentlyUsed($table = null, $hasActiveColumn = false)
    {
        static $isUsed = null;
        if (is_null($isUsed)) {
            $sql = (new DbQuery())
                ->select(1)
                ->from('pack');
            $isUsed = (bool)Db::readOnly()->getValue($sql);
        }
        return $isUsed;
    }

    /**
     * Add an item to the pack
     *
     * @param int $idProduct
     * @param int $idItem
     * @param int $qty
     * @param int $idAttributeItem
     *
     * @return bool true if everything was fine
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function addItem($idProduct, $idItem, $qty, $idAttributeItem = 0)
    {
        Tools::displayAsDeprecated();
        return static::getOrCreate($idProduct, static::PRODUCT_LEVEL_PACK)
            ->removeItem($idItem, $idAttributeItem)
            ->addOrUpdateItem($idItem, $idAttributeItem, (int)$qty)
            ->save();
    }

    /**
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function duplicate($idProductOld, $idProductNew)
    {
        Tools::displayAsDeprecated();
        $oldPack = static::getProductLevelPack($idProductOld);
        if ($oldPack) {
            $newPack = new Pack($idProductNew, static::PRODUCT_LEVEL_PACK);
            foreach ($oldPack->getPackItems() as $item) {
                $newPack->addOrUpdateItem($item->getProductId(), $item->getCombinationId(), $item->getQuantity());
            }
            return $newPack->save();
        }
        return true;
    }

    /**
     * For a given pack, tells if all products using the advanced stock management
     *
     * @param int $idProduct id_pack
     *
     * @return bool
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function allUsesAdvancedStockManagement($idProduct)
    {
        Tools::displayAsDeprecated();
        $pack = static::getProductLevelPack($idProduct);
        return $pack ? $pack->allItemsUsesAdvancedStockManagement() : false;
    }

    /**
     * Returns Packs that contains the given product in the right declinaison.
     *
     * @param integer $idItem Product item id that could be contained in a|many pack(s)
     * @param integer $idAttributeItem The declinaison of the product
     * @param integer $idLang
     *
     * @return Product[] Packs that contains the given product
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function getPacksContainingItem($idItem, $idAttributeItem, $idLang)
    {
        Tools::displayAsDeprecated();
        $arrayResult = [];
        foreach (static::getPacksContaining($idItem, $idAttributeItem) as $pack) {
            $productPack = new Product($pack->getProductId(), true, $idLang);
            $productPack->pack_item_quantity = $pack->findItem($idItem, $idAttributeItem)->getQuantity();
            $arrayResult[] = $productPack;
        }
        return $arrayResult;
    }

    /**
     * Returns information about all packs $idItem is part of, and item quantity
     *
     * @param int $idItem
     * @param int $idAttributeItem
     *
     * @return array
     * @throws PrestaShopException
     * @deprecated 1.7.0
     */
    public static function getItemQuantitiesInPacks($idItem, $idAttributeItem)
    {
        Tools::displayAsDeprecated();
        $idItem = (int)$idItem;
        $idAttributeItem = (int)$idAttributeItem;
        $packs = static::getPacksContaining($idItem, $idAttributeItem);
        $ret = [];
        foreach ($packs as $pack) {
            $ret[$pack->getProductId()] = $pack->findItem($idItem, $idAttributeItem)->getQuantity();
        }
        return $ret;
    }

}
