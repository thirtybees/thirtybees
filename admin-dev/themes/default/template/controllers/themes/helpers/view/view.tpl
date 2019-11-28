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
<div class="alert alert-success">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {l s='The "%1$s" theme has been successfully installed.'|sprintf:$theme_name}
</div>

{hook h='displayAfterThemeInstallation' theme_name=$theme_name}

{if $doc|count > 0}
    <ul>
        {foreach $doc as $key => $item}
        <li><i><a class="_blank" href="{$item}">{$key}</a></i>
        {/foreach}
    </ul>
{/if}

{if $modules_errors|count > 0}
    <div class="alert alert-warning">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {l s='The following module(s) were not installed properly:'}
        <ul>
            {foreach $modules_errors as $module_errors}
                <li>
                   <b>{$module_errors['module_name']}</b> : {foreach $module_errors['errors'] as $error}<br>  {$error|escape:'html':'UTF-8'}{/foreach}
                </li>
            {/foreach}
        </ul>
    </div>
{/if}

{if isset($img_error['ok'])}
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {l s='These image types have been added or updated:'}
        <ul>
            {foreach $img_error['ok'] as $error}
                <li>
                    <strong>{$error['name']}</strong> {l s='(width: %1$spx, height: %2$spx).'|sprintf:$error['width']:$error['height']}
                </li>
            {/foreach}
        </ul>

    </div>
    <div class="alert alert-warning">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {l s='Warning: You may have to regenerate images to fit with this new theme.'}
        <a href="{$image_link}">
            <button class="btn btn-default">{l s='Go to the thumbnails regeneration page'}</button>
        </a>
    </div>
{/if}

{if $installWarnings.ignoredModules || $installWarnings.ignoredHooks || $installWarnings.unmanagedModules}
    <div class="alert alert-warning">
        <button type="button" class="close" data-dismiss="alert">&times;</button>

        <div>
            {l s='Some warnings were encountered during theme installation.'}
            <button class='btn btn-default' onclick="$('#theme-warnings').show()">{l s='Show details'}</button>
        </div>

        <div id="theme-warnings" style="display:none; padding-top:20px">
            {if $installWarnings.ignoredModules|count > 0}
                <h4>{l s='Following module actions were ignored during theme installation'}</h4>
                <p>
                    {l s='Theme wants to enable or disable modules that are [1]not theme related[/1]. Such action can have unexpected side effects on stability of your system' tags=['<strong>']}
                </p>
                <ul>
                    {foreach $installWarnings.ignoredModules as $entry}
                        <li>
                            {l s='Theme wants to [1]%s[/1] module [2]%s[/2]. This action was blocked' tags=['<strong>', '<strong>'] sprintf=[$entry.action, $entry.module] }
                        </li>
                    {/foreach}
                </ul>
                <br />
            {/if}

            {if $installWarnings.ignoredHooks|count > 0}
                <h4>{l s='Following non-theme related hooks were ignored'}</h4>
                <p>
                    {l s='Theme instructs thirty bees core to register hooks that are [1]not theme related[/1]. These requests were ignored' tags=['<strong>']}
                </p>
                <ul>
                    {foreach $installWarnings.ignoredHooks as $entry}
                        <li>
                            {l s='Hook [1]%s[/1] from module [2]%s[/2]' tags=['<strong>', '<strong>'] sprintf=[$entry.hook, $entry.module] }
                        </li>
                    {/foreach}
                </ul>
                <br />
            {/if}

            {if $installWarnings.unmanagedModules|count > 0}
                <h4>{l s='No hooks defined for following modules'}</h4>
                <p>
                    {l s='Theme installed or enabled following modules but didn\'t provide hook list for them. Theme should always provide hook list in order to achieve consistent results. If no hooks are specified in config.xml file, module hook list will remain unchanged. If this is wanted behaviour, theme developer should make it explicit by adding [1]manageHooks="false"[/1] into module entry' tags=['<strong>']}
                </p>
                <ul>
                    {foreach $installWarnings.unmanagedModules as $module}
                        <li>
                            {l s='No [1]hooks[/1] are defined for module [2]%s[/2] in theme config.xml file' tags=['<strong>', '<strong>'] sprintf=[$module] }
                        </li>
                    {/foreach}
                </ul>
                <br />
            {/if}
            <p>
                {l s='Please contact theme developer and request correction of theme config.xml file'}
            </p>
        </div>
    </div>
{/if}



<a href="{$back_link}">
    <button class="btn btn-default">{l s='Finish'}</button>
</a>

