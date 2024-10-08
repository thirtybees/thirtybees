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

<{if isset($href) && $href}a style="display:block" href="{$href|escape:'html':'UTF-8'}"{else}div{/if} id="{$id|escape:'html':'UTF-8'}" data-toggle="tooltip" class="box-stats label-tooltip{if isset($color)} {$color|escape}{/if}"{if isset($tooltip)} data-original-title="{$tooltip|escape}"{/if}>
	<div class="kpi-content">
	{if isset($icon) && $icon}
		<i class="{$icon|escape}"></i>
	{/if}
	{if isset($chart) && $chart}
		<div class="boxchart-overlay">
			<div class="boxchart">
			</div>
		</div>
	{/if}
	{if isset($title)}
		<span class="title">{$title|escape}</span>
	{/if}
	{if isset($subtitle)}
		<span class="subtitle">{$subtitle|escape}</span>
	{/if}
	<span class="value">{if isset($value)}{$value|escape|replace:'&amp;':'&'}{/if}</span>
	</div>
	
</{if isset($href) && $href}a{else}div{/if}>

<script>
	window['kpis'] = window['kpis'] || { };
	window['kpis']['{$id|escape:'javascript'}'] = {strip}{
		{if (isset($source) && $source)}source: '{$source|addslashes}',{/if}
		initRefresh: {if (isset($source) && $source) && (isset($refresh) && $refresh)}true{else}false{/if}
	}{/strip};

	function refresh_{$id|replace:'-':'_'|addslashes}() {
		refresh_kpi('{$id|escape:'javascript'}', window['kpis']['{$id|escape:'javascript'}']);
	}
</script>