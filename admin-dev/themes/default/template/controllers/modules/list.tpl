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

{$moduleModals=[]}

{if $selectedCategory === AdminModulesController::CATEGORY_PREMIUM && $showBecomeSupporterButton}
	<div class="tb-premium-modules-banner-container">
		<div class="tb-premium-modules-banner-col-left">
			<div class="tb-premium-modules-banner-icon"></div>
			<div class="tb-premium-modules-banner-heading-container">
				<span class="tb-premium-modules-banner-heading-main">{l s="Premium [1]ThirtyBees[/1] Modules" tags=['<b>']}</span>
				<span class="tb-premium-modules-banner-heading-sub">{l s="Enhance your store and productivity with premium ThirtyBees Modules!"}</span>
			</div>
		</div>
		<div class="tb-premium-modules-banner-col-right">
		
			<span>{l s="Gain access to these modules and more with reasonable priced thirtybees membership! Your support helps ThirtyBees grow and thrive!"}</span>
			<div class="button-area">
				{if $connected}
					<a href="{$becomeSupporterUrl|escape:'html'}" class="btn btn-backer" target="_blank">{l s="Become a supporter today!"}</a>
				{else}
					<a href="{$connectLink|escape:'html'}" class="btn btn-backer" {if !$connectLink}disabled="disabled"{/if} target="_blank">{l s="Login to thirty bees"}</a>
				{/if}
			</div>
		</div>
	</div>
{/if}

<table id="module-list" class="table">
	<thead>
		<tr>
			<th colspan="4">
				{include file='controllers/modules/filters.tpl'}
			</th>
		</tr>
	</thead>
	{if count($modules)}
		<tbody>
			{foreach from=$modules item=module}
				{capture name="moduleStatutClass"}{if isset($module->id) && $module->id gt 0 && $module->active == 1}module_active{else}module_inactive{/if}{/capture}
				<tr class="{if $module->premium}premium-module{/if}">
					<td class="{{$smarty.capture.moduleStatutClass}} text-center" style="width: 1%;">
						{if (isset($module->id) && $module->id > 0)}
						<input type="checkbox" name="modules" value="{$module->name|escape:'html':'UTF-8'}" class="noborder" title="{l s='Module %1s '|sprintf:$module->name}"{if empty($module->confirmUninstall)} data-rel="false"{else} data-rel="{$module->confirmUninstall|addslashes}"{/if}/>
						{/if}
					</td>
					<td class="fixed-width-xs">
						<img width="57" alt="{$module->displayName|escape:'html':'UTF-8'}" title="{$module->displayName|escape:'html':'UTF-8'}" src="{if isset($module->image)}{$module->image}{else}{$modules_uri}/{$module->name}/{$module->logo}{/if}" />
					</td>
					<td>
						<div id="anchor{$module->name|ucfirst}" title="{$module->displayName|escape:'html':'UTF-8'}">
							<div class="text-muted">
								{$module->categoryName}
							</div>
							<div class="module_name">
								<span style="display:none">{$module->name|escape:'html':'UTF-8'}</span>
								{$module->displayName|escape:'html':'UTF-8'}
								<small class="text-muted">v{$module->version} - {l s='by'} {$module->author}</small>
								{if isset($module->id) && $module->id > 0}
									{if isset($module->version_addons) && $module->version_addons}
										<span class="label label-warning">{l s='Need update'}</span>
									{/if}
								{/if}
							</div>
							<p class="module_description">
								{if isset($module->description) && $module->description ne ''}
									{$module->description}
								{/if}
							</p>
							{if isset($module->message) && (empty($module->name) !== false)}
								<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">&times;</button>
									{$module->message}
								</div>
							{/if}
						</div>
					</td>
					<td class="actions">
						<div class="btn-group-action">
							<div class="btn-group pull-right">
									{if $module->id}
										{if isset($module->version_addons) && $module->version_addons}
											<a class="btn btn-warning" href="{$module->options.update_url|escape:'html':'UTF-8'}">
												<i class="icon-refresh"></i> {l s='Update it!'}
											</a>
										{elseif !isset($module->not_on_disk)}
											{if $module->optionsHtml|count > 0}
												{assign var=option value=$module->optionsHtml[0]}
												{$option}
											{/if}
										{else}
											<a class="btn btn-danger" {if !empty($module->options.uninstall_onclick)}onclick="{$module->options.uninstall_onclick}"{/if} href="{$module->options.uninstall_url|escape:'html':'UTF-8'}">
												<i class="icon-minus-sign-alt"></i>&nbsp;{l s='Uninstall'}
											</a>
										{/if}
									{else}
										{if $module->canInstall}
											 <a class="btn btn-success" href="{$module->options.install_url|escape:'html':'UTF-8'}">
												 <i class="icon-plus-sign-alt"></i>&nbsp;{l s='Install'}
											 </a>
										{elseif $module->premium}
											<a class="btn btn-success" data-toggle="modal" data-target="#modal-premium-module-{$module->name}">
												<i class="icon-puzzle-piece"></i>&nbsp;{l s='Premium module'}
											</a>
											{$moduleModals[] = $module}
										{/if}
									{/if}

									{if !isset($module->not_on_disk) || $module->premium}
										<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" >
											<span class="caret">&nbsp;</span>
										</button>

										<ul class="dropdown-menu">
											{foreach $module->optionsHtml key=key item=option}
												{if $key != 0}
													{if strpos($option, 'title="divider"') !== false}
														<li class="divider"></li>
													{else}
														<li>{$option}</li>
													{/if}
												{/if}
											{/foreach}
										</ul>
									{else}
										<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" >
											<span class="caret">&nbsp;</span>
										</button>
										<ul class="dropdown-menu">
											{if isset($module->preferences) && isset($module->preferences['favorite']) && $module->preferences['favorite'] == 1}
												<li>
													<a class="action_module action_unfavorite toggle_favorite" data-module="{$module->name}" data-value="0" href="#">
														<i class="icon-star"></i> {l s='Remove from Favorites'}
													</a>
													<a class="action_module action_favorite toggle_favorite" data-module="{$module->name}" data-value="1" href="#" style="display: none;">
														<i class="icon-star"></i> {l s='Mark as Favorite'}
													</a>
												</li>
											{else}
												<li>
													<a class="action_module action_unfavorite toggle_favorite" data-module="{$module->name}" data-value="0" href="#" style="display: none;">
														<i class="icon-star"></i> {l s='Remove from Favorites'}
													</a>
													<a class="action_module action_favorite toggle_favorite" data-module="{$module->name}" data-value="1" href="#">
														<i class="icon-star"></i> {l s='Mark as Favorite'}
													</a>
												</li>
											{/if}
										</ul>
									{/if}
							</div>
						</div>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
	<div class="btn-group pull-left">
		<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
			{l s='bulk actions'}
			 <span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
			<li>
			 	<a href="#" onclick="modules_management('install')">
					<i class="icon-plus-sign-alt"></i>&nbsp;
					{l s='Install the selection'}
				</a>
			</li>
			<li>
				<a href="#" onclick="modules_management('uninstall')">
					<i class="icon-minus-sign-alt"></i>&nbsp;
					{l s='Uninstall the selection'}
				</a>
			</li>
		</ul>
	</div>
	{else}
		<tbody>
			<tr>
				<td colspan="4" class="list-empty">
					<div class="list-empty-msg">
						<i class="icon-warning-sign list-empty-icon"></i> {l s='No modules available in this section.'}
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	{/if}
<script type="text/javascript">
	$(document).ready(function(){
		$('.fancybox-quick-view').fancybox({
			type: 'ajax',
			autoDimensions: false,
			autoSize: false,
			width: 600,
			height: 'auto',
			helpers: {
				overlay: {
					locked: false
				}
			}
		});
	});
</script>

{if $moduleModals}
	{foreach $moduleModals as $module}
		{include 'controllers/modules/modal_premium.tpl' module=$module}
	{/foreach}
{/if}
