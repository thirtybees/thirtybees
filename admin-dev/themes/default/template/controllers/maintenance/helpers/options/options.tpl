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

{extends file="helpers/options/options.tpl"}
{block name="input"}
    {if $field['type'] == 'maintenance_ip'}
        <script type="text/javascript">
            var remoteIp = "{$field['remoteIp']}";

            function uniqueAddresses(value) {
                return value
                    .split(/,/)
                    .map(function (str) {
                        return str.trim();
                    })
                    .sort()
                    .filter(function (item, pos, ary) {
                        return !pos || item !== ary[pos - 1];
                    })
                    .join(',');
            }

            function addRemoteAddr() {
                var input = $('input[name=PS_MAINTENANCE_IP]');
                var value = input.attr('value').trim()
                if (value) {
                    input.attr('value', uniqueAddresses(value + ',' + remoteIp));
                } else {
                    input.attr('value', remoteIp);
                }
            }

            function removeRemoteAddr() {
                var input = $('input[name=PS_MAINTENANCE_IP]');
                var value = input.attr('value').trim();
                input.attr('value', uniqueAddresses(value
                    .split(/,/)
                    .filter(function (item) {
                        return item.trim() !== remoteIp;
                    })
                    .join(',')
                ));
            }
        </script>
        <div class="col-lg-9">
            <div class="row">
                <div class="col-lg-8">
                    <input type="text"{if isset($field['id'])} id="{$field['id']}"{/if}
                           size="{if isset($field['size'])}{$field['size']|intval}{else}5{/if}" name="{$key}"
                           value="{$field['value']|escape:'html':'UTF-8'}"/>
                </div>
                <div class="col-lg-4">
                    <button type="button" class="btn btn-default" onclick="addRemoteAddr();">
                        <i class="icon-plus"></i> {l s='Add my IP'}
                    </button>
                    <button type="button" class="btn btn-default" onclick="removeRemoteAddr();">
                        <i class="icon-minus"></i> {l s='Remove my IP'}
                    </button>
                </div>
            </div>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}