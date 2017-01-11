<div id="suppliers_block_left" class="block blocksupplier">
  <p class="title_block">
    {if $display_link_supplier}
    <a href="{$link->getPageLink('supplier')|escape:'html':'UTF-8'}" title="{l s='Suppliers' mod='blocksupplier'}">
      {/if}
      {l s='Suppliers' mod='blocksupplier'}
      {if $display_link_supplier}
    </a>
    {/if}
  </p>
  <div class="block_content list-block">
    {if $suppliers}
      {if $text_list}
        <ul>
          {foreach from=$suppliers item=supplier name=supplier_list}
            {if $smarty.foreach.supplier_list.iteration <= $text_list_nb}
              <li>
                {if $display_link_supplier}
                <a
                  href="{$link->getsupplierLink($supplier.id_supplier, $supplier.link_rewrite)|escape:'html':'UTF-8'}"
                  title="{l s='More about' mod='blocksupplier'} {$supplier.name}">
                  {/if}
                  {$supplier.name|escape:'html':'UTF-8'}
                  {if $display_link_supplier}
                </a>
                {/if}
              </li>
            {/if}
          {/foreach}
        </ul>
      {/if}
      {if $form_list}
        <form action="{$smarty.server.SCRIPT_NAME|escape:'html':'UTF-8'}" method="get">
          <div class="form-group selector1">
            <select class="form-control" name="supplier_list">
              <option value="0">{l s='All suppliers' mod='blocksupplier'}</option>
              {foreach from=$suppliers item=supplier}
                <option value="{$link->getsupplierLink($supplier.id_supplier, $supplier.link_rewrite)|escape:'html':'UTF-8'}">{$supplier.name|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
          </div>
        </form>
      {/if}
    {else}
      <p>{l s='No supplier' mod='blocksupplier'}</p>
    {/if}
  </div>
</div>
