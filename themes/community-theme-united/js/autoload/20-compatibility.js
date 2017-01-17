/**
 * This file contains compatibility code
 * for code and functions that were removed from this theme.
 *
 * If you are developing a custom shop with this theme,
 * you may remove this file if you can take care of any
 * theme compatibility issues with custom modules.
 */

$(function() {
  // We added .form-inline in this theme, but some modules may be using these templates and not have this class applied
  $('.sortPagiBar, .top-pagination-content, .bottom-pagination-content').addClass('form-inline');
});

function bindUniform() {}

// Used by blocklanguages and blockcurrencies in the default template
function dropDown() {}

function accordionFooter() {}

function accordion(s) {}

function openBranch($, n) {}
function closeBranch($, n) {}
function toggleBranch($, n) {}
function blockHover(s) {}

var serialScrollNbImagesDisplayed;
function serialScrollSetNbImages() {}
function serialScrollFixLock() {}
