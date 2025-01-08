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
     * @param array $operands
     * @return void
     *
     * @throws PrestaShopException
     */
    protected function validateOperands(array $operands): void
    {
        $actual = count($operands);
        $expected = $this->getNumOperands();
        if ($expected !== $actual) {
            throw new PrestaShopException("Invalid number of operands for '" . $this->getName() . "' operator. Expected $expected, got $actual");
        }
    }

}