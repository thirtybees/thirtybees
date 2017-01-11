{capture name=path}<a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">{l s='My account'}</a><span class="navigation-pipe">{$navigationPipe}</span><span class="navigation_page">{l s='Credit slips'}</span>{/capture}

<h1 class="page-heading">
  {l s='Credit slips'}
</h1>
<p>
  <b>{l s='Credit slips you have received after canceled orders'}.</b>
</p>
<div class="block-center" id="block-history">
  {if $ordersSlip && count($ordersSlip)}
    <table id="order-list" class="table table-bordered footab">
      <thead>
      <tr>
        <th data-sort-ignore="true">{l s='Credit slip'}</th>
        <th data-sort-ignore="true">{l s='Order'}</th>
        <th>{l s='Date issued'}</th>
        <th data-sort-ignore="true" data-hide="phone">{l s='View credit slip'}</th>
      </tr>
      </thead>
      <tbody>
      {foreach from=$ordersSlip item=slip name=myLoop}
        <tr>
          <td class="bold">
            <span>
              {l s='#%s' sprintf=$slip.id_order_slip|string_format:"%06d"}
            </span>
          </td>
          <td class="history_method">
            <a href="javascript:showOrder(1, {$slip.id_order|intval}, '{$link->getPageLink('order-detail')|escape:'html':'UTF-8'}');">
              {l s='#%s' sprintf=$slip.id_order|string_format:"%06d"}
            </a>
          </td>
          <td class="bold"  data-value="{$slip.date_add|regex_replace:"/[\-\:\ ]/":""}">
            {dateFormat date=$slip.date_add full=0}
          </td>
          <td class="history_invoice">
            <a class="btn btn-default" href="{$link->getPageLink('pdf-order-slip', true, NULL, "id_order_slip={$slip.id_order_slip|intval}")|escape:'html':'UTF-8'}" title="{l s='Credit slip'} {l s='#%s' sprintf=$slip.id_order_slip|string_format:"%06d"}">
              <i class="icon icon-file-text large"></i> {l s='PDF'}
            </a>
          </td>
        </tr>
      {/foreach}
      </tbody>
    </table>
    <div id="block-order-detail" class="unvisible">&nbsp;</div>
  {else}
    <div class="alert alert-warning">{l s='You have not received any credit slips.'}</div>
  {/if}
</div>

<nav>
  <ul class="pager">
    <li class="previous">
      <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">&larr; {l s='Back to your account'}</a>
    </li>
  </ul>
</nav>
