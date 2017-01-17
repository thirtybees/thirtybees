<div id="contact_block" class="block">
  <h4 class="title_block">
    {l s='Contact Us' mod='blockcontact'}
  </h4>
  <div class="block_content clearfix">
    <p>
      {l s='Our support hotline is available 24/7.' mod='blockcontact'}
    </p>
    {if $telnumber != ''}
      <p class="tel">
        <span class="label">{l s='Phone:' mod='blockcontact'}</span>{$telnumber|escape:'html':'UTF-8'}
      </p>
    {/if}
    {if $email != ''}
      <a href="mailto:{$email|escape:'html':'UTF-8'}" title="{l s='Contact our expert support team!' mod='blockcontact'}">
        {l s='Contact our expert support team!' mod='blockcontact'}
      </a>
    {/if}
  </div>
</div>
