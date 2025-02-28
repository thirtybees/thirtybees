<?php

namespace Thirtybees\Core\Dataset\Storage;

use Thirtybees\Core\Dataset\Filter\Filter;

interface ListViewStorage
{
    /**
     * @return Filter[]
     */
    public function getFilters(): array;

    /**
     * @param Filter $filter
     * @return void
     */
    public function saveFilter(Filter $filter): void;

    /**
     * @return void
     */
    public function resetFilters(): void;

    /**
     * @return string|null
     */
    public function getOrderWay(): ?string;

    /**
     * @return string|null
     */
    public function getOrderBy(): ?string;
}