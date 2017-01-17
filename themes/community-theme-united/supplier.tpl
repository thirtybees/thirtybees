{include file="$tpl_dir./errors.tpl"}

{if !isset($errors) OR !sizeof($errors)}
  <section>
    <h1 class="page-heading">{$supplier->name|escape:'html':'UTF-8'}</h1>

    {if !empty($supplier->description)}
      <div class="rte">{$supplier->description}</div>
    {/if}
  </section>

  {if !empty($products)}
    <section>
      <h2 class="page-heading">
        {l s='List of products by supplier:'}&nbsp;{$supplier->name|escape:'html':'UTF-8'}
      </h2>
      <div class="content_sortPagiBar">
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
          {include file="./pagination.tpl" paginationId='bottom' no_follow=1}
        </div>
      </div>
    </section>

  {else}
    <div class="alert alert-warning">{l s='No products for this supplier.'}</div>
  {/if}
{/if}
