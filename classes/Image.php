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
 * Class ImageCore
 *
 * @since 1.0.0
 */
class ImageCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
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
    /** @var string Legend */
    public $legend;
    /** @var string image extension */
    public $image_format = 'jpg';
    /** @var string path to index.php file to be copied to new image folders */
    public $source_index;
    /** @var string image folder */
    protected $folder;
    /** @var string image path without extension */
    protected $existing_path;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'image',
        'primary'   => 'id_image',
        'multilang' => true,
        'fields'    => [
            'id_product' => ['type' => self::TYPE_INT, 'shop' => 'both', 'validate' => 'isUnsignedId', 'required' => true],
            'position'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'cover'      => ['type' => self::TYPE_BOOL, 'allow_null' => true, 'validate' => 'isBool', 'shop' => true],
            'legend'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
        ],
    ];

    /**
     * ImageCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id, $idLang);
        $this->image_dir = _PS_PROD_IMG_DIR_;
        $this->source_index = _PS_PROD_IMG_DIR_.'index.php';
    }

    /**
     * Return first image (by position) associated with a product attribute
     *
     * @param int $idShop             Shop ID
     * @param int $idLang             Language ID
     * @param int $idProduct          Product ID
     * @param int $idProductAttribute Product Attribute ID
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getBestImageAttribute($idShop, $idLang, $idProduct, $idProductAttribute)
    {
        $cacheId = 'Image::getBestImageAttribute'.'-'.(int) $idProduct.'-'.(int) $idProductAttribute.'-'.(int) $idLang.'-'.(int) $idShop;

        if (!Cache::isStored($cacheId)) {
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
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
     * @param int $idLang             Language ID. Null/0/false = all languages.
     * @param int $idProduct          Product ID
     * @param int $idProductAttribute Product Attribute ID
     *
     * @return array Images
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
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

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Check if a product has an image available
     *
     * @param int $idLang             Language ID. Null/0/false = all languages.
     * @param int $idProduct          Product ID
     * @param int $idProductAttribute Product Attribute ID
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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

        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Return Images
     *
     * @return array Images
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAllImages()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getImagesTotal($idProduct)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function deleteCover($idProduct)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            die(Tools::displayError());
        }

        if (file_exists(_PS_TMP_IMG_DIR_.'product_'.$idProduct.'.jpg')) {
            unlink(_PS_TMP_IMG_DIR_.'product_'.$idProduct.'.jpg');
        }

        return (Db::getInstance()->update(
        'image',
            [
                'cover' => ['type' => 'sql', 'value' => 'NULL'],
            ],
            '`id_product` = '.(int) $idProduct,
            0,
            true
        ) &&
        Db::getInstance()->update(
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
     * @return bool result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCover($idProduct)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('image_shop')
                ->where('`id_product` = '.(int) $idProduct)
                ->where('`cover` = 1')
        );
    }

    /**
     *Get global product cover
     *
     * @param int $idProduct Product ID
     *
     * @return bool result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGlobalCover($idProduct)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
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
     * @param int   $idProductOld Source product ID
     * @param bool  $idProductNew Destination product ID
     * @param array $combinationImages
     *
     * @return bool
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicateProductImages($idProductOld, $idProductNew, $combinationImages)
    {
        $imageTypes = ImageType::getImagesTypes('products');
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
                    if (file_exists(_PS_PROD_IMG_DIR_.$imageOld->getExistingImgPath().'-'.$imageType['name'].'.jpg')) {
                        if (!Configuration::get('PS_LEGACY_IMAGES')) {
                            $imageNew->createImgFolder();
                        }
                        copy(
                            _PS_PROD_IMG_DIR_.$imageOld->getExistingImgPath().'-'.$imageType['name'].'.jpg',
                            $newPath.'-'.$imageType['name'].'.jpg'
                        );
                        if (Configuration::get('WATERMARK_HASH')) {
                            $oldImagePath = _PS_PROD_IMG_DIR_.$imageOld->getExistingImgPath().'-'.$imageType['name'].'-'.Configuration::get('WATERMARK_HASH').'.jpg';
                            if (file_exists($oldImagePath)) {
                                copy($oldImagePath, $newPath.'-'.$imageType['name'].'-'.Configuration::get('WATERMARK_HASH').'.jpg');
                            }
                        }
                    }
                }

                if (file_exists(_PS_PROD_IMG_DIR_.$imageOld->getExistingImgPath().'.jpg')) {
                    copy(_PS_PROD_IMG_DIR_.$imageOld->getExistingImgPath().'.jpg', $newPath.'.jpg');
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getHighestPosition($idProduct)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
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
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getPathForCreation()
    {
        if (!$this->id) {
            return false;
        }
        if (Configuration::get('PS_LEGACY_IMAGES')) {
            if (!$this->id_product) {
                return false;
            }
            $path = $this->id_product.'-'.$this->id;
        } else {
            $path = $this->getImgPath();
            $this->createImgFolder();
        }

        return _PS_PROD_IMG_DIR_.$path;
    }

    /**
     * Create parent folders for the image in the new filesystem
     *
     * @return bool success
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function createImgFolder()
    {
        if (!$this->id) {
            return false;
        }

        if (!file_exists(_PS_PROD_IMG_DIR_.$this->getImgFolder())) {
            // Apparently sometimes mkdir cannot set the rights, and sometimes chmod can't. Trying both.
            // @codingStandardsIgnoreStart
            $success = @mkdir(_PS_PROD_IMG_DIR_.$this->getImgFolder(), static::$access_rights, true);
            $chmod = @chmod(_PS_PROD_IMG_DIR_.$this->getImgFolder(), static::$access_rights);
            // @codingStandardsIgnoreEnd

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
     * @param $combinationImages
     * @param $savedId
     * @param $idImage
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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

        return DB::getInstance()->insert('product_attribute_image', $insert);
    }

    /**
     * @param array  $params
     * @param Smarty $smarty
     *
     * @return mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getWidth($params, $smarty)
    {
        $result = static::getSize($params['type']);

        return $result['width'];
    }

    /**
     * @param mixed $type
     *
     * @return mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getSize($type)
    {
        $type = ImageType::getFormatedName($type);

        if (!isset(static::$_cacheGetSize[$type]) || static::$_cacheGetSize[$type] === null) {
            static::$_cacheGetSize[$type] = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('`width`, `height`')
                    ->from('image_type')
                    ->where('`name` = \''.pSQL($type).'\'')
            );
        }

        return static::$_cacheGetSize[$type];
    }

    /**
     * @param array  $params
     * @param Smarty $smarty
     *
     * @return mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getHeight($params, $smarty)
    {
        $result = static::getSize($params['type']);

        return $result['height'];
    }

    /**
     * Clear all images in tmp dir
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function clearTmpDir()
    {
        foreach (scandir(_PS_TMP_IMG_DIR_) as $d) {
            if (preg_match('/(.*)\.jpg$/', $d)) {
                unlink(_PS_TMP_IMG_DIR_.$d);
            }
        }
    }

    /**
     * Recursively deletes all product images in the given folder tree and removes empty folders.
     *
     * @param string $path   folder containing the product images to delete
     * @param string $format image format
     *
     * @return bool success
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function deleteAllImages($path, $format = 'jpg')
    {
        if (!$path || !$format || !is_dir($path)) {
            return false;
        }
        foreach (scandir($path) as $file) {
            if (preg_match('/^[0-9]+(\-(.*))?\.'.$format.'$/', $file)) {
                unlink($path.$file);
            } elseif (is_dir($path.$file) && (preg_match('/^[0-9]$/', $file))) {
                Image::deleteAllImages($path.$file.'/', $format);
            }
        }

        // Can we remove the image folder?
        if (is_numeric(basename($path))) {
            $removeFolder = true;
            foreach (scandir($path) as $file) {
                if (($file != '.' && $file != '..' && $file != 'index.php')) {
                    $removeFolder = false;
                    break;
                }
            }

            if ($removeFolder) {
                // we're only removing index.php if it's a folder we want to delete
                if (file_exists($path.'index.php')) {
                    @unlink($path.'index.php');
                }
                @rmdir($path);
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
     * @return mixed success or timeout
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function moveToNewFileSystem($maxExecutionTime = 0)
    {
        $startTime = time();
        $image = null;
        $tmpFolder = 'duplicates/';
        foreach (scandir(_PS_PROD_IMG_DIR_) as $file) {
            // matches the base product image or the thumbnails
            if (preg_match('/^([0-9]+\-)([0-9]+)(\-(.*))?\.jpg$/', $file, $matches)) {
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
                    $newPath = _PS_PROD_IMG_DIR_.$image->getImgPath().(isset($matches[3]) ? $matches[3] : '').'.jpg';
                    if (file_exists($newPath)) {
                        if (!file_exists(_PS_PROD_IMG_DIR_.$tmpFolder)) {
                            // @codingStandardsIgnoreStart
                            @mkdir(_PS_PROD_IMG_DIR_.$tmpFolder, static::$access_rights);
                            @chmod(_PS_PROD_IMG_DIR_.$tmpFolder, static::$access_rights);
                        }
                        $tmp_path = _PS_PROD_IMG_DIR_.$tmpFolder.basename($file);
                        if (!@rename($newPath, $tmp_path) || !file_exists($tmp_path)) {
                            // @codingStandardsIgnoreEnd
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
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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

        // @codingStandardsIgnoreStart
        @mkdir($testFolder, static::$access_rights, true);
        @chmod($testFolder, static::$access_rights);
        // @codingStandardsIgnoreEnd
        if (!is_writeable($testFolder)) {
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
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
        Db::getInstance()->execute('SET @position:=0', false);
        Db::getInstance()->execute(
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public function deleteProductAttributeImage()
    {
        return Db::getInstance()->delete('product_attribute_image', '`id_image` = '.(int) $this->id);
    }

    /**
     * Delete the product image from disk and remove the containing folder if empty
     * Handles both legacy and new image filesystems
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param bool $forceDelete
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteImage($forceDelete = false)
    {
        if (!$this->id) {
            return false;
        }

        // Delete base image
        if (file_exists($this->image_dir.$this->getExistingImgPath().'.'.$this->image_format)) {
            unlink($this->image_dir.$this->getExistingImgPath().'.'.$this->image_format);
        } else {
            return false;
        }

        $filesToDelete = [];

        // Delete auto-generated images
        $imageTypes = ImageType::getImagesTypes();
        foreach ($imageTypes as $imageType) {
            $filesToDelete[] = $this->image_dir.$this->getExistingImgPath().'-'.$imageType['name'].'.'.$this->image_format;
            if (Configuration::get('WATERMARK_HASH')) {
                $filesToDelete[] = $this->image_dir.$this->getExistingImgPath().'-'.$imageType['name'].'-'.Configuration::get('WATERMARK_HASH').'.'.$this->image_format;
            }
        }

        // Delete watermark image
        $filesToDelete[] = $this->image_dir.$this->getExistingImgPath().'-watermark.'.$this->image_format;
        // delete index.php
        $filesToDelete[] = $this->image_dir.$this->getImgFolder().'index.php';
        // Delete tmp images
        $filesToDelete[] = _PS_TMP_IMG_DIR_.'product_'.$this->id_product.'.'.$this->image_format;
        $filesToDelete[] = _PS_TMP_IMG_DIR_.'product_mini_'.$this->id_product.'.'.$this->image_format;

        foreach ($filesToDelete as $file) {
            if (file_exists($file) && !@unlink($file)) {
                return false;
            }
        }

        // Can we delete the image folder?
        if (is_dir($this->image_dir.$this->getImgFolder())) {
            $deleteFolder = true;
            foreach (scandir($this->image_dir.$this->getImgFolder()) as $file) {
                if (($file != '.' && $file != '..')) {
                    $deleteFolder = false;
                    break;
                }
            }
        }
        if (isset($deleteFolder) && $deleteFolder) {
            @rmdir($this->image_dir.$this->getImgFolder());
        }

        return true;
    }

    /**
     * Returns image path in the old or in the new filesystem
     *
     * @return string image path
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getExistingImgPath()
    {
        if (!$this->id) {
            return false;
        }

        if (!$this->existing_path) {
            if (Configuration::get('PS_LEGACY_IMAGES') && file_exists(_PS_PROD_IMG_DIR_.$this->id_product.'-'.$this->id.'.'.$this->image_format)) {
                $this->existing_path = $this->id_product.'-'.$this->id;
            } else {
                $this->existing_path = $this->getImgPath();
            }
        }

        return $this->existing_path;
    }

    /**
     * Returns the path to the image without file extension
     *
     * @return string path
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getImgPath()
    {
        if (!$this->id) {
            return false;
        }

        $path = $this->getImgFolder().$this->id;

        return $path;
    }

    /**
     * Returns the path to the folder containing the image in the new filesystem
     *
     * @return string path to folder
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param mixed $idImage
     *
     * @return string path to folder
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param int  $position  Position
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

        Db::getInstance()->update(
            'image',
            [
                'position' => (int) $highPosition,
            ],
            '`id_product` = '.(int) $this->id_product.' AND `position` = '.($direction ? $position - 1 : $position + 1)
        );

        Db::getInstance()->update(
            'image',
            [
                'position' => ['type' => 'sql', 'value' => '`position`'.($direction ? '-1' : '+1')],
            ],
            '`id_image` = '.(int) $this->id
        );

        Db::getInstance()->update(
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
     * @param int $way      position is moved up if 0, moved down if 1
     * @param int $position new position of the moved image
     *
     * @return int success
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updatePosition($way, $position)
    {
        if (!isset($this->id) || !$position) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $result = Db::getInstance()->update(
            'image',
            [
                'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
            ],
            '`position` '.($way ? '> '.(int) $this->position.' AND `position` <= '.(int) $position : '< '.(int) $this->position.' AND `position` >= '.(int) $position).' AND `id_product`='.(int) $this->id_product
        ) && Db::getInstance()->update(
            'image',
            [
                'position' => (int) $position,
            ],
            '`id_image` = '.(int) $this->id_image
        );

        return $result;
    }
}
