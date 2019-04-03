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
    /**
     * @var array Image types cache
     */
    protected static $images_types_cache = [];
    /** @var array $images_types_name_cache */
    protected static $images_types_name_cache = [];
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
        // @codingStandardsIgnoreStart
        if (!isset(static::$images_types_cache[$type])) {
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

            static::$images_types_cache[$type] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        }

        return static::$images_types_cache[$type];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Check if type already is already registered in database
     *
     * @param string $typeName Name
     *
     * @return int Number of results found
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function typeAlreadyExists($typeName)
    {
        if (!Validate::isImageTypeName($typeName)) {
            die(Tools::displayError());
        }

        Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_image_type`')
                ->from('image_type')
                ->where('`name` = \''.pSQL($typeName).'\'')
        );

        return Db::getInstance()->NumRows();
    }

    /**
     * @param string $name
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFormatedName($name)
    {
        $themeName = Context::getContext()->shop->theme_name;
        $nameWithoutThemeName = str_replace(['_'.$themeName, $themeName.'_'], '', $name);

        //check if the theme name is already in $name if yes only return $name
        if (strstr($name, $themeName) && static::getByNameNType($name)) {
            return $name;
        } elseif (static::getByNameNType($nameWithoutThemeName.'_'.$themeName)) {
            return $nameWithoutThemeName.'_'.$themeName;
        } elseif (static::getByNameNType($themeName.'_'.$nameWithoutThemeName)) {
            return $themeName.'_'.$nameWithoutThemeName;
        } else {
            return $nameWithoutThemeName.'_default';
        }
    }

    /**
     * Finds image type definition by name and type
     *
     * @param string $name
     * @param string $type
     *
     * @param int    $order
     *
     * @return bool|mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getByNameNType($name, $type = null, $order = 0)
    {
        static $isPassed = false;

        // @codingStandardsIgnoreStart
        if (!isset(static::$images_types_name_cache[$name.'_'.$type.'_'.$order]) && !$isPassed) {
            $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM `'._DB_PREFIX_.'image_type`');

            $types = ['products', 'categories', 'manufacturers', 'suppliers', 'scenes', 'stores'];
            $total = count($types);

            foreach ($results as $result) {
                foreach ($result as $value) {
                    for ($i = 0; $i < $total; ++$i) {
                        static::$images_types_name_cache[$result['name'].'_'.$types[$i].'_'.$value] = $result;
                    }
                }
            }

            $isPassed = true;
        }

        $return = false;
        if (isset(static::$images_types_name_cache[$name.'_'.$type.'_'.$order])) {
            $return = static::$images_types_name_cache[$name.'_'.$type.'_'.$order];
        }
        // @codingStandardsIgnoreEnd

        return $return;
    }
}
