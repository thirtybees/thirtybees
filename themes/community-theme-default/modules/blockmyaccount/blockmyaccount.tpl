<div class="block myaccount-column">
  <p class="title_block">
    <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}" title="{l s='My account' mod='blockmyaccount'}">
      {l s='My account' mod='blockmyaccount'}
    </a>
  </p>
  <div class="block_content list-block">
    <ul>
      <li>
        <a href="{$link->getPageLink('history', true)|escape:'html':'UTF-8'}" title="{l s='My orders' mod='blockmyaccount'}">
          {l s='My orders' mod='blockmyaccount'}
        </a>
      </li>
      {if $returnAllowed}
        <li>
          <a href="{$link->getPageLink('order-follow', true)|escape:'html':'UTF-8'}" title="{l s='My merchandise returns' mod='blockmyaccount'}">
            {l s='My merchandise returns' mod='blockmyaccount'}
          </a>
        </li>
      {/if}
      <li>
        <a href="{$link->getPageLink('order-slip', true)|escape:'html':'UTF-8'}" title="{l s='My credit slips' mod='blockmyaccount'}">
          {l s='My credit slips' mod='blockmyaccount'}
        </a>
      </li>
      <li>
        <a href="{$link->getPageLink('addresses', true)|escape:'html':'UTF-8'}" title="{l s='My addresses' mod='blockmyaccount'}">
          {l s='My addresses' mod='blockmyaccount'}
        </a>
      </li>
      <li>
        <a href="{$link->getPageLink('identity', true)|escape:'html':'UTF-8'}" title="{l s='My personal info' mod='blockmyaccount'}">
          {l s='My personal info' mod='blockmyaccount'}
        </a>
      </li>
      {if $voucherAllowed}
        <li>
          <a href="{$link->getPageLink('discount', true)|escape:'html':'UTF-8'}" title="{l s='My vouchers' mod='blockmyaccount'}">
            {l s='My vouchers' mod='blockmyaccount'}
          </a>
        </li>
      {/if}
      {$HOOK_BLOCK_MY_ACCOUNT}
    </ul>
    <div class="logout">
      <a
        class="btn btn-warning"
        href="{$link->getPageLink('index', true, NULL, "mylogout")|escape:'html':'UTF-8'}"
        title="{l s='Sign out' mod='blockmyaccount'}">
        <span>{l s='Sign out' mod='blockmyaccount'} <i class="icon icon-chevron-right"></i></span>
      </a>
    </div>
  </div>
</div>
