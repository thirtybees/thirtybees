<?php

namespace Thirtybees\Core\Dataset\Filter\Operator;


use PrestaShopException;
use Thirtybees\Core\Dataset\Filter\Type\ValueType;
use Translate;

class AnyOfOperatorCore extends FilterOperatorBase implements FilterOperator
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return Translate::getAdminTranslation('Any of');
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'anyOf';
    }

    /**
     * @return int
     */
    public function getNumOperands(): int
    {
        return static::VARIABLE_ARGUMENTS;
    }

    /**
     * @param ValueType $valueType
     * @param string $alias
     * @param array $operands
     * @param bool $inverted
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getCondition(ValueType $valueType, string $alias, array $operands, bool $inverted): string
    {
        $this->validateOperands($operands);

        if (! $operands) {
            // fast fail
            return "1 = 0";
        }

        $values = array_map(function($value) use ($valueType) {
            return $valueType->escapeForSql($value);
        }, $operands);

        $not = $inverted ? ' NOT' : '';
        return $alias . $not . ' IN (' . implode(', ', $values) . ')';
    }

    /**
     * @param ValueType $valueType
     * @param string $fieldName
     * @param array $operands
     * @param bool $inverted
     * @return string
     */
    public function describe(ValueType $valueType, string $fieldName, array $operands, bool $inverted): string
    {
        $values = array_map(function($value) use ($valueType) {
            return $valueType->describeValue($value);
        }, $operands);
        $values = implode(', ', $values);

        return $inverted
            ? sprintf(Translate::getAdminTranslation('%1$s is not any of [%2$s]'), $fieldName, $values)
            : sprintf(Translate::getAdminTranslation('%1$s is any of [%2$s]'), $fieldName, $values);
    }

}