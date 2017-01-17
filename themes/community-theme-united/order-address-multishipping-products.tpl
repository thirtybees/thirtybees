<p><b>{l s='Choose the delivery addresses'}</b></p>
<div id="order-detail-content" class="table_block table-responsive">
  <table id="cart_summary" class="table table-bordered multishipping-cart">
    <thead>
    <tr>
      <th class="cart_product">{l s='Product'}</th>
      <th class="cart_description">{l s='Description'}</th>
      <th class="cart_ref">{l s='Ref.'}</th>
      <th class="cart_avail">{l s='Avail.'}</th>
      <th class="cart_quantity">{l s='Qty'}</th>
      <th class="shipping_address">{l s='Shipping address'}</th>
    </tr>
    </thead>
    <tbody>
    {foreach $product_list as $product}
      {assign var='productId' value=$product.id_product}
      {assign var='productAttributeId' value=$product.id_product_attribute}
      {assign var='quantityDisplayed' value=0}
      {assign var='odd' value=$product@iteration%2}
      {* Display the product line *}
      {include file="$tpl_dir./order-address-product-line.tpl" productLast=$product@last productFirst=$product@first}
    {/foreach}
    </tbody>
  </table>
</div>
{addJsDefL name=CloseTxt}{l s='Submit' js=1}{/addJsDefL}
{addJsDefL name=QtyChanged}{l s='Some product quantities have changed. Please check them' js=1}{/addJsDefL}
{addJsDefL name=ShipToAnOtherAddress}{l s='Ship to multiple addresses' js=1}{/addJsDefL}
