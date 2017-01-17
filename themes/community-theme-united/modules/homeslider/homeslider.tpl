{if $page_name =='index'}
  {if isset($homeslider_slides)}
    <div id="homepage-slider" class="col-xs-12">
      {if isset($homeslider_slides[0]) && isset($homeslider_slides[0].sizes.1)}{capture name='height'}{$homeslider_slides[0].sizes.1}{/capture}{/if}
      <ul id="homeslider"{if isset($smarty.capture.height) && $smarty.capture.height} style="max-height:{$smarty.capture.height}px;"{/if}>
        {foreach from=$homeslider_slides item=slide}
          {if $slide.active}
            <li class="homeslider-container">
              <a href="{$slide.url|escape:'html':'UTF-8'}" title="{$slide.legend|escape:'html':'UTF-8'}">
                <img class="img-responsive" src="{$link->getMediaLink("`$smarty.const._MODULE_DIR_`homeslider/images/`$slide.image|escape:'htmlall':'UTF-8'`")}"{if isset($slide.size) && $slide.size} {$slide.size}{else} width="100%" height="100%"{/if} alt="{$slide.legend|escape:'htmlall':'UTF-8'}" />
              </a>
              {if isset($slide.description) && trim($slide.description) != ''}
                <div class="homeslider-wrapper hid1den-xs">
                  <div class="homeslider-description">{$slide.description}</div>
                </div>
              {/if}
            </li>
          {/if}
        {/foreach}
      </ul>
      <div id="homeslider-pager">
        <span>{l s='More offers:' mod='homeslider'}</span>
        <span id="homeslider-pages"></span>
      </div>
    </div>
  {/if}
{/if}
