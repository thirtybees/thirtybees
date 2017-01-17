{if isset($wishlists) && count($wishlists) > 1}
  <div class="wishlist">
    <a class="wishlist_button_list" tabindex="0" data-toggle="popover" data-trigger="focus" title="{l s='Wishlist' mod='blockwishlist'}" data-placement="top">
      <i class="icon icon-star-o"></i> {l s='Add to wishlist' mod='blockwishlist'}
    </a>
    <div hidden class="popover-content">
      <ul class="list-unstyled">
        {foreach name=wl from=$wishlists item=wishlist}
          <li>
            <a title="{$wishlist.name|escape:'html':'UTF-8'}" value="{$wishlist.id_wishlist}" onclick="WishlistCart('wishlist_block_list', 'add', '{$product.id_product|intval}', false, 1, '{$wishlist.id_wishlist}');">
              {l s='Add to %s' sprintf=[$wishlist.name] mod='blockwishlist'}
            </a>
          </li>
        {/foreach}
      </ul>
    </div>
  </div>
{else}
  <div class="wishlist">
    <a class="addToWishlist wishlistProd_{$product.id_product|intval}" href="#" rel="{$product.id_product|intval}" onclick="WishlistCart('wishlist_block_list', 'add', '{$product.id_product|intval}', false, 1); return false;">
      <i class="icon icon-star-o"></i> {l s="Add to Wishlist" mod='blockwishlist'}
    </a>
  </div>
{/if}
