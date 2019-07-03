<style>
    {literal}

    .addons-catalog {
        font-family: "Open Sans", Arial, sans-serif;
    }

    .addons-catalog .margin-bottom {
        margin-bottom: 35px
    }

    .addons-catalog .margin-bottom:last-child {
        margin-bottom: 0
    }

    .addons-catalog .margin-bottom.margin-bottom-h1 {
        margin-bottom: 25px
    }

    .addons-catalog h1 {
        font-family: "Open Sans", Arial, sans-serif;
        font-size: 25px;
        font-weight: bold;
        margin-top: 0
    }

    /*** Module ***/

    .addons-block-title {
        position: relative;
        display: flex;
        align-items: center;
        padding-left: 250px;
        margin-bottom: 0 !important;
        font-size: 14px;
        line-height: 18px;
        min-height: 43px;
        width: 90%;
    }

    .addons-block-title img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        max-width: 218px;
    }

    .addons-search-bar .input-group {
        width: 100%;
        position: relative;
    }

    .addons-search-bar .input-group input {
        border: 1px solid #adc2c8 !important;
        background-color: #fafbfc;
        height: 40px;
        padding-left: 15px;
        font-size: 14px;
    }

    .addons-search-bar .input-group i {
        font-size: 1.7em;
        position: absolute;
        right: 10px;
        top: 8px;
        z-index: 10;
        color: #6c868e;
    }

    .addons-search-bar .input-group input {
        border-radius: 3px !important
    }

    .addons-search-bar .input-group i:hover {
        cursor: pointer
    }

    .addons-search-bar #addons-search-btn {
        width: 8%
    }

    #addons-search-results {
        width: 100%
    }

    #addons-search-results .search-option {
        border-bottom: 1px solid #BBCDD4;
        margin: 0 10px;
        cursor: pointer;
    }

    #addons-search-results .search-option:last-child {
        border-bottom: none
    }

    #addons-search-results .addons-product-list {
        position: relative;
        padding-left: 40px;
        font-size: 14px;
        margin: 10px 0
    }

    #addons-search-results .addons-product-list.no-img {
        padding-left: 10px;
    }

    #addons-search-results .addons-product-list img {
        position: absolute;
        left: 0;
        top: 3px;
        width: 100%;
        max-width: 28px;
    }

    #addons-search-results .addons-product-list span {
        display: block;
        line-height: 17px
    }

    #addons-search-results .addons-product-list span.addons-product-list-name {
        font-weight: bold
    }


    .addons-module-block h2,
    .addons-theme-block h2 {
        line-height: 20px;
        font-family: "Open Sans", Arial, sans-serif;
        font-size: 16px;
        margin: 0 0 25px;
        font-weight: bold;
    }

    .addons-module-block h2 span,
    .addons-theme-block h2 span {
        display: block;
        font-weight: bold;
        color: #6c868e
    }

    .addons-module-block .link-all-selection,
    .addons-theme-block .link-all-selection {
        margin-top: 22px;
        font-weight: bold;
        font-size: 14px;
    }

    .addons-module-block .link-all-selection i,
    .addons-theme-block .link-all-selection i {
        position: relative;
        left: 3px;
        top: 1px;
    }

    .addons-module-block .addons-module-product {
        padding: 1.25rem;
    }

    .addons-module-block .addons-on-sale-product {
        position: absolute;
        top: 45px;
        right: 0;
        margin: 0;
        padding: 0 5px;
        background: #df0067;
        color: #fff;
    }

    .addons-module-block .addons-new-product {
        position: absolute;
        top: 45px;
        right: 0;
        margin: 0;
        padding: 0 5px;
        background: #25B9D7;
        color: #ffffff;
    }

    .addons-module-block .addons-module-product-header {
        min-height: 50px;
        position: relative;
    }

    .addons-module-block .addons-module-product-header .addons-module-product-name {
        margin: 0 37px 0 57px !important;
        padding: 0 !important;
        font-weight: bold;
        color: #363a41;
        border: none !important;
        height: inherit !important;
        line-height: 17px !important;
        font-family: "Open Sans", Arial, sans-serif;
        text-transform: inherit !important
    }

    .addons-module-block .addons-module-product-header .addons-module-product-author {
        font-weight: bold;
        margin: 0 57px
    }

    .addons-module-block .addons-module-product-header .addons-module-product-author span {
        font-weight: 400;
        color: #6c868e
    }

    .addons-module-block .addons-module-product-header .addons-module-product-logo {
        border-radius: 0.25rem;
        float: left;
        height: 45px;
        vertical-align: 0;
        width: 45px;
    }

    .addons-module-block .addons-module-product-description {
        margin: 15px 0
    }

    .addons-module-block .addons-module-product-description .addons-description-product {
        min-height: 60px;
        overflow: hidden;
        font-size: 13px;
        color: #363a41;
        line-height: 16px
    }

    .addons-module-block .addons-module-product-footer .addons-module-star-ranking span,
    .addons-module-block .addons-module-product .addons-marketplace {
        color: #6C868E;
        font-size: 10px;
    }

    .addons-module-block .addons-module-product-footer .addons-price-product {
        font-size: 14px;
        padding-top: 2px;
        font-weight: bold;
        color: #363a41;
    }

    .addons-module-block .addons-module-product .addons-marketplace span {
        font-weight: bold
    }

    .addons-bxslider-block.panel {
        padding: 5px 0 !important
    }

    .addons-bxslider-block .addons-bxslider {
        width: 100%;
        max-width: 1024px;
        margin: 0 auto;
    }

    .addons-module-selection-ps p {
        font-size: 13px;
        position: relative;
        padding-left: 185px;
        min-height: 125px;
        margin-bottom: 0
    }

    .addons-module-selection-ps img {
        position: absolute;
        left: 0;
        width: 100%;
        max-width: 175px;
    }

    .addons-module-selection-ps span {
        font-size: 16px;
        font-weight: 600;
        color: #363a41;
        display: block;
        padding-bottom: 15px
    }

    .addons-module-selection-ps a.link-all-selection {
        display: block;
        margin-top: 5px
    }

    .addons-all-modules .addons-content-discover p,
    .addons-all-modules .addons-content-discover a {
        display: inline-block;
    }

    .addons-all-modules .addons-content-discover p {
        margin-bottom: 0;
        padding: 0.5em 0;
        font-size: 14px;
        max-width: 75%
    }

    /*** Themes ***/

    .addons-catalog-theme {
        margin-bottom: 40px
    }

    .addons-catalog-theme .addons-block-title {
        padding-left: 300px;
    }

    .addons-catalog-theme .addons-theme-screenshot img {
        width: 100%
    }

    .addons-catalog-theme .addons-theme-product-link:hover {
        text-decoration: none;
    }

    .addons-catalog-theme .addons-theme-product-link:hover > .addons-theme-product .addons-theme-discover {
        text-decoration: underline
    }

    .addons-catalog-theme .addons-theme-product.panel {
        padding: 7px
    }

    .addons-catalog-theme .addons-theme-product-link .addons-theme-product {
        -webkit-transition: all 0.2s ease;
        -moz-transition: all 0.2s ease;
        -o-transition: all 0.2s ease;
        transition: all 0.2s ease;
        box-shadow: 0 0 0 transparent;
        border: 1px solid #fff
    }

    .addons-catalog-theme .addons-theme-product-link:hover > .addons-theme-product {
        box-shadow: 0px 0px 4px rgba(0, 0, 0, 0.3);
        border: 1px solid #BBCDD4
    }

    .addons-catalog-theme .addons-theme-footer-container {
        margin-top: 15px;
        min-height: 40px
    }

    .addons-catalog-theme .addons-theme-footer-container .addons-price-product span {
        color: #363a41;
        font-weight: 400
    }

    .addons-catalog-theme .addons-theme-footer-container p {
        font-weight: bold;
        font-size: 14px;
        color: #363A41;
        margin-bottom: 5px;
        line-height: 17px
    }

    .addons-catalog-theme .addons-theme-footer-container p.addons-theme-discover {
        font-size: 12px;
        color: #3ED2F0;
    }

    .addons-catalog-theme .addons-new-product {
        position: absolute;
        top: 15px;
        right: 0;
        margin: 0;
        padding: 2px 5px 3px;
        background: #25B9D7;
        color: #ffffff;
    }

    .addons-catalog-theme .addons-style-block {
        -webkit-transition: all 0.2s ease;
        -moz-transition: all 0.2s ease;
        -o-transition: all 0.2s ease;
        transition: all 0.2s ease;
        box-shadow: 0 0 0 transparent;
        border: 1px solid transparent
    }

    .addons-catalog-theme .link-addons-style:hover > .addons-style-block {
        box-shadow: 0px 0px 4px rgba(0, 0, 0, 0.3);
        border: 1px solid #BBCDD4
    }

    .addons-catalog-theme .link-addons-style:hover,
    .addons-catalog-theme .link-addons-style:hover > .addons-style-block p.panel {
        text-decoration: none
    }

    .addons-catalog-theme .addons-style-block p.panel {
        border-top: none !important;
        border-radius: 0 !important;
        font-weight: bold;
        padding: 10px !important;
        text-align: center;
        color: #363A41;
        margin-bottom: 0 !important
    }

    .addons-catalog-theme .addons-style-block img {
        width: 100%
    }

    .addons-theme-block.addons-theme-search .addons-block-title {
        padding-left: 0;
        text-align: right;
    }

    .addons-theme-block.addons-theme-search .addons-search-bar {
        padding-top: 0
    }

    .addons-bottom-img {
        width: 100%;
        max-width: 1279px;
        display: block;
        margin: 15px auto auto;
    }

    /**** Media Queries ****/

    @media screen and (min-width: 1199px) and (max-width: 1334px) {
        .addons-module-block .addons-module-product-header {
            min-height: 70px;
        }

        .addons-module-block .addons-module-product-description .addons-description-product {
            min-height: 96px;
        }
    }

    @media screen and (max-width: 1199px) {
        .addons-module-selection-ps .panel {
            margin-bottom: 15px
        }

        .addons-catalog-theme .addons-block-title {
            padding-left: 235px;
        }

        .addons-catalog-theme .addons-theme-one {
            margin-bottom: 15px
        }

        .addons-catalog-theme .addons-theme-search .addons-block-title {
            text-align: center;
        }
    }

    @media screen and (max-width: 1024px) {
        .addons-block-title {
            padding-left: 0;
            display: block
        }

        .addons-block-title img {
            position: inherit;
            display: block;
            margin-bottom: 15px
        }

        .addons-catalog-theme .addons-block-title {
            padding-left: 0;
            margin-bottom: 15px
        }

        .addons-catalog-theme .addons-style-block {
            margin-bottom: 25px
        }

        .addons-catalog-theme .addons-theme-search .addons-block-title {
            margin-bottom: 10px;
            min-height: inherit;
        }
    }

    @media screen and (max-width: 840px) {
        .addons-module-block h2 {
            float: none !important;
        }

        .link-all-selection {
            float: none !important;
            text-align: left;
            margin: 0 0 10px;
            display: inline-block;
        }
    }

    @media screen and (max-width: 768px) {
        .addons-search-bar {
            padding-top: 22px;
        }

        .addons-theme-footer-container .pull-right {
            float: none !important;
        }

        .addons-theme-footer-container .pull-right .addons-theme-discover {
            margin-top: 10px
        }
    }

    {/literal}
</style>

<div class="panel addons-bxslider-block addons-bxslider clearfix">
    <ul class="addons-rslides rslides rslides1">
        <li id="rslides1_s0" class="rslides1_on" style="display: block; float: left; position: relative; opacity: 1; z-index: 2; transition: opacity 500ms ease-in-out 0s;">
            <a href="{$addons_content.ad_top.url}" target="_blank" data-gaq="">
                <img src="{$addons_content.ad_top.img}" title="" alt="">
            </a>
        </li>
    </ul>
</div>

{foreach from=$addons_content.content item='group'}
    <div class="addons-module-block addons-module-traffic-block">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 clearfix">
                <h2 class="pull-left">
                    {$group.title} <span>{$group.tagline}</span>
                </h2>
                <a class="link-all-selection pull-right" href="{$group.categoryurl}" target="_blank" rel="noopener">
                    View all {$group.tab} <i class="icon-angle-right"></i>
                </a>
            </div>
        </div>
        <div class="row">
            {foreach from=$group.modules item='item'}
                <div class="col-lg-4 col-md-12 col-sm-12 pull-left">
                    <div class="addons-module-product panel clearfix">
                        <div class="addons-module-product-header">
                            <img class="addons-module-product-logo" src="{$item.icon}" width="45" height="45" alt="{$item.name}">
                            <h3 class="addons-module-product-name">
                                {$item.name}
                            </h3>
                            {if isset($item.author)}
                                <div class="addons-module-product-author">
                                    {$item.author}
                                </div>
                            {/if}
                        </div>
                        <div class="addons-module-product-description">
                            <div class="addons-description-product">
                                {$item.desc}
                            </div>
                        </div>
                        <div class="addons-module-product-footer clearfix">
                            <div class="addons-module-action pull-left">
                                {if isset($item.price)}
                                    <div class="addons-price-product">
                                        <span>{displayPrice price=$item.price}</span>
                                    </div>
                                {/if}
                            </div>
                            <div class="addons-module-action pull-right">
                                <a class="btn btn-primary" href="{$item.url}" target="_blank" rel="noopener" title="{$item.name}">
                                    Learn More
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
    <hr>
{/foreach}
