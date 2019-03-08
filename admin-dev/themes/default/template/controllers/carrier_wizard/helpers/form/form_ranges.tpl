{**
 * Copyright (C) 2017-2019 thirty bees
 * Copyright (C) 2007-2016 PrestaShop SA
 *
 * thirty bees is an extension to the PrestaShop software by PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2019 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Open Software License (OSL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
 *}

{* Corresponds to the third input text (max, min and all). *}

<script>
  var zones_nbr = {$zones|count + 3};
  {if ($currency_decimals)}
    var priceDisplayPrecision = {$smarty.const._PS_PRICE_DISPLAY_PRECISION_};
  {else}
    var priceDisplayPrecision = 0;
  {/if}
  var priceDatabasePrecision = {$smarty.const._TB_PRICE_DATABASE_PRECISION_};
</script>
<div id="zone_ranges" style="overflow:auto">
    <h4>{l s='Ranges'}</h4>
    <table id="zones_table" class="table" style="max-width:100%">
        <tbody>
            <tr class="range_inf">
                <td class="range_type"></td>
                <td class="border_left border_bottom range_sign">&gt;=</td>
                {foreach from=$ranges key=r item=range}
                <td class="border_bottom">
                    <div class="input-group fixed-width-md">
                        <span class="input-group-addon weight_unit">{$PS_WEIGHT_UNIT}</span>
                        <span class="input-group-addon price_unit">{$currency_sign}</span>
                        <input type="text"
                            name="range_inf[{$range.id_range|intval}]"
                            class="form-control"
                            value="{displayPriceValue price=$range.delimiter1}"
                            onkeyup="if (isArrowKey(event)) return;
                                     this.value = this.value.replace(/,/g, '.');"
                        />
                    </div>
                </td>
                {foreachelse}
                <td class="border_bottom">
                    <div class="input-group fixed-width-md">
                        <span class="input-group-addon weight_unit">{$PS_WEIGHT_UNIT}</span>
                        <span class="input-group-addon price_unit">{$currency_sign}</span>
                        <input class="form-control" name="range_inf[{$range.id_range|intval}]" type="text" />
                    </div>
                </td>
                {/foreach}
            </tr>
            <tr class="range_sup">
                <td class="range_type"></td>
                <td class="border_left range_sign">&lt;</td>
                {foreach from=$ranges key=r item=range}
                <td class="range_data">
                    <div class="input-group fixed-width-md">
                        <span class="input-group-addon weight_unit">{$PS_WEIGHT_UNIT}</span>
                        <span class="input-group-addon price_unit">{$currency_sign}</span>
                        <input type="text"
                            name="range_sup[{$range.id_range|intval}]"
                            class="form-control"
                            {if isset($form_id) && !$form_id}
                                value=""
                            {else}
                                value="{if isset($change_ranges) && $range.id_range == 0} {else}{displayPriceValue price=$range.delimiter2}{/if}"
                            {/if}
                            onkeyup="if (isArrowKey(event)) return;
                                     this.value = this.value.replace(/,/g, '.');"
                        />
                    </div>
                </td>
                {foreachelse}
                <td class="range_data_new">
                    <div class="input-group fixed-width-md">
                        <span class="input-group-addon weight_unit">{$PS_WEIGHT_UNIT}</span>
                        <span class="input-group-addon price_unit">{$currency_sign}</span>
                        <input class="form-control" name="range_sup[{$range.id_range|intval}]" type="text" autocomplete="off" />
                    </div>
                </td>
                {/foreach}
            </tr>
            <tr class="fees_all">
                <td class="border_top border_bottom border_bold">
                    <span class="fees_all">All</span>
                </td>
                <td style="">
                    <input type="checkbox" onclick="checkAllZones(this);" class="form-control">
                </td>
                {foreach from=$ranges key=r item=range}
                <td class="border_top border_bottom {if $range.id_range != 0} validated {/if}"  >
                    <div class="input-group fixed-width-md">
                        <span class="input-group-addon currency_sign">{$currency_sign}</span>
                        <input class="form-control" type="text" {if isset($form_id) && !$form_id} disabled="disabled"{/if} autocomplete="off" />
                    </div>
                </td>
                {foreachelse}
                <td class="border_top border_bottom">
                    <div class="input-group fixed-width-md">
                        <span class="input-group-addon currency_sign">{$currency_sign}</span>
                        <input class="form-control" type="text" autocomplete="off" />
                    </div>
                </td>
                {/foreach}
            </tr>
            {foreach from=$zones key=i item=zone}
            <tr class="fees" data-zoneid="{$zone.id_zone}">
                <td>
                    <label for="zone_{$zone.id_zone}">{$zone.name}</label>
                </td>
                <td class="zone">
                    <input class="form-control input_zone" id="zone_{$zone.id_zone}" name="zone_{$zone.id_zone}" value="1" type="checkbox" {if isset($fields_value['zones'][$zone.id_zone]) && $fields_value['zones'][$zone.id_zone]} checked="checked"{/if}/>
                </td>
                {foreach from=$ranges key=r item=range}
                <td>
                    <div class="input-group fixed-width-md">
                        <span class="input-group-addon">{$currency_sign}</span>
                        <input type="text"
                            class="form-control"
                            name="fees[{$zone.id_zone|intval}][{$range.id_range|intval}]"
                            {if !isset($fields_value['zones'][$zone.id_zone]) || (isset($fields_value['zones'][$zone.id_zone]) && !$fields_value['zones'][$zone.id_zone])}
                                disabled="disabled"
                            {/if}
                            {if isset($price_by_range[$range.id_range][$zone.id_zone]) && $price_by_range[$range.id_range][$zone.id_zone] && isset($fields_value['zones'][$zone.id_zone]) && $fields_value['zones'][$zone.id_zone]}
                                value="{displayPriceValue price=$price_by_range[$range.id_range][$zone.id_zone]}"
                            {else}
                                value=""
                            {/if}
                            onkeyup="if (isArrowKey(event)) return;
                                     this.value = this.value.replace(/,/g, '.');"
                        />
                    </div>
                </td>
                {/foreach}
            </tr>
            {/foreach}
            <tr class="delete_range">
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                {foreach from=$ranges name=ranges key=r item=range}
                    {if $smarty.foreach.ranges.first}
                        <td>&nbsp;</td>
                    {else}
                        <td>
                            <a href="#" onclick="delete_range();" class="btn btn-default">{l s='Delete'}</a>
                        </td>
                    {/if}
                {/foreach}
            </tr>
        </tbody>
    </table>
</div>
