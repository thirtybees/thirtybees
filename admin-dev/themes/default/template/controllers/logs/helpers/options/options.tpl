{**
 * Copyright (C) 2019 thirty bees
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
 * @copyright 2019 thirty bees
 * @license   Open Software License (OSL 3.0)
 *}

{extends file="helpers/options/options.tpl"}
{block name="input"}
    {if $field['type'] == 'iframe'}
        <div class="row">
            <iframe id='iframe' style="border: none; width:100%; height: 300px; overflow-y: hidden" srcdoc="{$field['srcdoc']|escape:'html':'UTF-8'}"></iframe>
        </div>
        <script>
            $('iframe').load(function() {
                var iframe = this;
                iframe.style.height = (iframe.contentWindow.document.body.offsetHeight + 50) + 'px';
                setInterval(function() {
                    iframe.style.height = (iframe.contentWindow.document.body.offsetHeight + 50) + 'px';
                }, 50);
            })
        </script>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
