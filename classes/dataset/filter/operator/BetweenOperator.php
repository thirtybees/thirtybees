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
        if ($valueType->isValid($operands[0])) {
            $operands[0] = $valueType->adjustStartOfInterval($operands[0]);
        } else {
            $operands[0] = null;
        }
        if ($valueType->isValid($operands[1])) {
            $operands[1] = $valueType->adjustEndOfInterval($operands[1]);
        } else {
            $operands[1] = null;
        }
        return $operands;
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
        $conds = [];
        $opers = $inverted
            ? ['<', '>', ' OR ']
            : ['>=', '<=', ' AND '];
        if ($valueType->isValid($operands[0])) {
            $conds[] = $alias . $opers[0] . $valueType->escapeForSql($operands[0]);
        }
        if ($valueType->isValid($operands[1])) {
            $conds[] = $alias . $opers[1] . $valueType->escapeForSql($operands[1]);
        }
        if (! $conds) {
            return '';
        }
        if (count($conds) > 1) {
            return '(' . implode($opers[2], $conds) . ')';
        } else {
            return $conds[0];
        }
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
        $valid1 = $valueType->isValid($operands[0]);
        $valid2 = $valueType->isValid($operands[1]);
        if ($valid1 && $valid2) {
            $from = $valueType->describeValue($operands[0]);
            $to = $valueType->describeValue($operands[1]);
            return $inverted
                ? sprintf(Translate::getAdminTranslation('%1$s not between %2$s and %3$s'), $fieldName, $from, $to)
                : sprintf(Translate::getAdminTranslation('%1$s between %2$s and %3$s'), $fieldName, $from, $to);
        }
        if ($valid1) {
            $from = $valueType->describeValue($operands[0]);
            return $inverted
                ? sprintf(Translate::getAdminTranslation('%1$s before %2$s'), $fieldName, $from)
                : sprintf(Translate::getAdminTranslation('%1$s after %2$s'), $fieldName, $from);
        }
        if ($valid2) {
            $to = $valueType->describeValue($operands[1]);
            return $inverted
                ? sprintf(Translate::getAdminTranslation('%1$s after %2$s'), $fieldName, $to)
                : sprintf(Translate::getAdminTranslation('%1$s before %2$s'), $fieldName, $to);
        }
        return '';
    }

}