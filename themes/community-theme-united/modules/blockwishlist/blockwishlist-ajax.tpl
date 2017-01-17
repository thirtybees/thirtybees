{if $products}
  <dl class="products">
    {foreach from=$products item=product name=i}
      <dt>
        <span class="quantity-formated">
          <span class="quantity">{$product.quantity|intval}</span>x
        </span>
        <a class="cart_block_product_name" href="{$link->getProductLink($product.id_product, $product.link_rewrite, $product.category_rewrite)|escape:'html':'UTF-8'}" title="{$product.name|escape:'html':'UTF-8'}">
          {$product.name|truncate:13:'...'|escape:'html':'UTF-8'}
        </a>
        <a class="ajax_cart_block_remove_link" href="javascript:;" onclick="javascript:WishlistCart('wishlist_block_list', 'delete', '{$product.id_product}', {$product.id_product_attribute}, '0');" rel="nofollow" title="{l s='remove this product from my wishlist' mod='blockwishlist'}">
          <i class="icon icon-remove-sign"></i>
        </a>
      </dt>
      {if isset($product.attributes_small)}
        <dd>
          <a href="{$link->getProductLink($product.id_product, $product.link_rewrite)|escape:'html':'UTF-8'}" title="{l s='Product detail' mod='blockwishlist'}">
            {$product.attributes_small|escape:'html':'UTF-8'}
          </a>
        </dd>
      {/if}
    {/foreach}
  </dl>
{else}
  <dl class="products no-products">
    {if isset($error) && $error}
      <dt>{l s='You must create a wishlist before adding products' mod='blockwishlist'}</dt>
    {else}
      <dt>{l s='No products' mod='blockwishlist'}</dt>
    {/if}
  </dl>
{/if}
