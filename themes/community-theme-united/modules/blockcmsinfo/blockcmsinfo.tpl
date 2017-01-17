{if !empty($infos)}
  {foreach from=$infos item=info}
    <div id="blockcmsinfo-{$info.id_info|escape:'html':'UTF-8'}" class="blockcmsinfo-block col-xs-12 col-sm-4">
      {$info.text}
    </div>
  {/foreach}
{/if}
