{extends file="helpers/list/list_content.tpl"}

{block name="td_content"}
    {if $params.type == 'fx_service'}
        <div>
            {assign var=services value=CurrencyRateModule::getServices($tr.id_currency, $tr.$key)}
            {if is_array($services) && count($services)}
                <select id="fx_service_{$tr['id_currency']|intval}" name="{$key|escape:'htmlall':'UTF-8'}">
                    {foreach $services as $service}
                        <option value="{$service.id_module|intval}"{if $service.selected} selected="selected"{/if}>{$service.display_name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <script type="text/javascript">
                    $(document).ready(function() {
                        $('#fx_service_{$tr['id_currency']|intval}').change(function () {
                            console.log($(this));
                            $.ajax({
                                url: 'index.php?controller=AdminCurrencies&token=' + window.token,
                                dataType: 'JSON',
                                type: 'POST',
                                data: {
                                    ajax: true,
                                    action: 'updateFxService',
                                    idModule: parseInt($(this).val(), 10),
                                    idCurrency: {$tr['id_currency']|intval}
                                },
                                success: function (response) {
                                    if (response.success) {
                                        showSuccessMessage('{l s='Successfully changed' js=1}');
                                    } else {
                                        showErrorMessage('{l s='Could not change the fx service' js=1}');
                                    }
                                },
                                error: function () {
                                    showErrorMessage('{l s='Could not change the fx service' js=1}');
                                }
                            });
                        });
                    });
                </script>
                {else}
                ---
            {/if}
        </div>
        <div class="show-xs show-sm hidden-md hidden-lg hidden-xl">
            {$tr.$key|escape:'htmlall':'UTF-8'}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
