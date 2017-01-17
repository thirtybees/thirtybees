{if isset($cms) && !isset($cms_category)}

  {if !$cms->active}
    <div id="admin-action-cms" class="well">
      <div>
        <div class="alert alert-info">{l s='This CMS page is not visible to your customers.'}</div>
        <input type="hidden" id="admin-action-cms-id" value="{$cms->id}" />
        <input type="submit" value="{l s='Publish'}" name="publish_button" class="btn btn-success"/>
        <input type="submit" value="{l s='Back'}" name="lnk_view" class="btn btn-warning"/>
      </div>
      <div class="clear"></div>
      <div id="admin-action-result"></div>
    </div>
  {/if}

  <article>
    {if !empty($cms->meta_title)}
      <h1 class="page-heading">{$cms->meta_title}</h1>
    {/if}
    <div class="cms-content rte">{$cms->content}</div>
  </article>

{elseif isset($cms_category)}

  <article>
    <h1 class="page-heading">{$cms_category->name|escape:'html':'UTF-8'}</h1>

    {if !empty($cms_category->description)}
      <div class="cms-category-content rte">{$cms_category->description|escape:'html':'UTF-8'}</div>
    {/if}

    {if !empty($sub_category)}
      <section>
        <h2 class="page-heading">{l s='List of sub categories in %s:' sprintf=$cms_category->name}</h2>
        <ul class="cms-category-list">
          {foreach from=$sub_category item=subcategory}
            <li>
              <a href="{$link->getCMSCategoryLink($subcategory.id_cms_category, $subcategory.link_rewrite)|escape:'html':'UTF-8'}">
                {$subcategory.name|escape:'html':'UTF-8'}
              </a>
            </li>
          {/foreach}
        </ul>
      </section>
    {/if}

    {if !empty($cms_pages)}
      <section>
        <h2 class="page-heading">{l s='List of pages in %s:' sprintf=$cms_category->name}</h2>
        <ul class="cms-page-list">
          {foreach from=$cms_pages item=cmspages}
            <li>
              <a href="{$link->getCMSLink($cmspages.id_cms, $cmspages.link_rewrite)|escape:'html':'UTF-8'}">
                {$cmspages.meta_title|escape:'html':'UTF-8'}
              </a>
            </li>
          {/foreach}
        </ul>
      </section>
    {/if}
  </article>

{else}
  <div class="alert alert-danger">{l s='This page does not exist.'}</div>
{/if}

{strip}
  {if isset($smarty.get.ad) && $smarty.get.ad}
    {addJsDefL name=ad}{$base_dir|cat:$smarty.get.ad|escape:'html':'UTF-8'}{/addJsDefL}
  {/if}
  {if isset($smarty.get.adtoken) && $smarty.get.adtoken}
    {addJsDefL name=adtoken}{$smarty.get.adtoken|escape:'html':'UTF-8'}{/addJsDefL}
  {/if}
{/strip}
