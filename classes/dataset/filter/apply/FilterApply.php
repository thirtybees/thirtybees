<?php

namespace Thirtybees\Core\Dataset\Filter\Apply;

use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;
use Thirtybees\Core\Dataset\Filter\Type\ValueType;
use Thirtybees\Core\Dataset\Query\DatasetQuery;

/**
 * how to apply filter to sql
 */
interface FilterApply
{
    /**
     * @param DatasetQuery $query
     * @param ValueType $valueType
     * @param bool $inverted
     * @param FilterOperator $operator
     * @param array $operands
     *
     * @return void
     */
    public function apply(
        DatasetQuery $query,
        ValueType $valueType,
        bool $inverted,
        FilterOperator $operator,
        array $operands
    ): void;

    /**
     * @return string
     */
    public function getFilterAlias(): string;
}