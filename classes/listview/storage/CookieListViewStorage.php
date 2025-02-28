<?php

namespace Thirtybees\Core\ListView\Storage;

use Context;
use Cookie;
use RuntimeException;
use Thirtybees\Core\Dataset\Filter\Filter;
use Thirtybees\Core\Dataset\Filter\Operator\FilterOperator;
use Thirtybees\Core\ListView\Model\ListView;

class CookieListViewStorageCore implements ListViewStorage
{
    /**
     * @param string $namespace
     * @param string $listId
     * @param array $filterFields
     * @return ListView
     */
    public function getListView(string $namespace, string $listId, array $filterFields): ListView
    {
        $cookie = Context::getContext()->cookie;

        return (new ListView($namespace, $listId))
            ->setFilters($this->getFilters($cookie, $namespace, $listId, $filterFields))
            ->setPageSize($this->getPageSize($cookie, $namespace, $listId))
            ->setOrderWay($this->getOrderWay($cookie, $namespace, $listId))
            ->setOrderBy($this->getOrderBy($cookie, $namespace, $listId));
    }

    /**
     * @param ListView $listView
     * @return ListView
     */
    public function saveListView(ListView $listView): ListView
    {
        $cookie = Context::getContext()->cookie;
        $namespace = $listView->getNamespace();
        $listId = $listView->getListId();

        $this->saveFilters($cookie, $namespace, $listId, $listView->getFilters());
        $this->savePagination($cookie, $namespace, $listId, $listView->getPageSize());
        $this->saveOrderWay($cookie, $namespace, $listId, $listView->getOrderWay());
        $this->saveOrderBy($cookie, $namespace, $listId, $listView->getOrderBy());
        return $listView;
    }

    /**
     * @param Cookie $cookie
     * @param string $namespace
     * @param string $listId
     * @param Filter[] $filters
     * @return void
     */
    protected function saveFilters(Cookie $cookie, string $namespace, string $listId, array $filters)
    {
        // delete existing filter families
        $families = [
            $namespace . $listId . 'Filter_',
            $namespace . $listId . 'AdHocFilter_',
        ];
        foreach ($families as $family) {
            foreach ($cookie->getFamily($family) as $cookieKey => $_) {
                unset($cookie->{$cookieKey});
            }
        }
        // save filters to cookie
        foreach ($filters as $filter) {
            $this->saveFilter($cookie, $namespace, $listId, $filter);
        }
    }

    /**
     * @param Cookie $cookie
     * @param string $namespace
     * @param string $listId
     * @return string|null
     */
    protected function getOrderWay(Cookie $cookie, string $namespace, string $listId): ?string
    {
        $key = $this->getOrderWayCookieKey($namespace, $listId);
        if (isset($cookie->{$key})) {
            return $cookie->{$key};
        }
        return null;
    }

    /**
     * @param Cookie $cookie
     * @param string $namespace
     * @param string $listId
     * @return string|null
     */
    protected function getOrderBy(Cookie $cookie, string $namespace, string $listId): ?string
    {
        $key = $this->getOrderByCookieKey($namespace, $listId);
        if (isset($cookie->{$key})) {
            return $cookie->{$key};
        }
        return null;
    }

    /**
     * @param Cookie $cookie
     * @param string $namespace
     * @param string $listId
     * @param Filter $filter
     * @return void
     */
    protected function saveFilter(Cookie $cookie, string $namespace, string $listId, Filter $filter): void
    {
        switch ($filter->getFilterType()) {
            case Filter::TYPE_FILTER:
                $this->saveAdhocFilter($cookie, $namespace, $listId, $filter);
                break;
            case Filter::TYPE_COLUMN:
                $this->saveColumnFilter($cookie, $namespace, $listId, $filter);
                break;
            default:
                throw new RuntimeException('Invalid filter type: ' . $filter->getFilterType());
        }
    }

    /**
     * @param Cookie $cookie
     * @param string $namespace
     * @param string $listId
     * @param string|null $value
     * @return void
     */
    protected function saveOrderBy(Cookie $cookie, string $namespace, string $listId, ?string $value)
    {
        $key = $this->getOrderByCookieKey($namespace, $listId);
        if ($value) {
            $cookie->{$key} = $value;
        } else {
            unset($cookie->{$key});
        }
    }

    /**
     * @param Cookie $cookie
     * @param string $namespace
     * @param string $listId
     * @param string|null $value
     * @return void
     */
    protected function saveOrderWay(Cookie $cookie, string $namespace, string $listId, ?string $value)
    {
        $key = $this->getOrderWayCookieKey($namespace, $listId);
        if ($value) {
            $cookie->{$key} = $value;
        } else {
            unset($cookie->{$key});
        }
    }

    /**
     * Returns selected pagination, zero if no pagination is selected
     * @param Cookie $cookie
     * @param string $namespace
     * @param string $listId
     * @return int
     */
    protected function getPageSize(Cookie $cookie, string $namespace, string $listId): int
    {
        $paginationKey = $this->getPaginationCookieKey($namespace, $listId);
        if (isset($cookie->{$paginationKey})) {
            $pagination = (int)$cookie->{$paginationKey};
            if ($pagination > 0) {
                return $pagination;
            }
        }
        return 0;
    }

    /**
     *
     */
    protected function saveAdhocFilter(Cookie $cookie, string $namespace, string $listId, Filter $filter): void
    {
        $key = $namespace . $listId . 'AdHocFilter_' . $filter->getFilterId();
        $field = $filter->getField();
        $operator = $filter->getOperator();
        $value = [
            $field->getId(),
            $operator->getId(),
            $filter->isInverted() ? 1 : 0,
            $operator->serializeOperands($field->getValueType(), $filter->getOperands())
        ];
        $cookie->{$key} = json_encode($value);
    }

    /**
     *
     */
    protected function saveColumnFilter(Cookie $cookie, string $namespace, string $listId, Filter $filter): void
    {
        $key = $namespace . $listId . 'Filter_' . $filter->getFilterId();
        $field = $filter->getField();
        $operator = $filter->getOperator();
        $serialized= $operator->serializeOperands($field->getValueType(), $filter->getOperands());
        $cookie->{$key} = $serialized;
    }

    /**
     * @param Cookie $cookie
     * @param string $namespace
     * @param string $listId
     * @param array $filterFields
     * @return Filter[]
     */
    protected function getFilters(Cookie $cookie, string $namespace, string $listId, array $filterFields): array
    {
        $filters = [];
        $family = $namespace . $listId . 'Filter_';
        foreach ($cookie->getFamily($family) as $cookieKey => $value) {
            $filterKey = substr($cookieKey, mb_strlen($family));
            $filter = $this->getColumnFilter($filterFields, $filterKey, (string)$value);
            if ($filter) {
                $filters[] = $filter;
            } else {
                unset($cookie->{$cookieKey});
            }
        }

        $family = $namespace . $listId . 'AdHocFilter_';
        foreach ($cookie->getFamily($family) as $cookieKey => $value) {
            $filterId = substr($cookieKey, mb_strlen($family));
            $filter = $this->getAdhocFilter($filterFields, $filterId, (string)$value);
            if ($filter) {
                $filters[] = $filter;
            } else {
                unset($cookie->{$cookieKey});
            }
        }
        return $filters;
    }

    /**
     * @param array $filterFields
     * @param string $filterId
     * @param string $serializedValue
     * @return Filter|null
     */
    protected function getAdhocFilter(array $filterFields, string $filterId, string $serializedValue): ?Filter
    {
        $data = json_decode($serializedValue, true);
        if (! is_array($data) || count($data) !== 4) {
            return null;
        }

        // resolve filter key
        list ($filterKey, $operatorId, $inverted, $serializedOperands) = $data;
        if (! isset($filterFields[$filterKey])) {
            return null;
        }
        $filterField = $filterFields[$filterKey];
        $valueType = $filterField->getValueType();

        // resolve operator
        $operator = static::getOperator($valueType->getSupportedOperators(), $operatorId);
        if (! $operator) {
            return null;
        }

        // deserialize operator parameters
        $operands = $operator->deserializeOperands($valueType, $serializedOperands);
        if (! $operands) {
            return null;
        }

        return new Filter(
            Filter::TYPE_FILTER,
            $filterId,
            $filterField,
            (bool)$inverted,
            $operator,
            $operands
        );
    }

    /**
     * @param array $filterFields
     * @param string $filterKey
     * @param string $serializedValue
     * @return Filter|null
     */
    protected function getColumnFilter(array $filterFields, string $filterKey, string $serializedValue): ?Filter
    {
        if (! isset($filterFields[$filterKey])) {
            return null;
        }
        $filterField = $filterFields[$filterKey];

        $valueType = $filterField->getValueType();
        $operator = $filterField->getValueType()->getDefaultOperator();

        // deserialize operator parameters
        $operands = $operator->deserializeOperands($valueType, $serializedValue);
        if (! $operands) {
            return null;
        }

        return new Filter(
            Filter::TYPE_COLUMN,
            $filterKey,
            $filterField,
            false,
            $operator,
            $operands
        );
    }

    /**
     * @param FilterOperator[] $supportedOperators
     * @param string $operatorId
     *
     * @return FilterOperator|null
     */
    protected static function getOperator(array $supportedOperators, string $operatorId): ?FilterOperator
    {
        foreach ($supportedOperators as $operator) {
            if ($operator->getId() === $operatorId) {
                return $operator;
            }
        }
        return null;
    }


    /**
     * @param string $namespace
     * @param string $listId
     * @param int $value
     * @return void
     */
    protected function savePagination(Cookie $cookie, string $namespace, string $listId, int $value)
    {
        $cookieKey = $this->getPaginationCookieKey($namespace, $listId);
        if ($value > 0) {
            $cookie->{$cookieKey} = $value;
        } else {
            unset($cookie->{$cookieKey});
        }
    }

    /**
     * @param string $namespace
     * @param string $listId
     * @return string
     */
    protected function getPaginationCookieKey(string $namespace, string $listId): string
    {
        return $namespace . $listId . '_pagination';
    }

    /**
     * @param string $namespace
     * @param string $listId
     * @return string
     */
    protected function getOrderWayCookieKey(string $namespace, string $listId): string
    {
        return $namespace . $listId . 'Orderway';
    }

    /**
     * @param string $namespace
     * @param string $listId
     * @return string
     */
    protected function getOrderByCookieKey(string $namespace, string $listId): string
    {
        return $namespace . $listId . 'Orderby';
    }

}