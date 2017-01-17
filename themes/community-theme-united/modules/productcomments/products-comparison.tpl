<tr class="comparison_header active">
  <td class="td_empty">{l s='Comments' mod='productcomments'}</td>
  <td colspan="{$list_ids_product|count}"></td>
</tr>

{foreach from=$grades item=grade key=grade_id}
  <tr>
    <td class="feature-name">{$grade}</td>
    {foreach from=$list_ids_product item=id_product}
      {assign var='tab_grade' value=$product_grades[$grade_id]}
      <td class="comparison_infos ajax_block_product" align="center">
        {if isset($tab_grade[$id_product]) AND $tab_grade[$id_product]}
          <div class="product-rating star_content">
            {section loop=6 step=1 start=1 name=average}
              <input class="auto-submit-star" disabled="disabled" type="radio" name="{$grade_id}_{$id_product}_{$smarty.section.average.index}" {if isset($tab_grade[$id_product]) AND $tab_grade[$id_product]|round neq 0 and $smarty.section.average.index eq $tab_grade[$id_product]|round}checked="checked"{/if} />
            {/section}
          </div>
        {else}
          -
        {/if}
      </td>
    {/foreach}
  </tr>
{/foreach}

<tr>
  <td  class="feature-name">{l s='Average' mod='productcomments'}</td>
  {foreach from=$list_ids_product item=id_product}
    <td class="comparison_infos product-{$id_product}" align="center">
      {if isset($list_product_average[$id_product]) AND $list_product_average[$id_product]}
        <div class="product-rating star_content">
          {section loop=6 step=1 start=1 name=average}
            <input class="auto-submit-star" disabled="disabled" type="radio" name="average_{$id_product}" {if $list_product_average[$id_product]|round neq 0 and $smarty.section.average.index eq $list_product_average[$id_product]|round}checked="checked"{/if} />
          {/section}
        </div>
      {else}
        -
      {/if}
    </td>
  {/foreach}
</tr>

