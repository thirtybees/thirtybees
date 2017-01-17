{if isset($colors_list)}
  <ul class="color_to_pick_list clearfix">
    {foreach from=$colors_list item='color'}
      {if isset($col_img_dir)}
        {assign var='img_color_exists' value=file_exists($col_img_dir|cat:$color.id_attribute|cat:'.jpg')}
        <li>
          <a href="{$link->getProductLink($color.id_product, null, null, null, null, null, $color.id_product_attribute, Configuration::get('PS_REWRITING_SETTINGS'), false, true)|escape:'html':'UTF-8'}" id="color_{$color.id_product_attribute|intval}" class="color_pick"{if !$img_color_exists && isset($color.color) && $color.color} style="background:{$color.color};"{/if}>
            {if $img_color_exists}
              <img src="{$img_col_dir}{$color.id_attribute|intval}.jpg" alt="{$color.name|escape:'html':'UTF-8'}" title="{$color.name|escape:'html':'UTF-8'}" width="20" height="20" />
            {/if}
          </a>
        </li>
      {/if}
    {/foreach}
  </ul>
{/if}
