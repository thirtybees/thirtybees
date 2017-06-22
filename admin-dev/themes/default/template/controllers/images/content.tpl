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
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
*}
{if isset($content)}
  {$content}
{/if}

{if isset($display_move) && $display_move}
    <form action="{$current|escape:'html':'UTF-8'}&amp;token={$token|escape:'html':'UTF-8'}" method="post" class="form-horizontal">
        <div class="panel">
            <h3>
                <i class="icon-picture"></i>
                {l s='Move images'}
            </h3>
            <div class="alert alert-warning">
                <p>{l s='You can choose to keep your images stored in the previous system. There\'s nothing wrong with that.'}</p>
                <p>{l s='You can also decide to move your images to the new storage system. In this case, click on the "Move images" button below. Please be patient. This can take several minutes.'}</p>
            </div>
            <div class="alert alert-info">&nbsp;
                {l s='After moving all of your product images, set the "Use the legacy image filesystem" option above to "No" for best performance.'}
            </div>
            <div class="row">
                <div class="col-lg-12 pull-right">
                    <button type="submit" name="submitMoveImages{$table|escape:'html':'UTF-8'}" class="btn btn-default pull-right" onclick="return confirm('{l s='Are you sure?'}');"><i class="process-icon-cogs"></i> {l s='Move images'}</button>
                </div>
            </div>
        </div>
    </form>
{/if}

{if isset($display_regenerate)}
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

      {foreach $types as $k => $type}
        <div class="form-group second-select format_{$k|escape:'html':'UTF-8'}" style="display:none;">
          <label class="control-label col-lg-3">{l s='Select a format'}</label>
          <div class="col-lg-9 margin-form">
            <select name="format_{$k|escape:'html':'UTF-8'}">
              <option value="all">{l s='All'}</option>
              {foreach $formats[$k] AS $format}
                <option value="{$format['id_image_type']|intval}">{$format['name']|escape:'html':'UTF-8'}</option>
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
          <tr>
            <td>
              <button class="btn btn-info ajax-regenerate-button"
                      id="regenerate{$entityType|ucfirst|escape:'htmlall':'UTF-8'}Images"
                      data-entity-type="{$entityType|escape:'htmlall':'UTF-8'}"
                      {if $status['total'] == 0}disabled="disabled"{/if}
              >
                <i class="icon icon-play"></i> {l s='Regenerate %s' sprintf=[Translate::getAdminTranslation(ucfirst($entityType), 'AdminImages')]}
              </button>
            </td>
            <td width="99%" style="padding-left: 20px; padding-top: 15px">
              <div class="progress{if $status['indexed'] == 0} disabled{/if}"{if $status['indexed'] == 0} disabled="disabled"{/if}>
                <div id="progress-bar-{$entityType|escape:'htmlall':'UTF-8'}"
                     class="progress-bar"
                     role="progressbar"
                     style="width: {if $status['total'] == 0}0{else}{(($status['indexed']|floatval / $status['total']|floatval) * 100)|intval}{/if}%; text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000">
                  <span style="position: absolute; padding-left: 5px; padding-right: 5px">
                    <span id="regen-indexed-{$entityType|escape:'html':'UTF-8'}">{$status['indexed']|intval}</span> / <span id="regen-total-{$entityType|escape:'html':'UTF-8'}">{$status['total']|intval}</span>
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
      var regenerating = window.regen = {
        products: false,
        categories: false,
        suppliers: false,
        manufacturers: false,
        scenes: false,
        stores: false,
      };

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
                $.each(response.indexStatus, function (entityType) {
                  $('#regen-indexed-' + entityType).text(response.indexStatus[entityType].indexed);
                  $('#regen-total-' + entityType).text(response.indexStatus[entityType].total);
                  $('#progress-bar-' + entityType).css('width', (response.indexStatus[entityType].indexed / response.indexStatus[entityType].total) * 100);
                });
              }
            },
            error: function (jqXhr) {
              showErrorMessage('{l s='Server timed out before all images could be deleted. You might want to increase the `max_execution_time`' js=1}');

              if (parseInt(jqXhr.status, 10) === 504) {
                $.each(regenerating, function (entityType) {
                  $('#regen-indexed-' + entityType).text('0');
                  $('#regen-total-' + entityType).text('0');
                  $('#progress-bar-' + entityType).css('width', 0);
                });
              } else if (parseInt(jqXhr.status, 10) >= 500 < 600) {
                showErrorMessage('{l s='Received a 5xx response (generic error). Make the rate limit of the server has been (temporarily) increased' js=1}');
              }
            },
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
                $.each(response.indexStatus, function (entityType) {
                  $('#regen-indexed-' + entityType).text(response.indexStatus[entityType].indexed);
                  $('#regen-total-' + entityType).text(response.indexStatus[entityType].total);
                  $('#progress-bar-' + entityType).css('width', (response.indexStatus[entityType].indexed / response.indexStatus[entityType].total) * 100);
                });
              }
            },
            error: function (jqXhr) {
              showErrorMessage('{l s='Server timed out before all images could be deleted. You might want to increase the `max_execution_time`' js=1}');

              if (parseInt(jqXhr.status, 10) === 504) {
                $.each(regenerating, function (entityType) {
                  $('#regen-indexed-' + entityType).text('0');
                  $('#regen-total-' + entityType).text('0');
                  $('#progress-bar-' + entityType).css('width', 0);
                });
              } else if (parseInt(jqXhr.status, 10) >= 500 < 600) {
                showErrorMessage('{l s='Received a 5xx response (generic error). Make the rate limit of the server has been (temporarily) increased' js=1}');
              }
            },
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
                if (response.indexStatus[entityType].indexed == 0) {
                  response.indexStatus[entityType].indexed = response.indexStatus[entityType].total;
                }
                $('#regen-indexed-' + entityType).text(response.indexStatus[entityType].indexed);
                $('#regen-total-' + entityType).text(response.indexStatus[entityType].total);
                $('#progress-bar-' + entityType).css('width', (response.indexStatus[entityType].indexed / response.indexStatus[entityType].total) * 100 + '%');

                if (response.indexStatus[entityType].indexed == response.indexStatus[entityType].total) {
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
            error: function (jqXhr) {
              if (parseInt(jqXhr.status, 10) >= 500 && parseInt(jqXhr.status, 10) < 600) {
                showErrorMessage('{l s='Received a 5xx response (generic error). Make the rate limit of the server has been (temporarily) increased' js=1}');
              }
            },
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
