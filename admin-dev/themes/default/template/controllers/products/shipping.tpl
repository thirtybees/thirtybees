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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<div id="product-shipping" class="panel product-tab">
	<input type="hidden" name="submitted_tabs[]" value="Shipping" />
	<h3>{l s='Shipping'}</h3>

	{if isset($display_common_field) && $display_common_field}
		<div class="alert alert-info">{l s='Warning, if you change the value of fields with an orange bullet %s, the value will be changed for all other shops for this product' sprintf=$bullet_common_field}</div>
	{/if}

	{include file="controllers/products/multishop/check_fields.tpl" product_tab="Shipping"}

	<div class="form-group">
		<label class="control-label col-lg-2 col-lg-offset-1" for="width">{$bullet_common_field} {l s='Package width'}</label>
		<div class="col-lg-9">
			<div class="input-group col-lg-3">
				<span class="input-group-addon">{$ps_dimension_unit}</span>
				<input maxlength="14" id="width" name="width" type="text" value="{$product->width}" onkeyup="if (isArrowKey(event)) return ;this.value = this.value.replace(/,/g, '.');" />
			</div>
		</div>
	</div>

	<div class="form-group">
		<label class="control-label col-lg-2 col-lg-offset-1" for="height">{$bullet_common_field} {l s='Package height'}</label>
		<div class="col-lg-9">
			<div class="input-group col-lg-3">
				<span class="input-group-addon">{$ps_dimension_unit}</span>
				<input maxlength="14" id="height" name="height" type="text" value="{$product->height}" onkeyup="if (isArrowKey(event)) return ;this.value = this.value.replace(/,/g, '.');" />
			</div>
		</div>
	</div>

	<div class="form-group">
		<label class="control-label col-lg-2 col-lg-offset-1" for="depth">{$bullet_common_field} {l s='Package depth'}</label>
		<div class="col-lg-9">
			<div class="input-group col-lg-3">
				<span class="input-group-addon">{$ps_dimension_unit}</span>
				<input maxlength="14" id="depth" name="depth" type="text" value="{$product->depth}" onkeyup="if (isArrowKey(event)) return ;this.value = this.value.replace(/,/g, '.');" />
			</div>
		</div>
	</div>

	<div class="form-group">
		<label class="control-label col-lg-2 col-lg-offset-1" for="weight">{$bullet_common_field} {l s='Package weight'}</label>
		<div class="col-lg-9">
			<div class="input-group col-lg-3">
				<span class="input-group-addon">{$ps_weight_unit}</span>
				<input maxlength="14" id="weight" name="weight" type="text" value="{$product->weight}" onkeyup="if (isArrowKey(event)) return ;this.value = this.value.replace(/,/g, '.');" />
			</div>
			{if isset($packWeight)}
			<p class="help-block">
				{l s='Calculated weight of pack items is [1]%1$s %2$s[/1]' sprintf=[$packWeight, $ps_weight_unit] tags=['<a class="copy-weight" href="#">']}
			</p>
			{/if}
		</div>
	</div>

	<div class="form-group">
		<div class="col-lg-1"><span class="pull-right">{include file="controllers/products/multishop/checkbox.tpl" field="additional_shipping_cost" type="default"}</span></div>
		<label class="control-label col-lg-2" for="additional_shipping_cost">
			<span class="label-tooltip" data-toggle="tooltip"
				title="{l s='If a carrier has a tax, it will be added to the shipping fees.'}">
				{l s='Additional shipping fees'}
			</span>

		</label>
		<div class="col-lg-9">
			<div class="input-group col-lg-3">
				<span class="input-group-addon">{$currency->prefix}{$currency->suffix} {if $country_display_tax_label}({l s='tax excl.'}){/if}</span>
				<input type="text" id="additional_shipping_cost" name="additional_shipping_cost" onchange="this.value = this.value.replace(/,/g, '.');" value="{$product->additional_shipping_cost|htmlentities}" />
			</div>
		</div>
	</div>

	<div class="form-group">
		<div class="col-lg-1"><span class="pull-right">{include file="controllers/products/multishop/checkbox.tpl" field="carrierSelection" type="selection"}</span></div>
		<label class="control-label col-lg-2" for="availableCarriers">{l s='Carriers'}</label>
		<div class="col-lg-9">
			<div class="form-control-static row" id="carrierSelection" data-selection-source="availableCarriers" data-selection-target="selectedCarriers">
				<div class="col-xs-6">
					<p>{l s='Available carriers'}</p>
					<select id="availableCarriers" name="availableCarriers" multiple="multiple" {if count($carrier_list)>4}style="height:12em"{/if}>
						{foreach $carrier_list as $carrier}
							{if !isset($carrier.selected) || !$carrier.selected}
								<option value="{$carrier.id_reference}">{$carrier.name}</option>
							{/if}
						{/foreach}
					</select>
					<a href="#" id="addCarrier" class="btn btn-default btn-block">{l s='Add'} <i class="icon-arrow-right"></i></a>
				</div>
				<div class="col-xs-6">
					<p>{l s='Selected carriers'}</p>
					<select id="selectedCarriers" name="selectedCarriers[]" multiple="multiple" {if count($carrier_list)>4}style="height:12em"{/if}>
						{foreach $carrier_list as $carrier}
							{if isset($carrier.selected) && $carrier.selected}
								<option value="{$carrier.id_reference}">{$carrier.name}</option>
							{/if}
						{/foreach}
					</select>
					<a href="#" id="removeCarrier" class="btn btn-default btn-block"><i class="icon-arrow-left"></i> {l s='Remove'}</a>
				</div>
			</div>
		</div>
	</div>
	<div class="form-group" id="no-selected-carries-alert">
		<div class="col-lg-offset-3">
			<div class="alert alert-warning">{l s='If no carrier is selected then all the carriers will be available for customers orders.'}</div>
		</div>
	</div>
	<div class="panel-footer">
		<a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay'}</button>
	</div>
</div>

<div id="product-shipping-compliance" class="panel product-tab">
	<h3>{l s='Compliance'}</h3>

	<div class="form-group">
		<label class="control-label col-lg-3" for="hs_code">
			<span class="label-tooltip" data-toggle="tooltip" title="{l s='6–12 digit Harmonized System code.'}">
				{l s='HS code'}
			</span>
		</label>
		<div class="col-lg-3">
			<input type="text" name="hs_code" id="hs_code"
				   value="{$product->hs_code|escape:'html':'UTF-8'}"
				   maxlength="12" pattern="{literal}[0-9]{6,12}{/literal}"/>
		</div>
	</div>

	<div class="form-group">
		<label class="control-label col-lg-3" for="country_of_origin">
			{l s='Country of origin'}
		</label>
		<div class="col-lg-3">
			<select name="country_of_origin" id="country_of_origin">
				<option value="0">--</option>
				{foreach from=$countries item=c}
					<option value="{$c.id_country}"
							{if $product->country_of_origin == $c.id_country}selected="selected"{/if}>
						{$c.name|escape:'html':'UTF-8'}
					</option>
				{/foreach}
			</select>
		</div>
	</div>

	<div class="form-group">
		<label class="control-label col-lg-3" for="age_verification">
			{l s='Age verification required'}
		</label>
		<div class="col-lg-9">
			<input type="checkbox" name="age_verification" id="age_verification"
				   value="1" {if $product->age_verification}checked="checked"{/if}/>
			<p class="help-block">{l s='Tick if this product is age-restricted (alcohol, tobacco, knives, etc). The minimum age threshold is configured shop-wide.'}</p>
		</div>
	</div>

	<div class="form-group">
		<label class="control-label col-lg-3">{l s='Dangerous goods'}</label>
		<div class="col-lg-9">
        <span class="switch prestashop-switch fixed-width-lg">
            <input type="radio" name="dangerous_goods" id="dg_on"
				   value="1" {if $product->dangerous_goods}checked="checked"{/if}/>
            <label for="dg_on">{l s='Yes'}</label>
            <input type="radio" name="dangerous_goods" id="dg_off"
				   value="0" {if !$product->dangerous_goods}checked="checked"{/if}/>
            <label for="dg_off">{l s='No'}</label>
            <a class="slide-button btn"></a>
        </span>
		</div>
	</div>

	<div class="form-group dg-detail">
		<label class="control-label col-lg-3" for="un_number">{l s='UN number'}</label>
		<div class="col-lg-2">
			<input type="text" name="un_number" id="un_number" maxlength="4"
				   pattern="{literal}[0-9]{4}{/literal}" value="{$product->un_number|escape:'html':'UTF-8'}"/>
		</div>
		<label class="control-label col-lg-1" for="hazard_class">{l s='Class'}</label>
		<div class="col-lg-1">
			<input type="text" name="hazard_class" id="hazard_class" maxlength="8"
				   value="{$product->hazard_class|escape:'html':'UTF-8'}"/>
		</div>
		<label class="control-label col-lg-1" for="packing_group">{l s='PG'}</label>
		<div class="col-lg-1">
			<select name="packing_group" id="packing_group">
				{foreach from=['','I','II','III'] item=pg}
					<option value="{$pg}" {if $product->packing_group == $pg}selected{/if}>
						{if $pg == ''}--{else}{$pg}{/if}
					</option>
				{/foreach}
			</select>
		</div>
	</div>

	<div class="panel-footer">
		<a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay'}</button>
	</div>
</div>

{if isset($packWeight)}
<script>
	$('.copy-weight').on('click', function() {
		$('#weight').val("{$packWeight|floatval}");
		event.stopPropagation();
		event.preventDefault();
	});
</script>
{/if}

<script>
	$(function () {
		const toggle = () => {
			$('.dg-detail').toggle($('input[name=dangerous_goods]:checked').val() === '1');
		};
		$('input[name=dangerous_goods]').on('change', toggle);
		toggle();
	});
</script>