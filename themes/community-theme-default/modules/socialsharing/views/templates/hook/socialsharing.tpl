{if $PS_SC_TWITTER || $PS_SC_FACEBOOK || $PS_SC_GOOGLE || $PS_SC_PINTEREST}
	<p class="socialsharing_product hidden-print">
		{if $PS_SC_TWITTER}
			<button data-type="twitter" type="button" class="btn btn-xs btn-twitter">
				<i class="icon-twitter"></i> {l s="Tweet" mod='socialsharing'}
			</button>
		{/if}
		{if $PS_SC_FACEBOOK}
			<button data-type="facebook" type="button" class="btn btn-xs btn-facebook">
				<i class="icon-facebook"></i> {l s="Share" mod='socialsharing'}
			</button>
		{/if}
		{if $PS_SC_GOOGLE}
			<button data-type="google-plus" type="button" class="btn btn-xs btn-google-plus">
				<i class="icon-google-plus"></i> {l s="Google+" mod='socialsharing'}
			</button>
		{/if}
		{if $PS_SC_PINTEREST}
			<button data-type="pinterest" type="button" class="btn btn-xs btn-pinterest">
				<i class="icon-pinterest"></i> {l s="Pinterest" mod='socialsharing'}
			</button>
		{/if}
	</p>
{/if}
