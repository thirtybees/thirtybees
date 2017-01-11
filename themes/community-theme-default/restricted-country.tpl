<!DOCTYPE html>
<html lang="en">
<head>
  <title>{$meta_title|escape:'html':'UTF-8'}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  {if isset($meta_description)}
    <meta name="description" content="{$meta_description|escape:'html':'UTF-8'}" />
  {/if}
  {if isset($meta_keywords)}
    <meta name="keywords" content="{$meta_keywords|escape:'html':'UTF-8'}" />
  {/if}
  <meta name="robots" content="{if isset($nobots)}no{/if}index,follow" />
  <link rel="shortcut icon" href="{$favicon_url}" />
  <style>
    ::-moz-selection {
      background: #b3d4fc;
      text-shadow: none;
    }

    ::selection {
      background: #b3d4fc;
      text-shadow: none;
    }

    html {
      padding: 30px 10px;
      font-size: 16px;
      line-height: 1.4;
      color: #737373;
      background: #f0f0f0;
      -webkit-text-size-adjust: 100%;
      -ms-text-size-adjust: 100%;
    }

    html,
    input {
      font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    }

    body {
      max-width:600px;
      _width: 600px;
      padding: 30px 20px 50px;
      border: 1px solid #b3b3b3;
      border-radius: 4px;
      margin: 0 auto;
      box-shadow: 0 1px 10px #a7a7a7, inset 0 1px 0 #fff;
      background: #fcfcfc;
    }

    h1 {
      margin: 0 10px;
      font-size: 50px;
      text-align: center;
    }

    h1 span {
      color: #bbb;
    }
    h2 {
      margin: 0 10px;
      font-size: 40px;
      text-align: center;
    }

    h2 span {
      color: #bbb;
      font-size: 60px;
    }

    h3 {
      margin: 1.5em 0 0.5em;
    }

    p {
      margin: 1em 0;
    }

    ul {
      padding: 0 0 0 40px;
      margin: 1em 0;
    }

    .container {
      max-width: 380px;
      _width: 380px;
      margin: 0 auto;
    }

    input::-moz-focus-inner {
      padding: 0;
      border: 0;
    }
  </style>
</head>
<body>
<div class="container">
  <h1>{$shop_name}</h1>
  <h2><span>503</span> Overloaded</h2>
  <p style="text-align:center;"><img src="{$logo_url}" alt="logo" /></p>
  <p>{l s='You cannot access this store from your country. We apologize for the inconvenience.'}</p>
</div>
</body>
</html>
