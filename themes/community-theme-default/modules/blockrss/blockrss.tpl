<div id="rss_block_left" class="block">
  <p class="title_block">{$title}</p>
  <div class="block_content">
    {if $rss_links}
      <ul>
        {foreach from=$rss_links item='rss_link'}
          <li><a href="{$rss_link.url}">{$rss_link.title}</a></li>
        {/foreach}
      </ul>
    {else}
      <p>{l s='No RSS feed added' mod='blockrss'}</p>
    {/if}
  </div>
</div>
