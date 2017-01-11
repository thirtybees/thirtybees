{capture name=path}
  <a href="{$link->getPageLink('my-account', true)|escape:'html'}" title="{l s='Manage my account' mod='mailalerts'}" rel="nofollow">{l s='My account' mod='mailalerts'}</a>
  <span class="navigation-pipe">{$navigationPipe}</span>
  <span class="navigation_page">{l s='My alerts' mod='mailalerts'}</span>
{/capture}

<div id="mailalerts_block_account" class="block">
  <h1 class="page-heading">{l s='My alerts' mod='mailalerts'}</h1>
  {if $mailAlerts}
    <div class="products-block">
      <ul>
        {foreach from=$mailAlerts item=mailAlert name=myLoop}
          <li class="clearfix">
            <a class="products-block-image" href="{$link->getProductLink($mailAlert.id_product, null, null, null, null, $mailAlert.id_shop)}" title="{$mailAlert.name|escape:'html':'UTF-8'}"><img src="{$link->getImageLink($mailAlert.link_rewrite, $mailAlert.cover, 'small_default')|escape:'html'}" alt=""/></a>
            <div class="product-content">
            <span class="remove">
              <i class="icon icon-remove" rel="ajax_id_mailalert_{$mailAlert.id_product}_{$mailAlert.id_product_attribute}"></i>
            </span>
              <h5><a class="product-name" href="{$link->getProductLink($mailAlert.id_product, null, null, null, null, $mailAlert.id_shop)|escape:'html'}" title="{$mailAlert.name|escape:'html':'UTF-8'}">{$mailAlert.name|escape:'html':'UTF-8'}</a></h5>
              <div class="product-description"><small>{$mailAlert.attributes_small|escape:'html':'UTF-8'}</small></div>
            </div>
          </li>
        {/foreach}
      </ul>
    </div>
  {/if}
  <div id="mailalerts_block_account_warning" class="{if $mailAlerts}hidden{/if} alert alert-warning">{l s='No mail alerts yet.' mod='mailalerts'}</div>
</div>

<nav>
  <ul class="pager">
    <li class="previous">
      <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">&larr; {l s='Back to your account' mod='mailalerts'}</a>
    </li>
  </ul>
</nav>

{addJsDef mailalerts_url_remove=$link->getModuleLink('mailalerts', 'actions', ['process' => 'remove'])|escape:'quotes':'UTF-8'}
