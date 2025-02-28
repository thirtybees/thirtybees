<?php

namespace Thirtybees\Core\Dataset\Filter\Operator;

use PrestaShopException;
use Thirtybees\Core\Dataset\Filter\Type\ValueType;

abstract class FilterOperatorBaseCore implements FilterOperator
{
    /**
     * @var FilterOperator[]
     */
    protected static $instances = [];

    /**
     * @return FilterOperator
     */
    public static function instance(): FilterOperator
    {
        if (! array_key_exists(static::class, self::$instances)) {
            static::$instances[static::class] = new static();
        }
        return static::$instances[static::class];
    }

    /**
     * @param ValueType $valueType
     * @param string $serializedValue
     * @return array|null
     */
    public function deserializeOperands(ValueType $valueType, string $serializedValue): ?array
    {
        $cnt = $this->getNumOperands();

        if ($cnt === static::VARIABLE_ARGUMENTS) {
            return $this->deserializeVariableArguments($valueType, $serializedValue);
        }

        if ($cnt === 0) {
            return [];
        }

        if ($cnt === 1) {
            return [
                $valueType->deserializeValue($serializedValue)
            ];
        }

        $array = json_decode($serializedValue, true);

        if (! is_array($array)) {
            trigger_error("Failed to deserialize value '{$serializedValue}'", E_USER_WARNING);
            return null;
        }

        $actualCount = count($array);
        if ($actualCount < $cnt) {
            trigger_error("Deserialized filter value should be an array with $cnt elements, has $actualCount elements", E_USER_WARNING);
            return null;
        }

        return array_map(function($val) use ($valueType) {
            return $valueType->deserializeValue($val);
        }, $array);
    }

    /**
     * @param ValueType $valueType
     * @param array $operands
     * @return string
     */
    public function serializeOperands(ValueType $valueType, array $operands): string
    {
        $cnt = $this->getNumOperands();

        if ($cnt === static::VARIABLE_ARGUMENTS) {
            return $this->serializeVariableArguments($valueType, $operands);
        }

        $actual = count($operands);
        if ($actual != $cnt) {
            trigger_error("Failed to serialize operands, invalid length. Expected $cnt, got $actual", E_USER_WARNING);
            return '';
        }

        if ($cnt === 0) {
            return '';
        }

        if ($cnt === 1) {
            return $valueType->serializeValue($operands[0]);
        }
        return json_encode(array_map(function($val) use ($valueType) {
            return $valueType->serializeValue($val);
        }, $operands));
    }

    /**
     * @param array $operands
     * @return void
     *
     * @throws PrestaShopException
     */
    protected function validateOperands(array $operands): void
    {
        $expected = $this->getNumOperands();
        if ($expected !== static::VARIABLE_ARGUMENTS) {
            $actual = count($operands);
            if ($expected !== $actual) {
                throw new PrestaShopException("Invalid number of operands for '" . $this->getName() . "' operator. Expected $expected, got $actual");
            }
        }
    }

    /**
     * @param ValueType $valueType
     * @param string $serializedValue
     * @return array
     */
    protected function deserializeVariableArguments(ValueType $valueType, string $serializedValue): array
    {
        $array = json_decode($serializedValue, true);
        if (! is_array($array)) {
            trigger_error("Failed to deserialize value '{$serializedValue}'", E_USER_WARNING);
            return [];
        }
        return array_map(function($val) use ($valueType) {
            return $valueType->deserializeValue($val);
        }, $array);
    }

    /**
     * @param ValueType $valueType
     * @param array $operands
     * @return string
     */
    protected function serializeVariableArguments(ValueType $valueType, array $operands): string
    {
        return json_encode(array_map(function($val) use ($valueType) {
            return $valueType->serializeValue($val);
        }, $operands));
    }

}