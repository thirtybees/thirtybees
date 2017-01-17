{capture name=path}{l s='Manufacturers:'}{/capture}

<h1 class="page-heading product-listing">
  {l s='Brands'}
  <div class="pull-right">
    <span class="heading-counter badge">
      {if $nbManufacturers == 0}
        {l s='There are no manufacturers.'}
      {else}
        {if $nbManufacturers == 1}
          {l s='There is 1 brand'}
        {else}
          {l s='There are %d brands' sprintf=$nbManufacturers}
        {/if}
      {/if}
    </span>
  </div>
</h1>

{if isset($errors) AND $errors}
  {include file="$tpl_dir./errors.tpl"}
{else}
  {if $nbManufacturers > 0}

    <div class="content_sortPagiBar clearfix">
      <div class="form-inline sortPagiBar clearfix">
        {if isset($manufacturer) && isset($manufacturer.nb_products) && $manufacturer.nb_products > 0}
          {include file='./product-list-switcher.tpl'}
        {/if}
        {include file="./nbr-product-page.tpl"}
      </div>
      <div class="top-pagination-content form-inline clearfix">
        {include file="$tpl_dir./pagination.tpl" no_follow=1}
      </div>
    </div>

    <ul id="manufacturers_list" class="list-grid row">
      {foreach from=$manufacturers item=manufacturer}
        <li class="col-xs-6 col-sm-4 col-md-3">
          <div class="thumbnail">
            <a href="{$link->getmanufacturerLink($manufacturer.id_manufacturer, $manufacturer.link_rewrite)|escape:'html':'UTF-8'}" title="{$manufacturer.name|escape:'html':'UTF-8'}">
              <img class="img-responsive" src="{$img_manu_dir}{$manufacturer.image|escape:'html':'UTF-8'}-medium_default.jpg">
            </a>
            <div class="caption">
              <h3 class="text-center">
                <a href="{$link->getmanufacturerLink($manufacturer.id_manufacturer, $manufacturer.link_rewrite)|escape:'html':'UTF-8'}">
                  {$manufacturer.name|escape:'html':'UTF-8'}
                </a>
              </h3>
              {if isset($manufacturer.nb_products)}
                <p class="text-center">
                  {if  $manufacturer.nb_products == 1}
                    {l s='%d product' sprintf=$manufacturer.nb_products|intval}
                  {else}
                    {l s='%d products' sprintf=$manufacturer.nb_products|intval}
                  {/if}
                </p>
              {/if}
              {if !empty($manufacturer.short_description)}
                <div class="rte">{$manufacturer.short_description}</div>
              {/if}
            </div>
          </div>
        </li>
      {/foreach}
    </ul>

    <div class="content_sortPagiBar">
      <div class="bottom-pagination-content form-inline clearfix">
        {include file="$tpl_dir./pagination.tpl" no_follow=1 paginationId='bottom'}
      </div>
    </div>
  {/if}
{/if}
