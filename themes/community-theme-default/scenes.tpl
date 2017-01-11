{if $scenes}
  <div id="scenes">
    <div>
      {foreach $scenes as $scene_key=>$scene}
        <div class="screen_scene" id="screen_scene_{$scene->id}" style="background:transparent url({$base_dir}img/scenes/{$scene->id}-scene_default.jpg); height:{$largeSceneImageType.height}px; width:{$largeSceneImageType.width}px;{if !$scene@first} display:none;{/if}">
          {foreach $scene->products as $product_key=>$product}
            {if isset($product.id_image)}
              {assign var=imageIds value="`$product.id_product`-`$product.id_image`"}
            {/if}
            <a href="{$product.link|escape:'html':'UTF-8'}" class="popover-button" style="width:{$product.zone_width}px; height:{$product.zone_height}px; margin-left:{$product.x_axis}px ;margin-top:{$product.y_axis}px;" data-id_product_scene="{$scene_key|intval}_{$product_key|intval}_{$product.id_product|intval}">
              <span style="margin-top:{math equation='a/2 -10' a=$product.zone_height}px; margin-left:{math equation='a/2 -10' a=$product.zone_width}px;"></span>
            </a>
            <div id="scene_products_cluetip_{$scene_key}_{$product_key}_{$product.id_product}" style="display:none;">
              <div class="product-image-container">
                {if isset($imageIds)}
                  <img class="img-responsive replace-2x" src="{$link->getImageLink($product.id_product, $imageIds, 'home_default')|escape:'html':'UTF-8'}" alt="" />
                {/if}
              </div>
              <p class="product-name"><span class="product_name">{$product.details->name}</span></p>
              <div class="description">{$product.details->description_short|strip_tags|truncate:170:'...'}</div>
              {if !$PS_CATALOG_MODE AND $product.details->show_price}
                <div class="prices">
                  {if isset($product.details->online_only) AND $product.details->online_only}
                    <span class="product-label product-label-online">{l s='Online only'}</span>
                  {/if}
                  {if isset($product.details->new) AND $product.details->new}
                    <span class="product-label product-label-new">{l s='New'}</span>
                  {/if}
                  <p class="price product-price">{if $priceDisplay}{convertPrice price=$product.details->getPrice(false, $product.details->getDefaultAttribute($product.id_product))}{else}{convertPrice price=$product.details->getPrice(true, $product.details->getDefaultAttribute($product.id_product))}{/if}</p>
                  {if $product.details->on_sale}
                    <span class="product-label product-label-sale">{l s='Sale'}</span>
                  {elseif isset($product.reduction) && $product.reduction}
                    <span class="product-label product-label-discount">{l s='Reduced price!'}</span>
                  {/if}
                </div>
              {/if}
            </div>
          {/foreach}
        </div>
      {/foreach}
    </div>
    {if isset($scenes.1)}
      <div class="thumbs_banner" style="height:{$thumbSceneImageType.height}px;">
        <span class="space-keeper">
            <a class="prev" href="#" style="height:{math equation='a+2' a=$thumbSceneImageType.height}px;">&nbsp;</a>
        </span>
        <div id="scenes_list">
          <ul style="width:{math equation='(a*b + (a-1)*10)' a=$scenes|@count b=$thumbSceneImageType.width}px; height:{$thumbSceneImageType.height}px;">
            {foreach $scenes as $scene}
              <li id="scene_thumb_{$scene->id}" style="{if !$scene@last} padding-right:10px;{/if}">
                <a style="width:{$thumbSceneImageType.width}px; height:{$thumbSceneImageType.height}px" title="{$scene->name|escape:'html':'UTF-8'}" href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl}{else}{$base_dir}{/if}" data-id_scene="{$scene->id|intval}" class="scene_thumb">
                  <img alt="{$scene->name|escape:'html':'UTF-8'}" src="{$content_dir}img/scenes/thumbs/{$scene->id}-m_scene_default.jpg" width="{$thumbSceneSize.width}" height="{$thumbSceneSize.height}" />
                </a>
              </li>
            {/foreach}
          </ul>
        </div>
        <span class="space-keeper">
            <a class="next" href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl}{else}{$base_dir}{/if}" style="height:{math equation='a+2' a=$thumbSceneImageType.height}px;">&nbsp;</a>
        </span>
      </div>
    {/if}
  </div>
  {strip}
    {addJsDefL name=i18n_scene_close}{l s='Close' js=1}{/addJsDefL}
    {addJsDef li_width=($thumbSceneImageType.width|intval+10)}
  {/strip}
{/if}
