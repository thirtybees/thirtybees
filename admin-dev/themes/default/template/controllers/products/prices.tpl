{*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    CustomPresta <developer@custompresta.com>
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<script type="text/javascript">
var Customer = new Object();
var product_url = '{$link->getAdminLink('AdminProducts', true)|addslashes}';
var ecotax_tax_excl = parseFloat({$ecotax_tax_excl});
{if ($currency->decimals)}
  var priceDisplayPrecision = {$smarty.const._PS_PRICE_DISPLAY_PRECISION_};
{else}
  var priceDisplayPrecision = 0;
{/if}
var priceDatabasePrecision = {$smarty.const._TB_PRICE_DATABASE_PRECISION_};

$(document).ready(function () {
	Customer = {
		"hiddenField": jQuery('#id_customer'),
		"field": jQuery('#customer'),
		"container": jQuery('#customers'),
		"loader": jQuery('#customerLoader'),
		"init": function() {
			jQuery(Customer.field).typeWatch({
				"captureLength": 1,
				"highlight": true,
				"wait": 50,
				"callback": Customer.search
			}).focus(Customer.placeholderIn).blur(Customer.placeholderOut);
		},
		"placeholderIn": function() {
			if (this.value == '{l s='All customers'}') {
				this.value = '';
			}
		},
		"placeholderOut": function() {
			if (this.value == '') {
				this.value = '{l s='All customers'}';
			}
		},
		"search": function()
		{
			Customer.showLoader();
			jQuery.ajax({
				"type": "POST",
				"url": "{$link->getAdminLink('AdminCustomers')|addslashes}",
				"async": true,
				"dataType": "json",
				"data": {
					"ajax": "1",
					"token": "{getAdminToken tab='AdminCustomers'}",
					"tab": "AdminCustomers",
					"action": "searchCustomers",
					"customer_search": Customer.field.val()
				},
				"success": Customer.success
			});
		},
		"success": function(result)
		{
			if(result.found) {
				var html = '<ul class="list-unstyled">';
				jQuery.each(result.customers, function() {
					html += '<li><a class="fancybox" href="{$link->getAdminLink('AdminCustomers')}&id_customer='+this.id_customer+'&viewcustomer&liteDisplaying=1">'+this.firstname+' '+this.lastname+'</a>'+(this.birthday ? ' - '+this.birthday:'');
					html += ' - '+this.email;
					html += '<a onclick="Customer.select('+this.id_customer+', \''+this.firstname+' '+this.lastname+'\'); return false;" href="#" class="btn btn-default">{l s='Choose'}</a></li>';
				});
				html += '</ul>';
			}
			else
				html = '<div class="alert alert-warning">{l s='No customers found'}</div>';
			Customer.hideLoader();
			Customer.container.html(html);
			jQuery('.fancybox', Customer.container).fancybox();
		},
		"select": function(id_customer, fullname)
		{
			Customer.hiddenField.val(id_customer);
			Customer.field.val(fullname);
			Customer.container.empty();
			return false;
		},
		"showLoader": function() {
			Customer.loader.fadeIn();
		},
		"hideLoader": function() {
			Customer.loader.fadeOut();
		}
	};
	Customer.init();
	
	$('#content_unit').val($('#base_price_unit option:selected').html());
});

function calcContentUnitPrice() {
  var pricetaxexcl = $('#priceTE').val();
  var contentunit = $('#content_value').val();
  
  var modifier_full = $('#base_price_unit option:selected').attr('data-modifier');
  var modifier_operator = modifier_full.substr(0, 1);
  var modifier_value = modifier_full.substr(1);
  
  var contentunit_calc = pricetaxexcl / contentunit;
  
  if(modifier_operator == '*')
  {
      var calc2 = contentunit_calc * modifier_value;
  }
  else if (modifier_operator == '/')
  {
      var calc2 = contentunit_calc / modifier_value;  
  }
  else if (modifier_operator == '+')
  {
      var calc2 = contentunit_calc + modifier_value;  
  }
  else if (modifier_operator == '-')
  {
      var calc2 = contentunit_calc - modifier_value;  
  }
  
  var contentunit_res = ps_round(calc2, 6).toFixed(6);
  $('#unit_price').val(contentunit_res);
  
  unitPriceWithTax('unit');
  unitChange();
}

function unitChange() {
  $('#unity').val($('#base_price_unit option:selected').attr('data-displayname'));
  $('#content_unit').val($('#base_price_unit option:selected').html());
}
</script>

<div id="product-prices" class="panel product-tab">
	<input type="hidden" name="submitted_tabs[]" value="Prices" />
	<h3>{l s='Product price'}</h3>
	<div class="alert alert-info" {if !$country_display_tax_label || $tax_exclude_taxe_option}style="display:none;"{/if}>
		{l s='You must enter either the pre-tax retail price, or the retail price with tax. The input field will be automatically calculated.'}
	</div>
	{include file="controllers/products/multishop/check_fields.tpl" product_tab="Prices"}
	<div class="form-group">
		<div class="col-lg-1"><span class="pull-right">{include file="controllers/products/multishop/checkbox.tpl" field="wholesale_price" type="default"}</span></div>
		<label class="control-label col-lg-2" for="wholesale_price">
			<span class="label-tooltip" data-toggle="tooltip" title="{l s='The wholesale price is the price you paid for the product. Do not include the tax.'}">{if !$country_display_tax_label || $tax_exclude_taxe_option}{l s='Wholesale price'}{else}{l s='Pre-tax wholesale price'}{/if}</span>
		</label>
		<div class="col-lg-2">
			<div class="input-group">
				<span class="input-group-addon">{$currency->prefix}{$currency->suffix}</span>
                <input type="text"
                    id="wholesale_price"
                    name="wholesale_price"
                    value="{displayPriceValue price=$product->wholesale_price}"
                    onkeyup="if (isArrowKey(event)) return;
                             this.value = this.value.replace(/,/g, '.');"
                />
			</div>
            {if isset($pack) && $pack->isPack($product->id)}
                <p class="help-block">
                    {l s='The sum of wholesale prices of the products in the pack is'}
                    {displayPrice price=$pack->noPackWholesalePrice($product->id)
                                  currency=$currency->id}
                </p>
            {/if}
		</div>
	</div>
	<div class="form-group">
		<div class="col-lg-1"><span class="pull-right">{include file="controllers/products/multishop/checkbox.tpl" field="price" type="price"}</span></div>
		<label class="control-label col-lg-2" for="priceTE">
			<span class="label-tooltip" data-toggle="tooltip" title="{l s='The pre-tax retail price is the price for which you intend sell this product to your customers. It should be higher than the pre-tax wholesale price: the difference between the two will be your margin.'}">{if !$country_display_tax_label || $tax_exclude_taxe_option}{l s='Retail price'}{else}{l s='Pre-tax retail price'}{/if}</span>
		</label>
		<div class="col-lg-2">
			<div class="input-group">
				<span class="input-group-addon">{$currency->prefix}{$currency->suffix}</span>
                <input type="hidden"
                    id="priceTEReal"
                    name="price"
                    value="{displayPriceValue price=$product->price}"
                />
                <input type="text"
                    id="priceTE"
                    name="price_displayed"
                    value="{displayPriceValue price=$product->price}"
                    onchange="$('#priceTEReal').val(this.value);calcContentUnitPrice();"
                    onkeyup="if (isArrowKey(event)) return;
                             this.value = this.value.replace(/,/g, '.');
                             $('#priceType').val('TE');
                             $('#priceTEReal').val(this.value);
                             calcPriceTI();"
                />
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-lg-1"><span class="pull-right">{include file="controllers/products/multishop/checkbox.tpl" field="id_tax_rules_group" type="default"}</span></div>
		<label class="control-label col-lg-2" for="id_tax_rules_group">
			{l s='Tax rule:'}
		</label>
		<div class="col-lg-8">
			<script type="text/javascript">
				noTax = {if $tax_exclude_taxe_option}true{else}false{/if};
				taxesArray = new Array();
				{foreach $taxesRatesByGroup as $tax_by_group}
					taxesArray[{$tax_by_group.id_tax_rules_group}] = {$tax_by_group|json_encode};
				{/foreach}
				ecotaxTaxRate = {$ecotaxTaxRate / 100};
			</script>
			<div class="row">
				<div class="col-lg-6">
					<select onchange="javascript:calcPrice(); unitPriceWithTax('unit');" name="id_tax_rules_group" id="id_tax_rules_group" {if $tax_exclude_taxe_option}disabled="disabled"{/if} >
						<option value="0">{l s='No Tax'}</option>
					{foreach from=$tax_rules_groups item=tax_rules_group}
						<option value="{$tax_rules_group.id_tax_rules_group}" {if $product->getIdTaxRulesGroup() == $tax_rules_group.id_tax_rules_group}selected="selected"{/if} >
					{$tax_rules_group['name']|htmlentitiesUTF8}
						</option>
					{/foreach}
					</select>
				</div>
				<div class="col-lg-2">
					<a class="btn btn-link confirm_leave" href="{$link->getAdminLink('AdminTaxRulesGroup')|escape:'html':'UTF-8'}&amp;addtax_rules_group&amp;id_product={$product->id}"{if $tax_exclude_taxe_option} disabled="disabled"{/if}>
						<i class="icon-plus-sign"></i> {l s='Create new tax'} <i class="icon-external-link-sign"></i>
					</a>
				</div>
			</div>
		</div>
	</div>
	{if $tax_exclude_taxe_option}
	<div class="form-group">
		<div class="col-lg-9 col-lg-offset-3">
			<div class="alert">
				{l s='Taxes are currently disabled:'}
				<a href="{$link->getAdminLink('AdminTaxes')|escape:'html':'UTF-8'}">{l s='Click here to open the Taxes configuration page.'}</a>
				<input type="hidden" value="{$product->getIdTaxRulesGroup()}" name="id_tax_rules_group" />
			</div>
		</div>
	</div>
	{/if}
	<div class="form-group" {if !$ps_use_ecotax} style="display:none;"{/if}>
		<div class="col-lg-1"><span class="pull-right">{include file="controllers/products/multishop/checkbox.tpl" field="ecotax" type="default"}</span></div>
		<label class="control-label col-lg-2" for="ecotax">
			<span class="label-tooltip" data-toggle="tooltip" title="{l s='The ecotax is a local set of taxes intended to "promote ecologically sustainable activities via economic incentives". It is already included in retail price: the higher this ecotax is, the lower your margin will be.'}">{l s='Ecotax (tax incl.)'}</span>
		</label>
		<div class="input-group col-lg-2">
			<span class="input-group-addon">{$currency->prefix}{$currency->suffix}</span>
            <input type="text"
                id="ecotax"
                name="ecotax"
                value="{displayPriceValue price=$product->ecotax}"
                onkeyup="if (isArrowKey(event)) return;
                         this.value = this.value.replace(/,/g, '.');
                         $('#priceType').val('TI');
                         calcPriceTE();"
            />
		</div>
	</div>
	<div class="form-group" {if !$country_display_tax_label || $tax_exclude_taxe_option}style="display:none;"{/if} >
		<label class="control-label col-lg-3" for="priceTI">{l s='Retail price with tax'}</label>
		<div class="input-group col-lg-2">
			<span class="input-group-addon">{$currency->prefix}{$currency->suffix}</span>
			<input id="priceType" name="priceType" type="hidden" value="TE" />
            <input type="text"
                id="priceTI"
                name="priceTI"
                value=""
                onkeyup="if (isArrowKey(event)) return;
                         this.value = this.value.replace(/,/g, '.');
                         $('#priceType').val('TI');
                         calcPriceTE();
						 calcContentUnitPrice();"
            />
		</div>
        {if isset($pack) && $pack->isPack($product->id)}
            <p class="col-lg-9 col-lg-offset-3 help-block">
                {l s='The sum of prices of the products in the pack is'}
                {displayPrice price=$pack->noPackPrice($product->id)
                              currency=$currency->id}
            </p>
        {/if}
	</div>
	<div class="form-group">
		<div class="col-lg-1"><span class="pull-right">{include file="controllers/products/multishop/checkbox.tpl" field="on_sale" type="default"}</span></div>
		<label class="control-label col-lg-2" for="on_sale">&nbsp;</label>
		<div class="col-lg-9">
			<div class="checkbox">
				<label class="control-label" for="on_sale" >
					<input type="checkbox" name="on_sale" id="on_sale" {if $product->on_sale}checked="checked"{/if} value="1" />
					{l s='Display the "on sale" icon on the product page, and in the text found within the product listing.'}
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-lg-9 col-lg-offset-3">
			<div class="alert alert-warning">
				<strong>{l s='Final retail price:'}</strong>
				<span>
					{$currency->prefix}
					<span id="finalPrice" >0.00</span>
					{$currency->suffix}
					<span{if !$ps_tax} style="display:none;"{/if}> ({l s='tax incl.'})</span>
				</span>
				<span{if !$ps_tax} style="display:none;"{/if} >
				{if $country_display_tax_label}
					/
				{/if}
					{$currency->prefix}
				<span id="finalPriceWithoutTax"></span>
					{$currency->suffix}
					{if $country_display_tax_label}({l s='tax excl.'}){/if}
				</span>
			</div>
		</div>
	</div>
	<div class="panel-footer">
		<a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay'}</button>
	</div>
</div>
<div class="panel" {if !$product_units}style="display:none;"{/if}>
<h3>{l s='Unit Price'}</h3>
	<div class="form-group">
		<label class="control-label col-lg-3" for="content_value">{l s='Verpackungsinhalt'}</label>
		<div class="col-lg-4">
			<div class="input-group">
				<input id="content_value" name="content_value" maxlength="16" type="text" value="{$product->content_value}" autocomplete="off" onkeyup="this.value = this.value.replace(/,/g, '.');calcContentUnitPrice();" />
				<span class="input-group-addon">{l s='Grundpreiseinheit'}</span>
				<select name="base_price_unit" id="base_price_unit" onchange="unitChange();">
					{foreach from=$product_units item=p_unit}
						<option value="{$p_unit.id_product_unit}" data-modifier="{$p_unit.modifier}" data-displayname="{$p_unit.display_name}" {if $product->unity == $p_unit.display_name}selected="selected"{/if}>{$p_unit.content_name|htmlentitiesUTF8}</option>
					{/foreach}
				</select>
				<input type="hidden" value="" id="content_unit" name="content_unit" />
			</div>
		</div>
	</div>
	
	<div class="form-group">
		<div class="col-lg-1"><span class="pull-right">{include file="controllers/products/multishop/checkbox.tpl" field="unit_price" type="unit_price"}</span></div>
		<label class="control-label col-lg-2" for="unit_price">
			<span class="label-tooltip" data-toggle="tooltip" title="{l s='When selling a pack of items, you can indicate the unit price for each item of the pack. For instance, "per bottle" or "per pound".'}">{l s='Unit price (tax excl.)'}</span>
		</label>
		<div class="col-lg-4">
			<div class="input-group">
				<span class="input-group-addon">{$currency->prefix}{$currency->suffix}</span>
				<input id="unit_price" name="unit_price" type="text" value="{$unit_price|string_format:'%.6f'}" readonly maxlength="27" />
				<span class="input-group-addon">{l s='per'}</span>
				<input id="unity" name="unity" type="text" value="{$product->unity|htmlentitiesUTF8}" readonly maxlength="255" />
			</div>
		</div>
	</div>
	
	<div class="panel-footer">
		<a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay'}</button>
	</div>
</div>
{if isset($specificPriceModificationForm)}
<div class="panel">
	<h3>{l s='Specific prices'}</h3>
	<div class="alert alert-info">
		{l s='You can set specific prices for clients belonging to different groups, different countries, etc.'}
	</div>
	<div class="form-group">
		<div class="col-lg-12">
			<a class="btn btn-default" href="#" id="show_specific_price">
				<i class="icon-plus-sign"></i> {l s='Add a new specific price'}
			</a>
			<a class="btn btn-default" href="#" id="hide_specific_price" style="display:none">
				<i class="icon-remove text-danger"></i> {l s='Cancel new specific price'}
			</a>
		</div>
	</div>
	<script type="text/javascript">
		var product_prices = new Array();
		{foreach from=$combinations item='combination'}
			product_prices['{$combination.id_product_attribute}'] = '{$combination.price|@addcslashes:'\''}';
		{/foreach}
	</script>
	<div id="add_specific_price" class="well clearfix" style="display: none;">
		<div class="col-lg-12">
			<div class="form-group">
				<label class="control-label col-lg-2" for="{if !$multi_shop}spm_currency_0{else}sp_id_shop{/if}">{l s='For'}</label>
				<div class="col-lg-9">
					<div class="row">
					{if !$multi_shop}
						<input type="hidden" name="sp_id_shop" value="0" />
					{else}
						<div class="col-lg-3">
							<select name="sp_id_shop" id="sp_id_shop">
								{if !$admin_one_shop}<option value="0">{l s='All shops'}</option>{/if}
								{foreach from=$shops item=shop}
								<option value="{$shop.id_shop}">{$shop.name|htmlentitiesUTF8}</option>
								{/foreach}
							</select>
						</div>
					{/if}
						<div class="col-lg-3">
							<select name="sp_id_currency" id="spm_currency_0" onchange="changeCurrencySpecificPrice(0);">
								<option value="0">{l s='All currencies'}</option>
								{foreach from=$currencies item=curr}
								<option value="{$curr.id_currency}">{$curr.name|htmlentitiesUTF8}</option>
								{/foreach}
							</select>
						</div>
						<div class="col-lg-3">
							<select name="sp_id_country" id="sp_id_country">
								<option value="0">{l s='All countries'}</option>
								{foreach from=$countries item=country}
								<option value="{$country.id_country}">{$country.name|htmlentitiesUTF8}</option>
								{/foreach}
							</select>
						</div>
						<div class="col-lg-3">
							<select name="sp_id_group" id="sp_id_group">
								<option value="0">{l s='All groups'}</option>
								{foreach from=$groups item=group}
								<option value="{$group.id_group}">{$group.name}</option>
								{/foreach}
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-2" for="customer">{l s='Customer'}</label>
				<div class="col-lg-4">
					<input type="hidden" name="sp_id_customer" id="id_customer" value="0" />
					<div class="input-group">
						<input type="text" name="customer" value="{l s='All customers'}" id="customer" autocomplete="off" />
						<span class="input-group-addon"><i id="customerLoader" class="icon-refresh icon-spin" style="display: none;"></i> <i class="icon-search"></i></span>
					</div>
				</div>
			</div>
			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<div id="customers"></div>
				</div>
			</div>
			{if $combinations|@count != 0}
			<div class="form-group">
				<label class="control-label col-lg-2" for="sp_id_product_attribute">{l s='Combination:'}</label>
				<div class="col-lg-4">
					<select id="sp_id_product_attribute" name="sp_id_product_attribute">
						<option value="0">{l s='Apply to all combinations'}</option>
					{foreach from=$combinations item='combination'}
						<option value="{$combination.id_product_attribute}">{$combination.attributes}</option>
					{/foreach}
					</select>
				</div>
			</div>
			{/if}
			<div class="form-group">
				<label class="control-label col-lg-2" for="sp_from">{l s='Available'}</label>
				<div class="col-lg-9">
					<div class="row">
						<div class="col-lg-4">
							<div class="input-group">
								<span class="input-group-addon">{l s='from'}</span>
								<input type="text" name="sp_from" class="datepicker" value="" style="text-align: center" id="sp_from" />
								<span class="input-group-addon"><i class="icon-calendar-empty"></i></span>
							</div>
						</div>
						<div class="col-lg-4">
							<div class="input-group">
								<span class="input-group-addon">{l s='to'}</span>
								<input type="text" name="sp_to" class="datepicker" value="" style="text-align: center" id="sp_to" />
								<span class="input-group-addon"><i class="icon-calendar-empty"></i></span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-2" for="sp_from_quantity">{l s='Starting at'}</label>
				<div class="col-lg-4">
					<div class="input-group">
						<span class="input-group-addon">{l s='unit'}</span>
						<input type="text" name="sp_from_quantity" id="sp_from_quantity" value="1" />
					</div>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-2" for="sp_price">{l s='Product price'}
					{if $country_display_tax_label}
						{l s='(tax excl.)'}
					{/if}
				</label>
				<div class="col-lg-9">
					<div class="row">
						<div class="col-lg-4">
							<div class="input-group">
								<span class="input-group-addon">{$currency->prefix}{$currency->suffix}</span>
                                <input type="text"
                                    id="sp_price"
                                    name="sp_price"
                                    value="{displayPriceValue price=$product->price}"
                                    onkeyup="if (isArrowKey(event)) return;
                                             this.value = this.value.replace(/,/g, '.');"
                                    disabled="disabled"
                                />
							</div>
							<p class="checkbox">
								<label for="leave_bprice">{l s='Leave base price:'}</label>
								<input type="checkbox" id="leave_bprice" name="leave_bprice"  value="1" checked="checked"  />
							</p>
						</div>
					</div>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-2" for="sp_reduction">{l s='Apply a discount of'}</label>
				<div class="col-lg-4">
					<div class="row">
						<div class="col-lg-3">
							<input type="text" name="sp_reduction" id="sp_reduction" value="0.00"/>
						</div>
						<div class="col-lg-6">
							<select name="sp_reduction_type" id="sp_reduction_type">
								<option value="amount" selected="selected">{$currency->name|escape:'html':'UTF-8'}</option>
								<option value="percentage">{l s='%'}</option>
							</select>
						</div>
						<div class="col-lg-3">
							<select name="sp_reduction_tax" id="sp_reduction_tax">
								<option value="0">{l s='Tax excluded'}</option>
								<option value="1" selected="selected">{l s='Tax included'}</option>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		var currencyName = '{$currency->name|escape:'html':'UTF-8'|@addcslashes:'\''}';
		$(document).ready(function(){
			product_prices['0'] = $('#sp_current_ht_price').html();
			$('#id_product_attribute').change(function() {
				$('#sp_current_ht_price').html(product_prices[$('#id_product_attribute option:selected').val()]);
			});
			$('#leave_bprice').click(function() {
				if (this.checked)
					$('#sp_price').attr('disabled', 'disabled');
				else
					$('#sp_price').removeAttr('disabled');
			});
			$('.datepicker').datetimepicker({
				prevText: '',
				nextText: '',
				dateFormat: 'yy-mm-dd',
				// Define a custom regional settings in order to use PrestaShop translation tools
				currentText: '{l s='Now' js=1}',
				closeText: '{l s='Done' js=1}',
				ampm: false,
				amNames: ['AM', 'A'],
				pmNames: ['PM', 'P'],
				timeFormat: 'hh:mm:ss tt',
				timeSuffix: '',
				timeOnlyTitle: '{l s='Choose Time' js=1}',
				timeText: '{l s='Time' js=1}',
				hourText: '{l s='Hour' js=1}',
				minuteText: '{l s='Minute' js=1}'
			});
			$('#sp_reduction_type').on('change', function() {
				if (this.value == 'percentage')
					$('#sp_reduction_tax').hide();
				else
					$('#sp_reduction_tax').show();
			});
		});
	</script>
	<div class="table-responsive">
	<table id="specific_prices_list" class="table table-bordered">
		<thead>
			<tr>
				<th>{l s='Rule'}</th>
				<th>{l s='Combination'}</th>
				{if $multi_shop}<th>{l s='Shop'}</th>{/if}
				<th>{l s='Currency'}</th>
				<th>{l s='Country'}</th>
				<th>{l s='Group'}</th>
				<th>{l s='Customer'}</th>
				{if $country_display_tax_label}
					<th>{l s='Fixed price (tax excl.)'}</th>
				{else}
					<th>{l s='Fixed price'}</th>
				{/if}
				<th>{l s='Impact'}</th>
				<th>{l s='Period'}</th>
				<th>{l s='From (quantity)'}</th>
				<th>{l s='Action'}</th>
			</tr>
		</thead>
		<tbody>
			{$specificPriceModificationForm}
				<script type="text/javascript">
					$(document).ready(function() {
						delete_price_rule = '{l s="Do you really want to remove this price rule?"}';
						calcPriceTI();
						unitPriceWithTax('unit');
						});
				</script>
			{/if}
