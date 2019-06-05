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
 * Class AdminImagesControllerCore
 *
 * @since 1.0.0
 */
class AdminImagesControllerCore extends AdminController
{
    // @codingStandardsIgnoreStart
    /** @var int $start_time */
    protected $start_time = 0;
    /** @var int $max_execution_time */
    protected $max_execution_time = 7200;
    /** @var bool $display_move */
    protected $display_move;
    // @codingStandardsIgnoreEnd

    /**
     * AdminImagesControllerCore constructor.
     *
     * @since 1.0.0
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

        $this->fields_list = [
            'id_image_type' => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name'          => ['title' => $this->l('Name')],
            'width'         => ['title' => $this->l('Width'), 'suffix' => ' px'],
            'height'        => ['title' => $this->l('Height'), 'suffix' => ' px'],
            'products'      => ['title' => $this->l('Products'), 'align' => 'center', 'type' => 'bool', 'callback' => 'printEntityActiveIcon', 'orderby' => false],
            'categories'    => ['title' => $this->l('Categories'), 'align' => 'center', 'type' => 'bool', 'callback' => 'printEntityActiveIcon', 'orderby' => false],
            'manufacturers' => ['title' => $this->l('Manufacturers'), 'align' => 'center', 'type' => 'bool', 'callback' => 'printEntityActiveIcon', 'orderby' => false],
            'suppliers'     => ['title' => $this->l('Suppliers'), 'align' => 'center', 'type' => 'bool', 'callback' => 'printEntityActiveIcon', 'orderby' => false],
            'stores'        => ['title' => $this->l('Stores'), 'align' => 'center', 'type' => 'bool', 'callback' => 'printEntityActiveIcon', 'orderby' => false],
        ];

        // Scenes tab has been removed by default from the installation, but may still exists in updates
        if (Tab::getIdFromClassName('AdminScenes')) {
            $this->fields_list['scenes'] = ['title' => $this->l('Scenes'), 'align' => 'center', 'type' => 'bool', 'callback' => 'printEntityActiveIcon', 'orderby' => false];
        }

        // No need to display the old image system migration tool except if product images are in _PS_PROD_IMG_DIR_
        $this->display_move = false;
        $dir = _PS_PROD_IMG_DIR_;
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false && $this->display_move == false) {
                    if (!is_dir($dir.DIRECTORY_SEPARATOR.$file) && $file[0] != '.' && is_numeric($file[0])) {
                        $this->display_move = true;
                    }
                }
                closedir($dh);
            }
        }

        $this->fields_options = [
            'images' => [
                'title'       => $this->l('Images generation options'),
                'icon'        => 'icon-picture',
                'top'         => '',
                'bottom'      => '',
                'description' => $this->l('JPEG images have a small file size and standard quality. PNG images have a larger file size, a higher quality and support transparency. Note that in all cases the image files will have the .jpg extension.').'<br /><br />'.$this->l('WARNING: This feature may not be compatible with your theme, or with some of your modules. In particular, PNG mode is not compatible with the Watermark module. If you encounter any issues, turn it off by selecting "Use JPEG".'),
                'fields'      => [
                    'PS_IMAGE_QUALITY'            => [
                        'title'    => $this->l('Image format'),
                        'show'     => true,
                        'required' => true,
                        'type'     => 'radio',
                        'choices'  => ['jpg' => $this->l('Use JPEG.'), 'png' => $this->l('Use PNG only if the base image is in PNG format.'), 'png_all' => $this->l('Use PNG for all images.')],
                    ],
                    'PS_JPEG_QUALITY'             => [
                        'title'      => $this->l('JPEG compression'),
                        'hint'       => $this->l('Ranges from 0 (worst quality, smallest file) to 100 (best quality, biggest file).').' '.$this->l('Recommended: 90.'),
                        'validation' => 'isUnsignedId',
                        'required'   => true,
                        'cast'       => 'intval',
                        'type'       => 'text',
                    ],
                    'PS_PNG_QUALITY'              => [
                        'title'      => $this->l('PNG compression'),
                        'hint'       => $this->l('PNG compression is lossless: unlike JPG, you do not lose image quality with a high compression ratio. However, photographs will compress very badly.').' '.$this->l('Ranges from 0 (biggest file) to 9 (smallest file, slowest decompression).').' '.$this->l('Recommended: 7.'),
                        'validation' => 'isUnsignedId',
                        'required'   => true,
                        'cast'       => 'intval',
                        'type'       => 'text',
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

        if ($this->display_move) {
            $this->fields_options['product_images']['fields']['PS_LEGACY_IMAGES'] = [
                'title'      => $this->l('Use the legacy image filesystem'),
                'hint'       => $this->l('This should be set to yes unless you successfully moved images in "Images" page under the "Preferences" menu.'),
                'validation' => 'isBool',
                'cast'       => 'intval',
                'required'   => false,
                'type'       => 'bool',
                'visibility' => Shop::CONTEXT_ALL,
            ];
        }

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
        if (!empty($themeConfiguration['webp'])) {
            $this->fields_options['images']['fields']['TB_USE_WEBP'] = [
                'type'       => 'bool',
                'validation' => 'isBool',
                'cast'       => 'intval',
                'required'   => false,
                'title'      => $this->l('Enable webp images'),
                'desc'       => $this->l('Serve smaller images in the webp format to browsers that support it'),
                'visibility' => Shop::CONTEXT_ALL,
            ];
            $this->fields_options['images']['fields']['TB_WEBP_QUALITY'] = [
                'title'      => $this->l('WEBP compression'),
                'hint'       => $this->l('Ranges from 0 (worst quality, smallest file) to 100 (best quality, biggest file).').' '.$this->l('Recommended: 90.'),
                'validation' => 'isUnsignedId',
                'required'   => true,
                'cast'       => 'intval',
                'type'       => 'text',
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
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Products'),
                    'name'     => 'products',
                    'required' => false,
                    'is_bool'  => true,
                    'hint'     => $this->l('This type will be used for Product images.'),
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
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Categories'),
                    'name'     => 'categories',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'hint'     => $this->l('This type will be used for Category images.'),
                    'values'   => [
                        [
                            'id'    => 'categories_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'categories_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Manufacturers'),
                    'name'     => 'manufacturers',
                    'required' => false,
                    'is_bool'  => true,
                    'hint'     => $this->l('This type will be used for Manufacturer images.'),
                    'values'   => [
                        [
                            'id'    => 'manufacturers_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'manufacturers_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Suppliers'),
                    'name'     => 'suppliers',
                    'required' => false,
                    'is_bool'  => true,
                    'hint'     => $this->l('This type will be used for Supplier images.'),
                    'values'   => [
                        [
                            'id'    => 'suppliers_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'suppliers_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Scenes'),
                    'name'     => 'scenes',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'hint'     => $this->l('This type will be used for Scene images.'),
                    'values'   => [
                        [
                            'id'    => 'scenes_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'scenes_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Stores'),
                    'name'     => 'stores',
                    'required' => false,
                    'is_bool'  => true,
                    'hint'     => $this->l('This type will be used for Store images.'),
                    'values'   => [
                        [
                            'id'    => 'stores_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'stores_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        parent::__construct();
    }

    /**
     * Print Entity Active icon
     *
     * @param mixed $value
     * @param mixed $object
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function printEntityActiveIcon($value, $object)
    {
        return ($value ? '<span class="list-action-enable action-enabled"><i class="icon-check"></i></span>' : '<span class="list-action-enable action-disabled"><i class="icon-remove"></i></span>');
    }

    /**
     * Post processing
     *
     * @return bool
     *
     * @since 1.0.0
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public function postProcess()
    {
        // When moving images, if duplicate images were found they are moved to a folder named duplicates/
        if (file_exists(_PS_PROD_IMG_DIR_.'duplicates/')) {
            $this->warnings[] = sprintf($this->l('Duplicate images were found when moving the product images. This is likely caused by unused demonstration images. Please make sure that the folder %s only contains demonstration images, and then delete it.'), _PS_PROD_IMG_DIR_.'duplicates/');
        }

        if (Tools::isSubmit('submitRegenerate'.$this->table)) {
            if ($this->tabAccess['edit'] === '1') {
                if ($this->_regenerateThumbnails(Tools::getValue('type'), Tools::getValue('erase'))) {
                    Tools::redirectAdmin(static::$currentIndex.'&conf=9'.'&token='.$this->token);
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitMoveImages'.$this->table)) {
            if ($this->tabAccess['edit'] === '1') {
                if ($this->_moveImagesToNewFileSystem()) {
                    Tools::redirectAdmin(static::$currentIndex.'&conf=25'.'&token='.$this->token);
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('submitOptions'.$this->table)) {
            if ($this->tabAccess['edit'] === '1') {
                if ((int) Tools::getValue('PS_JPEG_QUALITY') < 0
                    || (int) Tools::getValue('PS_JPEG_QUALITY') > 100
                ) {
                    $this->errors[] = Tools::displayError('Incorrect value for the selected JPEG image compression.');
                } elseif (((int) Tools::getValue('TB_WEBP_QUALITY') < 0 || (int) Tools::getValue('TB_WEBP_QUALITY') > 100)) {
                    $this->errors[] = Tools::displayError('Incorrect value for the selected WEBP image compression.');
                } elseif ((int) Tools::getValue('PS_PNG_QUALITY') < 0
                    || (int) Tools::getValue('PS_PNG_QUALITY') > 9
                ) {
                    $this->errors[] = Tools::displayError('Incorrect value for the selected PNG image compression.');
                } elseif (!Configuration::updateValue('PS_IMAGE_QUALITY', Tools::getValue('PS_IMAGE_QUALITY'))
                    || !Configuration::updateValue('PS_JPEG_QUALITY', Tools::getValue('PS_JPEG_QUALITY'))
                    || !Configuration::updateValue('PS_PNG_QUALITY', Tools::getValue('PS_PNG_QUALITY'))
                ) {
                    $this->errors[] = Tools::displayError('Unknown error.');
                } else {
                    $this->confirmations[] = $this->_conf[6];
                }

                return parent::postProcess();
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } else {
            return parent::postProcess();
        }
    }

    /**
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @since 1.0.4
     */
    public function ajaxProcessRegenerateThumbnails()
    {
        $request = json_decode(file_get_contents('php://input'));
        $entityType = $request->entity_type;
        if (!$entityType) {
            $this->ajaxDie(json_encode([
                'hasError' => true,
                'errors'   => [$this->l('Entity type missing')],
            ]));
        } elseif (!in_array($entityType, ['products', 'categories', 'manufacturers', 'suppliers', 'scenes', 'stores'])) {
            $this->ajaxDie(json_encode([
                'hasError' => true,
                'errors'   => [$this->l('Wrong entity type')],
            ]));
        }

        try {
            $idEntity = $this->getNextEntityId($request->entity_type);
            if (!$idEntity) {
                $this->ajaxDie(json_encode([
                    'hasError'    => true,
                    'errors'      => [$this->l('Thumbnails of this type have already been generated')],
                    'indexStatus' => $this->getIndexationStatus(),
                ]));
            }
            $this->regenerateNewImage($request->entity_type, $idEntity);
            Configuration::updateValue('TB_IMAGES_LAST_UPD_'.strtoupper($request->entity_type), $idEntity);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        $indexationStatus = $this->getIndexationStatus();
        if (!$indexationStatus || !array_sum(array_column(array_values($indexationStatus), 'indexed'))) {
            // First run, regenerate no picture images, too
            $process = array(
                'categories'    => _PS_CAT_IMG_DIR_,
                'manufacturers' => _PS_MANU_IMG_DIR_,
                'suppliers'     => _PS_SUPP_IMG_DIR_,
                'scenes'        => _PS_SCENE_IMG_DIR_,
                'products'      => _PS_PROD_IMG_DIR_,
                'stores'        => _PS_STORE_IMG_DIR_,
            );

            foreach ($process as $type => $dir) {
                $this->_regenerateNoPictureImages(
                    $dir,
                    ImageType::getImagesTypes($type),
                    Language::getLanguages(false)
                );
            }
        }

        $this->ajaxDie(json_encode([
            'hasError' => true,
            'errors'   => $this->errors,
            'indexStatus' => $indexationStatus,
        ]));
    }

    /**
     * Ajax - delete all previous images
     *
     * @since 1.0.4
     */
    public function ajaxProcessDeleteOldImages()
    {
        $process = [
            ['type' => 'categories',    'dir' => _PS_CAT_IMG_DIR_],
            ['type' => 'manufacturers', 'dir' => _PS_MANU_IMG_DIR_],
            ['type' => 'suppliers',     'dir' => _PS_SUPP_IMG_DIR_],
            ['type' => 'scenes',        'dir' => _PS_SCENE_IMG_DIR_],
            ['type' => 'products',      'dir' => _PS_PROD_IMG_DIR_],
            ['type' => 'stores',        'dir' => _PS_STORE_IMG_DIR_],
        ];

        foreach ($process as $proc) {
            try {
                // Getting format generation
                $formats = ImageType::getImagesTypes($proc['type']);
                Configuration::updateValue('TB_IMAGES_LAST_UPD_'.strtoupper($proc['type']), 0);
                $this->_deleteOldImages($proc['dir'], $formats, ($proc['type'] == 'products' ? true : false));
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
     * @since 1.0.4
     */
    public function ajaxProcessResetImageStats()
    {
        $process = [
            ['type' => 'categories',    'dir' => _PS_CAT_IMG_DIR_],
            ['type' => 'manufacturers', 'dir' => _PS_MANU_IMG_DIR_],
            ['type' => 'suppliers',     'dir' => _PS_SUPP_IMG_DIR_],
            ['type' => 'scenes',        'dir' => _PS_SCENE_IMG_DIR_],
            ['type' => 'products',      'dir' => _PS_PROD_IMG_DIR_],
            ['type' => 'stores',        'dir' => _PS_STORE_IMG_DIR_],
        ];

        foreach ($process as $proc) {
            try {
                // Getting format generation
                Configuration::updateValue('TB_IMAGES_LAST_UPD_'.strtoupper($proc['type']), 0);
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
     * Regenerate thumbnails
     *
     * @param string $type
     * @param bool   $deleteOldImages
     *
     * @return bool
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     * @deprecatd 1.0.4 Replaced by ajax regeneration
     */
    protected function _regenerateThumbnails($type = 'all', $deleteOldImages = false)
    {
        $this->start_time = time();
        ini_set('max_execution_time', $this->max_execution_time); // ini_set may be disabled, we need the real value
        $this->max_execution_time = (int) ini_get('max_execution_time');
        $languages = Language::getLanguages(false);

        $process = [
            ['type' => 'categories',    'dir' => _PS_CAT_IMG_DIR_],
            ['type' => 'manufacturers', 'dir' => _PS_MANU_IMG_DIR_],
            ['type' => 'suppliers',     'dir' => _PS_SUPP_IMG_DIR_],
            ['type' => 'scenes',        'dir' => _PS_SCENE_IMG_DIR_],
            ['type' => 'products',      'dir' => _PS_PROD_IMG_DIR_],
            ['type' => 'stores',        'dir' => _PS_STORE_IMG_DIR_],
        ];

        // Launching generation process
        foreach ($process as $proc) {
            if ($type != 'all' && $type != $proc['type']) {
                continue;
            }

            // Getting format generation
            $formats = ImageType::getImagesTypes($proc['type']);
            if ($type != 'all') {
                $format = strval(Tools::getValue('format_'.$type));
                if ($format != 'all') {
                    foreach ($formats as $k => $form) {
                        if ($form['id_image_type'] != $format) {
                            unset($formats[$k]);
                        }
                    }
                }
            }

            if ($deleteOldImages) {
                $this->_deleteOldImages($proc['dir'], $formats, ($proc['type'] == 'products' ? true : false));
            }
            if (($return = $this->_regenerateNewImages($proc['dir'], $formats, ($proc['type'] == 'products' ? true : false))) === true) {
                if (!count($this->errors)) {
                    $this->errors[] = sprintf(Tools::displayError('Cannot write images for this type: %s. Please check the %s folder\'s writing permissions.'), $proc['type'], $proc['dir']);
                }
            } elseif ($return == 'timeout') {
                $this->errors[] = Tools::displayError('Only a part of the images have been regenerated. The server timed out before finishing.');
            }

            if ($proc['type'] == 'products') {
                if ($this->_regenerateWatermark($proc['dir'], $formats) == 'timeout') {
                    $this->errors[] = Tools::displayError('Server timed out. The watermark may not have been applied to all images.');
                }
            }
            if (!count($this->errors)) {
                if ($this->_regenerateNoPictureImages($proc['dir'], $formats, $languages)) {
                    $this->errors[] = sprintf(Tools::displayError('Cannot write "No picture" image to (%s) images folder. Please check the folder\'s writing permissions.'), $proc['type']);
                }
            }
        }

        return (count($this->errors) > 0 ? false : true);
    }

    /**
     * Delete resized image then regenerate new one with updated settings
     *
     * @param string $dir
     * @param array  $type
     * @param bool   $product
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function _deleteOldImages($dir, $type, $product = false)
    {
        if (!is_dir($dir)) {
            return;
        }

        // Faster delete on servers that support it
        if (function_exists('chdir') && function_exists('exec') && shell_exec('which find')) {
            exec('cd '.escapeshellarg($dir).' && find . -name "*_default.jpg" -type f -delete');
            exec('cd '.escapeshellarg($dir).' && find . -name "*_thumbs.jpg" -type f -delete');
            exec('cd '.escapeshellarg($dir).' && find . -name "*2x.jpg" -type f -delete');
            exec('cd '.escapeshellarg($dir).' && find . -name "*-watermark.jpg" -type f -delete');
            exec('cd '.escapeshellarg($dir).' && find . -name "*.webp" -type f -delete');

            return;
        }

        $toDel = scandir($dir);

        foreach ($toDel as $d) {
            foreach ($type as $imageType) {
                if (preg_match('/^[0-9]+\-'.($product ? '[0-9]+\-' : '').$imageType['name'].'\.(jpg|webp)$/', $d)
                    || (count($type) > 1 && preg_match('/^[0-9]+\-[_a-zA-Z0-9-]*\.(jpg|webp)$/', $d))
                    || preg_match('/^([[:lower:]]{2})\-default\-'.$imageType['name'].'\.(jpg|webp)$/', $d)
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
                        foreach ($type as $imageType) {
                            if (preg_match('/^[0-9]+\-'.$imageType['name'].'\.(jpg|webp)$/', $d) || (count($type) > 1 && preg_match('/^[0-9]+\-[_a-zA-Z0-9-]*\.(jpg|webp)$/', $d))) {
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
     * @param int    $idEntity
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @since 1.0.4
     */
    protected function regenerateNewImage($entityType, $idEntity)
    {
        $process = array(
            'categories'    => _PS_CAT_IMG_DIR_,
            'manufacturers' => _PS_MANU_IMG_DIR_,
            'suppliers'     => _PS_SUPP_IMG_DIR_,
            'scenes'        => _PS_SCENE_IMG_DIR_,
            'products'      => _PS_PROD_IMG_DIR_,
            'stores'        => _PS_STORE_IMG_DIR_,
        );
        $type = ImageType::getImagesTypes($entityType);

        $watermarkModules = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('m.`name`')
                ->from('module', 'm')
                ->leftJoin('hook_module', 'hm', 'hm.`id_module` = m.`id_module`')
                ->leftJoin('hook', 'h', 'hm.`id_hook` = h.`id_hook`')
                ->where('h.`name` = \'actionWatermark\'')
                ->where('m.`active` = 1')
        );

        if ($entityType !== 'products') {
            foreach ($type as $k => $imageType) {
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
                        if (ImageManager::webpSupport()) {
                            $success &= ImageManager::resize(
                                $dir.$image,
                                $newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'.webp',
                                (int) $imageType['width'],
                                (int) $imageType['height'],
                                'webp'
                            );
                            if (ImageManager::retinaSupport()) {
                                $success &= ImageManager::resize(
                                    $dir.$image,
                                    $newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'2x.webp',
                                    (int) $imageType['width'] * 2,
                                    (int) $imageType['height'] * 2
                                );
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
                            if (ImageManager::webpSupport()) {
                                ImageManager::resize(
                                    $existingImage,
                                    $process[$entityType].$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'.webp',
                                    (int) $imageType['width'],
                                    (int) $imageType['height'],
                                    'webp'
                                );
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
     * @param string $entityType
     *
     * @return int
     * @throws PrestaShopException
     *
     * @since 1.0.4
     */
    protected function getNextEntityId($entityType)
    {
        if ($entityType === 'categories') {
            $primary = 'id_category';
            $table = 'category';
        } else {
            $primary = 'id_'.rtrim($entityType, 's');
            $table = rtrim($entityType, 's');
        }

        $lastId = (int) Configuration::get('TB_IMAGES_LAST_UPD_'.strtoupper($entityType));

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MIN(`'.bqSQL($primary).'`)')
                ->from($table)
                ->where('`'.bqSQL($primary).'` > '.(int) $lastId)
        );
    }

    /**
     * Regenerate images
     *
     * @param      $dir
     * @param      $type
     * @param bool $productsImages
     *
     * @return bool|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @since 1.0.0
     * @deprecated 1.0.4
     */
    protected function _regenerateNewImages($dir, $type, $productsImages = false)
    {
        if (!is_dir($dir)) {
            return false;
        }

        $generateHighDpiImages = (bool) Configuration::get('PS_HIGHT_DPI');

        if (!$productsImages) {
            $formattedThumbScene = ImageType::getFormatedName('thumb_scene');
            $formattedMedium = ImageType::getFormatedName('medium');
            foreach (scandir($dir) as $image) {
                if (preg_match('/^[0-9]*\.jpg$/', $image)) {
                    foreach ($type as $k => $imageType) {
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

                        if (!file_exists($newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'.jpg')) {
                            if (!file_exists($dir.$image) || !filesize($dir.$image)) {
                                $this->errors[] = sprintf(Tools::displayError('Source file does not exist or is empty (%s)'), $dir.$image);
                            } elseif (!ImageManager::resize($dir.$image, $newDir.substr(str_replace('_thumb.', '.', $image), 0, -4).'-'.stripslashes($imageType['name']).'.jpg', (int) $imageType['width'], (int) $imageType['height'])) {
                                $this->errors[] = sprintf(Tools::displayError('Failed to resize image file (%s)'), $dir.$image);
                            }

                            if ($generateHighDpiImages) {
                                if (!ImageManager::resize($dir.$image, $newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'2x.jpg', (int) $imageType['width'] * 2, (int) $imageType['height'] * 2)) {
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
                $existingImg = $dir.$imageObj->getExistingImgPath().'.jpg';
                if (file_exists($existingImg) && filesize($existingImg)) {
                    foreach ($type as $imageType) {
                        if (!file_exists($dir.$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'.jpg')) {
                            if (!ImageManager::resize($existingImg, $dir.$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'.jpg', (int) $imageType['width'], (int) $imageType['height'])) {
                                $this->errors[] = sprintf(Tools::displayError('Original image is corrupt (%s) for product ID %2$d or bad permission on folder'), $existingImg, (int) $imageObj->id_product);
                            }

                            if (ImageManager::retinaSupport()) {
                                if (!ImageManager::resize($existingImg, $dir.$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'2x.jpg', (int) $imageType['width'] * 2, (int) $imageType['height'] * 2)) {
                                    $this->errors[] = sprintf(Tools::displayError('Original image is corrupt (%s) for product ID %2$d or bad permission on folder'), $existingImg, (int) $imageObj->id_product);
                                }
                            }
                            if(!$this->errors && ImageManager::webpSupport()) {
                                $imgRes = imagecreatefromjpeg($dir.$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'.jpg');
                                ImageManager::resize(
                                    $imgRes,
                                    $dir.$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'.webp',
                                    (int) $imageType['width'] * 2,
                                    (int) $imageType['height'] * 2,
                                    'webp'
                                );
                            }
                        }
                    }
                } else {
                    $this->errors[] = sprintf(Tools::displayError('Original image is missing or empty (%1$s) for product ID %2$d'), $existingImg, (int) $imageObj->id_product);
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
     * @param null   $type
     *
     * @return string
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function _regenerateWatermark($dir, $type = null)
    {
        $result = Db::getInstance()->executeS(
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
                if (file_exists($dir.$imageObj->getExistingImgPath().'.jpg')) {
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
     * @param string     $dir
     * @param string[][] $type
     * @param string[][] $languages
     *
     * @return bool
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    protected function _regenerateNoPictureImages($dir, $type, $languages)
    {
        $errors = false;

        foreach ($type as $imageType) {
            foreach ($languages as $language) {
                $file = $dir.$language['iso_code'].'.jpg';
                if (!file_exists($file)) {
                    $file = _PS_PROD_IMG_DIR_.Language::getIsoById((int) Configuration::get('PS_LANG_DEFAULT')).'.jpg';
                }
                if (!file_exists($dir.$language['iso_code'].'-default-'.stripslashes($imageType['name']).'.jpg')) {
                    if (!ImageManager::resize($file, $dir.$language['iso_code'].'-default-'.stripslashes($imageType['name']).'.jpg', (int) $imageType['width'], (int) $imageType['height'])) {
                        $errors = true;
                    }

                    if (ImageManager::webpSupport()) {
                        ImageManager::resize(
                            $file,
                            $dir.$language['iso_code'].'-default-'.stripslashes($imageType['name']).'.webp',
                            (int) $imageType['width'],
                            (int) $imageType['height'],
                            'webp'
                        );
                    }

                    if (ImageManager::retinaSupport()) {
                        if (!ImageManager::resize($file, $dir.$language['iso_code'].'-default-'.stripslashes($imageType['name']).'2x.jpg', (int) $imageType['width'] * 2, (int) $imageType['height'] * 2)) {
                            $errors = true;
                        }

                        if (ImageManager::webpSupport()) {
                            ImageManager::resize(
                                $file,
                                $dir.$language['iso_code'].'-default-'.stripslashes($imageType['name']).'2x.webp',
                                (int) $imageType['width'],
                                (int) $imageType['height'],
                                'webp'
                            );
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Move product images to the new filesystem
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function _moveImagesToNewFileSystem()
    {
        if (!Image::testFileSystem()) {
            $this->errors[] = Tools::displayError('Error: Your server configuration is not compatible with the new image system. No images were moved.');
        } else {
            ini_set('max_execution_time', $this->max_execution_time); // ini_set may be disabled, we need the real value
            $this->max_execution_time = (int) ini_get('max_execution_time');
            $result = Image::moveToNewFileSystem($this->max_execution_time);
            if ($result === 'timeout') {
                $this->errors[] = Tools::displayError('Not all images have been moved. The server timed out before finishing. Click on "Move images" again to resume the moving process.');
            } elseif ($result === false) {
                $this->errors[] = Tools::displayError('Error: Some -- or all -- images cannot be moved.');
            }
        }

        return (count($this->errors) > 0 ? false : true);
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @since 1.0.0
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
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function initContent()
    {
        if ($this->display != 'edit' && $this->display != 'add') {
            $this->initRegenerate();
            $this->initMoveImages();

            $this->context->smarty->assign(
                [
                    'display_regenerate' => true,
                    'display_move'       => $this->display_move,
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
     * @since 1.0.0
     */
    public function initRegenerate()
    {
        $types = [
            'categories'    => $this->l('Categories'),
            'manufacturers' => $this->l('Manufacturers'),
            'suppliers'     => $this->l('Suppliers'),
            'scenes'        => $this->l('Scenes'),
            'products'      => $this->l('Products'),
            'stores'        => $this->l('Stores'),
        ];

        $formats = [];
        foreach ($types as $i => $type) {
            $formats[$i] = ImageType::getImagesTypes($i);
        }

        $this->context->smarty->assign(
            [
                'types'   => $types,
                'formats' => $formats,
            ]
        );
    }

    /**
     * Init display for moving the images block
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function initMoveImages()
    {
        $this->context->smarty->assign(
            [
                'link_ppreferences' => 'index.php?tab=AdminPPreferences&token='.Tools::getAdminTokenLite('AdminPPreferences').'#PS_LEGACY_IMAGES_on',
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
     * @since 1.0.0
     */
    protected function _childValidation()
    {
        if (!Tools::getValue('id_image_type') && Validate::isImageTypeName($typeName = Tools::getValue('name')) && ImageType::typeAlreadyExists($typeName)) {
            $this->errors[] = Tools::displayError('This name already exists.');
        }
    }

    /**
     * @return array|false
     *
     * @since 1.0.4
     */
    protected function getIndexationStatus()
    {
        try {
            return [
                'products'      => [
                    'indexed' => (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from(bqSQL(Product::$definition['table']))
                            ->where('`'.bqSQL(Product::$definition['primary']).'` <= '.(int) Configuration::get('TB_IMAGES_LAST_UPD_PRODUCTS'))
                    ),
                    'total'   => (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from(bqSQL(Product::$definition['table']))
                    ),
                ],
                'categories'    => [
                    'indexed' => (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from(bqSQL(Category::$definition['table']))
                            ->where('`'.bqSQL(Category::$definition['primary']).'` <= '.(int) Configuration::get('TB_IMAGES_LAST_UPD_CATEGORIES'))
                    ),
                    'total'   => (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from(bqSQL(Category::$definition['table']))
                    ),
                ],
                'suppliers'     => [
                    'indexed' => (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from(bqSQL(Supplier::$definition['table']))
                            ->where('`'.bqSQL(Supplier::$definition['primary']).'` <= '.(int) Configuration::get('TB_IMAGES_LAST_UPD_SUPPLIERS'))
                    ),
                    'total'   => (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from(bqSQL(Supplier::$definition['table']))
                    ),
                ],
                'manufacturers' => [
                    'indexed' => (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from(bqSQL(Manufacturer::$definition['table']))
                            ->where('`'.bqSQL(Manufacturer::$definition['primary']).'` <= '.(int) Configuration::get('TB_IMAGES_LAST_UPD_MANUFACTURERS'))
                    ),
                    'total'   => (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from(bqSQL(Manufacturer::$definition['table']))
                    ),
                ],
                'scenes'        => [
                    'indexed' => (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from('scene_category')
                            ->where('`id_scene` <= '.(int) Configuration::get('TB_IMAGES_LAST_UPD_SCENES'))
                    ),
                    'total'   => (int) Db::getInstance()->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from('scene_category')
                    ),
                ],
                'stores'        => [
                    'indexed' => (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from(bqSQL(Store::$definition['table']))
                            ->where('`'.bqSQL(Store::$definition['primary']).'` <= '.(int) Configuration::get('TB_IMAGES_LAST_UPD_STORES'))
                    ),
                    'total'   => (int) Db::getInstance()->getValue(
                        (new DbQuery())
                            ->select('COUNT(*)')
                            ->from(bqSQL(Store::$definition['table']))
                    ),
                ],
            ];
        } catch (Exception $e) {
            return false;
        }
    }
}
