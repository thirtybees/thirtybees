<div id="blockcart-dropdown" class="cart_block" style="display: none;">
  <div class="cart_block_list">
    {if $products}
      <dl class="products">
        {foreach from=$products item='product'}

          {assign var='productId' value=$product.id_product}
          {assign var='productAttributeId' value=$product.id_product_attribute}

          <dt data-id="cart_block_product_{$product.id_product|intval}_{if $product.id_product_attribute}{$product.id_product_attribute|intval}{else}0{/if}_{if $product.id_address_delivery}{$product.id_address_delivery|intval}{else}0{/if}" class="clearfix">
            <a class="cart-images" href="{$link->getProductLink($product.id_product, $product.link_rewrite, $product.category)|escape:'html':'UTF-8'}" title="{$product.name|escape:'html':'UTF-8'}">
              <img src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'cart_default')}" alt="{$product.name|escape:'html':'UTF-8'}" />
            </a>
            <div class="cart-info">
              <div class="product-name">
                <span class="quantity-formatted">
                  <span class="quantity">{$product.cart_quantity}</span> &times;
                </span>
                <a class="cart_block_product_name" href="{$link->getProductLink($product, $product.link_rewrite, $product.category, null, null, $product.id_shop, $product.id_product_attribute)|escape:'html':'UTF-8'}" title="{$product.name|escape:'html':'UTF-8'}">
                  {$product.name|truncate:13:'...'|escape:'html':'UTF-8'}
                </a>
              </div>
              {if isset($product.attributes_small)}
                <div class="product-attributes">
                  <a href="{$link->getProductLink($product, $product.link_rewrite, $product.category, null, null, $product.id_shop, $product.id_product_attribute)|escape:'html':'UTF-8'}" title="{l s='Product detail' mod='blockcart'}">{$product.attributes_small}</a>
                </div>
              {/if}
              <span class="price">
                {if !isset($product.is_gift) || !$product.is_gift}
                  {if $priceDisplay == $smarty.const.PS_TAX_EXC}{displayWtPrice p="`$product.total`"}{else}{displayWtPrice p="`$product.total_wt`"}{/if}
                  <span class="hookDisplayProductPriceBlock-price">
                    {hook h="displayProductPriceBlock" product=$product type="price" from="blockcart"}
                  </span>
                {else}
                  {l s='Free!' mod='blockcart'}
                {/if}
              </span>
            </div>
            <span class="remove_link">
              {if !isset($customizedDatas.$productId.$productAttributeId) && (!isset($product.is_gift) || !$product.is_gift)}
                <a class="ajax_cart_block_remove_link" href="{$link->getPageLink('cart', true, NULL, "delete=1&id_product={$product.id_product|intval}&ipa={$product.id_product_attribute|intval}&id_address_delivery={$product.id_address_delivery|intval}&token={$static_token}")|escape:'html':'UTF-8'}" rel="nofollow" title="{l s='remove this product from my cart' mod='blockcart'}">
                  <i class="icon icon-times"></i>
                </a>
              {/if}
            </span>
          </dt>

          {if isset($product.attributes_small)}
            <dd data-id="cart_block_combination_of_{$product.id_product|intval}{if $product.id_product_attribute}_{$product.id_product_attribute|intval}{/if}_{$product.id_address_delivery|intval}">
          {/if}

          {if isset($customizedDatas.$productId.$productAttributeId[$product.id_address_delivery])}

            {if !isset($product.attributes_small)}
              <dd data-id="cart_block_combination_of_{$product.id_product|intval}_{if $product.id_product_attribute}{$product.id_product_attribute|intval}{else}0{/if}_{if $product.id_address_delivery}{$product.id_address_delivery|intval}{else}0{/if}">
            {/if}

            <ul class="cart_block_customizations list-unstyled" data-id="customization_{$productId}_{$productAttributeId}">
              {foreach from=$customizedDatas.$productId.$productAttributeId[$product.id_address_delivery] key='id_customization' item='customization' name='customizations'}
                <li name="customization">
                  <div data-id="deleteCustomizableProduct_{$id_customization|intval}_{$product.id_product|intval}_{$product.id_product_attribute|intval}_{$product.id_address_delivery|intval}" class="deleteCustomizableProduct">
                    <a class="ajax_cart_block_remove_link" href="{$link->getPageLink('cart', true, NULL, "delete=1&id_product={$product.id_product|intval}&ipa={$product.id_product_attribute|intval}&id_customization={$id_customization|intval}&token={$static_token}")|escape:'html':'UTF-8'}" rel="nofollow">
                      <i class="icon icon-times"></i>
                    </a>
                  </div>
                  {if isset($customization.datas.$CUSTOMIZE_TEXTFIELD.0)}
                    {$customization.datas.$CUSTOMIZE_TEXTFIELD[0].value|replace:"<br />":" "|truncate:28:'...'|escape:'html':'UTF-8'}
                  {else}
                    {l s='Customization #%d:' sprintf=$id_customization|intval mod='blockcart'}
                  {/if}
                </li>
              {/foreach}
            </ul>

            {if !isset($product.attributes_small)}</dd>{/if}

          {/if}

          {if isset($product.attributes_small)}</dd>{/if}
        {/foreach}
      </dl>
    {/if}

    <p class="cart_block_no_products"{if $products} style="display: none;"{/if}>
      {l s='No products' mod='blockcart'}
    </p>

    {if !empty($discounts)}
      <table class="table vouchers">
        {foreach from=$discounts item=discount}
          {if $discount.value_real > 0}
            <tr class="bloc_cart_voucher" data-id="bloc_cart_voucher_{$discount.id_discount|intval}">
              <td class="quantity">1 x</td>
              <td class="name" title="{$discount.description}">
                {$discount.name|truncate:18:'...'|escape:'html':'UTF-8'}
              </td>
              <td class="price">
                -{if $priceDisplay == 1}{convertPrice price=$discount.value_tax_exc}{else}{convertPrice price=$discount.value_real}{/if}
              </td>
              <td class="delete">
                {if strlen($discount.code)}
                  <a class="delete_voucher" href="{$link->getPageLink("$order_process", true)}?deleteDiscount={$discount.id_discount|intval}" title="{l s='Delete' mod='blockcart'}" rel="nofollow">
                    <i class="icon icon-times"></i>
                  </a>
                {/if}
              </td>
            </tr>
          {/if}
        {/foreach}
      </table>
    {/if}

    {assign var='free_ship' value=count($cart->getDeliveryAddressesWithoutCarriers(true, $errors))}

    <div class="cart-prices">

      <div class="cart-prices-line" {if !($page_name == 'order-opc') && $shipping_cost_float == 0 && (!$cart_qties || $cart->isVirtualCart() || !isset($cart->id_address_delivery) || !$cart->id_address_delivery || $free_ship)} style="display: none;"{/if}>
        <span>{l s='Shipping' mod='blockcart'}</span>
        <span class="price cart_block_shipping_cost ajax_cart_shipping_cost">
          {if $shipping_cost_float == 0}
            {if !($page_name == 'order-opc') && (!isset($cart->id_address_delivery) || !$cart->id_address_delivery)}{l s='To be determined' mod='blockcart'}{else}{l s='Free shipping!' mod='blockcart'}{/if}
          {else}
            {$shipping_cost}
          {/if}
        </span>
      </div>

      {if $show_wrapping}
        <div class="cart-prices-line">
          {assign var='cart_flag' value='Cart::ONLY_WRAPPING'|constant}
          <span>{l s='Wrapping' mod='blockcart'}</span>
          <span class="price ajax_block_wrapping_cost cart_block_wrapping_cost">
            {if $priceDisplay == 1}
              {convertPrice price=$cart->getOrderTotal(false, $cart_flag)}{else}{convertPrice price=$cart->getOrderTotal(true, $cart_flag)}
            {/if}
          </span>
        </div>
      {/if}

      {if $show_tax && isset($tax_cost)}
        <div class="cart-prices-line">
          <span>{l s='Tax' mod='blockcart'}</span>
          <span class="price cart_block_tax_cost ajax_cart_tax_cost">{$tax_cost}</span>
        </div>
      {/if}

      <div class="cart-prices-line">
        <span>{l s='Total' mod='blockcart'}</span>
        <span class="price cart_block_total ajax_block_cart_total">{$total}</span>
      </div>

      {if $use_taxes && $display_tax_label && $show_tax}
        <div class="cart-prices-line">
          {if $priceDisplay == 0}
            {l s='Prices are tax included' mod='blockcart'}
          {elseif $priceDisplay == 1}
            {l s='Prices are tax excluded' mod='blockcart'}
          {/if}
        </div>
      {/if}

    </div>

    <div class="cart-buttons">
      <a id="button_order_cart" class="btn btn-block btn-success" href="{$link->getPageLink("$order_process", true)|escape:"html":"UTF-8"}" title="{l s='Check out' mod='blockcart'}" rel="nofollow">
        {l s='Check out' mod='blockcart'} <i class="icon icon-angle-right"></i>
      </a>
    </div>

  </div>
</div>
