{capture name=path}{l s='Shipping' mod='cashondelivery'}{/capture}

<h1 class="page-heading">{l s='Order summary' mod='cashondelivery'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<form action="{$link->getModuleLink('cashondelivery', 'validation', [], true)|escape:'html'}" method="post">
  <div class="box">
    <input type="hidden" name="confirm" value="1" />
    <h3 class="page-subheading">{l s='Cash on delivery (COD) payment' mod='cashondelivery'}</h3>
    <p>
      - {l s='You have chosen the Cash on Delivery method.' mod='cashondelivery'}
      <br/>
      - {l s='The total amount of your order is' mod='cashondelivery'}
      <span id="amount_{$currencies[0].id_currency}" class="price">{convertPrice price=$total}</span>
      {if $use_taxes == 1}
        {l s='(tax incl.)' mod='cashondelivery'}
      {/if}
    </p>
    <p>
      <b>{l s='Please confirm your order by clicking \'I confirm my order\'.' mod='cashondelivery'}.</b>
    </p>
  </div>
  <p class="cart_navigation" id="cart_navigation">
    <a href="{$link->getPageLink('order', true)}?step=3" class="btn btn-lg btn-default"><i class="icon icon-chevron-left"></i> {l s='Other payment methods' mod='cashondelivery'}</a>
    <button type="submit" class="btn btn-lg btn-success pull-right"><span>{l s='I confirm my order' mod='cashondelivery'}</span></button>
  </p>
</form>
