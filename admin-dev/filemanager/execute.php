<?php
include('config/config.php');

$_POST['path_thumb'] = FILE_MANAGER_THUMB_BASE_DIR.$_POST['path_thumb'];
if (!isset($_POST['path_thumb']) && trim($_POST['path_thumb']) == '') {
    die('wrong path');
}

$thumb_pos = strpos($_POST['path_thumb'], FILE_MANAGER_THUMB_BASE_DIR);
if ($thumb_pos === false
    || preg_match('/\.{1,2}[\/|\\\]/', $_POST['path_thumb']) !== 0
    || preg_match('/\.{1,2}[\/|\\\]/', $_POST['path']) !== 0
) {
    die('wrong path');
}

$language_file = 'lang/en.php';
if (isset($_GET['lang']) && $_GET['lang'] != 'undefined' && $_GET['lang'] != '') {
    $path_parts = pathinfo($_GET['lang']);
    if (is_readable('lang/'.$path_parts['basename'].'.php')) {
        $language_file = 'lang/'.$path_parts['basename'].'.php';
    }
}
require_once $language_file;

$base = FILE_MANAGER_BASE_DIR;

$path = FILE_MANAGER_BASE_DIR.str_replace("\0", "", $_POST['path']);
$path_thumb = $_POST['path_thumb'];
if (isset($_POST['name'])) {
    $name = $_POST['name'];
    if (preg_match('/\.{1,2}[\/|\\\]/', $name) !== 0) {
        die('wrong name');
    }
}

$info = pathinfo($path);
if (isset($info['extension']) && !(isset($_GET['action']) && $_GET['action'] == 'delete_folder') && !in_array(strtolower($info['extension']), getFileExtensions())) {
    die('wrong extension');
}

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'delete_file':
            unlink($path);
            if (file_exists($path_thumb)) {
                unlink($path_thumb);
            }
            break;
        case 'delete_folder':
            if (is_dir($path_thumb)) {
                deleteDir($path_thumb);
            }
            if (is_dir($path)) {
                deleteDir($path);
            }
            break;
        case 'create_folder':
            create_folder(fix_path($path), fix_path($path_thumb));
            break;
        case 'rename_folder':
            $name = fix_filename($name);
            $name = str_replace('.', '', $name);

            if (!empty($name)) {
                if (!rename_folder($path, $name)) {
                    die(lang_Rename_existing_folder);
                }
                rename_folder($path_thumb, $name);
            } else {
                die(lang_Empty_name);
            }
            break;
        case 'rename_file':
            $name = fix_filename($name);
            if (!empty($name)) {
                if (!rename_file($path, $name)) {
                    die(lang_Rename_existing_file);
                }
                rename_file($path_thumb, $name);
            } else {
                die(lang_Empty_name);
            }
            break;
        case 'duplicate_file':
            $name = fix_filename($name);
            if (!empty($name)) {
                if (!duplicate_file($path, $name)) {
                    die(lang_Rename_existing_file);
                }
                duplicate_file($path_thumb, $name);
            } else {
                die(lang_Empty_name);
            }
            break;
        default:
            die('wrong action');
    }
}
