<!DOCTYPE HTML>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7"{if isset($language_code) && $language_code} lang="{$language_code|escape:'html':'UTF-8'}"{/if}><![endif]-->
<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8 ie7"{if isset($language_code) && $language_code} lang="{$language_code|escape:'html':'UTF-8'}"{/if}><![endif]-->
<!--[if IE 8]><html class="no-js lt-ie9 ie8"{if isset($language_code) && $language_code} lang="{$language_code|escape:'html':'UTF-8'}"{/if}><![endif]-->
<!--[if gt IE 8]> <html class="no-js ie9"{if isset($language_code) && $language_code} lang="{$language_code|escape:'html':'UTF-8'}"{/if}><![endif]-->
<html{if isset($language_code) && $language_code} lang="{$language_code|escape:'html':'UTF-8'}"{/if}>
<head>
  <meta charset="utf-8" />
  <title>{$meta_title|escape:'html':'UTF-8'}</title>
  {if isset($meta_description) AND $meta_description}
    <meta name="description" content="{$meta_description|escape:'html':'UTF-8'}" />
  {/if}
  {if isset($meta_keywords) AND $meta_keywords}
    <meta name="keywords" content="{$meta_keywords|escape:'html':'UTF-8'}" />
  {/if}
  <meta name="generator" content="PrestaShop" />
  <meta name="robots" content="{if isset($nobots)}no{/if}index,{if isset($nofollow) && $nofollow}no{/if}follow" />
  <meta name="viewport" content="width=device-width, minimum-scale=0.25, maximum-scale=1.6, initial-scale=1.0" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <link rel="icon" type="image/vnd.microsoft.icon" href="{$favicon_url}?{$img_update_time}" />
  <link rel="shortcut icon" type="image/x-icon" href="{$favicon_url}?{$img_update_time}" />
  {if isset($css_files)}
    {foreach from=$css_files key=css_uri item=media}
      {if $css_uri == 'lteIE9'}
        <!--[if lte IE 9]>
        {foreach from=$css_files[$css_uri] key=css_uriie9 item=mediaie9}
        <link rel="stylesheet" href="{$css_uriie9|escape:'html':'UTF-8'}" type="text/css" media="{$mediaie9|escape:'html':'UTF-8'}" />
        {/foreach}
        <![endif]-->
      {else}
        <link rel="stylesheet" href="{$css_uri|escape:'html':'UTF-8'}" type="text/css" media="{$media|escape:'html':'UTF-8'}" />
      {/if}
    {/foreach}
  {/if}
  {if isset($js_defer) && !$js_defer && isset($js_files) && isset($js_def)}
    {$js_def}
    {foreach from=$js_files item=js_uri}
      <script type="text/javascript" src="{$js_uri|escape:'html':'UTF-8'}"></script>
    {/foreach}
  {/if}
  {$HOOK_HEADER}
  <!--[if IE 8]>
  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
  <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
  <![endif]-->
</head>
<body{if isset($page_name)} id="{$page_name|escape:'html':'UTF-8'}"{/if} class="{if isset($page_name)}{$page_name|escape:'html':'UTF-8'}{/if}{if isset($body_classes) && $body_classes|@count} {implode value=$body_classes separator=' '}{/if}{if $hide_left_column} hide-left-column{else} show-left-column{/if}{if $hide_right_column} hide-right-column{else} show-right-column{/if}{if isset($content_only) && $content_only} content_only{/if} lang_{$lang_iso}">
{if !isset($content_only) || !$content_only}

{if isset($restricted_country_mode) && $restricted_country_mode}
  <div id="restricted-country">
    <p>{l s='You cannot place a new order from your country.'}{if isset($geolocation_country) && $geolocation_country} <span class="bold">{$geolocation_country|escape:'html':'UTF-8'}</span>{/if}</p>
  </div>
{/if}

<header id="header">

  {capture name='displayBanner'}{hook h='displayBanner'}{/capture}
  {if $smarty.capture.displayBanner}
    <div id="header-banners">
      {$smarty.capture.displayBanner}
    </div>
  {/if}

  <nav class="navbar navbar-inverse">
    <div class="container">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#header-navbar" aria-expanded="false">
          <span class="sr-only">{l s='Toggle navigation'}</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl}{else}{$base_dir}{/if}">{$PS_SHOP_NAME|escape:'html':'UTF-8'}</a>
      </div>

      <div class="collapse navbar-collapse" id="header-navbar">
        {* {capture name='displayHeaderNavbarLeftNav'}{hook h='displayHeaderNavbarLeftNav'}{/capture}
        {if $smarty.capture.displayHeaderNavbarLeftNav}
          <ul id="header-navbar-left-nav" class="nav navbar-nav">
            {$smarty.capture.displayHeaderNavbarLeftNav}
          </ul>
        {/if} *}
        {* hook h='displayHeaderNavbar' *}
        {capture name='displayNav'}{hook h='displayNav'}{/capture}
        {if $smarty.capture.displayNav}
          <ul id="header-navbar-right-nav" class="nav navbar-nav navbar-right">
            {$smarty.capture.displayNav}
          </ul>
        {/if}
      </div>
    </div>
  </nav>

  <div id="header-blocks" class="container">
    <div class="row">
      <div id="shop-logo" class="col-sm-4">
        <a href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl}{else}{$base_dir}{/if}" title="{$shop_name|escape:'html':'UTF-8'}">
          <img class="img-responsive center-block" src="{$logo_url}" alt="{$shop_name|escape:'html':'UTF-8'}"{if isset($logo_image_width) && $logo_image_width} width="{$logo_image_width}"{/if}{if isset($logo_image_height) && $logo_image_height} height="{$logo_image_height}"{/if}/>
        </a>
      </div>
      {if !empty($HOOK_TOP)}{$HOOK_TOP}{/if}
    </div>
  </div>

</header>

<div id="columns" class="container">
  {if $page_name !='index' && $page_name !='pagenotfound'}
    {include file="$tpl_dir./breadcrumb.tpl"}
  {/if}
  {capture name='displayTopColumn'}{hook h='displayTopColumn'}{/capture}
  {if !empty($smarty.capture.displayTopColumn)}
    <div id="top_column" class="row">{$smarty.capture.displayTopColumn}</div>
  {/if}
  <div class="row">
    {if isset($left_column_size) && !empty($left_column_size)}
      <aside id="left_column" class="col-xs-12 col-sm-{$left_column_size|intval}">{$HOOK_LEFT_COLUMN}</aside>
    {/if}
    {if isset($left_column_size) && isset($right_column_size)}{assign var='cols' value=(12 - $left_column_size - $right_column_size)}{else}{assign var='cols' value=12}{/if}
    <main id="center_column" class="col-xs-12 col-sm-{$cols|intval}">
{/if}
