<?php

class PackItemCore
{
    /**
     * @var int
     */
    protected int $productId;

    /**
     * @var int
     */
    protected int $combinationId;

    /**
     * @var int
     */
    protected int $quantity;

    /**
     * @var Combination|null
     */
    protected ?Combination $combination = null;

    /**
     * @var Product|null $product
     */
    protected ?Product $product = null;

    /**
     * @var float|null
     */
    private ?float $cachePrice = null;

    /**
     * @param int $productId
     * @param int $combinationId
     * @param int $quantity
     */
    public function __construct(int $productId, int $combinationId, int $quantity)
    {
        $this->productId = $productId;
        $this->combinationId = $combinationId;
        $this->quantity = $quantity;
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * @param int $productId
     * @return $this
     */
    public function setProductId(int $productId)
    {
        $this->productId = $productId;
        return $this;
    }

    /**
     * @return int
     */
    public function getCombinationId(): int
    {
        return $this->combinationId;
    }

    /**
     * @param int $combinationId
     * @return $this
     */
    public function setCombinationId(int $combinationId)
    {
        $this->combinationId = $combinationId;
        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return $this
     */
    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return float
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getUnitWholesalePrice(): float
    {
        $wholesalePrice = 0.0;
        if ($this->hasResolvedCombination()) {
            $combination = $this->getCombination();
            $wholesalePrice = (float)$combination->wholesale_price;
        }
        if (! $wholesalePrice) {
            $product = $this->getProduct();
            $wholesalePrice = (float)$product->wholesale_price;
        }
        return $wholesalePrice;
    }

    /**
     * @param bool $withTax
     *
     * @return float
     *
     * @throws PrestaShopException
     */
    public function getUnitPrice(bool $withTax): float
    {
        if (is_null($this->cachePrice)) {
            $combinationId = $this->hasResolvedCombination() ? $this->combinationId : null;
            $price = Product::getPriceStatic($this->productId, $withTax, $combinationId);
            $this->cachePrice = $price;
        }
        return (float)$this->cachePrice;
    }

    /**
     * @return float
     * @throws PrestaShopException
     */
    public function getUnitWeight(): float
    {
        $product = $this->getProduct();
        $weight = (float)$product->weight;
        if ($this->hasResolvedCombination()) {
            $combination = $this->getCombination();
            $weight += (float)$combination->weight;
        }
        return $weight;
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function canBeOrdered(): bool
    {
        // enough quantity in stock
        $quantityAvailable = Product::getQuantity($this->productId, $this->combinationId);
        if ($quantityAvailable >= $this->quantity) {
            return true;
        }

        // not enough quantity in stock, check if we can back-order
        $product = $this->getProduct();
        if (Product::isAvailableWhenOutOfStock((int)$product->out_of_stock)) {
            return true;
        }

        return false;
    }

    /**
     * @return Combination
     * @throws PrestaShopException
     */
    protected function getCombination(): Combination
    {
        if (! $this->combination) {
            if ($this->combinationId === Pack::PRODUCT_LEVEL_PACK) {
                throw new PrestaShopException("Can't return combination for product level bundle");
            }
            if ($this->combinationId === Pack::VIRTUAL_PRODUCT_ATTRIBUTE) {
                throw new PrestaShopException("Can't return combination for virtual combination item");
            }
            $this->combination = new Combination($this->combinationId);
        }
        return $this->combination;
    }

        /**
         * @return Product
         * @throws PrestaShopException
         */
    protected function getProduct(): Product
    {
        if (! $this->product) {
            $this->product = new Product($this->productId, false);
            $this->product->loadStockData();
        }
        return $this->product;
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function usesAdvancedStockManagement(): bool
    {
        $product = $this->getProduct();
        return (bool)$product->advanced_stock_management;
    }

    /**
     * @param int $languageId
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getName(int $languageId): string
    {
        $productName = $this->getProductName($languageId);
        if ($this->combinationId === Pack::PRODUCT_LEVEL_PACK) {
            return $productName;
        }
        if ($this->combinationId === Pack::VIRTUAL_PRODUCT_ATTRIBUTE) {
            return $productName . ' ' . Translate::getAdminTranslation('(virtual attribute)');
        }
        return $productName . ': ' . $this->getCombinationName($languageId);
    }

    /**
     * @param int $languageId
     *
     * @return string
     * @throws PrestaShopException
     */
    public function getCombinationName(int $languageId): string
    {
        if ($this->hasResolvedCombination()) {
            return Combination::getCombinationName($this->productId, $this->combinationId, $languageId);
        } else {
            return '';
        }
    }

    /**
     * @param int $languageId
     *
     * @return string
     * @throws PrestaShopException
     */
    public function getProductName(int $languageId): string
    {
        $product = $this->getProduct();
        return $product->name[$languageId] ?? '';
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    public function getReference(): string
    {
        if ($this->combinationId === Pack::PRODUCT_LEVEL_PACK) {
            $product = $this->getProduct();
            return (string)$product->reference;
        }
        if ($this->combinationId === Pack::VIRTUAL_PRODUCT_ATTRIBUTE) {
            return Translate::getAdminTranslation('(to be determined)');
        }
        $combination = $this->getCombination();
        return (string)$combination->reference;
    }

    /**
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getImageId(): int
    {
        if ($this->hasResolvedCombination()) {
            $cover = Product::getCombinationImageById($this->combinationId, (int)Context::getContext()->language->id);
        } else {
            $cover = Product::getCover($this->productId);
        }
        if (isset($cover['id_image'])) {
            return (int)$cover['id_image'];
        }
        return 0;
    }

    /**
     * @param int $languageId
     * @return string
     * @throws PrestaShopException
     */
    public function getLinkRewrite(int $languageId): string
    {
        $product = $this->getProduct();
        return (string)($product->link_rewrite[$languageId] ?? '');
    }

    /**
     * @return bool
     */
    public function hasResolvedCombination(): bool
    {
        return ($this->combinationId && $this->combinationId !== Pack::VIRTUAL_PRODUCT_ATTRIBUTE);
    }

}