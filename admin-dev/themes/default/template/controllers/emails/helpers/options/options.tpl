{*
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
*}
{extends file="helpers/options/options.tpl"}

{block name="field"}
	{if $key == 'TB_MAIL_TRANSPORT'}
		{$smarty.block.parent}
		{foreach $field.list as $option}
			{$optionId = str_replace(':', '_', $option.id)}
			<div class="col-lg-9 col-lg-offset-3 mail_transport_hint" id="mail_transport_{$optionId}" {if $option.id != $field.value}style="display:none"{/if}>
				<div class="help-block">
					{$option.hint}

				</div>
				{if $option.config}
					<a class="btn" href="{$option.config}" target="_blank">
						<i class="icon-wrench"></i>
						{l s='Configure'|escape:'html'}
					</a>
				{/if}
			</div>
		{/foreach}
		<script type="application/javascript">
			function updateMailTransport() {
				const selected = $('#TB_MAIL_TRANSPORT').val().replace(':', '_');
				$('.mail_transport_hint').hide();
				$('#mail_transport_'+selected).show();
			}
			$('#TB_MAIL_TRANSPORT').on('change', updateMailTransport);
			updateMailTransport();
		</script>
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name="after"}
	<script type="application/javascript">
		function sendTestEmail()
		{
			document.getElementById('mailResultCheck').style.display = 'none';
			const email = document.getElementById('testEmail').value;
			if (validate_isEmail(email)) {
				$.ajax({
					type:"POST",
					url: "{$link->getAdminLink('AdminEmails')}",
					async: true,
					dataType: "json",
					data : {
						ajax: "1",
						action: "sendTestEmail",
						email: email
					},
					success: function(data) {
						if (data.status === 'success') {
							sendTestEmailSuccess();
						} else {
							sendTestEmailFailure(data.message);
						}
					},
					error: function() {
						sendTestEmailFailure("Internal server error");
					}
				});
			} else {
				sendTestEmailFailure("Invalid email address");
			}
		}

		function sendTestEmailFailure(msg)
		{
			const element = document.getElementById('mailResultCheck');
			element.style.display = 'block';
			element.classList.remove('alert-success');
			element.classList.add('alert-danger');
			element.innerText = msg;
		}

		function sendTestEmailSuccess()
		{
			const element = document.getElementById('mailResultCheck');
			element.style.display = 'block';
			element.classList.add('alert-success');
			element.classList.remove('alert-danger');
			element.innerText = "{l s="Email sent, please check your inbox"|escape:'javascript'}";
		}
	</script>
{/block}