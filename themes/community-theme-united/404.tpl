<div class="pagenotfound jumbotron text-center">
  <h2>{l s='This page is not available'}</h2>
  <p>{l s='We\'re sorry, but the Web address you\'ve entered is no longer available.'}</p>
  <p>{l s='To find a product, please type its name in the field below.'}</p>
  <form action="{$link->getPageLink('search')|escape:'html':'UTF-8'}" method="post">
    <div>
      <label for="search_query">{l s='Search our product catalog:'}</label>
      <div class="input-group">
        <input id="search_query" name="search_query" type="text" class="form-control" />
        <span class="input-group-btn">
          <button type="submit" name="Submit" value="OK" class="btn btn-primary"><i class="icon icon-search"></i></button>
        </span>
      </div>
    </div>
  </form>
</div>

<nav>
  <ul class="pager">
    <li>
      <a href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl}{else}{$base_dir}{/if}" title="{l s='Home'}">&larr; {l s='Home page'}</a>
    </li>
  </ul>
</nav>
