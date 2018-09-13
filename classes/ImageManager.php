<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class ImageManagerCore
 *
 * @since 1.0.0
 */
class ImageManagerCore
{
    const ERROR_FILE_NOT_EXIST = 1;
    const ERROR_FILE_WIDTH = 2;
    const ERROR_MEMORY_LIMIT = 3;

    /**
     * Generate a cached thumbnail for object lists (eg. carrier, order statuses...etc)
     *
     * @param string $image        Real image filename
     * @param string $cacheImage   Cached filename
     * @param int    $size         Desired size
     * @param string $imageType    Image type
     * @param bool   $disableCache When turned on a timestamp will be added to the image URI to disable the HTTP cache
     * @param bool   $regenerate   When turned on and the file already exist, the file will be regenerated
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function thumbnail($image, $cacheImage, $size, $imageType = 'jpg', $disableCache = true, $regenerate = false)
    {
        if (!file_exists($image)) {
            return '';
        }

        if (file_exists(_PS_TMP_IMG_DIR_.$cacheImage) && $regenerate) {
            @unlink(_PS_TMP_IMG_DIR_.$cacheImage);
        }

        if ($regenerate || !file_exists(_PS_TMP_IMG_DIR_.$cacheImage)) {
            $infos = getimagesize($image);

            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!ImageManager::checkImageMemoryLimit($image)) {
                return false;
            }

            $x = $infos[0];
            $y = $infos[1];
            $maxX = $size * 3;

            // Size is already ok
            if ($y < $size && $x <= $maxX) {
                copy($image, _PS_TMP_IMG_DIR_.$cacheImage);
            } // We need to resize */
            else {
                $ratio_x = $x / ($y / $size);
                if ($ratio_x > $maxX) {
                    $ratio_x = $maxX;
                    $size = $y / ($x / $maxX);
                }

                ImageManager::resize($image, _PS_TMP_IMG_DIR_.$cacheImage, $ratio_x, $size, $imageType);
            }
        }
        // Relative link will always work, whatever the base uri set in the admin
        if (Context::getContext()->controller->controller_type == 'admin') {
            return '<img src="../img/tmp/'.$cacheImage.($disableCache ? '?time='.time() : '').'" alt="" class="imgm img-thumbnail" />';
        } else {
            return '<img src="'._PS_TMP_IMG_.$cacheImage.($disableCache ? '?time='.time() : '').'" alt="" class="imgm img-thumbnail" />';
        }
    }

    /**
     * Check if memory limit is too long or not
     *
     * @param $image
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function checkImageMemoryLimit($image)
    {
        $infos = @getimagesize($image);

        if (!is_array($infos) || !isset($infos['bits'])) {
            return true;
        }

        $memoryLimit = Tools::getMemoryLimit();
        // memory_limit == -1 => unlimited memory
        if (isset($infos['bits']) && function_exists('memory_get_usage') && (int) $memoryLimit != -1) {
            $currentMemory = memory_get_usage();
            $bits = $infos['bits'] / 8;
            $channel = isset($infos['channels']) ? $infos['channels'] : 1;

            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            // For perfs, avoid computing static maths formulas in the code. pow(2, 16) = 65536 ; 1024 * 1024 = 1048576
            if (($infos[0] * $infos[1] * $bits * $channel + 65536) * 1.8 + $currentMemory > $memoryLimit - 1048576) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resize, cut and optimize image
     *
     * @param string $srcFile   Image object from $_FILE
     * @param string $dstFile   Destination filename
     * @param int    $dstWidth  Desired width (optional)
     * @param int    $dstHeight Desired height (optional)
     * @param string $fileType
     * @param bool   $forceType
     * @param int    $error
     * @param int    $tgtWidth
     * @param int    $tgtHeight
     * @param int    $quality
     * @param int    $srcWidth
     * @param int    $srcHeight
     *
     * @return bool Operation result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function resize(
        $srcFile,
        $dstFile,
        $dstWidth = null,
        $dstHeight = null,
        $fileType = 'jpg',
        $forceType = false,
        &$error = 0,
        &$tgtWidth = null,
        &$tgtHeight = null,
        $quality = 5,
        &$srcWidth = null,
        &$srcHeight = null
    ) {
        clearstatcache(true, $srcFile);

        if (!file_exists($srcFile) || !filesize($srcFile)) {
            return !($error = static::ERROR_FILE_NOT_EXIST);
        }

        list($tmpWidth, $tmpHeight, $type) = getimagesize($srcFile);
        $rotate = 0;
        if (function_exists('exif_read_data') && function_exists('mb_strtolower')) {
            $exif = @exif_read_data($srcFile);

            if ($exif && isset($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $srcWidth = $tmpWidth;
                        $srcHeight = $tmpHeight;
                        $rotate = 180;
                        break;

                    case 6:
                        $srcWidth = $tmpHeight;
                        $srcHeight = $tmpWidth;
                        $rotate = -90;
                        break;

                    case 8:
                        $srcWidth = $tmpHeight;
                        $srcHeight = $tmpWidth;
                        $rotate = 90;
                        break;

                    default:
                        $srcWidth = $tmpWidth;
                        $srcHeight = $tmpHeight;
                }
            } else {
                $srcWidth = $tmpWidth;
                $srcHeight = $tmpHeight;
            }
        } else {
            $srcWidth = $tmpWidth;
            $srcHeight = $tmpHeight;
        }

        // If PS_IMAGE_QUALITY is activated, the generated image will be a PNG with .jpg as a file extension.
        // This allow for higher quality and for transparency. JPG source files will also benefit from a higher quality
        // because JPG reencoding by GD, even with max quality setting, degrades the image.
        if ($fileType !== 'webp' && (Configuration::get('PS_IMAGE_QUALITY') == 'png_all'
            || (Configuration::get('PS_IMAGE_QUALITY') == 'png' && $type == IMAGETYPE_PNG) && !$forceType)
        ) {
            $fileType = 'png';
        }

        if (!$srcWidth) {
            return !($error = static::ERROR_FILE_WIDTH);
        }
        if (!$dstWidth) {
            $dstWidth = $srcWidth;
        }
        if (!$dstHeight) {
            $dstHeight = $srcHeight;
        }

        $widthDiff = $dstWidth / $srcWidth;
        $heightDiff = $dstHeight / $srcHeight;

        $psImageGenerationMethod = Configuration::get('PS_IMAGE_GENERATION_METHOD');
        if ($widthDiff > 1 && $heightDiff > 1) {
            $nextWidth = $srcWidth;
            $nextHeight = $srcHeight;
        } else {
            if ($psImageGenerationMethod == 2 || (!$psImageGenerationMethod && $widthDiff > $heightDiff)) {
                $nextHeight = $dstHeight;
                $nextWidth = round(($srcWidth * $nextHeight) / $srcHeight);
                $dstWidth = (int) (!$psImageGenerationMethod ? $dstWidth : $nextWidth);
            } else {
                $nextWidth = $dstWidth;
                $nextHeight = round($srcHeight * $dstWidth / $srcWidth);
                $dstHeight = (int) (!$psImageGenerationMethod ? $dstHeight : $nextHeight);
            }
        }

        if (!ImageManager::checkImageMemoryLimit($srcFile)) {
            return !($error = static::ERROR_MEMORY_LIMIT);
        }

        $tgtWidth = $dstWidth;
        $tgtHeight = $dstHeight;

        $destImage = imagecreatetruecolor($dstWidth, $dstHeight);

        // If image is a PNG or WEBP and the output is PNG/WEBP, fill with transparency. Else fill with white background.
        if ($fileType == 'png' && $type == IMAGETYPE_PNG || $fileType === 'webp') {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
            imagefilledrectangle($destImage, 0, 0, $dstWidth, $dstHeight, $transparent);
        } else {
            $white = imagecolorallocate($destImage, 255, 255, 255);
            imagefilledrectangle($destImage, 0, 0, $dstWidth, $dstHeight, $white);
        }

        $srcImage = ImageManager::create($type, $srcFile);
        if ($rotate) {
            $srcImage = imagerotate($srcImage, $rotate, 0);
        }

        if ($dstWidth >= $srcWidth && $dstHeight >= $srcHeight) {
            imagecopyresized($destImage, $srcImage, (int) (($dstWidth - $nextWidth) / 2), (int) (($dstHeight - $nextHeight) / 2), 0, 0, $nextWidth, $nextHeight, $srcWidth, $srcHeight);
        } else {
            ImageManager::imagecopyresampled($destImage, $srcImage, (int) (($dstWidth - $nextWidth) / 2), (int) (($dstHeight - $nextHeight) / 2), 0, 0, $nextWidth, $nextHeight, $srcWidth, $srcHeight, $quality);
        }
        $writeFile = ImageManager::write($fileType, $destImage, $dstFile);
        @imagedestroy($srcImage);

        return $writeFile;
    }

    /**
     * Create an image with GD extension from a given type
     *
     * @param string $type
     * @param string $filename
     *
     * @return resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function create($type, $filename)
    {
        switch ($type) {
            case IMAGETYPE_GIF :
                return imagecreatefromgif($filename);

            case IMAGETYPE_PNG :
                return imagecreatefrompng($filename);

            case 18:
                return imagecreatefromwebp($filename);

            case IMAGETYPE_JPEG :
            default:
                return imagecreatefromjpeg($filename);
                break;
        }
    }

    /**
     * @param     $dstImage
     * @param     $srcImage
     * @param     $dstX
     * @param     $dstY
     * @param     $srcX
     * @param     $srcY
     * @param     $dstW
     * @param     $dstH
     * @param     $srcW
     * @param     $srcH
     * @param int $quality
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function imagecopyresampled(&$dstImage, $srcImage, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH, $quality = 3)
    {
        // Plug-and-Play fastimagecopyresampled function replaces much slower imagecopyresampled.
        // Just include this function and change all "imagecopyresampled" references to "fastimagecopyresampled".
        // Typically from 30 to 60 times faster when reducing high resolution images down to thumbnail size using the default quality setting.
        // Author: Tim Eckel - Date: 09/07/07 - Version: 1.1 - Project: FreeRingers.net - Freely distributable - These comments must remain.
        //
        // Optional "quality" parameter (defaults is 3). Fractional values are allowed, for example 1.5. Must be greater than zero.
        // Between 0 and 1 = Fast, but mosaic results, closer to 0 increases the mosaic effect.
        // 1 = Up to 350 times faster. Poor results, looks very similar to imagecopyresized.
        // 2 = Up to 95 times faster.  Images appear a little sharp, some prefer this over a quality of 3.
        // 3 = Up to 60 times faster.  Will give high quality smooth results very close to imagecopyresampled, just faster.
        // 4 = Up to 25 times faster.  Almost identical to imagecopyresampled for most images.
        // 5 = No speedup. Just uses imagecopyresampled, no advantage over imagecopyresampled.

        if (empty($srcImage) || empty($dstImage) || $quality <= 0) {
            return false;
        }
        if ($quality < 5 && (($dstW * $quality) < $srcW || ($dstH * $quality) < $srcH)) {
            $temp = imagecreatetruecolor($dstW * $quality + 1, $dstH * $quality + 1);
            imagecopyresized($temp, $srcImage, 0, 0, $srcX, $srcY, $dstW * $quality + 1, $dstH * $quality + 1, $srcW, $srcH);
            imagecopyresampled($dstImage, $temp, $dstX, $dstY, 0, 0, $dstW, $dstH, $dstW * $quality, $dstH * $quality);
            imagedestroy($temp);
        } else {
            imagecopyresampled($dstImage, $srcImage, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
        }

        return true;
    }

    /**
     * Generate and write image
     *
     * @param string   $type
     * @param resource $resource
     * @param string   $filename
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function write($type, $resource, $filename)
    {
        static $psPngQuality = null;
        static $psJpegQuality = null;
        static $psWebpQuality = null;

        if ($psPngQuality === null) {
            $psPngQuality = Configuration::get('PS_PNG_QUALITY');
        }

        if ($psJpegQuality === null) {
            $psJpegQuality = Configuration::get('PS_JPEG_QUALITY');
        }

        if ($psWebpQuality === null) {
            $psWebpQuality = Configuration::get('PS_WEBP_QUALITY');
        }

        switch ($type) {
            case 'gif':
                $success = imagegif($resource, $filename);
                break;

            case 'png':
                $quality = ($psPngQuality === false ? 7 : $psPngQuality);
                $success = imagepng($resource, $filename, (int) $quality);
                break;

            case 'webp':
                $quality = ($psWebpQuality === false ? 90 : $psWebpQuality);
                $success = imagewebp($resource, $filename, (int) $quality);
                break;

            case 'jpg':
            case 'jpeg':
            default:
                $quality = ($psJpegQuality === false ? 90 : $psJpegQuality);
                imageinterlace($resource, 1); /// make it PROGRESSIVE
                $success = imagejpeg($resource, $filename, (int) $quality);
                break;
        }
        imagedestroy($resource);
        @chmod($filename, 0664);

        return $success;
    }

    /**
     * Validate image upload (check image type and weight)
     *
     * @param array $file        Upload $_FILE value
     * @param int   $maxFileSize Maximum upload size
     *
     * @return bool|string Return false if no error encountered
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function validateUpload($file, $maxFileSize = 0, $types = null)
    {
        if ((int) $maxFileSize > 0 && $file['size'] > (int) $maxFileSize) {
            return sprintf(Tools::displayError('Image is too large (%1$d kB). Maximum allowed: %2$d kB'), $file['size'] / 1024, $maxFileSize / 1024);
        }
        if (!ImageManager::isRealImage($file['tmp_name'], $file['type']) || !ImageManager::isCorrectImageFileExt($file['name'], $types) || preg_match('/\%00/', $file['name'])) {
            return Tools::displayError('Image format not recognized, allowed formats are: .gif, .jpg, .png');
        }
        if ($file['error']) {
            return sprintf(Tools::displayError('Error while uploading image; please change your server\'s settings. (Error code: %s)'), $file['error']);
        }

        return false;
    }

    /**
     * Check if file is a real image
     *
     * @param string $filename     File path to check
     * @param string $fileMimeType File known mime type (generally from $_FILES)
     * @param array  $mimeTypeList Allowed MIME types
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isRealImage($filename, $fileMimeType = null, $mimeTypeList = null)
    {
        // Detect mime content type
        $mimeType = false;
        if (!$mimeTypeList) {
            $mimeTypeList = ['image/gif', 'image/jpg', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'];
        }

        // Try 4 different methods to determine the mime type
        if (function_exists('getimagesize')) {
            $imageInfo = @getimagesize($filename);

            if ($imageInfo) {
                $mimeType = $imageInfo['mime'];
            } else {
                $fileMimeType = false;
            }
        } elseif (function_exists('finfo_open')) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            $finfo = finfo_open($const);
            $mimeType = finfo_file($finfo, $filename);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filename);
        } elseif (function_exists('exec')) {
            $mimeType = trim(exec('file -b --mime-type '.escapeshellarg($filename)));
            if (!$mimeType) {
                $mimeType = trim(exec('file --mime '.escapeshellarg($filename)));
            }
            if (!$mimeType) {
                $mimeType = trim(exec('file -bi '.escapeshellarg($filename)));
            }
        }

        if ($fileMimeType && (empty($mimeType) || $mimeType == 'regular file' || $mimeType == 'text/plain')) {
            $mimeType = $fileMimeType;
        }

        // For each allowed MIME type, we are looking for it inside the current MIME type
        foreach ($mimeTypeList as $type) {
            if (strstr($mimeType, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if image file extension is correct
     *
     * @param string     $filename Real filename
     * @param array|null $authorizedExtensions
     *
     * @return bool True if it's correct
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isCorrectImageFileExt($filename, $authorizedExtensions = null)
    {
        // Filter on file extension
        if ($authorizedExtensions === null) {
            $authorizedExtensions = ['gif', 'jpg', 'jpeg', 'jpe', 'png'];
        }
        $nameExplode = explode('.', $filename);
        if (count($nameExplode) >= 2) {
            $current_extension = strtolower($nameExplode[count($nameExplode) - 1]);
            if (!in_array($current_extension, $authorizedExtensions)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Validate icon upload
     *
     * @param array $file        Upload $_FILE value
     * @param int   $maxFileSize Maximum upload size
     *
     * @return bool|string Return false if no error encountered
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function validateIconUpload($file, $maxFileSize = 0)
    {
        if ((int) $maxFileSize > 0 && $file['size'] > $maxFileSize) {
            return sprintf(
                Tools::displayError('Image is too large (%1$d kB). Maximum allowed: %2$d kB'),
                $file['size'] / 1000,
                $maxFileSize / 1000
            );
        }
        if (substr($file['name'], -4) != '.ico' && substr($file['name'], -4) != '.png') {
            return Tools::displayError('Image format not recognized, allowed formats are: .ico, .png');
        }
        if ($file['error']) {
            return Tools::displayError('Error while uploading image; please change your server\'s settings.');
        }

        return false;
    }

    /**
     * Cut image
     *
     * @param array  $srcFile   Origin filename
     * @param string $dstFile   Destination filename
     * @param int    $dstWidth  Desired width
     * @param int    $dstHeight Desired height
     * @param string $fileType
     * @param int    $dstX
     * @param int    $dstY
     *
     * @return bool Operation result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function cut($srcFile, $dstFile, $dstWidth = null, $dstHeight = null, $fileType = 'jpg', $dstX = 0, $dstY = 0)
    {
        if (!file_exists($srcFile)) {
            return false;
        }

        // Source information
        $srcInfo = getimagesize($srcFile);
        $src = [
            'width'     => $srcInfo[0],
            'height'    => $srcInfo[1],
            'ressource' => ImageManager::create($srcInfo[2], $srcFile),
        ];

        // Destination information
        $dest = [];
        $dest['x'] = $dstX;
        $dest['y'] = $dstY;
        $dest['width'] = !is_null($dstWidth) ? $dstWidth : $src['width'];
        $dest['height'] = !is_null($dstHeight) ? $dstHeight : $src['height'];
        $dest['ressource'] = ImageManager::createWhiteImage($dest['width'], $dest['height']);

        $white = imagecolorallocate($dest['ressource'], 255, 255, 255);
        imagecopyresampled($dest['ressource'], $src['ressource'], 0, 0, $dest['x'], $dest['y'], $dest['width'], $dest['height'], $dest['width'], $dest['height']);
        imagecolortransparent($dest['ressource'], $white);
        $return = ImageManager::write($fileType, $dest['ressource'], $dstFile);
        @imagedestroy($src['ressource']);

        return $return;
    }

    /**
     * Create an empty image with white background
     *
     * @param int $width
     * @param int $height
     *
     * @return resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function createWhiteImage($width, $height)
    {
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        return $image;
    }

    /**
     * Return the mime type by the file extension
     *
     * @param string $fileName
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMimeTypeByExtension($fileName)
    {
        $types = [
            'image/gif'  => ['gif'],
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png'  => ['png'],
        ];
        $extension = substr($fileName, strrpos($fileName, '.') + 1);

        $mimeType = null;
        foreach ($types as $mime => $exts) {
            if (in_array($extension, $exts)) {
                $mimeType = $mime;
                break;
            }
        }

        if ($mimeType === null) {
            $mimeType = 'image/jpeg';
        }

        return $mimeType;
    }


    /**
     * Add an image to the generator.
     *
     * This function adds a source image to the generator. It serves two main purposes: add a source image if one was
     * not supplied to the constructor and to add additional source images so that different images can be supplied for
     * different sized images in the resulting ICO file. For instance, a small source image can be used for the small
     * resolutions while a larger source image can be used for large resolutions.
     *
     * @param string $source Path to the source image file.
     * @param array  $sizes  Optional. An array of sizes (each size is an array with a width and height) that the source image should be rendered at in the generated ICO file. If sizes are not supplied, the size of the source image will be used.
     *
     * @return boolean true on success and false on failure.
     *
     * @copyright 2011-2016  Chris Jean
     * @author Chris Jean
     * @license GNU General Public License v2.0
     * @source https://github.com/chrisbliss18/php-ico
     */
    public static function generateFavicon($source, $sizes = [['16', '16'], ['24', '24'], ['32', '32'], ['48', '48'], ['64', '64']])
    {
        $images = [];

        if (!$size = getimagesize($source)) {
            return false;
        }
        if (!$file_data = file_get_contents($source)) {
            return false;
        }
        if (!$im = imagecreatefromstring($file_data)) {
            return false;
        }
        unset($file_data);
        if (empty($sizes)) {
            $sizes = [imagesx($im), imagesy($im)];
        }

        // If just a single size was passed, put it in array.
        if ( ! is_array( $sizes[0] ) ) {
            $sizes = [$sizes];
        }

        foreach ( (array) $sizes as $size ) {
            list( $width, $height ) = $size;
            $new_im = imagecreatetruecolor( $width, $height );
            imagecolortransparent( $new_im, imagecolorallocatealpha( $new_im, 0, 0, 0, 127 ) );
            imagealphablending( $new_im, false );
            imagesavealpha( $new_im, true );
            $source_width = imagesx( $im );
            $source_height = imagesy( $im );
            if ( false === imagecopyresampled( $new_im, $im, 0, 0, 0, 0, $width, $height, $source_width, $source_height ) )
                continue;

            static::addFaviconImageData($new_im, $images);
        }

        return static::getIcoData($images);
    }

    /**
     * Generate the final ICO data by creating a file header and adding the image data.
     *
     * @copyright 2011-2016  Chris Jean
     * @author Chris Jean
     * @license GNU General Public License v2.0
     * @source https://github.com/chrisbliss18/php-ico
     */
    protected static function getIcoData($images)
    {
        if (!is_array($images) || empty($images))
            return false;
        $data = pack('vvv', 0, 1, count($images));
        $pixel_data = '';
        $icon_dir_entry_size = 16;
        $offset = 6 + ($icon_dir_entry_size * count($images));
        foreach ($images as $image) {
            $data .= pack('CCCCvvVV', $image['width'], $image['height'], $image['color_palette_colors'], 0, 1, $image['bits_per_pixel'], $image['size'], $offset);
            $pixel_data .= $image['data'];
            $offset += $image['size'];
        }
        $data .= $pixel_data;
        unset($pixel_data);
        return $data;
    }

    /**
     * Take a GD image resource and change it into a raw BMP format.
     *
     * @copyright 2011-2016  Chris Jean
     * @author Chris Jean
     * @license GNU General Public License v2.0
     * @source https://github.com/chrisbliss18/php-ico
     */
    protected static function addFaviconImageData($im, &$images)
    {
        $width = imagesx($im);
        $height = imagesy($im);
        $pixel_data = [];
        $opacity_data = [];
        $current_opacity_val = 0;
        for ($y = $height - 1; $y >= 0; $y--) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($im, $x, $y);
                $alpha = ($color & 0x7F000000) >> 24;
                $alpha = (1 - ($alpha / 127)) * 255;
                $color &= 0xFFFFFF;
                $color |= 0xFF000000 & ($alpha << 24);
                $pixel_data[] = $color;
                $opacity = ($alpha <= 127) ? 1 : 0;
                $current_opacity_val = ($current_opacity_val << 1) | $opacity;
                if ((($x + 1) % 32) == 0) {
                    $opacity_data[] = $current_opacity_val;
                    $current_opacity_val = 0;
                }
            }
            if (($x % 32) > 0) {
                while (($x++ % 32) > 0) {
                    $current_opacity_val = $current_opacity_val << 1;
                }
                $opacity_data[] = $current_opacity_val;
                $current_opacity_val = 0;
            }
        }
        $image_header_size = 40;
        $color_mask_size = $width * $height * 4;
        $opacity_mask_size = (ceil($width / 32) * 4) * $height;
        $data = pack('VVVvvVVVVVV', 40, $width, ($height * 2), 1, 32, 0, 0, 0, 0, 0, 0);
        foreach ($pixel_data as $color) {
            $data .= pack('V', $color);
        }
        foreach ($opacity_data as $opacity) {
            $data .= pack('N', $opacity);
        }
        $image = [
            'width'                => $width,
            'height'               => $height,
            'color_palette_colors' => 0,
            'bits_per_pixel'       => 32,
            'size'                 => $image_header_size + $color_mask_size + $opacity_mask_size,
            'data'                 => $data,
        ];
        $images[] = $image;
    }

    /**
     * @param bool $checkAccept Check the accept header
     *
     * @return bool
     *
     * @since 1.0.4
     */
    public static function webpSupport($checkAccept = false)
    {
        static $supported = null;
        if ($supported === null) {
            $config = Context::getContext()->theme->getConfiguration();

            try {
                $supported = Configuration::get('TB_USE_WEBP')
                    && !empty($config['webp'])
                    && function_exists('imagewebp');
            } catch (PrestaShopException $e) {
                $supported = false;
            }
        }

        if ($checkAccept) {
            $supported &= !empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false;
        }

        return $supported;
    }

    /**
     * @return bool
     *
     * @since 1.0.4
     */
    public static function retinaSupport()
    {
        static $supported = null;
        if ($supported === null) {
            try {
                $supported = (bool) Configuration::get('PS_HIGHT_DPI');
            } catch (PrestaShopException $e) {
                $supported = false;
            }
        }

        return $supported;
    }
}
