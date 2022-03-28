{*
* 2017-2021 thirty bees
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
*  @copyright  2017-2021 thirty bees
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if isset($product->id)}
  <div id="product-features" class="panel product-tab">
    <input type="hidden" name="submitted_tabs[]" value="Features"/>
    <h3>{l s='Assign features to this product'}</h3>

    <div class="alert alert-info">
      {l s='You can specify a value for each relevant feature regarding this product. Empty fields will not be displayed.'}
      <br/>
      {l s='You can either create a specific value, or select among the existing pre-defined values you\'ve previously added.'}
    </div>

    <table class="table">
      <thead>
      <tr>
        <th><span class="title_box">{l s='Feature'}</span></th>
        <th><span class="title_box">{l s='Pre-defined value'}</span></th>
        <th><span class="title_box">{l s='Displayable'}</span></th>
        <th><span class="title_box"><u>{l s='or'}</u> {l s='Customized value'}</span></th>
      </tr>
      </thead>

      <tbody>
      {foreach from=$available_features item=available_feature}
        <tr>
          {* feature name *}
          <td>{$available_feature.name}</td>

          {* predefined values *}
          <td>
            {if sizeof($available_feature.featureValues)}
              <select id="feature_{$available_feature.id_feature}_value"
                      class="chosen"
                      name="feature_{$available_feature.id_feature}_value[]"
                      onchange="$('.custom_{$available_feature.id_feature}_').val('');"
                      {if $available_feature.allows_multiple_values}
                        size="{min(3, count($available_feature.featureValues))}"
                        multiple
                      {/if}
              >
                {if !$available_feature.allows_multiple_values}
                  <option value="0">---</option>
                {/if}
                {foreach from=$available_feature.featureValues item=value}
                  {if !$value.custom}
                    <option value="{$value.id_feature_value}"
                            {if in_array($value.id_feature_value, $available_feature.selected)}selected="selected"{/if} >
                      {$value.value_full|truncate:80}
                    </option>
                  {/if}
                {/foreach}
              </select>
            {else}
              <input type="hidden" name="feature_{$available_feature.id_feature}_value" value="0"/>
              <span>
                {l s='N/A'}
                -
                <a href="{$link->getAdminLink('AdminFeatures')|escape:'html':'UTF-8'}&amp;addfeature_value&amp;id_feature={$available_feature.id_feature}"
                   class="confirm_leave btn btn-link">
                  <i class="icon-plus-sign"></i> {l s='Add pre-defined values first'} <i class="icon-external-link-sign"></i>
                </a>
					    </span>
            {/if}
          </td>

          {* displayable values *}
          <td class="displayable_values" {if $available_feature.allows_multiple_values}style="vertical-align: top;"{/if}>

            {foreach from=$available_feature.featureValues item=value}
              <div id="displayable_{$value.id_feature_value}" class="displayable-field" title="{l s='Displayable for'} {$value.value}">

                {if array_key_exists($value.id_feature_value, $available_feature['displayable_values'])}
                  {include file="../../helpers/form/form_input.tpl"
                  input=['type' => 'text', 'name' => "displayable_{$value.id_feature_value}", 'lang' => true, 'class' => 'displayable-input']
                  fields_value=["displayable_{$value.id_feature_value}" => $available_feature['displayable_values'][$value.id_feature_value]]
                  }
                {/if}

              </div>
            {/foreach}

          </td>

          {* custom values *}
          <td>
            {if $available_feature.allows_custom_values}
              <div class="custom_group" id="custom_group_{$available_feature.id_feature}">
                <input type="hidden" id="custom_values_count_{$available_feature.id_feature}" name="custom_values_count_{$available_feature.id_feature}" value="{count($available_feature['custom_values'])}" />
                {foreach from=$available_feature.custom_values key=customValueIndex item=customValue}
                  <div class="custom_group_value" id="custom_group_{$available_feature.id_feature}_value_{$customValueIndex}">
                    <div class="row lang-0" style='display: none;'>
                      <div class="col-lg-9">
                        <textarea class="textarea-autosize"
                                  name="custom_{$available_feature.id_feature}_{$customValueIndex}_ALL"
                                  cols="60" style='background-color:#CCF'
                                  rows="1"
                                  onkeyup="updateAll($(this))"
                        >{$customValue[1]|escape:'html':'UTF-8'|default:""}</textarea>
                      </div>

                      <div class="col-lg-3">
                        {if $languages|count > 1}
                          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                            {l s='ALL'}
                            <span class="caret"></span>
                          </button>
                          <ul class="dropdown-menu">
                            {foreach from=$languages item=language}
                              <li>
                                <a href="javascript:void(0);"
                                   onclick="restore_lng($(this),{$language.id_lang});">{$language.iso_code}</a>
                              </li>
                            {/foreach}
                          </ul>
                        {/if}
                        <a href="javascript:void(0);" onclick="deleteCustomValue($(this));">{l s='Delete'}</a>
                      </div>
                    </div>

                    {foreach from=$languages key=k item=language}

                      <div class="row translatable-field lang-{$language.id_lang}">
                        <div class="col-lg-9">
                          <textarea
                                  class="textarea-autosize"
                                  name="custom_{$available_feature.id_feature}_{$customValueIndex}_{$language.id_lang}"
                                  cols="60"
                                  rows="1"
                                  onkeyup="if (isArrowKey(event)) return ;$('#feature_{$available_feature.id_feature}_value').val(0);"
                          >{$customValue[$language.id_lang]|escape:'html':'UTF-8'|default:""}</textarea>
                        </div>

                        <div class="col-lg-3">
                          {if $languages|count > 1}
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                              {$language.iso_code}
                              <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                              <li>
                                <a href="javascript:void(0);" onclick="all_languages($(this));">{l s='ALL'}</a>
                              </li>
                              {foreach from=$languages item=language}
                                <li>
                                  <a href="javascript:hideOtherLanguage({$language.id_lang});">{$language.iso_code}</a>
                                </li>
                              {/foreach}
                            </ul>
                          {/if}
                          <a href="javascript:void(0);" onclick="deleteCustomValue($(this));">{l s='Delete'}</a>
                        </div>
                      </div>
                    {/foreach}
                  </div>
                {/foreach}
              </div>
              {if $available_feature.allows_multiple_values}
                <a href="javascript:void(0);" onclick="addCustomValue({$available_feature.id_feature})">{l s='Add another value'}</a>
              {/if}
            {else}
              <div>
                {l s='Custom values are not allowed for this feature'}
              </div>
            {/if}
          </td>
        </tr>
        {foreachelse}
        <tr>
          <td colspan="3" style="text-align:center;"><i class="icon-warning-sign"></i> {l s='No features have been defined'}</td>
        </tr>
      {/foreach}
      </tbody>
    </table>

    <a href="{$link->getAdminLink('AdminFeatures')|escape:'html':'UTF-8'}&amp;addfeature"
       class="btn btn-link confirm_leave button">
      <i class="icon-plus-sign"></i> {l s='Add a new feature'} <i class="icon-external-link-sign"></i>
    </a>
    <div class="panel-footer">
      <a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}"
         class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
      <button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i
                class="process-icon-loading"></i> {l s='Save'}</button>
      <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i
                class="process-icon-loading"></i> {l s='Save and stay'}</button>
    </div>
  </div>
  <script type="text/javascript">
    if (tabs_manager.allow_hide_other_languages)
      hideOtherLanguage({$defaultFormLanguage});
    {literal}
    $(".textarea-autosize").autosize();

    function all_languages(pos) {
      {/literal}
      {if isset($languages) && is_array($languages)}
      {foreach from=$languages key=k item=language}
      pos.parents('td').find('.lang-{$language.id_lang}').addClass('nolang-{$language.id_lang}').removeClass('lang-{$language.id_lang}');
      {/foreach}
      {/if}
      pos.parents('td').find('.translatable-field').hide();
      pos.parents('td').find('.lang-0').show();
      {literal}
    }

    function restore_lng(pos, i) {
      {/literal}
      {if isset($languages) && is_array($languages)}
      {foreach from=$languages key=k item=language}
      pos.parents('td').find('.nolang-{$language.id_lang}').addClass('lang-{$language.id_lang}').removeClass('nolang-{$language.id_lang}');
      {/foreach}
      {/if}
      {literal}
      pos.parents('td').find('.lang-0').hide();
      hideOtherLanguage(i);
    }

    function addCustomValue(featureId) {
      var group = $('#custom_group_' + featureId);
      var values = group.find('.custom_group_value');
      if (values.length > 0) {
        var custom = $(values[0]).clone();
        var cntInput = $('#custom_values_count_' + featureId);
        var newIndex = parseInt(cntInput.val(), 10);
        cntInput.val(newIndex + 1);
        custom.attr('id', 'custom_group_' + featureId + '_value_' + newIndex);
        custom.find('textarea').each(function (index, elem) {
          var $elem = $(elem);
          var name = $elem.attr('name');
          $elem.attr('name', "custom_" + featureId + "_" + newIndex + "_" + name.replace(/^.*_/, ""))
          $elem.attr('value', '');
        });
        group.append(custom);
      }
    }

    function deleteCustomValue($element) {
      var group = $element.closest('.custom_group');
      var cnt = group.find('.custom_group_value').length;
      if (cnt > 1) {
        $element.closest('.custom_group_value').remove();
      } else {
        $element.closest('.custom_group_value').find('textarea').attr('value', '');
      }
    }

    function updateAll($element) {
      $element.closest('.custom_group_value').find('textarea').val($element.val());
    }

    // Make sure, that the chosen select has not a width of 0px
    $(document).ready(function() {
      $('select.chosen').chosen( { width: '100%' } );
    });


    $("#product-features select").on("change", function(element, id_feature_value) {
      addDisplayableField(id_feature_value.selected);
      renderAllDisplayableFields(element.target);
    })

    function renderAllDisplayableFields(select) {

      // Get the selected values
      var feature_value_selected = [];
      var selected_options = Array.from(select.selectedOptions);

      selected_options.forEach(function (option) {
        feature_value_selected.push('displayable_'+option.value);
      })

      // Hide all displayable elements, which aren't selected
      var displayable_elements = select.closest('tr').querySelectorAll('.displayable-field');

      if (displayable_elements.length) {
        displayable_elements.forEach((element) => {
          if (!feature_value_selected.includes(element.id)) {
            element.style.display = 'none';
          }
        });
      }

    }

    function addDisplayableField(id_feature_value) {

      if (!id_feature_value || id_feature_value==='0') {
        return false;
      }

      var displayable_div = document.getElementById('displayable_'+id_feature_value);

      // Check if the parent div is empty (the field may already be generated before)
      if (displayable_div.innerHTML.trim().length===0) {

        var displayable_field_html = `{/literal}{include file="../../helpers/form/form_input.tpl" input=['type' => 'text', 'name' => "displayable_fake", 'lang' => true, 'class' => 'displayable-input']}{literal}`;

        // Replace the fake name with the correct one
        displayable_field_html = displayable_field_html.replaceAll('displayable_fake', 'displayable_'+id_feature_value);

        // Move the input into the parent div
        displayable_div.insertAdjacentHTML('beforeend', displayable_field_html);
      }


      // Move the parent div to the end of the list
      var displayable_values_div = displayable_div.closest('.displayable_values');
      displayable_values_div.appendChild(displayable_div);

      // Show the displayable div
      displayable_div.style.display = 'block';

    }

  </script>

  <style>
    /* Make sure that selected options of a chosen field are always displayed on a new line */
    #product-features li.search-choice,
    #product-features input.search-choice {
      display: inline-block;
      width: calc(100% - 10px);
      font-size: 12px;
      padding: 7px 8px;
    }

    #product-features .displayable-field .form-group {
      margin: 0;
    }

  </style>

{/literal}

{/if}
