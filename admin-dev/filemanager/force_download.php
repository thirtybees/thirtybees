<?php
include('config/config.php');

$directory = normalizePath(Tools::getValue('path', ''));
$filename = Tools::getValue('name', '');
if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    die('wrong path');
}

$fullPath = rtrim(FILE_MANAGER_BASE_DIR . $directory, '/') . '/' . $filename;

// check extension
$info = pathinfo($filename);
$fileExtension = fix_strtolower($info['extension'] ?? '');
if (!in_array($fileExtension, getFileExtensions())) {
    die('wrong extension');
}

// check that file exists
if (!file_exists($fullPath)) {
    die('file does not exists');
}
if (! is_file($fullPath)) {
    die('not a file');
}

// check mime type
$mimeType = mime_content_type($fullPath);
if (! canUploadFile($mimeType, $fileExtension)) {
   die('unsupported mime type');
}

header('Pragma: private');
header('Cache-control: private, must-revalidate');
header('Content-Type: ' . $mimeType);
header('Content-Length: '. filesize($fullPath));
header('Content-Disposition: attachment; filename="'.$filename.'"');
readfile($fullPath);

exit;
