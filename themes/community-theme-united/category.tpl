{$display_subcategories = (isset($display_subcategories) && $display_subcategories eq 1) || !isset($display_subcategories)}

{include file="$tpl_dir./errors.tpl"}

{if !empty($category) && $category->id}
  {if !$category->active}
    <div class="alert alert-warning">{l s='This category is currently unavailable.'}</div>
  {else}

    <section id="category-info">
      {if $category->id_image}
        <div id="category-banner">
          <img class="img-responsive" src="{$link->getCatImageLink($category->link_rewrite, $category->id_image, 'category_default')|escape:'html':'UTF-8'}" alt="{$category->name|escape:'html':'UTF-8'}">
        </div>
      {/if}

      <h1 class="page-heading{if (isset($subcategories) && !$products) || (isset($subcategories) && $products) || !isset($subcategories) && $products} product-listing{/if}">
      <span class="cat-name">
        {$category->name|escape:'html':'UTF-8'}
        {if isset($categoryNameComplement)}&nbsp;{$categoryNameComplement|escape:'html':'UTF-8'}{/if}
      </span>
      </h1>

      {if !empty($category->description)}
        <div id="category-description" class="rte">{$category->description}</div>
      {/if}
    </section>

    {if !empty($subcategories) && $display_subcategories}
      <section id="category-subcategories">
        <h2 class="page-heading">{l s='Subcategories'}</h2>
        <ul class="list-grid row">
          {foreach from=$subcategories item=subcategory}
            <li class="col-xs-6 col-sm-4 col-md-3">
              <div class="thumbnail">
                <a href="{$link->getCategoryLink($subcategory.id_category, $subcategory.link_rewrite)|escape:'html':'UTF-8'}" title="{$subcategory.name|escape:'html':'UTF-8'}">
                  {if $subcategory.id_image}
                    <img class="replace-2x img-responsive" src="{$link->getCatImageLink($subcategory.link_rewrite, $subcategory.id_image, 'medium_default')|escape:'html':'UTF-8'}" alt="{$subcategory.name|escape:'html':'UTF-8'}" width="{$mediumSize.width}" height="{$mediumSize.height}" />
                  {else}
                    <img class="replace-2x img-responsive" src="{$img_cat_dir}{$lang_iso}-default-medium_default.jpg" alt="{$subcategory.name|escape:'html':'UTF-8'}" width="{$mediumSize.width}" height="{$mediumSize.height}" />
                  {/if}
                </a>
                <div class="caption">
                  <h3 class="subcategory-title text-center">
                    <a href="{$link->getCategoryLink($subcategory.id_category, $subcategory.link_rewrite)|escape:'html':'UTF-8'}">{$subcategory.name|escape:'html':'UTF-8'}</a>
                  </h3>
                  {* if $subcategory.description}
                    <div>{$subcategory.description}</div>
                  {/if *}
                </div>
              </div>
            </li>
          {/foreach}
        </ul>
      </section>
    {/if}

    {if !empty($products)}
      <section id="category-products">
        <h2 class="page-heading">
          {l s='Products'}
          <div class="pull-right">
            {include file="$tpl_dir./category-count.tpl"}
          </div>
        </h2>

        <div class="content_sortPagiBar clearfix">
          <div class="form-inline sortPagiBar clearfix">
            {include file="./product-sort.tpl"}
            {include file="./nbr-product-page.tpl"}
          </div>
          <div class="top-pagination-content form-inline clearfix">
            {include file="./product-compare.tpl"}
            {include file="$tpl_dir./pagination.tpl"}
          </div>
        </div>
        {include file="./product-list.tpl" products=$products}

        <div class="content_sortPagiBar">
          <div class="bottom-pagination-content form-inline clearfix">
            {include file="./product-compare.tpl" paginationId='bottom'}
            {include file="./pagination.tpl" paginationId='bottom'}
          </div>
        </div>
      </section>
    {/if}

  {/if}
{/if}
