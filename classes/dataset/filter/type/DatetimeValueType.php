<?php

namespace Thirtybees\Core\Dataset\Filter\Type;

use DateTime;
use Thirtybees\Core\Dataset\Filter\Operator\AnyOfOperator;
use Thirtybees\Core\Dataset\Filter\Operator\BetweenOperator;
use Thirtybees\Core\Dataset\Filter\Operator\EqualsOperator;
use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;
use Thirtybees\Core\Dataset\Filter\Operator\LessThanOperator;
use Thirtybees\Core\Dataset\Filter\Operator\MoreThanOperator;
use Validate;

/**
 *
 */
class DatetimeValueTypeCore extends ValueTypeBase implements ValueType
{
    const TYPE_DATETIME = 'datetime';
    const TYPE_DATE = 'date';

    /**
     * @var string
     */
    protected string $type;

    /**
     * @param string $type
     */
    protected function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
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
            return $value->format($this->getFormat());
        } else {
            return "";
        }
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function toJavascriptValue($value)
    {
        return $this->serializeValue($value);
    }

    /**
     * @param string $value
     *
     * @return DateTime
     */
    public function deserializeValue(string $value)
    {
         $dt = DateTime::createFromFormat($this->getFormat(), $value);
         if ($dt) {
             if ($this->type === self::TYPE_DATE) {
                 $dt->setTime(0, 0, 0);
             }
             return $dt;
         }

         if (Validate::isDate($value)) {
             $dt = DateTime::createFromFormat('Y-m-d', $value);
             if ($dt) {
                 $dt->setTime(0, 0, 0);
                 return $dt;
             }
         }

        return DateTime::createFromFormat('Y-m-d H:i:s', "1900-01-01 00:00:00");
    }

    /**
     * @return static
     */
    public static function date()
    {
        static $dateInstance = null;
        if (is_null($dateInstance)) {
            $dateInstance = new static(static::TYPE_DATE);
        }
        return $dateInstance;
    }

    /**
     * @return static
     */
    public static function datetime()
    {
        static $datetimeInstance = null;
        if (is_null($datetimeInstance)) {
            $datetimeInstance = new static(static::TYPE_DATETIME);
        }
        return $datetimeInstance;
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
            LessThanOperator::instance(),
            MoreThanOperator::instance(),
            AnyOfOperator::instance(),
        ];
    }

    /**
     * @param DateTime|null $value
     * @return DateTime|null
     */
    public function adjustStartOfInterval($value)
    {
        if ($this->type === self::TYPE_DATE) {
            if ($value instanceof DateTime) {
                $value = $value->setTime(0, 0, 0);
            }
        }
        return $value;
    }

    /**
     * @param DateTime|null $value
     * @return DateTime|null
     */
    public function adjustEndOfInverval($value)
    {
        if ($this->type === self::TYPE_DATE) {
            if ($value instanceof DateTime) {
                $value = $value->setTime(23, 59, 59);
            }
        }
        return $value;
    }

    /**
     * @param DateTime|null $value
     * @param bool $addQuotes
     * @return string
     */
    public function escapeForSql($value, bool $addQuotes = true): string
    {
        if ($value instanceof DateTime) {
            $formatted = $value->format('Y-m-d H:i:s');
        } else {
            $formatted = '';
        }
        if ($addQuotes) {
            return "'" . $formatted . "'";
        } else {
            return $formatted;
        }
    }

    /**
     * @param DateTime $value
     * @return string
     */
    public function describeValue($value): string
    {
        if ($value instanceof DateTime) {
            return "'" . $value->format($this->getFormat()) . "'";
        } else {
            return "";
        }
    }

    /**
     * @return string
     */
    protected function getFormat(): string
    {
        return $this->type === static::TYPE_DATETIME
            ? 'Y-m-d H:i:s'
            : 'Y-m-d';
    }

}