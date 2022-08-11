<?php

/** @noinspection PhpUndefinedMethodInspection */

include('../config/config.inc.php');
header('content-type: application/x-javascript');

$jqueryFolder = dirname(__FILE__).'/jquery/';
$jqueryPluginsFolder = $jqueryFolder.'plugins/';

$mappings = [
    'ajaxfileupload.js' => $jqueryPluginsFolder . 'ajaxfileupload/jquery.ajaxfileupload.js',
    'jquery-colorpicker.js' => $jqueryPluginsFolder . 'jquery.colorpicker.js',
    'jquery.cluetip.js' => $jqueryPluginsFolder . 'cluetip/jquery.cluetip.js',
    'jquery-fieldselection.js' => $jqueryPluginsFolder . 'jquery.fieldselection.js',
    'jquery.dimensions.js' => $jqueryPluginsFolder . 'jquery.dimensions.js',
    'jquery.idTabs.modified.js' => $jqueryPluginsFolder . 'jquery.idTabs.js',
    'jquery.pngFix.pack.js' => $jqueryPluginsFolder . 'jquery.pngFix.js',
    'thickbox-modified.js' => $jqueryPluginsFolder . 'thickbox/jquery.thickbox.js',
    'excanvas.min.js' => $jqueryPluginsFolder . 'jquery.excanvas.js',
    'jquery-typewatch.pack.js' => $jqueryPluginsFolder . 'jquery.typewatch.js',
    'jquery.easing.1.3.js' => $jqueryPluginsFolder . 'jquery.easing.js',
    'jquery.scrollTo-1.4.2-min.js' => $jqueryPluginsFolder . 'jquery.scrollTo.js',
    'jqminmax-compressed.js' => $jqueryPluginsFolder . 'jquery.jqminmax.js',
    'jquery.fancybox-1.3.4.js' => $jqueryPluginsFolder . 'fancybox/jquery.fancybox.js',
    'jquery.serialScroll-1.2.2-min.js' => $jqueryPluginsFolder . 'jquery.serialScroll.js',
    'ifxtransfer.js' => $jqueryPluginsFolder . 'jquery.ifxtransfer.js',
    'jquery.autocomplete.js' => $jqueryPluginsFolder . 'autocomplete/jquery.autocomplete.js',
    'jquery.flot.min.js' => $jqueryPluginsFolder . 'jquery.flot.js',
    'jquery.tablednd_0_5.js' => $jqueryPluginsFolder . 'jquery.tablednd.js',
    'jquery.hoverIntent.minified.js' => $jqueryPluginsFolder . 'jquery.hoverIntent.js',
    'jquery-ui-1.8.10.custom.min.js' => $jqueryFolder . 'ui/jquery.ui.core.min.js',
    'jquery.treeview.async.js' => $jqueryPluginsFolder . 'treeview-categories/jquery.treeview-categories.async.js',
    'jquery.treeview.edit.js' => $jqueryPluginsFolder . 'treeview-categories/jquery.treeview-categories.edit.js',
    'jquery.treeview.js' => $jqueryPluginsFolder . 'treeview-categories/jquery.treeview-categories.js',
    'jquery.treeview.sortable.js' => $jqueryPluginsFolder . 'treeview-categories/jquery.treeview-categories.sortable.js',
    'tabpane.js' => $jqueryPluginsFolder . 'tabpane/jquery.tabpane.js',
    'admin-themes.js' => 'admin/themes.js',
    'admin-dashboard.js' => 'admin/dashboard.js',
    'admin-products.js' => 'admin/products.js',
    'adminImport.js' => 'admin/import.js',
    'admin_carrier_wizard.js' => 'admin/carrier_wizard.js',
    'admin_order.js' => 'admin/orders.js',
    'attributesBack.js' => 'admin/attributes.js',
    'admin-scene-cropping.js' => 'admin/scenes.js',
    'admin-dnd.js' => 'admin/dnd.js',
    'login.js' => 'admin/login.js',
    'notifications.js' => 'admin/notifications.js',
    'price.js' => 'admin/price.js',
    'tinymce.inc.js' => 'admin/tinymce.inc.js',
];

$file = Tools::getValue('file');
if (!array_key_exists($file, $mappings)) {
    //check if file is a real prestashop native JS
    die('file_not_found');
}

$newFile = $mappings[$file];

// display deprecation message
$message = 'retro-compat.js.php: mapped legacy file name [' . $file . '] to [' . $newFile . ']';
if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    $message .= '. Referrer = ['.$referer.']';
}
trigger_error($message , E_USER_DEPRECATED);

echo file_get_contents($newFile);
exit;
