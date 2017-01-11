{capture name=path}{l s='My account'}{/capture}

<h1 class="page-heading">{l s='My account'}</h1>
{if isset($account_created)}
  <div class="alert alert-success">{l s='Your account has been created.'}</div>
{/if}

<p>{l s='Welcome to your account. Here you can manage all of your personal information and orders.'}</p>

<div id="my-account-menu" class="row">
  <div class="col-sm-6">
    <ul class="nav nav-pills nav-stacked stacked-menu">
      {if $has_customer_an_address}
        <li><a href="{$link->getPageLink('address', true)|escape:'html':'UTF-8'}" title="{l s='Add my first address'}"><i class="icon icon-building"></i> <span>{l s='Add my first address'}</span></a></li>
      {/if}
      <li><a href="{$link->getPageLink('history', true)|escape:'html':'UTF-8'}" title="{l s='Orders'}"><i class="icon icon-list-ol"></i> <span>{l s='Order history and details'}</span></a></li>
      {if $returnAllowed}
        <li><a href="{$link->getPageLink('order-follow', true)|escape:'html':'UTF-8'}" title="{l s='Merchandise returns'}"><i class="icon icon-refresh"></i> <span>{l s='My merchandise returns'}</span></a></li>
      {/if}
      <li><a href="{$link->getPageLink('order-slip', true)|escape:'html':'UTF-8'}" title="{l s='Credit slips'}"><i class="icon icon-file-o"></i> <span>{l s='My credit slips'}</span></a></li>
      <li><a href="{$link->getPageLink('addresses', true)|escape:'html':'UTF-8'}" title="{l s='Addresses'}"><i class="icon icon-fw icon-building"></i> <span>{l s='My addresses'}</span></a></li>
      <li><a href="{$link->getPageLink('identity', true)|escape:'html':'UTF-8'}" title="{l s='Information'}"><i class="icon icon-user"></i> <span>{l s='My personal information'}</span></a></li>
    </ul>
  </div>
  {if $voucherAllowed || isset($HOOK_CUSTOMER_ACCOUNT) && $HOOK_CUSTOMER_ACCOUNT !=''}
    <div class="col-sm-6">
      <ul class="nav nav-pills nav-stacked stacked-menu">
        {if $voucherAllowed}
          <li><a href="{$link->getPageLink('discount', true)|escape:'html':'UTF-8'}" title="{l s='Vouchers'}"><i class="icon icon-barcode"></i> <span>{l s='My vouchers'}</span></a></li>
        {/if}
        {$HOOK_CUSTOMER_ACCOUNT}
      </ul>
    </div>
  {/if}
</div>

<nav>
  <ul class="pager">
    <li class="previous">
      <a href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl}{else}{$base_dir}{/if}">&larr; {l s='Home'}</a>
    </li>
  </ul>
</nav>
