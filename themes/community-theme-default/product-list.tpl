{if !empty($products)}

  {if $page_name == 'index' || $page_name == 'product'}
    {$product_block_size_class = 'col-xs-12 col-sm-4 col-md-3'}
  {else}
    {$product_block_size_class = 'col-xs-12 col-sm-6 col-md-4'}
  {/if}

  {$show_functional_buttons = $page_name != 'index'}

  <ul{if !empty($id)} id="{$id}"{/if} class="product_list grid list-grid row{if !empty($class)} {$class}{/if}">

    {* IMPORTANT! There must be no spaces betweem </li><li> tags! *}
    {foreach from=$products item=product}<li class="ajax_block_product {$product_block_size_class}">
      {include file='./product-list-item.tpl' product=$product}
    </li>{/foreach}
    {* IMPORTANT! There must be no spaces betweem </li><li> tags! *}

  </ul>

  {addJsDefL name=min_item}{l s='Please select at least one product' js=1}{/addJsDefL}
  {addJsDefL name=max_item}{l s='You cannot add more than %d product(s) to the product comparison' sprintf=$comparator_max_item js=1}{/addJsDefL}
  {addJsDef comparator_max_item=$comparator_max_item}
  {addJsDef comparedProductsIds=$compared_products}

{/if}
