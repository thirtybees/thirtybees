<li id="blockcontact-contact" class="blockcontact">
  <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}" title="{l s='Contact us' mod='blockcontact'}">
    {l s='Contact us' mod='blockcontact'}
  </a>
</li>

{if !empty($telnumber)}
  <li id="blockcontact-phone" class="blockcontact">
    <p class="navbar-text">
      <i class="icon icon-phone"></i>
      {l s='Call us now:' mod='blockcontact'}
      <a class="phone-link" href="tel:{$telnumber|escape:'html':'UTF-8'}">{$telnumber|escape:'html':'UTF-8'}</a>
    </p>
  </li>
{/if}
