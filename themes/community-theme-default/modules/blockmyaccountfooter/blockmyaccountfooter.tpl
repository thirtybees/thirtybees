<section id="blockmyaccountfooter" class="col-xs-12 col-sm-3">
  <h4>{l s='My account' mod='blockmyaccountfooter'}</h4>
  <ul class="list-unstyled">
    <li>
      <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}" title="{l s='Manage my customer account' mod='blockmyaccountfooter'}" rel="nofollow">
        {l s='My account' mod='blockmyaccountfooter'}
      </a>
    </li>
    <li>
      <a href="{$link->getPageLink('history', true)|escape:'html':'UTF-8'}" title="{l s='My orders' mod='blockmyaccountfooter'}" rel="nofollow">
        {l s='My orders' mod='blockmyaccountfooter'}
      </a>
    </li>
    {if $returnAllowed}
      <li>
        <a href="{$link->getPageLink('order-follow', true)|escape:'html':'UTF-8'}" title="{l s='My merchandise returns' mod='blockmyaccountfooter'}" rel="nofollow">
          {l s='My merchandise returns' mod='blockmyaccountfooter'}
        </a>
      </li>
    {/if}
    <li>
      <a href="{$link->getPageLink('order-slip', true)|escape:'html':'UTF-8'}" title="{l s='My credit slips' mod='blockmyaccountfooter'}" rel="nofollow">
        {l s='My credit slips' mod='blockmyaccountfooter'}
      </a>
    </li>
    <li>
      <a href="{$link->getPageLink('addresses', true)|escape:'html':'UTF-8'}" title="{l s='My addresses' mod='blockmyaccountfooter'}" rel="nofollow">
        {l s='My addresses' mod='blockmyaccountfooter'}
      </a>
    </li>
    <li>
      <a href="{$link->getPageLink('identity', true)|escape:'html':'UTF-8'}" title="{l s='Manage my personal information' mod='blockmyaccountfooter'}" rel="nofollow">
        {l s='My personal info' mod='blockmyaccountfooter'}
      </a>
    </li>
    {if $voucherAllowed}
      <li>
        <a href="{$link->getPageLink('discount', true)|escape:'html':'UTF-8'}" title="{l s='My vouchers' mod='blockmyaccountfooter'}" rel="nofollow">
          {l s='My vouchers' mod='blockmyaccountfooter'}
        </a>
      </li>
    {/if}
    {$HOOK_BLOCK_MY_ACCOUNT}
    {if $is_logged}
      <li>
        <a href="{$link->getPageLink('index')}?mylogout" title="{l s='Sign out' mod='blockmyaccountfooter'}" rel="nofollow">
          {l s='Sign out' mod='blockmyaccountfooter'}
        </a>
      </li>
    {/if}
  </ul>
</section>
