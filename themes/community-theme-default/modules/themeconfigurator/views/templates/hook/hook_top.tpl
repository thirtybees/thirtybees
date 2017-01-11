{if !empty($items)}
  <div id="themeconfigurator-top" class="col-xs-12 col-sm-4">
    {foreach from=$items item=item}
      {if $item.active}
        <div id="themeconfigurator-block-{$item.id_item|escape:'html':'UTF-8'}" class="themeconfigurator-block themeconfigurator-block-top">

          {if $item.url}
            <a class="item-link" href="{$item.url|escape:'html':'UTF-8'}" title="{$item.title|escape:'html':'UTF-8'}"{if $item.target == 1} target="_blank"{/if}>
          {/if}

          {if $item.image}
            <img class="item-img center-block img-responsive" src="{$link->getMediaLink("`$module_dir`img/`$item.image`")}" title="{$item.title|escape:'html':'UTF-8'}" alt="{$item.title|escape:'html':'UTF-8'}"{if $item.image_w} width="{$item.image_w|intval}"{/if}{if $item.image_h} height="{$item.image_h|intval}"{/if}/>
          {/if}

          {if $item.title && $item.title_use == 1}
            <h3 class="item-title">{$item.title|escape:'html':'UTF-8'}</h3>
          {/if}

          {if $item.html}
            <div class="item-html">
              {$item.html}
            </div>
          {/if}

          {if $item.url}
            </a>
          {/if}

        </div>
      {/if}
    {/foreach}
  </div>
{/if}
