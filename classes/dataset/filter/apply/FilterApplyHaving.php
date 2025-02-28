<?php

namespace Thirtybees\Core\Dataset\Filter\Apply;

use PrestaShopException;
use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;
use Thirtybees\Core\Dataset\Filter\Type\ValueType;
use Thirtybees\Core\Dataset\Query\DatasetQuery;

class FilterApplyHavingCore implements FilterApply
{
    /**
     * @var string
     */
    protected string $columnAlias;

    /**
     * @param string $columnAlias
     */
    public function __construct(string $columnAlias)
    {
        $this->columnAlias = $columnAlias;
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
        $query->addHavingSqlFilter($cond);
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getFilterAlias(): string
    {
        return '`' . bqSQL($this->columnAlias) . '`';
    }


}