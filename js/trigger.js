/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */
window.addEventListener("load", function() {
  if (window.triggerToken && window.triggerUrl) {
    var request = new XMLHttpRequest();
    request.open("POST", window.triggerUrl, true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.send("ajax=1&secret="+window.triggerToken);
  }
});
