<?php

namespace Thirtybees\Core\ListView\Storage;

use Thirtybees\Core\ListView\Model\ListView;

interface ListViewStorage
{

    /**
     * @param string $namespace
     * @param string $listId
     * @param array $filterFields
     * @return ListView
     */
    public function getListView(string $namespace, string $listId, array $filterFields): ListView;

    /**
     * @param ListView $listView
     * @return ListView
     */
    public function saveListView(ListView $listView): ListView;
}