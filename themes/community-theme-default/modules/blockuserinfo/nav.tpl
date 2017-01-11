{if $is_logged}
  <li id="blockuserinfo-customer" class="blockuserinfo">
    <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}" title="{l s='View my customer account' mod='blockuserinfo'}" rel="nofollow">
      <span>{$cookie->customer_firstname} {$cookie->customer_lastname}</span>
    </a>
  </li>
{/if}

{if $is_logged}
  <li id="blockuserinfo-logout" class="blockuserinfo">
    <a class="logout" href="{$link->getPageLink('index', true, NULL, "mylogout")|escape:'html':'UTF-8'}" rel="nofollow" title="{l s='Log me out' mod='blockuserinfo'}">
      {l s='Sign out' mod='blockuserinfo'}
    </a>
  </li>
{else}
  <li id="blockuserinfo-login" class="blockuserinfo">
    <a class="login" href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}" rel="nofollow" title="{l s='Log in to your customer account' mod='blockuserinfo'}">
      {l s='Sign in' mod='blockuserinfo'}
    </a>
  </li>
{/if}
