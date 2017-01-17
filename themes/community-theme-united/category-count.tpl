<span class="heading-counter badge">
  {if (isset($category) && $category->id == 1) OR (isset($nb_products) && $nb_products == 0)}
    {l s='There are no products in this category.'}
  {else}
    {if isset($nb_products) && $nb_products == 1}
      {l s='There is 1 product.'}
    {elseif isset($nb_products)}
      {l s='There are %d products.' sprintf=$nb_products}
    {/if}
  {/if}
</span>
