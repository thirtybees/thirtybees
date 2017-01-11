{capture name=path}
  <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
    {l s='My account'}
  </a>
  <span class="navigation-pipe">{$navigationPipe}</span>
  <span class="navigation_page">{l s='Order history'}</span>
{/capture}
{include file="$tpl_dir./errors.tpl"}
<h1 class="page-heading">{l s='Order history'}</h1>
<p><b>{l s='Here are the orders you\'ve placed since your account was created.'}</b></p>
{if $slowValidation}
  <div class="alert alert-warning">{l s='If you have just placed an order, it may take a few minutes for it to be validated. Please refresh this page if your order is missing.'}</div>
{/if}
<div class="block-center" id="block-history">
  {if $orders && count($orders)}
    <div class="table-responsive">
      <table id="order-list" class="table table-bordered footab">
        <thead>
        <tr>
          <th data-sort-ignore="true">{l s='Order reference'}</th>
          <th>{l s='Date'}</th>
          <th data-hide="phone">{l s='Total price'}</th>
          <th data-sort-ignore="true" data-hide="phone,tablet">{l s='Payment'}</th>
          <th>{l s='Status'}</th>
          <th data-sort-ignore="true" data-hide="phone,tablet">{l s='Invoice'}</th>
          <th data-sort-ignore="true" data-hide="phone,tablet">&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$orders item=order name=myLoop}
          <tr>
            <td class="history_link bold">
              {if isset($order.invoice) && $order.invoice && isset($order.virtual) && $order.virtual}
                <img class="icon" src="{$img_dir}icon/download_product.gif" alt="{l s='Products to download'}" title="{l s='Products to download'}" />
              {/if}
              <a class="color-myaccount" href="javascript:showOrder(1, {$order.id_order|intval}, '{$link->getPageLink('order-detail', true, NULL, "id_order={$order.id_order|intval}")|escape:'html':'UTF-8'}');">
                {Order::getUniqReferenceOf($order.id_order)}
              </a>
            </td>
            <td data-value="{$order.date_add|regex_replace:"/[\-\:\ ]/":""}" class="history_date bold">
              {dateFormat date=$order.date_add full=0}
            </td>
            <td class="history_price" data-value="{$order.total_paid}">
            <span class="price">
              {displayPrice price=$order.total_paid currency=$order.id_currency no_utf8=false convert=false}
            </span>
            </td>
            <td class="history_method">{$order.payment|escape:'html':'UTF-8'}</td>
            <td{if isset($order.order_state)} data-value="{$order.id_order_state}"{/if} class="history_state">
              {if isset($order.order_state)}
                <span class="label{if isset($order.order_state_color) && Tools::getBrightness($order.order_state_color) > 128} dark{/if}"{if isset($order.order_state_color) && $order.order_state_color} style="background-color:{$order.order_state_color|escape:'html':'UTF-8'}; border-color:{$order.order_state_color|escape:'html':'UTF-8'};"{/if}>
                {$order.order_state|escape:'html':'UTF-8'}
              </span>
              {/if}
            </td>
            <td class="history_invoice">
              {if (isset($order.invoice) && $order.invoice && isset($order.invoice_number) && $order.invoice_number) && isset($invoiceAllowed) && $invoiceAllowed == true}
                <a class="btn btn-default" href="{$link->getPageLink('pdf-invoice', true, NULL, "id_order={$order.id_order}")|escape:'html':'UTF-8'}" title="{l s='Invoice'}" target="_blank">
                  <i class="icon icon-file-text large"></i> {l s='PDF'}
                </a>
              {else}
                -
              {/if}
            </td>
            <td class="history_detail">
              <a class="btn btn-default" href="javascript:showOrder(1, {$order.id_order|intval}, '{$link->getPageLink('order-detail', true, NULL, "id_order={$order.id_order|intval}")|escape:'html':'UTF-8'}');">
              <span>
                {l s='Details'} <i class="icon icon-chevron-right"></i>
              </span>
              </a>
              {if isset($opc) && $opc}
              <a class="btn btn-default" href="{$link->getPageLink('order-opc', true, NULL, "submitReorder&id_order={$order.id_order|intval}")|escape:'html':'UTF-8'}" title="{l s='Reorder'}">
                {else}
                <a class="btn btn-default" href="{$link->getPageLink('order', true, NULL, "submitReorder&id_order={$order.id_order|intval}")|escape:'html':'UTF-8'}" title="{l s='Reorder'}">
                  {/if}
                  {if isset($reorderingAllowed) && $reorderingAllowed}
                    <i class="icon icon-refresh"></i> {l s='Reorder'}
                  {/if}
                </a>
            </td>
          </tr>
        {/foreach}
        </tbody>
      </table>
    </div>
    <div id="block-order-detail" class="unvisible">&nbsp;</div>
  {else}
    <div class="alert alert-warning">{l s='You have not placed any orders.'}</div>
  {/if}
</div>

<nav>
  <ul class="pager">
    <li class="previous">
      <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">&larr; {l s='Back to your account'}</a>
    </li>
  </ul>
</nav>

