{if isset($input.lang) AND $input.lang}
	{if $languages|count > 1}
		<div class="form-group">
			{if isset($input.all_lang_button) && $input.all_lang_button}
				<div class="translatable-field lang-0" style="display: none;">
					<div class="col-lg-9">
						<input type="text" value="" class="all_lang_field" onkeyup="updateAllLanguageFields(this); updateFriendlyURL();">
					</div>
					<div class="col-lg-2">
						<button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
							{l s='ALL'}
							<i class="icon-caret-down"></i>
						</button>
						<ul class="dropdown-menu">
							{foreach from=$languages item=language}
								<li><a href="javascript:hideOtherLanguage({$language.id_lang});" tabindex="-1">{$language.name}</a></li>
							{/foreach}
						</ul>
					</div>
				</div>
			{/if}
	{/if}
			{foreach $languages as $language}
				{if isset($fields_value[$input.name][$language.id_lang])}
					{assign var='value_text' value=$fields_value[$input.name][$language.id_lang]}
				{else}
					{assign var='value_text' value=''}
				{/if}
				{if $languages|count > 1}
					<div class="translatable-field lang-{$language.id_lang}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
					<div class="col-lg-9">
				{/if}
				{if $input.type == 'tags'}
				{literal}
					<script type="text/javascript">
						$().ready(function () {
							var input_id = '{/literal}{if isset($input.id)}{$input.id}_{$language.id_lang}{else}{$input.name}_{$language.id_lang}{/if}{literal}';
							$('#'+input_id).tagify({delimiters: {/literal}{if isset($input.delimiters)}[{$input.delimiters}]{else}[13,44]{/if}{literal}, addTagPrompt: '{/literal}{if isset($input.tagPrompt)}{$input.tagPrompt|escape:'javascript':'UTF-8'}{l s='Add tag' js=1}{/if}{literal}'});
							$({/literal}'#{$table}{literal}_form').submit( function() {
								$(this).find('#'+input_id).val($('#'+input_id).tagify('serialize'));
							});
						});
					</script>
				{/literal}
				{/if}
			{if isset($input.maxchar) || isset($input.prefix) || isset($input.suffix)}
				<div class="input-group{if isset($input.class)} {$input.class}{/if}">
			{/if}
				{if isset($input.disableKeys) && is_array($input.disableKeys)}
					<script type="text/javascript">
						$().ready(function() {
							$('#{if isset($input.id)}{$input.id|escape:'javascript':'UTF-8'}_{$language.id_lang|intval}{else}{$input.name|escape:'javascript':'UTF-8'}_{$language.id_lang|intval}{/if}').keydown(function (e) {
								{foreach $input.disableKeys AS $disabledKey}
								if (e.which == {$disabledKey|intval}) {
									return false;
								}
								{/foreach}
							});
						});
					</script>
				{/if}
				{if isset($input.maxchar) && $input.maxchar}
					<span id="{if isset($input.id)}{$input.id}_{$language.id_lang}{else}{$input.name}_{$language.id_lang}{/if}_counter" class="input-group-addon">
						<span class="text-count-down">{$input.maxchar|intval}</span>
					</span>
				{/if}
				{if isset($input.prefix)}
					<span class="input-group-addon">{$input.prefix}</span>
				{/if}
				<input type="text"
					   id="{if isset($input.id)}{$input.id}_{$language.id_lang}{else}{$input.name}_{$language.id_lang}{/if}"
					   name="{$input.name}_{$language.id_lang}"
					   class="{if isset($input.class)}{$input.class}{/if}{if $input.type == 'tags'} tagify{/if}"
					   value="{if isset($input.string_format) && $input.string_format}{$value_text|string_format:$input.string_format|escape:'html':'UTF-8'}{else}{$value_text|escape:'html':'UTF-8'}{/if}"
					   onkeyup="if (isArrowKey(event)) return ;updateFriendlyURL();"
						{if isset($input.size)} size="{$input.size}"{/if}
						{if isset($input.maxchar) && $input.maxchar} data-maxchar="{$input.maxchar|intval}"{/if}
						{if isset($input.maxlength) && $input.maxlength} maxlength="{$input.maxlength|intval}"{/if}
						{if isset($input.readonly) && $input.readonly} readonly="readonly"{/if}
						{if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
						{if isset($input.autocomplete) && !$input.autocomplete} autocomplete="off"{/if}
						{if isset($input.required) && $input.required} required="required" {/if}
						{if isset($input.placeholder) && $input.placeholder} placeholder="{$input.placeholder}"{/if} />
				{if isset($input.suffix)}
					<span class="input-group-addon">{$input.suffix}</span>
				{/if}
			{if isset($input.maxchar) || isset($input.prefix) || isset($input.suffix)}
				</div>
			{/if}
				{if $languages|count > 1}
					</div>

					<div class="col-lg-2">
						<button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
							{$language.iso_code}
							<i class="icon-caret-down"></i>
						</button>
						<ul class="dropdown-menu">
							{if isset($input.all_lang_button)}
								<li><a href="javascript:hideOtherLanguage(0);" tabindex="-1">{l s='ALL'}</a></li>
							{/if}
							{foreach from=$languages item=language}
								<li><a href="javascript:hideOtherLanguage({$language.id_lang});" tabindex="-1">{$language.name}</a></li>
							{/foreach}
						</ul>
					</div>
					</div>
				{/if}
			{/foreach}
			{if isset($input.maxchar) && $input.maxchar}
				<script type="text/javascript">
					$(document).ready(function(){
						{foreach from=$languages item=language}
						countDown($("#{if isset($input.id)}{$input.id}_{$language.id_lang}{else}{$input.name}_{$language.id_lang}{/if}"), $("#{if isset($input.id)}{$input.id}_{$language.id_lang}{else}{$input.name}_{$language.id_lang}{/if}_counter"));
						{/foreach}
					});
				</script>
			{/if}
	{if $languages|count > 1}
		</div>
	{/if}
{else}
	{if $input.type == 'tags'}
	{literal}
		<script type="text/javascript">
			$().ready(function () {
				var input_id = '{/literal}{if isset($input.id)}{$input.id}{else}{$input.name}{/if}{literal}';
				$('#'+input_id).tagify({delimiters: {/literal}{if isset($input.delimiters)}[{$input.delimiters}]{else}[13,44]{/if}{literal}, addTagPrompt: '{/literal}{if isset($input.tagPrompt)}{$input.tagPrompt|escape:'javascript':'UTF-8'}{else}{l s='Add tag'}{/if}{literal}'});
				$({/literal}'#{$table}{literal}_form').submit( function() {
					$(this).find('#'+input_id).val($('#'+input_id).tagify('serialize'));
				});
			});
		</script>
	{/literal}
	{/if}
	{assign var='value_text' value=$fields_value[$input.name]}
{if isset($input.maxchar) || isset($input.prefix) || isset($input.suffix)}
	<div class="input-group{if isset($input.class)} {$input.class}{/if}">
		{/if}
		{if isset($input.maxchar) && $input.maxchar}
			<span id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}_counter" class="input-group-addon"><span class="text-count-down">{$input.maxchar|intval}</span></span>
		{/if}
		{if isset($input.prefix)}
			<span class="input-group-addon">{$input.prefix}</span>
		{/if}
		<input type="text"
			   name="{$input.name}"
			   id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
			   value="{if isset($input.string_format) && $input.string_format}{$value_text|string_format:$input.string_format|escape:'html':'UTF-8'}{else}{$value_text|escape:'html':'UTF-8'}{/if}"
			   class="{if isset($input.class)}{$input.class}{/if}{if $input.type == 'tags'} tagify{/if}"
				{if isset($input.size)} size="{$input.size}"{/if}
				{if isset($input.maxchar) && $input.maxchar} data-maxchar="{$input.maxchar|intval}"{/if}
				{if isset($input.maxlength) && $input.maxlength} maxlength="{$input.maxlength|intval}"{/if}
				{if isset($input.readonly) && $input.readonly} readonly="readonly"{/if}
				{if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
				{if isset($input.autocomplete) && !$input.autocomplete} autocomplete="off"{/if}
				{if isset($input.required) && $input.required } required="required" {/if}
				{if isset($input.placeholder) && $input.placeholder } placeholder="{$input.placeholder}"{/if}
		/>
		{if isset($input.suffix)}
			<span class="input-group-addon">{$input.suffix}</span>
		{/if}
		{if isset($input.disableKeys) && is_array($input.disableKeys)}
			<script type="text/javascript">
				$().ready(function() {
					$('#{if isset($input.id)}{$input.id|escape:'javascript':'UTF-8'}_{$language.id_lang|intval}{else}{$input.name|escape:'javascript':'UTF-8'}_{$language.id_lang|intval}{/if}').keypress(function (e) {
						{foreach $input.disableKeys AS $disabledKey}
						if (e.which == {$disabledKey|intval}) {
							return false;
						}
						{/foreach}
					});
				});
			</script>
		{/if}
		{if isset($input.maxchar) || isset($input.prefix) || isset($input.suffix)}
	</div>
{/if}
	{if isset($input.maxchar) && $input.maxchar}
		<script type="text/javascript">
			$(document).ready(function(){
				countDown($("#{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"), $("#{if isset($input.id)}{$input.id}{else}{$input.name}{/if}_counter"));
			});
		</script>
	{/if}
{/if}