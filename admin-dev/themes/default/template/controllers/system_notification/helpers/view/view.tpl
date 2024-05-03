{*
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
*}

{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
    <div id="container-notification">
        <div class="row">
            <div class="panel clearfix">
                <div class="panel-heading">
                    <h4>{$notification->title}</h4>
                </div>

                <div class="panel-body">
                    <p>{$notification->message}</p>
                </div>
            </div>
        </div>
    </div>
{/block}
