{*
 * ATENTION! If you would like to have active categories already expanded before the JavaScript loads,
 * You must calculate whichs categories should be expanded by override a method in BlockCategories module.
 * You may use this override: https://gist.github.com/anonymous/7715d13eb74424075c95d880089e8200
 * If you decide to use it, don't forget to remove the JavaScript part which expands the active category.
 *}
{if !empty($list_item.children)}
  {$list_item_id = 'ct-'|cat:$list_item.id}
  <div class="list-group-item-wrapper{if (isset($currentCategoryId) && $list_item.id == $currentCategoryId) || (!empty($list_item.expanded) && $list_item.expanded)} active{/if}">
    <a href="{$list_item.link|escape:'html':'UTF-8'}" class="list-group-item ilvl-{$level|intval}{if isset($currentCategoryId) && $list_item.id == $currentCategoryId} current{/if}">
      <span>{$list_item.name|escape:'html':'UTF-8'}</span>
    </a>
    <a class="btn-toggle{if empty($list_item.expanded)} collapsed{/if} ilvl-{$level|intval}" href="#{$list_item_id|escape:'html':'UTF-8'}" data-toggle="collapse" title="{l s='Expand/Collapse' mod='blockcategories'}">
      <i class="icon icon-angle-up"></i>
    </a>
  </div>
  <div {if empty($list_item.expanded)} class="list-group collapse" style="height: 0px;"{else} class="list-group collapse in" style="height: auto;"{/if} id="{$list_item_id|escape:'html':'UTF-8'}">
    {foreach from=$list_item.children item=list_item_child}
      {include file="./list_group_item.tpl" list_item=$list_item_child level=$level+1}
    {/foreach}
  </div>
{else}
  <a class="list-group-item ilvl-{$level|intval}{if isset($currentCategoryId) && $list_item.id == $currentCategoryId} active current{/if}" href="{$list_item.link|escape:'html':'UTF-8'}">
    <span>{$list_item.name|escape:'html':'UTF-8'}</span>
  </a>
{/if}
