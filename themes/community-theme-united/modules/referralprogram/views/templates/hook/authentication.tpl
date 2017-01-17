<fieldset class="account_creation">
  <h3 class="page-subheading">{l s='Referral program' mod='referralprogram'}</h3>
  <div class="form-group">
    <label for="referralprogram">{l s='E-mail address of your sponsor' mod='referralprogram'}</label>
    <input class="form-control" type="text" size="52" maxlength="128" id="referralprogram" name="referralprogram" value="{if isset($smarty.post.referralprogram)}{$smarty.post.referralprogram|escape:'html':'UTF-8'}{/if}" />
  </div>
</fieldset>
