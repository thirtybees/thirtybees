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

<input type="hidden" name="submitted_tabs[]" value="Pack">
<div class="form-group listOfPack">
	<label class="control-label col-lg-3 product_description">
		{l s='List of products of this pack'}
	</label>
	<div class="col-lg-9">
		<p class="alert alert-warning pack-empty-warning" {if $pack_items|@count != 0}style="display:none"{/if}>{l s='This pack is empty. You must add at least one product item.'}</p>
		<div class="table-responsive">
			<table id="divPackItems" class="table table-condensed table-striped">
				<thead>
					<tr>
						<th>{l s='Image'}</th>
						<th>{l s='Product name'}</th>
						<th>{l s='Reference'}</th>
						<th class="text-center">{l s='Quantity'}</th>
						<th class="text-center">{l s='Delete'}</th>
					</tr>
				</thead>
				<tbody>
					{foreach $pack_items as $pack_item}
						{$packItemUrl = $link->getAdminLink('AdminProducts', true, ['id_product' => $pack_item.id, 'updateproduct' => 1])}
						<tr class="product-pack-item" data-product-name="{$pack_item.name|escape:'htmlall'}" data-product-qty="{$pack_item.pack_quantity}" data-product-id="{$pack_item.id}" data-product-id-attribute="{$pack_item.id_product_attribute}">
							<td class="fixed-width-xs text-center"><img class="img-thumbnail" alt="{$pack_item.name}" src="{$pack_item.image}"/></td>
							<td><a href="{$packItemUrl}" target="_blank">{$pack_item.name}</a></td>
							<td>{$pack_item.reference}</td>
							<td class="text-center">{$pack_item.pack_quantity}</td>
							<td class="text-center"><button type="button" class="btn btn-default delPackItem" data-delete="{$pack_item.id}" data-delete-attr="{$pack_item.id_product_attribute}"><i class="icon-trash"></i></button></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="form-group addProductToPack">
	<label class="control-label col-lg-3" for="curPackItemName">
		<span class="label-tooltip" data-toggle="tooltip" title="{l s='Start by typing the first letters of the product name, then select the product from the drop-down list.'}">
			{l s='Add product in your pack'}
		</span>
	</label>
	<div class="col-lg-9">
		<div class="row">
			<div class="col-lg-6">
				<input type="text" id="curPackItemName" name="curPackItemName" class="form-control">
			</div>
			<div class="col-lg-2">
				<div class="input-group">
					<span class="input-group-addon">&times;</span>
					<input type="number" name="curPackItemQty" id="curPackItemQty" class="form-control" min="1" value="1">
				</div>
			</div>
			<div class="col-lg-2">
				<button type="button" id="add_pack_item" class="btn btn-default">
					<i class="icon-plus-sign-alt"></i> {l s='Add this product'}
				</button>
			</div>
		</div>
	</div>
</div>

<input type="hidden" name="inputPackItems" id="inputPackItems" value="{$input_pack_items}" placeholder="inputs">
<input type="hidden" name="namePackItems" id="namePackItems" value="{$input_namepack_items}" placeholder="name">
