{capture name=path}{l s='Search'}{/capture}

<h1
  {if isset($instant_search) && $instant_search}id="instant_search_results"{/if}
  class="page-heading {if !isset($instant_search) || (isset($instant_search) && !$instant_search)} product-listing{/if}">
  {l s='Search'}&nbsp;
  {if $nbProducts > 0}
    <span class="lighter">
      "{if isset($search_query) && $search_query}{$search_query|escape:'html':'UTF-8'}{elseif $search_tag}{$search_tag|escape:'html':'UTF-8'}{elseif $ref}{$ref|escape:'html':'UTF-8'}{/if}"
    </span>
  {/if}
  {if isset($instant_search) && $instant_search}
    <a href="#" class="js-close-instant-search pull-right">
      {l s='Return to the previous page'}
    </a>
  {else}
    <div class="pull-right">
      <span class="heading-counter badge">
        {if $nbProducts == 1}{l s='%d result has been found.' sprintf=$nbProducts|intval}{else}{l s='%d results have been found.' sprintf=$nbProducts|intval}{/if}
      </span>
    </div>
  {/if}
</h1>

{include file="$tpl_dir./errors.tpl"}
{if !$nbProducts}
  <div class="alert alert-warning">
    {if isset($search_query) && $search_query}
      {l s='No results were found for your search'}&nbsp;"{if isset($search_query)}{$search_query|escape:'html':'UTF-8'}{/if}"
    {elseif isset($search_tag) && $search_tag}
      {l s='No results were found for your search'}&nbsp;"{$search_tag|escape:'html':'UTF-8'}"
    {else}
      {l s='Please enter a search keyword'}
    {/if}
  </div>
{else}
  {if isset($instant_search) && $instant_search}
    <div class="alert alert-info">
      {if $nbProducts == 1}{l s='%d result has been found.' sprintf=$nbProducts|intval}{else}{l s='%d results have been found.' sprintf=$nbProducts|intval}{/if}
    </div>
  {/if}
  <div class="content_sortPagiBar">
    <div class="form-inline sortPagiBar clearfix{if isset($instant_search) && $instant_search} instant_search{/if}">
      {include file="$tpl_dir./product-sort.tpl"}
      {if !isset($instant_search) || (isset($instant_search) && !$instant_search)}
        {include file="./nbr-product-page.tpl"}
      {/if}
    </div>
    <div class="top-pagination-content form-inline clearfix">
      {include file="./product-compare.tpl"}
      {if !isset($instant_search) || (isset($instant_search) && !$instant_search)}
        {include file="$tpl_dir./pagination.tpl" no_follow=1}
      {/if}
    </div>
  </div>
  {include file="$tpl_dir./product-list.tpl" products=$search_products}
  <div class="content_sortPagiBar">
    <div class="bottom-pagination-content form-inline clearfix">
      {include file="./product-compare.tpl"}
      {if !isset($instant_search) || (isset($instant_search) && !$instant_search)}
        {include file="$tpl_dir./pagination.tpl" paginationId='bottom' no_follow=1}
      {/if}
    </div>
  </div>
{/if}
