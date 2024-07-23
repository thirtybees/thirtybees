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
 * Class ImageEntityCore
 */
class ImageEntityCore extends ObjectModel
{
    const ENTITY_TYPE_PRODUCTS = 'products';
    const ENTITY_TYPE_CATEGORIES = 'categories';
    const ENTITY_TYPE_CATEGORIES_THUMB = 'categoriesthumb';
    const ENTITY_TYPE_MANUFACTURERS = 'manufacturers';
    const ENTITY_TYPE_SUPPLIERS = 'suppliers';
    const ENTITY_TYPE_SCENES = 'scenes';
    const ENTITY_TYPE_SCENES_THUMB = 'scenesthumb';
    const ENTITY_TYPE_STORES = 'stores';

    /**
     * @var string Name
     */
    public $id_image_entity;

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string Classname
     */
    public $classname;

    /**
     * @var string|string[]
     */
    public $display_name;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'image_entity',
        'primary' => 'id_image_entity',
        'multilang' => true,
        'fields'  => [
            'name'          => ['type' => self::TYPE_STRING, 'validate' => 'isImageTypeName', 'required' => true, 'size' => 64],
            'classname'     => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 64],

            /* Lang fields */
            'display_name'  => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 128, 'lang' => true],
        ],
        'keys' => [
            'image_entity' => [
                'image_entity_name' => ['type' => ObjectModel::KEY, 'columns' => ['name']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'objectsNodeName' => 'image_entities',
        'objectNodeName'  => 'image_entity',
        'fields'          => [],
        'associations'    => [
            'image_types' => [
                'resource' => 'image_types',
                'fields'   => [
                    'id' => [],
                ],
            ],
        ],
    ];

    /**
     * @param string $classname This needs to be the classname like defined in AdminController (with namespace)
     * @param array $images The structure is defined ObjectModel $definition['images']
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function rebuildImageEntities($classname, $images)
    {

        // Adding images from themes
        /** @var Theme $theme */
        foreach (Theme::getThemes() as $theme) {
            $xml = $theme->loadConfigFile();

            foreach ($xml->images->image as $imageDefinition) {

                $width = (int)$imageDefinition['width'];
                $height = (int)$imageDefinition['height'];
                $name = $theme->name . '_' . $imageDefinition['name'];

                if ((string)$imageDefinition[static::ENTITY_TYPE_PRODUCTS] === 'true') {
                    $images[static::ENTITY_TYPE_PRODUCTS]['classname'] = 'Product';
                    $images[static::ENTITY_TYPE_PRODUCTS]['imageTypes'][] = [
                        'name' => $name,
                        'width' => $width,
                        'height' => $height,
                    ];
                }
                if ((string)$imageDefinition[static::ENTITY_TYPE_CATEGORIES] === 'true') {
                    $images[static::ENTITY_TYPE_CATEGORIES]['classname'] = 'Category';
                    $images[static::ENTITY_TYPE_CATEGORIES]['imageTypes'][] = [
                        'name' => $name,
                        'width' => $width,
                        'height' => $height,
                    ];
                }
                if ((string)$imageDefinition[static::ENTITY_TYPE_CATEGORIES_THUMB] === 'true') {
                    $images[static::ENTITY_TYPE_CATEGORIES_THUMB]['classname'] = 'Category';
                    $images[static::ENTITY_TYPE_CATEGORIES_THUMB]['imageTypes'][] = [
                        'name' => $name,
                        'width' => $width,
                        'height' => $height,
                    ];
                }
                if ((string)$imageDefinition[static::ENTITY_TYPE_MANUFACTURERS] === 'true') {
                    $images[static::ENTITY_TYPE_MANUFACTURERS]['classname'] = 'Manufacturer';
                    $images[static::ENTITY_TYPE_MANUFACTURERS]['imageTypes'][] = [
                        'name' => $name,
                        'width' => $width,
                        'height' => $height,
                    ];
                }
                if ((string)$imageDefinition[static::ENTITY_TYPE_SUPPLIERS] === 'true') {
                    $images[static::ENTITY_TYPE_SUPPLIERS]['classname'] = 'Supplier';
                    $images[static::ENTITY_TYPE_SUPPLIERS]['imageTypes'][] = [
                        'name' => $name,
                        'width' => $width,
                        'height' => $height,
                    ];
                }
                if (Tab::getIdFromClassName('AdminScenes')) {
                    if ((string)$imageDefinition[static::ENTITY_TYPE_SCENES] === 'true') {
                        $images[static::ENTITY_TYPE_SCENES]['classname'] = 'Scene';
                        $images[static::ENTITY_TYPE_SCENES]['imageTypes'][] = [
                            'name' => $name,
                            'width' => $width,
                            'height' => $height,
                        ];
                    }
                    if ((string)$imageDefinition[static::ENTITY_TYPE_SCENES_THUMB] === 'true') {
                        $images[static::ENTITY_TYPE_SCENES_THUMB]['classname'] = 'Scene';
                        $images[static::ENTITY_TYPE_SCENES_THUMB]['imageTypes'][] = [
                            'name' => $name,
                            'width' => $width,
                            'height' => $height,
                        ];
                    }
                }
                if ((string)$imageDefinition[static::ENTITY_TYPE_STORES] === 'true') {
                    $images[static::ENTITY_TYPE_STORES]['classname'] = 'Store';
                    $images[static::ENTITY_TYPE_STORES]['imageTypes'][] = [
                        'name' => $name,
                        'width' => $width,
                        'height' => $height,
                    ];
                }
            }
        }

        foreach ($images as $imageEntityName => $imageEntity) {

            $existingImageEntity = static::getImageEntityInfo($imageEntityName);
            $imageEntityId = (int)$existingImageEntity['id_image_entity'] ?? 0;

            $imageEntityObj = new ImageEntity($imageEntityId);
            $imageEntityObj->name = $imageEntityName;
            $imageEntityObj->classname = $imageEntity['classname'] ?? $classname;
            $displayName = [];
            foreach (Language::getLanguages(false, false, true) as $langId) {
                if (isset($imageEntityObj->display_name[$langId]) && $imageEntityObj->display_name[$langId]) {
                    $displayName[$langId] = $imageEntityObj->display_name[$langId];
                }  else {
                    $displayName[$langId] = $imageEntity['displayName'] ?? ucfirst($imageEntityName);
                }
            }
            $imageEntityObj->display_name = $displayName;
            $imageEntityObj->save();
            $imageEntityId = (int)$imageEntityObj->id;

            if ($imageEntityId && !empty($imageEntity['imageTypes']) && is_array($imageEntity['imageTypes'])) {
                foreach ($imageEntity['imageTypes'] as $imageType) {

                    $imageTypeObj = ImageType::getInstanceByName($imageType['name']);

                    // Adding missing image types
                    if (!$imageTypeObj->id) {
                        $imageTypeObj->name = $imageType['name'];
                        $imageTypeObj->width = (int)$imageType['width'];
                        $imageTypeObj->height = (int)$imageType['height'];
                        $imageTypeObj->add();
                    }

                    // Link imageType to imageEntity
                    if ($imageTypeObj->id) {
                        Db::getInstance()->insert('image_entity_type', [
                            'id_image_entity' => $imageEntityId,
                            'id_image_type' => $imageTypeObj->id
                        ], false, true, Db::REPLACE);
                    }
                }
            }
        }

        static::rebuildBasedOnOldTypes();

        Configuration::updateGlobalValue('TB_IMAGE_ENTITY_REBUILD_LAST', date('Y-m-d H:i:s'));
    }

    /**
     * Function to transform the old entity structure into table image_entity_type
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private static function rebuildBasedOnOldTypes()
    {
        // This function should only be executed once
        if (!Configuration::get('TB_IMAGE_ENTITY_REBUILD_LAST')) {

            $db = Db::getInstance();

            $query = new DbQuery();
            $query->select('*');
            $query->from('image_type');
            $imageTypes = $db->getArray($query);

            // Get ids_image_entity
            $ids_image_entity = [];

            foreach (static::getImageEntities() as $imageEntity) {
                $ids_image_entity[$imageEntity['name']] = $imageEntity['id_image_entity'];
            }

            $oldEntityTypes = [
                static::ENTITY_TYPE_PRODUCTS,
                static::ENTITY_TYPE_CATEGORIES,
                static::ENTITY_TYPE_MANUFACTURERS,
                static::ENTITY_TYPE_SUPPLIERS,
                static::ENTITY_TYPE_SCENES,
                static::ENTITY_TYPE_STORES,
            ];

            foreach ($imageTypes as $imageType) {
                foreach ($oldEntityTypes as $oldEntityType) {
                    if ($imageType[$oldEntityType] && isset($ids_image_entity[$oldEntityType])) {
                        $data = [
                            'id_image_entity' => $ids_image_entity[$oldEntityType],
                            'id_image_type' => $imageType['id_image_type']
                        ];
                        $db->insert('image_entity_type', $data, false, true, Db::REPLACE);
                    }
                }
            }
        }
    }

    /**
     * @return ImageEntity[]
     *
     * @throws PrestaShopException
     */
    public static function getAll()
    {
        $collection = new PrestaShopCollection('ImageEntity');
        return $collection->getResults();
    }

    /**
     * @param string $imageEntityName
     *
     * @return array|null
     *
     * @throws PrestaShopException
     */
    public static function getImageEntityInfo(string $imageEntityName)
    {
        if ($imageEntityName) {
            $entities = static::getImageEntities();
            if (isset($entities[$imageEntityName])) {
                return $entities[$imageEntityName];
            }
        }
        return null;
    }

    /**
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getImageEntities(): array
    {
        $langId = (int)Context::getContext()->language->id;
        $cacheKey = 'ImageEntity::getImageEntities_' . $langId;
        if (! Cache::isStored($cacheKey)) {
            $query = new DbQuery();
            $query->select('ie.*');
            $query->select('l.display_name');
            $query->from(static::$definition['table'], 'ie');
            $query->select('it.id_image_type, it.name AS image_type, it.width, it.height, it.id_image_type_parent');
            $query->leftJoin('image_entity_type', 'iet', '(iet.id_image_entity = ie.id_image_entity)');
            $query->leftJoin('image_type', 'it', '(iet.id_image_type = it.id_image_type)');
            $query->leftJoin('image_entity_lang', 'l', '(l.id_image_entity = ie.id_image_entity AND l.id_lang = '.$langId.')');
            $query->orderBy('ie.name ASC');

            $result = Db::getInstance()->getArray($query);

            $imageEntities = [];

            foreach ($result as $res) {

                $name = $res['name'];

                if (!isset($imageEntities[$name])) {
                    // Get data from object model $definition
                    $className = $res['classname'];
                    $definition = ObjectModel::getDefinition($className);

                    $imageEntities[$name] = [
                        'table' => $definition['table'],
                        'primary' => $definition['primary'],
                        'path' => $definition['images'][$name]['path'] ?? '',
                        'name' => $name,
                        'display_name' => $res['display_name'] ? $res['display_name'] : ucfirst($name),
                        'classname' => $className,
                        'id_image_entity' => (int)$res['id_image_entity'],
                        'imageTypes' => [],
                    ];
                }

                $imageTypeId = (int)$res['id_image_type'];
                if ($imageTypeId) {
                    $imageEntities[$name]['imageTypes'][] = [
                        'id_image_type' => $imageTypeId,
                        'name' => $res['image_type'],
                        'width' => (int)$res['width'],
                        'height' => (int)$res['height'],
                        'id_image_type_parent' => (int)$res['id_image_type_parent'],
                    ];
                }
            }

            Cache::store($cacheKey, $imageEntities);
            return $imageEntities;
        }

        return Cache::retrieve($cacheKey);
    }

    /**
     * Method associates ImageTypes records with this ImageEntity object
     *
     * @param int[] $imageTypeIds ids of image types
     * @param bool $deleteExisting if true, existing associations will be deleted first
     *
     * @throws PrestaShopException
     */
    public function associateImageTypes(array $imageTypeIds, $deleteExisting = false)
    {
        $imageEntityId = (int)$this->id;
        if ($imageEntityId) {
            $conn = Db::getInstance();
            if ($deleteExisting) {
                $conn->delete('image_entity_type', "id_image_entity = $imageEntityId");
            }

            foreach ($imageTypeIds as $imageTypeId) {
                $this->associateImageType((int)$imageTypeId);
            }
        }
    }

    /**
     * @param int $imageTypeId
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function associateImageType(int $imageTypeId)
    {
        $imageEntityId = (int)$this->id;
        if ($imageEntityId && $imageTypeId) {
            Db::getInstance()->insert('image_entity_type', [
                'id_image_entity' => $imageEntityId,
                'id_image_type' => $imageTypeId,
            ], false, true, Db::INSERT_IGNORE);
        }
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
        Cache::clean('ImageEntity::*');
        Cache::clean('ImageType::*');
        return $res;
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getWsImageTypes()
    {
        $result = [];
        $info = static::getImageEntityInfo($this->name);
        if ($info) {
            foreach ($info['imageTypes'] as $type) {
                $result[] = [
                    'id' => (int)$type['id_image_type']
                ];
            }
        }
        return $result;
    }

}
