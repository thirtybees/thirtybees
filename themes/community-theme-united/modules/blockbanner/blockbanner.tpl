{if !empty($banner_img)}
  <div id="blockbanner">
    <div class="container">
      <a href="{if $banner_link}{$banner_link|escape:'htmlall':'UTF-8'}{else}{if isset($force_ssl) && $force_ssl}{$base_dir_ssl}{else}{$base_dir}{/if}{/if}"{if !empty($banner_desc)} title="{$banner_desc|escape:'htmlall':'UTF-8'}"{/if}>
        <img class="img-responsive" src="{$banner_img|escape:'htmlall':'UTF-8'}"{if !empty($banner_desc)} alt="{$banner_desc|escape:'htmlall':'UTF-8'}" title="{$banner_desc|escape:'htmlall':'UTF-8'}"{/if}/>
      </a>
    </div>
  </div>
{/if}
