<div class="form-group"><label>{l s='Spend history'}</label>

{if !$history}
    <div class="alert alert-info">{l s='This credit has not been spent on any order yet.'}</div>
{else}
    <table class="table" style="max-width:680px;">
        <thead>
            <tr>
                <th>{l s='Date'}</th>
                <th>{l s='Order'}</th>
                <th class="text-right">{l s='Amount'}</th>
                <th>{l s='Status'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach $history as $spend}
                <tr>
                    <td>{$spend.date_add|escape}</td>
                    <td>
                        <a href="{$spend.link}">{$spend.reference|escape:'htmlall'}</a>
                    </td>
                    <td class="text-right">
                        {displayPrice price=$spend.amount currency=$currencyId}
                    </td>
                    <td>
                        {if $spend.date_reverted}
                            <span class="label label-warning">{l s='Reverted '} {$spend.date_reverted}</span>
                        {else}
                            <span class="label label-success">{l s='Spent'}</span>
                        {/if}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
{/if}
</div>