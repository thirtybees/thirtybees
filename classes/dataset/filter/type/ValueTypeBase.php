<?php

namespace Thirtybees\Core\Dataset\Filter\Type;


abstract class ValueTypeBaseCore implements ValueType
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function adjustStartOfInterval($value)
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function adjustEndOfInterval($value)
    {
        return $value;
    }

    /**
     * @return array
     */
    public function getExtraOptions(): array
    {
        return [];
    }

    /**
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return true;
    }

}