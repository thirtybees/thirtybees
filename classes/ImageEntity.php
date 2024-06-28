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
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'image_entity',
        'primary' => 'id_image_entity',
        'fields'  => [
            'name'          => ['type' => self::TYPE_STRING, 'validate' => 'isImageTypeName', 'required' => true, 'size' => 64],
            'classname'     => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 64],
        ],
        'keys' => [
            'image_entity' => [
                'image_entity_name' => ['type' => ObjectModel::KEY, 'columns' => ['name']],
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

            $existingImageEntity = static::getImageEntities($imageEntityName);
            $id_image_entity = $existingImageEntity['id_image_entity'] ?? 0;

            $imageEntityObj = new ImageEntity($id_image_entity);
            $imageEntityObj->name = $imageEntityName;
            $imageEntityObj->classname = $imageEntity['classname'] ?? $classname;
            $imageEntityObj->save();

            if ($imageEntityObj->id && !empty($imageEntity['imageTypes'])) {
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
                        Db::getInstance()->insert('image_entity_type', ['id_image_entity' => $imageEntityObj->id, 'id_image_type' => $imageTypeObj->id], false, true, Db::REPLACE);
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
     * @param string $imageEntityName image entity name (products, manufacturers, beesblogbposts)
     * @param bool $withImageTypes if true, image types of the entity will be collected too
     * @param bool $orderImageTypeBySize
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getImageEntities($imageEntityName = '', $withImageTypes = false, $orderImageTypeBySize = false)
    {

        $query = new DbQuery();
        $query->select('ie.*');
        $query->from(static::$definition['table'], 'ie');

        if ($withImageTypes) {
            $query->select('it.id_image_type, it.name AS image_type, it.width, it.height, it.id_image_type_parent');
            $query->leftJoin('image_entity_type', 'iet', 'iet.id_image_entity=ie.id_image_entity');
            $query->leftJoin('image_type', 'it', 'iet.id_image_type=it.id_image_type');
        }

        if ($imageEntityName) {
            $query->where("ie.name = '" . pSQL($imageEntityName) ."'");
        }

        if ($withImageTypes && $orderImageTypeBySize) {
            $query->orderBy('it.width DESC, it.height DESC, it.name ASC');
        } else {
            $query->orderBy('ie.name ASC');
        }

        $result = Db::getInstance()->getArray($query);

        $imageEntities = [];

        foreach ($result as $res) {

            $name = $res['name'];

            // Get data from object model $definition
            $definition = $res['classname']::$definition;
            $imageEntities[$name]['table'] = $definition['table'];
            $imageEntities[$name]['primary'] = $definition['primary'];
            $imageEntities[$name]['path'] = $definition['images'][$name]['path'];

            $imageEntities[$name]['name'] = $name;
            $imageEntities[$name]['classname'] = $res['classname'];
            $imageEntities[$name]['id_image_entity'] = $res['id_image_entity'];

            if ($withImageTypes) {
                $imageEntities[$name]['imageTypes'][] = [
                    'id_image_type' => (int)$res['id_image_type'],
                    'name' => $res['image_type'],
                    'width' => (int)$res['width'],
                    'height' => (int)$res['height'],
                    'id_image_type_parent' => (int)$res['id_image_type_parent'],
                ];
            }

        }

        if ($imageEntityName) {
            return $imageEntities[$imageEntityName] ?? [];
        }

        return $imageEntities;
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

}
