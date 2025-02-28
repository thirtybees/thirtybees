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
    public function adjustEndOfInverval($value)
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

}