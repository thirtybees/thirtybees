{if $status == 'ok'}
  <div class="alert alert-success">{l s='Your order on %s is complete.' sprintf=$shop_name mod='cheque'}</div>
  <div class="box order-confirmation">
    <h3 class="page-subheading">{l s='Your check must include:' mod='cheque'}</h3>
    - {l s='Payment amount.' mod='cheque'} <span class="price"><strong>{$total_to_pay}</strong></span>
    <br />- {l s='Payable to the order of' mod='cheque'} <strong>{if $chequeName}{$chequeName}{else}___________{/if}</strong>
    <br />- {l s='Mail to' mod='cheque'} <strong>{if $chequeAddress}{$chequeAddress}{else}___________{/if}</strong>
    {if !isset($reference) && isset($id_order) && $id_order}
      <br />- {l s='Do not forget to insert your order number #%d.' sprintf=$id_order mod='cheque'}
    {else}
      <br />- {l s='Do not forget to insert your order reference %s.' sprintf=$reference mod='cheque'}
    {/if}
    <br />- {l s='An email has been sent to you with this information.' mod='cheque'}
    <br />- <strong>{l s='Your order will be sent as soon as we receive your payment.' mod='cheque'}</strong>
    <br />- {l s='For any questions or for further information, please contact our' mod='cheque'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer service department.' mod='cheque'}</a>.
  </div>
{else}
  <div class="alert alert-warning">
    {l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='cheque'}
    <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer service department.' mod='cheque'}</a>.
  </div>
{/if}
