<div id="search_block_top" class="col-sm-4 col-md-5" role="search">
  <form id="searchbox" method="get" action="{$link->getPageLink('search', null, null, null, false, null, true)|escape:'html':'UTF-8'}" >
    <input type="hidden" name="controller" value="search" />
    <input type="hidden" name="orderby" value="position" />
    <input type="hidden" name="orderway" value="desc" />
    <div class="input-group input-group-lg">
      <input class="form-control" type="search" id="search_query_top" name="search_query" placeholder="{l s='Search' mod='blocksearch'}" value="{$search_query|escape:'htmlall':'UTF-8'|stripslashes}" required>
      <span class="input-group-btn">
        <button class="btn btn-primary" type="submit" name="submit_search" title="{l s='Search' mod='blocksearch'}"><i class="icon icon-search"></i></button>
      </span>
    </div>
  </form>
</div>
