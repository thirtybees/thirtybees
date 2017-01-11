<section id="blockcategories-footer" class="blockcategories col-xs-12 col-sm-3">
  <h4>{l s='Categories' mod='blockcategories'}</h4>
  <ul class="list-unstyled">
    {foreach from=$blockCategTree.children item=child}
      {include file='./footer_list_item.tpl' item=$child}
    {/foreach}
  </ul>
</section>
