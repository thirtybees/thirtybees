<p id="referralprogram">
  <i class="icon icon-flag"></i>
  {l s='You have earned a voucher worth %s thanks to your sponsor!' sprintf=$discount_display mod='referralprogram'}
  {l s='Enter voucher name %s to receive the reduction on this order.' sprintf=$discount->name mod='referralprogram'}
  <a href="{$link->getModuleLink('referralprogram', 'program', [], true)|escape:'html':'UTF-8'}" title="{l s='Referral program' mod='referralprogram'}" rel="nofollow">{l s='View your referral program.' mod='referralprogram'}</a>
</p>
<br />
