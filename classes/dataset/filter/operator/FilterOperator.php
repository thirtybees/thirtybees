<?php

namespace Thirtybees\Core\Dataset\Filter\Operator;


use Thirtybees\Core\Dataset\Filter\Type\ValueType;

interface FilterOperator
{
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
     * @param string $alias
     * @param array $operands
     *
     * @return string
     */
    public function getCondition(ValueType $valueType, string $alias, array $operands): string;

    /**
     * @param ValueType $valueType
     * @param string $fieldName
     * @param array $operands
     * @return string
     */
    public function describe(ValueType $valueType, string $fieldName, array $operands): string;
}