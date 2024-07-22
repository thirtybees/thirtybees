<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class ImageManagerCore
 */
class ImageManagerCore
{
    const ERROR_FILE_NOT_EXIST = 1;
    const ERROR_FILE_WIDTH = 2;
    const ERROR_MEMORY_LIMIT = 3;
    const ERROR_FORBIDDEN_IMAGE_EXTENSION = 4;

    const DO_NOT_USE_WEBP = 0;
    const USE_WEBP = 1;

    /**
     * Generate a cached thumbnail for object lists (eg. carrier, order statuses...etc)
     *
     * @param string $image Real image filename
     * @param string $cacheImage Cached filename
     * @param int $size Desired size
     * @param string $imageExtension Image type
     * @param bool $disableCache When turned on a timestamp will be added to the image URI to disable the HTTP cache
     * @param bool $regenerate When turned on and the file already exist, the file will be regenerated
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function thumbnail($image, $cacheImage, $size, $imageExtension = null, $disableCache = true, $regenerate = false)
    {
        $imagePath = static::getThumbnailUrl($image, $cacheImage, $size, $imageExtension, $disableCache, $regenerate);
        if ($imagePath) {
            return '<img src="'.$imagePath.'" alt="" class="imgm img-thumbnail" />';
        }
        return $imagePath;
    }

    /**
     * Generate a cached thumbnail for image file and returns url to it
     *
     * @param string $image Real image filename
     * @param string $cacheImage Cached filename
     * @param int $size Desired size
     * @param string $imageExtension Image type
     * @param bool $disableCache When turned on a timestamp will be added to the image URI to disable the HTTP cache
     * @param bool $regenerate When turned on and the file already exist, the file will be regenerated
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function getThumbnailUrl($image, $cacheImage, $size, $imageExtension = null, $disableCache = true, $regenerate = false)
    {
        if (!file_exists($image) && (!$image = self::tryRestoreImage($image))) {
            return '';
        }

        $targetFile = _PS_TMP_IMG_DIR_ . $cacheImage;

        // delete existing thumbnail file if we are instructed to regenerate
        if (file_exists($targetFile) && $regenerate) {
            @unlink($targetFile);
        }

        // generate thumbnail file if it not exists yet
        if (!file_exists($targetFile)) {
            $infos = getimagesize($image);

            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!ImageManager::checkImageMemoryLimit($image)) {
                return '';
            }

            $x = $infos[0];
            $y = $infos[1];
            $maxX = $size * 3;

            // Size is already ok
            if ($y < $size && $x <= $maxX) {
                copy($image, $targetFile);
            } // We need to resize */
            else {
                $ratio_x = $x / ($y / $size);
                if ($ratio_x > $maxX) {
                    $ratio_x = $maxX;
                    $size = $y / ($x / $maxX);
                }

                ImageManager::resize($image, $targetFile, (int)$ratio_x, (int)$size, $imageExtension);
            }
        }

        if ($disableCache) {
            $ts = file_exists($targetFile) ? filemtime($targetFile) : time();
            $suffix = '?v=' . $ts;
        } else {
            $suffix = '';
        }

        // Relative link will always work, whatever the base uri set in the admin
        if (Context::getContext()->controller->controller_type == 'admin') {
            return '../img/tmp/'.$cacheImage . $suffix;
        } else {
            return _PS_TMP_IMG_.$cacheImage . $suffix;
        }
    }

    /**
     * Returns file name of product image thumbnail
     *
     * @param int $imageId
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getProductImageThumbnailFileName($imageId)
    {
        return 'image_mini_'.(int) $imageId . '.'.ImageManager::getDefaultImageExtension();
    }

    /**
     * Return path to product image thumbnail
     *
     * @param int $imageId
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getProductImageThumbnailFilePath($imageId)
    {
        return _PS_TMP_IMG_DIR_ . static::getProductImageThumbnailFileName($imageId);
    }

    /**
     * Deletes product image thumbnail, if exists
     *
     * @param int $imageId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function deleteProductImageThumbnail($imageId)
    {
        $path = static::getProductImageThumbnailFilePath($imageId);
        if ($path && file_exists($path)) {
            return @unlink($path);
        }
        return false;
    }

    /**
     * @param int $imageId
     * @param bool $disableCache
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function getProductImageThumbnailTag($imageId, $disableCache=true)
    {
        $imageId = (int)$imageId;
        if ($imageId) {
            $sourceFile = '';
            foreach (ImageManager::getAllowedImageExtensions(false, true) as $imageExtension) {
                if (file_exists($sourceFile = _PS_PROD_IMG_DIR_ . Image::resolveFilePath($imageId, $imageExtension))) {
                    break;
                }
            }

            $name = static::getProductImageThumbnailFileName($imageId);
            return static::thumbnail($sourceFile, $name, 45, null, $disableCache);
        }
        return '';
    }

    /**
     * Check if memory limit is too long or not
     *
     * @param string $image
     *
     * @return bool
     */
    public static function checkImageMemoryLimit($image)
    {
        $infos = @getimagesize($image);

        if (!is_array($infos) || !isset($infos['bits'])) {
            return true;
        }

        $memoryLimit = Tools::getMemoryLimit();
        // memory_limit == -1 => unlimited memory
        if ((int) $memoryLimit != -1) {
            $currentMemory = memory_get_usage();
            $bits = $infos['bits'] / 8;
            $channel = $infos['channels'] ?? 1;

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
     * @param string $srcFile Image object from $_FILE
     * @param string $dstFile Destination filename
     * @param int $dstWidth Desired width (optional)
     * @param int $dstHeight Desired height (optional)
     * @param string $imageExtension
     * @param bool $forceType
     * @param int $error
     * @param int $tgtWidth
     * @param int $tgtHeight
     * @param int $quality
     * @param int $srcWidth
     * @param int $srcHeight
     *
     * @return bool Operation result
     *
     * @throws PrestaShopException
     */
    public static function resize(
        $srcFile,
        $dstFile,
        $dstWidth = null,
        $dstHeight = null,
        $imageExtension = null,
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

        if (is_null($imageExtension)) {
            // try to detect extension from target file name
            $imageExtension = static::getImageExtensionFromFilename($dstFile);
            if (! $imageExtension) {
                // fallback to system default extension
                $imageExtension = static::getDefaultImageExtension();
            }
        }

        list($tmpWidth, $tmpHeight, $type) = getimagesize($srcFile);

        $srcWidth = (int)$tmpWidth;
        $srcHeight = (int)$tmpHeight;

        if (!$srcWidth) {
            return !($error = static::ERROR_FILE_WIDTH);
        }

        $dstWidth = (int)$dstWidth;
        if (!$dstWidth) {
            $dstWidth = $srcWidth;
        }

        $dstHeight = (int)$dstHeight;
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
                $nextHeight = (int) $dstHeight;
                $nextWidth = (int) round(($srcWidth * $nextHeight) / $srcHeight);
                $dstWidth = (int) (!$psImageGenerationMethod ? $dstWidth : $nextWidth);
            } else {
                $nextWidth = (int) $dstWidth;
                $nextHeight = (int) round($srcHeight * $dstWidth / $srcWidth);
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
        if ($imageExtension == 'png' || $imageExtension === 'webp' || $imageExtension === 'avif') {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
            imagefilledrectangle($destImage, 0, 0, $dstWidth, $dstHeight, $transparent);
        } else {
            $white = imagecolorallocate($destImage, 255, 255, 255);
            imagefilledrectangle($destImage, 0, 0, $dstWidth, $dstHeight, $white);
        }

        $srcImage = ImageManager::create($type, $srcFile);

        if (! $srcImage) {
            return false;
        }

        if ($dstWidth >= $srcWidth && $dstHeight >= $srcHeight) {
            imagecopyresized(
                $destImage,
                $srcImage,
                (int) (($dstWidth - $nextWidth) / 2),
                (int) (($dstHeight - $nextHeight) / 2),
                0,
                0,
                $nextWidth,
                $nextHeight,
                $srcWidth,
                $srcHeight
            );
        } else {
            imagecopyresampled(
                $destImage,
                $srcImage,
                (int) (($dstWidth - $nextWidth) / 2),
                (int) (($dstHeight - $nextHeight) / 2),
                0,
                0,
                $nextWidth,
                $nextHeight,
                $srcWidth,
                $srcHeight
            );
        }
        $writeFile = ImageManager::write($imageExtension, $destImage, $dstFile);
        @imagedestroy($srcImage);

        return $writeFile;
    }

    /**
     * Create an image with GD extension from a given type
     *
     * @param string $type
     * @param string $filename
     *
     * @return false|GdImage|resource
     */
    public static function create($type, $filename)
    {
        // avif is supported from PHP8.1 only
        if (! defined('IMAGETYPE_AVIF')) {
            define('IMAGETYPE_AVIF', 19);
        }

        switch ($type) {
            case IMAGETYPE_GIF :
                $resource = imagecreatefromgif($filename);
                imagepalettetotruecolor($resource); // Otherwise gif to webp can lead in fatal error
                return $resource;

            case IMAGETYPE_PNG :
                return imagecreatefrompng($filename);

            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($filename);

            case IMAGETYPE_JPEG :
                return imagecreatefromjpeg($filename);

            case IMAGETYPE_AVIF:
                return function_exists('imagecreatefromavif')
                    ? imagecreatefromavif($filename)
                    : false;

            default:
                return false;
        }
    }

    /**
     * @param GdImage $dstImage
     * @param GdImage $srcImage
     * @param int $dstX
     * @param int $dstY
     * @param int $srcX
     * @param int $srcY
     * @param int $dstW
     * @param int $dstH
     * @param int $srcW
     * @param int $srcH
     * @param int $quality
     *
     * @return bool
     *
     * @deprecated 1.4.0
     */
    public static function imagecopyresampled($dstImage, $srcImage, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH, $quality = 3)
    {
        Tools::displayAsDeprecated();
        return imagecopyresampled($dstImage, $srcImage, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
    }

    /**
     * Generate and write image
     *
     * @param string $imageExtension
     * @param GdImage|resource $resource
     * @param string $filename
     * @param int $quality
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function write($imageExtension, $resource, $filename, $quality = null)
    {
        if (is_null($quality)) {
            $quality = Configuration::get('TB_IMAGE_QUALITY') ?: 90;
        }

        if (!Validate::isInt($quality) || $quality<=0 || $quality>100) {
            throw new PrestaShopException("Image quality value needs to be between 0 and 100!");
        }

        if (!in_array($imageExtension, self::getAllowedImageExtensions(false, true))) {
            throw new PrestaShopException("The image extensions {$imageExtension} is not supported!");
        }

        switch ($imageExtension) {
            case 'gif':
                $success = imagegif($resource, $filename);
                break;

            case 'png':
                // PNG compression (0 => biggest file, 9 => smallest file)
                // This little mechanism transforms 0-100 range to a sensible compression value
                $quality *= -1;
                $quality += 100;
                $quality /= 10;

                $success = imagepng($resource, $filename, (int) $quality);
                break;

            case 'webp':
                $success = imagewebp($resource, $filename, (int) $quality);
                break;

            case 'avif':
                $success = function_exists('imageavif')
                    ? imageavif($resource, $filename, $quality)
                    : false;
                break;

            case 'jpg':
            case 'jpeg':
            default:
                imageinterlace($resource, 1); /// make it PROGRESSIVE
                $success = imagejpeg($resource, $filename, (int) $quality);
                break;
        }
        imagedestroy($resource);

        if (@file_exists(@$filename)) {
            @chmod($filename, 0664);
        }

        return $success;
    }

    /**
     * Copy and convert an image file
     *
     * @param string $sourceImage
     * @param string $newImageExtension
     * @param string $newImageDest
     * @param bool   $unlinkOldImage
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function convertImageToExtension($sourceImage, $newImageExtension, $newImageDest = '', $unlinkOldImage = false, &$error = 0)
    {
        if (empty($info = getimagesize($sourceImage)) || empty($info[2])) {
            $error = ImageManager::ERROR_FILE_NOT_EXIST;
            return false;
        }

        if (!ImageManager::checkImageMemoryLimit($sourceImage)) {
            $error = static::ERROR_MEMORY_LIMIT;
            return false;
        }

        if (!in_array($newImageExtension, self::getAllowedImageExtensions(false, true))) {
            $error = static::ERROR_FORBIDDEN_IMAGE_EXTENSION;
            return false;
        }

        if ($resource = self::create($info[2], $sourceImage)) {

            if (!$newImageDest) {
                $oldImageExtension = pathinfo($sourceImage, PATHINFO_EXTENSION);
                $newImageDest = str_replace('.'.$oldImageExtension, '.'.$newImageExtension, $sourceImage);
            }

            // Note: when we copy/convert an image, we don't want to lose quality
            if (self::write($newImageExtension, $resource, $newImageDest, 100)) {
                if ($unlinkOldImage) {
                    unlink($sourceImage);
                }
                @imagedestroy($resource);
                return true;
            }
        }

        return false;
    }

    /**
     * Regenerate images for one entity
     *
     * @param string $entityType
     * @param int $idEntity
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function generateImageTypesByEntity($entityType, $idEntity, $idsImage = [])
    {
        $imageEntity = ImageEntity::getImageEntityInfo($entityType);
        if (! $imageEntity) {
            return false;
        }

        $imageTypes = $imageEntity['imageTypes'];
        if (! $imageTypes) {
            return false;
        }

        // Get all source image paths, that are related to this entity
        $possibleSourceImages = [];

        if ($entityType==ImageEntity::ENTITY_TYPE_PRODUCTS) {
            if (empty($idsImage)) {
                $idsImage = array_column(Image::getImages(null, $idEntity), 'id_image');
            }
            foreach ($idsImage as $idImage) {
                $possibleSourceImages[] = [
                    'path' => $imageEntity['path'].Image::getImgFolderStatic($idImage),
                    'filename' => $idImage,
                ];
            }
        }
        else {
            $possibleSourceImages[] = [
                'path' => $imageEntity['path'],
                'filename' => $idEntity,
            ];
        }

        $watermarkModules = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('m.`name`')
                ->from('module', 'm')
                ->leftJoin('hook_module', 'hm', 'hm.`id_module` = m.`id_module`')
                ->leftJoin('hook', 'h', 'hm.`id_hook` = h.`id_hook`')
                ->where('h.`name` = \'actionWatermark\'')
                ->where('m.`active` = 1')
        );

        // Loop through all possible source image paths
        $success = true;

        foreach ($possibleSourceImages as $possibleSourceImage) {

            ImageManager::cleanSourceImage($possibleSourceImage['path'], $possibleSourceImage['filename']);

            // Check if the image does really exist
            if ($sourceImage = ImageManager::getSourceImage($possibleSourceImage['path'], $possibleSourceImage['filename'])) {
                list($sourceWidth, $sourceHeight) = getimagesize($sourceImage);
                $baseName = pathinfo($sourceImage, PATHINFO_DIRNAME) . '/' . pathinfo($sourceImage, PATHINFO_FILENAME);
                $defaultImageExtension = ImageManager::getDefaultImageExtension();

                foreach ($imageTypes as $imageType) {

                    // Check if imageType is alias
                    if ($imageType['id_image_type_parent']) {
                        continue;
                    }

                    $dstFile = $baseName . '-' . stripslashes($imageType['name']) . '.' . $defaultImageExtension;
                    $success = self::resize($sourceImage, $dstFile, $imageType['width'], $imageType['height'], $defaultImageExtension) && $success;

                    // Only generate if size of sourceImage is big enough
                    if (self::retinaSupport() && (($sourceWidth >= $imageType['width'] * 2) || ($sourceHeight >= $imageType['height'] * 2))) {
                        $dstFileRetina = $baseName . '-' . stripslashes($imageType['name']) . '2x.' . $defaultImageExtension;
                        $success = self::resize($sourceImage, $dstFileRetina, $imageType['width'] * 2, $imageType['height'] * 2, $defaultImageExtension) && $success;
                    }
                }

                // Call actionWatermark hook
                if (is_array($watermarkModules) && count($watermarkModules) && ($entityType == ImageEntity::ENTITY_TYPE_PRODUCTS)) {
                    foreach ($watermarkModules as $module) {
                        $moduleInstance = Module::getInstanceByName($module['name']);
                        if ($moduleInstance && is_callable([$moduleInstance, 'hookActionWatermark'])) {
                            call_user_func([$moduleInstance, 'hookActionWatermark'], [
                                'id_image' => $possibleSourceImage['filename'],
                                'id_product' => $idEntity,
                                'image_type' => $imageTypes,
                            ]);
                        }
                    }
                }
            }
            else {
                if ($entityType === ImageEntity::ENTITY_TYPE_PRODUCTS) {
                    // Note: for other entity types, we don't even know if we can expect an image
                    throw new PrestaShopException("Source file in {$possibleSourceImage['path']} is missing!");
                }
            }

        }

        return $success;
    }

    /**
     * Validate image upload (check image type and weight)
     *
     * @param array $file Upload $_FILE value
     * @param int $maxFileSize Maximum upload size
     * @param string[] $allowedExtensions allowed image extensions
     *
     * @return bool|string Return false if no error encountered
     */
    public static function validateUpload($file, $maxFileSize = 0, $allowedExtensions = null)
    {
        if ((int) $maxFileSize > 0 && $file['size'] > (int) $maxFileSize) {
            return sprintf(Tools::displayError('Image is too large (%1$d kB). Maximum allowed: %2$d kB'), $file['size'] / 1024, $maxFileSize / 1024);
        }
        if ($file['error']) {
            return Tools::decodeUploadError($file['error']);
        }
        if (!ImageManager::isRealImage($file['tmp_name'], $file['type']) ||
            !ImageManager::isCorrectImageFileExt($file['name'], $allowedExtensions) ||
            preg_match('/%00/', $file['name'])
        ) {
            return Tools::displayError('Image format not recognized, allowed formats are: ').implode(', ',self::getAllowedImageExtensions());
        }
        return false;
    }

    /**
     * Check if file is a real image
     *
     * @param string $filename File path to check
     * @param string $fileMimeType File known mime type (generally from $_FILES)
     * @param array $mimeTypeList Allowed MIME types
     *
     * @return bool
     */
    public static function isRealImage($filename, $fileMimeType = null, $mimeTypeList = null)
    {
        // Detect mime content type
        $mimeType = false;

        if (!$mimeTypeList) {
            foreach (Media::getFileInformations('images') as $imageFileInfo) {
                $mimeTypeList[] = $imageFileInfo['mimeType'];
            }
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
     * @param string $filename Real filename
     * @param array|null $allowedExtensions
     *
     * @return bool True if it's correct
     */
    public static function isCorrectImageFileExt($filename, $allowedExtensions = null)
    {
        // Filter on file extension
        if ($allowedExtensions === null) {
            $allowedExtensions = static::getAllowedImageExtensions();
        }

        $extension = pathinfo((string)$filename, PATHINFO_EXTENSION);
        return in_array($extension, $allowedExtensions);
    }

    /**
     * Validate icon upload
     *
     * @param array $file Upload $_FILE value
     * @param int $maxFileSize Maximum upload size
     *
     * @return bool|string Return false if no error encountered
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
     * @param string $srcFile Origin filename
     * @param string $dstFile Destination filename
     * @param int $dstWidth Desired width
     * @param int $dstHeight Desired height
     * @param string $imageExtension
     * @param int $dstX
     * @param int $dstY
     *
     * @return bool Operation result
     *
     * @throws PrestaShopException
     */
    public static function cut($srcFile, $dstFile, $dstWidth = null, $dstHeight = null, $imageExtension = null, $dstX = 0, $dstY = 0)
    {
        if (!file_exists($srcFile)) {
            return false;
        }

        if (is_null($imageExtension)) {
            $imageExtension = ImageManager::getDefaultImageExtension();
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
        $return = ImageManager::write($imageExtension, $dest['ressource'], $dstFile);
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
     */
    public static function getMimeTypeByExtension($fileName)
    {
        $imageExtensionInfos = Media::getFileInformations('images');
        $imageExtension = substr($fileName, strrpos($fileName, '.') + 1);

        $mimeType = null;

        foreach ($imageExtensionInfos as $imageExtensionInfo) {
            if (in_array($imageExtension, $imageExtensionInfo)) {
                $mimeType = $imageExtensionInfo['mimeType'];
                break;
            }
        }

        if ($mimeType === null) {
            $mimeType = 'image/jpeg';
        }

        return $mimeType;
    }


    /**
     * Returns an array of image extensions depending on filters
     *
     * @param bool $returnMainExtensions Main extensions are for example, jpg, png, bmp, but NOT png-X or jpeg
     * @param null|bool $imageSupport Only returns image extensions, that can be generated by thirty bees (jpg, png, gif, webp)
     * @param null|bool $uploadFrontOffice Are customers allowed to upload this extension in FO?
     * @param null|bool $uploadBackOffice Are merchants allowed to upload this extension in BO?
     *
     * @return array
     */
    public static function getAllowedImageExtensions($returnMainExtensions = false, $imageSupport = null, $uploadFrontOffice = null, $uploadBackOffice = null)
    {
        $imageExtensions = Media::getFileInformations('images');
        $returnHelper = [];

        foreach ($imageExtensions as $mainExtension => $imageExtension) {
            if (
                (is_null($imageSupport) || $imageSupport==$imageExtension['imageSupport']) &&
                (is_null($uploadFrontOffice) || $uploadFrontOffice==$imageExtension['uploadFrontOffice']) &&
                (is_null($uploadBackOffice) || $uploadBackOffice==$imageExtension['uploadBackOffice'])
            ) {
                if ($returnMainExtensions) {
                    $returnHelper[] = $mainExtension;
                }
                else {
                    $returnHelper = array_merge($returnHelper, $imageExtension['extensions']);
                }
            }
        }
        return $returnHelper;
    }


    /**
     *
     * @return string returns either jpg|png|gif|webp
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getDefaultImageExtension()
    {
        $imageExtension = Configuration::get('TB_IMAGE_EXTENSION');
        if (! $imageExtension) {
            $imageExtension = 'jpg';
        }
        return $imageExtension;
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
     * @param array $sizes Optional. An array of sizes (each size is an array with a width and height) that the source image should be rendered at in the generated ICO file. If sizes are not supplied, the size of the source image will be used.
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

        if (! getimagesize($source)) {
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
            $width = (int)$width;
            $height = (int)$height;
            $new_im = imagecreatetruecolor( $width, $height );
            imagecolortransparent( $new_im, imagecolorallocatealpha( $new_im, 0, 0, 0, 127 ) );
            imagealphablending( $new_im, false );
            imagesavealpha( $new_im, true );
            $source_width = imagesx( $im );
            $source_height = imagesy( $im );
            if ( false === imagecopyresampled( $new_im, $im, 0, 0, 0, 0, $width, $height, $source_width, $source_height ) ) {
                continue;
            }

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
        if (!is_array($images) || empty($images)) {
            return false;
        }
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
     * Returns true, if webp images can be used for current request
     *
     * @return bool
     */
    public static function webpSupport()
    {
        static $supported = null;

        if ($supported === null) {
            $supported = (
                static::getWebpPreference() === static::USE_WEBP &&
                static::serverSupportsWebp()
            );
        }

        return $supported;
    }

    /**
     * Returns true, if browser that initiated request supports webp images
     *
     * @return bool
     */
    public static function browserSupportsWebp()
    {
        if (array_key_exists('HTTP_ACCEPT', $_SERVER)) {
            return strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
        }
        return false;
    }

    /**
     * Returns true, if server supports webp images
     *
     * @return bool
     */
    public static function serverSupportsWebp()
    {
        return (bool)function_exists('imagewebp');
    }

    /**
     * Returns true, if server supports avif images
     *
     * @return bool
     */
    public static function serverSupportsAvif()
    {
        return (bool)function_exists('imageavif');
    }

    /**
     * Returns true, if webp images should be generated. That does not necessary mean that webp images
     * will be used by store
     *
     * @return bool
     *
     * @deprecated 1.5.0 This specific webp function is obsolete, since we support webp consistently
     */
    public static function generateWebpImages()
    {
        $preference = static::getWebpPreference();
        return ($preference === static::USE_WEBP);
    }

    /**
     * Returns current webp settings preference
     *
     * @return int
     */
    protected static function getWebpPreference()
    {
        try {
            return (int)(Configuration::get('TB_IMAGE_EXTENSION')=='webp');
        } catch (PrestaShopException $e) {
            return static::DO_NOT_USE_WEBP;
        }
    }

    /**
     * @return bool
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

    /**
     * Important function to convert core image files in "wrong" format. Also needed when merchant switches image extension.
     * Typical example: orderStatus icons. Originally they are stored in gif, but in lists they are needed in configured image extension.
     *
     * @param string $image
     *
     * @return string|null Returns full path to source image
     *
     */
    public static function tryRestoreImage($image)
    {
        if (! $image) {
            return null;
        }

        if (@file_exists($image)) {
            return $image;
        }

        $baseSourcePath = pathinfo($image, PATHINFO_DIRNAME) . '/' . pathinfo($image, PATHINFO_FILENAME);

        foreach (ImageManager::getAllowedImageExtensions(false, true) as $imageExtension) {
            $sourcePath = $baseSourcePath . '.' . $imageExtension;
            if (@file_exists($sourcePath)) {
                return $sourcePath;
            }
        }

        return null;
    }

    /**
     * Get a source file in any extension
     *
     * @param string $path // full folder path
     * @param string $filename // expected filename without any image extension (often this is just the $idEntity)
     * @param null $expectedImageExtension // Only set this, if you have clear idea in which extension the source file is available
     * @param bool $convertImage // If an image is not existing in the configured image extension, should we convert it?
     *
     * @return string Returns full path to source image
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getSourceImage($path, $filename, $expectedImageExtension = null, $convertImage = true)
    {
        $defaultImageExtension = Configuration::get('TB_IMAGE_EXTENSION');

        // First check default imageExtension (saving time)
        if (!$expectedImageExtension) {
            $expectedImageExtension = $defaultImageExtension;
        }

        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }

        // Check if image in expected extension is available (ideal situation)
        if (file_exists($path . $filename . '.' . $expectedImageExtension)) {
            return $path . $filename . '.' . $expectedImageExtension;
        }

        // Image is not available in the expected extension
        $sourceImage = false;
        $foundExtension = false;

        foreach (ImageManager::getAllowedImageExtensions(false, true) as $imageExtension) {
            if ($imageExtension != $expectedImageExtension && file_exists($path . $filename . '.' . $imageExtension)) {
                $sourceImage = $path . $filename . '.' . $imageExtension;
                $foundExtension = $imageExtension;
                break;
            }
        }

        // Check if we should convert the found sourceImage
        if ($convertImage) {
            $imageConversion = Configuration::get('TB_IMAGE_CONVERSION');

            if (
                $foundExtension &&
                $foundExtension != $defaultImageExtension &&
                ($imageConversion == 'converted' || $imageConversion == 'both')
            ) {
                $unlinkOldImage = ($imageConversion=='converted') && ($defaultImageExtension!=Configuration::get('TB_IMAGE_EXTENSION'));
                ImageManager::convertImageToExtension($sourceImage, $defaultImageExtension, '', $unlinkOldImage);
            }
        }

        return $sourceImage;
    }

    /**
     * Removing unnecessary source image extensions
     *
     * @param string $path // full folder path
     * @param string $filename // expected filename without any image extension (often this is just the $idEntity)
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function cleanSourceImage($path, $filename)
    {

        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }

        $imageConversion = Configuration::get('TB_IMAGE_CONVERSION');

        if ($imageConversion == 'converted') {

            // Making 100% sure, that source file is available in correct extension
            $source_file_to_hold = ImageManager::getSourceImage($path, $filename);

            foreach (ImageManager::getAllowedImageExtensions(false, true) as $imageExtension) {
                $source_file_to_check = $path.$filename.'.'.$imageExtension;

                if ($source_file_to_check!=$source_file_to_hold && $imageExtension!=ImageManager::getDefaultImageExtension()) {
                    if (file_exists($source_file_to_check)) {
                        unlink($source_file_to_check);
                    }
                }
            }

        }
    }

    /**
     * Resolves valid image extension from filepath. File does not need to exits -- extension is extracted from name
     * only
     *
     * @param string $filepath
     *
     * @return string|null
     */
    protected static function getImageExtensionFromFilename(string $filepath)
    {
        $extension = strtolower((string)pathinfo($filepath, PATHINFO_EXTENSION));
        if ($extension) {
            $allowedExtensions = static::getAllowedImageExtensions(true, true);
            if (in_array($extension, $allowedExtensions)) {
                return $extension;
            }
        }
        return null;
    }

    /**
     * Resolves valid image extension from filepath. File have to exists - image extension is resolved from file content
     *
     * @return string|null
     */
    public static function getImageExtension(string $filepath)
    {
        $imageInfo = @getimagesize($filepath);
        if (! $imageInfo) {
            return null;
        }

        $mimeType = $imageInfo['mime'] ?? null;
        if (! $mimeType) {
            return null;
        }

        // Detect mime content type
        foreach (Media::getFileInformations('images') as $ext => $imageFileInfo) {
            if (strstr($mimeType, $imageFileInfo['mimeType'])) {
                return $ext;
            }
        }
        return null;
    }
}
