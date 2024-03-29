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

<div id="product-virtualproduct" class="panel product-tab">
	<input type="hidden" name="submitted_tabs[]" value="VirtualProduct" />
	<input type="hidden" id="virtual_product_filename" name="virtual_product_filename" value="{$productDownload->filename}" />
	<h3>{l s='Virtual Product (services, booking or downloadable products)'}</h3>
	<div id="virtual_good" class="form-group">
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Does this product have an associated file?'}</label>
			<div class="col-lg-2">
				<span class="switch prestashop-switch fixed-width-lg">
					<input type="radio" name="is_virtual_file" id="is_virtual_file_on" value="1" {if $productDownload->id} checked="checked"{/if} />
					<label for="is_virtual_file_on">{l s='Yes'}</label>
					<input type="radio" name="is_virtual_file" id="is_virtual_file_off" value="0" {if !$productDownload->id} checked="checked"{/if} />
					<label for="is_virtual_file_off">{l s='No'}</label>
					<a class="slide-button btn"></a>
				</span>
                {if $productDownload->id}
                    <p class="help-block">{l s='Turning this off also removes related data and the downloadable file.'}</p>
                {/if}
			</div>
		</div>
		<div id="is_virtual_file_product"{if !$productDownload->id} style="display:none;"{/if}>
            <hr>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Is this file active?'}</label>
                <div class="col-lg-2">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="virtual_product_active" id="virtual_product_active_on" value="1" {if $productDownload->active} checked="checked"{/if} />
                        <label for="virtual_product_active_on">{l s='Yes'}</label>
                        <input type="radio" name="virtual_product_active" id="virtual_product_active_off" value="0" {if !$productDownload->active} checked="checked"{/if} />
                        <label for="virtual_product_active_off">{l s='No'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='Deactivating the download makes it inaccessible to customers, but keeps related data and the file on disk.'}</p>
                </div>
            </div>
            {if $productDownload->id}
                <input type="hidden" id="virtual_product_id" name="virtual_product_id" value="{$productDownload->id}">
            {/if}
            <div class="form-group"{if $is_file} style="display:none"{/if}>
                <label id="virtual_product_file_label" for="virtual_product_file" class="control-label col-lg-3">
                    <span class="label-tooltip" data-toggle="tooltip"
                        title="{l s='Upload a file from your computer'} ({Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE')|string_format:'%.2f'} {l s='MB max.'})">
                        {l s='File'}
                    </span>
                </label>
                <div class="col-lg-5">
                    {$virtual_product_file_uploader}
                    <p class="help-block">{l s='Upload a file from your computer'} ({Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE')|string_format:'%.2f'} {l s='MB max.'})</p>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 required">{l s='Filename'}</label>
                <div class="col-lg-5">
                    <input type="text" id="virtual_product_name" name="virtual_product_name" value="{$productDownload->display_filename|escape:'html':'UTF-8'}">
                    <p class="help-block">{l s='The full filename with its extension (e.g. Book.pdf)'}</p>
                </div>
            </div>
            {if $is_file}
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Link to the file:'}</label>
                <div class="col-lg-5">
                    <a href="{$productDownload->getTextLink(true)}" class="btn btn-default"><i class="icon-download"></i> {l s='Download file'}</a>
                    <a href="{$currentIndex|escape:'html':'UTF-8'}&amp;deleteVirtualProduct=true&amp;updateproduct&amp;token={$token|escape:'html':'UTF-8'}&amp;id_product={$product->id}" class="btn btn-default" onclick="return confirm('{l s='Do you really want to delete this file?' js=1}');"><i class="icon-trash"></i> {l s='Delete this file'}</a>
                </div>
            </div>
            {/if}
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Number of allowed downloads'}</label>
                <div class="col-lg-3">
                    <input type="text" id="virtual_product_nb_downloable" name="virtual_product_nb_downloable" value="{$productDownload->nb_downloadable|htmlentities}" class="" size="6">
                    <p class="help-block">{l s='Number of downloads allowed per customer. Set to 0 for unlimited downloads.'}</p>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Expiration date'}</label>
                <div class="col-lg-5">
                    <input class="datepicker" type="text" id="virtual_product_expiration_date" name="virtual_product_expiration_date" value="{$productDownload->date_expiration}" size="11" maxlength="10" autocomplete="off">
                    <p class="help-block">{l s='If set, the file will not be downloadable after this date. Leave blank if you do not wish to attach an expiration date.'}</p>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 required">{l s='Number of days'}</label>
                <div class="col-lg-3">
                    <input type="text" id="virtual_product_nb_days" name="virtual_product_nb_days" value="{if !$productDownload->nb_days_accessible}0{else}{$productDownload->nb_days_accessible|htmlentities}{/if}" class="" size="4">
                    <p class="help-block">{l s='Number of days this file can be accessed by customers. Set to zero for unlimited access.'}</p>
                </div>
            </div>
		</div>
	</div>
	<div class="panel-footer">
		<a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay'}</button>
	</div>
</div>
