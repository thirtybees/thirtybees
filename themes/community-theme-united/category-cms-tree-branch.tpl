<li>
  <a href="{$node.link|escape:'html':'UTF-8'}" title="{$node.name|escape:'html':'UTF-8'}">
    <strong>{$node.name|escape:'html':'UTF-8'}</strong>
  </a>

  {if !empty($node.children) || !empty($node.cms)}
    <ul>

      {if !empty($node.children)}
        {foreach from=$node.children item=child}
          {if !empty($child.children) || !empty($child.cms)}
            {include file="$tpl_dir./category-cms-tree-branch.tpl" node=$child}
          {/if}
        {/foreach}
      {/if}

      {if !empty($node.cms)}
        {foreach from=$node.cms item=cms }
          <li>
            <a href="{$cms.link|escape:'html':'UTF-8'}" title="{$cms.meta_title|escape:'html':'UTF-8'}">
              {$cms.meta_title|escape:'html':'UTF-8'}
            </a>
          </li>
        {/foreach}
      {/if}

    </ul>
  {/if}
</li>
