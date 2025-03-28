<div class="panel">
    <h3>{l s='Priority management'}</h3>
    <div class="alert alert-info">
        {l s='Sometimes one customer can fit into multiple price rules. Priorities allow you to define which rule applies to the customer.'}
    </div>

    <div class="form-group">
        <div class="input-group col-lg-12">
            {foreach $priorities as $id => $priority}
                <select id="specific-price-priority-{$id+1}" class="specific-price-priority" name="specificPricePriority[]">
                    {foreach $priorityOptions as $priorityOptionValue => $priorityOptionLabel}
                        <option value="{$priorityOptionValue}" {if $priority === $priorityOptionValue}selected="selected"{/if}>{$priorityOptionLabel|escape:'html':'UTF-8'}</option>
                    {/foreach}
                </select>
                {if !$priority@last}<span class="input-group-addon"><i class="icon-chevron-right"></i></span>{/if}
            {/foreach}
        </div>
    </div>
    <div class="form-group">
        <div class="col-lg-12">
            <p class="checkbox">
                <label for="specificPricePriorityToAll"><input type="checkbox" name="specificPricePriorityToAll" id="specificPricePriorityToAll" />{l s='Apply to all products'}</label>
            </p>
        </div>
    </div>
    <div class="panel-footer">
        <a href="{$cancelUrl}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
        <button id="product_form_submit_btn"  type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save'}</button>
        <button id="product_form_submit_btn"  type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay'}</button>
    </div>
</div>

<script>
    $('.specific-price-priority').on('change', function() {
        const $current = $(this);
        const currentId = $current.attr('id');
        const currentVal = $current.find('option:selected').val();
        const allValues = {array_keys($priorityOptions)|json_encode};
        const values = [];
        $('.specific-price-priority option:selected').map(function() {
            values.push($(this).val());
        });
        const missing = allValues.filter(x => !values.includes(x));
        if (missing.length === 1) {
            const missingItem = missing[0];

            $('.specific-price-priority').each(function () {
                const $this = $(this);
                const id = $this.attr('id');
                const value = $this.find('option:selected').val();
                if (value === currentVal && id !== currentId) {
                    $this.val(missingItem);
                }
            });
        }
    });
</script>
