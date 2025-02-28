<?php

namespace Thirtybees\Core\Dataset\Filter;


use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;
use Thirtybees\Core\Dataset\Query\DatasetQuery;

class FilterCore
{
    // filter displayed in list table column
    const TYPE_COLUMN = 'column';

    // separate filter displayed above list table
    const TYPE_FILTER = 'filter';

    /**
     * @var string
     */
    protected string $filterType;

    /**
     * @var string
     */
    protected string $filterId;

    /**
     * @var FilterField
     */
    protected FilterField $field;

    /**
     * @var bool
     */
    protected bool $inverted;

    /**
     * @var FilterOperator
     */
    protected FilterOperator $operator;

    /**
     * @var array
     */
    protected array $operands;

    /**
     * @param string $type
     * @param string $id
     * @param FilterField $field
     * @param bool $inverted
     * @param FilterOperator $operator
     * @param array $operands
     */
    public function __construct(
        string $type,
        string $id,
        FilterField $field,
        bool $inverted,
        FilterOperator $operator,
        array $operands
    ) {
        $this->filterType = $type;
        $this->filterId = $id;
        $this->field = $field;
        $this->inverted = $inverted;
        $this->operator = $operator;
        $this->operands = $operands;
    }

    /**
     * @param DatasetQuery $query
     *
     * @return void
     */
    public function apply(DatasetQuery $query): void
    {
        $this->field->getFilterApply()->apply(
            $query,
            $this->field->getValueType(),
            $this->inverted,
            $this->operator,
            $this->operands
        );
    }

    /**
     * @return string
     */
    public function getFilterType(): string
    {
        return $this->filterType;
    }

    /**
     * @return string
     */
    public function getFilterId(): string
    {
        return $this->filterId;
    }

    /**
     * @return FilterField
     */
    public function getField(): FilterField
    {
        return $this->field;
    }

    /**
     * @return bool
     */
    public function isInverted(): bool
    {
        return $this->inverted;
    }

    /**
     * @return FilterOperator
     */
    public function getOperator(): FilterOperator
    {
        return $this->operator;
    }

    /**
     * @return array
     */
    public function getOperands(): array
    {
        return $this->operands;
    }

    /**
     * @return string
     */
    public function describe(): string
    {
        return $this->operator->describe(
            $this->field->getValueType(),
            $this->field->getDisplayName(),
            $this->operands,
            $this->inverted
        );
    }
}