{if !$isCustomerFavoriteProduct AND $is_logged}
  <li id="favoriteproducts_block_extra_add" class="add">
    <i class="icon icon-fw icon-star-o"></i> {l s='Add this product to my list of favorites.' mod='favoriteproducts'}
  </li>
{/if}

{if $isCustomerFavoriteProduct AND $is_logged}
  <li id="favoriteproducts_block_extra_remove">
    <i class="icon icon-fw icon-star"></i> {l s='Remove this product from my favorite\'s list. ' mod='favoriteproducts'}
  </li>
{/if}

<li id="favoriteproducts_block_extra_added">
  <i class="icon icon-fw icon-star"></i> {l s='Remove this product from my favorite\'s list. ' mod='favoriteproducts'}
</li>
<li id="favoriteproducts_block_extra_removed">
  <i class="icon icon-fw icon-star-o"></i> {l s='Add this product to my list of favorites.' mod='favoriteproducts'}
</li>
