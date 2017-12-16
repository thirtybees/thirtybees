<?php
/**
 * 2007-2016 PrestaShop
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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

header('content-type: text/css');
$css_folder = dirname(__FILE__).'/../js/jquery/';

$cssFiles = [
                'datepicker.css' =>
                    ['new_file' => $css_folder.'ui/themes/base/jquery.ui.datepicker.css'],
                'fileuploader.css' =>
                    ['new_file' => $css_folder.'plugins/ajaxfileupload/jquery.ajaxfileupload.css'],
                'jquery.autocomplete.css' =>
                    ['new_file' => $css_folder.'plugins/autocomplete/jquery.autocomplete.css'],
                'jquery.cluetip.css' =>
                    ['new_file' => $css_folder.'plugins/cluetip/jquery.cluetip.css'],
                'jquery.fancybox-1.3.4.css' =>
                    ['new_file' => $css_folder.'plugins/fancybox/jquery.fancybox.css'],
                'jquery.jgrowl.css'=>
                    ['new_file' => $css_folder.'plugins/jgrowl/jquery.jgrowl.css'],
                'jquery.treeview.css' =>
                    ['new_file' => $css_folder.'plugins/treeview-categories/jquery.treeview-categories.css'],
                'jqzoom.css' =>
                    ['new_file' => $css_folder.'plugins/jqzoom/jquery.jqzoom.css'],
                'tabpane.css' =>
                    ['new_file' => $css_folder.'plugins/tabpane/jquery.tabpane.css'],
                'thickbox.css' =>
                    ['new_file' => $css_folder.'plugins/thickbox/jquery.thickbox.css'],
                'jquery.fancybox.css' =>
                    ['new_file' => $css_folder.'plugins/fancybox/jquery.fancybox.css'],
                ];
                
                
                

$file = $_GET['file'];

if (!array_key_exists($file, $cssFiles)) { //check if file is a real prestashop native CSS
    die('file_not_found');
} else {
    $html = file_get_contents($cssFiles[$file]['new_file']);
}

if ($file == 'datepicker.css') {
    $html = file_get_contents($css_folder.'ui/themes/base/jquery.ui.theme.css');
    $html .= file_get_contents($css_folder.'ui/themes/base/jquery.ui.datepicker.css');
    $html = str_replace('url(images', 'url(../ui/themes/base/images', $html);
}
echo $html ;
