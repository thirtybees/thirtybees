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

{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
	<script type="text/javascript">
		$(document).ready(function() {
			checkFiles();
			runTests();
		});

		function checkFiles() {
			$.ajax({
				type: 'GET',
				url: '{$link->getAdminLink('AdminInformation')|addslashes}',
				data: {
					'action': 'checkFiles',
					'ajax': 1
				},
				dataType: 'json',
				success: function(json)
				{
					const tab = {
						'missing': '{l s='Missing files'}',
						'updated': '{l s='Updated files'}',
						'obsolete': '{l s='Obsolete files'}'
					};

					if (json.missing.length || json.updated.length || json.obsolete.length || json.listMissing) {
						let text = '<div class="alert alert-warning">';
						if (json.isDevelopment) {
							text += '{l s='This is a development installation, so the following is not unexpected: '}';
						}
						if (json.listMissing) {
							text += '{l s='File @s1 missing, can\'t check any files.'}'.replace('@s1', json.listMissing);
						} else {
							text += '{l s='Changed/missing/obsolete files have been detected.'}';
						}
						text += '</div>';
						$('#changedFiles').html(text);
					} else {
						$('#changedFiles').html('<div class="alert alert-success">{l s='No change has been detected in your files.'}</div>');
					}

					$.each(tab, function (key, lang) {
						if (json[key].length) {
							let html = $('<ul>').attr('id', key + '_files');
							$(json[key]).each(function (key, file) {
								html.append($('<li>').html(file))
							});
							$('#changedFiles')
								.append($('<h4>').html(lang + ' (' + json[key].length + ')'))
								.append(html);
						}
					});
				}
			});
		}

		function runTests() {
			$.ajax({
				type: 'GET',
				url: '{$link->getAdminLink('AdminInformation')|addslashes}',
				data: {
					'action': 'runTests',
					'ajax': 1
				},
				dataType: 'json',
				success: function(json)
				{
					if (json.status === 'success') {
						const data = json.data;
						const required = data.required;
						const optional = data.optional;
						if (required.length === 0 && optional.length === 0) {
							$('#testResults').html('<div class="alert alert-success">{l s='All tests passed successfully'|escape:'javascript':'UTF-8'}<div>');
						} else {
							$('#testResults').html(
								renderTestResults(required, 'danger', '{l s='Some required tests failed'}') +
								renderTestResults(optional, 'warning', '{l s='Some optional tests failed'}')
							);
						}
					} else {
						$('#testResults').html('<div class="alert alert-danger">'+json.message+'<div>');
					}
				},
				error: function(response, message, c) {
					console.log(response, message, c);
					if (response.responseJSON) {
						$('#testResults').html('<div class="alert alert-danger">'+response.responseJSON.message+'<div>');
					} else {
						$('#testResults').html('<div class="alert alert-danger">{l s='Error: '}' + message + '<div>');
					}
				}
			});
		}

		function renderTestResults(tests, clazz, label) {
			let content = '';
			if (tests.length > 0) {
				content += '<div class="alert alert-' + clazz + '"><strong>' + label + '</strong>';
				content += '<ul>';
				for (let i = 0; i < tests.length; i++) {
					const entry = tests[i];
					let message = entry.message;
					if (entry.extra && entry.extra.length > 0) {
						message += '<ul>';
						for (j = 0; j < entry.extra.length; j++) {
							message += '<li>' + entry.extra[j] + '</li>';
						}
						message += '</ul>';
					}
					content += '<li>' + message + '</li>';
				}
				content += '</ul>';
				content += '</div>'
			}
			return content;
		}

	</script>
	<div class="row">
		<div class="col-lg-6">
			<div class="panel">
				<h3>
					<i class="icon-info"></i>
					{l s='Configuration information'}
				</h3>
				<p>{l s='This information must be provided when you report an [1]issue[/1] on github or [2]forum[/2].' tags=['<a href="https://github.com/thirtybees/thirtybees/issues" target="_blank" rel="noreferrer">', '<a href="https://forum.thirtybees.com/" target="_blank" rel="noreferrer">']}</p>
			</div>
			<div class="panel">
				<h3>
					<i class="icon-info"></i>
					{l s='Server information'}
				</h3>
				{if $uname}
				<p>
					<strong>{l s='Server information:'}</strong> {$uname|escape:'html':'UTF-8'}
				</p>
				{/if}
				<p>
					<strong>{l s='Server software version:'}</strong> {$version.server|escape:'html':'UTF-8'}
				</p>
				<p>
					<strong>{l s='PHP version:'}</strong> <a href="{$version.phpinfoUrl}" target="_blank">{$version.php|escape:'html':'UTF-8'}</a>
				</p>
				<p>
					<strong>{l s='Memory limit:'}</strong> {$version.memory_limit|escape:'html':'UTF-8'}
				</p>
				<p>
					<strong>{l s='Max execution time:'}</strong> {$version.max_execution_time|escape:'html':'UTF-8'}
				</p>
				{if $apache_instaweb}
					<p>{l s='PageSpeed module for Apache installed (mod_instaweb)'}</p>
				{/if}
			</div>
			<div class="panel">
				<h3>
					<i class="icon-info"></i>
					{l s='Database information'}
				</h3>
				<p>
					<strong>{l s='MySQL version:'}</strong> {$database.version|escape:'html':'UTF-8'}
				</p>
				<p>
					<strong>{l s='MySQL server:'}</strong> {$database.server|escape:'html':'UTF-8'}
				</p>
				<p>
					<strong>{l s='MySQL name:'}</strong> {$database.name|escape:'html':'UTF-8'}
				</p>
				<p>
					<strong>{l s='MySQL user:'}</strong> {$database.user|escape:'html':'UTF-8'}
				</p>
				<p>
					<strong>{l s='Tables prefix:'}</strong> {$database.prefix|escape:'html':'UTF-8'}
				</p>
				<p>
					<strong>{l s='MySQL engine:'}</strong> {$database.engine|escape:'html':'UTF-8'}
				</p>
				<p>
					<strong>{l s='MySQL driver:'}</strong> {$database.driver|escape:'html':'UTF-8'}
				</p>
			</div>
		</div>
		<div class="col-lg-6">
			<div class="panel">
				<h3>
					<i class="icon-info"></i>
					{l s='Store information'}
				</h3>
				<p>
					<strong>{l s='Thirty bees version:'}</strong> {$shop.version|escape:'html':'UTF-8'}
				</p>
				<p>
					<strong>{l s='Thirty bees revision:'}</strong>
					<a target="_blank" rel="noreferrer" href="https://github.com/thirtybees/thirtybees{if $shop.revision != 'development'}/tree/{$shop.revision}{/if}">
						{$shop.revision|escape:'html':'UTF-8'}
					</a>
				</p>
				{if $shop.build_php}
				<p>
					<strong>{l s='Build for PHP version:'}</strong> {$shop.build_php}
					{if $shop.wrong_php}
					<div class="text-danger">
						<i class="icon-warning"></i>
						{l s='Your server is running on PHP version %s. You should use core updater and fix your installation' sprintf=[$version.php]}
					</div>
					{/if}
				</p>
				{/if}
				<p>
					<strong>{l s='Shop URL:'}</strong>
					<a target="_blank" href="{$shop.url}">
						{$shop.url|escape:'html':'UTF-8'}
					</a>
				</p>
				<p>
					<strong>{l s='Shop Root Directory:'}</strong> {$shop.rootDir|escape:'html':'UTF-8'}
				</p>
				<p>
					<strong>{l s='Current theme in use:'}</strong> {$shop.theme|escape:'html':'UTF-8'}
				</p>
			</div>

			<div class="panel">
				<h3>
					<i class="icon-info"></i>
					{l s='Your information'}
				</h3>
				<p>
					<strong>{l s='Your web browser:'}</strong> {$user_agent|escape:'html':'UTF-8'}
				</p>
			</div>

			<div class="panel" id="checkConfiguration">
				<h3>
					<i class="icon-info"></i>
					{l s='Check your configuration'}
				</h3>
				<div id="testResults"><i class="icon-spin icon-refresh"></i> {l s='Running configuration tests, please wait...'}</div>
			</div>
		</div>
	</div>
	<div class="panel">
		<h3>
			<i class="icon-info"></i>
			{l s='List of changed files'}
		</h3>
		<div id="changedFiles"><i class="icon-spin icon-refresh"></i> {l s='Checking files...'}</div>
	</div>
{/block}
