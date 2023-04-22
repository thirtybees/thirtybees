<?php

/**
 * Returns all extensions for given $category
 *
 * @param string $category
 *
 * @return string[]
 */
function getFileExtensions(string $category = 'all'): array
{
    return array_reduce(FILE_MANAGER_ALLOWED_MIME_TYPES, function($carry, $record) use ($category){
        if ($category === 'all' || $record['category'] === $category) {
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
 *
 * @return bool
 */
function canUploadFile(string $mimeType, string $extension): bool
{
    if (isset(FILE_MANAGER_ALLOWED_MIME_TYPES[$mimeType])) {
        return in_array($extension, FILE_MANAGER_ALLOWED_MIME_TYPES[$mimeType]['extensions']);
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
 * @return bool|void
 */
function rename_file($old_path, $name)
{
    $name=fix_filename($name);
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
 * @return bool|void
 */
function rename_folder($old_path, $name)
{
    $name=fix_filename($name);
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
    if (ImageManager::checkImageMemoryLimit($imgfile)) {
        require_once('php_image_magician.php');
        $magicianObj = new imageLib($imgfile);
        $magicianObj->resizeImage($newwidth, $newheight, 'crop');
        $magicianObj->saveImage($imgthumb, 80);
        return true;
    }
    return false;
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
 * @return string
 */
function fix_filename($str)
{
    $str=str_replace(['"', "'", "/", "\\"], "", $str);
    $str=strip_tags($str);

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
 * @return string
 */
function fix_path($path)
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

    $str=fix_filename($info['filename']);
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
 * Returns currently selected subdirectory
 *
 * @param string|false|null $fldr
 *
 * @return string
 *
 * @noinspection PhpUndefinedFieldInspection
 */
function getSubDir($fldr)
{
    $cookie = Context::getContext()->cookie;
    if ($fldr) {
        $fldr = str_replace('\\', '/', urldecode((string)$fldr));
        $subdir = normalizePath($fldr);
        if ($subdir) {
            $subdir .= '/';
            $cookie->fmLastPosition = $subdir;
        } else {
            unset($cookie->fmLastPosition);
            return '';
        }
    }

    if (isset($cookie->fmLastPosition)) {
       return $cookie->fmLastPosition;
    }

    return '';
}

/**
 * Normalize $path, and expands /./ and /../ symlinks
 *
 * @param string $path
 *
 * @return string
 */
function normalizePath(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $arr = explode('/', $path);
    $arr = array_reduce($arr, function (array $path, string $item) {
        $s = trim($item);
        if (empty($s)) {
            return $path;
        }
        if ($s === '.') {
            return $path;
        }
        if ($s === '..') {
            if ($path) {
                array_pop($path);
            }
            return $path;
        }
        $path[] = $s;
        return $path;
    }, []);
    return implode('/', $arr);
}

/**
 * @return int
 */
function getViewType():int
{
    $cookie = Context::getContext()->cookie;
    if (isset($cookie->fmViewType)) {
        return (int)$cookie->fmViewType;
    }
    return 0;
}

/**
 * @param int $viewType
 *
 * @return int
 *
 * @noinspection PhpUndefinedFieldInspection
 */
function setViewType(int $viewType):int
{
    $viewType = (int)$viewType;
    $cookie = Context::getContext()->cookie;
    if ($viewType) {
        $cookie->fmViewType = (int)$viewType;
    } else {
        unset($cookie->fmViewType);
    }
    return $viewType;
}

/**
 * @return string
 */
function getSortBy(): string
{
    $cookie = Context::getContext()->cookie;
    if (isset($cookie->fmSortBy)) {
        $sortBy = strtolower($cookie->fmSortBy);
        if (in_array($sortBy, ['name', 'date', 'size', 'extension'])) {
            return $sortBy;
        }
        }
    return 'name';
}

/**
 * @param string $sortBy
 *
 * @return string
 *
 * @noinspection PhpUndefinedFieldInspection
 */
function setSortBy(string $sortBy):string
{
    $sortBy = strtolower($sortBy);
    if (!in_array($sortBy, ['name', 'date', 'size', 'extension'])) {
        $sortBy = 'name';
    }
    $cookie = Context::getContext()->cookie;
    $cookie->fmSortBy = $sortBy;
    return $sortBy;
}

/**
 * @return bool
 */
function getDescending(): bool
{
    $cookie = Context::getContext()->cookie;
    if (isset($cookie->fmSortOrder)) {
        return (bool)$cookie->fmSortOrder;
    }
    return false;
}

/**
 * @param bool $descending
 *
 * @return bool
 *
 * @noinspection PhpUndefinedFieldInspection
 */
function setDescending(bool $descending): bool
{
    $cookie = Context::getContext()->cookie;
    $cookie->fmSortOrder = $descending ? 1 : 0;
    return $descending;
}
