<?php

namespace Thirtybees\Core\Dataset\Filter\Operator;


use Thirtybees\Core\Dataset\Filter\Type\ValueType;

interface FilterOperator
{
    const VARIABLE_ARGUMENTS = -1;

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return int
     */
    public function getNumOperands(): int;

    /**
     * @param ValueType $valueType
     * @param string $serializedValue
     * @return array|null
     */
    public function deserializeOperands(ValueType $valueType, string $serializedValue): ?array;

    /**
     * @param ValueType $valueType
     * @param array $operands
     * @return string
     */
    public function serializeOperands(ValueType $valueType, array $operands): string;

    /**
     * @param ValueType $valueType
     * @param string $alias
     * @param array $operands
     * @param bool $inverted
     *
     * @return string
     */
    public function getCondition(ValueType $valueType, string $alias, array $operands, bool $inverted): string;

    /**
     * @param ValueType $valueType
     * @param string $fieldName
     * @param array $operands
     * @param bool $inverted
     * @return string
     */
    public function describe(ValueType $valueType, string $fieldName, array $operands, bool $inverted): string;
}