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

use Thirtybees\Core\DependencyInjection\ServiceLocator;
use Thirtybees\Core\Error\ErrorUtils;

/**
 * Class AdminImagesControllerCore
 *
 * @property ImageType|null $object
 */
class AdminImagesControllerCore extends AdminController
{
    /** @var int $start_time */
    protected $start_time = 0;
    /** @var int $max_execution_time */
    protected $max_execution_time = 7200;

    /**
     * AdminImagesControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'image_type';
        $this->className = 'ImageType';
        $this->lang = false;
        $this->context = Context::getContext();

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->bulk_actions = [
            'delete' =>
                [
                    'text'    => $this->l('Delete selected'),
                    'confirm' => $this->l('Delete selected items?'),
                    'icon'    => 'icon-trash',
                ],
        ];

        $this->_select = " image_aliases, image_entities ";

        $this->_join = "
            LEFT JOIN (
                SELECT id_image_type_parent, GROUP_CONCAT(name SEPARATOR ', ') AS image_aliases
                FROM "._DB_PREFIX_."image_type
                GROUP BY id_image_type_parent
            ) alias ON alias.id_image_type_parent=a.id_image_type
        ";

        $this->_join.= "
            LEFT JOIN (
                SELECT id_image_type, GROUP_CONCAT(name SEPARATOR ', ') AS image_entities
                FROM "._DB_PREFIX_."image_entity_type AS iet
                LEFT JOIN "._DB_PREFIX_."image_entity AS ie ON ie.id_image_entity=iet.id_image_entity
                GROUP BY iet.id_image_type
            ) entities ON entities.id_image_type=a.id_image_type
        ";

        $this->_where = ' AND (a.id_image_type_parent IS NULL OR a.id_image_type_parent=0) ';

        $this->_group = ' GROUP BY a.id_image_type ';

        $this->fields_list = [
            'id_image_type'  => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name'           => ['title' => $this->l('Name')],
            'width'          => ['title' => $this->l('Width'), 'suffix' => ' px'],
            'height'         => ['title' => $this->l('Height'), 'suffix' => ' px'],
            'image_aliases'  => ['title' => $this->l('Aliases'), 'havingFilter' => true],
            'image_entities' => ['title' => $this->l('Image Entities'), 'havingFilter' => true],
        ];

        $imageFormats = [];

        foreach (ImageManager::getAllowedImageExtensions(true, true) as $imageExtension) {
            $imageFormats[] = ['id' => $imageExtension, 'name' => $imageExtension];
        }

        if (ImageManager::serverSupportsWebp()) {
            $desc = $this->l('It\'s recommended to use modern webp extension. Note: tb does serve old browser with jpg format, so you are fully backward compatible.');
        }
        else {
            $desc = Translate::ppTags($this->l('[1]Warning[/1]: your server does not support webp images'), ['<b>']);
        }

        $this->fields_options = [
            'images' => [
                'title'       => $this->l('Images generation options'),
                'icon'        => 'icon-picture',
                'top'         => '',
                'bottom'      => '',
                'description' => $this->l('We recommend the usage of webp if your server and theme support it, otherwise jpg.').'<br /><br />'.$this->l('WARNING: This feature may not be compatible with your theme, or with some of your modules. In particular, PNG mode is not compatible with the Watermark module. If you encounter any issues, turn it off by selecting "Use JPEG".'),
                'fields'      => [
                    'TB_IMAGE_EXTENSION'            => [
                        'title'    => $this->l('Image extension'),
                        'show'     => true,
                        'required' => true,
                        'type'       => 'select',
                        'list'       => $imageFormats,
                        'identifier' => 'id',
                        'visibility' => Shop::CONTEXT_ALL,
                        'desc'       => $desc,
                    ],
                    'TB_IMAGE_QUALITY'             => [
                        'title'      => $this->l('Image quality'),
                        'hint'       => $this->l('Ranges from 0 (worst quality, smallest file) to 100 (best quality, biggest file).').' '.$this->l('Recommended: 90.'),
                        'validation' => 'isUnsignedId',
                        'required'   => true,
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'TB_IMAGE_CONVERSION'            => [
                        'title'    => $this->l('Source file extension'),
                        'show'     => true,
                        'required' => true,
                        'type'       => 'select',
                        'list'       => [
                            ['id' => 'original', 'name' => $this->l('Uploaded file')],
                            ['id' => 'converted', 'name' => $this->l('Converted file')],
                            ['id' => 'both', 'name' => $this->l('Uploaded + Converted file')],
                        ],
                        'identifier' => 'id',
                        'visibility' => Shop::CONTEXT_ALL,
                        'hint' => $this->l('In which file extension(s), do you want to hold uploaded images on your server?'),
                        'desc' => $this->l('If your theme or any module is using source images, you should save the image also in converted extension. It means, that your image are always available in the selected extension above.')
                    ],
                    'PS_IMAGE_GENERATION_METHOD'  => [
                        'title'      => $this->l('Generate images based on one side of the source image'),
                        'validation' => 'isUnsignedId',
                        'required'   => false,
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => [
                            [
                                'id'   => '0',
                                'name' => $this->l('Automatic (longest side)'),
                            ],
                            [
                                'id'   => '1',
                                'name' => $this->l('Width'),
                            ],
                            [
                                'id'   => '2',
                                'name' => $this->l('Height'),
                            ],
                        ],
                        'identifier' => 'id',
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_PRODUCT_PICTURE_MAX_SIZE' => [
                        'title'      => $this->l('Maximum file size of product customization pictures'),
                        'hint'       => $this->l('The maximum file size of pictures that customers can upload to customize a product (in bytes).'),
                        'validation' => 'isUnsignedInt',
                        'required'   => true,
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('bytes'),
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_PRODUCT_PICTURE_WIDTH'    => [
                        'title'      => $this->l('Product picture width'),
                        'hint'       => $this->l('Width of product customization pictures that customers can upload (in pixels).'),
                        'validation' => 'isUnsignedInt',
                        'required'   => true,
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'width'      => 'px',
                        'suffix'     => $this->l('pixels'),
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_PRODUCT_PICTURE_HEIGHT'   => [
                        'title'      => $this->l('Product picture height'),
                        'hint'       => $this->l('Height of product customization pictures that customers can upload (in pixels).'),
                        'validation' => 'isUnsignedInt',
                        'required'   => true,
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'height'     => 'px',
                        'suffix'     => $this->l('pixels'),
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_HIGHT_DPI'                => [
                        'type'       => 'bool',
                        'title'      => $this->l('Generate high resolution images'),
                        'required'   => false,
                        'is_bool'    => true,
                        'hint'       => $this->l('This will generate an additional file for each image (thus doubling your total amount of images). Resolution of these images will be twice higher.'),
                        'desc'       => $this->l('Enable to optimize the display of your images on high pixel density screens.'),
                        'visibility' => Shop::CONTEXT_ALL,
                    ],

                ],
                'submit'      => ['title' => $this->l('Save')],
            ],
        ];

        $themeConfiguration = $this->context->theme->getConfiguration();
        if (!empty($themeConfiguration['lazy_load'])) {
            $this->fields_options['images']['fields']['TB_LAZY_LOAD'] = [
                'type'       => 'bool',
                'validation' => 'isBool',
                'cast'       => 'intval',
                'required'   => false,
                'title'      => $this->l('Lazy load images'),
                'desc'       => $this->l('Defer the loading of images until they scroll into view'),
                'visibility' => Shop::CONTEXT_ALL,
            ];
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Image type'),
                'icon'  => 'icon-picture',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name for the image type'),
                    'name'     => 'name',
                    'required' => true,
                    'hint'     => $this->l('Letters, underscores and hyphens only (e.g. "small_custom", "cart_medium", "large", "thickbox_extra-large").'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Width'),
                    'name'      => 'width',
                    'required'  => true,
                    'maxlength' => 5,
                    'suffix'    => $this->l('pixels'),
                    'hint'      => $this->l('Maximum image width in pixels.'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Height'),
                    'name'      => 'height',
                    'required'  => true,
                    'maxlength' => 5,
                    'suffix'    => $this->l('pixels'),
                    'hint'      => $this->l('Maximum image height in pixels.'),
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        // Adding image type aliases to the form
        $id_image_type = (int)Tools::getValue('id_image_type');

        $this->fields_form['input'][] = [
            'type' => 'select',
            'name' => 'ids_image_type_parent',
            'class'    => 'chosen',
            'multiple' => true,
            'options' => [
                'query' => ImageType::getImagesTypes(),
                'id' => 'id_image_type',
                'name' => 'name',
            ],
            'label' => $this->l('Image type aliases'),
            'hint' => $this->l('The selected image types won\'t be generated anymore. Instead the current image type will be used.'),
            'desc' => $this->l('Important: make sure, that you also select the responsible image entities below, if you are using aliases.')
        ];

        $this->fields_value['ids_image_type_parent[]'] = array_column(ImageType::getImageTypeAliases($id_image_type), 'id_image_type');

        // Adding image entities to the form
        $imageEntities = ImageEntity::getImageEntities();

        foreach ($imageEntities as $imageEntity) {
            $this->fields_form['input'][] = [
                'type'     => 'switch',
                'label'    => $imageEntity['name'],
                'name'     => $imageEntity['name'],
                'required' => false,
                'is_bool'  => true,
                'hint'     => sprintf($this->l('Should this type be used for %s images?'), $imageEntity['name']),
                'values'   => [
                    [
                        'id'    => 'products_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id'    => 'products_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ];

            $this->fields_value[$imageEntity['name']] = in_array($id_image_type, array_column($imageEntity['imageTypes'], 'id_image_type'));
        }



        parent::__construct();
    }

    /**
     * Post processing
     *
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        // When moving images, if duplicate images were found they are moved to a folder named duplicates/
        if (file_exists(_PS_PROD_IMG_DIR_.'duplicates/')) {
            $this->warnings[] = sprintf($this->l('Duplicate images were found when moving the product images. This is likely caused by unused demonstration images. Please make sure that the folder %s only contains demonstration images, and then delete it.'), _PS_PROD_IMG_DIR_.'duplicates/');
        }

        if (Tools::isSubmit('submitRegenerate'.$this->table)) {
            if ($this->hasEditPermission()) {
                if ($this->_regenerateThumbnails(Tools::getValue('type'), Tools::getValue('erase'))) {
                    Tools::redirectAdmin(static::$currentIndex.'&conf=9'.'&token='.$this->token);
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitOptions'.$this->table)) {
            if ($this->hasEditPermission()) {
                if (Tools::getIntValue('TB_IMAGE_QUALITY') < 0 || Tools::getIntValue('TB_IMAGE_QUALITY') > 100) {
                    $this->errors[] = Tools::displayError('Incorrect value for the image quality.');
                } elseif (
                    !Configuration::updateValue('TB_IMAGE_EXTENSION', Tools::getValue('TB_IMAGE_EXTENSION')) ||
                    !Configuration::updateValue('TB_IMAGE_QUALITY', Tools::getValue('TB_IMAGE_QUALITY'))
                ) {
                    $this->errors[] = Tools::displayError('Unknown error.');
                } else {
                    $this->confirmations[] = $this->_conf[6];
                }

                parent::postProcess();
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } else {
            parent::postProcess();

            // Save image type aliases & image type entities
            if (Tools::isSubmit('submitAdd' . $this->table) && $this->object->id) {

                $imageTypeId = (int)$this->object->id;
                $db = Db::getInstance();

                // Reset old id_image_type_parent value
                $db->update('image_type', ['id_image_type_parent' => 0], 'id_image_type_parent = ' . $imageTypeId);

                if (!empty($ids_image_type_parent = Tools::getValue('ids_image_type_parent'))) {
                    foreach ($ids_image_type_parent as $id_image_type_parent) {
                        $id_image_type_parent = (int)$id_image_type_parent;
                        if ($imageTypeId !== $id_image_type_parent) {
                            $db->update('image_type', ['id_image_type_parent' => $imageTypeId], "id_image_type = $id_image_type_parent OR id_image_type_parent = $id_image_type_parent");
                        }
                    }
                }

                // Delete old image_entity_type entries
                $db->delete('image_entity_type', 'id_image_type=' . $imageTypeId);

                // BC: keep legacy properties in tb_image_type synchronized
                $values = [];
                foreach (ImageEntity::getLegacyImageEntities() as $column) {
                    $values[$column] = 0;
                }
                $db->update('image_type', $values, 'id_image_type = ' . $imageTypeId);

                foreach (ImageEntity::getAll() as $imageEntity) {
                    if (Tools::getValue($imageEntity->name)) {
                        $imageEntity->associateImageType($imageTypeId);
                    }
                }
            }
        }
    }

    /**
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessRegenerateThumbnails()
    {
        $this->setJSendErrorHandling();

        $request = json_decode(file_get_contents('php://input'));
        $entityType = $request->entity_type;
        if (!$entityType) {
            $this->ajaxDie(json_encode([
                'hasError' => true,
                'errors'   => [$this->l('Entity type missing')],
            ]));
        }

        $imageEntityInfo = ImageEntity::getImageEntityInfo($entityType);
        if (! $imageEntityInfo) {
            $this->ajaxDie(json_encode([
                'hasError' => true,
                'errors'   => [$this->l('Wrong entity type')],
            ]));
        }

        $imageEntityId = (int)$imageEntityInfo['id_image_entity'];

        $idEntity = $this->getNextEntityId($imageEntityId);
        if (!$idEntity) {
            $this->ajaxDie(json_encode([
                'hasError'    => true,
                'errors'      => [$this->l('Thumbnails of this type have already been generated')],
                'indexStatus' => $this->getIndexationStatus(),
            ]));
        }

        try {
            $this->updateRegenerationStatus($imageEntityId, $idEntity, 'in_progress');
            ImageManager::generateImageTypesByEntity($request->entity_type, $idEntity);
            $this->updateRegenerationStatus($imageEntityId, $idEntity, 'completed');
        } catch (Throwable $e) {
            $errorHandler = ServiceLocator::getInstance()->getErrorHandler();
            $errorHandler->logFatalError(ErrorUtils::describeException($e));
            $this->updateRegenerationStatus($imageEntityId, $idEntity, 'failed', $e->getMessage());
            $this->errors[] = $e->getMessage();
        }

        if (!Configuration::get('TB_IMAGES_UPD_DEFAULT')) {
            Configuration::updateValue('TB_IMAGES_UPD_DEFAULT', 1);
            $this->_regenerateNoPictureImages();
        }

        $this->ajaxDie(json_encode([
            'hasError' => true,
            'errors'   => $this->errors,
            'indexStatus' => $this->getIndexationStatus(),
        ]));
    }

    /**
     * @param int $imageEntityId
     * @param int $entityId
     * @param string $status
     * @param string|null $error
     *
     * @return void
     * @throws PrestaShopException
     */
    protected function updateRegenerationStatus(int $imageEntityId, int $entityId, string $status, ?string $error = null)
    {
        $imageEntityId = (int)$imageEntityId;
        $entityId = (int)$entityId;
        $conn = Db::getInstance();
        $conn->update('image_regeneration', [
            'status' => pSQL($status),
            'error' => pSQL($error),
            'date_upd' => date('Y-m-d H:i:s'),
        ], "id_image_entity = $imageEntityId AND id_entity = $entityId");
    }

    /**
     * Ajax - delete all previous images
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessDeleteOldImages()
    {
        $this->setJSendErrorHandling();

        Db::getInstance()->update('image_regeneration', [
            'status' => 'pending',
            'error' => null,
        ]);

        foreach (ImageEntity::getImageEntities() as $imageEntity) {
            try {
                // Getting format generation
                $this->_deleteOldImages($imageEntity['path'], $imageEntity['imageTypes'], ($imageEntity['name'] == ImageEntity::ENTITY_TYPE_PRODUCTS));
            } catch (PrestaShopException $e) {
                $this->errors[] = $e->getMessage();
            }
        }

        $this->ajaxDie(json_encode([
            'hasError'    => !empty($this->errors),
            'errors'      => $this->errors,
            'indexStatus' => $this->getIndexationStatus(),
        ]));
    }

    /**
     * Ajax - delete all previous images
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessResetImageStats()
    {
        $this->setJSendErrorHandling();

        // Reset default images
        Configuration::updateValue('TB_IMAGES_UPD_DEFAULT', 0);

        Db::getInstance()->update('image_regeneration', [
            'status' => 'pending',
            'error' => null,
        ]);

        $this->ajaxDie(json_encode([
            'hasError'    => !empty($this->errors),
            'errors'      => $this->errors,
            'indexStatus' => $this->getIndexationStatus(),
        ]));
    }

    /**
     * Regenerate thumbnails
     *
     * @param string $type
     * @param bool $deleteOldImages
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.4 Replaced by ajax regeneration
     */
    protected function _regenerateThumbnails($type = 'all', $deleteOldImages = false)
    {
        $this->start_time = time();
        ini_set('max_execution_time', $this->max_execution_time); // ini_set may be disabled, we need the real value
        $this->max_execution_time = (int) ini_get('max_execution_time');

        // Launching generation process
        foreach (ImageEntity::getImageEntities() as $imageEntity) {
            if ($type!='all' && $type!=$imageEntity['name']) {
                continue;
            }

            // Getting format generation
            $imagesTypes = $imageEntity['imageTypes'];

            if ($type!='all') {
                $format = strval(Tools::getValue('format_'.$type));
                if ($format != 'all') {
                    foreach ($imagesTypes as $k => $form) {
                        if ($form['id_image_type'] != $format) {
                            unset($imagesTypes[$k]);
                        }
                    }
                }
            }

            if ($deleteOldImages) {
                $this->_deleteOldImages($imageEntity['path'], $imagesTypes, ($imageEntity['name'] == ImageEntity::ENTITY_TYPE_PRODUCTS));
            }
            if (($return = $this->_regenerateNewImages($imageEntity['path'], $imagesTypes, ($imageEntity['name'] == ImageEntity::ENTITY_TYPE_PRODUCTS))) === true) {
                if (!count($this->errors)) {
                    $this->errors[] = sprintf(Tools::displayError('Cannot write images for this type: %s. Please check the %s folder\'s writing permissions.'), $imageEntity['name'], $imageEntity['path']);
                }
            } elseif ($return == 'timeout') {
                $this->errors[] = Tools::displayError('Only a part of the images have been regenerated. The server timed out before finishing.');
            }

            if ($imageEntity['name'] == ImageEntity::ENTITY_TYPE_PRODUCTS) {
                if ($this->_regenerateWatermark($imageEntity['path'], $imagesTypes) == 'timeout') {
                    $this->errors[] = Tools::displayError('Server timed out. The watermark may not have been applied to all images.');
                }
            }
            if (!count($this->errors)) {
                if ($this->_regenerateNoPictureImages()) {
                    $this->errors[] = sprintf(Tools::displayError('Cannot write "No picture" image to (%s) images folder. Please check the folder\'s writing permissions.'), $imageEntity['name']);
                }
            }
        }

        return (count($this->errors) > 0 ? false : true);
    }

    /**
     * Delete resized image then regenerate new one with updated settings
     *
     * @param string $dir
     * @param array $imageTypes
     * @param bool $product
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function _deleteOldImages($dir, $imageTypes, $product = false)
    {
        if (!is_dir($dir)) {
            return;
        }

        $imageExtensions = ImageManager::getAllowedImageExtensions(false, true);

        // Faster delete on servers that support it
        if (function_exists('chdir') && function_exists('exec') && shell_exec('which find')) {

            foreach ($imageExtensions as $imageExtension) {
                foreach ($imageTypes as $imageType) {
                    exec('cd '.escapeshellarg($dir).' && find . -name "*'.$imageType['name'].'.'.$imageExtension.'" -type f -delete');
                }
                exec('cd '.escapeshellarg($dir).' && find . -name "*2x.'.$imageExtension.'" -type f -delete');
                exec('cd '.escapeshellarg($dir).' && find . -name "*-watermark.'.$imageExtension.'" -type f -delete');
            }
            return;
        }

        $toDel = scandir($dir);

        $imageFormats = implode('|', $imageExtensions);

        foreach ($toDel as $d) {
            foreach ($imageTypes as $imageType) {
                if (preg_match('/^[0-9]+\-'.($product ? '[0-9]+\-' : '').$imageType['name'].'\.('.$imageFormats.')$/', $d)
                    || (count($imageTypes) > 1 && preg_match('/^[0-9]+\-[_a-zA-Z0-9-]*\.('.$imageFormats.')$/', $d))
                    || preg_match('/^([[:lower:]]{2})\-default\-'.$imageType['name'].'\.('.$imageFormats.')$/', $d)
                ) {
                    if (file_exists($dir.$d)) {
                        unlink($dir.$d);
                    }
                }
            }
        }

        // delete product images using new filesystem.
        if ($product) {
            $productsImages = Image::getAllImages();
            foreach ($productsImages as $image) {
                $imageObj = new Image($image['id_image']);
                $imageObj->id_product = $image['id_product'];
                if (file_exists($dir.$imageObj->getImgFolder())) {
                    $toDel = scandir($dir.$imageObj->getImgFolder());
                    foreach ($toDel as $d) {
                        foreach ($imageTypes as $imageType) {
                            if (preg_match('/^[0-9]+\-'.$imageType['name'].'\.('.$imageFormats.')$/', $d) || (count($imageTypes) > 1 && preg_match('/^[0-9]+\-[_a-zA-Z0-9-]*\.('.$imageFormats.')$/', $d))) {
                                if (file_exists($dir.$imageObj->getImgFolder().$d)) {
                                    unlink($dir.$imageObj->getImgFolder().$d);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Regenerate images for one entity
     *
     * @param string $entityType
     * @param int $idEntity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function regenerateNewImage($entityType, $idEntity)
    {
        $process = [
            'categories'    => _PS_CAT_IMG_DIR_,
            'manufacturers' => _PS_MANU_IMG_DIR_,
            'suppliers'     => _PS_SUPP_IMG_DIR_,
            'scenes'        => _PS_SCENE_IMG_DIR_,
            'products'      => _PS_PROD_IMG_DIR_,
            'stores'        => _PS_STORE_IMG_DIR_,
        ];
        $type = ImageType::getImagesTypes($entityType);

        $watermarkModules = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('m.`name`')
                ->from('module', 'm')
                ->leftJoin('hook_module', 'hm', 'hm.`id_module` = m.`id_module`')
                ->leftJoin('hook', 'h', 'hm.`id_hook` = h.`id_hook`')
                ->where('h.`name` = \'actionWatermark\'')
                ->where('m.`active` = 1')
        );

        if ($entityType !== 'products') {
            foreach ($type as $imageType) {
                // Customizable writing dir
                $dir = $newDir = $process[$entityType];
                $image = $idEntity.'.jpg';
                if ($imageType['name'] == 'thumb_scene') {
                    $newDir .= 'thumbs/';
                }
                if (!file_exists($newDir)) {
                    $this->errors[] = sprintf(
                        $this->l('Directory %s for image regeneration missing.'),
                        $newDir
                    );
                }
                $newFile = $newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'.jpg';
                if (file_exists($newFile) && !unlink($newFile)) {
                    $this->errors[] = sprintf(
                        $this->l('Can\'t remove old image %s.'),
                        $newFile
                    );
                }
                if (file_exists($dir.$image) && ! file_exists($newFile)) {
                    if ( ! filesize($dir.$image)) {
                        $this->errors[] = sprintf(
                            $this->l('Source file for %s id %s is corrupt: %s'),
                            $entityType,
                            $idEntity,
                            str_replace(_PS_ROOT_DIR_, '', $dir.$image)
                        );
                    } else {
                        $success = ImageManager::resize(
                            $dir.$image,
                            $newFile,
                            (int) $imageType['width'],
                            (int) $imageType['height']
                        );
                        if (ImageManager::retinaSupport()) {
                            if (!ImageManager::resize(
                                $dir.$image,
                                $newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'2x.jpg',
                                (int) $imageType['width'] * 2,
                                (int) $imageType['height'] * 2
                            )) {
                                $this->errors[] = sprintf(Tools::displayError('Failed to resize image file to high resolution (%s)'), $dir.$image);
                            }
                        }
                        if (ImageManager::generateWebpImages()) {
                            $success = ImageManager::resize(
                                $dir.$image,
                                $newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'.webp',
                                (int) $imageType['width'],
                                (int) $imageType['height'],
                                'webp'
                            ) && $success;
                            if (ImageManager::retinaSupport()) {
                                $success = ImageManager::resize(
                                    $dir.$image,
                                    $newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'2x.webp',
                                    (int) $imageType['width'] * 2,
                                    (int) $imageType['height'] * 2
                                ) && $success;
                            }
                        }

                        if (!$success) {
                            $this->errors[] = $this->l('Unable to resize image');
                        }
                    }
                }
            }
        } else {
            $productsImages = array_column(
                Image::getImages(null, $idEntity),
                'id_image'
            );
            foreach ($productsImages as $idImage) {
                $imageObj = new Image($idImage);
                $existingImage = $process[$entityType].$imageObj->getExistingImgPath().'.jpg';
                if (count($type) > 0) {
                    foreach ($type as $imageType) {
                        $newFile = $process[$entityType].$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'.jpg';
                        if (file_exists($newFile) && !unlink($newFile)) {
                            $this->errors[] = $this->l('Unable to generate new file');
                        }
                        if (!file_exists($newFile)) {
                            if (!ImageManager::resize($existingImage, $newFile, (int) ($imageType['width']), (int) ($imageType['height']))) {
                                $this->errors[] = sprintf($this->l('Original image is corrupt (%s) or bad permission on folder'), $existingImage);
                            }
                            if (ImageManager::generateWebpImages()) {
                                ImageManager::resize(
                                    $existingImage,
                                    $process[$entityType].$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'.webp',
                                    (int) $imageType['width'],
                                    (int) $imageType['height'],
                                    'webp'
                                );
                            }

                            if (ImageManager::retinaSupport()) {
                                if (!ImageManager::resize(
                                    $existingImage,
                                    $process[$entityType].$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'2x.jpg',
                                    (int) $imageType['width'] * 2,
                                    (int) $imageType['height'] * 2
                                )) {
                                    $this->errors[] = sprintf(Tools::displayError('Failed to resize image file to high resolution (%s)'), $existingImage);
                                }

                                if (!$this->errors && ImageManager::generateWebpImages()) {
                                    ImageManager::resize(
                                        $existingImage,
                                        $process[$entityType].$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'2x.webp',
                                        (int) $imageType['width'] * 2,
                                        (int) $imageType['height'] * 2,
                                        'webp'
                                    );
                                }
                            }
                        }
                    }
                }
                if (is_array($watermarkModules) && count($watermarkModules)) {
                    if (file_exists($process[$entityType].$imageObj->getExistingImgPath().'.jpg')) {
                        foreach ($watermarkModules as $module) {
                            $moduleInstance = Module::getInstanceByName($module['name']);
                            if ($moduleInstance && is_callable([$moduleInstance, 'hookActionWatermark'])) {
                                call_user_func([$moduleInstance, 'hookActionWatermark'], [
                                    'id_image'   => $imageObj->id,
                                    'id_product' => $imageObj->id_product,
                                    'image_type' => $type,
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param int $imageEntityId
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getNextEntityId(int $imageEntityId)
    {
        $query = (new DbQuery())
            ->select('MIN(r.id_entity)')
            ->from('image_regeneration', 'r')
            ->where('r.id_image_entity = ' . (int)$imageEntityId)
            ->where('r.status = "pending"');
        return (int)Db::readOnly()->getValue($query);
    }

    /**
     * Regenerate images
     *
     * @param string $dir
     * @param array[] $type
     * @param bool $productsImages
     *
     * @return bool|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @deprecated 1.0.4
     */
    protected function _regenerateNewImages($dir, $type, $productsImages = false)
    {
        if (!is_dir($dir)) {
            return false;
        }

        $legacyImageExtension = 'jpg';

        if (!$productsImages) {
            $formattedThumbScene = ImageType::getFormatedName('thumb_scene');
            $formattedMedium = ImageType::getFormatedName('medium');
            foreach (scandir($dir) as $image) {
                if (preg_match('/^[0-9]*\.'.$legacyImageExtension.'$/', $image)) {
                    foreach ($type as $imageType) {
                        // Customizable writing dir
                        $newDir = $dir;
                        if ($imageType['name'] == $formattedThumbScene) {
                            $newDir .= 'thumbs/';
                        }
                        if (!file_exists($newDir)) {
                            continue;
                        }

                        if (($dir == _PS_CAT_IMG_DIR_) && ($imageType['name'] == $formattedMedium) && is_file(_PS_CAT_IMG_DIR_.str_replace('.', '_thumb.', $image))) {
                            $image = str_replace('.', '_thumb.', $image);
                        }

                        if (!file_exists($newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'.'.$legacyImageExtension)) {
                            if (!file_exists($dir.$image) || !filesize($dir.$image)) {
                                $this->errors[] = sprintf(Tools::displayError('Source file does not exist or is empty (%s)'), $dir.$image);
                            } elseif (!ImageManager::resize($dir.$image, $newDir.substr(str_replace('_thumb.', '.', $image), 0, -4).'-'.stripslashes($imageType['name']).'.'.$legacyImageExtension, (int) $imageType['width'], (int) $imageType['height'], $legacyImageExtension)) {
                                $this->errors[] = sprintf(Tools::displayError('Failed to resize image file (%s)'), $dir.$image);
                            }

                            if (ImageManager::retinaSupport()) {
                                if (!ImageManager::resize($dir.$image, $newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'2x.'.$legacyImageExtension, (int) $imageType['width'] * 2, (int) $imageType['height'] * 2, $legacyImageExtension)) {
                                    $this->errors[] = sprintf(Tools::displayError('Failed to resize image file to high resolution (%s)'), $dir.$image);
                                }
                            }
                        }
                        // stop 4 seconds before the timeout, just enough time to process the end of the page on a slow server
                        if (time() - $this->start_time > $this->max_execution_time - 4) {
                            return 'timeout';
                        }
                    }
                }
            }
        } else {
            foreach (Image::getAllImages() as $image) {
                $imageObj = new Image($image['id_image']);
                $sourceImage = ImageManager::getSourceImage($dir.$imageObj->getImgFolder(), $imageObj->id);
                $sourceImageExtension = pathinfo($sourceImage, PATHINFO_EXTENSION);
                $defaultImageExtension = ImageManager::getDefaultImageExtension();
                if (file_exists($sourceImage) && filesize($sourceImage)) {
                    foreach ($type as $imageType) {
                        $imageByType = str_replace($imageObj->id.'.'.$sourceImageExtension, $imageObj->id.'.'.stripslashes($imageType['name']).'.'.$defaultImageExtension, $sourceImage);
                        if (!file_exists($imageByType)) {
                            if (!ImageManager::resize($sourceImage, $imageByType, (int) $imageType['width'], (int) $imageType['height'], $defaultImageExtension)) {
                                $this->errors[] = sprintf(Tools::displayError('Original image is corrupt (%s) for product ID %2$d or bad permission on folder'), $sourceImage, (int) $imageObj->id_product);
                            }
                            if (ImageManager::retinaSupport()) {
                                if (!ImageManager::resize($sourceImage, str_replace('-'.stripslashes($imageType['name']), '-'.stripslashes($imageType['name']).'2x', $imageByType), (int) $imageType['width'] * 2, (int) $imageType['height'] * 2, $defaultImageExtension)) {
                                    $this->errors[] = sprintf(Tools::displayError('Original image is corrupt (%s) for product ID %2$d or bad permission on folder'), $sourceImage, (int) $imageObj->id_product);
                                }
                            }
                        }
                    }
                } else {
                    $this->errors[] = sprintf(Tools::displayError('Original image is missing or empty (%1$s) for product ID %2$d'), $sourceImage, (int) $imageObj->id_product);
                }
                if (time() - $this->start_time > $this->max_execution_time - 4) { // stop 4 seconds before the tiemout, just enough time to process the end of the page on a slow server
                    return 'timeout';
                }
            }
        }

        return (bool) count($this->errors);
    }

    /**
     * Regenerate watermark
     *
     * @param string $dir
     * @param string|null $type
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function _regenerateWatermark($dir, $type = null)
    {
        $result = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('m.`name`')
                ->from('module', 'm')
                ->leftJoin('hook_module', 'hm', 'hm.`id_module` = m.`id_module`')
                ->leftJoin('hook', 'h', 'hm.`id_hook` = h.`id_hook`')
                ->where('h.`name` = \'actionWatermark\'')
                ->where('m.`active` = 1')
        );

        if ($result && count($result)) {
            $productsImages = Image::getAllImages();
            foreach ($productsImages as $image) {
                $imageObj = new Image($image['id_image']);
                if (ImageManager::getSourceImage($dir.$imageObj->getImgFolder(), $imageObj->id)) {
                    foreach ($result as $module) {
                        $moduleInstance = Module::getInstanceByName($module['name']);
                        if ($moduleInstance && is_callable([$moduleInstance, 'hookActionWatermark'])) {
                            call_user_func([$moduleInstance, 'hookActionWatermark'], ['id_image' => $imageObj->id, 'id_product' => $imageObj->id_product, 'image_type' => $type]);
                        }

                        if (time() - $this->start_time > $this->max_execution_time - 4) { // stop 4 seconds before the tiemout, just enough time to process the end of the page on a slow server
                            return 'timeout';
                        }
                    }
                }
            }
        }

        return '';
    }

    /**
     * Regenerate no-pictures images
     *
     * @param string $dir
     * @param string[][] $type
     * @param string[][] $languages
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function _regenerateNoPictureImages($dir = '', $type = [], $languages = [])
    {
        $success = true;

        foreach (Language::getLanguages(false) as $language) {
            $success = Language::regenerateDefaultImages($language['iso_code']) && $success;
        }

        return $success;
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_image_type'] = [
                'href' => static::$currentIndex.'&addimage_type&token='.$this->token,
                'desc' => $this->l('Add new image type', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        if ($this->display != 'edit' && $this->display != 'add') {
            $this->initRegenerate();

            $this->context->smarty->assign(
                [
                    'display_regenerate' => true,
                    'image_indexation' => $this->getIndexationStatus(),
                ]
            );
        }

        if ($this->display == 'edit') {
            $this->warnings[] = $this->l('After modification, do not forget to regenerate thumbnails');
        }

        parent::initContent();
    }

    /**
     * Init display for the thumbnails regeneration block
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initRegenerate()
    {
        $this->context->smarty->assign(
            [
                'imageEntities'   => ImageEntity::getImageEntities(),
            ]
        );
    }

    /**
     * Child validation
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function _childValidation()
    {
        if (!Tools::getIntValue('id_image_type') &&
            Validate::isImageTypeName($typeName = Tools::getValue('name')) &&
            ImageType::typeAlreadyExists($typeName)
        ) {
            $this->errors[] = Tools::displayError('This name already exists.');
        }
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getIndexationStatus()
    {
        static::updateImageRegenerationList();

        $conn = Db::readOnly();
        $return = [];
        foreach (ImageEntity::getImageEntities() as $entityType) {
            $imageEntityId = (int)$entityType['id_image_entity'];
            $name = $entityType['name'];

            $query = (new DbQuery())
                ->select('COUNT(1) AS total')
                ->select('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as indexed')
                ->select('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
                ->select('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending')
                ->from('image_regeneration', 'r')
                ->where('r.id_image_entity = ' . $imageEntityId);

            $data = $conn->getRow($query);
            $return[$name] = [
                'name' => $name,
                'display_name' => $entityType['display_name'],
                'total' => (int)$data['total'],
                'pending' => (int)$data['pending'],
                'failed' => (int)$data['failed'],
            ];
        }

        return $return;
    }

    /**
     * Adds records for missing entities into tb_image_regeneration table, and removes
     * old records
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public static function updateImageRegenerationList()
    {
        $conn = Db::getInstance();
        foreach (ImageEntity::getImageEntities() as $entityType) {
            $imageEntityId = (int)$entityType['id_image_entity'];
            $primary = bqSQL($entityType['primary']);
            $table = _DB_PREFIX_ . "image_regeneration";

            $insert = (
                "INSERT INTO $table(id_image_entity, id_entity, status, date_add, date_upd)\n" .
                "SELECT $imageEntityId, entity.$primary, 'pending', now(), now()\n" .
                "FROM " . _DB_PREFIX_ . $entityType['table'] . " entity\n" .
                "WHERE NOT EXISTS(SELECT 1 FROM " . _DB_PREFIX_ . "image_regeneration r WHERE r.id_image_entity = $imageEntityId AND r.id_entity = entity.$primary)"
            );
            $conn->execute($insert);

            $delete = (
                "DELETE FROM $table\n" .
                "WHERE id_image_entity = $imageEntityId\n" .
                "AND NOT EXISTS(SELECT 1 FROM " . _DB_PREFIX_ . $entityType['table'] . " entity WHERE $table.id_entity = entity.$primary)"
            );
            $conn->execute($delete);
        }
    }

}
