<?php
/** @noinspection PhpUnhandledExceptionInspection */

include('config/config.php');

$_POST['path'] = $current_path.str_replace('\0', '', $_POST['path']);
$_POST['path_thumb'] = $thumbs_base_path.str_replace("\0", '', $_POST['path_thumb']);

$storeFolder = $_POST['path'];
$storeFolderThumb = $_POST['path_thumb'];

$path_pos = strpos($storeFolder, $current_path);
$thumb_pos = strpos($_POST['path_thumb'], $thumbs_base_path);

if ($path_pos === false || $thumb_pos === false
    || preg_match('/\.{1,2}[\/|\\\]/', $_POST['path_thumb']) !== 0
    || preg_match('/\.{1,2}[\/|\\\]/', $_POST['path']) !== 0) {
    die('wrong path');
}

if (!empty($_FILES) && isset($_FILES['file']) && $_FILES['file']['tmp_name']) {
    $info = pathinfo($_FILES['file']['name']);
    $tempFile = $_FILES['file']['tmp_name'];
    $fileExtension = fix_strtolower($info['extension'] ?? '');
    $mimeType = mime_content_type($tempFile);
    if (canUploadFile($mimeType, $fileExtension, $allowedMineTypes)) {

        $targetPath = $storeFolder;
        $targetPathThumb = $storeFolderThumb;
        $_FILES['file']['name'] = fix_filename($_FILES['file']['name'], $transliteration);

        $file_name_splitted = explode('.', $_FILES['file']['name']);
        array_pop($file_name_splitted);
        $_FILES['file']['name'] = implode('-', $file_name_splitted).'.'.$fileExtension;

        if (file_exists($targetPath.$_FILES['file']['name'])) {
            $i = 1;
            $info = pathinfo($_FILES['file']['name']);
            while (file_exists($targetPath.$info['filename'].'_'.$i.'.'.$fileExtension)) {
                $i++;
            }
            $_FILES['file']['name'] = $info['filename'].'_'.$i.'.'.$fileExtension;
        }
        $targetFile = $targetPath.$_FILES['file']['name'];
        $targetFileThumb = $targetPathThumb.$_FILES['file']['name'];

        if (in_array($fileExtension, $ext_img) && @getimagesize($tempFile) != false) {
            $is_img = true;
        } else {
            $is_img = false;
        }

        if ($is_img) {
            move_uploaded_file($tempFile, $targetFile);
            chmod($targetFile, 0755);
            create_img_gd($targetFile, $targetFileThumb, 122, 91);
        } else {
            move_uploaded_file($tempFile, $targetFile);
            chmod($targetFile, 0755);
        }
    } else {
        header('HTTP/1.1 406 file not permitted', true, 406);
        die(Tools::displayError('File type not permitted'));
    }
} else {
    header('HTTP/1.1 405 Bad Request', true, 405);
    if (isset($_FILES['file']['error']) && $_FILES['file']['error']) {
        die(Tools::decodeUploadError((int)$_FILES['file']['error']));
    } else {
        die(Tools::displayError('Failed to upload file'));
    }
}
if (isset($_POST['submit'])) {
    $query = http_build_query(
        [
            'type' => $_POST['type'],
            'lang' => $_POST['lang'],
            'popup' => $_POST['popup'],
            'field_id' => $_POST['field_id'],
            'fldr' => $_POST['fldr'],
        ]
    );
    header('location: dialog.php?'.$query);
}
