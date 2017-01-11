<div id="manufacturers_block_left" class="block blockmanufacturer">
  <p class="title_block">
    {if $display_link_manufacturer}
      <a href="{$link->getPageLink('manufacturer')|escape:'html':'UTF-8'}" title="{l s='Manufacturers' mod='blockmanufacturer'}">
    {/if}
      {l s='Manufacturers' mod='blockmanufacturer'}
    {if $display_link_manufacturer}
      </a>
    {/if}
  </p>
  <div class="block_content list-block">
    {if $manufacturers}
      {if $text_list}
        <ul>
          {foreach from=$manufacturers item=manufacturer name=manufacturer_list}
            {if $smarty.foreach.manufacturer_list.iteration <= $text_list_nb}
              <li>
                <a
                  href="{$link->getmanufacturerLink($manufacturer.id_manufacturer, $manufacturer.link_rewrite)|escape:'html':'UTF-8'}" title="{l s='More about %s' mod='blockmanufacturer' sprintf=[$manufacturer.name]}">
                  {$manufacturer.name|escape:'html':'UTF-8'}
                </a>
              </li>
            {/if}
          {/foreach}
        </ul>
      {/if}
      {if $form_list}
        <form action="{$smarty.server.SCRIPT_NAME|escape:'html':'UTF-8'}" method="get">
          <div class="form-group selector1">
            <select class="form-control" name="manufacturer_list">
              <option value="0">{l s='All manufacturers' mod='blockmanufacturer'}</option>
              {foreach from=$manufacturers item=manufacturer}
                <option value="{$link->getmanufacturerLink($manufacturer.id_manufacturer, $manufacturer.link_rewrite)|escape:'html':'UTF-8'}">{$manufacturer.name|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
          </div>
        </form>
      {/if}
    {else}
      <p>{l s='No manufacturer' mod='blockmanufacturer'}</p>
    {/if}
  </div>
</div>
