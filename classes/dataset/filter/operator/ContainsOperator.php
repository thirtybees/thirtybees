<?php

namespace Thirtybees\Core\Dataset\Filter\Operator;

use Thirtybees\Core\Dataset\Filter\Type\ValueType;
use Translate;

class ContainsOperatorCore extends FilterOperatorBase implements FilterOperator
{
    /**
     * @return string
     */
    public function getId(): string
    {
        return 'contains';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return Translate::getAdminTranslation('Contains');
    }

    /**
     * @return int
     */
    public function getNumOperands(): int
    {
        return 1;
    }

    /**
     * @param ValueType $valueType
     * @param string $alias
     * @param array $operands
     * @return string
     * @throws \PrestaShopException
     */
    public function getCondition(ValueType $valueType, string $alias, array $operands): string
    {
        $this->validateOperands($operands);
        return $alias . ' LIKE \'%' . trim($valueType->escapeForSql($operands[0], false)) . '%\'';
    }

    /**
     * @param ValueType $valueType
     * @param string $fieldName
     * @param array $operands
     * @return string
     */
    public function describe(ValueType $valueType, string $fieldName, array $operands): string
    {
        $describedValue = $valueType->describeValue($operands[0]);
        return sprintf(Translate::getAdminTranslation('%1$s contains %2$s'), $fieldName, $describedValue);
    }
}