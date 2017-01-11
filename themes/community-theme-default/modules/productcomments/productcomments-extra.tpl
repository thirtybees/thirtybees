{if (!$content_only && (($nbComments == 0 && $too_early == false && ($is_logged || $allow_guests)) || ($nbComments != 0)))}
  <div id="product_comments_block_extra" class="hidden-print" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
    {if $nbComments != 0}
      <div class="comments_note clearfix">
        <span>{l s='Rating' mod='productcomments'}&nbsp;</span>
        <div class="star_content clearfix">
          {section name="i" start=0 loop=5 step=1}
            {if $averageTotal <= $smarty.section.i.index}
              <div class="star"></div>
            {else}
              <div class="star star_on"></div>
            {/if}
          {/section}
          <meta itemprop="worstRating" content = "0" />
          <meta itemprop="ratingValue" content = "{if isset($ratings.avg)}{$ratings.avg|round:1|escape:'html':'UTF-8'}{else}{$averageTotal|round:1|escape:'html':'UTF-8'}{/if}" />
          <meta itemprop="bestRating" content = "5" />
        </div>
      </div>
    {/if}

    <ul class="list-unstyled comments_advices">
      {if $nbComments != 0}
        <li>
          <a href="#product-reviews" class="reviews">
            <i class="icon icon-fw icon-comment"></i> {l s='Read reviews' mod='productcomments'} (<span itemprop="reviewCount">{$nbComments}</span>)
          </a>
        </li>
      {/if}
      {if ($too_early == false && ($is_logged || $allow_guests))}
        <li>
          <a class="open-comment-form" href="#new_comment_form">
            <i class="icon icon-fw icon-pencil"></i> {l s='Write a review' mod='productcomments'}
          </a>
        </li>
      {/if}
    </ul>
  </div>
{/if}
