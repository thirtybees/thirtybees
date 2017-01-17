<div id="search_block_left" class="block">
  <p class="title_block">{l s='Search' mod='blocksearch'}</p>
  <form method="get" action="{$link->getPageLink('search', true, null, null, false, null, true)|escape:'html':'UTF-8'}" id="searchbox">
    <label for="search_query_block">{l s='Search products:' mod='blocksearch'}</label>
    <p class="block_content clearfix">
      <input type="hidden" name="orderby" value="position" />
      <input type="hidden" name="controller" value="search" />
      <input type="hidden" name="orderway" value="desc" />
      <input class="search_query form-control" type="text" id="search_query_block" name="search_query" value="{$search_query|escape:'htmlall':'UTF-8'|stripslashes}" />
      <button type="submit" id="search_button" class="btn btn-default"><span><i class="icon icon-search"></i></span></button>
    </p>
  </form>
</div>
