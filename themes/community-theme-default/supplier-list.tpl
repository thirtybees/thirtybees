{capture name=path}{l s='Suppliers:'}{/capture}

<h1 class="page-heading product-listing">
  {l s='Suppliers:'}
  <div class="pull-right">
    <span class="heading-counter badge">
      {if $nbSuppliers == 0}{l s='There are no suppliers.'}
      {else}
        {if $nbSuppliers == 1}
          {l s='There is %d supplier.' sprintf=$nbSuppliers}
        {else}
          {l s='There are %d suppliers.' sprintf=$nbSuppliers}
        {/if}
      {/if}
    </span>
  </div>
</h1>

{if !empty($errors)}
  {include file="$tpl_dir./errors.tpl"}
{else}

  {if $nbSuppliers > 0}
    <div class="content_sortPagiBar">
      <div class="form-inline sortPagiBar clearfix">

        {if isset($supplier) && isset($supplier.nb_products) && $supplier.nb_products > 0}
          {include file='./product-list-switcher.tpl'}
        {/if}

        {include file="./nbr-product-page.tpl"}
      </div>
      <div class="top-pagination-content form-inline clearfix">
        {include file="$tpl_dir./pagination.tpl" no_follow=1}
      </div>
    </div>

    <ul id="suppliers_list" class="list-grid row">
      {foreach from=$suppliers_list item=supplier}
        <li class="col-xs-6 col-sm-4 col-md-3">
          <div class="thumbnail">
            <a href="{$link->getsupplierLink($supplier.id_supplier, $supplier.link_rewrite)|escape:'html':'UTF-8'}" title="{$supplier.name|escape:'html':'UTF-8'}">
              <img class="img-responsive" src="{$img_sup_dir}{$supplier.image|escape:'html':'UTF-8'}-medium_default.jpg" width="{$mediumSize.width}" height="{$mediumSize.height}" />
            </a>
            <div class="caption">
              <h3 class="text-center">
                <a class="product-name" href="{$link->getsupplierLink($supplier.id_supplier, $supplier.link_rewrite)|escape:'html':'UTF-8'}">
                  {$supplier.name|escape:'html':'UTF-8'}
                </a>
              </h3>
              {if isset($supplier.nb_products)}
                <p class="text-center">
                  {if $supplier.nb_products == 1}
                    {l s='%d product' sprintf=$supplier.nb_products|intval}
                  {else}
                    {l s='%d products' sprintf=$supplier.nb_products|intval}
                  {/if}
                </p>
              {/if}
              {if !empty($supplier.description)}
                <div class="rte">{$supplier.description}</div>
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
