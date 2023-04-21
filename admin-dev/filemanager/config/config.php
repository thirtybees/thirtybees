<?php

/** @noinspection PhpUnhandledExceptionInspection */

session_start();

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', dirname(__FILE__).'/../../');
}

require_once(_PS_ADMIN_DIR_.'/../config/config.inc.php');
require_once(_PS_ADMIN_DIR_.'/init.php');
require_once(__DIR__ . '/../include/utils.php');

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// check access
$employee = Context::getContext()->employee;
if (!$employee->hasAccess(AdminProductsController::class, Profile::PERMISSION_EDIT)) {
    throw new PrestaShopException(Tools::displayError("Access denied"));
}
if (!$employee->hasAccess(AdminCmsContentController::class, Profile::PERMISSION_EDIT)) {
    throw new PrestaShopException(Tools::displayError("Access denied"));
}
//------------------------------------------------------------------------------
// DON'T COPY THIS VARIABLES IN FOLDERS config.php FILES
//------------------------------------------------------------------------------

//**********************
//Path configuration
//**********************
// In this configuration the folder tree is
// root
//    |- source <- upload folder
//    |- thumbs <- thumbnail folder [must have write permission (755)]
//    |- filemanager
//    |- js
//    |   |- tinymce
//    |   |   |- plugins
//    |   |   |   |- responsivefilemanager
//    |   |   |   |   |- plugin.min.js


$base_url = Tools::getHttpHost(true);  // DON'T TOUCH (base url (only domain) of site (without final /)).
$base_url = Configuration::get('PS_SSL_ENABLED') ? $base_url : str_replace('https', 'http', $base_url);
$upload_dir = Context::getContext()->shop->getBaseURI().'img/cms/'; // path from base_url to base of upload folder (with start and final /)
$current_path = _PS_ROOT_DIR_.'/img/cms/'; // relative path from filemanager folder to upload folder (with final /)
//thumbs folder can't put inside upload folder
$thumbs_base_path = _PS_ROOT_DIR_.'/img/tmp/cms/'; // relative path from filemanager folder to thumbs folder (with final /)

//--------------------------------------------------------------------------------------------------------
// YOU CAN COPY AND CHANGE THESE VARIABLES INTO FOLDERS config.php FILES TO CUSTOMIZE EACH FOLDER OPTIONS
//--------------------------------------------------------------------------------------------------------

$MaxSizeUpload=100; //Mb

$default_language="en"; //default language file name
$icon_theme="ico"; //ico or ico_dark you can cusatomize just putting a folder inside filemanager/img
$show_folder_size=true; //Show or not show folder size in list view feature in filemanager (is possible, if there is a large folder, to greatly increase the calculations)
$show_sorting_bar=true; //Show or not show sorting feature in filemanager
$loading_bar=true; //Show or not show loading bar
$transliteration=false; //active or deactive the transliteration (mean convert all strange characters in A..Za..z0..9 characters)

//******************
// Default layout setting
//
// 0 => boxes
// 1 => detailed list (1 column)
// 2 => columns list (multiple columns depending on the width of the page)
// YOU CAN ALSO PASS THIS PARAMETERS USING SESSION VAR => $_SESSION["VIEW"]=
//
//******************
$default_view=0;

//set if the filename is truncated when overflow first row
$ellipsis_title_after_first_row=true;

//*************************
//Permissions configuration
//******************
$delete_files=true;
$create_folders=true;
$delete_folders=true;
$upload_files=true;
$rename_files=true;
$rename_folders=true;
$duplicate_files=true;

/**
 *
 * Allowed mime types
 *
 * All file extensions should match .htaccess rule in /img/cms/.htaccess
 */
$allowedMineTypes = [
    'image/jpeg' => [
        'extensions' => ['jpg', 'jpeg'],
        'category' => 'image'
    ],
    'image/png' => [
        'extensions' => ['png'],
        'category' => 'image'
    ],
    'image/gif' => [
        'extensions' => ['gif'],
        'category' => 'image'
    ],
    'image/bmp' => [
        'extensions' => ['bmp'],
        'category' => 'image'
    ],
    'image/tiff' => [
        'extensions' => ['tiff'],
        'category' => 'image'
    ],
    'image/svg' => [
        'extensions' => ['svg'],
        'category' => 'image'
    ],
    'image/webp' => [
        'extensions' => ['webp'],
        'category' => 'image'
    ],
    'application/pdf' => [
        'extensions' => ['pdf'],
        'category' => 'file'
    ],
    'video/mpeg' => [
        'extensions' => ['mpeg', 'mpg', 'mov'],
        'category' => 'video'
    ],
    'video/mp4' => [
        'extensions' => ['mp4'],
        'category' => 'video'
    ],
    'video/x-msvideo' => [
        'extensions' => ['avi'],
        'category' => 'video'
    ],
    'audio/x-ms-wma' => [
        'extensions' => ['wma'],
        'category' => 'video'
    ],
    'video/x-flv' => [
        'extensions' => ['flv'],
        'category' => 'video'
    ],
    'video/webm' => [
        'extensions' => ['webm'],
        'category' => 'video'
    ],
];

//**********************
//Allowed extensions (lowercase insert)
//**********************
$ext_img = getMimeTypeFileExtensions('image', $allowedMineTypes);
$ext_file = getMimeTypeFileExtensions('file', $allowedMineTypes);
$ext_video = getMimeTypeFileExtensions('video', $allowedMineTypes);
$ext_music = getMimeTypeFileExtensions('audio', $allowedMineTypes);
$ext_misc = getMimeTypeFileExtensions('misc', $allowedMineTypes);

$ext=array_merge($ext_img, $ext_file, $ext_misc, $ext_video, $ext_music); //allowed extensions

//The filter and sorter are managed through both javascript and php scripts because if you have a lot of
//file in a folder the javascript script can't sort all or filter all, so the filemanager switch to php script.
//The plugin automatic swich javascript to php when the current folder exceeds the below limit of files number
$file_number_limit_js=500;

//**********************
// Hidden files and folders
//**********************
// set the names of any folders you want hidden (eg "hidden_folder1", "hidden_folder2" ) Remember all folders with these names will be hidden (you can set any exceptions in config.php files on folders)
$hidden_folders = [];
// set the names of any files you want hidden. Remember these names will be hidden in all folders (eg "this_document.pdf", "that_image.jpg" )
$hidden_files = ['config.php'];
