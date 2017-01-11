<div id="links_block_left" class="block">
  <p class="title_block">
    {if $url}
      <a href="{$url|escape}">{$title|escape}</a>
    {else}
      {$title|escape}
    {/if}
  </p>
  <div class="block_content list-block">
    <ul>
      {foreach from=$blocklink_links item=blocklink_link}
        {if isset($blocklink_link.$lang)}
          <li><a href="{$blocklink_link.url|escape}"{if $blocklink_link.newWindow} onclick="window.open(this.href);return false;"{/if}>{$blocklink_link.$lang|escape}</a></li>
        {/if}
      {/foreach}
    </ul>
  </div>
</div>
