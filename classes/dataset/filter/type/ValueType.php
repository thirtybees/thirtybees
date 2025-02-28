<?php

namespace Thirtybees\Core\Dataset\Filter\Type;

use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;

interface ValueType
{
    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @param mixed $value
     * @return string
     */
    public function serializeValue($value): string;

    /**
     * @param string $value
     * @return mixed
     */
    public function deserializeValue(string $value);

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function toJavascriptValue($value);

    /**
     * @return FilterOperator
     */
    public function getDefaultOperator(): FilterOperator;

    /**
     * @return FilterOperator[]
     */
    public function getSupportedOperators(): array;

    /**
     * @param mixed $value
     * @return mixed
     */
    public function adjustStartOfInterval($value);

    /**
     * @param mixed $value
     * @return mixed
     */
    public function adjustEndOfInverval($value);

    /**
     * @param mixed $value
     * @param bool $addQuotes
     * @return string
     */
    public function escapeForSql($value, bool $addQuotes = true): string;

    /**
     * @param mixed $value
     * @return string
     */
    public function describeValue($value): string;

    /**
     * @return array
     */
    public function getExtraOptions(): array;
}