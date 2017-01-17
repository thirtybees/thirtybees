{include file="$tpl_dir./errors.tpl"}

{if empty($errors)}

  {if !isset($priceDisplayPrecision)}
    {assign var='priceDisplayPrecision' value=2}
  {/if}
  {if !$priceDisplay || $priceDisplay == 2}
    {assign var='productPrice' value=$product->getPrice(true, $smarty.const.NULL, 6)}
    {assign var='productPriceWithoutReduction' value=$product->getPriceWithoutReduct(false, $smarty.const.NULL)}
  {elseif $priceDisplay == 1}
    {assign var='productPrice' value=$product->getPrice(false, $smarty.const.NULL, 6)}
    {assign var='productPriceWithoutReduction' value=$product->getPriceWithoutReduct(true, $smarty.const.NULL)}
  {/if}

  <div itemscope itemtype="https://schema.org/Product">
    <meta itemprop="url" content="{$link->getProductLink($product)}">
    <div class="primary_block row">

      {if isset($adminActionDisplay) && $adminActionDisplay}
        <div id="admin-action" class="container">
          <div class="alert alert-info">{l s='This product is not visible to your customers.'}
            <input type="hidden" id="admin-action-product-id" value="{$product->id}" />
            <a id="publish_button" class="btn btn-success" href="#">{l s='Publish'}</a>
            <a id="lnk_view" class="btn btn-warning" href="#">{l s='Back'}</a>
          </div>
          <p id="admin-action-result"></p>
        </div>
      {/if}

      {if !empty($confirmation)}
        <div class="alert alert-warning">{$confirmation}</div>
      {/if}

      <div class="pb-left-column col-xs-12 col-sm-4 col-md-5">

        <div id="image-block" class="thumbnail clearfix">

          <div class="product-label-container">
            {if $product->online_only}
              <span class="product-label product-label-online">{l s='Online only'}</span>
            {/if}
            {if $product->new}
              <span class="product-label product-label-new">{l s='New'}</span>
            {/if}
            {if $product->on_sale}
              <span class="product-label product-label-sale">{l s='Sale!'}</span>
            {elseif $product->specificPrice && $product->specificPrice.reduction && $productPriceWithoutReduction > $productPrice}
              <span class="product-label product-label-discount">{l s='Reduced price!'}</span>
            {/if}
          </div>

          {if $have_image}
            <span id="view_full_size">
              <img id="bigpic" class="img-responsive center-block" itemprop="image" src="{$link->getImageLink($product->link_rewrite, $cover.id_image, 'large_default')|escape:'html':'UTF-8'}" title="{if !empty($cover.legend)}{$cover.legend|escape:'html':'UTF-8'}{else}{$product->name|escape:'html':'UTF-8'}{/if}" alt="{if !empty($cover.legend)}{$cover.legend|escape:'html':'UTF-8'}{else}{$product->name|escape:'html':'UTF-8'}{/if}" width="{$largeSize.width}" height="{$largeSize.height}"/>
              {if !$jqZoomEnabled && !$content_only}
                <span class="span_link" title="{l s='Zoom in'}">
                  <i class="icon icon-search-plus"></i>
                </span>
              {/if}
            </span>
          {else}
            <span id="view_full_size">
              <img id="bigpic" class="img-responsive center-block" itemprop="image" src="{$img_prod_dir}{$lang_iso}-default-large_default.jpg" title="{$product->name|escape:'html':'UTF-8'}" width="{$largeSize.width}" height="{$largeSize.height}"/>
            </span>
          {/if}
        </div>

        {if !empty($images)}
          <div id="views_block" class="clearfix {if isset($images) && count($images) < 2}hidden{/if}">
            <div id="thumbs_list">
              <ul id="thumbs_list_frame" class="list-unstyled row">
                {if isset($images)}
                  {foreach from=$images item=image name=thumbnails}

                    {assign var=imageIds value="`$product->id`-`$image.id_image`"}
                    {if !empty($image.legend)}
                      {assign var=imageTitle value=$image.legend|escape:'html':'UTF-8'}
                    {else}
                      {assign var=imageTitle value=$product->name|escape:'html':'UTF-8'}
                    {/if}

                    <li data-slide-num="{$smarty.foreach.thumbnails.iteration|intval}" id="thumbnail_{$image.id_image}" class="col-xs-6 col-sm-4 col-md-3">
                      {if $jqZoomEnabled && $have_image && !$content_only}
                        <a href="{$link->getImageLink($product->link_rewrite, $imageIds, 'thickbox_default')|escape:'html':'UTF-8'}" class="thumbnail {if $image.id_image == $cover.id_image} shown{/if}" title="{$imageTitle}">
                          <img class="img-responsive" id="thumb_{$image.id_image}" src="{$link->getImageLink($product->link_rewrite, $imageIds, 'cart_default')|escape:'html':'UTF-8'}" alt="{$imageTitle}" title="{$imageTitle}"{if isset($cartSize)} height="{$cartSize.height}" width="{$cartSize.width}"{/if} itemprop="image" />
                        </a>
                      {else}
                        <a href="{$link->getImageLink($product->link_rewrite, $imageIds, 'thickbox_default')|escape:'html':'UTF-8'}" data-fancybox-group="other-views" class="thumbnail fancybox{if $image.id_image == $cover.id_image} shown{/if}" title="{$imageTitle}">
                          <img class="img-responsive" id="thumb_{$image.id_image}" src="{$link->getImageLink($product->link_rewrite, $imageIds, 'cart_default')|escape:'html':'UTF-8'}" alt="{$imageTitle}" title="{$imageTitle}"{if isset($cartSize)} height="{$cartSize.height}" width="{$cartSize.width}"{/if} itemprop="image" />
                        </a>
                      {/if}
                    </li>
                  {/foreach}
                {/if}
              </ul>
            </div>
          </div>
        {/if}

      </div>

      <div class="pb-center-column col-xs-12 col-sm-4">

        <h1 itemprop="name">{$product->name|escape:'html':'UTF-8'}</h1>
        <p id="product_reference"{if empty($product->reference) || !$product->reference} style="display: none;"{/if}>
          <b>{l s='Reference:'}</b>
          <span class="editable" itemprop="sku"{if !empty($product->reference) && $product->reference} content="{$product->reference}"{/if}>{if !isset($groups)}{$product->reference|escape:'html':'UTF-8'}{/if}</span>
        </p>

        {if !$product->is_virtual && $product->condition}
          <p id="product_condition">
            <b>{l s='Condition:'}</b>
            {if $product->condition == 'new'}
              <link itemprop="itemCondition" href="https://schema.org/NewCondition"/>
              <span class="editable">{l s='New product'}</span>
            {elseif $product->condition == 'used'}
              <link itemprop="itemCondition" href="https://schema.org/UsedCondition"/>
              <span class="editable">{l s='Used'}</span>
            {elseif $product->condition == 'refurbished'}
              <link itemprop="itemCondition" href="https://schema.org/RefurbishedCondition"/>
              <span class="editable">{l s='Refurbished'}</span>
            {/if}
          </p>
        {/if}

        {if $product->id_manufacturer > 0}
          <p itemprop="brand" itemscope itemtype="https://schema.org/Brand">
            <b>{l s='Manufacturer:'}</b>
            <a itemprop="url" href="{$link->getManufacturerLink($product->id_manufacturer)}" title="{$product->manufacturer_name}">
              <span itemprop="name">{$product->manufacturer_name}</span>
            </a>
          </p>
        {/if}

        {if $product->description_short}
          <div id="short_description_block">
            <div id="short_description_content" class="rte" itemprop="description">{$product->description_short}</div>
          </div>
        {/if}

        <p id="product-availability">
          {if ($display_qties == 1 && !$PS_CATALOG_MODE && $PS_STOCK_MANAGEMENT && $product->available_for_order)}
          <span id="pQuantityAvailable"{if $product->quantity <= 0} style="display: none;"{/if}>
            <span id="quantityAvailable">{$product->quantity|intval}</span>
            <span {if $product->quantity > 1} style="display: none;"{/if} id="quantityAvailableTxt">{l s='Item'}</span>
            <span {if $product->quantity == 1} style="display: none;"{/if} id="quantityAvailableTxtMultiple">{l s='Items'}</span>
          </span>
          {/if}

          <span id="availability_statut"{if !$PS_STOCK_MANAGEMENT || ($product->quantity <= 0 && !$product->available_later && $allow_oosp) || ($product->quantity > 0 && !$product->available_now) || !$product->available_for_order || $PS_CATALOG_MODE} style="display: none;"{/if}>
            {*<span id="availability_label"><b>{l s='Availability:'}<b></span>*}
            <span id="availability_value" class="label{if $product->quantity <= 0 && !$allow_oosp} label-danger{elseif $product->quantity <= 0} label-warning{else} label-success{/if}">{if $product->quantity <= 0}{if $PS_STOCK_MANAGEMENT && $allow_oosp}{$product->available_later}{else}{l s='This product is no longer in stock'}{/if}{elseif $PS_STOCK_MANAGEMENT}{$product->available_now}{/if}</span>
          </span>

          {if $PS_STOCK_MANAGEMENT}
            <span class="label label-warning" id="last_quantities"{if ($product->quantity > $last_qties || $product->quantity <= 0) || $allow_oosp || !$product->available_for_order || $PS_CATALOG_MODE} style="display: none"{/if} >{l s='Warning: Last items in stock!'}</span>
          {/if}
        </p>

        {if $PS_STOCK_MANAGEMENT}
          {if !$product->is_virtual}{hook h="displayProductDeliveryTime" product=$product}{/if}
        {/if}

        <p id="availability_date"{if ($product->quantity > 0) || !$product->available_for_order || $PS_CATALOG_MODE || !isset($product->available_date) || $product->available_date < $smarty.now|date_format:'%Y-%m-%d'} style="display: none;"{/if}>
          <span id="availability_date_label"><b>{l s='Availability date:'}</b></span>
          <span id="availability_date_value">{if Validate::isDate($product->available_date)}{dateFormat date=$product->available_date full=false}{/if}</span>
        </p>

        <div id="oosHook"{if $product->quantity > 0} style="display: none;"{/if}>
          {$HOOK_PRODUCT_OOS}
        </div>

        {if isset($HOOK_EXTRA_RIGHT) && $HOOK_EXTRA_RIGHT}{$HOOK_EXTRA_RIGHT}{/if}

        {if !$content_only}
          <ul id="usefull_link_block" class="list-unstyled hidden-print">
            {if !empty($HOOK_EXTRA_LEFT)}{$HOOK_EXTRA_LEFT}{/if}
            <li>
              <a href="javascript:print();" title="{l s='Print this page'}">
                <i class="icon icon-fw icon-print"></i> {l s='Print'}
              </a>
            </li>
          </ul>
        {/if}
      </div>

      <div class="pb-right-column col-xs-12 col-sm-4 col-md-3">
        {if ($product->show_price && !isset($restricted_country_mode)) || isset($groups) || $product->reference || (isset($HOOK_PRODUCT_ACTIONS) && $HOOK_PRODUCT_ACTIONS)}

          <form id="buy_block"{if $PS_CATALOG_MODE && !isset($groups) && $product->quantity > 0} class="hidden"{/if} action="{$link->getPageLink('cart')|escape:'html':'UTF-8'}" method="post">

            <input type="hidden" name="token" value="{$static_token}" />
            <input type="hidden" name="id_product" value="{$product->id|intval}" id="product_page_product_id" />
            <input type="hidden" name="add" value="1" />
            <input type="hidden" name="id_product_attribute" id="idCombination" value="" />

            <div class="box-info-product">
              <div class="content_prices clearfix">
                {if $product->show_price && !isset($restricted_country_mode) && !$PS_CATALOG_MODE}
                  <div>
                    <p class="our_price_display" itemprop="offers" itemscope itemtype="https://schema.org/Offer">{strip}
                        {if $product->quantity > 0}<link itemprop="availability" href="https://schema.org/InStock"/>{/if}
                        {if $priceDisplay >= 0 && $priceDisplay <= 2}
                          <span id="our_price_display" class="price" itemprop="price" content="{$productPrice}">{convertPrice price=$productPrice|floatval}</span>
                          {if $tax_enabled  && ((isset($display_tax_label) && $display_tax_label == 1) || !isset($display_tax_label))}
                            {if $priceDisplay == 1} {l s='tax excl.'}{else} {l s='tax incl.'}{/if}
                          {/if}
                          <meta itemprop="priceCurrency" content="{$currency->iso_code}" />
                          {hook h="displayProductPriceBlock" product=$product type="price"}
                        {/if}
                      {/strip}</p>
                    <p id="reduction_percent" {if $productPriceWithoutReduction <= 0 || !$product->specificPrice || $product->specificPrice.reduction_type != 'percentage'} style="display:none;"{/if}>{strip}
                        <span id="reduction_percent_display">
                          {if $product->specificPrice && $product->specificPrice.reduction_type == 'percentage'}-{$product->specificPrice.reduction*100}%{/if}
                        </span>
                      {/strip}</p>
                    <p id="reduction_amount" {if $productPriceWithoutReduction <= 0 || !$product->specificPrice || $product->specificPrice.reduction_type != 'amount' || $product->specificPrice.reduction|floatval ==0} style="display:none"{/if}>{strip}
                        <span id="reduction_amount_display">
                          {if $product->specificPrice && $product->specificPrice.reduction_type == 'amount' && $product->specificPrice.reduction|floatval !=0}
                            -{convertPrice price=$productPriceWithoutReduction|floatval-$productPrice|floatval}
                          {/if}
                        </span>
                      {/strip}</p>
                    <p id="old_price"{if (!$product->specificPrice || !$product->specificPrice.reduction)} class="hidden"{/if}>{strip}
                        {if $priceDisplay >= 0 && $priceDisplay <= 2}
                          {hook h="displayProductPriceBlock" product=$product type="old_price"}
                          <span id="old_price_display"><span class="price">{if $productPriceWithoutReduction > $productPrice}{convertPrice price=$productPriceWithoutReduction|floatval}{/if}</span>{if $productPriceWithoutReduction > $productPrice && $tax_enabled && $display_tax_label == 1} {if $priceDisplay == 1}{l s='tax excl.'}{else}{l s='tax incl.'}{/if}{/if}</span>
                        {/if}
                      {/strip}</p>
                    {if $priceDisplay == 2}
                      <br />
                      <span id="pretaxe_price">{strip}
                        <span id="pretaxe_price_display">{convertPrice price=$product->getPrice(false, $smarty.const.NULL)}</span> {l s='tax excl.'}
                      {/strip}</span>
                    {/if}
                  </div>
                  {if $packItems|@count && $productPrice < $product->getNoPackPrice()}
                    <p class="pack_price">{l s='Instead of'} <span style="text-decoration: line-through;">{convertPrice price=$product->getNoPackPrice()}</span></p>
                  {/if}
                  {if $product->ecotax != 0}
                    <p class="price-ecotax">{l s='Including'} <span id="ecotax_price_display">{if $priceDisplay == 2}{$ecotax_tax_exc|convertAndFormatPrice}{else}{$ecotax_tax_inc|convertAndFormatPrice}{/if}</span> {l s='for ecotax'}
                      {if $product->specificPrice && $product->specificPrice.reduction}
                        <br />{l s='(not impacted by the discount)'}
                      {/if}
                    </p>
                  {/if}
                  {if !empty($product->unity) && $product->unit_price_ratio > 0.000000}
                    {math equation="pprice / punit_price" pprice=$productPrice  punit_price=$product->unit_price_ratio assign=unit_price}
                    <p class="unit-price"><span id="unit_price_display">{convertPrice price=$unit_price}</span> {l s='per'} {$product->unity|escape:'html':'UTF-8'}</p>
                    {hook h="displayProductPriceBlock" product=$product type="unit_price"}
                  {/if}
                {/if} {*close if for show price*}
                {hook h="displayProductPriceBlock" product=$product type="weight" hook_origin='product_sheet'}
                {hook h="displayProductPriceBlock" product=$product type="after_price"}
              </div>

              <div class="product_attributes clearfix">

                {if !$PS_CATALOG_MODE}
                  <div id="quantity_wanted_p"{if (!$allow_oosp && $product->quantity <= 0) || !$product->available_for_order || $PS_CATALOG_MODE} style="display: none;"{/if}>
                    <label for="quantity_wanted">{l s='Quantity'}</label>
                    <div class="input-group">
                      <div class="input-group-btn">
                        <a href="#" data-field-qty="qty" class="btn btn-default button-minus product_quantity_down">
                          <i class="icon icon-fw icon-minus"></i>
                        </a>
                      </div>
                      <input type="number" min="1" name="qty" id="quantity_wanted" class="text text-center form-control" value="{if isset($quantityBackup)}{$quantityBackup|intval}{else}{if $product->minimal_quantity > 1}{$product->minimal_quantity}{else}1{/if}{/if}" />
                      <div class="input-group-btn">
                        <a href="#" data-field-qty="qty" class="btn btn-default button-plus product_quantity_up">
                          <i class="icon icon-fw icon-plus"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                {/if}

                <p id="minimal_quantity_wanted_p"{if $product->minimal_quantity <= 1 || !$product->available_for_order || $PS_CATALOG_MODE} style="display: none;"{/if}>
                  {l s='The minimum purchase order quantity for the product is'} <b id="minimal_quantity_label">{$product->minimal_quantity}</b>
                </p>

                {if isset($groups)}
                  <div id="attributes">
                    {foreach from=$groups key=id_attribute_group item=group}
                      {if !empty($group.attributes)}
                        <fieldset class="attribute_fieldset form-group">
                          <label class="attribute_label" {if $group.group_type != 'color' && $group.group_type != 'radio'}for="group_{$id_attribute_group|intval}"{/if}>{$group.name|escape:'html':'UTF-8'}&nbsp;</label>
                          {assign var="groupName" value="group_$id_attribute_group"}
                          <div class="attribute_list">
                            {if ($group.group_type == 'select')}
                              <select name="{$groupName}" id="group_{$id_attribute_group|intval}" class="form-control attribute_select no-print">
                                {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                  <option value="{$id_attribute|intval}"{if (isset($smarty.get.$groupName) && $smarty.get.$groupName|intval == $id_attribute) || $group.default == $id_attribute} selected="selected"{/if} title="{$group_attribute|escape:'html':'UTF-8'}">{$group_attribute|escape:'html':'UTF-8'}</option>
                                {/foreach}
                              </select>
                            {elseif ($group.group_type == 'color')}
                              <ul id="color_to_pick_list">
                                {assign var="default_colorpicker" value=""}
                                {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                  {assign var='img_color_exists' value=file_exists($col_img_dir|cat:$id_attribute|cat:'.jpg')}
                                  <li{if $group.default == $id_attribute} class="selected"{/if}>
                                    <a href="{$link->getProductLink($product)|escape:'html':'UTF-8'}" id="color_{$id_attribute|intval}" name="{$colors.$id_attribute.name|escape:'html':'UTF-8'}" class="color_pick{if ($group.default == $id_attribute)} selected{/if}"{if !$img_color_exists && isset($colors.$id_attribute.value) && $colors.$id_attribute.value} style="background:{$colors.$id_attribute.value|escape:'html':'UTF-8'};"{/if} title="{$colors.$id_attribute.name|escape:'html':'UTF-8'}">
                                      {if $img_color_exists}
                                        <img src="{$img_col_dir}{$id_attribute|intval}.jpg" alt="{$colors.$id_attribute.name|escape:'html':'UTF-8'}" title="{$colors.$id_attribute.name|escape:'html':'UTF-8'}" width="20" height="20" />
                                      {/if}
                                    </a>
                                  </li>
                                  {if ($group.default == $id_attribute)}
                                    {$default_colorpicker = $id_attribute}
                                  {/if}
                                {/foreach}
                              </ul>
                              <input type="hidden" class="color_pick_hidden" name="{$groupName|escape:'html':'UTF-8'}" value="{$default_colorpicker|intval}" />
                            {elseif ($group.group_type == 'radio')}
                              <ul>
                                {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                  <li>
                                    <input type="radio" class="attribute_radio" name="{$groupName|escape:'html':'UTF-8'}" value="{$id_attribute}" {if ($group.default == $id_attribute)} checked="checked"{/if} />
                                    <span>{$group_attribute|escape:'html':'UTF-8'}</span>
                                  </li>
                                {/foreach}
                              </ul>
                            {/if}
                          </div>
                        </fieldset>
                      {/if}
                    {/foreach}
                  </div>
                {/if}
              </div>

              <div class="box-cart-bottom">
                <div{if (!$allow_oosp && $product->quantity <= 0) || !$product->available_for_order || (isset($restricted_country_mode) && $restricted_country_mode) || $PS_CATALOG_MODE} class="unvisible"{/if}>
                  <p id="add_to_cart" class="buttons_bottom_block no-print">
                    <button type="submit" name="Submit" class="btn btn-block btn-lg btn-success btn-add-to-cart">
                      <i class="icon icon-cart-plus"></i>
                      <span>{if $content_only && (isset($product->customization_required) && $product->customization_required)}{l s='Customize'}{else}{l s='Add to cart'}{/if}</span>
                    </button>
                  </p>
                </div>
                {if isset($HOOK_PRODUCT_ACTIONS) && $HOOK_PRODUCT_ACTIONS}{$HOOK_PRODUCT_ACTIONS}{/if}
              </div>

            </div>
          </form>
        {/if}
      </div>
    </div>

    {if !$content_only}
      {if !empty($quantity_discounts)}
        <section id="product-volume-discounts" class="page-product-box">
          <h3 class="page-product-heading">{l s='Volume discounts'}</h3>
          <div id="quantityDiscount" class="table-responsive">
            <table class="table-product-discounts table table-condensed table-bordered table-hover">
              <thead>
              <tr>
                <th>{l s='Quantity'}</th>
                <th>{if $display_discount_price}{l s='Price'}{else}{l s='Discount'}{/if}</th>
                <th>{l s='You Save'}</th>
              </tr>
              </thead>
              <tbody>
              {foreach from=$quantity_discounts item='quantity_discount' name='quantity_discounts'}
                {if $quantity_discount.price >= 0 || $quantity_discount.reduction_type == 'amount'}
                  {$realDiscountPrice=$productPriceWithoutReduction|floatval-$quantity_discount.real_value|floatval}
                {else}
                  {$realDiscountPrice=$productPriceWithoutReduction|floatval-($productPriceWithoutReduction*$quantity_discount.reduction)|floatval}
                {/if}
                <tr id="quantityDiscount_{$quantity_discount.id_product_attribute}" class="quantityDiscount_{$quantity_discount.id_product_attribute}" data-real-discount-value="{convertPrice price = $realDiscountPrice}" data-discount-type="{$quantity_discount.reduction_type}" data-discount="{$quantity_discount.real_value|floatval}" data-discount-quantity="{$quantity_discount.quantity|intval}">
                  <td>
                    {$quantity_discount.quantity|intval}
                  </td>
                  <td>
                    {if $quantity_discount.price >= 0 || $quantity_discount.reduction_type == 'amount'}
                      {if $display_discount_price}
                        {if $quantity_discount.reduction_tax == 0 && !$quantity_discount.price}
                          {convertPrice price = $productPriceWithoutReduction|floatval-($productPriceWithoutReduction*$quantity_discount.reduction_with_tax)|floatval}
                        {else}
                          {convertPrice price=$productPriceWithoutReduction|floatval-$quantity_discount.real_value|floatval}
                        {/if}
                      {else}
                        {convertPrice price=$quantity_discount.real_value|floatval}
                      {/if}
                    {else}
                      {if $display_discount_price}
                        {if $quantity_discount.reduction_tax == 0}
                          {convertPrice price = $productPriceWithoutReduction|floatval-($productPriceWithoutReduction*$quantity_discount.reduction_with_tax)|floatval}
                        {else}
                          {convertPrice price = $productPriceWithoutReduction|floatval-($productPriceWithoutReduction*$quantity_discount.reduction)|floatval}
                        {/if}
                      {else}
                        {$quantity_discount.real_value|floatval}%
                      {/if}
                    {/if}
                  </td>
                  <td>
                    <span>{l s='Up to'}</span>
                    {if $quantity_discount.price >= 0 || $quantity_discount.reduction_type == 'amount'}
                      {$discountPrice=$productPriceWithoutReduction|floatval-$quantity_discount.real_value|floatval}
                    {else}
                      {$discountPrice=$productPriceWithoutReduction|floatval-($productPriceWithoutReduction*$quantity_discount.reduction)|floatval}
                    {/if}
                    {$discountPrice=$discountPrice * $quantity_discount.quantity}
                    {$qtyProductPrice=$productPriceWithoutReduction|floatval * $quantity_discount.quantity}
                    {convertPrice price=$qtyProductPrice - $discountPrice}
                  </td>
                </tr>
              {/foreach}
              </tbody>
            </table>
          </div>
        </section>
      {/if}

      {if !empty($features)}
        <section id="product-features" class="page-product-box">
          <h3 class="page-product-heading">{l s='Data sheet'}</h3>
          <div class="table-responsive">
            <table class="table table-bordered table-condensed table-hover table-data-sheet">
              {foreach from=$features item=feature}
                <tr class="{cycle values="odd,even"}">
                  {if isset($feature.value)}
                    <td>{$feature.name|escape:'html':'UTF-8'}</td>
                    <td>{$feature.value|escape:'html':'UTF-8'}</td>
                  {/if}
                </tr>
              {/foreach}
            </table>
          </div>
        </section>
      {/if}

      {if isset($product) && $product->description}
        <section id="product-description" class="page-product-box">
          <h3 class="page-product-heading">{l s='More info'}</h3>
          <div  class="rte">{$product->description}</div>
        </section>
      {/if}

      {if isset($packItems) && $packItems|@count > 0}
        <section id="blockpack" class="page-product-box">
          <h3 class="page-product-heading">{l s='Pack content'}</h3>
          {include file="$tpl_dir./product-list.tpl" products=$packItems}
        </section>
      {/if}

      {if !empty($HOOK_PRODUCT_TAB_CONTENT) || !empty({$HOOK_PRODUCT_TAB})}
        <section id="product-hook-tab" class="page-product-box">
          {if !empty($HOOK_PRODUCT_TAB)}{$HOOK_PRODUCT_TAB}{/if}
          {if !empty($HOOK_PRODUCT_TAB_CONTENT)}{$HOOK_PRODUCT_TAB_CONTENT}{/if}
        </section>
      {/if}

      {if !empty($accessories)}
        <section id="product-accessories" class="page-product-box">
          <h3 class="page-product-heading">{l s='Accessories'}</h3>
          <div class="accessories-block">
            {include file="$tpl_dir./product-list.tpl" products=$accessories}
          </div>
        </section>
      {/if}

      {if isset($HOOK_PRODUCT_FOOTER) && $HOOK_PRODUCT_FOOTER}{$HOOK_PRODUCT_FOOTER}{/if}

      {if (isset($product) && $product->description) || (isset($features) && $features) || (isset($accessories) && $accessories) || (isset($HOOK_PRODUCT_TAB) && $HOOK_PRODUCT_TAB) || (isset($attachments) && $attachments) || isset($product) && $product->customizable}
        {if isset($attachments) && $attachments}
          <section id="product-attachments" class="page-product-box">
            <h3 class="page-product-heading">{l s='Download'}</h3>
            <div class="row">
            {foreach from=$attachments item=attachment}
              <div class="col-xs-12 col-sm-4 col-lg-3">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <a href="{$link->getPageLink('attachment', true, NULL, "id_attachment={$attachment.id_attachment}")|escape:'html':'UTF-8'}">
                      {$attachment.name|escape:'html':'UTF-8'}
                    </a>
                  </div>
                  <div class="panel-body">
                    {if !empty($attachment.description)}
                      <p class="text-muted">{$attachment.description|escape:'html':'UTF-8'}</p>
                    {/if}
                    <a class="btn btn-default btn-block" href="{$link->getPageLink('attachment', true, NULL, "id_attachment={$attachment.id_attachment}")|escape:'html':'UTF-8'}">
                      <i class="icon icon-download"></i> {l s="Download"} ({Tools::formatBytes($attachment.file_size, 2)})
                    </a>
                  </div>
                </div>
              </div>
            {/foreach}
            </div>
          </section>
        {/if}

        {if isset($product) && $product->customizable}
          <section id="product-customization" class="page-product-box">
            <h3 class="page-product-heading">{l s='Product customization'}</h3>
            <form method="post" action="{$customizationFormTarget}" enctype="multipart/form-data" id="customizationForm" class="clearfix">
              <p class="infoCustomizable">
                {l s='After saving your customized product, remember to add it to your cart.'}
                {if $product->uploadable_files}
                  <br> {l s='Allowed file formats are: GIF, JPG, PNG'}
                {/if}
              </p>

              {if $product->uploadable_files|intval}
                <div class="customizableProductsFile">
                  <h3>{l s='Pictures'}</h3>
                  <ul id="uploadable_files" class="list-unstyled clearfix">
                    {counter start=0 assign='customizationField'}
                    {foreach from=$customizationFields item='field' name='customizationFields'}
                      {if $field.type == 0}
                        <li class="customizationUploadLine form-group{if $field.required} required{/if}">{assign var='key' value='pictures_'|cat:$product->id|cat:'_'|cat:$field.id_customization_field}
                          {if isset($pictures.$key)}
                            <div class="customizationUploadBrowse">
                              <img src="{$pic_dir}{$pictures.$key}_small" alt="" />
                              <a href="{$link->getProductDeletePictureLink($product, $field.id_customization_field)|escape:'html':'UTF-8'}" title="{l s='Delete'}" >
                                <img src="{$img_dir}icon/delete.gif" alt="{l s='Delete'}" class="customization_delete_icon" width="11" height="13" />
                              </a>
                            </div>
                          {/if}
                          <div class="customizationUploadBrowse form-group">
                            <label class="customizationUploadBrowseDescription">
                              {if !empty($field.name)}
                                {$field.name}
                              {else}
                                {l s='Please select an image file from your computer'}
                              {/if}
                              {if $field.required}<sup>*</sup>{/if}
                            </label>
                            <input type="file" name="file{$field.id_customization_field}" id="img{$customizationField}" class="form-control customization_block_input {if isset($pictures.$key)}filled{/if}" />
                          </div>
                        </li>
                        {counter}
                      {/if}
                    {/foreach}
                  </ul>
                </div>
              {/if}

              {if $product->text_fields|intval}
                <div class="customizableProductsText">
                  <h3>{l s='Text'}</h3>
                  <ul id="text_fields" class="list-unstyled">
                    {counter start=0 assign='customizationField'}
                    {foreach from=$customizationFields item='field' name='customizationFields'}
                      {if $field.type == 1}
                        <li class="customizationUploadLine form-group{if $field.required} required{/if}">
                          <label for ="textField{$customizationField}">
                            {assign var='key' value='textFields_'|cat:$product->id|cat:'_'|cat:$field.id_customization_field}
                            {if !empty($field.name)}
                              {$field.name}
                            {/if}
                            {if $field.required}<sup>*</sup>{/if}
                          </label>
                          <textarea name="textField{$field.id_customization_field}" class="form-control customization_block_input" id="textField{$customizationField}" rows="3" cols="20">{strip}
                            {if isset($textFields.$key)}
                              {$textFields.$key|stripslashes}
                            {/if}
                          {/strip}</textarea>
                        </li>
                        {counter}
                      {/if}
                    {/foreach}
                  </ul>
                </div>
              {/if}
              <div id="customizedDatas" class="form-group">
                <input type="hidden" name="quantityBackup" id="quantityBackup" value="" />
                <input type="hidden" name="submitCustomizedDatas" value="1" />
                <button class="btn btn-lg btn-success" name="saveCustomization">
                  <span>{l s='Save'}</span>
                </button>
                <span id="ajax-loader" class="unvisible">
                 <img src="{$img_ps_dir}loader.gif" alt="loader" />
                </span>
              </div>
            </form>
            <div class="form-group">
              <div class="help-block">
                <sup>*</sup> {l s='required fields'}
              </div>
            </div>
          </section>
        {/if}

      {/if}
    {/if}
  </div>

  {strip}
    {if isset($smarty.get.ad) && $smarty.get.ad}
      {addJsDefL name=ad}{$base_dir|cat:$smarty.get.ad|escape:'html':'UTF-8'}{/addJsDefL}
    {/if}
    {if isset($smarty.get.adtoken) && $smarty.get.adtoken}
      {addJsDefL name=adtoken}{$smarty.get.adtoken|escape:'html':'UTF-8'}{/addJsDefL}
    {/if}
    {addJsDef allowBuyWhenOutOfStock=$allow_oosp|boolval}
    {addJsDef availableNowValue=$product->available_now|escape:'quotes':'UTF-8'}
    {addJsDef availableLaterValue=$product->available_later|escape:'quotes':'UTF-8'}
    {addJsDef attribute_anchor_separator=$attribute_anchor_separator|escape:'quotes':'UTF-8'}
    {addJsDef attributesCombinations=$attributesCombinations}
    {addJsDef currentDate=$smarty.now|date_format:'%Y-%m-%d %H:%M:%S'}
    {if isset($combinations) && $combinations}
      {addJsDef combinations=$combinations}
      {addJsDef combinationsFromController=$combinations}
      {addJsDef displayDiscountPrice=$display_discount_price}
      {addJsDefL name='upToTxt'}{l s='Up to' js=1}{/addJsDefL}
    {/if}
    {if isset($combinationImages) && $combinationImages}
      {addJsDef combinationImages=$combinationImages}
    {/if}
    {addJsDef customizationId=$id_customization}
    {addJsDef customizationFields=$customizationFields}
    {addJsDef default_eco_tax=$product->ecotax|floatval}
    {addJsDef displayPrice=$priceDisplay|intval}
    {addJsDef ecotaxTax_rate=$ecotaxTax_rate|floatval}
    {if isset($cover.id_image_only)}
      {addJsDef idDefaultImage=$cover.id_image_only|intval}
    {else}
      {addJsDef idDefaultImage=0}
    {/if}
    {addJsDef img_ps_dir=$img_ps_dir}
    {addJsDef img_prod_dir=$img_prod_dir}
    {addJsDef id_product=$product->id|intval}
    {addJsDef jqZoomEnabled=$jqZoomEnabled|boolval}
    {addJsDef maxQuantityToAllowDisplayOfLastQuantityMessage=$last_qties|intval}
    {addJsDef minimalQuantity=$product->minimal_quantity|intval}
    {addJsDef noTaxForThisProduct=$no_tax|boolval}
    {if isset($customer_group_without_tax)}
      {addJsDef customerGroupWithoutTax=$customer_group_without_tax|boolval}
    {else}
      {addJsDef customerGroupWithoutTax=false}
    {/if}
    {if isset($group_reduction)}
      {addJsDef groupReduction=$group_reduction|floatval}
    {else}
      {addJsDef groupReduction=false}
    {/if}
    {addJsDef oosHookJsCodeFunctions=Array()}
    {addJsDef productHasAttributes=isset($groups)|boolval}
    {addJsDef productPriceTaxExcluded=($product->getPriceWithoutReduct(true)|default:'null' - $product->ecotax)|floatval}
    {addJsDef productPriceTaxIncluded=($product->getPriceWithoutReduct(false)|default:'null' - $product->ecotax * (1 + $ecotaxTax_rate / 100))|floatval}
    {addJsDef productBasePriceTaxExcluded=($product->getPrice(false, null, 6, null, false, false) - $product->ecotax)|floatval}
    {addJsDef productBasePriceTaxExcl=($product->getPrice(false, null, 6, null, false, false)|floatval)}
    {addJsDef productBasePriceTaxIncl=($product->getPrice(true, null, 6, null, false, false)|floatval)}
    {addJsDef productReference=$product->reference|escape:'html':'UTF-8'}
    {addJsDef productAvailableForOrder=$product->available_for_order|boolval}
    {addJsDef productPriceWithoutReduction=$productPriceWithoutReduction|floatval}
    {addJsDef productPrice=$productPrice|floatval}
    {addJsDef productUnitPriceRatio=$product->unit_price_ratio|floatval}
    {addJsDef productShowPrice=(!$PS_CATALOG_MODE && $product->show_price)|boolval}
    {addJsDef PS_CATALOG_MODE=$PS_CATALOG_MODE}
    {if $product->specificPrice && $product->specificPrice|@count}
      {addJsDef product_specific_price=$product->specificPrice}
    {else}
      {addJsDef product_specific_price=array()}
    {/if}
    {if $display_qties == 1 && $product->quantity}
      {addJsDef quantityAvailable=$product->quantity}
    {else}
      {addJsDef quantityAvailable=0}
    {/if}
    {addJsDef quantitiesDisplayAllowed=$display_qties|boolval}
    {if $product->specificPrice && $product->specificPrice.reduction && $product->specificPrice.reduction_type == 'percentage'}
      {addJsDef reduction_percent=$product->specificPrice.reduction*100|floatval}
    {else}
      {addJsDef reduction_percent=0}
    {/if}
    {if $product->specificPrice && $product->specificPrice.reduction && $product->specificPrice.reduction_type == 'amount'}
      {addJsDef reduction_price=$product->specificPrice.reduction|floatval}
    {else}
      {addJsDef reduction_price=0}
    {/if}
    {if $product->specificPrice && $product->specificPrice.price}
      {addJsDef specific_price=$product->specificPrice.price|floatval}
    {else}
      {addJsDef specific_price=0}
    {/if}
    {addJsDef specific_currency=($product->specificPrice && $product->specificPrice.id_currency)|boolval} {* TODO: remove if always false *}
    {addJsDef stock_management=$PS_STOCK_MANAGEMENT|intval}
    {addJsDef taxRate=$tax_rate|floatval}
    {addJsDefL name=doesntExist}{l s='This combination does not exist for this product. Please select another combination.' js=1}{/addJsDefL}
    {addJsDefL name=doesntExistNoMore}{l s='This product is no longer in stock' js=1}{/addJsDefL}
    {addJsDefL name=doesntExistNoMoreBut}{l s='with those attributes but is available with others.' js=1}{/addJsDefL}
    {addJsDefL name=fieldRequired}{l s='Please fill in all the required fields before saving your customization.' js=1}{/addJsDefL}
    {addJsDefL name=uploading_in_progress}{l s='Uploading in progress, please be patient.' js=1}{/addJsDefL}
    {addJsDefL name='product_fileDefaultHtml'}{l s='No file selected' js=1}{/addJsDefL}
    {addJsDefL name='product_fileButtonHtml'}{l s='Choose File' js=1}{/addJsDefL}
  {/strip}
{/if}
