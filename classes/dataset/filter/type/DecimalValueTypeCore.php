<?php

namespace Thirtybees\Core\Dataset\Filter\Type;

use Thirtybees\Core\Dataset\Filter\Operator\AnyOfOperator;
use Thirtybees\Core\Dataset\Filter\Operator\BetweenOperator;
use Thirtybees\Core\Dataset\Filter\Operator\EqualsOperator;
use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;
use Thirtybees\Core\Dataset\Filter\Operator\LessThanOperator;
use Thirtybees\Core\Dataset\Filter\Operator\MoreThanOperator;
use Tools;

/**
 *
 */
class DecimalValueTypeCore extends ValueTypeBase implements ValueType
{
    /**
     * @var int
     */
    protected int $decimalPoints;

    /**
     * @param int $decimalPoints
     */
    public function __construct(int $decimalPoints)
    {
        $this->decimalPoints = $decimalPoints;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'decimal';
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function serializeValue($value): string
    {
        $value = $this->deserializeValue((string)$value) ;
        return (string)$value;
    }

    /**
     * @param mixed $value
     *
     * @return float
     */
    public function toJavascriptValue($value)
    {
        return $this->deserializeValue((string)$value) ;
    }

    /**
     * @param string $value
     * @return float
     */
    public function deserializeValue(string $value)
    {
        return Tools::parseNumber($value, $this->decimalPoints);
    }

    /**
     * @return static
     */
    public static function priceDatabasePrecision()
    {
        static $instance = null;
        if (is_null($instance)) {
            $instance = new static(_TB_PRICE_DATABASE_PRECISION_);
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
     * @param float $value
     * @param bool $addQuotes
     * @return string
     */
    public function escapeForSql($value, bool $addQuotes = true): string
    {
        $formatted = number_format((float)$value, $this->decimalPoints, '.', '');
        if (strpos($formatted, '.') !== false) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
            if ($formatted === '') {
                $formatted = '0.0';
            }
        }
        return $formatted;
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