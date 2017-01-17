{if isset($orderby) AND isset($orderway)}

  {include file='./product-list-switcher.tpl'}

  {* On 1.5 the var request is setted on the front controller. The next lines assure the retrocompatibility with some modules *}
  {if !isset($request)}
    {if isset($smarty.get.id_category) && $smarty.get.id_category}
      {assign var='request' value=$link->getPaginationLink('category', $category, false, true)}
    {elseif isset($smarty.get.id_manufacturer) && $smarty.get.id_manufacturer}
      {assign var='request' value=$link->getPaginationLink('manufacturer', $manufacturer, false, true)}
    {elseif isset($smarty.get.id_supplier) && $smarty.get.id_supplier}
      {assign var='request' value=$link->getPaginationLink('supplier', $supplier, false, true)}
    {else}
      {assign var='request' value=$link->getPaginationLink(false, false, false, true)}
    {/if}
  {/if}

  {if $page_name == 'best-sales' && (!isset($smarty.get.orderby) || empty($smarty.get.orderby))}
    {$orderby = ''}
    {$orderbydefault = ''}
  {/if}

  <div id="productsSortForm{if isset($paginationId)}_{$paginationId}{/if}" class="form-group productsSortForm">
    <form action="{$request|escape:'html':'UTF-8'}">
      <label for="selectProductSort{if isset($paginationId)}_{$paginationId}{/if}">{l s='Sort by'}</label>
      <select id="selectProductSort{if isset($paginationId)}_{$paginationId}{/if}" class="selectProductSort form-control">
        <option value="{if $page_name != 'best-sales'}{$orderbydefault|escape:'html':'UTF-8'}:{$orderwaydefault|escape:'html':'UTF-8'}{/if}"{if !in_array($orderby, array('price', 'name', 'quantity', 'reference')) && $orderby eq $orderbydefault} selected="selected"{/if}>--</option>
        {if !$PS_CATALOG_MODE}
          <option value="price:asc"{if $orderby eq 'price' AND $orderway eq 'asc'} selected="selected"{/if}>{l s='Price: Lowest first'}</option>
          <option value="price:desc"{if $orderby eq 'price' AND $orderway eq 'desc'} selected="selected"{/if}>{l s='Price: Highest first'}</option>
        {/if}
        <option value="name:asc"{if $orderby eq 'name' AND $orderway eq 'asc'} selected="selected"{/if}>{l s='Product Name: A to Z'}</option>
        <option value="name:desc"{if $orderby eq 'name' AND $orderway eq 'desc'} selected="selected"{/if}>{l s='Product Name: Z to A'}</option>
        {if $PS_STOCK_MANAGEMENT && !$PS_CATALOG_MODE}
          <option value="quantity:desc"{if $orderby eq 'quantity' AND $orderway eq 'desc'} selected="selected"{/if}>{l s='In stock'}</option>
        {/if}
        <option value="reference:asc"{if $orderby eq 'reference' AND $orderway eq 'asc'} selected="selected"{/if}>{l s='Reference: Lowest first'}</option>
        <option value="reference:desc"{if $orderby eq 'reference' AND $orderway eq 'desc'} selected="selected"{/if}>{l s='Reference: Highest first'}</option>
      </select>
    </form>
  </div>

  {if !isset($paginationId) || $paginationId == ''}
    {addJsDef request=$request}
  {/if}

{/if}
