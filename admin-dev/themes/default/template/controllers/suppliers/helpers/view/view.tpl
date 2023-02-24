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
<div class="panel">
	<div class="panel-heading">{$supplier->name} - {l s='Number of products:'} {count($products)}</div>	
	<table class="table">
		<thead>
			<tr>
				<th><span class="title_box">{l s='Product name'}</span></th>
				<th><span class="title_box">{l s='Attribute name'}</span></th>
				<th><span class="title_box">{l s='Supplier Reference'}</span></th>
				<th><span class="title_box">{l s='Wholesale price'}</span></th>
				<th><span class="title_box">{l s='Reference'}</span></th>
				<th><span class="title_box">{l s='EAN13'}</span></th>
				<th><span class="title_box">{l s='UPC'}</span></th>
				{if $stock_management && $shopContext != Shop::CONTEXT_ALL}<th class="right"><span class="title_box">{l s='Available Quantity'}</span></th>{/if}
			</tr>
		</thead>
		<tbody>
		{foreach $products AS $product}
			{if !$product.combinations}
				<tr>
					<td><a class="btn btn-link" href="{$product.link|escape:'html'}">{$product.name}</a></td>
					<td></td>
					<td>{$product.product_supplier_reference}</td>
					<td>{$product.product_supplier_price_te}</td>
					<td>{$product.reference}</td>
					<td>{$product.ean13}</td>
					<td>{$product.upc}</td>
					{if $stock_management && $shopContext != Shop::CONTEXT_ALL}<td class="right" width="150">{$product.quantity}</td>{/if}
				</tr>
			{else}
				{foreach $product.combinations AS $id_product_attribute => $product_attribute}
					<tr {if $id_product_attribute %2}class="alt_row"{/if} >
						<td><a class="btn btn-link" href="{$product.link|escape:'html'}">{$product.name}</a></td>
						<td>{$product_attribute.attributes}</td>
						<td>{$product_attribute.product_supplier_reference}</td>
						<td>{$product_attribute.product_supplier_price_te}</td>
						<td>{$product_attribute.reference}</td>
						<td>{$product_attribute.ean13}</td>
						<td>{$product_attribute.upc}</td>
						{if $stock_management && $shopContext != Shop::CONTEXT_ALL}<td class="right">{$product_attribute.quantity}</td>{/if}
					</tr>
				{/foreach}
			{/if}
		{/foreach}
		</tbody>
	</table>
</div>
{/block}

