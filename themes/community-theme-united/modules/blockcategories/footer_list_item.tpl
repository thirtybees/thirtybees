<li>
  <a href="{$item.link|escape:'html':'UTF-8'}" title="{$item.name|escape:'html':'UTF-8'}">
    {$item.name|escape:'html':'UTF-8'}
  </a>
  {if !empty($item.children)}
    <ul>
      {foreach from=$item.children item=child}
        {include file='./footer_list_item.tpl' item=$child}
      {/foreach}
    </ul>
  {/if}
</li>
