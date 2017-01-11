<section id="blockcontactinfos" class="col-xs-12 col-sm-3">
  <h4>{l s='Store Information' mod='blockcontactinfos'}</h4>
  <address>
    <ul class="list-unstyled">
      {if !empty($blockcontactinfos_company)}
        <li>
          <b>{$blockcontactinfos_company|escape:'html':'UTF-8'}</b>
        </li>
      {/if}
      {if !empty($blockcontactinfos_address)}
        <li>
          {$blockcontactinfos_address|escape:'html':'UTF-8'}
        </li>
      {/if}
      {if !empty($blockcontactinfos_phone)}
        <li>
          <i class="icon icon-phone"></i>
          <a href="{$blockcontactinfos_phone|escape:'html':'UTF-8'}">{$blockcontactinfos_phone|escape:'html':'UTF-8'}</a>
        </li>
      {/if}
      {if !empty($blockcontactinfos_email)}
        <li>
          <i class="icon icon-envelope-alt"></i>
          {mailto address=$blockcontactinfos_email|escape:'html':'UTF-8' encode="hex"}
        </li>
      {/if}
    </ul>
  </address>
</section>
