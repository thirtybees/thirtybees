{if !empty($htmlitems)}
  {if $hook == 'top'}
    {include file='./hook_top.tpl' items=$htmlitems}
  {elseif $hook == 'home'}
    {include file='./hook_home.tpl' items=$htmlitems}
  {elseif $hook == 'left'}
    {include file='./hook_left.tpl' items=$htmlitems}
  {elseif $hook == 'right'}
    {include file='./hook_right.tpl' items=$htmlitems}
  {elseif $hook == 'footer'}
    {include file='./hook_footer.tpl' items=$htmlitems}
  {/if}
{/if}
