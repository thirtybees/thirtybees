<?php

namespace Thirtybees\Core\View\Model;

use Combination;
use PrestaShopException;
use Product;
use StockAvailable;

class ProductViewModelCore extends Product
{

    const LEGACY_PROPERTY_GETTER = [
        'id_image' => 'getCoverImageId',
        'allow_oosp' => 'availableWhenOutOfStock',
    ];

    /**
     * @var array
     */
    protected $legacyPropertyValues = [];

    /**
     * @var Combination|null
     */
    protected $selectedCombination;

    /**
     * @var int|null
     */
    protected $coverImageId;

    /**
     * @param int $productId
     * @param int $combinationId
     * @param int $languageId
     * @param int $shopId
     *
     * @throws PrestaShopException
     */
    public function __construct(int $productId, int $combinationId, int $languageId, int $shopId)
    {
        parent::__construct($productId, true, $languageId, $shopId);
        if ($combinationId) {
            $this->selectedCombination = new Combination($combinationId, $languageId, $shopId);

            // recalculate price
            $this->price = static::getPriceStatic(
                (int) $this->id,
                false,
                $combinationId,
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
            $this->unit_price = ($this->unit_price_ratio != 0)
                ? round($this->price / $this->unit_price_ratio, _TB_PRICE_DATABASE_PRECISION_)
                : 0;

            // recalculate quantity
            $this->quantity = StockAvailable::getQuantityAvailableByProduct($this->id, $combinationId);
            $this->out_of_stock = StockAvailable::outOfStock($this->id, $shopId, $combinationId);
            $this->depends_on_stock = StockAvailable::dependsOnStock($this->id, $shopId, $combinationId);
        }
    }


    /**
     * Get all available attribute groups
     *
     * @param int $idLang Language id
     *
     * @return array Attribute groups
     *
     * @throws PrestaShopException
     */
    public function getAttributesGroups($idLang)
    {
        $attributeGroups = parent::getAttributesGroups($idLang);
        if ($this->selectedCombination) {
            $combinationAttributes = $this->selectedCombination->getAttributes();
            foreach ($attributeGroups as &$attributeGroup) {
                $attributeGroupId = (int)$attributeGroup['id_attribute_group'];
                $attributeId = (int)$attributeGroup['id_attribute'];
                $combinationAttributeId = $combinationAttributes[$attributeGroupId] ?? 0;
                $attributeGroup['default_on'] = $combinationAttributeId === $attributeId ? 1 : 0;
            }
        }
        return $attributeGroups;
    }


    /**
     * Get product price
     * Same as static function getPriceStatic, no need to specify product id
     *
     * @param bool $tax With taxes or not (optional)
     * @param int $idProductAttribute Product attribute id (optional)
     * @param int $decimals Number of decimals (optional)
     * @param int $divisor Util when paying many time without fees (optional)
     *
     * @return float Product price in euros
     *
     * @throws PrestaShopException
     */
    public function getPrice($tax = true, $idProductAttribute = null, $decimals = _TB_PRICE_DATABASE_PRECISION_, $divisor = null, $onlyReduc = false, $usereduc = true, $quantity = 1)
    {
        if ($idProductAttribute === null) {
            $idProductAttribute = $this->getSelectedCombinationId();
        }
        return parent::getPrice($tax, $idProductAttribute, $decimals, $divisor, $onlyReduc, $usereduc, $quantity);
    }

    /**
     * @return int|null
     */
    public function getSelectedCombinationId()
    {
        if ($this->selectedCombination) {
            return (int)$this->selectedCombination->id;
        }
        return null;
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
    public function &__get($property)
    {
        if (array_key_exists($property, $this->legacyPropertyValues)) {
            return $this->legacyPropertyValues[$property];
        }
        if (array_key_exists($property, static::LEGACY_PROPERTY_GETTER)) {
            $methodName = static::LEGACY_PROPERTY_GETTER[$property];
            $this->legacyPropertyValues[$property] = $this->$methodName();
            return $this->legacyPropertyValues[$property];
        }
        return parent::__get($property) ;
    }

    /**
     * @return int
     * @throws PrestaShopException
     */
    public function getCoverImageId(): int
    {
        $cover = Product::getCover($this->id);
        return $cover['id_image'] ?? 0;
    }

    /**
     * return bool|int
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function availableWhenOutOfStock(): bool
    {
        return Product::isAvailableWhenOutOfStock($this->out_of_stock);
    }

}