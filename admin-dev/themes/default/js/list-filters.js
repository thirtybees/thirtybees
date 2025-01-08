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

function renderListFilters(listId, filters)
{
    for (let i = 0; i<filters.length; i++) {
        const filter = filters[i];
        newListFilter(listId, filter.field, filter.operator, filter.inverted, filter.args);
    }
}

function resolveFilterNextId(listId)
{
    const $container = $("#list-filters-" + listId).find('.filters-container');
    let lastId = parseInt($container.find('.list-filter').last().data('filterId'), 10);
    if (isNaN(lastId)) {
        lastId = 0;
    }
    return lastId + 1;
}

function newListFilter(listId, selectedFieldId = '', selectedOperatorId = '', inverted = false, args = [])
{
    const $container = $("#list-filters-" + listId).find('.filters-container');
    const availableFields = window['filterFields' + listId];
    if (! availableFields) {
        return;
    }

    let id = resolveFilterNextId(listId);
    const prefix = 'listFilter['+listId+']['+id+']';

    $('#no-filters-placeholder').hide();

    const selectedField = findFilterField(availableFields, selectedFieldId);

    $container.append($(
        '<div data-filter-id="' + id + '" class="list-filter row" style="padding: 0.5em 0 0.5em 0; margin: 0.5em 0 0.5em 0; background: #eee">'+
        '   <div class="col-lg-2">' +
        '       '+getFilterFiledSelect(prefix, availableFields, selectedField) +
        '   </div>' +
        '   <div class="col-lg-1">' +
        '      <select name="'+prefix+'[inverted]">' +
        '         <option value="0" '+ (inverted ? '' : 'selected') +'>-</option>' +
        '         <option value="1" '+ (inverted ? 'selected' : '') +'>'+window.filterTranslations.not+'</option>' +
        '      </select>' +
        '   </div>' +
        '   <div class="col-lg-8 content"></div>' +
        '   <div class="col-lg-1">' +
        '      <a class="btn btn-default pull-right delete"><i class="icon-trash"></i> '+window.filterTranslations.delete+'</a>' +
        '   </div>' +
        '</div>'
    ));

    const $filter = $container.children().last();

    $filter
        .find('.delete')
        .on('click', () => {
            $filter.remove();
            if (resolveFilterNextId(listId) === 1) {
                $('#no-filters-placeholder').show();
            }
        });

    $filter.find('.select-field')
        .on('change', function() {
            let selectedOperatorId = $filter.find('.select-operator').val();
            let args = [];
            $filter.find('.argument input').each(function(index, element) {
                args.push($(element).val());
            });
            updateListFilter(
                prefix,
                $filter,
                availableFields,
                findFilterField(availableFields, $(this).val()),
                selectedOperatorId,
                args
            );
        });

    updateListFilter(prefix, $filter, availableFields, selectedField, selectedOperatorId, args)
}

function updateListFilter(prefix, $filter, availableFields, selectedField, selectedOperatorId, args)
{
    const fieldOperators = selectedField.operators;
    const selectedOperator = findFilterFieldOperator(fieldOperators, selectedOperatorId);
    $filter.find('.deletable').remove();
    let $content = $filter.find('.content');

    $content.append(getFilterOperatorSelect(fieldOperators, selectedOperatorId, prefix));
    for (let i = 0; i < selectedOperator.operands; i++) {
        let value = '';
        if (i < args.length) {
            value = args[i];
        }
        $content.append(getFilterArgument(value, prefix, selectedField));
    }
    $content.find('.select-operator')
        .on('change', function() {
            let selectedOperatorId = $(this).val();
            let args = [];
            $filter.find('.argument input').each(function(index, element) {
                args.push($(element).val());
            });
            updateListFilter(
                prefix,
                $filter,
                availableFields,
                selectedField,
                selectedOperatorId,
                args
            );
        });
}

function getFilterFiledSelect(prefix, availableFields, selectedField)
{
    let select = '<select name="'+prefix+'[field]" class="select-field form-control">';
    for (let i = 0; i<availableFields.length; i++) {
        const field = availableFields[i];
        const id = field['id'];
        select += '<option value=\'' + id + '\'' + (selectedField.id === id ? 'selected' : '') + '>'+field.name+'</option>';
    }
    select += '</select>';
    return select;
}

function findFilterFieldOperator(availableOperators, selectedId)
{
    for (let i = 0; i<availableOperators.length; i++) {
        const operatorId = availableOperators[i];
        if (selectedId === operatorId) {
            return filterOperators[operatorId];
        }
    }
    return filterOperators[availableOperators[0]];
}

function getFilterOperatorSelect(availableOperators, selectedOperatorId, prefix)
{
    let select = '<select name="'+prefix+'[operator]" class="select-operator form-control">';
    for (let i = 0; i < availableOperators.length; i++) {
        const id = availableOperators[i];
        const operator = filterOperators[id];
        select += '<option value=\'' + id + '\'' + (id === selectedOperatorId ? 'selected' : '') + '>' + operator.name + '</option>';
    }
    select += '</select>';
    return '<div class="deletable col-lg-3">'+ select +'</div>';
}

function getFilterArgument(value, prefix, selectedField)
{
    const type = selectedField.type;
    let input;
    switch (type) {
        case 'int':
            input = '<input name="'+prefix+'[args][]" type="text" placeholder="" value="'+value+'" />';
            break;
        case 'select':
            input = '<select name="'+prefix+'[args][]" class="form-control">';
            for (let key in selectedField.extra.options) {
                const optionValue = selectedField.extra.options[key];
                input += '<option value=\'' + key + '\'' + (key == value ? 'selected' : '') + '>' + optionValue + '</option>';
            }
            input += '</select>';
            break;
        default:
            input = '<input name="'+prefix+'[args][]" type="text" placeholder="" value="'+value+'" />';
            break;
    }
    return '<div class="deletable col-lg-3">'+ input +'</div>';
}

function findFilterField(availableFields, id)
{
    for (let i = 0; i<availableFields.length; i++) {
        if (availableFields[i].id === id) {
            return availableFields[i];
        }
    }
    return availableFields[0];
}