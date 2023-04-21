<?php
include('config/config.php');
if ($_SESSION['verify'] != 'RESPONSIVEfilemanager') {
    die('forbiden');
}

$_POST['path_thumb'] = $thumbs_base_path.$_POST['path_thumb'];
if (!isset($_POST['path_thumb']) && trim($_POST['path_thumb']) == '') {
    die('wrong path');
}

$thumb_pos = strpos($_POST['path_thumb'], $thumbs_base_path);
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

$base = $current_path;

if (isset($_POST['path'])) {
    $path = $current_path.str_replace("\0", "", $_POST['path']);
} else {
    $path = $current_path;
}

$cycle = true;
$max_cycles = 50;
$i = 0;
while ($cycle && $i < $max_cycles) {
    $i++;
    if ($path == $base) {
        $cycle = false;
    }

    if (file_exists($path.'config.php')) {
        require_once($path.'config.php');
        $cycle = false;
    }
    $path = fix_dirname($path).'/';
    $cycle = false;
}

$path = $current_path.str_replace("\0", "", $_POST['path']);
$path_thumb = $_POST['path_thumb'];
if (isset($_POST['name'])) {
    $name = $_POST['name'];
    if (preg_match('/\.{1,2}[\/|\\\]/', $name) !== 0) {
        die('wrong name');
    }
}

$info = pathinfo($path);
if (isset($info['extension']) && !(isset($_GET['action']) && $_GET['action'] == 'delete_folder') && !in_array(strtolower($info['extension']), $ext)) {
    die('wrong extension');
}

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'delete_file':
            if ($delete_files) {
                unlink($path);
                if (file_exists($path_thumb)) {
                    unlink($path_thumb);
                }
            }
            break;
        case 'delete_folder':
            if ($delete_folders) {
                if (is_dir($path_thumb)) {
                    deleteDir($path_thumb);
                }
                if (is_dir($path)) {
                    deleteDir($path);
                }
            }
            break;
        case 'create_folder':
            if ($create_folders) {
                create_folder(fix_path($path, $transliteration), fix_path($path_thumb, $transliteration));
            }
            break;
        case 'rename_folder':
            if ($rename_folders) {
                $name = fix_filename($name, $transliteration);
                $name = str_replace('.', '', $name);

                if (!empty($name)) {
                    if (!rename_folder($path, $name, $transliteration)) {
                        die(lang_Rename_existing_folder);
                    }
                    rename_folder($path_thumb, $name, $transliteration);
                } else {
                    die(lang_Empty_name);
                }
            }
            break;
        case 'rename_file':
            if ($rename_files) {
                $name = fix_filename($name, $transliteration);
                if (!empty($name)) {
                    if (!rename_file($path, $name, $transliteration)) {
                        die(lang_Rename_existing_file);
                    }
                    rename_file($path_thumb, $name, $transliteration);
                } else {
                    die(lang_Empty_name);
                }
            }
            break;
        case 'duplicate_file':
            if ($duplicate_files) {
                $name = fix_filename($name, $transliteration);
                if (!empty($name)) {
                    if (!duplicate_file($path, $name)) {
                        die(lang_Rename_existing_file);
                    }
                    duplicate_file($path_thumb, $name);
                } else {
                    die(lang_Empty_name);
                }
            }
            break;
        default:
            die('wrong action');
    }
}
