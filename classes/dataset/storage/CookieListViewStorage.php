<?php

namespace Thirtybees\Core\Dataset\Storage;

use Cookie;
use Thirtybees\Core\Dataset\Filter\Filter;
use Thirtybees\Core\Dataset\Filter\FilterField;
use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;

class CookieListViewStorageCore implements ListViewStorage
{
    /**
     * @var Cookie
     */
    protected Cookie $cookie;

    /**
     * @var string
     */
    protected string $listId;

    /**
     * @var string
     */
    protected string $cookiePrefix;

    /**
     * @var array<string, FilterField>
     */
    protected array $filterFields;

    /**
     * @param Cookie $cookie
     * @param string $cookiePrefix
     * @param string $listId
     * @param array $filterFields
     */
    public function __construct(Cookie $cookie, string $cookiePrefix, string $listId, array $filterFields)
    {
        $this->cookie = $cookie;
        $this->cookiePrefix = $cookiePrefix;
        $this->listId = $listId;
        $this->filterFields = $filterFields;
    }

    /**
     * @return void
     */
    public function resetFilters(): void
    {
        $families = [
            $this->cookiePrefix . $this->listId . 'Filter_',
            $this->cookiePrefix . $this->listId . 'AdHocFilter_',
            $this->cookiePrefix . $this->listId . 'Orderby',
            $this->cookiePrefix . $this->listId . 'Orderway',
            'submitFilter' . $this->listId,
        ];
        foreach ($families as $family) {
            foreach ($this->cookie->getFamily($family) as $cookieKey => $_) {
                unset($this->cookie->{$cookieKey});
            }
        }
    }

    /**
     * @return string|null
     */
    public function getOrderWay(): ?string
    {
        $key = $this->cookiePrefix . $this->listId . 'Orderway';
        if (isset($this->cookie->{$key})) {
            return $this->cookie->{$key};
        }
        return null;
    }

    /**
     * @return string|null
     */
    public function getOrderBy(): ?string
    {
        $key = $this->cookiePrefix . $this->listId . 'Orderby';
        if (isset($this->cookie->{$key})) {
            return $this->cookie->{$key};
        }
        return null;
    }

    /**
     * @param Filter $filter
     * @return void
     */
    public function saveFilter(Filter $filter): void
    {
        // TODO: Implement saveFilter() method.
    }

    /**
     * @return array|Filter[]
     */
    public function getFilters(): array
    {
        $filters = [];
        $family = $this->cookiePrefix . $this->listId . 'Filter_';
        foreach ($this->cookie->getFamily($family) as $cookieKey => $value) {
            $filterKey = substr($cookieKey, mb_strlen($family));
            $filter = $this->getColumnFilter($filterKey, (string)$value);
            if ($filter) {
                $filters[] = $filter;
            } else {
                unset($this->cookie->{$cookieKey});
            }
        }

        $family = $this->cookiePrefix . $this->listId . 'AdHocFilter_';
        foreach ($this->cookie->getFamily($family) as $cookieKey => $value) {
            $filterId = substr($cookieKey, mb_strlen($family));
            $filter = $this->getAdhocFilter($filterId, (string)$value);
            if ($filter) {
                $filters[] = $filter;
            } else {
                unset($this->cookie->{$cookieKey});
            }
        }

        return $filters;
    }

    /**
     * @param string $filterId
     * @param string $serializedValue
     * @return Filter|null
     */
    protected function getAdhocFilter(string $filterId, string $serializedValue): ?Filter
    {
        $data = json_decode($serializedValue, true);
        if (! is_array($data) || count($data) !== 3) {
            return null;
        }

        // resolve filter key
        $filterKey = $data[0];
        $serializedOperands = $data[2];
        if (! isset($this->filterFields[$filterKey])) {
            return null;
        }
        $filterField = $this->filterFields[$filterKey];
        $valueType = $filterField->getValueType();

        // resolve operator
        $operatorId = $data[1];
        $operator = $this->getOperator($valueType->getSupportedOperators(), $operatorId);
        if (! $operator) {
            return null;
        }

        // deserialize operator parameters
        $operands = $operator->deserializeOperands($valueType, $serializedOperands);
        if (! $operands) {
            return null;
        }

        return new Filter(Filter::TYPE_FILTER, $filterId, $filterField, $operator, $operands);
    }

    /**
     * @param string $filterKey
     * @param string $serializedValue
     * @return Filter|null
     */
    protected function getColumnFilter(string $filterKey, string $serializedValue): ?Filter
    {
        if (! isset($this->filterFields[$filterKey])) {
            return null;
        }
        $filterField = $this->filterFields[$filterKey];

        $valueType = $filterField->getValueType();
        $operator = $filterField->getValueType()->getDefaultOperator();

        // deserialize operator parameters
        $operands = $operator->deserializeOperands($valueType, $serializedValue);
        if (! $operands) {
            return null;
        }

        return new Filter(Filter::TYPE_COLUMN, $filterKey, $filterField, $operator, $operands);
    }

    /**
     * @param FilterOperator[] $supportedOperators
     * @param string $operatorId
     *
     * @return FilterOperator|null
     */
    protected function getOperator(array $supportedOperators, string $operatorId): ?FilterOperator
    {
        foreach ($supportedOperators as $operator) {
            if ($operator->getId() === $operatorId) {
                return $operator;
            }
        }
        return null;
    }

}