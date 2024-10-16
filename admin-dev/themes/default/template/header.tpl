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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7 lt-ie6 " lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8 ie7" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9 ie8" lang="en"> <![endif]-->
<!--[if gt IE 8]> <html lang="fr" class="no-js ie9" lang="en"> <![endif]-->
<html lang="{$iso}">
<head>
	<meta charset="utf-8">

	<meta name="viewport" content="width=device-width, initial-scale=0.75, maximum-scale=0.75, user-scalable=0">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="icon" type="image/x-icon" href="{$img_dir}favicon.ico" />
	<link rel="apple-touch-icon" href="{$img_dir}app_icon.png" />

	<meta name="robots" content="NOFOLLOW, NOINDEX">
	<title>{if $meta_title != ''}{$meta_title} • {/if}{$shop_name}</title>
	{if !isset($display_header_javascript) || $display_header_javascript}
	<script type="text/javascript">
		var supporter_info = {if $supporterInfo}{$supporterInfo|json_encode}{else}false{/if};
		var help_class_name = '{$controller_name|@addcslashes:'\''}';
		var iso_user = '{$iso_user|@addcslashes:'\''}';
		var full_language_code = '{$full_language_code|@addcslashes:'\''}';
		var country_iso_code = '{$country_iso_code|@addcslashes:'\''}';
		var _PS_VERSION_ = '{$smarty.const._TB_VERSION_|@addcslashes:'\''}';
		var roundMode = {$round_mode|intval};
		var autorefresh_notifications = {$autorefresh_notifications|intval};
		var new_order_msg = '{l s='A new order has been placed on your shop.' js=1}';
		var order_number_msg = '{l s='Order number:' js=1} ';
		var total_msg = '{l s='Total:' js=1} ';
		var from_msg = '{l s='From:' js=1} ';
		var see_order_msg = '{l s='View this order' js=1}';
		var new_customer_msg = '{l s='A new customer registered on your shop.' js=1}';
		var customer_name_msg = '{l s='Customer name:' js=1} ';
		var new_msg = '{l s='A new message was posted on your shop.' js=1}';
		var see_msg = '{l s='Read this message' js=1}';
		var token = '{$token|addslashes}';
		var token_admin_orders = '{getAdminToken tab='AdminOrders'}';
		var token_admin_customers = '{getAdminToken tab='AdminCustomers'}';
		var token_admin_customer_threads = '{getAdminToken tab='AdminCustomerThreads'}';
		var currentIndex = '{$currentIndex|@addcslashes:'\''}';
		var employee_token = '{getAdminToken tab='AdminEmployees'}';
		var choose_language_translate = '{l s='Choose language' js=1}';
		var default_language = '{$default_language|intval}';
		var admin_modules_link = '{$link->getAdminLink("AdminModules")|addslashes}';
		var update_success_msg = '{l s='Update successful' js=1}';
		var search_product_msg = '{l s='Search for a product' js=1}';

		/// Campaign Bar ///

		/// Supporter ///
		var campaign_bar_love_class = 'campaign-bar-supporter';
		var campaign_bar_love_intro = '{l s="Love [1]ThirtyBees?[/1] Help Us [2]Grow![/2]" tags=["<b>", "<b>"] js=1}';
		var campaign_bar_love_cta = '{l s="Become a Supporter Today!" js=1}';
		var campaign_bar_love_url = "#";

		/// Technical Support ///
		var campaign_bar_techsupport_class = 'campaign-bar-technical-support';
		var campaign_bar_techsupport_intro = '{l s="Need [1]Support?[/1] We are here to [1]help![/1]" tags=["<b>"] js=1}';
		var campaign_bar_techsupport_cta = '{l s="Contact Paid Support" js=1}';
		var campaign_bar_techsupport_url = "https://thirtybees.com/contact/";

		/// Thank You ///
		var campaign_bar_thanks_class = 'campaign-bar-thanks';
		var campaign_bar_thanks_intro = '{l s="[1][2]Thank You[/2] for choosing [2]ThirtyBees![/2][/1]" tags=["<div></div>","<b>"] js=1}';
		var campaign_bar_thanks_cta = "";
		var campaign_bar_thanks_url = "";

		/// Premium Modules ///
		var campaign_bar_premium_class = 'campaign-bar-premium';
		var campaign_bar_premium_intro = '{l s="[1]Enhance Your Store[/1] with [1]Premium Modules[/1]" tags=["<b>"] js=1}';
		var campaign_bar_premium_cta = '{l s="Check them out now" js =1}';
		var campaign_bar_premium_url = "{$link->getAdminLink('AdminModules')}";

		/// Supporter ///
		var campaign_slider_love_class = 'campaign-slider-supporter';
		var campaign_slider_love_header = '{l s="Love [1]ThirtyBees?[/1]" tags=["<b>"] js=1}';
		var campaign_slider_love_intro = '{l s="Support OpenSource - [1]Help us Grow![/1]" tags=["<b>"] js=1}';
		var campaign_slider_love_cta = '{l s="Become a Supporter Today!"}';
		var campaign_slider_love_url = "#";

		/// Technical Support ///
		var campaign_slider_techsupport_class = 'campaign-slider-technical-support';
		var campaign_slider_techsupport_header = '{l s="Need [1]Support?[/1]" tags=["<b>"] js=1}';
		var campaign_slider_techsupport_intro = '{l s="We are here to [1]help![/1]" tags=["<b>"] js=1}';
		var campaign_slider_techsupport_cta = '{l s="Contact Paid Support" js=1}';
		var campaign_slider_techsupport_url = "https://thirtybees.com/contact/";

		/// Thank You ///
		var campaign_slider_thanks_class = 'campaign-slider-thanks';
		var campaign_slider_thanks_header = '{l s="[1]Thank You![/1]" tags=["<b>"] js=1}';
		var campaign_slider_thanks_intro = '{l s="Thank you for choosing ThirtyBees :)" tags=["<b>"] js=1}';
		var campaign_slider_thanks_cta = "";
		var campaign_slider_thanks_url = "";

		/// Premium Modules ///
		var campaign_slider_premium_class = 'campaign-slider-premium';
		var campaign_slider_premium_header = '{l s="[1]Enhance[/1] Your Store" tags=["<b>"] js=1}';
		var campaign_slider_premium_intro = '{l s="With [1]Premium Modules[/1]" tags=["<b>"] js=1}';
		var campaign_slider_premium_cta = '{l s="Check them out now" js=1}';
		var campaign_slider_premium_url = "{$link->getAdminLink('AdminModules')}";
	</script>
{/if}
{if isset($css_files)}
{foreach from=$css_files key=css_uri item=media}
	<link rel="stylesheet" href="{$css_uri|escape:'html':'UTF-8'}" type="text/css" media="{$media|escape:'html':'UTF-8'}" />
{/foreach}
{/if}
	{if (isset($js_def) && count($js_def) || isset($js_files) && count($js_files))}
		{include file=$smarty.const._PS_ALL_THEMES_DIR_|cat:"javascript.tpl"}
	{/if}

	{if isset($displayBackOfficeHeader)}
		{$displayBackOfficeHeader}
	{/if}
	{if isset($brightness)}
	{/if}
</head>

{if $display_header}
	<body class="ps_back-office{if $employee->bo_menu} page-sidebar{if $collapse_menu} page-sidebar-closed{/if}{else} page-topbar{/if} {$smarty.get.controller|escape|strtolower} multistore-context-{$shopContext} {$campaingClass}">
	{* begin  HEADER *}
	<header id="header" class="bootstrap">
		<nav id="header_infos" role="navigation">
			<div class="navbar-header tb-admin-campaign-bar {if isset($displayBackOfficeTop) && $displayBackOfficeTop}tb-admin-header-with-hook{/if}">
				<button id="header_nav_toggle" type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse-primary">
					<i class="icon-reorder"></i>
				</button>
				<div class="admin-shopversion-holder">
					{if $supporterInfo}
						<div class="member-type">
							<span title='{l s="%s. Thank you for your support!" sprintf=[$supporterInfo.name]}'>{$supporterInfo.name}</span>
						</div>
					{/if}
					<a class="mobile-logo" href="{$default_tab_link|escape:'html':'UTF-8'}"></a>
					<a id="header_shopversion" href="{$default_tab_link|escape:'html':'UTF-8'}">
						<span id="shop_version">{$version}</span>
					</a>
				</div>

				{* /// Shop Name and MultiShop holder/// *}
				{if isset($is_multishop) && $is_multishop && $shop_list && (isset($multishop_context) && $multishop_context & Shop::CONTEXT_GROUP || $multishop_context & Shop::CONTEXT_SHOP)}
					<ul id="header_shop">
						<li class="dropdown tb-admin-top-bar-multishop-outer-container">
							{$shop_list}
						</li>
					</ul>
				{else}
					<a id="header_shopname" href="{$default_tab_link|escape:'html':'UTF-8'}" title="{$shop_name}">{$shop_name}</a>
				{/if}

				{* /// Notifications and Quick Access Holder /// *}
				<section class="notifications-quick-access-holder">
					{* Notifications *}
					<div class="notifications-icon">
						<a class="tb-admin-campaign-bar-fa-icon">
							<i class="icon-bell"></i>
							<span class="notifs_badge"><span>!</span></span>
						</a>
					</div>
					<ul id="header_notifs_icon_wrapper">
						{foreach $notificationTypes as $notificationType}
							<li id="{$notificationType.type}_notif" class="dropdown" data-type="{$notificationType.type}" data-last-id="0">
								<a href="javascript:void(0);" class="dropdown-toggle notifs" data-toggle="dropdown">
									<i class="{$notificationType.icon}"></i>
									<span id="{$notificationType.type}_notif_number_wrapper" class="notifs_badge hide">
										<span id="{$notificationType.type}_notif_value">0</span>
									</span>
								</a>

								<div class="dropdown-menu notifs_dropdown">
									<section id="{$notificationType.type}_notif_wrapper" class="notifs_panel">
											<div class="notifs_panel_header">
											<h3>{$notificationType.header}</h3>
										</div>
										<div id="{$notificationType.type}_notif_list" class="list_notif">
											<span class="no_notifs">{$notificationType.emptyMessage}</span>
										</div>
										<div class="notifs_panel_footer">
											<a href="{$notificationType.showAllLink}">{$notificationType.showAll}</a>
										</div>
									</section>
								</div>
							</li>
						{/foreach}
						{hook h='displayAdminHeaderNotif'}
					</ul>

					{* /// Quick Access /// *}
					{if count($quick_access) >= 0}
					<ul id="header_quick">
						<li class="header-main-li">
							<a href="javascript:void(0)" id="quick_select" class="dropdown-toggle" data-toggle="dropdown">
								<span class="header-quick-icon"><i class="icon-flash"></i></span>
								<span class="header-quick-text">{l s='Quick Access'} </span>
								<i class="icon-angle-down"></i>
							</a>
							<ul class="dropdown-menu">
								{foreach $quick_access as $quick}
									<li {if $link->matchQuickLink({$quick.link})}{assign "matchQuickLink" $quick.id_quick_access}class="active"{/if}>
										<a href="{$quick.link|escape:'html':'UTF-8'}"{if $quick.new_window} class="_blank"{/if}>
											{if isset($quick.icon)}
												<i class="icon-{$quick.icon} icon-fw"></i>
											{else}
												<i class="icon-chevron-right icon-fw"></i>
											{/if}
											{$quick.name}
										</a>
									</li>
								{/foreach}
								<li class="divider"></li>
								{if isset($matchQuickLink)}
									<li>
										<a href="javascript:void(0);" class="ajax-quick-link" data-method="remove" data-quicklink-id="{$matchQuickLink}">
											<i class="icon-minus-circle"></i>
											{l s='Remove from QuickAccess'}
										</a>
									</li>
								{/if}
								<li {if isset($matchQuickLink)}class="hide"{/if}>
									<a href="javascript:void(0);" class="ajax-quick-link" data-method="add">
										<i class="icon-plus-circle" style="margin-right: 5px"></i>
										{l s='Add current page to QuickAccess'}
									</a>
								</li>
								<li {if isset($matchQuickLink)}class="hide"{/if}>
									<a href="{$link->getAdminLink('AdminQuickAccesses')}">
										<i class="icon-flash" style="margin-right: 9px"></i>
										{l s='Manage Quick Access'}
									</a>
								</li>
							</ul>
						</li>
					</ul>
					{$quick_access_current_link_name = " - "|explode:$quick_access_current_link_name}
					<script>
						$(function() {
							$('.ajax-quick-link').on('click', function(e){
								e.preventDefault();

								var method = $(this).data('method');

								if(method == 'add')
									var name = prompt('{l s='Please name this shortcut:' js=1}', '{$quick_access_current_link_name.0|escape:'javascript'|truncate:32}');

								if(method == 'add' && name || method == 'remove')
								{
									$.ajax({
										type: 'POST',
										headers: { "cache-control": "no-cache" },
										async: false,
										url: "{$link->getAdminLink('AdminQuickAccesses')}" + "&action=GetUrl" + "&rand={'1'|rand:200}" + "&ajax=1" + "&method=" + method + ( $(this).data('quicklink-id') ? "&id_quick_access=" + $(this).data('quicklink-id') : ""),
										data: {
											"url": "{$link->getQuickLink($smarty.server['REQUEST_URI'])}",
											"name": name,
											"icon": "{$quick_access_current_link_icon}"
										},
										dataType: "json",
										success: function(data) {
											var quicklink_list ='';
											$.each(data, function(index,value){
												if (typeof data[index]['name'] !== 'undefined')
													quicklink_list += '<li><a href="' + data[index]['link'] + '&token=' + data[index]['token'] + '"><i class="icon-chevron-right"></i> ' + data[index]['name'] + '</a></li>';
											});

											if (typeof data['has_errors'] !== 'undefined' && data['has_errors'])
												$.each(data, function(index, value)
												{
													if (typeof data[index] == 'string')
														$.growl.error({ title: "", message: data[index]});
												});
											else if (quicklink_list)
											{
												$("#header_quick ul.dropdown-menu").html(quicklink_list);
												showSuccessMessage(update_success_msg);
											}
										}
									});
								}
							});
						});
					</script>
					{/if}
				</section>

				{if isset($displayBackOfficeTop) && $displayBackOfficeTop}
				<div class="display-back-office-top-hook">
					{$displayBackOfficeTop}
				</div>
				{/if}

				{* /// Campaign Bar /// *}
				<div class="campaign-bar-holder">
					<div class="campaign-bar-holder-inner-outer">
						<div class="campaign-bar-close-holder" title="{l s='We appreciate your support'}.&#10;{l s='Click to close these messages.'}">
							<div class="campaign-bar-close-icon">x</div>
						</div>
						<div class="campaign-bar-holder-inner-actual">
							<div class="tb-admin-campaign-bar-icon"></div>
							<div class="tb-admin-campaign-bar-text">
								<div class="tb-admin-campaign-bar-text-inner"></div>
								<div class="tb-admin-campaign-bar-cta-inline">
									<a href=""></a>
								</div>
							</div>
							<div class="tb-admin-campaign-bar-cta">
								<a href=""></a>
							</div>
						</div>
					</div>
				</div>

				<div id="header_employee_box" class="{if isset($maintenance_mode) && $maintenance_mode == true}maintenance-mode-on{/if}">
					{if {$base_url}}
						<a class="tb-admin-campaign-bar-fa-icon label-tooltip" data-toggle="tooltip" data-placement="bottom" data-html="true" href="{if isset($base_url_tc)}{$base_url_tc|escape:'html':'UTF-8'}{else}{$base_url|escape:'html':'UTF-8'}{/if}" title="" data-original-title="<p class='text-left text-nowrap'>{l s='View front office'}</p>" target="_blank">
							<i class="icon-eye"></i>
						</a>
					{/if}

					{if (isset($maintenance_mode) && $maintenance_mode == true)}
						<a href="{$link->getAdminLink('AdminMaintenance')}" class="tb-admin-campaign-bar-fa-icon label-tooltip" data-toggle="tooltip" data-placement="bottom" data-html="true" title="" data-original-title="<p class='text-left text-nowrap'><strong>Your shop is in Maintenance mode.</strong></p><p class='text-left maintenance-tooltip'>Your visitors and customers cannot access your shop while in maintenance mode.<br /> Click to turn off Maintenance mode.</p>">
							<i class="icon icon-cog"></i>
						</a>
					{/if}

					{if defined('_PS_MODE_DEV_') && _PS_MODE_DEV_}
						<a href="{$link->getAdminLink('AdminPerformance')}" class="tb-admin-campaign-bar-fa-icon label-tooltip" data-toggle="tooltip" data-placement="bottom" data-html="true" title="" data-original-title="<p class='text-left text-nowrap'><strong>Your shop is in Debug mode.</strong></p><p class='text-left maintenance-tooltip'>Your site is in Debug Mode.<br /> Click to turn it off.</p>">
							<i class="icon icon-bug"></i>
						</a>
					{/if}

					<div id="employee_infos" class="dropdown label-tooltip username-tooltip" data-toggle="tooltip" data-placement="bottom" data-html="true" title="{$employee->firstname}&nbsp;{$employee->lastname}">
						<a href="{$link->getAdminLink('AdminEmployees')|escape:'html':'UTF-8'}&amp;id_employee={$employee->id|intval}&amp;updateemployee" class="tb-admin-topbar-employee-holder employee_name dropdown-toggle" data-toggle="dropdown">
							{if isset($employee)}
								<i class="icon-user"></i>
							{/if}
							<i class="icon-angle-down"></i>
						</a>
						<ul id="employee_links" class="dropdown-menu">
							<li>
								<span class="employee_avatar">
									<i class="icon icon-user" style="font-size:6em"></i>
								</span>
							</li>
							<li class="text-center text-nowrap">{$employee->firstname} {$employee->lastname}</li>
							<li class="divider"></li>
							<li><a href="{$link->getAdminLink('AdminEmployees')|escape:'html':'UTF-8'}&amp;id_employee={$employee->id|intval}&amp;updateemployee"><i class="icon-wrench"></i> {l s='My preferences'}</a></li>
							<li class="divider"></li>
							<li><a id="header_logout" href="{$login_link|escape:'html':'UTF-8'}&amp;logout"><i class="icon-signout"></i> {l s='Sign out'}</a></li>
						</ul>
					</div>
				</div>

				<span id="ajax_running">
					<i class="icon-refresh icon-spin icon-fw"></i>
				</span>


			</div>
		</nav>{* end header_infos*}
	</header>

	<div id="main">
		{include file='nav.tpl'}

		<div id="content" class="{if !$bootstrap}nobootstrap{else}bootstrap{/if}">
			{if isset($page_header_toolbar)}{$page_header_toolbar}{/if}

{if $install_dir_exists}
			<div class="alert alert-warning">
				{l s='For security reasons, you must also delete the /install folder.'}
			</div>
{/if}

			{hook h='displayAdminAfterHeader'}


{*/// Notications Modal ///*}
<div class="modal fade" id="notificationsModal" tabindex="-1" role="dialog" aria-labelledby="notificationsModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="notificationsModalLabel">{l s="View Store Notifications"}</h4>
      </div>
      <div class="modal-body" id="notificationsModalContent"></div>
    </div>
  </div>
</div>

{*/// Support ThirtyBees Main Modal ///*}
<div class="modal fade" id="supportThirtyBeesModal" tabindex="-1" role="dialog" aria-labelledby="supportThirtyBeesModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="supportThirtyBeesModalLabel">{l s="ThirtyBees - Empowering your business to reach new heights!"}</h4>
      </div>
      <div class="modal-body" id="supportThirtyBeesModalContent">
			<div class="support-tb-intro-holder">
				<div class="support-tb-intro-text">
					{l s="[1]Why Support ThirtyBees?[/1] [2]With ThirtyBees you can utilise one of the [1]best Enterprise Class e-Commerce platforms for FREE![/1] [2]Unfortunately, sustaining and enhancing it involves costs. If you love ThirtyBees, [1]please consider supporting us[/1]. [2]Your support ensures ThirtyBees not only survives but thrives! Thank you for being part of our journey." tags=["<b>", "<br/>"]}
				</div>
			</div>

			{*/// Once off donation ///*}
			<a class="support-tb-item-holder" href="https://www.paypal.com/donate/?hosted_button_id=SDAYT6DWZRCSS" target="_blank">
				<div class="support-item-icon">
					<i class="icon-check-circle"></i>
				</div>
				<div class="support-item-text-holder">
					<div class="support-item-text-holder-inner">
						<span>{l s="Once-off donation"}</span>
						<p>
							{l s="Donate any amount once-off"}
						</p>
					</div>
				</div>
			</a>

			{*/// Become a Member ///*}
			<a class="support-tb-item-holder" href="https://forum.thirtybees.com/support-thirty-bees/ " target="_blank">
				<div class="support-item-icon">
					<i class="icon-check-circle"></i>
				</div>
				<div class="support-item-text-holder">
					<div class="support-item-text-holder-inner">
						<span>{l s="Become a Member (monthly subscription tiers)"}</span>
						<p>
							{l s="Select from a range of Monthly subscription amounts. [1] Some tiers include free support hours as well as access to powerful Premium Modules!" tags=["<br/>"]}
						</p>
					</div>
				</div>
			</a>

			{*/// Become a Sponsor ///*}
			<a class="support-tb-item-holder" href="mailto:contact@thirtybees.com?subject=ThirtyBees%20Sponsor%20Request" target="_blank">
				<div class="support-item-icon">
					<i class="icon-check-circle"></i>
				</div>
				<div class="support-item-text-holder">
					<div class="support-item-text-holder-inner">
						<span>{l s="Become a Sponsor"}</span>
						<p>
							{l s="Email us for sponsorship options" tags=["<br/>"]}
						</p>
					</div>
				</div>
			</a>

	  </div>
    </div>
  </div>
</div>

{*/// Support ThirtyBees Close Modal ///*}
<div class="modal fade" id="supportThirtyBeesCloseModal" tabindex="-1" role="dialog" aria-labelledby="supportThirtyBeesCloseModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="supportThirtyBeesCloseModalLabel">{l s="ThirtyBees - Empowering your business to reach new heights!"}</h4>
      </div>
      <div class="modal-body" id="supportThirtyBeesCloseModalContent">
			<div class="support-tb-intro-holder">
				<div class="support-tb-intro-text">
					{l s="[1]Why Support ThirtyBees?[/1] [2]With ThirtyBees you can utilise one of the [1]best Enterprise Class e-Commerce platforms for FREE![/1] [2]Unfortunately, sustaining and enhancing it involves costs. If you love ThirtyBees, [1]please consider supporting us[/1]. [2]Your support ensures ThirtyBees not only survives but thrives! Thank you for being part of our journey." tags=["<b>", "<br/>"]}
				</div>
			</div>

			{*/// Once off donation ///*}
			<a class="support-tb-item-holder" href="https://www.paypal.com/donate/?hosted_button_id=SDAYT6DWZRCSS" target="_blank">
				<div class="support-item-icon">
					<i class="icon-check-circle"></i>
				</div>
				<div class="support-item-text-holder">
					<div class="support-item-text-holder-inner">
						<span>{l s="Once-off donation"}</span>
						<p>
							{l s="Donate any amount once-off"}
						</p>
					</div>
				</div>
			</a>

			{*/// Become a Member ///*}
			<a class="support-tb-item-holder" href="https://forum.thirtybees.com/support-thirty-bees/ " target="_blank">
				<div class="support-item-icon">
					<i class="icon-check-circle"></i>
				</div>
				<div class="support-item-text-holder">
					<div class="support-item-text-holder-inner">
						<span>{l s="Become a Member (monthly subscription tiers)"}</span>
						<p>
							{l s="Select from a range of Monthly subscription amounts. [1] Some tiers include free support hours as well as access to powerful Premium Modules!" tags=["<br/>"]}
						</p>
					</div>
				</div>
			</a>

			{*/// Become a Sponsor ///*}
			<a class="support-tb-item-holder" href="mailto:chiel@thirtybees.com?subject=ThirtyBees%20Sponsor%20Request" target="_blank">
				<div class="support-item-icon">
					<i class="icon-check-circle"></i>
				</div>
				<div class="support-item-text-holder">
					<div class="support-item-text-holder-inner">
						<span>{l s="Become a Sponsor"}</span>
						<p>
							{l s="Email us for sponsorship options" tags=["<br/>"]}
						</p>
					</div>
				</div>
			</a>

			{*/// Close this modal for 1 month ///*}
			<a class="support-tb-item-holder setTopBarModal1Month" href="#">
				<div class="support-item-icon">
					<i class="icon-check-circle"></i>
				</div>
				<div class="support-item-text-holder">
					<div class="support-item-text-holder-inner">
						<span>{l s="Hide the top bar and slider messages for 1 month"}</span>
						<p>
							{l s="Thank you for choosing ThirtyBees. We hope that you will enjoy using it and will consider becoming a supporter in the future." tags=["<br/>"]}
						</p>
					</div>
				</div>
			</a>

	  </div>
    </div>
  </div>
</div>


{* end display_header*}

<div class="campaign-slider-holder-outer">
	<div class="campaign-slider-holder campaign-slider-hide">
		<div class="campaign-slider-holder-inner-outer">
			<div class="campaign-slider-close-holder">
				<div class="campaign-slider-close-holder-inner" title="{l s='We appreciate your support'}.&#10;{l s='Click to close these messages.'}">
					<div class="campaign-slider-close-icon">x</div>
				</div>
			</div>
			<div class="campaign-slider-holder-inner-actual">
				<div class="tb-admin-campaign-slider-icon"></div>
				<div class="tb-admin-campaign-slider-text-cta-holder">
					<div class="tb-admin-campaign-slider-text">
						<div class="tb-admin-campaign-slider-header-inner"></div>
						<div class="tb-admin-campaign-slider-text-inner"></div>
					</div>
					<div class="tb-admin-campaign-slider-cta">
						<a href=""></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="campaign-notification">
	<div Class="campaign-notification-icon"><i class="icon-check-circle"></i></div>
	<div Class="campaign-notification-text"></div>
</div>

{else}
	<body{if isset($lite_display) && $lite_display} class="ps_back-office display-modal"{/if}>
		<div id="main">
			<div id="content" class="{if !$bootstrap}nobootstrap{else}bootstrap{/if}">
{/if}
