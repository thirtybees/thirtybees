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
{extends file="helpers/form/form.tpl"}

{block name="input_row"}
{if $input.name == 'TB_CACHE_SYSTEM'}
<div id="{$input.name}_wrapper"{if $cacheDisabled} style="display:none"{/if}>{/if}
	{if $input.name == 'smarty_caching_type' || $input.name == 'smarty_clear_cache'}
	<div id="{$input.name}_wrapper"{if isset($fields_value.smarty_cache) && !$fields_value.smarty_cache} style="display:none"{/if}>{/if}
		{$smarty.block.parent}
		{if $input.name == 'TB_CACHE_SYSTEM' || $input.name == 'smarty_caching_type' || $input.name == 'smarty_clear_cache'}</div>{/if}
	{/block}

	{block name="input"}
		{if $input.type == 'radio' && $input.name == 'combination' && $input.disabled}
			<div class="alert alert-warning">
				{l s='This feature cannot be disabled because it is currently in use.'}
			</div>
		{/if}
		{$smarty.block.parent}
	{/block}

	{block name="description"}
		{$smarty.block.parent}
		{if $input.type == 'radio' && $input.name == 'combination'}
			<ul>
				<li>{l s='Combinations tab on product page'}</li>
				<li>{l s='Value'}</li>
				<li>{l s='Attribute'}</li>
			</ul>
		{elseif $input.type == 'radio' && $input.name == 'feature'}
			<ul>
				<li>{l s='Features tab on product page'}</li>
				<li>{l s='Feature'}</li>
				<li>{l s='Feature value'}</li>
			</ul>
		{/if}
	{/block}

	{block name="other_input"}
		{if $key == 'memcachedServers'}
			<div id="memcachedServers">
				<div id="formMemcachedServer">
					<div class="form-group">
						<label class="control-label col-lg-3">{l s='IP Address'} </label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="memcachedIp"/>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-lg-3">{l s='Port'} </label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="memcachedPort" value="11211"/>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-lg-3">{l s='Weight'} </label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="memcachedWeight" value="1"/>
						</div>
					</div>
					<div class="form-group">
						<div class="col-lg-9 col-lg-push-3">
							<input type="submit" value="{l s='Add Server'}" name="submitAddMemcachedServer" class="btn btn-default"/>
							<input type="button" value="{l s='Test Server'}" id="testMemcachedServer" class="btn btn-default"/>
						</div>
					</div>
				</div>
				{if isset($memcached_servers) && $memcached_servers}
					<div class="form-group">
						<table class="table">
							<thead>
							<tr>
								<th class="fixed-width-xs"><span class="title_box">{l s='ID'}</span></th>
								<th><span class="title_box">{l s='IP address'}</span></th>
								<th class="fixed-width-xs"><span class="title_box">{l s='Port'}</span></th>
								<th class="fixed-width-xs"><span class="title_box">{l s='Weight'}</span></th>
								<th>&nbsp;</th>
							</tr>
							</thead>
							<tbody>
							{foreach $memcached_servers AS $server}
								<tr>
									<td>{$server.id_memcached_server}</td>
									<td>{$server.ip}</td>
									<td>{$server.port}</td>
									<td>{$server.weight}</td>
									<td>
										<a class="btn btn-default" href="{$currentIndex|escape:'html':'UTF-8'}&amp;token={$token|escape:'html':'UTF-8'}&amp;deleteMemcachedServer={$server.id_memcached_server}" onclick="if (!confirm('{l s='Do you really want to remove the server %s:%s' sprintf=[$server.ip, $server.port] js=1}')) {ldelim}return false;{rdelim}"><i class="icon-minus-sign-alt"></i> {l s='Remove'}</a>
									</td>
								</tr>
							{/foreach}
							</tbody>
						</table>
					</div>
				{/if}
			</div>
		{elseif $key == 'redisServers'}
			<div id="redisServers">
				<div class="form-group">
					<div class="col-lg-9 col-lg-push-3">
						<button id="addRedisServer" class="btn btn-default" type="button">
							<i class="icon-plus-sign-alt"></i>&nbsp;{l s='Add server'}
						</button>
					</div>
				</div>
				<div id="formRedisServer" style="display:none;">
					<div class="form-group">
						<label class="control-label col-lg-3">{l s='IP Address'} </label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="redisIp" value="127.0.0.1"/>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-lg-3">{l s='Port'} </label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="redisPort" value="6379"/>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-lg-3">{l s='Auth'} </label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="redisAuth" value=""/>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-lg-3">{l s='Database ID'} </label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="redisDb" value="0"/>
						</div>
					</div>
					<div class="form-group">
						<div class="col-lg-9 col-lg-push-3">
							<input type="submit" value="{l s='Add Server'}" name="submitAddRedisServer" class="btn btn-default"/>
							<input type="button" value="{l s='Test Server'}" id="testRedisServer" class="btn btn-default"/>
						</div>
					</div>
				</div>
				{if isset($redis_servers) && $redis_servers}
					<div class="form-group">
						<table class="table">
							<thead>
							<tr>
								<th class="fixed-width-xs"><span class="title_box">{l s='ID'}</span></th>
								<th><span class="title_box">{l s='IP address'}</span></th>
								<th class="fixed-width-xs"><span class="title_box">{l s='Port'}</span></th>
								<th class="fixed-width-xs"><span class="title_box">{l s='Auth'}</span></th>
								<th class="fixed-width-xs"><span class="title_box">{l s='Db'}</span></th>
								<th>&nbsp;</th>
							</tr>
							</thead>
							<tbody>
							{foreach $redis_servers AS $server}
								<tr>
									<td>{$server.id_redis_server}</td>
									<td>{$server.ip}</td>
									<td>{$server.port}</td>
									<td>{$server.auth}</td>
									<td>{$server.db}</td>
									<td>
										<a class="btn btn-default" href="{$currentIndex|escape:'html':'UTF-8'}&amp;token={$token|escape:'html':'UTF-8'}&amp;deleteRedisServer={$server.id_redis_server}" onclick="if (!confirm('{l s='Do you really want to remove the server %s:%s' sprintf=[$server.ip, $server.port] js=1}')) {ldelim}return false;{rdelim}"><i class="icon-minus-sign-alt"></i> {l s='Remove'}</a>
									</td>
								</tr>
							{/foreach}
							</tbody>
						</table>
					</div>
				{/if}
			</div>
		{elseif $key === 'dynamicHooks'}
			<div class="table-responsive-row clearfix">
				<table class="table meta">
					<thead>
						<tr class="nodrag nodrop">
							<th class="title_box">
								<span class="title_box">
									{l s='Module name'}
								</span>
							</th>
							<th>
								<span class="title_box">
									{l s='Hooks'}
								</span>
							</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$moduleSettings key=idModule item=module}
							<tr>
								<td>
									{$module['displayName']|escape:'htmlall':'UTF-8'}
								</td>
								<td>
									{foreach from=$module['hooks'] key=hookName item=enabled}
										&nbsp;
										<span
												style="cursor: pointer"
												class="dynamic-hook label label-{if $enabled}success{else}danger{/if}"
												data-id-module="{$idModule|intval}"
												data-hook-name="{$hookName|escape:'htmlall':'UTF-8'}"
												data-enabled="{if $enabled}true{else}false{/if}">
											{$hookName|escape:'htmlall':'UTF-8'}
										</span>
									{/foreach}
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		{elseif $key === 'controllerList'}
			<div class="form-group">
				<label class="control-label col-lg-3">{l s='Controllers'}</label>
				<div class="col-lg-9">
					<div class="well">
						<div>
							{l s='Please specify the controllers for which you would like to enable full page caching.'}<br />
							{l s='Please input each controller name, separated by a comma (",").'}<br />
							{l s='You can also click the controller name in the list below, and even make a multiple selection by keeping the CTRL key pressed while clicking, or choose a whole range of filename by keeping the SHIFT key pressed while clicking.'}<br />
							{$controllerList}
						</div>
					</div>
				</div>
			</div>
		{/if}
	{/block}

	{block name="script"}

	function showMemcached() {
		if ($('input[name="TB_CACHE_SYSTEM"]:radio:checked').val() == 'CacheMemcache' || $('input[name="TB_CACHE_SYSTEM"]:radio:checked').val() == 'CacheMemcached') {
			$('#memcachedServers').css('display', $('#TB_CACHE_ENABLED_on').is(':checked') ? 'block' : 'none');
			$('#ps_cache_fs_directory_depth').closest('.form-group').hide();
			$('#redisServers').hide();
			$('#memcachedServers').show();

		}
		else if ($('input[name="TB_CACHE_SYSTEM"]:radio:checked').val() == 'CacheFs') {
			$('#memcachedServers').hide();
			$('#redisServers').hide();
			$('#ps_cache_fs_directory_depth').closest('.form-group').css('display', $('#TB_CACHE_ENABLED_on').is(':checked') ? 'block' : 'none');
		} else if ($('input[name="TB_CACHE_SYSTEM"]:radio:checked').val() == 'CacheRedis') {
			$('#redisServers').css('display', $('#TB_CACHE_ENABLED_on').is(':checked') ? 'block' : 'none');
			$('#ps_cache_fs_directory_depth').closest('.form-group').hide();
			$('#redisServers').show();
			$('#memcachedServers').hide();
		} else {
			$('#memcachedServers').hide();
			$('#redisServers').hide();
			$('#ps_cache_fs_directory_depth').closest('.form-group').hide();
		}
	}

	function processDynamicHook($elem) {
		if (window.dynamicHooksBlocked) {
			setTimeout(function() {
				processDynamicHook($elem);
			}, 100);

			return;
		}

		window.dynamicHooksBlocked = true;
		$.ajax({
			url: '{$currentIndex|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}',
			method: 'POST',
			data: {
				ajax: true,
				action: 'updateDynamicHooks',
				idModule: parseInt($elem.attr('data-id-module'), 10),
				hookName: $elem.attr('data-hook-name'),
				status: ($elem.attr('data-enabled') === 'true') ? 'false' : 'true'
			},
			dataType: 'JSON',
			success: function() {
				showSuccessMessage('{l s='Hook successfully updated' js=1}');
				var newStatus = !($elem.attr('data-enabled') === 'true');
				$elem.attr('data-enabled', (newStatus ? 'true' : 'false'));
				if (newStatus) {
					$elem.removeClass('label-danger').addClass('label-success');
				} else {
					$elem.removeClass('label-success').addClass('label-danger');
				}
			},
			error: function() {
				showErrorMessage('{l s='There was a problem while updating the hook' js=1}');
			},
			complete: function() {
				window.dynamicHooksBlocked = false;
			}
		});
	}

	function showDynamicHooks() {
		window.dynamicHooksBlocked = false;
		$('.dynamic-hook').each(function() {
			$(this).click(function() {
				processDynamicHook($(this));
			});
		});
	}

	function position_exception_textchange() {
		var obj = $(this);
		var shopID = obj.attr('id').replace(/\D/g, '');
		var list = obj.closest('form').find('#em_list_' + shopID);
		var values = obj.val().split(',');
		var len = values.length;

		list.find('option').prop('selected', false);
		for (var i = 0; i < len; i++) {
			list.find('option[value="' + $.trim(values[i]) + '"]').prop('selected', true);
		}
	}

	function position_exception_listchange() {
		var obj = $(this);
		var shopID = obj.attr('id').replace(/\D/g, '');
		var val = obj.val();
		var str = '';
		if (val) {
			str = val.join(', ');
		}
		obj.closest('form').find('#em_text_' + shopID).val(str);
	}

	$(document).ready(function () {

		showMemcached();

		showDynamicHooks();

		$('input[name="cache_active"]').change(function () {
			$('#TB_CACHE_SYSTEM_wrapper').css('display', ($(this).val() == 1) ? 'block' : 'none');
			showMemcached();

			if ($('input[name="TB_CACHE_SYSTEM"]:radio:checked').val() == 'CacheFs') {
				$('#ps_cache_fs_directory_depth').focus();
			}
		});

		$('input[name="TB_CACHE_SYSTEM"]').change(function () {
			$('#cache_up').val(1);
			showMemcached();

			if ($('input[name="TB_CACHE_SYSTEM"]:radio:checked').val() == 'CacheFs') {
				$('#ps_cache_fs_directory_depth').focus();
			}
		});

		$('input[name="smarty_cache"]').change(function () {
			$('#smarty_caching_type_wrapper').css('display', ($(this).val() == 1) ? 'block' : 'none');
			$('#smarty_clear_cache_wrapper').css('display', ($(this).val() == 1) ? 'block' : 'none');
		});

		$('#addMemcachedServer').click(function () {
			$('#formMemcachedServer').show();
			return false;
		});

		$('#testMemcachedServer').click(function () {
			var host = $('input:text[name=memcachedIp]').val();
			var port = $('input:text[name=memcachedPort]').val();
			var type = $('input[name="TB_CACHE_SYSTEM"]:radio:checked').val() == 'CacheMemcached' ? 'memcached' : 'memcache';
			if (host && port) {
				$.ajax({
					url: 'index.php',
					data: {
						controller: 'adminperformance',
						token: '{$token|escape:'html':'UTF-8'}',
						action: 'test_server',
						sHost: host,
						sPort: port,
						type: type,
						ajax: true
					},
					context: document.body,
					dataType: 'json',
					async: false,
					success: function (data) {
						if (data && $.isArray(data)) {
							var color = data[0] != 0 ? 'green' : 'red';
							{*$('#formMemcachedServerStatus').show();*}
							$('input:text[name=memcachedIp]').css('background', color);
							$('input:text[name=memcachedPort]').css('background', color);
						}
					}
				});
			}
			return false;
		});

		$('#addRedisServer').click(function () {
			$('#formRedisServer').show();
			return false;
		});

		$('#testRedisServer').click(function () {
			var host = $('input:text[name=redisIp]').val();
			var port = $('input:text[name=redisPort]').val();
			var auth = $('input:text[name=redisAuth]').val();
			var db = $('input:text[name=redisDb]').val();
			var type = $('input[name="TB_CACHE_SYSTEM"]:radio:checked').val() == 'redis';
			if (host && port) {
				$.ajax({
					url: 'index.php',
					data: {
						controller: 'adminperformance',
						token: '{$token|escape:'html':'UTF-8'}',
						action: 'test_redis_server',
						sHost: host,
						sPort: port,
						sDb: db,
						sAuth: auth,
						type: type,
						ajax: true
					},
					context: document.body,
					dataType: 'json',
					success: function (data) {
						if (data && $.isArray(data)) {
							var color = data[0] != 0 ? 'lightgreen' : 'red';
							$('#formRedisServerStatus').show();
							$('input:text[name=redisIp]').css('background', color);
							$('input:text[name=redisPort]').css('background', color);
							$('input:text[name=redisAuth]').css('background', color);
							$('input:text[name=redisDb]').css('background', color);
						}
					}
				});
			}
			return false;
		});

		$('input[name="smarty_force_compile"], input[name="smarty_cache"], input[name="smarty_clear_cache"], input[name="smarty_caching_type"], input[name="smarty_console"], input[name="smarty_console_key"]').change(function () {
			$('#smarty_up').val(1);
		});

		$('input[name="combination"], input[name="feature"], input[name="customer_group"]').change(function () {
			$('#features_detachables_up').val(1);
		});

		$('input[name="_MEDIA_SERVER_1_"], input[name="_MEDIA_SERVER_2_"], input[name="_MEDIA_SERVER_3_"]').change(function () {
			$('#media_server_up').val(1);
		});

		$('input[name="PS_CIPHER_ALGORITHM"]').change(function () {
			$('#ciphering_up').val(1);
		});

		$('input[name="TB_CACHE_ENABLED"]').change(function () {
			$('#cache_up').val(1);
		});

		$('form[id="configuration_form"] input[id^="em_text_"]').each(function(){
			$(this).change(position_exception_textchange).change();
		});
		$('form[id="configuration_form"] select[id^="em_list_"]').each(function(){
			$(this).change(position_exception_listchange);
		});
	});
	{/block}
