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

use CoreUpdater\TableSchema;

/**
 * Class ImageCore
 */
class ImageCore extends ObjectModel
{
    /** @var int access rights of created folders (octal) */
    protected static $access_rights = 0775;
    /** @var array $_cacheGetSize */
    protected static $_cacheGetSize = [];
    /** @var int Image ID */
    public $id_image;
    /** @var int Product ID */
    public $id_product;
    /** @var int Position used to order images of the same product */
    public $position;
    /** @var bool Image is cover */
    public $cover;
    /** @var string|string[] Legend */
    public $legend;
    /** @var string image extension */
    public $image_format;
    /** @var string path to index.php file to be copied to new image folders */
    public $source_index;
    /** @var string image folder */
    protected $folder;
    /** @var string image path without extension */
    protected $existing_path;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'     => 'image',
        'primary'   => 'id_image',
        'multilang' => true,
        'fields'    => [
            'id_product' => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId', 'required' => true],
            'position'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'smallint(2) unsigned', 'dbDefault' => '0'],
            'cover'      => ['type' => self::TYPE_BOOL, 'allow_null' => true, 'validate' => 'isBool', 'shop' => true],
            'legend'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
        ],
        'keys' => [
            'image' => [
                'idx_product_image' => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['id_image', 'id_product', 'cover']],
                'id_product_cover'  => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['id_product', 'cover']],
                'image_product'     => ['type' => ObjectModel::KEY, 'columns' => ['id_product']],
            ],
            'image_lang' => [
                'id_image' => ['type' => ObjectModel::KEY, 'columns' => ['id_image']],
            ],
            'image_shop' => [
                'id_product' => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['id_product', 'id_shop', 'cover']],
                'id_shop'    => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
            ],
        ],
    ];

    /**
     * ImageCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id, $idLang);
        $this->image_dir = _PS_PROD_IMG_DIR_;
        $this->source_index = _PS_PROD_IMG_DIR_.'index.php';
        $this->image_format = ImageManager::getDefaultImageExtension();
    }

    /**
     * Return first image (by position) associated with a product attribute
     *
     * @param int $idShop Shop ID
     * @param int $idLang Language ID
     * @param int $idProduct Product ID
     * @param int $idProductAttribute Product Attribute ID
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getBestImageAttribute($idShop, $idLang, $idProduct, $idProductAttribute)
    {
        $cacheId = 'Image::getBestImageAttribute'.'-'.(int) $idProduct.'-'.(int) $idProductAttribute.'-'.(int) $idLang.'-'.(int) $idShop;

        if (!Cache::isStored($cacheId)) {
            $row = Db::readOnly()->getRow(
                (new DbQuery())
                    ->select('image_shop.`id_image` id_image, il.`legend`')
                    ->from('image', 'i')
                    ->innerJoin('image_shop', 'image_shop', 'i.`id_image` = image_shop.`id_image` AND image_shop.`id_shop` = '.(int) $idShop)
                    ->innerJoin('product_attribute_image', 'pai', 'pai.`id_image` = i.`id_image` AND pai.`id_product_attribute` = '.(int) $idProductAttribute)
                    ->leftJoin('image_lang', 'il', 'image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $idLang)
                    ->where('i.`id_product` = '.(int) $idProduct)
                    ->orderBy('i.`position` ASC')
            );

            Cache::store($cacheId, $row);
        } else {
            $row = Cache::retrieve($cacheId);
        }

        return $row;
    }

    /**
     * Return available images for a product
     *
     * @param int|null $idLang Language ID. Null/0/false = all languages.
     * @param int $idProduct Product ID
     * @param int $idProductAttribute Product Attribute ID
     *
     * @return array Images
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getImages($idLang, $idProduct, $idProductAttribute = null)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('image', 'i');
        $sql->where('i.`id_product` = '.(int) $idProduct);
        if ($idLang) {
            $sql->leftJoin('image_lang', 'il', 'i.`id_image` = il.`id_image`');
            $sql->where('il.`id_lang` = '.(int) $idLang);
        }
        if ($idProductAttribute) {
            $sql->leftJoin('product_attribute_image', 'ai', 'i.`id_image` = ai.`id_image`');
            $sql->where('ai.`id_product_attribute` = '.(int) $idProductAttribute);
        }
        $sql->orderBy('i.`position` ASC');

        return Db::readOnly()->getArray($sql);
    }

    /**
     * Check if a product has an image available
     *
     * @param int $idLang Language ID. Null/0/false = all languages.
     * @param int $idProduct Product ID
     * @param int $idProductAttribute Product Attribute ID
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function hasImages($idLang, $idProduct, $idProductAttribute = null)
    {
        $sql = new DbQuery();
        $sql->select('1');
        $sql->from('image', 'i');
        $sql->where('i.`id_product` = '.(int) $idProduct);
        if ($idLang) {
            $sql->leftJoin('image_lang', 'il', 'i.`id_image` = il.`id_image`');
            $sql->where('il.`id_lang` = '.(int) $idLang);
        }
        if ($idProductAttribute) {
            $sql->leftJoin('product_attribute_image', 'ai', 'i.`id_image` = ai.`id_image`');
            $sql->where('ai.`id_product_attribute` = '.(int) $idProductAttribute);
        }

        return (bool) Db::readOnly()->getValue($sql);
    }

    /**
     * Return Images
     *
     * @return array Images
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getAllImages()
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_image`, `id_product`')
                ->from('image')
                ->orderBy('`id_image` ASC')
        );
    }

    /**
     * Return number of images for a product
     *
     * @param int $idProduct Product ID
     *
     * @return int number of images
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getImagesTotal($idProduct)
    {
        $result = Db::readOnly()->getRow(
            (new DbQuery())
                ->select('COUNT(`id_image`) AS `total`')
                ->from('image')
                ->where('`id_product` = '.(int) $idProduct)
        );

        return $result['total'];
    }

    /**
     * Delete product cover
     *
     * @param int $idProduct Product ID
     *
     * @return bool result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function deleteCover($idProduct)
    {
        $conn = Db::getInstance();
        return ($conn->update(
        'image',
            [
                'cover' => ['type' => 'sql', 'value' => 'NULL'],
            ],
            '`id_product` = '.(int) $idProduct,
            0,
            true
        ) &&
        $conn->update(
            'image_shop',
            [
                'cover' => ['type' => 'sql', 'value' => 'NULL'],
            ],
            '`id_shop` IN ('.implode(',', array_map('intval', Shop::getContextListShopID())).') AND `id_product` = '.(int) $idProduct
        ));
    }

    /**
     *Get product cover
     *
     * @param int $idProduct Product ID
     *
     * @return array|false result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCover($idProduct)
    {
        return Db::readOnly()->getRow(
            (new DbQuery())
                ->select('*')
                ->from('image_shop')
                ->where('`id_product` = '.(int) $idProduct)
                ->where('`cover` = 1')
        );
    }

    /**
     * Get global product cover
     *
     * @param int $idProduct Product ID
     *
     * @return array|false result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getGlobalCover($idProduct)
    {
        return Db::readOnly()->getRow(
            (new DbQuery())
                ->select('*')
                ->from('image', 'i')
                ->where('i.`id_product` = '.(int) $idProduct)
                ->where('i.`cover` = 1')
        );
    }

    /**
     * Copy images from a product to another
     *
     * @param int $idProductOld Source product ID
     * @param bool $idProductNew Destination product ID
     * @param array $combinationImages
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicateProductImages($idProductOld, $idProductNew, $combinationImages)
    {
        $imageTypes = ImageType::getImagesTypes(ImageEntity::ENTITY_TYPE_PRODUCTS);
        $imageExtension = ImageManager::getDefaultImageExtension();
        $result = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_image`')
                ->from('image')
                ->where('`id_product` = '.(int) $idProductOld)
        );
        foreach ($result as $row) {
            $imageOld = new Image($row['id_image']);
            $imageNew = clone $imageOld;
            unset($imageNew->id);
            $imageNew->id_product = (int) $idProductNew;

            // A new id is generated for the cloned image when calling add()
            if ($imageNew->add()) {
                $newPath = $imageNew->getPathForCreation();
                foreach ($imageTypes as $imageType) {
                    if (file_exists(_PS_PROD_IMG_DIR_.$imageOld->getExistingImgPath().'-'.$imageType['name'].'.'.$imageExtension)) {
                        $imageNew->createImgFolder();
                        copy(
                            _PS_PROD_IMG_DIR_.$imageOld->getExistingImgPath().'-'.$imageType['name'].'.'.$imageExtension,
                            $newPath.'-'.$imageType['name'].'.'.$imageExtension
                        );
                        if (Configuration::get('WATERMARK_HASH')) {
                            $oldImagePath = _PS_PROD_IMG_DIR_.$imageOld->getExistingImgPath().'-'.$imageType['name'].'-'.Configuration::get('WATERMARK_HASH').'.'.$imageExtension;
                            if (file_exists($oldImagePath)) {
                                copy($oldImagePath, $newPath.'-'.$imageType['name'].'-'.Configuration::get('WATERMARK_HASH').'.'.$imageExtension);
                            }
                        }
                    }
                }

                if ($sourceFile = ImageManager::getSourceImage(_PS_PROD_IMG_DIR_.$imageOld->getImgFolder(), $imageOld->id)) {
                    copy($sourceFile, $newPath.'.'.$imageExtension);
                }

                static::replaceAttributeImageAssociationId($combinationImages, (int) $imageOld->id, (int) $imageNew->id);

                // Duplicate shop associations for images
                $imageNew->duplicateShops($idProductOld);
            } else {
                return false;
            }
        }

        return Image::duplicateAttributeImageAssociations($combinationImages);
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if ($this->position <= 0) {
            $this->position = Image::getHighestPosition($this->id_product) + 1;
        }

        if ($this->cover) {
            $this->cover = 1;
        } else {
            $this->cover = null;
        }

        return parent::add($autoDate, $nullValues);
    }

    /**
     * Return highest position of images for a product
     *
     * @param int $idProduct Product ID
     *
     * @return int highest position of images
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getHighestPosition($idProduct)
    {
        $result = Db::readOnly()->getRow(
            (new DbQuery())
                ->select('MAX(`position`) AS `max`')
                ->from('image')
                ->where('`id_product` = '.(int) $idProduct)
        );

        return $result['max'];
    }

    /**
     * Returns the path where a product image should be created (without file format)
     *
     * @return string path
     */
    public function getPathForCreation()
    {
        if (!$this->id) {
            return false;
        }
        $path = $this->getImgPath();
        $this->createImgFolder();

        return _PS_PROD_IMG_DIR_.$path;
    }

    /**
     * Create parent folders for the image in the new filesystem
     *
     * @return bool success
     */
    public function createImgFolder()
    {
        if (!$this->id) {
            return false;
        }

        if (!file_exists(_PS_PROD_IMG_DIR_.$this->getImgFolder())) {
            // Apparently sometimes mkdir cannot set the rights, and sometimes chmod can't. Trying both.
            $success = @mkdir(_PS_PROD_IMG_DIR_.$this->getImgFolder(), static::$access_rights, true);
            $chmod = @chmod(_PS_PROD_IMG_DIR_.$this->getImgFolder(), static::$access_rights);

            // Create an index.php file in the new folder
            if (($success || $chmod)
                && !file_exists(_PS_PROD_IMG_DIR_.$this->getImgFolder().'index.php')
                && file_exists($this->source_index)
            ) {
                return @copy($this->source_index, _PS_PROD_IMG_DIR_.$this->getImgFolder().'index.php');
            }
        }

        return true;
    }

    /**
     * @param array $combinationImages
     * @param int $savedId
     * @param int $idImage
     */
    protected static function replaceAttributeImageAssociationId(&$combinationImages, $savedId, $idImage)
    {
        if (!isset($combinationImages['new']) || !is_array($combinationImages['new'])) {
            return;
        }
        foreach ($combinationImages['new'] as $idProductAttribute => $imageIds) {
            foreach ($imageIds as $key => $imageId) {
                if ((int) $imageId == (int) $savedId) {
                    $combinationImages['new'][$idProductAttribute][$key] = (int) $idImage;
                }
            }
        }
    }

    /**
     * Duplicate product attribute image associations
     *
     * @param array $combinationImages
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicateAttributeImageAssociations($combinationImages)
    {
        if (!isset($combinationImages['new']) || !is_array($combinationImages['new'])) {
            return true;
        }
        $insert = [];
        foreach ($combinationImages['new'] as $idProductAttribute => $imageIds) {
            foreach ($imageIds as $imageId) {
                $insert[] = [
                    'id_product_attribute' => (int) $idProductAttribute,
                    'id_image'             => (int) $imageId,
                ];
            }
        }

        return Db::getInstance()->insert('product_attribute_image', $insert);
    }

    /**
     * @param array $params
     * @param Smarty_Internal_Template $smarty
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getWidth($params, $smarty)
    {
        $result = static::getSize($params['type']);

        return $result['width'];
    }

    /**
     * @param string $type
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getSize($type)
    {
        $type = ImageType::getFormatedName($type);

        if (!isset(static::$_cacheGetSize[$type]) || static::$_cacheGetSize[$type] === null) {
            static::$_cacheGetSize[$type] = Db::readOnly()->getRow(
                (new DbQuery())
                    ->select('`width`, `height`')
                    ->from('image_type')
                    ->where('`name` = \''.pSQL($type).'\'')
            );
        }

        return static::$_cacheGetSize[$type];
    }

    /**
     * @param array $params
     * @param Smarty_Internal_Template $smarty
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getHeight($params, $smarty)
    {
        $result = static::getSize($params['type']);

        return $result['height'];
    }

    /**
     * Clear all images in tmp dir
     *
     * @return void
     */
    public static function clearTmpDir()
    {
        $imageFormats = implode('|', ImageManager::getAllowedImageExtensions(false, true));

        foreach (scandir(_PS_TMP_IMG_DIR_) as $d) {
            if (preg_match('/(.*)\.('.$imageFormats.')$/', $d)) {
                unlink(_PS_TMP_IMG_DIR_.$d);
            }
        }
    }

    /**
     * Recursively deletes all product images in the given folder tree and removes empty folders.
     *
     * @param string $path folder containing the product images to delete
     * @param array|string $formats image formats to delete
     *
     * @return bool success
     *
     * @throws PrestaShopException
     */
    public static function deleteAllImages($path = _PS_PROD_IMG_DIR_, $formats = null)
    {
        if (!$path || !is_dir($path)) {
            return false;
        }

        // normalize input variable $formats. It can either be null, string, or array
        if (is_null($formats)) {
            // all possible formats
            $formats = ImageManager::getAllowedImageExtensions(false, true);
        } elseif (is_string($formats)) {
            // single format provided
            $formats = [ $formats ];
        } elseif (! is_array($formats)) {
            return false;
        }

        // recursively delete files
        foreach (@scandir($path) as $file) {
            if (is_dir($path.$file)) {
                if (preg_match('/^[0-9]$/', $file)) {
                    Image::deleteAllImages($path.$file.'/', $formats);
                }
            } else {
                foreach ($formats as $format) {
                    if (preg_match('/^[0-9]+(\-(.*))?\.' . $format . '$/', $file)) {
                        @unlink($path . $file);
                    }
                }
            }
        }

        // Can we remove the image folder?
        if (is_numeric(basename($path))) {
            // delete image directory, if it's empty
            if (Tools::isDirectoryEmpty($path, ['index.php'])) {
                Tools::deleteDirectory($path);
            }
        }

        return true;
    }

    /**
     * Move all legacy product image files from the image folder root to their subfolder in the new filesystem.
     * If max_execution_time is provided, stops before timeout and returns string "timeout".
     * If any image cannot be moved, stops and returns "false"
     *
     * @param int $maxExecutionTime
     *
     * @return bool|string success or timeout
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function moveToNewFileSystem($maxExecutionTime = 0)
    {
        $startTime = time();
        $image = null;
        $tmpFolder = 'duplicates/';
        $imageFormats = implode('|', ImageManager::getAllowedImageExtensions(false, true));
        foreach (scandir(_PS_PROD_IMG_DIR_) as $file) {
            // matches the base product image or the thumbnails
            if (preg_match('/^([0-9]+\-)([0-9]+)(\-(.*))?\.('.$imageFormats.')$/', $file, $matches)) {
                // don't recreate an image object for each image type
                if (!$image || $image->id !== (int) $matches[2]) {
                    $image = new Image((int) $matches[2]);
                }
                // image exists in DB and with the correct product?
                if (Validate::isLoadedObject($image) && $image->id_product == (int) rtrim($matches[1], '-')) {
                    // create the new folder if it does not exist
                    if (!$image->createImgFolder()) {
                        return false;
                    }

                    // if there's already a file at the new image path, move it to a dump folder
                    // most likely the preexisting image is a demo image not linked to a product and it's ok to replace it
                    if ($newPath = ImageManager::getSourceImage(_PS_PROD_IMG_DIR_.$image->getImgFolder(), $image->id.(isset($matches[3]) ?? ''))) {
                        if (!file_exists(_PS_PROD_IMG_DIR_.$tmpFolder)) {
                            @mkdir(_PS_PROD_IMG_DIR_.$tmpFolder, static::$access_rights);
                            @chmod(_PS_PROD_IMG_DIR_.$tmpFolder, static::$access_rights);
                        }
                        $tmp_path = _PS_PROD_IMG_DIR_.$tmpFolder.basename($file);
                        if (!@rename($newPath, $tmp_path) || !file_exists($tmp_path)) {
                            return false;
                        }
                    }
                    // move the image
                    if (!@rename(_PS_PROD_IMG_DIR_.$file, $newPath) || !file_exists($newPath)) {
                        return false;
                    }
                }
            }
            if ((int) $maxExecutionTime != 0 && (time() - $startTime > (int) $maxExecutionTime - 4)) {
                return 'timeout';
            }
        }

        return true;
    }

    /**
     * Try to create and delete some folders to check if moving images to new file system will be possible
     *
     * @return bool success
     */
    public static function testFileSystem()
    {
        $folder1 = _PS_PROD_IMG_DIR_.'testfilesystem/';
        $testFolder = $folder1.'testsubfolder/';
        // check if folders are already existing from previous failed test
        if (file_exists($testFolder)) {
            @rmdir($testFolder);
            @rmdir($folder1);
        }
        if (file_exists($testFolder)) {
            return false;
        }

        @mkdir($testFolder, static::$access_rights, true);
        @chmod($testFolder, static::$access_rights);
        if (!is_writable($testFolder)) {
            return false;
        }
        @rmdir($testFolder);
        @rmdir($folder1);
        if (file_exists($folder1)) {
            return false;
        }

        return true;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        if ($this->cover) {
            $this->cover = 1;
        } else {
            $this->cover = null;
        }

        return parent::update($nullValues);
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }

        if ($this->hasMultishopEntries()) {
            return true;
        }

        if (!$this->deleteProductAttributeImage() || !$this->deleteImage()) {
            return false;
        }

        // update positions
        $conn = Db::getInstance();
        $conn->execute('SET @position:=0', false);
        $conn->execute(
            'UPDATE `'._DB_PREFIX_.'image` SET position=(@position:=@position+1)
									WHERE `id_product` = '.(int) $this->id_product.' ORDER BY position ASC'
        );

        return true;
    }

    /**
     * Delete Image - Product attribute associations for this image
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteProductAttributeImage()
    {
        return Db::getInstance()->delete('product_attribute_image', '`id_image` = '.(int) $this->id);
    }

    /**
     * Delete the product image from disk and remove the containing folder if empty
     * Handles both legacy and new image filesystems
     *
     * @param bool $forceDelete
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteImage($forceDelete = false, $path = '')
    {
        if (!$this->id) {
            return false;
        }

        $directory = $this->image_dir . $this->getImgFolder();
        if (!is_dir($directory)) {
            return true;
        }

        // delete all possible image formats
        $imageExtensions = ImageManager::getAllowedImageExtensions(false, true);
        $result = true;
        foreach ($imageExtensions as $imageExtension) {
            if (!$this->deleteImageFormat($imageExtension)) {
                $result = false;
            }
        }

        // delete image directory, if it's empty
        if (Tools::isDirectoryEmpty($directory, ['index.php'])) {
            Tools::deleteDirectory($directory);
        }

        ImageManager::deleteProductImageThumbnail($this->id);

        return $result;
    }

    /**
     * Delete the product images of given format from disk
     *
     * @param string $imageExtension
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function deleteImageFormat($imageExtension)
    {

        // Delete base image
        if (file_exists($this->image_dir.$this->getExistingImgPath().'.'.$imageExtension)) {
            if (! @unlink($this->image_dir.$this->getExistingImgPath().'.'.$imageExtension)) {
                return false;
            }
        }

        $filesToDelete = [];

        // Delete auto-generated images
        $imageTypes = ImageType::getImagesTypes();
        foreach ($imageTypes as $imageType) {
            $filesToDelete[] = $this->image_dir.$this->getExistingImgPath().'-'.$imageType['name'].'.'.$imageExtension;
            if (Configuration::get('WATERMARK_HASH')) {
                $filesToDelete[] = $this->image_dir.$this->getExistingImgPath().'-'.$imageType['name'].'-'.Configuration::get('WATERMARK_HASH').'.'.$imageExtension;
            }
        }

        // Delete watermark image
        $filesToDelete[] = $this->image_dir.$this->getExistingImgPath().'-watermark.'.$imageExtension;

        // perform delete
        $result = true;
        foreach ($filesToDelete as $file) {
            if (file_exists($file)) {
                $result = @unlink($file) && $result;
            }
        }

        return $result;
    }

    /**
     * Returns image path in the old or in the new filesystem
     *
     * @return string image path
     */
    public function getExistingImgPath()
    {
        if (!$this->id) {
            return false;
        }

        if (!$this->existing_path) {
            $this->existing_path = $this->getImgPath();
        }

        return $this->existing_path;
    }

    /**
     * Returns the path to the image without file extension
     *
     * @return string path
     */
    public function getImgPath()
    {
        if (!$this->id) {
            return false;
        }

        return $this->getImgFolder().$this->id;
    }

    /**
     * @param int $imageId
     * @param string $extension jpg | webp | png
     * @return false | string
     */
    public static function resolveFilePath($imageId, $extension)
    {
        $imageId = (int)$imageId;
        if ($imageId) {
            return static::getImgFolderStatic($imageId) . $imageId . '.' . $extension;
        }
        return false;
    }

    /**
     * Returns the path to the folder containing the image in the new filesystem
     *
     * @return string path to folder
     */
    public function getImgFolder()
    {
        if (!$this->id) {
            return false;
        }

        if (!$this->folder) {
            $this->folder = Image::getImgFolderStatic($this->id);
        }

        return $this->folder;
    }

    /**
     * Returns the path to the folder containing the image in the new filesystem
     *
     * @param int $idImage
     *
     * @return string path to folder
     */
    public static function getImgFolderStatic($idImage)
    {
        if (!is_numeric($idImage)) {
            return false;
        }
        $folders = str_split((string) $idImage);

        return implode('/', $folders).'/';
    }

    /**
     * Reposition image
     *
     * @param int $position Position
     * @param bool $direction Direction
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated since version 1.0.0 use Image::updatePosition() instead
     */
    public function positionImage($position, $direction)
    {
        Tools::displayAsDeprecated();

        $position = (int) $position;
        $direction = (int) $direction;

        // temporary position
        $highPosition = Image::getHighestPosition($this->id_product) + 1;

        $conn = Db::getInstance();
        $conn->update(
            'image',
            [
                'position' => (int) $highPosition,
            ],
            '`id_product` = '.(int) $this->id_product.' AND `position` = '.($direction ? $position - 1 : $position + 1)
        );

        $conn->update(
            'image',
            [
                'position' => ['type' => 'sql', 'value' => '`position`'.($direction ? '-1' : '+1')],
            ],
            '`id_image` = '.(int) $this->id
        );

        $conn->update(
            'image',
            [
                'position' => (int) $this->position,
            ],
            '`id_product` = '.(int) $this->id_product.' AND `position` = '.(int) $highPosition
        );
    }

    /**
     * Change an image position and update relative positions
     *
     * @param int $way position is moved up if 0, moved down if 1
     * @param int $position new position of the moved image
     *
     * @return bool success
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updatePosition($way, $position)
    {
        if (!isset($this->id) || !$position) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $conn = Db::getInstance();
        $result = $conn->update(
            'image',
            [
                'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
            ],
            '`position` '.($way ? '> '.(int) $this->position.' AND `position` <= '.(int) $position : '< '.(int) $this->position.' AND `position` >= '.(int) $position).' AND `id_product`='.(int) $this->id_product
        ) && $conn->update(
            'image',
            [
                'position' => (int) $position,
            ],
            '`id_image` = '.(int) $this->id_image
        );

        return $result;
    }

    /**
     * @param TableSchema $table
     */
    public static function processTableSchema($table)
    {
        if ($table->getNameWithoutPrefix() === 'image_shop') {
            $table->reorderColumns(['id_product', 'id_image', 'id_shop']);
        }
    }

    /**
     * Cleans orphaned product images and temporary images older than one hour,
     * and identifies suspicious (non-image) files in the product images directory.
     * 
     * Steps:
     * 1) Gathers all valid image IDs in one query.
     * 2) Recursively scans the product images folder:
     *    - Deletes images whose IDs are not in the database.
     *    - Flags any non-allowed file extension (or modified index.php) as suspicious.
     * 3) Deletes stale temporary images (older than 1 hour).
     * 4) Cleans up empty folders.
     *
     * @return array {
     *     @type int     $orphaned_count   The total number of orphaned and temporary images deleted.
     *     @type string[] $suspicious_files Paths of files with suspicious or non-image extensions.
     * }
     */
    public static function cleanOrphanedImages()
    {
        // Increase execution time for large operations.
        @ini_set('max_execution_time', 3600);

        // -----------------------------
        // Retrieve valid image IDs from DB in one query.
        // -----------------------------
        $dbRows = Db::getInstance()->executeS('SELECT id_image FROM ' . _DB_PREFIX_ . 'image');
        $validIds = [];
        foreach ($dbRows as $row) {
            $validIds[(int)$row['id_image']] = true;
        }

        // -----------------------------
        // Single pass through the product images directory for orphan and suspicious file detection.
        // -----------------------------
        $imagesToDelete = [];
        $suspiciousFiles = [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

        // Define the canonical index.php file path.
        $canonicalIndexFile = _PS_PROD_IMG_DIR_ . 'index.php';
        $canonicalIndexSize = is_file($canonicalIndexFile) ? filesize($canonicalIndexFile) : null;

        // Use RecursiveDirectoryIterator to iterate only over existing files.
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(_PS_PROD_IMG_DIR_, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                continue;
            }

            $filePath = $fileInfo->getPathname();
            $filename = $fileInfo->getFilename();
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // --- Orphan detection for allowed image files ---
            if (in_array($ext, $allowedExtensions)) {
                // Extract numeric candidate from filename (ignoring extension).
                $numericId = (int)pathinfo($filename, PATHINFO_FILENAME);
                if ($numericId > 0 && !isset($validIds[$numericId])) {
                    $imagesToDelete[] = $filePath;
                }
                // Skip further checks for valid image files.
                continue;
            }

            // --- Suspicious file detection for non-image files ---
            if ($filename === 'index.php') {
                // For index.php, compare with the canonical file.
                // Only the size of the file is compares as we would like to
                // update the header years in the canonical file in future.
                if ($canonicalIndexSize !== null) {
                    if ($fileInfo->getSize() !== $canonicalIndexSize) {
                        $suspiciousFiles[] = $filePath;
                    }
                } else {
                    // If there is no canonical index.php, flag it.
                    $suspiciousFiles[] = $filePath;
                }
            } else {
                // Flag any other file with a non-allowed extension.
                $suspiciousFiles[] = $filePath;
            }
        }

        // -----------------------------
        // Process temporary images (only delete if older than 1 hour).
        // -----------------------------
        $tempPatterns = [
            _PS_TMP_IMG_DIR_ . 'product_*',
            _PS_TMP_IMG_DIR_ . 'tinylink_form_mini_*'
        ];
        $oneHourAgo = time() - 3600;
        foreach ($tempPatterns as $pattern) {
            if ($tempImages = glob($pattern)) {
                foreach ($tempImages as $tempImage) {
                    if (is_file($tempImage) && filemtime($tempImage) < $oneHourAgo) {
                        $imagesToDelete[] = $tempImage;
                    }
                }
            }
        }

        // -----------------------------
        // Delete marked files and clean empty folders.
        // -----------------------------
        $orphanedCount = count($imagesToDelete);
        foreach ($imagesToDelete as $file) {
            if (file_exists($file) && !unlink($file)) {
                PrestaShopLogger::addLog('Failed to delete file: ' . $file, 3);
            }
        }

        self::cleanEmptyFolders(_PS_PROD_IMG_DIR_, true);

        return [
            'orphaned_count'   => $orphanedCount,
            'suspicious_files' => $suspiciousFiles,
        ];
    }

    /**
     * Recursively removes empty directories in the product images folder.
     * A directory is considered empty if it contains no files
     * or only an 'index.php' file. The root directory itself is never removed.
     *
     * @param string $dir    The directory path to clean.
     * @param bool   $isRoot Whether this directory is the root (and should not be removed).
     *
     * @return void
     */
    protected static function cleanEmptyFolders($dir, $isRoot = false)
    {
        // Recursively process subdirectories.
        $items = array_diff(scandir($dir), array('.', '..'));
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::cleanEmptyFolders($path, false);
            }
        }
        // If not the root and the directory is empty (ignoring index.php), delete it.
        if (!$isRoot && Tools::isDirectoryEmpty($dir, ['index.php'])) {
            Tools::deleteDirectory($dir);
        }
    }
}
