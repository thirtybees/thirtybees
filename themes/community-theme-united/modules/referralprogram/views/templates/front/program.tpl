{capture name=path}<a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}" title="{l s='Manage my account' mod='referralprogram'}" rel="nofollow">{l s='My account' mod='referralprogram'}</a><span class="navigation-pipe">{$navigationPipe}</span><span class="navigation_page">{l s='Referral Program' mod='referralprogram'}</span>{/capture}

<h1 class="page-heading">{l s='Referral program' mod='referralprogram'}</h1>

{if $error}
  <div class="alert alert-danger">
    {if $error == 'conditions not valided'}
      {l s='You need to agree to the conditions of the referral program!' mod='referralprogram'}
    {elseif $error == 'email invalid'}
      {l s='At least one e-mail address is invalid!' mod='referralprogram'}
    {elseif $error == 'name invalid'}
      {l s='At least one first name or last name is invalid!' mod='referralprogram'}
    {elseif $error == 'email exists'}
      {l s='Someone with this e-mail address has already been sponsored!' mod='referralprogram'}: {foreach from=$mails_exists item=mail}{$mail} {/foreach}
    {elseif $error == 'no revive checked'}
      {l s='Please mark at least one checkbox' mod='referralprogram'}
    {elseif $error == 'cannot add friends'}
      {l s='Cannot add friends to database' mod='referralprogram'}
    {/if}
  </div>
{/if}

{if $invitation_sent}
  <div class="alert alert-success">
    {if $nbInvitation > 1}
      {l s='E-mails have been sent to your friends!' mod='referralprogram'}
    {else}
      {l s='An e-mail has been sent to your friend!' mod='referralprogram'}
    {/if}
  </div>
{/if}

{if $revive_sent}
  <div class="alert alert-success">
    {if $nbRevive > 1}
      {l s='Reminder e-mails have been sent to your friends!' mod='referralprogram'}
    {else}
      {l s='A reminder e-mail has been sent to your friend!' mod='referralprogram'}
    {/if}
  </div>
{/if}
<ul class="nav nav-tabs" id="idTabs">
  <li class="active"><a data-toggle="tab" href="#idTab1" class="tab-pane {if $activeTab eq 'sponsor'} active{/if}" title="{l s='Sponsor my friends' mod='referralprogram'}" rel="nofollow">{l s='Sponsor my friends' mod='referralprogram'}</a></li>
  <li><a data-toggle="tab" href="#idTab2"  class="tab-pane {if $activeTab eq 'pending'} selected{/if}" title="{l s='List of pending friends' mod='referralprogram'}" rel="nofollow">{l s='Pending friends' mod='referralprogram'}</a></li>
  <li><a data-toggle="tab" href="#idTab3" class="tab-pane {if $activeTab eq 'subscribed'} selected{/if}" title="{l s='List of friends I sponsored' mod='referralprogram'}" rel="nofollow">{l s='Friends I sponsored' mod='referralprogram'}</a></li>
</ul>
<div class="sheets tab-content">

  <div id="idTab1" class="tab-pane active">
    <p class="bold">
      <strong>{l s='Get a discount of %1$s for you and your friends by recommending this Website.' sprintf=[$discount] mod='referralprogram'}</strong>
    </p>
    {if $canSendInvitations}
      <p>
        {l s='It\'s quick and it\'s easy. Just fill in the first name, last name, and e-mail address(es) of your friend(s) in the fields below.' mod='referralprogram'}
        {if $orderQuantity > 1}
          {l s='When one of them makes at least %d orders, ' sprintf=$orderQuantity mod='referralprogram'}
        {else}
          {l s='When one of them makes at least %d order, ' sprintf=$orderQuantity mod='referralprogram'}
        {/if},
        {l s='he or she will receive a %1$s voucher and you will receive your own voucher worth %1$s.' sprintf=[$discount] mod='referralprogram'}
      </p>
      <form method="post" action="{$link->getModuleLink('referralprogram', 'program', [], true)|escape:'html':'UTF-8'}" class="std">
        <table class="table table-bordered">
          <thead>
          <tr>
            <th>&nbsp;</th>
            <th>{l s='Last name' mod='referralprogram'}</th>
            <th>{l s='First name' mod='referralprogram'}</th>
            <th>{l s='E-mail' mod='referralprogram'}</th>
          </tr>
          </thead>
          <tbody>
          {section name=friends start=0 loop=$nbFriends step=1}
            <tr>
              <td class="align_right">{$smarty.section.friends.iteration}</td>
              <td><input type="text" class="form-control" name="friendsLastName[{$smarty.section.friends.index}]" size="14" value="{if isset($smarty.post.friendsLastName[$smarty.section.friends.index])}{$smarty.post.friendsLastName[$smarty.section.friends.index]|escape:'html':'UTF-8'}{/if}" /></td>
              <td><input type="text" class="form-control" name="friendsFirstName[{$smarty.section.friends.index}]" size="14" value="{if isset($smarty.post.friendsFirstName[$smarty.section.friends.index])}{$smarty.post.friendsFirstName[$smarty.section.friends.index]|escape:'html':'UTF-8'}{/if}" /></td>
              <td><input type="text" class="form-control" name="friendsEmail[{$smarty.section.friends.index}]" size="20" value="{if isset($smarty.post.friendsEmail[$smarty.section.friends.index])}{$smarty.post.friendsEmail[$smarty.section.friends.index]|escape:'html':'UTF-8'}{/if}" /></td>
            </tr>
          {/section}
          </tbody>
        </table>
        <p class="bold">
          <strong>{l s='Important: Your friends\' e-mail addresses will only be used in the referral program. They will never be used for other purposes.' mod='referralprogram'}</strong>
        </p>
        <div class="checkbox">
          <label for="conditionsValided">
            <input type="checkbox" name="conditionsValided" id="conditionsValided" value="1" {if isset($smarty.post.conditionsValided) AND $smarty.post.conditionsValided eq 1}checked="checked"{/if} />
            {l s='I agree to the terms of service and adhere to them unconditionally.' mod='referralprogram'}
          </label>
          <a href="{$link->getModuleLink('referralprogram', 'rules', ['height' => '500', 'width' => '400'], true)|escape:'html':'UTF-8'}" class="thickbox" title="{l s='Conditions of the referral program' mod='referralprogram'}" rel="nofollow">{l s='Read conditions.' mod='referralprogram'}</a>
        </div>
        <p class="see_email">
          {l s='Preview' mod='referralprogram'}
          {assign var="file" value="{$lang_iso}/referralprogram-invitation.html"}
          <a href="{$link->getModuleLink('referralprogram', 'email', ['height' => '500', 'width' => '600', 'mail' => {$file}], true)|escape:'html':'UTF-8'}" class="thickbox" title="{l s='Invitation e-mail' mod='referralprogram'}" rel="nofollow">{l s='the default e-mail' mod='referralprogram'}</a> {l s='that will be sent to your friend(s).' mod='referralprogram'}
        </p>
        <div class="submit">
          <button type="submit" id="submitSponsorFriends" name="submitSponsorFriends" class="btn btn-lg btn-success"><span>{l s='Validate' mod='referralprogram'} <i class="icon icon-chevron-right"></i></span></button>
        </div>
      </form>
    {else}
      <div class="alert alert-warning">
        {l s='To become a sponsor, you need to have completed at least' mod='referralprogram'} {$orderQuantity} {if $orderQuantity > 1}{l s='orders' mod='referralprogram'}{else}{l s='order' mod='referralprogram'}{/if}.
      </div>
    {/if}
  </div>

  <div id="idTab2" class="tab-pane">
    {if $pendingFriends AND $pendingFriends|@count > 0}
      <p>
        {l s='These friends have not yet placed an order on this Website since you sponsored them, but you can try again! To do so, mark the checkboxes of the friend(s) you want to remind, then click on the button "Remind my friend(s)"' mod='referralprogram'}
      </p>
      <form method="post" action="{$link->getModuleLink('referralprogram', 'program', [], true)|escape:'html':'UTF-8'}" class="std">
        <table class="table table-bordered">
          <thead>
          <tr>
            <th>&nbsp;</th>
            <th>{l s='Last name' mod='referralprogram'}</th>
            <th>{l s='First name' mod='referralprogram'}</th>
            <th>{l s='E-mail' mod='referralprogram'}</th>
            <th><b>{l s='Last invitation' mod='referralprogram'}</b></th>
          </tr>
          </thead>
          <tbody>
          {foreach from=$pendingFriends item=pendingFriend name=myLoop}
            <tr>
              <td>
                <input type="checkbox" name="friendChecked[{$pendingFriend.id_referralprogram}]" id="friendChecked[{$pendingFriend.id_referralprogram}]" value="1" />
              </td>
              <td>
                <label for="friendChecked[{$pendingFriend.id_referralprogram}]">{$pendingFriend.lastname|substr:0:22}</label>
              </td>
              <td>{$pendingFriend.firstname|substr:0:22}</td>
              <td>{$pendingFriend.email}</td>
              <td>{dateFormat date=$pendingFriend.date_upd full=1}</td>
            </tr>
          {/foreach}
          </tbody>
        </table>
        <div class="submit">
          <button type="submit" name="revive" id="revive" class="btn btn-primary">{l s='Remind my friend(s)' mod='referralprogram'}</button>
        </div>
      </form>
    {else}
      <div class="alert alert-warning">
        {if $subscribeFriends AND $subscribeFriends|@count > 0}
          {l s='You have no pending invitations.' mod='referralprogram'}
        {else}
          {l s='You have not sponsored any friends yet.' mod='referralprogram'}
        {/if}
      </div>
    {/if}
  </div>

  <div id="idTab3" class="tab-pane">
    {if $subscribeFriends AND $subscribeFriends|@count > 0}
      <p>
        {l s='Here are sponsored friends who have accepted your invitation:' mod='referralprogram'}
      </p>
      <table class="table table-bordered">
        <thead>
        <tr>
          <th>&nbsp;</th>
          <th>{l s='Last name' mod='referralprogram'}</th>
          <th>{l s='First name' mod='referralprogram'}</th>
          <th>{l s='E-mail' mod='referralprogram'}</th>
          <th>{l s='Inscription date' mod='referralprogram'}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$subscribeFriends item=subscribeFriend name=myLoop}
          <tr>
            <td>{$smarty.foreach.myLoop.iteration}.</td>
            <td>{$subscribeFriend.lastname|substr:0:22}</td>
            <td>{$subscribeFriend.firstname|substr:0:22}</td>
            <td>{$subscribeFriend.email}</td>
            <td>{dateFormat date=$subscribeFriend.date_upd full=1}</td>
          </tr>
        {/foreach}
        </tbody>
      </table>
    {else}
      <div class="alert alert-warning">
        {l s='No sponsored friends have accepted your invitation yet.' mod='referralprogram'}
      </div>
    {/if}
  </div>
</div>

<nav>
  <ul class="pager">
    <li class="previous">
      <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">&larr; {l s='Back to your account' mod='referralprogram'}</a>
    </li>
  </ul>
</nav>

{addJsDefL name=ThickboxI18nClose}{l s='Close' mod='referralprogram' js=1}{/addJsDefL}
{addJsDefL name=ThickboxI18nOrEscKey}{l s='or Esc key' mod='referralprogram' js=1}{/addJsDefL}
{addJsDef tb_pathToImage=$img_ps_dir|cat:'loadingAnimation.gif'}
