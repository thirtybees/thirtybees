<?php

namespace Thirtybees\Core\Dataset\Filter\Apply;


use PrestaShopException;
use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;
use Thirtybees\Core\Dataset\Filter\Type\ValueType;
use Thirtybees\Core\Dataset\Query\DatasetQuery;

class FilterApplyTableColumnCore implements FilterApply
{
    /**
     * @var string
     */
    protected string $table;
    /**
     * @var string
     */
    protected string $column;

    /**
     * @param string $table
     * @param string $column
     */
    public function __construct(string $table, string $column)
    {
        $this->table = $table;
        $this->column = $column;
    }

    /**
     * @param DatasetQuery $query
     * @param ValueType $valueType
     * @param bool $inverted
     * @param FilterOperator $operator
     * @param array $operands
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function apply(
        DatasetQuery $query,
        ValueType $valueType,
        bool $inverted,
        FilterOperator $operator,
        array $operands
    ): void
    {
        $cond = $operator->getCondition($valueType, $this->getFilterAlias(), $operands, $inverted);
        $query->addColumnSqlFilter($cond);
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    public function getFilterAlias(): string
    {
        return '`' . bqSQL($this->table) . '`.`' . bqSQL($this->column) . '`';
    }

}