<tr id="product_{$product.id_product}_{$product.id_product_attribute}_0_{$product.id_address_delivery|intval}" class="cart_item {if $odd}odd{else}even{/if}">
  <td class="cart_product">
    <a href="{$link->getProductLink($product.id_product, $product.link_rewrite, $product.category)|escape:'html':'UTF-8'}"><img src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'small_default')|escape:'html':'UTF-8'}" alt="{$product.name|escape:'html':'UTF-8'}" {if isset($smallSize)}width="{$smallSize.width}" height="{$smallSize.height}" {/if} /></a>
  </td>
  <td class="cart_description">
    <p class="product-name"><a href="{$link->getProductLink($product.id_product, $product.link_rewrite, $product.category)|escape:'html':'UTF-8'}">{$product.name|escape:'html':'UTF-8'}</a></p>
    {if isset($product.attributes) && $product.attributes}<small><a href="{$link->getProductLink($product.id_product, $product.link_rewrite, $product.category)|escape:'html':'UTF-8'}">{$product.attributes|escape:'html':'UTF-8'}</a></small>{/if}
  </td>
  <td class="cart_ref">{if $product.reference}{$product.reference|escape:'html':'UTF-8'}{else}--{/if}</td>
  <td class="cart_avail">{if $product.stock_quantity > 0}<span class="label label-success">{l s='In Stock'}</span>{else}<span class="label label-warning">{l s='Out of Stock'}</span>{/if}</td>
  <td class="cart_quantity {if isset($customizedDatas.$productId.$productAttributeId) AND $quantityDisplayed == 0} text-center {/if}">
    {if isset($cannotModify) AND $cannotModify == 1}
      <span>{if $quantityDisplayed == 0 AND isset($customizedDatas.$productId.$productAttributeId)}{$customizedDatas.$productId.$productAttributeId|@count}{else}{$product.cart_quantity-$quantityDisplayed}{/if}</span>
    {else}
      {if !isset($customizedDatas.$productId.$productAttributeId) OR $quantityDisplayed > 0}
        <input type="hidden" value="{if $quantityDisplayed == 0 AND isset($customizedDatas.$productId.$productAttributeId)}{$customizedDatas.$productId.$productAttributeId|@count}{else}{$product.cart_quantity-$quantityDisplayed}{/if}" name="quantity_{$product.id_product}_{$product.id_product_attribute}_0_{$product.id_address_delivery|intval}_hidden" />
        <input size="2" type="text" class="cart_quantity_input form-control text-center" value="{if $quantityDisplayed == 0 AND isset($customizedDatas.$productId.$productAttributeId)}{$customizedDatas.$productId.$productAttributeId|@count}{else}{$product.cart_quantity-$quantityDisplayed}{/if}"  name="quantity_{$product.id_product}_{$product.id_product_attribute}_0_{$product.id_address_delivery|intval}" />
        <div class="cart_quantity_button">
          {if $product.minimal_quantity < ($product.cart_quantity-$quantityDisplayed) OR $product.minimal_quantity <= 1}
            <a rel="nofollow" class="cart_quantity_down btn btn-default button-minus" id="cart_quantity_down_{$product.id_product}_{$product.id_product_attribute}_0_{$product.id_address_delivery|intval}" href="{$link->getPageLink('cart', true, NULL, "add=1&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;id_address_delivery={$product.id_address_delivery|intval}&amp;op=down&amp;token={$token_cart}")|escape:'html':'UTF-8'}" title="{l s='Subtract'}"><i class="icon icon-fw icon-minus"></i></a>
          {else}
            <a class="cart_quantity_down btn btn-default button-minus disabled" href="#" id="cart_quantity_down_{$product.id_product}_{$product.id_product_attribute}_0_{$product.id_address_delivery|intval}" title="{l s='You must purchase a minimum of %d of this product.' sprintf=$product.minimal_quantity}"><i class="icon icon-fw icon-minus"></i></a>
          {/if}
          <a rel="nofollow" class="cart_quantity_up btn btn-default button-plus" id="cart_quantity_up_{$product.id_product}_{$product.id_product_attribute}_0_{$product.id_address_delivery|intval}" href="{$link->getPageLink('cart', true, NULL, "add=1&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;id_address_delivery={$product.id_address_delivery|intval}&amp;token={$token_cart}")|escape:'html':'UTF-8'}" title="{l s='Add'}"><i class="icon icon-fw icon-plus"></i></a>
        </div>
      {/if}
    {/if}
  </td>
  <td>
    <form method="post" action="{$link->getPageLink('cart', true, NULL, "token={$token_cart}")|escape:'html':'UTF-8'}">
      <div class="selector2">
        <input type="hidden" name="id_product" value="{$product.id_product}" />
        <input type="hidden" name="id_product_attribute" value="{$product.id_product_attribute}" />
        <select name="address_delivery" id="select_address_delivery_{$product.id_product}_{$product.id_product_attribute}_{$product.id_address_delivery|intval}" class="cart_address_delivery form-control">
          {if $product.id_address_delivery == 0 && $delivery->id == 0}
            <option></option>
          {/if}
          <option value="-1">{l s='Create a new address'}</option>
          {foreach $address_list as $address}
            <option value="{$address.id_address}"
              {if ($product.id_address_delivery > 0 && $product.id_address_delivery == $address.id_address) || ($product.id_address_delivery == 0  && $address.id_address == $delivery->id)}
                selected="selected"
              {/if}
            >
              {$address.alias}
            </option>
          {/foreach}
          <option value="-2">{l s='Ship to multiple addresses'}</option>
        </select>
      </div>
    </form>
  </td>
</tr>
