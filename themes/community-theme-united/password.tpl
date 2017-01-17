{capture name=path}<a href="{$link->getPageLink('authentication', true)|escape:'html':'UTF-8'}" title="{l s='Authentication'}" rel="nofollow">{l s='Authentication'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Forgot your password'}{/capture}
<div class="box">
  <h1 class="page-subheading">{l s='Forgot your password?'}</h1>

  {include file="$tpl_dir./errors.tpl"}

  {if isset($confirmation) && $confirmation == 1}
    <div class="alert alert-success">{l s='Your password has been successfully reset and a confirmation has been sent to your email address:'} {if isset($customer_email)}{$customer_email|escape:'html':'UTF-8'|stripslashes}{/if}</div>
  {elseif isset($confirmation) && $confirmation == 2}
    <div class="alert alert-success">{l s='A confirmation email has been sent to your address:'} {if isset($customer_email)}{$customer_email|escape:'html':'UTF-8'|stripslashes}{/if}</div>
  {else}
    <p>{l s='Please enter the email address you used to register. We will then send you a new password. '}</p>
    <form action="{$request_uri|escape:'html':'UTF-8'}" method="post" class="std" id="form_forgotpassword">
      <fieldset>
        <div class="form-group">
          <label for="email">{l s='Email address'}</label>
          <input class="form-control" type="email" id="email" name="email" value="{if isset($smarty.post.email)}{$smarty.post.email|escape:'html':'UTF-8'|stripslashes}{/if}" required>
        </div>
        <div class="submit">
          <button type="submit" class="btn btn-lg btn-success"><span>{l s='Retrieve Password'} <i class="icon icon-chevron-right"></i></span></button>
        </div>
      </fieldset>
    </form>
  {/if}
</div>

<nav>
  <ul class="pager">
    <li class="previous">
      <a href="{$link->getPageLink('authentication')|escape:'html':'UTF-8'}" title="{l s='Back to Login'}">&larr; {l s='Back to Login'}</a>
    </li>
  </ul>
</nav>
