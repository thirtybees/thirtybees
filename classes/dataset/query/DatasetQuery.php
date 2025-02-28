<?php

namespace Thirtybees\Core\Dataset\Query;

interface DatasetQuery
{
    /**
     * @param string $cond
     * @return void
     */
    public function addColumnSqlFilter(string $cond): void;

    /**
     * @param string $cond
     * @return void
     */
    public function addHavingSqlFilter(string $cond): void;

    /**
     * @param string $cond
     * @return void
     */
    public function addTempTableSqlFilter(string $cond): void;

}