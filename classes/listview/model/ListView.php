<?php

namespace Thirtybees\Core\ListView\Model;

use Thirtybees\Core\Dataset\Filter\Filter;

class ListViewCore
{
    /**
     * @var string
     */
    protected string $namespace;

    /**
     * @var string
     */
    protected string $listId;

    /**
     * @var array
     */
    protected array $filters = [];

    /**
     * @var int
     */
    private int $pageSize = 0;

    /**
     * @var string|null
     */
    private ?string $orderBy;

    /**
     * @var string|null
     */
    private ?string $orderWay;

    /**
     * @param string $namespace
     * @param string $listId
     */
    public function __construct(string $namespace, string $listId)
    {
        $this->namespace = $namespace;
        $this->listId = $listId;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getListId(): string
    {
        return $this->listId;
    }

    /**
     * @return Filter[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return array
     */
    public function getListColumns(): array
    {
        return [];
    }

    /**
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize(int $pageSize)
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @param array $filters
     * @return $this
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * @param Filter $filter
     * @return static
     */
    public function addFilter(Filter $filter)
    {
        $this->filters[$filter->getFilterId()] = $filter;
        return $this;
    }

    /**
     * @param string $filterId
     * @return $this
     */
    public function removeFilterById(string $filterId)
    {
        unset($this->filters[$filterId]);
        return $this;
    }

    /**
     * @return $this
     */
    public function resetFilters()
    {
        $this->filters = [];
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrderBy(): ?string
    {
        return $this->orderBy;
    }

    /**
     * @param string|null $orderBy
     * @return $this
     */
    public function setOrderBy(?string $orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrderWay(): ?string
    {
        return $this->orderWay;
    }

    /**
     * @param string|null $orderWay
     * @return $this
     */
    public function setOrderWay(?string $orderWay)
    {
        $this->orderWay = $orderWay;
        return $this;
    }

}
