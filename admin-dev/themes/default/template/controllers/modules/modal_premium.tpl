<div class="modal fade" id="modal-premium-module-{$module->name}" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title">{l s="Premium module: %s" sprintf=[$module->displayName]}</h4>
			</div>
			<div class="modal-body">
				{if isset($module->description_full) && $module->description_full}
					<p>{$module->description_full}</p>
				{else}
					<p>{$module->description|escape:'html'}</p>
				{/if}
				{if isset($module->url) && $module->url}
					<p>{l s="For more information about this module, please visit [1]module page[/1]." tags=['<a target="_blank" href="'|cat:{$module->url}|cat:'">']}</p>
				{/if}
				<hr />
				{l s="This is a [1]Premimum Module[/1] available to following supporters:" tags=['<b>']}
				<br />
				<ul>
				{foreach $module->premium as $groupInfo}
					<li>{$groupInfo.name}</li>
				{/foreach}
				</ul>
				{l s='If you are already thirty bees supporter, please [1]connect[/1] this thirty bees installation with your account.' tags=['<a href="'|cat:{$connectLink}|cat:'">']}
			</div>
			<div class="modal-footer">
				<a href="{$connectLink|escape:'html'}" class="btn btn-primary">{l s="Login to thirty bees"}</a>
				<button type="button" class="btn btn-default" data-dismiss="modal">{l s="Close"}</button>
			</div>
		</div>
	</div>
</div>