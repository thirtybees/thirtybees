<section id="blocksocial" class="col-xs-12">
  <span class="h4">{l s='Follow us' mod='blocksocial'}</span>

  {if !empty($facebook_url)}
    <a href="{$facebook_url|escape:html:'UTF-8'}" title="{l s='Facebook' mod='blocksocial'}">
      <i class="icon icon-facebook icon-2x icon-fw"></i>
    </a>
  {/if}

  {if !empty($twitter_url)}
    <a href="{$twitter_url|escape:html:'UTF-8'}" title="{l s='Twitter' mod='blocksocial'}">
      <i class="icon icon-twitter icon-2x icon-fw"></i>
    </a>
  {/if}

  {if !empty($rss_url)}
    <a href="{$rss_url|escape:html:'UTF-8'}" title="{l s='RSS' mod='blocksocial'}">
      <i class="icon icon-rss icon-2x icon-fw"></i>
    </a>
  {/if}

  {if !empty($youtube_url)}
    <a href="{$youtube_url|escape:html:'UTF-8'}" title="{l s='Youtube' mod='blocksocial'}">
      <i class="icon icon-youtube icon-2x icon-fw"></i>
    </a>
  {/if}

  {if !empty($google_plus_url)}
    <a href="{$google_plus_url|escape:html:'UTF-8'}" title="{l s='Google Plus' mod='blocksocial'}">
      <i class="icon icon-google-plus icon-2x icon-fw"></i>
    </a>
  {/if}

  {if !empty($pinterest_url)}
    <a href="{$pinterest_url|escape:html:'UTF-8'}" title="{l s='Pinterest' mod='blocksocial'}">
      <i class="icon icon-pinterest icon-2x icon-fw"></i>
    </a>
  {/if}

  {if !empty($vimeo_url)}
    <a href="{$vimeo_url|escape:html:'UTF-8'}" title="{l s='Vimeo' mod='blocksocial'}">
      <i class="icon icon-vimeo icon-2x icon-fw"></i>
    </a>
  {/if}

  {if !empty($instagram_url)}
    <a href="{$instagram_url|escape:html:'UTF-8'}" title="{l s='Instagram' mod='blocksocial'}">
      <i class="icon icon-instagram icon-2x icon-fw"></i>
    </a>
  {/if}

</section>
