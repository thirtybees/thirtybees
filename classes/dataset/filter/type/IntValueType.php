<?php

namespace Thirtybees\Core\Dataset\Filter\Type;

use Thirtybees\Core\Dataset\Filter\Operator\AnyOfOperator;
use Thirtybees\Core\Dataset\Filter\Operator\BetweenOperator;
use Thirtybees\Core\Dataset\Filter\Operator\EqualsOperator;
use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;
use Thirtybees\Core\Dataset\Filter\Operator\LessThanOperator;
use Thirtybees\Core\Dataset\Filter\Operator\MoreThanOperator;

class IntValueTypeCore extends ValueTypeBase implements ValueType
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return 'int';
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function serializeValue($value): string
    {
        if (is_string($value)) {
            $value = $this->deserializeValue((string)$value) ;
        } else {
            $value = (int)$value;
        }
        return (string)$value;
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    public function toJavascriptValue($value)
    {
        return (int)$value;
    }

    /**
     * @param string $value
     * @return int
     */
    public function deserializeValue(string $value)
    {
        return (int)$value;
    }

    /**
     * @return static
     */
    public static function instance()
    {
        static $instance = null;
        if (is_null($instance)) {
            $instance = new static();
        }
        return $instance;
    }

    /**
     * @return FilterOperator
     */
    public function getDefaultOperator(): FilterOperator
    {
        return EqualsOperator::instance();
    }

    /**
     * @return FilterOperator[]
     */
    public function getSupportedOperators(): array
    {
        return [
            EqualsOperator::instance(),
            BetweenOperator::instance(),
            LessThanOperator::instance(),
            MoreThanOperator::instance(),
            AnyOfOperator::instance(),
        ];
    }

    /**
     * @param int $value
     * @param bool $addQuotes
     * @return string
     */
    public function escapeForSql($value, bool $addQuotes = true): string
    {
        return (string)(int)$value;
    }

    /**
     * @param float $value
     * @return string
     */
    public function describeValue($value): string
    {
        return $this->escapeForSql($value);
    }

}