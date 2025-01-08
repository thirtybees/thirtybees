function toggleFilters(listId) {
    const $filters = $("#list-filters-" + listId);
    const $filterIcon = $("#toolbar-filter-icon-" + listId);

    if ($filters.is(':hidden')) {
        $filters.show();
        $filterIcon
            .removeClass('icon-caret-down')
            .addClass('icon-caret-up');
    } else {
        $filters.hide();
        $filterIcon
            .removeClass('icon-caret-up')
            .addClass('icon-caret-down');
    }
    return false
}

function renderListFilters(listId)
{
}

function newListFilter(listId)
{
    const $container = $("#list-filters-" + listId).find('.filters-container');
    const availableFields = window['filterFields' + listId];
    if (! availableFields) {
        return;
    }
    console.log(availableFields);
}