<?php
include('config/config.php');

if (preg_match('/\.{1,2}[\/|\\\]/', $_POST['path']) !== 0) {
    die('wrong path');
}

if (strpos($_POST['name'], '/') !== false || strpos($_POST['name'], '\\') !== false) {
    die('wrong path');
}

$path = FILE_MANAGER_BASE_DIR . $_POST['path'];
$name = $_POST['name'];

$info = pathinfo($name);
if (!in_array(fix_strtolower($info['extension']), getFileExtensions())) {
    die('wrong extension');
}

header('Pragma: private');
header('Cache-control: private, must-revalidate');
header('Content-Type: application/octet-stream');
header('Content-Length: '.(string)filesize($path.$name));
header('Content-Disposition: attachment; filename="'.($name).'"');
readfile($path.$name);

exit;
