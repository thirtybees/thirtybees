<?php

/**
 * Returns all extensions for given $category
 *
 * @param string $category
 * @param array $allowedMineTypes
 *
 * @return string[]
 */
function getMimeTypeFileExtensions(string $category, array $allowedMineTypes): array
{
    return array_reduce($allowedMineTypes, function($carry, $record) use ($category){
        if ($record['category'] === $category) {
            return array_unique(array_merge($carry, $record['extensions']));
        }
        return $carry;
    }, []);
}

/**
 * Returns true, if file with mime type $mimeType and extension $extension is allowed to
 * be uploaded
 *
 * @param string $mimeType
 * @param string $extension
 * @param array $allowedMimeTypes
 *
 * @return bool
 */
function canUploadFile(string $mimeType, string $extension, array $allowedMimeTypes): bool
{
    if (isset($allowedMimeTypes[$mimeType])) {
        return in_array($extension, $allowedMimeTypes[$mimeType]['extensions']);
    }
    return false;
}

/**
 * @param string $dir
 * @return bool
 */
function deleteDir($dir)
{
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!deleteDir($dir.DIRECTORY_SEPARATOR.$item)) {
            return false;
        }
    }
    return rmdir($dir);
}

/**
 * @param string $old_path
 * @param string $name
 * @return bool|void
 */
function duplicate_file($old_path, $name)
{
    if (file_exists($old_path)) {
        $info=pathinfo($old_path);
        $new_path=$info['dirname']."/".$name.".".$info['extension'];
        if (file_exists($new_path)) {
            return false;
        }
        return copy($old_path, $new_path);
    }
}

/**
 * @param string $old_path
 * @param string $name
 * @param bool $transliteration
 * @return bool|void
 */
function rename_file($old_path, $name, $transliteration)
{
    $name=fix_filename($name, $transliteration);
    if (file_exists($old_path)) {
        $info=pathinfo($old_path);
        $new_path=$info['dirname']."/".$name.".".$info['extension'];
        if (file_exists($new_path)) {
            return false;
        }
        return rename($old_path, $new_path);
    }
}

/**
 * @param string $old_path
 * @param string $name
 * @param bool $transliteration
 * @return bool|void
 */
function rename_folder($old_path, $name, $transliteration)
{
    $name=fix_filename($name, $transliteration);
    if (file_exists($old_path)) {
        $new_path=fix_dirname($old_path)."/".$name;
        if (file_exists($new_path)) {
            return false;
        }
        return rename($old_path, $new_path);
    }
}

/**
 * @param string $imgfile
 * @param string $imgthumb
 * @param int $newwidth
 * @param string $newheight
 * @return bool
 * @throws PrestaShopException
 */
function create_img_gd($imgfile, $imgthumb, $newwidth, $newheight="")
{
    if (image_check_memory_usage($imgfile, $newwidth, $newheight)) {
        require_once('php_image_magician.php');
        $magicianObj = new imageLib($imgfile);
        $magicianObj->resizeImage($newwidth, $newheight, 'crop');
        $magicianObj->saveImage($imgthumb, 80);
        return true;
    }
    return false;
}

/**
 * @param string $imgfile
 * @param string $imgthumb
 * @param int $newwidth
 * @param string $newheight
 * @return bool
 * @throws PrestaShopException
 */
function create_img($imgfile, $imgthumb, $newwidth, $newheight="")
{
    if (image_check_memory_usage($imgfile, $newwidth, $newheight)) {
        require_once('php_image_magician.php');
        $magicianObj = new imageLib($imgfile);
        $magicianObj->resizeImage($newwidth, $newheight, 'auto');
        $magicianObj->saveImage($imgthumb, 80);
        return true;
    } else {
        return false;
    }
}

/**
 * @param int $size
 * @return string
 */
function makeSize($size)
{
    $units = ['B','KB','MB','GB','TB'];
    $u = 0;
    while ((round($size / 1024) > 0) && ($u < 4)) {
        $size = $size / 1024;
        $u++;
    }
    return (number_format($size, 0) . " " . $units[$u]);
}

/**
 * @param string $path
 * @return int
 */
function foldersize($path)
{
    $total_size = 0;
    $files = scandir($path);
    $cleanPath = rtrim($path, '/'). '/';

    foreach ($files as $t) {
        if ($t<>"." && $t<>"..") {
            $currentFile = $cleanPath . $t;
            if (is_dir($currentFile)) {
                $size = foldersize($currentFile);
                $total_size += $size;
            } else {
                $size = filesize($currentFile);
                $total_size += $size;
            }
        }
    }

    return $total_size;
}

/**
 * @param string $path
 * @param string $path_thumbs
 * @return void
 */
function create_folder($path=false, $path_thumbs=false)
{
    $oldumask = umask(0);
    if ($path && !file_exists($path)) {
        mkdir($path, 0777, true);
    } // or even 01777 so you get the sticky bit set
    if ($path_thumbs && !file_exists($path_thumbs)) {
        mkdir($path_thumbs, 0777, true) or die("$path_thumbs cannot be found");
    } // or even 01777 so you get the sticky bit set
    umask($oldumask);
}

/**
 * @param string $path
 * @param string[] $ext
 * @return void
 */
function check_files_extensions_on_path($path, $ext)
{
    if (!is_dir($path)) {
        $fileinfo = pathinfo($path);
        if (function_exists('mb_strtolower')) {
            if (!in_array(mb_strtolower($fileinfo['extension']), $ext)) {
                unlink($path);
            } elseif (!in_array(strtolower($fileinfo['extension']), $ext)) {
                unlink($path);
            }
        }
    } else {
        $files = scandir($path);
        foreach ($files as $file) {
            check_files_extensions_on_path(trim($path, '/')."/".$file, $ext);
        }
    }
}

/**
 * @param Traversable $phar
 * @param array $files
 * @param string $basepath
 * @param string[] $ext
 * @return void
 */
function check_files_extensions_on_phar($phar, &$files, $basepath, $ext)
{
    foreach ($phar as $file) {
        if ($file->isFile()) {
            if (in_array(mb_strtolower($file->getExtension()), $ext)) {
                $files[] = $basepath.$file->getFileName();
            }
        } elseif ($file->isDir()) {
            $iterator = new DirectoryIterator($file);
            check_files_extensions_on_phar($iterator, $files, $basepath.$file->getFileName().'/', $ext);
        }
    }
}

/**
 * @param string $str
 * @param bool $transliteration
 * @return string
 */
function fix_filename($str, $transliteration)
{
    if ($transliteration) {
        if (function_exists('transliterator_transliterate')) {
            $str = transliterator_transliterate('Accents-Any', $str);
        } else {
            $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        }

        $str = preg_replace("/[^a-zA-Z0-9.\[\]_| -]/", '', $str);
    }

    $str=str_replace(['"', "'", "/", "\\"], "", $str);
    $str=strip_tags($str);

    // Empty or incorrectly transliterated filename.
    // Here is a point: a good file UNKNOWN_LANGUAGE.jpg could become .jpg in previous code.
    // So we add that default 'file' name to fix that issue.
    if (strpos($str, '.') === 0) {
        $str = 'file'.$str;
    }

    return trim($str);
}

/**
 * @param string $str
 * @return string
 */
function fix_dirname($str)
{
    return str_replace('~', ' ', dirname(str_replace(' ', '~', $str)));
}

/**
 * @param string $str
 * @return string
 */
function fix_strtoupper($str)
{
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($str);
    } else {
        return strtoupper($str);
    }
}


/**
 * @param string $str
 * @return string
 */
function fix_strtolower($str)
{
    if (function_exists('mb_strtoupper')) {
        return mb_strtolower($str);
    } else {
        return strtolower($str);
    }
}

/**
 * @param string $path
 * @param bool $transliteration
 * @return string
 */
function fix_path($path, $transliteration)
{
    $info=pathinfo($path);
    if (($s = strrpos($path, '/')) !== false) {
        $s++;
    }
    if (($e = strrpos($path, '.') - $s) !== strlen($info['filename'])) {
        $info['filename'] = substr($path, $s, $e);
        $info['basename'] = substr($path, $s);
    }
    $tmp_path = $info['dirname'].DIRECTORY_SEPARATOR.$info['basename'];

    $str=fix_filename($info['filename'], $transliteration);
    if ($tmp_path!="") {
        return $tmp_path.DIRECTORY_SEPARATOR.$str;
    } else {
        return $str;
    }
}

/**
 * @return string
 */
function base_url()
{
    return sprintf(
    "%s://%s",
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
    $_SERVER['HTTP_HOST']
  );
}

/**
 * @param string $current_path
 * @param string $fld
 * @return bool
 */
function config_loading($current_path, $fld)
{
    if (file_exists($current_path.$fld.".config")) {
        require_once($current_path.$fld.".config");
        return true;
    }
    echo "!!!!".$parent=fix_dirname($fld);
    if ($parent!="." && !empty($parent)) {
        config_loading($current_path, $parent);
    }

    return false;
}


/**
 * @param string $img
 * @param int $max_breedte
 * @param int $max_hoogte
 * @return bool
 */
function image_check_memory_usage($img, $max_breedte, $max_hoogte)
{
    if (file_exists($img)) {
        $K64 = 65536;    // number of bytes in 64K
        $memory_usage = memory_get_usage();
        $memory_limit = abs(intval(str_replace('M', '', ini_get('memory_limit'))*1024*1024));
        $image_properties = getimagesize($img);
        $image_width = $image_properties[0];
        $image_height = $image_properties[1];
        $image_bits = $image_properties['bits'];
        $image_memory_usage = $K64 + ($image_width * $image_height * ($image_bits)  * 2);
        $thumb_memory_usage = $K64 + ($max_breedte * $max_hoogte * ($image_bits) * 2);
        $memory_needed = intval($memory_usage + $image_memory_usage + $thumb_memory_usage);

        if ($memory_needed > $memory_limit) {
            ini_set('memory_limit', (intval($memory_needed/1024/1024)+5) . 'M');
            if (ini_get('memory_limit') == (intval($memory_needed/1024/1024)+5) . 'M') {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    } else {
        return false;
    }
}

/**
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function endsWith($haystack, $needle)
{
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

/**
 * @param string $targetPath
 * @param string $targetFile
 * @param string $name
 * @param string $current_path
 * @param bool $relative_image_creation
 * @param array $relative_path_from_current_pos
 * @param array $relative_image_creation_name_to_prepend
 * @param array $relative_image_creation_name_to_append
 * @param array $relative_image_creation_width
 * @param array $relative_image_creation_height
 * @param bool $fixed_image_creation
 * @param array $fixed_path_from_filemanager
 * @param array $fixed_image_creation_name_to_prepend
 * @param array $fixed_image_creation_to_append
 * @param array $fixed_image_creation_width
 * @param array $fixed_image_creation_height
 * @return bool
 * @throws PrestaShopException
 */
function new_thumbnails_creation(
    $targetPath,
    $targetFile,
    $name,
    $current_path,
    $relative_image_creation,
    $relative_path_from_current_pos,
    $relative_image_creation_name_to_prepend,
    $relative_image_creation_name_to_append,
    $relative_image_creation_width,
    $relative_image_creation_height,
    $fixed_image_creation,
    $fixed_path_from_filemanager,
    $fixed_image_creation_name_to_prepend,
    $fixed_image_creation_to_append,
    $fixed_image_creation_width,
    $fixed_image_creation_height
) {
    //create relative thumbs
    $all_ok=true;
    if ($relative_image_creation) {
        foreach ($relative_path_from_current_pos as $k=>$path) {
            if ($path!="" && $path[strlen($path)-1]!="/") {
                $path.="/";
            }
            if (!file_exists($targetPath.$path)) {
                create_folder($targetPath.$path, false);
            }
            $info=pathinfo($name);
            if (!endsWith($targetPath, $path)) {
                if (!create_img($targetFile, $targetPath.$path.$relative_image_creation_name_to_prepend[$k].$info['filename'].$relative_image_creation_name_to_append[$k].".".$info['extension'], $relative_image_creation_width[$k], $relative_image_creation_height[$k])) {
                    $all_ok=false;
                }
            }
        }
    }

    //create fixed thumbs
    if ($fixed_image_creation) {
        foreach ($fixed_path_from_filemanager as $k=>$path) {
            if ($path!="" && $path[strlen($path)-1]!="/") {
                $path.="/";
            }
            $base_dir=$path.substr_replace($targetPath, '', 0, strlen($current_path));
            if (!file_exists($base_dir)) {
                create_folder($base_dir, false);
            }
            $info=pathinfo($name);
            if (!create_img($targetFile, $base_dir.$fixed_image_creation_name_to_prepend[$k].$info['filename'].$fixed_image_creation_to_append[$k].".".$info['extension'], $fixed_image_creation_width[$k], $fixed_image_creation_height[$k])) {
                $all_ok=false;
            }
        }
    }
    return $all_ok;
}


/**
 * Get a remote file, using whichever mechanism is enabled
 * @param string $url
 * @return bool|string
 */
function get_file_by_url($url)
{
    if (ini_get('allow_url_fopen')) {
        return file_get_contents($url);
    }
    if (!function_exists('curl_version')) {
        return false;
    }

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}
