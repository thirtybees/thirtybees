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
 * Class ImageTypeCore
 *
 * @since 1.0.0
 */
class ImageTypeCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var string Name */
    public $name;
    /** @var int Width */
    public $width;
    /** @var int Height */
    public $height;
    /** @var bool Apply to products */
    public $products;
    /** @var int Apply to categories */
    public $categories;
    /** @var int Apply to manufacturers */
    public $manufacturers;
    /** @var int Apply to suppliers */
    public $suppliers;
    /** @var int Apply to scenes */
    public $scenes;
    /** @var int Apply to store */
    public $stores;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'image_type',
        'primary' => 'id_image_type',
        'fields'  => [
            'name'          => ['type' => self::TYPE_STRING, 'validate' => 'isImageTypeName', 'required' => true, 'size' => 64],
            'width'         => ['type' => self::TYPE_INT, 'validate' => 'isImageSize', 'required' => true],
            'height'        => ['type' => self::TYPE_INT, 'validate' => 'isImageSize', 'required' => true],
            'products'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'categories'    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'manufacturers' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'suppliers'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'scenes'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'stores'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
        ],
        'keys' => [
            'image_type' => [
                'image_type_name' => ['type' => ObjectModel::KEY, 'columns' => ['name']],
            ],
        ],
    ];

    protected $webserviceParameters = [];

    /**
     * Return an instance for the named image type. If no such image type
     * exists yet, return an empty instance with just the name set.
     *
     * @param string $typeName  Name of the image type.
     * @param string $themeName Name of the theme this image type belongs to.
     *                          Defaults to the name of the current theme.
     *
     * @return ImageType
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @version 1.1.0 Initial version.
     */
    public static function getInstanceByName($typeName, $themeName = null)
    {
        if ($themeName === null) {
            $themeName = Context::getContext()->shop->theme_name;
        }

        $name = $themeName.'_'.$typeName;
        if ( ! static::typeAlreadyExists($name)) {
            $type = new ImageType();
            $type->name = $name;

            return $type;
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
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
     * @param string|null $type Image type
     * @param bool        $orderBySize
     *
     * @return array Image type definitions
     * @throws PrestaShopDatabaseException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getImagesTypes($type = null, $orderBySize = false)
    {
        static $cache = [];

        if ( ! isset($cache[$type])) {
            $query = (new DbQuery())
                ->select('*')
                ->from('image_type');
            if (!empty($type)) {
                $query->where('`'.bqSQL($type).'` = 1');
            }

            if ($orderBySize) {
                $query->orderBy('`width` DESC, `height` DESC, `name` ASC');
            } else {
                $query->orderBy('`name` ASC');
            }

            $cache[$type] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        }

        return $cache[$type];
    }

    /**
     * Check if type already is already registered in database.
     *
     * @param string $typeName Name
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @version 1.0.0 Initial version.
     * @version 1.1.0 Introduced caching, return type bool rather than int.
     */
    public static function typeAlreadyExists($typeName)
    {
        $typeNameCache = static::getIndexedImageTypeNames();
        return isset($typeNameCache[$typeName]);
    }

    /**
     * Return indexed list of image type names
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function getIndexedImageTypeNames()
    {
        static $typeNameCache = false;
        if ($typeNameCache === false) {
            $typeNameCache = [];
            $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('`name`')
                    ->from('image_type')
            );
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $name = $row['name'];
                    $typeNameCache[$name] = $name;
                }
            }
        }
        return $typeNameCache;
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
     * @version 1.0.0 Initial version
     */
    public static function getFormatedName($name)
    {
        if (! $name) {
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
     * @since 1.4.0
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
     * @since 1.4.0
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
     * Finds image type definition by name and type
     *
     * @param string $name
     * @param string $type
     * @param int    $order Deprecated.
     *
     * @return bool|mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @version 1.0.0 Initial version.
     * @version 1.1.0 Reworked entirely, $order deprecated, added fallbacks,
     */
    public static function getByNameNType($name, $type = '', $order = null)
    {
        static $cache = null;

        if (isset($order)) {
            Tools::displayParameterAsDeprecated('order');
        }

        if ( ! $cache) {
            $results = static::getImagesTypes();
            $resultTypes = [
                'products',
                'categories',
                'manufacturers',
                'suppliers',
                'scenes',
                'stores',
            ];

            foreach ($results as $result) {
                foreach ($resultTypes as $resultType) {
                    $key = $result['name'].'_'.$resultType;
                    $cache[$key] = $result;
                }
            }
        }

        $nameType = $name.'_'.$type;
        if ( ! isset($cache[$nameType])) {
            // Try fallbacks for compatibility with broken modules/templates.
            $context = Context::getContext();
            if ($context) {
                // Try formating (again, $name should be formatted already).
                $nameType = static::getFormatedName($name).'_'.$type;

                if ( ! isset($cache[$nameType])) {
                    // Try removing _default suffix.
                    $name = preg_replace('/_default$/', '', $name);
                    $nameType = static::getFormatedName($name).'_'.$type;
                }
            }

            if ( ! isset($cache[$nameType])) {
                // Last resort: find the first reasonable match.
                foreach (array_keys($cache) as $key) {
                    if (preg_match('/'.$type.'$/', $key)
                        && preg_match('/^.*_'.$name.'_/', $key)
                    ) {
                        $nameType = $key;
                        break;
                    }
                }
            }
        }

        $return = false;
        if (isset($cache[$nameType])) {
            $return = $cache[$nameType];
        }

        return $return;
    }
}
