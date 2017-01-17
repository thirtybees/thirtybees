{capture name=path}
  <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">{l s='My account'}</a>
  <span class="navigation-pipe">{$navigationPipe}</span>
  <span class="navigation_page">{l s='My vouchers'}</span>
{/capture}

<h1 class="page-heading">{l s='My vouchers'}</h1>

{if isset($cart_rules) && count($cart_rules) && $nb_cart_rules}
  <div class="table-responsive">
    <table class="discount table table-bordered">
      <thead>
      <tr>
        <th class="discount_code">{l s='Code'}</th>
        <th class="discount_description">{l s='Description'}</th>
        <th class="discount_quantity">{l s='Quantity'}</th>
        <th class="discount_value">{l s='Value'}*</th>
        <th class="discount_minimum">{l s='Minimum'}</th>
        <th class="discount_cumulative">{l s='Cumulative'}</th>
        <th class="discount_expiration_date">{l s='Expiration date'}</th>
      </tr>
      </thead>
      <tbody>
      {foreach from=$cart_rules item=discountDetail name=myLoop}
        <tr>
          <td class="discount_code">{$discountDetail.code}</td>
          <td class="discount_description">{$discountDetail.name}</td>
          <td class="discount_quantity">{$discountDetail.quantity_for_user}</td>
          <td class="discount_value">

            {if $discountDetail.reduction_percent > 0}
              {$discountDetail.reduction_percent|escape:'html':'UTF-8'}%
            {/if}
            {if $discountDetail.reduction_amount > 0}
              {if $discountDetail.reduction_percent > 0} + {/if}
              {convertPrice price=$discountDetail.reduction_amount} ({if $discountDetail.reduction_tax == 1}{l s='Tax included'}{else}{l s='Tax excluded'}{/if})
            {/if}
            {if $discountDetail.free_shipping}
              {if $discountDetail.reduction_percent > 0 || $discountDetail.reduction_amount > 0} + {/if}
              {l s='Free shipping'}
            {/if}

            {* .gift_product_name avaialable since 1.6.1.6 *}
            {if !empty($discountDetail.gift_product_name)}
              {if $discountDetail.gift_product > 0}
                {if $discountDetail.reduction_percent > 0 || $discountDetail.reduction_amount > 0 || $discountDetail.gift_product} + {/if}
                {$discountDetail.gift_product_name} {l s='Free %s!' sprintf=$discountDetail.gift_product_name}!
              {/if}
            {/if}

          </td>
          <td class="discount_minimum">
            {if $discountDetail.minimal == 0}
              {l s='None'}
            {else}
              {convertPrice price=$discountDetail.minimal}
            {/if}
          </td>
          <td class="discount_cumulative">
            {if $discountDetail.cumulable == 1}
              {l s='Yes'}
            {else}
              {l s='No'}
            {/if}
          </td>
          <td class="discount_expiration_date">
            {dateFormat date=$discountDetail.date_to}
          </td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  </div>

{else}
  <div class="alert alert-warning">{l s='You do not have any vouchers.'}</div>
{/if}

<nav>
  <ul class="pager">
    <li class="previous">
      <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">&larr; {l s='Back to your account'}</a>
    </li>
  </ul>
</nav>
