<?php

namespace Thirtybees\Core\Dataset\Filter\Operator;

use PrestaShopException;
use Thirtybees\Core\Dataset\Filter\Type\ValueType;
use Translate;

class EqualsOperatorCore extends FilterOperatorBase implements FilterOperator
{
    /**
     * @return string
     */
    public function getId(): string
    {
        return 'equals';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return Translate::getAdminTranslation('Equals');
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
     * @param bool $inverted
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getCondition(ValueType $valueType, string $alias, array $operands, bool $inverted): string
    {
        $this->validateOperands($operands);
        $oper = $inverted ? ' != ' : ' = ';
        return $alias . $oper . $valueType->escapeForSql($operands[0]);
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
        $describedValue = $valueType->describeValue($operands[0]);
        return $inverted
            ? sprintf(Translate::getAdminTranslation('%1$s is not equal to %2$s'), $fieldName, $describedValue)
            : sprintf(Translate::getAdminTranslation('%1$s equals %2$s'), $fieldName, $describedValue);
    }


}