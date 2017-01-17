{capture name=path}
  {if !isset($email_create)}{l s='Authentication'}{else}
    <a href="{$link->getPageLink('authentication', true)|escape:'html':'UTF-8'}" rel="nofollow" title="{l s='Authentication'}">{l s='Authentication'}</a>
    <span class="navigation-pipe">{$navigationPipe}</span>{l s='Create your account'}
  {/if}
{/capture}

<h1 class="page-heading">{if !isset($email_create)}{l s='Authentication'}{else}{l s='Create an account'}{/if}</h1>

{if isset($back) && preg_match("/^http/", $back)}{assign var='current_step' value='login'}{include file="$tpl_dir./order-steps.tpl"}{/if}

{include file="$tpl_dir./errors.tpl"}

{assign var='stateExist' value=false}
{assign var="postCodeExist" value=false}
{assign var="dniExist" value=false}

{if !isset($email_create)}

  {include './authentication-login.tpl'}

  {if isset($inOrderProcess) && $inOrderProcess && $PS_GUEST_CHECKOUT_ENABLED}
    {include './authentication-create-opc.tpl'}
  {/if}

{else}

  {include './authentication-create.tpl'}

{/if}

{strip}
  {if isset($smarty.post.id_state) && $smarty.post.id_state}
    {addJsDef idSelectedState=$smarty.post.id_state|intval}
  {elseif isset($address->id_state) && $address->id_state}
    {addJsDef idSelectedState=$address->id_state|intval}
  {else}
    {addJsDef idSelectedState=false}
  {/if}
  {if isset($smarty.post.id_state_invoice) && isset($smarty.post.id_state_invoice) && $smarty.post.id_state_invoice}
    {addJsDef idSelectedStateInvoice=$smarty.post.id_state_invoice|intval}
  {else}
    {addJsDef idSelectedStateInvoice=false}
  {/if}
  {if isset($smarty.post.id_country) && $smarty.post.id_country}
    {addJsDef idSelectedCountry=$smarty.post.id_country|intval}
  {elseif isset($address->id_country) && $address->id_country}
    {addJsDef idSelectedCountry=$address->id_country|intval}
  {else}
    {addJsDef idSelectedCountry=false}
  {/if}
  {if isset($smarty.post.id_country_invoice) && isset($smarty.post.id_country_invoice) && $smarty.post.id_country_invoice}
    {addJsDef idSelectedCountryInvoice=$smarty.post.id_country_invoice|intval}
  {else}
    {addJsDef idSelectedCountryInvoice=false}
  {/if}
  {if isset($countries)}
    {addJsDef countries=$countries}
  {/if}
  {if isset($vatnumber_ajax_call) && $vatnumber_ajax_call}
    {addJsDef vatnumber_ajax_call=$vatnumber_ajax_call}
  {/if}
  {if isset($email_create) && $email_create}
    {addJsDef email_create=$email_create|boolval}
  {else}
    {addJsDef email_create=false}
  {/if}
{/strip}
