<?php

namespace Thirtybees\Core\Dataset\Filter\Type;

use Thirtybees\Core\Dataset\Filter\Operator\EqualsOperator;
use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;
use Translate;

class BoolValueTypeCore extends ValueTypeBase implements ValueType
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return 'bool';
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
     * @param mixed $value
     *
     * @return string
     */
    public function serializeValue($value): string
    {
        if (is_string($value)) {
            $value = $this->deserializeValue((string)$value) ;
        } else {
            $value = (bool)$value;
        }
        return $value ? 'true' : 'false';
    }

    /**
     * @param bool $value
     * @return bool
     */
    public function toJavascriptValue($value)
    {
        if (is_string($value)) {
            $value = $this->deserializeValue((string)$value) ;
        } else {
            $value = (bool)$value;
        }
        return $value;
    }


    /**
     * @param string $value
     * @return bool
     */
    public function deserializeValue(string $value)
    {
        $value = strtolower($value);
        if ($value === 'true' || $value === 'on' || $value === '1') {
            return true;
        }
        if ((int)$value) {
            return true;
        }
        return false;
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
            EqualsOperator::instance()
        ];
    }

    /**
     * @param bool $value
     * @param bool $addQuotes
     * @return string
     */
    public function escapeForSql($value, bool $addQuotes = true): string
    {
        return $value ? '1' : '0';
    }

    /**
     * @param bool $value
     * @return string
     */
    public function describeValue($value): string
    {
        return $value
            ? Translate::getAdminTranslation('Yes')
            : Translate::getAdminTranslation('No');
    }

}