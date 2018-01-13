{*
* 2017 thirty bees
*
* thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
* Copyright (C) 2017-2018 thirty bees
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@thirtybees.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to https://www.thirtybees.com for more information.
*
* @author    thirty bees <contact@thirtybees.com>
* @copyright 2017-2018 thirty bees
* @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  PrestaShop is an internationally registered trademark & property of PrestaShop SA
*}

{foreach $languages as $language}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-link"></i> {l s='Duplicate URLs'} - {$language['name']|escape:'htmlall':'UTF-8'}
        </div>
        {assign var=duplicates value=$duplicates_languages[$language['id_lang']]}
        {if count($duplicates)}
            <div class="row table-responsive clearfix ">
                <div>
                    <table class="table">
                        <thead>
                        <tr>
                            <th style="width:10%">
                                <span class="title_box">{l s='Type'}</span>
                            </th>
                            <th style="width:10%">
                                <span class="title_box">{l s='ID'}</span>
                            </th>
                            <th style="width:10%">
                                <span class="title_box">{l s='Edit'}</span>
                            </th>
                            <th style="width:10%">
                                <span class="title_box">{l s='Type'}</span>
                            </th>
                            <th style="width:10%">
                                <span class="title_box">{l s='ID'}</span>
                            </th>
                            <th style="width:10%">
                                <span class="title_box">{l s='Edit'}</span>
                            </th>
                            <th style="width:40%">
                                <span class="title_box">{l s='URL'}</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $duplicates as $duplicate}
                            <tr>
                                <td>
                                    <span>{$duplicate['a_type']|escape:'htmlall':'UTF-8'}</span>
                                </td>
                                <td>
                                    <span>{$duplicate['a_id']|escape:'htmlall':'UTF-8'}</span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{$duplicate['a_view']|escape:'htmlall':'UTF-8'}"
                                           class="btn btn-default"
                                           title="{l s='Edit'}">
                                            <i class="icon-pencil"></i> {l s='Edit'}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span>{$duplicate['b_type']|escape:'htmlall':'UTF-8'}</span>
                                </td>
                                <td>
                                    <span>{$duplicate['b_id']|escape:'htmlall':'UTF-8'}</span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{$duplicate['b_view']|escape:'htmlall':'UTF-8'}"
                                           class="btn btn-default"
                                           title="{l s='Edit'}">
                                            <i class="icon-pencil"></i> {l s='Edit'}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <a href="{$duplicate['a_url']|escape:'htmlall':'UTF-8'}">{$duplicate['a_url']|escape:'htmlall':'UTF-8'}</a>
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        {else}
            <h2>{l s='No duplicates found. Good job!'}</h2>
        {/if}
    </div>
{/foreach}
