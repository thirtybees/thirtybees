{capture name=path}{l s='Top sellers'}{/capture}

<h1 class="page-heading product-listing">{l s='Top sellers'}</h1>

{if $products}
  <div class="content_sortPagiBar">
    <div class="form-inline sortPagiBar clearfix">
      {include file="./product-sort.tpl"}
      {include file="./nbr-product-page.tpl"}
    </div>
    <div class="top-pagination-content form-inline clearfix">
      {include file="./product-compare.tpl"}
      {include file="$tpl_dir./pagination.tpl"}
    </div>
  </div>

  {include file="./product-list.tpl" products=$products}

  <div class="content_sortPagiBar">
    <div class="bottom-pagination-content form-inline clearfix">
      {include file="./product-compare.tpl"}
      {include file="./pagination.tpl" paginationId='bottom'}
    </div>
  </div>
{else}
  <div class="alert alert-warning">{l s='No top sellers for the moment.'}</div>
{/if}
