{capture name=path}
  <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
    {l s='My account'}
  </a>
  <span class="navigation-pipe">
        {$navigationPipe}
    </span>
  <span class="navigation_page">
        {l s='Return Merchandise Authorization (RMA)'}
    </span>
{/capture}

<h1 class="page-heading">
  {l s='Return Merchandise Authorization (RMA)'}
</h1>
{if isset($errorQuantity) && $errorQuantity}
  <p class="error">
    {l s='You do not have enough products to request an additional merchandise return.'}
  </p>
{/if}
{if isset($errorMsg) && $errorMsg}
  <div class="alert alert-danger">
    {l s='Please provide an explanation for your RMA.'}
  </div>
  <form method="POST"  id="returnOrderMessage">
    <div class="textarea form-group">
      <label>{l s='Please provide an explanation for your RMA:'}</label>
      <textarea name="returnText" class="form-control"></textarea>
    </div>
    {foreach $ids_order_detail as $id_order_detail}
      <input type="hidden" name="ids_order_detail[{$id_order_detail|intval}]" value="{$id_order_detail|intval}"/>
    {/foreach}
    {foreach $order_qte_input as $key => $value}
      <input type="hidden" name="order_qte_input[{$key|intval}]" value="{$value|intval}"/>
    {/foreach}
    <input type="hidden" name="id_order" value="{$id_order|intval}"/>
    <input class="unvisible" type="submit" name="submitReturnMerchandise" value="{l s='Make an RMA slip'}"/>
    <p>
      <button type="submit" name="submitReturnMerchandise" class="btn btn-success">
       <span>
         {l s='Make an RMA slip'} <i class="icon icon-chevron-right"></i>
       </span>
      </button>
    </p>
  </form>

{/if}
{if isset($errorDetail1) && $errorDetail1}
  <div class="alert alert-danger">
    {l s='Please check at least one product you would like to return.'}
  </div>
{/if}
{if isset($errorDetail2) && $errorDetail2}
  <div class="alert alert-danger">
    {l s='For each product you wish to add, please specify the desired quantity.'}
  </div>
{/if}
{if isset($errorNotReturnable) && $errorNotReturnable}
  <div class="alert alert-danger">
    {l s='This order cannot be returned.'}
  </div>
{/if}

<p>
  <b>{l s='Here is a list of pending merchandise returns'}.</b>
</p>
<div class="block-center" id="block-history">
  {if $ordersReturn && count($ordersReturn)}
    <table id="order-list" class="table table-bordered footab">
      <thead>
      <tr>
        <th data-sort-ignore="true">{l s='Return'}</th>
        <th data-sort-ignore="true">{l s='Order'}</th>
        <th data-hide="phone">{l s='Package status'}</th>
        <th data-hide="phone,tablet">{l s='Date issued'}</th>
        <th data-sort-ignore="true" data-hide="phone,tablet">{l s='Return slip'}</th>
      </tr>
      </thead>
      <tbody>
      {foreach from=$ordersReturn item=return name=myLoop}
        <tr>
          <td class="bold">
            <a href="javascript:showOrder(0, {$return.id_order_return|intval}, '{$link->getPageLink('order-return', true)|escape:'html':'UTF-8'}');">
              {l s='#'}{$return.id_order_return|string_format:"%06d"}
            </a>
          </td>
          <td class="history_method">
            <a href="javascript:showOrder(1, {$return.id_order|intval}, '{$link->getPageLink('order-detail', true)|escape:'html':'UTF-8'}');">
              {$return.reference}
            </a>
          </td>
          <td class="history_method" data-value="{$return.state}">
            <span class="label label-info">
              {$return.state_name|escape:'html':'UTF-8'}
            </span>
          </td>
          <td class="bold" data-value="{$return.date_add|regex_replace:"/[\-\:\ ]/":""}">
            {dateFormat date=$return.date_add full=0}
          </td>
          <td class="history_invoice">
            {if $return.state == 2}
              <a class="btn btn-default" href="{$link->getPageLink('pdf-order-return', true, NULL, "id_order_return={$return.id_order_return|intval}")|escape:'html':'UTF-8'}" title="{l s='Order return'} {l s='#'}{$return.id_order_return|string_format:"%06d"}">
                <i class="icon icon-file-text"></i> {l s='Print out'}
              </a>
            {else}
              --
            {/if}
          </td>
        </tr>
      {/foreach}
      </tbody>
    </table>
    <div id="block-order-detail" class="unvisible">&nbsp;</div>
  {else}
    <div class="alert alert-warning">{l s='You have no merchandise return authorizations.'}</div>
  {/if}
</div>

<nav>
  <ul class="pager">
    <li class="previous">
      <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">&larr; {l s='Back to your account'}</a>
    </li>
  </ul>
</nav>
