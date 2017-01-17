<form action="{$link->getPageLink('authentication', true, NULL, "back=$back")|escape:'html':'UTF-8'}" method="post" id="new_account_form" class="std clearfix">
  <div class="box">
    <div id="opc_account_form" style="display: block; ">
      <h3 class="page-heading">{l s='Instant checkout'}</h3>
      <p class="required"><sup>*</sup>{l s='Required field'}</p>
      <div class="required form-group">
        <label for="guest_email">{l s='Email address'} <sup>*</sup></label>
        <input type="text" class="is_required validate form-control" data-validate="isEmail" id="guest_email" name="guest_email" value="{if isset($smarty.post.guest_email)}{$smarty.post.guest_email}{/if}" />
      </div>
      <div class="form-group gender-line">
        <label>{l s='Title'}</label>
        <div>
          {foreach from=$genders key=k item=gender}
            <label for="id_gender{$gender->id}" class="radio-inline">
              <input type="radio" name="id_gender" id="id_gender{$gender->id}" value="{$gender->id}"{if isset($smarty.post.id_gender) && $smarty.post.id_gender == $gender->id} checked="checked"{/if} />
              {$gender->name}
            </label>
          {/foreach}
        </div>
      </div>
      <div class="required form-group">
        <label for="firstname">{l s='First name'} <sup>*</sup></label>
        <input type="text" class="is_required validate form-control" data-validate="isName" id="firstname" name="firstname" value="{if isset($smarty.post.firstname)}{$smarty.post.firstname}{/if}" />
      </div>
      <div class="required form-group">
        <label for="lastname">{l s='Last name'} <sup>*</sup></label>
        <input type="text" class="is_required validate form-control" data-validate="isName" id="lastname" name="lastname" value="{if isset($smarty.post.lastname)}{$smarty.post.lastname}{/if}" />
      </div>
      <div class="form-group date-select">
        <label>{l s='Date of Birth'}</label>
        <div class="row">
          <div class="col-xs-4">
            <select id="days" name="days" class="form-control">
              <option value="">-</option>
              {foreach from=$days item=day}
                <option value="{$day}" {if ($sl_day == $day)} selected="selected"{/if}>{$day}&nbsp;&nbsp;</option>
              {/foreach}
            </select>
            {*
                {l s='January'}
                {l s='February'}
                {l s='March'}
                {l s='April'}
                {l s='May'}
                {l s='June'}
                {l s='July'}
                {l s='August'}
                {l s='September'}
                {l s='October'}
                {l s='November'}
                {l s='December'}
            *}
          </div>
          <div class="col-xs-4">
            <select id="months" name="months" class="form-control">
              <option value="">-</option>
              {foreach from=$months key=k item=month}
                <option value="{$k}" {if ($sl_month == $k)} selected="selected"{/if}>{l s=$month}&nbsp;</option>
              {/foreach}
            </select>
          </div>
          <div class="col-xs-4">
            <select id="years" name="years" class="form-control">
              <option value="">-</option>
              {foreach from=$years item=year}
                <option value="{$year}" {if ($sl_year == $year)} selected="selected"{/if}>{$year}&nbsp;&nbsp;</option>
              {/foreach}
            </select>
          </div>
        </div>
      </div>
      {if isset($newsletter) && $newsletter}
        <div class="checkbox">
          <label for="newsletter">
            <input type="checkbox" name="newsletter" id="newsletter" value="1" {if isset($smarty.post.newsletter) && $smarty.post.newsletter == '1'}checked="checked"{/if} />
            {l s='Sign up for our newsletter!'}
          </label>
        </div>
      {/if}
      {if isset($optin) && $optin}
        <div class="checkbox">
          <label for="optin">
            <input type="checkbox" name="optin" id="optin" value="1" {if isset($smarty.post.optin) && $smarty.post.optin == '1'}checked="checked"{/if} />
            {l s='Receive special offers from our partners!'}
          </label>
        </div>
      {/if}
      <h3 class="page-heading">{l s='Delivery address'}</h3>
      {foreach from=$dlv_all_fields item=field_name}
        {if $field_name eq "company"}
          <div class="form-group">
            <label for="company">{l s='Company'}{if in_array($field_name, $required_fields)} <sup>*</sup>{/if}</label>
            <input type="text" class="form-control" id="company" name="company" value="{if isset($smarty.post.company)}{$smarty.post.company}{/if}" />
          </div>
        {elseif $field_name eq "vat_number"}
          <div id="vat_number" style="display:none;">
            <div class="form-group">
              <label for="vat-number">{l s='VAT number'}{if in_array($field_name, $required_fields)} <sup>*</sup>{/if}</label>
              <input id="vat-number" type="text" class="form-control" name="vat_number" value="{if isset($smarty.post.vat_number)}{$smarty.post.vat_number}{/if}" />
            </div>
          </div>
        {elseif $field_name eq "dni"}
          {assign var='dniExist' value=true}
          <div class="required dni form-group">
            <label for="dni">{l s='Identification number'} <sup>*</sup></label>
            <input type="text" name="dni" id="dni" value="{if isset($smarty.post.dni)}{$smarty.post.dni}{/if}" />
            <p class="help-block">{l s='DNI / NIF / NIE'}</p>
          </div>
        {elseif $field_name eq "address1"}
          <div class="required form-group">
            <label for="address1">{l s='Address'} <sup>*</sup></label>
            <input type="text" class="form-control" name="address1" id="address1" value="{if isset($smarty.post.address1)}{$smarty.post.address1}{/if}" />
          </div>
        {elseif $field_name eq "address2"}
          <div class="form-group is_customer_param">
            <label for="address2">{l s='Address (Line 2)'}{if in_array($field_name, $required_fields)} <sup>*</sup>{/if}</label>
            <input type="text" class="form-control" name="address2" id="address2" value="{if isset($smarty.post.address2)}{$smarty.post.address2}{/if}" />
          </div>
        {elseif $field_name eq "postcode"}
          {assign var='postCodeExist' value=true}
          <div class="required postcode form-group">
            <label for="postcode">{l s='Zip/Postal Code'} <sup>*</sup></label>
            <input type="text" class="validate form-control" name="postcode" id="postcode" data-validate="isPostCode" value="{if isset($smarty.post.postcode)}{$smarty.post.postcode}{/if}"/>
          </div>
        {elseif $field_name eq "city"}
          <div class="required form-group">
            <label for="city">{l s='City'} <sup>*</sup></label>
            <input type="text" class="form-control" name="city" id="city" value="{if isset($smarty.post.city)}{$smarty.post.city}{/if}" />
          </div>
          {* if customer hasn't update his layout address, country has to be verified but it's deprecated *}
        {elseif $field_name eq "Country:name" || $field_name eq "country"}
          <div class="required select form-group">
            <label for="id_country">{l s='Country'} <sup>*</sup></label>
            <select name="id_country" id="id_country" class="form-control">
              {foreach from=$countries item=v}
                <option value="{$v.id_country}"{if (isset($smarty.post.id_country) AND  $smarty.post.id_country == $v.id_country) OR (!isset($smarty.post.id_country) && $sl_country == $v.id_country)} selected="selected"{/if}>{$v.name}</option>
              {/foreach}
            </select>
          </div>
        {elseif $field_name eq "State:name"}
          {assign var='stateExist' value=true}
          <div class="required id_state select form-group">
            <label for="id_state">{l s='State'} <sup>*</sup></label>
            <select name="id_state" id="id_state" class="form-control">
              <option value="">-</option>
            </select>
          </div>
        {/if}
      {/foreach}
      {if $stateExist eq false}
        <div class="required id_state select unvisible form-group">
          <label for="id_state">{l s='State'} <sup>*</sup></label>
          <select name="id_state" id="id_state" class="form-control">
            <option value="">-</option>
          </select>
        </div>
      {/if}
      {if $postCodeExist eq false}
        <div class="required postcode unvisible form-group">
          <label for="postcode">{l s='Zip/Postal Code'} <sup>*</sup></label>
          <input type="text" class="validate form-control" name="postcode" id="postcode" data-validate="isPostCode" value="{if isset($smarty.post.postcode)}{$smarty.post.postcode}{/if}"/>
        </div>
      {/if}
      {if $dniExist eq false}
        <div class="required form-group dni">
          <label for="dni">{l s='Identification number'} <sup>*</sup></label>
          <input type="text" class="text form-control" name="dni" id="dni" value="{if isset($smarty.post.dni) && $smarty.post.dni}{$smarty.post.dni}{/if}" />
          <p class="help-block">{l s='DNI / NIF / NIE'}</p>
        </div>
      {/if}
      <div class="{if isset($one_phone_at_least) && $one_phone_at_least}required {/if}form-group">
        <label for="phone_mobile">{l s='Mobile phone'}{if isset($one_phone_at_least) && $one_phone_at_least} <sup>*</sup>{/if}</label>
        <input type="text" class="form-control" name="phone_mobile" id="phone_mobile" value="{if isset($smarty.post.phone_mobile)}{$smarty.post.phone_mobile}{/if}" />
      </div>
      <input type="hidden" name="alias" id="alias" value="{l s='My address'}" />
      <input type="hidden" name="is_new_customer" id="is_new_customer" value="0" />
      <div class="checkbox">
        <label for="invoice_address">
          <input type="checkbox" name="invoice_address" id="invoice_address"{if (isset($smarty.post.invoice_address) && $smarty.post.invoice_address) || (isset($smarty.post.invoice_address) && $smarty.post.invoice_address)} checked="checked"{/if} autocomplete="off"/>
          {l s='Please use another address for invoice'}
        </label>
      </div>
      <div id="opc_invoice_address"  class="unvisible">
        {assign var=stateExist value=false}
        {assign var=postCodeExist value=false}
        {assign var=dniExist value=false}
        <h3 class="page-subheading">{l s='Invoice address'}</h3>
        {foreach from=$inv_all_fields item=field_name}
          {if $field_name eq "company"}
            <div class="form-group">
              <label for="company_invoice">{l s='Company'}{if in_array($field_name, $required_fields)} <sup>*</sup>{/if}</label>
              <input type="text" class="text form-control" id="company_invoice" name="company_invoice" value="{if isset($smarty.post.company_invoice) && $smarty.post.company_invoice}{$smarty.post.company_invoice}{/if}" />
            </div>
          {elseif $field_name eq "vat_number"}
            <div id="vat_number_block_invoice" style="display:none;">
              <div class="form-group">
                <label for="vat_number_invoice">{l s='VAT number'}{if in_array($field_name, $required_fields)} <sup>*</sup>{/if}</label>
                <input type="text" class="form-control" id="vat_number_invoice" name="vat_number_invoice" value="{if isset($smarty.post.vat_number_invoice) && $smarty.post.vat_number_invoice}{$smarty.post.vat_number_invoice}{/if}" />
              </div>
            </div>
          {elseif $field_name eq "dni"}
            {assign var=dniExist value=true}
            <div class="required form-group dni_invoice">
              <label for="dni_invoice">{l s='Identification number'} <sup>*</sup></label>
              <input type="text" class="text form-control" name="dni_invoice" id="dni_invoice" value="{if isset($smarty.post.dni_invoice) && $smarty.post.dni_invoice}{$smarty.post.dni_invoice}{/if}" />
              <p class="help-block">{l s='DNI / NIF / NIE'}</p>
            </div>
          {elseif $field_name eq "firstname"}
            <div class="required form-group">
              <label for="firstname_invoice">{l s='First name'} <sup>*</sup></label>
              <input type="text" class="form-control" id="firstname_invoice" name="firstname_invoice" value="{if isset($smarty.post.firstname_invoice) && $smarty.post.firstname_invoice}{$smarty.post.firstname_invoice}{/if}" />
            </div>
          {elseif $field_name eq "lastname"}
            <div class="required form-group">
              <label for="lastname_invoice">{l s='Last name'} <sup>*</sup></label>
              <input type="text" class="form-control" id="lastname_invoice" name="lastname_invoice" value="{if isset($smarty.post.lastname_invoice) && $smarty.post.lastname_invoice}{$smarty.post.lastname_invoice}{/if}" />
            </div>
          {elseif $field_name eq "address1"}
            <div class="required form-group">
              <label for="address1_invoice">{l s='Address'} <sup>*</sup></label>
              <input type="text" class="form-control" name="address1_invoice" id="address1_invoice" value="{if isset($smarty.post.address1_invoice) && $smarty.post.address1_invoice}{$smarty.post.address1_invoice}{/if}" />
            </div>
          {elseif $field_name eq "address2"}
            <div class="form-group is_customer_param">
              <label for="address2_invoice">{l s='Address (Line 2)'}{if in_array($field_name, $required_fields)} <sup>*</sup>{/if}</label>
              <input type="text" class="form-control" name="address2_invoice" id="address2_invoice" value="{if isset($smarty.post.address2_invoice) && $smarty.post.address2_invoice}{$smarty.post.address2_invoice}{/if}" />
            </div>
          {elseif $field_name eq "postcode"}
            {$postCodeExist = true}
            <div class="required postcode_invoice form-group">
              <label for="postcode_invoice">{l s='Zip/Postal Code'} <sup>*</sup></label>
              <input type="text" class="validate form-control" name="postcode_invoice" id="postcode_invoice" data-validate="isPostCode" value="{if isset($smarty.post.postcode_invoice) && $smarty.post.postcode_invoice}{$smarty.post.postcode_invoice}{/if}"/>
            </div>
          {elseif $field_name eq "city"}
            <div class="required form-group">
              <label for="city_invoice">{l s='City'} <sup>*</sup></label>
              <input type="text" class="form-control" name="city_invoice" id="city_invoice" value="{if isset($smarty.post.city_invoice) && $smarty.post.city_invoice}{$smarty.post.city_invoice}{/if}" />
            </div>
          {elseif $field_name eq "country" || $field_name eq "Country:name"}
            <div class="required form-group">
              <label for="id_country_invoice">{l s='Country'} <sup>*</sup></label>
              <select name="id_country_invoice" id="id_country_invoice" class="form-control">
                <option value="">-</option>
                {foreach from=$countries item=v}
                  <option value="{$v.id_country}"{if (isset($smarty.post.id_country_invoice) && $smarty.post.id_country_invoice == $v.id_country) OR (!isset($smarty.post.id_country_invoice) && $sl_country == $v.id_country)} selected="selected"{/if}>{$v.name|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
            </div>
          {elseif $field_name eq "state" || $field_name eq 'State:name'}
            {$stateExist = true}
            <div class="required id_state_invoice form-group" style="display:none;">
              <label for="id_state_invoice">{l s='State'} <sup>*</sup></label>
              <select name="id_state_invoice" id="id_state_invoice" class="form-control">
                <option value="">-</option>
              </select>
            </div>
          {/if}
        {/foreach}
        {if !$postCodeExist}
          <div class="required postcode_invoice form-group unvisible">
            <label for="postcode_invoice">{l s='Zip/Postal Code'} <sup>*</sup></label>
            <input type="text" class="form-control" name="postcode_invoice" id="postcode_invoice" value="{if isset($smarty.post.postcode_invoice) && $smarty.post.postcode_invoice}{$smarty.post.postcode_invoice}{/if}"/>
          </div>
        {/if}
        {if !$stateExist}
          <div class="required id_state_invoice form-group unvisible">
            <label for="id_state_invoice">{l s='State'} <sup>*</sup></label>
            <select name="id_state_invoice" id="id_state_invoice" class="form-control">
              <option value="">-</option>
            </select>
          </div>
        {/if}
        {if $dniExist eq false}
          <div class="required form-group dni_invoice">
            <label for="dni">{l s='Identification number'} <sup>*</sup></label>
            <input type="text" class="text form-control" name="dni_invoice" id="dni_invoice" value="{if isset($smarty.post.dni_invoice) && $smarty.post.dni_invoice}{$smarty.post.dni_invoice}{/if}" />
            <p class="help-block">{l s='DNI / NIF / NIE'}</p>
          </div>
        {/if}
        <div class="form-group is_customer_param">
          <label for="other_invoice">{l s='Additional information'}</label>
          <textarea class="form-control" name="other_invoice" id="other_invoice" cols="26" rows="3"></textarea>
        </div>
        {if isset($one_phone_at_least) && $one_phone_at_least}
          <p class="help-block required is_customer_param">{l s='You must register at least one phone number.'}</p>
        {/if}
        <div class="form-group is_customer_param">
          <label for="phone_invoice">{l s='Home phone'}</label>
          <input type="text" class="form-control" name="phone_invoice" id="phone_invoice" value="{if isset($smarty.post.phone_invoice) && $smarty.post.phone_invoice}{$smarty.post.phone_invoice}{/if}" />
        </div>
        <div class="{if isset($one_phone_at_least) && $one_phone_at_least}required {/if}form-group">
          <label for="phone_mobile_invoice">{l s='Mobile phone'}{if isset($one_phone_at_least) && $one_phone_at_least} <sup>*</sup>{/if}</label>
          <input type="text" class="form-control" name="phone_mobile_invoice" id="phone_mobile_invoice" value="{if isset($smarty.post.phone_mobile_invoice) && $smarty.post.phone_mobile_invoice}{$smarty.post.phone_mobile_invoice}{/if}" />
        </div>
        <input type="hidden" name="alias_invoice" id="alias_invoice" value="{l s='My Invoice address'}" />
      </div>
    </div>
    {$HOOK_CREATE_ACCOUNT_FORM}
  </div>
  <p class="cart_navigation required submit clearfix">
    <span><sup>*</sup>{l s='Required field'}</span>
    <input type="hidden" name="display_guest_checkout" value="1" />
    <button type="submit" class="btn btn-lg btn-success" name="submitGuestAccount" id="submitGuestAccount">
          <span>
            {l s='Proceed to checkout'}
            <i class="icon icon-chevron-right"></i>
          </span>
    </button>
  </p>
</form>
