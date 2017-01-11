<p id="loyalty" class="align_justify">
  {if $points}
    {l s='By buying this product you can collect up to' mod='loyalty'} <b><span id="loyalty_points">{$points}</span>
    {if $points > 1}{l s='loyalty points' mod='loyalty'}{else}{l s='loyalty point' mod='loyalty'}{/if}</b>.
    {l s='Your cart will total' mod='loyalty'} <b><span id="total_loyalty_points">{$total_points}</span>
    {if $total_points > 1}{l s='loyalty points' mod='loyalty'}{else}{l s='loyalty point' mod='loyalty'}{/if}</b> {l s='that can be converted into a voucher of' mod='loyalty'}
    <span id="loyalty_price">{convertPrice price=$voucher}</span>.
  {else}
    {if isset($no_pts_discounted) && $no_pts_discounted == 1}
      {l s='No reward points for this product because there\'s already a discount.' mod='loyalty'}
    {else}
      {l s='No reward points for this product.' mod='loyalty'}
    {/if}
  {/if}
</p>

{addJsDef point_rate=$point_rate}
{addJsDef point_value=$point_value}
{addJsDef points_in_cart=$points_in_cart}
{addJsDef none_award=$none_award}

{addJsDefL name=loyalty_willcollect}{l s='By buying this product you can collect up to' mod='loyalty' js=1}{/addJsDefL}
{addJsDefL name=loyalty_already}{l s='No reward points for this product because there\'s already a discount.' mod='loyalty' js=1}{/addJsDefL}
{addJsDefL name=loyalty_nopoints}{l s='No reward points for this product.' mod='loyalty' js=1}{/addJsDefL}
{addJsDefL name=loyalty_points}{l s='loyalty points' mod='loyalty' js=1}{/addJsDefL}
{addJsDefL name=loyalty_point}{l s='loyalty point' mod='loyalty' js=1}{/addJsDefL}
{addJsDefL name=loyalty_total}{l s='Your cart will total' mod='loyalty' js=1}{/addJsDefL}
{addJsDefL name=loyalty_converted}{l s='that can be converted into a voucher of' mod='loyalty' js=1}{/addJsDefL}
