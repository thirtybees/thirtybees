<?php

namespace Thirtybees\Core\Dataset\Filter\Type;

use Thirtybees\Core\Dataset\Filter\Operator\AnyOfOperator;
use Thirtybees\Core\Dataset\Filter\Operator\EqualsOperator;
use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;

/**
 *
 */
class SelectValueTypeCore extends ValueTypeBase implements ValueType
{
    /**
     * @var ValueType
     */
    protected ValueType $keyValueType;

    /**
     * @var array
     */
    protected array $options = [];

    /**
     * @param ValueType $keyValueType
     * @param array $options
     */
    public function __construct(ValueType $keyValueType, array $options)
    {
        $this->keyValueType = $keyValueType;
        foreach ($options as $key => $label) {
            $typedKey = $this->keyValueType->deserializeValue($this->keyValueType->serializeValue($key));
            $this->options[$typedKey] = $label;
        }
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'select';
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function serializeValue($value): string
    {
        return $this->keyValueType->serializeValue($value);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function toJavascriptValue($value)
    {
        return $this->keyValueType->toJavascriptValue($value);
    }

    /**
     * @param string $value
     * @return int
     */
    public function deserializeValue(string $value)
    {
        return $this->keyValueType->deserializeValue($value);
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
            AnyOfOperator::instance(),
        ];
    }

    /**
     * @param mixed $value
     * @param bool $addQuotes
     * @return string
     */
    public function escapeForSql($value, bool $addQuotes = true): string
    {
        return $this->keyValueType->escapeForSql($value, $addQuotes);
    }

    /**
     * @param $value
     * @return string
     */
    public function describeValue($value): string
    {
        if (isset($this->options[$value])) {
            return "'" . $this->options[$value] . "'";
        }
        return $this->keyValueType->describeValue($value);
    }

    /**
     * @return array
     */
    public function getExtraOptions(): array
    {
        return [
            'options' => (object)$this->options,
            'keyType' => $this->keyValueType->getType(),
        ];
    }


}