<section id="blocknewsletter" class="col-xs-12 col-sm-3">
  <h4>{l s='Newsletter' mod='blocknewsletter'}</h4>
  <form action="{$link->getPageLink('index', null, null, null, false, null, true)|escape:'html':'UTF-8'}" method="post">
    <div class="form-group{if isset($msg) && $msg } {if $nw_error}form-error{else}form-ok{/if}{/if}" >
      <div class="input-group">
        <input class="form-control" id="newsletter-input" type="email" name="email" size="18" value="{if isset($msg) && $msg}{$msg}{elseif isset($value) && $value}{$value}{else}{l s='Enter your e-mail' mod='blocknewsletter'}{/if}"/>
        <span class="input-group-btn">
          <button type="submit" name="submitNewsletter" class="btn btn-primary">
            <i class="icon icon-chevron-right"></i>
          </button>
        </span>
      </div>
      <input type="hidden" name="action" value="0" />
    </div>
  </form>
  {hook h="displayBlockNewsletterBottom" from='blocknewsletter'}
</section>

{strip}
  {if isset($msg) && $msg}
    {addJsDef msg_newsl=$msg|@addcslashes:'\''}
  {/if}
  {if isset($nw_error)}
    {addJsDef nw_error=$nw_error}
  {/if}
  {addJsDefL name=placeholder_blocknewsletter}{l s='Enter your e-mail' mod='blocknewsletter' js=1}{/addJsDefL}
  {if isset($msg) && $msg}
    {addJsDefL name=alert_blocknewsletter}{l s='Newsletter : %1$s' sprintf=$msg js=1 mod="blocknewsletter"}{/addJsDefL}
  {/if}
{/strip}
