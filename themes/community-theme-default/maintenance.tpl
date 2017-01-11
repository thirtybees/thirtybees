<!DOCTYPE html>
<html lang="{$language_code|escape:'html':'UTF-8'}">
<head>
  <meta charset="utf-8">
  <title>{$meta_title|escape:'html':'UTF-8'}</title>
  {if isset($meta_description)}
    <meta name="description" content="{$meta_description|escape:'html':'UTF-8'}">
  {/if}
  {if isset($meta_keywords)}
    <meta name="keywords" content="{$meta_keywords|escape:'html':'UTF-8'}">
  {/if}
  <meta name="robots" content="{if isset($nobots)}no{/if}index,follow">
  <link rel="shortcut icon" href="{$favicon_url}">
  <link href="{$css_dir}global.css" rel="stylesheet">
  <link href="{$css_dir}maintenance.css" rel="stylesheet">
</head>
<body>

<div id="maintenance" class="text-center">
  <img class="center-block img-responsive" src="{$logo_url}" {if $logo_image_width}width="{$logo_image_width}"{/if} {if $logo_image_height}height="{$logo_image_height}"{/if}>
  <h1>{l s='We\'ll be back soon.'}</h1>
  {$HOOK_MAINTENANCE}
  <p>
    {l s='We are currently updating our shop and will be back really soon.'}
    <br>
    {l s='Thanks for your patience.'}
  </p>
</div>

</body>
</html>
