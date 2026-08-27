<div class="panel">
	<h3><i class="icon-info-circle"></i> {l s='System cart rule'}</h3>
	<div class="alert alert-info">
		{l s='This cart rule was generated automatically by the system for a cheapest-product discount and is read-only.'}
	</div>

	<div class="form-horizontal">
		<div class="row">
			<div class="col-lg-6">
				<div class="form-group">
					<label class="control-label col-lg-5">{l s='Generated from cart rule'}</label>
					<div class="col-lg-7">
						<p class="form-control-static">
							{if $systemRuleDetails.source_rule_link}
								<a href="{$systemRuleDetails.source_rule_link|escape:'html':'UTF-8'}">{$systemRuleDetails.source_rule_label|escape:'html':'UTF-8'}</a>
							{else}
								{$systemRuleDetails.source_rule_label|escape:'html':'UTF-8'}
							{/if}
						</p>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label col-lg-5">{l s='Created for order'}</label>
					<div class="col-lg-7">
						<p class="form-control-static">
							{if $systemRuleDetails.order_link}
								<a href="{$systemRuleDetails.order_link|escape:'html':'UTF-8'}">{$systemRuleDetails.order_label|escape:'html':'UTF-8'}</a>
							{else}
								{$systemRuleDetails.order_label|escape:'html':'UTF-8'}
							{/if}
						</p>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label col-lg-5">{l s='Discount applied to product'}</label>
					<div class="col-lg-7">
						<p class="form-control-static">
							{if $systemRuleDetails.product_link}
								<a href="{$systemRuleDetails.product_link|escape:'html':'UTF-8'}">{$systemRuleDetails.product_label|escape:'html':'UTF-8'}</a>
							{else}
								{$systemRuleDetails.product_label|escape:'html':'UTF-8'}
							{/if}
						</p>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label col-lg-5">{l s='Combination'}</label>
					<div class="col-lg-7">
						<p class="form-control-static">
							{if $systemRuleDetails.combination_label}
								{$systemRuleDetails.combination_label|escape:'html':'UTF-8'}
							{else}
								{l s='No combination'}
							{/if}
						</p>
					</div>
				</div>
			</div>

			<div class="col-lg-6">
				<div class="form-group">
					<label class="control-label col-lg-5">{l s='System generated cart rule'}</label>
					<div class="col-lg-7">
						<p class="form-control-static">{$currentObject->code|escape:'html':'UTF-8'}</p>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label col-lg-5">{l s='Discount percent'}</label>
					<div class="col-lg-7">
						<p class="form-control-static">{$systemRuleDetails.discount_percent_label|escape:'html':'UTF-8'}</p>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label col-lg-5">{l s='Total discount'}</label>
					<div class="col-lg-7">
						<p class="form-control-static">{$systemRuleDetails.discount_amount_label|escape:'html':'UTF-8'}</p>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label col-lg-5">{l s='Created at'}</label>
					<div class="col-lg-7">
						<p class="form-control-static">{$systemRuleDetails.created_at|escape:'html':'UTF-8'}</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

{include file="footer_toolbar.tpl"}
