{assign var='taxes_behavior' value=false}
{if $use_taxes && (!$priceDisplay  || $priceDisplay == 2)}
  {assign var='taxes_behavior' value=true}
{/if}

{capture name=path}{l s='Product Comparison'}{/capture}

<h1 class="page-heading">{l s='Product Comparison'}</h1>

{if $hasProduct}
  <div class="table-responsive">
    <table id="product_comparison" class="table table-hover table-bordered text-center">

      <tr>
        <td >{$HOOK_COMPARE_EXTRA_INFORMATION}</td>
        {foreach from=$products item=product}
          <td>
            <div class="clearfix">
              <button class="close" href="{$link->getPageLink('products-comparison', true)|escape:'html':'UTF-8'}" title="{l s='Remove'}" data-id-product="{$product->id}">&times;</button>
            </div>
            <div class="product-image-container">
              <a class="product_image" href="{$product->getLink()|escape:'html':'UTF-8'}" title="{$product->name|escape:'html':'UTF-8'}">
                <img class="img-responsive center-block" src="{$link->getImageLink($product->link_rewrite, $product->id_image, 'home_default')|escape:'html':'UTF-8'}">
              </a>
              <div class="product-label-container">
                {if (!$PS_CATALOG_MODE AND ((isset($product->show_price) && $product->show_price) || (isset($product->available_for_order) && $product->available_for_order)))}
                  {if isset($product->online_only) && $product->online_only}
                    <span class="product-label product-label-online">{l s='Online only'}</span>
                  {/if}
                {/if}
                {if isset($product->new) && $product->new == 1}
                  <span class="product-label product-label-new">{l s='New'}</span>
                {/if}
                {if isset($product->on_sale) && $product->on_sale && isset($product->show_price) && $product->show_price && !$PS_CATALOG_MODE}
                  <span class="product-label product-label-sale">{l s='Sale!'}</span>
                {elseif isset($product->reduction) && $product->reduction && isset($product->show_price) && $product->show_price && !$PS_CATALOG_MODE}
                  <span class="product-label product-label-discount">{l s='Reduced price!'}</span>
                {/if}
              </div>
            </div>
          </td>
        {/foreach}
      </tr>

      <tr>
        <td></td>
        {foreach from=$products item=product}
          <td>
            <h4>
              <a href="{$product->getLink()|escape:'html':'UTF-8'}" title="{$product->name|escape:'html':'UTF-8'}">
                {$product->name|escape:'html':'UTF-8'}
              </a>
            </h4>
          </td>
        {/foreach}
      </tr>

      <tr>
        <td></td>
        {foreach from=$products item=product}
          <td>
            {if isset($product->show_price) && $product->show_price && !isset($restricted_country_mode) && !$PS_CATALOG_MODE}
              <span class="price product-price">{convertPrice price=$product->getPrice($taxes_behavior)}</span>
              {hook h="displayProductPriceBlock" id_product=$product->id type="price"}
              {if isset($product->specificPrice) && $product->specificPrice}
                {if {$product->specificPrice.reduction_type == 'percentage'}}
                  <span class="old-price product-price">{displayWtPrice p=($product->getPrice(true, null, 6, null, false, false))}</span>
                  <span class="price-percent-reduction">-{$product->specificPrice.reduction*100|floatval}%</span>
                {else}
                  <span class="old-price product-price">{convertPrice price=($product->getPrice($taxes_behavior) + $product->specificPrice.reduction)}</span>
                  <span class="price-percent-reduction">-{convertPrice price=$product->specificPrice.reduction}</span>
                {/if}
                {hook h="displayProductPriceBlock" product=$product type="old_price"}
              {/if}
              {hook h="displayProductPriceBlock" product=$product type="price"}
              {if $product->on_sale}
              {elseif $product->specificPrice AND $product->specificPrice.reduction}
                <div class="product_discount">
                  <span class="special-price">{l s='Reduced price!'}</span>
                </div>
              {/if}
              {if !empty($product->unity) && $product->unit_price_ratio > 0.000000}
                {math equation="pprice / punit_price"  pprice=$product->getPrice($taxes_behavior)  punit_price=$product->unit_price_ratio assign=unit_price}
                <span class="comparison_unit_price">
                    &nbsp;{convertPrice price=$unit_price} {l s='per %s' sprintf=$product->unity|escape:'html':'UTF-8'}
                  </span>
                {hook h="displayProductPriceBlock" product=$product type="unit_price"}
              {else}
              {/if}
            {/if}
          </td>
        {/foreach}
      </tr>

      <tr>
        <td></td>
        {foreach from=$products item=product}
          <td class="td-product-description">
            {if !empty($product->description_short)}
              <div class="rte">{$product->description_short}</div>
            {/if}
          </td>
        {/foreach}
      </tr>

      <tr>
        <td></td>
        {foreach from=$products item=product}
          <td>
            {if !(($product->quantity <= 0 && !$product->available_later) OR ($product->quantity != 0 && !$product->available_now) OR !$product->available_for_order OR $PS_CATALOG_MODE)}
              <span class="availability_label">{l s='Availability:'}</span>
              <span class="availability_value label {if $product->quantity <= 0}label-warning{else}label-success{/if}">
                {if $product->quantity <= 0}
                  {if $product->allow_oosp}
                    {$product->available_later|escape:'html':'UTF-8'}
                  {else}
                    {l s='This product is no longer in stock.'}
                  {/if}
                {else}
                  {$product->available_now|escape:'html':'UTF-8'}
                {/if}
              </span>
            {/if}
            {if !$product->is_virtual}{hook h="displayProductDeliveryTime" product=$product}{/if}
            {hook h="displayProductPriceBlock" product=$product type="weight"}
          </td>
        {/foreach}
      </tr>

      <tr>
        <td></td>
        {foreach from=$products item=product}
          <td>
            {if (!$product->hasAttributes() OR (isset($add_prod_display) AND ($add_prod_display == 1))) AND $product->minimal_quantity == 1 AND $product->customizable != 2 AND !$PS_CATALOG_MODE}
              {if ($product->quantity > 0 OR $product->allow_oosp)}
                <a class="ajax_add_to_cart_button btn btn-primary" data-id-product="{$product->id}" href="{$link->getPageLink('cart', true, NULL, "qty=1&amp;id_product={$product->id}&amp;token={$static_token}&amp;add")|escape:'html':'UTF-8'}" title="{l s='Add to cart'}">
                  {l s='Add to cart'}
                </a>
              {else}
                <span class="ajax_add_to_cart_button btn btn-primary disabled">{l s='Add to cart'}</span>
              {/if}
            {/if}
            <a class="btn btn-default" href="{$product->getLink()|escape:'html':'UTF-8'}" title="{l s='View'}">{l s='View'}</a>
          </td>
        {/foreach}
      </tr>

      {if $ordered_features}

        <tr class="text-center active">
          <td class="td_empty">{l s='Features:'}</td>
          <td colspan="{$products|count}"></td>
        </tr>

        {foreach from=$ordered_features item=feature}
          <tr>
            <td class="feature-name">{$feature.name|escape:'html':'UTF-8'}</td>
            {foreach from=$products item=product}
              {assign var='product_id' value=$product->id}
              {assign var='feature_id' value=$feature.id_feature}
              <td class="comparison_infos">
                {if isset($product_features[$product_id]) && isset($product_features[$product_id][$feature_id])}
                  {$product_features[$product_id][$feature_id]|escape:'html':'UTF-8'}
                {/if}
              </td>
            {/foreach}
          </tr>
        {/foreach}
      {else}
        <tr>
          <td></td>
          <td colspan="{$products|count}" class="text-center">{l s='No features to compare'}</td>
        </tr>
      {/if}

      {$HOOK_EXTRA_PRODUCT_COMPARISON}
    </table>
  </div>
{else}
  <div class="alert alert-warning">{l s='There are no products selected for comparison.'}</div>
{/if}

<nav>
  <ul class="pager">
    <li class="previous">
      <a href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl}{else}{$base_dir}{/if}">&larr; {l s='Continue Shopping'}</a>
    </li>
  </ul>
</nav>
