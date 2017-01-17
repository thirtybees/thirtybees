{if isset($no_follow) && $no_follow}
  {assign var='no_follow_text' value=' rel="nofollow"'}
{else}
  {assign var='no_follow_text' value=''}
{/if}

{if !empty($p)}

  {if isset($smarty.get.id_category) && $smarty.get.id_category && isset($category)}
    {if !isset($current_url)}
      {assign var='requestPage' value=$link->getPaginationLink('category', $category, false, false, true, false)}
    {else}
      {assign var='requestPage' value=$current_url}
    {/if}
    {assign var='requestNb' value=$link->getPaginationLink('category', $category, true, false, false, true)}
  {elseif isset($smarty.get.id_manufacturer) && $smarty.get.id_manufacturer && isset($manufacturer)}
    {assign var='requestPage' value=$link->getPaginationLink('manufacturer', $manufacturer, false, false, true, false)}
    {assign var='requestNb' value=$link->getPaginationLink('manufacturer', $manufacturer, true, false, false, true)}
  {elseif isset($smarty.get.id_supplier) && $smarty.get.id_supplier && isset($supplier)}
    {assign var='requestPage' value=$link->getPaginationLink('supplier', $supplier, false, false, true, false)}
    {assign var='requestNb' value=$link->getPaginationLink('supplier', $supplier, true, false, false, true)}
  {else}
    {if !isset($current_url)}
      {assign var='requestPage' value=$link->getPaginationLink(false, false, false, false, true, false)}
    {else}
      {assign var='requestPage' value=$current_url}
    {/if}
    {assign var='requestNb' value=$link->getPaginationLink(false, false, true, false, false, true)}
  {/if}

  <div id="pagination{if isset($paginationId)}_{$paginationId}{/if}" class="form-group clearfix">
    {if $start!=$stop}
      <ul class="pagination">
        {if $p != 1}
          {assign var='p_previous' value=$p-1}
          <li id="pagination_previous{if isset($paginationId)}_{$paginationId}{/if}" class="pagination_previous" title="{l s='Previous'}">
            <a{$no_follow_text} href="{$link->goPage($requestPage, $p_previous)}" rel="prev">
              <span>&laquo;</span>
            </a>
          </li>
        {else}
          <li id="pagination_previous{if isset($paginationId)}_{$paginationId}{/if}" class="disabled pagination_previous" title="{l s='Previous'}">
            <span>
              <span>&laquo;</span>
            </span>
          </li>
        {/if}
        {if $start==3}
          <li>
            <a{$no_follow_text} href="{$link->goPage($requestPage, 1)}">
              <span>1</span>
            </a>
          </li>
          <li>
            <a{$no_follow_text} href="{$link->goPage($requestPage, 2)}">
              <span>2</span>
            </a>
          </li>
        {/if}
        {if $start==2}
          <li>
            <a{$no_follow_text} href="{$link->goPage($requestPage, 1)}">
              <span>1</span>
            </a>
          </li>
        {/if}
        {if $start>3}
          <li>
            <a{$no_follow_text} href="{$link->goPage($requestPage, 1)}">
              <span>1</span>
            </a>
          </li>
          <li class="truncate">
            <span>
              <span>...</span>
            </span>
          </li>
        {/if}
        {section name=pagination start=$start loop=$stop+1 step=1}
          {if $p == $smarty.section.pagination.index}
            <li class="active current">
              <span>
                <span>{$p|escape:'html':'UTF-8'}</span>
              </span>
            </li>
          {else}
            <li>
              <a{$no_follow_text} href="{$link->goPage($requestPage, $smarty.section.pagination.index)}">
                <span>{$smarty.section.pagination.index|escape:'html':'UTF-8'}</span>
              </a>
            </li>
          {/if}
        {/section}
        {if $pages_nb>$stop+2}
          <li class="truncate">
            <span>
              <span>...</span>
            </span>
          </li>
          <li>
            <a{$no_follow_text} href="{$link->goPage($requestPage, $pages_nb)}">
              <span>{$pages_nb|intval}</span>
            </a>
          </li>
        {/if}
        {if $pages_nb==$stop+1}
          <li>
            <a{$no_follow_text} href="{$link->goPage($requestPage, $pages_nb)}">
              <span>{$pages_nb|intval}</span>
            </a>
          </li>
        {/if}
        {if $pages_nb==$stop+2}
          <li>
            <a{$no_follow_text} href="{$link->goPage($requestPage, $pages_nb-1)}">
              <span>{$pages_nb-1|intval}</span>
            </a>
          </li>
          <li>
            <a{$no_follow_text} href="{$link->goPage($requestPage, $pages_nb)}">
              <span>{$pages_nb|intval}</span>
            </a>
          </li>
        {/if}
        {if $pages_nb > 1 AND $p != $pages_nb}
          {assign var='p_next' value=$p+1}
          <li id="pagination_next{if isset($paginationId)}_{$paginationId}{/if}" class="pagination_next" title="{l s='Next'}">
            <a{$no_follow_text} href="{$link->goPage($requestPage, $p_next)}" rel="next">
              <span>&raquo;</span>
            </a>
          </li>
        {else}
          <li id="pagination_next{if isset($paginationId)}_{$paginationId}{/if}" class="disabled pagination_next" title="{l s='Next'}">
            <span>&raquo;</span>
          </li>
        {/if}
      </ul>

    {/if}
  </div>

  {if $nb_products > $products_per_page && $start!=$stop}
    <div class="form-group showall">
      <form action="{if !is_array($requestNb)}{$requestNb}{else}{$requestNb.requestUrl}{/if}" method="get">

        {if !empty($search_query)}
          <input type="hidden" name="search_query" value="{$search_query|escape:'html':'UTF-8'}" />
        {/if}
        {if !empty($tag) && !is_array($tag)}
          <input type="hidden" name="tag" value="{$tag|escape:'html':'UTF-8'}" />
        {/if}

        {if is_array($requestNb)}
          {foreach from=$requestNb item=requestValue key=requestKey}
            {if $requestKey != 'requestUrl' && $requestKey != 'p'}
              <input type="hidden" name="{$requestKey|escape:'html':'UTF-8'}" value="{$requestValue|escape:'html':'UTF-8'}" />
            {/if}
          {/foreach}
        {/if}

        <button type="submit" class="btn btn-default">{l s='Show all'}</button>
        <input name="n" id="nb_item" type="hidden" value="{$nb_products}" />

      </form>
    </div>
  {/if}

  <div class="form-group product-count">
    {if ($n*$p) < $nb_products }
      {assign var='productShowing' value=$n*$p}
    {else}
      {assign var='productShowing' value=($n*$p-$nb_products-$n*$p)*-1}
    {/if}
    {if $p==1}
      {assign var='productShowingStart' value=1}
    {else}
      {assign var='productShowingStart' value=$n*$p-$n+1}
    {/if}
    <p class="form-control-static">
      {if $nb_products > 1}
        {l s='Showing %1$d - %2$d of %3$d items' sprintf=[$productShowingStart, $productShowing, $nb_products]}
      {else}
        {l s='Showing %1$d - %2$d of 1 item' sprintf=[$productShowingStart, $productShowing]}
      {/if}
    </p>
  </div>

{/if}
