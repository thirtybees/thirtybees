{if $status == 'ok'}
  <div class="alert alert-success">{l s='Your order on %s is complete.' sprintf=$shop_name mod='bankwire'}</div>
  <div class="box">
    {l s='Please send us a bank wire with' mod='bankwire'}
    <br />- {l s='Amount' mod='bankwire'} <span class="price"><strong>{$total_to_pay}</strong></span>
    <br />- {l s='Name of account owner' mod='bankwire'}  <strong>{if $bankwireOwner}{$bankwireOwner}{else}___________{/if}</strong>
    <br />- {l s='Include these details' mod='bankwire'}  <strong>{if $bankwireDetails}{$bankwireDetails}{else}___________{/if}</strong>
    <br />- {l s='Bank name' mod='bankwire'}  <strong>{if $bankwireAddress}{$bankwireAddress}{else}___________{/if}</strong>
    {if !isset($reference)}
  <br />- {l s='Do not forget to insert your order number #%d in the subject of your bank wire.' sprintf=$id_order mod='bankwire'}
    {else}
  <br />- {l s='Do not forget to insert your order reference %s in the subject of your bank wire.' sprintf=$reference mod='bankwire'}
    {/if}<br />{l s='An email has been sent with this information.' mod='bankwire'}
    <br /> <strong>{l s='Your order will be sent as soon as we receive payment.' mod='bankwire'}</strong>
    <br />{l s='If you have questions, comments or concerns, please contact our' mod='bankwire'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team' mod='bankwire'}</a>.
  </div>
{else}
  <div class="alert alert-warning">
    {l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='bankwire'}
    <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer service department.' mod='bankwire'}</a>.
  </div>
{/if}
