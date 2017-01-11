{if isset($orderProducts) && count($orderProducts) > 0}
  <div class="crossseling-content">
    <h3>{l s='Customers who bought this product also bought:' mod='blockcart'}</h3>
    <div id="blockcart_list" class="row">
      {foreach from=$orderProducts item='orderProduct' name=orderProduct}
        <div class="col-xs-6 col-sm-4 col-md-3">
          <div class="thumbnail">
            <a href="{$orderProduct.link|escape:'html':'UTF-8'}" title="{$orderProduct.name|htmlspecialchars}" class="lnk_img">
              <img class="img-responsive" src="{$orderProduct.image}" alt="{$orderProduct.name|htmlspecialchars}" />
            </a>
          </div>
          <h4 class="product-name">
            <a href="{$orderProduct.link|escape:'html':'UTF-8'}" title="{$orderProduct.name|htmlspecialchars}">
              {$orderProduct.name|truncate:18:'...'|escape:'html':'UTF-8'}
            </a>
          </h4>
          {if $orderProduct.show_price == 1 AND !isset($restricted_country_mode) AND !$PS_CATALOG_MODE}
            <span class="price_display">
              <span class="price">{convertPrice price=$orderProduct.displayed_price}</span>
            </span>
          {/if}
        </div>
      {/foreach}
    </div>
  </div>
{/if}
