{if isset($email) AND $email}
  <div class="form-group">
    <input type="text" id="oos_customer_email" name="customer_email" size="20" value="{l s='your@email.com' mod='mailalerts'}" class="mailalerts_oos_email form-control" />
  </div>
{/if}
<a href="#" title="{l s='Notify me when available' mod='mailalerts'}" id="mailalert_link" rel="nofollow">{l s='Notify me when available' mod='mailalerts'}</a>
<span id="oos_customer_email_result" style="display: block;"></span>
{strip}
  {addJsDef oosHookJsCodeFunctions=array('oosHookJsCodeMailAlert')}
  {addJsDef mailalerts_url_check=$link->getModuleLink('mailalerts', 'actions', ['process' => 'check'])}
  {addJsDef mailalerts_url_add=$link->getModuleLink('mailalerts', 'actions', ['process' => 'add'])}

  {addJsDefL name='mailalerts_placeholder'}{l s='your@email.com' mod='mailalerts' js=1}{/addJsDefL}
  {addJsDefL name='mailalerts_registered'}{l s='Request notification registered' mod='mailalerts' js=1}{/addJsDefL}
  {addJsDefL name='mailalerts_already'}{l s='You already have an alert for this product' mod='mailalerts' js=1}{/addJsDefL}
  {addJsDefL name='mailalerts_invalid'}{l s='Your e-mail address is invalid' mod='mailalerts' js=1}{/addJsDefL}
{/strip}
