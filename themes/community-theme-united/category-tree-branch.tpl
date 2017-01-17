<li>
  <a href="{$node.link|escape:'html':'UTF-8'}" title="{$node.desc|escape:'html':'UTF-8'}">{$node.name|escape:'html':'UTF-8'}</a>
  {if !empty($node.children)}
    <ul>
      {foreach from=$node.children item=child}
        {include file="$tpl_dir./category-tree-branch.tpl" node=$child last='false'}
      {/foreach}
    </ul>
  {/if}
</li>
