<?php

namespace Thirtybees\Core\Dataset\Filter\Type;

use DateTime;
use Thirtybees\Core\Dataset\Filter\Operator\BetweenOperator;
use Thirtybees\Core\Dataset\Filter\Operator\EqualsOperator;
use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;
use Validate;

/**
 *
 */
class DatetimeValueTypeCore extends ValueTypeBase implements ValueType
{
    /**
     * @var string
     */
    protected string $format;

    /**
     * @param string $format
     */
    public function __construct(string $format)
    {
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'datetime';
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function serializeValue($value): string
    {
        if (! $value instanceof DateTime) {
            if (is_string($value)) {
                $value = $this->deserializeValue((string)$value);
            } elseif (is_int($value)) {
                $value = $this->fromTimestamp((int)$value);
            }
        }
        if ($value instanceof DateTime) {
            return $value->format($this->format);
        } else {
            return "";
        }
    }

    /**
     * @param string $value
     *
     * @return DateTime|null
     */
    public function deserializeValue(string $value)
    {
         $dt = DateTime::createFromFormat($this->format, $value);
         if ($dt) {
             return $dt;
         }

         if (Validate::isDate($value)) {
             $dt = DateTime::createFromFormat('Y-m-d', $value);
             if ($dt) {
                 $dt->setTime(0, 0, 0);
                 return $dt;
             }
         }

         return null;
    }

    /**
     * @return static
     */
    public static function date()
    {
        static $instance = null;
        if (is_null($instance)) {
            $instance = new static('Y-m-d');
        }
        return $instance;
    }

    /**
     * @return static
     */
    public static function datetime()
    {
        static $instance = null;
        if (is_null($instance)) {
            $instance = new static('Y-m-d H:i:s');
        }
        return $instance;
    }

    /**
     * @param int $ts
     * @return DateTime
     */
    protected function fromTimestamp(int $ts): DateTime
    {
        $date = new DateTime();
        $date->setTimestamp($ts);
        return $date;
    }

    /**
     * @return FilterOperator
     */
    public function getDefaultOperator(): FilterOperator
    {
        return BetweenOperator::instance();
    }

    /**
     * @return FilterOperator[]
     */
    public function getSupportedOperators(): array
    {
        return [
            EqualsOperator::instance(),
            BetweenOperator::instance(),
        ];
    }

    /**
     * @param DateTime|null $value
     * @return DateTime|null
     */
    public function adjustStartOfInterval($value)
    {
        if ($value instanceof DateTime) {
            $value = $value->setTime(0, 0, 0);
        }
        return $value;
    }

    /**
     * @param DateTime|null $value
     * @return DateTime|null
     */
    public function adjustEndOfInverval($value)
    {
        if ($value instanceof DateTime) {
            $value = $value->setTime(23, 59, 59);
        }
        return $value;
    }

    /**
     * @param DateTime $value
     * @param bool $addQuotes
     * @return string
     */
    public function escapeForSql($value, bool $addQuotes = true): string
    {
        $escaped = $value->format('Y-m-d H:i:s');
        if ($addQuotes) {
            return "'" . $escaped . "'";
        } else {
            return $escaped;
        }
    }

    /**
     * @param DateTime $value
     * @return string
     */
    public function describeValue($value): string
    {
        return "'" . $value->format($this->format) . "'";
    }

}