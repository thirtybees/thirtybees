<?php

namespace Thirtybees\Core\Dataset\Filter;

use Thirtybees\Core\Dataset\Filter\Apply\FilterApply;
use Thirtybees\Core\Dataset\Filter\Type\ValueType;

class FilterFieldCore
{
    /**
     * @var string
     */
    protected string $id;

    /**
     * @var string
     */
    protected string $displayName;

    /**
     * @var ValueType
     */
    protected ValueType $valueType;

    /**
     * @var FilterApply
     */
    protected FilterApply $filterApply;

    /**
     * @param string $id
     * @param string $displayName
     * @param ValueType $valueType
     * @param FilterApply $filterApply
     */
    public function __construct(string $id, string $displayName, ValueType $valueType, FilterApply $filterApply)
    {
        $this->id = $id;
        $this->displayName = $displayName;
        $this->valueType = $valueType;
        $this->filterApply = $filterApply;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return ValueType
     */
    public function getValueType(): ValueType
    {
        return $this->valueType;
    }

    /**
     * @return FilterApply
     */
    public function getFilterApply(): FilterApply
    {
        return $this->filterApply;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

}