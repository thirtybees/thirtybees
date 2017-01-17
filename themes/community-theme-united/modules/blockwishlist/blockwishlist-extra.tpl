<div class="buttons_bottom_block form-group hidden-print">
  {if isset($wishlists) && count($wishlists) > 1}
    <a id="wishlist_button" tabindex="0" data-toggle="popover" data-trigger="focus" title="{l s='Wishlist' mod='blockwishlist'}" data-placement="top">
      <i class="icon icon-fw icon-star-o"></i> <b>{l s='Add to wishlist' mod='blockwishlist'}</b>
    </a>
    <div hidden id="popover-content">
      <ul class="list-unstyled">
        {foreach name=wl from=$wishlists item=wishlist}
          <li>
            <a title="{$wishlist.name|escape:'html':'UTF-8'}" value="{$wishlist.id_wishlist}" onclick="WishlistCart('wishlist_block_list', 'add', '{$id_product|intval}', $('#idCombination').val(), document.getElementById('quantity_wanted').value, '{$wishlist.id_wishlist}');">
              {l s='Add to %s' sprintf=[$wishlist.name] mod='blockwishlist'}
            </a>
          </li>
        {/foreach}
      </ul>
    </div>
  {else}
    <a id="wishlist_button_nopop" href="#" onclick="WishlistCart('wishlist_block_list', 'add', '{$id_product|intval}', $('#idCombination').val(), document.getElementById('quantity_wanted').value); return false;" rel="nofollow"  title="{l s='Add to my wishlist' mod='blockwishlist'}">
      <i class="icon icon-fw icon-star-o"></i> <b>{l s='Add to wishlist' mod='blockwishlist'}</b>
    </a>
  {/if}
</div>
