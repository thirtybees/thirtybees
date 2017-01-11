<div class="box">
  <p>{l s='Your order on' mod='cashondelivery'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='cashondelivery'}
    <br />
    {l s='You have chosen the cash on delivery method.' mod='cashondelivery'}
    <br /><span class="bold">{l s='Your order will be sent very soon.' mod='cashondelivery'}</span>
    <br />{l s='For any questions or for further information, please contact our' mod='cashondelivery'} <a href="{$link->getPageLink('contact-form', true)|escape:'html'}">{l s='customer support' mod='cashondelivery'}</a>.
  </p>
</div>
