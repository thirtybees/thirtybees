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
 * Class ImageTypeCore
 */
class ImageTypeCore extends ObjectModel
{
    /**
     * @var string Name
     */
    public $name;

    /**
     * @var int Width
     */
    public $width;

    /**
     * @var int Height
     */
    public $height;

    /**
     * @var int $id_image_type_parent if set, the imageType acts like an alias
     */
    public $id_image_type_parent;

    /**
     * @var bool Apply to products
     *
     * @deprecated since 1.5 -> imageEntities are handled by table image_entity
     */
    public $products;

    /**
     * @var bool Apply to categories
     *
     * @deprecated since 1.5 -> imageEntities are handled by table image_entity
     */
    public $categories;

    /**
     * @var bool Apply to manufacturers
     *
     * @deprecated since 1.5 -> imageEntities are handled by table image_entity
     */
    public $manufacturers;

    /**
     * @var bool Apply to suppliers
     *
     * @deprecated since 1.5 -> imageEntities are handled by table image_entity
     */
    public $suppliers;

    /**
     * @var bool Apply to scenes
     *
     * @deprecated since 1.5 -> imageEntities are handled by table image_entity
     */
    public $scenes;


    /**
     * @var bool Apply to store
     *
     * @deprecated since 1.5 -> imageEntities are handled by table image_entity
     */
    public $stores;

    /**
     * @var string[]
     */
    protected static $typeNameCache;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'image_type',
        'primary' => 'id_image_type',
        'fields'  => [
            'name'                  => ['type' => self::TYPE_STRING, 'validate' => 'isImageTypeName', 'required' => true, 'size' => 64],
            'width'                 => ['type' => self::TYPE_INT, 'validate' => 'isImageSize', 'required' => true],
            'height'                => ['type' => self::TYPE_INT, 'validate' => 'isImageSize', 'required' => true],
            'id_image_type_parent'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'products'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'categories'            => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'manufacturers'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'suppliers'             => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'scenes'                => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'stores'                => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
        ],
        'keys' => [
            'image_type' => [
                'image_type_name' => ['type' => ObjectModel::KEY, 'columns' => ['name']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [];

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        $db = Db::getInstance();

        // Delete image entity types
        $db->delete('image_entity_type', 'id_image_type='.$this->id);

        // Unhook aliases
        $db->update('image_type', ['id_image_type_parent' => 0], 'id_image_type_parent='.$this->id);

        return parent::delete();
    }

    /**
     * Return an instance for the named image type. If no such image type
     * exists yet, return an empty instance with just the name set.
     *
     * @param string $typeName Name of the image type.
     * @param string $themeName Name of the theme this image type belongs to.
     *                          Defaults to the name of the current theme.
     *
     * @return ImageType
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getInstanceByName($typeName, $themeName = null)
    {
        $name = $themeName ? $themeName.'_'.$typeName : $typeName;
        if (! static::typeAlreadyExists($name)) {
            $type = new ImageType();
            $type->name = $name;

            return $type;
        }

        $result = Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_image_type`')
                ->from('image_type')
                ->where('`name` = \''.pSQL($name).'\'')
        );

        return new ImageType($result);
    }

    /**
     * Returns image type definitions
     *
     * @param string|null $imageEntityName Name of imageEntity
     * @param bool $orderBySize
     *
     * @return array[] Image type definitions
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public static function getImagesTypes($imageEntityName = null, $orderBySize = false)
    {
        $cacheKey = $imageEntityName
            ? 'ImageType::getImagesTypes_entity:' . $imageEntityName
            : 'ImageType::getImagesTypes_all';

        if (! Cache::isStored($cacheKey)) {
            if ($imageEntityName) {
                $imageEntity = ImageEntity::getImageEntityInfo($imageEntityName);
                $imageTypes = $imageEntity['imageTypes'] ?? [];
            } else {
                $query = new DbQuery();
                $query->select('*');
                $query->from(self::$definition['table']);
                $query->orderBy('`name` ASC');
                $imageTypes = Db::readOnly()->getArray($query);
            }
            Cache::store($cacheKey, $imageTypes);
        } else {
            $imageTypes = Cache::retrieve($cacheKey);
        }

        if ($orderBySize) {
            usort($imageTypes, function($a, $b) {
                $ret = $a['width'] - $b['width'];
                if (! $ret) {
                    $ret = $a['height'] - $b['height'];
                }
                return $ret;
            });
        }

        return $imageTypes;
    }

    /**
     * Check if type is already registered in database.
     *
     * @param string $typeName Name
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function typeAlreadyExists($typeName)
    {
        $typeNameCache = static::getIndexedImageTypeNames();
        return isset($typeNameCache[$typeName]);
    }

    /**
     * Return indexed list of image type names
     *
     * @return string[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function getIndexedImageTypeNames()
    {
        if (is_null(static::$typeNameCache)) {
            static::$typeNameCache = [];
            $rows = Db::readOnly()->getArray(
                (new DbQuery())
                    ->select('`name`')
                    ->from('image_type')
            );
            foreach ($rows as $row) {
                $name = $row['name'];
                static::$typeNameCache[$name] = $name;
            }
        }
        return static::$typeNameCache;
    }

    /**
     * Find an existing variant of a specific image type.
     *
     * @param string $name image type name
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getFormatedName($name)
    {
        if (!$name) {
            return $name;
        }
        $themeName = '';
        $themeDir = '';
        $theme = Context::getContext()->theme;
        if (Validate::isLoadedObject($theme)) {
            $themeName = $theme->name;
            $themeDir = $theme->directory;
        }
        return static::resolveImageTypeName($name, $themeName, $themeDir, static::getIndexedImageTypeNames());
    }

    /**
     * Helper method to resolve image type name to canonical version. If this method fails to
     * resolve image type, input $name value is returned
     *
     * For example:
     *     Niara_cart -> Niara_cart
     *     Niara_cart_default -> Niara_cart
     *     cart -> Niara_cart
     *     cart_default -> Niara_cart
     *     non-existing -> non-existing
     *
     * @param string $name image type name
     * @param string $themeName theme name
     * @param string $themeDirectory theme directory name
     * @param array $imageTypes indexed map of all image types
     * @return string
     */
    protected static function resolveImageTypeName($name, $themeName, $themeDirectory, $imageTypes)
    {
        static $cache = [];
        $cacheKey = $name . '|' . $themeName . '|' . $themeDirectory;
        if (! array_key_exists($cacheKey, $cache)) {
            $cache[$cacheKey] = static::resolveImageTypeNameWithoutCache($name, $themeName, $themeDirectory, $imageTypes);
        }
        return $cache[$cacheKey];
    }

    /**
     * Helper method to resolve image type name to canonical version. If this method fails to
     * resolve image type, input $name value is returned

     * @param string $name image type name
     * @param string $themeName theme name
     * @param string $themeDirectory theme directory name
     * @param array $imageTypes indexed map of all image types
     * @return string
     */
    protected static function resolveImageTypeNameWithoutCache($name, $themeName, $themeDirectory, $imageTypes)
    {
        // normalize input $name -- remove all theme prefixes/suffixes.
        $themeNames = array_unique([$themeName, $themeDirectory, 'default']);
        $regexps = [];
        foreach ($themeNames as $item) {
            $regexps[] = '/^' . preg_quote($item) . '_/i';
            $regexps[] = '/_' . preg_quote($item) . '$/i';
        }
        $nameWithoutTheme = $name;
        do {
            $nameWithoutTheme = preg_replace($regexps, '', $nameWithoutTheme, -1, $count);
        } while ($count > 0);

        // possible variants of the input image type name that we accept, ordered by priority
        $variants = [
            $themeName.'_'.$nameWithoutTheme,
            $themeDirectory.'_'.$nameWithoutTheme,
            $nameWithoutTheme.'_'.$themeName,
            $nameWithoutTheme.'_'.$themeDirectory,
            $themeName.'_'.$nameWithoutTheme.'_default',
            $themeDirectory.'_'.$nameWithoutTheme.'_default',
            $nameWithoutTheme.'_'.$themeName . '_default',
            $nameWithoutTheme.'_'.$themeDirectory .'_default',
            $nameWithoutTheme,
            $nameWithoutTheme.'_default',
            $themeName.'_'.$nameWithoutTheme.'_'.$themeName,
            $themeDirectory.'_'.$nameWithoutTheme.'_'.$themeDirectory,
        ];

        // image type is not case sensitive
        foreach ($imageTypes as $key => $value) {
            $lower = strtolower($key);
            if ($lower != $key && !in_array($lower, $imageTypes)) {
                $imageTypes[$lower] = $value;
            }
        }

        // try to find variant for input name, and map it to actual name
        foreach ($variants as $variant) {
            if (array_key_exists($variant, $imageTypes)) {
                return $imageTypes[$variant];
            }
            $lower = strtolower($variant);
            if (array_key_exists($lower, $imageTypes)) {
                return $imageTypes[$lower];
            }
        }

        // Give up searching.
        return $name;
    }

    /**
     * @param int $id_image_type ID (not name!) of imageType
     *
     * @return array of imageTypes
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getImageTypeAliases($id_image_type)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from(self::$definition['table']);
        $query->where('id_image_type_parent = ' . (int)$id_image_type);
        return Db::getInstance()->getArray($query);
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
        $res = parent::add($autoDate, $nullValues);
        static::$typeNameCache = null;
        return $res;
    }

}
