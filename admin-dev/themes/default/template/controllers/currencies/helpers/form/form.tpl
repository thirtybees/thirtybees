{**
 * Copyright (C) 2023 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2023 thirty bees
 * @license   Open Software License (OSL 3.0)
 *}
{extends file="helpers/form/form.tpl"}

{block name="script"}
    $(document).ready(function() {
        $('#decimals_on').click(function() {
            $('#decimal_places').removeAttr('disabled');
        });

        $('#decimals_off').click(function() {
            $('#decimal_places').attr('disabled', 'disabled');
        });
    });
{/block}
