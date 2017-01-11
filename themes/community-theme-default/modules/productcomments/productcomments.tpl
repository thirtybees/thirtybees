<div id="product_comments_block_tab">
  {if !empty($comments)}

    {foreach from=$comments item=comment}
      {if !empty($comment.content)}
        <div class="comment row no-gutter" itemprop="review" itemscope itemtype="https://schema.org/Review">

          <meta itemprop="datePublished" content="{$comment.date_add|escape:'html':'UTF-8'|substr:0:10}" />
          <meta itemprop="worstRating" content = "0" />
          <meta itemprop="ratingValue" content = "{$comment.grade|escape:'html':'UTF-8'}" />
          <meta itemprop="bestRating" content = "5" />

          <div class="comment_author col-sm-3 col-md-2">
            <div class="form-group">
              <div><b>{l s='Grade' mod='productcomments'}</b></div>
              <div class="star_content clearfix"  itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                {section name="i" start=0 loop=5 step=1}
                  {if $comment.grade <= $smarty.section.i.index}
                    <div class="star"></div>
                  {else}
                    <div class="star star_on"></div>
                  {/if}
                {/section}
              </div>
            </div>
            <div class="form-group">
              <b itemprop="author">{$comment.customer_name|escape:'html':'UTF-8'}</b>
              <p>{dateFormat date=$comment.date_add|escape:'html':'UTF-8' full=0}</p>
            </div>
          </div>

          <div class="comment_details col-sm-9 col-md-10">
            <p>
              <b itemprop="name">{$comment.title}</b>
            </p>
            <p itemprop="reviewBody">{$comment.content|escape:'html':'UTF-8'|nl2br}</p>
            <ul class="list-unstyled">
              {if $comment.total_advice > 0}
                <li>
                  {l s='%1$d out of %2$d people found this review useful.' sprintf=[$comment.total_useful,$comment.total_advice] mod='productcomments'}
                </li>
              {/if}
              {if $is_logged}
                {if !$comment.customer_advice}
                  <li>
                    {l s='Was this comment useful to you?' mod='productcomments'}
                    <button class="usefulness_btn btn btn-xs btn-link" data-is-usefull="1" data-id-product-comment="{$comment.id_product_comment}">
                      <i class="icon icon-thumbs-up"></i> {l s='Yes' mod='productcomments'}
                    </button>
                    <button class="usefulness_btn btn btn-xs btn-link" data-is-usefull="0" data-id-product-comment="{$comment.id_product_comment}">
                      <i class="icon icon-thumbs-down"></i> {l s='No' mod='productcomments'}
                    </button>
                  </li>
                {/if}
                {if !$comment.customer_report}
                  <li>
                    <a href="#" class="report_btn btn btn-xs btn-link" data-id-product-comment="{$comment.id_product_comment}">
                      <i class="icon icon-flag"></i> {l s='Report abuse' mod='productcomments'}
                    </a>
                  </li>
                {/if}
              {/if}
            </ul>
          </div>

        </div>
      {/if}
    {/foreach}

    {if (!$too_early && ($is_logged || $allow_guests))}
      <div class="form-group">
        <a id="new_comment_tab_btn" class="btn btn-primary open-comment-form" href="#new_comment_form">
          {l s='Write your review!' mod='productcomments'}
        </a>
      </div>
    {/if}

  {else}
    {if (!$too_early && ($is_logged || $allow_guests))}
      <div class="form-group">
        <a id="new_comment_tab_btn" class="btn btn-primary open-comment-form" href="#new_comment_form">
          {l s='Be the first to write your review!' mod='productcomments'}
        </a>
      </div>
    {else}
      <div class="form-group">{l s='No customer reviews for the moment.' mod='productcomments'}</div>
    {/if}
  {/if}
</div>

<div style="display: none;">
  <div id="new_comment_form">
    <form id="id_new_comment_form" action="#">
      <h2 class="page-subheading">{l s='Write a review' mod='productcomments'}</h2>

      <div id="new_comment_form_error" class="alert alert-danger" style="display: none;">
        <ul></ul>
      </div>

      {if !empty($criterions)}
        {foreach from=$criterions item='criterion'}
          <div class="form-group clearfix">
            <label>{$criterion.name|escape:'html':'UTF-8'}:</label>
            <div class="form-control-static">
              <div class="star_content">
                <input class="star" type="radio" name="criterion[{$criterion.id_product_comment_criterion|round}]" value="1" />
                <input class="star" type="radio" name="criterion[{$criterion.id_product_comment_criterion|round}]" value="2" />
                <input class="star" type="radio" name="criterion[{$criterion.id_product_comment_criterion|round}]" value="3" />
                <input class="star" type="radio" name="criterion[{$criterion.id_product_comment_criterion|round}]" value="4"/>
                <input class="star" type="radio" name="criterion[{$criterion.id_product_comment_criterion|round}]" value="5" checked="checked"/>
              </div>
            </div>
          </div>
        {/foreach}
      {/if}

      <div class="form-group">
        <label for="comment_title">{l s='Title:' mod='productcomments'} <sup class="required">*</sup></label>
        <input id="comment_title" class="form-control" name="title" type="text" value="" required/>
      </div>

      <div class="form-group">
        <label for="content">{l s='Comment:' mod='productcomments'} <sup class="required">*</sup></label>
        <textarea id="content" class="form-control" name="content" required></textarea>
      </div>

      {if $allow_guests == true && !$is_logged}
        <div class="form-group">
          <label for="commentCustomerName">
            {l s='Your name:' mod='productcomments'} <sup class="required">*</sup>
          </label>
          <input id="commentCustomerName" class="form-control" name="customer_name" type="text" value="" required/>
        </div>
      {/if}

      <div id="new_comment_form_footer" class="clearfix">
        <input id="id_product_comment_send" name="id_product" type="hidden" value='{$id_product_comment_form}' />
        <p class="help-block">
          <sup>*</sup> {l s='Required fields' mod='productcomments'}
        </p>
        <button id="submitNewMessage" name="submitMessage" type="submit" class="btn btn-success">
          {l s='Submit' mod='productcomments'}
        </button>
        <a class="closefb btn btn-link" href="#">
          {l s='Cancel' mod='productcomments'}
        </a>
      </div>

    </form>
  </div>
</div>

{strip}
  {addJsDef productcomments_controller_url=$productcomments_controller_url|@addcslashes:'\''}
  {addJsDef moderation_active=$moderation_active|boolval}
  {addJsDef productcomments_url_rewrite=$productcomments_url_rewriting_activated|boolval}
  {addJsDef secure_key=$secure_key}

  {addJsDefL name=confirm_report_message}{l s='Are you sure that you want to report this comment?' mod='productcomments' js=1}{/addJsDefL}
  {addJsDefL name=productcomment_added}{l s='Your comment has been added!' mod='productcomments' js=1}{/addJsDefL}
  {addJsDefL name=productcomment_added_moderation}{l s='Your comment has been added and will be available once approved by a moderator.' mod='productcomments' js=1}{/addJsDefL}
  {addJsDefL name=productcomment_title}{l s='New comment' mod='productcomments' js=1}{/addJsDefL}
  {addJsDefL name=productcomment_ok}{l s='OK' mod='productcomments' js=1}{/addJsDefL}
{/strip}
