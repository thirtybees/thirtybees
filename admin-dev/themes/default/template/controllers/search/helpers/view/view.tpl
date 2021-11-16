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

<script type="text/javascript">
$(function() {
	$('#content .panel').highlight('{$query}');
});
</script>

{if $query}
	<h2>
	{if isset($nb_results) && $nb_results == 0}
		<h2>{l s='There are no results matching your query "%s".' sprintf=$query}</h2>
	{elseif isset($nb_results) && $nb_results == 1}
		{l s='1 result matches your query "%s".' sprintf=$query}
	{elseif isset($nb_results)}
		{l s='%d results match your query "%s".' sprintf=[$nb_results|intval, $query]}
	{/if}
	</h2>
{/if}

{if $query && isset($nb_results) && $nb_results}

	{if isset($features)}
	<div class="panel">
		<h3>
			{if $features|@count == 1}
				{l s='1 feature'}
			{else}
				{l s='%d features' sprintf=$features|@count}
			{/if}
		</h3>
		<table class="table">
			<tbody>
			{foreach $features key=key item=feature}
				{foreach $feature key=k item=val name=feature_list}
					<tr>
						<td><a href="{$val.link}"{if $smarty.foreach.feature_list.first}><strong>{$key}</strong>{/if}</a></td>
						<td><a href="{$val.link}">{$val.value}</a></td>
					</tr>
				{/foreach}
			{/foreach}
			</tbody>
		</table>
	</div>
	{/if}

	{if isset($modules) && $modules}
	<div class="panel">
		<h3>
			{if $modules|@count == 1}
				{l s='1 module'}
			{else}
				{l s='%d modules' sprintf=$modules|@count}
			{/if}
		</h3>
		<table class="table">
			<tbody>
			{foreach $modules key=key item=module}
				<tr>
					<td><a href="{$module->linkto|escape:'html':'UTF-8'}"><strong>{$module->displayName}</strong></a></td>
					<td><a href="{$module->linkto|escape:'html':'UTF-8'}">{$module->description}</a></td>
				</tr>
			{/foreach}
		</tbody>
		</table>
	</div>
	{/if}

	{if isset($categories) && $categories}
	<div class="panel">
		<h3>
			{if $categories|@count == 1}
				{l s='1 category'}
			{else}
				{l s='%d categories' sprintf=$categories|@count}
			{/if}
		</h3>
		<table class="table" style="border-spacing : 0; border-collapse : collapse;">
			{foreach $categories key=key item=category}
				<tr>
					<td>{$category}</td>
				</tr>
			{/foreach}
		</table>
	</div>
	{/if}

	{if isset($products) && isset($productsCount)}
	<div class="panel">
		<h3>
			{if $productsCount == 1}
				{l s='1 product'}
			{else}
				{l s='%d products' sprintf=$productsCount}
			{/if}
		</h3>
		{$products}
	</div>
	{/if}

	{if isset($customers) && isset($customersCount)}
	<div class="panel">
		<h3>
			{if $customersCount == 1}
				{l s='1 customer'}
			{else}
				{l s='%d customers' sprintf=$customersCount}
			{/if}
		</h3>
		{$customers}
	</div>
	{/if}

	{if isset($orders) && isset($ordersCount)}
	<div class="panel">
		<h3>
			{if $ordersCount == 1}
				{l s='1 order'}
			{else}
				{l s='%d orders' sprintf=$ordersCount}
			{/if}
		</h3>
		{$orders}
	</div>
	{/if}
{/if}
<div class="row">
	<div class="col-lg-4">
		<div class="panel">
			<h3>{l s='Search thirty bees forum'}</h3>
			<a href="https://www.google.com/search?q=site%3Ahttps://forum.thirtybees.com+{$query}" class="btn btn-default _blank">{l s='Go to the Forum'}</a>
		</div>
	</div>
</div>




