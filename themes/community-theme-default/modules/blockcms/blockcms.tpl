{if $block == 1}
  {foreach from=$cms_titles item=cms_title}
    <section class="blockcms-block blockcms-block-col block">
      <h4 class="title_block">
        <a href="{$cms_title.category_link|escape:'html':'UTF-8'}">
          {if !empty($cms_title.name)}{$cms_title.name}{else}{$cms_title.category_name}{/if}
        </a>
      </h4>
      <div class="block_content list-block">
        <ul>
          {foreach from=$cms_title.categories item=cms_page}
            {if isset($cms_page.link)}
              <li>
                <a href="{$cms_page.link|escape:'html':'UTF-8'}" title="{$cms_page.name|escape:'html':'UTF-8'}">
                  {$cms_page.name|escape:'html':'UTF-8'}
                </a>
              </li>
            {/if}
          {/foreach}
          {foreach from=$cms_title.cms item=cms_page}
            {if isset($cms_page.link)}
              <li>
                <a href="{$cms_page.link|escape:'html':'UTF-8'}" title="{$cms_page.meta_title|escape:'html':'UTF-8'}">
                  {$cms_page.meta_title|escape:'html':'UTF-8'}
                </a>
              </li>
            {/if}
          {/foreach}
          {if $cms_title.display_store}
            <li>
              <a href="{$link->getPageLink('stores')|escape:'html':'UTF-8'}" title="{l s='Our stores' mod='blockcms'}">
                {l s='Our stores' mod='blockcms'}
              </a>
            </li>
          {/if}
        </ul>
      </div>
    </section>
  {/foreach}
{else}
  <section id="blockcms-footer" class="blockcms-block col-xs-12 col-sm-3">
    <h4>{l s='Information' mod='blockcms'}</h4>
    <ul class="list-unstyled">
      {if isset($show_price_drop) && $show_price_drop && !$PS_CATALOG_MODE}
        <li>
          <a href="{$link->getPageLink('prices-drop')|escape:'html':'UTF-8'}" title="{l s='Specials' mod='blockcms'}">
            {l s='Specials' mod='blockcms'}
          </a>
        </li>
      {/if}
      {if isset($show_new_products) && $show_new_products}
        <li>
          <a href="{$link->getPageLink('new-products')|escape:'html':'UTF-8'}" title="{l s='New products' mod='blockcms'}">
            {l s='New products' mod='blockcms'}
          </a>
        </li>
      {/if}
      {if isset($show_best_sales) && $show_best_sales && !$PS_CATALOG_MODE}
        <li>
          <a href="{$link->getPageLink('best-sales')|escape:'html':'UTF-8'}" title="{l s='Top sellers' mod='blockcms'}">
            {l s='Top sellers' mod='blockcms'}
          </a>
        </li>
      {/if}
      {if isset($display_stores_footer) && $display_stores_footer}
        <li>
          <a href="{$link->getPageLink('stores')|escape:'html':'UTF-8'}" title="{l s='Our stores' mod='blockcms'}">
            {l s='Our stores' mod='blockcms'}
          </a>
        </li>
      {/if}
      {if isset($show_contact) && $show_contact}
        <li>
          <a href="{$link->getPageLink($contact_url, true)|escape:'html':'UTF-8'}" title="{l s='Contact us' mod='blockcms'}">
            {l s='Contact us' mod='blockcms'}
          </a>
        </li>
      {/if}
      {foreach from=$cmslinks item=cmslink}
        {if $cmslink.meta_title != ''}
          <li>
            <a href="{$cmslink.link|escape:'html':'UTF-8'}" title="{$cmslink.meta_title|escape:'html':'UTF-8'}">
              {$cmslink.meta_title|escape:'html':'UTF-8'}
            </a>
          </li>
        {/if}
      {/foreach}
      {if isset($show_sitemap) && $show_sitemap}
        <li>
          <a href="{$link->getPageLink('sitemap')|escape:'html':'UTF-8'}" title="{l s='Sitemap' mod='blockcms'}">
            {l s='Sitemap' mod='blockcms'}
          </a>
        </li>
      {/if}
    </ul>
    {if !empty($footer_text)}
      <p>{$footer_text}</p>
    {/if}
  </section>
{/if}
