{**
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
 * @author PrestaShop SA <contact@prestashop.com>
 * @copyright  2007-2016 PrestaShop SA
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}

{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
  {if $mod_security_warning}
    <div class="alert alert-warning">
      {l s='Apache mod_security is activated on your server. This could result in some Bad Request errors'}
    </div>
  {/if}
  <div class="alert alert-info">
    <p>
      {l s='Click on the title of a section to open its fieldsets.'}
    </p>
  </div>
  <div class="panel">
    <p>{l s='Expressions to translate:'} <span class="badge">{l s='%d' sprintf=$count}</span></p>
    <p>{l s='Total missing expressions:'} <span class="badge">{l s='%d' sprintf=array_sum($missing_translations)}</p>
  </div>

  <div class="panel">
    <script type="text/javascript">
      $(document).ready(function(){
        $('a.useSpecialSyntax').click(function(){
          var syntax = $(this).find('img').attr('alt');
          $('#BoxUseSpecialSyntax .syntax span').html(syntax+".");
        });

        $('a.slidetoggle').click(function(){
          $('#'+$(this).attr('data-slidetoggle')).slideToggle();
          return false;
        });
      });
    </script>

    <div id="BoxUseSpecialSyntax">
      <div class="alert alert-warning">
        <p>
          {l s='Some of these expressions use this special syntax: %s.' sprintf='%d'}
          <br />
          {l s='You MUST use this syntax in your translations. Here are several examples:'}
        </p>
        <ul>
          <li>"{l s='There are [1]%d[/1] products' tags=['<strong>']}": {l s='"%s" will be replaced by a number.' sprintf='%d'}</li>
          <li>"{l s='List of pages in [1]%s[/1]' tags=['<strong>']}": {l s='"%s" will be replaced by a string.' sprintf='%s'}</li>
          <li>"{l s='Feature: [1]%1$s[/1] ([1]%2$d[/1] values)' tags=['<strong>']}": {l s='The numbers enable you to reorder the variables when necessary.'}</li>
        </ul>
      </div>
    </div>
    <div class="panel-footer">
      <a name="submitTranslations{$type|ucfirst}" href="{$cancel_url|escape:'html':'UTF-8'}" class="btn btn-default">
        <i class="process-icon-cancel"></i>
        {l s='Cancel'}
      </a>
      {$toggle_button}
    </div>
  </div>
  {foreach $tabsArray as $k => $newLang}
    {if !empty($newLang)}
      <form method="post" id="{$k}_form" action="{$url_submit|escape:'html':'UTF-8'}" class="form-horizontal">
        <div class="panel">
          <input type="hidden" name="lang" value="{$lang}" />
          <input type="hidden" name="type" value="{$type}" />
          <input type="hidden" name="theme" value="{$theme}" />
          <h3>
            <a href="#" class="slidetoggle" data-slidetoggle="{$k}-tpl">
              <i class="icon-caret-down"></i>
              {$k}
            </a>
            - {$newLang|count} {l s='expressions'}
            {if isset($missing_translations[$k])} <span class="label label-danger">{$missing_translations[$k]} {l s='missing'}</span>{/if}
          </h3>
          <div name="{$type}_div" id="{$k}-tpl" style="display:{if isset($missing_translations[$k])}block{else}none{/if}">
            <table class="table">
              {counter start=0 assign=irow}
              {foreach $newLang as $key => $value}{counter}
                <tr>
                  <td width="40%">{$key|stripslashes}</td>
                  <td width="2%">=</td>
                  <td width="40%">
                    {if $key|strlen < $textarea_sized}
                      <input type="text"
                        style="width: 450px{if empty($value.trad)};background:#FBB{/if}"
                        name="{if in_array($type, array('front', 'fields'))}{$k}_{$key|md5}{else}{$k}{$key|md5}{/if}"
                        value="{$value.trad|regex_replace:'/"/':'&quot;'|stripslashes}"' />
                    {else}
                      <textarea rows="{($key|strlen / $textarea_sized)|intval}"
                        style="width: 450px{if empty($value.trad)};background:#FBB{/if}"
                        name="{if in_array($type, array('front', 'fields'))}{$k}_{$key|md5}{else}{$k}{$key|md5}{/if}"
                      >{$value.trad|regex_replace:'/"/':'&quot;'|stripslashes}</textarea>
                    {/if}
                  </td>
                  <td width="18%">
                    {if isset($value.use_sprintf) && $value.use_sprintf}
                      <a class="useSpecialSyntax" title="{l s='This expression uses a special syntax:'} {$value.use_sprintf}">
                        <img src="{$smarty.const._PS_IMG_}admin/error.png" alt="{$value.use_sprintf}" />
                      </a>
                    {/if}
                  </td>
                </tr>
              {/foreach}
            </table>
            <div class="panel-footer">
              <a name="submitTranslations{$type|ucfirst}" href="{$cancel_url|escape:'html':'UTF-8'}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
              <button type="submit" id="{$table}_form_submit_btn" name="submitTranslations{$type|ucfirst}" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save'}</button>
              <button type="submit" id="{$table}_form_submit_btn" name="submitTranslations{$type|ucfirst}AndStay" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save and stay'}</button>
            </div>
          </div>
        </div>
      </form>
    {/if}
  {/foreach}
{/block}
