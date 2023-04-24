<?php
/** @noinspection PhpUnhandledExceptionInspection */

include('config/config.php');


$storeFolder = rtrim(FILE_MANAGER_BASE_DIR . normalizePath(Tools::getValue('path', '')), '/') . '/';
$storeFolderThumb = rtrim(FILE_MANAGER_THUMB_BASE_DIR . normalizePath(Tools::getValue('path_thumb', '')), '/') . '/';

if (!empty($_FILES) && isset($_FILES['file']) && $_FILES['file']['tmp_name']) {
    $info = pathinfo($_FILES['file']['name']);
    $tempFile = $_FILES['file']['tmp_name'];
    $fileExtension = fix_strtolower($info['extension'] ?? '');
    $mimeType = mime_content_type($tempFile);
    if (canUploadFile($mimeType, $fileExtension)) {

        $targetPath = $storeFolder;
        $targetPathThumb = $storeFolderThumb;
        $_FILES['file']['name'] = fix_filename($_FILES['file']['name']);

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

        if (in_array($fileExtension,getFileExtensions('image')) && @getimagesize($tempFile) != false) {
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

if (Tools::isSubmit('submit')) {
    $query = http_build_query([
        'type' => Tools::getValue('type', ''),
        'lang' => Tools::getValue('lang', ''),
        'popup' => Tools::getValue('popup', ''),
        'fldr' => Tools::getValue('fldr', ''),
    ]);
    header('location: dialog.php?'.$query);
}
