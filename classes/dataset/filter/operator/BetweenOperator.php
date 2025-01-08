<?php

namespace Thirtybees\Core\Dataset\Filter\Operator;


use PrestaShopException;
use Thirtybees\Core\Dataset\Filter\Type\ValueType;
use Translate;

class BetweenOperatorCore extends FilterOperatorBase implements FilterOperator
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return Translate::getAdminTranslation('Between');
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'between';
    }

    /**
     * @return int
     */
    public function getNumOperands(): int
    {
        return 2;
    }

    /**
     * @param ValueType $valueType
     * @param string $serializedValue
     * @return array|null
     */
    public function deserializeOperands(ValueType $valueType, string $serializedValue): ?array
    {
        $operands = parent::deserializeOperands($valueType, $serializedValue);
        if (! empty($operands[0])) {
            $operands[0] = $valueType->adjustStartOfInterval($operands[0]);
        }
        if (! empty($operands[1])) {
            $operands[1] = $valueType->adjustEndOfInverval($operands[1]);
        }
        return $operands;
    }

    /**
     * @param ValueType $valueType
     * @param string $alias
     * @param array $operands
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getCondition(ValueType $valueType, string $alias, array $operands): string
    {
        $this->validateOperands($operands);
        $conds = [];
        if (! is_null($operands[0])) {
            $conds[] = $alias . ' >= ' . $valueType->escapeForSql($operands[0]);
        }
        if (! is_null($operands[1])) {
            $conds[] = $alias . ' <= ' . $valueType->escapeForSql($operands[1]);
        }
        if (! $conds) {
            return '';
        }
        if (count($conds) > 1) {
            return '(' . implode(' AND ', $conds) . ')';
        } else {
            return $conds[0];
        }
    }

    /**
     * @param ValueType $valueType
     * @param string $fieldName
     * @param array $operands
     * @return string
     */
    public function describe(ValueType $valueType, string $fieldName, array $operands): string
    {
        if (! is_null($operands[0]) && ! is_null($operands[1])) {
            $from = $valueType->describeValue($operands[0]);
            $to = $valueType->describeValue($operands[1]);
            return sprintf(Translate::getAdminTranslation('%1$s between %2$s and %3$s'), $fieldName, $from, $to);
        }
        if (! is_null($operands[0])) {
            $from = $valueType->describeValue($operands[0]);
            return sprintf(Translate::getAdminTranslation('%1$s after %2$s'), $fieldName, $from);
        }
        if (! is_null($operands[1])) {
            $to = $valueType->describeValue($operands[1]);
            return sprintf(Translate::getAdminTranslation('%1$s before %2$s'), $fieldName, $to);
        }
        return '';
    }

}