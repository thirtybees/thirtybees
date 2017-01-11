<h3>{l s='Referral program rules' mod='referralprogram'}</h3>

{if isset($xml)}
  <div id="referralprogram_rules">
    {if isset($xml->body->$paragraph)}<div class="rte">{$xml->body->$paragraph|replace:"\'":"'"|replace:'\"':'"'}</div>{/if}
  </div>
{/if}
