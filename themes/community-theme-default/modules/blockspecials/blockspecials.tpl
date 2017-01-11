<div id="special_block_right" class="block">
  <p class="title_block">
    <a href="{$link->getPageLink('prices-drop')|escape:'html':'UTF-8'}" title="{l s='Specials' mod='blockspecials'}">
      {l s='Specials' mod='blockspecials'}
    </a>
  </p>
  <div class="block_content products-block">
    {if $special}
      <ul>
        <li class="clearfix">
          <a class="products-block-image" href="{$special.link|escape:'html':'UTF-8'}">
            <img
              class="replace-2x img-responsive"
              src="{$link->getImageLink($special.link_rewrite, $special.id_image, 'small_default')|escape:'html':'UTF-8'}"
              alt="{$special.legend|escape:'html':'UTF-8'}"
              title="{$special.name|escape:'html':'UTF-8'}" />
          </a>
          <div class="product-content">
            <h5>
              <a class="product-name" href="{$special.link|escape:'html':'UTF-8'}" title="{$special.name|escape:'html':'UTF-8'}">
                {$special.name|escape:'html':'UTF-8'}
              </a>
            </h5>
            {if isset($special.description_short) && $special.description_short}
              <p class="product-description">
                {$special.description_short|strip_tags:'UTF-8'|truncate:40}
              </p>
            {/if}
            <div class="price-box">
              {if !$PS_CATALOG_MODE}
                <span class="price special-price">
                  {if !$priceDisplay}
                    {displayWtPrice p=$special.price}{else}{displayWtPrice p=$special.price_tax_exc}
                  {/if}
                </span>
                {if $special.specific_prices}
                  {assign var='specific_prices' value=$special.specific_prices}
                  {if $specific_prices.reduction_type == 'percentage' && ($specific_prices.from == $specific_prices.to OR ($smarty.now|date_format:'%Y-%m-%d %H:%M:%S' <= $specific_prices.to && $smarty.now|date_format:'%Y-%m-%d %H:%M:%S' >= $specific_prices.from))}
                    <span class="price-percent-reduction">-{$specific_prices.reduction*100|floatval}%</span>
                  {/if}
                {/if}
                <span class="old-price">
                  {if !$priceDisplay}
                    {displayWtPrice p=$special.price_without_reduction}{else}{displayWtPrice p=$priceWithoutReduction_tax_excl}
                  {/if}
                </span>
                {hook h="displayProductPriceBlock" product=$special type="price"}
              {/if}
            </div>
          </div>
        </li>
      </ul>
      <div>
        <a
          class="btn btn-primary"
          href="{$link->getPageLink('prices-drop')|escape:'html':'UTF-8'}"
          title="{l s='All specials' mod='blockspecials'}">
          <span>{l s='All specials' mod='blockspecials'} <i class="icon icon-chevron-right"></i></span>
        </a>
      </div>
    {else}
      <div>{l s='No special products at this time.' mod='blockspecials'}</div>
    {/if}
  </div>
</div>
