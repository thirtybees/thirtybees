{if !empty($currencies)}

  {foreach from=$currencies key=k item=f_currency}
    {if $cookie->id_currency == $f_currency.id_currency}
      {$current_iso = $f_currency.iso_code}
      {break}
    {/if}
  {/foreach}

  <li id="blockcurrencies" class="dropdown">

    {* Backwards compatibility *}
    <div id="setCurrency" class="hidden" style="display: none">
      <input type="hidden" name="id_currency" id="id_currency" value=""/>
      <input type="hidden" name="SubmitCurrency" value="" />
    </div>

    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
      {l s='Currency: %s' sprintf=[$current_iso] mod='blockcurrencies'} <span class="caret"></span>
    </a>

    <ul class="dropdown-menu">
      {foreach from=$currencies key=k item=f_currency}
        {if strpos($f_currency.name, '('|cat:$f_currency.iso_code:')') === false}
          {assign var="currency_name" value={l s='%s (%s)' sprintf=[$f_currency.name, $f_currency.iso_code] mod='blockcurrencies'}}
        {else}
          {assign var="currency_name" value=$f_currency.name}
        {/if}
        <li{if $cookie->id_currency == $f_currency.id_currency} class="active"{/if}>
          <a href="javascript:setCurrency({$f_currency.id_currency});" rel="nofollow" title="{$currency_name}">
            {$currency_name}
          </a>
        </li>
      {/foreach}
    </ul>

  </li>

{/if}
