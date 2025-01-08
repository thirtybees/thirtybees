<?php

namespace Thirtybees\Core\Dataset\Filter\Apply;


use PrestaShopException;
use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;
use Thirtybees\Core\Dataset\Filter\Type\ValueType;
use Thirtybees\Core\Dataset\Query\DatasetQuery;

class FilterApplyUnqualifiedColumnCore implements FilterApply
{
    /**
     * @var string
     */
    protected string $column;

    /**
     * @param string $column
     */
    public function __construct(string $column)
    {
        $this->column = $column;
    }

    /**
     * @param DatasetQuery $query
     * @param ValueType $valueType
     * @param FilterOperator $operator
     * @param array $operands
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function apply(DatasetQuery $query, ValueType $valueType, FilterOperator $operator, array $operands): void
    {
        $cond = $operator->getCondition($valueType, $this->getFilterAlias(), $operands);
        $query->addColumnSqlFilter($cond);
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    public function getFilterAlias(): string
    {
        return '`' . bqSQL($this->column) . '`';
    }
}