{if isset($smarty.capture.path)}{assign var='path' value=$smarty.capture.path}{/if}

{if !empty($path)}
  {* Extract bradcrumb links from anchors *}
  {$matchCount = preg_match_all('/<a.+?href="(.+?)"[^>]*>([^<]*)<\/a>/', $path, $matches)}
  {$breadcrumbs = []}
  {for $i=0; $i<$matchCount; $i++}
    {$breadcrumbs[] = ['url' => $matches[1][$i], 'title' => $matches[2][$i]]}
  {/for}

  {* Extract the last breadcrumb which is not link, it's plain text or text inside span *}
  {$match = preg_match('/>([^<]+)(?:<\/\w+>\s*)?$/', $path, $matches)}
  {if !empty($matches[1])}
    {$breadcrumbs[] = ['url' => '', 'title' => $matches[1]]}
  {elseif !$match && !$matchCount}
    {$breadcrumbs[] = ['url' => '', 'title' => $path]}
  {/if}
{/if}

<ol class="breadcrumb" itemscope itemtype="http://schema.org/BreadcrumbList">
  <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
    <a href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl}{else}{$base_dir}{/if}" title="{l s='Home Page'}" itemprop="item">
      <span itemprop="name">{l s='Home'}</span>
    </a>
    <meta itemprop="position" content="1" />
  </li>
  {if !empty($breadcrumbs)}
    {foreach from=$breadcrumbs item=breadcrumb name=crumbs}
      <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
        {if !empty($breadcrumb.url)}
          <a href="{$breadcrumb.url}" itemprop="item">
            <span itemprop="name">{$breadcrumb.title}</span>
          </a>
        {else}
          <span itemprop="name">{$breadcrumb.title}</span>
        {/if}
        <meta itemprop="position" content="{($smarty.foreach.crumbs.iteration|intval + 1)}" />
      </li>
    {/foreach}
  {/if}
</ol>

{if isset($smarty.get.search_query) && isset($smarty.get.results) && $smarty.get.results > 1 && isset($smarty.server.HTTP_REFERER)}
  <nav>
    <ul class="pager">
      <li class="previous">
        {capture}{if isset($smarty.get.HTTP_REFERER) && $smarty.get.HTTP_REFERER}{$smarty.get.HTTP_REFERER}{elseif isset($smarty.server.HTTP_REFERER) && $smarty.server.HTTP_REFERER}{$smarty.server.HTTP_REFERER}{/if}{/capture}
        <a href="{$smarty.capture.default|escape:'html':'UTF-8'|secureReferrer|regex_replace:'/[\?|&]content_only=1/':''}" name="back">
          <span>&larr; {l s='Back to Search results for "%s" (%d other results)' sprintf=[$smarty.get.search_query,$smarty.get.results]}</span>
        </a>
      </li>
    </ul>
  </nav>
{/if}
