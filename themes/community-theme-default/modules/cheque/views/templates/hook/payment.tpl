<div class="row">
  <div class="col-xs-12">
    <p class="payment_module">
      <a class="cheque" href="{$link->getModuleLink('cheque', 'payment', [], true)|escape:'html':'UTF-8'}" title="{l s='Pay by check.' mod='cheque'}">
        {l s='Pay by check' mod='cheque'} <span>{l s='(order processing will be longer)' mod='cheque'}</span>
      </a>
    </p>
  </div>
</div>
