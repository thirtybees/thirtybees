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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
*}
{if isset($content)}
  {$content}
{/if}

<form class="form-horizontal" action="{$current|escape:'html':'UTF-8'}&amp;token={$token|escape:'html':'UTF-8'}" method="post">
        <div class="panel">
            <h3>
                <i class="icon-trash"></i>
                {l s='Check & Clean'}
            </h3>
            <div class="form-group">
                <div class="control-label col-lg-3">
                    {l s='Check and clean orphaned product images'}
                </div>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="cleanImages" id="cleanImages_on" value="1">
                        <label for="cleanImages_on" class="radioCheck">
                            {l s='Yes'}
                        </label>
                        <input type="radio" name="cleanImages" id="cleanImages_off" value="0" checked="checked">
                        <label for="cleanImages_off" class="radioCheck">
                            {l s='No'}
                        </label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">
                        {l s='This option will scan for and remove all orphaned product images, as well as delete any obsolete temporary product images if found. Please note that this process may take some time. The /img/p folder should contain only valid image files. If any additional files are detected, you will be notified so you can review and remove them if deemed suspicious.'}
                    </p>
                </div>
            </div>
            <div class="panel-footer">
                <button type="submit" name="submitCleanImages{$table}" class="btn btn-default pull-right">
                    <i class="process-icon-cogs"></i> {l s='Clean up!'}
                </button>
            </div>
        </div>
    </form>
    
{if isset($display_regenerate) && $display_regenerate}
  <form class="form-horizontal" action="{$current|escape:'html':'UTF-8'}&amp;token={$token|escape:'html':'UTF-8'}" method="post">
    <div class="panel">
      <h3>
        <i class="icon-picture"></i>
        {l s='Regenerate thumbnails'}
      </h3>

      <div class="alert alert-info">
        {l s='Regenerates thumbnails for all existing images'}<br />
        {l s='Please be patient. This can take several minutes.'}<br />
        {l s='Be careful! Manually uploaded thumbnails will be erased and replaced by automatically generated thumbnails.'}
      </div>

      {foreach $imageEntities as $imageEntity}
        <div class="form-group second-select format_{$imageEntity.name|escape:'html':'UTF-8'}" style="display:none;">
          <label class="control-label col-lg-3">{l s='Select a format'}</label>
          <div class="col-lg-9 margin-form">
            <select name="format_{$imageEntity.name|escape:'html':'UTF-8'}">
              <option value="all">{l s='All'}</option>
              {foreach $imageEntity.imageTypes AS $imageType}
                <option value="{$imageType['id_image_type']|intval}">{$imageType['name']|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
          </div>
        </div>
      {/foreach}
      <script>
        function changeFormat(elt)
        {ldelim}
          $('.second-select').hide();
          $('.format_' + $(elt).val()).show();
        {rdelim}
      </script>

      <div class="form-group">
        <label class="control-label col-lg-3">
          {l s='Regenerate thumbnails'}
        </label>
        <table class="col-lg-9">
          {foreach $image_indexation as $entityType => $status}
            {$total = intval($status['total'])}
            {$indexed = $total - intval($status['pending'])}
            {$failed = intval($status['failed'])}
          <tr>
            <td>
              <button class="btn btn-info ajax-regenerate-button"
                      id="regenerate{$entityType|ucfirst|escape:'htmlall':'UTF-8'}Images"
                      data-entity-type="{$entityType|escape:'htmlall':'UTF-8'}"
                      {if $total === 0}disabled="disabled"{/if}
                      title="{$entityType|escape:'htmlall':'UTF-8'}"
              >
                <i class="icon icon-play"></i> {l s='Regenerate %s' sprintf=[$status.display_name]}
              </button>
            </td>
            <td width="99%" style="padding-left: 20px; padding-top: 15px">
              <div class="progress{if $total === 0} disabled{/if}">
                <div id="progress-bar-{$entityType|escape:'htmlall':'UTF-8'}"
                     class="progress-bar"
                     role="progressbar"
                     style="width: {if $total === 0}0{else}{intval(($indexed / $total) * 100.0)}{/if}%; text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000">
                  <span style="position: absolute; padding-left: 5px; padding-right: 5px">
                    <span id="regen-indexed-{$entityType|escape:'html':'UTF-8'}">{$indexed}</span>
                    /
                    <span id="regen-total-{$entityType|escape:'html':'UTF-8'}">{$total}</span>
                    <span id="regen-failed-{$entityType|escape:'html':'UTF-8'}-wrap" {if !$failed}style="display:none"{/if}>
                      &nbsp;
                      {l s='([1]%s[/1] failed)' sprintf=[$failed] tags=['<span id="regen-failed-'|cat:$entityType|cat:'">']}
                    </span>
                  </span>
                </div>
              </div>
            </td>
          </tr>
          {/foreach}
        </table>
      </div>

      <div class="panel-footer">
        <button type="button" id="regenerateAllImages" name="regenerateAllImages" class="btn btn-default pull-right">
          <i class="process-icon-cogs"></i> <span>{l s='Regenerate all thumbnails'}</span>
        </button>
      </div>
    </div>
  </form>
  <script type="text/javascript">

    (function () {

      var regenerating = { };

      {foreach from=$imageEntities item=$imageEntity}
        regenerating.{$imageEntity.name} = false;
      {/foreach}

      window.regen = regenerating;

      var pendingRequests = [];
      function removeRequest(request) {
        const index = pendingRequests.indexOf(request);
        pendingRequests.splice(index, 1);
      }

      function getMax() {
        var max = 0;
        $('.ajax-regenerate-button').each(function (index, elem) {
          if (!elem.hasAttribute('disabled')) {
            max++;
          }
        });

        return max;
      }

      function getRegenerating() {
        var sum = 0;
        $.each(regenerating, function (entityType, value) {
          if (value) {
            sum++;
          }
        });

        return sum;
      }

      function initRegenerationButtons() {
        if (typeof $ === 'undefined') {
          setTimeout(initRegenerationButtons, 100);

          return;
        }

        function startGenerating(entityType) {
          var $button = $('button[data-entity-type="' + entityType + '"]');
          if ($button[0].hasAttribute('disabled')) {
            return;
          }

          $button
            .find('i')
            .removeClass('icon-play')
            .addClass('icon-pause');
          regenerating[entityType] = true;

          doAjaxRequest(entityType);
          checkRegenerationButton();
        }

        function pauseGenerating(entityType) {
          $('button[data-entity-type="' + entityType + '"]')
            .find('i')
            .addClass('icon-play')
            .removeClass('icon-pause');
          regenerating[entityType] = false;
          checkRegenerationButton();
        }

        function enableButtons() {
          $('#regenerateAllImages').removeAttr('disabled');
          $('#deleteOldImages').removeAttr('disabled');
          $('#resetImageStats').removeAttr('disabled');
        }

        function disableButtons() {
          $('#regenerateAllImages').attr('disabled', 'disabled');
          $('#deleteOldImages').attr('disabled', 'disabled');
          $('#resetImageStats').attr('disabled', 'disabled');
        }

        function spinDeleteButton() {
          $('#deleteOldImages')
            .find('i')
            .removeClass('process-icon-delete')
            .addClass('process-icon-refresh')
            .addClass('icon-spin');
          $('#resetImageStats')
            .find('i')
            .addClass('icon-spin');
        }

        function unspinDeleteButton() {
          $('#deleteOldImages')
            .find('i')
            .removeClass('process-icon-refresh')
            .removeClass('icon-spin')
            .addClass('process-icon-delete');
          $('#resetImageStats')
            .find('i')
            .removeClass('icon-spin')
        }

        function checkRegenerationButton() {
          var $button = $('#regenerateAllImages');
          if (getRegenerating() >= getMax()) {
            $button.find('i').removeClass('process-icon-cogs').addClass('process-icon-').text(String.fromCharCode(0xf04c));
            $button.find('span').text('{l s='Pause all' js=1}');
          } else {
            $button.find('i').removeClass('process-icon-').addClass('process-icon-cogs').text('');
            $button.find('span').text('{l s='Regenerate all thumbnails' js=1}');
          }
        }

        function updateProgress(indexStatus) {
            $.each(indexStatus, function (entityType) {
              const total = indexStatus[entityType].total;
              const pending = indexStatus[entityType].pending;
              const failed = indexStatus[entityType].failed;
              const indexed = total - pending;
              const progressPerc = total > 0 ? ((indexed / total) * 100) : 0;

              $('#regen-indexed-' + entityType).text(indexed);
              $('#regen-total-' + entityType).text(total);
              $('#regen-failed-' + entityType).text(failed);
              $('#progress-bar-' + entityType).css('width', progressPerc + '%');
              if (failed > 0) {
                $('#regen-failed-' + entityType+'-wrap').show();
              } else {
                $('#regen-failed-' + entityType+'-wrap').hide();
              }
            });
        }

        function handleResponseError(jqXhr) {
          let msg = '{l s='Server retured error code [statusCode]' js=1}'.replace('[statusCode]', jqXhr.status);
          const response = jqXhr.responseJSON;
          if (response && response.status === 'error' && response.message) {
            msg += ': ' + response.message;
          }
          showErrorMessage(msg);
        }

        function deleteOldImages() {
          $.each(pendingRequests, function (index, request) {
            if (request != null && typeof request.abort === 'function') {
              request.abort();
            }
          });
          pendingRequests = [];

          disableButtons();
          spinDeleteButton();
          $.each(regenerating, function (entityType) {
            pauseGenerating(entityType);
          });
          var req = $.ajax({
            url: currentIndex + '&token=' + token + '&ajax=1&action=DeleteOldImages',
            method: 'post',
            dataType: 'json',
            success: function (response) {
              if (response == null) {
                return;
              }

              if (response.indexStatus) {
                updateProgress(response.indexStatus);
              }
            },
            error: handleResponseError,
            complete: function () {
              unspinDeleteButton();
              enableButtons();

              removeRequest(req);
            }
          });
          pendingRequests.push(req);
        }

        function resetImageStats() {
          $.each(pendingRequests, function (index, request) {
            if (request != null && typeof request.abort === 'function') {
              request.abort();
            }
          });
          pendingRequests = [];

          disableButtons();
          spinDeleteButton();
          $.each(regenerating, function (entityType) {
            pauseGenerating(entityType);
          });
          var req = $.ajax({
            url: currentIndex + '&token=' + token + '&ajax=1&action=ResetImageStats',
            method: 'post',
            dataType: 'json',
            success: function (response) {
              if (response == null) {
                return;
              }

              if (response.indexStatus) {
                updateProgress(response.indexStatus);
              }
            },
            error: handleResponseError,
            complete: function () {
              unspinDeleteButton();
              enableButtons();

              removeRequest(req);
            }
          });
          pendingRequests.push(req);
        }

        function doAjaxRequest(entityType) {
          var req = $.ajax({
            url: currentIndex + '&token=' + token + '&ajax=1&action=RegenerateThumbnails',
            method: 'post',
            dataType: 'json',
            data: JSON.stringify({
              entity_type: entityType,
            }),
            success: function (response) {
              if (response == null || !regenerating[entityType]) {
                return;
              }

              if (response.indexStatus) {
                updateProgress(response.indexStatus);

                if (response.indexStatus[entityType].pending === 0) {
                  showSuccessMessage('{l s='The thumbnails for this type have been successfully generated' js=1}');
                  pauseGenerating(entityType);
                }
              }

              if (response.hasError) {
                $.each(response.errors, function (index, error) {
                  if (error) {
                    showErrorMessage(error);
                  }
                });
              }
            },
            error: handleResponseError,
            complete: function () {
              if (regenerating[entityType]) {
                doAjaxRequest(entityType);
              }

              removeRequest(req);
            }
          });
          pendingRequests.push(req);
        }

        function toggleGeneratingAll(event) {
          event.preventDefault();

          if (getRegenerating() >= getMax()) {
            $.each(regenerating, function (entityType) {
              pauseGenerating(entityType);
            });
          } else {
            $.each(regenerating, function (entityType) {
              startGenerating(entityType);
            });
          }
        }

        function toggleRegeneration(event) {
          event.preventDefault();

          var $target = $(event.target);
          var entityType = $target.data('entity-type');
          var busy = regenerating[entityType];

          if (!busy) {
            startGenerating(entityType);
          } else {
            pauseGenerating(entityType);
          }
        }

        $(document).ready(function () {
          $('.ajax-regenerate-button').each(function (index, elem) {
            $(elem).click(toggleRegeneration);
          });
          var $regenerateAllImages = $('#regenerateAllImages');
          $regenerateAllImages.click(toggleGeneratingAll);
          $regenerateAllImages.parent().prepend('<button style="margin-left: 5px" type="button" id="resetImageStats" name="resetImageStats" class="btn btn-default"><i class="process-icon-refresh"></i> {l s='Reset indexation status' js=1}</button>');
          $regenerateAllImages.parent().prepend('<button type="button" id="deleteOldImages" name="regenerateAllImages" class="btn btn-default"><i class="process-icon-delete"></i> {l s='Delete generated thumbnails' js=1}</button>');
          $('#deleteOldImages').click(deleteOldImages);
          $('#resetImageStats').click(resetImageStats);
        });
      }

      initRegenerationButtons();
    }());
  </script>
{/if}
