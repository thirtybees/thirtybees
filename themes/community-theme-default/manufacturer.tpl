{include file="$tpl_dir./errors.tpl"}

{if empty($errors)}
  <section>
    <h1 class="page-heading">{$manufacturer->name|escape:'html':'UTF-8'}</h1>
    {if !empty($manufacturer->description)}
      <div class="rte">{$manufacturer->description}</div>
    {elseif !empty($manufacturer->short_description)}
      <div class="rte">{$manufacturer->short_description}</div>
    {/if}
  </section>

  {if !empty($products)}
    <section>
      <h2 class="page-heading">
        {l s='List of products by manufacturer'}&nbsp;{$manufacturer->name|escape:'html':'UTF-8'}
      </h2>

      <div class="content_sortPagiBar clearfix">
        <div class="form-inline sortPagiBar clearfix">
          {include file="./product-sort.tpl"}
          {include file="./nbr-product-page.tpl"}
        </div>
        <div class="top-pagination-content form-inline clearfix">
          {include file="./product-compare.tpl"}
          {include file="$tpl_dir./pagination.tpl" no_follow=1}
        </div>
      </div>

      {include file="./product-list.tpl" products=$products}

      <div class="content_sortPagiBar">
        <div class="bottom-pagination-content form-inline clearfix">
          {include file="./product-compare.tpl"}
          {include file="./pagination.tpl" paginationId='bottom'}
        </div>
      </div>

    </section>
  {else}
    <div class="alert alert-warning">{l s='No products for this manufacturer.'}</div>
  {/if}
{/if}
