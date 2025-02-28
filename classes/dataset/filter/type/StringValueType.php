<?php

namespace Thirtybees\Core\Dataset\Filter\Type;

use PrestaShopException;
use Thirtybees\Core\Dataset\Filter\Operator\AnyOfOperator;
use Thirtybees\Core\Dataset\Filter\Operator\BetweenOperator;
use Thirtybees\Core\Dataset\Filter\Operator\ContainsOperator;
use Thirtybees\Core\Dataset\Filter\Operator\EndsWithOperator;
use Thirtybees\Core\Dataset\Filter\Operator\EqualsOperator;
use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;
use Thirtybees\Core\Dataset\Filter\Operator\StartsWithOperator;

class StringValueTypeCore extends ValueTypeBase implements ValueType
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return 'string';
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function serializeValue($value): string
    {
        return (string)$value;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function toJavascriptValue($value)
    {
        return (string)$value;
    }

    /**
     * @param string $value
     * @return string
     */
    public function deserializeValue(string $value)
    {
        return (string)$value;
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
        return ContainsOperator::instance();
    }

    /**
     * @return FilterOperator[]
     */
    public function getSupportedOperators(): array
    {
        return [
            EqualsOperator::instance(),
            ContainsOperator::instance(),
            StartsWithOperator::instance(),
            EndsWithOperator::instance(),
            AnyOfOperator::instance(),
        ];
    }

    /**
     * @param string $value
     * @param bool $addQuotes
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function escapeForSql($value, bool $addQuotes = true): string
    {
        $escaped = pSQL($value);
        if ($addQuotes) {
            return "'" . $escaped . "'";
        } else {
            return $escaped;
        }
    }

    /**
     * @param float $value
     * @return string
     */
    public function describeValue($value): string
    {
        return "'" . $value . "'";
    }

}