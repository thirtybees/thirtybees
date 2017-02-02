{extends file="helpers/list/list_content.tpl"}

{block name="td_content"}
	{if isset($params.prefix)}{$params.prefix|escape:'htmlall':'UTF-8'}{/if}
	{if isset($params.badge_success) && $params.badge_success && isset($tr.badge_success) && $tr.badge_success == $params.badge_success}<span class="badge badge-success">{/if}
{if isset($params.badge_warning) && $params.badge_warning && isset($tr.badge_warning) && $tr.badge_warning == $params.badge_warning}
	<span class="badge badge-warning">{/if}
{if isset($params.badge_danger) && $params.badge_danger && isset($tr.badge_danger) && $tr.badge_danger == $params.badge_danger}
	<span class="badge badge-danger">{/if}
{if isset($params.color) && isset($tr[$params.color])}
	<span class="label color_field"
		  style="background-color:{$tr[$params.color]|escape:'htmlall':'UTF-8'};color:{if Tools::getBrightness($tr[$params.color]) < 128}white{else}#383838{/if}">
{/if}
	{if isset($tr.$key)}
		{if $params.type == 'fx_service'}
			<div>
				{assign var=services value=CurrencyRateModule::getServices($tr.id_currency, $tr.$key)}
				{if is_array($services) && count($services)}
					<select id="fx_service_{$tr['id_currency']|intval}" name="{$key|escape:'htmlall':'UTF-8'}">
						{foreach $services as $service}
							<option value="{$service.id_module|intval}"{if $service.selected} selected="selected"{/if}>{$service.display_name|escape:'htmlall':'UTF-8'}</option>
						{/foreach}
					</select>
					<script type="text/javascript">
						$(document).ready(function() {
							$('#fx_service_{$tr['id_currency']|intval}').change(function () {
								console.log($(this));
								$.ajax({
									url: 'index.php?controller=AdminCurrencies&token=' + window.token,
									dataType: 'JSON',
									type: 'POST',
									data: {
										ajax: true,
										action: 'updateFxService',
										idModule: parseInt($(this).val(), 10),
										idCurrency: {$tr['id_currency']|intval}
									},
									success: function (response) {
										if (response.success) {
											showSuccessMessage('{l s='Successfully changed' js=1}');
										} else {
											showErrorMessage('{l s='Could not change the fx service' js=1}');
										}
									},
									error: function () {
										showErrorMessage('{l s='Could not change the fx service' js=1}');
									}
								});
							});
						});
					</script>
					{else}
					---
				{/if}
			</div>
			<div class="show-xs show-sm hidden-md hidden-lg hidden-xl">
				{$tr.$key|escape:'htmlall':'UTF-8'}
			</div>
		{elseif isset($params.active)}
			{$tr.$key}
		{elseif isset($params.activeVisu)}
			{if $tr.$key}
			<i class="icon-check-ok"></i>
				{l s='Enabled'}
			{else}
				<i class="icon-remove"></i>
			{l s='Disabled'}
		{/if}
		{elseif isset($params.position)}
			{if !$filters_has_value && $order_by == 'position' && $order_way != 'DESC'}
			<div class="dragGroup">
					<div class="positions">
						{($tr.$key.position + 1)|escape:'htmlall':'UTF-8'}
					</div>
				</div>
		{else}
			{($tr.$key.position + 1)|escape:'htmlall':'UTF-8'}
		{/if}
		{elseif isset($params.image)}
			{$tr.$key|escape:'htmlall':'UTF-8'}
		{elseif isset($params.icon)}
			{if is_array($tr[$key])}
			{if isset($tr[$key]['class'])}
				<i class="{$tr[$key]['class']|escape:'htmlall':'UTF-8'}"></i>
				{else}
					<img src="../img/admin/{$tr[$key]['src']|escape:'htmlall':'UTF-8'}"
						 alt="{$tr[$key]['alt']|escape:'htmlall':'UTF-8'}"
						 title="{$tr[$key]['alt']|escape:'htmlall':'UTF-8'}"/>
			{/if}
		{/if}
		{elseif isset($params.type) && $params.type == 'price'}
			{displayPrice price=$tr.$key}
		{elseif isset($params.float)}
			{$tr.$key}
		{elseif isset($params.type) && $params.type == 'date'}
			{dateFormat date=$tr.$key full=0}
		{elseif isset($params.type) && $params.type == 'datetime'}
			{dateFormat date=$tr.$key full=1}
		{elseif isset($params.type) && $params.type == 'decimal'}
			{$tr.$key|string_format:"%.2f"}
		{elseif isset($params.type) && $params.type == 'percent'}
			{$tr.$key} {l s='%'}
		{* If type is 'editable', an input is created *}
		{elseif isset($params.type) && $params.type == 'editable' && isset($tr.id)}
			<input type="text" name="{$key|escape:'htmlall':'UTF-8'}_{$tr.id|escape:'htmlall':'UTF-8'}"
				   value="{$tr.$key|escape:'html':'UTF-8'}" class="{$key|escape:'htmlall':'UTF-8'}"/>
		{elseif isset($params.callback)}
			{if isset($params.maxlength) && Tools::strlen($tr.$key) > $params.maxlength}
			<span title="{$tr.$key}">{$tr.$key|truncate:$params.maxlength:'...'|escape:'htmlall':'UTF-8'}</span>
		{else}
			{$tr.$key}
		{/if}
		{elseif $key == 'color'}
			{if !is_array($tr.$key)}
			<div style="background-color: {$tr.$key};" class="attributes-color-container"></div>
			{else} {*TEXTURE*}
				<img src="{$tr.$key.texture|escape:'htmlall':'UTF-8'}" alt="{$tr.name|escape:'htmlall':'UTF-8'}"
					 class="attributes-color-container"/>
		{/if}
		{elseif isset($params.maxlength) && Tools::strlen($tr.$key) > $params.maxlength}
			<span title="{$tr.$key}">{$tr.$key|truncate:$params.maxlength:'...'}</span>
		{else}
			{$tr.$key}
		{/if}
	{else}
		{block name="default_field"}--{/block}
	{/if}
	{if isset($params.suffix)}{$params.suffix|escape:'htmlall':'UTF-8'}{/if}
{if isset($params.color) && isset($tr.color)}
	</span>
{/if}
{if isset($params.badge_danger) && $params.badge_danger && isset($tr.badge_danger) && $tr.badge_danger == $params.badge_danger}
	</span>
{/if}
{if isset($params.badge_warning) && $params.badge_warning && isset($tr.badge_warning) && $tr.badge_warning == $params.badge_warning}
	</span>
{/if}
	{if isset($params.badge_success) && $params.badge_success && isset($tr.badge_success) && $tr.badge_success == $params.badge_success}</span>{/if}
{/block}
