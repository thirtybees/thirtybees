<?php
include('config/config.php');

$path_thumb = normalizePath(Tools::getValue('path_thumb', ''));
if (! $path_thumb) {
    die('wrong path');
}
$path_thumb = FILE_MANAGER_THUMB_BASE_DIR . $path_thumb;

$path = normalizePath(Tools::getValue('path', ''));
if (! $path) {
    die('wrong path');
}
$path = FILE_MANAGER_BASE_DIR . $path;

$name = Tools::getValue('name', '');
if (preg_match('/\.{1,2}[\/|\\\]/', $name) !== 0) {
    die('wrong name');
}

$action = Tools::getValue('action', '');
$info = pathinfo($path);
if (isset($info['extension']) && $action !== 'delete_folder' && !in_array(strtolower($info['extension']), getFileExtensions())) {
    die('wrong extension');
}

switch ($action) {
    case 'delete_file':
        if (file_exists($path)) {
            unlink($path);
        }
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
